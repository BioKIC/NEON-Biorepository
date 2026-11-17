<?php

class SampleTypeUrls {
    private static $shared;
    private $codes = array();
    private $ids = array();

    public function __construct(array $collections) {
        $groups = array();
        foreach ($collections as $collection) {
            $code = self::code($collection['collectioncode'] ?? '');
            $id = (int) ($collection['collid'] ?? 0);
            if (!$code || !$id) continue;
            $groups[strtolower($code)][$id] = $code;
        }
        foreach ($groups as $key => $matches) {
            // A code shared by different collections cannot identify a unique profile.
            if (count($matches) !== 1) continue;
            $id = key($matches);
            $this->codes[$id] = current($matches);
            $this->ids[$key] = $id;
        }
    }

    public static function code($value): string {
        if (!is_string($value)) return '';
        $value = trim($value);
        if (strlen($value) > 100 || !preg_match('/^(?=.*[a-z])[a-z0-9][a-z0-9._-]*$/iD', $value)
            || strcasecmp($value, 'index.php') === 0) return '';
        return $value;
    }

    public static function shared(): self {
        if (self::$shared) return self::$shared;
        require_once dirname(__DIR__, 2) . '/config/dbconnection.php';
        $connection = MySQLiConnectionFactory::getCon('readonly');
        if (!$connection) throw new RuntimeException('Sample type lookup is unavailable.');
        try {
            $result = $connection->query('SELECT collid, collectioncode FROM omcollections');
            if (!$result) throw new RuntimeException('Sample type lookup failed.');
            $rows = array();
            while ($row = $result->fetch_assoc()) $rows[] = $row;
            $result->free();
            self::$shared = new self($rows);
            return self::$shared;
        } finally {
            $connection->close();
        }
    }

    public function idForCode($code): int {
        return $this->ids[strtolower(self::code($code))] ?? 0;
    }

    public function url($id, $root): string {
        $code = $this->codes[(int) $id] ?? '';
        return rtrim($root, '/') . ($code ? '/sample-types/' . rawurlencode($code)
            : '/collections/misc/neoncollprofiles.php?collid=' . (int) $id);
    }

    public static function profileUrl($id, $root): string {
        try {
            return self::shared()->url($id, $root);
        } catch (Throwable $error) {
            error_log('Sample type URL lookup: ' . $error->getMessage());
            return rtrim($root, '/') . '/collections/misc/neoncollprofiles.php?collid=' . (int) $id;
        }
    }

    public function urls(array $ids, $root): array {
        $urls = array();
        foreach (array_unique(array_map('intval', $ids)) as $id) {
            if ($id > 0) $urls[$id] = $this->url($id, $root);
        }
        return $urls;
    }

    public function allUrls($root): array {
        return $this->urls(array_keys($this->codes), $root);
    }

    public function redirectTarget($id, $root, array $query, $requestUri): string {
        if (!isset($this->codes[(int) $id])) return '';
        unset($query['collid'], $query['sampletype']);
        $target = $this->url($id, $root);
        if ($query) $target .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        return $requestUri === $target ? '' : $target;
    }

    public static function requestedCode($requestUri, $root, array $query): string {
        $path = (string) parse_url($requestUri, PHP_URL_PATH);
        $prefix = rtrim($root, '/') . '/sample-types/';
        if (strpos($path, $prefix) !== 0) return '';
        $part = substr($path, strlen($prefix));
        if ($part === 'index.php') return self::code($query['sampletype'] ?? '');
        // The path takes priority over supplied query IDs or codes.
        return self::code(rawurldecode(rtrim($part, '/')));
    }
}
