<?php
//NEON custom code
include_once('OccurrenceManager.php');
include_once('CollectionMetadata.php');

class OccurrenceListFunctions extends OccurrenceManager{
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
				c.publicName,
				COUNT(DISTINCT o.occid) AS recordCnt
			FROM omoccurrences o
			INNER JOIN omcollections c ON o.collid = c.collid
			' . $this->getTableJoins($sqlWhere) . '
			' . $sqlWhere . '
			GROUP BY o.collid, c.publicName
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
				$nameMap[$collid] = $r->publicName;
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
							SELECT publicName
							FROM omcollections
							WHERE collid = ' . (int)$parentCollid;
						$nameRs = $this->conn->query($nameSql);
						if($nameRs && $nameRow = $nameRs->fetch_object()){
							$nameMap[$parentCollid] = $nameRow->publicName;
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
			return stripos($name, 'Identifications') !== false;
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
			$sortedSubtypes = array();
			
			for($i = 0; $i < count($subtypes); $i++){
				if(!empty($subtypes[$i]['isIdentification'])){
					continue;
				}
			
				$group = array($subtypes[$i]);
			
				if(isset($subtypes[$i + 1]) && !empty($subtypes[$i + 1]['isIdentification'])){
					$group[] = $subtypes[$i + 1];
				}
			
				$sortedSubtypes[] = $group;
			}
			
			usort($sortedSubtypes, function($a, $b){
				return strcasecmp($a[0]['name'], $b[0]['name']);
			});
			
			$subtypes = array_merge(...$sortedSubtypes);
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
			return strcasecmp($a['name'], $b['name']);
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
	
	public function getMaterialSampleTypes(array $occidArr): array {
		$retArr = [];
		if(!$occidArr) {
			return $retArr;
		}
	
		$sql = '
			SELECT occid, sampleType, disposition
			FROM ommaterialsample
			WHERE occid IN('.implode(',', $occidArr).')
			ORDER BY occid
		';
	
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()) {
			$retArr[$r->occid][] = [
				'sampleType' => $r->sampleType,
				'disposition' => $r->disposition
			];
		}
	
		$rs->free();
		return $retArr;
	}
}