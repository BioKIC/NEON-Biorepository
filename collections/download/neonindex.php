<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/download/index.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/download/index.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/download/index.en.php');
include_once($SERVER_ROOT . '/config/auth_config.php');

header("Content-Type: text/html; charset=".$CHARSET);

$sourcePage = array_key_exists('sourcepage', $_REQUEST) ? $_REQUEST['sourcepage'] : 'specimen';
$downloadType = array_key_exists('dltype', $_REQUEST) ? $_REQUEST['dltype'] : 'specimen';
$taxonFilterCode = array_key_exists('taxonFilterCode', $_REQUEST) ? filter_var($_REQUEST['taxonFilterCode'], FILTER_SANITIZE_NUMBER_INT) : 0;
$displayHeader = array_key_exists('displayheader', $_REQUEST) ? filter_var($_REQUEST['displayheader'], FILTER_SANITIZE_NUMBER_INT) : 0;
$searchVar = array_key_exists('searchvar', $_REQUEST) ? htmlspecialchars($_REQUEST['searchvar'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE| ENT_QUOTES) : '';

$dwcManager = new DwcArchiverCore();

function getAccountStatus()
	{
		global $AUDIENCE;
		$accessToken = $_SESSION['ACCESS_TOKEN'];
		$sub = $_SESSION['SUBSCRIBER'];
	
		$ch = curl_init();
	
		curl_setopt_array($ch, [
			//CURLOPT_URL => $PROVIDER_URLS . "/api/v2/users/" . $sub,
			CURLOPT_URL => "https://auth.neonscience.org/api/v2/users/" . $sub,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				"Authorization: Bearer {$accessToken}"
			]
		]);
		$response = curl_exec($ch);
	
		if (curl_errno($ch)) {
			throw new Exception(curl_error($ch));
		}
	
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		$user = json_decode($response, true);
		
		if ($statusCode === 401) {
			if (
				isset($user['message']) &&
				str_contains($user['message'], 'Expired token')
			) {
				return [
					'expired'
				];
			}
		
			throw new Exception(
				$user['message'] ?? 'Unauthorized'
			);
		}
		
		if ($statusCode !== 200) {
			throw new Exception(
				$user['message'] ?? "Unable to retrieve Auth0 user. HTTP {$statusCode}"
			);
		}
	
		$consentTimestamp = $user['user_metadata']['consent_timestamp'] ?? null;
		
		$validConsentTimestamp = false;
		if (is_numeric($consentTimestamp)) {
			$seconds = (int) floor($consentTimestamp / 1000);
			$validConsentTimestamp = checkdate(
				(int) date('n', $seconds),
				(int) date('j', $seconds),
				(int) date('Y', $seconds)
			);
		}
		
		$step1Complete =
			($user['user_metadata']['consent_given'] ?? false) === true &&
			$validConsentTimestamp &&
			($user['user_metadata']['has_signed_up'] ?? false) === true;
	
		$step2Complete =
			$step1Complete &&
			($user['email_verified'] ?? false) === true;
	
		$step3Complete =
			$step2Complete &&
			!empty($user['user_metadata']['first_name']) &&
			!empty($user['user_metadata']['last_name']) &&
			!empty($user['user_metadata']['affiliation']) &&
			!empty($user['user_metadata']['ror_id']) &&
			!empty($user['user_metadata']['organization_country']) &&
			!empty($user['user_metadata']['subject_matter_expertise_provider']);
	
		$step = 0;
	
		if ($step1Complete) $step = 1;
		if ($step2Complete) $step = 2;
		if ($step3Complete) $step = 3;
	
		return [
			'ready' => $step === 3,
			'step' => $step,
			'step1Complete' => $step1Complete,
			'step2Complete' => $step2Complete,
			'step3Complete' => $step3Complete
		];
	}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title> <?= $LANG['COLL_SEARCH_DWNL'] ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script>
		$(document).ready(function() {
			setPackageType('basic');
			var dialogArr = new Array("schemanative","schemadwc");
			var dialogStr = "";
			for(i=0;i<dialogArr.length;i++){
				dialogStr = dialogArr[i]+"info";
				$( "#"+dialogStr+"dialog" ).dialog({
					autoOpen: false,
					modal: true,
					position: { my: "left top", at: "center", of: "#"+dialogStr }
				});

				$( "#"+dialogStr ).click(function() {
					$( "#"+this.id+"dialog" ).dialog( "open" );
				});
			}

			if(sessionStorage.querystr){
				if(document.getElementById("searchVar-input").value == ""){
					document.getElementById("searchVar-input").value = sessionStorage.querystr;
				}
			}
		});

		function extensionSelected(obj){
			if(obj.checked == true){
				obj.form.zip.checked = true;
			}
		}

		function zipSelected(obj){
			if(obj.checked == false){
				obj.form.images.checked = false;
				obj.form.identifications.checked = false;
				if(obj.form.attributes) obj.form.attributes.checked = false;
				if(obj.form.materialsample) obj.form.materialsample.checked = false;
				if(obj.form.identifiers) obj.form.identifiers.checked = false;
			}
		}
		function validateDownloadForm(f){

			gtag('event', 'data_download', {
				downloader_id: f.symbUid.value,
				search_var: f.searchvar.value
			});

			document.getElementById("workingcircle").style.display = "inline";

			return true;
		}
		function closePage(timeToClose){
			setTimeout(function () {
				window.close();
			}, timeToClose);
		}
		function setPackageType(packageType){

			const extensionFields = [
				'identifications',
				'images',
				'attributes',
				'materialsample',
				'identifiers'
			];

			// zip always enabled
			document.getElementById('zip').value = '1';

			// BASIC = no extensions
			if(packageType === 'basic'){

				extensionFields.forEach(function(id){

					const el = document.getElementById(id);

					if(el){
						el.disabled = true;
						el.value = '';
					}
				});
			}

			// EXPANDED = all extensions
			if(packageType === 'expanded'){

				extensionFields.forEach(function(id){

					const el = document.getElementById(id);

					if(el){
						el.disabled = false;
						el.value = '1';
					}
				});
			}
		}
		function toggleDownloadButton(){

			const checkbox = document.getElementById('agreepolicy');
			const button = document.getElementById('downloadbutton');

			button.disabled = !checkbox.checked;
		}
	</script>
	<style>
		fieldset{ margin:10px; padding:10px }
		legend{ font-weight:bold }
		button { display: inline; }
		.sectionDiv{ clear:both; margin:20px; }
		.labelDiv{ float:left; font-weight:bold; width:200px }
		.formElemDiv{ float:left }
		#downloadbutton:disabled,
		#downloadbutton:disabled:hover,
		#downloadbutton:disabled:focus,
		#downloadbutton:disabled:active{
			cursor: not-allowed !important;
			color: #a2a4a3 !important;
			background: #e4e6e7 !important;
			background-color: #e4e6e7 !important;
			border-color: #d3d5d6 !important;
			box-shadow: none !important;
			text-shadow: none !important;
		}
		#downloadbutton{
			padding: 16px 24px;
		}
	</style>
	<style>
		#login-required-container{
			font-size: 0.8rem;
			font-family: "Inter",Helvetica,Arial,sans-serif;
			font-weight: 400;
			line-height: 1.43;
			box-sizing: inherit;
			color: rgba(0, 0, 0, 0.9);
			transition: box-shadow 300ms cubic-bezier(0.4, 0, 0.2, 1) 0ms;
			border: 1px solid #d7d9d9;
			overflow: hidden;
			border-radius: 4px;
			margin: 4px 0px 24px 0px;
			border-color: #ffcb4f;
			background-color: #fff5dc;
		}

		#login-required-container svg{
			fill: currentColor;
			width: 1em;
			height: 1em;
			display: inline-block;
			transition: fill 200ms cubic-bezier(0.4, 0, 0.2, 1) 0ms;
			flex-shrink: 0;
			user-select: none;
			font-size: 1.25rem;
			margin-right: 16px;
		}

		#login-required-container a{
			margin: 0;
			color: #0073cf;
			text-decoration: underline;
		}

		.signin-button {
			border: 0;
			cursor: pointer;
			margin: 0;
			display: inline-flex;
			outline: 0;
			position: relative;
			align-items: center;
			user-select: none;
			vertical-align: middle;
			justify-content: center;
			text-decoration: none;
			font-size: 0.7rem;
			min-width: 64px;
			box-sizing: border-box;
			transition: background-color 250ms cubic-bezier(0.4, 0, 0.2, 1) 0ms,box-shadow 250ms cubic-bezier(0.4, 0, 0.2, 1) 0ms,border 250ms cubic-bezier(0.4, 0, 0.2, 1) 0ms;
			font-weight: 600;
			line-height: 1.75;
			border-radius: 2px;
			letter-spacing: 0.06em;
			text-transform: uppercase;
			box-shadow: 0px 3px 1px -2px rgba(0,0,0,0.2),0px 2px 2px 0px rgba(0,0,0,0.14),0px 1px 5px 0px rgba(0,0,0,0.12);
			color: #fff;
			padding: 8px 16px;
			background-color: #0073cf;
		}
		
		.account-validation-card {
			color: rgba(0, 0, 0, 0.9);
			font-size: 0.8rem;
			font-family: "Inter", Helvetica, Arial, sans-serif;
			line-height: 1.43;
			border: 1px solid #ffcb4f;
			border-radius: 4px;
			background-color: #fff5dc;
			overflow: hidden;
			margin: 40px 0 0px;
		}
		
		.account-validation-header {
			display: flex;
			align-items: center;
			padding: 16px 20px 12px;
		}
		
		.account-validation-header h6 {
			flex-grow: 1;
			margin: 0;
			font-size: 0.775rem;
			font-weight: 600;
			text-transform: uppercase;
		}
		
		.account-validation-icon-small,
		.account-validation-icon-large {
			fill: currentColor;
			flex-shrink: 0;
		}
		
		.account-validation-icon-small {
			width: 1.25rem;
			height: 1.25rem;
			margin-right: 16px;
		}
		
		.account-validation-icon-large {
			width: 2.1875rem;
			height: 2.1875rem;
			color: #ffcb4f;
			margin-left: 16px;
		}
		
		.account-validation-body {
			padding: 0 24px 24px;
		}
		
		.account-validation-body p {
			margin: 0;
		}
		
		.account-validation-card hr {
			border: none;
			height: 1px;
			background-color: rgba(0, 0, 0, 0.12);
			margin: 0 0 16px;
		}
		
		.validation-steps-container {
			width: 100%;
			margin-top: 16px;
		}
		
		.validation-steps-container h6 {
			margin: 0;
			font-size: 0.875rem;
			font-weight: 600;
		}
		
		.validation-steps-container span {
			font-size: 0.75rem;
		}
		
		.validation-learn-more {
			margin-top: 8px !important;
		}
		
		.validation-learn-more a {
			color: #0073cf;
			text-decoration: underline;
		}
		
		.validation-learn-more a:hover,
		.validation-learn-more a:active {
			color: #0092e2;
		}
		
		.validation-stepper {
			display: flex;
			align-items: center;
			margin-top: 8px;
			padding: 24px 0;
			background-color: transparent;
		}
		
		.validation-step {
			display: flex;
			align-items: center;
			padding: 0 8px;
			color: rgba(0, 0, 0, 0.54);
			cursor: pointer;
		}
		
		.validation-step.complete,
		.validation-step.active {
			color: rgba(0, 0, 0, 0.9);
			font-weight: 500;
		}
		
		.step-icon {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 24px;
			height: 24px;
			margin-right: 8px;
			border-radius: 50%;
			background-color: rgba(0, 0, 0, 0.38);
			color: #fff;
			font-size: 0.75rem;
			font-weight: 600;
		}
		
		.validation-step.complete .step-icon,
		.validation-step.active .step-icon {
			background-color: #0073cf;
		}
		
		.step-label {
			font-size: 0.8rem !important;
		}
		
		.step-connector {
			flex: 1 1 auto;
			border-top: 1px solid #7c7f80;
		}
		
		.validation-message {
			margin-left: 32px;
		}
	</style>
</head>
<body style="width:700px;min-width:700px;margin-left:auto;margin-right:auto;background-color:#ffffff">
	<?php
	if($displayHeader){
		$displayLeftMenu = (isset($collections_download_downloadMenu) ? $collections_download_downloadMenu:false);
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div class="navpath">
			<a href="../../index.php"> <?= $LANG['HOME'] ?> </a> &gt;&gt;
			<a href="#" onclick="closePage(0)"> <?= $LANG['RETURN'] ?> </a> &gt;&gt;
			<b> <?= $LANG['OCC_DOWNLOAD'] ?> </b>
		</div>
		<?php
	}
	?>
	<div style="width:100%; background-color:white;">
		<h1 class="page-heading screen-reader-only"><?= $LANG['COLL_SEARCH_DWNL'] ?></h1>
		<?php
		$canDownload = false;
		$showLoginRequired = false;
		$showValidationRequired = false;
		
		if ($OVERRIDE_DOWNLOAD_LOGIN_REQUIREMENT) {
			$canDownload = true;
		}
		elseif (!$SYMB_UID) {
			$showLoginRequired = true;
		}
		else {
			$accountStatus = getAccountStatus();

			if (isset($accountStatus['expired'])) {
				$showLoginRequired = true;
			} elseif ($accountStatus['ready']) {
				$canDownload = true;
			} else {
				$showValidationRequired = true;
			}
		}
		if ($showLoginRequired) {
			$_SESSION['searchvar'] = $searchVar;
			//$queryStr = 'sourcepage=' . $sourcePage . '&dltype=' . $downloadType . '&taxonFilterCode=' . $taxonFilterCode;
			//header('Location: ../../profile/index.php?refurl=../collections/download/index.php?' . $queryStr);
			?>
			<div id="login-required-container">
				<div style="display: flex; align-items: center; justify-content: flex-start; padding: 16px 20px 12px 20px;">
					<svg class="" focusable="false" viewBox="0 0 24 24" aria-hidden="true">
						<path d="M11 7h2v2h-2zm0 4h2v6h-2zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"></path>
					</svg>
					<h6 style="flex-grow: 1; font-size: 0.775rem; text-transform: uppercase; margin: 0px">Login Required</h6>
					<svg style="font-size: 2.1875rem; color: #ffcb4f;" focusable="false" viewBox="0 0 24 24" aria-hidden="true">
						<path d="M11 7h2v2h-2zm0 4h2v6h-2zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"></path>
					</svg>
				</div>
				<div style="padding: 0px 24px 24px 24px;">
					<hr style="border: none; height: 1px; flex-shrink: 0; background-color: rgba(0, 0, 0, 0.12); margin: 0px 0px 16px 0px;">
					<div>
						<p style="margin: 0px 0px 16px 0px;font-size: 0.8rem; font-family: "Inter", Helvetica, Arial, sans-serif; font-weight: 400; line-height: 1.43;">
							You must sign in or create and validate an account before proceeding.  Navigate to

							<a target="_blank" href="https://data.neonscience.org/myaccount">My Account</a>
							to sign in or create an account.
							<a class="" target="_blank" href="https://www.neonscience.org/about/user-accounts">Learn</a>
							about the benefits of having an account.
						</p>
						<form name="loginRequiredForm" action="../../profile/index.php" method="post">
							<button class="Mui signin-button" type="submit"><span>Sign In</span></button>
							<input name="refurl" type="hidden" value="../collections/download/neonindex.php">
							<input name="dltype" type="hidden" value="<?= $downloadType ?>">
							<input name="taxonFilterCode" type="hidden" value="<?= $taxonFilterCode ?>">
						</form>
					</div>
				</div>
			</div>
		<?php
		}
		if ($showValidationRequired) {
			$step = $accountStatus['step'];
		?>
			<script>
				const accountStep = <?= $accountStatus['step'] ?>;
				
				const messages = {
					1: {
						complete: `
							<span class="step-icon" style="background-color:#0073cf;">✓</span>
							<p class="Mui" style="display:inline-block;">Sign In Completed</p>
						`,
						incomplete: `
							<form name="loginRequiredForm" action="../../profile/index.php" method="post">
								<button class="Mui signin-button" type="submit"><span>Sign In</span></button>
								<input name="refurl" type="hidden" value="../collections/download/neonindex.php">
								<input name="dltype" type="hidden" value="<?= $downloadType ?>">
								<input name="taxonFilterCode" type="hidden" value="<?= $taxonFilterCode ?>">
							</form>
						`
					},
					2: {
						complete: `
							<span class="step-icon" style="background-color:#0073cf;">✓</span>
							<p class="Mui" style="display:inline-block;">Email Verified</p>
						`,
						incomplete: `
							<p class="Mui">
								Verify your email by navigating to
								<a target="_blank" href="https://data.neonscience.org/myaccount">My Account</a>
								and selecting <strong>Send Verification Email</strong>. Follow the link in the email to complete the verification process.
							</p>
						`
					},
					3: {
						incomplete: `
							<p class="Mui">Validate your account by navigating to <a target="_blank" href="https://data.neonscience.org/myaccount">My Account</a> and updating your account information with all required fields.</p>

						`
					}
				};
				
				$(document).ready(function() {
					document.querySelectorAll('.validation-step').forEach(step => {
						step.addEventListener('click', function () {
							const stepNumber = parseInt(this.dataset.step);
							const complete = stepNumber <= accountStep;
				
							document.getElementById('validation-message').innerHTML =
								complete
									? messages[stepNumber].complete
									: messages[stepNumber].incomplete;
						});
					});
					const selectedStep = Math.min(accountStep + 1, 3);
					document.querySelector(`.validation-step[data-step="${selectedStep}"]`).click();
				
				});
			</script>
			<div class="account-validation-card">
				<div class="account-validation-header">
					<svg class="account-validation-icon-small" focusable="false" viewBox="0 0 24 24" aria-hidden="true">
						<path d="M11 7h2v2h-2zm0 4h2v6h-2zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" />
					</svg>
			
					<h6>Account Validation</h6>
			
					<svg class="account-validation-icon-large" focusable="false" viewBox="0 0 24 24" aria-hidden="true">
						<path d="M11 7h2v2h-2zm0 4h2v6h-2zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z" />
					</svg>
				</div>
			
				<div class="account-validation-body">
					<hr>
			
					<p class="Mui">Validate your account to gain access to data and all of the features of the NEON Data Portal.</p class="Mui>
			
					<div class="validation-steps-container">
						<h5 style="margin-bottom: unset;">Account Validation Steps</h5>
						<span><?= $step ?> of 3 completed</span>
			
						<p class="validation-learn-more Mui" style="margin-bottom: 8px;">
							<a target="_blank" href="https://www.neonscience.org/about/user-accounts">Learn</a>
							about account validation.
						</p>
			
						<hr>
			
						<div class="validation-stepper">
							<div class="validation-step <?= $step >= 1 ? 'complete' : 'active' ?>" data-step="1">
								<span class="step-icon">
									<?= $step >= 1 ? '✓' : '1' ?>
								</span>
								<span class="step-label">Sign In</span>
							</div>
							
							<div class="step-connector <?= $step >= 2 ? 'complete' : '' ?>"></div>
							
							<div class="validation-step <?= $step >= 2 ? 'complete' : ($step == 1 ? 'active' : '') ?>" data-step="2">
								<span class="step-icon">
									<?= $step >= 2 ? '✓' : '2' ?>
								</span>
								<span class="step-label">Verify Email</span>
							</div>
							
							<div class="step-connector <?= $step >= 3 ? 'complete' : '' ?>"></div>
							
							<div class="validation-step <?= $step == 3 ? 'active' : '' ?>" data-step="3">
								<span class="step-icon">
									<?= $step >= 3 ? '✓' : '3' ?>
								</span>
								<span class="step-label">Validate Account</span>
							</div>
						</div>
			
						<hr>
			
						<div id="validation-message" class="validation-message">
						</div>
					</div>
				</div>
			</div>
		<?php
		}
		?>
		<div style='margin:30px 15px;'>
			<form name="downloadform" action="downloadhandler.php" method="post" onsubmit="return validateDownloadForm(this);">
				<fieldset>
					<legend>
						<?php
						echo $LANG['DOWNLOAD_SPEC_REC'];
						?>
					</legend>
					<fieldset class="sectionDiv">
						<legend>Which package type do you want?</legend>

						<div class="formElemDiv">

							<input type="radio"
								   name="packageType"
								   id="package-basic"
								   value="basic"
								   checked
								   onchange="setPackageType(this.value)" />

							<label for="package-basic">
								<b>Basic</b>
							</label>

							<div style="margin:5px 0 15px 25px;">
								Includes occurrence records only.
							</div>

							<input type="radio"
								   name="packageType"
								   id="package-expanded"
								   value="expanded"
								   onchange="setPackageType(this.value)" />

							<label for="package-expanded">
								<b>Expanded</b>
							</label>

							<div style="margin:5px 0 15px 25px;">
								Includes the basic package information plus image links, identifications, measurements, material samples, and additional identifiers.
							</div>

						</div>

						<!-- Hidden extension controls -->
						<input type="hidden" name="schema" value="symbiota" />
						<input type="hidden" name="identifications" id="identifications" value="" />
						<input type="hidden" name="images" id="images" value="" />
						<input type="hidden" name="format" value="csv" />
						<input type="hidden" name="cset" value="iso-8859-1" />
						<input type="hidden" name="zip" id="zip" value="" />
						<input type="hidden" name="symbUid" value="<?= $SYMB_UID ?>">

						<?php
						if($dwcManager->hasAttributes()){
							echo '<input type="hidden" name="attributes" id="attributes" value="" />';
						}

						if($dwcManager->hasMaterialSamples()){
							echo '<input type="hidden" name="materialsample" id="materialsample" value="" />';
						}

						if($dwcManager->hasIdentifiers()){
							echo '<input type="hidden" name="identifiers" id="identifiers" value="" />';
						}
						?>
					</fieldset>
					<fieldset class="sectionDiv">
						<legend>Agree to Policies</legend>

						<div class="formElemDiv">

							<div style="margin-bottom:10px;">
								In order to proceed to download NEON data you must agree to the
								<a href="../../misc/sampleguidelines.php" target="_blank">
									Data Usage and Citation Policies</a>.
							</div>

							<input type="checkbox"
								   name="agreepolicy"
								   id="agreepolicy"
								   value="1"
								   onchange="<?= ($canDownload? 'toggleDownloadButton()' : '') ?>" />

						<label for="agreepolicy">
							<strong>I agree to the NEON Data Usage and Citation Policies.</strong>
						</label>

						</div>
					</fieldset>
					<div class="sectionDiv">
						<input name="publicsearch" type="hidden" value="1" />
						<input name="taxonFilterCode" type="hidden" value="<?= $taxonFilterCode; ?>" />
						<input name="sourcepage" type="hidden" value="<?= htmlspecialchars($sourcePage); ?>" />
						<input id="searchVar-input" name="searchvar" type="hidden" value="<?= $searchVar ?>" />
						<button type="submit" name="submitaction" id="downloadbutton" disabled>
							<?= $LANG['DOWNLOAD_DATA'] ?>
							<svg
								class="MuiSvgIcon-root"
								focusable="false"
								viewBox="0 0 24 24"
								aria-hidden="true"
								style="margin-left:8px;width:18px;height:18px;fill:currentColor;vertical-align:middle;"
							>
								<path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7h-2zm-6 .67l2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2z"></path>
							</svg>
						</button>
						<img id="workingcircle" src="../../images/ajax-loader_sm.gif" style="margin-bottom:-4px;width:20px;display:none;" />
					</div>
					<div class="sectionDiv">
						*  <?= $LANG['LIMIT_NOTE'] ?>
					</div>
				</fieldset>
			</form>
		</div>
	</div>
	<?php
	if($displayHeader) include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
