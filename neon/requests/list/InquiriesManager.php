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

  // Get researchers list
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

    // add inquiry
  public function addInquiry($collection_manager, $researcher_id, $inquiry_date = '') {

      $collection_manager = $this->conn->real_escape_string($collection_manager);
      $researcher_id = $this->conn->real_escape_string($researcher_id);
      $inquiry_date = $this->conn->real_escape_string($inquiry_date);

      $sql = "INSERT INTO neonrequest (collection_manager, researcher_id, inquiry_date) 
              VALUES ('$collection_manager', '$researcher_id', '$inquiry_date')";

      if ($this->conn->query($sql)) {
          return $this->conn->insert_id; 
      } else {
          $this->errorMessage = "Database Error: " . $this->conn->error;
          return false;
      }
  }



}

?>