<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/classes/InquiriesManager.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl='.$CLIENT_ROOT.'/neon/requests/materialsampleeditor.php');

$action = array_key_exists("action",$_POST)?$_POST["action"]:"";
$request_id = array_key_exists("request_id",$_REQUEST)?$_REQUEST["request_id"]:"";
$id = array_key_exists("id",$_REQUEST)?$_REQUEST["id"]:"";

$inquiryManager = new InquiriesManager();
$sampleTypes = $inquiryManager->getMaterialSampleTypes();

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif(array_key_exists('SuperAdmin',$USER_RIGHTS) || array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;

$status = "";
$errStr = '';

if ($isEditor && isset($_POST['action'])) {

    switch ($_POST['action']) {

        case 'save':
            if ($inquiryManager->editMaterialSample($_POST)) {
                $status = 'close';
            } else {
                $errStr = $inquiryManager->getErrorStr();
            }
            break;

        case 'deleteMaterialSample':
            $idToDelete = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($idToDelete && $inquiryManager->deleteMaterialSample($idToDelete)) {
                $status = 'close';
            } else {
                $errStr = 'Failed to delete material sample.';
            }
            break;

        default:
            $errStr = 'Unknown action.';
    }
}

?>
<html>


<?php if ($status === 'close'): ?>
<script type="text/javascript">
    alert('Action completed successfully.');
    if (window.opener && !window.opener.closed) {
        window.opener.location.reload();
    }
    window.close();
</script>
<?php endif; ?>

<?php if ($errStr): ?>
<div style="color:red;font-weight:bold;margin:10px;">
    <?php echo htmlspecialchars($errStr); ?>
</div>
<?php endif; ?>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Material Sample Editor</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="../../js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			$("#editForm :input").change(function() {
				$("#editForm").data("changed",true);
				$("#submitButton").prop("disabled",false).css('opacity',1);
			});

			$(window).on('beforeunload', function() {
				if($("#editForm").data("changed")) {
					return "Data changes have not been saved. Are you sure you want to leave?";
				}
		        return;
		    });

			<?php
			if($status == 'close') echo 'closeWindow();';
			?>
		});

		function validateMaterialSampleForm(f){
			if(!f.status.value.trim() || !f.use_type.value.trim() || !f.sampleType.value.trim()){
				alert("All required fields must be filled.");
				return false;
			}

			const status = f.status.value.trim();
			const shipmentAssigned = f.shipment_id.value.trim() !== "";
			const validShipmentStatuses = ["current", "complete"];

			if(!shipmentAssigned && validShipmentStatuses.includes(status)){
				alert("A material sample with this status cannot have a shipment assigned.");
				return false;
			}

			if(shipmentAssigned && !validShipmentStatuses.includes(status)){
				alert("If a shipment is assigned, material sample status must be 'current' or 'complete'");
				return false;
			}

			$("#editForm").data("changed", false);
			return true;
		}

		function closeWindow(){
			window.opener.refreshForm.submit();
			window.close();
		}
	</script>
	<style type="text/css">
		fieldset{ padding:15px }
		.fieldGroupDiv{ clear:both; margin-top:2px; height: 25px; }
		.fieldDiv{ float:left; margin-left: 10px}
		button { cursor:pointer; }
	</style>
</head>
<body>
<div id="popup-innertext">
	<?php
	if($isEditor){
		if($errStr) echo '<div style="color:red;margin:15px;">'.$errStr.'</div>';
		$materialsampleArr = array();
		if($id) $materialsampleArr = $inquiryManager->getMaterialSampleForEditor($id);
		if($id) $reqArr = $inquiryManager->getRequestData($request_id);
		?>
		<script>
    		const reqArr = <?php echo json_encode($reqArr); ?>;
		</script>
		<fieldset style="width:800px; margin-left:auto;margin-right:auto;">
			<legend><b><?php echo ($id?'Requested Material Sample Record (#'.$id.')':'New Record'); ?></b></legend>
			<form id="editForm" method="post" action="materialsampleeditor.php" onsubmit="return validateMaterialSampleForm(this)">
                	<div class="displayFieldDiv"><b>material sample PK:</b> <?php echo $materialsampleArr['matSampleID']; ?></div>
                    <div class="displayFieldDiv"><b>material sample catalogNumber:</b> <?php echo $materialsampleArr['catalogNumber']; ?></div>
					<div class="displayFieldDiv"><b>primary sample occid:</b> <?php echo $materialsampleArr['occid']; ?></div>
                    <div class="displayFieldDiv"><b>sampleID:</b> <?php echo $materialsampleArr['sampleID']; ?></div>
                    <div class="displayFieldDiv"><b>sampleClass:</b> <?php echo $materialsampleArr['sampleClass']; ?></div>
					<div class="displayFieldDiv"><b>sampleCode:</b> <?php echo $materialsampleArr['sampleCode']; ?></div>
				<div class="fieldGroupDiv">
                    <div class="fieldDiv">
						<b>Status:</b>
                        <?php
                        $statValue = $materialsampleArr['status']
                        ?>
						<select name="status">
							<option value="">-----</option>
							<option value="pending fulfillment" <?php if($statValue=='pending fulfillment') echo 'SELECTED'; ?>>pending fulfillment</option>
							<option value="current" <?php if($statValue=='current') echo 'SELECTED'; ?>>current</option>
                            <option value="complete" <?php if($statValue=='complete') echo 'SELECTED'; ?>>complete</option>
                            <option value="pending funding" <?php if($statValue=='pending funding') echo 'SELECTED'; ?>>pending funding </option>
							<?php
							if($statValue && !in_array($statValue,array('pending fulfillment','current','complete','pending funding'))){
								echo '<option value="'.$statValue.'" SELECTED>'.$statValue.'</option>';
							}
							?>
						</select>
					</div>
                    <div class="fieldDiv">
						<b>Type of Use:</b>
                        <?php
                        $useValue = $materialsampleArr['use_type']
                        ?>
						<select name="use_type">
							<option value="">-----</option>
							<option value="non-destructive" <?php if($useValue=='non-destructive') echo 'SELECTED'; ?>>non-destructive</option>
							<option value="invasive" <?php if($useValue=='invasive') echo 'SELECTED'; ?>>invasive</option>
							<option value="consumptive" <?php if($useValue=='consumptive') echo 'SELECTED'; ?>>consumptive</option>
							<option value="destructive" <?php if($useValue=='destructive') echo 'SELECTED'; ?>>destructive</option>                            
                            <?php
							if($useValue && !in_array($useValue,array('non-destructive','invasive','consumptive','destructive'))){
								echo '<option value="'.$useValue.'" SELECTED>'.$useValue.'</option>';
							}
							?>
						</select>
					</div>
                    <div class="fieldDiv">
						<b>Sample Type:</b>
                        <?php
                        $subValue = $materialsampleArr['sampleType']
                        ?>
						<select name="sampleType">
							<option disabled>-- Select option --</option>
							<option disabled>-------------------</option>
							<?php
								foreach($sampleTypes as $type => $label){
								    $selected = ($label == $subValue) ? 'selected' : '';
								    echo '<option value="' . htmlspecialchars($label) . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
											}
							?>
                            
						</select>
					</div>
				<div class="fieldGroupDiv">
					<div class="fieldDiv">
						<b>Notes:</b> <input name="notes" type="text" value="<?php echo isset($materialsampleArr['notes'])?$materialsampleArr['notes']:''; ?>" style="width:500px" />
					</div>
				</div>
                <div style="clear:both;padding-top:6px;float:left;">
                <span>
                    <strong><?php echo 'Shipment'; ?>:</strong> <?php echo '(edit inquiry to add new shipment to request)';?>
                </span><br />
                <span>
                    <?php $currentShipmentId = isset($materialsampleArr['shipment_id']) ? (string)$materialsampleArr['shipment_id'] : ''; ?>
                    <select name="shipment_id" style="width:400px;" aria-label="shipment">
                    <option value="" <?php echo ($currentShipmentId === '' ? 'selected="selected"' : ''); ?>>
                        -- No Shipment Assigned --
                    </option>
                    <option disabled>----------------------------</option>
                    <?php
                        $shipArr = $inquiryManager->getShipmentByID($request_id);
                        foreach ($shipArr as $shipid => $name) {
                            $selected = ($currentShipmentId !== '' && (string)$shipid === $currentShipmentId) ? 'selected="selected"' : '';
                            echo '<option value="'.htmlspecialchars($shipid).'" '.$selected.'>'.htmlspecialchars($name).'</option>';
                        }
                    ?>
                    </select>
                </span>
                </div>
                <div style="clear:both;padding-top:6px;float:left;">
					<?php
					if($id){
						?>
						<input name="id" type="hidden" value="<?php echo $id; ?>" />
						<div><button id="submitButton" type="submit" name="action" value="save">Save Changes</button></div>
						<?php
					}
					?>
				</div>
			</form>
		</fieldset>
		<?php
		if($id){
			$shipment_id = (isset($materialsampleArr['shipment_id']) && $materialsampleArr['shipment_id']?$materialsampleArr['shipment_id']:'');
			?>
			<fieldset style="width:800px;margin-left:auto;margin-right:auto;">
				<legend><b>Delete From Request<?php echo ' (#'.$id.')'; ?></b></legend>
				<?php
				if($shipment_id){
					echo '<div style="color:red;margin:20px 0px">';
					echo 'Material sample can\'t be deleted until it is unlinked from the shipment.';
					echo '</div>';
				}
				else{
					?>
					<form method="post" action="materialsampleeditor.php" onsubmit="return confirm('Are you sure you want to permanently delete this material sample from the request?')">
						<div style="clear:both;margin:15px">
							<input name="id" type="hidden" value="<?php echo $id; ?>" />
                            <button type="submit" name="action" value="deleteMaterialSample">Delete Material Sample</button>
						</div>
					</form>
					<?php
				}
				?>
			</fieldset>
			<?php
		}
	}
	else{
		?>
		<div style='font-weight:bold;margin:30px;'>
			Material sample identifier not set or you do not have permissions to view request records
		</div>
		<?php
	}
	?>
</div>
</body>
</html>