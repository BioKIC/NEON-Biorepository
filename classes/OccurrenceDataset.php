<?php
include_once($SERVER_ROOT . '/config/dbconnection.php');
include_once($SERVER_ROOT . '/classes/DwcArchiverCore.php');
include_once($SERVER_ROOT . '/classes/utilities/OccurrenceUtil.php');

class OccurrenceDataset{

	private $conn;
	private $collArr = array();
	private $datasetId = 0;
	private $errorArr = array();
	private $occurrenceCount = null;

	public function __construct($type = 'write') {
		$this->conn = MySQLiConnectionFactory::getCon($type);
	}

	public function __destruct() {
		if (!($this->conn === null)) $this->conn->close();
	}

	public function getPublicDatasets() {
		$retArr = array();
		$sql = 'SELECT datasetid, category, IFNULL(datasetName, name) as name, notes, description, uid, sortsequence, initialtimestamp, ispublic FROM omoccurdatasets WHERE ispublic=1 ORDER BY category,name';
		$rs = $this->conn->query($sql);
		while ($r = $rs->fetch_assoc()) {
			$retArr[] = $r;
		}
		$rs->free();
		return $retArr;
	}

	public function getPublicDatasetMetadata($dsid) {
		$retArr = array();
		if ($dsid) {
			//Get and return individual dataset

			//NEON edit start, remove isPublic requirement since this is managed in the list
			//$sql = 'SELECT datasetid, IFNULL(datasetName, name) as datasetName, notes, description, uid, sortsequence, initialtimestamp FROM omoccurdatasets WHERE (datasetid = ' . $dsid . ') AND ispublic=1';
			$sql = 'SELECT datasetid, IFNULL(datasetName, name) as datasetName, notes, description, uid, sortsequence, initialtimestamp FROM omoccurdatasets WHERE (datasetid = ' . $dsid . ')';
			//NEON edit end

			$rs = $this->conn->query($sql);
			while ($r = $rs->fetch_object()) {
				$retArr['name'] = $r->datasetName;
				$retArr['notes'] = $r->notes;
				$retArr['description'] = $r->description;
				$retArr['bibliographicCitation'] = $r->bibliographicCitation;
				$retArr['uid'] = $r->uid;
				$retArr['dynamicproperties'] = $r->dynamicProperties;
				$retArr['sort'] = $r->sortsequence;
				$retArr['ts'] = $r->initialtimestamp;
			}
			$rs->free();

			//NEON edit start - Get sample types
			$retArr['sampleTypes'] = array();
			$sql = 'SELECT c.collid, c.publicName, COUNT(*) AS sampleCount FROM omoccurdatasetlink odl INNER JOIN omoccurrences o ON odl.occid = o.occid INNER JOIN omcollections c ON o.collid = c.collid WHERE odl.datasetid = ' . $dsid . ' GROUP BY c.collid, c.publicName ORDER BY c.publicName';
			$rs = $this->conn->query($sql);
			while ($r = $rs->fetch_object()) {
				$retArr['sampleTypes'][] = array(
					'sampleType' => array(
						'value' => $r->publicName,
						'link' => 'https://biorepo.neonscience.org/portal/collections/misc/neoncollprofiles.php?collid=' . $r->collid
					),
					'sampleCount' => (int)$r->sampleCount
				);
			}
			$rs->free();
			//Get sites
			$retArr['sites'] = array();
			$sql = 'SELECT DISTINCT site.name FROM omoccurdatasetlink odl INNER JOIN omoccurdatasetlink siteLink ON odl.occid = siteLink.occid INNER JOIN omoccurdatasets site ON siteLink.datasetid = site.datasetid WHERE odl.datasetid = ' . $dsid . ' AND site.notes = "NEON Site" ORDER BY site.name';
			$rs = $this->conn->query($sql);
			while ($r = $rs->fetch_object()) {
				$retArr['sites'][] = $r->name;
			}
			$rs->free();
			//NEON edit end
		}
		return $retArr;
	}

	public function getDatasetMetadata($dsid) {
		$retArr = array();
		$dsid = (int)$dsid;
		if ($GLOBALS['SYMB_UID'] && $dsid) {
			//Get and return individual dataset
			// NEON edit start - to include dynamicProperties
			//$sql = 'SELECT datasetid, IFNULL(datasetName, name) as datasetName, notes, description, uid, sortsequence, initialtimestamp, ispublic FROM omoccurdatasets WHERE (datasetid = ' . $dsid . ') ';
			$sql = 'SELECT datasetid, IFNULL(datasetName, name) as datasetName, notes, description, category, bibliographicCitation, uid, sortsequence, initialtimestamp, ispublic, dynamicProperties FROM omoccurdatasets WHERE (datasetid = ' . $dsid . ') ';
			//NEON edit end
			$rs = $this->conn->query($sql);
			while ($r = $rs->fetch_object()) {
				$retArr['name'] = $r->datasetName;
				$retArr['notes'] = $r->notes;
				$retArr['description'] = $r->description;
				// NEON edit start
				$retArr['category'] = $r->category;
				$retArr['bibliographicCitation'] = $r->bibliographicCitation;
				$retArr['dynamicProperties'] = $r->dynamicProperties;
				//NEON edit end
				$retArr['uid'] = $r->uid;
				$retArr['sort'] = $r->sortsequence;
				$retArr['ts'] = $r->initialtimestamp;
				$retArr['ispublic'] = $r->ispublic;
			}
			$rs->free();
			//Get roles for current user
			$sql1 = 'SELECT role FROM userroles WHERE (tablename = "omoccurdatasets") AND (tablepk = ' . $dsid . ') AND (uid = ' . $GLOBALS['SYMB_UID'] . ') ';
			$rs1 = $this->conn->query($sql1);
			while ($r1 = $rs1->fetch_object()) {
				$retArr['roles'][] = $r1->role;
			}
			$rs1->free();
		}
		return $retArr;
	}

	//NEON edit start - neon specific functions
	public function getRequestInquiryMetadata($dsid) {
		$retArr = array();
		if ($dsid) {
			$sql = 'SELECT nr.id, r.name, r.institution, nr.activeDate, nr.completeDate, nr.sampleUseAgreementLink FROM neonrequest nr LEFT JOIN neonresearcher r ON nr.researcherID = r.researcherID WHERE nr.datasetID = ' . $dsid;
			$rs = $this->conn->query($sql);
			while ($r = $rs->fetch_object()) {
				$retArr['id'] = $r->id;
				$retArr['name'] = $r->name;
				$retArr['institution'] = $r->institution;
				$retArr['activeDate'] = $r->activeDate;
				$retArr['completeDate'] = $r->completeDate;
				$retArr['completeDate'] = $r->completeDate !== null ? $r->completeDate : 'In Progress';
				$retArr['sampleUseAgreementLink'] = $r->sampleUseAgreementLink;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getPublicProjects() {
		$retArr = array();

		$sql = "SELECT od.datasetid, od.name, od.notes, od.description, r.name AS researcherName, r.institution, nr.activeDate, nr.completeDate FROM omoccurdatasets od LEFT JOIN neonrequest nr ON od.datasetid = nr.datasetID LEFT JOIN neonresearcher r ON nr.researcherID = r.researcherID WHERE od.ispublic = 1 AND od.category = 'Request' AND nr.activeDate IS NOT NULL ORDER BY od.name";
		$rs = $this->conn->query($sql);

		$datasetIds = array();

		while ($r = $rs->fetch_object()) {
			$datasetIds[] = (int)$r->datasetid;

			$retArr[$r->datasetid] = array(
				'datasetid' => (int)$r->datasetid,
				'name' => $r->name,
				'notes' => $r->notes,
				'description' => $r->description,
				'researcherName' => $r->researcherName,
				'institution' => $r->institution,
				'activeDate' => $r->activeDate,
				'completeDate' => $r->completeDate,
				'sampleTypes' => array(),
				'sites' => array()
			);
		}

		$rs->free();

		if ($datasetIds) {
			$idStr = implode(',', $datasetIds);

			$sql = 'SELECT odl.datasetid, c.collid, c.publicName, COUNT(*) AS sampleCount FROM omoccurdatasetlink odl INNER JOIN omoccurrences o ON odl.occid = o.occid INNER JOIN omcollections c ON o.collid = c.collid WHERE odl.datasetid IN (' . $idStr . ') GROUP BY odl.datasetid, c.collid, c.publicName ORDER BY c.publicName';
			$rs = $this->conn->query($sql);

			while ($r = $rs->fetch_object()) {
				$retArr[$r->datasetid]['sampleTypes'][] = array(
					'sampleType' => array(
						'value' => $r->publicName,
						'link' => 'https://biorepo.neonscience.org/portal/collections/misc/neoncollprofiles.php?collid=' . $r->collid
					),
					'sampleCount' => (int)$r->sampleCount
				);
			}

			$rs->free();

			$sql = 'SELECT DISTINCT odl.datasetid, site.name FROM omoccurdatasetlink odl INNER JOIN omoccurdatasetlink siteLink ON odl.occid = siteLink.occid INNER JOIN omoccurdatasets site ON siteLink.datasetid = site.datasetid WHERE odl.datasetid IN (' . $idStr . ') AND site.notes = "NEON Site" ORDER BY site.name';
			$rs = $this->conn->query($sql);

			while ($r = $rs->fetch_object()) {
				$retArr[$r->datasetid]['sites'][] = $r->name;
			}

			$rs->free();
		}

		return array_values($retArr);
	}

	public function getAssociatedDatasets($dsid) {
		$retArr = array();
		$dsid = (int)$dsid;
		$sql = "SELECT DISTINCT a.datasetID AS assocDatasetID, d.name
			FROM omoccurdatasetassociations a
			INNER JOIN omoccurdatasets d
			ON a.datasetID = d.datasetID
			WHERE a.associatedDatasetID = $dsid
			AND a.datasetID <> $dsid

			UNION

			SELECT DISTINCT a.associatedDatasetID AS assocDatasetID, d.name
			FROM omoccurdatasetassociations a
			INNER JOIN omoccurdatasets d
			ON a.associatedDatasetID = d.datasetID
			WHERE a.datasetID = $dsid
			AND a.associatedDatasetID <> $dsid";

		$rs = $this->conn->query($sql);

		while ($r = $rs->fetch_object()) {
			$retArr[] = array(
					'datasetID' => $r->assocDatasetID,
					'name' => $r->name
			);
		}

		$rs->free();

		return $retArr;
	}
	//NEON edit end

	public function getDatasetArr() {
		$retArr = array();
		if ($GLOBALS['SYMB_UID']) {
			$sql = 'SELECT datasetid, IFNULL(datasetName, name) as datasetName, notes, description, sortsequence, initialtimestamp, ispublic FROM omoccurdatasets WHERE (uid = ' . $GLOBALS['SYMB_UID'] . ') ORDER BY sortsequence,name';
			$rs = $this->conn->query($sql);
			while ($r = $rs->fetch_object()) {
				$retArr['owner'][$r->datasetid]['name'] = $r->datasetName;
				$retArr['owner'][$r->datasetid]['notes'] = $r->notes;
				$retArr['owner'][$r->datasetid]['description'] = $r->description;
				$retArr['owner'][$r->datasetid]['sort'] = $r->sortsequence;
				$retArr['owner'][$r->datasetid]['ts'] = $r->initialtimestamp;
				$retArr['owner'][$r->datasetid]['ispublic'] = $r->ispublic;
			}
			$rs->free();

			//Get shared datasets
			$sql1 = 'SELECT d.datasetid, IFNULL(d.datasetName, d.name) as datasetName, d.notes, d.description, d.sortsequence, d.ispublic, d.initialtimestamp, r.role ' .
				'FROM omoccurdatasets d INNER JOIN userroles r ON d.datasetid = r.tablepk ' .
				'WHERE (r.uid = ' . $GLOBALS['SYMB_UID'] . ') AND (r.role IN("DatasetAdmin","DatasetEditor","DatasetReader")) ' .
				'ORDER BY sortsequence,name';
			//echo $sql1;
			$rs1 = $this->conn->query($sql1);
			while ($r1 = $rs1->fetch_object()) {
				$retArr['other'][$r1->datasetid]['name'] = $r1->datasetName;
				$retArr['other'][$r1->datasetid]['role'] = $r1->role;
				$retArr['other'][$r1->datasetid]['notes'] = $r1->notes;
				$retArr['other'][$r1->datasetid]['description'] = $r1->description;
				$retArr['other'][$r1->datasetid]['sort'] = $r1->sortsequence;
				$retArr['other'][$r1->datasetid]['ts'] = $r1->initialtimestamp;
				$retArr['other'][$r1->datasetid]['ispublic'] = $r1->ispublic;
			}
			$rs1->free();
		}
		return $retArr;
	}

	//  NEON edit start - add citation, clean input
	public function editDataset($datasetID, $name, $notes, $description, $ispublic) {
		$datasetID = intval($datasetID);
		$ispublic = intval($ispublic);

		$sql = "UPDATE omoccurdatasets
				SET datasetName = ?,
					notes = ?,
					description = ?,
					ispublic = ?
				WHERE datasetid = ?";

		$stmt = $this->conn->prepare($sql);

		if (!$stmt) {
			$this->errorArr[] = 'ERROR preparing dataset update: ' . $this->conn->error;
			return false;
		}

		$stmt->bind_param(
			'sssii',
			$name,
			$notes,
			$description,
			$ispublic,
			$datasetID
		);

		if (!$stmt->execute()) {
			$this->errorArr[] = 'ERROR saving dataset edits: ' . $stmt->error;
			$stmt->close();
			return false;
		}

		$stmt->close();

		return true;
	}
	// NEON edit end

	public function createDataset($name, $notes, $description, $ispublic, $uid) {
		$sql = 'INSERT INTO omoccurdatasets (datasetName,name,notes,description,ispublic,uid)
			VALUES("' . $this->cleanInStr($name) . '","' . $this->cleanInStr($name) . '",' . ($notes ? '"' . $this->cleanInStr($notes) . '"' : 'NULL') . ',' . ($description ? '"' . $this->cleanInStr($description) . '"' : 'NULL') . ',' . ($ispublic ? '"' . $this->cleanInStr($ispublic) . '"' : '"0"') . ',' . $uid . ') ';
		if ($this->conn->query($sql)) {
			$this->datasetId = $this->conn->insert_id;
		} else {
			$this->errorArr[] = 'ERROR creating new dataset: ' . $this->conn->error;
			return false;
		}
		return true;
	}

	public function mergeDatasets($targetArr) {
		$targetDsid = array_shift($targetArr);
		//Rename target
		$sql1 = 'UPDATE omoccurdatasets SET datasetName = CONCAT(datasetName," (merged)"), name = CONCAT(name," (merged)") WHERE datasetid = ' . $targetDsid;
		if ($this->conn->query($sql1)) {
			//Push occurrences to target
			$sql2 = 'UPDATE IGNORE omoccurdatasetlink SET datasetid = ' . $targetDsid . ' WHERE datasetid IN(' . implode(',', $targetArr) . ')';
			if ($this->conn->query($sql2)) {
				//Delete dataset, including linked occurrences that failed to transfer due to being linked multiple times within the group
				$sql3 = 'DELETE FROM omoccurdatasets WHERE datasetid IN(' . implode(',', $targetArr) . ')';
				if (!$this->conn->query($sql3)) {
					$this->errorArr[] = 'WARNING: Unable to remove extra datasets: ' . $this->conn->error;
					return false;
				}
			} else {
				$this->errorArr[] = 'FATAL ERROR: Unable to transfer occurrence records into target dataset: ' . $this->conn->error;
				return false;
			}
		} else {
			$this->errorArr[] = 'FATAL ERROR: Unable to rename target dataset in prep for merge: ' . $this->conn->error;
			return false;
		}
		return true;
	}

	public function cloneDatasets($targetArr, $uid) {
		$status = true;
		$sql = 'SELECT datasetid, IFNULL(datasetName, name) as datasetName, name, notes, description, sortsequence FROM omoccurdatasets WHERE datasetid IN(' . implode(',', $targetArr) . ')';
		$rs = $this->conn->query($sql);
		while ($r = $rs->fetch_object()) {
			//Create new name and ensure it doesn't already exist for owner
			$newName = $r->datasetName . ' - Copy';
			$newNameTemp = $newName;
			$cnt = 1;
			do {
				$sql1 = 'SELECT datasetid FROM omoccurdatasets WHERE name = "' . $newNameTemp . '" AND uid = ' . $uid;
				$nameExists = false;
				$rs1 = $this->conn->query($sql1);
				while ($rs1->fetch_object()) {
					$newNameTemp = $newName . ' ' . $cnt;
					$nameExists = true;
					$cnt++;
				}
				$rs1->free();
			} while ($nameExists);
			$newName = $newNameTemp;
			//Add to database
			$sql2 = 'INSERT INTO omoccurdatasets(datasetName, name, notes, description, sortsequence, uid)
				VALUES("' . $newName . '","' . $r->name . '","' . $r->notes . '","' . $r->description . '",' . ($r->sortsequence ? $r->sortsequence : '""') . ',' . $uid . ')';
			if ($this->conn->query($sql2)) {
				$this->datasetId = $this->conn->insert_id;
				//Duplicate all records wtihin new dataset
				$sql3 = 'INSERT INTO omoccurdatasetlink(occid, datasetid, notes, description) ' .
					'SELECT occid, ' . $this->datasetId . ', notes, description FROM omoccurdatasetlink WHERE datasetid = ' . $r->datasetid;
				if (!$this->conn->query($sql3)) {
					$this->errorArr[] = 'ERROR: Unable to clone dataset links into new datasets: ' . $this->conn->error;
					$status = false;
				}
			} else {
				$this->errorArr[] = 'ERROR: Unable to create new dataset within clone method: ' . $this->conn->error;
				$status = false;
			}
		}
		$rs->free();
		return $status;
	}

	public function deleteDataset($dsid) {
		//Delete users
		$sql1 = 'DELETE FROM userroles WHERE (role IN("DatasetAdmin","DatasetEditor","DatasetReader")) AND (tablename = "omoccurdatasets") AND (tablepk = ' . $dsid . ') ';
		//echo $sql;
		if (!$this->conn->query($sql1)) {
			$this->errorArr[] = 'ERROR deleting user: ' . $this->conn->error;
			return false;
		}

		// NEON edit start
		//Delete dataset records
		$sql2 = 'DELETE FROM omoccurdatasetlink WHERE datasetid = ' . $dsid;
		if (!$this->conn->query($sql2)) {
			$this->errorArr[] = 'ERROR: Unable to delete target datasets: ' . $this->conn->error;
			return false;
		}

		//Delete reference dataset link
		$sql4 = 'DELETE FROM referencedatasetlink WHERE datasetid = ' . $dsid;
		if (!$this->conn->query($sql4)) {
			$this->errorArr[] = 'ERROR: Unable to delete target datasets: ' . $this->conn->error;
			return false;
		}

		//Delete datasets
		$sql4 = 'DELETE FROM omoccurdatasets WHERE datasetid = ' . $dsid;
		if (!$this->conn->query($sql4)) {
			$this->errorArr[] = 'ERROR: Unable to delete target datasets: ' . $this->conn->error;
			return false;
		}

		$sql5 = 'UPDATE neonrequest SET datasetID = NULL WHERE datasetID = ' . $dsid;
		if (!$this->conn->query($sql5)) {
			$this->errorArr[] = 'ERROR: Unable to delete neon request datasets: ' . $this->conn->error;
			return false;
		}
		// NEON edit end

		return true;
	}

	public function getUsers($datasetId) {
		$retArr = array();
		//NEON edit start - pull email username if full name is empty
		$sql = 'SELECT u.uid, r.role,
				CASE
					WHEN u.firstname IS NULL AND u.lastname IS NULL
						THEN SUBSTRING_INDEX(u.email, "@", 1)
					ELSE CONCAT_WS(", ", u.lastname, u.firstname)
				END AS username
			FROM userroles r
			INNER JOIN users u ON r.uid = u.uid
			WHERE r.role IN("DatasetAdmin","DatasetEditor","DatasetReader")
			AND r.tablename = "omoccurdatasets"
			AND r.tablepk = ' . $datasetId;
		//NEON edit end
		//echo $sql;
		$rs = $this->conn->query($sql);
		while ($r = $rs->fetch_object()) {
			$retArr[$r->role][$r->uid] = $r->username;
		}
		$rs->free();
		return $retArr;
	}

	public function addUser($datasetID, $uid, $role) {
		if (is_numeric($uid)) {
			$sql = 'INSERT INTO userroles(uid,role,tablename,tablepk,uidassignedby) VALUES(' . $uid . ',"' . $this->cleanInStr($role) . '","omoccurdatasets",' . $datasetID . ',' . $GLOBALS['SYMB_UID'] . ')';
			if (!$this->conn->query($sql)) {
				$this->errorArr[] = 'ERROR adding new user: ' . $this->conn->error;
				return false;
			}
		}
		return true;
	}

	public function deleteUser($datasetID, $uid, $role) {
		$status = true;
		$sql = 'DELETE FROM userroles WHERE (uid = ' . $uid . ') AND (role = "' . $role . '") AND (tablename = "omoccurdatasets") AND (tablepk = ' . $datasetID . ') ';
		if (!$this->conn->query($sql)) {
			$this->errorArr[] = 'ERROR deleting user: ' . $this->conn->error;
			return false;
		}
		return $status;
	}


	public function setOccurrenceCount($datasetId) {
		$returnVal = 0;
		if ($datasetId) {
			$sql = 'SELECT COUNT(o.occid) FROM omoccurrences o INNER JOIN omoccurdatasetlink dl ON o.occid = dl.occid WHERE dl.datasetid = ? ';
			$sql .= OccurrenceUtil::appendFullProtectionSQL();
			$params[] = $datasetId;
			try {
				$result = QueryUtil::executeQuery($this->conn, $sql, $params);
				$countResponse = $result->fetch_array();
				$result->free();
			} catch (\Throwable  $e) {
				error_log('ERROR fetching count for dataset: ' . $datasetId);
			}
			if ($countResponse) {
				$returnVal = (int) $countResponse[0];
			}
		}
		$this->occurrenceCount = $returnVal;
		return $returnVal;
	}

	public function getOccurrenceCount($datasetId) {
		if (!$datasetId) {
			return null;
		}
		if ($this->occurrenceCount) {
			return $this->occurrenceCount;
		} else {
			return $this->setOccurrenceCount($datasetId);
		}
	}

	public function getOccurrences($datasetId, $pageNumber = 1, $retLimit = 1000000000) {
		$retArr = array();
		if ($datasetId) {
			$sql = 'SELECT o.occid, o.catalognumber, o.occurrenceid ,o.othercatalognumbers,
				o.sciname, o.family, o.recordedby, o.recordnumber, o.eventdate,
				o.country, o.stateprovince, o.county, o.locality, o.decimallatitude, o.decimallongitude, dl.notes
				FROM omoccurrences o INNER JOIN omoccurdatasetlink dl ON o.occid = dl.occid
				WHERE dl.datasetid = ? ';
			$sql .= OccurrenceUtil::appendFullProtectionSQL();
			if($retLimit) $sql .= 'LIMIT '. (($pageNumber - 1) * $retLimit) . ',' . $retLimit;
			$params[] = $datasetId;
			try {
				$result = QueryUtil::executeQuery($this->conn, $sql, $params);
			} catch (\Throwable  $e) {
				error_log('ERROR fetching count for dataset: ' . $datasetId);
			}
			$recordCount = 1;
			while ($r = $result->fetch_object()) {
				if ($r->catalognumber) $retArr[$r->occid]['catnum'] = $r->catalognumber;
				elseif ($r->occurrenceid) $retArr[$r->occid]['catnum'] = $r->occurrenceid;
				elseif ($r->othercatalognumbers) $retArr[$r->occid]['catnum'] = $r->othercatalognumbers;
				else $retArr[$r->occid]['catnum'] = '';
				$sciname = $r->sciname;
				if ($r->family) $sciname .= ' (' . $r->family . ')';
				$retArr[$r->occid]['sciname'] = $sciname;
				$collStr = $r->recordedby . ' ' . $r->recordnumber;
				if ($r->eventdate) $collStr .= ' [' . $r->eventdate . ']';
				$retArr[$r->occid]['coll'] = $collStr;
				$retArr[$r->occid]['loc'] = trim($r->country . ', ' . $r->stateprovince . ', ' . $r->county . ', ' . $r->locality, ', ');
			}
			$result->free();
		}
		return $retArr;
	}

	// NEON edit start - custom function
	public function neonGetOccurrences($datasetId) {
		$retArr = array();

		$sql = "WITH domains AS (
					SELECT l.occid, d.name
					FROM omoccurdatasetlink l
					LEFT JOIN omoccurdatasets d
						ON l.datasetID = d.datasetID
					WHERE d.datasetID > 0 AND d.datasetID < 20
				),
				sites AS (
					SELECT l.occid, d.name
					FROM omoccurdatasetlink l
					LEFT JOIN omoccurdatasets d
						ON l.datasetID = d.datasetID
					WHERE d.datasetID > 32 AND d.datasetID < 132
				)

				SELECT
					o.occid,
					e.name AS domain,
					o.stateProvince,
					t.name AS siteID,
					m.sampleID,
					m.sampleCode AS barcode,
					o.catalogNumber AS IGSN,
					CONCAT('https://doi.org/10.58052/', o.catalogNumber) AS IGSN_ID,
					o.sciname AS scientificName
				FROM domains e
				LEFT JOIN NeonSample m
					ON e.occid = m.occid
				LEFT JOIN omoccurrences o
					ON e.occid = o.occid
				LEFT JOIN sites t
					ON e.occid = t.occid
				JOIN omoccurdatasetlink sl
					ON e.occid = sl.occid
				WHERE sl.datasetID = ?
				ORDER BY domain, stateProvince, siteID, IGSN";

		if ($stmt = $this->conn->prepare($sql)) {
			$stmt->bind_param("i", $datasetId);
			$stmt->execute();

			$result = $stmt->get_result();

			while ($row = $result->fetch_assoc()) {
				$retArr[] = $row;
			}

			$stmt->close();
		} else {
			$this->errorMessage = 'Unable to prepare sample query.';
		}

		return $retArr;
	}
	//NEON edit end

	public function removeSelectedOccurrences($datasetId, $occArr) {
		$status = true;
		if (is_numeric($datasetId) && $occArr) {
			$sql = 'DELETE FROM omoccurdatasetlink WHERE (datasetid = ' . $datasetId . ') AND (occid IN(' . implode(',', $occArr) . '))';
			if (!$this->conn->query($sql)) {
				$this->errorArr[] = 'ERROR deleting selected occurrences: ' . $this->conn->error;
				return false;
			}
		}
		return $status;
	}


	public function addSelectedOccurrences($datasetId, $occArr) {
		$status = false;
		if (is_numeric($datasetId)) {
			if (is_numeric($occArr)) $occArr = array($occArr);
			foreach ($occArr as $v) {
				if (is_numeric($v)) {
					$sql = 'INSERT IGNORE INTO omoccurdatasetlink(occid,datasetid) VALUES("' . $v . '",' . $datasetId . ') ';
					if ($this->conn->query($sql)) $status = true;
					else {
						$this->errorArr[] = 'ERROR adding occurrence (' . $v . '): ' . $this->conn->error;
						$status = false;
					}
				}
			}
		}
		return $status;
	}

	//NEON edit start - custom funcionts
	//remove font families from html, which mess up the rendering
	public function removeFontFamily($html) {
		$dom = new DOMDocument();

		libxml_use_internal_errors(true);
		$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html,
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();

		$xpath = new DOMXPath($dom);

		foreach ($xpath->query('//*[@style]') as $node) {
			$style = $node->getAttribute('style');

			$style = preg_replace(
				'/(?:^|;)\s*font-family\s*:\s*[^;]+;?/i',
				'',
				$style
			);

			$style = trim($style, '; ');

			if ($style) {
				$node->setAttribute('style', $style);
			} else {
				$node->removeAttribute('style');
			}
		}

		return $dom->saveHTML();
	}

	public function addDatasetAssociation($datasetID, $associatedDatasetID) {

		$datasetID = (int)$datasetID;
		$associatedDatasetID = (int)$associatedDatasetID;

		if (!$datasetID || !$associatedDatasetID) {
			return false;
		}

		if ($datasetID == $associatedDatasetID) {
			return false;
		}

		$sql = "INSERT INTO omoccurdatasetassociations (datasetID, associatedDatasetID)
			VALUES ($datasetID, $associatedDatasetID)";

		if (!$this->conn->query($sql)) {
			return false;
		}

		$associatedDatasets = $this->getAssociatedDatasets($datasetID);

		foreach ($associatedDatasets as $row) {

			$existingDatasetID = (int)$row['datasetID'];

			if ($existingDatasetID == $associatedDatasetID) {
				continue;
			}

			$sql = "INSERT INTO omoccurdatasetassociations
					(datasetID, associatedDatasetID)
					VALUES ($existingDatasetID, $associatedDatasetID)";

			if (!$this->conn->query($sql)) {
				return false;
			}
		}

		return true;
	}
	//NEON edit end - custom functions

	//General setters and getters
	public function getUserList($term) {
		$retArr = array();
		$sql = 'SELECT uid, CONCAT(CONCAT_WS(", ", lastname, firstname)," - ", username," [#", uid,"]") AS username ' .
			'FROM users WHERE lastname LIKE "%' . $this->cleanInStr($term) . '%" OR username LIKE "%' . $this->cleanInStr($term) . '%" ' .
			'ORDER BY lastname, firstname';
		$rs = $this->conn->query($sql);
		while ($r = $rs->fetch_object()) {
			$retArr[] = array('id' => $r->uid, 'label' => $r->username);
		}
		$rs->free();
		return $retArr;
	}

	public function getCollName($collId) {
		$collName = '';
		if ($collId) {
			if (!$this->collArr) $this->setCollMetadata($collId);
			$collName = $this->collArr['collname'] . ' (' . $this->collArr['instcode'] . ($this->collArr['collcode'] ? ':' . $this->collArr['collcode'] : '') . ')';
		}
		return $collName;
	}

	private function setCollMetadata($collId) {
		$sql = 'SELECT institutioncode, collectioncode, collectionname, colltype ' .
			'FROM omcollections WHERE collid = ' . $collId;
		if ($rs = $this->conn->query($sql)) {
			while ($r = $rs->fetch_object()) {
				$this->collArr['instcode'] = $r->institutioncode;
				$this->collArr['collcode'] = $r->collectioncode;
				$this->collArr['collname'] = $r->collectionname;
				$this->collArr['colltype'] = $r->colltype;
			}
			$rs->free();
		}
	}

	public function getErrorArr() {
		return $this->errorArr;
	}

	public function getErrorMessage() {
		return implode('; ', $this->errorArr);
	}

	public function getDatasetId() {
		return $this->datasetId;
	}

	//Misc functions
	private function cleanInStr($str) {
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ', $newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
