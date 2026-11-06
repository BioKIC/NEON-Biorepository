<?php
require_once dirname(__DIR__) . '/classes/SampleTypeSummaryExport.php';

class SummaryResult {
    private $rows;
    private $columns;
    public function __construct($rows, $columns) { $this->rows = $rows; $this->columns = $columns; }
    public function fetch_fields() {
        return array_map(function ($name) { return (object)array('name' => $name); }, $this->columns);
    }
    public function fetch_assoc() { return array_shift($this->rows); }
}
$columns = array('collid', 'sample_type', 'description', 'storage_conditions', 'storage_container', 'data_product_ids');
$rows = array(
    array('collid' => 8, 'sample_type' => 'Aquatic DNA extracts', 'description' => "DNA extracts, including \"archived\" samples.\nSampling details remain included.", 'storage_conditions' => '-80 °C', 'storage_container' => '96-well plates', 'data_product_ids' => 'DP1.20120.001'),
    array('collid' => 9, 'sample_type' => 'Plant tissue – café', 'description' => str_repeat('A long description with complete metadata and preserved Unicode: α β µ. ', 45), 'storage_conditions' => null, 'storage_container' => 'Vials & bags <sealed>', 'data_product_ids' => ''),
    array('collid' => 10, 'sample_type' => 'Soil', 'description' => 'Final record', 'storage_conditions' => 'Ambient', 'storage_container' => 'Bags', 'data_product_ids' => ''),
);
$summary = SampleTypeSummaryExport::read(new SummaryResult($rows, $columns));
if ($summary['columns'] !== array_slice($columns, 1)) throw new Exception('Export columns must match PR #1223');
if (count($summary['rows']) !== 3 || array_key_exists('collid', $summary['rows'][0])) throw new Exception('Must export all summary rows without first column');
if ($summary['rows'][0]['description'] !== $rows[0]['description']) throw new Exception('Do not truncate descriptions in downloads');
$stream = fopen('php://temp', 'w+');
fwrite($stream, SampleTypeSummaryExport::csv($summary));
rewind($stream);
if (fgetcsv($stream, 0, ',', '"', '') !== array_slice($columns, 1)) throw new Exception('Wrong CSV headers');
foreach ($rows as $row) {
    $expected = array_map(function ($value) { return (string)($value ?? ''); }, array_values(array_slice($row, 1)));
    if (fgetcsv($stream, 0, ',', '"', '') !== $expected) throw new Exception('CSV values changed');
}
fclose($stream);
$empty = SampleTypeSummaryExport::read(new SummaryResult(array(), $columns));
if (trim(SampleTypeSummaryExport::csv($empty)) !== implode(',', array_slice($columns, 1))) throw new Exception('Empty exports must retain headers');
if (in_array('--pdf-base64', $argv ?? array(), true)) {
    $pdf = SampleTypeSummaryExport::pdf($summary);
    if (substr($pdf, 0, 5) !== '%PDF-' || strpos($pdf, '%%EOF') === false) throw new Exception('Invalid PDF');
    echo base64_encode($pdf);
} else {
    echo "Sample summary export contract passed\n";
}
