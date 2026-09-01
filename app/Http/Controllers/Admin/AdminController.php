<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\ActivitiesLog;
use App\Models\Admin;
use App\Models\Agent;
use App\Models\Application;
use App\Models\ApplicationActivitiesLog;
use App\Models\ApplicationReminder;
use App\Models\CheckinLog;
use App\Models\ClientAccessGrant;
// NOTE: TaxRate model/table has been removed
// use App\Models\TaxRate;
use App\Models\Country;
use App\Models\CrmEmailTemplate;
use App\Models\Document;
use App\Models\Email;
use App\Models\EmailLabel;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Note;
use App\Models\Notification;
use App\Models\Partner;
use App\Models\PartnerBranch;
use App\Models\Product;
use App\Models\Staff;
use App\Models\State;
use App\Models\UploadChecklist;
use App\Models\Workflow;
use App\Services\CrmAccess\CrmAccessService;
use App\Services\CrmSentEmailS3Service;
use App\Services\DashboardService;
use App\Services\EmailService;
use App\Services\StaffWorkloadService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $emailService;

    protected $dashboardService;

    protected $crmSentEmailS3Service;

    protected $staffWorkloadService;

    public function __construct(
        EmailService $emailService,
        DashboardService $dashboardService,
        CrmSentEmailS3Service $crmSentEmailS3Service,
        StaffWorkloadService $staffWorkloadService,
    ) {
        $this->middleware('auth:admin');
        $this->emailService = $emailService;
        $this->dashboardService = $dashboardService;
        $this->crmSentEmailS3Service = $crmSentEmailS3Service;
        $this->staffWorkloadService = $staffWorkloadService;
    }

    /**
     * Show the application dashboard.
     *
     * @return Response
     */
    public function dashboard()
    {
        $followupCalendarTabs = [];
        $followupCalendarDefault = FollowupController::DASHBOARD_DEFAULT_CONSULTANT;
        try {
            $followupCalendarTabs = FollowupController::dashboardCalendarTabs();
        } catch (\Throwable $e) {
            Log::warning('Dashboard followup calendar tabs failed: '.$e->getMessage());
        }

        $myDaySummary = $this->loadMyDaySummary();

        try {
            // Get dashboard data using service (always show today's actions)
            $todayTasks = $this->dashboardService->getTodayTasks('today');
            $checkInQueue = $this->dashboardService->getCheckInQueue();
            $clientsWithRecentActivities = $this->dashboardService->getClientsWithRecentActivities(10);
            $loginStats = $this->dashboardService->getLoginStatistics();
            $recentActivities = $this->dashboardService->getRecentActivities(10);

            $accessApprovals = $this->accessApprovalsPanelData();

            return view('Admin.dashboard', compact([
                'todayTasks',
                'checkInQueue',
                'clientsWithRecentActivities',
                'loginStats',
                'recentActivities',
                'accessApprovals',
                'followupCalendarTabs',
                'followupCalendarDefault',
                'myDaySummary',
            ]));
        } catch (\Exception $e) {
            Log::error('Dashboard error: '.$e->getMessage());
            Log::error('Dashboard error trace: '.$e->getTraceAsString());

            // Return view with empty data on error
            return view('Admin.dashboard', [
                'todayTasks' => collect([]),
                'checkInQueue' => ['total' => 0, 'items' => collect([])],
                'clientsWithRecentActivities' => collect([]),
                'loginStats' => $this->dashboardService->getLoginStatistics(),
                'recentActivities' => collect([]),
                'accessApprovals' => null,
                'followupCalendarTabs' => $followupCalendarTabs,
                'followupCalendarDefault' => $followupCalendarDefault,
                'myDaySummary' => $myDaySummary,
            ])->with('error', 'An error occurred while loading the dashboard. Some data may not be available.');
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function loadMyDaySummary(): ?array
    {
        $user = Auth::guard('admin')->user();
        if (! $user instanceof Staff) {
            return null;
        }

        try {
            return $this->staffWorkloadService->getDaySummary((int) $user->id);
        } catch (\Throwable $e) {
            Log::warning('Dashboard My Day summary failed: '.$e->getMessage(), ['staff_id' => $user->id]);

            return null;
        }
    }

    /**
     * Pending supervisor access requests for dashboard (approvers only).
     *
     * @return array{count: int, preview: Collection}|null
     */
    protected function accessApprovalsPanelData(): ?array
    {
        $user = Auth::guard('admin')->user();
        if (! $user instanceof Staff) {
            return null;
        }
        if (! app(CrmAccessService::class)->isApprover($user)) {
            return null;
        }

        $pendingForOthers = ClientAccessGrant::query()
            ->where('status', 'pending')
            ->where('grant_type', 'supervisor_approved')
            ->where('staff_id', '!=', (int) $user->id);

        return [
            'count' => (clone $pendingForOthers)->count(),
            'preview' => (clone $pendingForOthers)
                ->with(['staff', 'admin'])
                ->orderByDesc('requested_at')
                ->limit(5)
                ->get(),
        ];
    }

    public function fetchnotification(Request $request)
    {
        // $notificalists = \App\Models\Notification::where('receiver_id', Auth::user()->id)->where('receiver_status', 0)->orderby('created_at','DESC')->paginate(5);
        // Match header badge: only unseen (receiver_status = 0)
        $notificalistscount = Notification::where('receiver_id', Auth::user()->id)
            ->where('receiver_status', 0)
            ->count();
        /*$output = '';
        foreach($notificalists as $listnoti){
           $output .= '<a href="'.$listnoti->url.'?t='.$listnoti->id.'" class="dropdown-item dropdown-item-unread">
                       <span class="dropdown-item-icon bg-primary text-white">
                           <?php echo \App\Helpers\IconHelper::render('code'); ?>
                       </span>
                       <span class="dropdown-item-desc">'.$listnoti->message.' <span class="time">'.date('d/m/Y h:i A',strtotime($listnoti->created_at)).'</span></span>
                   </a>';
        }*/

        $data = [
            // 'notification' => $output,
            'unseen_notification' => $notificalistscount,
        ];
        echo json_encode($data);
    }

    public function fetchmessages(Request $request)
    {
        // N-2: return payload only — do not mark seen here (toast may fail client-side)
        $notification = Notification::where('receiver_id', Auth::user()->id)
            ->where('seen', 0)
            ->orderBy('id')
            ->first();

        if (! $notification) {
            return response()->json(['id' => null, 'message' => null]);
        }

        return response()->json([
            'id' => $notification->id,
            'message' => $notification->message,
        ]);
    }

    /**
     * Mark toast delivery flag (seen=1) after client successfully shows the toast.
     * Does not change receiver_status (bell unread).
     */
    public function markToastMessageSeen(Request $request)
    {
        $id = (int) $request->input('id');
        if ($id <= 0) {
            return response()->json(['success' => false, 'message' => 'Invalid id'], 422);
        }

        $notification = Notification::where('id', $id)
            ->where('receiver_id', Auth::user()->id)
            ->first();

        if (! $notification) {
            return response()->json(['success' => false, 'message' => 'Notification not found'], 404);
        }

        $notification->seen = 1;
        $notification->save();

        return response()->json(['success' => true]);
    }

    public function fetchInPersonWaitingCount(Request $request)
    {
        $InPersonwaitingCount = CheckinLog::waitingCountForUser(Auth::user());
        $data = ['InPersonwaitingCount' => $InPersonwaitingCount];
        echo json_encode($data);
    }

    public function fetchTotalActivityCount(Request $request)
    {
        if (Auth::user()->role == 1) {
            $assigneesCount = Note::where('type', 'client')->whereNotNull('client_id')->where('is_action', 1)->where('status', 0)->count();
        } else {
            $assigneesCount = Note::where('assigned_to', Auth::user()->id)->where('type', 'client')->where('is_action', 1)->where('status', 0)->count();
        }
        $data = ['assigneesCount' => $assigneesCount];
        echo json_encode($data);
    }

    /**
     * My Profile.
     *
     * @return Response
     */
    public function returnsetting(Request $request)
    {
        if ($request->isMethod('post')) {
            $requestData = $request->all();
            $obj = Staff::find(Auth::user()->id);
            $saved = $obj->save();

            if (! $saved) {
                return redirect()->back()->with('error', Config::get('constants.server_error'));
            } else {
                return redirect()->route('returnsetting')->with('success', 'Your Profile has been edited successfully.');
            }
        } else {
            // return view('Admin.my_profile', compact(['fetchedData', 'countries']));
            return view('Admin.settings.returnsetting');
        }
    }

    // NOTE: Tax rate methods have been removed (taxrates, taxratescreate, edittaxrates, savetaxrate)
    // These methods were related to the tax_rates table which has been dropped
    public function myProfile(Request $request)
    {
        $countries = [];

        if ($request->isMethod('post')) {
            $requestData = $request->all();

            $this->validate($request, [
                'first_name' => 'required',
                'last_name' => 'nullable',
                'email' => 'required|email|unique:staff,email,'.Auth::user()->id,
                'phone' => 'required',
            ]);

            $obj = Staff::find(Auth::user()->id);
            $obj->first_name = @$requestData['first_name'];
            $obj->last_name = @$requestData['last_name'];
            $obj->email = @$requestData['email'];
            $obj->phone = @$requestData['phone'];
            $obj->country_code = @$requestData['country_code'];

            $saved = $obj->save();

            if (! $saved) {
                return redirect()->back()->with('error', Config::get('constants.server_error'));
            }

            return redirect()->route('my_profile')->with('success', 'Your Profile has been edited successfully.');
        }

        $fetchedData = Staff::find(Auth::user()->id);

        return view('Admin.my_profile', compact(['fetchedData', 'countries']));
    }

    /**
     * Change password and Logout automatiaclly.
     *
     * @return Response
     */
    public function change_password(Request $request)
    {
        // check authorization start
        /* $check = $this->checkAuthorizationAction('Admin', $request->route()->getActionMethod(), Auth::user()->role);
        if($check)
        {
            return redirect()->route('dashboard')->with('error',config('constants.unauthorized'));
        } */
        // check authorization end

        if ($request->isMethod('post')) {
            $this->validate($request, [
                'old_password' => 'required|min:6',
                'password' => 'required|confirmed|min:6',
                'password_confirmation' => 'required|min:6',
            ]);

            $requestData = $request->all();
            $admin_id = Auth::user()->id;

            $fetchedData = Staff::where('id', '=', $admin_id)->first();
            if (! empty($fetchedData)) {
                if ($admin_id == trim($requestData['admin_id'])) {
                    if (! (Hash::check($request->get('old_password'), Auth::user()->password))) {
                        return redirect()->back()->with('error', 'Your current password does not matches with the password you provided. Please try again.');
                    } else {
                        $staff = Staff::find($requestData['admin_id']);
                        $staff->password = Hash::make($requestData['password']);
                        if ($staff->save()) {
                            Auth::guard('admin')->logout();
                            $request->session()->flush();

                            return redirect('/admin')->with('success', 'Your Password has been changed successfully.');
                        } else {
                            return redirect()->back()->with('error', Config::get('constants.server_error'));
                        }
                    }
                } else {
                    return redirect()->back()->with('error', 'You can change the password only your account.');
                }
            } else {
                return redirect()->back()->with('error', 'Staff member does not exist, so you cannot change the password.');
            }
        }

        return view('Admin.change_password');
    }

    public function editapi(Request $request)
    {
        $check = $this->checkAuthorizationAction('api_key', $request->route()->getActionMethod(), Auth::user()->role);
        if ($check) {
            return redirect()->route('dashboard')->with('error', config('constants.unauthorized'));
        }

        $staffId = Auth::user()->id;
        $storagePath = storage_path('app/staff_api_keys.json');

        if ($request->isMethod('post')) {
            $keys = [];
            if (file_exists($storagePath)) {
                $keys = json_decode(file_get_contents($storagePath), true) ?: [];
            }
            $keys[$staffId] = md5($staffId.time());
            file_put_contents($storagePath, json_encode($keys));

            return redirect()->route('edit_api')->with('success', 'Api Key'.Config::get('constants.edited'));
        }

        $apiKey = '';
        if (file_exists($storagePath)) {
            $keys = json_decode(file_get_contents($storagePath), true) ?: [];
            $apiKey = $keys[$staffId] ?? '';
        }

        return view('Admin.apikey', compact('apiKey'));
    }

    public function updateAction(Request $request)
    {
        $status = 0;
        $method = $request->method();
        if ($request->isMethod('post')) {
            $requestData = $request->all();

            $requestData['id'] = trim($requestData['id']);
            $requestData['current_status'] = trim($requestData['current_status']);
            $requestData['table'] = trim($requestData['table']);
            $requestData['col'] = trim($requestData['colname']);

            $role = Auth::user()->role;
            if ($role == 1 || $role == 7) {
                if (isset($requestData['id']) && ! empty($requestData['id']) && isset($requestData['current_status']) && isset($requestData['table']) && ! empty($requestData['table'])) {
                    $tableExist = Schema::hasTable(trim($requestData['table']));

                    if ($tableExist) {
                        $recordExist = DB::table($requestData['table'])->where('id', $requestData['id'])->exists();

                        if ($recordExist) {
                            if ($requestData['current_status'] == 0) {
                                $updated_status = 1;
                                $message = 'Record has been enabled successfully.';
                            } else {
                                $updated_status = 0;
                                $message = 'Record has been disabled successfully.';
                            }
                            $response = DB::table($requestData['table'])->where('id', $requestData['id'])->update([$requestData['col'] => $updated_status]);
                            if ($response) {
                                $status = 1;
                            } else {
                                $message = Config::get('constants.server_error');
                            }
                        } else {
                            $message = 'ID does not exist, please check it once again.';
                        }
                    } else {
                        $message = 'Table does not exist, please check it once again.';
                    }
                } else {
                    $message = 'Id OR Current Status OR Table does not exist, please check it once again.';
                }
            } else {
                $message = 'You are not authorized person to perform this action.';
            }
        } else {
            $message = Config::get('constants.post_method');
        }
        echo json_encode(['status' => $status, 'message' => $message]);
        exit;

    }

    public function moveAction(Request $request)
    {
        $status = 0;
        $method = $request->method();
        if ($request->isMethod('post')) {
            $requestData = $request->all();

            $requestData['id'] = trim($requestData['id']);

            $requestData['table'] = trim($requestData['table']);
            $requestData['col'] = trim($requestData['col']);

            if (isset($requestData['id']) && ! empty($requestData['id']) && isset($requestData['table']) && ! empty($requestData['table'])) {
                $tableExist = Schema::hasTable(trim($requestData['table']));

                if ($tableExist) {
                    $recordExist = DB::table($requestData['table'])->where('id', $requestData['id'])->exists();

                    if ($recordExist) {
                        // When un-archiving clients, also clear archive metadata for consistency
                        if ($requestData['table'] == 'admins' && $requestData['col'] == 'is_archived') {
                            $response = DB::table($requestData['table'])->where('id', $requestData['id'])->update([
                                'is_archived' => 0,
                                'archived_on' => null,
                                'archived_by' => null,
                            ]);
                        } else {
                            // For other tables/columns, keep existing behavior
                            $response = DB::table($requestData['table'])->where('id', $requestData['id'])->update([$requestData['col'] => 0]);
                        }

                        if ($response) {
                            $status = 1;
                            $message = 'Record successfully moved';
                        } else {
                            $message = Config::get('constants.server_error');
                        }
                    } else {
                        $message = 'ID does not exist, please check it once again.';
                    }
                } else {
                    $message = 'Table does not exist, please check it once again.';
                }
            } else {
                $message = 'Id OR Current Status OR Table does not exist, please check it once again.';
            }

        } else {
            $message = Config::get('constants.post_method');
        }
        echo json_encode(['status' => $status, 'message' => $message]);
        exit;
    }

    public function declinedAction(Request $request)
    {
        $status = 0;
        $method = $request->method();
        if ($request->isMethod('post')) {
            $requestData = $request->all();

            $requestData['id'] = trim($requestData['id']);

            $requestData['table'] = trim($requestData['table']);

            $role = Auth::user()->role;
            if ($role == 1 || $role == 7) {
                if (isset($requestData['id']) && ! empty($requestData['id']) && isset($requestData['table']) && ! empty($requestData['table'])) {
                    $tableExist = Schema::hasTable(trim($requestData['table']));

                    if ($tableExist) {
                        $recordExist = DB::table($requestData['table'])->where('id', $requestData['id'])->exists();

                        if ($recordExist) {

                            $updated_status = 2;
                            $message = 'Record has been disabled successfully.';

                            $response = DB::table($requestData['table'])->where('id', $requestData['id'])->update(['status' => $updated_status]);
                            if ($response) {
                                $status = 1;
                            } else {
                                $message = Config::get('constants.server_error');
                            }
                        } else {
                            $message = 'ID does not exist, please check it once again.';
                        }
                    } else {
                        $message = 'Table does not exist, please check it once again.';
                    }
                } else {
                    $message = 'Id OR Current Status OR Table does not exist, please check it once again.';
                }
            } else {
                $message = 'You are not authorized person to perform this action.';
            }
        } else {
            $message = Config::get('constants.post_method');
        }
        echo json_encode(['status' => $status, 'message' => $message]);
        exit;
    }

    public function approveAction(Request $request)
    {
        $status = 0;
        $method = $request->method();
        if ($request->isMethod('post')) {
            $requestData = $request->all();

            $requestData['id'] = trim($requestData['id']);

            $requestData['table'] = trim($requestData['table']);

            $role = Auth::user()->role;
            if ($role == 1 || $role == 7) {
                if (isset($requestData['id']) && ! empty($requestData['id']) && isset($requestData['table']) && ! empty($requestData['table'])) {
                    $tableExist = Schema::hasTable(trim($requestData['table']));

                    if ($tableExist) {
                        $recordExist = DB::table($requestData['table'])->where('id', $requestData['id'])->exists();

                        if ($recordExist) {

                            $updated_status = 1;
                            $message = 'Record has been approved successfully.';

                            $response = DB::table($requestData['table'])->where('id', $requestData['id'])->update(['status' => $updated_status]);
                            if ($response) {
                                $status = 1;
                            } else {
                                $message = Config::get('constants.server_error').'sss';
                            }
                        } else {
                            $message = 'ID does not exist, please check it once again.';
                        }
                    } else {
                        $message = 'Table does not exist, please check it once again.';
                    }
                } else {
                    $message = 'Id OR Current Status OR Table does not exist, please check it once again.';
                }
            } else {
                $message = 'You are not authorized person to perform this action.';
            }
        } else {
            $message = Config::get('constants.post_method');
        }
        echo json_encode(['status' => $status, 'message' => $message]);
        exit;
    }

    public function processAction(Request $request)
    {
        $status = 0;
        $method = $request->method();
        if ($request->isMethod('post')) {
            $requestData = $request->all();

            $requestData['id'] = trim($requestData['id']);

            $requestData['table'] = trim($requestData['table']);

            $role = Auth::user()->role;
            if ($role == 1 || $role == 7) {
                if (isset($requestData['id']) && ! empty($requestData['id']) && isset($requestData['table']) && ! empty($requestData['table'])) {
                    $tableExist = Schema::hasTable(trim($requestData['table']));

                    if ($tableExist) {
                        $recordExist = DB::table($requestData['table'])->where('id', $requestData['id'])->exists();

                        if ($recordExist) {

                            $updated_status = 4;
                            $message = 'Record has been processed successfully.';

                            $response = DB::table($requestData['table'])->where('id', $requestData['id'])->update(['status' => $updated_status]);
                            if ($response) {
                                $status = 1;
                            } else {
                                $message = Config::get('constants.server_error').'sss';
                            }
                        } else {
                            $message = 'ID does not exist, please check it once again.';
                        }
                    } else {
                        $message = 'Table does not exist, please check it once again.';
                    }
                } else {
                    $message = 'Id OR Current Status OR Table does not exist, please check it once again.';
                }
            } else {
                $message = 'You are not authorized person to perform this action.';
            }
        } else {
            $message = Config::get('constants.post_method');
        }
        echo json_encode(['status' => $status, 'message' => $message]);
        exit;
    }

    public function archiveAction(Request $request)
    {
        $status = 0;
        $method = $request->method();
        if ($request->isMethod('post')) {
            $requestData = $request->all();

            $requestData['id'] = trim($requestData['id']);

            $requestData['table'] = trim($requestData['table']);

            $astatus = '';
            $role = Auth::user()->role;
            if ($role == 1 || $role == 7) {
                if (isset($requestData['id']) && ! empty($requestData['id']) && isset($requestData['table']) && ! empty($requestData['table'])) {
                    $tableExist = Schema::hasTable(trim($requestData['table']));

                    if ($tableExist) {
                        $recordExist = DB::table($requestData['table'])->where('id', $requestData['id'])->exists();

                        if ($recordExist) {
                            $updated_status = 1;
                            $message = 'Record has been archived successfully.';

                            // Handle admins table (clients/leads) separately - use correct column names and metadata
                            if ($requestData['table'] == 'admins') {
                                // Archive clients/leads with proper metadata (same as deleteAction)
                                $updateData = [
                                    'is_archived' => 1,
                                    'archived_on' => date('Y-m-d'),
                                    'archived_by' => Auth::user()->id,
                                ];
                                $response = DB::table($requestData['table'])->where('id', $requestData['id'])->update($updateData);

                                if ($response) {
                                    $status = 1;
                                } else {
                                    $message = Config::get('constants.server_error');
                                }
                            } else {
                                // For other tables (quotations, etc.) - use existing logic with 'is_archive' column
                                $response = DB::table($requestData['table'])->where('id', $requestData['id'])->update(['is_archive' => $updated_status]);
                                $getarchive = DB::table($requestData['table'])->where('id', $requestData['id'])->first();
                                if ($getarchive->status == 0) {
                                    $astatus = '<span title="draft" class="ui label uppercase">Draft</span><span> (Archived)</span>';
                                } elseif ($getarchive->status == 1) {
                                    $astatus = '<span title="draft" class="ui label uppercase yellow">Sent</span><span> (Archived)</span>';
                                } elseif ($getarchive->status == 2) {
                                    $astatus = '<span title="draft" class="ui label uppercase text-danger">Declined</span><span> (Archived)</span>';
                                }
                                if ($response) {
                                    $status = 1;
                                } else {
                                    $message = Config::get('constants.server_error');
                                }
                            }
                        } else {
                            $message = 'ID does not exist, please check it once again.';
                        }
                    } else {
                        $message = 'Table does not exist, please check it once again.';
                    }
                } else {
                    $message = 'Id OR Current Status OR Table does not exist, please check it once again.';
                }
            } else {
                $message = 'You are not authorized person to perform this action.';
            }
        } else {
            $message = Config::get('constants.post_method');
        }
        echo json_encode(['status' => $status, 'message' => $message, 'astatus' => $astatus]);
        exit;
    }

    public function permanentDeleteAction(Request $request)
    {
        $status = 0;
        $message = '';

        if ($request->isMethod('post')) {
            $requestData = $request->all();
            $requestData['id'] = trim($requestData['id']);
            $requestData['table'] = trim($requestData['table']);

            $role = Auth::user()->role;

            // Only admin (role 1) can permanently delete
            if ($role == 1) {
                if (isset($requestData['id']) && ! empty($requestData['id']) && isset($requestData['table']) && ! empty($requestData['table'])) {
                    $tableExist = Schema::hasTable(trim($requestData['table']));

                    if ($tableExist) {
                        // Additional safety check for admins table (clients)
                        if ($requestData['table'] == 'admins') {
                            $client = Admin::where('id', $requestData['id'])->first();

                            if ($client) {
                                // Verify client is archived
                                if ($client->is_archived != 1) {
                                    $message = 'Only archived clients can be permanently deleted.';
                                }
                                // Verify archived for at least 6 months
                                elseif ($client->archived_on) {
                                    $archivedDate = Carbon::parse($client->archived_on);
                                    $sixMonthsAgo = Carbon::now()->subMonths(6);

                                    if ($archivedDate->lte($sixMonthsAgo)) {
                                        // Safe to delete - archived for 6+ months
                                        // Set is_deleted timestamp instead of actual deletion for audit trail
                                        $response = DB::table($requestData['table'])
                                            ->where('id', $requestData['id'])
                                            ->update(['is_deleted' => date('Y-m-d H:i:s')]);

                                        if ($response) {
                                            $status = 1;
                                            $message = 'Client has been permanently deleted successfully.';
                                        } else {
                                            $message = Config::get('constants.server_error');
                                        }
                                    } else {
                                        $daysArchived = Carbon::now()->diffInDays($archivedDate);
                                        $daysRemaining = 180 - $daysArchived;
                                        $message = 'Client must be archived for at least 6 months before permanent deletion. '.$daysRemaining.' days remaining.';
                                    }
                                } else {
                                    $message = 'Client must be archived before permanent deletion.';
                                }
                            } else {
                                $message = 'Client not found.';
                            }
                        } else {
                            $message = 'Permanent deletion is only allowed for clients.';
                        }
                    } else {
                        $message = 'Table does not exist.';
                    }
                } else {
                    $message = 'ID or Table parameter is missing.';
                }
            } else {
                $message = 'You are not authorized to perform this action. Only administrators can permanently delete records.';
            }
        } else {
            $message = Config::get('constants.post_method');
        }

        echo json_encode(['status' => $status, 'message' => $message]);
        exit;
    }

    public function deleteAction(Request $request)
    {
        $status = 0;
        $method = $request->method();
        if ($request->isMethod('post')) {
            $requestData = $request->all();

            $requestData['id'] = trim($requestData['id']);
            $requestData['table'] = trim($requestData['table']);

            $role = Auth::user()->role;

            if (isset($requestData['id']) && ! empty($requestData['id']) && isset($requestData['table']) && ! empty($requestData['table'])) {
                $tableExist = Schema::hasTable(trim($requestData['table']));

                if ($tableExist) {
                    $recordExist = DB::table($requestData['table'])->where('id', $requestData['id'])->exists();

                    if ($recordExist) {
                        if ($requestData['table'] == 'admins') {
                            /* if($requestData['current_status'] == 0)
                            {
                                $updated_status = 1;
                                $message = 'Record has been enabled successfully.';
                            }
                            else
                            {
                                $updated_status = 0;
                                $message = 'Record has been disabled successfully.';
                            }	 */
                            $o = Admin::where('id', $requestData['id'])->first();
                            if ($o->is_archived == 1) {
                                $is_archived = 0;
                                $updateData = ['is_archived' => $is_archived, 'archived_on' => null, 'archived_by' => null];
                            } else {
                                $is_archived = 1;
                                $updateData = ['is_archived' => $is_archived, 'archived_on' => date('Y-m-d'), 'archived_by' => Auth::user()->id];
                            }
                            $response = DB::table($requestData['table'])->where('id', $requestData['id'])->update($updateData);
                            if ($response) {
                                $status = 1;
                                $message = 'Record has been enabled successfully.';
                            } else {
                                $message = Config::get('constants.server_error');
                            }
                        } elseif ($requestData['table'] == 'currencies') {
                            $isexist = $recordExist = DB::table($requestData['table'])->where('id', $requestData['id'])->exists();
                            if ($isexist) {
                                $response = DB::table($requestData['table'])->where('id', @$requestData['id'])->delete();

                                if ($response) {
                                    $status = 1;
                                    $message = 'Record has been deleted successfully.';
                                } else {
                                    $message = Config::get('constants.server_error');
                                }
                            } else {
                                $message = 'ID does not exist, please check it once again.';
                            }
                        } elseif // NOTE: invoice_schedules table deletion handler removed - Invoice Schedule feature has been removed
                        ($requestData['table'] == 'agents') {
                            $response = DB::table($requestData['table'])->where('id', @$requestData['id'])->update(['is_acrchived' => 1]);

                            if ($response) {
                                $status = 1;
                                $message = 'Record has been Archived successfully.';
                            } else {
                                $message = Config::get('constants.server_error');
                            }
                        } elseif ($requestData['table'] == 'products') {
                            $applicationisexist = DB::table('applications')->where('product_id', $requestData['id'])->exists();

                            if ($applicationisexist) {
                                $message = "Can't Delete its have relation with other records";
                            } else {
                                $isexist = $recordExist = DB::table($requestData['table'])->where('id', $requestData['id'])->exists();
                                if ($isexist) {
                                    $response = DB::table($requestData['table'])->where('id', @$requestData['id'])->delete();
                                    // NOTE: template_infos table has been removed

                                    if ($response) {
                                        $status = 1;
                                        $message = 'Record has been deleted successfully.';
                                    } else {
                                        $message = Config::get('constants.server_error');
                                    }
                                } else {
                                    $message = 'ID does not exist, please check it once again.';
                                }
                            }

                        } elseif ($requestData['table'] == 'partners') {
                            $applicationisexist = DB::table('applications')->where('partner_id', $requestData['id'])->exists();
                            $productsexist = DB::table('products')->where('partner', $requestData['id'])->exists();

                            if ($applicationisexist) {
                                $message = "Can't Delete its have relation with other records";
                            } elseif ($productsexist) {
                                $message = "Can't Delete its have relation with other records";
                            } else {
                                $isexist = $recordExist = DB::table($requestData['table'])->where('id', $requestData['id'])->exists();
                                if ($isexist) {
                                    $response = DB::table($requestData['table'])->where('id', @$requestData['id'])->delete();
                                    // NOTE: template_infos table has been removed

                                    if ($response) {
                                        $status = 1;
                                        $message = 'Record has been deleted successfully.';
                                    } else {
                                        $message = Config::get('constants.server_error');
                                    }
                                } else {
                                    $message = 'ID does not exist, please check it once again.';
                                }
                            }

                        } elseif ($requestData['table'] == 'upload_checklists') {
                            // Delete DB row; remove local file if present (missing file must not block delete)
                            $row = DB::table('upload_checklists')->where('id', $requestData['id'])->first();
                            if ($row) {
                                if (! empty($row->file)) {
                                    $filePath = public_path('checklists/'.$row->file);
                                    if (is_file($filePath)) {
                                        @unlink($filePath);
                                    }
                                }
                                $response = DB::table('upload_checklists')->where('id', $requestData['id'])->delete();
                                if ($response) {
                                    $status = 1;
                                    $message = 'Record has been deleted successfully.';
                                } else {
                                    $message = Config::get('constants.server_error');
                                }
                            } else {
                                $message = 'ID does not exist, please check it once again.';
                            }
                        } else {

                            // save and send to activity log
                            if ($requestData['table'] == 'applications') {
                                $application_data = Application::select('id', 'client_id', 'partner_id', 'product_id')->where('id', $requestData['id'])->first();
                                if ($application_data) {
                                    $productdetail = Product::select('name')->where('id', $application_data->product_id)->first();
                                    $partnerdetail = Partner::select('partner_name')->where('id', $application_data->partner_id)->first();
                                    $subject = 'removed application';

                                    $description = 'removed '.$productdetail->name;
                                    $description_other = '<small>'.$partnerdetail->partner_name.'</small>';

                                    $objs = new ActivitiesLog;
                                    $objs->client_id = $application_data->client_id;
                                    $objs->created_by = Auth::user()->id;
                                    $objs->description = '<p>'.$description.' '.$description_other.'</p>';
                                    $objs->subject = $subject;
                                    $objs->task_status = 0; // Required NOT NULL field (0 = activity, 1 = task)
                                    $objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
                                    $objs->save();
                                }
                            }

                            $response = DB::table($requestData['table'])->where('id', @$requestData['id'])->delete();
                            if ($response) {
                                $status = 1;
                                $message = 'Record has been deleted successfully.';
                            } else {
                                $message = Config::get('constants.server_error');
                            }
                        }
                    } else {
                        $message = 'ID does not exist, please check it once again.';
                    }
                } else {
                    $message = 'Table does not exist, please check it once again.';
                }
            } else {
                $message = 'Id OR Table does not exist, please check it once again.';
            }

        } else {
            $message = Config::get('constants.post_method');
        }
        echo json_encode(['status' => $status, 'message' => $message]);
        exit;
    }

    public function deleteSlotAction(Request $request)
    {
        $status = 0;
        $method = $request->method();
        if ($request->isMethod('post')) {
            $requestData = $request->all();
            $requestData['id'] = trim($requestData['id']);
            $requestData['table'] = trim($requestData['table']);
            // echo  $requestData['id'].'==='.$requestData['table'];dd('###');
            $role = Auth::user()->role;
            if (isset($requestData['id']) && ! empty($requestData['id']) && isset($requestData['table']) && ! empty($requestData['table'])) {
                // Appointment/book_service functionality removed - table deleted
                if ($requestData['table'] == 'book_service_disable_slots') {
                    $message = 'This functionality has been removed. The book_service_disable_slots table no longer exists.';
                    $status = 0;
                } else {
                    $tableExist = Schema::hasTable(trim($requestData['table']));
                    if ($tableExist) {
                        // Handle other tables if needed
                    } else {
                        $message = 'Table does not exist, please check it once again.';
                    }
                }
            } else {
                $message = 'Id OR Table does not exist, please check it once again.';
            }
        } else {
            $message = Config::get('constants.post_method');
        }
        echo json_encode(['status' => $status, 'message' => $message]);
        exit;
    }

    public function getStates(Request $request)
    {
        $status = 0;
        $data = [];
        $method = $request->method();

        if ($request->isMethod('post')) {
            $requestData = $request->all();

            $requestData['id'] = trim($requestData['id']);

            if (isset($requestData['id']) && ! empty($requestData['id'])) {
                $recordExist = Country::where('id', $requestData['id'])->exists();

                if ($recordExist) {
                    $data = State::where('country_id', '=', $requestData['id'])->get();

                    if ($data) {
                        $status = 1;
                        $message = 'Record has been fetched successfully.';
                    } else {
                        $message = Config::get('constants.server_error');
                    }
                } else {
                    $message = 'ID does not exist, please check it once again.';
                }
            } else {
                $message = 'ID does not exist, please check it once again.';
            }
        } else {
            $message = Config::get('constants.post_method');
        }
        echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
        exit;
    }

    // Removed: getChapters() - McqSubject and McqChapter models/tables don't exist (dead code)

    public function sessions(Request $request)
    {
        return view('Admin.sessions');
    }

    public function getpartner(Request $request)
    {
        $catid = $request->cat_id;
        $lists = Partner::where('service_workflow', $catid)->where('status', 0)->orderby('partner_name', 'ASC')->get();
        ob_start();
        ?>
		<option value="">Select a Partner</option>
		<?php
        foreach ($lists as $list) {
            ?>
			<option value="<?php echo $list->id; ?>"><?php echo $list->partner_name; ?></option>
			<?php
        }
        echo ob_get_clean();
    }

    public function getpartnerbranch(Request $request)
    {
        $catid = $request->cat_id;
        $lists = Partner::where('service_workflow', $catid)->where('status', 0)->orderby('partner_name', 'ASC')->get();
        ob_start();
        ?>
		<option value="">Select Partner & Branch</option>
		<?php
        foreach ($lists as $list) {
            $listsbranchs = PartnerBranch::where('partner_id', $list->id)->get();
            foreach ($listsbranchs as $listsbranch) {
                ?>
			<option value="<?php echo $listsbranch->id; ?>_<?php echo $list->id; ?>"><?php echo $list->partner_name.' ('.$listsbranch->name.')'; ?></option>
			<?php
            }
        }
        echo ob_get_clean();
    }

    public function getbranchproduct(Request $request)
    {
        $catid = $request->cat_id;
        $lists = Product::whereRaw('? = ANY(string_to_array(branches, \',\'))', [$catid])->orderby('name', 'ASC')->get();
        ob_start();
        ?>
		<option value="">Select Product</option>
		<?php
        foreach ($lists as $list) {

            ?>
			<option value="<?php echo $list->id; ?>"><?php echo $list->name; ?></option>
			<?php

        }
        echo ob_get_clean();
    }

    public function getproduct(Request $request)
    {
        $catid = $request->cat_id;
        $lists = Product::where('partner', $catid)->orderby('name', 'ASC')->get();
        ob_start();
        ?>
		<option value="">Select a Product</option>
		<?php
        foreach ($lists as $list) {
            ?>
			<option value="<?php echo $list->id; ?>"><?php echo $list->name; ?></option>
			<?php
        }
        echo ob_get_clean();
    }

    public function gettemplates(Request $request)
    {
        $id = $request->id;

        // Validate and sanitize the ID parameter - PostgreSQL requires valid integers
        if (empty($id) || $id === '' || $id === null) {
            echo json_encode(['subject' => '', 'description' => '']);

            return;
        }

        // Ensure ID is a valid integer
        if (! is_numeric($id)) {
            echo json_encode(['subject' => '', 'description' => '']);

            return;
        }

        $CrmEmailTemplate = CrmEmailTemplate::where('id', (int) $id)->first();
        if ($CrmEmailTemplate) {
            echo json_encode(['subject' => $CrmEmailTemplate->subject, 'description' => $CrmEmailTemplate->description]);
        } else {
            echo json_encode(['subject' => '', 'description' => '']);
        }
    }

    public function sendmail(Request $request)
    {
        $requestData = $request->all();
        // echo '<pre>'; print_r($requestData); die;

        // Validate required fields
        if (! isset($requestData['email_from']) || empty($requestData['email_from'])) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => 'Please select a From email address']);
            }

            return redirect()->back()->with('error', 'Please select a From email address')->withInput();
        }

        if (! isset($requestData['email_to']) || empty($requestData['email_to'])) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => 'Please select at least one recipient']);
            }

            return redirect()->back()->with('error', 'Please select at least one recipient')->withInput();
        }

        if (! isset($requestData['subject']) || empty($requestData['subject'])) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => 'Please enter email subject']);
            }

            return redirect()->back()->with('error', 'Please enter email subject')->withInput();
        }

        if (! isset($requestData['message']) || empty($requestData['message'])) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => 'Please enter email message']);
            }

            return redirect()->back()->with('error', 'Please enter email message')->withInput();
        }

        $user_id = @Auth::user()->id;
        $reciept_id = null; // Initialize as NULL for PostgreSQL integer column compatibility
        $array = [];
        // For S3 / Email.client_id when compose omits client_id (invoice/receipt flows)
        $invoiceRelatedClientId = null;

        if (isset($requestData['receipt'])) {
            $fetchedData = InvoicePayment::where('id', '=', $requestData['receipt'])->first();
            $reciept_id = $fetchedData->id;
            $pdf = PDF::setOptions([
                'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true,
                'logOutputFile' => storage_path('logs/log.htm'),
                'tempDir' => storage_path('logs/'),
            ])->loadView('emails.reciept', compact('fetchedData'));
            $output = $pdf->output();
            $invoicefilename = 'receipt_'.$reciept_id.'.pdf';

            // Get client_id from invoice relationship for S3 path structure
            $invoice = $fetchedData->invoice;
            $client_id = $invoice ? $invoice->client_id : 'general';
            if ($invoice && ! empty($invoice->client_id) && is_numeric($invoice->client_id)) {
                $invoiceRelatedClientId = (int) $invoice->client_id;
            }
            $client_info = Admin::select('client_id')->where('id', $client_id)->first();
            $client_unique_id = $client_info ? $client_info->client_id : 'general';

            // Upload to S3
            $filePath = $client_unique_id.'/invoices/receipts/'.$invoicefilename;
            Storage::disk('s3')->put($filePath, $output);

            // Download to temp location for email attachment
            $tempPath = sys_get_temp_dir().'/'.$invoicefilename;
            file_put_contents($tempPath, $output);

            $array['file'] = $tempPath;
            $array['file_name'] = $invoicefilename;
            $array['s3_path'] = $filePath; // Store S3 path for potential cleanup if needed
        }

        if (isset($requestData['invreceipt'])) {
            $invoicedetail = Invoice::where('id', '=', $requestData['invreceipt'])->first();
            if ($invoicedetail->type == 3) {
                $workflowdaa = Workflow::where('id', $invoicedetail->application_id)->first();
                $applicationdata = [];
                $partnerdata = [];
                $productdata = [];
                $branchdata = [];
            } else {
                $applicationdata = Application::where('id', $invoicedetail->application_id)->first();
                $partnerdata = Partner::where('id', @$applicationdata->partner_id)->first();
                $productdata = Product::where('id', @$applicationdata->product_id)->first();
                $branchdata = PartnerBranch::where('id', @$applicationdata->branch)->first();
                $workflowdaa = Workflow::where('id', @$applicationdata->workflow)->first();
            }

            $clientdata = Admin::where('id', $invoicedetail->client_id)->first();
            $admindata = Staff::find($invoicedetail->user_id);
            if (! empty($invoicedetail->client_id) && is_numeric($invoicedetail->client_id)) {
                $invoiceRelatedClientId = (int) $invoicedetail->client_id;
            }

            $logoBase64 = Helper::profileLogoBase64(
                Helper::invoiceProfileLogoFilename($invoicedetail)
            );

            $pdf = PDF::setOptions([
                'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false,
                'logOutputFile' => storage_path('logs/log.htm'),
                'tempDir' => storage_path('logs/'),
            ])->loadView('emails.invoice', compact(['applicationdata', 'partnerdata', 'workflowdaa', 'clientdata', 'productdata', 'branchdata', 'invoicedetail', 'admindata', 'logoBase64']));
            $reciept_id = $invoicedetail->id;

            $output = $pdf->output();
            $invoicefilename = 'invoice_'.$reciept_id.'.pdf';

            // Get client unique ID for S3 path structure
            $client_info = Admin::select('client_id')->where('id', $invoicedetail->client_id)->first();
            $client_unique_id = $client_info ? $client_info->client_id : 'general';

            // Upload to S3
            $filePath = $client_unique_id.'/invoices/'.$invoicefilename;
            Storage::disk('s3')->put($filePath, $output);

            // Download to temp location for email attachment
            $tempPath = sys_get_temp_dir().'/'.$invoicefilename;
            file_put_contents($tempPath, $output);

            $array['file'] = $tempPath;
            $array['file_name'] = $invoicefilename;
            $array['s3_path'] = $filePath; // Store S3 path for potential cleanup if needed
        }

        $obj = new Email;
        $obj->user_id = $user_id;
        $obj->from_mail = isset($requestData['email_from']) ? $requestData['email_from'] : '';
        $obj->to_mail = isset($requestData['email_to']) ? $this->resolveRecipientsToEmails($requestData['email_to'], $requestData['type'] ?? 'client') : '';
        if (isset($requestData['email_cc'])) {
            $obj->cc = implode(',', @$requestData['email_cc']);
        }
        // Handle template_id - PostgreSQL integer column cannot accept empty strings, must be NULL or valid integer
        $obj->template_id = (isset($requestData['template']) && $requestData['template'] !== '' && $requestData['template'] !== null)
                                ? (int) $requestData['template']
                                : null;
        $obj->reciept_id = $reciept_id;
        $obj->subject = isset($requestData['subject']) ? $requestData['subject'] : '';
        if (isset($requestData['type'])) {
            $obj->type = @$requestData['type'];
        }
        // Client detail: Sent tab Client/College — explicit compose target wins, else infer from From address
        $entityType = isset($requestData['type']) ? trim((string) $requestData['type']) : '';
        if (in_array(strtolower($entityType), ['client', 'lead'], true)) {
            $explicitCategory = isset($requestData['compose_email_category']) ? strtolower(trim((string) $requestData['compose_email_category'])) : '';
            if ($explicitCategory === 'college' || $explicitCategory === 'client') {
                $obj->email_category = $explicitCategory;
            } else {
                $collegeFromEmails = [
                    'admin2@bansaleducation.com.au',
                    'admin@bansaleducation.com.au',
                    'apply@bansaleducation.com.au',
                    'admission@bansalimmigration.com.au',
                ];
                $fromMailRaw = isset($requestData['email_from']) ? $requestData['email_from'] : '';
                if (is_array($fromMailRaw)) {
                    $fromMailRaw = reset($fromMailRaw);
                }
                $fromMailRaw = trim((string) $fromMailRaw);
                $fromEmail = $fromMailRaw;
                if (preg_match('/<([^>]+)>/', $fromMailRaw, $m)) {
                    $fromEmail = trim($m[1]);
                }
                $fromEmail = strtolower(trim($fromEmail));
                $obj->email_category = in_array($fromEmail, $collegeFromEmails, true) ? 'college' : 'client';
            }
        }
        $obj->message = isset($requestData['message']) ? $requestData['message'] : '';
        // Set mail_type - Required NOT NULL field for PostgreSQL (1 = manually composed/sent email)
        $obj->mail_type = 1;
        // Entity id for Email tab / S3 archival (emails.client_id holds client, partner, or agent pk by type)
        // Never store free-form email addresses here.
        $resolvedEntityId = null;
        if (! empty($requestData['client_id']) && is_numeric($requestData['client_id'])) {
            $resolvedEntityId = (int) $requestData['client_id'];
        } elseif (! empty($requestData['application_id']) && is_numeric($requestData['application_id'])) {
            $appForClient = Application::find((int) $requestData['application_id']);
            if ($appForClient && $appForClient->client_id) {
                $resolvedEntityId = (int) $appForClient->client_id;
            }
        } elseif ($invoiceRelatedClientId !== null) {
            $resolvedEntityId = $invoiceRelatedClientId;
        } elseif (! empty($requestData['email_to']) && is_array($requestData['email_to'])) {
            foreach ($requestData['email_to'] as $toVal) {
                if (is_numeric($toVal)) {
                    $resolvedEntityId = (int) $toVal;
                    break;
                }
            }
        }
        $obj->client_id = $resolvedEntityId;
        // Default type for archival paths when form omits it (client detail / invoice)
        if (empty($obj->type) && $resolvedEntityId !== null) {
            $obj->type = 'client';
        }

        if ($resolvedEntityId === null) {
            Log::warning('Compose sendmail: no entity client_id resolved; S3 archive / Email tab linking may skip', [
                'type' => $requestData['type'] ?? null,
                'has_client_id_field' => ! empty($requestData['client_id']),
                'has_application_id' => ! empty($requestData['application_id']),
                'to_count' => isset($requestData['email_to']) && is_array($requestData['email_to']) ? count($requestData['email_to']) : 0,
            ]);
        }

        $attachments = [];

        if (isset($requestData['checklistfile'])) {
            if (! empty($requestData['checklistfile'])) {
                $checklistfiles = $requestData['checklistfile'];
                $attachments = [];
                foreach ($checklistfiles as $checklistfile) {
                    $filechecklist = UploadChecklist::where('id', $checklistfile)->first();
                    if ($filechecklist && ! empty($filechecklist->file)) {
                        $checkPath = public_path('checklists/'.$filechecklist->file);
                        // Skip missing disk files so metadata/compose never ship broken attachments
                        if (! is_file($checkPath)) {
                            Log::warning('Compose skipped missing upload checklist file', [
                                'upload_checklist_id' => $filechecklist->id,
                                'file' => $filechecklist->file,
                                'path' => $checkPath,
                            ]);

                            continue;
                        }
                        $attachments[] = [
                            'file_name' => $filechecklist->name,
                            'file_url' => $filechecklist->file,
                            'file_size' => (int) filesize($checkPath),
                        ];
                    }
                }
                // $obj->attachments = json_encode($attachments);
            }
        }

        $attachments2 = [];
        if (isset($requestData['checklistfile_document'])) {
            if (! empty($requestData['checklistfile_document'])) {
                $checklistfiles_documents = $requestData['checklistfile_document'];
                $attachments2 = [];
                foreach ($checklistfiles_documents as $checklistfile1) {
                    $filechecklist_doc = Document::with('category')->where('id', $checklistfile1)->first();
                    if ($filechecklist_doc) {
                        // Dual-read by storage shape first, then legacy doc_type path rules
                        $myfileVal = trim((string) ($filechecklist_doc->myfile ?? ''));
                        $isRemoteDoc = (! empty($filechecklist_doc->myfile_key))
                            || preg_match('#^https?://#i', $myfileVal);
                        $useLocalPath = in_array($filechecklist_doc->doc_type, ['education', 'migration'], true)
                            || ($filechecklist_doc->doc_type === 'documents' && $filechecklist_doc->category && in_array($filechecklist_doc->category->name, ['Education', 'Migration'], true));

                        if ($isRemoteDoc) {
                            $docSize = (isset($filechecklist_doc->file_size) && (int) $filechecklist_doc->file_size > 0)
                                ? (int) $filechecklist_doc->file_size
                                : 0;
                            $attachments2[] = [
                                'file_name' => $filechecklist_doc->file_name,
                                'file_url' => $filechecklist_doc->myfile,
                                'file_size' => $docSize,
                            ];
                        } elseif ($useLocalPath) {
                            $docLocal = public_path('img/documents/'.ltrim($myfileVal, '/'));
                            $docSize = (is_file($docLocal)) ? (int) filesize($docLocal) : 0;
                            $attachments2[] = [
                                'file_name' => $filechecklist_doc->file_name,
                                'file_url' => $docLocal,
                                'file_size' => $docSize,
                            ];
                        } else {
                            // Pre-existing documents checklist rows (often S3 basename or full URL in myfile)
                            $docSize = (isset($filechecklist_doc->file_size) && (int) $filechecklist_doc->file_size > 0)
                                ? (int) $filechecklist_doc->file_size
                                : 0;
                            $attachments2[] = [
                                'file_name' => $filechecklist_doc->file_name,
                                'file_url' => $filechecklist_doc->myfile,
                                'file_size' => $docSize,
                            ];
                        }
                    }
                }
                // $obj->attachments = json_encode($attachments);
            }
        }

        $attachments = array_merge($attachments, $attachments2);
        if (! empty($attachments) && count($attachments) > 0) {
            $obj->attachments = json_encode($attachments);
        }

        $saved = $obj->save();

        // Always attach "Sent" label for better records; plus any additional user-selected labels
        if ($saved) {
            $labelIds = [];
            // Permanent Sent label
            $sentLabel = EmailLabel::where('name', 'Sent')->where('type', 'system')->first();
            if ($sentLabel) {
                $labelIds[] = $sentLabel->id;
            }
            // User-selected additional labels
            if (isset($requestData['label_ids']) && is_array($requestData['label_ids']) && ! empty($requestData['label_ids'])) {
                $userLabelIds = array_map('intval', array_filter($requestData['label_ids']));
                $labelIds = array_unique(array_merge($labelIds, $userLabelIds));
            }
            if (! empty($labelIds)) {
                $obj->labels()->attach($labelIds);
            }
        }

        // Activity log based on which button user clicked (send_context)
        $sendContext = $requestData['send_context'] ?? '';
        $isApplicationComposeContext = ($sendContext === 'application_compose');

        // When send_context=checklist (Send checklist button): always log "Checklist Email sent"
        $isChecklistContext = ($sendContext === 'checklist');
        if ($saved && $isChecklistContext) {
            $clientIdForLog = null;
            $wasAlreadySent = false;
            $sentDate = now()->format('d/m/Y');
            if (! empty($requestData['application_id'])) {
                $app = Application::find((int) $requestData['application_id']);
                if ($app && $app->client_id) {
                    $clientIdForLog = $app->client_id;
                    $wasAlreadySent = $app->checklist_sent_at !== null;
                    if (! empty($requestData['checklistfile'])) {
                        $app->checklist_sent_at = now()->toDateString();
                        $app->save();
                    }
                }
            }
            if ($clientIdForLog === null && ! empty($requestData['email_to'][0])) {
                $clientIdForLog = is_numeric($requestData['email_to'][0]) ? (int) $requestData['email_to'][0] : null;
            }
            if ($clientIdForLog) {
                $logSubject = $wasAlreadySent ? 'Checklist Email resent' : 'Checklist Email sent';
                $logDescription = 'Checklist Email sent on '.$sentDate;
                $objs = new ActivitiesLog;
                $objs->client_id = $clientIdForLog;
                $objs->created_by = Auth::user()->id;
                $objs->subject = $logSubject;
                $objs->description = $logDescription;
                $objs->task_status = 0;
                $objs->pin = 0;
                $objs->save();
            }
        }

        // When checklistfile present but no send_context (legacy): update checklist_sent_at and log
        if (isset($requestData['checklistfile']) && ! empty($requestData['checklistfile']) && ! $isChecklistContext) {
            $clientIdForLog = null;
            $logSubject = 'Checklist Email sent';
            $sentDate = now()->format('d/m/Y');
            $logDescription = 'Checklist Email sent on '.$sentDate;

            if (! empty($requestData['application_id'])) {
                $app = Application::find((int) $requestData['application_id']);
                if ($app) {
                    $wasAlreadySent = $app->checklist_sent_at !== null;
                    $app->checklist_sent_at = now()->toDateString();
                    $app->save();
                    $clientIdForLog = $app->client_id;
                    $logSubject = $wasAlreadySent ? 'Checklist Email resent' : 'Checklist Email sent';
                    $logDescription = 'Checklist Email sent on '.$sentDate;
                }
            }
            if ($clientIdForLog === null && ! empty($requestData['email_to'][0])) {
                $clientIdForLog = is_numeric($requestData['email_to'][0]) ? (int) $requestData['email_to'][0] : null;
            }
            if ($clientIdForLog) {
                $objs = new ActivitiesLog;
                $objs->client_id = $clientIdForLog;
                $objs->created_by = Auth::user()->id;
                $objs->subject = $logSubject;
                $objs->description = $logDescription;
                $objs->task_status = 0;
                $objs->pin = 0;
                $objs->save();
            }
        }

        // When send_context=email_reminder (Email reminder button): always log "Email reminder sent" and create ApplicationReminder
        $isEmailReminderContext = ($sendContext === 'email_reminder');
        if ($saved && $isEmailReminderContext && ! empty($requestData['application_id'])) {
            $app = Application::find((int) $requestData['application_id']);
            if ($app && $app->client_id) {
                ApplicationReminder::create([
                    'application_id' => $app->id,
                    'type' => 'email',
                    'reminded_at' => now(),
                    'user_id' => Auth::user()->id,
                ]);
                $emailSentDate = now()->format('d/m/Y');
                $objs = new ActivitiesLog;
                $objs->client_id = $app->client_id;
                $objs->created_by = Auth::user()->id;
                $objs->subject = 'Email reminder sent';
                $objs->description = 'Email reminder sent on '.$emailSentDate;
                $objs->task_status = 0;
                $objs->pin = 0;
                $objs->save();
            }
        }

        // When application_id present, no send_context (legacy): record email reminder and log
        $isChecklistEmail = (! empty($requestData['checklistfile']) || ! empty($requestData['checklistfile_document']));
        if ($saved && ! empty($requestData['application_id']) && ! $isChecklistEmail && ! $isChecklistContext && ! $isEmailReminderContext && ! $isApplicationComposeContext) {
            $app = Application::find((int) $requestData['application_id']);
            if ($app && $app->client_id) {
                ApplicationReminder::create([
                    'application_id' => $app->id,
                    'type' => 'email',
                    'reminded_at' => now(),
                    'user_id' => Auth::user()->id,
                ]);
                $emailSentDate = now()->format('d/m/Y');
                $objs = new ActivitiesLog;
                $objs->client_id = $app->client_id;
                $objs->created_by = Auth::user()->id;
                $objs->subject = 'Email reminder sent';
                $objs->description = 'Email reminder sent on '.$emailSentDate;
                $objs->task_status = 0;
                $objs->pin = 0;
                $objs->save();
            }
        }

        // Plain compose: log "Sent email" when no checklist/reminder activity was created
        $loggedChecklistOrReminder = $isChecklistContext
            || (isset($requestData['checklistfile']) && ! empty($requestData['checklistfile']))
            || $isEmailReminderContext
            || (! empty($requestData['application_id']) && ! $isChecklistEmail && ! $isChecklistContext && ! $isEmailReminderContext && ! $isApplicationComposeContext);
        if ($saved && ! $loggedChecklistOrReminder) {
            $clientIdForLog = is_numeric($obj->client_id) ? (int) $obj->client_id : null;
            if ($clientIdForLog === null && ! empty($requestData['email_to'][0]) && is_numeric($requestData['email_to'][0])) {
                $clientIdForLog = (int) $requestData['email_to'][0];
            }
            if ($clientIdForLog) {
                $sentDate = now()->format('d/m/Y H:i');
                $toDisplay = is_string($obj->to_mail) ? $obj->to_mail : (is_array($obj->to_mail) ? implode(', ', $obj->to_mail) : '');
                $subjectDisplay = $obj->subject ?? $requestData['subject'] ?? '';
                $logDescription = 'Email sent to '.$toDisplay.' - Subject: "'.$subjectDisplay.'" on '.$sentDate;
                $objs = new ActivitiesLog;
                $objs->client_id = $clientIdForLog;
                $objs->created_by = Auth::user()->id;
                $objs->subject = 'Sent email';
                $objs->description = $logDescription;
                $objs->task_status = 0;
                $objs->pin = 0;
                $objs->save();
            }
        }

        if (isset($requestData['checklistfile_document']) && ! $isChecklistContext) {
            if (! empty($requestData['checklistfile_document'])) {
                $clientIdForLog = null;
                if (! empty($requestData['application_id'])) {
                    $app = Application::find((int) $requestData['application_id']);
                    if ($app && $app->client_id) {
                        $clientIdForLog = (int) $app->client_id;
                    }
                }
                if ($clientIdForLog === null && ! empty($requestData['email_to'][0]) && is_numeric($requestData['email_to'][0])) {
                    $clientIdForLog = (int) $requestData['email_to'][0];
                }
                if ($clientIdForLog === null && isset($obj->client_id) && is_numeric($obj->client_id)) {
                    $clientIdForLog = (int) $obj->client_id;
                }
                if ($clientIdForLog !== null) {
                    $objs = new ActivitiesLog;
                    $objs->client_id = $clientIdForLog;
                    $objs->created_by = Auth::user()->id;
                    $objs->subject = 'Document Checklist sent to client';
                    $objs->task_status = 0; // Required NOT NULL field for PostgreSQL (0 = activity, 1 = task)
                    $objs->pin = 0; // Required NOT NULL field for PostgreSQL (0 = not pinned, 1 = pinned)
                    $objs->save();
                }
            }
        }

        // Keep originals so each recipient gets a clean placeholder pass (multi-To)
        $subjectOriginal = $requestData['subject'];
        $messageOriginal = $requestData['message'];
        $s3Stored = false; // Store to S3 only once (not per recipient)

        // Build attachment tuples once before the recipient loop so UploadedFile objects
        // are only moved once and paths are valid for every recipient and archival.
        $attachmentTuples = [];

        // Ensure upload directory exists
        if (! is_dir(storage_path('app/uploads'))) {
            mkdir(storage_path('app/uploads'), 0755, true);
        }

        if ($request->hasFile('attach')) {
            foreach ($request->file('attach') as $file1) {
                $originalName = $file1->getClientOriginalName();
                $filename = time().'_'.$originalName;
                $filePath = storage_path('app/uploads/'.$filename);
                $file1->move(storage_path('app/uploads'), $filename);
                $attachmentTuples[] = ['path' => $filePath, 'name' => $originalName];
            }
        }

        // Include receipt/invoice PDF so it is both sent and archived
        if (isset($array['file']) && file_exists($array['file'])) {
            $attachmentTuples[] = ['path' => $array['file'], 'name' => $array['file_name'] ?? basename($array['file'])];
        }

        // CC list resolved once (emails only; allow free-form @ addresses like To)
        $ccEmails = [];
        if (isset($requestData['email_cc']) && ! empty($requestData['email_cc'])) {
            foreach ($requestData['email_cc'] as $cc) {
                $cc = is_string($cc) ? trim($cc) : $cc;
                if ($cc === '' || $cc === null) {
                    continue;
                }
                if (is_string($cc) && strpos($cc, '@') !== false) {
                    $ccEmails[] = $cc;

                    continue;
                }
                if (! is_numeric($cc)) {
                    continue;
                }
                $clientcc = Admin::where('id', $cc)->first();
                if ($clientcc && ! empty($clientcc->email)) {
                    $ccEmails[] = $clientcc->email;
                }
            }
        }

        // Success must not return inside this loop — otherwise only the first recipient gets mail (E-2).
        // Failures still return immediately (same fail-fast as before). Final success is after the loop.
        foreach ($requestData['email_to'] as $l) {
            // Per-recipient checklist paths (reset each iteration so multi-To does not stack duplicates)
            $array['files'] = [];
            $subject = $subjectOriginal;
            $message = $messageOriginal;
            $l = is_string($l) ? trim($l) : $l;
            $client = null;

            // Free-form email (college compose uses email as Tom Select id) — match resolveRecipientsToEmails
            if (is_string($l) && strpos($l, '@') !== false) {
                $displayName = strstr($l, '@', true);
                if (! is_string($displayName) || $displayName === '') {
                    $displayName = 'Recipient';
                }
                $client = (object) [
                    'email' => $l,
                    'first_name' => $displayName,
                    'partner_name' => $displayName,
                    'full_name' => $displayName,
                    'dob' => null,
                ];
            } elseif (@$requestData['type'] == 'partner') {
                $client = Partner::Where('id', $l)->first();
            } elseif (@$requestData['type'] == 'agent') {
                $client = Agent::Where('id', $l)->first();
            } else {
                $client = Admin::Where('id', $l)->first();
            }

            if (! $client || empty($client->email)) {
                $errMsg = 'Failed to send email: invalid or missing recipient ('.(is_scalar($l) ? (string) $l : 'unknown').').';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['status' => false, 'message' => $errMsg]);
                }

                return redirect()->back()->with('error', $errMsg)->withInput();
            }

            if (@$requestData['type'] == 'partner') {
                $subject = str_replace('{Client First Name}', $client->partner_name ?? $client->first_name ?? '', $subject);
                $message = str_replace('{Client First Name}', $client->partner_name ?? $client->first_name ?? '', $message);
            } elseif (@$requestData['type'] == 'agent') {
                $subject = str_replace('{Client First Name}', $client->full_name ?? $client->first_name ?? '', $subject);
                $message = str_replace('{Client First Name}', $client->full_name ?? $client->first_name ?? '', $message);
            } else {
                $subject = str_replace('{Client First Name}', $client->first_name ?? '', $subject);
                $message = str_replace('{Client First Name}', $client->first_name ?? '', $message);
            }

            $message = str_replace('{Client Assignee Name}', $client->first_name ?? '', $message);
            $message = str_replace('{Company Name}', Helper::defaultCrmCompanyName(), $message);
            $client_dob = '';
            if (isset($client->dob) && $client->dob && $client->dob != '0000-00-00') {
                $client_dob = date('d/m/Y', strtotime($client->dob));
            }
            $subject = str_replace('{DOB}', $client_dob, $subject);
            $message = str_replace('{DOB}', $client_dob, $message);

            if (isset($requestData['checklistfile'])) {
                if (! empty($requestData['checklistfile'])) {
                    $checklistfiles = $requestData['checklistfile'];
                    foreach ($checklistfiles as $checklistfile) {
                        $filechecklist = UploadChecklist::where('id', $checklistfile)->first();
                        if ($filechecklist && ! empty($filechecklist->file)) {
                            $checkPath = public_path('checklists/'.$filechecklist->file);
                            if (is_file($checkPath)) {
                                $array['files'][] = $checkPath;
                            } else {
                                Log::warning('Compose skipped missing upload checklist file on send', [
                                    'upload_checklist_id' => $filechecklist->id,
                                    'file' => $filechecklist->file,
                                    'path' => $checkPath,
                                ]);
                            }
                        }
                    }
                }
            }

            if (isset($requestData['checklistfile_document'])) {
                if (! empty($requestData['checklistfile_document'])) {
                    $checklistfiles_documents = $requestData['checklistfile_document'];
                    foreach ($checklistfiles_documents as $checklistfile1) {
                        $filechecklist_doc = Document::with('category')->where('id', $checklistfile1)->first();
                        if ($filechecklist_doc) {
                            // Dual-read: storage shape first, then legacy doc_type rules
                            $myfileVal = trim((string) ($filechecklist_doc->myfile ?? ''));
                            $isRemoteDoc = (! empty($filechecklist_doc->myfile_key))
                                || preg_match('#^https?://#i', $myfileVal);
                            $useLocalPath = in_array($filechecklist_doc->doc_type, ['education', 'migration'], true)
                                || ($filechecklist_doc->doc_type === 'documents' && $filechecklist_doc->category && in_array($filechecklist_doc->category->name, ['Education', 'Migration'], true));

                            if ($isRemoteDoc) {
                                $fileUrl = $filechecklist_doc->myfile;
                                if (filter_var($fileUrl, FILTER_VALIDATE_URL)) {
                                    $pathPart = parse_url($fileUrl, PHP_URL_PATH);
                                    $tempPath = sys_get_temp_dir().'/'.basename(is_string($pathPart) && $pathPart !== '' ? $pathPart : $fileUrl);
                                    $contents = @file_get_contents($fileUrl);
                                    if ($contents !== false && $contents !== '') {
                                        file_put_contents($tempPath, $contents);
                                        $array['files'][] = $tempPath;
                                    } else {
                                        Log::warning('Compose skipped unreachable client document on send', [
                                            'document_id' => $filechecklist_doc->id,
                                            'file_url' => $fileUrl,
                                        ]);
                                    }
                                } elseif ($fileUrl !== '') {
                                    $array['files'][] = $fileUrl;
                                }
                            } elseif ($useLocalPath && $myfileVal !== '') {
                                $localPath = public_path('img/documents/'.ltrim($myfileVal, '/'));
                                if (is_file($localPath)) {
                                    $array['files'][] = $localPath;
                                } else {
                                    Log::warning('Compose skipped missing local client document on send', [
                                        'document_id' => $filechecklist_doc->id,
                                        'path' => $localPath,
                                    ]);
                                }
                            } elseif ($filechecklist_doc->doc_type == 'documents') {
                                $fileUrl = $filechecklist_doc->myfile; // AWS S3 link (legacy rows)
                                if (filter_var($fileUrl, FILTER_VALIDATE_URL)) {
                                    $tempPath = sys_get_temp_dir().'/'.basename($fileUrl);
                                    file_put_contents($tempPath, file_get_contents($fileUrl));
                                    $array['files'][] = $tempPath;
                                } else {
                                    $array['files'][] = $fileUrl;
                                }
                            }
                        }
                    }
                }
            }

            // $this->send_compose_template($client->email, $subject, $requestData['email_from'], $message, '', $array,@$ccarray);

            try {
                // Merge per-recipient checklist paths into the attachment tuple list
                $recipientTuples = $attachmentTuples;
                if (isset($array['files'])) {
                    foreach ($array['files'] as $p) {
                        if (is_string($p)) {
                            $recipientTuples[] = ['path' => $p, 'name' => basename($p)];
                        }
                    }
                }

                // Flat paths for EmailService (expects plain strings)
                $attachmentPaths = array_column($recipientTuples, 'path');

                $this->emailService->sendEmail(
                    'emails.template',
                    ['content' => $message],
                    $client->email,
                    $subject,
                    $requestData['email_from'],
                    $attachmentPaths,
                    $ccEmails
                );

                // Archive email to S3 (HTML snapshot + attachments) — once per email, not per recipient
                if (! $s3Stored) {
                    try {
                        // Only mark stored when service actually succeeded (needs client_id + S3 config)
                        $s3Stored = $this->crmSentEmailS3Service->storeToS3($obj, $subject, $message, $recipientTuples) === true;
                    } catch (\Exception $s3Ex) {
                        Log::warning('CRM sent email S3 storage failed (email still sent)', [
                            'error' => $s3Ex->getMessage(),
                            'mail_report_id' => $obj->id ?? null,
                            'entity_id' => $obj->client_id ?? null,
                        ]);
                    }
                }
                // Continue to next recipient — response returned after the loop
            } catch (\Exception $e) {
                // Fail-fast on first send error (same as before); some prior recipients may already have received mail
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['status' => false, 'message' => 'Failed to send email: '.$e->getMessage()]);
                }

                return redirect()->back()->with('error', 'Failed to send email: '.$e->getMessage())->withInput();
            }
        }

        // Clean up receipt/invoice temp after all recipients (was inside loop and broke multi-To PDFs)
        if (isset($array['file']) && is_string($array['file']) && file_exists($array['file'])) {
            @unlink($array['file']);
        }
        if (! empty($array['file'])) {
            unset($array['file']);
        }
        if (! $saved) {
            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => false, 'message' => Config::get('constants.server_error')]);
            }

            return redirect()->back()->with('error', Config::get('constants.server_error'));
        } else {
            // Return JSON response for AJAX requests (include email_category so client detail can open College tab when sent from college address)
            if ($request->ajax() || $request->wantsJson()) {
                $json = ['status' => true, 'message' => 'Email Sent Successfully'];
                if (isset($obj->email_category)) {
                    $json['email_category'] = $obj->email_category;
                }

                return response()->json($json);
            }

            return redirect()->back()->with('success', 'Email Sent Successfully');
        }
    }

    /**
     * Resolve recipient IDs (client/partner/agent) to email addresses for Email.to_mail.
     */
    protected function resolveRecipientsToEmails(array $recipients, string $type): string
    {
        $emails = [];
        foreach ($recipients as $r) {
            $r = trim($r);
            if (empty($r)) {
                continue;
            }
            if (strpos($r, '@') !== false) {
                $emails[] = $r;

                continue;
            }
            if (! is_numeric($r)) {
                $emails[] = $r;

                continue;
            }
            $email = null;
            if ($type === 'partner') {
                $p = Partner::find($r);
                $email = ($p && isset($p->email) && $p->email !== '') ? $p->email : null;
            } elseif ($type === 'agent') {
                $a = Agent::find($r);
                $email = ($a && ! empty($a->email)) ? $a->email : null;
            } else {
                $a = Admin::withoutGlobalScopes()->find($r);
                $email = ($a && ! empty($a->email)) ? $a->email : null;
            }
            $emails[] = $email ?: $r;
        }

        return implode(', ', $emails);
    }

    public function getbranch(Request $request)
    {
        $catid = $request->cat_id;
        $pro = Product::where('id', $catid)->first();
        if ($pro) {
            $user_array = explode(',', $pro->branches);
            $lists = PartnerBranch::WhereIn('id', $user_array)->Where('partner_id', $pro->partner)->orderby('name', 'ASC')->get();
            ob_start();
            ?>
		<option value="">Select a Branch</option>
		<?php
            foreach ($lists as $list) {
                ?>
			<option value="<?php echo $list->id; ?>"><?php echo $list->name; ?></option>
			<?php
            }
        } else {
            ?>
			<option value="">Select a Branch</option>
			<?php
        }
        echo ob_get_clean();
    }

    public function getnewPartnerbranch(Request $request)
    {
        $catid = $request->cat_id;
        $lists = PartnerBranch::Where('partner_id', $catid)->orderby('name', 'ASC')->get();

        ob_start();
        ?>
		<option value="">Select a Branch</option>
		<?php
        foreach ($lists as $list) {
            ?>
			<option value="<?php echo $list->id; ?>"><?php echo $list->name.'('.$list->city.')'; ?></option>
			<?php
        }

        echo ob_get_clean();
    }

    // Removed: getsubjects() - subjects table has been dropped

    public function getproductbranch(Request $request)
    {
        $catid = $request->cat_id;
        $sss = Product::where('id', $catid)->first();
        if ($sss) {
            $lists = PartnerBranch::where('id', $sss->branches)->get();
            ob_start();
            ?>
		<option value="">Please select branch</option>
		<?php
            foreach ($lists as $list) {

                ?>
			<option value="<?php echo $list->id; ?>"><?php echo $list->name; ?></option>
			<?php

            }
        } else {
            ?>
			<option value="">Please select branch</option>
			<?php
        }
        echo ob_get_clean();
    }

    public function getpartnerajax(Request $request)
    {
        $fetchedData = Partner::where('partner_name', 'LIKE', '%'.$request->likevalue.'%')->get();
        $agents = [];
        foreach ($fetchedData as $list) {
            $agents[] = [
                'id' => $list->id,
                'agent_id' => $list->partner_name,
                'agent_company_name' => $list->partner_name,
            ];
        }

        echo json_encode($agents);
    }

    // getassigneeajax moved to StaffController::getassigneeajax

    public function allnotification(Request $request)
    {
        $query = Notification::where('receiver_id', Auth::user()->id);

        // Filter by read/unread status
        if ($request->has('filter') && $request->filter != 'all') {
            if ($request->filter == 'unread') {
                $query->where('receiver_status', 0);
            } elseif ($request->filter == 'read') {
                $query->where('receiver_status', 1);
            }
        }

        // Search functionality
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where('message', 'LIKE', '%'.$search.'%');
        }

        $lists = $query->orderby('created_at', 'DESC')->paginate(20)->appends($request->query());

        // Get counts for filter tabs
        $totalCount = Notification::where('receiver_id', Auth::user()->id)->count();
        $unreadCount = Notification::where('receiver_id', Auth::user()->id)->where('receiver_status', 0)->count();
        $readCount = Notification::where('receiver_id', Auth::user()->id)->where('receiver_status', 1)->count();

        return view('Admin.notifications', compact(['lists', 'totalCount', 'unreadCount', 'readCount']));
    }

    public function markNotificationAsRead(Request $request)
    {
        if ($request->has('id') && ! empty($request->id)) {
            $notification = Notification::where('id', $request->id)
                ->where('receiver_id', Auth::user()->id)
                ->first();

            if ($notification) {
                $notification->receiver_status = 1;
                $notification->save();

                return response()->json(['success' => true, 'message' => 'Notification marked as read']);
            }
        }

        return response()->json(['success' => false, 'message' => 'Notification not found']);
    }

    public function markAllNotificationsAsRead(Request $request)
    {
        $updated = Notification::where('receiver_id', Auth::user()->id)
            ->where('receiver_status', 0)
            ->update(['receiver_status' => 1]);

        return response()->json(['success' => true, 'message' => $updated.' notifications marked as read']);
    }

    public function partnerChangeToInactive(Request $request)
    {
        $status = 1;
        $method = $request->method();
        if ($request->isMethod('post')) {
            $requestData = $request->all();
            $requestData['id'] = trim($requestData['id']);
            $requestData['table'] = trim($requestData['table']);

            $astatus = '';
            $role = Auth::user()->role;
            if ($role == 1 || $role == 7 || $role == 12 || $role == 11) { // 11=>account staff team
                if (isset($requestData['id']) && ! empty($requestData['id']) && isset($requestData['table']) && ! empty($requestData['table'])) {
                    $tableExist = Schema::hasTable(trim($requestData['table']));
                    if ($tableExist) {
                        $recordExist = DB::table($requestData['table'])->where('id', $requestData['id'])->exists();
                        if ($recordExist) {
                            $updated_status = 1;
                            $message = 'Record has been inactive successfully.';

                            $response = DB::table($requestData['table'])->where('id', $requestData['id'])->update(['status' => $updated_status]);
                            $getarchive = DB::table($requestData['table'])->where('id', $requestData['id'])->first();
                            if ($getarchive->status == 0) {
                                $astatus = '<span title="draft" class="ui label uppercase">Active</span>';
                            } elseif ($getarchive->status == 1) {
                                $astatus = '<span title="draft" class="ui label uppercase yellow">Inactive</span>';
                            }
                            if ($response) {
                                $status = 1;
                            } else {
                                $message = Config::get('constants.server_error');
                            }
                        } else {
                            $message = 'ID does not exist, please check it once again.';
                        }
                    } else {
                        $message = 'Table does not exist, please check it once again.';
                    }
                } else {
                    $message = 'Id OR Current Status OR Table does not exist, please check it once again.';
                }
            } else {
                $message = 'You are not authorized person to perform this action.';
            }
        } else {
            $message = Config::get('constants.post_method');
        }
        echo json_encode(['status' => $status, 'message' => $message, 'astatus' => $astatus]);
        exit;
    }

    public function partnerChangeToActive(Request $request)
    {
        $status = 0;
        $method = $request->method();
        if ($request->isMethod('post')) {
            $requestData = $request->all();
            $requestData['id'] = trim($requestData['id']);
            $requestData['table'] = trim($requestData['table']);

            $astatus = '';
            $role = Auth::user()->role;
            if ($role == 1 || $role == 7 || $role == 12 || $role == 11) { // 11=>account staff team
                if (isset($requestData['id']) && ! empty($requestData['id']) && isset($requestData['table']) && ! empty($requestData['table'])) {
                    $tableExist = Schema::hasTable(trim($requestData['table']));
                    if ($tableExist) {
                        $recordExist = DB::table($requestData['table'])->where('id', $requestData['id'])->exists();
                        if ($recordExist) {
                            $updated_status = 0;
                            $message = 'Record has been active successfully.';

                            $response = DB::table($requestData['table'])->where('id', $requestData['id'])->update(['status' => $updated_status]);
                            $getarchive = DB::table($requestData['table'])->where('id', $requestData['id'])->first();
                            if ($getarchive->status == 0) {
                                $astatus = '<span title="draft" class="ui label uppercase">Active</span>';
                            } elseif ($getarchive->status == 1) {
                                $astatus = '<span title="draft" class="ui label uppercase yellow">Inactive</span>';
                            }
                            if ($response) {
                                $status = 0;
                            } else {
                                $message = Config::get('constants.server_error');
                            }
                        } else {
                            $message = 'ID does not exist, please check it once again.';
                        }
                    } else {
                        $message = 'Table does not exist, please check it once again.';
                    }
                } else {
                    $message = 'Id OR Current Status OR Table does not exist, please check it once again.';
                }
            } else {
                $message = 'You are not authorized person to perform this action.';
            }
        } else {
            $message = Config::get('constants.post_method');
        }
        echo json_encode(['status' => $status, 'message' => $message, 'astatus' => $astatus]);
        exit;
    }

    // Note deadline task complete
    public function updatenotedeadlinecompleted(Request $request, Note $note)
    {
        $data = $request->all(); // dd($data['id']);
        $note = Note::where('id', $data['id'])->update(['status' => '1']);
        // $note = 1;
        if ($note) {
            $note_data = Note::where('id', $data['id'])->first(); // dd($note_data);
            if ($note_data) {
                $admin_data = Staff::find($note_data['assigned_to']);
                if ($admin_data) {
                    $assignee_name = $admin_data['first_name'].' '.$admin_data['last_name'];
                } else {
                    $assignee_name = 'N/A';
                }
                $objs = new ActivitiesLog;
                $objs->client_id = $note_data['client_id'];
                $objs->created_by = Auth::user()->id;

                // $objs->subject = 'Partner closed action in group '.$note_data['task_group'].' with deadline '.date('d/m/Y',strtotime($note_data['note_deadline'])).' to '.@$assignee_name;
                // $objs->description = '<p>'.@$note_data['description'].'</p>';

                $objs->subject = 'Closed Note Deadline';
                $objs->description = '<span class="text-semi-bold">'.@$note_data['title'].'</span><p>'.@$note_data['description'].'</p>';

                if (Auth::user()->id != @$note_data['assigned_to']) {
                    $objs->use_for = @$note_data['assigned_to'];
                } else {
                    $objs->use_for = null; // Use null instead of empty string for PostgreSQL
                }

                $objs->followup_date = @$note_data['action_assign_date'] ?? @$note_data['updated_at'];
                $objs->task_group = 'partner';
                $objs->task_status = 1; // maked completed
                $objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
                $objs->save();
            }
            $response['status'] = true;
            $response['message'] = 'Note Deadline updated successfully';
        } else {
            $response['status'] = false;
            $response['message'] = 'Please try again';
        }
        echo json_encode($response);
    }

    // Note deadline extend
    public function extenddeadlinedate(Request $request)
    {
        $requestData = $request->all(); // dd($requestData);
        if (Note::where('id', $requestData['note_id'])->count() > 0) {
            $note_data = Note::where('id', $requestData['note_id'])->get();
            // dd($note_data);
            if (! empty($note_data) && count($note_data) > 0) {

                if (isset($requestData['note_deadline']) && $requestData['note_deadline'] != '') {
                    $note_deadlineArr = explode('/', $requestData['note_deadline']);
                    $note_deadlineArrFormated = $note_deadlineArr[2].'-'.$note_deadlineArr[1].'-'.$note_deadlineArr[0];
                } else {
                    $note_deadlineArrFormated = null;
                }

                foreach ($note_data as $note_val) {  // dd($note_val->unique_group_id);
                    $updated = Note::where('id', $note_val->id)
                        ->update([
                            'description' => $requestData['description'],
                            'note_deadline' => $note_deadlineArrFormated,
                            'user_id' => Auth::user()->id,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    if ($updated) {
                        $note_info = Note::where('id', $note_val->id)->first(); // dd($note_info);
                        // Create a notification for the current assignee
                        $o = new Notification;
                        $o->sender_id = Auth::user()->id;
                        $o->receiver_id = $note_info['assigned_to'];
                        $o->module_id = $note_info['client_id'];
                        $o->url = route('partners.detail', @$note_info['client_id']);
                        $o->notification_type = 'client';
                        $o->message = 'Action Assigned by '.Auth::user()->first_name.' '.Auth::user()->last_name.' on '.date('d/M/Y h:i A', strtotime(@$note_info['action_assign_date']));
                        $o->seen = 0; // Set seen to 0 (unseen) for new notifications
                        $o->save();

                        $objs = new ActivitiesLog;
                        $objs->client_id = $note_info['client_id'];
                        $objs->created_by = Auth::user()->id;
                        $objs->subject = 'Extended Note Deadline';

                        // Get assigner name
                        $assignee_info = Staff::select('id', 'first_name', 'last_name')->find($note_info['assigned_to']);
                        if ($assignee_info) {
                            $assignee_name = $assignee_info->first_name;
                        } else {
                            $assignee_name = 'N/A';
                        }

                        $note_info_title = 'Partner assigned action with deadline '.$requestData['note_deadline'].' to '.$assignee_name;

                        $objs->description = '<span class="text-semi-bold">'.@$note_info_title.'</span><p>'.@$note_info['description'].'</p>';

                        if (Auth::user()->id != $note_info['user_id']) {
                            $objs->use_for = $note_info['user_id'];
                        } else {
                            $objs->use_for = null; // Use null instead of empty string for PostgreSQL
                        }
                        $objs->followup_date = $note_info['action_assign_date'];
                        $objs->task_group = 'partner';
                        $objs->task_status = 0; // Required NOT NULL field (0 = activity, 1 = task)
                        $objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
                        $objs->save();
                    }
                }
            }
        }
        echo json_encode(['success' => true, 'message' => 'successfully updated', 'clientID' => $note_info['client_id']]);
        exit;
    }

    /**
     * Complete an action (Note) with a completion message
     *
     * @return JsonResponse
     */
    public function completeAction(Request $request)
    {
        try {
            $request->validate([
                'action_id' => 'required|integer|exists:notes,id',
                'completion_message' => 'required|string|min:1',
            ]);

            $note = Note::find($request->action_id);

            if (! $note) {
                return response()->json([
                    'status' => false,
                    'message' => 'Action not found',
                ], 404);
            }

            // Check if already completed
            if ($note->status == 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'This action is already completed',
                ], 400);
            }

            // Get client_id from request or note
            $clientId = $request->input('client_id') ?: $note->client_id;

            if (! $clientId && ! $note->isPersonalTaskWithoutClient()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Client ID is required',
                ], 400);
            }

            // Update note status to completed
            $note->status = 1;
            $note->save();

            // Create activity log entry when a client is linked
            if ($clientId) {
                $activity = new ActivitiesLog;
                $activity->client_id = $clientId;
                $activity->created_by = Auth::user()->id;
                $activity->subject = 'Completed action';
                $activity->description = '<span class="text-semi-bold">Action Completed</span><p>'.htmlspecialchars($request->completion_message).'</p>';
                $activity->task_status = 0; // Activity, not task
                $activity->pin = 0;
                $activity->save();
            }

            // If this action is related to an application, also log to ApplicationActivitiesLog
            if (! empty($note->application_id)) {
                // Get the application to determine the stage
                $application = Application::find($note->application_id);
                if ($application) {
                    // Get the ORIGINAL note description (entered when assigning the task)
                    $originalNoteDescription = strip_tags($note->description);

                    $obj1 = new ApplicationActivitiesLog;
                    $obj1->stage = $application->stage;
                    $obj1->type = 'task';
                    $obj1->comment = 'completed a task';
                    $obj1->title = 'Action completed by '.Auth::user()->first_name.' '.Auth::user()->last_name;
                    // Show BOTH the original note description AND the completion message
                    $obj1->description = '<span class="text-semi-bold">Action Completed</span><p>'.htmlspecialchars($originalNoteDescription).'</p><hr><p><strong>Completion Note:</strong> '.htmlspecialchars($request->completion_message).'</p>';
                    $obj1->app_id = $note->application_id;
                    $obj1->user_id = Auth::user()->id;
                    $obj1->save();
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Action completed successfully!',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed: '.implode(', ', array_map(function ($errors) {
                    return is_array($errors) ? implode(', ', $errors) : $errors;
                }, $e->errors())),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error completing action: '.$e->getMessage());
            Log::error('Error trace: '.$e->getTraceAsString());

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while completing the action. Please try again.',
            ], 500);
        }
    }
}
