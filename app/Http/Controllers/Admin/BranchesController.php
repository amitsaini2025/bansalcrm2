<?php

namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Redirect;



use App\Models\Admin;

use App\Models\Branch;



use Auth;

use Config;



class BranchesController extends Controller

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

	 * All branches (list/manage UI).

	 * Roles UI module 1 = create/edit offices (Admin Console Branches menu).

	 *

	 * @return \Illuminate\Http\Response

	 */

	public function index(Request $request)

	{

		$denied = $this->redirectUnlessCanManageBranches();

		if ($denied) {

			return $denied;

		}



		$query = Branch::query();



		$totalData = $query->count();



		$lists = $query->sortable(['id' => 'desc'])->paginate(config('constants.limit'));



		return view('Admin.branch.index', compact(['lists', 'totalData']));

	}



	public function create(Request $request)

	{

		$denied = $this->redirectUnlessCanManageBranches();

		if ($denied) {

			return $denied;

		}



		return view('Admin.branch.create');

	}



	public function store(Request $request)

	{

		$denied = $this->redirectUnlessCanManageBranches();

		if ($denied) {

			return $denied;

		}



		if ($request->isMethod('post')) {

			$this->validate($request, [

				'office_name' => 'required|max:255',

				'country' => 'required|max:255',

				'email' => 'required|max:255',

			]);



			$requestData = $request->all();



			$obj = new Branch;

			$obj->office_name = @$requestData['office_name'];

			$obj->address = @$requestData['address'];

			$obj->city = @$requestData['city'];

			$obj->state = @$requestData['state'];

			$obj->zip = @$requestData['zip'];

			$obj->country = @$requestData['country'];

			$obj->email = @$requestData['email'];

			$obj->phone = @$requestData['phone'];

			$obj->mobile = @$requestData['mobile'];

			$obj->contact_person = @$requestData['contact_person'];

			$obj->choose_admin = @$requestData['choose_admin'];



			$saved = $obj->save();



			if (! $saved) {

				return redirect()->back()->with('error', Config::get('constants.server_error'));

			}



			return redirect()->route('branch.index')->with('success', 'Branch Added Successfully');

		}



		return view('Admin.branch.create');

	}



	public function edit(Request $request, $id = null)

	{

		$denied = $this->redirectUnlessCanManageBranches();

		if ($denied) {

			return $denied;

		}



		if ($request->isMethod('post')) {

			$requestData = $request->all();



			$this->validate($request, [

				'id' => 'required|integer',

				'office_name' => 'required|max:255',

				'country' => 'required|max:255',

				'email' => 'required|max:255',

			]);



			// Null-safe: bad id never writes on null (S-5)

			$obj = Branch::find($requestData['id']);

			if (! $obj) {

				return redirect()->route('branch.index')->with('error', 'Branch Not Exist');

			}



			$obj->office_name = @$requestData['office_name'];

			$obj->address = @$requestData['address'];

			$obj->city = @$requestData['city'];

			$obj->state = @$requestData['state'];

			$obj->zip = @$requestData['zip'];

			$obj->country = @$requestData['country'];

			$obj->email = @$requestData['email'];

			$obj->phone = @$requestData['phone'];

			$obj->mobile = @$requestData['mobile'];

			$obj->contact_person = @$requestData['contact_person'];

			$obj->choose_admin = @$requestData['choose_admin'];



			$saved = $obj->save();



			if (! $saved) {

				return redirect()->back()->with('error', Config::get('constants.server_error'));

			}



			return redirect()->route('branch.index')->with('success', 'Branch Edited Successfully');

		}



		if (isset($id) && ! empty($id)) {

			$id = $this->decodeString($id);

			if (Branch::where('id', '=', $id)->exists()) {

				$fetchedData = Branch::find($id);



				return view('Admin.branch.edit', compact(['fetchedData']));

			}



			return redirect()->route('branch.index')->with('error', 'Branch Not Exist');

		}



		return redirect()->route('branch.index')->with('error', Config::get('constants.unauthorized'));

	}



	/**

	 * Office detail — intentionally auth:admin only (deep links from staff, clients, office visits).

	 * Do not require module 1/2 here or those links break for everyday staff.

	 */

	public function view(Request $request, $id = null)

	{

		if (isset($id) && ! empty($id)) {

			if (Branch::where('id', '=', $id)->exists()) {

				$fetchedData = Branch::find($id);



				return view('Admin.branch.view', compact(['fetchedData']));

			}



			return redirect()->route('dashboard')->with('error', 'Branch Not Exist');

		}



		return redirect()->route('dashboard')->with('error', Config::get('constants.unauthorized'));

	}



	/**

	 * Branch client list — same as view: auth:admin only for deep-link compatibility.

	 */

	public function viewclient(Request $request, $id = null)

	{

		if (isset($id) && ! empty($id)) {

			if (Branch::where('id', '=', $id)->exists()) {

				$fetchedData = Branch::find($id);



				return view('Admin.branch.viewclient', compact(['fetchedData']));

			}



			return redirect()->route('dashboard')->with('error', 'Branch Not Exist');

		}



		return redirect()->route('dashboard')->with('error', Config::get('constants.unauthorized'));

	}



	/**

	 * Roles UI module 1 (create/edit offices) or role 1. Matches Admin Console Branches menu.

	 *

	 * @return \Illuminate\Http\RedirectResponse|null

	 */

	private function redirectUnlessCanManageBranches()

	{

		if ($this->checkAuthorizationAction('1', null, Auth::user()->role)) {

			return redirect()->route('dashboard')->with('error', config('constants.unauthorized'));

		}



		return null;

	}

}


