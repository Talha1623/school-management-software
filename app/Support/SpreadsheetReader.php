<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

class SpreadsheetReader
{
    /**
     * @return array<int, array<int, mixed>>
     */
    public static function toArray(UploadedFile|string $file): array
    {
        if ($file instanceof UploadedFile) {
            $path = $file->getRealPath();
            $extension = strtolower($file->getClientOriginalExtension());
        } else {
            $path = (string) $file;
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        }

        if (! $path || ! is_readable($path)) {
            throw new \InvalidArgumentException('Unable to read uploaded spreadsheet file.');
        }

        if ($extension === 'xlsx') {
            return XlsxReader::toArray($path);
        }

        if ($extension === 'xls') {
            throw new \RuntimeException('Old .xls format is not supported. Please save the file as .xlsx or CSV and upload again.');
        }

        throw new \InvalidArgumentException('Unsupported spreadsheet format. Use .xlsx or .csv.');
    }
}
