@extends('layouts.admin')
@section('title', 'Client Detail')

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
							<h4>Product Detail</h4>
							<div class="card-header-action">
								<a href="{{route('products.index')}}" class="btn btn-primary">Product List</a>
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
							<span class="author-avtar" style="background: rgb(68, 182, 174);"><b>{{substr($fetchedData->name, 0, 1)}}</b></span>
								<div class="clearfix"></div>
								<div class="author-box-name">
									<a href="#">{{$fetchedData->name}}</a>
								</div>
								<div class="author-mail_sms"> 
									<a href="{{ route('products.edit', ['id' => base64_encode(convert_uuencode($fetchedData->id))], false) }}" title="Edit" aria-label="Edit product">@icon('edit')</a>
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
								<span class="float-start">Partner:</span>
								<span class="float-end text-muted"><?php
								$partnerdetail = \App\Models\Partner::where('id', $fetchedData->partner)->first();
								echo $partnerdetail->partner_name;
								?></span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Branches:</span>
								<span class="float-end text-muted"><?php
								$branchesdetail = \App\Models\PartnerBranch::where('id', $fetchedData->branches)->first();
								echo @$branchesdetail->name.' ('.@$branchesdetail->city.')';
								?></span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Services:</span>
								<span class="float-end text-muted"></span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Duration:</span>
								<span class="float-end text-muted">{{$fetchedData->duration}}</span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Intake Month:</span>
								<span class="float-end text-muted"><?php if($fetchedData->preferredIntake != '-- Select Intake Month --' || $fetchedData->preferredIntake != ''){ ?>{{$fetchedData->intake_month}}<?php } ?></span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Revenue Type: </span>
								<span class="float-end text-muted">{{$fetchedData->revenue_type}}</span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Notes: </span>
								<span class="float-end text-muted">{{$fetchedData->note}}</span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Description: </span>
								<span class="float-end text-muted">{!!$fetchedData->description!!}</span>
							</p> 
						</div>
					</div>
				</div>
				<div class="col-9 col-md-9 col-lg-9">
					<div class="card">
						<div class="card-body">
							<?php
								// Define allowed tabs for products
								$allowedTabs = [
									'application',
									'promotions'
								];
								$activeTab = Request::route('tab') ?? 'application';
								if (!in_array($activeTab, $allowedTabs, true)) {
									$activeTab = 'application';
								}
							?>
							<ul class="nav nav-pills" id="client_tabs" role="tablist">
								<li class="nav-item">
									<a class="nav-link <?php echo ($activeTab === 'application') ? 'active' : ''; ?>" href="{{route('products.detail', ['id' => base64_encode(convert_uuencode($fetchedData->id))])}}" id="application-tab" role="tab" aria-controls="application" aria-selected="<?php echo ($activeTab === 'application') ? 'true' : 'false'; ?>">Applications</a>
								</li>
								<li class="nav-item">
									<a class="nav-link <?php echo ($activeTab === 'promotions') ? 'active' : ''; ?>" href="{{route('products.detail', ['id' => base64_encode(convert_uuencode($fetchedData->id)), 'tab' => 'promotions'])}}" id="promotions-tab" role="tab" aria-controls="promotions" aria-selected="<?php echo ($activeTab === 'promotions') ? 'true' : 'false'; ?>">Promotions</a>
								</li>
							</ul> 
							<div class="tab-content" id="clientContent" style="padding-top:15px;">
								<div class="tab-pane fade <?php echo ($activeTab === 'application') ? 'show active' : ''; ?>" id="application" role="tabpanel" aria-labelledby="application-tab">
																		
									<div class="table-responsive if_applicationdetail"> 
										<table class="table text_wrap table-2">
											<thead>
												<tr>
													<th>Client Name</th>
													<th>Assignee</th>
													<th>Branch Name</th>
													<th>Workflow</th>
													<th>Current Stage</th>
													<th>Status</th>
													<th>Added On</th>
													<th>Last Updated</th>
													
												</tr> 
											</thead>
											<tbody class="applicationtdata">
											<?php
											foreach(\App\Models\Application::where('product_id', $fetchedData->id)->orderby('created_at','Desc')->get() as $alist){
												$productdetail = \App\Models\Product::where('id', $alist->product_id)->first();
												$clientdetail = \App\Models\Admin::where('id', $alist->client_id)->first();
												$admindetail = \App\Models\Staff::find($alist->user_id);
				$partnerdetail = \App\Models\Partner::where('id', $alist->partner_id)->first();
				$PartnerBranch = \App\Models\PartnerBranch::where('id', $alist->branch)->first();
				$workflow = \App\Models\Workflow::where('id', $alist->workflow)->first();
												?>
												<tr id="id_{{$alist->id}}">
													<td><a class="" data-id="{{$alist->id}}" href="{{URL::to('clients/detail/')}}/{{base64_encode(convert_uuencode(@$clientdetail->id))}}?tab=application&appid={{@$alist->id}}" style="display:block;">{{@$clientdetail->first_name}} {{@$clientdetail->last_name}}</a> </td> 
													<td>{{$admindetail->first_name}} {{$admindetail->last_name}}</td>
													<td>{{$PartnerBranch->name}}</td>
													<td>
													{{$workflow->name}}
													</td> 
													<td>{{$alist->stage}}</td>
													<td>@if($alist->status == 0)
													<span class="ag-label--circular" style="color: #6777ef" >In Progress</span>
													@elseif($alist->status == 1)
														<span class="ag-label--circular" style="color: #6777ef" >Completed</span>
													@endif</td> 
													<td>{{date('Y-m-d', strtotime($alist->created_at))}}</td> 
													<td>
													{{date('Y-m-d', strtotime($alist->updated_at))}}
													</td>
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
								<div class="tab-pane fade <?php echo ($activeTab === 'promotions') ? 'show active' : ''; ?>" id="promotions" role="tabpanel" aria-labelledby="promotions-tab">
									<div class="promotionlists"> 
									<?php
									$promotionslist = \App\Models\Promotion::where('apply_to', 'All Products') ->orwhereRaw('? = ANY(string_to_array(selectproduct, \',\'))', [$fetchedData->id])->orderby('created_at','DESC')->get();
									foreach($promotionslist as $promotion){
										$countproducts = 0;
										$countbranches = 0;
										if($promotion->apply_to == 'All Products'){
											$countproducts = \App\Models\Product::where('partner', $fetchedData->id)->count();
											$countbranches = \App\Models\PartnerBranch::where('partner_id', $fetchedData->id)->count();
										}else{
											$selectproduct = explode(',',$promotion->selectproduct);
											$countproducts = count($selectproduct);
											$branch = \App\Models\Product::select('branches')->whereIn('id', $selectproduct)->get()->toArray();
											$output =  array_map("unserialize", array_unique(array_map("serialize", $branch)));
											$countbranches = count($output);
										}
									?>
										<div class="promotion_col" id="contact_<?php echo $promotion->id; ?>"> 
											<div class="promotion_content">
											@if($promotion->status == 1)
												<span class="text-success"><b>Active</b></span>
											@else
												<span class="text-danger"><b>Inactive</b></span>
											@endif
												<div class="" style="margin-top: 15px!important;">
													<h4>{{$promotion->promotion_title}}</h4>
													<p>{{ @$promotion->promotion_desc == "" ? config('constants.empty') : str_limit(@$promotion->promotion_desc, '50', '...') }}</p>
												</div>
												<div class="" style="margin-top: 15px!important;">
													<div class="row">
														<div class="col-md-6">
														<span class="text-semi-bold text-underline">For Branches</span>
														</div>
														<div class="col-md-6">
														<span  class="">{{$countbranches}}</span>
														</div>
													</div>
													<div class="row">
														<div class="col-md-6">
														<span class="text-semi-bold text-underline">For Products</span>
														</div>
														<div class="col-md-6">
														<span  class="">{{$countproducts}}</span>
														</div>
													</div>
												</div>
												<div class="" style="margin-top: 15px!important;">
													<div class="row">
														<div class="col-md-6">
														<span ><b>Start Date</b></span>
														<p>{{$promotion->promotion_start_date}}</p>
														</div>
														<div class="col-md-6">
														<span ><b>Expiry Date</b></span>
														<p>{{$promotion->promotion_end_date}}</p>
														</div>
													</div>
												</div>
											</div>
											<div class="extra_content">
												<div class="view_btn text-end">
													<a href="#" class="btn btn-outline-primary">View</a>
												</div>  
											</div>
										</div>
									<?php } ?>
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

@include('Admin/products/addproductmodal')  
@include('Admin/products/editproductmodal')    

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
		data:{clientid:'{{$fetchedData->id}}'},
		success: function(responses){
			
			$('.note_term_list').html(responses);
		}
	});
}

function getallactivities(){
	$.ajax({
					url: site_url+'/get-activities',
					type:'GET',
					dataType:'json',
					data:{id:'{{$fetchedData->id}}'},
					success: function(responses){
						if (typeof applyActivitiesResponse === 'function') {
							applyActivitiesResponse(responses);
							return;
						}
						var ress = typeof responses === 'string' ? JSON.parse(responses) : responses;
						if (ress && ress.html) {
							$('.activities').html(ress.html);
						}
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
					getallnotes();
					
					//getallactivities();
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

$(".table-2").dataTable({
	"searching": false,
	"lengthChange": false,
  "columnDefs": [
    { "sortable": false, "targets": [0, 2, 3] }
  ],
  order: [[1, "desc"]] //column indexes is zero based

});

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
	
	$(document).delegate('#intrested_product','change', function(){
		
				var v = $('#intrested_product option:selected').val();
				if(v != ''){
					$('.popuploader').show();
		$.ajax({
			url: '{{URL::to('/getbranch')}}',
			type:'GET',
			data:{cat_id:v},
			success:function(response){
				$('.popuploader').hide();
				$('#intrested_branch').html(response);
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
	$(document).delegate('.payment_field_col .field_remove_col a.remove_col', 'click', function(){ 
		var $tr    = $(this).closest('.payment_field_clone');
		var trclone = $('.payment_field_clone').length;		
		if(trclone > 0){
			$tr.remove();
			grandtotal();
		} 
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
	
$(document).delegate('.nextstage', 'click', function(){
	var appliid = $(this).attr('data-id');
	var stage = $(this).attr('data-stage');
	$('.popuploader').show();
	$.ajax({
		url: '{{URL::to('/updatestage')}}',
		type:'GET',
		datatype:'json',
		data:{id:appliid, client_id:'{{$fetchedData->id}}'},
		success:function(response){
			$('.popuploader').hide();
			var obj = $.parseJSON(response);
			if(obj.status){
				$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
				$('.curerentstage').text(obj.stage);
				$.ajax({
					url: site_url+'/get-applications-logs',
					type:'GET',
					data:{clientid:'{{$fetchedData->id}}',id: appliid},
					success: function(responses){
						 
						$('#accordion').html(responses);
					}
				});
			}else{
				$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
			}
		}
	});
});

$(document).delegate('.backstage', 'click', function(){
	var appliid = $(this).attr('data-id');
	var stage = $(this).attr('data-stage');
	$('.popuploader').show();
	$.ajax({
		url: '{{URL::to('/updatebackstage')}}',
		type:'GET',
		datatype:'json',
		data:{id:appliid, client_id:'{{$fetchedData->id}}'},
		success:function(response){
			var obj = $.parseJSON(response);
			$('.popuploader').hide();
			if(obj.status){
				$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
				$('.curerentstage').text(obj.stage);
				$.ajax({
					url: site_url+'/get-applications-logs',
					type:'GET',
					data:{clientid:'{{$fetchedData->id}}',id: appliid},
					success: function(responses){
						 
						$('#accordion').html(responses);
					}
				});
			}else{
				$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
			}
		}
	});
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
@endsection
