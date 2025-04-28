<?php
include_once('../config/symbini.php');
require_once('classes/OccurrenceSesar.php'); 
require_once('classes/igsnManager.php');

set_time_limit(0);

$igsnManager = new IgsnManager();
$taskList = $igsnManager->getIgsnTaskReport();

if ($taskList) {
    foreach ($taskList as $collid => $collArr) {
        $guidManager = new OccurrenceSesar();
        $guidManager->setCollid($collid);
        $guidManager->setCollArr();
        $guidManager->setIgsnSeed($igsnSeed);
        
        $guidManager->batchProcessIdentifiers();

    }
}
?>
