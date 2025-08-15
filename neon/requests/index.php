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

$statusStr = '';

if($formSubmit == 'createInquiry' && $isEditor){
    $collection_manager = $_POST['inqmanager'] ?? '';
    $researcher_id = $_POST['inqresearcher'] ?? '';
    $inquiry_date = $_POST['inqdate'] ?? '';

    if(!$collection_manager || !$researcher_id || !$inquiry_date){
        $statusStr = '<span style="color:red;">All fields are required.</span>';
    } else {
        $insertId = $inquiryManager->addInquiry($collection_manager, $researcher_id, $inquiry_date);
        if($insertId){
            $statusStr = '<span style="color:green;">SUCCESS: Inquiry created (ID: '.$insertId.').</span>';
        } else {
            $statusStr = '<span style="color:red;">Error: '.$inquiryManager->errorMessage.'</span>';
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
		if (f.inqresearcher.options[f.inqresearcher.selectedIndex].value == 0) {
			alert("<?php echo 'Select Researcher'; ?>");
			return false;
		}

		if (f.inqmanager.options[f.inqmanager.selectedIndex].value == 0) {
			alert("<?php echo 'Select Manager'; ?>");
			return false;
		}

		if (f.inqdate.options[f.inqdate.selectedIndex].value == 0) {
			alert("<?php echo 'Select Inquiry Date'; ?>");
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
										<?php echo 'Manager'; ?>:
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
										<?php echo 'Primary Contact'; ?>:
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
    										<button type="button" id="addResearcherBtn" title="Add new researcher" aria-label="Add new researcher" style="background:none;border:none;cursor:pointer;">
											<?php echo 'Add researcher' ?>
											</button>									
									</span>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv">
										Initial Inquiry Date: <input name="inqdate" type="date" value="<?php echo $inquirydate; ?>" />
									</div>
								</div>
								<div style="clear:both;padding-top:8px;float:right;">
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
	document.getElementById('researcherForm').addEventListener('submit', function(e){
		e.preventDefault();
		let formData = new FormData(this);

		fetch('../../neon/requests/add_researcher.php', { method: 'POST', body: formData })
		
		.then(res => res.json())
		.then(data => {
			if(data.success){
				let dropdown = document.querySelector('select[name="inqresearcher"]');
				let newOption = document.createElement('option');
				newOption.value = data.researcher_id;
				newOption.text = data.name + ' (' + data.institution + ')';
				dropdown.appendChild(newOption);

				dropdown.value = data.researcher_id;

				document.getElementById('researcherModal').style.display = 'none';
				document.getElementById('researcherForm').reset();

				alert('Researcher added successfully!');
			} else {
				alert('Error: ' + data.message);
			}
		})
		.catch(err => alert('Request failed'));
	});
	</script>

	<script>
	document.getElementById('addResearcherBtn').addEventListener('click', function(){
		document.getElementById('researcherModal').style.display = 'flex';
	});

	document.getElementById('closeModal').addEventListener('click', function(){
		document.getElementById('researcherModal').style.display = 'none';
	});
	</script>
</body>
</html>