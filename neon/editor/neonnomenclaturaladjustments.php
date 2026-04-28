<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorDeterminations.php');
include_once($SERVER_ROOT.'/neon/classes/NeonEditor.php');

if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/batchdeterminations.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/batchdeterminations.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/batchdeterminations.en.php');
header('Content-Type: text/html; charset=' . $CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../neon/editor/neonnomenclaturaladjustments.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$occManager = new OccurrenceEditorDeterminations();
$editManager = new NeonEditor();
$occManager->getCollMap();

$isEditor = 0;
if($IS_ADMIN ){
	$isEditor = 1;
}

$statusStr = '';
$occidArr = $_REQUEST['occid'] ?? [];

if($isEditor && !empty($occidArr) && is_array($occidArr)){
	foreach($occidArr as $occid){
		$cleanOccid = filter_var($occid, FILTER_SANITIZE_NUMBER_INT);

		$occManager->setOccId($cleanOccid);

		$input = $_REQUEST;
		$input['occid'] = $cleanOccid;
		unset($input['formsubmit']);

		$editManager->addNEONNomAdjustment($input);
	}
}

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
	    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
		<title><?php echo $DEFAULT_TITLE.' '.$LANG['BATCH_DETERS']; ?></title>
		<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
		<script type="text/javascript">
			function initScinameAutocomplete(f){
				$( f.sciname ).autocomplete({
					source: "<?php echo $CLIENT_ROOT; ?>/collections/editor/rpc/getspeciessuggest.php",
					minLength: 3,
					change: function(event, ui) {
					}
				});
			}

			function initDetAutocomplete(f){
				$( f.sciname ).autocomplete({
					source: "<?php echo $CLIENT_ROOT; ?>/collections/editor/rpc/getspeciessuggest.php",
					minLength: 3,
					change: function(event, ui) {
						if(f.sciname.value){
							pauseSubmit = true;
							verifyDetSciName(f);
						}
						else{
							f.scientificnameauthorship.value = "";
							f.family.value = "";
							f.tidinterpreted.value = "";
						}
					}
				});
			}

			function submitAccForm(f){
				var workingObj = document.getElementById("workingcircle");
				workingObj.style.display = "inline";

				var allCatNum = f.allcatnum.checked ? 1 : 0;

				$.ajax({
					type: "POST",
					url: "<?php echo $CLIENT_ROOT; ?>/neon/editor/rpc/getnewdetitem.php",
					dataType: "json",
					data: {
						catalognumber: f.catalognumber.value,
						allcatnum: allCatNum,
						sciname: f.sciname.value,
						fieldsite: f.fieldsite.value
					}
				})
				.done(function(retStr) {

					console.log("SUCCESS:", retStr);

					if (retStr && Object.keys(retStr).length > 0) {
						for (var occid in retStr) {
							var occObj = retStr[occid];

							var trNode = createNewTableRow(occid, occObj);
							document.getElementById("catrecordstbody")
								.insertBefore(trNode, document.getElementById("catrecordstbody").firstChild);
						}

						document.getElementById("accrecordlistdviv").style.display = "block";
						setDefaultDeterminationValues();

					} else {
						alert("<?php echo $LANG['NO_RECORDS']; ?>");
					}

				})
				.fail(function(xhr, status, error){
					console.log("AJAX FAILED:", status, error);
					console.log(xhr.responseText);
					alert("AJAX error (see console)");
				})
				.always(function(){
					workingObj.style.display = "none";
				});

				if(f.catalognumber.value != ""){
					f.catalognumber.value = '';
					f.catalognumber.focus();
				}

				return false;
			}

			function checkCatalogNumber(catNum){
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					if(dbElements[i].value == catNum) return true;
				}
				return false;
			}

			function createNewTableRow(occid, occObj){
				var trNode = document.createElement("tr");
				var inputNode = document.createElement("input");
				inputNode.setAttribute("type", "checkbox");
				inputNode.setAttribute("name", "occid[]");
				inputNode.setAttribute("value", occid);
				inputNode.setAttribute("checked", "checked");
				var tdNode1 = document.createElement("td");
				tdNode1.appendChild(inputNode);
				trNode.appendChild(tdNode1);
				var tdNode2 = document.createElement("td");
				var anchor1 = document.createElement("a");
				anchor1.setAttribute("href","#");
				anchor1.setAttribute("onclick","openIndPopup("+occid+"); return false;");
				if(occObj["cn"]) anchor1.innerHTML = occObj["cn"];
				else anchor1.innerHTML = "[no catalog number]";
				tdNode2.appendChild(anchor1);
				var anchor2 = document.createElement("a");
				anchor2.setAttribute("href","#");

				tdNode2.appendChild(anchor2);
				trNode.appendChild(tdNode2);
				var tdNode3 = document.createElement("td");
				tdNode3.appendChild(document.createTextNode(occObj["sn"]));
				trNode.appendChild(tdNode3);
				var tdNode4 = document.createElement("td");
				tdNode4.appendChild(document.createTextNode(occObj["coll"]+'; '+occObj["loc"]));
				trNode.appendChild(tdNode4);
				return trNode;
			}

			function clearAccForm(f){
				if(confirm("<?php echo $LANG['CLEAR_FORM_RESETS']; ?>") == true){
					document.getElementById("accrecordlistdviv").style.display = "none";
					document.getElementById("catrecordstbody").innerHTML = '';
					f.catalognumber.value = '';
					f.sciname.value = '';
					f.fieldsite.selectedIndex = -1;
				}
			}

			function validateSelectForm(f){
				var specNotSelected = true;
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					if(dbElement.checked){
						specNotSelected = false;
						break;
					}
				}
				if(specNotSelected){
					alert("<?php echo $LANG['SELECT_ONE']; ?>");
					return false;
				}

				if(f.sciname.value == ""){
					alert("<?php echo $LANG['SCINAME_NEEDS_VALUE']; ?>");
					return false;
				}
				if(f.identifiedby.value == ""){
					alert("<?php echo $LANG['DETERMINER_NEEDS_VALUE']; ?>");
					return false;
				}
				if(f.dateidentified.value == ""){
					alert("<?php echo $LANG['DET_DATE_NEEDS_VALUE']; ?>");
					return false;
				}

				const formData = new FormData(f);
				for (let [key, value] of formData.entries()) {
					console.log(key, value);
				}
				return true;
			}

			function selectAll(cb){
				boxesChecked = true;
				if(!cb.checked){
					boxesChecked = false;
				}
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					dbElement.checked = boxesChecked;
				}
			}

			function verifyDetSciName(f){
				$.ajax({
					type: "POST",
					url: "<?php echo $CLIENT_ROOT; ?>/collections/editor/rpc/verifysciname.php",
					dataType: "json",
					data: { term: f.sciname.value }
				}).done(function( data ) {
					if(data){
						f.scientificnameauthorship.value = data.author;
						f.family.value = data.family;
						f.tidinterpreted.value = data.tid;
					}
					else{
						alert("<?php echo $LANG['WARNING_TAXON_NOT_FOUND']; ?>");
						f.scientificnameauthorship.value = "";
						f.family.value = "";
						f.tidinterpreted.value = "";
					}
				});
			}

			function openIndPopup(occid){
				openPopup('../../collections/individual/index.php?occid=' + occid);
			}

			function openEditorPopup(occid){
				openPopup('occurrenceeditor.php?occid=' + occid);
			}

			function openPopup(urlStr){
				var wWidth = 900;
				if(document.body.offsetWidth) wWidth = document.body.offsetWidth*0.9;
				if(wWidth > 1200) wWidth = 1200;
				newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
				if (newWindow.opener == null) newWindow.opener = self;
				return false;
			}

			function setDefaultDeterminationValues(){
				var f = document.forms["accselectform"];
				if(!f) return;
				if(f.identifiedby && !f.identifiedby.value){
					f.identifiedby.value = "Nomenclatural Adjustment";
				}
				if(f.dateidentified && !f.dateidentified.value){
					var today = new Date();
					var yyyy = today.getFullYear();
					var mm = String(today.getMonth() + 1).padStart(2, '0');
					var dd = String(today.getDate()).padStart(2, '0');
					f.dateidentified.value = yyyy + "-" + mm + "-" + dd;
				}
			}
		</script>
		<style>
			.top-breathing-room-sm-px {
				margin-top: 5px;
			}
			.left-breathing-room-rel-lg {
				margin-left: 2em;
			}
			.fieldset-like > div,
			fieldset > div {
				margin-left: 12px;
			}
		</style>
	</head>
	<body>
	<?php
	$displayLeftMenu = (isset($collections_batchdeterminationsMenu) ? $collections_batchdeterminationsMenu : false);
	include($SERVER_ROOT . '/includes/header.php');
	?>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading">Batch Nomenclatural Adjustments</h1>
		<?php
		if($isEditor){
			?>
			<div>
				<section class="fieldset-like">
					<h2> <span> <?php echo $LANG['DEFINE_RECORDSET']; ?> </span> </h2>
					<div>
						<?php echo $LANG['RECORDSET_EXPLAIN']; ?>
					</div>
					<div style="margin-top:15px;">
						<form name="accqueryform" action="neonnomenclaturaladjustments.php" method="post" onsubmit="return submitAccForm(this);">
							<div>
								<b>Field Site/Domain:</b>
								<select id="fieldsite" name="fieldsite" style="width:260px;">
									<option>--------------------</option>
									<option value="34">ABBY</option>
									<option value="96">ARIK</option>
									<option value="97">BARC</option>
									<option value="64">BARR</option>
									<option value="33">BART</option>
									<option value="98">BIGC</option>
									<option value="65">BLAN</option>
									<option value="99">BLDE</option>
									<option value="100">BLUE</option>
									<option value="101">BLWA</option>
									<option value="35">BONA</option>
									<option value="102">CARI</option>
									<option value="36">CLBJ</option>
									<option value="103">COMO</option>
									<option value="67">CPER</option>
									<option value="104">CRAM</option>
									<option value="105">CUPE</option>
									<option value="67">DCFS</option>
									<option value="37">DEJU</option>
									<option value="38">DELA</option>
									<option value="39">DSNY</option>
									<option value="106">FLNT</option>
									<option value="40">GRSM</option>
									<option value="41">GUAN</option>
									<option value="42">HARV</option>
									<option value="43">HEAL</option>
									<option value="108">HOPB</option>
									<option value="44">JERC</option>
									<option value="45">JORN</option>
									<option value="77">KONA</option>
									<option value="68">KONZ</option>
									<option value="109">KING</option>
									<option value="110">LECO</option>
									<option value="46">LENO</option>
									<option value="111">LEWI</option>
									<option value="112">LIRO</option>
									<option value="113">MART</option>
									<option value="114">MAYF</option>
									<option value="115">MCDI</option>
									<option value="116">MCRA</option>
									<option value="47">MLBS</option>
									<option value="48">MOAB</option>
									<option value="49">NIWO</option>
									<option value="70">NOGP</option>
									<option value="71">OAES</option>
									<option value="50">ONAQ</option>
									<option value="51">ORNL</option>
									<option value="52">OSBS</option>
									<option value="118">POSE</option>
									<option value="119">PRIN</option>
									<option value="120">PRLA</option>
									<option value="121">PRPO</option>
									<option value="122">REDB</option>
									<option value="75">RMNP</option>
									<option value="53">SCBI</option>
									<option value="54">SERC</option>
									<option value="72">SJER</option>
									<option value="55">SOAP</option>
									<option value="56">SRER</option>
									<option value="57">STEI</option>
									<option value="73">STER</option>
									<option value="124">SUGG</option>
									<option value="125">SYCA</option>
									<option value="58">TALL</option>
									<option value="59">TEAK</option>
									<option value="126">TECR</option>
									<option value="127">TOMB</option>
									<option value="128">TOOK</option>
									<option value="60">TOOL</option>
									<option value="61">TREE</option>
									<option value="62">UKFS</option>
									<option value="63">UNDE</option>
									<option value="74">WOOD</option>
									<option value="78">WREF</option>
									<option value="129">WALK</option>
									<option value="130">WLOU</option>
									<option value="131">YELL</option>
									<option value="2">D01</option>
									<option value="13">D02</option>
									<option value="7">D03</option>
									<option value="9">D04</option>
									<option value="15">D05</option>
									<option value="17">D06</option>
									<option value="8">D07</option>
									<option value="6">D08</option>
									<option value="19">D09</option>
									<option value="18">D10</option>
									<option value="5">D11</option>
									<option value="1">D12</option>
									<option value="11">D13</option>
									<option value="10">D14</option>
									<option value="12">D15</option>
									<option value="3">D16</option>
									<option value="14">D17</option>
									<option value="16">D18</option>
									<option value="4">D19</option>
									<option value="20">D20</option>
								</select>
							</div>
							<section class="flex-form" style="align-items: center; gap:0.5rem; margin-bottom: 1rem">
								<div style="margin: 0; display:flex; align-items: center; gap:0.25rem">
									<label for="catalognumber"><?php echo $LANG['CATNUM']; ?>:</label>
									<input style="margin: 0" name="catalognumber" id="catalognumber" type="text" style="border-color:green;width:200px;" />
								</div>
								<div style="margin: 0">
									<input name="allcatnum" id="allcatnum" type="checkbox" checked /> <label for="allcatnum"><?php echo $LANG['TARGET_ALL']; ?></label>
								</div>
							</section>
							<div style="margin-bottom: 1rem; display:flex; align-items: center; gap:0.25rem">
								<label for="nomsciname"><?php echo $LANG['TAXON']; ?>:</label>
								<input style="margin:0; width:260px;" type="text" id="nomsciname" name="sciname" onfocus="initScinameAutocomplete(this.form)" />
							</div>
							<section class="flex-form">
								<div style="margin: 0">
									<button name="addrecord" type="submit"><?php echo $LANG['ADD_RECORDS']; ?></button>
									<img id="workingcircle" src="../../images/workingcircle.gif" style="display:none;" alt="progress is being made" />
								</div>

							</section>
						</form>
					</div>
					<?php
					if($statusStr){
						echo '<div style="margin:30px 20px;">';
						echo '<div style="color:orange;font-weight:bold;">'.$statusStr.'</div>';
						echo '<div style="margin-top:10px;"><a href="neon/reports/annotationmanager.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($LANG['DISPLAY_QUEUE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></div>';
						echo '</div>';
					}
					?>
				</section>
				<div id="accrecordlistdviv" style="display:none;">
					<form name="accselectform" id="accselectform" action="neonnomenclaturaladjustments.php" method="post" onsubmit="return validateSelectForm(this);">
						<div style="margin-top: 15px; margin-left: 10px;">
						</div>
						<table class="styledtable">
							<thead>
								<tr>
									<th style="width:25px;text-align:center;">
										<input type="checkbox" onclick="selectAll(this)">
									</th>
									<th style="width:125px;text-align:center;"><?php echo $LANG['CATNUM']; ?></th>
									<th style="width:300px;text-align:center;"><?php echo $LANG['SCINAME']; ?></th>
									<th style="text-align:center;"><?php echo $LANG['COLLECTOR_LOCALITY']; ?></th>
								</tr>
							</thead>
							<tbody id="catrecordstbody"></tbody>
						</table>
						<div id="newdetdiv" style="">
							<fieldset style="margin: 15px 15px 0px 15px;padding:15px;">
								<legend><b><?php echo $LANG['NEW_DET_DETAILS']; ?></b></legend>
								<div style='margin:3px;'>
									<b><?php echo $LANG['SCINAME']; ?>:</b>
									<input type="text" id="dafsciname" name="sciname" style="background-color:lightyellow;width:350px;" onfocus="initDetAutocomplete(this.form)" />
									<input type="hidden" id="daftidinterpreted" name="tidinterpreted" value="" />
								</div>
								<!-- <div id="idQualifierDiv" style='margin:3px;clear:both'>
									<b><?php echo $LANG['ID_QUALIFIER']; ?>:</b>
									<input type="text" name="identificationqualifier" title="e.g. cf, aff, etc" />
								</div> -->
								<div style='margin:3px;'>
									<b><?php echo $LANG['FAMILY']; ?>:</b>
									<input type="text" name="family" style="width:200px;" />
								</div>
								<div style='margin:3px;'>
									<b><?php echo $LANG['AUTHOR']; ?>:</b>
									<input type="text" name="scientificnameauthorship" style="width:200px;" />
								</div>
								<div id="codDiv" style='margin:3px;'>
									<b><?php echo $LANG['CONFIDENCE']; ?>:</b>
									<select name="confidenceranking">
										<option value="8"><?php echo $LANG['HIGH']; ?></option>
										<option value="5" selected><?php echo $LANG['MEDIUM']; ?></option>
										<option value="2"><?php echo $LANG['LOW']; ?></option>
									</select>
								</div>
								<div id="identifiedByDiv" style='margin:3px;'>
									<b><?php echo $LANG['IDENTIFIED_BY']; ?>:</b>
									<input type="text" name="identifiedby" id="identifiedby" style="background-color:lightyellow;width:200px;" />
								</div>
								<div id="dateIdentifiedDiv" style='margin:3px;'>
									<b><?php echo $LANG['DATE_IDENTIFIED']; ?>:</b>
									<input type="text" name="dateidentified" id="dateidentified" style="background-color:lightyellow;" onchange="detDateChanged(this.form);" />
								</div>
								<div style='margin:3px;'>
									<b><?php echo $LANG['ID_REFERENCES']; ?>:</b>
									<input type="text" name="identificationreferences" style="width:350px;" />
								</div>
								<div style='margin:3px;'>
									<b><?php echo $LANG['ID_REMARKS']; ?>:</b>
									<input type="text" name="identificationremarks" style="width:350px;" />
								</div>
								<div style='margin:3px;'>
									<b><?php echo $LANG['TAXON_REMARKS']; ?>:</b>
									<input type="text" name="taxonremarks" style="width:350px;" />
								</div>
								<div id="makeCurrentDiv" style='margin:3px;'>
									<input type="checkbox" name="isCurrent" value="1" checked /> <?php echo $LANG['MAKE_CURRENT']; ?>
								</div>
								<div style='margin:3px;'>
									<input type="checkbox" name="printqueue" value="1" checked /> <?php echo $LANG['ADD_PRINT_QUEUE']; ?>
									<a href="../reports/annotationmanager.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" target="_blank"><img src="../../images/list.png" style="width:1.2em" title="<?php echo htmlspecialchars($LANG['DISPLAY_QUEUE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" /></a>
								</div>
								<div style='margin:15px;'>
									<div style="float:left;">
										<input name="createduid" type="hidden" value="<?php echo $SYMB_UID; ?>" />
										<button type="submit" name="formsubmit" value="Add New Determinations"><?php echo $LANG['ADD_DETERS']; ?></button>
									</div>
								</div>
							</fieldset>
						</div>
					</form>
				</div>
			</div>
			<?php
		}
		else{
			?>
			<div style="font-weight:bold;margin:20px;font-weight:150%;">
				<?php echo $LANG['NO_PERMISSIONS']; ?>
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
