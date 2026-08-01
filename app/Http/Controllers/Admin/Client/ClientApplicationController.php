<?php

namespace App\Http\Controllers\Admin\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\ActivitiesLog;
use Illuminate\Support\Facades\DB;
use Auth;

/**
 * Client application lifecycle
 * 
 * Methods to move from ClientsController:
 * - saveapplication
 * - getapplicationlists
 * - savetoapplication
 * - saleforcastservice
 */
class ClientApplicationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function saveapplication(Request $request){
		$response = [
			'status' => false,
			'message' => 'Please try again',
		];

		$enrolmentType = $request->input('enrolment_type');
		if (! array_key_exists($enrolmentType, \App\Models\Application::enrolmentTypeOptions())) {
			$response['message'] = 'Please select an Enrolment Type.';
			echo json_encode($response);
			return;
		}

		$companyName = $request->input('company_name');
		if (! array_key_exists($companyName, \App\Models\Application::companyNameOptions())) {
			$response['message'] = 'Please select a Company Name.';
			echo json_encode($response);
			return;
		}

		if(Admin::where('id', $request->client_id)->exists()){
			$workflow = $request->workflow;
			$explode = explode('_', $request->partner_branch);
			$partner = $explode[1];
			$branch = $explode[0];
			$product = $request->product;
			$client_id = $request->client_id;
			$status = 0;
			$workflowstage = \App\Models\WorkflowStage::where('w_id', $workflow)->orderby('id','asc')->first();
			$stage = $workflowstage->name;
			$sale_forcast = 0.00;
			$obj = new \App\Models\Application;
			$obj->user_id = Auth::user()->id;
			$obj->workflow = $workflow;
			$obj->partner_id = $partner;
			$obj->branch = $branch;
			$obj->product_id = $product;
			$obj->enrolment_type = $enrolmentType;
			$obj->company_name = $companyName;
			$obj->status = $status;
			$obj->stage = $stage;
			$obj->checklist_sheet_status = 'active'; // first-stage sheet: new apps show on Checklist until status is changed
			$obj->sale_forcast = $sale_forcast;
			$obj->client_id = $client_id;
			$saved = $obj->save();
			if($saved){
				$productdetail = \App\Models\Product::where('id', $product)->first();
				$partnerdetail = \App\Models\Partner::where('id', $partner)->first();
				$PartnerBranch = \App\Models\PartnerBranch::where('id', $branch)->first();
				$subject = 'has started an application';
				$objs = new ActivitiesLog;
				$objs->client_id = $request->client_id;
				$objs->created_by = Auth::user()->id;
				$objs->description = '<span class="text-semi-bold">'.@$productdetail->name.'</span><p>'.@$partnerdetail->partner_name.' ('.@$PartnerBranch->name.')</p>';
				$objs->subject = $subject;
				$objs->task_status = 0; // Required NOT NULL field (0 = activity, 1 = task)
				$objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
				$objs->save();
				$response['status'] 	= 	true;
				$response['message']	=	'You\'ve successfully updated your client\'s information.';
				$response['application_id'] = $obj->id;
				$clientRow = Admin::where('id', $client_id)->first(['id', 'email', 'first_name', 'last_name']);
				if ($clientRow) {
					$response['client_id'] = $clientRow->id;
					$response['client_email'] = $clientRow->email ?? '';
					$response['client_name'] = trim(($clientRow->first_name ?? '') . ' ' . ($clientRow->last_name ?? ''));
				}
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

	public function getapplicationlists(Request $request){
		if(Admin::where('id', $request->id)->exists()){
			$applications = \App\Models\Application::where('client_id', $request->id)->orderby('created_at', 'DESC')->get();
			$data = array();
			ob_start();
			foreach($applications as $alist){
				$productdetail = \App\Models\Product::where('id', $alist->product_id)->first();
				$partnerdetail = \App\Models\Partner::where('id', $alist->partner_id)->first();
				$PartnerBranch = \App\Models\PartnerBranch::where('id', $alist->branch)->first();
				$workflow = \App\Models\Workflow::where('id', $alist->workflow)->first();
              
                $application_assign_count = \App\Models\Note::where('type','client')->whereNotNull('client_id')->where('is_action',1)->where('status',0)->where('application_id',$alist->id)->where('client_id',$request->id)->count();
                //dd($application_assign_count);
				?>
				<tr id="id_<?php echo $alist->id; ?>">
				<td>
                  <a class="openapplicationdetail" data-id="<?php echo $alist->id; ?>" href="javascript:;" style="display:block;">
                  <?php echo @$productdetail->name; ?>
                  <?php  if( $application_assign_count > 0 ) { ?>
                            <span class="countTotalActivityAction" style="background: #1f1655;padding: 0px 5px;border-radius: 50%;color: #fff;margin-left: 5px;"><?php echo $application_assign_count;?></span>
                  <?php } ?>
                  </a> 
                  <small><?php echo @$partnerdetail->partner_name; ?>(<?php echo @$PartnerBranch->name; ?>)</small>
                </td>
				<td><?php echo @$workflow->name; ?></td>
				<td><?php echo @$alist->stage; ?></td>
				<td>
                    <?php if($alist->status == 0){ ?>
                        <span class="ag-label--circular" style="color: #6777ef" >In Progress</span>
                    <?php } else if($alist->status == 1){ ?>
                        <span class="ag-label--circular" style="color: #6777ef" >Completed</span>
                    <?php } else if($alist->status == 2){ ?>
                        <span class="ag-label--circular" style="color: red;" >Discontinued</span>
                    <?php } else if($alist->status == 3){ ?>
                        <span class="ag-label--circular" style="color: red;" >Cancelled</span>
                    <?php } else if($alist->status == 4){ ?>
                        <span class="ag-label--circular" style="color: red;" >Withdrawn</span>
                    <?php } else if($alist->status == 5){ ?>
                        <span class="ag-label--circular" style="color: red;" >Deferred</span>
                    <?php } else if($alist->status == 6){ ?>
                        <span class="ag-label--circular" style="color: red;" >Future</span>
                    <?php } else if($alist->status == 7){ ?>
                        <span class="ag-label--circular" style="color: red;" >VOE</span>
                    <?php } else if($alist->status == 8){ ?>
                            <span class="ag-label--circular" style="color: red;" >Refund</span>
                    <?php } ?>
                </td>

				<td><?php if(@$alist->start_date != ''){ echo date('d/m/Y', strtotime($alist->start_date)); } ?></td>
				<td><?php if(@$alist->end_date != ''){ echo date('d/m/Y', strtotime($alist->end_date)); } ?></td>
				<td><?php echo e(\App\Models\Application::enrolmentTypeLabel($alist->enrolment_type ?? null) ?: '—'); ?></td>
				<td><?php echo e(\App\Models\Application::companyNameLabel($alist->company_name ?? null) ?: '—'); ?></td>
			</tr>
				<?php
			}

			return ob_get_clean();
		}else{

		}

	}

}
