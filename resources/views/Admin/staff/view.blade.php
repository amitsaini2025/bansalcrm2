@extends('layouts.adminconsole')
@section('title', 'Staff')

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
							<h4>Staff</h4>
							<div class="card-header-action">
								<a href="{{route('staff.active')}}" class="btn btn-primary">Staff</a>
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
							<span class="author-avtar" style="background: rgb(68, 182, 174);"><b>{{substr($fetchedData->first_name, 0, 1)}}</b></span>
								<div class="clearfix"></div>
								<div class="author-box-name">
									<a href="#">{{$fetchedData->first_name}}</a>
								</div>
								<div class="author-mail_sms"> 
									<a href="{{ route('staff.edit', ['id' => base64_encode(convert_uuencode($fetchedData->id))], false) }}" title="Edit" aria-label="Edit staff">@icon('edit')</a>
								</div>
							</div>
						</div>
					</div>
					<div class="card">
						<div class="card-header">
							<h4>Personal Information</h4>
						</div>
						<div class="card-body">
							<p class="clearfix"> 
								<span class="float-start">Offices:</span>
								<span class="float-end text-muted"></span>
							<?php
							$branches = \App\Models\Branch::all();
							?>
								@foreach($branches as $branch)
								{{$branch->office_name}} @if($branch->id == $fetchedData->office_id)(Primary) @endif<br>
								@endforeach
							</p>
							<p class="clearfix"> 
								<span class="float-start">Email:</span>
								<span class="float-end text-muted">{{$fetchedData->email}}</span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Phone:</span>
								<span class="float-end text-muted">{{$fetchedData->phone}}</span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Staff Role:</span>
								<span class="float-end text-muted">{{$fetchedData->usertype->name}}</span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Position:</span>
								<span class="float-end text-muted">{{$fetchedData->position}}</span>
							</p>
							<p class="clearfix">
								<span class="float-start">CRM access:</span>
								<span class="float-end text-muted text-end" style="max-width: 65%;">
									{{ !empty($fetchedData->quick_access_enabled) ? config('crm_access.quick_grant_minutes', 15).'-minute cross-access: Yes' : 'Short cross-access: No' }}<br>
									{{ !empty($fetchedData->crm_full_access) ? 'Full access (all records): Yes' : 'Full access: No' }}<br>
									{{ !empty($fetchedData->crm_access_approver) ? 'Supervisor approvals: Yes' : 'Supervisor approvals: No' }}
								</span>
							</p>
							
							<p class="clearfix">
								<span class="float-start">Department:</span>
								<?php
								if($fetchedData->team != ""){
								    $teamData = \App\Models\Team::select('name')->where('id', '=', $fetchedData->team)->first(); //dd($teamData);
								    $teamname = $teamData->name;
								} else {
								    $teamname = "";
								} ?>
								<span class="float-end text-muted">{{$teamname}}</span>
							</p>
							
							
							<p class="clearfix">
								<span class="float-start">Permission:</span>
								<span class="float-end text-muted">

                                    <?php
                                    if( isset($fetchedData->permission) && $fetchedData->permission !="")
                                    {
                                        if( strpos($fetchedData->permission,",") ){
                                            $permission_arr =  explode(",",$fetchedData->permission);
                                        } else {
                                            $permission_arr = array($fetchedData->permission);
                                        } ?>

                                            <br><b>Notes</b>  &nbsp;&nbsp;&nbsp;&nbsp;
                                            <input value="1" <?php if ( in_array(1, $permission_arr) ) echo "checked='checked'"; ?> type="checkbox" name="permission[]" class="show_dashboard_per">&nbsp; View &nbsp;
                                            <input value="2" <?php if ( in_array(2, $permission_arr) ) echo "checked='checked'"; ?> type="checkbox" name="permission[]" class="show_dashboard_per">&nbsp; Add/Edit &nbsp;
                                            <input value="3" <?php if ( in_array(3, $permission_arr) ) echo "checked='checked'"; ?> type="checkbox" name="permission[]" class="show_dashboard_per">&nbsp; Delete &nbsp;

                                            <br><b>Documents</b>
                                            <input value="4" <?php if ( in_array(4, $permission_arr) ) echo "checked='checked'"; ?> type="checkbox" name="permission[]" class="show_dashboard_per">&nbsp; View &nbsp;
                                            <input value="5" <?php if ( in_array(5, $permission_arr) ) echo "checked='checked'"; ?> type="checkbox" name="permission[]" class="show_dashboard_per">&nbsp; Add/Edit &nbsp;
                                            <input value="6" <?php if ( in_array(6, $permission_arr) ) echo "checked='checked'"; ?> type="checkbox" name="permission[]" class="show_dashboard_per">&nbsp; Delete &nbsp;
                                        <?php
                                    }
                                    else
                                    {
                                    ?>
                                        <br><b>Notes</b>  &nbsp;&nbsp;&nbsp;&nbsp;
                                        <input value="1" type="checkbox" name="permission[]" class="show_dashboard_per">&nbsp; View &nbsp;
                                        <input value="2" type="checkbox" name="permission[]" class="show_dashboard_per">&nbsp; Add/Edit &nbsp;
                                        <input value="3" type="checkbox" name="permission[]" class="show_dashboard_per">&nbsp; Delete &nbsp;

                                        <br><b>Documents</b>
                                        <input value="4" type="checkbox" name="permission[]" class="show_dashboard_per">&nbsp; View &nbsp;
                                        <input value="5" type="checkbox" name="permission[]" class="show_dashboard_per">&nbsp; Add/Edit &nbsp;
                                        <input value="6" type="checkbox" name="permission[]" class="show_dashboard_per">&nbsp; Delete &nbsp;
                                    <?php
                                    }?>

                                </span>
							</p>
							 
						</div>
					</div>
				</div>
				<div class="col-9 col-md-9 col-lg-9">
					<div class="card">
						<div class="card-body">
							<ul class="nav nav-pills" id="client_tabs" role="tablist">
								<li class="nav-item">
									<a class="nav-link <?php if(isset($_GET['tab']) && $_GET['tab'] == 'progress'){}else{ echo 'active'; } ?>" data-bs-toggle="tab" id="clients-tab" href="#clients" role="tab" aria-controls="clients" aria-selected="false">Clients</a>
								</li>
								<li class="nav-item">
									<a class="nav-link" data-bs-toggle="tab" id="date-tab" href="#date" role="tab" aria-controls="date" aria-selected="false">Date & Time</a>
								</li>
								<li class="nav-item">
									<a class="nav-link <?php if(isset($_GET['tab']) && $_GET['tab'] == 'progress'){ echo 'active'; } ?>" data-bs-toggle="tab" id="progress-tab" href="#progress" role="tab" aria-controls="progress" aria-selected="false">Progress Report</a>
								</li>
							</ul> 
							<div class="tab-content" id="clientContent" style="padding-top:15px;">
								<div class="tab-pane fade <?php if(isset($_GET['tab']) && $_GET['tab'] == 'progress'){}else{ echo 'show active'; } ?>" id="clients" role="tabpanel" aria-labelledby="clients-tab">
																		
									<div class="table-responsive"> 
										<table class="table text_wrap table-2">
											<thead>
												<tr>
													<th>Name</th>
													<th>DOB</th>
													<th>Assignee</th>
													<th>Assignee Office</th>
													<th>Added On</th>
													<th>Client Status</th>
													
												</tr> 
											</thead>
											<tbody class="applicationtdata">
											<?php
											foreach(\App\Models\Admin::with('office')->where('user_id',$fetchedData->id)->get() as $alist){
												$creator = \App\Models\Staff::with('office')->find($alist->user_id) ?? \App\Models\Admin::with('office')->find($alist->user_id);
												$admin = $creator;
												?>
												<tr id="id_{{$alist->id}}">
													<td><a class="" data-id="{{$alist->id}}" href="{{URL::to('/clients/detail')}}/{{base64_encode(convert_uuencode(@$alist->id))}}" style="display:block;">{{$alist->first_name}} {{$alist->last_name}}</a> {{$alist->email}}</td> 
													<td>{{$alist->dob}}</td>
													<td>
													{{ $admin ? $admin->first_name.' '.$admin->last_name : 'N/A' }}
													</td> 
													<td>{{ $admin && $admin->office ? $admin->office->office_name : '' }}</td>
													
													<td>{{date('Y-m-d', strtotime($alist->created_at))}}</td> 
													<td>
													Client
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
								<div class="tab-pane fade" id="date" role="tabpanel" aria-labelledby="date-tab">
									<div class="card-header-action" style="padding-bottom:15px;">
										<?php
												$list = DateTimeZone::listIdentifiers();
												
												?>
												<form name="usertimezone" action="{{ route('staff.savezone') }}" method="POST" enctype="multipart/form-data">
											@csrf
											<input type="hidden" name="user_id" value="{{$fetchedData->id}}">
											<div class="row">
												<div class="col-12 col-md-6 col-lg-6">
													<div class="form-group">
														<select class="form-control tomselect staff-timezone-select" name="timezone">
															<option value="">Please select Timezone</option>
															@foreach($list as $l)
															<option @if($l == $fetchedData->time_zone) selected @endif value="{{$l}}">{{$l}}</option>
															@endforeach
														</select>
													</div>
												</div>
											</div>
										<div class="form-group">
											<button onclick="customValidate('usertimezone')" type="button" class="btn btn-primary">Send & Apply</button>
										</div>
										</form>
									</div>
									<div class="list_data"> 
										
									</div>
									
								</div>
								<div class="tab-pane fade <?php if(isset($_GET['tab']) && $_GET['tab'] == 'progress'){ echo 'show active'; } ?>" id="progress" role="tabpanel" aria-labelledby="progress-tab">
								    
									<div class="card-header-action" style="padding-bottom:15px;">
									     <div class="row">
									         <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-xs-12 mb-4">
                                    				<div class="card dash_card">
                                    					<div class="card-statistic-4">
                                    						<div class="align-items-center justify-content-between">
                                    							<div class="row ">
                                    								<div class="col-lg-12 col-md-12">
                                    									<div class="card-content">
                                    							<?php	$countclients = \App\Models\Admin::where('assignee', $fetchedData->id)->count(); ?>
                                    										<h5 class="font-14">Total Clients</h5>
                                    										<h2 class="mb-3 font-18">{{$countclients}}</h2>
                                    										
                                    									</div>
                                    								</div>
                                    							</div>
                                    						</div>
                                    					</div>
                                    				</div>
                                    			</div>
                                    			<div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-xs-12 mb-4">
                                    				<div class="card dash_card">
                                    					<div class="card-statistic-4">
                                    						<div class="align-items-center justify-content-between">
                                    							<div class="row ">
                                    								<div class="col-lg-12 col-md-12">
                                    									<div class="card-content">
                                    									<?php
                                    									$countleads = \App\Models\Admin::where('type','lead')->where('assignee', $fetchedData->id)->count();
                                    									?>
                                    										<h5 class="font-14">Total Leads</h5>
                                    										<h2 class="mb-3 font-18">{{$countleads}}</h2>
                                    										
                                    									</div>
                                    								</div>
                                    							</div>
                                    						</div>
                                    					</div>
                                    				</div>
                                    			</div>
                                    			</div>
                                    			
                            <div class="row">
                                    			<div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-xs-12 mb-4">
                                    				<div class="card dash_card">
                                    					<div class="card-statistic-4">
                                    						<div class="align-items-center justify-content-between">
                                    							<div class="row ">
                                    								<div class="col-lg-12 col-md-12">
                                    									<div class="card-content">
                                    									<?php
                                    									$countconver = \App\Models\Admin::where('type','lead')->where('assignee', $fetchedData->id)->where('converted', 1)->count();
                                    									?>
                                    										<h5 class="font-14">Total Converted</h5>
                                    										<h2 class="mb-3 font-18">{{$countconver}}</h2>
                                    										
                                    									</div>
                                    								</div>
                                    							</div>
                                    						</div>
                                    					</div>
                                    				</div>
                                    			</div>
                                    	<?php
                                    									$todaycountconver = \App\Models\Admin::where('type','lead')->where('assignee', $fetchedData->id)->where('converted', 1)->whereDate('converted_date', date('Y-m-d'))->count();
                                    									?>
                                    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-6 col-xs-12 mb-4">
                                    				<div class="card dash_card">
                                    					<div class="card-statistic-4">
                                    						<div class="align-items-center justify-content-between">
                                    							<div class="row ">
                                    								<div class="col-lg-12 col-md-12">
                                    									<div class="card-content">
                                    								
                                    										<h5 class="font-14">Today Converted</h5>
                                    										<h2 class="mb-3 font-18">{{$todaycountconver}}</h2>
                                    											
                                    										
                                    									</div>
                                    								</div>
                                    							</div>
                                    						</div>
                                    					</div>
                                    				</div>
                                    			</div>
                                    			</div>
                                    			
                                    			
									</div>
									<div class="list_data"> 
										   <div class="row">
										       <div class="col-lg-6 col-md-6 col-sm-6 col_inline_flex">
				<div class="card card_column">
					<div class="card-header">
						<h4>Progress of Leads</h4>
						 
					</div>
					<div class="card-body">
						<canvas id="application_workflow"></canvas>
					</div>
                </div>
			</div> 
										       
										       
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

<!-- Appliation Modal -->
<div class="modal fade office_select custom_modal" tabindex="-1" role="dialog" aria-labelledby="applicationModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content"> 
			<div class="modal-header">
				<h5 class="modal-title" id="appliationModalLabel">Select Office & Transfer Responsibilities</h5>
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
								<label for="primary_office">New Primary Office <span class="span_req">*</span></label>
								<select data-valid="required" class="form-control primary_office tomselect" id="primary_office" name="primary_office">
									<option value="">Search & Select new Primary Office</option>
									@foreach(\App\Models\Branch::all() as $wlist)
										<option value="{{$wlist->id}}">{{$wlist->office_name}}</option>
									@endforeach
								</select>
								<span class="custom-error workflow_error" role="alert">
									<strong></strong>
								</span> 
								<span class="span_note">You need to transfer your responsibilities to a new staff member in order to proceed further. The responsibilities are mentioned below.</span>
							</div>
						</div>
					</div>
					<div class="responsibilty_list">
						<h6>Responsibilities Include:</h6>
						<div class="row">
							<div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-xs-12">
								<div class="card">
									<div class="card-statistic-4">
										<div class="align-items-center justify-content-between">
											<div class="row">
												<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pe-0 pt-3">
													<div class="card-content">
													  <h2 class="mb-3 font-18">2</h2>
													  <p class="mb-0">Task</p>
													</div>
												</div>
												<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 ps-0">
													<div class="respons_icon">
														@icon('suitcase')
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-xs-12">
								<div class="card">
									<div class="card-statistic-4">
										<div class="align-items-center justify-content-between">
											<div class="row">
												<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pe-0 pt-3">
													<div class="card-content">
													  <h2 class="mb-3 font-18">7</h2>
													  <p class="mb-0">Contact</p>
													</div>
												</div>
												<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 ps-0">
													<div class="respons_icon">
														@icon('user')
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-xs-12">
								<div class="card">
									<div class="card-statistic-4">
										<div class="align-items-center justify-content-between">
											<div class="row">
												<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 pe-0 pt-3">
													<div class="card-content">
													  <h2 class="mb-3 font-18">19</h2>
													  <p class="mb-0">Applications</p>
													</div>
												</div>
												<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 ps-0">
													<div class="respons_icon">
														@icon('file-contract')
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="primary_office">Select a new assignee <span class="span_req">*</span></label>
								<select data-valid="required" class="form-control primary_office tomselect" id="primary_office" name="primary_office">
									<option value="">Select a new assignee</option>
									@foreach(\App\Models\Branch::all() as $wlist)
										<option value="{{$wlist->id}}">{{$wlist->name}}</option>
									@endforeach
								</select>
								<span class="custom-error workflow_error" role="alert">
									<strong></strong>
								</span> 
								<span class="span_note">The new assignee will be notified via email.Previous assignee will be added as following:</span>
								<ul>
									<li>Follower in Tasks & Contacts</li>
									<li>Multiple Assignee in Applications</li>
								</ul>
							</div>
						</div>
					</div>
					<div class="row">
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
<?php
$leadsconverted = \App\Models\Admin::where('type','lead')->where('assignee', $fetchedData->id)->where('converted', 1)->count();
$totalleads = \App\Models\Admin::where('type','lead')->where('assignee', $fetchedData->id)->count();
$leadsprogress = \App\Models\Admin::where('type','lead')->where('assignee', $fetchedData->id)->where('converted', 0)->count();

$data = array($totalleads,$leadsconverted, $leadsprogress); 
?>
<script>
var data = {{json_encode($data)}};
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
					if(delhref == 'deletedocs'){
						$('.documnetlist #id_'+notid).remove();
					}
					if(delhref == 'deletefee'){
						$.ajax({
						url: site_url+'/get-all-fees',
						type:'GET',
						data:{clientid:'{{$fetchedData->id}}'},
						success: function(responses){
							
							$('.feeslist').html(responses);
						}
					});
					}else{
						getallnotes();
					}
					
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
	$(document).delegate('input[name=document_upload]', 'click', function() {
    $(this).attr("value", "");
})  
	$(document).delegate('input[name=document_upload]', 'change', function() {
		$('.popuploader').show();	
var formData = new FormData($('#upload_form')[0]);		
		$.ajax({
			url: site_url+'/upload-document',
			type:'POST',
			datatype:'json',
			 data: formData,
			contentType: false,
			processData: false,
			
			success: function(responses){
					$('.popuploader').hide();
var ress = JSON.parse(responses);
if(ress.status){
	$('.custom-error-msg').html('<span class="alert alert-success">'+ress.message+'</span>');
	$('.documnetlist').html(ress.data);
	$('.griddata').html(ress.griddata);
}else{
$('.custom-error-msg').html('<span class="alert alert-danger">'+ress.message+'</span>');	
}	
				
			}
		});
	});
	
	$(document).on('click', '.documnetlist .renamedoc', function () {
			var parent = $(this).closest('.drow').find('.doc-row');

			parent.data('current-html', parent.html());
			var opentime = parent.data('name');

			parent.empty().append(
				$('<input style="display: inline-block;width: auto;" class="form-control opentime" type="text">').prop('value', opentime),
				
				$('<button class="btn btn-primary btn-sm mb-1">@icon('check')</button>'),
				$('<button class="btn btn-danger btn-sm mb-1">@icon('trash-alt', 'regular')</button>')
			);

			return false;
	
	});
	
	$(document).on('click', '.documnetlist .drow .btn-danger', function () {
			var parent = $(this).closest('.drow').find('.doc-row');
			var hourid = parent.data('id');
			if (hourid) {
				parent.html(parent.data('current-html'));
			} else {
				parent.remove();
				
			}
		});
		
	$(document).delegate('.documnetlist .drow .btn-primary', 'click', function () {
		
			var parent = $(this).closest('.drow').find('.doc-row');
			parent.find('.opentime').removeClass('is-invalid');
			parent.find('.invalid-feedback').remove();

			var opentime = parent.find('.opentime').val();


			if (!opentime) {
				parent.find('.opentime').addClass('is-invalid').css({ 'background-image': 'none', 'padding-right': '0.75em' });
				parent.append($("<div class='invalid-feedback'>This field is required</div>"));
				return false;
			}
			
			$.ajax({
			   type: "POST",
			   data: {"_token": $('meta[name="csrf-token"]').attr('content'),"filename": opentime, "id": parent.data('id')},
			   url: '{{URL::to('/renamedoc')}}',
			   success: function(result){
				   var obj = JSON.parse(result);
				 if (obj.status) {
						parent.empty()
							.data('id', obj.Id)
							.data('name', opentime)
							.append(
								$('<span>').html(crmIcon('file-image') + ' ' + obj.filename + '.' + obj.filetype)
							);
							$('#grid_'+obj.Id).html(obj.filename+'.'+obj.filetype);
					} else {
						parent.find('.opentime').addClass('is-invalid').css({ 'background-image': 'none', 'padding-right': '0.75em' });
						parent.append($('<div class="invalid-feedback">' + obj.message + '</div>'));
					}
			   }
			});
			

			return false;
		});
		

// Task system removed - December 2025 (dead code - modal is commented out)
/*$(document).delegate('.opencreate_task', 'click', function () {
	$('#tasktermform')[0].reset();
	$('#tasktermform select').val('').trigger('change');
	$('.create_task').modal('show');
	$('.ifselecttask').hide();
	$('.ifselecttask select').attr('data-valid', '');
	
});*/
	// Removed: Subject dropdown JavaScript - subjects functionality has been removed
	
	
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


$(document).delegate('.other_info_add', 'click', function(){
	$('#other_info_add').modal('show');
	$('#other_info_add #academiModalLabel').html('Add Subject Area & Level');

});

$(document).delegate('.new_fee_option', 'click', function(){
	$('#new_fee_option').modal('show');
});

$(document).delegate('.other_info_edit', 'click', function(){
	$('#other_info_edit').modal('show');
	$('#other_info_edit #academiModalLabel').html('Edit Subject Area & Level');
	$.ajax({
			url: '{{URL::to('/product/getotherinfo')}}',
			type:'GET',
			data:{id:'{{$fetchedData->id}}'},
			success:function(response){
				$('.popuploader').hide();
				$('.showsubjecthtml').html(response);
				
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
	
	// Staff timezone + fee modals: Tom Select via email-modal-tomselect.js / initModalTomSelects
  
  $('#attachments').on('change',function(){
       // output raw value of file input
      $('.showattachment').html(''); 

        // or, manipulate it further with regex etc.
        var filename = $(this).val().replace(/.*(\/|\\)/, '');
        // .. do your magic

       $('.showattachment').html(filename);
    });
	
	$(document).delegate('#new_fee_option .installment_amount','keyup', function(){
		var installment_amount = $(this).val();
		var cserv = 0.00;
		if(installment_amount != ''){
			cserv = installment_amount;
		}
		
		var installment = $(this).parent().parent().find('.installment').val();
		
		var totalamount = parseFloat(cserv) * parseInt(installment);
		$(this).parent().parent().find('.total_fee span').html(totalamount.toFixed(2));
		$(this).parent().parent().find('.total_fee_am').val(totalamount.toFixed(2));
		var price = 0;
		$('#new_fee_option .total_fee_am').each(function(){
			price += parseFloat($(this).val());
		});
		
		$('#new_fee_option .net_totl').html(price.toFixed(2));
	});
	
	$(document).delegate('#new_fee_option .installment','keyup', function(){
		var installment = $(this).val();
		
		
		var installment_amount = $(this).parent().parent().find('.installment_amount').val();
		var cserv = 0.00;
		if(installment_amount != ''){
			cserv = installment_amount;
		}
		var totalamount = parseFloat(cserv) * parseInt(installment);
		$(this).parent().parent().find('.total_fee span').html(totalamount.toFixed(2));
		$(this).parent().parent().find('.total_fee_am').val(totalamount.toFixed(2));
		var price = 0;
		$('#new_fee_option .total_fee_am').each(function(){
			price += parseFloat($(this).val());
		});
		
		$('#new_fee_option .net_totl').html(price.toFixed(2));
	});
	
	$(document).delegate('.editfeeoption', 'click', function(){
		var appliid = $(this).attr('data-id');
		$('#editfeeoption').modal('show');
		$('.popuploader').show();
		$.ajax({
			url: '{{URL::to('/getfeeoptionedit')}}',
			type:'GET',
			data:{id:appliid},
			success:function(response){
				$('.popuploader').hide();
				$('.showfeeoptionedit').html(response);
				if (typeof initTomSelectAllPreserveValues === 'function') {
					initTomSelectAllPreserveValues('#editfeeoption .edit_installment_type, #editfeeoption .residencyelect2', {
						width: '100%',
						dropdownParent: document.querySelector('#editfeeoption .modal-content') || document.querySelector('#editfeeoption')
					});
				}
			}
		});
	});
	
	
	$(document).delegate('#editfeeoption .installment_amount','keyup', function(){
		var installment_amount = $(this).val();
		var cserv = 0.00;
		if(installment_amount != ''){
			cserv = installment_amount;
		}
		
		var installment = $(this).parent().parent().find('.installment').val();
		
		var totalamount = parseFloat(cserv) * parseInt(installment);
		$(this).parent().parent().find('.total_fee span').html(totalamount.toFixed(2));
		$(this).parent().parent().find('.total_fee_am').val(totalamount.toFixed(2));
		var price = 0;
		$('#editfeeoption .total_fee_am').each(function(){
			price += parseFloat($(this).val());
		});
		
		$('#editfeeoption .net_totl').html(price.toFixed(2));
	});
	
	$(document).delegate('#editfeeoption .installment','keyup', function(){
		var installment = $(this).val();
		
		
		var installment_amount = $(this).parent().parent().find('.installment_amount').val();
		var cserv = 0.00;
		if(installment_amount != ''){
			cserv = installment_amount;
		}
		var totalamount = parseFloat(cserv) * parseInt(installment);
		$(this).parent().parent().find('.total_fee span').html(totalamount.toFixed(2));
		$(this).parent().parent().find('.total_fee_am').val(totalamount.toFixed(2));
		var price = 0;
		$('#editfeeoption .total_fee_am').each(function(){
			price += parseFloat($(this).val());
		});
		
		$('#editfeeoption .net_totl').html(price.toFixed(2));
	});
	
	
	$(document).delegate('#new_fee_option .fee_option_addbtn a', 'click', function(){
		var html = '<tr class="add_fee_option cus_fee_option"><td><select data-valid="required" class="form-control course_fee_type" name="course_fee_type[]"><option value="">Select Type</option><option value="Accommodation Fee">Accommodation Fee</option><option value="Administration Fee">Administration Fee</option><option value="Airline Ticket">Airline Ticket</option><option value="Airport Transfer Fee">Airport Transfer Fee</option><option value="Application Fee">Application Fee</option><option value="Bond">Bond</option></select></td><td><input type="number" value="0" class="form-control installment_amount" name="installment_amount[]"></td><td><input type="number" value="1" class="form-control installment" name="installment[]"></td><td class="total_fee"><span>0.00</span><input type="hidden"  class="form-control total_fee_am" value="0" name="total_fee[]"></td><td><input type="number" value="1" class="form-control claimable_terms" name="claimable_terms[]"></td><td><input type="number" class="form-control commission" name="commission[]"></td><td><input value="1" class="add_quotation" type="checkbox" name="add_quotation[]"> <a href="javascript:;" class="removefeetype">@icon('trash')</a></td></tr>';
		$('#new_fee_option #productitemview tbody').append(html);
				
			}); 
			
	$(document).delegate('#new_fee_option .removefeetype', 'click', function(){
		$(this).parent().parent().remove();
		
		var price = 0;
		$('#new_fee_option .total_fee_am').each(function(){
			price += parseFloat($(this).val());
		});
		
		$('#new_fee_option .net_totl').html(price.toFixed(2));
	});
	
	$(document).delegate('#editfeeoption .fee_option_addbtn a', 'click', function(){
		var html = '<tr class="add_fee_option cus_fee_option"><td><select data-valid="required" class="form-control course_fee_type" name="course_fee_type[]"><option value="">Select Type</option><option value="Accommodation Fee">Accommodation Fee</option><option value="Administration Fee">Administration Fee</option><option value="Airline Ticket">Airline Ticket</option><option value="Airport Transfer Fee">Airport Transfer Fee</option><option value="Application Fee">Application Fee</option><option value="Bond">Bond</option></select></td><td><input type="number" value="0" class="form-control installment_amount" name="installment_amount[]"></td><td><input type="number" value="1" class="form-control installment" name="installment[]"></td><td class="total_fee"><span>0.00</span><input type="hidden"  class="form-control total_fee_am" value="0" name="total_fee[]"></td><td><input type="number" value="1" class="form-control claimable_terms" name="claimable_terms[]"></td><td><input type="number" class="form-control commission" name="commission[]"></td><td><input value="1" class="add_quotation" type="checkbox" name="add_quotation[]"> <a href="javascript:;" class="removefeetype">@icon('trash')</a></td></tr>';
		$('#editfeeoption #productitemview tbody').append(html);
				
			}); 
			
	$(document).delegate('#editfeeoption .removefeetype', 'click', function(){
		$(this).parent().parent().remove();
		
		var price = 0;
		$('#editfeeoption .total_fee_am').each(function(){
			price += parseFloat($(this).val());
		});
		
		$('#editfeeoption .net_totl').html(price.toFixed(2));
	});
	
	
	
	

	
});
</script>
@endsection