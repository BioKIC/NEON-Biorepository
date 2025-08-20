<?php
include_once('../../config/symbini.php');
include_once('../../neon/requests/list/InquiriesManager.php');

if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $request_id = (int) $_GET['id'];
} else {
    die("Invalid or missing request ID.");
}


$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif(array_key_exists('SuperAdmin',$USER_RIGHTS) || array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;

$inquiryManager = new InquiriesManager();

$statusStr = '';

$inquirydata = $inquiryManager->getInquiryDataByID($request_id);
$researcherdata = $inquiryManager->getAdditionalResearchersByID($request_id);
$collectiondata = $inquiryManager->getCollectionsByID($request_id);
$cm = $inquiryManager->getCMByID($request_id);
$pc = $inquiryManager->getPrimaryContactByID($request_id);

if($formSubmit == 'editInquiry' && $isEditor){
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


    if(!$collection_manager || !$researcher_id || !$inquiry_date || !$title || !$collections || !$field || !$funded || !$fundingsource || !$description || !$howfound || !$dataproduced || !$existing || !$future || !$new || !$additionalresearchers || !$drivefolder || !$aiml){
        $statusStr = '<span style="color:red;">Missing required fields.</span>';
    } else {
        $request_id= $inquiryManager->editInquiry($collection_manager, $researcher_id, $inquiry_date, $title, $collections, $field, $secondaryfields, $funded, $fundingsource, $description, $howfound, $dataproduced, $existing, $future, $new, $additionalresearchers, $drivefolder, $aiml);
	 if ($request_id) {
        header("Location: inquiryform.php?id=" . $request_id);
        exit();
    } else {
        echo "Error saving inquiry edits: " . $inquiryManager->getError();
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


	<!-- <script>
	function verifyInquiryUpdateForm(f) {
		if (f.inqresearcher.options[f.inqresearcher.selectedIndex].value == 0) {
			alert("<?php echo 'Select Researcher'; ?>");
			return false;
		}
		return true;
	}
	</script> -->

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
			
					<div id="newinqdiv" style="display:<?php echo ($List ); ?>;">
						<form name="newinqform" action="index.php" method="post" onsubmit="return verifyInquiryAddForm(this);">
							<fieldset>
								<legend><?php echo 'Sample use inquiry record' ?></legend>
								<div style="padding-top:4px;float:left;">
									<span>
										<strong><?php echo 'Manager'; ?>:</strong> 
									</span><br />
									<span>
										<select name="inqmanager" style="width:400px;" aria-label="<?php echo 'Select Inquiry Manager' ?>" >
											<option value=""><?php echo $cm['name']; ?></option>
											<option value="">------------------------------------------</option>
											<?php
											$managerArr = $inquiryManager->getManagers();
											foreach($managerArr as $k => $v){
												echo '<option value="' . $k . '">' . $v . '</option>';
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
										<select name="inqresearcher" style="width:400px;" aria-label="<?php echo 'Researcher' ?>" >
											<option value=""><?php echo $pc['name'].' ('.$pc['institution'].')'; ?></option>
											<option value="">------------------------------------------</option>
											<?php
											$researcherArr = $inquiryManager->getResearchers();
											foreach($researcherArr as $k => $v){
												echo '<option value="' . $k . '">' . $v . '</option>';
											}
											?>
										</select>
									</span>
									<span>
											<button type="button" class="addResearcherBtn" data-target="primary">Add researcher</button>
											</button>									
									</span>
								</div>
									<div style="clear:both;padding-top:6px;float:left;">
									<span>
										<strong><?php echo 'Additional Researchers'; ?>:</strong><br>
										<?php 
										if (!empty($researcherdata)) {
											foreach ($researcherdata as $r) {
												echo htmlspecialchars($r) . "<br>";
											}
										} else {
											echo "None";
										}
										?>
									</span><br />
									<span>
										<select name="inqadditionalresearcher[]" style="width:800px;" multiple aria-label="<?php echo 'Researchers' ?>" >
											<option value=""><?php echo 'Select Researchers'; ?></option>
											<option value="">------------------------------------------</option>
											<?php
											$researcherArr = $inquiryManager->getResearchers();
											foreach($researcherArr as $k => $v){
												echo '<option value="' . $k . '">' . $v . '</option>';
											}
											?>
										</select>
									</span>
									<span>
											<button type="button" class="addResearcherBtn" data-target="additional">Add researcher</button>
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
									<div class="fieldDiv">
										<strong><?php echo 'Initial Inquiry Date: '?></strong>
										<input name="inqdate" type="date" value="<?php echo $inquirydata['inquiry_date']; ?>" />
									</div>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<span>
										<strong><?php echo 'Collections of Interest'; ?>:</strong><br>
										<?php 
										if (!empty($collectiondata)) {
											foreach ($collectiondata as $r) {
												echo htmlspecialchars($r) . "<br>";
											}
										} else {
											echo "None";
										}
										?>
									</span>
									<span>
										<select name="inqcolls[]" style="width:1000px; height:120px;" multiple aria-label="<?php echo 'Collections' ?>">
											<option value=""><?php echo 'Select all Collections of Interest'; ?></option>
											<option value="">------------------------------------------</option>
											<?php
											$collectionArr = $inquiryManager->getCollections();
											foreach($collectionArr as $k => $v){
												echo '<option value="' . $k . '">' . $v . '</option>';
											}
											?>
										</select>
									</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<span>
										<strong><?php echo 'Primary Research Field: '?> </strong> <?php echo $inquirydata['primary_research_field'] ?> 
									</span><br />
									<span>
										<select name="inqfield" style="width:400px;" aria-label="<?php echo 'Select Primary Research Field' ?>" >
											<option value=""><?php echo 'Select research field'; ?></option>
											<option value="">------------------------------------------</option>
											<?php
											$fieldArr = $inquiryManager->getFields();
											foreach($fieldArr as $k => $v){
												echo '<option value="' . $k . '">' . $v . '</option>';
											}
											?>
										</select>
									</span>
								</div>
																<div style="clear:both;padding-top:6px;float:left;">
									<span>
       								<strong><?php echo 'Uses AI / ML methods?'; ?>:</strong>
									</span><br />
								<span>
									<select name="inqaiml" style="width:400px;" aria-label="Select AI/ML">
										<option value="">Select AI/ML</option>
										<option value="">------------------------------------------</option>
										<?php
										$aimlArr = array('yes','no');
										foreach($aimlArr as $text){
											echo '<option value="' . htmlspecialchars($text) . '">' . htmlspecialchars($text) . '</option>';
										}
										?>
									</select>
								</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="inqsecondaryfields"><strong><?php echo 'Secondary Research Fields: ';?></strong> </label><br>
        									<input name="inqsecondaryfields" id="inqsecondaryfields" type="text" style="width:800px;" value="<?php echo $inquirydata['secondary_research_field']; ?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="inqdata"><strong><?php echo 'Types of Data Produced: '?></strong> </label><br>
        									<input name="inqdata" id="inqdata" type="text" style="width:800px;" value="<?php echo $inquirydata['data_produced'];?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<span>
										<strong><?php echo 'Funding status: '; ?></strong>
									</span><br />
								<span>
									<select name="inqfunded" style="width:400px;" aria-label="Select Funding Status">
										<option value= "<?php echo $inquirydata['funded']; ?>" </option>
										<option value="">------------------------------------------</option>
										<?php
										$fundingArr = array(
											'Already externally funded OR Internal/institutional support',
											'Proposal pending funding',
											'Proposal in development',
											'Proposal in preparation',
											'Proposal not funded'
										);
										foreach($fundingArr as $text){
											$selected = ($inquirydata['funded'] === $funded) ? 'selected' : '';
        									echo '<option value="' . htmlspecialchars($funded) . '" ' . $selected . '>' . htmlspecialchars($text) . '</option>';
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
										<select name="inqhowfound" style="width:400px;" aria-label="<?php echo 'How did the researchers find us' ?>" >
											<option value=""><?php echo $inquirydata['how_found_us']; ?></option>
											<option value="">------------------------------------------</option>
											<?php
											$howfoundArr = $inquiryManager->getHowFoundUs();
											foreach($howfoundArr as $k => $v){
												echo '<option value="' . $k . '">' . $v . '</option>';
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
									<input name="formsubmit" type="hidden" value="createInquiry" />
									<button name="submitButton" type="submit"><?php echo 'Update Inquiry' ?></button>
								</div>
							</fieldset>
						</form>
					</div>
					<div style="clear:both;">&nbsp;</div>
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