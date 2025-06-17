<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/ShipmentManager.php');
include_once($SERVER_ROOT.'/neon/classes/OccurrenceHarvester.php');
header('Content-Type: text/html; charset=' . $CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=' . $CLIENT_ROOT . '/neon/shipment/manifestviewer.php?' . $_SERVER['QUERY_STRING']);

$shipmentPK = array_key_exists('shipmentPK', $_REQUEST) ? filter_var($_REQUEST['shipmentPK'], FILTER_SANITIZE_NUMBER_INT) : '';
$sampleFilter = isset($_REQUEST['sampleFilter']) ? $_REQUEST['sampleFilter'] : '';
$action = array_key_exists('action', $_REQUEST) ? $_REQUEST['action'] : '';

$shipManager = new ShipmentManager();
if($shipmentPK) $shipManager->setShipmentPK($shipmentPK);
$sampleCntArr = $shipManager->getSampleCount();
$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif(array_key_exists('CollAdmin',$USER_RIGHTS) || array_key_exists('CollEditor',$USER_RIGHTS)) $isEditor = true;
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Manifest Viewer</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
	<?php
	$activateJQuery = true;
	include_once($SERVER_ROOT.'/includes/head.php');

	$activeSession = false;
	$sessionStartTime = null;
	$sessionName = null;
	
	if (isset($_SESSION['sampleCheckinSessionData'])) {
		$session_data = $_SESSION['sampleCheckinSessionData'];
	
		// Check if the session is active (end_time is null)
		if ($session_data['end_time'] === null) {
			$activeSession = true;
			$sessionStartTime = $session_data['start_time'];
			$sessionName = $session_data['sessionName'];
		}
	}
	?>
	<script src="../../js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.min.js" type="text/javascript"></script>
	<link rel="stylesheet" href="../../js/datatables/datatables.css" />
	<script src="../../js/datatables/datatables.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			$("#shipCheckinComment").keydown(function(evt){
				var evt  = (evt) ? evt : ((event) ? event : null);
				if ((evt.keyCode == 13)) { return false; }
			});
			var shipmentPK = <?php echo json_encode($shipmentPK); ?>;
			var table = $('#manifestTable').DataTable({
				serverSide: true, // turns on processing on the server (i.e., through SQL) vs through the browser
				ajax: { //what is sent upon an action in the table
					url: '../rpc/datatables_manifestview.php',
					type: 'POST',
					data: function (d) {
						d.sampleFilter = $('#sampleFilter').val();
						d.shipmentPK = shipmentPK;
					}
				},
				scrollCollapse: true, //Allow the table to reduce in height when a limited number of rows are shown
				columnDefs: [
					{ targets: [0, 12], orderable: false }, // disables ordering for columns 0 and last
					{ targets: 0, className: 'checkbox' }, // don't let people remove/add this column
					{ targets: [7, 8, 9, 10], visible: false }, // make this column not visible on load
					{ targets: [6], className: 'dt-left' }, // align date column to the left instead of the automatic right
					{ targets: [13,14,15,16,17,18,19,20,21,22], visible: false, searchable: false, className: 'checkbox' } // child notes data
				],
				layout: {
					topStart: {
						buttons: [
							{
								extend: 'columnsToggle',
								columns: ':not(.checkbox)'
							}
						]
					}
				},
				createdRow: function(row, data, dataIndex) {
					let str = '';
				
					const safe = val => val !== null && val !== undefined && val !== '';
				
					if (safe(data[13])) str += `<div>Alternative Sample ID: ${data[13]}</div>`;
					if (safe(data[14])) str += `<div>Hashed Sample ID: ${data[14]}</div>`;
					if (safe(data[15])) str += `<div>Individual Count: ${data[15]}</div>`;
					if (safe(data[16])) str += `<div>Filter Volume: ${data[16]}</div>`;
					if (safe(data[17])) str += `<div>Domain Remarks: ${data[17]}</div>`;
					if (safe(data[18])) str += `<div>Sample Notes: ${data[18]}</div>`;
					if (safe(data[19])) str += `<div>Check-in Remarks: ${data[19]}</div>`;
					if (safe(data[20])) {
						try {
							const parsed = JSON.parse(data[20]);
							const propStr = Object.entries(parsed)
								.map(([key, val]) => `${key}: ${val}`)
								.join('; ');
							str += `<div>${propStr}</div>`;
						} catch (e) {
							console.warn("Invalid JSON in dynamicProperties:", data[20]);
							str += `<div>${data[20]}</div>`; // fallback
						}
					}
				
					if (safe(data[21])) {
						try {
							const parsed = JSON.parse(data[21]);
							const symbStr = Object.entries(parsed).map(([label, val]) => `${label}: ${val}`).join('; ');
							str += `<div>Symbiota targeted data [${symbStr}]</div>`;
						} catch (e) {
							console.warn("Invalid symbiotaTarget JSON:", data[21]);
						}
					}
				
					if (safe(data[22])) str += `<div>Occurrence Harvesting Error: ${data[22]}</div>`;
				
					if (str) {
						$(row).attr('data-child-value', str);
					}
				}	
			});
			
			table.on('draw', function() {
				table.rows().every(function () {
					const tr = $(this.node());
					const childValue = tr.data('child-value');
			
					if (childValue !== undefined) {
						this.child(childValue).show();
						tr.addClass('shown');
					}
				});
				table.columns().every(function () {
					const colIdx = this.index();
					let hasData = false;
			
					this.data().each(function (value) {
						if (value !== null && value !== '' && value !== '&nbsp;') {
							hasData = true;
							return false; // break loop
						}
					});
			
					// If column is empty, trigger a click to hide it via column visibility button
					if (!hasData) {
						const $button = $(`.dt-button[data-cv-idx="${colIdx}"]`);
						if ($button.hasClass('dt-button-active')) {
							$button.click(); // triggers the built-in column visibility toggle
						}
					}
				});
			});
			
			$('#sampleFilter').on('change', function() {
				table.ajax.reload();
			});
			$('#manifestTable').css('width', '100%');
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
			var sampleIdentifier = f.identifier.value.trim();
			if(sampleIdentifier != ""){
				//alert("rpc/checkinsample.php?shipmentpk=<?php echo $shipmentPK; ?>&identifier="+sampleIdentifier+"&received="+f.sampleReceived.value+"&accepted="+f.acceptedForAnalysis.value+"&condition="+f.sampleCondition.value+"&altSampleID="+f.alternativeSampleID.value+"&notes="+f.checkinRemarks.value);
				$.ajax({
					type: "POST",
					url: "rpc/checkinsample.php",
					dataType: 'json',
					data: { shipmentpk: "<?php echo $shipmentPK; ?>", identifier: sampleIdentifier, received: f.sampleReceived.value, accepted: f.acceptedForAnalysis.value, condition: f.sampleCondition.value, altSampleID: f.alternativeSampleID.value, notes: f.checkinRemarks.value }
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
						f.alternativeSampleID.value = "";
						if(f.formReset.checked == true){
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

		function selectAll(cbObj){
			var boxesChecked = true;
			if(!cbObj.checked) boxesChecked = false;
			var f = cbObj.form;
			for(var i=0;i<f.length;i++){
				if(f.elements[i].name == "scbox[]") f.elements[i].checked = boxesChecked;
			}
		}

		function batchSelectSamples(selectObj){
			if(selectObj.value != ""){
				var f = selectObj.form;
				var selectCnt = 0;
				for(var i=0;i<f.length;i++){
					if(f.elements[i].name == "scbox[]") f.elements[i].checked = false;
				}
				$("."+selectObj.value).prop('checked', true);
				$("#selectedMsgDiv").text($("."+selectObj.value).length+' samples have been selected');
				if(f.batchContainerID && selectObj.name != "batchContainerID") f.batchContainerID.value = "";
				if(f.batchPlateID && selectObj.name != "batchPlateID") f.batchPlateID.value = "";
				if(f.batchPlateBarcode && selectObj.name != "batchPlateBarcode") f.batchPlateBarcode.value = "";
			}
		}

		function openShipmentEditor(){
			var url = "shipmenteditor.php?shipmentPK=<?php echo $shipmentPK; ?>";
			openPopup(url,"shipwindow");
			return false;
		}

		function openSampleEditor(samplePK){
			var url = "sampleeditor.php?samplePK="+samplePK;
			openPopup(url,"sample1window");
			return false;
		}

		function addSample(shipmentPK){
			var url = "sampleeditor.php?shipmentPK="+shipmentPK;
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
		
		let timerInterval;

		let serverStartTime = "<?php echo $sessionStartTime; ?>";
		let sessionName = "<?php echo $sessionName; ?>";
	
		// Check if a session is already active on page load
		document.addEventListener('DOMContentLoaded', function() {
			<?php if ($activeSession): ?>
				startTimer(serverStartTime);
				toggleButtons(true);
				showSessionID(sessionName);
			<?php else: ?>
				toggleButtons(false);
			<?php endif; ?>
			document.querySelectorAll('.start_session').forEach(button => {
				button.addEventListener('click', startSession);
			});
		
			// Attach event listeners to all stop session buttons
			document.querySelectorAll('.stop_session').forEach(button => {
				button.addEventListener('click', stopSession);
			});
		});
	
		function startSession() {
			// Send AJAX request to start session
			fetch('rpc/sessionManager.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					action: 'start_session'
				})
			}).then(response => response.json())
			  .then(data => {
				  console.log('Session started at:', data.start_time);
				  startTimer(data.start_time);
				  toggleButtons(true);
				  showSessionID(data.sessionName);
			  });
		}
	
		function stopSession() {
			// Send AJAX request to stop session
			fetch('rpc/sessionManager.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({ action: 'stop_session' })
			}).then(response => response.json())
			  .then(data => {
				  console.log('Session ended at:', data.end_time);
				  stopTimer();
				  toggleButtons(false);
			  });
		}
				
		function startTimer(startTime) {
			let start = new Date(startTime);
	
			// Clear any existing timer before starting a new one
			if (timerInterval) {
				clearInterval(timerInterval);
			}
			// Start the interval timer
			timerInterval = setInterval(function() {
				let now = new Date(new Date().toLocaleString("en-US", {timeZone: "America/Phoenix"}))
				let diff = Math.floor((now - start) / 1000);
				let hours = Math.floor(diff / 3600);
				let minutes = Math.floor((diff % 3600) / 60);
				let seconds = diff % 60;
				
				hours = String(hours).padStart(2, '0');
				minutes = String(minutes).padStart(2, '0');
				seconds = String(seconds).padStart(2, '0');
				
				let timers = document.querySelectorAll('.timer');
				timers.forEach(timer => {
					timer.textContent = hours + ":" + minutes + ":" + seconds;
				});
			}, 1000);
		}
	
		function stopTimer() {
			// Clear the timer interval and reset the timer display
			clearInterval(timerInterval);
			let timers = document.querySelectorAll('.timer');
			timers.forEach(timer => {
				timer.textContent = "00:00:00";
			});
		}
		
		function toggleButtons(isSessionActive) {
			let startButtons = document.querySelectorAll('.start_session');
			let stopButtons = document.querySelectorAll('.stop_session');
		
			startButtons.forEach(button => {
				button.disabled = isSessionActive;
			});
		
			stopButtons.forEach(button => {
				button.disabled = !isSessionActive;
			});
		}
		
		function showSessionID(sessionName) {
			let sessionNamedivs = document.querySelectorAll('.sessionName');
			
			sessionNamedivs.forEach(div => {
				div.innerHTML  = '<strong>SessionID:</strong> ' + sessionName;
			});	
		}
	</script>
	<style type="text/css">
		#innertext{margin-left: 0px; margin-right: 0px;}
		.displayFieldDiv { margin-bottom: 3px }
		fieldset legend { font-weight: bold; }
		.sample-row td { white-space: break-spaces; }
		.sorting_1 {
		  background-color: #c0c0c0a6 !important;
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
	<a href="../index.php">NEON Biorepository Tools</a> &gt;&gt;
	<a href="manifestsearch.php">Manifest Search</a> &gt;&gt;
	<a href="manifestviewer.php?shipmentPK=<?php echo $shipmentPK; ?>"><b>Manifest View</b></a>
</div>
<div id="innertext">
	<?php
	if($isEditor){
		if($action){
			$errStr = '';
			if($action == 'checkinShipment'){
				if(!$shipManager->checkinShipment($_POST)) $errStr = $shipManager->getErrorStr();
			}
			elseif($action == 'batchCheckin'){
				if(!$shipManager->batchCheckinSamples($_POST)) $errStr = $shipManager->getErrorStr();
			}
			elseif($action == 'receiptsubmitted'){
				if(!$shipManager->setReceiptStatus($_POST['submitted'])) $errStr = $shipManager->getErrorStr();
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
		$shipArr = $shipManager->getShipmentArr();
		if($shipArr){
			?>
			<fieldset style="margin-top:30px">
				<legend>Shipment #<?php echo $shipmentPK; ?></legend>
				<div style="float:left;margin-right:40px;width:400px;">
					<div class="displayFieldDiv">
						<b>Shipment ID:</b> <?php echo $shipArr['shipmentID']; ?>
						<a href="#" onclick="openShipmentEditor()"><img src="../../images/edit.png" style="width:13px" /></a>
					</div>
					<?php
					$domainStr = $shipArr['domainID'];
					if(isset($shipArr['domainTitle'])) $domainStr = $shipArr['domainTitle'].' ('.$shipArr['domainID'].')';
					?>
					<div class="displayFieldDiv"><b>Domain:</b> <?php echo $domainStr; ?></div>
					<div class="displayFieldDiv"><b>Date Shipped:</b> <?php echo $shipArr['dateShipped']; ?></div>
					<div class="displayFieldDiv"><b>Shipped From:</b> <?php echo $shipArr['shippedFrom']; ?></div>
					<div class="displayFieldDiv"><b>Sender ID:</b> <?php echo $shipArr['senderID']; ?></div>
					<div class="displayFieldDiv"><b>Destination Facility:</b> <?php echo $shipArr['destinationFacility']; ?></div>
					<div class="displayFieldDiv"><b>Sent To ID:</b> <?php echo $shipArr['sentToID']; ?></div>
					<div class="displayFieldDiv"><b>Shipment Service:</b> <?php echo $shipArr['shipmentService']; ?></div>
					<div class="displayFieldDiv"><b>Shipment Method:</b> <?php echo $shipArr['shipmentMethod']; ?></div>
					<?php
					if($shipArr['importUser']) echo '<div class="displayFieldDiv"><b>Manifest Importer:</b> '.$shipArr['importUser'].'</div>';
					if($shipArr['ts']) echo '<div class="displayFieldDiv"><b>Import Date:</b> '.$shipArr['ts'].'</div>';
					if($shipArr['modifiedUser']) echo '<div class="displayFieldDiv"><b>Modified By User:</b> '.$shipArr['modifiedUser'].' ('.$shipArr['modifiedTimestamp'].')</div>';
					if($shipArr['shipmentNotes']) echo '<div class="displayFieldDiv"><b>General Notes:</b> '.$shipArr['shipmentNotes'].'</div>';
					if($shipArr['fileName']){
						echo '<div class="displayFieldDiv"><b>Import file:</b> ';
						$filePath = $shipManager->getContentPath('url');
						$fileNameArr = explode('|',$shipArr['fileName']);
						foreach($fileNameArr as $fileName){
							echo '<div style="margin-left:15px"><a href="'.$filePath.$fileName.'">'.$fileName.'</a></div>';
						}
						echo '</div>';
					}
					?>
				</div>
				<div style="float:left;">
					<?php
					$receivedStr = '<span style="color:orange;font-weight:bold">Not yet arrived</span>';
					if($shipArr['receivedDate']) $receivedStr = $shipArr['receivedBy'].' ('.$shipArr['receivedDate'].')';
					echo '<div class="displayFieldDiv"><b>Received By:</b> '.$receivedStr.'</div>';
					echo '<div class="displayFieldDiv"><b>Tracking Number:</b> '.$shipManager->getTrackingStr().'</div>';
					if($shipArr['checkinTimestamp']){
						echo '<div class="displayFieldDiv"><b>Shipment Check-in:</b> '.$shipArr['checkinUser'].' ('.$shipArr['checkinTimestamp'].')</div>';
					}
					?>
					<div style="margin-top:10px;">
						<div class="displayFieldDiv">
							<b>Total Sample Count:</b> <?php echo ($sampleCntArr['all']); ?>
							<form name="refreshForm" action="manifestviewer.php" method="get" style="display:inline;" title="Refresh Counts and Sample Table">
								<input name="shipmentPK" type="hidden" value="<?php echo $shipmentPK; ?>" >
								<input type="image" src="../../images/refresh.png" style="width:15px;" >
							</form>
						</div>
						<div style="margin-left:15px">
							<div class="displayFieldDiv"><b>Not Checked In:</b> <?php echo $sampleCntArr[0]; ?></div>
							<div class="displayFieldDiv"><b>Missing Occurrence Link:</b> <?php echo $sampleCntArr[1]; ?></div>
						</div>
						<?php
						if($shipArr['checkinTimestamp'] && $sampleCntArr[0]){
							?>
							<div id="sampleCheckinDiv" style="margin-top:15px;background-color:white;top:50px;right:200px">
								<fieldset style="padding:10px;width:500px">
									<legend>Sample Check-in</legend>
									<input type="radio" class="start_session" name="session" value="start"> Start Session
									<input type="radio" class="stop_session" name="session" value="stop"> Stop Session
									<div class="timer">00:00:00</div>
									<div class="sessionName"></div>
									<form name="submitform" method="post" onsubmit="checkinSample(this); return false;">
										<div id="popoutDiv" style="float:right"><a href="#" onclick="popoutCheckinBox();return false" title="Popout Sample Check-in Box">&gt;&gt;</a></div>
										<div id="bindDiv" style="float:right;display:none"><a href="#" onclick="bindCheckinBox();return false" title="Bind Sample Check-in Box to top of form">&lt;&lt;</a></div>
										<div class="displayFieldDiv">
											<b>Identifier:</b> <input name="identifier" type="text" style="width:250px" required />
											<div id="checkinText" style="display:inline"></div>
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
												$condArr = $shipManager->getConditionArr();
												foreach($condArr as $condKey => $condValue){
													echo '<option value="'.$condKey.'" '.($condKey=='ok'?'SELECTED':'').'>'.$condValue.'</option>';
												}
												?>
											</select>
										</div>
										<div class="displayFieldDiv">
											<b>Alternative ID:</b> <input name="alternativeSampleID" type="text" style="width:225px" />
										</div>
										<div class="displayFieldDiv">
											<b>Remarks:</b> <input name="checkinRemarks" type="text" style="width:300px" />
										</div>
										<div class="displayFieldDiv">
											<input name="formReset" type="checkbox" checked /> reset form after each submission
										</div>
										<div class="displayFieldDiv">
											<button type="submit">Submit</button>
										</div>
									</form>
								</fieldset>
							</div>
							<?php
						}
						?>
					</div>
				</div>
				<div style="margin-left:40px;float:left;">
					<?php
					if(!$shipArr['checkinTimestamp']){
						?>
						<fieldset style="padding:10px;">
							<legend>Check-in Shipment</legend>
							<form action="manifestviewer.php" method="post">
								<?php
								$deliveryArr = $shipManager->getDeliveryArr();
								?>
								<div><b>Received By:</b> <input name="receivedBy" type="text" value="<?php echo (isset($deliveryArr['receivedBy'])?$deliveryArr['receivedBy']:''); ?>" required /></div>
								<div>
									<b>Delivery Date:</b>
									<input name="receivedDate" type="date" value="<?php echo (isset($deliveryArr['receivedDate'])?$deliveryArr['receivedDate']:''); ?>" required />
									<input name="receivedTime" type="time" value="<?php echo (isset($deliveryArr['receivedTime'])?$deliveryArr['receivedTime']:''); ?>" />
								</div>
								<div><b>Comments:</b> <input id="shipCheckinComment" name="notes" type="text" value="" style="width:350px" onchange="checkinCommentChanged(this);" /></div>
								<input name="shipmentPK" type="hidden" value="<?php echo $shipmentPK; ?>" />
								<div style="float:right;margin:40px 15px 0px 0px"><a href="manifestviewer.php?sampleFilter=displaySamples&shipmentPK=<?php echo $shipmentPK; ?>#samplePanel">Display Samples</a></div>
								<div style="margin:10px"><button name="action" type="submit" value="checkinShipment"> -- Mark as Arrived -- </button></div>
							</form>
						</fieldset>
						<?php
					}
					?>
				</div>
				<?php
				if($shipArr['checkinTimestamp'] || $sampleFilter == 'displaySamples'){
					$headerList = $shipManager->getSampleHeaders(null, $sampleFilter);
					?>
					<div style="clear:both;padding-top:30px;">
						<fieldset id="samplePanel">
							<legend>Sample Listing</legend>
							<div>
								<div style="float:right;">
									<form id="filterSampleForm" style="">
										Filter by:
										<select name="sampleFilter" id="sampleFilter">
											<option value="">All Records</option>
											<option value="notCheckedIn">Not Checked In</option>
											<option value="missingOccid">Missing Occurrences</option>
											<option value="notAccepted">Not Accepted for Analysis</option>
											<option value="altIds">Has Alternative IDs</option>
											<option value="harvestingError">Harvesting Errors</option>
										</select>
										<input name="shipmentPK" type="hidden" value="<?php echo $shipmentPK; ?>" />
									</form>
								</div>
							</div>
							<div style="clear:both">
								<?php
								if($headerList){
									?>
									<form name="sampleListingForm" action="manifestviewer.php" method="post" onsubmit="return batchCheckinFormVerify(this)">
										<table id="manifestTable" class="stripe hover compact" style="width:100 !important;">
											<thead>
												<tr>
												<?php
												$headerArr = array(
													'samplePK' => 'Sample PK',
													'sampleID' => 'Sample ID',
													'sampleCode' => 'Sample Code',
													'sampleClass' => 'Sample Class',
													'taxonID' => 'Taxon ID',
													'namedLocation' => 'Named Location',
													'collectDate' => 'Collection Date',
													'quarantineStatus' => 'Quarantine Status',
													'sampleReceived' => 'Sample Received',
													'acceptedForAnalysis' => 'Accepted for Analysis',
													'sampleCondition' => 'Sample Condition',
													'checkinTimestamp' => 'Check-in',
													'occid' => 'occid'
												);
												
												//echo '<th><input name="selectall" type="checkbox" onclick="selectAll(this)" /></th>';
												$rowCnt = 1;
												
												foreach ($headerArr as $fieldName => $headerTitle) {
													if ($fieldName === 'samplePK') {
														echo '<th><input name="selectall" type="checkbox" onclick="selectAll(this)" /></th>';
													} else {
														echo '<th>' . $headerTitle . '</th>';
													}
												}
												?>
												</tr>
											</thead>
										</table>
										<div style="margin:15px;float:left">
											<input name="shipmentPK" type="hidden" value="<?php echo $shipmentPK; ?>" />
											<fieldset style="width:450px;">
												<legend>Batch Check-in Selected Samples</legend>
												<?php
												if($shipArr['checkinTimestamp']){
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
															$condArr = $shipManager->getConditionArr();
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
										if($shipArr['checkinTimestamp']){
											?>
											<div style="margin:15px;float:left">
												<div style="margin:5px;width:200px">
													<a href="#" onclick="addSample(<?php echo $shipmentPK; ?>);return false;"><button name="addSampleButton" type="button">Add New Sample</button></a>
												</div>
												<fieldset style="margin:5px;float:left">
													<legend>Occurrence Harvesting</legend>
													<button name="action" type="submit" value="batchHarvestOccid">Batch Harvest</button>
													<div style="margin:10px" title="Upon reharvesting, replaces existing field values, but only if they haven't been explicitly edited to another value">
														<input name="replaceFieldValues" type="checkbox" value="1" /> Replace Existing Field Values
													</div>
												</fieldset>
												<?php
												$tagArr = array();
												if($tagArr){
													?>
													<fieldset style="margin:5px;float:left">
														<legend>Batch select based on plate or container IDs</legend>
														<div style="margin:10px">
															<?php
															if(array_key_exists('containerid',$tagArr)){
																?>
																<select name="batchContainerID" onchange="batchSelectSamples(this);">
																	<option value="">Select Container ID</option>
																	<option value="">----------------------</option>
																	<?php
																	$containerArr = $tagArr['containerid'];
																	ksort($containerArr);
																	foreach($containerArr as $containerTag => $cnt){
																		echo '<option value="'.str_replace(' ','_',$containerTag).'">'.$containerTag.' ('.$cnt.')'.'</option>';
																	}
																	?>
																</select>
																<?php
															}
															?>
														</div>
														<div style="margin:10px">
															<?php
															if(array_key_exists('plateid',$tagArr)){
																?>
																<select name="batchPlateID" onchange="batchSelectSamples(this);">
																	<option value="">Select Plate ID</option>
																	<option value="">----------------------</option>
																	<?php
																	$plateArr = $tagArr['plateid'];
																	ksort($plateArr);
																	foreach($plateArr as $plateTag => $cnt){
																		echo '<option value="'.str_replace(' ','_',$plateTag).'">'.$plateTag.' ('.$cnt.')'.'</option>';
																	}
																	?>
																</select>
																<?php
															}
															?>
														</div>
														<div style="margin:10px">
															<?php
															if(array_key_exists('platebarcode',$tagArr)){
																?>
																<select name="batchPlateBarcode" onchange="batchSelectSamples(this);">
																	<option value="">Select Plate Barcode</option>
																	<option value="">----------------------</option>
																	<?php
																	$plateBarcodeArr = $tagArr['platebarcode'];
																	ksort($plateBarcodeArr);
																	foreach($plateBarcodeArr as $barcodeTag => $cnt){
																		echo '<option value="'.str_replace(' ','_',$barcodeTag).'">'.$barcodeTag.' ('.$cnt.')'.'</option>';
																	}
																	?>
																</select>
																<?php
															}
															?>
														</div>
														<div id="selectedMsgDiv" style="margin:10px;color:orange"></div>
													</fieldset>
													<?php
												}
												?>
											</div>
											<?php
										}
										?>
									</form>
									<div style="clear:both">
										<div style="float:left;margin-left:15px;">
											<fieldset style="width:450px;">
												<a id="receiptStatus"></a>
												<legend>Receipt Status</legend>
												<?php
												if($shipArr['checkinTimestamp']){
													?>
													<form name="receiptSubmittedForm" action="manifestviewer.php#receiptStatus" method="post">
														<?php
														$receiptStatus = (!empty($shipArr['receiptStatus'])?$shipArr['receiptStatus']:'');
														$submittedBy = '';
														$submittedTimestamp = (!empty($shipArr['receiptTimestamp'])?$shipArr['receiptTimestamp']:'');
														if($statusArr = explode(':', $receiptStatus)){
															$receiptStatus = $statusArr[0];
															if(isset($statusArr[1])) $submittedBy = $statusArr[1];
															$submittedTimestamp = '';
															if(!empty($shipArr['receiptTimestamp'])) $submittedTimestamp = $shipArr['receiptTimestamp'];
														}
														?>
														<input name="submitted" type="radio" value="" <?php echo (!$receiptStatus?'checked':''); ?> onchange="this.form.submit()" />
														<b>Status Not Set</b><br/>
														<input name="submitted" type="radio" value="1" <?php echo ($receiptStatus=='Downloaded'?'checked':''); ?> onchange="this.form.submit()" />
														<b>Receipt Downloaded</b><br/>
														<input name="submitted" type="radio" value="2" <?php echo ($receiptStatus=='Submitted'?'checked':''); ?> onchange="this.form.submit()" />
														<b>Receipt Submitted to NEON</b>
														<?php
														if($receiptStatus){
															echo '<div style="margin-left:15px">';
															echo '<div>Performed by: '.$submittedBy.'</div>';
															echo '<div>Timestamp: '.$submittedTimestamp.'</div>';
															echo '</div>';
														}
														?>
														<input name="shipmentPK" type="hidden" value="<?php echo $shipmentPK; ?>" />
														<input name="action" type="hidden" value="receiptsubmitted" />
													</form>
													<div style="margin:15px">
														<form name="exportReceiptForm" action="exporthandler.php" method="post">
															<input name="shipmentPK" type="hidden" value="<?php echo $shipmentPK; ?>" />
															<input name="exportTask" type="hidden" value="receipt" />
															<button name="action" type="submit" value="downloadReceipt">Download Receipt</button>
														</form>
														<div style="margin-top:15px">
															<a href="http://data.neonscience.org/web/external-lab-ingest" target="_blank"><b>Proceed to NEON submission page</b></a>
														</div>
													</div>
													<?php
												}
												else{
													echo '<div style="color:orange;margin-bottom:140px">Shipment needs to be checked in before receipt can be submitted</div>';
												}
												?>
											</fieldset>
										</div>
										<div style="float:left;margin-left:30px;margin-top:10px;">
											<form name="exportSampleListForm" action="exporthandler.php" method="post" style="">
												<input name="shipmentPK" type="hidden" value="<?php echo $shipmentPK; ?>" />
												<input name="exportTask" type="hidden" value="sampleList" />
												<div style="margin:10px 0px"><button name="action" type="submit" value="exportSampleListing">Export Sample Listing</button></div>
											</form>
											<div ><a href="manifestloader.php"><button name="loadManifestButton" type="button">Load Another Manifest</button></a></div>
										</div>
										<?php
										$collectionArr = $shipManager->getCollectionArr();
										if($collectionArr){
											?>
											<div style="float:left;margin:0px 30px;">
												<fieldset style="width:400px;padding:15px;">
													<legend>Append Data to Occurrence Records via File Upload</legend>
													<?php
													foreach($collectionArr as $collid => $collName){
														echo '<div><a href="../../collections/admin/specupload.php?uploadtype=7&matchothercatnum=1&collid='.$collid.'" target="_blank">'.$collName.'</a></div>';
													}
													?>
												</fieldset>
											</div>
											<?php
										}
										?>
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
				?>
			</fieldset>
			<?php
		}
		else{
			if($quickSearchTerm){
				echo '<h2>Search term ' . htmlspecialchars($quickSearchTerm) . ' failed to return results</h2>';
			}
			else{
				echo '<h2>Shipment does not exist or has been deleted</h2>';
			}
			?>
			<div style="margin:10px">
				<b>Quick search:</b>
				<form name="sampleQuickSearchFrom" action="manifestviewer.php" method="post" style="display: inline" >
					<input name="quicksearch" type="text" value="" onchange="this.form.submit()" style="width:250px;" />
				</form>
			</div>
			<div style="margin:10px">
				<h3><a href="manifestsearch.php">List Manifests</a></h3>
			</div>
			<?php
		}
	}
	else{
		?>
		<div style='font-weight:bold;margin:30px;'>
			You do not have permissions to view manifests
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