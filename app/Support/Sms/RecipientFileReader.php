<?php

namespace App\Support\Sms;

use League\Csv\Reader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;

final class RecipientFileReader
{
    /**
     * Stream recipient rows. Invokes $onHeader once, then $onRow for each data row.
     *
     * @param  callable(list<string>): void  $onHeader
     * @param  callable(int, list<string>): void  $onRow  rowNumber (1-based data rows), values
     */
    public static function each(string $absolutePath, string $extension, callable $onHeader, callable $onRow): void
    {
        $extension = strtolower($extension);

        match ($extension) {
            'csv', 'txt' => self::eachCsv($absolutePath, $onHeader, $onRow),
            'xlsx' => self::eachXlsx($absolutePath, $onHeader, $onRow),
            default => throw new RuntimeException("Unsupported recipient file type: {$extension}"),
        };
    }

    /**
     * @param  callable(list<string>): void  $onHeader
     * @param  callable(int, list<string>): void  $onRow
     */
    private static function eachCsv(string $absolutePath, callable $onHeader, callable $onRow): void
    {
        $reader = Reader::createFromPath($absolutePath);
        $reader->setHeaderOffset(0);

        /** @var list<string> $headers */
        $headers = array_values(array_map(
            static fn ($h) => trim((string) $h),
            $reader->getHeader(),
        ));

        $onHeader($headers);

        $rowNumber = 0;
        foreach ($reader->getRecords() as $record) {
            $rowNumber++;
            /** @var array<string|int, string|null> $record */
            $values = array_values(array_map(
                static fn ($value) => trim((string) ($value ?? '')),
                $record,
            ));
            $onRow($rowNumber, $values);
        }
    }

    /**
     * @param  callable(list<string>): void  $onHeader
     * @param  callable(int, list<string>): void  $onRow
     */
    private static function eachXlsx(string $absolutePath, callable $onHeader, callable $onRow): void
    {
        $reader = new XlsxReader;
        $reader->open($absolutePath);

        $headerSeen = false;
        $rowNumber = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $values = array_map(
                    static fn ($value) => trim((string) $value),
                    $row->toArray(),
                );

                if (! $headerSeen) {
                    if ($values === [] || self::rowIsEmpty($values)) {
                        continue;
                    }
                    $onHeader(array_values($values));
                    $headerSeen = true;

                    continue;
                }

                if (self::rowIsEmpty($values)) {
                    continue;
                }

                $rowNumber++;
                $onRow($rowNumber, array_values($values));
            }
            break;
        }

        $reader->close();

        if (! $headerSeen) {
            throw new RuntimeException('Recipient file has no header row.');
        }
    }

    /**
     * @param  list<string>  $values
     */
    private static function rowIsEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $headers
     */
    public static function detectPhoneColumn(array $headers): ?string
    {
        $candidates = ['phone', 'mobile', 'msisdn', 'number', 'msisdn_number', 'recipient', 'contact'];

        foreach ($headers as $header) {
            $normalised = mb_strtolower(trim($header));
            if (in_array($normalised, $candidates, true)) {
                return $header;
            }
        }

        return $headers[0] ?? null;
    }
}
