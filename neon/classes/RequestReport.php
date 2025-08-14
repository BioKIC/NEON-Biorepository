<?php

  include_once($SERVER_ROOT.'/classes/Manager.php');

 /**
 * Controler class for /neon/classes/RequestReport.php
 *
 */

 class RequestReport extends Manager {

  public function __construct() {
    parent::__construct(null,'readonly');
    $this->verboseMode = 2;
    set_time_limit(2000);
  }

  public function __destruct() {
    parent::__destruct();
  }

  // Main functions

  // Gets data about requests grouping by status
  // Uses "status" in table "requests"
  public function getRequestsByStatus(){
    $dataArr = array();

    $sql = 'SELECT 
    status, COUNT(*) AS count
    FROM requests
    GROUP BY status;';
    $result = $this->conn->query($sql);

    if ($result) {
      //output data of each row
      while ($row = $result->fetch_assoc()){
        // originally
        // $dataArr[] = $row;
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
}
?>