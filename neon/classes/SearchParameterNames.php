<?php

class SearchParameterNames {
    private static $dictionaries = array();
    private $conn;
    private $root;
    private $entries = array();
    private $collectionCodes = array();
    private $collectionHint;
    private $taxa = array();
    private $scopes = array();

    public function __construct($conn, $root = null) {
        $this->conn = $conn;
        $this->root = $root ?? $GLOBALS['SERVER_ROOT'];
        foreach (array($_GET ?? array(), $_REQUEST ?? array()) as $request) {
            if (!isset($request['db']) && isset($request['searchvar']) && is_string($request['searchvar'])) {
                parse_str(str_replace('&amp;', '&', $request['searchvar']), $nested);
                $request['db'] = $nested['db'] ?? null;
            }
            if (isset($request['db'])) {
                $this->collectionHint = $request['db'];
                break;
            }
        }
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
        $result = $this->conn->query('SELECT c.collid, c.collectionname, c.collectioncode,
            EXISTS (SELECT 1 FROM omcollcatlink l INNER JOIN omcollcategories cat ON cat.ccpk = l.ccpk
                WHERE l.collid = c.collid AND cat.category = "Additional NEON Collections") AS isOtherRepository
            FROM omcollections c');
        if (!$result) return;
        $rows = array();
        while ($row = $result->fetch_assoc()) $rows[] = $row;
        $result->free();
        $otherIds = array_column(array_filter($rows, function ($row) { return !empty($row['isOtherRepository']); }), 'collid');
        $this->add('db', 'all-others', $otherIds, 'All NEON Sample Types at Other Repositories', 'other');
        $counts = array_count_values(array_map(function ($row) { return self::slug($row['collectionname']); }, $rows));
        $codeCounts = array_count_values(array_map(function ($row) { return strtolower(trim($row['collectioncode'] ?? '')); }, $rows));
        foreach ($rows as $row) {
            $slug = self::slug($row['collectionname']);
            $token = 'collection:' . $slug . ($counts[$slug] > 1 || !$slug ? '~' . $row['collid'] : '');
            $this->add('db', $token, array($row['collid']), $row['collectionname']);
            $code = trim($row['collectioncode'] ?? '');
            if ($codeCounts[strtolower($code)] === 1 && preg_match('/^(?=.*[a-z])[a-z0-9._-]+$/iD', $code)
                && !in_array(strtolower($code), array('all', 'allspec', 'allobs'), true)) {
                $this->collectionCodes[(int) $row['collid']] = array('code' => $code, 'label' => $row['collectionname']);
            }
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
                    if (!isset($groups[$token])) $groups[$token] = array('ids' => array(), 'label' => $site[$kind . 'Name'], 'kind' => $kind, 'short' => $site[$kind . 'Code']);
                    $groups[$token]['ids'][] = $id;
                }
            }
            if (isset($domains[$name]) && $names[$name] === 1) {
                $this->add('datasetid', 'dataset:domain-' . self::slug($domains[$name]), array($id),
                    $domains[$name] . ' (' . $name . ')', 'dataset', !empty($row['ispublic']));
                $this->entries['datasetid']['dataset:domain-' . self::slug($domains[$name])]['short'] = 'dataset:' . $name;
            }
            $slug = $titles[$index];
            $token = 'dataset:' . $slug . ($counts[$slug] > 1 || !$slug ? '~' . $id : '');
            $this->add('datasetid', $token, array($id), $title, 'dataset', !empty($row['ispublic']));
        }
        foreach ($groups as $token => $group) {
            $this->add('datasetid', $token, $group['ids'], $group['label'], $group['kind']);
            $this->entries['datasetid'][$token]['short'] = $group['short'];
        }
    }

    private function shortNames($entries) {
        $suffixes = array();
        foreach ($entries as $entry) {
            $parts = explode('/', strtolower($entry['token']));
            while ($parts) {
                $suffix = implode('/', $parts);
                if (!isset($suffixes[$suffix])) $suffixes[$suffix] = $entry['ids'];
                elseif ($suffixes[$suffix] !== $entry['ids']) $suffixes[$suffix] = false;
                array_shift($parts);
            }
        }
        foreach ($entries as &$entry) {
            if (isset($entry['short'])) continue;
            $parts = explode('/', $entry['token']);
            $entry['short'] = $entry['token'];
            for ($length = 1; $length <= count($parts); $length++) {
                $suffix = implode('/', array_slice($parts, -$length));
                if ($suffixes[strtolower($suffix)] === $entry['ids']) {
                    $entry['short'] = $suffix;
                    break;
                }
            }
        }
        return $entries;
    }

    private function scopedEntries($entries, $scope) {
        $cacheKey = array_key_first($entries) . ':' . implode(',', $scope);
        if (isset($this->scopes[$cacheKey])) return $this->scopes[$cacheKey];
        $scoped = array();
        foreach ($entries as $key => $entry) {
            if (array_diff($entry['ids'], $scope)) continue;
            if (strpos($entry['token'], '/') !== false) unset($entry['short']);
            $scoped[$key] = $entry;
        }
        return $this->scopes[$cacheKey] = $this->shortNames($scoped);
    }

    public function dictionary($key) {
        $cacheKey = $this->root . ':' . $key;
        if (isset(self::$dictionaries[$cacheKey])) return self::$dictionaries[$cacheKey];
        if ($key === 'db') $this->loadCollections();
        elseif ($key === 'datasetid') $this->loadDatasets();
        $entries = array_filter($this->entries[$key] ?? array(), function ($entry) { return !$entry['ambiguous']; });
        if ($key === 'db') {
            $entries = $this->shortNames($entries);
            $taxonomic = array_filter($entries, function ($entry) { return $entry['view'] === 'taxonomic'; });
            foreach ($taxonomic as &$entry) unset($entry['short']);
            unset($entry);
            // Reserve the shortest names for the default taxonomic tree.
            $entries = array_replace($entries, $this->shortNames($taxonomic));
            $codes = array();
            foreach ($this->collectionCodes as $id => $collection) {
                $code = $collection['code'];
                $existing = $this->lookup($entries, $code);
                // Keep existing group/name URLs unambiguous when codes collide with an alias.
                if ($existing !== null && $existing !== array($id)) continue;
                $codes[$id] = $code;
                if ($existing === null) {
                    $entries[strtolower($code)] = array(
                        'token' => $code, 'short' => $code, 'ids' => array($id), 'label' => $collection['label'],
                        'view' => 'code', 'public' => true, 'ambiguous' => false,
                    );
                }
            }
            foreach ($entries as &$entry) {
                if ($entry['view'] !== 'other' && count($entry['ids']) === 1 && isset($codes[$entry['ids'][0]])) $entry['code'] = $codes[$entry['ids'][0]];
            }
            unset($entry);
        }
        return self::$dictionaries[$cacheKey] = $entries;
    }

    private function lookup($entries, $token) {
        $token = strtolower($token);
        // Keep previously shared URLs working after standardizing the group name.
        if ($token === 'all-other' && isset($entries['all-others'])) $token = 'all-others';
        if (isset($entries[$token])) return $entries[$token]['ids'];
        $ids = null;
        foreach ($entries as $entry) {
            if (strtolower($entry['short'] ?? $entry['token']) !== $token) continue;
            if ($ids !== null && $ids !== $entry['ids']) return null;
            $ids = $entry['ids'];
        }
        return $ids;
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
                $parts = explode('!', $token);
                $base = array_shift($parts);
                $selected = $this->lookup($entries, $base);
                // An unknown name must never silently remove a filter or broaden a search.
                if ($selected === null) return '0,0';
                if ($parts) {
                    $scoped = $this->scopedEntries($entries, $selected);
                    foreach ($parts as $part) {
                        $excluded = ctype_digit($part) ? array((int) $part) : $this->lookup($scoped, $part);
                        if (!$excluded || array_diff($excluded, $selected)) return '0,0';
                        $selected = array_values(array_diff($selected, $excluded));
                    }
                }
                $ids = array_merge($ids, array_map('strval', $selected));
            }
        }
        $resolved = implode(',', array_unique($ids));
        // Legacy request readers treat the string "0" as an absent filter.
        return $resolved === '0' || ($tokens && $resolved === '') ? '0,0' : $resolved;
    }

    public function collectionView($value = null) {
        $entries = $this->dictionary('db');
        foreach (array($value, $this->collectionHint) as $selection) {
            if ($selection === null) continue;
            foreach ($this->parameterTokens('db', $selection) as $token) {
                $base = strtolower(explode('!', $token, 2)[0]);
                $entry = $entries[$base] ?? null;
                if (!$entry) {
                    foreach ($entries as $candidate) {
                        if (strtolower($candidate['short'] ?? $candidate['token']) === $base) {
                            $entry = $candidate;
                            break;
                        }
                    }
                }
                if ($entry && count($entry['ids']) > 1 && in_array($entry['view'], array('taxonomic', 'protocol', 'sampletype'), true)) return $entry['view'];
            }
        }
        return 'taxonomic';
    }

    private function cover($ids, $entries, $view = null) {
        $tokens = array();
        uasort($entries, function ($a, $b) {
            return count($b['ids']) <=> count($a['ids']);
        });
        foreach ($entries as $entry) {
            if ($view !== null && count($entry['ids']) > 1 && $entry['view'] !== $view && $entry['view'] !== 'other') continue;
            if (array_diff($entry['ids'], $ids)) continue;
            $tokens[] = $entry['code'] ?? $entry['short'] ?? $entry['token'];
            $ids = array_values(array_diff($ids, $entry['ids']));
        }
        return array_merge($tokens, array_map('strval', $ids));
    }

    private function compactSelection($ids, $entries, $view = null) {
        $best = $this->cover($ids, $entries, $view);
        $bestLength = strlen(rawurlencode(implode(',', $best)));
        $remainder = null;
        $prefix = null;
        foreach ($entries as $entry) {
            if ($view !== null && $entry['view'] !== $view && $entry['view'] !== 'other') continue;
            $selected = array_values(array_intersect($entry['ids'], $ids));
            $missing = array_values(array_diff($entry['ids'], $ids));
            if (count($selected) < 2 || !$missing) continue;
            $base = $entry['short'] ?? $entry['token'];
            if (strlen(rawurlencode($base)) + 4 >= $bestLength) continue;
            $excluded = $this->cover($missing, $this->scopedEntries($entries, $entry['ids']), $view);
            $term = $base . '!' . implode('!', $excluded);
            $rest = array_values(array_diff($ids, $selected));
            $candidate = array_merge(array($term), $this->cover($rest, $entries, $view));
            $length = strlen(rawurlencode(implode(',', $candidate)));
            if ($length >= $bestLength) continue;
            $bestLength = $length;
            $best = $candidate;
            $prefix = $term;
            $remainder = $rest;
        }
        return $remainder === null ? $best : array_merge(array($prefix), $this->compactSelection($remainder, $entries, $view));
    }

    public function encode($key, $value, $view = null) {
        if ($key === 'db' && $view === null) $view = $this->collectionView($value);
        $tokens = $this->parameterTokens($key, $this->resolve($key, $value));
        $entries = $this->dictionary($key);
        $remaining = array();
        $named = array();
        foreach ($tokens as $token) {
            if ($key === 'db' && in_array($token, array('all', 'allspec', 'allobs'), true)) $named[] = $token;
            elseif (ctype_digit($token)) $remaining[(int) $token] = true;
            else return '0';
        }
        $named = array_merge($named, $this->compactSelection(array_keys($remaining), $entries, $view));
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
        $request['includeothercatnum'] = '1';
        $request['usethes'] = in_array($request['usethes'] ?? null, array('0', 0), true) ? '0' : '1';
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
        unset($request['includeothercatnum']);
        if (in_array($request['usethes'] ?? null, array('0', 0), true)) $request['usethes'] = '0';
        else unset($request['usethes']);
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
