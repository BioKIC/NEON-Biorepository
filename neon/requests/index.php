<?php
include_once('../../config/symbini.php');
include_once('../../neon/requests/list/InquiriesManager.php');

if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';


$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif(array_key_exists('SuperAdmin',$USER_RIGHTS) || array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;

$inquiryManager = new InquiriesManager();

$statusStr = '';


if($formSubmit == 'createInquiry' && $isEditor){
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


    if(!$collection_manager || !$researcher_id || !$inquiry_date || !$title || !$collections || !$field || !$funded || !$fundingsource || !$description || !$howfound || !$dataproduced || !$existing || !$future || !$new || !$additionalresearchers || !$drivefolder || !$aiml || !$internal){
        $statusStr = '<span style="color:red;">Missing required fields.</span>';
    } else {
        $insertId = $inquiryManager->addInquiry($collection_manager, $researcher_id, $inquiry_date, $title, $collections, $field, $secondaryfields, $funded, $fundingsource, $description, $howfound, $dataproduced, $existing, $future, $new, $additionalresearchers, $drivefolder, $aiml, $internal);
	 if ($insertId) {
        header("Location: inquiryform.php?id=" . $insertId);
        exit();
    } else {
        echo "Error saving inquiry: " . $inquiryManager->getError();
    }
    }
}

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<title><?php echo 'New Inquiry Record' ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>


	<script>
		function verifyInquiryAddForm(f) {
		if (f.inqresearcher.value === "") {
			alert("Select Researcher");
			return false;
		}
		if (f.inqmanager.value === "") {
			alert("Select Manager");
			return false;
		}
		if (f.inqdate.value.trim() === "") {
			alert("Select Inquiry Date");
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
			<b>New Inquiry Record</b>
		</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= 'New Inquiry Record' ?></h1>
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
								<legend><?php echo 'Create New Record' ?></legend>
								<div style="padding-top:4px;float:left;">
									<span>
       								<strong><?php echo 'Manager'; ?>:</strong>
									</span><br />
									<span>
										<select name="inqmanager" style="width:400px;" aria-label="<?php echo 'Select Inquiry Manager' ?>" >
											<option value=""><?php echo 'Select Manager'; ?></option>
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
											<option value=""><?php echo 'Select Researcher'; ?></option>
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
											<button type="button" class="addResearcherBtn" data-target="primary">Create new researcher</button>
											</button>									
									</span>
								</div>
									<div style="clear:both;padding-top:6px;float:left;">
									<span>
       								<strong><?php echo 'Additional Researchers (select all)'; ?>:</strong>
									</span><br />
									<strong><?php echo 'Additional Researchers (select all)'; ?>:</strong><br />
									<span>
										<select name="inqadditionalresearcher[]" style="width:800px;" multiple aria-label="<?php echo 'Researchers' ?>">
											<option value=""><?php echo 'Select Researchers'; ?></option>
											<option value="">------------------------------------------</option>
											<?php
											$selectedResearchers = $inquiryManager->getAdditionalResearchersByID($request_id);
											$researcherArr = $inquiryManager->getResearchers();
											foreach ($researcherArr as $k => $v) {
												$selected = (in_array($k, $selectedResearchers)) ? 'selected' : '';
												echo '<option value="' . htmlspecialchars($k) . '" ' . $selected . '>' . htmlspecialchars($v) . '</option>';
											}
											?>
										</select>
									</span>

									<span>
											<button type="button" class="addResearcherBtn" data-target="additional">Create new researcher</button>
											</button>									
									</span>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
								<div class="fieldDiv">
									<label for="inqtitle"><strong><?php echo 'Title'; ?>:</strong></label><br>
									<input name="inqtitle" id="inqtitle" type="text" style="width:800px;" 
										value="<?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?>" />
								</div>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv"> <strong>
										Initial Inquiry Date:</strong> <input name="inqdate" type="date" value="<?php echo $inquiry_date; ?>" />
									</div>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<span>
       								<strong><?php echo 'Collections of Interest (select all)'; ?>:</strong>
									</span><br />
									<span>
										<select name="inqcolls[]" style="width:800px; height:120px;" multiple aria-label="<?php echo 'Collections' ?>">
											<option value=""><strong><?php echo 'Select all Collections of Interest'; ?><strong></option>
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
       								<strong><?php echo 'Primary Research Field'; ?>:</strong>
									</span><br />
									<span>
										<select name="inqfield" style="width:600px;" aria-label="<?php echo 'Select Primary Research Field' ?>" >
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
       								<strong><?php echo 'Uses AI / ML methods?'; ?></strong>
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
											echo '<option value="' . htmlspecialchars($text) . '">' . htmlspecialchars($text) . '</option>';
										}
										?>
									</select>
								</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="inqsecondaryfields"><strong><?php echo 'Secondary Research Fields or Keywords (separate multiple with semicolons):'; ?></strong>:</label><br>
        									<input name="inqsecondaryfields" id="inqsecondaryfields" type="text" style="width:400px;" value="<?php echo htmlspecialchars($secondaryfields ?? '', ENT_QUOTES); ?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="inqdata"><strong><?php echo 'Types of Data Produced (separate multiple with semicolons)'; ?>:</strong></label><br>
        									<input name="inqdata" id="inqdata" type="text" style="width:400px;" value="<?php echo htmlspecialchars($dataproduced ?? '', ENT_QUOTES); ?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<span>
       								<strong><?php echo 'Funding Status'; ?>:</strong>
									</span><br />
								<span>
									<select name="inqfunded" style="width:400px;" aria-label="Select Funding Status">
										<option value="">Select funding status</option>
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
											echo '<option value="' . htmlspecialchars($text) . '">' . htmlspecialchars($text) . '</option>';
										}
										?>
									</select>
								</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="inqfundingsource"><strong><?php echo 'Funding Source'; ?>:</strong></label><br>
        									<input name="inqfundingsource" id="inqfundingsource" type="text" style="width:400px;" value="<?php echo htmlspecialchars($fundingsource ?? '', ENT_QUOTES); ?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
										<div class="fieldDiv">
											<label for="inqdescription"><strong><?php echo 'Project Description'; ?>:</strong></label><br>
											<textarea name="inqdescription" id="inqdescription" style="width:1000px; height:150px;"><?php echo htmlspecialchars($description ?? '', ENT_QUOTES); ?></textarea>
										</div>
									</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<span>
       								<strong><?php echo 'How did the researchers find us?'; ?>:</strong>
									</span><br />
									<span>
										<select name="inqhowfound" style="width:400px;" aria-label="<?php echo 'How did the researchers find us' ?>" >
											<option value=""><?php echo 'Select Option'; ?></option>
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
									<input type="checkbox" id="inqexist" name="inqexist" value="yes" <?php echo (!empty($exist) && $exist === 'yes') ? 'checked' : ''; ?> />
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<label for="inqfuture"><strong><?php echo 'May use future samples not yet at the Biorepository?' ?></strong></label>
									<input type="hidden" name="inqfuture" value="no" />
									<input type="checkbox" id="inqfuture" name="inqfuture" value="yes" <?php echo (!empty($future) && $future === 'yes') ? 'checked' : ''; ?> />
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<label for="inqnew"><strong><?php echo 'May generate new samples?' ?></strong></label>
									<input type="hidden" name="inqnew" value="no" />
									<input type="checkbox" id="inqnew" name="inqnew" value="yes" <?php echo (!empty($new) && $new === 'yes') ? 'checked' : ''; ?> />
								</div>
									<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="inqdrive"><strong><?php echo 'Name of Google Drive Folder for Inquiry Documents'; ?>:</strong></label><br>
        									<input name="inqdrive" id="inqdrive" type="text" style="width:400px;" value="<?php echo htmlspecialchars($drivefolder ?? '', ENT_QUOTES); ?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:8px;float:left;">
									<input name="formsubmit" type="hidden" value="createInquiry" />
									<button name="submitButton" type="submit"><?php echo 'Create Inquiry' ?></button>
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