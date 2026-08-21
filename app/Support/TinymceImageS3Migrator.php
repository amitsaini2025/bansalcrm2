<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    /**
     * S3 object key from a stored TinyMCE image URL, or null for local /storage/ and document paths.
     */
    public function keyFromUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '' || preg_match('#/storage/tinymce-images/#i', $url)) {
            return null;
        }

        if (preg_match('#/tinymce/image/([a-f0-9-]{36}\.(?:png|jpe?g|gif|webp))#i', $url, $matches)) {
            return $this->s3Key($matches[1]);
        }

        if (! preg_match('#tinymce-images/([a-f0-9-]{36}\.(?:png|jpe?g|gif|webp))#i', $url, $matches)) {
            return null;
        }

        return $this->s3Key($matches[1]);
    }

    /**
     * Short-lived S3 URL for note display only. Stored HTML is never rewritten.
     */
    public function temporaryDisplayUrl(string $src): string
    {
        $key = $this->keyFromUrl($src);
        if ($key === null) {
            return $src;
        }

        try {
            return Storage::disk('s3')->temporaryUrl($key, now()->addMinutes(15));
        } catch (\Throwable) {
            return $src;
        }
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

    /**
     * Upload inlined data:image src values to S3 and replace them with object URLs.
     * Leaves http(s) images and document paths unchanged. Skips images over 2MB.
     */
    public function replaceDataUrisWithS3(string $html): string
    {
        if (trim($html) === '' || ! str_contains(strtolower($html), 'data:image')) {
            return $html;
        }

        $enc = 'UTF-8';
        $wrapper = '<div id="tinymce-s3-root">'.$html.'</div>';
        $dom = new \DOMDocument;
        @$dom->loadHTML(
            '<?xml encoding="'.$enc.'">'.$wrapper,
            \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD
        );
        $root = $dom->getElementById('tinymce-s3-root');
        if (! $root) {
            return $html;
        }

        $images = [];
        foreach ($root->getElementsByTagName('img') as $img) {
            $images[] = $img;
        }

        foreach ($images as $img) {
            $src = trim(html_entity_decode($img->getAttribute('src')));
            $url = $this->storeDataUriOnS3($src);
            if ($url !== null) {
                $img->setAttribute('src', $url);
            }
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out !== '' ? $out : $html;
    }

    private function storeDataUriOnS3(string $src): ?string
    {
        if (! preg_match('#^data:image/(png|jpe?g|gif|webp);base64,([A-Za-z0-9+/=\s]+)$#i', $src, $matches)) {
            return null;
        }

        $ext = strtolower($matches[1]);
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $binary = base64_decode(preg_replace('/\s+/', '', $matches[2]), true);
        if ($binary === false || $binary === '' || strlen($binary) > 2048 * 1024) {
            return null;
        }

        $key = $this->s3Key((string) Str::uuid().'.'.$ext);

        try {
            Storage::disk('s3')->put($key, $binary);
        } catch (\Throwable) {
            return null;
        }

        $url = Storage::disk('s3')->url($key);

        return is_string($url) && $url !== '' ? $url : null;
    }
}
