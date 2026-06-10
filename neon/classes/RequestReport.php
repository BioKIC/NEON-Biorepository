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

        //   'datasetid' => isset($input['datasetid'])
        //       ? (is_array($input['datasetid']) 
        //           ? array_filter($input['datasetid']) 
        //           : array_filter(explode(',', $input['datasetid'])))
        //       : [],

        //   'state' => isset($input['state']) ? trim($input['state']) : '',
        //   'local' => isset($input['local']) ? trim($input['local']) : '',
        //   'taxa' => isset($input['taxa']) ? trim($input['taxa']) : '',

          'inquiryDateStart' => $input['inquiry-eventdate1'] ?? '',
          'inquiryDateEnd'   => $input['inquiry-eventdate2'] ?? '',
          'activeDateStart' => $input['active-eventdate1'] ?? '',
          'activeDateEnd'   => $input['active-eventdate2'] ?? '',
          'statusDateStart' => $input['status-eventdate1'] ?? '',
          'statusDateEnd'   => $input['status-eventdate2'] ?? '',

          'researcher' => isset($input['researcher'])
              ? array_filter(
                  is_array($input['researcher'])
                      ? $input['researcher']
                      : explode(',', $input['researcher'])
                )
              : [],
              
          'aiml' => $input['aiml'] ?? '',
          'internal' => $input['internal'] ?? '',
          'outreach' => $input['outreach'] ?? '',

          'catnum' => isset($input['catnum'])
              ? array_filter(
                  preg_split('/[\s,]+/', trim($input['catnum']))
                )
              : [],

          'includeothercatnum' => $input['includeothercatnum'] ?? 0,
          'includematerialsample' => $input['includematerialsample'] ?? 0,

          'collid' => isset($input['collid'])
              ? array_filter(
                  is_array($input['collid'])
                      ? $input['collid']
                      : explode(',', $input['collid'])
                )
              : [],

            'keywords' => isset($input['keywords']) ? trim($input['keywords']) : ''

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

    // properties filters

    if ($params['aiml'] !== '') {
        if ($params['aiml'] == 1) {
          $where[] = "i.usesAIML = 'yes' ";
        }
        elseif($params['aiml'] == 0) {
          $where[] = "i.usesAIML = 'no' ";
        }
    }

    if ($params['outreach'] !== '') {
        if ($params['outreach'] == 1) {
          $where[] = "i.outreach = 'yes' ";
        }
        elseif ($params['outreach'] == 0) {
          $where[] = "i.outreach = 'no' ";
        }
    }

    if ($params['internal'] !== '') {
        if ($params['internal'] == 1) {
          $where[] = "i.internal = 'yes' ";
        }
        elseif ($params['internal'] == 0) {
          $where[] = "i.internal = 'no' ";
        }
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

    // sample type filter

    if (!empty($params['collid'])) {
        $placeholders = implode(',', array_fill(0, count($params['collid']), '?'));
        
        $sql .= " 
            INNER JOIN neoncollectionrequestlink cr 
                ON cr.requestID = i.id
        ";

        $where[] = "cr.collID IN ($placeholders)";
        $binds = array_merge($binds, $params['collid']);
        $types .= str_repeat('i', count($params['collid']));
    }

    // description filter

    if (!empty($params['keywords'])) {

        $where[] = "(
            i.description LIKE ?
            OR i.secondaryResearchField LIKE ?
            OR i.primaryResearchField LIKE ?
        )";

        $keyword = '%' . $params['keywords'] . '%';

        $binds[] = $keyword;
        $binds[] = $keyword;
        $binds[] = $keyword;

        $types .= 'sss';
    }

    // catalog number filter

    if (!empty($params['catnum'])) {

        $allOccids = [];

        foreach ($params['catnum'] as $catnum) {

            $catnum = trim($catnum);

            if (!$catnum) continue;

            $occids = $this->getOccid(
                $catnum,
                $params['includeothercatnum'],
                $params['includematerialsample']
            );

            if (!empty($occids)) {
                $allOccids = array_merge($allOccids, $occids);
            }
        }

        $allOccids = array_unique($allOccids);

        if (empty($allOccids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($allOccids), '?'));

        $where[] = "s.occid IN ($placeholders)";

        $binds = array_merge($binds, $allOccids);

        $types .= str_repeat('i', count($allOccids));
    }

    // build final query

    if (!empty($where)) {
          $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " GROUP BY i.id ORDER BY i.id";
    $stmt = $this->conn->prepare($sql);

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
            'requestid' => $row['id'],
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

  private function getOccid($catNum, $othercat, $matsamp){

      $occArr = array();
      $catNum = $this->cleanInStr($catNum);

      $sql = 'SELECT DISTINCT o.occid
              FROM omoccurrences o ';

      $where = [];

      if($othercat == 1){
          $sql .= 'LEFT JOIN omoccuridentifiers i 
                  ON o.occid = i.occid ';

          $where[] = 'o.othercatalognumbers = "'.$catNum.'"';
          $where[] = 'i.identifierValue = "'.$catNum.'"';
      }

      if($matsamp == 1){
          $sql .= 'LEFT JOIN ommaterialsample m 
                  ON o.occid = m.occid ';

          $where[] = 'm.catalogNumber = "'.$catNum.'"';
          $where[] = 'm.recordID = "'.$catNum.'"';
      }

      $where[] = 'o.catalognumber = "'.$catNum.'"';
      $where[] = 'o.occid = "'.$catNum.'"';

      if(!empty($where)){

          $sql .= ' WHERE (' . implode(' OR ', $where) . ')';

          $rs = $this->conn->query($sql);

          if($rs){
              while($r = $rs->fetch_object()) {
                  $occArr[] = $r->occid;
              }
              $rs->free();
          }
      }

      return $occArr;
  }

  // Export inquiry list

public function exportInquiryList($ids){

    $idArray = array_map('intval', explode(',', $ids));

    if(empty($idArray)){
        die('No IDs provided');
    }

    $placeholders = implode(',', array_fill(0, count($idArray), '?'));

    $sql = "
        SELECT r.id,
        p.name as primaryContact,
        r.inquiryDate,
        r.status,
        r.title,
        r.funded,
        r.cut as fundingCut,
        r.fundingSource,
        r.primaryResearchField,
        r.secondaryResearchField as keywords,
        r.description,
        r.dataProduced,
        r.howFoundUs,
        r.existingSamples,
        r.futureSamples,
        r.generatingSamples,
        r.folderName,
        r.pendingFundingDate,
        r.notFundedDate,
        r.pendingFulfillmentDate,
        r.pendingSampleListDate,
        r.activeDate,
        r.completeDate,
        r.processing,
        r.moreThan100,
        r.internal,
        r.outreach,
        r.usesAIML,
        r.datasetID,
        r.followUpType,
        r.followUpDate,
        r.lastUpdated
        FROM neonrequest r
        JOIN neonresearcher p
        ON r.researcherID = p.researcherID
        WHERE r.id IN ($placeholders)
    ";

      $stmt = $this->conn->prepare($sql);

      if(!$stmt){
          die('SQL prepare failed: ' . $this->conn->error);
      }

      $types = str_repeat('i', count($idArray));

      $stmt->bind_param($types, ...$idArray);

      if(!$stmt->execute()){
          die('Query execution failed: ' . $stmt->error);
      }

      $result = $stmt->get_result();

      if(!$result){
          die('Result retrieval failed: ' . $stmt->error);
      }

      $fileName = 'inquiryExport_' . date('Y-m-d_H-i-s') . '.csv';

      header('Content-Type: text/csv; charset=utf-8');
      header('Content-Disposition: attachment; filename="' . $fileName . '"');
      header('Pragma: no-cache');
      header('Expires: 0');

      $output = fopen('php://output', 'w');

      $firstRow = $result->fetch_assoc();

      if($firstRow){
          fputcsv($output, array_keys($firstRow));
          fputcsv($output, $firstRow);

          while($row = $result->fetch_assoc()){
              fputcsv($output, $row);
          }
      }

      fclose($output);
      $stmt->close();
      exit;
  }
  // sample export
  public function exportSampleList($ids){

    $idArray = array_map('intval', explode(',', $ids));

    if(empty($idArray)){
        die('No IDs provided');
    }

    $placeholders = implode(',', array_fill(0, count($idArray), '?'));

    $sql = "
        SELECT r.id as requestID,
        s.occid, m.sampleID, m.sampleCode, m.sampleClass,
        s.status,s.useType,s.substanceProvided,s.available,s.notes,
        s.shipmentID,
        s.initialTimestamp,
        s.editedTimestamp
        FROM neonsamplerequestlink s
        JOIN neonrequest r
        ON s.requestID = r.id
        JOIN NeonSample m
        ON s.occid = m.occid
        WHERE r.id IN ($placeholders)
    ";

      $stmt = $this->conn->prepare($sql);

      if(!$stmt){
          die('SQL prepare failed: ' . $this->conn->error);
      }

      $types = str_repeat('i', count($idArray));

      $stmt->bind_param($types, ...$idArray);

      if(!$stmt->execute()){
          die('Query execution failed: ' . $stmt->error);
      }

      $result = $stmt->get_result();

      if(!$result){
          die('Result retrieval failed: ' . $stmt->error);
      }

      $fileName = 'sampleExport_' . date('Y-m-d_H-i-s') . '.csv';

      header('Content-Type: text/csv; charset=utf-8');
      header('Content-Disposition: attachment; filename="' . $fileName . '"');
      header('Pragma: no-cache');
      header('Expires: 0');

      $output = fopen('php://output', 'w');

      $firstRow = $result->fetch_assoc();

      if($firstRow){
          fputcsv($output, array_keys($firstRow));
          fputcsv($output, $firstRow);

          while($row = $result->fetch_assoc()){
              fputcsv($output, $row);
          }
      }

      fclose($output);
      $stmt->close();
      exit;
  }

  // occurrence export
  public function exportOccurrenceList($ids){

    $idArray = array_map('intval', explode(',', $ids));

    if(empty($idArray)){
        die('No IDs provided');
    }

    $placeholders = implode(',', array_fill(0, count($idArray), '?'));

    $sql = "
        SELECT r.id as requestID, m.*
        FROM neonsamplerequestlink s
        JOIN neonrequest r
        ON s.requestID = r.id
        JOIN omoccurrences m
        ON s.occid = m.occid
        WHERE r.id IN ($placeholders)
    ";

      $stmt = $this->conn->prepare($sql);

      if(!$stmt){
          die('SQL prepare failed: ' . $this->conn->error);
      }

      $types = str_repeat('i', count($idArray));

      $stmt->bind_param($types, ...$idArray);

      if(!$stmt->execute()){
          die('Query execution failed: ' . $stmt->error);
      }

      $result = $stmt->get_result();

      if(!$result){
          die('Result retrieval failed: ' . $stmt->error);
      }

      $fileName = 'occurrenceExport_' . date('Y-m-d_H-i-s') . '.csv';

      header('Content-Type: text/csv; charset=utf-8');
      header('Content-Disposition: attachment; filename="' . $fileName . '"');
      header('Pragma: no-cache');
      header('Expires: 0');

      $output = fopen('php://output', 'w');

      $firstRow = $result->fetch_assoc();

      if($firstRow){
          fputcsv($output, array_keys($firstRow));
          fputcsv($output, $firstRow);

          while($row = $result->fetch_assoc()){
              fputcsv($output, $row);
          }
      }

      fclose($output);
      $stmt->close();
      exit;
  }

  // material sample export
  public function exportMaterialSampleList($ids){

    $idArray = array_map('intval', explode(',', $ids));

    if(empty($idArray)){
        die('No IDs provided');
    }

    $placeholders = implode(',', array_fill(0, count($idArray), '?'));

    $sql = "SELECT r.id as requestID, m.matSampleID,
        s.occid, m.catalogNumber, m.guid,
        s.status,s.useType,s.sampleType,s.notes,
        s.shipmentID,
        s.initialTimestamp,
        s.editedTimestamp
        FROM neonmaterialsamplerequestlink s
        JOIN neonrequest r
        ON s.requestID = r.id
        JOIN ommaterialsample m
        ON s.matSampleID = m.matSampleID
        WHERE r.id IN ($placeholders)";

      $stmt = $this->conn->prepare($sql);

      if(!$stmt){
          die('SQL prepare failed: ' . $this->conn->error);
      }

      $types = str_repeat('i', count($idArray));

      $stmt->bind_param($types, ...$idArray);

      if(!$stmt->execute()){
          die('Query execution failed: ' . $stmt->error);
      }

      $result = $stmt->get_result();

      if(!$result){
          die('Result retrieval failed: ' . $stmt->error);
      }

      $fileName = 'materialSampleExport_' . date('Y-m-d_H-i-s') . '.csv';

      header('Content-Type: text/csv; charset=utf-8');
      header('Content-Disposition: attachment; filename="' . $fileName . '"');
      header('Pragma: no-cache');
      header('Expires: 0');

      $output = fopen('php://output', 'w');

      $firstRow = $result->fetch_assoc();

      if($firstRow){
          fputcsv($output, array_keys($firstRow));
          fputcsv($output, $firstRow);

          while($row = $result->fetch_assoc()){
              fputcsv($output, $row);
          }
      }

      fclose($output);
      $stmt->close();
      exit;
  }

}
?>