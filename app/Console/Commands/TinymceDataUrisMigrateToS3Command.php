<?php

namespace App\Console\Commands;

use App\Models\ActivitiesLog;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationActivitiesLog;
use App\Models\Note;
use App\Models\Partner;
use App\Support\TinymceImageS3Migrator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tinymce-images:migrate-data-uris-to-s3 {--client= : Client numeric id or client_id reference (e.g. DEMO2308)} {--partner= : Partner numeric id} {--application= : Application id} {--note= : Single notes.id} {--all : Process every matching row} {--dry-run : Report only; do not upload or update HTML}')]
#[Description('Upload inlined data:image TinyMCE screenshots from note HTML to S3 and rewrite src to the S3 object URL. Does not change documents or other S3 paths. View image stays signed at display time.')]
class TinymceDataUrisMigrateToS3Command extends Command
{
    public function __construct(private TinymceImageS3Migrator $migrator)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $scope = $this->resolveScope();
        if ($scope === false) {
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? 'Dry run — no S3 uploads or database writes.' : 'Live run — will upload data: images to S3 and rewrite matching HTML.');

        $summaries = $this->collectRowSummaries($scope);
        if ($summaries === []) {
            $this->warn('No data:image notes found in this scope.');

            return self::SUCCESS;
        }

        $preview = array_slice($summaries, 0, 50);
        $this->table(['Table', 'ID', 'Type/client_id'], $preview);
        if (count($summaries) > 50) {
            $this->info('... and '.(count($summaries) - 50).' more rows.');
        }

        if ($dryRun) {
            $this->info('Dry run complete ('.count($summaries).' rows). Re-run without --dry-run to upload and rewrite HTML.');

            return self::SUCCESS;
        }

        if ($this->input->isInteractive() && ! $this->confirm('Upload data: images to S3 and rewrite those note HTML rows only?', false)) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        $rewritten = 0;
        $unchanged = 0;
        $stillInline = 0;

        foreach ($this->eachRow($scope) as $model) {
            $html = (string) $model->description;
            $updated = $this->migrator->replaceDataUrisWithS3($html);
            if ($updated === $html) {
                $unchanged++;
                if ($this->migrator->countDataUris($html) > 0) {
                    $stillInline++;
                    $this->warn('Left inline (over 2MB or invalid): '.$model->getTable().'#'.$model->id);
                }

                continue;
            }

            $model->description = $updated;
            $model->save();
            $rewritten++;
            $this->line('Updated '.$model->getTable().'#'.$model->id);

            if ($this->migrator->countDataUris($updated) > 0) {
                $stillInline++;
                $this->warn('Partially updated, leftover data:image: '.$model->getTable().'#'.$model->id);
            }
        }

        $this->info("Done. rows_updated={$rewritten} unchanged={$unchanged} leftover_data_uri={$stillInline}");

        return self::SUCCESS;
    }

    /**
     * @return array{mode: string, client_id?: int, partner_id?: int, app_id?: int, note_id?: int}|false
     */
    private function resolveScope(): array|false
    {
        $all = (bool) $this->option('all');
        $clientOpt = trim((string) $this->option('client'));
        $partnerOpt = trim((string) $this->option('partner'));
        $applicationOpt = trim((string) $this->option('application'));
        $noteOpt = trim((string) $this->option('note'));
        $set = array_filter([$all ? 'all' : '', $clientOpt, $partnerOpt, $applicationOpt, $noteOpt]);

        if (count($set) > 1) {
            $this->error('Use only one of --all, --client, --partner, --application, or --note.');

            return false;
        }

        if ($all) {
            $this->warn('Scope: ALL notes/activities with data:image.');

            return ['mode' => 'all'];
        }

        if ($noteOpt !== '') {
            $note = Note::find($noteOpt);
            if (! $note) {
                $this->error("Note not found: {$noteOpt}");

                return false;
            }
            $this->info("Scope: notes.id {$note->id}");

            return ['mode' => 'note', 'note_id' => (int) $note->id];
        }

        if ($partnerOpt !== '') {
            $partner = Partner::find($partnerOpt);
            if (! $partner) {
                $this->error("Partner not found: {$partnerOpt}");

                return false;
            }
            $this->info("Scope: partner {$partner->id} ({$partner->partner_name})");

            return ['mode' => 'partner', 'partner_id' => (int) $partner->id];
        }

        if ($applicationOpt !== '') {
            $application = Application::find($applicationOpt);
            if (! $application) {
                $this->error("Application not found: {$applicationOpt}");

                return false;
            }
            $this->info("Scope: application {$application->id}");

            return ['mode' => 'application', 'app_id' => (int) $application->id];
        }

        if ($clientOpt !== '') {
            $client = ctype_digit($clientOpt)
                ? Admin::find((int) $clientOpt)
                : Admin::where('client_id', $clientOpt)->first();
            if (! $client) {
                $this->error("Client not found: {$clientOpt}");

                return false;
            }
            $this->info("Scope: client {$client->id} ({$client->client_id})");

            return ['mode' => 'client', 'client_id' => (int) $client->id];
        }

        $this->error('Specify --partner=ID, --client=ID, --application=ID, --note=ID, or --all.');
        $this->line('Example: php artisan tinymce-images:migrate-data-uris-to-s3 --partner=37 --dry-run');

        return false;
    }

    /**
     * @param  array{mode: string, client_id?: int, partner_id?: int, app_id?: int, note_id?: int}  $scope
     * @return list<array{0: string, 1: int|string, 2: string}>
     */
    private function collectRowSummaries(array $scope): array
    {
        $rows = [];
        foreach ($this->eachRow($scope, true) as $model) {
            $rows[] = [
                $model->getTable(),
                $model->id,
                (string) ($model->type ?? $model->client_id ?? $model->app_id ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @param  array{mode: string, client_id?: int, partner_id?: int, app_id?: int, note_id?: int}  $scope
     * @return \Generator<int, Note|ActivitiesLog|ApplicationActivitiesLog>
     */
    private function eachRow(array $scope, bool $summaryOnly = false): \Generator
    {
        $needle = '%data:image%';
        $includeNotes = in_array($scope['mode'], ['all', 'client', 'partner', 'note'], true);
        $includeActivities = in_array($scope['mode'], ['all', 'client'], true);
        $includeAppLogs = in_array($scope['mode'], ['all', 'client', 'application'], true);

        if ($includeNotes) {
            $notes = Note::query()->where('description', 'like', $needle);
            if ($scope['mode'] === 'note') {
                $notes->where('id', $scope['note_id']);
            } elseif ($scope['mode'] === 'partner') {
                $notes->where('client_id', $scope['partner_id'])->where('type', 'partner');
            } elseif ($scope['mode'] === 'client') {
                $notes->where('client_id', $scope['client_id'])->where(function ($q) {
                    $q->where('type', 'client')->orWhereNull('type');
                });
            }
            $noteCols = $summaryOnly ? ['id', 'type', 'client_id'] : ['*'];
            foreach ($notes->select($noteCols)->orderBy('id')->cursor() as $row) {
                yield $row;
            }
        }

        if ($includeActivities) {
            $activities = ActivitiesLog::query()->where('description', 'like', $needle);
            if ($scope['mode'] === 'client') {
                $activities->where('client_id', $scope['client_id']);
            }
            $activityCols = $summaryOnly ? ['id', 'client_id'] : ['*'];
            foreach ($activities->select($activityCols)->orderBy('id')->cursor() as $row) {
                yield $row;
            }
        }

        if ($includeAppLogs) {
            $appLogs = ApplicationActivitiesLog::query()->where('description', 'like', $needle);
            if ($scope['mode'] === 'client') {
                $appIds = Application::where('client_id', $scope['client_id'])->pluck('id');
                $appLogs->whereIn('app_id', $appIds);
            } elseif ($scope['mode'] === 'application') {
                $appLogs->where('app_id', $scope['app_id']);
            }
            $appCols = $summaryOnly ? ['id', 'type', 'app_id'] : ['*'];
            foreach ($appLogs->select($appCols)->orderBy('id')->cursor() as $row) {
                yield $row;
            }
        }
    }
}
