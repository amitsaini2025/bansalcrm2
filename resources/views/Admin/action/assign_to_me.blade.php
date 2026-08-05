@extends('layouts.admin')
@section('title', 'Assigned to me')

@section('content')
<style>
.fc-event-container .fc-h-event{cursor:pointer;}
.sort_col a { color: #212529 !important; font-weight: 700 !important;}
.popover .popover-body { overflow: visible !important; }
.popover .ts-wrapper { z-index: 100001 !important; width: 100% !important; }
.popover .ts-dropdown { z-index: 100001 !important; }
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
							<!--<h4>Assignee's</h4>-->
                            <h4>Assigned to me</h4>
							<div class="card-header-action">
								<!-- <a href="{{URL::to('quotations/template/create')}}"  class="btn btn-primary is_checked_clientn">Create Template</a> -->
							</div>

                            <ul class="nav nav-pills" id="client_tabs" role="tablist">
								<!--<li class="nav-item is_checked_clientn">
									<a class="nav-link" id="clients-tab"  href="{{URL::to('/action')}}">Incomplete</a>
								</li>
								<li class="nav-item is_checked_clientn11">
									<a class="nav-link" id="archived-tab"  href="{{URL::to('/action/completed')}}">Completed</a>
								</li>


                                <li class="nav-item is_checked_clientn12">
									<a class="nav-link" id="assigned_by_me"  href="{{URL::to('/assigned_by_me')}}">Assigned by me</a>
								</li>

								<li class="nav-item is_checked_clientn13">
									<a class="nav-link active" id="assigned_to_me"  href="{{URL::to('/assigned_to_me')}}">Assigned to me</a>
								</li>-->
							</ul>
						</div>
						<div class="card-body">
							<div class="tab-content" id="quotationContent">
							<form action="{{ route('action.assigned_to_me') }}" method="get" id="assignedToMeFilters">
								<div class="row mb-2">
									<div class="col-md-12">
										<label class="form-check form-check-inline" title="Future-dated Followup actions are hidden by default until their assign date">
											<input type="checkbox" class="form-check-input" name="include_scheduled_followups" value="1"
												{{ !empty($includeScheduledFollowups) ? 'checked' : '' }}
												onchange="document.getElementById('assignedToMeFilters').submit();">
											Include scheduled follow-ups
										</label>
										<small class="text-muted d-block">Default list shows Followups only on/after their assign date.</small>
									</div>
								</div>
								<div class="row">
									<div class="col-md-3">
										<!-- <select  class="form-control mb-3" name="filter">
										<option>All assignees</option>
										<option value="today">Today</option>
										<option value="last week">Last Week</option>
										<option value="previous month">Previous Month</option>
										<option value="last 6 month">Last 6 Months</option>
										<option value="last year">Last Year</option>
										</select> -->
									</div>
									{{-- <div class="col-md-3">
									</div>
									<div class="col-md-4">
										<input type="text" class="form-control mb-3 ms-4" placeholder="Searching...." name="q">
									</div>
									<div class="col-md-2">
										<input type="submit" class="form-control mb-3 btn btn-primary" value="Search">
									</div> --}}
								</div>
							</form>
                                <h5>Incomplete List</h5>
								<div class="tab-pane fade show active" id="active_quotation" role="tabpanel" aria-labelledby="active_quotation-tab">
									<div class="table-responsive common_table">
									<!-- @if ($message = Session::get('success'))
										<div class="alert alert-success">
											<p>{{ $message }}</p>
										</div>
									@endif   -->
									<table class="table table-bordered">
										<tr>
											<th width="120px">#</th>
											<th class="sort_col">@sortablelink('first_name','Assignee name')</th>
                                            <th>Assigner name</th>
											<th>Client Reference</th>
											<th class="sort_col">@sortablelink('action_assign_date','Action Date')</th>
                                            <th class="sort_col">@sortablelink('task_group','Group')</th>
                                            <th>Note</th>
                                            <th width="180px">Action</th>

                                            <!--<th>Title</th>-->
											{{-- <th>Nature of enquiry</th> --}}
											<!--<th>Service</th>-->
                                            <!--<th>status</th>-->

										</tr>
                                        @foreach ($assignees_notCompleted as $list)
                                        <?php  //echo "<pre>list==";print_r($list);
                                            $admin = \App\Models\Staff::find($list->user_id);//dd($admin);
                                            if($admin){
                                                $first_name = $admin->first_name ?? 'N/A';
                                                $last_name = $admin->last_name ?? 'N/A';
                                                $full_name = $first_name.' '.$last_name;
                                            } else {
                                                $full_name = 'N/A';
                                            }
                                        ?>
										<tr>
                                            <?php
												if($list->noteClient){
													$user_name=$list->noteClient->first_name.' '.$list->noteClient->last_name;
												}else{
													$user_name='N/P';
												}
											?>
											<td>{{ ++$i }} &nbsp;
                                                <?php
                                                if($list->status === 1) { ?>
                                                    <a class="btn btn-dark not_complete_task" data-id="{{ $list->id }}" href="javascript:void(0)">Incomplete</a>
                                                <?php } else { ?>
                                                    <a class="btn btn-success complete_task" data-id="{{ $list->id }}" href="javascript:void(0)">Complete</a>
                                                <?php } ?>
                                            </td>
											<td>{{ $list->assigned_user->first_name ?? ''}}  {{$list->assigned_user->last_name ?? ''}}</td>
											<td>{{ $full_name??'N/P' }}</td>
                                            <td><a href="{{URL::to('/clients/detail/'.base64_encode(convert_uuencode(@$list->client_id)))}}" target="_blank" >{{ $list->noteClient->client_id ?? 'N/P' }}</a></td>
											<td>
												@if(!empty($list->action_assign_date))
													{{ date('d/m/Y', strtotime($list->action_assign_date)) }}
													@php
														$isFutureFollowup = strcasecmp((string) ($list->task_group ?? ''), 'Followup') === 0
															&& \Carbon\Carbon::parse($list->action_assign_date)->timezone(config('app.timezone'))->startOfDay()
																->gt(\Carbon\Carbon::today(config('app.timezone')));
													@endphp
													@if($isFutureFollowup)
														<span class="badge bg-info text-dark">Scheduled</span>
													@endif
												@else
													N/P
												@endif
											</td>
                                            <td>{{ $list->task_group??'N/P' }}</td>
                                            <td>{{ $list->description??'N/P' }}</td>

                                            <!--<td>$list->title??'N --}}/P' --}}</td>
											<td>{{-- $list->noteClient->service??'N/P' --}}</td>-->

											<!--@if($list->noteClient->status === 0)
											<td><span title="draft" class="badge bg-warning">Pending</span></td>
											@elseif($list->noteClient->status === 1)
											<td><span title="draft" class="badge bg-success">Approved</span></td>
											@elseif($list->noteClient->status === 'Unassigned')
											<td><span title="draft" class="badge bg-warning">Unassigned</span></td>
											@elseif($list->noteClient->status === 'Assigned')
											<td><span title="draft" class="badge bg-info">Assigned</span></td>
											@elseif($list->noteClient->status === 'In-Progress')

											<td><span title="draft" class="badge bg-primary">In-Progress</span></td>
											@elseif($list->noteClient->status === 'Closed')
											<td><span title="draft" class="badge bg-success">Closed</span></td>
											@else
											<td><span title="draft" class="badge bg-warning">Pending</span></td>
											@endif-->


											<td>
												@if($list->noteClient)
												<form action="{{ route('action.destroy_to_me',$list->id) }}" method="POST">

													{{-- <a class="btn btn-info" href="{{ route('assignees.show',$list->id) }}">Show</a> --}}

													{{--<a class="btn btn-primary" href="{{ url('/clients/edit/'.base64_encode(convert_uuencode(@$list->client_id)).'') }}">Edit</a>--}}

													@csrf
													@method('DELETE')

													<button type="submit" class="btn btn-danger" data-crm-confirm='Are you sure want to delete?'">Delete</button>
													<button type="button" class="btn btn-primary btn-block" data-bs-container="body" data-role="popover" data-bs-placement="bottom" data-html="true" data-content="<div id=&quot;popover-content&quot;>
														<h4 class=&quot;text-center&quot;>Re-Assign Staff</h4>
														<div class=&quot;clearfix&quot;></div>
													<div class=&quot;box-header with-border&quot;>
														<div class=&quot;form-group row&quot; style=&quot;margin-bottom:12px&quot; >
															<label for=&quot;inputSub3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>Select Assignee</label>
															<div class=&quot;col-sm-9&quot;>
																<select class=&quot;assignee-tomselect tomselect form-control selec_reg&quot; id=&quot;rem_cat&quot; name=&quot;rem_cat&quot; onchange=&quot;&quot;>
																	<option value=&quot;&quot; >Select</option>
																	@foreach(\App\Models\Staff::where('status',1)->orderby('first_name','ASC')->get() as $admin)
																	<?php
																	$branchname = \App\Models\Branch::where('id',$admin->office_id)->first();
																	?>
																	<option value=&quot;<?php echo $admin->id; ?>&quot;><?php echo $admin->first_name.' '.$admin->last_name.' ('.@$branchname->office_name.')'; ?></option>
																	@endforeach
																</select>
															</div>
															<div class=&quot;clearfix&quot;></div>
														</div>
													</div><div id=&quot;popover-content&quot;>
													<div class=&quot;box-header with-border&quot;>
														<div class=&quot;form-group row&quot; style=&quot;margin-bottom:12px&quot; >
															<label for=&quot;inputEmail3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>Note</label>
															<div class=&quot;col-sm-9&quot;>
																<textarea id=&quot;assignnote&quot; class=&quot;form-control tinymce-simple f13&quot; placeholder=&quot;Enter an note....&quot; type=&quot;text&quot;></textarea>
															</div>
															<div class=&quot;clearfix&quot;></div>
														</div>
													</div>
													<div class=&quot;box-header with-border&quot;>
														<div class=&quot;form-group row&quot; style=&quot;margin-bottom:12px&quot; >
															<label for=&quot;inputEmail3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>Date</label>
															<div class=&quot;col-sm-9&quot;>
																<input type="text" class="form-control f13 flatpickr-date" placeholder="yyyy-mm-dd" id="popoverdatetime" value="<?php echo date('Y-m-d');?>" name="popoverdate" autocomplete="off">
															</div>
															<div class=&quot;clearfix&quot;></div>
														</div>
													</div>

                                                    <div class=&quot;form-group row&quot; style=&quot;margin-bottom:12px&quot; >
                                                        <label for=&quot;inputSub3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>Group</label>
                                                        <div class=&quot;col-sm-9&quot;>
                                                            <select class=&quot;assignee-tomselect tomselect form-control selec_reg&quot; id=&quot;task_group&quot; name=&quot;task_group&quot;>
                                                                <option value=&quot;&quot;>Select</option>
                                                                <option value=&quot;Call&quot;>Call</option>
                                                                <option value=&quot;Checklist&quot;>Checklist</option>
                                                                <option value=&quot;Review&quot;>Review</option>
                                                                <option value=&quot;Query&quot;>Query</option>
                                                                <option value=&quot;Urgent&quot;>Urgent</option>
                                                            </select>
                                                        </div>
                                                        <div class=&quot;clearfix&quot;></div>
                                                    </div>

													<input id=&quot;assign_client_id&quot;  type=&quot;hidden&quot; value=&quot;{{base64_encode(convert_uuencode(@$list->client_id))}}&quot;>
													<div class=&quot;box-footer&quot; style=&quot;padding:10px 0&quot;>
													<div class=&quot;row&quot;>
														<input type=&quot;hidden&quot; value=&quot;&quot; id=&quot;popoverrealdate&quot; name=&quot;popoverrealdate&quot; />
													</div>
													<div class=&quot;row text-center&quot;>
														<div class=&quot;col-md-12 text-center&quot;>
														<button  class=&quot;btn btn-info&quot; id=&quot;assignUser&quot;>Assign Staff</button>
														</div>
													</div>
											</div>" data-original-title="" title="" style="width: 82px;display: inline;">Reassign</button>
									{{-- <a class="btn btn-primary openassigneview" id="{{$list->id}}" href="#">Reassign</a> --}}
												</form>
												@endif
											</td>


										</tr>
										@endforeach
									</table>
										{{-- {!! $assignees->appends(\Request::except('page'))->render() !!} --}}
   										 {!! $assignees_notCompleted->appends($_GET)->links() !!}
								</div>
								<div class="card-footer">

								</div>
							</div>



                                <h5>Completed List</h5>
								<div class="tab-pane fade show active" id="active_quotation" role="tabpanel" aria-labelledby="active_quotation-tab">
									<div class="table-responsive common_table">
									<!-- @if ($message = Session::get('success'))
										<div class="alert alert-success">
											<p>{{ $message }}</p>
										</div>
									@endif   -->
									<table class="table table-bordered">
										<tr>
											<th width="125px">#</th>
											<th class="sort_col">@sortablelink('first_name','Assignee name')</th>
                                            <th>Assigner name</th>
											<th>Client Reference</th>
											<th class="sort_col">@sortablelink('action_assign_date','Action Date')</th>
                                            <th class="sort_col">@sortablelink('task_group','Group')</th>
                                            <th>Note</th>
                                            <th width="180px">Action</th>

                                            <!--<th>Title</th>-->
											{{-- <th>Nature of enquiry</th> --}}
											<!--<th>Service</th>-->
                                            <!--<th>status</th>-->

										</tr>
                                        @foreach ($assignees_completed as $keyC=>$listC)
                                        <?php  //echo "<pre>$listC==";print_r($listC);
                                            $adminC = \App\Models\Staff::find($listC->user_id);//dd($admin);
                                            if($adminC){
                                                $first_nameC = $adminC->first_name ?? 'N/A';
                                                $last_nameC = $adminC->last_name ?? 'N/A';
                                                $full_nameC = $first_nameC.' '.$last_nameC;
                                            } else {
                                                $full_nameC = 'N/A';
                                            }
                                        ?>
										<tr>
                                            <?php
												if($listC->noteClient){
													$user_name=$listC->noteClient->first_name.' '.$listC->noteClient->last_name;
												}else{
													$user_name='N/P';
												}
											?>
											<td>{{ ($keyC+1) }} &nbsp;
                                                <?php
                                                if($listC->status === 1) { ?>
                                                    <a class="btn btn-dark not_complete_task" data-id="{{ $listC->id }}" href="javascript:void(0)">Incomplete</a>
                                                <?php } else { ?>
                                                    <a class="btn btn-success complete_task" data-id="{{ $listC->id }}" href="javascript:void(0)">Complete</a>
                                                <?php } ?>
                                            </td>
											<td>{{ $listC->assigned_user->first_name ?? ''}}  {{$listC->assigned_user->last_name ?? ''}}</td>
											<td>{{ $full_nameC??'N/P' }}</td>
                                            <td><a href="{{URL::to('/clients/detail/'.base64_encode(convert_uuencode(@$listC->client_id)))}}" target="_blank" >{{ $listC->noteClient->client_id ?? 'N/P' }}</a></td>
											<td>{{ date('d/m/Y',strtotime($listC->action_assign_date)) ?? 'N/P'}} </td>
                                            <td>{{ $listC->task_group??'N/P' }}</td>
                                            <td>{{ $listC->description??'N/P' }}</td>

                                            <!--<td>$list->title??'N --}}/P' --}}</td>
											<td>{{-- $list->noteClient->service??'N/P' --}}</td>-->

											<!--@if($listC->noteClient->status === 0)
											<td><span title="draft" class="badge bg-warning">Pending</span></td>
											@elseif($listC->noteClient->status === 1)
											<td><span title="draft" class="badge bg-success">Approved</span></td>
											@elseif($listC->noteClient->status === 'Unassigned')
											<td><span title="draft" class="badge bg-warning">Unassigned</span></td>
											@elseif($listC->noteClient->status === 'Assigned')
											<td><span title="draft" class="badge bg-info">Assigned</span></td>
											@elseif($listC->noteClient->status === 'In-Progress')

											<td><span title="draft" class="badge bg-primary">In-Progress</span></td>
											@elseif($listC->noteClient->status === 'Closed')
											<td><span title="draft" class="badge bg-success">Closed</span></td>
											@else
											<td><span title="draft" class="badge bg-warning">Pending</span></td>
											@endif-->


											<td>
												@if($listC->noteClient)
												<form action="{{ route('assignee.destroy_to_me',$listC->id) }}" method="POST">

													{{-- <a class="btn btn-info" href="{{ route('assignees.show',$listC->id) }}">Show</a> --}}

													{{--<a class="btn btn-primary" href="{{ url('/clients/edit/'.base64_encode(convert_uuencode(@$listC->client_id)).'') }}">Edit</a>--}}

                                                    @csrf
													@method('DELETE')

													<button type="submit" class="btn btn-danger" data-crm-confirm='Are you sure want to delete?'">Delete</button>
													<button type="button" class="btn btn-primary btn-block" data-bs-container="body" data-role="popover" data-bs-placement="bottom" data-html="true" data-content="<div id=&quot;popover-content&quot;>
														<h4 class=&quot;text-center&quot;>Re-Assign Staff</h4>
														<div class=&quot;clearfix&quot;></div>
													<div class=&quot;box-header with-border&quot;>
														<div class=&quot;form-group row&quot; style=&quot;margin-bottom:12px&quot; >
															<label for=&quot;inputSub3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>Select Assignee</label>
															<div class=&quot;col-sm-9&quot;>
																<select class=&quot;assignee-tomselect tomselect form-control selec_reg&quot; id=&quot;rem_cat&quot; name=&quot;rem_cat&quot; onchange=&quot;&quot;>
																	<option value=&quot;&quot; >Select</option>
																	@foreach(\App\Models\Staff::where('status',1)->orderby('first_name','ASC')->get() as $admin)
																	<?php
																	$branchname = \App\Models\Branch::where('id',$admin->office_id)->first();
																	?>
																	<option value=&quot;<?php echo $admin->id; ?>&quot;><?php echo $admin->first_name.' '.$admin->last_name.' ('.@$branchname->office_name.')'; ?></option>
																	@endforeach
																</select>
															</div>
															<div class=&quot;clearfix&quot;></div>
														</div>
													</div><div id=&quot;popover-content&quot;>
													<div class=&quot;box-header with-border&quot;>
														<div class=&quot;form-group row&quot; style=&quot;margin-bottom:12px&quot; >
															<label for=&quot;inputEmail3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>Note</label>
															<div class=&quot;col-sm-9&quot;>
																<textarea id=&quot;assignnote&quot; class=&quot;form-control tinymce-simple f13&quot; placeholder=&quot;Enter an note....&quot; type=&quot;text&quot;></textarea>
															</div>
															<div class=&quot;clearfix&quot;></div>
														</div>
													</div>
													<div class=&quot;box-header with-border&quot;>
														<div class=&quot;form-group row&quot; style=&quot;margin-bottom:12px&quot; >
															<label for=&quot;inputEmail3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>DateTime</label>
															<div class=&quot;col-sm-9&quot;>
																<input type="text" class="form-control f13 flatpickr-date" placeholder="yyyy-mm-dd" id="popoverdatetime" value="<?php echo date('Y-m-d');?>" name="popoverdate" autocomplete="off">
															</div>
															<div class=&quot;clearfix&quot;></div>
														</div>
													</div>

                                                    <div class=&quot;form-group row&quot; style=&quot;margin-bottom:12px&quot; >
                                                        <label for=&quot;inputSub3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>Group</label>
                                                        <div class=&quot;col-sm-9&quot;>
                                                            <select class=&quot;assignee-tomselect tomselect form-control selec_reg&quot; id=&quot;task_group&quot; name=&quot;task_group&quot;>
                                                                <option value=&quot;&quot;>Select</option>
                                                                <option value=&quot;Call&quot;>Call</option>
                                                                <option value=&quot;Checklist&quot;>Checklist</option>
                                                                <option value=&quot;Review&quot;>Review</option>
                                                                <option value=&quot;Query&quot;>Query</option>
                                                                <option value=&quot;Urgent&quot;>Urgent</option>
                                                            </select>
                                                        </div>
                                                        <div class=&quot;clearfix&quot;></div>
                                                    </div>

													<input id=&quot;assign_client_id&quot;  type=&quot;hidden&quot; value=&quot;{{base64_encode(convert_uuencode(@$list->client_id))}}&quot;>
													<div class=&quot;box-footer&quot; style=&quot;padding:10px 0&quot;>
													<div class=&quot;row&quot;>
														<input type=&quot;hidden&quot; value=&quot;&quot; id=&quot;popoverrealdate&quot; name=&quot;popoverrealdate&quot; />
													</div>
													<div class=&quot;row text-center&quot;>
														<div class=&quot;col-md-12 text-center&quot;>
														<button  class=&quot;btn btn-info&quot; id=&quot;assignUser&quot;>Assign Staff</button>
														</div>
													</div>
											</div>" data-original-title="" title="" style="width: 82px;display: inline;">Reassign</button>
									{{-- <a class="btn btn-primary openassigneview" id="{{$list->id}}" href="#">Reassign</a> --}}
												</form>
												@endif
											</td>


										</tr>
										@endforeach
									</table>
										{{-- {!! $assignees->appends(\Request::except('page'))->render() !!} --}}
   										 {!! $assignees_completed->appends($_GET)->links() !!}
								</div>
								<div class="card-footer">

								</div>
							</div>



						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</div>
<!-- Assign Modal (legacy appointment detail — removed) -->
@endsection
@section('scripts')

@push('scripts')
	@vite(['resources/js/pages/admin/popover-entry.js'])
@endpush

<script>
	jQuery(document).ready(function($){
     $(document).delegate('.openassignee', 'click', function(){
        $('.assignee').show();
    });
	$(document).delegate('.closeassignee', 'click', function(){
        $('.assignee').hide();
    });

    //Function is used for not complete the task
	$(document).delegate('.not_complete_task', 'click', function(){
		var row_id = $(this).attr('data-id');
        if(row_id !=""){
            $.ajax({
				type:'post',
                url:"{{URL::to('/')}}/action/task-incomplete",
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {id:row_id },
                success: function(response){
                    // Handle string (legacy) or object (application/json) without breaking reload
                    var obj = (typeof response === 'string') ? $.parseJSON(response) : response;
                    location.reload();
                }
			});
        }
	});


    //Function is used for complete the task
	$(document).delegate('.complete_task', 'click', function(){
		var row_id = $(this).attr('data-id');
        if(row_id !=""){ //&& confirm('Are you sure want to complete the task?')
            $.ajax({
				type:'post',
                url:"{{URL::to('/')}}/action/task-complete",
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {id:row_id },
                success: function(response){
                    // Handle string (legacy) or object (application/json) without breaking reload
                    var obj = (typeof response === 'string') ? $.parseJSON(response) : response;
                    location.reload();
                }
			});
        }
	});


    $(document).delegate('#assignUser','click', function(){
		$(".popuploader").show();
		var flag = true;
		var error ="";
		$(".custom-error").remove();
		// if($('#lead_id').val() == ''){
		// 	$('.popuploader').hide();
		// 	error="Lead field is required.";
		// 	$('#lead_id').after("<span class='custom-error' role='alert'>"+error+"</span>");
		// 	flag = false;
		// }
		if(typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal('#rem_cat') === '' : $('#rem_cat').val() == ''){
			$('.popuploader').hide();
			error="Assignee field is required.";
			$('#rem_cat').after("<span class='custom-error' role='alert'>"+error+"</span>");
			flag = false;
		}
		if($('#assignnote').val() == ''){
			$('.popuploader').hide();
			error="Note field is required.";
			$('#assignnote').after("<span class='custom-error' role='alert'>"+error+"</span>");
			flag = false;
		}
        if(typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal('#task_group') === '' : $('#task_group').val() == ''){
			$('.popuploader').hide();
			error="Group field is required.";
			$('#task_group').after("<span class='custom-error' role='alert'>"+error+"</span>");
			flag = false;
		}
		if(flag){
			$.ajax({
				type:'post',
					url:"{{URL::to('/')}}/clients/action/store",
					headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},

					data: {note_type:'action',description:$('#assignnote').val(),client_id:$('#assign_client_id').val(),followup_datetime:$('#popoverdatetime').val(),assignee_name:(typeof actionPopoverAssigneeLabel === 'function' ? actionPopoverAssigneeLabel('#rem_cat') : $('#rem_cat :selected').text()),rem_cat:(typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal('#rem_cat') : $('#rem_cat option:selected').val()),task_group:(typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal('#task_group') : $('#task_group option:selected').val())},
					success: function(response){
						console.log(response);
						$('.popuploader').hide();
						var obj = $.parseJSON(response);
						if(obj.success){
							$("[data-role=popover]").each(function(){
								// Bootstrap 5: plain hide (no BS3 inState API)
								try {
									if (window.bootstrap && window.bootstrap.Popover) {
										var inst = window.bootstrap.Popover.getInstance(this);
										if (inst) { inst.hide(); return; }
									}
								} catch (e) {}
								try { $(this).popover('hide'); } catch (e2) {}
							});
							location.reload();
							getallactivities();
							getallnotes();

						}else{
							showToast(obj.message, 'error');
							location.reload();

						}
					}
			});
		}else{
			$("#loader").hide();
		}
	});
});
</script>

@push('tinymce-scripts')
@include('partials.tinymce')
@endpush

@endsection
