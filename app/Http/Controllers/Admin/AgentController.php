<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use App\Imports\ImportAgent;
use App\Models\Admin;
use App\Models\Agent;
use Maatwebsite\Excel\Facades\Excel;
// NOTE: RepresentingPartner model and table have been removed
// use App\Models\RepresentingPartner;
 
use Auth;
use Config;
use App\Helpers\PhoneHelper;

class AgentController extends Controller
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
	public function active(Request $request)
	{
		$query 		= Agent::where('is_acrchived', '=', 0);  
		 
		$totalData 	= $query->count();	//for all data
		
		$lists		= $query->sortable(['id' => 'desc'])->paginate(config('constants.limit'));
		
		return view('Admin.agents.active',compact(['lists', 'totalData']));  
	}
	public function inactive(Request $request)
	{
		$query 		= Agent::where('is_acrchived', '=', 1); 
		 
		$totalData 	= $query->count();	//for all data
		
		$lists		= $query->sortable(['id' => 'desc'])->paginate(config('constants.limit'));
		
		return view('Admin.agents.inactive',compact(['lists', 'totalData']));  
	}
	
	public function create(Request $request){
		return view('Admin.agents.create');	
	}
	
	public function store(Request $request)
	{		
		//check authorization end
		if ($request->isMethod('post')) 
		{
			// Base validation rules
			$validationRules = [
				'email' => 'required|email',
				'related_office' => 'required',
				'agent_type' => 'required|array|min:1',
				'agent_type.*' => 'in:Super Agent,Sub Agent',
				'struture' => 'required|in:Individual,Business'
			];
			
			// Conditional validation based on structure
			if($request->input('struture') == 'Individual') {
				$validationRules['full_name'] = 'required|string|max:255';
			} else {
				$validationRules['business_name'] = 'required|string|max:255';
				$validationRules['c_name'] = 'required|string|max:255';
			}
			
			$this->validate($request, $validationRules);
			
			$requestData 		= 	$request->all();
			 
			$obj				= 	new Agent; 
			// Safely handle agent_type - ensure it's an array before imploding
			$agentType = isset($requestData['agent_type']) && is_array($requestData['agent_type']) 
				? $requestData['agent_type'] 
				: [];
			$obj->agent_type	=	!empty($agentType) ? implode(',', $agentType) : null;
			$obj->struture	=	@$requestData['struture'];
			if(@$requestData['struture'] == 'Individual'){
			$obj->full_name	=	@$requestData['full_name'];
			// Set contract_expiry_date for Individual agents - use provided value or default to far future date
			$obj->contract_expiry_date	=	!empty(@$requestData['contract_expiry_date']) ? @$requestData['contract_expiry_date'] : '2099-12-31';
			}else{
				$obj->full_name	=	@$requestData['c_name'];
				$obj->business_name	=	@$requestData['business_name'];
				$obj->tax_number	=	@$requestData['tax_number']; 
		    	$obj->contract_expiry_date	=	@$requestData['contract_expiry_date'];
			}
		
			$obj->email	=	@$requestData['email'];
			$obj->country_code	=	PhoneHelper::normalizeCountryCode(@$requestData['country_code']);
			$obj->phone	=	@$requestData['phone'];
			$obj->address	=	@$requestData['address'];
			$obj->city	=	@$requestData['city'];
			$obj->state	=	@$requestData['state'];
			$obj->zip	=	@$requestData['zip'];
			$obj->country	=	@$requestData['country'];
			$obj->related_office	=	@$requestData['related_office'];
			$obj->income_sharing	=	@$requestData['income_sharing'];
			$obj->claim_revenue	=	@$requestData['claim_revenue'];
			
			// profile_img column removed from admins table
			$obj->status				=	1;
			$obj->is_acrchived	=	0; // Set is_acrchived to 0 (not archived) for new agents
			
			$saved				=	$obj->save();  
			
			if(!$saved)
			{
				return redirect()->back()->with('error', Config::get('constants.server_error'));
			}
			else
			{
				return redirect()->route('agents.active')->with('success', 'Agents Added Successfully');
			}				
		}	

		return view('Admin.agents.create');	 
	}
	
	public function edit(Request $request, $id = NULL)
	{
	
		//check authorization end
		
		if ($request->isMethod('post')) 
		{
			$requestData 		= 	$request->all();
			
			// Base validation rules
			$validationRules = [
				'email' => 'required|email',
				'related_office' => 'required',
				'id' => 'required|exists:agents,id',
				'agent_type' => 'required|array|min:1',
				'agent_type.*' => 'in:Super Agent,Sub Agent',
				'struture' => 'required|in:Individual,Business'
			];
			
			// Conditional validation based on structure
			if($request->input('struture') == 'Individual') {
				$validationRules['full_name'] = 'required|string|max:255';
			} else {
				$validationRules['business_name'] = 'required|string|max:255';
				$validationRules['c_name'] = 'required|string|max:255';
			}
			
			$this->validate($request, $validationRules);
								  					  
			$obj				= 	Agent::find(@$requestData['id']);
			if (!$obj) {
				return redirect()->back()->with('error', 'Agent not found');
			}
			
			// Safely handle agent_type - ensure it's an array before imploding
			$agentType = isset($requestData['agent_type']) && is_array($requestData['agent_type']) 
				? $requestData['agent_type'] 
				: [];
			$obj->agent_type	=	!empty($agentType) ? implode(',', $agentType) : null;
			$obj->struture	=	@$requestData['struture'];
			if(@$requestData['struture'] == 'Individual'){
			$obj->full_name	=	@$requestData['full_name'];
			// Set contract_expiry_date for Individual agents - use provided value or default to far future date
			$obj->contract_expiry_date	=	!empty(@$requestData['contract_expiry_date']) ? @$requestData['contract_expiry_date'] : '2099-12-31';
			}else{
				$obj->full_name	=	@$requestData['c_name'];
				$obj->business_name	=	@$requestData['business_name'];
			$obj->tax_number	=	@$requestData['tax_number']; 
			$obj->contract_expiry_date	=	@$requestData['contract_expiry_date'];
			}
			
			$obj->email	=	@$requestData['email'];
			$obj->country_code	=	PhoneHelper::normalizeCountryCode(@$requestData['country_code']);
			$obj->phone	=	@$requestData['phone'];
			$obj->address	=	@$requestData['address'];
			$obj->city	=	@$requestData['city'];
			$obj->state	=	@$requestData['state'];
			$obj->zip	=	@$requestData['zip'];
			$obj->country	=	@$requestData['country'];
			$obj->related_office	=	@$requestData['related_office'];
			$obj->income_sharing	=	@$requestData['income_sharing'];
			$obj->claim_revenue	=	@$requestData['claim_revenue'];
			
		// profile_img column removed from admins table
			$saved							=	$obj->save();
			
			if(!$saved)
			{
				return redirect()->back()->with('error', Config::get('constants.server_error'));
			}
			
			else
			{
				return redirect()->route('agents.active')->with('success', 'Agents Edited Successfully');
			}				
		}

		else
		{		
			if(isset($id) && !empty($id))
			{
				
				$id = $this->decodeString($id);	
				if(Agent::where('id', '=', $id)->exists()) 
				{
					$fetchedData = Agent::find($id);
					return view('Admin.agents.edit', compact(['fetchedData']));
				}
				else 
				{
					return redirect()->route('agents.active')->with('error', 'Agents Not Exist');
				}	
			}
			else
			{
				return redirect()->route('agents.active')->with('error', Config::get('constants.unauthorized'));
			}		
		} 	
		
	}
	
	/* public function show(Request $request, $id = NULL){
		if(isset($id) && !empty($id)) 
			{
				$id = $this->decodeString($id);	
				if(User::where('id', '=', $id)->exists()) 
				{
					$fetchedData = User::where('id',$id)->first();
					
					return view('Admin.agents.show', compact(['fetchedData']));
				}
				else
				{
					return redirect()->route('agents.active')->with('error', 'Agent Not Exist');
				}	
			}
			else
			{
				return redirect()->route('agents.active')->with('error', Config::get('constants.unauthorized'));
			}
	} */ 
 
	public function detail(Request $request, $id = NULL){
		if(isset($id) && !empty($id)) { 
			$id = $this->decodeString($id);	
			if(Agent::where('id', '=', $id)->exists()) 
			{
				$fetchedData = Agent::find($id);
				return view('Admin.agents.detail', compact(['fetchedData']));
			}
			else 
			{
				return redirect()->route('agents.active')->with('error', 'Agents Not Exist');
			}	
		}
		else
		{
			return redirect()->route('agents.active')->with('error', Config::get('constants.unauthorized'));
		}
	}
	
	public function businessimport(Request $request){
		if ($request->isMethod('post')) 
		{
			
			 Excel::import(new ImportAgent($request), 
                     $request->file('uploadfile')->store('files'));
			return redirect()->back()->with('success', 'Agents Imported successfully');
		}else{
			return view('Admin.agents.importbusiness');
		}
	}
	
	public function individualimport(Request $request){
		if ($request->isMethod('post')) 
		{
			Excel::import(new ImportAgent($request), 
                     $request->file('uploadfile')->store('files'));
			return redirect()->back()->with('success', 'Agents Imported successfully');
		}else{
			return view('Admin.agents.importindividual');
		}
	}
}
