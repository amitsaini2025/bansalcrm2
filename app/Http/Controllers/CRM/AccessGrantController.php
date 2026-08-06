<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ClientAccessGrant;
use App\Models\Staff;
use App\Services\CrmAccess\CrmAccessDeniedException;
use App\Services\CrmAccess\CrmAccessService;
use App\Support\StaffClientVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccessGrantController extends Controller
{
    public function __construct(
        protected CrmAccessService $crmAccess
    ) {
        $this->middleware('auth:admin');
    }

    /**
     * Full-page request form + modal POST /supervisor: allow usual cross-access rules, or any non-exempt staff
     * eligible for supervisor requests (module 20 not required).
     */
    protected function ensureStaffMayOpenCrossAccessOrSupervisorEligible(?Staff $user, int $adminId): void
    {
        if (! $user instanceof Staff) {
            abort(403);
        }
        if (StaffClientVisibility::staffMayOpenCrossAccessRequest($user, $adminId)) {
            return;
        }
        if (StaffClientVisibility::staffMayUseSupervisorAccessPath($user)) {
            return;
        }
        abort(403);
    }

    /**
     * JSON helper for access UI (global search modal). Non-exempt staff may load options; exempt users only if they
     * still use cross-access tools (approver, clients module, or quick access enabled).
     */
    protected function ensureStaffForCrmAccessMeta(?Staff $user): void
    {
        if (! $user instanceof Staff) {
            abort(403);
        }
        if (! StaffClientVisibility::isExemptFromAllocation($user)) {
            return;
        }
        if ($this->crmAccess->isApprover($user)) {
            return;
        }
        if (StaffClientVisibility::staffHasClientsModule($user)) {
            return;
        }
        if ((bool) ($user->quick_access_enabled ?? false)) {
            return;
        }
        abort(403);
    }

    /** Approvers, clients module, or quick-access users may view their grant history. */
    protected function ensureStaffClientsModuleOrApprover(): void
    {
        $user = Auth::guard('admin')->user();
        if (! $user instanceof Staff) {
            abort(403);
        }
        if ($this->crmAccess->isApprover($user)) {
            return;
        }
        if (StaffClientVisibility::staffHasClientsModule($user)) {
            return;
        }
        if ((bool) ($user->quick_access_enabled ?? false)) {
            return;
        }
        abort(403);
    }

    public function requestForm(Request $request, int $adminId)
    {
        /** @var Staff|null $user */
        $user = Auth::guard('admin')->user();
        $this->ensureStaffMayOpenCrossAccessOrSupervisorEligible($user instanceof Staff ? $user : null, $adminId);
        /** @var Staff $user */
        $user = Auth::guard('admin')->user();
        $admin = Admin::query()
            ->where(function ($q) {
                $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
            })
            ->findOrFail($adminId);

        if (StaffClientVisibility::canAccessAdminRecord($adminId, $user)) {
            $encodeId = base64_encode(convert_uuencode($admin->id));

            return $admin->type === 'lead'
                ? redirect()->to('/leads/detail/' . $encodeId)
                : redirect()->to('/clients/detail/' . $encodeId);
        }

        $offices = \App\Models\Branch::query()->orderBy('office_name')->get(['id', 'office_name']);
        $reasons = config('crm_access.quick_reason_options', []);
        $quickEnabled = (bool) ($user->quick_access_enabled ?? false);
        $canSupervisor = StaffClientVisibility::staffMayUseSupervisorAccessPath($user);

        return view('crm.access.request', compact('admin', 'offices', 'reasons', 'quickEnabled', 'canSupervisor'));
    }

    public function meta(Request $request)
    {
        /** @var Staff|null $user */
        $user = Auth::guard('admin')->user();
        $this->ensureStaffForCrmAccessMeta($user instanceof Staff ? $user : null);
        /** @var Staff $user */
        $user = Auth::guard('admin')->user();

        return response()->json([
            'offices' => \App\Models\Branch::query()->orderBy('office_name')->get(['id', 'office_name']),
            'reasons' => config('crm_access.quick_reason_options', []),
            'quick_access_enabled' => (bool) ($user->quick_access_enabled ?? false),
            'can_supervisor' => StaffClientVisibility::staffMayUseSupervisorAccessPath($user),
            'is_approver' => $this->crmAccess->isApprover($user),
        ]);
    }

    public function queue(Request $request)
    {
        /** @var Staff $user */
        $user = Auth::guard('admin')->user();
        if (! $this->crmAccess->isApprover($user)) {
            abort(403);
        }

        $pending = ClientAccessGrant::query()
            ->with(['staff', 'admin'])
            ->where('status', 'pending')
            ->where('grant_type', 'supervisor_approved')
            ->orderByDesc('requested_at')
            ->paginate(25);

        return view('crm.access.queue', compact('pending'));
    }

    /**
     * Approver-only grants overview (filters + export), aligned with CRM cross-access tooling.
     */
    public function dashboard(Request $request)
    {
        /** @var Staff $user */
        $user = Auth::guard('admin')->user();
        if (! $this->crmAccess->isApprover($user)) {
            abort(403);
        }

        $filters = $this->validatedDashboardFilters($request);

        $filtered = $this->grantsFilteredQuery($filters);
        $globalPending = ClientAccessGrant::query()->where('status', 'pending')->count();
        $globalActive = ClientAccessGrant::query()->where('status', 'active')->count();
        $rowCount = (clone $filtered)->count();
        $distinctRecords = (int) DB::query()
            ->fromSub(
                $this->grantsFilteredQuery($filters)->select('admin_id')->distinct(),
                'grant_distinct_admins'
            )
            ->count();

        $pendingPreview = ClientAccessGrant::query()
            ->with(['staff', 'admin'])
            ->where('status', 'pending')
            ->where('grant_type', 'supervisor_approved')
            ->orderByDesc('requested_at')
            ->limit(15)
            ->get();

        $grants = (clone $filtered)
            ->with(['staff', 'admin', 'approvedBy'])
            ->orderByDesc('requested_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $offices = \App\Models\Branch::query()->orderBy('office_name')->get(['id', 'office_name']);
        $teams = \App\Models\Team::query()->orderBy('name')->get(['id', 'name']);
        $reasonLabels = config('crm_access.quick_reason_options', []);

        return view('crm.access.dashboard', compact(
            'filters',
            'globalPending',
            'globalActive',
            'rowCount',
            'distinctRecords',
            'pendingPreview',
            'grants',
            'offices',
            'teams',
            'reasonLabels'
        ));
    }

    public function dashboardExport(Request $request)
    {
        /** @var Staff $user */
        $user = Auth::guard('admin')->user();
        if (! $this->crmAccess->isApprover($user)) {
            abort(403);
        }

        $filters = $this->validatedDashboardFilters($request);

        $filename = 'grants-export-' . date('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $query = $this->grantsFilteredQuery($filters)
            ->with(['staff', 'admin', 'approvedBy'])
            ->orderByDesc('requested_at')
            ->orderByDesc('id');

        return response()->stream(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'id',
                'staff_id',
                'staff_name',
                'admin_id',
                'record_type',
                'grant_type',
                'access_type',
                'status',
                'reason_code',
                'reason_label',
                'office_id',
                'office_label',
                'team_id',
                'team_label',
                'requested_at',
                'approved_at',
                'approved_by_staff_id',
                'ends_at',
                'revoked_at',
                'requester_note',
            ]);
            foreach ($query->cursor() as $g) {
                /** @var ClientAccessGrant $g */
                $reasons = config('crm_access.quick_reason_options', []);
                fputcsv($out, [
                    $g->id,
                    $g->staff_id,
                    $g->staff ? trim(($g->staff->first_name ?? '') . ' ' . ($g->staff->last_name ?? '')) : '',
                    $g->admin_id,
                    $g->record_type,
                    $g->grant_type,
                    $g->access_type,
                    $g->status,
                    $g->quick_reason_code,
                    $reasons[$g->quick_reason_code] ?? $g->quick_reason_code,
                    $g->office_id,
                    $g->office_label_snapshot,
                    $g->team_id,
                    $g->team_label_snapshot,
                    $g->requested_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                    $g->approved_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                    $g->approved_by_staff_id,
                    $g->ends_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                    $g->revoked_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                    preg_replace('/\s+/u', ' ', (string) ($g->requester_note ?? '')),
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }

    /**
     * @return array{staff_id?: int, admin_id?: int, from?: string, to?: string, office_id?: int, team_id?: int, grant_type?: string, status?: string}
     */
    protected function validatedDashboardFilters(Request $request): array
    {
        $validated = $request->validate([
            'staff_id' => 'nullable|integer|min:1',
            'admin_id' => 'nullable|integer|min:1',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'office_id' => 'nullable|integer|exists:branches,id',
            'team_id' => 'nullable|integer|exists:teams,id',
            'grant_type' => 'nullable|in:quick,supervisor_approved',
            'status' => 'nullable|in:pending,active,rejected,revoked,expired',
        ]);

        if (! empty($validated['from']) && ! empty($validated['to']) && $validated['to'] < $validated['from']) {
            throw ValidationException::withMessages([
                'to' => 'The to date must be on or after the from date.',
            ]);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function grantsFilteredQuery(array $filters): Builder
    {
        $q = ClientAccessGrant::query();

        if (! empty($filters['staff_id'])) {
            $q->where('staff_id', (int) $filters['staff_id']);
        }
        if (! empty($filters['admin_id'])) {
            $q->where('admin_id', (int) $filters['admin_id']);
        }
        if (! empty($filters['from'])) {
            $q->whereDate('requested_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $q->whereDate('requested_at', '<=', $filters['to']);
        }
        if (! empty($filters['office_id'])) {
            $q->where('office_id', (int) $filters['office_id']);
        }
        if (! empty($filters['team_id'])) {
            $q->where('team_id', (int) $filters['team_id']);
        }
        if (! empty($filters['grant_type'])) {
            $q->where('grant_type', (string) $filters['grant_type']);
        }
        if (! empty($filters['status'])) {
            $q->where('status', (string) $filters['status']);
        }

        return $q;
    }

    public function myGrants(Request $request)
    {
        $this->ensureStaffClientsModuleOrApprover();
        /** @var Staff $user */
        $user = Auth::guard('admin')->user();

        $grants = ClientAccessGrant::query()
            ->with(['admin'])
            ->where('staff_id', (int) $user->id)
            ->orderByDesc('requested_at')
            ->paginate(25);

        return view('crm.access.my-grants', compact('grants'));
    }

    public function quick(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|integer|exists:admins,id',
            'office_id' => 'required|integer|exists:branches,id',
            'reason' => 'required|string|max:50',
        ]);

        /** @var Staff|null $user */
        $user = Auth::guard('admin')->user();
        $adminId = (int) $request->input('admin_id');
        // Same eligibility as requestForm + supervisor(): clients module / cross-access rules, or non-exempt supervisor path.
        $this->ensureStaffMayOpenCrossAccessOrSupervisorEligible($user instanceof Staff ? $user : null, $adminId);
        /** @var Staff $user */
        $user = Auth::guard('admin')->user();

        // Align with migrationmanager2: POST /quick always persists an active quick grant when checks pass
        // (quick_access_enabled, valid reason, no duplicate active quick grant, valid office). No short-circuit
        // for users who already pass canAccessAdminRecord — grants table is the audit trail.
        // Do not gate on canRequestCrossAccessGrant alone (would block assignee audit grants).

        $admin = Admin::query()
            ->where(function ($q) {
                $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
            })
            ->findOrFail($adminId);
        $recordType = strtolower((string) ($admin->type ?? 'client')) === 'lead' ? 'lead' : 'client';

        try {
            $grant = $this->crmAccess->requestQuickGrant(
                $user,
                (int) $admin->id,
                $recordType,
                (int) $request->input('office_id'),
                (string) $request->input('reason')
            );
        } catch (CrmAccessDeniedException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'mode' => 'new_grant',
            'grant_id' => $grant->id,
            'ends_at' => $grant->ends_at?->toIso8601String(),
        ]);
    }

    public function supervisor(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|integer|exists:admins,id',
            'office_id' => 'required|integer|exists:branches,id',
            'reason' => 'required|string|max:50',
            'note' => 'nullable|string|max:2000',
        ]);

        /** @var Staff|null $user */
        $user = Auth::guard('admin')->user();
        $this->ensureStaffMayOpenCrossAccessOrSupervisorEligible($user instanceof Staff ? $user : null, (int) $request->input('admin_id'));
        /** @var Staff $user */
        $user = Auth::guard('admin')->user();
        if (! StaffClientVisibility::staffMayUseSupervisorAccessPath($user)) {
            return response()->json(['ok' => false, 'message' => 'Supervisor access requests are not enabled for your account.'], 403);
        }
        $admin = Admin::query()
            ->where(function ($q) {
                $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
            })
            ->findOrFail((int) $request->input('admin_id'));
        $recordType = strtolower((string) ($admin->type ?? 'client')) === 'lead' ? 'lead' : 'client';

        try {
            $grant = $this->crmAccess->requestSupervisorGrant(
                $user,
                (int) $admin->id,
                $recordType,
                (int) $request->input('office_id'),
                (string) $request->input('reason'),
                (string) $request->input('note', '')
            );
        } catch (CrmAccessDeniedException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'grant_id' => $grant->id]);
    }

    public function approve(Request $request, int $id)
    {
        /** @var Staff $user */
        $user = Auth::guard('admin')->user();
        try {
            $this->crmAccess->approveGrant($user, $id);
        } catch (CrmAccessDeniedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Access request approved.');
    }

    public function reject(Request $request, int $id)
    {
        $request->validate(['reason' => 'nullable|string|max:2000']);

        /** @var Staff $user */
        $user = Auth::guard('admin')->user();
        try {
            $this->crmAccess->rejectGrant($user, $id, (string) $request->input('reason', ''));
        } catch (CrmAccessDeniedException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Access request rejected.');
    }
}
