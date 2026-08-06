<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

use App\Models\Staff;
use App\Models\FromEmail;
use App\Models\StaffRole;

use Auth;
use Config;
use App\Helpers\PhoneHelper;
use App\Services\CrmAccess\CrmAccessService;

class StaffController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function create(Request $request)
    {
        $check = $this->checkAuthorizationAction('user_management', $request->route()->getActionMethod(), Auth::user()->role);
        if ($check) {
            return redirect()->route('dashboard')->with('error', config('constants.unauthorized'));
        }

        // Staff roles only (exclude role 7 = client)
        $usertype = StaffRole::where('id', '!=', 7)->get();
$emails = FromEmail::where('status', 1)->orderBy('email')->get();
        $canManageCrmAccess = app(CrmAccessService::class)->canManageStaffQuickAccess(Auth::user());
		return view('Admin.staff.create', compact(['usertype', 'emails', 'canManageCrmAccess']));
    }

    public function store(Request $request)
    {
        $check = $this->checkAuthorizationAction('user_management', $request->route()->getActionMethod(), Auth::user()->role);
        if ($check) {
            return redirect()->route('dashboard')->with('error', config('constants.unauthorized'));
        }

        if ($request->isMethod('post')) {
            $requestData = $request->all();
            $this->validate($request, [
                'first_name' => 'required|max:255',
                'last_name' => 'required|max:255',
                'email' => 'required|max:255|unique:staff',
                'password' => 'required|max:255|confirmed',
                'phone' => 'required',
                'role' => 'required',
                'office' => 'nullable|exists:branches,id',
            ]);

            $obj = new Staff;
            $obj->first_name = @$requestData['first_name'];
            $obj->last_name = @$requestData['last_name'];
            $obj->email = @$requestData['email'];
            $obj->country_code = PhoneHelper::normalizeCountryCode(@$requestData['country_code']);
            $obj->position = @$requestData['position'];
            $obj->password = Hash::make(@$requestData['password']);
            $obj->phone = @$requestData['phone'];
            $obj->role = @$requestData['role'];
            $obj->office_id = @$requestData['office'];
            $obj->team = @$requestData['team'];
            $obj->verified = 1;
            $obj->status = 1;
            $obj->email_signature = $request->input('email_signature');
            if (isset($requestData['show_dashboard_per'])) {
                $obj->show_dashboard_per = 1;
            } else {
                $obj->show_dashboard_per = 0;
            }
            if (isset($requestData['permission']) && is_array($requestData['permission'])) {
                $obj->permission = implode(',', $requestData['permission']);
            } else {
                $obj->permission = '';
            }

            if (app(CrmAccessService::class)->canManageStaffQuickAccess(Auth::user())) {
                $obj->quick_access_enabled = $request->boolean('quick_access_enabled');
                $obj->crm_full_access = $request->boolean('crm_full_access');
                $obj->crm_access_approver = $request->boolean('crm_access_approver');
            }

            $saved = $obj->save();

            if (!$saved) {
                return redirect()->back()->with('error', Config::get('constants.server_error'));
            }
            return redirect()->route('staff.active')->with('success', 'Staff added Successfully');
        }

        return view('Admin.staff.create');
    }

    public function edit(Request $request, $id = null)
    {
        $check = $this->checkAuthorizationAction('user_management', $request->route()->getActionMethod(), Auth::user()->role);
        if ($check) {
            return redirect()->route('dashboard')->with('error', config('constants.unauthorized'));
        }

        $usertype = StaffRole::where('id', '!=', 7)->get();
        if ($request->isMethod('post')) {
            $requestData = $request->all();

            $this->validate($request, [
                'first_name' => 'required|max:255',
                'last_name' => 'required|max:255',
                'email' => 'required|email|max:255|unique:staff,email,' . (@$requestData['id'] ?? 0),
                'phone' => 'required|max:255',
                'office' => 'nullable|exists:branches,id',
            ]);

            $obj = Staff::find(@$requestData['id']);
            if (! $obj) {
                return redirect()->route('staff.active')->with('error', 'Staff not found.');
            }
            $obj->first_name = @$requestData['first_name'];
            $obj->last_name = @$requestData['last_name'];
            $obj->email = @$requestData['email'];
            $obj->country_code = PhoneHelper::normalizeCountryCode(@$requestData['country_code']);
            $obj->position = @$requestData['position'];
            $obj->phone = @$requestData['phone'];
            $obj->role = @$requestData['role'];
            $obj->office_id = @$requestData['office'];
            $obj->team = @$requestData['team'];

            if (isset($requestData['permission']) && $requestData['permission'] != '') {
                $obj->permission = implode(',', $requestData['permission']);
            } else {
                $obj->permission = '';
            }

            if (isset($requestData['show_dashboard_per'])) {
                $obj->show_dashboard_per = 1;
            } else {
                $obj->show_dashboard_per = 0;
            }
            $obj->email_signature = $request->input('email_signature');
            if (!empty(@$requestData['password'])) {
                $obj->password = Hash::make(@$requestData['password']);
            }
            $obj->phone = @$requestData['phone'];

            $crmAccess = app(CrmAccessService::class);
            if ($crmAccess->canManageStaffQuickAccess(Auth::user())) {
                $wasQuick = (bool) ($obj->quick_access_enabled ?? false);
                $obj->quick_access_enabled = $request->boolean('quick_access_enabled');
                $obj->crm_full_access = $request->boolean('crm_full_access');
                $obj->crm_access_approver = $request->boolean('crm_access_approver');
                if ($wasQuick && ! $obj->quick_access_enabled) {
                    $crmAccess->revokeGrantsForStaff((int) $obj->id, 'Quick access disabled by admin');
                }
            }

            $saved = $obj->save();

            if (!$saved) {
                return redirect()->back()->with('error', Config::get('constants.server_error'));
            }
            return redirect()->route('staff.view', ['id' => @$requestData['id']])->with('success', 'Staff Edited Successfully');
        }

        if (isset($id) && !empty($id)) {
            $id = $this->decodeString($id);
            if ($id === false || $id === '') {
                return redirect()->route('staff.active')->with('error', 'Invalid Staff ID');
            }
            if (Staff::where('id', '=', $id)->exists()) {
                $fetchedData = Staff::with(['office'])->find($id);
$emails = FromEmail::where('status', 1)->orderBy('email')->get();
                $canManageCrmAccess = app(CrmAccessService::class)->canManageStaffQuickAccess(Auth::user());
				return view('Admin.staff.edit', compact(['fetchedData', 'usertype', 'emails', 'canManageCrmAccess']));
            }
            return redirect()->route('staff.active')->with('error', 'Staff Not Exist');
        }
        return redirect()->route('staff.active')->with('error', Config::get('constants.unauthorized'));
    }

    public function savezone(Request $request)
    {
        if (! $request->isMethod('post')) {
            return redirect()->route('dashboard')->with('error', config('constants.unauthorized'));
        }

        $requestData = $request->all();
        $targetId = (int) ($requestData['user_id'] ?? 0);
        $user = Auth::user();

        // IDOR fix: only self, super-admin (role 1), or module 3 (manage staff)
        $isSelf = $targetId > 0 && (int) $user->id === $targetId;
        $canManage = $this->staffUserCanManageStaff();
        if (! $isSelf && ! $canManage) {
            return redirect()->route('dashboard')->with('error', config('constants.unauthorized'));
        }

        $obj = Staff::find($targetId);
        if (! $obj) {
            return redirect()->back()->with('error', 'Staff not found.');
        }

        $obj->time_zone = @$requestData['timezone'];
        $saved = $obj->save();

        if (! $saved) {
            return redirect()->back()->with('error', Config::get('constants.server_error'));
        }

        return redirect()->route('staff.view', ['id' => $targetId])->with('success', 'Staff Edited Successfully');
    }

    public function view(Request $request, $id)
    {
        if (! isset($id) || $id === '' || $id === null) {
            return redirect()->route('dashboard')->with('error', config('constants.unauthorized'));
        }

        // Own profile: no module 3/4 required (timezone / self-view from deep links)
        $isSelf = (int) Auth::user()->id === (int) $id;
        if (! $isSelf) {
            $denied = $this->redirectUnlessCanViewStaffDirectory();
            if ($denied) {
                return $denied;
            }
        }

        if (Staff::where('id', '=', $id)->exists()) {
            $fetchedData = Staff::with('office')->find($id);
            return view('Admin.staff.view', compact(['fetchedData']));
        }

        return redirect()->route('staff.active')->with('error', 'Staff Not Exist');
    }

    public function active(Request $request)
    {
        $denied = $this->redirectUnlessCanViewStaffDirectory();
        if ($denied) {
            return $denied;
        }

        $search_by = trim((string) $request->input('search_by', ''));

        if ($search_by !== '') {
            $query = Staff::where('status', '=', 1);
            $this->applyStaffListSearch($query, $search_by);
            $query->with(['usertype', 'office']);
        } else {
            $query = Staff::where('status', '=', 1)->with(['usertype', 'office']);
        }

        $totalData = $query->count();
        $lists = $query->orderby('first_name', 'ASC')->paginate(config('constants.limit'));
        $viewType = 'active';
        return view('Admin.staff.index', compact(['lists', 'totalData', 'viewType']));
    }

    public function inactive(Request $request)
    {
        $denied = $this->redirectUnlessCanViewStaffDirectory();
        if ($denied) {
            return $denied;
        }

        $search_by = trim((string) $request->input('search_by', ''));

        if ($search_by !== '') {
            $query = Staff::where('status', '=', 0);
            $this->applyStaffListSearch($query, $search_by);
            $query->with(['usertype', 'office']);
        } else {
            $query = Staff::where('status', '=', 0)->with(['usertype', 'office']);
        }

        $totalData = $query->count();
        $lists = $query->orderby('first_name', 'ASC')->paginate(config('constants.limit'));
        $viewType = 'inactive';
        return view('Admin.staff.index', compact(['lists', 'totalData', 'viewType']));
    }

    /**
     * Free-text filter for staff listings (aligned with getassigneeajax). Uses ILIKE for PostgreSQL.
     */
    private function applyStaffListSearch($query, string $searchTerm): void
    {
        $like = '%' . $searchTerm . '%';
        $query->where(function ($q) use ($like) {
            $q->where('first_name', 'ILIKE', $like)
                ->orWhere('last_name', 'ILIKE', $like)
                ->orWhere('email', 'ILIKE', $like)
                ->orWhere('phone', 'ILIKE', $like)
                ->orWhere(DB::raw("COALESCE(first_name, '') || ' ' || COALESCE(last_name, '')"), 'ILIKE', $like);
        });
    }

    /**
     * Roles UI module 3 (manage staff) or 4 (view staff list/details), or role 1.
     * checkAuthorizationAction: truthy = deny.
     *
     * @return \Illuminate\Http\RedirectResponse|null
     */
    private function redirectUnlessCanViewStaffDirectory()
    {
        $role = Auth::user()->role;
        if ($this->checkAuthorizationAction('3', null, $role)
            && $this->checkAuthorizationAction('4', null, $role)) {
            return redirect()->route('dashboard')->with('error', config('constants.unauthorized'));
        }

        return null;
    }

    /**
     * Roles UI module 3 (invite/edit staff) or role 1.
     */
    private function staffUserCanManageStaff(): bool
    {
        return ! $this->checkAuthorizationAction('3', null, Auth::user()->role);
    }

    /**
     * AJAX: Get staff for assignee dropdown (search by name, email, phone).
     * Intentionally auth:admin only (no module 3/4): used by Actions, Applications, etc.
     */
    public function getassigneeajax(Request $request)
    {
        $squery = $request->likevalue ?? '';
        $fetchedData = Staff::where(function ($query) use ($squery) {
            return $query
                ->where('email', 'ILIKE', '%' . $squery . '%')
                ->orWhere('first_name', 'ILIKE', '%' . $squery . '%')
                ->orWhere('last_name', 'ILIKE', '%' . $squery . '%')
                ->orWhere('phone', 'ILIKE', '%' . $squery . '%')
                ->orWhere(DB::raw("COALESCE(first_name, '') || ' ' || COALESCE(last_name, '')"), 'ILIKE', '%' . $squery . '%');
        })->get();

        $agents = [];
        foreach ($fetchedData as $list) {
            $agents[] = [
                'id' => $list->id,
                'agent_id' => $list->first_name . ' ' . $list->last_name,
                'assignee' => $list->first_name . ' ' . $list->last_name,
            ];
        }
        echo json_encode($agents);
    }

    /**
     * Get assignee list for Action module dropdown.
     * Intentionally auth:admin only (no module 3/4): used by Action module assign UI.
     * Returns HTML option strings (callers use .html(obj.message)); labels must be escaped.
     */
    public function getAssigneeList(Request $request)
    {
        $assignedto = $request->assignedto ?? null;
        $content1 = [];
        foreach (Staff::with('office')->where('status', 1)->orderby('first_name', 'ASC')->get() as $staff) {
            $staffId = (int) $staff->id;
            $officeName = $staff->office ? (string) $staff->office->office_name : '';
            $label = trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')) . ' (' . $officeName . ')';
            // Escape label text only — keep value as integer so Action popovers still bind staff id
            $optionLabel = e($label);
            $selected = ((string) $staffId === (string) $assignedto) ? ' selected' : '';
            $content1[] = '<option value="' . $staffId . '"' . $selected . '>' . $optionLabel . '</option>';
        }
        $response['status'] = true;
        $response['message'] = $content1;
        echo json_encode($response);
    }
}
