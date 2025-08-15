<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class InquiriesList extends Manager{

	function __construct() {
		parent::__construct(null,'write');
	}

	function __destruct(){
		parent::__destruct();
	}

  // Gets all inquiries
  public function getInquiriesOut(){
  	$dataArr = array();
    $sql = 'SELECT
r.id, p.name AS researcher, DATE(r.inquiry_date) AS date, r.title, r.status, COUNT(s.occid) AS samples FROM neonrequest AS r LEFT JOIN neonresearcher AS p ON r.researcher_id = p.researcher_id LEFT JOIN neonsamplerequestlink AS s ON r.id = s.request_id GROUP BY r.id;';
    if($result = $this->conn->query($sql)){
      while($row = $result->fetch_assoc()){
        $dataArr[] = array(
          'id' => '<a href="../inquiryform.php?id='.$row['id'].'">'.$row['id'].'</a>',
          'researcher' => is_null($row['researcher'])?'<span style="color:lightgray;">NULL</span>':$row['researcher'],
          'date' => is_null($row['date'])?'<span style="color:lightgray;">NULL</span>':$row['date'],
          'title' => is_null($row['title'])?'<span style="color:lightgray;">NULL</span>':$row['title'],
          'status' => is_null($row['status'])?'<span style="color:lightgray;">NULL</span>':$row['status'],
          'samples' => is_null($row['samples'])?'<span style="color:lightgray;">NULL</span>':$row['samples'],
        );
      }
      $result->free();
    }
    else {
      $this->errorMessage = 'Inquiry query was not successfull';
      $dataArr = false;
    }
    return $dataArr;
  }

  // Gets count of all samples in inquiry
  public function getInqSamplesCnt(){
    $retArr = array();
    $sql = 'SELECT COUNT(occid) AS totalOut FROM neonsamplerequestlink';
    if($result = $this->conn->query($sql)){
      while($row = $result->fetch_assoc()){
        $totalInq = $row['totalOut'];
      }
      $result->free();
    }
    else {
      $this->errorMessage = 'Inquiry query was not successfull';
      $totalInq = false;
    }
    return $totalInq;
  }
}
?>