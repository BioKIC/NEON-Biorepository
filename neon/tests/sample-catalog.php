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
    public function query($sql) {
        if (strpos($sql, 'FROM omcollections') !== false) {
            if (strpos($sql, "COALESCE(securityKey, '') = ''") === false) throw new Exception('Missing public collection restriction');
            return new Rows(array(array('collid' => 1, 'name' => 'Ear Punches', 'fullDescription' => '<p>Frozen &amp; preserved</p>')));
        }
        if (strpos($sql, 'SELECT DISTINCT o.collid') !== false) {
            if (strpos($sql, 'COALESCE(o.localitySecurity, 0) = 0') === false) throw new Exception('Missing locality restriction');
            return new Rows(array(
                array('collid' => 1, 'siteCode' => 'ABBY', 'month' => '2023-02'),
                array('collid' => 1, 'siteCode' => 'ABBY', 'month' => '2023-01'),
                array('collid' => 1, 'siteCode' => 'ABBY', 'month' => '2023-01'),
                array('collid' => 1, 'siteCode' => 'BART', 'month' => null),
            ));
        }
        if (strpos($sql, 'COUNT(*)') !== false) return new Rows(array(array('collid' => 1, 'count' => 3)));
        if (strpos($sql, 'SHOW TABLES') !== false) return new Rows(array());
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
                array('name' => 'Private collection', 'collid' => 2),
            )),
        )));
        file_put_contents($SERVER_ROOT . '/neon-react/biorepo_lib/collections-' . $tree . '.json', json_encode($nodes));
    }
    require dirname(__DIR__) . '/classes/SampleCatalog.php';
    $result = (new SampleCatalog())->build();
    if (count($result['items']) !== 1) throw new Exception('Private or duplicate collections leaked');
    $item = $result['items'][0];
    if ($item['taxa'] !== array('taxonomic', 'Small Mammals')) throw new Exception('Taxonomic hierarchy lost');
    if ($item['description'] !== 'Frozen & preserved') throw new Exception('Description normalization failed');
    if ($item['siteCodes'][0]['availableMonths'] !== array('2023-01', '2023-02')) throw new Exception('Months must be sorted and unique');
    if ($item['siteCodes'][1]['availableMonths'] !== array()) throw new Exception('Undated sites must remain represented');
    if ($item['totalSamples'] !== 3) throw new Exception('Totals must not multiply site associations');
    if ($item['storageConditions'] !== '') throw new Exception('Missing optional summary must be supported');
    echo "Sample catalog contract passed\n";
} finally {
    foreach (glob($SERVER_ROOT . '/neon-react/biorepo_lib/*') as $path) unlink($path);
    unlink($SERVER_ROOT . '/classes/Manager.php');
    rmdir($SERVER_ROOT . '/neon-react/biorepo_lib');
    rmdir($SERVER_ROOT . '/neon-react');
    rmdir($SERVER_ROOT . '/classes');
    rmdir($SERVER_ROOT);
}
