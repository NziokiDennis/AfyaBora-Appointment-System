<?php
// Shared CSV / Excel / PDF export for admin report & list pages.
// Call export_and_exit() before any HTML output on the page.

require_once __DIR__ . "/../../lib/fpdf/fpdf.php";

function export_and_exit($format, $filename, $title, array $headers, array $rows) {
    switch ($format) {
        case 'csv':
            export_csv($filename, $headers, $rows);
            break;
        case 'excel':
            export_excel($filename, $title, $headers, $rows);
            break;
        case 'pdf':
            export_pdf($filename, $title, $headers, $rows);
            break;
        default:
            return; // unknown format, let the page render normally
    }
    exit;
}

function export_csv($filename, array $headers, array $rows) {
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
    $out = fopen("php://output", "w");
    fputcsv($out, $headers);
    foreach ($rows as $row) {
        fputcsv($out, $row);
    }
    fclose($out);
}

function export_excel($filename, $title, array $headers, array $rows) {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
    echo "<table border='1'>";
    echo "<tr><th colspan='" . count($headers) . "' style='font-size:14px;text-align:left'>" . htmlspecialchars($title) . "</th></tr>";
    echo "<tr>";
    foreach ($headers as $h) {
        echo "<th>" . htmlspecialchars($h) . "</th>";
    }
    echo "</tr>";
    foreach ($rows as $row) {
        echo "<tr>";
        foreach ($row as $cell) {
            echo "<td>" . htmlspecialchars((string)$cell) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

function export_pdf($filename, $title, array $headers, array $rows) {
    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, $title, 0, 1);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(0, 6, 'Generated: ' . date('Y-m-d H:i'), 0, 1);
    $pdf->Ln(4);

    $colCount = max(1, count($headers));
    $pageWidth = $pdf->GetPageWidth() - 20;
    $colWidth = $pageWidth / $colCount;

    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(230, 230, 230);
    foreach ($headers as $h) {
        $pdf->Cell($colWidth, 8, $h, 1, 0, 'L', true);
    }
    $pdf->Ln();

    $pdf->SetFont('Arial', '', 8);
    foreach ($rows as $row) {
        foreach ($row as $cell) {
            $text = mb_convert_encoding((string)$cell, 'ISO-8859-1', 'UTF-8');
            $pdf->Cell($colWidth, 7, $text, 1);
        }
        $pdf->Ln();
    }

    $pdf->Output('D', $filename . '.pdf');
}
