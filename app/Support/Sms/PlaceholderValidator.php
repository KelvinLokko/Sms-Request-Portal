<?php

namespace App\Support\Sms;

final class PlaceholderValidator
{
    /**
     * Extract [HEADER] placeholders from a message body.
     *
     * @return list<string>
     */
    public static function extract(string $message): array
    {
        preg_match_all('/\[([^\]]+)\]/', $message, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * @param  list<string>  $fileHeaders
     * @return list<string> Missing placeholders
     */
    public static function missing(string $message, array $fileHeaders): array
    {
        $placeholders = self::extract($message);
        $normalisedHeaders = array_map(
            static fn (string $header): string => mb_strtolower(trim($header)),
            $fileHeaders,
        );

        $missing = [];
        foreach ($placeholders as $placeholder) {
            if (! in_array(mb_strtolower(trim($placeholder)), $normalisedHeaders, true)) {
                $missing[] = $placeholder;
            }
        }

        return $missing;
    }
}
