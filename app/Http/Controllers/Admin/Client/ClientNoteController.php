<?php

namespace App\Http\Controllers\Admin\Client;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\ActivitiesLog;
use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationActivitiesLog;
use App\Models\Note;
use App\Models\Staff;
use App\Traits\ClientAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Client notes management
 *
 * Methods to move from ClientsController:
 * - createnote
 * - getnotedetail
 * - viewnotedetail
 * - viewapplicationnote
 * - getnotes
 * - deletenote
 * - pinnote
 */
class ClientNoteController extends Controller
{
    use ClientAuthorization;

    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * JSON helpers: null when client missing or staff not allowed.
     */
    private function resolveAccessibleNoteClient($clientId, bool $forEdit = false): ?Admin
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

    private function unauthorizedJsonResponse(): void
    {
        echo json_encode([
            'status' => false,
            'message' => 'Unauthorized',
        ]);
    }

    public function createnote(Request $request)
    {
        $targetClientId = $request->client_id;
        if (! $this->resolveAccessibleNoteClient($targetClientId, true)) {
            $this->unauthorizedJsonResponse();

            return;
        }

        if (isset($request->noteid) && $request->noteid != '') {
            $obj = Note::find($request->noteid);
            if (! $obj) {
                echo json_encode(['status' => false, 'message' => 'Please try again']);

                return;
            }
            // Existing note must belong to a client this staff can edit (block re-point IDOR).
            if (! $this->resolveAccessibleNoteClient($obj->client_id ?? null, true)) {
                $this->unauthorizedJsonResponse();

                return;
            }
        } else {
            $obj = new Note;
        }

        $obj->client_id = $request->client_id;
        $obj->user_id = Auth::user()->id;
        $obj->title = $request->title;
        $obj->description = $request->description;
        $obj->mail_id = $request->mailid;
        $obj->type = $request->vtype;

        if (isset($request->mobileNumber) && $request->mobileNumber != '') {
            $obj->mobile_number = $request->mobileNumber;
        }

        $obj->pin = 0;
        $obj->is_action = 0;
        $obj->status = 0;
        $saved = $obj->save();
        if ($saved) {
            if ($request->vtype == 'client') {
                $subject = 'added a note';
                if (isset($request->noteid) && $request->noteid != '') {
                    $subject = 'updated a note';
                }
                $objs = new ActivitiesLog;
                $objs->client_id = $request->client_id;
                $objs->created_by = Auth::user()->id;

                if (isset($request->mobileNumber) && $request->mobileNumber != '') {
                    $objs->description = '<span class="text-semi-bold">'.$request->title.'</span><p>'.$request->description.'</p><p>'.$request->mobileNumber.'</p>';
                } else {
                    $objs->description = '<span class="text-semi-bold">'.$request->title.'</span><p>'.$request->description.'</p>';
                }

                $objs->subject = $subject;
                $objs->task_status = 0;
                $objs->pin = 0;
                $objs->save();
            }
            $response['status'] = true;
            if (isset($request->noteid) && $request->noteid != '') {
                $response['message'] = 'You\'ve successfully updated Note';
            } else {
                $response['message'] = 'You\'ve successfully added Note';
            }
        } else {
            $response['status'] = false;
            $response['message'] = 'Please try again';
        }

        echo json_encode($response);
    }

    public function getnotedetail(Request $request)
    {
        $note_id = $request->note_id;
        if (Note::where('id', $note_id)->exists()) {
            $data = Note::select('title', 'description', 'client_id')->where('id', $note_id)->first();
            if (! $this->resolveAccessibleNoteClient($data->client_id ?? null, false)) {
                $this->unauthorizedJsonResponse();

                return;
            }
            unset($data->client_id);
            $response['status'] = true;
            $response['data'] = $data;
        } else {
            $response['status'] = false;
            $response['message'] = 'Please try again';
        }
        echo json_encode($response);
    }

    public function viewnotedetail(Request $request)
    {
        $note_id = $request->note_id;
        if (Note::where('id', $note_id)->exists()) {
            $data = Note::select('title', 'description', 'user_id', 'updated_at', 'client_id')->where('id', $note_id)->first();
            if (! $this->resolveAccessibleNoteClient($data->client_id ?? null, false)) {
                $this->unauthorizedJsonResponse();

                return;
            }
            unset($data->client_id);
            $admin = Staff::find($data->user_id);
            $s = substr(@$admin->first_name, 0, 1);
            $data->admin = $s;
            $data->description = Helper::normalizeActivityDescriptionHtml((string) $data->description, true);
            $response['status'] = true;
            $response['data'] = $data;
        } else {
            $response['status'] = false;
            $response['message'] = 'Please try again';
        }
        echo json_encode($response);
    }

    public function viewapplicationnote(Request $request)
    {
        $note_id = $request->note_id;
        if (ApplicationActivitiesLog::where('type', 'note')->where('id', $note_id)->exists()) {
            $log = ApplicationActivitiesLog::where('type', 'note')->where('id', $note_id)->first();
            $app = $log && $log->app_id ? Application::find($log->app_id) : null;
            if (! $app || ! $this->resolveAccessibleNoteClient($app->client_id ?? null, false)) {
                $this->unauthorizedJsonResponse();

                return;
            }
            $data = ApplicationActivitiesLog::select('title', 'description', 'user_id', 'updated_at')
                ->where('type', 'note')
                ->where('id', $note_id)
                ->first();
            $admin = Staff::find($data->user_id);
            $s = substr(@$admin->first_name, 0, 1);
            $data->admin = $s;
            $data->description = Helper::normalizeActivityDescriptionHtml((string) $data->description, true);
            $response['status'] = true;
            $response['data'] = $data;
        } else {
            $response['status'] = false;
            $response['message'] = 'Please try again';
        }
        echo json_encode($response);
    }

    public function getnotes(Request $request)
    {
        $client_id = $request->clientid;
        $type = $request->type;

        if (! $this->resolveAccessibleNoteClient($client_id, false)) {
            $notelist = collect();

            return view('Admin.partials.notes-list', compact('notelist'))->render();
        }

        $notelist = Note::where('client_id', $client_id)
            ->whereNull('assigned_to')
            ->whereNull('task_group')
            ->where('type', $type)
            ->orderby('pin', 'DESC')
            ->orderByRaw('created_at DESC NULLS LAST')
            ->get();

        return view('Admin.partials.notes-list', compact('notelist'))->render();
    }

    public function deletenote(Request $request)
    {
        $note_id = $request->note_id;
        if (Note::where('id', $note_id)->exists()) {
            $data = Note::select('client_id', 'title', 'description', 'type')->where('id', $note_id)->first();
            if (! $this->resolveAccessibleNoteClient($data->client_id ?? null, true)) {
                $this->unauthorizedJsonResponse();

                return;
            }
            $res = DB::table('notes')->where('id', @$note_id)->delete();
            if ($res) {
                if ($data && $data->type == 'client') {
                    $subject = 'deleted a note';

                    $objs = new ActivitiesLog;
                    $objs->client_id = $data->client_id;
                    $objs->created_by = Auth::user()->id;
                    $objs->description = '<span class="text-semi-bold">'.$data->title.'</span><p>'.$data->description.'</p>';
                    $objs->subject = $subject;
                    $objs->task_status = 0;
                    $objs->pin = 0;
                    $objs->save();
                }
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

    public function pinnote(Request $request)
    {
        $requestData = $request->all();

        if (Note::where('id', $requestData['note_id'] ?? null)->exists()) {
            $note = Note::where('id', $requestData['note_id'])->first();
            if (! $this->resolveAccessibleNoteClient($note->client_id ?? null, true)) {
                $this->unauthorizedJsonResponse();

                return;
            }
            if ($note->pin == 0) {
                $obj = Note::find($note->id);
                $obj->pin = 1;
                $obj->save();
            } else {
                $obj = Note::find($note->id);
                $obj->pin = 0;
                $obj->save();
            }
            $response['status'] = true;
            $response['message'] = 'Fee Option added successfully';
        } else {
            $response['status'] = false;
            $response['message'] = 'Record not found';
        }
        echo json_encode($response);
    }
}
