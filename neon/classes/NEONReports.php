<?php

  include_once($SERVER_ROOT.'/classes/Manager.php');

 /**
 * Controler class for /neon/classes/NEONReports.php
 *
 */

 class NEONReports extends Manager {

  public function __construct() {
    parent::__construct(null,'readonly');
    $this->verboseMode = 2;
    set_time_limit(2000);
  }

  public function __destruct() {
    parent::__destruct();
  }

  // Main functions

// Find list of available monthly reports
    public function getAvailableMonthlyReports() {
        $sql = "SELECT DISTINCT name FROM neonmonthlyreport ORDER BY name DESC";
        $result = $this->conn->query($sql);

        if (!$result) {
            $this->errorMessage = 'Monthly report query was not successful';
            return [];
        }

        $months = [];
        while ($row = $result->fetch_assoc()) {
            $months[] = $row['name'];
        }

        return $months;
    }

// Find monthly report date
    public function getMonthlyReportDate($month) {
        $sql = "SELECT date FROM neonmonthlyreport WHERE name = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            $this->errorMessage = $this->conn->error;
            return null;
        }

        $stmt->bind_param('s', $month);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();

        return $row ? $row['date'] : null;
        
    }

    // gbif record count
    public function gbifCount(){
        $gbifUrl = 'https://api.gbif.org/v1/occurrence/search?publishingOrg=e794e60e-e558-4549-99f8-cfb241cdce24&limit=0';

        $ch = curl_init($gbifUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        $gbifCount = $data['count'] ?? 0;

        return $gbifCount;

    }

    // gbif literature citation count
    public function gbifLit(){
        $gbifUrl = 'https://api.gbif.org/v1/literature/search?publishingOrg=e794e60e-e558-4549-99f8-cfb241cdce24&limit=0';

        $ch = curl_init($gbifUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        $gbifCount = $data['count'] ?? 0;

        return $gbifCount;

    }

function getScholarProfileStats() {
    $url = "https://scholar.google.com/citations?user=MGg_jIcAAAAJ&hl=en";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64)");
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) return null;

    $stats = [
        'total_citations' => 0,
        'h_index' => 0,
        'i10_index' => 0,
    ];

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($doc);

    $tableRows = $xpath->query('//table[@id="gsc_rsb_st"]/tbody/tr');
    foreach ($tableRows as $row) {
        $cells = $xpath->query('td', $row);
        if ($cells->length >= 2) {
            $label = trim($cells->item(0)->textContent);
            $value = intval(trim($cells->item(1)->textContent));

            switch (strtolower($label)) {
                case 'citations':
                    $stats['total_citations'] = $value;
                    break;
                case 'h-index':
                    $stats['h_index'] = $value;
                    break;
                case 'i10-index':
                    $stats['i10_index'] = $value;
                    break;
            }
        }
    }

    return $stats;
}


    // Generates data for Monthly Report Data
    public function generateMonthlyReport() {
        $dataArr = [];

        $name = date('Y-m');
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

        foreach ($dataArr as $statName => $statValue) {
            $ins = $this->conn->prepare(
                "INSERT INTO neonmonthlyreport (`name`, `statistic`, `statValue`, `date`) VALUES (?, ?, ?, ?)"
            );

            // Bind all as strings to avoid type mismatch
            $ins->bind_param('ssss', $name, $statName, $statValue, $reportDate);
            $ins->execute();

            if ($ins->error) {
                error_log("Insert error for $statName: " . $ins->error);
            }
        }

        return $name;
    }


  // Gets data from Monthly Report
    public function getMonthlyReport($month){
        $dataArr = array();

        $currentsql = 'SELECT * FROM neonmonthlyreport WHERE name = ?';
        $currentstmt = $this->conn->prepare($currentsql);
        if (!$currentstmt) {
            $this->errorMessage = $this->conn->error;
            return null;
        }

        $currentstmt->bind_param('s', $month);
        $currentstmt->execute();
        $current = $currentstmt->get_result();
        $currentstmt->close();

        $priorsql = 'SELECT * FROM neonmonthlyreport WHERE name = (SELECT DISTINCT name FROM neonmonthlyreport WHERE name < ? ORDER BY name DESC LIMIT 1);';
        $priorstmt = $this->conn->prepare($priorsql);
        if (!$priorstmt) {
            $this->errorMessage = $this->conn->error;
            return null;
        }

        $priorstmt->bind_param('s', $month);
        $priorstmt->execute();
        $prior = $priorstmt->get_result();
        $priorstmt->close();

        $currentDate = null;
        $current->data_seek(0);
        if ($row = $current->fetch_assoc()) {
            $currentDate = $row['date'];
        }
        $priorDate = null;
        $prior->data_seek(0);
        if ($row = $prior->fetch_assoc()) {
            $priorDate = $row['date'];
        }

        $dayChange = '';

        if ($currentDate && $priorDate) {
            $d1 = new DateTime($priorDate);
            $d2 = new DateTime($currentDate);
            $days = $d1->diff($d2)->days;
            $dayChange = '+' . $days . ' days';
        }

        $currentData = [];
        while ($row = $current->fetch_assoc()) {
            $currentData[$row['statistic']] = (int)$row['statValue'];
        }

        $priorData = [];
        while ($row = $prior->fetch_assoc()) {
            $priorData[$row['statistic']] = (int)$row['statValue'];
        }

        $reportRows = [];

        $reportRows[] = [
            'prior report date',
            $priorDate ? date('Y-m-d', strtotime($priorDate)) : 'N/A',
            $dayChange
        ];
        foreach ($currentData as $stat => $currentValue) {
            $priorValue = $priorData[$stat] ?? null;

            if ($priorValue === null) {
                $change = '';  
            } else {
                $diff = $currentValue - $priorValue;
                $change = ($diff > 0 ? '+' : '') . $diff;
            }

            $reportRows[] = [
                $stat,
                $currentValue,
                $change
            ];
        }

        return $reportRows;
    
    }
}
?>