<?php

namespace App\Http\Controllers\Admin\Client;

use App\Http\Controllers\Controller;
use App\Models\ActivitiesLog;
use App\Models\Admin;
use App\Models\Application;
use App\Models\Partner;
use App\Models\PartnerBranch;
use App\Models\Product;
use App\Models\WorkflowStage;
use App\Traits\ClientAuthorization;
use Auth;
use Illuminate\Http\Request;

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
    use ClientAuthorization;

    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Null when client missing or staff not allowed (same rules as notes/activity).
     */
    private function resolveAccessibleApplicationClient($clientId, bool $forEdit = false): ?Admin
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

    /**
     * Parse UI value "branchId_partnerId" (see AdminController::getpartnerbranch).
     *
     * @return array{branch: int, partner: int}|null
     */
    private function parsePartnerBranch(?string $partnerBranch): ?array
    {
        if ($partnerBranch === null || $partnerBranch === '') {
            return null;
        }

        $parts = explode('_', $partnerBranch, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        if (! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
            return null;
        }

        $branchId = (int) $parts[0];
        $partnerId = (int) $parts[1];

        if ($branchId < 1 || $partnerId < 1) {
            return null;
        }

        $branch = PartnerBranch::where('id', $branchId)->where('partner_id', $partnerId)->first();
        if (! $branch) {
            return null;
        }

        if (! Partner::where('id', $partnerId)->exists()) {
            return null;
        }

        return [
            'branch' => $branchId,
            'partner' => $partnerId,
        ];
    }

    public function saveapplication(Request $request)
    {
        $response = [
            'status' => false,
            'message' => 'Please try again',
        ];

        $client = $this->resolveAccessibleApplicationClient($request->client_id, true);
        if (! $client) {
            $this->unauthorizedJsonResponse();

            return;
        }

        $enrolmentType = $request->input('enrolment_type');
        if (! array_key_exists($enrolmentType, Application::enrolmentTypeOptions())) {
            $response['message'] = 'Please select an Enrolment Type.';
            echo json_encode($response);

            return;
        }

        $companyName = $request->input('company_name');
        if (! array_key_exists($companyName, Application::companyNameOptions())) {
            $response['message'] = 'Please select a Company Name.';
            echo json_encode($response);

            return;
        }

        $parsedPartnerBranch = $this->parsePartnerBranch($request->input('partner_branch'));
        if ($parsedPartnerBranch === null) {
            $response['message'] = 'Please select a Partner & Branch.';
            echo json_encode($response);

            return;
        }

        $workflow = $request->workflow;
        $workflowstage = WorkflowStage::where('w_id', $workflow)->orderby('id', 'asc')->first();
        if (! $workflowstage) {
            $response['message'] = 'This workflow has no stages. Please select a valid workflow.';
            echo json_encode($response);

            return;
        }

        $partner = $parsedPartnerBranch['partner'];
        $branch = $parsedPartnerBranch['branch'];
        $product = $request->product;
        $client_id = $client->id;
        $status = 0;
        $stage = $workflowstage->name;
        $sale_forcast = 0.00;
        $obj = new Application;
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
        if ($saved) {
            $productdetail = Product::where('id', $product)->first();
            $partnerdetail = Partner::where('id', $partner)->first();
            $PartnerBranch = PartnerBranch::where('id', $branch)->first();
            $subject = 'has started an application';
            $objs = new ActivitiesLog;
            $objs->client_id = $client_id;
            $objs->created_by = Auth::user()->id;
            $objs->description = '<span class="text-semi-bold">'.@$productdetail->name.'</span><p>'.@$partnerdetail->partner_name.' ('.@$PartnerBranch->name.')</p>';
            $objs->subject = $subject;
            $objs->task_status = 0; // Required NOT NULL field (0 = activity, 1 = task)
            $objs->pin = 0; // Required NOT NULL field (0 = not pinned, 1 = pinned)
            $objs->save();
            $response['status'] = true;
            $response['message'] = 'You\'ve successfully updated your client\'s information.';
            $response['application_id'] = $obj->id;
            $response['client_id'] = $client->id;
            $response['client_email'] = $client->email ?? '';
            $response['client_name'] = trim(($client->first_name ?? '').' '.($client->last_name ?? ''));
        } else {
            $response['status'] = false;
            $response['message'] = 'Please try again';
        }

        echo json_encode($response);
    }

    public function getapplicationlists(Request $request)
    {
        $client = $this->resolveAccessibleApplicationClient($request->id, false);
        if (! $client) {
            return '';
        }

        $applications = Application::eagerForClientDetailList((int) $client->id);
        $applicationAssignCounts = Application::openClientActionCountsByApplicationId((int) $client->id, $applications->pluck('id'));
        ob_start();
        foreach ($applications as $alist) {
            $productdetail = $alist->product;
            $partnerdetail = $alist->partner;
            $PartnerBranch = $alist->branch;
            $workflow = $alist->workflow;

            $application_assign_count = (int) $applicationAssignCounts->get($alist->id, 0);
            // dd($application_assign_count);
            ?>
				<tr id="id_<?php echo $alist->id; ?>">
				<td>
                  <a class="openapplicationdetail" data-id="<?php echo $alist->id; ?>" href="javascript:;" style="display:block;">
                  <?php echo @$productdetail->name; ?>
                  <?php if ($application_assign_count > 0) { ?>
                            <span class="countTotalActivityAction" style="background: #1f1655;padding: 0px 5px;border-radius: 50%;color: #fff;margin-left: 5px;"><?php echo $application_assign_count; ?></span>
                  <?php } ?>
                  </a> 
                  <small><?php echo @$partnerdetail->partner_name; ?>(<?php echo @$PartnerBranch->name; ?>)</small>
                </td>
				<td><?php echo @$workflow->name; ?></td>
				<td><?php echo @$alist->stage; ?></td>
				<td>
                    <?php if ($alist->status == 0) { ?>
                        <span class="ag-label--circular" style="color: #6777ef" >In Progress</span>
                    <?php } elseif ($alist->status == 1) { ?>
                        <span class="ag-label--circular" style="color: #6777ef" >Completed</span>
                    <?php } elseif ($alist->status == 2) { ?>
                        <span class="ag-label--circular" style="color: red;" >Discontinued</span>
                    <?php } elseif ($alist->status == 3) { ?>
                        <span class="ag-label--circular" style="color: red;" >Cancelled</span>
                    <?php } elseif ($alist->status == 4) { ?>
                        <span class="ag-label--circular" style="color: red;" >Withdrawn</span>
                    <?php } elseif ($alist->status == 5) { ?>
                        <span class="ag-label--circular" style="color: red;" >Deferred</span>
                    <?php } elseif ($alist->status == 6) { ?>
                        <span class="ag-label--circular" style="color: red;" >Future</span>
                    <?php } elseif ($alist->status == 7) { ?>
                        <span class="ag-label--circular" style="color: red;" >VOE</span>
                    <?php } elseif ($alist->status == 8) { ?>
                            <span class="ag-label--circular" style="color: red;" >Refund</span>
                    <?php } ?>
                </td>

				<td><?php if (@$alist->start_date != '') {
				    echo date('d/m/Y', strtotime($alist->start_date));
				} ?></td>
				<td><?php if (@$alist->end_date != '') {
				    echo date('d/m/Y', strtotime($alist->end_date));
				} ?></td>
				<td><?php echo e(Application::enrolmentTypeLabel($alist->enrolment_type ?? null) ?: '—'); ?></td>
				<td><?php echo e(Application::companyNameLabel($alist->company_name ?? null) ?: '—'); ?></td>
			</tr>
				<?php
        }

        return ob_get_clean();
    }
}
