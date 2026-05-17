<?php
include_once('OccurrenceManager.php');
include_once('OccurrenceAccessStats.php');
include_once('OmDeterminations.php');
include_once('CollectionMetadata.php');

class OccurrenceListManager extends OccurrenceManager{

	private $recordCount = 0;
	private $sortArr = array();

 	public function __construct(){
 		parent::__construct();
 	}

	public function __destruct(){
 		parent::__destruct();
	}

	public function getSpecimenMap($pageRequest, $cntPerPage){
		$retArr = Array();
		$isSecuredReader = false;
		if($GLOBALS['USER_RIGHTS']){
			if($GLOBALS['IS_ADMIN'] || array_key_exists('CollAdmin', $GLOBALS['USER_RIGHTS']) || array_key_exists('RareSppAdmin', $GLOBALS['USER_RIGHTS']) || array_key_exists('RareSppReadAll', $GLOBALS['USER_RIGHTS'])){
				$isSecuredReader = true;
			}
		}
		$occArr = array();
		$sqlWhere = $this->getSqlWhere();
		if(!$this->recordCount || $this->reset) $this->setRecordCnt($sqlWhere);
		$sql = 'SELECT o.occid, o.collid, c.institutioncode, c.collectioncode, c.icon, o.institutioncode AS instcodeoverride, o.collectioncode AS collcodeoverride, '.
				'o.catalognumber, o.family, o.sciname, o.scientificnameauthorship, o.tidinterpreted, o.recordedby, o.recordnumber, o.eventdate, '.
				'o.country, o.stateprovince, o.county, o.locality, o.decimallatitude, o.decimallongitude, o.recordsecurity, o.securityreason, '.
				'o.habitat, o.substrate, o.minimumelevationinmeters, o.maximumelevationinmeters, o.observeruid, c.sortseq '.
				'FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid ';
		$sql .= $this->getTableJoins($sqlWhere).$sqlWhere;
		//Don't allow someone to query all occurrences if there are no conditions
		if(!$sqlWhere) $sql .= 'WHERE o.occid IS NULL ';
		if($this->sortArr){
			$sql .= 'ORDER BY ' . implode(',', $this->sortArr) . ', o.collid ';
		}
		else{
			$sql .= 'ORDER BY o.collid ';
		}
		if($pageRequest > 0) $pageRequest = ($pageRequest - 1) * $cntPerPage;
		$sql .= ' LIMIT ' . $pageRequest . ',' . $cntPerPage;
		//echo '<div style="width: 1200px">' . $sql . '</div>';
		// echo $sql; exit; // @TODO here
		$result = $this->conn->query($sql);
		if($result){
			$securityCollArr = array();
			if(isset($GLOBALS['USER_RIGHTS']['CollEditor'])) $securityCollArr = $GLOBALS['USER_RIGHTS']['CollEditor'];
			if(isset($GLOBALS['USER_RIGHTS']['RareSppReader'])) $securityCollArr = array_unique(array_merge($securityCollArr, $GLOBALS['USER_RIGHTS']['RareSppReader']));
			while($row = $result->fetch_object()){
				$securityClearance = false;
				if($isSecuredReader) $securityClearance = true;
				elseif(in_array($row->collid,$securityCollArr)) $securityClearance = true;
				$retArr[$row->occid]['collid'] = $row->collid;
				$retArr[$row->occid]['instcode'] = $this->cleanOutStr($row->institutioncode);
				if($row->instcodeoverride){
					if(!$retArr[$row->occid]['instcode']) $retArr[$row->occid]['instcode'] = $row->instcodeoverride;
					elseif($retArr[$row->occid]['instcode'] != $row->instcodeoverride) $retArr[$row->occid]['instcode'] .= '-'.$row->instcodeoverride;
				}
				$retArr[$row->occid]['collcode'] = $this->cleanOutStr($row->collectioncode);
				if($row->collcodeoverride){
					if(!$retArr[$row->occid]['collcode']) $retArr[$row->occid]['collcode'] = $row->collcodeoverride;
					elseif($retArr[$row->occid]['collcode'] != $row->collcodeoverride) $retArr[$row->occid]['collcode'] .= '-'.$row->collcodeoverride;
				}
				$retArr[$row->occid]['icon'] = $row->icon;
				$retArr[$row->occid]['catnum'] = $this->cleanOutStr($row->catalognumber);
				$retArr[$row->occid]['family'] = $this->cleanOutStr($row->family);
				$retArr[$row->occid]['sciname'] = ($row->sciname?$this->cleanOutStr($row->sciname):'undetermined');
				$retArr[$row->occid]['tid'] = $row->tidinterpreted;
				$retArr[$row->occid]['author'] = $this->cleanOutStr($row->scientificnameauthorship);
				/*
				 if(isset($row->scinameprotected) && $row->scinameprotected && !$securityClearance){
				 $retArr[$row->occid]['taxonsecure'] = 1;
				 $retArr[$row->occid]['sciname'] = $this->cleanOutStr($row->scinameprotected);
				 $retArr[$row->occid]['author'] = '';
				 $retArr[$row->occid]['family'] = $row->familyprotected;
				 $retArr[$row->occid]['tid'] = $row->tidprotected;
				 }
				 */
				$retArr[$row->occid]['collector'] = $this->cleanOutStr($row->recordedby);
				$retArr[$row->occid]['country'] = $this->cleanOutStr($row->country);
				$retArr[$row->occid]['state'] = $this->cleanOutStr($row->stateprovince);
				$retArr[$row->occid]['county'] = $this->cleanOutStr($row->county);
				$retArr[$row->occid]['obsuid'] = $row->observeruid;
				$retArr[$row->occid]['recordsecurity'] = $row->recordsecurity;
				if($securityClearance || $row->recordsecurity != 1){
					$locStr = $row->locality ?? '';
					$retArr[$row->occid]['locality'] = str_replace('.,',',',$this->cleanOutStr(trim($locStr,' ,;')));
					$retArr[$row->occid]['declat'] = $row->decimallatitude;
					$retArr[$row->occid]['declong'] = $row->decimallongitude;
					$retArr[$row->occid]['collnum'] = $this->cleanOutStr($row->recordnumber);
					$retArr[$row->occid]['date'] = $row->eventdate;
					$retArr[$row->occid]['habitat'] = $this->cleanOutStr($row->habitat);
					$retArr[$row->occid]['substrate'] = $this->cleanOutStr($row->substrate);
					$elevStr = $row->minimumelevationinmeters;
					if($row->maximumelevationinmeters) $elevStr .= ' - '.$row->maximumelevationinmeters;
					$retArr[$row->occid]['elev'] = $elevStr;
					$occArr[] = $row->occid;
				}
				else{
					$retArr[$row->occid]['locality'] = 'PROTECTED';
				}
				//neon edit
				$retArr[$row->occid]['sampleID'] = null;
				$retArr[$row->occid]['sampleCode'] = null;
				//end neon edit
			}
			$result->free();
		}
		if($occArr){
			$this->setImages($occArr,$retArr);
			$statsManager = new OccurrenceAccessStats();
			$statsManager->recordAccessEventByArr($occArr,'list');
		}
		//NEON edit
		if($retArr){
			$this->setIdentifiers(array_keys($retArr), $retArr);
		}
		//end NEON edit
		return $retArr;
	}

	//NEON edit add functions
	public function getNeonAvailabilitySiteCodes(): array {
		$retArr = array();
		$sqlWhere = $this->getSqlWhere();
		if(!$sqlWhere) {
			return $retArr;
		}
		$sql = '
			SELECT DISTINCT
				d.name AS siteCode,
				DATE_FORMAT(o.eventdate, "%Y-%m") AS eventMonth
			FROM omoccurrences o
			' . $this->getTableJoins($sqlWhere) . '
			INNER JOIN omoccurdatasetlink l ON o.occid = l.occid
			INNER JOIN omoccurdatasets d ON l.datasetid = d.datasetid
			' . $sqlWhere . '
			AND d.notes = "NEON Site"
			AND o.eventdate IS NOT NULL
		';
		$rs = $this->conn->query($sql);
		$availabilityMap = array();
		if($rs){
			while($r = $rs->fetch_object()){
				$siteCode = $this->cleanOutStr($r->siteCode);
				$eventMonth = $r->eventMonth;
				if(!$siteCode || !$eventMonth){
					continue;
				}
				if(!isset($availabilityMap[$siteCode])){
					$availabilityMap[$siteCode] = array();
				}
				$availabilityMap[$siteCode][$eventMonth] = true;
			}
			$rs->free();
		}
		foreach($availabilityMap as $siteCode => $monthsMap){
			$months = array_keys($monthsMap);
			sort($months);
			$retArr[] = array(
				'siteCode' => $siteCode,
				'availableMonths' => $months
			);
		}
		usort($retArr, function($a, $b){
			return strcmp($a['siteCode'], $b['siteCode']);
		});
		return $retArr;
	}
	
	public function getCollectionTypeSummary(): array {
		$retArr = array();
		$sqlWhere = $this->getSqlWhere();
		if(!$sqlWhere) return $retArr;
		$jsonPath = $GLOBALS['SERVER_ROOT'] . '/neon-react/biorepo_lib/collections-protocol.json';
		if(!file_exists($jsonPath)) return $retArr;
		$protocolTree = json_decode(file_get_contents($jsonPath), true);
		if(!$protocolTree) return $retArr;
		$identificationParentMap = array();
		$mapIdentificationParents = function($node, $parentCollid = null) use (&$mapIdentificationParents, &$identificationParentMap){
			if(isset($node['collid'])){
				$name = $node['name'] ?? '';
				if(stripos($name, 'Identifications') !== false && $parentCollid){
					$identificationParentMap[(int)$node['collid']] = (int)$parentCollid;
				}
				return;
			}
			if(!empty($node['children'])){
				$bulkCollid = $parentCollid;
				foreach($node['children'] as $child){
					if(isset($child['collid']) && stripos($child['name'], 'Identifications') === false){
						$bulkCollid = (int)$child['collid'];
					}
				}
				foreach($node['children'] as $child){
					$mapIdentificationParents($child, $bulkCollid);
				}
			}
		};
		foreach($protocolTree as $familyNode){
			$mapIdentificationParents($familyNode);
		}
		$sql = '
			SELECT
				o.collid,
				c.collectionName,
				COUNT(*) AS recordCnt
			FROM omoccurrences o
			INNER JOIN omcollections c ON o.collid = c.collid
			' . $this->getTableJoins($sqlWhere) . '
			' . $sqlWhere . '
			GROUP BY o.collid, c.collectionName
		';
		$rs = $this->conn->query($sql);
		$countMap = array();
		$nameMap = array();
		$totalRecords = 0;
		if($rs){
			while($r = $rs->fetch_object()){
				$collid = (int)$r->collid;
				$cnt = (int)$r->recordCnt;
				$countMap[$collid] = $cnt;
				$nameMap[$collid] = $this->cleanOutStr($r->collectionName);
			}
			$rs->free();
		}
		if($identificationParentMap){
			$identifiedCollids = implode(',', array_keys($identificationParentMap));
			$linkSql = '
				SELECT
					p.collid AS parentCollid,
					COUNT(DISTINCT p.occid) AS linkedCnt
				FROM omoccurrences o
				' . $this->getTableJoins($sqlWhere) . '
				INNER JOIN omoccurassociations oa
					ON o.occid = oa.occidAssociate
					AND oa.relationship = "originatingSampleOf"
				INNER JOIN omoccurrences p
					ON oa.occid = p.occid
				' . $sqlWhere . '
				AND o.collid IN(' . $identifiedCollids . ')
				GROUP BY p.collid
			';
			$linkRs = $this->conn->query($linkSql);
			if($linkRs){
				while($r = $linkRs->fetch_object()){
					$parentCollid = (int)$r->parentCollid;
					$linkedCnt = (int)$r->linkedCnt;
					if(!isset($countMap[$parentCollid])){
						$countMap[$parentCollid] = 0;
					}
					if(!isset($nameMap[$parentCollid])){
						$nameSql = '
							SELECT collectionName
							FROM omcollections
							WHERE collid = ' . (int)$parentCollid;
						$nameRs = $this->conn->query($nameSql);
						if($nameRs && $nameRow = $nameRs->fetch_object()){
							$nameMap[$parentCollid] = $this->cleanOutStr($nameRow->collectionName);
						}
						if($nameRs){
							$nameRs->free();
						}
					}
					$currentParentCnt = $countMap[$parentCollid] ?? 0;
					$countMap[$parentCollid] = max($currentParentCnt, $linkedCnt);
				}
				$linkRs->free();
			}
		}
		$isIdentification = function($name){
			return stripos($name, 'Identified ') !== false;
		};
		foreach($countMap as $collid => $cnt){
			$name = $nameMap[$collid] ?? '';
			if(!$isIdentification($name)){
				$totalRecords += $cnt;
			}
		}
		$flattenNode = function($node) use (&$flattenNode, $countMap, $nameMap, $isIdentification){
			$ret = array();
			if(isset($node['collid'])){
				$collid = (int)$node['collid'];
				$total = $countMap[$collid] ?? 0;
				if($total > 0){
					$name = $nameMap[$collid] ?? $node['name'];
					$ret[] = array(
						'name' => $name,
						'collid' => $collid,
						'total' => $total,
						'isIdentification' => $isIdentification($name),
					);
				}
				return $ret;
			}
			if(!empty($node['children'])){
				foreach($node['children'] as $child){
					$ret = array_merge($ret, $flattenNode($child));
				}
			}
			return $ret;
		};
		foreach($protocolTree as $familyNode){
			$subtypes = array();
			foreach($familyNode['children'] as $child){
				$subtypes = array_merge($subtypes, $flattenNode($child));
			}
			if(!$subtypes) continue;
			$familyTotal = 0;
			foreach($subtypes as $subtype){
				if(empty($subtype['isIdentification'])){
					$familyTotal += $subtype['total'];
				}
			}
			if($familyTotal <= 0 || $totalRecords <= 0) continue;
			foreach($subtypes as &$subtype){
				if(!empty($subtype['isIdentification'])){
					$subtype['percent'] = 0;
				} else {
					$subtype['percent'] = round(($subtype['total'] / $totalRecords) * 100, 1);
				}
			}
			unset($subtype);
			$retArr[] = array(
				'family' => $familyNode['name'],
				'total' => $familyTotal,
				'percent' => round(($familyTotal / $totalRecords) * 100, 1),
				'subtypes' => $subtypes
			);
		}
		usort($retArr, function($a, $b){
			return $b['total'] <=> $a['total'];
		});
		return array(
			'totalRecords' => $totalRecords,
			'families' => $retArr
		);
	}
	
	public function getAdditionalCollectionTypeSummary(): array {
		$retArr = array();
		$sqlWhere = $this->getSqlWhere();
		if(!$sqlWhere) return $retArr;
		$collData = new CollectionMetadata();
		$allowedCollids = array();
		if($collsArr = $collData->getCollMetaByCat('Additional NEON Collections')){
			foreach($collsArr as $collArr){
				if(!empty($collArr['collid'])){
					$allowedCollids[] = (int)$collArr['collid'];
				}
			}
		}
		if(!$allowedCollids) return $retArr;
		$sql = '
			SELECT
				o.collid,
				c.collectionName,
				COUNT(*) AS recordCnt
			FROM omoccurrences o
			INNER JOIN omcollections c ON o.collid = c.collid
			' . $this->getTableJoins($sqlWhere) . '
			' . $sqlWhere . '
			AND o.collid IN(' . implode(',', $allowedCollids) . ')
			GROUP BY o.collid, c.collectionName
		';
		$rs = $this->conn->query($sql);
		$subtypes = array();
		$totalRecords = 0;
		if($rs){
			while($r = $rs->fetch_object()){
				$cnt = (int)$r->recordCnt;
				$totalRecords += $cnt;
				$subtypes[] = array(
					'name' => $this->cleanOutStr($r->collectionName),
					'collid' => (int)$r->collid,
					'total' => $cnt
				);
			}
			$rs->free();
		}
		if(!$subtypes || $totalRecords <= 0){
			return array(
				'totalRecords' => 0,
				'families' => array()
			);
		}
		foreach($subtypes as &$subtype){
			$subtype['percent'] = round(($subtype['total'] / $totalRecords) * 100, 1);
		}
		usort($subtypes, function($a, $b){
			return $b['total'] <=> $a['total'];
		});
		$retArr[] = array(
			'family' => 'Other Repositories',
			'total' => $totalRecords,
			'percent' => 100,
			'subtypes' => $subtypes
		);
		return array(
			'totalRecords' => $totalRecords,
			'families' => $retArr
		);
	}
	
	private function setIdentifiers($occArr, &$retArr): void {
		$sql = 'SELECT occid, identifierName, identifierValue 
				FROM omoccuridentifiers 
				WHERE occid IN('.implode(',', $occArr).') 
				AND identifierName IN("NEON sampleID", "NEON sampleID Hash", "NEON sampleCode (barcode)")';
	
		$rs = $this->conn->query($sql);
	
		$isAdmin = (
			!empty($GLOBALS['IS_ADMIN']) ||
			!empty($GLOBALS['USER_RIGHTS']['CollAdmin']) ||
			!empty($GLOBALS['USER_RIGHTS']['CollEditor'])
		);
	
		while($r = $rs->fetch_object()){
			$occid = $r->occid;
	
			if($r->identifierName === 'NEON sampleID'){
				if(!isset($retArr[$occid]['sampleID'])){
					$retArr[$occid]['sampleID'] = $this->cleanOutStr($r->identifierValue);
				}
			}
			elseif($r->identifierName === 'NEON sampleID Hash'){
				if(!$isAdmin){
					$retArr[$occid]['sampleID'] = $this->cleanOutStr($r->identifierValue);
				}
			}
			elseif($r->identifierName === 'NEON sampleCode (barcode)'){
				$retArr[$occid]['sampleCode'] = $this->cleanOutStr($r->identifierValue);
			}
		}
	
		$rs->free();
	}
	//end NEON edit

	private function setImages($occArr, &$retArr): void {
		$sql = 'SELECT occid, thumbnailurl, mediaType FROM media WHERE occid IN('.implode(',',$occArr).') ORDER BY occid, sortOccurrence';
		$rs = $this->conn->query($sql);
		$previousOccid = 0;
		while($r = $rs->fetch_object()){
			if($r->occid != $previousOccid) {
				$thumbnail = $r->mediaType === 'audio'?
				$GLOBALS['CLIENT_ROOT'] . '/images/speaker_thumbnail.png':
				$r->thumbnailurl;

				$retArr[$r->occid]['media'] = [
					'thumbnail' => $thumbnail,
					'mediaType' => $r->mediaType
				];
			}

			if($r->mediaType === 'image' && !isset($retArr[$r->occid]['has_image'])) {
				$retArr[$r->occid]['has_image'] = true;
			} else if($r->mediaType === 'audio' && !isset($retArr[$r->occid]['has_audio'])) {
				$retArr[$r->occid]['has_audio'] = true;
			}

			$previousOccid = $r->occid;
		}
		$rs->free();
	}

	private function setRecordCnt($sqlWhere){
		if($sqlWhere){
			$sql = "SELECT COUNT(DISTINCT o.occid) AS cnt FROM omoccurrences o ".$this->getTableJoins($sqlWhere).$sqlWhere;
			// echo "<div>Count sql: ".$sql."</div>"; exit; // @TODO here
			$result = $this->conn->query($sql);
			if($result){
				if($row = $result->fetch_object()){
					$this->recordCount = $row->cnt;
				}
				$result->free();
			}
		}
	}

	public function getRecordCnt(){
		return $this->recordCount;
	}

	public function addSort($field, $direction){
		if($field){
			$this->sortArr[] = $this->cleanInStr($field) . ($direction ? ' desc' : '');
		}
	}

	//Misc support functions
	public function getDatasetArr(){
		$retArr = array();
		$symbUid = $GLOBALS['SYMB_UID'];
		if($symbUid){
			$sql = 'SELECT DISTINCT datasetid, name FROM omoccurdatasets WHERE uid = '.$symbUid.' OR datasetid IN(SELECT tablepk FROM userroles WHERE uid = '.$symbUid.' AND role IN("DatasetAdmin","DatasetEditor"))';
			//echo "<div>Count sql: ".$sql."</div>";
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->datasetid] = $r->name;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getCloseTaxaMatch($name){
		$retArr = array();
		$searchName = trim($name);
		$sql = 'SELECT tid, sciname FROM taxa WHERE soundex(sciname) = soundex(?)';
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param('s', $searchName);
		$stmt->execute();
		$stmt->bind_result($tid, $sciname);
		while($stmt->fetch()){
			if($searchName != $sciname) $retArr[$tid] = $this->cleanOutStr($sciname);
		}
		$stmt->close();
		return $retArr;
	}
}
?>
