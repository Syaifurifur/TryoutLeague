<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class SpreadsheetReader
{
    /** @return array<int, array<string, string>> */
    public function read(string $path, string $extension): array
    {
        $rows = strtolower($extension) === 'csv' ? $this->readCsv($path) : $this->readXlsx($path);
        if (count($rows) < 2) {
            throw new RuntimeException('Spreadsheet harus memiliki header dan minimal satu baris data.');
        }
        $headerIndex = collect($rows)->search(function ($row) {
            $normalized = array_map(fn ($value) => $this->normalizeHeader((string) $value), $row);

            return in_array('nama', $normalized, true) || in_array('soal', $normalized, true);
        });
        if ($headerIndex === false) {
            throw new RuntimeException('Baris header tidak ditemukan. Gunakan template resmi tanpa mengubah nama kolom.');
        }
        $headers = array_map(fn ($value) => $this->normalizeHeader((string) $value), $rows[$headerIndex]);
        $rows = array_slice($rows, $headerIndex + 1);

        return collect($rows)->filter(fn ($row) => collect($row)->filter(fn ($value) => trim((string) $value) !== '')->isNotEmpty())->values()->map(function ($row) use ($headers) {
            $result = [];
            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $result[$header] = trim((string) ($row[$index] ?? ''));
                }
            }

            return $result;
        })->all();
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            throw new RuntimeException('File CSV tidak dapat dibaca.');
        }
        $rows = [];
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    private function readXlsx(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File XLSX tidak dapat dibuka.');
        }
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            throw new RuntimeException('Sheet pertama tidak ditemukan.');
        }
        $shared = [];
        if (($sharedXml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $doc = $this->document($sharedXml);
            $xpath = new DOMXPath($doc);
            $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            foreach ($xpath->query('//x:si') as $item) {
                $text = '';
                foreach ($xpath->query('.//x:t', $item) as $node) {
                    $text .= $node->textContent;
                }
                $shared[] = $text;
            }
        }
        $zip->close();

        $doc = $this->document($sheetXml);
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = [];
        foreach ($xpath->query('//x:sheetData/x:row') as $rowNode) {
            $row = [];
            foreach ($xpath->query('./x:c', $rowNode) as $cell) {
                $reference = $cell->attributes?->getNamedItem('r')?->nodeValue ?? 'A1';
                preg_match('/^[A-Z]+/', $reference, $match);
                $index = $this->columnIndex($match[0] ?? 'A');
                $type = $cell->attributes?->getNamedItem('t')?->nodeValue;
                $valueNode = $xpath->query('./x:v', $cell)->item(0);
                $value = $valueNode?->textContent ?? '';
                if ($type === 's') {
                    $value = $shared[(int) $value] ?? '';
                }
                if ($type === 'inlineStr') {
                    $value = '';
                    foreach ($xpath->query('.//x:t', $cell) as $node) {
                        $value .= $node->textContent;
                    }
                }
                $row[$index] = $value;
            }
            if ($row !== []) {
                $max = max(array_keys($row));
                $rows[] = array_map(fn ($index) => $row[$index] ?? '', range(0, $max));
            }
        }

        return $rows;
    }

    private function document(string $xml): DOMDocument
    {
        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $doc->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            throw new RuntimeException('Struktur XML spreadsheet rusak.');
        }

        return $doc;
    }

    private function columnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + ord($letter) - 64;
        }

        return $index - 1;
    }

    private function normalizeHeader(string $header): string
    {
        $header = mb_strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/u', '_', $header) ?? '';

        return trim($header, '_');
    }
}
