<?php

require_once(__DIR__ . '/BaseProtocol.php');

class CanopyProtocolLinker extends BaseProtocol {

    private const RELATIONSHIP_SAME_COLLECTING_EVENT = 'sameCollectingEvent';
    private const RELATIONSHIP_SAME_BOUT = 'sameBout';
    private const RELATIONSHIP_SAME_LOCATION = 'sameLocation';

    public function run(): void {
        // Canopy and herbaceous clips have no individual-level linkage.
        $this->linkSameCollectingEvent();
        $this->linkSameBout();
        $this->linkSameLocation();

        $this->log('Finished Canopy extended specimen linking');
    }

    private function linkSameCollectingEvent(): void {
        /**
         * Same collecting event = all taxa at the same plot and date.
         *
         * CFC.SERC047.QUAL-2.20160725.AR
         * CFC.SERC047.LITU-1.20160725.AR
         * CFC.SERC047.CATO6-3.20160725.AR
         *
         * Collecting event key: SERC047.20160725
         * The taxon component varies; preserve the date verbatim.
         */
        $this->log('Linking Canopy collecting events');

        $records = $this->getOccurrencesWithIdentifier('NEON sampleID');
        $groups = [];

        foreach ($records as $record) {
            $sampleID = trim($record['identifierValue']);
            $sample = $this->parseSampleID($sampleID);

            if ($sample === null) {
                $this->log('Canopy: unable to determine collecting events for occid ' . $record['occid'] . ' sampleID ' . $sampleID);
                continue;
            }

            $key = $sample['plot'] . '.' . $sample['date'];
            $groups[$key][] = (int)$record['occid'];
        }

        $groupCount = 0;
        $associationCount = 0;

        foreach ($groups as $key => $occids) {
            $occids = array_values(array_unique($occids));

            if (count($occids) < 2) continue;
            if ($this->groupAlreadyLinked($occids, self::RELATIONSHIP_SAME_COLLECTING_EVENT)) continue;

            $created = $this->linkOccurrenceGroup($occids, self::RELATIONSHIP_SAME_COLLECTING_EVENT, $key,
                'Canopy and herbaceous clip samples collected at the same plot on the same date, across all taxa.');

            if ($created > 0) {
                $groupCount++;
                $associationCount += $created;
                $this->log("Canopy collecting events {$key}: " . count($occids) . " occurrences; {$created} new associations");
            }
        }

        $this->log("Canopy collecting events: {$groupCount} new linked groups; {$associationCount} new associations");
    }

    private function linkSameBout(): void {
        /**
         * Same bout = all taxa across all plots at the same site and year.
         *
         * CFC.TOOL044.CLIP-1.20170721.AR
         * CFC.TOOL043.CLIP-1.20170720.AR
         *
         * Bout key: TOOL.2017
         * Plot, taxon and collection day may differ.
         */
        $this->log('Linking Canopy sampling bouts');

        $records = $this->getOccurrencesWithIdentifier('NEON sampleID');
        $groups = [];

        foreach ($records as $record) {
            $sampleID = trim($record['identifierValue']);
            $sample = $this->parseSampleID($sampleID);

            if ($sample === null) {
                $this->log('Canopy: unable to determine sampling bouts for occid ' . $record['occid'] . ' sampleID ' . $sampleID);
                continue;
            }

            $year = $this->getYear($sample['date']);
            if ($year === null) {
                $this->log('Canopy: unable to determine bout year for occid ' . $record['occid'] . ' sampleID ' . $sampleID);
                continue;
            }

            $key = $sample['site'] . '.' . $year;
            $groups[$key][] = (int)$record['occid'];
        }

        $groupCount = 0;
        $associationCount = 0;

        foreach ($groups as $key => $occids) {
            $occids = array_values(array_unique($occids));

            if (count($occids) < 2) continue;
            if ($this->groupAlreadyLinked($occids, self::RELATIONSHIP_SAME_BOUT)) continue;

            $created = $this->linkOccurrenceGroup($occids, self::RELATIONSHIP_SAME_BOUT, $key,
                'Canopy and herbaceous clip samples collected at the same site in the same year, across all plots and taxa.');

            if ($created > 0) {
                $groupCount++;
                $associationCount += $created;
                $this->log("Canopy sampling bouts {$key}: " . count($occids) . " occurrences; {$created} new associations");
            }
        }

        $this->log("Canopy sampling bouts: {$groupCount} new linked groups; {$associationCount} new associations");
    }

    private function linkSameLocation(): void {
        /**
         * Same location = the same plotID, across dates and taxa.
         *
         * CFC.SERC047.QUAL-2.20160725.AR
         * CFC.SERC047.CAGL8-1.20210818.AR
         *
         * Plot key: SERC047
         * Links are limited to the collections assigned to the CFC protocol.
         */
        $this->log('Linking Canopy plots');

        $records = $this->getOccurrencesWithIdentifier('NEON sampleID');
        $groups = [];

        foreach ($records as $record) {
            $sampleID = trim($record['identifierValue']);
            $sample = $this->parseSampleID($sampleID);

            if ($sample === null) {
                $this->log('Canopy: unable to determine plots for occid ' . $record['occid'] . ' sampleID ' . $sampleID);
                continue;
            }

            $key = $sample['plot'];
            $groups[$key][] = (int)$record['occid'];
        }

        $groupCount = 0;
        $associationCount = 0;

        foreach ($groups as $key => $occids) {
            $occids = array_values(array_unique($occids));

            if (count($occids) < 2) continue;
            if ($this->groupAlreadyLinked($occids, self::RELATIONSHIP_SAME_LOCATION)) continue;

            $created = $this->linkOccurrenceGroup($occids, self::RELATIONSHIP_SAME_LOCATION, $key,
                'Canopy and herbaceous clip samples collected at the same plot, across all dates and taxa.');

            if ($created > 0) {
                $groupCount++;
                $associationCount += $created;
                $this->log("Canopy plots {$key}: " . count($occids) . " occurrences; {$created} new associations");
            }
        }

        $this->log("Canopy plots: {$groupCount} new linked groups; {$associationCount} new associations");
    }

    private function parseSampleID(string $sampleID): ?array {
        $parts = explode('.', $sampleID);

        // CFC.SRER011.PAFL6-1.20250910.AR
        // Site: SRER; plot: SRER011; taxon component: PAFL6-1;
        // date: 20250910; AR: archive.
        // CLIP-1 and other herbaceous clip components are also accepted.
        if (count($parts) !== 5 || $parts[0] !== 'CFC'
            || preg_match('/^([A-Z]{4})[0-9]{3}$/', $parts[1], $location) !== 1
            || trim($parts[2]) === '' || trim($parts[3]) === ''
            || $parts[4] !== 'AR') {
            return null;
        }

        return [
            'plot' => $parts[1],
            'site' => $location[1],
            'date' => $parts[3]
        ];
    }
}
