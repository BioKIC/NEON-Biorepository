<?php
include_once($SERVER_ROOT . '/config/dbconnection.php');
include_once($SERVER_ROOT . '/classes/utilities/OccurrenceUtil.php');
include_once($SERVER_ROOT . '/classes/utilities/UuidFactory.php');

class OmGenetic{

	private $conn;
	private $connInherited = false;
	private $idoccurgenetic;
	private $occid;
	private $schemaMap = array();
	private $parameterArr = array();
	private $typeStr = '';
	private $errorMessage;

	function __construct($conn = null){
		if($conn){
			$this->conn = $conn;
			$this->connInherited = true;
		}
		else $this->conn = MySQLiConnectionFactory::getCon('write');
		$this->schemaMap = array('identifier' => 's', 'resourcename' => 's', 'title' => 's', 'locus' => 's', 'resourceurl' => 's','notes' => 's');
	}

	function __destruct(){
		if(!($this->conn === null) && !$this->connInherited) $this->conn->close();
	}

	public function getGenLinkArr(){
		$retArr = array();
		$sql = 'SELECT g.idoccurgenetic, g.'.implode(', g.', array_keys($this->schemaMap)).', g.initialTimestamp
			FROM omoccurgenetic g WHERE g.occid = '.$this->occid;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_assoc()){
				$retArr[$r['idoccurgenetic']] = $r;
			}
			$rs->free();
		}
		return $retArr;
	}


    public function insertGeneticLink($inputArr){
        $status = false;

        if ($this->occid && $this->conn) {
            $this->setParameterArr($inputArr);

            $identifier   = $this->parameterArr['identifier']   ?? null;
            $resourcename = $this->parameterArr['resourcename'] ?? null;

            $dupSql = 'SELECT idoccurgenetic 
                    FROM omoccurgenetic 
                    WHERE occid = ? AND identifier <=> ? AND resourcename <=> ? 
                    LIMIT 1';
            if ($stmt = $this->conn->prepare($dupSql)) {
                $stmt->bind_param('iss', $this->occid, $identifier, $resourcename);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $stmt->close();
                    $this->errorMessage = "Duplicate record ignored: (occid={$this->occid}, identifier={$identifier}, resourcename={$resourcename}) already exists.";
                    return false;
                }
                $stmt->close();
            }

            $sql = 'INSERT INTO omoccurgenetic(occid';
            $sqlValues = '?';
            $paramArr = array($this->occid);
            $this->typeStr = 'i';

            foreach ($this->parameterArr as $fieldName => $value) {
                $sql .= ', ' . $fieldName;
                $sqlValues .= ', ?';
                $paramArr[] = $value;
                $this->typeStr .= $this->schemaMap[$fieldName];
            }
            $sql .= ') VALUES(' . $sqlValues . ')';

            if ($stmt = $this->conn->prepare($sql)) {
                $stmt->bind_param($this->typeStr, ...$paramArr);

                try {
                    if ($stmt->execute()) {
                        if ($stmt->affected_rows || !$stmt->error) {
                            $this->idoccurgenetic = $stmt->insert_id;
                            $status = true;
                        } else {
                            $this->errorMessage = 'ERROR inserting genetic record (2): ' . $stmt->error;
                        }
                    } else {
                        $this->errorMessage = 'ERROR inserting genetic record (1): ' . $stmt->error;
                    }
                } catch (mysqli_sql_exception $e) {
                    if ($e->getCode() == 1062) {
                        $this->errorMessage = "Duplicate conflict: (occid={$this->occid}, resourceurl={$this->parameterArr['resourceurl']}) already exists.";
                    } else {
                        $this->errorMessage = 'Database error inserting genetic record: ' . $e->getMessage();
                    }
                }
                $stmt->close();
            } else {
                $this->errorMessage = 'ERROR preparing statement for genetic link insert: ' . $this->conn->error;
            }
        }
        return $status;
    }

    public function updateGeneticLink($inputArr){
        $status = false;
        $this->setParameterArr($inputArr);

        $identifier   = $this->parameterArr['identifier']   ?? null;
        $resourcename = $this->parameterArr['resourcename'] ?? null;

        if (!$identifier && !$resourcename) {
            $this->errorMessage = "Cannot update: 'identifier' and 'resourcename' required to locate record.";
            return false;
        }

        $dupSql = 'SELECT idoccurgenetic 
                FROM omoccurgenetic 
                WHERE occid = ? AND identifier <=> ? AND resourcename <=> ? 
                LIMIT 1';
        if ($stmt = $this->conn->prepare($dupSql)) {
            $stmt->bind_param('iss', $this->occid, $identifier, $resourcename);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($foundID);

            if ($stmt->num_rows === 0) {
                $stmt->close();
                $this->errorMessage = "No genetic link found for occid={$this->occid}, identifier={$identifier}, resourcename={$resourcename}.";
                return false;
            }

            $stmt->fetch();
            $this->idoccurgenetic = $foundID;
            $stmt->close();
        } else {
            $this->errorMessage = "Error preparing statement to locate genetic link: " . $this->conn->error;
            return false;
        }

        $paramArr = array();
        $sqlFrag = '';
        $typeStr = '';

        foreach ($this->parameterArr as $fieldName => $value) {
            if ($value === null || $value === '') {
                echo 'WARNING: Field '. $fieldName . ' is null or empty in upload and will not be updated.<br>';
                continue; // skip empty fields
            }
            $sqlFrag .= $fieldName . ' = ?, ';
            $paramArr[] = $value;
            $typeStr .= $this->schemaMap[$fieldName];
        }

        if (empty($paramArr)) {
            $this->errorMessage = "No fields to update: all uploaded fields are empty or null.";
            return false;
        }

        $paramArr[] = $this->idoccurgenetic;
        $typeStr .= 'i';

        $sql = 'UPDATE omoccurgenetic SET '.trim($sqlFrag, ', ').' WHERE idoccurgenetic = ?';

        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param($typeStr, ...$paramArr);

            try {
                if ($stmt->execute()) {
                    if ($stmt->affected_rows || !$stmt->error) {
                        $status = true;
                    } else {
                        $this->errorMessage = 'ERROR updating genetic link: ' . $stmt->error;
                    }
                } else {
                    $this->errorMessage = 'ERROR executing update statement: ' . $stmt->error;
                }
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() == 1062) {
                    $this->errorMessage = "Duplicate conflict: (occid={$this->occid}, resourceurl={$this->parameterArr['resourceurl']}) already exists.";
                } else {
                    $this->errorMessage = 'Database error updating genetic link: ' . $e->getMessage();
                }
            }

            $stmt->close();
        } else {
            $this->errorMessage = 'ERROR preparing statement for updating genetic link: '.$this->conn->error;
        }

        return $status;
    }

    public function insertAndPropagateGeneticLink($occid, $inputArr,$type) {
		$results = []; 
		$occidList = [];

        if ($type == 'derived'){
		    $sql = 'SELECT occid, occidAssociate 
				FROM omoccurassociations 
				WHERE (occid = ? OR occidAssociate = ?) 
				AND relationship = "derivedFromSameIndividual"';
        }
        if ($type == 'originating'){
		    $sql = 'SELECT occid, occidAssociate 
				FROM omoccurassociations 
				WHERE (occid = ? OR occidAssociate = ?) 
				AND relationship IN ("originatingSampleOf","subsampleOf")';
        }
        if ($type == 'both'){
		    $sql = 'SELECT occid, occidAssociate 
				FROM omoccurassociations 
				WHERE (occid = ? OR occidAssociate = ?) 
				AND relationship IN ("originatingSampleOf","subsampleOf","derivedFromSameIndividual")';
        }

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
			if ($this->insertGeneticLink($inputArr)) {
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

    public function updateAndPropagateGeneticLink($occid, $inputArr,$type) {
		$results = []; 
		$occidList = [];

        if ($type == 'derived'){
		    $sql = 'SELECT occid, occidAssociate 
				FROM omoccurassociations 
				WHERE (occid = ? OR occidAssociate = ?) 
				AND relationship = "derivedFromSameIndividual"';
        }
        if ($type == 'originating'){
		    $sql = 'SELECT occid, occidAssociate 
				FROM omoccurassociations 
				WHERE (occid = ? OR occidAssociate = ?) 
				AND relationship IN ("originatingSampleOf","subsampleOf")';
        }
        if ($type == 'both'){
		    $sql = 'SELECT occid, occidAssociate 
				FROM omoccurassociations 
				WHERE (occid = ? OR occidAssociate = ?) 
				AND relationship IN ("originatingSampleOf","subsampleOf","derivedFromSameIndividual")';
        }

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
			if ($this->updateGeneticLink($inputArr)) {
				$results[$assocOccid] = ['success' => true, 'message' => 'Updated successfully'];
			} else {
				$results[$assocOccid] = [
					'success' => false,
					'message' => $this->errorMessage ?: 'Unknown error during update'
				];
			}
		}

		return $results;
	}

	private function setParameterArr($inputArr){
        $this->parameterArr = [];
        $this->typeStr = '';
		foreach($this->schemaMap as $field => $type){
			$postField = '';
			if(isset($inputArr[$field])) $postField = $field;
			elseif(isset($inputArr[strtolower($field)])) $postField = strtolower($field);

			if($postField){
				$value = trim($inputArr[$postField]);
				if($value === '') $value = null;
				$this->parameterArr[$field] = $value;
				$this->typeStr .= $type;
			}
		}
		if(isset($inputArr['occid']) && $inputArr['occid'] && !$this->occid) $this->occid = $inputArr['occid'];
	}

	//Misc support functions
	public function cleanFormData(&$postArr){
		foreach($postArr as $k => $v){
			if(substr($k,0,3) == 'ms_') $postArr[$k] = htmlspecialchars($v, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
		}
	}

	//Setters and getters
	public function setGenLinkID($id){
		if(is_numeric($id)) $this->idoccurgenetic = $id;
	}

	public function setOccid($id){
		if(is_numeric($id)) $this->occid = $id;
	}

	public function getOccid(){
		return $this->occid;
	}

	public function getSchemaMap(){
		return $this->schemaMap;
	}

	public function getErrorMessage(){
		return $this->errorMessage;
	}
}
?>
