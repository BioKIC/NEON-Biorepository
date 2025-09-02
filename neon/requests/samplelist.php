<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/requests/list/InquiriesManager.php');
header('Content-Type: text/html; charset=' . $CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=' . $CLIENT_ROOT . '/neon/requests/inquiryform.php?' . $_SERVER['QUERY_STRING']);

$request_id = array_key_exists('id', $_REQUEST) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_NUMBER_INT) : '';
$sampleFilter = isset($_REQUEST['sampleFilter']) ? $_REQUEST['sampleFilter'] : '';
$quickSearchTerm = array_key_exists('quicksearch', $_REQUEST) ? $_REQUEST['quicksearch'] : '';
$sortableTable = isset($_REQUEST['sortabletable']) ? filter_var($_REQUEST['sortabletable'], FILTER_SANITIZE_NUMBER_INT) : false;
$action = array_key_exists('action', $_REQUEST) ? $_REQUEST['action'] : '';

$inquiryManager = new inquiriesManager();
$sampleCnt = $inquiryManager->getSampleCountByID($request_id);
if($sortableTable === false){
	//Variable has not been explicitly set by user, thus only turn on if list contains < 3000 samples
	if($sampleCnt < 3000) $sortableTable = 1;
	else $sortableTable = 0;
}
$isEditor = false;
if($IS_ADMIN) $isEditor = true;
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Sample Request List Viewer</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');

	?>
	<script src="../../js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.min.js" type="text/javascript"></script>
	<?php
	if($sortableTable){
		?>
		<link rel="stylesheet" href="../../js/datatables/datatables.css" />
		<script src="../../js/datatables/datatables.js"></script>

		<?php
	}
	?>
	<script type="text/javascript">
		$(document).ready(function() {
			$("#shipCheckinComment").keydown(function(evt){
				var evt  = (evt) ? evt : ((event) ? event : null);
				if ((evt.keyCode == 13)) { return false; }
			});
			<?php
			if($sortableTable){
				?>
				$('#manifestTable').DataTable({
					paging: false,
					scrollCollapse: true,
					fixedHeader: true,
					columnDefs: [{ orderable: false, targets: [0, -1]}],
					});
				$("#manifestTable").DataTable().rows().every( function () {
					var tr = $(this.node());
					var childValue = tr.data('child-value');

					if (childValue !== undefined) {
						this.child(childValue).show();
						tr.addClass('shown');
					}
				});
				<?php
			}
			?>

			['prefix', 'identifier', 'suffix'].forEach(id => {
			  const el = document.getElementById(id);
			  if (el) {
				el.addEventListener('input', updateFullIdentifier);
			  }
			});
		});

		function batchCheckinFormVerify(f){
			var formVerified = false;
			for(var h=0;h<f.length;h++){
				if(f.elements[h].name == "scbox[]" && f.elements[h].checked){
					formVerified = true;
					break;
				}
			}
			if(!formVerified){
				alert("Select samples");
				return false;
			}
			if(f.sampleReceived.value == "0"){
				if(f.acceptedForAnalysis.value != "" || f.sampleCondition.value != ""){
					alert("If sample is not received, Accepted for Analysis and Sample Condition must be NULL");
					return false;
				}
			}
			else if(f.sampleReceived.value == "1"){
				if(f.acceptedForAnalysis.value == ""){
					alert("Please select if accepted for analysis");
					return false;
				}
			}
			if(f.acceptedForAnalysis.value === 0){
				if(f.sampleCondition.value == "ok"){
					alert("Sample Condition cannot be OK if sample is Not Accepted for Analysis");
					return false;
				}
				else if(f.sampleCondition.value == ""){
					alert("Enter a Sample Condition");
					return false;
				}
			}
			return true;
		}

		function checkinCommentChanged(textObj){
			var f = textObj.form;
			var testStr = textObj.value.trim();
			if(testStr){
				if(!f.receivedDate.value){
					var yearStr = "";
					var monthStr = "";
					var dayStr = "";
					var dateEx1 = /(\d{1,2})\/(\d{1,2})\/(\d{2,4})/;
					var dateEx2 = /(\d{1,2})\s{1,3}([A-Z]+)\s{1,3}(\d{2,4})/;
					var dateEx3 = /([A-Za-z]+)\s{1,3}(\d{1,2})[,\s,]{1,3}(\d{2,4})/;
					var dateEx4 = /([A-Z]{1}[a-z]+)\s{1}(\d{1,2})/;
					if(extractArr = dateEx1.exec(testStr)){
						yearStr = extractArr[3];
						monthStr = extractArr[1];
						dayStr = extractArr[2];
					}
					else if(extractArr = dateEx2.exec(testStr)){
						yearStr = extractArr[3];
						monthStr = getMonthFromString(extractArr[2]);
						dayStr = extractArr[1];
					}
					else if(extractArr = dateEx3.exec(testStr)){
						yearStr = extractArr[3];
						monthStr = getMonthFromString(extractArr[1]);
						dayStr = extractArr[2];
					}
					else if(extractArr = dateEx4.exec(testStr)){
						yearStr = "2023";
						monthStr = getMonthFromString(extractArr[1]);
						dayStr = extractArr[2];
					}
					if(yearStr){
						if(yearStr.length == 2) yearStr = '20'+yearStr;
						if(monthStr.length == 1) monthStr = '0'+monthStr;
						if(dayStr.length == 1) dayStr = '0'+dayStr;
						if(!f.receivedDate.value){
							f.receivedDate.value = yearStr+"-"+monthStr+"-"+dayStr;
							textObj.value = "";
						}
					}
				}
				if(!f.receivedTime.value){
					var timeEx1 = /(\d{1,2}):(\d{1,2})\s{0,1}([apm.]+)/i;
					if(extractArr = timeEx1.exec(testStr)){
						var hourStr = extractArr[1];
						var minStr = extractArr[2];
						var dayPeriod = extractArr[3].toLowerCase();
						if(dayPeriod.indexOf('p') > -1){
							if(parseInt(hourStr) < 12) hourStr = String(parseInt(hourStr)+12);
						}
						else if(dayPeriod.indexOf('a') > -1){
							if(parseInt(hourStr) == 12) hourStr = "00";
						}
						if(hourStr.length == 1 ) hourStr = "0"+hourStr;
						if(minStr.length == 1 ) minStr = "0"+minStr;
						f.receivedTime.value = hourStr+":"+minStr;
						textObj.value = "";
					}
				}
			}
		}


		function sampleReceivedChanged(f){
			$(f.acceptedForAnalysis).prop("checked", false );
			$('[name=sampleCondition]').val( '' );
		}

		function popoutCheckinBox(){
			$("#sampleCheckinDiv").css('position', 'fixed');
			$("#popoutDiv").hide();
			$("#bindDiv").show();
		}

		function bindCheckinBox(){
			$("#sampleCheckinDiv").css('position', 'static');
			$("#popoutDiv").show();
			$("#bindDiv").hide();
		}

		function tableSortHandlerChanged(cbElem){
			let sortValue = 0;
			if(cbElem.checked){
				sortValue = 1;
			}
			document.getElementById('sortableTableID').value = sortValue;
			document.refreshForm.submit();
		}

		function selectAll(cbObj) {
			const boxesChecked = cbObj.checked;
			const form = cbObj.form;

			for (let i = 0; i < form.elements.length; i++) {
				const el = form.elements[i];
				if (el.name === "scbox[]") {
					el.checked = boxesChecked;
				}
				if (el.name === "selectall") {
					el.checked = boxesChecked;
				}
			}
		}

		function openSampleEditor(id){
			var url = "sampleeditor.php?id="+id;
			openPopup(url,"sample1window");
			return false;
		}


		function openSampleCheckinEditor(id){
			var url = "samplecheckineditor.php?id="+id;
			openPopup(url,"sample2window");
			return false;
		}

		function openPopup(url,windowName){
			newWindow = window.open(url,windowName,'scrollbars=1,toolbar=0,resizable=1,width=1000,height=500,left=20,top=100');
			if (newWindow.opener == null) newWindow.opener = self;
			return false;
		}


		function updateFullIdentifier() {
		  const prefix = document.getElementById('prefix').value.trim();
		  const identifier = document.getElementById('identifier').value.trim();
		  const suffix = document.getElementById('suffix').value.trim();

		  let full = '';
		  if (prefix) full += prefix;
		  full += identifier;
		  if (suffix) full += suffix;

		  document.getElementById('fullIdentifier').textContent = full;
		}
	</script>
	<style type="text/css">
		#innertext{ max-width: 1400px; }
		.fieldGroupDiv { clear:both; margin-top:2px; height: 25px; }
		.fieldDiv { float:left; margin-left: 10px}
		.displayFieldDiv { margin-bottom: 3px }
		fieldset legend { font-weight: bold; }
		.sample-row td { white-space: break-spaces; }
		.sorting_1 {
		  background-color: #c0c0c0a6 !important;
		}

		.input-group {
		  display: flex;
		  align-items: stretch;
		  width: fit-content;
		}

		.input-addon {
		  padding-left: 0.5em;
		  padding-right: 0.5em;
		  background-color: #eee;
		  border: 1px solid #ccc;
		  border-right: none;
		  display: flex;
		  align-items: center;
		}

		.input-addon.suffix {
		  border-left: none;
		  border-right: 1px solid #ccc;
		}

		#prefix {
			width: 120px;
		}

		#suffix {
			width: 60px;
		}

		.input-addon input {
		  border: none !important;
		  background: transparent;
		  padding: 0;
		  margin: 0;
		  outline: none;
		  font-family: inherit;
		}

		.main-input {
		  border: 1px solid #ccc;
		  width: 350px;
		  outline: none;
		  margin-top: 0;
		}

		.input-group input:focus {
		  outline: 2px solid #88f;
		}
	</style>
</head>
<body>
<?php
$displayLeftMenu = false;
include($SERVER_ROOT.'/includes/header.php');
?>
<div class="navpath">
	<a href="../../index.php">Home</a> &gt;&gt;
	<a href="../../neon/index.php">Management Tools</a> &gt;&gt;
	<a href="../../neon/requests/list/inquiries.php">Inquiry List</a> &gt;&gt;
	<a href="inquiryform.php?id=<?php echo $request_id; ?>">Inquiry Record</a> &gt;&gt;
	<b>Sample List</b>
</div>
<div id="innertext">
	<?php
	if($isEditor){
		if($action){
			$errStr = '';
			if($action == 'checkinShipment'){
				if(!$inquiryManager->checkinShipment($_POST)) $errStr = $inquiryManager->getErrorStr();
			}
			elseif($action == 'batchCheckin'){
				if(!$inquiryManager->batchCheckinSamples($_POST)) $errStr = $inquiryManager->getErrorStr();
			}
			elseif($action == 'receiptsubmitted'){
				if(!$inquiryManager->setReceiptStatus($_POST['submitted'])) $errStr = $inquiryManager->getErrorStr();
			}
			elseif($action == 'batchHarvestOccid'){
				echo '<fieldset style="padding:15px"><legend>Action Panel</legend><ul>';
				$occurManager = new OccurrenceHarvester();
				$occurManager->batchHarvestOccid($_POST);
				echo '</ul></fieldset>';
			}
			if($errStr){
				?>
				<fieldset style="padding:15px">
					<legend>Action Panel</legend>
					<ul>
					<?php
					echo $errStr;
					?>
					</ul>
				</fieldset>
				<?php
			}
		}
		$reqArr = $inquiryManager->getRequestData($request_id);
		if($reqArr){
			?>
			<fieldset style="margin-top:30px">
				<legend>Samples Associated with Request</legend>
				<div style="float:left;margin-right:40px;width:1000px;">
					<div class="displayFieldDiv">
						<b>Request ID:</b> <?php echo $request_id; ?>
						<a href="<?php echo $CLIENT_ROOT . '/neon/requests/inquiryform.php?id=' . $request_id; ?>">
							Edit request
						</a>
					</div>
					<div class="displayFieldDiv"><b>Title:</b> <?php echo $reqArr['title']; ?></div>
					<div class="displayFieldDiv"><b>Primary Contact:</b> <?php echo $reqArr['name']; ?></div>
					<div class="displayFieldDiv"><b>Status:</b> <?php echo $reqArr['status']; ?></div>
					<div class="displayFieldDiv"><b>Number of Samples Linked:</b> <?php echo $sampleCnt; ?></div>
				</div>
				<?php
				if(in_array($reqArr['status'],array('pending sample list','active use','completed','pending funding')) && $sampleCnt){
					$sampleList = $inquiryManager->getSamplesByID($request_id, $sampleFilter);
					?>
					<div style="clear:both;padding:10px 0;">
						<!-- <div style="float:left;margin-right:10px;">
							<a href="#" onclick="return addSample(<?php echo $request_id; ?>);">
								<button type="button">Add New Sample</button>
							</a>
						</div> -->
						<div style="float:left;">
							<form name="exportSampleListForm" action="exporthandler.php" method="post">
								<input type="hidden" name="request_id" value="<?php echo $request_id; ?>" />
								<input type="hidden" name="exportTask" value="samplesrequest" />
								<button type="submit" name="action" value="exportSampleListing">
									Export Request Samples
								</button>
							</form>
						</div>
					</div>
					<div style="clear:both;padding:10px 0;">
						<div style="float:left;">
							<form name="exportOccurForm" action="exporthandler.php" method="post">
								<input type="hidden" name="request_id" value="<?php echo $request_id; ?>" />
								<input type="hidden" name="exportTask" value="occurrences" />
								<button type="submit" name="action" value="exportOccurListing">
									Export Occurrences
								</button>
							</form>
						</div>
					</div>
					<div style="clear:both;padding-top:30px;">
						<fieldset id="samplePanel">
							<legend>Sample Listing</legend>
							<div>
								<div style="float:left">Records displayed: <?php echo count($sampleList); ?></div>
								<div style="float:left; margin-left: 50px;"><input name="sorthandler" type="checkbox" onchange="tableSortHandlerChanged(this)" <?= ($sortableTable ? 'checked' : '') ?> > Make table sortable</div>
								<div style="float:right;">
									<form name="filterSampleForm" action="samplelist.php#samplePanel" method="post" style="">
										Filter by:
										<select name="sampleFilter" onchange="this.form.submit()">
											<option value="">All Records</option>
											<option value="available" <?php echo ($sampleFilter=='available'?'SELECTED':''); ?>>Available</option>
											<option value="notavailable" <?php echo ($sampleFilter=='notavailable'?'SELECTED':''); ?>>Not Available</option>
											<option value="pending" <?php echo ($sampleFilter=='pending'?'SELECTED':''); ?>>Pending Fulflillment</option>
											<option value="current" <?php echo ($sampleFilter=='current'?'SELECTED':''); ?>>Current</option>
											<option value="completed" <?php echo ($sampleFilter=='completed'?'SELECTED':''); ?>>Completed</option>
										</select>
											<input name="id" type="hidden" value="<?php echo $request_id; ?>" />
									</form>
								</div>
							</div>
							<div style="clear:both">
								<?php
								if($sampleList){
									?>
									<form name="sampleListingForm" action="samplelist.php" method="post" onsubmit="return batchCheckinFormVerify(this)">
										<input name="sortabletable" type="hidden" value="<?= $sortableTable ?>">
										<table id="manifestTable" class="styledtable">
											<thead>
												<tr>
													<?php
													$headerOutArr = current($sampleList);
													echo '<th><input name="selectall" type="checkbox" onclick="selectAll(this)" /></th>';
													$headerArr = array('sampleID' => 'sampleID','sampleClass' => 'sampleClass',
																'sampleCode' =>'sampleCode','status'=>'Status', 'use_type'=>'Use Type', 
																'substance_provided'=>'Substance Provided', 'available'=>'Available',
																'notes'=> 'Notes','shipment_id' => 'Shipment ID','occid'=> 'occid');
													$rowCnt = 1;
													foreach($headerArr as $fieldName => $headerTitle){
														if(array_key_exists($fieldName, $headerOutArr) || $fieldName == 'occid'){
															echo '<th>'.$headerTitle.'</th>';
															$rowCnt++;
														}
													}
													?>
												</tr>
											</thead>
											<tbody>
												<?php
												$tagArr = array();
												foreach($sampleList as $id => $sampleArr){
													$classStr = '';
													$propStr = '';
													$str = '';
													if(!empty($sampleArr['occurErr'])) $str .= '<div>Occurrence Harvesting Error: '.$sampleArr['occurErr'].'</div>';

													if($sortableTable){
														if($str) {
															echo '<tr class="sample-row" data-child-value="'.trim($str,'; ').'">';
														} else {
															echo '<tr class="sample-row">';
														}
													}
													echo '<td>';
													echo '<input id="scbox-'.$sampleArr['id'].'" class="'.trim($classStr).'" name="scbox[]" type="checkbox" value="'.$sampleArr['id'].'" />';
													echo '<a href="#" onclick="return openSampleEditor('.$sampleArr['id'].')"><img src="../../images/edit.png" style="width:12px" /></a>';
													echo '</td>';

													echo '<td>'.$sampleArr['sampleID'].'</td>';
													echo '<td>'.$sampleArr['sampleClass'].'</td>';
													echo '<td>'.$sampleArr['sampleCode'].'</td>';
													echo '<td>'.$sampleArr['status'].'</td>';
													echo '<td>'.$sampleArr['use_type'].'</td>';
													echo '<td>'.$sampleArr['substance_provided'].'</td>';
													echo '<td>'.$sampleArr['available'].'</td>';
													echo '<td>'.$sampleArr['notes'].'</td>';
													echo '<td>'.$sampleArr['shipment_id'].'</td>';
													
													echo '</td>';
													echo '<td style="text-align:center">';
													echo '<a href="../../collections/individual/index.php?occid=' . $sampleArr['occid'] . '" target="_blank">' . $sampleArr['occid'] . '</a>';
													echo '</br>';
													echo '</br>';
													echo '<a href="../../collections/editor/occurrenceeditor.php?occid='.$sampleArr['occid'].'" target="_blank"><img src="../../images/edit.png" style="width:13px" /></a>';
													echo '</span>';
													echo '</td>';
													echo '</tr>';

													if(!$sortableTable){
														if($str) echo '<tr><td colspan="'.$rowCnt.'"><div style="margin-left:30px;">'.trim($str,'; ').'</div></td></tr>';
													}

												}
												?>
											</tbody>
										</table>
										<div style="margin:15px;float:left">
											<input name="request_id" type="hidden" value="<?php echo $request_id; ?>" />
											<fieldset style="width:450px;">
												<legend>Add Selected Samples to Shipment</legend>
													<div class="displayFieldDiv">
														<b>Sample Received:</b>
														<input name="sampleReceived" type="radio" value="1" checked /> Yes
														<input name="sampleReceived" type="radio" value="0" onchange="sampleReceivedChanged(this.form)" /> No
													</div>
													<div class="displayFieldDiv">
														<b>Accepted for Analysis:</b>
														<input name="acceptedForAnalysis" type="radio" value="1" checked /> Yes
														<input name="acceptedForAnalysis" type="radio" value="0" onchange="this.form.sampleCondition.value = ''" /> No
													</div>
													<div class="displayFieldDiv">
														<b>Sample Condition:</b>
														<select name="sampleCondition">
															<option value="">Not Set</option>
															<option value="">--------------------------------</option>
															<?php
															$condArr = $inquiryManager->getConditionArr();
															foreach($condArr as $condKey => $condValue){
																echo '<option value="'.$condKey.'" '.($condKey=='ok'?'SELECTED':'').'>'.$condValue.'</option>';
															}
															?>
														</select>
													</div>
													<div class="displayFieldDiv">
														<b>Check-in Remarks:</b> <input name="checkinRemarks" type="text" style="width:300px" />
													</div>
													<div style="margin:5px 10px">
														<button name="action" type="submit" value="batchCheckin" >Check-in Selected Samples</button>
													</div>
											</fieldset>
										</div>
										<?php
										?>
									</form>
									<?php
								}
								else{
									echo '<div style="margin: 20px">No samples exist matching filter criteria</div>';
								}
								?>
							</div>
						</fieldset>
					</div>
					<?php
				}
				else{
					?>
					<div style='font-weight:bold;margin:30px;color:red;'>
						Update status by editing request before adding samples
					</div>
					<?php
				}
				?>
			</fieldset>
			<?php
		}
		else echo '<h2>Request does not exist</h2>';
	}
	else{
		?>
		<div style='font-weight:bold;margin:30px;'>
			You do not have permissions to view requests
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