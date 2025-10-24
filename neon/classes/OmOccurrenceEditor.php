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

	public function getOccurrenceArr($occid) {
		$idomoccuridentifiers = null;
		$sql = 'SELECT idomoccuridentifiers FROM omoccuridentifiers WHERE id = ? AND identifierName = ?';
		if ($stmt = $this->conn->prepare($sql)) {
			$stmt->bind_param('is', $occid);
			$stmt->execute();
			$stmt->bind_result($idomoccuridentifiers);
			$stmt->fetch();
			$stmt->close();
		}
		return $idomoccuridentifiers;
	}

	public function updateOccurrence($inputArr) {
		$status = false;
		if ($this->occid && $this->conn) {
			$occidPlaceholder = null;
			$identifierNamePlaceholder = null;
			if (array_key_exists('occid', $inputArr)) {
				$occidPlaceholder = (int)$inputArr['occid'];
				unset($inputArr['occid']);
			}
			if (array_key_exists('identifierName', $inputArr)) {
				$identifierNamePlaceholder = $inputArr['identifierName'];
				unset($inputArr['identifierName']);
			}
			$paramArr = array();
			$paramArr[] = $GLOBALS['SYMB_UID'];
			$this->typeStr .= 'i';
			$this->setParameterArr($inputArr);
			$sqlFrag = '';
			foreach ($this->parameterArr as $fieldName => $value) {
				if ($fieldName !== 'occid' || $fieldName !== 'identifierName') {
					$sqlFrag .= $fieldName . ' = ?, ';
					if ($fieldName == 'modifiedUid' && empty($value)) {
						$value = $GLOBALS['SYMB_UID'];
					}
					$paramArr[] = $value;
				}
			}
			$paramArr[] = $occidPlaceholder;
			$paramArr[] = $identifierNamePlaceholder;
			$this->typeStr .= 'is';
			$sql = 'UPDATE IGNORE omoccuridentifiers SET modifiedTimestamp = now(), modifiedUid = ? , ' . trim($sqlFrag, ', ') . ' WHERE (occid = ? AND identifierName = ?)';
			if ($stmt = $this->conn->prepare($sql)) {
				$stmt->bind_param($this->typeStr, ...$paramArr);
				$stmt->execute();
				if ($stmt->affected_rows || !$stmt->error) $status = true;
				else $this->errorMessage = 'ERROR updating omoccurassociations record: ' . $stmt->error;
				$stmt->close();
				$this->typeStr = '';
			} else $this->errorMessage = 'ERROR preparing statement for updating omoccurassociations: ' . $this->conn->error;
		}
		return $status;
	}

	private function setParameterArr($inputArr) {
		foreach ($this->schemaMap as $field => $type) {
			$postField = '';
			if (isset($inputArr[$field])) $postField = $field;
			elseif (isset($inputArr[strtolower($field)])) $postField = strtolower($field);
			if ($postField) {
				$value = trim($inputArr[$postField]);
				if ($value) {
					$postField = strtolower($postField);
					if ($postField == 'modifiedTimestamp') $value = OccurrenceUtil::formatDate($value);
					if ($postField == 'modifieduid') $value = OccurrenceUtil::verifyUser($value, $this->conn);
					if ($postField == 'sortBy') { 
						if (!is_numeric($value)) $value = 10;
					}
				} else $value = null;
				$this->parameterArr[$field] = $value;
				$this->typeStr .= $type;
			}
		}
		if (isset($inputArr['occid']) && $inputArr['occid'] && !$this->occid) $this->occid = $inputArr['occid'];
	}

	//Setters and getters
	public function getSchemaMap() {
		return $this->schemaMap;
	}

	public function setOccid($id) {
		if (is_numeric($id)) $this->occid = $id;
	}

}
