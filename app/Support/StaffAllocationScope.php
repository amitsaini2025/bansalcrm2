<?php

namespace App\Support;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Builder;

/**
 * Allocation ownership for admins rows (user_id + comma-separated assignee).
 * Does not include temporary client_access_grants — those are access, not caseload.
 */
final class StaffAllocationScope
{
    public static function staffMatchesAssigneeValue(mixed $assignee, int $staffId): bool
    {
        $assignee = trim((string) ($assignee ?? ''));
        if ($assignee === '') {
            return false;
        }

        $staffIdString = (string) $staffId;

        if ($assignee === $staffIdString) {
            return true;
        }

        if (! str_contains($assignee, ',')) {
            return false;
        }

        $parts = array_filter(array_map('trim', explode(',', $assignee)));

        return in_array($staffIdString, $parts, true)
            || in_array($staffId, array_map('intval', $parts), true);
    }

    /**
     * @param  Builder<Admin>  $query
     * @return Builder<Admin>
     */
    public static function applyToAdminsQuery(Builder $query, int $staffId, ?string $table = null): Builder
    {
        $prefix = $table ? $table.'.' : '';

        return $query->where(function (Builder $outer) use ($staffId, $prefix) {
            $outer->where($prefix.'user_id', $staffId)
                ->orWhere($prefix.'assignee', (string) $staffId)
                ->orWhere($prefix.'assignee', 'like', $staffId.',%')
                ->orWhere($prefix.'assignee', 'like', '%,'.$staffId.',%')
                ->orWhere($prefix.'assignee', 'like', '%,'.$staffId);
        });
    }

    /**
     * Active client/lead rows for caseload (not archived, not deleted).
     *
     * @param  Builder<Admin>  $query
     * @return Builder<Admin>
     */
    public static function applyActiveClientFilters(Builder $query, ?string $table = null): Builder
    {
        $prefix = $table ? $table.'.' : '';

        return $query
            ->where(function (Builder $q) use ($prefix) {
                $q->where($prefix.'is_archived', 0)
                    ->orWhereNull($prefix.'is_archived');
            })
            ->where(function (Builder $q) use ($prefix) {
                $q->where($prefix.'is_deleted', 0)
                    ->orWhereNull($prefix.'is_deleted');
            });
    }
}
