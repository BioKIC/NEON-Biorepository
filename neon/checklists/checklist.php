<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistManager.php');
include_once($SERVER_ROOT.'/classes/MapSupport.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/checklists/checklist.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/checklists/checklist.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/checklists/checklist.en.php');
header('Content-Type: text/html; charset='.$CHARSET);

$action = array_key_exists('submitaction',$_REQUEST) ? $_REQUEST['submitaction'] : '';
$clid = array_key_exists('clid', $_REQUEST) && is_numeric($_REQUEST['clid']) ? filter_var($_REQUEST['clid'], FILTER_SANITIZE_NUMBER_INT) : 0;
if(!$clid && array_key_exists('cl',$_REQUEST)) $clid = filter_var($_REQUEST['cl'], FILTER_SANITIZE_NUMBER_INT);
$dynClid = array_key_exists('dynclid', $_REQUEST) ? filter_var($_REQUEST['dynclid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$pageNumber = array_key_exists('pagenumber', $_REQUEST) ? filter_var($_REQUEST['pagenumber'], FILTER_SANITIZE_NUMBER_INT) : 1;
$pid = array_key_exists('pid', $_REQUEST) ? filter_var($_REQUEST['pid'], FILTER_SANITIZE_NUMBER_INT) : '';
$thesFilter = array_key_exists('thesfilter', $_REQUEST) ? filter_var($_REQUEST['thesfilter'], FILTER_SANITIZE_NUMBER_INT) : 0;
$taxonFilter = array_key_exists('taxonfilter', $_REQUEST) ? htmlspecialchars($_REQUEST['taxonfilter'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) : '';
$showAuthors = array_key_exists('showauthors', $_REQUEST) ? filter_var($_REQUEST['showauthors'], FILTER_SANITIZE_NUMBER_INT) : 0;
$showSynonyms = array_key_exists('showsynonyms', $_REQUEST) ? filter_var($_REQUEST['showsynonyms'], FILTER_SANITIZE_NUMBER_INT) : 0;
$showCommon = array_key_exists('showcommon', $_REQUEST) ? filter_var($_REQUEST['showcommon'], FILTER_SANITIZE_NUMBER_INT) : 0;
$showImages = array_key_exists('showimages', $_REQUEST) ? filter_var($_REQUEST['showimages'], FILTER_SANITIZE_NUMBER_INT) : 0 ;
$limitImagesToVouchers = array_key_exists('voucherimages', $_REQUEST) ? filter_var($_REQUEST['voucherimages'], FILTER_SANITIZE_NUMBER_INT) : 0;
$showVouchers = array_key_exists('showvouchers', $_REQUEST) ? filter_var($_REQUEST['showvouchers'], FILTER_SANITIZE_NUMBER_INT) : 0;
$showAlphaTaxa = array_key_exists('showalphataxa', $_REQUEST) ? filter_var($_REQUEST['showalphataxa'], FILTER_SANITIZE_NUMBER_INT) : 0;
$showSubgenera = array_key_exists('showsubgenera', $_REQUEST) ? filter_var($_REQUEST['showsubgenera'], FILTER_SANITIZE_NUMBER_INT) : 0;
$searchCommon = array_key_exists('searchcommon', $_REQUEST) ? filter_var($_REQUEST['searchcommon'], FILTER_SANITIZE_NUMBER_INT) : 0;
$searchSynonyms = array_key_exists('searchsynonyms', $_REQUEST) ? filter_var($_REQUEST['searchsynonyms'], FILTER_SANITIZE_NUMBER_INT) : 0;
$defaultOverride = array_key_exists('defaultoverride', $_REQUEST) ? filter_var($_REQUEST['defaultoverride'], FILTER_SANITIZE_NUMBER_INT) : 0;
$printMode = array_key_exists('printmode', $_REQUEST) ? filter_var($_REQUEST['printmode'], FILTER_SANITIZE_NUMBER_INT) : 0;
$groupByRank = array_key_exists('groupbyrank', $_REQUEST) ? strtolower(trim($_REQUEST['groupbyrank'])) : 'family';

$statusStr='';

//Search Synonyms is default
if($action != 'Rebuild List' && !array_key_exists('dllist_x',$_POST)) $searchSynonyms = 1;
if ($action == 'Rebuild List' || $action == 'Download') {
    $defaultOverride = 1;
}

$clManager = new ChecklistManager();
if($clid) $clManager->setClid($clid);
elseif($dynClid) $clManager->setDynClid($dynClid);
$clArray = $clManager->getClMetaData();
$activateKey = $KEY_MOD_IS_ACTIVE;
$showDetails = 0;
if(isset($clArray['defaultSettings'])){
	try {
		$defaultArr = json_decode(stripslashes(html_entity_decode($clArray['defaultSettings'])), true, $depth=512, JSON_THROW_ON_ERROR);
	}
	catch (Exception $e){
		$statusStr = $e->getMessage();
		$defaultArr = [];
	}
	$showDetails = $defaultArr['ddetails'];
	if(!$defaultOverride){
		if(array_key_exists('dsynonyms',$defaultArr)) $showSynonyms = $defaultArr['dsynonyms'];
		if(array_key_exists('dcommon',$defaultArr)) $showCommon = $defaultArr['dcommon'];
		if(array_key_exists('dimages',$defaultArr)) $showImages = $defaultArr['dimages'];
		if(array_key_exists('dvoucherimages',$defaultArr)) $limitImagesToVouchers = $defaultArr['dvoucherimages'];
		if(array_key_exists('dvouchers',$defaultArr)) $showVouchers = $defaultArr['dvouchers'];
		if(array_key_exists('dauthors',$defaultArr)) $showAuthors = $defaultArr['dauthors'];
		if(array_key_exists('dsubgenera',$defaultArr)) $showSubgenera = $defaultArr['dsubgenera'];
		if(array_key_exists('dalpha',$defaultArr)) $showAlphaTaxa = $defaultArr['dalpha'];
		if(array_key_exists('dgroupbyrank', $defaultArr)) $groupByRank = strtolower(trim($defaultArr['dgroupbyrank']));

	}
	if(isset($defaultArr['activatekey'])) $activateKey = $defaultArr['activatekey'];
}
if($pid) $clManager->setProj($pid);
elseif(array_key_exists('proj',$_REQUEST) && $_REQUEST['proj']) $pid = $clManager->setProj(filter_var($_REQUEST['proj'], FILTER_SANITIZE_NUMBER_INT));
if($thesFilter) $clManager->setThesFilter($thesFilter);
if($taxonFilter) $clManager->setTaxonFilter($taxonFilter);
$clManager->setLanguage($LANG_TAG);
if($searchCommon){
	$showCommon = 1;
	$clManager->setSearchCommon(true);
}

//Output variable sanitation
$taxonFilter = htmlspecialchars($taxonFilter, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);

if (!empty($taxonFilter)) {
    $showSynonyms = 1;
    $showCommon = 1;
}

if($searchSynonyms) $clManager->setSearchSynonyms(true);
if($showAuthors) $clManager->setShowAuthors(true);
if($showSynonyms) $clManager->setShowSynonyms(true);
if($showCommon) $clManager->setShowCommon(true);
if($showImages) $clManager->setShowImages(true);
if($limitImagesToVouchers) $clManager->setLimitImagesToVouchers(true);
if($showVouchers) $clManager->setShowVouchers(true);
if($showAlphaTaxa) $clManager->setShowAlphaTaxa(true);
if($showSubgenera) $clManager->setShowSubgenera(true);
$clid = $clManager->getClid();
$pid = $clManager->getPid();
if($groupByRank) $clManager->setGroupByRank($groupByRank);

if (array_key_exists('dlcsv', $_POST)) {
	$clManager->downloadChecklistCsv();
	exit();
}
if (array_key_exists('dlpdf', $_POST)) {
	$clManager->downloadChecklistPdf();
	exit();
}
elseif(array_key_exists('printlist_x',$_POST)){
	$printMode = 1;
}
$isEditor = 0;
if($IS_ADMIN || (array_key_exists('ClAdmin',$USER_RIGHTS) && in_array($clid,$USER_RIGHTS['ClAdmin']))){
	$isEditor = 1;
}
elseif(isset($clArray['access']) && $clArray['access'] == 'private-strict'){
	$isEditor = false;
}
if($isEditor && array_key_exists('formsubmit',$_POST)){
	if($_POST['formsubmit'] == 'AddSpecies'){
		$statusStr = $clManager->addNewSpecies($_POST);
	}
}

$taxaArray = $clManager->getTaxaList($pageNumber,($printMode?0:500));

//sort by group
uasort($taxaArray, function ($a, $b) {
    $nameCompare = strcasecmp($a['taxongroup'], $b['taxongroup']);
    if ($nameCompare !== 0) {
        return $nameCompare;
    }
    return strcasecmp($a['sciname'], $b['sciname']);
});


$taxaArray = array_filter($taxaArray, function($item) use ($taxonFilter) {
    $filter = mb_strtolower($taxonFilter);

    // Normalize all fields and search
    $sciname     = isset($item['sciname']) ? mb_strtolower($item['sciname']) : '';
    $syn         = isset($item['syn']) ? mb_strtolower($item['syn']) : '';
    $vern        = isset($item['vern']) ? mb_strtolower($item['vern']) : '';
    $taxongroup  = isset($item['taxongroup']) ? mb_strtolower($item['taxongroup']) : '';

    return (
        strpos($sciname, $filter) !== false ||
        strpos($syn, $filter) !== false ||
        strpos($vern, $filter) !== false ||
        strpos($taxongroup, $filter) !== false
    );
});

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE. ' ' . $LANG['CHECKLIST'] . ': ' . $clManager->getClName(); ?></title>
	<link href="<?= $CSS_BASE_PATH ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<link href="<?= $CSS_BASE_PATH ?>/symbiota/checklists/checklist.css" type="text/css" rel="stylesheet" />
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		<?php
		if($clid) echo 'var clid = '.$clid.';'."\n";
		echo 'var taxaCount = '.count($taxaArray).';'."\n";
		?>
		function changeImageSource(elem){
			let f = document.optionform;
			if(elem.id == "vi_voucher") f.voucherimages.value = "1";
			else f.voucherimages.value = "0";
			f.submit();
		}

		var lang_NAME_BLANK = '<?= $LANG['NAME_BLANK'] ?>';
		var lang_SELECT_TAXON = '<?= $LANG['SELECT_TAXON'] ?>';

		document.addEventListener('DOMContentLoaded', function() {
			const syncCheckbox = (checkboxId, hiddenIds) => {
				const checkbox = document.getElementById(checkboxId);
				checkbox.addEventListener('change', () => {
					const val = checkbox.checked ? '1' : '0';
					hiddenIds.forEach(id => {
						const hiddenInput = document.getElementById(id);
						if (hiddenInput) hiddenInput.value = val;
					});
				});
			};
		
			syncCheckbox('showsynonyms', ['imgform-showsynonyms', 'imgform2-showsynonyms']);
			syncCheckbox('showcommon', ['imgform-showcommon', 'imgform2-showcommon']);
			syncCheckbox('showauthors', ['imgform-showauthors', 'imgform2-showauthors']);
		});
		
		function syncOptionValues() {
			const downloadForm = document.forms['downloadform'];
			const optionForm = document.forms['optionform'];
		
			downloadForm.dl_showsynonyms.value = optionForm.showsynonyms.checked ? "1" : "0";
			downloadForm.dl_showauthors.value = optionForm.showauthors.checked ? "1" : "0";
		
			const showcommon = optionForm.showcommon;
			if (showcommon) {
				downloadForm.dl_showcommon.value = showcommon.checked ? "1" : "0";
			}
		
			const groupBySelect = optionForm.groupbyrank;
			const hiddenGroupByInput = downloadForm.dl_groupbyrank;
			if (groupBySelect && hiddenGroupByInput) {
				hiddenGroupByInput.value = groupBySelect.value;
			}
		}
	</script>
	<script type="text/javascript" src="../../js/symb/checklists.checklist.js"></script>
	
	<style>
		<?php
		if($printMode){
			?>
			body{ background-color:#ffffff;  }
			#innertext{ background-color:#ffffff; }
			.printoff{ display:none; }
			a{ color: currentColor; cursor: none; pointer-events: none; text-decoration: none; }
			<?php
		}
		?>
		#editsppon { display: none; color:green; font-size: 70%; font-weight:bold; padding-bottom: 5px; position: relative; top: -4px; }
		.moredetails{ clear: both }
		.normal-font-weight {
			font-weight: normal;
		}
	</style>
</head>
<body>
	<?php
	$HEADER_URL = '';
	if(isset($clArray['headerurl']) && $clArray['headerurl']) $HEADER_URL = $CLIENT_ROOT.$clArray['headerurl'];
	$displayLeftMenu = (isset($checklists_checklistMenu) ? $checklists_checklistMenu : false);
	if(!$printMode) include($SERVER_ROOT.'/includes/header.php');
	echo '<div class="navpath printoff">';
	if($pid){
		echo '<a href="../../index.php">' . $LANG['NAV_HOME'] . '</a> &gt; ';
		echo '<a href="' . $CLIENT_ROOT . '/projects/index.php?pid=' . $pid . '">';
		echo $clManager->getProjName();
		echo '</a> &gt; ';
		echo '<b>' . $clManager->getClName() . '</b>';
	}
	else{
		echo '<a href="../../index.php">' . $LANG['NAV_HOME'] . '</a> &gt;&gt; ';
		echo '<a href="checklist.php?clid='. $clid . '&pid=' . $pid . ($dynClid ? '&dynclid=' . $dynClid : '') . '"><b>' . $clManager->getClName() . '</b></a>';
	}
	echo '</div>';
	?>
	<!-- This is inner text! -->
	<div id='innertext'>
		<?php
		if(($clid || $dynClid) && $clArray && is_numeric($isEditor)){
			if($clid && $isEditor){
				?>
				<div class="printoff" style="float:right;width:auto;">
					<span style="">
						<a href="checklistadmin.php?clid=<?php echo $clid . '&pid=' . $pid; ?>" style="margin-right:10px;" aria-label="<?php echo $LANG['CHECKLIST_ADMIN']; ?>" title="<?php echo $LANG['CHECKLIST_ADMIN']; ?>">
						<img src="../../images/editadmin.png" style="height:1.3em" alt="<?php echo $LANG['IMG_CHECKLIST_ADMIN']; ?>" /></a>
					</span>
				</div>
				<?php
			}
			?>
			<h1 class="page-heading">
				<?php echo $clManager->getClName(); ?>
			</h1>
			<?php
			echo '<div style="clear:both;"></div>';
			$argStr = '&clid='.$clid.'&dynclid='.$dynClid.($showCommon?'&showcommon=1':'').($showSynonyms?'&showsynonyms=1':'').($showVouchers?'&showvouchers=1':'');
			$argStr .= ($showAuthors?'&showauthors=1':'').($clManager->getThesFilter()?'&thesfilter='.$clManager->getThesFilter():'');
			$argStr .= ($pid?'&pid='.$pid:'').($showImages?'&showimages=1':'').($taxonFilter?'&taxonfilter='.$taxonFilter:'').($limitImagesToVouchers?'&voucherimages=1':'');
			$argStr .= ($searchCommon?'&searchcommon=1':'').($searchSynonyms?'&searchsynonyms=1':'').($showAlphaTaxa?'&showalphataxa=1':'').($showSubgenera?'&showsubgenera=1':'');
			$argStr .= ($defaultOverride?'&defaultoverride=1':'');
			if(($clArray["locality"] || ($clid && ($clArray["latcentroid"] || $clArray["abstract"])) || $clArray["notes"])){
				?>
				<div class="moredetails printoff" style="<?php echo (($showDetails)?'display:none;' : ''); ?>"><a href="#" onclick="toggle('moredetails');return false;"><?php echo $LANG['MOREDETAILS'];?></a></div>
				<div class="moredetails" style="display:<?php echo (($showDetails || $printMode)?'block' : 'none'); ?>;" aria-live="polite">
					<?php
					if($clArray['type'] != 'excludespp'){
						$locStr = $clArray["locality"];
						if($clid && $clArray["latcentroid"]) $locStr .= " (".$clArray["latcentroid"] . ", ".$clArray["longcentroid"] . ")";
						if($locStr){
							echo '<div><span  class="md-label">' . $LANG['LOCALITY'] . ': </span>' . $locStr . '</div>';
						}
					}
					if($clid && $clArray["abstract"]){
						$abstractTitle = $LANG['ABSTRACT'];
						if($clArray['type'] == 'excludespp') $abstractTitle = $LANG['COMMENTS'];
						echo '<div><span  class="md-label">' . $abstractTitle . ': </span>' . $clArray['abstract'] . '</div>';
					}
					if($clArray['notes']){
						echo '<div><span class="md-label">' . $LANG['NOTES'] . ': </span>' . $clArray['notes'] . '</div>';
					}
					?>
				</div>
				<div class="moredetails printoff" style="display:<?php echo (($showDetails)?'block' : 'none'); ?>"><a href="#" onclick="toggle('moredetails');return false;"><?php echo $LANG['LESSDETAILS'];?></a></div>
				<?php
			}
			if($statusStr){
				?>
				<hr />
				<div style="margin:20px;font-weight:bold;color:red;">
					<?php echo $statusStr; ?>
				</div>
				<hr />
				<?php
			}

			$plainStyle = 'border: 1px solid #999; padding: 4px 10px; font-size: 14px; margin-bottom: 0px;';
			$photoStyle = $plainStyle;
			
			if (!$showImages) {
				$plainStyle .= ' background-color: #e0e0e0; color: #000; font-weight: bold;';
				$photoStyle .= ' background-color: #f9f9f9; color: #666; font-weight: normal;';
			} else {
				$plainStyle .= ' background-color: #f9f9f9; color: #666; font-weight: normal;';
				$photoStyle .= ' background-color: #e0e0e0; color: #000; font-weight: bold;';
			}
			?>
			
			<div style="display: flex; align-items: center; gap: 6px; margin-top: .5rem; margin-bottom: .5rem;">
				<div style="font-style: italic; font-size: 14px; line-height: 1;">View</div>
			
				<form method="get" action="checklist.php" style="display:inline;">
					<input type="hidden" name="clid" value="<?= $clid ?>">
					<input type="hidden" name="showimages" value="0">
					<input type="hidden" id="imgform-showsynonyms" name="showsynonyms" value="<?= $showSynonyms ?>">
					<input type="hidden" id="imgform-showcommon" name="showcommon" value="<?= $showCommon ?>">
					<input type="hidden" id="imgform-showauthors" name="showauthors" value="<?= $showAuthors ?>">
					<input type="hidden" name="defaultoverride" value="1">
					<button type="submit" style="<?= $plainStyle ?>">Plain</button>
				</form>
			
				<form method="get" action="checklist.php" style="display:inline;">
					<input type="hidden" name="clid" value="<?= $clid ?>">
					<input type="hidden" name="showimages" value="1">
					<input type="hidden" id="imgform2-showsynonyms" name="showsynonyms" value="<?= $showSynonyms ?>">
					<input type="hidden" id="imgform2-showcommon" name="showcommon" value="<?= $showCommon ?>">
					<input type="hidden" id="imgform2-showauthors" name="showauthors" value="<?= $showAuthors ?>">
					<input type="hidden" name="defaultoverride" value="1">
					<button type="submit" style="<?= $photoStyle ?>">Photo</button>
				</form>
			</div>


			<div style="clear:both">
				<hr/>
			</div>
			<div id="checklist-container" style="display:flex; gap: 0.5rem">
				<div id="img-container">
					<div aria-live="polite" tabindex="0">
						<div style="margin:3px;">
							<?php
							echo '<b>' . $LANG['FAMILIES'] . '</b>: ';
							echo $clManager->getFamilyCount();
							echo '<span class="screen-reader-only">.</span>';
							?>
						</div>
						<div style="margin:3px;">
							<?php
							echo '<b>' . $LANG['GENERA'] . '</b>: ';
							echo $clManager->getGenusCount();
							echo '<span class="screen-reader-only">.</span>';
							?>
						</div>
						<div style="margin:3px;">
							<?php
							echo '<b>' . $LANG['SPECIES'] . '</b>: ';
							echo $clManager->getSpeciesCount();
							echo '<span class="screen-reader-only">.</span>';
							?>
						</div>
						<div style="margin:3px;">
							<?php
							echo '<b>' . $LANG['TOTAL_TAXA'] . '</b>: ';
							echo $clManager->getTaxaCount();
							echo '<span class="screen-reader-only">.</span>';
							?>
						</div>
					</div>
					<hr />
					<div class="printoff">
						<?php
						$taxaLimit = ($showImages?$clManager->getImageLimit():$clManager->getTaxaLimit());
						$pageCount = ceil($clManager->getTaxaCount()/$taxaLimit);
						if(($pageNumber)>$pageCount) $pageNumber = 1;
						echo $LANG['PAGE'] . '<b> ' . ($pageNumber).'</b> ' . $LANG['OF'] . ' <b>' . $pageCount . '</b>: ';
						for($x=1;$x<=$pageCount;$x++){
							if($x>1) echo " | ";
							if(($pageNumber) == $x) echo '<b>';
							else echo '<a href="checklist.php?pagenumber=' . $x . $argStr . '">';
							echo ($x);
							if(($pageNumber) == $x) echo '</b>';
							else echo '</a>';
						}
						?>
					</div>
					<hr />
					<?php
					if($showImages){
						$prevfam = '';
						foreach($taxaArray as $tid => $sppArr){
							$tu = (array_key_exists('tnurl',$sppArr) ? $sppArr['tnurl'] : '');
							$u = (array_key_exists('url',$sppArr) ? $sppArr['url'] : '');
							$imgSrc = ($tu?$tu:$u);
							?>
							<div class="tndiv">
								<div class="tnimg" style="<?php echo ($imgSrc?'' : 'border:1px solid black;'); ?>">
									<?php
									$spUrl = "../../taxa/index.php?taxauthid=1&taxon=$tid&clid=".$clid;
									if($imgSrc){
										$imgSrc = (array_key_exists('IMAGE_DOMAIN', $GLOBALS) && substr($imgSrc, 0, 4) != 'http' ? $GLOBALS['IMAGE_DOMAIN'] : "") . $imgSrc;
										echo "<a href='" . $spUrl . "' target='_blank'>";
										echo "<img src='" . $imgSrc . "' />";
										echo "</a>";
									}
									else{
										?>
										<div style="margin-top:50px;">
											<b><?php echo $LANG['IMAGE'] . '<br/>' . $LANG['NOTY'] . '<br/>' . $LANG['AVAIL']; ?></b>
										</div>
										<?php
									}
									?>
								</div>
								<div style="clear:both">
									<?php
									echo '<a href="' . $spUrl . '" target="_blank">';
									echo '<b>' . $sppArr['sciname'] . '</b>';
									echo '</a>';
									?>
									<div class="editspp printoff" style="float:left;display:none;">
										<?php
										if(isset($sppArr['clid'])){
											$clidArr = explode(',',$sppArr['clid']);
											foreach($clidArr as $id){
												?>
												<a href="#" onclick="return openPopup('clsppeditor.php?tid=<?php echo $tid . '&clid=' . $id; ?>','editorwindow');">
													<img src='../../images/edit.png' style='width:1.3em;' alt="<?php echo $LANG['EDIT_DETAILS']; ?>" title='<?php echo $LANG['EDIT_DETAILS']; ?>' />
												</a>
												<?php
											}
										}
										?>
									</div>
									<?php
									if(array_key_exists('vern',$sppArr)){
										echo "<div style='font-weight:bold;'>" . $sppArr["vern"] . "</div>";
									}
									if(!$showAlphaTaxa){
										$family = $sppArr['family'];
										if($family != $prevfam){
											?>
											<div class="familydiv" id="<?php echo $family; ?>">
												[<?php echo $family; ?>]
											</div>
											<?php
											$prevfam = $family;
										}
									}
									?>
								</div>
							</div>
							<?php
						}
					}
					else{
						//Display taxa
						echo '<div id="taxalist-div">';
						if($clid && $clArray['dynamicProperties']){
							$dynamPropsArr = json_decode($clArray['dynamicProperties'], true);
						}
						$voucherArr = array();
						$externalVoucherArr = array();
						if($showVouchers) {
							$voucherArr = $clManager->getVoucherArr();
							if($clManager->getAssociatedExternalService()) $externalVoucherArr = $clManager->getExternalVoucherArr();
						}
						$prevGroup = '';
						$arrForExternalServiceApi = '';
						foreach($taxaArray as $tid => $sppArr){
							$group = $sppArr['taxongroup'];
							if($group != $prevGroup){
								$famUrl = '../../taxa/index.php?taxauthid=1&taxon=' . strip_tags($group) . '&clid='.$clid;
								//Edit family name display style here
								?>
								<div class="family-div" id="<?php echo strip_tags($group);?>">
									<a href="<?php echo strip_tags($famUrl); ?>" target="_blank" style="color:black;">
										<?php echo strip_tags($group);?>
									</a>
								</div>
								<?php
								$prevGroup = $group;
							}
							echo '<div id="tid-'.$tid.'" class="taxon-container">';
							//Edit species name display style here
							echo '<div class="taxon-div">';
							if(!preg_match('/\ssp\d/',$sppArr["sciname"])) echo '<a href="../../taxa/index.php?taxauthid=1&taxon=' . $tid . '&clid=' . $clid . '" target="_blank">';
							echo '<span class="taxon-span">' . $sppArr['sciname'] . '</span> ';
							if(array_key_exists("author",$sppArr)) echo $sppArr["author"];
							if(!preg_match('/\ssp\d/',$sppArr["sciname"])) echo "</a>";
							if(array_key_exists('vern',$sppArr)){
								echo ' - <span class="vern-span">'.$sppArr['vern'] . '</span>';
							}
							if($clid && $clArray['dynamicsql']){
								?>
								<span class="view-specimen-span printoff">
									<a href="../../collections/list.php?usethes=1&taxontype=2&taxa=<?php echo $tid . "&targetclid=" . $clid . "&targettid=" . $tid;?>" target="_blank" style="text-decoration:none;">
										<img src="../../images/list.png" style="width:1.2em;" title="<?php echo $LANG['VIEW_RELATED']; ?>" />
									</a>
									<?php
									if(isset($dynamPropsArr)){
										$scinameasid = str_replace(" ", "-", $sppArr['sciname']);
										$arrForExternalServiceApi .= ($arrForExternalServiceApi?',' : '') . "'" . $scinameasid . "'";
										echo '<a href="#" target="_blank" id="a-'.$scinameasid.'">';
										echo '<img src="../../images/icons/inaturalist.png" style="width:1.2em;display:none;" title="'. $LANG['LINKTOINAT'] . '" id="i-' . $scinameasid . '" />';
										echo '</a>';
									}
									?>
								</span>
								<?php
							}

							if($isEditor){
								if(isset($sppArr['clid'])){
									$clidArr = explode(',',$sppArr['clid']);
									foreach($clidArr as $id){
										?>
										<span class="editspp" style="display:none;">
											<a href="#" onclick="return openPopup('clsppeditor.php?tid=<?php echo $tid . '&clid=' . $id; ?>','editorwindow');"><img src="../../images/edit.png" style="width:1.2em;" title="<?php echo $LANG['EDIT_DETAILS']; ?> (clid = <?php echo $id; ?>)" /></a>
										</span>
										<?php
									}
									if(in_array($clid, $clidArr) && $showVouchers && $clArray['dynamicsql']){
										?>
										<span class="editspp" style="margin-left:5px;display:none">
											<a href="../../collections/list.php?usethes=1&taxontype=2&taxa=<?php echo $tid . "&targetclid=" . $clid . "&targettid=" . $tid . '&mode=voucher'; ?>" target="_blank">
												<img src="../../images/link.png" style="width:1.2em;" title="<?php echo $LANG['VIEW_RELATED']; ?>" /><span style="font-size:70%">V</span>
											</a>
										</span>
										<?php
									}
								}
							}
							echo "</div>\n";
							if($showSynonyms && isset($sppArr['syn'])){
								echo '<div class="syn-div">[' . $sppArr['syn'] . ']</div>';
							}
							if($showVouchers){
								$voucStr = '';
								if(array_key_exists('notes',$sppArr)) $voucStr .= $sppArr['notes'] . '; ';
								if(array_key_exists($tid,$voucherArr)){
									$voucCnt = 0;
									foreach($voucherArr[$tid] as $occid => $collName){
										if($voucCnt == 4 && !$printMode){
											$voucStr .= '<a href="#" id="morevouch-' . $tid . '" onclick="return toggleVoucherDiv(' . $tid . ');">' . $LANG['MORE'] . '...</a>'.
												'<span id="voucdiv-'.$tid.'" style="display:none;">';
										}
										$voucStr .= '<a href="#" onclick="return openIndividualPopup(' . $occid . ')">' . htmlspecialchars($collName, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>, ';
										$voucCnt++;
									}
									if($voucCnt > 4 && !$printMode) $voucStr .= '</span><a href="#" id="lessvouch-' . $tid . '" style="display:none;" onclick="return toggleVoucherDiv(' . $tid . ');">...' . $LANG['LESS'] . '</a>';
								}
								if(isset($externalVoucherArr[$tid])) {
									foreach($externalVoucherArr[$tid] as $extVouchArr){
										if(!empty($extVouchArr['display'])){
											if(!empty($extVouchArr['url'])) $voucStr .= '<a href="'.$extVouchArr['url'] . '" target="_blank">' . $extVouchArr['display'] . '</a>, ';
											else $voucStr .= $extVouchArr['display'] . ', ';
										}
									}
								}
								$voucStr = trim($voucStr,' ;,');
								if($voucStr) echo '<div class="note-div">' . $voucStr . '</div>';
							}
							echo "</div>\n";
						}
						echo '</div>';
						if(isset($dynamPropsArr['externalservice']) && $dynamPropsArr['externalservice'] == 'inaturalist') {
							echo '<script>const externalProjID = "' . ($dynamPropsArr['externalserviceid']?$dynamPropsArr['externalserviceid'] : '') . '";';
							echo 'const iconictaxon = "' . ($dynamPropsArr['externalserviceiconictaxon']?$dynamPropsArr['externalserviceiconictaxon'] : '') . '";';
							echo 'const checklisttaxa = [' . $arrForExternalServiceApi . '];</script>';
							echo '<script src="../../js/symb/checklists.externalserviceapi.js"></script>';
							?>
							<script>

							</script>
							<?php
						}
					}
					$taxaLimit = ($showImages?$clManager->getImageLimit():$clManager->getTaxaLimit());
					if($clManager->getTaxaCount() > (($pageNumber)*$taxaLimit)){
						echo '<div class="printoff" style="margin:20px;clear:both;">';
						echo '<a href="checklist.php?pagenumber=' . ($pageNumber+1).$argStr . '"> ' . $LANG['DISPLAYNEXT'] . ' ' . $taxaLimit . ' ' . $LANG['TAXA'] . '...</a>';
						echo '</div>';
					}
					if(!$taxaArray) echo "<h1 style='margin:40px;'>" . $LANG['NOTAXA'] . "</h1>";
					?>
				</div>

				<!-- Option box -->
				<div class="printoff" id="cloptiondiv">
					<div style="">
						<form id="optionform" name="optionform" action="checklist.php" method="post">
						<span class="screen-reader-only">
							<a href = "#img-container"><?php echo $LANG['SKIP_LINK']; ?></a>
						</span>
							<fieldset style="background-color:white;padding-bottom:10px;">
								<legend><b>Customize Display</b></legend>
								<!-- Taxon Filter option -->
								<div id="taxonfilterdiv" style="margin-bottom:10px">
									<div>
										<b><?php echo $LANG['SEARCH'];?>:</b><br>
										<input
											type="text"
											id="taxonfilter"
											name="taxonfilter"
											value="<?php echo $taxonFilter; ?>"
											size="40"
											placeholder="Species, higher taxon, synonym or common name..."
											aria-label="<?php echo $LANG['TAXONFILTER']; ?>"
										/>
									</div>
								</div>
								<div class="top-breathing-room-rel">
								</div>
								<b>Display Options:</b><br>
								<div id="showsynonymsdiv" style="display:block;">
									<input name='showsynonyms' id='showsynonyms' type='checkbox' value='1' <?php echo ($showSynonyms ? "checked" : ""); ?> />
									<label for="showsynonyms">Synonyms</label>
								</div>
								<?php
								if($DISPLAY_COMMON_NAMES){
									echo '<div>';
									echo "<input id='showcommon' name='showcommon' type='checkbox' value='1' " . ($showCommon ? "checked" : "") . "/> " . "<label for='showcommon'>Common Names</label>";
									echo '</div>';
								}
								?>
								<div id="showauthorsdiv" style="display:block;">
									<input id='showauthors' name='showauthors' type='checkbox' value='1' <?php echo ($showAuthors ? "checked" : ""); ?>/>
									<label for='showauthors'>Taxon Authorship</label>
								</div>
								<div id="groupbyrankdiv" style="margin:10px 0px 10px 5px;">
									<label for="groupbyrank"><b>Group by Taxon Rank:</b></label><br>
									<select id="groupbyrank" name="groupbyrank">
										<option value="">-- None --</option>
										<option value="order" <?php echo ($groupByRank === 'order' ? 'selected' : ''); ?>>Order</option>
										<option value="family" <?php echo ($groupByRank === 'family' ? 'selected' : ''); ?>>Family</option>
										<option value="genus" <?php echo ($groupByRank === 'genus' ? 'selected' : ''); ?>>Genus</option>
										<option value="taxon" <?php echo ($groupByRank === 'taxon' ? 'selected' : ''); ?>>Taxon</option>
									</select>
								</div>								
								<div style="margin:5px 0px 0px 5px;">
									<div style="float:left;margin-bottom:5px">
										<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
										<input type="hidden" name="dynclid" value="<?php echo $dynClid; ?>" />
										<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
										<input type="hidden" name="defaultoverride" value="1" />
										<input type="hidden" name="voucherimages" value="<?= $limitImagesToVouchers; ?>" >
										<input type="hidden" name="showsubgenera" value="<?= ($showSubgenera?1:0) ?>" >
										<?php if(!$taxonFilter) echo '<input type="hidden" name="pagenumber" value="'.$pageNumber.'" />'; ?>
									<button 
									  name="submitaction" 
									  type="submit" 
									  value="Rebuild List" 
									  onclick="
										const form = document.forms['optionform'];
										const url = 'checklist.php?clid=<?php echo $clid; ?>'
										  + '&taxonfilter=' + encodeURIComponent(form.taxonfilter.value)
										  + '&showauthors=' + (form.showauthors.checked ? '1' : '0')
										  + '&showcommon=' + (form.showcommon && form.showcommon.checked ? '1' : '0')
										  + '&showsynonyms=' + (form.showsynonyms.checked ? '1' : '0')
										  + '&groupbyrank=' + encodeURIComponent(form.groupbyrank.value);
										changeOptionFormAction(url, '_self');
									  "
									>
									  <?php echo $LANG['BUILD_LIST']; ?>
									</button>
									</div>
								</div>
							</fieldset>
						</form>
					<form id="downloadform" name="downloadform" action="checklist.php" method="post">
						<span class="screen-reader-only">
							<a href="#img-container"><?php echo $LANG['SKIP_LINK']; ?></a>
						</span>
						<fieldset style="background-color:white;padding-bottom:10px;">
							<legend><b>Download</b></legend>
							<input type="hidden" name="showsynonyms" id="dl_showsynonyms" value="">
							<input type="hidden" name="showauthors" id="dl_showauthors" value="">
							<input type="hidden" name="showcommon" id="dl_showcommon" value="">
							<input type="hidden" name="groupbyrank" id="dl_groupbyrank" value="">
							<input type="hidden" name="submitaction" value="Download">
							<div style="display: flex; gap: 20px;">
<!--								<div class="icon-button" style="text-align: center; flex: 1;" title="<?php echo $LANG['DOWNLOAD_CHECKLIST']; ?>">
									<input type="image" name="dllist" alt="<?php echo $LANG['IMG_DWNL_LIST']; ?>" src="../../images/dl.png"
										onclick="changeDownloadFormAction('checklist.php?clid=<?php echo $clid . '&pid=' . $pid . '&dynclid=' . $dynClid; ?>','_self');" />
									<div style="font-size: 0.9em;"><?php echo $LANG['DOWNLOAD_CHECKLIST']; ?></div>
								</div>-->
								<!-- Download as CSV -->
								<div class="icon-button" style="text-align: center; flex: 1;">
									<button type="submit"
										name="dlcsv"
										onclick="syncOptionValues(); changeDownloadFormAction('checklist.php?clid=<?php echo $clid . '&format=csv'; ?>','_self');"
										style="all: unset; cursor: pointer; display: inline-block; text-align: center; background: none !important; background-color: transparent !important;"
										onmouseover="this.querySelector('div').style.textDecoration='underline'; this.style.setProperty('background', 'none', 'important'); this.style.setProperty('background-color', 'transparent', 'important');"
										onmouseout="this.querySelector('div').style.textDecoration='none'; this.style.setProperty('background', 'none', 'important'); this.style.setProperty('background-color', 'transparent', 'important');">
										<img src="../../images/file-csv-solid.svg" alt="Download CSV" width="24" height="24" />
										<div style="font-size: 0.9em; text-decoration: none;">Download CSV</div>
									</button>
								</div>
								<!-- Download as PDF -->
								<div class="icon-button" style="text-align: center; flex: 1;">
									<button type="submit"
										name="dlpdf"
										onclick="syncOptionValues(); changeDownloadFormAction('checklist.php?clid=<?php echo $clid . '&format=pdf'; ?>','_self');"
										style="all: unset; cursor: pointer; display: inline-block; text-align: center; background: none !important; background-color: transparent !important;"
										onmouseover="this.querySelector('div').style.textDecoration='underline'; this.style.setProperty('background', 'none', 'important'); this.style.setProperty('background-color', 'transparent', 'important');"
										onmouseout="this.querySelector('div').style.textDecoration='none'; this.style.setProperty('background', 'none', 'important'); this.style.setProperty('background-color', 'transparent', 'important');">
										<img src="../../images/file-pdf-solid.svg" alt="Download PDF" width="24" height="24" />
										<div style="font-size: 0.9em; text-decoration: none;">Download PDF</div>
									</button>
								</div>					
<!--								<div class="icon-button" style="text-align: center; flex: 1;" title="<?php echo $LANG['PRINT_BROWSER']; ?>">
									<input type="image" name="printlist" alt="<?php echo $LANG['IMG_PRINT_LIST']; ?>" src="../../images/print.png"
										onclick="changeDownloadFormAction('checklist.php?clid=<?php echo $clid; ?>','_blank');" />
									<div style="font-size: 0.9em;"><?php echo $LANG['PRINT_BROWSER']; ?></div>
								</div>
								<div class="icon-button" id="wordicondiv" style="text-align: center; flex: 1;<?php echo ($showImages ? 'display:none;' : ''); ?>" title="<?php echo $LANG['EXPORT_DOCX']; ?>">
									<input type="image" name="exportdoc" alt="<?php echo $LANG['IMG_DOCX_EXPORT']; ?>" src="../../images/wordicon.png"
										onclick="changeDownloadFormAction('../../checklists/mswordexport.php?clid=<?php echo $clid; ?>','_self');" />
									<div style="font-size: 0.9em;"><?php echo $LANG['EXPORT_DOCX']; ?></div>
								</div>-->
							</div>
						</fieldset>
					</form>

					</div>
					<?php
					if($clid && $isEditor){
						?>
						<div class="editspp" style="width:250px;display:none;">
							<form id='addspeciesform' action='checklist.php' method='post' name='addspeciesform' onsubmit="return validateAddSpecies(this);">
								<fieldset style='margin:5px 0px 5px 5px;background-color:#FFFFCC;'>
									<legend><b><?php echo $LANG['NEWSPECIES']; ?></b></legend>
									<div>
										<?php echo $LANG['TAXON']; ?>:<br/>
										<input type="text" id="speciestoadd" name="speciestoadd" style="width:174px;" />
										<input type="hidden" id="tid" name="tid" />
									</div>
									<!--
									<div>
										<?php echo $LANG['MORPHOSPECIES']; ?>:<br/>
										<input type="text" name="morphospecies" style="width:122px;" title="" />
									</div>
									-->
									<div>
										<?php echo $LANG['FAMILYOVERRIDE']; ?>:<br/>
										<input type="text" name="familyoverride" style="width:122px;" title="<?php echo $LANG['FAMILYOVERRIDE_DESCR']; ?>" />
									</div>
									<div>
										<?php echo $LANG['HABITAT']; ?>:<br/>
										<input type="text" name="habitat" style="width:170px;" />
									</div>
									<div>
										<?php echo $LANG['ABUNDANCE']; ?>:<br/>
										<input type="text" name="abundance" style="width:145px;" />
									</div>
									<div>
										<?php echo $LANG['NOTES']; ?>:<br/>
										<input type="text" name="notes" style="width:175px;" />
									</div>
									<div style="padding:2px;">
										<?php echo $LANG['INTNOTES']; ?>:<br/>
										<input type="text" name="internalnotes" style="width:126px;" title="<?php echo $LANG['ADMIN_ONLY']; ?>" />
									</div>
									<div>
										<?php echo $LANG['SOURCE']; ?>:<br/>
										<input type="text" name="source" style="width:167px;" />
									</div>
									<div style="margin-top:5px">
										<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
										<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
										<input type="hidden" name="showsynonyms" value="<?php echo $showSynonyms; ?>" />
										<input type="hidden" name="showcommon" value="<?php echo $showCommon; ?>" />
										<input type="hidden" name="showvouchers" value="<?php echo $showVouchers; ?>" />
										<input type="hidden" name="showimages" value="<?php $showImages; ?>" />
										<input type="hidden" name="voucherimages" value="<?php $limitImagesToVouchers; ?>" />
										<input type="hidden" name="showauthors" value="<?php echo $showAuthors; ?>" />
										<input type="hidden" name="thesfilter" value="<?php echo $clManager->getThesFilter(); ?>" />
										<input type="hidden" name="taxonfilter" value="<?php echo $taxonFilter; ?>" />
										<input type="hidden" name="searchcommon" value="<?php echo $searchCommon; ?>" />
										<input type="hidden" name="showalphataxa" value="<?= ($showAlphaTaxa ? 1 : 0) ?>" >
										<input type="hidden" name="showsubgenera" value="<?= ($showSubgenera ? 1 : 0) ?>" >
										<input type="hidden" name="formsubmit" value="AddSpecies" />
										<button name="submitbtn" type="submit"><?php echo $LANG['ADD_SPECIES']; ?></button>
										<hr />
									</div>
									<div style="text-align:center;">
										<a href="tools/checklistloader.php?clid=<?php echo $clid . '&pid=' . $pid ;?>"><?php echo $LANG['BATCH_LOAD_SPREADSHEET'];?></a>
									</div>
								</fieldset>
							</form>
						</div>
						<?php
					}
					if(!$showImages){
						?>
						<div style="text-align:center">
							<?php
							$coordArr = $clManager->getVoucherCoordinates(200);
							if($coordArr){
								$tnUrl = MapSupport::getStaticMap($coordArr);
								$tnWidth = 100;
								if(strpos($tnUrl,$CLIENT_ROOT) === 0) $tnWidth = 50;
								?>
								<div style="text-align:center;font-weight:bold;margin-bottom:5px"><?php echo $LANG['VOUCHER_MAPPING']; ?></div>
								<div style="display: flex; align-items: center; justify-content: center;">
									<div style="float:left;" title="<?php echo $LANG['VOUCHERS_SIMPLE_MAP']; ?>">
										<a href="checklistmap.php?clid=<?php echo $clid . '&thesfilter=' . $thesFilter . '&taxonfilter=' . $taxonFilter; ?>" target="_blank">
											<img src="<?php echo $tnUrl; ?>" style="border:0px;width:<?php echo $tnWidth; ?>px" alt="<?php echo $LANG['IMG_VOUCHERS_SIMPLE_MAP']; ?>" /><br/>
											<?php echo $LANG['SIMPLE_MAP']; ?>
										</a>
									</div>
									<div style="float:left;margin-left:15px" title="<?php echo $LANG['VOUCHERS_DYNAMIC_MAP']; ?>">
										<a href="../../collections/map/index.php?clid=<?php echo $clid . '&cltype=vouchers&taxonfilter=' . $taxonFilter; ?>&db=all&type=1&reset=1">
											<img src="<?php echo $tnUrl; ?>" style="width:<?php echo $tnWidth; ?>px" alt="<?php echo $LANG['IMG_VOUCHERS_DYNAMIC_MAP']; ?>"/><br/>
											<?php echo $LANG['DYNAMIC_MAP']; ?>
										</a>
									</div>
								</div>
								<?php
							}
							if(false && $clArray['dynamicsql']){
								//Temporarily turned off
								?>
								<span style="margin:5px">
									<a href="../../collections/map/index.php?clid=<?php echo $clid . '&cltype=all&taxonfilter=' . $taxonFilter; ?>&db=all&type=1&reset=1">
										<?php
										if($coordArr){
											echo '<img src="../../images/world.png" style="width:30px" title="' . $LANG['OCCUR_DYNAMIC_MAP'] . '" alt="' . $LANG['IMG_OCCUR_DYNAMIC_MAP'] . '" />';
										}
										else{
											$polygonCoordArr = $clManager->getPolygonCoordinates();
											$googleUrl .= '&markers=size:tiny|'.implode('|',$polygonCoordArr);
											echo '<img src="'.$googleUrl.'" style="border:0px;" /><br/>';
										}
										?>
									</a>
								</span>
								<?php
							}
							?>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			<?php
		}
		else{
			echo '<div style="color:red;">';
			if(isset($clArray['access']) && $clArray['access'] == 'private-strict'){
				echo $LANG['IS_PRIVATE'];
			}
			else{
				echo $LANG['CHECKNULL'];
			}
			echo '</div>';
		}
		?>
	</div>
	<?php
	if(!$printMode) include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
<script type="text/javascript" src="../js/checklists.checklist.neon.taxa.js"></script>
</html>
