<?php

namespace App\Console\Commands;

use App\Helpers\Helper;
use App\Models\ActivitiesLog;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationActivitiesLog;
use App\Models\Note;
use App\Support\TinymceImageS3Migrator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('tinymce-images:migrate-to-s3 {--client= : Client numeric id or client_id reference (e.g. DEMO2308)} {--application= : Application id (resolves that client)} {--all : Process every client (use after a one-client test)} {--dry-run : Report only; do not upload or update HTML}')]
#[Description('Copy local TinyMCE note screenshots to S3 and rewrite note HTML for the related client. Does not change documents or other S3 paths.')]
class TinymceImagesMigrateToS3Command extends Command
{
    public function __construct(private TinymceImageS3Migrator $migrator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $client = $this->resolveClient();
        if ($client === false) {
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? 'Dry run — no S3 uploads or database writes.' : 'Live run — will upload to S3 and update note HTML.');

        $rows = $this->collectDescriptionRows($client);
        $filenames = [];
        foreach ($rows as $row) {
            foreach ($this->migrator->extractFilenames((string) $row['html']) as $name) {
                $filenames[$name] = true;
            }
        }
        $filenames = array_keys($filenames);

        if ($filenames === []) {
            $this->warn('No tinymce-images filenames found in note/activity HTML for this scope.');

            return self::SUCCESS;
        }

        $this->table(
            ['File', 'Local', 'Already on S3'],
            array_map(function (string $name) {
                $key = $this->migrator->s3Key($name);

                return [
                    $name,
                    Storage::disk('public')->exists($key) ? 'yes' : 'missing',
                    Storage::disk('s3')->exists($key) ? 'yes' : 'no',
                ];
            }, $filenames)
        );

        if ($dryRun) {
            $this->info('Dry run complete. Re-run without --dry-run to upload and rewrite HTML.');

            return self::SUCCESS;
        }

        if ($this->input->isInteractive() && ! $this->confirm('Upload missing files to S3 and rewrite matching note HTML?', false)) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        $uploaded = 0;
        $skipped = 0;
        $missing = 0;
        $rewritten = 0;

        foreach ($filenames as $name) {
            $key = $this->migrator->s3Key($name);
            $onS3 = Storage::disk('s3')->exists($key);

            if (! $onS3) {
                if (! Storage::disk('public')->exists($key)) {
                    $this->error("Local file missing, skipped: {$key}");
                    $missing++;

                    continue;
                }

                Storage::disk('s3')->put($key, Storage::disk('public')->get($key));
                $uploaded++;
                $this->line("Uploaded {$key}");
            } else {
                $skipped++;
            }

            $s3Url = Helper::s3ObjectUrl($key);
            if ($s3Url === '') {
                $this->error("Could not build S3 URL for {$key}");

                continue;
            }

            foreach ($rows as $index => $row) {
                $updated = $this->migrator->rewriteStorageUrlsToS3((string) $row['html'], $name, $s3Url);
                if ($updated === $row['html']) {
                    continue;
                }
                $row['model']->description = $updated;
                $row['model']->save();
                $rows[$index]['html'] = $updated;
                $rewritten++;
            }
        }

        $this->info("Done. uploaded={$uploaded} already_on_s3={$skipped} local_missing={$missing} html_rows_updated={$rewritten}");

        return self::SUCCESS;
    }

    /**
     * @return Admin|null|false null = --all, false = error
     */
    private function resolveClient(): Admin|null|false
    {
        $all = (bool) $this->option('all');
        $clientOpt = trim((string) $this->option('client'));
        $applicationOpt = trim((string) $this->option('application'));

        if ($all) {
            if ($clientOpt !== '' || $applicationOpt !== '') {
                $this->error('Do not combine --all with --client or --application.');

                return false;
            }
            $this->warn('Scope: ALL clients.');

            return null;
        }

        if ($clientOpt === '' && $applicationOpt === '') {
            $this->error('Specify --client=ID (or DEMO2308) or --application=ID. Use --all only after a one-client test.');
            $this->line('Example: php artisan tinymce-images:migrate-to-s3 --application=32546 --dry-run');

            return false;
        }

        if ($applicationOpt !== '') {
            $application = Application::find($applicationOpt);
            if (! $application) {
                $this->error("Application not found: {$applicationOpt}");

                return false;
            }
            $client = Admin::find($application->client_id);
            if (! $client) {
                $this->error("Client not found for application {$applicationOpt}.");

                return false;
            }
            $this->info("Scope: application {$application->id} → client {$client->id} ({$client->client_id})");

            return $client;
        }

        $client = ctype_digit($clientOpt)
            ? Admin::find((int) $clientOpt)
            : Admin::where('client_id', $clientOpt)->first();

        if (! $client) {
            $this->error("Client not found: {$clientOpt}");

            return false;
        }

        $this->info("Scope: client {$client->id} ({$client->client_id})");

        return $client;
    }

    /**
     * @return list<array{model: Note|ActivitiesLog|ApplicationActivitiesLog, html: string}>
     */
    private function collectDescriptionRows(?Admin $client): array
    {
        $needle = '%tinymce-images/%';
        $rows = [];

        $notes = Note::query()
            ->where('description', 'like', $needle)
            ->when($client, fn ($q) => $q->where('client_id', $client->id))
            ->get();
        foreach ($notes as $note) {
            $rows[] = ['model' => $note, 'html' => (string) $note->description];
        }

        $activities = ActivitiesLog::query()
            ->where('description', 'like', $needle)
            ->when($client, fn ($q) => $q->where('client_id', $client->id))
            ->get();
        foreach ($activities as $activity) {
            $rows[] = ['model' => $activity, 'html' => (string) $activity->description];
        }

        $appQuery = ApplicationActivitiesLog::query()->where('description', 'like', $needle);
        if ($client) {
            $appIds = Application::where('client_id', $client->id)->pluck('id');
            $appQuery->whereIn('app_id', $appIds);
        }
        foreach ($appQuery->get() as $log) {
            $rows[] = ['model' => $log, 'html' => (string) $log->description];
        }

        return $rows;
    }
}
