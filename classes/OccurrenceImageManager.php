<?php
include_once($SERVER_ROOT.'/classes/OccurrenceManager.php');

class OccurrenceImageManager extends OccurrenceManager{

	private $recordCount = 0;

	function __construct($type = 'readonly') {
		parent::__construct($type);
	}

	function __destruct(){
		parent::__destruct();
	}

	public function getImageArr($pageRequest, $cntPerPage){
		$retArr = Array();
		$sql = 'SELECT m.mediaID, m.tid, o.catalogNumber, o.sciname, m.url, m.thumbnailurl, m.originalurl, m.creatorUid, m.caption, m.occid, m.mediaType
			FROM omoccurrences o INNER JOIN media m ON o.occid = m.occid ';
		$sqlWhere = $this->getSqlWhere();
		if(!$this->recordCount || $this->reset) $this->setRecordCnt($sqlWhere);
		$sql .= $this->getTableJoins($sqlWhere);
		$sql .= $sqlWhere;
		$sql .= 'ORDER BY o.sciname ';
		$bottomLimit = ($pageRequest - 1)*$cntPerPage;
		$sql .= 'LIMIT '.$bottomLimit.','.$cntPerPage;
		//echo '<div>Spec sql: '.$sql.'</div>';
		if($rs = $this->conn->query($sql)){
			$mediaID = 0;
			while($r = $rs->fetch_object()){
				if($mediaID == $r->mediaID) continue;
				$mediaID = $r->mediaID;
				$retArr[$mediaID]['mediaID'] = $mediaID;
				$retArr[$mediaID]['tid'] = $r->tid;
				$retArr[$mediaID]['catnum'] = $r->catalogNumber;
				$retArr[$mediaID]['sciname'] = $r->sciname;
				$retArr[$mediaID]['url'] = $r->url;
				$retArr[$mediaID]['thumbnailurl'] = $r->thumbnailurl;
				$retArr[$mediaID]['originalurl'] = $r->originalurl;
				$retArr[$mediaID]['uid'] = $r->creatorUid;
				$retArr[$mediaID]['caption'] = $r->caption;
				$retArr[$mediaID]['occid'] = $r->occid;

			}
			$rs->free();
		}
		return $retArr;
	}

	private function setRecordCnt($sqlWhere){
		$sql = 'SELECT COUNT(m.mediaID) AS cnt FROM omoccurrences o INNER JOIN media m ON o.occid = m.occid ';
		$sql .= $this->getTableJoins($sqlWhere);
		$sql .= $sqlWhere;
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$this->recordCount = $r->cnt;
		}
		$rs->free();
	}

	public function getRecordCnt(){
		return $this->recordCount;
	}
}
?>
