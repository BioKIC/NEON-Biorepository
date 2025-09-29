<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class InquirySampleLoadManager{

	private $conn;
	private $requestID = null;
	private $reloadSampleRecs = false;
	private $fieldMap = array();       
	private $sourceArr = array();      
	private $errorStr = '';

 	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("write");
 	}

 	public function __destruct(){
		if($this->conn) $this->conn->close();
	}

	private $savedFile = null;

	public function setSavedFile($path){
		if(file_exists($path)){
			$this->savedFile = $path;
		}
	}
	
	
	public function analyzeUpload(){
		if(!isset($_FILES['uploadfile']) || !is_uploaded_file($_FILES['uploadfile']['tmp_name'])){
			$this->errorStr = 'No upload file found';
			return false;
		}

		$fh = fopen($_FILES['uploadfile']['tmp_name'], 'r');
		if(!$fh){
			$this->errorStr = 'Unable to open uploaded file';
			return false;
		}

		$this->sourceArr = array();
		$headerRow = fgetcsv($fh);
		if($headerRow === false){
			fclose($fh);
			$this->errorStr = 'Unable to read header row from CSV';
			return false;
		}

		foreach($headerRow as $col){
			$col = trim($col);
			if($col !== '') $this->sourceArr[] = $col;
		}
		fclose($fh);

		if(empty($this->sourceArr)){
			$this->errorStr = 'No header columns found in CSV';
			return false;
		}

		return true;
	}


	public function uploadData(){
		set_time_limit(1800);

		$filepath = null;
		if($this->savedFile){
			$filepath = $this->savedFile;
		} elseif(isset($_FILES['uploadfile']) && is_uploaded_file($_FILES['uploadfile']['tmp_name'])){
			$filepath = $_FILES['uploadfile']['tmp_name'];
		}

		if(!$filepath || !file_exists($filepath)){
			$this->outputMsg('<li>File Upload FAILED: unable to access uploaded file</li>');
			return array();
		}

		$fh = fopen($filepath,'r');
		if(!$fh){
			$this->outputMsg('<li>File Upload FAILED: unable to open uploaded file</li>');
			return array();
		}

		$this->outputMsg('<li>Initiating import</li>');

		$headerArr = $this->getHeaderArr($fh); 
		$recCnt = 0;
		$errCnt = 0;

		$indexMap = array();
		foreach($this->fieldMap as $sourceField => $targetField){
			$indexArr = array_keys($headerArr, $sourceField);
			if(!empty($indexArr)){
				$index = array_shift($indexArr);
				$indexMap[$targetField][$sourceField] = $index;
			}
		}

		$this->outputMsg('<li>Beginning to load samples...</li>');
		ob_flush(); flush();

		while(($recordArr = fgetcsv($fh)) !== false){
			$recMap = array();
			foreach($indexMap as $targetField => $indexValueArr){
				foreach($indexValueArr as $sField => $indexValue){
					if(isset($recordArr[$indexValue])){
						$recMap[$targetField] = $recordArr[$indexValue];
					}
				}
			}

			$recMap = array_change_key_case($recMap, CASE_LOWER);

			if($this->addSample($recMap, true)){
				$recCnt++;
				if($recCnt % 1000 == 0){
					$this->outputMsg('<li>'.$recCnt.' samples loaded</li>');
					ob_flush(); flush();
				}
			} else {
				$errCnt++;
			}
		}

		fclose($fh);
		$this->outputMsg('<li>Complete: '.$recCnt.' records loaded '.($errCnt?('('.$errCnt.' errors)'):'').'</li>');

		return array();
	}


	private function getHeaderArr($fHandler){
		$retArr = array();
		$headerArr = fgetcsv($fHandler);
		if($headerArr === false) return $retArr;
		foreach($headerArr as $field){
			$fieldStr = trim($field);
			$retArr[] = $fieldStr;
		}
		$this->sourceArr = $retArr;
		return $retArr;
	}


	public function addSample($recArr, $verbose = false){
		$status = false;
		$recArr = array_change_key_case($recArr, CASE_LOWER);

		if(!$this->requestID){
			$this->errorStr = '<span style="color:red">ERROR:</span> request ID not set for load';
			if($verbose) $this->outputMsg('<li>'.$this->errorStr.'</li>');
			return false;
		}

		if(!isset($recArr['occid']) || trim($recArr['occid']) === ''){
			$this->errorStr = '<span style="color:red">ERROR:</span> record skipped due to missing occid';
			if($verbose) $this->outputMsg('<li>'.$this->errorStr.'</li>');
			return false;
		}

		$duplicate = $this->duplicateSampleExists($this->requestID, $recArr['occid']);
		if($duplicate){
			$this->errorStr = '<span style="color:red">ERROR:</span> sample with occid "'.$this->cleanOutStr($recArr['occid']).'" already exists for request '.$this->requestID;
			if($verbose) $this->outputMsg('<li>'.$this->errorStr.'</li>');
			return false;
		}

		$sql = 'INSERT INTO neonsamplerequestlink (
			request_id, occid, status, use_type, substance_provided, available, notes, shipment_id
		) VALUES (
			'.(int)$this->requestID.',
			'.(isset($recArr['occid']) && $recArr['occid'] !== '' ? '"'.$this->cleanInStr($recArr['occid']).'"' : 'NULL').',
			'.(isset($recArr['status']) ? '"'.$this->cleanInStr($recArr['status']).'"' : 'NULL').',
			'.(isset($recArr['use_type']) ? '"'.$this->cleanInStr($recArr['use_type']).'"' : 'NULL').',
			'.(isset($recArr['substance_provided']) ? '"'.$this->cleanInStr($recArr['substance_provided']).'"' : 'NULL').',
			'.(isset($recArr['available']) ? '"'.$this->cleanInStr($recArr['available']).'"' : 'NULL').',
			'.(isset($recArr['notes']) ? '"'.$this->cleanInStr($recArr['notes']).'"' : 'NULL').',
			'.(isset($recArr['shipment_id']) ? '"'.$this->cleanInStr($recArr['shipment_id']).'"' : 'NULL').'
		)';

		if($this->conn->query($sql)){
			$status = true;
		} else {
			if($this->conn->errno == 1062){
				$this->errorStr = '<span style="color:red">FAILURE:</span> duplicate record for occid '.$this->cleanOutStr($recArr['occid']);
				if($verbose) $this->outputMsg('<li>'.$this->errorStr.'</li>');
			} else {
				$this->errorStr = '<span style="color:red">ERROR</span> adding sample: '.$this->cleanOutStr($this->conn->error);
				if($verbose) $this->outputMsg('<li style="margin-left:15px">'.$this->errorStr.'</li>');
			}
			$status = false;
		}

		return $status;
	}


	private function duplicateSampleExists($requestID, $occid){
		$requestID = (int)$requestID;
		$occEsc = $this->conn->real_escape_string(trim($occid));
		$sql = 'SELECT 1 FROM neonsamplerequestlink WHERE request_id = '.$requestID.' AND occid = "'.$occEsc.'" LIMIT 1';
		$rs = $this->conn->query($sql);
		if(!$rs) return false;
		$exists = ($rs->fetch_row() ? true : false);
		$rs->free();
		return $exists;
	}

	/* --- Setters / getters --- */

	public function setRequestID($id){
		if(is_numeric($id)) $this->requestID = (int)$id;
	}

	public function setReloadSampleRecs($cond){
		$this->reloadSampleRecs = (bool)$cond;
	}

	public function setFieldMap($fieldMap){
		$this->fieldMap = is_array($fieldMap) ? $fieldMap : array();
	}

	public function getSourceArr(){
		return $this->sourceArr;
	}

	public function getTargetArr(){
		$retArr = array('request_id','occid','status','use_type','substance_provided','available','notes','shipment_id');
		sort($retArr);
		return $retArr;
	}

	public function getErrorStr(){
		return $this->errorStr;
	}

	public function in_iarray($needle, $haystack) {
		return in_array(strtolower($needle), array_map('strtolower', $haystack));
	}

	public function array_key_iexists($key, $array) {
		return array_key_exists(strtolower($key), array_change_key_case($array, CASE_LOWER));
	}

	private function cleanOutStr($str){
		if($str === null) return '';
		return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}

	private function cleanInStr($str){
		$newStr = trim((string)$str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		return $this->conn->real_escape_string($newStr);
	}

	private function outputMsg($msg){
		echo $msg;
	}
}
?>
