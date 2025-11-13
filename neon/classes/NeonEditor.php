<?php
include_once('UtilitiesFileImport.php');
include_once('ImageShared.php');
include_once('OmMaterialSample.php');
include_once('OmAssociations.php');
include_once('OmDeterminations.php');
include_once('OmIdentifiers.php');
include_once('OmOccurrenceEditor.php');
include_once('OmGenetic.php');
include_once('OccurrenceMaintenance.php');
include_once('Media.php');
include_once('utilities/UuidFactory.php');

class NeonEditor extends UtilitiesFileImport {

	private $importType;
	private $createNewRecord = false;

	private $importManager = null;

    private const IMPORT_ASSOCIATIONS = 1;
	private const IMPORT_DETERMINATIONS = 2;
	private const IMPORT_IMAGE_MAP = 3;
	private const IMPORT_MATERIAL_SAMPLE = 4;
	private const IMPORT_IDENTIFIERS = 5;
    private const UPDATE_OCCURRENCE = 6;
	private const IMPORT_GENETIC = 7;

	function __construct() {
		parent::__construct(null, 'write');
		$this->setVerboseMode(2);
		set_time_limit(2000);
	}

	function __destruct() {
		parent::__destruct();
	}

	public function loadData($postArr) {
		global $LANG;
		$status = false;
		if ($this->fileName && isset($postArr['tf'])) {
			$this->fieldMap = array_flip($postArr['tf']);
			if ($this->setTargetPath()) {
				if ($this->getHeaderArr()) {		// Advance past header row, set file handler, and define delimiter
					$cnt = 1;
					while ($recordArr = $this->getRecordArr()) {
						$identifierArr = array();
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
						$this->logOrEcho('#' . $cnt . ': ' . $LANG['PROCESSING_CATNUM'] . ': ' . implode(', ', $identifierArr));
						if ($occidArr = $this->getOccurrencePK($identifierArr)) {
							$status = $this->insertRecord($recordArr, $occidArr, $postArr);
						}
						$cnt++;
					}
					$occurMain = new OccurrenceMaintenance($this->conn);
					$this->logOrEcho($LANG['UPDATING_STATS'] . '...');
				}
				$this->deleteImportFile();
			}
		}
		return $status;
	}

	private function insertRecord($recordArr, $occidArr, $postArr) {
		global $LANG;
		$status = false;
		if ($this->importType == self::IMPORT_IMAGE_MAP) {
			$importManager = new ImageShared($this->conn);

			/* originalurl is a required field */
			if (!isset($this->fieldMap['originalurl']) || !$recordArr[$this->fieldMap['originalurl']]) {
				//$this->errorMessage = 'large url (originalUrl) is null (required)';
				$this->logOrEcho('ERROR `originalUrl` field mapping is required', 1);
				return false;
			}

			/* Media uploads must only be of one type */
			if (!isset($postArr['mediaUploadType']) || !$postArr['mediaUploadType']) {
				$this->logOrEcho('ERROR `mediaUploadType` is required', 1);
				return false;
			}

			$fields = [
				 //'tid',
				'thumbnailUrl',
				'url',
				'sourceUrl',
				'archiveUrl',
				'referenceUrl',
				'creator',
				'creatoruid',
				'caption',
				'owner',
				'anatomy',
				'notes',
				'format',
				'sourceIdentifier',
				'hashFunction',
				'hashValue',
				'mediaMD5',
				'copyright',
				'accessRights',
				'rights',
				'sortOccurrence'
			];

			foreach ($occidArr as $occid) {
				$data = [
					"occid" => $occid,
					"originalUrl" => $recordArr[$this->fieldMap['originalurl']],
					"mediaUploadType" => $postArr['mediaUploadType']
				];
				foreach($fields as $key) {
					$record_idx = $this->fieldMap[$key] ?? $this->fieldMap[strtolower($key)] ?? false;
					if($record_idx && $recordArr[$record_idx]) {
						$data[$key] = $this->encodeString($recordArr[$record_idx]);
					}
				}

				if (!isset($data['originalUrl']) && !$data['originalUrl']) {
					$this->logOrEcho('SKIPPING Record ' . $occid . ' missing `originalUrl` value');
				}

				// Will Not store files on the server unless StorageStrategy is provided which is desired for this use case
				try {
					Media::insert($data);
					if ($errors = Media::getErrors()) {
						$this->logOrEcho('ERROR: ' . array_pop($errors));
					} else {
						$this->logOrEcho($LANG['IMAGE_LOADED'] . ': <a href="' . $GLOBALS['CLIENT_ROOT'] . '/collections/editor/occurrenceeditor.php?occid=' . $occid . '" target="_blank">' . $occid . '</a>', 1);
						$status = true;
					}
				} catch (MediaException $th) {
					$message = $th->getMessage();

					$this->logOrEcho('ERROR: ' . $message);
					$this->logOrEcho("Ensure mapping links point directly at the media file", 1, 'div');
					if (strpos($message, ' text ')) {
						$this->logOrEcho("Linking webpages is supported via the sourceUrl field", 1, 'div');
					}
				} catch (Throwable $th) {
					$this->logOrEcho('ERROR: ' . $th->getMessage());
				}
			}
		} elseif ($this->importType == self::IMPORT_DETERMINATIONS) {
			$detManager = new OmDeterminations($this->conn);
			foreach ($occidArr as $occid) {
				$detManager->setOccid($occid);
				$fieldArr = array_keys($detManager->getSchemaMap());
				$detArr = array();
				foreach ($fieldArr as $field) {
					$fieldLower = strtolower($field);
					if (isset($this->fieldMap[$fieldLower]) && !empty($recordArr[$this->fieldMap[$fieldLower]])) $detArr[$field] = $this->encodeString($recordArr[$this->fieldMap[$fieldLower]]);
				}
				if (empty($detArr['sciname'])) {
					$this->logOrEcho('ERROR loading determination: Scientific name is empty.', 1);
					continue;
				}
				if (empty($detArr['identifiedBy'])) {
					$paramArr['identifiedBy'] = 'unknown';
				}
				if (empty($detArr['dateIdentified'])) {
					$paramArr['dateIdentified'] = 's.d.';
				}
				if ($postArr['associatedoccurrences'] == 1){
					$results = $detManager->insertAndPropagateDetermination($occid, $detArr);
						if ($results !== false) {
							foreach ($results as $id => $res) {
								if ($res['success']) {
									$this->logOrEcho('Determination added for associated occid: <a href="' . 
										$GLOBALS['CLIENT_ROOT'] . '/collections/editor/occurrenceeditor.php?occid=' . 
										$id . '" target="_blank">' . $id . '</a>', 1);
								} else {
									$this->logOrEcho('Error adding determination for occid ' . $id . ': ' . 
										htmlspecialchars($res['message']), 1);
								}
							}
							$status = true;
						} else {
							$this->logOrEcho('ERROR retrieving associated occurrences: ' . $detManager->getErrorMessage(), 1);
						}
				}
				else if ($detManager->insertDetermination($detArr)) {
					 $this->logOrEcho($LANG['DETERMINATION_ADDED'] . ': <a href="' . $GLOBALS['CLIENT_ROOT'] . '/collections/editor/occurrenceeditor.php?occid=' . $occid . '" target="_blank">' . $occid . '</a>', 1);
                    $status = true;
				} else {
					$this->logOrEcho('ERROR loading determination: ' . $detManager->getErrorMessage(), 1);
				}
			}
		} elseif ($this->importType == self::IMPORT_ASSOCIATIONS) {
			$importManager = new OmAssociations($this->conn);
			foreach ($occidArr as $occid) {
				$importManager->setOccid($occid);
				$fieldArr = array_keys($importManager->getSchemaMap());
				$fieldArr[] = 'object-occurrenceID';
				$fieldArr[] = 'object-catalogNumber';
				$assocArr = array();
				foreach ($fieldArr as $field) {
					$fieldLower = strtolower($field);
					if (isset($this->fieldMap[$fieldLower])) $assocArr[$field] = $this->encodeString($recordArr[$this->fieldMap[$fieldLower]]);
				}
				if ($assocArr) {
					if (!empty($postArr['associationType']) && !empty($postArr['relationship'])) {
						$assocArr['associationType'] = $postArr['associationType'];
						$assocArr['relationship'] = $postArr['relationship'];
						if (isset($postArr['subType']) && empty($assocArr['subType'])) $assocArr['subType'] = $postArr['subType'];
						if (!empty($postArr['replace'])) {
							$existingAssociation = null;
							if (!empty($assocArr['instanceID'])) {
								$existingAssociation = $importManager->getAssociationArr(array('associationType' => $assocArr['associationType'], 'recordID' => $assocArr['instanceID']));
								if ($existingAssociation) {
									//instanceID is recordID, thus don't add to instanceID
									unset($assocArr['instanceID']);
								}
								if (!$existingAssociation) {
									$existingAssociation = $importManager->getAssociationArr(array('associationType' => $assocArr['associationType'], 'instanceID' => $assocArr['instanceID']));
								}
							}
							if (!$existingAssociation && !empty($assocArr['resourceUrl'])) {
								$existingAssociation = $importManager->getAssociationArr(array('associationType' => $assocArr['associationType'], 'resourceUrl' => $assocArr['resourceUrl']));
							}
							if (!$existingAssociation && !empty($assocArr['objectID'])) {
								$existingAssociation = $importManager->getAssociationArr(array('associationType' => $assocArr['associationType'], 'objectID' => $assocArr['objectID']));
							}
							if ($existingAssociation) {
								if ($assocID = key($existingAssociation)) {
									$importManager->setAssocID($assocID);
									if ($assocArr['relationship'] == 'DELETE') {
										if ($importManager->deleteAssociation()) {
                                            $this->logOrEcho($LANG['ASSOC_DELETED'] . ': <a href="' . $GLOBALS['CLIENT_ROOT'] . '/collections/editor/occurrenceeditor.php?occid=' . $occid . '" target="_blank">' . $occid . '</a>', 1);

										} else {
											$this->logOrEcho($LANG['ERROR_DELETING'] . ': ' . $importManager->getErrorMessage(), 1);
										}
									} else {
										if ($importManager->updateAssociation($assocArr)) {
						                    $this->logOrEcho($LANG['ASSOC_UPDATED'] . ': <a href="' . $GLOBALS['CLIENT_ROOT'] . '/collections/editor/occurrenceeditor.php?occid=' . $occid . '" target="_blank">' . $occid . '</a>', 1);
                                            $status = true;
										} else {
											$this->logOrEcho($LANG['ERROR_UPDATING'] . ': ' . $importManager->getErrorMessage(), 1);
										}
									}
								}
							} else {
								$this->logOrEcho($LANG['TARGET_NOT_FOUND'], 1);
							}
						} elseif ($importManager->insertAssociation($assocArr)) {
							$this->logOrEcho($LANG['ASSOC_ADDED'] . ': <a href="' . $GLOBALS['CLIENT_ROOT'] . '/collections/editor/occurrenceeditor.php?occid=' . $occid . '" target="_blank">' . $occid . '</a>', 1);

                            $status = true;
						} else {
							$this->logOrEcho($LANG['ERROR_ADDING'] . ': ' . $importManager->getErrorMessage(), 1);
						}
					}
				}
			}
		} elseif ($this->importType == self::IMPORT_MATERIAL_SAMPLE) {
			$importManager = new OmMaterialSample($this->conn);
			foreach ($occidArr as $occid) {
				$importManager->setOccid($occid);
				$fieldArr = array_keys($importManager->getSchemaMap());
				$msArr = array();
				foreach ($fieldArr as $field) {
					$fieldLower = strtolower($field);
					if (isset($this->fieldMap[$fieldLower]) && !empty($recordArr[$this->fieldMap[$fieldLower]])) $msArr[$field] = $this->encodeString($recordArr[$this->fieldMap[$fieldLower]]);
				}
				if (isset($msArr['ms_catalogNumber']) && $msArr['ms_catalogNumber']) {
					$msArr['catalogNumber'] = $msArr['ms_catalogNumber'];
					unset($msArr['ms_catalogNumber']);
				}
				if ($importManager->insertMaterialSample($msArr)) {
					$this->logOrEcho($LANG['MAT_SAMPLE_ADDED'] . ': <a href="' . $GLOBALS['CLIENT_ROOT'] . '/collections/editor/occurrenceeditor.php?occid=' . $occid . '" target="_blank">' . $occid . '</a>', 1);
                    $status = true;
				} else {
					$this->logOrEcho('ERROR loading Material Sample: ' . $importManager->getErrorMessage(), 1);
				}
			}
		} elseif ($this->importType == self::IMPORT_IDENTIFIERS) {
			$importManager = new OmIdentifiers($this->conn);
			foreach ($occidArr as $occid) {
				$importManager->setOccid($occid);
				$fieldArr = array_keys($importManager->getSchemaMap());
				$identifierArr = array();
				foreach ($fieldArr as $field) {
					$fieldLower = strtolower($field);
					if ($fieldLower == 'occid') {
						$identifierArr[$field] = $occid;
					} else {
						if (isset($this->fieldMap[$fieldLower])) $identifierArr[$field] = $this->encodeString($recordArr[$this->fieldMap[$fieldLower]]);
					}
				}
				if (empty($identifierArr['occid'])) {
                
					$this->logOrEcho('ERROR loading identifier: occid could not be fetched from provided occurrence identifiers.', 1);
					continue;
				}
				if (empty($identifierArr['identifierValue'])) {
					$this->logOrEcho('ERROR loading identifier: identifierValue is empty.', 1);
					continue;
				}
				if (empty($identifierArr['identifierName'])) {
					$this->logOrEcho('ERROR loading identifier: identifierName is empty.', 1);
					continue;
				}
				if ($identifierArr) {
					$existingIdentifier = null;
					$existingIdentifier = $importManager->getIdentifier($occid, $identifierArr['identifierName']);
					if ($existingIdentifier) {
						$importManager->setIdentifierID($existingIdentifier);
						if ($postArr['action'] == 'delete') {
							$status = $importManager->deleteIdentifier();
							$this->logOrEcho($LANG['IDENTIFIER_DELETED'], 1);
						}
						if (!empty($postArr['replace-identifier'])) {
							$status = $importManager->updateIdentifier($identifierArr);
                            $this->logOrEcho($LANG['IDENTIFIER_UPDATED'] . ': <a href="' . $GLOBALS['CLIENT_ROOT'] . '/collections/editor/occurrenceeditor.php?occid=' . $occid . '" target="_blank">' . $occid . '</a>', 1);
						}
					}
					if (!$existingIdentifier) {
						$status = $importManager->insertIdentifier($identifierArr);
						$this->logOrEcho($LANG['IDENTIFIER_ADDED'] . ': <a href="' . $GLOBALS['CLIENT_ROOT'] . '/collections/editor/occurrenceeditor.php?occid=' . $occid . '" target="_blank">' . $occid . '</a>', 1);
					}
					if (!$status) {
						if ($existingIdentifier) {
							$this->logOrEcho('ERROR loading identifier: existing identifier detected. ' . $importManager->getErrorMessage(), 1);
						} else {
							$this->logOrEcho('ERROR loading identifier. ' . $importManager->getErrorMessage(), 1);
						}
					}
				}
			}
		}
        elseif ($this->importType == self::UPDATE_OCCURRENCE) {
            $importManager = new OmoccurrenceEditor($this->conn);
            foreach ($occidArr as $occid) {
                $importManager->setOccid($occid);
                $occurArr = $importManager->getOccurArr($occid);
                if (empty($occurArr) || empty($occurArr['occid'])) {
                    $this->logOrEcho('ERROR: occid could not be fetched from provided occurrence identifiers.', 1);
                    continue;
                }
                $inputArr = [];
                $fieldArr = array_keys($importManager->getSchemaMap());
                foreach ($fieldArr as $field) {
                    $fieldLower = strtolower($field);
                    if (isset($this->fieldMap[$fieldLower])) {
                        $srcIndex = $this->fieldMap[$fieldLower];
						$value = $recordArr[$srcIndex] ?? null;

						if ($value === '0' || $value === 0) {
							$inputArr[$field] = 0;
						} else {
							$inputArr[$field] = $this->encodeString($value);
						}                   
					}
                }
                $inputArr['occid'] = $occid;
                $status = $importManager->updateOccurrence($inputArr,$occurArr,$postArr);
                if ($status){
                    $this->logOrEcho('Occurrence Updated' . ': <a href="' . $GLOBALS['CLIENT_ROOT'] . '/collections/editor/occurrenceeditor.php?occid=' . $occid . '" target="_blank">' . $occid . '</a>', 1);
                }
                if (!$status) {
                    $this->logOrEcho('ERROR updating occurrence <a href="' . $GLOBALS['CLIENT_ROOT'] . '/collections/editor/occurrenceeditor.php?occid=' . $occid .'" target="_blank">' . $occid . '</a>'. $importManager->getErrorMessage(), 1);
                }
            }
        }
		elseif ($this->importType == self::IMPORT_GENETIC) {
			$importManager = new OmGenetic($this->conn);
			foreach ($occidArr as $occid) {
				$importManager->setOccid($occid);
				$fieldArr = array_keys($importManager->getSchemaMap());
				$genArr = array();

				foreach ($fieldArr as $field) {
					$fieldLower = strtolower($field);
					if (isset($this->fieldMap[$fieldLower])) {
						$value = $recordArr[$this->fieldMap[$fieldLower]] ?? null;
						$genArr[$field] = $this->encodeString($value);
					}
				}

				$propagateDerived = $postArr['propagatederived'] ?? 0;
				$propagateOriginating = $postArr['propagateoriginating'] ?? 0;

				if ($postArr['action'] == 'add') {
					if ($propagateDerived == 1 || $propagateOriginating == 1) {
						$type = 'both';
						if ($propagateDerived == 1 && !$propagateOriginating) $type = 'derived';
						if (!$propagateDerived && $propagateOriginating == 1) $type = 'originating';

						$results = $importManager->insertAndPropagateGeneticLink($occid, $genArr, $type);
						if ($results !== false) {
							foreach ($results as $id => $res) {
								if ($res['success']) {
									$this->logOrEcho('Genetic link added for associated occid: <a href="' .
										$GLOBALS['CLIENT_ROOT'] . '/collections/editor/occurrenceeditor.php?occid=' .
										$id . '" target="_blank">' . $id . '</a>', 1);
								} else {
									$this->logOrEcho('Error adding genetic link for occid ' . $id . ': ' .
										htmlspecialchars($res['message']), 1);
								}
							}
							$status = true;
						} else {
							$this->logOrEcho('ERROR retrieving associated occurrences: ' . $importManager->getErrorMessage(), 1);
						}
					} elseif ($importManager->insertGeneticLink($genArr)) {
						$this->logOrEcho('Genetic links loaded: <a href="' .
							$GLOBALS['CLIENT_ROOT'] . '/collections/editor/occurrenceeditor.php?occid=' . $occid .
							'" target="_blank">' . $occid . '</a>', 1);
						$status = true;
					} else {
						$this->logOrEcho('ERROR loading Genetic Link: ' . $importManager->getErrorMessage(), 1);
					}
				} elseif ($postArr['action'] == 'update') {
					if ($propagateDerived == 1 || $propagateOriginating == 1) {
						$type = 'both';
						if ($propagateDerived == 1 && !$propagateOriginating) $type = 'derived';
						if (!$propagateDerived && $propagateOriginating == 1) $type = 'originating';

						$results = $importManager->updateAndPropagateGeneticLink($occid, $genArr, $type);
						if ($results !== false) {
							foreach ($results as $id => $res) {
								if ($res['success']) {
									$this->logOrEcho('Genetic link updated for associated occid: <a href="' .
										$GLOBALS['CLIENT_ROOT'] . '/collections/editor/occurrenceeditor.php?occid=' .
										$id . '" target="_blank">' . $id . '</a>', 1);
								} else {
									$this->logOrEcho('Error updating genetic link for occid ' . $id . ': ' .
										htmlspecialchars($res['message']), 1);
								}
							}
							$status = true;
						} else {
							$this->logOrEcho('ERROR retrieving associated occurrences: ' . $importManager->getErrorMessage(), 1);
						}
					} elseif ($importManager->updateGeneticLink($genArr)) {
						$this->logOrEcho('Genetic links updated: <a href="' .
							$GLOBALS['CLIENT_ROOT'] . '/collections/editor/occurrenceeditor.php?occid=' . $occid .
							'" target="_blank">' . $occid . '</a>', 1);
						$status = true;
					} else {
						$this->logOrEcho('ERROR updating Genetic Link: ' . $importManager->getErrorMessage(), 1);
					}
				}

			}
		}

		return $status;
	}

	//Identifier and occid functions
	protected function getOccurrencePK($identifierArr) {
		$retArr = array();
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
			$sql .= 'LEFT JOIN omoccuridentifiers i ON o.occid = i.occid ';
		}
		if ($sqlConditionArr) {
			$sql .= 'WHERE (' . implode(' OR ', $sqlConditionArr) . ') ';
			$rs = $this->conn->query($sql);
			while ($r = $rs->fetch_object()) {
				$retArr[] = $r->occid;
			}
			$rs->free();
		}
        if ($retArr) {
            if (count($retArr) > 1) {
                $this->logOrEcho(
                    'ERROR: Identifier matches multiple occurrence records: ' . implode(', ', $retArr),
                    1
                );
                $retArr = array();

            }
        }
        else {
            $this->logOrEcho(
                'SKIPPED: Unable to find record matching any provided identifier(s): ' . implode(', ', $identifierArr),
                1
            );
        }
		return $retArr;
	}

	//Mapping functions
	public function setTargetFieldArr($associationType = null) {
		$this->targetFieldMap['catalognumber'] = 'subject identifier: catalogNumber';
		$this->targetFieldMap['othercatalognumbers'] = 'subject identifier: otherCatalogNumbers';
		$this->targetFieldMap['occurrenceid'] = 'subject identifier: occurrenceID';
		$this->targetFieldMap['occid'] = 'subject identifier: occid';
		$this->targetFieldMap[''] = '------------------------------------';
		$fieldArr = array();
		if ($this->importType == self::IMPORT_IMAGE_MAP) {
			$fieldArr = array(
				'url',
				'thumbnailUrl',
				'sourceUrl',
				'archiveUrl',
				'referenceUrl',
				'creator',
				'creatorUid',
				'caption',
				'owner',
				'anatomy',
				'notes',
				'format',
				'sourceIdentifier',
				'hashFunction',
				'hashValue',
				'mediaMD5',
				'copyright',
				'rights',
				'accessRights',
				'sortOccurrence'
			);

			$this->targetFieldMap['originalurl'] = 'originalUrl (required)';
		} elseif ($this->importType == self::IMPORT_ASSOCIATIONS) {
			$fieldArr = array('relationshipID', 'objectID', 'basisOfRecord', 'establishedDate', 'notes', 'accordingTo');
			if ($associationType == 'resource') {
				$fieldArr[] = 'resourceUrl';
			} elseif ($associationType == 'internalOccurrence') {
				$this->targetFieldMap['object-catalognumber'] = 'object identifier: catalogNumber';
				$this->targetFieldMap['object-occurrenceid'] = 'object identifier: occurrenceID';
				$this->targetFieldMap['occidassociate'] = 'object identifier: occid';
				$this->targetFieldMap['0'] = '------------------------------------';
			} elseif ($associationType == 'externalOccurrence') {
				$fieldArr[] = 'verbatimSciname';
				$fieldArr[] = 'resourceUrl';
			} elseif ($associationType == 'observational') {
				$fieldArr[] = 'verbatimSciname';
			}
		} elseif ($this->importType == self::IMPORT_DETERMINATIONS) {
			$detManager = new OmDeterminations($this->conn);
			$schemaMap = $detManager->getSchemaMap();
			unset($schemaMap['appliedStatus']);
			unset($schemaMap['detType']);
			$fieldArr = array_keys($schemaMap);
		} elseif ($this->importType == self::IMPORT_MATERIAL_SAMPLE) {
			$fieldArr = array(
				'sampleType',
				'ms_catalogNumber',
				'guid',
				'sampleCondition',
				'disposition',
				'preservationType',
				'preparationDetails',
				'preparationDate',
				'preparedByUid',
				'individualCount',
				'sampleSize',
				'storageLocation',
				'remarks'
			);
		} elseif ($this->importType == self::IMPORT_IDENTIFIERS) {
			$fieldArr = array(
				// 'occid',
				'identifierValue',
				'identifierName',
				// 'format',
				// 'notes',
				// 'sortBy',
			);
		}
        elseif ($this->importType == self::UPDATE_OCCURRENCE) {
			$detManager = new OmOccurrenceEditor($this->conn);
			$schemaMap = $detManager->getSchemaMap();
			$fieldArr = array_keys($schemaMap);
		}
		elseif ($this->importType == self::IMPORT_GENETIC) {
			$fieldArr = array(
				'occid',
				'identifier',
				'resourcename',
				'title',
				'locus',
				'resourceurl',
				'notes'
			);
		}
		sort($fieldArr);
		foreach ($fieldArr as $field) {
			$this->targetFieldMap[strtolower($field)] = $field;
		}
	}

	private function defineTranslationMap() {
		if ($this->translationMap === null) {
			if ($this->importType == self::IMPORT_IMAGE_MAP) {
				$this->translationMap = array(
					'web' => 'url',
					'webviewoptional' => 'url',
					'thumbnail' => 'thumbnailurl',
					'thumbnailoptional' => 'thumbnailurl',
					'largejpg' => 'originalurl',
					'large' => 'originalurl',
					'imageurl' => 'url',
					'accessuri' => 'url'
				);
			} elseif ($this->importType == self::IMPORT_ASSOCIATIONS) {
				$this->translationMap = array();
			} elseif ($this->importType == self::IMPORT_DETERMINATIONS) {
				$this->translationMap = array('identificationid' => 'sourceIdentifier');
			} elseif ($this->importType == self::IMPORT_MATERIAL_SAMPLE) {
				$this->translationMap = array();
			} elseif ($this->importType == self::IMPORT_IDENTIFIERS) {
				$this->translationMap = array();
			} elseif ($this->importType == self::UPDATE_OCCURRENCE) {
				$this->translationMap = array();
			}
			elseif ($this->importType == self::IMPORT_GENETIC) {
				$this->translationMap = array();
			}
		}
	}

	//Data set functions


	public function getControlledVocabulary($tableName, $fieldName, $filterVariable = '') {
		$retArr = array();
		$sql = 'SELECT t.term, t.termDisplay
			FROM ctcontrolvocab v INNER JOIN ctcontrolvocabterm t ON v.cvID = t.cvID
			WHERE tableName = ? AND fieldName = ? AND filterVariable = ?';
		if ($stmt = $this->conn->prepare($sql)) {
			$stmt->bind_param('sss', $tableName, $fieldName, $filterVariable);
			$stmt->execute();
			$term = '';
			$termDisplay = '';
			$stmt->bind_result($term, $termDisplay);
			while ($stmt->fetch()) {
				if (!$termDisplay) $termDisplay = $term;
				$retArr[$term] = $termDisplay;
			}
			$stmt->close();
		}
		asort($retArr);
		return $retArr;
	}

	//Basic setters and getters

	public function setCreateNewRecord($b) {
		if ($b) $this->createNewRecord = true;
		else $this->createNewRecord = false;
	}

	public function setImportType($importType) {
		if (is_numeric($importType)) $this->importType = $importType;
		$this->defineTranslationMap();
	}

}
