<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\Staff;
use App\Models\StaffRole;
use App\Services\CrmAccess\CrmAccessService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StaffClientVisibility
{
    /**
     * Module key for clients/leads in user_roles.module_access JSON (see ClientAuthorization).
     */
    public static function staffHasClientsModule(?Authenticatable $user): bool
    {
        if (! $user instanceof Staff) {
            return false;
        }

        $role = StaffRole::query()->find($user->role);
        if (! $role || $role->module_access === null || $role->module_access === '') {
            return false;
        }

        $moduleAccess = json_decode($role->module_access, true);

        return is_array($moduleAccess) && array_key_exists('20', $moduleAccess);
    }

    /**
     * May use /crm/access/request/{adminId} and quick/supervisor POST for this record.
     * Includes normal clients-module roles plus staff who only have cross-access (e.g. quick access
     * without module "20" in role JSON) when strict allocation applies.
     */
    public static function staffMayOpenCrossAccessRequest(Staff $user, int $adminId): bool
    {
        if (self::staffHasClientsModule($user)) {
            return true;
        }

        return self::canRequestCrossAccessGrant($adminId, $user);
    }

    /**
     * Whether this staff may create a quick/supervisor cross-access grant for this admins.id.
     */
    public static function canRequestCrossAccessGrant(int $adminId, Staff $user): bool
    {
        if (! self::strictAllocationEnabled()) {
            return false;
        }

        if (self::isExemptFromAllocation($user)) {
            return false;
        }

        return ! self::canAccessAdminRecord($adminId, $user);
    }

    public static function strictAllocationEnabled(): bool
    {
        return (bool) config('crm_access.strict_allocation', true);
    }

    public static function isExemptFromAllocation(Authenticatable $user): bool
    {
        if (! $user instanceof Staff) {
            return false;
        }

        return app(CrmAccessService::class)->isExemptFromAllocation($user);
    }

    public static function isQuickAccessOnly(?Authenticatable $user): bool
    {
        if (! $user instanceof Staff) {
            return false;
        }

        return in_array((int) ($user->role ?? 0), config('crm_access.quick_access_only_role_ids', [9]), true);
    }

    /**
     * May use supervisor-approved access requests (beyond time-boxed quick grant).
     * Any staff who is not allocation-exempt may request (including quick-access-only / calling roles).
     * Exempt = full access flag, exempt staff ids, or exempt roles (e.g. Super Admin / Admin).
     */
    public static function staffMayUseSupervisorAccessPath(?Authenticatable $user): bool
    {
        if (! $user instanceof Staff) {
            return false;
        }

        return ! self::isExemptFromAllocation($user);
    }

    /**
     * Which cross-access entry points to show in global search (see migrationmanager2 StaffClientVisibility).
     *
     * @return array{show_quick: bool, show_supervisor: bool}
     */
    public static function crossAccessUiFlags(?Authenticatable $user): array
    {
        if (! $user || ! ($user instanceof Staff) || self::isExemptFromAllocation($user)) {
            return ['show_quick' => false, 'show_supervisor' => false];
        }

        return [
            'show_quick' => (bool) ($user->quick_access_enabled ?? false),
            'show_supervisor' => self::staffMayUseSupervisorAccessPath($user),
        ];
    }

    /**
     * Whether the logged-in staff may open this admins row (by admins.id).
     */
    public static function canAccessAdminRecord(int $adminId, ?Authenticatable $user = null): bool
    {
        $user = $user ?? Auth::guard('admin')->user();

        if (! $user instanceof Staff) {
            return false;
        }

        $row = Admin::query()
            ->where('id', $adminId)
            ->where(function ($q) {
                $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
            })
            ->first(['id', 'type', 'user_id', 'assignee']);

        if (! $row) {
            return false;
        }

        if (! self::strictAllocationEnabled()) {
            return true;
        }

        if (self::isExemptFromAllocation($user)) {
            return true;
        }

        $svc = app(CrmAccessService::class);
        if ($svc->hasActiveGrant($user, $adminId)) {
            return true;
        }

        return self::userAllocatedToRecord($user, $row);
    }

    public static function userAllocatedToRecord(Staff $user, Admin $row): bool
    {
        $sid = (int) $user->id;

        if ((int) ($row->user_id ?? 0) === $sid) {
            return true;
        }

        $assignee = trim((string) ($row->assignee ?? ''));
        if ($assignee === '') {
            return false;
        }

        if ((string) (int) $assignee === $assignee && (int) $assignee === $sid) {
            return true;
        }

        $parts = array_filter(array_map('trim', explode(',', $assignee)));

        return in_array((string) $sid, $parts, true) || in_array($sid, array_map('intval', $parts), true);
    }

    /**
     * Restrict admins query to rows the current staff may see (allocation + active grants).
     */
    public static function restrictAdminsQueryForStaff(Builder $query, ?Authenticatable $user = null, string $adminsIdColumn = 'admins.id'): Builder
    {
        $user = $user ?? Auth::guard('admin')->user();

        if (! $user instanceof Staff) {
            return $query->whereRaw('1 = 0');
        }

        if (! self::strictAllocationEnabled()) {
            return $query;
        }

        if (self::isExemptFromAllocation($user)) {
            return $query;
        }

        $staffId = (int) $user->id;
        $now = now('UTC')->format('Y-m-d H:i:s');

        return $query->where(function (Builder $outer) use ($staffId, $now, $adminsIdColumn) {
            $outer->where('user_id', $staffId)
                ->orWhere('assignee', (string) $staffId)
                ->orWhere('assignee', 'like', $staffId . ',%')
                ->orWhere('assignee', 'like', '%,' . $staffId . ',%')
                ->orWhere('assignee', 'like', '%,' . $staffId)
                ->orWhereExists(function ($sub) use ($staffId, $now, $adminsIdColumn) {
                    $sub->selectRaw('1')
                        ->from('client_access_grants as cag')
                        ->whereColumn('cag.admin_id', $adminsIdColumn)
                        ->where('cag.staff_id', $staffId)
                        ->where('cag.status', 'active')
                        // Open-ended (null ends_at) or not yet past ends_at
                        ->where(function ($w) use ($now) {
                            $w->whereNull('cag.ends_at')->orWhere('cag.ends_at', '>', $now);
                        });
                });
        });
    }

    /**
     * Limit an applications query to clients the staff may see (same rules as admins listing).
     *
     * @param  Builder|\Illuminate\Database\Query\Builder  $applicationQuery
     */
    public static function restrictApplicationsToVisibleClients($applicationQuery, ?Authenticatable $user = null)
    {
        $user = $user ?? Auth::guard('admin')->user();

        if (! $user instanceof Staff) {
            return $applicationQuery->whereRaw('1 = 0');
        }

        if (! self::strictAllocationEnabled() || self::isExemptFromAllocation($user)) {
            return $applicationQuery;
        }

        $sub = Admin::query();
        self::restrictAdminsQueryForStaff($sub, $user);

        return $applicationQuery->whereIn('applications.client_id', $sub->select('id'));
    }
}
