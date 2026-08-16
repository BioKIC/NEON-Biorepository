<?php

require_once(__DIR__ . '/BaseProtocol.php');

class FishProtocolLinker extends BaseProtocol {

    private const RELATIONSHIP_SAME_INDIVIDUAL = 'sameIndividual';
    private const RELATIONSHIP_SAME_COLLECTING_EVENT = 'sameCollectingEvent';
    private const RELATIONSHIP_SAME_BOUT = 'sameBout';
    private const RELATIONSHIP_SAME_LOCATION = 'sameLocation';

    public function run(): void {
        $this->linkSameIndividual();
        $this->linkSameCollectingEvent();
        $this->linkSameBout();

        $this->log('Finished Fish extended specimen linking');
    }

    private function linkSameIndividual(): void {
        /**
         * Same individual = same base sample ID, ignoring .DNA suffix.
         *
         * FSH.MCRA.02.20181001.1.0012
         * FSH.MCRA.02.20181001.1.0012.DNA
         *
         * Individual key: FSH.MCRA.02.20181001.1.0012
         */
        $this->log('Linking Fish individuals');

        $records = $this->getOccurrencesWithIdentifier('NEON sampleID');
        $groups = [];

        foreach ($records as $record) {
            $sampleID = trim($record['identifierValue']);
            $individualKey = $this->getIndividualKey($sampleID);

            if ($individualKey === null) {
                $this->log('Fish: unable to determine individual for occid ' . $record['occid'] . ' sampleID ' . $sampleID);
                continue;
            }

            $groups[$individualKey][] = (int)$record['occid'];
        }

        $individualCount = 0;
        $associationCount = 0;

        foreach ($groups as $individualKey => $occids) {
            $occids = array_values(array_unique($occids));

            if (count($occids) < 2) continue;
            if ($this->groupAlreadyLinked($occids, self::RELATIONSHIP_SAME_INDIVIDUAL)) continue;

            $individualCount++;

            $created = $this->linkOccurrenceGroup($occids, self::RELATIONSHIP_SAME_INDIVIDUAL, $individualKey, 'Fish specimen and DNA extract derived from the same individual fish.');
            $associationCount += $created;

            $this->log("Fish individual {$individualKey}: " . count($occids) . " occurrences; {$created} new associations");
        }

        $this->log("Fish same individual: {$individualCount} new linked individuals; {$associationCount} new associations");
    }

    private function linkSameCollectingEvent(): void {
        /**
         * Same collecting event = same reach + collecting type + pass.
         *
         * FSH.KING.01.20230418.2.0021
         * FSH.KING.01.20230418.2.0022
         * FSH.KING.01.20230418.2.0023.DNA
         * FSH.KING.01.20230418.2.0001.NONFISH
         *
         * Collecting event key: FSH.KING.01.20230418.2
         */
        $this->log('Linking Fish collecting events');

        $records = $this->getOccurrencesWithIdentifier('NEON sampleID');
        $groups = [];

        foreach ($records as $record) {
            $sampleID = trim($record['identifierValue']);
            $eventKey = $this->getCollectingEventKey($sampleID);

            if ($eventKey === null) {
                $this->log('Fish: unable to determine collecting event for occid ' . $record['occid'] . ' sampleID ' . $sampleID);
                continue;
            }

            $groups[$eventKey][] = (int)$record['occid'];
        }

        $eventCount = 0;
        $associationCount = 0;

        foreach ($groups as $eventKey => $occids) {
            $occids = array_values(array_unique($occids));

            if (count($occids) < 2) continue;
            if ($this->groupAlreadyLinked($occids, self::RELATIONSHIP_SAME_COLLECTING_EVENT)) continue;

            $eventCount++;

            $created = $this->linkOccurrenceGroup($occids, self::RELATIONSHIP_SAME_COLLECTING_EVENT, $eventKey, 'Samples and specimens collected at the same reach using the same collection method and during the same sampling pass.');
            $associationCount += $created;

            $this->log("Fish collecting event {$eventKey}: " . count($occids) . " occurrences; {$created} new associations");
        }

        $this->log("Fish same collecting event: {$eventCount} new linked events; {$associationCount} new associations");
    }

    private function linkSameBout(): void {
        /**
         * Same bout = samples and specimens collected during the same fish sampling bout.
         *
         * Use omoccurrences.eventID when it contains a valid Fish bout event ID.
         * Otherwise, use the NEON sampleUUID to traverse the sample hierarchy and
         * retrieve the event_id from fsh_perFish_in.voucherSampleID.
         *
         * EventID: PRPO.2025.fall
         */
        $this->log('Linking Fish sampling bouts');
    
        $collidList = implode(',', $this->collids);
    
        $sql = "
            SELECT o.occid, o.eventID, i.identifierValue
            FROM omoccurrences o
            INNER JOIN omoccuridentifiers i ON i.occid = o.occid
            WHERE o.collid IN ({$collidList})
                AND i.identifierName = 'NEON sampleUUID'
                AND i.identifierValue IS NOT NULL
                AND i.identifierValue <> ''
        ";
    
        $result = $this->conn->query($sql);
    
        if (!$result) {
            throw new RuntimeException('Unable to retrieve Fish bout records: ' . $this->conn->error);
        }
    
        $groups = [];
    
        while ($record = $result->fetch_assoc()) {
            $eventID = trim($record['eventID'] ?? '');
    
            if (!$this->isValidBoutEventID($eventID)) {
                $sampleUUID = trim($record['identifierValue']);
    
                if ($sampleUUID === '') continue;
    
                $eventID = $this->getBoutEventID($sampleUUID);
    
                if ($eventID === null) {
                    $this->log('Fish: unable to determine bout for occid ' . $record['occid'] . ' sampleUUID ' . $sampleUUID);
                    continue;
                }
            }
    
            $groups[$eventID][] = (int)$record['occid'];
        }
    
        $boutCount = 0;
        $associationCount = 0;
    
        foreach ($groups as $eventID => $occids) {
            $occids = array_values(array_unique($occids));
    
            if (count($occids) < 2) continue;
            if ($this->groupAlreadyLinked($occids, self::RELATIONSHIP_SAME_BOUT)) continue;
    
            $created = $this->linkOccurrenceGroup($occids, self::RELATIONSHIP_SAME_BOUT, $eventID, 'Fish specimens and samples collected during the same sampling bout.');
    
            if ($created > 0) {
                $boutCount++;
                $associationCount += $created;
                $this->log("Fish bout {$eventID}: " . count($occids) . " occurrences; {$created} new associations");
            }
        }
    
        $this->log("Fish same bout: {$boutCount} new linked bouts; {$associationCount} new associations");
    }

    private function getIndividualKey(string $sampleID): ?string {
        $sampleID = trim($sampleID);

        if ($sampleID === '') return null;

        $parts = explode('.', $sampleID);

        if (count($parts) < 6) return null;

        return implode('.', array_slice($parts, 0, 6));
    }

    private function getCollectingEventKey(string $sampleID): ?string {
        $sampleID = trim($sampleID);

        if ($sampleID === '') return null;

        $parts = explode('.', $sampleID);

        if (count($parts) < 6) return null;

        return implode('.', array_slice($parts, 0, 5));
    }

    private function getBoutEventID(string $sampleUUID): ?string {
        $voucher = $this->findParentSampleByClass($sampleUUID, 'fsh_perFish_in.voucherSampleID');

        if (!$voucher) return null;

        foreach ($voucher['sampleEvents'] ?? [] as $event) {
            if (($event['ingestTableName'] ?? '') !== 'fsh_perFish_in') continue;

            foreach ($event['smsFieldEntries'] ?? [] as $entry) {
                if (($entry['smsKey'] ?? '') === 'event_id') {
                    $eventID = trim($entry['smsValue'] ?? '');
                    return $eventID !== '' ? $eventID : null;
                }
            }
        }

        return null;
    }
    
    private function isValidBoutEventID(?string $eventID): bool {
        if (!$eventID) return false;
    
        return preg_match('/^[A-Z0-9]+\.\d{4}\.(spring|fall)$/i', trim($eventID)) === 1;
    }
}