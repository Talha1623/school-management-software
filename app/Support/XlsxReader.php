<?php

namespace App\Support;

use SimpleXMLElement;
use ZipArchive;

class XlsxReader
{
    /**
     * @return array<int, array<int, mixed>>
     */
    public static function toArray(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('PHP zip extension is required to read .xlsx files. Enable ext-zip on the server.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Unable to open Excel file.');
        }

        $sharedStrings = self::readSharedStrings($zip);
        $sheetXml = self::readFirstSheetXml($zip);
        $zip->close();

        if ($sheetXml === null) {
            throw new \RuntimeException('No worksheet found in Excel file.');
        }

        return self::parseSheet($sheetXml, $sharedStrings);
    }

    /**
     * @return array<int, string>
     */
    private static function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $doc = self::loadXml($xml);
        $strings = [];

        foreach ($doc->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;

                continue;
            }

            $text = '';
            foreach ($si->r as $run) {
                $text .= (string) ($run->t ?? '');
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private static function readFirstSheetXml(ZipArchive $zip): ?string
    {
        $sheetNames = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name !== false && preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
                $sheetNames[] = $name;
            }
        }

        sort($sheetNames, SORT_NATURAL);

        foreach ($sheetNames as $name) {
            $xml = $zip->getFromName($name);
            if ($xml !== false) {
                return $xml;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array<int, array<int, mixed>>
     */
    private static function parseSheet(string $xml, array $sharedStrings): array
    {
        $doc = self::loadXml($xml);
        $sparseRows = [];
        $maxCol = 0;

        if (! isset($doc->sheetData->row)) {
            return [];
        }

        foreach ($doc->sheetData->row as $row) {
            $rowIndex = isset($row['r']) ? ((int) $row['r']) - 1 : count($sparseRows);
            $rowData = [];
            $nextCol = 0;

            foreach ($row->c as $cell) {
                if (isset($cell['r'])) {
                    $colIndex = self::columnIndexFromCellRef((string) $cell['r']);
                    $nextCol = $colIndex + 1;
                } else {
                    $colIndex = $nextCol;
                    $nextCol++;
                }

                $maxCol = max($maxCol, $colIndex);
                $rowData[$colIndex] = self::cellValue($cell, $sharedStrings);
            }

            $sparseRows[$rowIndex] = $rowData;
        }

        if ($sparseRows === []) {
            return [];
        }

        ksort($sparseRows);
        $result = [];

        foreach ($sparseRows as $rowData) {
            $line = [];
            for ($col = 0; $col <= $maxCol; $col++) {
                $line[] = $rowData[$col] ?? null;
            }
            $result[] = $line;
        }

        return $result;
    }

    private static function loadXml(string $xml): SimpleXMLElement
    {
        $xml = preg_replace('/(<\/?)(?:[\w-]+:)?([^>]*>)/', '$1$2', $xml) ?? $xml;
        $xml = preg_replace('/ xmlns(?::[^=]*)?="[^"]*"/', '', $xml) ?? $xml;
        $xml = preg_replace('/\s+[\w-]+:[\w-]+="[^"]*"/', '', $xml) ?? $xml;

        $previous = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($doc === false) {
            throw new \RuntimeException('Unable to parse worksheet data.');
        }

        return $doc;
    }

    private static function columnIndexFromCellRef(string $ref): int
    {
        if (! preg_match('/^([A-Z]+)/i', $ref, $matches)) {
            return 0;
        }

        $letters = strtoupper($matches[1]);
        $index = 0;

        for ($i = 0, $length = strlen($letters); $i < $length; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - ord('A') + 1);
        }

        return $index - 1;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     */
    private static function cellValue(SimpleXMLElement $cell, array $sharedStrings): mixed
    {
        $type = (string) ($cell['t'] ?? '');
        $value = isset($cell->v) ? (string) $cell->v : '';

        return match ($type) {
            's' => $sharedStrings[(int) $value] ?? '',
            'inlineStr' => (string) ($cell->is->t ?? ''),
            'b' => $value === '1',
            'str' => $value,
            default => self::normalizeNumericValue($value),
        };
    }

    private static function normalizeNumericValue(string $value): mixed
    {
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }
}
