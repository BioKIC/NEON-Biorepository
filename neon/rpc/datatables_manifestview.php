<?php
include_once('../../config/symbini.php');

// DB table to use
$table = 'NeonSample';
 
// Table's primary key
$primaryKey = 'samplePK';
 
// Array of database columns which should be read and sent back to DataTables.
// The `db` parameter represents the column name in the database, while the `dt`
// parameter represents the DataTables column identifier. In this case simple
// indexes
$columns = array(
    array(
        'db' => 'samplePK',
        'dt' => 0,
        'formatter' => function($d, $row) {
            $checkbox = '<input id="scbox-' . (int)$d . '" class="scbox" name="scbox[]" type="checkbox" value="' . (int)$d . '" />';
            $editLink = '<a href="#" onclick="return openSampleEditor(' . (int)$d . ')"><img src="../../images/edit.png" style="width:12px" /></a>';
            return $checkbox . ' ' . $editLink;
        }
    ),
    array( 'db' => 'sampleID',            'dt' => 1 ),
    array( 'db' => 'sampleCode',          'dt' => 2 ),
    array( 'db' => 'sampleClass',         'dt' => 3 ),
    array( 'db' => 'taxonID',             'dt' => 4 ),
    array( 'db' => 'namedLocation',       'dt' => 5 ),
    array( 'db' => 'collectDate',         'dt' => 6 ),
    array( 'db' => 'quarantineStatus',    'dt' => 7 ),
    array(
        'db' => 'sampleReceived',
        'dt' => 8,
        'formatter' => function( $d, $row ) {
            return $d == 1 ? 'Y' : ($d === 0 ? 'N' : '');
        }
    ),
    array(
        'db' => 'acceptedForAnalysis',
        'dt' => 9,
        'formatter' => function( $d, $row ) {
            return $d == 1 ? 'Y' : ($d === 0 ? 'N' : '');
        }
    ),
    array( 'db' => 'sampleCondition',     'dt' => 10 ),
    array(
        'db' => 'checkinTimestamp',
        'dt' => 11,
        'formatter' => function($d, $row) {
            $samplePK = (int)$row['samplePK'];
    
            if ($d === null || $d === '') {
                return '';
            }
    
            $timestamp = htmlspecialchars($d);
            $editLink = '<a href="#" onclick="return openSampleCheckinEditor(' . $samplePK . ')"><img src="../../images/edit.png" style="width:13px" /></a>';
    
            return $timestamp . ' ' . $editLink;
        }
    ),
    array(
        'db' => 'occid',
        'dt' => 12,
        'formatter' => function($d, $row) {
            if (!$d) {
                return '';
            }
    
            $escapedOccid = htmlspecialchars($d);
            $harvestTs = isset($row['harvestTimestamp']) ? htmlspecialchars($row['harvestTimestamp']) : '';
    
            $html = '<span title="harvested ' . $harvestTs . '">';
            $html .= '<a href="../../collections/individual/index.php?occid=' . $escapedOccid . '" target="_blank">' . $escapedOccid . '</a>';
            $html .= '<a href="../../collections/editor/occurrenceeditor.php?occid=' . $escapedOccid . '" target="_blank"><img src="../../images/edit.png" style="width:13px" /></a>';
            $html .= '</span>';
    
            return $html;
        }
    ),
    #this is for the child notes row
    array(
        'db' => 'samplePK',
        'dt' => 13,
        'formatter' => function($d, $row) {
            $parts = [];
    
            if (!empty($row['alternativeSampleID'])) $parts[] = '<div>Alternative Sample ID: '.htmlspecialchars($row['alternativeSampleID']).'</div>';
            if (!empty($row['hashedSampleID'])) $parts[] = '<div>Hashed Sample ID: '.htmlspecialchars($row['hashedSampleID']).'</div>';
            if (!empty($row['individualCount'])) $parts[] = '<div>Individual Count: '.htmlspecialchars($row['individualCount']).'</div>';
            if (!empty($row['filterVolume'])) $parts[] = '<div>Filter Volume: '.htmlspecialchars($row['filterVolume']).'</div>';
            if (!empty($row['domainRemarks'])) $parts[] = '<div>Domain Remarks: '.htmlspecialchars($row['domainRemarks']).'</div>';
            if (!empty($row['notes'])) $parts[] = '<div>Sample Notes: '.htmlspecialchars($row['notes']).'</div>';
            if (!empty($row['checkinRemarks'])) $parts[] = '<div>Check-in Remarks: '.htmlspecialchars($row['checkinRemarks']).'</div>';
    
            if (!empty($row['dynamicProperties'])) {
                $parsed = json_decode($row['dynamicProperties'], true);
                if (is_array($parsed)) {
                    $values = [];
                    foreach ($parsed as $key => $value) $values[] = htmlspecialchars($key).': '.htmlspecialchars((string)$value);
                    if ($values) $parts[] = '<div>'.implode('; ', $values).'</div>';
                }
            }
    
            if (!empty($row['symbiotaTarget'])) {
                $parsed = json_decode($row['symbiotaTarget'], true);
                if (is_array($parsed)) {
                    $values = [];
                    foreach ($parsed as $key => $value) $values[] = htmlspecialchars($key).': '.htmlspecialchars((string)$value);
                    if ($values) $parts[] = '<div>Symbiota targeted data ['.implode('; ', $values).']</div>';
                }
            }
    
            if (!empty($row['errorMessage'])) $parts[] = '<div>Occurrence Harvesting Error: '.htmlspecialchars($row['errorMessage']).'</div>';
    
            return implode('', $parts);
        }
    )
);

$shipmentPK = isset($_POST['shipmentPK']) && is_numeric($_POST['shipmentPK']) ? intval($_POST['shipmentPK']) : null;

$whereAll = null;

if ($shipmentPK !== null) {
    $conditionParts = ['shipmentPK = ?'];
    $bindings = [
        [
            'val' => $shipmentPK,
            'type' => 'i'
        ]
    ];

    $filter = $_POST['sampleFilter'] ?? '';
    $containerFilter = $_POST['containerFilter'] ?? '';

    if ($filter === 'notCheckedIn') {
        $conditionParts[] = 'checkinTimestamp IS NULL';
    } elseif ($filter === 'missingOccid') {
        $conditionParts[] = '(occid IS NULL OR occid = "")';
    } elseif ($filter === 'notAccepted') {
        $conditionParts[] = 'acceptedForAnalysis = 0';
    } elseif ($filter === 'altIds') {
        $conditionParts[] = 'alternativeSampleID IS NOT NULL';
    } elseif ($filter === 'harvestingError') {
        $conditionParts[] = 'errorMessage IS NOT NULL';
    }
    
    if (strpos($containerFilter, 'dyn:') === 0) {
        $parts = explode(':', $containerFilter, 3);
    
        if (count($parts) === 3) {
            $key = $parts[1];   // e.g. containerID
            $value = $parts[2]; // e.g. Box 1
    
            $conditionParts[] = "JSON_UNQUOTE(JSON_EXTRACT(dynamicProperties, '$.$key')) = ?";
            $bindings[] = [
                'val' => $value,
                'type' => 's'
            ];
        }
    }

    $whereAll = [
        'condition' => implode(' AND ', $conditionParts),
        'bindings' => $bindings
    ];
}

$extraColumns = [
    'alternativeSampleID',
    'hashedSampleID',
    'individualCount',
    'filterVolume',
    'domainRemarks',
    'notes',
    'checkinRemarks',
    'dynamicProperties',
    'symbiotaTarget',
    'errorMessage'
];
 
require($SERVER_ROOT.'/neon/classes/DatatablesSSP.php');
 
echo json_encode(
    SSP::complex( $_POST, $table, $primaryKey, $columns, $whereResult=null, $whereAll, $extraColumns )
);