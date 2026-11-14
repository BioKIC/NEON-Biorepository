<?php

class DatasetCitations {
    public static function fromStoredText($html) {
        // Request citations are saved with blank lines or paired <br> tags.
        // Preserve their original wording and access dates when adding exports.
        $html = preg_replace('/<br\s*\/?>/i', "\n", (string) $html);
        $html = preg_replace('/<\/(?:p|div|li)\s*>/i', "\n\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $parts = preg_split('/\R\s*\R/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $entries = array();

        foreach ($parts as $part) {
            $text = trim(preg_replace('/\s+/u', ' ', $part));
            if ($text === '') continue;
            $entry = array('text' => $text, 'key' => 'NEON-citation-' . (count($entries) + 1));
            $pattern = '~^(?<author>NEON \(National Ecological Observatory Network\) Biorepository)\.\s+'
                . '(?<title>.+?)\.\s+(?<note>Data accessed from (?<url>https?://[^\s]+[?&]collid=(?<id>\d+))'
                . ' on (?<date>\d{4}-\d{2}-\d{2})\.\s+Licensed under (?<license>.+?) '
                . '\((?<licenseUrl>https?://[^)]+)\)\.\s*(?<bibNote>.*))$~u';
            if (preg_match($pattern, $text, $match)) {
                $entry = array_merge($entry, array(
                    'key' => 'NEON' . $match['id'],
                    'author' => $match['author'],
                    'title' => $match['title'],
                    'url' => $match['url'],
                    'accessDate' => $match['date'],
                    'license' => $match['license'],
                    'licenseUrl' => $match['licenseUrl'],
                    'note' => $match['note'],
                    'bibNote' => $match['bibNote']
                ));
            }
            $entries[] = $entry;
        }
        return $entries;
    }

    private static function escapeBibtex($value) {
        return strtr($value, array(
            '\\' => '\\textbackslash{}', '{' => '\\{', '}' => '\\}',
            '&' => '\\&', '%' => '\\%', '$' => '\\$', '#' => '\\#',
            '_' => '\\_', '~' => '\\textasciitilde{}', '^' => '\\textasciicircum{}'
        ));
    }

    public static function download($entry, $format) {
        $filename = isset($entry['title'])
            ? 'NEON-' . preg_replace('/[^a-zA-Z0-9]+/', '-', $entry['title']) . '-' . $entry['accessDate']
            : $entry['key'];
        if ($format === 'bib') {
            // Use note-only metadata for custom citations instead of inventing fields.
            $fields = array('note' => $entry['text']);
            if (isset($entry['title'])) {
                $fields = array(
                    'doi' => '', 'url' => $entry['url'], 'author' => $entry['author'],
                    'language' => 'en', 'title' => $entry['title'],
                    'publisher' => 'National Ecological Observatory Network (NEON)',
                    'note' => $entry['bibNote'], 'urldate' => $entry['accessDate'],
                    'copyright' => $entry['license'] . ' (' . $entry['licenseUrl'] . ')'
                );
            }
            $lines = array();
            foreach ($fields as $field => $value) {
                $value = self::escapeBibtex($value);
                if ($field === 'title' || $field === 'author') $value = '{' . $value . '}';
                $lines[] = '    ' . $field . ' = {' . $value . '}';
            }
            $content = '@misc{' . $entry['key'] . "/provisional,\n" . implode(",\n", $lines) . "\n}";
            $mime = 'application/x-bibtex';
        } else {
            $lines = array('TY  - GEN', 'N1  - ' . $entry['text']);
            if (isset($entry['title'])) {
                $lines = array(
                    'TY  - DATA', 'T1  - ' . $entry['title'], 'AU  - ' . $entry['author'],
                    'DO  -', 'UR  - ' . $entry['url'],
                    'PB  - NEON (National Ecological Observatory Network) Biorepository',
                    'Y2  - ' . $entry['accessDate'], 'LA  - en', 'N1  - ' . $entry['note']
                );
            }
            $content = implode("\r\n", array_merge($lines, array('ER  -', '')));
            $mime = 'application/x-research-info-systems';
        }
        return array(
            'filename' => $filename . '.' . $format,
            'href' => 'data:' . $mime . ';charset=utf-8,' . rawurlencode($content)
        );
    }
}
