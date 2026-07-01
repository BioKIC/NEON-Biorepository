<?php

  include_once($SERVER_ROOT.'/classes/Manager.php');

 /**
 * Controler class for /neon/classes/SOWReport.php
 *
 */

 class SOWReport extends Manager {

  public function __construct() {
    parent::__construct(null,'readonly');
    $this->verboseMode = 2;
    set_time_limit(2000);
  }

  public function __destruct() {
    parent::__destruct();
  }

  // Main functions

// Find list of available  reports
    public function getAvailableReports() {
        $sql = "SELECT DISTINCT name FROM neonsowreport ORDER BY name DESC";
        $result = $this->conn->query($sql);

        if (!$result) {
            $this->errorMessage = 'Report query was not successful';
            return [];
        }

        $ays = [];
        while ($row = $result->fetch_assoc()) {
            $ays[] = $row['name'];
        }

        return $ays;
    }

    // Find  report date
    public function getReportDate($period) {
        $sql = "SELECT date FROM neonsowreport WHERE name = ? LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            $this->errorMessage = $this->conn->error;
            return null;
        }

        $stmt->bind_param('s', $period);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();

        return $row ? $row['date'] : null;
        
    }


    // Generates data for SOW Report 
    public function generateSOWReport() {

        $reportDate = date('Y-m-d H:i:s');

        $name = (int) date('y');  
        $month = (int) date('m');

        if ($month != 7) {
            throw new Exception('SOW report can only be generated in July (end of Q3).');
        }

        $this->receiptStats($name,$reportDate);
        $this->accessionStats($name,$reportDate);
        // $this->dataStats($name,$reportDate);
        // $this->loanStats($name,$reportDate);

        return $name;
    }

    // Calculate accessionStats

    private function accessionStats($ay,$reportDate) {
        $sql = "SELECT
                CASE
                    WHEN (
                        CASE
                            WHEN MONTH(h.dateShipped) >= 10 THEN YEAR(h.dateShipped) + 1
                            ELSE YEAR(h.dateShipped)
                        END
                    ) = ?
                    THEN CONCAT(
                        CASE
                            WHEN MONTH(h.dateShipped) >= 10 THEN YEAR(h.dateShipped) + 1
                            ELSE YEAR(h.dateShipped)
                        END,
                        ' ',
                        CASE
                            WHEN MONTH(h.dateShipped) IN (10,11,12,1,2,3) THEN 'Q1+Q2'
                            WHEN MONTH(h.dateShipped) IN (4,5,6) THEN 'Q3'
                        END
                    )
                    ELSE
                        CASE
                            WHEN MONTH(h.dateShipped) >= 10 THEN YEAR(h.dateShipped) + 1
                            ELSE YEAR(h.dateShipped)
                        END
                END AS awardYearLabel,

                COUNT(DISTINCT s.samplePK) AS samples,
                COUNT(DISTINCT CASE WHEN s.checkinUid IS NOT NULL THEN s.samplePK END) AS samplesCheckedIn

            FROM NeonShipment h
            JOIN NeonSample s
                ON h.shipmentPK = s.shipmentPK

            WHERE NOT (
                (
                    CASE
                        WHEN MONTH(h.dateShipped) >= 10 THEN YEAR(h.dateShipped) + 1
                        ELSE YEAR(h.dateShipped)
                    END
                ) = 2026
                AND MONTH(h.dateShipped) IN (7,8,9)
            )

            GROUP BY awardYearLabel
            ORDER BY
                CASE
                    WHEN MONTH(h.dateShipped) >= 10 THEN YEAR(h.dateShipped) + 1
                    ELSE YEAR(h.dateShipped)
                END;";


        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $ay, $ay);
        $stmt->execute();

        $result = $stmt->get_result();

        $insert = $this->conn->prepare("
                INSERT INTO NeonSOWReport
                    (name, type, awardYear, statistic, statValue, date)
                VALUES (?, 'accessioning', ?, ?, ?, ?)
            ");

            while ($row = $result->fetch_assoc()) {

                $awardYear = $row['awardYearLabel'];

                foreach ([
                    'samples',
                    'samplesCheckedIn',
                    'meanDaysToCheckIn',
                    'stdevDaysToCheckIn',
                    'medianDaysToCheckIn',
                    'proportionCheckedIn',
                    'proportion30Days'
                ] as $stat) {

                    $value = $row[$stat];

                    $insert->bind_param(
                        "sssss",
                        $ay,
                        $awardYear,
                        $stat,
                        $value,
                        $reportDate
                    );

                    $insert->execute();
                }
            }

            $insert->close();
            $stmt->close();
    }

    // Calculate Receipt Stats

        private function receiptStats($ay, $reportDate) {

            $sql = "SELECT
                        CASE
                            WHEN awardYear = ? THEN
                                CONCAT(
                                    awardYear,
                                    ' ',
                                    CASE
                                        WHEN MONTH(dateShipped) IN (10,11,12,1,2,3) THEN 'Q1+Q2'
                                        WHEN MONTH(dateShipped) IN (4,5,6) THEN 'Q3'
                                    END
                                )
                            ELSE awardYear
                        END AS awardYearLabel,

                        COUNT(DISTINCT shipmentID) AS shipments,
                        COUNT(DISTINCT CASE WHEN receiptStatus IS NOT NULL THEN shipmentID END) AS receiptsSubmitted,
                        ROUND(
                            100.0 * COUNT(DISTINCT CASE WHEN receiptStatus IS NOT NULL THEN shipmentID END)
                            / COUNT(DISTINCT shipmentID),
                            1
                        ) AS proportionSubmitted

                    FROM (
                        SELECT *,
                            CASE
                                WHEN MONTH(dateShipped) >= 10 THEN YEAR(dateShipped) + 1
                                ELSE YEAR(dateShipped)
                            END AS awardYear
                        FROM NeonShipment
                    ) s

                    WHERE NOT (
                        awardYear = ?
                        AND MONTH(dateShipped) IN (7,8,9)
                    )

                    GROUP BY awardYearLabel
                    ORDER BY awardYear";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $ay, $ay);
            $stmt->execute();

            $result = $stmt->get_result();

            $insert = $this->conn->prepare("
                INSERT INTO NeonSOWReport
                    (name, type, awardYear, statistic, statValue, date)
                VALUES (?, 'receipts', ?, ?, ?, ?)
            ");

            while ($row = $result->fetch_assoc()) {

                $awardYear = $row['awardYearLabel'];

                foreach ([
                    'shipments',
                    'receiptsSubmitted',
                    'proportionSubmitted'
                ] as $stat) {

                    $value = $row[$stat];

                    $insert->bind_param(
                        "sssss",
                        $ay,
                        $awardYear,
                        $stat,
                        $value,
                        $reportDate
                    );

                    $insert->execute();
                }
            }

            $insert->close();
            $stmt->close();
        }



    ################ STUFF NEEDED

    // Gets data for SOW Report tables
    public function getSOWReport($ay) {

        $currentsql = 'SELECT statistic, statValue, type, date
                    FROM neonmonthlyreport
                    WHERE name = ?';

        $currentstmt = $this->conn->prepare($currentsql);
        $currentstmt->bind_param('s', $month);
        $currentstmt->execute();
        $current = $currentstmt->get_result();
        $currentstmt->close();

        $priorsql = '
            SELECT statistic, statValue, type, date
            FROM neonmonthlyreport
            WHERE name = (
                SELECT DISTINCT name
                FROM neonmonthlyreport
                WHERE name < ?
                ORDER BY name DESC
                LIMIT 1
            )';

        $priorstmt = $this->conn->prepare($priorsql);
        $priorstmt->bind_param('s', $month);
        $priorstmt->execute();
        $prior = $priorstmt->get_result();
        $priorstmt->close();

        $currentDate = null;
        $priorDate   = null;

        if ($row = $current->fetch_assoc()) {
            $currentDate = $row['date'];
        }
        $current->data_seek(0);

        if ($row = $prior->fetch_assoc()) {
            $priorDate = $row['date'];
        }
        $prior->data_seek(0);

        $dayChange = '';
        if ($currentDate && $priorDate) {
            $d1 = new DateTime($priorDate);
            $d2 = new DateTime($currentDate);
            $dayChange = '+' . $d1->diff($d2)->days . ' days';
        }

        $currentData = [];
        while ($row = $current->fetch_assoc()) {
            $currentData[$row['statistic']] = [
                'value' => (int)$row['statValue'],
                'type'  => $row['type']
            ];
        }

        $priorData = [];
        while ($row = $prior->fetch_assoc()) {
            $priorData[$row['statistic']] = (int)$row['statValue'];
        }

        $reportRows = [];

        $reportRows[] = [
            'general',
            'Prior report date',
            $priorDate ? date('Y-m-d', strtotime($priorDate)) : 'N/A',
            $dayChange
        ];
        foreach ($currentData as $stat => $data) {
            $currentValue = $data['value'];
            $type         = $data['type'];
            $priorValue   = $priorData[$stat] ?? null;

            if ($priorValue === null) {
                $change = '';  
            } else {
                $diff = $currentValue - $priorValue;
                $change = ($diff > 0 ? '+' : '') . $diff;
            }

            $reportRows[] = [
                $type,
                $stat,
                $currentValue,
                $change
            ];
        }

        return $reportRows;
    
    }

}

?>