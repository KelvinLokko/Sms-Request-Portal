<?php

namespace App\Support;

class CsvFormulaEscaper
{
    /**
     * Prefix values that Excel/Sheets would treat as formulas.
     */
    public static function escape(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
