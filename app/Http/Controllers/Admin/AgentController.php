<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Imports\ImportAgent;
use App\Models\Admin;
use App\Models\Agent;
use Config;
use Illuminate\Http\Request;
// NOTE: RepresentingPartner model and table have been removed
// use App\Models\RepresentingPartner;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Facades\Excel;

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
     * @return Response
     */
    public function active(Request $request)
    {
        $query = Agent::where('is_acrchived', '=', 0);
        $this->applyAgentListFilters($query, $request);

        $totalData = $query->count();
        $lists = $query->sortable(['id' => 'desc'])->paginate(config('constants.limit'));

        return view('Admin.agents.active', compact(['lists', 'totalData']));
    }

    public function inactive(Request $request)
    {
        $query = Agent::where('is_acrchived', '=', 1);
        $this->applyAgentListFilters($query, $request);

        $totalData = $query->count();
        $lists = $query->sortable(['id' => 'desc'])->paginate(config('constants.limit'));

        return view('Admin.agents.inactive', compact(['lists', 'totalData']));
    }

    /**
     * Apply list filters for agent Active/Inactive pages.
     */
    private function applyAgentListFilters($query, Request $request): void
    {
        $searchBy = trim((string) $request->input('search_by', ''));
        if ($searchBy !== '') {
            $like = '%'.$searchBy.'%';
            $query->where(function ($q) use ($like) {
                $q->where('full_name', 'ILIKE', $like)
                    ->orWhere('email', 'ILIKE', $like)
                    ->orWhere('phone', 'ILIKE', $like);
            });
        }

        $agentType = trim((string) $request->input('agent_type', ''));
        if (in_array($agentType, ['Super Agent', 'Sub Agent'], true)) {
            // agent_type is stored as comma-separated values (e.g. "Super Agent,Sub Agent")
            $query->where('agent_type', 'ILIKE', '%'.$agentType.'%');
        }

        $structure = trim((string) $request->input('struture', ''));
        if (in_array($structure, ['Individual', 'Business'], true)) {
            $query->where('struture', '=', $structure);
        }
    }

    public function create(Request $request)
    {
        return view('Admin.agents.create');
    }

    public function store(Request $request)
    {
        // check authorization end
        if ($request->isMethod('post')) {
            // Base validation rules
            $validationRules = [
                'email' => 'required|email',
                'related_office' => 'required',
                'agent_type' => 'required|array|min:1',
                'agent_type.*' => 'in:Super Agent,Sub Agent',
                'struture' => 'required|in:Individual,Business',
            ];

            // Conditional validation based on structure
            if ($request->input('struture') == 'Individual') {
                $validationRules['full_name'] = 'required|string|max:255';
            } else {
                $validationRules['business_name'] = 'required|string|max:255';
                $validationRules['c_name'] = 'required|string|max:255';
            }

            $this->validate($request, $validationRules);

            $requestData = $request->all();

            $obj = new Agent;
            // Safely handle agent_type - ensure it's an array before imploding
            $agentType = isset($requestData['agent_type']) && is_array($requestData['agent_type'])
                ? $requestData['agent_type']
                : [];
            $obj->agent_type = ! empty($agentType) ? implode(',', $agentType) : null;
            $obj->struture = @$requestData['struture'];
            if (@$requestData['struture'] == 'Individual') {
                $obj->full_name = @$requestData['full_name'];
                // Set contract_expiry_date for Individual agents - use provided value or default to far future date
                $obj->contract_expiry_date = ! empty(@$requestData['contract_expiry_date']) ? @$requestData['contract_expiry_date'] : '2099-12-31';
            } else {
                $obj->full_name = @$requestData['c_name'];
                $obj->business_name = @$requestData['business_name'];
                $obj->tax_number = @$requestData['tax_number'];
                $obj->contract_expiry_date = @$requestData['contract_expiry_date'];
            }

            $obj->email = @$requestData['email'];
            $obj->country_code = PhoneHelper::normalizeCountryCode(@$requestData['country_code']);
            $obj->phone = @$requestData['phone'];
            $obj->address = @$requestData['address'];
            $obj->city = @$requestData['city'];
            $obj->state = @$requestData['state'];
            $obj->zip = @$requestData['zip'];
            $obj->country = @$requestData['country'];
            $obj->related_office = @$requestData['related_office'];
            $obj->income_sharing = @$requestData['income_sharing'];
            $obj->claim_revenue = @$requestData['claim_revenue'];

            // profile_img column removed from admins table
            $obj->status = 1;
            $obj->is_acrchived = 0; // Set is_acrchived to 0 (not archived) for new agents

            $saved = $obj->save();

            if (! $saved) {
                return redirect()->back()->with('error', Config::get('constants.server_error'));
            } else {
                return redirect()->route('agents.active')->with('success', 'Agents Added Successfully');
            }
        }

        return view('Admin.agents.create');
    }

    public function edit(Request $request, $id = null)
    {

        // check authorization end

        if ($request->isMethod('post')) {
            $requestData = $request->all();

            // Base validation rules
            $validationRules = [
                'email' => 'required|email',
                'related_office' => 'required',
                'id' => 'required|exists:agents,id',
                'agent_type' => 'required|array|min:1',
                'agent_type.*' => 'in:Super Agent,Sub Agent',
                'struture' => 'required|in:Individual,Business',
            ];

            // Conditional validation based on structure
            if ($request->input('struture') == 'Individual') {
                $validationRules['full_name'] = 'required|string|max:255';
            } else {
                $validationRules['business_name'] = 'required|string|max:255';
                $validationRules['c_name'] = 'required|string|max:255';
            }

            $this->validate($request, $validationRules);

            $obj = Agent::find(@$requestData['id']);
            if (! $obj) {
                return redirect()->back()->with('error', 'Agent not found');
            }

            // Safely handle agent_type - ensure it's an array before imploding
            $agentType = isset($requestData['agent_type']) && is_array($requestData['agent_type'])
                ? $requestData['agent_type']
                : [];
            $obj->agent_type = ! empty($agentType) ? implode(',', $agentType) : null;
            $obj->struture = @$requestData['struture'];
            if (@$requestData['struture'] == 'Individual') {
                $obj->full_name = @$requestData['full_name'];
                // Set contract_expiry_date for Individual agents - use provided value or default to far future date
                $obj->contract_expiry_date = ! empty(@$requestData['contract_expiry_date']) ? @$requestData['contract_expiry_date'] : '2099-12-31';
            } else {
                $obj->full_name = @$requestData['c_name'];
                $obj->business_name = @$requestData['business_name'];
                $obj->tax_number = @$requestData['tax_number'];
                $obj->contract_expiry_date = @$requestData['contract_expiry_date'];
            }

            $obj->email = @$requestData['email'];
            $obj->country_code = PhoneHelper::normalizeCountryCode(@$requestData['country_code']);
            $obj->phone = @$requestData['phone'];
            $obj->address = @$requestData['address'];
            $obj->city = @$requestData['city'];
            $obj->state = @$requestData['state'];
            $obj->zip = @$requestData['zip'];
            $obj->country = @$requestData['country'];
            $obj->related_office = @$requestData['related_office'];
            $obj->income_sharing = @$requestData['income_sharing'];
            $obj->claim_revenue = @$requestData['claim_revenue'];

            // profile_img column removed from admins table
            $saved = $obj->save();

            if (! $saved) {
                return redirect()->back()->with('error', Config::get('constants.server_error'));
            } else {
                return redirect()->route('agents.active')->with('success', 'Agents Edited Successfully');
            }
        } else {
            if (isset($id) && ! empty($id)) {

                $id = $this->decodeString($id);
                if (Agent::where('id', '=', $id)->exists()) {
                    $fetchedData = Agent::find($id);

                    return view('Admin.agents.edit', compact(['fetchedData']));
                } else {
                    return redirect()->route('agents.active')->with('error', 'Agents Not Exist');
                }
            } else {
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

    public function detail(Request $request, $id = null)
    {
        if (isset($id) && ! empty($id)) {
            $id = $this->decodeString($id);
            if (Agent::where('id', '=', $id)->exists()) {
                $fetchedData = Agent::find($id);

                return view('Admin.agents.detail', compact(['fetchedData']));
            } else {
                return redirect()->route('agents.active')->with('error', 'Agents Not Exist');
            }
        } else {
            return redirect()->route('agents.active')->with('error', Config::get('constants.unauthorized'));
        }
    }

    public function businessimport(Request $request)
    {
        if ($request->isMethod('post')) {

            Excel::import(new ImportAgent($request),
                $request->file('uploadfile')->store('files'));

            return redirect()->back()->with('success', 'Agents Imported successfully');
        } else {
            return view('Admin.agents.importbusiness');
        }
    }

    public function individualimport(Request $request)
    {
        if ($request->isMethod('post')) {
            Excel::import(new ImportAgent($request),
                $request->file('uploadfile')->store('files'));

            return redirect()->back()->with('success', 'Agents Imported successfully');
        } else {
            return view('Admin.agents.importindividual');
        }
    }
}
