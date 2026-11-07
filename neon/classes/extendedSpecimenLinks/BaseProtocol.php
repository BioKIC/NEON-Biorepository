<?php

abstract class BaseProtocol {

    protected mysqli $conn;
    protected array $collids;
    protected $logger;
    protected array $sampleViewCache = [];

    public function __construct(mysqli $conn, array $collids, ?callable $logger = null) {
        $this->conn = $conn;
        $this->collids = array_values(array_unique(array_map('intval', $collids)));
        $this->logger = $logger;
    }

    abstract public function run(): void;

    /**
     * Retrieve identifiers and eventIDs for this protocol's collections.
     * A fallback tag is used only when the primary tag has no value.
     * Existing callers continue to receive only records with an identifier.
     */
    protected function getOccurrencesWithIdentifier(string $identifierName, ?string $fallbackIdentifierName = null, bool $includeMissing = false): array {
        if (!$this->collids) return [];

        $collidList = implode(',', $this->collids);
        $valueColumn = 'i.identifierValue';
        $nameColumn = 'i.identifierName';
        $fallbackJoin = '';

        if ($fallbackIdentifierName !== null) {
            $valueColumn = 'COALESCE(i.identifierValue, fallback.identifierValue)';
            $nameColumn = 'COALESCE(i.identifierName, fallback.identifierName)';
            $fallbackJoin = "
                LEFT JOIN omoccuridentifiers fallback ON fallback.occid = o.occid
                    AND fallback.identifierName = ?
                    AND TRIM(fallback.identifierValue) <> ''
                    AND i.occid IS NULL
            ";
        }

        $identifierFilter = $includeMissing ? '' : "AND {$valueColumn} IS NOT NULL";
        $sql = "
            SELECT o.occid, o.eventID,
                {$valueColumn} AS identifierValue,
                {$nameColumn} AS identifierName
            FROM omoccurrences o
            LEFT JOIN omoccuridentifiers i ON i.occid = o.occid
                AND i.identifierName = ?
                AND TRIM(i.identifierValue) <> ''
            {$fallbackJoin}
            WHERE o.collid IN ({$collidList})
                {$identifierFilter}
        ";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new RuntimeException('Unable to prepare occurrence identifier query: ' . $this->conn->error);
        }

        if ($fallbackIdentifierName !== null) {
            $stmt->bind_param('ss', $identifierName, $fallbackIdentifierName);
        } else {
            $stmt->bind_param('s', $identifierName);
        }

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException('Unable to execute occurrence identifier query: ' . $error);
        }

        $result = $stmt->get_result();
        $records = [];

        while ($row = $result->fetch_assoc()) {
            $row['occid'] = (int)$row['occid'];
            $records[] = $row;
        }

        $stmt->close();

        return $records;
    }

    protected function getOccurrencesWithEventID(): array {
        // Retain missing identifiers so callers can report errors or use eventID.
        return $this->getOccurrencesWithIdentifier('NEON sampleID', 'Originating NEON sampleID', true);
    }

    protected function getYear(string $date): ?string {
        // Compact YYYYMMDD dates, or a standalone four-digit year in
        // separated dates (YYYY-MM-DD, MM/DD/YYYY, etc.).
        if (preg_match('/^([0-9]{4})[0-9]{4}$/', $date, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match_all('/(?<![0-9])[0-9]{4}(?![0-9])/', $date, $matches) === 1) {
            return $matches[0][0];
        }

        return null;
    }

    protected function getLocationIDs(): array {
        if (!$this->collids) return [];

        $collidList = implode(',', $this->collids);

        $sql = "
            SELECT DISTINCT locationID
            FROM omoccurrences
            WHERE collid IN ({$collidList})
                AND locationID IS NOT NULL
                AND locationID <> ''
        ";

        $result = $this->conn->query($sql);

        if (!$result) {
            throw new RuntimeException('Unable to retrieve locationIDs: ' . $this->conn->error);
        }

        $locationIDs = [];

        while ($row = $result->fetch_assoc()) {
            $locationIDs[] = $row['locationID'];
        }

        return $locationIDs;
    }

    protected function getOccurrenceIDsForLocation(string $locationID): array {
        $sql = "
            SELECT occid
            FROM omoccurrences
            WHERE locationID = ?
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $locationID);
        $stmt->execute();

        $result = $stmt->get_result();
        $occids = [];

        while ($row = $result->fetch_assoc()) {
            $occids[] = (int)$row['occid'];
        }

        $stmt->close();

        return $occids;
    }

    protected function linkOccurrenceGroup(array $occids, string $relationship, string $sourceIdentifier, string $notes): int {
        $occids = array_values(array_unique(array_map('intval', $occids)));

        if (count($occids) < 2) return 0;

        $created = 0;
        $count = count($occids);

        for ($i = 0; $i < $count - 1; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $occidA = $occids[$i];
                $occidB = $occids[$j];

                if ($this->createAssociation($occidA, $occidB, $relationship, $sourceIdentifier, $notes)) $created++;
                if ($this->createAssociation($occidB, $occidA, $relationship, $sourceIdentifier, $notes)) $created++;
            }
        }

        return $created;
    }

    protected function createAssociation(int $occid, int $occidAssociate, string $relationship, string $sourceIdentifier, string $notes): bool {
        if ($occid === $occidAssociate) return false;

        $sql = "
            INSERT INTO omoccurassociations (
                occid,
                associationType,
                occidAssociate,
                relationship,
                sourceIdentifier,
                notes,
                initialtimestamp
            )
            SELECT
                ?,
                'autoExtendedSpecimenLink',
                ?,
                ?,
                ?,
                ?,
                NOW()
            WHERE NOT EXISTS (
                SELECT 1
                FROM omoccurassociations
                WHERE occid = ?
                    AND occidAssociate = ?
                    AND associationType = 'autoExtendedSpecimenLink'
                    AND relationship = ?
            )
        ";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new RuntimeException('Unable to prepare association insert: ' . $this->conn->error);
        }

        $stmt->bind_param('iisssiis', $occid, $occidAssociate, $relationship, $sourceIdentifier, $notes, $occid, $occidAssociate, $relationship);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException('Unable to create occurrence association: ' . $error);
        }

        $inserted = ($stmt->affected_rows > 0);
        $stmt->close();

        return $inserted;
    }

    protected function groupAlreadyLinked(array $occids, string $relationship): bool {
        $occids = array_values(array_unique(array_map('intval', $occids)));

        if (count($occids) < 2) return true;

        // Links are stored in both directions.
        $expectedLinks = count($occids) * (count($occids) - 1);
        $occidList = implode(',', $occids);

        $sql = "
            SELECT COUNT(*) AS linkCount
            FROM omoccurassociations
            WHERE relationship = ?
                AND associationType = 'autoExtendedSpecimenLink'
                AND occid IN ($occidList)
                AND occidAssociate IN ($occidList)
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $relationship);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return (int)$row['linkCount'] >= $expectedLinks;
    }

    protected function getNeonSampleView(string $sampleUUID): ?array {
        global $NEON_API_KEY;

        if (empty($NEON_API_KEY)) {
            throw new RuntimeException('NEON API key is not configured.');
        }

        if (array_key_exists($sampleUUID, $this->sampleViewCache)) {
            return $this->sampleViewCache[$sampleUUID];
        }

        $url = 'https://data.neonscience.org/api/v0/samples/view?sampleUuid=' . urlencode($sampleUUID);
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['accept: application/json', 'X-API-Token: ' . $NEON_API_KEY],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            $this->log("NEON API error for {$sampleUUID}: {$error}");
            $this->sampleViewCache[$sampleUUID] = null;

            return null;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $this->log("NEON API returned HTTP {$httpCode} for {$sampleUUID}");
            $this->sampleViewCache[$sampleUUID] = null;

            return null;
        }

        $data = json_decode($response, true);
        $sample = $data['data']['sampleViews'][0] ?? null;

        $this->sampleViewCache[$sampleUUID] = $sample;

        return $sample;
    }

    protected function findParentSampleByClass(string $sampleUUID, string $targetSampleClass, array $visited = []): ?array {
        if (isset($visited[$sampleUUID])) return null;

        $visited[$sampleUUID] = true;
        $sample = $this->getNeonSampleView($sampleUUID);

        if (!$sample) return null;
        if (($sample['sampleClass'] ?? '') === $targetSampleClass) return $sample;

        foreach ($sample['parentSampleIdentifiers'] ?? [] as $parent) {
            $parentUUID = $parent['sampleUuid'] ?? null;

            if (!$parentUUID) continue;

            $found = $this->findParentSampleByClass($parentUUID, $targetSampleClass, $visited);

            if ($found) return $found;
        }

        return null;
    }

    protected function findChildSampleByClass(string $sampleUUID, string $targetSampleClass, array $visited = []): ?array {
        if (isset($visited[$sampleUUID])) return null;

        $visited[$sampleUUID] = true;
        $sample = $this->getNeonSampleView($sampleUUID);

        if (!$sample) return null;
        if (($sample['sampleClass'] ?? '') === $targetSampleClass) return $sample;

        foreach ($sample['childSampleIdentifiers'] ?? [] as $child) {
            $childUUID = $child['sampleUuid'] ?? null;

            if (!$childUUID) continue;

            $found = $this->findChildSampleByClass($childUUID, $targetSampleClass, $visited);

            if ($found) return $found;
        }

        return null;
    }

    protected function log(string $message): void {
        if ($this->logger) {
            call_user_func($this->logger, $message);
        }
    }
}