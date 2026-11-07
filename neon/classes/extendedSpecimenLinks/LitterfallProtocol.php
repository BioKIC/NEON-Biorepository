<?php

require_once(__DIR__ . '/BaseProtocol.php');

class LitterfallProtocolLinker extends BaseProtocol {

    private const RELATIONSHIP_SAME_COLLECTING_EVENT = 'sameCollectingEvent';
    private const RELATIONSHIP_SAME_BOUT = 'sameBout';

    public function run(): void {
        // Litterfall has no individual-level linkage.
        $this->linkSameCollectingEvent();
        $this->linkSameBout();

        $this->log('Finished Litterfall extended specimen linking');
    }

    private function linkSameCollectingEvent(): void {
        /**
         * Same collecting event = leaves and needles at the same plot and date.
         *
         * NEON.LTR.TALL060.20171113.LVS.AR
         * NEON.LTR.TALL060.20171113.NDL.AR
         *
         * Collecting event key: NEON.LTR.TALL060.20171113
         * Preserve the date verbatim. Only selected bouts are archived, so
         * leaves and needles from a plot may have different collection dates.
         * Other functional groups, such as NEON.LTR.ABBY.2022.TWI.AR, are
         * pooled across the site and year and receive only bout links.
         */
        $this->log('Linking Litterfall collecting events');

        $records = $this->getOccurrencesWithIdentifier('NEON sampleID', 'Originating NEON sampleID', true);
        $groups = [];

        foreach ($records as $record) {
            $sampleID = trim($record['identifierValue'] ?? '');
            $sample = $this->parseSampleID($sampleID);

            if ($sample === null) {
                $this->log('ERROR: Litterfall: unable to create collecting event for occid ' . $record['occid'] . ' sampleID ' . ($sampleID !== '' ? $sampleID : '<missing>'));
                continue;
            }

            if ($sample['plot'] === null) continue;

            $key = 'NEON.LTR.' . $sample['plot'] . '.' . $sample['date'];
            $groups[$key][] = (int)$record['occid'];
        }

        $groupCount = 0;
        $associationCount = 0;

        foreach ($groups as $key => $occids) {
            $occids = array_values(array_unique($occids));

            if (count($occids) < 2) continue;
            if ($this->groupAlreadyLinked($occids, self::RELATIONSHIP_SAME_COLLECTING_EVENT)) continue;

            $created = $this->linkOccurrenceGroup($occids, self::RELATIONSHIP_SAME_COLLECTING_EVENT, $key,
                'Litterfall leaves and needles collected at the same plot on the same date.');

            if ($created > 0) {
                $groupCount++;
                $associationCount += $created;
                $this->log("Litterfall collecting events {$key}: " . count($occids) . " occurrences; {$created} new associations");
            }
        }

        $this->log("Litterfall collecting events: {$groupCount} new linked groups; {$associationCount} new associations");
    }

    private function linkSameBout(): void {
        /**
         * Same bout = all functional groups at the same site and year.
         *
         * NEON.LTR.ABBY061.20221006.NDL.AR
         * NEON.LTR.ABBY061.20221201.LVS.AR
         * NEON.LTR.ABBY.2022.TWI.AR
         *
         * Bout key: LTR.2022.ABBY
         * Use the LTR.YEAR.SITE prefix of eventID, ignoring its suffix.
         * If eventID is missing, derive the same key from sampleID: use the
         * year of the leaves/needles date or the pooled sample's year.
         */
        $this->log('Linking Litterfall sampling bouts');

        $records = $this->getOccurrencesWithIdentifier('NEON sampleID', 'Originating NEON sampleID', true);
        $groups = [];

        foreach ($records as $record) {
            $sampleID = trim($record['identifierValue'] ?? '');
            $key = $this->getBoutKey($record['eventID'] ?? null, $sampleID);

            if ($key === null) {
                $this->log('ERROR: Litterfall: unable to create sampling bout for occid ' . $record['occid'] . ' sampleID ' . ($sampleID !== '' ? $sampleID : '<missing>'));
                continue;
            }

            $groups[$key][] = (int)$record['occid'];
        }

        $groupCount = 0;
        $associationCount = 0;

        foreach ($groups as $key => $occids) {
            $occids = array_values(array_unique($occids));

            if (count($occids) < 2) continue;
            if ($this->groupAlreadyLinked($occids, self::RELATIONSHIP_SAME_BOUT)) continue;

            $created = $this->linkOccurrenceGroup($occids, self::RELATIONSHIP_SAME_BOUT, $key,
                'Litterfall samples collected at the same site in the same year, across all plots and functional groups.');

            if ($created > 0) {
                $groupCount++;
                $associationCount += $created;
                $this->log("Litterfall sampling bouts {$key}: " . count($occids) . " occurrences; {$created} new associations");
            }
        }

        $this->log("Litterfall sampling bouts: {$groupCount} new linked groups; {$associationCount} new associations");
    }

    private function getBoutKey(?string $eventID, string $sampleID): ?string {
        $eventID = trim($eventID ?? '');

        if ($eventID !== '') {
            if (preg_match('/^LTR\.([0-9]{4})\.([A-Z]{4})(?:\..+)?$/', $eventID, $matches) !== 1) {
                return null;
            }

            return 'LTR.' . $matches[1] . '.' . $matches[2];
        }

        $sample = $this->parseSampleID($sampleID);
        if ($sample === null) return null;

        $year = $this->getYear($sample['date']);
        if ($year === null) return null;

        return 'LTR.' . $year . '.' . $sample['site'];
    }

    private function parseSampleID(string $sampleID): ?array {
        $parts = explode('.', $sampleID);

        if (count($parts) !== 6 || $parts[0] !== 'NEON' || $parts[1] !== 'LTR'
            || trim($parts[3]) === '' || trim($parts[4]) === '' || $parts[5] !== 'AR') {
            return null;
        }

        $plot = null;

        if (in_array($parts[4], ['LVS', 'NDL'], true)) {
            // NEON.LTR.ABBY061.20221006.NDL.AR: plot and collection date.
            if (preg_match('/^([A-Z]{4})[0-9]{3}$/', $parts[2], $location) !== 1) return null;
            $site = $location[1];
            $plot = $parts[2];
        } else {
            // NEON.LTR.ABBY.2022.TWI.AR: other groups pooled by site and year.
            if (preg_match('/^[A-Z]{4}$/', $parts[2]) !== 1
                || preg_match('/^[0-9]{4}$/', $parts[3]) !== 1) return null;
            $site = $parts[2];
        }

        return [
            'plot' => $plot,
            'site' => $site,
            'date' => $parts[3]
        ];
    }
}
