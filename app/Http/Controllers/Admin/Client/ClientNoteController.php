<?php

namespace App\Http\Controllers\Admin\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActivitiesLog;
use Illuminate\Support\Facades\DB;
use Auth;

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
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function createnote(Request $request){

		if(isset($request->noteid) && $request->noteid != ''){
			$obj = \App\Models\Note::find($request->noteid);
		}else{
			$obj = new \App\Models\Note;
		}

		$obj->client_id = $request->client_id;
		$obj->user_id = Auth::user()->id;
		$obj->title = $request->title;
		$obj->description = $request->description;
		$obj->mail_id = $request->mailid;
		$obj->type = $request->vtype;
      	
  		if( isset($request->mobileNumber) && $request->mobileNumber != ""){
        	$obj->mobile_number = $request->mobileNumber; // Add this line
    	}
  
		$obj->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
		$obj->is_action = 0; // Required NOT NULL field (0 = not a followup, 1 = followup)
		$obj->status = 0; // Required NOT NULL field (0 = active/open, 1 = closed/completed)
		$saved = $obj->save();
		if($saved){
			if($request->vtype == 'client'){
				$subject = 'added a note';
				if(isset($request->noteid) && $request->noteid != ''){
				$subject = 'updated a note';
				}
				$objs = new ActivitiesLog;
				$objs->client_id = $request->client_id;
				$objs->created_by = Auth::user()->id;
              
				if( isset($request->mobileNumber) && $request->mobileNumber != ""){
                    $objs->description = '<span class="text-semi-bold">'.$request->title.'</span><p>'.$request->description.'</p><p>'.$request->mobileNumber.'</p>';
                } else {
                    $objs->description = '<span class="text-semi-bold">'.$request->title.'</span><p>'.$request->description.'</p>';
                }
              
				$objs->subject = $subject;
				$objs->task_status = 0; // Required NOT NULL field (0 = activity, 1 = task)
				$objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
				$objs->save();
			}
			$response['status'] 	= 	true;
			if(isset($request->noteid) && $request->noteid != ''){
			$response['message']	=	'You\'ve successfully updated Note';
			}else{
				$response['message']	=	'You\'ve successfully added Note';
			}
		}else{
		$response['status'] 	= 	false;
		$response['message']	=	'Please try again';
		}

	echo json_encode($response);
	}

	public function getnotedetail(Request $request){
		$note_id = $request->note_id;
		if(\App\Models\Note::where('id',$note_id)->exists()){
			$data = \App\Models\Note::select('title','description')->where('id',$note_id)->first();
			$response['status'] 	= 	true;
			$response['data']	=	$data;
		}else{
			$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
		}
		echo json_encode($response);
	}

	public function viewnotedetail(Request $request){
		$note_id = $request->note_id;
		if(\App\Models\Note::where('id',$note_id)->exists()){
			$data = \App\Models\Note::select('title','description','user_id','updated_at')->where('id',$note_id)->first();
			$admin = \App\Models\Staff::find($data->user_id);
			$s = substr(@$admin->first_name, 0, 1);
			$data->admin = $s;
			$response['status'] 	= 	true;
			$response['data']	=	$data;
		}else{
			$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
		}
		echo json_encode($response);
	}

	public function viewapplicationnote(Request $request){
		$note_id = $request->note_id;
		if(\App\Models\ApplicationActivitiesLog::where('type','note')->where('id',$note_id)->exists()){
			$data = \App\Models\ApplicationActivitiesLog::select('title','description','user_id','updated_at')->where('type','note')->where('id',$note_id)->first();
			$admin = \App\Models\Staff::find($data->user_id);
			$s = substr(@$admin->first_name, 0, 1);
			$data->admin = $s;
			$response['status'] 	= 	true;
			$response['data']	=	$data;
		}else{
			$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
		}
		echo json_encode($response);
	}

	public function getnotes(Request $request){
		$client_id = $request->clientid;
		$type = $request->type;

		$notelist = \App\Models\Note::where('client_id', $client_id)
			->whereNull('assigned_to')
			->whereNull('task_group')
			->where('type', $type)
			->orderby('pin', 'DESC')
			->orderByRaw('created_at DESC NULLS LAST')
			->get();

		return view('Admin.partials.notes-list', compact('notelist'))->render();
	}

	public function deletenote(Request $request){
		$note_id = $request->note_id;
		if(\App\Models\Note::where('id',$note_id)->exists()){
			$data = \App\Models\Note::select('client_id','title','description','type')->where('id',$note_id)->first();
			$res = DB::table('notes')->where('id', @$note_id)->delete();
			if($res){
				if($data && $data->type == 'client'){
				$subject = 'deleted a note';

				$objs = new ActivitiesLog;
				$objs->client_id = $data->client_id;
				$objs->created_by = Auth::user()->id;
				$objs->description = '<span class="text-semi-bold">'.$data->title.'</span><p>'.$data->description.'</p>';
				$objs->subject = $subject;
				$objs->task_status = 0; // Required NOT NULL field (0 = activity, 1 = task)
				$objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
				$objs->save();
				}
			$response['status'] 	= 	true;
			$response['data']	=	$data;
			}else{
				$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
			}
		}else{
			$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
		}
		echo json_encode($response);
	}

	public function pinnote(Request $request){
		$requestData = $request->all();

		if(\App\Models\Note::where('id',$requestData['note_id'])->exists()){
			$note = \App\Models\Note::where('id',$requestData['note_id'])->first();
			if($note->pin == 0){
				$obj = \App\Models\Note::find($note->id);
				$obj->pin = 1;
				$saved = $obj->save();
			}else{
				$obj = \App\Models\Note::find($note->id);
				$obj->pin = 0;
				$saved = $obj->save();
			}
			$response['status'] 				= 	true;
			$response['message']			=	'Fee Option added successfully';
		}else{
			$response['status'] 	= 	false;
			$response['message']	=	'Record not found';
		}
		echo json_encode($response);
	}
}
