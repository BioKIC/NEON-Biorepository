<?php
include_once($SERVER_ROOT.'/classes/OccurrenceCollectionProfile.php');
include_once($SERVER_ROOT.'/classes/utilities/TaxonomyUtil.php');
//include_once($SERVER_ROOT.'/classes/TaxonomyHarvester.php');
include_once($SERVER_ROOT.'/classes/GuidManager.php');
include_once($SERVER_ROOT.'/config/symbini.php');

class DeterminationHarvester {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

	public function getTaxon($sciname, $activeCollid, &$taxonArr){	
		$retArr = array();
		$targetTaxon = '';
		$sciname2 = '';
		if(array_key_exists($sciname, $taxonArr)){
			$targetTaxon = $sciname;
		}
		elseif(substr($sciname,-1) == 's'){
			//Soil taxon needs to have s removed from end of word
			$sciname2 = substr($sciname,0,-1);
			if(array_key_exists($sciname2, $taxonArr)){
				$targetTaxon = $sciname2;
			}
		}
		if(!$targetTaxon){
			$taxonGroup = $this->getTaxonGroup($activeCollid);
			$sql = 'SELECT t.tid, t.sciname, t.author, t.rankid, ts.family, a.tid as acceptedTid, t.taxonGroup as `group`, a.sciname as accepted, a.author as acceptedAuthor
			FROM taxa t
			INNER JOIN taxstatus ts ON t.tid = ts.tid
			INNER JOIN taxa a ON ts.tidAccepted = a.tid
			WHERE ts.taxauthid = 1 AND t.sciname IN("' . $this->cleanInStr($sciname) . '"' . ($this->cleanInStr($sciname2) ? ',"' . $this->cleanInStr($sciname2) . '"' : '') . ')
			ORDER BY
				CASE
					WHEN t.taxonGroup = "' . $taxonGroup . '" THEN 1
					WHEN t.taxonGroup IS NULL THEN 2
					ELSE 3
				END
			LIMIT 1';
			$matchingGroupFound = false;
			$nullGroupFound = false;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					// preferentially choose those of the correct taxon group
					if ($r->group === $taxonGroup) {
						$taxonArr[$r->sciname]['tid'] = $r->tid;
						$taxonArr[$r->sciname]['author'] = $r->author;
						$taxonArr[$r->sciname]['rankid'] = $r->rankid;
						$taxonArr[$r->sciname]['family'] = $r->family;
						$taxonArr[$r->sciname]['accepted'] = $r->accepted;
						$taxonArr[$r->sciname]['acceptedAuthor'] = $r->acceptedAuthor;
						$taxonArr[$r->sciname]['acceptedTid'] = $r->acceptedTid;
						$targetTaxon = $r->sciname;
						$matchingGroupFound = true;
					}
					// if no matching taxa from the taxon group, try ones with a null taxon group
					elseif(!$matchingGroupFound && $r->group === null){
						$taxonArr[$r->sciname]['tid'] = $r->tid;
						$taxonArr[$r->sciname]['author'] = $r->author;
						$taxonArr[$r->sciname]['rankid'] = $r->rankid;
						$taxonArr[$r->sciname]['family'] = $r->family;
						$taxonArr[$r->sciname]['accepted'] = $r->accepted;
						$taxonArr[$r->sciname]['acceptedAuthor'] = $r->acceptedAuthor;
						$taxonArr[$r->sciname]['acceptedTid'] = $r->acceptedTid;
						$targetTaxon = $r->sciname;
					}
				}
			}
			$rs->free();
		}
		if($targetTaxon){
			$retArr['sciname'] = $targetTaxon;
			$retArr['tidInterpreted'] = $taxonArr[$targetTaxon]['tid'];
			$retArr['scientificNameAuthorship'] = $taxonArr[$targetTaxon]['author'];
			$retArr['rankid'] = $taxonArr[$targetTaxon]['rankid'];
			$retArr['family'] = $taxonArr[$targetTaxon]['family'];
			$retArr['accepted'] = $taxonArr[$targetTaxon]['accepted'];
		}
		return $retArr;
	}

	public function adjustTaxonomy(){
		// Update tidInterpreted index
		// These statements should no longer be needed since tids are explicitly set upon harvest, but we'll keep to ensure that nothing falls through the cracks
		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname
			INNER JOIN taxstatus ts ON t.tid = ts.tid
			SET o.tidInterpreted = t.tid
			WHERE o.tidInterpreted IS NULL AND o.family = ts.family';
		if(!$this->conn->query($sql)){
			echo 'ERROR updating occurrence tidInterpreted with family match: '.$sql;
		}
		$sql = 'UPDATE omoccurdeterminations d INNER JOIN taxa t ON d.sciname = t.sciname
			INNER JOIN omoccurrences o ON d.occid = o.occid
			INNER JOIN taxstatus ts ON t.tid = ts.tid
			SET d.tidInterpreted = t.tid
			WHERE d.tidInterpreted IS NULL AND o.family = ts.family';
		if(!$this->conn->query($sql)){
			echo 'ERROR updating determination tidInterpreted with family match: '.$sql;
		}

		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname SET o.tidinterpreted = t.tid WHERE (o.tidinterpreted IS NULL)';
		if(!$this->conn->query($sql)){
			echo 'ERROR updating tidInterpreted: '.$sql;
		}
		$sql = 'UPDATE omoccurdeterminations d INNER JOIN taxa t ON d.sciname = t.sciname SET d.tidinterpreted = t.tid WHERE (d.tidinterpreted IS NULL)';
		if(!$this->conn->query($sql)){
			echo 'ERROR updating tidInterpreted: '.$sql;
		}

		//Update Mosquito taxa details
		$sql = 'UPDATE omoccurrences o INNER JOIN NeonSample s ON o.occid = s.occid
			INNER JOIN taxa t ON o.sciname = t.sciname
			INNER JOIN taxstatus ts ON t.tid = ts.tid
			SET o.scientificNameAuthorship = t.author, o.tidinterpreted = t.tid, o.family = ts.family
			WHERE (o.collid = 29) AND (o.scientificNameAuthorship IS NULL) AND (o.family IS NULL) AND (ts.taxauthid = 1)';
		if(!$this->conn->query($sql)){
			echo 'ERROR updating occurrence taxonomy codes: '.$sql;
		}
		$sql = 'UPDATE omoccurrences o INNER JOIN omoccurdeterminations d ON o.occid = d.occid
			INNER JOIN NeonSample s ON d.occid = s.occid
			INNER JOIN taxa t ON d.sciname = t.sciname
			INNER JOIN taxstatus ts ON t.tid = ts.tid
			SET d.scientificNameAuthorship = t.author, d.tidinterpreted = t.tid, d.family = ts.family
			WHERE (o.collid = 29) AND (d.scientificNameAuthorship IS NULL) AND (d.family IS NULL) AND (ts.taxauthid = 1)';
		if(!$this->conn->query($sql)){
			echo 'ERROR updating determination taxonomy codes: '.$sql;
		}

		//Run custom stored procedure that performs some special assignment tasks
		if(!$this->conn->query('call occurrence_harvesting_sql()')){
			echo 'ERROR running stored procedure occurrence_harvesting_sql: '.$this->conn->error;
		}


	}

	public function getTaxonGroup($collid){
		$taxonGroup = array( 5 => 'ALGAE',6 => 'ALGAE',46 => 'ALGAE', 47 => 'ALGAE', 49 => 'ALGAE', 50 => 'ALGAE',  67 => 'ALGAE', 68 => 'ALGAE', 73 => 'ALGAE',  98 => 'ALGAE', 105 => 'ALGAE',  106 => 'ALGAE', 110 => 'ALGAE',  111 => 'ALGAE',
			11 => 'BEETLE', 14 => 'BEETLE', 39 => 'BEETLE', 44 => 'BEETLE', 63 => 'BEETLE', 82 =>'BEETLE', 95 =>'BEETLE',
			20 => 'FISH', 66 => 'FISH',
			12 => 'HERPETOLOGY', 15 => 'HERPETOLOGY', 70 => 'HERPETOLOGY',
			21 => 'MACROINVERTEBRATE', 22 => 'MACROINVERTEBRATE', 45 => 'MACROINVERTEBRATE', 48 => 'MACROINVERTEBRATE', 52 => 'MACROINVERTEBRATE', 53 => 'MACROINVERTEBRATE', 55 => 'MACROINVERTEBRATE', 57 => 'MACROINVERTEBRATE', 60 => 'MACROINVERTEBRATE', 61 => 'MACROINVERTEBRATE', 62 => 'MACROINVERTEBRATE', 84 => 'MACROINVERTEBRATE', 100 => 'MACROINVERTEBRATE', 101 => 'MACROINVERTEBRATE', 102 => 'MACROINVERTEBRATE', 103 => 'MACROINVERTEBRATE',
			29 => 'MOSQUITO', 56 => 'MOSQUITO', 58 => 'MOSQUITO', 59 => 'MOSQUITO', 65 => 'MOSQUITO',
			7 => 'PLANT', 8 => 'PLANT', 9 => 'PLANT', 18 => 'PLANT', 40 => 'PLANT', 54 => 'PLANT', 107 => 'PLANT', 108 => 'PLANT', 109 => 'PLANT', 115 => 'PLANT',
			17 => 'SMALL_MAMMAL', 19 => 'SMALL_MAMMAL', 24 => 'SMALL_MAMMAL', 25 => 'SMALL_MAMMAL', 26 => 'SMALL_MAMMAL', 27 => 'SMALL_MAMMAL', 28 => 'SMALL_MAMMAL', 64 => 'SMALL_MAMMAL', 71 => 'SMALL_MAMMAL', 74 => 'SMALL_MAMMAL', 90 => 'SMALL_MAMMAL', 91 => 'SMALL_MAMMAL',
			30 => 'SOIL', 79 => 'SOIL', 80 =>'SOIL',
			75 => 'TICK', 83 => 'TICK', 116 =>'TICK'
		);
		if(array_key_exists($collid, $taxonGroup)) return $taxonGroup[$collid];
		return false;
	}

	public function getTaxonArr($sciname, $activeCollid, &$taxonArr) {
		if(substr($sciname, -4) == ' sp.') $sciname = trim(substr($sciname, 0, strlen($sciname) - 4));
		elseif(substr($sciname, -4) == ' spp.') $sciname = trim(substr($sciname, 0, strlen($sciname) - 5));
		$retArr = $this->getTaxon($sciname, $activeCollid, $taxonArr);
		if(!$retArr){
			//Parse name in case author is inbedded within taxon
			$scinameArr = TaxonomyUtil::parseScientificName($sciname, $this->conn);
			if(!empty($scinameArr['sciname'])){
				$sciname = $scinameArr['sciname'];
				if($retArr = $this->getTaxon($sciname, $activeCollid, $taxonArr)){
					if(!empty($scinameArr['author'])) $retArr['scientificNameAuthorship'] = $scinameArr['author'];
				}
			}
		}

		return $retArr;
	}

	public function translateTaxonCode($taxonCode){
		$retArr = array();
		$taxonGroup = $this->getTaxonGroup($this->activeCollid);
		$taxonCode = trim($taxonCode);
		if($taxonCode && $taxonGroup){
			if(!isset($this->taxonCodeArr[$taxonGroup][$taxonCode])){
				$tid = 0;
				$sciname = '';
				$sql = 'SELECT t.tid, t.sciName, t.author, s.family
					FROM neon_taxonomy n LEFT JOIN taxa t ON n.sciname = t.sciname
					LEFT JOIN taxstatus s ON t.tid = s.tid
					WHERE n.taxonGroup = "'.$this->cleanInStr($taxonGroup).'" AND n.taxonCode = "'.$this->cleanInStr($taxonCode).'"';
				if($rs = $this->conn->query($sql)){
					while($r = $rs->fetch_object()){
						$tid = $r->tid;
						$sciname = $r->sciName;
						$this->taxonCodeArr[$taxonGroup][$taxonCode]['tid'] = $tid;
						$this->taxonCodeArr[$taxonGroup][$taxonCode]['sciname'] = $sciname;
						$this->taxonCodeArr[$taxonGroup][$taxonCode]['author'] = $r->author;
						$this->taxonCodeArr[$taxonGroup][$taxonCode]['family'] = $r->family;
					}
					$rs->free();
				}
				else echo 'ERROR populating taxonomy codes: '.$sql;
			}
			if(isset($this->taxonCodeArr[$taxonGroup][$taxonCode])){
				$retArr['tidInterpreted'] = $this->taxonCodeArr[$taxonGroup][$taxonCode]['tid'];
				$retArr['sciname'] = $this->taxonCodeArr[$taxonGroup][$taxonCode]['sciname'];
				$retArr['scientificNameAuthorship'] = $this->taxonCodeArr[$taxonGroup][$taxonCode]['author'];
				$retArr['family'] = $this->taxonCodeArr[$taxonGroup][$taxonCode]['family'];
			}
		}
		return $retArr;
	}

	public function protectTaxonomyTest($idArr){
		$protectTaxon = false;
		if(empty($idArr['taxonPublished']) && !empty($idArr['taxonPublishedCode'])){
			if($translatedTaxaArr = $this->translateTaxonCode($idArr['taxonPublishedCode'])){
				$idArr['taxonPublished'] = $translatedTaxaArr['sciname'];
			}
		}
		if(!empty($idArr['sciname'])){
			$taxaPublishedArr = array();
			if(!empty($idArr['taxonPublished'])){
				//Run taxonPublished by taxonomic thesaurus to ensure that taxonomic author is not embedded in name
				$taxaPublishedArr = $this->getTaxonArr($idArr['taxonPublished'],$activeCollid,$taxonArr);
				if(!empty($taxaPublishedArr['sciname'])) $idArr['taxonPublished'] = $taxaPublishedArr['sciname'];
				//Taxon published does not match base taxon, thus protect taxonomy
				if( $idArr['sciname'] != $idArr['taxonPublished']) $protectTaxon = true;
			}
			if($protectTaxon){
				if(!empty($idArr['taxonPublishedCode']) && $idArr['sciname'] == $idArr['taxonPublishedCode']){
					//But taxon does match the taxonCode, thus abort taxon protections
					//We should need this, but codes are not always translated successfully
					return false;
				}
				if($taxaPublishedArr && !empty($idArr['taxonPublished'])){
					//run secondary test to ensure that names are not synonyms
					$taxaArr = $this->getTaxonArr($idArr['sciname'],$activeCollid,$taxonArr);
					if(!empty($taxaArr['accepted']) && !empty($taxaPublishedArr['accepted'])){
						if($taxaArr['sciname'] == $taxaPublishedArr['accepted'] || $taxaArr['accepted'] == $taxaPublishedArr['sciname'] || $taxaArr['accepted'] == $taxaPublishedArr['accepted']){
							//both taxa are synonyms, thus abort protections
							return false;
						}
						if($taxaArr['rankid'] <= $taxaPublishedArr['rankid']){
							//protected taxon should always have a rankid greater than published taxon, thus abort
							return false;
						}
					}
				}
			}
		}
		return $protectTaxon;
	}

	

	//Misc functions
	private function formatDate($dateStr){
		if(preg_match('/^(20\d{2})-(\d{2})-(\d{2})T\d{2}/', $dateStr)){
			//UTC datetime
			$dt = new DateTime($dateStr, new DateTimeZone('UTC'));
			$dt->setTimezone(new DateTimeZone($this->timezone));
			$dateStr = $dt->format('Y-m-d');
		}
		elseif(preg_match('/^(20\d{2})-(\d{2})-(\d{2})\D*/', $dateStr, $m)) $dateStr = $m[1].'-'.$m[2].'-'.$m[3];
		elseif(preg_match('/^(20\d{2})(\d{2})(\d{2})\D+/', $dateStr, $m)) $dateStr = $m[1].'-'.$m[2].'-'.$m[3];
		elseif(preg_match('/^(\d{1,2})\/(\d{1,2})\/(20\d{2})/', $dateStr, $m)){
			$month = $m[1];
			if(strlen($month) == 1) $month = '0'.$month;
			$day = $m[2];
			if(strlen($day) == 1) $day = '0'.$day;
			$dateStr = $m[3].'-'.$month.'-'.$day;
		}
		return $dateStr;
	}

	private function cleanInStr($str){
		$newStr = trim($str ?? '');
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	public function getErrorStr(){
		return $this->errorStr;
	}
}