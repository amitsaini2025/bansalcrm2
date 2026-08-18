<?php

namespace App\Support;

use App\Models\ActivitiesLog;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Client/lead detail Activities tab: shared filters, order, and load-more pages.
 */
final class ClientDetailActivities
{
    public const PAGE_SIZE = 25;

    /**
     * @return array{keyword: string, activity_type: string, date_from: string, date_to: string}
     */
    public static function filtersFromRequest(Request $request): array
    {
        return [
            'keyword' => (string) $request->get('keyword', ''),
            'activity_type' => (string) $request->get('activity_type', 'all'),
            'date_from' => (string) $request->get('date_from', ''),
            'date_to' => (string) $request->get('date_to', ''),
        ];
    }

    /**
     * @param  array{keyword?: string, activity_type?: string, date_from?: string, date_to?: string}  $filters
     */
    public static function queryForClient(int $clientId, array $filters = []): Builder
    {
        $query = ActivitiesLog::query()->where('activities_logs.client_id', $clientId);
        self::applyFilters($query, $filters);

        return $query->orderBy('activities_logs.created_at', 'DESC');
    }

    /**
     * @param  array{keyword?: string, activity_type?: string, date_from?: string, date_to?: string}  $filters
     */
    public static function paginate(int $clientId, array $filters = [], int $page = 1): Paginator
    {
        $page = max(1, $page);

        return self::queryForClient($clientId, $filters)
            ->simplePaginate(self::PAGE_SIZE, ['*'], 'page', $page);
    }

    /**
     * @param  array{keyword?: string, activity_type?: string, date_from?: string, date_to?: string}  $filters
     */
    public static function applyFilters(Builder $query, array $filters): void
    {
        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('activities_logs.description', 'like', '%'.$keyword.'%')
                    ->orWhere('activities_logs.subject', 'like', '%'.$keyword.'%');
            });
        }

        $activityType = (string) ($filters['activity_type'] ?? 'all');
        if ($activityType !== '' && $activityType !== 'all') {
            self::applyActivityTypeFilter($query, $activityType);
        }

        $dateFrom = (string) ($filters['date_from'] ?? '');
        if ($dateFrom !== '') {
            $query->whereDate('activities_logs.created_at', '>=', date('Y-m-d', strtotime($dateFrom)));
        }

        $dateTo = (string) ($filters['date_to'] ?? '');
        if ($dateTo !== '') {
            $query->whereDate('activities_logs.created_at', '<=', date('Y-m-d', strtotime($dateTo)));
        }
    }

    private static function applyActivityTypeFilter(Builder $query, string $activityType): void
    {
        switch ($activityType) {
            case 'notes':
                $query->where(function ($q) {
                    $q->where('activities_logs.subject', 'like', '%added a note%')
                        ->orWhere('activities_logs.subject', 'like', '%updated a note%')
                        ->orWhere('activities_logs.subject', 'like', '%deleted a note%');
                });
                break;
            case 'messages':
                $query->where('activities_logs.subject', 'like', '%sent a message%');
                break;
            case 'calls':
                $query->where(function ($q) {
                    $q->where('activities_logs.description', 'like', '%Call not picked%')
                        ->orWhere('activities_logs.subject', 'like', '%call%');
                });
                break;
            case 'reviews':
                $query->where('activities_logs.subject', 'like', '%review%');
                break;
            case 'reminders':
                $query->where(function ($q) {
                    $q->where('activities_logs.subject', 'like', '%Email reminder sent%')
                        ->orWhere('activities_logs.subject', 'like', '%SMS reminder sent%')
                        ->orWhere('activities_logs.subject', 'like', '%Phone reminder recorded%')
                        ->orWhere('activities_logs.subject', 'like', '%Checklist Email sent%')
                        ->orWhere('activities_logs.subject', 'like', '%Checklist Email resent%')
                        ->orWhere('activities_logs.subject', 'like', '%Document Checklist sent%');
                });
                break;
            case 'documents':
                $query->where(function ($q) {
                    $q->where('activities_logs.subject', 'like', '%document%')
                        ->orWhere('activities_logs.subject', 'like', '%uploaded%')
                        ->orWhere('activities_logs.subject', 'like', '%verified%');
                });
                break;
            case 'action':
                $query->where(function ($q) {
                    $q->where('activities_logs.subject', 'like', '%action%')
                        ->orWhere('activities_logs.subject', 'like', '%task%')
                        ->orWhere('activities_logs.subject', 'like', '%Completed action%')
                        ->orWhere('activities_logs.task_status', '=', 1);
                });
                break;
            case 'accounting':
                $query->where(function ($q) {
                    $q->where('activities_logs.subject', 'like', '%receipt%')
                        ->orWhere('activities_logs.subject', 'like', '%invoice%')
                        ->orWhere('activities_logs.subject', 'like', '%payment%');
                });
                break;
            case 'applications':
                $query->where('activities_logs.subject', 'like', '%started an application%');
                break;
            case 'services':
                $query->where(function ($q) {
                    $q->where('activities_logs.subject', 'like', '%an interested service%');
                });
                break;
            case 'status':
                $query->where(function ($q) {
                    $q->where('activities_logs.subject', 'like', '%status%')
                        ->orWhere('activities_logs.subject', 'like', '%rated%')
                        ->orWhere('activities_logs.subject', 'like', '%rating%');
                });
                break;
            case 'checkins':
                $query->where(function ($q) {
                    $q->where('activities_logs.subject', 'like', '%check-in%')
                        ->orWhere('activities_logs.subject', 'like', '%session%')
                        ->orWhere('activities_logs.subject', 'like', '%commented%');
                });
                break;
            case 'other':
                $query->where(function ($q) {
                    $q->where('activities_logs.subject', 'not like', '%note%')
                        ->where('activities_logs.subject', 'not like', '%document%')
                        ->where('activities_logs.subject', 'not like', '%action%')
                        ->where('activities_logs.subject', 'not like', '%task%')
                        ->where('activities_logs.subject', 'not like', '%receipt%')
                        ->where('activities_logs.subject', 'not like', '%application%')
                        ->where('activities_logs.subject', 'not like', '%message%')
                        ->where('activities_logs.subject', 'not like', '%call%')
                        ->where('activities_logs.subject', 'not like', '%service%')
                        ->where('activities_logs.subject', 'not like', '%status%')
                        ->where('activities_logs.subject', 'not like', '%check-in%')
                        ->where('activities_logs.subject', 'not like', '%session%')
                        ->where('activities_logs.subject', 'not like', '%review%')
                        ->where('activities_logs.subject', 'not like', '%reminder%')
                        ->where('activities_logs.subject', 'not like', '%Checklist Email sent%')
                        ->where('activities_logs.subject', 'not like', '%Checklist Email resent%');
                });
                break;
        }
    }
}
