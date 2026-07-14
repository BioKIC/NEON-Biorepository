<?php

  include_once($SERVER_ROOT.'/classes/Manager.php');

 /**
 * Controler class for /neon/classes/ArchiveUpload.php
 *
 */

 class ArchiveUpload extends Manager {

  public function __construct() {
    parent::__construct(null,'readonly');
    $this->verboseMode = 2;
    set_time_limit(2000);
  }

  public function __destruct() {
    parent::__destruct();
  }

  // Main functions

    // Find potential new archive data
    public function findPotentialNewArchiveSamples() {
        $sql ='SELECT disposition, COUNT(*) as count
                FROM omoccurrences o
                WHERE disposition IS NOT NULL
                AND availability = 0
                AND NOT EXISTS (
                    SELECT 1
                    FROM neonarchiveupload n
                    WHERE n.archiveGuid = o.catalogNumber
                )
                GROUP BY disposition;';


        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            $this->errorMessage = $this->conn->error;
            return null;
        }
        $stmt->execute();

        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $stmt->close();

        return $rows;
    }

    // Add new archive data
    public function addNewArchiveSamples($dispositions) {

        if (empty($dispositions)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($dispositions), '?'));

        $sql = "INSERT INTO neonarchiveupload
                    (sampleID, sampleCode, sampleClass, archiveGuid, sampleFate, submitted, initialTimestamp)
                SELECT
                    s.sampleID,
                    s.sampleCode,
                    s.sampleClass,
                    o.catalogNumber,
                    'consumed',
                    0,
                    NOW()
                FROM omoccurrences o
                JOIN NeonSample s ON o.occid = s.occid
                WHERE o.disposition IN ($placeholders)
                AND NOT EXISTS (
                    SELECT 1
                    FROM neonarchiveupload n
                    WHERE n.archiveGuid = o.catalogNumber
                )";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            $this->errorMessage = $this->conn->error;
            return false;
        }

        try {

            $this->conn->begin_transaction();

            $types = str_repeat('s', count($dispositions));
            $stmt->bind_param($types, ...$dispositions);

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $this->conn->commit();

            $status = true;

        } catch (Exception $e) {

            $this->conn->rollback();

            $this->errorMessage = $e->getMessage();

            $status = false;

        } finally {

            $stmt->close();

        }

        return $status;
    }

    // Grab new archive data
    public function getNewArchiveSampleTable() {

        $sql = 'SELECT archiveGuid,sampleID,sampleCode,sampleClass,sampleFate
                FROM neonarchiveupload
                WHERE submitted = 0';

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            $this->errorMessage = $this->conn->error;
            return null;
        }

        $stmt->execute();

        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $stmt->close();

        return $rows;
    }

    // Grab prior archive data
    public function getPriorArchiveSampleTable() {

        $sql = 'SELECT archiveGuid,sampleID,sampleCode,sampleClass,sampleFate
                FROM neonarchiveupload
                WHERE submitted = 1';

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            $this->errorMessage = $this->conn->error;
            return null;
        }

        $stmt->execute();

        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $stmt->close();

        return $rows;
    }

    // Update archive data to indicate what has been newly submitted

    public function updateArchiveData() {

        $sql = 'UPDATE neonarchiveupload
                SET submitted = 1
                WHERE submitted = 0';

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            $this->errorMessage = $this->conn->error;
            return false;
        }

        $status = $stmt->execute();

        if (!$status) {
            $this->errorMessage = $stmt->error;
        }

        $stmt->close();

        return $status;
    }

    // Table for export

    public function getArchiveExport($type) {

        $sql = 'SELECT archiveLaboratoryName,archiveStartDate,sampleID,sampleCode,sampleFate,sampleClass,
                archiveMedium,storageTemperature,scientificName,scientificNameAuthorship,identificationQualifier,
                sex,reproductiveCondition,lifeStage,identifiedBy,archiveGuid,accessionNumber,catalogueNumber,
                externalURLs,collectionCode,remarks
            FROM neonarchiveupload
            WHERE submitted = ?';

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            $this->errorMessage = $this->conn->error;
            return null;
        }

        $stmt->bind_param('i', $type);
        $stmt->execute();

        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $stmt->close();

        return $rows;

    }

}

?>