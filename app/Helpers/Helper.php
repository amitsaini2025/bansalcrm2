<?php

namespace App\Helpers; // Your helpers namespace

// NOTE: User model/table has been removed
// use App\Models\User;
use App\Models\Company;
use App\Models\Profile;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class Helper
{
    public static function changeDateFormate($date, $date_format)
    {
        return Carbon::createFromFormat('Y-m-d', $date)->format($date_format);
    }

    public static function getUserCompany(): ?object
    {
        $companyId = Auth::user()->comp_id ?? null;

        return $companyId ? Company::find($companyId) : null;
    }

    /**
     * Get the default CRM profile (Bansal Education Group - Profile ID 1).
     * Used for all non-invoice contexts: emails, receipts, templates, etc.
     */
    public static function defaultCrmProfile(): ?Profile
    {
        $profileId = config('app.default_profile_id', 1);

        return Profile::find($profileId);
    }

    /**
     * Get the default CRM company name.
     */
    public static function defaultCrmCompanyName(): string
    {
        $profile = self::defaultCrmProfile();

        return $profile ? $profile->company_name : 'Bansal Education Group';
    }

    /**
     * Resolve logo filename for an invoice (stored profile snapshot, then default CRM profile).
     */
    public static function invoiceProfileLogoFilename($invoicedetail): ?string
    {
        if (! empty($invoicedetail->profile)) {
            $profile = json_decode($invoicedetail->profile);
            if ($profile && ! empty($profile->logo)) {
                return $profile->logo;
            }
        }

        $crmProfile = self::defaultCrmProfile();

        return ($crmProfile && ! empty($crmProfile->logo)) ? $crmProfile->logo : null;
    }

    /**
     * Embed profile logo as base64 data URI for DomPDF (same approach as client receipt print preview).
     */
    public static function profileLogoBase64(?string $logoFilename = null): ?string
    {
        $paths = [];
        if (! empty($logoFilename)) {
            $paths[] = config('constants.profile_imgs').DIRECTORY_SEPARATOR.$logoFilename;
        } else {
            $profile = self::defaultCrmProfile();
            if ($profile && ! empty($profile->logo)) {
                $paths[] = config('constants.profile_imgs').DIRECTORY_SEPARATOR.$profile->logo;
            }
        }
        $paths[] = public_path('img/logo.png');

        foreach ($paths as $logoPath) {
            if (is_string($logoPath) && file_exists($logoPath)) {
                $logoData = file_get_contents($logoPath);
                $mime = mime_content_type($logoPath) ?: 'image/png';

                return 'data:'.$mime.';base64,'.base64_encode($logoData);
            }
        }

        return null;
    }

    /**
     * Strip cid: (Content-ID) references from email HTML to prevent ERR_UNKNOWN_URL_SCHEME.
     * Browsers cannot load cid: URLs; replace with transparent 1x1 pixel.
     * Use for server-rendered email content (Conversations tab, etc.).
     */
    public static function stripCidReferences(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }
        $pixel = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
        // img src="cid:..." or src='cid:...'
        $html = preg_replace('/src=["\']cid:[^"\'>]+["\']/i', 'src="'.$pixel.'"', $html);
        // background-image: url("cid:...") or url('cid:...') or url(cid:...)
        $html = preg_replace('/background-image:\s*url\s*\(["\']?cid:[^"\'\)]+["\']?\)/i', 'background-image: none', $html);

        return $html;
    }

    /**
     * Normalize HTML fragment so unclosed/unbalanced tags don't break the page DOM.
     * Use when outputting activity or note descriptions that may contain invalid HTML.
     * Parser closes tags within the fragment so they cannot wrap following content.
     *
     * When $imagesAsLinks is true (note display only), http(s) images become
     * "View image" links. Stored HTML and edit payloads are unchanged.
     */
    public static function normalizeActivityDescriptionHtml(string $html, bool $imagesAsLinks = false): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        $enc = 'UTF-8';
        $wrapper = '<div id="activity-desc-root">'.$html.'</div>';
        $dom = new \DOMDocument;
        @$dom->loadHTML(
            '<?xml encoding="'.$enc.'">'.$wrapper,
            \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD
        );
        $root = $dom->getElementById('activity-desc-root');
        if (! $root) {
            return $html;
        }
        if ($imagesAsLinks) {
            self::replaceImagesWithPreviewLinks($dom, $root);
        }
        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out;
    }

    /**
     * Replace <img> nodes with a new-tab preview link (document-style).
     */
    private static function replaceImagesWithPreviewLinks(\DOMDocument $dom, \DOMElement $root): void
    {
        $images = [];
        foreach ($root->getElementsByTagName('img') as $img) {
            $images[] = $img;
        }

        $total = count($images);
        foreach ($images as $index => $img) {
            $parent = $img->parentNode;
            if (! $parent) {
                continue;
            }

            $src = trim($img->getAttribute('src'));
            if (! self::isSafeHttpUrl($src)) {
                $parent->removeChild($img);

                continue;
            }

            $label = $total > 1 ? 'View image '.($index + 1) : 'View image';
            $link = $dom->createElement('a', $label);
            $link->setAttribute('href', $src);
            $link->setAttribute('target', '_blank');
            $link->setAttribute('rel', 'noopener noreferrer');
            $link->setAttribute('class', 'note-image-link');
            $parent->replaceChild($link, $img);
        }
    }

    private static function isSafeHttpUrl(string $url): bool
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }

    /**
     * Human label for lead list status (display only — does not change stored values).
     *
     * Supports modern string statuses from create/edit, plus legacy numeric IDs from
     * the removed followup_types workflow (see pre-refactor LeadController counters).
     */
    public static function formatLeadStatusDisplay(mixed $status): string
    {
        if ($status === null || $status === '') {
            return '—';
        }

        // Modern string statuses (create/edit forms) — show as stored
        if (is_string($status) && ! is_numeric($status)) {
            $trimmed = trim($status);

            return $trimmed !== '' ? $trimmed : '—';
        }

        // Numeric / numeric-string legacy codes (0/1/11/… from old followup_types IDs)
        if (is_numeric($status) && (string) (int) $status === (string) trim((string) $status)) {
            $id = (int) $status;
            $legacy = [
                0 => 'Not Contacted',
                1 => 'Create Proposal',
                11 => 'Undecided',
                12 => 'Lost',
                13 => 'Won',
                14 => 'Ready to Pay',
            ];

            if (array_key_exists($id, $legacy)) {
                return $legacy[$id];
            }

            // Unmapped legacy code: avoid blank "—" for int; show raw id string for auditability
            return (string) $id;
        }

        $asString = trim((string) $status);

        return $asString !== '' ? $asString : '—';
    }

    /**
     * Browser URL for a document file stored as either a full remote URL or a public relative/local path.
     * Prevents asset() double-wrapping (e.g. https://app/https://bucket.s3.../key).
     */
    public static function documentFileUrl(?string $pathOrUrl): string
    {
        $pathOrUrl = trim((string) $pathOrUrl);
        if ($pathOrUrl === '') {
            return '';
        }

        // Already double-wrapped by a previous asset(fullUrl) misuse
        if (preg_match('#^https?://[^/]+/(https?://.+)$#i', $pathOrUrl, $matches)) {
            return $matches[1];
        }

        // Protocol-relative
        if (str_starts_with($pathOrUrl, '//')) {
            return $pathOrUrl;
        }

        // Absolute remote URL (S3, CDN, etc.)
        if (preg_match('#^https?://#i', $pathOrUrl)) {
            return $pathOrUrl;
        }

        // Scheme-less S3 host/path (legacy bad rows) — never asset()-wrap
        if (
            preg_match('#^[a-z0-9.-]*amazonaws\.com/#i', $pathOrUrl)
            || preg_match('#^[a-z0-9.-]+\.s3[.-]#i', $pathOrUrl)
        ) {
            return 'https://'.ltrim($pathOrUrl, '/');
        }

        return asset(ltrim($pathOrUrl, '/'));
    }

    /**
     * Public URL for an S3 object key, or pass-through for already-absolute URLs.
     * Prefer disk / AWS_URL config (same as Storage uploads) over hand-built hosts.
     */
    public static function s3ObjectUrl(?string $keyOrUrl): string
    {
        $keyOrUrl = trim((string) $keyOrUrl);
        if ($keyOrUrl === '') {
            return '';
        }

        // Absolute or double-wrapped remote URL — never rebuild host
        if (
            preg_match('#^https?://#i', $keyOrUrl)
            || str_starts_with($keyOrUrl, '//')
            || preg_match('#^https?://[^/]+/(https?://.+)$#i', $keyOrUrl)
        ) {
            return self::documentFileUrl($keyOrUrl);
        }

        $key = ltrim(str_replace('\\', '/', $keyOrUrl), '/');
        if ($key === '') {
            return '';
        }

        // AWS_URL on s3 disk (path-style / CDN / custom endpoint)
        $configuredBase = rtrim((string) (config('filesystems.disks.s3.url') ?: env('AWS_URL', '')), '/');
        if ($configuredBase !== '') {
            return $configuredBase.'/'.$key;
        }

        try {
            $url = Storage::disk('s3')->url($key);
            if (is_string($url) && trim($url) !== '') {
                return $url;
            }
        } catch (\Throwable $e) {
            // fall through to legacy virtual-hosted form
        }

        $bucket = (string) (config('filesystems.disks.s3.bucket') ?: env('AWS_BUCKET', ''));
        $region = (string) (config('filesystems.disks.s3.region') ?: env('AWS_DEFAULT_REGION', ''));
        if ($bucket !== '' && $region !== '') {
            return 'https://'.$bucket.'.s3.'.$region.'.amazonaws.com/'.$key;
        }

        return self::documentFileUrl($key);
    }

    /**
     * Escaped document URL safe for HTML attributes and single-quoted JS onclick strings.
     */
    public static function documentFileUrlAttr(?string $pathOrUrl): string
    {
        return htmlspecialchars(self::documentFileUrl($pathOrUrl), ENT_QUOTES, 'UTF-8');
    }
}
