<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class InquirySampleLoadManager{

	private $conn;
	private $shipmentPK;
	private $shipmentArr = array();
	private $uploadFileName;
	private $reloadSampleRecs = false;
	private $fieldMap = array();
	private $sourceArr = array();
	private $searchArr = array();
	private $errorStr;

 	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("write");
 	}

 	public function __destruct(){
		if($this->conn) $this->conn->close();
	}


	//Shipment import functions
	public function uploadManifestFile(){
		$status = false;
		//Load file onto server
		$uploadPath = $this->getContentPath();
		if(array_key_exists("uploadfile",$_FILES)){
			$this->initiateUploadFileName($_FILES['uploadfile']['name'],$uploadPath);
			if(!move_uploaded_file($_FILES['uploadfile']['tmp_name'], $uploadPath.$this->uploadFileName)){
				$this->errorStr = 'ERROR uploading file (code '.$_FILES['uploadfile']['error'].'): ';
				if(!is_writable($uploadPath)) $this->errorStr .= 'Target path ('.$uploadPath.') is not writable ';
				return false;
			}
			//If a zip file, unpackage and assume that last or only file is the occurrrence file
			if($this->uploadFileName && substr($this->uploadFileName,-4) == ".zip"){
				$zipFilePath = $uploadPath.$this->uploadFileName;
				$fileName = '';
				$zip = new ZipArchive;
				$res = $zip->open($zipFilePath);
				if($res === TRUE) {
					for($i = 0; $i < $zip->numFiles; $i++) {
						$name = $zip->getNameIndex($i);
						if(substr($name,0,2) != '._'){
							$ext = strtolower(substr(strrchr($name, '.'), 1));
							if($ext == 'csv'){
								$fileName = $name;
								break;
							}
						}
					}
					if($fileName){
						$this->initiateUploadFileName($fileName,$uploadPath);
						$zip->extractTo($uploadPath,$this->uploadFileName);
					}
					else{
						$this->uploadFileName = '';
					}
				}
				else{
					echo 'failed, code:' . $res;
					return false;
				}
				$zip->close();
				unlink($zipFilePath);
			}
		}
		return $status;
	}

	private function initiateUploadFileName($fileName,$uploadPath){
		$fName = str_replace(array(' ','(',')',';','%','$',","),'_',$fileName);
		$fName = substr($fName,0,strrpos($fName,'.'));
		if(strlen($fName) > 35) $fName = substr($fName,35);
		$ext = substr($fileName,strrpos($fileName,'.')+1);
		$cnt = 1;
		$fileName = $fName.'.'.$ext;
		while(file_exists($uploadPath.$fileName)){
			$fileName = $fName.'_'.$cnt.'.'.$ext;
			$cnt++;
		}
		$this->uploadFileName = $fileName;
	}

	public function analyzeUpload(){
		$status = false;
		//Read first line of file to obtain source field names
		if($this->uploadFileName){
			$fullPath = $this->getContentPath().$this->uploadFileName;
			$fh = fopen($fullPath,'rb') or die("Can't open file");
			$this->sourceArr = $this->getHeaderArr($fh);
			$status = true;
			//Continue iterating through file to obtain all shipmentIDs, and then insure shipment doesn't already exist
			$shipmentIdIndex = false;
			foreach($this->sourceArr as $k => $colName){
				if(strtolower(str_replace(array(' ','_'),'',$colName)) == 'shipmentid'){
					$shipmentIdIndex = $k;
					break;
				}
			}
			if(is_numeric($shipmentIdIndex)){
				$shipmentIDArr = array();
				while($recordArr = fgetcsv($fh)){
					$shipmentIDArr[$recordArr[$shipmentIdIndex]] = '';
				}
				if($shipmentIDArr){
					//Check to make sure shipments IDs were not previously entered
					$dupeShipmentExists = false;
					$sql = 'SELECT shipmentPK, shipmentID FROM NeonShipment WHERE shipmentid IN("'.implode('","',array_keys($shipmentIDArr)).'")';
					$rs = $this->conn->query($sql);
					while($r = $rs->fetch_object()){
						$shipmentIDArr[$r->shipmentID] = $r->shipmentPK;
						$dupeShipmentExists = true;
					}
					$rs->free();
					if($dupeShipmentExists){
						$status = false;
						$this->errorStr = '<div>ERROR: shipment already in system</div>';
						foreach($shipmentIDArr as $id => $pk){
							if($pk) $this->errorStr .= '<div style="margin-left:10px"><a href="manifestviewer.php?shipmentPK='.$pk.'" target="_blank">'.$id.'</a></div>';
						}
					}
				}
				else{
					$this->errorStr = 'ERROR: failed to return shipmentID ';
					$status = false;
				}
			}
			else{
				$this->errorStr = 'ERROR: Unable to locate shipmentID column (required). Please make sure the column exists and is named appropriately';
				$status = false;
			}
			fclose($fh);
		}
		return $status;
	}

	public function uploadData(){
		$status = true;
		set_time_limit(1800);
		$shipmentArr = array();
		if($this->uploadFileName){
			echo '<li>Initiating import from: '.$this->uploadFileName.'</li>';
			$fullPath = $this->getContentPath().$this->uploadFileName;
			$fh = fopen($fullPath,'rb') or die("Can't open file");
			$headerArr = $this->getHeaderArr($fh);
			$recCnt = 0;
			//Setup record index array
			$indexMap = array();
			foreach($this->fieldMap as $sourceField => $targetField){
				$indexArr = array_keys($headerArr,$sourceField);
				$index = array_shift($indexArr);
				$indexMap[$targetField][$sourceField] = $index;
			}
			echo '<li>Beginning to load records...</li>';
			ob_flush();
			flush();
			$errCnt = 0;
			while($recordArr = fgetcsv($fh)){
				$recMap = Array();
				foreach($indexMap as $targetField => $indexValueArr){
					foreach($indexValueArr as $sField => $indexValue){
							$recMap[$targetField] = $recordArr[$indexValue];
					}
				}
				$recMap = array_change_key_case($recMap);
				if(!array_key_exists($recMap['shipmentid'],$shipmentArr)) $shipmentArr[$recMap['shipmentid']] = $this->loadShipmentRecord($recMap);
				$this->shipmentPK = $shipmentArr[$recMap['shipmentid']];
				if($this->shipmentPK){
					if($this->addSample($recMap,true)){
						$recCnt++;
						if($recCnt%1000 == 0){
							echo '<li>'.$recCnt.' record loaded</li>';
							ob_flush();
							flush();
						}
					}
					else $errCnt++;
				}
				unset($recMap);
			}
			fclose($fh);
			echo '<li>Complete: '.$recCnt.' records loaded '.($errCnt?'('.$errCnt.' errors)':'').'</li>';
		}
		else{
			$this->outputMsg('<li>File Upload FAILED: unable to locate file</li>');
		}
		return $shipmentArr;
	}

	private function loadShipmentRecord($recArr){
		$shipmentPK = 0;
		$trackingId = '';
		if(isset($recArr['trackingnumber'])){
			$trackingId = trim($recArr['trackingnumber'],' #');
			$trackingId = str_replace(array("\n",','), ';', $trackingId);
			$trackingId = preg_replace('/[^a-zA-Z0-9;]+/', '', $trackingId);
			if($trackingId == 'none') $trackingId = '';
		}
		$sql = 'INSERT INTO NeonShipment(shipmentID, domainID, dateShipped, shippedFrom,senderID, destinationFacility, sentToID, shipmentService, shipmentMethod, trackingNumber, notes, fileName, importUid) '.
			'VALUES("'.$this->cleanInStr($recArr['shipmentid']).'",'.(isset($recArr['domainid'])?'"'.$this->cleanInStr($recArr['domainid']).'"':'NULL').','.
			(isset($recArr['shippedfrom'])?'"'.$this->cleanInStr($recArr['shippedfrom']).'"':'NULL').','.(isset($recArr['senderid'])?'"'.$this->cleanInStr($recArr['senderid']).'"':'NULL').','.
			(isset($recArr['destinationfacility'])?'"'.$this->cleanInStr($recArr['destinationfacility']).'"':'NULL').','.
			(isset($recArr['senttoid'])?'"'.$this->cleanInStr($recArr['senttoid']).'"':'NULL').','.
			(isset($recArr['shipmentservice'])?'"'.$this->cleanInStr($recArr['shipmentservice']).'"':'NULL').','.
			(isset($recArr['shipmentmethod'])?'"'.$this->cleanInStr($recArr['shipmentmethod']).'"':'NULL').','.
			($trackingId?'"'.$trackingId.'"':'NULL').','.
			(isset($recArr['shipmentnotes'])?'"'.$this->cleanInStr($recArr['shipmentnotes']).'"':'NULL').','.
			($this->uploadFileName?'"'.$this->cleanInStr($this->uploadFileName).'"':'NULL').','.
			$GLOBALS['SYMB_UID'].')';
		//echo '<div>'.$sql.'</div>';
		if($this->conn->query($sql)){
			$shipmentPK = $this->conn->insert_id;
			echo '<li>New shipment created (#<a href="manifestviewer.php?shipmentPK='.$shipmentPK.'" target="_blank">'.$recArr['shipmentid'].'</a>)</li>';
		}
		else{
			if($this->conn->errno == 1062){
				$existingFileName = '';
				$sql = 'SELECT shipmentpk, filename FROM NeonShipment WHERE shipmentID = "'.$this->cleanInStr($recArr['shipmentid']).'"';
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$shipmentPK = $r->shipmentpk;
					$existingFileName = $r->filename;
				}
				$rs->free();
				if(!in_array($this->uploadFileName,explode('|',$existingFileName))){
					//Append new filename to existing
					$this->conn->query('UPDATE NeonShipment SET filename = "'.trim($this->cleanInStr($existingFileName.'|'.$this->uploadFileName),' |').'" WHERE shipmentpk = '.$shipmentPK);
				}
				echo '<li><span style="color:orange">NOTICE:</span> Samples mapped to existing shipment: <a href="manifestviewer.php?shipmentPK='.$shipmentPK.'" target="_blank">'.$recArr['shipmentid'].'</a></li>';
			}
			else{
				echo '<li style="margin-left:15px"><span style="color:red">ERROR</span> loading shipment record (errNo: '.$this->conn->errno.'): '.$this->conn->error.'</li>';
				echo '<li style="margin-left:15px">SQL: '.$sql.'</li>';
				return 0;
			}
		}
		return $shipmentPK;
	}

	private function getHeaderArr($fHandler){
		$retArr = array();
		$headerArr = fgetcsv($fHandler);
		foreach($headerArr as $field){
			//$fieldStr = strtolower(trim($field));
			$fieldStr = trim($field);
			//if($fieldStr) $retArr[] = $fieldStr;
			$retArr[] = $fieldStr;
		}
		return $retArr;
	}


	public function addSample($recArr, $verbose = false){
		$status = false;
		$recArr = array_change_key_case($recArr);
		if($this->shipmentPK){
			$sampleID = '';
			if(isset($recArr['sampleid']) && $recArr['sampleid']) $sampleID = $recArr['sampleid'];
			$sampleCode = '';
			if(isset($recArr['samplecode']) && $recArr['samplecode']) $sampleCode = $recArr['samplecode'];
			if($sampleID || $sampleCode){
				$insertRecord = true;
				if($this->reloadSampleRecs){
					if($sampleCode || ($sampleID && $recArr['sampleclass'])){
						$sql = 'SELECT samplepk FROM NeonSample WHERE shipmentpk = '.$this->shipmentPK.' AND (';
						if($sampleID) $sql .= '(sampleid = "'.$sampleID.'" AND sampleclass = "'.$recArr['sampleclass'].'")';
						if($sampleID && $sampleCode) $sql .= ' OR ';
						if($sampleCode) $sql .= 'samplecode = "'.$sampleCode.'"';
						$sql .= ')';
						$rs = $this->conn->query($sql);
						if($r = $rs->fetch_object()){
							$recArr['samplepk'] = $r->samplepk;
							$status = $this->editSample($recArr);
							$insertRecord = false;
							if($verbose){
								if(!$status) echo '<li style="margin-left:15px"><span style="color:orange">NOTICE:</span> '.$this->errorStr.'</li>';
							}
						}
						$rs->free();
					}
				}
				if($insertRecord){
					$duplicateArr = $this->duplicateSampleExists($request_id, $recArr['occid']);
					if(!$duplicateArr){
						$sql = 'INSERT INTO NeonSample(shipmentPK, sampleID, sampleCode, alternativeSampleID, sampleClass, quarantineStatus, namedLocation, collectDate, '.
							'dynamicproperties, symbiotatarget, taxonID, individualCount, filterVolume, domainRemarks, notes) '.
							'VALUES('.$this->shipmentPK.','.
							($sampleID?'"'.$this->cleanInStr($sampleID).'"':'NULL').','.
							($sampleCode?'"'.$this->cleanInStr($sampleCode).'"':'NULL').','.
							(isset($recArr['alternativesampleid']) && $recArr['alternativesampleid']?'"'.$this->cleanInStr($recArr['alternativesampleid']).'"':'NULL').','.
							(isset($recArr['sampleclass']) && $recArr['sampleclass']?'"'.$this->cleanInStr($recArr['sampleclass']).'"':'NULL').','.
							(isset($recArr['quarantinestatus']) && $recArr['quarantinestatus']?'"'.$this->cleanInStr($recArr['quarantinestatus']).'"':'NULL').','.
							(isset($recArr['namedlocation']) && $recArr['namedlocation']?'"'.$this->cleanInStr($recArr['namedlocation']).'"':'NULL').','.
							(isset($recArr['dynamicproperties']) && $recArr['dynamicproperties']?'"'.$this->cleanInStr($recArr['dynamicproperties']).'"':'NULL').','.
							(isset($recArr['symbiotatarget']) && $recArr['symbiotatarget']?'"'.$this->cleanInStr($recArr['symbiotatarget']).'"':'NULL').','.
							(isset($recArr['taxonid']) && $recArr['taxonid']?'"'.$this->cleanInStr($recArr['taxonid']).'"':'NULL').','.
							(isset($recArr['individualcount']) && $recArr['individualcount']?'"'.$this->cleanInStr($recArr['individualcount']).'"':'NULL').','.
							(isset($recArr['filtervolume']) && $recArr['filtervolume']?'"'.$this->cleanInStr($recArr['filtervolume']).'"':'NULL').','.
							(isset($recArr['domainremarks']) && $recArr['domainremarks']?'"'.$this->cleanInStr($recArr['domainremarks']).'"':'NULL').','.
							(isset($recArr['samplenotes']) && $recArr['samplenotes']?'"'.$this->cleanInStr($recArr['samplenotes']).'"':'NULL').')';
						if($this->conn->query($sql)){
							$status = true;
						}
						else{
							if($this->conn->errno == 1062){
								$id = $sampleCode;
								if(!$id) $id = $sampleID;
								$this->errorStr = '<span style="color:red">FAILURE:</span> record already in system with duplicate: <a href="manifestviewer.php?quicksearch='.$id.'" target="_blank">'.$id.'</a>';
								if($verbose) echo '<li>'.$this->errorStr.'</li>';
							}
							else{
								$this->errorStr = '<span style="color:red">ERROR</span> adding sample: '.$this->conn->error;
								if($verbose){
									echo '<li style="margin-left:15px">'.$this->errorStr.'</li>';
								}
							}
							$status = false;
						}
					}
					else{
						$errStr = '';
						foreach($duplicateArr as $dupSamplePK => $idArr){
							$link = 'manifestviewer.php?shipmentPK='.$dupSamplePK.'&sampleFilter=displaySamples&quicksearch=';
							if(isset($idArr['sampleID'])) $errStr .= '<a href="'.$link.$idArr['sampleID'].'#samplePanel" target="_blank">'.$idArr['sampleID'].'</a>, ';
							if(isset($idArr['sampleCode'])) $errStr .= '<a href="'.$link.$idArr['sampleCode'].'#samplePanel" target="_blank">'.$idArr['sampleCode'].'</a>, ';
						}
						$this->errorStr = trim($errStr,', ');
						if($verbose) echo '<li><span style="color:red">ERROR,</span> record already in system with duplicate identifiers: '.$this->errorStr.'</li>';
					}
				}
			}
			else{
				$this->errorStr = '<span style="color:red">ERROR:</span> record skipped due to required field being NULL';
				if($verbose) echo '<li>'.$this->errorStr.'</li>';
			}
		}
		return $status;
	}


	private function getFilteredWhereSql(){
		$sqlWhere = '';
		if(isset($_REQUEST['shipmentID'])){
			if(isset($_REQUEST['shipmentID']) && $_REQUEST['shipmentID']){
				$sqlWhere .= 'AND (s.shipmentID LIKE "'.$this->cleanInStr($_REQUEST['shipmentID']).'%") ';
				$this->searchArr['shipmentID'] = $_REQUEST['shipmentID'];
			}
			if (isset($_REQUEST['sampleID']) && $_REQUEST['sampleID']) {
				$rawInput = $_REQUEST['sampleID'];
				$sampleIDs = preg_split('/[\r\n,]+/', $rawInput, -1, PREG_SPLIT_NO_EMPTY);
			
				$likeConditions = [];
				foreach ($sampleIDs as $id) {
					$id = trim($this->cleanInStr($id));
					$likeConditions[] = "(m.sampleID LIKE '%$id%' OR m.alternativeSampleID LIKE '%$id%')";
				}
			
				if (!empty($likeConditions)) {
					$sqlWhere .= 'AND (' . implode(' OR ', $likeConditions) . ') ';
				}
			
				$this->searchArr['sampleID'] = $rawInput;
			}
			if (isset($_REQUEST['sampleCode']) && $_REQUEST['sampleCode']) {
				$rawInput = $_REQUEST['sampleCode'];
				$sampleCodes = preg_split('/[\r\n,]+/', $rawInput, -1, PREG_SPLIT_NO_EMPTY);
			
				$conditions = [];
				foreach ($sampleCodes as $code) {
					$code = trim($this->cleanInStr($code));
					$conditions[] = "(m.sampleCode = '$code')";
				}
			
				if (!empty($conditions)) {
					$sqlWhere .= 'AND (' . implode(' OR ', $conditions) . ') ';
				}
			
				$this->searchArr['sampleCode'] = $rawInput;
			}
			if(isset($_REQUEST['domainID']) && $_REQUEST['domainID']){
				$sqlWhere .= 'AND (s.domainID = "'.$_REQUEST['domainID'].'") ';
				$this->searchArr['domainID'] = $_REQUEST['domainID'];
			}
			if(isset($_REQUEST['namedLocation']) && $_REQUEST['namedLocation']){
				$sqlWhere .= 'AND ((m.namedLocation LIKE "'.$_REQUEST['namedLocation'].'%") OR (m.sampleID LIKE "'.$_REQUEST['namedLocation'].'%")) ';
				$this->searchArr['namedLocation'] = $_REQUEST['namedLocation'];
			}
			if(isset($_REQUEST['sampleClass']) && $_REQUEST['sampleClass']){
				$sqlWhere .= 'AND (m.sampleClass LIKE "%'.$this->cleanInStr($_REQUEST['sampleClass']).'%") ';
				$this->searchArr['sampleClass'] = $_REQUEST['sampleClass'];
			}
			if(isset($_REQUEST['sampleUuid']) && $_REQUEST['sampleUuid']){
				$sqlWhere .= 'AND (m.sampleUuid LIKE "%'.$this->cleanInStr($_REQUEST['sampleUuid']).'%") ';
				$this->searchArr['sampleUuid'] = $_REQUEST['sampleUuid'];
			}
			if(isset($_REQUEST['taxonID']) && $_REQUEST['taxonID']){
				$sqlWhere .= 'AND (m.taxonID = "'.$_REQUEST['taxonID'].'") ';
				$this->searchArr['taxonID'] = $_REQUEST['taxonID'];
			}
			if(isset($_REQUEST['trackingNumber']) && $_REQUEST['trackingNumber']){
				$trackingId = trim($_REQUEST['trackingNumber'],' #');
				$trackingId = preg_replace('/[^a-zA-Z0-9]+/', '', $trackingId);
				$sqlWhere .= 'AND (s.trackingNumber = "'.$trackingId.'") ';
				$this->searchArr['trackingNumber'] = $_REQUEST['trackingNumber'];
			}
			if(isset($_REQUEST['dynamicProperties']) && $_REQUEST['dynamicProperties']){
				$sqlWhere .= 'AND (m.dynamicProperties LIKE "%'.$this->cleanInStr($_REQUEST['dynamicProperties']).'%") ';
				$this->searchArr['dynamicProperties'] = $_REQUEST['dynamicProperties'];
			}
			if (isset($_REQUEST['occid']) && $_REQUEST['occid']) {
				$rawInput = $_REQUEST['occid'];
				$occids = preg_split('/[\r\n,]+/', $rawInput, -1, PREG_SPLIT_NO_EMPTY);
			
				$conditions = [];
				foreach ($occids as $id) {
					$id = trim($this->cleanInStr($id));
					$conditions[] = "(m.occid = '$id')";
				}
			
				if (!empty($conditions)) {
					$sqlWhere .= 'AND (' . implode(' OR ', $conditions) . ') ';
				}
			
				$this->searchArr['occid'] = $rawInput;
			}
			if(isset($_REQUEST['dateShippedStart']) && $_REQUEST['dateShippedStart']){
				$sqlWhere .= 'AND (s.dateShipped > "'.$_REQUEST['dateShippedStart'].'") ';
				$this->searchArr['dateShippedStart'] = $_REQUEST['dateShippedStart'];
			}
			if(isset($_REQUEST['dateShippedEnd']) && $_REQUEST['dateShippedEnd']){
				$sqlWhere .= 'AND (s.dateShipped < "'.$_REQUEST['dateShippedEnd'].'") ';
				$this->searchArr['dateShippedEnd'] = $_REQUEST['dateShippedEnd'];
			}
			if(isset($_REQUEST['dateCheckinStart']) && $_REQUEST['dateCheckinStart']){
				$sqlWhere .= 'AND (m.checkinTimestamp > "'.$_REQUEST['dateCheckinStart'].'") ';
				$this->searchArr['dateCheckinStart'] = $_REQUEST['dateCheckinStart'];
			}
			if(isset($_REQUEST['dateCheckinEnd']) && $_REQUEST['dateCheckinEnd']){
				$sqlWhere .= 'AND (m.checkinTimestamp < "'.$_REQUEST['dateCheckinEnd'].'") ';
				$this->searchArr['dateCheckinEnd'] = $_REQUEST['dateCheckinEnd'];
			}
			/*
			 if(isset($_REQUEST['senderID']) && $_REQUEST['senderID']){
				 $sqlWhere .= 'AND (s.senderID = "'.$_REQUEST['senderID'].'") ';
				$this->searchArr['senderID'] = $_REQUEST['senderID'];
			 }
			 */
			if(isset($_REQUEST['sessionData']) && $_REQUEST['sessionData']){
				$sqlWhere .= 'AND ((m.sessionID = "'.$this->conn->real_escape_string($_REQUEST['sessionData']).'")) ';
				$this->searchArr['sessionData'] = $_REQUEST['sessionData'];
			}
			if(isset($_REQUEST['checkinUid']) && $_REQUEST['checkinUid']){
				$sqlWhere .= 'AND (s.checkinUid IN ("' . implode('","', $_REQUEST['checkinUid']) . '")) ';
				$this->searchArr['checkinUid'] = $_REQUEST['checkinUid'];
			}
			if (isset($_REQUEST['checkinsampleUid']) && $_REQUEST['checkinsampleUid']) {
				$sqlWhere .= 'AND (m.checkinUid IN ("' . implode('","', $_REQUEST['checkinsampleUid']) . '")) ';
				$this->searchArr['checkinsampleUid'] = $_REQUEST['checkinsampleUid'];
			}
			if(isset($_REQUEST['importedUid']) && $_REQUEST['importedUid']){
				$sqlWhere .= 'AND (s.importUid IN ("' . implode('","', $_REQUEST['importedUid']) .'") OR s.modifiedByUid IN ("' . implode('","', $_REQUEST['importedUid']).'")) ';
				$this->searchArr['importedUid'] = $_REQUEST['importedUid'];
			}
			/*
			 if(isset($_REQUEST['collectDateStart']) && $_REQUEST['collectDateStart']){
				 $sqlWhere .= 'AND (m.collectDate > "'.$_REQUEST['collectDateStart'].'") ';
				 $this->searchArr['collectDateStart'] = $_REQUEST['collectDateStart'];
			 }
			 if(isset($_REQUEST['collectDateEnd']) && $_REQUEST['collectDateEnd']){
				 $sqlWhere .= 'AND (m.collectDate < "'.$_REQUEST['collectDateEnd'].'") ';
 				 $this->searchArr['collectDateEnd'] = $_REQUEST['collectDateEnd'];
			 }
			 */
			if(isset($_REQUEST['sampleCondition']) && $_REQUEST['sampleCondition']){
				$sqlWhere .= 'AND (m.sampleCondition = "'.$_REQUEST['sampleCondition'].'") ';
				$this->searchArr['sampleCondition'] = $_REQUEST['sampleCondition'];
			}
			if(isset($_REQUEST['manifestStatus'])){
				$statusArr = $_REQUEST['manifestStatus'];
				if(in_array('shipCheck', $statusArr, true)){
					$sqlWhere .= 'AND (s.checkinTimestamp IS NOT NULL) ';
				}
				if(in_array('shipNotCheck', $statusArr, true)){
					$sqlWhere .= 'AND (s.checkinTimestamp IS NULL) ';
				}
				if(in_array('receiptNotSubmitted', $statusArr, true)){
					$sqlWhere .= 'AND (s.receiptstatus IS NULL OR s.receiptstatus NOT LIKE "submitted%") ';
				}
				if(in_array('allSamplesChecked', $statusArr, true)){
					$sqlWhere .= 'AND m.shipmentPK IN (SELECT shipmentPK 
									FROM NeonSample
									GROUP BY shipmentPK
									HAVING SUM(CASE WHEN checkinUid IS NOT NULL THEN 1 ELSE 0 END) = COUNT(samplePK))';
				}
				if(in_array('sampleCheck', $statusArr, true)){
					$sqlWhere .= 'AND (m.checkinTimestamp IS NOT NULL) ';
				}
				if(in_array('sampleNotCheck', $statusArr, true)){
					$sqlWhere .= 'AND (m.checkinTimestamp IS NULL) ';
				}
				if(in_array('notReceivedSamples', $statusArr, true)){
					$sqlWhere .= 'AND (m.sampleReceived = 0) ';
				}
				if(in_array('notAcceptedSamples', $statusArr, true)){
					$sqlWhere .= 'AND (m.acceptedForAnalysis = 0) ';
				}
				if(in_array('occurNotHarvested', $statusArr, true)){
					$sqlWhere .= 'AND (m.occid IS NULL) ';
				}
				$this->searchArr['manifestStatus'] = $_REQUEST['manifestStatus'];
			}
			if($sqlWhere) $sqlWhere = 'WHERE '.subStr($sqlWhere, 3);
		}
		elseif($this->shipmentPK){
			$sqlWhere = 'WHERE (s.shipmentPK = '.$this->shipmentPK.') ';
		}
		// echo 'where: '.$sqlWhere; exit;
		return $sqlWhere;
	}

	//Setters and getters
	public function setShipmentPK($id){
		if(is_numeric($id)) $this->shipmentPK = $id;
	}

	public function setQuickSearchTerm($term) {
		$cleanTerm = $this->cleanInStr($term);
	
		// Try match by occid
		$sql = 'SELECT s.shipmentPK 
				FROM NeonShipment s 
				LEFT JOIN NeonSample m ON s.shipmentPK = m.shipmentPK 
				WHERE m.occid = "' . $cleanTerm . '"';
		$rs = $this->conn->query($sql);
		
		if ($r = $rs->fetch_object()) {
			$this->shipmentPK = $r->shipmentPK;
			$rs->free();
			return $this->shipmentPK;
		}
		$rs->free();

				// Try match by catalogNumber
				$sql = 'SELECT s.shipmentPK 
				FROM NeonShipment s 
				LEFT JOIN NeonSample m ON s.shipmentPK = m.shipmentPK 
				LEFT JOIN omoccurrences o ON m.occid = o.occid
				WHERE o.catalogNumber = "' . $cleanTerm . '"';
		$rs = $this->conn->query($sql);
		
		if ($r = $rs->fetch_object()) {
			$this->shipmentPK = $r->shipmentPK;
			$rs->free();
			return $this->shipmentPK;
		}
		$rs->free();
	
		// Try match by sampleCode
		$sql = 'SELECT s.shipmentPK 
				FROM NeonShipment s 
				LEFT JOIN NeonSample m ON s.shipmentPK = m.shipmentPK 
				WHERE m.sampleCode = "' . $cleanTerm . '"';
		$rs = $this->conn->query($sql);
		
		if ($r = $rs->fetch_object()) {
			$this->shipmentPK = $r->shipmentPK;
			$rs->free();
			return $this->shipmentPK;
		}
		$rs->free();
	
		// Try match by sampleID
		$sql = 'SELECT s.shipmentPK 
				FROM NeonShipment s 
				LEFT JOIN NeonSample m ON s.shipmentPK = m.shipmentPK 
				WHERE m.sampleid = "' . $cleanTerm . '"';
		$rs = $this->conn->query($sql);
		
		if ($r = $rs->fetch_object()) {
			$this->shipmentPK = $r->shipmentPK;
		}
		$rs->free();
	
		return $this->shipmentPK;
	}


	public function setUploadFileName($name){
		$this->uploadFileName = $name;
	}

	public function getUploadFileName(){
		return $this->uploadFileName;
	}

	public function setReloadSampleRecs($cond){
		if($cond) $this->reloadSampleRecs = true;
		else $this->reloadSampleRecs = false;
	}

	public function setFieldMap($fieldMap){
		$this->fieldMap = $fieldMap;
	}

	public function getSourceArr(){
		return $this->sourceArr;
	}

	public function getTargetArr(){
		$retArr = array('request_id','occid','status','use_type','substance_provided','available','notes','shipment_id',);
		sort($retArr);
		return $retArr;
	}



	public function getErrorStr(){
		return $this->errorStr;
	}

	//Misc functions
	public function in_iarray($needle, $haystack) {
		return in_array(strtolower($needle), array_map('strtolower', $haystack));
	}

	public function array_key_iexists($key, $array) {
		return array_key_exists(strtolower($key), array_map('strtolower', $array));
	}

	private function cleanOutStr($str){
		if($str) $str = htmlspecialchars($str);
		elseif($str === null) $str = '';
		return $str;
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>