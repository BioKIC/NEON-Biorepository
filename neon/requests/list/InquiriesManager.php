<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class InquiriesManager extends Manager{

	function __construct() {
		parent::__construct(null,'write');
	}

	function __destruct(){
		parent::__destruct();
	}

  // Gets all inquiries
  public function getInquiriesOut(){
  	$dataArr = array();
    $sql = 'SELECT r.id, p.name AS researcher, DATE(r.inquiry_date) AS date, r.title, r.status, COUNT(s.occid) AS samples FROM neonrequest AS r LEFT JOIN neonresearcher AS p ON r.researcher_id = p.researcher_id LEFT JOIN neonsamplerequestlink AS s ON r.id = s.request_id GROUP BY r.id;';
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

      $sql = 'SELECT researcher_id, name, institution
              FROM neonresearcher 
              ORDER BY name';

      $rs = $this->conn->query($sql);

      while($r = $rs->fetch_object()){
          $name = $this->cleanOutStr($r->name);
          $institution = $this->cleanOutStr($r->institution);

          $display = $institution ? "$name ($institution)" : $name;

          $retArr[$r->researcher_id] = $display;
      }

      $rs->free();
      return $retArr;
  }

// Get primary research fields list
public function getFields(){
    $retArr = array();

    $sql = 'SELECT DISTINCT primary_research_field 
            FROM neonrequest 
            WHERE primary_research_field IS NOT NULL 
            AND primary_research_field <> "" 
            ORDER BY primary_research_field';

    $rs = $this->conn->query($sql);

    while($r = $rs->fetch_object()){
        $field = $this->cleanOutStr($r->primary_research_field);
        $display = trim($field);

        $retArr[$field] = $display;
    }

    $rs->free();
    return $retArr;
}

// Get options for how researcher found us 
public function getHowFoundUs(){
    $retArr = array();

    $sql = 'SELECT DISTINCT how_found_us 
            FROM neonrequest 
            WHERE how_found_us  IS NOT NULL 
            AND how_found_us  <> "" 
            ORDER BY how_found_us ';

    $rs = $this->conn->query($sql);

    while($r = $rs->fetch_object()){
        $how_found_us  = $this->cleanOutStr($r->how_found_us );
        $display = trim($how_found_us);

        $retArr[$how_found_us] = $display;
    }

    $rs->free();
    return $retArr;
}


  // add researcher to neonresearcher table
  public function addResearcher($name, $institution, $contact_email = '', $address = '', $phone = '') {
      // Only name and institution are required
      if (empty($name) || empty($institution)) {
          $this->errorMessage = "Name and institution are required.";
          return false;
      }

      $name = $this->conn->real_escape_string($name);
      $institution = $this->conn->real_escape_string($institution);
      $contact_email = $this->conn->real_escape_string($contact_email);
      $address = $this->conn->real_escape_string($address);
      $phone = $this->conn->real_escape_string($phone);

      $sql = "INSERT INTO neonresearcher (name, institution, contact_email, address, phone) 
              VALUES ('$name', '$institution', '$contact_email', '$address', '$phone')";

      if ($this->conn->query($sql)) {
          return $this->conn->insert_id; 
      } else {
          $this->errorMessage = "Database Error: " . $this->conn->error;
          return false;
      }
  }

   public function addInquiry($collection_manager, $researcher_id, $inquiry_date,$title,$collections,$field,$secondaryfields,$funded,$fundingsource,$description,$howfound,$dataproduced,$existing,$future,$new,$additionalresearchers,$drivefolder,$aiml,$internal) {

    $collection_manager = (int) $collection_manager;
    $researcher_id = (int) $researcher_id;
    $title = $this->conn->real_escape_string($title);
    $inquiry_date = $this->conn->real_escape_string($inquiry_date);
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



    $sql = "INSERT INTO neonrequest 
        (collection_manager, inquiry_date,researcher_id,status, title, primary_research_field, secondary_research_field, funded, funding_source, description, how_found_us, data_produced, existing_samples, future_samples, generating_samples, folder_name,uses_aiml, internal,cut,last_updated) 
        VALUES 
        ('$collection_manager', '$inquiry_date','$researcher_id', 'sample inquiry', '$title', '$field', '$secondaryfields', '$funded', '$fundingsource', '$description', '$howfound', '$dataproduced', '$existing', '$future', '$new', '$drivefolder','$aiml','$internal','no', NOW())";

    if ($this->conn->query($sql)) {
        $request_id = $this->conn->insert_id;

        if (!is_array($additionalresearchers)) {
            $additionalresearchers = [$additionalresearchers];
        }

        $allResearchers = array_merge([$researcher_id], $additionalresearchers);


        // call link functions here
        $this->addResearcherInquiryLink($request_id, $allResearchers);
        $this->addCollectionInquiryLink($request_id, $collections);

        return $request_id; 
    } else {
        $this->errorMessage = "Database Error: " . $this->conn->error;
        return false;
    }
}

// add researcher request link
public function addResearcherInquiryLink($request_id, $allResearchers) {

    $request_id = $this->conn->real_escape_string($request_id);
    if (!is_array($allResearchers)) {
        $allResearchers = [$allResearchers]; 
    }
    
    foreach ($allResearchers as $rid) {
        $rid = $this->conn->real_escape_string((string)$rid);

        $sql = "INSERT INTO neonresearcherrequestlink (request_id, researcher_id) 
                VALUES ('$request_id', '$rid')";

        if (!$this->conn->query($sql)) {
            $this->errorMessage = "Database Error: " . $this->conn->error;
            return false;
        }
    }
}

// add collections request link
public function addCollectionInquiryLink($request_id, $collections) {
    $request_id = $this->conn->real_escape_string($request_id);

    if (!is_array($collections)) {
        $collections = [$collections]; 
    }

    foreach ($collections as $collid) {
        $collid = $this->conn->real_escape_string((string)$collid);

        $sql = "INSERT INTO neoncollectionrequestlink (request_id, coll_id) 
                VALUES ('$request_id', '$collid')";

        if (!$this->conn->query($sql)) {
            $this->errorMessage = "Database Error: " . $this->conn->error;
            return false;
        }
    }

    return true;
}

// get basic record data for a given request
public function getInquiryDataByID($request_id) {
    $request_id = (int)$request_id;

    $sql = "SELECT * FROM neonrequest WHERE id = $request_id";
    $rs = $this->conn->query($sql);

    if (!$rs) {
        $this->errorMessage = "Database Error: " . $this->conn->error;
        return false;
    }

    if ($rs->num_rows === 0) {
        $this->errorMessage = "No inquiry found with ID $request_id";
        return false;
    }

    $row = $rs->fetch_assoc();
    $rs->free();

    return $row; 
}

      // get researchers for a given request
      public function getAdditionalResearchersByID($request_id) {
          $request_id = (int)$request_id;

          $sql = "SELECT r.researcher_id, CONCAT(r.name,' (',r.institution,')') as researcher
                  FROM neonresearcherrequestlink l
                  LEFT JOIN neonresearcher r
                      ON l.researcher_id = r.researcher_id
                  LEFT JOIN neonrequest s
                      ON l.request_id = s.id
                  WHERE l.request_id = $request_id
                  AND l.researcher_id != s.researcher_id";
          $rs = $this->conn->query($sql);

          if (!$rs) {
              $this->errorMessage = "Database Error: " . $this->conn->error;
              return false;
          }

          $researchers = [];
          while ($row = $rs->fetch_assoc()) {
              $researchers[$row['researcher_id']] = $row['researcher'];
          }
          $rs->free();

          return $researchers;
      }


  // get collections for a given request
  public function getCollectionsByID($request_id) {
      $request_id = (int)$request_id;

      $sql = "SELECT l.coll_id AS collID, c.collectionName
              FROM neoncollectionrequestlink l
              LEFT JOIN omcollections c
              ON l.coll_id = c.collID
              WHERE l.request_id = $request_id";

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
  public function getCMByID($request_id) {
      $request_id = (int)$request_id;

      $sql = "SELECT u.uid,r.collection_manager,concat(u.firstName, ' ',u.lastName) as name FROM neonrequest r
              LEFT JOIN  users u
              ON r.collection_manager = u.uid
              WHERE r.id = $request_id";
      $rs = $this->conn->query($sql);

      if (!$rs) {
          $this->errorMessage = "Database Error: " . $this->conn->error;
          return false;
      }

      if ($rs->num_rows === 0) {
          $this->errorMessage = "No inquiry found with ID $request_id";
          return false;
      }

      $row = $rs->fetch_assoc();
      $rs->free();

      return $row; 
  }

  // get cm for a given request
  public function getPrimaryContactByID($request_id) {
      $request_id = (int)$request_id;

      $sql = "SELECT p.researcher_id,p.name,p.institution FROM neonrequest r
              LEFT JOIN  neonresearcher p
              ON r.researcher_id = p.researcher_id
              WHERE r.id = $request_id";
      $rs = $this->conn->query($sql);

      if (!$rs) {
          $this->errorMessage = "Database Error: " . $this->conn->error;
          return false;
      }

      if ($rs->num_rows === 0) {
          $this->errorMessage = "No inquiry found with ID $request_id";
          return false;
      }

      $row = $rs->fetch_assoc();
      $rs->free();

      return $row; 
  }

  public function clearResearcherInquiryLink($request_id) {
      $request_id = (int)$request_id; 

      $sql = "DELETE FROM neonresearcherrequestlink WHERE request_id = $request_id";
      
      if (!$this->conn->query($sql)) {
          $this->errorMessage = "Database Error: " . $this->conn->error;
          return false;
      }

      return true; 
  }

  public function clearCollectionInquiryLink($request_id) {
      $request_id = (int)$request_id; 

      $sql = "DELETE FROM neoncollectionrequestlink WHERE request_id = $request_id";
      
      if (!$this->conn->query($sql)) {
          $this->errorMessage = "Database Error: " . $this->conn->error;
          return false;
      }

      return true; 
  }



  public function editInquiry(
      $request_id,
      $collection_manager,
      $researcher_id,
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
      $uid 
  ) {
      $request_id = (int)$request_id;

      $oldSql = "SELECT * FROM neonrequest WHERE id = ?";
      $oldStmt = $this->conn->prepare($oldSql);
      $oldStmt->bind_param("i", $request_id);
      $oldStmt->execute();
      $oldResult = $oldStmt->get_result();
      $oldData = $oldResult->fetch_assoc();
      $oldStmt->close();

      if (!$oldData) {
          $this->errorMessage = "Inquiry not found.";
          return false;
      }

      $sql = "UPDATE neonrequest 
              SET collection_manager = ?, 
                  researcher_id = ?, 
                  title = ?, 
                  primary_research_field = ?, 
                  secondary_research_field = ?, 
                  funded = ?, 
                  funding_source = ?, 
                  description = ?, 
                  how_found_us = ?, 
                  data_produced = ?, 
                  existing_samples = ?, 
                  future_samples = ?, 
                  generating_samples = ?, 
                  folder_name = ?, 
                  uses_aiml = ?, 
                  internal = ?,
                  last_updated = NOW() 
              WHERE id = ?";

      $stmt = $this->conn->prepare($sql);
      if (!$stmt) {
          $this->errorMessage = "Prepare failed: " . $this->conn->error;
          return false;
      }

      $stmt->bind_param(
          "iissssssssssssssi",
          $collection_manager,
          $researcher_id,
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
          $request_id
      );

      if (!$stmt->execute()) {
          $this->errorMessage = "Execute failed: " . $stmt->error;
          return false;
      }
      $stmt->close();

      $newData = [
          "collection_manager" => $collection_manager,
          "researcher_id" => $researcher_id,
          "title" => $title,
          "primary_research_field" => $field,
          "secondary_research_field" => $secondaryfields,
          "funded" => $funded,
          "funding_source" => $fundingsource,
          "description" => $description,
          "how_found_us" => $howfound,
          "data_produced" => $dataproduced,
          "existing_samples" => $existing,
          "future_samples" => $future,
          "generating_samples" => $new,
          "folder_name" => $drivefolder,
          "uses_aiml" => $aiml,
          "internal" => $internal
      ];

      foreach ($newData as $field => $newValue) {
          $oldValue = $oldData[$field] ?? null;
          if ($oldValue != $newValue) {
              $this->logEdit($request_id,"neonrequest", $field, $oldValue, $newValue, $uid);
          }
      }


      if (!is_array($additionalresearchers)) {
          $additionalresearchers = [$additionalresearchers];
      }
      $allResearchers = array_merge([$researcher_id], $additionalresearchers);
      $newResearcherIDs = array_map('intval', $allResearchers);

      $oldAdditional = $this->getAdditionalResearchersByID($request_id); 
      $oldAdditionalIDs = array_map('intval', array_keys($oldAdditional));
      $oldMainID = (int)$oldData['researcher_id'];

        if ($oldMainID !== (int)$researcher_id) {
      $this->logEdit($request_id, "neonresearcherrequestlink", "researcher_id", $oldMainID, null, $uid);
      $this->logEdit($request_id, "neonresearcherrequestlink", "researcher_id", null, $researcher_id, $uid);
      }

      $added   = array_diff($newResearcherIDs, array_merge([$researcher_id], $oldAdditionalIDs));
      $removed = array_diff($oldAdditionalIDs, $additionalresearchers);

      foreach ($added as $rid) {
          $this->logEdit($request_id, "neonresearcherrequestlink", "researcher_id", null, $rid, $uid);
      }
      foreach ($removed as $rid) {
          $this->logEdit($request_id, "neonresearcherrequestlink", "researcher_id", $rid, null, $uid);
      }

      $this->clearResearcherInquiryLink($request_id);
      $this->addResearcherInquiryLink($request_id, $allResearchers);

      $oldCollections = $this->getCollectionsByID($request_id);
      $oldCollectionIds = array_map('intval', array_keys($oldCollections));
      $newCollectionIds = array_map('intval', $collections);

      $added = array_diff($newCollectionIds, $oldCollectionIds);
      $removed = array_diff($oldCollectionIds, $newCollectionIds);

      if (!empty($added) || !empty($removed)) {
          $this->clearCollectionInquiryLink($request_id);
          $this->addCollectionInquiryLink($request_id, $newCollectionIds);

          foreach ($added as $cid) {
              $this->logEdit($request_id, "neoncollectionrequestlink", "coll_id", null, $cid, $uid);
          }
          foreach ($removed as $cid) {
              $this->logEdit($request_id, "neoncollectionrequestlink", "coll_id", $cid, null, $uid);
          }
      }


      return $request_id;

  }

  // log request table edits
  private function logEdit($request_id, $table, $field, $oldValue, $newValue, $uid) {

      if (is_string($oldValue)) $oldValue = trim($oldValue);
      if (is_string($newValue)) $newValue = trim($newValue);

      if ($oldValue === $newValue) {
          return;
      }

      $sql = "INSERT INTO neonrequestedit (request_id,tableName, fieldName, oldValue, newValue, uid, editTimestamp) 
              VALUES (?, ?, ?, ?, ?, ?, NOW())";
      $stmt = $this->conn->prepare($sql);
      $stmt->bind_param("issssi", $request_id,$table, $field, $oldValue, $newValue, $uid);
      $stmt->execute();
      $stmt->close();
  }

      // edit status
    public function editStatus(
      $request_id,
      $inquiry_date,
      $pendingfunding,
      $notfunded,
      $cut,
      $pendinglist,
      $fulfillment,
      $active,
      $complete,
      $uid
  ) {
      $request_id = (int)$request_id;

      $inquiry_date   = !empty($inquiry_date)   ? $inquiry_date   : null;
      $pendingfunding = !empty($pendingfunding) ? $pendingfunding : null;
      $notfunded      = !empty($notfunded)      ? $notfunded      : null;
      $cut            = !empty($cut)            ? $cut            : null;
      $pendinglist    = !empty($pendinglist)    ? $pendinglist    : null;
      $fulfillment    = !empty($fulfillment)    ? $fulfillment    : null;
      $active         = !empty($active)         ? $active         : null;
      $complete       = !empty($complete)       ? $complete       : null;

      $dates = [
          'sample use inquiry'           => $inquiry_date,
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
          $status_date = $latestDate;
      } else {
          $status = null;
          $status_date = null;
      }

      $oldSql = "SELECT * FROM neonrequest WHERE id = ?";
      $oldStmt = $this->conn->prepare($oldSql);
      $oldStmt->bind_param("i", $request_id);
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
                  status_date = ?,
                  inquiry_date = ?, 
                  pending_funding_date = ?, 
                  not_funded_date = ?, 
                  cut = ?,
                  pending_sample_list_date = ?, 
                  pending_fulfillment_date = ?,
                  active_date = ?, 
                  complete_date = ?, 
                  last_updated = NOW() 
              WHERE id = ?";

      $stmt = $this->conn->prepare($sql);
      if (!$stmt) {
          $this->errorMessage = "Prepare failed: " . $this->conn->error;
          return false;
      }

      $stmt->bind_param(
          "ssssssssssi",
          $status,
          $status_date,
          $inquiry_date,
          $pendingfunding,
          $notfunded,
          $cut,
          $pendinglist,
          $fulfillment,
          $active,
          $complete,
          $request_id
      );

      if (!$stmt->execute()) {
          $this->errorMessage = "Execute failed: " . $stmt->error;
          return false;
      }
      $stmt->close();

      $newData = [
          "status" => $status,
          "status_date" => $status_date,
          "inquiry_date" => $inquiry_date,
          "pending_funding_date" => $pendingfunding,
          "not_funded_date" => $notfunded,
          "cut" => $cut,
          "pending_sample_list_date" => $pendinglist,
          "pending_fulfillment_date" => $fulfillment,
          "active_date" => $active,
          "complete_date" => $complete
      ];

      foreach ($newData as $field => $newValue) {
          $oldValue = $oldData[$field] ?? null;
          if ($oldValue != $newValue) {
              $this->logEdit($request_id, "neonrequest", $field, $oldValue, $newValue, $uid);
          }
      }

      return $request_id;
  }


}

?>