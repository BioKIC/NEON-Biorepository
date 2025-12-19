<?php
include_once('UtilitiesFileImport.php');
include_once('OmMaterialSample.php');
include_once('OmAssociations.php');
include_once('OccurrenceMaintenance.php');
include_once('utilities/UuidFactory.php');

class SampleRequestImport extends UtilitiesFileImport {

	private $requestID;
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
        $stmt->bind_param('i', $this->requestID);
        $stmt->execute();
        $stmt->bind_result($status);
        $stmt->fetch();
        $stmt->close();
        return $status;
    }

    // load sample data
    public function loadData($postArr, $uid) {

        $imported = false;

        if (!$this->fileName || !isset($postArr['tf'])) {
            return false;
        }

        $this->fieldMap = array_flip($postArr['tf']);

        if (!$this->setTargetPath() || !$this->getHeaderArr()) {
            return false;
        }

        try {
            while ($recordArr = $this->getRecordArr()) {

                $identifierArr = [];

                if ($this->importType == self::IMPORT_SAMPLE) {
                    if (isset($this->fieldMap['occid']) && $recordArr[$this->fieldMap['occid']] !== '') {
                        $identifierArr['occid'] = $recordArr[$this->fieldMap['occid']];
                    }
                    if (isset($this->fieldMap['occurrenceID']) && $recordArr[$this->fieldMap['occurrenceID']] !== '') {
                        $identifierArr['occurrenceID'] = $recordArr[$this->fieldMap['occurrenceid']];
                    }
                    if (isset($this->fieldMap['catalogNumber']) && $recordArr[$this->fieldMap['catalogNumber']] !== '') {
                        $identifierArr['catalogNumber'] = $recordArr[$this->fieldMap['catalognumber']];
                    }
                    if (isset($this->fieldMap['otherCatalogNumber']) && $recordArr[$this->fieldMap['otherCatalogNumber']] !== '') {
                        $identifierArr['otherCatalogNumbers'] = $recordArr[$this->fieldMap['othercatalognumbers']];
                    }

                    $this->logOrEcho('Processing Sample: ' . implode(', ', $identifierArr));

                } elseif ($this->importType == self::IMPORT_MATERIAL_SAMPLE) {

                    if (isset($this->fieldMap['matsampleid']) && $recordArr[$this->fieldMap['matsampleid']] !== '') {
                        $identifierArr['matsampleid'] = $recordArr[$this->fieldMap['matsampleid']];
                    }
                    if (isset($this->fieldMap['catalogNumber']) && $recordArr[$this->fieldMap['catalogNumber']] !== '') {
                        $identifierArr['catalogNumber'] = $recordArr[$this->fieldMap['catalognumber']];
                    }

                    $this->logOrEcho('Processing Material Sample: ' . implode(', ', $identifierArr));
                }

                $sampArr = $this->getSampPK($identifierArr);
                if (!$sampArr) {
                    continue;
                }

                try {
                    // insert record
                    if ($this->insertRecord($recordArr, $sampArr, $postArr, $uid)) {
                        $imported = true;
                    }
                } catch (Exception $e) {
                    throw $e;
                }
            }
        } 
        finally {
            $this->deleteImportFile();
        }

        return $imported;
    }


    # insert samples/material sample request links
    private function insertRecord($recordArr, $sampArr, $postArr, $uid) {

        $allowedUseTypes = ['destructive', 'consumptive', 'invasive', 'non-destructive'];
        $requestStatus = $this->getRequestStatus();
        $defaultStatus = ($requestStatus === 'pending funding') ? 'pending funding' : 'pending fulfillment';


        if ($this->importType == self::IMPORT_SAMPLE) {
            $allowedSubstances = ['whole sample', 'subsample/aliquot', 'tissue/material sample', 'individual(s)', 'image', 'data'];
            $insertSql = "INSERT INTO neonsamplerequestlink 
                        (requestID, occid, status, available, useType, substanceProvided, notes, initialTimestamp,editedTimestamp)
                        VALUES (?, ?, ?, 'yes', ?, ?, ?, NOW(),NOW())";
            $checkDupeSql = "SELECT 1 FROM neonsamplerequestlink 
                        WHERE requestID = ? AND occid = ? LIMIT 1";
            $checkReqSql = "SELECT 1 FROM neonsamplerequestlink s
                        JOIN omoccurrences o
                        ON s.occid = o.occid
                        WHERE s.requestID != ? AND s.occid = ? AND s.status LIKE 'pending%' LIMIT 1";

            $checkAvailSql = "SELECT availability FROM omoccurrences
                        WHERE occid = ?";

            $insertStmt = $this->conn->prepare($insertSql);
            $checkDupeStmt  = $this->conn->prepare($checkDupeSql);
            $checkReqStmt  = $this->conn->prepare($checkReqSql);
            $checkAvailStmt = $this->conn->prepare($checkAvailSql);

            foreach ($sampArr as $occid) {
                $useType = $recordArr[$this->fieldMap['usetype']] ?? null;
                $substanceProvided = $recordArr[$this->fieldMap['substanceprovided']] ?? null;
                $notes = isset($this->fieldMap['notes']) ? $recordArr[$this->fieldMap['notes']] ?? null : null;

                if (!$useType || !$substanceProvided) {
                    throw new Exception("ERROR: Missing required fields (useType and/or sampleType) for occid $occid");
                }
                if (in_array($substanceProvided, ["individual(s)", "tissue/material sample", "subsample/aliquot"]) && !$notes) {
                    throw new Exception("ERROR: Notes required for occid $occid when substance is tissue/material sample, individual(s), or subsample/aliquot");                    
                }
                if (!in_array($useType, $allowedUseTypes)) {
                    throw new Exception("ERROR: Invalid useType '$useType' for occid $occid. Allowed: " . implode(', ', $allowedUseTypes));
                }
                if (!in_array($substanceProvided, $allowedSubstances)) {
                    throw new Exception("ERROR: Invalid substanceProvided '$substanceProvided' for occid $occid. Allowed: " . implode(', ', $allowedSubstances));
                }

                    // skip duplicates in this request
                $checkDupeStmt->bind_param('ii', $this->requestID, $occid);
                $checkDupeStmt->execute();

                if ($checkDupeStmt->get_result()->num_rows) {
                    $this->logOrEcho("Skipping occid $occid (already in request)", 1);
                    continue;
                }

                    // block samples associated with pending request         
                $checkReqStmt->bind_param('ii', $this->requestID, $occid);
                $checkReqStmt->execute();

                if ($checkReqStmt->get_result()->num_rows) {
                    throw new Exception("ERROR: occid $occid already linked to another pending request");
                }

                $checkAvailStmt->bind_param('i', $occid);
                $checkAvailStmt->execute();
                $availability = $checkAvailStmt->get_result()->fetch_row()[0] ?? 0;
                if (!$availability) {
                    throw new Exception("ERROR: occid $occid is unavailable according to occurrence record");
                }

                // insert record
                $insertStmt->bind_param('iissss', $this->requestID, $occid, $defaultStatus, $useType, $substanceProvided, $notes);

                if (!$insertStmt->execute()) {
                    throw new Exception("Insert failed for occid $occid: ".$insertStmt->error);
                }

                $this->logOrEcho('<strong style="color:green;">Sample Added: ' . (int)$occid . '</strong>', 1);

            }

            $insertStmt->close();
            $checkDupeStmt->close();
            $checkReqStmt->close();
            $checkAvailStmt->close();

            $this->syncCollidsForRequest($uid);
            $this->moreThan100($this->requestID);
            return true;
        }

        elseif ($this->importType == self::IMPORT_MATERIAL_SAMPLE) {
            $insertSql = "INSERT INTO neonmaterialsamplerequestlink 
                    (requestID, matSampleID, occid, status, useType, sampleType, notes, initialTimestamp,editedTimestamp)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $checkDupeSql = "SELECT 1 FROM neonmaterialsamplerequestlink 
                    WHERE requestID = ? AND matSampleID = ? LIMIT 1";
            $checkReqSql = "SELECT 1 FROM neonmaterialsamplerequestlink 
                    WHERE requestID != ? AND matSampleID = ? AND status LIKE 'pending%' LIMIT 1";

                $insertStmt = $this->conn->prepare($insertSql);
                $checkDupeStmt  = $this->conn->prepare($checkDupeSql);
                $checkReqStmt  = $this->conn->prepare($checkReqSql);

            if (!$insertStmt || !$checkDupeStmt || !$checkReqStmt) {
                throw new Exception("ERROR: preparing statement: " . $this->conn->error);
            }

            foreach ($sampArr as $matSampleID) {
                    $matSampleID = (int)$matSampleID;

                    $occidStmt = $this->conn->prepare(
                    "SELECT s.occid
                    FROM ommaterialsample s
                    INNER JOIN neonsamplerequestlink r ON s.occid = r.occid
                    WHERE r.requestID = ? AND s.matSampleID = ?"
                    );
                $occidStmt->bind_param('ii', $this->requestID, $matSampleID);
                $occidStmt->execute();
                $occidRes = $occidStmt->get_result();

                if ($occidRes && $occidRes->num_rows > 0) {
                    $row = $occidRes->fetch_assoc();
                    $occid = (int)$row['occid'];
                    $this->logOrEcho("Found occid '$occid' for matSampleID '$matSampleID'", 1);
                } else {
                     $occidStmt->close();
                    throw new Exception("ERROR: matSampleID '$matSampleID' cannot be imported — no linked occid found for requestID {$this->requestID}");
                }
                $occidStmt->close();

                $useType   = $recordArr[$this->fieldMap['usetype']] ?? null;
                $sampleType = $recordArr[$this->fieldMap['sampletype']] ?? null;
                $notes      = isset($this->fieldMap['notes']) ? $recordArr[$this->fieldMap['notes']] ?? null : null;

                if (!$useType || !$sampleType) {
                    throw new Exception("ERROR: Missing required fields (useType and/or sampleType) for matSampleID $matSampleID");
                }

                if (!in_array($useType, $allowedUseTypes)) {
                    throw new Exception("ERROR: Invalid useType '$useType' for matSampleID $matSampleID. Allowed: " . implode(', ', $allowedUseTypes));
                }

                // duplicate check
                $checkDupeStmt->bind_param('ii', $this->requestID, $matSampleID);
                $checkDupeStmt->execute();

                if ($checkDupeStmt->get_result()->num_rows) {
                    $this->logOrEcho("Skipping matSampleID $matSampleID: already linked to this request", 1);
                    continue;
                }

                    // in another request check
                $checkReqStmt->bind_param('ii', $this->requestID, $matSampleID);
                $checkReqStmt->execute();

                if ($checkReqStmt->get_result()->num_rows) {
                    throw new Exception("ERROR: matSampleID $matSampleID linked to another pending request");
                }

                    // insert
                $insertStmt->bind_param('iiissss', $this->requestID, $matSampleID, $occid, $defaultStatus, $useType, $sampleType, $notes);
                if ($insertStmt->execute()) {
                    $this->logOrEcho('<strong style="color:green;">Material sample Added: ' . (int)$matSampleID . '</strong>', 1);
                } else {
                    throw new Exception("ERROR inserting matSampleID $matSampleID: " . $insertStmt->error);
                }
                
            }

            $insertStmt->close();
            $checkDupeStmt->close();
            $checkReqStmt->close();

            $this->moreThan100($this->requestID);


        }

        return false;
     }

        // link collids to request
    private function syncCollidsForRequest($uid) {

        $sql = "SELECT DISTINCT o.collid
            FROM neonsamplerequestlink r
            JOIN omoccurrences o ON r.occid = o.occid
            WHERE r.requestID = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $this->requestID);
        $stmt->execute();
        $res = $stmt->get_result();

        $expectedCollids = [];
        while ($row = $res->fetch_assoc()) {
            $expectedCollids[] = (int)$row['collid'];
        }
        $stmt->close();

        $sql = "SELECT collID FROM neoncollectionrequestlink WHERE requestID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $this->requestID);
        $stmt->execute();
        $res = $stmt->get_result();

        $currentCollids = [];
        while ($row = $res->fetch_assoc()) {
            $currentCollids[] = (int)$row['collID'];
        }
        $stmt->close();

        $expectedSet = array_unique($expectedCollids);
        $currentSet  = array_unique($currentCollids);

        $toAdd    = array_diff($expectedSet, $currentSet);
        $toDelete = array_diff($currentSet, $expectedSet);

        if (!empty($toAdd)) {
            $insertSql = "INSERT INTO neoncollectionrequestlink (requestID, collID) VALUES ";
            $insertSql .= implode(',', array_fill(0, count($toAdd), '(?, ?)'));

            $stmt = $this->conn->prepare($insertSql);
            $types = str_repeat('i', count($toAdd) * 2);
            $values = [];
            foreach ($toAdd as $c) {
                $values[] = $this->requestID;
                $values[] = $c;
            }

            $stmt->bind_param($types, ...$values);
            if (!$stmt->execute()) {
                $this->logOrEcho("ERROR adding collids to request: " . $stmt->error, 1);
            }
            $stmt->close();

            $editSql = "INSERT INTO neonrequestedit
                (requestID, tableName, fieldName, oldValue, newValue, uid, editTimeStamp)
                VALUES (?, 'neoncollectionrequestlink', 'collID', ?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($editSql);
            foreach ($toAdd as $c) {
                $oldVal = '';
                $newVal = $c;
                $stmt->bind_param('issi', $this->requestID, $oldVal, $newVal, $uid);
                $stmt->execute();
            }
            $stmt->close();
        }

        if (!empty($toDelete)) {
            $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
            $sql = "DELETE FROM neoncollectionrequestlink 
                    WHERE requestID = ? AND collID IN ($placeholders)";
            $stmt = $this->conn->prepare($sql);
            $types = 'i' . str_repeat('i', count($toDelete));
            $params = [$this->requestID, ...$toDelete];
            $stmt->bind_param($types, ...$params);

            if (!$stmt->execute()) {
                throw new Exception("ERROR deleting collids from request: " . $stmt->error);
            }
            $stmt->close();

            $editSql = "INSERT INTO neonrequestedit
                (requestID, tableName, fieldName, oldValue, newValue, uid, editTimeStamp)
                VALUES (?, 'neoncollectionrequestlink', 'collID', ?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($editSql);
            foreach ($toDelete as $c) {
                $oldVal = $c;
                $newVal = '';
                $stmt->bind_param('issi', $this->requestID, $oldVal, $newVal, $uid);
                $stmt->execute();
            }
            $stmt->close();
        }

        return true;
    }

    // update 'moreThan100' value
    private function moreThan100($requestID) {

        $sampsql = "SELECT COUNT(*) FROM neonsamplerequestlink WHERE requestID = ?";
        $sampstmt = $this->conn->prepare($sampsql);
        $sampstmt->bind_param('i', $requestID);
        $sampstmt->execute();
        $sampstmt->bind_result($sampCount);
        $sampstmt->fetch();
        $sampstmt->close();

        $matsampsql = "SELECT COUNT(*) FROM neonmaterialsamplerequestlink WHERE requestID = ?";
        $matsampstmt = $this->conn->prepare($matsampsql);
        $matsampstmt->bind_param('i', $requestID);
        $matsampstmt->execute();
        $matsampstmt->bind_result($matSampCount);
        $matsampstmt->fetch();
        $matsampstmt->close();

        $moreThan100 = ($sampCount > 100 || $matSampCount > 100) ? 1 : 0;

        $updatesql = "UPDATE neonrequest SET moreThan100 = ? WHERE id = ?";
        $updatestmt = $this->conn->prepare($updatesql);
        $updatestmt->bind_param('ii', $moreThan100, $requestID);
        $updatestmt->execute();
        $updatestmt->close();
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
            $fieldArr = array('useType', 'substanceProvided','notes');
        } elseif ($this->importType == self::IMPORT_MATERIAL_SAMPLE) {
            $this->targetFieldMap['mat_catalognumber'] = 'subject identifier: catalogNumber';
            $this->targetFieldMap['matsampleid'] = 'subject identifier: matSampleID';
            $this->targetFieldMap[''] = '------------------------------------';
            $fieldArr = array('useType','sampleType','notes');
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
            ON r.researcherID = p.researcherID
            WHERE r.id = ' . $this->requestID;
		$rs = $this->conn->query($sql);
		while ($r = $rs->fetch_object()) {
			$this->requestMetaArr['requestID'] = $r->id;
			$this->requestMetaArr['name'] = $r->name;
			$this->requestMetaArr['title'] = $r->title;
		}
		$rs->free();
	}

	//Basic setters and getters
    public function setRequestID($id) {
        $this->requestID = (int) $id;
    }

	public function getRequestID() {
		return $this->requestID;
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
