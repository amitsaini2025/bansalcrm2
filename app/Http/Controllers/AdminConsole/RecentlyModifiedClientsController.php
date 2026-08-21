<?php
namespace App\Http\Controllers\AdminConsole;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

use App\Models\Admin;
use App\Models\ActivitiesLog;
use App\Models\Application;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Support\ClientDetailEagerLoads;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

use Auth; 
use Config;

class RecentlyModifiedClientsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

	/**
	 * Recently Modified Clients tools are super admin only (header Admin Console is role == 1).
	 * Use loose compare so string/int role values both work. Does not affect other adminconsole routes.
	 */
	private function ensureSuperAdminAccess(): void
	{
		if ((Auth::user()->role ?? null) != 1) {
			abort(403, 'Unauthorized.');
		}
	}

	/**
	 * Inclusive end bound for a Y-m-d to_date filter against datetime created_at.
	 * (Date-only strings compare as midnight and exclude most of the end day.)
	 */
	private function activityDateEndInclusive(string $toDate): string
	{
		try {
			return Carbon::createFromFormat('Y-m-d', $toDate)->endOfDay()->format('Y-m-d H:i:s');
		} catch (\Exception $e) {
			try {
				return Carbon::parse($toDate)->endOfDay()->format('Y-m-d H:i:s');
			} catch (\Exception $e2) {
				return $toDate;
			}
		}
	}

	/**
	 * Restrict the latest-activity subquery to created_at >= fromDate only when
	 * that matches "true latest falls on/after fromDate": no to_date and no
	 * last-activity-years filter. Windowing with to_date or stale-years would
	 * pick latest-in-window instead of true latest.
	 */
	private function latestActivitySubqueryFromDate(string $fromDate, string $toDate, string $lastActivityYears): ?string
	{
		if ($fromDate === '' || $toDate !== '') {
			return null;
		}

		if ($lastActivityYears !== '' && in_array((int) $lastActivityYears, [1, 2, 3, 4, 5], true)) {
			return null;
		}

		return $fromDate;
	}

	/**
	 * One latest activity log per client (newest created_at, then highest id).
	 * Postgres uses DISTINCT ON; other drivers use an equivalent window function.
	 *
	 * @return \Illuminate\Database\Query\Builder
	 */
	private function latestActivityPerClientSubquery(?string $fromDate = null)
	{
		$fromDate = ($fromDate !== null && $fromDate !== '') ? $fromDate : null;

		if (DB::connection()->getDriverName() === 'pgsql') {
			$query = DB::table('activities_logs')
				->selectRaw('DISTINCT ON (client_id) client_id, id as last_activity_id')
				->whereNotNull('client_id')
				->orderBy('client_id')
				->orderByDesc('created_at')
				->orderByDesc('id');

			if ($fromDate !== null) {
				$query->where('created_at', '>=', $fromDate);
			}

			return $query;
		}

		$ranked = DB::table('activities_logs')
			->select('client_id', 'id')
			->selectRaw('ROW_NUMBER() OVER (PARTITION BY client_id ORDER BY created_at DESC, id DESC) as rn')
			->whereNotNull('client_id');

		if ($fromDate !== null) {
			$ranked->where('created_at', '>=', $fromDate);
		}

		return DB::query()
			->fromSub($ranked, 'ranked_latest_activities')
			->select('client_id', DB::raw('id as last_activity_id'))
			->where('rn', 1);
	}
	
	/**
     * Display recently modified clients based on activities log.
     *
     * @return \Illuminate\Http\Response 
     */
	public function index(Request $request)
	{
		$this->ensureSuperAdminAccess();

		set_time_limit(180);

		// Get filter parameters from request (normalize to scalar to avoid "Illegal operator and value combination")
		$fromDate = $request->input('from_date');
		$fromDate = is_array($fromDate) ? '' : trim((string) $fromDate);
		$toDate = $request->input('to_date');
		$toDate = is_array($toDate) ? '' : trim((string) $toDate);
		$sortOrder = $request->input('sort_order', 'desc'); // Default to descending (newest first)
		$search = $request->input('search', '');
		$search = is_array($search) ? '' : trim((string) $search);
		$activityType = $request->input('activity_type', '');
		$activityType = is_array($activityType) ? '' : trim((string) $activityType);
		$perPage = $request->input('per_page', config('constants.limit', 20));
		$sortColumn = $request->input('sort_column', 'activity_date');
		$hasApplications = $request->input('has_applications', ''); // '' = all, '0' = no applications
		$lastActivityYears = $request->input('last_activity_years', ''); // 1, 2, 3, 4, 5 = X+ years ago
		$documentCount = $request->input('document_count', ''); // '', '0', '1', ... '9', '10+' = documents count filter
		$docStorage = $request->input('doc_storage', ''); // '', 'local', 'aws', 'both', 'none' = document storage location filter
		$noPhone = $request->input('no_phone', ''); // '' = all, '1' = only clients with no phone number
		$noEmail = $request->input('no_email', ''); // '' = all, '1' = only clients with no email address

		// Default to last 12 months when no date/search applied for faster initial load
		if ($fromDate === '' && $toDate === '' && $search === '') {
			$fromDate = Carbon::now()->subMonths(12)->format('Y-m-d');
		}
		
		// One latest activity log per client (max created_at; max id when timestamps collide)
		$subQuery = $this->latestActivityPerClientSubquery(
			$this->latestActivitySubqueryFromDate($fromDate, $toDate, $lastActivityYears)
		);
		
		// Clients live in admins (role = 7). Activity created_by is staff first, then admin (same as client detail).
		$query = ActivitiesLog::select(
				'activities_logs.id as activity_id',
				'activities_logs.client_id',
				'activities_logs.created_by',
				'activities_logs.subject',
				'activities_logs.description',
				'activities_logs.created_at as activity_date',
				'client_admins.first_name as client_firstname',
				'client_admins.last_name as client_lastname',
				'client_admins.client_id as client_unique_id',
				'client_admins.email as client_email',
				'client_admins.phone as client_phone',
				DB::raw($this->modifiedByFirstNameSql().' as admin_firstname'),
				DB::raw($this->modifiedByLastNameSql().' as admin_lastname')
			)
			->joinSub($subQuery, 'latest_activities', function($join) {
				$join->on('activities_logs.id', '=', 'latest_activities.last_activity_id');
			})
			// Use INNER JOIN so we only show non-archived clients (archived clients would otherwise appear with NULL client info)
			->join('admins as client_admins', function($join) {
				$join->on('activities_logs.client_id', '=', 'client_admins.id')
					 ->where(function($q) {
						 $q->whereIn('client_admins.is_archived', [0, '0'])
						   ->orWhereNull('client_admins.is_archived');
					 });
			})
			->leftJoin('staff as modifier_staff', 'activities_logs.created_by', '=', 'modifier_staff.id')
			->leftJoin('admins as modifier_admins', 'activities_logs.created_by', '=', 'modifier_admins.id');

		// Only join document stats when filtering by document count or doc storage (otherwise we fetch per page later for speed)
		$useDocStatsInQuery = ($documentCount !== '' || ($docStorage !== '' && in_array($docStorage, ['local', 'aws', 'both', 'none'], true)));
		if ($useDocStatsInQuery) {
			$docStatsSubQuery = Document::select(
					'client_id',
					DB::raw('COUNT(*) as doc_count'),
					DB::raw("SUM(CASE WHEN (myfile_key IS NULL OR TRIM(COALESCE(myfile_key, '')) = '') AND myfile IS NOT NULL AND TRIM(COALESCE(myfile, '')) != '' THEN 1 ELSE 0 END) AS count_local"),
					DB::raw("SUM(CASE WHEN myfile_key IS NOT NULL AND TRIM(myfile_key) != '' THEN 1 ELSE 0 END) AS count_aws")
				)
				->whereNull('archived_at')
				->appEduMigForStorage()
				->groupBy('client_id');
			$query->leftJoinSub($docStatsSubQuery, 'doc_stats', 'activities_logs.client_id', '=', 'doc_stats.client_id');
			$query->addSelect([
				DB::raw("CASE
					WHEN COALESCE(doc_stats.count_local, 0) > 0 AND COALESCE(doc_stats.count_aws, 0) > 0 THEN 'both'
					WHEN COALESCE(doc_stats.doc_count, 0) > 0 AND COALESCE(doc_stats.count_local, 0) = COALESCE(doc_stats.doc_count, 0) THEN 'local'
					WHEN COALESCE(doc_stats.doc_count, 0) > 0 AND COALESCE(doc_stats.count_aws, 0) = COALESCE(doc_stats.doc_count, 0) THEN 'aws'
					ELSE 'none'
				END AS doc_storage")
			]);
		}

		
		// Apply search filter (name, email, phone, client unique ID e.g. TEST105453)
		if (!empty($search)) {
			$query->where(function($q) use ($search) {
				$q->where(DB::raw("CONCAT(client_admins.first_name, ' ', client_admins.last_name)"), 'ILIKE', "%{$search}%")
				  ->orWhere('client_admins.email', 'ILIKE', "%{$search}%")
				  ->orWhere('client_admins.phone', 'ILIKE', "%{$search}%")
				  ->orWhere('client_admins.client_id', 'ILIKE', "%{$search}%");
			});
		}
		
		// Apply activity type filter
		if (!empty($activityType)) {
			$query->where(function($q) use ($activityType) {
				$q->where('activities_logs.subject', 'ILIKE', "%{$activityType}%")
				  ->orWhere('activities_logs.description', 'ILIKE', "%{$activityType}%");
			});
		}
		
		// Apply date filters to main query if provided
		// This filters clients whose most recent activity falls within the date range
		if ($fromDate !== '') {
			$query->where('activities_logs.created_at', '>=', $fromDate);
		}
		if ($toDate !== '') {
			$query->where('activities_logs.created_at', '<=', $this->activityDateEndInclusive($toDate));
		}
		
		// Filter: clients that have no applications created
		if ($hasApplications === '0') {
			$query->whereNotIn('activities_logs.client_id', Application::select('client_id'));
		}
		
		// Filter: last activity X+ years ago (1 to 5 years on yearly basis)
		if ($lastActivityYears !== '' && in_array((int) $lastActivityYears, [1, 2, 3, 4, 5], true)) {
			$query->where('activities_logs.created_at', '<=', Carbon::now()->subYears((int) $lastActivityYears));
		}
		
		// Filter: document count (0, 1, 2, ... 9, 10+)
		if ($documentCount !== '') {
			if ($documentCount === '0') {
				$query->where(function ($q) {
					$q->whereNull('doc_stats.doc_count')->orWhere('doc_stats.doc_count', 0);
				});
			} elseif ($documentCount === '10+') {
				$query->whereNotNull('doc_stats.doc_count')->where('doc_stats.doc_count', '>=', 10);
			} elseif (in_array($documentCount, ['1', '2', '3', '4', '5', '6', '7', '8', '9'], true)) {
				$query->where('doc_stats.doc_count', '=', (int) $documentCount);
			}
		}
		
		// Filter: document storage location (local, aws, both, none)
		if ($docStorage !== '' && in_array($docStorage, ['local', 'aws', 'both', 'none'], true)) {
			$docStorageExpr = "CASE
				WHEN COALESCE(doc_stats.count_local, 0) > 0 AND COALESCE(doc_stats.count_aws, 0) > 0 THEN 'both'
				WHEN COALESCE(doc_stats.doc_count, 0) > 0 AND COALESCE(doc_stats.count_local, 0) = COALESCE(doc_stats.doc_count, 0) THEN 'local'
				WHEN COALESCE(doc_stats.doc_count, 0) > 0 AND COALESCE(doc_stats.count_aws, 0) = COALESCE(doc_stats.doc_count, 0) THEN 'aws'
				ELSE 'none'
			END";
			$query->whereRaw("({$docStorageExpr}) = ?", [$docStorage]);
		}
		
		// Filter: no phone number (only clients with missing/empty phone)
		if ($noPhone === '1') {
			$query->where(function ($q) {
				$q->whereNull('client_admins.phone')
				  ->orWhere(DB::raw("TRIM(COALESCE(client_admins.phone, ''))"), '=', '');
			});
		}
		
		// Filter: no email address (only clients with missing/empty email)
		if ($noEmail === '1') {
			$query->where(function ($q) {
				$q->whereNull('client_admins.email')
				  ->orWhere(DB::raw("TRIM(COALESCE(client_admins.email, ''))"), '=', '');
			});
		}
		
		// Apply column sorting
		$allowedSortColumns = [
			'client_name' => DB::raw("CONCAT(client_admins.first_name, ' ', client_admins.last_name)"),
			'client_email' => 'client_admins.email',
			'client_phone' => 'client_admins.phone',
			'activity_date' => 'activities_logs.created_at',
			'modified_by' => DB::raw($this->modifiedByFullNameSql()),
		];
		
		if (isset($allowedSortColumns[$sortColumn])) {
			$query->orderBy($allowedSortColumns[$sortColumn], $sortOrder);
		} else {
			$query->orderBy('activities_logs.created_at', $sortOrder);
		}
		
		// One COUNT for page numbers (tabs/Total no longer need four storage-split counts).
		$totalData = (int) (clone $query)->toBase()->getCountForPagination();
		$lists = $query->simplePaginate($perPage)
			->appends($request->query());
		$lists = $this->withPageNumbers($lists, $totalData, $request);

		$viewData = compact([
			'lists',
			'totalData',
			'fromDate',
			'toDate',
			'sortOrder',
			'search',
			'activityType',
			'perPage',
			'sortColumn',
			'hasApplications',
			'lastActivityYears',
			'documentCount',
			'docStorage',
			'noPhone',
			'noEmail',
		]);

		return view('AdminConsole.recent_clients.index', $viewData);
	}

	/**
	 * Listing total from storage-tab counts (already computed; no extra COUNT).
	 * When a storage tab is active, total matches that tab's list.
	 *
	 * @param  array{local?: int, both?: int, aws?: int, storage?: int}  $storageCounts
	 */
	private function totalFromStorageCounts(array $storageCounts, string $docStorage): int
	{
		$local = (int) ($storageCounts['local'] ?? 0);
		$both = (int) ($storageCounts['both'] ?? 0);
		$aws = (int) ($storageCounts['aws'] ?? 0);
		$none = (int) ($storageCounts['storage'] ?? 0);

		return match ($docStorage) {
			'local' => $local,
			'both' => $both,
			'aws' => $aws,
			'none' => $none,
			default => $local + $both + $aws + $none,
		};
	}

	/**
	 * Activity actor first name: staff row if present, otherwise admin (same as client detail).
	 */
	private function modifiedByFirstNameSql(): string
	{
		return 'CASE WHEN modifier_staff.id IS NOT NULL THEN modifier_staff.first_name ELSE modifier_admins.first_name END';
	}

	/**
	 * Activity actor last name: staff row if present, otherwise admin (same as client detail).
	 */
	private function modifiedByLastNameSql(): string
	{
		return 'CASE WHEN modifier_staff.id IS NOT NULL THEN modifier_staff.last_name ELSE modifier_admins.last_name END';
	}

	/**
	 * Full activity actor name used for Modified By sort.
	 */
	private function modifiedByFullNameSql(): string
	{
		return 'CONCAT('.$this->modifiedByFirstNameSql().", ' ', ".$this->modifiedByLastNameSql().')';
	}

	/**
	 * Numbered pagination using the listing COUNT.
	 */
	private function withPageNumbers(Paginator $lists, int $totalData, Request $request): LengthAwarePaginator
	{
		return (new LengthAwarePaginator(
			$lists->items(),
			$totalData,
			max(1, (int) $lists->perPage()),
			max(1, (int) $lists->currentPage()),
			[
				'path' => $request->url(),
				'pageName' => $lists->getPageName(),
			]
		))->appends($request->query());
	}

	/**
	 * Get client counts per storage tab (local, both, aws) with same filters as index.
	 *
	 * @param Request $request
	 * @param string $fromDate
	 * @param string $toDate
	 * @param string $search
	 * @param string $activityType
	 * @param string $hasApplications
	 * @param string $lastActivityYears
	 * @param string $documentCount
	 * @param string $noPhone
	 * @param string $noEmail
	 * @return array{local: int, both: int, aws: int, storage: int}
	 */
	private function getStorageTabCounts(Request $request, $fromDate, $toDate, $search, $activityType, $hasApplications, $lastActivityYears, $documentCount, $noPhone, $noEmail): array
	{
		$subQuery = $this->latestActivityPerClientSubquery(
			$this->latestActivitySubqueryFromDate((string) $fromDate, (string) $toDate, (string) $lastActivityYears)
		);

		$docStatsSubQuery = Document::select(
				'client_id',
				DB::raw('COUNT(*) as doc_count'),
				DB::raw("SUM(CASE WHEN (myfile_key IS NULL OR TRIM(COALESCE(myfile_key, '')) = '') AND myfile IS NOT NULL AND TRIM(COALESCE(myfile, '')) != '' THEN 1 ELSE 0 END) AS count_local"),
				DB::raw("SUM(CASE WHEN myfile_key IS NOT NULL AND TRIM(myfile_key) != '' THEN 1 ELSE 0 END) AS count_aws")
			)
			->whereNull('archived_at')
			->appEduMigForStorage()
			->groupBy('client_id');

		$docStorageExpr = "CASE
			WHEN COALESCE(doc_stats.count_local, 0) > 0 AND COALESCE(doc_stats.count_aws, 0) > 0 THEN 'both'
			WHEN COALESCE(doc_stats.doc_count, 0) > 0 AND COALESCE(doc_stats.count_local, 0) = COALESCE(doc_stats.doc_count, 0) THEN 'local'
			WHEN COALESCE(doc_stats.doc_count, 0) > 0 AND COALESCE(doc_stats.count_aws, 0) = COALESCE(doc_stats.doc_count, 0) THEN 'aws'
			ELSE 'none'
		END";

		$query = ActivitiesLog::select('activities_logs.client_id')
			->joinSub($subQuery, 'latest_activities', function ($join) {
				$join->on('activities_logs.id', '=', 'latest_activities.last_activity_id');
			})
			->join('admins as client_admins', function ($join) {
				$join->on('activities_logs.client_id', '=', 'client_admins.id')
					->where(function ($q) {
						$q->whereIn('client_admins.is_archived', [0, '0'])
							->orWhereNull('client_admins.is_archived');
					});
			})
			->leftJoinSub($docStatsSubQuery, 'doc_stats', 'activities_logs.client_id', '=', 'doc_stats.client_id');

		if (!empty($search)) {
			$query->where(function ($q) use ($search) {
				$q->where(DB::raw("CONCAT(client_admins.first_name, ' ', client_admins.last_name)"), 'ILIKE', "%{$search}%")
					->orWhere('client_admins.email', 'ILIKE', "%{$search}%")
					->orWhere('client_admins.phone', 'ILIKE', "%{$search}%")
					->orWhere('client_admins.client_id', 'ILIKE', "%{$search}%");
			});
		}
		if (!empty($activityType)) {
			$query->where(function ($q) use ($activityType) {
				$q->where('activities_logs.subject', 'ILIKE', "%{$activityType}%")
					->orWhere('activities_logs.description', 'ILIKE', "%{$activityType}%");
			});
		}
		if ($fromDate !== '') {
			$query->where('activities_logs.created_at', '>=', $fromDate);
		}
		if ($toDate !== '') {
			$query->where('activities_logs.created_at', '<=', $this->activityDateEndInclusive($toDate));
		}
		if ($hasApplications === '0') {
			$query->whereNotIn('activities_logs.client_id', Application::select('client_id'));
		}
		if ($lastActivityYears !== '' && in_array((int) $lastActivityYears, [1, 2, 3, 4, 5], true)) {
			$query->where('activities_logs.created_at', '<=', Carbon::now()->subYears((int) $lastActivityYears));
		}
		if ($documentCount !== '') {
			if ($documentCount === '0') {
				$query->where(function ($q) {
					$q->whereNull('doc_stats.doc_count')->orWhere('doc_stats.doc_count', 0);
				});
			} elseif ($documentCount === '10+') {
				$query->whereNotNull('doc_stats.doc_count')->where('doc_stats.doc_count', '>=', 10);
			} elseif (in_array($documentCount, ['1', '2', '3', '4', '5', '6', '7', '8', '9'], true)) {
				$query->where('doc_stats.doc_count', '=', (int) $documentCount);
			}
		}
		if ($noPhone === '1') {
			$query->where(function ($q) {
				$q->whereNull('client_admins.phone')
					->orWhere(DB::raw("TRIM(COALESCE(client_admins.phone, ''))"), '=', '');
			});
		}
		if ($noEmail === '1') {
			$query->where(function ($q) {
				$q->whereNull('client_admins.email')
					->orWhere(DB::raw("TRIM(COALESCE(client_admins.email, ''))"), '=', '');
			});
		}

		$countLocal = (clone $query)->whereRaw("({$docStorageExpr}) = ?", ['local'])->count();
		$countBoth = (clone $query)->whereRaw("({$docStorageExpr}) = ?", ['both'])->count();
		$countAws = (clone $query)->whereRaw("({$docStorageExpr}) = ?", ['aws'])->count();
		$countStorage = (clone $query)->whereRaw("({$docStorageExpr}) = ?", ['none'])->count();

		return [
			'local'   => $countLocal,
			'both'    => $countBoth,
			'aws'     => $countAws,
			'storage' => $countStorage,
		];
	}
	
	/**
     * Get client details for expandable row (AJAX)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
	public function getClientDetails(Request $request)
	{
		$this->ensureSuperAdminAccess();

		$clientId = (int) $request->input('client_id');
		
		if (!$clientId || $clientId < 1) {
			return response()->json([
				'success' => false,
				'message' => 'Client ID is required'
			], 400);
		}
		
		// Get client info
		$client = Admin::where('id', $clientId)->first();
		
		if (!$client) {
			return response()->json([
				'success' => false,
				'message' => 'Client not found'
			], 404);
		}
		
		// Get last activity with creator info (staff first, then admin)
		$lastActivity = ActivitiesLog::where('client_id', $clientId)
			->orderBy('created_at', 'desc')
			->first();
		$activityActor = null;
		if ($lastActivity && is_numeric($lastActivity->created_by)) {
			$activityActor = ClientDetailEagerLoads::staffThenAdminByIds([$lastActivity->created_by])
				->get((int) $lastActivity->created_by);
		}
		
		// Get document count (same conditions as Storage and App/Edu/Mig: appEduMigForStorage + not_used_doc)
		$documentCount = Document::where('client_id', $clientId)
			->whereNull('archived_at')
			->appEduMigForStorage()
			->count();
		
		// Get document storage (same logic as Application/Education/Migration): App/Edu/Mig docs only
		$storageDocCount = Document::where('client_id', $clientId)
			->whereNull('archived_at')
			->appEduMigForStorage()
			->count();
		$countLocal = Document::where('client_id', $clientId)
			->whereNull('archived_at')
			->appEduMigForStorage()
			->where(function ($q) {
				$q->whereNull('myfile_key')->orWhere('myfile_key', '');
			})
			->whereNotNull('myfile')
			->where('myfile', '!=', '')
			->count();
		$countAws = Document::where('client_id', $clientId)
			->whereNull('archived_at')
			->appEduMigForStorage()
			->whereNotNull('myfile_key')
			->where('myfile_key', '!=', '')
			->count();
		if ($countLocal > 0 && $countAws > 0) {
			$documentStorage = 'both';
		} elseif ($storageDocCount > 0 && $countLocal === $storageDocCount) {
			$documentStorage = 'local';
		} elseif ($storageDocCount > 0 && $countAws === $storageDocCount) {
			$documentStorage = 'aws';
		} else {
			$documentStorage = 'none';
		}

		// Category doc counts (local/public folder only, not S3) - category_id resolved by default Application, Education, Migration
		$applicationCategoryId = DocumentCategory::where('name', 'Application')->default()->value('id');
		$educationCategoryId = DocumentCategory::where('name', 'Education')->default()->value('id');
		$migrationCategoryId = DocumentCategory::where('name', 'Migration')->default()->value('id');

		$applicationDocCountLocal = 0;
		if ($applicationCategoryId) {
			$applicationDocCountLocal = Document::where('client_id', $clientId)
				->where('type', 'client')
				->whereNull('archived_at')
				->whereNull('not_used_doc')
				->where('doc_type', 'documents')
				->where('category_id', $applicationCategoryId)
				->storedLocally()
				->count();
		}

		$educationDocCountLocal = 0;
		if ($educationCategoryId) {
			$educationDocCountLocal = Document::where('client_id', $clientId)
				->where('type', 'client')
				->whereNull('archived_at')
				->whereNull('not_used_doc')
				->where('doc_type', 'documents')
				->where('is_edu_and_mig_doc_migrate', Document::EDU_MIG_MIGRATE_SUCCESS)
				->where('category_id', $educationCategoryId)
				->storedLocally()
				->count();
		}

		$migrationDocCountLocal = 0;
		if ($migrationCategoryId) {
			$migrationDocCountLocal = Document::where('client_id', $clientId)
				->where('type', 'client')
				->whereNull('archived_at')
				->whereNull('not_used_doc')
				->where('doc_type', 'documents')
				->where('is_edu_and_mig_doc_migrate', Document::EDU_MIG_MIGRATE_SUCCESS)
				->where('category_id', $migrationCategoryId)
				->storedLocally()
				->count();
		}

		// Count docs that still have a public folder presence (local-only OR on S3 with doc_public_path) - same as popup list
		$applicationPublicPathCount = 0;
		$educationPublicPathCount = 0;
		$migrationPublicPathCount = 0;
		$publicPathCondition = function ($q) {
			$q->where(function ($q2) {
				$q2->whereNull('myfile_key')->orWhere('myfile_key', '');
			})->orWhere(function ($q2) {
				$q2->whereNotNull('myfile_key')->where('myfile_key', '!=', '')
					->whereNotNull('doc_public_path')->where('doc_public_path', '!=', '');
			});
		};
		if ($applicationCategoryId) {
			$applicationPublicPathCount = Document::where('client_id', $clientId)
				->where('type', 'client')
				->whereNull('archived_at')
				->whereNull('not_used_doc')
				->where('doc_type', 'documents')
				->where('category_id', $applicationCategoryId)
				->whereNotNull('myfile')
				->where('myfile', '!=', '')
				->where($publicPathCondition)
				->count();
		}
		if ($educationCategoryId) {
			$educationPublicPathCount = Document::where('client_id', $clientId)
				->where('type', 'client')
				->whereNull('archived_at')
				->whereNull('not_used_doc')
				->where('doc_type', 'documents')
				->where('is_edu_and_mig_doc_migrate', Document::EDU_MIG_MIGRATE_SUCCESS)
				->where('category_id', $educationCategoryId)
				->whereNotNull('myfile')
				->where('myfile', '!=', '')
				->where($publicPathCondition)
				->count();
		}
		if ($migrationCategoryId) {
			$migrationPublicPathCount = Document::where('client_id', $clientId)
				->where('type', 'client')
				->whereNull('archived_at')
				->whereNull('not_used_doc')
				->where('doc_type', 'documents')
				->where('is_edu_and_mig_doc_migrate', Document::EDU_MIG_MIGRATE_SUCCESS)
				->where('category_id', $migrationCategoryId)
				->whereNotNull('myfile')
				->where('myfile', '!=', '')
				->where($publicPathCondition)
				->count();
		}
		
		// Check if client is archived
		$isArchived = $client->is_archived == 1;
		
		return response()->json([
			'success' => true,
			'data' => [
				'client_id' => $clientId,
				'last_activity' => $lastActivity ? [
					'subject' => $lastActivity->subject,
					'description' => $lastActivity->description,
					'date' => $lastActivity->created_at->format('d/m/Y h:i A'),
					'created_by' => $activityActor
						? trim($activityActor->first_name.' '.$activityActor->last_name)
						: 'N/A'
				] : null,
				'document_count' => $documentCount,
				'document_storage' => $documentStorage,
				'count_local' => $countLocal,
				'count_aws' => $countAws,
				'application_doc_count_local' => $applicationDocCountLocal,
				'education_doc_count_local' => $educationDocCountLocal,
				'migration_doc_count_local' => $migrationDocCountLocal,
				'application_public_path_count' => $applicationPublicPathCount,
				'education_public_path_count' => $educationPublicPathCount,
				'migration_public_path_count' => $migrationPublicPathCount,
				'is_archived' => $isArchived
			]
		]);
	}

	/**
	 * Get documents for a client by category (Application, Education, Migration) - public folder only, for popup list.
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getClientDocumentsByCategory(Request $request)
	{
		$this->ensureSuperAdminAccess();

		$clientId = (int) $request->input('client_id');
		$category = $request->input('category'); // application, education, migration
		$category = is_array($category) ? '' : trim((string) $category);

		if (!$clientId || $clientId < 1) {
			return response()->json(['success' => false, 'message' => 'Client ID is required'], 400);
		}
		if (!in_array($category, ['application', 'education', 'migration'], true)) {
			return response()->json(['success' => false, 'message' => 'Invalid category'], 400);
		}

		$categoryId = null;
		if ($category === 'application') {
			$categoryId = DocumentCategory::where('name', 'Application')->default()->value('id');
		} elseif ($category === 'education') {
			$categoryId = DocumentCategory::where('name', 'Education')->default()->value('id');
		} else {
			$categoryId = DocumentCategory::where('name', 'Migration')->default()->value('id');
		}

		if (!$categoryId) {
			return response()->json(['success' => true, 'documents' => [], 'category_label' => ucfirst($category)]);
		}

		$query = Document::where('client_id', $clientId)
			->where('type', 'client')
			->whereNull('archived_at')
			->whereNull('not_used_doc')
			->where('doc_type', 'documents')
			->where('category_id', $categoryId)
			->whereNotNull('myfile')
			->where('myfile', '!=', '')
			->where(function ($q) {
				// Include: (a) stored locally (no S3), or (b) on S3 but still have doc_public_path (local copy exists)
				$q->where(function ($q2) {
					$q2->whereNull('myfile_key')->orWhere('myfile_key', '');
				})->orWhere(function ($q2) {
					$q2->whereNotNull('myfile_key')->where('myfile_key', '!=', '')
						->whereNotNull('doc_public_path')->where('doc_public_path', '!=', '');
				});
			});

		if ($category !== 'application') {
			$query->where('is_edu_and_mig_doc_migrate', Document::EDU_MIG_MIGRATE_SUCCESS);
		}

		$documents = $query->orderBy('created_at', 'desc')->get(['id', 'file_name', 'filetype', 'myfile', 'myfile_key', 'doc_public_path', 'created_at']);

		$list = [];
		foreach ($documents as $doc) {
			$isOnS3 = !empty(trim((string) ($doc->myfile_key ?? '')));
			$hasPublicPath = $isOnS3 && !empty(trim((string) ($doc->doc_public_path ?? '')));
			$previewUrl = null;
			if (!empty($doc->myfile)) {
				$previewUrl = $isOnS3 ? $doc->myfile : asset('img/documents/' . $doc->myfile);
			}
			$list[] = [
				'id' => $doc->id,
				'file_name' => $doc->file_name,
				'filetype' => $doc->filetype,
				'created_at' => $doc->created_at ? $doc->created_at->format('d/m/Y H:i') : null,
				'preview_url' => $previewUrl,
				'is_on_s3' => $isOnS3,
				'has_public_path' => $hasPublicPath,
			];
		}

		return response()->json([
			'success' => true,
			'documents' => $list,
			'category_label' => ucfirst($category),
		]);
	}

	/**
	 * Upload a single public (local) document to S3. Updates only myfile and myfile_key after successful upload.
	 * Does not delete the local file to avoid data loss.
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function uploadDocumentToS3(Request $request)
	{
		$this->ensureSuperAdminAccess();

		$documentId = (int) $request->input('document_id');
		if (!$documentId || $documentId < 1) {
			return response()->json(['success' => false, 'message' => 'Document ID is required'], 400);
		}

		$document = Document::find($documentId);
		if (!$document) {
			return response()->json(['success' => false, 'message' => 'Document not found'], 404);
		}

		// Only allow documents that are currently stored locally (public folder), not already on S3
		if (!empty(trim((string) ($document->myfile_key ?? '')))) {
			return response()->json(['success' => false, 'message' => 'Document is already on S3'], 400);
		}
		if (empty(trim((string) $document->myfile ?? ''))) {
			return response()->json(['success' => false, 'message' => 'Document has no local file path'], 400);
		}

		// Restrict to Application, Education, Migration categories
		$allowedCategoryNames = ['Application', 'Education', 'Migration'];
		$category = $document->category_id ? DocumentCategory::find($document->category_id) : null;
		if (!$category || !in_array($category->name, $allowedCategoryNames, true)) {
			return response()->json(['success' => false, 'message' => 'Document category not allowed for this upload'], 400);
		}

		$result = $this->uploadSingleDocumentToS3Internal($document);
		if (!$result['success']) {
			return response()->json(['success' => false, 'message' => $result['message']], $result['status'] ?? 400);
		}
		return response()->json([
			'success' => true,
			'message' => 'Document uploaded to S3 successfully',
			's3_url' => $result['s3_url'],
			'document_id' => $document->id,
		]);
	}

	/**
	 * Upload all Application, Education, Migration (local) documents for a client to S3.
	 * Only allowed when client storage is Local or Both (i.e. has some local docs).
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function uploadAllDocumentsToS3(Request $request)
	{
		$this->ensureSuperAdminAccess();

		$clientId = (int) $request->input('client_id');
		if (!$clientId || $clientId < 1) {
			return response()->json(['success' => false, 'message' => 'Client ID is required'], 400);
		}

		$client = Admin::find($clientId);
		if (!$client || empty(trim((string) ($client->client_id ?? '')))) {
			return response()->json(['success' => false, 'message' => 'Client not found or has no unique ID'], 404);
		}

		$applicationCategoryId = DocumentCategory::where('name', 'Application')->default()->value('id');
		$educationCategoryId = DocumentCategory::where('name', 'Education')->default()->value('id');
		$migrationCategoryId = DocumentCategory::where('name', 'Migration')->default()->value('id');

		$query = Document::where('client_id', $clientId)
			->where('type', 'client')
			->whereNull('archived_at')
			->whereNull('not_used_doc')
			->where('doc_type', 'documents')
			->whereNotNull('myfile')
			->where('myfile', '!=', '')
			->where(function ($q) {
				$q->whereNull('myfile_key')->orWhere('myfile_key', '');
			});

		$categoryIds = array_filter([$applicationCategoryId, $educationCategoryId, $migrationCategoryId]);
		if (count($categoryIds) > 0) {
			$query->where(function ($q) use ($applicationCategoryId, $educationCategoryId, $migrationCategoryId) {
				if ($applicationCategoryId) {
					$q->where('category_id', $applicationCategoryId);
				}
				if ($educationCategoryId) {
					$q->orWhere(function ($q2) use ($educationCategoryId) {
						$q2->where('category_id', $educationCategoryId)
							->where('is_edu_and_mig_doc_migrate', Document::EDU_MIG_MIGRATE_SUCCESS);
					});
				}
				if ($migrationCategoryId) {
					$q->orWhere(function ($q2) use ($migrationCategoryId) {
						$q2->where('category_id', $migrationCategoryId)
							->where('is_edu_and_mig_doc_migrate', Document::EDU_MIG_MIGRATE_SUCCESS);
					});
				}
			});
		} else {
			$query->whereRaw('1 = 0');
		}

		$documents = $query->get();
		$uploaded = 0;
		$failed = 0;
		$errors = [];

		foreach ($documents as $document) {
			$result = $this->uploadSingleDocumentToS3Internal($document);
			if ($result['success']) {
				$uploaded++;
			} else {
				$failed++;
				$errors[$document->id] = $result['message'];
			}
		}

		$message = $uploaded > 0
			? $uploaded . ' document(s) uploaded to S3 successfully.' . ($failed > 0 ? ' ' . $failed . ' failed.' : '')
			: ($failed > 0 ? 'No documents were uploaded. ' . $failed . ' failed.' : 'No local documents found to upload.');

		return response()->json([
			'success' => $uploaded > 0,
			'message' => $message,
			'uploaded_count' => $uploaded,
			'failed_count' => $failed,
			'errors' => $errors,
		]);
	}

	/**
	 * Bulk upload all Application/Education/Migration local documents for selected clients to S3,
	 * then remove public(local) paths for docs that are on S3.
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function bulkUploadAllDocumentsToS3(Request $request)
	{
		$this->ensureSuperAdminAccess();

		$clientIds = $request->input('client_ids', []);
		if (empty($clientIds) || !is_array($clientIds)) {
			return response()->json([
				'success' => false,
				'message' => 'First Select Client atlest 1 client.'
			], 400);
		}

		$clientIds = array_values(array_unique(array_map('intval', array_filter($clientIds))));
		if (empty($clientIds)) {
			return response()->json([
				'success' => false,
				'message' => 'First Select Client atlest 1 client.'
			], 400);
		}

		$totalUploaded = 0;
		$totalUploadFailed = 0;
		$totalPublicDeleted = 0;
		$totalPublicDeleteFailed = 0;
		$processedClients = 0;
		$errors = [];

		foreach ($clientIds as $clientId) {
			$clientResult = $this->uploadAndCleanupClientDocsToS3Internal($clientId);
			if ($clientResult['processed']) {
				$processedClients++;
			}

			$totalUploaded += (int) ($clientResult['uploaded'] ?? 0);
			$totalUploadFailed += (int) ($clientResult['upload_failed'] ?? 0);
			$totalPublicDeleted += (int) ($clientResult['public_deleted'] ?? 0);
			$totalPublicDeleteFailed += (int) ($clientResult['public_delete_failed'] ?? 0);

			if (!empty($clientResult['message'])) {
				$errors['client_' . $clientId] = $clientResult['message'];
			}
		}

		$success = $totalUploaded > 0 || $totalPublicDeleted > 0;
		$message = $success
			? 'Bulk upload completed. Uploaded: ' . $totalUploaded . ', Public paths removed: ' . $totalPublicDeleted . '.'
			: 'No documents were processed. Please check selected clients.';

		if ($totalUploadFailed > 0 || $totalPublicDeleteFailed > 0) {
			$message .= ' Failed uploads: ' . $totalUploadFailed . ', Failed public path removals: ' . $totalPublicDeleteFailed . '.';
		}

		return response()->json([
			'success' => $success,
			'message' => $message,
			'processed_clients' => $processedClients,
			'uploaded_count' => $totalUploaded,
			'upload_failed_count' => $totalUploadFailed,
			'public_deleted_count' => $totalPublicDeleted,
			'public_delete_failed_count' => $totalPublicDeleteFailed,
			'errors' => $errors,
		]);
	}

	/**
	 * Get bulk upload summary for selected clients:
	 * client reference ID and total eligible local files (App/Edu/Mig) to upload.
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function bulkUploadSummary(Request $request)
	{
		$this->ensureSuperAdminAccess();

		$clientIds = $request->input('client_ids', []);
		if (empty($clientIds) || !is_array($clientIds)) {
			return response()->json([
				'success' => false,
				'message' => 'First Select Client atlest 1 client.'
			], 400);
		}

		$clientIds = array_values(array_unique(array_map('intval', array_filter($clientIds))));
		if (empty($clientIds)) {
			return response()->json([
				'success' => false,
				'message' => 'First Select Client atlest 1 client.'
			], 400);
		}

		$applicationCategoryId = DocumentCategory::where('name', 'Application')->default()->value('id');
		$educationCategoryId = DocumentCategory::where('name', 'Education')->default()->value('id');
		$migrationCategoryId = DocumentCategory::where('name', 'Migration')->default()->value('id');

		$rows = [];
		$totalFiles = 0;
		foreach ($clientIds as $clientId) {
			$client = Admin::select('id', 'client_id')->find($clientId);
			$referenceId = trim((string) ($client->client_id ?? ''));
			if ($referenceId === '') {
				$referenceId = 'ID-' . $clientId;
			}

			$fileCount = $this->countEligibleLocalDocsForClientInternal(
				$clientId,
				$applicationCategoryId,
				$educationCategoryId,
				$migrationCategoryId
			);
			$totalFiles += $fileCount;

			$rows[] = [
				'client_id' => $clientId,
				'client_reference_id' => $referenceId,
				'total_files' => $fileCount,
			];
		}

		return response()->json([
			'success' => true,
			'total_clients' => count($rows),
			'total_files' => $totalFiles,
			'rows' => $rows,
		]);
	}

	/**
	 * Internal: upload one document (local, App/Edu/Mig) to S3. Updates document record.
	 *
	 * @param Document $document
	 * @return array{success: bool, message?: string, status?: int, s3_url?: string}
	 */
	private function uploadSingleDocumentToS3Internal(Document $document): array
	{
		$documentId = $document->id;
		$client = Admin::where('id', $document->client_id)->first();
		if (!$client || empty(trim((string) ($client->client_id ?? '')))) {
			return ['success' => false, 'message' => 'Client unique ID not found', 'status' => 400];
		}
		$clientUniqueId = trim($client->client_id);

		$relativePath = ltrim(str_replace('\\', '/', $document->myfile), '/');
		$localPath = public_path('img/documents/' . $relativePath);
		if (!file_exists($localPath) || !is_readable($localPath)) {
			Log::warning('Upload to S3: local file not found or not readable', ['document_id' => $documentId, 'path' => $localPath]);
			return ['success' => false, 'message' => 'Local file not found or not readable', 'status' => 404];
		}

		$fileContents = file_get_contents($localPath);
		if ($fileContents === false) {
			return ['success' => false, 'message' => 'Failed to read local file', 'status' => 500];
		}

		$originalName = basename($relativePath);
		$sanitized = $this->sanitizeFilenameForS3($originalName);
		$s3FileName = time() . '_' . $documentId . '_' . $sanitized;
		$docType = $document->doc_type ?: 'documents';
		$s3Key = $clientUniqueId . '/' . $docType . '/' . $s3FileName;

		try {
			$put = Storage::disk('s3')->put($s3Key, $fileContents);
			if (!$put) {
				Log::error('Upload to S3: put returned false', ['document_id' => $documentId, 's3_key' => $s3Key]);
				return ['success' => false, 'message' => 'S3 upload failed', 'status' => 500];
			}
			$fileUrl = Storage::disk('s3')->url($s3Key);
		} catch (\Throwable $e) {
			Log::error('Upload to S3 exception', ['document_id' => $documentId, 'error' => $e->getMessage()]);
			return ['success' => false, 'message' => 'S3 upload error: ' . $e->getMessage(), 'status' => 500];
		}

		$document->doc_public_path = $document->myfile;
		$document->myfile = $fileUrl;
		$document->myfile_key = $s3FileName;
		$document->save();

		return ['success' => true, 's3_url' => $fileUrl];
	}

	/**
	 * Internal: for one client, upload all local App/Edu/Mig docs to S3,
	 * then delete all public(local) copies for App/Edu/Mig docs already on S3.
	 *
	 * @param int $clientId
	 * @return array{processed: bool, uploaded: int, upload_failed: int, public_deleted: int, public_delete_failed: int, message?: string}
	 */
	private function uploadAndCleanupClientDocsToS3Internal(int $clientId): array
	{
		$result = [
			'processed' => false,
			'uploaded' => 0,
			'upload_failed' => 0,
			'public_deleted' => 0,
			'public_delete_failed' => 0,
		];

		if ($clientId < 1) {
			$result['message'] = 'Invalid client ID';
			return $result;
		}

		$client = Admin::find($clientId);
		if (!$client || empty(trim((string) ($client->client_id ?? '')))) {
			$result['message'] = 'Client not found or has no unique ID';
			return $result;
		}

		$result['processed'] = true;

		$applicationCategoryId = DocumentCategory::where('name', 'Application')->default()->value('id');
		$educationCategoryId = DocumentCategory::where('name', 'Education')->default()->value('id');
		$migrationCategoryId = DocumentCategory::where('name', 'Migration')->default()->value('id');

		$categoryIds = array_filter([$applicationCategoryId, $educationCategoryId, $migrationCategoryId]);
		if (count($categoryIds) === 0) {
			return $result;
		}

		$uploadQuery = Document::where('client_id', $clientId)
			->where('type', 'client')
			->whereNull('archived_at')
			->whereNull('not_used_doc')
			->where('doc_type', 'documents')
			->whereNotNull('myfile')
			->where('myfile', '!=', '')
			->where(function ($q) {
				$q->whereNull('myfile_key')->orWhere('myfile_key', '');
			})
			->where(function ($q) use ($applicationCategoryId, $educationCategoryId, $migrationCategoryId) {
				if ($applicationCategoryId) {
					$q->where('category_id', $applicationCategoryId);
				}
				if ($educationCategoryId) {
					$q->orWhere(function ($q2) use ($educationCategoryId) {
						$q2->where('category_id', $educationCategoryId)
							->where('is_edu_and_mig_doc_migrate', Document::EDU_MIG_MIGRATE_SUCCESS);
					});
				}
				if ($migrationCategoryId) {
					$q->orWhere(function ($q2) use ($migrationCategoryId) {
						$q2->where('category_id', $migrationCategoryId)
							->where('is_edu_and_mig_doc_migrate', Document::EDU_MIG_MIGRATE_SUCCESS);
					});
				}
			});

		$docsToUpload = $uploadQuery->get();
		foreach ($docsToUpload as $document) {
			$upload = $this->uploadSingleDocumentToS3Internal($document);
			if ($upload['success']) {
				$result['uploaded']++;
			} else {
				$result['upload_failed']++;
			}
		}

		$cleanupQuery = Document::where('client_id', $clientId)
			->where('type', 'client')
			->whereNull('archived_at')
			->whereNull('not_used_doc')
			->where('doc_type', 'documents')
			->whereNotNull('myfile_key')
			->where('myfile_key', '!=', '')
			->whereNotNull('doc_public_path')
			->where('doc_public_path', '!=', '')
			->where(function ($q) use ($applicationCategoryId, $educationCategoryId, $migrationCategoryId) {
				if ($applicationCategoryId) {
					$q->where('category_id', $applicationCategoryId);
				}
				if ($educationCategoryId) {
					$q->orWhere(function ($q2) use ($educationCategoryId) {
						$q2->where('category_id', $educationCategoryId)
							->where('is_edu_and_mig_doc_migrate', Document::EDU_MIG_MIGRATE_SUCCESS);
					});
				}
				if ($migrationCategoryId) {
					$q->orWhere(function ($q2) use ($migrationCategoryId) {
						$q2->where('category_id', $migrationCategoryId)
							->where('is_edu_and_mig_doc_migrate', Document::EDU_MIG_MIGRATE_SUCCESS);
					});
				}
			});

		$docsToCleanup = $cleanupQuery->get();
		foreach ($docsToCleanup as $document) {
			$cleanup = $this->deleteOnePublicDocInternal($document);
			if ($cleanup['success']) {
				$result['public_deleted']++;
			} else {
				$result['public_delete_failed']++;
			}
		}

		return $result;
	}

	/**
	 * Internal: count eligible local docs for S3 upload by client (Application/Education/Migration).
	 *
	 * @param int $clientId
	 * @param int|null $applicationCategoryId
	 * @param int|null $educationCategoryId
	 * @param int|null $migrationCategoryId
	 * @return int
	 */
	private function countEligibleLocalDocsForClientInternal(
		int $clientId,
		$applicationCategoryId,
		$educationCategoryId,
		$migrationCategoryId
	): int {
		$categoryIds = array_filter([$applicationCategoryId, $educationCategoryId, $migrationCategoryId]);
		if ($clientId < 1 || count($categoryIds) === 0) {
			return 0;
		}

		$query = Document::where('client_id', $clientId)
			->where('type', 'client')
			->whereNull('archived_at')
			->whereNull('not_used_doc')
			->where('doc_type', 'documents')
			->whereNotNull('myfile')
			->where('myfile', '!=', '')
			->where(function ($q) {
				$q->whereNull('myfile_key')->orWhere('myfile_key', '');
			})
			->where(function ($q) use ($applicationCategoryId, $educationCategoryId, $migrationCategoryId) {
				if ($applicationCategoryId) {
					$q->where('category_id', $applicationCategoryId);
				}
				if ($educationCategoryId) {
					$q->orWhere(function ($q2) use ($educationCategoryId) {
						$q2->where('category_id', $educationCategoryId)
							->where('is_edu_and_mig_doc_migrate', Document::EDU_MIG_MIGRATE_SUCCESS);
					});
				}
				if ($migrationCategoryId) {
					$q->orWhere(function ($q2) use ($migrationCategoryId) {
						$q2->where('category_id', $migrationCategoryId)
							->where('is_edu_and_mig_doc_migrate', Document::EDU_MIG_MIGRATE_SUCCESS);
					});
				}
			});

		return (int) $query->count();
	}

	/**
	 * Delete the local (public) copy of a document that is already on S3.
	 * Requires doc_public_path to be set (saved at S3 upload time). Clears doc_public_path after delete.
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function deletePublicDoc(Request $request)
	{
		$this->ensureSuperAdminAccess();

		$documentId = (int) $request->input('document_id');
		if (!$documentId || $documentId < 1) {
			return response()->json(['success' => false, 'message' => 'Document ID is required'], 400);
		}

		$document = Document::find($documentId);
		if (!$document) {
			return response()->json(['success' => false, 'message' => 'Document not found'], 404);
		}

		if (empty(trim((string) ($document->myfile_key ?? '')))) {
			return response()->json(['success' => false, 'message' => 'Document is not on S3; nothing to delete for public copy'], 400);
		}
		$publicPath = trim((string) ($document->doc_public_path ?? ''));
		if ($publicPath === '') {
			return response()->json(['success' => false, 'message' => 'No public path stored; local copy may already be deleted'], 400);
		}

		$result = $this->deleteOnePublicDocInternal($document);
		if (!$result['success']) {
			return response()->json(['success' => false, 'message' => $result['message']], $result['status'] ?? 400);
		}
		return response()->json([
			'success' => true,
			'message' => 'Public document deleted successfully',
			'document_id' => $document->id,
		]);
	}

	/**
	 * Delete all public (local) copies for documents in a category that are already on S3.
	 * Only documents that have doc_public_path set are processed.
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function deleteAllPublicDocsByCategory(Request $request)
	{
		$this->ensureSuperAdminAccess();

		$clientId = (int) $request->input('client_id');
		$category = $request->input('category');
		$category = is_array($category) ? '' : trim((string) $category);

		if (!$clientId || $clientId < 1) {
			return response()->json(['success' => false, 'message' => 'Client ID is required'], 400);
		}
		if (!in_array($category, ['application', 'education', 'migration'], true)) {
			return response()->json(['success' => false, 'message' => 'Invalid category'], 400);
		}

		$categoryId = null;
		if ($category === 'application') {
			$categoryId = DocumentCategory::where('name', 'Application')->default()->value('id');
		} elseif ($category === 'education') {
			$categoryId = DocumentCategory::where('name', 'Education')->default()->value('id');
		} else {
			$categoryId = DocumentCategory::where('name', 'Migration')->default()->value('id');
		}

		if (!$categoryId) {
			return response()->json(['success' => true, 'message' => 'No documents found', 'deleted_count' => 0]);
		}

		$query = Document::where('client_id', $clientId)
			->where('type', 'client')
			->whereNull('archived_at')
			->whereNull('not_used_doc')
			->where('doc_type', 'documents')
			->where('category_id', $categoryId)
			->whereNotNull('myfile_key')
			->where('myfile_key', '!=', '')
			->whereNotNull('doc_public_path')
			->where('doc_public_path', '!=', '');

		if ($category !== 'application') {
			$query->where('is_edu_and_mig_doc_migrate', Document::EDU_MIG_MIGRATE_SUCCESS);
		}

		$documents = $query->get();
		$deleted = 0;
		$failed = 0;

		foreach ($documents as $document) {
			$result = $this->deleteOnePublicDocInternal($document);
			if ($result['success']) {
				$deleted++;
			} else {
				$failed++;
			}
		}

		$message = $deleted > 0
			? $deleted . ' public document(s) deleted successfully.' . ($failed > 0 ? ' ' . $failed . ' failed.' : '')
			: ($failed > 0 ? 'No documents were deleted. ' . $failed . ' failed.' : 'No public documents found to delete.');

		return response()->json([
			'success' => $deleted > 0,
			'message' => $message,
			'deleted_count' => $deleted,
		]);
	}

	/**
	 * Internal: delete the local file for one document (on S3 with doc_public_path). Clears doc_public_path.
	 *
	 * @param Document $document
	 * @return array{success: bool, message?: string, status?: int}
	 */
	private function deleteOnePublicDocInternal(Document $document): array
	{
		$publicPath = trim((string) ($document->doc_public_path ?? ''));
		if ($publicPath === '') {
			return ['success' => true];
		}

		$relativePath = ltrim(str_replace('\\', '/', $publicPath), '/');
		if (preg_match('#^https?://#i', $relativePath)) {
			$parsed = parse_url($relativePath);
			$path = isset($parsed['path']) ? ltrim($parsed['path'], '/') : '';
			$prefix = 'img/documents/';
			if (stripos($path, $prefix) === 0) {
				$relativePath = substr($path, strlen($prefix));
			} else {
				$relativePath = $path;
			}
			$relativePath = ltrim($relativePath, '/');
		}
		if ($relativePath !== '' && stripos($relativePath, 'img/documents/') === 0) {
			$relativePath = ltrim(substr($relativePath, strlen('img/documents/')), '/');
		}
		if ($relativePath === '' || preg_match('#\.\./#', $relativePath)) {
			return ['success' => false, 'message' => 'Invalid path', 'status' => 400];
		}

		$baseDir = realpath(public_path('img/documents'));
		if ($baseDir === false) {
			$document->doc_public_path = null;
			$document->save();
			return ['success' => true];
		}

		$candidatePath = public_path('img/documents/' . $relativePath);
		$resolvedPath = realpath($candidatePath);
		$usedBaseDir = $baseDir;

		// Fallback: on some servers document root is app root (not public/), so file may be at base_path('img/documents/...')
		if ($resolvedPath === false) {
			$fallbackPath = base_path('img/documents/' . $relativePath);
			$fallbackBaseDir = realpath(base_path('img/documents'));
			if ($fallbackBaseDir !== false) {
				$resolvedPath = realpath($fallbackPath);
				if ($resolvedPath !== false && strpos($resolvedPath, $fallbackBaseDir) === 0 && is_file($resolvedPath)) {
					$usedBaseDir = $fallbackBaseDir;
				} else {
					$resolvedPath = false;
				}
			}
		}

		if ($resolvedPath === false) {
			$fallbackPath = base_path('img/documents/' . $relativePath);
			$fallbackBaseDirRaw = base_path('img/documents');
			$fallbackBaseDirResolved = realpath($fallbackBaseDirRaw);
			Log::warning('Delete public doc: realpath failed (file not found or not resolvable)', [
				'document_id' => $document->id,
				'doc_public_path' => $document->doc_public_path,
				'relative_path' => $relativePath,
				'candidate_path' => $candidatePath,
				'public_path_base' => public_path(),
				'file_exists' => file_exists($candidatePath),
				'is_readable' => is_readable($candidatePath),
				'fallback_path' => $fallbackPath,
				'fallback_base_dir_exists' => $fallbackBaseDirResolved !== false,
				'fallback_file_exists' => file_exists($fallbackPath),
			]);
			$document->doc_public_path = null;
			$document->save();
			return ['success' => true];
		}
		if (strpos($resolvedPath, $usedBaseDir) !== 0 || !is_file($resolvedPath)) {
			return ['success' => false, 'message' => 'Invalid path', 'status' => 400];
		}

		try {
			unlink($resolvedPath);
		} catch (\Throwable $e) {
			Log::error('Delete public doc: unlink failed', ['document_id' => $document->id, 'path' => $resolvedPath, 'error' => $e->getMessage()]);
			return ['success' => false, 'message' => 'Failed to delete file', 'status' => 500];
		}

		$document->doc_public_path = null;
		$document->save();
		return ['success' => true];
	}

	/**
	 * Delete a document permanently: remove from documents table and delete file from public path.
	 * Document must not be on S3 (local only).
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function deleteDocument(Request $request)
	{
		$this->ensureSuperAdminAccess();

		$documentId = (int) $request->input('document_id');
		if (!$documentId || $documentId < 1) {
			return response()->json(['success' => false, 'message' => 'Document ID is required'], 400);
		}

		$document = Document::find($documentId);
		if (!$document) {
			return response()->json(['success' => false, 'message' => 'Document not found'], 404);
		}

		// Only allow delete when document is not on S3 (local only)
		if (!empty(trim((string) ($document->myfile_key ?? '')))) {
			return response()->json(['success' => false, 'message' => 'Cannot delete document that is on S3'], 400);
		}

		$allowedCategoryNames = ['Application', 'Education', 'Migration'];
		$category = $document->category_id ? DocumentCategory::find($document->category_id) : null;
		if (!$category || !in_array($category->name, $allowedCategoryNames, true)) {
			return response()->json(['success' => false, 'message' => 'Document category not allowed for this action'], 400);
		}

		// Resolve local file path (myfile for local-only docs)
		$localPath = trim((string) ($document->myfile ?? ''));
		if ($localPath !== '') {
			$relativePath = ltrim(str_replace('\\', '/', $localPath), '/');
			if (preg_match('#^https?://#i', $relativePath)) {
				$parsed = parse_url($relativePath);
				$path = isset($parsed['path']) ? ltrim($parsed['path'], '/') : '';
				$prefix = 'img/documents/';
				if (stripos($path, $prefix) === 0) {
					$relativePath = substr($path, strlen($prefix));
				} else {
					$relativePath = $path;
				}
				$relativePath = ltrim($relativePath, '/');
			}
			if ($relativePath !== '' && stripos($relativePath, 'img/documents/') === 0) {
				$relativePath = ltrim(substr($relativePath, strlen('img/documents/')), '/');
			}
			if ($relativePath !== '' && !preg_match('#\.\./#', $relativePath)) {
				$baseDir = realpath(public_path('img/documents'));
				if ($baseDir !== false) {
					$candidatePath = public_path('img/documents/' . $relativePath);
					$resolvedPath = realpath($candidatePath);
					if ($resolvedPath !== false && strpos($resolvedPath, $baseDir) === 0 && is_file($resolvedPath)) {
						try {
							unlink($resolvedPath);
						} catch (\Throwable $e) {
							Log::warning('Delete document: unlink failed', ['document_id' => $documentId, 'path' => $resolvedPath, 'error' => $e->getMessage()]);
						}
					}
				}
			}
		}

		// Remove documents table row after local file unlink (local-only docs).
		$document->delete();

		return response()->json([
			'success' => true,
			'message' => 'Document deleted successfully',
			'document_id' => $documentId,
		]);
	}

	/**
	 * Sanitize filename for S3 path to prevent 403 (same idea as EmailUploadV2Controller).
	 *
	 * @param string $filename
	 * @return string
	 */
	private function sanitizeFilenameForS3(string $filename): string
	{
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$base = pathinfo($filename, PATHINFO_FILENAME);
		$base = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $base);
		$base = preg_replace('/_+/', '_', $base);
		$base = trim($base, '_');
		if ($base === '') {
			$base = 'doc_' . time();
		}
		return $ext ? $base . '.' . $ext : $base;
	}
	
	/**
     * Archive or unarchive a client
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
	public function toggleArchive(Request $request)
	{
		$this->ensureSuperAdminAccess();

		$clientId = $request->input('client_id');
		$action = $request->input('action'); // 'archive' or 'unarchive'
		
		if (!$clientId) {
			return response()->json([
				'success' => false,
				'message' => 'Client ID is required'
			], 400);
		}
		
		// Clients only (role = 7) — never archive staff/admin accounts
		$client = Admin::where('id', $clientId)->where('role', 7)->first();
		
		if (!$client) {
			return response()->json([
				'success' => false,
				'message' => 'Client not found'
			], 404);
		}
		
		// Determine archive status based on action
		if ($action === 'archive') {
			$isArchived = 1;
			$updateData = [
				'is_archived' => $isArchived,
				'archived_on' => date('Y-m-d'),
				'archived_by' => Auth::user()->id
			];
			$message = 'Client has been archived successfully.';
		} else if ($action === 'unarchive') {
			$isArchived = 0;
			$updateData = [
				'is_archived' => $isArchived,
				'archived_on' => null,
				'archived_by' => null
			];
			$message = 'Client has been unarchived successfully.';
		} else {
			return response()->json([
				'success' => false,
				'message' => 'Invalid action. Use "archive" or "unarchive".'
			], 400);
		}
		
		// Update the client
		$updated = DB::table('admins')->where('id', $clientId)->update($updateData);
		
		if ($updated) {
			// Log the activity
			$subject = $action === 'archive' ? 'Client has been archived' : 'Client has been unarchived';
			$activity = new ActivitiesLog();
			$activity->client_id = $clientId;
			$activity->created_by = Auth::user()->id;
			$activity->subject = $subject;
			$activity->description = $subject . ' by ' . Auth::user()->first_name . ' ' . Auth::user()->last_name;
			$activity->task_status = 0;
			$activity->pin = 0;
			$activity->save();
			
			return response()->json([
				'success' => true,
				'message' => $message,
				'is_archived' => $isArchived
			]);
		} else {
			return response()->json([
				'success' => false,
				'message' => 'Failed to update client. Please try again.'
			], 500);
		}
	}
	
	/**
	 * Bulk archive selected clients
	 *
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function bulkArchive(Request $request)
	{
		$this->ensureSuperAdminAccess();

		$clientIds = $request->input('client_ids', []);
		
		if (empty($clientIds) || !is_array($clientIds)) {
			return response()->json([
				'success' => false,
				'message' => 'Please select at least one client to archive.',
			], 400);
		}
		
		$clientIds = array_map('intval', array_filter($clientIds));
		
		if (empty($clientIds)) {
			return response()->json([
				'success' => false,
				'message' => 'Please select at least one client to archive.',
			], 400);
		}
		
		// Clients only (role = 7), not archived (is_archived = 0 or NULL)
		$clients = Admin::whereIn('id', $clientIds)
			->where('role', 7)
			->where(function ($q) {
				$q->whereIn('is_archived', [0, '0'])
				  ->orWhereNull('is_archived');
			})
			->get();
		
		$archived = 0;
		$updateData = [
			'is_archived' => 1,
			'archived_on' => date('Y-m-d'),
			'archived_by' => Auth::user()->id
		];
		
		foreach ($clients as $client) {
			$updated = DB::table('admins')->where('id', $client->id)->update($updateData);
			if ($updated) {
				$archived++;
				$subject = 'Client has been archived';
				$activity = new ActivitiesLog();
				$activity->client_id = $client->id;
				$activity->created_by = Auth::user()->id;
				$activity->subject = $subject;
				$activity->description = $subject . ' by ' . Auth::user()->first_name . ' ' . Auth::user()->last_name;
				$activity->task_status = 0;
				$activity->pin = 0;
				$activity->save();
			}
		}
		
		$message = $archived === 1
			? '1 client has been archived successfully.'
			: $archived . ' clients have been archived successfully.';
		
		return response()->json([
			'success' => true,
			'message' => $message,
			'archived_count' => $archived,
		]);
	}
}
