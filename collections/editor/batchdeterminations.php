<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorDeterminations.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/batchdeterminations.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/batchdeterminations.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/batchdeterminations.en.php');
header('Content-Type: text/html; charset=' . $CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/editor/batchdeterminations.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid = filter_var(($_REQUEST['collid'] ?? 0), FILTER_SANITIZE_NUMBER_INT);
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$occManager = new OccurrenceEditorDeterminations();
$occManager->setCollId($collid);
$occManager->getCollMap();

$isEditor = 0;
if($IS_ADMIN || (array_key_exists('CollAdmin', $USER_RIGHTS) && in_array($collid, $USER_RIGHTS['CollAdmin']))){
	$isEditor = 1;
}
elseif(array_key_exists('CollEditor', $USER_RIGHTS) && in_array($collid, $USER_RIGHTS['CollEditor'])){
	$isEditor = 1;
}
$statusStr = '';
if($isEditor){
	if($formSubmit == 'Add New Determinations'){
		$occidArr = $_REQUEST['occid'];
		foreach($occidArr as $k){
			$occManager->setOccId(filter_var($k, FILTER_SANITIZE_NUMBER_INT));
			$occManager->addDetermination($_REQUEST,$isEditor);
		}
		$statusStr = 'SUCCESS: ' . count($occidArr) . ' annotations submitted';
	}
	elseif($formSubmit == 'Adjust Nomenclature'){
		$occidArr = $_REQUEST['occid'];
		foreach($occidArr as $k){
			$occManager->setOccId(filter_var($k, FILTER_SANITIZE_NUMBER_INT));
			$occManager->addNomAdjustment($_REQUEST,$isEditor);
		}
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
					source: "rpc/getspeciessuggest.php",
					minLength: 3,
					change: function(event, ui) {
					}
				});
			}

			function initDetAutocomplete(f){
				$( f.sciname ).autocomplete({
					source: "rpc/getspeciessuggest.php",
					minLength: 3,
					change: function(event, ui) {
						if(f.sciname.value){
							pauseSubmit = true;
							verifyDetSciName(f);
						}
						else{
							f.scientificnameauthorship.value = "";
							f.family.value = "";
							f.tidtoadd.value = "";
						}
					}
				});
			}

			function submitAccForm(f){
				var workingObj = document.getElementById("workingcircle");
				workingObj.style.display = "inline"
				var allCatNum = 0;
				if(f.allcatnum.checked) allCatNum = 1;

				$.ajax({
					type: "POST",
					url: "rpc/getnewdetitem.php",
					dataType: "json",
					data: {
						catalognumber: f.catalognumber.value,
						allcatnum: allCatNum,
						sciname: f.sciname.value,
						collid: f.collid.value,
						fieldsite: f.fieldsite.value
					}
				}).done(function( retStr ) {
					if(retStr != ""){
						for (var occid in retStr) {
							var occObj = retStr[occid];
							if(f.catalognumber.value && checkCatalogNumber(occid, occObj["cn"])){
								alert("<?php echo $LANG['RECORD_EXISTS']; ?>");
							}
							else{
								var trNode = createNewTableRow(occid, occObj);
								var tableBody = document.getElementById("catrecordstbody");
								tableBody.insertBefore(trNode, tableBody.firstElementChild);
							}
						}
						document.getElementById("accrecordlistdviv").style.display = "block";
					}
					else{
						alert("<?php echo $LANG['NO_RECORDS']; ?>");
					}
				});

				if(f.catalognumber.value != ""){
					f.catalognumber.value = '';
					f.catalognumber.focus();
				}
				workingObj.style.display = "none";
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

			function annotationTypeChanged(selectElem){
				var f = selectElem.form;
				if(selectElem.value == "na"){
					f.identificationqualifier.value = "";
					$("#idQualifierDiv").hide();
					f.confidenceranking.value = "";
					$("#codDiv").hide();
					f.identifiedby.value = "Nomenclatural Adjustment";
					f.identifiedby.readonly = true;
					f.makecurrent.checked = true;

					var today = new Date();
					var month = (today.getMonth() + 1);
					var day = today.getDate();
					var year = today.getFullYear();
					if(month < 10) month = '0' + month;
					if(day < 10) day = '0' + day;
					f.dateidentified.value = [year, month, day].join('-');
				}
				else{
					$("#idQualifierDiv").show();
					f.confidenceranking.value = 5;
					$("#codDiv").show();
					f.identifiedby.value = "";
					f.identifiedby.readonly = true;
					f.dateidentified.value = "";
					f.makecurrent.checked = false;
				}
			}

			function verifyDetSciName(f){
				$.ajax({
					type: "POST",
					url: "rpc/verifysciname.php",
					dataType: "json",
					data: { term: f.sciname.value }
				}).done(function( data ) {
					if(data){
						f.scientificnameauthorship.value = data.author;
						f.family.value = data.family;
						f.tidtoadd.value = data.tid;
					}
					else{
						alert("<?php echo $LANG['WARNING_TAXON_NOT_FOUND']; ?>");
						f.scientificnameauthorship.value = "";
						f.family.value = "";
						f.tidtoadd.value = "";
					}
				});
			}

			function openIndPopup(occid){
				openPopup('../individual/index.php?occid=' + occid);
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
		</script>
		<style>
			.top-breathing-room-sm-px {
				margin-top: 5px;
			}
			.left-breathing-room-rel-lg {
				margin-left: 2em;
			}
		</style>
	</head>
	<body>
	<?php
	$displayLeftMenu = (isset($collections_batchdeterminationsMenu) ? $collections_batchdeterminationsMenu : false);
	include($SERVER_ROOT . '/includes/header.php');
	?>
	<div class='navpath'>
		<a href='../../index.php'><?php echo htmlspecialchars($LANG['HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"><?php echo htmlspecialchars($LANG['COLL_MANAGE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
		<b><?php echo $LANG['BATCH_DETERS']; ?></b>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['BATCH_DETERS']; ?></h1>
		<?php
		if($isEditor){
			echo '<h2>'.$occManager->getCollName().'</h2>';
			?>
			<div>
				<section class="fieldset-like">
					<h2> <span> <?php echo $LANG['DEFINE_RECORDSET']; ?> </span> </h2>
					<div>
						<?php echo $LANG['RECORDSET_EXPLAIN']; ?>
					</div>
					<div style="margin-top:15px;">
						<form name="accqueryform" action="batchdeterminations.php" method="post" onsubmit="return submitAccForm(this);">
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
								<div style="margin: 0">
									<button name="clearaccform" type="button" onclick='clearAccForm(this.form)'><?php echo $LANG['CLEAR_LIST']; ?></button>
									<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								</div>
							</section>
						</form>
					</div>
					<div style="margin-top: 1rem">
						* <?php echo $LANG['LIST_LIMIT']; ?><br/>
					</div>
					<?php
					if($statusStr){
						echo '<div style="margin:30px 20px;">';
						echo '<div style="color:orange;font-weight:bold;">'.$statusStr.'</div>';
						echo '<div style="margin-top:10px;"><a href="../reports/annotationmanager.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($LANG['DISPLAY_QUEUE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></div>';
						echo '</div>';
					}
					?>
				</section>
				<div id="accrecordlistdviv" style="display:none;">
					<form name="accselectform" id="accselectform" action="batchdeterminations.php" method="post" onsubmit="return validateSelectForm(this);">
						<div style="margin-top: 15px; margin-left: 10px;">
							<input name="accselectall" value="" type="checkbox" onclick="selectAll(this);" checked />
							<?php echo $LANG['SELECT_DESELECT']; ?>
						</div>
						<table class="styledtable">
							<thead>
								<tr>
									<th style="width:25px;text-align:center;">&nbsp;</th>
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
								<div style='margin:3px;position:relative;height:35px'>
									<div style="float:left;">
										<b><?php echo $LANG['ANNOTATION_TYPE']; ?>:</b>
									</div>
									<div style="float:left;">
										<input name="annotype" type="radio" value="id" onchange="annotationTypeChanged(this)" checked /> <?php echo $LANG['ID_ADJUST']; ?><br/>
										<input name="annotype" type="radio" value="na" onchange="annotationTypeChanged(this)" /> <?php echo $LANG['NOM_ADJUST']; ?>
									</div>
								</div>
								<div style="clear:both;margin:15px 0px"><hr /></div>
								<div style='margin:3px;'>
									<b><?php echo $LANG['SCINAME']; ?>:</b>
									<input type="text" id="dafsciname" name="sciname" style="background-color:lightyellow;width:350px;" onfocus="initDetAutocomplete(this.form)" />
									<input type="hidden" id="daftidtoadd" name="tidtoadd" value="" />
								</div>
								<div id="idQualifierDiv" style='margin:3px;clear:both'>
									<b><?php echo $LANG['ID_QUALIFIER']; ?>:</b>
									<input type="text" name="identificationqualifier" title="e.g. cf, aff, etc" />
								</div>
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
									<b><?php echo $LANG['ID_REFERENCE']; ?>:</b>
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
									<input type="checkbox" name="makecurrent" value="1" checked /> <?php echo $LANG['MAKE_CURRENT']; ?>
								</div>
								<div style='margin:3px;'>
									<input type="checkbox" name="printqueue" value="1" checked /> <?php echo $LANG['ADD_PRINT_QUEUE']; ?>
									<a href="../reports/annotationmanager.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" target="_blank"><img src="../../images/list.png" style="width:1.2em" title="<?php echo htmlspecialchars($LANG['DISPLAY_QUEUE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" /></a>
								</div>
								<div style='margin:15px;'>
									<div style="float:left;">
										<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
										<input name="tabtarget" type="hidden" value="0" />
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
