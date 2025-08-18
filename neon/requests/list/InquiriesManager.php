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

   public function addInquiry($collection_manager, $researcher_id, $inquiry_date,$title,$collections,$field,$secondaryfields,$funded,$fundingsource,$description,$howfound,$dataproduced,$existing,$future,$new,$additionalresearchers,$drivefolder) {

    $collection_manager = $this->conn->real_escape_string($collection_manager);
    $researcher_id = $this->conn->real_escape_string($researcher_id);
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

    $sql = "INSERT INTO neonrequest 
        (collection_manager, researcher_id, inquiry_date, status_date, status, title, primary_research_field, secondary_research_field, funded, funding_source, description, how_found_us, data_produced, existing_samples, future_samples, generating_samples, folder_name, laste_updated) 
        VALUES 
        ('$collection_manager', '$researcher_id', '$inquiry_date', '$inquiry_date', 'sample inquiry', '$title', '$field', '$secondaryfields', '$funded', '$fundingsource', '$description', '$howfound', '$dataproduced', '$existing', '$future', '$new', '$drivefolder', NOW())";

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


}

?>