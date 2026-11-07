<?php

require_once(__DIR__ . '/BaseProtocol.php');

class TerrestrialPlantProtocolLinker extends BaseProtocol {

    private const RELATIONSHIP_SAME_INDIVIDUAL = 'sameIndividual';
    private const RELATIONSHIP_SAME_BOUT = 'sameBout';

    public function run(): void {
        $this->linkSameIndividual();
        $this->linkSameBout();

        $this->log('Finished Terrestrial Plant extended specimen linking');
    }

    private function linkSameIndividual(): void {
        /**
         * Same individual = the same taggedPlant parent sampleTag.
         *
         * GEN.OSBS.20210908.09:20
         * Parent sampleClass: taggedPlant
         * Parent sampleTag / individual key: NEON.PLA.D03.OSBS.06046
         *
         * Retrieve the sample view using NEON sampleUUID, then inspect
         * parentSampleIdentifiers directly. Vouchers and leaf samples with
         * the same parent tag are linked, including samples from different years.
         */
        $this->log('Linking Terrestrial Plant individuals');

        $records = $this->getOccurrencesWithIdentifier('NEON sampleUUID');
        $this->log('DEBUG: Terrestrial Plant: ' . count($records) . ' sampleUUID records to check');
        $groups = [];

        foreach ($records as $record) {
            $sampleUUID = trim($record['identifierValue']);
            $lookup = array_key_exists($sampleUUID, $this->sampleViewCache) ? 'cache' : 'API';
            $this->log('DEBUG: Terrestrial Plant: ' . $lookup . ' lookup for occid ' . $record['occid'] . ' sampleUUID ' . $sampleUUID);
            $sample = $this->getNeonSampleView($sampleUUID);

            if ($sample === null) {
                $this->log('ERROR: Terrestrial Plant: unable to determine individual for occid ' . $record['occid'] . ' sampleUUID ' . $sampleUUID);
                continue;
            }

            $parents = $sample['parentSampleIdentifiers'] ?? [];
            $this->log('DEBUG: Terrestrial Plant: occid ' . $record['occid'] . ' sampleTag ' . ($sample['sampleTag'] ?? '<missing>') . '; ' . count($parents) . ' parents');

            // No parent identifiers means there is no individual plant to link.
            if (!$parents) {
                $this->log('DEBUG: Terrestrial Plant: skipping individual for occid ' . $record['occid'] . '; no parents');
                continue;
            }

            foreach ($parents as $parent) {
                $sampleClass = $parent['sampleClass'] ?? '';
                $this->log('DEBUG: Terrestrial Plant: occid ' . $record['occid'] . ' parent sampleClass ' . ($sampleClass !== '' ? $sampleClass : '<missing>') . ' sampleTag ' . ($parent['sampleTag'] ?? '<missing>'));

                if ($sampleClass !== 'taggedPlant') {
                    $this->log('Terrestrial Plant: unexpected parent sampleClass ' . ($sampleClass !== '' ? $sampleClass : '<missing>') . ' for occid ' . $record['occid']);
                    continue;
                }

                $individualKey = trim($parent['sampleTag'] ?? '');
                if ($individualKey === '') {
                    $this->log('ERROR: Terrestrial Plant: missing parent sampleTag for occid ' . $record['occid'] . ' sampleUUID ' . $sampleUUID);
                    continue;
                }

                $groups[$individualKey][] = (int)$record['occid'];
                $this->log('DEBUG: Terrestrial Plant: occid ' . $record['occid'] . ' assigned to individual ' . $individualKey);
            }
        }

        $this->log('DEBUG: Terrestrial Plant: ' . count($groups) . ' individual groups found');
        $individualCount = 0;
        $associationCount = 0;

        foreach ($groups as $individualKey => $occids) {
            $occids = array_values(array_unique($occids));

            $this->log('DEBUG: Terrestrial Plant individual ' . $individualKey . ': occids ' . implode(', ', $occids));

            if (count($occids) < 2) {
                $this->log('DEBUG: Terrestrial Plant individual ' . $individualKey . ': skipped; only one occurrence');
                continue;
            }
            if ($this->groupAlreadyLinked($occids, self::RELATIONSHIP_SAME_INDIVIDUAL)) {
                $this->log('DEBUG: Terrestrial Plant individual ' . $individualKey . ': skipped; already linked');
                continue;
            }

            $created = $this->linkOccurrenceGroup($occids, self::RELATIONSHIP_SAME_INDIVIDUAL, $individualKey,
                'Terrestrial plant vouchers and leaf samples derived from the same tagged individual plant.');

            if ($created > 0) {
                $individualCount++;
                $associationCount += $created;
                $this->log("Terrestrial Plant individual {$individualKey}: " . count($occids) . " occurrences; {$created} new associations");
            }
        }

        $this->log("Terrestrial Plant same individual: {$individualCount} new linked individuals; {$associationCount} new associations");
    }

    private function linkSameBout(): void {
        /**
         * Same bout = all terrestrial plant samples from the same site and year.
         *
         * GEN.OSBS.20210908.09:20
         * PLA.OSBS.20210908.09:31.KVM.V958
         *
         * Bout key: OSBS.2021
         * Site is the second component and date is the third.
         * Parent identifiers are not required for bout linking.
         */
        $this->log('Linking Terrestrial Plant sampling bouts');

        $records = $this->getOccurrencesWithIdentifier('NEON sampleID', 'Originating NEON sampleID', true);
        $this->log('DEBUG: Terrestrial Plant: ' . count($records) . ' sampleID records to check for bouts');
        $groups = [];

        foreach ($records as $record) {
            $sampleID = trim($record['identifierValue'] ?? '');
            $boutKey = $this->getBoutKey($sampleID);

            if ($boutKey === null) {
                $this->log('ERROR: Terrestrial Plant: unable to create sampling bout for occid ' . $record['occid'] . ' sampleID ' . ($sampleID !== '' ? $sampleID : '<missing>'));
                continue;
            }

            $groups[$boutKey][] = (int)$record['occid'];
            $this->log('DEBUG: Terrestrial Plant: occid ' . $record['occid'] . ' sampleID ' . $sampleID . ' assigned to bout ' . $boutKey);
        }

        $this->log('DEBUG: Terrestrial Plant: ' . count($groups) . ' bout groups found');
        $boutCount = 0;
        $associationCount = 0;

        foreach ($groups as $boutKey => $occids) {
            $occids = array_values(array_unique($occids));

            $this->log('DEBUG: Terrestrial Plant bout ' . $boutKey . ': ' . count($occids) . ' occurrences');

            if (count($occids) < 2) {
                $this->log('DEBUG: Terrestrial Plant bout ' . $boutKey . ': skipped; only one occurrence');
                continue;
            }
            if ($this->groupAlreadyLinked($occids, self::RELATIONSHIP_SAME_BOUT)) {
                $this->log('DEBUG: Terrestrial Plant bout ' . $boutKey . ': skipped; already linked');
                continue;
            }

            $created = $this->linkOccurrenceGroup($occids, self::RELATIONSHIP_SAME_BOUT, $boutKey,
                'Terrestrial plant vouchers and leaf samples collected at the same site in the same year.');

            if ($created > 0) {
                $boutCount++;
                $associationCount += $created;
                $this->log("Terrestrial Plant bout {$boutKey}: " . count($occids) . " occurrences; {$created} new associations");
            }
        }

        $this->log("Terrestrial Plant same bout: {$boutCount} new linked bouts; {$associationCount} new associations");
    }

    private function getBoutKey(string $sampleID): ?string {
        $parts = explode('.', $sampleID);

        if (count($parts) < 3 || !in_array($parts[0], ['GEN', 'PLA'], true)
            || preg_match('/^[A-Z]{4}$/', $parts[1]) !== 1) {
            return null;
        }

        $year = $this->getYear($parts[2]);
        if ($year === null) return null;

        return $parts[1] . '.' . $year;
    }
}
