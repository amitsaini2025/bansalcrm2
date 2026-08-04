<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use App\Models\Admin;
use App\Models\Staff;
use App\Support\StaffClientVisibility;
use App\Services\ClientImportService;
use App\Services\ClientLeadListExportService;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use App\Helpers\PhoneHelper;

class LeadController extends Controller
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
     * All leads from admins table (type='lead').
     *
     * @return \Illuminate\Http\Response
     */
	public function index(Request $request)
	{
		//check authorization start	
			/* $check = $this->checkAuthorizationAction('lead_management', $request->route()->getActionMethod(), Auth::user()->role);
			if($check)
			{
				return redirect()->route('dashboard')->with('error',config('constants.unauthorized'));
			}*/	
		//check authorization end

		$baseQuery = Admin::where('type', 'lead')->where('converted', 0)
			->where('is_archived', 0)
			->where(function ($q) {
				$q->whereNull('is_deleted')->orWhere('is_deleted', 0);
			});
		$user = Auth::guard('admin')->user();
		if ($user instanceof Staff) {
			StaffClientVisibility::restrictAdminsQueryForStaff($baseQuery, $user);
		}

		$totalData = (clone $baseQuery)->count();
		$query = $this->buildLeadListQuery($request, $baseQuery);

		if ($request->has('id') || $request->has('email') || $request->has('name') || $request->has('phone') || $request->has('status') || $request->has('from') || $request->has('to'))
		{
			$totalData = $query->count();
		}
		$lists = $query->sortable(['id' => 'desc'])->paginate(config('constants.limit'));
		$cur_url = $request->fullUrl();
		return view('Admin.leads.index', compact(['lists', 'totalData', 'cur_url']));

	}

	/**
	 * Export filtered lead list as CSV.
	 */
	public function exportList(Request $request)
	{
		$user = Auth::guard('admin')->user();
		if (! $user instanceof Staff || ! $user->isSuperAdmin()) {
			return redirect()->route('leads.index')
				->with('error', config('constants.unauthorized'));
		}

		$baseQuery = Admin::where('type', 'lead')->where('converted', 0)
			->where('is_archived', 0)
			->where(function ($q) {
				$q->whereNull('is_deleted')->orWhere('is_deleted', 0);
			});
		$user = Auth::guard('admin')->user();
		if ($user instanceof Staff) {
			StaffClientVisibility::restrictAdminsQueryForStaff($baseQuery, $user);
		}

		$query = $this->buildLeadListQuery($request, $baseQuery);

		return app(ClientLeadListExportService::class)
			->export($query, 'lead', 'leads_export');
	}

	/**
	 * Build the lead list query with the same filters as the index page.
	 */
	protected function buildLeadListQuery(Request $request, Builder $baseQuery): Builder
	{
		$query = clone $baseQuery;

		if ($request->has('id')) {
			$lead_id = $request->input('id');
			if (trim($lead_id) != '') {
				$query->where(function ($q) use ($lead_id) {
					$q->where('lead_id', '=', $lead_id)->orWhere('id', '=', $lead_id);
				});
			}
		}
		if ($request->has('email')) {
			$email = $request->input('email');
			if (trim($email) != '') {
				// Match primary admins.email and related emails in client_emails (no join → no
				// row multiplication for count/paginate/export).
				$query->where(function ($q) use ($email) {
					$q->where('email', '=', $email);
					if (Schema::hasTable('client_emails')) {
						$q->orWhereIn('id', function ($sub) use ($email) {
							$sub->select('client_id')
								->from('client_emails')
								->where('client_email', '=', $email);
						});
					}
				});
			}
		}
		if ($request->has('name')) {
			$name = $request->input('name');
			if (trim($name) != '') {
				$query->where(DB::raw("COALESCE(first_name, '') || ' ' || COALESCE(last_name, '')"), 'ilike', '%' . $name . '%');
			}
		}
		if ($request->has('phone')) {
			$phone = $request->input('phone');
			if (trim($phone) != '') {
				// Match primary admins.phone and related numbers in client_phones (no join → no
				// row multiplication for count/paginate/export).
				$query->where(function ($q) use ($phone) {
					$q->where('phone', '=', $phone);
					if (Schema::hasTable('client_phones')) {
						$q->orWhereIn('id', function ($sub) use ($phone) {
							$sub->select('client_id')
								->from('client_phones')
								->where('client_phone', '=', $phone);
						});
					}
				});
			}
		}
		if ($request->has('status')) {
			$status = $request->input('status');
			if (trim($status) != '') {
				$query->where('status', '=', $status);
			}
		}
		if ($request->has('from')) {
			$from = $request->input('from');
			if (trim($from) != '') {
				$query->whereDate('created_at', '>=', $from);
			}
		}
		if ($request->has('to')) {
			$to = $request->input('to');
			if (trim($to) != '') {
				$query->whereDate('created_at', '<=', $to);
			}
		}

		return $query;
	}
	
	public function create(Request $request) 
	{
		//check authorization start	
			/* $check = $this->checkAuthorizationAction('add_lead', $request->route()->getActionMethod(), Auth::user()->role);
			if($check)
			{
				return redirect()->route('dashboard')->with('error',config('constants.unauthorized'));
			}*/	 
		//check authorization end
		
		return view('Admin.leads.create');
	}
	
	public function assign(Request $request) {
		$requestData 		= 	$request->all();
		$id = $this->decodeString($requestData['mlead_id']);	 
		// Admin model only: support migrated (lead_id) and admin-only (admins.id)
		$admin = Admin::where('lead_id', '=', $id)->where('type', 'lead')->first()
			?? Admin::where('id', '=', $id)->where('type', 'lead')->first();
		if ($admin) 
		{
			$currentAssignee = $admin->assignee;
			if($currentAssignee != '' && $currentAssignee != null){
				if($currentAssignee == $requestData['assignto']){
					return redirect()->back()->with('error', 'Already Assigned to this Staff');
				}
				$assignfrom = \App\Models\Staff::find($currentAssignee);
				$assignto = \App\Models\Staff::find($requestData['assignto']);
				if (!$assignfrom || !$assignto) {
					return redirect()->back()->with('error', 'Invalid assignee');
				}
			}
			Admin::where('id', $admin->id)->update(['assignee' => $requestData['assignto']]);
			$saved = true;
			if(!$saved) {
				return redirect()->back()->with('error', 'Please try again');
			}
			return redirect()->back()->with('success', 'Lead Assigned successfully');
			} else {
			return redirect()->back()->with('error', 'Not Found');
		}
	}
	
	 public function store(Request $request)
	{
		//check authorization start	
		/*$check = $this->checkAuthorizationAction('add_lead', $request->route()->getActionMethod(), Auth::user()->role);
		if($check)
		{
			return redirect()->route('dashboard')->with('error',config('constants.unauthorized'));
		}	*/ 
		//check authorization end
		if ($request->isMethod('post')) 
		{
			$this->validate($request, [
										'first_name' => 'required|max:255',
										'last_name' => 'required|max:255',
										'gender' => 'required|in:Male,Female,Other',
										'contact_type' => 'required|in:Personal,Office,Work,Mobile,Business,Secondary,Father,Mother,Brother,Sister,Uncle,Aunt,Cousin,Others,Partner,Not In Use',
									'phone' => 'required|max:255|unique:admins,phone',
										'email_type' => 'required|in:Personal,Work,Business,Secondary,Additional,Sister,Brother,Father,Mother,Uncle,Auntie',
										'email' => 'required|max:255|unique:admins,email',
										'service' => 'required',
										'assign_to' => 'required|array|min:1',
										'assign_to.*' => 'required|integer',
										'lead_quality' => 'required',
										'source' => 'required',
									
									  ]);
			
			$requestData 		= 	$request->all();
		
			$related_files = '';
	        if(isset($requestData['related_files'])){
	            for($i=0; $i<count($requestData['related_files']); $i++){
	                $related_files .= $requestData['related_files'][$i].',';
	            }
	        }
			$dob = '';
	        if(isset($requestData['dob']) && $requestData['dob'] != ''){
	           $dobs = explode('/', $requestData['dob']);
	          $dob = $dobs[2].'-'.$dobs[1].'-'. $dobs[0]; 
	        }
	        $visa_expiry_date = '';
	        if(isset($requestData['visa_expiry_date']) && $requestData['visa_expiry_date'] != ''){
	           $visa_expiry_dates = explode('/', $requestData['visa_expiry_date']);
	          $visa_expiry_date = $visa_expiry_dates[2].'-'.$visa_expiry_dates[1].'-'. $visa_expiry_dates[0]; 
	        }

			// Save to admins table only (type=lead), not to leads table
			$adminId = $this->createAdminFromRequestData($requestData, $dob, $visa_expiry_date, $related_files);
			if (!$adminId) {
				return redirect()->back()->with('error', Config::get('constants.server_error'));
			}

			return redirect()->route('leads.detail', base64_encode(convert_uuencode($adminId)))->with('success', 'Lead added Successfully'); 				
		}	
	} 
	
	/**
	 * Create Admin row from request data (saves to admins only, lead_id=null for new leads).
	 * @return int|null Admin id or null on failure
	 */
	protected function createAdminFromRequestData(array $requestData, string $dob, string $visa_expiry_date, string $related_files): ?int
	{
		if (!Schema::hasTable('admins')) {
			return null;
		}

		// Multi-select assign_to[]: store like clients (comma-separated when >1; single id when one).
		// office_id is single — always resolve from the first selected staff.
		$assignee = null;
		$firstAssigneeId = null;
		if (isset($requestData['assign_to']) && is_array($requestData['assign_to'])) {
			$assignIds = array_values(array_filter($requestData['assign_to'], static function ($id) {
				return $id !== null && $id !== '';
			}));
			$assignCount = count($assignIds);
			if ($assignCount > 1) {
				$assignee = implode(',', $assignIds);
				$firstAssigneeId = $assignIds[0];
			} elseif ($assignCount === 1) {
				$assignee = $assignIds[0];
				$firstAssigneeId = $assignIds[0];
			}
		} elseif (isset($requestData['assign_to']) && $requestData['assign_to'] !== '' && $requestData['assign_to'] !== null) {
			$assignee = $requestData['assign_to'];
			$firstAssigneeId = $requestData['assign_to'];
		}

		$officeId = null;
		if ($firstAssigneeId !== null && $firstAssigneeId !== '' && is_numeric($firstAssigneeId)) {
			$staff = \App\Models\Staff::find((int) $firstAssigneeId);
			$officeId = $staff ? $staff->office_id : null;
		}

		$age = isset($requestData['age']) && $requestData['age'] != '' ? preg_replace('/[^0-9]/', '', $requestData['age']) : null;
		$tags = '';
		if (isset($requestData['tagname']) && !empty($requestData['tagname']) && is_array($requestData['tagname'])) {
			$tags = implode(',', $requestData['tagname']);
		}
		$naatiPy = '';
		if (isset($requestData['naati_py']) && !empty($requestData['naati_py']) && is_array($requestData['naati_py'])) {
			$naatiPy = implode(',', $requestData['naati_py']);
		}

		$adminCols = Schema::getColumnListing('admins');
		$now = now();
		$data = [
			'type' => 'lead',
			'remember_token' => null,
			'lead_id' => null,
			'first_name' => $requestData['first_name'] ?? null,
			'last_name' => $requestData['last_name'] ?? null,
			'email' => trim((string) ($requestData['email'] ?? '')),
			'password' => bcrypt(Str::random(32)),
			'phone' => $requestData['phone'] ?? null,
			'country_code' => PhoneHelper::normalizeCountryCode($requestData['country_code'] ?? null),
			'gender' => $requestData['gender'] ?? null,
			'dob' => ($dob != '') ? $dob : null,
			'marital_status' => $requestData['marital_status'] ?? null,
			'address' => $requestData['address'] ?? null,
			'city' => $requestData['city'] ?? null,
			'state' => $requestData['state'] ?? null,
			'zip' => $requestData['zip'] ?? null,
			'country' => $requestData['country'] ?? null,
			'user_id' => Auth::user()->id,
			'assignee' => $assignee,
			'office_id' => $officeId,
			'source' => $requestData['source'] ?? null,
			'tags' => $tags,
			'passport_number' => $requestData['passport_no'] ?? null,
			'visaexpiry' => ($visa_expiry_date != '') ? $visa_expiry_date : null,
			'visa_type' => $requestData['visa_type'] ?? null,
			'nomi_occupation' => $requestData['nomi_occupation'] ?? null,
			'skill_assessment' => $requestData['skill_assessment'] ?? null,
			'high_quali_aus' => $requestData['high_quali_aus'] ?? null,
			'high_quali_overseas' => $requestData['high_quali_overseas'] ?? null,
			'relevant_work_exp_aus' => $requestData['relevant_work_exp_aus'] ?? null,
			'relevant_work_exp_over' => $requestData['relevant_work_exp_over'] ?? null,
			'naati_py' => $naatiPy,
			'married_partner' => $requestData['married_partner'] ?? null,
			'total_points' => $requestData['total_points'] ?? null,
			'comments_note' => $requestData['comments_note'] ?? null,
			'service' => $requestData['service'] ?? null,
			'lead_quality' => $requestData['lead_quality'] ?? null,
			'country_passport' => $requestData['country_passport'] ?? null,
			'contact_type' => $requestData['contact_type'] ?? null,
			'email_type' => $requestData['email_type'] ?? null,
			'related_files' => rtrim($related_files, ','),
			'status' => $requestData['status'] ?? 0,
			'verified' => 0,
			'is_archived' => 0,
			'show_dashboard_per' => 0,
			'created_at' => $now,
			'updated_at' => $now,
			'converted' => 0,
			'is_verified' => 0,
			'verified_at' => null,
			'verified_by' => null,
		];

		$data = array_intersect_key($data, array_flip($adminCols));
		$adminId = DB::table('admins')->insertGetId($data);

		$firstName = substr((string) ($requestData['first_name'] ?? ''), 0, 4);
		$clientId = strtoupper(preg_replace('/[^A-Za-z]/', '', $firstName) ?: 'LEAD') . date('ym') . $adminId;
		if (in_array('client_id', $adminCols, true)) {
			DB::table('admins')->where('id', $adminId)->update(['client_id' => $clientId]);
		}

		return (int) $adminId;
	}

	/* REMOVED: Broken edit method - Leads now use the detail page (ClientsController@detail) for viewing and editing
	 * The detail page provides a much richer interface with tabs for notes, activities, documents, etc.
	 * This old edit method had issues:
	 * - Missing CSRF token handling
	 * - No proper method specification in form
	 * - Route mismatch between GET and POST
	 * - Form submission failures (405 errors)
	 * 
	 * To edit a lead, users should now click on the lead to open the detail page.
	 */ 
	
	public function convertoClient(Request $request, $id = null)
	{ 
		$requestData = $request->all();
		// Use Admin model only: iterate migrated leads (admins with lead_id set)
		$admins = Admin::where('type', 'lead')->whereNotNull('lead_id')->paginate(500);
		foreach ($admins as $admin) {
			$leadId = $admin->lead_id;
			// Sync timestamps from leads table if it exists (using DB, not Lead model)
			if (Schema::hasTable('leads')) {
				$leadRow = DB::table('leads')->where('id', $leadId)->first();
				if ($leadRow) {
					Admin::where('id', $admin->id)->update([
						'created_at' => $leadRow->created_at,
						'updated_at' => $leadRow->updated_at,
					]);
				}
			}
				echo $leadId . '<br>';
		}
	}
	
	//Check Email is unique or not
    public function is_email_unique(Request $request){
        $email = $request->email;
        // Admins table holds both clients and leads - single count
        $email_count = \App\Models\Admin::where('email',$email)->count();
        if($email_count >0){
            $response['status'] 	= 	1;
            $response['message']	=	"The email has already been taken.";
        }else{
            $response['status'] 	= 	0;
            $response['message']	=	"";
        }
        echo json_encode($response);
    }

    //Check Contact no is unique or not
    public function is_contactno_unique(Request $request){
        $contact = $request->contact;
        // Admins table holds both clients and leads - single count
        $phone_count = \App\Models\Admin::where('phone',$contact)->count();
        if($phone_count >0){
            $response['status'] 	= 	1;
            $response['message']	=	"The phone has already been taken.";
        }else{
            $response['status'] 	= 	0;
            $response['message']	=	"";
        }
        echo json_encode($response);
    }

    /**
     * Import lead data from JSON file
     */
    public function import(Request $request)
    {
        try {
            $request->validate([
                'import_file' => 'required|file|mimes:json|max:10240',
            ]);

            $file = $request->file('import_file');
            $jsonContent = file_get_contents($file->getRealPath());
            $importData = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()
                    ->withErrors(['import_file' => 'Invalid JSON file: ' . json_last_error_msg()])
                    ->withInput();
            }

            if (!isset($importData['client'])) {
                return redirect()->back()
                    ->withErrors(['import_file' => 'Invalid import file format: missing client data'])
                    ->withInput();
            }

            if (empty($importData['client']['email'])) {
                return redirect()->back()
                    ->withErrors(['import_file' => 'Lead email is required and cannot be empty'])
                    ->withInput();
            }

            if (empty($importData['client']['first_name'])) {
                return redirect()->back()
                    ->withErrors(['import_file' => 'Lead first name is required'])
                    ->withInput();
            }

            // Force type=lead when importing from Leads page
            $importData['client']['type'] = 'lead';

            $skipDuplicates = $request->has('skip_duplicates');
            $importService = app(ClientImportService::class);
            $result = $importService->importClient($importData, $skipDuplicates);

            if ($result['success']) {
                return redirect()->route('leads.index')
                    ->with('success', $result['message']);
            } else {
                return redirect()->back()
                    ->withErrors(['import_file' => $result['message']])
                    ->withInput();
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = $e->validator->errors()->first();
            return redirect()->back()
                ->withErrors(['import_file' => $firstError])
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Lead import error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $request->file('import_file') ? $request->file('import_file')->getClientOriginalName() : 'unknown'
            ]);

            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'file_get_contents') !== false || strpos($errorMessage, 'failed to open stream') !== false) {
                $errorMessage = 'File error: Could not read the uploaded file. Please ensure the file is not corrupted and try again.';
            } elseif (strpos($errorMessage, 'json_decode') !== false) {
                $errorMessage = 'JSON error: The file is not a valid JSON file. Please check the file format.';
            } elseif (strpos($errorMessage, 'mimes') !== false || strpos($errorMessage, 'mime type') !== false) {
                $errorMessage = 'File type error: Please upload a valid JSON file (.json extension).';
            } elseif (strpos($errorMessage, 'max:') !== false) {
                $errorMessage = 'File size error: The file is too large. Maximum file size is 10MB.';
            }

            return redirect()->back()
                ->withErrors(['import_file' => $errorMessage])
                ->withInput();
        }
    }
}
