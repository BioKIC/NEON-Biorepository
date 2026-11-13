<?php

class SearchParameterNames {
    private static $dictionaries = array();
    private $conn;
    private $root;
    private $entries = array();
    private $taxa = array();

    public function __construct($conn, $root = null) {
        $this->conn = $conn;
        $this->root = $root ?? $GLOBALS['SERVER_ROOT'];
    }

    public static function slug($value) {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (function_exists('iconv')) {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($ascii !== false) $value = $ascii;
        }
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($value)), '-');
    }

    private function tokens($value) {
        $tokens = array();
        foreach (is_array($value) ? $value : array($value) as $part) {
            if (!is_scalar($part)) return array('!invalid');
            foreach (preg_split('/[,;]+/', (string) $part) as $token) {
                $token = trim($token);
                if ($token !== '') $tokens[] = $token;
            }
        }
        return array_values(array_unique($tokens));
    }

    private function add($key, $token, $ids, $label, $view = '', $public = true) {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids, SORT_NUMERIC);
        if (!$ids || !$token) return;
        $lookup = strtolower($token);
        if (isset($this->entries[$key][$lookup])) {
            if ($this->entries[$key][$lookup]['ids'] !== $ids) $this->entries[$key][$lookup]['ambiguous'] = true;
            return;
        }
        $this->entries[$key][$lookup] = array(
            'token' => $token, 'ids' => $ids, 'label' => $label, 'view' => $view,
            'public' => (bool) $public, 'ambiguous' => false,
        );
    }

    private function parameterTokens($key, $value) {
        if ($key === 'db') {
            $parts = is_array($value) ? $value : array($value);
            foreach ($parts as $part) if (!is_scalar($part)) return array('!invalid');
            // The legacy db format ignores its optional semicolon suffix.
            $value = explode(';', implode(',', $parts), 2)[0];
        }
        return $this->tokens($value);
    }

    private function tree($nodes, $path, $view) {
        $ids = array();
        foreach ($nodes as $node) {
            $parts = array_merge($path, array(self::slug($node['name'])));
            $children = isset($node['collid']) ? array((int) $node['collid'])
                : $this->tree($node['children'] ?? array(), $parts, $view);
            $this->add('db', implode('/', $parts), $children, $node['name'], $view);
            $ids = array_merge($ids, $children);
        }
        return array_values(array_unique($ids));
    }

    private function loadCollections() {
        if (isset($this->entries['db'])) return;
        $this->entries['db'] = array();
        $views = array('taxonomic' => array(), 'protocol' => array('protocol'), 'sampletype' => array('preservation'));
        foreach ($views as $view => $prefix) {
            $path = $this->root . '/neon-react/biorepo_lib/collections-' . $view . '.json';
            $nodes = is_file($path) ? json_decode(file_get_contents($path), true) : array();
            if (is_array($nodes)) $this->tree($nodes, $prefix, $view);
        }
        $result = $this->conn->query('SELECT collid, collectionname FROM omcollections');
        if (!$result) return;
        $rows = array();
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        $result->free();
        $counts = array_count_values(array_map(function ($row) { return self::slug($row['collectionname']); }, $rows));
        foreach ($rows as $row) {
            $slug = self::slug($row['collectionname']);
            $token = 'collection:' . $slug . ($counts[$slug] > 1 || !$slug ? '~' . $row['collid'] : '');
            $this->add('db', $token, array($row['collid']), $row['collectionname']);
        }
    }

    private function loadDatasets() {
        if (isset($this->entries['datasetid'])) return;
        $this->entries['datasetid'] = array();
        $path = $this->root . '/neon/config/search-geography.json';
        $geography = is_file($path) ? json_decode(file_get_contents($path), true) : array();
        $sites = $geography['sites'] ?? array();
        $domains = array();
        foreach ($sites as $site) $domains[$site['domainCode']] = $site['domainName'];
        $result = $this->conn->query('SELECT datasetid, name, datasetName, ispublic FROM omoccurdatasets');
        if (!$result) return;
        $rows = array();
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        $result->free();
        $titles = array_map(function ($row) { return self::slug($row['datasetName'] ?: $row['name']); }, $rows);
        $counts = array_count_values($titles);
        $names = array_count_values(array_map(function ($row) { return strtoupper(trim($row['name'] ?? '')); }, $rows));
        $groups = array();
        foreach ($rows as $index => $row) {
            $name = strtoupper(trim($row['name'] ?? ''));
            $title = $row['datasetName'] ?: $row['name'];
            $id = (int) $row['datasetid'];
            if (isset($sites[$name]) && $names[$name] === 1 && !empty($row['ispublic'])) {
                $site = $sites[$name];
                $this->add('datasetid', $name, array($id), $name . ' — ' . $site['siteName'], 'site');
                foreach (array('domain', 'state') as $kind) {
                    $token = $kind . ':' . self::slug($site[$kind . 'Name']);
                    if (!isset($groups[$token])) $groups[$token] = array('ids' => array(), 'label' => $site[$kind . 'Name'], 'kind' => $kind);
                    $groups[$token]['ids'][] = $id;
                }
            }
            if (isset($domains[$name]) && $names[$name] === 1) {
                $this->add('datasetid', 'dataset:domain-' . self::slug($domains[$name]), array($id),
                    $domains[$name] . ' (' . $name . ')', 'dataset', !empty($row['ispublic']));
            }
            $slug = $titles[$index];
            $token = 'dataset:' . $slug . ($counts[$slug] > 1 || !$slug ? '~' . $id : '');
            $this->add('datasetid', $token, array($id), $title, 'dataset', !empty($row['ispublic']));
        }
        foreach ($groups as $token => $group) {
            $this->add('datasetid', $token, $group['ids'], $group['label'], $group['kind']);
        }
    }

    public function dictionary($key) {
        $cacheKey = $this->root . ':' . $key;
        if (isset(self::$dictionaries[$cacheKey])) return self::$dictionaries[$cacheKey];
        if ($key === 'db') $this->loadCollections();
        elseif ($key === 'datasetid') $this->loadDatasets();
        return self::$dictionaries[$cacheKey] = array_filter($this->entries[$key] ?? array(), function ($entry) { return !$entry['ambiguous']; });
    }

    public function resolve($key, $value) {
        $tokens = $this->parameterTokens($key, $value);
        $ids = array();
        foreach ($tokens as $token) {
            if (ctype_digit($token)) {
                $ids[] = (string) (int) $token;
            } elseif ($key === 'db' && in_array($token, array('all', 'allspec', 'allobs'), true)) {
                $ids[] = $token;
            } else {
                $entries = $this->dictionary($key);
                $entry = $entries[strtolower($token)] ?? null;
                // An unknown name must never silently remove a filter or broaden a search.
                if (!$entry) return '0,0';
                $ids = array_merge($ids, array_map('strval', $entry['ids']));
            }
        }
        $resolved = implode(',', array_unique($ids));
        // Legacy request readers treat the string "0" as an absent filter.
        return $resolved === '0' ? '0,0' : $resolved;
    }

    public function encode($key, $value) {
        $tokens = $this->parameterTokens($key, $value);
        $entries = $this->dictionary($key);
        $remaining = array();
        $named = array();
        foreach ($tokens as $token) {
            if ($key === 'db' && in_array($token, array('all', 'allspec', 'allobs'), true)) $named[] = $token;
            elseif (ctype_digit($token)) $remaining[(int) $token] = true;
            elseif (isset($entries[strtolower($token)])) $named[] = $entries[strtolower($token)]['token'];
            else return '0';
        }
        // Largest complete groups first; never replace a partial selection with its parent.
        uasort($entries, function ($a, $b) { return count($b['ids']) <=> count($a['ids']); });
        foreach ($entries as $entry) {
            if (count($entry['ids']) < 2 || array_diff($entry['ids'], array_keys($remaining))) continue;
            $named[] = $entry['token'];
            foreach ($entry['ids'] as $id) unset($remaining[$id]);
        }
        foreach (array_keys($remaining) as $id) {
            $token = (string) $id;
            foreach ($entries as $entry) {
                if ($entry['ids'] === array($id)) { $token = $entry['token']; break; }
            }
            $named[] = $token;
        }
        return implode(',', array_unique($named));
    }

    private function loadTaxa($ids) {
        $ids = array_values(array_filter(array_unique(array_map('intval', $ids)), function ($id) { return $id > 0 && !array_key_exists($id, $this->taxa); }));
        if (!$ids) return;
        foreach ($ids as $id) $this->taxa[$id] = null;
        $result = $this->conn->query('SELECT t.tid, t.sciname, (SELECT COUNT(*) FROM taxa n WHERE n.sciname = t.sciname) AS nameCount FROM taxa t WHERE t.tid IN(' . implode(',', $ids) . ')');
        if (!$result) return;
        while ($row = $result->fetch_assoc()) $this->taxa[(int) $row['tid']] = $row;
        $result->free();
    }

    public function encodeTaxa($value) {
        $tokens = $this->tokens($value);
        $this->loadTaxa(array_filter($tokens, 'ctype_digit'));
        foreach ($tokens as &$token) {
            if (!ctype_digit($token) || empty($this->taxa[(int) $token])) continue;
            $row = $this->taxa[(int) $token];
            // Preserve names that the legacy taxon reader would split or strip.
            if (preg_match("/[^a-zA-Z0-9\\s\\-\\.()'×†]/u", $row['sciname'])) {
                $token = 'taxon:' . self::slug($row['sciname']) . '~' . $row['tid'];
            } else {
                $token = $row['sciname'] . ((int) $row['nameCount'] > 1 ? '~' . $row['tid'] : '');
            }
        }
        return implode(',', $tokens);
    }

    private function resolveTaxa($value) {
        $tokens = $this->tokens($value);
        foreach ($tokens as &$token) {
            if (!preg_match('/^(.+)~([0-9]+)$/u', $token, $match)) continue;
            $id = (int) $match[2];
            $this->loadTaxa(array($id));
            $name = $this->taxa[$id]['sciname'] ?? '';
            $matches = $name !== '' && (strcasecmp($match[1], $name) === 0
                || $match[1] === 'taxon:' . self::slug($name));
            $token = $matches ? (string) $id : '-1';
        }
        return implode(',', $tokens);
    }

    public function normalize($request) {
        foreach (array('db', 'datasetid') as $key) {
            if (isset($request[$key])) $request[$key] = $this->resolve($key, $request[$key]);
        }
        if (isset($request['taxa'])) $request['taxa'] = $this->resolveTaxa($request['taxa']);
        if (isset($request['searchvar']) && is_string($request['searchvar'])) {
            parse_str(str_replace('&amp;', '&', $request['searchvar']), $nested);
            unset($nested['searchvar']);
            $request['searchvar'] = self::queryString($this->normalize($nested));
        }
        return $request;
    }

    public function canonical($request) {
        foreach (array('db', 'datasetid') as $key) {
            if (isset($request[$key])) $request[$key] = $this->encode($key, $request[$key]);
        }
        if (isset($request['taxa'])) $request['taxa'] = $this->encodeTaxa($request['taxa']);
        if (isset($request['searchvar']) && is_string($request['searchvar'])) {
            parse_str(str_replace('&amp;', '&', $request['searchvar']), $nested);
            unset($nested['searchvar']);
            $request['searchvar'] = self::queryString($this->canonical($nested));
        }
        return $request;
    }

    public static function queryString($params) {
        $parts = array();
        foreach ($params as $key => $value) {
            foreach (is_array($value) ? $value : array($value) as $entry) {
                if (!is_scalar($entry)) continue;
                $parts[] = rawurlencode($key . (is_array($value) ? '[]' : '')) . '=' . rawurlencode((string) $entry);
            }
        }
        return implode('&', $parts);
    }

    public function redirect($path) {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET' || headers_sent()) return;
        $params = $this->canonical($_GET);
        if ($params === $_GET) return;
        header('Location: ' . $path . '?' . self::queryString($params), true, 302);
        exit;
    }

    public function clientConfig($request) {
        $resolved = $this->normalize($request);
        $selected = array_map('intval', $this->tokens($resolved['datasetid'] ?? ''));
        $datasets = array_filter($this->dictionary('datasetid'), function ($entry) use ($selected) {
            return $entry['public'] || array_intersect($entry['ids'], $selected);
        });
        $taxa = array();
        foreach ($this->tokens($request['taxa'] ?? '') as $term) {
            if (ctype_digit($term)) $taxa[$term] = $this->encodeTaxa($term);
        }
        // Keep IDs for search controls; display and submit the scientific name when it is unique.
        if (isset($resolved['taxa'])) $resolved['taxa'] = $this->encodeTaxa($resolved['taxa']);
        return array('query' => self::queryString($resolved), 'db' => array_values($this->dictionary('db')),
            'datasetid' => array_values($datasets), 'taxa' => $taxa);
    }
}
