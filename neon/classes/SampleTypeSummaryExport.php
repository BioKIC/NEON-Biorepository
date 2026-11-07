<?php

/** Combines the PR #1223 summary with public catalog facets and availability. */
class SampleTypeSummaryExport {
    public static function read($result, array $catalog): array {
        $items = array();
        foreach ($catalog['items'] as $item) $items[(int)$item['id']] = $item;
        $columns = array_slice(array_map(function ($field) { return $field->name; }, $result->fetch_fields()), 1);
        $columns = array_map(function ($column) {
            return array('taxon' => 'taxonomic_groups', 'years' => 'collection_months')[$column] ?? $column;
        }, $columns);
        $columns = array_values(array_unique(array_merge($columns, array(
            'collection_code', 'collection_id', 'preservation', 'sites', 'taxonomic_groups', 'collection_months', 'description'
        ))));
        // Keep the summary's existing order, with collection identity immediately after its name.
        $columns = array_values(array_diff($columns, array('collection_code', 'collection_id', 'preservation', 'sites', 'description')));
        array_splice($columns, 1, 0, array('collection_code', 'collection_id'));
        foreach (array('preservation' => 'storageconditions', 'sites' => 'numberofsites', 'description' => 'descriptionurl') as $column => $anchor) {
            $position = count($columns);
            foreach ($columns as $index => $name) {
                if (strtolower(preg_replace('/[^a-z0-9]/i', '', $name)) === $anchor) {
                    $position = $index;
                    break;
                }
            }
            array_splice($columns, $position, 0, array($column));
        }
        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $id = (int)$row['collid'];
            $item = $items[$id] ?? array();
            $sites = array();
            $months = array();
            foreach ($item['siteCodes'] ?? array() as $site) {
                $sites[] = $site['siteCode'];
                $months = array_merge($months, $site['availableMonths']);
            }
            $months = array_values(array_unique($months));
            sort($months);
            $row['collection_code'] = $item['collectionCode'] ?? '';
            $row['collection_id'] = $id;
            $row['preservation'] = implode('; ', $item['preservation'] ?? array());
            $row['sites'] = implode('; ', array_unique($sites));
            $row['taxonomic_groups'] = implode('; ', $item['taxa'] ?? array());
            $row['collection_months'] = implode('; ', $months);
            $description = $item['description'] ?? $row['description'] ?? '';
            $intro = $item['shortDescription'] ?? preg_split('/\bSampling\b/u', $description, 2)[0];
            $row['description'] = trim(preg_replace('/\s+/u', ' ', $intro));
            $public = array();
            foreach ($columns as $column) $public[$column] = $row[$column];
            $rows[] = $public;
        }
        return array('columns' => $columns, 'rows' => $rows);
    }

    public static function csv(array $summary): string {
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, $summary['columns'], ',', '"', '');
        foreach ($summary['rows'] as $row) fputcsv($stream, array_values($row), ',', '"', '');
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        return $csv;
    }

    /** All formats share the summary rows; the browse catalog supplies PDF metadata. */
    public static function forPdf(array $catalog, string $baseUrl, array $summary): array {
        $columns = array('sample_type', 'collection_code', 'description_url', 'protocol', 'taxonomic_groups',
            'preservation', 'storage_conditions', 'storage_container', 'available_dates', 'bulk_sample_identifications', 'description');
        $rows = array();
        $items = array_column($catalog['items'], null, 'id');
        foreach ($summary['rows'] as $summaryRow) {
            $id = (int)$summaryRow['collection_id'];
            $item = $items[$id] ?? array(
                'id' => $id, 'name' => $summaryRow['sample_type'],
                'description' => $summaryRow['description'] ?? '',
                'storageConditions' => $summaryRow['storage_conditions'] ?? '',
                'storageContainer' => $summaryRow['storage_container'] ?? '',
            );
            $months = array();
            foreach ($item['siteCodes'] ?? array() as $site) $months = array_merge($months, $site['availableMonths']);
            $months = array_values(array_unique($months));
            sort($months);
            $intro = $item['shortDescription'] ?? preg_split('/\bSampling\b/u', $item['description'] ?? '', 2)[0];
            $rows[] = array(
                'sample_type' => $item['name'],
                'collection_code' => $item['collectionCode'] ?? '',
                'description_url' => rtrim($baseUrl, '/') . '/collections/misc/neoncollprofiles.php?collid=' . (int)$item['id'],
                'protocol' => implode('; ', $item['protocols'] ?? array()),
                'taxonomic_groups' => implode('; ', $item['taxa'] ?? array()),
                'preservation' => implode('; ', $item['preservation'] ?? array()),
                'storage_conditions' => $item['storageConditions'] ?? '',
                'storage_container' => $item['storageContainer'] ?? '',
                'available_dates' => $months ? reset($months) . ' through ' . end($months) : 'No dated records',
                'bulk_sample_identifications' => $summaryRow['bulk_sample_identifications'] ?? '',
                'description' => trim(preg_replace('/\s+/u', ' ', $intro)),
            );
        }
        usort($rows, function ($a, $b) { return strnatcasecmp($a['sample_type'], $b['sample_type']); });
        return array('columns' => $columns, 'rows' => $rows);
    }

    /** Reuse a completed PDF until its data, generation date, or renderer changes. */
    public static function cachedPdf(array $summary, string $directory): string {
        $key = hash('sha256', hash_file('sha256', __FILE__) . '|' . gmdate('Y-m-d') . '|' . json_encode($summary, JSON_THROW_ON_ERROR));
        if (!is_dir($directory) || !is_writable($directory)) return static::pdf($summary);
        $lock = @fopen($directory . '/sample-summary-pdf.lock', 'c');
        if (!$lock) return static::pdf($summary);
        try {
            if (!flock($lock, LOCK_EX)) return static::pdf($summary);
            $path = $directory . '/sample-summary-pdf.json';
            $cached = is_readable($path) ? json_decode(file_get_contents($path), true) : null;
            if (($cached['key'] ?? '') === $key) {
                $bytes = base64_decode($cached['pdf'] ?? '', true);
                if ($bytes !== false && substr($bytes, 0, 5) === '%PDF-') return $bytes;
            }
            $bytes = static::pdf($summary);
            // Readers share this lock; an interrupted write is discarded on the next request.
            @file_put_contents($path, json_encode(array('key' => $key, 'pdf' => base64_encode($bytes)), JSON_THROW_ON_ERROR));
            return $bytes;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public static function pdf(array $summary): string {
        require_once dirname(__DIR__, 2) . '/vendor/tcpdf/tcpdf.php';
        $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
        $pdf->SetCreator('NEON Biorepository');
        $pdf->SetTitle('NEON Sample Type Catalog');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(18, 15, 18);
        $pdf->SetAutoPageBreak(true, 15);
        // Embedded Unicode fonts avoid expensive per-download font subsetting.
        $pdf->setFontSubsetting(false);
        $pdf->setCellPaddings(0, 0, 0, 0);
        $pdf->setCellHeightRatio(1.2);
        $pdf->AddPage();
        $width = $pdf->getPageWidth() - 36;
        $bottom = $pdf->getPageHeight() - 15;
        $pdf->SetFont('calibri', '', 22);
        $pdf->MultiCell($width, 10, 'NEON Sample Type Catalog', 0, 'L');
        $pdf->SetFont('calibri', 'B', 13);
        $pdf->Write(6, 'Generated: ');
        $pdf->SetFont('calibri', '', 13);
        $pdf->Write(6, gmdate('Y-m-d') . '    ');
        $pdf->SetFont('calibri', 'B', 13);
        $pdf->Write(6, 'Sample Type Count: ');
        $pdf->SetFont('calibri', '', 13);
        $pdf->Write(6, count($summary['rows']) . ' (full catalog)', '', false, 'L', true);
        $pdf->Ln(2);
        $pdf->MultiCell($width, 6, 'Catalog entries list physical sample types. Identification collections shown in the explorer are linked under Bulk Sample Identifications in their associated entries, rather than listed separately.', 0, 'L');
        $pdf->Ln(4);
        $labels = array('collection_code' => 'Collection Code', 'description_url' => 'URL',
            'taxonomic_groups' => 'Taxon Groups', 'storage_conditions' => 'Storage', 'storage_container' => 'Container');
        foreach ($summary['rows'] as $index => $row) {
            $name = (string)($row['sample_type'] ?? ('Sample type ' . ($index + 1)));
            $description = (string)($row['description'] ?? '');
            $url = (string)($row['description_url'] ?? '');
            $url = preg_match('~^https?://~i', $url) ? $url : '';
            $lines = array();
            $line = array();
            $lineWidth = 0;
            $pdf->SetFont('calibri', '', 13);
            foreach ($summary['columns'] as $column) {
                if ($column === 'sample_type' || $column === 'description') continue;
                $value = trim(preg_replace('/\s+/u', ' ', (string)($row[$column] ?? '')));
                if ($column === 'bulk_sample_identifications' && $value === '') continue;
                $label = $labels[$column] ?? ucwords(str_replace('_', ' ', $column));
                $text = $label . ': ' . ($value !== '' ? $value : '-');
                $size = $pdf->GetStringWidth($text) + 6;
                if ($line && $lineWidth + $size > $width - 8) {
                    $lines[] = $line;
                    $line = array();
                    $lineWidth = 0;
                }
                $line[] = array('label' => $label, 'value' => $value, 'url' => in_array($column, array('description_url', 'bulk_sample_identifications'), true) && preg_match('~^https?://~i', $value) ? $value : '');
                $lineWidth += $size;
            }
            if ($line) $lines[] = $line;
            // Keep ordinary entries together; oversized descriptions may continue onto another page.
            $height = 13;
            foreach ($lines as $fields) {
                $text = implode('    ', array_map(function ($field) { return $field['label'] . ': ' . $field['value']; }, $fields));
                $height += $pdf->getStringHeight($width, $text) + 1;
            }
            $pdf->SetFont('calibri', '', 13);
            $height += $pdf->getStringHeight($width, $description) + 4;
            $pdf->SetFont('calibri', 'B', 14);
            $height += max(0, $pdf->getStringHeight($width, $name) - 6);
            if ($pdf->GetY() + min($height, 35) > $bottom
                || ($height < $bottom - 15 && $pdf->GetY() + $height > $bottom)) $pdf->AddPage();
            $pdf->SetDrawColor(170, 170, 170);
            $pdf->SetLineWidth(0.15);
            $pdf->Line(18, $pdf->GetY(), $pdf->getPageWidth() - 18, $pdf->GetY());
            $pdf->Ln(3);
            $pdf->SetTextColor(0, 115, 207);
            $pdf->Write(6, $name, $url, false, 'L', true);
            $pdf->Ln(1);
            foreach ($lines as $fields) {
                foreach ($fields as $i => $field) {
                    if ($i) $pdf->Write(6, '    ');
                    $pdf->SetTextColor(30, 30, 30);
                    $pdf->SetFont('calibri', 'B', 13);
                    $pdf->Write(6, $field['label'] . ': ');
                    $pdf->SetFont('calibri', '', 13);
                    if ($field['url']) $pdf->SetTextColor(50, 70, 150);
                    $pdf->Write(6, $field['value'] !== '' ? $field['value'] : '-', $field['url']);
                }
                $pdf->Ln(6);
            }
            if ($description !== '') {
                $pdf->Ln(2);
                $pdf->SetFont('calibri', '', 13);
                $pdf->SetTextColor(30, 30, 30);
                $pdf->MultiCell($width, 6, $description, 0, 'L');
            }
            $pdf->Ln(5);
        }
        return $pdf->Output('', 'S');
    }
}
