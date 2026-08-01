<!-- Appliation Modal -->
<div class="modal fade add_appliation custom_modal" tabindex="-1" role="dialog" aria-labelledby="applicationModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content"> 
			<div class="modal-header">
				<h5 class="modal-title" id="appliationModalLabel">Add Appliation</h5>
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
							<button onclick="customValidate('applicationform')" type="button" class="btn btn-primary">Save</button>
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
				<h5 class="modal-title" id="appliationModalLabel">Create Note</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/create-note')}}" name="notetermform" autocomplete="off" id="notetermform" enctype="multipart/form-data">
				@csrf 
				<input type="hidden" name="client_id" value="{{$fetchedData->id}}">
				<input type="hidden" name="noteid" value="">
				<input type="hidden" name="mailid" value="0">
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
								<select data-valid="" class="form-control contact_name js-data-example-ajaxcc" name="contact_name[]">
									
									
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


<!-- Education Modal -->
<div class="modal fade custom_modal" id="other_info_add" tabindex="-1" role="dialog" aria-labelledby="academiModalLabel" aria-hidden="true">
<div class="modal-dialog modal-lg">
	<div class="modal-content">
		<div class="modal-header">
			<h5 class="modal-title" id="academiModalLabel">Add Subject Area & Level</h5>
			<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<div class="modal-body">
			<form method="post" action="{{URL::to('/saveotherinfo')}}" name="savesubjectarea" id="savesubjectarea" autocomplete="off" enctype="multipart/form-data">
			@csrf 
			<input type="hidden" name="client_id" value="{{$fetchedData->id}}">
			
				<div class="row">
					<div class="col-12 col-md-6 col-lg-6">
						<div class="form-group">
							<label for="degree_level">Degree Level</label> 	
							<select data-valid="required" class="form-control degree_level tomselect" name="degree_level">
								<option value=""></option>
								<option value="Bachelor">Bachelor</option>
									<option value="Certificate">Certificate</option>
									<option value="Diploma">Diploma</option>
									<option value="High School">High School</option>
									<option value="Master">Master</option>
							</select>
							<span class="custom-error degree_level_error" role="alert">
								<strong></strong>
							</span> 
						</div>
					</div>
					<div class="col-12 col-md-12 col-lg-12">
						<button onclick="customValidate('savesubjectarea')" type="button" class="btn btn-primary">Save</button>
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					</div>
				</div>
			</form> 
		</div>
	</div>
</div>
</div> 

<div class="modal fade custom_modal" id="other_info_edit" tabindex="-1" role="dialog" aria-labelledby="academiModalLabel" aria-hidden="true">
<div class="modal-dialog modal-lg">
	<div class="modal-content">
		<div class="modal-header">
			<h5 class="modal-title" id="academiModalLabel">Edit Subject Area & Level</h5>
			<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<div class="modal-body">
			<form method="post" action="{{URL::to('/saveotherinfo')}}" name="editsubjectarea" id="editsubjectarea" autocomplete="off" enctype="multipart/form-data">
			@csrf 
			<input type="hidden" name="client_id" value="{{$fetchedData->id}}">
			
				<div class="row showsubjecthtml">
					
				</div>
			</form> 
		</div>
	</div>
</div>
</div> 

<!-- Note & Terms Modal -->
<div class="modal fade custom_modal" id="opencommissionmodal" tabindex="-1" role="dialog" aria-labelledby="create_noteModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="appliationModalLabel">Commission Invoice</h5>
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
										<option value="{{$aplist->id}}">{{$productdetail->name}} ({{$partnerdetail->partner_name}})</option>
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
				<h5 class="modal-title" id="appliationModalLabel">General Invoice</h5>
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
		<form action="{{ url('invoice/payment-store') }}" method="POST" name="ajaxinvoicepaymentform" autocomplete="off" enctype="multipart/form-data" id="ajaxinvoicepaymentform">
			@csrf
	<input type="hidden" value="" name="invoice_id" id="invoice_id">
	<input type="hidden" value="true" name="is_ajax">
	<input type="hidden" value="{{$fetchedData->id}}" name="client_id">
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
									<div class="input-group-prepend">
										<div class="input-group-text">
											@icon('clock')
										</div>
									</div>	
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
				<h5 class="modal-title" id="appliationModalLabel">Create Note</h5>
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
				<form method="post" action="#" name="checklistform" id="checklistform" autocomplete="off" enctype="multipart/form-data">
				@csrf 
				<input type="hidden" name="client_id" value="{{$fetchedData->id}}">
					<div class="row">						
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="document_type">Document Type <span class="span_req">*</span></label>
								<select class="form-control tomselect" name="document_type" data-valid="required">
									<option value="">Please Select Document Type</option>
									<option value="Salary Statement">Salary Statement</option>
									<option value="Experience Letter">Experience Letter</option>
									<option value="Transcripts">Transcripts</option>
									<option value="Academic Certificates">Academic Certificates</option>
									<option value="Passport">Passport</option>
									<option value="Citizenship">Citizenship</option>
								</select>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="description">Description</label>
								<textarea class="form-control" name="description" placeholder="Description"></textarea>
								<span class="custom-error description_error" role="alert">
									<strong></strong>
								</span> 
							</div>	
						</div>	
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<div class="form-check form-check-inline">
									<input class="form-check-input" type="checkbox" value="Make this as mandatory inorder to proceed next stage" name="proceed_next_stage">
									<label class="form-check-label" for="proceed_next_stage">Make this as mandatory inorder to proceed next stage</label>
								</div> 
							</div>
						</div>	
					</div>	
					<div class="due_date_sec">
						<a href="javascript:;" class="btn btn-primary due_date_btn">@icon('plus') Add Due Date</a>
						<div class="due_date_col">	  
							<div class="row">	 
								<div class="col-12 col-md-6 col-lg-6">
									<div class="form-group"> 
										<label for="appoint_date">Date</label>
										<div class="input-group">
											<div class="input-group-prepend"> 
												<div class="input-group-text">
													@icon('calendar-alt')
												</div>
											</div>
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
											<div class="input-group-prepend">
												<div class="input-group-text">
													@icon('clock')
												</div>
											</div>
											{!! Form::text('appoint_time', '', array('class' => 'form-control timepicker', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Select Time' ))  !!}
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

<!-- Payment Schedule Modal -->
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
									<div class="input-group-prepend"> 
										<div class="input-group-text">
											@icon('calendar-alt')
										</div>
									</div>
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
													<option value="Tution Fees" selected>Tution Fees</option>
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
									<div class="input-group-prepend"> 
										<div class="input-group-text">
											@icon('calendar-alt')
										</div>
									</div>
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


<div id="applicationemailmodal"  data-backdrop="static" data-keyboard="false" class="modal fade custom_modal" tabindex="-1" role="dialog" aria-labelledby="clientModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="clientModalLabel">Compose Email</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" name="appkicationsendmail" id="appkicationsendmail" action="{{URL::to('/application-sendmail')}}" autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="client_id" value="{{$fetchedData->id}}">
				<input type="hidden" id="type" name="type" value="application">
				<input type="hidden" id="appointid" name="noteid" value="">
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
								<select data-valid="" class="js-data-example-ajaxcc" name="email_cc[]"></select>
								
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
								<select data-valid="" class="form-control tomselect selectapplicationtemplate" name="template">
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


<!-- Education Modal -->
<div class="modal fade custom_modal" id="new_fee_option" tabindex="-1" role="dialog" aria-labelledby="feeoptionModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="feeoptionModalLabel">Add New Fee Option</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/savefee')}}" name="feeform" id="feeform" autocomplete="off" enctype="multipart/form-data">
				@csrf 
				<input type="hidden" name="product_id" value="{{$fetchedData->id}}">
					<div class="row">
						<div class="col-12 col-md-4 col-lg-4">
							<div class="form-group">
								<label for="fee_option_name">Fee Option Name <span class="span_req">*</span></label> 	
								{!! Form::text('fee_option_name', '', array('class' => 'form-control selectedappsubject', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Enter Fee Option Name' ))  !!}
								<span class="custom-error feeoption_name_error" role="alert">
									<strong></strong>
								</span> 
							</div>
						</div>
						<div class="col-12 col-md-4 col-lg-4">
							<div class="form-group">
								<label for="country_residency">Country of Residency <span class="span_req">*</span></label> 
								<select class="form-control tomselect residencyelect2" name="country_residency" data-valid="required">
								<option value="">Select Country</option>
								<?php
									foreach(\App\Models\Country::all() as $list){
										?>
										<option @if(@$list->name == 'Australia') selected @endif value="{{@$list->name}}">{{@$list->name}}</option>
										<?php
									}
									?>
								</select>
								<span class="custom-error country_residency_error" role="alert">
									<strong></strong>
								</span> 
							</div>
						</div>
						<div class="col-12 col-md-4 col-lg-4">
							<div class="form-group">
								<label for="degree_level">Installment Type <span class="span_req">*</span></label> 
								<select data-valid="required" class="form-control degree_level tomselect installment_type" name="degree_level">
									<option value="">Select Type</option>
									<option value="Full Fee">Full Fee</option>
									<option value="Per Year">Per Year</option>
									<option value="Per Month">Per Month</option>
									<option value="Per Term">Per Term</option>
									<option value="Per Trimester">Per Trimester</option>
									<option value="Per Semester">Per Semester</option>
									<option value="Per Week">Per Week</option>
									<option value="Installment">Installment</option>
								</select>
								<span class="custom-error degree_level_error" role="alert">
									<strong></strong>
								</span> 
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="table-responsive"> 
								<table class="table text_wrap" id="productitemview">
									<thead>
										<tr> 
											<th>Fee Type <span class="span_req">*</span></th>
											<th>Installment Amount <span class="span_req">*</span></th>
											<th>Installments <span class="span_req">*</span></th>
											<th>Total Fee</th>
											<th>Claimable Terms</th>
											<th>Commission %</th>
											<th>Add in quotation</th>
								
										</tr> 
									</thead>
									<tbody class="tdata">
										<tr class="add_fee_option cus_fee_option">
											<td>
												<select data-valid="required" class="form-control course_fee_type " name="course_fee_type[]">
													<option value="">Select Type</option>
													<option value="Tution Fees" selected>Tution Fees</option>
												</select>
											</td>
											<td>
												<input type="number" value="0" class="form-control installment_amount" name="installment_amount[]">
											</td>
											<td>
												<input type="number" value="1" class="form-control installment" name="installment[]">
											</td>
											<td class="total_fee"><span>0.00</span><input value="0" type="hidden"  class="form-control total_fee_am" name="total_fee[]"></td>
											<td>
												<input type="number" value="1" class="form-control claimable_terms" name="claimable_terms[]">
											</td>
											<td>
												<input type="number" class="form-control commission" name="commission[]">
											</td>
											<td>
												<input value="1" class="add_quotation" type="checkbox" name="add_quotation[]">
											</td>
									
										</tr>
									</tbody>
									<tfoot>
										<tr>
											
											<td colspan="3" style="text-align: right;"><b>Net Total</b></td>
											<td class="net_totl text-info">0.00</td>
											<td colspan="3"></td>
										</tr>
									</tfoot>
								</table>	
							</div>
							<div class="fee_option_addbtn">
								<a href="#" class="btn btn-primary">@icon('plus') Add Fee</a>
							</div>
							
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('feeform')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form> 
			</div>
		</div>
	</div>
</div> 