<?php
// Standalone aggregation contract test; no database credentials required.
$SERVER_ROOT = sys_get_temp_dir() . '/catalog-test-' . uniqid();
mkdir($SERVER_ROOT . '/classes', 0700, true);
mkdir($SERVER_ROOT . '/neon-react/biorepo_lib', 0700, true);
file_put_contents($SERVER_ROOT . '/classes/Manager.php', '<?php class Manager { protected $conn; public function __construct() { $this->conn = $GLOBALS["connection"]; } }');
class Rows {
    public $num_rows;
    private $rows;
    public function __construct($rows) { $this->rows = $rows; $this->num_rows = count($rows); }
    public function fetch_assoc() { return array_shift($this->rows); }
}
class Connection {
    public function real_escape_string($value) { return addslashes($value); }
    public function query($sql) {
        if (stripos($sql, 'recordSecurity') !== false) throw new Exception('Catalog aggregates must include restricted records');
        if (strpos($sql, 'FROM omcollections') !== false) {
            if (strpos($sql, 'securityKey') !== false) throw new Exception('Integration keys must not exclude catalog collections');
            if (strpos($sql, 'collid IN (1,2,118,119)') === false) throw new Exception('Missing configured collection restriction');
            return new Rows(array(array('collid' => 1, 'name' => 'Ear Punches', 'collectionCode' => 'NEON-MAM-EP', 'fullDescription' => '<p>Frozen &amp; preserved</p>'),
                array('collid' => 118, 'name' => 'Gastrointestinal Tracts'),
                array('collid' => 119, 'name' => 'Hearts')));
        }
        if (strpos($sql, 'FROM ommaterialsample ms') !== false) {
            foreach (array('o.collid IN(17,19,28)',
                'EXISTS (SELECT 1', 'ms.occid = o.occid') as $required) {
                if (strpos($sql, $required) === false) throw new Exception('Missing tissue restriction: ' . $required);
            }
            $heart = strpos($sql, "LIKE '%heart%'") !== false;
            $tract = strpos($sql, "LIKE '%tract%'") !== false;
            if (!$heart && !$tract) throw new Exception('Unexpected tissue type');
            $id = $heart ? 119 : 118;
            if (strpos($sql, 'SELECT DISTINCT') !== false) {
                if (strpos($sql, "$id AS collid") === false) throw new Exception('Heart and tract mappings reversed');
                if (strpos($sql, "d.notes = 'NEON Site'") === false) throw new Exception('Missing site dataset restriction');
                return new Rows(array(
                    array('siteCode' => 'ABBY', 'month' => '2024-02'),
                    array('siteCode' => 'ABBY', 'month' => '2024-01'),
                    array('siteCode' => 'ABBY', 'month' => '2024-01'),
                    array('siteCode' => 'BART', 'month' => null),
                ));
            }
            return new Rows(array(array('count' => $heart ? 7 : 5)));
        }
        if (strpos($sql, 'SELECT DISTINCT o.collid') !== false) {
            return new Rows(array(
                array('collid' => 1, 'siteCode' => 'ABBY', 'month' => '2023-02'),
                array('collid' => 1, 'siteCode' => 'ABBY', 'month' => '2023-01'),
                array('collid' => 1, 'siteCode' => 'ABBY', 'month' => '2023-01'),
                array('collid' => 1, 'siteCode' => 'BART', 'month' => null),
            ));
        }
        if (strpos($sql, 'COUNT(*)') !== false) {
            return new Rows(array(array('collid' => 1, 'count' => 3)));
        }
        if (strpos($sql, 'SHOW TABLES') !== false) return new Rows(isset($GLOBALS['summaryRows']) ? array(array('table' => 'neonsamplesummary')) : array());
        if (strpos($sql, 'FROM neonsamplesummary') !== false) return new Rows($GLOBALS['summaryRows']);
        throw new Exception('Unexpected query');
    }
}
$GLOBALS['connection'] = new Connection();
try {
    foreach (array('taxonomic', 'protocol', 'sampletype') as $tree) {
        $nodes = array(array('name' => $tree, 'children' => array(
            array('id' => 1, 'name' => 'Small Mammals', 'children' => array(
                array('name' => 'Ear Punches', 'collid' => 1),
                array('id' => 1, 'name' => 'Ear Punches', 'collid' => 1),
                array('name' => 'Missing collection', 'collid' => 2),
                array('name' => 'Gastrointestinal Tracts', 'collid' => 118),
                array('name' => 'Hearts', 'collid' => 119),
            )),
        )));
        file_put_contents($SERVER_ROOT . '/neon-react/biorepo_lib/collections-' . $tree . '.json', json_encode($nodes));
    }
    require dirname(__DIR__) . '/classes/SampleCatalog.php';
    $result = (new SampleCatalog())->build();
    if (count($result['items']) !== 3) throw new Exception('Missing or duplicate collections included');
    $item = $result['items'][0];
    if ($item['collectionCode'] !== 'NEON-MAM-EP') throw new Exception('Collection code missing');
    if ($item['taxa'] !== array('taxonomic', 'Small Mammals')) throw new Exception('Taxonomic hierarchy lost');
    if ($item['description'] !== 'Frozen & preserved') throw new Exception('Description normalization failed');
    if ($item['shortDescriptionHtml'] !== '<p>Frozen &amp; preserved</p>') throw new Exception('Description markup was lost');
    if ($item['siteCodes'][0]['availableMonths'] !== array('2023-01', '2023-02')) throw new Exception('Months must be sorted and unique');
    if ($item['siteCodes'][1]['availableMonths'] !== array()) throw new Exception('Undated sites must remain represented');
    if ($item['totalSamples'] !== 3) throw new Exception('Totals must not multiply site associations');
    if ($item['storageConditions'] !== '') throw new Exception('Missing optional summary must be supported');
    $byId = array_column($result['items'], null, 'id');
    foreach (array(118 => 5, 119 => 7) as $id => $count) {
        if ($byId[$id]['totalSamples'] !== $count) throw new Exception('Tissue totals missing or swapped');
        if ($byId[$id]['siteCodes'][0]['availableMonths'] !== array('2024-01', '2024-02')) throw new Exception('Tissue months must be sorted and unique');
        if ($byId[$id]['siteCodes'][1]['availableMonths'] !== array()) throw new Exception('Undated tissue sites lost');
    }
    if (NeonMaterialSampleCollections::TYPES !== array(118 => '%tract%', 119 => '%heart%',
        120 => '%lung%', 121 => '%kidney%', 122 => '%spleen%', 123 => '%liver%', 124 => '%muscle%')) {
        throw new Exception('Incorrect virtual collection mapping');
    }
    $GLOBALS['summaryRows'] = array(
        array('collid' => 118, 'storage_conditions' => 'Frozen', 'storage_container' => 'Vials',
            'bulk_sample_identifications' => 'https://example.org/neoncollprofiles.php?collid=1'),
        array('collid' => 1, 'storage_conditions' => '', 'storage_container' => 'Existing container'),
        array('collid' => 119, 'storage_conditions' => 'Other', 'storage_container' => 'Other',
            'bulk_sample_identifications' => 'not-a-link'),
    );
    $enriched = array_column((new SampleCatalog())->build()['items'], null, 'id');
    if ($enriched[1]['storageConditions'] !== 'Frozen') throw new Exception('Parent storage was not inherited');
    if ($enriched[1]['storageContainer'] !== 'Existing container') throw new Exception('Explicit child metadata must be preserved');
    if ($enriched[118]['storageContainer'] !== 'Vials') throw new Exception('Parent metadata changed');
    echo "Sample catalog contract passed\n";
} finally {
    foreach (glob($SERVER_ROOT . '/neon-react/biorepo_lib/*') as $path) unlink($path);
    unlink($SERVER_ROOT . '/classes/Manager.php');
    rmdir($SERVER_ROOT . '/neon-react/biorepo_lib');
    rmdir($SERVER_ROOT . '/neon-react');
    rmdir($SERVER_ROOT . '/classes');
    rmdir($SERVER_ROOT);
}
