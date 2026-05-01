<?php

  include_once($SERVER_ROOT.'/classes/Manager.php');

 /**
 * Controler class for /neon/classes/RequestReport.php
 *
 */

 class RequestReportManager extends Manager {

  public function __construct() {
    parent::__construct(null,'readonly');
    $this->verboseMode = 2;
    set_time_limit(2000);
  }

  public function __destruct() {
    parent::__destruct();
  }

  // requests by status
  public function getRequestsByStatus(){
    $dataArr = array();

    $sql = 'SELECT 
            status, COUNT(*) AS count
            FROM neonrequest
            GROUP BY status;';
    $result = $this->conn->query($sql);

    if ($result) {
      while ($row = $result->fetch_assoc()){
        $dataArr[] = array(
          $row['status'],
          $row['count'],
        );
      }
      $totalsRow = array("status" => "Total", "count" => array_sum(array_column($dataArr, 1)));
      $dataArr[] = $totalsRow; 
      $result->free();
    }
    else {
      $this->errorMessage = 'Requests report query was not successfull';
      $dataArr = false;
    }
    return $dataArr;
  }

  public function getInquiriesOut(){
  	$dataArr = array();
    $sql = 'SELECT r.id, p.name AS researcher, DATE(r.inquiryDate) AS date, r.title, r.status, COUNT(s.occid) AS samples FROM neonrequest AS r LEFT JOIN neonresearcher AS p ON r.researcherID = p.researcherID LEFT JOIN neonsamplerequestlink AS s ON r.id = s.requestID GROUP BY r.id;';
    if($result = $this->conn->query($sql)){
      while($row = $result->fetch_assoc()){
        $dataArr[] = array(
          'id' => '<a href="../requests/inquiryform.php?id='.$row['id'].'">'.$row['id'].'</a>',
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

    // Gets inquiry statuses
  public function getStatuses(){
    $dataArr = array();

    $sql = 'SELECT DISTINCT(status) AS status FROM neonrequest;';

    $result = $this->conn->query($sql);

    while ($row = $result->fetch_assoc()){
      $dataArr[] = $row;
    }
    $result->free(); 
    return $dataArr;
  }

  // Search inquiries
  public function getSearchInquiries($params){

    $sql = "SELECT 
                i.id,
                i.researcherID,
                i.inquiryDate,
                i.title,
                i.status,
                COUNT(s.id) as samples
            FROM neonrequest i
            LEFT JOIN neonsamplerequestlink s ON s.requestID = i.id";

    $where = [];
    $binds = [];

    // -------------------------
    // REQUEST-LEVEL FILTERS
    // -------------------------
    if(!empty($params['status'])){
        $statusArr = explode(',', $params['status']);
        $where[] = "i.status IN (" . $this->placeholders($statusArr) . ")";
        $binds = array_merge($binds, $statusArr);
    }

    // -------------------------
    // SAMPLE-LEVEL FILTERS
    // -------------------------
    if(!empty($params['state'])){
        $where[] = "s.state IN (" . $this->placeholders(explode(',', $params['state'])) . ")";
        $binds = array_merge($binds, explode(',', $params['state']));
    }

    if(!empty($params['datasetid'])){
        $ids = explode(',', $params['datasetid']);
        $where[] = "s.datasetid IN (" . $this->placeholders($ids) . ")";
        $binds = array_merge($binds, $ids);
    }

    if(!empty($params['scientificName'])){
        $where[] = "s.scientificName LIKE ?";
        $binds[] = "%" . $params['scientificName'] . "%";
    }

    // -------------------------
    // FINALIZE QUERY
    // -------------------------
    if(count($where)){
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " GROUP BY i.id ORDER BY i.id";

    $stmt = $this->conn->prepare($sql);

    if (!$stmt) {
        $this->errorMessage = $this->conn->error;
        return false;
    }

    if (!empty($binds)) {
        $types = str_repeat('s', count($binds));
        $stmt->bind_param($types, ...$binds);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $dataArr = [];

    while ($row = $result->fetch_assoc()) {
        $dataArr[] = $row;
    }

    $stmt->close();

    return $dataArr;
    }

}
?>