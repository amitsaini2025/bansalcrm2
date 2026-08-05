<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Contracts\Database\Eloquent\Builder;

// use App\Models\Appointment; // Appointment model deleted
use App\Models\Note;
// use App\Models\AppointmentLog; // AppointmentLog model deleted - appointments table removed
use App\Models\Notification;
use Carbon\Carbon;
use App\Models\Admin;
use App\Models\ActivitiesLog;
use Auth;
use Illuminate\Support\Facades\DB;
use DataTables;
use App\Support\Utf8Helper;

class ActionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

     public function __construct()
     {
         $this->middleware('auth:admin');
     }

    //Update task to be complete
    public function markComplete(Request $request)
    {
        try {
            $noteId = $request->input('id');
            
            if (!$noteId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Action ID is required'
                ], 400);
            }
            
            $note = Note::find($noteId);
            
            if (!$note) {
                return response()->json([
                    'status' => false,
                    'message' => 'Action not found'
                ], 404);
            }
            
            // Check if already completed
            if ($note->status == 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'This action is already completed'
                ], 400);
            }
            
            // Get client_id from request or note
            $clientId = $request->input('client_id') ?: $note->client_id;

            if (!$clientId && !$note->isPersonalTaskWithoutClient()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Client ID is required'
                ], 400);
            }
            
            // Get completion message from request (required)
            $completionMessage = $request->input('completion_message', '');
            
            if (empty($completionMessage)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Completion message is required'
                ], 400);
            }
            
            // Update note status to completed
            $note->status = 1;
            $note->save();
            
            // Get application and stage name if this is a stage-type action
            $stageName = null;
            $application = null;
            if (!empty($note->application_id)) {
                $application = \App\Models\Application::find($note->application_id);
                if ($application) {
                    $stageName = $application->stage;
                }
            }
            
            // Create activity log entry
            $admin_data = \App\Models\Staff::find($note->assigned_to);
            if($admin_data){
                $assignee_name = $admin_data->first_name." ".$admin_data->last_name;
            } else {
                $assignee_name = 'N/A';
            }
            
            if ($clientId) {
                $objs = new ActivitiesLog;
                $objs->client_id = $clientId;
                $objs->created_by = Auth::user()->id;

                // For stage-type actions, include the stage name in the subject
                if ($note->task_group == 'stage' && !empty($stageName)) {
                    $objs->subject = 'Completed ' . $stageName . ' stage action';
                } else {
                    $objs->subject = 'Completed action';
                }

                $objs->description = '<span class="text-semi-bold">Action Completed</span><p>' . htmlspecialchars($completionMessage) . '</p>';

                if (Auth::user()->id != @$note->assigned_to) {
                    $objs->use_for = @$note->assigned_to;
                } else {
                    $objs->use_for = null;
                }

                $objs->followup_date = @$note->updated_at;
                // Set task_group to 'partner' if note type is partner, otherwise use note's task_group
                $objs->task_group = ($note->type == 'partner') ? 'partner' : @$note->task_group;
                $objs->task_status = 0; // Activity, not task
                $objs->pin = 0;
                $objs->save();
            }
            
            // If this action is related to an application, also log to ApplicationActivitiesLog
            if (!empty($note->application_id) && $application) {
                // Get the ORIGINAL note description (entered when assigning the task)
                $originalNoteDescription = strip_tags($note->description);
                
                $obj1 = new \App\Models\ApplicationActivitiesLog;
                $obj1->stage = $application->stage;
                $obj1->type = 'task';
                $obj1->comment = 'completed a task';
                $obj1->title = 'Action completed by '.Auth::user()->first_name.' '.Auth::user()->last_name;
                // Show BOTH the original note description AND the completion message
                $obj1->description = '<span class="text-semi-bold">Action Completed</span><p>' . htmlspecialchars($originalNoteDescription) . '</p><hr><p><strong>Completion Note:</strong> ' . htmlspecialchars($completionMessage) . '</p>';
                $obj1->app_id = $note->application_id;
                $obj1->user_id = Auth::user()->id;
                $obj1->save();
            }

            return response()->json([
                'status' => true,
                'message' => 'Action completed successfully!'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error completing action: ' . $e->getMessage());
            \Log::error('Error trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while completing the action. Please try again.'
            ], 500);
        }
    }
    
    // Get note data for completion modal
    public function getNoteData(Request $request)
    {
        try {
            $noteId = $request->input('id');
            
            if (!$noteId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Note ID is required'
                ], 400);
            }
            
            $note = Note::with(['noteClient'])->find($noteId);
            
            if (!$note) {
                return response()->json([
                    'status' => false,
                    'message' => 'Note not found'
                ], 404);
            }
            
            $clientName = 'N/A';
            if ($note->isPersonalTaskWithoutClient()) {
                $clientName = 'Personal Action';
            } elseif ($note->type == 'client' && $note->noteClient) {
                $clientName = trim(($note->noteClient->first_name ?? '') . ' ' . ($note->noteClient->last_name ?? ''));
            } elseif ($note->type == 'partner') {
                $partner = \App\Models\Partner::find($note->client_id);
                if ($partner) {
                    $clientName = $partner->partner_name ?? 'N/A';
                }
            }
            
            return response()->json([
                'status' => true,
                'client_id' => $note->client_id,
                'client_name' => $clientName
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error getting note data: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving note data'
            ], 500);
        }
    }

    //Update task to be not complete
    public function markIncomplete(Request $request)
    {
        try {
            $noteId = $request->input('id');

            if (!$noteId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Action ID is required'
                ], 400);
            }

            // Get the note before updating
            $noteRecord = Note::find($noteId);

            if (!$noteRecord) {
                return response()->json([
                    'status' => false,
                    'message' => 'Action not found'
                ], 404);
            }

            // Store note details before update
            $clientId = $noteRecord->client_id;
            $noteType = $noteRecord->type;
            $taskGroup = $noteRecord->task_group;
            $assignedTo = $noteRecord->assigned_to;

            // Update status to incomplete
            $updated = Note::where('id', $noteId)->update(['status' => '0']);

            if ($updated) {
                // Create activity log entry for marking incomplete
                $admin_data = \App\Models\Staff::find($assignedTo);
                if ($admin_data) {
                    $assignee_name = $admin_data->first_name . " " . $admin_data->last_name;
                } else {
                    $assignee_name = 'N/A';
                }

                if ($clientId) {
                    $objs = new ActivitiesLog;
                    $objs->client_id = $clientId;
                    $objs->created_by = Auth::user()->id;
                    $objs->subject = 'Marked action as incomplete';
                    $objs->description = '<span class="text-semi-bold">Action marked as incomplete</span><p>This action was marked as incomplete and moved back to active tasks.</p>';

                    if (Auth::user()->id != $assignedTo) {
                        $objs->use_for = $assignedTo;
                    } else {
                        $objs->use_for = null;
                    }

                    $objs->followup_date = now();
                    // Set task_group to 'partner' if note type is partner, otherwise use note's task_group
                    $objs->task_group = ($noteType == 'partner') ? 'partner' : $taskGroup;
                    $objs->task_status = 0; // Activity, not task
                    $objs->pin = 0;
                    $objs->save();
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Task updated successfully'
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Please try again'
            ], 400);

        } catch (\Exception $e) {
            \Log::error('Error marking incomplete: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating the task'
            ], 500);
        }
    }


    //All assigned by me task list which r incomplete
    public function assignedByMe(Request $request)
    {  //dd(Auth::user()->id);
         $includeScheduledFollowups = $this->wantsIncludeScheduledFollowups($request);
         if(\Auth::user()->role == 1){
             $assignees_notCompleted = $this->applyFollowupAssignDateVisibilityFilter(
                 \App\Models\Note::sortable()
                 ->with(['noteUser','noteClient','assigned_user'])
                 ->where('status','<>','1')
                 ->where('type','client')
                 ->whereNotNull('client_id')
                 ->where('is_action',1),
                 $includeScheduledFollowups
             )->orderByRaw('created_at DESC NULLS LAST')->paginate(20);
         } else {
             $assignees_notCompleted = $this->applyFollowupAssignDateVisibilityFilter(
                 \App\Models\Note::sortable()
                 ->with(['noteUser','noteClient','assigned_user'])
                 ->where('status','<>','1')
                 ->where('user_id',\Auth::user()->id)
                 ->where('type','client')
                 ->where('is_action',1),
                 $includeScheduledFollowups
             )->orderByRaw('created_at DESC NULLS LAST')->paginate(20);
         }
         #dd($assignees_notCompleted);
         return view('Admin.action.assigned_by_me', compact('assignees_notCompleted', 'includeScheduledFollowups'))
          ->with('i', (request()->input('page', 1) - 1) * 20);
    }


    //All assigned to me task list
    public function assignedToMe(Request $request)
    {
        $includeScheduledFollowups = $this->wantsIncludeScheduledFollowups($request);
        if(\Auth::user()->role == 1){
            $assignees_notCompleted = $this->applyFollowupAssignDateVisibilityFilter(
                \App\Models\Note::sortable()
                ->with(['noteUser','noteClient','assigned_user'])
                ->where('status','<>','1')
                ->where('assigned_to',\Auth::user()->id)
                ->where('type','client')
                ->whereNotNull('client_id')
                ->where('is_action',1),
                $includeScheduledFollowups
            )->orderByRaw('created_at DESC NULLS LAST')->paginate(20);

            $assignees_completed = \App\Models\Note::sortable()
            ->with(['noteUser','noteClient','assigned_user'])->where('status','1')->where('assigned_to',\Auth::user()->id)->where('type','client')->whereNotNull('client_id')->where('is_action',1)->orderByRaw('created_at DESC NULLS LAST')->paginate(20);
        }else{
            $assignees_notCompleted = $this->applyFollowupAssignDateVisibilityFilter(
                \App\Models\Note::sortable()
                ->with(['noteUser','noteClient','assigned_user'])
                ->where('status','<>','1')
                ->where('assigned_to',\Auth::user()->id)
                ->where('type','client')
                ->where('is_action',1),
                $includeScheduledFollowups
            )->orderByRaw('created_at DESC NULLS LAST')->paginate(20);

            $assignees_completed = \App\Models\Note::sortable()
            ->with(['noteUser','noteClient','assigned_user'])->where('status','1')->where('assigned_to',\Auth::user()->id)->where('type','client')->where('is_action',1)->orderByRaw('created_at DESC NULLS LAST')->paginate(20);
        }
        //dd($assignees_notCompleted);
        //dd($assignees_completed);
        return view('Admin.action.assigned_to_me', compact('assignees_notCompleted', 'assignees_completed', 'includeScheduledFollowups'))
         ->with('i', (request()->input('page', 1) - 1) * 20);
    }



   //All incomplete activities list
    /*public function activities(Request $request)
    {   //dd($request->all());
        $req_data = $request->all();
        if( isset($req_data['group_type'])  && $req_data['group_type'] != ""){
            $task_group = $req_data['group_type'];
        } else {
            $task_group = 'All';
        }

        if( isset($req_data['search_by'])  && $req_data['search_by'] != ""){
            $search_by = $req_data['search_by'];
        } else {
            $search_by = "";
        }
        //dd($task_group.'==='.$search_by); dd(Auth::user()->id);
        if($task_group == 'All')
        {  //if no task group is present

            if(\Auth::user()->role == 1)
            { //admin role
                if($search_by) { //if search string is present
                    $assignees_notCompleted = \App\Models\Note::sortable()
                    ->with(['noteUser','noteClient','assigned_user'])
                    ->where('status','<>','1')
                    ->where('type','client')
                    ->where('is_action',1)
                    ->where(function($subQuery) use ($search_by)
                    {   
                        $subQuery->whereHas('noteUser', function ( $query ) use ($search_by) {
                            $query->where('first_name', 'LIKE', '%'.$search_by.'%');
                            $query->orWhere('last_name', 'LIKE', '%'.$search_by.'%');
                        })
                        ->orWhereHas('noteClient', function ( $query ) use ($search_by)  {
                            $query->where('first_name', 'LIKE', '%'.$search_by.'%');
                            $query->orWhere('last_name', 'LIKE', '%'.$search_by.'%');
                        })
                        ->orWhereHas('noteClient', function ( $query ) use ($search_by)  {
                            $query->where('phone', 'LIKE', '%'.$search_by.'%');
                        })
                        ->orWhereHas('noteClient', function ( $query ) use ($search_by)  {
                             $query->where('client_id', 'LIKE', '%'.$search_by.'%');
                        });
                    })->orderByRaw('created_at DESC NULLS LAST')
                    ->paginate(20);
                } else { //if no searching
                    $assignees_notCompleted = \App\Models\Note::sortable()
                    ->with(['noteUser','noteClient','assigned_user'])
                    ->where('status','<>','1')
                    ->where('type','client')
                    ->whereNotNull('client_id')
                    ->where('is_action',1)
                    ->orderByRaw('created_at DESC NULLS LAST')->paginate(20);
                }
                //dd($assignees_notCompleted);
            }
            else
            { //role is not admin
                if($search_by) { //if search string is present
                    //dd('ifff'.Auth::user()->id);
                    $assignees_notCompleted = \App\Models\Note::sortable()
                    ->with(['noteUser','noteClient','assigned_user'])
                    ->where('status','<>','1')
                    ->where('assigned_to',\Auth::user()->id)
                    ->where('type','client')
                    ->where('is_action',1)
                    ->where(function($subQuery) use ($search_by)
                    {   
                        $subQuery->whereHas('noteUser', function ( $query ) use ($search_by) {
                            $query->where('first_name', 'LIKE', '%'.$search_by.'%');
                            $query->orWhere('last_name', 'LIKE', '%'.$search_by.'%');
                        })
                        ->orWhereHas('noteClient', function ( $query ) use ($search_by)  {
                            $query->where('first_name', 'LIKE', '%'.$search_by.'%');
                            $query->orWhere('last_name', 'LIKE', '%'.$search_by.'%');
                        })
                        ->orWhereHas('noteClient', function ( $query ) use ($search_by)  {
                            $query->where('phone', 'LIKE', '%'.$search_by.'%');
                        })
                        ->orWhereHas('noteClient', function ( $query ) use ($search_by)  {
                             $query->where('client_id', 'LIKE', '%'.$search_by.'%');
                        });
                    })->orderByRaw('created_at DESC NULLS LAST')
                    ->paginate(20);
                } else { //if no searching
                //dd('elsee');
                    $assignees_notCompleted = \App\Models\Note::sortable()
                    ->with(['noteUser','noteClient','assigned_user'])
                    ->where('status','<>','1')
                    ->where('assigned_to',\Auth::user()->id)
                    ->where('type','client')
                    ->where('is_action',1)
                    ->orderByRaw('created_at DESC NULLS LAST')
                    ->paginate(20);
                }
                //dd($assignees_notCompleted);
            }
        }
        else
        { //if search by task group is present
            if(\Auth::user()->role == 1)
            {  //admin role
                if($search_by) { //if search string is present
                    $assignees_notCompleted = \App\Models\Note::sortable()
                    ->with(['noteUser','noteClient','assigned_user'])
                    ->where('task_group','like',$task_group)
                    ->where('status','<>','1')
                    ->where('type','client')
                    ->whereNotNull('client_id')
                    ->where('is_action',1)
                    ->where(function($subQuery) use ($search_by)
                    {   
                        $subQuery->whereHas('noteUser', function ( $query ) use ($search_by) {
                            $query->where('first_name', 'LIKE', '%'.$search_by.'%');
                            $query->orWhere('last_name', 'LIKE', '%'.$search_by.'%');
                        })
                        ->orWhereHas('noteClient', function ( $query ) use ($search_by)  {
                            $query->where('first_name', 'LIKE', '%'.$search_by.'%');
                            $query->orWhere('last_name', 'LIKE', '%'.$search_by.'%');
                        })
                        ->orWhereHas('noteClient', function ( $query ) use ($search_by)  {
                            $query->where('phone', 'LIKE', '%'.$search_by.'%');
                        })
                        ->orWhereHas('noteClient', function ( $query ) use ($search_by)  {
                             $query->where('client_id', 'LIKE', '%'.$search_by.'%');
                        });
                    })->orderByRaw('created_at DESC NULLS LAST')
                    ->paginate(20);
                } else { //if no searching
                    $assignees_notCompleted = \App\Models\Note::sortable()
                    ->with(['noteUser','noteClient','assigned_user'])
                    ->where('task_group','like',$task_group)
                    ->where('status','<>','1')
                    ->where('type','client')
                    ->whereNotNull('client_id')
                    ->where('is_action',1)
                    ->orderByRaw('created_at DESC NULLS LAST')
                    ->paginate(20);
                }
                //dd($assignees_notCompleted);
            }
            else
            { //role is not admin
                if($search_by) { //if search string is present
                    $assignees_notCompleted = \App\Models\Note::sortable()
                    ->with(['noteUser','noteClient','assigned_user'])
                    ->where('task_group','like',$task_group)
                    ->where('status','<>','1')
                    ->where('assigned_to',\Auth::user()->id)
                    ->where('type','client')
                    ->where('is_action',1)
                    ->where(function($subQuery) use ($search_by)
                    {   
                        $subQuery->whereHas('noteUser', function ( $query ) use ($search_by) {
                            $query->where('first_name', 'LIKE', '%'.$search_by.'%');
                            $query->orWhere('last_name', 'LIKE', '%'.$search_by.'%');
                        })
                        ->orWhereHas('noteClient', function ( $query ) use ($search_by)  {
                            $query->where('first_name', 'LIKE', '%'.$search_by.'%');
                            $query->orWhere('last_name', 'LIKE', '%'.$search_by.'%');
                        })
                        ->orWhereHas('noteClient', function ( $query ) use ($search_by)  {
                            $query->where('phone', 'LIKE', '%'.$search_by.'%');
                        })
                        ->orWhereHas('noteClient', function ( $query ) use ($search_by)  {
                             $query->where('client_id', 'LIKE', '%'.$search_by.'%');
                        });
                    })->orderByRaw('created_at DESC NULLS LAST')
                    ->paginate(20);
                } else { //if no searching
                    $assignees_notCompleted = \App\Models\Note::sortable()
                    ->with(['noteUser','noteClient','assigned_user'])
                    ->where('task_group','like',$task_group)
                    ->where('status','<>','1')
                    ->where('assigned_to',\Auth::user()->id)
                    ->where('type','client')
                    ->where('is_action',1)
                    ->orderByRaw('created_at DESC NULLS LAST')
                    ->paginate(20);
                }
                //dd($assignees_notCompleted);
            }
        }
        //dd($assignees_notCompleted);
        return view('Admin.action.index',compact('assignees_notCompleted','task_group'))
         ->with('i', (request()->input('page', 1) - 1) * 20);
    }*/

    public function completed(Request $request)
    {   //dd($request->all());
        $req_data = $request->all();
        if( isset($req_data['group_type'])  && $req_data['group_type'] != ""){
            $task_group = $req_data['group_type'];
        } else {
            $task_group = 'All';
        }
        //dd($task_group);
        if($task_group == 'All') {
            if(\Auth::user()->role == 1){
                $assignees_completed = \App\Models\Note::sortable()
                ->with(['noteUser','noteClient','notePartner','assigned_user'])
                ->where('status','1')
                ->whereIn('type',['client', 'partner']) // Include both client and partner actions
                ->whereNotNull('client_id')
                ->where('is_action',1)
                ->orderByRaw('created_at DESC NULLS LAST')->paginate(20);
            } else {
                $assignees_completed = \App\Models\Note::sortable()
                ->with(['noteUser','noteClient','notePartner','assigned_user'])
                ->where('status','1')
                ->where('assigned_to',\Auth::user()->id)
                ->whereIn('type',['client', 'partner']) // Include both client and partner actions
                ->where('is_action',1)
                ->orderByRaw('created_at DESC NULLS LAST')->paginate(20);
            }
        } else {
            if(\Auth::user()->role == 1){
                $assignees_completed = \App\Models\Note::sortable()
                ->with(['noteUser','noteClient','notePartner','assigned_user'])
                ->where('task_group','like',$task_group)
                ->where('status','1')
                ->whereIn('type',['client', 'partner']) // Include both client and partner actions
                ->whereNotNull('client_id')
                ->where('is_action',1)
                ->orderByRaw('created_at DESC NULLS LAST')->paginate(20);
            } else {
                $assignees_completed = \App\Models\Note::sortable()
                ->with(['noteUser','noteClient','notePartner','assigned_user'])
                ->where('task_group','like',$task_group)
                ->where('status','1')
                ->where('assigned_to',\Auth::user()->id)
                ->whereIn('type',['client', 'partner']) // Include both client and partner actions
                ->where('is_action',1)
                ->orderByRaw('created_at DESC NULLS LAST')->paginate(20);
            }
        }
        #dd($assignees_completed);
        return view('Admin.action.completed',compact('assignees_completed','task_group'))
         ->with('i', (request()->input('page', 1) - 1) * 20);
    }
    
    
    
    public function index() {
        return view('Admin.action.index');
    }

    /**
     * Whether the request asks to show future-dated Followup actions (default off).
     */
    private function wantsIncludeScheduledFollowups(?Request $request = null): bool
    {
        $request = $request ?? request();
        $value = $request->input('include_scheduled_followups');

        return $request->boolean('include_scheduled_followups')
            || $value === 1
            || $value === '1'
            || $value === true
            || $value === 'true'
            || $value === 'on';
    }

    /**
     * Followup actions appear on the Action page only on/after their assign date.
     * All other action types are unchanged.
     * Pass $includeScheduledFollowups = true to list future-dated Followups too.
     */
    private function applyFollowupAssignDateVisibilityFilter($query, bool $includeScheduledFollowups = false)
    {
        if ($includeScheduledFollowups) {
            return $query;
        }

        $today = Carbon::today(config('app.timezone'));

        return $query->where(function ($q) use ($today) {
            $q->where('task_group', '!=', 'Followup')
                ->orWhereNull('task_group')
                ->orWhereNull('action_assign_date')
                ->orWhereDate('action_assign_date', '<=', $today);
        });
    }

    /**
     * True when a Followup row is scheduled for a day after today (not yet in default queue).
     */
    private function isFutureScheduledFollowup($note): bool
    {
        if (!$note || strcasecmp((string) ($note->task_group ?? ''), 'Followup') !== 0) {
            return false;
        }
        if (empty($note->action_assign_date)) {
            return false;
        }
        try {
            return Carbon::parse($note->action_assign_date)
                ->timezone(config('app.timezone'))
                ->startOfDay()
                ->gt(Carbon::today(config('app.timezone')));
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getList(Request $request) {
        if ($request->ajax()) {
           $includeScheduledFollowups = $this->wantsIncludeScheduledFollowups($request);
           if(\Auth::user()->role == 1)
            { //admin role
            	$data = $this->applyFollowupAssignDateVisibilityFilter(
                    \App\Models\Note::with(['noteUser','noteClient','notePartner','assigned_user'])
                    ->where('status','<>','1')
                    //->where('type','client')
                    ->whereIn('type', ['client', 'partner']) // Include 'client' or 'partner'
                    ->where('is_action',1),
                    $includeScheduledFollowups
                )->orderByRaw('created_at DESC NULLS LAST')->get();
            }
            else
            { //role is not admin
            	$data = $this->applyFollowupAssignDateVisibilityFilter(
                    \App\Models\Note::with(['noteUser','noteClient','notePartner','assigned_user'])
                    ->where('status','<>','1')
                    ->where('assigned_to',\Auth::user()->id)
                    //->where('type','client')
                    ->whereIn('type', ['client', 'partner']) // Include 'client' or 'partner'
                    ->where('is_action',1),
                    $includeScheduledFollowups
                )->orderByRaw('created_at DESC NULLS LAST')->get();
            }
            //dd($data);
            return Datatables::of($data)
            ->addIndexColumn()
            ->addColumn('done_task', function($data) {
                $done_task = '<input type="radio" class="complete_task" data-bs-toggle="tooltip" title="Mark Complete!" data-id="'.$data->id.'">';
                return $done_task;
            })
            ->addColumn('assigner_name', function($data) {
                if($data->noteUser){
                    $full_name = $data->noteUser->first_name.' '.$data->noteUser->last_name;
                } else {
                    $full_name = 'N/P';
                }
                return Utf8Helper::sanitize($full_name);
            })
            ->addColumn('client_reference', function($data) {
                $taskGroup = strtolower(trim((string) ($data->task_group ?? '')));
                $isPersonalTask = in_array($taskGroup, ['personal task', 'personal action'], true);

                if ($data->type === 'client' && $isPersonalTask && empty($data->client_id)) {
                    return '<span class="badge badge-info bg-info">Personal Action</span>';
                }

                $user_name = 'N/P';

                if($data->type == 'client'){
                    if($data->noteClient){
                        $user_name = Utf8Helper::sanitize($data->noteClient->first_name.' '.$data->noteClient->last_name);
                        $user_name .= "<br>";
                        $user_name .= "\n";

                        $client_encoded_id = base64_encode(convert_uuencode(@$data->client_id)) ;
                        $user_name .= '<a href="'.route('clients.detail', $client_encoded_id).'" target="_blank" >'.Utf8Helper::sanitize($data->noteClient->client_id).'</a>';
                    }
                } else if($data->type == 'partner'){
                    $partnerInfo = \App\Models\Partner::select('partner_name')->where('id',$data->client_id)->first();
                    if($partnerInfo){
                        $partnerName = Utf8Helper::sanitize($partnerInfo->partner_name);
                        $user_name = $partnerName;
                        $user_name .= "<br>";
                        $user_name .= "\n";

                        $partner_encoded_id = base64_encode(convert_uuencode(@$data->client_id)) ;
                        $user_name .= '<a href="'.route('partners.detail', $partner_encoded_id).'" target="_blank" >'.$partnerName.'</a>';
                    }
                }
                return $user_name;
            })

            ->addColumn('assign_date', function($data) {
                if($data->action_assign_date){
                    $assign_date =  date('d/m/Y',strtotime($data->action_assign_date)) ;
                } else {
                    $assign_date = 'N/P';
                }
                if ($this->isFutureScheduledFollowup($data)) {
                    $assign_date .= ' <span class="badge bg-info text-dark">Scheduled</span>';
                }
                return $assign_date;
            })
            ->addColumn('task_group', function($data) {
                if($data->task_group){
                    $task_group = Utf8Helper::sanitize($data->task_group);
                } else {
                    $task_group = 'N/P';
                }
                return $task_group;
            })
              
            ->addColumn('note_description', function($data) {
                $rawDescription = $data->description ?? '';

                if (strcasecmp((string) $data->task_group, 'Followup') === 0) {
                    $parsed = FollowupController::parseScheduledFollowupNoteHtml((string) $rawDescription);
                    $detailsPlain = trim($parsed['details_plain'] ?? '');
                    if ($detailsPlain !== '') {
                        $rawDescription = $detailsPlain;
                    }
                }

                $cleanDescription = Utf8Helper::sanitize(trim(strip_tags($rawDescription)));

                if ($cleanDescription === '') {
                    return 'N/P';
                }

                $description = Utf8Helper::sanitizeForHtml($cleanDescription);

                if (mb_strlen($description) > 190) {
                    $full_description = $description;
                    $final_desc = mb_substr($description, 0, 190);
                    $final_desc .= '<button type="button" class="btn btn-link btn_readmore" data-bs-toggle="popover" title="" data-content="'.$full_description.'">Read more</button>';
                } else {
                    $final_desc = $description;
                }

                return $final_desc;
            })
            ->addColumn('action', function($list){
                $safeDescription = Utf8Helper::sanitizeForHtmlAttribute($list->description ?? '');
                $safeTaskGroup = Utf8Helper::sanitizeForHtmlAttribute($list->task_group ?? '');
                $actionBtn = '<div class="action-btn-group">';

                if($list->task_group != 'Personal Task')
                {
                    if($list->action_assign_date != ""){
                        $current_date1 = $list->action_assign_date;
                    } else{
                        $current_date1 = date('Y-m-d');
                    }

                    $content1 =
                    '<div id=&quot;popover-content&quot;>
                        <h4 class=&quot;text-center&quot;>Update Task</h4>
                        <div class=&quot;clearfix&quot;></div>

                        <div class=&quot;box-header with-border&quot;>
                            <div class=&quot;form-group row&quot; style=&quot;margin-bottom:12px&quot; >
                                <label for=&quot;inputSub3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>Select Assignee</label>
                                <div class=&quot;col-sm-9&quot;>
                                    <select class=&quot;assignee-tomselect tomselect form-control selec_reg&quot; id=&quot;rem_cat&quot; name=&quot;rem_cat&quot; onchange=&quot;&quot;>
                                        <option value=&quot;&quot;>Select</option>';
                                        


                    $content1 .= '</select></div>
                                <div class=&quot;clearfix&quot;></div>
                            </div>
                        </div>

                        <div class=&quot;box-header with-border&quot;>
                            <div class=&quot;form-group row&quot; style=&quot;margin-bottom:12px&quot; >
                                <label for=&quot;inputEmail3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>Note</label>
                                <div class=&quot;col-sm-9&quot;>
                                    <textarea id=&quot;assignnote&quot; class=&quot;form-control tinymce-simple f13&quot; placeholder=&quot;Enter an note....&quot; type=&quot;text&quot;></textarea>
                                </div>
                                <div class=&quot;clearfix&quot;></div>
                            </div>
                        </div>

                        <div class=&quot;box-header with-border&quot;>
                            <div class=&quot;form-group row&quot; style=&quot;margin-bottom:12px&quot; >
                                <label for=&quot;inputEmail3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>DateTime</label>
                                <div class=&quot;col-sm-9&quot;>
                                    <input type=&quot;text&quot; class=&quot;form-control f13 flatpickr-date&quot; placeholder=&quot;yyyy-mm-dd&quot; id=&quot;popoverdatetime&quot; value=&quot;'.$current_date1.'&quot; name=&quot;popoverdate&quot; autocomplete=&quot;off&quot;>
                                </div>
                                <div class=&quot;clearfix&quot;></div>
                            </div>
                        </div>

                        <div class=&quot;form-group row&quot; style=&quot;margin-bottom:12px&quot; >
                            <label for=&quot;inputSub3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>Group</label>
                            <div class=&quot;col-sm-9&quot;>
                                <select class=&quot;assignee-tomselect tomselect form-control selec_reg&quot; id=&quot;task_group&quot; name=&quot;task_group&quot;>
                                    <option value=&quot;&quot;>Select</option>
                                    <option value=&quot;Call&quot;>Call</option>
                                    <option value=&quot;Checklist&quot;>Checklist</option>
                                    <option value=&quot;Review&quot;>Review</option>
                                    <option value=&quot;Query&quot;>Query</option>
                                    <option value=&quot;Urgent&quot;>Urgent</option>
                                </select>
                            </div>
                            <div class=&quot;clearfix&quot;></div>
                        </div>

                        <input id=&quot;assign_note_id&quot;  type=&quot;hidden&quot; value=&quot;&quot;>

                        <input id=&quot;assign_client_id&quot;  type=&quot;hidden&quot; value=&quot;'.base64_encode(convert_uuencode(@$list->client_id)).'&quot;>

                        <div class=&quot;box-footer&quot; style=&quot;padding:10px 0&quot;>
                            <div class=&quot;row&quot;>
                                <input type=&quot;hidden&quot; value=&quot;&quot; id=&quot;popoverrealdate&quot; name=&quot;popoverrealdate&quot; />
                            </div>
                            <div class=&quot;row text-center&quot;>
                                <div class=&quot;col-md-12 text-center&quot;>
                                <button  class=&quot;btn btn-info&quot; id=&quot;updateTask&quot;>Update Task</button>
                                </div>
                            </div>
                        </div>
                    </div>';

                    $actionBtn .= '<button type="button" data-assignedto="'.$list->assigned_to.'" data-description="'.$safeDescription.'" data-taskid="'.$list->id.'" data-taskgroupid="'.$safeTaskGroup.'" data-followupdate="'.$list->action_assign_date.'" class="btn btn-primary btn-sm action-icon-btn update_task" data-bs-container="body" data-role="popover" title="" data-bs-placement="left" data-html="true" data-content="'.$content1.'">' . \App\Helpers\IconHelper::render('edit') . '</button>';
                }

                $actionBtn .= '<button type="button" class="btn btn-danger btn-sm action-icon-btn deleteNote" data-remote="'. route('action.destroy', $list->id) .'">' . \App\Helpers\IconHelper::render('trash') . '</button>';



                if($list->task_group != 'Personal Task')
                {
                    $content2 =
                    '<div id=&quot;popover-content&quot;>
                        <h4 class=&quot;text-center&quot;>Re-Assign User</h4>
                        <div class=&quot;clearfix&quot;></div>
                        <div class=&quot;box-header with-border&quot;>
                            <div class=&quot;form-group row&quot; style=&quot;margin-bottom:12px&quot; >
                                <label for=&quot;inputSub3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>Select Assignee</label>
                                <div class=&quot;col-sm-9&quot;>
                                    <select class=&quot;assignee-tomselect tomselect form-control selec_reg&quot; id=&quot;rem_cat&quot; name=&quot;rem_cat&quot; onchange=&quot;&quot;>
                                        <option value=&quot;&quot; >Select</option>';
                            $content2 .= '</select>
                                </div>
                                <div class=&quot;clearfix&quot;></div>
                            </div>
                        </div>


                        <div class=&quot;box-header with-border&quot;>
                            <div class=&quot;form-group row&quot; style=&quot;margin-bottom:12px&quot; >
                                <label for=&quot;inputEmail3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>Note</label>
                                <div class=&quot;col-sm-9&quot;>
                                    <textarea id=&quot;assignnote&quot; class=&quot;form-control tinymce-simple f13&quot; placeholder=&quot;Enter an note....&quot; type=&quot;text&quot;></textarea>
                                </div>
                                <div class=&quot;clearfix&quot;></div>
                            </div>
                        </div>

                        <div class=&quot;box-header with-border&quot;>
                            <div class=&quot;form-group row&quot; style=&quot;margin-bottom:12px&quot; >
                                <label for=&quot;inputEmail3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>DateTime</label>
                                <div class=&quot;col-sm-9&quot;>
                                    <input type=&quot;text&quot; class=&quot;form-control f13 flatpickr-date&quot; placeholder=&quot;yyyy-mm-dd&quot; id=&quot;popoverdatetime&quot; value=&quot;'.$current_date1.'&quot; name=&quot;popoverdate&quot; autocomplete=&quot;off&quot;>
                                </div>
                                <div class=&quot;clearfix&quot;></div>
                            </div>
                        </div>

                        <div class=&quot;form-group row&quot; style=&quot;margin-bottom:12px&quot; >
                            <label for=&quot;inputSub3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>Group</label>
                            <div class=&quot;col-sm-9&quot;>
                                <select class=&quot;assignee-tomselect tomselect form-control selec_reg&quot; id=&quot;task_group&quot; name=&quot;task_group&quot;>
                                    <option value=&quot;&quot;>Select</option>
                                    <option value=&quot;Call&quot;>Call</option>
                                    <option value=&quot;Checklist&quot;>Checklist</option>
                                    <option value=&quot;Review&quot;>Review</option>
                                    <option value=&quot;Query&quot;>Query</option>
                                    <option value=&quot;Urgent&quot;>Urgent</option>
                                </select>
                            </div>
                            <div class=&quot;clearfix&quot;></div>
                        </div>

                        <input id=&quot;assign_note_id&quot;  type=&quot;hidden&quot; value=&quot;&quot;>

                        <input id=&quot;assign_client_id&quot;  type=&quot;hidden&quot; value=&quot;'.base64_encode(convert_uuencode(@$list->client_id)).'&quot;>

                        <div class=&quot;box-footer&quot; style=&quot;padding:10px 0&quot;>
                            <div class=&quot;row&quot;>
                                <input type=&quot;hidden&quot; value=&quot;&quot; id=&quot;popoverrealdate&quot; name=&quot;popoverrealdate&quot; />
                            </div>
                            <div class=&quot;row text-center&quot;>
                                <div class=&quot;col-md-12 text-center&quot;>
                                <button  class=&quot;btn btn-info&quot; id=&quot;assignUser&quot;>Assign Staff</button>
                                </div>
                            </div>
                        </div>
                    </div>';

                    $actionBtn .= '<button type="button" data-assignedto="'.$list->assigned_to.'" data-description="'.$safeDescription.'" data-taskid="'.$list->id.'" data-taskgroupid="'.$safeTaskGroup.'" data-followupdate="'.$list->action_assign_date.'" title="" class="btn btn-primary btn-sm action-icon-btn reassign_task" data-bs-container="body" data-role="popover" data-bs-placement="auto" data-html="true" data-content="'.$content2.'">' . \App\Helpers\IconHelper::render('tasks') . '</button>';
                }
                $actionBtn .= '</div>';
                return $actionBtn;
            })
            ->rawColumns(['done_task','client_reference','assign_date','note_description','action'])
            ->make(true);
        }
    }


    /**
     * Soft-delete an action created by me (is_action = 0).
     *
     * @param  int|string  $note_id
     * @return \Illuminate\Http\Response
     */
    public function destroyByMe($note_id)
    {
        $appointment = Note::find($note_id);

        if (!$appointment) {
            return redirect()->route('action.assigned_by_me')
                ->with('error', 'Activity not found');
        }

        $appointment->is_action = 0;
        if( $appointment->save() ){
            $objs = new ActivitiesLog;
            $objs->client_id = $appointment->client_id;
            $objs->created_by = Auth::user()->id;

            $assign_user = \App\Models\Staff::find($appointment->assigned_to);
            if($assign_user){
                $assign_full_name = $assign_user->first_name." ".$assign_user->last_name;
                $objs->subject = 'deleted activity for '.@$assign_full_name;
            } else {
                $objs->subject = 'deleted activity ';
            }

            $objs->description = '<p>'.$appointment->description.'</p>';
            if(Auth::user()->id != @$appointment->assigned_to){
                $objs->use_for = @$appointment->assigned_to;
            } else {
                $objs->use_for = null; // Use null instead of empty string for PostgreSQL
            }
            $objs->followup_date = @$appointment->action_assign_date;
            $objs->task_group = @$appointment->task_group;
            $objs->task_status = 0; // Required NOT NULL field for PostgreSQL (0 = activity, 1 = task)
            $objs->pin = 0; // Required NOT NULL field for PostgreSQL (0 = not pinned, 1 = pinned)
            $objs->save();
            //echo json_encode(array('success' => true, 'message' => 'Activity deleted successfully', 'clientID' => $appointment->client_id));
            //exit;
            return redirect()->route('action.assigned_by_me')->with('success','Activity deleted successfully');
        }

        return redirect()->route('action.assigned_by_me')
            ->with('error', 'Failed to delete activity');
    }


    public function destroyToMe($note_id)
    {
        $appointment = Note::find($note_id);

        if (!$appointment) {
            return redirect()->route('action.assigned_to_me')
                ->with('error', 'Action not found');
        }

        $appointment->is_action = 0;
        $appointment->save();

        return redirect()->route('action.assigned_to_me')
        ->with('success','Action deleted successfully');
    }


   //incomplete activity remove
    public function destroy($note_id)
    {
        $appointment = Note::find($note_id);
        
        if(!$appointment) {
            return response()->json(['success' => false, 'message' => 'Note not found'], 404);
        }
        
        $appointment->is_action = 0;
        if( $appointment->save() ){
            $objs = new ActivitiesLog;
            $objs->client_id = $appointment->client_id;
            $objs->created_by = Auth::user()->id;

            $assign_user = \App\Models\Staff::find($appointment->assigned_to);
            if($assign_user){
                $assign_full_name = $assign_user->first_name." ".$assign_user->last_name;
                $objs->subject = 'deleted activity for '.@$assign_full_name;
            } else {
                $objs->subject = 'deleted activity ';
            }

            $objs->description = '<p>'.$appointment->description.'</p>';
            if(Auth::user()->id != @$appointment->assigned_to){
                $objs->use_for = @$appointment->assigned_to;
            } else {
                $objs->use_for = null; // Use null instead of empty string for PostgreSQL
            }
            $objs->followup_date = @$appointment->action_assign_date;
            $objs->task_group = @$appointment->task_group;
            $objs->task_status = 0; // Required NOT NULL field for PostgreSQL (0 = activity, 1 = task)
            $objs->pin = 0; // Required NOT NULL field for PostgreSQL (0 = not pinned, 1 = pinned)
            $objs->save();
            
            return response()->json(['success' => true, 'message' => 'Activity deleted successfully', 'clientID' => $appointment->client_id]);
        }
        
        return response()->json(['success' => false, 'message' => 'Failed to delete activity'], 500);
    }

    //complete activity remove
    public function destroyCompleted($note_id)
    {
        $appointment = Note::find($note_id);

        if (!$appointment) {
            return redirect()->route('action.completed')
                ->with('error', 'Activity not found');
        }

        $appointment->is_action = 0;
        if( $appointment->save() ){
            $objs = new ActivitiesLog;
            $objs->client_id = $appointment->client_id;
            $objs->created_by = Auth::user()->id;

            $assign_user = \App\Models\Staff::find($appointment->assigned_to);
            if($assign_user){
                $assign_full_name = $assign_user->first_name." ".$assign_user->last_name;
                $objs->subject = 'deleted completed activity for '.@$assign_full_name;
            } else {
                $objs->subject = 'deleted completed activity ';
            }

            $objs->description = '<p>'.$appointment->description.'</p>';
            if(Auth::user()->id != @$appointment->assigned_to){
                $objs->use_for = @$appointment->assigned_to;
            } else {
                $objs->use_for = null; // Use null instead of empty string for PostgreSQL
            }
            $objs->followup_date = @$appointment->action_assign_date;
            $objs->task_group = @$appointment->task_group;
            $objs->task_status = 0; // Required NOT NULL field for PostgreSQL (0 = activity, 1 = task)
            $objs->pin = 0; // Required NOT NULL field for PostgreSQL (0 = not pinned, 1 = pinned)
            $objs->save();
            //echo json_encode(array('success' => true, 'message' => 'Activity deleted successfully', 'clientID' => $appointment->client_id));
            //exit;
            return redirect()->route('action.completed')->with('success','Activity deleted successfully');
        }
        return redirect()->route('action.completed')
            ->with('error', 'Failed to delete activity');
    }


    // getAssigneeList moved to StaffController::getAssigneeList

}


