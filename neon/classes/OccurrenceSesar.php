<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class OccurrenceSesar extends Manager {

	private $collid;
	private $collArr = array();
	private $igsnDom;
	private $sesarUser;
	private $sesarPwd;
	private $namespace;
	private $generationMethod = 'sesar';
	private $igsnSeed = false;
	private $registrationMethod;
	private $dynPropArr = false;
	private $fieldMap = array();
	private $productionMode = false;

	public function __construct($type = 'write'){
		parent::__construct(null, $type);
		if(isset($GLOBALS['IGSN_ACTIVATION']) && $GLOBALS['IGSN_ACTIVATION']) $this->productionMode = true;
	}

	public function __destruct(){
		parent::__destruct();
	}

	//Profile management functions
	public function getSesarProfile(){
		$profileArr = array();
		$this->setDynamicPropertiesArr();
		if(isset($this->dynPropArr['sesar'])){
			$profileArr = $this->dynPropArr['sesar'];
			$this->namespace = $profileArr['namespace'];
			$this->generationMethod = $profileArr['generationMethod'];
		}
		return $profileArr;
	}

	private function setDynamicPropertiesArr(){
		if($this->dynPropArr === false && $this->collid){
			$this->dynPropArr = array();
			$sql = 'SELECT dynamicProperties FROM omcollections WHERE collid = '.$this->collid;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				if($r->dynamicProperties) $this->dynPropArr = json_decode($r->dynamicProperties, true);
			}
			$rs->free();
		}
	}

	public function saveProfile(){
		$this->setDynamicPropertiesArr();
		if($this->namespace){
			$this->dynPropArr['sesar']['namespace'] = $this->namespace;
			$this->dynPropArr['sesar']['generationMethod'] = $this->generationMethod;
			$sql = 'UPDATE omcollections SET dynamicProperties = "'.$this->cleanInStr(json_encode($this->dynPropArr)).'" WHERE collid = '.$this->collid;
			if($this->conn->query($sql)){
				return true;
			}
			else{
				$this->errorMessage = 'ERROR saving profile';
				$this->logOrEcho($this->errorMessage);
				return false;
			}
		}
		return false;
	}

	public function deleteProfile(){
		$this->setDynamicPropertiesArr();
		unset($this->dynPropArr['sesar']);
		$sql = 'UPDATE omcollections SET dynamicProperties = "'.$this->cleanInStr(json_encode($this->dynPropArr)).'" WHERE collid = '.$this->collid;
		if($this->conn->query($sql)){
			return true;
		}
		else{
			$this->errorMessage = 'ERROR deleting profile';
			$this->logOrEcho($this->errorMessage);
			return false;
		}
	}

	//Token management
	function getAccessToken($uid) {
		$uid = intval($uid);
		$sql = "SELECT accessTokenSesar FROM users WHERE uid = $uid";
		$result = $this->conn->query($sql);
	
		if ($result && $row = $result->fetch_assoc()) {
			return $row['accessTokenSesar'] ?? '';
		}
	
		$this->errorMessage = 'Database query error: ' . $this->conn->error;
		return '';
	}
	
	function getRefreshToken($uid) {
		$uid = intval($uid);
		$sql = "SELECT refreshTokenSesar FROM users WHERE uid = $uid";
		$result = $this->conn->query($sql);
	
		if ($result && $row = $result->fetch_assoc()) {
			return $row['refreshTokenSesar'] ?? '';
		}
	
		$this->errorMessage = 'Database query error: ' . $this->conn->error;
		return '';
	}
	
	function getDevelopmentAccessToken($uid) {
		$uid = intval($uid);
		$sql = "SELECT developmentAccessTokenSesar FROM users WHERE uid = $uid";
		$result = $this->conn->query($sql);
	
		if ($result && $row = $result->fetch_assoc()) {
			return $row['developmentAccessTokenSesar'] ?? '';
		}
	
		$this->errorMessage = 'Database query error: ' . $this->conn->error;
		return '';
	}
	
	function getDevelopmentRefreshToken($uid) {
		$uid = intval($uid);
		$sql = "SELECT developmentRefreshTokenSesar FROM users WHERE uid = $uid";
		$result = $this->conn->query($sql);
	
		if ($result && $row = $result->fetch_assoc()) {
			return $row['developmentRefreshTokenSesar'] ?? '';
		}
	
		$this->errorMessage = 'Database query error: ' . $this->conn->error;
		return '';
	}
	
	public function isAccessTokenValid($accessToken, $logFH) {
		if (!$accessToken) {
			logMessage("Access token is empty or null", $logFH);
			return false;
		}
	
		$url = $this->getProductionMode()
			? 'https://app.geosamples.org/webservices/credentials_service_v2.php'
			: 'https://app-sandbox.geosamples.org/webservices/credentials_service_v2.php';
	
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Authorization: Bearer ' . $accessToken
		]);
	
		$response = curl_exec($ch);
	
		if ($response === false) {
			$error = curl_error($ch);
			logMessage("cURL error: $error", $logFH);
			curl_close($ch);
			return false;
		}
		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
	
		logMessage("Raw response: " . ($result ?: 'NULL'), $logFH);
	
		if ($httpCode !== 200) {
			logMessage("Access token validation failed", $logFH);
			return false;
		}
	
		logMessage("Access token is valid", $logFH);
		return true;
	}

	
	public function refreshAccessToken($refreshToken, $symbUid = null, $logFH = null) {
		if (!$refreshToken) {
			if ($logFH) logMessage("No refresh token provided.", $logFH);
			return false;
		}
	
		$url = $this->getProductionMode()
			? 'https://app.geosamples.org/webservices/refresh_token.php'
			: 'https://app-sandbox.geosamples.org/webservices/refresh_token.php';
	
		$data = http_build_query(['refresh' => $refreshToken]);
	
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/x-www-form-urlencoded'
		]);
	
		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curlError = curl_error($ch);
		curl_close($ch);
	
		if ($curlError) {
			logMessage("cURL error: $curlError", $logFH);
		}
	
		if ($httpCode === 200 && $result) {
			$response = json_decode($result, true);
			if (isset($response['access']) && isset($response['refresh'])) {
				$newAccessToken = $response['access'];
				$newRefreshToken = $response['refresh'];
	
				if ($symbUid) {
					$this->saveTokens($symbUid, $newAccessToken, $newRefreshToken);
				}
	
				if ($logFH) logMessage("Access token refresh successful.", $logFH);
				return $newAccessToken;
			} else {
				if ($logFH) logMessage("Invalid response structure: " . json_encode($response), $logFH);
			}
		} else {
			if ($logFH) logMessage("Token refresh failed. HTTP status: $httpCode", $logFH);
		}
	
		return false;
	}


	public function saveTokens($uid, $accessToken, $refreshToken) {
		if (!$uid || !$accessToken || !$refreshToken) return false;
	
		include_once($GLOBALS['SERVER_ROOT'].'/config/dbconnection.php');
		$conn = MySQLiConnectionFactory::getCon("write");
	
		if (!$this->getProductionMode()) {
			$accessField = 'developmentaccessTokenSesar';
			$refreshField = 'developmentrefreshTokenSesar';
		} else {
			$accessField = 'accessTokenSesar';
			$refreshField = 'refreshTokenSesar';
		}
	
		$sql = "UPDATE users SET {$accessField} = ?, {$refreshField} = ? WHERE uid = ?";
		$stmt = $conn->prepare($sql);
		if (!$stmt) return false;
	
		$stmt->bind_param("ssi", $accessToken, $refreshToken, $uid);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}
	
	//Processing functions
	public function batchProcessIdentifiers($processingCount){
		$status = false;
		$this->setVerboseMode(3);
		$logPath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1)=='/'?'':'/').'content/logs/igsn/IGSN_'.date('Y-m-d').'.log';
		$this->setLogFH($logPath);
		$this->logOrEcho('Starting batch IGSN processing for collection: ' . $this->collArr['collectionName'] . ' (' . $this->collid . ') at ' . date('Y-m-d H:i:s'));
		$baseTenID = '';
		if(!$this->igsnSeed){
			$this->errorMessage = 'FATAL ERROR batch assigning IDs: IGSN seed not set';
			$this->logOrEcho($this->errorMessage);
			return false;
		}
		$baseTenID = base_convert($this->igsnSeed,36,10);
		if(!$this->validateUser()){
			$this->errorMessage = 'SESAR tokens failed to validate';
			$this->logOrEcho($this->errorMessage);
			return false;
		}

		//Batch assign GUIDs
		$this->logOrEcho('Generating IGSN identifiers');
		$this->setFieldMap();
		$increment = 1;
		$sql = 'SELECT o.occid';
		foreach($this->fieldMap as $symbField => $mapArr){
			if(isset($mapArr['sql'])) $sql .= ','.$mapArr['sql'];
			else $sql .= ',o.'.$symbField;
		}
		$sql .= ' '.$this->getSqlBase();
		if($processingCount) $sql .= 'LIMIT '.$processingCount;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_assoc()){
			if(!$this->igsnDom) $this->initiateDom();
			$igsn = '';
			$igsn = base_convert($baseTenID,10,36);
			$igsn = str_pad($igsn, (9-strlen($this->namespace)), '0', STR_PAD_LEFT);
			$igsn = strtoupper($igsn);
			$this->occidBatch[] = $r['occid'];
			//$igsn = $this->namespace.$igsn;
			$baseTenID++;
			//Set Symbiota record values
			$this->fieldMap['occid']['value'] = $r['occid'];
			foreach($this->fieldMap as $symbField => $fieldArr){
				$this->fieldMap[$symbField]['value'] = $r[$symbField];
			}
			$this->cleanFieldValues();
			$this->otherNames = [];
			
			$sql2 = "SELECT identifiervalue FROM omoccuridentifiers WHERE occid = " . $r['occid'];
			$rs2 = $this->conn->query($sql2);
			while ($r2 = $rs2->fetch_assoc()) {
				$this->otherNames[] = $r2['identifiervalue'];
			}

			if(!$this->igsnExists($igsn)) $this->setSampleXmlNode($igsn, "new");
			//$this->logOrEcho('#'.$increment.': IGSN created for <a href="../editor/occurrenceeditor.php?occid='.$this->fieldMap['occid']['value'].'" target="_blank">'.$this->fieldMap['catalogNumber']['value'].'</a>',1);
			if($this->registerIdentifiersViaApi()){
				//this link won't work when called by igsnhandler.php, only igsnmapper.php
				$this->logOrEcho('#'.$increment.': IGSN registered: <a href="../../../collections/editor/occurrenceeditor.php?occid='.$r['occid'].'" target="_blank">'.$igsn.'</a>',1);
			}
			$this->igsnDom = null;
			$status = true;
			$increment++;
		}
		$rs->free();

		$this->logOrEcho('Finished ('.date('Y-m-d H:i:s').')');
		return $status;
	}

	public function batchUpdateIdentifiers($processingCount, $parallelCount=1){
		$status = false;
		$this->setVerboseMode(3);
		$logPath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1)=='/'?'':'/').'content/logs/igsn/Update_'.date('Y-m-d').'.log';
		$this->setLogFH($logPath);
		$this->logOrEcho('Starting batch sample update for collection: ' . $this->collArr['collectionName'] . ' (' . $this->collid . ') at ' . date('Y-m-d H:i:s'));
		if(!$this->validateUser()){
			$this->errorMessage = 'SESAR tokens failed to validate';
			$this->logOrEcho($this->errorMessage);
			return false;
		}

		//Batch update GUIDs
		$this->logOrEcho('Updating Sample Metadata');
		$this->setFieldMap();
		$increment = 1;
		$sql = 'SELECT o.occid, o.occurrenceID';
		foreach($this->fieldMap as $symbField => $mapArr){
			if(isset($mapArr['sql'])) $sql .= ','.$mapArr['sql'];
			else $sql .= ',o.'.$symbField;
		}
		$sql .= ' '.$this->getSqlBaseNeedsUpdate();
		if($processingCount) $sql .= 'LIMIT '.$processingCount;
		
		$rs = $this->conn->query($sql);
		$batchCounter = 0;
		$increment = 1;
		
		
		while ($r = $rs->fetch_assoc()) {
			if ($batchCounter === 0) {
				$this->initiateDom();  // Start a new XML doc for the batch
				$this->occidBatch = [];
			}
			$igsn = $r['occurrenceID'];
			$this->occidBatch[] = $r['occid'];
		
			// Set Symbiota record values
			$this->fieldMap['occid']['value'] = $r['occid'];
			foreach ($this->fieldMap as $symbField => $fieldArr) {
				$this->fieldMap[$symbField]['value'] = $r[$symbField];
			}
			$this->cleanFieldValues();
		
			// Load other identifiers
			$this->otherNames = [];
			$sql2 = "SELECT identifiervalue FROM omoccuridentifiers WHERE occid = " . $r['occid'];
			$rs2 = $this->conn->query($sql2);
			while ($r2 = $rs2->fetch_assoc()) {
				$this->otherNames[] = $r2['identifiervalue'];
			}
		
			// Add the sample node to the current DOM
			$this->setSampleXmlNode($igsn, "update");
			$batchCounter++;
			$increment++;
		
			// If we've reached the parallelCount, send the batch
			if ($batchCounter >= $parallelCount) {
				if ($this->updateMetadataViaApi()) {
					$urlPrefix = $this->productionMode ? 'https://app.geosamples.org' : 'https://app-sandbox.geosamples.org';
					$this->logOrEcho('#' . ($increment - $batchCounter) . '–' . ($increment - 1) . ': IGSN batch updated (' . $batchCounter . ' samples)', 1);
				}
				$this->igsnDom = null;
				$batchCounter = 0;
			}
		}
		
		// If any remaining samples after the last batch
		if ($batchCounter > 0) {
			if ($this->updateMetadataViaApi()) {
				$this->logOrEcho('#' . ($increment - $batchCounter) . '–' . ($increment - 1) . ': IGSN final batch updated (' . $batchCounter . ' samples)', 1);
			}
			$this->igsnDom = null;
		}
		
		$rs->free();


		$this->logOrEcho('Finished ('.date('Y-m-d H:i:s').')');
		return $status;
	}	
	
	private function setFieldMap(){
		$this->fieldMap['basisOfRecord']['sesar'] = 'collection_method_descr';
		$this->fieldMap['catalogNumber']['sesar'] = 'name';
		$this->fieldMap['catalogNumber']['sql'] = 'CONCAT_WS(" ",IFNULL(o.catalogNumber, o.otherCatalogNumbers),"[",o.occid,"]") AS catalogNumber';
		$this->fieldMap['sciname']['sesar'] = 'field_name';
		$this->fieldMap['sciname']['sql'] = 'CONCAT_WS(" ",o.sciname, o.scientificNameAuthorship) AS sciname';
		$this->fieldMap['recordedBy']['sesar'] = 'collector';
		$this->fieldMap['eventDate']['sesar'] = 'collection_start_date';
		$this->fieldMap['verbatimAttributes']['sesar'] = 'description';
		$this->fieldMap['country']['sesar'] = 'country';
		$this->fieldMap['stateProvince']['sesar'] = 'province';
		$this->fieldMap['county']['sesar'] = 'county';
		$this->fieldMap['locality']['sesar'] = 'locality';
		$this->fieldMap['decimalLatitude']['sesar'] = 'latitude';
		$this->fieldMap['decimalLatitude']['sql'] = 'ROUND(o.decimalLatitude,6) AS decimalLatitude';
		$this->fieldMap['decimalLongitude']['sesar'] = 'longitude';
		$this->fieldMap['decimalLongitude']['sql'] = 'ROUND(o.decimalLongitude,6) AS decimalLongitude';
		$this->fieldMap['minimumElevationInMeters']['sesar'] = 'elevation';
		//$this->fieldMap['parentOccurrenceID']['sesar'] = 'parent_igsn';
		//$this->fieldMap['parentOccurrenceID']['sql'] = ' AS parentOccurrenceID';
	}

	// SESAR web service calls (http://www.geosamples.org/interop)
	// End point: https://app.geosamples.org/webservices/
	// Test end point: https://app-sandbox.geosamples.org/webservices/
	public function validateUser() {
		global $SYMB_UID;
		
		$userCodeArr = array();
		$baseUrl = 'https://app.geosamples.org/webservices/credentials_service_v2.php';
		$accessToken = $this->getAccessToken($SYMB_UID);
		if (!$this->productionMode) {
			$baseUrl = 'https://app-sandbox.geosamples.org/webservices/credentials_service_v2.php';
			$accessToken = $this->getDevelopmentAccessToken($SYMB_UID);
		}

		if (!$accessToken) {
			$this->errorMessage = 'Fatal Error validating user: Access token not set';
			return false;
		}
	
		$headers = [
			'Authorization: Bearer ' . $accessToken
		];
		
		$resArr = $this->getSesarApiData($baseUrl, 'get', null, $headers);
	
		if (isset($resArr['retStr']) && $resArr['retStr']) {
			$dom = new DOMDocument('1.0','UTF-8');
			if ($dom->loadXML($resArr['retStr'])) {
				$validElemList = $dom->getElementsByTagName('valid');
				if ($validElemList[0]->nodeValue == 'yes') {
					$userCodeList = $dom->getElementsByTagName('user_code');
					foreach ($userCodeList as $UserCodeElem) {
						$userCodeArr[] = $UserCodeElem->nodeValue;
					}
				} else {
					$errCodeList = $dom->getElementsByTagName('error');
					$this->logOrEcho('Fatal Error validating user: ' . $errCodeList[0]->nodeValue);
					$userCodeArr = false;
				}
			} else {
				$this->logOrEcho('FATAL ERROR parsing response XML (validateUser): ' . htmlentities($resArr['retStr']));
				$userCodeArr = false;
			}
		} else {
			$this->logOrEcho($this->errorMessage);
			$userCodeArr = false;
		}
	
		return $userCodeArr;
	}

	private function registerIdentifiersViaApi($retryCount = 0) {
		$status = false;
	
		global $SYMB_UID;
		$baseUrl = 'https://app.geosamples.org/webservices/upload.php';
		$accessToken = $this->getAccessToken($SYMB_UID);
		if (!$this->productionMode) {
			$baseUrl = 'https://app-sandbox.geosamples.org/webservices/upload.php';
			$accessToken = $this->getDevelopmentAccessToken($SYMB_UID);
		}
	
		
		if (!$accessToken) {
			$this->errorMessage = 'Fatal Error submitting to SESAR: Access token not found';
			return false;
		}
		$xmldata = $this->igsnDom->saveXML();
		$postData = http_build_query([
			'content' => $this->igsnDom->saveXML()
		]);
	
		$headers = [
			'Content-Type: application/x-www-form-urlencoded',
			'Authorization: Bearer ' . $accessToken
		];

		$resArr = $this->getSesarApiData($baseUrl, 'post', $postData, $headers);
		if ($retryCount >= 3) {
			$this->logOrEcho('Retry limit reached. Not retrying.');
			$status = false;
		} else {
			if (isset($resArr['retStr']) && $resArr['retStr']) {
				$status = $this->processRegistrationResponse($resArr['retStr'], $retryCount);
				if ($status) {
					$status = $this->updateSqlSesarDate();
				}
			}
		}
		return $status;
	}

	private function updateMetadataViaApi($retryCount = 0) {
		$status = false;
	
		global $SYMB_UID;
		$baseUrl = 'https://app.geosamples.org/webservices/update.php';
		$accessToken = $this->getAccessToken($SYMB_UID);
		if (!$this->productionMode) {
			$baseUrl = 'https://app-sandbox.geosamples.org/webservices/update.php';
			$accessToken = $this->getDevelopmentAccessToken($SYMB_UID);
		}
	
		
		if (!$accessToken) {
			$this->errorMessage = 'Fatal Error submitting to SESAR: Access token not found';
			return false;
		}
		$xmldata = $this->igsnDom->saveXML();
		$postData = http_build_query([
			'content' => $this->igsnDom->saveXML()
		]);
	
		$headers = [
			'Content-Type: application/x-www-form-urlencoded',
			'Authorization: Bearer ' . $accessToken
		];

		$resArr = $this->getSesarApiData($baseUrl, 'post', $postData, $headers);
		if ($retryCount >= 3) {
			$this->logOrEcho('Retry limit reached. Not retrying.');
			$status = false;
		} else if (isset($resArr['retStr']) && $resArr['retStr']) {
				$status = $this->processRegistrationResponse($resArr['retStr'], $retryCount);
				if ($status) {
					$status = $this->updateSqlSesarDate();
				}
		}
		return $status;
	}

	private function processRegistrationResponse($responseXML, $retryCount = 0){
		$status = true;
		$dom = new DOMDocument('1.0','UTF-8');
		if($dom->loadXML($responseXML)){
			$rootElem = $dom->documentElement;
			$resultNodeList = $rootElem->childNodes;
	
			foreach($resultNodeList as $resultNode){
				if(isset($resultNode->nodeName)){
					if($resultNode->nodeName == 'valid'){
						if($resultNode->nodeValue == 'no'){
							$errCodeList = $rootElem->getElementsByTagName('error');
							$this->errorMessage = 'ERROR registering IGSN ('.$resultNode->getAttribute('code').'): '.$errCodeList[0]->nodeValue;
						} else {
							$this->errorMessage = 'ERROR registering IGSN: unknown';
						}
						$this->logOrEcho('FAILED processing: '.$this->errorMessage,1);
						$status = false;
						break;
					}
					elseif($resultNode->nodeName == 'sample'){
						$sampleArr = array();
						$childNodeList = $resultNode->childNodes;
	
						foreach($childNodeList as $childNode){
							if($childNode->nodeName == 'valid'){
								$sampleArr['valid'] = $childNode->nodeValue.': '.$childNode->getAttribute('code');
							} else {
								$sampleArr[$childNode->nodeName] = $childNode->nodeValue;
							}
						}
	
						if(isset($sampleArr['valid']) && strpos($sampleArr['valid'], 'no') !== false){
							$status = false;
							$occid = 0;
							$igsn = '';
							$msgStr = 'valid = '.$sampleArr['valid'];

							if(isset($sampleArr['name']) && $sampleArr['name']){
								$msgStr .= '; ID = '.$sampleArr['name'];
								$occid = trim($sampleArr['name'], '[] ');
							}
							if(isset($sampleArr['error']) && $sampleArr['error']){
								$msgStr .= '; error = '.$sampleArr['error'];
								if(preg_match('/(NEON[A-Z0-9]{5})/', $sampleArr['error'], $m)){
									$igsn = $m[1];
								}
							}
							if(!$status) $this->logOrEcho('FAILED: '.$msgStr, 1);
						}
						elseif(isset($sampleArr['igsn']) && $sampleArr['igsn']){
							$occid = 0;
							$dbStatus = false;
							if(preg_match('/\[\s*(\d+)\s*\]\s*$/', $sampleArr['name'], $m1)){
								$occid = $m1[1];
								if(preg_match('/(NEON[A-Z0-9]{5})/', $sampleArr['igsn'], $m2)){
									$igsn = $m2[1];
									$dbStatus = $this->updateOccurrenceID($igsn, $occid);
								}
							} else {
								$this->errorMessage = 'WARNING: unable to extract occid to add igsn ('.$sampleArr['igsn'].')';
							}
							if(!$dbStatus){
								$status = false;
								$this->logOrEcho($this->errorMessage, 2);
							}
						}
					}
				}
			}
		} else {
			$this->logOrEcho('ERROR parsing response XML (processRegistrationResponse): '.htmlentities($responseXML));
			// Check for "too many requests"
			if (stripos($responseXML, 'too many requests') !== false) {
				if ($retryCount >= 3) {
					$this->logOrEcho('Retry limit reached. Not retrying.');
					return false;
				}
				$this->logOrEcho('Too many requests detected. Waiting 30 seconds...', 1);
				sleep(30);
	
				// Retry the API request with incremented retryCount
				$status = $this->registerIdentifiersViaApi($retryCount + 1);
			}
			if(preg_match('/\[\s(\d+)\s\]\] was saved successfully with IGSN \[(NEON[A-Z0-9]{5})\]/', $responseXML, $m)){
				$dbStatus = $this->updateOccurrenceID($m[2], $m[1]);
				$this->logOrEcho('Error resolved: success parsing occid ('.$m[1].') and IGSN ('.$m[2].') from response and appended to database',1);
			} else {
				$status = false;
			}
		}
		return $status;
	}
	
	private function updateSqlSesarDate() {
		$status = true;
		$occidList = array_map('intval', $this->occidBatch);
		$occidStr = implode(',', $occidList);
	
		$sql = "UPDATE NeonSample SET sampleLastUpdatedSESAR = NOW() WHERE occid IN ($occidStr)";
	
		if (!$this->conn->query($sql)) {
			$this->errorMessage = 'ERROR updating sampleLastUpdatedSESAR: ' . $this->conn->error;
			$status = false;
		}
	
		return $status;
	}


	private function initiateDom(){
		$this->igsnDom = new DOMDocument('1.0','UTF-8');

		//Add root element
		$rootElem = $this->igsnDom->createElement('samples');
		$rootElem->setAttribute('xmlns','http://app.geosamples.org');
		$rootElem->setAttribute('xmlns:xsi','http://www.w3.org/2001/XMLSchema-instance');
		$rootElem->setAttribute('xsi:schemaLocation','http://app.geosamples.org/4.0/sample.xsd');
		$this->igsnDom->appendChild($rootElem);
	}

	private function setSampleXmlNode($igsn, $newOrUpdate){
		$sampleElem = $this->igsnDom->createElement('sample');
		
		if ($newOrUpdate === 'new') {
			$this->addSampleElem($this->igsnDom, $sampleElem, 'sesar_code', 'NEO'); // Required for new only
		} else if ($newOrUpdate === 'update') {
			$this->addSampleElem($this->igsnDom, $sampleElem, 'igsn', $igsn); // Required for update only
		}
		$this->addSampleElem($this->igsnDom, $sampleElem, 'sample_type', 'Individual Sample');		//Required
		$this->addSampleElem($this->igsnDom, $sampleElem, 'sample_subtype', 'Specimen');		//Required
		$this->addSampleElem($this->igsnDom, $sampleElem, 'material', 'Biology');		//Required
		if ($newOrUpdate === 'new') {
			$igsnElem = $this->igsnDom->createElement('igsn');		//If blank, SESAR will generate new IGSN
			$igsnElem->appendChild($this->igsnDom->createTextNode($igsn));
			$sampleElem->appendChild($igsnElem);
		}

		$classificationElem = $this->igsnDom->createElement('classification');
		$biologyElem = $this->igsnDom->createElement('Biology');
		$macrobiologyElem = $this->igsnDom->createElement('Macrobiology');
		//there is no vocabulary for MacrobiologyType
		//$this->addSampleElem($this->igsnDom, $macrobiologyElem, 'MacrobiologyType', 'Coral');
		$biologyElem->appendChild($macrobiologyElem);
		$classificationElem->appendChild($biologyElem);
		$sampleElem->appendChild($classificationElem);

		$this->addSampleElem($this->igsnDom, $sampleElem, 'collection_method', 'Manual');
		if(isset($this->fieldMap['eventDate']) && $this->fieldMap['eventDate']['value']) $this->addSampleElem($this->igsnDom, $sampleElem, 'collection_date_precision', 'day');

		foreach($this->fieldMap as $symbArr){
			if(isset($symbArr['sesar'])) $this->addSampleElem($this->igsnDom, $sampleElem, $symbArr['sesar'], $symbArr['value']);
		}

		if(isset($this->fieldMap['minimumElevationInMeters']['value']) && $this->fieldMap['minimumElevationInMeters']['value'] !== '') $this->addSampleElem($this->igsnDom, $sampleElem, 'elevation_unit', 'meters');
		$this->addSampleElem($this->igsnDom, $sampleElem, 'current_archive', $this->collArr['collectionName']);
		$this->addSampleElem($this->igsnDom, $sampleElem, 'current_archive_contact', 'NEON Biorepository (biorepo@asu.edu)');
		
		if (!empty($this->otherNames)) {
			$sampleOtherNamesElem = $this->igsnDom->createElement('sample_other_names');
			foreach ($this->otherNames as $name) {
				$nameElem = $this->igsnDom->createElement('sample_other_name', htmlspecialchars($name));
				$sampleOtherNamesElem->appendChild($nameElem);
			}
			$sampleElem->appendChild($sampleOtherNamesElem);
		}
		
		//I'm not going to deal with updating urls since you need to specify the old url to update and we don't really have a way of (nicely) getting that information. The hope is that the occid will never change. 
		if ($newOrUpdate === 'new') {
			$baseUrl = $this->getDomain().$GLOBALS['CLIENT_ROOT'].(substr($GLOBALS['CLIENT_ROOT'],-1)=='/'?'':'/');
			$url = $baseUrl.'collections/individual/index.php?occid='.$this->fieldMap['occid']['value'];
			$externalUrlsElem = $this->igsnDom->createElement('external_urls');
			$externalUrlElem = $this->igsnDom->createElement('external_url');
			$urlElem = $this->igsnDom->createElement('url');
			$urlElem->appendChild($this->igsnDom->createTextNode($url));
			$externalUrlElem->appendChild($urlElem);
			$descriptionElem = $this->igsnDom->createElement('description');
			$descriptionElem->appendChild($this->igsnDom->createTextNode('Source Reference URL'));
			$externalUrlElem->appendChild($descriptionElem);
			$urlTypeElem = $this->igsnDom->createElement('url_type');
			$urlTypeElem->appendChild($this->igsnDom->createTextNode('regular URL'));
			$externalUrlElem->appendChild($urlTypeElem);
			$externalUrlsElem->appendChild($externalUrlElem);
			$sampleElem->appendChild($externalUrlsElem);
		}
		
		$rootElem = $this->igsnDom->documentElement;
		$rootElem->appendChild($sampleElem);
	}

	private function addSampleElem(&$dom, &$sampleElem, $elemName, $elemValue){
		if($elemValue){
			$newElem = $dom->createElement($elemName);
			$newElem->appendChild($dom->createTextNode($elemValue));
			$sampleElem->appendChild($newElem);
		}
	}

	private function updateOccurrenceID($igsn, $occid){
		$status = true;
		if(strlen($igsn) == 9){
			$sql = 'UPDATE omoccurrences SET occurrenceID = '.($igsn=='NULL'?'NULL':'"'.$igsn.'"').' WHERE occurrenceID IS NULL AND occid = '.$occid;
			if(!$this->conn->query($sql)){
				$this->errorMessage = 'ERROR adding IGSN to occurrence table: '.$this->conn->error;
				//$this->logOrEcho('ERROR adding IGSN to occurrence table: '.$this->conn->error,2);
				$status = false;
			}
		}
		else{
			$this->errorMessage = 'FATAL ERROR adding IGSN to occurrence table: IGSN ('.$igsn.') not 9 digits';
			//$this->logOrEcho('ERROR adding IGSN to occurrence table: IGSN ('.$igsn.') not 9 digits',2);
			exit;
		}
		return $status;
	}

	private function igsnExists($igsn){
		$status = false;
		if($this->namespace){
			$sql = 'SELECT occurrenceID FROM omoccurrences WHERE (occurrenceid LIKE "'.$this->namespace.'%") AND (occurrenceID = "'.$igsn.'")';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$status = true;
			}
			$rs->free();
		}
		return $status;
	}

	//Record field cleaning functions
	private function cleanFieldValues(){
		if(isset($this->fieldMap['country']['value']) && $this->fieldMap['country']['value']){
			$this->fieldMap['country']['value'] = $this->cleanCountryStr($this->fieldMap['country']['value']);
		}
		if(isset($this->fieldMap['eventDate']['value'])){
			if($this->fieldMap['eventDate']['value']){
				//echo 'date: '.$this->fieldMap['eventDate']['value'].' - ';
				$y = substr($this->fieldMap['eventDate']['value'],0,4);
				if($y < 1900) unset($this->fieldMap['eventDate']);
				if(isset($this->fieldMap['eventDate']['value']) && $this->fieldMap['eventDate']['value']) $this->fieldMap['eventDate']['value'] .= 'T00:00:00';
			}
		}
	}

	private function cleanCountryStr($countryStr){
		if(!$countryStr) return $countryStr;
		$countryStr = $this->mbStrtr($countryStr,'áéÉ','aeE');
		$testStr = strtolower($countryStr);
		$synonymArr = array('united states of america'=>'United States','usa'=>'United States','u.s.a.'=>'united states','us'=>'United States');
		if(array_key_exists($testStr, $synonymArr)) $countryStr = $synonymArr[$testStr];
		$goodCountryArr = array('Afghanistan','Albania','Algeria','American Samoa','Andorra','Angola','Anguilla','Antarctica','Antigua And Barbuda','Argentina','Armenia','Aruba',
			'Australia','Austria','Azerbaijan','Bahamas','Bahrain','Bangladesh','Barbados','Belarus','Belgium','Belize','Benin','Bermuda','Bhutan','Bolivia','Bosnia And Herzegovina',
			'Botswana','Bouvet Island','Brazil','British Indian Ocean Territory','Brunei Darussalam','Bulgaria','Burkina Faso','Burundi','Cambodia','Cameroon','Canada','Cape Verde',
			'Cayman Islands','Central African Republic','Chad','Chile','China','Christmas Island','Cocos (keeling) Islands','Colombia','Comoros','Congo',
			'Congo, The Democratic Republic Of The','Cook Islands','Costa Rica',"Cote D'ivoire",'Croatia','Cuba','Cyprus','Czech Republic','Denmark','Djibouti','Dominica',
			'Dominican Republic','East Timor','Ecuador','Egypt','El Salvador','Equatorial Guinea','Eritrea','Estonia','Ethiopia','Falkland Islands (malvinas)','Faroe Islands',
			'Fiji','Finland','France','French Guiana','French Polynesia','French Southern Territories','Gabon','Gambia','Georgia','Germany','Ghana','Gibraltar','Greece','Greenland',
			'Grenada','Guadeloupe','Guam','Guatemala','Guinea','Guinea-bissau','Guyana','Haiti','Heard Island And Mcdonald Islands','Holy See (vatican City State)','Honduras',
			'Hong Kong','Hungary','Iceland','India','Indonesia','Iran','Iraq','Ireland','Israel','Italy','Jamaica','Japan','Jordan','Kazakhstan','Kenya','Kiribati','South Korea',
			'North Korea','Kosovo','Kuwait','Kyrgyzstan',"Lao People's Democratic Republic",'Latvia','Lebanon','Lesotho','Liberia','Libyan Arab Jamahiriya','Liechtenstein',
			'Lithuania','Luxembourg','Macau','Macedonia','Madagascar','Malawi','Malaysia','Maldives','Mali','Malta','Marshall Islands','Martinique','Mauritania','Mauritius',
			'Mayotte','Mexico','Micronesia, Federated States Of','Moldova, Republic Of','Monaco','Mongolia','Montserrat','Montenegro','Morocco','Mozambique',
			'Myanmar (Burma)','Namibia','Nauru','Nepal','Netherlands','Netherlands Antilles','New Caledonia','New Zealand','Nicaragua','Niger','Nigeria','Niue','Norfolk Island',
			'Northern Mariana Islands','Norway','Oman','Pakistan','Palau','Palestinian Territory, Occupied','Panama','Papua New Guinea','Paraguay','Peru','Philippines','Pitcairn',
			'Poland','Portugal','Puerto Rico','Qatar','Reunion','Romania','Russia','Rwanda','Saint Helena','St. Kitts And Nevis','Saint Lucia','Saint Pierre And Miquelon',
			'Saint Vincent And The Grenadines','Samoa','San Marino','Sao Tome And Principe','Saudi Arabia','Senegal','Serbia','Seychelles','Sierra Leone','Singapore','Slovakia',
			'Slovenia','Solomon Islands','Somalia','South Africa','South Georgia And The South Sandwich Islands','Spain','Sri Lanka','Sudan','Suriname','Svalbard And Jan Mayen',
			'Swaziland','Sweden','Switzerland','Syria','Taiwan, Republic Of China','Tajikistan','Tanzania','Thailand','Togo','Tokelau','Tonga','Trinidad And Tobago',
			'Tunisia','Turkey','Turkmenistan','Turks And Caicos Islands','Tuvalu','Uganda','Ukraine','United Arab Emirates','United Kingdom','United States',
			'United States Minor Outlying Islands','Uruguay','Uzbekistan','Vanuatu','Venezuela','Viet Nam','British Virgin Islands','U.S. Virgin Islands','Wallis And Futuna',
			'Western Sahara','Yemen','Zambia','Zimbabwe','Not Applicable');
		if(!in_array($countryStr, $goodCountryArr)){
			if(preg_grep( '/'.$countryStr.'/i' , $goodCountryArr )){
				//Name in approved list, but case is wrong, thus fix
				foreach($goodCountryArr as $countryName){
					if(strtolower($countryName) == strtolower($countryStr)) $countryStr = $countryName;
				}
			}
			else{
				$countryStr = '';
			}
		}
		return $countryStr;
	}

	function mbStrtr($str, $from, $to = null) {
		if(function_exists('mb_strtr')) {
			return mb_strtr($str, $from, $to);
		}
		else{
			if(is_array($from)) {
				$from = array_map('utf8_decode', $from);
				$from = array_map('utf8_decode', array_flip ($from));
				return utf8_encode (strtr (utf8_decode ($str), array_flip ($from)));
			}
			return utf8_encode (strtr (utf8_decode ($str), utf8_decode($from), utf8_decode ($to)));
		}
	}

	//GUID verification functions
	public function verifySesarGuids(){
		//Clear IGSN verification table
		$this->conn->query('DELETE FROM igsnverification');
		$this->conn->query('OPTIMIZE TABLE igsnverification');

		$this->logOrEcho('Loading records into verification table...',1);
		$sesarResultArr = array('totalCnt'=>0);
		$this->batchVerifySesar($sesarResultArr);

		if($sesarResultArr['totalCnt']){
			$this->logOrEcho('Calculating stats...',1);
			$sql = 'UPDATE igsnverification i INNER JOIN omoccurrences o ON i.igsn = o.occurrenceid SET i.occidInPortal = o.occid WHERE i.occidInPortal IS NULL';
			if(!$this->conn->query($sql)){
				$this->logOrEcho('ERROR updating IGSN field: '.$this->conn->error,2);
			}
			//Grab collection details
			$collArr = array();
			$sql = 'SELECT o.collid, COUNT(o.occid) as cnt FROM omoccurrences o INNER JOIN igsnverification i ON o.occid = i.occidInPortal GROUP BY o.collid ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$collArr[$r->collid]['cnt'] = $r->cnt;
			}
			$rs->free();
			$sql = 'SELECT collid, CONCAT_WS(":",institutionCode,collectionCode) as code, collectionname FROM omcollections WHERE collid IN('.implode(',',array_keys($collArr)).')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$collArr[$r->collid]['name'] = $r->collectionname.' ('.$r->code.')';
			}
			$rs->free();
			$sesarResultArr['collid'] = $collArr;

			//Add missing IGSNs
			$sql = 'SELECT igsn FROM igsnverification WHERE occidInPortal IS NULL';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$sesarResultArr['missing'][$r->igsn] = array();
			}
			$rs->free();
		}
		if(isset($sesarResultArr['missing'])) $this->setMissingSesarMeta($sesarResultArr);
		$this->setSynchronizationStatus();
		return $sesarResultArr;
	}

	private function batchVerifySesar(&$sesarResultArr, $pageNumber = 0){
		$retStatus = false;
		$batchLimit = 1000;
		$cnt = 0;
		$ns = substr($this->namespace,0,3);
		$url = 'https://app.geosamples.org/samples/user_code/NEO?limit='.$batchLimit.'&page_no='.$pageNumber;
		if(!$this->productionMode) $url = 'https://app-sandbox.geosamples.org/samples/user_code/NEO?limit='.$batchLimit.'&page_no='.$pageNumber;
		$headers = [
			'Accept: application/json'
		];

		$responseArr = $this->getSesarApiData($url, 'get', null, $headers);
		if($responseArr['retCode'] == 200){
			if($retStr = $responseArr['retStr']){
				$jsonObj = json_decode($retStr);
				$sqlBase = 'INSERT INTO igsnverification(igsn) VALUE';
				$sqlFrag = '';
				foreach($jsonObj->igsn_list as $igsn){
					//Load records into IGSN Verification table
					$igsn = str_replace('10.58052/', '', $igsn);
					$sqlFrag .= '("'.$igsn.'"),';
					$cnt++;
					if($cnt%1000==0){
						if($this->conn->query($sqlBase.trim($sqlFrag,', '))){
							$this->logOrEcho((($pageNumber*$batchLimit)+$cnt).' records loaded',2);
							$sqlFrag = '';
						}
						else{
							$this->logOrEcho('ERROR loading IGSNs: '.$this->conn->error,2);
							return false;
						}
					}						
				}
				if($sqlFrag){
					if($this->conn->query($sqlBase.trim($sqlFrag,', '))){
						$this->logOrEcho(($pageNumber*$batchLimit)+$cnt.' records loaded',2);
					}
				}
			}
		}
		else{
			if($sesarResultArr['totalCnt']){
				$this->logOrEcho('Done harvesting IGSNs (return count: '.number_format($sesarResultArr['totalCnt']).')',1);
				return true;
			}
			else{
				$this->logOrEcho('ERROR obtaining IGSNs (code: '.$responseArr['retCode'].'): '.$this->errorMessage,1);
				return false;
			}
		}
		$sesarResultArr['totalCnt'] += $cnt;
		$pageNumber++;
		$retStatus = $this->batchVerifySesar($sesarResultArr,$pageNumber);
		return $retStatus;
	}

	private function setMissingSesarMeta(&$sesarResultArr){
		//Grab SESAR meta for unmatched IGSNs
		$this->logOrEcho(count($sesarResultArr['missing']).' records unlink IGSNs found. Getting metadata from SESAR Systems...',1);
		$url = 'https://app.geosamples.org/sample/igsn/10.58052/';
		if(!$this->productionMode) $url = 'https://app-sandbox.geosamples.org/sample/igsn/10.58052/';
		$headers = [
			'Accept: application/json'
		];	
		$cnt = 0;
		foreach(array_keys($sesarResultArr['missing']) as $lostIGSN){
			$resArr = $this->getSesarApiData($url.$lostIGSN, 'get', null, $headers);
			if ($resArr['retCode'] == 200) {
				$retStr = $resArr['retStr'];
				if (strpos($retStr, 'The application has encountered an unknown error.') === 0) {
					if (preg_match('/[A-Z.\s]+(\{.+)/i', $retStr, $m1)) {
						$retStr = $m1[1]; 
					}
				}
				$igsnObj = json_decode($retStr);
				$igsnParts = explode('/', $igsnObj->sample->igsn);
				$catNum = $igsnParts[1]; 
				if (preg_match('/\[\s*(\d+)\s*\]/', $igsnObj->sample->name, $m2)) {
					$occid = $m2[1];
				}
				$sesarResultArr['missing'][$lostIGSN] = array('catNum' => $catNum, 'occid' => $occid);
				$sql = 'UPDATE igsnverification SET occidInSesar = ' . $occid . ', catalogNumber = "' . $catNum . '" WHERE igsn = "' . $lostIGSN . '"';
				$this->conn->query($sql);
			}
			$cnt++;
			if($cnt%10==0) $this->logOrEcho($cnt.' records processed',2);
		}
		$this->logOrEcho('Complete!',2);
	}

	private function setSynchronizationStatus(){
		$status = false;
		$sqlArr = array();
		$sqlArr[] = 'UPDATE omoccurrences o LEFT JOIN igsnverification v ON o.occurrenceid = v.igsn
			SET v.syncStatus = "IGSN in portal, not in SESAR system"
			WHERE o.occurrenceID LIKE "NEON%" AND v.igsn IS NULL';
		$sqlArr[] = 'UPDATE igsnverification SET syncStatus = "OK" WHERE occidInPortal IS NOT NULL AND catalogNumber IS NULL';
		$sqlArr[] = 'UPDATE igsnverification i INNER JOIN omoccurrences o ON i.occidInSesar = o.occid
			SET i.syncStatus = "newIGSN re-assigned to same occurrence record"
			WHERE i.occidInPortal IS NULL AND i.igsn != o.occurrenceID AND i.syncStatus IS NULL';
		$sqlArr[] = 'UPDATE igsnverification v INNER JOIN omoccuridentifiers i ON v.catalogNumber = i.identifierValue
			INNER JOIN omoccurrences o ON i.occid = o.occid
			SET v.syncStatus = "newIGSN, new record, matching NEON ID"
			WHERE v.occidInPortal IS NULL AND v.igsn != o.occurrenceID AND v.syncStatus IS NULL';
		$sqlArr[] = 'UPDATE igsnverification SET syncStatus = "Occurrence not in portal" WHERE occidInPortal IS NULL AND catalogNumber IS NOT NULL AND syncStatus IS NULL';
		foreach($sqlArr as $sql){
			if(!$this->conn->query($sql)){
				$errorStr = 'ERROR setting syncStatus: '.$this->conn->error;
				echo $errorStr.'<br/>';
				//$this->warningArr[] = $errorStr;
			}
		}
		return $status;
	}

	public function verifyLocalGuids(){
		$retArr = array();
		if($this->collid){
			$sql = 'SELECT o.occid, o.occurrenceid FROM omoccurrences o LEFT JOIN igsnverification i ON o.occid = i.occidInSesar '.
				'WHERE o.occurrenceID LIKE "'.$this->namespace.'%" AND o.collid = '.$this->collid.' AND i.occidInSesar IS NULL';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->occid] = $r->occurrenceid;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function syncIGSN($occid,$catalogNumber,$igsn){
		$ok = true;
		$retArr = array('status'=>0);
		if(is_numeric($occid) && preg_match('/^[A-Z0-9]+$/', $igsn)){
			$sql = 'SELECT catalogNumber, otherCatalogNumbers, occurrenceID FROM omoccurrences WHERE occid = '.$occid;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				if($r->occurrenceID){
					$retArr['errCode'] = 1;
					$retArr['guid'] = $r->occurrenceID;
					$ok = false;
				}
				elseif($r->catalogNumber != $catalogNumber && $r->otherCatalogNumbers != $catalogNumber){
					$retArr['errCode'] = 2;
					$catNum = $r->catalogNumber;
					if($r->otherCatalogNumbers) $catNum .= ', '.$r->otherCatalogNumbers;
					$retArr['catNum'] = trim($catNum,', ');
					$ok = false;
				}
			}
			else{
				$retArr['errCode'] = 3;
				$ok = false;
			}
			$rs->free();

			$sql = 'SELECT identifierValue FROM omoccuridentifiers WHERE (occid = '.$occid.') ORDER BY sortBy';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if($r->identifierValue != $catalogNumber && $r->identifierValue != $catalogNumber){
					$retArr['errCode'] = 2;
					$catNum = $r->identifierValue;
					if(isset($retArr['catNum'])) $catNum .= ', '.$retArr['catNum'];
					$retArr['catNum'] = $catNum;
					$ok = false;
				}
			}
			$rs->free();

			if($ok){
				$sqlUpdate = 'UPDATE omoccurrences SET occurrenceid = "'.$this->cleanInStr($igsn).'" WHERE occid = '.$occid;
				if($this->conn->query($sqlUpdate)){
					if($this->conn->affected_rows) $retArr['status'] = 1;
				}
			}
		}
		return $retArr;
	}

	private function getSesarApiData($url, $method = 'get', $requestData = null, $customHeaders = []) {
		$retArr = array();
		$ch = curl_init();
	
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
		$headers = !empty($customHeaders) ? $customHeaders : [];
	
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
		if (strtolower($method) === 'post') {
			curl_setopt($ch, CURLOPT_POST, true);
		}
	
		if ($requestData) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
		}
	
		$retArr['retStr'] = curl_exec($ch);
		$retArr['retCode'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
		if ($retArr['retCode'] != 200) {
			$this->errorMessage = 'FATAL CURL ERROR: ' . curl_error($ch) . ' (#' . curl_errno($ch) . ')';
		}
	
		curl_close($ch);
		return $retArr;
	}

	//Misc data return functions
	public function getGuidCount($collid = null){
		$cnt = 0;
		if($this->namespace){
			$sql = 'SELECT COUNT(*) AS cnt FROM omoccurrences WHERE (occurrenceid LIKE "'.$this->namespace.'%") ';
			if($collid) $sql .= 'AND (collid = '.$this->collid.')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	public function getMissingGuidCount(){
		$cnt = 0;
		$sql = 'SELECT COUNT(o.occid) AS cnt '.$this->getSqlBase();
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$cnt = $r->cnt;
		}
		$rs->free();
		return $cnt;
	}

	private function getSqlBase(){
		$sqlBase = 'FROM omoccurrences o INNER JOIN NeonSample s ON o.occid = s.occid
			WHERE (o.occurrenceid IS NULL) AND (s.sampleReceived = 1) AND (s.acceptedForAnalysis = 1)
			AND (s.checkinUid IS NOT NULL) AND (s.occid = s.occidOriginal) ';
		if($this->collid) $sqlBase .= 'AND (o.collid = '.$this->collid.') ';
		return $sqlBase;
	}
	
	public function getNeedsUpdateCount(){
		$cnt = 0;
		$sql = 'SELECT COUNT(o.occid) AS cnt ' . $this->getSqlBaseNeedsUpdate();
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$cnt = $r->cnt;
		}
		$rs->free();
		return $cnt;
	}
	
	private function getSqlBaseNeedsUpdate(){
		$sqlBase = 'FROM omoccurrences o 
			INNER JOIN omcollections c ON o.collid = c.collid
			INNER JOIN NeonSample s ON o.occid = s.occid
			WHERE c.institutionCode = "NEON"
			AND c.collid NOT IN (44,74,78,79,80,82,83,95,97,4,81,85,93,96,84,115)
			AND s.sampleReceived = 1
			AND o.dateLastModified > IFNULL(s.sampleLastUpdatedSESAR, "1900-01-01") ';
	
		if($this->collid){
			$sqlBase .= 'AND o.collid = ' . intval($this->collid) . ' ';
		}
	
		return $sqlBase;
	}

	//Setters and getters
	public function setCollid($id){
		if($id && is_numeric($id)){
			$this->collid = $id;
		}
	}

	public function setCollArr(){
		if($this->collid){
			$sql = 'SELECT institutionCode, collectionCode, collectionName, contact, email FROM omcollections WHERE collid = '.$this->collid;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$this->collArr['collectionName'] = $r->collectionName.' ('.$r->institutionCode.($r->collectionCode?$r->collectionCode:'').')';
				$this->collArr['contact'] = $r->contact;
				$this->collArr['email'] = $r->email;
			}
			$rs->free();
		}
	}

	public function getCollectionName(){
		if($this->collArr) return $this->collArr['collectionName'];
	}
	
	public function setSesarUser($user){
		$this->sesarUser = $user;
	}

	public function setSesarPwd($pwd){
		$this->sesarPwd = $pwd;
	}

	public function setNamespace($ns){
		if(preg_match('/^[A-Z]+$/', $ns)){
			if($ns == 'NEO') $ns .= 'N';
			$this->namespace = $ns;
		}
	}

	public function generateIgsnSeed(){
		$igsnSeed = '';
		$this->getSesarProfile();
		//Get maximum identifier
		if($this->collid){
			$seed = 0;
			$sql = 'SELECT MAX(occurrenceID) as maxid FROM omoccurrences WHERE occurrenceID LIKE "NEO%" AND length(occurrenceID) = 9';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$seed = $r->maxid;
			}
			$rs->free();
			//Increase max alphanumeric ID by 1
			if($seed){
				$seedBaseTen = base_convert($seed,36,10);
				$igsn = base_convert($seedBaseTen+1,10,36);
				$igsn = str_pad($igsn, (6), '0', STR_PAD_LEFT);
				$igsnSeed = strtoupper($igsn);
			}
			else{
				$igsnSeed = $this->namespace.str_pad('1', (6), '0', STR_PAD_LEFT);
			}
		}
		return $igsnSeed;
	}

	public function setIgsnSeed($seed){
		if($seed && preg_match('/^[A-Z0-9]+$/', $seed)){
			if($this->igsnExists($seed)) $this->warningArr[] = 'ERROR: Seed ('.$seed.') already exists or is out of sequence ';
			else $this->igsnSeed = $seed;
		}
	}

	public function getIgsnSeed(){
		return $this->igsnSeed;
	}

	public function setRegistrationMethod($method){
		$this->registrationMethod = $method;
	}

	public function setGenerationMethod($method){
		if($method == 'inhouse') $this->generationMethod = $method;
	}

	public function getProductionMode(){
		return $this->productionMode;
	}
}
?>