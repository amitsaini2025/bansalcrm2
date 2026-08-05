<?php

namespace App\Http\Controllers\Admin\Client;

use App\Http\Controllers\Admin\FollowupController;
use App\Http\Controllers\Controller;
use App\Models\ActivitiesLog;
use App\Models\Admin;
use App\Models\FollowupConsultant;
use App\Support\FollowupAvailability;
use App\Traits\ClientHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Client action/task management
 *
 * Manages actions, tasks, and actions for clients.
 * Used primarily in the Action module (/action).
 *
 * Methods:
 * - actionstore - Create new action/task
 * - actionstore_application - Create application stage task
 * - reassignactionstore - Reassign existing task
 * - updateaction - Update task details
 * - retagaction - Retag/reassign task
 * - personalaction - Create personal task
 */
class ClientActionController extends Controller
{
    use ClientHelpers;

    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    // Assign action and save
    public function actionstore(Request $request)
    {
        $requestData = $request->all();
        // echo '<pre>'; print_r($requestData); die;
        /*if(\App\Models\Note::where('client_id',$requestData['client_id'])->where('assigned_to',$requestData['rem_cat'])->exists())
        {
            // return redirect()->back()->with('error', 'Lead already assigned');
            // return Redirect::to('/admin/assignee')->with('error', 'Lead already assigned');
            echo json_encode(array('success' => false, 'message' => 'Lead already assigned to '.@$requestData['assignee_name'], 'clientID' => $requestData['client_id']));
            exit;
        }*/

        $action = new \App\Models\Note;
        $action->client_id = $this->decodeString(@$requestData['client_id']);
        $action->user_id = Auth::user()->id;
        $action->description = @$requestData['description'];
        $action->title = @$requestData['remindersubject'] ?? 'Lead assign to '.@$requestData['assignee_name'];
        $action->is_action = 1;
        $action->task_group = @$requestData['task_group'];
        $action->assigned_to = @$requestData['rem_cat'];
        $action->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
        $action->status = 0; // Required NOT NULL field (0 = active/open, 1 = closed/completed)
        $action->type = 'client'; // Required field - mark as client type for Action page filtering
        if (isset($requestData['followup_datetime']) && $requestData['followup_datetime'] != '') {
            //	$action->followup_date	= @$requestData['followup_date'].date('H:i', strtotime($requestData['followup_time']));
            $action->action_assign_date = @$requestData['followup_datetime'];
        }
        $saved = $action->save();
        if (! $saved) {
            echo json_encode(['success' => false, 'message' => 'Please try again', 'clientID' => $requestData['client_id']]);
        } else {
            $o = new \App\Models\Notification;
            $o->sender_id = Auth::user()->id;
            $o->receiver_id = @$requestData['rem_cat'];
            $o->module_id = $this->decodeString(@$requestData['client_id']);
            $o->url = route('clients.detail', @$requestData['client_id']);
            $o->notification_type = 'client';
            $o->message = 'Action Assigned by '.Auth::user()->first_name.' '.Auth::user()->last_name.' '.date('d/M/Y h:i A', strtotime($requestData['followup_datetime']));
            $o->seen = 0; // Set seen to 0 (unseen) for new notifications
            $o->save();

            $objs = new ActivitiesLog;
            $objs->client_id = $this->decodeString(@$requestData['client_id']);
            $objs->created_by = Auth::user()->id;
            $objs->subject = 'set action for '.@$requestData['assignee_name'];
            $objs->description = '<span class="text-semi-bold">'.@$requestData['remindersubject'].'</span><p>'.@$requestData['description'].'</p>';
            $objs->task_status = 0; // Required NOT NULL field (0 = activity, 1 = task)
            $objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
            if (Auth::user()->id != @$requestData['rem_cat']) {
                $objs->use_for = @$requestData['rem_cat'];
            } else {
                $objs->use_for = null; // Use null instead of empty string for PostgreSQL
            }
            $objs->followup_date = @$requestData['followup_datetime'];
            $objs->task_group = @$requestData['task_group'];
            $objs->save();
            echo json_encode(['success' => true, 'message' => 'successfully saved', 'clientID' => $requestData['client_id']]);
            exit;
        }
    }

    // Task reassign and update existing action
    public function reassignactionstore(Request $request)
    {
        $requestData = $request->all();
        // echo '<pre>'; print_r($requestData); die;
        /*if(\App\Models\Note::where('client_id',$requestData['client_id'])->where('assigned_to',$requestData['rem_cat'])->exists())
        {
            // return redirect()->back()->with('error', 'Lead already assigned');
            // return Redirect::to('/admin/assignee')->with('error', 'Lead already assigned');
            echo json_encode(array('success' => false, 'message' => 'Lead already assigned to '.@$requestData['assignee_name'], 'clientID' => $requestData['client_id']));
            exit;
        }*/

        $action = \App\Models\Note::query()->find($requestData['note_id']);

        if (! $action) {
            echo json_encode(['success' => false, 'message' => 'Note not found', 'clientID' => $requestData['client_id']]);
            exit;
        }

        $action->id = $action->id;
        $action->client_id = $this->decodeString(@$requestData['client_id']);
        $action->user_id = Auth::user()->id;
        $action->description = @$requestData['description'];
        $action->title = @$requestData['remindersubject'] ?? 'Lead assign to '.@$requestData['assignee_name'];
        $action->is_action = 1;
        $action->task_group = @$requestData['task_group'];
        $action->assigned_to = @$requestData['rem_cat'];
        if (isset($requestData['followup_datetime']) && $requestData['followup_datetime'] != '') {
            //	$action->followup_date	= @$requestData['followup_date'].date('H:i', strtotime($requestData['followup_time']));
            $action->action_assign_date = @$requestData['followup_datetime'];
        }
        $saved = $action->save();
        if (! $saved) {
            echo json_encode(['success' => false, 'message' => 'Please try again', 'clientID' => $requestData['client_id']]);
        } else {
            // Note: followup_date column was removed from admins table - no longer updating Lead.followup_date
            $followupDateText = isset($requestData['followup_datetime']) ? date('d/M/Y h:i A', strtotime($requestData['followup_datetime'])) : '';

            $o = new \App\Models\Notification;
            $o->sender_id = Auth::user()->id;
            $o->receiver_id = @$requestData['rem_cat'];
            $o->module_id = $this->decodeString(@$requestData['client_id']);
            $o->url = route('clients.detail', @$requestData['client_id']);
            $o->notification_type = 'client';
            $o->message = 'Action Assigned by '.Auth::user()->first_name.' '.Auth::user()->last_name.($followupDateText ? ' '.$followupDateText : '');
            $o->seen = 0; // Set seen to 0 (unseen) for new notifications
            $o->save();

            $objs = new ActivitiesLog;
            $objs->client_id = $this->decodeString(@$requestData['client_id']);
            $objs->created_by = Auth::user()->id;
            // $objs->subject = 'Action set for '.date('d/M/Y h:i A',strtotime($Lead->followup_date));
            $objs->subject = 'set action for '.@$requestData['assignee_name'];
            $objs->description = '<span class="text-semi-bold">'.@$requestData['remindersubject'].'</span><p>'.@$requestData['description'].'</p>';
            $objs->task_status = 0; // Required NOT NULL field (0 = activity, 1 = task)
            $objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
            if (Auth::user()->id != @$requestData['rem_cat']) {
                $objs->use_for = @$requestData['rem_cat'];
            } else {
                $objs->use_for = null; // Use null instead of empty string for PostgreSQL
            }
            $objs->followup_date = @$requestData['followup_datetime'];
            $objs->task_group = @$requestData['task_group'];
            $objs->save();
            echo json_encode(['success' => true, 'message' => 'successfully saved', 'clientID' => $requestData['client_id']]);
            exit;
        }
    }

    // Update task action and save
    public function updateaction(Request $request)
    {
        $requestData = $request->all();

        // echo '<pre>'; print_r($requestData); die;
        /*if(\App\Models\Note::where('client_id',$requestData['client_id'])->where('assigned_to',$requestData['rem_cat'])->exists())
        {
            echo json_encode(array('success' => false, 'message' => 'Lead already assigned to '.@$requestData['assignee_name'], 'clientID' => $requestData['client_id']));
            exit;
        }*/

        $action = \App\Models\Note::query()->find($requestData['note_id']);

        if (! $action) {
            echo json_encode(['success' => false, 'message' => 'Note not found', 'clientID' => $requestData['client_id']]);
            exit;
        }

        // $action 				= new \App\Models\Note;
        $action->id = $action->id;
        $action->client_id = $this->decodeString(@$requestData['client_id']);
        $action->user_id = Auth::user()->id;
        $action->description = @$requestData['description'];
        $action->title = @$requestData['remindersubject'] ?? 'Update Task and lead assign to '.@$requestData['assignee_name'];
        $action->is_action = 1;
        $action->task_group = @$requestData['task_group'];
        $action->assigned_to = @$requestData['rem_cat'];
        if (isset($requestData['followup_datetime']) && $requestData['followup_datetime'] != '') {
            //	$action->followup_date	= @$requestData['followup_date'].date('H:i', strtotime($requestData['followup_time']));
            $action->action_assign_date = @$requestData['followup_datetime'];
        }
        $saved = $action->save();

        if (! $saved) {
            echo json_encode(['success' => false, 'message' => 'Please try again', 'clientID' => $requestData['client_id']]);
        } else {
            // Note: followup_date column was removed from admins table - no longer updating Lead.followup_date
            $followupDateText = isset($requestData['followup_datetime']) ? date('d/M/Y h:i A', strtotime($requestData['followup_datetime'])) : '';

            $o = new \App\Models\Notification;
            $o->sender_id = Auth::user()->id;
            $o->receiver_id = @$requestData['rem_cat'];
            $o->module_id = $this->decodeString(@$requestData['client_id']);
            $o->url = route('clients.detail', @$requestData['client_id']);
            $o->notification_type = 'client';
            $o->message = 'Action Assigned by '.Auth::user()->first_name.' '.Auth::user()->last_name.($followupDateText ? ' '.$followupDateText : '');
            $o->seen = 0; // Set seen to 0 (unseen) for new notifications
            $o->save();

            $objs = new ActivitiesLog;
            $objs->client_id = $this->decodeString(@$requestData['client_id']);
            $objs->created_by = Auth::user()->id;
            // $objs->subject = 'Action set for '.date('d/M/Y h:i A',strtotime($Lead->followup_date));
            $objs->subject = 'Update task for '.@$requestData['assignee_name'];
            $objs->description = '<span class="text-semi-bold">'.@$requestData['remindersubject'].'</span><p>'.@$requestData['description'].'</p>';
            $objs->task_status = 0; // Required NOT NULL field (0 = activity, 1 = task)
            $objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
            if (Auth::user()->id != @$requestData['rem_cat']) {
                $objs->use_for = @$requestData['rem_cat'];
            } else {
                $objs->use_for = null; // Use null instead of empty string for PostgreSQL
            }

            $objs->followup_date = @$requestData['followup_datetime'];
            $objs->task_group = @$requestData['task_group'];
            $objs->save();
            echo json_encode(['success' => true, 'message' => 'successfully saved', 'clientID' => $requestData['client_id']]);
            exit;
        }
    }

    // Personal action
    public function personalaction(Request $request)
    {
        $requestData = $request->all();

        // Debug logging (remove in production)
        Log::info('personalaction request data: ', $requestData);

        $client_id = null;
        $req_clientID = '';

        if (isset($requestData['client_id']) && $requestData['client_id'] != '') {
            // Check if client_id contains "/" (encoded format)
            if (strpos($requestData['client_id'], '/') !== false) {
                $req_client_arr = explode('/', $requestData['client_id']);
                if (! empty($req_client_arr)) {
                    $req_clientID = $req_client_arr[0];
                    $client_id = $this->decodeString($req_clientID);
                    if ($client_id === false) {
                        $client_id = null;
                    }
                }
            }
            // Check if client_id is a raw integer (from enhanced select)
            elseif (is_numeric($requestData['client_id'])) {
                $client_id = (int) $requestData['client_id'];
                $req_clientID = $requestData['client_id'];
            }
            // Try to decode if it's an encoded string without "/"
            else {
                $decoded = $this->decodeString($requestData['client_id']);
                if ($decoded !== false) {
                    $client_id = $decoded;
                    $req_clientID = $requestData['client_id'];
                }
            }
        }

        Log::info('personalaction parsed client_id: '.$client_id);

        // Reject only when a client was selected but could not be parsed
        $rawClientId = isset($requestData['client_id']) ? trim((string) $requestData['client_id']) : '';
        if ($rawClientId !== '' && ($client_id === null || $client_id === '')) {
            Log::error('personalaction: Invalid client_id. Request: '.json_encode($requestData));
            echo json_encode(['success' => false, 'message' => 'Invalid client ID. Please select a valid client.', 'clientID' => $rawClientId]);
            exit;
        }

        /*if(\App\Models\Note::where('client_id',$requestData['client_id'])->where('assigned_to',$requestData['rem_cat'])->exists())
        {
            echo json_encode(array('success' => false, 'message' => 'Lead already assigned to '.@$requestData['assignee_name'], 'clientID' => $req_clientID));
            exit;
        }*/
        $action = new \App\Models\Note;
        $action->client_id = $client_id; // $this->decodeString(@$requestData['client_id']);
        $action->user_id = Auth::user()->id;
        $action->description = @$requestData['description'];
        $action->title = @$requestData['remindersubject'] ?? 'Personal Task assign to '.@$requestData['assignee_name'];
        $action->is_action = 1;
        $action->task_group = @$requestData['task_group'];
        $action->assigned_to = @$requestData['rem_cat'];
        $action->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
        $action->status = 0; // Required NOT NULL field (0 = active/open, 1 = closed/completed)
        $action->type = 'client'; // Required field - mark as client type
        if (isset($requestData['followup_datetime']) && $requestData['followup_datetime'] != '') {
            $action->action_assign_date = @$requestData['followup_datetime'];
        }
        try {
            $saved = $action->save();
        } catch (\Exception $e) {
            Log::error('Error saving action in personalaction: '.$e->getMessage());
            Log::error('Error trace: '.$e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => 'Error saving action: '.$e->getMessage(), 'clientID' => $client_id]);
            exit;
        }

        if (! $saved) {
            echo json_encode(['success' => false, 'message' => 'Please try again', 'clientID' => $client_id]); // $requestData['client_id']
        } else {
            // Validate receiver_id before creating notification
            if (isset($requestData['rem_cat']) && $requestData['rem_cat'] != '') {
                $o = new \App\Models\Notification;
                $o->sender_id = Auth::user()->id;
                $o->receiver_id = $requestData['rem_cat'];
                $o->module_id = $client_id ?? $action->id;
                $o->url = $client_id
                    ? route('clients.detail', base64_encode(convert_uuencode($client_id)))
                    : route('action.index');
                $o->notification_type = $client_id ? 'client' : 'action';
                // Safely format date
                $followupDateText = '';
                if (isset($requestData['followup_datetime']) && $requestData['followup_datetime'] != '') {
                    $timestamp = strtotime($requestData['followup_datetime']);
                    if ($timestamp !== false) {
                        $followupDateText = ' '.date('d/M/Y h:i A', $timestamp);
                    }
                }
                $o->message = 'Personal Task Action Assigned by '.Auth::user()->first_name.' '.Auth::user()->last_name.$followupDateText;
                $o->seen = 0; // Set seen to 0 (unseen) for new notifications
                $o->save();
            }

            $objs = new ActivitiesLog;
            $objs->client_id = $client_id; // $this->decodeString(@$requestData['client_id']);
            $objs->created_by = Auth::user()->id;
            $objs->subject = 'set action for '.@$requestData['assignee_name'];
            $objs->description = '<span class="text-semi-bold">'.@$requestData['remindersubject'].'</span><p>'.@$requestData['description'].'</p>';
            if (Auth::user()->id != @$requestData['rem_cat']) {
                $objs->use_for = @$requestData['rem_cat'];
            } else {
                $objs->use_for = null; // Use null instead of empty string for PostgreSQL
            }
            $objs->followup_date = @$requestData['followup_datetime'];
            $objs->task_group = @$requestData['task_group'];
            $objs->task_status = 0; // Required NOT NULL field for PostgreSQL (0 = activity, 1 = task)
            $objs->pin = 0; // Required NOT NULL field for PostgreSQL (0 = not pinned, 1 = pinned)
            $objs->save();
            echo json_encode(['success' => true, 'message' => 'successfully saved', 'clientID' => $client_id]); // $requestData['client_id']
            exit;
        }
    }

    public function retagaction(Request $request)
    {
        $requestData = $request->all();

        //	echo '<pre>'; print_r($requestData); die;
        $action = new \App\Models\Note;
        $action->client_id = @$requestData['client_id'];
        $action->user_id = Auth::user()->id;
        $action->description = @$requestData['message'];
        $action->title = '';
        $action->is_action = 1;
        $action->assigned_to = @$requestData['changeassignee'];
        $action->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
        $action->status = 0; // Required NOT NULL field (0 = active/open, 1 = closed/completed)
        $action->type = 'client'; // Required field - mark as client type for Action page filtering
        if (isset($requestData['followup_date']) && $requestData['followup_date'] != '') {

            $action->action_assign_date = $requestData['followup_date'].' '.date('H:i', strtotime($requestData['followup_time']));
        }

        $saved = $action->save();

        if (! $saved) {
            return redirect()->route('action.index')->with('error', 'Please try again');
        } else {
            /*$objnote =  \App\Models\Note::find();
            $objnote->status = 1;
            $objnote->save();*/
            $newassignee = \App\Models\Staff::query()->find($requestData['changeassignee']);
            $o = new \App\Models\Notification;
            $o->sender_id = Auth::user()->id;
            $o->receiver_id = @$requestData['changeassignee'];
            $o->module_id = @$requestData['client_id'];
            $o->url = route('clients.detail', @$requestData['client_id']);
            $o->notification_type = 'client';
            $o->message = 'Action Assigned by '.Auth::user()->first_name.' '.Auth::user()->last_name;
            $o->seen = 0; // Set seen to 0 (unseen) for new notifications
            $o->save();

            $objs = new ActivitiesLog;
            $objs->client_id = @$requestData['client_id'];
            $objs->created_by = Auth::user()->id;
            $objs->subject = Auth::user()->first_name.' '.Auth::user()->last_name.' tags work to '.$newassignee->first_name.' '.$newassignee->last_name;
            $objs->description = @$requestData['message'];
            $objs->task_status = 0; // Required NOT NULL field (0 = activity, 1 = task)
            $objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
            $objs->save();

            return redirect()->route('action.index')->with('success', 'Record Updated successfully');
        }
    }

    // Assign application stage and save
    public function actionstore_application(Request $request)
    {
        $requestData = $request->all(); // echo '<pre>'; print_r($requestData); die;
        // echo "client_id==".$requestData['client_id'];
        // echo $client_decode_id = base64_encode(convert_uuencode($requestData['client_id'])); die;
        $client_decode_id = base64_encode(convert_uuencode($requestData['client_id']));

        $action = new \App\Models\Note;
        $action->client_id = @$requestData['client_id'];
        $action->user_id = Auth::user()->id;

        // Get Description
        $description = 'Application '.$requestData['course'].' for this college '.$requestData['school'].' assigned for '.$requestData['stage_name'].' stage';
        $action->description = $description;

        // Get assigner name
        $assignee_info = \App\Models\Staff::select('id', 'first_name', 'last_name')->find($requestData['rem_cat11']);
        if ($assignee_info) {
            $assignee_name = $assignee_info->first_name;
        } else {
            $assignee_name = 'N/A';
        }
        $title = 'Application assign to '.$assignee_name;
        $action->title = $title;
        $action->is_action = 1;
        $action->task_group = 'stage';
        $action->assigned_to = @$requestData['rem_cat11'];
        $action->action_assign_date = date('Y-m-d H:i:s');
        $action->application_id = $requestData['application_id'];
        $action->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
        $action->status = 0; // Required NOT NULL field (0 = active/open, 1 = closed/completed)
        $action->type = 'client'; // Required field - mark as client type for Action page filtering
        $saved = $action->save();
        if (! $saved) {
            echo json_encode(['success' => false, 'message' => 'Please try again', 'clientID' => $client_decode_id]);
        } else {
            /*if(isset($requestData['followup_datetime']) && $requestData['followup_datetime'] != ''){
                $Lead = Admin::find($this->decodeString($requestData['client_id']));
                $Lead->followup_date = date('Y-m-d H:i:s');
                $Lead->save();
            }*/

            $o = new \App\Models\Notification;
            $o->sender_id = Auth::user()->id;
            $o->receiver_id = @$requestData['rem_cat11'];
            $o->module_id = $requestData['client_id'];

            $o->url = route('clients.detail', $client_decode_id);
            $o->notification_type = 'client';
            $o->message = 'Action Assigned by '.Auth::user()->first_name.' '.Auth::user()->last_name.' '.date('d/M/Y h:i A');
            $o->seen = 0; // Set seen to 0 (unseen) for new notifications
            $o->save();

            $obj1 = new \App\Models\ApplicationActivitiesLog;
            $obj1->stage = $requestData['stage_name'];
            $obj1->type = 'task';
            $obj1->comment = 'assigned a task';
            $obj1->title = 'set action for '.@$assignee_name;
            $obj1->description = '<span class="text-semi-bold">'.@$title.'</span><p>'.$description.'</p>';
            $obj1->app_id = $requestData['application_id'];
            $obj1->user_id = Auth::user()->id;
            $obj1->save();

            echo json_encode(['success' => true, 'message' => 'Applcation successfully assigned', 'clientID' => $client_decode_id, 'application_id' => $requestData['application_id']]);
            exit;
        }
    }

    /**
     * JSON slot list for the schedule-follow-up modal (per consultant calendar settings + blocks).
     */
    public function scheduleFollowupSlots(Request $request)
    {
        $validated = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'consultant' => [
                'required',
                'string',
                'max:120',
                Rule::exists('followup_consultants', 'slug')->where('status', 1),
            ],
            'date' => ['required', 'date_format:Y-m-d'],
            'service' => ['nullable', 'string', 'in:free'],
        ])->validate();

        $service = $validated['service'] ?? 'free';
        $consultantSlug = $validated['consultant'];

        return response()->json([
            'slots' => FollowupAvailability::slotStartsFor($consultantSlug, $validated['date'], $service),
            'disable_weekdays' => FollowupAvailability::disabledJsWeekdays($consultantSlug, $service),
            'slot_duration_minutes' => FollowupAvailability::slotDurationMinutes($consultantSlug, $service),
        ]);
    }

    /**
     * Store a scheduled follow-up from the client detail “Add follow-up” modal.
     * Persists as a client Note + ActivitiesLog (same pattern as other actions).
     */
    public function scheduleFollowupStore(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'client_id' => 'required|string',
            'client_reference' => 'nullable|string|max:120',
            'followup_type' => 'required|string|max:500',
            'service' => 'required|in:free',
            'consultant' => [
                'required',
                'string',
                'max:120',
                Rule::exists('followup_consultants', 'slug')->where('status', 1),
            ],
            'followup_detail' => ['required', 'string', 'max:200', Rule::in(['In-Person', 'Phone call'])],
            'preferred_language' => 'required|string|in:English,Hindi,Punjabi',
            'details_of_enquiry' => 'required|string|max:15000',
            'followup_datetime' => 'required|string|max:40',
        ]);

        if ($validator->fails()) {
            echo json_encode(['success' => false, 'message' => $validator->errors()->first()]);
            exit;
        }

        $data = $validator->validated();

        $decodedId = $this->decodeString($data['client_id']);
        if ($decodedId === false || ! $decodedId) {
            echo json_encode(['success' => false, 'message' => 'Invalid client.']);
            exit;
        }

        $admin = Admin::query()->find($decodedId);
        if (! $admin) {
            echo json_encode(['success' => false, 'message' => 'Client not found.']);
            exit;
        }

        if (! empty($data['client_reference']) && trim((string) $data['client_reference']) !== trim((string) ($admin->client_id ?? ''))) {
            echo json_encode(['success' => false, 'message' => 'Client reference does not match this record.']);
            exit;
        }

        try {
            $parsedFollowup = \Carbon\Carbon::parse($data['followup_datetime']);
            $followupAt = $parsedFollowup->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Invalid follow-up date or time.']);
            exit;
        }

        $slotHm = $parsedFollowup->format('H:i');
        $dateOnly = $parsedFollowup->format('Y-m-d');
        if (! FollowupAvailability::isValidSlotSelection($data['consultant'], $dateOnly, $slotHm, $data['service'])) {
            echo json_encode(['success' => false, 'message' => 'This time is not available for the selected consultant. Choose another slot.']);
            exit;
        }

        $consultantDbName = FollowupConsultant::query()
            ->where('slug', $data['consultant'])
            ->where('status', 1)
            ->value('name');

        $consultantDisplay = FollowupController::consultantLabel($data['consultant'])
            ?? ($consultantDbName !== null
                ? preg_replace('/\s+Calendar$/u', ' Followups', (string) $consultantDbName)
                : $data['consultant']);
        $serviceLabels = [
            'free' => 'Free Consultation',
        ];
        $serviceLabel = $serviceLabels[$data['service']] ?? $data['service'];

        $detailsSafe = nl2br(htmlspecialchars($data['details_of_enquiry'], ENT_QUOTES, 'UTF-8'));

        try {
            $followTimingDisplay = $parsedFollowup->copy()->timezone(config('app.timezone'))->format('j M Y, g:i a');
        } catch (\Throwable $e) {
            $followTimingDisplay = $followupAt;
        }

        $description = '<p><strong>Scheduled follow-up</strong></p>'
            .'<ul>'
            .'<li><strong>Follow timing:</strong> '.htmlspecialchars($followTimingDisplay, ENT_QUOTES, 'UTF-8').'</li>'
            .'<li><strong>Follow-up type:</strong> '.htmlspecialchars($data['followup_type'], ENT_QUOTES, 'UTF-8').'</li>'
            .'<li><strong>Service:</strong> '.htmlspecialchars($serviceLabel, ENT_QUOTES, 'UTF-8').'</li>'
            .'<li><strong>Consultant:</strong> '.htmlspecialchars($consultantDisplay, ENT_QUOTES, 'UTF-8').'</li>'
            .'<li><strong>Follow-up details:</strong> '.htmlspecialchars($data['followup_detail'], ENT_QUOTES, 'UTF-8').'</li>'
            .'<li><strong>Preferred language:</strong> '.htmlspecialchars($data['preferred_language'], ENT_QUOTES, 'UTF-8').'</li>'
            .'</ul>'
            .'<p><strong>Details:</strong></p><p>'.$detailsSafe.'</p>';

        $canonicalTitle = FollowupController::followupNoteTitle($data['consultant']);
        $noteTitle = $canonicalTitle ?? ('Followup — '.$consultantDisplay);

        $note = new \App\Models\Note;
        $note->client_id = $decodedId;
        $note->user_id = Auth::user()->id;
        $note->description = $description;
        $note->title = $noteTitle;
        $note->is_action = 1;
        $note->task_group = 'Followup';
        $note->assigned_to = Auth::user()->id;
        $note->pin = 0;
        $note->status = 0;
        $note->type = 'client';
        $note->action_assign_date = $followupAt;

        if (! $note->save()) {
            echo json_encode(['success' => false, 'message' => 'Please try again']);
            exit;
        }

        try {
            $log = new ActivitiesLog;
            $log->client_id = $decodedId;
            $log->created_by = Auth::user()->id;
            $log->subject = 'Scheduled follow-up ('.$consultantDisplay.')';
            $log->description = '<span class="text-semi-bold">'.e($note->title).'</span><p>'.$description.'</p>';
            $log->use_for = null;
            $log->followup_date = $followupAt;
            $log->task_group = 'Followup';
            $log->task_status = 0;
            $log->pin = 0;
            $log->save();
        } catch (\Throwable $e) {
            Log::warning('scheduleFollowupStore: activity log save failed: '.$e->getMessage());
        }

        echo json_encode([
            'success' => true,
            'message' => (function () use ($parsedFollowup, $followupAt) {
                $base = 'Follow-up scheduled successfully.';
                try {
                    $tzToday = \Carbon\Carbon::today(config('app.timezone'));
                    $assignDay = $parsedFollowup->copy()->timezone(config('app.timezone'))->startOfDay();
                    if ($assignDay->gt($tzToday)) {
                        $appear = $parsedFollowup->copy()->timezone(config('app.timezone'))->format('d/m/Y');
                        return $base.' It will appear on the Action list on '.$appear
                            .' (or use “Include scheduled follow-ups” on Action pages to see it earlier).';
                    }
                } catch (\Throwable $e) {
                    // keep short message
                }
                return $base;
            })(),
            'clientID' => $data['client_id'],
        ]);
        exit;
    }
}
