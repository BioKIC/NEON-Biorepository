<?php

class DatasetCitations {
    public static function fromCollections($collections) {
        $entries = array();
        foreach ($collections as $collData) {
            $id = (int)$collData['collid'];
            if ($id < 1 || isset($entries[$id])) continue;
            // The profile page uses this same template for its text and export metadata.
            // Include it for each collection so wording, licensing and dates cannot drift.
            ob_start();
            try {
                include __DIR__ . '/../../includes/citationneoncollection.php';
            } finally {
                ob_end_clean();
            }
            $entries[$id] = array_merge($neonCitation, array(
                'text' => $neonCitationText,
                'description' => html_entity_decode(strip_tags($collData['fulldescription'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')
            ));
        }
        return array_values($entries);
    }

    private static function escapeBibtex($value) {
        return strtr($value, array(
            '\\' => '\\textbackslash{}', '{' => '\\{', '}' => '\\}',
            '&' => '\\&', '%' => '\\%', '$' => '\\$', '#' => '\\#',
            '_' => '\\_', '~' => '\\textasciitilde{}', '^' => '\\textasciicircum{}'
        ));
    }

    public static function download($entry, $format) {
        $filename = 'NEON-' . preg_replace('/[^a-zA-Z0-9]+/', '-', $entry['title']) . '-' . $entry['accessDate'];
        if ($format === 'bib') {
            $key = preg_replace('/[^a-zA-Z0-9._-]/', '', $entry['collectionCode']) ?: 'NEON' . $entry['collectionId'];
            $fields = array(
                'doi' => '', 'url' => $entry['url'], 'author' => $entry['author'],
                'language' => 'en', 'title' => $entry['title'],
                'publisher' => 'National Ecological Observatory Network (NEON)',
                'note' => $entry['bibNote'] ?? $entry['note'], 'urldate' => $entry['accessDate'],
                'copyright' => $entry['license'] . ' (' . $entry['licenseUrl'] . ')'
            );
            $lines = array();
            foreach ($fields as $field => $value) {
                $value = self::escapeBibtex($value);
                if ($field === 'title' || $field === 'author') $value = '{' . $value . '}';
                $lines[] = '    ' . $field . ' = {' . $value . '}';
            }
            $content = '@misc{' . $key . "/provisional,\n" . implode(",\n", $lines) . "\n}";
            $mime = 'application/x-bibtex';
        } else {
            $lines = array(
                'TY  - DATA', 'T1  - ' . $entry['title'], 'AU  - ' . $entry['author'],
                'DO  -', 'UR  - ' . $entry['url'],
                'PB  - NEON (National Ecological Observatory Network) Biorepository',
                'Y2  - ' . $entry['accessDate'], 'LA  - en', 'N1  - ' . $entry['note']
            );
            if (!empty($entry['description'])) $lines[] = 'AB  - ' . $entry['description'];
            $lines = array_map(function ($line) { return preg_replace('/[\r\n]+/', ' ', $line); }, $lines);
            $content = implode("\r\n", array_merge($lines, array('ER  -', '')));
            $mime = 'application/x-research-info-systems';
        }
        return array(
            'filename' => $filename . '.' . $format,
            'href' => 'data:' . $mime . ';charset=utf-8,' . rawurlencode($content)
        );
    }
}
