<?php

// Run without a database: php tests/AquaticPlantProtocolLinkerTest.php
require_once(__DIR__ . '/../neon/classes/extendedSpecimenLinks/AquaticPlantProtocol.php');

class AquaticPlantLinkerFixture extends AquaticPlantProtocolLinker {
    private array $records;
    public array $links = [];

    public function __construct(array $records) {
        $this->records = $records;
        $this->logger = null;
    }

    protected function getOccurrencesWithEventID(): array {
        return $this->records;
    }

    protected function groupAlreadyLinked(array $occids, string $relationship): bool {
        foreach ($occids as $a) {
            foreach ($occids as $b) {
                if ($a !== $b && !isset($this->links["{$relationship}:{$a}:{$b}"])) return false;
            }
        }
        return true;
    }

    protected function createAssociation(int $occid, int $occidAssociate, string $relationship, string $sourceIdentifier, string $notes): bool {
        $key = "{$relationship}:{$occid}:{$occidAssociate}";
        if (isset($this->links[$key])) return false;
        $this->links[$key] = $sourceIdentifier;
        return true;
    }
}

function checkAquaticPlant(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

$records = [
    ['occid' => 1, 'eventID' => 'PRIN.20161109', 'identifierValue' => 'PRIN.20161109.PLANT9.Q10'],
    ['occid' => 2, 'eventID' => 'PRIN.20161109', 'identifierValue' => 'PRIN.20161109.QUADRAT.Q10-VOUCHER'],
    ['occid' => 3, 'eventID' => 'PRIN.20161109', 'identifierValue' => 'PRIN.20161109.PLANT6.T4'],
    // Synthetic edge cases: location types and point numbers must not collapse.
    ['occid' => 5, 'eventID' => 'PRIN.20161109', 'identifierValue' => 'PRIN.20161109.TEST.P10'],
    ['occid' => 6, 'eventID' => 'PRIN.20161109', 'identifierValue' => 'PRIN.20161109.TEST.P1'],
    // The database eventID remains authoritative even when the sample date differs.
    ['occid' => 7, 'eventID' => 'PRIN.20161109', 'identifierValue' => 'PRIN.20161108.TEST.Q10'],
    ['occid' => 8, 'eventID' => 'PRIN.20161110', 'identifierValue' => 'PRIN.20161109.TEST.Q10'],
    ['occid' => 9, 'eventID' => null, 'identifierValue' => 'PRIN.20161109.TEST.Q10'],
    ['occid' => 12, 'eventID' => 'GUIL.20210712', 'identifierValue' => 'GUIL.20210712.AP17.1.T4'],
    ['occid' => 13, 'eventID' => 'GUIL.20210712', 'identifierValue' => 'GUIL.20210712.AP1.1.T4']
];
// A repeated identifier row must not create duplicate or self associations.
$records[] = $records[0];

$linker = new AquaticPlantLinkerFixture($records);
$linker->run();
checkAquaticPlant(($linker->links['sameCollectingEvent:1:2'] ?? null) === 'PRIN.20161109.Q10', 'Voucher normalization failed');
checkAquaticPlant(isset($linker->links['sameCollectingEvent:2:1']), 'Reverse association missing');
checkAquaticPlant(isset($linker->links['sameCollectingEvent:1:7']), 'The eventID field must take precedence');
checkAquaticPlant(!isset($linker->links['sameCollectingEvent:1:8']), 'Different eventIDs must remain separate');
checkAquaticPlant(!isset($linker->links['sameBout:1:8']), 'Bouts must use the full eventID, not the year');
checkAquaticPlant(!isset($linker->links['sameCollectingEvent:1:5']), 'Quadrats and points must remain distinct');
checkAquaticPlant(!isset($linker->links['sameCollectingEvent:5:6']), 'P1 and P10 must remain distinct');
checkAquaticPlant(isset($linker->links['sameBout:1:3']), 'Different locations in the same event must share a bout');
checkAquaticPlant(($linker->links['sameCollectingEvent:12:13'] ?? null) === 'GUIL.20210712.T4', 'Dotted taxa must not shift the location field');
foreach (array_keys($linker->links) as $key) {
    [$relationship, $a, $b] = explode(':', $key);
    checkAquaticPlant(in_array($relationship, ['sameCollectingEvent', 'sameBout'], true), 'Unexpected relationship');
    checkAquaticPlant($a !== $b && $a !== '9' && $b !== '9', 'Self link or missing-event link');
}
$count = count($linker->links);
$linker->run();
checkAquaticPlant(count($linker->links) === $count, 'Rerun created duplicate associations');
// Invalid input must raise a general validation error before creating links.
foreach ([null, '', 'invalid-sample-id', 'PRIN.20161109.TEST.INVALID'] as $sampleID) {
    $invalid = new AquaticPlantLinkerFixture([
        $records[0],
        $records[1],
        ['occid' => 999, 'eventID' => 'PRIN.20161109', 'identifierValue' => $sampleID]
    ]);
    $error = null;
    try {
        $invalid->run();
    } catch (RuntimeException $e) {
        $error = $e;
    }
    checkAquaticPlant($error !== null, 'Invalid sampleID did not throw an error');
    checkAquaticPlant(strpos($error->getMessage(), 'occid 999') !== false, 'Error must identify the occurrence');
    checkAquaticPlant(strpos($error->getMessage(), 'sampleID ' . ($sampleID ?? '')) !== false, 'Error must identify the sampleID');
    checkAquaticPlant($invalid->links === [], 'Invalid input must fail before creating links');
}
echo "Aquatic Plant linkage checks passed.\n";
