<?php

namespace App\Services\CrmAccess;

use App\Models\Branch;
use App\Models\ClientAccessGrant;
use App\Models\Notification;
use App\Models\Staff;
use App\Services\SearchService;
use App\Support\StaffClientVisibility;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CrmAccessService
{
    /** @return list<int> */
    public function approverRoleIds(): array
    {
        return array_values(array_unique(array_map('intval', config('crm_access.exempt_role_ids', [1, 12]))));
    }

    public function isApprover(Staff $user): bool
    {
        if ((int) ($user->status ?? 0) !== 1) {
            return false;
        }

        if (in_array((int) ($user->role ?? 0), $this->approverRoleIds(), true)) {
            return true;
        }

        return (bool) ($user->crm_access_approver ?? false);
    }

    public function isExemptFromAllocation(Staff $user): bool
    {
        if ((bool) ($user->crm_full_access ?? false)) {
            return true;
        }

        $staffId = (int) ($user->id ?? 0);
        if ($staffId > 0 && in_array($staffId, config('crm_access.exempt_staff_ids', []), true)) {
            return true;
        }

        return in_array((int) ($user->role ?? 0), config('crm_access.exempt_role_ids', [1, 12]), true);
    }

    /**
     * Who may edit Staff → CRM access (quick / full / approver flags).
     * Role-based only (Super Admin + Admin): delegated {@see crm_access_approver} users can use the queue
     * but must not grant full access or further approvers to others.
     */
    public function canManageStaffQuickAccess(Staff $actor): bool
    {
        if ((int) ($actor->status ?? 0) !== 1) {
            return false;
        }

        return in_array((int) ($actor->role ?? 0), $this->approverRoleIds(), true);
    }

    /** @return list<int> */
    public function getApproverStaffIds(): array
    {
        $roles = $this->approverRoleIds();

        $byRole = $roles === [] ? [] : Staff::query()
            ->whereIn('role', $roles)
            ->where('status', 1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $byFlag = Staff::query()
            ->where('status', 1)
            ->where('crm_access_approver', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique(array_merge($byRole, $byFlag)));
    }

    public function hasActiveGrant(Staff $user, int $adminId): bool
    {
        if ((int) ($user->status ?? 0) !== 1) {
            return false;
        }

        $now = Carbon::now('UTC');

        return ClientAccessGrant::query()
            ->where('staff_id', (int) $user->id)
            ->where('admin_id', $adminId)
            ->where('status', 'active')
            // Open-ended (null ends_at) or not yet past ends_at
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', $now);
            })
            ->exists();
    }

    public function requestQuickGrant(Staff $user, int $adminId, string $recordType, int $officeId, string $reasonCode): ClientAccessGrant
    {
        // Match migrationmanager2: do not gate on canRequestCrossAccessGrant here (assignees / exempt users may
        // still log a quick grant for audit). Authorization is auth:admin + quick_access_enabled + duplicate check.
        if (! (bool) ($user->quick_access_enabled ?? false)) {
            throw new CrmAccessDeniedException('Quick access is not enabled for your account.');
        }
        $reasons = config('crm_access.quick_reason_options', []);
        if (! array_key_exists($reasonCode, $reasons)) {
            throw new CrmAccessDeniedException('Invalid reason.');
        }
        if ($this->hasDuplicateActiveQuickGrant($user, $adminId)) {
            throw new CrmAccessDeniedException('An active quick access grant already exists for this record.');
        }

        $minutes = max(1, (int) config('crm_access.quick_grant_minutes', 15));
        $starts = Carbon::now('UTC');
        $ends = $starts->copy()->addMinutes($minutes);

        $office = Branch::query()->find($officeId);
        if (! $office) {
            throw new CrmAccessDeniedException('Invalid office.');
        }

        $grant = ClientAccessGrant::query()->create([
            'staff_id' => (int) $user->id,
            'admin_id' => $adminId,
            'record_type' => $recordType,
            'grant_type' => 'quick',
            'access_type' => 'quick',
            'status' => 'active',
            'quick_reason_code' => $reasonCode,
            'office_id' => $officeId,
            'office_label_snapshot' => (string) $office->office_name,
            'team_id' => null,
            'team_label_snapshot' => null,
            'requested_at' => $starts,
            'starts_at' => $starts,
            'ends_at' => $ends,
        ]);

        SearchService::bumpGlobalSearchCacheForStaff((int) $user->id);

        return $grant;
    }

    public function requestSupervisorGrant(Staff $user, int $adminId, string $recordType, int $officeId, string $reasonCode, string $note = ''): ClientAccessGrant
    {
        // Match requestQuickGrant / migrationmanager2: do not gate on canRequestCrossAccessGrant (module 20 /
        // strict allocation). Controller enforces staffMayUseSupervisorAccessPath (non-exempt staff). Quick-only /
        // calling roles may submit supervisor requests; pending requests are bounded by max_pending and reason validation.
        $reasons = config('crm_access.quick_reason_options', []);
        if ($reasonCode === '' || ! array_key_exists($reasonCode, $reasons)) {
            throw new CrmAccessDeniedException('Invalid reason.');
        }

        $maxPending = max(1, (int) config('crm_access.max_pending_supervisor_requests', 5));
        $pendingCount = ClientAccessGrant::query()
            ->where('staff_id', (int) $user->id)
            ->where('grant_type', 'supervisor_approved')
            ->where('status', 'pending')
            ->count();
        if ($pendingCount >= $maxPending) {
            throw new CrmAccessDeniedException("You already have {$maxPending} pending supervisor requests. Wait for them to be resolved before submitting more.");
        }

        $office = Branch::query()->find($officeId);
        if (! $office) {
            throw new CrmAccessDeniedException('Invalid office.');
        }

        $grant = ClientAccessGrant::query()->create([
            'staff_id' => (int) $user->id,
            'admin_id' => $adminId,
            'record_type' => $recordType,
            'grant_type' => 'supervisor_approved',
            'access_type' => 'supervisor_approved',
            'status' => 'pending',
            'quick_reason_code' => $reasonCode,
            'requester_note' => $note !== '' ? $note : null,
            'office_id' => $officeId,
            'office_label_snapshot' => (string) $office->office_name,
            'team_id' => null,
            'team_label_snapshot' => null,
            'requested_at' => Carbon::now('UTC'),
        ]);

        $this->notifyApproversOfPendingGrant($grant, $user, $adminId);

        return $grant;
    }

    public function approveGrant(Staff $approver, int $grantId): ClientAccessGrant
    {
        if (! $this->isApprover($approver)) {
            throw new CrmAccessDeniedException('Not authorized to approve.');
        }

        $grant = ClientAccessGrant::query()->findOrFail($grantId);
        if ($grant->status !== 'pending') {
            throw new CrmAccessDeniedException('Grant is not pending.');
        }
        if ((int) $grant->staff_id === (int) $approver->id) {
            throw new CrmAccessDeniedException('You cannot approve your own request.');
        }

        $hours = max(1, (int) config('crm_access.supervisor_grant_hours', 24));
        $starts = Carbon::now('UTC');
        $ends = $starts->copy()->addHours($hours);

        $requesterStaffId = (int) $grant->staff_id;

        $grant->update([
            'status' => 'active',
            'approved_by_staff_id' => (int) $approver->id,
            'approved_at' => $starts,
            'starts_at' => $starts,
            'ends_at' => $ends,
        ]);

        SearchService::bumpGlobalSearchCacheForStaff($requesterStaffId);

        $this->notifyRequesterGrantProcessed($grant->fresh(), 'approved');

        return $grant->fresh();
    }

    public function rejectGrant(Staff $approver, int $grantId, string $reason = ''): ClientAccessGrant
    {
        if (! $this->isApprover($approver)) {
            throw new CrmAccessDeniedException('Not authorized to reject.');
        }

        $grant = ClientAccessGrant::query()->findOrFail($grantId);
        if ($grant->status !== 'pending') {
            throw new CrmAccessDeniedException('Grant is not pending.');
        }
        if ((int) $grant->staff_id === (int) $approver->id) {
            throw new CrmAccessDeniedException('You cannot reject your own request.');
        }

        $grant->update([
            'status' => 'rejected',
            'approved_by_staff_id' => (int) $approver->id,
            'revoke_reason' => $reason !== '' ? $reason : null,
        ]);

        $this->notifyRequesterGrantProcessed($grant->fresh(), 'rejected');

        return $grant->fresh();
    }

    public function revokeGrantsForStaff(int $staffId, string $reason): int
    {
        $count = ClientAccessGrant::query()
            ->where('staff_id', $staffId)
            ->whereIn('status', ['active', 'pending'])
            ->update([
                'status' => 'revoked',
                'revoked_at' => Carbon::now('UTC'),
                'revoke_reason' => $reason,
            ]);

        if ($count > 0) {
            SearchService::bumpGlobalSearchCacheForStaff((int) $staffId);
        }

        return $count;
    }

    public function expireStaleGrants(): int
    {
        $now = Carbon::now('UTC');

        $activeExpiredStaffIds = ClientAccessGrant::query()
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', $now)
            ->pluck('staff_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        $expired = ClientAccessGrant::query()
            ->where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', $now)
            ->update(['status' => 'expired']);

        $pendingTtlDays = max(1, (int) config('crm_access.pending_ttl_days', 14));
        $pendingCutoff = $now->copy()->subDays($pendingTtlDays);

        $pendingExpiredStaffIds = ClientAccessGrant::query()
            ->where('status', 'pending')
            ->where('requested_at', '<', $pendingCutoff)
            ->pluck('staff_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        $pendingExpired = ClientAccessGrant::query()
            ->where('status', 'pending')
            ->where('requested_at', '<', $pendingCutoff)
            ->update(['status' => 'expired', 'revoke_reason' => 'Auto-expired: not actioned within ' . $pendingTtlDays . ' days']);

        foreach (array_unique(array_merge($activeExpiredStaffIds, $pendingExpiredStaffIds)) as $sid) {
            SearchService::bumpGlobalSearchCacheForStaff($sid);
        }

        return $expired + $pendingExpired;
    }

    protected function hasDuplicateActiveQuickGrant(Staff $user, int $adminId): bool
    {
        $now = Carbon::now('UTC');

        return ClientAccessGrant::query()
            ->where('staff_id', (int) $user->id)
            ->where('admin_id', $adminId)
            ->where('grant_type', 'quick')
            ->where('status', 'active')
            // Open-ended (null ends_at) or not yet past ends_at
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', $now);
            })
            ->exists();
    }

    protected function notifyApproversOfPendingGrant(ClientAccessGrant $grant, Staff $requester, int $adminId): void
    {
        $senderName = trim(($requester->first_name ?? '') . ' ' . ($requester->last_name ?? ''));
        if ($senderName === '') {
            $senderName = $requester->email ?? 'Staff';
        }
        $msg = $senderName . ' requested access to record #' . $adminId;
        // Root-relative path so notification links follow the browser host (not APP_URL).
        $url = route('crm.access.queue', [], false);

        $approverIds = array_values(array_filter(
            $this->getApproverStaffIds(),
            fn ($id) => (int) $id !== (int) $requester->id
        ));

        foreach ($approverIds as $receiverId) {
            try {
                Notification::query()->create([
                    'sender_id' => (int) $requester->id,
                    'receiver_id' => (int) $receiverId,
                    'module_id' => (int) $grant->id,
                    'url' => $url,
                    'notification_type' => 'access_request',
                    'message' => $msg,
                    'receiver_status' => 0,
                    'seen' => 0,
                ]);
            } catch (\Throwable $e) {
                Log::warning('access_request notification failed', ['e' => $e->getMessage()]);
            }
        }
    }

    protected function notifyRequesterGrantProcessed(ClientAccessGrant $grant, string $verb): void
    {
        $receiverId = (int) $grant->staff_id;
        $hours = max(1, (int) config('crm_access.supervisor_grant_hours', 24));
        $msg = $verb === 'approved'
            ? "Your supervisor access request was approved ({$hours}h from approval)."
            : 'Your supervisor access request was rejected.';
        $senderId = (int) ($grant->approved_by_staff_id ?? 0);
        // Root-relative path so notification links follow the browser host (not APP_URL).
        $notifUrl = route('crm.access.my-grants', [], false);

        try {
            Notification::query()->create([
                'sender_id' => $senderId > 0 ? $senderId : $receiverId,
                'receiver_id' => $receiverId,
                'module_id' => (int) $grant->id,
                'url' => $notifUrl,
                'notification_type' => 'access_request_' . $verb,
                'message' => $msg,
                'receiver_status' => 0,
                'seen' => 0,
            ]);
        } catch (\Throwable $e) {
            Log::warning('access_request result notification failed', ['e' => $e->getMessage()]);
        }
    }
}
