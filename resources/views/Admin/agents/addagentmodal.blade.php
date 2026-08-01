<!-- Appliation Modal -->
<div class="modal fade add_appliation custom_modal" tabindex="-1" role="dialog" aria-labelledby="applicationModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content"> 
			<div class="modal-header">
				<h5 class="modal-title" id="appliationModalLabel">Add Appliation</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
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
<!-- Appliation Modal -->
<div class="modal fade custom_modal" id="discon_application" tabindex="-1" role="dialog" aria-labelledby="applicationModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content"> 
			<div class="modal-header">
				<h5 class="modal-title" id="appliationModalLabel">Discontinue Application</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
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
				<h5 class="modal-title" id="appliationModalLabel">Revert Discontinued Application</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
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
				<h5 class="modal-title" id="appliationModalLabel">Create Note</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/create-note')}}" name="notetermform" autocomplete="off" id="notetermform" enctype="multipart/form-data">
				@csrf 
				<input type="hidden" name="client_id" value="{{$fetchedData->id}}">
				<input type="hidden" name="noteid" value="">
				<input type="hidden" name="mailid" value="0">
				<input type="hidden" name="vtype" value="agent">
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

<!-- Note & Terms View Modal -->
<div class="modal fade custom_modal" id="view_note" tabindex="-1" role="dialog" aria-labelledby="view_noteModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div class="note_col"> 
					<div class="note_content">
						<h5></h5> 
						<p></p>
					</div>
				</div>
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
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
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

<div class="modal fade custom_modal" id="create_applicationnote" tabindex="-1" role="dialog" aria-labelledby="create_noteModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="appliationModalLabel">Create Note</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
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
