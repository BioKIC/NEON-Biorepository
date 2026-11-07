<?php

require_once(__DIR__ . '/BaseProtocol.php');

class RootsProtocolLinker extends BaseProtocol {

    private const RELATIONSHIP_SAME_CORE = 'sameCore';
    private const RELATIONSHIP_SAME_COLLECTING_EVENT = 'sameCollectingEvent';
    private const RELATIONSHIP_SAME_BOUT = 'sameBout';

    public function run(): void {
        $this->linkSameIndividual();
        $this->linkSameCollectingEvent();
        $this->linkSameBout();

        $this->log('Finished Roots extended specimen linking');
    }

    private function linkSameIndividual(): void {
        /**
         * Same individual grouping for roots = samples derived from the same core.
         * The stored relationship remains sameCore.
         *
         * BBC.TREE052163.20230418.1
         * BBC.TREE052163.20230418.2
         *
         * Core key: BBC.TREE052163.20230418
         * Keep everything before the variable suffix; preserve the date verbatim.
         */
        $this->log('Linking Roots cores');

        $records = $this->getOccurrencesWithIdentifier('NEON sampleID');
        $groups = [];

        foreach ($records as $record) {
            $sampleID = trim($record['identifierValue']);
            $sample = $this->parseSampleID($sampleID);

            if ($sample === null) {
                $this->log('Roots: unable to determine cores for occid ' . $record['occid'] . ' sampleID ' . $sampleID);
                continue;
            }

            $key = $sample['core'];
            $groups[$key][] = (int)$record['occid'];
        }

        $groupCount = 0;
        $associationCount = 0;

        foreach ($groups as $key => $occids) {
            $occids = array_values(array_unique($occids));

            if (count($occids) < 2) continue;
            if ($this->groupAlreadyLinked($occids, self::RELATIONSHIP_SAME_CORE)) continue;

            $created = $this->linkOccurrenceGroup($occids, self::RELATIONSHIP_SAME_CORE, $key,
                'Root samples derived from the same core.');

            if ($created > 0) {
                $groupCount++;
                $associationCount += $created;
                $this->log("Roots cores {$key}: " . count($occids) . " occurrences; {$created} new associations");
            }
        }

        $this->log("Roots cores: {$groupCount} new linked groups; {$associationCount} new associations");
    }

    private function linkSameCollectingEvent(): void {
        /**
         * Same collecting event = same plot (XXXX###) and date.
         *
         * BBC.TREE052163.20230418.1
         * BBC.TREE052164.20230418.1
         *
         * Collecting event key: TREE052.20230418
         * Different cores may share an event. Preserve the date verbatim.
         */
        $this->log('Linking Roots collecting events');

        $records = $this->getOccurrencesWithIdentifier('NEON sampleID');
        $groups = [];

        foreach ($records as $record) {
            $sampleID = trim($record['identifierValue']);
            $sample = $this->parseSampleID($sampleID);

            if ($sample === null) {
                $this->log('Roots: unable to determine collecting events for occid ' . $record['occid'] . ' sampleID ' . $sampleID);
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
                'Root samples collected at the same plot on the same date.');

            if ($created > 0) {
                $groupCount++;
                $associationCount += $created;
                $this->log("Roots collecting events {$key}: " . count($occids) . " occurrences; {$created} new associations");
            }
        }

        $this->log("Roots collecting events: {$groupCount} new linked groups; {$associationCount} new associations");
    }

    private function linkSameBout(): void {
        /**
         * Same bout = same site (XXXX) and year.
         *
         * BBC.TREE052163.20230418.1
         * BBC.TREE053164.20230920.1
         *
         * Bout key: TREE.2023
         * Only this grouping extracts the year from the date.
         */
        $this->log('Linking Roots sampling bouts');

        $records = $this->getOccurrencesWithIdentifier('NEON sampleID');
        $groups = [];

        foreach ($records as $record) {
            $sampleID = trim($record['identifierValue']);
            $sample = $this->parseSampleID($sampleID);

            if ($sample === null) {
                $this->log('Roots: unable to determine sampling bouts for occid ' . $record['occid'] . ' sampleID ' . $sampleID);
                continue;
            }

            $year = $this->getYear($sample['date']);
            if ($year === null) {
                $this->log('Roots: unable to determine bout year for occid ' . $record['occid'] . ' sampleID ' . $sampleID);
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
                'Root samples collected at the same site in the same year.');

            if ($created > 0) {
                $groupCount++;
                $associationCount += $created;
                $this->log("Roots sampling bouts {$key}: " . count($occids) . " occurrences; {$created} new associations");
            }
        }

        $this->log("Roots sampling bouts: {$groupCount} new linked groups; {$associationCount} new associations");
    }

    private function parseSampleID(string $sampleID): ?array {
        $parts = explode('.', $sampleID, 4);

        // BBC.TREE052163.DATE.*: TREE is the site, TREE052 is the plot,
        // and TREE052163 identifies the core within that plot.
        if (count($parts) !== 4 || $parts[0] !== 'BBC'
            || preg_match('/^([A-Z]{4})([0-9]{3}).+$/', $parts[1], $location) !== 1
            || trim($parts[2]) === '' || trim($parts[3]) === '') {
            return null;
        }

        return [
            'core' => implode('.', array_slice($parts, 0, 3)),
            'plot' => $location[1] . $location[2],
            'site' => $location[1],
            'date' => $parts[2]
        ];
    }
}
