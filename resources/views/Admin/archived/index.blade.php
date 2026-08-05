@extends('layouts.admin')
@section('title', 'Clients')

@section('content')
<style>
.filter_panel {
	background: #f7f7f7;
	margin-bottom: 10px;
	border: 1px solid #eee;
	display: none;
}
.card .card-body .filter_panel {
	padding: 20px;
}
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
							<h4>All Clients</h4>
							<div class="card-header-action">
								<div class="drop_table_data" style="display: inline-block;margin-right: 10px;">
									<button type="button" class="btn btn-primary dropdown-toggle">@icon('columns')</button>
									<div class="dropdown_list">
										<label class="dropdown-option all"><input type="checkbox" value="all" checked /> Display All</label>
										<label class="dropdown-option"><input type="checkbox" value="3" checked /> Assignee</label>
										<label class="dropdown-option"><input type="checkbox" value="4" checked /> Archived By</label>
										<label class="dropdown-option"><input type="checkbox" value="5" checked /> Archived On</label>
									</div>
								</div>
								<a href="javascript:;" class="btn btn-theme btn-theme-sm filter_btn">@icon('filter') Filter</a>
							</div>
						</div>
						<div class="card-body">
							<div class="filter_panel">
								<h4>Search & Filter Archived Records</h4>
								<form action="{{URL::to('/archived')}}" method="get">
									<div class="row">
										<div class="col-md-3">
											<div class="form-group">
												<label for="client_id" class="col-form-label">Client ID</label>
												{!! Form::text('client_id', Request::get('client_id'), array('class' => 'form-control', 'autocomplete'=>'off','placeholder'=>'Client ID', 'id' => 'client_id' ))  !!}
											</div>
										</div>
										<div class="col-md-3">
											<div class="form-group">
												<label for="name" class="col-form-label">Name</label>
												{!! Form::text('name', Request::get('name'), array('class' => 'form-control', 'autocomplete'=>'off','placeholder'=>'Name', 'id' => 'name' ))  !!}
											</div>
										</div>
										<div class="col-md-3">
											<div class="form-group">
												<label for="email" class="col-form-label">Email</label>
												{!! Form::text('email', Request::get('email'), array('class' => 'form-control', 'autocomplete'=>'off','placeholder'=>'Email', 'id' => 'email' ))  !!}
											</div>
										</div>
										<div class="col-md-3">
											<div class="form-group">
												<label for="phone" class="col-form-label">Phone</label>
												{!! Form::text('phone', Request::get('phone'), array('class' => 'form-control', 'autocomplete'=>'off','placeholder'=>'Phone', 'id' => 'phone' ))  !!}
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-3">
											<div class="form-group">
												<label for="type" class="col-form-label">Type</label>
												<select class="form-control" name="type" id="type">
													<option value="">All</option>
													<option value="client" {{ Request::get('type') === 'client' ? 'selected' : '' }}>Client</option>
													<option value="lead" {{ Request::get('type') === 'lead' ? 'selected' : '' }}>Lead</option>
												</select>
											</div>
										</div>
										<div class="col-md-3">
											<div class="form-group">
												<label for="archived_from" class="col-form-label">Archived From</label>
												{!! Form::date('archived_from', Request::get('archived_from'), array('class' => 'form-control', 'id' => 'archived_from' ))  !!}
											</div>
										</div>
										<div class="col-md-3">
											<div class="form-group">
												<label for="archived_to" class="col-form-label">Archived To</label>
												{!! Form::date('archived_to', Request::get('archived_to'), array('class' => 'form-control', 'id' => 'archived_to' ))  !!}
											</div>
										</div>
										<div class="col-md-3">
											<div class="form-group">
												<label for="archived_by" class="col-form-label">Archived By</label>
												<select class="form-control" name="archived_by" id="archived_by">
													<option value="">All</option>
													@foreach($archivedByUsers as $user)
														<option value="{{$user->id}}" {{Request::get('archived_by') == $user->id ? 'selected' : ''}}>{{$user->first_name}} {{$user->last_name}}</option>
													@endforeach
												</select>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-3">
											<div class="form-group">
												<label for="assignee" class="col-form-label">Assignee</label>
												<select class="form-control" name="assignee" id="assignee">
													<option value="">All</option>
													@foreach($assignees as $assignee)
														<option value="{{$assignee->id}}" {{Request::get('assignee') == $assignee->id ? 'selected' : ''}}>{{$assignee->first_name}} {{$assignee->last_name}}</option>
													@endforeach
												</select>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12 text-center">
											{!! Form::submit('Search', ['class'=>'btn btn-primary btn-theme-lg' ])  !!}
											<a class="btn btn-info" href="{{URL::to('/archived')}}">Reset</a>
										</div>
									</div>
								</form>
							</div>
							<ul class="nav nav-pills" id="client_tabs" role="tablist">
								
								<li class="nav-item">
									<a class="nav-link " id="clients-tab"  href="{{URL::to('/clients')}}" >Clients</a>
								</li>
								<li class="nav-item ">
									<a class="nav-link active" id="archived-tab"  href="{{URL::to('/archived')}}" >Archived</a>
								</li>
							</ul> 
							<div class="tab-content" id="clientContent">
								<div class="tab-pane fade show active" id="archived" role="tabpanel" aria-labelledby="archived-tab">
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
													<th>Assignee</th>
													<th>Archived By</th>
													<th>Archived On</th>
													<th></th>
												</tr> 
											</thead>
											
											<tbody class="tdata">
												@if(@$totalData !== 0)
												@foreach (@$lists as $list)	
												<tr id="id_{{$list->id}}">
													<td style="white-space: initial;" class="text-center">
														<div class="custom-checkbox custom-control">
															<input type="checkbox" data-checkboxes="mygroup" class="custom-control-input" id="checkbox-1">
															<label for="checkbox-1" class="custom-control-label">&nbsp;</label>
														</div>
													</td>
													<?php
													$archivedEncodedId = base64_encode(convert_uuencode($list->id));
													$isLeadType = strtolower((string) ($list->type ?? '')) === 'lead';
													$detailUrl = $isLeadType
														? URL::to('/leads/detail/' . $archivedEncodedId)
														: URL::to('/clients/detail/' . $archivedEncodedId);
													$displayName = trim(
														(@$list->first_name == "" ? config('constants.empty') : str_limit(@$list->first_name, '50', '...'))
														. ' '
														. (@$list->last_name == "" ? config('constants.empty') : str_limit(@$list->last_name, '50', '...'))
													);
													$assignee = \App\Support\StaffAssigneeResolver::firstStaffFromAssigneeValue(@$list->assignee);
													$archivedBy = null;
													if(!empty(@$list->archived_by) && @$list->archived_by !== '') {
														$archivedBy = \App\Models\Staff::find(@$list->archived_by);
													}
													
													// Check if archived for 6+ months (allow permanent deletion)
													$canDelete = false;
													if($list->archived_on) {
														$archivedDate = \Carbon\Carbon::parse($list->archived_on);
														$sixMonthsAgo = \Carbon\Carbon::now()->subMonths(6);
														$canDelete = $archivedDate->lte($sixMonthsAgo);
													}
													?>
													<td style="white-space: initial;">
														<a href="{{ $detailUrl }}">{{ $displayName }}</a>
														@if(!empty($list->type))
															<span class="badge btn-warning">{{ $list->type }}</span>
														@endif
													</td>
													<td style="white-space: initial;">{{ $assignee ? (@$assignee->first_name == "" ? config('constants.empty') : str_limit(@$assignee->first_name, '50', '...')) : '-' }}</td>
													<td style="white-space: initial;">{{ $archivedBy ? (str_limit(trim(($archivedBy->first_name ?? '') . ' ' . ($archivedBy->last_name ?? '')), 50, '...') ?: '-') : '-' }}</td>
													<td style="white-space: initial;">{{ $list->archived_on ? date('d/m/Y', strtotime($list->archived_on)) : '-' }}</td>
													<td style="white-space: initial;">
														<div class="dropdown d-inline">
															<button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
															<div class="dropdown-menu">
																<a class="dropdown-item has-icon" href="javascript:;" onclick="movetoclientAction({{$list->id}}, 'admins','is_archived')">@icon('undo') {{ $isLeadType ? 'Restore to leads' : 'Move to clients' }}</a>
																
																@if($canDelete)
																	<a class="dropdown-item has-icon text-danger" href="javascript:;" onclick="permanentDeleteAction({{$list->id}}, 'admins')">@icon('trash') Permanently Delete</a>
																@endif
															</div>
														</div>								  
													</td>
												</tr>											
												@endforeach	
											</tbody>
											@else		
											<tbody>
												<tr>
													<td style="text-align:center;" colspan="6">
														No Record found
													</td>
												</tr>
											</tbody>
											@endif
											@if(@$totalData > 0)
											<tfoot>
												<tr>
													<td colspan="6" style="text-align:center; padding: 10px;">
														<strong>Total: {{$totalData}} archived record(s)</strong>
													</td>
												</tr>
											</tfoot>
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

<script>
$(document).ready(function() {
	// Toggle filter panel
	$('.filter_btn').on('click', function(){
		$('.filter_panel').slideToggle();
	});
});
</script>

@endsection