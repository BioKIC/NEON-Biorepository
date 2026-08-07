<?php

  include_once($SERVER_ROOT.'/classes/Manager.php');

 /**
 * Controler class for /neon/classes/NEONReports.php
 *
 */

 class SampleDashboard extends Manager {

  public function __construct() {
    parent::__construct(null,'readonly');
    $this->verboseMode = 2;
    set_time_limit(2000);
  }

  public function __destruct() {
    parent::__destruct();
  }

    public function cumulativeReceipt() {
        $sql = "
            SELECT
                sample_date,
                daily_samples,
                SUM(daily_samples) OVER (ORDER BY sample_date) AS cumulative_samples
            FROM (
                SELECT
                    DATE(s.initialtimestamp) AS sample_date,
                    COUNT(*) AS daily_samples
                FROM NeonSample s
                JOIN NeonShipment h
                    ON s.shipmentPK = h.shipmentPK
                WHERE h.shipmentID NOT LIKE '%eudo%'
                GROUP BY DATE(s.initialtimestamp)
            ) t
            ORDER BY sample_date";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function cumulativeShipments() {
        $sql = "SELECT
                shipment_date,
                daily_shipments,
                SUM(daily_shipments) OVER (ORDER BY shipment_date) AS cumulative_shipments
            FROM (
                SELECT
                    DATE(initialtimestamp) AS shipment_date,
                    COUNT(*) AS daily_shipments
                FROM NeonShipment 
                WHERE shipmentID NOT LIKE '%eudo%'
                GROUP BY DATE(initialtimestamp)
            ) t
            ORDER BY shipment_date";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function cumulativeCheckIn() {
        $sql = "SELECT
                checkin_date,
                daily_checkin,
                SUM(daily_checkin) OVER (ORDER BY checkin_date) AS cumulative_checkin
            FROM (
                SELECT
                    DATE(s.checkinTimestamp) AS checkin_date,
                    COUNT(*) AS daily_checkin
                FROM NeonSample s
                JOIN NeonShipment h
                ON s.shipmentPK = h.shipmentPK
                WHERE h.shipmentID NOT LIKE '%eudo%'
                GROUP BY DATE(s.checkinTimestamp)
            ) t
            ORDER BY checkin_date";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function cumulativeRecords() {
        $sql = "SELECT
                record_date,
                daily_records,
                SUM(daily_records) OVER (ORDER BY record_date) AS cumulative_records
            FROM (
                SELECT
                    DATE(dateEntered) AS record_date,
                    COUNT(*) AS daily_records
                FROM omoccurrences
                GROUP BY DATE(dateEntered)
            ) t
            ORDER BY record_date";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->get_result();
    }
    public function cumulativeTaxa() {
        $sql = "SELECT
                taxon_date,
                COUNT(*) OVER (ORDER BY taxon_date) AS cumulative_taxa
            FROM (
                SELECT
                    MIN(DATE(dateEntered)) AS taxon_date,
                    tidInterpreted
                FROM omoccurrences
                WHERE dateEntered IS NOT NULL
                AND tidInterpreted IS NOT NULL
                GROUP BY tidInterpreted
            ) t
            ORDER BY taxon_date;";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->get_result();
    }

    public function totalSamples() {
        $sql = "
            SELECT COUNT(*) AS total
            FROM NeonSample s
            JOIN NeonShipment h
                ON s.shipmentPK = h.shipmentPK
            WHERE h.shipmentID NOT LIKE '%eudo%';
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (int)$row['total'];
    }

    public function totalShipments() {
        $sql = "
            SELECT COUNT(*) AS total
            FROM NeonShipment
            WHERE shipmentID NOT LIKE '%eudo%';
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (int)$row['total'];
    }

    public function totalCheckIn() {
        $sql = "
            SELECT COUNT(*) AS total
            FROM NeonSample s
            JOIN NeonShipment h
                ON s.shipmentPK = h.shipmentPK
            WHERE h.shipmentID NOT LIKE '%eudo%'
            AND s.checkinTimestamp IS NOT NULL;";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (int)$row['total'];
    }

    public function totalRecords() {
        $sql = "
            SELECT COUNT(*) AS total
            FROM omoccurrences;";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (int)$row['total'];
    }

    public function totalTaxa() {
        $sql = "
            SELECT COUNT(DISTINCT(tidInterpreted)) AS total
            FROM omoccurrences;";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (int)$row['total'];
    }

 }
 ?>
