<?php

/** The public summary columns and ordering used by PR #1223. */
class SampleTypeSummaryExport {
    public static function read($result): array {
        $columns = array_slice(array_map(function ($field) { return $field->name; }, $result->fetch_fields()), 1);
        $rows = array();
        while ($row = $result->fetch_assoc()) {
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

    public static function pdf(array $summary): string {
        require_once dirname(__DIR__, 2) . '/vendor/tcpdf/tcpdf.php';
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('NEON Biorepository');
        $pdf->SetTitle('NEON Sample Type Summary');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->setFooterFont(array('calibri', '', 9));
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->SetFont('calibri', '', 10);
        $escape = function ($value) {
            return nl2br(htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        };
        $pdf->AddPage();
        $pdf->writeHTML('<h1 style="color:#0073cf">NEON Sample Type Summary</h1><p>'
            . count($summary['rows']) . ' sample types</p>', true, false, true, false, '');
        foreach ($summary['rows'] as $index => $row) {
            if ($index > 0) $pdf->AddPage();
            $html = '<h2 style="color:#0073cf">' . $escape($row['sample_type'] ?? ('Sample type ' . ($index + 1))) . '</h2>';
            $html .= '<table cellpadding="5" border="1">';
            foreach ($summary['columns'] as $column) {
                $html .= '<tr><td width="32%" bgcolor="#f2f4f5"><b>' . $escape($column)
                    . '</b></td><td width="68%">' . $escape($row[$column]) . '</td></tr>';
            }
            $html .= '</table>';
            $pdf->writeHTML($html, true, false, true, false, '');
        }
        return $pdf->Output('', 'S');
    }
}
