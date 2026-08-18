<?php

namespace App\Support;

use App\Models\Admin;
use App\Models\Staff;
use Illuminate\Support\Collection;

/**
 * Batch lookups that match per-row Staff::find / Admin::find on client detail.
 */
final class ClientDetailEagerLoads
{
    /**
     * Same as Staff::find($id) for each id, keyed by integer id.
     *
     * @param  iterable<mixed>  $ids
     * @return Collection<int, Staff>
     */
    public static function staffByIds(iterable $ids): Collection
    {
        $ids = self::numericIds($ids);
        if ($ids->isEmpty()) {
            return collect();
        }

        return Staff::query()->whereIn('id', $ids)->get()->keyBy(fn (Staff $staff): int => (int) $staff->id);
    }

    /**
     * Same as Staff::find($id) ?? Admin::find($id) for each id, keyed by integer id.
     *
     * @param  iterable<mixed>  $ids
     * @return Collection<int, Staff|Admin>
     */
    public static function staffThenAdminByIds(iterable $ids): Collection
    {
        $ids = self::numericIds($ids);
        if ($ids->isEmpty()) {
            return collect();
        }

        $staff = self::staffByIds($ids);
        $missing = $ids->reject(fn (int $id): bool => $staff->has($id))->values();
        if ($missing->isEmpty()) {
            return $staff;
        }

        $admins = Admin::query()->whereIn('id', $missing)->get()->keyBy(fn (Admin $admin): int => (int) $admin->id);

        return $staff->union($admins);
    }

    /**
     * @param  iterable<mixed>  $ids
     * @return Collection<int, int>
     */
    public static function numericIds(iterable $ids): Collection
    {
        return collect($ids)
            ->map(function ($id): ?int {
                if ($id === null || $id === '') {
                    return null;
                }
                if (! is_numeric($id)) {
                    return null;
                }

                return (int) $id;
            })
            ->filter(fn (?int $id): bool => $id !== null)
            ->unique()
            ->values();
    }
}
