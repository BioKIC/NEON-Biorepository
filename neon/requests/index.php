<?php
include_once('../../config/symbini.php');
include_once('../../neon/requests/list/InquiriesList.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/loans/loan_langs.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/loans/loan_langs.en.php');
header("Content-Type: text/html; charset=".$CHARSET);

$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';


$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif(array_key_exists('SuperAdmin',$USER_RIGHTS) || array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;

$inquiryManager = new InquiriesList();

$statusStr = '';
if($isEditor){
	if($formSubmit){
		if($formSubmit == 'Delete Inquiry'){
			if($inquiryManager->deleteInquiry($_POST['inquiryId'])){
				$statusStr = 'Inquiry deleted successfully!';
			}
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
						<form name="newloanoutform" action="outgoing.php" method="post" onsubmit="return verfifyLoanOutAddForm(this);">
							<fieldset>
								<legend><?php echo 'Create New Record' ?></legend>
								<div style="padding-top:4px;float:left;">
									<span>
										<?php echo 'Managed by' ?>:
									</span><br />
									<span>
										<input type="text" autocomplete="off" name="createdbyown" maxlength="32" style="width:100px;" value="<?php echo $PARAMS_ARR['un']; ?>" title="<?php echo $LANG['ENTERED_BY'] ?>" aria-label="<?php echo $LANG['SUBMITTED_BY'] ?>" />
									</span>
								</div>
								<div style="clear:both;padding-top:6px;float:left;">
									<span>
										<?php echo 'Primary Contact'; ?>:
									</span><br />
									<span>
										<select name="reqinstitution" style="width:400px;" aria-label="<?php echo $LANG['SEND_INSTITUTION'] ?>" >
											<option value=""><?php echo 'Select Researcher'; ?></option>
											<option value="">------------------------------------------</option>
											<?php
											$instArr = $inquiryManager->getInstitutionArr();
											foreach($instArr as $k => $v){
												echo '<option value="' . $k . '">' . $v . '</option>';
											}
											?>
										</select>
									</span>
									<span>
										<a href="../misc/institutioneditor.php?emode=1" target="_blank" title="<?php echo $LANG['ADD_NEW_INST']; ?>" aria-label="<?php echo $LANG['ADD_A_NEW_INST']; ?>">
											<img src="../../images/add.png" style="width:1.2em;" alt="<?php echo $LANG['ADD_NEW_INST']; ?>" />
										</a>
									</span>
								</div>
								<div style="clear:both;padding-top:8px;float:right;">
									<input name="formsubmit" type="hidden" value="createLoanOut" />
									<button name="submitButton" type="submit"><?php echo $LANG['CREATE_LOAN']; ?></button>
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
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
</body>
</html>