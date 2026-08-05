<?php

namespace App\Http\Controllers\Elite;

use App\Http\Controllers\Controller;
use App\Models\EliteEmail;
use App\Models\EliteEmailAttachment;
use App\Models\Email;
use App\Models\OutlookDraftEmail;
use App\Services\EducationEliteInboxService;
use App\Services\EliteInboundAttachmentService;
use Illuminate\Support\Facades\Artisan;
use App\Support\EducationEliteMail;
use App\Support\EliteEmailCidRewriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EliteEmailController extends Controller
{
    /**
     * Serve a stored inbound attachment (SES-imported or legacy webhook multipart).
     */
    public function attachment(EliteEmailAttachment $attachment): StreamedResponse
    {
        $disk = EliteInboundAttachmentService::findDiskForPath($attachment->storage_path);
        if ($disk === null) {
            abort(404);
        }

        $filename = $attachment->original_filename ?: 'attachment';
        $filename = str_replace(["\0", "\r", "\n", '"'], '', basename($filename));
        if ($filename === '') {
            $filename = 'attachment';
        }

        $mime = is_string($attachment->mime_type) && $attachment->mime_type !== ''
            ? $attachment->mime_type
            : 'application/octet-stream';
        $disposition = str_starts_with(strtolower($mime), 'image/') ? 'inline' : 'attachment';

        return $disk->response(
            $attachment->storage_path,
            $filename,
            [
                'Content-Type' => $mime,
            ],
            $disposition
        );
    }

    /**
     * Lazy-load HTML body for rows stored in object storage (admin JSON).
     */
    public function messageBody(EliteEmail $eliteEmail): JsonResponse
    {
        $html = '';

        if (Schema::hasTable('elite_emails') && Schema::hasColumn('elite_emails', 'body_html_s3_key')) {
            $key = is_string($eliteEmail->body_html_s3_key) ? trim($eliteEmail->body_html_s3_key) : '';
            if ($key !== '') {
                $disk = EliteInboundAttachmentService::findDiskForPath($key);
                if ($disk !== null) {
                    try {
                        $html = (string) $disk->get($key);
                    } catch (\Throwable $e) {
                        Log::warning('elite.inbound.body_s3_read_failed', [
                            'elite_email_id' => $eliteEmail->id,
                            'key' => $key,
                            'message' => $e->getMessage(),
                        ]);
                    }
                }
            }
        }

        if ($html === '' && is_string($eliteEmail->body_html) && $eliteEmail->body_html !== '') {
            $html = $eliteEmail->body_html;
        }

        if ($html === '' && is_string($eliteEmail->body_text) && $eliteEmail->body_text !== '') {
            $html = '<pre style="white-space:pre-wrap;margin:0">'.e($eliteEmail->body_text).'</pre>';
        }

        $atts = $eliteEmail->attachments()->get();
        $html = EliteEmailCidRewriter::rewrite($html, $atts);

        return response()->json(['body' => $html]);
    }

    public function index()
    {
        $service = EducationEliteInboxService::make();
        $items = $service->getInbox('', '', '', 'newest', 200, 'inbox', null);

        $webhookUrl = $this->inboundWebhookUrl();

        return view('elite.emails-inbox', [
            'eliteInboxItems' => $items,
            'eliteInitialFolder' => 'inbox',
            'eliteInitialAccount' => 'all',
            'eliteMailboxes' => $service->listMailboxes(),
            'webhookUrl' => $webhookUrl,
            'sesInboundBucket' => config('filesystems.disks.s3_inbound.bucket'),
            'sesInboundPrefix' => config('filesystems.disks.s3_inbound.root'),
        ]);
    }

    /**
     * JSON list for the Elite inbox: SES inbound sync (`elite_emails`) plus
     * optional CRM inbound (emails, mail_type 0) for the same domain.
     * Folder is always treated as 'inbox'; 'sent' returns [] in the service.
     */
    public function inbox(Request $request)
    {
        if ($request->boolean('sync')) {
            Artisan::call('ses:sync-inbound', ['--limit' => 50]);
        }

        $search = trim((string) $request->get('search', ''));
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');
        $sort = $request->get('sort', 'newest');
        if (! in_array($sort, ['newest', 'oldest'], true)) {
            $sort = 'newest';
        }
        $accountRaw = (string) $request->get('account', 'all');
        $account = ($accountRaw === 'all' || $accountRaw === '') ? null : $accountRaw;

        $service = EducationEliteInboxService::make();
        $emails = $service->getInbox($search, $dateFrom, $dateTo, $sort, 500, 'inbox', $account);
        $normalized = $service->normalizeAccountFilter($account);

        return response()->json([
            'emails' => $emails,
            'folder' => 'inbox',
            'account' => $normalized ?? 'all',
            'accounts' => $service->listMailboxes(),
            'message' => $service->emptyListMessage('inbox'),
        ]);
    }

    /**
     * JSON list of sent emails whose from_mail is an @elite-domain address.
     */
    public function sent(Request $request): JsonResponse
    {
        $domain   = ltrim(strtolower((string) config('crm.education_elite_sender_domain', 'educationelite.com.au')), '@');
        $like     = '%@'.$domain;
        $search   = trim((string) $request->get('search', ''));
        $dateFrom = (string) $request->get('date_from', '');
        $dateTo   = (string) $request->get('date_to', '');
        $sort     = in_array($request->get('sort', 'newest'), ['oldest'], true) ? 'asc' : 'desc';

        $query = Email::query()
            ->where('mail_type', 1)
            ->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(TRIM(COALESCE(from_mail,\'\'))) LIKE ?', [strtolower($like)])
                  ->orWhereRaw('LOWER(TRIM(COALESCE(to_mail,\'\'))) LIKE ?', [strtolower($like)]);
            });

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('from_mail', 'like', '%'.$search.'%')
                  ->orWhere('to_mail', 'like', '%'.$search.'%')
                  ->orWhere('subject', 'like', '%'.$search.'%');
            });
        }
        if ($dateFrom !== '') $query->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo   !== '') $query->whereDate('created_at', '<=', $dateTo);
        $query->orderBy('created_at', $sort);

        $emails = [];
        foreach ($query->get() as $row) {
            $emails[] = [
                'id'         => $row->id,
                'from'       => $row->from_mail,
                'to'         => $row->to_mail,
                'cc'         => $row->cc,
                'subject'    => $row->subject ?: '(No subject)',
                'body'       => $row->message ?? '',
                'date'       => $row->created_at->format('d/m/Y g:i A'),
                'date_short' => $row->created_at->format('g:i A'),
            ];
        }

        // Group by from_mail (like Outlook sent view)
        $byFrom = [];
        foreach ($emails as $e) {
            $key = $e['from'] ?? '(unknown)';
            if (! isset($byFrom[$key])) {
                $byFrom[$key] = ['from_email' => $key, 'emails' => []];
            }
            $byFrom[$key]['emails'][] = $e;
        }

        return response()->json([
            'emails'      => $emails,
            'sent_groups' => array_values($byFrom),
            'message'     => count($emails) === 0
                ? 'No sent mail from @'.$domain.' yet.'
                : '',
        ]);
    }

    /**
     * JSON list of draft emails whose from_email is an @elite-domain address.
     */
    public function drafts(Request $request): JsonResponse
    {
        $domain = ltrim(strtolower((string) config('crm.education_elite_sender_domain', 'educationelite.com.au')), '@');
        $like   = '%@'.$domain;
        $search = trim((string) $request->get('search', ''));

        $adminId = Auth::guard('admin')->id();
        if ($adminId === null) {
            return response()->json([
                'emails' => [],
                'message' => 'Sign in as an admin to view drafts.',
            ]);
        }

        $query = OutlookDraftEmail::query()
            ->where('admin_id', $adminId)
            ->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(TRIM(COALESCE(from_email,\'\'))) LIKE ?', [strtolower($like)])
                  ->orWhereRaw('LOWER(TRIM(COALESCE(to_email,\'\'))) LIKE ?', [strtolower($like)]);
            })
            ->orderBy('updated_at', 'desc');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('from_email', 'like', '%'.$search.'%')
                  ->orWhere('to_email', 'like', '%'.$search.'%')
                  ->orWhere('subject', 'like', '%'.$search.'%');
            });
        }

        $drafts = [];
        foreach ($query->get() as $d) {
            $drafts[] = [
                'id'      => $d->id,
                'from'    => $d->from_email,
                'to'      => $d->to_email,
                'cc'      => $d->cc,
                'subject' => $d->subject ?: '(No subject)',
                'body'    => $d->body ?? '',
                'date'    => $d->updated_at->format('d/m/Y g:i A'),
            ];
        }

        return response()->json([
            'emails'  => $drafts,
            'message' => count($drafts) === 0 ? 'No drafts saved for @'.$domain.' yet.' : '',
        ]);
    }

    /**
     * Legacy inbound webhook (multipart, no CSRF). Primary inbound is SES → S3 via ses:sync-inbound.
     */
    public function store(Request $request): JsonResponse
    {
        if ($this->inboundDebugLogging()) {
            $request->attributes->set('elite_inbound_cid', bin2hex(random_bytes(8)));
            Log::info('elite.inbound.hit', array_merge($this->inboundCorrelationContext($request), [
                'method' => $request->method(),
                'path' => $request->path(),
                'content_length' => $request->header('Content-Length'),
                'user_agent' => $request->userAgent() !== null ? substr((string) $request->userAgent(), 0, 200) : null,
            ]));
            $this->logInboundWebhookRequest($request, 'webhook_post');
        }

        try {
            $this->assertInboundSecret($request);

            if ($this->inboundDebugLogging()) {
                Log::info('elite.inbound.auth_ok', array_merge($this->inboundCorrelationContext($request), [
                    'secret_configured' => (string) config('crm.education_elite_inbound_secret', '') !== '',
                ]));
            }

            $response = $this->persistInbound($request);
            if ($this->inboundDebugLogging()) {
                $this->logInboundResponse($request, $response);
            }

            return $response;
        } catch (\Throwable $e) {
            if (! $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                Log::error('elite.inbound.unhandled_exception', array_merge($this->inboundCorrelationContext($request), [
                    'message' => $e->getMessage(),
                    'exception' => $e::class,
                    'file' => $e->getFile().':'.$e->getLine(),
                ]));
            }
            throw $e;
        }
    }

    private function inboundDebugLogging(): bool
    {
        return (bool) config('crm.education_elite_inbound_webhook_log', true);
    }

    /**
     * @return array<string, mixed>
     */
    private function inboundCorrelationContext(Request $request): array
    {
        $cid = $request->attributes->get('elite_inbound_cid');

        return $cid !== null ? ['cid' => $cid, 'ip' => $request->ip()] : ['ip' => $request->ip()];
    }

    private function logInboundResponse(Request $request, JsonResponse $response): void
    {
        $data = $response->getData(true);
        $bodyKeys = is_array($data) ? array_keys($data) : [];
        $log = array_merge($this->inboundCorrelationContext($request), [
            'http_status' => $response->getStatusCode(),
            'body_keys' => $bodyKeys,
            'ok' => is_array($data) && array_key_exists('ok', $data) ? $data['ok'] : null,
            'id' => is_array($data) && array_key_exists('id', $data) ? $data['id'] : null,
            'error' => is_array($data) && isset($data['error'])
                ? $this->stringPreview((string) $data['error'], 200)
                : null,
        ]);
        Log::info('elite.inbound.response', $log);
    }

    /**
     * Safe diagnostics for /elite/emails POST (no full MIME bodies; lengths + short previews).
     * Enable/disable via config crm.education_elite_inbound_webhook_log.
     */
    private function logInboundWebhookRequest(Request $request, string $stage): void
    {
        $keys = array_keys($request->all());
        sort($keys);
        $lengths = [];
        foreach (['text', 'html', 'email', 'headers', 'dkim', 'from', 'to', 'subject', 'envelope', 'raw'] as $k) {
            $v = $request->input($k);
            if (is_string($v)) {
                $lengths[$k.'_bytes'] = strlen($v);
            } elseif (is_array($v)) {
                $lengths[$k.'_count'] = count($v);
            }
        }
        $previews = [
            'from' => $this->stringPreview($request->input('from'), 220),
            'to' => $this->stringPreview($request->input('to') ?? $request->input('recipient'), 220),
            'subject' => $this->stringPreview($request->input('subject') ?? $request->input('Subject'), 300),
            'envelope' => $this->stringPreview(
                is_string($request->input('envelope')) ? $request->input('envelope') : null,
                500
            ),
        ];
        Log::info('elite.inbound', array_merge($this->inboundCorrelationContext($request), [
            'stage' => $stage,
            'content_type' => (string) $request->header('Content-Type'),
            'user_agent' => $request->userAgent() !== null ? substr($request->userAgent(), 0, 200) : null,
            'form_keys' => $keys,
            'field_sizes' => $lengths,
            'previews' => array_filter($previews, static fn ($v) => $v !== null && $v !== ''),
        ]));
    }

    private function stringPreview(mixed $value, int $max = 200): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }
        $s = str_replace(["\r\n", "\r", "\n"], ' ', $value);
        if (strlen($s) > $max) {
            return substr($s, 0, $max).'…';
        }

        return $s;
    }

    private function assertInboundSecret(Request $request): void
    {
        $secret = (string) config('crm.education_elite_inbound_secret', '');
        if ($secret === '') {
            return;
        }

        $given = (string) ($request->query('secret', $request->header('X-Elite-Webhook-Secret', '')));

        if (! hash_equals($secret, $given)) {
            // 403 is logged by LogEliteInboundWebhookRejections (incl. query/header presence; no secrets logged).
            abort(403, 'Invalid inbound secret');
        }
    }

    private function persistInbound(Request $request): JsonResponse
    {
        $fromAddress = $this->resolveFromAddress($request);
        $fromRaw = $request->input('from')
            ?? $request->input('sender')
            ?? $request->input('envelope');

        $subject = $request->input('subject') ?? $request->input('Subject');
        $toRaw = $request->input('to') ?? $request->input('recipient') ?? $request->input('To');

        // Validate that at least one participant (To or From) is an Elite-domain address.
        // Inbound mail arrives FROM external senders TO @educationelite.com.au — we check
        // the To field. Some CRM-originated rows have the Elite address in From; we check both.
        // Some inbound providers / clients (e.g. Outlook-thread replies) may send `to` as an array, only
        // in envelope JSON, or only in raw headers — collect all recipients before rejecting.
        $toAddresses = $this->collectInboundRecipientAddresses($request, $toRaw);
        $eliteTo = false;
        foreach ($toAddresses as $addr) {
            if (EducationEliteMail::isEliteOwnedAddress($addr)) {
                $eliteTo = true;
                break;
            }
        }
        $eliteToAddr = EducationEliteMail::preferApexMailbox($toAddresses);
        $eliteFrom = $fromAddress && EducationEliteMail::isEliteOwnedAddress($fromAddress);

        if ($this->inboundDebugLogging()) {
            Log::info('elite.inbound.parsed', array_merge($this->inboundCorrelationContext($request), [
                'apex' => EducationEliteMail::apexDomain(),
                'from_parsed' => $fromAddress,
                'to_addresses' => $toAddresses,
                'elite_to' => $eliteTo,
                'elite_from' => $eliteFrom,
            ]));
        }

        if (! $eliteTo && ! $eliteFrom) {
            // Parsed addresses but none Elite → reject (no longer force-save non-Elite mail).
            // Empty from + empty to still saves — provider parse gaps must not drop real Elite mail.
            $hasResolvedParticipant = ($fromAddress !== null && $fromAddress !== '')
                || (is_array($toAddresses) && $toAddresses !== []);

            Log::warning('elite.inbound.rejected', array_merge($this->inboundCorrelationContext($request), [
                'from' => $fromAddress,
                'to' => $toAddresses,
                'action' => $hasResolvedParticipant ? 'http_422' : 'save_ambiguous',
            ]));

            if ($hasResolvedParticipant) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Not an Education Elite recipient or sender',
                ], 422);
            }
        }
        $text = $request->input('text') ?? $request->input('body_text') ?? $request->input('plain');
        $html = $request->input('html') ?? $request->input('body_html') ?? $request->input('body');

        if (is_string($html) && $this->looksLikePlainText($html) && $text === null) {
            $text = $html;
            $html = null;
        }

        $payload = $this->compactPayload($request);

        // Fall back to the raw From string if the strict parser couldn't extract an address
        $storedFrom = $fromAddress ?? (is_string($fromRaw) ? substr(trim($fromRaw), 0, 255) : null);

        $storedTo = $this->formatStoredToAddress($eliteToAddr, $toAddresses, $toRaw);

        try {
            $record = EliteEmail::create([
                'from_address' => $storedFrom,
                'to_address' => $storedTo,
                'subject' => is_string($subject) ? substr($subject, 0, 998) : null,
                'body_text' => is_string($text) ? $text : null,
                'body_html' => is_string($html) ? $html : null,
                'payload' => $payload,
            ]);
        } catch (\Throwable $e) {
            Log::error('elite.inbound.db_failed', array_merge($this->inboundCorrelationContext($request), [
                'message' => $e->getMessage(),
                'exception' => $e::class,
                'from' => $storedFrom,
                'to' => $storedTo,
            ]));
            throw $e;
        }

        try {
            $this->offloadInboundBodyHtmlToObjectStorage($record);
        } catch (\Throwable $e) {
            Log::warning('elite.inbound.body_s3_offload_failed', array_merge($this->inboundCorrelationContext($request), [
                'elite_email_id' => $record->id,
                'message' => $e->getMessage(),
            ]));
        }

        Log::info('elite.inbound.stored', array_merge($this->inboundCorrelationContext($request), [
            'id' => $record->id,
            'from' => $storedFrom,
            'to' => $storedTo,
        ]));

        try {
            app(EliteInboundAttachmentService::class)->storeFromInboundRequest($record, $request);
        } catch (\Throwable $e) {
            Log::warning('elite.inbound.attachments_store_failed', array_merge($this->inboundCorrelationContext($request), [
                'elite_email_id' => $record->id,
                'message' => $e->getMessage(),
            ]));
        }

        return response()->json(['ok' => true, 'id' => $record->id]);
    }

    /**
     * Normalised list of recipient addresses (lowercase) for inbound validation and storage.
     * Covers legacy inbound webhook payloads plus clients that omit top-level `to` or use envelope/headers only.
     *
     * @return list<string>
     */
    private function collectInboundRecipientAddresses(Request $request, mixed $toPrimary): array
    {
        $seen = [];
        $add = function (?string $parsed) use (&$seen): void {
            if ($parsed !== null && $parsed !== '' && ! isset($seen[$parsed])) {
                $seen[$parsed] = true;
            }
        };

        foreach ($this->flattenInboundAddressInputs($toPrimary) as $chunk) {
            $p = $this->parseEmailAddress($chunk);
            if ($p) {
                $add($p);
            }
        }

        foreach (['recipient', 'To', 'Recipients', 'recipients'] as $key) {
            foreach ($this->flattenInboundAddressInputs($request->input($key)) as $chunk) {
                $p = $this->parseEmailAddress($chunk);
                if ($p) {
                    $add($p);
                }
            }
        }

        $envelope = $request->input('envelope');
        if (is_string($envelope)) {
            $decoded = json_decode($envelope, true);
            $envelope = is_array($decoded) ? $decoded : null;
        }
        if (is_array($envelope) && isset($envelope['to'])) {
            foreach ($this->flattenInboundAddressInputs($envelope['to']) as $chunk) {
                $p = $this->parseEmailAddress($chunk);
                if ($p) {
                    $add($p);
                }
            }
        }

        $headers = $request->input('headers');
        if (is_string($headers) && $headers !== '') {
            foreach ($this->parseRecipientEmailsFromRawHeaders($headers) as $p) {
                $add($p);
            }
        }

        return array_keys($seen);
    }

    /**
     * @param  list<string>  $parsedRecipients
     */
    private function formatStoredToAddress(?string $eliteToAddr, array $parsedRecipients, mixed $toRaw): ?string
    {
        if ($eliteToAddr !== null) {
            return substr($eliteToAddr, 0, 255);
        }
        if ($parsedRecipients !== []) {
            return substr(implode(', ', $parsedRecipients), 0, 255);
        }
        if (is_string($toRaw) && trim($toRaw) !== '') {
            return substr(trim($toRaw), 0, 255);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function flattenInboundAddressInputs(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            $out = [];
            foreach ($value as $item) {
                foreach ($this->flattenInboundAddressInputs($item) as $s) {
                    $out[] = $s;
                }
            }

            return $out;
        }
        $s = trim((string) $value);
        if ($s === '') {
            return [];
        }
        $parts = preg_split('/\s*,\s*/', $s) ?: [];

        return array_values(array_filter(array_map('trim', $parts), static fn (string $p): bool => $p !== ''));
    }

    /**
     * Extract mailbox addresses from raw RFC822 headers (inbound webhook `headers` field).
     *
     * @return list<string> Normalised lowercase emails
     */
    private function parseRecipientEmailsFromRawHeaders(string $headers): array
    {
        $out = [];
        // Standard + Microsoft 365 / Outlook forwards (OriginalRecipients, Resent-To, etc.)
        $headerNames = [
            'delivered-to',
            'envelope-to',
            'x-original-to',
            'to',
            'x-forwarded-to',
            'resent-to',
            'x-ms-exchange-organization-originalrecipients',
        ];
        foreach ($headerNames as $name) {
            $qn = preg_quote($name, '/');
            if (preg_match_all('/^'.$qn.':\s*(.+?)(?=\r?\n[^\t ]|\r?\n*$)/msi', $headers, $blocks)) {
                foreach ($blocks[1] as $block) {
                    $block = trim(preg_replace("/\r?\n[\t ]+/", ' ', $block) ?? '');
                    foreach ($this->expandHeaderAddressBlock($block) as $chunk) {
                        $p = $this->parseEmailAddress($chunk);
                        if ($p) {
                            $out[$p] = true;
                        }
                    }
                }
            }
        }

        return array_keys($out);
    }

    /**
     * Split M365-style recipient lists (semicolons, SMTP:user@host tokens) before normal parsing.
     *
     * @return list<string>
     */
    private function expandHeaderAddressBlock(string $block): array
    {
        if ($block === '') {
            return [];
        }
        $segments = preg_split('/\s*;\s*/', $block) ?: [$block];
        $chunks = [];
        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }
            $segment = preg_replace('/^smtp:\\s*/i', '', $segment);
            $segment = trim($segment);
            foreach ($this->flattenInboundAddressInputs($segment) as $c) {
                $chunks[] = $c;
            }
        }

        return $chunks;
    }

    /**
     * Legacy webhook providers send `envelope` as a JSON string: {"to":["…"],"from":"…"}.
     */
    private function resolveFromAddress(Request $request): ?string
    {
        $headerCandidates = [
            $request->input('from'),
            $request->input('sender'),
            $request->input('from_email'),
            $request->input('From'),
        ];

        foreach ($headerCandidates as $c) {
            if ($c !== null && $c !== '') {
                $parsed = $this->parseEmailAddress((string) $c);
                if ($parsed) {
                    return $parsed;
                }
            }
        }

        $envelope = $request->input('envelope');
        if (is_string($envelope)) {
            $decoded = json_decode($envelope, true);
            $envelope = is_array($decoded) ? $decoded : null;
        }
        if (is_array($envelope) && ! empty($envelope['from'])) {
            return $this->parseEmailAddress((string) $envelope['from']);
        }

        return null;
    }

    /**
     * Store HTML body on the configured disk and clear DB column; on failure leaves body_html in DB.
     */
    private function offloadInboundBodyHtmlToObjectStorage(EliteEmail $record): void
    {
        if (! Schema::hasTable('elite_emails') || ! Schema::hasColumn('elite_emails', 'body_html_s3_key')) {
            return;
        }

        $html = $record->body_html;
        if (! is_string($html) || $html === '') {
            return;
        }

        $diskName = EliteInboundAttachmentService::writeDisk();
        $key = 'elite-inbound/'.$record->id.'/body.html';

        Storage::disk($diskName)->put($key, $html, ['visibility' => 'private']);

        $updates = [
            'body_html_s3_key' => $key,
            'body_html' => null,
        ];

        $existingText = $record->body_text;
        if (! is_string($existingText) || trim($existingText) === '') {
            $plain = trim(strip_tags($html));
            if (strlen($plain) > 60000) {
                $plain = substr($plain, 0, 60000);
            }
            $updates['body_text'] = $plain !== '' ? $plain : null;
        }

        $record->update($updates);
    }

    /**
     * Drop huge inbound webhook fields (raw MIME) from stored JSON; keep metadata only.
     */
    private function compactPayload(Request $request): ?array
    {
        $payload = $request->except(['_token']);
        foreach (['email', 'html', 'text', 'content-id_map'] as $key) {
            unset($payload[$key]);
        }

        foreach (array_keys($payload) as $key) {
            if (! is_string($key)) {
                continue;
            }
            if ($payload[$key] instanceof \Illuminate\Http\UploadedFile) {
                unset($payload[$key]);
                continue;
            }
            if ($key === 'attachment' || preg_match('/^attachment\d+$/i', $key)) {
                unset($payload[$key]);
            }
        }

        if (count($payload) > 60) {
            $payload = array_slice($payload, 0, 60, true);
        }

        return $payload === [] ? null : $payload;
    }

    private function looksLikePlainText(string $html): bool
    {
        return ! preg_match('/<[a-z][\s\S]*>/i', $html);
    }

    private function inboundWebhookUrl(): string
    {
        $url = route('elite.emails.store', [], true);
        $secret = (string) config('crm.education_elite_inbound_secret', '');
        if ($secret !== '') {
            $url .= '?secret='.urlencode($secret);
        }

        return $url;
    }

    private function parseEmailAddress(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        if (preg_match('/<([^>]+@[^>]+)>/', $raw, $m)) {
            $raw = trim($m[1]);
        }
        $raw = strtolower($raw);
        if (! filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $raw;
    }
}
