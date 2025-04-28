<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/IgsnManager.php');
include_once($SERVER_ROOT.'/neon/classes/OccurrenceSesar.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../profile/index.php?refurl='.$CLIENT_ROOT.'/neon/igsncontrol.php?'.$_SERVER['QUERY_STRING']);

$recTarget = array_key_exists('recTarget',$_POST)?$_POST['recTarget']:'';
$resetSession = array_key_exists('resetSession',$_POST) && $_POST['resetSession'] == 1?1:0;
$startIndex = array_key_exists('startIndex',$_POST)?$_POST['startIndex']:'';
$limit = array_key_exists('limit',$_POST)?$_POST['limit']:1000;
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';

//Sanitation
$limit = filter_var($limit, FILTER_SANITIZE_NUMBER_INT);

$igsnManager = new IgsnManager();
$occurrenceSesar = new OccurrenceSesar();

if (!$occurrenceSesar->getProductionMode()) {
    $accesstoken = $occurrenceSesar->getDevelopmentAccessToken($SYMB_UID);
    $refreshtoken = $occurrenceSesar->getDevelopmentRefreshToken($SYMB_UID);
	$modeLabel = " (Development)";
} else {
    $accesstoken = $occurrenceSesar->getAccessToken($SYMB_UID);
    $refreshtoken = $occurrenceSesar->getRefreshToken($SYMB_UID);
	$modeLabel = "";
}

$isEditor = false;
if($IS_ADMIN) $isEditor = true;

$statusStr = '';
if($isEditor){
	if($action == 'export'){
		$markAsSubmitted = array_key_exists('markAsSubmitted',$_POST) && $_POST['markAsSubmitted'] == 1?true:false;
		if($igsnManager->exportReport($recTarget, $startIndex, $limit, $markAsSubmitted)){
			exit;
		}
		else{
			$statusStr = 'Unable to create export. Are you sure there are unsynchronized records?';
		}
	}
}
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> IGSN Manager</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
	<?php
	$activateJQuery = true;
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="../js/jquery-3.2.1.min.js" type="text/javascript"></script>
	<script src="../js/jquery-ui-1.12.1/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		function verifySync(f){
			if(f.recTarget.value == 'notsubmitted'){
				alert("Unsubmitted records cannot be synchronized");
				return false;
			}
			return true;
		}

		function checkMarkAsSubmitted(cbElem){
			let f = cbElem.form;
			if(cbElem.checked){
				if(f.recTarget.value != "notsubmitted") alert("Only 'Not Submitted' records can be tagged as Submitted to NEON");
			}
		}
		function saveTokens(form) {
			const accessToken = $(form.accessToken).val().trim();
			const refreshToken = $(form.refreshToken).val().trim();
		
			$.post('collections/admin/rpc/savetokens.php', {
				accessToken: accessToken,
				refreshToken: refreshToken
			})
			.done(function(response) {
				if (response.success) {
					alert('Tokens saved successfully.');
				} else {
					alert('Failed to save tokens: ' + response.message);
				}
			})
			.fail(function(xhr, status, error) {
				console.error('Error saving tokens:', error);
				alert('An error occurred while saving tokens.');
			});
		}

	function validateTokens(form) {
		const accessToken = $(form.accessToken).val().trim();
		const refreshToken = $(form.refreshToken).val().trim();
	
		$.post('collections/admin/rpc/validatetokens.php', {
			accessToken: accessToken,
			refreshToken: refreshToken
		})
		.done(function(response) {
			alert(response.message);
		})
		.fail(function(xhr, status, error) {
			console.error('Token validation error:', error);
			alert('An error occurred while validating tokens.');
		});
	}

	function refreshTokens(form) {
		const refreshToken = $(form.refreshToken).val().trim();
	
		if (!refreshToken) {
			alert('Refresh token is missing.');
			return;
		}
	
		$.post('collections/admin/rpc/refreshtokens.php', {
			refreshToken: refreshToken
		})
		.done(function(response) {
			if (response.success) {
				$(form.accessToken).val(response.newAccessToken);
				$(form.refreshToken).val(response.newRefreshToken);
				alert('Tokens refreshed successfully.');
	
				saveTokens(form);
			} else {
				alert('Failed to refresh tokens: ' + response.message);
			}
		})
		.fail(function(xhr, status, error) {
			console.error('Refresh token error:', error);
			alert('An error occurred while refreshing the token.');
		});
	}
	
	function toggleTokenInfo(toggleElem) {
		const content = toggleElem.nextElementSibling;
		const arrow = toggleElem.querySelector('.arrow');
		content.classList.toggle('open');
		arrow.innerHTML = content.classList.contains('open') ? '&#9650;' : '&#9660;';
	}

	</script>

	<style type="text/css">
		fieldset{ padding:15px }
		legend{ font-weight:bold; }
		.fieldGroupDiv{ clear:both; margin:10px; }
		.fieldDiv{ float:left; }
		label{ font-weight: bold; }
		button{ width: 250px; }

		.token-info-toggle {
			cursor: pointer;
			display: flex;
			align-items: center;
			font-weight: bold;
		}
		.token-info-toggle span.arrow {
			margin-left: 10px;
			transition: transform 0.2s ease;
		}
		.token-info-content {
			display: none;
			margin-top: 10px;
		}
		.token-info-content.open {
			display: block;
		}
		.token-info-box {
			border: 1px solid #ccc;
			padding: 20px;
			margin-bottom: 10px;
			background-color: #f9f9f9;
			border-radius: 8px;
		}
	</style>
</head>
<body>
<?php
$displayLeftMenu = false;
include($SERVER_ROOT.'/includes/header.php');
?>
<div class="navpath">
	<a href="../index.php">Home</a> &gt;&gt;
	<a href="index.php">NEON Biorepository Tools</a> &gt;&gt;
	<a href="igsncontrol.php"><b>NEON IGSN Control Panel</b></a>
</div>
<div id="innertext">
	<?php
	if($isEditor){
		if(!$occurrenceSesar->getProductionMode()){
			echo '<h2 style="color:orange">-- In Development Mode --</h2>';
		}
		if($statusStr){
			echo '<div style="color:red">'.$statusStr.'</div>';
		}
		if($action != 'syncIGSNs'){
			?>
			<fieldset>
				<legend>IGSN Registration</legend>
				<div class="token-info-box">
					<div class="token-info-toggle" onclick="toggleTokenInfo(this)">
						Using JWT Tokens for IGSN Registration
						<span class="arrow">&#9660;</span>
					</div>
					<div class="token-info-content">
						<p>
							To register IGSNs via the SESAR API, you need to authenticate using your unique <strong>access token</strong>.
						</p>
						<ul>
							<li><strong>Access Token:</strong> Grants temporary access to the registration services. It expires after one day.</li>
							<li><strong>Refresh Token:</strong> Used to generate a new access token when the old one expires. It lasts for up to one year. </li>
						</ul>
						<h4>How to Use the Token Tools</h4>
						<ol>
							<li>
								<strong>Validate Access Token:</strong> Click <em>Validate Access Token</em> to check if your current access token is valid.
							</li>
							<li>
								<strong>Refresh Tokens:</strong> If validation fails, click <em>Refresh Tokens</em> to use your refresh token to get a new access token. You will also get a new refresh token.
								<ul>
									<li>
										If your refresh token has expired (after one year), visit the MySESAR
										<a href="https://app.geosamples.org/" target="_blank"><strong>Production server</strong></a> 
										or the 
										<a href="https://app-sandbox.geosamples.org/" target="_blank"><strong>Development server</strong></a> 
										to log in and generate a new token pair.
									</li>
								</ul>
							</li>
							<li>
								<strong>Save Tokens:</strong> After manually editing your access or refresh tokens, click <em>Save Tokens</em> to store the updated values.
							</li>
						</ol>
						<h4>Development vs. Production Mode</h4>
						<p>
							The SESAR system operates with two separate environments: a <strong>production server</strong> and a <strong>sandbox (development) server</strong>. 
							When using development mode, all token operations are directed to the sandbox server (<a href="https://app-sandbox.geosamples.org/" target="_blank">https://app-sandbox.geosamples.org/</a>), and a separate set of development tokens is used. 
							In production mode, operations connect to the main SESAR server (<a href="https://app.geosamples.org/" target="_blank">https://app.geosamples.org/</a>).
						</p>
						<p>
							The interface will automatically detect whether you are in development or production mode and display the appropriate access and refresh tokens accordingly. These are stored separately in the MySQL database.
						</p>
					</div>
				</div>				
				
				
				
				<form name="tokenManagement">
					<legend>Current User Token Pair:</legend>
					<div style="margin-left: 1em">
					<p>
						<div>
							<span class="form-label">Access token<?php echo $modeLabel; ?>:</span>
							<div>
								<textarea name="accessToken" rows="4" style="width: 95%"><?php echo htmlspecialchars($accesstoken); ?></textarea>
							</div>
						</div>
						<div>
							 <span class="form-label">Refresh token<?php echo $modeLabel; ?>:</span>
							<div>
								<textarea name="refreshToken" rows="4" style="width: 95%"><?php echo htmlspecialchars($refreshtoken); ?></textarea>
							</div>
						</div>
					</p>
					</div>
					<div>
						<button id="validate-button" type="button" onclick="validateTokens(this.form)">Validate Access Token</button>
						<button id="refresh-button" type="button" onclick="refreshTokens(this.form)">Refresh Tokens</button>
						<button id="save-button" type="button" onclick="saveTokens(this.form)">Save Tokens</button>
						<button id="assign-button" type="button" onclick="window.location.href='./collections/admin/igsnmanagement.php'">Assign IGSN IDs</button>
					</div>
				</form>
			</fieldset>
			<?php
		}
		if($action == 'syncIGSNs'){
			echo '<fieldset>';
			echo '<legend>Action Panel: IGSN synchronization</legend>';
			echo '<ul>';
			$startIndex = $igsnManager->synchronizeIgsn($recTarget, $startIndex ,$limit, $resetSession);
			echo '<li><a href="igsncontrol.php">Return to IGSN report listing</a></li>';
			echo '</ul>';
			echo '</fieldset>';
		}
		?>
		<fieldset>
			<legend>SESAR Synchronization</legend>
			<div style="margin-bottom:10px;">
				This tool will harvest all NEON IGSNs from the SESAR system, insert them into a local tables, and then run comparisons with the IGSNs stored within the Biorepo to
				locate inconsistencies. In particular, it is looking for multiple IGSNs assigned to a single specimen, IGSNs within SESAR and not the Biorepo, and in Biorepo and not in SESAR.
			</div>
			<form name="" method="post" action="collections/admin/igsnverification.php">
				<input name="namespace" type="hidden" value="NEO" />
				<button name="formsubmit" type="submit" value="verifysesar">NEON IGSN Verification</button>
			</form>
		</fieldset>
		<fieldset>
			<legend>IGSN Synchronization with NEON</legend>
			<div style="margin-bottom:10px;">
				Displays occurrence counts that have been synchronized with the central NEON System.
				After IGSNs have been integrated into NEON system, run the synchronization tool to adjust the report.
				Previously unsynchronized records are rechecked within a session. The session will have to be reset to perform additional rechecks.<br>
				Export function will produce a report containing unchecked or unsynchronized records limited by the transaction limit.
				Ideally, reports submitted to NEON should not contain more than 5000 records.
			</div>
			<div style="">
				<ul>
					<?php
					$reportArr = $igsnManager->getIgsnSynchronizationReport();
					if($reportArr){
						echo '<div><label>Not submitted: </label>'.(isset($reportArr['x'])?$reportArr['x']:'0').'</div>';
						echo '<div><label>Submitted but unchecked: </label>'.(isset($reportArr['3'])?$reportArr['3']:'0').'</div>';
						echo '<div><label>Unsynchronized: </label>'.(isset($reportArr[0])?$reportArr[0]:'0').'</div>';
						if(isset($reportArr[100])) echo '<div><label>Unsynchronized (current session): </label>'.$reportArr[100].'</div>';
						echo '<div><label>Synchronized: </label>'.(isset($reportArr[1])?$reportArr[1]:'0').'</div>';
						echo '<div><label>Mismatched: </label>'.(isset($reportArr[2])?$reportArr[2]:'0').'</div>';
						echo '<div><label>Data return errors: </label>'.(isset($reportArr[10])?$reportArr[10]:'0').'</div>';
					}
					?>
				</ul>
				<div style="">
					<form name="igsnsyncform" method="post" action="igsncontrol.php">
						<div style="clear:both;">
							<div style="float:left; margin-left:35px; margin-right:5px"><label>Target:</label> </div>
							<div style="float:left;">
								<input name="recTarget" type="radio" value="notsubmitted" <?php echo ($recTarget == 'notsubmitted'?'checked':''); ?> /> Not submitted<br/>
								<input name="recTarget" type="radio" value="unchecked" <?php echo ($recTarget == 'unchecked'?'checked':''); ?> /> Unchecked<br/>
								<input name="recTarget" type="radio" value="unsynchronized" <?php echo (!$recTarget || $recTarget == 'unsynchronized'?'checked':''); ?> /> Unsynchronized records<br/>
							</div>
						</div>
						<div style="clear:both;padding-top:10px;margin-left:35px;">
							<input name="resetSession" type="checkbox" value="1" >
							<label>reset session</label>
						</div>
						<div style="clear:both;padding-top:10px;margin-left:35px;">
							<label>Start at IGSN:</label> <input name="startIndex" type="text" value="<?php echo htmlspecialchars($startIndex); ?>" />
						</div>
						<div style="clear:both;padding-top:10px;margin-left:35px;">
							<label>Transaction limit:</label> <input name="limit" type="number" value="<?php echo $limit; ?>" required />
						</div>
						<div style="clear:both;padding:20px 35px;">
							<div style="float:left;"><button name="action" type="submit" value="syncIGSNs" onclick="return verifySync(this.form)">Synchronize Records</button></div>
							<div style="float:left;margin-left:20px">
								<button name="action" type="submit" value="export" style="margin-bottom:0px">Export Report</button><br>
								<span style="margin-left:15px"><input name="markAsSubmitted" type="checkbox" value="1" onchange="checkMarkAsSubmitted(this)"> mark as submitted</span>
							</div>
							<div style="float:left;margin-left:20px">
								<button name="action" type="submit" value="refresh" style="margin-bottom:0px">Refresh Statistics</button>
							</div>
						</div>
					</form>
					<div style="clear:both;margin-left:40px">
						<a href="http://data.neonscience.org/web/external-lab-ingest" target="_blank">NEON report submission page</a>
					</div>
				</div>
			</div>
		</fieldset>
		<?php
	}
	else{
		?>
		<div style='font-weight:bold;margin:30px;'>
			You do not have permissions to access occurrence harvester
		</div>
		<?php
	}
	?>
</div>
<?php
include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>