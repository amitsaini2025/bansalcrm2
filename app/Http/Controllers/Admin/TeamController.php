<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

use App\Models\Admin;
use App\Models\Team;

use Auth;
use Config;

class TeamController extends Controller
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
	 * Teams list / manage. Admin Console shows Teams under module 4 (with Staff).
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		$denied = $this->redirectUnlessCanManageTeams();
		if ($denied) {
			return $denied;
		}

		$query = Team::query();

		$totalData = $query->count();

		$lists = $query->sortable(['id' => 'desc'])->paginate(config('constants.limit'));

		return view('Admin.teams.index', compact(['lists', 'totalData']));
	}

	public function edit(Request $request, $id = null)
	{
		$denied = $this->redirectUnlessCanManageTeams();
		if ($denied) {
			return $denied;
		}

		if ($request->isMethod('post')) {
			$this->validate($request, [
				'id' => 'required|integer',
				'name' => 'required|max:255',
			]);

			$requestData = $request->all();

			// Null-safe: bad/missing id never writes on null (S-5)
			$obj = Team::find($requestData['id']);
			if (! $obj) {
				return redirect()->route('adminconsole.teams.index')->with('error', 'Team Not Exist');
			}

			$obj->name = @$requestData['name'];
			$obj->color = @$requestData['color'];
			$saved = $obj->save();

			if (! $saved) {
				return redirect()->back()->with('error', Config::get('constants.server_error'));
			}

			return redirect()->route('adminconsole.teams.index')->with('success', 'Record update Successfully');
		}

		if (isset($id) && ! empty($id)) {
			if (Team::where('id', '=', $id)->exists()) {
				$fetchedData = Team::find($id);
				$query = Team::query();
				$totalData = $query->count();
				$lists = $query->sortable(['id' => 'desc'])->paginate(config('constants.limit'));

				return view('Admin.teams.index', compact(['fetchedData', 'lists', 'totalData']));
			}

			return redirect()->route('adminconsole.teams.index')->with('error', 'Team Not Exist');
		}

		return redirect()->route('adminconsole.teams.index')->with('error', Config::get('constants.unauthorized'));
	}

	public function store(Request $request)
	{
		$denied = $this->redirectUnlessCanManageTeams();
		if ($denied) {
			return $denied;
		}

		if ($request->isMethod('post')) {
			$this->validate($request, [
				'name' => 'required|max:255',
			]);

			$requestData = $request->all();

			$obj = new Team;
			$obj->name = @$requestData['name'];
			$obj->color = @$requestData['color'];
			$saved = $obj->save();

			if (! $saved) {
				return redirect()->back()->with('error', Config::get('constants.server_error'));
			}

			return redirect()->route('adminconsole.teams.index')->with('success', 'Record Added Successfully');
		}
	}

	/**
	 * Roles UI / Admin Console: Teams menu under module 4; role 1 always allowed.
	 *
	 * @return \Illuminate\Http\RedirectResponse|null
	 */
	private function redirectUnlessCanManageTeams()
	{
		if ($this->checkAuthorizationAction('4', null, Auth::user()->role)) {
			return redirect()->route('dashboard')->with('error', config('constants.unauthorized'));
		}

		return null;
	}
}

