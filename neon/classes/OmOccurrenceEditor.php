<?php
include_once('Manager.php');
include_once('utilities/OccurrenceUtil.php');
include_once('utilities/UuidFactory.php');

class OmoccurrenceEditor extends Manager {

	private $occid = null;
	private $schemaMap = array();
	private $parameterArr = array();
	private $typeStr = '';

	public function __construct($conn) {
		parent::__construct(null, 'write', $conn);
		$this->schemaMap = array('associatedCollectors' => "s",'availability' => "I",'coordinateUncertaintyInMeters' => "s",
        'county' => "s",'decimalLatitude' => "s",'decimalLongitude' => "s",'disposition' => "s",'dynamicProperties' => "s",
        'eventDate' => "s",'eventDate2' => "s",'eventID' => "s",'geodeticDatum' => "s",'habitat' => "s",'individualCount' => "s",
        'lifeStage' => "s",'locality' => "s",'locationID' => "s",'maximumDepthInMeters' => "s",'maximumElevationInMeters' => "s",
        'minimumDepthInMeters' => "s",'minimumElevationInMeters' => "s",'occurrenceRemarks' => "s",'preparations' => "s",
        'recordedBy' => "s",'reproductiveCondition' => "s",'samplingProtocol' => "s",'sex' => "s",'stateProvince' => "s",
        'verbatimDepth' => "s",'verbatimElevation' => "s");
	}

	public function __destruct() {
		parent::__destruct();
	}

    public function getOccurArr($occid) {
        $occurArr = null;
        $sql = 'SELECT * FROM omoccurrences WHERE occid = ?';
        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param('i', $occid);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $row = $result->fetch_assoc()) {
                $occurArr = $row;
            }
            $stmt->close();
        }
        return $occurArr;
    }


    public function updateOccurrence($inputArr, $occurArr, $postArr) {
        $status = false;

        if ($this->occid && $this->conn) {
            $occidPlaceholder = null;
            if (array_key_exists('occid', $inputArr)) {
                $occidPlaceholder = (int)$inputArr['occid'];
                unset($inputArr['occid']);
            }

            $paramArr = array();
            $sqlFrag = '';
            $fieldsToUpdate = [];
            $oldValues = [];
            $newValues = [];

            if (!empty($postArr['action']) && $postArr['action'] === 'add') {
                foreach ($inputArr as $field => $value) {
                    if (!isset($occurArr[$field]) || is_null($occurArr[$field])) {
                        $sqlFrag .= $field . ' = ?, ';
                        $paramArr[] = $value;
                        $fieldsToUpdate[] = $field;
                        $oldValues[$field] = $occurArr[$field] ?? null;
                        $newValues[$field] = $value;
                    }
                }
            } else {
                $this->setParameterArr($inputArr);
                foreach ($this->parameterArr as $field => $value) {
                    if ($field !== 'occid') {
                        $sqlFrag .= $field . ' = ?, ';
                        $paramArr[] = $value;
                        $fieldsToUpdate[] = $field;
                        $oldValues[$field] = $occurArr[$field] ?? null;
                        $newValues[$field] = $value;
                    }
                }
            }

            if (!empty($fieldsToUpdate)) {
                echo '<div style="color:green;">Updating fields: ' . implode(', ', $fieldsToUpdate) . '</div>';
            } else {
                echo '<div style="color:orange;">No fields to update for occid </div>';
                return false; 
            }

            $sql = 'UPDATE omoccurrences SET dateLastModified = now(), ' . trim($sqlFrag, ', ') . ' WHERE (occid = ?)';

            $paramArr[] = $occidPlaceholder;
            $oldValues['occid'] = $occidPlaceholder;
            $newValues['occid'] = $occidPlaceholder;

            $typeStr = '';
            foreach ($paramArr as $val) {
                $typeStr .= is_int($val) ? 'i' : 's';
            }

            if ($stmt = $this->conn->prepare($sql)) {
                $stmt->bind_param($typeStr, ...$paramArr);
                $stmt->execute();
                if ($stmt->affected_rows || !$stmt->error) {
                    $status = true;
                    if (array_key_exists('allow-overwrite',$postArr) && $postArr['allow-overwrite'] == 1) $user = 50;
                    else ($user = $GLOBALS['SYMB_UID']);
                    $this->recordEdits($oldValues,$newValues,$user);
                }
                else $this->errorMessage = 'ERROR updating occurrence record: ' . $stmt->error;
                $stmt->close();
            } else {
                $this->errorMessage = 'ERROR preparing statement for updating omoccurrences: ' . $this->conn->error;
            }
        }

        return $status;
    }


    public function recordEdits($oldValues, $newValues, $user) {

        $occid = (int)$newValues['occid'];
        foreach ($newValues as $field => $newVal) {
            if ($field === 'occid') continue;

            $oldVal = $oldValues[$field] ?? null;
            if (is_null($oldVal)) $oldVal = '';
            if (is_null($newVal)) $newVal = '';

            if ($oldVal !== $newVal) {
                $sql = 'INSERT INTO omoccuredits (
                            occid,
                            fieldName,
                            fieldValueNew,
                            fieldValueOld,
                            reviewStatus,
                            appliedStatus,
                            editType,
                            uid,
                            initialTimestamp
                        ) VALUES (?, ?, ?, ?, 1, 1, 1, ?, NOW())';
                if ($stmt = $this->conn->prepare($sql)) {
                    $stmt->bind_param('isssi', $occid, $field, $newVal, $oldVal, $user);
                   if(!$stmt->execute()){
                        echo "<div style='color:red;'>ERROR inserting edit for field '$field': " . $stmt->error . '</div>';
                    }
                    $stmt->close();
                } else {
                    echo "<div style='color:red;'>ERROR updaing field '$field': " . $this->conn->error . '</div>';
                }
            }
        }
        return true;
    }


    private function setParameterArr($inputArr) {
        $inputArr = OccurrenceUtil::occurrenceArrayCleaning($inputArr);

        foreach ($this->schemaMap as $field => $type) {
            $postField = '';
            if (isset($inputArr[$field])) {
                $postField = $field;
            } elseif (isset($inputArr[strtolower($field)])) {
                $postField = strtolower($field);
            }

            if ($postField) {
                $value = trim($inputArr[$postField]);
                if ($value !== '') {
                    $postFieldLower = strtolower($postField);
                    if (in_array($postFieldLower, ['eventdate', 'eventdate2'])) {
                        $value = OccurrenceUtil::formatDate($value);
                    }
                } else {
                    $value = null;
                }

                $this->parameterArr[$field] = $value;
                $this->typeStr .= $type;
            }
        }
        if (isset($inputArr['occid']) && $inputArr['occid'] && !$this->occid) {
            $this->occid = $inputArr['occid'];
        }
    }



	//Setters and getters
	public function getSchemaMap() {
		return $this->schemaMap;
	}

	public function setOccid($id) {
		if (is_numeric($id)) $this->occid = $id;
	}

}
