@extends('layouts.admin')
@section('title', 'Agent Detail')

@section('content')
<style>
.ag-space-between {justify-content: space-between;} 
.ag-align-center {align-items: center;}
.ag-flex {display: flex;}
.ag-align-start {align-items: flex-start;}
.ag-flex-column {flex-direction: column;}  
.col-hr-1 {margin-right: 5px!important;}
.text-semi-bold {font-weight: 600!important;}
.small, small {font-size: 85%;}
.ag-align-end { align-items: flex-end;}

.zippyLabel{background-color: #e8e8e8; line-height: 1;display: inline-block;color: rgba(0,0,0,.6);font-weight: 700; border: 0 solid transparent; font-size: 10px;padding: 3px; }
.accordion .accordion-header.app_green{background-color: #54b24b;color: #fff;}
.accordion .accordion-header.app_green .accord_hover a{color: #fff!important;}
.accordion .accordion-header.app_blue{background-color: rgba(3,169,244,.1);color: #03a9f4;}
.card .card-body table tbody.taskdata tr td span.check{background: #71cc53;color: #fff; border-radius: 50%;font-size: 10px;line-height: 14px;padding: 3px 4px;width: 18px;height: 18px;
display: inline-block;}
.card .card-body table tbody.taskdata tr td span.round{background: #fff;border:1px solid #000; border-radius: 50%;font-size: 10px;line-height: 14px;padding: 2px 5px;width: 16px;height: 16px; display: inline-block;}
#opentaskview .modal-body ul.navbar-nav li .dropdown-menu{transform: none!important; top:40px!important;} 
</style>
<?php
use App\Http\Controllers\Controller;
?>
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
							<h4>Agent Detail</h4>
							<div class="card-header-action">
								<a href="{{route('agents.active')}}" class="btn btn-primary">Agent List</a>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-3 col-md-3 col-lg-3">
					<div class="card author-box">
						<div class="card-body">
							<div class="author-box-center">
								<span class="author-avtar" style="background: rgb(68, 182, 174);"><b>{{substr($fetchedData->full_name, 0, 1)}}</b></span>
								<div class="clearfix"></div>
								<div class="author-tag">
									<span>{{$fetchedData->struture}}</span>
								</div>
								<div class="author-box-name">
									<a href="#">{{$fetchedData->full_name}}</a>
								</div>
								<div class="author-mail_sms">
									<a href="#" title="Compose SMS">@icon('comment-alt')</a>
									<a href="javascript:;" data-id="{{@$fetchedData->id}}" data-email="{{@$fetchedData->email}}" data-name="{{@$fetchedData->full_name}}" class="clientemail" title="Compose Mail">@icon('envelope')</a>  
									<a href="{{ route('agents.edit', ['id' => base64_encode(convert_uuencode($fetchedData->id))], false) }}" title="Edit" aria-label="Edit agent">@icon('edit')</a>
								</div>
							</div>
						</div>
					</div>
					<div class="card">
						<div class="card-header">
							<h4>General Information</h4>
						</div>
						<div class="card-body">
							<p class="clearfix"> 
								<span class="float-start">Phone No:</span>
								<span class="float-end text-muted">{{$fetchedData->country_code}}{{$fetchedData->phone}}</span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Email:</span>
								<span class="float-end text-muted">{{$fetchedData->email}}</span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Address:</span>
								<span class="float-end text-muted">{{$fetchedData->address}}</span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Agent Type:</span>
								<span class="float-end text-muted"><a href="javascript:;" class="btn btn-sm btn-outline-primary">{{$fetchedData->agent_type}}</a> </span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Claim Revenue Percentage:</span>
								<span class="float-end text-muted">{{$fetchedData->claim_revenue}}</span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Income Sharing Percentage:</span>
								<span class="float-end text-muted">{{$fetchedData->income_sharing}}</span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Associated Offices:</span>
								<?php 
									$branches = \App\Models\Branch::where('id', $fetchedData->related_office)->first();
								?>	
								<span class="float-end text-muted"><?php echo $branches->office_name;?></span>
							</p>							
							<p class="clearfix"> 
								<span class="float-start">Tax Number:</span>
								<span class="float-end text-muted">{{$fetchedData->tax_number}}</span>
							</p>
						</div>
					</div>
				</div>
				<div class="col-9 col-md-9 col-lg-9">
					<div class="card">
						<div class="card-body">
							<ul class="nav nav-pills" id="agents_tabs" role="tablist">
								<li class="nav-item">
									<a class="nav-link active" data-bs-toggle="tab" id="noteterm-tab" href="#noteterm" role="tab" aria-controls="noteterm" aria-selected="true">Notes & Terms</a>
								</li>
								<li class="nav-item">
									<a class="nav-link" data-bs-toggle="tab" id="reffered_client-tab" href="#reffered_client" role="tab" aria-controls="reffered_client" aria-selected="false">Referred Clients</a>
								</li>
								<li class="nav-item">
									<a class="nav-link" data-bs-toggle="tab" id="application-tab" href="#application" role="tab" aria-controls="application" aria-selected="false">Applications</a>
								</li>
								<li class="nav-item">
									<a class="nav-link" data-bs-toggle="tab" id="accounts-tab" href="#accounts" role="tab" aria-controls="accounts" aria-selected="false">Accounts</a>
								</li>
							</ul> 
							<div class="tab-content" id="agentContent" style="padding-top:15px;">
								<div class="tab-pane fade show active" id="noteterm" role="tabpanel" aria-labelledby="noteterm-tab">
									<div class="card-header-action text-end" style="padding-bottom:15px;">
										<a href="javascript:;" datatype="note" class="create_note btn btn-primary">@icon('plus') Add</a>
									</div>
									<div class="note_term_list"> 									
									<?php									
									$notelist = \App\Models\Note::where('client_id', $fetchedData->id)->where('type', 'agent')->orderby('created_at', 'DESC')->get();
									foreach($notelist as $list){
										$admin = \App\Models\Staff::find($list->user_id);
									?>
										<div class="note_col" id="note_id_{{$list->id}}"> 
											<div class="note_content">
												<h4><a class="viewnote" data-id="{{$list->id}}" href="javascript:;">{{ @$list->title == "" ? config('constants.empty') : str_limit(@$list->title, '19', '...') }}</a></h4>
												<p><?php //echo @$list->description == "" ? config('constants.empty') : str_limit(@$list->description, '15', '...'); ?></p>
											</div>
											<div class="extra_content">
												<div class="left">
													<div class="author">
														<a href="#">{{substr($admin->first_name, 0, 1)}}</a>
													</div>
													<div class="note_modify">
														<small>Last Modified <span>{{date('Y-m-d', strtotime($list->updated_at))}}</span></small>
													</div>
												</div>  
												<div class="right">
													<div class="dropdown d-inline dropdown_ellipsis_icon">
														<a class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">@icon('ellipsis-v')</a>
														<div class="dropdown-menu">
															<a class="dropdown-item opennoteform" data-id="{{$list->id}}" href="javascript:;">Edit</a>
															<a data-id="{{$list->id}}" data-href="deletenote" class="dropdown-item deletenote" href="javascript:;" >Delete</a>
														</div>
													</div>
												</div>
											</div>
										</div>
									<?php } ?>
									</div>
									<div class="clearfix"></div>
								</div>
								<div class="tab-pane fade" id="reffered_client" role="tabpanel" aria-labelledby="reffered_client-tab">
									<div class="table-responsive"> 
										<table class="table text_wrap table-2">
											<thead>
												<tr>
													<th>Name</th>
													<th>Tag</th>
													<th>City / Country</th>
													<th>Status</th>
													<th>Assignee</th>
													<th>Added Date</th>
												</tr> 
											</thead>
											<tbody class="referredclienttdata">
												<?php
													foreach(\App\Models\Admin::where('agent_id', $fetchedData->id)->orderby('created_at','Desc')->get() as $reflist){
													//$productdetail = \App\Models\Product::where('id', $alist->product_id)->first();
													//$partnerdetail = \App\Models\Partner::where('id', $alist->partner_id)->first();
													//$PartnerBranch = \App\Models\PartnerBranch::where('id', $alist->branch)->first();
													//$workflow = \App\Models\Workflow::where('id', $alist->workflow)->first();
												?>
												<tr id="id_{{$reflist->id}}">
													<td>{{$reflist->first_name}} {{$reflist->last_name}}</td>
													<td>-</td>
													<td>{{$reflist->city}}<br/>{{$reflist->country}}</td>
													<td><span class="ag-label--circular" style="color: #6777ef" >In Progress</span></td>
													<td>{{$reflist->assignee}}</td>
													<td>{{date('Y-m-d', strtotime($reflist->created_at))}}</td>
												</tr>
												<?php
													} 
												?>
											</tbody>
											<tbody>
												<tr>
													<td style="text-align:center;" colspan="10">
														No Record found
													</td>
												</tr>
											</tbody>
										</table>
									</div>
								</div>
								<div class="tab-pane fade <?php if(isset($_GET['tab']) && $_GET['tab'] == 'application'){ echo 'show active'; } ?>" id="application" role="tabpanel" aria-labelledby="application-tab">
									<div class="card-header-action text-end if_applicationdetail" style="padding-bottom:15px;">
										
									</div>									
									<div class="table-responsive if_applicationdetail"> 
										<table class="table text_wrap table-2">
											<thead>
												<tr>
													<th>Contact</th>
													<th>Name</th>
													<th>Current Stage</th>
													<th>Status</th>
													
													<th>Started</th>
													<th>Last Updated</th>
													
												</tr> 
											</thead>
											<tbody class="applicationtdata">
											<?php
											foreach(\App\Models\Application::where('sub_agent', $fetchedData->id)->orwhere('super_agent', $fetchedData->id)->orderby('created_at','Desc')->get() as $alist){
												$productdetail = \App\Models\Product::where('id', $alist->product_id)->first();
				$partnerdetail = \App\Models\Partner::where('id', $alist->partner_id)->first();
				$PartnerBranch = \App\Models\PartnerBranch::where('id', $alist->branch)->first();
				$workflow = \App\Models\Workflow::where('id', $alist->workflow)->first();
				$clientdetail = \App\Models\Admin::where('id', $alist->client_id)->first();
												?>
												<tr id="id_{{$alist->id}}">
													<td><a href="{{URL::to('clients/detail/')}}/{{base64_encode(convert_uuencode(@$clientdetail->id))}}?tab=application">{{@$clientdetail->first_name}} {{@$clientdetail->last_name}}</a><br/>{{@$clientdetail->email}}</td> 
													<td><a href="{{URL::to('clients/detail/')}}/{{base64_encode(convert_uuencode(@$clientdetail->id))}}?tab=application">{{$productdetail->name}}</a> <br><small>{{$partnerdetail->partner_name}} ({{$PartnerBranch->name}})</small></td> 
													<td>{{$alist->stage}}<br>{{$workflow->name}}</td>
												
													<td>
													@if($alist->status == 0)
													<span class="ag-label--circular" style="color: #6777ef" >In Progress</span>
													@elseif($alist->status == 1)
														<span class="ag-label--circular" style="color: #6777ef" >Completed</span>
													@endif
												</td> 
												
													<td>{{date('Y-m-d', strtotime($alist->created_at))}}</td> 
													<td>{{date('Y-m-d', strtotime($alist->updated_at))}}</td> 
													
												</tr>
												<?php
											} 
											?>											
												
											</tbody>
											<!--<tbody>
												<tr>
													<td style="text-align:center;" colspan="10">
														No Record found
													</td>
												</tr>
											</tbody>-->
										</table> 
									</div>
									
								</div>
								<div class="tab-pane fade" id="accounts" role="tabpanel" aria-labelledby="accounts-tab">
									<div class="row">
										<div class="col-md-12">
											<ul class="nav nav-pills" id="agents_tabs" role="tablist">
												<li class="nav-item">
													<a class="nav-link active" data-bs-toggle="tab" id="incomesharingterm-tab" href="#incomesharing" role="tab" aria-controls="incomesharingterm" aria-selected="true">Income Sharing</a>
												</li>
												<li class="nav-item">
													<a class="nav-link" data-bs-toggle="tab" id="invoices-tab" href="#invoices" role="tab" aria-controls="invoices" aria-selected="false">Invoices</a>
												</li>
											</ul> 
										</div>
										<div class="clearfix"></div>
									</div>
									<div class="tab-content" id="agentinvoiceContent" style="padding-top:15px;">
										<div class="tab-pane fade show active" id="incomesharing" role="tabpanel" aria-labelledby="incomesharing-tab">
											<div class="table-responsive"> 
												<table class="table invoicetable text_wrap">
													<thead>
														<tr>
															<th>Invoice No.</th>
															<th>Associated Office</th>
															<th>Client</th>
															<th>Application</th>
															<th>Amount</th>
															<th>Tax Amount</th>
															<th>Paid Date</th>
															<th>Paid By</th>
															<th>Status</th>
														</tr> 
													</thead>
													
												</table>
											</div>
										</div>
										<div class="tab-pane fade show active" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
											<table class="table invoicetable text_wrap">
													<thead>
														<tr>
															<th>Invoice No.</th>
															<th>Issue Date</th>
															<th>Invoice Type</th>
															<th>Client Name</th>
															<th>Partner Name</th>
															<th>Product Name</th>
															<th>Workflow</th>
															<th>Invoice Amount</th>
															<th>Amount Due</th>
															<th>Status</th>
															<th>Actions</th>
														</tr> 
													</thead>
													
												</table>
										</div>
									</div>
								</div>
							</div> 
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</div> 

@include('Admin/agents/addagentmodal')  
@include('Admin/agents/editagentmodal')  

<div id="emailmodal"  data-backdrop="static" data-keyboard="false" class="modal fade custom_modal" tabindex="-1" role="dialog" aria-labelledby="clientModalLabel" aria-hidden="true">
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


<div id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="false" class="modal fade" >
	<div class="modal-dialog">
		<div class="modal-content popUp">
			<div class="modal-body text-center">
				<button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn-close"></button>
				<h4 class="modal-title text-center message col-v-5">Do you want to delete this note?</h4> 
				<button type="submit" style="margin-top: 40px;" class="button btn btn-danger accept">Delete</button> 
				<button type="button" style="margin-top: 40px;" data-bs-dismiss="modal" class="button btn btn-secondary cancel">Cancel</button>
			</div>
		</div>
	</div>
</div>

<div id="confirmcompleteModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="false" class="modal fade" >
	<div class="modal-dialog">
		<div class="modal-content popUp">
			<div class="modal-body text-center">
				<button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn-close"></button>
				<h4 class="modal-title text-center message col-v-5">Do you want to complete the Application?</h4> 
				<button  data-id="" type="submit" style="margin-top: 40px;" class="button btn btn-danger acceptapplication">Complete</button> 
				<button type="button" style="margin-top: 40px;" data-bs-dismiss="modal" class="button btn btn-secondary cancel">Cancel</button>
			</div>
		</div>
	</div>
</div>
@endsection
@section('scripts')
<script>
jQuery(document).ready(function($){
	$(document).delegate('.opentaskview', 'click', function(){
		$('#opentaskview').modal('show');
		var v = $(this).attr('id');
		$.ajax({
			url: site_url+'/get-task-detail',
			type:'GET',
			data:{task_id:v},
			success: function(responses){
				
				$('.taskview').html(responses);
			}
		});
	});
	 function getallnotes(){
		$.ajax({
			url: site_url+'/get-notes',
			type:'GET',
			data:{clientid:'{{$fetchedData->id}}',type:'agent'},
			success: function(responses){
				
				$('.note_term_list').html(responses);
			}
		});
	} 

	var notid = '';
	var delhref = '';
	$(document).delegate('.deletenote', 'click', function(){
		$('#confirmModal').modal('show');
		notid = $(this).attr('data-id');
		delhref = $(this).attr('data-href');
	});
	$(document).delegate('#confirmModal .accept', 'click', function(){
	
		$('.popuploader').show(); 
		$.ajax({
			url: '{{URL::to('/')}}/'+delhref,
			type:'GET',
			datatype:'json',
			data:{note_id:notid},
			success:function(response){
				$('.popuploader').hide(); 
				var res = JSON.parse(response);
				$('#confirmModal').modal('hide');
				if(res.status){
					$('#note_id_'+notid).remove();
					if(delhref == 'deletedocs'){
						$('.documnetlist #id_'+notid).remove();
					} 
					getallnotes();
					
					getallactivities();
				}
			}
		});
	}); 
	
	
	$(document).delegate('.create_note', 'click', function(){
		$('#create_note').modal('show');
		$('#create_note input[name="mailid"]').val(0);
		$('#create_note input[name="title"]').val('');
		$('#create_note #appliationModalLabel').html('Create Note');
		$('#create_note input[name="title"]').val('');
					$("#create_note .tinymce-simple").val('');
					$('#create_note input[name="noteid"]').val('');                    
				if (typeof TinyMCEHelpers !== 'undefined') TinyMCEHelpers.resetBySelector("#create_note .tinymce-simple");
		if($(this).attr('datatype') == 'note'){
			$('.is_not_note').hide();
		}else{ 
		var datasubject = $(this).attr('datasubject');
		var datamailid = $(this).attr('datamailid');
			$('#create_note input[name="title"]').val(datasubject);
			$('#create_note input[name="mailid"]').val(datamailid);
			$('.is_not_note').show();
		}
	});
	
	$(document).delegate('.opentaskmodal', 'click', function(){
		$('#opentaskmodal').modal('show');
		$('#opentaskmodal input[name="mailid"]').val(0);
		$('#opentaskmodal input[name="title"]').val('');
		$('#opentaskmodal #appliationModalLabel').html('Create Note');
		$('#opentaskmodal input[name="attachments"]').val('');
		$('#opentaskmodal input[name="title"]').val('');
		$('#opentaskmodal .showattachment').val('Choose file');
              
		var datasubject = $(this).attr('datasubject');
		var datamailid = $(this).attr('datamailid');
			$('#opentaskmodal input[name="title"]').val(datasubject);
			$('#opentaskmodal input[name="mailid"]').val(datamailid);
	});
	var recipientsUrl = '{{URL::to('/clients/get-recipients')}}';
	RecipientSelect.init('#create_note .js-data-example-ajaxcc', { url: recipientsUrl, dropdownParent: '#create_note' });
	RecipientSelect.init('#opentaskmodal .js-data-example-ajaxcontact', { url: recipientsUrl, dropdownParent: '#opentaskmodal' });
	$(document).delegate('.opennoteform', 'click', function(){
		$('#create_note').modal('show');
		$('#create_note #appliationModalLabel').html('Edit Note');
		var v = $(this).attr('data-id');
		$('#create_note input[name="noteid"]').val(v);
			$('.popuploader').show(); 
		$.ajax({
			url: '{{URL::to('/getnotedetail')}}',
			type:'GET',
			datatype:'json',
			data:{note_id:v},
			success:function(response){
				$('.popuploader').hide(); 
				var res = JSON.parse(response);
				
				if(res.status){
					$('#create_note input[name="title"]').val(res.data.title);
					$("#create_note .tinymce-simple").val(res.data.description);                    
				if (typeof TinyMCEHelpers !== 'undefined') TinyMCEHelpers.setContentBySelector("#create_note .tinymce-simple", res.data.description);
				}
			}
		});
	});
	$(document).delegate('.viewnote', 'click', function(){
		$('#view_note').modal('show');
		var v = $(this).attr('data-id');
		$('#view_note input[name="noteid"]').val(v);
			$('.popuploader').show(); 
		$.ajax({
			url: '{{URL::to('/viewnotedetail')}}',
			type:'GET',
			datatype:'json',
			data:{note_id:v},
			success:function(response){
				$('.popuploader').hide(); 
				var res = JSON.parse(response);
				
				if(res.status){
					$('#view_note .modal-body .note_content h5').html(res.data.title);
					$("#view_note .modal-body .note_content p").html(res.data.description);                    
					
				} 
			}
		});
	});
	// Add Application modal cascade: application-modal-cascade.js (Tom Select)
	
	
	
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
 $(document).delegate('.change_client_status', 'click', function(e){
	
	var v = $(this).attr('rating');
	$('.change_client_status').removeClass('active');
	$(this).addClass('active');
	
	 $.ajax({
		url: '{{URL::to('/change-client-status')}}',
		type:'GET',
		datatype:'json',
		data:{id:'{{$fetchedData->id}}',rating:v},
		success: function(response){
			var res = JSON.parse(response);
			if(res.status){
				
				$('.custom-error-msg').html('<span class="alert alert-success">'+res.message+'</span>');
				getallactivities();
			}else{
				$('.custom-error-msg').html('<span class="alert alert-danger">'+response.message+'</span>');
			}
			
		}
	}); 
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
			 if (typeof TinyMCEHelpers !== 'undefined') { TinyMCEHelpers.resetBySelector("#emailmodal .tinymce-simple"); TinyMCEHelpers.setContentBySelector("#emailmodal .tinymce-simple", res.description); }
					$("#emailmodal .tinymce-simple").val(res.description); 
			
		}
	});
});

$(document).delegate('.selectapplicationtemplate', 'change', function(){
	var v = $(this).val();
	$.ajax({
		url: '{{URL::to('/get-templates')}}',
		type:'GET',
		datatype:'json',
		data:{id:v},
		success: function(response){
			var res = JSON.parse(response);
			$('.selectedappsubject').val(res.subject);
			 if (typeof TinyMCEHelpers !== 'undefined') { TinyMCEHelpers.resetBySelector("#applicationemailmodal .tinymce-simple"); TinyMCEHelpers.setContentBySelector("#applicationemailmodal .tinymce-simple", res.description); }
					$("#applicationemailmodal .tinymce-simple").val(res.description); 
			
		}
	});
});
	RecipientSelect.init('#emailmodal .js-data-example-ajax', { url: recipientsUrl, dropdownParent: '#emailmodal' });
	RecipientSelect.init('#emailmodal .js-data-example-ajaxcc', { url: recipientsUrl, dropdownParent: '#emailmodal' });

/* $(".table-2").dataTable({
	"searching": false,
	"lengthChange": false,
  "columnDefs": [
    { "sortable": false, "targets": [0, 2, 3] }
  ],
  order: [[1, "desc"]] //column indexes is zero based

}); */

$(".invoicetable").dataTable({
	"searching": false,
	"lengthChange": false,
  "columnDefs": [
    { "sortable": false, "targets": [0, 2, 3] }
  ],
  order: [[1, "desc"]] //column indexes is zero based

});


$(document).delegate('#intrested_workflow', 'change', function(){
	
				var v = $('#intrested_workflow option:selected').val();
				
				if(v != ''){
						$('.popuploader').show();
		$.ajax({
			url: '{{URL::to('/getpartner')}}',
			type:'GET',
			data:{cat_id:v},
			success:function(response){
				$('.popuploader').hide();
				$('#intrested_partner').html(response);
				
				$("#intrested_partner").val('').trigger('change');
			$("#intrested_product").val('').trigger('change');
			$("#intrested_branch").val('').trigger('change');
			}
		});
				}
	});
	
	$(document).delegate('#intrested_partner','change', function(){
		
				var v = $('#intrested_partner option:selected').val();
				if(v != ''){
					$('.popuploader').show();
		$.ajax({
			url: '{{URL::to('/getproduct')}}',
			type:'GET',
			data:{cat_id:v},
			success:function(response){
				$('.popuploader').hide();
				$('#intrested_product').html(response);
				$("#intrested_product").val('').trigger('change');
			$("#intrested_branch").val('').trigger('change');
			}
		});
				}
	});
	
	
	
	// Task system removed - December 2025 (dead code - modal is commented out)
/*$(document).delegate('.opencreate_task', 'click', function () {
	$('#tasktermform')[0].reset();
	$('#tasktermform select').val('').trigger('change');
	$('.create_task').modal('show');
	$('.ifselecttask').hide();
	$('.ifselecttask select').attr('data-valid', '');
	
});*/
	
	$(document).delegate('.opencommissioninvoice', 'click', function(){
		$('#opencommissionmodal').modal('show');
	});
	
	$(document).delegate('.opengeneralinvoice', 'click', function(){
		$('#opengeneralinvoice').modal('show');
	});
	 

$(document).delegate('.addpaymentmodal','click', function(){
		var v = $(this).attr('data-invoiceid');
		var netamount = $(this).attr('data-netamount');
		var dueamount = $(this).attr('data-dueamount');
		$('#invoice_id').val(v);
		$('.invoicenetamount').html(netamount+' AUD');
		$('.totldueamount').html(dueamount);
		$('.totldueamount').attr('data-totaldue', dueamount);
		$('#addpaymentmodal').modal('show');
		$('.payment_field_clone').remove();
		$('.paymentAmount').val('');
	});	

$(document).delegate('.paymentAmount','keyup', function(){
		grandtotal();	
			
		});
		function grandtotal(){
			var p =0;
			$('.paymentAmount').each(function(){
				if($(this).val() != ''){
					p += parseFloat($(this).val());
				}
			});
		
			var tamount = $('.totldueamount').attr('data-totaldue');
			
			var am = parseFloat(tamount) - parseFloat(p);
			$('.totldueamount').html(am.toFixed(2));
		}
	$('.add_payment_field a').on('click', function(){
		var clonedval = $('.payment_field .payment_field_row .payment_first_step').html();
		$('.payment_field .payment_field_row').append('<div class="payment_field_col payment_field_clone">'+clonedval+'</div>');
	}); 
	$('.add_fee_type a.fee_type_btn').on('click', function(){ 
		var clonedval = $('.fees_type_sec .fee_type_row .fees_type_col').html();
		$('.fees_type_sec .fee_type_row').append('<div class="custom_type_col fees_type_clone">'+clonedval+'</div>');
	});
	$(document).delegate('.payment_field_col .field_remove_col a.remove_col', 'click', function(){ 
		var $tr    = $(this).closest('.payment_field_clone');
		var trclone = $('.payment_field_clone').length;		
		if(trclone > 0){
			$tr.remove();
			grandtotal();
		} 
	});
	$(document).delegate('.fees_type_sec .fee_type_row .fees_type_clone a.remove_btn', 'click', function(){ 
		var $tr    = $(this).closest('.fees_type_clone');
		var trclone = $('.fees_type_clone').length;		
		if(trclone > 0){
			$tr.remove();
			grandtotal();
		} 
	});	
	
	<?php
	if(isset($_GET['tab']) && $_GET['tab'] == 'application'){
		?>
		var appliid = '{{@$_GET['appid']}}';
		$('.if_applicationdetail').hide();
		$('.ifapplicationdetailnot').show();
		$.ajax({
			url: '{{URL::to('/getapplicationdetail')}}',
			type:'GET',
			data:{id:appliid},
		success:function(response){
			$('.popuploader').hide();
			$('.ifapplicationdetailnot').html(response);
			
			if (typeof flatpickr !== 'undefined') {
				flatpickr('.datepicker', {
					dateFormat: "Y-m-d",
					allowInput: true,
					onChange: function(selectedDates, dateStr, instance) {
						if (selectedDates.length > 0) {
							$.ajax({
								url:"{{URL::to('/application/updateintake')}}",
								method: "GET",
								dataType: "json",
								data: {from: dateStr, appid: appliid},
								success:function(result) {
									console.log("sent back -> do whatever you want now");
								}
							});
						}
					}
				});
			}
			if (typeof initExpectDatepickers === 'function') {
				initExpectDatepickers('.ifapplicationdetailnot', appliid);
			}
			

		}
	});
	<?php
	}
	?>
$(document).delegate('.discon_application', 'click', function(){
	var appliid = $(this).attr('data-id');
	$('#discon_application').modal('show');
	$('input[name="diapp_id"]').val(appliid);
});

$(document).delegate('.revertapp', 'click', function(){
	var appliid = $(this).attr('data-id');
	$('#revert_application').modal('show');
	$('input[name="revapp_id"]').val(appliid);
});
$(document).delegate('.completestage', 'click', function(){
	var appliid = $(this).attr('data-id');
	$('#confirmcompleteModal').modal('show');
	$('.acceptapplication').attr('data-id',appliid)

});
$(document).delegate('.openapplicationdetail', 'click', function(){
		var appliid = $(this).attr('data-id');
		$('.if_applicationdetail').hide();
		$('.ifapplicationdetailnot').show();
		$.ajax({
			url: '{{URL::to('/getapplicationdetail')}}',
			type:'GET',
			data:{id:appliid},
			success:function(response){
				$('.popuploader').hide();
				$('.ifapplicationdetailnot').html(response);
				
				if (typeof flatpickr !== 'undefined') {
					flatpickr('.datepicker', {
						dateFormat: "Y-m-d",
						allowInput: true,
						onChange: function(selectedDates, dateStr, instance) {
							if (selectedDates.length > 0) {
								$.ajax({
									url:"{{URL::to('/application/updateintake')}}",
									method: "GET",
									dataType: "json",
									data: {from: dateStr, appid: appliid},
									success:function(result) {
										console.log("sent back -> do whatever you want now");
									}
								});
							}
						}
					});
				}
				if (typeof initExpectDatepickers === 'function') {
					initExpectDatepickers('.ifapplicationdetailnot', appliid);
				}
				

			}
		});
	});
	
	$(document).delegate('#application-tab', 'click', function(){
		
		$('.if_applicationdetail').show();
		$('.ifapplicationdetailnot').hide();
		$('.ifapplicationdetailnot').html('<h4>Please wait ...</h4>');
	});
	
$(document).delegate('.openappnote', 'click', function(){
	var apptype = $(this).attr('data-app-type');
	var id = $(this).attr('data-id');
	$('#create_applicationnote #noteid').val(id);
	$('#create_applicationnote #type').val(apptype);
	$('#create_applicationnote').modal('show');
}); 
$(document).delegate('.openappappoint', 'click', function(){
	var id = $(this).attr('data-id');
	var apptype = $(this).attr('data-app-type');
	$('#create_applicationappoint #type').val(apptype);
	$('#create_applicationappoint #appointid').val(id);
	$('#create_applicationappoint').modal('show');
});

$(document).delegate('.openclientemail', 'click', function(){
	var id = $(this).attr('data-id');
	var apptype = $(this).attr('data-app-type');
	$('#applicationemailmodal #type').val(apptype);
	$('#applicationemailmodal #appointid').val(id);
	$('#applicationemailmodal').modal('show');
});

$(document).delegate('.openchecklist', 'click', function(){
	var id = $(this).attr('data-id'); 
	$('#create_checklist #checklistid').val(id);
	$('#create_checklist').modal('show');
});

$(document).delegate('.createaddapointment', 'click', function(){
	$('#create_appoint').modal('show');
});
$(document).delegate('.due_date_sec a.due_date_btn', 'click', function(){
	$('.due_date_sec .due_date_col').show();
	$(this).hide();
});
$(document).delegate('.remove_col a.remove_btn', 'click', function(){
	$('.due_date_sec .due_date_col').hide();
	$('.due_date_sec a.due_date_btn').show();  
});
	
$(document).delegate('#notes-tab', 'click', function(){
		var appliid = $(this).attr('data-id');
		$('.if_applicationdetail').hide();
		$('.ifapplicationdetailnot').show();
		$.ajax({
			url: '{{URL::to('/getapplicationnotes')}}',
			type:'GET',
			data:{id:appliid},
			success:function(response){
				$('.popuploader').hide();
				$('#notes').html(response);
				
			}
		});
	});
	
	// Appointment modal timezone selects: initModalTomSelects on #create_appoint shown (if present)
  
  $('#attachments').on('change',function(){
       // output raw value of file input
      $('.showattachment').html(''); 

        // or, manipulate it further with regex etc.
        var filename = $(this).val().replace(/.*(\/|\\)/, '');
        // .. do your magic

       $('.showattachment').html(filename);
    });
});
</script>

@push('tinymce-scripts')
@include('partials.tinymce')
@endpush
@endsection