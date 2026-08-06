<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

use App\Models\Admin;
use App\Models\StaffRole;

use Auth;
use Config;

class StaffroleController extends Controller
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
     * All Staff Roles.
     *
     * @return \Illuminate\Http\Response
     */
	public function index(Request $request)
	{
		//check authorization start	
			$check = $this->checkAuthorizationAction('user_role', $request->route()->getActionMethod(), Auth::user()->role);
			if($check)
			{
				return redirect()->route('dashboard')->with('error',config('constants.unauthorized'));
			}	
	//check authorization end
	$query 		= StaffRole::query();
		 
		$totalData 	= $query->count();	//for all data

		$lists		= $query->sortable(['id' => 'desc'])->paginate(config('constants.limit'));
		
		return view('Admin.staffrole.index',compact(['lists', 'totalData']));	

		//return view('Admin.usertype.index');	
	}
	
	public function create(Request $request) 
	{
			//check authorization start	
			$check = $this->checkAuthorizationAction('user_role', $request->route()->getActionMethod(), Auth::user()->role);
			if($check)
			{
				return redirect()->route('dashboard')->with('error',config('constants.unauthorized'));
			}	
		//check authorization end
		return view('Admin.staffrole.create');	
	} 
	
	public function store(Request $request)
	{
		//check authorization start
		$check = $this->checkAuthorizationAction('user_role', $request->route()->getActionMethod(), Auth::user()->role);
		if ($check) {
			return redirect()->route('dashboard')->with('error', config('constants.unauthorized'));
		}
		//check authorization end

		if ($request->isMethod('post')) {
			// Form field is name (not legacy usertype). Unique skipped so legacy duplicate names still save.
			$this->validate($request, [
				'name' => 'required|string|max:255',
				'description' => 'nullable|string|max:5000',
				'module_access' => 'nullable|array',
			]);

			$requestData = $request->all();

			$obj = new StaffRole;
			$obj->name = $requestData['name'];
			$obj->description = $requestData['description'] ?? null;
			$obj->module_access = $this->encodeModuleAccess($request->input('module_access'));

			$saved = $obj->save();

			if (! $saved) {
				return redirect()->back()->with('error', Config::get('constants.server_error'));
			}

			return redirect()->route('staffrole.index')->with('success', 'Staff Role added Successfully');
		}

		return view('Admin.staffrole.create');
	}

	public function edit(Request $request, $id = null)
	{
		//check authorization start
		$check = $this->checkAuthorizationAction('user_role', $request->route()->getActionMethod(), Auth::user()->role);
		if ($check) {
			return redirect()->route('dashboard')->with('error', config('constants.unauthorized'));
		}
		//check authorization end

		if ($request->isMethod('post')) {
			$this->validate($request, [
				'id' => 'required|integer',
				'name' => 'required|string|max:255',
				'description' => 'nullable|string|max:5000',
				'module_access' => 'nullable|array',
			]);

			$requestData = $request->all();

			$obj = StaffRole::find($requestData['id']);
			if (! $obj) {
				return redirect()->route('staffrole.index')->with('error', 'Staff Role does not exist');
			}

			$obj->name = $requestData['name'];
			$obj->description = $requestData['description'] ?? null;
			// Full form posts only checked boxes; absent/empty = clear permissions (same as UI uncheck-all)
			$obj->module_access = $this->encodeModuleAccess($request->input('module_access'));

			$saved = $obj->save();

			if (! $saved) {
				return redirect()->back()->with('error', Config::get('constants.server_error'));
			}

			return redirect()->route('staffrole.index')->with('success', 'Staff Role Edited Successfully');
		}

		if (isset($id) && ! empty($id)) {
			$id = $this->decodeString($id);
			if (StaffRole::where('id', '=', $id)->exists()) {
				$fetchedData = StaffRole::find($id);

				return view('Admin.staffrole.edit', compact(['fetchedData']));
			}

			return redirect()->route('staffrole.index')->with('error', 'Staff Role does not exist');
		}

		return redirect()->route('staffrole.index')->with('error', Config::get('constants.unauthorized'));
	}

	/**
	 * Roles UI stores module_access as object keys (e.g. {"3":"on"}). Empty → [].
	 *
	 * @param  mixed  $moduleAccess
	 */
	private function encodeModuleAccess($moduleAccess): string
	{
		if (! is_array($moduleAccess)) {
			return json_encode([]);
		}

		return json_encode($moduleAccess);
	}
}
