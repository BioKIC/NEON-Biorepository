<?php

require_once(__DIR__ . '/BaseProtocol.php');

class AquaticPlantProtocolLinker extends BaseProtocol {

    private const RELATIONSHIP_SAME_COLLECTING_EVENT = 'sameCollectingEvent';
    private const RELATIONSHIP_SAME_BOUT = 'sameBout';

    public function run(): void {
        // APL has no individual or standalone location linkages.
        $this->linkSameCollectingEvent();
        $this->linkSameBout();

        $this->log('Finished Aquatic Plant extended specimen linking');
    }

    private function linkSameCollectingEvent(): void {
        /**
         * Same collecting event = the same eventID and sampling location code.
         * Taxa may differ. Keep P (point), Q (quadrat) and T (transect) distinct.
         *
         * TOOK.20180730.BRYOPHYTE1.P10
         * TOOK.20180730.AQPLANT2.P10
         * Collecting event key: TOOK.20180730.P10
         *
         * PRIN.20161109.PLANT9.Q10
         * PRIN.20161109.QUADRAT.Q10-VOUCHER
         * Collecting event key: PRIN.20161109.Q10
         *
         * Prefer omoccurrences.eventID; if empty, use the SITE.date prefix
         * from NEON sampleID (falling back to Originating NEON sampleID).
         * Ignore -VOUCHER when extracting the location.
         */
        $this->log('Linking Aquatic Plant collecting events');

        $records = $this->getOccurrencesWithIdentifier('NEON sampleID', 'Originating NEON sampleID', true);
        $groups = [];

        foreach ($records as $record) {
            $sampleID = trim($record['identifierValue'] ?? '');
            $eventID = $this->getEventID($record['eventID'] ?? null, $sampleID);

            if ($eventID === null) {
                $this->log('ERROR: Aquatic Plant: unable to create collecting event for occid ' . $record['occid'] . ' sampleID ' . ($sampleID !== '' ? $sampleID : '<missing>'));
                continue;
            }

            $point = $this->getSamplingPointKey($sampleID);

            if ($point === null) {
                $this->log('ERROR: Aquatic Plant: unable to create collecting event for occid ' . $record['occid'] . ' sampleID ' . ($sampleID !== '' ? $sampleID : '<missing>'));
                continue;
            }

            $key = $eventID . '.' . $point;
            $groups[$key][] = (int)$record['occid'];
        }

        $groupCount = 0;
        $associationCount = 0;

        foreach ($groups as $key => $occids) {
            $occids = array_values(array_unique($occids));

            if (count($occids) < 2) continue;
            if ($this->groupAlreadyLinked($occids, self::RELATIONSHIP_SAME_COLLECTING_EVENT)) continue;

            $created = $this->linkOccurrenceGroup($occids, self::RELATIONSHIP_SAME_COLLECTING_EVENT, $key,
                'Aquatic plant, bryophyte, lichen and macroalgae samples collected at the same point, quadrat or transect during the same event.');

            if ($created > 0) {
                $groupCount++;
                $associationCount += $created;
                $this->log("Aquatic Plant collecting events {$key}: " . count($occids) . " occurrences; {$created} new associations");
            }
        }

        $this->log("Aquatic Plant collecting events: {$groupCount} new linked groups; {$associationCount} new associations");
    }

    private function linkSameBout(): void {
        /**
         * Same bout = the same eventID (SITE.date).
         *
         * COMO.20190807.SCRI4.T3
         * COMO.20190807.SCUN6.T10
         * Bout key: COMO.20190807
         *
         * Prefer the eventID field. If empty, use the SITE.date prefix
         * from NEON sampleID (falling back to Originating NEON sampleID),
         * preserving the full date.
         */
        $this->log('Linking Aquatic Plant sampling bouts');

        $records = $this->getOccurrencesWithIdentifier('NEON sampleID', 'Originating NEON sampleID', true);
        $groups = [];

        foreach ($records as $record) {
            $sampleID = trim($record['identifierValue'] ?? '');
            $eventID = $this->getEventID($record['eventID'] ?? null, $sampleID);

            if ($eventID === null) {
                $this->log('ERROR: Aquatic Plant: unable to create sampling bout for occid ' . $record['occid'] . ' sampleID ' . ($sampleID !== '' ? $sampleID : '<missing>'));
                continue;
            }

            $key = $eventID;
            $groups[$key][] = (int)$record['occid'];
        }

        $groupCount = 0;
        $associationCount = 0;

        foreach ($groups as $key => $occids) {
            $occids = array_values(array_unique($occids));

            if (count($occids) < 2) continue;
            if ($this->groupAlreadyLinked($occids, self::RELATIONSHIP_SAME_BOUT)) continue;

            $created = $this->linkOccurrenceGroup($occids, self::RELATIONSHIP_SAME_BOUT, $key,
                'Aquatic plant, bryophyte, lichen and macroalgae samples collected during the same sampling bout, across all points and taxa.');

            if ($created > 0) {
                $groupCount++;
                $associationCount += $created;
                $this->log("Aquatic Plant sampling bouts {$key}: " . count($occids) . " occurrences; {$created} new associations");
            }
        }

        $this->log("Aquatic Plant sampling bouts: {$groupCount} new linked groups; {$associationCount} new associations");
    }

    private function getEventID(?string $eventID, string $sampleID): ?string {
        $eventID = trim($eventID ?? '');
        if ($eventID !== '') return $eventID;

        // COMO.20190807.SCRI4.T3 -> COMO.20190807
        // Only derive an eventID from the expected SITE.date.taxon.location structure.
        $sampleID = trim($sampleID);
        if ($this->getSamplingPointKey($sampleID) === null) return null;

        $parts = explode('.', $sampleID);
        return $parts[0] . '.' . $parts[1];
    }

    private function getSamplingPointKey(string $sampleID): ?string {
        $parts = explode('.', $sampleID);

        // Expected structure: SITE.date.taxon.location, e.g. COMO.20190807.SCRI4.T3.
        if (count($parts) !== 4 || preg_match('/^[A-Z]{4}$/i', $parts[0]) !== 1
            || trim($parts[1]) === '' || trim($parts[2]) === '') {
            return null;
        }

        $point = trim($parts[3]);

        // Q10 and Q10-VOUCHER share a key, but P1 and P10 do not.
        if (preg_match('/^([PQT][0-9]+)(?:-VOUCHER)?$/i', $point, $matches) !== 1) {
            return null;
        }

        return strtoupper($matches[1]);
    }
}
