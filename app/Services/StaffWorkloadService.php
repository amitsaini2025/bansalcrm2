<?php

namespace App\Services;

use App\Models\ActivitiesLog;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationActivitiesLog;
use App\Models\Document;
use App\Models\Email;
use App\Models\Note;
use App\Models\Partner;
use App\Models\SmsLog;
use App\Models\Staff;
use App\Support\StaffAllocationScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StaffWorkloadService
{
    /** @var list<string> */
    public const CONTACT_TITLES = ['Call', 'In-Person'];

    /** @var list<string> */
    private const STUDENT_NOTE_TYPES = ['client', 'lead'];

    /** @var list<int> */
    private const CLOSED_APPLICATION_STATUSES = [2, 8];

    /** @var list<string> */
    private const NOTE_AUDIT_SUBJECTS = ['added a note', 'updated a note', 'deleted a note'];

    /**
     * @return array<string, mixed>
     */
    public function getDaySummary(int $staffId, ?Carbon $day = null): array
    {
        $day = ($day ?? now())->copy()->timezone($this->timezone());
        [$start, $end] = $this->dayBounds($day);

        $caseload = $this->getOpenCaseload($staffId);
        $contact = $this->getContactEvents($staffId, $start, $end);
        $throughput = $this->getThroughput($staffId, $start, $end);

        return [
            'day' => $day,
            'day_label' => $day->format('l, j F Y'),
            'range' => ['start' => $start, 'end' => $end],
            'caseload' => $caseload,
            'contact' => $contact,
            'throughput' => $throughput,
            'leads' => [
                'assigned' => $caseload['leads_assigned_count'],
                'converted_today' => $this->countLeadsConvertedToday($staffId, $start, $end),
            ],
        ];
    }

    /**
     * Compact metrics for admin team table (no drill-down lists).
     *
     * @return array<string, mixed>
     */
    public function getStaffOverview(int $staffId, ?Carbon $day = null): array
    {
        $day = ($day ?? now())->copy()->timezone($this->timezone());
        [$start, $end] = $this->dayBounds($day);

        $caseload = $this->getOpenCaseload($staffId);
        $contact = $this->getContactEvents($staffId, $start, $end);
        $throughput = $this->getThroughput($staffId, $start, $end);

        return [
            'staff_id' => $staffId,
            'day_label' => $day->format('l, j F Y'),
            'active_clients_count' => $caseload['active_clients_count'],
            'leads_assigned_count' => $caseload['leads_assigned_count'],
            'contacted_students_live_count' => $contact['contacted_students_live_count'],
            'spoke_to_students_count' => $contact['spoke_to_students_count'],
            'met_students_count' => $contact['met_students_count'],
            'worked_students_count' => $throughput['worked_students_count'],
            'worked_applications_count' => $throughput['worked_applications_count'],
            'stage_moves_count' => $throughput['stage_moves_count'],
            'owned_applications_open_count' => $caseload['owned_applications_open_count'],
            'owned_applications_closed_count' => $caseload['owned_applications_closed_count'],
            'converted_today' => $this->countLeadsConvertedToday($staffId, $start, $end),
            'quiet_students_count' => $caseload['quiet_students_count'],
            'inactive_students_count' => $caseload['inactive_students_count'],
            'call_not_picked_count' => $throughput['call_not_picked_count'],
            'actions_completed_count' => $throughput['actions_completed_count'],
        ];
    }

    /**
     * @return array{day_label: string, rows: list<array<string, mixed>>}
     */
    public function getTeamOverview(?Carbon $day = null): array
    {
        $day = ($day ?? now())->copy()->timezone($this->timezone());

        $staffMembers = Staff::query()
            ->where('status', 1)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'email']);

        $rows = [];
        foreach ($staffMembers as $staff) {
            $overview = $this->getStaffOverview((int) $staff->id, $day);
            $overview['staff_name'] = $staff->full_name;
            $overview['staff_email'] = $staff->email;
            $rows[] = $overview;
        }

        return [
            'day_label' => $day->format('l, j F Y'),
            'rows' => $rows,
        ];
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    public function dayBounds(Carbon $day): array
    {
        $tz = $this->timezone();
        $localized = $day->copy()->timezone($tz);

        return [
            $localized->copy()->startOfDay(),
            $localized->copy()->endOfDay(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getOpenCaseload(int $staffId): array
    {
        $allocatedClientsQuery = $this->allocatedAdminsQuery($staffId)
            ->where('type', 'client');

        $allocatedLeadsQuery = $this->allocatedAdminsQuery($staffId)
            ->where('type', 'lead')
            ->where(function (Builder $q) {
                $q->where('converted', 0)->orWhereNull('converted');
            });

        $activeClientsCount = (clone $allocatedClientsQuery)->count();

        $leadsAssignedCount = (clone $allocatedLeadsQuery)->count();

        $ownedAppsQuery = Application::query()->where('user_id', $staffId);

        $openAppsQuery = (clone $ownedAppsQuery)->where(function (Builder $q) {
            $q->whereNotIn('status', self::CLOSED_APPLICATION_STATUSES)
                ->orWhereNull('status');
        });

        $closedAppsQuery = (clone $ownedAppsQuery)->whereIn('status', self::CLOSED_APPLICATION_STATUSES);

        $allocatedStudentIds = $this->allocatedAdminsQuery($staffId)
            ->whereIn('type', ['client', 'lead'])
            ->pluck('id');

        $studentsWithApps = Application::query()
            ->whereIn('client_id', $allocatedStudentIds)
            ->distinct()
            ->pluck('client_id');

        $noApplicationCount = $allocatedStudentIds->diff($studentsWithApps)->count();

        $quietInactive = $this->getQuietInactive($staffId, $allocatedStudentIds, (clone $openAppsQuery)->pluck('id'));

        return [
            'active_clients_count' => $activeClientsCount,
            'leads_assigned_count' => $leadsAssignedCount,
            'owned_applications_open_count' => (clone $openAppsQuery)->count(),
            'owned_applications_closed_count' => (clone $closedAppsQuery)->count(),
            'no_application_students_count' => $noApplicationCount,
            'quiet_students_count' => $quietInactive['quiet_students_count'],
            'inactive_students_count' => $quietInactive['inactive_students_count'],
            'quiet_applications_count' => $quietInactive['quiet_applications_count'],
            'inactive_applications_count' => $quietInactive['inactive_applications_count'],
            'quiet_students' => $quietInactive['quiet_students'],
            'inactive_students' => $quietInactive['inactive_students'],
            'quiet_applications' => $quietInactive['quiet_applications'],
            'inactive_applications' => $quietInactive['inactive_applications'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getContactEvents(int $staffId, Carbon $start, Carbon $end): array
    {
        $studentNotes = $this->contactNotesQuery($staffId, $start, $end)
            ->whereIn('type', self::STUDENT_NOTE_TYPES)
            ->get(['id', 'client_id', 'title', 'created_at', 'description']);

        $partnerNotes = $this->contactNotesQuery($staffId, $start, $end)
            ->where('type', 'partner')
            ->get(['id', 'client_id', 'title', 'created_at', 'description']);

        $studentIdsCall = $this->distinctClientIdsForTitle($studentNotes, 'Call');
        $studentIdsMet = $this->distinctClientIdsForTitle($studentNotes, 'In-Person');
        $studentIdsLive = $studentIdsCall->merge($studentIdsMet)->unique()->values();

        $partnerIdsCall = $this->distinctClientIdsForTitle($partnerNotes, 'Call');
        $partnerIdsMet = $this->distinctClientIdsForTitle($partnerNotes, 'In-Person');
        $partnerIdsLive = $partnerIdsCall->merge($partnerIdsMet)->unique()->values();

        $students = Admin::query()
            ->whereIn('id', $studentIdsLive)
            ->get(['id', 'first_name', 'last_name', 'client_id', 'type'])
            ->keyBy('id');

        $partners = Partner::query()
            ->whereIn('id', $partnerIdsLive)
            ->get(['id', 'partner_name'])
            ->keyBy('id');

        return [
            'spoke_to_students_count' => $studentIdsCall->count(),
            'met_students_count' => $studentIdsMet->count(),
            'contacted_students_live_count' => $studentIdsLive->count(),
            'spoke_to_colleges_count' => $partnerIdsCall->count(),
            'met_colleges_count' => $partnerIdsMet->count(),
            'contacted_colleges_live_count' => $partnerIdsLive->count(),
            'students' => $this->buildContactList($studentNotes, $students, 'student'),
            'colleges' => $this->buildContactList($partnerNotes, $partners, 'partner'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getThroughput(int $staffId, Carbon $start, Carbon $end): array
    {
        $studentNoteClientIds = Note::query()
            ->where('user_id', $staffId)
            ->where('is_action', 0)
            ->whereIn('type', self::STUDENT_NOTE_TYPES)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('client_id')
            ->distinct()
            ->pluck('client_id');

        $activityStudentIds = $this->filterStudentAdminIds(
            $this->applyExcludeNoteAuditSubjects(
                ActivitiesLog::query()
                    ->where('created_by', $staffId)
                    ->whereBetween('created_at', [$start, $end])
                    ->whereNotNull('client_id')
            )
                ->distinct()
                ->pluck('client_id')
        );

        $emailStudentIds = Email::query()
            ->where('user_id', $staffId)
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('type', self::STUDENT_NOTE_TYPES)
            ->whereNotNull('client_id')
            ->distinct()
            ->pluck('client_id');

        $smsStudentIds = SmsLog::query()
            ->where('sender_id', $staffId)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('client_id')
            ->distinct()
            ->pluck('client_id');

        $documentStudentIds = Document::query()
            ->where(function (Builder $q) use ($staffId) {
                $q->where('created_by', $staffId)->orWhere('user_id', $staffId);
            })
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('type', self::STUDENT_NOTE_TYPES)
            ->whereNotNull('client_id')
            ->distinct()
            ->pluck('client_id');

        $workedStudentIds = $this->filterStudentAdminIds(
            $studentNoteClientIds
                ->merge($activityStudentIds)
                ->merge($emailStudentIds)
                ->merge($smsStudentIds)
                ->merge($documentStudentIds)
                ->unique()
                ->values()
        );

        $applicationIds = ApplicationActivitiesLog::query()
            ->where('user_id', $staffId)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('app_id')
            ->distinct()
            ->pluck('app_id');

        $stageMovesCount = ApplicationActivitiesLog::query()
            ->where('user_id', $staffId)
            ->where('type', 'stage')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $partnerNoteIds = Note::query()
            ->where('user_id', $staffId)
            ->where('is_action', 0)
            ->where('type', 'partner')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('client_id')
            ->distinct()
            ->pluck('client_id');

        $partnerEmailIds = Email::query()
            ->where('user_id', $staffId)
            ->where('type', 'partner')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('client_id')
            ->distinct()
            ->pluck('client_id');

        $partnerDocumentIds = Document::query()
            ->where(function (Builder $q) use ($staffId) {
                $q->where('created_by', $staffId)->orWhere('user_id', $staffId);
            })
            ->where('type', 'partner')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('client_id')
            ->distinct()
            ->pluck('client_id');

        $workedCollegeIds = $partnerNoteIds
            ->merge($partnerEmailIds)
            ->merge($partnerDocumentIds)
            ->unique()
            ->values();

        $actionsCompletedCount = Note::query()
            ->where('assigned_to', $staffId)
            ->where('is_action', 1)
            ->where('status', '1')
            ->whereBetween('updated_at', [$start, $end])
            ->count();

        $callNotPickedCount = (int) SmsLog::query()
            ->where('sender_id', $staffId)
            ->where('message_type', 'notification')
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('client_id')
            ->distinct()
            ->count('client_id');

        $students = Admin::query()
            ->whereIn('id', $workedStudentIds)
            ->get(['id', 'first_name', 'last_name', 'client_id', 'type'])
            ->keyBy('id');

        $applications = Application::query()
            ->whereIn('id', $applicationIds)
            ->with(['client:id,first_name,last_name,client_id,type'])
            ->get(['id', 'client_id', 'stage', 'partner_id']);

        $colleges = Partner::query()
            ->whereIn('id', $workedCollegeIds)
            ->get(['id', 'partner_name'])
            ->keyBy('id');

        return [
            'worked_students_count' => $workedStudentIds->count(),
            'worked_applications_count' => $applicationIds->count(),
            'worked_colleges_count' => $workedCollegeIds->count(),
            'stage_moves_count' => $stageMovesCount,
            'actions_completed_count' => $actionsCompletedCount,
            'call_not_picked_count' => $callNotPickedCount,
            'students' => $workedStudentIds->map(function ($id) use ($students) {
                $row = $students->get($id);

                return $this->formatStudentRow($row);
            })->filter()->values()->all(),
            'applications' => $applications->map(function (Application $app) {
                $client = $app->client;

                return [
                    'id' => $app->id,
                    'stage' => $app->stage,
                    'client_name' => $client ? trim($client->first_name.' '.$client->last_name) : 'Unknown',
                    'client_reference' => $client?->client_id,
                    'url' => $client ? $this->clientDetailUrl((int) $client->id, (string) ($client->type ?? 'client')) : null,
                ];
            })->values()->all(),
            'colleges' => $workedCollegeIds->map(function ($id) use ($colleges) {
                $row = $colleges->get($id);
                if (! $row) {
                    return null;
                }

                return [
                    'id' => $row->id,
                    'name' => $row->partner_name,
                    'url' => route('partners.detail', ['id' => base64_encode(convert_uuencode((string) $row->id))]),
                ];
            })->filter()->values()->all(),
        ];
    }

    public function encodeRecordId(int $id): string
    {
        return base64_encode(convert_uuencode((string) $id));
    }

    public function clientDetailUrl(int $adminId, string $type = 'client'): string
    {
        $encoded = $this->encodeRecordId($adminId);
        $route = $type === 'lead' ? 'leads.detail' : 'clients.detail';

        return route($route, ['id' => $encoded]);
    }

    public function isNoteAuditSubject(?string $subject): bool
    {
        return in_array(strtolower(trim((string) $subject)), self::NOTE_AUDIT_SUBJECTS, true);
    }

    private function timezone(): string
    {
        return (string) config('app.timezone', 'Australia/Melbourne');
    }

    /**
     * @return Builder<Admin>
     */
    private function allocatedAdminsQuery(int $staffId): Builder
    {
        $query = Admin::query();

        StaffAllocationScope::applyToAdminsQuery($query, $staffId);
        StaffAllocationScope::applyActiveClientFilters($query);

        return $query;
    }

    private function countLeadsConvertedToday(int $staffId, Carbon $start, Carbon $end): int
    {
        $query = Admin::query()
            ->where('type', 'client')
            ->where('converted', 1)
            ->whereBetween('converted_date', [$start->toDateString(), $end->toDateString()]);

        StaffAllocationScope::applyToAdminsQuery($query, $staffId);
        StaffAllocationScope::applyActiveClientFilters($query);

        return $query->count();
    }

    /**
     * @return Builder<Note>
     */
    private function contactNotesQuery(int $staffId, Carbon $start, Carbon $end): Builder
    {
        return Note::query()
            ->where('user_id', $staffId)
            ->where('is_action', 0)
            ->whereIn('title', self::CONTACT_TITLES)
            ->whereBetween('created_at', [$start, $end]);
    }

    /**
     * @param  Collection<int, Note>  $notes
     * @return Collection<int, int>
     */
    private function distinctClientIdsForTitle(Collection $notes, string $title): Collection
    {
        return $notes
            ->where('title', $title)
            ->pluck('client_id')
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * @param  Collection<int, Admin|Partner>  $records
     * @return list<array<string, mixed>>
     */
    private function buildContactList(Collection $notes, Collection $records, string $kind): array
    {
        $seen = [];

        return $notes
            ->sortByDesc('created_at')
            ->map(function (Note $note) use ($records, $kind, &$seen) {
                $entityId = (int) $note->client_id;
                $key = $entityId.'|'.$note->title;
                if (isset($seen[$key])) {
                    return null;
                }
                $seen[$key] = true;

                if ($kind === 'student') {
                    /** @var Admin|null $row */
                    $row = $records->get($entityId);
                    if (! $row) {
                        return null;
                    }

                    return [
                        'note_type' => $note->title,
                        'created_at' => $note->created_at?->timezone($this->timezone())->format('d/m/Y h:i A'),
                        'snippet' => str($note->description ?? '')->stripTags()->limit(80)->toString(),
                        'name' => trim($row->first_name.' '.$row->last_name),
                        'reference' => $row->client_id,
                        'url' => $this->clientDetailUrl($row->id, (string) ($row->type ?? 'client')),
                    ];
                }

                /** @var Partner|null $row */
                $row = $records->get($entityId);
                if (! $row) {
                    return null;
                }

                return [
                    'note_type' => $note->title,
                    'created_at' => $note->created_at?->timezone($this->timezone())->format('d/m/Y h:i A'),
                    'snippet' => str($note->description ?? '')->stripTags()->limit(80)->toString(),
                    'name' => $row->partner_name,
                    'reference' => null,
                    'url' => route('partners.detail', ['id' => $this->encodeRecordId($row->id)]),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, int|string>  $studentIds
     * @param  Collection<int, int|string>  $applicationIds
     * @return array<string, mixed>
     */
    private function getQuietInactive(int $staffId, Collection $studentIds, Collection $applicationIds): array
    {
        $today = now()->timezone($this->timezone())->startOfDay();

        $studentLastWork = $this->lastWorkByStudent($staffId, $studentIds);
        $applicationLastWork = $this->lastWorkByApplication($staffId, $applicationIds);

        $quietStudents = [];
        $inactiveStudents = [];
        $quietApps = [];
        $inactiveApps = [];

        $studentRows = Admin::query()
            ->whereIn('id', $studentIds)
            ->get(['id', 'first_name', 'last_name', 'client_id', 'type'])
            ->keyBy('id');

        foreach ($studentIds as $studentId) {
            $band = $this->inactivityBand($studentLastWork->get((int) $studentId), $today);
            if ($band === null) {
                continue;
            }

            $row = $studentRows->get((int) $studentId);
            if (! $row) {
                continue;
            }

            $entry = $this->formatStudentRow($row, $studentLastWork->get((int) $studentId));
            if ($band === 'quiet') {
                $quietStudents[] = $entry;
            } else {
                $inactiveStudents[] = $entry;
            }
        }

        $applicationRows = Application::query()
            ->whereIn('id', $applicationIds)
            ->with(['client:id,first_name,last_name,client_id,type'])
            ->get(['id', 'client_id', 'stage'])
            ->keyBy('id');

        foreach ($applicationIds as $applicationId) {
            $band = $this->inactivityBand($applicationLastWork->get((int) $applicationId), $today);
            if ($band === null) {
                continue;
            }

            $app = $applicationRows->get((int) $applicationId);
            if (! $app) {
                continue;
            }

            $client = $app->client;
            $entry = [
                'id' => $app->id,
                'stage' => $app->stage,
                'client_name' => $client ? trim($client->first_name.' '.$client->last_name) : 'Unknown',
                'client_reference' => $client?->client_id,
                'last_work_at' => $applicationLastWork->get((int) $applicationId)?->timezone($this->timezone())->format('d/m/Y'),
                'url' => $client ? $this->clientDetailUrl((int) $client->id, (string) ($client->type ?? 'client')) : null,
            ];

            if ($band === 'quiet') {
                $quietApps[] = $entry;
            } else {
                $inactiveApps[] = $entry;
            }
        }

        return [
            'quiet_students_count' => count($quietStudents),
            'inactive_students_count' => count($inactiveStudents),
            'quiet_applications_count' => count($quietApps),
            'inactive_applications_count' => count($inactiveApps),
            'quiet_students' => array_slice($quietStudents, 0, 25),
            'inactive_students' => array_slice($inactiveStudents, 0, 25),
            'quiet_applications' => array_slice($quietApps, 0, 25),
            'inactive_applications' => array_slice($inactiveApps, 0, 25),
        ];
    }

    /**
     * @param  Collection<int, int|string>  $studentIds
     * @return Collection<int, Carbon>
     */
    private function lastWorkByStudent(int $staffId, Collection $studentIds): Collection
    {
        if ($studentIds->isEmpty()) {
            return collect();
        }

        $ids = $studentIds->map(fn ($id) => (int) $id)->all();
        $last = collect();

        $noteRows = Note::query()
            ->select('client_id', DB::raw('MAX(created_at) as last_at'))
            ->where('user_id', $staffId)
            ->whereIn('client_id', $ids)
            ->groupBy('client_id')
            ->get();

        foreach ($noteRows as $row) {
            $this->mergeLastTimestamp($last, (int) $row->client_id, $row->last_at);
        }

        $activityRows = $this->applyExcludeNoteAuditSubjects(
            ActivitiesLog::query()
                ->select('client_id', DB::raw('MAX(created_at) as last_at'))
                ->where('created_by', $staffId)
                ->whereIn('client_id', $ids)
        )
            ->groupBy('client_id')
            ->get();

        foreach ($activityRows as $row) {
            $this->mergeLastTimestamp($last, (int) $row->client_id, $row->last_at);
        }

        $emailRows = Email::query()
            ->select('client_id', DB::raw('MAX(created_at) as last_at'))
            ->where('user_id', $staffId)
            ->whereIn('client_id', $ids)
            ->groupBy('client_id')
            ->get();

        foreach ($emailRows as $row) {
            $this->mergeLastTimestamp($last, (int) $row->client_id, $row->last_at);
        }

        $smsRows = SmsLog::query()
            ->select('client_id', DB::raw('MAX(created_at) as last_at'))
            ->where('sender_id', $staffId)
            ->whereIn('client_id', $ids)
            ->groupBy('client_id')
            ->get();

        foreach ($smsRows as $row) {
            $this->mergeLastTimestamp($last, (int) $row->client_id, $row->last_at);
        }

        $documentRows = Document::query()
            ->select('client_id', DB::raw('MAX(created_at) as last_at'))
            ->where(function (Builder $q) use ($staffId) {
                $q->where('created_by', $staffId)->orWhere('user_id', $staffId);
            })
            ->whereIn('client_id', $ids)
            ->groupBy('client_id')
            ->get();

        foreach ($documentRows as $row) {
            $this->mergeLastTimestamp($last, (int) $row->client_id, $row->last_at);
        }

        return $last;
    }

    /**
     * @param  Collection<int, int|string>  $applicationIds
     * @return Collection<int, Carbon>
     */
    private function lastWorkByApplication(int $staffId, Collection $applicationIds): Collection
    {
        if ($applicationIds->isEmpty()) {
            return collect();
        }

        $ids = $applicationIds->map(fn ($id) => (int) $id)->all();
        $last = collect();

        $rows = ApplicationActivitiesLog::query()
            ->select('app_id', DB::raw('MAX(created_at) as last_at'))
            ->where('user_id', $staffId)
            ->whereIn('app_id', $ids)
            ->groupBy('app_id')
            ->get();

        foreach ($rows as $row) {
            $this->mergeLastTimestamp($last, (int) $row->app_id, $row->last_at);
        }

        return $last;
    }

    /**
     * @param  Collection<int, Carbon>  $bag
     */
    private function mergeLastTimestamp(Collection $bag, int $id, mixed $timestamp): void
    {
        if ($timestamp === null) {
            return;
        }

        $carbon = Carbon::parse($timestamp);
        $existing = $bag->get($id);
        if ($existing === null || $carbon->gt($existing)) {
            $bag->put($id, $carbon);
        }
    }

    /**
     * Partner rows store partners.id in activities/documents client_id — keep student metrics student-only.
     *
     * @param  Collection<int, int|string>  $candidateIds
     * @return Collection<int, int>
     */
    private function filterStudentAdminIds(Collection $candidateIds): Collection
    {
        if ($candidateIds->isEmpty()) {
            return collect();
        }

        return Admin::query()
            ->whereIn('id', $candidateIds->map(fn ($id) => (int) $id)->all())
            ->whereIn('type', self::STUDENT_NOTE_TYPES)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    private function applyExcludeNoteAuditSubjects(Builder $query, string $column = 'subject'): Builder
    {
        $placeholders = implode(', ', array_fill(0, count(self::NOTE_AUDIT_SUBJECTS), '?'));

        return $query->where(function (Builder $q) use ($column, $placeholders) {
            $q->whereNull($column)
                ->orWhereRaw(
                    'LOWER(TRIM('.$column.')) NOT IN ('.$placeholders.')',
                    self::NOTE_AUDIT_SUBJECTS
                );
        });
    }

    private function inactivityBand(?Carbon $lastWork, Carbon $todayStart): ?string
    {
        if ($lastWork === null) {
            return 'inactive';
        }

        $lastDay = $lastWork->copy()->timezone($this->timezone())->startOfDay();
        $daysAgo = (int) $lastDay->diffInDays($todayStart);

        if ($daysAgo >= 14) {
            return 'inactive';
        }

        if ($daysAgo >= 7) {
            return 'quiet';
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatStudentRow(?Admin $row, ?Carbon $lastWorkAt = null): ?array
    {
        if (! $row) {
            return null;
        }

        return [
            'id' => $row->id,
            'name' => trim($row->first_name.' '.$row->last_name),
            'reference' => $row->client_id,
            'type' => $row->type,
            'last_work_at' => $lastWorkAt?->timezone($this->timezone())->format('d/m/Y'),
            'url' => $this->clientDetailUrl($row->id, (string) ($row->type ?? 'client')),
        ];
    }
}
