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

// Find list of available  reports
    public function getAvailableReports($type) {
        if ($type == 'monthly'){
            $sql = "SELECT DISTINCT name FROM neonmonthlyreport ORDER BY name DESC";
        }
        if ($type == 'quarterly'){
            $sql = "SELECT DISTINCT name FROM neonquarterlyreport ORDER BY name DESC";
        }
        $result = $this->conn->query($sql);

        if (!$result) {
            $this->errorMessage = 'Report query was not successful';
            return [];
        }

        $months = [];
        while ($row = $result->fetch_assoc()) {
            $months[] = $row['name'];
        }

        return $months;
    }

// Find  report date
    public function getReportDate($period,$type) {

        if ($type == 'monthly'){
            $sql = "SELECT date FROM neonmonthlyreport WHERE name = ? LIMIT 1";
        }
        if ($type == 'quarterly'){
           $sql = "SELECT date FROM neonquarterlyreport WHERE name = ? LIMIT 1";

        }

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
        $gbifUrl = 'https://api.gbif.org/v1/literature/search?publishingOrganizationKey=e794e60e-e558-4549-99f8-cfb241cdce24&limit=0';

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


    // Generates data for Monthly Report 
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


    // Gets data for Monthly Report tables
    public function getMonthlyReport($month) {

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

    public function samplesReceivedBarChart($reportDate) {
        $sql = "SELECT sampleClass,COUNT(samplePK) AS count
                FROM NeonSample 
                WHERE initialTimeStamp <= ?
                AND sampleClass NOT LIKE 'EMPTY%' 
                GROUP BY sampleClass";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $reportDate);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function generateQuarterlyReport() {
        $reportDate = date('Y-m-d H:i:s');

        $year = (int) date('y');  
        $month = (int) date('m');

        $quarterMap = [1  => 1, 4  => 2, 7  => 3, 11 => 4];

        if (!isset($quarterMap[$month])) {
            throw new Exception('Quarterly report can only be generated in the month directly following completion of a quarter.');
        }

        $quarter = $quarterMap[$month];
        $name = "AY{$year} Q{$quarter}";

        preg_match('/AY(\d{2})Q([1-4])/', $name, $matches);

        $ayYear  = 2000 + $year; 
        $startyear = ($ayYear - 1) . '-10-01';

        switch ($quarter) {
            case 1:
                $startquarter = ($ayYear - 1) . '-10-01';
                $endquarter   = ($ayYear - 1) . '-12-31';
                break;

            case 2:
                $startquarter = $ayYear . '-01-01';
                $endquarter   = $ayYear . '-03-31';
                break;

            case 3:
                $startquarter = $ayYear . '-04-01';
                $endquarter   = $ayYear . '-06-30';
                break;

            case 4:
                $startquarter = $ayYear . '-07-01';
                $endquarter   = $ayYear . '-09-30';
                break;

            default:
                throw new Exception('Invalid quarter');
        }

        $this->researchersRequestsStatus($name,$reportDate,$startquarter,$endquarter,$startyear);
        $this->researchersSamplesCollection($name,$reportDate,$startquarter,$endquarter,$startyear);
        $this->samplesByField($name,$reportDate,$startquarter,$endquarter,$startyear);
        $this->sampleUseByInitiationAYBarChart($name,$reportDate,$endquarter);
        $this->sampleUseByStatusAYBarChart($name,$reportDate,$endquarter);
        $this->cumulativeRequests($endquarter,$reportDate);
        $this->cumulativeSampleRequests($endquarter,$reportDate);

        return $name;
    
    }

    public function researchersRequestsStatus($name,$reportDate,$startquarter,$endquarter,$startyear){

        $sql = "SELECT
                    CASE
                        WHEN r.activeDate BETWEEN ? AND ? 
                            THEN 'active/complete'
                        WHEN r.pendingFulfillmentDate BETWEEN ?  AND ? 
                            THEN 'pending fulfillment'
                        WHEN r.pendingSampleListDate BETWEEN ?  AND ? 
                            THEN 'pending sample list'
                        WHEN r.pendingFundingDate BETWEEN ?  AND ? 
                            THEN 'pending funding'
                    END AS periodStatus,
                    COUNT(DISTINCT p.requestID) AS requests,
                    COUNT(DISTINCT p.researcherID) AS researchers
                FROM neonrequest r
                JOIN neonresearcherrequestlink p
                    ON r.id = p.requestID
                WHERE p.researcherID != 371
                AND r.status IN ('active use','completed','pending sample list','pending funding','pending fulfillment')
                AND (
                    r.activeDate               BETWEEN ?  AND ? 
                    OR r.pendingFulfillmentDate   BETWEEN ?  AND ? 
                    OR r.pendingFundingDate       BETWEEN ?  AND ? 
                    OR r.pendingSampleListDate    BETWEEN ?  AND ? 
                )
                GROUP BY periodStatus";

        $stmt = $this->conn->prepare($sql);


        $periodtypes = array('Quarter','Award Year','To Date');

        foreach ($periodtypes as $period) {

            if ($period == 'Quarter') $start = $startquarter;
            elseif ($period == 'Award Year') $start = $startyear;
            elseif ($period == 'To Date') $start = '2010-01-01';

            $stmt->bind_param('ssssssssssssssss',
                $start, $endquarter,
                $start, $endquarter,
                $start, $endquarter,
                $start, $endquarter,
                $start, $endquarter,
                $start, $endquarter,
                $start, $endquarter,
                $start, $endquarter
            );

            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {

                $ins = $this->conn->prepare("INSERT INTO neonquarterlyreport (`name`, `period`, `tabletype`, `status`,`requests`,`researchers`, `date`) VALUES (?, ?, 'Researchers and Requests by Status', ?, ?, ?, ?)");

                $ins->bind_param('sssiis', $name, $period, $row['periodStatus'],  $row['requests'], $row['researchers'], $reportDate);
                $ins->execute();

                if ($ins->error) {
                    error_log("Insert error for Researchers and Requests by Status: " . $ins->error);
                }
            }
        }
    }

    public function researchersSamplesCollection($name,$reportDate,$startquarter,$endquarter,$startyear){
        $sql = "WITH filtered_requests AS (
            SELECT id
            FROM neonrequest r
            WHERE r.status IN (
                'completed',
                'active use',
                'pending fulfillment',
                'pending funding',
                'pending sample list'
            )
            AND (
                r.activeDate              BETWEEN ? AND ?
                OR r.pendingFulfillmentDate  BETWEEN ? AND ?
                OR r.pendingFundingDate      BETWEEN ? AND ?
                OR r.pendingSampleListDate   BETWEEN ? AND ?
            )
        ),

        coll_request_map AS (
            SELECT DISTINCT
                cr.collID,
                cr.requestID
            FROM neoncollectionrequestlink cr
            JOIN filtered_requests fr
                ON cr.requestID = fr.id
        ),

        researchers_per_coll AS (
            SELECT
                crm.collID,
                COUNT(DISTINCT rr.researcherID) AS researchers
            FROM coll_request_map crm
            JOIN neonresearcherrequestlink rr
                ON rr.requestID = crm.requestID
            GROUP BY crm.collID
        ),

        samples_per_coll AS (
            SELECT
                oa.collID,
                COUNT(DISTINCT sr.id) AS samples
            FROM neonsamplerequestlink sr
            JOIN filtered_requests fr
                ON sr.requestID = fr.id
            JOIN omoccurrences oa
                ON sr.occid = oa.occid
            GROUP BY oa.collID
        ),

        physical_samples_per_coll AS (
            SELECT
                oa.collID,
                COUNT(DISTINCT sr.id) AS physical_samples
            FROM neonsamplerequestlink sr
            JOIN filtered_requests fr
                ON sr.requestID = fr.id
            JOIN neonrequest r
                ON sr.requestID = r.id
            JOIN omoccurrences oa
                ON sr.occid = oa.occid
            WHERE
                (
                    sr.substanceProvided IS NULL
                    OR sr.substanceProvided NOT IN ('image', 'data')
                )
                AND (
                    r.outreach != 'yes'
                    OR r.internal != 'yes'
                )
            GROUP BY oa.collID
        )

        SELECT
            o.collectionName,
            COALESCE(rpc.researchers, 0)      AS researchers,
            COALESCE(spc.samples, 0)          AS samples,
            COALESCE(psc.physical_samples, 0) AS physicalSamples
        FROM omcollections o
        LEFT JOIN researchers_per_coll rpc
            ON o.collID = rpc.collID
        LEFT JOIN samples_per_coll spc
            ON o.collID = spc.collID
        LEFT JOIN physical_samples_per_coll psc
            ON o.collID = psc.collID
        WHERE
            COALESCE(rpc.researchers, 0) > 0
            OR COALESCE(spc.samples, 0) > 0
            OR COALESCE(psc.physical_samples, 0) > 0
        ORDER BY o.collectionName";


        $stmt = $this->conn->prepare($sql);


        $periodtypes = array('Quarter','Award Year','To Date');

        foreach ($periodtypes as $period) {

            if ($period == 'Quarter') $start = $startquarter;
            elseif ($period == 'Award Year') $start = $startyear;
            elseif ($period == 'To Date') $start = '2010-01-01';

            $stmt->bind_param('ssssssss',
                $start, $endquarter,
                $start, $endquarter,
                $start, $endquarter,
                $start, $endquarter
            );

            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {

                $ins = $this->conn->prepare("INSERT INTO neonquarterlyreport (`name`, `period`, `tabletype`, `collectionname`,`researchers`, `samples`,`physicalSamples`,`date`) VALUES (?, ?, 'Researchers and Samples by Collection', ?, ?, ?, ?, ?)");

                $ins->bind_param('sssiiis', $name, $period, $row['collectionName'],  $row['researchers'], $row['samples'],$row['physicalSamples'], $reportDate);
                $ins->execute();

                if ($ins->error) {
                    error_log("Insert error for Researchers and Samples by Collection: " . $ins->error);
                }
            }
        }
    }

    public function samplesByField($name,$reportDate,$startquarter,$endquarter,$startyear){
        $sql = "WITH filtered_requests AS (
            SELECT
                r.id,
                r.primaryResearchField,
                r.outreach,
                r.internal
            FROM neonrequest r
            WHERE r.status IN (
                'active use',
                'completed',
                'pending sample list',
                'pending funding',
                'pending fulfillment'
            )
            AND (
                r.activeDate              BETWEEN ? AND ?
                OR r.pendingFulfillmentDate  BETWEEN ? AND ?
                OR r.pendingFundingDate      BETWEEN ? AND ?
                OR r.pendingSampleListDate   BETWEEN ? AND ?
            )
        )

        SELECT 
            r.primaryResearchField AS field,

            COUNT(s.id) AS samples,

            COUNT(
                CASE
                    WHEN
                        (
                            s.substanceProvided IS NULL
                            OR s.substanceProvided NOT IN ('image','data')
                        )
                        AND (
                            r.outreach != 'yes'
                            OR r.internal != 'yes'
                        )
                    THEN s.id
                END
            ) AS physicalSamples

        FROM neonsamplerequestlink s
        JOIN filtered_requests r
            ON s.requestID = r.id
        GROUP BY r.primaryResearchField
        ";


        $stmt = $this->conn->prepare($sql);


        $periodtypes = array('Quarter','Award Year','To Date');

        foreach ($periodtypes as $period) {

            if ($period == 'Quarter') $start = $startquarter;
            elseif ($period == 'Award Year') $start = $startyear;
            elseif ($period == 'To Date') $start = '2010-01-01';

            $stmt->bind_param('ssssssss',
                $start, $endquarter,
                $start, $endquarter,
                $start, $endquarter,
                $start, $endquarter
            );

            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {

                $ins = $this->conn->prepare("INSERT INTO neonquarterlyreport (`name`, `period`, `tabletype`, `field`, `samples`,`physicalSamples`,`date`) VALUES (?, ?, 'Samples by Primary Research Field', ?, ?, ?, ?)");

                $ins->bind_param('sssiis', $name, $period, $row['field'], $row['samples'], $row['physicalSamples'], $reportDate);
                $ins->execute();

                if ($ins->error) {
                    error_log("Insert error for Samples by Primary Research Field: " . $ins->error);
                }
            }
        }
    }

    public function samplesDistributed($startquarter,$endquarter){
        $sql = 'SELECT s.occid,s.sampleID,s.sampleCode,s.sampleClass,r.name,r.institution
                FROM neonsamplerequestlink sr
                LEFT JOIN neonrequestshipment h 
                ON sr.shipmentID= h.id
                LEFT JOIN neonresearcher r
                ON h.researcherID=r.researcherID
                LEFT JOIN NeonSample s
                ON sr.occid=s.occid
                WHERE h.shipDate >= ? AND h.shipDate <= ?';

        $stmt = $this->conn->prepare($sql);

        $stmt->bind_param('ss',$startquarter, $endquarter);

        $stmt->execute();

        if (!$stmt) {
            error_log($this->conn->error);
            return [];
        }

        $result = $stmt->get_result();
        
        if (!$result) {
            error_log("Error for Samples Distributed This Quarter: " . $ins->error);
        }

        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $stmt->close();
        return $rows;
        
    }

    public function samplesConsumed($startquarter,$endquarter){
        $sql = "SELECT n.sampleID,n.sampleCode,s.useType
                FROM neonsamplerequestlink s
                LEFT JOIN neonrequestshipment h
                ON s.shipmentID = h.id
                LEFT JOIN NeonSample n
                ON s.occid=n.occid
                WHERE h.shipDate >= ? AND h.shipDate <= ?
                AND s.useType IN ('destructive','consumptive')";

        $stmt = $this->conn->prepare($sql);

        $stmt->bind_param('ss',$startquarter, $endquarter);

        $stmt->execute();

        if (!$stmt) {
            error_log($this->conn->error);
            return [];
        }

        $result = $stmt->get_result();
        
        if (!$result) {
            error_log("Error for Samples Consumed This Quarter: " . $ins->error);
        }

        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $stmt->close();
        return $rows;
        
    }

    public function dataEdits($startquarter,$endquarter){
        $sql = 'SELECT * FROM omoccuredits WHERE initialTimestamp >= ? AND initialTimestamp <= ?';

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('ss', $startquarter, $endquarter);
            $stmt->execute();

            $result = $stmt->get_result();

            if (!$result) {
                error_log("Error for Data Edits This Quarter: " . $ins->error);
            }
            $rows = [];

            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }

            $stmt->close();
            return $rows;
        }

    public function samplesGenerated($startquarter,$endquarter){
        $sql = 'SELECT * FROM omoccurrences WHERE collid IN (4,85) AND dateEntered >= ? AND dateEntered <= ?';

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss', $startquarter, $endquarter);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
         if (!$result) {
            error_log("Error for Samples Generated This Quarter: " . $ins->error);
        }
            $stmt->close();
            return $rows;
    }

    public function datasetsGenerated($startquarter,$endquarter){
        $sql = 'SELECT * FROM omoccurdatasets WHERE initialTimestamp >= ? AND initialTimestamp <= ?';

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss', $startquarter, $endquarter);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
         if (!$result) {
            error_log("Error for Datasets Generated This Quarter: " . $ins->error);
        }

        $stmt->close();
        return $rows;
    }

        // Gets data for Quarterly Report tables
    public function getQuarterlyReport($quarter) {

        $sql = 'SELECT *
                FROM neonquarterlyreport
                WHERE name = ?';

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $quarter);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $stmt->close();
        return $rows;
    }

    // Remove empty columns
    public function removeNullColumns(array $rows): array {

        if (empty($rows)) return $rows;

        $columns = array_keys($rows[0]);
        $keep = [];

        foreach ($columns as $col) {
            foreach ($rows as $row) {
                if ($row[$col] !== null) {
                    $keep[] = $col;
                    break;
                }
            }
        }

        $final = [];
        foreach ($rows as $row) {
            $newRow = [];
            foreach ($keep as $col) {
                $newRow[$col] = $row[$col];
            }
            $final[] = $newRow;
        }

        return $final;
    }

    public function sampleUseByInitiationAYBarChart($name,$reportDate,$endquarter){
        $sql = "SELECT
                    CASE
                        WHEN EXTRACT(MONTH FROM inquiryDate) >= 10
                            THEN EXTRACT(YEAR FROM inquiryDate) + 1
                        ELSE EXTRACT(YEAR FROM inquiryDate)
                    END AS initiationAY,
                    statustype,
                    COUNT(id) as requests
                FROM (
                    SELECT
                        id,
                        inquiryDate,
                        statustype,
                        date,
                        ROW_NUMBER() OVER (
                            PARTITION BY id
                            ORDER BY date DESC
                        ) AS rn
                    FROM (
                        SELECT id, inquiryDate,'initial inquiry only' AS statustype, inquiryDate AS date
                        FROM neonrequest

                        UNION ALL
                        SELECT id,inquiryDate, 'active/complete', activeDate
                        FROM neonrequest

                        UNION ALL
                        SELECT id,inquiryDate, 'pending funding/fulfillment', pendingFundingDate
                        FROM neonrequest

                        UNION ALL
                        SELECT id, inquiryDate,'pending funding/fulfillment', pendingSampleListDate
                        FROM neonrequest

                        UNION ALL
                        SELECT id, inquiryDate,'pending funding/fulfillment', pendingFulfillmentDate
                        FROM neonrequest
                        
                        UNION ALL
                        SELECT id, inquiryDate,'not funded', notFundedDate
                        FROM neonrequest
                    ) t
                    WHERE date IS NOT NULL AND date <= ?
                ) ranked
                WHERE rn = 1
                GROUP BY initiationAY, statustype";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s',$endquarter);

        $stmt->execute();
        $result = $stmt->get_result();
        
        $ins = $this->conn->prepare("INSERT INTO neonquarterlyreport (`name`, `period`, `tabletype`, `initiationOrStatusAY`, `status`, `requests`,`date`) VALUES (?, 'To Date', 'Sample Use By Initiation Year Bar Chart', ?, ?, ?, ?)");

        while ($row = $result->fetch_assoc()) {

            $ins->bind_param('sssis', $name, $row['initiationAY'], $row['statustype'], $row['requests'], $reportDate);
               $ins->execute();

            if ($ins->error) {
                error_log("Insert error for Bar Chart Data: " . $ins->error);
            }
        }
    }

    public function sampleUseByStatusAYBarChart($name,$reportDate,$endquarter){
        $sql = "SELECT
                    CASE
                        WHEN EXTRACT(MONTH FROM date) >= 10
                            THEN EXTRACT(YEAR FROM date) + 1
                        ELSE EXTRACT(YEAR FROM date)
                    END AS statusAY,
                    statustype,
                    COUNT(id) as requests
                FROM (
                    SELECT
                        id,
                        statustype,
                        date,
                        ROW_NUMBER() OVER (
                            PARTITION BY id
                            ORDER BY date DESC
                        ) AS rn
                    FROM (
                        SELECT id, 'initial inquiry only' AS statustype, inquiryDate AS date
                        FROM neonrequest

                        UNION ALL
                        SELECT id, 'active/complete', activeDate
                        FROM neonrequest

                        UNION ALL
                        SELECT id, 'pending funding/fulfillment', pendingFundingDate
                        FROM neonrequest

                        UNION ALL
                        SELECT id,'pending funding/fulfillment', pendingSampleListDate
                        FROM neonrequest

                        UNION ALL
                        SELECT id,'pending funding/fulfillment', pendingFulfillmentDate
                        FROM neonrequest
                        
                        UNION ALL
                        SELECT id, 'not funded', notFundedDate
                        FROM neonrequest
                    ) t
                    WHERE date IS NOT NULL AND date <= ?
                ) ranked
                WHERE rn = 1
                GROUP BY statusAY,statustype";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s',$endquarter);

        $stmt->execute();
        $result = $stmt->get_result();
        
        $ins = $this->conn->prepare("INSERT INTO neonquarterlyreport (`name`, `period`, `tabletype`, `initiationOrStatusAY`, `status`, `requests`,`date`) VALUES (?, 'To Date', 'Sample Use By Status Year Bar Chart', ?, ?, ?, ?)");

        while ($row = $result->fetch_assoc()) {

            $ins->bind_param('sssis', $name, $row['statusAY'], $row['statustype'], $row['requests'], $reportDate);
               $ins->execute();

            if ($ins->error) {
                error_log("Insert error for Bar Chart Data: " . $ins->error);
            }
        }
    }

    public function cumulativeRequests($endquarter,$reportDate) {
        $sql = "SELECT
                    inquiryDate AS date,
                    'all inquiries' AS statustype,
                    ROW_NUMBER() OVER (ORDER BY inquiryDate) AS rank
                FROM neonrequest
                WHERE inquiryDate <= ?

                UNION ALL

                SELECT
                    activeDate AS date,
                    'active requests' AS statustype,
                    ROW_NUMBER() OVER (ORDER BY activeDate) AS rank
                FROM neonrequest
                WHERE activeDate IS NOT NULL
                AND activeDate <= ? ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss',$endquarter,$endquarter);

        $stmt->execute();
        $result = $stmt->get_result();
        
        $ins = $this->conn->prepare("INSERT INTO neoncumulativerequest (`date`, `statustype`, `rank`,`reportDate`) VALUES (?, ?, ?, ?)");

        while ($row = $result->fetch_assoc()) {

            $ins->bind_param('ssis', $row['date'], $row['statustype'], $row['rank'], $reportDate);
               $ins->execute();

            if ($ins->error) {
                error_log("Insert error for cumulative requests: " . $ins->error);
            }
        }
    }

    public function cumulativeSampleRequests($endquarter,$reportDate) {
        $sql = "SELECT h.shipDate as date, 'all sample use' as type, 
                    ROW_NUMBER() OVER (ORDER BY h.shipDate) AS samples
                FROM neonsamplerequestlink s
                LEFT JOIN neonrequestshipment h
                ON s.shipmentID=h.id
                LEFT JOIN neonrequest r
                ON s.requestID=r.id
                WHERE h.shipDate <= ?
                AND s.status NOT IN ('not funded','request not fulfilled')

                UNION ALL

                SELECT h.shipDate as date, 'research use of physical samples' as type,
                    ROW_NUMBER() OVER (ORDER BY h.shipDate) AS samples
                FROM neonsamplerequestlink s
                LEFT JOIN neonrequestshipment h
                ON s.shipmentID=h.id
                LEFT JOIN neonrequest r
                ON s.requestID=r.id
                WHERE h.shipDate <= ?
                AND s.status NOT IN ('not funded','request not fulfilled')
                AND s.substanceProvided NOT IN ('image','data')
                AND r.internal = 'no'
                AND r.outreach = 'no'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss',$endquarter,$endquarter);

        $stmt->execute();
        $result = $stmt->get_result();
        
        $ins = $this->conn->prepare("INSERT INTO neoncumulativesamplerequest (`date`, `type`, `samples`,`reportDate`) VALUES (?, ?, ?, ?)");

        while ($row = $result->fetch_assoc()) {

            $ins->bind_param('ssis', $row['date'], $row['type'], $row['samples'], $reportDate);
               $ins->execute();

            if ($ins->error) {
                error_log("Insert error for cumulative samples: " . $ins->error);
            }
        }
    }
}

?>