<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

use App\Models\Admin;
use App\Models\Report;
use App\Models\Application;
use App\Models\CheckinLog;
use App\Models\Invoice;
use App\Models\StaffRole;
// use App\Models\Task; // Task system removed - December 2025
 
use Auth; 
use Config;

class ReportController extends Controller
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
     * All Vendors.
     *
     * @return \Illuminate\Http\Response
     */
	public function client(Request $request)  
	{		
		// Sidebar: Reports (role 1|12) + module 62
		$this->ensureReportsModuleAccess('62');

		$query 		= Admin::where('is_archived', '=', '0'); 		  
		$totalData 	= $query->count();	//for all data
		$lists		= $query->sortable(['id' => 'desc'])->paginate(20);
		
		return view('Admin.reports.client', compact(['lists', 'totalData'])); 	
		//return view('Admin.reports.client');
	}
	public function application(Request $request)  
	{		
		// Sidebar: Reports (role 1|12) + module 62
		$this->ensureReportsModuleAccess('62');

		$query 		= Application::query(); 		  
		$totalData 	= $query->count();	//for all data
		$lists		= $query->sortable(['id' => 'desc'])->paginate(20);
		
		return view('Admin.reports.application', compact(['lists', 'totalData'])); 
		//return view('Admin.reports.application');
	}
	public function invoice(Request $request)  
	{	
		// Sidebar: Reports (role 1|12) + module 63
		$this->ensureReportsModuleAccess('63');

		$query 		= Invoice::query(); 		  
		$totalData 	= $query->count();	//for all data
		$lists		= $query->sortable(['id' => 'desc'])->paginate(20);
		
		return view('Admin.reports.invoice', compact(['lists', 'totalData'])); 
		//return view('Admin.reports.invoice');
	}
	public function office_visit(Request $request)  
	{		
		// Sidebar: Reports (role 1|12) + module 64
		$this->ensureReportsModuleAccess('64');

		$query 		= CheckinLog::query();  	  
		$totalData 	= $query->count();	//for all data
		$lists		= $query->sortable(['id' => 'desc'])->paginate(20);
		
		return view('Admin.reports.office-task-report', compact(['lists', 'totalData']));
		// return view('Admin.reports.office-visit', compact(['lists', 'totalData']));
		//return view('Admin.reports.office-visit');
	}
	public function saleforecast_application(Request $request)  
	{	
		// Sidebar: Reports (role 1|12) + module 65
		$this->ensureReportsModuleAccess('65');

		$query 		= Application::query(); 		  
		$totalData 	= $query->count();	//for all data
		$lists		= $query->sortable(['id' => 'desc'])->paginate(20);
		
		return view('Admin.reports.saleforecast-application', compact(['lists', 'totalData']));
		//return view('Admin.reports.sale-forecast');
	}
	// Interested services report removed - applications are created directly
	// Task system removed - December 2025 (inactive for 16+ months)
	// Database tables preserved: tasks, task_logs, to_do_groups
	/*
	public function personal_task(Request $request)  
	{	
		$query 		= Task::query();  	  
		$totalData 	= $query->count();	//for all data
		$lists		= $query->sortable(['id' => 'desc'])->paginate(20);
		
		//return view('Admin.reports.tasks');
	}
	public function office_task(Request $request)  
	{	
		$query 		= Task::query();  	  
		$totalData 	= $query->count();	//for all data
		$lists		= $query->sortable(['id' => 'desc'])->paginate(20);
		
		return view('Admin.reports.office-task-report', compact(['lists', 'totalData']));
		//return view('Admin.reports.tasks');
	}
	*/
	
	public function visaexpires(Request $request)  
	{	
		// Match Reports menu (left-side-bar): super admin (1) or admin (12) only
		$this->ensureReportsRoleAccess();
		return view('Admin.reports.visaexpires');
	}
	public function actionCalendar(Request $request)  
	{	
		// Intentionally login-only: view already scopes data (role 1 = all, others = assigned_to self)
		return view('Admin.reports.action_calendar');
	}
	public function agreementexpires(Request $request)  
	{	
		// Match Reports menu (left-side-bar): super admin (1) or admin (12) only
		$this->ensureReportsRoleAccess();
		return view('Admin.reports.agreementexpires');
	}

	/**
	 * Server-side gate for reports that only appear under Reports (role 1 or 12).
	 */
	private function ensureReportsRoleAccess(): void
	{
		$role = Auth::user()->role ?? null;
		if ($role != 1 && $role != 12) {
			abort(403, 'Unauthorized.');
		}
	}

	/**
	 * Match left-side-bar: role 1|12 and StaffRole module_access key (e.g. 62–65).
	 */
	private function ensureReportsModuleAccess(string $moduleKey): void
	{
		$this->ensureReportsRoleAccess();

		$staffRole = StaffRole::find(Auth::user()->role);
		if (!$staffRole || $staffRole->module_access === null || $staffRole->module_access === '') {
			abort(403, 'Unauthorized.');
		}

		$moduleAccess = (array) json_decode($staffRole->module_access);
		if (!array_key_exists((string) $moduleKey, $moduleAccess)) {
			abort(403, 'Unauthorized.');
		}
	}
	
	//Daily no of person office visit
    public function noofpersonofficevisit(Request $request)
	{
		// Sidebar: role == 1 only (Office Visit Report Date wise)
		if ((Auth::user()->role ?? null) != 1) {
			abort(403, 'Unauthorized.');
		}

		//SELECT date, count(id) as personCount FROM `checkin_logs` group by date order by date desc;
         $lists = DB::table('checkin_logs')
        ->join('branches', 'branches.id', '=', 'checkin_logs.office')
        ->select(DB::raw('checkin_logs.date,branches.office_name,count(checkin_logs.id) as person_count'))
        ->groupBy(['checkin_logs.date', 'checkin_logs.office', 'branches.office_name'])
        ->orderByRaw('checkin_logs.date DESC NULLS LAST')
        ->paginate(5);

		// Full result total (not current page size); Sno offset matches paginate(5) via firstItem()
		$totalData = $lists->total();
		$i = ($lists->firstItem() ?? 1) - 1;

		return view('Admin.reports.noofpersonofficevisit', compact(['lists', 'totalData']))
			->with('i', $i);
	}
	
}
