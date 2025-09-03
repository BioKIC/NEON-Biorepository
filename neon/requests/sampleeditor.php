<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/neon/requests/list/InquiriesManager.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl='.$CLIENT_ROOT.'/neon/requests/sampleeditor.php');

$action = array_key_exists("action",$_POST)?$_POST["action"]:"";
$request_id = array_key_exists("request_id",$_REQUEST)?$_REQUEST["request_id"]:"";
$id = array_key_exists("id",$_REQUEST)?$_REQUEST["id"]:"";

$inquiryManager = new InquiriesManager();

$isEditor = false;
if($IS_ADMIN) $isEditor = true;
elseif(array_key_exists('SuperAdmin',$USER_RIGHTS) || array_key_exists('SuperAdmin',$USER_RIGHTS)) $isEditor = true;

$status = "";
$errStr = '';

if ($isEditor && isset($_POST['action'])) {

    switch ($_POST['action']) {

        case 'save':
            if ($inquiryManager->editSample($_POST)) {
                $status = 'close';
            } else {
                $errStr = $inquiryManager->getErrorStr();
            }
            break;

        case 'deleteSample':
            $idToDelete = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($idToDelete && $inquiryManager->deleteSample($idToDelete)) {
                $status = 'close';
            } else {
                $errStr = 'Failed to delete sample.';
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
	<title><?php echo $DEFAULT_TITLE; ?> Sample Editor</title>
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

		function validateSampleForm(f){
			if(f.available.value.trim() == "" 
                && f.use_type.value.trim() == ""
                && f.status.value.trim() == ""
                && f.substance_provided.value.trim() == ""){
				alert("Missing required value");
				return false;
			}
			if (f.shipment_id.value.trim() !== "" &&
				!["current", "completed", "loaned, not used"].includes(f.status.value.trim())) {
					alert("Status and shipment assignment match is not logical");
				return false;
			}
						if (f.shipment_id.value.trim() !== "" &&
				!["requested, not found","not funded", "pending fulfillment"].includes(f.status.value.trim())) {
					alert("Status and shipment assignment match is not logical");
				return false;
			}
			$("#editForm").data("changed",false);
			return true;
		}

		function isNumeric(inStr){
		   	var validChars = "0123456789-.";
		   	var isNumber = true;
		   	var charVar;

		   	for(var i = 0; i < inStr.length && isNumber == true; i++){
		   		charVar = inStr.charAt(i);
				if(validChars.indexOf(charVar) == -1){
					isNumber = false;
					break;
		      	}
		   	}
			return isNumber;
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
		$sampleArr = array();
		if($id) $sampleArr = $inquiryManager->getSampleForEditor($id);
		?>
		<fieldset style="width:800px; margin-left:auto;margin-right:auto;">
			<legend><b><?php echo ($id?'Requested Sample Record (#'.$id.')':'New Record'); ?></b></legend>
			<form id="editForm" method="post" action="sampleeditor.php" onsubmit="return validateSampleForm(this)">
					<div class="displayFieldDiv"><b>occid:</b> <?php echo $sampleArr['occid']; ?></div>
                    <div class="displayFieldDiv"><b>sampleID:</b> <?php echo $sampleArr['sampleID']; ?></div>
                    <div class="displayFieldDiv"><b>sampleClass:</b> <?php echo $sampleArr['sampleClass']; ?></div>
					<div class="displayFieldDiv"><b>sampleCode:</b> <?php echo $sampleArr['sampleCode']; ?></div>
				<div class="fieldGroupDiv">
                    <div class="fieldDiv">
						<b>Status:</b>
                        <?php
                        $statValue = $sampleArr['status']
                        ?>
						<select name="status">
							<option value="">-----</option>
							<option value="pending fulfillment" <?php if($statValue=='pending fulfillment') echo 'SELECTED'; ?>>pending fulfillment</option>
							<option value="current" <?php if($statValue=='current') echo 'SELECTED'; ?>>current</option>
                            <option value="completed" <?php if($statValue=='completed') echo 'SELECTED'; ?>>completed</option>
                            <option value="loaned, not used" <?php if($statValue=='loaned, not used') echo 'SELECTED'; ?>>loaned, not used</option>
                            <option value="requested, not found" <?php if($statValue=='requested, not found') echo 'SELECTED'; ?>>requested, not found</option>
                            <option value="not funded" <?php if($statValue=='not funded') echo 'SELECTED'; ?>>not funded</option>
							<?php
							if($statValue && !in_array($statValue,array('pending fulfillment','current','completed','loaned, not used','requests, not found','not funded'))){
								echo '<option value="'.$statValue.'" SELECTED>'.$statValue.'</option>';
							}
							?>
						</select>
					</div>
                    <div class="fieldDiv">
						<b>Type of Use:</b>
                        <?php
                        $useValue = $sampleArr['use_type']
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
						<b>Available:</b>
                        <?php
                        $avValue = $sampleArr['available']
                        ?>
						<select name="available">
							<option value="">-----</option>
							<option value="yes" <?php if($avValue=='yes') echo 'SELECTED'; ?>>yes</option>
							<option value="no" <?php if($avValue=='no') echo 'SELECTED'; ?>>no</option>
							<?php
							if($avValue && $avValue != 'yes' && $avValue != 'no'){
								echo '<option value="'.$avValue.'" SELECTED>'.$avValue.'</option>';
							}
							?>
						</select>
					</div>
                    <div class="fieldDiv">
						<b>Substance Provided:</b>
                        <?php
                        $subValue = $sampleArr['substance_provided']
                        ?>
						<select name="substance_provided">
							<option value="">-----</option>
							<option value="whole sample" <?php if($subValue=='whole sample') echo 'SELECTED'; ?>>whole sample</option>
							<option value="individual(s)" <?php if($subValue=='individual(s)') echo 'SELECTED'; ?>>individual(s) - indicate number in notes</option>
                            <option value="tissue/material sample" <?php if($subValue=='tissue/material sample') echo 'SELECTED'; ?>>tissue/material sample - link material samples & indicate tissue type in notes</option>
                            <option value="subsample/aliquot" <?php if($subValue=='subsample/aliquot') echo 'SELECTED'; ?>>subsample/aliquot - indicate amount in notes</option>
                            <option value="image" <?php if($subValue=='image') echo 'SELECTED'; ?>>image</option>
                            <option value="data" <?php if($subValue=='data') echo 'SELECTED'; ?>>data only</option>
                            <?php
							if($subValue && in_array($subValue,array("whole sample","individual(s)","tissue/material sample","subsample/aliquot","image","data"))){
								echo '<option value="'.$subValue.'" SELECTED>'.$subValue.'</option>';
							}
							?>
						</select>
					</div>
				</div>
                <div style="clear:both;padding-top:6px;float:left;">
				<div class="fieldGroupDiv">
					<div class="fieldDiv">
						<b>Notes:</b> <input name="notes" type="text" value="<?php echo isset($sampleArr['notes'])?$sampleArr['notes']:''; ?>" style="width:500px" />
					</div>
				</div>
                <div style="clear:both;padding-top:6px;float:left;">
                <span>
                    <strong><?php echo 'Shipment'; ?>:</strong> <?php echo '(exit sample editor to add new shipment)';?>
                </span><br />
                <span>
                    <?php $currentShipmentId = isset($sampleArr['shipment_id']) ? (string)$sampleArr['shipment_id'] : ''; ?>
                    <select name="shipment_id" style="width:400px;" aria-label="shipment">
                    <!-- Placeholder shown when no shipment is assigned -->
                    <option value="" <?php echo ($currentShipmentId === '' ? 'selected="selected"' : ''); ?>>
                        -- No Shipment Assigned --
                    </option>
                    <option disabled>----------------------------</option>
                    <?php
                        $shipArr = $inquiryManager->getShipments();
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
			$shipment_id = (isset($sampleArr['shipment_id']) && $sampleArr['shipment_id']?$sampleArr['shipment_id']:'');
			?>
			<fieldset style="width:800px;margin-left:auto;margin-right:auto;">
				<legend><b>Delete From Request<?php echo ' (#'.$id.')'; ?></b></legend>
				<?php
				if($shipment_id){
					echo '<div style="color:red;margin:20px 0px">';
					echo 'Sample can\'t be deleted until it is unlinked from the shipment.';
					echo '</div>';
				}
				else{
					?>
					<form method="post" action="sampleeditor.php" onsubmit="return confirm('Are you sure you want to permanently delete this sample from the request?')">
						<div style="clear:both;margin:15px">
							<input name="id" type="hidden" value="<?php echo $id; ?>" />
                            <button type="submit" name="action" value="deleteSample">Delete Sample</button>
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
			Sample identifier not set or you do not have permissions to view request records
		</div>
		<?php
	}
	?>
</div>
</body>
</html>