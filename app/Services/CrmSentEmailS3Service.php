<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Email;
use App\Models\MailReportAttachment;
use App\Models\Admin;
use App\Models\Partner;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Stores CRM-sent emails to AWS S3 (full HTML snapshot + attachments).
 * Enables consistent archival with uploaded .msg emails and attachment download in the Email tab.
 */
class CrmSentEmailS3Service
{
    /**
     * Store a CRM-sent email to S3 and create Document + MailReportAttachment records.
     *
     * @param Email $mailReport
     * @param string $subject
     * @param string $messageHtml
     * @param array $attachmentPaths Array of local file paths (or ['path' => ..., 'name' => ...])
     * @return bool Success
     */
    public function storeToS3(Email $mailReport, string $subject, string $messageHtml, array $attachmentPaths = []): bool
    {
        try {
            $clientId = $mailReport->client_id;
            if ($clientId === null || $clientId === '' || ! is_numeric($clientId) || (int) $clientId <= 0) {
                Log::warning('CrmSentEmailS3Service: No client_id, skipping S3 storage (email still delivered)', [
                    'mail_report_id' => $mailReport->id,
                    'type' => $mailReport->type,
                    'from_mail' => $mailReport->from_mail,
                    'to_mail' => $mailReport->to_mail,
                ]);
                return false;
            }
            $clientId = (int) $clientId;

            if (!config('filesystems.disks.s3.key') || !config('filesystems.disks.s3.bucket')) {
                Log::info('CrmSentEmailS3Service: S3 not configured, skipping storage');
                return false;
            }

            $entityType = $mailReport->type ?? 'client';
            $clientUniqueId = $this->resolveClientUniqueId($clientId, $entityType);
            $sanitizedClientId = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $clientUniqueId);

            // HTML snapshot — isolated so failure does not block attachment archival
            try {
                $docType = 'crm_sent';
                $uniqueFileName = time() . '-' . substr(uniqid(), -6) . '-email.html';
                $s3Path = $sanitizedClientId . '/' . $docType . '/sent/' . $uniqueFileName;
                $htmlContent = $this->buildEmailHtml($mailReport, $subject, $messageHtml);

                $uploaded = Storage::disk('s3')->put($s3Path, $htmlContent);
                if (!$uploaded) {
                    throw new \Exception('S3 put returned false');
                }
                $fileUrl = Storage::disk('s3')->url($s3Path);

                $document = new Document();
                $document->file_name = 'email-' . $mailReport->id;
                $document->filetype = 'html';
                $document->user_id = $mailReport->user_id;
                $document->myfile = $fileUrl;
                $document->myfile_key = $uniqueFileName;
                $document->client_id = $clientId;
                $document->type = $entityType;
                $document->mail_type = 'sent';
                $document->file_size = strlen($htmlContent);
                $document->doc_type = $docType;
                $document->save();

                $mailReport->uploaded_doc_id = $document->id;
                $mailReport->save();
            } catch (\Exception $e) {
                Log::error('CrmSentEmailS3Service: HTML snapshot upload failed', [
                    'mail_report_id' => $mailReport->id,
                    'error' => $e->getMessage(),
                ]);
                // Continue — attachment archival must still proceed
            }

            // Always archive attachments regardless of whether the HTML snapshot succeeded
            foreach ($attachmentPaths as $pathOrTuple) {
                $filePath = is_array($pathOrTuple) ? ($pathOrTuple['path'] ?? null) : $pathOrTuple;
                $displayName = is_array($pathOrTuple) ? ($pathOrTuple['name'] ?? basename($filePath)) : basename($filePath);
                if ($filePath && file_exists($filePath)) {
                    $this->uploadAttachmentToS3($mailReport->id, $filePath, $displayName, $clientUniqueId);
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('CrmSentEmailS3Service: storeToS3 failed', [
                'mail_report_id' => $mailReport->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    protected function resolveClientUniqueId($entityId, string $entityType): string
    {
        if ($entityType === 'partner') {
            $partner = Partner::find($entityId);
            return $partner ? (string) $partner->id : ('partner_' . $entityId);
        }
        $admin = Admin::withoutGlobalScopes()->find($entityId);
        return ($admin && !empty($admin->client_id)) ? $admin->client_id : ('client_' . $entityId);
    }

    /**
     * Build full HTML document for the email archive (not the live SES payload).
     */
    protected function buildEmailHtml(Email $mailReport, string $subject, string $messageHtml): string
    {
        $from = htmlspecialchars($mailReport->from_mail ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $to = htmlspecialchars($mailReport->to_mail ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $date = $mailReport->created_at ? $mailReport->created_at->format('d/m/Y h:i a') : date('d/m/Y h:i a');
        $subjectEscaped = htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $body = $this->sanitizeArchiveMessageHtml($messageHtml);

        // CSP limits damage if residual markup is opened as a top-level HTML document in-browser.
        $csp = "default-src 'none'; img-src https: http: data:; style-src 'unsafe-inline'; font-src data:; base-uri 'none'; form-action 'none'; frame-ancestors 'none'";

        return '<!DOCTYPE html><html><head><meta charset="UTF-8">'
            . '<meta http-equiv="Content-Security-Policy" content="' . htmlspecialchars($csp, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">'
            . '<title>' . $subjectEscaped . '</title></head><body>'
            . '<div style="font-family:Arial,sans-serif;max-width:800px;">'
            . '<p><strong>From:</strong> ' . $from . '</p>'
            . '<p><strong>To:</strong> ' . $to . '</p>'
            . '<p><strong>Date:</strong> ' . $date . '</p>'
            . '<p><strong>Subject:</strong> ' . $subjectEscaped . '</p>'
            . '<hr>'
            . '<div class="crm-sent-body">' . $body . '</div>'
            . '</div></body></html>';
    }

    /**
     * Strip high-risk constructs from compose HTML before storing the archive snapshot.
     * Keeps normal rich formatting (p, a, tables, images, styles) for Email-tab readability.
     * Does not alter the body actually sent via SES.
     */
    protected function sanitizeArchiveMessageHtml(string $messageHtml): string
    {
        if ($messageHtml === '') {
            return '';
        }

        $html = $messageHtml;

        // Drop script / iframe / object / embed / form blocks and bare tags
        $blockTags = 'script|iframe|object|embed|form|base|link|meta';
        $html = preg_replace('#<(' . $blockTags . ')\b[^>]*>[\s\S]*?</\1\s*>#iu', '', $html) ?? $html;
        $html = preg_replace('#<(' . $blockTags . ')\b[^>]*/?>#iu', '', $html) ?? $html;

        // Inline event handlers: onclick="...", onerror=...
        $html = preg_replace('/\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $html) ?? $html;

        // javascript: / vbscript: / data:text/html URLs in common attributes
        $html = preg_replace(
            '/\b(href|src|xlink:href|action|formaction|poster)\s*=\s*(["\'])\s*(?:javascript|vbscript|data\s*:\s*text\/html)[^"\']*\2/iu',
            '$1=$2#$2',
            $html
        ) ?? $html;
        $html = preg_replace(
            '/\b(href|src|xlink:href|action|formaction|poster)\s*=\s*(?:javascript|vbscript|data\s*:\s*text\/html)[^\s>]*/iu',
            '$1="#"',
            $html
        ) ?? $html;

        // <meta http-equiv=refresh> (already stripped as meta; belt-and-suspenders)
        $html = preg_replace('/<meta\b[^>]*http-equiv\s*=\s*["\']?\s*refresh[^>]*>/iu', '', $html) ?? $html;

        return $html;
    }

    /**
     * Upload a single attachment to S3 and create MailReportAttachment record.
     */
    protected function uploadAttachmentToS3(int $mailReportId, string $localPath, string $displayName, string $clientUniqueId): void
    {
        try {
            $content = file_get_contents($localPath);
            if ($content === false) {
                Log::warning('CrmSentEmailS3Service: Could not read attachment', ['path' => $localPath]);
                return;
            }

            $sanitized = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $displayName);
            $sanitized = preg_replace('/_+/', '_', trim($sanitized, '_')) ?: 'attachment';
            $s3Key = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $clientUniqueId) . '/attachments/' . time() . '_' . substr(uniqid(), -6) . '_' . $sanitized;

            $uploaded = Storage::disk('s3')->put($s3Key, $content);
            $s3Path = $uploaded ? Storage::disk('s3')->url($s3Key) : null;

            $contentType = $this->guessContentType($displayName);

            MailReportAttachment::create([
                'mail_report_id' => $mailReportId,
                'filename' => $displayName,
                'display_name' => $displayName,
                'content_type' => $contentType,
                'file_path' => $s3Path,
                's3_key' => $s3Key,
                'file_size' => strlen($content),
                'is_inline' => false,
                'extension' => pathinfo($displayName, PATHINFO_EXTENSION),
            ]);
        } catch (\Exception $e) {
            Log::warning('CrmSentEmailS3Service: Attachment upload failed', [
                'path' => $localPath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function guessContentType(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $map = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        return $map[$ext] ?? 'application/octet-stream';
    }
}
