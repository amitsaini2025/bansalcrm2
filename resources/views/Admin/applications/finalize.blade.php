@extends('layouts.admin')
@section('title', 'Applications finalized')

@section('content')
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<style>
    .filter_panel {background: #f7f7f7;margin-bottom: 10px;border: 1pxsolid #eee;display: none;}
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
							<h4>All Finalized Applications</h4>
							<div class="card-header-action is_checked_clientn">
								<a href="#" class="btn btn-primary importmodal"> Import csv</a>
							</div>
							<div class="card-header-action">
							        <a href="javascript:;" class="btn btn-theme btn-theme-sm filter_btn">@icon('filter') Filter</a>
							 </div>
						</div>
						<div class="card-body">
                            <ul class="nav nav-pills" id="application_tabs" role="tablist">
								<li class="nav-item">
									<a class="nav-link" id="applications-tab"  href="{{URL::to('/applications')}}" >All</a>
								</li>
								<li class="nav-item">
									<a class="nav-link" id="applications-overdue-tab"  href="{{URL::to('/applications-overdue')}}" >Overdue</a>
								</li>
                                <li class="nav-item">
									<a class="nav-link" id="applications-finalize-tab"  href="{{URL::to('/applications-finalize')}}" >Finalized</a>
								</li>
							</ul>
						    <div class="filter_panel">
								<h4>Search By Details</h4>
								<form action="{{URL::to('/applications-finalize')}}" method="get">
									<div class="row">
										<div class="col-md-4">
											<div class="form-group">
												<label for="ass_id" class="col-form-label">Assignee</label>
												{!! Form::text('ass_id', Request::get('ass_id'), array('class' => 'form-control assignee', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'Assignee', 'id' => 'ass_id', 'onkeyup' => "suggestassignee(this.value)" ))  !!}
											</div>
											<input type="hidden" value="{{Request::get('assignee')}}" id="assigneeid" name="assignee">
										</div>
										<div class="col-md-4">
											<div class="form-group">
											    <?php
											    //$par = \App\Models\Partner::where('id', Request::get('partner'))->first();
											    ?>
												<label for="partner" class="col-form-label">Partner</label>
												{{--Form::text('partner', @$par->partner_name, array('class' => 'form-control agent_company_name', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'Partner', 'id' => 'partner', 'onkeyup' => "suggest(this.value)" ))--}}
                                                <!--<input type="hidden" value="{{Request::get('partner')}}" id="partnerid" name="partner">-->

                                                <select class="form-control" name="partner">
												    <option value="">Select Partner</option>
												    @foreach($allpartners as $allpartner)
                                                    <option @if(Request::get('partner') == $allpartner->id) selected @endif value="{{$allpartner->id}}">{{$allpartner->partner_name}}</option>
                                                    @endforeach
												</select>
                                            </div>
										</div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
											<div class="form-group">
												<label for="" class="col-form-label">Stage</label>
												<select class="form-control" name="stage">
												    <option value="">Select Stage</option>
												    @foreach($allstages as $allstage)
											        <option @if(Request::get('stage') == $allstage->stage) selected @endif value="{{$allstage->stage}}">{{$allstage->stage}}</option>
											        @endforeach
												</select>
											</div>
										</div>

                                        <div class="col-md-4">
											<div class="form-group">
												<label for="" class="col-form-label">Status</label>
												<select class="form-control" name="status">
												    <option value="">Select Status</option>
                                                    <option value="0" @if(Request::filled('status') && (string) Request::get('status') === '0') selected @endif>In Progress</option>
                                                    <option value="1" @if(Request::filled('status') && (string) Request::get('status') === '1') selected @endif>Completed</option>
                                                    <option value="2" @if(Request::filled('status') && (string) Request::get('status') === '2') selected @endif>Discontinued</option>
                                                    <option value="3" @if(Request::filled('status') && (string) Request::get('status') === '3') selected @endif>Cancelled</option>
                                                </select>
											</div>
										</div>
                                    </div>

									<div class="row">
										<div class="col-md-12 text-center">
                                            {!! Form::submit('Search', ['class'=>'btn btn-primary btn-theme-lg' ])  !!}
											<a class="btn btn-info" href="{{URL::to('/applications-finalize')}}">Reset</a>
										</div>
									</div>
								</form>
							</div>
							<div class="table-responsive">
								<table class="table text_wrap">
									<thead>
										<tr>
											<th>Application ID</th>
											<th>Client Name</th>

											<!--<th>Client Phone</th>-->
											<th>Client Assignee</th>

											<th>Product</th>
											<th>Partner</th>
											<th>Partner Branch</th>

											<th>Workflow</th>
											<th>Stage</th>

											<th>Branch</th>
											<th>Status</th>

											<th>Created At</th>
											<th></th>
										</tr>
									</thead>
									@if(@$totalData !== 0)
									@foreach (@$lists as $list)
									<?php
									$productdetail = \App\Models\Product::where('id', $list->product_id)->first();
									$partnerdetail = \App\Models\Partner::where('id', $list->partner_id)->first();
									$clientdetail = \App\Models\Admin::where('id', $list->client_id)->first();
									$PartnerBranch = \App\Models\PartnerBranch::where('id', $list->branch)->first();
									$workflow = \App\Models\Workflow::where('id', $list->workflow)->first();
									?>
									<tbody class="tdata">
										<tr id="id_{{@$list->id}}">
											<td style="white-space: initial;"><a href="{{URL::to('clients/detail/')}}/{{base64_encode(convert_uuencode(@$clientdetail->id))}}?tab=application&appid={{@$list->id}}">{{ @$list->id == "" ? config('constants.empty') : str_limit(@$list->id, '50', '...') }}</a></td>
											<td style="white-space: initial;"><a href="{{URL::to('clients/detail/')}}/{{base64_encode(convert_uuencode(@$clientdetail->id))}}?tab=application">{{@$clientdetail->first_name}} {{@$clientdetail->last_name}}</a><!--<br/>-->{{--@$clientdetail->email--}}</td>

											{{--<td>@$clientdetail->phone }}</td> --}}
											<td style="white-space: initial;">{{@$list->application_assignee->first_name}}</td>

											<td style="white-space: initial;">{{@$productdetail->name}}</td>
											<td style="white-space: initial;">{{@$partnerdetail->partner_name}}</td>
											<td style="white-space: initial;">{{$PartnerBranch->name ?? 'N/P'}}</td>

											<td style="white-space: initial;"><?php echo @$workflow->name; ?></td>
											<td style="white-space: initial;">{{ @$list->stage == "" ? config('constants.empty') : str_limit(@$list->stage, '50', '...') }}</td>

											<td style="white-space: initial;">{{$PartnerBranch->name ?? 'N/P'}}</td>
											<td>
                                                <?php if($list->status == 0){ ?>
                                                    <span class="ag-label--circular" style="color: #6777ef" >In Progress</span>
                                                <?php }else if($list->status == 1){ ?>
                                                    <span class="ag-label--circular" style="color: #6777ef" >Completed</span>
                                                <?php } else if($list->status == 2){ ?>
                                                    <span class="ag-label--circular" style="color: red;" >Discontinued</span>
                                                <?php } else if($list->status == 3){ ?>
                                                    <span class="ag-label--circular" style="color: red;" >Cancelled</span>
                                                <?php }?>
                                            </td>

											<!--<td>{{ @$list->created_at == "" ? config('constants.empty') : str_limit(@$list->created_at , '50', '...') }}</td>-->
											<td style="white-space: initial;">{{ @$list->created_at == "" ? config('constants.empty') : date('d/m/Y', strtotime($list->created_at))  }}</td>

                                            <td>
												<div class="dropdown d-inline">
													<button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
													<div class="dropdown-menu">


													</div>
												</div>
											</td>
										</tr>
									@endforeach
									</tbody>
									@else
									<tbody>
										<tr>
											<td style="text-align:center;" colspan="12">
												No Record found
											</td>
										</tr>
									</tbody>
									@endif
								</table>
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

@endsection
@section('scripts')
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
<script>
    let currentUrl = window.location.href; //console.log(currentUrl);
    var currentUrlArr = currentUrl.split("/"); //console.log(currentUrlArr[4]);
    if( currentUrlArr.length >0 ){
        if(currentUrlArr[4] == 'applications'){
            $('a#applications-tab').addClass('active');
        } else if(currentUrlArr[4] == 'applications-overdue'){
            $('a#applications-overdue-tab').addClass('active');
        } else if(currentUrlArr[4] == 'applications-finalize'){
            $('a#applications-finalize-tab').addClass('active');
        }
    }

 function suggest(inputString) {
$( ".agent_company_name" ).autocomplete({
	autoFocus: true,
	minLength : 2,
	source : function(request, response) {
    $.ajax({
        type: "GET",
        url: "{{URL::to('/')}}/getpartnerajax",
        dataType : "json",
        cache : false,
        data: {likewith : 'agent_company_name', likevalue: inputString},
        success:
            function(data){
                var all_l=[];
                for(var i=0;i<data.length;i++)
                {
                    var city_name=data[i].agent_company_name;
                     var y=data[i].id;
                    all_l.push({ "label": city_name, "value": city_name , "s": y } );
                }
                response(all_l);
            }
        });
    },
});

}

$('.agent_company_name').on('autocompleteselect', function (e, ui) {
      $('#partnerid').val(ui.item.s);
    });

 function suggestassignee(inputString) {
$( ".assignee" ).autocomplete({
	autoFocus: true,
	minLength : 2,
	source : function(request, response) {
    $.ajax({
        type: "GET",
        url: "{{URL::to('/')}}/getassigneeajax",
        dataType : "json",
        cache : false,
        data: {likewith : 'assignee', likevalue: inputString},
        success:
            function(data){
                var all_l=[];
                for(var i=0;i<data.length;i++)
                {
                    var city_name=data[i].assignee;
                     var y=data[i].id;
                    all_l.push({ "label": city_name, "value": city_name , "s": y } );
                }
                response(all_l);
            }
        });
    },
});

}
$('.assignee').on('autocompleteselect', function (e, ui) {
      $('#assigneeid').val(ui.item.s);
    });
jQuery(document).ready(function($){

$('.filter_btn').on('click', function(){
		$('.filter_panel').slideToggle();
	});


});
$(document).delegate('.importmodal', 'click', function(){

$('#importmodal').modal('show');
});
</script>
@endsection
