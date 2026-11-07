<?php

require_once(__DIR__ . '/BaseProtocol.php');

class TickProtocolLinker extends BaseProtocol {

    private const RELATIONSHIP_SAME_INDIVIDUAL = 'sameIndividual';
    private const RELATIONSHIP_SAME_COLLECTING_EVENT = 'sameCollectingEvent';
    private const RELATIONSHIP_SAME_BOUT = 'sameBout';

    public function run(): void {
        $this->linkSameIndividual();
        $this->linkSameCollectingEvent();
        $this->linkSameBout();

        $this->log('Finished Tick extended specimen linking');
    }

    private function linkSameIndividual(): void {
        /**
         * Same individual = nymph samples and extracts sharing the prefix
         * through the N component, ignoring everything after it.
         *
         * HARV_022.20170622.IXOSCA.N.T.003
         * Individual key: HARV_022.20170622.IXOSCA.N
         *
         * Matching pattern: TREE_007.20240917.IXOSCA.N*
         * Other life stages receive collecting-event and bout links only.
         */
        $this->log('Linking Tick individuals');

        $records = $this->getOccurrencesWithIdentifier('NEON sampleID', 'Originating NEON sampleID', true);
        $groups = [];

        foreach ($records as $record) {
            $sampleID = trim($record['identifierValue'] ?? '');
            $sample = $this->parseSampleID($sampleID);

            if ($sample === null) {
                $this->log('ERROR: Tick: unable to determine individual for occid ' . $record['occid'] . ' sampleID ' . ($sampleID !== '' ? $sampleID : '<missing>'));
                continue;
            }

            if ($sample['stage'] !== 'N') continue;

            $key = $sample['plot'] . '.' . $sample['date'] . '.' . $sample['taxon'] . '.N';
            $groups[$key][] = (int)$record['occid'];
        }

        $individualCount = 0;
        $associationCount = 0;

        foreach ($groups as $key => $occids) {
            $occids = array_values(array_unique($occids));

            if (count($occids) < 2) continue;
            if ($this->groupAlreadyLinked($occids, self::RELATIONSHIP_SAME_INDIVIDUAL)) continue;

            $created = $this->linkOccurrenceGroup($occids, self::RELATIONSHIP_SAME_INDIVIDUAL, $key,
                'Tick nymph samples and extracts sharing the same plot, collection date, taxon and nymph sample prefix.');

            if ($created > 0) {
                $individualCount++;
                $associationCount += $created;
                $this->log("Tick individual {$key}: " . count($occids) . " occurrences; {$created} new associations");
            }
        }

        $this->log("Tick same individual: {$individualCount} new linked individuals; {$associationCount} new associations");
    }

    private function linkSameCollectingEvent(): void {
        /**
         * Same collecting event = all samples from the same plot and drag date.
         *
         * HARV_022.20170622.IXOSCA.N.T.003
         * Collecting event key: HARV_022.20170622
         *
         * Matching pattern: TREE_007.20240917.*
         * Taxon, life stage and trailing sample components may differ.
         * Preserve the plot and date verbatim.
         */
        $this->log('Linking Tick collecting events');

        $records = $this->getOccurrencesWithIdentifier('NEON sampleID', 'Originating NEON sampleID', true);
        $groups = [];

        foreach ($records as $record) {
            $sampleID = trim($record['identifierValue'] ?? '');
            $sample = $this->parseSampleID($sampleID);

            if ($sample === null) {
                $this->log('ERROR: Tick: unable to create collecting event for occid ' . $record['occid'] . ' sampleID ' . ($sampleID !== '' ? $sampleID : '<missing>'));
                continue;
            }

            $key = $sample['plot'] . '.' . $sample['date'];
            $groups[$key][] = (int)$record['occid'];
        }

        $eventCount = 0;
        $associationCount = 0;

        foreach ($groups as $key => $occids) {
            $occids = array_values(array_unique($occids));

            if (count($occids) < 2) continue;
            if ($this->groupAlreadyLinked($occids, self::RELATIONSHIP_SAME_COLLECTING_EVENT)) continue;

            $created = $this->linkOccurrenceGroup($occids, self::RELATIONSHIP_SAME_COLLECTING_EVENT, $key,
                'Tick specimens and extracts collected during the same drag event at the same plot and date.');

            if ($created > 0) {
                $eventCount++;
                $associationCount += $created;
                $this->log("Tick collecting event {$key}: " . count($occids) . " occurrences; {$created} new associations");
            }
        }

        $this->log("Tick collecting events: {$eventCount} new linked events; {$associationCount} new associations");
    }

    private function linkSameBout(): void {
        /**
         * Same bout = all tick samples with the same omoccurrences.eventID,
         * including samples from different plots during that bout.
         *
         * EventID / bout key: ORNL.2024.11
         *
         * Retain the full eventID, including the bout number. It cannot be
         * derived from sampleID; records without it cannot receive bout links.
         */
        $this->log('Linking Tick sampling bouts');

        $records = $this->getOccurrencesWithIdentifier('NEON sampleID', 'Originating NEON sampleID', true);
        $groups = [];

        foreach ($records as $record) {
            $eventID = trim($record['eventID'] ?? '');

            if (preg_match('/^[A-Z]{4}\.[0-9]{4}\.[0-9]+$/', $eventID) !== 1) {
                $sampleID = trim($record['identifierValue'] ?? '');
                $this->log('ERROR: Tick: unable to create sampling bout for occid ' . $record['occid'] . ' sampleID ' . ($sampleID !== '' ? $sampleID : '<missing>'));
                continue;
            }

            $groups[$eventID][] = (int)$record['occid'];
        }

        $boutCount = 0;
        $associationCount = 0;

        foreach ($groups as $eventID => $occids) {
            $occids = array_values(array_unique($occids));

            if (count($occids) < 2) continue;
            if ($this->groupAlreadyLinked($occids, self::RELATIONSHIP_SAME_BOUT)) continue;

            $created = $this->linkOccurrenceGroup($occids, self::RELATIONSHIP_SAME_BOUT, $eventID,
                'Tick specimens and extracts collected during the same sampling bout, across all plots.');

            if ($created > 0) {
                $boutCount++;
                $associationCount += $created;
                $this->log("Tick sampling bout {$eventID}: " . count($occids) . " occurrences; {$created} new associations");
            }
        }

        $this->log("Tick sampling bouts: {$boutCount} new linked bouts; {$associationCount} new associations");
    }

    private function parseSampleID(string $sampleID): ?array {
        // HARV_022.20170622.IXOSCA.N.T.003
        // SITE_PLOT.DATE.TAXON.STAGE, followed by any sample-specific suffix.
        $parts = explode('.', $sampleID, 5);

        if (count($parts) < 4 || preg_match('/^[A-Z]{4}_[0-9]{3}$/', $parts[0]) !== 1
            || trim($parts[1]) === '' || trim($parts[2]) === '' || trim($parts[3]) === '') {
            return null;
        }

        return [
            'plot' => $parts[0],
            'date' => $parts[1],
            'taxon' => $parts[2],
            'stage' => $parts[3]
        ];
    }
}
