<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/InquiriesManager.php');
header('Content-Type: text/html; charset=' . $CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=' . $CLIENT_ROOT . '/neon/requests/inquiryform.php?' . $_SERVER['QUERY_STRING']);

$requestID = array_key_exists('id', $_REQUEST) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_NUMBER_INT) : '';
$sampleFilter = isset($_REQUEST['sampleFilter']) ? $_REQUEST['sampleFilter'] : '';
$quickSearchTerm = array_key_exists('quicksearch', $_REQUEST) ? $_REQUEST['quicksearch'] : '';
$sortableTable = isset($_REQUEST['sortabletable']) ? filter_var($_REQUEST['sortabletable'], FILTER_SANITIZE_NUMBER_INT) : false;
$action = array_key_exists('action', $_REQUEST) ? $_REQUEST['action'] : '';

$inquiryManager = new inquiriesManager();
$sampleCnt = $inquiryManager->getSampleCountByID($requestID);
$researchers = $inquiryManager->getResearchersByID($requestID);
$pc = $inquiryManager->getPrimaryContactByID($requestID);
$managers = $inquiryManager->getManagers();

if($sortableTable === false){
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
			
			<?php
			if($sortableTable){
				?>
				$('#sampletable').DataTable({
					pageLength: 500,
					lengthMenu: [10, 50, 100, 500, { label: 'All', value: -1 }],
					scrollCollapse: true,
					fixedHeader: true,
					columnDefs: [
						{ orderable: false, targets: [0, -1] }
					]
				});
				$("#sampletable").DataTable().rows().every( function () {
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

		});

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

		function openSampleEditor(id, requestId){
			var url = "sampleeditor.php?id=" + id + "&requestID=" + requestId;
			openPopup(url, "sample1window");
			return false;
		}

		function openBatchEditor() {
			const form = document.forms['sampleListingForm'];
			const checked = [...form.querySelectorAll('input[name="scbox[]"]:checked')];
			if(checked.length === 0){
				alert("Please select at least one sample");
				return false;
			}

			const batchForm = document.createElement("form");
			batchForm.method = "post";
			batchForm.action = "batchsampleeditor.php";
			batchForm.target = "batchEditorWindow";
			const req = document.createElement("input");
			req.type = "hidden";
			req.name = "requestID";
			req.value = "<?= $requestID ?>";
			batchForm.appendChild(req);

			checked.forEach(cb => {
				let hidden = document.createElement("input");
				hidden.type = "hidden";
				hidden.name = "ids[]";
				hidden.value = cb.value;
				batchForm.appendChild(hidden);
			});

			document.body.appendChild(batchForm);

			const popup = window.open(
				"",
				"batchEditorWindow",
				"scrollbars=1,toolbar=0,resizable=1,width=1000,height=700,left=50,top=100"
			);

			batchForm.submit();

			document.body.removeChild(batchForm);
		}

		function openAvailabilityForm() {
			const form = document.forms['sampleListingForm'];
			const checked = [...form.querySelectorAll('input[name="scbox[]"]:checked')];
			if(checked.length === 0){
				alert("Please select at least one sample");
				return false;
			}

			const availabilityForm = document.createElement("form");
			availabilityForm.method = "post";
			availabilityForm.action = "availabilitysync.php";
			availabilityForm.target = "AvailabilitySyncWindow";
			const req = document.createElement("input");
			req.type = "hidden";
			req.name = "requestID";
			req.value = "<?= $requestID ?>";
			availabilityForm.appendChild(req);

			checked.forEach(cb => {
				let hidden = document.createElement("input");
				hidden.type = "hidden";
				hidden.name = "ids[]";
				hidden.value = cb.value;
				availabilityForm.appendChild(hidden);
			});

			document.body.appendChild(availabilityForm);

			const popup = window.open(
				"",
				"AvailabilitySyncWindow",
				"scrollbars=1,toolbar=0,resizable=1,width=1000,height=700,left=50,top=100"
			);

			availabilityForm.submit();

			document.body.removeChild(availabilityForm);
		}

		function openPopup(url,windowName){
			newWindow = window.open(url,windowName,'scrollbars=1,toolbar=0,resizable=1,width=1000,height=500,left=20,top=100');
			if (newWindow.opener == null) newWindow.opener = self;
			return false;
		}

		async function handleDataset(requestID) {
			try {
				let response = await fetch(`datasethandler.php?id=${requestID}&action=check`);
				let data = await response.json();

				if (data.exists) {
					// dataset exists, go straight to manager
					window.location.href = `../datasets/neondatasetmanager.php?datasetid=${data.datasetid}`;
				} else {
					// Ask user if they want to create a new dataset
					if (!confirm("No dataset exists for this request. Do you want to create a new dataset?")) {
						return; // user cancelled
					}

					// user confirmed, create dataset
					response = await fetch(`datasethandler.php?id=${requestID}&action=create`);
					const text = await response.text();
					let createData;
					try {
						createData = JSON.parse(text);
					} catch (e) {
						console.error("Non-JSON response:", text);
						alert("Failed to communicate with the dataset handler. See console for details.");
						return;
					}

					if (createData.success) {
						window.location.href = `../datasets/neondatasetmanager.php?datasetid=${createData.datasetid}`;
					} else {
						alert("Error creating dataset: " + createData.error);
					}
				}
			} catch (err) {
				console.error("Dataset handler fetch error:", err);
				alert("Failed to communicate with the dataset handler.");
			}
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
<div id="innertext">
	<?php
	if($isEditor){
		$reqArr = $inquiryManager->getRequestData($requestID);
		if($reqArr){
			?>
			<fieldset style="margin-top:30px">
				<legend>Samples Associated with Request</legend>
				<div style="float:left;margin-right:40px;box-sizing:border-box;">
					<div class="displayFieldDiv">
						<b>Request ID:</b> <?php echo $requestID; ?>
						<a href="<?php echo $CLIENT_ROOT . '/neon/requests/inquiryform.php?id=' . $requestID; ?>">
							<strong>Back to Inquiry Record</strong>
						</a>
					</div>
					<div class="displayFieldDiv"><b>Title:</b> <?php echo $reqArr['title']; ?></div>
					<div class="displayFieldDiv">
						<b>Primary Contact:</b> <?php echo $reqArr['name']; ?>

						<a href="#"
						id="editResearcherBtn"
						data-researcherid="<?php echo $pc['researcherID']; ?>">
							<strong>Edit Contact Info</strong>
						</a>
					</div>
					<?php if (!$pc['contactEmail'] | !$pc['orcid']) { ?>
						<div style="clear:both;font-weight:bold;color:red;">
							The primary contact must have a NEON account and ORCID before a shipment or pseudoshipment can be linked.
						</div>
					<?php } ?>
					<div class="displayFieldDiv"><b>Status:</b> <?php echo $reqArr['status']; ?></div>
					<?php if ((!in_array($reqArr['status'],array('active use','completed')) && $sampleCnt > 0) | (in_array($reqArr['status'],array('active use','completed','pending fulfillment')) && !$sampleCnt))  { ?>
						<div style='font-weight:bold;margin:30px;color:red;'>
							Check status of request relative to samples. Edit request if necessary
						</div>
					<?php } ?>
					<div class="displayFieldDiv"><b>Number of Samples Linked:</b> <?php echo $sampleCnt; ?>
					<?php if(in_array($reqArr['status'],array('pending funding','pending sample list','pending fulfillment','active use','completed'))){ ?> 
						<a href="<?php echo $CLIENT_ROOT . '/neon/requests/importrequestsample.php?id=' . $requestID; ?>">
							<strong>Batch Load Samples</strong>
						</a>
					<?php } ?>
					</div>
					<div style="clear:both; padding:10px 0; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">

						<b>Export as:</b>
						<?php 
							$type = 'request';
							$pubID = $requestID; 
						?>

						<form name="exportPubTable" action="exporthandler.php" method="post" style="margin:0;">
							<input type="hidden" name="pubID" value="<?php echo $pubID; ?>" />
							<input type="hidden" name="type" value="<?php echo $type; ?>" />
							<input type="hidden" name="exportTask" value="pubtable" />
							<button type="submit" name="action" value="exportPubTable">
								Publication-Ready Table
							</button>
						</form>

						<form name="exportSampleListForm" action="exporthandler.php" method="post" style="margin:0;">
							<input type="hidden" name="requestID" value="<?php echo $requestID; ?>" />
							<input type="hidden" name="exportTask" value="samplesrequest" />
							<button type="submit" name="action" value="exportSampleListing">
								Samples
							</button>
						</form>

						<form name="exportOccurForm" action="exporthandler.php" method="post" style="margin:0;">
							<input type="hidden" name="requestID" value="<?php echo $requestID; ?>" />
							<input type="hidden" name="exportTask" value="occurrences" />
							<button type="submit" name="action" value="exportOccurListing">
								Occurrences
							</button>
						</form>
						
					</div>
				<?php
				if($sampleCnt > 0) {
					$sampleList = $inquiryManager->getSamplesByID($requestID, $sampleFilter);
					if ($pc['contactEmail'] && $pc['orcid']) { ?>
						<div style="clear:both;padding:10px 0;">
							<div style="float:left;">
								<button type="button"
										class="addShipmentButton"
										data-target="primary">
									Create New Shipment/Pseudoshipment
								</button>
							</div>
						</div>
					<?php } ?>
					<div style="clear:both;padding:10px 0;">
						<div style="float:left;">
							<button type="button" onclick="handleDataset(<?= $requestID ?>)">
								Dataset Management
							</button>
						</div>
					</div>
					<!-- <div style="clear:both;padding:10px 0;">
						<div style="float:left;margin-right:10px;">
						<a href="#" onclick="return addSample(<?php echo $requestID; ?>);">
							<button type="button">Add New Sample</button>
						</a>
					</div> -->
					<div style="clear:both;padding:10px 0;">
						<div style="float:left;">
							<div style="float:left;">
								<button type="button" onclick="openBatchEditor()">Batch Edit Selected Samples</button>
							</div>
						</div>
					</div>
					<div style="clear:both;padding:10px 0;">
						<div style="float:left;">
							<div style="float:left;">
								<button type="button" onclick="openAvailabilityForm()">Sync Availability/Disposition Values</button>
							</div>
						</div>	
					</div>
					<div style="clear:both;padding-top:30px;">
						<fieldset id="samplePanel">
							<legend>Sample Listing</legend>
							<div>
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
											<input name="id" type="hidden" value="<?php echo $requestID; ?>" />
									</form>
								</div>
							</div>
							<div style="clear:both">
								<?php if($sampleList){ ?>
									<form name="sampleListingForm" action="samplelist.php" method="post" onsubmit="return sampleFormVerify(this)">
										<input name="sortabletable" type="hidden" value="<?= $sortableTable ?>">
										<table id="sampletable" class="styledtable">
											<thead>
												<tr>
													<?php
													$headerOutArr = current($sampleList);
													echo '<th><input name="selectall" type="checkbox" onclick="selectAll(this)" /></th>';
													$headerArr = array('sampleID' => 'sampleID','sampleClass' => 'sampleClass',
																'sampleCode' =>'sampleCode','status'=>'Status', 'useType'=>'Use Type', 
																'substanceProvided'=>'Substance Provided', 'available'=>'Available',
																'notes'=> 'Notes','shipmentID' => 'Shipment ID','occid'=> 'occid');
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
													if($sortableTable){
														if($str) {
															echo '<tr class="sample-row" data-child-value="'.trim($str,'; ').'">';
														} else {
															echo '<tr class="sample-row">';
														}
													}
													echo '<td>';
													echo '<input id="scbox-'.$sampleArr['id'].'" class="'.trim($classStr).'" name="scbox[]" type="checkbox" value="'.$sampleArr['id'].'" />';
													echo '<a href="#" onclick="return openSampleEditor('.$sampleArr['id'].', '.$requestID.')"><img src="../../images/edit.png" style="width:12px" /></a>';
													echo '</td>';

													echo '<td>'.$sampleArr['sampleID'].'</td>';
													echo '<td>'.$sampleArr['sampleClass'].'</td>';
													echo '<td>'.$sampleArr['sampleCode'].'</td>';
													echo '<td>'.$sampleArr['status'].'</td>';
													echo '<td>'.$sampleArr['useType'].'</td>';
													echo '<td>'.$sampleArr['substanceProvided'].'</td>';
													echo '<td>'.$sampleArr['available'].'</td>';
													echo '<td>'.$sampleArr['notes'].'</td>';
													echo '<td>'.$sampleArr['shipmentID'].'</td>';
													
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
									</form>
									<?php
								} else {
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
		} else { 
			echo '<h2>Request does not exist</h2>';
		}
	} else { ?>
		<div style='font-weight:bold;margin:30px;'>
			Please login with administrator privileges to get access to this page.
		</div>
	<?php } ?>
</div>


	<div id="researcherModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; justify-content:center; align-items:center;">
		<div style="background:#fff; padding:20px; border-radius:6px; width:400px; max-width:90%; position:relative;">
			<h2>Edit Researcher</h2>

			<form id="researcherForm">
				<input type="hidden" name="researcherID" id="researcherID">

				<label>Name*:</label>
				<input type="text" name="name" required readonly style="width:100%;"><br><br>

				<label>Institution*:</label>
				<input type="text" name="institution" required style="width:100%;"><br><br>

				<label>Email:</label>
				<input type="text" name="contactEmail" style="width:100%;"><br><br>

				<label>ORCID:</label>
				<input type="text" name="orcid" style="width:100%;"><br><br>

				<label>Address:</label>
				<input type="text" name="address" style="width:100%;"><br><br>

				<label>Phone:</label>
				<input type="text" name="phone" style="width:100%;"><br><br>

				<button type="submit">Save</button>
				<button type="button" id="closeResearcherModal">Cancel</button>
			</form>
		</div>
	</div>

	<div id="shipmentModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
		background:rgba(0,0,0,0.6); z-index:9999; justify-content:center; align-items:center;">
		<div style="background:#fff; padding:20px; border-radius:6px; width:400px; position:relative;">
			<h2>Add New Shipment or Pseudoshipment</h2>
			<form id="shipmentform">
				<label><b>Recipient:</b> (if recipient is not present, link researcher to the request on the Inquiry Info tab)</label>
				<select name="researcherid" required style="width:100%; margin-bottom:15px;">
					<option value="">-- Select Researcher --</option>
					<?php 
					$researchers = $inquiryManager->getResearchersByID($requestID); 
					foreach($researchers as $id => $name): ?>
						<option value="<?= htmlspecialchars($id) ?>">
							<?= htmlspecialchars($name) ?>
						</option>
					<?php endforeach; ?>
				</select>
				
				<label><b>Date of Shipment or Data/Image Transfer:</b></label>
				<input type="date" name="shipdate" required style="width:100%;"><br><br>

				<label><b>Address: (input "NA" if data/image transfer only)</b></label>
				<input type="text" name="address" required style="width:100%;"><br><br>

				<label><b>Person who shipped samples or transferred data/images:</b></label>
				<select name="shippedby" required style="width:100%; margin-bottom:15px;">
					<option value="">-- Select Manager --</option>
					<?php foreach($managers as $id => $name): ?>
						<option value="<?= htmlspecialchars($id) ?>">
							<?= htmlspecialchars($name) ?>
						</option>
					<?php endforeach; ?>
				</select>
				<input type="hidden" name="requestID" value="<?php echo (int)$requestID; ?>">
				<button type="submit">Save</button>
				<button type="button" id="closeShipmentModal">Cancel</button>
			</form>
		</div>
	</div>

<?php
include($SERVER_ROOT.'/includes/footer.php');
?>

<script>

	$("#editResearcherBtn").on("click", function() {

		$("#researcherID").val("<?php echo $pc['researcherID']; ?>");

		$("[name=name]").val(<?php echo json_encode($pc['name']); ?>);
		$("[name=institution]").val(<?php echo json_encode($pc['institution']); ?>);
		$("[name=contactEmail]").val(<?php echo json_encode($pc['contactEmail']); ?>);
		$("[name=orcid]").val(<?php echo json_encode($pc['orcid']); ?>);
		$("[name=address]").val(<?php echo json_encode($pc['address']); ?>);
		$("[name=phone]").val(<?php echo json_encode($pc['phone']); ?>);

		$("[name=name]").prop("readonly", true);

		$("#researcherModal h2").text("Edit Researcher");

		$("#researcherModal").css("display", "flex");
	});

	document.getElementById('closeResearcherModal').addEventListener('click', function(){
		document.getElementById('researcherModal').style.display = 'none';
	});

	document.getElementById('closeShipmentModal').addEventListener('click', function(){
		document.getElementById('shipmentModal').style.display = 'none';
	});

	document.getElementById('researcherForm').addEventListener('submit', function(e){
		e.preventDefault();
		let formData = new FormData(this);

		fetch('../../neon/requests/add_researcher.php', { method: 'POST', body: formData })
		.then(res => res.json())
		.then(data => {
			if(data.success){
				const label = data.name + ' (' + data.institution + ')';

				if(document.getElementById('researcherModal').dataset.source === 'primary') {

					$("#researcherSearch").val(label);
					$("#inqresearcher").val(data.researcherID);
				}

				if(document.getElementById('researcherModal').dataset.source === 'additional') {

					if(!window.selectedResearchers.find(r => r.id == data.researcherID)) {

						window.selectedResearchers.push({
							id: data.researcherID,
							label: label
						});

						renderAdditionalResearchers();
						markPendingChanges();
					}
				}

				document.getElementById('researcherModal').style.display = 'none';
				document.getElementById('researcherForm').reset();

				alert('Researcher added or edited successfully.');
			} else {
				alert('Error: ' + data.message);
			}
		})
		.catch(err => alert('Request failed'));
	});

	document.addEventListener('DOMContentLoaded', function() {
		const modal = document.getElementById('shipmentModal');
		const shipmentForm = document.getElementById('shipmentform');

		if (!shipmentForm) {
			console.error('shipmentForm not found in DOM!');
			return;
		}

		document.querySelectorAll('.addShipmentButton').forEach(btn => {
			btn.addEventListener('click', function() {
				modal.dataset.source = btn.dataset.target || '';
				modal.style.display = 'flex';
			});
		});

		document.getElementById('closeShipmentModal').addEventListener('click', function() {
			modal.style.display = 'none';
			shipmentForm.reset(); 
		});
		
		document.getElementById('closeResearcherModal').addEventListener('click', function() {
			modal.style.display = 'none';
			shipmentForm.reset(); 
		});


		modal.addEventListener('click', function(e) {
			if (e.target === modal) {
				modal.style.display = 'none';
				shipmentForm.reset();
			}
		});

		shipmentForm.addEventListener('submit', function(e) {
			e.preventDefault();
			const formData = new FormData(shipmentForm);

			console.log("Posting shipment form:", ...formData.entries());

			fetch('../../neon/requests/add_shipment.php?id=<?php echo (int)$requestID; ?>', {
				method: 'POST',
				body: formData
			})
			.then(res => res.json())
			.then(data => {
				if (data.success) {
					modal.style.display = 'none';
					shipmentForm.reset();
					alert('Shipment created successfully. You can now link it to samples within the request.');
				} else {
					alert('Error: ' + data.message);
				}
			})
			.catch(err => {
				console.error(err);
				alert('Request failed: ' + err);
			});
		});
	});


</script>

</body>
</html>