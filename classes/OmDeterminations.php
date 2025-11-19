<?php
include_once('Manager.php');
include_once('utilities/OccurrenceUtil.php');
include_once('utilities/UuidFactory.php');

class OmDeterminations extends Manager{

	private $detID = null;
	private $occid = null;
	private $schemaMap = array();
	private $parameterArr = array();
	private $typeStr = '';

	public function __construct($conn){
		parent::__construct(null, 'write', $conn);
		/*
		 $this->fieldMap = array('identifiedBy' => 's', 'identifiedByAgentID' => 'i', 'identifiedByID' => 's', 'dateIdentified' => 's', 'dateIdentifiedInterpreted' => 's',
		 'higherClassification' => 's', 'family' => 's', 'sciname' => 's', 'verbatimIdentification' => 's', 'scientificNameAuthorship' => 's', 'tidInterpreted' => 'i',
		 'identificationUncertain' => 'i', 'identificationQualifier' => 's', 'genus' => 's', 'specificEpithet' => 's', 'verbatimTaxonRank' => 's', 'taxonRank' => 's',
		 'infraSpecificEpithet' => 's', 'isCurrent' => 'i', 'printQueue' => 'i', 'appliedStatus' => 'i', 'securityStatus' => 'i', 'securityStatusReason' => 's',
		 'detType' => 's', 'identificationReferences' => 's', 'identificationRemarks' => 's', 'taxonRemarks' => 's', 'identificationVerificationStatus' => 's',
		 'taxonConceptID' => 's', 'sourceIdentifier' => 's', 'sortSequence' => 'i', 'recordID' => 's', 'createdUid' => 'i', 'modifiedUid' => 'i', 'dateLastModified' => 's');
		 */
		$this->schemaMap = array('identifiedBy' => 's', 'dateIdentified' => 's', 'higherClassification' => 's', 'family' => 's', 'sciname' => 's', 'verbatimIdentification' => 's',
			'scientificNameAuthorship' => 's', 'identificationQualifier' => 's', 'isCurrent' => 'i', 'printQueue' => 'i', 'appliedStatus' => 'i',
			'securityStatus' => 'i', 'securityStatusReason' => 's', 'detType' => 's', 'identificationReferences' => 's', 'identificationRemarks' => 's', 'taxonRemarks' => 's',
			'identificationVerificationStatus' => 's', 'taxonConceptID' => 's', 'sourceIdentifier' => 's', 'sortSequence' => 'i');
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function getDeterminationArr($filterArr = null){
		$retArr = array();
		$uidArr = array();
		$sql = 'SELECT detID, occid, '.implode(', ', array_keys($this->schemaMap)).', initialTimestamp FROM omoccurdeterminations WHERE ';
		if($this->detID) $sql .= '(detID = '.$this->detID.') ';
		elseif($this->occid) $sql .= '(occid = '.$this->occid.') ';
		foreach($filterArr as $field => $cond){
			$sql .= 'AND '.$field.' = "'.$this->cleanInStr($cond).'" ';
		}
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_assoc()){
				$retArr[$r['detID']] = $r;
				$uidArr[$r['createdUid']] = $r['createdUid'];
				$uidArr[$r['modifiedUid']] = $r['modifiedUid'];
			}
			$rs->free();
		}
		if($uidArr){
			//Add user names for modified and created by
			$sql = 'SELECT uid, firstname, lastname, username FROM users WHERE uid IN('.implode(',', $uidArr).')';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$uidArr[$r->uid] = $r->lastname . ($r->firstname ? ', ' . $r->firstname : '');
				}
				$rs->free();
			}
			foreach($retArr as $detID => $detArr){
				if($detArr['createdUid'] && array_key_exists($detArr['createdUid'], $uidArr)) $retArr[$detID]['createdBy'] = $uidArr[$detArr['createdUid']];
				if($detArr['modifiedUid'] && array_key_exists($detArr['modifiedUid'], $uidArr)) $retArr[$detID]['modifiedBy'] = $uidArr[$detArr['modifiedUid']];
			}
		}
		return $retArr;
	}

	public function insertDetermination($inputArr){
		$status = false;
		if($this->occid){
			if(!isset($inputArr['createdUid'])) $inputArr['createdUid'] = $GLOBALS['SYMB_UID'];
			$sql = 'INSERT INTO omoccurdeterminations(occid, recordID';
			$sqlValues = '?, ?, ';
			$paramArr = array($this->occid);
			$paramArr[] = UuidFactory::getUuidV4();
			$this->typeStr = 'is';
			$this->setParameterArr($inputArr);
			foreach($this->parameterArr as $fieldName => $value){
				$sql .= ', '.$fieldName;
				$sqlValues .= '?, ';
				$paramArr[] = $value;
			}
			$sql .= ') VALUES('.trim($sqlValues, ', ').') ';
			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param($this->typeStr, ...$paramArr);
				try {
					if($stmt->execute()){
						if($stmt->affected_rows || !$stmt->error){
							$this->detID = $stmt->insert_id;
							$status = true;

							// Start NEON Customization
							if (!empty($inputArr['isCurrent']) && intval($inputArr['isCurrent']) === 1) {
                            $updateSql = 'UPDATE omoccurdeterminations 
                                          SET isCurrent = 0 
                                          WHERE occid = ? AND detid != ? AND isCurrent = 1';
                            if ($updateStmt = $this->conn->prepare($updateSql)) {
                                $updateStmt->bind_param('ii', $this->occid, $this->detID);
                                if (!$updateStmt->execute()) {
                                    $this->errorMessage = 'ERROR updating isCurrent value: ' . $updateStmt->error;
                                }
                                $updateStmt->close();
                            } else {
                                $this->errorMessage = 'ERROR preparing isCurrent query: ' . $this->conn->error;
                            }
							// End NEON customization
                        }

						}
						else $this->errorMessage = 'ERROR inserting omoccurdeterminations record (2): '.$stmt->error;
					}
					else $this->errorMessage = 'ERROR inserting omoccurdeterminations record (1): '.$stmt->error;
				} catch (mysqli_sql_exception $e) {
					if ($e->getCode() == '1062' || $e->getCode() == '1406') {
						$this->errorMessage = $e->getMessage();
					}
					else {
						throw $e;
					}
				}
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for omoccurdeterminations insert: '.$this->conn->error;
		}
		return $status;
	}

	public function updateDetermination($inputArr){
		$status = false;
		if($this->detID && $this->conn){
			$this->setParameterArr($inputArr);
			$paramArr = array();
			$sqlFrag = '';
			foreach($this->parameterArr as $fieldName => $value){
				$sqlFrag .= $fieldName . ' = ?, ';
				$paramArr[] = $value;
			}
			$paramArr[] = $this->detID;
			$this->typeStr .= 'i';
			$sql = 'UPDATE omoccurdeterminations SET '.trim($sqlFrag, ', ').' WHERE (detID = ?)';
			if($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param($this->typeStr, ...$paramArr);
				$stmt->execute();
				if($stmt->affected_rows || !$stmt->error) $status = true;
				else $this->errorMessage = 'ERROR updating omoccurdeterminations record: '.$stmt->error;
				$stmt->close();
			}
			else $this->errorMessage = 'ERROR preparing statement for updating omoccurdeterminations: '.$this->conn->error;
		}
		return $status;
	}

	private function setParameterArr($inputArr){
		foreach($this->schemaMap as $field => $type){
			$postField = '';
			if(isset($inputArr[$field])) $postField = $field;
			elseif(isset($inputArr[strtolower($field)])) $postField = strtolower($field);
			if($postField){
				$value = trim($inputArr[$postField]);
				if($value){
					$postField = strtolower($postField);
					if($postField == 'establisheddate') $value = OccurrenceUtil::formatDate($value);
					if($postField == 'modifieduid') $value = OccurrenceUtil::verifyUser($value, $this->conn);
					if($postField == 'createduid') $value = OccurrenceUtil::verifyUser($value, $this->conn);
					if($postField == 'identificationuncertain' || $postField == 'iscurrent' || $postField == 'printqueue' || $postField == 'appliedstatus' || $postField == 'securitystatus'){
						if(!is_numeric($value)){
							$value = strtolower($value);
							if($value == 'yes' || $value == 'true') $value = 1;
							else $value = 0;
						}
					}
					if($postField == 'sortsequence'){
						if(!is_numeric($value)) $value = 10;
					}
				}
				else $value = null;
				$this->parameterArr[$field] = $value;
				$this->typeStr .= $type;
			}
		}
		if(isset($inputArr['occid']) && $inputArr['occid'] && !$this->occid) $this->occid = $inputArr['occid'];
	}

	public function deleteDetermination(){
		if($this->detID){
			$sql = 'DELETE FROM omoccurdeterminations WHERE detID = '.$this->detID;
			if($this->conn->query($sql)){
				return true;
			}
			else{
				$this->errorMessage = 'ERROR deleting omoccurdeterminations record: '.$this->conn->error;
				return false;
			}
		}
	}

	//Setters and getters
	public function setDetID($id){
		if(is_numeric($id)) $this->detID = $id;
	}

	public function getDetID(){
		return $this->detID;
	}

	public function setOccid($id){
		if(is_numeric($id)) $this->occid = $id;
	}

	public function getSchemaMap(){
		return $this->schemaMap;
	}

	// NEON specific functions

	public function insertAndPropagateNEONDetermination($occid, $inputArr) {
		$results = []; 
		$occidList = [];

		$sql = 'SELECT occid, occidAssociate 
				FROM omoccurassociations 
				WHERE (occid = ? OR occidAssociate = ?) 
				AND relationship = "derivedFromSameIndividual"';

		if ($stmt = $this->conn->prepare($sql)) {
			$stmt->bind_param('ii', $occid, $occid);
			if ($stmt->execute()) {
				$result = $stmt->get_result();
				while ($row = $result->fetch_assoc()) {
					if (!empty($row['occid'])) $occidList[$row['occid']] = true;
					if (!empty($row['occidAssociate'])) $occidList[$row['occidAssociate']] = true;
				}
			} else {
				$this->errorMessage = 'ERROR executing association query: ' . $stmt->error;
				return false;
			}
			$stmt->close();
		} else {
			$this->errorMessage = 'ERROR preparing association query: ' . $this->conn->error;
			return false;
		}

		$occidList[$occid] = true;

		foreach (array_keys($occidList) as $assocOccid) {
			$this->occid = $assocOccid;
			if ($this->insertNEONDetermination($inputArr)) {
				$results[$assocOccid] = ['success' => true, 'message' => 'Inserted successfully'];
			} else {
				$results[$assocOccid] = [
					'success' => false,
					'message' => $this->errorMessage ?: 'Unknown error during insert'
				];
			}
		}

		return $results;
	}

	public function insertNEONDetermination($inputArr){
		$status = false;
		if($this->occid){
			if(!isset($inputArr['createdUid'])) $inputArr['createdUid'] = $GLOBALS['SYMB_UID'];

			// insert determination
			$sql = 'INSERT INTO omoccurdeterminations(occid, recordID';
			$sqlValues = '?, ?, ';
			$paramArr = [$this->occid, UuidFactory::getUuidV4()];
			$this->typeStr = 'is';
			$this->setParameterArr($inputArr);
			foreach($this->parameterArr as $fieldName => $value){
				$sql .= ', '.$fieldName;
				$sqlValues .= '?, ';
				$paramArr[] = $value;
			}
			$sql .= ') VALUES('.trim($sqlValues, ', ').')';

			if($stmt = $this->conn->prepare($sql)){
				$stmt->bind_param($this->typeStr, ...$paramArr);

				try {
					if($stmt->execute()){
						$this->detID = $stmt->insert_id;
						$status = true;


						// tid lookup
						try {
							$tid = null;
							$tidSql = 'SELECT t.tid FROM taxa t WHERE t.sciName = ? LIMIT 2';
							if ($tidstmt = $this->conn->prepare($tidSql)) {
								$tidstmt->bind_param('s', $inputArr['sciname']);
								if ($tidstmt->execute()) {
									$result = $tidstmt->get_result();
									if($result->num_rows === 1){
										$tid = (int)$result->fetch_assoc()['tid'];
										$updateSql = 'UPDATE omoccurdeterminations SET tidInterpreted = ? WHERE detid = ?';
										if($updateStmt = $this->conn->prepare($updateSql)){
											$updateStmt->bind_param('ii', $tid, $this->detID);
											$updateStmt->execute();
											$updateStmt->close();
										} else $this->errorMessage .= ' ERROR preparing tid update: '.$this->conn->error;
									} elseif ($result->num_rows > 1){
										$this->errorMessage .= ' Multiple taxa found for sciname.';
									} else {
										$this->errorMessage .= ' No tid found for sciname.';
									}
								} else $this->errorMessage .= ' ERROR executing tid query: '.$tidstmt->error;
								$tidstmt->close();
							} else $this->errorMessage .= ' ERROR preparing tid query: '.$this->conn->error;
						} catch(Exception $e){
							$this->errorMessage .= ' TID lookup exception: '.$e->getMessage();
						}

						if(!empty($inputArr['isCurrent']) && intval($inputArr['isCurrent']) === 1){
							try {
								// if current determination,set all others to isCurrent = 0 
								$updateSql = 'UPDATE omoccurdeterminations 
											SET isCurrent = 0 
											WHERE occid = ? AND detid != ? AND isCurrent = 1';
								if ($updateStmt = $this->conn->prepare($updateSql)){
									$updateStmt->bind_param('ii', $this->occid, $this->detID);
									$updateStmt->execute();
									$updateStmt->close();
								} else $this->errorMessage .= ' ERROR preparing isCurrent clear query: '.$this->conn->error;

								// Update occurrence for current determination
								$occUpdateSql = 'UPDATE omoccurrences o
									JOIN omoccurdeterminations d ON o.occid = d.occid
									SET 
										o.identifiedBy = d.identifiedBy,
										o.dateIdentified = d.dateIdentified,
										o.family = d.family,
										o.sciname = d.sciname,
										o.scientificName = d.sciname,
										o.scientificNameAuthorship = d.scientificNameAuthorship,
										o.tidInterpreted = d.tidInterpreted,
										o.identificationQualifier = d.identificationQualifier,
										o.identificationReferences = d.identificationReferences,
										o.identificationRemarks = d.identificationRemarks
									WHERE d.detid = ? AND d.isCurrent = 1';
								if ($occStmt = $this->conn->prepare($occUpdateSql)){
									$occStmt->bind_param('i', $this->detID);
									$occStmt->execute();
									$occStmt->close();
								} else $this->errorMessage .= ' ERROR preparing omoccurrences update query: '.$this->conn->error;
							} catch(Exception $e){
								$this->errorMessage .= ' Omoccurrences update exception: '.$e->getMessage();
							}
						}

					} else $this->errorMessage .= ' ERROR inserting determination: '.$stmt->error;
				} catch (mysqli_sql_exception $e){
					if(in_array($e->getCode(), [1062, 1406])) $this->errorMessage .= ' '.$e->getMessage();
					else throw $e;
				}
				$stmt->close();
			} else $this->errorMessage .= ' ERROR preparing insert: '.$this->conn->error;
		}

		return $status; 
	}




}
?>