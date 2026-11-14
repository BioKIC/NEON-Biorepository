<?php
require_once __DIR__ . '/SearchParameterNames.php';
require_once __DIR__ . '/../../classes/NeonMaterialSampleCollections.php';

/** Public research projects, paged before loading any record-level information. */
class PublicProjectSearch {
    private $conn;
    private $ownsConnection;
    private $catalog;

    public function __construct($conn = null) {
        $this->ownsConnection = $conn === null;
        $this->conn = $conn ?? MySQLiConnectionFactory::getCon('readonly');
        if (!$this->conn) throw new RuntimeException('Unable to connect to the project database.');
    }

    public function __destruct() {
        if ($this->ownsConnection && $this->conn) $this->conn->close();
    }

    private function rows($sql, $values = array()) {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) throw new RuntimeException('Unable to prepare a public project query.');
        try {
            if ($values) $stmt->bind_param(str_repeat('s', count($values)), ...$values);
            if (!$stmt->execute()) throw new RuntimeException('Unable to execute a public project query.');
            $result = $stmt->get_result();
            if (!$result) throw new RuntimeException('Unable to read public projects.');
            $rows = array();
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            $result->free();
            return $rows;
        } finally {
            $stmt->close();
        }
    }

    private function text($value) {
        return trim(html_entity_decode(strip_tags($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public function options() {
        if ($this->catalog !== null) return $this->catalog;
        $this->catalog = array('db' => array(), 'site' => array());
        $codes = array();
        $names = new SearchParameterNames($this->conn);
        foreach ($names->dictionary('db') as $entry) {
            if (count($entry['ids']) === 1 && isset($entry['code'])) $codes[$entry['ids'][0]] = $entry['code'];
        }
        foreach ($this->rows('SELECT collid, COALESCE(NULLIF(publicName, \'\'), collectionname) AS label, collectioncode
            FROM omcollections ORDER BY label, collid') as $row) {
            $this->catalog['db'][] = array('value' => $codes[$row['collid']] ?? (string)$row['collid'],
                'collid' => (string)$row['collid'], 'label' => $this->text($row['label']),
                'code' => $row['collectioncode'] ?? '');
        }
        $path = $GLOBALS['SERVER_ROOT'] . '/neon/config/search-geography.json';
        $geography = is_readable($path) ? json_decode(file_get_contents($path), true) : array();
        $sites = array();
        $domains = array();
        foreach ($this->rows("SELECT d.name, d.datasetName FROM omoccurdatasets d
            WHERE d.ispublic = 1 AND d.notes = 'NEON Site' ORDER BY d.name") as $row) {
            $code = strtoupper(trim($row['name']));
            $site = $geography['sites'][$code] ?? array();
            $siteName = $site['siteName'] ?? $row['datasetName'] ?? $code;
            $sites[$code] = array('value' => $code, 'label' => $code . ($siteName !== $code ? ' — ' . $this->text($siteName) : ''),
                'name' => $siteName, 'group' => 'Sites', 'sites' => array($code));
            if (!empty($site['domainCode'])) {
                $domain = $site['domainCode'];
                if (!isset($domains[$domain])) {
                    $domainName = $site['domainName'] ?? $domain;
                    $domains[$domain] = array('value' => $domain, 'label' => $domain . ' — ' . $this->text($domainName),
                        'name' => $domainName, 'group' => 'Domains', 'sites' => array());
                }
                $domains[$domain]['sites'][$code] = $code;
            }
        }
        foreach ($domains as &$domain) $domain['sites'] = array_values($domain['sites']);
        unset($domain);
        ksort($domains);
        ksort($sites);
        $this->catalog['site'] = array_merge(array_values($domains), array_values($sites));
        return $this->catalog;
    }

    private function tokens($value) {
        $tokens = array();
        foreach (is_array($value) ? $value : array($value) as $part) {
            if (!is_scalar($part) || strlen((string)$part) > 5000) throw new InvalidArgumentException('Invalid project filter.');
            foreach (explode(',', (string)$part) as $token) if (trim($token) !== '') $tokens[] = trim($token);
        }
        if (count($tokens) > 250) throw new InvalidArgumentException('Too many project filters.');
        return array_values(array_unique($tokens));
    }

    public function filters($input) {
        $input = array_change_key_case($input, CASE_LOWER);
        $names = new SearchParameterNames($this->conn);
        $collections = $this->tokens($input['db'] ?? $input['collid'] ?? '');
        if ($collections) {
            $collections = explode(',', $names->resolve('db', $collections));
            if (in_array('all', $collections, true)) {
                foreach ($names->dictionary('db') as $entry) {
                    if ($entry['view'] === 'taxonomic') $collections = array_merge($collections, $entry['ids']);
                }
            }
            $collections = array_values(array_unique(array_map('strval', array_filter($collections, 'is_numeric'))));
            if (!$collections) $collections = array('0');
        }
        $sites = $this->tokens($input['site'] ?? $input['sitename'] ?? '');
        $options = $this->options();
        $sites = array_map(function ($site) use ($options) {
            foreach ($options['site'] as $option) {
                if (strcasecmp($site, $option['value']) === 0 || strcasecmp($site, $option['name']) === 0
                    || strcasecmp($site, $option['label']) === 0) return $option['value'];
            }
            return $site;
        }, $sites);
        $q = $input['q'] ?? $input['search'] ?? '';
        if (!is_scalar($q) || strlen((string)$q) > 500) throw new InvalidArgumentException('Invalid project search text.');
        $page = $input['page'] ?? 1;
        $pageSize = $input['pagesize'] ?? 25;
        if (!is_scalar($page) || !is_scalar($pageSize)) throw new InvalidArgumentException('Invalid project page.');
        return array('db' => $collections, 'site' => array_values(array_unique($sites)),
            'q' => trim((string)$q), 'page' => max(1, min(1000000, (int)$page)),
            'pageSize' => in_array((int)$pageSize, array(10, 25, 50, 100), true) ? (int)$pageSize : 25);
    }

    public function clientFilters($input) {
        $filters = $this->filters($input);
        $codes = array_column($this->options()['db'], 'value', 'collid');
        $filters['db'] = array_map(function ($id) use ($codes) { return $codes[$id] ?? $id; }, $filters['db']);
        return $filters;
    }

    private function baseFrom() {
        // One request row per project, even if the request table contains duplicate links.
        return ' FROM omoccurdatasets od
            INNER JOIN neonrequest nr ON nr.id = (SELECT MIN(nr0.id) FROM neonrequest nr0
                WHERE nr0.datasetID = od.datasetid AND nr0.activeDate IS NOT NULL)
            LEFT JOIN neonresearcher r ON r.researcherID = nr.researcherID ';
    }

    private function baseWhere() {
        return " od.ispublic = 1 AND od.category = 'Request' ";
    }

    private function inList($column, $ids, &$values) {
        if (!$ids) return '1 = 0';
        $values = array_merge($values, array_values($ids));
        return $column . ' IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
    }

    private function recordWhere($filters, &$values) {
        $parts = array('projectLink.datasetid = od.datasetid');
        if ($filters['db']) {
            $types = NeonMaterialSampleCollections::TYPES;
            $normal = array_diff($filters['db'], array_keys($types));
            $collections = $normal ? array($this->inList('o.collid', $normal, $values)) : array();
            foreach ($types as $id => $pattern) if (in_array((string)$id, $filters['db'], true)) {
                $collections[] = ' (o.collid IN (17,19,28) AND EXISTS (SELECT 1 FROM ommaterialsample ms
                    WHERE ms.occid = o.occid AND ms.sampleType LIKE ?)) ';
                $values[] = $pattern;
            }
            $parts[] = '(' . implode(' OR ', $collections) . ')';
        }
        if ($filters['site']) {
            $siteCodes = array();
            foreach ($this->options()['site'] as $option) {
                if (in_array($option['value'], $filters['site'], true)) $siteCodes = array_merge($siteCodes, $option['sites']);
            }
            $parts[] = "EXISTS (SELECT 1 FROM omoccurdatasetlink sl INNER JOIN omoccurdatasets s ON s.datasetid = sl.datasetid
                WHERE sl.occid = o.occid AND s.ispublic = 1 AND s.notes = 'NEON Site' AND "
                . $this->inList('s.name', array_values(array_unique($siteCodes)), $values) . ')';
        }
        return 'EXISTS (SELECT 1 FROM omoccurdatasetlink projectLink INNER JOIN omoccurrences o ON o.occid = projectLink.occid
            WHERE ' . implode(' AND ', $parts) . ')';
    }

    public function search($input) {
        $filters = $this->filters($input);
        $values = array();
        $where = $this->baseWhere();
        if ($filters['q'] !== '') {
            $fields = array('od.name', 'od.datasetName', 'od.description', 'r.name', 'r.institution');
            // Match each word across the full catalog, including titles with inline HTML.
            foreach (preg_split('/\s+/', $filters['q']) as $word) {
                $term = '%' . strtr($word, array('!' => '!!', '%' => '!%', '_' => '!_')) . '%';
                $where .= ' AND (' . implode(' OR ', array_map(function ($field) { return $field . " LIKE ? ESCAPE '!'"; }, $fields)) . ')';
                foreach ($fields as $field) $values[] = $term;
            }
        }
        if ($filters['db'] || $filters['site']) {
            $where .= ' AND ' . $this->recordWhere($filters, $values);
        }
        $from = $this->baseFrom() . ' WHERE ' . $where;
        $total = (int)$this->rows('SELECT COUNT(*) AS total' . $from, $values)[0]['total'];
        $page = min($filters['page'], max(1, (int)ceil($total / $filters['pageSize'])));
        $offset = ($page - 1) * $filters['pageSize'];
        $rows = $total ? $this->rows('SELECT od.datasetid, od.name, od.description, r.name AS researcherName,
            r.institution, nr.activeDate, nr.completeDate' . $from
            . ' ORDER BY nr.activeDate DESC, od.datasetid DESC LIMIT ' . $filters['pageSize'] . ' OFFSET ' . $offset, $values) : array();
        foreach ($rows as &$row) $row['datasetid'] = (int)$row['datasetid'];
        unset($row);
        return array('rows' => $rows, 'total' => $total, 'page' => $page, 'pageSize' => $filters['pageSize']);
    }

    public function overview($cacheFile = null) {
        if ($cacheFile === null) $cacheFile = $GLOBALS['SERVER_ROOT'] . '/neon/cache/public-project-overview-v1.json';
        if ($cacheFile && is_readable($cacheFile) && filemtime($cacheFile) > time() - 900) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached) && isset($cached['projects'], $cached['specimens'], $cached['institutions'], $cached['sampleTypes'])) return $cached;
        }
        $from = $this->baseFrom() . ' WHERE ' . $this->baseWhere();
        $projects = $this->rows('SELECT od.datasetid, r.institution' . $from);
        $institutions = array();
        foreach ($projects as $project) {
            $name = strtolower(trim(preg_replace('/\s+/', ' ', $project['institution'] ?? '')));
            if ($name !== '') $institutions[$name] = true;
        }
        $records = ' FROM omoccurdatasetlink dl INNER JOIN omoccurrences o ON o.occid = dl.occid
            INNER JOIN omcollections c ON c.collid = o.collid
            WHERE EXISTS (SELECT 1 FROM omoccurdatasets od WHERE od.datasetid = dl.datasetid AND '
            . $this->baseWhere() . ' AND EXISTS (SELECT 1 FROM neonrequest nr WHERE nr.datasetID = od.datasetid AND nr.activeDate IS NOT NULL))';
        $specimens = 0;
        $types = array();
        foreach ($this->rows('SELECT c.collid, c.publicName, COUNT(DISTINCT o.occid) AS total' . $records
            . ' GROUP BY c.collid, c.publicName') as $row) {
            $specimens += (int)$row['total'];
            $name = strtolower(trim(preg_replace('/\s+/', ' ', $row['publicName'] ?? '')));
            if ($name !== '') $types[$name] = true;
        }
        $overview = array('projects' => count($projects), 'specimens' => $specimens,
            'institutions' => count($institutions), 'sampleTypes' => count($types));
        if ($cacheFile && is_dir(dirname($cacheFile)) && is_writable(dirname($cacheFile))) {
            $temp = tempnam(dirname($cacheFile), 'project-overview-');
            if ($temp !== false) {
                if (@file_put_contents($temp, json_encode($overview)) !== false) @rename($temp, $cacheFile);
                if (is_file($temp)) @unlink($temp);
            }
        }
        return $overview;
    }
}
