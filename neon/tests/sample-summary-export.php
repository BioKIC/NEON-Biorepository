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
$columns = array_merge($columns, array('taxon', 'years', 'number_of_sites', 'description_url'));
foreach ($rows as &$row) { $row['taxon'] = 'Old taxon'; $row['years'] = '2018-2024'; $row['number_of_sites'] = 2; $row['description_url'] = 'https://example.org/collections/' . $row['collid']; }
unset($row);
$catalog = array('version' => 1, 'items' => array(
    array('id' => 8, 'description' => $rows[0]['description'], 'collectionCode' => 'NEON-AQ-DNA', 'taxa' => array('Invertebrates', 'Aquatic Macroinvertebrates'),
        'preservation' => array('Genomic Samples'), 'siteCodes' => array(
            array('siteCode' => 'ABBY', 'availableMonths' => array('2024-08', '2018-06')),
            array('siteCode' => 'BART', 'availableMonths' => array('2018-06')),
            array('siteCode' => 'ABBY', 'availableMonths' => array()),
        )),
    array('id' => 9, 'collectionCode' => 'NEON-PLANT', 'taxa' => array('Plants'),
        'preservation' => array('Frozen', 'Dried'), 'siteCodes' => array()),
));
$summary = SampleTypeSummaryExport::read(new SummaryResult($rows, $columns), $catalog);
$expectedColumns = array('sample_type', 'collection_code', 'collection_id', 'preservation',
    'storage_conditions', 'storage_container', 'data_product_ids', 'taxonomic_groups', 'collection_months',
    'sites', 'number_of_sites', 'description', 'description_url');
if ($summary['columns'] !== $expectedColumns) throw new Exception('Wrong enriched summary columns');
$first = $summary['rows'][0];
if ($first['collection_id'] !== 8 || $first['collection_code'] !== 'NEON-AQ-DNA') throw new Exception('Wrong collection identity');
if ($first['taxonomic_groups'] !== 'Invertebrates; Aquatic Macroinvertebrates') throw new Exception('Taxon was not replaced with catalog taxonomy');
if ($first['collection_months'] !== '2018-06; 2024-08') throw new Exception('Months must be unique, sorted and replace years');
if ($first['sites'] !== 'ABBY; BART' || $first['preservation'] !== 'Genomic Samples') throw new Exception('Wrong catalog facets');
if ($summary['rows'][1]['preservation'] !== 'Frozen; Dried') throw new Exception('Multiple preservation values lost');
if ($summary['rows'][2]['collection_id'] !== 10 || $summary['rows'][2]['sites'] !== '') throw new Exception('Unmapped summary rows must be retained');
if (count($summary['rows']) !== 3 || array_key_exists('collid', $summary['rows'][0])) throw new Exception('Must export all summary rows without first column');
if ($summary['rows'][0]['description'] !== 'DNA extracts, including "archived" samples.') throw new Exception('Description must stop before Sampling');
if ($summary['rows'][1]['description'] !== trim($rows[1]['description'])) throw new Exception('Descriptions without Sampling must remain complete');
if ($summary['rows'][2]['description'] !== 'Final record') throw new Exception('Fallback description lost');
$stream = fopen('php://temp', 'w+');
fwrite($stream, SampleTypeSummaryExport::csv($summary));
rewind($stream);
if (fread($stream, 3) !== "\xEF\xBB\xBF") throw new Exception('CSV must declare UTF-8 for Excel');
if (fgetcsv($stream, 0, ',', '"', '') !== $expectedColumns) throw new Exception('Wrong CSV headers');
foreach ($summary['rows'] as $row) {
    $expected = array_map(function ($value) { return (string)($value ?? ''); }, array_values($row));
    if (fgetcsv($stream, 0, ',', '"', '') !== $expected) throw new Exception('CSV values changed');
}
fclose($stream);
$empty = SampleTypeSummaryExport::read(new SummaryResult(array(), $columns), $catalog);
if (trim(substr(SampleTypeSummaryExport::csv($empty), 3)) !== implode(',', $expectedColumns)) throw new Exception('Empty exports must retain headers');
$pdfCatalog = $catalog;
foreach ($pdfCatalog['items'] as &$item) {
    $item['name'] = $item['id'] === 8 ? 'Aquatic DNA extracts' : 'Plant tissue';
    $item['protocols'] = array('INV - Aquatic Macroinvertebrate Sampling');
    $item['storageConditions'] = '-80 °C';
    $item['storageContainer'] = '96-well plates';
    if ($item['id'] === 9) $item['description'] = 'Plant tissues collected for archival storage. Sampling details';
}
unset($item);
$pdfCatalog['items'][] = array('id' => 99, 'name' => 'Soil', 'collectionCode' => 'NEON-SOIL',
    'description' => 'Archived soil specimens. Sampling details not included.');
$summary['rows'][0]['bulk_sample_identifications'] = 'https://example.org/portal/collections/misc/neoncollprofiles.php?collid=99';
$pdfSummary = SampleTypeSummaryExport::forPdf($pdfCatalog, 'https://example.org/portal/', $summary);
if (count($pdfSummary['rows']) !== count($summary['rows'])) {
    throw new Exception('PDF must contain the same rows as CSV and JSON');
}
if ($pdfSummary['columns'] !== array('sample_type', 'collection_code', 'description_url', 'protocol',
    'taxonomic_groups', 'preservation', 'storage_conditions', 'storage_container', 'available_dates', 'bulk_sample_identifications', 'description')) {
    throw new Exception('PDF fields differ from requested list');
}
if ($pdfSummary['rows'][0]['available_dates'] !== '2018-06 through 2024-08') throw new Exception('Wrong PDF available dates');
if ($pdfSummary['rows'][2]['description_url'] !== 'https://example.org/portal/collections/misc/neoncollprofiles.php?collid=10') throw new Exception('Summary rows without cached metadata must remain included');
if ($pdfSummary['rows'][2]['description'] !== 'Final record') throw new Exception('Summary description fallback lost');
if ($pdfSummary['rows'][0]['bulk_sample_identifications'] !== $summary['rows'][0]['bulk_sample_identifications']) throw new Exception('Identification link missing from PDF');
$fullCatalog = array('items' => array());
$physicalSummary = array('rows' => array());
for ($i = 1; $i <= 84; $i++) {
    $fullCatalog['items'][] = array('id' => $i, 'name' => 'Sample ' . $i);
    if ($i <= 72) $physicalSummary['rows'][] = array('collection_id' => $i, 'sample_type' => 'Sample ' . $i);
}
if (count(SampleTypeSummaryExport::forPdf($fullCatalog, 'https://example.org', $physicalSummary)['rows']) !== 72) throw new Exception('PDF must follow the 72 summary rows, not all 84 explorer entries');
class CacheTestSummaryExport extends SampleTypeSummaryExport {
    public static $renders = 0;
    public static function pdf(array $summary): string {
        self::$renders++;
        return '%PDF-test-' . self::$renders;
    }
}
$cacheDirectory = sys_get_temp_dir() . '/summary-cache-' . uniqid();
mkdir($cacheDirectory);
try {
    $firstPdf = CacheTestSummaryExport::cachedPdf($summary, $cacheDirectory);
    if (CacheTestSummaryExport::cachedPdf($summary, $cacheDirectory) !== $firstPdf || CacheTestSummaryExport::$renders !== 1) {
        throw new Exception('Repeated download regenerated PDF');
    }
    $changed = $summary;
    $changed['rows'][0]['description'] = 'Updated';
    CacheTestSummaryExport::cachedPdf($changed, $cacheDirectory);
    if (CacheTestSummaryExport::$renders !== 2) throw new Exception('Changed data must invalidate PDF');
    file_put_contents($cacheDirectory . '/sample-summary-pdf.json', 'incomplete');
    CacheTestSummaryExport::cachedPdf($changed, $cacheDirectory);
    if (CacheTestSummaryExport::$renders !== 3) throw new Exception('Corrupt cache must be rebuilt');
    CacheTestSummaryExport::cachedPdf($summary, $cacheDirectory . '/missing');
    if (CacheTestSummaryExport::$renders !== 4) throw new Exception('Unavailable cache must not prevent downloading');
} finally {
    foreach (glob($cacheDirectory . '/*') as $file) unlink($file);
    rmdir($cacheDirectory);
}
$unicodeCsv = SampleTypeSummaryExport::csv(array('columns' => array('sample_type'), 'rows' => array(array('sample_type' => 'Hawaiʻi specimens'))));
if (substr($unicodeCsv, 0, 3) !== "\xEF\xBB\xBF" || strpos($unicodeCsv, 'Hawaiʻi specimens') === false) throw new Exception('CSV must preserve the okina in UTF-8');

$externalRow = array_fill_keys($columns, null);
$externalRow['collid'] = 200;
$externalRow['sample_type'] = 'Outside repository specimens';
$externalItem = array('id' => 200, 'name' => 'Outside repository specimens',
    'outsideRepository' => true, 'collectionCode' => 'EXT', 'description' => 'External specimens.',
    'taxa' => array(), 'protocols' => array(), 'preservation' => array(), 'siteCodes' => array());
$externalCatalog = array('items' => array($externalItem));
$externalSummary = SampleTypeSummaryExport::read(new SummaryResult(array($externalRow), $columns), $externalCatalog);
if (strpos(SampleTypeSummaryExport::csv($externalSummary), 'Outside repository specimens') === false) throw new Exception('Outside collection missing from CSV');
if (json_decode(json_encode($externalSummary['rows']), true)[0]['collection_id'] !== 200) throw new Exception('Outside collection missing from JSON');
$externalPdf = SampleTypeSummaryExport::forPdf($externalCatalog, 'https://example.org', $externalSummary);
if ($externalPdf['rows'][0]['sample_type'] !== 'Outside repository specimens') throw new Exception('Outside collection missing from PDF');

if (in_array('--pdf-base64', $argv ?? array(), true)) {
    $pdf = SampleTypeSummaryExport::pdf($pdfSummary);
    if (substr($pdf, 0, 5) !== '%PDF-' || strpos($pdf, '%%EOF') === false) throw new Exception('Invalid PDF');
    echo base64_encode($pdf);
} else {
    echo "Sample summary export contract passed\n";
}
