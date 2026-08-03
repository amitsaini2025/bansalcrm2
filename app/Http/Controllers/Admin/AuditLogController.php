<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Staff;
use App\Models\StaffLoginLog;

use Auth;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Staff Login Log is super admin only (matches left-side-bar role == 1).
     * Use loose compare so string/int role values both work.
     */
    private function ensureSuperAdminAccess(): void
    {
        if ((Auth::user()->role ?? null) != 1) {
            abort(403, 'Unauthorized.');
        }
    }

    /**
     * Build base query with all applied filters.
     */
    protected function baseQuery(Request $request)
    {
        $q = StaffLoginLog::query()->with('staff');

        if ($request->filled('staff_id')) {
            $q->where('user_id', $request->staff_id);
        }
        if ($request->filled('date_from')) {
            $dateFrom = $this->parseDate($request->date_from);
            if ($dateFrom) {
                $q->where('created_at', '>=', $dateFrom->copy()->startOfDay());
            }
        }
        if ($request->filled('date_to')) {
            $dateTo = $this->parseDate($request->date_to);
            if ($dateTo) {
                $q->where('created_at', '<=', $dateTo->copy()->endOfDay());
            }
        }
        if ($request->filled('event_type')) {
            if ($request->event_type === 'login') {
                $q->whereRaw("LOWER(message) LIKE ?", ['%logged in%']);
            } elseif ($request->event_type === 'logout') {
                $q->whereRaw("LOWER(message) LIKE ?", ['%logged out%']);
            }
        }
        if ($request->filled('ip_address')) {
            $q->where('ip_address', 'LIKE', '%' . trim($request->ip_address) . '%');
        }

        return $q;
    }

    protected function parseDate(?string $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }
        try {
            return Carbon::createFromFormat('d/m/Y', trim($value));
        } catch (\Exception $e) {
            try {
                return Carbon::parse($value);
            } catch (\Exception $e2) {
                return null;
            }
        }
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdminAccess();

        $perPage = (int) $request->get('per_page', 20);
        $perPage = in_array($perPage, [20, 50, 100]) ? $perPage : 20;

        $lists = $this->baseQuery($request)
            ->sortable(['created_at' => 'desc'])
            ->paginate($perPage)
            ->withQueryString();

        // ── Stats (always global / unfiltered for overview cards) ──────────
        $today = Carbon::today();

        $todayLogins = StaffLoginLog::whereDate('created_at', $today)
            ->whereRaw("LOWER(message) LIKE ?", ['%logged in%'])
            ->count();

        $todayLogouts = StaffLoginLog::whereDate('created_at', $today)
            ->whereRaw("LOWER(message) LIKE ?", ['%logged out%'])
            ->count();

        // Active staff now: staff whose most recent event today is a login
        $activeStaffCount = 0;
        $todayStaffIds = StaffLoginLog::whereDate('created_at', $today)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');
        foreach ($todayStaffIds as $sid) {
            $last = StaffLoginLog::where('user_id', $sid)
                ->whereDate('created_at', $today)
                ->orderByDesc('created_at')
                ->value('message');
            if ($last && stripos($last, 'logged in') !== false) {
                $activeStaffCount++;
            }
        }

        $weekStart = Carbon::now()->startOfWeek();
        $uniqueStaffThisWeek = (int) DB::table('staff_login_logs')
            ->where('created_at', '>=', $weekStart)
            ->whereNotNull('user_id')
            ->distinct()
            ->count('user_id');

        $topStaffRow = StaffLoginLog::whereDate('created_at', $today)
            ->whereNotNull('user_id')
            ->whereRaw("LOWER(message) LIKE ?", ['%logged in%'])
            ->select('user_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('user_id')
            ->orderByDesc('cnt')
            ->first();
        $topStaffName = $topStaffRow
            ? (Staff::find($topStaffRow->user_id)?->full_name ?? '—')
            : '—';

        // ── Chart data (respects active filters) ───────────────────────────

        $loginsByHourRaw = $this->baseQuery($request)
            ->whereRaw("LOWER(message) LIKE ?", ['%logged in%'])
            ->select(DB::raw('EXTRACT(HOUR FROM created_at)::integer as h'), DB::raw('COUNT(*) as cnt'))
            ->groupBy(DB::raw('EXTRACT(HOUR FROM created_at)'))
            ->orderBy('h')
            ->get()
            ->keyBy('h');
        $loginsByHour = [];
        for ($h = 0; $h < 24; $h++) {
            $loginsByHour[$h] = $loginsByHourRaw->get($h)?->cnt ?? 0;
        }

        $isPgsql = DB::getDriverName() === 'pgsql';
        $loginsByWeekRaw = $this->baseQuery($request)
            ->whereRaw("LOWER(message) LIKE ?", ['%logged in%'])
            ->where('created_at', '>=', Carbon::now()->subDays(30));
        if ($isPgsql) {
            $loginsByWeekRaw = $loginsByWeekRaw
                ->select(DB::raw("date_trunc('week', created_at)::date as week_start"), DB::raw('COUNT(*) as cnt'))
                ->groupBy(DB::raw("date_trunc('week', created_at)"))
                ->orderBy('week_start');
        } else {
            $loginsByWeekRaw = $loginsByWeekRaw
                ->select(DB::raw("DATE(DATE_SUB(created_at, INTERVAL WEEKDAY(created_at) DAY)) as week_start"), DB::raw('COUNT(*) as cnt'))
                ->groupBy(DB::raw("DATE(DATE_SUB(created_at, INTERVAL WEEKDAY(created_at) DAY))"))
                ->orderBy('week_start');
        }
        $loginsByWeekRaw = $loginsByWeekRaw->get()->keyBy('week_start');

        $labels30 = [];
        $values30 = [];
        $weeksBack = 5;
        for ($w = $weeksBack - 1; $w >= 0; $w--) {
            $weekStart = Carbon::now()->startOfWeek()->subWeeks($w);
            $weekKey = $weekStart->format('Y-m-d');
            $weekEnd = $weekStart->copy()->endOfWeek();
            $labels30[] = $weekStart->format('d M') . '–' . $weekEnd->format('d M');
            $values30[] = (int) ($loginsByWeekRaw->get($weekKey)?->cnt ?? 0);
        }

        // Top staff chart — bulk-load names to avoid N+1
        $topStaffChartRaw = $this->baseQuery($request)
            ->whereRaw("LOWER(message) LIKE ?", ['%logged in%'])
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('user_id')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();
        $staffIds = $topStaffChartRaw->pluck('user_id')->unique()->values();
        $staffNameMap = Staff::whereIn('id', $staffIds)->get()->keyBy('id')
            ->map(fn ($s) => $s->full_name);
        $topStaffChartLabels = [];
        $topStaffChartValues = [];
        foreach ($topStaffChartRaw as $r) {
            $topStaffChartLabels[] = $staffNameMap->get($r->user_id) ?? 'Staff #' . $r->user_id;
            $topStaffChartValues[] = (int) $r->cnt;
        }

        // ── Per-staff session hours ────────────────────────────────────────
        $staffSessions = $this->computeStaffSessions($request);

        // Duration map for table column (keyed by staffId_loginTimestamp)
        $durationMap = [];
        foreach ($staffSessions as $staffId => $data) {
            foreach ($data['sessions'] ?? [] as $s) {
                $k = $staffId . '_' . $s['login_at']->format('Y-m-d H:i:s');
                $durationMap[$k] = $s['duration_secs'];
            }
        }

        $staffList = Staff::active()->orderBy('first_name')->get();

        return view('Admin.auditlogs.index', compact(
            'lists',
            'todayLogins',
            'todayLogouts',
            'activeStaffCount',
            'uniqueStaffThisWeek',
            'topStaffName',
            'loginsByHour',
            'labels30',
            'values30',
            'topStaffChartLabels',
            'topStaffChartValues',
            'staffSessions',
            'staffList',
            'perPage',
            'durationMap'
        ));
    }

    /**
     * Pair login → logout events per staff to compute session durations.
     * Sorted by user_id first so single-pointer tracking is safe across staff.
     */
    protected function computeStaffSessions(Request $request): array
    {
        $logs = $this->baseQuery($request)
            ->orderBy('user_id')
            ->orderBy('created_at')
            ->get();

        // Pre-load all staff names to avoid N+1
        $staffIds = $logs->pluck('user_id')->filter()->unique()->values();
        $staffNames = Staff::whereIn('id', $staffIds)->get()->keyBy('id')
            ->map(fn ($s) => $s->full_name);

        $sessionsByStaff = [];
        $currentStaffId  = null;
        $loginAt         = null;

        foreach ($logs as $log) {
            $sid = $log->user_id;

            if ($log->isLogin()) {
                // If same staff logs in again without logging out, close the open session
                // using the new login time as the implied end (duplicate/crash scenario)
                if ($loginAt !== null && $currentStaffId === $sid) {
                    $this->pushSession($sessionsByStaff, $staffNames, $sid, $loginAt, $log->created_at);
                }
                // If new staff starts (logs sorted by user_id, so previous staff is done)
                if ($currentStaffId !== $sid && $loginAt !== null && $currentStaffId !== null) {
                    $this->pushSession($sessionsByStaff, $staffNames, $currentStaffId, $loginAt, now());
                }
                $currentStaffId = $sid;
                $loginAt        = $log->created_at;

            } elseif ($log->isLogout() && $currentStaffId == $sid && $loginAt !== null) {
                $this->pushSession($sessionsByStaff, $staffNames, $sid, $loginAt, $log->created_at);
                $loginAt = null;
            }
        }

        // Flush any still-open session (staff currently logged in)
        if ($loginAt !== null && $currentStaffId !== null) {
            $this->pushSession($sessionsByStaff, $staffNames, $currentStaffId, $loginAt, now());
        }

        return $sessionsByStaff;
    }

    protected function pushSession(
        array &$sessionsByStaff,
        $staffNames,
        $staffId,
        $loginAt,
        $logoutAt
    ): void {
        $staffId = (string) $staffId;
        if (!isset($sessionsByStaff[$staffId])) {
            $sessionsByStaff[$staffId] = [
                'name'     => $staffNames->get((int) $staffId) ?? 'Staff #' . $staffId,
                'sessions' => [],
            ];
        }
        $sessionsByStaff[$staffId]['sessions'][] = [
            'login_at'      => $loginAt,
            'logout_at'     => $logoutAt,
            'duration_secs' => $logoutAt->diffInSeconds($loginAt),
        ];
    }

    /**
     * Build a map of login-row ID → session duration in seconds for CSV export.
     * Tracks state per staff independently to handle interleaved multi-staff logs.
     */
    protected function buildDurationMap($logs): array
    {
        $map        = [];
        $openLogins = []; // keyed by user_id: ['at' => Carbon, 'id' => int]

        foreach ($logs->sortBy('created_at') as $log) {
            $sid = (string) $log->user_id;

            if ($log->isLogin()) {
                if (isset($openLogins[$sid])) {
                    // Consecutive login without logout — close previous at this login time
                    $map[$openLogins[$sid]['id']] = $log->created_at->diffInSeconds($openLogins[$sid]['at']);
                }
                $openLogins[$sid] = ['at' => $log->created_at, 'id' => $log->id];

            } elseif ($log->isLogout() && isset($openLogins[$sid])) {
                $map[$openLogins[$sid]['id']] = $log->created_at->diffInSeconds($openLogins[$sid]['at']);
                unset($openLogins[$sid]);
            }
        }

        return $map;
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $this->ensureSuperAdminAccess();

        $query = $this->baseQuery($request)
            ->orderBy('created_at', 'desc')
            ->limit(5000);

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="staff-login-log-' . date('Y-m-d-His') . '.csv"',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Time', 'Staff', 'Event', 'IP Address', 'Device / Browser', 'Session Duration (mins)']);

            $logs            = $query->get();
            $sessionsByLogId = $this->buildDurationMap($logs);

            foreach ($logs as $log) {
                $staffName   = $log->staff?->full_name ?? '—';
                $event       = $log->isLogin() ? 'Logged In' : 'Logged Out';
                $device      = \App\Helpers\UserAgentParser::parse($log->user_agent);
                $durationSec = $sessionsByLogId[$log->id] ?? null;
                $durationMin = $durationSec !== null ? round($durationSec / 60, 1) : '';

                fputcsv($handle, [
                    $log->created_at->format('d/m/Y'),
                    $log->created_at->format('H:i:s'),
                    $staffName,
                    $event,
                    $log->ip_address ?? '',
                    $device,
                    $durationMin,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
