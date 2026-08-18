<!-- Appliation Modal -->
<div class="modal fade add_appliation custom_modal"  tabindex="-1" role="dialog" aria-labelledby="applicationModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="addApplicationModalLabel">Add Application</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/saveapplication')}}" name="applicationform" id="addapplicationformform" autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="client_id" value="{{$fetchedData->id}}">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="workflow">Select Workflow <span class="span_req">*</span></label>
								<select data-valid="required" class="form-control workflow tomselect" id="workflow" name="workflow">
									<option value="">Please Select a Workflow</option>
									@foreach(\App\Models\Workflow::all() as $wlist)
										<option value="{{$wlist->id}}">{{$wlist->name}}</option>
									@endforeach
								</select>
								<span class="custom-error workflow_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="partner_branch">Select Partner & Branch <span class="span_req">*</span></label>
								<select data-valid="required" class="form-control partner_branch tomselect" id="partner" name="partner_branch">
									<option value="">Please Select a Partner & Branch</option>
								</select>
								<span class="custom-error partner_branch_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="product">Select Product</label>
								<select data-valid="required" class="form-control product tomselect" id="product" name="product">
									<option value="">Please Select a Product</option>

								</select>
								<span class="custom-error product_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="enrolment_type">Enrolment Type <span class="span_req">*</span></label>
								<select data-valid="required" class="form-control" id="enrolment_type" name="enrolment_type">
									<option value="">Please Select Enrolment Type</option>
									@foreach(\App\Models\Application::enrolmentTypeOptions() as $value => $label)
										<option value="{{ $value }}">{{ $label }}</option>
									@endforeach
								</select>
								<span class="custom-error enrolment_type_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="company_name">Company Name <span class="span_req">*</span></label>
								<select data-valid="required" class="form-control" id="company_name" name="company_name">
									<option value="">Please Select Company Name</option>
									@foreach(\App\Models\Application::companyNameOptions() as $value => $label)
										<option value="{{ $value }}">{{ $label }}</option>
									@endforeach
								</select>
								<span class="custom-error company_name_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<div class="form-check">
									<input class="form-check-input" type="checkbox" name="send_checklist_after" id="send_checklist_after" value="1">
									<label class="form-check-label" for="send_checklist_after">Send Checklist</label>
								</div>
								<small class="text-muted">Open email popup after creating so you can select template and attach checklist to send to client.</small>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('applicationform')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>


<!-- Appliation Modal -->
<div class="modal fade custom_modal" id="discon_application" tabindex="-1" role="dialog" aria-labelledby="applicationModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="disconApplicationModalLabel">Discontinue Application</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/discontinue_application')}}" name="discontinue_application" id="discontinue_application" autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="diapp_id" value="">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="workflow">Discontinue Reason <span class="span_req">*</span></label>
								<select data-valid="required" class="form-control workflow" id="workflow" name="workflow">
									<option value="">Please Select</option>
									<option value="Change of Application">Change of Application</option>
									<option value="Error by Team Member">Error by Team Member</option>
									<option value="Financial Difficulties">Financial Difficulties</option>
									<option value="Loss of competitor">Loss of competitor</option>
									<option value="Other Reasons">Other Reasons</option>
                                </select>
								<span class="custom-error workflow_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="">Notes <span class="span_req">*</span></label>
								<textarea data-valid="required"  class="form-control" name="note"></textarea>

							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('discontinue_application')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<div class="modal fade custom_modal" id="revert_application" tabindex="-1" role="dialog" aria-labelledby="applicationModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="revertApplicationModalLabel">Revert Discontinued Application</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/revert_application')}}" name="revertapplication" id="revertapplication" autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="revapp_id" value="">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="">Notes <span class="span_req">*</span></label>
								<textarea data-valid="required"  class="form-control" name="note"></textarea>

							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('revertapplication')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<!-- Note & Terms Modal -->
<div class="modal fade custom_modal" id="create_note" tabindex="-1" role="dialog" aria-labelledby="create_noteModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="createNoteModalLabel">Create Note</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/create-note')}}" name="notetermform" autocomplete="off" id="notetermform" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="client_id" value="{{$fetchedData->id}}">
				<input type="hidden" name="noteid" value="">
				<input type="hidden" name="mailid" value="0">
				<input type="hidden" name="vtype" value="client">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="title">Title <span class="span_req">*</span></label>
								<select name="title" class="form-control" data-valid="required">
								    <option value="">Please Select Note</option>
								    <option value="Call">Call</option>
								    <option value="Email">Email</option>
								    <option value="In-Person">In-Person</option>
								    <option value="Others">Others</option>
								    <option value="Attention">Attention</option>
								</select>
								<span class="custom-error title_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="description">Description <span class="span_req">*</span></label>
								<textarea  class="tinymce-simple" name="description" data-valid="required"></textarea>
								<span class="custom-error title_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<!--<div class="col-12 col-md-12 col-lg-12 is_not_note" style="display:none;">
							<div class="form-group">
								<label class="d-block" for="">Related To</label>
								<div class="form-check form-check-inline">
									<input class="" type="radio" id="note_contact" value="Contact" name="related_to_note" checked>
									<label style="padding-left: 6px;" class="form-check-label" for="note_contact">Contact</label>
								</div>
								<div class="form-check form-check-inline">
									<input class="" type="radio" id="note_partner" value="Partner" name="related_to_note">
									<label style="padding-left: 6px;" class="form-check-label" for="note_partner">Partner</label>
								</div>
								<div class="form-check form-check-inline">
									<input class="" type="radio" id="note_application" value="Application" name="related_to_note">
									<label style="padding-left: 6px;" class="form-check-label" for="note_application">Application</label>
								</div>

							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12 is_not_note" style="display:none;">
							<div class="form-group">
								<label for="contact_name">Contact Name <span class="span_req">*</span></label>
								<select data-valid="" class="form-control contact_name js-data-example-ajaxcc" name="contact_name[]" id="contact_name_select">


								</select>
								<span class="custom-error contact_name_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>-->
						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('notetermform')" type="button" class="btn btn-primary">Submit</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<div class="modal fade custom_modal" id="create_note_d" tabindex="-1" role="dialog" aria-labelledby="create_noteModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="createNoteDModalLabel">Create Note</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
		<div class="modal-body">
			<form method="post" action="{{URL::to('/create-note')}}" name="notetermform_n" autocomplete="off" id="notetermform_n" enctype="multipart/form-data">
			@csrf
			<input type="hidden" name="client_id" id="note_client_id" value="{{$fetchedData->id}}">
			<input type="hidden" name="noteid" value="">
			<input type="hidden" name="mailid" value="0">
			<input type="hidden" name="vtype" value="client">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="title">Title <span class="span_req">*</span></label>
								<select name="title" class="form-control" data-valid="required" id="noteType">
								    <option value="">Please Select Note</option>
								    <option value="Call">Call</option>
								    <option value="Email">Email</option>
								    <option value="In-Person">In-Person</option>
								    <option value="Others">Others</option>
								    <option value="Attention">Attention</option>
								</select>

                                <!-- Container for additional inputs -->
						        <div id="additionalFields"></div>

								<span class="custom-error title_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="description">Description <span class="span_req">*</span></label>
								<textarea  class="tinymce-simple" name="description" data-valid="required"></textarea>
								<span class="custom-error title_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>

						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('notetermform_n')" type="button" class="btn btn-primary">Submit</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- Note & Terms Modal -->
<div class="modal fade custom_modal" id="view_note" tabindex="-1" role="dialog" aria-labelledby="view_noteModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="note_col">
					<div class="note_content">
						<h5></h5>
						<p></p>
						<div class="extra_content">

						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Note & Terms Modal -->
<div class="modal fade custom_modal" id="view_application_note" tabindex="-1" role="dialog" aria-labelledby="view_noteModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="note_col">
					<div class="note_content">
						<h5></h5>
						<p></p>
						<div class="extra_content">

						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Note & Terms Modal -->
<div class="modal fade custom_modal" id="opencommissionmodal" tabindex="-1" role="dialog" aria-labelledby="create_noteModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="commissionInvoiceModalLabel">Commission Invoice</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/create-invoice')}}" name="noteinvform" autocomplete="off" id="noteinvform" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="client_id" value="{{$fetchedData->id}}">

					<div class="row">
						<div class="col-12 col-md-6 col-lg-6">
						<?php
						$timelist = \DateTimeZone::listIdentifiers(DateTimeZone::ALL);
						?>
							<div class="form-group">
								<label style="display:block;" for="invoice_type">Choose invoice:</label>
								<div class="form-check form-check-inline">
									<input class="form-check-input" type="radio" id="net_invoice" value="1" name="invoice_type" checked>
									<label class="form-check-label" for="net_invoice">Net Claim Invoice</label>
								</div>
								<div class="form-check form-check-inline">
									<input class="form-check-input" type="radio" id="gross_invoice" value="2" name="invoice_type">
									<label class="form-check-label" for="gross_invoice">Gross Claim Invoice</label>
								</div>
								<span class="custom-error related_to_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="client">Client <span class="span_req">*</span></label>
								{!! Form::text('client', @$fetchedData->first_name.' '.@$fetchedData->last_name, array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'' ))  !!}
								<span class="custom-error title_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="description">Application <span class="span_req">*</span></label>
								<select data-valid="required" class="form-control tomselect" name="application">
									<option value="">Select</option>
									@foreach(\App\Models\Application::where('client_id',$fetchedData->id)->get() as $aplist)
									<?php
									$productdetail = \App\Models\Product::where('id', $aplist->product_id)->first();
				$partnerdetail = \App\Models\Partner::where('id', $aplist->partner_id)->first();
				$PartnerBranch = \App\Models\PartnerBranch::where('id', $aplist->branch)->first();
				$workflow = \App\Models\Workflow::where('id', $aplist->workflow)->first();
									?>
										<option value="{{$aplist->id}}">{{@$productdetail->name}} ({{@$partnerdetail->partner_name}})</option>
									@endforeach
								</select>

							</div>
						</div>

						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('noteinvform')" type="button" class="btn btn-primary">Submit</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- Note & Terms Modal -->
<div class="modal fade custom_modal" id="opengeneralinvoice" tabindex="-1" role="dialog" aria-labelledby="create_noteModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="generalInvoiceModalLabel">General Invoice</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/create-invoice')}}" name="notegetinvform" autocomplete="off" id="notegetinvform" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="client_id" value="{{$fetchedData->id}}">

					<div class="row">
						<div class="col-12 col-md-6 col-lg-6">
						<?php
						$timelist = \DateTimeZone::listIdentifiers(DateTimeZone::ALL);
						?>
							<div class="form-group">
								<label style="display:block;" for="invoice_type">Choose invoice:</label>
								<div class="form-check form-check-inline">
									<input class="form-check-input" type="radio" id="net_invoice" value="3" name="invoice_type" checked>
									<label class="form-check-label" for="net_invoice">Client Invoice</label>
								</div>

								<span class="custom-error related_to_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="client">Client <span class="span_req">*</span></label>
								{!! Form::text('client', @$fetchedData->first_name.' '.@$fetchedData->last_name, array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'' ))  !!}
								<span class="custom-error title_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="description">Service <span class="span_req">*</span></label>
								<select data-valid="required" class="form-control tomselect" name="application">
									<option value="">Select</option>
									@foreach(\App\Models\Application::where('client_id',$fetchedData->id)->select('workflow')->distinct()->get() as $aplist)
									<?php

				$workflow = \App\Models\Workflow::where('id', $aplist->workflow)->first();
									?>
										<option value="{{$workflow->id}}">{{$workflow->name}}</option>
									@endforeach
								</select>

							</div>
						</div>

						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('notegetinvform')" type="button" class="btn btn-primary">Submit</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<div id="addpaymentmodal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="false" class="modal fade" >
	<div class="modal-dialog">
	{!! Form::open(array('url' => 'invoice/payment-store', 'method' => 'post', 'name'=>"ajaxinvoicepaymentform", 'autocomplete'=>'off', "enctype"=>"multipart/form-data", "id"=>"ajaxinvoicepaymentform"))  !!}
	<input type="hidden" value="" name="invoice_id" id="invoice_id">
	<input type="hidden" value="true" name="is_ajax" id="invoice_is_ajax">
	<input type="hidden" value="{{$fetchedData->id}}" name="client_id" id="payment_invoice_client_id">
		<div class="modal-content ">
			<div class="modal-header">
				<h4 class="modal-title">Payment Details</h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
			</div>
			<div class="modal-body">

				<div class="payment_field">
					<div class="payment_field_row">
						<div class="payment_field_col payment_first_step">
							<div class="field_col">
								<div class="label_input">
									<input data-valid="required" type="number" name="payment_amount[]" placeholder="" class="paymentAmount" />
									<div class="basic_label">AUD</div>
								</div>
							</div>

							<div class="field_col">
								<select name="payment_mode[]" class="form-control">
									<option value="Cheque">Cheque</option>
									<option value="Cash">Cash</option>
									<option value="Credit Card">Credit Card</option>
									<option value="Bank Transfers">Bank Transfers</option>
								</select>
							</div>
							<div class="field_col">
								<div class="input-group">
									<span class="input-group-text">
										@icon('clock')
									</span>
									<input type="text" name="payment_date[]" placeholder="" class="datepicker form-control" />
								</div>
								<span class="span_note">Date must be in YYYY-MM-DD (2012-12-22) format.</span>
							</div>
							<div class="field_remove_col">
								<a href="javascript:;" class="remove_col">@icon('times')</a>
							</div>
						</div>
					</div>
					<div class="add_payment_field">
						<a href="javascript:;">@icon('plus') Add New Line</a>
					</div>
					<div class="clearfix"></div>
					<div class="invoiceamount">
						<table class="table">
							<tr>
								<td><b>Invoice Amount:</b></td>
								<td class="invoicenetamount"></td>
								<td><b>Total Due:</b></td>
								<td class="totldueamount" data-totaldue=""></td>
							</tr>

						</table>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" onclick="customValidate('ajaxinvoicepaymentform')" class="btn btn-primary" >Save & Close</button>
				<button type="button" class="btn btn-primary">Save & Send Receipt</button>
			  </div>
		</div>
		</form>
	</div>
</div>

<div class="modal fade custom_modal" id="create_applicationnote" tabindex="-1" role="dialog" aria-labelledby="create_noteModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="appNoteModalLabel">Create Note</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/create-app-note')}}" name="appnotetermform" autocomplete="off" id="appnotetermform" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="client_id" value="{{$fetchedData->id}}">
				<input type="hidden" name="noteid" id="noteid" value="">
				<input type="hidden" name="type" id="type" value="">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="title">Title <span class="span_req">*</span></label>
								{!! Form::text('title', '', array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Enter Title' ))  !!}
								<span class="custom-error title_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="description">Description <span class="span_req">*</span></label>
								<textarea class="tinymce-simple" name="description" data-valid="required"></textarea>
								<span class="custom-error title_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>

						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('appnotetermform')" type="button" class="btn btn-primary">Submit</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- Checklist Modal -->
<div class="modal fade custom_modal" id="create_checklist" tabindex="-1" role="dialog" aria-labelledby="create_checklistModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="checklistModalLabel">Add New Checklist</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/add-checklists')}}" name="checklistform" id="checklistform" autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="client_id" value="{{$fetchedData->id}}">
				<input type="hidden" id="checklistapp_id" name="app_id" value="{{$fetchedData->id}}">
				<input type="hidden" id="checklist_type" name="type" value="">
				<input type="hidden" id="checklist_typename" name="typename" value="">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="document_type">Document Type <span class="span_req">*</span></label>
							<select class="form-control tomselect" id="document_type" name="document_type" data-valid="required">
								<option value="">Please Select Document Type</option>
								<?php foreach(\App\Models\Checklist::all() as $checklist){ ?>
								<option value="{{$checklist->name}}">{{$checklist->name}}</option>
								<?php } ?>
							</select>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="description">Description</label>
								<textarea class="form-control" id="checklistdesc" name="description" placeholder="Description"></textarea>
								<span class="custom-error description_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<div class="form-check form-check-inline">
									<input value="1" class="" type="checkbox" value="Make this as mandatory inorder to proceed next stage" name="proceed_next_stage">
									<label style="padding-left: 8px;" class="form-check-label" for="proceed_next_stage">Make this as mandatory inorder to proceed next stage</label>
								</div>
							</div>
						</div>
					</div>
					<div class="due_date_sec">
						<a href="javascript:;" class="btn btn-primary due_date_btn">@icon('plus') Add Due Date</a>
						<input type="hidden" value="0" class="checklistdue_date" name="due_date">
						<div class="due_date_col">
							<div class="row">
								<div class="col-12 col-md-6 col-lg-6">
									<div class="form-group">
										<label for="appoint_date">Date</label>
										<div class="input-group">
											<span class="input-group-text">
												@icon('calendar-alt')
											</span>
											{!! Form::text('appoint_date', '', array('class' => 'form-control datepicker', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Select Date' ))  !!}
										</div>
										<span class="span_note">Date must be in YYYY-MM-DD (2012-12-22) format.</span>
										<span class="custom-error appoint_date_error" role="alert">
											<strong></strong>
										</span>
									</div>
								</div>
								<div class="col-12 col-md-5 col-lg-5">
									<div class="form-group">
										<label for="appoint_time">Time</label>
										<div class="input-group">
											<span class="input-group-text">
												@icon('clock')
											</span>
											{!! Form::time('appoint_time', '', array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Select Time' ))  !!}
										</div>
										<span class="custom-error appoint_time_error" role="alert">
											<strong></strong>
										</span>
									</div>
								</div>
								<div class="col-12 col-md-1 col-lg-1 remove_col">
									<a href="javascript:;" class="remove_btn">@icon('trash')</a>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('checklistform')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- NOTE: Payment Schedule modals removed - Invoice Schedule feature has been removed -->
<!--
<div class="modal fade custom_modal paymentschedule" id="create_paymentschedule" tabindex="-1" role="dialog" aria-labelledby="create_paymentscheduleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="paymentscheduleModalLabel">Add Payment Schedule</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="#" name="paymentform" id="paymentform" autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="client_id" value="{{$fetchedData->id}}">
					<div class="row">
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="client_name">Client Name</label>
								{!! Form::text('client_name', '', array('class' => 'form-control', 'autocomplete'=>'off', 'data-valid'=>'', 'placeholder'=>'Enter Client Name' ))  !!}
								<span class="custom-error client_name_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="application">Application</label>
								{!! Form::text('application', '', array('class' => 'form-control', 'autocomplete'=>'off', 'data-valid'=>'', 'placeholder'=>'Enter Application' ))  !!}
								<span class="custom-error application_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="installment_name">Installment Name <span class="span_req">*</span></label>
								{!! Form::text('installment_name', '', array('class' => 'form-control', 'autocomplete'=>'off', 'data-valid'=>'required', 'placeholder'=>'Enter Installment Name' ))  !!}
								<span class="custom-error installment_name_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="installment_date">Installment Date <span class="span_req">*</span></label>
								<div class="input-group">
									<span class="input-group-text">
										@icon('calendar-alt')
									</span>
									{!! Form::text('installment_date', '', array('class' => 'form-control datepicker', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Select Date' ))  !!}
								</div>
								<span class="span_note">Date must be in YYYY-MM-DD (2012-12-22) format.</span>
								<span class="custom-error installment_date_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="fees_type_sec">
								<div class="fee_type_row">
									<div class="custom_type_col">
										<div class="feetype_field">
											<div class="form-group">
												<label for="fee_type">Fee Type <span class="span_req">*</span></label>
											</div>
										</div>
										<div class="feeamount_field">
											<div class="form-group">
												<label for="fee_amount">Fee Amount <span class="span_req">*</span></label>
											</div>
										</div>
										<div class="commission_field">
											<div class="form-group">
												<label for="commission_percent">Commission %</label>
											</div>
										</div>
										<div class="remove_field">
											<div class="form-group">
											</div>
										</div>
									</div>
									<div class="fees_type_col custom_type_col">
										<div class="feetype_field">
											<div class="form-group">
												<select class="form-control tomselect" name="fee_type" data-valid="required">
													<option value="">Select Fee Type</option>
													<option value="Accommodation Fee">Accommodation Fee</option>
													<option value="Administration Fee">Administration Fee</option>
													<option value="Airline Ticket">Airline Ticket</option>
													<option value="Airport Transfer Fee">Airport Transfer Fee</option>
													<option value="Application Fee">Application Fee</option>
													<option value="Bond">Bond</option>
													<option value="Tuition Fee">Tuition Fee</option>
												</select>
											</div>
										</div>
										<div class="feeamount_field">
											<div class="form-group">
												{!! Form::text('fee_amount', '', array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'0.00' ))  !!}
											</div>
										</div>
										<div class="commission_field">
											<div class="form-group">
												{!! Form::text('commission_percent', '', array('class' => 'form-control', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'0.00' ))  !!}
											</div>
										</div>
										<div class="remove_field">
											<a href="javascript:;" class="remove_btn">@icon('trash')</a>
										</div>
									</div>
								</div>
								<div class="clearfix"></div>
								<div class="discount_row">
									<div class="discount_col custom_type_col">
										<div class="feetype_field">
											<div class="form-group">
												<input class="form-control" placeholder="Discount" disabled />
											</div>
										</div>
										<div class="feeamount_field">
											<div class="form-group">
												{!! Form::text('discount_amount', '', array('class' => 'form-control', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'0.00' ))  !!}
											</div>
										</div>
										<div class="commission_field">
											<div class="form-group">
												{!! Form::text('dispcunt_commission_percent', '', array('class' => 'form-control', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'0.00' ))  !!}
											</div>
										</div>
										<div class="remove_field">
											<a href="javascript:;" class="remove_btn">@icon('trash')</a>
										</div>
									</div>
									<div class="clearfix"></div>
								</div>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="divider"></div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="add_fee_type">
								<a href="javascript:;" class="btn btn-outline-primary fee_type_btn">@icon('plus') Add Fee</a>
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6 text-end">
							<div class="total_fee">
								<h4>Total Fee (USD)</h4>
								<span>11.00</span>
							</div>
							<div class="net_fee">
								<span class="span_label">Net Fee</span>
								<span class="span_value">0.00</span>
							</div>
						</div>
						<div class="clearfix"></div>
						<div class="divider"></div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="schedule_title">
								<h4>Setup Invoice Scheduling</h4>
							</div>
							<span class="schedule_note">@icon('info-circle') Schedule your Invoices by selecting an Invoice date for this installment.</span>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="invoice_date">Invoice Date</label>
								<div class="input-group">
									<span class="input-group-text">
										@icon('calendar-alt')
									</span>
									{!! Form::text('invoice_date', '', array('class' => 'form-control datepicker', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Select Date' ))  !!}
								</div>
								<span class="span_note">Date must be in YYYY-MM-DD (2012-12-22) format.</span>
								<span class="custom-error installment_date_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<div class="form-check form-check-inline">
									<label class="form-check-label" for="allow_upload_docu">Auto Invoicing</label>
								</div>
								<span class="schedule_note">@icon('info-circle') Enabling Auto Invoicing will automatically create unpaid invoices at above stated Invoice Date.</span>
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="fee_type">Invoice Type <span class="span_req">*</span></label>
								<select class="form-control tomselect" name="fee_type" data-valid="required">
									<option value="">Select Invoice Type</option>
									<option value="Net Claim">Net Claim</option>
									<option value="Gross Claim">Gross Claim</option>
								</select>
							</div>
						</div>
					</div>
					<div class="clearfix"></div>
					<div class="divider"></div>
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('paymentform')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
-->


<div id="applicationemailmodal"  data-backdrop="static" data-keyboard="false" class="modal fade custom_modal" tabindex="-1" role="dialog" aria-labelledby="applicationEmailModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="applicationEmailModalLabel">Compose Email</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" name="appkicationsendmail" id="appkicationsendmail" action="{{URL::to('/application-sendmail')}}" autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="client_id" value="{{$fetchedData->id}}">
				<input type="hidden" id="application_email_type" name="type" value="application">
				<input type="hidden" id="application_email_appointid" name="noteid" value="">
				<input type="hidden"  name="atype" value="application">
					<div class="row">
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="email_from">From <span class="span_req">*</span></label>
								@include('partials.email-from-ses')
								@if ($errors->has('email_from'))
									<span class="custom-error" role="alert">
										<strong>{{ @$errors->first('email_from') }}</strong>
									</span>
								@endif
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="email_to">To <span class="span_req">*</span></label>
								<input type="text" readonly class="form-control" name="to" value="{{$fetchedData->first_name}} {{$fetchedData->last_name}}">
								<input type="hidden" class="form-control" name="to" value="{{$fetchedData->email}}">
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="email_cc">CC </label>
								<select data-valid="" class="js-data-example-ajaxccapp" name="email_cc[]" id="email_cc_select"></select>

								@if ($errors->has('email_cc'))
									<span class="custom-error" role="alert">
										<strong>{{ @$errors->first('email_cc') }}</strong>
									</span>
								@endif
							</div>
						</div>

						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="template">Templates </label>
								<select data-valid="" class="form-control tomselect selectapplicationtemplate" name="template" id="email_template_select">
									<option value="">Select</option>
									@foreach(\App\Models\CrmEmailTemplate::all() as $list)
										<option value="{{$list->id}}">{{$list->name}}</option>
									@endforeach
								</select>

							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="subject">Subject <span class="span_req">*</span></label>
								{!! Form::text('subject', '', array('class' => 'form-control selectedappsubject', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Enter Subject' ))  !!}
								@if ($errors->has('subject'))
									<span class="custom-error" role="alert">
										<strong>{{ @$errors->first('subject') }}</strong>
									</span>
								@endif
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="message">Message <span class="span_req">*</span></label>
								<textarea class="tinymce-simple selectedmessage" name="message"></textarea>
								@if ($errors->has('message'))
									<span class="custom-error" role="alert">
										<strong>{{ @$errors->first('message') }}</strong>
									</span>
								@endif
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('appkicationsendmail')" type="button" class="btn btn-primary">Send</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>


<!-- NOTE: Payment Schedule modals removed - Invoice Schedule feature has been removed -->
<!--
<div class="modal fade custom_modal paymentschedule" id="create_apppaymentschedule" tabindex="-1" role="dialog" aria-labelledby="create_paymentscheduleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="paymentscheduleModalLabel">Payment Schedule Setup</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/setup-paymentschedule')}}" name="setuppaymentschedule" id="setuppaymentschedule" autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="application_id" id="application_id" value="">
				<input type="hidden" name="is_ajax" id="is_ajax" value="true">
					<div class="row">
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="installment_date">Installment Date <span class="span_req">*</span></label>
								<div class="input-group">
									<span class="input-group-text">
										@icon('calendar-alt')
									</span>
									{!! Form::text('installment_date', '', array('class' => 'form-control datepicker', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Select Date' ))  !!}
								</div>
								<span class="span_note">Date must be in YYYY-MM-DD (2012-12-22) format.</span>
								<span class="custom-error installment_date_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="row">
								<div class="col-md-12">
									<label for="installment_date">Installment Interval  <span class="span_req">*</span></label>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										{!! Form::text('installment_no', 1, array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'' ))  !!}
									</div>
								</div>
								<div class="col-md-8">
									<div class="input-group">
										<select class="form-control" name="installment_intervel">
											<option value="">Select Intervel</option>
											<option value="Day">Day</option>
											<option value="Week">Week</option>
											<option value="Month">Month</option>
											<option value="Year">Year</option>

										</select>
									</div>
								</div>
							</div>
						</div>

						<div class="clearfix"></div>
						<div class="divider"></div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="schedule_title">
								<h4>Setup Invoice Scheduling</h4>
							</div>
							<span class="schedule_note">@icon('info-circle') Schedule your Invoices by selecting an Invoice date for this installment.</span>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="invoice_date">Invoice Date</label>
								<div class="input-group">
									<span class="input-group-text">
										@icon('calendar-alt')
									</span>
									{!! Form::text('invoice_date', '', array('class' => 'form-control datepicker', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Select Date' ))  !!}
								</div>
								<span class="span_note">Date must be in YYYY-MM-DD (2012-12-22) format.</span>
								<span class="custom-error installment_date_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>

					</div>
					<div class="clearfix"></div>
					<div class="divider"></div>
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('setuppaymentschedule')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
-->


<!-- NOTE: Payment Schedule modals removed - Invoice Schedule feature has been removed -->
<!--
<div class="modal fade custom_modal" id="editpaymentschedule" tabindex="-1" role="dialog" aria-labelledby="paymentscheModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="paymentscheModalLabel">Edit Payment Schedule</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body showeditmodule">

			</div>
		</div>
	</div>
</div>
-->


<!-- NOTE: Payment Schedule modals removed - Invoice Schedule feature has been removed -->
<!--
<div class="modal fade add_payment_schedule custom_modal" id="addpaymentschedule" tabindex="-1" role="dialog" aria-labelledby="paymentscheModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="paymentscheModalLabel">Add Payment Schedule</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body showpoppaymentscheduledata">

			</div>
		</div>
	</div>
</div>
-->

<!-- NOTE: opencreateinvoiceform modal removed - Invoice Schedule feature has been removed -->
<!--
<div class="modal fade custom_modal" id="opencreateinvoiceform" tabindex="-1" role="dialog" aria-labelledby="paymentscheModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="paymentscheModalLabel">Select Invoice Type:</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
		<div class="modal-body">
		<form method="post" action="{{URL::to('/create-invoice')}}" name="createinvoive"  autocomplete="off" enctype="multipart/form-data">
			@csrf
			<input type="hidden" name="client_id" id="invoice_client_id">
			<input type="hidden" name="application" id="app_id">
			<input type="hidden" name="schedule_id" id="schedule_id">
					<div class="row">
						<div class="col-4 col-md-4 col-lg-4">
							<div class="form-group">
								<label for="netclaim"><input id="netclaim" value="1" type="radio" name="invoice_type" > Net Claim</label>
							</div>
						</div>
						<div class="col-4 col-md-4 col-lg-4">
							<div class="form-group">
								<label for="grossclaim"><input value="2" id="grossclaim" type="radio" name="invoice_type" > Gross Claim</label>
							</div>
						</div>
						<div class="col-4 col-md-4 col-lg-4">
							<div class="form-group">
								<label for="geclaim"><input value="3" id="geclaim" type="radio" name="invoice_type" > Client General</label>
							</div>
						</div>
						<div class="col-4 col-md-4 col-lg-4">
							<div class="form-group">
								<button onclick="customValidate('createinvoive')" class="btn btn-info" type="button">Create</button>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
-->

<div class="modal fade custom_modal" id="uploadmail" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="paymentscheModalLabel">Upload Mail:</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
			<form method="post" action="{{URL::to('/upload-mail')}}" name="uploadmail"  autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="client_id" id="maclient_id">
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="">From <span class="span_req">*</span></label>
								<input type="text" data-valid="required" name="from" class="form-control">
							</div>
						</div>
						<div class="col-md-12">
							<div class="form-group">
								<label for="">To <span class="span_req">*</span></label>
								<input type="text" data-valid="required" name="to" class="form-control">
							</div>
						</div>
						<div class="col-md-12">
							<div class="form-group">
								<label for="">Subject <span class="span_req">*</span></label>
								<input type="text" data-valid="required" name="subject" class="form-control">
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="message">Message <span class="span_req">*</span></label>
								<textarea data-valid="required" class="tinymce-simple selectedmessage" name="message"></textarea>

							</div>
						</div>
						<div class="col-4 col-md-4 col-lg-4">
							<div class="form-group">
								<button onclick="customValidate('uploadmail')" class="btn btn-info" type="button">Create</button>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<div class="modal fade custom_modal" id="openfileuploadmodal" tabindex="-1" role="dialog" aria-labelledby="paymentscheModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="paymentscheModalLabel">Upload Document</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="row">
				<style>
#ddArea {height: 200px;border: 2px dashed #ccc;line-height: 200px;font-size: 20px;background: #f9f9f9;margin-bottom: 15px;}
.drag_over {color: #000;border-color: #000;}
.thumbnail {width: 100px;height: 100px;padding: 2px;margin: 2px;border: 2px solid lightgray;border-radius: 3px;float: left;}
.d-none {display: none;}
				</style>
					<div class="col-md-8">
					<input type="hidden" class="checklisttype" value="">
					<input type="hidden" class="checklisttypename" value="">
					<input type="hidden" class="checklistid" value="">
					<input type="hidden" class="application_id" value="">
                    <input type="hidden" class="app_doc_client_id" value="">
						<div id="ddArea" style="text-align: center;">
							Click or drag to upload new file from your device

							<a style="display: none;" class="bg-transparent hover:bg-blue-500 text-blue-700 font-semibold hover:text-white py-2 px-4 border border-blue-500 hover:border-transparent ">

							</a>
						</div>
                        <div id="uploadSummary" class="alert" style="display: none; margin-top: 12px;"></div>

						<input type="file" class="d-none" id="selectfile" />
					</div>
					<div class="col-md-4">
						<div id="showThumb">
							<ul>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>



<!-- Create Client Receipt Modal -->
<style>
/* Reduce padding in client receipt table for better visibility */
.client-receipt-table td {
    padding: 0.2rem 0.35rem !important;
    vertical-align: middle !important;
}

.client-receipt-table th {
    padding: 0.3rem 0.35rem !important;
    font-size: 0.813rem;
    vertical-align: middle !important;
    font-weight: 600;
}

.client-receipt-table .form-control {
    padding: 0.2rem 0.4rem !important;
    font-size: 0.813rem;
    height: auto !important;
    min-height: 28px;
    line-height: 1.2;
}

.client-receipt-table select.form-control {
    padding: 0.15rem 0.4rem !important;
}

.client-receipt-table .currencyinput {
    display: flex;
    align-items: center;
    gap: 0.15rem;
}

.client-receipt-table .currencyinput span {
    font-size: 0.813rem;
    padding: 0;
    margin: 0;
}

.client-receipt-table .removeitems {
    padding: 0.15rem;
    display: inline-block;
    font-size: 0.875rem;
}

.receipt-totals-row td {
    padding: 0.3rem 0.35rem !important;
    font-size: 0.875rem;
}
</style>

<div class="modal fade custom_modal" id="createclientreceiptmodal" tabindex="-1" role="dialog" aria-labelledby="create_noteModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="clientReceiptModalLabel">Create Client Receipt</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
                <input type="hidden" id="top_value_db" value="">
				<form method="post" action="{{URL::to('/clients/saveaccountreport')}}" name="create_client_receipt" autocomplete="off" id="create_client_receipt" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="client_id" value="{{$fetchedData->id}}">
                <input type="hidden" name="loggedin_userid" value="{{@Auth::user()->id}}">
                <input type="hidden" name="receipt_type" value="1">
                <input type="hidden" name="function_type" id="function_type" value="">
					<div class="row">
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="client">Client <span class="span_req">*</span></label>
								{!! Form::text('client', @$fetchedData->first_name.' '.@$fetchedData->last_name, array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'' ))  !!}
								<span class="custom-error title_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>

						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="receipt_application_id">Application / Course</label>
								<select class="form-control" name="application_id" id="receipt_application_id" data-apps-loaded="{{ ($clientApplications ?? null) !== null ? '1' : '0' }}">
									<option value="">Unallocated</option>
									@foreach(($clientApplications ?? collect()) as $app)
										<?php
										$productName = $app->product->name ?? 'N/A';
										$partnerName = $app->partner->partner_name ?? '';
										$label = $productName . ($partnerName ? ' – ' . $partnerName : '');
										?>
										<option value="{{ $app->id }}">{{ $label }}</option>
									@endforeach
								</select>
								<small class="form-text text-muted">Optional. Links this payment to a specific course/application.</small>
							</div>
						</div>

						<div class="col-12 col-md-12 col-lg-12" id="reassignment_reason_wrapper" style="display:none;">
							<div class="form-group">
								<label for="reassignment_reason">Reason for change <span class="span_req">*</span></label>
								<textarea class="form-control" name="reassignment_reason" id="reassignment_reason" rows="2" placeholder="e.g. Transfer to migration – client pursuing skilled visa"></textarea>
								<small class="form-text text-muted">Required when reassigning payment to a different application.</small>
							</div>
						</div>

                        <div class="col-6 col-md-6 col-lg-6 d-none">
                            <div class="form-group">
                                <label for="agent_id">Agent <span class="span_req">*</span></label>
                                <select class="form-control tomselect" name="agent_id" id="sel_client_agent_id">
                                    <option value="">Select Agent</option>
                                    @foreach(\App\Models\Agent::where('status',1)->get() as $aplist)
                                        <option value="{{$aplist->id}}">{{@$aplist->full_name}} ({{@$aplist->email}})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label class="mb-2">Receipt Details</label>
                                <div class="table-responsive">
                                    <table class="table table-bordered text_wrap table-striped table-hover table-md vertical_align client-receipt-table">
                                        <thead>
                                            <tr>
                                                <th style="width:14%;">Trans. Date</th>
                                                <th style="width:14%;">Entry Date</th>
                                                <th style="width:13%;">Trans. No</th>
                                                <th style="width:14%;">Payment Method</th>
                                                <th style="width:30%;">Description</th>
                                                <th style="width:12%;">Deposit</th>
                                                <th style="width:3%; text-align:center;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="productitem">
                                            <tr class="clonedrow">
                                                <td>
                                                    <input data-valid="required" class="form-control report_date_fields" name="trans_date[]" type="text" value="" />
                                                </td>
                                                <td>
                                                    <input data-valid="required" class="form-control report_entry_date_fields" name="entry_date[]" type="text" value="" />
                                                </td>
                                                <td>
                                                    <input class="form-control unique_trans_no" type="text" value="" readonly/>
                                                    <input class="unique_trans_no_hidden" name="trans_no[]" type="hidden" value="" />
                                                </td>
                                                <td>
                                                    <select data-valid="required" class="form-control" name="payment_method[]">
                                                        <option value="">Select</option>
                                                        <option value="Cash">Cash</option>
                                                        <option value="Bank transfer">Bank transfer</option>
                                                        <option value="EFTPOS">EFTPOS</option>
                                                        <option value="Paid to College">Paid to College</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input data-valid="required" class="form-control" name="description[]" type="text" value="" />
                                                </td>
                                                <td>
                                                    <div class="currencyinput">
                                                        <span>$</span>
                                                        <input data-valid="required" class="form-control deposit_amount_per_row" name="deposit_amount[]" type="text" value="" />
                                                    </div>
                                                </td>
                                                <td style="text-align:center;">
                                                    <a class="removeitems text-danger" href="javascript:;" title="Remove row">
                                                        @icon('times')
                                                    </a>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr class="receipt-totals-row">
                                                <td colspan="5" style="text-align:right; font-weight:600; padding-right:15px;">Totals</td>
                                                <td colspan="2" style="font-weight:600;">
                                                    <span class="total_deposit_amount_all_rows"></span>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
								<div class="mt-2">
									<a href="javascript:;" class="openproductrinfo btn btn-sm btn-outline-primary">
										@icon('plus') Add New Line
									</a>
								</div>
                            </div>
						</div>

						<div class="col-12 col-md-12 col-lg-12 mt-3">
							<div class="upload_client_receipt_document">
								<input type="hidden" name="type" value="client">
								<input type="hidden" name="doctype" value="client_receipt">
								<button type="button" class="btn btn-outline-primary btn-sm upload-receipt-doc-btn">
									@icon('plus') Add Document
								</button>
								<input class="docclientreceiptupload d-none" type="file" name="document_upload[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"/>
								<div class="selected-file-info mt-2" style="display:none;">
									<span class="badge bg-success">
										@icon('file') <span class="file-name-display"></span>
										<button type="button" class="btn-close btn-close-white btn-sm ms-2 remove-selected-file" style="font-size:0.7rem;"></button>
									</span>
								</div>
							</div>
						</div>
                    </div>
				</form>
            </div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				<button onclick="customValidate('create_client_receipt')" type="button" class="btn btn-primary">Save Receipt</button>
			</div>
		</div>
	</div>
</div>

<!-- Create Client Refund Modal -->
<div class="modal fade custom_modal" id="createclientrefundmodal" tabindex="-1" role="dialog" aria-labelledby="createRefundModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="createRefundModalLabel">Create Refund</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{ url('/clients/saverefund') }}" name="create_client_refund" id="create_client_refund" autocomplete="off">
				@csrf
				<input type="hidden" name="client_id" value="{{ $fetchedData->id ?? '' }}">
				<input type="hidden" name="parent_receipt_id" id="refund_parent_receipt_id" value="">
				<input type="hidden" name="application_id" id="refund_application_id" value="">
				<div class="form-group mb-3">
					<label>Original Receipt</label>
					<p class="form-control-plaintext" id="refund_original_display"></p>
				</div>
				<div class="form-group mb-3">
					<label for="refund_amount">Refund Amount <span class="span_req">*</span></label>
					<div class="input-group">
						<span class="input-group-text">$</span>
						<input type="number" step="0.01" min="0.01" class="form-control" name="refund_amount" id="refund_amount" data-valid="required" placeholder="0.00">
					</div>
				</div>
				<div class="form-group mb-3">
					<label for="refund_reason">Refund Reason <span class="span_req">*</span></label>
					<textarea class="form-control" name="refund_reason" id="refund_reason" rows="3" data-valid="required" placeholder="e.g. Client withdrew, Duplicate payment"></textarea>
				</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary" onclick="customValidate('create_client_refund')">Save Refund</button>
			</div>
		</div>
	</div>
</div>

<!-- Create All document Docs Modal -->
<div class="modal fade create_alldocument_docs custom_modal" id="openalldocsmodal" tabindex="-1" role="dialog" aria-labelledby="checklistModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="checklistModalLabel">Add Checklist</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{route('clients.addalldocchecklist')}}" name="alldocs_upload_form" id="alldocs_upload_form" autocomplete="off"  enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="clientid" value="{{$fetchedData->id}}">
                    <input type="hidden" name="type" value="client">
                    <input type="hidden" name="doctype" value="documents">
                    <input type="hidden" name="category_id" id="alldocs_category_id" value="">

                    <div class="row">
                        <div class="col-6 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="checklist">Select Checklist<span class="span_req">*</span></label>
							<select data-valid="required" class="form-control tomselect" name="checklist[]" id="checklist" multiple data-placeholder="Select checklist items...">
								<?php
								$eduChkList = \App\Models\DocumentChecklist::where('status',1)->get();
								foreach($eduChkList as $edulist){
								?>
									<option value="{{$edulist->name}}">{{$edulist->name}}</option>
								<?php
								}
								?>
							</select>
								<span class="custom-error checklist_name_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
                    </div>

                    <div class="row">
						<!--<div class="col-9 col-md-9 col-lg-9">
							<div class="form-group">
								<label for="attachments">Upload Document<span class="span_req">*</span></label>
								<div class="custom-file">
									<input data-valid="required" class="docupload" multiple type="file" name="document_upload[]"/>
								</div>
								<span class="custom-error document_upload_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>-->
						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('alldocs_upload_form')" type="button" class="btn btn-primary">Create</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>


<!-- Add application assign staff Modal -->
<div class="modal fade custom_modal" id="create_applicationaction" tabindex="-1" role="dialog" aria-labelledby="create_applicationactionModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="appointModalLabel">Assign Staff</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/clients/action_application/store_application')}}" name="appliassignform" id="appliassignform" autocomplete="off" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="client_id" value="{{$fetchedData->id}}">
                    <input type="hidden" id="assign_application_id" name="application_id" value="">
                    <input type="hidden" id="stage_name" name="stage_name" value="">
                    <input type="hidden" name="atype" value="application">
                    <input type="hidden" name="course" id="course" value="">
                    <input type="hidden" name="school" id="school" value="">

					<div class="row">
                        <div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="course">Course:</label>
								<span id="course_s"></span>
							</div>
						</div>

						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="school">School:</label>
                                <span id="school_s"></span>
							</div>
						</div>

                        <div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="stage">Stage</label>
                                <span id="stage_name_f"></span>
							</div>
						</div>

						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="rem_cat11">Select Assignee <span class="span_req">*</span></label>
								<select class="assigneeselect211 tomselect form-control selec_reg11" id="rem_cat11" name="rem_cat11" data-valid="required">
                                    <option value="">Select</option>
                                    @foreach(\App\Models\Staff::select('id', 'office_id', 'first_name', 'last_name')->where('status',1)->orderby('first_name','ASC')->get() as $admin)
                                    <?php
                                    $branchname = \App\Models\Branch::select('id', 'office_name')->where('id',$admin->office_id)->first();
                                    ?>
                                    <option value="<?php echo $admin->id; ?>"><?php echo $admin->first_name.' '.$admin->last_name.' ('.@$branchname->office_name.')'; ?></option>
                                    @endforeach
                                </select>
							</div>
						</div>

						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('appliassignform')" type="button" class="btn btn-primary">Assign Staff</button>
							<!--<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>-->
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- Refund Appliation Modal -->
<div class="modal fade custom_modal" id="refund_application" tabindex="-1" role="dialog" aria-labelledby="applicationModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="refundApplicationModalLabel">Refund Application</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/refund_application')}}" name="refund_app" id="refund_app" autocomplete="off" enctype="multipart/form-data">
				    @csrf
				    <input type="hidden" name="reapp_id" value="">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="">Notes <span class="span_req">*</span></label>
								<textarea data-valid="required"  class="form-control" name="refund_note" id="refund_note_textarea"></textarea>
                            </div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('refund_app')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>


<!-- NP message -->
<div class="modal fade" id="notPickedCallModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Are you sure want to send text message to this user?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control" id="messageText" rows="10" style="height: 130px !important;"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary sendMessage">Send</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
