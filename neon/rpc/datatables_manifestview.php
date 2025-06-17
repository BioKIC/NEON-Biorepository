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
            return $d == 1 ? 'Y' : ($d == 0 ? 'N' : '');
        }
    ),
    array(
        'db' => 'acceptedForAnalysis',
        'dt' => 9,
        'formatter' => function( $d, $row ) {
            return $d == 1 ? 'Y' : ($d == 0 ? 'N' : '');
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
    #these are for the child notes row
    array('db' => 'alternativeSampleID', 'dt' => 13),
    array('db' => 'hashedSampleID',      'dt' => 14),
    array('db' => 'individualCount',     'dt' => 15),
    array('db' => 'filterVolume',        'dt' => 16),
    array('db' => 'domainRemarks',       'dt' => 17),
    array('db' => 'notes',               'dt' => 18),
    array('db' => 'checkinRemarks',      'dt' => 19),
    array('db' => 'dynamicProperties',   'dt' => 20),
    array('db' => 'symbiotaTarget',      'dt' => 21),
    array('db' => 'errorMessage',        'dt' => 22)
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

    $whereAll = [
        'condition' => implode(' AND ', $conditionParts),
        'bindings' => $bindings
    ];
}

 
require($SERVER_ROOT.'/neon/classes/DatatablesSSP.php');
 
echo json_encode(
    SSP::complex( $_POST, $table, $primaryKey, $columns, $whereResult=null, $whereAll )
);