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
$columns = array_merge($columns, array('taxon', 'years'));
foreach ($rows as &$row) { $row['taxon'] = 'Old taxon'; $row['years'] = '2018-2024'; }
unset($row);
$catalog = array('version' => 1, 'items' => array(
    array('id' => 8, 'collectionCode' => 'NEON-AQ-DNA', 'taxa' => array('Invertebrates', 'Aquatic Macroinvertebrates'),
        'preservation' => array('Genomic Samples'), 'siteCodes' => array(
            array('siteCode' => 'ABBY', 'availableMonths' => array('2024-08', '2018-06')),
            array('siteCode' => 'BART', 'availableMonths' => array('2018-06')),
            array('siteCode' => 'ABBY', 'availableMonths' => array()),
        )),
    array('id' => 9, 'collectionCode' => 'NEON-PLANT', 'taxa' => array('Plants'),
        'preservation' => array('Frozen', 'Dried'), 'siteCodes' => array()),
));
$summary = SampleTypeSummaryExport::read(new SummaryResult($rows, $columns), $catalog);
$expectedColumns = array('sample_type', 'description', 'storage_conditions', 'storage_container', 'data_product_ids',
    'taxonomic_groups', 'collection_months', 'collection_code', 'collection_id', 'preservation', 'sites');
if ($summary['columns'] !== $expectedColumns) throw new Exception('Wrong enriched summary columns');
$first = $summary['rows'][0];
if ($first['collection_id'] !== 8 || $first['collection_code'] !== 'NEON-AQ-DNA') throw new Exception('Wrong collection identity');
if ($first['taxonomic_groups'] !== 'Invertebrates; Aquatic Macroinvertebrates') throw new Exception('Taxon was not replaced with catalog taxonomy');
if ($first['collection_months'] !== '2018-06; 2024-08') throw new Exception('Months must be unique, sorted and replace years');
if ($first['sites'] !== 'ABBY; BART' || $first['preservation'] !== 'Genomic Samples') throw new Exception('Wrong catalog facets');
if ($summary['rows'][1]['preservation'] !== 'Frozen; Dried') throw new Exception('Multiple preservation values lost');
if ($summary['rows'][2]['collection_id'] !== 10 || $summary['rows'][2]['sites'] !== '') throw new Exception('Unmapped summary rows must be retained');
if (count($summary['rows']) !== 3 || array_key_exists('collid', $summary['rows'][0])) throw new Exception('Must export all summary rows without first column');
if ($summary['rows'][0]['description'] !== $rows[0]['description']) throw new Exception('Do not truncate descriptions in downloads');
$stream = fopen('php://temp', 'w+');
fwrite($stream, SampleTypeSummaryExport::csv($summary));
rewind($stream);
if (fgetcsv($stream, 0, ',', '"', '') !== $expectedColumns) throw new Exception('Wrong CSV headers');
foreach ($summary['rows'] as $row) {
    $expected = array_map(function ($value) { return (string)($value ?? ''); }, array_values($row));
    if (fgetcsv($stream, 0, ',', '"', '') !== $expected) throw new Exception('CSV values changed');
}
fclose($stream);
$empty = SampleTypeSummaryExport::read(new SummaryResult(array(), $columns), $catalog);
if (trim(SampleTypeSummaryExport::csv($empty)) !== implode(',', $expectedColumns)) throw new Exception('Empty exports must retain headers');
if (in_array('--pdf-base64', $argv ?? array(), true)) {
    $pdf = SampleTypeSummaryExport::pdf($summary);
    if (substr($pdf, 0, 5) !== '%PDF-' || strpos($pdf, '%%EOF') === false) throw new Exception('Invalid PDF');
    echo base64_encode($pdf);
} else {
    echo "Sample summary export contract passed\n";
}
