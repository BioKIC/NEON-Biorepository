<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class InquiriesManager extends Manager{

	function __construct() {
		parent::__construct(null,'write');
	}

	function __destruct(){
		parent::__destruct();
	}


    private $errorStr = '';

    public function getErrorStr() {
        return $this->errorMessage ?? '';
    }


  // Gets all inquiries
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

  // Get managers list
  public function getManagers(){
      $retArr = array();

      $sql = 'SELECT u.uid, u.firstName, u.lastName
              FROM users u
              LEFT JOIN userroles r
              ON u.uid=r.uid
              WHERE r.role = "SuperAdmin"
              ORDER BY u.firstName';

      $rs = $this->conn->query($sql);

      while($r = $rs->fetch_object()){
          $firstName = $this->cleanOutStr($r->firstName);
          $lastName = $this->cleanOutStr($r->lastName);

          $display = trim($r->firstName . ' ' . $r->lastName);

          $retArr[$r->uid] = $display;
      }

      $rs->free();
      return $retArr;
  }

    // Get collections list
public function getCollections(){
    $retArr = array();

    $sql = 'SELECT collID, collectionName
            FROM omcollections
            ORDER BY collectionName';

    $rs = $this->conn->query($sql);

    while($r = $rs->fetch_object()){
        $collectionName = $this->cleanOutStr($r->collectionName);

        $display = trim($collectionName);

        $retArr[$r->collID] = $display;
    }

    $rs->free();
    return $retArr;
}


  // Get researchers list
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

// Get primary research fields list
public function getFields(){
    $retArr = array();

    $sql = 'SELECT DISTINCT primaryResearchField 
            FROM neonrequest 
            WHERE primaryResearchField IS NOT NULL 
            AND primaryResearchField <> "" 
            ORDER BY primaryResearchField';

    $rs = $this->conn->query($sql);

    while($r = $rs->fetch_object()){
        $field = $this->cleanOutStr($r->primaryResearchField);
        $display = trim($field);

        $retArr[$field] = $display;
    }

    $rs->free();
    return $retArr;
}

  // Get material sample types
  public function getMaterialSampleTypes(){
      $retArr = array();

      $sql = 'SELECT term
              FROM ctcontrolvocabterm
              WHERE cvID = 3
              ORDER BY term';

      $rs = $this->conn->query($sql);

      while($r = $rs->fetch_object()){
        $term = $this->cleanOutStr($r->term);
        $display = trim($term);

        $retArr[$term] = $display;
      }

      $rs->free();
      return $retArr;
  }

// Get options for how researcher found us 
public function getHowFoundUs(){
    $retArr = array();

    $sql = 'SELECT DISTINCT howFoundUs 
            FROM neonrequest 
            WHERE howFoundUs  IS NOT NULL 
            AND howFoundUs  <> "" 
            ORDER BY howFoundUs ';

    $rs = $this->conn->query($sql);

    while($r = $rs->fetch_object()){
        $howFoundUs  = $this->cleanOutStr($r->howFoundUs );
        $display = trim($howFoundUs);

        $retArr[$howFoundUs] = $display;
    }

    $rs->free();
    return $retArr;
}


  // add researcher to neonresearcher table
  public function addResearcher($name, $institution, $contactEmail = '', $address = '', $phone = '') {
      // Only name and institution are required
      if (empty($name) || empty($institution)) {
          $this->errorMessage = "Name and institution are required.";
          return false;
      }

      $name = $this->conn->real_escape_string($name);
      $institution = $this->conn->real_escape_string($institution);
      $contactEmail = $this->conn->real_escape_string($contactEmail);
      $address = $this->conn->real_escape_string($address);
      $phone = $this->conn->real_escape_string($phone);

      $sql = "INSERT INTO neonresearcher (name, institution, contactEmail, address, phone) 
              VALUES ('$name', '$institution', '$contactEmail', '$address', '$phone')";

      if ($this->conn->query($sql)) {
          return $this->conn->insert_id; 
      } else {
          $this->errorMessage = "Database Error: " . $this->conn->error;
          return false;
      }
  }

   public function addInquiry($collectionManager, $researcherID, $inquiryDate,$title,$collections,$field,$secondaryfields,$funded,$fundingsource,$description,$howfound,$dataproduced,$existing,$future,$new,$additionalresearchers,$drivefolder,$aiml,$internal,$outreach,$processing) {

    $collectionManager = (int) $collectionManager;
    $researcherID = (int) $researcherID;
    $title = $this->conn->real_escape_string($title);
    $inquiryDate = $this->conn->real_escape_string($inquiryDate);
    $field = $this->conn->real_escape_string($field);
    $secondaryfields = $this->conn->real_escape_string($secondaryfields);
    $funded = $this->conn->real_escape_string($funded);
    $fundingsource = $this->conn->real_escape_string($fundingsource);
    $description = $this->conn->real_escape_string($description);
    $howfound = $this->conn->real_escape_string($howfound);
    $dataproduced = $this->conn->real_escape_string($dataproduced);
    $existing = $this->conn->real_escape_string($existing);
    $future = $this->conn->real_escape_string($future);
    $new = $this->conn->real_escape_string($new);
    $drivefolder = $this->conn->real_escape_string($drivefolder);
    $aiml = $this->conn->real_escape_string($aiml);
    $internal = $this->conn->real_escape_string($internal);
    $outreach = $this->conn->real_escape_string($outreach);
    $processing = $this->conn->real_escape_string($processing);


    $sql = "INSERT INTO neonrequest 
        (collectionManager, inquiryDate, researcherID, status, title, primaryResearchField, secondaryResearchField, funded, fundingSource, description, howFoundUs, dataProduced, existingSamples, futureSamples, generatingSamples, folderName,usesAIML, internal,cut,processing,outreach,lastUpdated) 
        VALUES 
        ('$collectionManager', '$inquiryDate','$researcherID', 'sample inquiry', '$title', '$field', '$secondaryfields', '$funded', '$fundingsource', '$description', '$howfound', '$dataproduced', '$existing', '$future', '$new', '$drivefolder','$aiml','$internal','no','$processing','$outreach', NOW())";

    if ($this->conn->query($sql)) {
        $requestID = $this->conn->insert_id;

        if (!is_array($additionalresearchers)) {
            $additionalresearchers = [$additionalresearchers];
        }

        $allResearchers = array_merge([$researcherID], $additionalresearchers);


        // call link functions here
        $this->addResearcherInquiryLink($requestID, $allResearchers);
        $this->addCollectionInquiryLink($requestID, $collections);

        return $requestID; 
    } else {
        $this->errorMessage = "Database Error: " . $this->conn->error;
        return false;
    }
}

// add researcher request link
public function addResearcherInquiryLink($requestID, $allResearchers) {

    $requestID = $this->conn->real_escape_string($requestID);
    if (!is_array($allResearchers)) {
        $allResearchers = [$allResearchers]; 
    }
    
    foreach ($allResearchers as $rid) {
        $rid = $this->conn->real_escape_string((string)$rid);

        $sql = "INSERT INTO neonresearcherrequestlink (requestID, researcherID) 
                VALUES ('$requestID', '$rid')";

        if (!$this->conn->query($sql)) {
            $this->errorMessage = "Database Error: " . $this->conn->error;
            return false;
        }
    }
}

// add collections request link
public function addCollectionInquiryLink($requestID, $collections) {
    $requestID = $this->conn->real_escape_string($requestID);

    if (!is_array($collections)) {
        $collections = [$collections]; 
    }

    foreach ($collections as $collid) {
        $collid = $this->conn->real_escape_string((string)$collid);

        $sql = "INSERT INTO neoncollectionrequestlink (requestID, collID) 
                VALUES ('$requestID', '$collid')";

        if (!$this->conn->query($sql)) {
            $this->errorMessage = "Database Error: " . $this->conn->error;
            return false;
        }
    }

    return true;
}

    // get basic record data for a given request
    public function getInquiryDataByID($requestID) {
        $requestID = (int)$requestID;

        $sql = "SELECT * FROM neonrequest WHERE id = $requestID";
        $rs = $this->conn->query($sql);

        if (!$rs) {
            $this->errorMessage = "Database Error: " . $this->conn->error;
            return false;
        }

        if ($rs->num_rows === 0) {
            $this->errorMessage = "No inquiry found with ID $requestID";
            return false;
        }

        $row = $rs->fetch_assoc();
        $rs->free();

        return $row; 
    }

      // get researchers for a given request
      public function getResearchersByID($requestID) {
          $requestID = (int)$requestID;

          $sql = "SELECT r.researcherID, CONCAT(r.name,' (',r.institution,')') as researcher
                  FROM neonresearcherrequestlink l
                  LEFT JOIN neonresearcher r
                      ON l.researcherID = r.researcherID
                  WHERE l.requestID = $requestID";
          $rs = $this->conn->query($sql);

          if (!$rs) {
              $this->errorMessage = "Database Error: " . $this->conn->error;
              return false;
          }

          $researchers = [];
            while ($row = $rs->fetch_assoc()) {
                $researchers[$row['researcherID']] = $row['researcher'];
            }
            $rs->free();
         return $researchers;

      }

      // get *additional* researchers for a given request
      public function getAdditionalResearchersByID($requestID) {
          $requestID = (int)$requestID;

          $sql = "SELECT r.researcherID, CONCAT(r.name,' (',r.institution,')') as researcher
                  FROM neonresearcherrequestlink l
                  LEFT JOIN neonresearcher r
                      ON l.researcherID = r.researcherID
                  LEFT JOIN neonrequest s
                      ON l.requestID = s.id
                  WHERE l.requestID = $requestID
                  AND l.researcherID != s.researcherID";
          $rs = $this->conn->query($sql);

          if (!$rs) {
              $this->errorMessage = "Database Error: " . $this->conn->error;
              return false;
          }

          $researchers = [];
          while ($row = $rs->fetch_assoc()) {
              $researchers[$row['researcherID']] = $row['researcher'];
          }
          $rs->free();

          return $researchers;
      }


  // get collections for a given request
  public function getCollectionsByID($requestID) {
      $requestID = (int)$requestID;

      $sql = "SELECT l.collID AS collID, c.collectionName
              FROM neoncollectionrequestlink l
              LEFT JOIN omcollections c
              ON l.collID = c.collID
              WHERE l.requestID = $requestID";

      $rs = $this->conn->query($sql);
      if (!$rs) {
          $this->errorMessage = "Database Error: " . $this->conn->error;
          return false;
      }

      $collections = [];
      while ($row = $rs->fetch_assoc()) {
          $collections[$row['collID']] = $row['collectionName'];
      }

      $rs->free();
      return $collections;
  }


  // get cm for a given request
  public function getCMByID($requestID) {
      $requestID = (int)$requestID;

      $sql = "SELECT u.uid,r.collectionManager,concat(u.firstName, ' ',u.lastName) as name FROM neonrequest r
              LEFT JOIN  users u
              ON r.collectionManager = u.uid
              WHERE r.id = $requestID";
      $rs = $this->conn->query($sql);

      if (!$rs) {
          $this->errorMessage = "Database Error: " . $this->conn->error;
          return false;
      }

      if ($rs->num_rows === 0) {
          $this->errorMessage = "No inquiry found with ID $requestID";
          return false;
      }

      $row = $rs->fetch_assoc();
      $rs->free();

      return $row; 
  }

  // get cm for a given request
  public function getPrimaryContactByID($requestID) {
      $requestID = (int)$requestID;

      $sql = "SELECT p.researcherID,p.name,p.institution FROM neonrequest r
              LEFT JOIN  neonresearcher p
              ON r.researcherID = p.researcherID
              WHERE r.id = $requestID";
      $rs = $this->conn->query($sql);

      if (!$rs) {
          $this->errorMessage = "Database Error: " . $this->conn->error;
          return false;
      }

      if ($rs->num_rows === 0) {
          $this->errorMessage = "No inquiry found with ID $requestID";
          return false;
      }

      $row = $rs->fetch_assoc();
      $rs->free();

      return $row; 
  }

  public function clearResearcherInquiryLink($requestID) {
      $requestID = (int)$requestID; 

      $sql = "DELETE FROM neonresearcherrequestlink WHERE requestID = $requestID";
      
      if (!$this->conn->query($sql)) {
          $this->errorMessage = "Database Error: " . $this->conn->error;
          return false;
      }

      return true; 
  }

  public function clearCollectionInquiryLink($requestID) {
      $requestID = (int)$requestID; 

      $sql = "DELETE FROM neoncollectionrequestlink WHERE requestID = $requestID";
      
      if (!$this->conn->query($sql)) {
          $this->errorMessage = "Database Error: " . $this->conn->error;
          return false;
      }

      return true; 
  }



  public function editInquiry(
      $requestID,
      $collectionManager,
      $researcherID,
      $title,
      $collections,
      $field,
      $secondaryfields,
      $funded,
      $fundingsource,
      $description,
      $howfound,
      $dataproduced,
      $existing,
      $future,
      $new,
      $additionalresearchers,
      $drivefolder,
      $aiml,
      $internal,
      $outreach,
      $processing,
      $uid 
  ) {
      $requestID = (int)$requestID;

      $oldSql = "SELECT * FROM neonrequest WHERE id = ?";
      $oldStmt = $this->conn->prepare($oldSql);
      $oldStmt->bind_param("i", $requestID);
      $oldStmt->execute();
      $oldResult = $oldStmt->get_result();
      $oldData = $oldResult->fetch_assoc();
      $oldStmt->close();

      if (!$oldData) {
          $this->errorMessage = "Inquiry not found.";
          return false;
      }

      $sql = "UPDATE neonrequest 
              SET collectionManager = ?, 
                  researcherID = ?, 
                  title = ?, 
                  primaryResearchField = ?, 
                  secondaryResearchField = ?, 
                  funded = ?, 
                  fundingSource = ?, 
                  description = ?, 
                  howFoundUs = ?, 
                  dataProduced = ?, 
                  existingSamples = ?, 
                  futureSamples = ?, 
                  generatingSamples = ?, 
                  folderName = ?, 
                  usesAIML = ?, 
                  internal = ?,
                  outreach = ?,
                  processing = ?,
                  lastUpdated = NOW() 
              WHERE id = ?";

      $stmt = $this->conn->prepare($sql);
      if (!$stmt) {
          $this->errorMessage = "Prepare failed: " . $this->conn->error;
          return false;
      }

      $stmt->bind_param(
          "iissssssssssssssssi",
          $collectionManager,
          $researcherID,
          $title,
          $field,
          $secondaryfields,
          $funded,
          $fundingsource,
          $description,
          $howfound,
          $dataproduced,
          $existing,
          $future,
          $new,
          $drivefolder,
          $aiml,
          $internal,
          $outreach,
          $processing,
          $requestID
      );

      if (!$stmt->execute()) {
          $this->errorMessage = "Execute failed: " . $stmt->error;
          return false;
      }
      $stmt->close();

      $newData = [
          "collectionManager" => $collectionManager,
          "researcherID" => $researcherID,
          "title" => $title,
          "primaryResearchField" => $field,
          "secondaryResearchField" => $secondaryfields,
          "funded" => $funded,
          "fundingSource" => $fundingsource,
          "description" => $description,
          "howFoundUs" => $howfound,
          "dataProduced" => $dataproduced,
          "existingSamples" => $existing,
          "futureSamples" => $future,
          "generatingSamples" => $new,
          "folderName" => $drivefolder,
          "usesAIML" => $aiml,
          "internal" => $internal,
          "outreach" => $outreach,
          "processing" => $processing
      ];

      foreach ($newData as $field => $newValue) {
          $oldValue = $oldData[$field] ?? null;
          if ($oldValue != $newValue) {
              $this->logEdit($requestID,"neonrequest", $field, $oldValue, $newValue, $uid);
          }
      }


      if (!is_array($additionalresearchers)) {
          $additionalresearchers = [$additionalresearchers];
      }
      $allResearchers = array_merge([$researcherID], $additionalresearchers);
      $newResearcherIDs = array_map('intval', $allResearchers);

      $oldAdditional = $this->getAdditionalResearchersByID($requestID); 
      $oldAdditionalIDs = array_map('intval', array_keys($oldAdditional));
      $oldMainID = (int)$oldData['researcherID'];

        if ($oldMainID !== (int)$researcherID) {
      $this->logEdit($requestID, "neonresearcherrequestlink", "researcherID", $oldMainID, null, $uid);
      $this->logEdit($requestID, "neonresearcherrequestlink", "researcherID", null, $researcherID, $uid);
      }

      $added   = array_diff($newResearcherIDs, array_merge([$researcherID], $oldAdditionalIDs));
      $removed = array_diff($oldAdditionalIDs, $additionalresearchers);

      foreach ($added as $rid) {
          $this->logEdit($requestID, "neonresearcherrequestlink", "researcherID", null, $rid, $uid);
      }
      foreach ($removed as $rid) {
          $this->logEdit($requestID, "neonresearcherrequestlink", "researcherID", $rid, null, $uid);
      }

      $this->clearResearcherInquiryLink($requestID);
      $this->addResearcherInquiryLink($requestID, $allResearchers);

      $oldCollections = $this->getCollectionsByID($requestID);
      $oldCollectionIds = array_map('intval', array_keys($oldCollections));
      $newCollectionIds = array_map('intval', $collections);

      $added = array_diff($newCollectionIds, $oldCollectionIds);
      $removed = array_diff($oldCollectionIds, $newCollectionIds);

      if (!empty($added) || !empty($removed)) {
          $this->clearCollectionInquiryLink($requestID);
          $this->addCollectionInquiryLink($requestID, $newCollectionIds);

          foreach ($added as $cid) {
              $this->logEdit($requestID, "neoncollectionrequestlink", "collID", null, $cid, $uid);
          }
          foreach ($removed as $cid) {
              $this->logEdit($requestID, "neoncollectionrequestlink", "collID", $cid, null, $uid);
          }
      }


      return $requestID;

  }

  // log request table edits
  private function logEdit($requestID, $table, $field, $oldValue, $newValue, $uid) {

      if (is_string($oldValue)) $oldValue = trim($oldValue);
      if (is_string($newValue)) $newValue = trim($newValue);

      if ($oldValue === $newValue) {
          return;
      }

      $sql = "INSERT INTO neonrequestedit (requestID,tableName, fieldName, oldValue, newValue, uid, editTimestamp) 
              VALUES (?, ?, ?, ?, ?, ?, NOW())";
      $stmt = $this->conn->prepare($sql);
      $stmt->bind_param("issssi", $requestID,$table, $field, $oldValue, $newValue, $uid);
      $stmt->execute();
      $stmt->close();
  }

      // edit status
    public function editStatus(
      $requestID,
      $inquiryDate,
      $pendingfunding,
      $notfunded,
      $cut,
      $pendinglist,
      $fulfillment,
      $active,
      $complete,
      $uid
  ) {
      $requestID = (int)$requestID;

      $inquiryDate   = !empty($inquiryDate)   ? $inquiryDate   : null;
      $pendingfunding = !empty($pendingfunding) ? $pendingfunding : null;
      $notfunded      = !empty($notfunded)      ? $notfunded      : null;
      $cut            = !empty($cut)            ? $cut            : null;
      $pendinglist    = !empty($pendinglist)    ? $pendinglist    : null;
      $fulfillment    = !empty($fulfillment)    ? $fulfillment    : null;
      $active         = !empty($active)         ? $active         : null;
      $complete       = !empty($complete)       ? $complete       : null;

      $dates = [
          'sample use inquiry'    => $inquiryDate,
          'pending funding'   => $pendingfunding,
          'not funded'        => $notfunded,
          'pending sample list'      => $pendinglist,
          'pending fulfillment'       => $fulfillment,
          'active use'            => $active,
          'completed'         => $complete
      ];

      $timestamps = [];
      foreach ($dates as $label => $date) {
          if (!empty($date) && strtotime($date) !== false) {
              $timestamps[$label] = strtotime($date);
          }
      }

      if (!empty($timestamps)) {
          $latestLabel = array_search(max($timestamps), $timestamps);
          $latestDate  = $dates[$latestLabel];
          $status      = $latestLabel;
          $statusDate = $latestDate;
      } else {
          $status = null;
          $statusDate = null;
      }

    if ($status == 'pending funding') $funded = 'Proposal pending funding';
    elseif($status == 'not funded') $funded = 'Proposal not funded';
    elseif(in_array($status, array('pending sample list','pending fulfillment','active use','completed'))) $funded = 'Already externally funded OR Internal/institutional support'; 

      $oldSql = "SELECT * FROM neonrequest WHERE id = ?";
      $oldStmt = $this->conn->prepare($oldSql);
      $oldStmt->bind_param("i", $requestID);
      $oldStmt->execute();
      $oldResult = $oldStmt->get_result();
      $oldData = $oldResult->fetch_assoc();
      $oldStmt->close();

      if (!$oldData) {
          $this->errorMessage = "Inquiry not found.";
          return false;
      }

      $sql = "UPDATE neonrequest 
              SET 
                  status = ?,
                  statusDate = ?,
                  funded = ?,
                  inquiryDate = ?, 
                  pendingFundingDate = ?, 
                  notFundedDate = ?, 
                  cut = ?,
                  pendingSampleListDate = ?, 
                  pendingFulfillmentDate = ?,
                  activeDate = ?, 
                  completeDate = ?, 
                  lastUpdated = NOW() 
              WHERE id = ?";

      $stmt = $this->conn->prepare($sql);
      if (!$stmt) {
          $this->errorMessage = "Prepare failed: " . $this->conn->error;
          return false;
      }

      $stmt->bind_param(
          "sssssssssssi",
          $status,
          $statusDate,
          $funded,
          $inquiryDate,
          $pendingfunding,
          $notfunded,
          $cut,
          $pendinglist,
          $fulfillment,
          $active,
          $complete,
          $requestID
      );

      if (!$stmt->execute()) {
          $this->errorMessage = "Execute failed: " . $stmt->error;
          return false;
      }
      $stmt->close();

      $newData = [
          "status" => $status,
          "statusDate" => $statusDate,
          "funded" => $funded,
          "inquiryDate" => $inquiryDate,
          "pendingFundingDate" => $pendingfunding,
          "notFundedDate" => $notfunded,
          "cut" => $cut,
          "pendingSampleListDate" => $pendinglist,
          "pendingFulfillmentDate" => $fulfillment,
          "activeDate" => $active,
          "completeDate" => $complete
      ];

      foreach ($newData as $field => $newValue) {
          $oldValue = $oldData[$field] ?? null;
          if ($oldValue != $newValue) {
              $this->logEdit($requestID, "neonrequest", $field, $oldValue, $newValue, $uid);
          }
      }

    // update status of linked samples/material samples if necessary
      $sampleStatusMap = [
        'not funded'     => 'not funded',
        'cut'            => 'funded, but cut'
    ];

    if (isset($sampleStatusMap[$status])) {
        $newSampleStatus = $sampleStatusMap[$status];

        $updateSampleSql = "UPDATE neonsamplerequestlink 
                            SET status = ?
                            WHERE requestID = ?";
        $updateStmt = $this->conn->prepare($updateSampleSql);
        if ($updateStmt) {
            $updateStmt->bind_param("si", $newSampleStatus, $requestID);
            $updateStmt->execute();
            $updateStmt->close();
        }

        $updateMatSql = "UPDATE neonmaterialsamplerequestlink 
                        SET status = ?
                        WHERE requestID = ?";
        $updateMatStmt = $this->conn->prepare($updateMatSql);
        if ($updateMatStmt) {
            $updateMatStmt->bind_param("si", $newSampleStatus, $requestID);
            $updateMatStmt->execute();
            $updateMatStmt->close();
        }
    }

      return $requestID;
  }


    // Get samples associated with a request for inquiry form table
  public function getSampleTableByID($requestID){
      $retArr = [];

      $requestID = (int)$requestID;

      $sql = "SELECT occid,status,useType,substanceProvided,available,notes,shipmentID FROM neonsamplerequestlink
            WHERE requestID = ?";
      $stmt = $this->conn->prepare($sql);
      if (!$stmt) {
          $this->errorMessage = "Dababase error: " . $this->conn->error;
          return $retArr;
      }

      $stmt->bind_param("i", $requestID);
      $stmt->execute();
      $result = $stmt->get_result();

      while ($row = $result->fetch_assoc()) {
          $retArr[] = $row;
      }

      $stmt->close();

      return $retArr;
  }

      // Get material samples associated with a request for inquiry form table
  public function getMaterialSampleTableByID($requestID){
      $retArr = [];

      $requestID = (int)$requestID;

      $sql = "SELECT matSampleID,occid,status,useType,sampleType,notes,shipmentID FROM neonmaterialsamplerequestlink
            WHERE requestID = ?";
      $stmt = $this->conn->prepare($sql);
      if (!$stmt) {
          $this->errorMessage = "Dababase error: " . $this->conn->error;
          return $retArr;
      }

      $stmt->bind_param("i", $requestID);
      $stmt->execute();
      $result = $stmt->get_result();

      while ($row = $result->fetch_assoc()) {
          $retArr[] = $row;
      }

      $stmt->close();

      return $retArr;
  }

    // Get samples count of samples associated with a request
    public function getSampleCountByID($requestID){
        $requestID = (int)$requestID;

        $sql = "SELECT COUNT(*) AS sampleCount
                FROM neonsamplerequestlink
                WHERE requestID = ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->errorMessage = "Database error: " . $this->conn->error;
            return 0;
        }

        $stmt->bind_param("i", $requestID);
        $stmt->execute();
        $result = $stmt->get_result();

        $count = 0;
        if ($row = $result->fetch_assoc()) {
            $count = (int)$row['sampleCount'];
        }

        $stmt->close();

        return $count;
    }

        // Get samples count of samples associated with a request
    public function getMaterialSampleCountByID($requestID){
        $requestID = (int)$requestID;

        $sql = "SELECT COUNT(*) AS sampleCount
                FROM neonmaterialsamplerequestlink
                WHERE requestID = ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->errorMessage = "Database error: " . $this->conn->error;
            return 0;
        }

        $stmt->bind_param("i", $requestID);
        $stmt->execute();
        $result = $stmt->get_result();

        $count = 0;
        if ($row = $result->fetch_assoc()) {
            $count = (int)$row['sampleCount'];
        }

        $stmt->close();

        return $count;
    }

      // Get request data
    public function getRequestData($requestID){
        $requestID = (int)$requestID;
        $sql = "SELECT r.title, p.name, r.status
                FROM neonrequest r
                LEFT JOIN neonresearcher p
                ON r.researcherID = p.researcherID
                WHERE r.id = ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->errorMessage = "Database error: " . $this->conn->error;
            return [];
        }

        $stmt->bind_param("i", $requestID);
        $stmt->execute();
        $result = $stmt->get_result();

        $row = $result->fetch_assoc();  

        $stmt->close();

        return $row ?: []; 
    }

        // Get detailed samples associated with a request 
    public function getSamplesByID($requestID,$filter = ''){
      $retArr = [];

      $requestID = (int)$requestID;

      $sql = "SELECT id,n.sampleID,n.sampleClass,n.sampleCode,status,useType,substanceProvided,available,s.notes,shipmentID,s.occid 
            FROM neonsamplerequestlink s
            LEFT JOIN NeonSample n
            ON s.occid=n.occid
            WHERE requestID = ?";

        if($filter){
                    if($filter == 'available'){
                        $sql .= ' AND (available = "yes") ';
                    }
                    elseif($filter == 'notavailable'){
                        $sql .= ' AND (available = "no") ';
                    }
                    elseif($filter == 'pending'){
                        $sql .= ' AND (status = "pending fulfillment") ';
                    }
                    elseif($filter == 'current'){
                        $sql .= ' AND (status = "current") ';
                    }
                    elseif($filter == 'completed'){
                        $sql .= ' AND (status = "completed") ';
                    }
        }
            
        $sql .= ' ORDER BY n.sampleID,n.sampleCode ';

      $stmt = $this->conn->prepare($sql);
      if (!$stmt) {
          $this->errorMessage = "Dababase error: " . $this->conn->error;
          return $retArr;
      }

      $stmt->bind_param("i", $requestID);
      $stmt->execute();
      $result = $stmt->get_result();

      while ($row = $result->fetch_assoc()) {
          $retArr[] = $row;
      }

      $stmt->close();

      return $retArr;
    }

          // Get detailed material samples associated with a request 
  public function getMaterialSamplesByID($requestID, $filter = ''){
      $retArr = [];

      $requestID = (int)$requestID;

      $sql = "SELECT s.id,s.matSampleID,t.catalogNumber,n.sampleID,n.sampleClass,n.sampleCode,s.status,s.useType,s.sampleType,s.notes,s.shipmentID,s.occid 
            FROM neonmaterialsamplerequestlink s
            LEFT JOIN NeonSample n
            ON s.occid=n.occid
            LEFT JOIN ommaterialsample t
            ON s.matSampleID = t.matSampleID
            WHERE requestID = ?";

        if($filter){
                    if($filter == 'pendingfunding'){
                        $sql .= ' AND (status = "pending funding") ';
                    }
                    elseif($filter == 'pendingfulfillment'){
                        $sql .= ' AND (status = "pending fulfillment") ';
                    }
                    elseif($filter == 'current'){
                        $sql .= ' AND (status = "current") ';
                    }
                    elseif($filter == 'complete'){
                        $sql .= ' AND (status = "complete") ';
                    }
        }
            
        $sql .= ' ORDER BY t.catalogNumber,n.sampleID ';

      $stmt = $this->conn->prepare($sql);
      if (!$stmt) {
          $this->errorMessage = "Dababase error: " . $this->conn->error;
          return $retArr;
      }

      $stmt->bind_param("i", $requestID);
      $stmt->execute();
      $result = $stmt->get_result();

      while ($row = $result->fetch_assoc()) {
          $retArr[] = $row;
      }

      $stmt->close();

      return $retArr;
  }

      // Sample for sampleeditor
  public function getSampleForEditor($id){
      $retArr = [];

      $sql = "SELECT id,n.sampleID,n.sampleClass,n.sampleCode,status,
            useType,substanceProvided,available,s.notes,shipmentID,s.occid 
            FROM neonsamplerequestlink s
            LEFT JOIN NeonSample n
            ON s.occid=n.occid
            WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->errorMessage = "Database error: " . $this->conn->error;
            return [];
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        $row = $result->fetch_assoc();  

        $stmt->close();

        return $row ?: []; 
  }

        // Material sample for materialsampleeditor
  public function getMaterialSampleForEditor($id){
      $retArr = [];

      $sql = "SELECT 
            s.id             AS id,
            s.matSampleID    AS matSampleID,
            o.catalogNumber  AS catalogNumber,
            n.sampleID       AS sampleID,
            n.sampleClass    AS sampleClass,
            n.sampleCode     AS sampleCode,
            n.occid          AS occid,
            s.status         AS status,
            s.useType       AS useType,
            s.sampleType     AS sampleType,
            s.notes          AS notes,
            s.shipmentID    AS shipmentID
        FROM neonmaterialsamplerequestlink s
        LEFT JOIN NeonSample n ON s.occid = n.occid
        LEFT JOIN ommaterialsample o ON s.matSampleID = o.matSampleID
        WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->errorMessage = "Database error: " . $this->conn->error;
            return [];
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        $row = $result->fetch_assoc();  

        $stmt->close();

        return $row ?: []; 
  }



    // Get shipments list
  public function getShipments(){
      $retArr = array();

      $sql = 'SELECT id, name, shipDate
              FROM neonrequestshipment s
              LEFT JOIN neonresearcher r
              ON s.researcherID = r.researcherID';

      $rs = $this->conn->query($sql);

      while($r = $rs->fetch_object()){
        $id = $this->cleanOutStr($r->id);
        $name = $this->cleanOutStr($r->name);
        $date = $this->cleanOutStr($r->shipDate);

        $display = $id ? "$id - $name ($date)" : $name;

        $retArr[$r->id] = $display;
      }

      $rs->free();
      return $retArr;
  }

    // Get shipments list by requestID
    public function getShipmentByID($requestID){
        $retArr = array();

        $sql = 'SELECT s.id, r.name, s.shipDate
                FROM neonrequestshipment s
                LEFT JOIN neonresearcher r
                    ON s.researcherID = r.researcherID
                LEFT JOIN neonrequestshipmentrequestlink l
                    ON s.id = l.shipmentID
                WHERE l.requestID = ?';

        $stmt = $this->conn->prepare($sql);

        if($stmt){
            $stmt->bind_param('i', $requestID); 
            $stmt->execute();
            $result = $stmt->get_result();

            while($r = $result->fetch_object()){
                $id   = $this->cleanOutStr($r->id);
                $name = $this->cleanOutStr($r->name);
                $date = $this->cleanOutStr($r->shipDate);

                $display = $id ? "$id - $name ($date)" : $name;
                $retArr[$id] = $display;
            }

            $stmt->close();
        }

        return $retArr;
    }


    // delete sample from request
	public function deleteSample($id){
		$status = false;
		if(is_numeric($id)){
			$sql = 'DELETE FROM neonsamplerequestlink WHERE id = '.$id;
			if($this->conn->query($sql)){
				$status = true;
			}
			else{
				$this->errorStr = 'ERROR deleting sample: '.$this->conn->error;
				return false;
			}
		}
		return $status;
	}

      // delete material sample from request
	public function deleteMaterialSample($id){
		$status = false;
		if(is_numeric($id)){
			$sql = 'DELETE FROM neonmaterialsamplerequestlink WHERE id = '.$id;
			if($this->conn->query($sql)){
				$status = true;
			}
			else{
				$this->errorStr = 'ERROR deleting material sample: '.$this->conn->error;
				return false;
			}
		}
		return $status;
	}

    //edit sample record associated with request
    public function editSample($postArr){
		$status = false;
		$postArr = array_change_key_case($postArr);
		if(is_numeric($postArr['id'])){

			$sql = 'UPDATE neonsamplerequestlink
				SET status = ?, useType = ?,
				available = ?, substanceProvided = ?, notes = ?, shipmentID = ?
                WHERE (id = ?)';
			$stmt = $this->conn->stmt_init();
			$stmt->prepare($sql);
			if($stmt->error==null) {
				$stat = isset($postArr['status'])&&$postArr['status']?$postArr['status']:NULL;
				$useType = $postArr['usetype']?$postArr['usetype']:NULL;
				$available = isset($postArr['available'])&&$postArr['available']?$postArr['available']:NULL;
				$substanceProvided = isset($postArr['substanceprovided'])&&$postArr['substanceprovided']?$postArr['substanceprovided']:NULL;
				$notes = isset($postArr['notes'])&&$postArr['notes']?$postArr['notes']:NULL;
                $shipmentID = array_key_exists('shipmentid', $postArr)? $postArr['shipmentid']: null;				
                $stmt->bind_param('ssssssi', $stat, $useType, $available, $substanceProvided, $notes, $shipmentID, $postArr['id']);
				$stmt->execute();
				if($stmt->error==null) $status = true;
				else{
					$this->errormessage = $stmt->error;
					echo $this->errorStr;
				}
			}
			else{
				$this->errorStr = $stmt->error;
				echo $this->errorStr;
			}
			$stmt->close();

		}
		return $status;
	}

    //edit material sample record associated with request
    public function editMaterialSample($postArr){
        $status = false;

        // Keep keys consistent (lowercase)
        $postArr = array_change_key_case($postArr, CASE_LOWER);

        if(is_numeric($postArr['id'])){
            $sql = 'UPDATE neonmaterialsamplerequestlink
                    SET status = ?, useType = ?, sampleType = ?, notes = ?, shipmentID = ?
                    WHERE id = ?';
            $stmt = $this->conn->stmt_init();
            $stmt->prepare($sql);

            if($stmt->error == null) {

                $stat        = !empty($postArr['status']) ? $postArr['status'] : NULL;
                $useType    = !empty($postArr['usetype']) ? $postArr['usetype'] : NULL;
                $sampleType  = !empty($postArr['sampletype']) ? $postArr['sampletype'] : NULL;
                $notes       = !empty($postArr['notes']) ? $postArr['notes'] : NULL;
                $shipmentID = array_key_exists('shipmentid', $postArr)? $postArr['shipmentid']: null;				
                $id          = (int)$postArr['id'];
                $stmt->bind_param('sssssi', $stat, $useType, $sampleType, $notes, $shipmentID, $id);
                $stmt->execute();

                if($stmt->error == null) {
                    $status = true;
                } else {
                    $this->errorStr = $stmt->error;
                }
            } else {
                $this->errorStr = $stmt->error;
            }

            $stmt->close();
        }

        return $status;
    }


    // export request sample list
    public function exportSampleList($requestID){
        $requestID = (int)$requestID;

        $fileName = 'sampleRequestExport_' . $requestID . '_' . date('Y-m-d') . '.csv';

        $sql = 'SELECT * FROM neonsamplerequestlink WHERE requestID = ?';
        $stmt = $this->conn->prepare($sql);
        if(!$stmt){
            die('SQL prepare failed: ' . $this->conn->error);
        }

        $stmt->bind_param('i', $requestID);
        $stmt->execute();
        $result = $stmt->get_result();

        if(!$result){
            die('Query failed: ' . $stmt->error);
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        if($row = $result->fetch_assoc()){
            fputcsv($output, array_keys($row)); 
            fputcsv($output, $row);            
        }

        while($row = $result->fetch_assoc()){
            fputcsv($output, $row);
        }

        fclose($output);
        $stmt->close();
        exit; 
    }
    public function exportPubTable($requestID){
        $requestID = (int)$requestID;

        $fileName = 'samplePublicationTable_' . $requestID . '_' . date('Y-m-d') . '.csv';

        $sql = 'WITH domains AS (
                            SELECT l.occid,d.name
                            FROM omoccurdatasetlink l
                            LEFT JOIN omoccurdatasets d
                            ON l.datasetID=d.datasetID
                            WHERE d.datasetID >0 AND d.datasetID <20
                        ),
                        sites AS (
                                    SELECT l.occid,d.name
                            FROM omoccurdatasetlink l
                            LEFT JOIN omoccurdatasets d
                            ON l.datasetID=d.datasetID
                            WHERE d.datasetID >32 AND d.datasetID <132
                            )
                        
                SELECT  e.name AS domain,
                        o.stateProvince,
                        t.name AS siteID, 
                        o.catalogNumber AS IGSN, 
                        m.sampleID AS sampleID, 
                        m.sampleCode AS barcode, 
                        o.sciname AS scientificName
                        FROM  domains e 
                        LEFT JOIN NeonSample m ON e.occid = m.occid
                        LEFT JOIN omoccurrences o ON e.occid = o.occid 
                        LEFT JOIN sites t ON e.occid = t.occid
                        LEFT JOIN neonsamplerequestlink sl
                        ON e.occid=sl.occid
                        WHERE sl.requestID= ? 
                        ORDER by domain,stateProvince,siteID,IGSN';

                $stmt = $this->conn->prepare($sql);
                if(!$stmt){
                    die('SQL prepare failed: ' . $this->conn->error);
                }

                $stmt->bind_param('i', $requestID);
                $stmt->execute();
                $result = $stmt->get_result();

                if(!$result){
                    die('Query failed: ' . $stmt->error);
                }

                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
                header('Pragma: no-cache');
                header('Expires: 0');

                $output = fopen('php://output', 'w');

                if($row = $result->fetch_assoc()){
                    fputcsv($output, array_keys($row)); 
                    fputcsv($output, $row);            
                }

                while($row = $result->fetch_assoc()){
                    fputcsv($output, $row);
                }

                fclose($output);
                $stmt->close();
                exit; 
    }

    // export material sample list
    public function exportMaterialSampleList($requestID){
        $requestID = (int)$requestID;

        $fileName = 'materialSampleRequestExport_' . $requestID . '_' . date('Y-m-d') . '.csv';

        $sql = 'SELECT * FROM neonmaterialsamplerequestlink WHERE requestID = ?';
        $stmt = $this->conn->prepare($sql);
        if(!$stmt){
            die('SQL prepare failed: ' . $this->conn->error);
        }

        $stmt->bind_param('i', $requestID);
        $stmt->execute();
        $result = $stmt->get_result();

        if(!$result){
            die('Query failed: ' . $stmt->error);
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        if($row = $result->fetch_assoc()){
            fputcsv($output, array_keys($row)); 
            fputcsv($output, $row);            
        }

        while($row = $result->fetch_assoc()){
            fputcsv($output, $row);
        }

        fclose($output);
        $stmt->close();
        exit; 
    }


    // export request occurrence list
    public function exportOccurList($requestID){
        $requestID = (int)$requestID;

        $fileName = 'requestOccurrenceExport_' . $requestID . '_' . date('Y-m-d') . '.csv';

        $sql = 'SELECT  o.occid,c.collectionName, o.catalogNumber,o.occurrenceID,m.sampleID, m.alternativeSampleID, m.sampleCode, m.sampleClass, '.
			'o.catalogNumber, o.sciname, o.scientificNameAuthorship, o.identifiedBy, o.dateIdentified, o.recordedBy, o.eventDate, '.
			'o.country, o.stateProvince, o.county, o.locality, o.decimalLatitude, o.decimalLongitude, o.coordinateUncertaintyInMeters, o.minimumElevationInMeters, '.
			'o.habitat, o.dateEntered, o.dateLastModified '.
			'FROM  neonsamplerequestlink s '.
            'LEFT JOIN NeonSample m ON s.occid=m.occid '.
			'INNER JOIN omoccurrences o ON m.occid = o.occid '.
            'LEFT JOIN omcollections c ON o.collid=c.collid '.
            'WHERE s.requestID = ?';

        $stmt = $this->conn->prepare($sql);
        if(!$stmt){
            die('SQL prepare failed: ' . $this->conn->error);
        }

        $stmt->bind_param('i', $requestID);
        $stmt->execute();
        $result = $stmt->get_result();

        if(!$result){
            die('Query failed: ' . $stmt->error);
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        if($row = $result->fetch_assoc()){
            fputcsv($output, array_keys($row)); 
            fputcsv($output, $row);            
        }

        while($row = $result->fetch_assoc()){
            fputcsv($output, $row);
        }

        fclose($output);
        $stmt->close();
        exit; 
    }

       // export request material sample table
    public function exportMaterialSampleTable($requestID){
        $requestID = (int)$requestID;

        $fileName = 'materialSampleRequestTableExport_' . $requestID . '_' . date('Y-m-d') . '.csv';

        $sql = 'SELECT  t.*,c.collectionName, o.catalogNumber,o.occurrenceID,m.sampleID, m.alternativeSampleID, m.sampleCode, m.sampleClass, '.
			'o.catalogNumber as primaryCatalogNumber, o.sciname, o.scientificNameAuthorship, o.identifiedBy, o.dateIdentified, o.recordedBy, o.eventDate, '.
			'o.country, o.stateProvince, o.county, o.locality '.
			'FROM  neonmaterialsamplerequestlink s '.
            'LEFT JOIN ommaterialsample t ON s.matSampleID = t.matSampleID '.
            'LEFT JOIN NeonSample m ON s.occid=m.occid '.
			'INNER JOIN omoccurrences o ON t.occid = o.occid '.
            'LEFT JOIN omcollections c ON o.collid=c.collid '.
            'WHERE s.requestID = ?';

        $stmt = $this->conn->prepare($sql);
        if(!$stmt){
            die('SQL prepare failed: ' . $this->conn->error);
        }

        $stmt->bind_param('i', $requestID);
        $stmt->execute();
        $result = $stmt->get_result();

        if(!$result){
            die('Query failed: ' . $stmt->error);
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        if($row = $result->fetch_assoc()){
            fputcsv($output, array_keys($row)); 
            fputcsv($output, $row);            
        }

        while($row = $result->fetch_assoc()){
            fputcsv($output, $row);
        }

        fclose($output);
        $stmt->close();
        exit; 
    }

    // batch edit samples in request
    public function batchEditSamples(array $updates, array $ids){
        
        if (empty($ids)) {
            $this->errorMessage = "No sample IDs provided for batch edit.";
            return false;
        }

        if (empty($updates)) {
            $this->errorMessage = "No fields selected for update.";
            return false;
        }

        $this->conn->begin_transaction();
        try {
            foreach($ids as $id){
                $id = intval($id);
                $sample = $this->getSampleForEditor($id);
                if(!$sample){
                    throw new Exception("Sample #$id not found.");
                }
                $newData = array_merge($sample, $updates, ['id' => $id]);
                $needsShipment = in_array($newData['status'], ["current","completed","loaned, not used"]);
                $hasShipment = array_key_exists('shipmentID', $updates)? !empty($newData['shipmentID']): !empty($sample['shipmentID']);
                if($needsShipment && !$hasShipment){
                    throw new Exception("Sample #$id: A shipment must be assigned if status is {$newData['status']}.");
                }
                if($hasShipment && !$needsShipment){
                    throw new Exception("Sample #$id: If a shipment is assigned, status must be current, completed, or loaned, not used.");
                }
                foreach(['status','useType','available','substanceProvided'] as $field){
                    if(empty($newData[$field])){
                        throw new Exception("Sample #$id is missing a required field: $field");
                    }
                }
                if(!$this->editSample($newData)){
                    throw new Exception("Failed to update sample #$id: ".$this->getErrorStr());
                }
            }
            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            $this->errorMessage = $e->getMessage();
            return false;
        }
    }


        // batch edit material samples in request
    public function batchEditMaterialSamples(array $updates, array $ids){

        if(!$ids) {
            $this->errorMessage = "No material sample IDs provided for batch edit.";
            return false;
        }
        if (empty($updates)) {
            $this->errorMessage = "No fields selected for update.";
            return false;
        }

        $this->conn->begin_transaction();
        try {
            foreach($ids as $id){
                $id = intval($id);
                $sample = $this->getMaterialSampleForEditor($id);
                if(!$sample){
                    throw new Exception("Material sample #$id not found.");
                }
                $newData = array_merge($sample, $updates, ['id' => $id]);
                $needsShipment = in_array($newData['status'], ["current","complete"]);
                $hasShipment = array_key_exists('shipmentID', $newData) && $newData['shipmentID'] !== null && $newData['shipmentID'] !== '';

                if($needsShipment && !$hasShipment){
                    throw new Exception("Material sample #$id: A shipment must be assigned if status is {$newData['status']}.");
                }
                if($hasShipment && !$needsShipment){
                    throw new Exception("Material sample #$id: If a shipment is assigned, status must be current or complete.");
                }
                foreach(['status','useType','sampleType'] as $field){
                    if(empty($newData[$field])){
                        throw new Exception("Material sample #$id is missing a required field: $field");
                    }
                }
                if(!$this->editMaterialSample($newData)){
                    throw new Exception("Failed to update material sample #$id: ".$this->getErrorStr());
                }
            }
            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            $this->errorMessage = $e->getMessage();
            return false;
        }
    }

  // add shipment to neonrequestshipment table
    public function addShipment($researcherID, $shipDate, $address, $shippedBy) {
        if (empty($researcherID) || empty($shipDate) || empty($address) || empty($shippedBy)) {
            $this->errorMessage = "All fields are required.";
            return false;
        }

        $sql = "INSERT INTO neonrequestshipment (researcherID, shipDate, address, shippedBy) 
                VALUES (?, ?, ?, ?)";

        if ($stmt = $this->conn->prepare($sql)) {
            $stmt->bind_param("issi", $researcherID, $shipDate, $address, $shippedBy);
            if ($stmt->execute()) {
                $newId = $stmt->insert_id;
                $stmt->close();
                return $newId;
            } else {
                $this->errorMessage = "Database Error: " . $stmt->error;
                $stmt->close();
                return false;
            }
        } else {
            $this->errorMessage = "Database Error: " . $this->conn->error;
            return false;
        }
    }
    
    // edit shipments
    public function editShipment($shipmentIDs, $uid, $requestID) {
        if (!is_array($shipmentIDs)) {
            $shipmentIDs = [$shipmentIDs];
        }

        $newShipmentIDs = array_map('intval', $shipmentIDs);
        $requestID = (int)$requestID;

        $sql = "SELECT MIN(shipDate) AS earliestShipDate FROM neonrequestshipmentrequestlink rr
                JOIN neonrequestshipment s ON rr.shipmentID = s.id
                WHERE rr.requestID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $requestID);
        $stmt->execute();
        $stmt->bind_result($earliestShipDate);
        $stmt->fetch();
        $stmt->close();

        if (empty($newShipmentIDs)) {
            $newShipDates = [];
        } else {
            $placeholders = implode(',', array_fill(0, count($newShipmentIDs), '?'));
            $types = str_repeat('i', count($newShipmentIDs));
            $sql = "SELECT id, shipDate FROM neonrequestshipment WHERE id IN ($placeholders)";
            $stmt = $this->conn->prepare($sql);

            $stmt->bind_param($types, ...$newShipmentIDs);
            $stmt->execute();
            $result = $stmt->get_result();
            $newShipDates = [];
            while ($row = $result->fetch_assoc()) {
                $newShipDates[$row['id']] = $row['shipDate'];
            }
            $stmt->close();
        }

        foreach ($newShipDates as $id => $shipDate) {
            if ($earliestShipDate && $shipDate < $earliestShipDate) {
                $this->errorMessage = "Cannot attach shipment $id: shipment date ($shipDate) is earlier than request's earliest allowed shipment date ($earliestShipDate).";
                return false;
            }
        }

        $oldShipments = $this->getShipmentByID($requestID);
        $oldShipmentIDs = array_map('intval', array_keys($oldShipments));

        $removed = array_diff($oldShipmentIDs, $newShipmentIDs);
        foreach ($removed as $ship_id) {
            $this->logEdit($requestID, "neonrequestshipmentrequestlink", "shipmentID", $ship_id, null, $uid);
        }

        $added = array_diff($newShipmentIDs, $oldShipmentIDs);
        foreach ($added as $ship_id) {
            $this->logEdit($requestID, "neonrequestshipmentrequestlink", "shipmentID", null, $ship_id, $uid);
        }

        $deleteSQL = "DELETE FROM neonrequestshipmentrequestlink WHERE requestID = ?";
        $stmt = $this->conn->prepare($deleteSQL);
        $stmt->bind_param("i", $requestID);
        if (!$stmt->execute()) {
            $this->errorMessage = "Failed to clear existing shipments: " . $stmt->error;
            return false;
        }
        $stmt->close();

        $insertSQL = "INSERT INTO neonrequestshipmentrequestlink (requestID, shipmentID) VALUES (?, ?)";
        $stmt = $this->conn->prepare($insertSQL);
        foreach ($newShipmentIDs as $ship_id) {
            $stmt->bind_param("ii", $requestID, $ship_id);
            if (!$stmt->execute()) {
                $this->errorMessage = "Failed to add shipment $ship_id: " . $stmt->error;
                return false;
            }
        }
        $stmt->close();

        return $requestID;
    }

          // Sample data for availability/disposition editor
  public function getSampleAvailabilityForEditor($id){
      $retArr = [];

      $sql = "SELECT id,s.occid,n.sampleID,n.sampleCode,s.available,o.availability,o.disposition
            FROM neonsamplerequestlink s
            INNER JOIN omoccurrences o
            ON s.occid=o.occid
            INNER JOIN NeonSample n
            ON s.occid=n.occid
            WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->errorMessage = "Database error: " . $this->conn->error;
            return [];
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        $row = $result->fetch_assoc();  

        $stmt->close();

        return $row ?: []; 
  }

            // Sample data for availability table
    public function getSampleAvailabilityTable(array $ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "SELECT n.sampleID, 
                    n.sampleCode, 
                    s.available AS `available: request`, 
                    CASE 
                        WHEN o.availability = 1 THEN 'yes' 
                        ELSE 'no' 
                    END AS `available: occurrence`
                FROM neonsamplerequestlink s
                INNER JOIN omoccurrences o ON s.occid = o.occid
                INNER JOIN NeonSample n     ON s.occid = n.occid
                WHERE s.id IN ($placeholders)";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->errorMessage = "Database error: " . $this->conn->error;
            return [];
        }

        $types = str_repeat('i', count($ids));

        $stmt->bind_param($types, ...$ids);

        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);

        $stmt->close();
        return $rows;
    }

        // Sample data for disposition table
    public function getSampleDispositionTable(array $ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "SELECT n.sampleID, n.sampleCode, o.disposition
                FROM neonsamplerequestlink s
                INNER JOIN omoccurrences o ON s.occid = o.occid
                INNER JOIN NeonSample n     ON s.occid = n.occid
                WHERE s.id IN ($placeholders)";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $this->errorMessage = "Database error: " . $this->conn->error;
            return [];
        }

        $types = str_repeat('i', count($ids));

        $stmt->bind_param($types, ...$ids);

        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res->fetch_all(MYSQLI_ASSOC);

        $stmt->close();
        return $rows;
    }

    // update sample availability
    public function updateAvailability(array $ids, int $userId = 0): bool {
        if (empty($ids)) return false;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $this->conn->begin_transaction();

        try {
            $sql1 = "INSERT INTO omoccuredits 
                        (occid,fieldName, fieldValueNew, fieldValueOld, reviewStatus, appliedStatus, editType, isActive, uid, initialTimestamp)
                    SELECT
                        o.occid,
                        'availability',
                        CASE WHEN s.available='yes' THEN 1 WHEN s.available='no' THEN 0 ELSE o.availability END,
                        o.availability,
                        1, 
                        1, 
                        1, 
                        1, 
                        ?,
                        NOW()
                    FROM omoccurrences o
                    INNER JOIN neonsamplerequestlink s ON o.occid = s.occid
                    WHERE s.id IN ($placeholders)";

            $stmt1 = $this->conn->prepare($sql1);
            if (!$stmt1) throw new Exception("DB error (update availability): " . $this->conn->error);

            $bindTypes = 'i' . str_repeat('i', count($ids));
            $stmt1->bind_param($bindTypes, $userId, ...$ids);
            $stmt1->execute();
            $stmt1->close();


            $sql2 = "UPDATE omoccurrences o
                    INNER JOIN neonsamplerequestlink s ON o.occid = s.occid
                    SET o.availability = CASE
                        WHEN s.available = 'yes' THEN 1
                        WHEN s.available = 'no' THEN 0
                        ELSE o.availability
                    END
                    WHERE s.id IN ($placeholders)";

            $stmt2 = $this->conn->prepare($sql2);
            if (!$stmt2) throw new Exception("DB error (insert edits): " . $this->conn->error);

            $types = str_repeat('i', count($ids));
            $stmt2->bind_param($types, ...$ids);
            $stmt2->execute();
            $stmt2->close();

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            $this->errorMessage = $e->getMessage();
            return false;
        }
    }


        // write sample disposition
    public function writeDisposition(array $ids, $newDisposition, int $userId = 0) {
        if (empty($ids)) return false;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $this->conn->begin_transaction();

        try {
            $sql1 = "INSERT INTO omoccuredits 
                        (occid, fieldName, fieldValueNew, fieldValueOld, reviewStatus, appliedStatus, editType, isActive, uid, initialTimestamp)
                    SELECT
                        o.occid,
                        'disposition',
                        ?,
                        COALESCE(o.disposition, ''),
                        1, 
                        1, 
                        1, 
                        1, 
                        ?,
                        NOW()
                    FROM omoccurrences o
                    INNER JOIN neonsamplerequestlink s ON o.occid = s.occid
                    WHERE s.id IN ($placeholders)";

            $stmt1 = $this->conn->prepare($sql1);
            if (!$stmt1) throw new Exception("DB error (writeDisposition - stmt1): " . $this->conn->error);

            $bindTypes = 'si' . str_repeat('i', count($ids));
            $params = array_merge([$newDisposition, $userId], $ids);

            $refs = [];
            foreach ($params as $key => $value) {
                $refs[$key] = &$params[$key];
            }

            $stmt1->bind_param($bindTypes, $newDisposition, $userId, ...$ids);
            
            $stmt1->execute();
            if ($stmt1->errno) {
                throw new Exception("Execute failed (stmt1): " . $stmt1->error);
            }
            $stmt1->close();

            $sql2 = "UPDATE omoccurrences o
                    INNER JOIN neonsamplerequestlink s ON o.occid = s.occid
                    SET o.disposition = ?
                    WHERE s.id IN ($placeholders)";

            $stmt2 = $this->conn->prepare($sql2);
            if (!$stmt2) throw new Exception("DB error (writeDisposition - stmt2): " . $this->conn->error);

            $types = 's' . str_repeat('i', count($ids));
            $stmt2->bind_param($types, $newDisposition, ...$ids);

            $stmt2->execute();
            if ($stmt2->errno) {
                throw new Exception("Execute failed (stmt2): " . $stmt2->error);
            }
            $stmt2->close();

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            $this->errorMessage = $e->getMessage();
            return false;
        }
    }


}

?>