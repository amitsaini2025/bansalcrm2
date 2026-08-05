<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

use App\Models\Admin;
use App\Models\Application;
use App\Models\ApplicationFeeOptionType;
use App\Models\ApplicationFeeOption;
   use Barryvdh\DomPDF\Facade\Pdf as PDF; 
use Auth;
use Config;
use App\Models\Partner;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ApplicationsController extends Controller
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
     * All Vendors.
     *
     * @return \Illuminate\Http\Response
     */
	 public function index(Request $request)
	 {
		//check authorization start
        /* if($check)
        {
            return Redirect::to('/admin/dashboard')->with('error',config('constants.unauthorized'));
        } */
		//check authorization end
	    //$allstages = Application::select('stage')->groupBy('stage')->get();
		//$query 		= Application::where('id', '!=', '')->with(['application_assignee']);
        $allstages = Application::select('stage')->where('status', '!=', 2)->groupBy('stage')->get();
        $allpartners = Partner::select('partner_name','id')->where('status', '=', 0)->get();

		$query 		= Application::query()->where('status', '!=', 2)->with(['application_assignee']);

        if ($request->has('partner'))
		{
			$partner 		= 	$request->input('partner');
			if(trim($partner) != '')
			{
				$query->where('partner_id', '=', $partner);
			}
		}
		if ($request->has('assignee'))
		{
			$assignee 		= 	$request->input('assignee');
			if(trim($assignee) != '')
			{
				$query->where('user_id', '=', $assignee);
			}
		}
		 if ($request->has('stage'))
		{
			$stage 		= 	$request->input('stage');
			if(trim($stage) != '')
			{
				$query->where('stage', '=', $stage);
			}
		}

        if ($request->has('status'))
		{
			$status 		= 	$request->input('status');
			if(trim($status) != '')
			{
				$query->where('status', '=', $status);
			}
		}
		$totalData 	= $query->count();
		$lists		= $query->sortable(['id' => 'desc'])->paginate(10);

		return view('Admin.applications.index', compact(['lists', 'totalData','allstages','allpartners']));

		//return view('Admin.applications.index');
	}
	
	public function create(Request $request)
	{
		//check authorization end
		//return view('Admin.users.create',compact(['usertype']));	
		
		//return view('Admin.clients.create');	
	}
	 
	 
	public function getapplicationdetail(Request $request){
		$fetchData = Application::find($request->id);
		if(!$fetchData){
			return response('<div class="alert alert-danger mb-0">Application not found.</div>');
		}
		$fetchedData = ($fetchData && $fetchData->client_id) ? Admin::find($fetchData->client_id) : null;
		$assignees = \App\Models\Staff::where('status', 1)->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);
		return view('Admin.clients.applicationdetail', compact(['fetchData', 'fetchedData', 'assignees']));
	}
	
	public function completestage(Request $request){
		$fetchData = Application::find($request->id);
		if(!$fetchData){
			echo json_encode([
				'status' => false,
				'message' => 'Application not found.',
			]);
			return;
		}
		$fetchData->status = 1;
		
		$saved = $fetchData->save();
		if($saved){
			$response['status'] 	= 	true;
			$response['stage']	=	$fetchData->stage;
			$response['width']	=	100;
			$response['message']	=	'Application has been successfully completed.';
		}else{
			$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
		}
		
		echo json_encode($response);
	}
	public function updatestage(Request $request){
		$fetchData = Application::find($request->id);
		if(!$fetchData){
			echo json_encode([
				'status' => false,
				'message' => 'Application not found.',
			]);
			return;
		}

		$workflowstagecount = \App\Models\WorkflowStage::where('w_id', $fetchData->workflow)->count();
		$widthcount = 0;
		if($workflowstagecount !== 0){
			$s = 100 / $workflowstagecount;
			$widthcount = round($s);
		}
		$workflowstage = \App\Models\WorkflowStage::where('name', 'like', '%'.$fetchData->stage.'%')->where('w_id', $fetchData->workflow)->first();
		if(!$workflowstage){
			echo json_encode([
				'status' => false,
				'message' => 'Current workflow stage not found.',
			]);
			return;
		}

		// No next stage when already on the last workflow step (same pattern as updatebackstage).
		$nextid = \App\Models\WorkflowStage::where('id', '>', $workflowstage->id)->where('w_id', $fetchData->workflow)->orderBy('id','asc')->first();
		if(!$nextid){
			echo json_encode([
				'status' => false,
				'message' => 'Application is already at the last stage.',
			]);
			return;
		}
		
		$fetchData->stage = $nextid->name;
		$comments = 'moved the stage from  <b>'.$workflowstage->name.'</b> to <b>'.$nextid->name.'</b>';
		
		$width = $fetchData->progresswidth + $widthcount;
		$fetchData->progresswidth = $width;
		$saved = $fetchData->save();
		if($saved){
			$obj = new \App\Models\ApplicationActivitiesLog;
			$obj->stage = $workflowstage->name;
			$obj->comment = @$comments;
			$obj->app_id = $request->id;
			$obj->type = 'stage';
			$obj->user_id = Auth::user()->id;
			$saved = $obj->save();
			$displayback = false;
			$workflowstage = \App\Models\WorkflowStage::where('w_id', $fetchData->workflow)->orderBy('id','desc')->first();
		
			if($workflowstage && $workflowstage->name == $fetchData->stage){
				$displayback = true;
			}
			$response['status'] 	= 	true;
			$response['stage']	=	$fetchData->stage;
			$response['width']	=	$width;
			$response['displaycomplete']	=	$displayback;
			$response['message']	=	'Application has been successfully moved to next stage.';
		}else{
			$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
		}
		echo json_encode($response);
	}
	
	public function updatebackstage(Request $request){
		$fetchData = Application::find($request->id);
		$workflowstage = \App\Models\WorkflowStage::where('name', $fetchData->stage)->where('w_id', $fetchData->workflow)->first();
		$nextid = \App\Models\WorkflowStage::where('id', '<', $workflowstage->id)->where('w_id', $fetchData->workflow)->orderBy('id','Desc')->first();
		if($nextid){
			$workflowstagecount = \App\Models\WorkflowStage::where('w_id', $fetchData->workflow)->count();
			$widthcount = 0;
			if($workflowstagecount !== 0){
				$s = 100 / $workflowstagecount;
				$widthcount = round($s);
			}
			$fetchData->stage = $nextid->name;
			$comments = 'moved the stage from  <b>'.$workflowstage->name.'</b> to <b>'.$nextid->name.'</b>';
			$width = $fetchData->progresswidth - $widthcount;
			if($width <= 0){
				$width = 0;
			}	
			
			$fetchData->progresswidth = $width;
			
			$saved = $fetchData->save();
			if($saved){
				
				
				$obj = new \App\Models\ApplicationActivitiesLog;
				$obj->stage = $workflowstage->name;
				$obj->type = 'stage';
				$obj->comment = $comments;
				$obj->app_id = $request->id;
				$obj->user_id = Auth::user()->id;
				$saved = $obj->save();
				
				$displayback = false;
				$workflowstage = \App\Models\WorkflowStage::where('w_id', $fetchData->workflow)->orderBy('id','desc')->first();
			
				if($workflowstage->name == $fetchData->stage){
					$displayback = true;
				}
				
				$response['status'] 	= 	true;
				$response['stage']	=	$fetchData->stage;
				$response['displaycomplete']	=	$displayback;
		
				$response['width']	=	$width;
				$response['message']	=	'Application has been successfully moved to previous stage.';
			}else{
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
			}
	   }else{
		   $response['status'] 	= 	false;
				$response['message']	=	'';
	   }
		echo json_encode($response);
	}
	
	public function getapplicationslogs(Request $request){
		//$clientid = @$request->clientid;
		$id = $request->id;
		$fetchData = Application::find($id);
		if(!$fetchData){
			echo '<div class="alert alert-danger mb-0">Application not found.</div>';
			return;
		}
		// Client record for compose-email attrs (data-email / data-name) — same pattern as getapplicationdetail.
		$fetchedData = $fetchData->client_id ? Admin::find($fetchData->client_id) : null;
      
        if(isset($fetchData->product_id) && $fetchData->product_id !=""){
            $productdetail = \App\Models\Product::where('id', $fetchData->product_id)->first();
            if($productdetail){
                $course_name = $productdetail->name;
            } else {
                $course_name = "";
            }
        } else {
            $course_name = "";
        }

        if(isset($fetchData->partner_id) && $fetchData->partner_id !=""){
            $partnerdetail = \App\Models\Partner::where('id', $fetchData->partner_id)->first();
            if($partnerdetail){
                $school_name = $partnerdetail->partner_name;
            } else {
                $school_name = "";
            }
        } else {
            $school_name = "";
        }
		
		$stagesquery = \App\Models\WorkflowStage::where('w_id', $fetchData->workflow)->get();
		foreach($stagesquery as $stages){
		$stage1 = '';
						
							$workflowstagess = \App\Models\WorkflowStage::where('name', $fetchData->stage)->where('w_id', $fetchData->workflow)->first();
					
					$prevdata = \App\Models\WorkflowStage::where('id', '<', $workflowstagess->id)->where('w_id', $fetchData->workflow)->orderBy('id','Desc')->get();
					$stagearray = array();
					foreach($prevdata as $pre){
						$stagearray[] = $pre->id;
					}
							
							if(in_array($stages->id, $stagearray)){
								$stage1 = 'app_green';
							}
							if($fetchData->status == 1){
								$stage1 = 'app_green';
							}
							$stagname = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $stages->name)));
							?>
				
					<div class="accordion cus_accrodian">
						
						<div class="accordion-header collapsed <?php echo $stage1; ?> <?php if($fetchData->stage == $stages->name && $fetchData->status != 1){ echo  'app_blue'; }  ?>">
								<h4 class="stage-collapse-toggle" role="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $stagname; ?>_accor" aria-expanded="false" tabindex="0"><?php echo $stages->name; ?></h4>
								<div class="accord_hover">
									<a title="Add Note" class="openappnote" data-app-type="<?php echo $stages->name; ?>" data-id="<?php echo $fetchData->id; ?>" href="javascript:;"><?php echo \App\Helpers\IconHelper::render('file-alt'); ?></a>
									<a title="Add Document" class="opendocnote" data-app-type="<?php echo $stagname; ?>" data-typename="<?php echo $stages->name; ?>" data-id="<?php echo $fetchData->id; ?>" data-appdocclientid="<?php echo $fetchData->client_id; ?>" href="javascript:;"><?php echo \App\Helpers\IconHelper::render('file-image'); ?></a>
                                    
									<!--<a data-app-type="<?php //echo $stages->name; ?>" title="Add Appointments" class="openappappoint" data-id="<?php //echo $fetchData->id; ?>" href="javascript:;"><?php echo \App\Helpers\IconHelper::render('calendar'); ?></a>-->
                                    <a data-course="<?php echo $course_name; ?>" data-school="<?php echo $school_name; ?>" data-app-type="<?php echo $stages->name; ?>" title="Actions" class="openappaction" data-id="<?php echo $fetchData->id; ?>" href="javascript:;"><?php echo \App\Helpers\IconHelper::render('calendar'); ?></a>

									<a data-app-type="<?php echo $stages->name; ?>" title="Email" data-id="{{@$fetchData->id}}" data-email="{{@$fetchedData->email}}" data-name="{{@$fetchedData->first_name}} {{@$fetchedData->last_name}}" class="openclientemail" title="Compose Mail" href="javascript:;"><?php echo \App\Helpers\IconHelper::render('envelope'); ?></a>
								</div>
							</div>
							<?php
							$applicationlists = \App\Models\ApplicationActivitiesLog::where('app_id', $fetchData->id)->where('stage',$stages->name)->orderby('created_at', 'DESC')->get();
							
							?>
							<div class="accordion-body collapse" id="<?php echo $stagname; ?>_accor" data-parent="#accordion" style="">
								<div class="activity_list">
								<?php foreach($applicationlists as $applicationlist){ 
								$admin = \App\Models\Staff::find($applicationlist->user_id);
								?>
									<div class="activity_col">
										<div class="activity_txt_time">
											<span class="span_txt"><b><?php echo $admin->first_name; ?></b> <?php echo $applicationlist->comment; ?></span>
											<span class="span_time"><?php echo date('d D, M Y h:i A', strtotime($applicationlist->created_at)); ?></span>
										</div>
										<?php if($applicationlist->title != ''){ ?>
										<div class="app_description"> 
											<div class="app_card">
												<div class="app_title"><?php echo $applicationlist->title; ?></div>
											</div>
											<?php if($applicationlist->description != ''){ ?>
											<div class="log_desc">
												<?php echo $applicationlist->description; ?>
											</div>
											<?php } ?>
										</div>	
										<?php } ?> 
									</div>
								<?php } ?>
								</div>
							</div>
						</div>
						<?php } ?>
		<?php
		}
	
	public function addNote(Request $request){
		$noteid =  $request->noteid;
		$type =  $request->type;
		
		$obj = new \App\Models\ApplicationActivitiesLog;
			$obj->stage = $type;
			$obj->type = 'note';
			$obj->comment = 'added a note';
			$obj->title = $request->title;
			$obj->description = $request->description;
			$obj->app_id = $noteid;
			$obj->user_id = Auth::user()->id;
			$saved = $obj->save();
		$saved = $obj->save();
		if($saved){
			$response['status'] 	= 	true;
			$response['message']	=	'Note successfully added';
		}else{
			$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
		}
		echo json_encode($response);
	}	
	
	public function getapplicationnotes(Request $request){
		$noteid =  $request->id;
		$filter = $request->get('filter', 'all'); // all | note | sheet_comment

		$query = \App\Models\ApplicationActivitiesLog::where('app_id', $noteid);
		if ($filter === 'note') {
			$query->where('type', 'note');
		} elseif ($filter === 'sheet_comment') {
			$query->where('type', 'sheet_comment');
		} else {
			$query->whereIn('type', ['note', 'sheet_comment']);
		}
		$lists = $query->orderby('updated_at', 'DESC')->get();

		$notesUrl = url('/getapplicationnotes');
		ob_start();
			?>
			<div class="notes-tab-content" data-app-id="<?php echo (int) $noteid; ?>" data-notes-url="<?php echo e($notesUrl); ?>">
				<div class="mb-3">
					<label class="me-2">Filter:</label>
					<select id="notes_filter_type" class="form-control form-control-sm d-inline-block" style="width: auto;">
						<option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All</option>
						<option value="note" <?php echo $filter === 'note' ? 'selected' : ''; ?>>Notes only</option>
						<option value="sheet_comment" <?php echo $filter === 'sheet_comment' ? 'selected' : ''; ?>>Sheet comments only</option>
					</select>
				</div>
				<div class="note_term_list">
				<?php
				foreach($lists as $list){
					$admin = \App\Models\Staff::find($list->user_id);
					$isSheetComment = ($list->type === 'sheet_comment');
					$titleDisplay = $isSheetComment ? $list->title : (@$list->title == "" ? config('constants.empty') : str_limit(@$list->title, '19', '...'));
					$descDisplay = $isSheetComment ? $list->comment : (@$list->description == "" ? config('constants.empty') : str_limit(@$list->description, '15', '...'));
				?>
					<div class="note_col" id="note_id_<?php echo $list->id; ?>">
						<div class="note_content">
							<h4><a class="viewapplicationnote" data-id="<?php echo $list->id; ?>" href="javascript:;"><?php echo e($titleDisplay); ?></a></h4>
							<p><?php echo e($descDisplay); ?></p>
						</div>
						<div class="extra_content">
							<div class="left">
								<div class="author">
									<a href="#"><?php echo $admin ? substr($admin->first_name, 0, 1) : ''; ?></a>
								</div>
								<div class="note_modify">
									<small>Last Modified <span><?php echo date('Y-m-d', strtotime($list->updated_at)); ?></span></small>
								</div>
							</div>
							<div class="right"></div>
						</div>
					</div>
				<?php } ?>
				</div>
				<div class="clearfix"></div>
			</div>
			<script>
			(function(){
				var $notes = $('#notes');
				$(document).off('change', '#notes_filter_type').on('change', '#notes_filter_type', function(){
					var appId = $(this).closest('.notes-tab-content').data('app-id');
					var url = $(this).closest('.notes-tab-content').data('notes-url');
					var filter = $(this).val();
					if (!appId || !url) return;
					$.get(url, { id: appId, filter: filter }, function(html){ $notes.html(html); });
				});
			})();
			</script>
			<?php
			echo ob_get_clean();
	}
	
	public function applicationsendmail(Request $request){
		$requestData = $request->all();

		if (empty($requestData['email_from'])) {
			echo json_encode(['status' => false, 'message' => 'Please select a From email address']);
			return;
		}

		$user_id = @Auth::user()->id;
		$subject = $requestData['subject'];
		$message = $requestData['message'];
		$to = $requestData['to'];

		$client = \App\Models\Admin::Where('email', $requestData['to'])->first();
		if (!$client) {
			echo json_encode(['status' => false, 'message' => 'Recipient not found']);
			return;
		}

		$emailService = app(\App\Services\EmailService::class);
		$emailConfig = $emailService->configureMailerForEmail($requestData['email_from']);
		if (!$emailConfig) {
			echo json_encode(['status' => false, 'message' => 'Invalid From email address']);
			return;
		}
		$sender = $emailConfig->email;
		$senderName = $emailConfig->display_name ?? $sender;

			$subject = str_replace('{Client First Name}',$client->first_name, $subject);
			$message = str_replace('{Client First Name}',$client->first_name, $message);
			$message = str_replace('{Client Assignee Name}',$client->first_name, $message);
			$message = str_replace('{Company Name}', \App\Helpers\Helper::defaultCrmCompanyName(), $message);
			$client_dob = (isset($client->dob) && $client->dob && $client->dob != '0000-00-00') ? date('d/m/Y', strtotime($client->dob)) : '';
			$subject = str_replace('{DOB}', $client_dob, $subject);
			$message = str_replace('{DOB}', $client_dob, $message);
			$array = array();
			$ccarray = array();
			if(isset($requestData['email_cc']) && !empty($requestData['email_cc'])){
				foreach($requestData['email_cc'] as $cc){
					$clientcc = \App\Models\Admin::Where('id', $cc)->first();
					if ($clientcc && !empty($clientcc->email)) {
						$ccarray[] = $clientcc->email;
					}
				}
			}
				$sent = $this->send_compose_template($message, $senderName, $to, $subject, $sender, $array, $ccarray);
			if($sent){
				$objs = new \App\Models\ApplicationActivitiesLog;
				$objs->stage = $request->type;
				$objs->type = 'appointment';
				$objs->comment = 'sent an email';
				$objs->title = '<b>Subject : '.$subject.'</b>';
				$objs->description = '<b>To: '.$to.'</b></br>'.$message;
				$objs->app_id = $request->noteid;
				$objs->user_id = Auth::user()->id;
				$saved = $objs->save();
				$response['status'] 	= 	true;
				$response['message']	=	'Email Sent Successfully';
			}else{
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
			}
		
		echo json_encode($response);
	}
	
	public function updateintake(Request $request){
		$requestData = $request->all();
		//echo '<pre>'; print_r($requestData); die;
		$user_id = @Auth::user()->id;
		$obj = Application::find($request->appid);
		if(!$obj){
			echo json_encode([
				'status' => false,
				'message' => 'Application not found.',
			]);
			return;
		}
		$obj->intakedate = $request->from;
		$saved = $obj->save();
			if($saved){
				
				$response['status'] 	= 	true;
				$response['message']	=	'Applied date successfully updated.';
			}else{
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
			}
		
		echo json_encode($response);
	}

	public function updateEnrolmentType(Request $request)
	{
		$request->validate([
			'appid' => 'required|integer',
			'enrolment_type' => 'nullable|string',
		]);

		$enrolmentType = $request->input('enrolment_type');
		if ($enrolmentType !== null && $enrolmentType !== '' && !array_key_exists($enrolmentType, Application::enrolmentTypeOptions())) {
			return response()->json([
				'status' => false,
				'message' => 'Invalid enrolment type selected.',
				'enrolmentType' => '',
			]);
		}

		$application = Application::find($request->appid);
		if (!$application) {
			return response()->json([
				'status' => false,
				'message' => 'Application not found. Please try again.',
				'enrolmentType' => '',
			]);
		}

		$existing = Application::normalizeEnrolmentType($application->enrolment_type);
		if (! Application::canEditEnrolmentOrCompanyValue($existing)) {
			return response()->json([
				'status' => false,
				'message' => 'Only Super Admin and Admin can update Enrolment Type once it is set.',
				'enrolmentType' => $existing,
			]);
		}

		$application->enrolment_type = ($enrolmentType === '' || $enrolmentType === null)
			? null
			: Application::normalizeEnrolmentType($enrolmentType);
		$application->save();

		if ($application->partner_id) {
			PartnersController::forgetPartnerStudentTabCache((int) $application->partner_id);
		}

		$normalized = Application::normalizeEnrolmentType($application->enrolment_type);

		return response()->json([
			'status' => true,
			'message' => 'Enrolment type updated successfully.',
			'enrolmentType' => $normalized,
		]);
	}

	public function updateCompanyName(Request $request)
	{
		$request->validate([
			'appid' => 'required|integer',
			'company_name' => 'nullable|string',
		]);

		$companyName = $request->input('company_name');
		if ($companyName !== null && $companyName !== '' && !array_key_exists($companyName, Application::companyNameOptions())) {
			return response()->json([
				'status' => false,
				'message' => 'Invalid company name selected.',
				'companyName' => '',
			]);
		}

		$application = Application::find($request->appid);
		if (!$application) {
			return response()->json([
				'status' => false,
				'message' => 'Application not found. Please try again.',
				'companyName' => '',
			]);
		}

		$existing = Application::normalizeCompanyName($application->company_name);
		if (! Application::canEditEnrolmentOrCompanyValue($existing)) {
			return response()->json([
				'status' => false,
				'message' => 'Only Super Admin and Admin can update Company Name once it is set.',
				'companyName' => $existing,
			]);
		}

		$application->company_name = ($companyName === '' || $companyName === null)
			? null
			: Application::normalizeCompanyName($companyName);
		$application->save();

		if ($application->partner_id) {
			PartnersController::forgetPartnerStudentTabCache((int) $application->partner_id);
		}

		$normalized = Application::normalizeCompanyName($application->company_name);

		return response()->json([
			'status' => true,
			'message' => 'Company name updated successfully.',
			'companyName' => $normalized,
		]);
	}
	
	public function updateexpectwin(Request $request){
		$requestData = $request->all();
		//echo '<pre>'; print_r($requestData); die;
		$user_id = @Auth::user()->id;
		$obj = Application::find($request->appid);
		$obj->expect_win_date = $request->from;
		$saved = $obj->save();
			if($saved){
				
				$response['status'] 	= 	true;
				$response['message']	=	'Date successfully updated.';
			}else{
				$response['status'] 	= 	true;
				$response['message']	=	'Please try again';
			}
		
		echo json_encode($response);
	}
	
	public function updatedates(Request $request){
		$requestData = $request->all();
		//echo '<pre>'; print_r($requestData); die;
		$user_id = @Auth::user()->id;
		$obj = Application::find($request->appid);
		if($request->datetype == 'start'){
			$obj->start_date = $request->from;
		}else{
			$obj->end_date = $request->from;
		}
		$saved = $obj->save();
			if($saved){
				
				$response['status'] 	= 	true;
				$response['message']	=	'Date successfully updated.';
				if($request->datetype == 'start'){
					$response['dates']	=	array(
						'date' => date('d',strtotime($obj->start_date)),
						'month' => date('M',strtotime($obj->start_date)),
						'year' => date('Y',strtotime($obj->start_date)),
					);
				}else{
					$response['dates']	=	array(
						'date' => date('d',strtotime($obj->end_date)),
						'month' => date('M',strtotime($obj->end_date)),
						'year' => date('Y',strtotime($obj->end_date)),
					);
				}
				
			}else{
				$response['status'] 	= 	true;
				$response['message']	=	'Please try again';
			}
		
		echo json_encode($response);
	}
	
	public function discontinue_application(Request $request){
		$requestData = $request->all();
		//echo '<pre>'; print_r($requestData); die;
		$user_id = @Auth::user()->id;
		$obj = Application::find($request->diapp_id);
		if(!$obj){
			echo json_encode([
				'status' => false,
				'message' => 'Application not found.',
				'discontinue_reason' => '',
				'discontinue_note' => '',
			]);
			return;
		}
		$obj->status = 2;
        $obj->discontinue_reason = $request->workflow;
        $obj->discontinue_note = $request->note;
		$saved = $obj->save();
        if($saved){
            $response['status'] 	= 	true;
            $response['message']	=	'Application successfully discontinued.';
            $response['discontinue_reason'] 	= 	$request->workflow;
            $response['discontinue_note'] 	= 	$request->note;
        }else{
            $response['status'] 	= 	false;
            $response['message']	=	'Please try again';
            $response['discontinue_reason'] = 	"";
            $response['discontinue_note'] 	= 	"";
        }
        echo json_encode($response);
	}

    public function refund_application(Request $request){
		$requestData = $request->all();
		//echo '<pre>'; print_r($requestData); die;
		
		// Validate required parameters
		if(empty($request->reapp_id)){
			$response['status'] 	= 	false;
			$response['message']	=	'Application ID is required.';
			$response['refund_note'] = "";
			echo json_encode($response);
			return;
		}
		
		if(empty($request->refund_note)){
			$response['status'] 	= 	false;
			$response['message']	=	'Refund notes are required.';
			$response['refund_note'] = "";
			echo json_encode($response);
			return;
		}
		
		$user_id = @Auth::user()->id;
		$obj = Application::find($request->reapp_id);
		
		// Check if application exists
		if(!$obj){
			$response['status'] 	= 	false;
			$response['message']	=	'Application not found.';
			$response['refund_note'] = "";
			echo json_encode($response);
			return;
		}
		
		$obj->status = 8;
        $obj->refund_notes = $request->refund_note;
		$saved = $obj->save();
        if($saved){
            $response['status'] 	= 	true;
            $response['message']	=	'Application successfully refunded.';
            $response['refund_note'] 	= 	$request->refund_note;
        }else{
            $response['status'] 	= 	false;
            $response['message']	=	'Please try again';
            $response['refund_note'] 	= "";
        }
        echo json_encode($response);
	}
	
	
	public function revert_application(Request $request){
		$requestData = $request->all();
		
		//echo '<pre>'; print_r($requestData); die;
		$user_id = @Auth::user()->id;
		$obj = Application::find($request->revapp_id);
		if(!$obj){
			echo json_encode([
				'status' => false,
				'message' => 'Application not found.',
			]);
			return;
		}
		$obj->status = 0;
		$workflowstagecount = \App\Models\WorkflowStage::where('w_id', $obj->workflow)->count();
			$widthcount = 0;
			if($workflowstagecount !== 0){
				$s = 100 / $workflowstagecount;
				$widthcount = round($s);
			}
		$progresswidth = $obj->progresswidth - $widthcount;
		$obj->progresswidth = $progresswidth;
		$saved = $obj->save();
			if($saved){
			$displayback = false;
				$workflowstage = \App\Models\WorkflowStage::where('w_id', $obj->workflow)->orderBy('id','desc')->first();
			
				if($workflowstage && $workflowstage->name == $obj->stage){
					$displayback = true;
				}	
				$response['status'] 	= 	true;
				$response['width'] 	= 	$progresswidth;
				$response['displaycomplete'] 	= 	$displayback;
				$response['message']	=	'Application successfully reverted.';
			}else{
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
			}
		
		echo json_encode($response);
	}
	
	public function spagent_application(Request $request){
		$requestData = $request->all();
		$flag = true;
		/* if(Application::where('super_agent',$request->super_agent)->exists()){
			$flag = false;
			$response['message']	=	'Agent is already exists';
		}
		if(Application::where('sub_agent',$request->super_agent)->exists()){
			$flag = false;
			$response['message']	=	'Agent is already exists in sub admin';
		} */
		if($flag){
			$user_id = @Auth::user()->id;
			$obj = Application::find($request->siapp_id);
			$obj->super_agent = $request->super_agent;
			$saved = $obj->save();
			if($saved){
				$agent = \App\Models\Agent::where('id',$request->super_agent)->first();
				$response['status'] 	= 	true;
				$response['message']	=	'Application successfully updated.';
				$response['data']	=	'<div class="client_info">
							<div class="cl_logo" style="display: inline-block;width: 30px;height: 30px; border-radius: 50%;background: #6777ef;text-align: center;color: #fff;font-size: 14px; line-height: 30px; vertical-align: top;">'.substr($agent->full_name, 0, 1).'</div>
							<div class="cl_name" style="display: inline-block;margin-left: 5px;width: calc(100% - 60px);">
								<span class="name">'.$agent->full_name.'</span>
								<span class="ui label zippyLabel alignMiddle yellow">
							  '.$agent->struture.'
							</span>
							</div>
							<div class="cl_del" style="display: inline-block;">
								<a href="javascript:;" data-href="superagent" data-id="'.$request->siapp_id.'" class="deletenote">' . \App\Helpers\IconHelper::render('times') . '</a>
							</div>
						</div>';
			}else{
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
			}
		}else{
			$response['status'] 	= 	false;
		}
		
		echo json_encode($response);
	}
	
	public function sbagent_application(Request $request){
		$requestData = $request->all();
		$flag = true;
		/* if(Application::where('super_agent',$request->sub_agent)->exists()){
			$flag = false;
			$response['message']	=	'Agent is already exists in super admin';
		}
		if(Application::where('sub_agent',$request->sub_agent)->exists()){
			$flag = false;
			$response['message']	=	'Agent is already exists';
		} */
		if($flag){
			$user_id = @Auth::user()->id;
			$obj = Application::find($request->sbapp_id);
			$obj->sub_agent = $request->sub_agent;
			$saved = $obj->save();
			if($saved){
				$agent = \App\Models\Agent::where('id',$request->sub_agent)->first();
				$response['status'] 	= 	true;
				$response['message']	=	'Application successfully updated.';
				$response['data']	=	'<div class="client_info">
							<div class="cl_logo" style="display: inline-block;width: 30px;height: 30px; border-radius: 50%;background: #6777ef;text-align: center;color: #fff;font-size: 14px; line-height: 30px; vertical-align: top;">'.substr($agent->full_name, 0, 1).'</div>
							<div class="cl_name" style="display: inline-block;margin-left: 5px;width: calc(100% - 60px);">
								<span class="name">'.$agent->full_name.'</span>
								<span class="ui label zippyLabel alignMiddle yellow">
							  '.$agent->struture.'
							</span>
							</div>
							<div class="cl_del" style="display: inline-block;">
								<a href="javascript:;" data-href="subagent" data-id="'.$request->sbapp_id.'" class="deletenote">' . \App\Helpers\IconHelper::render('times') . '</a>
							</div>
						</div>';
			}else{
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
			}
		}else{
			$response['status'] 	= 	false;
		}
		
		echo json_encode($response);
	}
	
	public function superagent(Request $request){
		$response = ['status' => false, 'message' => 'Please try again'];
		
		$obj = Application::find($request->note_id);
		if (!$obj) {
			$response['message'] = 'Application not found';
			return response()->json($response);
		}
		
		// Set to null instead of empty string - super_agent is an integer column
		$obj->super_agent = null;
		$saved = $obj->save();
		
		if ($saved) {
			$response['status'] = true;
			$response['message'] = 'Super agent removed successfully.';
		}
		
		return response()->json($response);
	}
	
	public function subagent(Request $request){
		$response = ['status' => false, 'message' => 'Please try again'];
		
		$obj = Application::find($request->note_id);
		if (!$obj) {
			$response['message'] = 'Application not found';
			return response()->json($response);
		}
		
		// Set to null instead of empty string - sub_agent is an integer column
		$obj->sub_agent = null;
		$saved = $obj->save();
		
		if ($saved) {
			$response['status'] = true;
			$response['message'] = 'Sub agent removed successfully.';
		}
		
		return response()->json($response);
	}
	
	public function application_ownership(Request $request){
		$requestData = $request->all();
		
			$user_id = @Auth::user()->id;
			$obj = Application::find($request->mapp_id);
			$obj->ratio = $request->ratio;
			$saved = $obj->save();
			if($saved){

				$response['status'] 	= 	true;
				$response['message']	=	'Application successfully updated.';
				$response['ratio']	=	$obj->ratio;
				
			}else{
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
			}
		
		echo json_encode($response);
	}

	/**
	 * Change the assignee (user_id) for an application. Used by Ongoing Sheet and Application detail.
	 */
	public function changeApplicationAssignee(Request $request)
	{
		$request->validate([
			'application_id' => 'required|integer|exists:applications,id',
			'assignee_id'    => 'required|integer',
		]);

		$application = Application::findOrFail($request->application_id);
		$assigneeId  = (int) $request->assignee_id;

		$assignee = \App\Models\Staff::where('id', $assigneeId)->where('status', 1)->first();
		if (!$assignee) {
			return response()->json(['success' => false, 'message' => 'Invalid assignee. Select an active staff member.']);
		}

		$application->user_id = $assigneeId;
		$saved = $application->save();

	if ($saved) {
		return response()->json([
			'success' => true,
			'message' => 'Assignee updated successfully.',
			'assignee_name' => trim($assignee->first_name . ' ' . $assignee->last_name),
			'assignee_email' => $assignee->email,
		]);
	}

	return response()->json(['success' => false, 'message' => 'Failed to update assignee.']);
	}
	
	public function saleforcast(Request $request){
		$requestData = $request->all();
		
			$user_id = @Auth::user()->id;
			$obj = Application::find($request->fapp_id);
			$obj->client_revenue = $request->client_revenue;
			$obj->partner_revenue = $request->partner_revenue;
			$obj->discounts = $request->discounts;
			$saved = $obj->save();
			if($saved){

				$response['status'] 	= 	true;
				$response['message']	=	'Application successfully updated.';
				$response['client_revenue']	=	$obj->client_revenue;
				$response['partner_revenue']	=	$obj->partner_revenue;
				$response['discounts']	=	$obj->discounts;
				
			}else{
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
			}
		
		echo json_encode($response);
	}
	
	public function getapplicationbycid(Request $request){
		$clientid = $request->clientid;
		//echo '<pre>'; print_r($requestData); die;
		$applications = Application::where('client_id', $clientid)->orderby('created_at', 'DESC')->get();
		ob_start();
		?>
		<option value="">Select Application</option>
		<?php
		foreach($applications as $application){
			$productdetail = \App\Models\Product::where('id', $application->product_id)->first();
			$partnerdetail = \App\Models\Partner::where('id', $application->partner_id)->first();		
			$clientdetail = \App\Models\Admin::where('id', $application->client_id)->first();
			$PartnerBranch = \App\Models\PartnerBranch::where('id', $application->branch)->first();
			?>
			<option value="<?php echo $application->id; ?>"><?php echo @$productdetail->name.'('.@$partnerdetail->partner_name; ?> <?php echo @$PartnerBranch->name; ?>)</option>
			<?php
		}
		return ob_get_clean();
	}
	
	
	public function showproductfee(Request $request){ //dd($request->all());
		$id = $request->id;
        $partnerid = $request->partnerid;
		ob_start();
		$appfeeoption = ApplicationFeeOption::where('app_id', $id)->first(); //dd($appfeeoption);
        $appInfo = Application::join('partners', 'applications.partner_id', '=', 'partners.id')
        ->join('products', 'applications.product_id', '=', 'products.id')
        ->select('applications.product_id','applications.partner_id','partners.commission_percentage','partners.partner_name','products.name as coursename')
        ->where('applications.id', $id)->first(); //dd($appInfo);
        ?>
		<form method="post" action="<?php echo \URL::to('/applicationsavefee'); ?>" name="applicationfeeform" id="applicationfeeform" autocomplete="off" enctype="multipart/form-data">
				<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
				<input type="hidden" name="id" value="<?php echo $id; ?>">
					<div class="row">
                        <div class="col-12 col-md-4 col-lg-4">
							<div class="form-group">
								<label for="college_name">College Name <span class="span_req">*</span></label>
                              <input type="text" readonly name="college_name" class="form-control" value="<?php if( isset($appInfo->partner_name) && $appInfo->partner_name != "") {echo $appInfo->partner_name;} else { echo '';} ?>">
                                <span class="custom-error college_name_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>

                        <div class="col-12 col-md-4 col-lg-4">
							<div class="form-group">
								<label for="course_name">Course Name <span class="span_req">*</span></label>
								<input type="text" readonly name="course_name" class="form-control" value="<?php if( isset($appInfo->coursename) && $appInfo->coursename != "") {echo $appInfo->coursename;} else { echo '';} ?>">
                                <span class="custom-error course_name_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>

                        <div class="col-12 col-md-4 col-lg-4">
							<div class="form-group">
								<label for="installment">Installment <span class="span_req">*</span></label>
                                <select name="installment" class="form-control" data-valid="required">
                                    <option value="">Select Option</option>
                                    <option value="Yes" <?php if( isset($appfeeoption->installment) && $appfeeoption->installment == 'Yes' ) { echo ' selected="selected"'; } else { echo '';} ?> >Yes</option>
	                                <option value="No" <?php if( isset($appfeeoption->installment) && $appfeeoption->installment == 'No' ) { echo ' selected="selected"'; } else { echo '';} ?> >No</option>
                                </select>
								<span class="custom-error installment_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>

                        <div class="col-12 col-md-12 col-lg-12">
							<div class="table-responsive">
								<table class="table text_wrap" id="productitemview">
									<thead>
										<tr>
											<th>Fee Type</th>
											<th>Total Fee</th>
										</tr>
									</thead>
									<tbody class="tdata">
									<?php
									$totl = 0.00;
									$discount = 0.00;
									?>
                                        <tr class="add_fee_option cus_fee_option">
											<td>Total Course Fee
                                                <input value="1" type="hidden" name="fee_option_type[]">
                                                <input value="Total Course Fee" type="hidden" name="course_fee_type[]">
                                            </td>
											<td class="total_fee">
                                                <input value="<?php if( isset($appfeeoption->total_course_fee_amount) && $appfeeoption->total_course_fee_amount != "") { echo $appfeeoption->total_course_fee_amount;} ?>" data-valid="required" type="number" min="0" step="0.01" class="form-control total_fee_am" name="total_fee[]" id="total_course_fee_amount">
                                            </td>
										</tr>

                                        <tr class="add_fee_option cus_fee_option">
											<td>Scholarship Fee
                                                <input value="1" type="hidden" name="fee_option_type[]">
                                                <input value="Scholarship Fee" type="hidden" name="course_fee_type[]">
                                            </td>
											<td class="total_fee">
                                                <input value="<?php if( isset($appfeeoption->scholarship_fee_amount) && $appfeeoption->scholarship_fee_amount != "") { echo $appfeeoption->scholarship_fee_amount;} ?>" data-valid="required" type="number" min="0" step="0.01" class="form-control total_fee_am" name="total_fee[]" id="scholarship_fee_amount">
                                            </td>
										</tr>


                                        <tr class="add_fee_option cus_fee_option">
                                            <td>Enrolment Fee
                                            <input value="1" type="hidden" name="fee_option_type[]">
                                            <input value="Enrolment Fee" type="hidden" name="course_fee_type[]">
                                            </td>
											<td class="total_fee">
                                                <input value="<?php if( isset($appfeeoption->enrolment_fee_amount) && $appfeeoption->enrolment_fee_amount != "") { echo $appfeeoption->enrolment_fee_amount;} ?>" data-valid="required" type="number" min="0" step="0.01" class="form-control total_fee_am" name="total_fee[]" id="enrolment_fee_amount">
                                            </td>
										</tr>

                                        <tr class="add_fee_option cus_fee_option">
                                            <td>Material fees
                                                <input value="1" type="hidden" name="fee_option_type[]">
                                                <input value="Material fees" type="hidden"  name="course_fee_type[]">
                                            </td>
											<td class="total_fee">
                                                <input value="<?php if( isset($appfeeoption->material_fees) && $appfeeoption->material_fees != "") { echo $appfeeoption->material_fees;} ?>" data-valid="required" type="number" min="0" step="0.01" class="form-control total_fee_am" name="total_fee[]" id="material_fee_amount">
                                            </td>
										</tr>
                                    </tbody>
									<tfoot>
                                        <tr>
                                            <td>Tution Fee -</td>
											<td class="calculate_tution_fee"><?php if( isset($appfeeoption->tution_fees) && $appfeeoption->tution_fees != "") { echo $appfeeoption->tution_fees;} ?></td>
                                        </tr>
                                    </tfoot>
								</table>
							</div>
						</div>
                        <input type="hidden" name="tution_fees" id="tution_fees" value="<?php if( isset($appfeeoption->tution_fees) && $appfeeoption->tution_fees != "") { echo $appfeeoption->tution_fees;} ?>">

                        <?php
                        if( isset($appInfo->commission_percentage)) {
                            if( $appInfo->commission_percentage !="" ) {
                                $commission_percentage = $appInfo->commission_percentage;
                            } else {
                                $commission_percentage = 0;
                            }
                        } else {
                            $commission_percentage = 0;
                        }?>

                        <?php
                        if( isset($appfeeoption->bonus_amount) ) {
                            if( $appfeeoption->bonus_amount !="" ) {
                                $bonus_amount = $appfeeoption->bonus_amount;
                            } else {
                                $bonus_amount = 0;
                            }
                        } else {
                            $bonus_amount = 0;
                        } ?>

                        <?php
                        /*if( isset($appfeeoption->bonus_paid) ) {
                            if( $appfeeoption->bonus_paid !="" ) {
                                $bonus_paid = $appfeeoption->bonus_paid;
                            } else {
                                $bonus_paid = "";
                            }
                        } else {
                            $bonus_paid = "";
                        } */?>

                        <input type="hidden" name="commission_percentage" id="commission_percentage" value="<?php echo $commission_percentage;?>">
                        <input type="hidden" name="bonus_amount" id="bonus" value="<?php echo $bonus_amount;?>">
                        <!--<input type="hidden" name="bonus_paid" value="<?php //echo $bonus_paid;?>">-->
                        <!--<input type="hidden" name="tution_fees_commission" id="tution_fees_commission" value="<?php //if( isset($appfeeoption->tution_fees_commission) && $appfeeoption->tution_fees_commission != "") { echo $appfeeoption->tution_fees_commission;} ?>">-->
                        <input type="hidden" name="partnerid" id="partnerid" value="<?php echo $partnerid;?>">

                        <div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('applicationfeeform')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
		<?php
		return ob_get_clean();
	}


	public function applicationsavefee(Request $request){
		$requestData = $request->all(); //dd($requestData);
        //save commission percentage in partner table
        /*if(isset($request->partnerid) && $request->partnerid !="" ){
            $obj3 = Partner::find($request->partnerid);
            $obj3->commission_percentage = $request->commission_percentage;
            $saved = $obj3->save();
        }*/
        if(ApplicationFeeOption::where('app_id', $request->id)->exists())
        {
			$o = ApplicationFeeOption::where('app_id', $request->id)->first();
			$obj = ApplicationFeeOption::find($o->id);
			$obj->user_id = Auth::user()->id;
			$obj->app_id = $request->id;
			$obj->college_name = $requestData['college_name'];
			$obj->course_name = $requestData['course_name'];
			$obj->installment = $requestData['installment'];

            if( isset($requestData['total_fee'][0]) && $requestData['total_fee'][0] != ""){
                $total_course_fee_amount = $requestData['total_fee'][0];
            } else {
                $total_course_fee_amount = "0.00";
            }

            if( isset($requestData['total_fee'][1]) && $requestData['total_fee'][1] != ""){
                $scholarship_fee_amount = $requestData['total_fee'][1];
            } else {
                $scholarship_fee_amount = "0.00";
            }

            if( isset($requestData['total_fee'][2]) && $requestData['total_fee'][2] != ""){
                $enrolment_fee_amount = $requestData['total_fee'][2];
            } else {
                $enrolment_fee_amount = "0.00";
            }

            if( isset($requestData['total_fee'][3]) && $requestData['total_fee'][3] != ""){
                $material_fees = $requestData['total_fee'][3];
            } else {
                $material_fees = "0.00";
            }
            $obj->total_course_fee_amount = $total_course_fee_amount;
            $obj->scholarship_fee_amount = $scholarship_fee_amount;
            $obj->enrolment_fee_amount = $enrolment_fee_amount;
            $obj->material_fees  = $material_fees;
            $obj->tution_fees = $requestData['tution_fees'];
            //calculate tution_fees_commission
            $commission_percentage_amount  = ($requestData['tution_fees'] * $requestData['commission_percentage'])/100;
            $tution_fees_commission = $commission_percentage_amount + $requestData['bonus_amount'];
            $obj->tution_fees_commission = $tution_fees_commission;
            //$obj->bonus_amount = $requestData['bonus_amount'];
            //$obj->bonus_paid = $requestData['bonus_paid'];

            $saved = $obj->save();
			if($saved){
				ApplicationFeeOptionType::where('fee_id', $obj->id)->where('fee_option_type', 1)->delete();
				//$course_fee_type = $requestData['course_fee_type'];
				$totl = 0;
				for($i = 0; $i< count($requestData['course_fee_type']); $i++){
					$totl += $requestData['total_fee'][$i];
					$objs = new ApplicationFeeOptionType;
					$objs->fee_id = $obj->id;
                    $objs->fee_option_type = 1; //primary
					$objs->fee_type = $requestData['course_fee_type'][$i];
					$objs->total_fee = $requestData['total_fee'][$i];
                    $saved = $objs->save();
                }
                $discount = 0.00;
				$response['status'] 	= 	true;
                $response['message']	=	'Fee Option added successfully';
                $response['totalfee']	=	$totl;
                $response['discount']	=	$discount;


                $response['total_course_fee_amount']	=	$total_course_fee_amount;
                $response['scholarship_fee_amount']	=	$scholarship_fee_amount;
                $response['enrolment_fee_amount']	=	$enrolment_fee_amount;
                $response['material_fees']	=	$material_fees;
                $response['tution_fees']	=	$requestData['tution_fees'];
            }else{
				$response['status'] 	= 	false;
				$response['message']	=	'Record not found';
			}
		}
        else
        {
			$obj = new ApplicationFeeOption;
			$obj->user_id = Auth::user()->id;
			$obj->app_id = $request->id;
            $obj->college_name = $requestData['college_name'];
			$obj->course_name = $requestData['course_name'];
			$obj->installment = $requestData['installment'];

            if( isset($requestData['total_fee'][0]) && $requestData['total_fee'][0] != ""){
                $total_course_fee_amount = $requestData['total_fee'][0];
            } else {
                $total_course_fee_amount = "0.00";
            }

            if( isset($requestData['total_fee'][1]) && $requestData['total_fee'][1] != ""){
                $scholarship_fee_amount = $requestData['total_fee'][1];
            } else {
                $scholarship_fee_amount = "0.00";
            }

            if( isset($requestData['total_fee'][2]) && $requestData['total_fee'][2] != ""){
                $enrolment_fee_amount = $requestData['total_fee'][2];
            } else {
                $enrolment_fee_amount = "0.00";
            }

            if( isset($requestData['total_fee'][3]) && $requestData['total_fee'][3] != ""){
                $material_fees = $requestData['total_fee'][3];
            } else {
                $material_fees = "0.00";
            }
            $obj->total_course_fee_amount = $total_course_fee_amount;
            $obj->scholarship_fee_amount = $scholarship_fee_amount;
            $obj->enrolment_fee_amount = $enrolment_fee_amount;
            $obj->material_fees  = $material_fees;
            $obj->tution_fees = $requestData['tution_fees'];

            //calculate tution_fees_commission
            $commission_percentage_amount  = ($requestData['tution_fees'] * $requestData['commission_percentage'])/100;
            $tution_fees_commission = $commission_percentage_amount + $requestData['bonus_amount'];
            $obj->tution_fees_commission = $tution_fees_commission;

            //$obj->bonus_amount = $requestData['bonus_amount'];
            //$obj->bonus_paid = $requestData['bonus_paid'];
            $saved = $obj->save();
			if($saved){
				$course_fee_type = $requestData['course_fee_type'];
				$totl = 0;
				for($i = 0; $i< count($course_fee_type); $i++){
					$totl += $requestData['total_fee'][$i];
					$objs = new ApplicationFeeOptionType;
					$objs->fee_id = $obj->id;
                    $objs->fee_option_type = 1; //primary
					$objs->fee_type = $requestData['course_fee_type'][$i];
					$objs->total_fee = $requestData['total_fee'][$i];
                    $saved = $objs->save();
                }
				$discount = 0.00;
                $response['status'] 	= 	true;
                $response['message']	=	'Fee Option added successfully';
                $response['totalfee']	=	$totl;
                $response['discount']	=	$discount;

                $response['total_course_fee_amount']	=	$total_course_fee_amount;
                $response['scholarship_fee_amount']	=	$scholarship_fee_amount;
                $response['enrolment_fee_amount']	=	$enrolment_fee_amount;
                $response['material_fees']	=	$material_fees;
                $response['tution_fees']	=	$requestData['tution_fees'];
			}else{
				$response['status'] 	= 	false;
				$response['message']	=	'Record not found';
			}
		}
		if (!empty($response['status'])) {
			$partnerIdForStudentTab = Application::where('id', $request->id)->value('partner_id');
			PartnersController::forgetPartnerStudentTabCache($partnerIdForStudentTab ? (int) $partnerIdForStudentTab : null);
		}
		echo json_encode($response);
	}

	
    
	public function exportapplicationpdf(Request $request, $id){
		$applications = \App\Models\Application::where('id', $id)->first();
		if(!$applications){
			return Redirect::back()->with('error', 'Application not found.');
		}
		$partnerdetail = \App\Models\Partner::where('id', @$applications->partner_id)->first();
		$productdetail = \App\Models\Product::where('id', @$applications->product_id)->first();
		$cleintname = \App\Models\Admin::where('id',@$applications->client_id)->first();
		$PartnerBranch = \App\Models\PartnerBranch::where('id', @$applications->branch)->first();
		$pdf = PDF::setOptions([
			'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true,
			'logOutputFile' => storage_path('logs/log.htm'),
			'tempDir' => storage_path('logs/')
			])->loadView('emails.application',compact(['cleintname','applications','productdetail','PartnerBranch','partnerdetail'])); 
			//
			return $pdf->stream('application.pdf');
	}
	
	public function addchecklists(Request $request){
		$requestData = $request->all();
		$client_id = $requestData['client_id'];
		$app_id = $requestData['app_id'];
		$type = $requestData['type'];
		$typename = $requestData['typename'];
		$obj = new \App\Models\ApplicationDocumentList;
		$obj->type = $type;
		$obj->typename = $typename;
		$obj->client_id = $client_id;
		$obj->application_id = $app_id;
		$obj->document_type = @$request->document_type;
		$obj->description = $request->description;
		$obj->make_mandatory = $request->proceed_next_stage;
		if($requestData['due_date'] == 1){
			$obj->date = $request->appoint_date;
			$obj->time = $request->appoint_time;
		}
		$obj->user_id = Auth::user()->id;
		
		$saved = $obj->save();
		if($saved){
			$applicationdocuments = \App\Models\ApplicationDocumentList::where('application_id', $app_id)->where('client_id', $client_id)->where('type', $type)->get();
			$checklistdata = '<table class="table"><tbody>';
			foreach($applicationdocuments as $applicationdocument){
				$appcount = \App\Models\ApplicationDocument::where('list_id', $applicationdocument->id)->count();
				$checklistdata .= '<tr>';
				if($appcount >0){
					$checklistdata .= '<td><span class="check">' . \App\Helpers\IconHelper::render('check') . '</span></td>';
				}else{
					$checklistdata .= '<td><span class="round"></span></td>';
				}
					
					$checklistdata .= '<td>'.@$applicationdocument->document_type.'</td>';
					$checklistdata .= '<td><div class="circular-box cursor-pointer"><button class="transparent-button paddingNone">'.$appcount.'</button></div></td>';
					$checklistdata .= '<td><a data-aid="'.$app_id.'" data-type="'.$type.'" data-typename="'.$typename.'" data-id="'.$applicationdocument->id.'" class="openfileupload" href="javascript:;">' . \App\Helpers\IconHelper::render('plus') . '</a></td>';
				$checklistdata .= '</tr>';
			}
			$checklistdata .= '</tbody></table>';
			$response['status'] 	= 	true;
			$response['message']	=	'CHecklist added successfully';
			$response['data']	=	$checklistdata;
			$countchecklist = \App\Models\ApplicationDocumentList::where('application_id', $app_id)->count();
			$response['countchecklist']	=	$countchecklist;
		}else{
			$response['status'] 	= 	false;
				$response['message']	=	'Record not found';
		}
		echo json_encode($response);
	}
	
    public function checklistupload(Request $request){ //dd($request->all());
        $imageData = "";
        $uploadSummary = [
            'total' => 0,
            'uploaded_count' => 0,
            'failed_count' => 0,
            'failed_files' => [],
        ];

        if (!$request->hasfile('file')) {
            $response = [
                'status' => false,
                'message' => 'No files found in the upload.',
                'upload_summary' => $uploadSummary,
            ];
            echo json_encode($response);
            return;
        }

        $client_id = $request->client_id;
        $admin_info1 = \App\Models\Admin::select('client_id')->where('id', $client_id)->first(); //dd($admin);
        if(!empty($admin_info1)){
            $client_unique_id = $admin_info1->client_id;
        } else {
            $client_unique_id = "";
        }  //dd($client_unique_id);

        if(!is_array($request->file('file'))){
            $files[] = $request->file('file');
        }else{
            $files = $request->file('file');
        }

        foreach ($files as $file) {
            $uploadSummary['total']++;

            if (!$file->isValid()) {
                $uploadSummary['failed_count']++;
                $uploadSummary['failed_files'][] = [
                    'name' => $file->getClientOriginalName(),
                    'reason' => $file->getErrorMessage(),
                ];
                continue;
            }

            $size = $file->getSize();
            $fileName = $file->getClientOriginalName();
            $nameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
            $fileExtension = $file->getClientOriginalExtension();
            $uniqueName = (string) Str::uuid();
            $storageName = $uniqueName . ($fileExtension ? '.' . $fileExtension : '');
            $filePath = $client_unique_id.'/application_documents/'.$storageName; //dd($filePath);

            try {
                Storage::disk('s3')->put($filePath, file_get_contents($file));
            } catch (\Throwable $e) {
                $uploadSummary['failed_count']++;
                $uploadSummary['failed_files'][] = [
                    'name' => $fileName,
                    'reason' => 'Upload failed: ' . $e->getMessage(),
                ];
                continue;
            }

            $obj = new \App\Models\ApplicationDocument;
            $obj->type = $request->type;
            //$typename = ucwords(str_replace("-", " ", $request->type));
            $obj->typename = $request->typename;

            $obj->list_id = $request->id;
            $obj->file_name = $nameWithoutExtension; //$explodeFileName[0];
            $obj->file_type = $fileExtension; //$exploadename[1];
            // Get the full URL of the uploaded file
            $fileUrl = Storage::disk('s3')->url($filePath);
            $obj->myfile = $fileUrl;
            $obj->myfile_key = $storageName;
            $obj->file_size = $size;
            $obj->user_id = Auth::user()->id;
            $obj->application_id = $request->application_id;

            $save = $obj->save();
            if($save){
                $obj1 = new \App\Models\ApplicationActivitiesLog;
                $obj1->stage = $request->typename;
                $obj1->type = 'document';
                $obj1->comment = 'added a document';
                $obj1->title =  '';
                $obj1->description =  '';
                $obj1->app_id = $request->application_id;
                $obj1->user_id = Auth::user()->id;
                $obj1->save();
                $uploadSummary['uploaded_count']++;
                $imageData .= '<li>' . \App\Helpers\IconHelper::render('file') . ' '.htmlspecialchars($nameWithoutExtension, ENT_QUOTES, 'UTF-8').'</li>';
            } else {
                $uploadSummary['failed_count']++;
                $uploadSummary['failed_files'][] = [
                    'name' => $fileName,
                    'reason' => 'Database save failed.',
                ];
            }
        }

		$doclists = \App\Models\ApplicationDocument::where('application_id',$request->application_id)->orderby('created_at','DESC')->get();
		$doclistdata = '';
		foreach($doclists as $doclist){
			$docdata = \App\Models\ApplicationDocumentList::where('id', $doclist->list_id)->first();
			$doclistdata .= '<tr id="">';
            $doclistdata .= '<td>' . \App\Helpers\IconHelper::render('file') . ' '. $doclist->file_name.'<br>'.@$docdata->document_type.'</td>';
            $doclistdata .= '<td>';
            $doclistdata .=  $doclist->typename;
            $doclistdata .= '</td>';
            $admin = \App\Models\Staff::find(@$doclist->user_id);

			$doclistdata .= '<td><span style="    position: relative;background: rgb(3, 169, 244);font-size: .8rem;height: 24px;line-height: 24px;min-width: 24px;width: 24px;color: #fff;display: block;font-weight: 600;letter-spacing: 1px;text-align: center;border-radius: 50%;overflow: hidden;">'.substr(@$admin->first_name, 0, 1).'</span>'.@$admin->first_name.'</td>';
			$doclistdata .= '<td>'.date('d/m/Y',strtotime($doclist->created_at)).'</td>';
			$doclistdata .= '<td>';
			if($doclist->status == 1){
			    $doclistdata .= '<span class="check">' . \App\Helpers\IconHelper::render('eye') . '</span>';
			}
            $doclistdata .= '<div class="dropdown d-inline">
                <button class="btn btn-primary dropdown-toggle" type="button" id="" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
                <div class="dropdown-menu">
                    <a target="_blank" class="dropdown-item" href="'.$doclist->myfile.'">Preview</a>
                    <a data-id="'.$doclist->id.'" class="dropdown-item deletenote" data-href="deleteapplicationdocs" href="javascript:;">Delete</a>
                    <a download class="dropdown-item" href="'.$doclist->myfile.'">Download</a>';
                    if($doclist->status == 0){
                        $doclistdata .= '<a data-id="'.$doclist->id.'" class="dropdown-item publishdoc" href="javascript:;">Publish Document</a>';
                    }else{
                        $doclistdata .= '<a data-id="'.$doclist->id.'"  class="dropdown-item unpublishdoc" href="javascript:;">Unpublish Document</a>';
                    }
                $doclistdata .= '</div></div></td>';
			$doclistdata .= '</tr>';
		} //end foreach

		$application_id = (int) $request->application_id;
		$applicationuploadcount = DB::select(
			'SELECT COUNT(DISTINCT list_id) AS cnt FROM application_documents WHERE application_id = ?',
			[$application_id]
		);
		$response['status'] 	= 	true;
		$response['imagedata']	=	$imageData;
		$response['doclistdata']	=	$doclistdata;
		$response['applicationuploadcount']	=	@$applicationuploadcount[0]->cnt;
        $applicationdocuments = \App\Models\ApplicationDocumentList::where('application_id', $application_id)->where('type', $request->type)->get();
        $checklistdata = '<table class="table"><tbody>';
        foreach($applicationdocuments as $applicationdocument){
            $appcount = \App\Models\ApplicationDocument::where('list_id', $applicationdocument->id)->count();
            $checklistdata .= '<tr>';
            if($appcount >0){
                $checklistdata .= '<td><span class="check">' . \App\Helpers\IconHelper::render('check') . '</span></td>';
            }else{
                $checklistdata .= '<td><span class="round"></span></td>';
            }

            $checklistdata .= '<td>'.@$applicationdocument->document_type.'</td>';
            $checklistdata .= '<td><div class="circular-box cursor-pointer"><button class="transparent-button paddingNone">'.$appcount.'</button></div></td>';
            $checklistdata .= '<td><a data-aid="'.$application_id.'" data-type="'.$request->type.'" data-typename="'.$request->typename.'" data-id="'.$applicationdocument->id.'" class="openfileupload" href="javascript:;">' . \App\Helpers\IconHelper::render('plus') . '</a></td>';
            $checklistdata .= '</tr>';
        }
		$checklistdata .= '</tbody></table>';
		$response['checklistdata']	=	$checklistdata;
		$response['type']	=	$request->type;
        $response['application_id']	=	$request->application_id;
		echo json_encode($response);
	}
	
	public function deleteapplicationdocs(Request $request){
		if(\App\Models\ApplicationDocument::where('id', $request->note_id)->exists()){
			$appdoc = \App\Models\ApplicationDocument::where('id', $request->note_id)->first();
            
            if( isset($appdoc->myfile_key) && $appdoc->myfile_key != '' ){
                // Extract the file path from the URL
                $parsedUrl = parse_url($appdoc->myfile);
                $filePath = ltrim($parsedUrl['path'], '/'); //dd($filePath);

                // Find the position of the keyword
                $position = strpos($filePath, '/');
                if ($position !== false) {
                    $filePathArr = explode('/',$filePath); //dd($filePathArr);
                    if(!empty($filePathArr)){
                        $fileExistPath = $filePathArr[0]."/".$filePathArr[1]."/".$appdoc->myfile_key;
                        if (Storage::disk('s3')->exists($fileExistPath)) {
                            // To delete the uploaded file, use the delete method
                            Storage::disk('s3')->delete($fileExistPath); //dd('done');
                        } 
                    }
                }
            }
          
			$res = \App\Models\ApplicationDocument::where('id', $request->note_id)->delete();
			if($res){
				$response['status'] 	= 	true;
				$response['message'] 	= 	'Record removed successfully';
				
				//save in application activity log
                $obj1 = new \App\Models\ApplicationActivitiesLog;
                $obj1->stage = $appdoc->typename;
                $obj1->type = 'document';
                $obj1->comment = 'deleted a document';
                $obj1->title =  '';
                $obj1->description =  '';
                $obj1->app_id = $appdoc->application_id;
                $obj1->user_id = Auth::user()->id;
                $obj1->save();
              
              
				
				$doclists = \App\Models\ApplicationDocument::where('application_id',$appdoc->application_id)->orderby('created_at','DESC')->get();
		$doclistdata = ''; 
		foreach($doclists as $doclist){
			$docdata = \App\Models\ApplicationDocumentList::where('id', $doclist->list_id)->first();
			$doclistdata .= '<tr id="">';
				$doclistdata .= '<td>' . \App\Helpers\IconHelper::render('file') . ' '. $doclist->file_name.'<br>'.@$docdata->document_type.'</td>';
				$doclistdata .= '<td>';
				if($doclist->type == 'application'){ $doclistdata .= 'Application'; }else if($doclist->type == 'acceptance'){ $doclistdata .=  'Acceptance'; }else if($doclist->type == 'payment'){ $doclistdata .=  'Payment'; }else if($doclist->type == 'formi20'){ $doclistdata .=  'Form I 20'; }else if($doclist->type == 'visaapplication'){ $doclistdata .=  'Visa Application'; }else if($doclist->type == 'interview'){ $doclistdata .=  'Interview'; }else if($doclist->type == 'enrolment'){ $doclistdata .=  'Enrolment'; }else if($doclist->type == 'courseongoing'){ $doclistdata .=  'Course Ongoing'; }
				$doclistdata .= '</td>';
				$admin = \App\Models\Staff::find($doclist->user_id);
				
			$doclistdata .= '<td><span style="    position: relative;background: rgb(3, 169, 244);font-size: .8rem;height: 24px;line-height: 24px;min-width: 24px;width: 24px;color: #fff;display: block;font-weight: 600;letter-spacing: 1px;text-align: center;border-radius: 50%;overflow: hidden;">'.substr($admin->first_name, 0, 1).'</span>'.$admin->first_name.'</td>';
			$doclistdata .= '<td>'.date('Y-m-d',strtotime($doclist->created_at)).'</td>';
			$doclistdata .= '<td>';
			if($doclist->status == 1){
				$doclistdata .= '<span class="check">' . \App\Helpers\IconHelper::render('eye') . '</span>';
			}
				$doclistdata .= '<div class="dropdown d-inline">
					<button class="btn btn-primary dropdown-toggle" type="button" id="" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
					<div class="dropdown-menu"><a target="_blank" class="dropdown-item" href="'.$doclist->file_name.'">Preview</a>
                        <a data-id="'.$doclist->id.'" class="dropdown-item deletenote" data-href="deleteapplicationdocs" href="javascript:;">Delete</a>
                        <a download class="dropdown-item" href="'.$doclist->file_name.'">Download</a>';
                        
						
          				if($doclist->status == 0){
							$doclistdata .= '<a data-id="'.$doclist->id.'" class="dropdown-item publishdoc" href="javascript:;">Publish Document</a>';
						}else{
							$doclistdata .= '<a data-id="'.$doclist->id.'"  class="dropdown-item unpublishdoc" href="javascript:;">Unpublish Document</a>';
						}
						
					$doclistdata .= '</div>
				</div>								  
			</td>';
			$doclistdata .= '</tr>';
		}
		$application_id = (int) $appdoc->application_id;
		$applicationuploadcount = DB::select(
			'SELECT COUNT(DISTINCT list_id) AS cnt FROM application_documents WHERE application_id = ?',
			[$application_id]
		);
		$response['status'] 	= 	true;

		$response['doclistdata']	=	$doclistdata;
		$response['applicationuploadcount']	=	@$applicationuploadcount[0]->cnt;
		
		
		$applicationdocuments = \App\Models\ApplicationDocumentList::where('application_id', $application_id)->where('type', $appdoc->type)->get();
			$checklistdata = '<table class="table"><tbody>';
			foreach($applicationdocuments as $applicationdocument){
				$appcount = \App\Models\ApplicationDocument::where('list_id', $applicationdocument->id)->count();
				$checklistdata .= '<tr>';
				if($appcount >0){
					$checklistdata .= '<td><span class="check">' . \App\Helpers\IconHelper::render('check') . '</span></td>';
				}else{
					$checklistdata .= '<td><span class="round"></span></td>';
				}
					
					$checklistdata .= '<td>'.@$applicationdocument->document_type.'</td>';
					$checklistdata .= '<td><div class="circular-box cursor-pointer"><button class="transparent-button paddingNone">'.$appcount.'</button></div></td>';
					$checklistdata .= '<td><a data-aid="'.$application_id.'" data-type="'.$appdoc->type.'"data-typename="'.$appdoc->typename.'" data-id="'.$applicationdocument->id.'" class="openfileupload" href="javascript:;">' . \App\Helpers\IconHelper::render('plus') . '</a></td>';
				$checklistdata .= '</tr>';
			}
			$checklistdata .= '</tbody></table>';
		$response['checklistdata']	=	$checklistdata;
		$response['type']	=	$appdoc->type;
               $response['application_id']	= $appdoc->application_id;
			}else{
				$response['status'] 	= 	false;
				$response['message'] 	= 	'Please try again';
              $response['application_id']	= "";
			}
		}else{
			$response['status'] 	= 	false;
			$response['message'] 	= 	'No Record found';
          $response['application_id']	= "";
		}
		echo json_encode($response);
	}
	
	
	public function publishdoc(Request $request){
		if(\App\Models\ApplicationDocument::where('id', $request->appid)->exists()){
			$appdoc = \App\Models\ApplicationDocument::where('id', $request->appid)->first();
			$obj = \App\Models\ApplicationDocument::find($request->appid);
			$obj->status = $request->status;
			$saved = $obj->save();
			if($saved){
				$response['status'] 	= 	true;
				$response['message'] 	= 	'Record updated successfully';
				$doclists = \App\Models\ApplicationDocument::where('application_id',$appdoc->application_id)->orderby('created_at','DESC')->get();
		$doclistdata = ''; 
		foreach($doclists as $doclist){
			$docdata = \App\Models\ApplicationDocumentList::where('id', $doclist->list_id)->first();
			$doclistdata .= '<tr id="">';
				$doclistdata .= '<td>' . \App\Helpers\IconHelper::render('file') . ' '. $doclist->file_name.'<br>'.@$docdata->document_type.'</td>';
				$doclistdata .= '<td>';
				if($doclist->type == 'application'){ $doclistdata .= 'Application'; }else if($doclist->type == 'acceptance'){ $doclistdata .=  'Acceptance'; }else if($doclist->type == 'payment'){ $doclistdata .=  'Payment'; }else if($doclist->type == 'formi20'){ $doclistdata .=  'Form I 20'; }else if($doclist->type == 'visaapplication'){ $doclistdata .=  'Visa Application'; }else if($doclist->type == 'interview'){ $doclistdata .=  'Interview'; }else if($doclist->type == 'enrolment'){ $doclistdata .=  'Enrolment'; }else if($doclist->type == 'courseongoing'){ $doclistdata .=  'Course Ongoing'; }
				$doclistdata .= '</td>';
				$admin = \App\Models\Staff::find($doclist->user_id);
				
			$doclistdata .= '<td><span style="    position: relative;background: rgb(3, 169, 244);font-size: .8rem;height: 24px;line-height: 24px;min-width: 24px;width: 24px;color: #fff;display: block;font-weight: 600;letter-spacing: 1px;text-align: center;border-radius: 50%;overflow: hidden;">'.substr($admin->first_name, 0, 1).'</span>'.$admin->first_name.'</td>';
			$doclistdata .= '<td>'.date('Y-m-d',strtotime($doclist->created_at)).'</td>';
			$doclistdata .= '<td>';
			if($doclist->status == 1){
				$doclistdata .= '<span class="check">' . \App\Helpers\IconHelper::render('eye') . '</span>';
			}
				$doclistdata .= '<div class="dropdown d-inline">
					<button class="btn btn-primary dropdown-toggle" type="button" id="" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
					<div class="dropdown-menu">
						<a target="_blank" class="dropdown-item" href="'.asset('img/documents').'/'.$doclist->file_name.'">Preview</a>
						<a data-id="'.$doclist->id.'" class="dropdown-item deletenote" data-href="deleteapplicationdocs" href="javascript:;">Delete</a>
						<a download class="dropdown-item" href="'.asset('img/documents').'/'.$doclist->file_name.'">Download</a>';
						if($doclist->status == 0){
							$doclistdata .= '<a data-id="'.$doclist->id.'" class="dropdown-item publishdoc" href="javascript:;">Publish Document</a>';
						}else{
							$doclistdata .= '<a data-id="'.$doclist->id.'"  class="dropdown-item unpublishdoc" href="javascript:;">Unpublish Document</a>';
						}
						
					$doclistdata .= '</div>
				</div>								  
			</td>';
			$doclistdata .= '</tr>';
		}
		
		$response['status'] 	= 	true;

		$response['doclistdata']	=	$doclistdata;
		
			}else{
				$response['status'] 	= 	false;
				$response['message'] 	= 	'Please try again';
			}
		}else{
			$response['status'] 	= 	false;
			$response['message'] 	= 	'No Record found';
		}
		echo json_encode($response);
	}
	
	public function getapplications(Request $request){
		$client_id = $request->client_id;
		$applications = Application::where('client_id', '=', $client_id)->get(); 
		ob_start();
		?>
		<option value="">Choose Application</option>
		<?php
		foreach($applications as $application){
			$Products = \App\Models\Product::where('id', '=', @$application->product_id)->first(); 
			$Partners = \App\Models\Partner::where('id', '=', @$application->partner_id)->first(); 
			?>
		<option value="<?php echo $application->id; ?>">(#<?php echo $application->id; ?>) <?php echo @$Products->name; ?>  (<?php echo @$Partners->partner_name; ?>)</option>
			<?php
		}
		return ob_get_clean();
	}
  
    //Update Student id
    public function updateStudentId(Request $request){
		$requestData = $request->all(); //dd($requestData);
        $obj = Application::find($request->application_id);
        $obj->student_id = $request->student_id;
        $saved = $obj->save();
        if($saved){
            $response['status'] 	= 	true;
            $response['message']	=	'Application student id successfully updated.';
            $response['student_id']	=	$obj->student_id;
        } else {
            $response['status'] 	= 	false;
            $response['message']	=	'Please try again';
        }
        echo json_encode($response);
	}
  
    
	
    //Show Product Fee latest
    public function showproductfeelatest(Request $request){ //dd($request->all());
		$id = $request->id;
		
		// DEBUG: Check for various date formats in this application
		$appfeeoption_debug = ApplicationFeeOption::where('app_id', $id)->first();
		if($appfeeoption_debug) {
		    $appfeeoptiontype_debug = \App\Models\ApplicationFeeOptionType::where('fee_id', $appfeeoption_debug->id)->where('fee_option_type', 2)->get();
		    if($appfeeoptiontype_debug->count() > 0) {
		        \Log::info('=== DATE FORMAT CHECK - APP ID: ' . $id . ' ===');
		        foreach($appfeeoptiontype_debug as $idx => $fee_dbg) {
		            $date_val = $fee_dbg->date_paid;
		            \Log::info("Record #".($idx+1)." - Raw: '".$date_val."'");
		            
		            // Check format characteristics
		            $has_slash = strpos($date_val, '/') !== false;
		            $has_dot = strpos($date_val, '.') !== false;
		            $has_dash = strpos($date_val, '-') !== false;
		            $has_space = strpos($date_val, ' ') !== false;
		            
		            $format_info = [];
		            if($has_slash) $format_info[] = 'slash';
		            if($has_dot) $format_info[] = 'dot';
		            if($has_dash) $format_info[] = 'dash';
		            if($has_space) $format_info[] = 'space';
		            
		            \Log::info("Record #".($idx+1)." - Separators: " . implode(', ', $format_info));
		            \Log::info("Record #".($idx+1)." - Length: " . strlen($date_val));
		        }
		    }
		}
		
		ob_start();
		$appfeeoption = ApplicationFeeOption::where('app_id', $id)->first(); //dd($appfeeoption);
        $appInfo = Application::join('partners', 'applications.partner_id', '=', 'partners.id')
        ->join('products', 'applications.product_id', '=', 'products.id')
        ->select('applications.product_id','applications.partner_id','partners.commission_percentage','partners.partner_name','products.name as coursename','applications.partner_id')
        ->where('applications.id', $id)->first();
        //dd($appInfo);
        ?>
		<form method="post" action="<?php echo \URL::to('/applicationsavefeelatest'); ?>" name="applicationfeeformlatest" id="applicationfeeformlatest" autocomplete="off" enctype="multipart/form-data">
            <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <?php
            if( isset($appInfo->commission_percentage)) {
                if( $appInfo->commission_percentage !="" ) {
                    $commission_percentage = $appInfo->commission_percentage;
                } else {
                    $commission_percentage = 0;
                }
            } else {
                $commission_percentage = 0;
            }?>
            <?php
            if( isset($appfeeoption->bonus_amount) ) {
                if( $appfeeoption->bonus_amount !="" ) {
                    $bonus_amount = $appfeeoption->bonus_amount;
                } else {
                    $bonus_amount = 0;
                }
            } else {
                $bonus_amount = 0;
            } ?>

            <?php
            if( isset($appfeeoption->bonus_pending_amount) ) {
                if( $appfeeoption->bonus_pending_amount !="" ) {
                    $bonus_pending_amount = $appfeeoption->bonus_pending_amount;
                } else {
                    $bonus_pending_amount = 0;
                }
            } else {
                $bonus_pending_amount = 0;
            } ?>
            <div class="row">
                <div class="col-3 col-md-3 col-lg-3">
                    <div class="form-group">
						<label for="partner_commission_percentage">Commission(%)</label>
                        <input type="text" name="partner_commission_percentage" id="commission_percentage" style="width:100px;" value="<?php echo $commission_percentage;?>" >
                    </div>
                </div>

                <div class="col-3 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="bonus_amount">Total Bonus Amount($)</label>
                        <input type="text" name="bonus_amount" id="bonus_amount" style="width:100px;" value="<?php echo $bonus_amount;?>" >
                    </div>
                </div>

                <div class="col-3 col-md-3 col-lg-3">
                    <div class="form-group">
                        <label for="bonus_pending_amount">Bonus Pending($)</label>
                        <input type="text" name="bonus_pending_amount" id="bonus_pending_amount" style="width:100px;" value="<?php echo $bonus_pending_amount;?>" >
                    </div>
                </div>

                <!--<div class="col-3 col-md-3 col-lg-3">
                    <div class="form-group" style="margin-top: -10px;">
                        <label for="bonus_paid">Bonus Paid</label>
                        <select name="bonus_paid" class="form-control" style="width:100px;display:inline-block;" disabled>
                            <option value="">Select</option>
                            <option value="Yes" <?php //if( isset($appfeeoption->bonus_paid) && $appfeeoption->bonus_paid == 'Yes' ) { echo ' selected="selected"'; } else { echo '';} ?> >Yes</option>
                            <option value="No" <?php //if( isset($appfeeoption->bonus_paid) && $appfeeoption->bonus_paid == 'No' ) { echo ' selected="selected"'; } else { echo '';} ?> >No</option>
                        </select>
                    </div>
                </div>-->
            </div>

            <div class="row">
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="table-responsive">
                        <table class="table text_wrap" id="productitemviewlatest">
                            <thead>
                                <tr>
                                    <th style="white-space: initial;width: 150px;">Date</th>
                                    <th style="white-space: initial;width: 135px;">Tuition Fees Paid($)</th>
                                    <th style="white-space: initial;width: 100px;">Commission Percentage(%)</th>
                                    <th style="white-space: initial;width: 135px;">Commission Earned($)</th>
                                    <th style="white-space: initial;width: 135px;">Adjustment/Discount Entries($)</th>
                                    <th style="white-space: initial;width: 135px;">Commission Claimed($)</th>
                                    <th style="white-space: initial;width: 135px;">Commission Received</th>
                                    <th style="white-space: initial;width: 180px;">Source</th>
                                </tr>
                            </thead>
                            <tbody class="tdata">
                            <?php
                            $totl = 0.00;
                            $totl_commission = 0.00;
                            $discount = 0.00;

                            $total_commission_claimed = 0.00;
                            $sum_of_paid_commission = 0;
                            $sum_of_pending_commission = 0;
                            $sum_of_adjustment = 0;
                            $sum_of_anticipated_commission = 0;
                            if($appfeeoption)
                            {
                                $appfeeoptiontype = \App\Models\ApplicationFeeOptionType::where('fee_id', $appfeeoption->id)->where('fee_option_type', 2)->get();
                                
                                // Check if there are any records to display
                                if($appfeeoptiontype->count() > 0)
                                {
                                    foreach($appfeeoptiontype as $fee)
                                    {
                                        $totl += $fee->total_fee;

                                        //Total Commission
                                        if( isset($fee->total_fee) && $fee->total_fee != ""){
                                            $total_fee_per_line = $fee->total_fee;

                                            //if commission per line is not empty
                                            if( isset($fee->commission_percentage) && $fee->commission_percentage != ""){
                                                $commission_per_line  = $fee->commission;
                                            } else  {
                                                $commission_per_line  = ($total_fee_per_line * $commission_percentage)/100;
                                            }
                                        } else {
                                            $commission_per_line  = 0;
                                        }
                                        $totl_commission += $commission_per_line;

                                        $sum_of_adjustment += $fee->adjustment_discount_entry;
                                        $total_commission_claimed  += $fee->commission_claimed;

                                        //Paid Commission
                                        if( $fee->claimed_or_not == 'Yes') {
                                            $sum_of_paid_commission +=  $fee->commission_claimed;
                                        }
                                        //Pending Commission
                                        if( $fee->claimed_or_not == 'No' ) {
                                            $sum_of_pending_commission +=  $fee->commission_claimed;
                                        }
                                        //Anticipated Commission
                                        if( $fee->claimed_or_not == 'Anticipated' ) {
                                            $sum_of_anticipated_commission +=  $fee->commission_claimed;
                                        }
                                        ?>

                                        <tr class="add_fee_option cus_fee_option">
                                            <td>
                                                <input type="hidden" value="2"  name="fee_option_type[]">
                                                <?php
                                                // Convert date from DD/MM/YYYY or DD.MM.YYYY to YYYY-MM-DD for flatpickr
                                                $formatted_date = '';
                                                if (!empty($fee->date_paid)) {
                                                    try {
                                                        // Normalize: replace dots with slashes to handle both DD.MM.YYYY and DD/MM/YYYY formats
                                                        $normalized_date = str_replace('.', '/', $fee->date_paid);
                                                        $formatted_date = \Carbon\Carbon::createFromFormat('d/m/Y', $normalized_date)->format('Y-m-d');
                                                    } catch (\Exception $e) {
                                                        // If date parsing fails, keep original value to prevent data loss
                                                        $formatted_date = $fee->date_paid;
                                                    }
                                                }
                                                ?>
                                                <input type="text" data-valid="required" value="<?php echo $formatted_date;?>" class="form-control date_paid" name="date_paid[]">
                                            </td>
                                            <td>
                                                <input type="number" data-valid="required" value="<?php echo $fee->total_fee;?>" class="form-control total_fee_am_2nd" name="total_fee[]">
                                            </td>
                                            <td>
                                                <?php
                                                //if commission per line is not empty
                                                if( isset($fee->commission_percentage) && $fee->commission_percentage != ""){ ?>
                                                    <input type="number" data-valid="required" value="<?php echo $fee->commission_percentage;?>" class="form-control commission_percentage" name="commission_percentage[]">
                                                <?php } else { ?>
                                                    <input type="number" data-valid="required" value="<?php echo $commission_percentage;?>" class="form-control commission_percentage" name="commission_percentage[]">
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <input type="text" value="<?php echo $fee->commission;?>" class="form-control commission_cal" readonly>
                                                <input type="hidden" value="<?php echo $fee->commission;?>" class="form-control commission_cal_hidden" name="commission[]">
                                            </td>
                                            <td>
                                                <input type="number" data-valid="required" value="<?php echo $fee->adjustment_discount_entry;?>" class="form-control adjustment_discount_entry" name="adjustment_discount_entry[]">
                                            </td>
                                            <td>
                                                <input type="text" value="<?php echo $fee->commission_claimed;?>" class="form-control commission_claimed" readonly>
                                                <input type="hidden" value="<?php echo $fee->commission_claimed;?>" class="form-control commission_claimed_hidden" name="commission_claimed[]">
                                            </td>

                                            <td>
                                                <select class="form-control" data-valid="required"  name="claimed_or_not[]" >
                                                    <option value="">Select</option>
                                                    <option value="Yes" <?php if( $fee->claimed_or_not == 'Yes' ) { echo ' selected="selected"'; } else { echo '';} ?> >Yes</option>
                                                    <option value="No" <?php if( $fee->claimed_or_not == 'No' ) { echo ' selected="selected"'; } else { echo '';} ?> >No</option>
                                                    <option value="Anticipated" <?php if( $fee->claimed_or_not == 'Anticipated' ) { echo ' selected="selected"'; } else { echo '';} ?> >Anticipated</option>
                                                </select>
                                            </td>

                                            <td>
                                                <select class="form-control" data-valid="required"  name="source[]" >
                                                    <option value="">Select</option>
                                                    <option value="Prededuct" <?php if( $fee->source == 'Prededuct' ) { echo ' selected="selected"'; } else { echo '';} ?> >Prededuct</option>
                                                    <option value="Reported by college" <?php if( $fee->source == 'Reported by college' ) { echo ' selected="selected"'; } else { echo '';} ?> >Reported by college</option>
                                                    <option value="Calculated by us" <?php if( $fee->source == 'Calculated by us' ) { echo ' selected="selected"'; } else { echo '';} ?> >Calculated by us</option>
                                                    <option value="Told by student" <?php if( $fee->source == 'Told by student' ) { echo ' selected="selected"'; } else { echo '';} ?> >Told by student</option>
                                                    <option value="Bonus" <?php if( $fee->source == 'Bonus' ) { echo ' selected="selected"'; } else { echo '';} ?> >Bonus</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <?php
                                    } //end foreach
                                }
                                else
                                {
                                    // No fee records exist, create default empty row
                                    ?>
                                    <tr class="add_fee_option cus_fee_option">
                                        <td>
                                            <input type="hidden" value="2"  name="fee_option_type[]">
                                            <input type="text" data-valid="required" value="" class="form-control date_paid" name="date_paid[]">
                                        </td>
                                        <td>
                                            <input type="number" data-valid="required" value="" class="form-control total_fee_am_2nd" name="total_fee[]">
                                        </td>

                                        <td>
                                            <input type="number" data-valid="required" value="<?php echo $commission_percentage;?>" class="form-control commission_percentage" name="commission_percentage[]">
                                        </td>

                                        <td>
                                            <input type="text" value="" class="form-control commission_cal" readonly>
                                            <input type="hidden" value="" class="form-control commission_cal_hidden" name="commission[]">
                                        </td>

                                        <td>
                                            <input type="number" data-valid="required" value="" class="form-control adjustment_discount_entry" name="adjustment_discount_entry[]">
                                        </td>
                                        <td>
                                            <input type="text" value="" class="form-control commission_claimed" readonly>
                                            <input type="hidden" value="" class="form-control commission_claimed_hidden" name="commission_claimed[]">
                                        </td>
                                        <td>
                                            <select class="form-control" data-valid="required"  name="claimed_or_not[]" >
                                                <option value="">Select</option>
                                                <option value="Yes">Yes</option>
                                                <option value="No">No</option>
                                                <option value="Anticipated">Anticipated</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-control" data-valid="required"  name="source[]" >
                                                <option value="">Select</option>
                                                <option value="Prededuct">Prededuct</option>
                                                <option value="Reported by college">Reported by college</option>
                                                <option value="Calculated by us">Calculated by us</option>
                                                <option value="Told by student">Told by student</option>
                                                <option value="Bonus">Bonus</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php
                                }
							}
                            else
                            { ?>
                                    <tr class="add_fee_option cus_fee_option">
                                        <td>
                                            <input type="hidden" value="2"  name="fee_option_type[]">
                                            <input type="text" data-valid="required" value="" class="form-control date_paid" name="date_paid[]">
                                        </td>
                                        <td>
                                            <input type="number" data-valid="required" value="" class="form-control total_fee_am_2nd" name="total_fee[]">
                                        </td>

                                        <td>
                                            <input type="number" data-valid="required" value="<?php echo $commission_percentage;?>" class="form-control commission_percentage" name="commission_percentage[]">
                                        </td>

                                        <td>
                                            <input type="text" value="" class="form-control commission_cal" readonly>
                                            <input type="hidden" value="" class="form-control commission_cal_hidden" name="commission[]">
                                        </td>

                                        <td>
                                            <input type="number" data-valid="required" value="" class="form-control adjustment_discount_entry" name="adjustment_discount_entry[]">
                                        </td>
                                        <td>
                                            <input type="text" value="" class="form-control commission_claimed" readonly>
                                            <input type="hidden" value="" class="form-control commission_claimed_hidden" name="commission_claimed[]">
                                        </td>
                                        <td>
                                            <select class="form-control" data-valid="required"  name="claimed_or_not[]" >
                                                <option value="">Select</option>
                                                <option value="Yes">Yes</option>
                                                <option value="No">No</option>
                                                <option value="Anticipated">Anticipated</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-control" data-valid="required"  name="source[]" >
                                                <option value="">Select</option>
                                                <option value="Prededuct">Prededuct</option>
                                                <option value="Reported by college">Reported by college</option>
                                                <option value="Calculated by us">Calculated by us</option>
                                                <option value="Told by student">Told by student</option>
                                                <option value="Bonus">Bonus</option>
                                            </select>
                                        </td>
                                    </tr>
                            <?php
                            }
                            $net = $totl -  $discount;
                            ?>
							</tbody>
                            <tfoot>
                                <tr>
                                    <td style="text-align: right;"><b>Total</b></td>
                                    <td class="net_totl text-info total_fees_paid"><?php if( isset($totl) && $totl != "" ){ echo $totl;}?></td>
                                    <td></td>
                                    <td class="net_totl text-info total_commission_earned"><?php if( isset($totl_commission) && $totl_commission != "" ){ echo $totl_commission;}?></td>

                                    <td class="net_totl text-info total_adjustment_discount_entry"><?php if( isset($sum_of_adjustment) && $sum_of_adjustment != "" ){ echo $sum_of_adjustment;}?></td>
                                    <td colspan="3" class="net_totl text-info total_commission_claimed"><?php if( isset($total_commission_claimed) && $total_commission_claimed != "" ){ echo $total_commission_claimed;}?></td>
                                </tr>
                            </tfoot>
						</table>
					</div>
				</div>

                <div class="col-12 col-md-12 col-lg-12" style="margin-top: 10px;">
                    <div style="float:left;">
                        <div class="fee_option_addbtn_latest" style="display: inline-block;">
                            <a href="#" class="btn btn-primary"><?php echo \App\Helpers\IconHelper::render('plus'); ?> Add Fee</a>
                        </div>

                        <?php
                        /*if( isset($appfeeoption->bonus_paid ) && $appfeeoption->bonus_paid  != ""){
                            if( $appfeeoption->bonus_paid  == "Yes"){
                                $sum_of_paid_commission = $sum_of_paid_commission + $appfeeoption->bonus_amount;
                                $sum_of_pending_commission = $sum_of_pending_commission;
                            }
                            if( $appfeeoption->bonus_paid  == "No"){
                                $sum_of_paid_commission = $sum_of_paid_commission;
                                $sum_of_pending_commission = $sum_of_pending_commission + $appfeeoption->bonus_amount;
                            }
                        } else {
                            $sum_of_paid_commission = $sum_of_paid_commission;
                            $sum_of_pending_commission = $sum_of_pending_commission;
                        } */
                        $sum_of_paid_commission = $sum_of_paid_commission;
                        $sum_of_pending_commission = $sum_of_pending_commission;
                        $sum_of_anticipated_commission = $sum_of_anticipated_commission;

                        ?>
                        <div style="display: inline-block;margin-left:95px;">
                            <span><b>Paid Commission - </b></span>
                            <span id="paid_commission"> <?php if( isset($sum_of_paid_commission) && $sum_of_paid_commission != "" ){ echo $sum_of_paid_commission;}?></span>
                        </div>

                        <div style="display: inline-block;margin-left:95px;">
                            <span><b>Pending Commission - </b></span>
                            <span id="pending_commission"> <?php if( isset($sum_of_pending_commission) && $sum_of_pending_commission != "" ){ echo $sum_of_pending_commission;}?></span>
                        </div>

                        <div style="display: inline-block;margin-left:95px;">
                            <span><b>Anticipated Commission - </b></span>
                            <span id="anticipated_commission"> <?php if( isset($sum_of_anticipated_commission) && $sum_of_anticipated_commission != "" ){ echo $sum_of_anticipated_commission;}?></span>
                        </div>
                    </div>
                    <div style="float:right;">
                        <button onclick="customValidate('applicationfeeformlatest')" type="button" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
			</div>
		</form>
		<?php
		return ob_get_clean();
	}

   //Save application Fee latest
	public function applicationsavefeelatest(Request $request){
		$requestData = $request->all(); //dd($requestData);

        $id = $request->id;

		$appInfoArr = Application::select('id','partner_id')->where('id', $id)->first(); //dd($appInfoArr);
        if($appInfoArr){
            // Update the commission_percentage in the partners table
            $partner = Partner::find($appInfoArr->partner_id); // Find the partner by ID
            if ($partner) {
                $partner->commission_percentage = $requestData['partner_commission_percentage'];
                $partner->save();
            }
        }

        if( isset($requestData['bonus_amount']) && $requestData['bonus_amount'] != ""){
            $bonus_amount = $requestData['bonus_amount'];
        } else {
            $bonus_amount = 0;
        }
        if( isset($requestData['bonus_pending_amount']) && $requestData['bonus_pending_amount'] != ""){
            $bonus_pending_amount = $requestData['bonus_pending_amount'];
        } else {
            $bonus_pending_amount = 0;
        }
		if(ApplicationFeeOption::where('app_id', $request->id)->exists())
        {
			$o = ApplicationFeeOption::where('app_id', $request->id)->first();
			$obj = ApplicationFeeOption::find($o->id);
			$obj->user_id = Auth::user()->id;
			$obj->app_id = $request->id;
            $obj->bonus_amount = $bonus_amount;
            $obj->bonus_pending_amount = $bonus_pending_amount;
            $saved = $obj->save();
			if($saved)
            {
				ApplicationFeeOptionType::where('fee_id', $obj->id)->where('fee_option_type', 2)->delete();
				$totl = 0;
                $totl_commission = 0;
                $sum_of_option_yes = 0;
                $sum_of_option_no = 0;
                $sum_of_adjustment = 0;
                $totl_commission_claimed = 0;
                $sum_of_option_anticipated = 0;
				for($i = 0; $i< count($requestData['date_paid']); $i++)
                {
					$totl += $requestData['total_fee'][$i];
                    $totl_commission +=  $requestData['commission'][$i];
                    $totl_commission_claimed +=  $requestData['commission_claimed'][$i];
                    if($requestData['claimed_or_not'][$i] == 'Yes') {
                        $sum_of_option_yes +=  $requestData['commission_claimed'][$i];
                    }
                    if($requestData['claimed_or_not'][$i] == 'No') {
                        $sum_of_option_no +=  $requestData['commission_claimed'][$i];
                    }

                    if($requestData['claimed_or_not'][$i] == 'Anticipated') {
                        $sum_of_option_anticipated +=  $requestData['commission_claimed'][$i];
                    }
                    $sum_of_adjustment +=  $requestData['adjustment_discount_entry'][$i];

					$objs = new ApplicationFeeOptionType;
					$objs->fee_id = $obj->id;
					$objs->fee_option_type = 2; //other fee
                    // Convert date from YYYY-MM-DD (flatpickr format) back to DD/MM/YYYY (database format)
                    $date_to_save = $requestData['date_paid'][$i];
                    if (!empty($date_to_save)) {
                        try {
                            $date_to_save = \Carbon\Carbon::createFromFormat('Y-m-d', $date_to_save)->format('d/m/Y');
                        } catch (\Exception $e) {
                            // If conversion fails, keep original value to prevent data loss
                            $date_to_save = $requestData['date_paid'][$i];
                        }
                    }
                    $objs->date_paid = $date_to_save;
                    $objs->total_fee = $requestData['total_fee'][$i];
                    $objs->commission_percentage = $requestData['commission_percentage'][$i];
					$objs->commission = $requestData['commission'][$i];
                    $objs->adjustment_discount_entry = $requestData['adjustment_discount_entry'][$i];
                    $objs->commission_claimed = $requestData['commission_claimed'][$i];
                    $objs->claimed_or_not = $requestData['claimed_or_not'][$i];
                    $objs->source = $requestData['source'][$i];
                    $saved = $objs->save();
                }
                // Recalculate from DB (source of truth) to avoid form/array sync issues
                $recalculated_total = ApplicationFeeOptionType::where('fee_id', $obj->id)
                    ->where('fee_option_type', 2)
                    ->sum('total_fee');
                //Update commision related col in table
                $obj3 = ApplicationFeeOption::find($obj->id);
                $obj3->fee_reported_by_college = $recalculated_total;

                $app_fee_info = ApplicationFeeOption::where('id', $obj->id)->first(); //dd($app_fee_info);
                /*if(isset($app_fee_info['bonus_amount']) && $app_fee_info['bonus_amount'] !=""){
                    $bonus_amount = $app_fee_info['bonus_amount'];
                } else {
                    $bonus_amount = "0.00";
                }*/
                /*$commission_as_per_fee_reported = $totl_commission_claimed + $bonus_amount;
                $commission_as_per_fee_reported = $commission_as_per_fee_reported - $sum_of_adjustment;*/

                $commission_as_per_fee_reported = $totl_commission_claimed;
                $obj3->commission_as_per_fee_reported = $commission_as_per_fee_reported; //Total commission claimed + bonus amount - adjustment amount

                /*if( isset($app_fee_info['bonus_paid']) && $app_fee_info['bonus_paid']  != ""){
                    if( $app_fee_info['bonus_paid']  == "Yes"){
                        $obj3->commission_paid_as_per_fee_reported = $sum_of_option_yes + $bonus_amount;
                        $obj3->commission_pending = $sum_of_option_no;
                    }
                    if( $app_fee_info['bonus_paid'] == "No"){
                        $obj3->commission_paid_as_per_fee_reported = $sum_of_option_yes;
                        $obj3->commission_pending = $sum_of_option_no + $bonus_amount;
                    }
                }
                else {
                    $obj3->commission_paid_as_per_fee_reported = $sum_of_option_yes;
                    $obj3->commission_pending = $sum_of_option_no;
                }*/
                $obj3->commission_paid_as_per_fee_reported = $sum_of_option_yes;
                $obj3->commission_pending = $sum_of_option_no;
                $obj3->commission_payable_as_per_anticipated_fee = $sum_of_option_anticipated;
                $saved3 = $obj3->save();

				$discount = 0.00;
				$response['status'] 	= 	true;
                $response['message']	=	'Other Fee Option added successfully';
                $response['totalfee']	=	$recalculated_total;
                $response['discount']	=	$discount;
			}else{
				$response['status'] 	= 	false;
				$response['message']	=	'Record not found';
			}
		}
        else
        {
			$obj = new ApplicationFeeOption;
			$obj->user_id = Auth::user()->id;
			$obj->app_id = $request->id;
            $obj->bonus_amount = $bonus_amount;
            $obj->bonus_pending_amount = $bonus_pending_amount;
            $saved = $obj->save();
			if($saved)
            {
				$date_paid = $requestData['date_paid'];
				$totl = 0;
                $totl_commission = 0;
                $sum_of_option_yes = 0;
                $sum_of_option_no = 0;
                $sum_of_adjustment = 0;
                $sum_of_option_anticipated = 0;
                $totl_commission_claimed = 0;
				for($i = 0; $i< count($date_paid); $i++){
					$totl += $requestData['total_fee'][$i];
                    $totl_commission +=  $requestData['commission'][$i];
                    $totl_commission_claimed +=  $requestData['commission_claimed'][$i];
                    if($requestData['claimed_or_not'][$i] == 'Yes') {
                        $sum_of_option_yes +=  $requestData['commission_claimed'][$i];
                    }
                    if($requestData['claimed_or_not'][$i] == 'No') {
                        $sum_of_option_no +=  $requestData['commission_claimed'][$i];
                    }
                    if($requestData['claimed_or_not'][$i] == 'Anticipated') {
                        $sum_of_option_anticipated +=  $requestData['commission_claimed'][$i];
                    }
                    $sum_of_adjustment +=  $requestData['adjustment_discount_entry'][$i];

					$objs = new ApplicationFeeOptionType;
					$objs->fee_id = $obj->id;
					$objs->fee_option_type = 2; //other fee
                    // Convert date from YYYY-MM-DD (flatpickr format) back to DD/MM/YYYY (database format)
                    $date_to_save = $requestData['date_paid'][$i];
                    if (!empty($date_to_save)) {
                        try {
                            $date_to_save = \Carbon\Carbon::createFromFormat('Y-m-d', $date_to_save)->format('d/m/Y');
                        } catch (\Exception $e) {
                            // If conversion fails, keep original value to prevent data loss
                            $date_to_save = $requestData['date_paid'][$i];
                        }
                    }
                    $objs->date_paid = $date_to_save;
                    $objs->total_fee = $requestData['total_fee'][$i];
                    $objs->commission_percentage = $requestData['commission_percentage'][$i];
					$objs->commission = $requestData['commission'][$i];
                    $objs->adjustment_discount_entry = $requestData['adjustment_discount_entry'][$i];
                    $objs->commission_claimed = $requestData['commission_claimed'][$i];
                    $objs->claimed_or_not = $requestData['claimed_or_not'][$i];
                    $objs->source = $requestData['source'][$i];
                    $saved = $objs->save();
                }

              	// Recalculate from DB (source of truth) to avoid form/array sync issues
                $recalculated_total = ApplicationFeeOptionType::where('fee_id', $obj->id)
                    ->where('fee_option_type', 2)
                    ->sum('total_fee');
                //Update commision related col in table
                $obj3 = ApplicationFeeOption::find($obj->id);
                $obj3->fee_reported_by_college = $recalculated_total;

                $app_fee_info = ApplicationFeeOption::where('id', $obj->id)->first(); //dd($app_fee_info);
                /*if(isset($app_fee_info['bonus_amount']) && $app_fee_info['bonus_amount'] !=""){
                    $bonus_amount = $app_fee_info['bonus_amount'];
                } else {
                    $bonus_amount = "0.00";
                }*/
                //$commission_as_per_fee_reported = $totl_commission_claimed + $bonus_amount;
                //$commission_as_per_fee_reported = $commission_as_per_fee_reported - $sum_of_adjustment;
                $commission_as_per_fee_reported = $totl_commission_claimed;
                $obj3->commission_as_per_fee_reported = $commission_as_per_fee_reported; //Total commission claimed + bonus amount - adjustment amount

                /*if( isset($app_fee_info['bonus_paid']) && $app_fee_info['bonus_paid']  != ""){
                    if( $app_fee_info['bonus_paid']  == "Yes"){
                        $obj3->commission_paid_as_per_fee_reported = $sum_of_option_yes + $bonus_amount;
                        $obj3->commission_pending = $sum_of_option_no;
                    }
                    if( $app_fee_info['bonus_paid'] == "No"){
                        $obj3->commission_paid_as_per_fee_reported = $sum_of_option_yes;
                        $obj3->commission_pending = $sum_of_option_no + $bonus_amount;
                    }
                }
                else {
                    $obj3->commission_paid_as_per_fee_reported = $sum_of_option_yes;
                    $obj3->commission_pending = $sum_of_option_no;
                }*/
                $obj3->commission_paid_as_per_fee_reported = $sum_of_option_yes;
                $obj3->commission_pending = $sum_of_option_no;
                $obj3->commission_payable_as_per_anticipated_fee = $sum_of_option_anticipated;
                $saved3 = $obj3->save();

                $discount = 0.00;
				$response['status'] 	= 	true;
				$response['message']	=	'Other Fee Option added successfully';
				$response['totalfee']	=	$recalculated_total;
				$response['discount']	=	$discount;
			}else{
				$response['status'] 	= 	false;
				$response['message']	=	'Record not found';
			}
		}
		if (!empty($response['status'])) {
			$partnerIdForStudentTab = Application::where('id', $request->id)->value('partner_id');
			PartnersController::forgetPartnerStudentTabCache($partnerIdForStudentTab ? (int) $partnerIdForStudentTab : null);
		}
		echo json_encode($response);
	}

    
   
   
    
  	public function overdueApplicationList(Request $request)
	{
		//check authorization start
        /* if($check)
        {
            return Redirect::to('/admin/dashboard')->with('error',config('constants.unauthorized'));
        } */
		//check authorization end
	    //$allstages = Application::select('stage')->groupBy('stage')->get();
		//$query 		= Application::where('id', '!=', '')->with(['application_assignee']);
        //dd(Carbon::now()->subDays(15)->toDateTimeString());
        $allstages = Application::select('stage')->where('status', '!=', 2)->groupBy('stage')->get();
        $allpartners = Partner::select('partner_name','id')->where('status', '=', 0)->get();

		$query 	   = Application::query()->where('stage', '=', 'Coe processing')->where('updated_at', '<=', Carbon::now()->subDays(15)->toDateTimeString() )->where('status', '!=', 2)->with(['application_assignee']);
        if ($request->has('partner'))
		{
			$partner 		= 	$request->input('partner');
			if(trim($partner) != '')
			{
				$query->where('partner_id', '=', $partner);
			}
		}
		if ($request->has('assignee'))
		{
			$assignee 		= 	$request->input('assignee');
			if(trim($assignee) != '')
			{
				$query->where('user_id', '=', $assignee);
			}
		}
		if ($request->has('stage'))
		{
			$stage 		= 	$request->input('stage');
			if(trim($stage) != '')
			{
				$query->where('stage', '=', $stage);
			}
		}

        if ($request->has('status'))
		{
			$status 		= 	$request->input('status');
			if(trim($status) != '')
			{
				$query->where('status', '=', $status);
			}
		}
		$totalData 	= $query->count();
		$lists	= $query->sortable(['id' => 'desc'])->paginate(10);
        return view('Admin.applications.overdue', compact(['lists', 'totalData','allstages','allpartners']));
    }
  
    public function finalizeApplicationList(Request $request)
	{
		// Finalized tab: COE end-of-pipeline stages. Default status remains Discontinued (2)
		// to preserve existing list behaviour; status filter replaces that default (no AND stack).
		$finalizeStages = ['Coe processing', 'Coe issued', 'Refund', 'Coe Cancelled'];
		$allstages = collect($finalizeStages)->map(function ($stage) {
			return (object) ['stage' => $stage];
		});
        $allpartners = Partner::select('partner_name','id')->where('status', '=', 0)->get();

		$query = Application::query()
			->whereIn('stage', $finalizeStages)
			->with(['application_assignee']);

		$statusFilter = $request->input('status');
		if ($request->has('status') && trim((string) $statusFilter) !== '') {
			$query->where('status', '=', $statusFilter);
		} else {
			$query->where('status', '=', 2);
		}

        if ($request->has('partner'))
		{
			$partner 		= 	$request->input('partner');
			if(trim($partner) != '')
			{
				$query->where('partner_id', '=', $partner);
			}
		}
		if ($request->has('assignee'))
		{
			$assignee 		= 	$request->input('assignee');
			if(trim($assignee) != '')
			{
				$query->where('user_id', '=', $assignee);
			}
		}
		if ($request->has('stage'))
		{
			$stage 		= 	$request->input('stage');
			if(trim($stage) != '')
			{
				// Only allow a stage within the finalized set (ignores arbitrary values).
				if (in_array($stage, $finalizeStages, true)) {
					$query->where('stage', '=', $stage);
				}
			}
		}

		$totalData 	= $query->count();
		$lists	= $query->sortable(['id' => 'desc'])->paginate(10);
        return view('Admin.applications.finalize', compact(['lists', 'totalData','allstages','allpartners']));
    }

    
}
