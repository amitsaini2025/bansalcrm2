<?php

namespace App\Support;

final class Utf8Helper
{
    /**
     * Strip or replace invalid UTF-8 byte sequences so json_encode() succeeds.
     */
    public static function sanitize(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (function_exists('mb_scrub')) {
            return mb_scrub($value, 'UTF-8');
        }

        if (function_exists('iconv')) {
            $sanitized = @iconv('UTF-8', 'UTF-8//IGNORE', $value);

            return $sanitized !== false ? $sanitized : '';
        }

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';
    }

    /**
     * Sanitize and escape for safe use inside HTML attributes (e.g. data-description).
     */
    public static function sanitizeForHtmlAttribute(?string $value): string
    {
        return htmlspecialchars(
            self::sanitize($value),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }

    /**
     * Sanitize and escape for safe HTML text output.
     */
    public static function sanitizeForHtml(?string $value): string
    {
        return htmlspecialchars(
            self::sanitize($value),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}
