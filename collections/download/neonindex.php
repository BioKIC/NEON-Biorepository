<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/download/index.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/collections/download/index.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/download/index.en.php');

header("Content-Type: text/html; charset=".$CHARSET);

$sourcePage = array_key_exists('sourcepage', $_REQUEST) ? $_REQUEST['sourcepage'] : 'specimen';
$downloadType = array_key_exists('dltype', $_REQUEST) ? $_REQUEST['dltype'] : 'specimen';
$taxonFilterCode = array_key_exists('taxonFilterCode', $_REQUEST) ? filter_var($_REQUEST['taxonFilterCode'], FILTER_SANITIZE_NUMBER_INT) : 0;
$displayHeader = array_key_exists('displayheader', $_REQUEST) ? filter_var($_REQUEST['displayheader'], FILTER_SANITIZE_NUMBER_INT) : 0;
$searchVar = array_key_exists('searchvar', $_REQUEST) ? htmlspecialchars($_REQUEST['searchvar'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE| ENT_QUOTES) : '';

$dwcManager = new DwcArchiverCore();
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

			<?php
			if(!$searchVar){
				?>
				if(sessionStorage.querystr){
					window.location = "index.php?"+sessionStorage.querystr;
				}
				<?php
			}
			?>
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
				search_var: f.searchvar.value,
				source_page: f.sourcepage.value,
				taxon_filter_code: f.taxonFilterCode.value
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

		#login-required-container button{
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
		if(empty($OVERRIDE_DOWNLOAD_LOGIN_REQUIREMENT) && !$SYMB_UID){
			$_SESSION['searchvar'] = $searchVar;
			$queryStr = 'sourcepage=' . $sourcePage . '&dltype=' . $downloadType . '&taxonFilterCode=' . $taxonFilterCode;
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
							<a class="" target="_blank" href="https://cert-data.neonscience.org/myaccount">My Account</a>
							to sign in or create an account.
							<a class="" target="_blank" href="https://www.develop-sr3snxi-di4alr4iwbwyg.us-2.platformsh.site/about/user-accounts">Learn</a>
							about the benefits of having an account.
						</p>
						<form target="../../profile/index.php" method="post">
							<button type="submit"><span>Sign In</span></button>
							<input name="refurl" type="hidden" value="../collections/download/index.php?<?= $sourcePage ?>">
							<input name="dltype" type="hidden" value="<?= $downloadType ?>">
							<input name="taxonFilterCode" type="hidden" value="<?= $taxonFilterCode ?>">
						</form>
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
								   onchange="toggleDownloadButton()" />

						<label for="agreepolicy">
							<strong>I agree to the NEON Data Usage and Citation Policies.</strong>
						</label>

						</div>
					</fieldset>
					<div class="sectionDiv">
						<input name="publicsearch" type="hidden" value="1" />
						<input name="taxonFilterCode" type="hidden" value="<?= $taxonFilterCode; ?>" />
						<input name="sourcepage" type="hidden" value="<?= htmlspecialchars($sourcePage); ?>" />
						<input name="searchvar" type="hidden" value="<?= $searchVar ?>" />
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
