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

  public function getResearchers(){
      $retArr = array();

      $sql = 'SELECT researcherID, name, institution
              FROM neonresearcher 
              ORDER BY name';

      $rs = $this->conn->query($sql);

      while($r = $rs->fetch_object()){
          $name = $this->cleanOutStr($r->name);
          $institution = $this->cleanOutStr($r->institution);

          $display = $institution ? "$name ($institution)" : $name;

          $retArr[$r->researcherID] = $display;
      }

      $rs->free();
      return $retArr;
  }


  // normalize search parameters

  public function normalizeParams($input){

      return [
          'status' => isset($input['status']) && is_array($input['status'])
              ? array_filter($input['status'])
              : [],

          'datasetid' => isset($input['datasetid'])
              ? (is_array($input['datasetid']) 
                  ? array_filter($input['datasetid']) 
                  : array_filter(explode(',', $input['datasetid'])))
              : [],

          'state' => isset($input['state']) ? trim($input['state']) : '',
          'local' => isset($input['local']) ? trim($input['local']) : '',
          'taxa' => isset($input['taxa']) ? trim($input['taxa']) : '',

          'inquiryDateStart' => $input['inquiry-eventdate1'] ?? '',
          'inquiryDateEnd'   => $input['inquiry-eventdate2'] ?? '',
          'activeDateStart' => $input['active-eventdate1'] ?? '',
          'activeDateEnd'   => $input['active-eventdate2'] ?? '',
          'statusDateStart' => $input['status-eventdate1'] ?? '',
          'statusDateEnd'   => $input['status-eventdate2'] ?? '',

          'researcher' => isset($input['researcher']) 
              ? (is_array($input['researcher'])
                  ? $input['researcher']
                  : explode(',', $input['researcher']))
              : [],      
       ];
  }

  // filter inquiries based on search
  public function filterSearchInquiries($rawParams){

    $params = $this->normalizeParams($rawParams);

    $sql = "SELECT DISTINCT
                i.id,
                r.name,
                i.inquiryDate,
                i.title,
                i.status,
                COUNT(s.occid) as samples
            FROM neonrequest i
            LEFT JOIN neonsamplerequestlink s 
            ON s.requestID = i.id
            LEFT JOIN neonresearcher r 
            ON i.researcherID = r.researcherID";

    $where = [];
    $binds = [];
    $types = '';

    // status filter
    if (!empty($params['status'])) {
        $placeholders = implode(',', array_fill(0, count($params['status']), '?'));
        $where[] = "i.status IN ($placeholders)";
        $binds = array_merge($binds, $params['status']);
        $types .= str_repeat('s', count($params['status']));
    }

    // date filters
    if (!empty($params['inquiryDateStart']) && !empty($params['inquiryDateEnd'])) {
        $where[] = "DATE(i.inquiryDate) BETWEEN ? AND ?";
        $binds[] = $params['inquiryDateStart'];
        $binds[] = $params['inquiryDateEnd'];
        $types .= 'ss';
    }

    if (!empty($params['activeDateStart']) && !empty($params['activeDateEnd'])) {
        $where[] = "DATE(i.activeDate) BETWEEN ? AND ?";
        $binds[] = $params['activeDateStart'];
        $binds[] = $params['activeDateEnd'];
        $types .= 'ss';
    }

    if (!empty($params['statusDateStart']) && !empty($params['statusDateEnd'])) {
        $where[] = "DATE(i.statusDate) BETWEEN ? AND ?";
        $binds[] = $params['statusDateStart'];
        $binds[] = $params['statusDateEnd'];
        $types .= 'ss';
    }

    // researcher filter

    if (!empty($params['researcher'])) {
        $placeholders = implode(',', array_fill(0, count($params['researcher']), '?'));
        
        $sql .= " 
            INNER JOIN neonresearcherrequestlink rr 
                ON rr.requestID = i.id
        ";

        $where[] = "rr.researcherID IN ($placeholders)";
        $binds = array_merge($binds, $params['researcher']);
        $types .= str_repeat('i', count($params['researcher']));
    }

    // build final query

    if (!empty($where)) {
          $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " GROUP BY i.id ORDER BY i.id";
    $stmt = $this->conn->prepare($sql);

    print_r($sql);

    if (!$stmt) {
        $this->errorMessage = $this->conn->error;
        return false;
    }

    if (!empty($binds)) {
        $stmt->bind_param($types, ...$binds);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $dataArr = [];

    while ($row = $result->fetch_assoc()) {
        $dataArr[] = array(
            'id' => '<a href="../requests/inquiryform.php?id='.$row['id'].'">'.$row['id'].'</a>',
            'researcher' => is_null($row['name'])?'<span style="color:lightgray;">NULL</span>':$row['name'],
            'date' => is_null($row['inquiryDate'])?'<span style="color:lightgray;">NULL</span>':$row['inquiryDate'],
            'title' => is_null($row['title'])?'<span style="color:lightgray;">NULL</span>':$row['title'],
            'status' => is_null($row['status'])?'<span style="color:lightgray;">NULL</span>':$row['status'],
            'samples' => is_null($row['samples'])?'<span style="color:lightgray;">NULL</span>':$row['samples'],
        );
    }

    $stmt->close();
    return $dataArr;
  }

}
?>