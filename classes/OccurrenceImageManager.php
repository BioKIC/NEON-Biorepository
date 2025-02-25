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
		$sql = 'SELECT i.imgid, o.tidInterpreted, o.sciname, i.url, i.thumbnailurl, i.originalurl, i.photographeruid, i.caption, i.occid
			FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid ';
		$sqlWhere = $this->getSqlWhere();
		if(!$this->recordCount || $this->reset) $this->setRecordCnt($sqlWhere);
		$sql .= $this->getTableJoins($sqlWhere);
		$sql .= $sqlWhere;
		$sql .= 'ORDER BY o.sciname ';
		$bottomLimit = ($pageRequest - 1)*$cntPerPage;
		$sql .= 'LIMIT '.$bottomLimit.','.$cntPerPage;
		//echo '<div>Spec sql: '.$sql.'</div>';
		$rs = $this->conn->query($sql);
		$imgId = 0;
		while($r = $rs->fetch_object()){
			if($imgId == $r->imgid) continue;
			$imgId = $r->imgid;
			$retArr[$imgId]['imgid'] = $r->imgid;
			$retArr[$imgId]['tid'] = $r->tidInterpreted;
			$retArr[$imgId]['sciname'] = $r->sciname;
			$retArr[$imgId]['url'] = $r->url;
			$retArr[$imgId]['thumbnailurl'] = $r->thumbnailurl;
			$retArr[$imgId]['originalurl'] = $r->originalurl;
			$retArr[$imgId]['uid'] = $r->photographeruid;
			$retArr[$imgId]['caption'] = $r->caption;
			$retArr[$imgId]['occid'] = $r->occid;

		}
		$rs->free();
		return $retArr;
	}

	private function setRecordCnt($sqlWhere){
		$sql = 'SELECT COUNT(i.imgid) AS cnt FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid ';
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
