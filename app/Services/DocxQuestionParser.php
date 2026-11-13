<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class DocxQuestionParser
{
    private const MAX_DOCUMENT_XML_BYTES = 20 * 1024 * 1024;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File DOCX tidak dapat dibuka.');
        }

        $stat = $zip->statName('word/document.xml');
        if (! $stat || ($stat['size'] ?? 0) > self::MAX_DOCUMENT_XML_BYTES) {
            $zip->close();
            throw new RuntimeException('Struktur DOCX tidak valid atau terlalu besar.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException('Dokumen tidak memiliki konten Word yang valid.');
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new RuntimeException('Konten XML pada DOCX rusak.');
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $lines = [];

        foreach ($xpath->query('//w:body/w:p | //w:body/w:tbl/w:tr/w:tc/w:p') as $paragraph) {
            if (! $paragraph instanceof DOMElement) {
                continue;
            }

            $parts = [];
            foreach ($xpath->query('.//w:t | .//w:tab | .//w:br', $paragraph) as $node) {
                $parts[] = in_array($node->localName, ['tab', 'br'], true) ? ' ' : $node->textContent;
            }

            $line = trim(preg_replace('/\s+/u', ' ', implode('', $parts)) ?? '');
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $this->parseLines($lines);
    }

    /**
     * @param  array<int, string>  $lines
     * @return array<int, array<string, mixed>>
     */
    public function parseLines(array $lines): array
    {
        $blocks = [];
        $current = [];

        foreach ($lines as $line) {
            if ($line === '---') {
                if ($current !== []) {
                    $blocks[] = $current;
                    $current = [];
                }

                continue;
            }

            if (preg_match('/^SOAL\s*:/iu', $line) && $current !== []) {
                $blocks[] = $current;
                $current = [];
            }

            if ($current !== [] || preg_match('/^SOAL\s*:/iu', $line)) {
                $current[] = $line;
            }
        }

        if ($current !== []) {
            $blocks[] = $current;
        }

        return array_map(fn (array $block, int $index) => $this->parseBlock($block, $index + 1), $blocks, array_keys($blocks));
    }

    /** @return array<string, mixed> */
    private function parseBlock(array $lines, int $number): array
    {
        $question = [
            'number' => $number,
            'content' => '',
            'options' => [],
            'correct' => '',
            'domain' => '',
            'difficulty' => '',
            'explanation' => '',
        ];
        $activeField = null;

        foreach ($lines as $line) {
            if (preg_match('/^SOAL\s*:\s*(.*)$/iu', $line, $match)) {
                $question['content'] = trim($match[1]);
                $activeField = 'content';
            } elseif (preg_match('/^([A-E])[\.\)]\s*(.+)$/iu', $line, $match)) {
                $label = strtoupper($match[1]);
                $question['options'][$label] = trim($match[2]);
                $activeField = 'option:'.$label;
            } elseif (preg_match('/^KUNCI\s*:\s*([A-E])\s*$/iu', $line, $match)) {
                $question['correct'] = strtoupper($match[1]);
                $activeField = null;
            } elseif (preg_match('/^MATERI\s*:\s*(.*)$/iu', $line, $match)) {
                $question['domain'] = trim($match[1]);
                $activeField = 'domain';
            } elseif (preg_match('/^LEVEL\s*:\s*(.*)$/iu', $line, $match)) {
                $question['difficulty'] = $this->normalizeDifficulty($match[1]);
                $activeField = null;
            } elseif (preg_match('/^PEMBAHASAN\s*:\s*(.*)$/iu', $line, $match)) {
                $question['explanation'] = trim($match[1]);
                $activeField = 'explanation';
            } elseif ($activeField !== null) {
                if (str_starts_with($activeField, 'option:')) {
                    $label = substr($activeField, 7);
                    $question['options'][$label] = trim($question['options'][$label].' '.$line);
                } else {
                    $question[$activeField] = trim($question[$activeField].' '.$line);
                }
            }
        }

        ksort($question['options']);
        $errors = [];
        if ($question['content'] === '') {
            $errors[] = 'Teks SOAL wajib diisi.';
        }
        if (array_diff(['A', 'B', 'C', 'D'], array_keys($question['options'])) !== []) {
            $errors[] = 'Minimal tersedia opsi A sampai D.';
        }
        if ($question['correct'] === '' || ! array_key_exists($question['correct'], $question['options'])) {
            $errors[] = 'KUNCI harus menunjuk opsi yang tersedia.';
        }
        if ($question['domain'] === '') {
            $errors[] = 'MATERI wajib diisi.';
        } elseif (mb_strlen($question['domain']) > 120) {
            $errors[] = 'MATERI maksimal 120 karakter.';
        }
        if ($question['difficulty'] === '') {
            $errors[] = 'LEVEL harus Mudah, Sedang, atau Sulit.';
        }

        $question['valid'] = $errors === [];
        $question['errors'] = $errors;

        return $question;
    }

    private function normalizeDifficulty(string $value): string
    {
        return match (mb_strtolower(trim($value))) {
            'mudah', 'easy' => 'easy',
            'sedang', 'medium' => 'medium',
            'sulit', 'hard' => 'hard',
            default => '',
        };
    }
}
