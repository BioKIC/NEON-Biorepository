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
    public function getReportDate($ay) {
        $sql = "SELECT MAX(date) as date FROM neonsowreport WHERE name = ? LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            $this->errorMessage = $this->conn->error;
            return null;
        }

        $stmt->bind_param('s', $ay);
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
        $this->dataStats($name,$reportDate);
        $this->loanStats($name,$reportDate);

        return $name;
    }

    // Calculate loan requests stats

    private function loanStats($ay,$reportDate) {

        $sql = "SELECT
                CASE
                    WHEN awardYear = (2000 + ?) THEN
                        CONCAT(
                            awardYear,
                            ' ',
                            CASE
                                WHEN MONTH(pendingFulfillmentDate) IN (10,11,12,1,2,3) THEN 'Q1+Q2'
                                WHEN MONTH(pendingFulfillmentDate) IN (4,5,6) THEN 'Q3'
                            END
                        )
                    ELSE awardYear
                END AS awardYearLabel,

                COUNT(*) AS requests,

                ROUND(
                    AVG(
                        DATEDIFF(activeDate, pendingFulfillmentDate)
                    ),
                    1
                ) AS meanDays,

                ROUND(
                    STDDEV_SAMP(
                        DATEDIFF(activeDate, pendingFulfillmentDate)
                    ),
                    1
                ) AS stdDays,

                ROUND(
                    100.0 * COUNT(
                        CASE
                            WHEN DATEDIFF(activeDate, pendingFulfillmentDate) <= 90
                            THEN 1
                        END
                    ) / COUNT(*),
                    1
                ) AS percent30DaysAll,

                ROUND(
                    100.0 * COUNT(
                        CASE
                            WHEN processing <> 'yes'
                            AND moreThan100 <> 1
                            AND DATEDIFF(activeDate, pendingFulfillmentDate) <= 90
                            THEN 1
                        END
                    ) /
                    COUNT(
                        CASE
                            WHEN processing <> 'yes'
                            AND moreThan100 <> 1
                            THEN 1
                        END
                    ),
                    1
                ) AS percent30DaysTypical

            FROM (
                SELECT
                    pendingFulfillmentDate,
                    activeDate,
                    processing,
                    moreThan100,
                    CASE
                        WHEN MONTH(pendingFulfillmentDate) >= 10
                            THEN YEAR(pendingFulfillmentDate) + 1
                        ELSE YEAR(pendingFulfillmentDate)
                    END AS awardYear
                FROM NeonRequest
                WHERE pendingFulfillmentDate IS NOT NULL
                AND activeDate IS NOT NULL
                AND pendingFulfillmentDate > '2021-09-30'
            ) r

            WHERE NOT (
                awardYear = (2000 + ?)
                AND MONTH(pendingFulfillmentDate) IN (7,8,9)
            )

            GROUP BY awardYearLabel
            
            UNION ALL

            SELECT
                'Total' AS awardYearLabel,

                COUNT(*) AS requests,

                ROUND(
                    AVG(DATEDIFF(activeDate, pendingFulfillmentDate)),
                    1
                ) AS meanDays,

                ROUND(
                    STDDEV_SAMP(DATEDIFF(activeDate, pendingFulfillmentDate)),
                    1
                ) AS stdDays,

                ROUND(
                    100.0 * COUNT(
                        CASE
                            WHEN DATEDIFF(activeDate, pendingFulfillmentDate) <= 90
                            THEN 1
                        END
                    ) / COUNT(*),
                    1
                ) AS percent30DaysAll,

                ROUND(
                    100.0 * COUNT(
                        CASE
                            WHEN processing <> 'yes'
                            AND moreThan100 <> 1
                            AND DATEDIFF(activeDate, pendingFulfillmentDate) <= 90
                            THEN 1
                        END
                    ) /
                    COUNT(
                        CASE
                            WHEN processing <> 'yes'
                            AND moreThan100 <> 1
                            THEN 1
                        END
                    ),
                    1
                ) AS percent30DaysTypical

            FROM NeonRequest

            WHERE pendingFulfillmentDate IS NOT NULL
            AND activeDate IS NOT NULL
            AND pendingFulfillmentDate > '2021-09-30'
            ORDER BY awardYearLabel;";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $ay, $ay);
        $stmt->execute();

        $result = $stmt->get_result();

        $insert = $this->conn->prepare("
                INSERT INTO NeonSOWReport
                    (name, type, awardYear, statistic, statValue, date)
                VALUES (?, 'loans', ?, ?, ?, ?)
            ");

            while ($row = $result->fetch_assoc()) {

                $awardYear = $row['awardYearLabel'];

                foreach ([
                    'requests',
                    'meanDays',
                    'stdDays',
                    'percent30DaysAll',
                    'percent30DaysTypical'
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

     // Calculate accessionStats

    private function accessionStats($ay,$reportDate) {
        $sql = "SELECT
            CASE
                WHEN awardYear = (2000 + ?) THEN
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

            COUNT(DISTINCT samplePK) AS samples,

            COUNT(DISTINCT CASE
                WHEN checkinUid IS NOT NULL THEN samplePK
            END) AS samplesCheckedIn,

            ROUND(
                AVG(
                    CASE
                        WHEN checkinUid IS NOT NULL
                        THEN DATEDIFF(checkinTimestamp, dateShipped)
                    END
                ),
                1
            ) AS meanDays,

            ROUND(
                STDDEV_SAMP(
                    CASE
                        WHEN checkinUid IS NOT NULL
                        THEN DATEDIFF(checkinTimestamp, dateShipped)
                    END
                ),
                1
            ) AS stdDays,

            ROUND(
                100.0 * COUNT(DISTINCT CASE
                    WHEN checkinUid IS NOT NULL THEN samplePK
                END)
                / COUNT(DISTINCT samplePK),
                1
            ) AS percentCheckedIn,

            ROUND(
                100.0 * COUNT(DISTINCT CASE
                    WHEN checkinUid IS NOT NULL
                    AND DATEDIFF(checkinTimestamp, dateShipped) <= 90
                    THEN samplePK
                END)
                / COUNT(DISTINCT CASE WHEN checkInUid IS NOT NULL THEN samplePK END),
                1
            ) AS percent90Days

            FROM (
                SELECT
                    h.dateShipped,
                    s.samplePK,
                    s.checkinUid,
                    s.checkinTimestamp,
                    CASE
                        WHEN MONTH(h.dateShipped) >= 10 THEN YEAR(h.dateShipped) + 1
                        ELSE YEAR(h.dateShipped)
                    END AS awardYear
                FROM NeonShipment h
                JOIN NeonSample s
                    ON h.shipmentPK = s.shipmentPK
                WHERE h.shipmentID NOT LIKE '%seudo%'
                AND (s.sampleReceived != 0 OR s.sampleReceived IS NULL)
            ) x

            WHERE NOT (
                awardYear = (2000 + ?)
                AND MONTH(dateShipped) IN (7,8,9)
            )

            GROUP BY awardYearLabel
            UNION ALL

            SELECT
                'Total' AS awardYearLabel,

                COUNT(DISTINCT samplePK) AS samples,

                COUNT(DISTINCT CASE
                    WHEN checkinUid IS NOT NULL THEN samplePK
                END) AS samplesCheckedIn,

                ROUND(
                    AVG(
                        CASE
                            WHEN checkinUid IS NOT NULL
                            THEN DATEDIFF(checkinTimestamp, dateShipped)
                        END
                    ),
                    1
                ) AS meanDays,

                ROUND(
                    STDDEV_SAMP(
                        CASE
                            WHEN checkinUid IS NOT NULL
                            THEN DATEDIFF(checkinTimestamp, dateShipped)
                        END
                    ),
                    1
                ) AS stdDays,

                ROUND(
                    100.0 * COUNT(DISTINCT CASE
                        WHEN checkinUid IS NOT NULL THEN samplePK
                    END)
                    / COUNT(DISTINCT samplePK),
                    1
                ) AS percentCheckedIn,

                ROUND(
                    100.0 * COUNT(DISTINCT CASE
                        WHEN checkinUid IS NOT NULL
                        AND DATEDIFF(checkinTimestamp, dateShipped) <= 90
                        THEN samplePK
                    END)
                    / COUNT(DISTINCT CASE WHEN checkInUid IS NOT NULL THEN samplePK END),
                    1
                ) AS percen90Days

            FROM (
                SELECT
                    h.dateShipped,
                    s.samplePK,
                    s.checkinUid,
                    s.checkinTimestamp
                FROM NeonShipment h
                JOIN NeonSample s
                    ON h.shipmentPK = s.shipmentPK
                WHERE h.shipmentID NOT LIKE '%seudo%'
                AND (s.sampleReceived != 0 OR s.sampleReceived IS NULL)

            ) x

                    ORDER BY awardYearLabel;";


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
                    'meanDays',
                    'stdDays',
                    'percentCheckedIn',
                    'percent90Days'
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

    // Calculate data availability Stats

    private function dataStats($ay,$reportDate) {
        $sql = "SELECT
            CASE
                WHEN x.awardYear = (2000 + ?) THEN
                    CONCAT(
                        x.awardYear,
                        ' ',
                        CASE
                            WHEN MONTH(x.dateShipped) IN (10,11,12,1,2,3) THEN 'Q1+Q2'
                            WHEN MONTH(x.dateShipped) IN (4,5,6) THEN 'Q3'
                        END
                    )
                ELSE x.awardYear
            END AS awardYearLabel,

            COUNT(DISTINCT x.samplePK) AS samples,

            COUNT(DISTINCT CASE
                WHEN x.occid IS NOT NULL AND o.dateEntered IS NOT NULL
                THEN x.samplePK
            END) AS samplesWithData,

            ROUND(
                AVG(
                    CASE
                        WHEN x.occid IS NOT NULL AND o.dateEntered IS NOT NULL
                        THEN DATEDIFF(o.dateEntered, x.dateShipped)
                    END
                ),
                1
            ) AS meanDays,

            ROUND(
                STDDEV_SAMP(
                    CASE
                        WHEN x.occid IS NOT NULL AND o.dateEntered IS NOT NULL
                        THEN DATEDIFF(o.dateEntered, x.dateShipped)
                    END
                ),
                1
            ) AS stdDays,

            ROUND(
                100.0 * COUNT(DISTINCT CASE
                    WHEN x.occid IS NOT NULL AND o.dateEntered IS NOT NULL
                    THEN x.samplePK
                END)
                / COUNT(DISTINCT x.samplePK),
                1
            ) AS percentWithData,

            ROUND(
                100.0 * COUNT(DISTINCT CASE
                    WHEN x.occid IS NOT NULL
                    AND o.dateEntered IS NOT NULL
                    AND DATEDIFF(o.dateEntered, x.dateShipped) <= 90
                    THEN x.samplePK
                END)
                / COUNT(DISTINCT x.samplePK),
                1
            ) AS percent90Days

        FROM (
            SELECT
                h.dateShipped,
                s.samplePK,
                s.occid,
                CASE
                    WHEN MONTH(h.dateShipped) >= 10 THEN YEAR(h.dateShipped) + 1
                    ELSE YEAR(h.dateShipped)
                END AS awardYear
            FROM NeonShipment h
            JOIN NeonSample s
                ON h.shipmentPK = s.shipmentPK
            WHERE h.shipmentID NOT LIKE '%seudo%'
                AND (s.sampleReceived != 0 OR s.sampleReceived IS NULL)

        ) x

        LEFT JOIN omoccurrences o
            ON x.occid = o.occid

        WHERE NOT (
            x.awardYear = (2000 + ?)
            AND MONTH(x.dateShipped) IN (7,8,9)
        )

        GROUP BY awardYearLabel

        UNION ALL

        SELECT
            'Total' AS awardYearLabel,

            COUNT(DISTINCT x.samplePK) AS samples,

            COUNT(DISTINCT CASE
                WHEN x.occid IS NOT NULL AND o.dateEntered IS NOT NULL
                THEN x.samplePK
            END) AS samplesWithData,

            ROUND(
                AVG(
                    CASE
                        WHEN x.occid IS NOT NULL AND o.dateEntered IS NOT NULL
                        THEN DATEDIFF(o.dateEntered, x.dateShipped)
                    END
                ),
                1
            ) AS meanDays,

            ROUND(
                STDDEV_SAMP(
                    CASE
                        WHEN x.occid IS NOT NULL AND o.dateEntered IS NOT NULL
                        THEN DATEDIFF(o.dateEntered, x.dateShipped)
                    END
                ),
                1
            ) AS stdDays,

            ROUND(
                100.0 * COUNT(DISTINCT CASE
                    WHEN x.occid IS NOT NULL AND o.dateEntered IS NOT NULL
                    THEN x.samplePK
                END)
                / COUNT(DISTINCT x.samplePK),
                1
            ) AS percentWithData,

            ROUND(
                100.0 * COUNT(DISTINCT CASE
                    WHEN x.occid IS NOT NULL
                    AND o.dateEntered IS NOT NULL
                    AND DATEDIFF(o.dateEntered, x.dateShipped) <= 90
                    THEN x.samplePK
                END)
                / COUNT(DISTINCT x.samplePK),
                1
            ) AS percent90Days

        FROM (
            SELECT
                h.dateShipped,
                s.samplePK,
                s.occid,
                CASE
                    WHEN MONTH(h.dateShipped) >= 10 THEN YEAR(h.dateShipped) + 1
                    ELSE YEAR(h.dateShipped)
                END AS awardYear
            FROM NeonShipment h
            JOIN NeonSample s
                ON h.shipmentPK = s.shipmentPK
            WHERE h.shipmentID NOT LIKE '%seudo%'
                AND (s.sampleReceived != 0 OR s.sampleReceived IS NULL)

        ) x

        LEFT JOIN omoccurrences o
            ON x.occid = o.occid

        ORDER BY
            CASE
                WHEN awardYearLabel = 'Total' THEN 9999
                ELSE CAST(LEFT(awardYearLabel,4) AS UNSIGNED)
            END,
            awardYearLabel;";


        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $ay, $ay);
        $stmt->execute();

        $result = $stmt->get_result();

        $insert = $this->conn->prepare("
                INSERT INTO NeonSOWReport
                    (name, type, awardYear, statistic, statValue, date)
                VALUES (?, 'data', ?, ?, ?, ?)
            ");

            while ($row = $result->fetch_assoc()) {

                $awardYear = $row['awardYearLabel'];

                foreach ([
                    'samples',
                    'samplesWithData',
                    'meanDays',
                    'stdDays',
                    'percentWithData',
                    'percent90Days'
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

            $sql = $sql = "SELECT
                    CASE
                        WHEN awardYear = (2000 + ?) THEN
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
                    COUNT(DISTINCT CASE
                        WHEN receiptStatus IS NOT NULL THEN shipmentID
                    END) AS receiptsSubmitted,

                    ROUND(
                        100.0 * COUNT(DISTINCT CASE
                            WHEN receiptStatus IS NOT NULL THEN shipmentID
                        END)
                        / COUNT(DISTINCT shipmentID),
                        1
                    ) AS percentSubmitted

                FROM (
                    SELECT *,
                        CASE
                            WHEN MONTH(dateShipped) >= 10 THEN YEAR(dateShipped) + 1
                            ELSE YEAR(dateShipped)
                        END AS awardYear
                    FROM NeonShipment
                ) s

                WHERE NOT (
                    awardYear = (2000 + ?)
                    AND MONTH(dateShipped) IN (7,8,9)
                )

                GROUP BY awardYearLabel

                UNION ALL

                SELECT
                    'Total' AS awardYearLabel,

                    COUNT(DISTINCT shipmentID) AS shipments,

                    COUNT(DISTINCT CASE
                        WHEN receiptStatus IS NOT NULL THEN shipmentID
                    END) AS receiptsSubmitted,

                    ROUND(
                        100.0 * COUNT(DISTINCT CASE
                            WHEN receiptStatus IS NOT NULL THEN shipmentID
                        END)
                        / COUNT(DISTINCT shipmentID),
                        1
                    ) AS percentSubmitted

                FROM NeonShipment

                ORDER BY
                    CASE
                        WHEN awardYearLabel = 'Total' THEN 9999
                        ELSE CAST(LEFT(awardYearLabel,4) AS UNSIGNED)
                    END,
                    awardYearLabel";

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
                    'percentSubmitted'
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

    // Gets data for SOW Report tables
    public function getSOWReport($ay, $type, $reportDate) {

        $sql = 'SELECT awardYear, statistic, statValue
                FROM NeonSOWReport
                WHERE name = ?
                AND type = ?
                AND date = ?
                ORDER BY awardYear';

        if ($type == 'receipts') {
            $sql .= ", FIELD(statistic,
                            'shipments',
                            'receiptsSubmitted',
                            'percentSubmitted'
                        );";
        }

        elseif ($type == 'accessioning') {
            $sql .= ", FIELD(statistic,
                            'samples',
                            'samplesCheckedIn',
                            'meanDays',
                            'stdDays',
                            'percentCheckedIn',
                            'percent90Days'
                        );";
        }

        elseif ($type == 'data') {
            $sql .= ", FIELD(statistic,
                            'samples',
                            'samplesWithData',
                            'meanDays',
                            'stdDays',
                            'percentWithData',
                            'percent90Days'
                        );";
        }
        elseif ($type == 'loans') {
            $sql .= ", FIELD(statistic,
                            'requests',
                            'meanDays',
                            'stdDays',
                            'percent30DaysAll',
                            'percent30DaysTypical'
                        );";
        }


        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sss', $ay, $type, $reportDate);
        $stmt->execute();

        $result = $stmt->get_result();

        $rows = [];

        while ($row = $result->fetch_assoc()) {

            $awardYear = $row['awardYear'];

            if (!isset($rows[$awardYear])) {
                $rows[$awardYear] = [
                    0 => $awardYear
                ];
            }

            $rows[$awardYear][$row['statistic']] = $row['statValue'];
        }

        $stmt->close();
        $result->free();

        return array_values($rows);
    }

    // export table

    public function exportTable($ay, $type, $reportDate){
        $ay = (int)$ay;

        $fileName = $type . '_AY' . $ay . '.csv';

        $result = $this->getSOWReport($ay, $type, $reportDate);
    if (empty($result)) {
        die(
            "No rows returned.<br>" .
            "AY = $ay<br>" .
            "Type = $type<br>" .
            "Report Date = $reportDate"
        );
    }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        fputcsv($output, array_keys($result[0]));

        foreach ($result as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }


    // latency to receipt plot

    public function receiptPlot($reportDate) {

        $sql = "
            SELECT
                dateShipped,
                DATEDIFF(receiptTimestamp, dateShipped) AS daysToReceiptSubmission
            FROM NeonShipment
            WHERE shipmentID NOT LIKE '%seudo%'
                AND receiptTimestamp IS NOT NULL
                AND dateShipped < ?
            ORDER BY dateShipped
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $reportDate);
        $stmt->execute();

        $result = $stmt->get_result();

        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[] = [
                'x' => $row['dateShipped'],
                'y' => (int)$row['daysToReceiptSubmission']
            ];
        }
        
        $result->free();
        $stmt->close();

        return $rows;
    }

    // latency to accession in plot

        public function checkinPlot($reportDate) {

        $sql = "SELECT
                    DATE_FORMAT(h.dateShipped, '%Y-%m-01') AS shipmentMonth,
                    AVG(DATEDIFF(s.checkinTimestamp, h.dateShipped)) AS meanDaysToCheckIn
                FROM NeonSample s
                JOIN NeonShipment h
                    ON s.shipmentPK = h.shipmentPK
                WHERE h.shipmentID NOT LIKE '%seudo%'
                    AND s.checkinUid IS NOT NULL
                    AND s.acceptedForAnalysis = 1
                    AND h.dateShipped < ?
                GROUP BY shipmentID
                ORDER BY shipmentMonth;";

        // $sql = "SELECT
        //     h.dateShipped, 
        //     DATEDIFF(s.checkinTimestamp, h.dateShipped) AS daysToSampleCheckIn
        //     FROM NeonSample s
        //     LEFT JOIN NeonShipment h
        //     ON s.shipmentPK = h.shipmentPK
        //     WHERE h.shipmentID NOT LIKE '%seudo%'
        //         AND s.checkinUid IS NOT NULL
        //         AND s.acceptedForAnalysis = 1
        //         AND h.dateShipped < ?
        //     ORDER BY h.dateShipped
        //     LIMIT 10000";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $reportDate);
        $stmt->execute();

        $result = $stmt->get_result();

        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[] = [
                'x' => $row['shipmentMonth'],
                'y' => (int)$row['meanDaysToCheckIn']
            ];
        }
        
        $result->free();
        $stmt->close();

        return $rows;
    }

    // latency to data availability

    public function dataPlot($reportDate) {

        $sql = "SELECT
                    DATE_FORMAT(h.dateShipped, '%Y-%m-01') AS shipmentMonth,
                    AVG(DATEDIFF(o.dateEntered, h.dateShipped)) AS meanDaysToDataAvailability
                FROM omoccurrences o
                JOIN NeonSample s
                ON o.occid = s.occid
                JOIN NeonShipment h
                    ON s.shipmentPK = h.shipmentPK
                WHERE h.shipmentID NOT LIKE '%seudo%'
                    AND s.checkinUid IS NOT NULL
                    AND s.acceptedForAnalysis = 1
                    AND h.dateShipped < ?
                GROUP BY shipmentID
                ORDER BY shipmentMonth;";


        // $sql = " SELECT h.dateShipped, DATEDIFF(o.dateEntered, h.dateShipped) as daysToDataAvailability
        //         FROM NeonSample s
        //         LEFT JOIN NeonShipment h
        //         ON s.shipmentPK = h.shipmentPK
        //         LEFT JOIN omoccurrences o
        //         ON s.occid = o.occid
        //         WHERE shipmentID NOT LIKE '%seudo%'
        //         AND s.checkinUid IS NOT NULL
        //         AND s.acceptedForAnalysis = 1
        //         AND h.dateShipped < ?;";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $reportDate);
        $stmt->execute();

        $result = $stmt->get_result();

        $rows = [];

        while ($row = $result->fetch_assoc()) {
            $rows[] = [
                'x' => $row['shipmentMonth'],
                'y' => (int)$row['meanDaysToDataAvailability']
            ];
        }

        $result->free();
        $stmt->close();

        return $rows;
    }



}

?>