<?php

namespace App\Http\Controllers\Admin\Client;

use App\Http\Controllers\Controller;
use App\Models\ActivitiesLog;
use App\Models\Admin;
use App\Services\Sms\UnifiedSmsManager;
use App\Support\ClientDetailActivities;
use App\Support\ClientDetailEagerLoads;
use App\Traits\ClientAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Client activity log operations
 *
 * Methods moved from ClientsController:
 * - activities
 * - deleteactivitylog
 * - pinactivitylog
 * - notpickedcall
 */
class ClientActivityController extends Controller
{
    use ClientAuthorization;

    protected $smsManager;

    public function __construct(UnifiedSmsManager $smsManager)
    {
        $this->middleware('auth:admin');
        $this->smsManager = $smsManager;
    }

    /**
     * Resolve admin/client and enforce view/edit (allocation + grants).
     */
    private function resolveAccessibleActivityClient($clientId, bool $forEdit = false): ?Admin
    {
        if ($clientId === null || $clientId === '' || ! is_numeric($clientId)) {
            return null;
        }

        $client = Admin::find((int) $clientId);
        if (! $client) {
            return null;
        }

        $allowed = $forEdit ? $this->canEditClient($client) : $this->canViewClient($client);

        return $allowed ? $client : null;
    }

    private function unauthorizedJson(array $extra = []): void
    {
        echo json_encode(array_merge([
            'status' => false,
            'message' => 'Unauthorized',
        ], $extra));
    }

    /**
     * Not picked call button click - send SMS via UnifiedSmsManager
     */
    public function notpickedcall(Request $request)
    {
        $data = $request->all();
        if (! $this->resolveAccessibleActivityClient($data['id'] ?? null, true)) {
            $this->unauthorizedJson([
                'not_picked_call' => $data['not_picked_call'] ?? null,
            ]);

            return;
        }

        $userInfo = Admin::select('id', 'country_code', 'phone')->where('id', $data['id'])->first();
        $smsSent = false;
        if ($userInfo && ! empty($data['message'])) {
            $userPhone = trim(($userInfo->country_code ?? '').''.($userInfo->phone ?? ''));
            if ($userPhone) {
                $result = $this->smsManager->sendSms($userPhone, $data['message'], 'notification', ['client_id' => $data['id']]);
                $smsSent = ! empty($result['success']);
            }
        }
        $recExist = Admin::where('id', $data['id'])->update(['not_picked_call' => $data['not_picked_call']]);
        if ($recExist) {
            if ($data['not_picked_call'] == 1) {
                $response['status'] = true;
                $response['message'] = $smsSent ? 'Call not picked. SMS sent successfully!' : 'Call not picked. SMS failed to send.';
                $response['not_picked_call'] = $data['not_picked_call'];
            } else {
                $response['status'] = true;
                $response['message'] = 'You have updated call not picked bit. Please try again';
                $response['not_picked_call'] = $data['not_picked_call'];
            }
        } else {
            $response['status'] = false;
            $response['message'] = 'Please try again';
            $response['not_picked_call'] = $data['not_picked_call'];
        }
        echo json_encode($response);
    }

    /**
     * Delete activity log
     */
    public function deleteactivitylog(Request $request)
    {
        $activitylogid = $request->activitylogid;
        if (ActivitiesLog::where('id', $activitylogid)->exists()) {
            $data = ActivitiesLog::select('client_id', 'subject', 'description')->where('id', $activitylogid)->first();
            if (! $this->resolveAccessibleActivityClient($data->client_id ?? null, true)) {
                $this->unauthorizedJson();

                return;
            }
            $res = DB::table('activities_logs')->where('id', @$activitylogid)->delete();
            if ($res) {
                $response['status'] = true;
                $response['data'] = $data;
            } else {
                $response['status'] = false;
                $response['message'] = 'Please try again';
            }
        } else {
            $response['status'] = false;
            $response['message'] = 'Please try again';
        }
        echo json_encode($response);
    }

    /**
     * Pin activity log
     */
    public function pinactivitylog(Request $request)
    {
        $requestData = $request->all();
        if (ActivitiesLog::where('id', $requestData['activity_id'] ?? null)->exists()) {
            $activity = ActivitiesLog::where('id', $requestData['activity_id'])->first();
            if (! $this->resolveAccessibleActivityClient($activity->client_id ?? null, true)) {
                $this->unauthorizedJson();

                return;
            }
            if ($activity->pin == 0) {
                $obj = ActivitiesLog::find($activity->id);
                $obj->pin = 1;
                $obj->save();
            } else {
                $obj = ActivitiesLog::find($activity->id);
                $obj->pin = 0;
                $obj->save();
            }
            $response['status'] = true;
            $response['message'] = 'Pin Option added successfully';
        } else {
            $response['status'] = false;
            $response['message'] = 'Record not found';
        }
        echo json_encode($response);
    }

    /**
     * Get activity log for a client
     */
    public function activities(Request $request)
    {
        if (Admin::where('id', $request->id)->exists()) {
            if (! $this->resolveAccessibleActivityClient($request->id, false)) {
                $this->unauthorizedJson();

                return;
            }

            $clientId = (int) $request->id;
            $filters = ClientDetailActivities::filtersFromRequest($request);
            $paginate = $request->boolean('paginated');
            $page = max(1, $request->integer('page', 1));
            $hasMore = false;
            $nextPage = $page + 1;

            if ($paginate) {
                $paginator = ClientDetailActivities::paginate($clientId, $filters, $page);
                $activities = $paginator->getCollection();
                $hasMore = $paginator->hasMorePages();
                $nextPage = $paginator->currentPage() + 1;
            } else {
                $activities = ActivitiesLog::where('client_id', $clientId)->orderby('created_at', 'DESC')->get();
            }

            $actorMap = ClientDetailEagerLoads::staffThenAdminByIds($activities->pluck('created_by'));
            $data = [];
            foreach ($activities as $activit) {
                $admin = is_numeric($activit->created_by) ? $actorMap->get((int) $activit->created_by) : null;
                if (! $admin) {
                    continue;
                }

                $data[] = [
                    'activity_id' => $activit->id,
                    'subject' => $activit->subject,
                    'createdname' => substr($admin->first_name, 0, 1),
                    'name' => $admin->first_name,
                    'message' => $activit->description,
                    'date' => date('d M Y, H:i A', strtotime($activit->created_at)),
                    'followup_date' => $activit->followup_date,
                    'task_group' => $activit->task_group,
                    'pin' => $activit->pin,
                ];
            }

            $response['status'] = true;
            $response['data'] = $data;
            $response['hasMore'] = $hasMore;
            $response['nextPage'] = $nextPage;
            $response['page'] = $page;
            $response['html'] = view('Admin.partials.activities-list', [
                'activities' => $activities,
                'staffMap' => $actorMap,
                'adminMap' => collect(),
            ])->render();
        } else {
            $response['status'] = false;
            $response['message'] = 'Please try again';
        }
        echo json_encode($response);
    }
}
