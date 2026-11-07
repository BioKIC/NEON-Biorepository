<?php

require_once(__DIR__ . '/BaseProtocol.php');

class RootsProtocolLinker extends BaseProtocol {

    private const RELATIONSHIP_SAME_CORE = 'sameCore';
    private const RELATIONSHIP_SAME_COLLECTING_EVENT = 'sameCollectingEvent';
    private const RELATIONSHIP_SAME_BOUT = 'sameBout';

    public function run(): void {
        $coreGroups = [];
        $eventGroups = [];
        $boutGroups = [];

        foreach ($this->getOccurrencesWithIdentifier('NEON sampleID') as $record) {
            $sampleID = trim($record['identifierValue']);
            $parts = explode('.', $sampleID, 4);

            // BBC.TREE052163.DATE.*: TREE052 is the plot; the full
            // second component identifies the core within that plot.
            if (count($parts) !== 4 || $parts[0] !== 'BBC'
                || preg_match('/^([A-Z]{4})([0-9]{3}).+$/', $parts[1], $location) !== 1
                || trim($parts[2]) === '' || trim($parts[3]) === '') {
                $this->log('Roots: unable to determine core for occid ' . $record['occid'] . ' sampleID ' . $sampleID);
                continue;
            }

            $occid = (int)$record['occid'];
            $date = $parts[2];
            $coreKey = implode('.', array_slice($parts, 0, 3));
            $eventKey = $location[1] . $location[2] . '.' . $date;

            // Preserve the date verbatim: it is consistent within each group.
            $coreGroups[$coreKey][] = $occid;
            $eventGroups[$eventKey][] = $occid;

            $year = $this->getYear($date);
            if ($year === null) {
                $this->log('Roots: unable to determine bout year for occid ' . $occid . ' sampleID ' . $sampleID);
                continue;
            }

            $boutGroups[$location[1] . '.' . $year][] = $occid;
        }

        $this->linkGroups($coreGroups, self::RELATIONSHIP_SAME_CORE,
            'Root samples derived from the same core.');
        $this->linkGroups($eventGroups, self::RELATIONSHIP_SAME_COLLECTING_EVENT,
            'Root samples collected at the same plot on the same date.');
        $this->linkGroups($boutGroups, self::RELATIONSHIP_SAME_BOUT,
            'Root samples collected at the same site in the same year.');

        $this->log('Finished Roots extended specimen linking');
    }

    private function getYear(string $date): ?string {
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

    private function linkGroups(array $groups, string $relationship, string $notes): void {
        $groupCount = 0;
        $associationCount = 0;

        foreach ($groups as $key => $occids) {
            $occids = array_values(array_unique($occids));

            if (count($occids) < 2) continue;
            if ($this->groupAlreadyLinked($occids, $relationship)) continue;

            $created = $this->linkOccurrenceGroup($occids, $relationship, $key, $notes);

            if ($created > 0) {
                $groupCount++;
                $associationCount += $created;
                $this->log("Roots {$relationship} {$key}: " . count($occids) . " occurrences; {$created} new associations");
            }
        }

        $this->log("Roots {$relationship}: {$groupCount} new linked groups; {$associationCount} new associations");
    }
}
