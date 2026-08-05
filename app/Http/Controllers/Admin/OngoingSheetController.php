<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Staff;
use App\Support\StaffClientVisibility;
use App\Models\ActivitiesLog;
use App\Models\Application;
use App\Models\ApplicationActivitiesLog;
use App\Models\ApplicationReminder;
use App\Models\ClientOngoingReference;
use App\Models\Branch;
use App\Models\CheckinLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OngoingSheetController extends Controller
{
    public const SHEET_TYPES = ['ongoing', 'coe_enrolled', 'discontinue', 'refund', 'checklist'];

    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /** Session key for persisting ongoing sheet filters (legacy) */
    const FILTER_SESSION_KEY = 'ongoing_sheet_filters';

    /** @var string|null Session key for current request (set in index) */
    protected $currentFilterSessionKey;

    /**
     * Config for each sheet type: title, route name, session key.
     */
    public static function getSheetConfig(string $sheetType): array
    {
        $configs = [
            'ongoing'       => ['title' => 'Ongoing Sheet', 'route' => 'clients.sheets.ongoing', 'session_key' => 'ongoing_sheet_filters'],
            'coe_enrolled' => ['title' => 'COE Issued & Enrolled', 'route' => 'clients.sheets.coe-enrolled', 'session_key' => 'coe_enrolled_sheet_filters'],
            'discontinue'   => ['title' => 'Discontinue', 'route' => 'clients.sheets.discontinue', 'session_key' => 'discontinue_sheet_filters'],
            'refund'        => ['title' => 'Refund', 'route' => 'clients.sheets.refund', 'session_key' => 'refund_sheet_filters'],
            'checklist'    => ['title' => 'Checklist', 'route' => 'clients.sheets.checklist', 'session_key' => 'checklist_sheet_filters'],
        ];
        return $configs[$sheetType] ?? $configs['ongoing'];
    }

    /**
     * Display a sheet (Ongoing, COE Issued & Enrolled, or Discontinue).
     */
    public function index(Request $request, $sheetType = null)
    {
        $sheetType = $sheetType ?? $request->route('sheetType', 'ongoing');
        if (!in_array($sheetType, self::SHEET_TYPES, true)) {
            $sheetType = 'ongoing';
        }
        $config = self::getSheetConfig($sheetType);
        $this->currentFilterSessionKey = $config['session_key'];

        // Clear stored filters when user explicitly requests it (Reset / Clear Filters)
        if ($request->has('clear_filters')) {
            session()->forget($this->currentFilterSessionKey);
            return redirect()->route($config['route']);
        }

        // Restore filters from session when the URL has no explicit filter params.
        // Redirect so the address bar matches applied filters (clear/edit without surprise).
        if (! $this->requestHasExplicitSheetFilters($request)) {
            $stored = session($this->currentFilterSessionKey, []);
            if ($this->sessionHasRestorableSheetFilters($stored)) {
                return redirect()->route(
                    $config['route'],
                    $this->buildSheetFilterRedirectQuery($request, $stored)
                );
            }
        }

        // Restrict assignee filter to configured staff; default to self when allowed, else "all"
        $this->normalizeSheetAssigneeFilter($request);

        // Pagination
        $perPage = (int) $request->get('per_page', 50);
        $allowedPerPage = [10, 25, 50, 100, 200];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 50;
        }

        // Remember filters for the next bare visit (return/back still preserves selection)
        $this->persistFiltersToSession($request);

        // Build base query (depends on sheet type)
        $query = $this->buildBaseQuery($request, $sheetType);
        $staffUser = Auth::guard('admin')->user();
        if ($staffUser instanceof Staff) {
            StaffClientVisibility::restrictApplicationsToVisibleClients($query, $staffUser);
        }

        // Apply filters
        $query = $this->applyFilters($query, $request, $sheetType);

        // Apply sorting
        $query = $this->applySorting($query, $request, $sheetType);

        // Get rows (paginate)
        $rows = $query->paginate($perPage)->appends($request->except('page'));

        // Dropdown data for top-bar assignee filter (fixed counsellor list from config)
        $branches = Branch::orderBy('office_name')->get(['id', 'office_name']);
        $assignees = $this->getSheetAssigneeFilterOptions();
        // Full staff list for Change assignee modal (active only, same as Application tab)
        $assigneesForChangeModal = \App\Models\Staff::where('status', 1)
            ->orderBy('first_name')->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);
        $currentStages = $this->getCurrentStagesForSheet($sheetType);
        $activeFilterCount = $this->countActiveFilters($request, $sheetType);
        $showStageEntryDateFilters = $sheetType === 'coe_enrolled' && in_array((int) Auth::user()->role, [1, 12], true);

        return view('Admin.sheets.ongoing', compact(
            'rows',
            'perPage',
            'activeFilterCount',
            'branches',
            'assignees',
            'assigneesForChangeModal',
            'currentStages',
            'sheetType',
            'showStageEntryDateFilters'
        ) + [
            'sheetTitle' => $config['title'],
            'sheetRoute' => $config['route'],
        ]);
    }

    /**
     * True when the request includes an intentional filter selection (not just page size/sort).
     * Used to decide whether session filters should be restored.
     */
    protected function requestHasExplicitSheetFilters(Request $request): bool
    {
        $filterParams = [
            'branch',
            'assignee',
            'current_stage',
            'visa_expiry_from',
            'visa_expiry_to',
            'stage_entry_from',
            'stage_entry_to',
            'search',
        ];

        foreach ($filterParams as $key) {
            if (! $request->has($key)) {
                continue;
            }
            $value = $request->input($key);
            if (is_array($value)) {
                if ($this->nonEmptyFilterValues($value) !== []) {
                    return true;
                }
                continue;
            }
            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether stored session data should be reapplied on a bare sheet visit.
     */
    protected function sessionHasRestorableSheetFilters(array $stored): bool
    {
        if ($stored === []) {
            return false;
        }

        foreach (['branch', 'assignee', 'current_stage', 'visa_expiry_from', 'visa_expiry_to', 'stage_entry_from', 'stage_entry_to', 'search'] as $key) {
            if (! array_key_exists($key, $stored)) {
                continue;
            }
            $value = $stored[$key];
            if (is_array($value)) {
                if ($this->nonEmptyFilterValues($value) !== []) {
                    return true;
                }
                continue;
            }
            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Build query params for session restore redirect (filters + current page/sort/per_page).
     */
    protected function buildSheetFilterRedirectQuery(Request $request, array $stored): array
    {
        $query = [];

        foreach (['assignee', 'current_stage', 'visa_expiry_from', 'visa_expiry_to', 'stage_entry_from', 'stage_entry_to', 'search', 'per_page', 'sort', 'direction'] as $key) {
            if (! array_key_exists($key, $stored)) {
                continue;
            }
            $value = $stored[$key];
            if ($value === null || $value === '' || (is_array($value) && $value === [])) {
                continue;
            }
            $query[$key] = $value;
        }

        if (array_key_exists('branch', $stored)) {
            $branch = $this->nonEmptyFilterValues((array) $stored['branch']);
            if ($branch !== []) {
                $query['branch'] = $branch;
            }
        }

        // Prefer live request presentation params when present (e.g. page=2 on bare URL + session filters)
        foreach (['page', 'per_page', 'sort', 'direction'] as $key) {
            if ($request->filled($key)) {
                $query[$key] = $request->input($key);
            }
        }

        return $query;
    }

    /**
     * @param  array<int|string|null>  $values
     * @return list<int|string>
     */
    protected function nonEmptyFilterValues(array $values): array
    {
        return array_values(array_filter($values, static function ($v) {
            return $v !== null && $v !== '';
        }));
    }

    /**
     * Persist current filter values to session for next visit.
     */
    protected function persistFiltersToSession(Request $request): void
    {
        $payload = [
            'branch' => $request->input('branch'),
            'assignee' => $request->input('assignee'),
            'current_stage' => $request->input('current_stage'),
            'visa_expiry_from' => $request->input('visa_expiry_from'),
            'visa_expiry_to' => $request->input('visa_expiry_to'),
            'stage_entry_from' => $request->input('stage_entry_from'),
            'stage_entry_to' => $request->input('stage_entry_to'),
            'search' => $request->input('search'),
            'per_page' => $request->input('per_page'),
            'sort' => $request->input('sort'),
            'direction' => $request->input('direction'),
        ];
        $payload = array_filter($payload, function ($v) {
            if (is_array($v)) {
                return $this->nonEmptyFilterValues($v) !== [];
            }
            return $v !== null && $v !== '';
        });
        $key = $this->currentFilterSessionKey ?? self::FILTER_SESSION_KEY;
        session()->put($key, $payload);
    }

    /**
     * Staff ids allowed in the sheet top-bar assignee filter dropdown.
     *
     * @return list<int>
     */
    protected function getSheetAssigneeFilterStaffIds(): array
    {
        $ids = config('sheets.sheet_assignee_filter_staff_ids', []);

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $ids), static fn (int $id) => $id > 0));
    }

    /**
     * Default assignee to logged-in user when they are in the allowed list; otherwise "all".
     * Reject assignee values outside the allowed list (e.g. stale session).
     */
    protected function normalizeSheetAssigneeFilter(Request $request): void
    {
        $allowedIds = $this->getSheetAssigneeFilterStaffIds();
        $assignee = $request->input('assignee');

        if ($assignee === null || $assignee === '') {
            $default = in_array((int) Auth::id(), $allowedIds, true)
                ? (string) Auth::id()
                : 'all';
            $request->merge(['assignee' => $default]);

            return;
        }

        if ($assignee !== 'all' && ! in_array((int) $assignee, $allowedIds, true)) {
            $request->merge(['assignee' => 'all']);
        }
    }

    /**
     * Options for #ongoing-assignee-bar — fixed staff list, alphabetical by display name.
     */
    protected function getSheetAssigneeFilterOptions()
    {
        $ids = $this->getSheetAssigneeFilterStaffIds();
        if ($ids === []) {
            return collect();
        }

        $staff = Staff::query()
            ->where('status', 1)
            ->whereIn('id', $ids)
            ->get(['id', 'first_name', 'last_name', 'email']);

        return $staff
            ->sortBy(fn ($member) => strtolower(trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: ($member->email ?? '')))
            ->values();
    }

    /**
     * Stage dropdown options for the current sheet type.
     */
    /**
     * Early stage names for Checklist sheet (first-stage / follow-up).
     * Returns lowercase list from config for case-insensitive matching.
     */
    protected function getChecklistEarlyStages(): array
    {
        $stages = config('sheets.checklist_early_stages', []);
        return array_values(array_map(function ($s) {
            return strtolower(trim((string) $s));
        }, is_array($stages) ? $stages : []));
    }

    /**
     * Stage filter options for the current sheet (config + DB).
     * Config supplies preferred order and “always show” labels; DB adds any stages
     * that appear on the sheet but are missing from config (e.g. “Offer letter sent”).
     */
    protected function getCurrentStagesForSheet(string $sheetType): \Illuminate\Support\Collection
    {
        $key = match ($sheetType) {
            'coe_enrolled' => 'sheets.coe_enrolled_stages',
            'discontinue'   => 'sheets.discontinue_stages',
            'refund'        => 'sheets.refund_stages',
            'checklist'     => 'sheets.checklist_early_stages',
            default         => 'sheets.ongoing_stages',
        };
        $stages = config($key, []);
        if (!is_array($stages)) {
            $stages = [];
        }
        $fromConfig = collect($stages)
            ->filter(fn ($s) => $s !== null && trim((string) $s) !== '')
            ->values()
            ->mapWithKeys(fn ($s) => [trim((string) $s) => trim((string) $s)]);

        $fromDb = $this->getCurrentStagesFromDatabase($sheetType);

        if ($fromConfig->isEmpty()) {
            return $fromDb;
        }

        return $this->mergeStageFilterOptions($fromConfig, $fromDb);
    }

    /**
     * Config stages first (deduped case-insensitively), then remaining DB stages sorted by label.
     *
     * @param  \Illuminate\Support\Collection<string, string>  $fromConfig
     * @param  \Illuminate\Support\Collection<string, string>  $fromDb
     */
    protected function mergeStageFilterOptions(\Illuminate\Support\Collection $fromConfig, \Illuminate\Support\Collection $fromDb): \Illuminate\Support\Collection
    {
        $seen = [];
        $ordered = collect();
        foreach ($fromConfig as $value => $label) {
            $norm = strtolower(trim((string) $value));
            if ($norm === '') {
                continue;
            }
            $seen[$norm] = true;
            $ordered->put($value, $label);
        }
        $extras = collect();
        foreach ($fromDb as $value => $label) {
            $norm = strtolower(trim((string) $value));
            if ($norm === '' || isset($seen[$norm])) {
                continue;
            }
            $seen[$norm] = true;
            $extras->put($value, $label);
        }

        return $ordered->merge($extras->sortBy(fn ($l) => strtolower((string) $l), SORT_NATURAL));
    }

    /**
     * Distinct stage values from applications on this sheet (same rules as the table query).
     */
    protected function getCurrentStagesFromDatabase(string $sheetType): \Illuminate\Support\Collection
    {
        $query = $this->buildBaseQuery(new Request(), $sheetType);

        $staffUser = Auth::guard('admin')->user();
        if ($staffUser instanceof Staff) {
            StaffClientVisibility::restrictApplicationsToVisibleClients($query, $staffUser);
        }

        return $query
            ->whereNotNull('applications.stage')
            ->whereRaw('TRIM(applications.stage) <> ?', [''])
            ->select('applications.stage')
            ->distinct()
            ->orderBy('applications.stage')
            ->pluck('applications.stage', 'applications.stage');
    }

    /**
     * Build base query: one row per application (application-focused sheet).
     * Criteria depend on sheet type: ongoing, coe_enrolled, or discontinue.
     */
    protected function buildBaseQuery(Request $request, string $sheetType = 'ongoing')
    {
        $query = Application::query()
            ->select([
                'applications.id as application_id',
                'applications.created_at as application_created_at',
                'applications.stage as application_stage',
                'products.name as course_name',
                'admins.id as client_id',
                'admins.type as client_type',
                'admins.client_id as crm_ref',
                'admins.first_name',
                'admins.last_name',
                'admins.dob',
                'admins.visaexpiry',
                'admins.visa_type',
                'admins.visa_opt',
                'admins.office_id',
                'partners.partner_name',
                'branches.office_name as branch_name',
                'applications.user_id as assignee_id',
                'assignee.first_name as assignee_first_name',
                'assignee.last_name as assignee_last_name',
                'ongoing.current_status',
                'ongoing.payment_display_note',
                'ongoing.institute_override',
                'ongoing.visa_category_override',
                DB::raw("(SELECT COALESCE(SUM(acr.deposit_amount), 0) 
                         FROM account_client_receipts acr 
                         WHERE acr.client_id = admins.id 
                         AND (acr.receipt_type = 1 OR acr.receipt_type = 2)
                         AND (acr.void_invoice = 0 OR acr.void_invoice IS NULL)
                         AND (
                           acr.application_id = applications.id
                           OR (
                             acr.application_id IS NULL 
                             AND (SELECT COUNT(*) FROM applications a2 WHERE a2.client_id = admins.id AND a2.status NOT IN (2, 8)) = 1
                           )
                         )) as total_payment"),
                DB::raw("(SELECT CASE 
                         WHEN (SELECT COALESCE(SUM(acr2.deposit_amount), 0) FROM account_client_receipts acr2 
                               WHERE acr2.client_id = admins.id 
                               AND (acr2.receipt_type = 1 OR acr2.receipt_type = 2)
                               AND (acr2.void_invoice = 0 OR acr2.void_invoice IS NULL)
                               AND (acr2.application_id = applications.id
                                    OR (acr2.application_id IS NULL 
                                        AND (SELECT COUNT(*) FROM applications a2 
                                             WHERE a2.client_id = admins.id AND a2.status NOT IN (2, 8)) = 1))) <= 0 
                         THEN 0
                         WHEN (SELECT COUNT(*) FROM account_client_receipts acr2 
                               WHERE acr2.client_id = admins.id 
                               AND (acr2.receipt_type = 1 OR acr2.receipt_type = 2)
                               AND (acr2.void_invoice = 0 OR acr2.void_invoice IS NULL)
                               AND (acr2.application_id = applications.id
                                    OR (acr2.application_id IS NULL 
                                        AND (SELECT COUNT(*) FROM applications a2 
                                             WHERE a2.client_id = admins.id AND a2.status NOT IN (2, 8)) = 1))) = 
                             (SELECT COUNT(*) FROM account_client_receipts acr2 
                              WHERE acr2.client_id = admins.id 
                              AND (acr2.receipt_type = 1 OR acr2.receipt_type = 2)
                              AND (acr2.void_invoice = 0 OR acr2.void_invoice IS NULL)
                              AND acr2.payment_method = 'Paid to College'
                              AND (acr2.application_id = applications.id
                                   OR (acr2.application_id IS NULL 
                                       AND (SELECT COUNT(*) FROM applications a2 
                                            WHERE a2.client_id = admins.id AND a2.status NOT IN (2, 8)) = 1)))
                         THEN 1
                         ELSE 0
                         END) as is_paid_to_college"),
                DB::raw('(SELECT edu_college 
                         FROM client_service_takens 
                         WHERE client_id = admins.id 
                         ORDER BY id DESC 
                         LIMIT 1) as service_college'),
                DB::raw("(SELECT aal.comment FROM application_activities_logs aal 
                         WHERE aal.app_id = applications.id AND aal.type = 'sheet_comment' 
                         ORDER BY aal.updated_at DESC LIMIT 1) as sheet_comment_text")
            ]);
        if ($sheetType === 'checklist') {
            $query->addSelect('applications.checklist_sheet_status', 'applications.checklist_sent_at')
                ->addSelect(DB::raw("(SELECT MAX(ar.reminded_at) FROM application_reminders ar WHERE ar.application_id = applications.id AND ar.type = 'email') as email_reminder_latest"))
                ->addSelect(DB::raw("(SELECT COUNT(*) FROM application_reminders ar WHERE ar.application_id = applications.id AND ar.type = 'email') as email_reminder_count"))
                ->addSelect(DB::raw("(SELECT MAX(ar.reminded_at) FROM application_reminders ar WHERE ar.application_id = applications.id AND ar.type = 'sms') as sms_reminder_latest"))
                ->addSelect(DB::raw("(SELECT COUNT(*) FROM application_reminders ar WHERE ar.application_id = applications.id AND ar.type = 'sms') as sms_reminder_count"))
                ->addSelect(DB::raw("(SELECT MAX(ar.reminded_at) FROM application_reminders ar WHERE ar.application_id = applications.id AND ar.type = 'phone') as phone_reminder_latest"))
                ->addSelect(DB::raw("(SELECT COUNT(*) FROM application_reminders ar WHERE ar.application_id = applications.id AND ar.type = 'phone') as phone_reminder_count"));
        }
        $query
            ->join('admins', 'applications.client_id', '=', 'admins.id')
            ->leftJoin('products', 'applications.product_id', '=', 'products.id')
            ->leftJoin('partners', 'applications.partner_id', '=', 'partners.id')
            ->leftJoin('branches', 'admins.office_id', '=', 'branches.id')
            ->leftJoin('staff as assignee', 'applications.user_id', '=', 'assignee.id')
            ->leftJoin('client_ongoing_references as ongoing', 'ongoing.client_id', '=', 'admins.id')
            ->where('admins.is_archived', 0)
            ->whereNull('admins.is_deleted');

        if ($sheetType === 'discontinue') {
            $query->where(function ($q) {
                $q->where('applications.status', 2)
                  ->orWhereRaw('LOWER(TRIM(applications.stage)) = ?', ['coe cancelled']);
            });
        } elseif ($sheetType === 'refund') {
            $query->where('applications.status', 8); // 8 = Refund
        } else {
            $query->whereNotIn('applications.status', [2, 8]);
            if ($sheetType === 'coe_enrolled') {
                $query->whereRaw('LOWER(TRIM(applications.stage)) IN (?, ?)', ['coe issued', 'enrolled']);
            } elseif ($sheetType === 'checklist') {
                // Checklist (first-stage / follow-up sheet): applications in early stages only, with or without follow-up.
                // Status convert_to_client / discontinue = row moves to other sheets.
                $earlyStages = $this->getChecklistEarlyStages();
                if (!empty($earlyStages)) {
                    $placeholders = implode(',', array_fill(0, count($earlyStages), '?'));
                    $query->whereRaw('LOWER(TRIM(applications.stage)) IN (' . $placeholders . ')', $earlyStages);
                } else {
                    $query->whereRaw('1 = 0'); // no early stages configured: show nothing
                }
                $query->where(function ($q) {
                    $q->whereNull('applications.checklist_sheet_status')
                      ->orWhereIn('applications.checklist_sheet_status', ['active', 'hold']);
                });
            } else {
                // Ongoing: exclude COE/Enrolled/Cancelled and also "Awaiting document" (that stage is Checklist only)
                $query->whereRaw('LOWER(TRIM(applications.stage)) NOT IN (?, ?, ?, ?)', [
                    'coe issued',
                    'enrolled',
                    'coe cancelled',
                    'awaiting document',
                ]);
            }
        }

        return $query;
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, Request $request, string $sheetType = 'ongoing')
    {
        // Branch filter (client's office/branch; multi-select)
        if ($request->filled('branch')) {
            $branchIds = is_array($request->input('branch'))
                ? $request->input('branch')
                : [$request->input('branch')];
            $query->whereIn('admins.office_id', $branchIds);
        }

        // Assignee filter ("all" = no filter)
        if ($request->filled('assignee') && $request->input('assignee') !== 'all') {
            $query->where('applications.user_id', $request->input('assignee'));
        }

        // Current stage filter (case-insensitive to handle config vs DB casing differences)
        if ($request->filled('current_stage')) {
            $stage = trim((string) $request->input('current_stage'));
            if ($stage !== '') {
                $query->whereRaw('LOWER(TRIM(applications.stage)) = ?', [strtolower($stage)]);
            }
        }

        // Visa expiry date range
        if ($request->filled('visa_expiry_from')) {
            try {
                $fromDate = Carbon::createFromFormat('d/m/Y', $request->input('visa_expiry_from'))->startOfDay();
                $query->whereDate('admins.visaexpiry', '>=', $fromDate);
            } catch (\Exception $e) {
                // Ignore invalid date format
            }
        }

        if ($request->filled('visa_expiry_to')) {
            try {
                $toDate = Carbon::createFromFormat('d/m/Y', $request->input('visa_expiry_to'))->endOfDay();
                $query->whereDate('admins.visaexpiry', '<=', $toDate);
            } catch (\Exception $e) {
                // Ignore invalid date format
            }
        }

        // COE Enrolled only (superadmin/admin): filter by date when course entered Coe Issued or Enrolled stage (uses application updated_at as proxy)
        if ($sheetType === 'coe_enrolled' && in_array((int) Auth::user()->role, [1, 12], true)) {
            if ($request->filled('stage_entry_from')) {
                try {
                    $fromDate = Carbon::createFromFormat('d/m/Y', $request->input('stage_entry_from'))->startOfDay();
                    $query->whereDate('applications.updated_at', '>=', $fromDate);
                } catch (\Exception $e) {
                    // Ignore invalid date format
                }
            }
            if ($request->filled('stage_entry_to')) {
                try {
                    $toDate = Carbon::createFromFormat('d/m/Y', $request->input('stage_entry_to'))->endOfDay();
                    $query->whereDate('applications.updated_at', '<=', $toDate);
                } catch (\Exception $e) {
                    // Ignore invalid date format
                }
            }
        }

        // Search (name, CRM ref, current status, stage)
        // Full names ("Jaspreet Singh") must match combined first+last, not only single columns.
        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            if ($term !== '') {
                $like = '%' . strtolower($term) . '%';
                $fullNameExpr = DB::getDriverName() === 'pgsql'
                    ? "LOWER(TRIM(COALESCE(admins.first_name, '') || ' ' || COALESCE(admins.last_name, '')))"
                    : "LOWER(TRIM(CONCAT(COALESCE(admins.first_name, ''), ' ', COALESCE(admins.last_name, ''))))";
                $fullNameReverseExpr = DB::getDriverName() === 'pgsql'
                    ? "LOWER(TRIM(COALESCE(admins.last_name, '') || ' ' || COALESCE(admins.first_name, '')))"
                    : "LOWER(TRIM(CONCAT(COALESCE(admins.last_name, ''), ' ', COALESCE(admins.first_name, ''))))";

                $query->where(function ($q) use ($like, $term, $fullNameExpr, $fullNameReverseExpr) {
                    $q->whereRaw('LOWER(admins.first_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(admins.last_name) LIKE ?', [$like])
                        ->orWhereRaw($fullNameExpr . ' LIKE ?', [$like])
                        ->orWhereRaw($fullNameReverseExpr . ' LIKE ?', [$like])
                        ->orWhereRaw('LOWER(admins.client_id) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(ongoing.current_status) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(applications.stage) LIKE ?', [$like]);

                    // Multi-word names: each token must appear in first or last name
                    $words = preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY);
                    if (is_array($words) && count($words) > 1) {
                        $q->orWhere(function ($nameQ) use ($words) {
                            foreach ($words as $word) {
                                $wordLike = '%' . strtolower($word) . '%';
                                $nameQ->where(function ($part) use ($wordLike) {
                                    $part->whereRaw('LOWER(admins.first_name) LIKE ?', [$wordLike])
                                        ->orWhereRaw('LOWER(admins.last_name) LIKE ?', [$wordLike]);
                                });
                            }
                        });
                    }
                });
            }
        }

        return $query;
    }

    /**
     * Apply sorting to query. Rows for the same client always stay together:
     * order by sort column (client-level), then client id, then application id.
     * For checklist sheet: Hold status rows sort to the bottom.
     *
     * Sort keys are whitelisted; unknown sort falls back to CRM ref (admins.client_id).
     */
    protected function applySorting($query, Request $request, string $sheetType = 'ongoing')
    {
        $sortField = (string) $request->get('sort', 'client_id');
        $sortDirection = strtolower((string) $request->get('direction', 'asc'));

        if (!in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'asc';
        }

        // Checklist: Hold rows at the bottom (order by hold last)
        if ($sheetType === 'checklist') {
            $query->orderByRaw("CASE WHEN COALESCE(applications.checklist_sheet_status, 'active') = 'hold' THEN 1 ELSE 0 END ASC");
        }

        switch ($sortField) {
            case 'course_name':
                $query->orderBy('products.name', $sortDirection);
                break;
            case 'crm_ref':
            case 'client_id':
                $query->orderBy('admins.client_id', $sortDirection);
                break;
            case 'created_at':
                $query->orderBy('applications.created_at', $sortDirection);
                break;
            case 'name':
                $query->orderBy('admins.last_name', $sortDirection)
                    ->orderBy('admins.first_name', $sortDirection);
                break;
            case 'dob':
                $query->orderBy('admins.dob', $sortDirection);
                break;
            case 'total_payment':
                $query->orderBy('total_payment', $sortDirection);
                break;
            case 'institute':
                $query->orderByRaw('COALESCE(ongoing.institute_override, partners.partner_name, \'\') ' . $sortDirection);
                break;
            case 'branch':
                $query->orderBy('branches.office_name', $sortDirection);
                break;
            case 'assignee':
                $query->orderBy('assignee.last_name', $sortDirection)
                    ->orderBy('assignee.first_name', $sortDirection);
                break;
            case 'visa_expiry':
                $query->orderBy('admins.visaexpiry', $sortDirection);
                break;
            case 'visa_category':
                $query->orderByRaw(
                    'COALESCE(ongoing.visa_category_override, TRIM(CONCAT(COALESCE(admins.visa_type, \'\'), \' \', COALESCE(admins.visa_opt, \'\')))) ' . $sortDirection
                );
                break;
            case 'stage':
                $query->orderBy('applications.stage', $sortDirection);
                break;
            case 'application_id':
                $query->orderBy('applications.id', $sortDirection);
                break;
            case 'checklist_sent_at':
                if ($sheetType === 'checklist') {
                    $query->orderBy('applications.checklist_sent_at', $sortDirection);
                } else {
                    $query->orderBy('admins.client_id', $sortDirection);
                }
                break;
            case 'checklist_status':
                if ($sheetType === 'checklist') {
                    $query->orderBy('applications.checklist_sheet_status', $sortDirection);
                } else {
                    $query->orderBy('admins.client_id', $sortDirection);
                }
                break;
            default:
                $query->orderBy('admins.client_id', $sortDirection);
                break;
        }

        $query->orderBy('admins.id', 'asc')
            ->orderBy('applications.id', 'asc');

        return $query;
    }

    /**
     * Count active filters
     */
    protected function countActiveFilters(Request $request, string $sheetType = 'ongoing')
    {
        $count = 0;
        if ($request->filled('branch')) {
            $count++;
        }
        if ($request->filled('assignee') && $request->input('assignee') !== 'all') {
            $count++;
        }
        if ($request->filled('current_stage')) {
            $count++;
        }
        if ($request->filled('visa_expiry_from')) {
            $count++;
        }
        if ($request->filled('visa_expiry_to')) {
            $count++;
        }
        if ($sheetType === 'coe_enrolled' && in_array((int) Auth::user()->role, [1, 12], true)) {
            if ($request->filled('stage_entry_from')) {
                $count++;
            }
            if ($request->filled('stage_entry_to')) {
                $count++;
            }
        }
        if ($request->filled('search')) {
            $count++;
        }
        return $count;
    }

    /**
     * Display sheets insights: conversions, clients seen, discontinues by assignee.
     */
    public function sheetsInsights(Request $request)
    {
        $dateFrom = $this->parseDateFilter($request->input('date_from'));
        $dateTo = $this->parseDateFilter($request->input('date_to'));
        $branchFilter = $request->filled('branch')
            ? (is_array($request->input('branch')) ? $request->input('branch') : [$request->input('branch')])
            : null;
        if ($branchFilter) {
            $branchFilter = array_values(array_filter($branchFilter, function ($id) {
                return $id !== null && $id !== '';
            }));
            if ($branchFilter === []) {
                $branchFilter = null;
            }
        }
        $assigneeFilter = $request->filled('assignee') && $request->input('assignee') !== 'all'
            ? (int) $request->input('assignee')
            : null;

        // Base application query (clients only)
        $appBase = Application::query()
            ->join('admins', 'applications.client_id', '=', 'admins.id')
            ->where('admins.is_archived', 0)
            ->whereNull('admins.is_deleted');

        $insightsStaff = Auth::guard('admin')->user();
        if ($insightsStaff instanceof Staff) {
            StaffClientVisibility::restrictApplicationsToVisibleClients($appBase, $insightsStaff);
        }

        if ($branchFilter) {
            $appBase->whereIn('admins.office_id', $branchFilter);
        }
        if ($assigneeFilter) {
            $appBase->where('applications.user_id', $assigneeFilter);
        }

        // Conversions: checklist_sheet_status = 'convert_to_client'
        $conversionsQuery = (clone $appBase)->where('applications.checklist_sheet_status', 'convert_to_client');
        if ($dateFrom) {
            $conversionsQuery->where('applications.updated_at', '>=', $dateFrom->startOfDay());
        }
        if ($dateTo) {
            $conversionsQuery->where('applications.updated_at', '<=', $dateTo->endOfDay());
        }
        $totalConversions = $conversionsQuery->count();

        // Discontinued: checklist_sheet_status = 'discontinue' OR status 2/8 (Discontinue/Refund)
        $discontinueQuery = (clone $appBase)->where(function ($q) {
            $q->where('applications.checklist_sheet_status', 'discontinue')
              ->orWhereIn('applications.status', [2, 8]);
        });
        if ($dateFrom) {
            $discontinueQuery->where('applications.updated_at', '>=', $dateFrom->startOfDay());
        }
        if ($dateTo) {
            $discontinueQuery->where('applications.updated_at', '<=', $dateTo->endOfDay());
        }
        $totalDiscontinued = $discontinueQuery->count();

        // Clients seen (from checkin_logs) - distinct clients per assignee (visibility-scoped)
        $seenQuery = CheckinLog::query()
            ->select('user_id', DB::raw('COUNT(DISTINCT client_id) as seen_count'))
            ->where('contact_type', 'Client')
            ->groupBy('user_id');
        if ($insightsStaff instanceof Staff) {
            // Same client visibility as sheet list / $appBase (no-op for allocation-exempt staff)
            if (
                StaffClientVisibility::strictAllocationEnabled()
                && ! StaffClientVisibility::isExemptFromAllocation($insightsStaff)
            ) {
                $visibleClients = Admin::query();
                StaffClientVisibility::restrictAdminsQueryForStaff($visibleClients, $insightsStaff);
                $seenQuery->whereIn('client_id', $visibleClients->select('id'));
            }
        } else {
            $seenQuery->whereRaw('1 = 0');
        }
        if ($dateFrom) {
            $seenQuery->where(function ($q) use ($dateFrom) {
                $q->whereDate('date', '>=', $dateFrom)->orWhere('created_at', '>=', $dateFrom->startOfDay());
            });
        }
        if ($dateTo) {
            $seenQuery->where(function ($q) use ($dateTo) {
                $q->whereDate('date', '<=', $dateTo)->orWhere('created_at', '<=', $dateTo->endOfDay());
            });
        }
        if ($branchFilter) {
            $seenQuery->whereIn('office', $branchFilter);
        }
        if ($assigneeFilter) {
            $seenQuery->where('user_id', $assigneeFilter);
        }
        $seenByAssignee = $seenQuery->pluck('seen_count', 'user_id');
        $totalSeen = $seenByAssignee->sum();

        // Per-assignee breakdown (staff who have applications or did check-ins)
        $assigneeIds = Application::select('user_id')->whereNotNull('user_id')->distinct()->pluck('user_id')
            ->merge(CheckinLog::select('user_id')->distinct()->pluck('user_id'))
            ->unique()->filter()->values();
        $assignees = \App\Models\Staff::where('status', 1)
            ->whereIn('id', $assigneeIds)
            ->orderBy('first_name')->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);

        $assigneeData = [];
        foreach ($assignees as $a) {
            $aid = $a->id;
            $convQ = (clone $appBase)->where('applications.user_id', $aid)
                ->where('applications.checklist_sheet_status', 'convert_to_client');
            $discQ = (clone $appBase)->where('applications.user_id', $aid)
                ->where(function ($q) {
                    $q->where('applications.checklist_sheet_status', 'discontinue')->orWhereIn('applications.status', [2, 8]);
                });
            if ($dateFrom) {
                $convQ->where('applications.updated_at', '>=', $dateFrom->startOfDay());
                $discQ->where('applications.updated_at', '>=', $dateFrom->startOfDay());
            }
            if ($dateTo) {
                $convQ->where('applications.updated_at', '<=', $dateTo->endOfDay());
                $discQ->where('applications.updated_at', '<=', $dateTo->endOfDay());
            }
            $conv = $convQ->count();
            $disc = $discQ->count();
            $seen = (int) ($seenByAssignee[$aid] ?? 0);
            $loadQuery = Application::query()
                ->where('user_id', $aid)
                ->whereNotIn('status', [2, 8])
                ->whereRaw('LOWER(TRIM(stage)) NOT IN (?, ?)', ['coe issued', 'enrolled']);
            if ($insightsStaff instanceof Staff) {
                StaffClientVisibility::restrictApplicationsToVisibleClients($loadQuery, $insightsStaff);
            } else {
                $loadQuery->whereRaw('1 = 0');
            }
            $load = $loadQuery->count();
            $total = $conv + $disc;
            $rate = $total > 0 ? round(($conv / $total) * 100, 1) : 0;

            $assigneeData[] = [
                'id' => $aid,
                'name' => trim(($a->first_name ?? '') . ' ' . ($a->last_name ?? '')) ?: ($a->email ?? '—'),
                'converted' => $conv,
                'seen' => $seen,
                'discontinued' => $disc,
                'rate' => $rate,
                'load' => $load,
            ];
        }
        usort($assigneeData, fn ($x, $y) => $y['converted'] <=> $x['converted']);

        // Chart data (exclude assignees with zero for cleaner charts)
        $convChartData = array_values(array_filter($assigneeData, fn ($r) => $r['converted'] >= 1));
        $chartConversionsByAssignee = [
            'labels' => array_column($convChartData, 'name'),
            'values' => array_column($convChartData, 'converted'),
        ];
        $seenChartData = array_values(array_filter($assigneeData, fn ($r) => $r['seen'] >= 1));
        $chartSeenByAssignee = [
            'labels' => array_column($seenChartData, 'name'),
            'values' => array_column($seenChartData, 'seen'),
        ];
        $chartDiscontinueByAssignee = [
            'labels' => array_column($assigneeData, 'name'),
            'values' => array_column($assigneeData, 'discontinued'),
        ];
        $chartConvertVsDiscontinue = [
            ['Converted', $totalConversions],
            ['Discontinued', $totalDiscontinued],
        ];

        // Monthly trend (last 12 months)
        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $months->push(now()->subMonths($i)->format('M Y'));
        }
        $convByMonth = [];
        $discByMonth = [];
        foreach (range(11, 0) as $i) {
            $mStart = now()->subMonths($i)->startOfMonth();
            $mEnd = now()->subMonths($i)->endOfMonth();
            $convByMonth[] = (clone $appBase)
                ->where('applications.checklist_sheet_status', 'convert_to_client')
                ->whereBetween('applications.updated_at', [$mStart, $mEnd])
                ->count();
            $discByMonth[] = (clone $appBase)
                ->where(function ($q) {
                    $q->where('applications.checklist_sheet_status', 'discontinue')->orWhereIn('applications.status', [2, 8]);
                })
                ->whereBetween('applications.updated_at', [$mStart, $mEnd])
                ->count();
        }
        $chartMonthlyTrend = [
            'labels' => $months->toArray(),
            'conversions' => $convByMonth,
            'discontinues' => $discByMonth,
        ];

        $conversionRate = ($totalConversions + $totalDiscontinued) > 0
            ? round(($totalConversions / ($totalConversions + $totalDiscontinued)) * 100, 1)
            : 0;

        $branches = Branch::orderBy('office_name')->get(['id', 'office_name']);
        $assigneesForFilter = \App\Models\Staff::where('status', 1)
            ->whereIn('id', Application::select('user_id')->whereNotNull('user_id')->distinct())
            ->orderBy('first_name')->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);

        return view('Admin.sheets.insights', compact(
            'totalConversions',
            'totalSeen',
            'totalDiscontinued',
            'conversionRate',
            'assigneeData',
            'chartConversionsByAssignee',
            'chartSeenByAssignee',
            'chartDiscontinueByAssignee',
            'chartConvertVsDiscontinue',
            'chartMonthlyTrend',
            'branches',
            'assigneesForFilter'
        ));
    }

    /**
     * Parse d/m/Y date string to Carbon instance.
     */
    protected function parseDateFilter(?string $value): ?Carbon
    {
        if (!$value || trim($value) === '') {
            return null;
        }
        try {
            return Carbon::createFromFormat('d/m/Y', trim($value));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Block sheet mutations for clients the staff cannot see (same rules as list).
     * Returns a 403 JSON response when denied; null when allowed.
     */
    protected function denyUnlessVisibleClient(?int $clientId)
    {
        if (! $clientId || ! StaffClientVisibility::canAccessAdminRecord((int) $clientId)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this client.',
            ], 403);
        }

        return null;
    }

    /**
     * Update ongoing reference for a client (optional - for future use)
     */
    public function updateReference(Request $request, $clientId)
    {
        if ($denied = $this->denyUnlessVisibleClient((int) $clientId)) {
            return $denied;
        }

        $request->validate([
            'current_status' => 'nullable|string',
            'payment_display_note' => 'nullable|string|max:100',
            'institute_override' => 'nullable|string|max:255',
            'visa_category_override' => 'nullable|string|max:50',
        ]);

        $ongoingRef = ClientOngoingReference::updateOrCreate(
            ['client_id' => $clientId],
            [
                'current_status' => $request->input('current_status'),
                'payment_display_note' => $request->input('payment_display_note'),
                'institute_override' => $request->input('institute_override'),
                'visa_category_override' => $request->input('visa_category_override'),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Ongoing reference updated successfully',
            'data' => $ongoingRef
        ]);
    }

    /**
     * Store or replace sheet comment for an application (Option A: one current comment per app).
     * Appears in Notes & Activity with course and college name; filter by sheet_comment.
     */
    public function storeSheetComment(Request $request)
    {
        $request->validate([
            'application_id' => 'required|integer|exists:applications,id',
            'comment' => 'required|string|max:65535',
        ]);

        $app = Application::with(['product', 'partner'])->findOrFail($request->application_id);
        if ($denied = $this->denyUnlessVisibleClient($app->client_id ? (int) $app->client_id : null)) {
            return $denied;
        }

        $courseName = $app->product ? $app->product->name : '—';
        $collegeName = $app->partner ? $app->partner->partner_name : '—';
        $title = "Course: {$courseName}, College: {$collegeName}";

        $log = ApplicationActivitiesLog::updateOrCreate(
            [
                'app_id' => $request->application_id,
                'type' => 'sheet_comment',
            ],
            [
                'stage' => 'Sheet comment',
                'comment' => $request->comment,
                'title' => $title,
                'description' => '',
                'user_id' => Auth::id(),
            ]
        );

        // Also log to client Activities feed so it appears on the client page
        if ($app->client_id) {
            ActivitiesLog::create([
                'client_id' => $app->client_id,
                'created_by' => Auth::id(),
                'subject' => "added a sheet comment",
                'description' => "<strong>Sheet comment</strong> ({$title}): " . e($request->comment),
                'task_status' => 0,
                'pin' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comment saved. Previous comment replaced.',
            'data' => ['comment' => $log->comment, 'title' => $log->title],
        ]);
    }

    /**
     * Update checklist sheet status for an application.
     * active / hold = stay on Checklist (hold sorts to bottom).
     * convert_to_client = leaves Checklist, appears on Ongoing.
     * discontinue = leaves Checklist, appears on Discontinue; sets application status = 2.
     */
    public function updateChecklistStatus(Request $request)
    {
        $request->validate([
            'application_id' => 'required|integer|exists:applications,id',
            'status' => 'required|string|in:active,convert_to_client,discontinue,hold',
        ]);

        $app = Application::findOrFail($request->application_id);
        if ($denied = $this->denyUnlessVisibleClient($app->client_id ? (int) $app->client_id : null)) {
            return $denied;
        }

        $status = $request->input('status');

        $app->checklist_sheet_status = $status;

        if ($status === 'discontinue') {
            $app->status = 2;
        }

        if ($status === 'convert_to_client') {
            $nextStage = config('sheets.checklist_convert_to_client_stage', 'Document received');
            $app->stage = $nextStage;
        }

        $app->save();

        $leavesSheet = in_array($status, ['convert_to_client', 'discontinue'], true);

        return response()->json([
            'success' => true,
            'message' => $leavesSheet ? 'Status updated. Row has moved to the respective sheet.' : 'Status updated.',
            'data' => [
                'status' => $status,
                'leaves_sheet' => $leavesSheet,
            ],
        ]);
    }

    /**
     * Record a phone reminder for an application (multiple allowed).
     * Creates ApplicationReminder (type=phone) and ActivitiesLog for client.
     */
    public function storePhoneReminder(Request $request)
    {
        $request->validate([
            'application_id' => 'required|integer|exists:applications,id',
        ]);

        $app = Application::findOrFail($request->application_id);
        if ($denied = $this->denyUnlessVisibleClient($app->client_id ? (int) $app->client_id : null)) {
            return $denied;
        }

        ApplicationReminder::create([
            'application_id' => $app->id,
            'type' => 'phone',
            'reminded_at' => now(),
            'user_id' => Auth::id(),
        ]);

        $sentDate = now()->format('d/m/Y');
        if ($app->client_id) {
            ActivitiesLog::create([
                'client_id' => $app->client_id,
                'created_by' => Auth::id(),
                'subject' => 'Phone reminder recorded',
                'description' => 'Phone reminder recorded on ' . $sentDate,
                'task_status' => 0,
                'pin' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Phone reminder recorded.',
            'data' => ['reminded_at' => now()->toIso8601String()],
        ]);
    }
}
