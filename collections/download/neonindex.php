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
		
			// Basic = no extensions, no zip
			if(packageType === 'basic'){
		
				document.getElementById('zip').value = '';
		
				document.getElementById('identifications').value = '';
				document.getElementById('images').value = '';
		
				if(document.getElementById('attributes')){
					document.getElementById('attributes').value = '';
				}
		
				if(document.getElementById('materialsample')){
					document.getElementById('materialsample').value = '';
				}
		
				if(document.getElementById('identifiers')){
					document.getElementById('identifiers').value = '';
				}
			}
		
			// Expanded = all extensions + zip
			if(packageType === 'expanded'){
		
				document.getElementById('zip').value = '1';
		
				document.getElementById('identifications').value = '1';
				document.getElementById('images').value = '1';
		
				if(document.getElementById('attributes')){
					document.getElementById('attributes').value = '1';
				}
		
				if(document.getElementById('materialsample')){
					document.getElementById('materialsample').value = '1';
				}
		
				if(document.getElementById('identifiers')){
					document.getElementById('identifiers').value = '1';
				}
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
		<h1 class="page-heading"><?= $LANG['COLL_SEARCH_DWNL'] ?></h1>
		<div style="margin:15px 0px;">
		</div>
		<div style='margin:30px 15px;'>
			<form name="downloadform" action="downloadhandler.php" method="post" onsubmit="return validateDownloadForm(this);">
				<fieldset>
					<legend>
						<?php
						echo $LANG['DOWNLOAD_SPEC_REC'];
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
								Includes the basic package information plus image links, identifications, attributes, material samples, and additional identifiers.
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
