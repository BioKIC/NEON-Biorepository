<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT . '/classes/utilities/Language.php');
include_once($SERVER_ROOT.'/classes/OccurrenceChecklistManager.php');

Language::load('collections/checklist');

$taxonFilter = array_key_exists('taxonfilter',$_REQUEST) ? filter_var($_REQUEST['taxonfilter'], FILTER_SANITIZE_NUMBER_INT) : '';

$checklistManager = new OccurrenceChecklistManager();
$searchVar = $checklistManager->getQueryTermStr();
$searchVarEncoded = urlencode($searchVar);
//NEON edit start: default output to filter through taxonomic thesaurus
//$taxonFilter = 1;
//NEON edit end
?>
<div>
	<form action="download/index.php" method="post" style="float:right" onsubmit="targetPopup(this)">
		<button class="icon-button" title="<?= $LANG['DOWNLOAD_TITLE']; ?>">
			<svg style="width:1.3em;height:1.3em" alt="Download checklist data" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24">
				<path d="M480-320 280-520l56-58 104 104v-326h80v326l104-104 56 58-200 200ZM240-160q-33 0-56.5-23.5T160-240v-120h80v120h480v-120h80v120q0 33-23.5 56.5T720-160H240Z" />
			</svg>
		</button>
		<input name="searchvar" type="hidden" value="<?=  $searchVar; ?>" />
		<input name="dltype" type="hidden" value="checklist" />
		<input name="taxonFilterCode" type="hidden" value="<?=  $taxonFilter; ?>" />
	</form>
	<?php
	//NEON edit start: comment out following action buttons
	/*
	if($KEY_MOD_IS_ACTIVE){
		?>
		<form action="checklistsymbiota.php" method="post" style="float:right">
			<button class="ui-button ui-widget ui-corner-all" style="margin:5px;padding:5px;cursor: pointer" title="<?= $LANG['OPEN_KEY']; ?>">
				<img src="../images/key.png" style="width:1.3em" />
			</button>
			<input name="searchvar" type="hidden" value="<?=  $searchVar; ?>" />
			<input name="taxonfilter" type="hidden" value="<?=  $taxonFilter; ?>" />
			<input name="interface" type="hidden" value="key" />
		</form>
		<?php
	}
	if($FLORA_MOD_IS_ACTIVE){
		?>
		<form action="checklistsymbiota.php" method="post" style="float:right">
			<button class="ui-button ui-widget ui-corner-all" style="margin:5px;padding:5px;cursor: pointer" title="<?= $LANG['OPEN_CHECKLIST_EXPLORER']; ?>">
				<img src="../images/list.png" style="width:1.3em" />
			</button>
			<input name="searchvar" type="hidden" value="<?=  $searchVar; ?>" />
			<input name="taxonfilter" type="hidden" value="<?=  $taxonFilter; ?>" />
			<input name="interface" type="hidden" value="checklist" />
		</form>
		<?php
	}
	?>
	<div style='margin:10px;float:right;'>
		<form name="changetaxonomy" id="changetaxonomy" action="list.php" method="post">
			<?= $LANG['TAXONOMIC_FILTER']; ?>:
			<select id="taxonfilter" name="taxonfilter" onchange="this.form.submit();">
				<option value="0"><?= $LANG['UNRESOLVED'];?></option>
				<?php
					$taxonAuthList = $checklistManager->getTaxonAuthorityList();
					foreach($taxonAuthList as $taCode => $taValue){
						echo "<option value='".$taCode."' ".($taCode == $taxonFilter?"SELECTED":"").">".$taValue."</option>";
					}
					?>
			</select>
			<input name="tabindex" type="hidden" value="0" />
			<input name="searchvar" type="hidden" value='<?=  $searchVar; ?>' />
		</form>
	</div>
	<?php
	*/
	//NEON edit end
	?>
	<div style="clear:both;"><hr/></div>
		<?php
		$checklistArr = $checklistManager->getChecklist($taxonFilter);
		echo '<div style="font-size:110%;margin-bottom: 10px">'.$LANG['TAXA_COUNT'].': '.$checklistManager->getChecklistTaxaCnt().'</div>';
		$undFamilyArray = Array();
		if(array_key_exists('undefined',$checklistArr)){
			$undFamilyArray = $checklistArr['undefined'];
			unset($checklistArr['undefined']);
		}
		ksort($checklistArr);
		foreach($checklistArr as $family => $sciNameArr){
			ksort($sciNameArr);
			echo '<div style="margin-left:5;margin-top:5;">'.$family.'</div>';
			foreach($sciNameArr as $sciName => $tid){
				echo '<div style="margin-left:20;font-style:italic;">';
				if($tid) echo '<a target="_blank" href="../taxa/index.php?tid=' . htmlspecialchars($tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
				echo $sciName;
				if($tid) echo '</a>';
				echo '</div>';
			}
		}
		if($undFamilyArray){
			echo '<div style="margin-left:5;margin-top:5;">'.$LANG['FAMILY_NOT_DEFINED'].'</div>';
			foreach($undFamilyArray as $sciName => $tid){
				echo '<div style="margin-left:20;font-style:italic;">';
				if($tid) echo '<a target="_blank" href="../taxa/index.php?tid=' . htmlspecialchars($tid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">';
				echo $sciName;
				if($tid) echo '</a>';
				echo '</div>';
			}
		}
	?>
</div>
