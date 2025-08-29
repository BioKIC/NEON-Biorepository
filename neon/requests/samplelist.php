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
	//Variable has not been explicitly set by user, thus only turn on if manifest contains < 3000 samples
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
				alert("Select samples to check-in");
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
			//USPS: WEDNESDAY 15  MAY 2019	 by 8:00pm
			//USPS ver2: May 15, 2019 at 2:43 pm
			//FedEx: 7/13/23 at 9:39 AM
			//UPS: Monday, July 17 at 7:24 A.M. at Receiver

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

		function getMonthFromString(mon){
			var d = Date.parse(mon + "1, 2012");
			if(!isNaN(d)){
				var month = new Date(d).getMonth() + 1;
				return month.toString();
			}
			return "";
		}

		function checkinSample(f){
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
					alert("Sample Condition cannot be OK when sample is tagged as Not Accepted for Analysis");
					return false;
				}
				else if(f.sampleCondition.value == ""){
					alert("Sample Condition required when sample is tagged as Not Accepted for Analysis");
					return false;
				}
			}
			var sampleIdentifier = document.getElementById('fullIdentifier').textContent.trim();
			if(sampleIdentifier != ""){
				//alert("rpc/checkinsample.php?request_id=<?php echo $request_id; ?>&identifier="+sampleIdentifier+"&received="+f.sampleReceived.value+"&accepted="+f.acceptedForAnalysis.value+"&condition="+f.sampleCondition.value+"&altSampleID="+f.alternativeSampleID.value+"&notes="+f.checkinRemarks.value);
				$.ajax({
					type: "POST",
					url: "rpc/checkinsample.php",
					dataType: 'json',
					data: { request_id: "<?php echo $request_id; ?>", identifier: sampleIdentifier, received: f.sampleReceived.value, accepted: f.acceptedForAnalysis.value, condition: f.sampleCondition.value, altSampleID: f.alternativeSampleID.value, notes: f.checkinRemarks.value }
				}).done(function( retJson ) {
					$("#checkinText").show();
					if(retJson.status == 0){
						$("#checkinText").css('color', 'red');
						$("#checkinText").text('check-in failed!');
					}
					else if(retJson.status == 1){
						$("#checkinText").css('color', 'green');
						$("#checkinText").text('success!!!');
						$("#scSpan-"+retJson.samplePK).html("checked in");
						f.identifier.value = "";
						updateFullIdentifier();
						f.alternativeSampleID.value = "";
						if(f.formReset.checked == true){
							f.prefix.value = "";
							f.suffix.value = "";
							updateFullIdentifier();
							f.sampleReceived.value = 1;
							f.acceptedForAnalysis.value = 1;
							f.sampleCondition.value = "ok";
							f.checkinRemarks.value = "";
						}
					}
					else if(retJson.status == 2){
						$("#checkinText").css('color', 'orange');
						$("#checkinText").text('already checked!');
					}
					else if(retJson.status == 3){
						$("#checkinText").css('color', 'red');
						$("#checkinText").text('not found!');
					}
					else{
						$("#checkinText").css('color', 'red');
						$("#checkinText").text('Failed: unknown error!');
					}
					$("#checkinText").animate({fontSize: "125%"}, "slow");
					$("#checkinText").animate({fontSize: "100%"}, "slow");
					$("#checkinText").animate({fontSize: "125%"}, "slow");
					$("#checkinText").animate({fontSize: "100%"}, "slow").delay(5000).fadeOut();
					f.identifier.focus();
				});
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

		function openSampleEditor(samplePK){
			var url = "sampleeditor.php?samplePK="+samplePK;
			openPopup(url,"sample1window");
			return false;
		}

		function addSample(request_id){
			var url = "sampleeditor.php?request_id="+request_id;
			openPopup(url,"sample1window");
			return false;
		}

		function openSampleCheckinEditor(samplePK){
			var url = "samplecheckineditor.php?samplePK="+samplePK;
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
					$sampleList = $inquiryManager->getSamplesByID($request_id);
					?>
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
											<option value="notAvailable" <?php echo ($sampleFilter=='notAvailable'?'SELECTED':''); ?>>Not Available</option>
											<option value="current" <?php echo ($sampleFilter=='current'?'SELECTED':''); ?>>Current</option>
											<option value="completed" <?php echo ($sampleFilter=='completed'?'SELECTED':''); ?>>Completed</option>
										</select>
										<input name="request_id" type="hidden" value="<?php echo $request_id; ?>" />
									</form>
								</div>
							</div>
							<div style="clear:both">
								<?php
								if($sampleList){
									?>
									<form name="sampleListingForm" action="manifestviewer.php" method="post" onsubmit="return batchCheckinFormVerify(this)">
										<input name="sortabletable" type="hidden" value="<?= $sortableTable ?>">
										<table id="manifestTable" class="styledtable">
											<thead>
												<tr>
													<?php
													$headerOutArr = current($sampleList);
													echo '<th><input name="selectall" type="checkbox" onclick="selectAll(this)" /></th>';
													$headerArr = array('sampleID'=>'Sample ID', 'sampleCode'=>'Sample<br/>Code', 'sampleClass'=>'Sample<br/>Class', 'taxonID'=>'Taxon ID',
														'namedLocation'=>'Named<br/>Location', 'collectDate'=>'Collection<br/>Date', 'quarantineStatus'=>'Quarantine<br/>Status','sampleReceived'=>'Sample<br/>Received',
														'acceptedForAnalysis'=>'Accepted<br/>for<br/>Analysis','sampleCondition'=>'Sample<br/>Condition','checkinUser'=>'Check-in','occid'=>'occid');
														//'individualCount'=>'Individual Count', 'filterVolume'=>'Filter Volume', 'domainRemarks'=>'Domain Remarks', 'sampleNotes'=>'Sample Notes',
													$rowCnt = 1;
													foreach($headerArr as $fieldName => $headerTitle){
														if(array_key_exists($fieldName, $headerOutArr) || $fieldName == 'checkinUser' || $fieldName == 'occid'){
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
												foreach($sampleList as $samplePK => $sampleArr){
													$classStr = '';
													$propStr = '';
													if(isset($sampleArr['dynamicProperties'])){
														$dynPropArr = json_decode($sampleArr['dynamicProperties'],true);
														foreach($dynPropArr as $category => $propValue){
															if(strtolower($category) == 'containerid'){
																$tagArr['containerid'][$propValue] = (isset($tagArr['containerid'][$propValue])?++$tagArr['containerid'][$propValue]:1);
																$classStr .= str_replace(' ','_',$propValue).' ';
															}
															elseif(strtolower($category) == 'plateid'){
																$tagArr['plateid'][$propValue] = (isset($tagArr['plateid'][$propValue])?++$tagArr['plateid'][$propValue]:1);
																$classStr .= str_replace(' ','_',$propValue).' ';
															}
															elseif(strtolower($category) == 'platebarcode'){
																$tagArr['platebarcode'][$propValue] = (isset($tagArr['platebarcode'][$propValue])?++$tagArr['platebarcode'][$propValue]:1);
																$classStr .= str_replace(' ','_',$propValue).' ';
															}
															$propStr .= $category.': '.$propValue.'; ';
														}
													}

													$str = '';
													if(!empty($sampleArr['alternativeSampleID'])) $str .= '<div>Alternative Sample ID: '.$sampleArr['alternativeSampleID'].'</div>';
													if(!empty($sampleArr['hashedSampleID'])) $str .= '<div>Hashed Sample ID: '.$sampleArr['hashedSampleID'].'</div>';
													if(!empty($sampleArr['individualCount'])) $str .= '<div>Individual Count: '.$sampleArr['individualCount'].'</div>';
													if(!empty($sampleArr['filterVolume'])) $str .= '<div>Filter Volume: '.$sampleArr['filterVolume'].'</div>';
													if(!empty($sampleArr['domainRemarks'])) $str .= '<div>Domain Remarks: '.$sampleArr['domainRemarks'].'</div>';
													if(!empty($sampleArr['sampleNotes'])) $str .= '<div>Sample Notes: '.$sampleArr['sampleNotes'].'</div>';
													if(!empty($sampleArr['checkinRemarks'])) $str .= '<div>Check-in Remarks: '.$sampleArr['checkinRemarks'].'</div>';
													if(isset($sampleArr['dynamicProperties']) && $sampleArr['dynamicProperties']){
														$str .= '<div>'.trim($propStr,'; ').'</div>';
													}
													if(isset($sampleArr['symbiotaTarget']) && $sampleArr['symbiotaTarget']){
														$symbTargetArr = json_decode($sampleArr['symbiotaTarget'],true);
														$symbStr = '';
														foreach($symbTargetArr as $symbLabel => $symbValue){
															$symbStr .= $symbLabel.': '.$symbValue.'; ';
														}
														$str .= '<div>Symbiota targeted data ['.trim($symbStr,'; ').']</div>';
													}
													if(!empty($sampleArr['occurErr'])) $str .= '<div>Occurrence Harvesting Error: '.$sampleArr['occurErr'].'</div>';

													if($sortableTable){
														if($str) {
															echo '<tr class="sample-row" data-child-value="'.trim($str,'; ').'">';
														} else {
															echo '<tr class="sample-row">';
														}
													}

													echo '<td>';
													echo '<input id="scbox-'.$samplePK.'" class="'.trim($classStr).'" name="scbox[]" type="checkbox" value="'.$samplePK.'" />';
													echo ' <a href="#" onclick="return openSampleEditor('.$samplePK.')"><img src="../../images/edit.png" style="width:12px" /></a>';
													echo '</td>';
													$sampleID = (array_key_exists('sampleID',$sampleArr)?$sampleArr['sampleID']:'');
													if(array_key_exists('sampleID', $headerOutArr)){
														if($quickSearchTerm == $sampleID) $sampleID = '<b>'.$sampleID.'</b>';
														echo '<td>'.$sampleID.'</td>';
													}
													$sampleCode = (array_key_exists('sampleCode',$sampleArr)?$sampleArr['sampleCode']:'');
													if(array_key_exists('sampleCode', $headerOutArr)){
														if($quickSearchTerm == $sampleCode) $sampleCode = '<b>'.$sampleCode.'</b>';
														echo '<td>'.$sampleCode.'</td>';
													}
													echo '<td>'.$sampleArr['sampleClass'].'</td>';
													if(array_key_exists('taxonID',$sampleArr)) echo '<td>'.$sampleArr['taxonID'].'</td>';
													if(array_key_exists('namedLocation', $sampleArr)){
														$namedLocation = $sampleArr['namedLocation'];
														if(isset($sampleArr['siteTitle']) && $sampleArr['siteTitle']) $namedLocation = '<span title="'.$sampleArr['siteTitle'].'">'.$namedLocation.'</span>';
														echo '<td>'.$namedLocation.'</td>';
													}
													if(array_key_exists('collectDate', $sampleArr)) echo '<td>'.$sampleArr['collectDate'].'</td>';
													echo '<td>'.$sampleArr['quarantineStatus'].'</td>';
													if(array_key_exists('sampleReceived', $sampleArr)){
														$sampleReceived = $sampleArr['sampleReceived'];
														if($sampleArr['sampleReceived']==1) $sampleReceived = 'Y';
														if($sampleArr['sampleReceived']==='0') $sampleReceived = 'N';
														echo '<td>'.$sampleReceived.'</td>';
													}
													if(array_key_exists('acceptedForAnalysis', $sampleArr)){
														$acceptedForAnalysis = $sampleArr['acceptedForAnalysis'];
														if($sampleArr['acceptedForAnalysis']==1) $acceptedForAnalysis = 'Y';
														if($sampleArr['acceptedForAnalysis']==='0') $acceptedForAnalysis = 'N';
														echo '<td>'.$acceptedForAnalysis.'</td>';
													}
													if(array_key_exists('sampleCondition', $sampleArr)) echo '<td>'.$sampleArr['sampleCondition'].'</td>';
													echo '<td title="'.$sampleArr['checkinUser'].'">';
													echo '<span id="scSpan-'.$samplePK.'">'.$sampleArr['checkinTimestamp'].'</span> ';
													if($sampleArr['checkinTimestamp']) echo '<a href="#" onclick="return openSampleCheckinEditor('.$samplePK.')"><img src="../../images/edit.png" style="width:13px" /></a>';
													echo '</td>';
													echo '<td style="text-align:center">';
													if(array_key_exists('occid',$sampleArr) && $sampleArr['occid']){
														echo '<span title="harvested '.(isset($sampleArr['harvestTimestamp'])?$sampleArr['harvestTimestamp']:'').'">';
														if ($quickSearchTerm === $sampleArr['occid']) {
															echo '<a href="../../collections/individual/index.php?occid=' . $sampleArr['occid'] . '" target="_blank"><strong>' . $sampleArr['occid'] . '</strong></a>';
														} else {
															echo '<a href="../../collections/individual/index.php?occid=' . $sampleArr['occid'] . '" target="_blank">' . $sampleArr['occid'] . '</a>';
														}
														echo '</br>';
														echo '</br>';
														echo '<a href="../../collections/editor/occurrenceeditor.php?occid='.$sampleArr['occid'].'" target="_blank"><img src="../../images/edit.png" style="width:13px" /></a>';
														echo '</span>';
													}
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
												<legend>Batch Check-in Selected Samples</legend>
												<?php
												if($reqArr['checkinTimestamp']){
													?>
													<div class="displayFieldDiv">
														<input type="radio" class="start_session" name="session" value="start"> Start Session
														<input type="radio" class="stop_session" name="session" value="stop"> Stop Session
														<div class="timer">00:00:00</div>
														<div class="sessionName"></div>
													</div>
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
													<?php
												}
												else{
													echo '<div style="color:orange;margin-bottom:140px">Shipment needs to be checked in before you can check-in samples</div>';
												}
												?>
											</fieldset>
										</div>
										<?php
										if($sampleCnt){
											?>
											<div style="margin:15px;float:left">
												<div style="margin:5px;width:200px">
													<a href="#" onclick="addSample(<?php echo $request_id; ?>);return false;"><button name="addSampleButton" type="button">Add New Sample</button></a>
												</div>
												<fieldset style="margin:5px;float:left">
													<legend>Occurrence Harvesting</legend>
													<button name="action" type="submit" value="batchHarvestOccid">Batch Harvest</button>
													<div style="margin:10px" title="Upon reharvesting, replaces existing field values, but only if they haven't been explicitly edited to another value">
														<div>
															<input name="replaceFieldValues" type="checkbox" value="1" /> Replace Existing Field Values
														</div>
														<div>
															<input name="selectall" type="checkbox" onclick="selectAll(this)" /> Check All Samples
														</div>
													</div>
												</fieldset>
											</div>
											<?php
										}
										?>
									</form>
										<div style="float:left;margin-left:30px;margin-top:10px;">
											<form name="exportSampleListForm" action="exporthandler.php" method="post" style="">
												<input name="request_id" type="hidden" value="<?php echo $request_id; ?>" />
												<input name="exportTask" type="hidden" value="sampleList" />
												<div style="margin:10px 0px"><button name="action" type="submit" value="exportSampleListing">Export Sample Listing</button></div>
											</form>
										</div>
									</div>
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
						Update status to link samples
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