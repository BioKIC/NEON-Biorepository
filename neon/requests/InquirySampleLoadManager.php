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


### Import functions
	public function uploadData(){
		$status = true;
		set_time_limit(1800);
		$shipmentArr = array();



		if($this->uploadFileName){
			echo '<li>Initiating import</li>';

			


			$headerArr = $this->getHeaderArr($fh);
			$recCnt = 0;
			//Setup record index array
			$indexMap = array();
			foreach($this->fieldMap as $sourceField => $targetField){
				$indexArr = array_keys($headerArr,$sourceField);
				$index = array_shift($indexArr);
				$indexMap[$targetField][$sourceField] = $index;
			}
			echo '<li>Beginning to load samples...</li>';
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
				$this->shipmentPK = $shipmentArr[$recMap['shipmentid']];
				if($this->shipmentPK){
					if($this->addSample($recMap,true)){
						$recCnt++;
						if($recCnt%1000 == 0){
							echo '<li>'.$recCnt.' samples loaded</li>';
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



	private function getHeaderArr($fHandler){
		$retArr = array();
		$headerArr = fgetcsv($fHandler);
		foreach($headerArr as $field){
			$fieldStr = trim($field);
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
						$sql = 'INSERT INTO NeonSample(request_id, occid, status, use_type, substance_provided, available, notes, shipment_id '.
							'VALUES('.$this->requestID.','.
							($sampleID?'"'.$this->cleanInStr($sampleID).'"':'NULL').','.
							($sampleCode?'"'.$this->cleanInStr($sampleCode).'"':'NULL').','.
							(isset($recArr['alternativesampleid']) && $recArr['alternativesampleid']?'"'.$this->cleanInStr($recArr['alternativesampleid']).'"':'NULL').','.
							(isset($recArr['sampleclass']) && $recArr['sampleclass']?'"'.$this->cleanInStr($recArr['sampleclass']).'"':'NULL').','.
							(isset($recArr['status']) && $recArr['status']?'"'.$this->cleanInStr($recArr['status']).'"':'NULL').','.
							(isset($recArr['use_type']) && $recArr['use_type']?'"'.$this->cleanInStr($recArr['use_type']).'"':'NULL').','.
							(isset($recArr['substance_provided']) && $recArr['substance_provided']?'"'.$this->cleanInStr($recArr['substance_provided']).'"':'NULL').','.
							(isset($recArr['available']) && $recArr['available']?'"'.$this->cleanInStr($recArr['available']).'"':'NULL').','.
							(isset($recArr['notes']) && $recArr['notes']?'"'.$this->cleanInStr($recArr['notes']).'"':'NULL').')';
							(isset($recArr['shipment_id']) && $recArr['shipment_id']?'"'.$this->cleanInStr($recArr['shipment_id']).'"':'NULL').','.
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
							$link = 'manifestviewer.php?sid='.$dup.'&sampleFilter=displaySamples&quicksearch=';
							if(isset($idArr['sampleID'])) $errStr .= '<a href="'.$link.$idArr['sampleID'].'#samplePanel" target="_blank">'.$idArr['sampleID'].'</a>, ';
							if(isset($idArr['sampleCode'])) $errStr .= '<a href="'.$link.$idArr['sampleCode'].'#samplePanel" target="_blank">'.$idArr['sampleCode'].'</a>, ';
						}
						$this->errorStr = trim($errStr,', ');
						if($verbose) echo '<li><span style="color:red">ERROR,</span> sample record already in system for this inquiry: '.$this->errorStr.'</li>';
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


	//Setters and getters
	public function setShipmentPK($id){
		if(is_numeric($id)) $this->shipmentPK = $id;
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