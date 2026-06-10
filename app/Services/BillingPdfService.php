<?php

namespace App\Services;

class BillingPdfService
{
    public function renderBankSlip(array $municipality, array $bankSlip, $label)
    {
        return $this->render('Billing document', [
            'Municipality: ' . $municipality['name'],
            'Municipality code: ' . $municipality['code'],
            'Reference: ' . $label,
            'Due date: ' . ($bankSlip['due_date'] ?? 'Not informed'),
            'Amount: ' . number_format((float) ($bankSlip['amount'] ?? 0), 2, '.', ','),
            'Status: ' . ($bankSlip['status'] ?? 'Not informed'),
            'Mock document generated for portfolio demonstration.',
        ]);
    }

    private function render($title, array $lines)
    {
        $commands = [
            'BT',
            '/F1 16 Tf',
            '50 790 Td',
            '(' . $this->escapeText($title) . ') Tj',
            '/F1 11 Tf',
        ];

        foreach ($lines as $line) {
            $commands[] = '0 -24 Td';
            $commands[] = '(' . $this->escapeText($line) . ') Tj';
        }
        $commands[] = 'ET';

        $stream = implode("\n", $commands);
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private function escapeText($value)
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $value);
        $value = $ascii === false ? (string) $value : $ascii;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
