<?php
include_once('../../config/symbini.php');
include_once('../../neon/requests/list/InquiriesManager.php');

if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';
$tabIndex = array_key_exists('tabindex',$_REQUEST)?$_REQUEST['tabindex']:0;

if(!is_numeric($tabIndex)) $tabIndex = 0;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $request_id = (int) $_GET['id'];
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

$statusStr = '';

$inquirydata = $inquiryManager->getInquiryDataByID($request_id);
$cm = $inquiryManager->getCMByID($request_id);
$pc = $inquiryManager->getPrimaryContactByID($request_id);

if($formSubmit == 'editInquiry' && $isEditor){

// Initialize missing fields array
$missing = [];

// Normalize arrays from form
$collections = isset($_POST['inqcolls']) && is_array($_POST['inqcolls']) ? $_POST['inqcolls'] : [];
$additionalresearchers = isset($_POST['additionalresearchers']) && is_array($_POST['additionalresearchers']) ? $_POST['additionalresearchers'] : [];

// Trim all input values to catch empty strings
    $collection_manager = $_POST['inqmanager'] ?? '';
    $researcher_id = $_POST['inqresearcher'] ?? '';
    $inquiry_date = $_POST['inqdate'] ?? '';
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
	$pendingfunding = $_POST['inqpendfunddate'] ?? '';
	$notfunded = $_POST['inqnotfunddate'] ?? '';
	$pendinglist = $_POST['inqpendlistdate'] ?? '';
	$fulfillment = $_POST['inqpendffdate'] ?? '';
	$active = $_POST['inqshipdate'] ?? '';
	$complete = $_POST['inqcompletedate'] ?? '';


// Check required fields
if (empty($collection_manager)) $missing[] = 'Collection Manager';
if (empty($researcher_id)) $missing[] = 'Researcher';
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

// Display missing fields or save the inquiry
if (!empty($missing)) {
    $statusStr = '<span style="color:red;">Missing required fields: ' . implode(', ', $missing) . '</span>';
} else {
    $updatedrequestid = $inquiryManager->editInquiry(
        $request_id,
        $collection_manager,
        $researcher_id,
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
		$SYMB_UID
    );

	if ($updatedrequestid) {
		header("Location: inquiryform.php?id=" . $updatedrequestid . "&status=success");
		exit();
	} else {
        $statusStr = '<span style="color:red;">Error saving inquiry edits: ' . $inquiryManager->getError() . '</span>';
    }
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
			<a href="../../neon/requests/list/inquiries.php">Inquiry List</a> &gt;&gt;
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
					<li><a href="#editinqdiv"><span><?php echo 'Record Info'; ?></span></a></li>
					<li><a href="#editstatus"><span><?php echo 'Status'; ?></span></a></li>
					<li><a href="#samples"><span><?php echo 'Samples'; ?></span></a></li>
					<li><a href="#materialsamples"><span><?php echo 'Material Samples'; ?></span></a></li>
					<li><a href="#shipments"><span><?php echo 'Shipments'; ?></span></a></li>
				</ul>
					<div id="editinqdiv" style="display:<?php echo ($List ); ?>;">
						<form name="editinqform" action="inquiryform.php?id=<?php echo $request_id; ?>" method="post" onsubmit="return verifyInquiryAddForm(this);">
							<fieldset>
								<legend><?php echo 'Basic sample use inquiry record' ?></legend>
								<div style="padding-top:4px;float:left;">
									<span>
										<strong><?php echo 'Last Updated: '; ?></strong> <?php echo $inquirydata['last_updated']; ?>
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
												$selected = ($id == $pc['researcher_id']) ? 'selected' : '';
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
											$selectedResearchers = $inquiryManager->getAdditionalResearchersByID($request_id);
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
												$selectedCollections = array_keys($inquiryManager->getCollectionsByID($request_id));
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
											$selected = ($inquirydata['primary_research_field'] == $k) ? 'selected' : '';
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
											$selected = ($inquirydata['uses_aiml'] === $text) ? 'selected' : '';
											echo '<option value="' . htmlspecialchars($text) . '" ' . $selected . '>' . htmlspecialchars($text) . '</option>';
										}
										?>
									</select>
								</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<span>
       								<strong><?php echo 'For Battelle (except IRAD) or Contractor?'; ?></strong>
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
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="inqsecondaryfields"><strong><?php echo 'Secondary Research Fields or Keywords (separate multiple with semicolons): ';?></strong> </label><br>
        									<input name="inqsecondaryfields" id="inqsecondaryfields" type="text" style="width:800px;" value="<?php echo $inquirydata['secondary_research_field']; ?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="inqdata"><strong><?php echo 'Types of Data Produced (separate multiple with semicolons): '?></strong> </label><br>
        									<input name="inqdata" id="inqdata" type="text" style="width:800px;" value="<?php echo $inquirydata['data_produced'];?>" />
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
											'Proposal in preparation',
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
        									<input name="inqfundingsource" id="inqfundingsource" type="text" style="width:400px;" value="<?php echo $inquirydata['funding_source'];?>" />
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
										<strong><?php echo 'How did the researchers find us?'; ?></strong><?php $inquirydata['how_found_us']; ?>
									</span><br />
									<span>
										<select name="inqhowfound" style="width:400px;" aria-label="How did the researchers find us">
											<option disabled>-- Select option --</option>
											<option disabled>-------------------</option>
											<?php
											$howfoundArr = $inquiryManager->getHowFoundUs();
											foreach($howfoundArr as $id => $label){
												$selected = ($label == $inquirydata['how_found_us']) ? 'selected' : '';
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
										<?php echo (!empty($inquirydata['existing_samples']) && $inquirydata['existing_samples'] === 'yes') ? 'checked' : ''; ?> />
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<label for="inqfuture"><strong><?php echo 'May use future samples not yet at the Biorepository?' ?></strong></label>
									<input type="hidden" name="inqfuture" value="no" />
									<input type="checkbox" name="inqfuture" value="yes"
										<?php echo (!empty($inquirydata['future_samples']) && $inquirydata['future_samples'] === 'yes') ? 'checked' : ''; ?> />
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<label for="inqnew"><strong><?php echo 'May generate new samples?'; ?></strong></label>
									<input type="hidden" name="inqnew" value="no" />
									<input type="checkbox" id="inqnew" name="inqnew" value="yes"
										<?php echo (!empty($inquirydata['generating_samples']) && $inquirydata['generating_samples'] === 'yes') ? 'checked' : ''; ?> />
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="inqdrive"><strong><?php echo 'Name of Google Drive Folder for Inquiry Documents'; ?>:</strong></label><br>
        									<input name="inqdrive" id="inqdrive" type="text" style="width:400px;" value="<?php echo $inquirydata['folder_name']; ?>" />
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
						<form name="editingstatus" action="inquiryform.php?id=<?php echo $request_id; ?>" method="post" onsubmit="return verifyInquiryStatusForm(this);">
							<fieldset>
								<legend><?php echo 'Current status' ?></legend>
								<div style="clear:both;padding-top:4px;float:left;">
									<span>
										<strong><?php echo 'Last Updated: '; ?></strong> <?php echo $inquirydata['last_updated']; ?>
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
										<input name="inqdate" type="date" value="<?php echo $inquirydata['inquiry_date']; ?>" />
									</div>
								</div>
							</fieldset>
							<fieldset>
								<legend><?php echo 'Funding' ?></legend>
								<div style="clear:both;padding-top:4px;float:left;">
									<div class="fieldDiv">
										<strong><?php echo 'Pending Funding Date: '?></strong>
										<input name="inqpendfunddate" type="date" value="<?php echo $inquirydata['pending_funding_date']; ?>" />
									</div>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv">
										<strong><?php echo 'Not Funded (or funded but cut) Date: '?></strong>
										<input name="inqnotfunddate" type="date" value="<?php echo $inquirydata['not_funded_date']; ?>" />
									</div>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv">
										<strong><?php echo 'Funded/Pending Sample List Date: '?></strong>
										<input name="inqpendlistdate" type="date" value="<?php echo $inquirydata['pending_sample_list_date']; ?>" />
									</div>
								</div>
							</fieldset>
							<fieldset>
								<legend><?php echo 'Fulfillment' ?></legend>
								<div style="clear:both;padding-top:4px;float:left;">
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv">
										<strong><?php echo 'Pending Fulfillment Date: '?></strong>
										<input name="inqpendffdate" type="date" value="<?php echo $inquirydata['pending_fulfillment_date']; ?>" />
									</div>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv">
										<strong><?php echo 'Active/Shipment Date: '?></strong>
										<input name="inqshipdate" type="date" value="<?php echo $inquirydata['active_date']; ?>" />
									</div>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv">
										<strong><?php echo 'Completed Date: '?></strong>
										<input name="inqcompletedate" type="date" value="<?php echo $inquirydata['complete_date']; ?>" />
									</div>
								</div>
							</fieldset>
								<div style="clear:both;padding-top:8px;float:left;">
									<input name="formsubmit" type="hidden" value="update status" />
									<button name="submitButton" type="submit"><?php echo 'Update Status' ?></button>
									<input type="hidden" name="tabindex" value="1" />
								</div>
						</form>
					</div>
					<div id="samples" style="">
						<form name="linksamples" action="inquiryform.php?id=<?php echo $request_id; ?>" method="post" onsubmit="return verifyInquirySamplesForm(this);">
							<fieldset>
								<legend><?php echo 'Samples' ?></legend>
								<div style="clear:both;padding-top:4px;float:left;">
									<span>
										<strong><?php echo 'Last Updated: '; ?></strong> <?php echo $inquirydata['last_updated']; ?>
									</span><br />
								</div>
								<div style="clear:both;padding-top:4px;float:left;">
									<span>
										<strong><?php echo 'Current: '; ?></strong> <?php echo $inquirydata['status']; ?>
									</span><br />
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv">
										<strong><?php echo 'Initial Inquiry Date: '?></strong>
										<input name="inqdate" type="date" value="<?php echo $inquirydata['inquiry_date']; ?>" />
									</div>
								</div>
								<div style="clear:both;padding-top:8px;float:left;">
									<input name="formsubmit" type="hidden" value="update samples" />
									<button name="submitButton" type="submit"><?php echo 'Link/Unlink Samples' ?></button>
									<input type="hidden" name="tabindex" value="1" />
								</div>
							</fieldset>
						</form>
					</div>
					<div id="materialsamples" style="">
						<form name="linksamples" action="inquiryform.php?id=<?php echo $request_id; ?>" method="post" onsubmit="return verifyInquiryMaterialSamplesForm(this);">
							<fieldset>
								<legend><?php echo 'Material Samples' ?></legend>
								<div style="clear:both;padding-top:4px;float:left;">
									<span>
										<strong><?php echo 'Last Updated: '; ?></strong> <?php echo $inquirydata['last_updated']; ?>
									</span><br />
								</div>
								<div style="clear:both;padding-top:4px;float:left;">
									<span>
										<strong><?php echo 'Current: '; ?></strong> <?php echo $inquirydata['status']; ?>
									</span><br />
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv">
										<strong><?php echo 'Initial Inquiry Date: '?></strong>
										<input name="inqdate" type="date" value="<?php echo $inquirydata['inquiry_date']; ?>" />
									</div>
								</div>
								<div style="clear:both;padding-top:8px;float:left;">
									<input name="formsubmit" type="hidden" value="update material samples" />
									<button name="submitButton" type="submit"><?php echo 'Link/Unlink Material Samples' ?></button>
									<input type="hidden" name="tabindex" value="1" />
								</div>
							</fieldset>
						</form>
					</div>
					<div id="shipments" style="">
						<form name="updateshipments" action="inquiryform.php?id=<?php echo $request_id; ?>" method="post" onsubmit="return verifyInquiryMaterialShipmentsForm(this);">
							<fieldset>
								<legend><?php echo 'Shipments' ?></legend>
								<div style="clear:both;padding-top:4px;float:left;">
									<span>
										<strong><?php echo 'Last Updated: '; ?></strong> <?php echo $inquirydata['last_updated']; ?>
									</span><br />
								</div>
								<div style="clear:both;padding-top:4px;float:left;">
									<span>
										<strong><?php echo 'Current: '; ?></strong> <?php echo $inquirydata['status']; ?>
									</span><br />
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv">
										<strong><?php echo 'Initial Inquiry Date: '?></strong>
										<input name="inqdate" type="date" value="<?php echo $inquirydata['inquiry_date']; ?>" />
									</div>
								</div>
								<legend><?php echo 'Shipments' ?></legend>
								<div style="clear:both;padding-top:8px;float:left;">
									<input name="formsubmit" type="hidden" value="update shipments" />
									<button name="submitButton" type="submit"><?php echo 'Update Shipments' ?></button>
									<input type="hidden" name="tabindex" value="1" />
								</div>
							</fieldset>
						</form>
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
				<button type="button" id="closeModal">Cancel</button>
			</form>
		</div>
	</div>

	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>

	<script>
// Open modal when either "Add researcher" button is clicked
document.querySelectorAll('.addResearcherBtn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Save which button opened the modal
        document.getElementById('researcherModal').dataset.source = btn.dataset.target;
        document.getElementById('researcherModal').style.display = 'block';
    });
});

// Close modal
document.getElementById('closeModal').addEventListener('click', function(){
    document.getElementById('researcherModal').style.display = 'none';
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

            // Save currently selected values in multi-select
            let selectedAdditional = Array.from(additionalDropdown.selectedOptions).map(opt => opt.value);

            // Create new option
            let optionPrimary = document.createElement('option');
            optionPrimary.value = data.researcher_id;
            optionPrimary.text = data.name + ' (' + data.institution + ')';

            let optionAdditional = optionPrimary.cloneNode(true);

            // Add to both dropdowns
            primaryDropdown.appendChild(optionPrimary);
            additionalDropdown.appendChild(optionAdditional);

            // Restore previous selections in additional dropdown
            selectedAdditional.forEach(val => {
                let option = additionalDropdown.querySelector(`option[value="${val}"]`);
                if(option) option.selected = true;
            });

            // Only auto-select in primary if added via primary button
            if(document.getElementById('researcherModal').dataset.source === 'primary') {
                primaryDropdown.value = data.researcher_id;
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

	</script>
</body>
</html>