<?php

namespace App\Services;

use App\Models\FromEmail;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{

    /**
     * Get the first active email (default for system emails).
     */
    public function getDefaultEmail(): ?FromEmail
    {
        return FromEmail::where('status', true)->orderBy('id')->first();
    }

    /**
     * Resolve From address + display name for outbound mail.
     *
     * Does NOT reconfigure Laravel's mailer transport or global mail config.
     * Callers should use the returned object for ->from(...) and choose SES mailer via
     * SesSenderService::mailerForAddress() (or EmailService::sendEmail).
     *
     * When $emailAddress is null/empty: MAIL_FROM_ADDRESS/NAME, else first active from_emails row.
     * Returns null if no From identity is available.
     *
     * @return object{email: string, display_name?: string}|FromEmail|null
     */
    public function resolveFromEmail(?string $emailAddress = null): ?object
    {
        $emailConfig = null;

        if ($emailAddress && trim($emailAddress) !== '') {
            $trimmed = trim($emailAddress);
            $emailConfig = FromEmail::where('status', true)
                ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower($trimmed)])
                ->first();
        }

        if (! $emailConfig) {
            $envFrom = env('MAIL_FROM_ADDRESS');
            if ($envFrom) {
                return (object) [
                    'email' => $envFrom,
                    'display_name' => env('MAIL_FROM_NAME', $envFrom),
                ];
            }

            return $this->getDefaultEmail();
        }

        return $emailConfig;
    }

    /**
     * @deprecated Use resolveFromEmail() — this never configured the mailer; kept for BC.
     * @return object{email: string, display_name?: string}|FromEmail|null
     */
    public function configureMailerForEmail(?string $emailAddress = null): ?object
    {
        return $this->resolveFromEmail($emailAddress);
    }

    /**
     * From address for document signature emails only (send + reminders).
     * Uses signature_from_email from config / from_emails table — not MAIL_FROM_ADDRESS.
     */
    public function configureMailerForSignature(?string $emailAddress = null): ?object
    {
        $address = ($emailAddress && trim($emailAddress) !== '')
            ? trim($emailAddress)
            : trim((string) config('services.ses_crm.signature_from_email', 'info@bansaleducation.com.au'));

        if ($address === '') {
            return $this->getDefaultEmail();
        }

        return $this->resolveFromEmail($address);
    }

    public function getAllActiveEmails()
    {
        return FromEmail::where('status', true)
            ->select('id', 'email', 'display_name')
            ->get();
    }

    /**
     * Send an email via AWS SES.
     *
     * @throws \Exception
     */
    public function sendEmail(
        $view,
        $data,
        $to,
        $subject,
        $fromEmailAddress,
        $attachments = [],
        $cc = []
    ): void {
        try {
            $trimmed = trim((string) $fromEmailAddress);
            if ($trimmed === '') {
                throw new \Exception('From email address is required.');
            }
            $emailConfig = FromEmail::where('status', true)
                ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower($trimmed)])
                ->first();
            if (! $emailConfig) {
                if (! filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception("Invalid From email address: {$trimmed}");
                }
                $emailConfig = (object) [
                    'email' => $trimmed,
                    'display_name' => $trimmed,
                ];
            }

            Log::info('EmailService - Sending Email via SES', [
                'from_email' => $emailConfig->email,
                'to' => $to,
                'subject' => $subject,
            ]);

            $mailer = app(SesSenderService::class)->mailerForAddress($emailConfig->email);

            Mail::mailer($mailer)->send($view, $data, function (Message $message) use ($to, $subject, $emailConfig, $attachments, $cc) {
                $message->to($to)
                    ->subject($subject)
                    ->from($emailConfig->email, $emailConfig->display_name ?? $emailConfig->email);

                if (! empty($cc)) {
                    $message->cc($cc);
                }

                if (! empty($attachments)) {
                    foreach ($attachments as $attachment) {
                        if (is_string($attachment) && file_exists($attachment)) {
                            $message->attach($attachment);
                        }
                    }
                }
            });

            Log::info('EmailService - Email Sent Successfully', [
                'from' => $emailConfig->email,
                'to' => $to,
            ]);
        } catch (\Exception $e) {
            Log::error('EmailService - Send Failed', [
                'error' => $e->getMessage(),
                'from_email' => $fromEmailAddress ?? 'unknown',
                'to' => $to ?? 'unknown',
            ]);
            throw new \Exception('Email could not be sent: '.$e->getMessage());
        }
    }
}
