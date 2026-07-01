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
        $dataArr = [];

        $name = date('Y');
        $reportDate = date('Y-m-d H:i:s');



        $shipsql = 'SELECT COUNT(*) AS shipments FROM NeonShipment';
        $sampsql = 'SELECT COUNT(*) AS samples FROM NeonSample';
        $occursql = 'SELECT COUNT(*) AS occurrences FROM omoccurrences';
        $errorsql = 'SELECT COUNT(*) AS errors FROM NeonSample WHERE errorMessage IS NOT NULL AND acceptedForAnalysis = 1';
        $gbifCount = $this->gbifCount();
        $gbifLit = $this->gbifLit();
        $scholarData = $this->getScholarProfileStats();
        $googleScholarLit = $scholarData['total_citations'];
        $googleScholarHindex = $scholarData['h_index'];
        $googleScholarI10index = $scholarData['i10_index'];
        $activesql = "SELECT COUNT(*) AS activeuse FROM neonrequest WHERE status = 'active use'";
        $completesql = "SELECT COUNT(*) AS completed FROM neonrequest WHERE status = 'completed'";
        $notfunded = "SELECT COUNT(*) AS notfunded FROM neonrequest WHERE status = 'not funded'";
        $pendingfulfillmentsql = "SELECT COUNT(*) AS pendingfulfillment FROM neonrequest WHERE status = 'pending fulfillment'";
        $pendingfundingsql = "SELECT COUNT(*) AS pendingfunding FROM neonrequest WHERE status = 'pending funding'";
        $pendingsamplelistsql = "SELECT COUNT(*) AS pendingsamplelist FROM neonrequest WHERE status = 'pending sample list'";
        $inquirylistsql = "SELECT COUNT(*) AS inquiry FROM neonrequest WHERE status = 'sample inquiry'";
        $useunlikelysql = "SELECT COUNT(*) AS useunlikely FROM neonrequest WHERE status = 'sample use unlikely'";
        $totalsql = "SELECT COUNT(*) AS total FROM neonrequest";

        // Collect all query results
        $queries = [
            'shipments' => $shipsql,
            'samples' => $sampsql,
            'occurrences' => $occursql,
            'harvesting errors' => $errorsql,
            'GBIF records' => $gbifCount,
            'GBIF citations' => $gbifLit,
            'Google Scholar citations' => $googleScholarLit,
            'Google Scholar H index' => $googleScholarHindex,
            'Google Scholar I10 index' => $googleScholarI10index,
            'active use' => $activesql,
            'completed' => $completesql,
            'not funded' => $notfunded,
            'pending fulfillment' => $pendingfulfillmentsql,
            'pending funding' => $pendingfundingsql,
            'pending sample list' => $pendingsamplelistsql,
            'sample inquiry' => $inquirylistsql,
            'sample use unlikely' => $useunlikelysql,
            'total' => $totalsql
        ];

        foreach ($queries as $key => $sql) {
            if (is_string($sql)) {
                $result = $this->conn->query($sql);
                if ($result) {
                    $row = $result->fetch_assoc();
                    $dataArr[$key] = array_values($row)[0];
                } else {
                    $dataArr[$key] = 0;
                }
            } else {
                $dataArr[$key] = $sql;
            }
        }

        $type = 'general';

        foreach ($dataArr as $statName => $statValue) {

            if ($statName === 'active use') {
                $type = 'request';
            }

            $ins = $this->conn->prepare("INSERT INTO neonmonthlyreport (`name`, `type`, `statistic`, `statValue`, `date`) VALUES (?, ?, ?, ?, ?)");

            $ins->bind_param('sssss', $name, $type, $statName, $statValue, $reportDate);
            $ins->execute();

            if ($ins->error) {
                error_log("Insert error for $statName: " . $ins->error);
            }
        }

        $samplesql = "SELECT sampleClass, COUNT(*) AS count FROM NeonSample GROUP BY sampleClass";

        $result = $this->conn->query($samplesql);

        if ($result) {
            $ins = $this->conn->prepare(
                "INSERT INTO neonmonthlyreport (`name`, `type`, `statistic`, `statValue`, `date`)
                VALUES (?, 'sample', ?, ?, ?)"
            );

            while ($row = $result->fetch_assoc()) {
                $statistic = $row['sampleClass'] ?? 'Unknown sampleClass';
                $statValue = $row['count'];

                $ins->bind_param(
                    'ssss',
                    $name,
                    $statistic,
                    $statValue,
                    $reportDate
                );

                $ins->execute();

                if ($ins->error) {
                    error_log("Sample insert error ($statistic): " . $ins->error);
                }
            }

            $ins->close();
        }

        return $name;
    }


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