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

    /** Resolve exact names once before looking for their descendants. */
    public static function rootIds($connection, array $terms): array {
        $sql = 'SELECT tid FROM taxa WHERE sciname IN (' . implode(',', array_fill(0, count($terms), '?')) . ')';
        $statement = $connection->prepare($sql);
        if (!$statement) throw new RuntimeException('Unable to prepare taxon lookup');
        try {
            $statement->bind_param(str_repeat('s', count($terms)), ...$terms);
            if (!$statement->execute()) throw new RuntimeException('Unable to resolve taxon names');
            $statement->bind_result($id);
            $ids = array();
            while ($statement->fetch()) $ids[] = (int)$id;
            return array_values(array_unique($ids));
        } finally {
            $statement->close();
        }
    }

    /** Separate indexed name and taxon-ID lookups; UNION removes overlapping matches. */
    public static function occurrenceQuery(array $collectionIds, array $terms, array $rootIds): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $collectionIds), function ($id) { return $id > 0; })));
        if (!$ids || !$terms) throw new InvalidArgumentException('No collections or names to search.');
        $patterns = array_map(function ($term) {
            // Escape LIKE metacharacters as literals; values are bound separately.
            return strtr($term, array('!' => '!!', '%' => '!%', '_' => '!_')) . '%';
        }, $terms);
        $names = '(' . implode(' OR ', array_fill(0, count($patterns), "o.sciname LIKE ? ESCAPE '!'")) . ')';
        $scope = 'o.collid IN (' . implode(',', $ids) . ') AND (o.recordSecurity IS NULL OR o.recordSecurity <> 5)';
        $queries = array('SELECT o.occid FROM omoccurrences o WHERE ' . $scope . ' AND ' . $names);
        $rootIds = array_values(array_unique(array_filter(array_map('intval', $rootIds), function ($id) { return $id > 0; })));
        // Partial names need only the prefix lookup. Descendants stay in SQL, never in the browser.
        if ($rootIds) {
            $roots = implode(',', $rootIds);
            $taxonIds = 'SELECT tid FROM taxa WHERE tid IN (' . $roots . ')
                UNION SELECT e.tid FROM taxaenumtree e WHERE e.taxauthid = 1 AND e.parenttid IN (' . $roots . ')
                UNION SELECT ts.tid FROM taxaenumtree e
                    INNER JOIN taxstatus ts ON ts.tidaccepted = e.tid AND ts.taxauthid = 1
                    WHERE e.taxauthid = 1 AND e.parenttid IN (' . $roots . ')';
            $queries[] = 'SELECT o.occid FROM (' . $taxonIds . ') relevant
                INNER JOIN omoccurrences o ON o.tidinterpreted = relevant.tid WHERE ' . $scope;
            // Keep matching recorded names even when their interpreted ID is missing.
            $queries[] = 'SELECT o.occid FROM (' . $taxonIds . ') relevant
                INNER JOIN taxa named ON named.tid = relevant.tid
                INNER JOIN omoccurrences o ON o.sciname = named.sciname WHERE ' . $scope;
        }
        return array(implode(' UNION ', $queries), $patterns);
    }

    public static function query(array $collectionIds, array $terms, array $rootIds): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $collectionIds), function ($id) { return $id > 0; })));
        if (!$ids || !$terms) throw new InvalidArgumentException('No collections or names to search.');
        $ordinaryIds = array_diff($ids, array_keys(NeonMaterialSampleCollections::TYPES));
        $sourceIds = $ordinaryIds;
        $mapping = array();
        $sampleTypes = array();
        foreach ($ordinaryIds as $id) {
            $mapping[] = 'SELECT ' . $id . ' AS collid, ' . $id . ' AS sourceCollid, NULL AS sampleType';
        }
        // Map virtual tissues onto their source collections so the occurrence lookup
        // runs once for all sample types instead of repeating for every tissue type.
        foreach (NeonMaterialSampleCollections::TYPES as $id => $sampleType) {
            if (!in_array($id, $ids, true)) continue;
            foreach (array(17, 19, 28) as $parentId) {
                $sourceIds[] = $parentId;
                $mapping[] = 'SELECT ' . (int)$id . ' AS collid, ' . $parentId . ' AS sourceCollid, ? AS sampleType';
                $sampleTypes[] = $sampleType;
            }
        }
        list($matching, $params) = self::occurrenceQuery($sourceIds, $terms, $rootIds);
        // Keep matches without a site; only NEON Site associations contribute coverage.
        $sql = "SELECT DISTINCT kinds.collid, d.name AS siteCode, DATE_FORMAT(o.eventdate, '%Y-%m') AS month
            FROM (" . $matching . ') matching INNER JOIN omoccurrences o ON o.occid = matching.occid
            INNER JOIN (' . implode(' UNION ALL ', $mapping) . ") kinds ON kinds.sourceCollid = o.collid
            LEFT JOIN omoccurdatasetlink l ON l.occid = o.occid
            LEFT JOIN omoccurdatasets d ON d.datasetid = l.datasetid AND d.notes = 'NEON Site'
            WHERE kinds.sampleType IS NULL OR EXISTS (SELECT 1 FROM ommaterialsample ms
                WHERE ms.occid = o.occid AND ms.sampleType LIKE kinds.sampleType)";
        return array($sql, array_merge($params, $sampleTypes));
    }

    public static function find($connection, array $collectionIds, array $terms): array {
        $rootIds = self::rootIds($connection, $terms);
        list($sql, $params) = self::query($collectionIds, $terms, $rootIds);
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
