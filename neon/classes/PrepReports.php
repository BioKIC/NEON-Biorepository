<?php

  include_once($SERVER_ROOT.'/classes/Manager.php');

 /**
 * Controler class for /neon/classes/PrepReports.php
 *
 */

 class PrepReports extends Manager {

  public function __construct() {
    parent::__construct(null,'readonly');
    $this->verboseMode = 2;
    set_time_limit(2000);
  }

  public function __destruct() {
    parent::__destruct();
  }

  // Main functions

  // Gets data about preparations grouping by preparators
  // Uses "preparedBy" key:value available in "dynamicProperties" in table "omoccurrences"
  // For NEON, specifically used for Mammal collections, counting skin and ethanol preparations
  public function getMamPrepsCntByPreparator(){
    $dataArr = array();

    $sql = 'SELECT 
    prepBy AS preparator,
    SUM(skinPrepCnt) AS skinPrepCnt,
    SUM(flatskinPrepCnt) AS flatskinPrepCnt,
    SUM(fluidPrepCnt) AS fluidPrepCnt,
    SUM(skinPrepCnt) +  SUM(flatskinPrepCnt) +SUM(fluidPrepCnt) AS total
FROM (
    SELECT 
        TRIM(REGEXP_SUBSTR(dynamicProperties, "(?<=preparedBy:)(.*?)(?=,)")) AS prepBy, 
        COUNT(occid) AS skinPrepCnt,
        0 AS flatskinPrepCnt,
        0 AS fluidPrepCnt
    FROM omoccurrences
    WHERE dynamicProperties LIKE "%preparedBy%" 
    AND preparations LIKE "%skin%" AND preparations NOT LIKE "%flat%"
    AND collid IN (17, 19, 28)
    GROUP BY prepBy
    
    UNION ALL

        SELECT 
        TRIM(REGEXP_SUBSTR(dynamicProperties, "(?<=preparedBy:)(.*?)(?=,)")) AS prepBy, 
        0 AS skinPrepCnt,
        COUNT(occid) AS flatskinPrepCnt,
        0 AS fluidPrepCnt
    FROM omoccurrences
    WHERE dynamicProperties LIKE "%preparedBy%" 
    AND preparations LIKE "%flat%"
    AND collid IN (17, 19, 28)
    GROUP BY prepBy

    UNION ALL
    
    SELECT 
        TRIM(REGEXP_SUBSTR(dynamicProperties, "(?<=preparedBy:)(.*?)(?=,)")) AS prepBy, 
        0 AS skinPrepCnt,
        0 AS flatskinPrepCnt,
        COUNT(occid) AS fluidPrepCnt
    FROM omoccurrences
    WHERE dynamicProperties LIKE "%preparedBy%" 
    AND preparations LIKE "%eth%" 
    AND collid IN (17, 19, 28)
    GROUP BY prepBy
) AS combined
GROUP BY prepBy
ORDER BY preparator;';
    $result = $this->conn->query($sql);

    if ($result) {
      //output data of each row
      while ($row = $result->fetch_assoc()){
        // originally
        // $dataArr[] = $row;
        $dataArr[] = array(
          $row['preparator'],
          $row['skinPrepCnt'],
          $row['flatskinPrepCnt'],
          $row['fluidPrepCnt'],
          $row['total'],
        );
      }
      $totalsRow = array("prepBy" => "Total", "skinPrepCnt" => array_sum(array_column($dataArr, 1)), "flatskinPrepCnt" => array_sum(array_column($dataArr, 2)), "fluidPrepCnt" => array_sum(array_column($dataArr, 3)), "total" => array_sum(array_column($dataArr, 4)));
      $dataArr[] = $totalsRow; 
      $result->free();
    }
    else {
      $this->errorMessage = 'Preparations report query was not successfull';
      $dataArr = false;
    }
    return $dataArr;
  }
}
?>