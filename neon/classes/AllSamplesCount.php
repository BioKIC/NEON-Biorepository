<?php
require_once __DIR__ . '/SearchParameterNames.php';
require_once __DIR__ . '/../../classes/OccurrenceSearchSupport.php';
require_once __DIR__ . '/../../classes/utilities/OccurrenceUtil.php';

class AllSamplesCount {
    public static function scope($value): string {
        if (!is_string($value)) throw new InvalidArgumentException('Invalid sample selection.');
        $parts = array_unique(array_map('trim', explode(',', strtolower($value))));
        $parts = array_map(function ($part) { return $part === 'all-other' ? 'all-others' : $part; }, $parts);
        if (!in_array('all', $parts, true) || array_diff($parts, array('all', 'all-others'))) {
            throw new InvalidArgumentException('An all-samples selection is required.');
        }
        return in_array('all-others', $parts, true) ? 'all,all-others' : 'all';
    }

    public static function query($connection, $scope): string {
        $scope = self::scope($scope);
        $path = $GLOBALS['SERVER_ROOT'] . '/neon-react/biorepo_lib/collections-taxonomic.json';
        if (!is_readable($path) || !json_decode(file_get_contents($path), true)) {
            throw new RuntimeException('The sample type tree is unavailable.');
        }
        $names = new SearchParameterNames($connection);
        $resolved = $names->resolve('db', $scope);
        if (!preg_match('/^all(,[0-9]+)*$/D', $resolved)) throw new RuntimeException('The sample selection could not be resolved.');
        $where = OccurrenceSearchSupport::getDbWhereFrag(array('db' => $resolved));
        if (!$where) throw new RuntimeException('The sample selection is empty.');
        // Match list.php, including the current user's access to fully protected records.
        // Count occurrences once rather than summing overlapping material sample cards.
        return 'SELECT COUNT(*) AS count FROM omoccurrences o WHERE 1=1 ' . $where . OccurrenceUtil::appendFullProtectionSQL();
    }
}
