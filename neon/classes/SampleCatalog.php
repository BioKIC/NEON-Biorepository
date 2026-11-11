<?php
require_once(__DIR__ . '/../../classes/NeonMaterialSampleCollections.php');
include_once($SERVER_ROOT . '/classes/Manager.php');

/** Builds a public catalog snapshot; never exports specimen IDs or sensitive localities. */
class SampleCatalog extends Manager {
    public function __construct() { parent::__construct(null, 'readonly'); }

    public function build(): array {
        $catalog = array();
        foreach (array('taxonomic' => 'taxa', 'protocol' => 'protocols', 'sampletype' => 'preservation') as $tree => $field) {
            $nodes = json_decode(file_get_contents($GLOBALS['SERVER_ROOT'] . '/neon-react/biorepo_lib/collections-' . $tree . '.json'), true, 512, JSON_THROW_ON_ERROR);
            $walk = function ($nodes, $ancestors = array()) use (&$walk, &$catalog, $field) {
                foreach ($nodes as $node) {
                    $names = array_merge($ancestors, array($node['name']));
                    if (!empty($node['collid'])) {
                        $id = (int)$node['collid'];
                        if (!isset($catalog[$id])) $catalog[$id] = array('id' => $id, 'name' => $node['name'], 'sampleTypeName' => $node['name'], 'description' => '',
                            'taxa' => array(), 'protocols' => array(), 'preservation' => array(),
                            'storageConditions' => '', 'storageContainer' => '', 'dataProducts' => '', 'siteCodes' => array(), 'totalSamples' => 0);
                        $values = $field === 'taxa' ? array_slice($ancestors, 0, 2) : array_slice($names, 0, 1);
                        $catalog[$id][$field] = array_values(array_unique(array_merge($catalog[$id][$field], $values)));
                    }
                    if (!empty($node['children'])) $walk($node['children'], $names);
                }
            };
            $walk($nodes);
        }
        $facetPath = $GLOBALS['SERVER_ROOT'] . '/neon-react/biorepo_lib/external-collection-facets.json';
        $externalFacets = is_file($facetPath) ? json_decode(file_get_contents($facetPath), true, 512, JSON_THROW_ON_ERROR) : array();
        // Match the outside-repository collections offered by the sample search form.
        $external = $this->conn->query("SELECT DISTINCT c.collid, c.collectionName
            FROM omcollections c
            INNER JOIN omcollcatlink l ON c.collid = l.collid
            INNER JOIN omcollcategories cat ON l.ccpk = cat.ccpk
            WHERE cat.category = 'Additional NEON Collections'");
        if (!$external) throw new RuntimeException('Unable to read outside repository collections');
        while ($row = $external->fetch_assoc()) {
            $id = (int)$row['collid'];
            if (!isset($catalog[$id])) $catalog[$id] = array(
                'id' => $id, 'name' => $row['collectionName'], 'sampleTypeName' => $row['collectionName'],
                'description' => '', 'taxa' => array(), 'protocols' => array(), 'preservation' => array(),
                'storageConditions' => '', 'storageContainer' => '', 'dataProducts' => '',
                'siteCodes' => array(), 'totalSamples' => 0
            );
            $catalog[$id]['outsideRepository'] = true;
            foreach (array('taxa', 'protocols', 'preservation') as $field) {
                $catalog[$id][$field] = array_values(array_unique(array_merge(
                    $catalog[$id][$field], $externalFacets[$id][$field] ?? array()
                )));
            }

        }
        if (!$catalog) throw new RuntimeException('No sample types configured');
        // The public browse trees select catalog collections; securityKey is an integration
        // credential generated for ordinary collections, not a visibility flag.
        // IDs originate in configured trees or collection metadata and are cast to integers.
        $ids = implode(',', array_keys($catalog));
        $result = $this->conn->query("SELECT collid, COALESCE(NULLIF(publicName, ''), collectionName) AS name,
            collectionName, collectionCode, fullDescription FROM omcollections WHERE collid IN ($ids)");
        if (!$result) throw new RuntimeException('Unable to read collection metadata');
        $public = array();
        while ($row = $result->fetch_assoc()) {
            $id = (int)$row['collid'];
            $public[$id] = $catalog[$id];
            $public[$id]['name'] = $row['name'];
            $public[$id]['collectionName'] = trim($row['collectionName'] ?? '');
            $public[$id]['collectionCode'] = trim($row['collectionCode'] ?? '');
            $html = $row['fullDescription'] ?? '';
            $intro = preg_split('~<h3\b[^>]*>~i', $html, 2)[0];
            $public[$id]['shortDescriptionHtml'] = $intro;
            $intro = preg_replace('~</p\s*>|<br\s*/?>~i', ' ', $intro);
            $public[$id]['shortDescription'] = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($intro), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            $public[$id]['description'] = trim(html_entity_decode(strip_tags($row['fullDescription'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
        $catalog = $public;
        if (!$catalog) throw new RuntimeException('No configured sample type IDs match omcollections; check that the local database contains the collections referenced in collections-*.json');
        $ids = implode(',', array_keys($catalog));
        // Same NEON Site association as OccurrenceListFunctions::getNeonAvailabilitySiteCodes.
        $result = $this->conn->query("SELECT DISTINCT o.collid, d.name AS siteCode,
            DATE_FORMAT(o.eventdate, '%Y-%m') AS month FROM omoccurrences o
            INNER JOIN omoccurdatasetlink l ON l.occid = o.occid
            INNER JOIN omoccurdatasets d ON d.datasetid = l.datasetid
            WHERE o.collid IN ($ids) AND d.notes = 'NEON Site'");
        if (!$result) throw new RuntimeException('Unable to aggregate sample availability');
        $sites = array();
        while ($row = $result->fetch_assoc()) {
            $id = (int)$row['collid'];
            $code = $row['siteCode'];
            if (!$code) continue;
            if (!isset($sites[$id][$code])) $sites[$id][$code] = array('siteCode' => $code, 'availableMonths' => array());
            if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $row['month'] ?? '')) $sites[$id][$code]['availableMonths'][] = $row['month'];
        }
        // Separate count avoids multiplying specimens associated with multiple sites.
        $result = $this->conn->query("SELECT collid, COUNT(*) AS count FROM omoccurrences
            WHERE collid IN ($ids) GROUP BY collid");
        if (!$result) throw new RuntimeException('Unable to count samples');
        while ($row = $result->fetch_assoc()) $catalog[(int)$row['collid']]['totalSamples'] = (int)$row['count'];
        // Tissue pseudo-collections have no occurrences of their own. Count matching parent
        // records with EXISTS so multiple tissue rows never multiply a record or its coverage.
        foreach (NeonMaterialSampleCollections::TYPES as $id => $sampleType) {
            if (!isset($catalog[$id])) continue;
            $where = "o.collid IN(17,19,28)
                AND EXISTS (SELECT 1 FROM ommaterialsample ms WHERE ms.occid = o.occid
                    AND ms.sampleType LIKE '" . $this->conn->real_escape_string($sampleType) . "')";
            $result = $this->conn->query("SELECT DISTINCT $id AS collid, d.name AS siteCode,
                DATE_FORMAT(o.eventdate, '%Y-%m') AS month FROM omoccurrences o
                INNER JOIN omoccurdatasetlink l ON l.occid = o.occid
                INNER JOIN omoccurdatasets d ON d.datasetid = l.datasetid
                WHERE $where AND d.notes = 'NEON Site'");
            if (!$result) throw new RuntimeException('Unable to aggregate tissue sample availability');
            $sites[$id] = array();
            while ($row = $result->fetch_assoc()) {
                $code = $row['siteCode'];
                if (!$code) continue;
                if (!isset($sites[$id][$code])) $sites[$id][$code] = array('siteCode' => $code, 'availableMonths' => array());
                if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $row['month'] ?? '')) $sites[$id][$code]['availableMonths'][] = $row['month'];
            }
            $result = $this->conn->query("SELECT COUNT(*) AS count FROM omoccurrences o WHERE $where");
            if (!$result) throw new RuntimeException('Unable to count tissue sample records');
            $row = $result->fetch_assoc();
            $catalog[$id]['totalSamples'] = (int)$row['count'];
        }
        foreach ($catalog as $id => &$item) {
            $item['siteCodes'] = array_values($sites[$id] ?? array());
            foreach ($item['siteCodes'] as &$site) {
                $site['availableMonths'] = array_values(array_unique($site['availableMonths']));
                sort($site['availableMonths']);
            }
            unset($site);
        }
        unset($item);
        // Optional enrichment from #1223; no schema dependency for the catalog itself.
        $exists = $this->conn->query("SHOW TABLES LIKE 'neonsamplesummary'");
        if ($exists && $exists->num_rows) {
            $result = $this->conn->query('SELECT collid, storage_conditions, storage_container, data_product_ids, bulk_sample_identifications FROM neonsamplesummary');
            if (!$result) throw new RuntimeException('Unable to read sample summary metadata');
            $summaryRows = array();
            while ($row = $result->fetch_assoc()) {
                $summaryRows[] = $row;
                $id = (int)$row['collid'];
                if (!isset($catalog[$id])) continue;
                $catalog[$id]['storageConditions'] = $row['storage_conditions'] ?? '';
                $catalog[$id]['storageContainer'] = $row['storage_container'] ?? '';
                $catalog[$id]['dataProducts'] = $row['data_product_ids'] ?? '';
            }
            // Identification collections inherit storage metadata through the summary's parent link.
            foreach ($summaryRows as $row) {
                $url = html_entity_decode($row['bulk_sample_identifications'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $params);
                $id = filter_var($params['collid'] ?? null, FILTER_VALIDATE_INT);
                if (!$id || !isset($catalog[$id])) continue;
                foreach (array('storageConditions' => 'storage_conditions', 'storageContainer' => 'storage_container') as $field => $column) {
                    if (trim($catalog[$id][$field]) === '') {
                        $catalog[$id][$field] = $row[$column] ?? '';
                    }
                }
            }
        }
        usort($catalog, function ($a, $b) { return strnatcasecmp($a['name'], $b['name']); });
        return array('version' => 1, 'generatedAt' => gmdate('c'), 'items' => array_values($catalog));
    }
}
