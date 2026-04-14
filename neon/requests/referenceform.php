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
    $refID = (int) $_GET['id'];
} else {
    die("Invalid or missing reference ID.");
}

if (isset($_GET['status']) && $_GET['status'] == 'success') {
    echo "<div style='color:green; margin:10px 0;'>Successfully updated reference record.</div>";
}

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif(array_key_exists('SuperAdmin',$USER_RIGHTS) || array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;

$inquiryManager = new InquiriesManager();
$utilities = new Utilities();

$statusStr = '';

$referencedata = $inquiryManager->getReferenceDataByID($refID);
$sampledata = $inquiryManager->getReferenceSampleTableByID($refID);


if($formSubmit == 'editReference' && $isEditor){

	$missing = [];

		$referencetype = $_POST['reftype'] ?? '';
		$authors = $_POST['refauthors'] ?? '';
		$pubdate = $_POST['refpubdate'] ?? '';
		$title = $_POST['reftitle'] ?? '';
		$secondarytitle = $_POST['refsecondarytitle'] ?? '';
		$volume = $_POST['refvolume'] ?? '';
		$number = $_POST['refnumber'] ?? '';
		$pages = $_POST['refpages'] ?? '';
		$url = $_POST['refurl'] ?? '';

	if (empty($referencetype)) $missing[] = 'Reference Type';
	if (empty($authors)) $missing[] = 'Authors';
	if (empty($pubdate)) $missing[] = 'Publication Date';
	if (empty($title)) $missing[] = 'Title';
	if (empty($secondarytitle)) $missing[] = 'Secondary Title';
	if (empty($url)) $missing[] = 'URL';


	if (!empty($missing)) {
		$statusStr = '<span style="color:red;">Missing required fields: ' . implode(', ', $missing) . '</span>';
	} else {
		$updatedrefID = $inquiryManager->editReference(
			$refID,
			$referencetype,
			$authors,
			$pubdate,
			$title,
			$secondarytitle,
			$volume,
			$number,
			$pages,
			$url,
			$SYMB_UID
		);

		if ($updatedrefID) {
			header("Location: referenceform.php?id=" . $updatedrefID . "&status=success");
			exit();
		} else {
			$statusStr = '<span style="color:red;">Error saving inquiry edits: ' . htmlspecialchars($inquiryManager->getErrorStr()) . '</span>';
		}
		}
	}



?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<title><?php echo 'View and Edit Existing Reference Record' ?></title>
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


	function verifyReferenceEditForm(f) {
		if (f.refauthors.value === "") {
			alert("Input authors");
			return false;
		}
		if (f.reftitle.value === "") {
			alert("Input title");
			return false;
		}
		if (f.refpubdate.value.trim() === "") {
			alert("Insert publication date");
			return false;
		}
		if (f.refurl.value === "") {
			alert("Insert url");
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
		<h1 class="page-heading"><?= 'View and Edit Reference Record' ?></h1>
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
					<li><a href="#editrefdiv"><span><?php echo 'Reference Info'; ?></span></a></li>
					<li><a href="#samples"><span><?php echo 'Samples'; ?></span></a></li>

				</ul>
					<div id="editrefdiv" style="display:<?php echo ($List ); ?>;">
						<form name="editrefform" action="referenceform.php?id=<?php echo $refID; ?>" method="post" onsubmit="return verifyReferenceAddForm(this);">
							<fieldset>
								<legend><?php echo 'Reference record' ?></legend>
								<div style="clear:both;padding-top:6px;float:left;">
								<div style="clear:both;padding-top:6px;float:left;">
									<span>
										<strong><?php echo 'Reference Type'; ?></strong><?php $referencedata['ReferenceTypeId']; ?>
									</span><br />
									<span>
										<select name="reftype" style="width:400px;" aria-label="Reference Type">
											<option disabled>-- Select option --</option>
											<option disabled>-------------------</option>
											<?php
											$reftype = $inquiryManager->getReferenceType();
											foreach($reftype as $id => $label){
												$selected = ($id == $referencedata['ReferenceTypeId']) ? 'selected' : '';
												echo '<option value="' . htmlspecialchars($id) . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
											}
											?>
										</select>
									</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       									<label for="refauthors"><strong><?php echo 'Authors (format "Last name, First and Middle initials" and seperate multiple with commas)'; ?>:</strong></label><br>
										<textarea name="refauthors" id="refauthors" style="width:1000px; height:60px;"> <?php echo $referencedata['cheatauthors']; ?></textarea>
   								 	</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="refpubdate"><strong><?php echo 'Publication Date (year)'?></strong> </label><br>
        									<input name="refpubdate" id="refpubdate" type="text" style="width:800px;" value="<?php echo $referencedata['pubdate'];?>" />
   								 	</div>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       									<label for="reftitle"><strong><?php echo 'Title'; ?>:</strong></label><br>
										<textarea name="reftitle" id="reftitle" style="width:1000px; height:60px;"> <?php echo $referencedata['title']; ?></textarea>
   								 	</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="refsecondarytitle"><strong><?php echo 'Secondary Title (e.g. journal or web platform): ';?></strong> </label><br>
        									<input name="refsecondarytitle" id="refsecondarytitle" type="text" style="width:800px;" value="<?php echo $referencedata['secondarytitle']; ?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="refvolume"><strong><?php echo 'Volume: '?></strong> </label><br>
        									<input name="refvolume" id="refvolume" type="text" style="width:800px;" value="<?php echo $referencedata['volume'];?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="refnumber"><strong><?php echo 'Number: '?></strong> </label><br>
        									<input name="refnumber" id="refnumber" type="text" style="width:800px;" value="<?php echo $referencedata['number'];?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="refpages"><strong><?php echo 'Pages: '?></strong> </label><br>
        									<input name="refpages" id="refpages" type="text" style="width:800px;" value="<?php echo $referencedata['pages'];?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="refurl"><strong><?php echo 'Pages: '?></strong> </label><br>
        									<input name="refurl" id="refurl" type="text" style="width:800px;" value="<?php echo $referencedata['url'];?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:8px;float:left;">
									<input name="formsubmit" type="hidden" value="editReference" />
									<button name="submitButton" type="submit"><?php echo 'Update Reference' ?></button>
									<input type="hidden" name="tabindex" value="1" />
								</div>
							</fieldset>
						</form>
					</div>
					<div id="samples" style="">
						<fieldset>
							<legend><?php echo 'Samples'; ?></legend>									
							<div style="clear:both;padding-top:8px;float:left;">
								<button type="button" onclick="window.location.href='samplelist.php?id=<?php echo $refID; ?>'">
								View and Modify Sample List
								</button>
							</div>
							
							</div>
						</fieldset>
					</div>
					
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

	</div>



	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>


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


</style>