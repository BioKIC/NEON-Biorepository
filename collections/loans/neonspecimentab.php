<?php
#start neon edits
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OccurrenceLoans.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.en.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/editor/includes/determinationtab.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/editor/includes/determinationtab.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/editor/includes/determinationtab.en.php');
header("Content-Type: text/html; charset=" . $CHARSET);
if(!$SYMB_UID) header('Location: ' . $CLIENT_ROOT . '/profile/index.php?refurl=../collections/loans/outgoing.php?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = $_REQUEST['collid'];
$loanId = $_REQUEST['loanid'];
$sortTag = (isset($_REQUEST['sortTag']) ? $_REQUEST['sortTag'] : '');
// start NEON addition
$sampletype = (isset($_REQUEST['sampletype']) ? $_REQUEST['sampletype'] : '');
// end NEON addition

$loanManager = new OccurrenceLoans();

//Sanitation
$collid = $loanManager->sanitizeInt($collid);
$loanId = $loanManager->sanitizeInt($loanId);

// The remote tab must enforce the same editor permissions as outgoing.php.
if(!$SYMB_UID || !$collid || !($IS_ADMIN
	|| (isset($USER_RIGHTS['CollAdmin']) && in_array($collid, $USER_RIGHTS['CollAdmin']))
	|| (isset($USER_RIGHTS['CollEditor']) && in_array($collid, $USER_RIGHTS['CollEditor'])))){
	http_response_code(403);
	exit('You do not have permission to manage this loan.');
}

if($collid) $loanManager->setCollId($collid);
$specList = $loanManager->getSpecimenList($loanId, $sortTag);
?>
<script type="text/javascript">
	function initLoanDetAutocomplete(f){
		$( f.sciname ).autocomplete({
			source: "../editor/rpc/getspeciessuggest.php",
			minLength: 3,
			change: function(event, ui) {
				if(f.sciname.value){
					pauseSubmit = true;
					verifyLoanDetSciName(f);
				}
				else{
					f.scientificnameauthorship.value = "";
					f.family.value = "";
					f.tidtoadd.value = "";
				}
			}
		});
	}

	function verifyLoanDetSciName(f){
		$.ajax({
			type: "POST",
			url: "../editor/rpc/verifysciname.php",
			dataType: "json",
			data: { term: f.sciname.value }
		}).done(function( data ) {
			if(data){
				f.scientificnameauthorship.value = data.author;
				f.family.value = data.family;
				f.tidtoadd.value = data.tid;
			}
			else{
	            alert("<?php echo $LANG['TAXON_NOT_FOUND']; ?>");
				f.scientificnameauthorship.value = "";
				f.family.value = "";
				f.tidtoadd.value = "";
			}
		});
	}

	function verifyLoanDet(){
		if(document.getElementById('dafsciname').value == ""){
			alert("<?php echo $LANG['SCINAME_NEEDS_VALUE']; ?>");
			return false;
		}
		if(document.getElementById('identifiedby').value == ""){
			alert("<?php echo $LANG['DET_NEEDS_VALUE']; ?>");
			return false;
		}
		if(document.getElementById('dateidentified').value == ""){
			alert("<?php echo $LANG['DET_DATE_NEEDS_VALUE']; ?>");
			return false;
		}
		return true;
	}

	function openCheckinPopup(loanId, occid, collid){
		urlStr = "specnoteseditor.php?loanid="+loanId+"&occid="+occid+"&collid="+collid;
		newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=0,resizable=1,width=800,height=300,left=60,top=250');
		window.name = "parentWin";
		if(newWindow.opener == null) newWindow.opener = self;
		return false;
	}


</script>
<link rel="stylesheet" href="../../css/loan-specimens.css" />
<div id="outloanspecdiv">
	<div class="loan-heading">
		<h2>Loan specimens</h2>
		<a class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-sizeMedium MuiButton-colorPrimary" href="outgoing.php?formsubmit=exportSpecimenList&amp;loanid=<?= $loanId ?>&amp;collid=<?= $collid ?>">Export specimens</a>
	</div>
	<section class="loan-entry">
		<h3>Manage loan specimens</h3>
		<form id="loan-entry-form" name="batchaddform" action="outgoing.php" method="post">
            <div class="loan-fields">
				<label for="loan-process-mode">Action
					<select id="loan-process-mode" name="processmode"><option value="link">Add to this loan</option><option value="checkin">Mark as returned</option><option value="remove">Remove from loan</option><option value="determination">Add determination</option></select>
				</label>
				<label for="loan-target">Identifier to match
					<select id="loan-target" name="targetidentifier"><option value="catnum">Catalog number</option><option value="other">Other identifier</option><option value="allid">Any identifier</option></select>
				</label>
				<label for="loan-sample-type">Sample type
					<select id="loan-sample-type" name="sampletype"><option value="">All sample types</option>
					<?php foreach($loanManager->getSampleTypes() as $row){ ?>
						<option value="<?= (int)$row['collID'] ?>" <?= $sampletype == $row['collID'] ? 'selected' : '' ?>><?= htmlspecialchars($row['collectionName'], ENT_QUOTES) ?></option>
					<?php } ?>
					</select>
				</label>
			</div>
			<label for="loan-input-mode">Input method</label>
            <select id="loan-input-mode"><option value="paste">Paste a list</option><option value="scan">Scan continuously</option></select>
            <div id="loan-scan-panel" style="display:none">
                <label for="loan-scan-input">Scan a barcode (Enter to process)</label>
                <input id="loan-scan-input" type="text" autocomplete="off" />
                <p>Scanning is available for adding, returning, or removing specimens. Determinations use pasted lists.</p>
                <ol id="loan-scan-history" aria-label="Recent scan results" role="status" aria-live="polite"></ol>
                <button id="loan-scan-refresh" type="button">Finish scanning and refresh table</button>
            </div>
			<label for="loan-identifiers">Specimen identifiers (one per line, comma, or semicolon)</label>
			<textarea id="loan-identifiers" name="catalogNumbers" rows="2" required></textarea>
			<p id="loan-match-result" role="status" aria-live="polite"></p>
			<input name="collid" type="hidden" value="<?= $collid ?>" />
			<input name="loanid" type="hidden" value="<?= $loanId ?>" />
			<input name="tabindex" type="hidden" value="1" />
			<button id="loan-process-button" name="formsubmit" type="submit" value="batchProcessSpecimens">Add to loan</button>
		</form>
	</section>
	<div id="speclist-div" style="<?php echo (!$specList?'display:none;':''); ?>">
		<form name="speceditform" action="outgoing.php" method="post" id="loan-specimen-form" >
			<div id="newdet-div" style="display:none;">
				<fieldset>
					<legend><b><?php echo $LANG['ADD_A_DET']; ?></b></legend>
					<div style='margin:3px;'>
						<b><?php echo $LANG['ID_QUALIFIER']; ?>:</b>
						<input type="text" name="identificationqualifier" title="<?php echo $LANG['ID_QUALIFIER_EX']; ?>" />
					</div>
					<div style='margin:3px;'>
						<b><?php echo $LANG['SCI_NAME']; ?>:</b>
						<input type="text" id="dafsciname" name="sciname" style="background-color:lightyellow;width:350px;" onfocus="initLoanDetAutocomplete(this.form)" />
						<input type="hidden" id="daftidtoadd" name="tidtoadd" value="" />
						<input type="hidden" name="family" value="" />
					</div>
					<div style='margin:3px;'>
						<b><?php echo $LANG['AUTHOR']; ?>:</b>
						<input type="text" name="scientificnameauthorship" style="width:200px;" />
					</div>
					<div style='margin:3px;'>
						<b><?php echo $LANG['CONFIDENCE_IN_DET']; ?>:</b>
						<select name="confidenceranking">
							<option value="8"><?php echo $LANG['HIGH']; ?></option>
							<option value="5" selected><?php echo $LANG['MEDIUM']; ?></option>
							<option value="2"><?php echo $LANG['LOW']; ?></option>
						</select>
					</div>
					<div style='margin:3px;'>
						<b><?php echo $LANG['DETERMINER']; ?>:</b>
						<input type="text" name="identifiedby" id="identifiedby" style="background-color:lightyellow;width:200px;" />
					</div>
					<div style='margin:3px;'>
						<b><?php echo $LANG['DATE']; ?>:</b>
						<input type="date" name="dateidentified" id="dateidentified" />
					</div>
					<div style='margin:3px;'>
						<b><?php echo $LANG['REFERENCE']; ?>:</b>
						<input type="text" name="identificationreferences" style="width:350px;" />
					</div>
					<div style='margin:3px;'>
						<b><?php echo $LANG['NOTES']; ?>:</b>
						<input type="text" name="identificationremarks" style="width:350px;" />
						<input type="hidden" name="taxonremarks" value="" />
					</div>
					<div style='margin:3px;'>
						<input type="checkbox" name="makecurrent" value="1" /> <?php echo $LANG['MAKE_THIS_CURRENT']; ?>
					</div>
					<div style='margin:3px;'>
						<input type="checkbox" name="printqueue" value="1" /> <?php echo $LANG['ADD_TO_PRINT']; ?>
					</div>
					<div style='margin:15px;'>
						<div style="float:left;">
							<button type="submit" name="formsubmit" value="addDeterminations" onclick="return verifyLoanDet();"><?php echo $LANG['ADD_NEW_DET']; ?></button>
						</div>
					</div>
				</fieldset>
			</div>
			<input name="collid" type="hidden" value="<?= $collid ?>" />
			<input name="loanid" type="hidden" value="<?= $loanId ?>" />
			<input name="tabindex" type="hidden" value="1" />
			<div id="loan-action-inputs"></div>
			<!--neon edit-->

			<table id="loanSpecimenTable" class="display" style="font-size:12px;"> 
				<thead> <!--neon edit-->
					<tr>
						<th>&nbsp;</th>
						<th><?php echo $LANG['CATNO']; ?>
						</th>
						<th><?php echo $LANG['DETAILS']; ?></th>
						<th><?php echo $LANG['DATE_RETURNED']; ?></th>
					</tr>
				</thead> <!--neon edit-->
				<tbody> <!--neon edit-->
					<?php
					$specSortArr = $loanManager->getSpecimenSortArr();
					foreach($specSortArr as $occid => $identifier){
						$specArr = $specList[$occid];
						?>
						<tr data-occid="<?= (int)$occid ?>" data-catalog="<?= htmlspecialchars($specArr['catalognumber'] ?? '', ENT_QUOTES) ?>" data-other-identifiers="<?= htmlspecialchars(json_encode(array_values(array_unique($specArr['identifiermatches'] ?? array()))), ENT_QUOTES) ?>" data-sample-type="<?= (int)$specArr['collid'] ?>" data-returned="<?= $specArr['returndate'] ? '1' : '0' ?>">
							<td>
								<div>
									<a href="#" onclick="openIndPopup(<?php echo $occid; ?>); return false;"><?php echo $occid; ?></a><br/>								</div>
								<div>
									<a href="#" onclick="openEditorPopup(<?php echo $occid; ?>); return false;"><img class="icon-img" src="../../images/edit.png" title="<?php echo $LANG['OPEN_OCC_EDITOR']; ?>" /></a>
								</div>
							</td>
							<td>
								<?php
								if($specArr['catalognumber']) echo '<div>' . $specArr['catalognumber'] . '</div>';
								if(isset($specArr['othercatalognumbers'])) echo '<div>' . implode('<br/>',$specArr['othercatalognumbers']) . '</div>';
								if($specArr['collid'] != $collid) echo '<div style="color:orange">external</div>';
								?>
							</td>
							<td>
								<?php
								if($specArr['sciname']) echo '<i>' . $specArr['sciname'] . '</i>; ';
								$loc = $specArr['locality'];
								if(strlen($loc) > 500) $loc = substr($loc,400);
								if($specArr['collector']) echo $specArr['collector'] . '; ';
								echo $loc;
								if($specArr['notes']) echo '<div class="notesDiv"><b>Notes:</b> ' . $specArr['notes'],'</div>';
								?>
							</td>
							<td><?php
							echo '<div style="float:right"><a href="#" onclick="openCheckinPopup(' . $loanId . ',' . $occid . ',' . $collid . ');return false"><img class="icon-img" src="../../images/edit.png" title="' . $LANG['EDIT_NOTES'] . '" /></a></div>';
							echo $specArr['returndate'];
							?></td>
						</tr>
						<?php
					}
						?>
				</tbody> <!--neon edit-->
			</table>
		</form>
	</div>
	<div id="nospecdiv" style="margin:20px;font-size:120%;<?php echo ($specList?'display:none;':''); ?>"><?php echo $LANG['NO_SPECS_REGISTERED']; ?></div>
</div>

<script src="../../js/symb/collections.loan-specimens.js"></script>

<?php
#end neon edits
?>
