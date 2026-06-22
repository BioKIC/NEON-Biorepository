<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT . '/classes/TaxonomyEditorManager.php');
include_once($SERVER_ROOT . '/classes/OccurrenceListManager.php');
//neon edit; add custom functions
include_once($SERVER_ROOT . '/neon/classes/OccurrenceListFunctions.php');
$occurrenceListFunctions = new OccurrenceListFunctions();
include_once($SERVER_ROOT.'/classes/ImageLibrarySearch.php');
$imgLibManager = new ImageLibrarySearch();
$imagePageNumber = array_key_exists('imagepage', $_REQUEST) ? filter_var($_REQUEST['imagepage'], FILTER_SANITIZE_NUMBER_INT) : 1;
//end neon edit
if ($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/list.' . $LANG_TAG . '.php'))
	include_once($SERVER_ROOT . '/content/lang/collections/list.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/list.en.php');
header("Content-Type: text/html; charset=" . $CHARSET);

$taxonFilter = array_key_exists('taxonfilter', $_REQUEST) ? filter_var($_REQUEST['taxonfilter'], FILTER_SANITIZE_NUMBER_INT) : 0;
$targetTid = array_key_exists('targettid', $_REQUEST) ? filter_var($_REQUEST['targettid'], FILTER_SANITIZE_NUMBER_INT) : '';
$tabIndex = array_key_exists('tabindex', $_REQUEST) ? filter_var($_REQUEST['tabindex'], FILTER_SANITIZE_NUMBER_INT) : 1;
$cntPerPage = array_key_exists('cntperpage', $_REQUEST) ? filter_var($_REQUEST['cntperpage'], FILTER_SANITIZE_NUMBER_INT) : 100;
$pageNumber = array_key_exists('page', $_REQUEST) ? filter_var($_REQUEST['page'], FILTER_SANITIZE_NUMBER_INT) : 1;
$datasetid = array_key_exists('datasetid', $_REQUEST) ? filter_var($_REQUEST['datasetid'], FILTER_SANITIZE_NUMBER_INT) : '';
$sortField1 = array_key_exists('sortfield1', $_REQUEST) ? $_REQUEST['sortfield1'] : '';
$sortField2 = array_key_exists('sortfield2', $_REQUEST) ? $_REQUEST['sortfield2'] : '';
$sortOrder = !empty($_REQUEST['sortorder']) ? 'desc' : '';
$comingFrom =  (array_key_exists('comingFrom', $_REQUEST) ? $_REQUEST['comingFrom'] : '');
if ($comingFrom != 'harvestparams' && $comingFrom != 'newsearch') {
	//If not set via a valid input variable, use setting set within symbini
	$comingFrom = !empty($SHOULD_USE_HARVESTPARAMS) ? 'harvestparams' : 'newsearch';
}

$_SESSION['datasetid'] = filter_var($datasetid, FILTER_SANITIZE_NUMBER_INT);

$collManager = new OccurrenceListManager();
$searchVar = $collManager->getQueryTermStr();
if ($targetTid && array_key_exists('mode', $_REQUEST)) $searchVar .= '&mode=voucher&targettid=' . $targetTid;
//NEON edit
//$searchVar .= '&comingFrom=' . $comingFrom;
//end NEON edit
if ($sortField1) {
	$searchVar .= '&sortfield1=' . htmlspecialchars($sortField1, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&sortorder=' . $sortOrder;
	if ($sortField2) {
		$searchVar .= '&sortfield2=' . htmlspecialchars($sortField2, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
		$collManager->addSort($sortField2, $sortOrder);
	}
	$collManager->addSort($sortField1, $sortOrder);
}

$occurArr = $collManager->getSpecimenMap($pageNumber, $cntPerPage);
//NEON edit
//sample type summary
$biorepoAvailabilitySiteCodes = $occurrenceListFunctions->getNeonAvailabilitySiteCodes();
$collectionTypeSummary = $occurrenceListFunctions->getCollectionTypeSummary();
$additionalCollectionTypeSummary = $occurrenceListFunctions->getAdditionalCollectionTypeSummary();

$combinedCollectionFamilies = array_merge(
	$collectionTypeSummary['families'] ?? [],
	$additionalCollectionTypeSummary['families'] ?? []
);

foreach($combinedCollectionFamilies as &$family){
	$family['percent'] = round(
		($family['total'] / $collectionTypeSummary['totalRecords']) * 100,
		1
	);
}
unset($family);

//material sample
$materialSampleArr = [];
$dbSearchTerm = $collManager->getSearchTerm('db');

if($dbSearchTerm){
    $dbArr = explode(',', $dbSearchTerm);
    if (array_intersect($dbArr, [118, 17, 19, 28])) {
        $materialSampleArr = $occurrenceListFunctions->getMaterialSampleTypes(array_keys($occurArr));
    }
}
//end NEON edit
$_SESSION['citationvar'] = $searchVar;

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['PAGE_TITLE']; ?></title>
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	include_once($SERVER_ROOT . '/includes/googleanalytics.php');

	// NEON start
	parse_str($searchVar, $params);
	$encodedSearchVar = json_encode($searchVar, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
	?>

	<script>
	window.biorepoAvailabilitySiteCodes = <?php echo json_encode(
		$biorepoAvailabilitySiteCodes ?? [],
		JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
	); ?>;

	window.biorepoCollectionTypeSummary = <?php echo json_encode(
		$combinedCollectionFamilies,
		JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
	); ?>;

	window.biorepoCollectionTypeSummaryTotal = <?php echo json_encode(
		$collectionTypeSummary['totalRecords'],
		JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
	); ?>;

	const params = <?php echo json_encode($params, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
	const rawSearchVar = <?php echo $encodedSearchVar; ?>;

	const eventParams = {};

	Object.keys(params).forEach(key => {
		eventParams[key] = Array.isArray(params[key])
			? params[key].join(',')
			: params[key];
	});

	eventParams.rawSearchVar = rawSearchVar;

	window.pendingGAEvents.push([
		'event',
		'search_query',
		{
			event_category: 'Search',
			event_label: 'Search Parameters',
			...eventParams
		}
	]);
	</script>
	<!-- NEON end-->

	<link href="<?= $CSS_BASE_PATH; ?>/symbiota/collections/list.css?ver=<?= date('YmdHis'); ?>" type="text/css" rel="stylesheet" />
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.min.css" type="text/css" rel="stylesheet">
	<link rel="stylesheet"
		  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		let urlQueryStr = "<?php if ($searchVar) echo $searchVar . '&page=' . $pageNumber; ?>";

		$(document).ready(function() {
			<?php
			if ($searchVar) {
			?>
				sessionStorage.querystr = "<?php echo $searchVar; ?>";
			<?php
			}
			?>

			$('#tabs').tabs({
				active: <?= $tabIndex; ?>,
				beforeLoad: function(event, ui) {
					$(ui.panel).html("<p>Loading...</p>");
				}
			});
		});

		function validateOccurListForm(f) {
			if (f.targetdatasetid.value == "") {
				alert("<?= $LANG['SELECT_DATASET'] ?>");
				return false;
			}
			return true;
		}

		function hasSelectedOccid(f) {
			var isSelected = false;
			for (var h = 0; h < f.length; h++) {
				if (f.elements[h].name == "occid[]" && f.elements[h].checked) {
					isSelected = true;
					break;
				}
			}
			if (!isSelected) {
				alert("<?= $LANG['SELECT_OCCURRENCE'] ?>");
				return false;
			}
			return true;
		}

		function displayDatasetTools() {
			$('.dataset-div').toggle();
			document.getElementById("dataset-tools").scrollIntoView({
				behavior: 'smooth'
			});
		}
	</script>
	<script src="../js/symb/collections.list.js?ver=5" type="text/javascript"></script>
	<script src="../js/symb/shared.js?ver=1" type="text/javascript"></script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_listMenu) ? $collections_listMenu : false);
	include($SERVER_ROOT . '/includes/header.php');
	if (isset($collections_listCrumbs)) {
		if ($collections_listCrumbs) {
			echo '<div class="navpath">';
			echo '<a href="../index.php">' . $LANG['NAV_HOME'] . '</a> &gt;&gt; ';
			echo $collections_listCrumbs . ' &gt;&gt; ';
			echo '<b>' . $LANG['NAV_SPECIMEN_LIST'] . '</b>';
			echo '</div>';
		}
	} else {
		echo '<div class="navpath">';
		echo '<a href="../index.php">' . $LANG['NAV_HOME'] . '</a> &gt;&gt; ';
		if ($comingFrom == 'harvestparams') {
			echo '<a href="index.php">' . $LANG['NAV_COLLECTIONS'] . '</a> &gt;&gt; ';
			echo '<a href="' . $CLIENT_ROOT . '/collections/harvestparams.php">' . $LANG['NAV_SEARCH'] . '</a> &gt;&gt; ';
		} else {
			echo '<a href="' . $CLIENT_ROOT . '/neon/search/index.php">' . $LANG['NAV_SEARCH'] . '</a> &gt;&gt; ';
		}
		echo '<b>' . $LANG['NAV_SPECIMEN_LIST'] . '</b>';
		echo '</div>';
	}
	?>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading screen-reader-only"><?php echo $LANG['SEARCH_RES_LIST']; ?></h1>
		<div id="tabs" class="top-breathing-room-rel" style="margin-bottom: 1rem">
			<ul>
				<li>
					<a id="taxatablink" href="<?= 'checklist.php?' . $searchVar . '&taxonfilter=' . $taxonFilter ?>">
						<span>Taxa</span>
					</a>
				</li>
				<li>
					<a href="#speclist">
						<span>Records</span>
					</a>
				</li>
				<li>
					<!-- neon edit: convert map to JSON tab thus reducing load on this page -->
					<a id="maptablink" href="maptab.php?<?= $searchVar ?>">
						<span>Map</span>
					</a>
					<!-- end neon edit -->
				</li>
				<!-- neon edit: Add new Image tab -->
				<li>
					<a id="imagesdiv" href="imagetab.php?<?= $searchVar . '&imagepage=' . $imagePageNumber ?>">
						<span>Gallery</span>
					</a>
				</li>
				<!-- end neon edit -->
				<!-- neon edit: Add new Metrics tab -->
				<li>
					<a href="#metricsdiv">
						<span id="metricstab">Metrics</span>
					</a>
				</li>
				<!-- end neon edit -->
			</ul>
			<div id="speclist">
				<div id="biorepo-coll-type"></div>
				<div id="queryrecords">
					<div style="display:flex; justify-content: flex-end; margin-top:16px;"> <!--buttons div-->
						<?php
						if ($SYMB_UID) {
						?>
							<span style="margin-right: 8px">
								<button class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-containedPrimary" onclick="displayDatasetTools()" type="button">
									<span class="MuiButton-label" style="font-size: 0.55rem;">
										<i class="fa-solid fa-table-cells-large" style="font-size: 0.75rem; margin-right: 1.2em;"></i>
										Dataset Management
										<span class="MuiButton-endIcon MuiButton-iconSizeMedium">
											<i class="fa-solid fa-chevron-right" style="font-size: 0.55rem; margin-left: 1.4em;"></i>
										</span>
									</span>
									<span class="MuiTouchRipple-root"></span>
								</button>
							</span>
						<?php
						}
						?>
						<span style="margin-right: 8px">
							<button class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-containedPrimary" type="button" onclick="toggleElement('#sort-div', 'block')">
								<span class="MuiButton-label" style="font-size: 0.55rem;">
									<i class="fa-solid fa-sort" style="font-size: 0.75rem; margin-right: 1.2em;"></i>
									Sort
									<span class="MuiButton-endIcon MuiButton-iconSizeMedium">
										<i class="fa-solid fa-chevron-down" style="font-size: 0.55rem; margin-left: 1.4em;"></i>
									</span>
								</span>
								<span class="MuiTouchRipple-root"></span>
							</button>
						</span>
						<span style="margin-right: 8px">
							<form class="button-form" action="listtabledisplay.php" method="post">
								<input name="comingFrom" type="hidden" value="<?= $comingFrom; ?>" />
								<input name="sortfield1" type="hidden" value="<?= htmlspecialchars($sortField1, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" />
								<input name="sortfield2" type="hidden" value="<?= htmlspecialchars($sortField2, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" />
								<input name="sortorder" type="hidden" value="<?= $sortOrder ?>" />
								<input name="searchvar" type="hidden" value="<?php echo $searchVar ?>" />
								<button class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-containedPrimary" type="submit">
									<span class="MuiButton-label" style="font-size: 0.55rem;">
										<i class="fa-solid fa-table" style="font-size: 0.75rem; margin-right: 1.2em;"></i>
										Table Display
										<span class="MuiButton-endIcon MuiButton-iconSizeMedium">
											<i class="fa-solid fa-chevron-right" style="font-size: 0.55rem; margin-left: 1.4em;"></i>
										</span>
									</span>
									<span class="MuiTouchRipple-root"></span>
								</button>
							</form>
						</span>
						<span>
							<!--neon edit-->
							<form class="button-form" action="download/neonindex.php" method="post" onsubmit="targetPopup(this)">
							<!--end neon edit-->
								<button class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-containedPrimary" type="submit">
									<span class="MuiButton-label" style="font-size: 0.55rem;">
										<i class="fa-solid fa-download" style="font-size: 0.75rem; margin-right: 1.2em;"></i>
										Download Records
										<span class="MuiButton-endIcon MuiButton-iconSizeMedium">
											<i class="fa-solid fa-chevron-right" style="font-size: 0.55rem; margin-left: 1.4em;"></i>
										</span>
									</span>
									<span class="MuiTouchRipple-root"></span>
								</button>
								<input name="searchvar" type="hidden" value="<?= $searchVar ?>" />
								<input name="dltype" type="hidden" value="specimen" />
							</form>
						</span>
<!--						<span>
							<button class="icon-button" onclick="copyUrl()" aria-label="<?= $LANG['COPY_TO_CLIPBOARD'] ?>" title="<?= $LANG['COPY_TO_CLIPBOARD'] ?>">
								<svg style="width:1.3em;height:1.3em" alt="<?= $LANG['IMG_COPY']; ?>" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24">
									<path d="M440-280H280q-83 0-141.5-58.5T80-480q0-83 58.5-141.5T280-680h160v80H280q-50 0-85 35t-35 85q0 50 35 85t85 35h160v80ZM320-440v-80h320v80H320Zm200 160v-80h160q50 0 85-35t35-85q0-50-35-85t-85-35H520v-80h160q83 0 141.5 58.5T880-480q0 83-58.5 141.5T680-280H520Z" />
								</svg>
							</button>
						</span>-->
					</div>
					<div id="sort-div" style="display:<?= ($sortField1 ? 'block' : 'none') ?>">
						<section style="margin: 2rem 0 1rem 1rem;">
							<form name="sortform" action="list.php" method="post">
								<div id="sort-inner-div">
									<div>
										<label for="sortfield1"><?= $LANG['SORT_BY'] ?>:</label>
										<select name="sortfield1" id="sortfield1">
											<option value=""></option>
											<?php
											$sortFields = array(
												'c.collectionname' => $LANG['COLLECTION'],
												'o.catalogNumber' => $LANG['CATALOG_NUMBER'],
												'o.family' => $LANG['FAMILY'],
												'o.sciname' => $LANG['SCINAME'],
												'o.recordedBy' => $LANG['COLLECTOR'],
												'o.recordNumber' => $LANG['NUMBER'],
												'o.eventDate' => $LANG['EVENT_DATE'],
												'o.country' => $LANG['COUNTRY'],
												'o.StateProvince' => $LANG['STATE_PROVINCE'],
												'o.county' => $LANG['COUNTY'],
												'o.minimumElevationInMeters' => $LANG['ELEVATION']
											);
											foreach ($sortFields as $k => $v) {
												echo '<option value="' . $k . '" ' . ($k == $sortField1 ? 'SELECTED' : '') . '>' . $v . '</option>';
											}
											?>
										</select>
									</div>
									<div>
										<label for="sortfield2"><?= $LANG['SORT_THEN_BY'] ?>:</label>
										<select name="sortfield2" id="sortfield2">
											<option value=""></option>
											<?php
											foreach ($sortFields as $k => $v) {
												echo '<option value="' . $k . '" ' . ($k == $sortField2 ? 'SELECTED' : '') . '>' . $v . '</option>';
											}
											?>
										</select>
									</div>
									<div>
										<label for="sortorder"> <?= $LANG['SORT_ORDER'] ?>: </label>
										<select id="sortorder" name="sortorder">
											<option value=""><?= $LANG['SORT_ASCENDING'] ?></option>
											<option value="desc" <?= ($sortOrder == "desc" ? 'SELECTED' : ''); ?>><?= $LANG['SORT_DESCENDING'] ?></option>
										</select>
									</div>
									<div>
										<input name="searchvar" type="hidden" value="<?= $searchVar ?>">
										<button name="formsubmit" class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-containedPrimary" type="submit">
											<span class="MuiButton-label" style="font-size: 0.55rem;">
												Sort
											</span>
											<span class="MuiTouchRipple-root"></span>
										</button>
									</div>
								</div>
							</form>
						</section>
					</div>
					<div style="clear:both;"></div>
					<?php
					$paginationStr = '<div><div style="clear:both;"><hr/></div><div style="float:left;margin:5px;">';
					$lastPage = (int)($collManager->getRecordCnt() / $cntPerPage) + 1;
					$startPage = ($pageNumber > 5 ? $pageNumber - 5 : 1);
					$endPage = ($lastPage > $startPage + 10 ? $startPage + 10 : $lastPage);
					$pageBar = '';
					if ($startPage > 1) {
						$pageBar .= '<span class="pagination" style="margin-right:5px;"><a href="list.php?' . $searchVar . '" >' . $LANG['PAGINATION_FIRST'] . '</a></span>';
						$pageBar .= '<span class="pagination" style="margin-right:5px;"><a href="list.php?' . $searchVar . '&page=' . (($pageNumber - 10) < 1 ? 1 : $pageNumber - 10) . '">&lt;&lt;</a></span>';
					}
					for ($x = $startPage; $x <= $endPage; $x++) {
						if ($pageNumber != $x) {
							$pageBar .= '<span class="pagination" style="margin-right:3px;margin-right:3px;"><a href="list.php?' . $searchVar . '&page=' . $x . '">' . $x . '</a></span>';
						} else {
							$pageBar .= '<span class="pagination" style="margin-right:3px;margin-right:3px;font-weight:bold;">' . $x . '</span>';
						}
					}
					if (($lastPage - $startPage) >= 10) {
						$pageBar .= '<span class="pagination" style="margin-left:5px;"><a href="list.php?' . $searchVar . '&page=' . (($pageNumber + 10) > $lastPage ? $lastPage : ($pageNumber + 10)) . '">&gt;&gt;</a></span>';
						$pageBar .= '<span class="pagination" style="margin-left:5px;"><a href="list.php?' . $searchVar . '&page=' . $lastPage . '">Last</a></span>';
					}
					$pageBar .= '</div><div style="float:right;margin:5px;">';
					$beginNum = ($pageNumber - 1) * $cntPerPage + 1;
					$endNum = $beginNum + $cntPerPage - 1;
					if ($endNum > $collManager->getRecordCnt()) $endNum = $collManager->getRecordCnt();
					//neon edit; add text
					$pageBar .= $LANG['PAGINATION_PAGE'] . ' ' . $pageNumber . ', ' . $beginNum . '-' . $endNum . ' ' . $LANG['PAGINATION_OF'] . ' ' . $collManager->getRecordCnt() . ' records';
					//end neon edit
					$paginationStr .= $pageBar;
					$paginationStr .= '</div><div style="clear:both;"><hr/></div></div>';
					echo $paginationStr;

					//Add search return
					if ($occurArr) {
					?>
						<form name="occurListForm" method="post" action="datasets/datasetHandler.php" onsubmit="return validateOccurListForm(this)" target="_blank">
							<?php include('datasetinclude.php'); ?>
							<table id="omlisttable">
								<?php
								$permissionArr = array();
								if (array_key_exists('CollAdmin', $USER_RIGHTS)) $permissionArr = $USER_RIGHTS['CollAdmin'];
								if (array_key_exists('CollEditor', $USER_RIGHTS)) $permissionArr = array_merge($permissionArr, $USER_RIGHTS['CollEditor']);
								foreach ($occurArr as $occid => $fieldArr) {
									$collId = $fieldArr['collid'];
									$taxonEditorObj = new TaxonomyEditorManager();
									$taxonEditorObj->setTid($fieldArr['tid']);
									$taxonEditorObj->setTaxon();
									$splitSciname = $taxonEditorObj->splitSciname();
									$author = !empty($splitSciname['author']) ? ($splitSciname['author'] . ' ') : '';
									$cultivarEpithet = !empty($splitSciname['cultivarEpithet']) ? ($taxonEditorObj->standardizeCultivarEpithet($splitSciname['cultivarEpithet'])) . ' ' : '';
									$tradeName = !empty($splitSciname['tradeName']) ? ($taxonEditorObj->standardizeTradeName($splitSciname['tradeName']) . ' ') : '';
									$nonItalicizedScinameComponent = $author . $cultivarEpithet . $tradeName;
									echo '<tr><td width="60" valign="top" align="center">';
									echo '<a href="misc/collprofiles.php?collid=' . $fieldArr['collid'] . '" target="_blank">';
									if ($fieldArr["icon"]) {
										$icon = (substr($fieldArr["icon"], 0, 6) == 'images' ? '../' : '') . $fieldArr["icon"];
										echo '<img align="bottom" src="' . $icon . '" style="width:35px;border:0px;" />';
									}
									echo '</a>';
									echo '<div style="font-weight:bold;font-size:75%;">';
									$instCode = $fieldArr["instcode"];
									if ($fieldArr["collcode"]) $instCode .= ":" . $fieldArr["collcode"];
									echo $instCode;
									echo '</div>';
									echo '<div style="margin-top:10px"><span class="dataset-div checkbox-elem" style="display:none;"><input name="occid[]" type="checkbox" value="' . $occid . '" /></span></div>';
									echo '</td><td>';
									if ($IS_ADMIN || in_array($fieldArr['collid'], $permissionArr) || ($SYMB_UID && $SYMB_UID == $fieldArr['obsuid'])) {
										echo '<div style="float:right;" title="' . $LANG['OCCUR_EDIT_TITLE'] . '">';
										echo '<a href="editor/occurrenceeditor.php?occid=' . $occid . '" target="_blank">';
										echo '<img src="../images/edit.png" style="width:1.3em" alt="' . $LANG['IMG_EDIT_OCC'] . '" /></a></div>';
									}
									if (isset($fieldArr['has_audio']) && $fieldArr['has_audio']) {
										echo '<div style="float:right; padding-right: 0.5rem"><img style="width:1.3rem; border: 0" src="' . $CLIENT_ROOT . '/images/speaker_thumbnail.png' . '"/></div>';
									}
									$targetClid = $collManager->getSearchTerm("targetclid");
									if (isset($fieldArr['has_image']) && $fieldArr['has_image']) {
										echo '<div style="float:right;margin:5px 25px;">';
										echo '<a href="#" onclick="return openIndPU(' . $occid . ',' . ($targetClid ? $targetClid : "0") . ');">';
										echo '<img src="' . $fieldArr['media']['thumbnail'] . '" style="height:70px" alt="' . (isset($LANG['IMG_OCC']) ? $LANG['IMG_OCC'] : 'Image Associated With the Occurrence') . '"
											onerror="this.onerror=null; this.src=\'' . $CLIENT_ROOT . '/images/image-icon.svg\';" />';
										echo '</a></div>';
									}
									if ($collManager->getClName() && $targetTid && array_key_exists('mode', $_REQUEST)) {
										echo '<div style="float:right;" >';
										echo '<a href="#" onclick="addVoucherToCl(' . $occid . ',' . $targetClid . ',' . $targetTid . ');return false" title="' . $LANG['VOUCHER_LINK_TITLE'] . ' ' . $collManager->getClName() . ';">';
										echo '<img src="../images/voucheradd.png" style="border:solid 1px gray;height:1.3em;margin-right:5px;" alt="' . $LANG['IMG_ADD_VOUCHER'] . '"/></a></div>';
									}
									if (isset($fieldArr['media']) && isset($fieldArr['media']['thumbnailurl'])) {
										echo '<div style="float:right;margin:5px 25px;">';
										// neon edit
										echo '<a href="individual/index.php?occid=' . $occid . '&clid=0" onclick="return openIndPU(' . $occid . ',' . ($targetClid ? $targetClid : "0") . ');">';
										// end edit
										echo '<img src="' . $fieldArr['media']['thumbnailurl'] . '" style="height:70px" alt="' . $LANG['IMG_OCC'] . '"/></a></div>';
									}
									echo '<div style="margin:4px;">';

									if (isset($fieldArr['sciname'])) {
										$sciStr = '<span style="font-style:italic;">' . $fieldArr['sciname'] . '</span>';
										if (isset($fieldArr['author']) && $fieldArr['author']) $sciStr .= ' ' . $fieldArr['author'];
										if (isset($fieldArr['tid']) && $fieldArr['tid']) {
											$sciStr = '<a target="_blank" href="../taxa/index.php?tid=' . strip_tags($fieldArr['tid']) . '">'
												. '<i> ' . strip_tags($splitSciname['base']) . '</i>'
												. (!empty($nonItalicizedScinameComponent) ? (' ' . $nonItalicizedScinameComponent) : '') . '</a>';
										}
										echo $sciStr;
									}
									echo '</div>';
									echo '<div style="margin:4px">';
									echo '<span style="width:150px;">' . $fieldArr["catnum"] . '</span>';
									//NEON edit
									if (isset($fieldArr['sampleID'])) {
										echo '<span style="width:150px;margin-left:30px;">' . $fieldArr["sampleID"] . '</span>';
									}
									if (isset($fieldArr['sampleCode'])) {
										echo '<span style="width:150px;margin-left:30px;">' . $fieldArr["sampleCode"] . '</span>';
									}
									echo '</div><div style="margin:4px">';
									echo '<span style="width:200px;">' . $fieldArr["collector"] . '&nbsp;&nbsp;&nbsp;' . (isset($fieldArr["collnum"]) ? $fieldArr["collnum"] : '') . '</span>';
									//end NEON edit
									if (isset($fieldArr["date"])) echo '<span style="margin-left:30px;">' . $fieldArr["date"] . '</span>';
									echo '</div><div style="margin:4px">';
									$localStr = '';
									if ($fieldArr["country"]) $localStr .= $fieldArr["country"];
									if ($fieldArr["state"]) $localStr .= ', ' . $fieldArr["state"];
									if ($fieldArr["county"]) $localStr .= ', ' . $fieldArr["county"];
									if ($fieldArr['locality'] == 'PROTECTED') {
										$localStr .= ', <span class="protected-span">' . $LANG['PROTECTED'] . '</span>';
									} else {
										if ($fieldArr['locality']) $localStr .= ', ' . $fieldArr['locality'];
										if ($fieldArr['declat']) $localStr .= ', ' . $fieldArr['declat'] . ' ' . $fieldArr['declong'];
										if (isset($fieldArr['elev']) && $fieldArr['elev']) $localStr .= ', ' . $fieldArr['elev'] . 'm';
									}
									$localStr = trim($localStr, ' ,');
									echo $localStr;
									echo '</div><div style="margin:4px">';
									//neon edit
									if(!empty($materialSampleArr[$occid])){
										echo '<div style="margin-top:4px;">';
										echo '<strong>Prepared Tissues:</strong> ';
									
										$sampleDisplayArr = [];
									
										foreach($materialSampleArr[$occid] as $sample){
											$sampleDisplayArr[] =
												htmlspecialchars($sample['sampleType'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE)
												. ' (' .
												htmlspecialchars($sample['disposition'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE)
												. ')';
										}
									
										echo implode(', ', array_unique($sampleDisplayArr));
										echo '</div>';
									}
									echo '<b><a href="individual/index.php?occid=' . $occid . '&clid=0" onclick="return openIndPU(' . $occid . ',' . ($targetClid ? $targetClid : "0") . ');">' . $LANG['FULL_DETAILS'] . '</a></b>';
									//end edit
									echo '</div></td></tr><tr><td colspan="2"><hr/></td></tr>';
								}
								?>
							</table>
						</form>
					<?php
						echo $paginationStr;
						echo '<hr/>';
					} else {
						echo '<div><h3>' . $LANG['NO_RESULTS'] . '</h3>';
						$tn = $collManager->getTaxaSearchStr();
						if ($p = strpos($tn, ';')) {
							$tn = substr($tn, 0, $p);
						}
						if ($p = strpos($tn, '=>')) {
							$tn = substr($tn, $p + 2);
						}
						if ($p = strpos($tn, '(')) {
							$tn = substr($tn, 0, $p);
						}
						if ($closeArr = $collManager->getCloseTaxaMatch($tn)) {
							echo '<div style="margin: 40px 0px 200px 20px;font-weight:bold;">';
							echo $LANG['PERHAPS_LOOKING_FOR'] . ' ';
							$outStr = '';
							$actionPage = $comingFrom === 'newsearch' ? 'search/index' : 'harvestparams';
							foreach ($closeArr as $v) {
								$outStr .= '<a href="' . $actionPage  . '.php?taxa=' . $v . '">' . $v . '</a>, ';
							}
							echo trim($outStr, ' ,');
							echo '</div>';
						}
						echo '</div>';
					}
					?>
				</div>
			</div>
			<div id="metricsdiv">
				<div id="biorepo-search-metrics"></div>
			</div>
		</div>
	</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
</body>

</html>
