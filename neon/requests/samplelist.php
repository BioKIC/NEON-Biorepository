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
$sampleCnt = $inquiryManager->getSampleCountByID($request_id);
$researchers = $inquiryManager->getResearchersByID($request_id);
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
					paging: false,
					scrollCollapse: true,
					fixedHeader: true,
					columnDefs: [{ orderable: false, targets: [0, -1]}],
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
			var url = "sampleeditor.php?id=" + id + "&request_id=" + requestId;
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
				"batchEditorWindow",
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
									Export Samples
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
					<div style="clear:both;padding:10px 0;">
						<div style="float:left;">
							<div style="float:left;">
								<button type="button" onclick="openBatchEditor()">Batch Edit Selected Samples</button>
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
									<form name="sampleListingForm" action="samplelist.php" method="post" onsubmit="return sampleFormVerify(this)">
										<input name="sortabletable" type="hidden" value="<?= $sortableTable ?>">
										<table id="sampletable" class="styledtable">
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
													if($sortableTable){
														if($str) {
															echo '<tr class="sample-row" data-child-value="'.trim($str,'; ').'">';
														} else {
															echo '<tr class="sample-row">';
														}
													}
													echo '<td>';
													echo '<input id="scbox-'.$sampleArr['id'].'" class="'.trim($classStr).'" name="scbox[]" type="checkbox" value="'.$sampleArr['id'].'" />';
													echo '<a href="#" onclick="return openSampleEditor('.$sampleArr['id'].', '.$request_id.')"><img src="../../images/edit.png" style="width:12px" /></a>';
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