<?php

namespace App\Support;

/**
 * Maps TinyMCE note screenshot filenames in HTML and rewrites local /storage/ URLs.
 * Does not touch document/checklist S3 keys.
 */
class TinymceImageS3Migrator
{
    public const PREFIX = 'tinymce-images/';

    /**
     * @return list<string>
     */
    public function extractFilenames(string $html): array
    {
        if ($html === '') {
            return [];
        }

        preg_match_all('#tinymce-images/([a-f0-9-]{36}\.(?:png|jpe?g|gif|webp))#i', $html, $matches);
        $names = $matches[1] ?? [];

        return array_values(array_unique(array_map('strtolower', $names)));
    }

    public function isSafeFilename(string $filename): bool
    {
        return (bool) preg_match('/^[a-f0-9-]{36}\.(png|jpe?g|gif|webp)$/i', $filename);
    }

    public function s3Key(string $filename): string
    {
        return self::PREFIX.strtolower($filename);
    }

    public function rewriteStorageUrlsToS3(string $html, string $filename, string $s3Url): string
    {
        if ($html === '' || $s3Url === '' || ! $this->isSafeFilename($filename)) {
            return $html;
        }

        $quoted = preg_quote(strtolower($filename), '#');
        $updated = preg_replace(
            '#https?://[^"\'\s<>]+/storage/tinymce-images/'.$quoted.'#i',
            $s3Url,
            $html
        );
        $updated = preg_replace(
            '#(?<=["\'])/storage/tinymce-images/'.$quoted.'#i',
            $s3Url,
            $updated ?? $html
        );

        return $updated ?? $html;
    }
}
