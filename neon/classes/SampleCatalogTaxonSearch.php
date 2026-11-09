<?php
#neon edit
require_once __DIR__ . '/../../classes/NeonMaterialSampleCollections.php';

/** Read-only scientific-name lookup. Returns catalog IDs and aggregate site/month coverage. */
class SampleCatalogTaxonSearch {
    public static function terms($input): array {
        if (!is_array($input) || !$input || count($input) > 8) {
            throw new InvalidArgumentException('Use between one and eight scientific-name terms.');
        }
        $terms = array();
        foreach ($input as $term) {
            if (!is_string($term) || strlen($term) > 800) {
                throw new InvalidArgumentException('Invalid scientific-name term.');
            }
            $term = preg_replace('/\s+/u', ' ', trim($term));
            if ($term === null || !preg_match('/^.{3,200}$/us', $term)) {
                throw new InvalidArgumentException('Scientific-name terms must contain 3 to 200 characters.');
            }
            $terms[] = $term;
        }
        return array_values(array_unique($terms));
    }

    /** Match name prefixes and descendants of named taxa in the portal's default authority. */
    public static function nameWhere(array $terms): array {
        $patterns = array_map(function ($term) {
            // Escape LIKE metacharacters as literals; values are bound separately.
            return strtr($term, array('!' => '!!', '%' => '!%', '_' => '!_')) . '%';
        }, $terms);
        $names = '(' . implode(' OR ', array_fill(0, count($patterns), "o.sciname LIKE ? ESCAPE '!'")) . ')';
        // Resolve a higher taxon's full name, as the browse/search pages do.
        $taxa = '(' . implode(' OR ', array_fill(0, count($terms), 't.sciname = ?')) . ')';
        // Descendants stay in SQL; no list of taxa or specimen IDs is sent to the browser.
        $descendants = 'SELECT e.tid FROM taxa t INNER JOIN taxaenumtree e ON e.parenttid = t.tid
            WHERE e.taxauthid = 1 AND ' . $taxa;
        $taxonIds = 'SELECT t.tid FROM taxa t WHERE ' . $taxa . '
            UNION ' . $descendants . '
            UNION SELECT ts.tid FROM taxstatus ts WHERE ts.taxauthid = 1
                AND ts.tidaccepted IN (' . $descendants . ')';
        // Also match recorded names whose interpreted taxon ID has not been populated.
        $names = '(' . $names . ' OR o.tidinterpreted IN (' . $taxonIds . ')
            OR o.sciname IN (SELECT named.sciname FROM taxa named WHERE named.tid IN (' . $taxonIds . ')))';
        return array($names, array_merge($patterns, $terms, $terms, $terms, $terms, $terms, $terms));
    }

    public static function query(array $collectionIds, array $terms): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $collectionIds), function ($id) { return $id > 0; })));
        if (!$ids || !$terms) throw new InvalidArgumentException('No collections or names to search.');
        list($names, $patterns) = self::nameWhere($terms);
        $visible = '(o.recordSecurity IS NULL OR o.recordSecurity <> 5)';
        // Keep matches without a site so they remain discoverable, but restrict
        // availability to the same NEON Site associations as the catalog.
        $siteJoins = " LEFT JOIN omoccurdatasetlink l ON l.occid = o.occid
            LEFT JOIN omoccurdatasets d ON d.datasetid = l.datasetid AND d.notes = 'NEON Site' ";
        $coverage = "d.name AS siteCode, DATE_FORMAT(o.eventdate, '%Y-%m') AS month";
        $queries = array();
        $params = array();
        $ordinaryIds = array_diff($ids, array_keys(NeonMaterialSampleCollections::TYPES));
        if ($ordinaryIds) {
            $queries[] = 'SELECT DISTINCT o.collid, ' . $coverage . ' FROM omoccurrences o' . $siteJoins . ' WHERE ' . $names
                . ' AND o.collid IN (' . implode(',', $ordinaryIds) . ') AND ' . $visible;
            $params = $patterns;
        }
        // Virtual tissues have no occurrences of their own. Match the same parent
        // records and material-sample patterns as SampleCatalog::build(). EXISTS
        // prevents multiple tissues from multiplying a parent's site coverage.
        foreach (NeonMaterialSampleCollections::TYPES as $id => $sampleType) {
            if (!in_array($id, $ids, true)) continue;
            $queries[] = 'SELECT DISTINCT ' . (int)$id . ' AS collid, ' . $coverage
                . ' FROM omoccurrences o' . $siteJoins . ' WHERE ' . $names
                . ' AND o.collid IN (17,19,28) AND ' . $visible . '
                AND EXISTS (SELECT 1 FROM ommaterialsample ms
                    WHERE ms.occid = o.occid AND ms.sampleType LIKE ?)';
            $params = array_merge($params, $patterns, array($sampleType));
        }
        return array(implode(' UNION ALL ', $queries), $params);
    }

    public static function find($connection, array $collectionIds, array $terms): array {
        list($sql, $params) = self::query($collectionIds, $terms);
        $statement = $connection->prepare($sql);
        if (!$statement) throw new RuntimeException('Unable to prepare scientific-name lookup');
        try {
            $statement->bind_param(str_repeat('s', count($params)), ...$params);
            if (!$statement->execute()) throw new RuntimeException('Unable to search scientific names');
            $statement->bind_result($id, $siteCode, $month);
            $matches = array();
            while ($statement->fetch()) {
                $id = (int)$id;
                if (!isset($matches[$id])) $matches[$id] = array();
                if (!$siteCode) continue;
                if (!isset($matches[$id][$siteCode])) $matches[$id][$siteCode] = array();
                if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month ?? '')) {
                    $matches[$id][$siteCode][$month] = true;
                }
            }
            ksort($matches, SORT_NUMERIC);
            $siteCodesByCollection = array();
            foreach ($matches as $collectionId => $sites) {
                ksort($sites, SORT_STRING);
                $siteCodesByCollection[$collectionId] = array();
                foreach ($sites as $code => $months) {
                    $months = array_keys($months);
                    sort($months, SORT_STRING);
                    $siteCodesByCollection[$collectionId][] = array('siteCode' => $code, 'availableMonths' => $months);
                }
            }
            return array(
                'collectionIds' => array_keys($matches),
                'siteCodesByCollection' => (object)$siteCodesByCollection
            );
        } finally {
            $statement->close();
        }
    }
}
#end neon edit
