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
        'eventDate' => "s",'eventDate2' => "s",'day'=> "i",'month'=> "i",'year' => "i",'startDayOfYear' => "i",'endDayOfYear' => "i",
        'eventID' => "s",'geodeticDatum' => "s",'habitat' => "s",'individualCount' => "s",
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

            if (isset($inputArr['eventDate']) || isset($inputArr['eventDate2'])) {
                self::updateDateFields($inputArr);
                foreach (['eventDate', 'eventDate2'] as $f) {
                    if (!isset($inputArr[$f]) || $inputArr[$f] === '') {
                        $inputArr[$f] = null;
                    }
                }
            }

            if (!empty($postArr['action']) && $postArr['action'] === 'add') {
                foreach ($inputArr as $field => $value) {
                    if (!isset($occurArr[$field]) || is_null($occurArr[$field]) || $occurArr[$field] == '') {
                        $sqlFrag .= $field . ' = ?, ';
                        $paramArr[] = $value;
                        $oldValues[$field] = $occurArr[$field] ?? null;
                        $newValues[$field] = $value;
                        if ($oldValues[$field] != $newValues[$field]) $fieldsToUpdate[] = $field;
                    }
                }
            } else {
                $this->setParameterArr($inputArr);
                foreach ($this->parameterArr as $field => $value) {
                    if ($field !== 'occid') {
                        $sqlFrag .= $field . ' = ?, ';
                        $paramArr[] = $value;
                        $oldValues[$field] = $occurArr[$field] ?? null;
                        $newValues[$field] = $value;
                        if ($oldValues[$field] != $newValues[$field]) $fieldsToUpdate[] = $field;
                    }
                }
            }

            if (!empty($fieldsToUpdate)) {
                echo '<div style="color:green;">Updating fields: ' . implode(', ', $fieldsToUpdate) . '</div>';
            } else {
                echo '<div style="color:orange;">No fields to update </div>';
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

private static function updateDateFields(array &$recMap): void {
    $dateStr = isset($recMap['eventDate']) ? trim($recMap['eventDate']) : '';
    $dateStr2 = isset($recMap['eventDate2']) ? trim($recMap['eventDate2']) : '';
    if ($dateStr === '') {
        $recMap['eventDate'] = null;
        $recMap['year'] = null;
        $recMap['month'] = null;
        $recMap['day'] = null;
        $recMap['startDayOfYear'] = null;
    } else {
        $formatted = OccurrenceUtil::formatDate($dateStr);
        if ($formatted) {
            $recMap['eventDate'] = $formatted;
            $date = date_create($formatted);
            $recMap['year'] = (int)$date->format('Y');
            $recMap['month'] = (int)$date->format('m');
            $recMap['day'] = (int)$date->format('d');
            $recMap['startDayOfYear'] = (int)$date->format('z') + 1;
        } else {
            echo "Invalid eventDate: $dateStr → NULL<br>";
            $recMap['eventDate'] = null;
            $recMap['year'] = null;
            $recMap['month'] = null;
            $recMap['day'] = null;
            $recMap['startDayOfYear'] = null;
        }
    }
    if ($dateStr2 === '') {
        $recMap['eventDate2'] = null;
        $recMap['endDayOfYear'] = null;
    } else {
        $formatted2 = OccurrenceUtil::formatDate($dateStr2);
        if ($formatted2) {
            $recMap['eventDate2'] = $formatted2;
            $date2 = date_create($formatted2);
            $recMap['endDayOfYear'] = (int)$date2->format('z') + 1;
        } else {
            echo "Invalid eventDate2: $dateStr2 → NULL<br>";
            $recMap['eventDate2'] = null;
            $recMap['endDayOfYear'] = null;
        }
    }
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
            $postField = null;

            foreach ($inputArr as $k => $v) {
                if (strcasecmp($k, $field) === 0) {
                    $postField = $k;
                    break;
                }
            }

            if ($postField === null) continue;

            $value = $inputArr[$postField];

            // Normalize string values
            if (is_string($value)) {
                $value = trim($value);
                if ($value === '') {
                    $value = null;
                }
            }

            $this->parameterArr[$field] = $value;
            $this->typeStr .= $type;
        }

        if (isset($inputArr['occid']) && $inputArr['occid'] && !$this->occid) {
            $this->occid = (int)$inputArr['occid'];
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
