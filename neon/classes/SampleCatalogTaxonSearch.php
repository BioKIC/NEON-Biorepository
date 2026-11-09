<?php
#neon edit
require_once __DIR__ . '/../../classes/NeonMaterialSampleCollections.php';

/** Read-only scientific-name lookup. Returns catalog IDs, never specimen data. */
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

    /** No LOWER(), leading wildcard, or substring scan on the indexed sciname column. */
    public static function query(array $collectionIds, array $terms): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $collectionIds), function ($id) { return $id > 0; })));
        if (!$ids || !$terms) throw new InvalidArgumentException('No collections or names to search.');
        $patterns = array_map(function ($term) {
            // Escape LIKE metacharacters as literals; values are bound separately.
            return strtr($term, array('!' => '!!', '%' => '!%', '_' => '!_')) . '%';
        }, $terms);
        $names = '(' . implode(' OR ', array_fill(0, count($patterns), "o.sciname LIKE ? ESCAPE '!'")) . ')';
        $visible = '(o.recordSecurity IS NULL OR o.recordSecurity <> 5)';
        $queries = array();
        $params = array();
        $ordinaryIds = array_diff($ids, array_keys(NeonMaterialSampleCollections::TYPES));
        if ($ordinaryIds) {
            $queries[] = 'SELECT DISTINCT o.collid FROM omoccurrences o WHERE ' . $names
                . ' AND o.collid IN (' . implode(',', $ordinaryIds) . ') AND ' . $visible;
            $params = $patterns;
        }
        // Virtual tissues have no occurrences of their own. Match the same parent
        // records and material-sample patterns as SampleCatalog::build(), stopping
        // as soon as a qualifying specimen is found for each tissue collection.
        foreach (NeonMaterialSampleCollections::TYPES as $id => $sampleType) {
            if (!in_array($id, $ids, true)) continue;
            $queries[] = 'SELECT ' . (int)$id . ' AS collid WHERE EXISTS (
                SELECT 1 FROM omoccurrences o
                INNER JOIN ommaterialsample ms ON ms.occid = o.occid
                WHERE ' . $names . ' AND o.collid IN (17,19,28) AND ' . $visible . '
                AND ms.sampleType LIKE ?)';
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
            $statement->bind_result($id);
            $matches = array();
            while ($statement->fetch()) $matches[] = (int)$id;
            sort($matches, SORT_NUMERIC);
            return array_values(array_unique($matches));
        } finally {
            $statement->close();
        }
    }
}
#end neon edit
