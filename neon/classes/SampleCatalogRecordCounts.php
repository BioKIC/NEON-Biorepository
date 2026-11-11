<?php
#neon edit
require_once __DIR__ . '/SampleCatalogTaxonSearch.php';

/** Public, filtered record totals for visible sample catalog cards. */
class SampleCatalogRecordCounts {
    private static function date($value): string {
        if ($value === '') return '';
        if (!is_string($value)) throw new InvalidArgumentException('Invalid collection date.');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException('Invalid collection date.');
        return $value;
    }

    public static function query($queries, array $allowedIds): array {
        if (!is_array($queries) || !$queries || count($queries) > 100) {
            throw new InvalidArgumentException('Use between one and 100 sample type queries.');
        }
        $allowedIds = array_map('intval', $allowedIds);
        $seen = array();
        $sql = array();
        $params = array();
        foreach ($queries as $query) {
            if (!is_array($query) || array_diff(array_keys($query), array('db', 'datasetid', 'eventdate1', 'eventdate2', 'taxa'))) {
                throw new InvalidArgumentException('Invalid record query.');
            }
            $id = filter_var($query['db'] ?? null, FILTER_VALIDATE_INT);
            if (!$id || !in_array($id, $allowedIds, true) || isset($seen[$id])) {
                throw new InvalidArgumentException('Invalid sample type.');
            }
            $seen[$id] = true;
            $where = array('(o.recordSecurity IS NULL OR o.recordSecurity <> 5)');
            $values = array();
            if (isset(NeonMaterialSampleCollections::TYPES[$id])) {
                $where[] = 'o.collid IN (17,19,28) AND EXISTS (SELECT 1 FROM ommaterialsample ms
                    WHERE ms.occid = o.occid AND ms.sampleType LIKE ?)';
                $values[] = NeonMaterialSampleCollections::TYPES[$id];
            } else {
                $where[] = 'o.collid = ?';
                $values[] = (string)$id;
            }
            $filtered = false;
            if (isset($query['datasetid'])) {
                $sites = $query['datasetid'];
                if (!is_string($sites) || strlen($sites) > 1100 || !preg_match('/^[1-9][0-9]*(,[1-9][0-9]*)*$/', $sites)) {
                    throw new InvalidArgumentException('Invalid site selection.');
                }
                $sites = array_values(array_unique(explode(',', $sites)));
                if (count($sites) > 100) throw new InvalidArgumentException('Too many sites.');
                $where[] = "EXISTS (SELECT 1 FROM omoccurdatasetlink l
                    INNER JOIN omoccurdatasets d ON d.datasetid = l.datasetid
                    WHERE l.occid = o.occid AND d.notes = 'NEON Site'
                    AND d.datasetid IN (" . implode(',', array_fill(0, count($sites), '?')) . '))';
                $values = array_merge($values, $sites);
                $filtered = true;
            }
            $from = self::date($query['eventdate1'] ?? '');
            $to = self::date($query['eventdate2'] ?? '');
            if ($from && $to && $from > $to) throw new InvalidArgumentException('Invalid date range.');
            if ($from) { $where[] = 'o.eventdate >= ?'; $values[] = $from; $filtered = true; }
            if ($to) {
                $where[] = 'o.eventdate < ?';
                $values[] = (new DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d');
                $filtered = true;
            }
            if (isset($query['taxa'])) {
                if (!is_string($query['taxa'])) throw new InvalidArgumentException('Invalid scientific names.');
                $terms = SampleCatalogTaxonSearch::terms(explode(',', $query['taxa']));
                list($names, $patterns) = SampleCatalogTaxonSearch::nameWhere($terms);
                $where[] = $names;
                $values = array_merge($values, $patterns);
                $filtered = true;
            }
            if (!$filtered) throw new InvalidArgumentException('A record filter is required.');
            // EXISTS keeps multiple site links or tissue rows from multiplying a record.
            $sql[] = 'SELECT ' . $id . ' AS collid, COUNT(*) AS count FROM omoccurrences o WHERE ' . implode(' AND ', $where);
            $params = array_merge($params, $values);
        }
        return array(implode(' UNION ALL ', $sql), $params);
    }

    public static function find($connection, $queries, array $allowedIds): array {
        list($sql, $params) = self::query($queries, $allowedIds);
        $statement = $connection->prepare($sql);
        if (!$statement) throw new RuntimeException('Unable to prepare record counts');
        try {
            $statement->bind_param(str_repeat('s', count($params)), ...$params);
            if (!$statement->execute()) throw new RuntimeException('Unable to count records');
            $statement->bind_result($id, $count);
            $counts = array();
            while ($statement->fetch()) $counts[(int)$id] = (int)$count;
            return $counts;
        } finally {
            $statement->close();
        }
    }
}
#end neon edit
