<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

use App\Models\Admin;
use App\Models\Agent;
use App\Models\Application;
use App\Models\Country;
use App\Models\CrmEmailTemplate;
use App\Models\Partner;
use App\Models\PartnerBranch;
use App\Models\PartnerAgreement;
use App\Models\Product;
use App\Models\Staff;
use App\Models\Workflow;
// use App\Models\Task; // Task system removed - December 2025
// use App\Models\TaskLog; // Task system removed - December 2025
//use App\Models\ActivitiesLog;
 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Services\PythonEmailParserService;
use App\Models\ActivitiesLog;
use App\Models\PartnerEmail;
use App\Models\PartnerPhone;
use App\Models\Document;
use App\Models\Invoice;
use App\Helpers\PhoneHelper;
use App\Exports\PartnerAccountsTabExport;
use App\Exports\PartnerStudentTabExport;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class PartnersController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected PythonEmailParserService $emailParser
    ) {
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
	
		 $query 		= Partner::query()->where('status',0); 
		 
		$totalData 	= $query->count();	//for all data
		if ($request->has('name')) 
		{
			$name 		= 	$request->input('name'); 
			if(trim($name) != '')
			{
				$query->where('partner_name', 'ilike', '%'.$name.'%');
			}
		}
	
		 if ($request->has('email')) 
		{
			$email 		= 	$request->input('email'); 
			if(trim($email) != '')
			{
				$query->where('email', '=', $email);
			}
		}
		 if ($request->has('reginal_code')) 
		{
			$reginal_code 		= 	$request->input('reginal_code'); 
			if(trim($reginal_code) != '')
			{
				$query->where('reginal_code', '=', $reginal_code);
			}
		}

		if ($request->has('level')) 
		{
			$level 		= 	$request->input('level'); 
			if(trim($level) != '')
			{
				$query->where('level', 'ilike', '%'.$level.'%');
			}
		}
	
		$lists = $query->withCount(['applications as student_count' => function ($query) {
            $query->whereIn('stage', ['Coe issued', 'Enrolled', 'Coe Cancelled']);
        }]);

        // Make columns sortable first
        $lists = $lists->sortable(['id' => 'desc']);
        
        // Apply sorting based on request parameters
        if ($request->has('sort') && $request->has('direction')) {
            $sort = $request->input('sort');
            $direction = $request->input('direction');
            $lists = $lists->orderBy($sort, $direction);
        } else {
            // Default sorting by student_count descending, then by id descending
            $lists = $lists->orderByDesc('student_count')->orderByDesc('id');
        }

        // Paginate the results
        $lists = $lists->paginate(config('constants.limit'));
        return view('Admin.partners.index',compact(['lists', 'totalData']));
	}
  
    public function inactivePartnerList(Request $request)
	{
		$query 	= Partner::query()->where('status',1);
        $totalData 	= $query->count();	//for all data
		if ($request->has('name'))
		{
			$name 		= 	$request->input('name');
			if(trim($name) != '')
			{
				$query->where('partner_name', 'ilike', '%'.$name.'%');
			}
		}

		 if ($request->has('email'))
		{
			$email 		= 	$request->input('email');
			if(trim($email) != '')
			{
				$query->where('email', '=', $email);
			}
		}
		 if ($request->has('reginal_code'))
		{
			$reginal_code 		= 	$request->input('reginal_code');
			if(trim($reginal_code) != '')
			{
				$query->where('reginal_code', '=', $reginal_code);
			}
		}

		if ($request->has('level'))
		{
			$level 		= 	$request->input('level');
			if(trim($level) != '')
			{
				$query->where('level', 'ilike', '%'.$level.'%');
			}
		}
      
        $lists = $query->withCount(['applications as student_count' => function ($query) {
            $query->whereIn('stage', ['Coe issued', 'Enrolled', 'Coe Cancelled']);
        }]);

        // Make columns sortable first
        $lists = $lists->sortable(['id' => 'desc']);
        
        // Apply sorting based on request parameters
        if ($request->has('sort') && $request->has('direction')) {
            $sort = $request->input('sort');
            $direction = $request->input('direction');
            $lists = $lists->orderBy($sort, $direction);
        } else {
            // Default sorting by student_count descending, then by id descending
            $lists = $lists->orderByDesc('student_count')->orderByDesc('id');
        }

        // Paginate the results
        $lists = $lists->paginate(config('constants.limit'));
        return view('Admin.partners.inactive',compact(['lists', 'totalData']));
    }
	
	public function create(Request $request)
	{
		//check authorization end
		//return view('Admin.users.create',compact(['usertype']));	
		
		return view('Admin.partners.create');	
	}
	
	
	
	public function store(Request $request)
	{
        //check authorization end
		if ($request->isMethod('post'))
		{
			$this->validate($request, [
                //'master_category' => 'required|max:255',
                //'partner_type' => 'required|max:255',
                'partner_name' => 'required|max:255',
                //'service_workflow' => 'required|max:255',
                //'currency' => 'required|max:255',
                //'email' => 'required|max:255'
                'partner_email' => 'required|array', // Validate that partner_email is an array
                'partner_email.*' => 'required|email|max:255' // Validate each email within the array
            ]);

			$requestData 		= 	$request->all();
			//echo '<pre>'; print_r($requestData);die;
            //echo $lastEmail = end($requestData['partner_email']); die;
			$obj				= 	new Partner;
			$obj->master_category	=	@$requestData['master_category'];
			$obj->partner_type	=	@$requestData['partner_type'];
			$obj->partner_name	=	@$requestData['partner_name'];
            $obj->legal_name	=	@$requestData['legal_name'];
			$obj->business_reg_no	=	@$requestData['business_reg_no'];
			$obj->service_workflow	=	@$requestData['service_workflow'];
			$obj->currency	=	'AUD';
			$obj->address	=	@$requestData['address'];
			$obj->city	=	@$requestData['city'];
			$obj->state	=	@$requestData['state'];
			$obj->zip	=	@$requestData['zip'];
			$obj->country	=	@$requestData['country'];
			//$obj->country_code	=	@$requestData['country_code'];
			$obj->is_regional	=	isset($requestData['is_regional']) ? $requestData['is_regional'] : 1; // Default to 1 (Regional) if not set
			//$obj->phone	=	@$requestData['phone'];
			//$obj->email	=	@$requestData['email'];
			$obj->fax	=	@$requestData['fax'];
			$obj->level = @$requestData['level'];
			$obj->website	=	@$requestData['website'];

			// profile_img column removed from admins table
			$obj->status	=	0; // Set status to 0 (active) for new partners
			$obj->is_archived	=	0; // Set is_archived to 0 (not archived) for new partners
			if(isset($requestData['gst'])){
				$obj->gst = 1;
			}else{
				$obj->gst = 0;
			}
			$saved				=	$obj->save();

            
			//Save to partner email table
            if(isset($requestData['partner_email']) && $requestData['partner_email'] != ''){
                $partner_email =  $requestData['partner_email'];
                $partner_email_type =  $requestData['partner_email_type'];
                for($ii=0; $ii< count($partner_email); $ii++){
                    $oe = new \App\Models\PartnerEmail;
                    $oe->user_id = @Auth::user()->id;
                    $oe->partner_id = @$obj->id;
                    $oe->partner_email_type = isset($partner_email_type[$ii]) && $partner_email_type[$ii] != '' ? $partner_email_type[$ii] : 'Personal'; // Default to 'Personal' if not set
                    $oe->partner_email = @$partner_email[$ii];
                    $oe->created_at = date('Y-m-d H:i:s');
                    $oe->updated_at = date('Y-m-d H:i:s');
                    $oe->save();

                    if( isset($partner_email_type[$ii]) && $partner_email_type[$ii] == 'Personal'){
                        //Update partner  table
                        $partnerInfo = Partner::query()->find($obj->id); // Retrieve the record by ID
                        //$lastEmail = end($requestData['partner_email']);
                        $lastEmail = $partner_email[$ii];;
                        $partnerInfo->email =  $lastEmail;
                        $partnerInfo->save(); // Save the changes
                    }
                }
            }


            //Save to partner phone table
            if(isset($requestData['partner_phone']) && $requestData['partner_phone'] != ''){
                $partner_phone_type =  $requestData['partner_phone_type'];
                $partner_phone =  $requestData['partner_phone'];
                $partner_country_code = array_map(function($code) {
                    return PhoneHelper::normalizeCountryCode($code);
                }, (array)$requestData['partner_country_code']);
                for($iii=0; $iii< count($partner_phone); $iii++){
                    $oe1 = new \App\Models\PartnerPhone;
                    $oe1->user_id = @Auth::user()->id;
                    $oe1->partner_id = @$obj->id;
                    $oe1->partner_phone_type = isset($partner_phone_type[$iii]) && $partner_phone_type[$iii] != '' ? $partner_phone_type[$iii] : 'Personal'; // Default to 'Personal' if not set
                    $oe1->partner_country_code = @$partner_country_code[$iii];
                    $oe1->partner_phone = @$partner_phone[$iii];
                    $oe1->created_at = date('Y-m-d H:i:s');
                    $oe1->updated_at = date('Y-m-d H:i:s');
                    $oe1->save();

                    if( isset($partner_phone_type[$iii]) && $partner_phone_type[$iii] == 'Personal'){
                        //Update partner  table
                        $partnerInfo1 = Partner::query()->find($obj->id); // Retrieve the record by ID

                        //$lastPhone = end($requestData['partner_phone']);
                        //$lastPhoneCountryCode = end($requestData['partner_country_code']);

                        $lastPhone = $partner_phone[$iii];;
                        $lastPhoneCountryCode =  $partner_country_code[$iii];

                        $partnerInfo1->phone =  $lastPhone;
                        $partnerInfo1->country_code =  $lastPhoneCountryCode;
                        $partnerInfo1->save(); // Save the changes
                    }
                }
            }

           

			if(isset($requestData['branchname']) && $requestData['branchname'] != ''){
                $branchname =  $requestData['branchname'];
                $branchemail =  $requestData['branchemail'];
                $branchcountry =  $requestData['branchcountry'];
                $branchcity =  $requestData['branchcity'];
                $branchstate =  $requestData['branchstate'];
                $branchaddress =  $requestData['branchaddress'];
                $branchzip =  $requestData['branchzip'];
                $branchreg =  $requestData['branchreg'];
                $branchcountry_code = array_map(function($code) {
                    return PhoneHelper::normalizeCountryCode($code);
                }, (array)$requestData['branchcountry_code']);
                $branchphone =  $requestData['branchphone'];
                for($i=0; $i< count($branchname); $i++){
                    $is_headoffice = 0;
                    if($i==0){
                        $is_headoffice = 1;
                    }
                    $o = new \App\Models\PartnerBranch;
                    $o->user_id = @Auth::user()->id;
                    $o->partner_id = @$obj->id;
                    $o->name = @$branchname[$i];
                    $o->email = @$branchemail[$i];
                    $o->country = @$branchcountry[$i];
                    $o->city = @$branchcity[$i];
                    $o->state = @$branchstate[$i];
                    $o->street = @$branchaddress[$i];
                    $o->zip = @$branchzip[$i];
                    $o->country_code = @$branchcountry_code[$i];
                    $o->phone = @$branchphone[$i];
                    $o->is_regional = isset($branchreg[$i]) ? $branchreg[$i] : 1; // Default to 1 (Regional) if not set
                    $o->is_headoffice = $is_headoffice;
                    $o->save();
                }
		    } else {
		        $is_headoffice = 1;
		        $o = new \App\Models\PartnerBranch;
				$o->user_id = @Auth::user()->id;
				$o->partner_id = @$obj->id;
				$o->name = 'Head Office';
				$o->email = @$requestData['email'];
				$o->country = @$requestData['country'];
				$o->city = @$requestData['city'];
				$o->state = @$requestData['state'];
				$o->street = @$requestData['address'];
				$o->zip = @$requestData['zip'];
				$o->country_code = PhoneHelper::normalizeCountryCode(@$requestData['country_code']);
				$o->phone =@$requestData['phone'];
				$o->is_regional = isset($requestData['is_regional']) ? $requestData['is_regional'] : 1; // Default to 1 (Regional) if not set
				$o->is_headoffice = $is_headoffice;
				$o->save();
		    }
			if(!$saved) {
				return redirect()->back()->with('error', Config::get('constants.server_error'));
			} else {
                return redirect()->route('partners.index')->with('success', 'Partners Added Successfully');
			}
		}
        return view('Admin.partners.create');
	}

	public function edit(Request $request, $id = NULL)
	{
        //check authorization end
        if ($request->isMethod('post'))
		{ //dd('ifff');
			$requestData 		= 	$request->all();
		    //echo '<pre>'; print_r($requestData); die;
			$this->validate($request, [
                //'master_category' => 'required|max:255',
                //'partner_type' => 'required|max:255',
                'partner_name' => 'required|max:255',
                //'service_workflow' => 'required|max:255',
                //'currency' => 'required|max:255',
                //'email' => 'required|max:255'
                'partner_email' => 'required|array', // Validate that partner_email is an array
                'partner_email.*' => 'required|email|max:255' // Validate each email within the array
            ]);

			$obj			= 	Partner::query()->find(@$requestData['id']);

			//$obj->master_category	=	@$requestData['master_category'];
			$obj->partner_type	=	@$requestData['partner_type'];
			$obj->partner_name	=	@$requestData['partner_name'];
            $obj->legal_name	=	@$requestData['legal_name'];
			$obj->business_reg_no	=	@$requestData['business_reg_no'];
			$obj->service_workflow	=	@$requestData['service_workflow'];
			$obj->currency	=	'AUD';
			$obj->address	=	@$requestData['address'];
			$obj->city	=	@$requestData['city'];
			$obj->state	=	@$requestData['state'];
			$obj->is_regional	=	@$requestData['is_regional'];
			$obj->zip	=	@$requestData['zip'];
			$obj->country	=	@$requestData['country'];
			//$obj->country_code	=	@$requestData['country_code'];
			//$obj->phone	=	@$requestData['phone'];
			//$obj->email	=	@$requestData['email'];
			$obj->fax	=	@$requestData['fax'];
			$obj->level = @$requestData['level'];
			$obj->website	=	@$requestData['website'];

			// profile_img column removed from admins table
			$saved							=	$obj->save();

            
            //////////////////////////////////////////////////////
            //////////Code Start For partner email////////////////
            //////////////////////////////////////////////////////
            //////////////////////////////////////////////////////
            //Update partner email table
            if(isset($requestData['rem_email'])){
                $rem_email =  @$requestData['rem_email'];
                for($irem_email=0; $irem_email< count($rem_email); $irem_email++){
                    if(\App\Models\PartnerEmail::query()->where('id', $rem_email[$irem_email])->exists()){
                        \App\Models\PartnerEmail::query()->where('id', $rem_email[$irem_email])->delete();
                    }
                }
            }

            if(isset($requestData['partner_email_type'])){
                $partner_email_type =  $requestData['partner_email_type'];
            } else {
                $partner_email_type = array();
            }

            if(isset($requestData['partner_email'])){
                $partner_email =  $requestData['partner_email'];
            } else {
                $partner_email = array();
            }

            if(count($partner_email) >0){
                for($ii=0; $ii< count($partner_email); $ii++){

                    if(\App\Models\PartnerEmail::query()->where('id', $requestData['partneremailid'][$ii])->exists()){
                        $os = \App\Models\PartnerEmail::query()->find($requestData['partneremailid'][$ii]);
                        $os->user_id = @Auth::user()->id;
                        $os->partner_id = @$obj->id;
                        $os->partner_email_type = @$partner_email_type[$ii];
                        $os->partner_email = @$partner_email[$ii];
                        $os->updated_at = date('Y-m-d H:i:s');
                        $os->save();
                    }else{
                        $oe = new \App\Models\PartnerEmail;
                        $oe->user_id = @Auth::user()->id;
                        $oe->partner_id = @$obj->id;
                        $oe->partner_email_type = isset($partner_email_type[$ii]) && $partner_email_type[$ii] != '' ? $partner_email_type[$ii] : 'Personal'; // Default to 'Personal' if not set
                        $oe->partner_email = @$partner_email[$ii];
                        $oe->created_at = date('Y-m-d H:i:s');
                        $oe->updated_at = date('Y-m-d H:i:s');
                        $oe->save();
                    }

                    if( isset($partner_email_type[$ii]) && $partner_email_type[$ii] == 'Personal'){
                        //Update partner  table
                        $partnerInfo = Partner::query()->find($obj->id); // Retrieve the record by ID
                        //$lastEmail = end($requestData['partner_email']);
                        $lastEmail = $partner_email[$ii];;
                        $partnerInfo->email =  $lastEmail;
                        $partnerInfo->save(); // Save the changes
                    }
                }
            }

            //////////////////////////////////////////////////////
            //////////Code End For partner email////////////////
            //////////////////////////////////////////////////////
            //////////////////////////////////////////////////////


            //////////////////////////////////////////////////////
            //////////Code Start For partner phone////////////////
            //////////////////////////////////////////////////////
            //////////////////////////////////////////////////////
            //Update partner phone table
            if(isset($requestData['rem_phone'])){
                $rem_phone =  @$requestData['rem_phone'];
                for($irem_phone=0; $irem_phone< count($rem_phone); $irem_phone++){
                    if(\App\Models\PartnerPhone::query()->where('id', $rem_phone[$irem_phone])->exists()){
                        \App\Models\PartnerPhone::query()->where('id', $rem_phone[$irem_phone])->delete();
                    }
                }
            }

            if(isset($requestData['partner_phone_type'])){
                $partner_phone_type =  $requestData['partner_phone_type'];
            } else {
                $partner_phone_type = array();
            }

            if(isset($requestData['partner_phone'])){
                $partner_phone =  $requestData['partner_phone'];
            } else {
                $partner_phone = array();
            }

            if(isset($requestData['partner_country_code'])){
                $partner_country_code = array_map(function($code) {
                    return PhoneHelper::normalizeCountryCode($code);
                }, (array)$requestData['partner_country_code']);
            } else {
                $partner_country_code = array();
            }

            if(count($partner_phone) >0){
                for($iii=0; $iii< count($partner_phone); $iii++){

                    if(\App\Models\PartnerPhone::query()->where('id', $requestData['partnerphoneid'][$iii])->exists()){
                        $os1 = \App\Models\PartnerPhone::query()->find($requestData['partnerphoneid'][$iii]);
                        $os1->user_id = @Auth::user()->id;
                        $os1->partner_id = @$obj->id;
                        $os1->partner_phone_type = @$partner_phone_type[$iii];
                        $os1->partner_country_code = @$partner_country_code[$iii];
                        $os1->partner_phone = @$partner_phone[$iii];
                        $os1->updated_at = date('Y-m-d H:i:s');
                        $os1->save();
                    }else{
                        $oe1 = new \App\Models\PartnerPhone;
                        $oe1->user_id = @Auth::user()->id;
                        $oe1->partner_id = @$obj->id;
                        $oe1->partner_phone_type = isset($partner_phone_type[$iii]) && $partner_phone_type[$iii] != '' ? $partner_phone_type[$iii] : 'Personal'; // Default to 'Personal' if not set
                        $oe1->partner_country_code = @$partner_country_code[$iii];
                        $oe1->partner_phone = @$partner_phone[$iii];
                        $oe1->created_at = date('Y-m-d H:i:s');
                        $oe1->updated_at = date('Y-m-d H:i:s');
                        $oe1->save();
                    }

                    if( isset($partner_phone_type[$iii]) && $partner_phone_type[$iii] == 'Personal'){
                        //Update partner  table
                        $partnerInfo1 = Partner::query()->find($obj->id); // Retrieve the record by ID

                        //$lastPhone = end($requestData['partner_phone']);
                        //$lastPhoneCountryCode = end($requestData['partner_country_code']);

                        $lastPhone = $partner_phone[$iii];;
                        $lastPhoneCountryCode =  $partner_country_code[$iii];

                        $partnerInfo1->phone =  $lastPhone;
                        $partnerInfo1->country_code =  $lastPhoneCountryCode;
                        $partnerInfo1->save(); // Save the changes
                    }
                }
            }

            //////////////////////////////////////////////////////
            //////////Code End For partner phone////////////////
            //////////////////////////////////////////////////////
            //////////////////////////////////////////////////////

           

            if(isset($requestData['rem'])){
                $rem =  @$requestData['rem'];
                for($irem=0; $irem< count($rem); $irem++){
                    if(\App\Models\PartnerBranch::query()->where('id', $rem[$irem])->exists()){
                        \App\Models\PartnerBranch::query()->where('id', $rem[$irem])->delete();
                    }
                }
            }
            if(isset($requestData['branchname'])){
                $branchname =  $requestData['branchname'];
            } else {
                $branchname = array();
            }

            if(isset($requestData['branchemail'])){
                $branchemail =  $requestData['branchemail'];
            }

            if(isset($requestData['branchcountry'])){
                $branchcountry =  $requestData['branchcountry'];
            }

            if(isset($requestData['branchcity'])){
                $branchcity =  $requestData['branchcity'];
            }

			if(isset($requestData['branchstate'])){
                $branchstate =  $requestData['branchstate'];
            }

            if(isset($requestData['branchaddress'])){
                $branchaddress =  $requestData['branchaddress'];
            }

            if(isset($requestData['branchzip'])){
                $branchzip =  $requestData['branchzip'];
            }

            if(isset($requestData['branchreg'])){
                $branchreg =  $requestData['branchreg'];
            }

            $branchcountry_code = [];
            if(isset($requestData['branchcountry_code'])){
                $branchcountry_code = array_map(function($code) {
                    return PhoneHelper::normalizeCountryCode($code);
                }, (array)$requestData['branchcountry_code']);
            }

            if(isset($requestData['branchphone'])){
                $branchphone =  $requestData['branchphone'];
            }

            if(count($branchname) >0){
                for($i=0; $i< count($branchname); $i++){
                    $is_headoffice = 0;
                    if($i==0){
                        $is_headoffice = 1;
                    }
                    if(\App\Models\PartnerBranch::query()->where('id', $requestData['branchid'][$i])->exists()){
                        $os = \App\Models\PartnerBranch::query()->find($requestData['branchid'][$i]);

                        $os->name = @$branchname[$i];
                        $os->email = @$branchemail[$i];
                        $os->country = @$branchcountry[$i];
                        $os->city = @$branchcity[$i];
                        $os->state = @$branchstate[$i];
                        $os->street = @$branchaddress[$i];
                        $os->zip = @$branchzip[$i];
                        $os->country_code = @$branchcountry_code[$i];
                        $os->phone = @$branchphone[$i];
                        $os->is_regional = @$branchreg[$i];

                        $os->save();
                    }else{
                        $o = new \App\Models\PartnerBranch;
                        $o->user_id = @Auth::user()->id;
                        $o->partner_id = @$obj->id;
                        $o->name = @$branchname[$i];
                        $o->email = @$branchemail[$i];
                        $o->country = @$branchcountry[$i];
                        $o->city = @$branchcity[$i];
                        $o->state = @$branchstate[$i];
                        $o->street = @$branchaddress[$i];
                        $o->zip = @$branchzip[$i];
                        $o->country_code = @$branchcountry_code[$i];
                        $o->phone = @$branchphone[$i];
                        $o->is_regional = isset($branchreg[$i]) ? $branchreg[$i] : 1; // Default to 1 (Regional) if not set
                        $o->is_headoffice = $is_headoffice;
                        $o->save();
                    }
                }
            }

			if(!$saved)
			{
				return redirect()->back()->with('error', Config::get('constants.server_error'));
			}

			else
			{
				return redirect()->route('partners.index')->with('success', 'Partners Edited Successfully');
			}
		}

		else
		{ //dd('elseee');
			if(isset($id) && !empty($id))
			{
                $id = $this->decodeString($id);
				if(Partner::query()->where('id', '=', $id)->exists())
				{
					$fetchedData = Partner::query()->find($id); //dd($fetchedData);

                    //Check email record is exist in partner email table
                    if( \App\Models\PartnerEmail::query()->where('partner_id', $id)->doesntExist()  && $fetchedData->email != ""){
                        $oef = new \App\Models\PartnerEmail;
                        $oef->user_id = @Auth::user()->id;
                        $oef->partner_id = $id;
                        $oef->partner_email = $fetchedData->email;
                        $oef->created_at = date('Y-m-d H:i:s');
                        $oef->updated_at = date('Y-m-d H:i:s');
                        $oef->save();
                    }

                    //Check phone record is exist in partner phone table
                    if( \App\Models\PartnerPhone::query()->where('partner_id', $id)->doesntExist()  && $fetchedData->phone != ""){
                        $oef1 = new \App\Models\PartnerPhone;
                        $oef1->user_id = @Auth::user()->id;
                        $oef1->partner_id = $id;
                        $oef1->partner_country_code = $fetchedData->country_code;
                        $oef1->partner_phone = $fetchedData->phone;
                        $oef1->created_at = date('Y-m-d H:i:s');
                        $oef1->updated_at = date('Y-m-d H:i:s');
                        $oef1->save();
                    }
					return view('Admin.partners.edit', compact(['fetchedData']));
				}
				else
				{
					return redirect()->route('partners.index')->with('error', 'Partners Not Exist');
				}
			}
			else
			{
				return redirect()->route('partners.index')->with('error', Config::get('constants.unauthorized'));
			}
		}

	}
	
	
	
	public function getpaymenttype(Request $request){
		$catid = $request->cat_id;
		$lists = \App\Models\PartnerType::query()->where('category_id', $catid)->orderby('name','ASC')->get();
		ob_start();
		?>
		<option value="">Select a Partner Type</option>
		<?php
		foreach($lists as $list){
			?>
			<option value="<?php echo $list->id; ?>"><?php echo $list->name; ?></option>
			<?php
		}
		echo ob_get_clean();
	}
	
	public function detail(Request $request, $id = NULL, $tab = NULL){
		if(isset($id) && !empty($id))  
			{				
				$id = $this->decodeString($id);	
				if(Partner::query()->where('id', '=', $id)->exists()) 
				{ 
					$fetchedData = Partner::query()->find($id);
                  
                    //Check email record is exist in partner email table
                    if( \App\Models\PartnerEmail::query()->where('partner_id', $id)->doesntExist()  && $fetchedData->email != ""){
                        $oef = new \App\Models\PartnerEmail;
                        $oef->user_id = @Auth::user()->id;
                        $oef->partner_id = $id;
                        $oef->partner_email = $fetchedData->email;
                        $oef->created_at = date('Y-m-d H:i:s');
                        $oef->updated_at = date('Y-m-d H:i:s');
                        $oef->save();
                    }

                    //Check phone record is exist in partner phone table
                    if( \App\Models\PartnerPhone::query()->where('partner_id', $id)->doesntExist()  && $fetchedData->phone != ""){
                        $oef1 = new \App\Models\PartnerPhone;
                        $oef1->user_id = @Auth::user()->id;
                        $oef1->partner_id = $id;
                        $oef1->partner_country_code = $fetchedData->country_code;
                        $oef1->partner_phone = $fetchedData->phone;
                        $oef1->created_at = date('Y-m-d H:i:s');
                        $oef1->updated_at = date('Y-m-d H:i:s');
                        $oef1->save();
                    }

                    // Resolve active tab so Blade can skip heavy queries for inactive tabs
                    $allowedTabs = ['application','partner-activities','products','branches','agreements',
                                    'noteterm','documents','notuseddocuments','accounts','promotions',
                                    'student','invoice','email-v2'];
                    $tabAliases  = ['activities' => 'partner-activities', 'notestrm' => 'noteterm'];
                    $requestedTab = $tab ?? $request->get('tab', 'application');
                    if (empty($requestedTab) || !in_array($requestedTab, array_merge($allowedTabs, array_keys($tabAliases)), true)) {
                        $requestedTab = 'application';
                    }
                    $activeTab = $tabAliases[$requestedTab] ?? $requestedTab;

                    $sharedDropdowns = $this->partnerDetailSharedDropdowns($id);
                    $sidebarContacts = $this->partnerSidebarContactDetails($id, $fetchedData);
                    $tabData = $this->partnerDetailTabData($id, $activeTab);

					return view('Admin.partners.detail', array_merge(
                        compact('fetchedData', 'activeTab'),
                        $sharedDropdowns,
                        $sidebarContacts,
                        $tabData
                    ));
				}
				else 
				{  
					return redirect()->route('partners.index')->with('error', 'Partners Not Exist');
				}	
			}
			else
			{
				return redirect()->route('partners.index')->with('error', Config::get('constants.unauthorized'));
			}
	}

	/**
	 * Cached/shared dropdown data for partner detail modals (Phase 1 — one load per request).
	 *
	 * @return array<string, mixed>
	 */
	private function partnerDetailSharedDropdowns(int $partnerId): array
	{
		// Store plain arrays in cache (not Eloquent models) to avoid broken unserialization.
		$partnerDetailWorkflowOptions = Cache::remember('partner_detail_workflow_options_v2', 3600, function () {
			return Workflow::query()
				->orderBy('name')
				->get(['id', 'name'])
				->map(static fn ($row) => ['id' => $row->id, 'name' => $row->name])
				->values()
				->all();
		});

		$partnerDetailCountries = Cache::remember('partner_detail_countries_v2', 86400, function () {
			return Country::query()
				->orderBy('name')
				->get(['id', 'name'])
				->map(static fn ($row) => ['id' => $row->id, 'name' => $row->name])
				->values()
				->all();
		});

		$partnerDetailEmailTemplates = Cache::remember('partner_detail_email_templates_v2', 3600, function () {
			return CrmEmailTemplate::query()
				->orderBy('name')
				->get(['id', 'name'])
				->map(static fn ($row) => ['id' => $row->id, 'name' => $row->name])
				->values()
				->all();
		});

		$partnerDetailStaffAssignees = Cache::remember('partner_detail_staff_assignees_v2', 3600, function () {
			return Staff::query()
				->select('id', 'office_id', 'first_name', 'last_name')
				->where('status', 1)
				->orderBy('first_name')
				->with(['office:id,office_name'])
				->get()
				->map(static fn ($admin) => [
					'id' => $admin->id,
					'first_name' => $admin->first_name,
					'last_name' => $admin->last_name,
					'office_name' => $admin->office->office_name ?? '',
				])
				->values()
				->all();
		});

		$partnerDetailSuperAgents = Cache::remember('partner_detail_super_agents_v2', 3600, function () {
			return Agent::query()
				->whereRaw("? = ANY(string_to_array(agent_type, ','))", ['Super Agent'])
				->orderBy('full_name')
				->get(['id', 'full_name', 'email'])
				->map(static fn ($agent) => [
					'id' => $agent->id,
					'full_name' => $agent->full_name,
					'email' => $agent->email,
				])
				->values()
				->all();
		});

		$partnerDetailProducts = Product::query()
			->where('partner', $partnerId)
			->orderByDesc('created_at')
			->get(['id', 'name'])
			->map(static fn ($row) => ['id' => $row->id, 'name' => $row->name])
			->values()
			->all();

		$partnerDetailModalApplications = Application::query()
			->where('partner_id', $partnerId)
			->with([
				'product:id,name',
				'partner:id,partner_name',
			])
			->orderByDesc('created_at')
			->get(['id', 'product_id', 'partner_id'])
			->map(static fn ($application) => [
				'id' => $application->id,
				'label' => trim(($application->product->name ?? '').' ('.($application->partner->partner_name ?? '').')'),
			])
			->values()
			->all();

		$partnerDetailApplicationWorkflows = Workflow::query()
			->whereIn('id', function ($query) use ($partnerId) {
				$query->select('workflow')
					->from('applications')
					->where('partner_id', $partnerId)
					->whereNotNull('workflow')
					->distinct();
			})
			->orderBy('name')
			->get(['id', 'name'])
			->map(static fn ($row) => ['id' => $row->id, 'name' => $row->name])
			->values()
			->all();

		return compact(
			'partnerDetailWorkflowOptions',
			'partnerDetailCountries',
			'partnerDetailEmailTemplates',
			'partnerDetailStaffAssignees',
			'partnerDetailSuperAgents',
			'partnerDetailProducts',
			'partnerDetailModalApplications',
			'partnerDetailApplicationWorkflows'
		);
	}

	/**
	 * AJAX endpoint for the Applications tab DataTables (server-side pagination).
	 */
	public function getApplicationTabData(Request $request, ?string $id = null)
	{
		$partnerId = $this->resolvePartnerTabPartnerId($request, $id);

		if (!$partnerId || !Partner::query()->where('id', $partnerId)->exists()) {
			return response()->json(['message' => 'Partner not found'], 404);
		}

		$draw   = (int) $request->input('draw', 1);
		$start  = max(0, (int) $request->input('start', 0));
		$length = (int) $request->input('length', 10);
		$length = $length < 1 ? 10 : min($length, 500);

		$searchValue = trim((string) data_get($request->input('search'), 'value', ''));

		$query = Application::query()->where('partner_id', $partnerId);

		$recordsTotal = (clone $query)->count();

		if ($searchValue !== '') {
			$like = '%' . addcslashes($searchValue, '%_\\') . '%';
			$query->where(function ($q) use ($like) {
				$q->whereHas('client', function ($c) use ($like) {
					$c->where('first_name', 'ilike', $like)
						->orWhere('last_name', 'ilike', $like);
				})
				->orWhereHas('product', function ($p) use ($like) {
					$p->where('name', 'ilike', $like);
				})
				->orWhere('stage', 'ilike', $like);
			});
		}

		$recordsFiltered = (clone $query)->count();

		$orderColIndex = (int) data_get($request->input('order'), '0.column', 7);
		$orderDir      = strtolower((string) data_get($request->input('order'), '0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

		$query = $this->applyPartnerApplicationTabOrdering($query, $orderColIndex, $orderDir);

		$applications = $query
			->with([
				'client:id,first_name,last_name',
				'product:id,name',
				'workflow:id,name',
			])
			->skip($start)
			->take($length)
			->get();

		$data = [];
		foreach ($applications as $application) {
			$data[] = $this->formatPartnerApplicationTabRow($application);
		}

		$statusCounts = Application::query()
			->where('partner_id', $partnerId)
			->selectRaw('status, COUNT(*) as total')
			->groupBy('status')
			->pluck('total', 'status');

		return response()->json([
			'draw'            => $draw,
			'recordsTotal'    => $recordsTotal,
			'recordsFiltered' => $recordsFiltered,
			'data'            => $data,
			'statusCounts'    => [
				0 => (int) ($statusCounts[0] ?? 0),
				1 => (int) ($statusCounts[1] ?? 0),
				2 => (int) ($statusCounts[2] ?? 0),
				3 => (int) ($statusCounts[3] ?? 0),
			],
		]);
	}

	/**
	 * AJAX endpoint for the Accounts tab DataTables (server-side pagination).
	 */
	public function getAccountsTabData(Request $request, ?string $id = null)
	{
		$partnerId = $this->resolvePartnerTabPartnerId($request, $id);

		if (!$partnerId || !Partner::query()->where('id', $partnerId)->exists()) {
			return response()->json(['message' => 'Partner not found'], 404);
		}

		$partner = Partner::query()->select('id', 'partner_name')->find($partnerId);

		$draw   = (int) $request->input('draw', 1);
		$start  = max(0, (int) $request->input('start', 0));
		$length = (int) $request->input('length', 10);
		$length = $length < 1 ? 10 : min($length, 500);

		$applicationIds = Application::query()
			->where('partner_id', $partnerId)
			->pluck('id');

		if ($applicationIds->isEmpty()) {
			return response()->json([
				'draw'            => $draw,
				'recordsTotal'    => 0,
				'recordsFiltered' => 0,
				'data'            => [],
			]);
		}

		$query = Invoice::query()->whereIn('application_id', $applicationIds);

		$recordsTotal = (clone $query)->count();

		$searchValue = trim((string) data_get($request->input('search'), 'value', ''));
		if ($searchValue !== '') {
			$like = '%' . addcslashes($searchValue, '%_\\') . '%';
			$query->where(function ($q) use ($like) {
				$q->where('id', 'ilike', $like)
					->orWhereHas('application.workflow', function ($w) use ($like) {
						$w->where('name', 'ilike', $like);
					});
			});
		}

		$recordsFiltered = (clone $query)->count();

		$orderColIndex = (int) data_get($request->input('order'), '0.column', 1);
		$orderDir      = strtolower((string) data_get($request->input('order'), '0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

		if ($orderColIndex === 0) {
			$query->orderBy('id', $orderDir);
		} else {
			$query->orderBy('invoice_date', $orderDir)->orderBy('id', $orderDir);
		}

		$invoices = $query
			->with([
				'application.workflow:id,name',
				'invoiceDetails',
				'invoicePayments',
			])
			->skip($start)
			->take($length)
			->get();

		$data = [];
		foreach ($invoices as $invoice) {
			$data[] = $this->formatPartnerAccountsTabRow($invoice, $partner);
		}

		return response()->json([
			'draw'            => $draw,
			'recordsTotal'    => $recordsTotal,
			'recordsFiltered' => $recordsFiltered,
			'data'            => $data,
		]);
	}

	/**
	 * Export all invoice rows for a partner Accounts tab (CSV or XLSX).
	 */
	public function exportAccountsTabData(Request $request, ?string $id = null)
	{
		$partnerId = $this->resolvePartnerTabPartnerId($request, $id);
		$partner = $partnerId ? Partner::query()->select(['id', 'partner_name'])->find($partnerId) : null;

		if (!$partner) {
			abort(404, 'Partner not found');
		}

		$searchValue = trim((string) $request->input('search', ''));
		$format = strtolower(trim((string) $request->input('format', 'csv')));
		$isXlsx = in_array($format, ['xlsx', 'excel'], true);

		$exportHeaders = $this->partnerAccountsTabExportHeaders();
		$baseFilename = 'Partner_Accounts_'.preg_replace('/[^a-z0-9]+/i', '_', $partner->partner_name).'_'.date('Y-m-d');
		$invoiceIds = $this->partnerAccountsTabExportInvoiceIds($partnerId, $searchValue);

		if ($isXlsx) {
			$rows = $invoiceIds->isEmpty()
				? []
				: $this->buildPartnerAccountsTabExportRows($invoiceIds, $partner);

			return Excel::download(
				new PartnerAccountsTabExport($rows, $exportHeaders),
				$baseFilename.'.xlsx'
			);
		}

		$filename = $baseFilename.'.csv';

		$headers = [
			'Content-Type'        => 'text/csv; charset=UTF-8',
			'Content-Disposition' => 'attachment; filename="'.$filename.'"',
		];

		if ($invoiceIds->isEmpty()) {
			$callback = function () use ($exportHeaders) {
				$handle = fopen('php://output', 'w');
				fputcsv($handle, $exportHeaders);
				fclose($handle);
			};

			return response()->stream($callback, 200, $headers);
		}

		$callback = function () use ($invoiceIds, $partner, $exportHeaders) {
			$handle = fopen('php://output', 'w');
			fputcsv($handle, $exportHeaders);

			foreach ($invoiceIds->chunk(200) as $chunk) {
				$invoices = Invoice::query()
					->whereIn('id', $chunk->all())
					->with([
						'application.workflow:id,name',
						'invoiceDetails',
						'invoicePayments',
					])
					->orderBy('invoice_date', 'desc')
					->orderBy('id', 'desc')
					->get();

				foreach ($invoices as $invoice) {
					fputcsv($handle, $this->formatPartnerAccountsTabExportRow($invoice, $partner));
				}
			}

			fclose($handle);
		};

		return response()->stream($callback, 200, $headers);
	}

	/**
	 * @return array<int, string>
	 */
	private function partnerAccountsTabExportHeaders(): array
	{
		return [
			'Invoice No.',
			'Issue Date',
			'Claim Type',
			'Service',
			'Partner Name',
			'Invoice Amount',
			'Paid Amount',
			'Status',
		];
	}

	/**
	 * @return \Illuminate\Support\Collection<int, int|string>
	 */
	private function partnerAccountsTabExportInvoiceIds(int $partnerId, string $searchValue = '')
	{
		$applicationIds = Application::query()
			->where('partner_id', $partnerId)
			->pluck('id');

		if ($applicationIds->isEmpty()) {
			return collect();
		}

		$query = Invoice::query()->whereIn('application_id', $applicationIds);

		if ($searchValue !== '') {
			$like = '%'.addcslashes($searchValue, '%_\\').'%';
			$query->where(function ($q) use ($like) {
				$q->where('id', 'ilike', $like)
					->orWhereHas('application.workflow', function ($w) use ($like) {
						$w->where('name', 'ilike', $like);
					});
			});
		}

		return (clone $query)
			->orderBy('invoice_date', 'desc')
			->orderBy('id', 'desc')
			->pluck('id');
	}

	/**
	 * @param  \Illuminate\Support\Collection<int, int|string>  $invoiceIds
	 * @return array<int, array<int, string>>
	 */
	private function buildPartnerAccountsTabExportRows($invoiceIds, Partner $partner): array
	{
		$rows = [];

		foreach ($invoiceIds->chunk(200) as $chunk) {
			$invoices = Invoice::query()
				->whereIn('id', $chunk->all())
				->with([
					'application.workflow:id,name',
					'invoiceDetails',
					'invoicePayments',
				])
				->orderBy('invoice_date', 'desc')
				->orderBy('id', 'desc')
				->get();

			foreach ($invoices as $invoice) {
				$rows[] = $this->formatPartnerAccountsTabExportRow($invoice, $partner);
			}
		}

		return $rows;
	}

	/**
	 * Sidebar phone/email for partner detail (single query each, no redundant exists()).
	 *
	 * @return array<string, mixed>
	 */
	private function partnerSidebarContactDetails(int $partnerId, Partner $partner): array
	{
		$partnerSidebarPhones = PartnerPhone::query()
			->select('partner_phone', 'partner_country_code', 'partner_phone_type')
			->where('partner_id', $partnerId)
			->get();

		if ($partnerSidebarPhones->isEmpty() && !empty($partner->phone)) {
			$partnerSidebarPhones = collect([(object) [
				'partner_phone'        => $partner->phone,
				'partner_country_code' => $partner->country_code,
				'partner_phone_type'   => null,
			]]);
		}

		$partnerSidebarEmails = PartnerEmail::query()
			->select('partner_email', 'partner_email_type')
			->where('partner_id', $partnerId)
			->get();

		if ($partnerSidebarEmails->isEmpty() && !empty($partner->email)) {
			$partnerSidebarEmails = collect([(object) [
				'partner_email'      => $partner->email,
				'partner_email_type' => null,
			]]);
		}

		return compact('partnerSidebarPhones', 'partnerSidebarEmails');
	}

	/**
	 * Tab-specific datasets loaded only when the active tab needs them.
	 *
	 * @return array<string, mixed>
	 */
	private function partnerDetailTabData(int $partnerId, string $activeTab): array
	{
		$partnerDocuments = null;
		$partnerNotUsedDocuments = null;
		$invoiceDocumentMap = collect();

		if ($activeTab === 'documents') {
			$partnerDocuments = Document::query()
				->where('client_id', $partnerId)
				->where('type', 'partner')
				->whereNull('not_used_doc')
				->where(function ($query) {
					$query->where('doc_type', 'documents')
						->orWhere(function ($q) {
							$q->whereNull('doc_type')->orWhere('doc_type', '');
						});
				})
				->with(['user:id,first_name'])
				->orderBy('updated_at', 'DESC')
				->get();
		}

		if ($activeTab === 'notuseddocuments') {
			$partnerNotUsedDocuments = Document::query()
				->where('client_id', $partnerId)
				->where('not_used_doc', 1)
				->where('type', 'partner')
				->where('doc_type', 'documents')
				->with(['user:id,first_name'])
				->orderBy('updated_at', 'DESC')
				->get();
		}

		if ($activeTab === 'invoice') {
			$docIds = DB::table('partner_student_invoices')
				->where('partner_id', $partnerId)
				->where('uploaded_doc_id', '>', 0)
				->pluck('uploaded_doc_id')
				->unique()
				->values();

			if ($docIds->isNotEmpty()) {
				$invoiceDocumentMap = Document::query()
					->whereIn('id', $docIds)
					->select('id', 'myfile', 'client_id', 'doc_type')
					->get()
					->keyBy('id');
			}
		}

		return compact('partnerDocuments', 'partnerNotUsedDocuments', 'invoiceDocumentMap');
	}

	private function formatPartnerAccountsTabRow(Invoice $invoicelist, ?Partner $partner): array
	{
		$workflow = $invoicelist->application?->workflow;

		$netamount = 0;
		$coom_amt  = 0;
		$total_fee = 0;
		foreach ($invoicelist->invoiceDetails as $invoiceitemdetail) {
			$netamount += $invoiceitemdetail->netamount;
			$coom_amt  += $invoiceitemdetail->comm_amt;
			$total_fee += $invoiceitemdetail->total_fee;
		}

		$amount_rec = 0;
		foreach ($invoicelist->invoicePayments as $paymentdetail) {
			$amount_rec += $paymentdetail->amount_rec;
		}

		if ((int) $invoicelist->type === 1) {
			$totaldue = $total_fee - $coom_amt;
		} elseif ((int) $invoicelist->type === 2) {
			$totaldue = $netamount - $amount_rec;
		} else {
			$totaldue = $netamount - $amount_rec;
		}

		if ((int) $invoicelist->type === 1) {
			$rtype = 'Net Claim';
		} elseif ((int) $invoicelist->type === 2) {
			$rtype = 'Gross Claim';
		} else {
			$rtype = 'General';
		}

		$issueDateHtml = e($invoicelist->invoice_date ?? '')
			. ' <span title="' . e($rtype) . '" class="ui label zippyLabel">' . e($rtype) . '</span>';

		$serviceHtml = e($workflow->name ?? '') . '<br>' . e($partner->partner_name ?? '');

		$statusHtml = ((int) $invoicelist->status === 1)
			? '<span class="ag-label--circular" style="color: #6777ef">Paid</span>'
			: '<span class="ag-label--circular" style="color: #ed5a5a">UnPaid</span>';

		$actionsHtml = '<div class="dropdown d-inline">'
			. '<button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>'
			. '<div class="dropdown-menu">'
			. '<a class="dropdown-item has-icon" href="#">Send Email</a>'
			. '<a target="_blank" class="dropdown-item has-icon" href="' . url('invoice/view/' . $invoicelist->id) . '">View</a>';

		if ((int) $invoicelist->status === 0) {
			$actionsHtml .= '<a target="_blank" class="dropdown-item has-icon" href="' . url('invoice/edit/' . $invoicelist->id) . '">Edit</a>'
				. '<a data-netamount="' . e((string) $netamount) . '" data-dueamount="' . e((string) $totaldue) . '" data-invoiceid="' . (int) $invoicelist->id . '" class="dropdown-item has-icon addpaymentmodal" href="javascript:;"> Make Payment</a>';
		}

		$actionsHtml .= '</div></div>';

		return [
			(string) $invoicelist->id,
			$issueDateHtml,
			$serviceHtml,
			'AUD ' . e((string) ($invoicelist->net_fee_rec ?? '')),
			e((string) $amount_rec),
			$statusHtml,
			$actionsHtml,
		];
	}

	/**
	 * Plain-text row for Accounts tab CSV export (no HTML).
	 *
	 * @return array<int, string>
	 */
	private function formatPartnerAccountsTabExportRow(Invoice $invoicelist, ?Partner $partner): array
	{
		$workflow = $invoicelist->application?->workflow;

		$amountRec = 0;
		foreach ($invoicelist->invoicePayments as $paymentdetail) {
			$amountRec += $paymentdetail->amount_rec;
		}

		if ((int) $invoicelist->type === 1) {
			$rtype = 'Net Claim';
		} elseif ((int) $invoicelist->type === 2) {
			$rtype = 'Gross Claim';
		} else {
			$rtype = 'General';
		}

		$status = ((int) $invoicelist->status === 1) ? 'Paid' : 'UnPaid';

		return [
			(string) $invoicelist->id,
			(string) ($invoicelist->invoice_date ?? ''),
			$rtype,
			(string) ($workflow->name ?? ''),
			(string) ($partner->partner_name ?? ''),
			'AUD '.(string) ($invoicelist->net_fee_rec ?? ''),
			(string) $amountRec,
			$status,
		];
	}

	/**
	 * @param  \Illuminate\Database\Eloquent\Builder  $query
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	private function applyPartnerApplicationTabOrdering($query, int $colIndex, string $dir)
	{
		switch ($colIndex) {
			case 0:
				return $query
					->leftJoin('admins as partner_app_clients', 'applications.client_id', '=', 'partner_app_clients.id')
					->orderBy('partner_app_clients.first_name', $dir)
					->orderBy('partner_app_clients.last_name', $dir)
					->select('applications.*');
			case 2:
				return $query
					->leftJoin('products as partner_app_products', 'applications.product_id', '=', 'partner_app_products.id')
					->orderBy('partner_app_products.name', $dir)
					->select('applications.*');
			case 3:
				return $query
					->leftJoin('workflows as partner_app_workflows', 'applications.workflow', '=', 'partner_app_workflows.id')
					->orderBy('partner_app_workflows.name', $dir)
					->select('applications.*');
			case 4:
				return $query->orderBy('applications.stage', $dir);
			case 6:
				return $query->orderBy('applications.status', $dir);
			case 8:
				return $query->orderBy('applications.updated_at', $dir);
			case 7:
			default:
				return $query->orderBy('applications.created_at', $dir);
		}
	}

	private function formatPartnerApplicationTabRow(Application $alist): array
	{
		$client   = $alist->client;
		$product  = $alist->product;
		$workflow = $alist->workflow;

		$clientEncodedId = $client
			? base64_encode(convert_uuencode($client->id))
			: '';

		$nameHtml = $client
			? '<a href="'.url('/clients/detail/'.$clientEncodedId).'">'.e(trim($client->first_name.' '.$client->last_name)).'</a>'
			: '';

		$productHtml = ($product && $clientEncodedId !== '')
			? '<a href="'.url('clients/detail/'.$clientEncodedId.'?tab=application&appid='.$alist->id).'">'.e($product->name).'</a>'
			: '';

		$enrolmentLabel = Application::enrolmentTypeLabel($alist->enrolment_type ?? null) ?: '—';

		return [
			$nameHtml,
			'',
			$productHtml,
			e($workflow->name ?? ''),
			e($alist->stage ?? ''),
			e($enrolmentLabel),
			$this->formatPartnerApplicationStatusBadge((int) $alist->status),
			!empty($alist->created_at) ? date('d/m/Y', strtotime($alist->created_at)) : '',
			!empty($alist->updated_at) ? date('d/m/Y', strtotime($alist->updated_at)) : '',
		];
	}

	private function formatPartnerApplicationStatusBadge(int $status): string
	{
		$style = 'margin-top: 5px;margin-bottom:5px;';
		$labels = [
			0 => ['In Progress', 'badge-info'],
			1 => ['Completed', 'badge-success'],
			2 => ['Discontinued', 'badge-success'],
			3 => ['Cancelled', 'badge-success'],
			4 => ['Withdrawn', 'badge-success'],
			5 => ['Deferred', 'badge-success'],
			6 => ['Future', 'badge-success'],
			7 => ['VOE', 'badge-success'],
		];

		if (!isset($labels[$status])) {
			return '';
		}

		[$label, $class] = $labels[$status];

		return '<span class="badge '.$class.'" style="'.$style.'">'.e($label).'</span>';
	}

	private const PARTNER_STUDENT_STAGES = ['Coe issued', 'Enrolled', 'Coe Cancelled'];

	private const PARTNER_STUDENT_TAB_CACHE_SECONDS = 600;

	private function partnerStudentStatusMap(): array
	{
		return [
			0 => 'In Progress', 1 => 'Completed', 2 => 'Discontinued',
			3 => 'Cancelled',   4 => 'Withdrawn', 5 => 'Deferred',
			6 => 'Future',      7 => 'VOE',       8 => 'Refund',
		];
	}

	private function partnerStudentStatusLabelToInt(string $label): ?int
	{
		foreach ($this->partnerStudentStatusMap() as $statusId => $statusLabel) {
			if ($statusLabel === $label) {
				return (int) $statusId;
			}
		}

		return null;
	}

	/**
	 * Decode a partner ID from an AJAX route segment (handles URL encoding + missing base64 padding).
	 *
	 * @return int|false
	 */
	private function decodePartnerRouteId(?string $encodedId)
	{
		if ($encodedId === null || $encodedId === '') {
			return false;
		}

		$encodedId = rawurldecode(trim($encodedId));
		$candidates = [$encodedId];

		if (!str_contains($encodedId, '=')) {
			$padLen = (4 - (strlen($encodedId) % 4)) % 4;
			if ($padLen > 0) {
				$candidates[] = $encodedId.str_repeat('=', $padLen);
			}
		}

		foreach (array_unique($candidates) as $candidate) {
			$decoded = $this->decodeString($candidate);
			if ($decoded !== false && $decoded !== '') {
				return (int) $decoded;
			}
		}

		return false;
	}

	/**
	 * Resolve partner id for tab AJAX endpoints: prefer numeric partner_id query param,
	 * fall back to encoded route segment (legacy bookmarks / old JS URLs).
	 *
	 * @return int|false
	 */
	private function resolvePartnerTabPartnerId(Request $request, ?string $routeId)
	{
		$numericId = (int) $request->input('partner_id', 0);
		if ($numericId > 0) {
			return Partner::query()->where('id', $numericId)->exists() ? $numericId : false;
		}

		if ($routeId !== null && $routeId !== '') {
			$decoded = $this->decodePartnerRouteId($routeId);

			return $decoded ?: false;
		}

		return false;
	}

	/**
	 * Log Student tab diagnostics to storage/logs/laravel.log (grep: [partner-student-tab]).
	 */
	private function logPartnerStudentTab(string $level, string $message, array $context = []): void
	{
		$context['scope'] = 'partner_student_tab';

		try {
			Log::{$level}('[partner-student-tab] '.$message, $context);
		} catch (\Throwable $e) {
			// Logging must never break the request.
		}
	}

	private function partnerStudentTabLikeOperator(): string
	{
		return DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
	}

	private function partnerStudentTabOrderNullsSuffix(): string
	{
		return DB::getDriverName() === 'pgsql' ? ' NULLS LAST' : '';
	}

	private static function partnerStudentTabCountCacheKey(int $partnerId, string $list): string
	{
		return "partner_student_count_v6_{$partnerId}_{$list}";
	}

	private function peekCachedPartnerStudentTabCount(int $partnerId, string $list): ?int
	{
		try {
			$cached = Cache::get(self::partnerStudentTabCountCacheKey($partnerId, $list));

			return $cached === null ? null : (int) $cached;
		} catch (\Throwable $e) {
			return null;
		}
	}

	/**
	 * @return array{0: int, 1: int}
	 */
	private function resolvePartnerStudentTabRecordCounts(
		Request $request,
		int $partnerId,
		string $list,
		int $start,
		int $length,
		int $rowCount,
		$baseQuery,
		$filteredQuery,
		string $searchValue,
		string $statusFilter
	): array {
		$deferCounts = $request->boolean('defer_counts')
			&& $start === 0
			&& $searchValue === ''
			&& $statusFilter === '';

		if ($deferCounts) {
			$cachedTotal = $this->peekCachedPartnerStudentTabCount($partnerId, $list);
			if ($cachedTotal !== null) {
				return [$cachedTotal, $cachedTotal];
			}

			return $this->estimatePartnerStudentTabCounts($start, $length, $rowCount);
		}

		try {
			$recordsTotal = $this->countPartnerStudentTabQuery(clone $baseQuery, $partnerId, $list, '', '');
			$recordsFiltered = ($searchValue === '' && $statusFilter === '')
				? $recordsTotal
				: $this->countPartnerStudentTabQuery(clone $filteredQuery, $partnerId, $list, $searchValue, $statusFilter);

			return [$recordsTotal, $recordsFiltered];
		} catch (\Throwable $countException) {
			$this->logPartnerStudentTab('warning', 'Student tab count failed; using estimate', [
				'partner_id' => $partnerId,
				'message'    => $countException->getMessage(),
			]);

			return $this->estimatePartnerStudentTabCounts($start, $length, $rowCount);
		}
	}

	/**
	 * @return \Illuminate\Database\Query\Builder
	 */
	private function partnerStudentTabIdQuery(int $partnerId, string $list)
	{
		$query = DB::table('applications')
			->where('applications.partner_id', $partnerId)
			->whereIn('applications.stage', self::PARTNER_STUDENT_STAGES);

		if ($list === 'inactive') {
			$query->where('applications.overall_status', 1);
		}

		return $query;
	}

	/**
	 * Parse DOB search input (DD/MM/YYYY) to database format (Y-m-d).
	 */
	private function parsePartnerStudentTabDobSearch(string $searchValue): ?string
	{
		if (! str_contains($searchValue, '/')) {
			return null;
		}

		$parts = array_map('trim', explode('/', $searchValue));
		if (count($parts) !== 3) {
			return null;
		}

		[$day, $month, $year] = $parts;
		if (! ctype_digit($day) || ! ctype_digit($month) || ! ctype_digit($year)) {
			return null;
		}

		$day = (int) $day;
		$month = (int) $month;
		$year = (int) $year;

		if (strlen((string) $year) !== 4 || ! checkdate($month, $day, $year)) {
			return null;
		}

		return sprintf('%04d-%02d-%02d', $year, $month, $day);
	}

	/**
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @return \Illuminate\Database\Query\Builder
	 */
	private function applyPartnerStudentTabSearch($query, string $searchValue)
	{
		if ($searchValue === '') {
			return $query;
		}

		$like = '%'.addcslashes($searchValue, '%_\\').'%';
		$likeOp = $this->partnerStudentTabLikeOperator();
		$dobDb = $this->parsePartnerStudentTabDobSearch($searchValue);

		return $query->where(function ($q) use ($like, $likeOp, $dobDb) {
			$q->where('applications.student_id', $likeOp, $like)
				->orWhere('applications.stage', $likeOp, $like)
				->orWhere('applications.student_add_notes', $likeOp, $like)
				->orWhereExists(function ($sub) use ($like, $likeOp, $dobDb) {
					$sub->select(DB::raw('1'))
						->from('admins')
						->whereColumn('admins.id', 'applications.client_id')
						->where(function ($a) use ($like, $likeOp, $dobDb) {
							$a->where('admins.first_name', $likeOp, $like)
								->orWhere('admins.last_name', $likeOp, $like)
								->orWhere('admins.client_id', $likeOp, $like);
							if ($dobDb !== null) {
								$a->orWhere('admins.dob', '=', $dobDb);
							}
						});
				})
				->orWhereExists(function ($sub) use ($like, $likeOp) {
					$sub->select(DB::raw('1'))
						->from('products')
						->whereColumn('products.id', 'applications.product_id')
						->where('products.name', $likeOp, $like);
				});
		});
	}

	/**
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @return \Illuminate\Database\Query\Builder
	 */
	private function applyPartnerStudentTabStatusFilter($query, string $statusFilter)
	{
		if ($statusFilter === '') {
			return $query;
		}

		$statusId = $this->partnerStudentStatusLabelToInt($statusFilter);
		if ($statusId !== null) {
			$query->where('applications.status', $statusId);
		}

		return $query;
	}

	/**
	 * Normalise DataTables status filter sent from the Student tab toolbar.
	 */
	private function normalisePartnerStudentTabStatusFilter(string $statusFilter): string
	{
		$statusFilter = trim($statusFilter);

		return ($statusFilter === '' || $statusFilter === '-' || $statusFilter === 'null') ? '' : $statusFilter;
	}

	/**
	 * Count student-tab rows; cache unfiltered totals for 5 minutes per partner/list.
	 */
	private function countPartnerStudentTabQuery($query, int $partnerId, string $list, string $searchValue, string $statusFilter): int
	{
		if ($searchValue !== '' || $statusFilter !== '') {
			return (clone $query)->count();
		}

		$cacheKey = self::partnerStudentTabCountCacheKey($partnerId, $list);

		try {
			return (int) Cache::remember($cacheKey, self::PARTNER_STUDENT_TAB_CACHE_SECONDS, function () use ($query) {
				return (clone $query)->count();
			});
		} catch (\Throwable $e) {
			return (clone $query)->count();
		}
	}

	/**
	 * DataTables column index => latest application_fee_options field used for ordering.
	 *
	 * @return array<int, string>
	 */
	private function partnerStudentTabFeeOrderColumns(): array
	{
		return [
			9  => 'total_course_fee_amount',
			10 => 'enrolment_fee_amount',
			11 => 'material_fees',
			12 => 'tution_fees',
			13 => 'fee_reported_by_college',
			14 => 'bonus_amount',
			15 => 'bonus_pending_amount',
			16 => 'scholarship_fee_amount',
			17 => 'commission_as_per_fee_reported',
			18 => 'commission_payable_as_per_anticipated_fee',
			19 => 'commission_paid_as_per_fee_reported',
			20 => 'commission_pending',
		];
	}

	private function partnerStudentTabLatestFeeOrderExpression(string $feeColumn): string
	{
		if (! in_array($feeColumn, $this->partnerStudentTabFeeOrderColumns(), true)) {
			throw new \InvalidArgumentException('Invalid fee column for ordering.');
		}

		return '(SELECT afo.'.$feeColumn.' FROM application_fee_options afo'
			.' WHERE afo.app_id = applications.id'
			.' AND afo.id = (SELECT MAX(afo2.id) FROM application_fee_options afo2 WHERE afo2.app_id = applications.id))';
	}

	private function partnerStudentTabNumericOrderRaw(string $valueExpression, string $dirSql, string $nullsSuffix): string
	{
		if (DB::getDriverName() === 'pgsql') {
			return "COALESCE(({$valueExpression})::numeric, 0) {$dirSql}{$nullsSuffix}";
		}

		return "COALESCE(CAST({$valueExpression} AS DECIMAL(15,2)), 0) {$dirSql}";
	}

	/**
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @return \Illuminate\Database\Query\Builder
	 */
	private function applyPartnerStudentTabOrdering($query, int $colIndex, string $dir)
	{
		$dirSql = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';
		$nullsSuffix = $this->partnerStudentTabOrderNullsSuffix();

		$feeColumns = $this->partnerStudentTabFeeOrderColumns();
		if (isset($feeColumns[$colIndex])) {
			$feeExpr = $this->partnerStudentTabLatestFeeOrderExpression($feeColumns[$colIndex]);

			return $query
				->orderByRaw($this->partnerStudentTabNumericOrderRaw($feeExpr, $dirSql, $nullsSuffix))
				->orderBy('applications.id', $dir)
				->select('applications.id');
		}

		switch ($colIndex) {
			case 1:
				return $query->orderByRaw(
					"(SELECT a.client_id FROM admins a WHERE a.id = applications.client_id LIMIT 1) {$dirSql}{$nullsSuffix}"
				)->select('applications.id');
			case 2:
				return $query
					->orderByRaw("(SELECT a.first_name FROM admins a WHERE a.id = applications.client_id LIMIT 1) {$dirSql}{$nullsSuffix}")
					->orderByRaw("(SELECT a.last_name FROM admins a WHERE a.id = applications.client_id LIMIT 1) {$dirSql}{$nullsSuffix}")
					->select('applications.id');
			case 3:
				return $query->orderByRaw(
					"(SELECT a.dob FROM admins a WHERE a.id = applications.client_id LIMIT 1) {$dirSql}{$nullsSuffix}"
				)->select('applications.id');
			case 4:
				return $query->orderBy('applications.student_id', $dir)->select('applications.id');
			case 6:
				return $query->orderByRaw(
					"(SELECT p.name FROM products p WHERE p.id = applications.product_id LIMIT 1) {$dirSql}{$nullsSuffix}"
				)->select('applications.id');
			case 7:
				return $query->orderBy('applications.start_date', $dir)->select('applications.id');
			case 8:
				return $query->orderBy('applications.end_date', $dir)->select('applications.id');
			case 21:
				return $query->orderBy('applications.status', $dir)->select('applications.id');
			case 22:
				if (Schema::hasColumn('applications', 'enrolment_type')) {
					return $query->orderBy('applications.enrolment_type', $dir)->select('applications.id');
				}

				return $query->orderBy('applications.id', $dir)->select('applications.id');
			default:
				return $query->orderBy('applications.id', $dir)->select('applications.id');
		}
	}

	/**
	 * @param  \Illuminate\Support\Collection<int, int>  $applicationIds
	 * @return array<int, object>
	 */
	private function partnerStudentLatestFeesForIds($applicationIds): array
	{
		if ($applicationIds->isEmpty()) {
			return [];
		}

		$driver = DB::getDriverName();
		$latestFeeConstraint = $driver === 'pgsql'
			? 'afo.id = (SELECT MAX(afo2.id) FROM application_fee_options afo2 WHERE afo2.app_id = afo.app_id)'
			: 'afo.id = (SELECT MAX(afo2.id) FROM application_fee_options afo2 WHERE afo2.app_id = afo.app_id LIMIT 1)';

		$fees = DB::table('application_fee_options as afo')
			->whereIn('afo.app_id', $applicationIds->all())
			->whereRaw($latestFeeConstraint)
			->get();

		$latest = [];
		foreach ($fees as $fee) {
			$latest[$fee->app_id] = $fee;
		}

		return $latest;
	}

	private function formatPartnerStudentTabRow(object $data, bool $isActive, string $partnerName): array
	{
		$statusMap = $this->partnerStudentStatusMap();
		$clientEncodedId = !empty($data->client_id)
			? base64_encode(convert_uuencode((string) $data->client_id))
			: '';

		$crmRef = !empty($data->client_reference)
			? '<a href="'.url('/clients/detail/'.$clientEncodedId.'/application/'.$data->id).'" target="_blank">'.e($data->client_reference).'</a>'
			: 'N/P';

		$dob = 'N/P';
		if (!empty($data->dob)) {
			$dobArr = explode('-', $data->dob);
			$dob = ($dobArr[2] ?? '').'/'.($dobArr[1] ?? '').'/'.($dobArr[0] ?? '');
		}

		$coursename = 'N/P';
		if (!empty($data->coursename)) {
			$coursename = '<a href="'.url('/clients/detail/'.$clientEncodedId.'/application/'.$data->id).'" target="_blank">'.e($data->coursename).'</a>';
		}

		$overallStatusBtn = $isActive
			? '<button class="btn btn-sm btn-primary dropdown-item change-application-overall-status-btn" data-id="'.$data->id.'" data-application-overall-status="'.$data->overall_status.'" data-bs-toggle="modal" data-bs-target="#changeApplicationOverallStatusModal">Change Application To Inactive</button>'
			: '<button class="btn btn-sm btn-primary dropdown-item change-application-overall-status-btn" data-id="'.$data->id.'" data-application-overall-status="'.$data->overall_status.'" data-bs-toggle="modal" data-bs-target="#changeApplicationOverallStatusModal">Change Application To Active</button>';

		$actionHtml = '<div class="dropdown d-inline">
			<button style="margin-top:3px;margin-bottom:3px;" class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
			<div class="dropdown-menu">
				<button class="btn btn-sm btn-primary dropdown-item change-status-btn" data-id="'.$data->id.'" data-current-status="'.$data->status.'" data-bs-toggle="modal" data-bs-target="#changeStatusModal">Change Status</button>
				'.$overallStatusBtn.'
			</div>
		</div>';

		return [
			'',
			$crmRef,
			($data->first_name ?? '') !== '' ? $data->first_name.' '.$data->last_name : 'N/P',
			$dob,
			($data->student_id ?? '') !== '' ? $data->student_id : 'N/P',
			e($partnerName) ?: 'N/P',
			$coursename,
			!empty($data->start_date) ? date('d/m/Y', strtotime($data->start_date)) : 'N/P',
			!empty($data->end_date) ? date('d/m/Y', strtotime($data->end_date)) : 'N/P',
			$data->total_course_fee_amount ?? '0.00',
			$data->enrolment_fee_amount ?? '0.00',
			$data->material_fees ?? '0.00',
			$data->tution_fees ?? '0.00',
			$data->fee_reported_by_college ?? '0.00',
			$data->bonus_amount ?? '0.00',
			$data->bonus_pending_amount ?? '0.00',
			$data->scholarship_fee_amount ?? '0.00',
			$data->commission_as_per_fee_reported ?? '0.00',
			$data->commission_payable_as_per_anticipated_fee ?? '0.00',
			$data->commission_paid_as_per_fee_reported ?? '0.00',
			$data->commission_pending ?? '0.00',
			$statusMap[$data->status] ?? '',
			(string) Application::normalizeEnrolmentType($data->enrolment_type ?? null),
			(string) $data->id,
			'<textarea class="'.($isActive ? 'note-field' : 'note-field1').'" data-studentid="'.$data->id.'">'.e($data->student_add_notes ?? '').'</textarea>',
			$actionHtml,
		];
	}

	/**
	 * @return array{claimed: float, anticipated: float, paid: float, pending: float}
	 */
	private function computePartnerStudentTabTotals(int $partnerId, string $list, string $searchValue, string $statusFilter): array
	{
		$zero = ['claimed' => 0.0, 'anticipated' => 0.0, 'paid' => 0.0, 'pending' => 0.0];

		$idQuery = $this->partnerStudentTabIdQuery($partnerId, $list);
		$idQuery = $this->applyPartnerStudentTabSearch($idQuery, $searchValue);
		$idQuery = $this->applyPartnerStudentTabStatusFilter($idQuery, $statusFilter);

		$totals = DB::table('application_fee_options as afo')
			->whereIn('afo.app_id', (clone $idQuery)->select('applications.id'))
			->whereRaw(
				'afo.id = (SELECT MAX(afo2.id) FROM application_fee_options afo2 WHERE afo2.app_id = afo.app_id)'
			)
			->selectRaw('
				COALESCE(SUM(afo.commission_as_per_fee_reported), 0) as claimed,
				COALESCE(SUM(afo.commission_payable_as_per_anticipated_fee), 0) as anticipated,
				COALESCE(SUM(afo.commission_paid_as_per_fee_reported), 0) as paid,
				COALESCE(SUM(afo.commission_pending), 0) as pending
			')
			->first();

		if (!$totals) {
			return $zero;
		}

		return [
			'claimed'     => round((float) ($totals->claimed ?? 0), 2),
			'anticipated' => round((float) ($totals->anticipated ?? 0), 2),
			'paid'        => round((float) ($totals->paid ?? 0), 2),
			'pending'     => round((float) ($totals->pending ?? 0), 2),
		];
	}

	/**
	 * Cached commission totals when list has no search/status filter applied.
	 *
	 * @return array{claimed: float, anticipated: float, paid: float, pending: float}
	 */
	private function partnerStudentTabTotals(int $partnerId, string $list, string $searchValue, string $statusFilter): array
	{
		if ($searchValue !== '' || $statusFilter !== '') {
			return $this->computePartnerStudentTabTotals($partnerId, $list, $searchValue, $statusFilter);
		}

		$cacheKey = "partner_students_totals_v7_{$partnerId}_{$list}";

		try {
			return Cache::remember($cacheKey, self::PARTNER_STUDENT_TAB_CACHE_SECONDS, function () use ($partnerId, $list) {
				return $this->computePartnerStudentTabTotals($partnerId, $list, '', '');
			});
		} catch (\Throwable $e) {
			return $this->computePartnerStudentTabTotals($partnerId, $list, '', '');
		}
	}

	/**
	 * Estimate row counts when the cached/live count query fails (keeps table usable).
	 *
	 * @return array{0: int, 1: int}
	 */
	private function estimatePartnerStudentTabCounts(int $start, int $length, int $rowCount): array
	{
		$recordsFiltered = $start + $rowCount;
		$recordsTotal = $rowCount >= $length ? $recordsFiltered + 1 : $recordsFiltered;

		return [$recordsTotal, $recordsFiltered];
	}

	/**
	 * Bulk-fetch student rows for client-side DataTables (one JSON payload: active + inactive).
	 *
	 * @return array<int, object>
	 */
	private function partnerStudentTabBulkQuery(int $partnerId, ?int $overallStatus): array
	{
		$bindings = [$partnerId];
		$overallFilter = '';
		if ($overallStatus !== null) {
			$overallFilter = 'AND applications.overall_status = ?';
			$bindings[] = $overallStatus;
		}

		$stages = implode("', '", self::PARTNER_STUDENT_STAGES);

		return DB::select("
			SELECT DISTINCT ON (applications.id)
				applications.id,
				applications.client_id,
				applications.status,
				applications.overall_status,
				applications.student_id,
				applications.start_date,
				applications.end_date,
				applications.enrolment_type,
				applications.student_add_notes,
				admins.client_id          AS client_reference,
				admins.first_name,
				admins.last_name,
				admins.dob,
				products.name             AS coursename,
				afo.total_course_fee_amount,
				afo.enrolment_fee_amount,
				afo.material_fees,
				afo.tution_fees,
				afo.fee_reported_by_college,
				afo.bonus_amount,
				afo.bonus_pending_amount,
				afo.scholarship_fee_amount,
				afo.commission_as_per_fee_reported,
				afo.commission_payable_as_per_anticipated_fee,
				afo.commission_paid_as_per_fee_reported,
				afo.commission_pending
			FROM applications
			INNER JOIN admins       ON admins.id       = applications.client_id
			LEFT  JOIN products     ON products.id     = applications.product_id
			LEFT  JOIN application_fee_options afo ON afo.app_id = applications.id
			WHERE applications.partner_id = ?
			  AND applications.stage IN ('{$stages}')
			  {$overallFilter}
			ORDER BY applications.id ASC, afo.id DESC
		", $bindings);
	}

	/**
	 * AJAX endpoint for the Student tab DataTables (server-side pagination).
	 */
	public function getStudentTabData(Request $request, ?string $id = null)
	{
		$draw = (int) $request->input('draw', 1);

		$this->logPartnerStudentTab('info', 'getStudentTabData request started', [
			'route_id'   => $id,
			'partner_id' => $request->input('partner_id'),
			'list'       => $request->input('list', 'active'),
			'start'      => $request->input('start', 0),
			'length'     => $request->input('length', 10),
		]);

		try {
			$partnerId = $this->resolvePartnerTabPartnerId($request, $id);
			if (!$partnerId) {
				$this->logPartnerStudentTab('warning', 'Invalid partner route id', [
					'route_id'   => $id,
					'partner_id' => $request->input('partner_id'),
				]);

				return response()->json([
					'draw'            => $draw,
					'recordsTotal'    => 0,
					'recordsFiltered' => 0,
					'data'            => [],
					'error'           => 'Invalid partner reference.',
				], 400);
			}

			$partner = Partner::query()->select(['id', 'partner_name'])->find($partnerId);

			if (!$partner) {
				$this->logPartnerStudentTab('warning', 'Partner not found', ['route_id' => $id, 'partner_id' => $partnerId]);

				return response()->json([
					'draw'            => $draw,
					'recordsTotal'    => 0,
					'recordsFiltered' => 0,
					'data'            => [],
					'error'           => 'Partner not found.',
				], 404);
			}

			$list = $request->input('list', 'active') === 'inactive' ? 'inactive' : 'active';
			$isActive = $list !== 'inactive';

			$start  = max(0, (int) $request->input('start', 0));
			$length = (int) $request->input('length', 10);
			$length = $length < 1 ? 10 : min($length, 500);

			$searchValue  = trim((string) data_get($request->input('search'), 'value', ''));
			$statusFilter = $this->normalisePartnerStudentTabStatusFilter((string) $request->input('status_filter', ''));

			$baseQuery = $this->partnerStudentTabIdQuery($partnerId, $list);
			$filteredQuery = $this->applyPartnerStudentTabSearch(clone $baseQuery, $searchValue);
			$filteredQuery = $this->applyPartnerStudentTabStatusFilter($filteredQuery, $statusFilter);

			$orderColIndex = (int) data_get($request->input('order'), '0.column', -1);
			$orderDir      = strtolower((string) data_get($request->input('order'), '0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

			$pageIds = collect();
			try {
				$pageIdQuery = $this->applyPartnerStudentTabOrdering(clone $filteredQuery, $orderColIndex, $orderDir);
				$pageIds = $pageIdQuery->skip($start)->take($length)->pluck('applications.id');
			} catch (\Throwable $pageException) {
				$this->logPartnerStudentTab('warning', 'Student tab page query failed', [
					'partner_id' => $partnerId,
					'list'       => $list,
					'message'    => $pageException->getMessage(),
				]);
			}

			$data = [];
			if ($pageIds->isNotEmpty()) {
				$applications = DB::table('applications')
					->join('admins', 'admins.id', '=', 'applications.client_id')
					->leftJoin('products', 'products.id', '=', 'applications.product_id')
					->whereIn('applications.id', $pageIds->all())
					->select(
						'applications.*',
						'admins.client_id as client_reference',
						'admins.first_name',
						'admins.last_name',
						'admins.dob',
						'products.name as coursename'
					)
					->get()
					->keyBy('id');

				$latestFees = $this->partnerStudentLatestFeesForIds($pageIds);

				foreach ($pageIds as $appId) {
					try {
						$row = $applications->get($appId);
						if (!$row) {
							continue;
						}
						$fee = $latestFees[$appId] ?? null;
						if ($fee) {
							foreach ([
								'total_course_fee_amount', 'enrolment_fee_amount', 'material_fees', 'tution_fees',
								'fee_reported_by_college', 'bonus_amount', 'bonus_pending_amount', 'scholarship_fee_amount',
								'commission_as_per_fee_reported', 'commission_payable_as_per_anticipated_fee',
								'commission_paid_as_per_fee_reported', 'commission_pending',
							] as $feeField) {
								$row->{$feeField} = $fee->{$feeField} ?? '0.00';
							}
						}
						$data[] = $this->formatPartnerStudentTabRow($row, $isActive, $partner->partner_name);
					} catch (\Throwable $rowException) {
						$this->logPartnerStudentTab('error', 'Student row formatting failed', [
							'partner_id'     => $partnerId,
							'application_id' => $appId,
							'message'        => $rowException->getMessage(),
						]);
					}
				}
			}

			[$recordsTotal, $recordsFiltered] = $this->resolvePartnerStudentTabRecordCounts(
				$request,
				$partnerId,
				$list,
				$start,
				$length,
				count($data),
				$baseQuery,
				$filteredQuery,
				$searchValue,
				$statusFilter
			);

			$this->logPartnerStudentTab('info', 'getStudentTabData request completed', [
				'partner_id'       => $partnerId,
				'records_total'    => $recordsTotal,
				'records_filtered' => $recordsFiltered,
				'rows_returned'    => count($data),
			]);

			return response()->json([
				'draw'            => $draw,
				'recordsTotal'    => $recordsTotal,
				'recordsFiltered' => $recordsFiltered,
				'data'            => $data,
			]);
		} catch (\Throwable $e) {
			$this->logPartnerStudentTab('error', 'getStudentTabData failed', [
				'route_id'  => $id,
				'message'   => $e->getMessage(),
				'file'      => $e->getFile(),
				'line'      => $e->getLine(),
				'exception' => $e::class,
			]);

			return response()->json([
				'draw'            => $draw,
				'recordsTotal'    => 0,
				'recordsFiltered' => 0,
				'data'            => [],
				'error'           => 'Unable to load student data.',
			]);
		}
	}

	/**
	 * Lightweight cached row counts for Student tab pagination (no row payload).
	 */
	public function getStudentTabCount(Request $request, ?string $id = null)
	{
		try {
			$partnerId = $this->resolvePartnerTabPartnerId($request, $id);

			if (!$partnerId) {
				return response()->json(['status' => false, 'message' => 'Partner not found'], 404);
			}

			$list = $request->input('list', 'active') === 'inactive' ? 'inactive' : 'active';
			$searchValue  = trim((string) $request->input('search', ''));
			$statusFilter = $this->normalisePartnerStudentTabStatusFilter((string) $request->input('status_filter', ''));

			$baseQuery = $this->partnerStudentTabIdQuery($partnerId, $list);
			$filteredQuery = $this->applyPartnerStudentTabSearch(clone $baseQuery, $searchValue);
			$filteredQuery = $this->applyPartnerStudentTabStatusFilter($filteredQuery, $statusFilter);

			$recordsTotal = $this->countPartnerStudentTabQuery(clone $baseQuery, $partnerId, $list, '', '');
			$recordsFiltered = ($searchValue === '' && $statusFilter === '')
				? $recordsTotal
				: $this->countPartnerStudentTabQuery(clone $filteredQuery, $partnerId, $list, $searchValue, $statusFilter);

			$this->logPartnerStudentTab('info', 'getStudentTabCount completed', [
				'partner_id'       => $partnerId,
				'list'             => $list,
				'records_total'    => $recordsTotal,
				'records_filtered' => $recordsFiltered,
			]);

			return response()->json([
				'status'          => true,
				'recordsTotal'    => $recordsTotal,
				'recordsFiltered' => $recordsFiltered,
			]);
		} catch (\Throwable $e) {
			$this->logPartnerStudentTab('warning', 'getStudentTabCount failed; returning estimate', [
				'route_id'  => $id,
				'message'   => $e->getMessage(),
				'exception' => $e::class,
			]);

			$start    = max(0, (int) $request->input('start', 0));
			$length   = max(1, (int) $request->input('length', 25));
			$rowCount = max(0, (int) $request->input('row_count', 0));
			[$recordsTotal, $recordsFiltered] = $this->estimatePartnerStudentTabCounts($start, $length, $rowCount);

			return response()->json([
				'status'          => true,
				'recordsTotal'    => $recordsTotal,
				'recordsFiltered' => $recordsFiltered,
				'estimated'       => true,
			]);
		}
	}

	/**
	 * Commission totals for the Student tab (respects search + status filter).
	 */
	public function getStudentTabTotals(Request $request, ?string $id = null)
	{
		try {
			$partnerId = $this->resolvePartnerTabPartnerId($request, $id);

			if (!$partnerId) {
				$this->logPartnerStudentTab('warning', 'Totals: partner not found', [
					'route_id'   => $id,
					'partner_id' => $request->input('partner_id'),
				]);

				return response()->json(['status' => false, 'message' => 'Partner not found'], 404);
			}

			$list = $request->input('list', 'active') === 'inactive' ? 'inactive' : 'active';
			$searchValue  = trim((string) $request->input('search', ''));
			$statusFilter = $this->normalisePartnerStudentTabStatusFilter((string) $request->input('status_filter', ''));

			$totals = $this->partnerStudentTabTotals($partnerId, $list, $searchValue, $statusFilter);

			$this->logPartnerStudentTab('info', 'getStudentTabTotals completed', [
				'partner_id' => $partnerId,
				'list'       => $list,
				'claimed'    => $totals['claimed'],
			]);

			return response()->json([
				'status'      => true,
				'claimed'     => number_format($totals['claimed'], 2, '.', ''),
				'anticipated' => number_format($totals['anticipated'], 2, '.', ''),
				'paid'        => number_format($totals['paid'], 2, '.', ''),
				'pending'     => number_format($totals['pending'], 2, '.', ''),
			]);
		} catch (\Throwable $e) {
			$this->logPartnerStudentTab('error', 'getStudentTabTotals failed', [
				'route_id'  => $id,
				'message'   => $e->getMessage(),
				'file'      => $e->getFile(),
				'line'      => $e->getLine(),
				'exception' => $e::class,
			]);

			return response()->json(['status' => false, 'message' => 'Unable to load totals.'], 500);
		}
	}

	/**
	 * Export all student rows for a partner list (CSV or XLSX).
	 */
	public function exportStudentTabData(Request $request, ?string $id = null)
	{
		$partnerId = $this->resolvePartnerTabPartnerId($request, $id);
		$partner = $partnerId ? Partner::query()->select(['id', 'partner_name'])->find($partnerId) : null;

		if (!$partner) {
			abort(404, 'Partner not found');
		}

		$list = $request->input('list', 'active') === 'inactive' ? 'inactive' : 'active';
		$isActive = $list !== 'inactive';
		$searchValue  = trim((string) $request->input('search', ''));
		$statusFilter = $this->normalisePartnerStudentTabStatusFilter((string) $request->input('status_filter', ''));
		$format = strtolower(trim((string) $request->input('format', 'csv')));
		$isXlsx = in_array($format, ['xlsx', 'excel'], true);

		$idQuery = $this->partnerStudentTabIdQuery($partnerId, $list);
		$idQuery = $this->applyPartnerStudentTabSearch($idQuery, $searchValue);
		$idQuery = $this->applyPartnerStudentTabStatusFilter($idQuery, $statusFilter);
		$applicationIds = $idQuery->orderBy('applications.id')->pluck('applications.id');

		$exportHeaders = $this->partnerStudentTabExportHeaders();
		$baseFilename = 'Partner_Student_Data'.($isActive ? '' : '_Inactive').'_'.preg_replace('/[^a-z0-9]+/i', '_', $partner->partner_name).'_'.date('Y-m-d');

		if ($isXlsx) {
			$rows = $applicationIds->isEmpty()
				? []
				: $this->buildPartnerStudentTabExportRows($applicationIds, $partner, $isActive);

			return Excel::download(
				new PartnerStudentTabExport($rows, $exportHeaders),
				$baseFilename.'.xlsx'
			);
		}

		$filename = $baseFilename.'.csv';

		$headers = [
			'Content-Type'        => 'text/csv; charset=UTF-8',
			'Content-Disposition' => 'attachment; filename="'.$filename.'"',
		];

		$callback = function () use ($applicationIds, $partner, $isActive, $exportHeaders) {
			$handle = fopen('php://output', 'w');
			fputcsv($handle, $exportHeaders);

			$chunkSize = 200;
			$sno = 0;

			foreach ($applicationIds->chunk($chunkSize) as $chunk) {
				$rows = DB::table('applications')
					->join('admins', 'admins.id', '=', 'applications.client_id')
					->leftJoin('products', 'products.id', '=', 'applications.product_id')
					->whereIn('applications.id', $chunk->all())
					->select(
						'applications.*',
						'admins.client_id as client_reference',
						'admins.first_name',
						'admins.last_name',
						'admins.dob',
						'products.name as coursename'
					)
					->orderBy('applications.id')
					->get();

				$latestFees = $this->partnerStudentLatestFeesForIds($chunk);

				foreach ($rows as $row) {
					$fee = $latestFees[$row->id] ?? null;
					if ($fee) {
						foreach ([
							'total_course_fee_amount', 'enrolment_fee_amount', 'material_fees', 'tution_fees',
							'fee_reported_by_college', 'bonus_amount', 'bonus_pending_amount', 'scholarship_fee_amount',
							'commission_as_per_fee_reported', 'commission_payable_as_per_anticipated_fee',
							'commission_paid_as_per_fee_reported', 'commission_pending',
						] as $feeField) {
							$row->{$feeField} = $fee->{$feeField} ?? '0.00';
						}
					}

					$sno++;
					fputcsv($handle, $this->formatPartnerStudentTabExportRow($row, $isActive, $partner->partner_name, $sno));
				}
			}

			fclose($handle);
		};

		return response()->stream($callback, 200, $headers);
	}

	/**
	 * @return array<int, string>
	 */
	private function partnerStudentTabExportHeaders(): array
	{
		return [
			'SNo', 'CRM Ref', 'Student Name', 'Date of Birth', 'Student Id', 'College Name', 'Course Name',
			'Start Date', 'End Date', 'Total Course Fee', 'Enrolment Fee', 'Material Fee', 'Tution Fee',
			'Fee Reported by College', 'Total Bonus', 'Bonus Pending', 'Scholarship Fee',
			'Commission as per Fee reported', 'Commission payable as per anticipated fee',
			'Commission paid as per fee Reported', 'Commission Pending', 'Student Status', 'Enrolment Type',
			'Add Note',
		];
	}

	/**
	 * @param  \Illuminate\Support\Collection<int, int|string>  $applicationIds
	 * @return array<int, array<int, string>>
	 */
	private function buildPartnerStudentTabExportRows($applicationIds, Partner $partner, bool $isActive): array
	{
		$exportRows = [];
		$sno = 0;

		foreach ($applicationIds->chunk(200) as $chunk) {
			$rows = DB::table('applications')
				->join('admins', 'admins.id', '=', 'applications.client_id')
				->leftJoin('products', 'products.id', '=', 'applications.product_id')
				->whereIn('applications.id', $chunk->all())
				->select(
					'applications.*',
					'admins.client_id as client_reference',
					'admins.first_name',
					'admins.last_name',
					'admins.dob',
					'products.name as coursename'
				)
				->orderBy('applications.id')
				->get();

			$latestFees = $this->partnerStudentLatestFeesForIds($chunk);

			foreach ($rows as $row) {
				$fee = $latestFees[$row->id] ?? null;
				if ($fee) {
					foreach ([
						'total_course_fee_amount', 'enrolment_fee_amount', 'material_fees', 'tution_fees',
						'fee_reported_by_college', 'bonus_amount', 'bonus_pending_amount', 'scholarship_fee_amount',
						'commission_as_per_fee_reported', 'commission_payable_as_per_anticipated_fee',
						'commission_paid_as_per_fee_reported', 'commission_pending',
					] as $feeField) {
						$row->{$feeField} = $fee->{$feeField} ?? '0.00';
					}
				}

				$sno++;
				$exportRows[] = $this->formatPartnerStudentTabExportRow($row, $isActive, $partner->partner_name, $sno);
			}
		}

		return $exportRows;
	}

	/**
	 * @return array<int, string|int>
	 */
	private function formatPartnerStudentTabExportRow(object $row, bool $isActive, string $partnerName, int $sno): array
	{
		$formatted = $this->formatPartnerStudentTabRow($row, $isActive, $partnerName);
		$plain = array_map(function ($cell) {
			if (!is_string($cell)) {
				return $cell;
			}
			$text = strip_tags($cell);
			$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

			return trim($text);
		}, array_slice($formatted, 1, 22));

		$plain[] = trim((string) ($row->student_add_notes ?? ''));

		return array_merge([(string) $sno], $plain);
	}

	/**
	 * Bust the student tab cache for a given partner.
	 * Call this from any method that updates a student's stage, status,
	 * overall_status, or application_fee_options for this partner.
	 */
	public function clearStudentTabCache(int $partnerId): void
	{
		self::forgetPartnerStudentTabCache($partnerId);
	}

	/**
	 * Invalidate cached JSON for /partners/getStudentTabData (used by partner Student tab DataTables).
	 * Safe if partner_id is missing; cache driver failures are swallowed so callers are not broken.
	 */
	public static function forgetPartnerStudentTabCache(?int $partnerId): void
	{
		if ($partnerId === null || $partnerId < 1) {
			return;
		}
		try {
			Cache::forget("partner_students_v4_{$partnerId}");
			Cache::forget("partner_students_totals_v5_{$partnerId}_active");
			Cache::forget("partner_students_totals_v5_{$partnerId}_inactive");
			Cache::forget("partner_students_totals_v7_{$partnerId}_active");
			Cache::forget("partner_students_totals_v7_{$partnerId}_inactive");
			Cache::forget(self::partnerStudentTabCountCacheKey($partnerId, 'active'));
			Cache::forget(self::partnerStudentTabCountCacheKey($partnerId, 'inactive'));
		} catch (\Throwable $e) {
			// Same resilience as getStudentTabData when cache is unavailable
		}
	}

	public function getrecipients(Request $request){
		$squery = $request->q;
		if($squery != ''){
			
			 $partners = \App\Models\Partner::query()
      
       ->where(
           function($query) use ($squery) {
             return $query
                    ->where('email', 'ilike', '%'.$squery.'%')
                    ->orwhere('partner_name', 'ilike','%'.$squery.'%');
            })
            ->get();
			
			$items = array();
			foreach($partners as $partner){
				$items[] = array('name' => $partner->partner_name,'email'=>$partner->email,'status'=>'Partner','id'=>$partner->id,'cid'=>base64_encode(convert_uuencode(@$partner->id)));
			}
			
			echo json_encode(array('items'=>$items));
		}
	}
	
	
	public function getallpartners(Request $request){
		$squery = $request->q;
		if($squery != ''){
			
			 $partners = \App\Models\Partner::query()
       
       ->where( 
           function($query) use ($squery) {
             return $query
                    ->where('email', 'ilike', '%'.$squery.'%')
                    ->orwhere('partner_name', 'ilike','%'.$squery.'%');
            })
            ->get();
			
			$items = array();
			foreach($partners as $partner){ 
				$items[] = array('name' => $partner->partner_name,'email'=>$partner->email,'status'=>'Partner','id'=>$partner->id,'cid'=>base64_encode(convert_uuencode(@$partner->id)));
			}
			
			echo json_encode(array('items'=>$items));
		}
	}
	
	public function saveagreement(Request $request){ //dd($request->all());
		$response = [
			'status' => false,
			'message' => 'Partner not found',
		];

		if(Partner::query()->where('id', '=', $request->partner_id)->exists()) 
		{
			$obj = Partner::query()->find($request->partner_id);
            $obj->contract_start = $request->contract_start;
			$obj->contract_expiry = $request->contract_expiry;
			if(isset($request->represent_region) && !empty($request->represent_region)){
				$obj->represent_region = implode(',',$request->represent_region);
			}
			if(isset($request->gst)){
				$obj->gst = 1;
			}else{
				$obj->gst = 0;
			}
			$obj->commission_percentage = $request->commission_percentage;
			$obj->default_super_agent = $request->default_super_agent;
          
            if ($request->hasfile('file_upload')) {
                // Get partner info for S3 path structure
                $partner_info = Partner::query()->select(['id', 'partner_name', 'email'])->where('id', $request->partner_id)->first();
                if(!empty($partner_info) && !empty($partner_info->email)){
                    $partner_unique_id = $partner_info->email;
                } else {
                    $partner_unique_id = "";
                }
                
                // Handle old file deletion (S3 and local for backward compatibility)
                if(!empty($obj->file_upload)){
                    $old_file = $obj->file_upload;
                    
                    // Check if old file is S3 URL
                    if(filter_var($old_file, FILTER_VALIDATE_URL) && strpos($old_file, 'amazonaws.com') !== false){
                        // Extract S3 key from URL
                        $parsedUrl = parse_url($old_file);
                        if(isset($parsedUrl['path'])){
                            $s3Key = ltrim($parsedUrl['path'], '/');
                            // Delete from S3 if exists
                            if(Storage::disk('s3')->exists($s3Key)){
                                Storage::disk('s3')->delete($s3Key);
                            }
                        }
                    } else {
                        // Old local file - delete for backward compatibility
                        $old_file_path = Config::get('constants.documents').'/'.$old_file;
                        if(file_exists($old_file_path) && is_file($old_file_path)){
                            unlink($old_file_path);
                        }
                    }
                }
                
                if(!is_array($request->file('file_upload'))){
                    $files[] = $request->file('file_upload');
                } else {
                    $files = $request->file('file_upload');
                }
                
                foreach ($files as $file) {
                    $size = $file->getSize();
                    $fileName = $file->getClientOriginalName();
                    $explodeFileName = explode('.', $fileName);
                    
                    // Upload to S3
                    $name = time() . '_' . $file->getClientOriginalName();
                    $filePath = $partner_unique_id.'/agreements/'. $name;
                    Storage::disk('s3')->put($filePath, file_get_contents($file));
                    
                    // Get the full URL of the uploaded file
                    $fileUrl = $this->s3Url($filePath);
                    $obj->file_upload = $fileUrl; // Store full S3 URL
                }
            }
          
			$saved = $obj->save();
			if($saved){
				$response['status'] 	= 	true;
				$response['message']	=	'You\'ve successfully saved your partner agreement\'s information.';
			}else{
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
			}
		}
		
		 echo json_encode($response);
	}
	
	/**
	 * Store or update a partner agreement (New multiple agreements system)
	 */
	public function storePartnerAgreement(Request $request)
	{
		try {
			// Validate partner exists
			if (!Partner::query()->where('id', '=', $request->partner_id)->exists()) {
				return response()->json([
					'status' => false,
					'message' => 'Partner not found'
				]);
			}

			// Create or update agreement
			if (isset($request->agreement_id) && $request->agreement_id != '') {
				$agreement = PartnerAgreement::query()->find($request->agreement_id);
				if (!$agreement) {
					return response()->json([
						'status' => false,
						'message' => 'Agreement not found'
					]);
				}
			} else {
				$agreement = new PartnerAgreement();
				$agreement->partner_id = $request->partner_id;
			}

			// Set agreement fields
			$agreement->contract_start = $this->normalizeAgreementDate($request->contract_start);
			$agreement->contract_expiry = $this->normalizeAgreementDate($request->contract_expiry);
			
			if (isset($request->represent_region) && !empty($request->represent_region)) {
				$agreement->represent_region = implode(',', $request->represent_region);
			} else {
				$agreement->represent_region = null;
			}
			
			$agreement->gst = isset($request->gst) ? 1 : 0;
			$agreement->commission_percentage = $request->commission_percentage;
			$agreement->bonus = $request->bonus;
			$agreement->description = $request->description;
			$agreement->default_super_agent = $request->default_super_agent;
			$agreement->status = $request->status ?? 'active';

			// Handle file upload
			if ($request->hasfile('file_upload')) {
				// Get partner info for S3 path structure
				$partner_info = Partner::query()->select(['id', 'partner_name', 'email'])->where('id', $request->partner_id)->first();
				$partner_unique_id = !empty($partner_info->email) ? $partner_info->email : "";

				// Handle old file deletion (S3)
				if (!empty($agreement->file_upload)) {
					$old_file = $agreement->file_upload;
					
					// Check if old file is S3 URL
					if (filter_var($old_file, FILTER_VALIDATE_URL) && strpos($old_file, 'amazonaws.com') !== false) {
						$parsedUrl = parse_url($old_file);
						if (isset($parsedUrl['path'])) {
							$s3Key = ltrim($parsedUrl['path'], '/');
							if (Storage::disk('s3')->exists($s3Key)) {
								Storage::disk('s3')->delete($s3Key);
							}
						}
					}
				}

				$file = $request->file('file_upload');
				$name = time() . '_' . $file->getClientOriginalName();
				$filePath = $partner_unique_id . '/agreements/' . $name;
				Storage::disk('s3')->put($filePath, file_get_contents($file));
				
				$fileUrl = $this->s3Url($filePath);
				$agreement->file_upload = $fileUrl;
			}

			$saved = $agreement->save();

			if ($saved) {
				// Also update partners table for backward compatibility (keep latest agreement data)
				$this->syncPartnerTableWithLatestAgreement($request->partner_id);
				
				return response()->json([
					'status' => true,
					'message' => 'Agreement saved successfully',
					'agreement_id' => $agreement->id
				]);
			} else {
				return response()->json([
					'status' => false,
					'message' => 'Failed to save agreement'
				]);
			}
		} catch (\Exception $e) {
			return response()->json([
				'status' => false,
				'message' => 'Error: ' . $e->getMessage()
			]);
		}
	}

	/**
	 * Get all agreements for a partner
	 */
	public function getPartnerAgreements(Request $request)
	{
		$partner_id = $request->partner_id;
		
		$agreements = PartnerAgreement::query()->where('partner_id', $partner_id)
			->orderBy('created_at', 'DESC')
			->get();

		return response()->json([
			'status' => true,
			'agreements' => $agreements
		]);
	}

	/**
	 * Get a single agreement
	 */
	public function getPartnerAgreement(Request $request)
	{
		$agreement_id = $request->agreement_id;
		
		$agreement = PartnerAgreement::query()->find($agreement_id);

		if ($agreement) {
			return response()->json([
				'status' => true,
				'agreement' => $agreement
			]);
		} else {
			return response()->json([
				'status' => false,
				'message' => 'Agreement not found'
			]);
		}
	}

	/**
	 * Delete a partner agreement
	 */
	public function deletePartnerAgreement(Request $request)
	{
		$agreement_id = $request->agreement_id;
		
		$agreement = PartnerAgreement::query()->find($agreement_id);

		if (!$agreement) {
			return response()->json([
				'status' => false,
				'message' => 'Agreement not found'
			]);
		}

		// Delete file from S3 if exists
		if (!empty($agreement->file_upload)) {
			if (filter_var($agreement->file_upload, FILTER_VALIDATE_URL) && strpos($agreement->file_upload, 'amazonaws.com') !== false) {
				$parsedUrl = parse_url($agreement->file_upload);
				if (isset($parsedUrl['path'])) {
					$s3Key = ltrim($parsedUrl['path'], '/');
					if (Storage::disk('s3')->exists($s3Key)) {
						Storage::disk('s3')->delete($s3Key);
					}
				}
			}
		}

		$deleted = DB::table('partner_agreements')->where('id', $agreement->id)->delete() > 0;

		if ($deleted) {
			// Update partners table for backward compatibility
			$this->syncPartnerTableWithLatestAgreement($agreement->partner_id);
			
			return response()->json([
				'status' => true,
				'message' => 'Agreement deleted successfully'
			]);
		} else {
			return response()->json([
				'status' => false,
				'message' => 'Failed to delete agreement'
			]);
		}
	}

	/**
	 * Set an agreement as active (and deactivate others)
	 */
	public function setActiveAgreement(Request $request)
	{
		$agreement_id = $request->agreement_id;
		
		$agreement = PartnerAgreement::query()->find($agreement_id);

		if (!$agreement) {
			return response()->json([
				'status' => false,
				'message' => 'Agreement not found'
			]);
		}

		// Deactivate all agreements for this partner
		PartnerAgreement::query()->where('partner_id', $agreement->partner_id)
			->update(['status' => 'inactive']);

		// Activate the selected agreement
		$agreement->status = 'active';
		$saved = $agreement->save();

		if ($saved) {
			// Update partners table for backward compatibility
			$this->syncPartnerTableWithLatestAgreement($agreement->partner_id);
			
			return response()->json([
				'status' => true,
				'message' => 'Agreement set as active'
			]);
		} else {
			return response()->json([
				'status' => false,
				'message' => 'Failed to update agreement status'
			]);
		}
	}

	/**
	 * Normalize agreement date input to Y-m-d in the application timezone.
	 */
	private function normalizeAgreementDate(?string $date): ?string
	{
		if ($date === null || trim($date) === '') {
			return null;
		}

		$date = trim($date);

		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
			return $date;
		}

		if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
			return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
		}

		return Carbon::parse($date)
			->timezone(config('app.timezone'))
			->format('Y-m-d');
	}

	/**
	 * Sync partners table with latest active agreement for backward compatibility
	 */
	private function syncPartnerTableWithLatestAgreement(int|string $partner_id): void
	{
		$latestAgreement = PartnerAgreement::query()->where('partner_id', $partner_id)
			->where('status', 'active')
			->orderBy('created_at', 'DESC')
			->first();

		if ($latestAgreement) {
			$partner = Partner::query()->find($partner_id);
			if ($partner) {
				$partner->contract_start = $latestAgreement->contract_start;
				$partner->contract_expiry = $latestAgreement->contract_expiry;
				$partner->represent_region = $latestAgreement->represent_region;
				$partner->commission_percentage = $latestAgreement->commission_percentage;
				$partner->default_super_agent = $latestAgreement->default_super_agent;
				$partner->gst = $latestAgreement->gst;
				$partner->file_upload = $latestAgreement->file_upload;
				$partner->save();
			}
		}
	}
	
	public function createbranch(Request $request){
		if(isset($request->branch_id) && $request->branch_id != ''){
			$obj = PartnerBranch::query()->find($request->branch_id);
		}else{
			$obj = new PartnerBranch;
		}
		$obj->user_id 			= Auth::user()->id;
		$obj->partner_id 				= $request->client_id;
		$obj->name 	= $request->name;
		$obj->email 	= $request->email;
		$obj->country 		= $request->country;
		$obj->city 			= $request->city;
		$obj->state 				= $request->state;
		$obj->street 			= $request->street;
		$obj->country_code 	= PhoneHelper::normalizeCountryCode($request->country_code);
		$obj->phone 			= $request->phone;
		$obj->is_headoffice 		= isset($request->head_office) ? $request->head_office : 0; // Default to 0 if not set
		$obj->is_regional 		= isset($request->is_regional) ? $request->is_regional : 1; // Default to 1 (Regional) if not set
		$obj->zip 		= $request->zip_code;
		$saved = $obj->save();
		
		if($saved){
				$response['status'] 	= 	true;
				$response['message']	=	'You’ve successfully saved your branch.';
			}else{
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
			}
			
			echo json_encode($response);
	}
	
	public function getbranch(Request $request){
		$branchesquery = PartnerBranch::query()->where('partner_id', $request->clientid)->orderby('created_at', 'DESC');
		$branchescount = $branchesquery->count();
		$branches = $branchesquery->get();
		if($branchescount !== 0){
		
			foreach($branches as $branch){
		
				?>
				<div class="branch_col" id="branch_<?php echo $branch->id; ?>"> 
					<div class="branch_content">
						<h4><?php echo $branch->name; ?></h4>
						<div class="" style="margin-top: 15px!important;">
							<p><?php echo \App\Helpers\IconHelper::render('map-marker-alt', 'solid', ['attrs' => ['style' => 'margin-right: 10px!important;']]); ?> <?php echo $branch->city; ?>, <?php echo $branch->country ?? '-'; ?></p>
						</div>
					</div>
					<div class="extra_content">
						<div class="left">
							<p><?php echo \App\Helpers\IconHelper::render('phone', 'solid', ['attrs' => ['style' => 'margin-right: 20px!important;']]); ?> <?php if($branch->phone != ''){ echo $branch->phone; }else{ echo '-'; } ?></p>
							<p><?php echo \App\Helpers\IconHelper::render('envelope', 'regular', ['attrs' => ['style' => 'margin-right: 20px!important;']]); ?> <?php if($branch->email != ''){ echo $branch->email; }else{ echo '-'; } ?></p>
						</div>  
						<div class="right">
							<div class="dropdown d-inline dropdown_ellipsis_icon">
								<a class="dropdown-toggle" type="button" id="" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo \App\Helpers\IconHelper::render('ellipsis-v'); ?></a>
								<div class="dropdown-menu">
									<a class="dropdown-item openbranchform" data-id="<?php echo $branch->id; ?>" href="javascript:;">Edit</a>
									<a data-id="<?php echo $branch->id; ?>" data-href="deletebranch" class="dropdown-item deletenote" href="javascript:;" >Delete</a>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
			}
		}else{
			
		}
	}
	
	public function getbranchdetail(Request $request){
		$note_id = $request->note_id;
		if(\App\Models\PartnerBranch::query()->where('id',$note_id)->exists()){
			$data = \App\Models\PartnerBranch::query()->where('id',$note_id)->first();
			$response['status'] 	= 	true;
			$response['data']	=	$data;
		}else{
			$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
		}
		echo json_encode($response);
	}
	
	public function deletebranch(Request $request){
		$note_id = $request->note_id;
		if(\App\Models\PartnerBranch::query()->where('id',$note_id)->exists()){
			$res = DB::table('partner_branches')->where('id', @$note_id)->delete();
			if($res){
				
			$response['status'] 	= 	true;
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
	
     //Save Partner Student Invoice
    public function savepartnerstudentinvoice(Request $request, $id = NULL)
	{
		$requestData = $request->all(); //echo '<pre>'; print_r($requestData); die;
		$response = [];

        if( $requestData['function_type'] == 'add')
        {
			$saved = false;
			$finalArr = [];
			$insertedDocId = null;
			$doc_saved = false;
			$name = '';
			$client_unique_id = '';
			$doctype = '';
			$invoice_id = null;

            if ($request->hasfile('document_upload'))
            {
                if(!is_array($request->file('document_upload'))){
                    $files[] = $request->file('document_upload');
                } else {
                    $files = $request->file('document_upload');
                }

                $partner_info = \App\Models\Partner::query()->select(['id', 'partner_name', 'email'])->where('id', $requestData['partner_id'])->first(); //dd($admin);
                if(!empty($partner_info)){
                    $client_unique_id = $partner_info->email;
                } else {
                    $client_unique_id = "";
                }
                $doctype = isset($request->doctype)? $request->doctype : '';
                foreach ($files as $file) {
                    $size = $file->getSize();
                    $fileName = $file->getClientOriginalName();
                    $explodeFileName = explode('.', $fileName);
                    $name = time() . $file->getClientOriginalName();
                    $filePath = $client_unique_id.'/'.$doctype.'/'. $name;
                    Storage::disk('s3')->put($filePath, file_get_contents($file));
                    $exploadename = explode('.', $name);
                    $obj = new \App\Models\Document;
                    $obj->file_name = $explodeFileName[0];
                    $obj->filetype = $exploadename[1];
                    $obj->user_id = Auth::user()->id;
                    //Get the full URL of the uploaded file
                    $fileUrl = $this->s3Url($filePath);
                    $obj->myfile = $fileUrl;
                    $obj->myfile_key = $name;

                    $obj->client_id = $requestData['partner_id'];
                    $obj->type = $request->type;
                    $obj->file_size = $size;
                    $obj->doc_type = $doctype;
                    $doc_saved = $obj->save();
                    $insertedDocId = $obj->id;
                }  //end foreach
            } else {
                $insertedDocId = null;
                $doc_saved = "";
            }

            if(isset($requestData['invoice_date'])){
                //Generate unique invoice_id
                $is_record_exist = DB::table('partner_student_invoices')->select('invoice_id')->where('invoice_type',1)->orderBy('invoice_id', 'desc')->first();
                //dd($is_record_exist);
                if(!$is_record_exist){
                    $invoice_id = 1;
                } else {
                    $invoice_id = $is_record_exist->invoice_id;
                    $invoice_id = $invoice_id +1;
                }  //dd($invoice_id);
                $finalArr = array();
                $finalArr['invoice_date'] = $requestData['invoice_date'];
                $finalArr['invoice_no'] = $requestData['invoice_no'];
                $finalArr['save_type'] = $requestData['save_type'];
                for($i=0; $i<count($requestData['description']); $i++){
                    $saved	= DB::table('partner_student_invoices')->insertGetId([
                        'user_id' => $requestData['loggedin_userid'],
                        'partner_id' =>  $requestData['partner_id'],
                        'invoice_id'=>  $invoice_id,
                        'invoice_type' => $requestData['invoice_type'],
                        'invoice_date' => $requestData['invoice_date'],
                        'invoice_no' => $requestData['invoice_no'],
                        'student_id' => $requestData['student_id'][$i],
                        'student_dob' => $requestData['student_dob'][$i],
                        'student_name' => $requestData['student_name'][$i],
                        'student_ref_no' => $requestData['student_ref_no'][$i],
                        'course_name' => $requestData['course_name'][$i],
                        'student_info_id' => $requestData['student_info_id'][$i],
                        'description' => $requestData['description'][$i],
                        'amount_aud' => $requestData['amount_aud'][$i],
                        'uploaded_doc_id'=> $insertedDocId,
                        'save_type'=> $requestData['save_type'],
                        'created_at'=> date('Y-m-d H:i:s'),
                        'updated_at'=> date('Y-m-d H:i:s')
                    ]);
                }
            }
            //echo '<pre>'; print_r($finalArr); die;
            if($saved) {
                $response['status'] = true;
                $response['requestData'] = $finalArr;
                $response['lastInsertedId'] = $saved;
                $response['function_type'] = $requestData['function_type'];
                $response['last_invoice_id'] = $invoice_id;
                $response['partnerid'] = $requestData['partner_id'];


                //Get Total Enrolled Student for any specific invoice no
                $db_total_enrolled_student = DB::table('partner_student_invoices')->where('partner_id',$requestData['partner_id'])->where('invoice_type',1)->where('invoice_id',$invoice_id)->count('invoice_id');
                $response['db_total_enrolled_student'] = $db_total_enrolled_student; //dd($db_total_enrolled_student );

                //Get Total amount for any specific invoice no
                $db_total_amount = DB::table('partner_student_invoices')->where('partner_id',$requestData['partner_id'])->where('invoice_type',1)->where('invoice_id',$invoice_id)->sum('amount_aud');
                $response['db_total_amount'] = $db_total_amount; //dd($db_total_amount);

                //Get total amount for all invoices
                $db_total_deposit_amount = DB::table('partner_student_invoices')->where('partner_id',$requestData['partner_id'])->where('invoice_type',1)->sum('amount_aud');
                $response['db_total_deposit_amount'] = $db_total_deposit_amount; //dd($db_total_deposit_amount);

                if($doc_saved){
                    //Get AWS Url link
                    $url = 'https://'.env('AWS_BUCKET').'.s3.'. env('AWS_DEFAULT_REGION') . '.amazonaws.com/';
                    $awsUrl = $url.$client_unique_id.'/'.$doctype.'/'.$name; //dd($awsUrl);
                    $response['awsUrl'] = $awsUrl;
                    $response['message'] = 'Student Invoice with document added successfully';
                    $subject = 'added student invoice with invoice No-'.$requestData['invoice_no'].' and document' ;
                } else {
                    $response['message'] = 'Student Invoice added successfully';
                    $response['awsUrl'] =  "";
                    $subject = 'added student invoice with invoice No-'.$requestData['invoice_no'];
                }
                $printUrl = URL::to('/partners/printpreviewcreateinvoice').'/'.$invoice_id;
                $response['printUrl'] = $printUrl;
            } else {
                $response['lastInsertedId'] = "";
                $response['awsUrl'] =  "";
                $response['requestData'] = "";
                $response['status'] 	= 	false;
                $response['message']	=	'Please try again';
                $response['function_type'] = $requestData['function_type'];
                $response['last_invoice_id'] = "";
                $response['partnerid'] = "";
            }
        }
        else if( $requestData['function_type'] == 'edit')
        {
			$saved = false;
			$finalArr = [];
			$insertedDocId2 = null;
			$doc_saved2 = false;
			$name = '';
			$client_unique_id = '';
			$doctype = '';

            if ($request->hasfile('document_upload'))
            {
                if(!is_array($request->file('document_upload'))){
                    $files[] = $request->file('document_upload');
                } else {
                    $files = $request->file('document_upload');
                }

                $partner_info = \App\Models\Partner::query()->select(['id', 'partner_name', 'email'])->where('id', $requestData['partner_id'])->first(); //dd($admin);
                if(!empty($partner_info)){
                    $client_unique_id = $partner_info->email;
                } else {
                    $client_unique_id = "";
                }
                $doctype = isset($request->doctype)? $request->doctype : '';
                foreach ($files as $file) {
                    $size = $file->getSize();
                    $fileName = $file->getClientOriginalName();
                    $explodeFileName = explode('.', $fileName);
                    $name = time() . $file->getClientOriginalName();
                    $filePath = $client_unique_id.'/'.$doctype.'/'. $name;
                    Storage::disk('s3')->put($filePath, file_get_contents($file));
                    $exploadename = explode('.', $name);
                    $obj2 = new \App\Models\Document;
                    $obj2->file_name = $explodeFileName[0];
                    $obj2->filetype = $exploadename[1];
                    $obj2->user_id = Auth::user()->id;
                    //Get the full URL of the uploaded file
                    $fileUrl = $this->s3Url($filePath);
                    $obj2->myfile = $fileUrl;
                    $obj2->myfile_key = $name;

                    $obj2->client_id = $requestData['partner_id'];
                    $obj2->type = $request->type;
                    $obj2->file_size = $size;
                    $obj2->doc_type = $doctype;
                    $doc_saved2 = $obj2->save();
                    $insertedDocId2 = $obj2->id;
                }  //end foreach
            } else {
                $insertedDocId2 = null;
                $doc_saved2 = "";
            }

            //Check any entry is deleted
            $all_record_get = DB::table('partner_student_invoices')->select('id')->where('invoice_type',1)->where('invoice_id',$requestData['invoice_id'])->get();
            //dd($all_record_get);
            if(!empty($all_record_get) ){
                $db_arr = array();
                foreach($all_record_get as $dbkey=>$dbval){
                    $db_arr[] = $dbval->id;
                }
                //dd($db_arr);
                $req_id_arr = array();
                foreach($requestData['id'] as $reqkey=>$reqval){
                    $req_id_arr[] = $reqval;
                }

                $result_final = array_diff($db_arr, $req_id_arr);
                $result_final = array_values($result_final); //dd($result_final);
                if(!empty($result_final)){
                    $response['requestDeleteData'] 	= $result_final;
                    $response['requestDeleteDataType'] 	= "delete";
                    foreach($result_final as $final_key=>$final_val){
                        DB::table('partner_student_invoices')->where('id', $final_val)->delete();
                    }
                }
            }

            if(isset($requestData['description'])){
                $finalArr = array();
                $finalAddArr = array();

                $finalAddArr['invoice_date'] = $requestData['invoice_date'];
                $finalAddArr['invoice_id'] = $requestData['invoice_id'];

                $finalArr['invoice_date'] = $requestData['invoice_date'];
                $finalArr['invoice_id'] = $requestData['invoice_id'];

                for($j=0; $j<count($requestData['description']); $j++){
                    if( empty($requestData['id'][$j] ) ){ //add new entry
                        $lastInsertId11	= DB::table('partner_student_invoices')->insertGetId([
                            'user_id' => $requestData['loggedin_userid'],
                            'partner_id' =>  $requestData['partner_id'],
                            'invoice_id'=>  $requestData['invoice_id'],
                            'invoice_type' => $requestData['invoice_type'],
                            'invoice_date' => $requestData['invoice_date'],
                            'invoice_no'=>  $requestData['invoice_no'],
                            'student_id' => $requestData['student_id'][$j],
                            'student_dob' => $requestData['student_dob'][$j],
                            'student_name' => $requestData['student_name'][$j],
                            'student_ref_no' => $requestData['student_ref_no'][$j],
                            'course_name' => $requestData['course_name'][$j],
                            'student_info_id' => $requestData['student_info_id'][$j],
                            'description' => $requestData['description'][$j],
                            'amount_aud' => $requestData['amount_aud'][$j],
                            'save_type' => $requestData['save_type']
                        ]);
                        $finalAddArr[$j]['id'] = $lastInsertId11;
                        //$finalAddArr['id'] = $lastInsertId11;
                        $response['requestAddData'] 	= $finalAddArr;
                        $response['requestAddDataType'] = "add";
                    }
                    else { //edit case
                        $saved	= DB::table('partner_student_invoices')
                        ->where('id',$requestData['id'][$j])
                        ->update([
                            'user_id' => $requestData['loggedin_userid'],
                            'partner_id' =>  $requestData['partner_id'],
                            'invoice_id' =>  $requestData['invoice_id'],
                            'invoice_type' => $requestData['invoice_type'],
                            'invoice_date' => $requestData['invoice_date'],
                            'invoice_no' => $requestData['invoice_no'],
                            'student_id' => $requestData['student_id'][$j],
                            'student_dob' => $requestData['student_dob'][$j],
                            'student_name' => $requestData['student_name'][$j],
                            'student_ref_no' => $requestData['student_ref_no'][$j],
                            'course_name' => $requestData['course_name'][$j],
                            'student_info_id' => $requestData['student_info_id'][$j],
                            'description' => $requestData['description'][$j],
                            'amount_aud' => $requestData['amount_aud'][$j],
                            'save_type' => $requestData['save_type']
                        ]);
                    }
                }
            }
            //echo '<pre>'; print_r($saved); die;
            if($saved>=0) {
                $response['requestData'] = $finalArr;
                $response['partnerid'] = $requestData['partner_id'];

                //Get Total Enrolled Student for any specific invoice no
                $db_total_enrolled_student2 = DB::table('partner_student_invoices')->where('partner_id',$requestData['partner_id'])->where('invoice_type',1)->where('invoice_id',$requestData['invoice_id'])->count('invoice_id');
                $response['db_total_enrolled_student2'] = $db_total_enrolled_student2; //dd($db_total_enrolled_student2 );

                //Get Total amount for any specific invoice no
                $db_total_amount2 = DB::table('partner_student_invoices')->where('partner_id',$requestData['partner_id'])->where('invoice_type',1)->where('invoice_id',$requestData['invoice_id'])->sum('amount_aud');
                $response['db_total_amount2'] = $db_total_amount2; //dd($db_total_amount2 );

                //Get total amount for all invoices
                $db_total_deposit_amount2 = DB::table('partner_student_invoices')->where('partner_id',$requestData['partner_id'])->where('invoice_type',1)->sum('amount_aud');
                $response['db_total_deposit_amount2'] = $db_total_deposit_amount2;

                if($doc_saved2){
                    //Update document id
                    DB::table('partner_student_invoices')
                    ->where('partner_id',$requestData['partner_id'])
                    ->where('invoice_id',$requestData['invoice_id'])
                    ->where('invoice_type',1)
                    ->update(['uploaded_doc_id'=> $insertedDocId2]);

                    //Get AWS Url link
                    $url2 = 'https://'.env('AWS_BUCKET').'.s3.'. env('AWS_DEFAULT_REGION') . '.amazonaws.com/';
                    $awsUrl2 = $url2.$client_unique_id.'/'.$doctype.'/'.$name; //dd($awsUrl);
                    $response['awsUrl2'] = $awsUrl2;
                    $response['message'] = 'Student Invoice with document added successfully';
                    $subject = 'added student invoice with invoice No-'.$requestData['invoice_no'].' and document' ;
                } else {
                    $response['message'] = 'Student Invoice added successfully';
                    $response['awsUrl2'] =  "";
                    $subject = 'added student invoice with invoice No-'.$requestData['invoice_no'];
                }
                $printUrl2 = URL::to('/partners/printpreviewcreateinvoice').'/'.$requestData['invoice_id'];
                $response['printUrl2'] = $printUrl2;

                $response['status'] 	= 	true;
                $response['message']	=	'Invoice updated successfully';
                $response['function_type'] = $requestData['function_type'];
            } else {
                $response['requestData'] = "";
                $response['db_total_deposit_amount2'] = "";
                $response['db_total_amount2'] = "";
                $response['db_total_enrolled_student2']= "";
                $response['awsUrl2'] = "";
                $response['printUrl2'] = "";
                $response['status'] 	= 	false;
                $response['message']	=	'Please try again';
                $response['function_type'] = $requestData['function_type'];
                $response['partnerid'] = "";
            }
        }
       echo json_encode($response);
    }
  
    //Get Top Invoice Value From Db
    public function getTopReceiptValInDB(Request $request)
	{
        $requestData = 	$request->all();
        $invoice_type = $requestData['type'];
        $record_count = DB::table('partner_student_invoices')->where('invoice_type',$invoice_type)->max('id');
        //dd($record_count);
        if($record_count) {
            /*if($invoice_type == 3){ //type = record payment
                $max_invoice_id = DB::table('partner_student_invoices')->where('invoice_type',$invoice_type)->max('invoice_id');
                $response['max_invoice_id'] 	= $max_invoice_id;
            } else {
                $response['max_invoice_id'] 	= "";
            }*/
            $response['invoice_type'] 	= $invoice_type;
            $response['record_count'] 	= $record_count;
            $response['status'] 	= 	true;
            $response['message']	=	'Record is exist';
        }else{
            $response['invoice_type'] 	= $invoice_type;
            $response['record_count'] 	= $record_count;
            //$response['max_invoice_id'] = "";
            $response['status'] 	= 	false;
            $response['message']	=	'Record is not exist.Please try again';
        }
        echo json_encode($response);
    }

    //Get Enrolled Student List
    public function getEnrolledStudentList(Request $request)
	{
        //dd($request->partnerid);
        $record_get = \App\Models\Application::join('admins', 'applications.client_id', '=', 'admins.id')
        ->leftJoin('partners', 'applications.partner_id', '=', 'partners.id')
        ->select('admins.client_id as client_reference','admins.first_name','admins.last_name','admins.id')
        ->where('applications.partner_id', $request->partnerid)
        ->where(function ($query) {
            $query->where('applications.stage', 'Coe issued')
            ->orWhere('applications.stage', 'Enrolled')
            ->orWhere('applications.stage', 'Coe Cancelled');
        })
        ->groupBy(['admins.client_id', 'admins.first_name', 'admins.last_name', 'admins.id']) // PostgreSQL requires all selected columns in GROUP BY
        ->orderBy('admins.first_name', 'ASC')
        ->get();
        //dd($record_get);
        if(!empty($record_get)) {
            $str = "<option value=''>Select</option>";
            foreach($record_get as $key=>$val) {
                $student_name = $val->first_name.' '.$val->last_name.'('.$val->client_reference.')';
                $str .=  '<option value="'.$val->id.'">'.$student_name.'</option>';
            }
            $response['record_get'] = $str;
            $response['status'] 	= 	true;
            $response['message']	=	'Record is exist';
        }else{
            $response['record_get'] 	= array();
            $response['status'] 	= 	false;
            $response['message']	=	'Record is not exist.Please try again';
        }
        echo json_encode($response);
    }

    //Save Partner Record Invoice
    public function savepartnerrecordinvoice(Request $request, $id = NULL)
	{
		$requestData = $request->all(); //echo '<pre>'; print_r($requestData); die;
		$response = [];

        if( $requestData['function_type'] == 'add')
        {
			$saved = false;
			$finalArr = [];
			$insertedDocId = null;
			$doc_saved = false;
			$name = '';
			$client_unique_id = '';
			$doctype = '';
			$invoice_id = null;

            if ($request->hasfile('document_upload'))
            {
                if(!is_array($request->file('document_upload'))){
                    $files[] = $request->file('document_upload');
                }else{
                    $files = $request->file('document_upload');
                }

                $partner_info = \App\Models\Partner::query()->select(['id', 'partner_name', 'email'])->where('id', $requestData['partner_id'])->first(); //dd($admin);
                if(!empty($partner_info)){
                    $client_unique_id = $partner_info->email;
                } else {
                    $client_unique_id = "";
                }

                $doctype = isset($request->doctype)? $request->doctype : '';

                foreach ($files as $file) {
                    $size = $file->getSize();
                    $fileName = $file->getClientOriginalName();
                    $explodeFileName = explode('.', $fileName);
                    $name = time() . $file->getClientOriginalName();
                    $filePath = $client_unique_id.'/'.$doctype.'/'. $name;
                    Storage::disk('s3')->put($filePath, file_get_contents($file));
                    $exploadename = explode('.', $name);

                    $obj = new \App\Models\Document;
                    $obj->file_name = $explodeFileName[0];
                    $obj->filetype = $exploadename[1];
                    $obj->user_id = Auth::user()->id;
                    //$obj->myfile = $name;

                    // Get the full URL of the uploaded file
                    $fileUrl = $this->s3Url($filePath);
                    $obj->myfile = $fileUrl;
                    $obj->myfile_key = $name;

                    $obj->client_id = $requestData['partner_id'];
                    $obj->type = $request->type;
                    $obj->file_size = $size;
                    $obj->doc_type = $doctype;
                    $doc_saved = $obj->save();

                    $insertedDocId = $obj->id;
                }  //end foreach
            } else {
                $insertedDocId = null;
                $doc_saved = "";
            }

            if(isset($requestData['invoice_date'])){
                //Generate unique invoice_id
                $is_record_exist = DB::table('partner_student_invoices')->select('invoice_id')->where('invoice_type',2)->orderBy('invoice_id', 'desc')->first();
                //dd($is_record_exist);
                if(!$is_record_exist){
                    $invoice_id = 1;
                } else {
                    $invoice_id = $is_record_exist->invoice_id;
                    $invoice_id = $invoice_id +1;
                }  //dd($invoice_id);
                $finalArr = array();
                for($i=0; $i<count($requestData['invoice_date']); $i++){
                    $finalArr[$i]['invoice_date'] = $requestData['invoice_date'][$i];
                    $finalArr[$i]['sent_date'] = $requestData['sent_date'][$i];
                    $finalArr[$i]['invoice_no'] = $requestData['invoice_no'][$i];
                    $finalArr[$i]['amount_aud'] = $requestData['amount_aud'][$i];
                    $finalArr[$i]['partnerid'] = $requestData['partner_id'];

                    $saved	= DB::table('partner_student_invoices')->insertGetId([
                        'user_id' => $requestData['loggedin_userid'],
                        'partner_id' =>  $requestData['partner_id'],
                        'invoice_id'=>  $invoice_id,
                        'invoice_type' => $requestData['invoice_type'],
                        'invoice_date' => $requestData['invoice_date'][$i],
                        'invoice_no' => $requestData['invoice_no'][$i],
                        'sent_date' => $requestData['sent_date'][$i],
                        'amount_aud' => $requestData['amount_aud'][$i],
                        'uploaded_doc_id'=> $insertedDocId
                    ]);
                    $finalArr[$i]['id'] = $saved;
                }
            }
            //echo '<pre>'; print_r($finalArr); die;
            if($saved) {
                $response['status'] = true;
                $response['requestData'] = $finalArr;
                $response['lastInsertedId'] = $saved;
                $response['function_type'] = $requestData['function_type'];

                //Get total amount
                $db_total_deposit_amount = DB::table('partner_student_invoices')->where('partner_id',$requestData['partner_id'])->where('invoice_type',2)->sum('amount_aud');
                $response['db_total_deposit_amount'] = $db_total_deposit_amount; //dd($db_total_deposit_amount );

                if($doc_saved){
                    //Get AWS Url link
                    $url = 'https://'.env('AWS_BUCKET').'.s3.'. env('AWS_DEFAULT_REGION') . '.amazonaws.com/';
                    $awsUrl = $url.$client_unique_id.'/'.$doctype.'/'.$name; //dd($awsUrl);
                    $response['awsUrl'] = $awsUrl;
                    $response['message'] = 'Record Invoice with document added successfully';

                    $subject = 'added record invoice with Receipt No-'.$invoice_id.' and document' ;
                } else {
                    $response['message'] = 'Record Invoice added successfully';
                    $response['awsUrl'] =  "";
                    $subject = 'added record invoice with Receipt No-'.$invoice_id;
                }

                //$printUrl = URL::to('/partners/printpreviewrecordinvoice').'/'.$invoice_id;
                //$response['printUrl'] = $printUrl;
            } else {
                $response['lastInsertedId'] = "";
                $response['awsUrl'] =  "";
                $response['requestData'] = "";
                $response['status'] 	= 	false;
                $response['message']	=	'Please try again';
                $response['function_type'] = $requestData['function_type'];
            }
        }
        echo json_encode($response);
    }


    //Get Recorded Invoice List
    public function getRecordedInvoiceList(Request $request)
	{
        //dd($request->partnerid);
        $record_get = DB::table('partner_student_invoices')->select('invoice_no')->where('partner_id',$request->partnerid)->whereIn('invoice_type', [1, 2])->groupBy('invoice_no')->get();
        //dd($record_get);
        if(!empty($record_get)) {
            $str = "<option value=''>Select</option>";
            foreach($record_get as $key=>$val) {
                $str .=  '<option value="'.$val->invoice_no.'">'.$val->invoice_no.'</option>';
            }
            $response['record_get'] = $str;
            $response['status'] 	= 	true;
            $response['message']	=	'Record is exist';
        }else{
            $response['record_get'] 	= array();
            $response['status'] 	= 	false;
            $response['message']	=	'Record is not exist.Please try again';
        }
        echo json_encode($response);
    }


    //Save Partner Record Payment
    public function savepartnerrecordpayment(Request $request, $id = NULL)
	{
		$requestData = $request->all(); //echo '<pre>'; print_r($requestData); die;
		$response = [];

        if( $requestData['function_type'] == 'add')
        {
			$saved = false;
			$finalArr = [];
			$insertedDocId = null;
			$doc_saved = false;
			$name = '';
			$client_unique_id = '';
			$doctype = '';
			$invoice_id = null;

            if ($request->hasfile('document_upload'))
            {
                if(!is_array($request->file('document_upload'))){
                    $files[] = $request->file('document_upload');
                }else{
                    $files = $request->file('document_upload');
                }

                $partner_info = \App\Models\Partner::query()->select(['id', 'partner_name', 'email'])->where('id', $requestData['partner_id'])->first(); //dd($admin);
                if(!empty($partner_info)){
                    $client_unique_id = $partner_info->email;
                } else {
                    $client_unique_id = "";
                }

                $doctype = isset($request->doctype)? $request->doctype : '';

                foreach ($files as $file) {
                    $size = $file->getSize();
                    $fileName = $file->getClientOriginalName();
                    $explodeFileName = explode('.', $fileName);
                    $name = time() . $file->getClientOriginalName();
                    $filePath = $client_unique_id.'/'.$doctype.'/'. $name;
                    Storage::disk('s3')->put($filePath, file_get_contents($file));
                    $exploadename = explode('.', $name);

                    $obj = new \App\Models\Document;
                    $obj->file_name = $explodeFileName[0];
                    $obj->filetype = $exploadename[1];
                    $obj->user_id = Auth::user()->id;
                    //$obj->myfile = $name;

                    // Get the full URL of the uploaded file
                    $fileUrl = $this->s3Url($filePath);
                    $obj->myfile = $fileUrl;
                    $obj->myfile_key = $name;

                    $obj->client_id = $requestData['partner_id'];
                    $obj->type = $request->type;
                    $obj->file_size = $size;
                    $obj->doc_type = $doctype;
                    $doc_saved = $obj->save();

                    $insertedDocId = $obj->id;
                }  //end foreach
            } else {
                $insertedDocId = null;
                $doc_saved = "";
            }

            if(isset($requestData['verified_date'])){
                //Generate unique invoice_id
                $is_record_exist = DB::table('partner_student_invoices')->select('invoice_id')->where('invoice_type',3)->orderBy('invoice_id', 'desc')->first();
                //dd($is_record_exist);
                if(!$is_record_exist){
                    $invoice_id = 1;
                } else {
                    $invoice_id = $is_record_exist->invoice_id;
                    $invoice_id = $invoice_id +1;
                }  //dd($invoice_id);
                $finalArr = array();
                for($i=0; $i<count($requestData['verified_date']); $i++){
                    $finalArr[$i]['invoice_no'] = $requestData['invoice_no'][$i];
                    $finalArr[$i]['method_received'] = $requestData['method_received'][$i];
                    $finalArr[$i]['verified_by'] = $requestData['verified_by'][$i];
                    $finalArr[$i]['verified_date'] = $requestData['verified_date'][$i];
                    $finalArr[$i]['amount_aud'] = $requestData['amount_aud'][$i];
                    $finalArr[$i]['partnerid'] = $requestData['partner_id'];

                    $saved	= DB::table('partner_student_invoices')->insertGetId([
                        'user_id' => $requestData['loggedin_userid'],
                        'partner_id' =>  $requestData['partner_id'],
                        'invoice_id'=>  $invoice_id,
                        'invoice_type' => $requestData['invoice_type'],
                        'invoice_no' => $requestData['invoice_no'][$i],
                        'method_received' => $requestData['method_received'][$i],
                        'verified_by' => $requestData['verified_by'][$i],
                        'verified_date' => $requestData['verified_date'][$i],
                        'amount_aud' => $requestData['amount_aud'][$i],
                        'uploaded_doc_id'=> $insertedDocId
                    ]);
                    $finalArr[$i]['id'] = $saved;
                }
            }
            //echo '<pre>'; print_r($finalArr); die;
            if($saved) {
                $response['status'] = true;
                $response['requestData'] = $finalArr;
                $response['lastInsertedId'] = $saved;
                $response['function_type'] = $requestData['function_type'];

                //Get total amount
                $db_total_deposit_amount = DB::table('partner_student_invoices')->where('partner_id',$requestData['partner_id'])->where('invoice_type',3)->sum('amount_aud');
                $response['db_total_deposit_amount'] = $db_total_deposit_amount; //dd($db_total_deposit_amount );

                if($doc_saved){
                    //Get AWS Url link
                    $url = 'https://'.env('AWS_BUCKET').'.s3.'. env('AWS_DEFAULT_REGION') . '.amazonaws.com/';
                    $awsUrl = $url.$client_unique_id.'/'.$doctype.'/'.$name; //dd($awsUrl);

                    //$awsUrl = URL::to('/img/client_receipts').'/'.$document_upload;
                    $response['awsUrl'] = $awsUrl;
                    $response['message'] = 'Record Payment with document added successfully';

                    $subject = 'added record payment with Receipt No-'.$invoice_id.' and document' ;
                } else {
                    $response['message'] = 'Record payment added successfully';
                    $response['awsUrl'] =  "";
                    $subject = 'added record payment with Receipt No-'.$invoice_id;
                }

                $printUrl = URL::to('/partners/printpreview').'/'.$invoice_id;
                $response['printUrl'] = $printUrl;
            } else {
                $response['lastInsertedId'] = "";
                $response['awsUrl'] =  "";
                $response['requestData'] = "";
                $response['status'] 	= 	false;
                $response['message']	=	'Please try again';
                $response['function_type'] = $requestData['function_type'];
            }
        }
        echo json_encode($response);
    }

    //Update student status
    public function updateStudentStatus(Request $request)
	{
        $statusMap = $this->partnerStudentStatusMap();
        $studentId = $request->input('student_id');
        $newStatusRaw = $request->input('new_status');

        $emptyResponse = [
            'status'       => false,
            'message'      => '',
            'studentId'    => '',
            'newStatus'    => '',
            'newStatus_id' => '',
        ];

        if ($studentId === null || $studentId === '') {
            $emptyResponse['message'] = 'Student application ID is required.';
            echo json_encode($emptyResponse);

            return;
        }

        if (!is_numeric($newStatusRaw)) {
            $emptyResponse['message'] = 'Invalid status selected.';
            echo json_encode($emptyResponse);

            return;
        }

        $newStatus = (int) $newStatusRaw;
        if (!array_key_exists($newStatus, $statusMap)) {
            $emptyResponse['message'] = 'Invalid status selected.';
            echo json_encode($emptyResponse);

            return;
        }

        $application = DB::table('applications')
            ->select('id', 'status', 'partner_id')
            ->where('id', $studentId)
            ->first();

        if (!$application) {
            $emptyResponse['message'] = 'Student application not found. Please try again.';
            echo json_encode($emptyResponse);

            return;
        }

        $statusLabel = $statusMap[$newStatus];
        $response = [
            'status'       => true,
            'message'      => '',
            'studentId'    => $studentId,
            'newStatus'    => $statusLabel,
            'newStatus_id' => $newStatus,
        ];

        if ((int) $application->status === $newStatus) {
            $response['message'] = 'Status is already set to '.$statusLabel.'.';
            echo json_encode($response);

            return;
        }

        $updatedRows = DB::table('applications')
            ->where('id', $studentId)
            ->update(['status' => $newStatus]);

        if ($updatedRows > 0) {
            if ($application->partner_id) {
                $this->clearStudentTabCache((int) $application->partner_id);
            }

            $response['message'] = 'Student status updated successfully.';
            echo json_encode($response);

            return;
        }

        $emptyResponse['message'] = 'Unable to update student status. Please try again.';
        echo json_encode($emptyResponse);
    }


    //Get Student Info
    public function getStudentInfo(Request $request)
	{
        //dd($request->sel_student_id);
        $record_get = \App\Models\Admin::query()->select(['first_name', 'last_name', 'id', 'dob', 'client_id'])
        ->where('id', $request->sel_student_id)->first(); //dd($record_get);
        if($record_get) {
            if( $record_get->dob != ""){
                $dobArr = explode("-",$record_get->dob);
                $dobFinal = $dobArr[2]."/".$dobArr[1]."/".$dobArr[0];
                $response['student_db'] = $dobFinal;
            } else {
                $response['student_db'] = "";
            }
            $response['student_name'] = $record_get->first_name.' '.$record_get->last_name;
            $response['student_ref_no'] = $record_get->client_id;
            $response['status'] 	= 	true;
            $response['message']	=	'Record is exist';
        } else {
            $response['student_db'] =  "";
            $response['status'] 	= 	false;
            $response['message']	=	'Record is not exist.Please try again';
        }
        echo json_encode($response);
    }

    //Get Student Course info and student id
    public function getStudentCourseInfo(Request $request)
	{
        //dd($request->sel_student_id);
        $studentCourseInfo = \App\Models\Application::join('partners', 'applications.partner_id', '=', 'partners.id')
        ->leftJoin('products', 'applications.product_id', '=', 'products.id')
        ->leftJoin('application_fee_options', 'applications.id', '=', 'application_fee_options.app_id')
        ->select('applications.student_id','products.name as coursename','application_fee_options.commission_pending')
        ->where('applications.partner_id', $request->partner_id)
        ->where('applications.client_id', $request->sel_student_id)
        ->where(function ($query) {
            $query->where('applications.stage', 'Coe issued')
                ->orWhere('applications.stage', 'Enrolled')
                ->orWhere('applications.stage', 'Coe Cancelled');
        })->first(); //dd($studentCourseInfo);
        if($studentCourseInfo) {
            if($studentCourseInfo->commission_pending != ""){
                $studentCourseInfo->commission_pending = $studentCourseInfo->commission_pending;
            } else {
                $studentCourseInfo->commission_pending = 0;
            }
            $response['student_course_info'] = $studentCourseInfo;
            $response['status'] 	= 	true;
            $response['message']	=	'Record is exist';
        } else {
            $response['student_course_info'] =  array();
            $response['status'] 	= 	false;
            $response['message']	=	'Record is not exist.Please try again';
        }
        echo json_encode($response);
    }


    public function generateIncrementInvoiceNumber($lastInvoiceNumber = null) {
        // Prefix with the current year
        $yearPrefix = date('y'); // e.g., "24" for the year 2024
        $prefix = "INV-" . $yearPrefix;

        // If there is no previous invoice, start with the base number
        if (!$lastInvoiceNumber) {
            return $prefix . "0001";
        }

        // Extract the numeric part of the last invoice number
        $numericPart = (int)substr($lastInvoiceNumber, strlen($prefix));

        // Increment the numeric part
        $newNumericPart = $numericPart + 1;

        // Combine prefix and the new numeric part
        return $prefix . str_pad($newNumericPart, 4, "0", STR_PAD_LEFT);
    }



    //Get Top Invoice Value From Db
    public function getTopInvoiceValInDB(Request $request)
	{
        $requestData = 	$request->all();
        $invoice_type = $requestData['type'];
		$newInvoice = null;
        $get_invoice_info = DB::table('partner_student_invoices')->select('invoice_no')->where('invoice_type', $invoice_type)->orderBy('id', 'desc')->first();
        //dd($get_invoice_info);
        if($get_invoice_info) {
            $get_max_invoice_id = $get_invoice_info->invoice_no;
            if($invoice_type == 1){
                $newInvoice = $this->generateIncrementInvoiceNumber($get_max_invoice_id);
            }

            $response['invoice_type'] 	= $invoice_type;
            $response['max_invoice_id'] = $newInvoice;
            $response['status'] 	= 	true;
            $response['message']	=	'Record is exist';
        } else {
            $response['invoice_type'] 	= $invoice_type;
            if($invoice_type == 1){
                $response['max_invoice_id'] = "INV-".date('y')."0001";
            }
            $response['status'] 	= 	false;
            $response['message']	=	'Record is not exist.Please try again';
        }
        echo json_encode($response);
    }


    public function printpreviewcreateinvoice(Request $request, string|int $id){
        //phpinfo();
        $record_get = DB::table('partner_student_invoices')->where('invoice_type',1)->where('invoice_id',$id)->get();
        //dd($record_get);
        $logo = '';
        if(!empty($record_get)){
            $partnerInfo = DB::table('partners')->where('id',$record_get[0]->partner_id)->first(); //dd($partnerInfo);
            $crmProfile = \App\Helpers\Helper::defaultCrmProfile();
            $admin = $crmProfile ? (object)['company_name' => $crmProfile->company_name, 'logo' => $crmProfile->logo] : (object)['company_name' => 'Bansal Education Group', 'logo' => null];
            $logo = $crmProfile && $crmProfile->logo ? $crmProfile->logo : '';
        }
        set_time_limit(3000);
        $pdf = PDF::setOptions([
			'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true,
			'logOutputFile' => storage_path('logs/log.htm'),
			'tempDir' => storage_path('logs/')
		])->loadView('emails.studentinvoice',compact(['record_get','partnerInfo','admin','logo']));
		return $pdf->stream('StudentInvoice.pdf');
	}


    //update Invoice Sent Option To Yes
    public function updateInvoiceSentOptionToYes(Request $request)
	{
        $requestData = 	$request->all();
        $sel_invoice_id = $requestData['sel_invoice_id'];
        $sent_date = $requestData['sentDate'];
        /*$today_date = date('Y-m-d');
        $today_date_arr = explode("-", $today_date);
        $sent_date = $today_date_arr[2]."/".$today_date_arr[1]."/".$today_date_arr[0];*/
        $upd = DB::table('partner_student_invoices')->where('invoice_type', 1)->where('invoice_id', $sel_invoice_id)->update(['sent_option' => 'Yes','sent_date' => $sent_date,'save_type' => 'final']);
        //dd($upd);
        if($upd) {
            $response['status'] 	= 	true;
            $response['message']	=	'Invoice is updated successfully';
        } else {
            $response['status'] 	= 	false;
            $response['message']	=	'Invoice is not exist.Please try again';
        }
        echo json_encode($response);
    }

    //Get Info By InvoiceId For draft invoice
    public function getInfoByInvoiceId(Request $request)
	{
        $requestData = 	$request->all();
        $invoiceid = $requestData['invoiceid'];
        $record_get = DB::table('partner_student_invoices')->where('invoice_type',1)->where('invoice_id',$invoiceid)->get();
        //dd($record_get);
        if(!empty($record_get)) {
            $response['record_get'] = $record_get;
            $response['status'] 	= 	true;
            $response['message']	=	'Record is exist';
            $last_record_id = DB::table('partner_student_invoices')->where('invoice_type',1)->max('id');
            //dd($last_record_id);
            $response['last_record_id'] = $last_record_id;
            $response['invoiceid'] = $invoiceid;
        }else{
            $response['record_get'] =   array();
            $response['status'] 	= 	false;
            $response['message']	=	'Record is not exist.Please try again';
            $response['last_record_id'] = 0;
            $response['invoiceid'] = $invoiceid;
        }
        echo json_encode($response);
    }


    //Get Enrolled Student List in Edit mode
    public function getEnrolledStudentListInEditMode(Request $request)
    {
        //dd($request->partnerid);
        $record_get = \App\Models\Application::join('admins', 'applications.client_id', '=', 'admins.id')
        ->leftJoin('partners', 'applications.partner_id', '=', 'partners.id')
        ->select('admins.client_id as client_reference','admins.first_name','admins.last_name','admins.id')
        ->where('applications.partner_id', $request->partnerid)
        ->where(function ($query) {
            $query->where('applications.stage', 'Coe issued')
            ->orWhere('applications.stage', 'Enrolled')
            ->orWhere('applications.stage', 'Coe Cancelled');
        })
        ->groupBy(['admins.client_id', 'admins.first_name', 'admins.last_name', 'admins.id']) // PostgreSQL requires all selected columns in GROUP BY
        ->orderBy('admins.first_name', 'ASC')
        ->get(); //dd($record_get);

        $studentRecordInfo = DB::table('partner_student_invoices')->select('student_id')->where('id',$request->uniqueRowId)->first();
        if(!empty($record_get)) {
            $str = "<option value=''>Select</option>";
            foreach($record_get as $key=>$val) {
                $student_name = $val->first_name.' '.$val->last_name.'('.$val->client_reference.')';
                // Check if the current student's ID matches
                if($studentRecordInfo->student_id){
                    $selected = ($studentRecordInfo->student_id == $val->id) ? 'selected' : '';
                    $str .= '<option value="' . $val->id . '" ' . $selected . '>' . $student_name . '</option>';
                } else {
                    $str .=  '<option value="'.$val->id.'" >'.$student_name.'</option>';
                }
            }
            $response['record_get'] =  $str;
            $response['status'] 	=  true;
            $response['message']	=  'Record is exist';
        } else {
            $response['record_get'] =  array();
            $response['status'] 	=  false;
            $response['message']	=  'Record is not exist.Please try again';
        }
        echo json_encode($response);
    }


    //Partner upload inbox email
    public function uploadpartnerfetchmail(Request $request)
    {
        $this->processPartnerMsgUpload(
            $request,
            'inbox',
            'Partner inbox email uploaded successfully.',
            'No inbox email uploaded.Please try again'
        );
    }

    //Partner upload sent email
    public function uploadpartnersentfetchmail(Request $request)
    {
        $this->processPartnerMsgUpload(
            $request,
            'sent',
            'Partner sent email uploaded successfully.',
            'No sent email uploaded.Please try again'
        );
    }

    /**
     * Parse, store, and record partner inbox/sent .msg uploads.
     */
    private function processPartnerMsgUpload(Request $request, string $mailType, string $successMessage, string $failureMessage): void
    {
        $request->validate([
            'email_file' => 'required|array',
            'email_file.*' => 'required|mimes:msg|max:20480',
        ]);

        $response = [
            'status' => false,
            'message' => $failureMessage,
        ];

        $partnerInfo = Partner::query()->select(['id', 'partner_name', 'email'])->where('id', $request->partner_id)->first();
        $partnerUniqueId = !empty($partnerInfo) ? $partnerInfo->email : '';
        $docType = 'partner_email_fetch';

        if (!$request->hasfile('email_file')) {
            echo json_encode($response);
            return;
        }

        $files = $request->file('email_file');
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            $parsedData = $this->emailParser->parseUploadedMsgFile($file);
            if ($this->partnerMsgParseFailed($parsedData)) {
                $response['status'] = false;
                $response['message'] = $parsedData['error'] ?? 'Failed to parse email file.';
                continue;
            }

            $emailFields = $this->partnerEmailFieldsFromParsedData($parsedData);
            $fileName = $file->getClientOriginalName();
            $fileExtension = $file->getClientOriginalExtension();
            $name = time() . $fileName;
            $filePath = $partnerUniqueId . '/' . $docType . '/' . $mailType . '/' . $name;

            $fileContents = file_get_contents($file->getPathname());
            if ($fileContents === false) {
                $response['status'] = false;
                $response['message'] = 'Failed to read email file.';
                continue;
            }

            Storage::disk('s3')->put($filePath, $fileContents);
            $fileUrl = $this->s3Url($filePath);

            $document = new \App\Models\Document;
            $document->file_name = $fileName;
            $document->filetype = $fileExtension;
            $document->user_id = Auth::user()->id;
            $document->myfile = $fileUrl;
            $document->myfile_key = $name;
            $document->client_id = $request->partner_id;
            $document->type = $request->type;
            $document->mail_type = $mailType;
            $document->file_size = $file->getSize();
            $document->doc_type = $docType;

            if (!$document->save()) {
                $response['status'] = false;
                $response['message'] = $failureMessage;
                continue;
            }

            $email = new \App\Models\Email;
            $email->user_id = Auth::user()->id;
            $email->from_mail = $emailFields['from_mail'];
            $email->to_mail = $emailFields['to_mail'];
            $email->subject = $emailFields['subject'];
            $email->message = $emailFields['message'];
            $email->mail_type = 1;
            $email->client_id = $request->partner_id;
            $email->conversion_type = $docType;
            $email->mail_body_type = $mailType;
            $email->uploaded_doc_id = $document->id;
            $email->fetch_mail_sent_time = $emailFields['fetch_mail_sent_time'];
            $email->type = $request->type;

            if ($email->save()) {
                $response['status'] = true;
                $response['message'] = $successMessage;
            } else {
                $response['status'] = false;
                $response['message'] = $failureMessage;
            }
        }

        echo json_encode($response);
    }

    private function partnerMsgParseFailed(array $parsedData): bool
    {
        return isset($parsedData['error']) || (isset($parsedData['success']) && $parsedData['success'] === false);
    }

    /**
     * Map Python parser output to partner email record fields.
     */
    private function partnerEmailFieldsFromParsedData(array $parsedData): array
    {
        return [
            'subject' => $parsedData['subject'] ?? '',
            'from_mail' => $parsedData['sender_email'] ?? '',
            'message' => $parsedData['html_content'] ?? $parsedData['text_content'] ?? '',
            'to_mail' => isset($parsedData['recipients']) && is_array($parsedData['recipients'])
                ? implode(',', $parsedData['recipients'])
                : '',
            'fetch_mail_sent_time' => $this->emailParser->formatFetchMailSentTime($parsedData),
        ];
    }
   
    


    //Delete Student Invoice
    public function deleteStudentRecordByInvoiceId(Request $request)
	{
        $requestData = 	$request->all();
        $invoiceid = $requestData['invoiceid'];
        $invoicetype = $requestData['invoicetype'];
        $partnerid = $requestData['partnerid'];
        $record_get = DB::table('partner_student_invoices')->where('invoice_type',$invoicetype)->where('invoice_id',$invoiceid)->where('partner_id',$partnerid)->delete();
        //dd($record_get);
        //$record_get = 1;
        if($record_get) {
            $response['status'] 	= 	true;
            $response['message']	=	'Record is deleted';
            $sum = DB::table('partner_student_invoices')->where('invoice_type',$invoicetype)->where('partner_id',$partnerid)->sum('amount_aud');
            //dd($sum);
            $response['sum'] = $sum;
            $response['invoiceid']  = $invoiceid;
            $response['invoicetype'] = $invoicetype;
            $response['partnerid']  = $partnerid;
        } else {
            $response['status'] 	= 	false;
            $response['message']	=	'Record is not exist.Please try again';
            $response['sum']  = 0;
            $response['invoiceid']  = "";
            $response['invoicetype'] = "";
            $response['partnerid']  = "";
        }
        echo json_encode($response);
    }

    //Delete Student Record Invoice
    public function deleteStudentRecordInvoiceByInvoiceId(Request $request)
	{
        $requestData = 	$request->all();
        $id = $requestData['id'];
        $invoicetype = $requestData['invoicetype'];
        $partnerid = $requestData['partnerid'];
        $record_get = DB::table('partner_student_invoices')->where('id',$id)->delete();
        //dd($record_get);
        if($record_get) {
            $response['status'] 	= 	true;
            $response['message']	=	'Record is deleted';
            $sum = DB::table('partner_student_invoices')->where('invoice_type',$invoicetype)->where('partner_id',$partnerid)->sum('amount_aud');
            //dd($sum);
            $response['sum'] = $sum;
            $response['id']  = $id;
            $response['invoicetype'] = $invoicetype;
            $response['partnerid']  = $partnerid;
        } else {
            $response['status'] 	= 	false;
            $response['message']	=	'Record is not exist.Please try again';
            $response['sum']  = 0;
            $response['id']  = "";
            $response['invoicetype'] = "";
            $response['partnerid']  = "";
        }
        echo json_encode($response);
    }

    //Delete Student Payment Invoice
    public function deleteStudentPaymentInvoiceByInvoiceId(Request $request)
	{
        $requestData = 	$request->all();
        $id = $requestData['id'];
        $invoicetype = $requestData['invoicetype'];
        $partnerid = $requestData['partnerid'];
        $record_get = DB::table('partner_student_invoices')->where('id',$id)->delete();
        //dd($record_get);
        if($record_get) {
            $response['status'] 	= 	true;
            $response['message']	=	'Record is deleted';
            $sum = DB::table('partner_student_invoices')->where('invoice_type',$invoicetype)->where('partner_id',$partnerid)->sum('amount_aud');
            //dd($sum);
            $response['sum'] = $sum;
            $response['id']  = $id;
            $response['invoicetype'] = $invoicetype;
            $response['partnerid']  = $partnerid;
        } else {
            $response['status'] 	= 	false;
            $response['message']	=	'Record is not exist.Please try again';
            $response['sum']  = 0;
            $response['id']  = "";
            $response['invoicetype'] = "";
            $response['partnerid']  = "";
        }
        echo json_encode($response);
    }
  
  
    //Assign partner action and save
	public function actionstore_partner(Request $request){
	    $requestData = $request->all(); //echo '<pre>'; print_r($requestData); die;
        $partner_decode_id = base64_encode(convert_uuencode($requestData['partner_id'])); //dd($partner_decode_id);
        $action 				    = new \App\Models\Note;
        $action->client_id		= @$requestData['partner_id'];
		$action->user_id			= Auth::user()->id;
        
        // Get description from assignnote field
        $description = isset($requestData['assignnote']) ? $requestData['assignnote'] : '';
        $action->description		= $description;

        //Get assigner name
        $assignee_info = \App\Models\Staff::query()->select(['id', 'first_name', 'last_name'])->find($requestData['rem_cat123']);
        if($assignee_info){
            $assignee_name = $assignee_info->first_name;
        } else {
            $assignee_name = 'N/A';
        }

        if(isset($requestData['note_deadline']) && $requestData['note_deadline'] != ''){
            $title = 'Partner assigned action with deadline '.$requestData['note_deadline'].' to '.$assignee_name;
            $recurring_type = $requestData['recurring_type'];
        } else {
            $title = 'Partner assigned action to '.$assignee_name;
            $recurring_type = "";
        }

		$action->title		    = $title;
        $action->is_action	        = 1;
        $action->task_group       =  $requestData['task_group'];
		$action->assigned_to	    =  @$requestData['rem_cat123'];
        if( isset($requestData['popoverdate']) && $requestData['popoverdate'] != "" ){
            $popoverdateArr = explode("/",$requestData['popoverdate']);
            $popoverdateFormated = $popoverdateArr[2]."-".$popoverdateArr[1]."-".$popoverdateArr[0];
        } else {
            $popoverdateFormated = "";
        }
		$action->action_assign_date	=  $popoverdateFormated;
        $action->type	            =  $requestData['type'];

        //add note deadline
        if(isset($requestData['note_deadline_checkbox']) && $requestData['note_deadline_checkbox'] != ''){
            if($requestData['note_deadline_checkbox'] == 1){
                $note_deadlineArr = explode("/",$requestData['note_deadline']);
                $note_deadlineArrFormated = $note_deadlineArr[2]."-".$note_deadlineArr[1]."-".$note_deadlineArr[0];
                $action->note_deadline = $note_deadlineArrFormated;
                $action->deadline_recurring_type = $recurring_type;
            } else {
                $action->note_deadline = NULL;
                $action->deadline_recurring_type = NULL;
            }
        } else {
            $action->note_deadline = NULL;
            $action->deadline_recurring_type = NULL;
        }
		$action->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
		$action->status = 0; // Required NOT NULL field (0 = active/open, 1 = closed/completed)

        $saved	=  $action->save();
        if(!$saved) {
			echo json_encode(array('success' => false, 'message' => 'Please try again', 'clientID' => $partner_decode_id));
		} else {
			$o = new \App\Models\Notification;
	    	$o->sender_id = Auth::user()->id;
	    	$o->receiver_id = @$requestData['rem_cat123'];
	    	$o->module_id = $requestData['partner_id'];
            $o->url = route('partners.detail', $partner_decode_id);
	    	$o->notification_type = 'partner';
	    	$o->message = 'Action Assigned by '.Auth::user()->first_name.' '.Auth::user()->last_name.' '.date('d/M/Y h:i A');
	    	$o->seen = 0; // Set seen to 0 (unseen) for new notifications
	    	$o->save();

            //save in activity log
			$objs = new ActivitiesLog;
            $objs->client_id = @$requestData['partner_id'];
            $objs->created_by = Auth::user()->id;

            if(isset($requestData['note_deadline']) && $requestData['note_deadline'] != ''){
                $subject = 'Partner assigned action in group '.$requestData['task_group'].' with deadline '.$requestData['note_deadline'].' to '.$assignee_name;
            } else {
                $subject = 'Partner assigned action in group '.$requestData['task_group'].' to '.@$assignee_name;
            }
            $objs->subject = $subject;
            // Use the cleaned description variable instead of raw request data
            $objs->description = '<span class="text-semi-bold">'.@$title.'</span><p>'.$description.'</p>';
            if(Auth::user()->id != @$requestData['rem_cat123']){
                $objs->use_for = @$requestData['rem_cat123'];
            } else {
                $objs->use_for = null; // Use null instead of empty string for PostgreSQL
            }
            $objs->followup_date = $popoverdateFormated;
            $objs->task_group = 'partner'; //$requestData['task_group'];
            $objs->task_status = 0; // Required NOT NULL field (0 = activity, 1 = task)
            $objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
            $objs->save();

            echo json_encode(array('success' => true, 'message' => 'Partner successfully assigned action', 'clientID' => $partner_decode_id,'partner_id'=>$requestData['partner_id']));
			exit;
		}
	}
  
    //Get partner all actions
    /*public function partnerActivities(Request $request){ //dd($request->all());
		if(Partner::query()->where('id', $request->id)->exists()){
			$activities = ActivitiesLog::query()->where('client_id', $request->id)->where('task_group', 'partner')->orderby('created_at', 'DESC')->get();
			$data = array();
			foreach($activities as $activit){
				$admin = \App\Models\Staff::query()->find($activit->created_by) ?? Admin::query()->find($activit->created_by);
                $data[] = array(
                    'activity_id' => $activit->id,
					'subject' => $activit->subject,
					'createdname' => substr($admin->first_name, 0, 1),
					'name' => $admin->first_name,
					'message' => $activit->description,
					'date' => date('d M Y, H:i A', strtotime($activit->created_at)),
                   'followup_date' => date('d/m/Y',strtotime($activit->followup_date)),
                   'task_group' => $activit->task_group,
                   'pin' => $activit->pin
                );
			}
            $response['status'] 	= 	true;
			$response['data']	=	$data;
		} else{
			$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
		}
		echo json_encode($response);
	}*/
  
  
    //Fetch all contact list of any partner at create note popup
     public function fetchPartnerContactNo(Request $request){ //dd($request->all());
        if( \App\Models\PartnerPhone::query()->where('partner_id', $request->partner_id)->exists())
        {
            //Fetch All partner contacts
            $partnerContacts = \App\Models\PartnerPhone::query()->select(['partner_phone', 'partner_country_code'])->where('partner_id', $request->partner_id)->get();
            //dd($partnerContacts);
            if( !empty($partnerContacts) && count($partnerContacts)>0 ){
                $response['status'] 	= 	true;
                $response['message']	=	'Partner contact is successfully fetched.';
                $response['partnerContacts']	=	$partnerContacts;
            } else {
                $response['status'] 	= 	false;
                $response['message']	=	'Please try again';
                $response['partnerContacts']	=	array();
            }
        }
        else
        {
            if( \App\Models\Partner::query()->where('id', $request->partner_id)->exists()){
                //Fetch All partner contacts
                $partnerContacts = \App\Models\Partner::query()->select(['phone as partner_phone', 'country_code as partner_country_code'])->where('id', $request->partner_id)->get();
                //dd($partnerContacts);
                if( !empty($partnerContacts) && count($partnerContacts)>0 ){
                    $response['status'] 	= 	true;
                    $response['message']	=	'Partner contact is successfully fetched.';
                    $response['partnerContacts']	=	$partnerContacts;
                } else {
                    $response['status'] 	= 	false;
                    $response['message']	=	'Please try again';
                    $response['partnerContacts']	=	array();
                }
            }
            else {
                $response['status'] 	= 	false;
                $response['message']	=	'Please try again';
                $response['partnerContacts']	=	array();
            }
        }
        echo json_encode($response);
	}
  
  
  
    //Update student application overall status
    public function updateStudentApplicationOverallStatus(Request $request)
	{
        //dd($request->all());
		$response = [];

		if (!isset($request->application_overall_status)) {
			$response['status'] = false;
			$response['message'] = 'No changes made or student application overall not found.Please try again';
			echo json_encode($response);
			return;
		}

        if( (int) $request->application_overall_status === 0){
            $application_overall_status = 1;
        } else if( (int) $request->application_overall_status === 1){
            $application_overall_status = 0;
        } else {
			$response['status'] = false;
			$response['message'] = 'No changes made or student application overall not found.Please try again';
			echo json_encode($response);
			return;
		}
        $updatedRows = DB::table('applications')->where('id', $request->application_student_id)->update(['overall_status' => $application_overall_status]);
        // Check if the update was successful
        if ($updatedRows > 0) {
            // Bust student tab cache — active/inactive split has changed.
            $app = DB::table('applications')->select('partner_id')->where('id', $request->application_student_id)->first();
            if ($app && $app->partner_id) {
                $this->clearStudentTabCache((int) $app->partner_id);
            }

            $response['status'] 	= 	true;
            $response['message']	=	'Student application overall status updated successfully.';
        } else {
            $response['status'] 	= 	false;
            $response['message']	=	'No changes made or student application overall not found.Please try again';
        }
        echo json_encode($response);
    }
  
  
    //Add Note To Student
    public function addstudentnote(Request $request){ //dd($request->all());
        //In Student Note
        if(isset($request->noteid) && $request->noteid != ''){
            $obj = \App\Models\Note::query()->find($request->noteid);
        } else {
            $obj = new \App\Models\Note;
        }

        $obj->client_id = $request->student_id; // In student note
        $obj->user_id = Auth::user()->id;
        $obj->title = $request->title;
        $obj->mail_id = $request->mailid;
        $obj->type = 'client';

        if( isset($request->mobileNumber) && $request->mobileNumber != ""){
            $obj->mobile_number = $request->mobileNumber; // Add this line
        }

        //$obj->description = $request->description;
        $partner_encoded_id = base64_encode(convert_uuencode(@$request->partner_id)) ;
        $partner_reference = '<a href="'.route('partners.detail', $partner_encoded_id).'" target="_blank" >'.$request->college_name.'</a>';

        $title = 'Added a note by partner '.$partner_reference;
        if(isset($request->noteid) && $request->noteid != ''){
            $title = 'Updated a note by partner '.$partner_reference;
        }
        if( isset($request->mobileNumber) && $request->mobileNumber != ""){
            //$obj->description = '<span class="text-semi-bold">'.$title.'</span><p>'.$request->description.'</p><p>'.$request->mobileNumber.'</p>';
            $obj->description = '<span class="text-semi-bold">'.$title.'</span><p>'.$request->description.'</p>';
        } else {
            $obj->description = '<span class="text-semi-bold">'.$title.'</span><p>'.$request->description.'</p>';
        }
        $obj->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
        $obj->is_action = 0; // Required NOT NULL field (0 = not a followup, 1 = followup)
        $obj->status = 0; // Required NOT NULL field (0 = active/open, 1 = closed/completed)
        $saved = $obj->save();
		if($saved){
            //In Partner activity log
            if($request->vtype == 'partner'){
                $client_encoded_id = base64_encode(convert_uuencode(@$request->student_id)) ;
                $client_reference = '<a href="'.route('clients.detail', $client_encoded_id).'" target="_blank" >'.$request->student_ref_no.'</a>';

                $subject = 'added a note for '.$client_reference;
                if(isset($request->noteid) && $request->noteid != ''){
					$subject = 'updated a note for '.$client_reference;
                }

                $objs = new ActivitiesLog;
                $objs->client_id = $request->partner_id;
				$objs->task_group = $request->vtype; //partner
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
                $response['message']	=	'You’ve successfully updated Note';
            }else{
                $response['message']	=	'You’ve successfully added Note';
            }
		}else{
			$response['status'] 	= 	false;
			$response['message']	=	'Please try again';
		}
        echo json_encode($response);
	}


    //Get Partner all activity logs
    public function activities(Request $request){
		$partnerId = (int) ($request->partner_id ?: $request->id);

		if ($partnerId <= 0 || !Partner::query()->where('id', $partnerId)->exists()) {
			$response['status']  = false;
			$response['message'] = 'Please try again';
			echo json_encode($response);
			return;
		}

		$activities = ActivitiesLog::query()
			->where('client_id', $partnerId)
			->where('task_group', 'partner')
			->orderby('created_at', 'DESC')
			->get();

		$creatorIds = $activities->pluck('created_by')->unique()->filter()->values();
		$staffMap = Staff::query()->whereIn('id', $creatorIds)->get()->keyBy('id');
		$adminMap = Admin::query()->whereIn('id', $creatorIds)->get()->keyBy('id');

		$data = [];
		foreach ($activities as $activit) {
			$admin = $staffMap->get($activit->created_by) ?? $adminMap->get($activit->created_by);
			if (!$admin) {
				continue;
			}
			$data[] = [
				'activity_id'   => $activit->id,
				'subject'       => $activit->subject,
				'createdname'   => substr($admin->first_name, 0, 1),
				'name'          => $admin->first_name,
				'message'       => $activit->description,
				'date'          => date('d M Y, H:i A', strtotime($activit->created_at)),
				'followup_date' => $activit->followup_date,
				'task_group'    => $activit->task_group,
				'pin'           => $activit->pin,
			];
		}

		$response['status'] = true;
		$response['data']   = $data;
		$response['html']   = view('Admin.partials.activities-list', [
			'activities' => $activities,
			'staffMap'   => $staffMap,
			'adminMap'   => $adminMap,
		])->render();

		echo json_encode($response);
	}

  
  
  
  
    public function saveStudentEnrolmentType(Request $request)
    {
        $request->validate([
            'rowId' => 'required|integer',
            'enrolment_type' => 'nullable|string',
        ]);

        $enrolmentType = $request->input('enrolment_type');
        if ($enrolmentType !== null && $enrolmentType !== '' && !array_key_exists($enrolmentType, \App\Models\Application::enrolmentTypeOptions())) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid enrolment type selected.',
                'studentId' => '',
                'enrolmentTypeHtml' => '',
            ]);
        }

        $application = \App\Models\Application::query()->find($request->rowId);
        if (!$application) {
            return response()->json([
                'status' => false,
                'message' => 'Application not found. Please try again.',
                'studentId' => '',
                'enrolmentTypeHtml' => '',
            ]);
        }

        $existing = \App\Models\Application::normalizeEnrolmentType($application->enrolment_type);
        if (! \App\Models\Application::canEditEnrolmentOrCompanyValue($existing)) {
            return response()->json([
                'status' => false,
                'message' => 'Only Super Admin and Admin can update Enrolment Type once it is set.',
                'studentId' => (string) $application->id,
                'enrolmentTypeHtml' => '',
                'enrolmentType' => $existing,
            ]);
        }

        $application->enrolment_type = ($enrolmentType === '' || $enrolmentType === null)
            ? null
            : \App\Models\Application::normalizeEnrolmentType($enrolmentType);
        $application->save();

        if ($application->partner_id) {
            $this->clearStudentTabCache((int) $application->partner_id);
        }

        return response()->json([
            'status' => true,
            'message' => 'Enrolment type updated successfully.',
            'studentId' => (string) $application->id,
            'enrolmentType' => (string) \App\Models\Application::normalizeEnrolmentType($application->enrolment_type),
        ]);
    }

   //save student note
    public function saveStudentNote(Request $request)
	{
        //dd($request->all());
        $updatedRows = DB::table('applications')->where('id', $request->rowId)->update(['student_add_notes' => $request->note]);
        // Check if the update was successful
        if ($updatedRows > 0) {
            // Bust student tab cache so the saved note appears on next tab load.
            $app = DB::table('applications')->select('partner_id')->where('id', $request->rowId)->first();
            if ($app && $app->partner_id) {
                $this->clearStudentTabCache((int) $app->partner_id);
            }

            $response['status'] 	= 	true;
            $response['message']	=	'Student note added successfully.';
            $response['studentId']	=   $request->rowId;
            $response['studentNote']	= $request->note;
        } else {
            $response['status'] 	= 	false;
            $response['message']	=	'No changes made or student not found.Please try again';
            $response['studentId']	=  "";
            $response['studentNote']	= "";
        }

        return response()->json($response);
    }
  
    
    //Get partner notes
    public function getPartnerNotes(Request $request){
		$client_id = $request->clientid;
		$type = $request->type;

		$notelist = \App\Models\Note::query()->where('client_id',$client_id)->whereNull('assigned_to')->whereNull('task_group')->where('type',$type)->orderby('pin', 'DESC')->orderByRaw('created_at DESC NULLS LAST')->get();

		$staffIds = $notelist->pluck('user_id')->unique()->filter();
		$staffMap = Staff::query()->whereIn('id', $staffIds)->get()->keyBy('id');

		ob_start();
		foreach($notelist as $list){
			$admin = $staffMap->get($list->user_id);
			if (!$admin) {
				continue;
			}
			?>
			<div class="note_col" id="note_id_<?php echo $list->id; ?>">
                <div class="note-icon bg-primary text-white" style="width: 50px;height: 50px;line-height: 50px;font-size: 20px;margin-right: 20px;border-radius: 50%;text-align: center;">
                    <span><?php echo substr($admin->first_name, 0, 1);?></span>
                </div>
				<div class="note_content">
					<!--<h4><a class="viewnote" data-id="<?php //echo $list->id; ?>" href="javascript:;"><?php //echo @$list->title == "" ? config('constants.empty') : str_limit(@$list->title, '19', '...'); ?> </a></h4>-->
					<div class="note-title" style="display: inline-block;margin-right: 60px;">
                        <p><b><?php echo $admin->first_name;?></b>  Added Note with Title <b><?php echo @$list->title; ?></b></p>
                    </div>

                    <div class="note-date" style="display: inline-block;">
                        <span class="text-job"><?php echo date('d M Y, H:i A', strtotime($list->updated_at));?></span>
                    </div>

                    <div class="right" style="float: right;width: 15px;">
						<div class="dropdown d-inline dropdown_ellipsis_icon">
							<a class="dropdown-toggle" type="button" id="" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo \App\Helpers\IconHelper::render('ellipsis-v'); ?></a>
							<div class="dropdown-menu">
								<a class="dropdown-item opennoteform" data-id="<?php echo $list->id; ?>" href="javascript:;">Edit</a>
                                <?php if(Auth::user()->role == 1){ ?>
								<a data-id="<?php echo $list->id; ?>" data-href="deletenote" class="dropdown-item deletenote" href="javascript:;" >Delete</a>
								<?php }?>
                                <?php if($list->pin == 1){ ?>
									<a data-id="<?php echo $list->id; ?>"  class="dropdown-item pinnote" href="javascript:;" >UnPin</a>
								<?php }else{ ?>
									<a data-id="<?php echo $list->id; ?>"  class="dropdown-item pinnote" href="javascript:;" >Pin</a>
								<?php } ?>
							</div>
						</div>
					</div>

                    <?php if($list->pin == 1){ ?>
                        <div class="pined_note"><?php echo \App\Helpers\IconHelper::render('thumbtack'); ?></div>
                    <?php } ?>
				</div>
				<div class="extra_content">
				    <p><?php echo @$list->description; ?></p>

                    <?php if( isset($list->mobile_number) && $list->mobile_number != ""){ ?>
                        <p><?php echo @$list->mobile_number; ?></p>
                    <?php } ?>

					<!--<div class="left">
						<div class="author">
							<a href="<?php //echo URL::to('/users/view/'.$admin->id); ?>"><?php //echo substr($admin->first_name, 0, 1); ?></a>
						</div>
						<div class="note_modify">
							<small>Last Modified <span><?php //echo date('d/m/Y h:i A', strtotime($list->updated_at)); ?></span></small>
							<?php //echo $admin->first_name.' '.$admin->last_name; ?>
						</div>
					</div>-->

				</div>
			</div>
			<?php
		}
		$output = ob_get_clean();
		if ($notelist->isEmpty()) {
			return '<h4>No Record Found</h4>';
		}
		return $output;
	}
  
  
   //Partner upload document
    public function uploadpartnerdocumentupload(Request $request){ //dd($request->all());
        $id = $request->clientid;
		$response = [
			'status' => false,
			'message' => 'Please try again',
		];
		$saved = false;
        //get partner info
        $partner_info = \App\Models\Partner::select('email')->where('id', $id)->first(); //dd($partner_info);
        if(!empty($partner_info)){
            $partner_unique_email = $partner_info->email;
        } else {
            $partner_unique_email = "";
        }
        if ($request->hasfile('document_upload')) {
            if(!is_array($request->file('document_upload'))){
				$files[] = $request->file('document_upload');
			}else{
				$files = $request->file('document_upload');
			}

		    foreach ($files as $file) {
                $size = $file->getSize();
                $fileName = $file->getClientOriginalName();
                $nameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
                $fileExtension = $file->getClientOriginalExtension();
                //echo $nameWithoutExtension."===".$fileExtension;
                //$document_upload = $this->uploadrenameFile($file, Config::get('constants.documents'));

                $name = time() . $file->getClientOriginalName();
                $filePath = $partner_unique_email.'/partner_document/'. $name;
                Storage::disk('s3')->put($filePath, file_get_contents($file));

                $obj = new \App\Models\Document;
                $obj->file_name = $nameWithoutExtension;
                $obj->filetype = $fileExtension;
                $obj->user_id = Auth::user()->id;
                // Get the full URL of the uploaded file
                $fileUrl = $this->s3Url($filePath);
                $obj->myfile = $fileUrl;
                $obj->myfile_key = $name;

                $obj->client_id = $id;
                $obj->type = $request->type;
                $obj->file_size = $size;
                $obj->doc_type = '';
                $saved = $obj->save();
            }

			if($saved){
				if($request->type == 'partner'){
                    $subject = 'added 1 partner document';
                    $objs = new ActivitiesLog;
                    $objs->client_id = $id;
                    $objs->created_by = Auth::user()->id;
                    $objs->description = '';
                    $objs->subject = $subject;
                    $objs->task_status = 0; // Required NOT NULL field (0 = activity, 1 = task)
                    $objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
                    $objs->save();
                }
				$response['status'] 	= 	true;
				$response['message']	=	'You have successfully uploaded your partner document';
				$fetchd = \App\Models\Document::query()->where('client_id',$id)
                        ->where(function ($query) {
                            $query->whereNull('doc_type')
                                ->orWhere('doc_type', '');
                        })->where('type','partner')->orderby('created_at', 'DESC')->get();
				ob_start();
				foreach($fetchd as $fetch){
					$admin = \App\Models\Staff::query()->find($fetch->user_id);
                    ?>
					<tr class="drow" id="id_<?php echo $fetch->id; ?>">
                        <td style="white-space: initial;">
                            <div data-id="{{$fetch->id}}" data-name="<?php echo $fetch->file_name; ?>" class="doc-row">
                                <?php echo \App\Helpers\IconHelper::render('file-image'); ?> <span><?php echo $fetch->file_name; ?><?php echo '.'.$fetch->filetype; ?></span>
                            </div>
                        </td>
						<td style="white-space: initial;"><?php echo $admin->first_name; ?></td>
                        <td style="white-space: initial;"><?php echo date('d/m/Y', strtotime($fetch->created_at)); ?></td>
						<td>
							<div class="dropdown d-inline">
								<button class="btn btn-primary dropdown-toggle" type="button" id="" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
								<div class="dropdown-menu">
									<a class="dropdown-item renamedoc" href="javascript:;">Rename</a>
									<?php
                                    if( isset($fetch->myfile_key) && $fetch->myfile_key !="")
                                    { ?>
                                        <a target="_blank" class="dropdown-item" href="<?php echo $fetch->myfile; ?>">Preview</a>
                                        <a target="_blank" class="dropdown-item" href="<?php echo $fetch->myfile; ?>">PDF</a>
                                        <a download class="dropdown-item" href="<?php echo $fetch->myfile; ?>">Download</a>
                                    <?php
                                    }
                                    else
                                    {
                                        if (filter_var($fetch->myfile, FILTER_VALIDATE_URL)) { //String is a valid URL
                                        ?>
                                            <a target="_blank" class="dropdown-item" href="<?php echo $fetch->myfile; ?>">Preview</a>
                                            <a target="_blank" class="dropdown-item" href="<?php echo $fetch->myfile; ?>">PDF</a>
                                            <a download class="dropdown-item" href="<?php echo $fetch->myfile; ?>">Download</a>
                                        <?php
                                        }
                                        else
                                        { //String is not a valid URL
                                        ?>
                                            <a target="_blank" class="dropdown-item" href="<?php echo asset('img/documents'); ?>/<?php echo $fetch->myfile; ?>">Preview</a>
                                            <?php
                                            $explodeimg = explode('.',$fetch->myfile);
                                            if($explodeimg[1] == 'jpg'|| $explodeimg[1] == 'png'|| $explodeimg[1] == 'jpeg'){ ?>
                                                <a target="_blank" class="dropdown-item" href="<?php echo URL::to('/document/download/pdf'); ?>/<?php echo $fetch->id; ?>">PDF</a>
                                            <?php } ?>
                                            <a download class="dropdown-item" href="<?php echo asset('img/documents'); ?>/<?php echo $fetch->myfile; ?>">Download</a>
                                        <?php
                                        }
                                    }
                                    ?>

									<a data-id="<?php echo $fetch->id; ?>" class="dropdown-item deletenote" data-href="deletedocs" href="javascript:;" >Delete</a>
								</div>
							</div>
						</td>
					</tr>
					<?php
				}
				$data = ob_get_clean();
				ob_start();
				foreach($fetchd as $fetch){
					$admin = \App\Models\Staff::query()->find($fetch->user_id);
					?>
					<div class="grid_list">
						<div class="grid_col">
							<div class="grid_icon">
								<?php echo \App\Helpers\IconHelper::render('file-image'); ?>
							</div>
							<div class="grid_content">
								<span id="grid_<?php echo $fetch->id; ?>" class="gridfilename"><?php echo $fetch->file_name; ?></span>
								<div class="dropdown d-inline dropdown_ellipsis_icon">
									<a class="dropdown-toggle" type="button" id="" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo \App\Helpers\IconHelper::render('ellipsis-v'); ?></a>
									<div class="dropdown-menu">
										<?php
                                        if( isset($fetch->myfile_key) && $fetch->myfile_key !="")
                                        { ?>
                                            <a target="_blank" class="dropdown-item" href="<?php echo $fetch->myfile; ?>">Preview</a>
                                            <a download class="dropdown-item" href="<?php echo $fetch->myfile; ?>">Download</a>
                                        <?php
                                        }
                                        else
                                        {
                                            if (filter_var($fetch->myfile, FILTER_VALIDATE_URL)) { //String is a valid URL
                                            ?>
                                                <a target="_blank" class="dropdown-item" href="<?php echo $fetch->myfile; ?>">Preview</a>
                                                <a download class="dropdown-item" href="<?php echo $fetch->myfile; ?>">Download</a>
                                            <?php
                                            }
                                            else
                                            { //String is not a valid URL
                                            ?>
                                                <a target="_blank" class="dropdown-item" href="<?php echo asset('img/documents'); ?>/<?php echo $fetch->myfile; ?>">Preview</a>
                                                <a download class="dropdown-item" href="<?php echo asset('img/documents'); ?>/<?php echo $fetch->myfile; ?>">Download</a>
                                            <?php
                                            }
                                        }
                                        ?>
										<a data-id="<?php echo $fetch->id; ?>" class="dropdown-item deletenote" data-href="deletedocs" href="javascript:;" >Delete</a>
									</div>
								</div>
							</div>
						</div>
					</div>
					<?php
				}
				$griddata = ob_get_clean();
				$response['data']	= $data;
				$response['griddata'] = $griddata;
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
    
    //Add all document checklist for partners
    public function addalldocchecklist(Request $request){ 
        try {
            $response = ['status' => false, 'message' => 'Please try again'];
            $partnerid = $request->clientid;
            
            if(empty($partnerid)) {
                $response['message'] = 'Partner ID is required';
                echo json_encode($response);
                return;
            }
            
            $partner_info = \App\Models\Partner::select('email')->where('id', $partnerid)->first();
            if(!empty($partner_info)){
                $partner_unique_email = $partner_info->email;
            } else {
                $partner_unique_email = "";
            }
            
            $doctype = isset($request->doctype) ? $request->doctype : 'documents';
            $checklist = $request->input('checklist');
            $debugId = $request->input('debug_id');

            if (!empty($checklist)) {
                // Handle single checklist string or array
                $checklistArray = is_array($checklist) ? $checklist : [$checklist];
                
                $saved = false;
                foreach ($checklistArray as $item) {
                    if(empty($item)) continue;
                    
                    $obj = new \App\Models\Document;
                    $obj->user_id = Auth::user()->id;
                    $obj->client_id = $partnerid;
                    $obj->type = 'partner';
                    $obj->doc_type = $doctype;
                    $obj->checklist = $item;
                    $saved = $obj->save();
                }

                if($saved) {
                    if($request->type == 'partner'){
                        $subject = 'added document checklist';
                        $objs = new \App\Models\ActivitiesLog;
                        $objs->client_id = $partnerid;
                        $objs->created_by = Auth::user()->id;
                        $objs->description = '';
                        $objs->subject = $subject;
                        $objs->task_group = 'partner';
                        $objs->task_status = 0;
                        $objs->pin = 0;
                        $objs->save();
                    }

                    $response['status'] = true;
                    $response['message'] = 'You have successfully added your document checklist';
                } else {
                    $response['status'] = false;
                    $response['message'] = 'Please try again';
                }
            } else {
                $response['status'] = false;
                $response['message'] = 'Checklist name is required';
            }
        } catch (\Exception $e) {
            Log::error('Error in addalldocchecklist (partner): ' . $e->getMessage() . ' at line ' . $e->getLine());
            $response = ['status' => false, 'message' => 'An error occurred: ' . $e->getMessage()];
        }
        echo json_encode($response);
    }

    //Upload all document for partners (with checklist support for bulk upload)
    public function uploadalldocument(Request $request){ 
        try {
            if ($request->hasfile('document_upload')) {
                $partnerid = $request->clientid;
                $partner_info = \App\Models\Partner::select('email')->where('id', $partnerid)->first();
                if(!empty($partner_info)){
                    $partner_unique_email = $partner_info->email;
                } else {
                    $partner_unique_email = "";
                }
                
                $doctype = isset($request->doctype) ? $request->doctype : 'documents';
                $checklist = $request->input('checklist');
                $debugId = $request->input('debug_id');
                
                $files = $request->file('document_upload');
                $size = $files->getSize();
                $fileName = $files->getClientOriginalName();
                $nameWithoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
                $fileExtension = $files->getClientOriginalExtension();
                $name = time() . $files->getClientOriginalName();
                $filePath = $partner_unique_email.'/'.$doctype.'/'. $name;

                Log::error('partners.uploadalldocument start', [
                    'debug_id' => $debugId,
                    'client_id' => $partnerid,
                    'doctype' => $doctype,
                    'checklist' => $checklist,
                    'fileid' => $request->fileid,
                    'has_file' => $request->hasfile('document_upload'),
                    'file_original_name' => $fileName,
                    'file_extension' => $fileExtension,
                    'user_id' => Auth::user()->id,
                ]);

                Storage::disk('s3')->put($filePath, file_get_contents($files));

                $req_file_id = $request->fileid;
                
                // If fileid provided, update existing entry
                if (!empty($req_file_id)) {
                    $obj = \App\Models\Document::query()->find($req_file_id);
                    if (!$obj) {
                        $response['status'] = false;
                        $response['message'] = 'Document not found';
                        Log::error('partners.uploadalldocument missing document', [
                            'debug_id' => $debugId,
                            'fileid' => $req_file_id,
                        ]);
                        echo json_encode($response);
                        return;
                    }
                    Log::error('partners.uploadalldocument update existing', [
                        'debug_id' => $debugId,
                        'document_id' => $obj->id,
                        'existing_checklist' => $obj->checklist,
                    ]);
                } else {
                    $obj = null;
                    if (!empty($checklist)) {
                        $obj = \App\Models\Document::query()->where('client_id', $partnerid)
                            ->where('type', 'partner')
                            ->where('doc_type', $doctype)
                            ->where('checklist', $checklist)
                            ->whereNull('not_used_doc')
                            ->whereNull('file_name')
                            ->first();
                    }

                    // Create new entry (for bulk upload with checklist)
                    if (!$obj) {
                        $obj = new \App\Models\Document;
                        if (!empty($checklist)) {
                            $obj->checklist = $checklist;
                        }
                        Log::error('partners.uploadalldocument create new', [
                            'debug_id' => $debugId,
                            'checklist' => $checklist,
                        ]);
                    } else {
                        Log::error('partners.uploadalldocument reuse checklist placeholder', [
                            'debug_id' => $debugId,
                            'document_id' => $obj->id,
                            'checklist' => $obj->checklist,
                        ]);
                    }
                }
                
                $obj->file_name = $nameWithoutExtension;
                $obj->filetype = $fileExtension;
                $obj->user_id = Auth::user()->id;
                $fileUrl = $this->s3Url($filePath);
                $obj->myfile = $fileUrl;
                $obj->myfile_key = $name;
                $obj->client_id = $partnerid;
                $obj->type = 'partner';
                $obj->file_size = $size;
                $obj->doc_type = $doctype;
                $saved = $obj->save();
                Log::error('partners.uploadalldocument saved', [
                    'debug_id' => $debugId,
                    'document_id' => $obj->id,
                    'saved' => (bool) $saved,
                    'final_checklist' => $obj->checklist,
                    'final_file_name' => $obj->file_name,
                ]);
                if ($saved) {
                    $dupes = \App\Models\Document::query()->where('client_id', $partnerid)
                        ->where('type', 'partner')
                        ->where('doc_type', $doctype)
                        ->where('file_name', $nameWithoutExtension)
                        ->whereNull('not_used_doc')
                        ->get(['id', 'checklist', 'file_name', 'created_at']);
                    if ($dupes->count() > 1) {
                        Log::error('partners.uploadalldocument duplicates detected', [
                            'debug_id' => $debugId,
                            'duplicate_count' => $dupes->count(),
                            'records' => $dupes->map(function ($row) {
                                return [
                                    'id' => $row->id,
                                    'checklist' => $row->checklist,
                                    'file_name' => $row->file_name,
                                    'created_at' => $row->created_at,
                                ];
                            })->values(),
                        ]);
                    }
                }

                if($saved){
                    if($request->type == 'partner'){
                        $subject = 'uploaded document';
                        $objs = new \App\Models\ActivitiesLog;
                        $objs->client_id = $partnerid;
                        $objs->created_by = Auth::user()->id;
                        $objs->description = '';
                        $objs->subject = $subject;
                        $objs->task_group = 'partner';
                        $objs->task_status = 0;
                        $objs->pin = 0;
                        $objs->save();
                    }
                    $response['status'] = true;
                    $response['message'] = 'You have successfully uploaded your document';
                } else {
                    $response['status'] = false;
                    $response['message'] = 'Please try again';
                }
            } else {
                $response['status'] = false;
                $response['message'] = 'Please try again';
            }
        } catch (\Throwable $e) {
            Log::error('partners.uploadalldocument exception', [
                'debug_id' => $request->input('debug_id'),
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            $response['status'] = false;
            $response['message'] = 'Upload failed: ' . $e->getMessage();
        }
        echo json_encode($response);
    }

    private function s3Url(string $path): string
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('s3');

        return $disk->url($path);
    }
    
}
