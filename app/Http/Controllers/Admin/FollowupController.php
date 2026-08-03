<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivitiesLog;
use App\Models\FollowupConsultant;
use App\Models\Note;
use App\Support\FollowupAvailability;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Scheduled follow-ups (Notes with task_group = Followup).
 */
class FollowupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Display heading per consultant (e.g. calendar card title).
     *
     * @var array<string, string>
     */
    public const CONSULTANT_LABELS = [
        'ankit' => 'Ankit Followups',
        'rakshita' => 'Rakshita Followups',
        'jaspreet' => 'Jaspreet Followups',
        'syed' => 'Syed Followups',
    ];

    /**
     * Suffix used in historical note titles (still matched on consultant calendars).
     *
     * @var array<string, string>
     */
    protected const LEGACY_CONSULTANT_NOTE_SUFFIXES = [
        'ankit' => 'Ankit Calendar',
        'rakshita' => 'Rakshita Calendar',
        'jaspreet' => 'Jaspreet Calendar',
        'syed' => 'Syed Calendar',
    ];

    public static function consultantLabel(string $slug): ?string
    {
        return self::CONSULTANT_LABELS[$slug] ?? null;
    }

    public static function followupNoteTitle(string $slug): ?string
    {
        $label = self::consultantLabel($slug);

        return $label ? 'Followup — '.$label : null;
    }

    /**
     * Resolve consultant slug from a scheduled follow-up note title.
     */
    public static function consultantSlugFromFollowupNoteTitle(?string $title): ?string
    {
        if ($title === null || $title === '') {
            return null;
        }
        $prefix = 'Followup — ';
        if (! str_starts_with($title, $prefix)) {
            return null;
        }
        $suffix = substr($title, strlen($prefix));
        foreach (array_keys(self::CONSULTANT_LABELS) as $slug) {
            $label = self::consultantLabel($slug);
            if ($label !== null && $suffix === $label) {
                return $slug;
            }
            $legacy = self::LEGACY_CONSULTANT_NOTE_SUFFIXES[$slug] ?? null;
            if ($legacy !== null && $suffix === $legacy) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * Note titles that belong on this consultant’s calendar (current + legacy).
     *
     * @return list<string>
     */
    public static function followupNoteTitlesForCalendar(string $slug): array
    {
        $titles = [];
        $primary = self::followupNoteTitle($slug);
        if ($primary !== null) {
            $titles[] = $primary;
        }
        $legacySuffix = self::LEGACY_CONSULTANT_NOTE_SUFFIXES[$slug] ?? null;
        if ($legacySuffix !== null) {
            $legacy = 'Followup — '.$legacySuffix;
            if (! in_array($legacy, $titles, true)) {
                $titles[] = $legacy;
            }
        }

        return $titles;
    }

    /**
     * Human-readable consultant label for HTML bodies (matches schedule-follow-up saves).
     */
    protected static function consultantDisplayForSlug(string $slug): string
    {
        $mapped = self::consultantLabel($slug);
        if ($mapped !== null) {
            return $mapped;
        }

        $dbName = FollowupConsultant::query()
            ->where('slug', $slug)
            ->where('status', 1)
            ->value('name');

        if ($dbName !== null) {
            $replaced = preg_replace('/\s+Calendar$/u', ' Followups', (string) $dbName);

            return $replaced !== null && $replaced !== '' ? $replaced : (string) $dbName;
        }

        return $slug;
    }

    /**
     * Extract structured fields from HTML saved by schedule-follow-up.
     *
     * @return array{followup_type: string, service: string, consultant_display: string, followup_detail: string, preferred_language: string, details_plain: string}
     */
    public static function parseScheduledFollowupNoteHtml(string $html): array
    {
        $out = [
            'followup_type' => '',
            'service' => '',
            'consultant_display' => '',
            'followup_detail' => '',
            'preferred_language' => '',
            'details_plain' => '',
        ];

        $patterns = [
            'followup_type' => '#<li><strong>Follow-up type:</strong>\s*(.*?)</li>#si',
            'service' => '#<li><strong>Service:</strong>\s*(.*?)</li>#si',
            'consultant_display' => '#<li><strong>Consultant:</strong>\s*(.*?)</li>#si',
            'followup_detail' => '#<li><strong>Follow-up details:</strong>\s*(.*?)</li>#si',
            'preferred_language' => '#<li><strong>Preferred language:</strong>\s*(.*?)</li>#si',
        ];

        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $html, $m)) {
                $out[$key] = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
        }

        if (preg_match('#<p><strong>Details:</strong></p>\s*<p>(.*?)</p>#si', $html, $m)) {
            $inner = str_ireplace(['<br>', '<br/>', '<br />'], "\n", $m[1]);
            $out['details_plain'] = trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return $out;
    }

    /**
     * Short channel label for calendar pills (e.g. Phone, In-Person).
     */
    protected static function followupChannelShortLabel(string $followupDetail): string
    {
        $raw = trim($followupDetail);
        if ($raw === '') {
            return '';
        }
        $lower = strtolower($raw);
        if (str_contains($lower, 'phone')) {
            return 'Phone';
        }
        if (str_contains($lower, 'in-person') || str_contains($lower, 'in person')) {
            return 'In-Person';
        }

        return $raw;
    }

    /**
     * Service text for activity logs (drop redundant "(15 min — Free)" style suffix).
     */
    protected static function serviceLabelForActivityLog(?string $serviceRaw): string
    {
        $s = trim((string) $serviceRaw);
        if ($s === '') {
            return '—';
        }
        $stripped = preg_replace('/\s*\(\s*15\s*min\s*[—–\-]\s*Free\s*\)\s*$/iu', '', $s);
        $s = is_string($stripped) ? $stripped : $s;

        return trim($s) !== '' ? trim($s) : '—';
    }

    /**
     * Human-readable status for the follow-up listing (aligned with calendar outcome logic).
     */
    public static function followupListingStatusLabel(Note $note): string
    {
        if (! Schema::hasColumn('notes', 'followup_outcome')) {
            return (int) $note->status === 0 ? 'Confirmed' : 'Completed';
        }

        $outcome = $note->followup_outcome;
        if ($outcome === 'completed') {
            return 'Completed';
        }
        if ($outcome === 'cancelled') {
            return 'Cancelled';
        }
        if ($outcome === 'no_show') {
            return 'No show';
        }
        if ((int) $note->status === 1 && ($outcome === null || $outcome === '')) {
            return 'Completed';
        }

        return 'Confirmed';
    }

    /**
     * Bootstrap 5 colour utility classes for the follow-up listing status pill.
     */
    public static function followupListingStatusBadgeClass(string $statusLabel): string
    {
        return match ($statusLabel) {
            'Confirmed' => 'bg-success',
            'Cancelled' => 'bg-danger',
            'Completed' => 'bg-primary',
            'No show' => 'bg-warning text-dark',
            default => 'bg-secondary',
        };
    }

    /**
     * Strip redundant "(15 min — Free)" from stored follow-up note HTML for display (detail page).
     */
    protected static function stripFreeConsultationDurationSuffixFromFollowupHtml(string $html): string
    {
        $out = preg_replace('/Free\s+Consultation\s*\(\s*15\s*min\s*[—–\-]\s*Free\s*\)/iu', 'Free Consultation', $html);

        return is_string($out) ? $out : $html;
    }

    /**
     * All client follow-up notes for the admin listing (any outcome / status).
     *
     * Listing is not scoped by role or assignee — any authenticated CRM user sees the same rows.
     */
    protected function followupListingQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Note::query()
            ->where('type', 'client')
            ->where('is_action', 1)
            ->where('task_group', 'Followup');
    }

    /**
     * Notes loaded for consultant calendars (all open/closed outcomes).
     * UI defaults to showing open (confirmed) only; filter can show completed/cancelled/no_show/all.
     * Consultant routing uses note title in {@see calendar()}; status colours come from the view payload.
     */
    protected function calendarFollowupNotesQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return Note::query()
            ->where('type', 'client')
            ->where('is_action', 1)
            ->where('task_group', 'Followup');
    }

    /**
     * Resolve the original “Scheduled follow-up” activities_logs id for a note.
     * Matches subject prefix + full note title in description (no note_id / no row-window cap).
     * Soft-fail: returns null if nothing matches — callers must not fail the user action.
     */
    protected function findScheduledFollowupActivityLogId(Note $note, ?string $titleNeedle = null): ?int
    {
        if (! Schema::hasTable('activities_logs') || ! $note->client_id) {
            return null;
        }

        $titleNeedle = $titleNeedle !== null && $titleNeedle !== ''
            ? $titleNeedle
            : (string) $note->title;

        if ($titleNeedle === '') {
            return null;
        }

        $q = DB::table('activities_logs')
            ->where('client_id', $note->client_id);

        if (Schema::hasColumn('activities_logs', 'task_group')) {
            $q->where('task_group', 'Followup');
        }

        // Only the original schedule row — never reschedule / consultant-changed / status logs.
        if (Schema::hasColumn('activities_logs', 'subject')) {
            $q->where('subject', 'like', 'Scheduled follow-up (%');
        }

        // Prefer exact title span as written on schedule; fall back to raw title.
        $titleInSpan = '<span class="text-semi-bold">'.e($titleNeedle).'</span>';

        foreach ($q->orderByDesc('id')->get(['id', 'description']) as $row) {
            $description = (string) $row->description;
            if (
                str_contains($description, $titleInSpan)
                || str_contains($description, $titleNeedle)
            ) {
                return (int) $row->id;
            }
        }

        return null;
    }

    /**
     * Try to sync the activity log row created when this follow-up was scheduled.
     */
    protected function syncActivitiesLogAfterConsultantChange(Note $note, string $oldNoteTitle): void
    {
        if (! Schema::hasTable('activities_logs')) {
            return;
        }

        $slug = self::consultantSlugFromFollowupNoteTitle($note->title);
        if ($slug === null) {
            return;
        }

        $logId = $this->findScheduledFollowupActivityLogId($note, $oldNoteTitle);
        if ($logId === null) {
            return;
        }

        $consultantDisplay = self::consultantDisplayForSlug($slug);
        $newSubject = 'Scheduled follow-up ('.$consultantDisplay.')';
        $newDesc = '<span class="text-semi-bold">'.e($note->title).'</span><p>'.$note->description.'</p>';

        $payload = array_filter([
            'subject' => Schema::hasColumn('activities_logs', 'subject') ? $newSubject : null,
            'description' => $newDesc,
        ], fn ($v) => $v !== null);

        if ($payload === []) {
            return;
        }

        DB::table('activities_logs')->where('id', $logId)->update($payload);
    }

    public function index()
    {
        $followups = $this->followupListingQuery()
            ->with([
                'noteClient:id,first_name,last_name,client_id,email',
            ])
            ->whereNotNull('action_assign_date')
            ->orderByDesc('action_assign_date')
            ->paginate(20);

        return view('Admin.followups.index', compact('followups'));
    }

    /**
     * Read-only follow-up note detail (from listing “View”).
     */
    public function viewNote(Note $note)
    {
        $this->assertCanAccessFollowupNoteOrAbort($note);

        $note->loadMissing([
            'noteClient:id,first_name,last_name,client_id,email',
            'assigned_user:id,first_name,last_name',
        ]);

        $client = $note->noteClient;
        $clientDetailUrl = $client ? url('/clients/detail/'.base64_encode(convert_uuencode($client->id))) : null;

        $slug = self::consultantSlugFromFollowupNoteTitle($note->title);
        if ($slug !== null) {
            $consultantDisplay = self::consultantDisplayForSlug($slug);
        } else {
            $consultantDisplay = $note->title
                ? preg_replace('/^Followup\s+[—\-]\s*/u', '', (string) $note->title)
                : '—';
            if ($consultantDisplay === '') {
                $consultantDisplay = '—';
            }
        }

        $assignPretty = $note->action_assign_date
            ? Carbon::parse($note->action_assign_date)->timezone(config('app.timezone'))->format('d/m/Y H:i')
            : '—';

        $descriptionHtml = self::stripFreeConsultationDurationSuffixFromFollowupHtml((string) $note->description);

        return view('Admin.followups.show', [
            'note' => $note,
            'client' => $client,
            'clientDetailUrl' => $clientDetailUrl,
            'consultantDisplay' => $consultantDisplay,
            'assignPretty' => $assignPretty,
            'followupOutcome' => Schema::hasColumn('notes', 'followup_outcome') ? $note->followup_outcome : null,
            'descriptionHtml' => $descriptionHtml,
        ]);
    }

    public function calendar(string $consultant)
    {
        $titleVariants = self::followupNoteTitlesForCalendar($consultant);
        if ($titleVariants === []) {
            abort(404);
        }
        $consultantLabel = self::consultantLabel($consultant);

        $notes = $this->calendarFollowupNotesQuery()
            ->whereIn('title', $titleVariants)
            ->whereNotNull('action_assign_date')
            ->with([
                'noteClient:id,first_name,last_name,client_id,email,phone,office_id',
                'noteClient.office:id,office_name',
            ])
            ->orderBy('action_assign_date')
            ->get();

        $followupConsultants = collect();
        if (Schema::hasTable('followup_consultants')) {
            $followupConsultants = FollowupConsultant::query()
                ->where('status', 1)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        $sched_res = [];
        foreach ($notes as $note) {
            $client = $note->noteClient;
            if (! $client) {
                continue;
            }

            $parsed = self::parseScheduledFollowupNoteHtml((string) $note->description);
            $consultantSlug = self::consultantSlugFromFollowupNoteTitle($note->title);

            $dt = Carbon::parse($note->action_assign_date)->timezone(config('app.timezone'));

            $displayName = trim(implode(' ', array_filter([(string) ($client->first_name ?? ''), (string) ($client->last_name ?? '')])));
            if ($displayName === '') {
                $displayName = (string) ($client->client_id ?: ('#'.$client->id));
            }

            $durationMin = $consultantSlug
                ? (FollowupAvailability::slotDurationMinutes($consultantSlug, 'free') ?? 30)
                : 30;

            $followupOutcome = (Schema::hasColumn('notes', 'followup_outcome'))
                ? $note->followup_outcome
                : null;

            $calendarStatus = 'confirmed';
            if ($followupOutcome === 'completed') {
                $calendarStatus = 'completed';
            } elseif ($followupOutcome === 'cancelled') {
                $calendarStatus = 'cancelled';
            } elseif ($followupOutcome === 'no_show') {
                $calendarStatus = 'no_show';
            } elseif ((int) $note->status === 1 && $followupOutcome === null) {
                $calendarStatus = 'completed';
            }

            $sched_res[$note->id] = [
                'id' => $note->id,
                'clientid' => $client->id,
                'stitle' => $client->client_id ?: ('#'.$client->id),
                'name' => base64_encode(trim($client->first_name.' '.$client->last_name)),
                'email' => base64_encode((string) ($client->email ?? '')),
                'phone' => base64_encode((string) ($client->phone ?? '')),
                'startdate' => $dt->format('Y-m-d'),
                'end' => $dt->format('Y-m-d'),
                'start_iso' => $dt->format('Y-m-d\TH:i:s'),
                'end_iso' => $dt->copy()->addMinutes(max(5, $durationMin))->format('Y-m-d\TH:i:s'),
                'time_label' => $dt->format('g:ia'),
                'client_display_name' => $displayName,
                'channel_short' => self::followupChannelShortLabel($parsed['followup_detail']),
                'followup_date' => date('F j, Y g:i A', strtotime((string) $note->action_assign_date)),
                'date_pretty' => $dt->format('j M Y, g:i a'),
                'datetime_local' => $dt->format('Y-m-d\TH:i'),
                'duration_minutes' => $durationMin,
                'location_display' => $client->office?->office_name ?? '—',
                'consultant_display' => $consultantSlug ? self::consultantDisplayForSlug($consultantSlug) : '—',
                'followup_outcome' => $followupOutcome,
                'calendar_status' => $calendarStatus,
                'note_status' => (int) $note->status,
                'url' => url('/clients/detail/'.base64_encode(convert_uuencode($client->id))),
                'followup_type' => $parsed['followup_type'],
                'service' => $parsed['service'],
                'followup_detail' => $parsed['followup_detail'],
                'preferred_language' => $parsed['preferred_language'],
                'details_plain' => $parsed['details_plain'],
                'consultant_slug' => $consultantSlug,
            ];
        }

        $followupsReassignUrl = route('followups.reassign-consultant');
        $followupsRescheduleUrl = route('followups.reschedule');
        $followupsOutcomeUrl = route('followups.set-outcome');

        return view('Admin.followups.calendar', compact(
            'sched_res',
            'consultant',
            'consultantLabel',
            'followupConsultants',
            'followupsReassignUrl',
            'followupsRescheduleUrl',
            'followupsOutcomeUrl'
        ));
    }

    public function reassignConsultant(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'note_id' => ['required', 'integer', Rule::exists('notes', 'id')],
            'consultant' => [
                'required',
                'string',
                'max:120',
                Rule::exists('followup_consultants', 'slug')->where('status', 1),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $data = $validator->validated();

        $note = Note::query()->findOrFail($data['note_id']);

        if ($note->type !== 'client' || (int) $note->is_action !== 1 || (int) $note->status !== 0 || $note->task_group !== 'Followup') {
            return response()->json([
                'success' => false,
                'message' => 'This record cannot be reassigned.',
            ], 422);
        }

        $currentSlug = self::consultantSlugFromFollowupNoteTitle($note->title);
        if ($currentSlug === null) {
            return response()->json([
                'success' => false,
                'message' => 'Consultant could not be determined from this follow-up.',
            ], 422);
        }

        $oldConsultantDisp = self::consultantDisplayForSlug($currentSlug);

        $newSlug = $data['consultant'];
        if ($newSlug === $currentSlug) {
            return response()->json([
                'success' => true,
                'message' => 'Consultant unchanged.',
                'redirect' => route('followups.calendar', ['consultant' => $newSlug]),
            ]);
        }

        $newTitle = self::followupNoteTitle($newSlug);
        if ($newTitle === null) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid consultant.',
            ], 422);
        }

        $consultantDisplay = self::consultantDisplayForSlug($newSlug);
        $newConsultantLi = '<li><strong>Consultant:</strong> '.htmlspecialchars($consultantDisplay, ENT_QUOTES, 'UTF-8').'</li>';

        $oldTitle = $note->title;
        $updatedHtml = preg_replace(
            '#<li><strong>Consultant:</strong>\s*.*?</li>#si',
            $newConsultantLi,
            (string) $note->description,
            1
        );

        if ($updatedHtml === null) {
            $updatedHtml = (string) $note->description;
        }

        DB::transaction(function () use ($note, $newTitle, $updatedHtml, $oldTitle): void {
            $note->title = $newTitle;
            $note->description = $updatedHtml;
            $note->save();
            $this->syncActivitiesLogAfterConsultantChange($note, $oldTitle);
        });

        $note->refresh();
        $newConsultantDisp = self::consultantDisplayForSlug($newSlug);
        $this->logFollowupConsultantChangedActivity($note, $oldConsultantDisp, $newConsultantDisp);

        return response()->json([
            'success' => true,
            'message' => 'Consultant updated. This follow-up now appears on the selected consultant’s calendar.',
            'redirect' => route('followups.calendar', ['consultant' => $newSlug]),
        ]);
    }

    /** Any authenticated CRM user may reschedule/set outcome; still validates note shape. */
    protected function authorizeFollowupNoteForEditor(Note $note): ?JsonResponse
    {
        if ($note->type !== 'client' || (int) $note->is_action !== 1 || $note->task_group !== 'Followup') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid follow-up record.',
            ], 422);
        }

        return null;
    }

    protected function assertCanAccessFollowupNoteOrAbort(Note $note): void
    {
        if ($note->type !== 'client' || (int) $note->is_action !== 1 || $note->task_group !== 'Followup') {
            abort(404);
        }
    }

    protected function syncActivitiesLogFollowupDate(Note $note, string $needleInDescription): void
    {
        if (! Schema::hasTable('activities_logs') || ! Schema::hasColumn('activities_logs', 'followup_date')) {
            return;
        }

        $logId = $this->findScheduledFollowupActivityLogId($note, $needleInDescription);
        if ($logId === null) {
            return;
        }

        DB::table('activities_logs')->where('id', $logId)->update([
            'followup_date' => $note->action_assign_date,
        ]);
    }

    public function rescheduleFollowup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'note_id' => ['required', 'integer', Rule::exists('notes', 'id')],
            'followup_datetime' => ['required', 'string', 'max:40'],
            'calendar_consultant' => ['nullable', 'string', Rule::in(['ankit', 'rakshita', 'jaspreet', 'syed'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $data = $validator->validated();

        $note = Note::query()->findOrFail($data['note_id']);

        $blocked = $this->authorizeFollowupNoteForEditor($note);
        if ($blocked !== null) {
            return $blocked;
        }

        if ((int) $note->status !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Only open follow-ups can be rescheduled.',
            ], 422);
        }

        try {
            $parsed = Carbon::parse($data['followup_datetime'], config('app.timezone'));
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date or time.',
            ], 422);
        }

        $slug = self::consultantSlugFromFollowupNoteTitle($note->title);
        if ($slug === null) {
            return response()->json([
                'success' => false,
                'message' => 'Consultant could not be determined for this follow-up.',
            ], 422);
        }

        $slotHm = $parsed->format('H:i');
        $dateOnly = $parsed->format('Y-m-d');

        if (! FollowupAvailability::isValidSlotSelection($slug, $dateOnly, $slotHm, 'free', (int) $note->id)) {
            return response()->json([
                'success' => false,
                'message' => 'That date and time is not available for this consultant.',
            ], 422);
        }

        $needle = $note->title;
        $previousAssign = (string) $note->action_assign_date;
        $note->action_assign_date = $parsed->format('Y-m-d H:i:s');
        $note->save();

        $noteFresh = $note->fresh();
        $this->syncActivitiesLogFollowupDate($noteFresh, $needle);
        $this->logFollowupRescheduledActivity($noteFresh, $previousAssign);

        $calendarSlug = $data['calendar_consultant'] ?? $slug;

        return response()->json([
            'success' => true,
            'message' => 'Follow-up date and time updated.',
            'redirect' => route('followups.calendar', ['consultant' => $calendarSlug]),
        ]);
    }

    public function setFollowupOutcome(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'note_id' => ['required', 'integer', Rule::exists('notes', 'id')],
            'outcome' => ['required', 'string', 'in:confirmed,completed,cancelled,no_show'],
            'calendar_consultant' => ['nullable', 'string', Rule::in(['ankit', 'rakshita', 'jaspreet', 'syed'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $data = $validator->validated();

        if (! Schema::hasColumn('notes', 'followup_outcome')) {
            return response()->json([
                'success' => false,
                'message' => 'Please run database migrations (followup_outcome on notes).',
            ], 503);
        }

        $note = Note::query()->findOrFail($data['note_id']);

        $blocked = $this->authorizeFollowupNoteForEditor($note);
        if ($blocked !== null) {
            return $blocked;
        }

        switch ($data['outcome']) {
            case 'confirmed':
                $note->status = 0;
                $note->followup_outcome = null;
                break;
            case 'completed':
                $note->status = 1;
                $note->followup_outcome = 'completed';
                break;
            case 'cancelled':
                $note->status = 1;
                $note->followup_outcome = 'cancelled';
                break;
            case 'no_show':
                $note->status = 1;
                $note->followup_outcome = 'no_show';
                break;
        }

        $note->save();

        $note->refresh();
        $this->logFollowupStatusChangedActivity($note, (string) $data['outcome']);

        $calendarSlug = $data['calendar_consultant'] ?? self::consultantSlugFromFollowupNoteTitle($note->title) ?? 'ankit';

        return response()->json([
            'success' => true,
            'message' => 'Follow-up status updated.',
            'redirect' => route('followups.calendar', ['consultant' => $calendarSlug]),
        ]);
    }

    /**
     * Standard list rows matching schedule-follow-up activity HTML (see ClientActionController::scheduleFollowupStore).
     */
    protected function followupActivityStandardListItems(Note $note, bool $includeConsultant = true, bool $includeFollowTiming = true): string
    {
        $parsed = self::parseScheduledFollowupNoteHtml((string) $note->description);
        $slug = self::consultantSlugFromFollowupNoteTitle($note->title);
        $consultant = $slug ? self::consultantDisplayForSlug($slug) : ($parsed['consultant_display'] ?: '—');

        $html = '';
        if ($includeFollowTiming) {
            try {
                $timingPretty = Carbon::parse($note->action_assign_date)->timezone(config('app.timezone'))->format('j M Y, g:i a');
            } catch (\Throwable $e) {
                $timingPretty = $note->action_assign_date ? (string) $note->action_assign_date : '—';
            }
            $html .= '<li><strong>Follow timing:</strong> '.e($timingPretty).'</li>';
        }

        $html .= '<li><strong>Follow-up type:</strong> '.e($parsed['followup_type'] ?: '—').'</li>'
            .'<li><strong>Service:</strong> '.e(self::serviceLabelForActivityLog($parsed['service'] ?: '')).'</li>';
        if ($includeConsultant) {
            $html .= '<li><strong>Consultant:</strong> '.e($consultant).'</li>';
        }
        $html .= '<li><strong>Follow-up details:</strong> '.e($parsed['followup_detail'] ?: '—').'</li>'
            .'<li><strong>Preferred language:</strong> '.e($parsed['preferred_language'] ?: '—').'</li>';

        return $html;
    }

    protected function followupActivityDetailsParagraph(Note $note): string
    {
        $parsed = self::parseScheduledFollowupNoteHtml((string) $note->description);
        $detailsSafe = ($parsed['details_plain'] ?? '') !== '' ? nl2br(e($parsed['details_plain'])) : '—';

        return '<p><strong>Details:</strong></p><p>'.$detailsSafe.'</p>';
    }

    /**
     * Persist a client activity row in the same shape as “Scheduled follow-up”.
     */
    protected function logFollowupClientActivity(Note $note, string $subjectLine, string $innerBodyHtml): void
    {
        if (! Schema::hasTable('activities_logs')) {
            return;
        }

        try {
            $log = new ActivitiesLog;
            $log->client_id = $note->client_id;
            $log->created_by = Auth::id();
            $log->subject = $subjectLine;
            $log->description = '<span class="text-semi-bold">'.e($note->title).'</span><p>'.$innerBodyHtml.'</p>';
            $log->use_for = null;
            if (Schema::hasColumn('activities_logs', 'followup_date')) {
                $log->followup_date = $note->action_assign_date;
            }
            if (Schema::hasColumn('activities_logs', 'task_group')) {
                $log->task_group = 'Followup';
            }
            $log->task_status = 0;
            $log->pin = 0;
            $log->save();
        } catch (\Throwable $e) {
            Log::warning('FollowupController: activity log failed: '.$e->getMessage());
        }
    }

    protected function logFollowupRescheduledActivity(Note $note, string $previousActionAssignDate): void
    {
        $slug = self::consultantSlugFromFollowupNoteTitle($note->title);
        $consultantDisplay = $slug ? self::consultantDisplayForSlug($slug) : '—';

        try {
            $newDt = Carbon::parse($note->action_assign_date)->timezone(config('app.timezone'));
            $oldDt = Carbon::parse($previousActionAssignDate)->timezone(config('app.timezone'));
        } catch (\Throwable $e) {
            $newDt = Carbon::parse((string) $note->action_assign_date);
            $oldDt = Carbon::parse($previousActionAssignDate);
        }

        $newPretty = $newDt->format('j M Y, g:i a');
        $oldPretty = $oldDt->format('j M Y, g:i a');

        $inner = '<p><strong>Rescheduled follow-up</strong></p>'
            .'<ul>'
            .$this->followupActivityStandardListItems($note, true, false)
            .'<li><strong>New date &amp; time:</strong> '.e($newPretty).'</li>'
            .'<li><strong>Previous date &amp; time:</strong> '.e($oldPretty).'</li>'
            .'</ul>'
            .$this->followupActivityDetailsParagraph($note);

        $this->logFollowupClientActivity($note, 'Rescheduled follow-up ('.$consultantDisplay.')', $inner);
    }

    protected function logFollowupConsultantChangedActivity(Note $note, string $previousConsultantDisplay, string $newConsultantDisplay): void
    {
        $inner = '<p><strong>Follow-up consultant changed</strong></p>'
            .'<ul>'
            .'<li><strong>Previous consultant:</strong> '.e($previousConsultantDisplay ?: '—').'</li>'
            .'<li><strong>New consultant:</strong> '.e($newConsultantDisplay ?: '—').'</li>'
            .$this->followupActivityStandardListItems($note, false)
            .'</ul>'
            .$this->followupActivityDetailsParagraph($note);

        $this->logFollowupClientActivity($note, 'Follow-up consultant changed ('.$newConsultantDisplay.')', $inner);
    }

    protected function logFollowupStatusChangedActivity(Note $note, string $outcome): void
    {
        $slug = self::consultantSlugFromFollowupNoteTitle($note->title);
        $consultantDisplay = $slug ? self::consultantDisplayForSlug($slug) : '—';

        $statusLabel = match ($outcome) {
            'confirmed' => 'Confirmed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'no_show' => 'No show',
            default => ucfirst(str_replace('_', ' ', $outcome)),
        };

        $inner = '<p><strong>Follow-up status updated</strong></p>'
            .'<ul>'
            .'<li><strong>Status:</strong> '.e($statusLabel).'</li>'
            .$this->followupActivityStandardListItems($note)
            .'</ul>'
            .$this->followupActivityDetailsParagraph($note);

        $this->logFollowupClientActivity($note, 'Follow-up status: '.$statusLabel.' ('.$consultantDisplay.')', $inner);
    }
}
