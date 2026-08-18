<?php

namespace App\Http\Controllers\Admin\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\SmsTemplate;
use App\Models\ActivitiesLog;
use App\Models\Application;
use App\Models\FollowupConsultant;
use App\Traits\ClientHelpers;
use App\Traits\ClientQueries;
use App\Traits\ClientAuthorization;
use App\Services\SearchService;
use App\Services\ClientExportService;
use App\Services\ClientLeadListExportService;
use App\Models\Staff;
use App\Support\StaffClientVisibility;
use App\Support\ClientDetailTab;
use App\Models\CheckinLog;
use App\Models\ClientPhone;
use App\Models\ClientEmail;
use App\Models\ClientTestScore;
use App\Helpers\PhoneHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Carbon\Carbon;
use App\Services\Sms\UnifiedSmsManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Core client CRUD and listing operations
 * 
 * Methods to move from ClientsController:
 * - index
 * - archived
 * - create
 * - store
 * - edit
 * - clientdetail
 * - leaddetail
 * - changetype
 * - change_assignee
 * - removetag
 * - save_tag
 * - getallclients
 * - getrecipients
 * - getonlyclientrecipients
 * - updatesessioncompleted
 */
class ClientController extends Controller
{
    use ClientHelpers, ClientQueries, ClientAuthorization;

    protected ?bool $googleReviewSmsTemplateExistsCache = null;

    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
	{
		// Check authorization using trait
		if (!$this->hasModuleAccess('20')) {
			// Return empty result set for users without module access
			$lists = $this->getEmptyClientQuery()->paginate(20);
			$totalData = 0;
			return view($this->getClientViewPath('clients.index'), compact(['lists', 'totalData']));
		}
		
		// Base + filters (default type=client when Type filter empty — C-13)
		$query = $this->applyClientFilters($this->getBaseClientQuery(), $request);
		$totalData = $query->count();

		// Eager-load count of applications that are in progress (status = 0) and office/branch
		$query->withCount(['applications as in_progress_applications_count' => function ($q) {
			$q->where('status', 0);
		}])->with('office');
		
		// Paginate results
		$lists = $query->sortable(['id' => 'desc'])->paginate(20);
		
		// Return appropriate view based on context
		return view($this->getClientViewPath('clients.index'), compact(['lists', 'totalData']));
	}

	/**
	 * Export filtered client list as CSV.
	 */
	public function exportList(Request $request)
	{
		$user = Auth::guard('admin')->user();
		if (! $user instanceof Staff || ! $user->isSuperAdmin()) {
			return redirect()->route('clients.index')
				->with('error', config('constants.unauthorized'));
		}

		$query = $this->applyClientFilters($this->getBaseClientQuery(), $request);

		return app(ClientLeadListExportService::class)
			->export($query, 'client', 'clients_export');
	}

	public function archived(Request $request)
	{
		// Get archived clients query with automatic agent filtering
		$query = $this->getArchivedClientQuery();
		
		// Apply search and filters
		$query = $this->applyArchivedFilters($query, $request);
		
		$totalData = $query->count();
		$lists = $query->sortable(['id' => 'desc'])->paginate(20)->appends($request->except('page'));
		
		// Assignees for filter dropdown (staff)
		$assignees = \App\Models\Staff::select('id', 'first_name', 'last_name')
			->where('status', 1)
			->orderBy('first_name')
			->get();
		
		// Staff who have archived at least one client (for "Archived by" filter)
		$archivedByUsers = \App\Models\Staff::select('id', 'first_name', 'last_name')
			->whereIn('id', function ($q) {
				$q->select('archived_by')->from('admins')->where('is_archived', 1)->whereNotNull('archived_by');
			})
			->orderBy('first_name')
			->get();
		
		return view($this->getClientViewPath('archived.index'), compact(['lists', 'totalData', 'assignees', 'archivedByUsers']));
	}

    public function edit(Request $request, $id = NULL)
	{
		 
        $showAlert = false;
		if ($request->isMethod('post'))
		{
			$requestData 		= 	$request->all();

			$editClientId = (int) ($requestData['id'] ?? 0);
			$editClientRow = $editClientId > 0 ? Admin::find($editClientId) : null;
			if (! $editClientRow || ! $this->canEditClient($editClientRow)) {
				return redirect()->route('clients.index')->with('error', config('constants.unauthorized'));
			}

			//echo '<pre>'; print_r($requestData); die;

			// Normalize email/email_type to arrays (handles legacy form, cached views, or string values)
			if (!is_array($request->get('email'))) {
				$emailVal = trim((string)($request->get('email', '') ?? ''));
				$request->merge([
					'email' => $emailVal !== '' ? [$emailVal] : [],
					'email_type' => [$request->get('email_type', 'Personal') ?: 'Personal'],
				]);
			}
			if (is_array($request->get('email')) && !is_array($request->get('email_type'))) {
				$etype = $request->get('email_type', 'Personal') ?: 'Personal';
				$request->merge(['email_type' => array_fill(0, count($request->get('email')), $etype)]);
			}
          
            //Get Db values of related files
			$db_arr = Admin::select('related_files')->where('id', $requestData['id'] ?? null)->get();
			$requestData = $request->all();
          
			$this->validate($request, [
              'first_name' => 'required|max:255',
              'last_name' => 'required|max:255',
              'gender' => 'required|in:Male,Female,Other',
              'email' => 'required|array|min:1',
              'email.*' => 'required|email|max:255',

              'contact_type' => 'required|array',
              'contact_type.*' => 'required|in:Personal,Office,Work,Mobile,Business,Secondary,Father,Mother,Brother,Sister,Uncle,Aunt,Cousin,Others,Partner,Not In Use',

              'client_phone' => 'required|array',
              'client_phone.*' => 'required|max:255',

              'email_type' => 'nullable|array',
              'email_type.*' => 'nullable|in:Personal,Work,Business,Secondary,Additional,Sister,Brother,Father,Mother,Uncle,Auntie',
              'email_type_modal' => 'nullable|in:Personal,Work,Business,Secondary,Additional,Sister,Brother,Father,Mother,Uncle,Auntie',

              'office' => 'nullable|exists:branches,id',

              'service' => 'required|string|max:255',
              'source' => 'required|string|max:255',

            ]);

            // Primary (first) email must be unique in admins table
            $emails = $requestData['email'] ?? [];
            if (!empty($emails)) {
                $primaryEmail = trim($emails[0]);
                $existing = Admin::where('id', '!=', $requestData['id'])
                    ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower($primaryEmail)])
                    ->exists();
                if ($existing) {
                    return redirect()->back()->withInput()->with('error', 'The primary email address is already in use by another client.');
                }
            }
          
             if ( isset($requestData['contact_type']) && count(array_keys($requestData['contact_type'] , "Personal")) > 1) {
                //echo "Error: 'Personal' contact type can only be used once.";
                return redirect()->back()->withInput()->with('error', "Error: 'Personal' contact type can only be used once.");
            }
            // Email type 'Personal' can only be used once per client
            $emailTypes = $requestData['email_type'] ?? [];
            if (is_array($emailTypes) && count(array_filter($emailTypes, function($t) { return trim($t ?? '') === 'Personal'; })) > 1) {
                return redirect()->back()->withInput()->with('error', "Error: 'Personal' email type can only be used once.");
            }
          
			$related_files = '';
	        if(isset($requestData['related_files'])){
	            $relatedFilesCount = count($requestData['related_files']);
	            for($i=0; $i<$relatedFilesCount; $i++){
	                $related_files .= $requestData['related_files'][$i].',';
	            }

	        }
	         $dob = '';
	        if(array_key_exists("dob",$requestData) && $requestData['dob'] != ''){
	           $dobs = explode('/', $requestData['dob']);
	          $dob = $dobs[2].'-'.$dobs[1].'-'. $dobs[0];
	        }
	         $visaExpiry = '';
	        if(array_key_exists("visaExpiry",$requestData) && $requestData['visaExpiry'] != '' ){
	           $visaExpirys = explode('/', $requestData['visaExpiry']);
	          $visaExpiry = $visaExpirys[2].'-'.$visaExpirys[1].'-'. $visaExpirys[0];
	        }
			$obj		= 	Admin::find(@$requestData['id']);
			$first_name = substr(@$requestData['first_name'], 0, 4);
				$obj->first_name	=	@$requestData['first_name'];
			$obj->last_name	=	@$requestData['last_name'];
			//$obj->age	=	@$requestData['age'];
			$obj->gender	=	@$requestData['gender'];
			$obj->marital_status	=	@$requestData['marital_status'];
			
			$obj->service	=	@$requestData['service'];
          
			$obj->dob	=	($dob != '') ? $dob : null;
            if(isset($dob) && $dob != ""){
                $calculate_age  = $this->calculateAge($dob); //dd($age);
                $obj->age	=	$calculate_age;
            }
          
			$obj->related_files	=	rtrim($related_files,',');
			// Primary (first) email and type go to admins; all emails also sync to client_emails below
			$emails = $requestData['email'] ?? [];
			$emailTypes = $requestData['email_type'] ?? [];
			$clientEmailIds = $requestData['clientemailid'] ?? [];
			if (!empty($emails)) {
				$obj->email = trim($emails[0]);
				$obj->email_type = trim($emailTypes[0] ?? 'Personal') ?: 'Personal';
			}
          
			//$obj->contact_type	=	@$requestData['contact_type'];
            //$obj->country_code	=	@$requestData['country_code'];
			//$obj->phone	=	@$requestData['phone'];
          
		$obj->address	=	@$requestData['address'];
		
			$obj->city	=	@$requestData['city'];
			$obj->state	=	@$requestData['state'];
			$obj->zip	=	@$requestData['zip'];
			$obj->country	=	@$requestData['country'];
			$obj->visa_opt = @$requestData['visa_opt'];
			$obj->country_passport			=	@$requestData['country_passport'];
			$obj->passport_number			=	@$requestData['passport_number'];
			$obj->visa_type			=		@$requestData['visa_type'];
			$obj->visaExpiry			=	($visaExpiry != '') ? $visaExpiry : null;
			$obj->office_id	=	!empty($requestData['office']) ? $requestData['office'] : null;
			//$obj->assignee	=	@$requestData['assign_to'];
            if( isset($requestData['assign_to']) && is_array($requestData['assign_to']) ){
                $assignToCount = count($requestData['assign_to']);
                if( $assignToCount >1 ) {
                    $obj->assignee	=  implode(",", $requestData['assign_to']);
                } else if( $assignToCount == 1 ) {
                    $obj->assignee	=  $requestData['assign_to'][0];
                } else {
                    $obj->assignee	= "";
                }
            }
          
			$obj->status	=	@$requestData['status'];
			$obj->lead_quality	=	@$requestData['lead_quality'];
			$obj->nomi_occupation	=	@$requestData['nomi_occupation'];
			$obj->skill_assessment	=	@$requestData['skill_assessment'];
			$obj->high_quali_aus	=	@$requestData['high_quali_aus'];
			$obj->high_quali_overseas	=	@$requestData['high_quali_overseas'];
			$obj->relevant_work_exp_aus	=	@$requestData['relevant_work_exp_aus'];
			$obj->relevant_work_exp_over	=	@$requestData['relevant_work_exp_over'];

			$obj->married_partner	=	@$requestData['married_partner'];
			$obj->total_points	=	@$requestData['total_points'];
			$obj->comments_note	=	@$requestData['comments_note'];
			$obj->type	=	@$requestData['type'];
			$obj->source	=	@$requestData['source'];
			if(isset($requestData['tagname'])){
				$obj->tagname = $this->normalizeTags($requestData['tagname']);
			}

				if(isset($requestData['naati_py']) && !empty($requestData['naati_py'])){
			$obj->naati_py	=	implode(',',@$requestData['naati_py']);
			}else{
			   	$obj->naati_py	=	'';
			}
			if(@$requestData['source'] == 'Sub Agent' ){
				$obj->agent_id	=	@$requestData['subagent'];
			}
			else{
				$obj->agent_id	=	'';
			}

			
			// profile_img column removed from admins table
		 //$obj->manual_email_phone_verified	=	@$requestData['manual_email_phone_verified'];
		 
		$saved							=	$obj->save();
		
		//////////////////////////////////////////////////////
		//////////Code Start For Test Scores (client_testscore)/////////////////
		//////////////////////////////////////////////////////
		if (isset($requestData['test_type']) && !empty(trim((string)$requestData['test_type']))) {
			$testType = $this->normalizeTestType($requestData['test_type']);
			$testDate = null;
			if (!empty($requestData['test_date'])) {
				$testDate = date('Y-m-d', strtotime(str_replace('/', '-', $requestData['test_date'])));
			}
			// Replace single test score for this client (match migrationmanager2 structure)
			ClientTestScore::where('client_id', $obj->id)->delete();
			ClientTestScore::create([
				'admin_id' => Auth::id(),
				'client_id' => $obj->id,
				'test_type' => $testType,
				'listening' => $requestData['listening'] ?? null,
				'reading' => $requestData['reading'] ?? null,
				'writing' => $requestData['writing'] ?? null,
				'speaking' => $requestData['speaking'] ?? null,
				'overall_score' => $requestData['overall'] ?? null,
				'test_date' => $testDate,
				'relevant_test' => 1,
			]);
		}
		//////////////////////////////////////////////////////
		//////////Code End For Test Scores///////////////////
		//////////////////////////////////////////////////////
          
            //////////////////////////////////////////////////////
            //////////Code Start For client phone////////////////
            //////////////////////////////////////////////////////
            //////////////////////////////////////////////////////
          
          
            //Update partner phone table
            if(isset($requestData['rem_phone'])){
                $rem_phone =  @$requestData['rem_phone'];
                $remPhoneCount = count($rem_phone);
                for($irem_phone=0; $irem_phone< $remPhoneCount; $irem_phone++){
                    if(\App\Models\ClientPhone::where('id', $rem_phone[$irem_phone])->exists()){
                        \App\Models\ClientPhone::where('id', $rem_phone[$irem_phone])->delete();
                    }
                }
            }

            if(isset($requestData['contact_type'])){
                $contact_type =  $requestData['contact_type'];
            } else {
                $contact_type = array();
            }

            if(isset($requestData['client_country_code'])){
                $client_country_code = array_map(function($code) {
                    return PhoneHelper::normalizeCountryCode($code);
                }, (array)$requestData['client_country_code']);
            } else {
                $client_country_code = array();
            }

            if(isset($requestData['client_phone'])){
                $client_phone =  $requestData['client_phone'];
            } else {
                $client_phone = array();
            }

            $clientPhoneCount = count($client_phone);
            if($clientPhoneCount >0){
                for($iii=0; $iii< $clientPhoneCount; $iii++){
                    if(\App\Models\ClientPhone::where('id', $requestData['clientphoneid'][$iii])->exists()){
                        $os1 = \App\Models\ClientPhone::find($requestData['clientphoneid'][$iii]);
                        $os1->user_id = @Auth::user()->id;
                        $os1->client_id = @$obj->id;
                        $os1->contact_type = @$contact_type[$iii];
                        $os1->client_country_code = @$client_country_code[$iii];
                        $os1->client_phone = @$client_phone[$iii];
                        $os1->updated_at = date('Y-m-d H:i:s');
                        $os1->save();
                    } else {
                        $oe1 = new \App\Models\ClientPhone;
                        $oe1->user_id = @Auth::user()->id;
                        $oe1->client_id = @$obj->id;
                        $oe1->contact_type = @$contact_type[$iii];
                        $oe1->client_country_code = @$client_country_code[$iii];
                        $oe1->client_phone = @$client_phone[$iii];
                        $oe1->created_at = date('Y-m-d H:i:s');
                        $oe1->updated_at = date('Y-m-d H:i:s');
                        $oe1->save();
                    }

                    if( isset($contact_type[$iii]) && $contact_type[$iii] == 'Personal'){
                        //Update admin  table
                        $adminInfo1 = Admin::find($obj->id); // Retrieve the record by ID
                        $lastContactType = $contact_type[$iii];
                        $lastPhoneCountryCode = $client_country_code[$iii];
                        $lastPhone = $client_phone[$iii];
                        $adminInfo1->contact_type =  $lastContactType;
                        $adminInfo1->country_code =  $lastPhoneCountryCode;
                        $adminInfo1->phone =  $lastPhone;
                        $adminInfo1->save(); // Save the changes
                    }
                } //end for loop
            }
            //////////////////////////////////////////////////////
            //////////Code End For client phone////////////////
            //////////////////////////////////////////////////////
            // Sync client_emails
            $emails = $requestData['email'] ?? [];
            $emailTypes = $requestData['email_type'] ?? [];
            $clientEmailIds = $requestData['clientemailid'] ?? [];
            $existingIds = [];
            foreach ($emails as $idx => $emailAddr) {
                $emailAddr = trim($emailAddr ?? '');
                if ($emailAddr === '') continue;
                $emailType = $emailTypes[$idx] ?? 'Personal';
                $ceId = $clientEmailIds[$idx] ?? '';
                $ceId = (is_numeric($ceId) || $ceId === '') ? $ceId : null;
                if ($ceId !== '' && $ceId !== null && ClientEmail::where('id', $ceId)->where('client_id', $obj->id)->exists()) {
                    $ce = ClientEmail::find($ceId);
                    $ce->client_email = $emailAddr;
                    $ce->email_type = $emailType;
                    $ce->user_id = Auth::id();
                    $ce->updated_at = now();
                    $ce->save();
                    $existingIds[] = $ce->id;
                } else {
                    $ce = new ClientEmail;
                    $ce->client_id = $obj->id;
                    $ce->user_id = Auth::id();
                    $ce->client_email = $emailAddr;
                    $ce->email_type = $emailType;
                    $ce->save();
                    $existingIds[] = $ce->id;
                }
            }
            // Remove client_emails that were deleted from the form
            ClientEmail::where('client_id', $obj->id)->whereNotIn('id', $existingIds)->delete();
            //////////////////////////////////////////////////////
          
          
			if($requestData['client_id'] == ''){
		    	$objs							= 	Admin::find($obj->id);
		    	$objs->client_id	=	strtoupper($first_name).date('ym').$objs->id;
		    	$saveds				=	$objs->save();
			}else{
			    $objs							= 	Admin::find($obj->id);
		    	$objs->client_id	=	$requestData['client_id'];
		    	$saveds				=	$objs->save();
			}
			$route=$request->route;
			if(strpos($request->route,'?')){
				$position=strpos($request->route,'?');
				if ($position !== false) {
					$route = substr($request->route, 0, $position);
				}
			}

			// dd($route);
			if(!$saved)
			{
				return redirect()->back()->with('error', Config::get('constants.server_error'));
			}
             else  if($route==url('/action')){
				$subject = 'Lead status has changed to '.@$requestData['status'].' from '. Auth::user()->first_name;
				$objs = new ActivitiesLog;
				$objs->client_id = $request->id;
				$objs->created_by = Auth::user()->id;
				$objs->subject = $subject;
				$objs->task_status = 0; // Required NOT NULL field (0 = activity, 1 = task)
				$objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
				$objs->save();

				return redirect()->route('action.index')->with('success','Action updated successfully');
			}

			else
			{
              
              //Code for addition of simiar related files in added users account  
                    if(isset($requestData['related_files']))
                    {
                        $relatedFilesCount = count($requestData['related_files']);
                        for($j=0; $j<$relatedFilesCount; $j++){
                            if(Admin::where('id', '=', $requestData['related_files'][$j])->exists())
                            {
                                $objsY = Admin::select('id', 'related_files')->where('id', $requestData['related_files'][$j])->get();
                                if(!empty($objsY)){
                                    if($objsY[0]->related_files != ""){
                                        $related_files_string = $objsY[0]->related_files;
                                        $commaPosition = strpos($related_files_string, ',');
                                        if ($commaPosition !== false) { //If comma is exist
                                            $related_files_string_Arr = explode(",",$related_files_string);
                                            array_push($related_files_string_Arr, $requestData['id']);
                                            // Remove duplicate elements
                                            $uniqueArray = array_unique($related_files_string_Arr);

                                            // Reindex the array
                                            $uniqueArray = array_values($uniqueArray);

                                            $related_files_latest = implode(",",$uniqueArray);
                                        } else { //If comma is not exist
                                            $related_files_string_Arr = array($objsY[0]->related_files);
                                            array_push($related_files_string_Arr, $requestData['id']);
                                            // Remove duplicate elements
                                             $uniqueArray = array_unique($related_files_string_Arr);

                                             // Reindex the array
                                             $uniqueArray = array_values($uniqueArray);

                                            $related_files_latest = implode(",",$uniqueArray);
                                        }
                                    } else {
                                        $related_files_latest = $requestData['id'];
                                    }
                                    Admin::where('id', $requestData['related_files'][$j])->update(['related_files' => $related_files_latest]);
                                }
                            }
                        } //end foreach
                    }

              //Code for removal of simiar related files in added users account                  
              if( isset($requestData['related_files'])  || !isset($requestData['related_files']) )
              {

                  if( isset($requestData['related_files']) ) {
                    $req_arr11 = $requestData['related_files'];
                  } else {
                    $req_arr11 = array();
                  }

                  if( !empty($db_arr)  ){
                      $commaPosition11 = strpos($db_arr[0]->related_files, ',');
                      if ($commaPosition11 !== false) { //If comma is exist
                        $db_arr11 = explode(",",$db_arr[0]->related_files);
                      } else { //If comma is not exist
                        $db_arr11 = array($db_arr[0]->related_files);
                      }

                      //echo "<pre>db_arr11=";print_r($db_arr11);
                      //echo "<pre>req_arr11=";print_r($req_arr11);
                      $diff_arr = array_diff( $db_arr11,$req_arr11 );
                      //echo "<pre>diff_arr=";print_r($diff_arr);
                      $diff_arr = array_values($diff_arr);
                      //echo "<pre>diff_arr=";print_r($diff_arr);die;
                  }


                  if( isset($diff_arr) && !empty($diff_arr))
                  {
                      $diffArrCount = count($diff_arr);
                      for($k=0; $k<$diffArrCount; $k++)
                      {
                          if(Admin::where('id', '=', $diff_arr[$k])->exists())
                          {
                              $rel_data_arr = Admin::select('related_files')->where('id', $diff_arr[$k])->get();
                              if( !empty($rel_data_arr) ){
                                  $commaPosition1 = strpos($rel_data_arr[0]->related_files, ',');
                                  if ($commaPosition1 !== false) { //If comma is exist
                                      $rel_data_exploded_arr = explode(",",$rel_data_arr[0]->related_files);
                                      $key_search = array_search($requestData['id'], $rel_data_exploded_arr);
                                      if ($key_search !== false) {
                                          unset($rel_data_exploded_arr[$key_search]);
                                      }
                                      $rel_data_exploded_arr = array_values($rel_data_exploded_arr);
                                      //print_r($rel_data_exploded_arr);
                                      $related_files_updated = implode(",",$rel_data_exploded_arr);

                                      Admin::where('id', $diff_arr[$k])->update(['related_files' => $related_files_updated]);

                                  } else { //If comma is not exist
                                      if ($rel_data_arr[0]->related_files == $requestData['id']) {
                                          $related_files_updated = "";
                                          Admin::where('id', $diff_arr[$k])->update(['related_files' => $related_files_updated]);
                                      }
                                  }
                              }
                          }
                      }
                  }
              }
              
              return redirect()->route('clients.detail', $this->encodeString(@$requestData['id']))->with('success', 'Clients Edited Successfully');
			}
		}

		else
		{
			if(isset($id) && !empty($id))
			{

				$id = $this->decodeString($id);
				if(Admin::where('id', '=', $id)->exists())
				{
					$fetchedData = Admin::with('office')->find($id);
					if (! $fetchedData) {
						return redirect()->route('clients.index')->with('error', config('constants.unauthorized'));
					}
					if (! $this->canEditClient($fetchedData)) {
						return $this->redirectWhenCannotViewClientRecord($fetchedData);
					}
                  
                    if(!empty($fetchedData) && $fetchedData->dob != ""){
                        $this->applyCalculatedAgeForDisplay($fetchedData);
                    }
                  
                     //Check phone record is exist in client phone table
                    if( \App\Models\ClientPhone::where('client_id', $id)->doesntExist() ){
                        if( $fetchedData->phone != "" ) {
                            $oef1 = new \App\Models\ClientPhone;
                            $oef1->user_id = @Auth::user()->id;
                            $oef1->client_id = $id;
                            $oef1->contact_type = $fetchedData->contact_type ?? 'Personal';
                            $oef1->client_country_code = $fetchedData->country_code;
                            $oef1->client_phone = $fetchedData->phone;
                            $oef1->created_at = date('Y-m-d H:i:s');
                            $oef1->updated_at = date('Y-m-d H:i:s');
                            $oef1->save();
                        }
                    }
                  
                  
                     //Show alert box is entry is updated before 1 month ago
                    if ($fetchedData && $fetchedData->updated_at) {
                        $updatedAt = Carbon::parse($fetchedData->updated_at);
                        $fourWeeksAgo = Carbon::now()->subWeeks(4);
                        if ($updatedAt->lt($fourWeeksAgo)) {
                            $showAlert = true;
                        }
                    }
                  
					return view('Admin.clients.edit', compact(['fetchedData','showAlert']));
				}
				else
				{
					return redirect()->route('clients.index')->with('error', 'Clients Not Exist');
				}
			}
			else
			{
				return redirect()->route('clients.index')->with('error', Config::get('constants.unauthorized'));
			}
		}

	}

    /**
     * Active consultants for the schedule-followup modal (empty collection if table is not migrated).
     */
    protected function followupConsultantsForSchedule(): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable('followup_consultants')) {
            return collect();
        }

        return FollowupConsultant::query()
            ->where('status', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Staff with the clients module but no allocation/grant for this admins row: offer cross-access request.
     * Otherwise send them to the listing with an error (e.g. no module access).
     */
    protected function redirectWhenCannotViewClientRecord(Admin $client, string $fallbackRoute = 'clients.index')
    {
        $user = Auth::guard('admin')->user();
        if ($user instanceof Staff
            && ! StaffClientVisibility::canAccessAdminRecord((int) $client->id, $user)
            && StaffClientVisibility::staffMayOpenCrossAccessRequest($user, (int) $client->id)) {
            return redirect()->route('crm.access.request', ['adminId' => (int) $client->id]);
        }

        return redirect()->route($fallbackRoute)->with('error', config('constants.unauthorized'));
    }

    //Client detail page
    public function clientdetail(Request $request, $id = NULL, $tab = NULL){ 
        $showAlert = false;
        $applicationId = $request->route('applicationId');
        if (!empty($applicationId) && empty($tab)) {
            $tab = 'application';
        }
        $forcedTab = $tab;
        if(isset($request->t)){
           if(\App\Models\Notification::where('id', $request->t)->exists()){
              $ovv =  \App\Models\Notification::find($request->t);
              $ovv->receiver_status = 1;
              $ovv->save();
           }
       }
       if(isset($id) && !empty($id))
        {
            $encodeId = $id;
            $originalId = $id;
            $id = $this->decodeString($id); //dd($id);
            
            // Check if decodeString returned false (invalid encoded string)
            if($id === false || empty($id))
            {
                return Redirect::to($this->getClientRedirectUrl('index'))->with('error', 'Invalid Client ID');
            }
            // Otherwise check admins table (old clients/leads)
            if(Admin::where('id', '=', $id)->exists())
            {
                $fetchedData = Admin::with('office')->find($id);
                
                // Double check that fetchedData exists
                if(empty($fetchedData))
                {
                    return Redirect::to($this->getClientRedirectUrl('index'))->with('error', 'Client data not found');
                }

                // Clients detail is for clients only — send leads to /leads/detail (C-21)
                if (strcasecmp((string) ($fetchedData->type ?? ''), 'lead') === 0) {
                    $leadRouteParams = ['id' => $encodeId];
                    if (! empty($applicationId)) {
                        $leadRouteParams['applicationId'] = $applicationId;

                        return redirect()
                            ->route('leads.detail.application', $leadRouteParams)
                            ->withQueryString();
                    }
                    if (! empty($tab)) {
                        $leadRouteParams['tab'] = $tab;
                    }

                    return redirect()
                        ->route('leads.detail', $leadRouteParams)
                        ->withQueryString();
                }

                if (! $this->canViewClient($fetchedData)) {
                    return $this->redirectWhenCannotViewClientRecord($fetchedData);
                }

                if(!empty($fetchedData) && $fetchedData->dob != ""){
                    $this->applyCalculatedAgeForDisplay($fetchedData);
                }
                
                
                //Show alert box is entry is updated before 1 month ago
                if ($fetchedData && $fetchedData->updated_at) {
                    $updatedAt = Carbon::parse($fetchedData->updated_at);
                    $fourWeeksAgo = Carbon::now()->subWeeks(4);
                    if ($updatedAt->lt($fourWeeksAgo)) {
                        $showAlert = true;
                    }
                }
                
                $clientApplications = $this->clientApplicationsForActiveDetailTab($request, $forcedTab, (int) $fetchedData->id);

                $showGoogleReviewReminderModal = $this->shouldShowGoogleReviewReminderModal($fetchedData);

                $followupConsultants = $this->followupConsultantsForSchedule();
                $canEditClient = $this->canEditClient($fetchedData);
                return view(
                    $this->getClientViewPath('clients.detail'),
                    compact(['fetchedData','encodeId','showAlert','applicationId','forcedTab','clientApplications','showGoogleReviewReminderModal','followupConsultants','canEditClient'])
                );
            }
            else
            {
                return Redirect::to($this->getClientRedirectUrl('index'))->with('error', 'Client or Lead Not Found');
            }
        }
        else
        {
            return Redirect::to($this->getClientRedirectUrl('index'))->with('error', Config::get('constants.unauthorized'));
        }
    }

    //Lead detail page
    public function leaddetail(Request $request, $id = NULL, $tab = NULL){ 
        $showAlert = false;
        $applicationId = $request->route('applicationId');
        if (!empty($applicationId) && empty($tab)) {
            $tab = 'application';
        }
        $forcedTab = $tab;
        if(isset($request->t)){
           if(\App\Models\Notification::where('id', $request->t)->exists()){
              $ovv =  \App\Models\Notification::find($request->t);
              $ovv->receiver_status = 1;
              $ovv->save();
           }
        }
        if(isset($id) && !empty($id))
        {
            $encodeId = $id;
            $originalId = $id;
            $id = $this->decodeString($id);
            
            // Check if decodeString returned false (invalid encoded string)
            if($id === false || empty($id))
            {
                return redirect()->route('leads.index')->with('error', 'Invalid Lead ID');
            }
            
            // Admin model: check by admins.id or lead_id (leads.id for migrated)
            $adminLead = Admin::where('id', '=', $id)->where('type', 'lead')->first()
                ?? Admin::where('lead_id', '=', $id)->where('type', 'lead')->first();
            if ($adminLead) {
                $fetchedData = $adminLead;
                if (! $this->canViewClient($fetchedData)) {
                    return $this->redirectWhenCannotViewClientRecord($fetchedData, 'leads.index');
                }
                $encodeId = base64_encode(convert_uuencode($adminLead->id));
                $this->applyCalculatedAgeForDisplay($fetchedData);
                $clientApplications = $this->clientApplicationsForActiveDetailTab($request, $forcedTab, (int) $fetchedData->id);
                $showGoogleReviewReminderModal = $this->shouldShowGoogleReviewReminderModal($fetchedData);
                $followupConsultants = $this->followupConsultantsForSchedule();
                $canEditClient = $this->canEditClient($fetchedData);
                return view(
                    $this->getClientViewPath('clients.detail'),
                    compact(['fetchedData','encodeId','showAlert','applicationId','forcedTab','clientApplications','showGoogleReviewReminderModal','followupConsultants','canEditClient'])
                );
            }
            // Fallback: existing admins row linked by legacy leads.id (read-only).
            // L-10: do not INSERT on GET — residual legacy rows must use MigrateLeadsToAdminsCommand.
            $enqdata = Admin::where('lead_id', $id)->first();
            if ($enqdata) {
                $fetchedData = Admin::find($enqdata->id);
                if ($fetchedData) {
                    $encodeId = base64_encode(convert_uuencode($fetchedData->id));
                    if (! $this->canViewClient($fetchedData)) {
                        return $this->redirectWhenCannotViewClientRecord($fetchedData, 'leads.index');
                    }
                    $this->applyCalculatedAgeForDisplay($fetchedData);
                    if ($fetchedData->updated_at) {
                        $updatedAt = Carbon::parse($fetchedData->updated_at);
                        $fourWeeksAgo = Carbon::now()->subWeeks(4);
                        if ($updatedAt->lt($fourWeeksAgo)) {
                            $showAlert = true;
                        }
                    }
                    $clientApplications = $this->clientApplicationsForActiveDetailTab($request, $forcedTab, (int) $fetchedData->id);
                    $showGoogleReviewReminderModal = $this->shouldShowGoogleReviewReminderModal($fetchedData);
                    $followupConsultants = $this->followupConsultantsForSchedule();
                    $canEditClient = $this->canEditClient($fetchedData);
                    return view(
                        $this->getClientViewPath('clients.detail'),
                        compact(['fetchedData','encodeId','showAlert','applicationId','forcedTab','clientApplications','showGoogleReviewReminderModal','followupConsultants','canEditClient'])
                    );
                }
            }
            return redirect()->route('leads.index')->with('error', 'Client or Lead Not Found');
        }
        else
        {
            return redirect()->route('leads.index')->with('error', Config::get('constants.unauthorized'));
        }
    }

    /**
     * Applications tab data only — other tabs lazy-load this via /get-application-lists.
     */
    protected function clientApplicationsForActiveDetailTab(Request $request, $forcedTab, int $clientId)
    {
        $tab = ClientDetailTab::resolve($forcedTab, $request->route('tab'), $request->get('tab'))['tab'];
        if ($tab !== 'application') {
            return null;
        }

        return Application::eagerForClientDetailList($clientId);
    }

    /**
     * Set age on the in-memory record for display. GET must not persist this.
     */
    protected function applyCalculatedAgeForDisplay(Admin $record): void
    {
        if ($record->dob === null || $record->dob === '') {
            return;
        }

        $record->age = $this->calculateAge($record->dob);
    }

    //Calculate age
    function calculateAge($dob) {
        // Convert the DOB string to a DateTime object
        $birthDate = new \DateTime($dob);
        // Get the current date
        $today = new \DateTime();
        // Calculate the difference between the current date and the birth date
        $diff = $today->diff($birthDate);

        // Get the years and months from the difference
        $ageYears = $diff->y;
        $ageMonths = $diff->m;

        return "$ageYears years and $ageMonths months";
    }
	
	//Update session to be complete
    public function updatesessioncompleted(Request $request,CheckinLog $checkinLog)
    {
        $data = $request->all(); //dd($data['client_id']);
        $cid = (int) ($data['client_id'] ?? 0);
        $clientRow = $cid > 0 ? Admin::find($cid) : null;
        if (! $clientRow || ! $this->canEditClient($clientRow)) {
            echo json_encode(['status' => false, 'message' => 'Unauthorized']);
            return;
        }
        $sessionExist = CheckinLog::where('client_id', $data['client_id'])
        ->where('status', 2)
        ->update(['status' => 1]);
        if($sessionExist){
            $response['status'] 	= 	true;
            $response['message']	=	'Session completed successfully';
        } else {
            $response['status'] 	= 	false;
            $response['message']	=	'Please try again';
        }
        echo json_encode($response);
    }

	public function getallclients(Request $request){
		// Validate input
		$validated = $request->validate([
			'q' => 'required|string|min:2|max:100',
		]);

		$query = $validated['q'];

		// Use SearchService for optimized search
		$searchService = new SearchService($query, 50, true);
		$results = $searchService->search();

		return response()->json($results);
	}

	public function getrecipients(Request $request){
		$squery = trim($request->q ?? '');
		if($squery === ''){
			return response()->json(array('items'=>array()));
		}

		try {
			$operator = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
			$clients = $this->buildRecipientsSearchQuery($squery, $operator);
			$user = Auth::guard('admin')->user();
			if ($user instanceof Staff) {
				StaffClientVisibility::restrictAdminsQueryForStaff($clients, $user);
			}
			$clients = $clients->get();

			$items = array();
			foreach($clients as $clint){
				$fullName = trim(($clint->first_name ?? '') . ' ' . ($clint->last_name ?? ''));
				$items[] = array(
					'id' => $clint->id,
					'text' => $fullName, // Required by Tom Select / AJAX recipient format
					'name' => $fullName,
					'email' => $clint->email ?? '',
					'phone' => $this->formatRecipientPhone($clint),
					'client_id' => $clint->client_id ?? '',
					'status' => $clint->type ?? 'Client',
					'cid' => base64_encode(convert_uuencode($clint->id))
				);
			}

			return response()->json(array('items'=>$items));
		} catch (\Exception $e) {
			Log::error('getrecipients error: ' . $e->getMessage());
			return response()->json(array('items'=>array(), 'error' => $e->getMessage()));
		}
	}

	public function getonlyclientrecipients(Request $request){
		$squery = trim($request->q ?? '');
		if($squery === ''){
			echo json_encode(array('items'=>array()));
			return;
		}

		$operator = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
		$clients = Admin::where('is_archived', '=', 0)
			->where('type', 'client')
			->where(function($query) use ($squery, $operator) {
				return $query
					->where('email', $operator, '%'.$squery.'%')
					->orwhere('first_name', $operator, '%'.$squery.'%')
					->orwhere('last_name', $operator, '%'.$squery.'%')
					->orwhere('client_id', $operator, '%'.$squery.'%')
					->orwhere('phone', $operator, '%'.$squery.'%')
					->orWhere(DB::raw("COALESCE(first_name, '') || ' ' || COALESCE(last_name, '')"), $operator, "%".$squery."%");
			});
		$user = Auth::guard('admin')->user();
		if ($user instanceof Staff) {
			StaffClientVisibility::restrictAdminsQueryForStaff($clients, $user);
		}
		$clients = $clients->get();

		$items = array();
		foreach($clients as $clint){
			$fullName = trim(($clint->first_name ?? '') . ' ' . ($clint->last_name ?? ''));
			$items[] = array(
				'text' => $fullName,
				'name' => $fullName,
				'email' => $clint->email,
				'status' => $clint->type,
				'id' => $clint->id,
				'cid' => base64_encode(convert_uuencode(@$clint->id)),
			);
		}

		echo json_encode(array('items'=>$items));
	}

	public function save_tag(Request $request){
		$id = $request->client_id;

		if(Admin::where('id',$id)->exists()){
			$rawTags = $request->input('tagname', '');
			$obj = Admin::find($id);
			if (! $obj || ! $this->canEditClient($obj)) {
				return redirect()->route('clients.index')->with('error', Config::get('constants.unauthorized'));
			}
			$obj->tagname = $this->normalizeTags($rawTags);
			$saved = $obj->save();
			if($saved){
				return redirect()->route('clients.detail', base64_encode(convert_uuencode(@$id)))->with('success', 'Tags addes successfully');
			}else{
				return redirect()->route('clients.detail', base64_encode(convert_uuencode(@$id)))->with('error', 'Please try again');
			}
		}else{
			return redirect()->route('clients.index')->with('error', Config::get('constants.unauthorized'));
		}
	}

	public function change_assignee(Request $request){
		$objs = Admin::find($request->id);
		if (! $objs || ! $this->canEditClient($objs)) {
			echo json_encode(['status' => false, 'message' => 'Unauthorized']);
			return;
		}

		// Accept array, scalar id, empty clear, or comma-separated string; store format unchanged
		$assigneeArr = [];
		if ($request->exists('assignee') || $request->exists('assinee')) {
			$assigneeInput = $request->input('assignee', $request->input('assinee'));
			if (is_array($assigneeInput)) {
				$assigneeArr = array_values(array_filter($assigneeInput, function ($v) {
					return $v !== null && $v !== '';
				}));
			} elseif (is_string($assigneeInput)) {
				$assigneeInput = trim($assigneeInput);
				if ($assigneeInput !== '') {
					$assigneeArr = str_contains($assigneeInput, ',')
						? array_values(array_filter(array_map('trim', explode(',', $assigneeInput))))
						: [$assigneeInput];
				}
			} elseif (is_numeric($assigneeInput)) {
				$assigneeArr = [(string) $assigneeInput];
			}

			$assigneeCount = count($assigneeArr);
			if ($assigneeCount < 1) {
				$objs->assignee = "";
			} elseif ($assigneeCount === 1) {
				$objs->assignee = $assigneeArr[0];
			} else {
				$objs->assignee = implode(",", $assigneeArr);
			}
		}

		$saved = $objs->save();
		if($saved){
			if (count($assigneeArr) >= 1) {
				foreach($assigneeArr as $key=>$val) {
					$o = new \App\Models\Notification;
					$o->sender_id = Auth::user()->id;
					$o->receiver_id = $val;
					$o->module_id = $request->id;
					$o->url = route('clients.detail', base64_encode(convert_uuencode(@$request->id)));
					$o->notification_type = 'client';
					$o->message = 'Client Assigned by '.Auth::user()->first_name.' '.Auth::user()->last_name;
					$o->seen = 0;
					$o->save();
				}
			}
			$response['status'] 	= 	true;
			$response['message']	=	'Updated successfully';
		}else{
			$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
		}
		echo json_encode($response);
	}

	public function removetag(Request $request){
		$objs = Admin::find($request->c);
		if (! $objs || ! $this->canEditClient($objs)) {
			return redirect()->route('clients.index')->with('error', Config::get('constants.unauthorized'));
		}
		$itag = $request->rem_id;

		if($objs->tagname != ''){
			$rs = explode(',', $objs->tagname);
			unset($rs[$itag]);
			$objs->tagname = 	implode(',',@$rs);
			$objs->save();
		}
		return redirect()->route('clients.detail', ['id' => base64_encode(convert_uuencode(@$objs->id))])->with('success', 'Record Updated successfully');
	}

    /**
     * Check if client exists (AJAX validation)
     * Used for form validation to prevent duplicate clients
     * 
     * @param Request $request
     * @return int Returns 1 if exists, 0 if not
     */
    public function checkclientexist(Request $request){
        $type = (string) $request->input('type', 'phone');
        $vl = trim((string) $request->input('vl', ''));
        if ($vl === '') {
            echo 0;
            return;
        }

        if ($type === 'email') {
            $clientexists = Admin::where('email', $vl)->exists();
            echo $clientexists ? 1 : 0;
        } elseif ($type === 'clientid') {
            $clientexists = Admin::where('client_id', $vl)->exists();
            echo $clientexists ? 1 : 0;
        } else {
            // phone (default) — keep 1/0 for create/edit form JS
            $clientexists = Admin::where('phone', $vl)->exists();
            echo $clientexists ? 1 : 0;
        }
    }

    /**
     * Change client type (client/lead)
     */
    public function changetype(Request $request,$id = Null, $slug = Null){
        if(isset($id) && !empty($id))
        {
            $id = $this->decodeString($id);
            if(Admin::where('id', '=', $id)->exists())
            {
                $obj = Admin::find($id);
                if (! $obj || ! $this->canEditClient($obj)) {
                    return redirect()->route('clients.index')->with('error', config('constants.unauthorized'));
                }
                $obj->type = $slug;
                $saved = $obj->save();

                return redirect()->route('clients.detail', ['id' => base64_encode(convert_uuencode(@$id))])->with('success', 'Record Updated successfully');
            }
            else
            {
                return redirect()->route('clients.index')->with('error', 'Clients Not Exist');
            }
        }
        else
        {
            return redirect()->route('clients.index')->with('error', Config::get('constants.unauthorized'));
        }
    }

    /**
     * Export client data to JSON file
     * 
     * @param int $id Client ID
     * @return \Illuminate\Http\Response
     */
    public function export($id)
    {
        try {
            $client = Admin::where('id', $id)->first();

            if (!$client) {
                return redirect()->route('clients.index')
                    ->with('error', 'Client not found.');
            }

            if (! $this->canViewClient($client)) {
                return $this->redirectWhenCannotViewClientRecord($client);
            }

            $exportService = app(ClientExportService::class);
            $exportData = $exportService->exportClient($id);

            $filename = 'client_export_' . ($client->client_id ?? $id) . '_' . date('Y-m-d_His') . '.json';

            return response()->json($exportData, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            Log::error('Client export error: ' . $e->getMessage(), [
                'client_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('clients.index')
                ->with('error', 'Failed to export client data: ' . $e->getMessage());
        }
    }

    /**
     * Check (once per request) that an active SMS template titled "google_review_link"
     * exists so we only show the modal when there is a real send path available.
     */
    protected function googleReviewSmsTemplateExists(): bool
    {
        if ($this->googleReviewSmsTemplateExistsCache !== null) {
            return $this->googleReviewSmsTemplateExistsCache;
        }

        $this->googleReviewSmsTemplateExistsCache = SmsTemplate::active()
            ->whereRaw('LOWER(TRIM(title)) = ?', ['google_review_link'])
            ->exists();

        return $this->googleReviewSmsTemplateExistsCache;
    }

    /**
     * Roles in config `crm.google_review_reminder_exclude_role_ids` do not see the reminder modal or related APIs.
     */
    protected function currentStaffIsExcludedFromGoogleReviewReminder(): bool
    {
        $user = Auth::guard('admin')->user();
        if (! $user) {
            return false;
        }

        $roleId = (int) ($user->role ?? 0);
        $excluded = config('crm.google_review_reminder_exclude_role_ids', [14, 15]);

        return $roleId > 0 && in_array($roleId, $excluded, true);
    }

    protected function shouldShowGoogleReviewReminderModal(Admin $record): bool
    {
        // Master switch: keeps modal/routes/SMS code intact; only stops auto-open.
        if (! config('crm.google_review_reminder_enabled', false)) {
            return false;
        }

        // Role excluded (e.g. Calling Team, Accounts)
        if ($this->currentStaffIsExcludedFromGoogleReviewReminder()) {
            return false;
        }

        // Only show for active, non-archived client/lead records
        if ((int) ($record->is_archived ?? 0) === 1) {
            return false;
        }
        if (! in_array($record->type, ['client', 'lead'], true)) {
            return false;
        }

        // Terminal statuses — never show again
        $status = strtolower(trim((string) ($record->google_review_reminder_status ?? '')));
        if (in_array($status, [
            Admin::GOOGLE_REVIEW_REMINDER_NOT_INTERESTED,
            Admin::GOOGLE_REVIEW_REMINDER_REVIEW_RECEIVED,
        ], true)) {
            return false;
        }

        // Active snooze
        $until = $record->google_review_reminder_snooze_until;
        if ($until && $until->isFuture()) {
            return false;
        }

        // Only show when the SMS template is configured so staff can actually send the link
        if (! $this->googleReviewSmsTemplateExists()) {
            return false;
        }

        return true;
    }

    public function updateGoogleReviewReminder(Request $request)
    {
        if ($this->currentStaffIsExcludedFromGoogleReviewReminder()) {
            return response()->json(['ok' => false, 'message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'client_id' => 'required|integer|min:1',
            'action' => 'required|in:snooze,snooze_one_day,not_interested,review_received',
        ]);

        $admin = Admin::query()
            ->where('id', $validated['client_id'])
            ->whereIn('type', ['client', 'lead'])
            ->first();

        if (! $admin) {
            return response()->json(['ok' => false, 'message' => 'Record not found'], 404);
        }

        if ((int) ($admin->is_archived ?? 0) === 1) {
            return response()->json(['ok' => false, 'message' => 'Record not found'], 404);
        }

        if (! $this->canViewClient($admin)) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 403);
        }

        switch ($validated['action']) {
            case 'snooze':
                $admin->google_review_reminder_snooze_until = Carbon::now()->addWeek();
                break;
            case 'snooze_one_day':
                $admin->google_review_reminder_snooze_until = Carbon::now()->addDay();
                break;
            case 'not_interested':
                $admin->google_review_reminder_status = Admin::GOOGLE_REVIEW_REMINDER_NOT_INTERESTED;
                $admin->google_review_reminder_snooze_until = null;
                break;
            case 'review_received':
                $admin->google_review_reminder_status = Admin::GOOGLE_REVIEW_REMINDER_REVIEW_RECEIVED;
                $admin->google_review_reminder_snooze_until = null;
                break;
        }

        $admin->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Send SMS with Google review link from the client/lead detail reminder modal.
     * Looks up an active SMS template by title (case-insensitive): "google_review_link" first,
     * then legacy "Google review link".
     * Template variables supported: {client_name} (primary), plus {first_name}, {last_name} for older templates.
     */
    public function sendGoogleReviewReminderSms(Request $request)
    {
        if ($this->currentStaffIsExcludedFromGoogleReviewReminder()) {
            return response()->json(['ok' => false, 'message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'client_id' => 'required|integer|min:1',
        ]);

        $admin = Admin::query()
            ->where('id', $validated['client_id'])
            ->whereIn('type', ['client', 'lead'])
            ->first();

        if (! $admin) {
            return response()->json(['ok' => false, 'message' => 'Record not found'], 404);
        }

        if ((int) ($admin->is_archived ?? 0) === 1) {
            return response()->json(['ok' => false, 'message' => 'Record not found'], 404);
        }

        if (! $this->canViewClient($admin)) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 403);
        }

        $rawPhone = trim((string) ($admin->country_code ?? '')).trim((string) ($admin->phone ?? ''));
        if ($rawPhone === '') {
            return response()->json(['ok' => false, 'message' => 'No phone number on file for this contact'], 422);
        }

        $rawFirst = trim((string) ($admin->first_name ?? ''));
        $rawLast = trim((string) ($admin->last_name ?? ''));
        $firstDisplay = $rawFirst !== ''
            ? mb_convert_case(mb_strtolower($rawFirst), MB_CASE_TITLE, 'UTF-8')
            : 'there';
        $fullName = trim($rawFirst.' '.$rawLast);
        $clientNameDisplay = $fullName !== ''
            ? mb_convert_case(mb_strtolower($fullName), MB_CASE_TITLE, 'UTF-8')
            : 'there';

        $variables = [
            'client_name' => $clientNameDisplay,
            'first_name' => $firstDisplay,
            'last_name' => $rawLast,
        ];

        $template = null;
        foreach (['google_review_link', 'Google review link'] as $tryTitle) {
            $found = SmsTemplate::active()
                ->whereRaw('LOWER(TRIM(title)) = ?', [mb_strtolower(trim($tryTitle))])
                ->orderBy('id')
                ->first();
            if ($found) {
                $template = $found;
                break;
            }
        }

        if (! $template) {
            return response()->json([
                'ok' => false,
                'message' => 'Google review SMS template not found. Create an active SMS template with title "google_review_link" in Admin Console.',
            ], 422);
        }

        $smsManager = app(UnifiedSmsManager::class);
        $senderId = Auth::guard('admin')->id();
        $result = $smsManager->sendFromTemplate(
            $rawPhone,
            (int) $template->id,
            $variables,
            ['client_id' => (int) $admin->id, 'sender_id' => $senderId]
        );

        if ($result['success'] ?? false) {
            return response()->json([
                'ok' => true,
                'message' => 'Review link sent by SMS',
            ]);
        }

        return response()->json([
            'ok' => false,
            'message' => $result['message'] ?? $result['error'] ?? 'Failed to send SMS',
        ], 422);
    }

    /**
     * Recipient / check-in contact search — name, email, client reference, and phone numbers.
     */
    private function buildRecipientsSearchQuery(string $squery, string $operator)
    {
        $phoneSubquery = $this->clientPhonesSubquery();
        $query = Admin::query()->where('admins.is_archived', '=', 0);

        if ($phoneSubquery) {
            $query->leftJoinSub($phoneSubquery, 'phone_data', 'admins.id', '=', 'phone_data.client_id')
                ->select('admins.*', 'phone_data.phones as aggregated_phones');
        } else {
            $query->select('admins.*');
        }

        $digitsOnly = preg_replace('/[^\d]/', '', $squery);
        $isNumericQuery = $digitsOnly !== ''
            && strlen($digitsOnly) >= 4
            && preg_match('/^[\d\s\-+().]+$/', $squery);

        $query->where(function ($q) use ($squery, $operator, $phoneSubquery, $isNumericQuery) {
            if ($isNumericQuery) {
                $q->where('admins.client_id', $operator, '%' . $squery . '%');

                foreach ($this->recipientPhonePatterns($squery) as $pattern) {
                    $q->orWhere('admins.phone', $operator, $pattern);
                    if ($phoneSubquery) {
                        $q->orWhere('phone_data.phones', $operator, $pattern);
                    }
                }

                return;
            }

            $q->where('admins.email', $operator, '%' . $squery . '%')
                ->orWhere('admins.first_name', $operator, '%' . $squery . '%')
                ->orWhere('admins.last_name', $operator, '%' . $squery . '%')
                ->orWhere('admins.client_id', $operator, '%' . $squery . '%');

            foreach ($this->recipientPhonePatterns($squery) as $pattern) {
                $q->orWhere('admins.phone', $operator, $pattern);
                if ($phoneSubquery) {
                    $q->orWhere('phone_data.phones', $operator, $pattern);
                }
            }

            if (DB::getDriverName() === 'pgsql') {
                $q->orWhere(
                    DB::raw("COALESCE(admins.first_name, '') || ' ' || COALESCE(admins.last_name, '')"),
                    $operator,
                    '%' . $squery . '%'
                );
            } else {
                $q->orWhere(
                    DB::raw("CONCAT(COALESCE(admins.first_name, ''), ' ', COALESCE(admins.last_name, ''))"),
                    $operator,
                    '%' . $squery . '%'
                );
            }
        });

        return $query->distinct();
    }

    private function clientPhonesSubquery()
    {
        if (! Schema::hasTable('client_phones')) {
            return null;
        }

        $agg = DB::getDriverName() === 'pgsql'
            ? "STRING_AGG(TRIM(CONCAT(COALESCE(client_country_code, ''), ' ', client_phone)), ', ')"
            : "GROUP_CONCAT(TRIM(CONCAT(COALESCE(client_country_code, ''), ' ', client_phone)) SEPARATOR ', ')";

        return DB::table('client_phones')
            ->select('client_id', DB::raw("{$agg} as phones"))
            ->groupBy('client_id');
    }

    /**
     * @return list<string>
     */
    private function recipientPhonePatterns(string $squery): array
    {
        $patterns = ['%' . $squery . '%'];
        $digitsOnly = preg_replace('/[^\d]/', '', $squery);

        if ($digitsOnly === '' || strlen($digitsOnly) < 4) {
            return array_values(array_unique($patterns));
        }

        $core = $digitsOnly;
        if (preg_match('/^61(4\d+)$/', $digitsOnly, $matches)) {
            $core = $matches[1];
        } elseif (preg_match('/^0(4\d+)$/', $digitsOnly, $matches)) {
            $core = $matches[1];
        }

        if (preg_match('/^4\d+$/', $core)) {
            return array_values(array_unique([
                '%' . $core . '%',
                '%0' . $core . '%',
                '%61' . $core . '%',
                '%' . $squery . '%',
            ]));
        }

        $patterns[] = '%' . $digitsOnly . '%';

        return array_values(array_unique($patterns));
    }

    private function formatRecipientPhone($client): string
    {
        $parts = [];
        $primary = trim((string) ($client->phone ?? ''));
        if ($primary !== '') {
            $parts[] = $primary;
        }

        $extra = trim((string) ($client->aggregated_phones ?? ''));
        if ($extra !== '') {
            foreach (preg_split('/,\s*/', $extra) as $chunk) {
                $chunk = trim($chunk);
                if ($chunk !== '' && ! in_array($chunk, $parts, true)) {
                    $parts[] = $chunk;
                }
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Normalize test type to canonical value (match migrationmanager2 stored values).
     * Legacy and full names map to: IELTS, IELTS_A, PTE, TOEFL, CAE, OET, CELPIP, MET, LANGUAGECERT.
     */
    private function normalizeTestType(string $value): string
    {
        $v = trim($value);
        $map = [
            'toefl' => 'TOEFL', 'ilets' => 'IELTS', 'pte' => 'PTE',
            'ielts academic' => 'IELTS_A', 'ielts_academic' => 'IELTS_A',
            'celpip general' => 'CELPIP', 'celpip' => 'CELPIP',
            'michigan english test (met)' => 'MET', 'met' => 'MET',
            'languagecert academic' => 'LANGUAGECERT', 'languagecert' => 'LANGUAGECERT',
        ];
        $lower = strtolower($v);
        return $map[$lower] ?? $v;
    }
}
