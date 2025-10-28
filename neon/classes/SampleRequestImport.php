<?php
include_once('UtilitiesFileImport.php');
include_once('OmMaterialSample.php');
include_once('OmAssociations.php');
include_once('OccurrenceMaintenance.php');
include_once('utilities/UuidFactory.php');

class SampleRequestImport extends UtilitiesFileImport {

	private $request_id;
	private $importType;

	private $importManager = null;


    private $requestMetaArr = []; 
	private const IMPORT_SAMPLE = 1;
	private const IMPORT_MATERIAL_SAMPLE = 2;

	function __construct() {
		parent::__construct(null, 'write');
		$this->setVerboseMode(2);
		set_time_limit(2000);
	}

	function __destruct() {
		parent::__destruct();
	}

    // get request status
    private function getRequestStatus() {
        $sql = "SELECT status FROM neonrequest WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $this->request_id);
        $stmt->execute();
        $stmt->bind_result($status);
        $stmt->fetch();
        $stmt->close();
        return $status;
    }

    // load sample data
	public function loadData($postArr,$uid) {
		$status = false;
		if ($this->fileName && isset($postArr['tf'])) {
			$this->fieldMap = array_flip($postArr['tf']);
			if ($this->setTargetPath()) {
				if ($this->getHeaderArr()) {
					$cnt = 1;
					while ($recordArr = $this->getRecordArr()) {

						$identifierArr = array();

                        if ($this->importType == self::IMPORT_SAMPLE) {

                            if (isset($this->fieldMap['occid'])) {
                                if ($recordArr[$this->fieldMap['occid']]) $identifierArr['occid'] = $recordArr[$this->fieldMap['occid']];
                            }
                            if (isset($this->fieldMap['occurrenceid'])) {
                                if ($recordArr[$this->fieldMap['occurrenceid']]) $identifierArr['occurrenceID'] = $recordArr[$this->fieldMap['occurrenceid']];
                            }
                            if (isset($this->fieldMap['catalognumber'])) {
                                if ($recordArr[$this->fieldMap['catalognumber']]) $identifierArr['catalogNumber'] = $recordArr[$this->fieldMap['catalognumber']];
                            }
                            if (isset($this->fieldMap['othercatalognumbers'])) {
                                if ($recordArr[$this->fieldMap['othercatalognumbers']]) $identifierArr['otherCatalogNumbers'] = $recordArr[$this->fieldMap['othercatalognumbers']];
                            }
                            $this->logOrEcho('#' . $cnt . ': Processing Sample: ' . implode(', ', $identifierArr));
                            if ($sampArr = $this->getSampPK($identifierArr)) {
                                $inserted = $this->insertRecord($recordArr, $sampArr, $postArr, $uid);
                                if ($inserted) $status = true; 
                            }
                            $cnt++;
                        }
                        if ($this->importType == self::IMPORT_MATERIAL_SAMPLE) {
                            if (isset($this->fieldMap['matsampleid'])) {
                                if ($recordArr[$this->fieldMap['matsampleid']]) $identifierArr['matsampleid'] = $recordArr[$this->fieldMap['matsampleid']];
                            }
                            if (isset($this->fieldMap['catalognumber'])) {
                                if ($recordArr[$this->fieldMap['catalognumber']]) $identifierArr['catalogNumber'] = $recordArr[$this->fieldMap['catalognumber']];
                            }
                            $this->logOrEcho('#' . $cnt . ': Processing Material Sample: ' . implode(', ', $identifierArr));
                            if ($sampArr = $this->getSampPK($identifierArr)) {
                                $inserted = $this->insertRecord($recordArr, $sampArr, $postArr, $uid);
                                if ($inserted) $status = true;                             }
                            $cnt++;
                        }
					}
				}
				$this->deleteImportFile();
			}
		}
		return $status;
	}

    # insert samples/material sample request links
    private function insertRecord($recordArr, $sampArr, $postArr, $uid) {

        $status = false;
        $allowedUseTypes = ['destructive', 'consumptive', 'invasive', 'non-destructive'];
        $requestStatus = $this->getRequestStatus();
        $defaultStatus = ($requestStatus === 'pending funding') ? 'pending funding' : 'pending fulfillment';


        if ($this->importType == self::IMPORT_SAMPLE) {
            $allowedSubstances = ['whole sample', 'subsample/aliquot', 'tissue/material sample', 'individual(s)', 'image', 'data'];
            $insertSql = "INSERT INTO neonsamplerequestlink 
                        (request_id, occid, status, available, use_type, substance_provided, notes, initialTimestamp,editedTimestamp)
                        VALUES (?, ?, ?, 'yes', ?, ?, ?, NOW(),NOW())";
            $checkDupeSql = "SELECT COUNT(*) as cnt FROM neonsamplerequestlink 
                        WHERE request_id = ? AND occid = ?";
            $checkReqSql = "SELECT COUNT(*) as cnt FROM neonsamplerequestlink s
                        JOIN omoccurrences o
                        ON s.occid = o.occid
                        WHERE s.request_id != ? AND s.occid = ? AND s.status IN ('current','pending fulfillment') ";
            $checkAvailSql = "SELECT availability FROM omoccurrences
                        WHERE occid = ?";

            $insertStmt = $this->conn->prepare($insertSql);
            $checkDupeStmt  = $this->conn->prepare($checkDupeSql);
            $checkReqStmt  = $this->conn->prepare($checkReqSql);
            $checkAvailStmt = $this->conn->prepare($checkAvailSql);

            if ($insertStmt && $checkDupeStmt && $checkReqStmt && $checkAvailStmt) {
                foreach ($sampArr as $occid) {
                    $use_type = $recordArr[$this->fieldMap['use_type']] ?? null;
                    $substance_provided = $recordArr[$this->fieldMap['substance_provided']] ?? null;
                    $notes = isset($this->fieldMap['notes']) ? $recordArr[$this->fieldMap['notes']] ?? null : null;

                    if (!$use_type || !$substance_provided) {
                        $this->logOrEcho("ERROR: Missing required fields for occid $occid", 1);
                        continue;
                    }
                    if (in_array($substance_provided, ["individual(s)", "tissue/material sample", "subsample/aliquot"]) && !$notes) {
                        $this->logOrEcho(
                            "ERROR: Notes required for occid $occid when substance is tissue/material sample, individual(s), or subsample/aliquot", 
                            1
                        );
                        continue;
                    }
                    
                    if (!in_array($use_type, $allowedUseTypes)) {
                        $this->logOrEcho("ERROR: Invalid use_type '$use_type' for occid $occid. Allowed: " . implode(', ', $allowedUseTypes), 1);
                        continue;
                    }
                    if (!in_array($substance_provided, $allowedSubstances)) {
                        $this->logOrEcho("ERROR: Invalid substance_provided '$substance_provided' for occid $occid. Allowed: " . implode(', ', $allowedSubstances), 1);
                        continue;
                    }

                    // duplicate within request check
                    $checkDupeStmt->bind_param('ii', $this->request_id, $occid);
                    $checkDupeStmt->execute();
                    $checkDupeStmt->store_result();
                    $checkDupeStmt->bind_result($countdupe);
                    $checkDupeStmt->fetch();
                    $checkDupeStmt->free_result();

                    if ($countdupe > 0) {
                        $this->logOrEcho("Skipping occid $occid: already exists for this request", 1);
                        continue;
                    }

                    //  duplicate across other current requests check
                    $checkReqStmt->bind_param('ii', $this->request_id, $occid);
                    $checkReqStmt->execute();
                    $checkReqStmt->store_result();
                    $checkReqStmt->bind_result($countreq);
                    $checkReqStmt->fetch();
                    $checkReqStmt->free_result();

                    if ($countreq > 0) {
                        $this->logOrEcho("Skipping occid $occid: already associated with another current or pending request", 1);
                        continue;
                    }

                    //  check availability
                    $checkAvailStmt->bind_param('i', $occid);
                    $checkAvailStmt->execute();
                    $checkAvailStmt->store_result();
                    $checkAvailStmt->bind_result($availability);
                    $checkAvailStmt->fetch();
                    $checkAvailStmt->free_result();

                    if ($availability === 0) {
                        $this->logOrEcho("Skipping occid $occid: sample is unavailable according to occurrence record", 1);
                        continue;
                    }

                    $insertStmt->bind_param('iissss', $this->request_id, $occid, $defaultStatus, $use_type, $substance_provided, $notes);
                    if ($insertStmt->execute()) {
                        $status = true;
                        $this->logOrEcho("Sample Added: $occid", 1);
                    } else {
                        $this->logOrEcho("ERROR loading Sample: $occid - " . $insertStmt->error, 1);
                    }
                }

                $this->syncCollidsForRequest($uid);

                $insertStmt->close();
                $checkDupeStmt->close();
                $checkReqStmt->close();
                $checkAvailStmt->close();

            } else {
                $this->logOrEcho("ERROR preparing statement: " . $this->conn->error, 1);
            }
        }

        elseif ($this->importType == self::IMPORT_MATERIAL_SAMPLE) {
            $insertSql = "INSERT INTO neonmaterialsamplerequestlink 
                        (request_id, matSampleID, occid, status, use_type, sampleType, notes, initialTimestamp,editedTimestamp)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $checkSql = "SELECT COUNT(*) as cnt FROM neonmaterialsamplerequestlink 
                        WHERE request_id = ? AND matSampleID = ?";

            $insertStmt = $this->conn->prepare($insertSql);
            $checkStmt  = $this->conn->prepare($checkSql);

            if (!$insertStmt || !$checkStmt) {
                $this->logOrEcho("ERROR preparing statement: " . $this->conn->error, 1);
                return false;
            }

            foreach ($sampArr as $matSampleID) {
                $matSampleID = trim($matSampleID); 

                $occidStmt = $this->conn->prepare(
                    "SELECT s.occid
                    FROM ommaterialsample s
                    INNER JOIN neonsamplerequestlink r ON s.occid = r.occid
                    WHERE r.request_id = ? AND s.matSampleID = ?"
                );
                $occidStmt->bind_param('ii', $this->request_id, $matSampleID);
                $occidStmt->execute();
                $occidRes = $occidStmt->get_result();

                if ($occidRes && $occidRes->num_rows > 0) {
                    $row = $occidRes->fetch_assoc();
                    $occid = $row['occid'];
                    $this->logOrEcho("Found occid '$occid' for matSampleID '$matSampleID'", 1);
                } else {
                    $this->logOrEcho(
                        "ERROR: matSampleID '$matSampleID' cannot be imported — no linked occid found for request_id {$this->request_id}",
                        1
                    );
                    $occidStmt->close();
                    continue;  
                }
                $occidStmt->close();

                $use_type   = $recordArr[$this->fieldMap['use_type']] ?? null;
                $sampleType = $recordArr[$this->fieldMap['sampletype']] ?? null;
                $notes      = isset($this->fieldMap['notes']) ? $recordArr[$this->fieldMap['notes']] ?? null : null;

                if (!$use_type || !$sampleType) {
                    $this->logOrEcho("ERROR: Missing required fields for matSampleID $matSampleID", 1);
                    continue;
                }

                if (!in_array($use_type, $allowedUseTypes)) {
                    $this->logOrEcho("ERROR: Invalid use_type '$use_type' for matSampleID $matSampleID. Allowed: " . implode(', ', $allowedUseTypes), 1);
                    continue;
                }

                // duplicate check
                $checkStmt->bind_param('is', $this->request_id, $matSampleID);
                $checkStmt->execute();
                $checkStmt->store_result();
                $checkStmt->bind_result($count);
                $checkStmt->fetch();
                $checkStmt->free_result();

                if ($count > 0) {
                    $this->logOrEcho("Skipping matSampleID $matSampleID: already linked to this request", 1);
                    continue;
                }

                // insert
                $insertStmt->bind_param('iiissss', $this->request_id, $matSampleID, $occid, $defaultStatus, $use_type, $sampleType, $notes);
                if ($insertStmt->execute()) {
                    $status = true;
                    $this->logOrEcho("Material Sample Added: matSampleID $matSampleID", 1);
                } else {
                    $this->logOrEcho("ERROR inserting matSampleID $matSampleID: " . $insertStmt->error, 1);
                }
            }

            $insertStmt->close();
            $checkStmt->close();
        }
        return $status;
    }

        // link collids to request
    private function syncCollidsForRequest($uid) {

        $sql = "SELECT DISTINCT o.collid
            FROM neonsamplerequestlink r
            JOIN omoccurrences o ON r.occid = o.occid
            WHERE r.request_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $this->request_id);
        $stmt->execute();
        $res = $stmt->get_result();

        $expectedCollids = [];
        while ($row = $res->fetch_assoc()) {
            $expectedCollids[] = (int)$row['collid'];
        }
        $stmt->close();

        $sql = "SELECT coll_id FROM neoncollectionrequestlink WHERE request_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $this->request_id);
        $stmt->execute();
        $res = $stmt->get_result();

        $currentCollids = [];
        while ($row = $res->fetch_assoc()) {
            $currentCollids[] = (int)$row['coll_id'];
        }
        $stmt->close();

        $expectedSet = array_unique($expectedCollids);
        $currentSet  = array_unique($currentCollids);

        $toAdd    = array_diff($expectedSet, $currentSet);
        $toDelete = array_diff($currentSet, $expectedSet);

        if (!empty($toAdd)) {
            $insertSql = "INSERT INTO neoncollectionrequestlink (request_id, coll_id) VALUES ";
            $insertSql .= implode(',', array_fill(0, count($toAdd), '(?, ?)'));

            $stmt = $this->conn->prepare($insertSql);
            $types = str_repeat('i', count($toAdd) * 2);
            $values = [];
            foreach ($toAdd as $c) {
                $values[] = $this->request_id;
                $values[] = $c;
            }

            $stmt->bind_param($types, ...$values);
            if (!$stmt->execute()) {
                $this->logOrEcho("ERROR adding collids: " . $stmt->error, 1);
            }
            $stmt->close();

            $editSql = "INSERT INTO neonrequestedit
                (request_id, tableName, fieldName, oldValue, newValue, uid, editTimeStamp)
                VALUES (?, 'neoncollectionrequestlink', 'coll_id', ?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($editSql);
            foreach ($toAdd as $c) {
                $oldVal = '';
                $newVal = $c;
                $stmt->bind_param('issi', $this->request_id, $oldVal, $newVal, $uid);
                $stmt->execute();
            }
            $stmt->close();
        }

        if (!empty($toDelete)) {
            $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
            $sql = "DELETE FROM neoncollectionrequestlink 
                    WHERE request_id = ? AND coll_id IN ($placeholders)";
            $stmt = $this->conn->prepare($sql);
            $types = 'i' . str_repeat('i', count($toDelete));
            $params = [$this->request_id, ...$toDelete];
            $stmt->bind_param($types, ...$params);

            if (!$stmt->execute()) {
                $this->logOrEcho("ERROR deleting collids: " . $stmt->error, 1);
            }
            $stmt->close();

            $editSql = "INSERT INTO neonrequestedit
                (request_id, tableName, fieldName, oldValue, newValue, uid, editTimeStamp)
                VALUES (?, 'neoncollectionrequestlink', 'coll_id', ?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($editSql);
            foreach ($toDelete as $c) {
                $oldVal = $c;
                $newVal = '';
                $stmt->bind_param('issi', $this->request_id, $oldVal, $newVal, $uid);
                $stmt->execute();
            }
            $stmt->close();
        }

        return true;
    }


	//Identifier and occid functions
    protected function getSampPK($identifierArr) {
        $retArr = array();

        if ($this->importType == self::IMPORT_SAMPLE) {
            $sql = 'SELECT DISTINCT o.occid FROM omoccurrences o ';
            $sqlConditionArr = array();

            if (isset($identifierArr['occid'])) {
                $occid = $this->cleanInStr($identifierArr['occid']);
                $sqlConditionArr[] = '(o.occid = "' . $occid . '" OR o.recordID = "' . $occid . '")';
            }
            if (isset($identifierArr['occurrenceID'])) {
                $occurrenceID = $this->cleanInStr($identifierArr['occurrenceID']);
                $sqlConditionArr[] = '(o.occurrenceID = "' . $occurrenceID . '" OR o.recordID = "' . $occurrenceID . '")';
            }
            if (isset($identifierArr['catalogNumber'])) {
                $sqlConditionArr[] = '(o.catalogNumber = "' . $this->cleanInStr($identifierArr['catalogNumber']) . '")';
            }
            if (isset($identifierArr['otherCatalogNumbers'])) {
                $otherCatalogNumbers = $this->cleanInStr($identifierArr['otherCatalogNumbers']);
                $sqlConditionArr[] = '(o.othercatalognumbers = "' . $otherCatalogNumbers . '" OR i.identifierValue = "' . $otherCatalogNumbers . '")';
                $sql .= ' LEFT JOIN omoccuridentifiers i ON o.occid = i.occid ';
            }

            if ($sqlConditionArr) {
                $sql .= ' WHERE ' . implode(' OR ', $sqlConditionArr);
                $rs = $this->conn->query($sql);
                while ($r = $rs->fetch_object()) {
                    $retArr[] = $r->occid;
                }
                $rs->free();
            }
        } 
        elseif ($this->importType == self::IMPORT_MATERIAL_SAMPLE) {
            $sql = 'SELECT DISTINCT m.matSampleID FROM ommaterialsample m 
                    INNER JOIN neonsamplerequestlink s
                    ON m.occid = s.occid';
            $sqlConditionArr = array();

            if (isset($identifierArr['matsampleid'])) {
                $matSampleID = $this->cleanInStr($identifierArr['matsampleid']);
                $sqlConditionArr[] = '(m.matSampleID = "' . $matSampleID . '")';
            }
            if (isset($identifierArr['catalogNumber'])) {
                $sqlConditionArr[] = '(m.catalogNumber = "' . $this->cleanInStr($identifierArr['catalogNumber']) . '")';
            }

            if ($sqlConditionArr) {
                $sql .= ' WHERE ' . implode(' OR ', $sqlConditionArr);
                $rs = $this->conn->query($sql);
                while ($r = $rs->fetch_object()) {
                    $retArr[] = $r->matSampleID;
                }
                $rs->free();
            }
        }

        return $retArr;
    }


	//Mapping functions
    public function setTargetFieldArr() {
        $this->targetFieldMap = []; // reset
        if ($this->importType == self::IMPORT_SAMPLE) {
            $this->targetFieldMap['sample_catalognumber'] = 'subject identifier: catalogNumber';
            $this->targetFieldMap['othercatalognumbers'] = 'subject identifier: otherCatalogNumbers';
            $this->targetFieldMap['occurrenceid'] = 'subject identifier: occurrenceID';
            $this->targetFieldMap['occid'] = 'subject identifier: occid';
            $this->targetFieldMap[''] = '------------------------------------';
            $fieldArr = array('use_type', 'substance_provided','notes');
        } elseif ($this->importType == self::IMPORT_MATERIAL_SAMPLE) {
            $this->targetFieldMap['mat_catalognumber'] = 'subject identifier: catalogNumber';
            $this->targetFieldMap['matsampleid'] = 'subject identifier: matSampleID';
            $this->targetFieldMap[''] = '------------------------------------';
            $fieldArr = array('use_type','sampleType','notes');
        } else {
            $fieldArr = [];
        }

        sort($fieldArr);
        foreach ($fieldArr as $field) {
            $this->targetFieldMap[strtolower($field)] = $field;
        }
    }

	private function defineTranslationMap() {
		if ($this->translationMap === null) {
			if ($this->importType == self::IMPORT_SAMPLE) {
				$this->translationMap = array();
			} elseif ($this->importType == self::IMPORT_MATERIAL_SAMPLE) {
				$this->translationMap = array();
		    }
    	}
    }


	//Data set functions

    private function setRequestMetaArr() {
		$sql = 'SELECT r.id, p.name,r.title FROM neonrequest r
            LEFT JOIN neonresearcher p
            ON r.researcher_id = p.researcher_id
            WHERE r.id = ' . $this->request_id;
		$rs = $this->conn->query($sql);
		while ($r = $rs->fetch_object()) {
			$this->requestMetaArr['request_id'] = $r->id;
			$this->requestMetaArr['name'] = $r->name;
			$this->requestMetaArr['title'] = $r->title;
		}
		$rs->free();
	}

	//Basic setters and getters
    public function setRequestID($id) {
        $this->request_id = (int) $id;
    }

	public function getRequestID() {
		return $this->request_id;
	}

	public function getRequestMeta($field) {
		$fieldValue = '';
		if (!$this->requestMetaArr) $this->setRequestMetaArr();
		if (isset($this->requestMetaArr[$field])) return $this->requestMetaArr[$field];
		return $fieldValue;
	}

	public function setImportType($importType) {
		if (is_numeric($importType)) $this->importType = $importType;
		$this->defineTranslationMap();
	}
}
