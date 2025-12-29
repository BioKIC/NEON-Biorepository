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
		$collections = $_POST['inqcolls'] ?? '';
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
		$additionalresearchers = $_POST['inqadditionalresearcher'] ?? '';
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
	if (!empty($fullfillment) && !empty($notfunded)) $errorMessage[] = 'Unfunded proposal cannot be fulfilled (Create new Inquiry Record).';
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

		$shipmentIDs = isset($_POST['inqshipmentids']) && is_array($_POST['inqshipmentids']) ? $_POST['inqshipmentids'] : [];

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
		if (f.inqexist.value === "") {
			alert("Select whether the request would use existing samples");
			return false;
		}
		if (f.inqfuture.value === "") {
			alert("Select whether the request would use future samples");
			return false;
		}
		if (f.inqnew.value === "") {
			alert("Select whether new samples will be generated");
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
		<div class="navpath">
			<a href="../../index.php">Home</a> &gt;&gt;
			<a href="../../neon/index.php">Management Tools</a> &gt;&gt;
			<a href="../../neon/requests/inquiries.php">Inquiry List</a> &gt;&gt;
			<b>Inquiry Record</b>
		</div>
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
			<div id="tabs" style="margin:0px;">
			    <ul>
					<li><a href="#editinqdiv"><span><?php echo 'Inquiry Info'; ?></span></a></li>
					<li><a href="#editstatus"><span><?php echo 'Status'; ?></span></a></li>
					<li><a href="#samples"><span><?php echo 'Samples'; ?></span></a></li>
					<li><a href="#materialsamples"><span><?php echo 'Material Samples'; ?></span></a></li>
					<li><a href="#shipments"><span><?php echo 'Shipments'; ?></span></a></li>

				</ul>
					<div id="editinqdiv" style="display:<?php echo ($List ); ?>;">
						<form name="editinqform" action="inquiryform.php?id=<?php echo $requestID; ?>" method="post" onsubmit="return verifyInquiryAddForm(this);">
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
										<strong><?php echo 'Primary Contact'; ?>:</strong>
									</span><br />
									<span>
										<select name="inqresearcher" style="width:400px;" aria-label="Researcher">
											<option disabled>-- Select Primary Contact --</option>
											<option disabled>----------------------------</option>

											<?php
											$researcherArr = $inquiryManager->getResearchers();
											foreach($researcherArr as $id => $name){
												$selected = ($id == $pc['researcherID']) ? 'selected' : '';
												echo '<option value="' . $id . '" ' . $selected . '>' . htmlspecialchars($name) . '</option>';
											}
											?>
										</select>
									</span>
									<span>
											<button type="button" class="addResearcherBtn" data-target="primary">Create New Researcher</button>
											</button>									
									</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
								<strong><?php echo 'Additional Researchers (select all)'; ?>:</strong><br />
								<span>
									<select name="inqadditionalresearcher[]" style="width:800px;" multiple aria-label="<?php echo 'Researchers' ?>">
										<option disabled><?php echo 'Select Researchers'; ?></option>
										<option disabled>------------------------------------------</option>
										<?php
											$selectedResearchers = $inquiryManager->getAdditionalResearchersByID($requestID);
											$researcherArr = $inquiryManager->getResearchers();
											foreach ($researcherArr as $id => $name) {
												$selected = array_key_exists($id, $selectedResearchers) ? 'selected' : '';
												echo '<option value="' . $id . '" ' . $selected . '>' . $name . '</option>';
											}
										?>
									</select>
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
       								<strong><?php echo 'Collections of Interest (select all)'; ?>:</strong>
									</span><br />
									<span>
										<select name="inqcolls[]" style="width:800px; height:120px;" multiple aria-label="Collections">
											<option disabled>Select all Collections of Interest</option>
											<option disabled>------------------------------------------</option>
											<?php
												// Get existing collections for this request
												$selectedCollections = array_keys($inquiryManager->getCollectionsByID($requestID));
												// Get all possible collections
												$allCollections = $inquiryManager->getCollections();

												foreach ($allCollections as $id => $name) {
													// Mark as selected if this collection is part of the existing ones
													$selected = in_array($id, $selectedCollections) ? 'selected' : '';
													echo '<option value="' . htmlspecialchars($id) . '" ' . $selected . '>' . htmlspecialchars($name) . '</option>';
												}
											?>
										</select>
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
       								<strong><?php echo 'For Battelle (except IRAD), Contractor, or Biorepo team?'; ?></strong>
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
										<strong><?php echo 'Active/Shipment Date: '?></strong>
										<input name="inqshipdate" type="date" value="<?php echo $inquirydata['activeDate']; ?>" />
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
								Update/Export Sample List
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
								<div style="clear:both;padding-top:8px;float:left;">
									<button type="button" onclick="window.location.href='materialsamplelist.php?id=<?php echo $requestID; ?>'">
									Update/Export Material Sample List
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
			
			<div id="shipments" style="">
			<fieldset>
				<legend><?php echo 'Shipments'; ?></legend>                                    
				<div style="clear:both;padding:10px 0;">
					<div style="float:left;">
						<button type="button" class="addShipmentButton" data-target="primary">Create New Shipment</button>
					</div>
				</div>
				<div style="clear:both;padding-top:6px;float:left;">
					<strong><?php echo 'Shipments (select all)'; ?>:</strong><br />
					<span>
						<form method="post" action="inquiryform.php?id=<?php echo $requestID; ?>">
						<select name="inqshipmentids[]" style="width:800px;" multiple aria-label="<?php echo 'Shipments' ?>">
							<option disabled><?php echo 'Select Shipments'; ?></option>
							<option disabled>------------------------------------------</option>
							<?php
								$selectedShipments = $inquiryManager->getShipmentByID($requestID); // array [id => display]
								$allShipments = $inquiryManager->getShipments(); // array [id => display]

								foreach ($allShipments as $id => $name) {
									$selected = array_key_exists($id, $selectedShipments) ? 'selected' : '';
									echo '<option value="' . $id . '" ' . $selected . '>' . $name . '</option>';
								}
							?>
						</select>
					</span>
					<div style="clear:both;padding-top:8px;float:left;">
						<input name="formsubmit" type="hidden" value="editShipment" />
						<button name="submitButton" type="submit"><?php echo 'Update Shipments' ?></button>
					</form>
					</div>
				</div>
			</fieldset>
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
				<label>Name*:</label>
				<input type="text" name="name" required style="width:100%;"><br><br>

				<label>Institution*:</label>
				<input type="text" name="institution" required style="width:100%;"><br><br>

				<label>Email:</label>
				<input type="text" name="contact_email"  style="width:100%;"><br><br>

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
document.querySelectorAll('.addResearcherBtn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('researcherModal').dataset.source = btn.dataset.target;
        document.getElementById('researcherModal').style.display = 'block';
    });
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
            let primaryDropdown = document.querySelector('select[name="inqresearcher"]');
            let additionalDropdown = document.querySelector('select[name="inqadditionalresearcher[]"]');

            let selectedAdditional = Array.from(additionalDropdown.selectedOptions).map(opt => opt.value);

            let optionPrimary = document.createElement('option');
            optionPrimary.value = data.researcherID;
            optionPrimary.text = data.name + ' (' + data.institution + ')';

            let optionAdditional = optionPrimary.cloneNode(true);

            primaryDropdown.appendChild(optionPrimary);
            additionalDropdown.appendChild(optionAdditional);

            selectedAdditional.forEach(val => {
                let option = additionalDropdown.querySelector(`option[value="${val}"]`);
                if(option) option.selected = true;
            });

            if(document.getElementById('researcherModal').dataset.source === 'primary') {
                primaryDropdown.value = data.researcherID;
            }

            document.getElementById('researcherModal').style.display = 'none';
            document.getElementById('researcherForm').reset();

            alert('Researcher added successfully and can be found at the bottom of the dropdown list!');
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
					alert('Shipment added successfully. Refresh page to find new shipment at the bottom of the list');
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

</style>