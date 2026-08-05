<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\File; 

use App\Models\Admin;
use App\Models\Application;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Staff;
use App\Exports\PaidInvoicesExport;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
// NOTE: Item and AttachFile models/tables have been removed
// use App\Models\Item;
use App\Models\InvoiceDetail;
use App\Models\InvoicePayment;
// NOTE: ScheduleItem and InvoiceSchedule models have been removed
// use App\Models\ScheduleItem;
// NOTE: InvoiceFollowup is used in CronJob.php for audit logging
// use App\Models\InvoiceFollowup;
use App\Models\EmailTemplate;
use App\Models\ShareInvoice;
// use App\Models\InvoiceSchedule;
// NOTE: TaxRate model/table has been removed
// use App\Models\TaxRate;
// use App\Models\AttachFile;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Auth; 
use Config;

class InvoiceController extends Controller
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
	public function createInvoice(Request $request){
		// NOTE: schedule_id parameter removed - Invoice Schedule feature has been removed
		return redirect()->to('/application/invoice/'.$request->client_id.'/'.$request->application.'/'.$request->invoice_type);
	} 
	public function getInvoice(Request $request, $clientid, $applicationid, $type){
		//dd($type);
		$clientdata = \App\Models\Admin::where('id', $clientid)->first();
		if(!$clientdata){
			return redirect()->back()->with('error', 'Client not found for this invoice.');
		}
		if($type == 3){
			$workflowdaa = \App\Models\Workflow::where('id', $applicationid)->first();
			return view('Admin.invoice.general-invoice',compact(['clientid','applicationid','type','clientdata','workflowdaa'])); 
		}else{ 
			$applicationdata = \App\Models\Application::where('id', $applicationid)->first();
			if(!$applicationdata){
				return redirect()->back()->with('error', 'Application not found for this invoice.');
			}
    		$partnerdata = \App\Models\Partner::where('id', @$applicationdata->partner_id)->first();
    		$productdata = \App\Models\Product::where('id', @$applicationdata->product_id)->first();
    		$branchdata = \App\Models\PartnerBranch::where('id', @$applicationdata->branch)->first();
    		$workflowdaa = \App\Models\Workflow::where('id', @$applicationdata->workflow)->first();
			return view('Admin.invoice.commission-invoice',compact(['clientid','applicationid','type','applicationdata','partnerdata','workflowdaa','clientdata','productdata','branchdata'])); 
		}
	}
	
	public function unpaid(Request $request)
	{
		
		$query 		= Invoice::where('status', '=', 0);
		$lists		= $query->orderby('id','desc')->paginate(20);
		return view('Admin.invoice.unpaid',compact(['lists'])); 

	} 
	
	public function paid(Request $request)
	{
		$query = $this->buildPaidInvoicesQuery($request);
		$lists = $query->orderby('id', 'desc')->paginate(20)->withQueryString();
		$partners = Partner::query()
			->select('id', 'partner_name')
			->orderBy('partner_name')
			->get();

		return view('Admin.invoice.paid', compact('lists', 'partners'));
	}

	/**
	 * Export paid invoices (CSV or XLSX) using the same filters as the list page.
	 */
	public function exportPaidInvoices(Request $request)
	{
		$format = strtolower(trim((string) $request->input('format', 'csv')));
		$isXlsx = in_array($format, ['xlsx', 'excel'], true);
		$exportHeaders = $this->paidInvoiceExportHeaders();
		$baseFilename = 'Paid_Invoices_'.date('Y-m-d');

		$invoiceIds = $this->buildPaidInvoicesQuery($request)
			->orderBy('id', 'desc')
			->pluck('id');

		if ($isXlsx) {
			$rows = $invoiceIds->isEmpty()
				? []
				: $this->buildPaidInvoiceExportRows($invoiceIds);

			return Excel::download(
				new PaidInvoicesExport($rows, $exportHeaders),
				$baseFilename.'.xlsx'
			);
		}

		$filename = $baseFilename.'.csv';
		$headers = [
			'Content-Type'        => 'text/csv; charset=UTF-8',
			'Content-Disposition' => 'attachment; filename="'.$filename.'"',
		];

		$callback = function () use ($invoiceIds, $exportHeaders) {
			$handle = fopen('php://output', 'w');
			fputcsv($handle, $exportHeaders);

			if ($invoiceIds->isEmpty()) {
				fclose($handle);
				return;
			}

			foreach ($invoiceIds->chunk(200) as $chunk) {
				$rows = $this->buildPaidInvoiceExportRows($chunk);
				foreach ($rows as $row) {
					fputcsv($handle, $row);
				}
			}

			fclose($handle);
		};

		return response()->stream($callback, 200, $headers);
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	private function buildPaidInvoicesQuery(Request $request)
	{
		$query = Invoice::query()->where('status', 1);

		if ($request->filled('issue_date_from')) {
			$from = $this->parsePaidInvoiceFilterDate($request->issue_date_from);
			if ($from) {
				$query->whereDate('invoice_date', '>=', $from->format('Y-m-d'));
			}
		}

		if ($request->filled('issue_date_to')) {
			$to = $this->parsePaidInvoiceFilterDate($request->issue_date_to);
			if ($to) {
				$query->whereDate('invoice_date', '<=', $to->format('Y-m-d'));
			}
		}

		if ($request->filled('partner_id')) {
			$partnerId = (int) $request->partner_id;
			$applicationIds = Application::query()
				->where('partner_id', $partnerId)
				->pluck('id');

			if ($applicationIds->isEmpty()) {
				$query->whereRaw('1 = 0');
			} else {
				$query->whereIn('application_id', $applicationIds);
			}
		}

		return $query;
	}

	private function parsePaidInvoiceFilterDate(?string $value): ?Carbon
	{
		if (empty($value)) {
			return null;
		}

		try {
			return Carbon::createFromFormat('d/m/Y', trim($value));
		} catch (\Exception $e) {
			try {
				return Carbon::parse($value);
			} catch (\Exception $e2) {
				return null;
			}
		}
	}

	/**
	 * @return array<int, string>
	 */
	private function paidInvoiceExportHeaders(): array
	{
		return [
			'No',
			'Issue Date',
			'Client Name',
			'Created By',
			'Partner Name',
			'Product',
			'Amount',
			'Commission Claimed',
			'GST',
			'Net Fee Paid to Partner',
			'Client Reference',
			'Assignee',
		];
	}

	/**
	 * @param  \Illuminate\Support\Collection<int, int|string>  $invoiceIds
	 * @return array<int, array<int, string>>
	 */
	private function buildPaidInvoiceExportRows($invoiceIds): array
	{
		$rows = [];

		$invoices = Invoice::query()
			->whereIn('id', $invoiceIds->all())
			->orderBy('id', 'desc')
			->get();

		$clientIds = $invoices->pluck('client_id')->filter()->unique()->values();
		$staffIds = $invoices->pluck('user_id')->filter()->unique()->values();
		$applicationIds = $invoices->where('type', '!=', 3)->pluck('application_id')->filter()->unique()->values();

		$clients = Admin::query()->whereIn('id', $clientIds)->get()->keyBy('id');
		$staffMembers = Staff::query()->whereIn('id', $staffIds)->get()->keyBy('id');
		$applications = Application::query()->whereIn('id', $applicationIds)->get()->keyBy('id');
		$partnerIds = $applications->pluck('partner_id')->filter()->unique()->values();
		$productIds = $applications->pluck('product_id')->filter()->unique()->values();
		$assigneeIds = $applications->pluck('user_id')->filter()->unique()->values();

		$partners = Partner::query()->whereIn('id', $partnerIds)->get()->keyBy('id');
		$products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');
		$assignees = Staff::query()->whereIn('id', $assigneeIds)->get()->keyBy('id');

		$invoiceDetails = InvoiceDetail::query()
			->whereIn('invoice_id', $invoiceIds->all())
			->orderBy('id')
			->get()
			->groupBy('invoice_id');

		foreach ($invoices as $invoice) {
			$rows[] = $this->formatPaidInvoiceExportRow(
				$invoice,
				$clients,
				$staffMembers,
				$applications,
				$partners,
				$products,
				$assignees,
				$invoiceDetails->get($invoice->id, collect())
			);
		}

		return $rows;
	}

	/**
	 * @param  \Illuminate\Support\Collection<int, \App\Models\Admin>  $clients
	 * @param  \Illuminate\Support\Collection<int, \App\Models\Staff>  $staffMembers
	 * @param  \Illuminate\Support\Collection<int, \App\Models\Application>  $applications
	 * @param  \Illuminate\Support\Collection<int, \App\Models\Partner>  $partners
	 * @param  \Illuminate\Support\Collection<int, \App\Models\Product>  $products
	 * @param  \Illuminate\Support\Collection<int, \App\Models\Staff>  $assignees
	 * @param  \Illuminate\Support\Collection<int, \App\Models\InvoiceDetail>  $invoiceitemdetails
	 * @return array<int, string>
	 */
	private function formatPaidInvoiceExportRow(
		Invoice $invoicelist,
		$clients,
		$staffMembers,
		$applications,
		$partners,
		$products,
		$assignees,
		$invoiceitemdetails
	): array {
		$clientdata = $clients->get($invoicelist->client_id);
		$admindata = $staffMembers->get($invoicelist->user_id);
		$partnerdata = null;
		$productdata = null;
		$assignedTo = null;

		if ((int) $invoicelist->type !== 3) {
			$applicationdata = $applications->get($invoicelist->application_id);
			if ($applicationdata) {
				$partnerdata = $partners->get($applicationdata->partner_id);
				$productdata = $products->get($applicationdata->product_id);
				$assignedTo = $applicationdata->user_id
					? $assignees->get($applicationdata->user_id)
					: null;
			}
		}

		$fees = Invoice::sumLineFeeTotals($invoiceitemdetails);
		$coom_amt = $fees['coom_amt'];
		$tax_amt = $fees['tax_amt'];
		$feepaid = $fees['feepaid'];
		$issueDate = $invoicelist->invoice_date
			? date('d/m/Y', strtotime($invoicelist->invoice_date))
			: '';

		return [
			(string) $invoicelist->id,
			$issueDate,
			trim(($clientdata->first_name ?? '').' '.($clientdata->last_name ?? '')),
			(string) ($admindata->first_name ?? ''),
			(string) ($partnerdata->partner_name ?? ''),
			(string) ($productdata->name ?? ''),
			'AUD '.(string) ($invoicelist->net_fee_rec ?? ''),
			'$'.number_format($coom_amt, 2),
			'$'.number_format($tax_amt, 2),
			'$'.number_format($feepaid, 2),
			(string) ($clientdata->client_id ?? 'N/A'),
			$assignedTo ? trim($assignedTo->first_name.' '.$assignedTo->last_name) : 'N/A',
		];
	}
	
	
	
	public function show(Request $request, $id){
		if(Invoice::where('id', '=', $id)->exists()) 
		{
			$invoicedetail = Invoice::where('id', '=', $id)->first();
			if($invoicedetail->type == 3){
				$workflowdaa = \App\Models\Workflow::where('id', $invoicedetail->application_id)->first();
				$applicationdata = array();
				$partnerdata = array();
				$productdata = array();
				$branchdata = array();
			}else{
				$applicationdata = \App\Models\Application::where('id', $invoicedetail->application_id)->first();
				if(!$applicationdata){
					return redirect()->back()->with('error', 'Application data not found for this invoice.');
				}
				$partnerdata = \App\Models\Partner::where('id', @$applicationdata->partner_id)->first();
				$productdata = \App\Models\Product::where('id', @$applicationdata->product_id)->first();
				$branchdata = \App\Models\PartnerBranch::where('id', @$applicationdata->branch)->first();
				$workflowdaa = \App\Models\Workflow::where('id', @$applicationdata->workflow)->first();
			}
			
			$clientdata = \App\Models\Admin::where('id', $invoicedetail->client_id)->first();
			if(!$clientdata){
				return redirect()->back()->with('error', 'Client not found for this invoice.');
			}
			$admindata = \App\Models\Staff::find($invoicedetail->user_id);
			
			
			return view('Admin.invoice.show',compact(['applicationdata','partnerdata','workflowdaa','clientdata','productdata','branchdata','invoicedetail','admindata'])); 
		}else{
			return redirect()->back()->with('error', 'Record Not Found');
		}
	}
	
	public function invoicepaymentstore(Request $request){
		$requestData 		= 	$request->all();
		$invoicedetail = \App\Models\Invoice::where('id', @$requestData['invoice_id'])->first();
		if (!$invoicedetail) {
			if ($request->is_ajax == 'true') {
				echo json_encode(['status' => false, 'message' => 'Invoice not found']);
				return;
			}
			return redirect()->back()->with('error', 'Invoice not found');
		}

		$invoiceitemdetails = \App\Models\InvoiceDetail::where('invoice_id', $requestData['invoice_id'])->orderby('id','ASC')->get();
		$coom_amt = 0;
		$total_fee = 0;
		$netamount = 0;		
		foreach($invoiceitemdetails as $invoiceitemdetail){
			$coom_amt += $invoiceitemdetail->comm_amt;
			$total_fee += $invoiceitemdetail->total_fee;
			$netamount += $invoiceitemdetail->netamount;
		}
		$paymentdetails = \App\Models\InvoicePayment::where('invoice_id', $requestData['invoice_id'])->orderby('created_at', 'DESC')->get();
		$amount_rec = 0;
		foreach($paymentdetails as $paymentdetail){
			$amount_rec += $paymentdetail->amount_rec;
		}
		if($invoicedetail->type == 2){
			$totaldue = $coom_amt - $amount_rec;
		}else if($invoicedetail->type == 3){
			$totaldue = $netamount - $amount_rec;
		}else{
			$feepaid = $total_fee - $coom_amt;
			$totaldue = $feepaid - $amount_rec;
		}

		// INV-9: only count/insert non-empty, positive payment lines
		$paymentAmounts = is_array($requestData['payment_amount'] ?? null) ? $requestData['payment_amount'] : [];
		$paymentDates = is_array($requestData['payment_date'] ?? null) ? $requestData['payment_date'] : [];
		$paymentModes = is_array($requestData['payment_mode'] ?? null) ? $requestData['payment_mode'] : [];
		$validLineIndexes = [];
		$payment_amount = 0;
		foreach ($paymentAmounts as $ia => $rawAmount) {
			if ($rawAmount === null || $rawAmount === '') {
				continue;
			}
			if (!is_numeric($rawAmount)) {
				continue;
			}
			$lineAmount = (float) $rawAmount;
			if ($lineAmount <= 0) {
				continue;
			}
			$validLineIndexes[] = $ia;
			$payment_amount += $lineAmount;
		}

		if (empty($validLineIndexes)) {
			if ($request->is_ajax == 'true') {
				echo json_encode(['status' => false, 'message' => 'Please enter a valid payment amount']);
				return;
			}
			return redirect()->back()->with('error', 'Please enter a valid payment amount');
		}

		if($payment_amount > $totaldue){
			if($request->is_ajax == 'true'){
				$response['status'] 	= 	false;
				$response['message']	=	'Amount should be less than total due';
				echo json_encode($response);
				return;
			}
			return redirect()->back()->with('error', 'Amount should be less than total due');
		}

		$followupsaved = false;
		foreach ($validLineIndexes as $ia) {
			$objf				= 	new InvoicePayment;
			$objf->invoice_id	=	$requestData['invoice_id'];
			$objf->amount_rec	=	(float) $paymentAmounts[$ia];
			$objf->payment_date	=	$paymentDates[$ia] ?? null;
			$objf->payment_mode	=	$paymentModes[$ia] ?? null;
			$followupsaved = $objf->save() || $followupsaved;
		}
		
		$invoicedetail = \App\Models\Invoice::where('id', $requestData['invoice_id'])->first();
		$invoiceitemdetails = \App\Models\InvoiceDetail::where('invoice_id', $requestData['invoice_id'])->orderby('id','ASC')->get();
		$coom_amt = 0;
		$total_fee = 0;
		$netamount = 0;		
		foreach($invoiceitemdetails as $invoiceitemdetail){
			$coom_amt += $invoiceitemdetail->comm_amt;
			$total_fee += $invoiceitemdetail->total_fee;
			$netamount += $invoiceitemdetail->netamount;
		}
		$paymentdetails = \App\Models\InvoicePayment::where('invoice_id', $requestData['invoice_id'])->orderby('created_at', 'DESC')->get();
		$amount_rec = 0;
		foreach($paymentdetails as $paymentdetail){
			$amount_rec += $paymentdetail->amount_rec;
		}
		if($invoicedetail->type == 2){
			$totaldue = $coom_amt - $amount_rec;
		}else if($invoicedetail->type == 3){
			$totaldue = $netamount - $amount_rec;
		}else{
			$feepaid = $total_fee - $coom_amt;
			$totaldue = $feepaid - $amount_rec;
		}
		if($totaldue == 0){
			$obj = \App\Models\Invoice::find($requestData['invoice_id']);
			$obj->status = 1;
			$obj->save();
		}
		if($followupsaved){
			if($request->is_ajax == 'true'){
				$response['status'] 	= 	true;
				$response['message']	=	'Payment Saved successfully';
				echo json_encode($response);
			}else{
				return redirect()->back()->with('success', 'Payment Saved successfully');
			}
		}else{
			if($request->is_ajax == 'true'){
				$response['status'] 	= 	false;
				$response['message']	=	'Please try again';
				echo json_encode($response);
			}else{
				return redirect()->back()->with('error', 'Please try again');
			}
		}
	}
	
	public function getinvoices(Request $request){
		$client_id = $request->clientid;
		
		$invoicelists = \App\Models\Invoice::where('client_id',$client_id)->orderby('created_at','DESC')->get();
		ob_start();
		foreach($invoicelists as $invoicelist){
			if($invoicelist->type == 3){
				$workflowdaa = \App\Models\Workflow::where('id', $invoicelist->application_id)->first();
			}else{
				$applicationdata = \App\Models\Application::where('id', $invoicelist->application_id)->first();
				$workflowdaa = \App\Models\Workflow::where('id', @$applicationdata->workflow)->first();
				$partnerdata = \App\Models\Partner::where('id', @$applicationdata->partner_id)->first();
			}
			$invoiceitemdetails = \App\Models\InvoiceDetail::where('invoice_id', $invoicelist->id)->orderby('id','ASC')->get();
			$netamount = 0;
			$coom_amt = 0;
			$total_fee = 0;
			foreach($invoiceitemdetails as $invoiceitemdetail){
				$netamount += $invoiceitemdetail->netamount;
				$coom_amt += $invoiceitemdetail->comm_amt;
				$total_fee += $invoiceitemdetail->total_fee;
			}
			
			$paymentdetails = \App\Models\InvoicePayment::where('invoice_id', $invoicelist->id)->orderby('created_at', 'DESC')->get();
			$amount_rec = 0;
			foreach($paymentdetails as $paymentdetail){
				$amount_rec += $paymentdetail->amount_rec;
			} 
			// INV-1: same due rules as invoicepaymentstore
			$totaldue = Invoice::computeOutstandingDue(
				$invoicelist->type,
				$total_fee,
				$coom_amt,
				$netamount,
				$amount_rec
			);
			?>
			<tr id="iid_<?php echo $invoicelist->id; ?>">
				<td><?php echo $invoicelist->id; ?></td>
				<td><?php echo $invoicelist->invoice_date; ?>
				<?php if($invoicelist->type == 1){
					$rtype = 'Net Claim';
				}else if($invoicelist->type == 2){
					$rtype = 'Gross Claim';
				}else{
					$rtype = 'General';
				} ?>
				<span title="<?php echo $rtype; ?>" class="ui label zippyLabel"><?php echo $rtype; ?></span></td>
				<td class="invoice-service-col"><?php echo $workflowdaa->name; ?><br><?php echo @$partnerdata->partner_name; ?></td>
				<td>AUD <?php echo $invoicelist->net_fee_rec; ?></td>	
				<td><?php echo $invoicelist->discount; ?></td>
				<td>-</td>
				<td>
				<?php if($invoicelist->status == 1){ ?>
					<span class="ag-label--circular" style="color: #6777ef" >Paid</span></td> 
				<?php }else{  ?>
					<span class="ag-label--circular" style="color: #ed5a5a" >UnPaid</span></td> 
				<?php }  ?>
				<td>
					<div class="dropdown d-inline">
						<button class="btn btn-primary dropdown-toggle" type="button" id="" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
						<div class="dropdown-menu">
							<a class="dropdown-item has-icon" href="#">Send Email</a>
							<a target="_blank" class="dropdown-item has-icon" href="<?php echo \URL::to('invoice/view/'); ?>/<?php echo $invoicelist->id; ?>">View</a>
							<?php if($invoicelist->status == 0){ ?>
							<a target="_blank" class="dropdown-item has-icon" href="<?php echo \URL::to('invoice/edit/'); ?>/<?php echo $invoicelist->id; ?>">Edit</a>
							<a data-netamount="<?php echo $netamount; ?>" data-dueamount="<?php echo $totaldue; ?>" data-invoiceid="<?php echo $invoicelist->id; ?>" class="dropdown-item has-icon addpaymentmodal" href="javascript:;"> Make Payment</a>
							<?php } ?>
						</div>
					</div>								  
				</td>
			</tr>
			<?php
		}
		return ob_get_clean();
	}
	public function store(Request $request)  
	{		
	    
		$userid = Auth::user()->id; 
		
		$requestData 		= 	$request->all();
		//echo "<pre>requestData==";print_r($requestData);die;
	
		
		$subtotal = 0;
		$total_value = 0;
		/* Profile Image Upload Function Start */						  
		$files = $request->file('attachfile');
		if($request->hasfile('attachfile')) 
		{	
		    $attachfile = array();
			foreach ($files as $file) {
				$attachfile[] = $this->uploadFile($file, Config::get('constants.invoice'));
			}
		}
		else
		{
			//$attachfile = NULL;
			$attachfile = array();
		}	
		//echo "<pre>attachfile==";print_r($attachfile);die;
		
		/* Profile Image Upload Function End */	
		$profiledetail = \App\Models\Profile::where('id', @$requestData['profile'])->first();
		if (!$profiledetail) {
			$profiledetail = \App\Helpers\Helper::defaultCrmProfile();
		}
		$pdetail = '';
		if($profiledetail){
			$ps = array(
				'name'=> $profiledetail->company_name,
				'address'=> $profiledetail->address,
				'phone'=> $profiledetail->phone,
				'other_phone'=> $profiledetail->other_phone,
				'email'=> $profiledetail->email,
				'website'=> $profiledetail->website,
				'logo'=> $profiledetail->logo,
				'id'=> $profiledetail->id,
				'abn'=> $profiledetail->abn,
			);
			$pdetail = json_encode($ps);
		}
		$obj						= 	new Invoice; 
		$obj->user_id				=	Auth::user()->id;  
		$obj->client_id				=	@$requestData['client_id'];  
		$obj->application_id		=	@$requestData['applicationid'];  
		$obj->type					=	@$requestData['type'];  
		$obj->invoice_date			=	@$requestData['invoice_date'];  
		$obj->due_date				=	@$requestData['invoice_due_date'];  
		$obj->discount				=	@$requestData['discount'];  
		$obj->discount_date			=	@$requestData['discount_date']; 
		if($requestData['type'] == 2){
			$obj->net_fee_rec			=	@$requestData['invoice_net_revenue'];
		}else{
			$obj->net_fee_rec			=	@$requestData['invoice_net_amount'];
			$obj->net_incone			=	@$requestData['invoice_net_income'];  
		}
		  
		
		$obj->notes					=	@$requestData['notes'];  
		$obj->profile				=	@$pdetail;  
		$obj->payment_option		=	@$requestData['paymentoption'];  
		
		if(is_array($attachfile) && count($attachfile) >0 ){
		    $obj->attachments			=	implode(",",$attachfile);
		} else {
		    $obj->attachments			=	"";
		}
		
		if( isset( $requestData['payment_done'] ) && $requestData['payment_done'] == 'on'){
		    $obj->status	=  1;
		} else {
		    $obj->status	=  0; 
		}
		$obj->currency				=	'AUD';
	 
		$saved				=	$obj->save();  
		
		/* Update Client detail start*/
        $obj_client				=   \App\Models\Admin::find(@$requestData['client_id']);
		if(!$obj_client){
			return redirect()->back()->with('error', 'Client not found. Please refresh and try again.');
		}
        $obj_client->first_name	=	@$requestData['first_name'];
        $obj_client->last_name	=	@$requestData['last_name'];

        $dob = '';
        if($requestData['dob'] != ''){
            $dobs = explode('/', $requestData['dob']);
            $dob = $dobs[2].'-'.$dobs[1].'-'. $dobs[0];
        }
        $obj_client->dob =	@$dob;
        $saved_client	 =	$obj_client->save();
        /* Update Client detail end*/
        
		for($i =0; $i<count(@$requestData['total_fee']); $i++){
			//echo $requestData['item_detail'][$i].'---'.$requestData['quantity'][$i].'---'.$requestData['rate'][$i].'<br>';
			$invoicedetail = new InvoiceDetail;
			$invoicedetail->invoice_id 		= $obj->id;
			$invoicedetail->description		= @$requestData['description'][$i];
			$invoicedetail->total_fee 		= @$requestData['total_fee'][$i];
			$invoicedetail->comm_per 		= @$requestData['comm_per'][$i];
			$invoicedetail->comm_amt 		= @$requestData['comm_amt'][$i];
			$invoicedetail->tax 			= @$requestData['tax'][$i];
			$invoicedetail->tax_amount 		= @$requestData['tax_amount'][$i];
			$invoicedetail->bonus_amount 		= @$requestData['bonus_amount'][$i];
			$invoicedetail->netamount 		= @$requestData['netamount'][$i];
			$saved							=	@$invoicedetail->save();
		}
		if(!$saved) 
		{
			return redirect()->back()->with('error', Config::get('constants.server_error'));
		}
		else
		{ 
	
			if(isset($request->share_user) && $request->share_user != 'no' && $request->incomeshare_amount != ''){
				if(\App\Models\IncomeSharing::where('invoice_id',$obj->id)->exists()){
					$IncomeSharing = \App\Models\IncomeSharing::where('invoice_id',$obj->id)->first();
					$oshare = \App\Models\IncomeSharing::find($IncomeSharing->id);
				}else{
					$oshare = new \App\Models\IncomeSharing;
					$oshare->invoice_id = $obj->id;
				}
			$oshare->rec_id = $request->share_user;
			$oshare->amount = $request->incomeshare_amount;
			$oshare->status = 0; // 0 = unpaid, 1 = paid
			if(isset($request->taxval)){
				$oshare->is_tax = 1;
				// Calculate GST (10% of amount)
				$oshare->tax = $request->incomeshare_amount * 0.10;
			}else{
				$oshare->is_tax = 0;
				$oshare->tax = 0;
			}
			$oshare->save();
			}else{
				
			}
	
			for($ia =0; $ia<count(@$requestData['payment_amount']); $ia++){
				$objf				= 	new InvoicePayment;
				$objf->invoice_id	=	$obj->id;
				$objf->amount_rec	=	@$requestData['payment_amount'][$ia];
				$objf->payment_date	=	@$requestData['payment_date'][$ia];
				$objf->payment_mode	=	@$requestData['payment_mode'][$ia];
				
				$followupsaved				=	$objf->save(); 
			}
			
			
			
			$invoicedetail = \App\Models\Invoice::where('id',$obj->id)->first();
		$invoiceitemdetails = \App\Models\InvoiceDetail::where('invoice_id', $obj->id)->orderby('id','ASC')->get();
		$coom_amt = 0;
		$total_fee = 0;
		$netamount = 0;		
		foreach($invoiceitemdetails as $invoiceitemdetail){
			$coom_amt += $invoiceitemdetail->comm_amt;
			$total_fee += $invoiceitemdetail->total_fee;
			$netamount += $invoiceitemdetail->netamount;
		}
		$paymentdetails = \App\Models\InvoicePayment::where('invoice_id',$obj->id)->orderby('created_at', 'DESC')->get();
		$amount_rec = 0;
		foreach($paymentdetails as $paymentdetail){
			$amount_rec += $paymentdetail->amount_rec;
		}
		if($invoicedetail->type == 2){
			$totaldue = $coom_amt - $amount_rec;
		}else if($invoicedetail->type == 3){
			$totaldue = $netamount - $amount_rec;
		}else{
			$feepaid = $total_fee - $coom_amt;
			$totaldue = $feepaid - $amount_rec;
		}
		
			$objsss = \App\Models\Invoice::find($obj->id);
			$objsss->invoice_no = date('Y').'/'.date('m').'/'.$obj->id;
			$objsss->save();
		if($totaldue == 0){
			$objss = \App\Models\Invoice::find($obj->id);
			$objss->status = 1;
			$objss->save();
		}
		
			if(@$requestData['btn'] == 'savepreview'){
				return redirect()->to('/invoice/view/'.@$obj->id)->with('success', 'Invoice saved Successfully');
			}
			else{
				return redirect()->route('invoice.unpaid')->with('success', 'Invoice saved Successfully');
				
			}
		} 
	}
	
	public function updatecominvoices(Request $request)  
	{		
		$userid = Auth::user()->id; 
		
		$requestData 		= 	$request->all();
		//echo '<pre>'; print_r($requestData); die;
		$subtotal = 0;
		$total_value = 0;
		//echo Config::get('constants.invoice'); die;
		/* Profile Image Upload Function Start */	
			$files = $request->file('attachfile');
			if($request->hasfile('attachfile')) 
			{	
				foreach ($files as $file) {
					$attachfile[] = $this->uploadFile($file, Config::get('constants.invoice'));
				}

				$old_images=explode(",",$requestData['old_attachments']);

				foreach($old_images as $image){
					$image_path = public_path("/img/invoice/{$image}");
					if (File::exists($image_path)) {
						File::delete($image_path);
						// unlink($image_path);
					}
				}
				
			}
			else
			{
				$attachfile[] = @$requestData['old_attachments'];
			}
		
		$profiledetail = \App\Models\Profile::where('id', @$requestData['profile'])->first();
		if (!$profiledetail) {
			$profiledetail = \App\Helpers\Helper::defaultCrmProfile();
		}
		$pdetail = '';
		if($profiledetail){
			$ps = array(
				'name'=> $profiledetail->company_name,
				'address'=> $profiledetail->address,
				'phone'=> $profiledetail->phone,
				'other_phone'=> $profiledetail->other_phone,
				'email'=> $profiledetail->email,
				'website'=> $profiledetail->website,
				'logo'=> $profiledetail->logo,
				'id'=> $profiledetail->id,
				'abn'=> $profiledetail->abn,
			);
			$pdetail = json_encode($ps);
		}		
		/* Profile Image Upload Function End */	
		$obj						= 	Invoice::find($requestData['id']); 
		$obj->user_id				=	Auth::user()->id;  
		//$obj->invoice_no = date('Y').'/'.date('m').'/'.$requestData['id'];
		$obj->client_id				=	@$requestData['client_id'];  
		$obj->application_id		=	@$requestData['applicationid'];  
		$obj->type					=	@$requestData['type'];  
		$obj->invoice_date			=	@$requestData['invoice_date'];  
		$obj->due_date				=	@$requestData['invoice_due_date'];  
		$obj->discount				=	@$requestData['discount'];  
		$obj->discount_date			=	@$requestData['discount_date']; 
		if($requestData['type'] == 2){
			$obj->net_fee_rec			=	@$requestData['invoice_net_revenue'];
		}else{
			$obj->net_fee_rec			=	@$requestData['invoice_net_amount'];
			$obj->net_incone			=	@$requestData['invoice_net_income'];  
		}
		  
		$obj->profile				=	@$pdetail;  
		$obj->notes					=	@$requestData['notes'];  
		$obj->payment_option		=	@$requestData['paymentoption'];  
		
		$obj->attachments			=	implode(",",$attachfile);  
		$obj->currency				=	'AUD';
		
		$saved				=	$obj->save();  
		$res= InvoiceDetail::where('invoice_id',$obj->id)->delete();
		for($i =0; $i<count(@$requestData['total_fee']); $i++){
			//echo $requestData['item_detail'][$i].'---'.$requestData['quantity'][$i].'---'.$requestData['rate'][$i].'<br>';
			$invoicedetail = new InvoiceDetail;
			$invoicedetail->invoice_id 		= $obj->id;
			$invoicedetail->description		= @$requestData['description'][$i];
			$invoicedetail->total_fee 		= @$requestData['total_fee'][$i];
			$invoicedetail->comm_per 		= @$requestData['comm_per'][$i];
			$invoicedetail->comm_amt 		= @$requestData['comm_amt'][$i];
			$invoicedetail->tax 			= @$requestData['tax'][$i];
			$invoicedetail->bonus_amount 		= @$requestData['bonus_amount'][$i];
			$invoicedetail->tax_amount 		= @$requestData['tax_amount'][$i];
			$invoicedetail->netamount 		= @$requestData['netamount'][$i];
			$saved							=	@$invoicedetail->save();
		}
		if(!$saved) 
		{
			return redirect()->back()->with('error', Config::get('constants.server_error'));
		}
		else
		{ 
			if($request->share_user != 'no' && $request->incomeshare_amount != ''){
				if(\App\Models\IncomeSharing::where('invoice_id',$obj->id)->exists()){
					$IncomeSharing = \App\Models\IncomeSharing::where('invoice_id',$obj->id)->first();
					$oshare = \App\Models\IncomeSharing::find($IncomeSharing->id);
				}else{
					$oshare = new \App\Models\IncomeSharing;
					$oshare->invoice_id = $obj->id;
				}
			$oshare->rec_id = $request->share_user;
			$oshare->amount = $request->incomeshare_amount;
			$oshare->status = 0; // 0 = unpaid, 1 = paid
			if(isset($request->taxval)){
				$oshare->is_tax = 1;
				// Calculate GST (10% of amount)
				$oshare->tax = $request->incomeshare_amount * 0.10;
			}else{
				$oshare->is_tax = 0;
				$oshare->tax = 0;
			}
			$oshare->save();
			}
			
			if($request->share_user == 'no'){
				if(\App\Models\IncomeSharing::where('invoice_id',$obj->id)->exists()){
					\App\Models\IncomeSharing::where('invoice_id',$obj->id)->delete();
				}
			}
	
	$res= InvoicePayment::where('invoice_id',$obj->id)->delete();
			for($ia =0; $ia<count(@$requestData['payment_amount']); $ia++){
				if(!isset($requestData['payment_amount'][$ia]) || $requestData['payment_amount'][$ia] === '' || $requestData['payment_amount'][$ia] === null){
					continue;
				}
				$objf				= 	new InvoicePayment;
				$objf->invoice_id	=	$obj->id;
				$objf->amount_rec	=	@$requestData['payment_amount'][$ia];
				$objf->payment_date	=	@$requestData['payment_date'][$ia];
				$objf->payment_mode	=	@$requestData['payment_mode'][$ia];
				
				$followupsaved				=	$objf->save(); 
			}
			
			$this->finalizeInvoiceStatusAfterUpdate($obj->id, $requestData);
		
			if(@$requestData['btn'] == 'savepreview'){
				return Redirect::to('/invoice/view/'.@$obj->id)->with('success', 'Invoice saved Successfully');
			}
			else{
				return redirect()->route('invoice.unpaid')->with('success', 'Invoice updated Successfully');
			}
		} 
	}

	public function generalStore(Request $request)  
	{		
	
		$userid = Auth::user()->id; 
		
		$requestData 		= 	$request->all();
		//echo '<pre>'; print_r($requestData); die;
		$subtotal = 0;
		$total_value = 0;
		/* Profile Image Upload Function Start */						  
		$files = $request->file('attachfile');
		if($request->hasfile('attachfile')) 
		{	
			foreach ($files as $file) {
				$attachfile[] = $this->uploadFile($file, Config::get('constants.invoice'));
			}
		}
		else
		{
			$attachfile = NULL;
		}		
		/* Profile Image Upload Function End */	
		$profiledetail = \App\Models\Profile::where('id', @$requestData['profile'])->first();
		if (!$profiledetail) {
			$profiledetail = \App\Helpers\Helper::defaultCrmProfile();
		}
		$pdetail = '';
		if($profiledetail){
			$ps = array(
				'name'=> $profiledetail->company_name,
				'address'=> $profiledetail->address,
				'phone'=> $profiledetail->phone,
				'other_phone'=> $profiledetail->other_phone,
				'email'=> $profiledetail->email,
				'website'=> $profiledetail->website,
				'logo'=> $profiledetail->logo,
				'id'=> $profiledetail->id,
				'abn'=> $profiledetail->abn,
			);
			$pdetail = json_encode($ps);
		}
		$obj						= 	new Invoice; 
	$obj->user_id				=	Auth::user()->id;  
	$obj->client_id				=	@$requestData['client_id'];  
	$obj->application_id		=	@$requestData['applicationid'];  
	$obj->type					=	@$requestData['type'];  
	$obj->invoice_date			=	@$requestData['invoice_date'];  
	$obj->due_date				=	@$requestData['invoice_due_date'];  
	$obj->net_fee_rec			=	@$requestData['invoice_net_amount'];
	$obj->net_incone			=	@$requestData['invoice_net_income'];  
	$obj->notes					=	@$requestData['notes'];  
	$obj->payment_option		=	@$requestData['paymentoption'];  
	$obj->attachments			=	$attachfile ? implode(",",$attachfile) : null;
		$obj->currency				=	'AUD';
		$obj->status				=	0;  
		$obj->profile				=	@$pdetail;  
		$saved				=	$obj->save();  
		for($i =0; $i<count(@$requestData['amount']); $i++){
			//echo $requestData['item_detail'][$i].'---'.$requestData['quantity'][$i].'---'.$requestData['rate'][$i].'<br>';
			$invoicedetail = new InvoiceDetail;
			$invoicedetail->invoice_id 		= $obj->id;
			$invoicedetail->description		= @$requestData['description'][$i];
			$invoicedetail->income_type		= @$requestData['income_type'][$i];
			$invoicedetail->total_fee 		= @$requestData['amount'][$i];
			
			$invoicedetail->tax 			= @$requestData['tax_code'][$i];
			$invoicedetail->tax_amount 		= @$requestData['tax_amt'][$i];
			$invoicedetail->bonus_amount 		= 0;
			$invoicedetail->netamount 		= @$requestData['total_amt'][$i];
			$saved							=	@$invoicedetail->save();
		}
		if(!$saved) 
		{
			return redirect()->back()->with('error', Config::get('constants.server_error'));
		}
		else
		{ 
			for($ia =0; $ia<count(@$requestData['payment_amount']); $ia++){
				$objf				= 	new InvoicePayment;
				$objf->invoice_id	=	$obj->id;
				$objf->amount_rec	=	@$requestData['payment_amount'][$ia];
				$objf->payment_date	=	@$requestData['payment_date'][$ia];
				$objf->payment_mode	=	@$requestData['payment_mode'][$ia];
				
				$followupsaved				=	$objf->save(); 
			}
		
			// Generate invoice number
			$objsss = \App\Models\Invoice::find($obj->id);
			$objsss->invoice_no = date('Y').'/'.date('m').'/'.$obj->id;
			$objsss->save();
		
			if(@$requestData['btn'] == 'savepreview'){
				return Redirect::to('/invoice/view/'.@$obj->id)->with('success', 'Invoice saved Successfully');
			}
			else{
				return redirect()->route('invoice.unpaid')->with('success', 'Invoice updated Successfully');
			}
		} 
	}
	
	public function updategeninvoices(Request $request)  
	{		
	
		$userid = Auth::user()->id; 
		
		$requestData 		= 	$request->all();
		//echo '<pre>'; print_r($requestData); die;
		$subtotal = 0;
		$total_value = 0;
		/* Profile Image Upload Function Start */						  
			if($request->hasfile('attachfile')) 
			{	
				$attachfile = $this->uploadFile($request->file('attachfile'), Config::get('constants.invoice'));
			}
			else
			{
				$attachfile = @$requestData['old_attachments'];
			}		
		/* Profile Image Upload Function End */
		$profiledetail = \App\Models\Profile::where('id', @$requestData['profile'])->first();
		if (!$profiledetail) {
			$profiledetail = \App\Helpers\Helper::defaultCrmProfile();
		}
		$pdetail = '';
		if($profiledetail){
			$ps = array(
				'name'=> $profiledetail->company_name,
				'address'=> $profiledetail->address,
				'phone'=> $profiledetail->phone,
				'other_phone'=> $profiledetail->other_phone,
				'email'=> $profiledetail->email,
				'website'=> $profiledetail->website,
				'logo'=> $profiledetail->logo,
				'id'=> $profiledetail->id,
				'abn'=> $profiledetail->abn,
			);
			$pdetail = json_encode($ps);
		}
		$obj						= 	Invoice::find($requestData['id']); 
		$obj->user_id				=	Auth::user()->id;  
		$obj->client_id				=	@$requestData['client_id'];  
		$obj->application_id		=	@$requestData['applicationid'];  
		$obj->type					=	@$requestData['type'];  
		$obj->invoice_date			=	@$requestData['invoice_date'];  
		$obj->due_date				=	@$requestData['invoice_due_date'];  
		$obj->net_fee_rec			=	@$requestData['invoice_net_amount'];
		$obj->net_incone			=	@$requestData['invoice_net_income'];  
		$obj->notes					=	@$requestData['notes'];  
		$obj->payment_option		=	@$requestData['paymentoption'];  
		$obj->attachments			=	$attachfile;  
		$obj->currency				=	'AUD';
		$obj->profile				=	@$pdetail;  
		$saved				=	$obj->save();  
		$res= InvoiceDetail::where('invoice_id',$obj->id)->delete();
		for($i =0; $i<count(@$requestData['amount']); $i++){
			//echo $requestData['item_detail'][$i].'---'.$requestData['quantity'][$i].'---'.$requestData['rate'][$i].'<br>';
			$invoicedetail = new InvoiceDetail;
			$invoicedetail->invoice_id 		= $obj->id;
			$invoicedetail->description		= @$requestData['description'][$i];
			$invoicedetail->income_type		= @$requestData['income_type'][$i];
			$invoicedetail->total_fee 		= @$requestData['amount'][$i];
			
			$invoicedetail->tax 			= @$requestData['tax_code'][$i];
			$invoicedetail->tax_amount 		= @$requestData['tax_amt'][$i];
			$invoicedetail->bonus_amount 		= 0;
			$invoicedetail->netamount 		= @$requestData['total_amt'][$i];
			$saved							=	@$invoicedetail->save();
		}
		if(!$saved) 
		{
			return redirect()->back()->with('error', Config::get('constants.server_error'));
		}
		else
		{ 
	$res= InvoicePayment::where('invoice_id',$obj->id)->delete();
			for($ia =0; $ia<count(@$requestData['payment_amount']); $ia++){
				if(!isset($requestData['payment_amount'][$ia]) || $requestData['payment_amount'][$ia] === '' || $requestData['payment_amount'][$ia] === null){
					continue;
				}
				$objf				= 	new InvoicePayment;
				$objf->invoice_id	=	$obj->id;
				$objf->amount_rec	=	@$requestData['payment_amount'][$ia];
				$objf->payment_date	=	@$requestData['payment_date'][$ia];
				$objf->payment_mode	=	@$requestData['payment_mode'][$ia];
				
				$followupsaved				=	$objf->save(); 
			}
		
			$this->finalizeInvoiceStatusAfterUpdate($obj->id, $requestData);
		
			if(@$requestData['btn'] == 'savepreview'){
				return Redirect::to('/invoice/view/'.@$obj->id)->with('success', 'Invoice saved Successfully');
			}
			else{
				//return Redirect::to('/admin/invoice/lists/'.base64_encode(convert_uuencode(@$obj->id)))->with('success', 'Invoice saved Successfully');
				echo 'success';
			}
		} 
	} 
	
	
	public function getinvoicespdf(Request $request, $id = NULL){
		if(Invoice::where('id', '=', $id)->exists()) 
		{
			$invoicedetail = Invoice::where('id', '=', $id)->first();
			if($invoicedetail->type == 3){
				$workflowdaa = \App\Models\Workflow::where('id', $invoicedetail->application_id)->first();
				$applicationdata = null;
				$partnerdata = null;
				$productdata = null;
				$branchdata = null;
			}else{
				$applicationdata = \App\Models\Application::where('id', $invoicedetail->application_id)->first();
				$partnerdata = \App\Models\Partner::where('id', @$applicationdata->partner_id)->first();
				$productdata = \App\Models\Product::where('id', @$applicationdata->product_id)->first();
				$branchdata = \App\Models\PartnerBranch::where('id', @$applicationdata->branch)->first();
				$workflowdaa = \App\Models\Workflow::where('id', @$applicationdata->workflow)->first();
			}
			
			$clientdata = \App\Models\Admin::where('id', $invoicedetail->client_id)->first();
			$admindata = \App\Models\Staff::find($invoicedetail->user_id);

			$logoBase64 = \App\Helpers\Helper::profileLogoBase64(
				\App\Helpers\Helper::invoiceProfileLogoFilename($invoicedetail)
			);
			
			$pdf = PDF::setOptions([
			'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false,
			'logOutputFile' => storage_path('logs/log.htm'),
			'tempDir' => storage_path('logs/')
			])->loadView('emails.invoice',compact(['applicationdata','partnerdata','workflowdaa','clientdata','productdata','branchdata','invoicedetail','admindata','logoBase64'])); 
			//
			return $pdf->stream('Inv '.$invoicedetail->id.'.pdf');
		}else{
			return redirect()->back()->with('error', 'Record Not Found');
		}

	}
	public function edit(Request $request, $id = NULL)
	{	
		if(Invoice::where('id', '=', $id)->exists()) 
		{
			$invoicedetail = Invoice::where('id', '=', $id)->first();
			$clientdata = \App\Models\Admin::where('id', $invoicedetail->client_id)->first();
			$admindata = \App\Models\Staff::find($invoicedetail->user_id);
			
			// Check if client data exists
			if(!$clientdata) {
				return redirect()->back()->with('error', 'Client data not found for this invoice');
			}
			
			if($invoicedetail->type == 3){
				$workflowdaa = \App\Models\Workflow::where('id', $invoicedetail->application_id)->first();
				
				return view('Admin.invoice.edit-gen',compact(['workflowdaa','clientdata','invoicedetail','admindata'])); 
			}else{
				$applicationdata = \App\Models\Application::where('id', $invoicedetail->application_id)->first();
				
				// Provide default empty objects if data is missing
				$partnerdata = \App\Models\Partner::where('id', @$applicationdata->partner_id)->first();
				$productdata = \App\Models\Product::where('id', @$applicationdata->product_id)->first();
				$branchdata = \App\Models\PartnerBranch::where('id', @$applicationdata->branch)->first();
				$workflowdaa = \App\Models\Workflow::where('id', @$applicationdata->workflow)->first();
				
				return view('Admin.invoice.edit',compact(['applicationdata','partnerdata','workflowdaa','clientdata','productdata','branchdata','invoicedetail','admindata'])); 
			}
			
		}else{
			return redirect()->back()->with('error', 'Record Not Found');
		}	
	
	}
	
	public function deletepayment(Request $request){
		if(InvoicePayment::where('id', '=', $request->pay_id)->exists()){
			$invocie = InvoicePayment::where('id', '=', $request->pay_id)->first();
			$res = DB::table('invoice_payments')->where('id', $request->pay_id)->delete();
			if($res){
				
				$invoicedetail = \App\Models\Invoice::where('id',$invocie->invoice_id)->first();
		$invoiceitemdetails = \App\Models\InvoiceDetail::where('invoice_id', $invocie->invoice_id)->orderby('id','ASC')->get();
		$coom_amt = 0;
		$total_fee = 0;
		$netamount = 0;		
		foreach($invoiceitemdetails as $invoiceitemdetail){
			$coom_amt += $invoiceitemdetail->comm_amt;
			$total_fee += $invoiceitemdetail->total_fee;
			$netamount += $invoiceitemdetail->netamount;
		}
		$paymentdetails = \App\Models\InvoicePayment::where('invoice_id',$invocie->invoice_id)->orderby('created_at', 'DESC')->get();
		$amount_rec = 0;
		foreach($paymentdetails as $paymentdetail){
			$amount_rec += $paymentdetail->amount_rec;
		}
		if($invoicedetail->type == 2){
			$totaldue = $coom_amt - $amount_rec;
		}else if($invoicedetail->type == 3){
			$totaldue = $netamount - $amount_rec;
		}else{
			$feepaid = $total_fee - $coom_amt;
			$totaldue = $feepaid - $amount_rec;
		}
		
		if($totaldue != 0){
			$objss = \App\Models\Invoice::find($invocie->invoice_id);
			$objss->status = 0;
			$objss->save();
		}
				$response['status'] 	= 	true;
			$response['message']	=	'Deleted successfully';
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
	
	public function deleteinvoice(Request $request){
		if(Invoice::where('id', '=', $request->id)->exists()){
			$invocie = Invoice::where('id', '=', $request->id)->delete();
			
			if($invocie){
				$res = DB::table('invoice_payments')->where('invoice_id', $request->id)->delete();
				$res = DB::table('invoice_details')->where('invoice_id', $request->id)->delete();
				$response['status'] 	= 	true;
			$response['message']	=	'Deleted successfully';
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
	
	public function unpaidgroupinvoice(){
		return view('Admin.invoice.unpaidgroupinvoice'); 
	}
	public function paidgroupinvoice(){
		return view('Admin.invoice.paidgroupinvoice'); 
	}	
	public function creategroupinvoice(){
		return view('Admin.invoice.creategroupinvoice');  
	} 
	// NOTE: All Invoice Schedule related methods have been removed
	// - invoiceschedules()
	// - deletepaymentschedule()
	// - paymentschedule()
	// - setuppaymentschedule()
	// - editpaymentschedule()
	// - getallpaymentschedules()
	// - addscheduleinvoicedetail()
	// - scheduleinvoicedetail()
	// - apppreviewschedules()

	/**
	 * Set invoice status from outstanding balance (same rules as invoicepaymentstore / deletepayment).
	 */
	private function syncInvoiceStatus($invoiceId)
	{
		$invoicedetail = Invoice::find($invoiceId);
		if (!$invoicedetail) {
			return;
		}

		$invoiceitemdetails = InvoiceDetail::where('invoice_id', $invoiceId)->orderby('id', 'ASC')->get();
		$coom_amt = 0;
		$total_fee = 0;
		$netamount = 0;
		foreach ($invoiceitemdetails as $invoiceitemdetail) {
			$coom_amt += $invoiceitemdetail->comm_amt;
			$total_fee += $invoiceitemdetail->total_fee;
			$netamount += $invoiceitemdetail->netamount;
		}

		$paymentdetails = InvoicePayment::where('invoice_id', $invoiceId)->orderby('created_at', 'DESC')->get();
		$amount_rec = 0;
		foreach ($paymentdetails as $paymentdetail) {
			$amount_rec += $paymentdetail->amount_rec;
		}

		if ($invoicedetail->type == 2) {
			$totaldue = $coom_amt - $amount_rec;
		} elseif ($invoicedetail->type == 3) {
			$totaldue = $netamount - $amount_rec;
		} else {
			$feepaid = $total_fee - $coom_amt;
			$totaldue = $feepaid - $amount_rec;
		}

		$invoicedetail->status = ($totaldue == 0) ? 1 : 0;
		$invoicedetail->save();
	}

	/**
	 * On invoice update: honor "Mark as paid" checkbox (same as store()), else derive status from balance.
	 */
	private function finalizeInvoiceStatusAfterUpdate($invoiceId, array $requestData)
	{
		if (isset($requestData['payment_done']) && $requestData['payment_done'] == 'on') {
			$invoice = Invoice::find($invoiceId);
			if ($invoice) {
				$invoice->status = 1;
				$invoice->save();
			}
		} else {
			$this->syncInvoiceStatus($invoiceId);
		}
	}
	
}
