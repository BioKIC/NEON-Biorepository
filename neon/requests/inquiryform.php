<?php
include_once('../../config/symbini.php');
include_once('../../neon/classes/InquiriesManager.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');


if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';
$tabIndex = array_key_exists('tabindex',$_REQUEST)?$_REQUEST['tabindex']:0;

if(!is_numeric($tabIndex)) $tabIndex = 0;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $requestID = (int) $_GET['id'];
} else {
    die("Invalid or missing request ID.");
}

if (isset($_GET['status']) && $_GET['status'] == 'success') {
    echo "<div style='color:green; margin:10px 0;'>Successfully updated inquiry record.</div>";
}

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif(array_key_exists('SuperAdmin',$USER_RIGHTS) || array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;

$inquiryManager = new InquiriesManager();
$utilities = new Utilities();

$statusStr = '';

$inquirydata = $inquiryManager->getInquiryDataByID($requestID);
$cm = $inquiryManager->getCMByID($requestID);
$pc = $inquiryManager->getPrimaryContactByID($requestID);
$sampledata = $inquiryManager->getSampleTableByID($requestID);
$materialsampledata = $inquiryManager->getMaterialSampleTableByID($requestID);


if($formSubmit == 'editInquiry' && $isEditor){

	$missing = [];

	$collections = isset($_POST['inqcolls']) && is_array($_POST['inqcolls']) ? $_POST['inqcolls'] : [];
	$additionalresearchers = isset($_POST['additionalresearchers']) && is_array($_POST['additionalresearchers']) ? $_POST['additionalresearchers'] : [];

		$collectionManager = $_POST['inqmanager'] ?? '';
		$researcherID = $_POST['inqresearcher'] ?? '';
		$title = $_POST['inqtitle'] ?? '';
		$collections = [];
		if(!empty($_POST['inqcolls'])){
			$collections = explode(',', $_POST['inqcolls']);
		}
		$field = $_POST['inqfield'] ?? '';
		$aiml = $_POST['inqaiml'] ?? '';
		$secondaryfields = $_POST['inqsecondaryfields'] ?? '';
		$funded = $_POST['inqfunded'] ?? '';
		$fundingsource = $_POST['inqfundingsource'] ?? '';
		$description = $_POST['inqdescription'] ?? '';
		$howfound = $_POST['inqhowfound'] ?? '';
		$dataproduced = $_POST['inqdata'] ?? '';
		$existing = $_POST['inqexist'] ?? '';
		$future = $_POST['inqfuture'] ?? '';
		$new = $_POST['inqnew'] ?? '';
		$additionalresearchers = [];
		if(!empty($_POST['inqadditionalresearcher'])){
			$additionalresearchers = explode(',', $_POST['inqadditionalresearcher']);
		}
		$drivefolder = $_POST['inqdrive'] ?? '';
		$internal = $_POST['inqinternal'] ?? '';
		$outreach = $_POST['inqoutreach'] ?? '';
		$processing = $_POST['inqprocess'] ?? '';


	if (empty($collectionManager)) $missing[] = 'Collection Manager';
	if (empty($researcherID)) $missing[] = 'Researcher';
	if (empty($title)) $missing[] = 'Title';
	if (empty($collections)) $missing[] = 'Collections of Interest';
	if (empty($field)) $missing[] = 'Primary Research Field';
	if (empty($secondaryfields)) $missing[] = 'Secondary Research Fields or Keywords';
	if (empty($funded)) $missing[] = 'Funding Status';
	if (empty($fundingsource)) $missing[] = 'Funding Source';
	if (empty($description)) $missing[] = 'Description';
	if (empty($howfound)) $missing[] = 'How Found Us';
	if (empty($dataproduced)) $missing[] = 'Data Produced';
	if (empty($existing)) $missing[] = 'Existing Samples';
	if (empty($future)) $missing[] = 'Future Samples';
	if (empty($new)) $missing[] = 'Generating Samples';
	if (empty($additionalresearchers)) $missing[] = 'Additional Researchers';
	if (empty($drivefolder)) $missing[] = 'Drive Folder';
	if (empty($aiml)) $missing[] = 'AI/ML Usage';
	if (empty($internal)) $missing[] = 'Battelle/Contractor Request';
	if (empty($outreach)) $missing[] = 'Primarily Outreach/Education Request';
	if (empty($processing)) $missing[] = 'Processing Requirements';

	if (!empty($missing)) {
		$statusStr = '<span style="color:red;">Missing required fields: ' . implode(', ', $missing) . '</span>';
	} else {
		$updatedrequestid = $inquiryManager->editInquiry(
			$requestID,
			$collectionManager,
			$researcherID,
			$title,
			$collections,
			$field,
			$secondaryfields,
			$funded,
			$fundingsource,
			$description,
			$howfound,
			$dataproduced,
			$existing,
			$future,
			$new,
			$additionalresearchers,
			$drivefolder,
			$aiml,
			$internal,
			$outreach,
			$processing,
			$SYMB_UID
		);

		if ($updatedrequestid) {
			header("Location: inquiryform.php?id=" . $updatedrequestid . "&status=success");
			exit();
		} else {
			$statusStr = '<span style="color:red;">Error saving inquiry edits: ' . htmlspecialchars($inquiryManager->getErrorStr()) . '</span>';
		}
		}
}

if($formSubmit == 'editStatus' && $isEditor){

	$errorMessage = [];

		$inquiryDate = $_POST['inqdate'] ?? '';
		$pendingfunding = $_POST['inqpendfunddate'] ?? '';
		$notfunded = $_POST['inqnotfunddate'] ?? '';
		$cut = $_POST['inqcut'] ?? '';
		$pendinglist = $_POST['inqpendlistdate'] ?? '';
		$fulfillment = $_POST['inqpendffdate'] ?? '';
		$active = $_POST['inqshipdate'] ?? '';
		$complete = $_POST['inqcompletedate'] ?? '';
		$followUpType = $_POST['inqfollowuptype'] ?? '';
		$followUpDate = $_POST['inqfollowupdate'] ?? '';
		$followUpNotes = $_POST['inqfollowupnotes'] ?? '';
		$suaLink = $_POST['inqsualink'] ?? '';
		$csrLink = $_POST['inqcsrlink'] ?? '';



	if (
		!(
			empty($pendingfunding) &&
			empty($notfunded) &&
			empty($cut) &&
			empty($pendinglist) &&
			empty($fulfillment) &&
			empty($active) &&
			empty($complete)
		)
		&& empty($inquiryDate)
	) {
		$errorMessage[] = 'Initial Inquiry Date required.';
	}
	if ($cut =="yes" && empty($notfunded)) $errorMessage[] = 'Must indicate that proposal was not funded to select "cut"';
	if (!empty($complete) && empty($active)) $errorMessage[] = 'Active Date required when complete date present.';
	if (!empty($active) && empty($fulfillment)) $errorMessage[] = 'Pending Fulfillment Date required when active date present.';
	if (!(empty($notfunded) && empty($pendinglist)) && empty($pendingfunding)) $errorMessage[] = 'Pending Funding Date required when funded/not funded date present.';
	if (!empty($fulfillment) && empty($pendinglist) && !empty($pendingfunding)) $errorMessage[] = 'Must have a funding date prior to a fulfillment date.';
	if (!empty($fulfillment) && !empty($notfunded)) $errorMessage[] = 'Unfunded proposal cannot be fulfilled (Create new Inquiry Record).';
	if (!empty($notfunded) && !empty($pendinglist)) $errorMessage[] = 'Cannot have both funded and not funded date.';
	if (!empty($complete) && !empty($active)) {
		if (strtotime($complete) <= strtotime($active)) {
			$errorMessage[] = 'Completed Date cannot be before or equal to Active Date';
		}
	}
	if (!empty($fulfillment) && !empty($active)) {
		if (strtotime($active) <= strtotime($fulfillment)) {
			$errorMessage[] = 'Active Date cannot be before or equal to Pending Fulfillment Date';
		}
	}
	if ((!empty($pendingfunding) || !empty($active)) && (empty($followUpDate) || (empty($followUpType)))) {
			$errorMessage[] = 'Follow Up Type and Date are required for active projects and inquiries pending funding.';
	}
	if (!empty($fulfillment) && !empty($pendinglist)) {
		if (strtotime($fulfillment) <= strtotime($pendinglist)) {
			$errorMessage[] = 'Pending Fulfillment Date cannot be before or equal to Funding Date';
		}
	}
	if (!empty($inquiryDate) && !empty($pendingfunding)) {
		if (strtotime($pendingfunding) <= strtotime($inquiryDate)) {
			$errorMessage[] = 'Pending Funding Date cannot be before or equal to Inquiry Date';
		}
	}
	if (!empty($inquiryDate) && !empty($fulfillment)) {
		if (strtotime($fulfillment) <= strtotime($inquiryDate)) {
			$errorMessage[] = 'Pending Fulfillment Date cannot be before or equal to Inquiry Date';
		}
	}
	if (!empty($fulfillment) && empty($suaLink)) {
		$errorMessage[] = 'A sample use agreement is required in order to fulfill a request.';
	}	
	if(!empty($active) && empty($sampledata)) $errorMessage[] = 'Must link samples to request before setting Fulfillment Date.';

	if (!empty($errorMessage)) {
		$statusStr = '<span style="color:red;">' . implode(', ', $errorMessage) . '</span>';
	} else {
		$updatedrequestid = $inquiryManager->editStatus(
			$requestID,
			$inquiryDate,
			$pendingfunding,
			$notfunded,
			$cut,
			$pendinglist,
			$fulfillment,
			$active,
			$complete,
			$followUpType,
			$followUpDate,
			$followUpNotes,
			$suaLink,
			$csrLink,
			$SYMB_UID
		);

		if ($updatedrequestid) {
			header("Location: inquiryform.php?id=" . $updatedrequestid . "&status=success");
			exit();
		} else {
			$statusStr = '<span style="color:red;">Error saving inquiry edits: ' . htmlspecialchars($inquiryManager->getErrorStr()) . '</span>';
		}
		}
}

	if($formSubmit == 'editShipment' && $isEditor){

		$shipmentIDs = [];

		if(!empty($_POST['inqshipmentids'])){
			$shipmentIDs = explode(',', $_POST['inqshipmentids']);
		}

		$updatedRequestID = $inquiryManager->editShipment(
			$shipmentIDs,
			$SYMB_UID,
			$requestID  
		);

		if ($updatedRequestID) {
			header("Location: inquiryform.php?id=" . $updatedRequestID . "&status=success");
			exit();
		} else {
			$statusStr = '<span style="color:red;">Error saving inquiry edits: ' . htmlspecialchars($inquiryManager->getErrorStr()) . '</span>';
		}
	}


?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<title><?php echo 'View and Edit Existing Inquiry Record' ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>

	<script>

	var tabIndex = <?php echo $tabIndex; ?>;
	$(function() {
    $("#tabs").tabs({
        active: tabIndex
    });
	});


	function verifyInquiryEditForm(f) {
		if (f.inqresearcher.value === "") {
			alert("Select Researcher");
			return false;
		}
		if (f.inqmanager.value === "") {
			alert("Select Manager");
			return false;
		}
		if (f.inqtitle.value.trim() === "") {
			alert("Insert title");
			return false;
		}
		if (!f.inqcolls.value || f.inqcolls.value.length === 0) {
			alert("Select collections");
			return false;
		}
		if (f.inqfield.value === "") {
			alert("Select research field");
			return false;
		}
		if (f.inqfunded.value === "") {
			alert("Indicate funding status");
			return false;
		}
		if (f.inqfundingsource.value.trim() === "") {
			alert("Insert funding source");
			return false;
		}
		if (f.inqdescription.value.trim() === "") {
			alert("Insert description");
			return false;
		}
		if (f.inqhowfound.value === "") {
			alert('Select "How Found Us" option');
			return false;
		}
		if (f.inqdata.value.trim() === "") {
			alert("Insert data produced");
			return false;
		}
		if (!f.inqadditionalresearcher.value || f.inqadditionalresearcher.value.length === 0) {
			alert("Select additional researchers");
			return false;
		}
		if (f.inqdrive.value.trim() === "") {
			alert("Input Google Drive Folder");
			return false;
		}
		if (f.inqaiml.value === "") {
			alert("Select whether the request involves AI/ML methods");
			return false;
		}
		if (f.inqinternal.value === "") {
			alert("Select whether the request is for Battelle/Contractor");
			return false;
		}
		if (f.inqprocess.value === "") {
			alert("Select whether the request involves subsampling or additional processing");
			return false;
		}
		if (f.inqoutreach.value === "") {
			alert("Select whether the request is primarily for outreach and/or education?");
			return false;
		}
		return true;
	}

	function verifyInquiryStatusForm(f) {
		if (f.inqdate.value.trim() === "") {
			alert("Insert inquiry date");
			return false;
		}
		return true;
	}

	</script>

	<style>
		fieldset{ padding:10px; }
		fieldset legend{ font-weight:bold }
		.important{ color: red; }
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT . '/includes/header.php');
	?>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= 'View and Edit Inquiry Record' ?></h1>
		<?php
		if($isEditor){
			if($statusStr){
				$colorStr = 'red';
				if(stripos($statusStr,'SUCCESS') !== false) $colorStr = 'green';
				?>
				<hr/>
				<div style="margin:15px;color:<?php echo $colorStr; ?>;">
					<?php echo $statusStr; ?>
				</div>
				<hr/>
				<?php
			}
			?>
			<div id="saveNotice"
					style="display:none;
							background:#fff3cd;
							color:#856404;
							border:1px solid #ffeeba;
							padding:10px;
							margin-bottom:10px;
							border-radius:4px;">

					You have unsaved changes. Click the Update button on the relevant tab to save them.

				</div>
			<div id="tabs" style="margin:0px;">
			    <ul>
					<li><a href="#editinqdiv"><span><?php echo 'Inquiry Info'; ?></span></a></li>
					<li><a href="#editstatus"><span><?php echo 'Status'; ?></span></a></li>
					<li><a href="#samples"><span><?php echo 'Samples'; ?></span></a></li>
					<li><a href="#materialsamples"><span><?php echo 'Material Samples'; ?></span></a></li>
					<li><a href="#shipments"><span><?php echo 'Shipments'; ?></span></a></li>

				</ul>
					<div id="editinqdiv" style="display:<?php echo ($List ); ?>;">
						<form name="editinqform" action="inquiryform.php?id=<?php echo $requestID; ?>" method="post" onsubmit="return verifyInquiryEditForm(this);">
							<fieldset>
								<legend><?php echo 'Basic sample use inquiry record' ?></legend>
								<div style="padding-top:4px;float:left;">
									<span>
										<strong><?php echo 'Last Updated: '; ?></strong> <?php echo $inquirydata['lastUpdated']; ?>
									</span><br />
								</div>
								<div style="clear:both;padding-top:4px;float:left;">
									<span>
										<strong><?php echo 'Manager'; ?>:</strong> 
									</span><br />
									<span>
									<select name="inqmanager" style="width:400px;" aria-label="Select Inquiry Manager">
										<option disabled>-- Select Manager --</option>
										<option disabled>--------------------</option>

										<?php
										$managerArr = $inquiryManager->getManagers();
										foreach($managerArr as $id => $name){
											$selected = ($id == $cm['uid']) ? 'selected' : '';
											echo '<option value="' . htmlspecialchars($id) . '" ' . $selected . '>' . htmlspecialchars($name) . '</option>';
										}
										?>
									</select>
									</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<span>
										<strong><?php echo 'Primary Contact (will need NEON Biorepo account and ORCID)'; ?>:</strong>
										<input type="text"
											id="researcherSearch"
											placeholder="Search researcher..."
											style="width:400px;"
											value="<?php echo htmlspecialchars($pc['name'] . ' (' . $pc['institution'] . ')'); ?>">

										<input type="hidden"
											name="inqresearcher"
											id="inqresearcher"
											value="<?php echo $pc['researcherID']; ?>">
											<button type="button"
													id="editResearcherBtn"
													data-researcherid="<?php echo $pc['researcherID']; ?>">
												Edit Researcher Contact Info
											</button>
										<button type="button"
											class="addResearcherBtn"
											data-target="primary">
											Create New Researcher
										</button>
									</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
								<strong><?php echo 'Additional Researchers (select all)'; ?>:</strong><br />
								<span>
									<input type="text"
										id="additionalResearcherSearch"
										placeholder="Search and add researchers..."
										style="width:400px;">

									<input type="hidden"
										name="inqadditionalresearcher"
										id="inqadditionalresearcher">

									<div id="additionalResearcherList" style="margin-top:10px;"></div>
								</span>
									<span>
											<button type="button" class="addResearcherBtn" data-target="additional">Create New Researcher</button>
											</button>									
									</span>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">

       										<label for="inqtitle"><strong><?php echo 'Inquiry Title'; ?>:</strong></label><br>
											<textarea name="inqtitle" id="inqtitle" style="width:1000px; height:60px;"> <?php echo $inquirydata['title']; ?></textarea>
   								 	</div>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<span>
										<?php
										$selectedCollections = $inquiryManager->getCollectionsByID($requestID);
										?>

										<div style="clear:both;padding-top:6px;float:left;">
											<span>
												<strong>Collections of Interest (select all):</strong>
											</span><br />

											<input type="text"
												id="collectionSearch"
												placeholder="Search and add collections..."
												style="width:400px;">

											<input type="hidden"
												name="inqcolls"
												id="inqcolls">

											<div id="collectionList" style="margin-top:10px;"></div>
										</div>
									</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<span>
										<strong><?php echo 'Primary Research Field: '?> </strong> 
									</span><br />
									<span>
									<select name="inqfield" style="width:400px;" aria-label="<?php echo 'Select Primary Research Field' ?>" >
										<option value=""><?php echo 'Select research field'; ?></option>
										<option value="">------------------------------------------</option>
										<?php
										$fieldArr = $inquiryManager->getFields();
										foreach($fieldArr as $k => $v){
											$selected = ($inquirydata['primaryResearchField'] == $k) ? 'selected' : '';
											echo '<option value="' . htmlspecialchars($k) . '" ' . $selected . '>' . htmlspecialchars($v) . '</option>';
										}
										?>
									</select>
									</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<span>
       								<strong><?php echo 'Uses AI / ML methods?'; ?></strong>
									</span><br />
								<span>
									<select name="inqaiml" style="width:400px;" aria-label="Select AI/ML">
										<option value="">Select AI/ML</option>
										<option value="">------------------------------------------</option>
										<?php
										$aimlArr = array('yes','no');
										foreach($aimlArr as $text){
											$selected = ($inquirydata['usesAIML'] === $text) ? 'selected' : '';
											echo '<option value="' . htmlspecialchars($text) . '" ' . $selected . '>' . htmlspecialchars($text) . '</option>';
										}
										?>
									</select>
								</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<span>
       								<strong><?php echo 'For internal Battelle (except IRAD), Contractor, or Biorepo team? (E.g., to backfill a data product -- no research focus)'; ?></strong>
									</span><br />
								<span>
									<select name="inqinternal" style="width:400px;" aria-label="Select Battelle/Contractor">
										<option value="">Select Battelle/Contractor</option>
										<option value="">------------------------------------------</option>
										<?php
										$intArr = array('yes','no');
										foreach($intArr as $text){
											$selected = ($inquirydata['internal'] === $text) ? 'selected' : '';
											echo '<option value="' . htmlspecialchars($text) . '" ' . $selected . '>' . htmlspecialchars($text) . '</option>';
										}
										?>
									</select>
								</span>
																</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<span>
       								<strong><?php echo 'Primarily for outreach and/or education?'; ?></strong>
									</span><br />
								<span>
									<select name="inqoutreach" style="width:400px;" aria-label="Select Outreach/Education">
										<option value="">Select Outreach/Education</option>
										<option value="">------------------------------------------</option>
										<?php
										$intArr = array('yes','no');
										foreach($intArr as $text){
											$selected = ($inquirydata['outreach'] === $text) ? 'selected' : '';
											echo '<option value="' . htmlspecialchars($text) . '" ' . $selected . '>' . htmlspecialchars($text) . '</option>';
										}
										?>
									</select>
								</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="inqsecondaryfields"><strong><?php echo 'Secondary Research Fields or Keywords (separate multiple with semicolons): ';?></strong> </label><br>
        									<input name="inqsecondaryfields" id="inqsecondaryfields" type="text" style="width:800px;" value="<?php echo $inquirydata['secondaryResearchField']; ?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="inqdata"><strong><?php echo 'Types of Data Produced (separate multiple with semicolons): '?></strong> </label><br>
        									<input name="inqdata" id="inqdata" type="text" style="width:800px;" value="<?php echo $inquirydata['dataProduced'];?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<span>
										<strong><?php echo 'Funding status: '; ?></strong>
									</span><br />
								<span>
									<select name="inqfunded" style="width:400px;" aria-label="Select Funding Status">
										<option value=""><?php echo 'Select funding status'; ?></option>
										<option value="">------------------------------------------</option>
										<?php
										$fundingArr = array(
											'Already externally funded OR Internal/institutional support',
											'Proposal pending funding',
											'Proposal in development',
											'Proposal not funded'
										);
										foreach ($fundingArr as $text) {
											$selected = ($inquirydata['funded'] === $text) ? 'selected' : '';
											echo '<option value="' . htmlspecialchars($text) . '" ' . $selected . '>' . htmlspecialchars($text) . '</option>';
										}
										?>
									</select>
								</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="inqfundingsource"><strong><?php echo 'Funding Source: '; ?></strong></label><br>
        									<input name="inqfundingsource" id="inqfundingsource" type="text" style="width:400px;" value="<?php echo $inquirydata['fundingSource'];?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
										<div class="fieldDiv">
											<label for="inqdescription"><strong><?php echo 'Project Description: '; ?></strong></label><br>
											<textarea name="inqdescription" id="inqdescription" style="width:1000px; height:150px;"> <?php echo $inquirydata['description']; ?></textarea>
										</div>
									</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<span>
										<strong><?php echo 'How did the researchers find us?'; ?></strong><?php $inquirydata['howFoundUs']; ?>
									</span><br />
									<span>
										<select name="inqhowfound" style="width:400px;" aria-label="How did the researchers find us">
											<option disabled>-- Select option --</option>
											<option disabled>-------------------</option>
											<?php
											$howfoundArr = $inquiryManager->getHowFoundUs();
											foreach($howfoundArr as $id => $label){
												$selected = ($label == $inquirydata['howFoundUs']) ? 'selected' : '';
												echo '<option value="' . htmlspecialchars($label) . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
											}
											?>
										</select>
									</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<label for="inqexist"><strong><?php echo 'May use existing samples?' ?></strong></label>
									<input type="hidden" name="inqexist" value="no" />
									<input type="checkbox" name="inqexist" value="yes"
										<?php echo (!empty($inquirydata['existingSamples']) && $inquirydata['existingSamples'] === 'yes') ? 'checked' : ''; ?> />
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<label for="inqfuture"><strong><?php echo 'May use future samples not yet at the Biorepository?' ?></strong></label>
									<input type="hidden" name="inqfuture" value="no" />
									<input type="checkbox" name="inqfuture" value="yes"
										<?php echo (!empty($inquirydata['futureSamples']) && $inquirydata['futureSamples'] === 'yes') ? 'checked' : ''; ?> />
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<label for="inqnew"><strong><?php echo 'May generate new samples?'; ?></strong></label>
									<input type="hidden" name="inqnew" value="no" />
									<input type="checkbox" id="inqnew" name="inqnew" value="yes"
										<?php echo (!empty($inquirydata['generatingSamples']) && $inquirydata['generatingSamples'] === 'yes') ? 'checked' : ''; ?> />
								</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<span>
       								<strong><?php echo 'Involves subsampling or signficant processing?'; ?></strong>
									</span><br />
								<span>
									<select name="inqprocess" style="width:400px;" aria-label="Select additional processing">
										<option value="">Select additional processing</option>
										<option value="">------------------------------------------</option>
										<?php
										$processingArr = array('yes','no');
										foreach($processingArr as $text){
											$selected = ($inquirydata['processing'] === $text) ? 'selected' : '';
											echo '<option value="' . htmlspecialchars($text) . '" ' . $selected . '>' . htmlspecialchars($text) . '</option>';
										}
										?>
									</select>
								</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="inqdrive"><strong><?php echo 'Name of Google Drive Folder for Inquiry Documents'; ?>:</strong></label><br>
        									<input name="inqdrive" id="inqdrive" type="text" style="width:400px;" value="<?php echo $inquirydata['folderName']; ?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:8px;float:left;">
									<input name="formsubmit" type="hidden" value="editInquiry" />
									<button name="submitButton" type="submit"><?php echo 'Update Inquiry' ?></button>
									<input type="hidden" name="tabindex" value="1" />
								</div>
							</fieldset>
						</form>
					</div>
					<div id="editstatus" style="">
						<form name="editingstatus" action="inquiryform.php?id=<?php echo $requestID; ?>" method="post" onsubmit="return verifyInquiryStatusForm(this);">
							<h4>Note that dates must make sense chronologically in the order listed below.</h4>
							<fieldset>
								<legend><?php echo 'Current status' ?></legend>
								<div style="clear:both;padding-top:4px;float:left;">
									<span>
										<strong><?php echo 'Last Updated: '; ?></strong> <?php echo $inquirydata['lastUpdated']; ?>
									</span><br />
								</div>
								<div style="clear:both;padding-top:4px;float:left;">
									<span>
										<strong><?php echo 'Current: '; ?></strong> <?php echo $inquirydata['status']; ?>
									</span><br />
								</div>
							</fieldset>
							<fieldset>
								<legend><?php echo 'Inquiry' ?></legend>
								<div style="clear:both;padding-top:4px;float:left;">
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv">
										<strong><?php echo 'Initial Inquiry Date: '?></strong>
										<input name="inqdate" type="date" value="<?php echo $inquirydata['inquiryDate']; ?>" />
									</div>
								</div>
							</fieldset>
							<fieldset>
								<legend><?php echo 'Funding' ?></legend>
								<div style="clear:both;padding-top:4px;float:left;">
									<div class="fieldDiv">
										<strong><?php echo 'Pending Funding Date: '?></strong>
										<input name="inqpendfunddate" type="date" value="<?php echo $inquirydata['pendingFundingDate']; ?>" />
									</div>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv">
										<strong><?php echo 'Not Funded (or funded but cut) Date: '?></strong>
										<input name="inqnotfunddate" type="date" value="<?php echo $inquirydata['notFundedDate']; ?>" />
									</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<label for="inqcut"><strong><?php echo 'Proposal funded but sample use cut from project?'; ?></strong></label>
									<input type="hidden" name="inqcut" value="no" />
									<input type="checkbox" id="inqcut" name="inqcut" value="yes"
										<?php echo (!empty($inquirydata['cut']) && $inquirydata['cut'] === 'yes') ? 'checked' : ''; ?> />
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv">
										<strong><?php echo 'Funded/Pending Sample List Date: '?></strong>
										<input name="inqpendlistdate" type="date" value="<?php echo $inquirydata['pendingSampleListDate']; ?>" />
									</div>
								</div>
							</fieldset>
							<fieldset>
								<legend><?php echo 'Fulfillment' ?></legend>
								<div style="clear:both;padding-top:4px;float:left;">
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv">
										<strong><?php echo 'Pending Fulfillment Date: '?></strong>
										<input name="inqpendffdate" type="date" value="<?php echo $inquirydata['pendingFulfillmentDate']; ?>" />
									</div>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv">
										<strong><?php echo 'Link to Signed Sample Use Agreement: '?></strong>
										<input name="inqsualink" type="text" style = 'width:400px' value="<?php echo $inquirydata['sampleUseAgreementLink']; ?>" />
									</div>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv">
										<strong><?php echo 'Active/Shipment Date: '?></strong>
										<input name="inqshipdate" type="date" value="<?php echo $inquirydata['activeDate']; ?>" />
									</div>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv">
										<strong><?php echo 'Link to Signed Confirmation of Sample Receipt: '?></strong>
										<input name="inqcsrlink" type="text" style = 'width:400px' value="<?php echo $inquirydata['confirmationOfReceiptLink']; ?>" />
									</div>
								</div>
							</fieldset>
							<fieldset>
								<legend><?php echo 'Completion' ?></legend>
								<div style="clear:both;padding-top:4px;float:left;">
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv">
										<strong><?php echo 'Completed Date: '?></strong>
										<input name="inqcompletedate" type="date" value="<?php echo $inquirydata['completeDate']; ?>" />
									</div>
								</div>
							</fieldset>
							<fieldset>
								<legend style = "color: red"><?php echo 'Follow Up' ?></legend>
								<div style="clear:both;padding-top:4px;float:left;">
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<strong><?php echo 'Follow Up Type: '?></strong>
								<span>
									<select name="inqfollowuptype" style="width:400px;" aria-label="Select Follow Up Type">
										<option value="">Select Follow Up Type</option>
										<option value="">------------------------------------------</option>
										<?php
										$followuptypes = array('funding status','loan recall','data return','publication check','embargo expiration');
										foreach($followuptypes as $text){
											$selected = ($inquirydata['followUpType'] === $text) ? 'selected' : '';
											echo '<option value="' . htmlspecialchars($text) . '" ' . $selected . '>' . htmlspecialchars($text) . '</option>';
										}
										?>
									</select>
								</span>
			
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">

									<div class="fieldDiv">
										<strong><?php echo 'Follow Up Date: '?></strong>
										<input name="inqfollowupdate" type="date" value="<?php echo $inquirydata['followUpDate']; ?>" />
									</div>
								</div>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">

									<div class="fieldDiv">
										<strong><?php echo 'Follow Up Notes: '?></strong>
										<input name="inqfollowupnotes" type="text" style="width:600px; height:60px;" value="<?php echo $inquirydata['followUpNotes']; ?>" />
									</div>
								</div>
							</fieldset>
								<div style="clear:both;padding-top:8px;float:left;">
									<input name="formsubmit" type="hidden" value="editStatus" />
									<button name="submitButton" type="submit"><?php echo 'Update Status' ?></button>
									<input type="hidden" name="tabindex" value="1" />
								</div>
						</form>
					</div>
					<div id="samples" style="">
						<fieldset>
							<legend><?php echo 'Samples'; ?></legend>									
							<div style="clear:both;padding-top:8px;float:left;">
								<button type="button" onclick="window.location.href='samplelist.php?id=<?php echo $requestID; ?>'">
								View and Modify Sample List
								</button>
							</div>
							<div style="clear:both;padding-top:8px;float:left;">
								<?php
								if (!empty($sampledata)) {
									echo '<div class="table-container">';
									$samplesTable = $utilities->htmlTable(
										$sampledata, 
										['occid','status','use type','substance','available','notes','shipment']
									);
									echo $samplesTable;
									echo '</div>';
								}
								?>
							</div>
						</fieldset>
					</div>
					<div id="materialsamples" style="">
							<fieldset>
								<legend><?php echo 'Material Samples'; ?></legend>		
								<h4>Note that material samples cannot be edited until the associated parent samples have been added.</h4>							
								<div style="clear:both;padding-top:8px;float:left;">
									<button type="button" onclick="window.location.href='materialsamplelist.php?id=<?php echo $requestID; ?>'">
									View and Modify Material Sample List
									</button>
								</div>
								<div style="clear:both;padding-top:8px;float:left;">
									<?php
									if (!empty($materialsampledata)) {
										echo '<div class="table-container">';
										$materialSamplesTable = $utilities->htmlTable(
											$materialsampledata, 
											['material sample PK','occid','status','use type','sample type','notes','shipment']
										);
										echo $materialSamplesTable;
										echo '</div>';
									}
									?>
								</div>
							</fieldset>
				</div>
				<div id="saveNotice"
					style="display:none;
							background:#fff3cd;
							color:#856404;
							border:1px solid #ffeeba;
							padding:10px;
							margin-bottom:10px;
							border-radius:4px;">

					You have unsaved changes. Click the Update on the relevant tab to save them.

				</div>
			<div id="shipments" style="">
				<?php
				$selectedShipments = $inquiryManager->getShipmentByID($requestID);
				if ($pc['contactEmail'] && $pc['orcid']) {
				?>
					<fieldset>
						<legend><?php echo 'Shipments'; ?></legend>

						<div style="clear:both;padding:10px 0;">
							<div style="float:left;">
								<button type="button"
										class="addShipmentButton"
										data-target="primary">
									Create New Shipment
								</button>
							</div>
						</div>
						<form method="post"
							action="inquiryform.php?id=<?php echo $requestID; ?>">

							<div style="clear:both;padding-top:6px;float:left;">

								<strong><?php echo 'Shipments (select all)'; ?>:</strong><br />

								<input type="text"
									id="shipmentSearch"
									placeholder="Search and add shipments..."
									style="width:400px;">

								<input type="hidden"
									name="inqshipmentids"
									id="inqshipmentids">
								<br>
								<fieldset>
									<div id="shipmentList"
										style="margin-top:10px;"></div>
								</fieldset>
							</div>

							<div style="clear:both;padding-top:8px;float:left;">
								<input name="formsubmit"
									type="hidden"
									value="editShipment" />

								<button name="submitButton"
										type="submit">
									<?php echo 'Update Shipments' ?>
								</button>
							</div>

						</form>

					</fieldset>
				<?php
				}
				else echo 'Primary contact must have a NEON account and ORCID before shipments can be added';
				?>
			</div>
			</div>

					<div style="clear:both;">&nbsp;</div>
			</div>
			<?php
		}
		else{
			if(!$isEditor) echo '<h2>' . $LANG['NOT_AUTH_LOANS'] . '</h2>';
			else echo '<h2>' . $LANG['UNKNOWN_ERROR'] . '</h2>';
		}
		?>
	</div>
		<div id="researcherModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
		background:rgba(0,0,0,0.6); z-index:9999; justify-content:center; align-items:center;">
		
		<div style="background:#fff; padding:20px; border-radius:6px; width:400px; position:relative;">
			<h2>Add New Researcher</h2>
			<form id="researcherForm">


				<input type="hidden"
					name="researcherID"
					id="researcherID">

				<label>Name*:</label>
				<input type="text" name="name" required style="width:100%;"><br><br>

				<label>Institution*:</label>
				<input type="text" name="institution" required style="width:100%;"><br><br>

				<label>Email:</label>
				<input type="text" name="contactEmail"  style="width:100%;"><br><br>

				<label>ORCID:</label>
				<input type="text" name="orcid"  style="width:100%;"><br><br>

				<label>Address:</label>
				<input type="text" name="address"  style="width:100%;"><br><br>

				<label>Phone:</label>
				<input type="text" name="phone"  style="width:100%;"><br><br>

				<button type="submit">Save</button>
				<button type="button" id="closeResearcherModal">Cancel</button>
			</form>
		</div>
	</div>

	<div id="shipmentModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
		background:rgba(0,0,0,0.6); z-index:9999; justify-content:center; align-items:center;">
		<div style="background:#fff; padding:20px; border-radius:6px; width:400px; position:relative;">
			<h2>Add New Shipment</h2>
			<form id="shipmentform">
				<label><b>Shipped to:</b> (if researcher is not present, go back to request editor to link researcher to the request)</label>
				<select name="researcherid" required style="width:100%; margin-bottom:15px;">
					<option value="">-- Select Researcher --</option>
					<?php 
					$researchers = $inquiryManager->getResearchersByID($requestID); 
					foreach($researchers as $id => $name): ?>
						<option value="<?= htmlspecialchars($id) ?>">
							<?= htmlspecialchars($name) ?>
						</option>
					<?php endforeach; ?>
				</select>
				
				<label><b>Shipment Date:</b></label>
				<input type="date" name="shipdate" required style="width:100%;"><br><br>

				<label><b>Address:</b></label>
				<input type="text" name="address" required style="width:100%;"><br><br>

				<label><b>Shipped By:</b></label>
				<select name="shippedby" required style="width:100%; margin-bottom:15px;">
					<option value="">-- Select Manager --</option>
					<?php foreach($managerArr as $id => $name): ?>
						<option value="<?= htmlspecialchars($id) ?>">
							<?= htmlspecialchars($name) ?>
						</option>
					<?php endforeach; ?>
				</select>
				<button type="submit">Save</button>
				<button type="button" id="closeShipmentModal">Cancel</button>
			</form>
		</div>
	</div>

	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>

	<script>

let hasPendingChanges = false;

function markPendingChanges() {

    hasPendingChanges = true;

    $("#saveNotice").show();
}

function clearPendingChanges() {

    hasPendingChanges = false;

    $("#saveNotice").hide();
}

document.querySelectorAll('.addResearcherBtn').forEach(btn => {
    btn.addEventListener('click', function() {

        $("#researcherID").val("");

        $("#researcherForm")[0].reset();

        $("[name=name]").prop("readonly", false);

        $("#researcherModal h2").text("Add New Researcher");

        document.getElementById('researcherModal').dataset.source = btn.dataset.target;
        document.getElementById('researcherModal').style.display = 'flex';
    });
});

$("#editResearcherBtn").on("click", function() {

    $("#researcherID").val("<?php echo $pc['researcherID']; ?>");

    $("[name=name]").val(<?php echo json_encode($pc['name']); ?>);
    $("[name=institution]").val(<?php echo json_encode($pc['institution']); ?>);
    $("[name=contactEmail]").val(<?php echo json_encode($pc['contactEmail']); ?>);
    $("[name=orcid]").val(<?php echo json_encode($pc['orcid']); ?>);
    $("[name=address]").val(<?php echo json_encode($pc['address']); ?>);
    $("[name=phone]").val(<?php echo json_encode($pc['phone']); ?>);

    $("[name=name]").prop("readonly", true);

    $("#researcherModal h2").text("Edit Researcher");

    $("#researcherModal").css("display", "flex");
});

document.getElementById('closeResearcherModal').addEventListener('click', function(){
    document.getElementById('researcherModal').style.display = 'none';
});

document.getElementById('closeShipmentModal').addEventListener('click', function(){
    document.getElementById('shipmentModal').style.display = 'none';
});

document.getElementById('researcherForm').addEventListener('submit', function(e){
    e.preventDefault();
    let formData = new FormData(this);

    fetch('../../neon/requests/add_researcher.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.success){
			const label = data.name + ' (' + data.institution + ')';

			if(document.getElementById('researcherModal').dataset.source === 'primary') {

				$("#researcherSearch").val(label);
				$("#inqresearcher").val(data.researcherID);
			}

			if(document.getElementById('researcherModal').dataset.source === 'additional') {

				if(!window.selectedResearchers.find(r => r.id == data.researcherID)) {

					window.selectedResearchers.push({
						id: data.researcherID,
						label: label
					});

					renderAdditionalResearchers();
					markPendingChanges();
				}
			}

            document.getElementById('researcherModal').style.display = 'none';
            document.getElementById('researcherForm').reset();

            alert('Researcher added or edited successfully.');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => alert('Request failed'));
});

	document.addEventListener('DOMContentLoaded', function() {
		const modal = document.getElementById('shipmentModal');
		const shipmentForm = document.getElementById('shipmentform');

		if (!shipmentForm) {
			console.error('shipmentForm not found in DOM!');
			return;
		}

		document.querySelectorAll('.addShipmentButton').forEach(btn => {
			btn.addEventListener('click', function() {
				modal.dataset.source = btn.dataset.target || '';
				modal.style.display = 'flex';
			});
		});

		document.getElementById('closeShipmentModal').addEventListener('click', function() {
			modal.style.display = 'none';
			shipmentForm.reset(); 
		});
		
		document.getElementById('closeResearcherModal').addEventListener('click', function() {
			modal.style.display = 'none';
			shipmentForm.reset(); 
		});


		modal.addEventListener('click', function(e) {
			if (e.target === modal) {
				modal.style.display = 'none';
				shipmentForm.reset();
			}
		});

		shipmentForm.addEventListener('submit', function(e) {
			e.preventDefault();
			const formData = new FormData(shipmentForm);

			console.log("Posting shipment form:", ...formData.entries());

			fetch('../../neon/requests/add_shipment.php', {
				method: 'POST',
				body: formData
			})
			.then(res => res.json())
			.then(data => {
				if (data.success) {
					modal.style.display = 'none';
					shipmentForm.reset();
					alert('Shipment added successfully.');
				} else {
					alert('Error: ' + data.message);
				}
			})
			.catch(err => {
				console.error(err);
				alert('Request failed: ' + err);
			});
		});
	});

	window.selectedResearchers = [];

<?php
$selectedResearchers = $inquiryManager->getAdditionalResearchersByID($requestID);

foreach($selectedResearchers as $id => $label){
?>
window.selectedResearchers.push({
    id: "<?php echo $id; ?>",
    label: "<?php echo addslashes($label); ?>"
});
<?php
}
?>

function renderAdditionalResearchers(){

    let html = '';

    window.selectedResearchers.forEach((r, index) => {

        html += `
            <div style="padding:4px 0;">
                ${r.label}
                <button type="button"
                        onclick="removeResearcher(${index})">
                    Remove (not saved until Update)
                </button>
            </div>
        `;
    });

    $("#additionalResearcherList").html(html);

    $("#inqadditionalresearcher").val(
        window.selectedResearchers.map(r => r.id).join(',')
    );
}

function removeResearcher(index){
    window.selectedResearchers.splice(index,1);
    renderAdditionalResearchers();
}

window.selectedCollections = [
<?php
$first = true;

foreach($selectedCollections as $id => $name){

    if(!$first) echo ",";

    echo "{";
    echo "id:" . (int)$id . ",";
    echo "label:" . json_encode($name);
    echo "}";

    $first = false;
}
?>
];

window.selectedShipments = [

<?php

$first = true;

foreach($selectedShipments as $id => $label){

    if(!$first) echo ",";

    echo "{";
    echo "id:" . (int)$id . ",";
    echo "label:" . json_encode($label);
    echo "}";

    $first = false;
}

?>

];

$(document).ready(function(){

    renderAdditionalResearchers();

    // PRIMARY CONTACT
    $("#researcherSearch").autocomplete({
        source: function(request, response){

            $.ajax({
                url: "../../neon/requests/researcher_suggest.php",
                dataType: "json",
                data: {
                    term: request.term
                },
                success: function(data){
                    response(data);
                }
            });

        },
        minLength: 2,
        select: function(event, ui){

            $("#researcherSearch").val(ui.item.label);
            $("#inqresearcher").val(ui.item.resid);

            return false;
        }
    });

	$("#researcherSearch").on('input', function(){
		$("#inqresearcher").val('');
	});

	$(document).ready(function(){

		renderAdditionalResearchers();

		$("#researcherSearch").autocomplete({
			source: function(request, response){

				$.ajax({
					url: "../../neon/requests/researcher_suggest.php",
					dataType: "json",
					data: {
						term: request.term
					},
					success: function(data){
						response(data);
					}
				});

			},

			minLength: 2,

			select: function(event, ui){

				$("#researcherSearch").val(ui.item.label);
				$("#inqresearcher").val(ui.item.resid);

				return false;
			}
		});

		// Clear hidden ID if user types manually
		$("#researcherSearch").on('input', function(){
			$("#inqresearcher").val('');
		});

		// Prevent invalid manual text
		$("#researcherSearch").on('blur', function(){

			if($("#inqresearcher").val() === ''){
				$(this).val('');
			}
		});

		$("#additionalResearcherSearch").autocomplete({

			source: function(request, response){

				$.ajax({
					url: "../../neon/requests/researcher_suggest.php",
					dataType: "json",
					data: {
						term: request.term
					},
					success: function(data){
						response(data);
					}
				});

			},

			minLength: 2,

			select: function(event, ui){

				if(window.selectedResearchers.find(r => r.id == ui.item.resid)){
					$("#additionalResearcherSearch").val('');
					return false;
				}

				window.selectedResearchers.push({
					id: ui.item.resid,
					label: ui.item.label
				});

				renderAdditionalResearchers();

				$("#additionalResearcherSearch").val('');

				return false;
			}
		});

	});

	function renderCollections(){

		let html = '';

		window.selectedCollections.forEach((c, index) => {

			html += `
				<div style="padding:4px 0;">
					${c.label}
					<button type="button"
							onclick="removeCollection(${index})">
						Remove (not saved until Update)
					</button>
				</div>
			`;
		});

		$("#collectionList").html(html);

    $("#inqcolls").val(
			window.selectedCollections.map(c => c.id).join(',')
		);
	}

	window.removeCollection = function(index){

		window.selectedCollections.splice(index, 1);

		renderCollections();
		markPendingChanges()
	};

	renderCollections();

	$("#collectionSearch").autocomplete({

			source: function(request, response){

				$.ajax({
					url: "../../neon/requests/collection_suggest.php",
					dataType: "json",
					data: {
						term: request.term
					},
					success: function(data){
						response(data);
					}
				});

			},

			minLength: 2,

			select: function(event, ui){

				// prevent duplicates
				if(window.selectedCollections.find(c => c.id == ui.item.collid)){

					$("#collectionSearch").val('');

					return false;
				}

				window.selectedCollections.push({
					id: ui.item.collid,
					label: ui.item.label
				});

				renderCollections();

				$("#collectionSearch").val('');

				return false;
			}
		});

		function renderShipments(){

		let html = '';

		window.selectedShipments.forEach((s, index) => {

			html += `
				<div style="padding:4px 0;">
					${s.label}

					<button type="button"
							onclick="removeShipment(${index})">
						     Remove (not saved until Update)
					</button>
				</div>
			`;
		});

		$("#shipmentList").html(html);

		$("#inqshipmentids").val(
			window.selectedShipments.map(s => s.id).join(',')
		);
	}

	window.removeShipment = function(index){

		window.selectedShipments.splice(index, 1);

		renderShipments();
		markPendingChanges();

	};

	renderShipments();


	$("#shipmentSearch").autocomplete({

		source: function(request, response){

			$.ajax({

				url: "../../neon/requests/shipment_suggest.php",

				dataType: "json",

				data: {
					term: request.term
				},

				success: function(data){

					response(data);
				}
			});
		},

		minLength: 2,

		select: function(event, ui){

			// prevent duplicates
			if(window.selectedShipments.find(s => s.id == ui.item.id)){

				$("#shipmentSearch").val('');

				return false;
			}

			window.selectedShipments.push({

				id: ui.item.id,
				label: ui.item.label
			});

			renderShipments();

			$("#shipmentSearch").val('');

			return false;
		}
	});

	$("form").on("submit", function(){

		clearPendingChanges();
	});


});

	window.addEventListener("beforeunload", function (e) {

		if (hasPendingChanges) {

			e.preventDefault();

			e.returnValue = '';
		}
	});

	</script>
</body>
</html>

<style>
    .table-container {
        overflow-x: auto;
    }

    table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 1em;
        font-size: 0.95em;
        background-color: #fff;
    }

    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    th {
        background-color: #f2f2f2;
        cursor: pointer;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    tr:hover {
        background-color: #f1f1f1;
    }

    #filterInput {
        padding: 8px;
        margin-top: 10px;
        width: 100%;
        max-width: 300px;
        font-size: 1em;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
	
	#shipmentModal {
			display: flex;
			position: fixed;
			top: 0; left: 0; width: 100%; height: 100%;
			background: rgba(0,0,0,0.6);
			z-index: 9999;
			justify-content: center;
			align-items: center;
		}
	
	#shipmentModal.show {
			display: flex;
		}

	#researcherModal {
			display: flex;
			position: fixed;
			top: 0; left: 0; width: 100%; height: 100%;
			background: rgba(0,0,0,0.6);
			z-index: 9999;
			justify-content: center;
			align-items: center;
		}
	
	#researcherModal.show {
			display: flex;
		}

</style>