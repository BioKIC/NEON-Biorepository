<?php
include_once('../../config/symbini.php');
include_once('../../neon/classes/InquiriesManager.php');

if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';


$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif(array_key_exists('SuperAdmin',$USER_RIGHTS) || array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;

$inquiryManager = new InquiriesManager();

$statusStr = '';


if($formSubmit == 'createReference' && $isEditor){
    $referencetype = $_POST['reftype'] ?? '';
    $authors = $_POST['refauthors'] ?? '';
    $pubdate = $_POST['refpubdate'] ?? '';
	$title = $_POST['reftitle'] ?? '';
	$secondarytitle = $_POST['refsecondarytitle'] ?? '';
	$volume = $_POST['refvolume'] ?? '';
	$number = $_POST['refnumber'] ?? '';
	$pages = $_POST['refpages'] ?? '';
	$url = $_POST['refurl'] ?? '';

    if(!$referencetype || !$authors || !$pubdate || !$secondarytitle || !$title || !$url ){
        $statusStr = '<span style="color:red;">Missing required fields.</span>';
    } else {
        $insertId = $inquiryManager->addReference($referencetype, $authors, $pubdate, $title, $secondarytitle, $volume, $number, $pages, $url);
	 if ($insertId) {
        header("Location: referenceform.php?id=" . $insertId);
        exit();
    } else {
        echo "Error saving reference: " . $inquiryManager->getError();
    }
    }
}

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<title><?php echo 'New Reference Record' ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>


	<script>
		function verifyReferenceAddForm(f) {
		if (f.reftype.value === "") {
			alert("Select Reference Type");
			return false;
		}
		if (f.refauthors.value === "") {
			alert("Input researchers");
			return false;
		}
		if (f.refpubdate.value.trim() === "") {
			alert("Input publication date");
			return false;
		}
		if (f.reftitle.value.trim() === "") {
			alert("Insert title");
			return false;
		}
		if (f.refsecondarytitle.value.trim() === "") {
			alert("Insert secondary title");
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
		<h1 class="page-heading"><?= 'New Reference' ?></h1>
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
			
					<div id="newrefdiv" style="display:block;">
						<form name="newrefform" action="reference.php" method="post" onsubmit="return verifyReferenceAddForm(this);">
							<fieldset>
								<legend><?php echo 'Create New Reference' ?></legend>
								<div style="padding-top:4px;float:left;">
									<span>
       								<strong><?php echo 'Reference Type'; ?>:</strong>
									</span><br />
									<span>
										<select name="reftype" style="width:400px;" aria-label="<?php echo 'Select Reference Type' ?>" >
											<option value=""><?php echo 'Select Type'; ?></option>
											<option value="">------------------------------------------</option>
											<?php
											$typeArr = $inquiryManager->getReferenceType();
											foreach($typeArr as $k => $v){
												echo '<option value="' . $k . '">' . $v . '</option>';
											}
											?>
										</select>
									</span>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
								<div class="fieldDiv">
									<label for="refauthors"><strong><?php echo 'Authors (format "Last name, First and Middle initials" and seperate multiple with commas)'; ?>:</strong></label><br>
									<input name="refauthors" id="refauthors" type="text" style="width:800px;" 
										value="<?php echo htmlspecialchars($authors ?? '', ENT_QUOTES); ?>" />
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
								<div class="fieldDiv">
									<label for="reftitle"><strong><?php echo 'Title'; ?>:</strong></label><br>
									<input name="reftitle" id="reftitle" type="text" style="width:800px;" 
										value="<?php echo htmlspecialchars($title ?? '', ENT_QUOTES); ?>" />
								</div>
								</div>
								<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
									<div class="fieldDiv"> <strong>
										Publication Date (year):</strong> <input name="refpubdate" type="text" value="<?php  echo htmlspecialchars($pubdate ?? '', ENT_QUOTES); ?>" />
									</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="refsecondarytitle"><strong><?php echo 'Secondary Title (e.g. journal or web platform):'; ?></strong></label><br>
        									<input name="refsecondarytitle" id="refsecondarytitle" type="text" style="width:400px;" value="<?php echo htmlspecialchars($secondarytitle ?? '', ENT_QUOTES); ?>" />
   								 		</div>
										</div>
								</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="refvolume"><strong><?php echo 'Volume'; ?>:</strong></label><br>
        									<input name="refvolume" id="refvolume" type="text" style="width:400px;" value="<?php echo htmlspecialchars($volume ?? '', ENT_QUOTES); ?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="refnumber"><strong><?php echo 'Number'; ?>:</strong></label><br>
        									<input name="refnumber" id="refnumber" type="text" style="width:400px;" value="<?php echo htmlspecialchars($number ?? '', ENT_QUOTES); ?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="refpages"><strong><?php echo 'Pages'; ?>:</strong></label><br>
        									<input name="refpages" id="refpages" type="text" style="width:400px;" value="<?php echo htmlspecialchars($pages ?? '', ENT_QUOTES); ?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<div class="fieldGroupDiv" style="clear:both;padding-top:6px;float:left;">
   						 			<div class="fieldDiv">
       										<label for="refurl"><strong><?php echo 'URL'; ?>:</strong></label><br>
        									<input name="refurl" id="refurl" type="text" style="width:600px;" value="<?php echo htmlspecialchars($url ?? '', ENT_QUOTES); ?>" />
   								 	</div>
								</div>
								<div style="clear:both;padding-top:8px;float:left;">
									<input name="formsubmit" type="hidden" value="createReference" />
									<button name="submitButton" type="submit"><?php echo 'Create Reference' ?></button>
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

	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>

	<script>

	</script>
</body>
</html>