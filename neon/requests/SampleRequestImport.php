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

	public function loadData($postArr) {
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
                                $inserted = $this->insertRecord($recordArr, $sampArr, $postArr);
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
                                $inserted = $this->insertRecord($recordArr, $sampArr, $postArr);
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
    private function insertRecord($recordArr, $sampArr, $postArr) {

        $status = false;
        $allowedUseTypes = ['destructive', 'consumptive', 'invasive', 'non-destructive'];

        if ($this->importType == self::IMPORT_SAMPLE) {
            $allowedSubstances = ['whole sample', 'subsample/aliquot', 'tissue/material sample', 'individual(s)', 'image', 'data'];

            $insertSql = "INSERT INTO neonsamplerequestlink 
                        (request_id, occid, status, available, use_type, substance_provided, notes)
                        VALUES (?, ?, 'pending fulfillment', 'yes', ?, ?, ?)";
            $checkSql = "SELECT COUNT(*) as cnt FROM neonsamplerequestlink 
                        WHERE request_id = ? AND occid = ?";

            $insertStmt = $this->conn->prepare($insertSql);
            $checkStmt  = $this->conn->prepare($checkSql);

            if ($insertStmt && $checkStmt) {
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

                    // duplicate check
                    $checkStmt->bind_param('ii', $this->request_id, $occid);
                    $checkStmt->execute();
                    $checkStmt->store_result();
                    $checkStmt->bind_result($count);
                    $checkStmt->fetch();
                    $checkStmt->free_result();

                    if ($count > 0) {
                        $this->logOrEcho("Skipping occid $occid: already exists for this request", 1);
                        continue;
                    }

                    // insert
                    $insertStmt->bind_param('iisss', $this->request_id, $occid, $use_type, $substance_provided, $notes);
                    if ($insertStmt->execute()) {
                        $status = true;
                        $this->logOrEcho("Sample Added: $occid", 1);
                    } else {
                        $this->logOrEcho("ERROR loading Sample: $occid - " . $insertStmt->error, 1);
                    }
                }

                $insertStmt->close();
                $checkStmt->close();
            } else {
                $this->logOrEcho("ERROR preparing statement: " . $this->conn->error, 1);
            }
        }

        elseif ($this->importType == self::IMPORT_MATERIAL_SAMPLE) {
            $insertSql = "INSERT INTO neonmaterialsamplerequestlink 
                        (request_id, matSampleID, occid, status, use_type, sampleType, notes)
                        VALUES (?, ?, ?, 'pending fulfillment', ?, ?, ?)";
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
                $insertStmt->bind_param('iiisss', $this->request_id, $matSampleID, $occid, $use_type, $sampleType, $notes);
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
