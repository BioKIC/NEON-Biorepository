<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class OccurrenceLoans extends Manager{

	function __construct() {
		parent::__construct(null,'write');
	}

	function __destruct(){
		parent::__destruct();
	}

  // Gets all loans for all collections, with links for loan and collection
  public function getLoanOutAll(){
  	$dataArr = array();
    $sql = "SELECT
            l.loanid,
            l.collidown,
            CONCAT(l.forwhom,' (',i.institutioncode,')') AS requestor,
            l.forwhom,
            l.datesent,
            l.datedue,
            l.dateclosed,
            COUNT(o.occid) AS totalspecimens,
            SUM(CASE WHEN o.returndate IS NULL THEN 1 ELSE 0 END) AS specimensout,
            l.createdbyown AS assignee
        FROM omoccurloans AS l
        LEFT JOIN institutions AS i
            ON l.iidborrower = i.iid
        JOIN omoccurloanslink AS o
            ON l.loanid = o.loanid
        GROUP BY l.loanid;";
    if($result = $this->conn->query($sql)){
      while($row = $result->fetch_assoc()){
        $dataArr[] = array(
          'loanID' => '<a href="../collections/loans/outgoing.php?collid='.$row['collidown'].'&loanid='.$row['loanid'].'">'.$row['loanid'].'</a>',
          'requestor' => is_null($row['requestor'])?'<span style="color:lightgray;">NULL</span>':$row['requestor'],
          'dateSent' => is_null($row['datesent'])?'<span style="color:lightgray;">NULL</span>':$row['datesent'],
          'dateDue' => is_null($row['datedue'])?'<span style="color:lightgray;">NULL</span>':$row['datedue'],
          'dateClosed' => is_null($row['dateclosed'])?'<span style="color:lightgray;">NULL</span>':$row['dateclosed'],
          'totalSpecimens' => is_null($row['totalspecimens']) ? '<span style="color:lightgray;">NULL</span>' : $row['totalspecimens'],
          'specimensOut' => is_null($row['specimensout']) ? '<span style="color:lightgray;">NULL</span>' : $row['specimensout'],
          'assignee' => is_null($row['assignee'])?'<span style="color:lightgray;">NULL</span>':$row['assignee'],
        );
      }
      $result->free();
    }
    else {
      $this->errorMessage = 'Loan out query was not successfull';
      $dataArr = false;
    }
    return $dataArr;
  }

    // Gets all loans for all collections, with links for loan and collection, filtered by sample identifier
  public function getLoanOutFiltered($sample) {
      $sample = $this->cleanInStr($sample);
      $occids = $this->getOccid($sample);

      $dataArr = array();

      if ($occids === false) {
          return false;
      }

      if (empty($occids)) {
          return $dataArr;
      }

      $placeholders = implode(',', array_fill(0, count($occids), '?'));

      $sql = "SELECT
              l.loanid,
              l.collidown,
              CONCAT(l.forwhom, ' (', i.institutioncode, ')') AS requestor,
              l.forwhom,
              l.datesent,
              l.datedue,
              l.dateclosed,
              COUNT(o.occid) AS totalspecimens,
              SUM(CASE WHEN o.returndate IS NULL THEN 1 ELSE 0 END) AS specimensout,
              l.createdbyown AS assignee
          FROM omoccurloans AS l
          LEFT JOIN institutions AS i
              ON l.iidborrower = i.iid
          JOIN omoccurloanslink AS o
              ON l.loanid = o.loanid
          WHERE o.occid IN ($placeholders)
          GROUP BY l.loanid";

      $stmt = $this->conn->prepare($sql);

      if (!$stmt) {
          $this->errorMessage = "Database error: " . $this->conn->error;
          return false;
      }

      $types = str_repeat('i', count($occids));
      $stmt->bind_param($types, ...$occids);

      if (!$stmt->execute()) {
          $this->errorMessage = "Loan out query was not successful: " . $stmt->error;
          return false;
      }

      $result = $stmt->get_result();

      while ($row = $result->fetch_assoc()) {
          $dataArr[] = array(
              'loanID' => '<a href="../collections/loans/outgoing.php?collid=' . $row['collidown'] . '&loanid=' . $row['loanid'] . '">' . $row['loanid'] . '</a>',
              'requestor' => is_null($row['requestor']) ? '<span style="color:lightgray;">NULL</span>' : $row['requestor'],
              'dateSent' => is_null($row['datesent']) ? '<span style="color:lightgray;">NULL</span>' : $row['datesent'],
              'dateDue' => is_null($row['datedue']) ? '<span style="color:lightgray;">NULL</span>' : $row['datedue'],
              'dateClosed' => is_null($row['dateclosed']) ? '<span style="color:lightgray;">NULL</span>' : $row['dateclosed'],
              'matchingSpecimens' => is_null($row['totalspecimens']) ? '<span style="color:lightgray;">NULL</span>' : $row['totalspecimens'],
              'matchingSpecimensOut' => is_null($row['specimensout'])  ? '<span style="color:lightgray;">NULL</span>' : $row['specimensout'],
              'assignee' => is_null($row['assignee']) ? '<span style="color:lightgray;">NULL</span>' : $row['assignee'],
          );
      }

      $result->free();
      $stmt->close();

      return $dataArr;
  }

  // find occid for sample to filter loans

  public function getOccid($sample) {

      $sql = 'SELECT o.occid
              FROM omoccurrences AS o
              WHERE o.occid = ?
                OR o.catalogNumber = ?
                OR o.occurrenceID = ?
                OR o.recordID LIKE ?
                OR EXISTS (
                    SELECT 1
                    FROM omoccuridentifiers AS oi
                    WHERE oi.occid = o.occid
                      AND oi.identifierValue = ?
                )';

      $recordID = '%' . $sample;

      $stmt = $this->conn->prepare($sql);

      if (!$stmt) {
          $this->errorMessage = "Database error: " . $this->conn->error;
          return false;
      }

      $stmt->bind_param(
          "issss",
          $sample,
          $sample,
          $sample,
          $recordID,
          $sample
      );

      if (!$stmt->execute()) {
          $this->errorMessage = "Query error: " . $stmt->error;
          return false;
      }

      $result = $stmt->get_result();

      $occids = array();

      while ($row = $result->fetch_assoc()) {
          $occids[] = $row['occid'];
      }

      return $occids;
  }

  // Gets count of all samples in open loans in all collections
  public function getOutSamplesCnt(){
    $retArr = array();
    $sql = 'SELECT COUNT(occid) AS totalOut FROM omoccurloanslink WHERE returndate IS NULL;';
    if($result = $this->conn->query($sql)){
      while($row = $result->fetch_assoc()){
        $totalLoaned = $row['totalOut'];
      }
      $result->free();
    }
    else {
      $this->errorMessage = 'Loan out query was not successfull';
      $totalLoaned = false;
    }
    return $totalLoaned;
  }
}
?>