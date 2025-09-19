<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/requests/InquiriesManager.php');
header('Content-Type: text/html; charset=' . $CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=' . $CLIENT_ROOT . '/neon/requests/inquiryform.php?' . $_SERVER['QUERY_STRING']);

$request_id = array_key_exists('id', $_REQUEST) ? filter_var($_REQUEST['id'], FILTER_SANITIZE_NUMBER_INT) : '';
$sampleFilter = isset($_REQUEST['sampleFilter']) ? $_REQUEST['sampleFilter'] : '';
$quickSearchTerm = array_key_exists('quicksearch', $_REQUEST) ? $_REQUEST['quicksearch'] : '';
$sortableTable = isset($_REQUEST['sortabletable']) ? filter_var($_REQUEST['sortabletable'], FILTER_SANITIZE_NUMBER_INT) : false;
$action = array_key_exists('action', $_REQUEST) ? $_REQUEST['action'] : '';

$inquiryManager = new inquiriesManager();
$sampleCnt = $inquiryManager->getMaterialSampleCountByID($request_id);
$researchers = $inquiryManager->getResearchersByID($request_id);
$managers = $inquiryManager->getManagers();

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
	<title><?php echo $DEFAULT_TITLE; ?> Material Sample Request List Viewer</title>
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
				$('#materialsampletable').DataTable({
					paging: false,
					scrollCollapse: true,
					fixedHeader: true,
					columnDefs: [{ orderable: false, targets: [0, -1]}],
					});
				$("#materialsampletable").DataTable().rows().every( function () {
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

		function openMaterialSampleEditor(id){
			var url = "materialsampleeditor.php?id="+id;
			openPopup(url,"sample1window");
			return false;
		}

		function openMaterialSampleBatchEditor() {
			const form = document.forms['materialSampleListingForm'];
			const checked = [...form.querySelectorAll('input[name="scbox[]"]:checked')];
			if(checked.length === 0){
				alert("Please select at least one sample");
				return false;
			}

			const batchForm = document.createElement("form");
			batchForm.method = "post";
			batchForm.action = "batchmaterialsampleeditor.php";
			batchForm.target = "batchMaterialEditorWindow";
			const req = document.createElement("input");
			req.type = "hidden";
			req.name = "request_id";
			req.value = "<?= $request_id ?>";
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
				"batchMaterialEditorWindow",
				"scrollbars=1,toolbar=0,resizable=1,width=1000,height=700,left=50,top=100"
			);

			batchForm.submit();

			document.body.removeChild(batchForm);
		}

		function openPopup(url,windowName){
			newWindow = window.open(url,windowName,'scrollbars=1,toolbar=0,resizable=1,width=1000,height=500,left=20,top=100');
			if (newWindow.opener == null) newWindow.opener = self;
			return false;
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

		#shipmentModal {
			display: flex;
			position: fixed;
			top: 0; left: 0; width: 100%; height: 100%;
			background: rgba(0,0,0,0.6);
			z-index: 9999;
			justify-content: center;
			align-items: center;
		}
		#shipmentModal.show {
			display: flex;
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
	<a href="../../neon/requests/inquiries.php">Inquiry List</a> &gt;&gt;
	<a href="inquiryform.php?id=<?php echo $request_id; ?>">Inquiry Record</a> &gt;&gt;
	<b>Sample List</b>
</div>
<div id="innertext">
	<?php
	if($isEditor){
		$reqArr = $inquiryManager->getRequestData($request_id);
		if($reqArr){
			?>
			<fieldset style="margin-top:30px">
				<legend>Material Samples Associated with Request</legend>
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
					<div class="displayFieldDiv"><b>Number of Material Samples Linked:</b> <?php echo $sampleCnt; ?></div>
				</div>
				<?php
				if(in_array($reqArr['status'],array('pending sample list','active use','completed','pending funding')) && $sampleCnt){
					$materialSampleList = $inquiryManager->getMaterialSamplesByID($request_id, $sampleFilter);
					?>
					<div style="clear:both;padding:10px 0;">
						<!-- <div style="float:left;margin-right:10px;">
							<a href="#" onclick="return addSample(<?php echo $request_id; ?>);">
								<button type="button">Add New Sample</button>
							</a>
						</div> -->
						<div style="float:left;">
							<form name="exportMaterialSampleListForm" action="exporthandler.php" method="post">
								<input type="hidden" name="request_id" value="<?php echo $request_id; ?>" />
								<input type="hidden" name="exportTask" value="materialsamplesrequest" />
								<button type="submit" name="action" value="exportMaterialSampleList">
									Export Material Samples
								</button>
							</form>
						</div>
					</div>
					<div style="clear:both;padding:10px 0;">
						<div style="float:left;">
							<form name="exportMaterialTableForm" action="exporthandler.php" method="post">
								<input type="hidden" name="request_id" value="<?php echo $request_id; ?>" />
								<input type="hidden" name="exportTask" value="materialsamplestable" />
								<button type="submit" name="action" value="exportMaterialSampleTable">
									Export Material Sample Occurrence Table
								</button>
							</form>
						</div>
					</div>
					<div style="clear:both;padding:10px 0;">
						<div style="float:left;">
							<button type="button" class="addShipmentButton" data-target="primary">Create New Shipment</button>
						</div>
					</div>
					<div style="clear:both;padding:10px 0;">
						<div style="float:left;">
							<div style="float:left;">
								<button type="button" onclick="openMaterialSampleBatchEditor()">Batch Edit Selected Material Samples</button>
							</div>
					</div>
					<div style="clear:both;padding-top:30px;">
						<fieldset id="samplePanel">
							<legend>Material Sample Listing</legend>
							<div>
								<div style="float:left">Records displayed: <?php echo count($materialSampleList); ?></div>
								<div style="float:left; margin-left: 50px;"><input name="sorthandler" type="checkbox" onchange="tableSortHandlerChanged(this)" <?= ($sortableTable ? 'checked' : '') ?> > Make table sortable</div>
								<div style="float:right;">
									<form name="filterSampleForm" action="materialsamplelist.php#samplePanel" method="post" style="">
										Filter by:
										<select name="sampleFilter" onchange="this.form.submit()">
											<option value="">All Records</option>
											<option value="pendingfulfillment" <?php echo ($sampleFilter=='pendingfulfillment'?'SELECTED':''); ?>>Pending Fulflillment</option>
											<option value="current" <?php echo ($sampleFilter=='current'?'SELECTED':''); ?>>Current</option>
											<option value="complete" <?php echo ($sampleFilter=='complete'?'SELECTED':''); ?>>Complete</option>
                                            <option value="pendingfunding" <?php echo ($sampleFilter=='pendingfunding'?'SELECTED':''); ?>>Pending Funding</option>
										</select>
											<input name="id" type="hidden" value="<?php echo $request_id; ?>" />
									</form>
								</div>
							</div>
							<div style="clear:both">
								<?php
								if($materialSampleList){
									?>
									<form name="materialSampleListingForm" action="materialsamplelist.php" method="post" onsubmit="return materialSampleFormVerify(this)">
										<input name="sortabletable" type="hidden" value="<?= $sortableTable ?>">
										<table id="materialsampletable" class="styledtable">
											<thead>
												<tr>
													<?php
													$headerOutArr = current($materialSampleList);
													echo '<th><input name="selectall" type="checkbox" onclick="selectAll(this)" /></th>';
													$headerArr = array('matSampleID' => 'matSampPK','catalogNumber' => 'Material Sample catalogNumber','sampleID' => 'sampleID','sampleClass' => 'sampleClass',
																'sampleCode' =>'sampleCode','status'=>'Status', 'use_type'=>'Use Type', 
																'sampleType'=>'Sample Type', 'notes' => 'Notes', 'shipment_id' => 'Shipment ID','occid'=> 'occid'
                                                                );
													$rowCnt = 1;
													foreach($headerArr as $fieldName => $headerTitle){
														if(array_key_exists($fieldName, $headerOutArr) || $fieldName == 'matSampleID'){
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
												foreach($materialSampleList as $id => $materialsampleArr){
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
													echo '<input id="scbox-'.$materialsampleArr['id'].'" class="'.trim($classStr).'" name="scbox[]" type="checkbox" value="'.$materialsampleArr['id'].'" />';
													echo '<a href="#" onclick="return openMaterialSampleEditor('.$materialsampleArr['id'].')"><img src="../../images/edit.png" style="width:12px" /></a>';
													echo '</td>';
                                                    
                                                    echo '<td>'.$materialsampleArr['matSampleID'].'</td>';
                                                    echo '<td>'.$materialsampleArr['catalogNumber'].'</td>';
													echo '<td>'.$materialsampleArr['sampleID'].'</td>';
													echo '<td>'.$materialsampleArr['sampleClass'].'</td>';
													echo '<td>'.$materialsampleArr['sampleCode'].'</td>';
													echo '<td>'.$materialsampleArr['status'].'</td>';
													echo '<td>'.$materialsampleArr['use_type'].'</td>';
													echo '<td>'.$materialsampleArr['sampleType'].'</td>';
													echo '<td>'.$materialsampleArr['notes'].'</td>';
													echo '<td>'.$materialsampleArr['shipment_id'].'</td>';
                                                    													
													echo '</td>';
													echo '<td style="text-align:center">';
													echo '<a href="../../collections/individual/index.php?occid=' . $materialsampleArr['occid'] . '" target="_blank">' . $materialsampleArr['occid'] . '</a>';
													echo '</br>';
													echo '</br>';
													echo '<a href="../../collections/editor/occurrenceeditor.php?occid='.$materialsampleArr['occid'].'" target="_blank"><img src="../../images/edit.png" style="width:13px" /></a>';
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
						Update status by editing request before adding material samples
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

<div id="shipmentModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
    background:rgba(0,0,0,0.6); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:20px; border-radius:6px; width:400px; position:relative;">
        <h2>Add New Shipment</h2>
        <form id="shipmentform">
            <label><b>Shipped to:</b> (if researcher is not present, go back to request editor to link researcher to the request)</label>
			<select name="researcher_id" required style="width:100%; margin-bottom:15px;">
				<option value="">-- Select Researcher --</option>
				<?php foreach($researchers as $id => $name): ?>
					<option value="<?= htmlspecialchars($id) ?>">
						<?= htmlspecialchars($name) ?>
					</option>
				<?php endforeach; ?>
			</select>
			
            <label><b>Shipment Date:</b></label>
            <input type="date" name="ship_date" required style="width:100%;"><br><br>

            <label><b>Address:</b></label>
            <input type="text" name="address" required style="width:100%;"><br><br>

            <label><b>Shipped By:</b></label>
			<select name="shipped_by" required style="width:100%; margin-bottom:15px;">
				<option value="">-- Select Manager --</option>
				<?php foreach($managers as $id => $name): ?>
					<option value="<?= htmlspecialchars($id) ?>">
						<?= htmlspecialchars($name) ?>
					</option>
				<?php endforeach; ?>
			</select>
            <button type="submit">Save</button>
            <button type="button" id="closeModal">Cancel</button>
        </form>
    </div>
</div>


<?php
include($SERVER_ROOT.'/includes/footer.php');
?>


<script>

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

    document.getElementById('closeModal').addEventListener('click', function() {
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

        console.log("Posting shipment form:", ...formData.entries()); // Debug

        fetch('../../neon/requests/add_shipment.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                modal.style.display = 'none';
                shipmentForm.reset();
                alert('Shipment added successfully');
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