@extends('layouts.admin')
@section('title', 'Agents')

@section('content')
<style>
.filter_panel {background: #f7f7f7;margin-bottom: 10px;border: 1px solid #eee;display: none;}
.card .card-body .filter_panel { padding: 20px;}
</style>
<!-- Main Content -->
<div class="main-content">
	<section class="section">
		<div class="section-body">
			<div class="server-error">
				@include('../Elements/flash-message')
			</div>
			<div class="custom-error-msg">
			</div>
			<div class="row">
				<div class="col-12 col-md-12 col-lg-12">
					<div class="card">
						<div class="card-header">
							<h4>All Agents</h4>
							<div class="card-header-action">
								<a href="{{route('agents.create')}}" class="btn btn-primary">Create Agent</a>
								<a href="javascript:;" class="btn btn-primary openimportmodal">Import</a>
								<a href="javascript:;" class="btn btn-theme btn-theme-sm filter_btn">@icon('filter') Filter</a>
							</div>
						</div>
						<div class="card-body">
							<ul class="nav nav-pills" id="client_tabs" role="tablist">
								<li class="nav-item is_checked_client" style="display:none;">
									<a class="btn btn-primary emailmodal" href="javascript:;"  >Send Mail</a>
								</li>
								<li class="nav-item is_checked_clientn">
									<a class="nav-link active" id="active-tab"  href="{{URL::to('/agents/active')}}" >Active</a>
								</li>
								<li class="nav-item is_checked_clientn">
									<a class="nav-link" id="inactive-tab"  href="{{URL::to('/agents/inactive')}}" >Inactive</a>
								</li>
							</ul>
							<div class="filter_panel"@if(request()->hasAny(['search_by', 'agent_type', 'struture']) && (request('search_by') || request('agent_type') || request('struture'))) style="display:block;"@endif>
								<h4>Search By Details</h4>
								<form action="{{ route('agents.active') }}" method="get">
									<div class="row">
										<div class="col-md-4">
											<div class="form-group">
												<label for="search_by" class="col-form-label">Name, Email or Phone</label>
												<input id="search_by" type="search" name="search_by" class="form-control" placeholder="Search by name, email or phone..." value="{{ request('search_by') }}" autocomplete="off" />
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label for="agent_type" class="col-form-label">Agent Type</label>
												<select id="agent_type" name="agent_type" class="form-control">
													<option value="">All</option>
													<option value="Super Agent" @selected(request('agent_type') === 'Super Agent')>Super Agent</option>
													<option value="Sub Agent" @selected(request('agent_type') === 'Sub Agent')>Sub Agent</option>
												</select>
											</div>
										</div>
										<div class="col-md-4">
											<div class="form-group">
												<label for="struture" class="col-form-label">Agent Structure</label>
												<select id="struture" name="struture" class="form-control">
													<option value="">All</option>
													<option value="Individual" @selected(request('struture') === 'Individual')>Individual</option>
													<option value="Business" @selected(request('struture') === 'Business')>Business</option>
												</select>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12 text-center">
											<button type="submit" class="btn btn-primary btn-theme-lg">Search</button>
											<a class="btn btn-info" href="{{ route('agents.active') }}">Reset</a>
										</div>
									</div>
								</form>
							</div>
							<div class="tab-content" id="clientContent">								
								<div class="tab-pane fade show active" id="active" role="tabpanel" aria-labelledby="active-tab">
									<div class="table-responsive common_table"> 
										<table class="table text_wrap">
											<thead>
												<tr> 
													<th class="text-center" style="width:30px;">
														<div class="custom-checkbox custom-checkbox-table custom-control">
															<input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-all">
															<label for="checkbox-all" class="custom-control-label">&nbsp;</label>
														</div>
													</th>	
													<th>Name</th>
													<th>Type</th>
													<th>Structure</th>
													<!--<th>Phone</th>-->
													<th>City</th>
													<th>Associated Office</th>
													<th>Clients Count</th>
													<th>Applications Count</th>
													<th>Status</th>
													<th></th>
												</tr> 
											</thead>
											
											<tbody class="tdata">	
												@if(@$totalData !== 0)
													<?php $i=0; ?>
												@foreach (@$lists as $list)
												<tr id="id_{{@$list->id}}"> 
													<td style="white-space: initial;" class="text-center">
														<div class="custom-checkbox custom-control">
															<input data-id="{{@$list->id}}" data-email="{{@$list->email}}" data-name="{{@$list->full_name}}" type="checkbox" data-checkboxes="mygroup" class="cb-element custom-control-input" id="checkbox-{{$i}}">
															<label for="checkbox-{{$i}}" class="custom-control-label">&nbsp;</label>
														</div>
													</td>
													<td style="white-space: initial;"><a href="{{URL::to('/agent/detail/'.base64_encode(convert_uuencode(@$list->id)))}}">{{ @$list->full_name == "" ? config('constants.empty') : str_limit(@$list->full_name, '50', '...') }}</a> <br/>{{--@$list->email == "" ? config('constants.empty') : str_limit(@$list->email, '50', '...')--}}</td> 
													<td style="white-space: initial;">{{ @$list->agent_type == "" ? config('constants.empty') : str_limit(@$list->agent_type, '50', '...') }}</td>
													<td style="white-space: initial;">{{ @$list->struture == "" ? config('constants.empty') : str_limit(@$list->struture, '50', '...') }}</td>
													<!--<td>-->{{--@$list->country_code == "" ? config('constants.empty') : str_limit(@$list->country_code, '50', '...') }} {{ @$list->phone == "" ? config('constants.empty') : str_limit(@$list->phone, '50', '...')--}}<!--</td>-->	
													<td style="white-space: initial;">{{ @$list->city == "" ? config('constants.empty') : str_limit(@$list->city, '50', '...') }}</td> 	
													<td style="white-space: initial;">{{ @$list->related_office == "" ? config('constants.empty') : str_limit(@$list->related_office, '50', '...') }}</td> 	
													<td style="white-space: initial;">0</td> 	
													<td style="white-space: initial;">0</td> 
													<td style="white-space: initial;">
													    <div class="custom-switches">
									<label class="">
										<input value="1" data-id="{{@$list->id}}"  data-status="{{@$list->status}}" data-col="status" data-table="agents" type="checkbox" name="custom-switch-checkbox" class="change-status custom-switch-input" {{ (@$list->status == 1 ? 'checked' : '')}}>
										<span class="custom-switch-indicator"></span>
									</label>
								</div>
													</td>
													<td>
														<div class="dropdown d-inline">
															<button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
															<div class="dropdown-menu">
																<a class="dropdown-item has-icon" href="{{ route('agents.edit', ['id' => base64_encode(convert_uuencode($list->id))], false) }}">@icon('edit', 'regular') Edit</a>
																<a class="dropdown-item has-icon" href="javascript:;" onclick="deleteAction({{$list->id}}, 'agents')">@icon('trash') Archived</a>
															</div>
														</div>								  
													</td>
												</tr>	
												<?php $i++; ?>
												@endforeach	
											</tbody>
											@else
											<tbody>
												<tr>
													<td style="text-align:center;" colspan="10">
														No Record found
													</td>
												</tr>
											</tbody>
											@endif
										</table>
									</div>
								</div>
								
							</div> 
						</div>
						<div class="card-footer">
							{!! $lists->appends(\Request::except('page'))->render() !!}
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</div>

<div id="emailmodal" class="modal fade custom_modal" tabindex="-1" role="dialog" aria-labelledby="clientModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="clientModalLabel">Compose Email</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form method="post" name="sendmail" action="{{URL::to('/sendmail')}}" autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="type" value="agent">
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
								<select data-valid="required" class="js-data-example-ajax" name="email_to[]"></select>
								
								@if ($errors->has('email_to'))
									<span class="custom-error" role="alert">
										<strong>{{ @$errors->first('email_to') }}</strong>
									</span> 
								@endif
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="email_cc">CC </label>
								<select data-valid="" class="js-data-example-ajaxccdd" name="email_cc[]"></select>
								
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
								<select data-valid="" class="form-control tomselect selecttemplate" name="template">
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
								{!! Form::text('subject', '', array('class' => 'form-control selectedsubject', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Enter Subject' ))  !!}
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
							<button onclick="customValidate('sendmail')" type="button" class="btn btn-primary">Send</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<div class="modal fade custom_modal" id="openimportmodal" tabindex="-1" role="dialog" aria-labelledby="create_interestModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="interestModalLabel">Import Agents</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				 <div class="col-md-12">	
					<h5>Business</h5>
					<a href="{{URL::to('agents/import/business')}}" style="background-color: transparent;color: #9c9c9c;fill: #9c9c9c;width: 48%;border: 1px solid #9c9c9c;display: inline-flex;" class="btn btn-info defaultButton ghostButton">Import Business Agents</a>
				 </div>
				  <div class="col-md-12" style="margin-top: 20px!important;">
				  <h5>Individual</h5>
					<a href="{{URL::to('agents/import/individual')}}" style="background-color: transparent;color: #9c9c9c;fill: #9c9c9c;width: 48%;border: 1px solid #9c9c9c;display: inline-flex;" class="btn btn-info defaultButton ghostButton">Import Individual Agents</a>
				 </div>
			</div>
		</div>
	</div>
</div>
@endsection
@section('scripts')
<script>
jQuery(document).ready(function($){
	$(document).delegate('.openimportmodal','click', function(){
		$('#openimportmodal').modal('show');
	});
	$('.filter_btn').on('click', function(){
		$('.filter_panel').slideToggle();
	});
	$("[data-checkboxes]").each(function () {
  var me = $(this),
    group = me.data('checkboxes'),
    role = me.data('checkbox-role');

  me.change(function () {
    var all = $('[data-checkboxes="' + group + '"]:not([data-checkbox-role="dad"])'),
      checked = $('[data-checkboxes="' + group + '"]:not([data-checkbox-role="dad"]):checked'),
      dad = $('[data-checkboxes="' + group + '"][data-checkbox-role="dad"]'),
      total = all.length,
      checked_length = checked.length;

    if (role == 'dad') {
      if (me.is(':checked')) {
        all.prop('checked', true);
		$('.is_checked_client').show();
		$('.is_checked_clientn').hide();
      } else {
        all.prop('checked', false);
		$('.is_checked_client').hide();
		$('.is_checked_clientn').show();
      }
    } else {
      if (checked_length >= total) {
        dad.prop('checked', true);
			$('.is_checked_client').show();
		$('.is_checked_clientn').hide();
      } else {
        dad.prop('checked', false);
		$('.is_checked_client').hide();
		$('.is_checked_clientn').show();
      }
    }
  });
});

$('.cb-element').change(function () {
	
 if ($('.cb-element:checked').length == $('.cb-element').length){
  $('#checkbox-all').prop('checked',true);
 }
 else {
  $('#checkbox-all').prop('checked',false);
 }

 if ($('.cb-element:checked').length > 0){
		$('.is_checked_client').show();
		$('.is_checked_clientn').hide();
	}else{
		$('.is_checked_client').hide();
		$('.is_checked_clientn').show();
	}
});
$(document).delegate('.emailmodal', 'click', function(){
	$('#emailmodal').modal('show');
	var collected = RecipientSelect.collectFromCheckboxes('.cb-element', 'Client');
	RecipientSelect.setData('#emailmodal .js-data-example-ajax', collected.entries, { dropdownParent: '#emailmodal' });
});

$(document).delegate('.clientemail', 'click', function(){
	$('#emailmodal').modal('show');
	RecipientSelect.setClientEmailRecipient(
		'#emailmodal .js-data-example-ajax',
		$(this).attr('data-id'),
		$(this).attr('data-name'),
		$(this).attr('data-email'),
		'Client',
		{ dropdownParent: '#emailmodal' }
	);
});
$(document).delegate('.selecttemplate', 'change', function(){
	var v = $(this).val();
	$.ajax({
		url: '{{URL::to('/get-templates')}}',
		type:'GET',
		datatype:'json',
		data:{id:v},
		success: function(response){
			var res = JSON.parse(response);
			$('.selectedsubject').val(res.subject);
			 if (typeof TinyMCEHelpers !== 'undefined') { TinyMCEHelpers.resetBySelector(".tinymce-simple"); TinyMCEHelpers.setContentBySelector(".tinymce-simple", res.description); }
					$(".tinymce-simple").val(res.description); 
			
		}
	});
});
	var recipientsUrl = '{{URL::to('/clients/get-recipients')}}';
	RecipientSelect.init('#emailmodal .js-data-example-ajax', { url: recipientsUrl, dropdownParent: '#emailmodal' });
	RecipientSelect.init('#emailmodal .js-data-example-ajaxccdd', { url: recipientsUrl, dropdownParent: '#emailmodal' });
});
</script>
@endsection