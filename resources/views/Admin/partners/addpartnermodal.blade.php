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
									@foreach($partnerDetailWorkflowOptions as $wlist)
										<option value="{{ $wlist['id'] }}">{{ $wlist['name'] }}</option>
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
				<form method="post" action="{{URL::to('/create-note')}}" name="clientnotetermform" autocomplete="off" id="clientnotetermform" enctype="multipart/form-data">
				@csrf 
				<input type="hidden" name="client_id" id= "partner_id" value="{{$fetchedData->id}}">
				<input type="hidden" name="noteid" value="">
				<input type="hidden" name="mailid" value="0">
				<input type="hidden" name="type" id="type" value="">
				<input type="hidden" name="vtype"  value="partner">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="title">Title <span class="span_req">*</span></label>
								{{--Form::text('title', '', array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Enter Title' )) --}}

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
								<textarea class="tinymce-simple" name="description" data-valid="required"></textarea>
								<span class="custom-error title_error" role="alert">
									<strong></strong>
								</span> 
							</div>
						</div>
						
						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('clientnotetermform')" type="button" class="btn btn-primary">Submit</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form> 
			</div>
		</div>
	</div>
</div>

<!-- Branch Modal -->
<div class="modal fade custom_modal" id="add_clientbranch" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="appliationModalLabel">Add new branch</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/partner/create-branch')}}" name="clientbranch" autocomplete="off" id="clientbranch" enctype="multipart/form-data">
				@csrf 
				<input type="hidden" name="client_id" value="{{$fetchedData->id}}">
				<input type="hidden" name="branch_id" id="branch_id" value="">
				
					<div class="row allinputfields">
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="name">Name <span class="span_req">*</span></label>
								{!! Form::text('name', '', array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Enter Name' ))  !!}
								<span class="custom-error name_error" role="alert">
									<strong></strong>
								</span> 
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="email">Email <span class="span_req">*</span></label>
								{!! Form::text('email', '', array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Enter Email' ))  !!}
								<span class="custom-error email_error" role="alert">
									<strong></strong>
								</span> 
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="branch">Country </label>
								<select name="country" id="country" class="form-control" data-valid="required">
									<?php
										foreach($partnerDetailCountries as $list){
											?>
											<option value="{{ $list['name'] }}">{{ $list['name'] }}</option>
											<?php
										}
										?>
								</select>
								<span class="custom-error branch_error" role="alert">
									<strong></strong>
								</span> 
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="city">City</label>
								{!! Form::text('city', '', array('class' => 'form-control', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'Enter CIty' ))  !!}
								<span class="custom-error name_error" role="alert">
									<strong></strong>
								</span> 
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="state">State</label>
								{!! Form::text('state', '', array('class' => 'form-control', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'Enter State' ))  !!}
								<span class="custom-error name_error" role="alert">
									<strong></strong>
								</span> 
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="zip_code">Zip Code</label>
								{!! Form::text('zip_code', '', array('class' => 'form-control', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'Enter Zip Code' ))  !!}
								<span class="custom-error name_error" role="alert">
									<strong></strong>
								</span> 
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="phone">Phone Number</label>
								<div class="cus_field_input">
									<div class="country_code"> 
										@include('partials.country-code-select', [
											'name' => 'country_code',
											'selected' => old('country_code', \App\Helpers\PhoneHelper::getDefaultCountryCode())
										])
									</div>	
									{!! Form::text('phone', '', array('class' => 'form-control tel_input', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'Enter Phone' ))  !!}
									@if ($errors->has('phone'))
										<span class="custom-error" role="alert">
											<strong>{{ @$errors->first('phone') }}</strong>
										</span> 
									@endif
								</div>
							</div>
						</div>
						
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="head_office"> Head Office</label>
								<input type="checkbox" id="head_office" value="1" name="head_office" class="checkbox">
								<span class="custom-error name_error" role="alert">
									<strong></strong>
								</span> 
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('clientbranch')" type="button" class="btn btn-primary">Submit</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form> 
			</div>
		</div>
	</div>
</div>


<!-- Education Modal -->
<div class="modal fade create_education custom_modal" tabindex="-1" role="dialog" aria-labelledby="create_educationModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="appliationModalLabel">Create Education</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/saveeducation')}}" name="educationform" id="educationform" autocomplete="off" enctype="multipart/form-data">
				@csrf 
				<input type="hidden" name="client_id" value="{{$fetchedData->id}}">
					<div class="row">
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="degree_title">Degree Title <span class="span_req">*</span></label>
								{!! Form::text('degree_title', '', array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Enter Degree Title' ))  !!}
								<span class="custom-error degree_title_error" role="alert">
									<strong></strong>
								</span> 
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="degree_level">Degree Level <span class="span_req">*</span></label> 	
								<select data-valid="required" class="form-control degree_level tomselect" name="degree_level">
									<option value="">Please Select Degree Level</option>
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
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="institution">Institution <span class="span_req">*</span></label>
								{!! Form::text('institution', '', array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Enter Institution' ))  !!}
								<span class="custom-error institution_error" role="alert">
									<strong></strong>
								</span> 
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group"> 
								<label for="course_start">Course Start</label>
								<div class="input-group">
									<div class="input-group-prepend">
										<div class="input-group-text">
											@icon('calendar-alt')
										</div>
									</div>
									{!! Form::text('course_start', '', array('class' => 'form-control datepicker', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'Select Date' ))  !!}
									@if ($errors->has('course_start'))
										<span class="custom-error" role="alert">
											<strong>{{ @$errors->first('course_start') }}</strong>
										</span> 
									@endif
								</div>
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group"> 
								<label for="course_end">Course End</label>
								<div class="input-group">
									<div class="input-group-prepend">
										<div class="input-group-text">
											@icon('calendar-alt')
										</div>
									</div>
									{!! Form::text('course_end', '', array('class' => 'form-control datepicker', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'Select Date' ))  !!}
									@if ($errors->has('course_end'))
										<span class="custom-error" role="alert">
											<strong>{{ @$errors->first('course_end') }}</strong>
										</span> 
									@endif
								</div>
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group"> 
								<label class="d-block" for="academic_score">Academic Score</label> 
								<div class="form-check form-check-inline">
									<input class="form-check-input" type="radio" id="percentage" value="%" name="academic_score_type" checked>
									<label class="form-check-label" for="percentage">Percentage</label>
								</div>
								<div class="form-check form-check-inline">
									<input class="form-check-input" type="radio" id="GPA" value="GPA" name="academic_score_type">
									<label class="form-check-label" for="GPA">GPA</label>
								</div>
								{!! Form::number('academic_score', '', array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','step'=>'0.01' ))  !!}
								<span class="custom-error academic_score_error" role="alert">
									<strong></strong>
								</span> 
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('educationform')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
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
									@foreach($partnerDetailModalApplications as $aplist)
										<option value="{{ $aplist['id'] }}">{{ $aplist['label'] }}</option>
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
									@foreach($partnerDetailApplicationWorkflows as $workflow)
										<option value="{{ $workflow['id'] }}">{{ $workflow['name'] }}</option>
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
				<form method="post" action="{{URL::to('/create-app-note')}}" name="clientAPPnotetermform" autocomplete="off" id="clientAPPnotetermform" enctype="multipart/form-data">
				@csrf 
				<input type="hidden" name="client_id" value="{{$fetchedData->id}}">
				<input type="hidden" name="noteid" id="noteid" value="">
				<input type="hidden" name="type" id="type" value="partner">
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
							<button onclick="customValidate('clientAPPnotetermform')" type="button" class="btn btn-primary">Submit</button>
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
													<option value="Accommodation Fee">Accommodation Fee</option>
													<option value="Administration Fee">Administration Fee</option>
													<option value="Airline Ticket">Airline Ticket</option>
													<option value="Airport Transfer Fee">Airport Transfer Fee</option>
													<option value="Application Fee">Application Fee</option>
													<option value="Bond">Bond</option>
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
									@foreach($partnerDetailEmailTemplates as $list)
										<option value="{{ $list['id'] }}">{{ $list['name'] }}</option>
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

  
<!-- Promotion Modal -->
<div class="modal fade add_promotion custom_modal" id="create_promotion" tabindex="-1" role="dialog" aria-labelledby="create_promotionModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="create_promotionModalLabel">Add New Promotion</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/promotion/store')}}" id="promotionform" name="promotionform" id="promotionform" autocomplete="off" enctype="multipart/form-data">
				@csrf 
				<input type="hidden" name="client_id" value="{{$fetchedData->id}}">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="promotion_title">Promotion Title <span class="span_req">*</span></label>
								{!! Form::text('promotion_title', '', array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Enter Promotion Title'))  !!} 
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="promotion_desc">Description</label>
								<textarea class="form-control" name="promotion_desc" placeholder="Description"></textarea>
								<span class="custom-error promotion_desc_error" role="alert">
									<strong></strong>
								</span> 
							</div>	
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group"> 
								<label for="promotion_start_date">Start Date</label>
								<div class="input-group">
									<div class="input-group-prepend"> 
										<div class="input-group-text">
											@icon('calendar-alt')
										</div>
									</div>
									{!! Form::text('promotion_start_date', '', array('class' => 'form-control datepicker', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Select Date' ))  !!}
								</div>
								<span class="span_note">Date must be in YYYY-MM-DD (2012-12-22) format.</span>
								<span class="custom-error promotion_start_date_error" role="alert">
									<strong></strong>
								</span> 
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group"> 
								<label for="promotion_end_date">End Date</label>
								<div class="input-group">
									<div class="input-group-prepend"> 
										<div class="input-group-text">
											@icon('calendar-alt')
										</div>
									</div>
									{!! Form::text('promotion_end_date', '', array('class' => 'form-control datepicker', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Select Date' ))  !!}
								</div>
								<span class="span_note">Date must be in YYYY-MM-DD (2012-12-22) format.</span>
								<span class="custom-error promotion_end_date_error" role="alert">
									<strong></strong>
								</span> 
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label style="display:block;" for="apply_to">Apply To:</label>
								<div class="form-check form-check-inline">
									<input class="form-check-input" type="radio" id="all_product" value="All Products" name="apply_to" checked>
									<label class="form-check-label" for="all_product">All Products</label>
								</div>
								<div class="form-check form-check-inline">
									<input class="form-check-input" type="radio" id="select_products" value="Select Products" name="apply_to">
									<label class="form-check-label" for="select_products">Select Products</label>
								</div>
								<span class="custom-error apply_to_error" role="alert">
									<strong></strong>
								</span> 
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12 ifselectproducts" multiple style="display:none;">
							<div class="form-group">
								<select class="form-control tomselect promotion-product-select" name="selectproduct[]" multiple>
									@foreach($partnerDetailProducts as $plist)
										<option value="{{ $plist['id'] }}">{{ $plist['name'] }}</option>
									@endforeach
								</select>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('promotionform')" type="button" class="btn btn-primary">Save</button>
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
							<div class="left">
								<div class="author">
									
								</div>
								<div class="note_modify">
									<small>Last Modified <span class="lastdate"></span></small>
								</div>
							</div>  
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>


<!-- Partner Student Invoice Modal -->
<div class="modal fade custom_modal" id="createpartnerstudentinvoicemodal" tabindex="-1" role="dialog" aria-labelledby="create_noteModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="appliationModalLabel">Create Student Invoice</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
                <input type="hidden"  id="top_value_db" value="">
				<form method="post" action="{{URL::to('/partners/savepartnerstudentinvoice')}}" name="create_student_invoice" autocomplete="off" id="create_student_invoice" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="partner_id" id="partner_id" value="{{$fetchedData->id}}">
                <input type="hidden" name="loggedin_userid" value="{{@Auth::user()->id}}">
                <input type="hidden" name="invoice_type" value="1">
                <input type="hidden" name="function_type" id="function_type" value="">
                <input type="hidden" name="invoice_id" id="invoice_id_val" value="">
					<div class="row">
						<div class="col-3 col-md-3 col-lg-3">
							<div class="form-group">
								<label for="partner">Partner <span class="span_req">*</span></label>
								{!! Form::text('partner', @$fetchedData->partner_name, array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'' ))  !!}
								<span class="custom-error partner_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>

                        <div class="col-3 col-md-3 col-lg-3">
							<div class="form-group">
								<label for="invoice_date">Invoice Date <span class="span_req">*</span></label>
								<input data-valid="required"  class="form-control invoice_date_fields" name="invoice_date" type="text" value="" />
                                <span class="custom-error invoice_date_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>

                        <div class="col-3 col-md-3 col-lg-3">
							<div class="form-group">
								<label for="invoice_no">Invoice Number <span class="span_req">*</span></label>
                                <input class="form-control unique_trans_no" type="text" value="" readonly/>
                                <input class="unique_trans_no_hidden" name="invoice_no" type="hidden" value="" />
                                <span class="custom-error invoice_no_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>

                        <div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
                                <table border="1" style="margin-bottom:0rem !important;" class="table text_wrap table-striped table-hover table-md vertical_align">
                                    <thead>
                                        <tr>
                                            <th style="width:10%;">Student Enrolled</th>
                                            <th style="width:15%;">DOB</th>
                                            <th style="width:15%;">Course Name</th>
                                            <th style="width:15%;">Student Id</th>
                                            <th style="width:15%;">Description</th>
                                            <th style="width:5%;">Amount(Incl GST)</th>
                                            <th style="width:1%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="productitem">
                                        <tr class="clonedrow">
                                            <td>
                                                <input name="id[]" type="hidden" value="" />
                                                <select data-valid="required" class="form-control student_no_cls" name="student_id[]">
                                                </select>
                                            </td>
                                            <td>
                                                <input data-valid="required" class="form-control student_dob" name="student_dob[]" type="text" value="" />
                                                <input class="form-control student_name" name="student_name[]" type="hidden" value="" />
                                                <input class="form-control student_ref_no" name="student_ref_no[]" type="hidden" value="" />
                                            </td>
                                            <td>
                                                <input data-valid="required" class="form-control student_course_name" name="course_name[]" type="text" value="" />
                                            </td>
                                            <td>
                                                <input data-valid="required" class="form-control student_info_id" name="student_info_id[]" type="text" value="" />
                                            </td>
                                            <td>
                                                <input data-valid="required" class="form-control" name="description[]" type="text" value="" />
                                            </td>
                                            <td>
                                                <span class="currencyinput" style="display: inline-block;">$</span>
                                                <input style="display: inline-block;" data-valid="required" class="form-control deposit_amount_per_row" type="text" value="" readonly/>
                                                <input class="form-control deposit_amount_per_row_hidden" name="amount_aud[]" type="hidden" value="" />
                                           </td>
                                            <td>
                                                <a class="removeitems" href="javascript:;">@icon('times')</a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <table border="1" class="table text_wrap table-striped table-hover table-md vertical_align">
                                    <tbody>
                                        <tr>
                                            <td colspan="5" style="width:83.6%;text-align:right;">Totals</td>
                                            <td colspan="2">
                                                <span class="total_deposit_amount_all_rows"></span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
						</div>

                        <div class="col-3 col-md-3 col-lg-3">
                            <a href="javascript:;" class="openproductrinfo">@icon('plus') Add New</a>
                        </div>

						<div class="col-9 col-md-9 col-lg-9 text-end">
                            <div class="upload_client_receipt_document" style="display:inline-block;">
                                <input type="hidden" name="type" value="partner">
                                <input type="hidden" name="doctype" value="student_invoice">
                                <a href="javascript:;" class="btn btn-primary">@icon('plus') Add Document</a>
                                <input class="docclientreceiptupload" type="file" name="document_upload[]"/>
                            </div>

                            <input type="hidden" name="save_type" class="save_type" value="">
                            <!--<button onclick="customValidate('create_student_invoice')" type="button" class="btn btn-primary">Save</button>-->
                            <button onclick="customValidate('create_student_invoice','draft')" type="button" class="btn btn-primary">Save Draft</button>
							<!--<button onclick="customValidate('create_student_invoice','final')" type="button" class="btn btn-primary">Save and Finalised</button>-->
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
                    </div>
				</form>
            </div>
		</div>
	</div>
</div>



<!-- Record Invoice Modal -->
<div class="modal fade custom_modal" id="createrecordinvoicemodal" tabindex="-1" role="dialog" aria-labelledby="create_noteModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="appliationModalLabel">Create Record Invoice</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
                <input type="hidden"  id="top_value_db_invoice" value="">
				<form method="post" action="{{URL::to('/partners/savepartnerrecordinvoice')}}" name="create_record_invoice" autocomplete="off" id="create_record_invoice" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="partner_id" value="{{$fetchedData->id}}">
                <input type="hidden" name="loggedin_userid" value="{{@Auth::user()->id}}">
                <input type="hidden" name="invoice_type" value="2">
                <input type="hidden" name="function_type" id="function_type_invoice" value="">
					<div class="row">
						<div class="col-3 col-md-3 col-lg-3">
							<div class="form-group">
								<label for="partner">Partner <span class="span_req">*</span></label>
								{!! Form::text('partner', @$fetchedData->partner_name, array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'' ))  !!}
								<span class="custom-error partner_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>

                        <div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
                                <table border="1" style="margin-bottom:0rem !important;" class="table text_wrap table-striped table-hover table-md vertical_align">
                                    <thead>
                                        <tr>
                                            <th style="width:11%;">Invoice Date</th>
                                            <th style="width:11%;">Sent Date</th>
                                            <th style="width:10%;">Invoice Number</th>
                                            <th style="width:5%;">Amount(Incl GST)</th>
                                            <th style="width:1%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="productitem_invoice">
                                        <tr class="clonedrow_invoice">
                                            <td>
                                                <input data-valid="required"  class="form-control record_invoice_date_fields" name="invoice_date[]" type="text" value="" />
                                            </td>
                                            <td>
                                                <input data-valid="required"  class="form-control record_sent_date_fields" name="sent_date[]" type="text" value="" />
                                            </td>
                                            <td>
                                                <input class="form-control" data-valid="required" name="invoice_no[]" type="text" value="" />
                                                <!--<input class="unique_record_invoice_trans_no_hidden" type="hidden" value="" />-->
                                            </td>
                                            <td>
                                                <span class="currencyinput" style="display: inline-block;">$</span>
                                                <input style="display: inline-block;" data-valid="required" class="form-control deposit_invoice_amount_per_row" name="amount_aud[]" type="text" value=""/>
                                            </td>
                                            <td>
                                                <a class="removeitems_invoice" href="javascript:;">@icon('times')</a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <table border="1" class="table text_wrap table-striped table-hover table-md vertical_align">
                                    <tbody>
                                        <tr>
                                            <td colspan="3" style="width:83.6%;text-align:right;">Totals</td>
                                            <td colspan="1">
                                                <span class="total_deposit_amount_all_rows_invoice"></span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
						</div>

                        <div class="col-3 col-md-3 col-lg-3">
                            <a href="javascript:;" class="openproductrinfo_invoice">@icon('plus') Add New</a>
                        </div>

						<div class="col-9 col-md-9 col-lg-9 text-end">

                            <div class="upload_client_receipt_document" style="display:inline-block;">
                                <input type="hidden" name="type" value="partner">
                                <input type="hidden" name="doctype" value="record_invoice">
                                <a href="javascript:;" class="btn btn-primary">@icon('plus') Add Document</a>
                                <input class="docclientreceiptupload" type="file" name="document_upload[]"/>
                            </div>

                            <button onclick="customValidate('create_record_invoice')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
                    </div>
				</form>
            </div>
		</div>
	</div>
</div>


<!-- Record Payment Modal -->
<div class="modal fade custom_modal" id="createrecordpaymentmodal" tabindex="-1" role="dialog" aria-labelledby="create_noteModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="appliationModalLabel">Create Record Payment</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
                <input type="hidden"  id="top_value_db_payment" value="">
				<form method="post" action="{{URL::to('/partners/savepartnerrecordpayment')}}" name="create_record_payment" autocomplete="off" id="create_record_payment" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="partner_id" value="{{$fetchedData->id}}">
                <input type="hidden" name="loggedin_userid" value="{{@Auth::user()->id}}">
                <input type="hidden" name="invoice_type" value="3">
                <input type="hidden" name="function_type" id="function_type_payment" value="">
					<div class="row">
						<div class="col-3 col-md-3 col-lg-3">
							<div class="form-group">
								<label for="partner">Partner <span class="span_req">*</span></label>
								{!! Form::text('partner', @$fetchedData->partner_name, array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'' ))  !!}
								<span class="custom-error partner_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>

                        <div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
                                <table border="1" style="margin-bottom:0rem !important;" class="table text_wrap table-striped table-hover table-md vertical_align">
                                    <thead>
                                        <tr>
                                            <th style="width:10%;">Invoice Number</th>
                                            <th style="width:11%;">Method Received</th>
                                            <th style="width:11%;">Verified By</th>
                                            <th style="width:11%;">Verified Date</th>
                                            <th style="width:10%;">Amount Received</th>
                                            <th style="width:1%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="productitem_payment">
                                        <tr class="clonedrow_payment">
                                            <td>
                                                <select data-valid="required" class="form-control invoice_no_cls" name="invoice_no[]">
                                                </select>
                                            </td>
                                            <td>
                                                <select data-valid="required" class="form-control" name="method_received[]">
                                                    <option value="">Select</option>
                                                    <option value="Bank Tranfer">Bank Tranfer</option>
                                                    <option value="EFT">EFT</option>
                                                    <option value="Prededuct">Prededuct</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input data-valid="required"  class="form-control" name="verified_by[]" type="text" value="" />
                                            </td>
                                            <td>
                                                <input data-valid="required"  class="form-control record_payment_date_fields" name="verified_date[]" type="text" value="" />
                                            </td>
                                            <td>
                                                <span class="currencyinput" style="display: inline-block;">$</span>
                                                <input style="display: inline-block;" data-valid="required" class="form-control deposit_payment_amount_per_row" name="amount_aud[]" type="text" value=""/>
                                            </td>
                                            <!--<td>
                                                <input class="form-control unique_record_payment_trans_no" type="text" value="" readonly/>
                                                <input class="unique_record_payment_trans_no_hidden" name="invoice_no[]" type="hidden" value="" />
                                            </td>-->

                                            <td>
                                                <a class="removeitems_payment" href="javascript:;">@icon('times')</a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <table border="1" class="table text_wrap table-striped table-hover table-md vertical_align">
                                    <tbody>
                                        <tr>
                                            <td colspan="3" style="width:83.6%;text-align:right;">Totals</td>
                                            <td colspan="1">
                                                <span class="total_deposit_amount_all_rows_payment"></span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
						</div>

                        <div class="col-3 col-md-3 col-lg-3">
                            <a href="javascript:;" class="openproductrinfo_payment">@icon('plus') Add New</a>
                        </div>

						<div class="col-9 col-md-9 col-lg-9 text-end">

                            <div class="upload_client_receipt_document" style="display:inline-block;">
                                <input type="hidden" name="type" value="partner">
                                <input type="hidden" name="doctype" value="record_payment">
                                <a href="javascript:;" class="btn btn-primary">@icon('plus') Add Document</a>
                                <input class="docclientreceiptupload" type="file" name="document_upload[]"/>
                            </div>

                            <button onclick="customValidate('create_record_payment')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
                    </div>
				</form>
            </div>
		</div>
	</div>
</div>


<!-- Partner Upload inbox email Modal -->
<div class="modal fade custom_modal" id="partnerUploadAndFetchMail" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="paymentscheModalLabel">Upload Inbox Mail And Fetch Content:</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
			<form method="post" action="{{URL::to('/upload-partner-fetch-mail')}}" name="partner_upload_inbox_mail" id="partner_upload_inbox_mail" autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="partner_id" id="mapartner_id_fetch">
                <input type="hidden" name="type" value="partner">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
                            <div class="form-group">
                               <label>Upload Outlook Email (.msg)</label>
                               <input type="file" name="email_file[]" class="form-control" accept=".msg" data-valid="required" multiple>
                            </div>
                       </div>

						<div class="col-4 col-md-4 col-lg-4">
							<div class="form-group">
								<button onclick="customValidate('partner_upload_inbox_mail')" class="btn btn-info" type="button">Save</button>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- Partner upload sent email Modal -->
<div class="modal fade custom_modal" id="partnerUploadSentAndFetchMail" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="paymentscheModalLabel">Upload Sent Mail And Fetch Content:</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
			<form method="post" action="{{URL::to('/upload-partner-sent-fetch-mail')}}" name="partner_upload_sent_mail" id="partner_upload_sent_mail"  autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="partner_id" id="mapartner_id_fetch_sent" value="">
                <input type="hidden" name="type" value="partner">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
                            <div class="form-group">
                               <label>Upload Outlook Email (.msg)</label>
                               <input type="file" name="email_file[]" class="form-control" accept=".msg" data-valid="required" multiple>
                            </div>
                       </div>

						<div class="col-4 col-md-4 col-lg-4">
							<div class="form-group">
								<button onclick="customValidate('partner_upload_sent_mail')" class="btn btn-info" type="button">Save</button>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>



<!-- Add partner assign staff Modal -->
<div class="modal fade custom_modal" id="create_partneraction" tabindex="-1" role="dialog" aria-labelledby="create_partneractionModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="appointModalLabel">Assign Staff</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/partners/action_partner/store_partner')}}" name="partnerassignform" id="partnerassignform" autocomplete="off" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="partner_id" value="{{$fetchedData->id}}">
                    <input type="hidden" name="type" value="partner">
                    <!--<input type="hidden" name="task_group" value="partner">-->

					<div class="row">
                        <div class="col-9 col-md-9 col-lg-9">
							<div class="form-group">
								<label for="rem_cat123">Select Assignee <span class="span_req">*</span></label>
								<select class="assigneeselect123 form-control selec_reg23" id="rem_cat123" name="rem_cat123" data-valid="required">
                                    <option value="">Select</option>
                                    @foreach($partnerDetailStaffAssignees as $admin)
                                    <option value="{{ $admin['id'] }}">{{ $admin['first_name'] }} {{ $admin['last_name'] }} ({{ $admin['office_name'] }})</option>
                                    @endforeach
                                </select>
							</div>
						</div>
                    </div>
                    <div class="row">
                        <div class="col-9 col-md-9 col-lg-9">
							<div class="form-group">
								<label for="inputEmail3">Note<span class="span_req">*</span></label>
					            <textarea id="assignnote" data-valid="required" name="assignnote" class="form-control tinymce-simple f13" placeholder="Enter an note...." type="text"></textarea>
				            </div>
						</div>
                    </div>

                    <div class="row">
                        <div class="col-3 col-md-3 col-lg-3">
							<div class="form-group">
								<label for="inputEmail3">Date<span class="span_req">*</span></label>
					            <input type="text" class="form-control f13" placeholder="dd/mm/yyyy" id="popoverdatetime" value="" name="popoverdate">
                            </div>
						</div>
                    </div>

                    <div class="row">
                        <div class="col-3 col-md-3 col-lg-3">
							<div class="form-group">
								<label for="task_group">Group<span class="span_req">*</span></label>
					            <select class="assignee-tomselect tomselect form-control selec_reg" id="task_group" name="task_group" data-valid="required">
                                    <option value="">Select</option>
                                    <option value="Call">Call</option>
                                    <option value="Checklist">Checklist</option>
                                    <option value="Review">Review</option>
                                    <option value="Query">Query</option>
                                    <option value="Urgent">Urgent</option>
                                </select>
                            </div>
						</div>
                    </div>


                    <div class="row">
                        <div class="col-3 col-md-3 col-lg-3">
							<div class="form-group">
								<label for="task_group">
                                    Note Deadline
                                    <input class="note_deadline_checkbox" type="checkbox" id="note_deadline_checkbox" name="note_deadline_checkbox" value="">
                                </label>
					            <input type="text" class="form-control f13" placeholder="dd/mm/yyyy" id="note_deadline" value="<?php echo date('d/m/Y');?>" name="note_deadline" disabled>
                            </div>
						</div>
                    </div>
                  
                  
                    <div class="row" id="recurring_type_section" style="display: none;">
                        <div class="col-3 col-md-3 col-lg-3">
                            <div class="form-group">
                                <label for="task_group">Recurring Type</label>
                                <div>
                                    <!-- Radio buttons for Recurring Type -->
                                    <label class="me-2">
                                        <input type="radio" name="recurring_type" value="yes"> Yes
                                    </label>
                                    <label>
                                        <input type="radio" name="recurring_type" value="no" checked> No
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 col-md-6 col-lg-6">
							<button onclick="customValidate('partnerassignform')" type="button" class="btn btn-primary">Assign Staff</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>




<!-- At Partner Detail Page in Student Tab Create Note Modal -->
<div class="modal fade custom_modal" id="create_student_note" tabindex="-1" role="dialog" aria-labelledby="create_student_noteModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="studentappliationModalLabel">Add Note To Student</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/add-student-note')}}" name="studentnotetermform" autocomplete="off" id="studentnotetermform" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="partner_id" id="partner_id" value="{{$fetchedData->id}}">
                <input type="hidden" name="college_name" id="college_name" value="">
				<input type="hidden" name="student_id" id="student_id" value="">
				<input type="hidden" name="student_ref_no" id="student_ref_no" value="">
				<input type="hidden" name="noteid" value="">
				<input type="hidden" name="mailid" value="0">
				<input type="hidden" name="type" id="type" value="">
				<input type="hidden" name="vtype"  value="partner">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="title">Title <span class="span_req">*</span></label>
								{{--Form::text('title', '', array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Enter Title' )) --}}

                                <select name="title" class="form-control" data-valid="required" id="studentNoteType">
								    <option value="">Please Select Note</option>
								    <option value="Call">Call</option>
								    <option value="Email">Email</option>
								    <option value="In-Person">In-Person</option>
								    <option value="Others">Others</option>
								    <option value="Attention">Attention</option>
								</select>

                                <!-- Container for additional inputs -->
						        <div id="additionalStudentNoteFields"></div>
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
							<button onclick="customValidate('studentnotetermform')" type="button" class="btn btn-primary">Submit</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
