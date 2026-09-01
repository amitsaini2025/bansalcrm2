<?php

namespace App\Support;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class ArrayPaginator
{
    public const DEFAULT_PER_PAGE = 10;

    /**
     * Paginate an in-memory list without changing the source data used for counts.
     *
     * @param  list<mixed>|Collection<int, mixed>  $items
     */
    public static function make(
        array|Collection $items,
        string $pageName,
        int $perPage = self::DEFAULT_PER_PAGE,
        ?string $fragment = null,
    ): LengthAwarePaginator {
        $perPage = max(1, $perPage);
        $collection = Collection::make($items);
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);

        $paginator = new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ],
        );

        $paginator->withQueryString();

        if (is_string($fragment) && $fragment !== '') {
            $paginator->fragment($fragment);
        }

        return $paginator;
    }
}
