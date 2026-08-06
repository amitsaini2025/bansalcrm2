@extends('layouts.admin')
@section('title', 'Client Detail')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/client-detail.css') }}?v={{ (config('app.asset_version') ? config('app.asset_version').'-' : '') . filemtime(public_path('css/client-detail.css')) }}">
<style>
/* Fix: Tom Select dropdown z-index in modals */
.ts-wrapper.focus,
.ts-dropdown {
    z-index: 1060 !important;
}

/* Document preview: alignment and height (Blade-only for production) */
.file-preview-container {
    vertical-align: top !important;
    min-height: 500px !important;
}
.pdf-viewer, .doc-viewer {
    width: 100% !important;
    height: 75vh !important;
    min-height: 500px !important;
    border: none !important;
}

/* Multi-file attachment list — Compose Email modal */
.compose-attach-file-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    padding: 0;
    list-style: none;
    margin: 0;
}
.compose-attach-file-item {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    padding: 0.15rem 0.4rem;
    font-size: 0.78rem;
    max-width: 260px;
}
.compose-attach-file-icon {
    color: #64748b;
    font-size: 0.75rem;
    flex-shrink: 0;
}
.compose-attach-file-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 160px;
    color: #1e293b;
}
.compose-attach-file-size {
    color: #94a3b8;
    white-space: nowrap;
    flex-shrink: 0;
}
.compose-attach-remove-btn {
    background: none;
    border: none;
    padding: 0 0 0 0.15rem;
    cursor: pointer;
    color: #94a3b8;
    font-size: 0.7rem;
    line-height: 1;
    flex-shrink: 0;
    transition: color 0.15s;
}
.compose-attach-remove-btn:hover {
    color: #ef4444;
}
</style>
@endpush

@section('content')
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

			<?php
            //List if any attending inperssion session
            $attendingSessionExist = \App\Models\CheckinLog::where('client_id', '=', $fetchedData->id)->where('status', '=', '2')->orderBy('id', 'DESC')->get();
            if(!empty($attendingSessionExist) && count($attendingSessionExist) >0){?>
                <div class="row mb-3">
                    <div class="col-12">
                        <a href="javascript:void(0);" class="btn btn-primary complete_session" data-clientid="<?php echo $fetchedData->id;?>">Complete Session</a>
                    </div>
                </div>
            <?php }?>

		<div class="client-detail-container">
			<!-- Bottom Row: Personal Details (with Avatar) + Third Section -->
			<div class="bottom-row-container">
				<div class="personal-details-container">
					<div class="card left_section_lower">
						<!-- Avatar Section at Top -->
						<div class="card-body" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px 16px 0 0; padding: 20px; color: white;">
							<div class="author-box-center">
								<span class="author-avtar"><b>{{substr($fetchedData->first_name, 0, 1)}}{{substr($fetchedData->last_name, 0, 1)}}</b></span>
								<div class="author-box-name">
									<a href="#" style="color: white; text-decoration: none;">{{$fetchedData->first_name}} {{$fetchedData->last_name}}</a>
									<span style="color: rgba(255, 255, 255, 0.9); font-size: 11px;">{{$fetchedData->client_id}}</span>
								</div>
							</div>
								
							<div class="author-mail_sms">
								<a href="javascript:;" class="send-sms-btn" data-client-id="{{ @$fetchedData->id }}" data-client-name="{{ @$fetchedData->first_name }} {{ @$fetchedData->last_name }}" title="Send SMS">@icon('sms')</a>
								<a href="javascript:;" data-id="{{@$fetchedData->id}}" data-email="{{@$fetchedData->email}}" data-name="{{@$fetchedData->first_name}} {{@$fetchedData->last_name}}" class="clientemail" title="Compose Mail">@icon('envelope')</a>
								@if(!empty($canEditClient))
								<a href="{{ route('clients.edit', ['id' => $encodeId], false) }}" class="client-detail-edit-link" title="Edit" aria-label="Edit client">@icon('edit')</a>
								@endif
								@if($fetchedData->is_archived == 0)
									<a class="arcivedval" href="javascript:;" onclick="arcivedAction({{$fetchedData->id}}, 'admins')" title="Archive">@icon('archive')</a>
								@else
									<a class="arcivedval" style="background-color:rgba(239, 68, 68, 0.8);" href="javascript:;" onclick="arcivedAction({{$fetchedData->id}}, 'admins')" title="UnArchive">@icon('archive')</a>
								@endif
								<a href="javascript:;" class="client-detail-add-followup-btn" title="Add follow-up" aria-label="Add follow-up">@icon('calendar-plus')</a>
							</div>
							
							<div style="display: flex; gap: 8px; justify-content: center; margin: 12px 0; flex-wrap: wrap;">
								<a class="badge-outline col-greenf <?php if($fetchedData->type == 'client'){ echo 'active'; } ?>" href="{{ route('clients.changetype', ['id' => $encodeId, 'type' => 'client'], false) }}" data-crm-confirm="Are you sure?">Client</a>
								<a href="{{ route('clients.changetype', ['id' => $encodeId, 'type' => 'lead'], false) }}" class="badge-outline col-greenf <?php if($fetchedData->type == 'lead'){ echo 'active'; } ?>" data-crm-confirm="Are you sure?">Lead</a>
							</div>
							
							<button type="button" class="btn btn-primary btn-block btn-assignaction" data-bs-container="body" data-role="popover" data-bs-placement="auto" data-html="true" data-content="<div id=&quot;popover-content&quot;>
									<h4 class=&quot;text-center&quot;>Assign Staff</h4>
									<div class=&quot;clearfix&quot;></div>

							    <div class=&quot;box-header with-border&quot;>
								    <div class=&quot;form-group row&quot; style=&quot;margin-bottom:12px&quot; >
										<label for=&quot;inputSub3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>Select Assignee</label>
										<div class=&quot;col-sm-9&quot;>

											<select class=&quot;assignee-tomselect tomselect form-control selec_reg&quot; id=&quot;rem_cat&quot; name=&quot;rem_cat&quot; onchange=&quot;&quot;>
												<option value=&quot;&quot; >Select</option>
												@foreach(\App\Models\Staff::select('id', 'office_id', 'first_name', 'last_name')->where('status',1)->orderby('first_name','ASC')->get() as $admin)

												<?php
												$branchname = \App\Models\Branch::select('id', 'office_name')->where('id',$admin->office_id)->first();
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
											<input type=&quot;text&quot; class=&quot;form-control f13 flatpickr-date&quot; placeholder=&quot;yyyy-mm-dd&quot; id=&quot;popoverdatetime&quot; value=&quot;<?php echo date('Y-m-d');?>&quot; name=&quot;popoverdate&quot; autocomplete=&quot;off&quot;>
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

								<input id=&quot;assign_client_id&quot;  type=&quot;hidden&quot; value=&quot;{{base64_encode(convert_uuencode(@$fetchedData->id))}}&quot;>
							    <div class=&quot;box-footer&quot; style=&quot;padding:10px 0&quot;>
							    <div class=&quot;row&quot;>
									<input type=&quot;hidden&quot; value=&quot;&quot; id=&quot;popoverrealdate&quot; name=&quot;popoverrealdate&quot; />
							    </div>
							    <div class=&quot;row text-center&quot;>
									<div class=&quot;col-md-12 text-center&quot;>
									<button  class=&quot;btn btn-danger&quot; id=&quot;assignUser&quot;>Assign Staff</button>
									</div>
							    </div>
					    </div>" data-original-title="" title="" style="background: rgba(255, 255, 255, 0.95); color: var(--primary-color); padding: 10px; font-size: 13px;width:100% !important;"> Action</button>
							<?php
									// PostgreSQL doesn't accept empty strings for integer columns - check before querying
									$agent = null;
									if(!empty(@$fetchedData->agent_id) && @$fetchedData->agent_id !== '') {
										$agent = \App\Models\Agent::select('id', 'full_name', 'email')->where('id', @$fetchedData->agent_id)->first();
									}
									if($agent){
										?>
										<div class="client_assign client_info_tags" style="margin-top: 12px;">
																<span class=""><b>Agent:</b></span>
																@if($agent)
																<div class="client_info">
																	<div class="cl_logo">{{substr(@$agent->full_name, 0, 1)}}</div>
																	<div class="cl_name">
																		<span class="name">{{@$agent->full_name}}</span>
																		<span class="email">{{@$agent->email}}</span>
																	</div>
																</div>
																@else
																	-
																@endif
															</div>
										<?php
									}
								?>
						</div>
						
						<!-- Personal Details Section -->
						<div class="card-header">
							<div style="display: flex; align-items: center; gap: 8px;">
								<h4>Personal Details</h4>
								<a href="javascript:;" datatype="not_picked_call" class="not_picked_call" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 3px 10px; border-radius: 6px; font-size: 10px; font-weight: 600; letter-spacing: 0.5px; text-decoration: none;">NP</a>
							</div>
							<a href="javascript:;" datatype="note" class="create_note_d">@icon('plus') Add Notes</a>
						</div>
						<div class="card-body">
							<p class="clearfix">
								<span class="float-start">Last Updated</span>
								<span class="float-end text-muted">
									<?php
									if( isset($fetchedData->updated_at) && $fetchedData->updated_at != "" ){
										echo date('d/m/Y', strtotime('-5 hours 30 minute', strtotime($fetchedData->updated_at)));
									} else {
										echo '—';
									} ?>
								</span>
							</p>
						    <p class="clearfix">
								<span class="float-start">Date Of Birth / Age</span>
								<span class="float-end text-muted">
									<?php
										if($fetchedData->dob != '' && $fetchedData->dob != '0000-00-00'){
										    echo $dob = date('d/m/Y', strtotime($fetchedData->dob));
										}
										?>	<?php
										if($fetchedData->age != ''){
										    echo ' / '.$fetchedData->age;
										}
									?>
								</span>
							</p>
							<p class="clearfix">
								<span class="float-start">Visa:</span>
								<span class="float-end text-muted">{{$fetchedData->visa_type}}
									<?php
										if($fetchedData->visa_opt != ''){
										    //echo '<br>'.$fetchedData->visa_opt;
										    echo $fetchedData->visa_opt;
										}
									?>
								</span>
							</p>
							<p class="clearfix">
								<span class="float-start">Visa Expiry:</span>
								<span class="float-end text-muted"><?php
										if($fetchedData->visaExpiry != '' && $fetchedData->visaExpiry != '0000-00-00'){
										    echo date('d/m/Y', strtotime($fetchedData->visaExpiry));
										}
										?>
								</span>
							</p>


							<p class="clearfix">
								<span class="float-start">Phone No:</span>
								<span class="float-end text-muted">
                                    {{--$fetchedData->phone--}} {{--@if($fetchedData->att_phone != '') / {{$fetchedData->att_phone}} @endif--}}
                                    <?php
                                    $isLeadDetail = isset($fetchedData->type) && $fetchedData->type == 'lead';
                                    // For leads (type='lead'), use the phone from $fetchedData which comes from leads table
                                    // For clients, check ClientPhone table first, then admins table
                                    if( $isLeadDetail ) {
                                        // Lead: use phone data passed from controller (from leads table or admins for admin-only)
                                        $clientContacts = collect([
                                            (object)[
                                                'client_phone' => $fetchedData->phone,
                                                'client_country_code' => $fetchedData->country_code ?? '',
                                                'contact_type' => $fetchedData->contact_type ?? 'Personal',
                                                'is_lead_primary' => true,
                                                'lead_id' => $fetchedData->lead_id ?? null,
                                                'admin_id' => $fetchedData->id,
                                                'is_verified' => $fetchedData->is_verified ?? false
                                            ]
                                        ]);
                                    } elseif( \App\Models\ClientPhone::where('client_id', $fetchedData->id)->exists()) {
                                        $clientContacts = \App\Models\ClientPhone::select('client_phone','client_country_code','contact_type','is_verified')->where('client_id', $fetchedData->id)->where('contact_type', '!=', 'Not In Use')->get();
                                    } else {
                                        if( \App\Models\Admin::where('id', $fetchedData->id)->exists()){
                                            $clientContacts = \App\Models\Admin::select('phone as client_phone','country_code as client_country_code','contact_type')->where('id', $fetchedData->id)->get();
                                        } else {
                                            $clientContacts = array();
                                        }
                                    }
                                    if( !empty($clientContacts) && count($clientContacts)>0 ){
                                        $phonenoStr = "";
                                        foreach($clientContacts as $conKey=>$conVal){
                                            //Check phone is verified or not (lead: use Admin model; client: use client_phones.is_verified)
                                            $verifiedNumber = null;
                                            if ( !empty($isLeadDetail) && !empty($conVal->is_lead_primary) ) {
                                                if ( !empty($conVal->lead_id) ) {
                                                    $leadRow = \App\Models\Admin::where('lead_id', $conVal->lead_id)->where('type','lead')->first()
                                                        ?? \App\Models\Admin::where('id', $conVal->lead_id)->where('type','lead')->first();
                                                    $verifiedNumber = $leadRow && ($leadRow->is_verified ?? false) ? (object)['is_verified' => true] : null;
                                                } else {
                                                    $verifiedNumber = !empty($conVal->is_verified) ? (object)['is_verified' => true] : null;
                                                }
                                            }
                                            if ( $verifiedNumber === null && empty($isLeadDetail) ) {
                                                $verifiedNumber = !empty($conVal->is_verified) ? (object)['is_verified' => true] : null;
                                            }


                                            if( isset($conVal->client_country_code) && $conVal->client_country_code != "" ){
                                                $client_country_code = $conVal->client_country_code;
                                            } else {
                                                $client_country_code = "";
                                            }

                                            if( isset($conVal->contact_type) && $conVal->contact_type != "" ){
												if( $conVal->contact_type == "Personal" ){
													if ( $verifiedNumber) {
														$phonenoStr .= $client_country_code."".$conVal->client_phone.'('.$conVal->contact_type .') '.\App\Helpers\IconHelper::render('check-circle', 'solid', ['class' => 'verified-icon']);
													} else {
														$phonenoStr .= $client_country_code."".$conVal->client_phone.'('.$conVal->contact_type .') '.\App\Helpers\IconHelper::render('circle', 'regular', ['class' => 'unverified-icon']);
														// Lead primary phone: resolve admins.id first (admin-only leads have lead_id null), then migrated lead_id
														if ( !empty($conVal->is_lead_primary) ) {
															$leadRow = null;
															if ( !empty($conVal->admin_id) ) {
																$leadRow = \App\Models\Admin::where('id', $conVal->admin_id)->where('type', 'lead')->first();
															}
															if ( ! $leadRow && !empty($conVal->lead_id) ) {
																$leadRow = \App\Models\Admin::where('lead_id', $conVal->lead_id)->where('type', 'lead')->first()
																	?? \App\Models\Admin::where('id', $conVal->lead_id)->where('type', 'lead')->first();
															}
															if ( $leadRow && $leadRow->needsVerification() ) {
																$phonenoStr .= ' <a href="javascript:;" class="btn-lead-verify-phone" data-lead-id="'.(int)$leadRow->id.'" title="Verify">Verify</a>';
															}
														}
													}
													$phonenoStr .= ' <br/>';
												} else {
													$phonenoStr .= $client_country_code."".$conVal->client_phone.'('.$conVal->contact_type .') <br/>';
												}
											} else {
												$phonenoStr .= $client_country_code."".$conVal->client_phone.' <br/>';
                                            }
                                        }
                                        echo $phonenoStr;
                                    } else {
                                        echo "N/A";
                                    }?>
                                </span>
							</p>


							<p class="clearfix">
								<span class="float-start">Email / Is verified:</span>
								<span class="float-end text-muted">
								    <?php
								    $allEmails = [];
								    $primaryFromAdmins = trim($fetchedData->email ?? '');
								    $clientEmails = \App\Models\ClientEmail::where('client_id', $fetchedData->id)->orderBy('id')->get();
								    if ($primaryFromAdmins !== '' && $clientEmails->isEmpty()) {
								        $allEmails[] = ['email' => $primaryFromAdmins, 'verified' => (isset($fetchedData->manual_email_phone_verified) && $fetchedData->manual_email_phone_verified == '1')];
								    } else {
								        $emailsFromCe = $clientEmails->pluck('client_email')->map(function($e){ return strtolower(trim($e)); })->toArray();
								        if ($primaryFromAdmins !== '' && !in_array(strtolower($primaryFromAdmins), $emailsFromCe)) {
								            $allEmails[] = ['email' => $primaryFromAdmins, 'verified' => (isset($fetchedData->manual_email_phone_verified) && $fetchedData->manual_email_phone_verified == '1')];
								        }
								        foreach ($clientEmails as $ce) {
								            $allEmails[] = ['email' => $ce->client_email, 'verified' => false];
								        }
								    }
								    if (empty($allEmails) && $primaryFromAdmins !== '') {
								        $allEmails[] = ['email' => $primaryFromAdmins, 'verified' => (isset($fetchedData->manual_email_phone_verified) && $fetchedData->manual_email_phone_verified == '1')];
								    }
								    foreach ($allEmails as $i => $e) {
								        if ($i > 0) echo '<br/>';
								        echo e($e['email']);
								        if ($e['verified']) {
								            echo ' '.\App\Helpers\IconHelper::render('check-circle', 'solid', ['class' => 'verified-icon']);
								        } else {
								            echo ' '.\App\Helpers\IconHelper::render('circle', 'regular', ['class' => 'unverified-icon']);
								        }
								    }
								    if (empty($allEmails)) echo '—';
								    ?>
                                </span>
							</p>

                            <?php if( isset($fetchedData->email_verified_at) && $fetchedData->email_verified_at != "" ){ ?>
                                <p class="clearfix">
                                    <span class="float-start">Email Verified At:</span>
                                    <span class="float-end text-muted">{{ date('d/m/Y',strtotime($fetchedData->email_verified_at))}}</span>
                                </p>
                            <?php } ?>

							<p class="clearfix">
								<span class="float-start">City:</span>
								<span class="float-end text-muted">{{$fetchedData->city}}</span>
							</p>
							<p class="clearfix">
								<span class="float-start">Nominated Occupation:</span>
								<span class="float-end text-muted">{{$fetchedData->nomi_occupation}}</span>
							</p>
							<p class="clearfix">
								<span class="float-start">Highest Qualification in Australia:</span>
								<span class="float-end text-muted">{{$fetchedData->high_quali_aus}}</span>
							</p>
							<p class="clearfix">
								<span class="float-start">Highest Qualification Overseas:</span>
								<span class="float-end text-muted">{{$fetchedData->high_quali_overseas}}</span>
							</p>
							<p class="clearfix">
								<span class="float-start">Work experience Australia:</span>
								<span class="float-end text-muted">{{$fetchedData->relevant_work_exp_aus}}</span>
							</p>
								<p class="clearfix">
								<span class="float-start">Work experience Offshore:</span>
								<span class="float-end text-muted">{{$fetchedData->relevant_work_exp_over}}</span>
							</p>
							<p class="clearfix">
								<span class="float-start">Overall English score: </span>
								<?php
									$testScoreRows = \App\Models\ClientTestScore::where('client_id', $fetchedData->id)->orderBy('updated_at', 'desc')->get();
									$testscores = $testScoreRows->first();
								?>
								<span class="float-end text-muted">@if($testscores){{ $testscores->test_type }}: {{ $testscores->overall_score ?? '-' }}@else - @endif</span>
							</p>
							<p class="clearfix">
								<span class="float-start">Preferred Intake:</span>
								<span class="float-end text-muted"><?php if($fetchedData->preferredIntake != ''){ ?>{{date('M Y', strtotime($fetchedData->preferredIntake))}}<?php } ?></span>
							</p>
							 <p class="clearfix">
								<span class="float-start">Naati/PY</span>
								<span class="float-end text-muted"><?php if($fetchedData->naati_py != ''){ ?>{{$fetchedData->naati_py}}<?php } ?></span>
							</p>
							<?php
								// PostgreSQL doesn't accept empty strings for integer columns - check before querying
								$addedby = null;
								if(!empty(@$fetchedData->user_id) && @$fetchedData->user_id !== '') {
									$addedby = \App\Models\Staff::select('id', 'first_name', 'last_name')->find(@$fetchedData->user_id);
								}
							?>
							<div class="client_added client_info_tags">
								<span class="">Added By:</span>
								@if($addedby)
								<div class="client_info">
									<div class="cl_logo">{{substr(@$addedby->first_name, 0, 1)}}</div>
									<div class="cl_name">
										<span class="name">{{@$addedby->first_name}}</span>
										<span class="email">{{@$addedby->email}}</span>
									</div>
								</div>
								@else
									-
								@endif
							</div>
								<?php
                                use Illuminate\Support\Str;
                                //dd($fetchedData->assignee);
                                if( Str::contains($fetchedData->assignee, ',')){
                                    $assigneeUArr = explode(",",$fetchedData->assignee);
                                    $assigneeArr = \App\Models\Staff::select('id', 'first_name', 'last_name')->whereIn('id',$assigneeUArr)->get();
                                } else {
                                    $assigneeU = $fetchedData->assignee;
                                    $assigneeArr = \App\Models\Staff::select('id', 'first_name', 'last_name')->where('id',$assigneeU)->get();
                                }
                                //dd($assigneeArr);
                                ?>
							<div class="client_assign client_info_tags">
								<span class="">Office:</span>
								<span class="float-end text-muted">
									@if($fetchedData->office)
										<a href="{{ URL::to('/branch/view/'.$fetchedData->office->id) }}">{{ $fetchedData->office->office_name }}</a>
									@else
										-
									@endif
								</span>
								<div class="clearfix"></div>
							</div>
							<div class="client_assign client_info_tags">
								<span class="">Assignee:</span>
								<span class="float-end text-muted">
								      <a href="javascript:;" data-id="{{$fetchedData->id}}" class="btn btn-primary openassigneeshow btn-sm">@icon('plus') Edit</a>
								    </span>
								    <div class="clearfix"></div>

								<?php
                                if( !empty($assigneeArr) && count($assigneeArr) >0 ){
                                    foreach ($assigneeArr as $assignee) {
                                ?>

								{{-- @if($assignee) --}}
                                <div class="client_info">
                                    <div class="cl_logo">{{substr(@$assignee->first_name, 0, 1)}}</div>
                                    <div class="cl_name">
                                        <span class="name">{{@$assignee->first_name}}</span>
                                        <!--<span class="email">{{--@$assignee->email--}}</span>-->
                                    </div>
                                </div>
                                <?php
                                    }
                                }
                                else { echo "-"; } ?>

								{{-- @else --}}
									<!-- -  -->
								{{-- @endif --}}

								<div class="assigneeshow" style="display:none;">
								    <table>
								        <tr>
								            <td><select class="form-control tomselect" id="changeassignee" name="changeassignee[]" multiple="multiple">
						                 	<?php
												foreach(\App\Models\Staff::select('id', 'office_id', 'first_name', 'last_name')->orderby('first_name','ASC')->get() as $admin){
													$branchname = \App\Models\Branch::select('id', 'office_name')->where('id',$admin->office_id)->first();
											?>
												<option value="<?php echo $admin->id; ?>"><?php echo $admin->first_name.' '.$admin->last_name.' ('.@$branchname->office_name.')'; ?></option>
											<?php } ?>
												</select></td>
											<td><a class="saveassignee btn btn-success" data-id="<?php echo $fetchedData->id; ?>" href="javascript:;">Save</a></td>
											<td><a class="closeassigneeshow" href="javascript:;">@icon('times')</a></td>
								        </tr>
								    </table>
								</div>
							</div>
							<div class="client_assign client_info_tags">
								<p class="clearfix">
                                    <span class="float-start">Services Taken:</span>
                                    <!--<span class="float-end text-muted">
                                        <a href="javascript:;" data-id="{{--$fetchedData->id--}}" class="btn btn-primary serviceTaken btn-sm">@icon('plus') Add</a>
                                    </span>-->
                                </p>

                                 <div class="client_info">
								    <ul style="margin-left: -35px;">
										<?php
                                        $serviceTakenArr = \App\Models\clientServiceTaken::where('client_id', $fetchedData->id )->orderBy('created_at', 'desc')->get();
                                        //dd($serviceTakenArr);
                                        if( !empty($serviceTakenArr) && count($serviceTakenArr) >0 ){
                                            foreach ($serviceTakenArr as $tokenkey => $tokenval) {
                                                if($tokenval['service_type']  == "Migration") {
                                                    echo $tokenval['service_type']."-".htmlspecialchars($tokenval['mig_ref_no'])."-".htmlspecialchars($tokenval['mig_service'])."-".htmlspecialchars($tokenval['mig_notes']). "<br/>";
                                                } else if($tokenval['service_type']  == "Education") {
                                                    echo $tokenval['service_type']."-".htmlspecialchars($tokenval['edu_course'])."-".htmlspecialchars($tokenval['edu_college'])."-".htmlspecialchars($tokenval['edu_service_start_date'])."-".htmlspecialchars($tokenval['edu_notes']). "<br/>";
                                                }
                                            }
                                        } ?>
									</ul>
								</div>
                             </div>

                             <div class="">
								<p class="clearfix">
                                    <span class="float-start">Related files:</span>
                                </p>

								<div class="client_info">
								    <ul >
										<?php
											//$relatedclientss = \App\Models\Admin::select('id',  'first_name', 'last_name')->whereRaw("FIND_IN_SET($fetchedData->id,related_files)")->get();
											//foreach($relatedclientss AS $res){
										?>
											<!--<li><a target="_blank" href="{{--URL::to('/clients/detail/'.base64_encode(convert_uuencode(@$res->id)))--}}">{{--$res->first_name--}} {{--$res->last_name--}}</a></li>-->
										<?php //} ?>

										<?php
										if($fetchedData->related_files != ''){
											$exploder = explode(',', $fetchedData->related_files);

										?>
										<?php   
										if(!empty($exploder)) {
											foreach($exploder AS $EXP){
												// PostgreSQL doesn't accept empty strings for integer columns - filter empty values
												if(!empty(trim($EXP)) && trim($EXP) !== '') {
													$relatedclients = \App\Models\Admin::where('id', trim($EXP))->first();
													if($relatedclients) {
										?>
														<li><a target="_blank" href="{{URL::to('/clients/detail/'.base64_encode(convert_uuencode(@$relatedclients->id)))}}">{{$relatedclients->first_name}} {{$relatedclients->last_name}}</a></li>
										<?php 
													}
												}
											} 
										}
										?>
										<?php } ?>
									</ul>
								</div>
							</div>

							<p class="clearfix">
								<span class="float-start">Tag(s):</span>
								<span class="float-end text-muted">
									<a href="javascript:;" data-id="{{$fetchedData->id}}" class="btn btn-primary opentagspopup btn-sm">@icon('plus') Add</a>
								</span>
							</p>
							<p>
								<?php
									$tagList = array();
									if(!empty($fetchedData->tagname)){
										$rawTags = explode(',', $fetchedData->tagname);
										foreach($rawTags as $rawTag){
											$cleanTag = trim($rawTag);
											if($cleanTag !== ''){
												$tagList[] = $cleanTag;
											}
										}
									}
								?>
								@if(!empty($tagList))
									@foreach($tagList as $tagLabel)
										<span class="badge bg-light text-dark ag-flex ag-align-center ag-space-between" style="display: inline-flex;">
											<span class="col-hr-1" style="font-size: 12px;">{{ $tagLabel }}</span>
										</span>
									@endforeach
								@else
									<span class="text-muted">No tags added</span>
								@endif
							</p>
						</div>
					</div>
				</div> <!-- end personal-details-container -->

			<!--<div class="col-12 col-md-12 col-lg-12">-->
              
             
			<div class="right_section">
				<div class="card">
						<div class="card-body">
							@php
							$allowedTabs = [
								'activities',
								'noteterm',
								'application',
								'alldocuments',
								'notuseddocuments',
								'accounts',
							];
								$tabAliases = [
									'notestrm' => 'noteterm',
									'documents' => 'alldocuments',
									'migrationdocuments' => 'alldocuments'
								];
								$allowedTabSlugs = array_unique(array_merge($allowedTabs, array_keys($tabAliases)));
								$requestedTab = (isset($forcedTab) && $forcedTab)
									? $forcedTab
									: (Request::route('tab') ?? Request::get('tab'));
							if (in_array($requestedTab, ['documents', 'migrationdocuments'])) {
								$requestedTab = 'alldocuments';
							}
													if (empty($requestedTab) || !in_array($requestedTab, $allowedTabSlugs, true)) {
								$requestedTab = 'activities';
							}
								$activeTab = $tabAliases[$requestedTab] ?? $requestedTab;
								$activeTabSlug = array_search($activeTab, $tabAliases, true);
								if ($activeTabSlug === false) {
									$activeTabSlug = $requestedTab;
								}
								// Redirected from documents/migrationdocuments: use alldocuments as URL slug
								if ($activeTab === 'alldocuments' && in_array($activeTabSlug, ['documents', 'migrationdocuments'])) {
									$activeTabSlug = 'alldocuments';
								}
								$detailBaseUrl = Request::route()
									&& \Illuminate\Support\Str::startsWith(Request::route()->getName(), 'leads.detail')
										? url('/leads/detail/'.$encodeId)
										: url('/clients/detail/'.$encodeId);
							@endphp
							<ul class="nav nav-pills" id="client_tabs" role="tablist" data-base-url="{{ $detailBaseUrl }}" data-active-tab="{{ $activeTabSlug }}" data-application-id="{{ $applicationId ?? '' }}">
								<li class="nav-item">
									<a class="nav-link {{ $activeTab === 'activities' ? 'active' : '' }}" data-bs-toggle="tab" data-tab="activities" id="activities-tab" href="#activities" role="tab" aria-controls="activities" aria-selected="{{ $activeTab === 'activities' ? 'true' : 'false' }}">Activities</a>
								</li>

								<li class="nav-item">
									<a class="nav-link {{ $activeTab === 'noteterm' ? 'active' : '' }}" data-bs-toggle="tab" data-tab="notestrm" id="noteterm-tab" href="#noteterm" role="tab" aria-controls="noteterm" aria-selected="{{ $activeTab === 'noteterm' ? 'true' : 'false' }}">Notes & Terms</a>
								</li>

								<li class="nav-item">
									<a class="nav-link {{ $activeTab === 'application' ? 'active' : '' }}" data-bs-toggle="tab" data-tab="application" id="application-tab" href="#application" role="tab" aria-controls="application" aria-selected="{{ $activeTab === 'application' ? 'true' : 'false' }}">Applications</a>
								</li>
                                <li class="nav-item">
                                    <a class="nav-link {{ $activeTab === 'alldocuments' ? 'active' : '' }}" data-bs-toggle="tab" data-tab="alldocuments" id="alldocuments-tab" href="#alldocuments" role="tab" aria-controls="alldocuments" aria-selected="{{ $activeTab === 'alldocuments' ? 'true' : 'false' }}">Documents</a>
                                </li>

                                <li class="nav-item">
                                    <a class="nav-link {{ $activeTab === 'notuseddocuments' ? 'active' : '' }}" data-bs-toggle="tab" data-tab="notuseddocuments" id="notuseddocuments-tab" href="#notuseddocuments" role="tab" aria-controls="notuseddocuments" aria-selected="{{ $activeTab === 'notuseddocuments' ? 'true' : 'false' }}">Not Used Documents</a>
                                </li>

								<li class="nav-item">
									{{-- <a class="nav-link" data-bs-toggle="tab" id="appointments-tab" href="#appointments" role="tab" aria-controls="appointments" aria-selected="false">Appointments</a> --}}
								</li>

								<!--<li class="nav-item">
									<a class="nav-link" data-bs-toggle="tab" id="noteterm-tab" href="#noteterm" role="tab" aria-controls="noteterm" aria-selected="false">Notes & Terms</a>
								</li>-->

								<li class="nav-item">
									<a class="nav-link {{ $activeTab === 'accounts' ? 'active' : '' }}" data-bs-toggle="tab" data-tab="accounts" id="accounts-tab" href="#accounts" role="tab" aria-controls="accounts" aria-selected="{{ $activeTab === 'accounts' ? 'true' : 'false' }}">Accounts</a>
								</li>
							<li class="nav-item">
								<a class="nav-link {{ $activeTab === 'email-v2' ? 'active' : '' }}" data-bs-toggle="tab" data-tab="email-v2" id="email-v2-tab" href="#email-v2" role="tab" aria-controls="email-v2" aria-selected="{{ $activeTab === 'email-v2' ? 'true' : 'false' }}">Emails</a>
							</li>
								<!--<li class="nav-item">
									<a class="nav-link" data-bs-toggle="tab" id="other_info-tab" href="#other_info" role="tab" aria-controls="other_info" aria-selected="false">Other Information</a>
								</li>-->
							</ul>
							<div class="tab-content" id="clientContent" style="padding-top:15px;">
								<div class="tab-pane fade {{ $activeTab === 'activities' ? 'show active' : '' }}" id="activities" role="tabpanel" aria-labelledby="activities-tab">

								<!-- Activities Filter Bar - Permanently Visible -->
								<div class="activities-filter-bar">
									<form action="{{ $detailBaseUrl }}" method="get" id="activitiesFilterForm">
										@if($activeTabSlug && $activeTabSlug !== 'activities')
											<input type="hidden" name="tab" value="{{ $activeTabSlug }}">
										@endif
										
										<div class="row align-items-end">
											<!-- Search Box -->
											<div class="col-md-3 col-lg-2">
												<div class="form-group mb-0">
													<label for="activity_search" class="form-label small text-muted mb-1">Search</label>
													<input type="text" 
														   class="form-control form-control-sm" 
														   id="activity_search" 
														   name="keyword" 
														   value="{{ Request::get('keyword', '') }}" 
														   placeholder="Search activities...">
												</div>
											</div>

											<!-- Activity Type Filters -->
											<div class="col-md-6 col-lg-7">
												<div class="form-group mb-0">
													<label class="form-label small text-muted mb-1 d-block">Type</label>
													<div class="activity-type-filters" style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
										<?php
														// Main filter buttons (always visible)
														$mainTypes = [
															'all' => 'All',
															'notes' => 'Notes'
														];
														
														// Dropdown types (all other types)
														$dropdownTypes = [
															'documents' => 'Documents',
															'action' => 'Action',
															'accounting' => 'Accounting',
															'messages' => 'Messages',
															'calls' => 'Calls',
															'reviews' => 'Reviews',
															'reminders' => 'Reminders',
															'applications' => 'Applications',
															'services' => 'Services',
															'status' => 'Status',
															'checkins' => 'Check-ins',
															'other' => 'Other'
														];
														
														$selectedType = Request::get('activity_type', 'all');
														
														// Display main buttons
														foreach($mainTypes as $key => $label) {
															$active = ($selectedType == $key) ? 'active' : '';
															echo '<button type="button" class="btn btn-sm activity-type-btn ' . $active . '" data-type="' . $key . '" style="border: 1px solid #ddd; background: #fff; padding: 5px 12px; font-size: 12px; border-radius: 4px; white-space: nowrap;">' . $label . '</button>';
														}
														
														// Display dropdown for additional types
														$dropdownActive = in_array($selectedType, array_keys($dropdownTypes));
														$dropdownLabel = $dropdownActive ? $dropdownTypes[$selectedType] : 'More...';
														?>
														<div class="dropdown d-inline-block">
															<button type="button" 
																	class="btn btn-sm activity-type-btn dropdown-toggle <?php echo $dropdownActive ? 'active' : ''; ?>" 
																	data-bs-toggle="dropdown" 
																	aria-expanded="false"
																	style="border: 1px solid #ddd; background: <?php echo $dropdownActive ? '#6777ef' : '#fff'; ?>; color: <?php echo $dropdownActive ? '#fff' : '#000'; ?>; padding: 5px 12px; font-size: 12px; border-radius: 4px; white-space: nowrap;">
																<?php echo $dropdownLabel; ?>
															</button>
															<ul class="dropdown-menu" style="font-size: 12px;">
																<?php foreach($dropdownTypes as $key => $label): ?>
																	<li>
																		<a class="dropdown-item activity-type-dropdown-item <?php echo ($selectedType == $key) ? 'active' : ''; ?>" 
																		   href="javascript:;" 
																		   data-type="<?php echo $key; ?>"
																		   style="<?php echo ($selectedType == $key) ? 'background-color: #6777ef; color: #fff;' : ''; ?>">
																			<?php echo $label; ?>
																		</a>
																	</li>
																<?php endforeach; ?>
															</ul>
														</div>
														<input type="hidden" name="activity_type" id="activity_type_input" value="{{ $selectedType }}">
													</div>
												</div>
											</div>

											<!-- Date Range Filter -->
											<div class="col-md-3 col-lg-3">
												<div class="form-group mb-0">
													<label for="date_from" class="form-label small text-muted mb-1">Date Range</label>
													<div class="d-flex gap-2">
														<input type="text" 
															   class="form-control form-control-sm date-filter" 
															   id="date_from" 
															   name="date_from" 
															   value="{{ Request::get('date_from', '') }}" 
															   placeholder="From" 
															   autocomplete="off"
															   style="flex: 1;">
														<input type="text" 
															   class="form-control form-control-sm date-filter" 
															   id="date_to" 
															   name="date_to" 
															   value="{{ Request::get('date_to', '') }}" 
															   placeholder="To" 
															   autocomplete="off"
															   style="flex: 1;">
													</div>
												</div>
											</div>
										</div>

										<!-- Action Buttons -->
										<div class="row mt-2">
											<div class="col-12">
												<button type="submit" class="btn btn-primary btn-sm" style="margin-right: 8px;">
													@icon('search') Apply Filters
												</button>
												<a href="{{ $detailBaseUrl }}" class="btn btn-secondary btn-sm">
													@icon('redo') Reset
												</a>
												<?php if(Request::get('keyword') || Request::get('activity_type') != 'all' || Request::get('date_from') || Request::get('date_to')): ?>
													<span class="badge bg-info ms-2" style="vertical-align: middle; padding: 6px 10px;">
														Filters Active
													</span>
												<?php endif; ?>
											</div>
										</div>
									</form>
								</div>

								<div class="activities">
										<?php
										// Build query with filters
										$query = \App\Models\ActivitiesLog::where('activities_logs.client_id', $fetchedData->id);
										
										// Keyword search filter
										$keyword_search = Request::get('keyword', '');
										if($keyword_search != "") {
											$query->where(function($q) use ($keyword_search) {
												$q->where('activities_logs.description', 'like', '%'.$keyword_search.'%')
												  ->orWhere('activities_logs.subject', 'like', '%'.$keyword_search.'%');
											});
										}
										
										// Activity type filter
										$activity_type = Request::get('activity_type', 'all');
										if($activity_type != 'all') {
											switch($activity_type) {
												case 'notes':
													$query->where(function($q) {
														$q->where('activities_logs.subject', 'like', '%added a note%')
														  ->orWhere('activities_logs.subject', 'like', '%updated a note%')
														  ->orWhere('activities_logs.subject', 'like', '%deleted a note%');
													});
													break;
												case 'messages':
													$query->where('activities_logs.subject', 'like', '%sent a message%');
													break;
												case 'calls':
													$query->where(function($q) {
														$q->where('activities_logs.description', 'like', '%Call not picked%')
														  ->orWhere('activities_logs.subject', 'like', '%call%');
													});
													break;
												case 'reviews':
													$query->where('activities_logs.subject', 'like', '%review%');
													break;
												case 'reminders':
													$query->where(function($q) {
														$q->where('activities_logs.subject', 'like', '%Email reminder sent%')
														  ->orWhere('activities_logs.subject', 'like', '%SMS reminder sent%')
														  ->orWhere('activities_logs.subject', 'like', '%Phone reminder recorded%')
														  ->orWhere('activities_logs.subject', 'like', '%Checklist Email sent%')
														  ->orWhere('activities_logs.subject', 'like', '%Checklist Email resent%')
														  ->orWhere('activities_logs.subject', 'like', '%Document Checklist sent%');
													});
													break;
												case 'documents':
													$query->where(function($q) {
														$q->where('activities_logs.subject', 'like', '%document%')
														  ->orWhere('activities_logs.subject', 'like', '%uploaded%')
														  ->orWhere('activities_logs.subject', 'like', '%verified%');
													});
													break;
												case 'action':
													// Renamed from 'tasks' - Groups: Tasks, Actions
													$query->where(function($q) {
														$q->where('activities_logs.subject', 'like', '%action%')
														  ->orWhere('activities_logs.subject', 'like', '%task%')
														  ->orWhere('activities_logs.subject', 'like', '%Completed action%')
														  ->orWhere('activities_logs.task_status', '=', 1);
													});
													break;
												case 'accounting':
													$query->where(function($q) {
														$q->where('activities_logs.subject', 'like', '%receipt%')
														  ->orWhere('activities_logs.subject', 'like', '%invoice%')
														  ->orWhere('activities_logs.subject', 'like', '%payment%');
													});
													break;
												case 'applications':
													$query->where('activities_logs.subject', 'like', '%started an application%');
													break;
											case 'services':
												$query->where(function($q) {
													$q->where('activities_logs.subject', 'like', '%an interested service%');
												});
												break;
												case 'status':
													$query->where(function($q) {
														$q->where('activities_logs.subject', 'like', '%status%')
														  ->orWhere('activities_logs.subject', 'like', '%rated%')
														  ->orWhere('activities_logs.subject', 'like', '%rating%');
													});
													break;
												case 'checkins':
													$query->where(function($q) {
														$q->where('activities_logs.subject', 'like', '%check-in%')
														  ->orWhere('activities_logs.subject', 'like', '%session%')
														  ->orWhere('activities_logs.subject', 'like', '%commented%');
													});
													break;
												case 'other':
													// Exclude all known types
													$query->where(function($q) {
														$q->where('activities_logs.subject', 'not like', '%note%')
														  ->where('activities_logs.subject', 'not like', '%document%')
														  ->where('activities_logs.subject', 'not like', '%action%')
														  ->where('activities_logs.subject', 'not like', '%task%')
														  ->where('activities_logs.subject', 'not like', '%receipt%')
														  ->where('activities_logs.subject', 'not like', '%application%')
														  ->where('activities_logs.subject', 'not like', '%message%')
														  ->where('activities_logs.subject', 'not like', '%call%')
														  ->where('activities_logs.subject', 'not like', '%service%')
														  ->where('activities_logs.subject', 'not like', '%status%')
														  ->where('activities_logs.subject', 'not like', '%check-in%')
														  ->where('activities_logs.subject', 'not like', '%session%')
														  ->where('activities_logs.subject', 'not like', '%review%')
														  ->where('activities_logs.subject', 'not like', '%reminder%')
														  ->where('activities_logs.subject', 'not like', '%Checklist Email sent%')
														  ->where('activities_logs.subject', 'not like', '%Checklist Email resent%');
													});
													break;
											}
										}
										
										// Date range filter
										$date_from = Request::get('date_from', '');
										$date_to = Request::get('date_to', '');
										if($date_from != "") {
											$query->whereDate('activities_logs.created_at', '>=', date('Y-m-d', strtotime($date_from)));
										}
										if($date_to != "") {
											$query->whereDate('activities_logs.created_at', '<=', date('Y-m-d', strtotime($date_to)));
										}
										
										// Execute query
										$activities = $query->orderby('activities_logs.created_at', 'DESC')->get();

										//dd($activities);
                                        foreach($activities as $activit){
											$admin = \App\Models\Staff::find($activit->created_by) ?? \App\Models\Admin::find($activit->created_by);
                                            /*if($activit->use_for != ""){
                                                $receiver = \App\Models\Staff::find($activit->use_for) ?? \App\Models\Admin::find($activit->use_for);
                                                if($receiver->first_name){
                                                    $reciver_name = "to <b>{$receiver->first_name}</b>";
                                                } else {
                                                    $reciver_name = "";
                                                }
                                            } else {
                                                $reciver_name = "";
                                            }*/

											?>
											<div class="activity" id="activity_{{$activit->id}}">
												<div class="activity-icon bg-primary text-white">
													<span>{{substr($admin->first_name, 0, 1)}}</span>
												</div>
												<div class="activity-detail">
												    <div class="activity-head">
												    	<div class="activity-title">
															<p><b>{{$admin->first_name}}</b>  <?php echo @$activit->subject; ?></p>
                                                    	</div>

														<div class="activity-head-actions">
															<div class="activity-date">
																<span class="text-job">{{date('d M Y, H:i A', strtotime($activit->created_at))}}</span>
															</div>

															<div class="activity-actions">
																<?php if($activit->pin == 1){?>
																	<div class="pined_note">{!! \App\Helpers\IconHelper::render('thumbtack', 'solid', ['attrs' => ['style' => 'font-size: 12px;color: #6777ef;']]) !!}</div>
																<?php } ?>

																<div class="dropdown d-inline dropdown_ellipsis_icon">
																	<a class="dropdown-toggle" href="javascript:;" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">@icon('ellipsis-v')</a>
																	<div class="dropdown-menu">
																		@if(Auth::user()->role == 1)
																		<a data-id="{{$activit->id}}" data-href="deleteactivitylog" class="dropdown-item deleteactivitylog" href="javascript:;" >Delete</a>
																		@endif
																		<?php if($activit->pin == 1){ ?>
																			<a data-id="<?php echo $activit->id;?>"  class="dropdown-item pinactivitylog" href="javascript:;" >UnPin</a>
																		<?php
																		} else { ?>
																			<a data-id="<?php echo $activit->id;?>"  class="dropdown-item pinactivitylog" href="javascript:;" >Pin</a>
																		<?php } ?>
																	</div>
																</div>
															</div>
														</div>
                                                    </div>

													@if(!empty($activit->description))
                                                        @php
                                                            $description = $activit->description;
                                                            $subject = strtolower($activit->subject ?? '');
                                                            $actType = $activit->activity_type ?? '';

                                                            // Infer activity type for badge
                                                            $inferredType = 'other';
                                                            if (in_array($actType, ['receipt_created','receipt_validated','receipt_edited','receipt_reassigned','receipt_refunded','receipt_voided']) || strpos($description, 'action: receipt_') === 0) {
                                                                $inferredType = 'receipt';
                                                            } elseif ($actType === 'document' || preg_match('/\b(document|uploaded|verified|attached|detached)\b/i', $subject)) {
                                                                $inferredType = 'document';
                                                            } elseif ($actType === 'sms' || preg_match('/\b(sms|message sent|text)\b/i', $subject)) {
                                                                $inferredType = 'message';
                                                            } elseif (preg_match('/\b(added a note|updated a note|deleted a note)\b/i', $subject)) {
                                                                $inferredType = 'note';
                                                            } elseif (preg_match('/\b(sent a message)\b/i', $subject)) {
                                                                $inferredType = 'message';
                                                            } elseif (preg_match('/\b(call not picked|call)\b/i', $subject) || preg_match('/\bCall not picked\b/', $description)) {
                                                                $inferredType = 'call';
                                                            } elseif (preg_match('/\b(review|rated|rating)\b/i', $subject)) {
                                                                $inferredType = 'review';
                                                            } elseif (preg_match('/\b(reminder|checklist email|checklist resent|document checklist)\b/i', $subject)) {
                                                                $inferredType = 'reminder';
                                                            } elseif (preg_match('/\b(action|task|completed action|marked action)\b/i', $subject) || $activit->task_status == 1) {
                                                                $inferredType = 'action';
                                                            } elseif (preg_match('/\b(receipt|invoice|payment)\b/i', $subject)) {
                                                                $inferredType = 'accounting';
                                                            } elseif (preg_match('/\b(started an application)\b/i', $subject)) {
                                                                $inferredType = 'application';
                                                            } elseif (preg_match('/\b(interested service)\b/i', $subject)) {
                                                                $inferredType = 'service';
                                                            } elseif (preg_match('/\b(status|rated|rating)\b/i', $subject)) {
                                                                $inferredType = 'status';
                                                            } elseif (preg_match('/\b(check-in|session|commented|sheet comment)\b/i', $subject)) {
                                                                $inferredType = 'checkin';
                                                            }

                                                            $isReceiptActivity = in_array($actType, ['receipt_created','receipt_validated','receipt_edited','receipt_reassigned','receipt_refunded','receipt_voided']) || (strpos($description, 'action: receipt_') === 0);
                                                            $receiptFields = [];
                                                            $parsedAction = $actType;
                                                            if ($isReceiptActivity) {
                                                                $lines = preg_split('/\r?\n/', trim($description));
                                                                if (empty($parsedAction) && preg_match('/^action:\s*(\S+)/m', $description, $am)) {
                                                                    $parsedAction = $am[1];
                                                                }
                                                                $displayLabels = [
                                                                    'receipt_id' => 'Receipt ID', 'trans_no' => 'Trans. No', 'trans_date' => 'Trans. Date', 'entry_date' => 'Entry Date',
                                                                    'payment_method' => 'Payment Method', 'description' => 'Description', 'deposit_amount' => 'Amount', 'application_name' => 'Application',
                                                                    'document_attached' => 'Document', 'parent_receipt_id' => 'Parent Receipt', 'refund_reason' => 'Refund Reason', 'reassignment_reason' => 'Reassignment Reason',
                                                                ];
                                                                foreach ($lines as $line) {
                                                                    if (preg_match('/^(\w[\w_]+):\s*(.+)$/', trim($line), $m)) {
                                                                        $key = $m[1];
                                                                        $value = trim($m[2]);
                                                                        if (in_array($key, ['action', 'performed_at', 'performed_by', 'deposit_amount'])) continue;
                                                                        $label = $displayLabels[$key] ?? ucfirst(str_replace('_', ' ', $key));
                                                                        $receiptFields[] = ['label' => $label, 'value' => $value];
                                                                    }
                                                                }
                                                            }

                                                            $skipXml = (strpos($description, '<xml>') !== false || strpos($description, '<o:OfficeDocumentSettings>') !== false);
                                                        @endphp

                                                        @if($isReceiptActivity && count($receiptFields) > 0)
                                                            <div class="activity-content-card">
                                                                <div class="activity-type-badge activity-type-badge--{{ str_replace('receipt_', '', $parsedAction ?: 'created') }}">
                                                                    {{ ucfirst(str_replace(['receipt_', '_'], ['', ' '], $parsedAction ?: 'receipt')) }}
                                                                </div>
                                                                <div class="activity-receipt-grid">
                                                                    @foreach($receiptFields as $field)
                                                                        <div class="activity-receipt-field {{ ($field['label'] ?? '') === 'Amount' ? 'activity-receipt-field--amount' : '' }}">
                                                                            <span class="activity-receipt-label">{{ $field['label'] }}</span>
                                                                            <span class="activity-receipt-value">{{ $field['value'] }}</span>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @elseif($skipXml)
                                                            <div class="activity-content-card">
                                                                <div class="activity-type-badge activity-type-badge--other">Note</div>
                                                                <div class="activity-content-body"><p>{!! htmlentities($description) !!}</p></div>
                                                            </div>
                                                        @else
                                                            <div class="activity-content-card">
                                                                <div class="activity-type-badge activity-type-badge--{{ $inferredType }}">{{ ucfirst(str_replace('_', ' ', $inferredType)) }}</div>
                                                                <div class="activity-content-body">{!! \App\Helpers\Helper::normalizeActivityDescriptionHtml($description) !!}</div>
                                                            </div>
                                                        @endif
                                                    @endif

                                                    @if(isset($activit->task_status) && $activit->task_status == '1')
														<p style="color:#4caf50;"><b>Completed</b></p>
													@endif

                                                    @if($activit->followup_date != '')
														<p>{!!$activit->followup_date!!}</p>
													@endif

                                                    @if($activit->task_group != '')
														<p>{!!$activit->task_group!!}</p>
													@endif
												</div>
											</div>
											<?php
												}
											?>
									</div>
								</div>
								<div class="tab-pane fade {{ $activeTab === 'application' ? 'show active' : '' }}" id="application" role="tabpanel" aria-labelledby="application-tab">
									<div class="card-header-action text-end if_applicationdetail" style="padding-bottom:15px;">
										<a href="javascript:;" data-bs-toggle="modal" data-bs-target=".add_appliation" class="btn btn-primary">@icon('plus') Add</a>
									</div>
									<div class="table-responsive if_applicationdetail">
										<table class="table text_wrap table-2">
											<thead>
												<tr>
													<th>Name</th>
													<th>Workflow</th>
													<th>Current Stage</th>
													<th>Status</th>
													<th>Start Date</th>
													<th>End Date</th>
													<th>Enrolment Type</th>
													<th>Company Name</th>
												</tr>
											</thead>
											<tbody class="applicationtdata">
											<?php
											$application_data=\App\Models\Application::where('client_id', $fetchedData->id)->orderby('created_at','Desc')->get();
											if(count($application_data) > 0){
											foreach($application_data as $alist){
												$productdetail = \App\Models\Product::where('id', $alist->product_id)->first();
												$partnerdetail = \App\Models\Partner::where('id', $alist->partner_id)->first();
												$PartnerBranch = \App\Models\PartnerBranch::where('id', $alist->branch)->first();
												$workflow = \App\Models\Workflow::where('id', $alist->workflow)->first();

                                                $application_assign_count = \App\Models\Note::where('type','client')->whereNotNull('client_id')->where('is_action',1)->where('status',0)->where('application_id',$alist->id)->where('client_id',$fetchedData->id)->count();
                                                //dd($application_assign_count);
												?>
												<tr id="id_{{$alist->id}}">
													<td>
                                                      <a class="openapplicationdetail" data-id="{{$alist->id}}" href="{{ $detailBaseUrl }}/application/{{ $alist->id }}" style="display:block;">
                                                        {{@$productdetail->name}}

                                                        <?php if( $application_assign_count > 0 ) { ?>
                                                           <span class="countTotalActivityAction" style="background: #1f1655;padding: 0px 5px;border-radius: 50%;color: #fff;margin-left: 5px;">{{ $application_assign_count }}</span>
                                                        <?php } ?>
                                                      </a>
                                                      <small>{{@$partnerdetail->partner_name}} ({{@$PartnerBranch->name}})</small>
                                                    </td>
													<td>{{@$workflow->name}}</td>
													<td>{{@$alist->stage}}</td>
													<td>
                                                      @if(@$alist->status == 0)
                                                      <span class="ag-label--circular" style="color: #6777ef" >In Progress</span>
                                                      @elseif(@$alist->status == 1)
                                                      <span class="ag-label--circular" style="color: #6777ef" >Completed</span>
                                                      @elseif(@$alist->status == 2)
                                                      <span class="ag-label--circular" style="color: red;" >Discontinued</span>
                                                      @elseif(@$alist->status == 3)
                                                      <span class="ag-label--circular" style="color: red;" >Cancelled</span>
                                                      @elseif(@$alist->status == 4)
                                                      <span class="ag-label--circular" style="color: red;" >Withdrawn</span>
                                                      @elseif(@$alist->status == 5)
                                                      <span class="ag-label--circular" style="color: red;" >Deferred</span>
                                                      @elseif(@$alist->status == 6)
                                                      <span class="ag-label--circular" style="color: red;" >Future</span>
                                                      @elseif(@$alist->status == 7)
                                                      <span class="ag-label--circular" style="color: red;" >VOE</span>
                                                      @elseif(@$alist->status == 8)
                                                      <span class="ag-label--circular" style="color: red;" >Refund</span>
                                                      @endif
                                                    </td>

													<td><?php if(@$alist->start_date != ''){ echo date('d/m/Y', strtotime($alist->start_date)); } ?></td>
													<td><?php if(@$alist->end_date != ''){ echo date('d/m/Y', strtotime($alist->end_date)); } ?></td>
													<td>{{ \App\Models\Application::enrolmentTypeLabel($alist->enrolment_type ?? null) ?: '—' }}</td>
													<td>{{ \App\Models\Application::companyNameLabel($alist->company_name ?? null) ?: '—' }}</td>
												</tr>
												<?php
											}

											?>

											<?php
											}else{ ?>
												<tr>
													<td style="text-align:center;" colspan="8">
														No Record found
													</td>
												</tr>
									<?php	} ?>
											</tbody>
										</table>
									</div>
									<div class="ifapplicationdetailnot" style="display:none;">
										<h4>Please wait ...</h4>
									</div>
								</div>
                                      
                                 <div class="tab-pane fade {{ $activeTab === 'alldocuments' ? 'show active' : '' }}" id="alldocuments" role="tabpanel" aria-labelledby="alldocuments-tab">
                                    
                                    <!-- Document Category Tabs -->
                                    <div id="document-category-tabs" data-user-role="{{ Auth::user()->role }}" style="padding-bottom: 15px; border-bottom: 1px solid #e2e8f0; margin-bottom: 20px;">
                                        <!-- Categories will be loaded here via JavaScript -->
                                    </div>
                                    
                                    <div class="card-header-action text-end" style="padding-bottom:15px;">
                                        <a href="javascript:;" class="btn btn-primary add_alldocument_doc">@icon('plus') Add Checklist</a>
                                        <button type="button" class="btn btn-info bulk-upload-toggle-btn ms-2">@icon('upload') Bulk Upload</button>
                                    </div>
                                    
                                    <!-- Bulk Upload Dropzone (Hidden by default) -->
                                    <div class="bulk-upload-dropzone-container" id="bulk-upload-documents" style="display: none; margin-bottom: 20px; padding: 0 15px;">
                                        <div class="bulk-upload-dropzone" 
                                             style="border: 2px dashed #4a90e2; border-radius: 8px; padding: 40px; 
                                                    text-align: center; background-color: #f8f9fa; cursor: pointer;">
                                            {!! \App\Helpers\IconHelper::render('cloud-upload-alt', 'solid', ['attrs' => ['style' => 'font-size: 48px; color: #4a90e2; margin-bottom: 15px;']]) !!}
                                            <h4 style="color: #333; margin-bottom: 10px;">Drop files here or click to browse</h4>
                                            <p style="color: #666; margin-bottom: 0;">Supported: PDF, JPG, PNG, DOC, DOCX (Max 50MB per file)</p>
                                            <input type="file" class="bulk-upload-file-input" multiple style="display: none;" 
                                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                        </div>
                                        <div class="bulk-upload-file-list" style="display: none; margin-top: 15px; padding: 15px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px;">
                                            <strong style="color: #333;">Selected Files: <span class="file-count">0</span></strong>
                                            <div class="bulk-upload-files-container"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="list_data col-6 col-md-6 col-lg-6" style="display:inline-block;vertical-align: top;">
                                        <div class="">
                                            <table class="table text_wrap">
                                                <thead>
                                                    <tr>
                                                        <th>Checklist</th>
                                                        <th>File Name</th>
                                                        <!--<th>Verified By</th>-->
                                                    </tr>
                                                </thead>
                                                <tbody class="tdata alldocumnetlist">
                                                    <?php
                                                    $fetchd = \App\Models\Document::where('client_id',$fetchedData->id)->whereNull('not_used_doc')->where('doc_type', 'documents')->where('type','client')->orderby('updated_at', 'DESC')->get();
                                                    foreach($fetchd as $docKey=>$fetch)
                                                    {
                                                        $admin = \App\Models\Staff::find($fetch->user_id);
                                                        $addedByInfo = $admin->first_name . ' on ' . date('d/m/Y', strtotime($fetch->created_at));
                                                        //Checklist verified by
                                                        /*if( isset($fetch->checklist_verified_by) && $fetch->checklist_verified_by != "") {
                                                            $checklist_verified_Info = \App\Models\Staff::select('first_name')->find($fetch->checklist_verified_by) ?? \App\Models\Admin::select('first_name')->find($fetch->checklist_verified_by);
                                                            $checklist_verified_by = $checklist_verified_Info->first_name;
                                                        } else {
                                                            $checklist_verified_by = 'N/A';
                                                        }

                                                        if( isset($fetch->checklist_verified_at) && $fetch->checklist_verified_at != "") {
                                                            $checklist_verified_at = date('d/m/Y', strtotime($fetch->checklist_verified_at));
                                                        } else {
                                                            $checklist_verified_at = 'N/A';
                                                        }*/
                                                        ?>
                                                        <tr class="drow document-row" id="id_{{$fetch->id}}" 
                                                            data-doc-id="<?php echo $fetch->id;?>"
                                                            data-checklist-name="<?php echo htmlspecialchars($fetch->checklist, ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-file-name="<?php echo htmlspecialchars($fetch->file_name, ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-file-type="<?php echo htmlspecialchars($fetch->filetype, ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-myfile="<?php echo htmlspecialchars($fetch->myfile, ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-myfile-key="<?php echo isset($fetch->myfile_key) ? htmlspecialchars($fetch->myfile_key, ENT_QUOTES, 'UTF-8') : ''; ?>"
                                                            data-doc-type="<?php echo htmlspecialchars($fetch->doc_type, ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-signature-status="<?php echo htmlspecialchars($fetch->status ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-user-role="<?php echo Auth::user()->role; ?>"
                                                            title="Added by: <?php echo htmlspecialchars($addedByInfo, ENT_QUOTES, 'UTF-8'); ?>"
                                                            style="cursor: context-menu;">
                                                            <td style="white-space: initial;">
                                                                <div data-id="<?php echo $fetch->id;?>" data-personalchecklistname="<?php echo $fetch->checklist; ?>" class="personalchecklist-row">
                                                                    <span><?php echo $fetch->checklist; ?></span>
                                                                </div>
                                                            </td>
                                                            <td style="white-space: initial;">
                                                                <?php
                                                                if( isset($fetch->file_name) && $fetch->file_name !=""){ ?>
                                                                    <div data-id="{{$fetch->id}}" data-name="<?php echo $fetch->file_name; ?>" class="doc-row">
                                                                        <?php
                                                                        if (isset($fetch->myfile_key) && $fetch->myfile_key != '') {
                                                                            $inlinePreviewUrl = \App\Helpers\Helper::documentFileUrl($fetch->myfile);
                                                                        } else {
                                                                            $url = 'https://'.env('AWS_BUCKET').'.s3.'. env('AWS_DEFAULT_REGION') . '.amazonaws.com/';
                                                                            $inlinePreviewUrl = \App\Helpers\Helper::documentFileUrl($url.$fetchedData->client_id.'/'.$fetch->doc_type.'/'.$fetch->myfile);
                                                                        }
                                                                        ?>
                                                                            <a href="javascript:void(0);"
                                                                                data-preview-type="<?php echo htmlspecialchars($fetch->filetype, ENT_QUOTES, 'UTF-8'); ?>"
                                                                                data-preview-url="<?php echo htmlspecialchars($inlinePreviewUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                                                data-preview-container="preview-container-alldocumentlist">
                                                                                @icon('file-image') <span><?php echo $fetch->file_name . '.' . $fetch->filetype; ?></span>
                                                                            </a>
                                                                    </div>
                                                                <?php
                                                                }
                                                                else
                                                                {?>
                                                                    <div class="allupload_document" style="display:inline-block;">
                                                                        <form method="POST" enctype="multipart/form-data" id="upload_form_<?php echo $fetch->id;?>">
                                                                            @csrf
                                                                            <input type="hidden" name="clientid" value="{{$fetchedData->id}}">
                                                                            <input type="hidden" name="fileid" value="{{$fetch->id}}">
                                                                            <input type="hidden" name="type" value="client">
                                                                            <input type="hidden" name="doctype" value="documents">
                                                                            <a href="javascript:;" class="btn btn-primary">@icon('plus') Add Document</a>
                                                                            <input class="alldocupload" data-fileid="<?php echo $fetch->id;?>" type="file" name="document_upload"/>
                                                                        </form>
                                                                    </div>
                                                                <?php
                                                                }?>
                                                            </td>
                                                            <!--<td id="docverifiedby_<?php //echo $fetch->id;?>">
                                                                <?php
                                                                //echo $checklist_verified_by. "<br>";
                                                                //echo $checklist_verified_at;
                                                                ?>
                                                            </td>-->
                                                        </tr>
                                                        <?php
                                                        // Render action bar for docs in signature flow (placed/sent/viewed)
                                                        if (!empty($fetch->file_name) && !$fetch->source_document_id && in_array($fetch->status ?? '', ['signature_placed', 'sent', 'viewed'])):
                                                            $sigSent = in_array($fetch->status, ['sent', 'viewed']);
                                                        ?>
                                                        <tr class="document-signature-action-bar" data-doc-id="<?php echo $fetch->id; ?>">
                                                            <td colspan="2" class="py-2" style="background:#f8f9fa;border-left:3px solid #0d6efd;">
                                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                                    <span class="text-muted small me-2">Signature:</span>
                                                                    <button type="button" class="btn btn-sm btn-primary document-sig-send" <?php if($sigSent): ?>disabled<?php endif; ?> title="Send for signature">
                                                                        @icon('paper-plane') Send
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary document-sig-revise" title="Revise placement">
                                                                        @icon('edit') Revise
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger document-sig-remove" title="Remove">
                                                                        @icon('times') Remove
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-warning document-sig-reminder" title="Send reminder" <?php if(!$sigSent): ?>style="display:none;"<?php endif; ?>>
                                                                        @icon('bell') Reminder
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php endif; ?>
                                                    <?php
                                                    } //end foreach?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="grid_data allgriddata">
                                        <?php
                                        foreach($fetchd as $fetch)
                                        {
                                            $admin = \App\Models\Staff::find($fetch->user_id);
                                            ?>
                                            <div class="grid_list" id="gid_<?php echo $fetch->id; ?>">
                                                <div class="grid_col">
                                                    <div class="grid_icon">
                                                        @icon('file-image')
                                                    </div>
                                                    <?php
                                                    if( isset($fetch->myfile) && $fetch->myfile != "")
                                                    { ?>
                                                        <div class="grid_content">
                                                            <span id="grid_<?php echo $fetch->id; ?>" class="gridfilename"><?php echo $fetch->file_name; ?></span>
                                                            <div class="dropdown d-inline dropdown_ellipsis_icon">
                                                                <a class="dropdown-toggle" href="javascript:;" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">@icon('ellipsis-v')</a>
                                                                <div class="dropdown-menu">
                                                                    <?php $url = 'https://'.env('AWS_BUCKET').'.s3.'. env('AWS_DEFAULT_REGION') . '.amazonaws.com/';?>

                                                                    <?php
                                                                        $suggestedGridDl = \App\Http\Controllers\Admin\Client\ClientDocumentController::buildClientDocumentDownloadFilename(
                                                                            (string) ($fetch->file_name ?? 'document'),
                                                                            (string) ($fetch->filetype ?? '')
                                                                        );
                                                                    ?>
                                                                    <?php if( isset($fetch->myfile_key) && $fetch->myfile_key != ""){ //For new file upload ?>
                                                                        <a target="_blank" class="dropdown-item" href="<?php echo $fetch->myfile; ?>">Preview</a>
                                                                        <a href="<?php echo url('/download-document') . '?filelink=' . urlencode($fetch->myfile) . '&filename=' . urlencode($suggestedGridDl); ?>" class="dropdown-item download-file" data-filelink="<?php echo htmlspecialchars($fetch->myfile, ENT_QUOTES, 'UTF-8'); ?>" data-filename="<?php echo htmlspecialchars($suggestedGridDl, ENT_QUOTES, 'UTF-8'); ?>" data-dl-base="<?php echo htmlspecialchars((string) ($fetch->file_name ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-dl-ext="<?php echo htmlspecialchars((string) ($fetch->filetype ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Download</a>
                                                                    <?php } else {  //For old file upload
                                                                        $myawsfile = $url.$fetchedData->client_id.'/'.$fetch->doc_type.'/'.$fetch->myfile;
                                                                    ?>
                                                                        <a target="_blank" class="dropdown-item" href="<?php echo $myawsfile; ?>">Preview</a>
                                                                        <a href="<?php echo url('/download-document') . '?filelink=' . urlencode($myawsfile) . '&filename=' . urlencode($suggestedGridDl); ?>" class="dropdown-item download-file" data-filelink="<?php echo htmlspecialchars($myawsfile, ENT_QUOTES, 'UTF-8'); ?>" data-filename="<?php echo htmlspecialchars($suggestedGridDl, ENT_QUOTES, 'UTF-8'); ?>" data-dl-base="<?php echo htmlspecialchars((string) ($fetch->file_name ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-dl-ext="<?php echo htmlspecialchars((string) ($fetch->filetype ?? ''), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Download</a>
                                                                    <?php } ?>

                                                                    <?php if( Auth::user()->role == 1 ){ //super admin ?>
                                                                    <a data-id="{{$fetch->id}}" class="dropdown-item deletenote" data-href="deletealldocs" href="javascript:;">Delete</a>
                                                                    <?php } ?>
                                                                    <a data-id="{{$fetch->id}}" class="dropdown-item verifydoc" data-doctype="documents" data-href="verifydoc" href="javascript:;">Verify</a>
                                                                    <a data-id="{{$fetch->id}}" class="dropdown-item notuseddoc" data-doctype="documents" data-href="notuseddoc" href="javascript:;">Not Used</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php
                                                    }?>
                                                </div>
                                            </div>
                                        <?php
                                        } //end foreach ?>
                                        <div class="clearfix"></div>
                                    </div>
                                   
                                    <!-- Container for File Preview -->
                                    <div style="margin-left: 10px;" class="col-5 col-md-5 col-lg-5 file-preview-container preview-container-alldocumentlist">
                                        <p style="color:#000;">Click on a file to preview it here.</p>
                                    </div>
                                    
                                    <!-- Bulk Upload Mapping Modal -->
                                    <div id="bulk-upload-mapping-modal" class="bulk-upload-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; overflow-y: auto; padding: 20px 0;">
                                        <div class="bulk-upload-modal-content">
                                            <div style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                                                <h3 style="margin: 0; color: #333;">Map Files to Checklists</h3>
                                                <button type="button" class="close-mapping-modal" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
                                            </div>
                                            <div style="padding: 20px; overflow-x: auto;">
                                                <div id="bulk-upload-mapping-table"></div>
                                            </div>
                                            <div style="padding: 20px; border-top: 1px solid #e2e8f0;">
                                                <div class="bulk-upload-progress" id="bulk-upload-progress" style="display: none; margin-bottom: 15px;">
                                                    <div style="background: #e2e8f0; border-radius: 4px; overflow: hidden; height: 30px;">
                                                        <div class="progress-bar" id="bulk-upload-progress-bar" 
                                                             style="background: #4a90e2; height: 100%; color: white; display: flex; 
                                                                    align-items: center; justify-content: center; font-weight: bold; 
                                                                    transition: width 0.3s; width: 0%;">0%</div>
                                                    </div>
                                                </div>
                                                <div style="text-align: right;">
                                                    <button type="button" class="btn btn-secondary" id="cancel-bulk-upload">Cancel</button>
                                                    <button type="button" class="btn btn-primary" id="confirm-bulk-upload" style="margin-left: 10px;">Upload All</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade {{ $activeTab === 'notuseddocuments' ? 'show active' : '' }}" id="notuseddocuments" role="tabpanel" aria-labelledby="notuseddocuments-tab">
									<!--<div class="card-header-action text-end" style="padding-bottom:15px;">
										<div class="document_layout_type">
											<a href="javascript:;" class="list active">@icon('list')</a>
											<a href="javascript:;" class="grid">@icon('columns')</a>
										</div>
                                    </div>-->
									<div class="list_data col-6 col-md-6 col-lg-6" style="display:inline-block;vertical-align: top;">
										<div class="">
											<table class="table text_wrap">
												<thead>
													<tr>
                                                        <!--<th>SNo.</th>-->
                                                        <th>Checklist</th>
                                                        <th>Added By</th>
                                                        <th>File Name</th>
                                                        <!--<th>Verified By</th>-->
                                                        <th></th>
													</tr>
												</thead>
												<tbody class="tdata notuseddocumnetlist">
                                                    <?php
                                                    //$fetchd = \App\Models\Document::where('client_id',$fetchedData->id)->where('not_used_doc', 1)->where('doc_type', 'personal')->where('type','client')->orderby('updated_at', 'DESC')->get();
                                                    $fetchd = \App\Models\Document::where('client_id', $fetchedData->id)
                                                    ->where('not_used_doc', 1)
                                                    ->where('type','client')
                                                    ->whereIn('doc_type', ['documents', 'education', 'migration'])
                                                    ->orderBy('updated_at', 'DESC')->get();
                                                    //dd($fetchd);
                                                    foreach($fetchd as $notuseKey=>$fetch)
                                                    {
                                                        $admin = \App\Models\Staff::find($fetch->user_id);
                                                        //Checklist verified by
                                                        /*if( isset($fetch->checklist_verified_by) && $fetch->checklist_verified_by != "") {
                                                            $checklist_verified_Info = \App\Models\Staff::select('first_name')->find($fetch->checklist_verified_by) ?? \App\Models\Admin::select('first_name')->find($fetch->checklist_verified_by);
                                                            $checklist_verified_by = $checklist_verified_Info->first_name;
                                                        } else {
                                                            $checklist_verified_by = 'N/A';
                                                        }

                                                        if( isset($fetch->checklist_verified_at) && $fetch->checklist_verified_at != "") {
                                                            $checklist_verified_at = date('d/m/Y', strtotime($fetch->checklist_verified_at));
                                                        } else {
                                                            $checklist_verified_at = 'N/A';
                                                        }*/
                                                        ?>
                                                        <tr class="drow" id="id_{{$fetch->id}}">
                                                            <!--<td><?php //echo $notuseKey+1;?></td>-->
                                                            <td style="white-space: initial;"><?php echo $fetch->checklist; ?></td>
                                                            <td style="white-space: initial;">
                                                                <?php
                                                                    echo $admin->first_name. "<br>";
                                                                    echo date('d/m/Y', strtotime($fetch->created_at));
                                                                ?>
                                                            </td>
                                                            <td style="white-space: initial;">
                                                                <?php if( isset($fetch->file_name) && $fetch->file_name !=""){ ?>
                                                                    <div data-id="{{$fetch->id}}" data-name="<?php echo $fetch->file_name; ?>" class="doc-row">
                                                                        <?php
                                                                        if (isset($fetch->myfile_key) && $fetch->myfile_key != '') {
                                                                            $inlinePreviewUrl = \App\Helpers\Helper::documentFileUrl($fetch->myfile);
                                                                        } else {
                                                                            $url = 'https://'.env('AWS_BUCKET').'.s3.'. env('AWS_DEFAULT_REGION') . '.amazonaws.com/';
                                                                            $inlinePreviewUrl = \App\Helpers\Helper::documentFileUrl($url.$fetchedData->client_id.'/'.$fetch->doc_type.'/'.$fetch->myfile);
                                                                        }
                                                                        ?>
                                                                            <a href="javascript:void(0);"
                                                                                data-preview-type="<?php echo htmlspecialchars($fetch->filetype, ENT_QUOTES, 'UTF-8'); ?>"
                                                                                data-preview-url="<?php echo htmlspecialchars($inlinePreviewUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                                                data-preview-container="preview-container-notuseddocumentlist">
                                                                                @icon('file-image') <span><?php echo $fetch->file_name . '.' . $fetch->filetype; ?></span>
                                                                            </a>
                                                                    </div>
                                                                <?php
                                                                }
                                                                else
                                                                {
                                                                    echo "N/A";
                                                                }?>
                                                            </td>
                                                            <!--<td id="docverifiedby_<?php //echo $fetch->id;?>">
                                                                <?php
                                                                //echo $checklist_verified_by. "<br>";
                                                                //echo $checklist_verified_at;
                                                                ?>
                                                            </td>-->
                                                            <td>
                                                                <div class="dropdown d-inline">
                                                                    <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
                                                                    <div class="dropdown-menu">
                                                                        <?php
                                                                        $url = 'https://'.env('AWS_BUCKET').'.s3.'. env('AWS_DEFAULT_REGION') . '.amazonaws.com/';
                                                                        ?>
                                                                        <?php if( isset($fetch->myfile_key) && $fetch->myfile_key != ""){ //For new file upload ?>
                                                                            <a target="_blank" class="dropdown-item" href="<?php echo $fetch->myfile; ?>">Preview</a>
                                                                        <?php } else {  //For old file upload ?>
                                                                            <a target="_blank" class="dropdown-item" href="<?php echo $url.$fetchedData->client_id.'/'.$fetch->doc_type.'/'.$fetch->myfile; ?>">Preview</a>
                                                                        <?php } ?>

                                                                        <a data-id="{{$fetch->id}}" class="dropdown-item backtodoc" data-doctype="documents" data-href="backtodoc" href="javascript:;">Back To Document</a>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
												    <?php
                                                    } //end foreach?>
												</tbody>
                                            </table>
										</div>
									</div>
                                  
                                    <!-- Container for File Preview -->
                                    <div class="col-5 col-md-5 col-lg-5 file-preview-container preview-container-notuseddocumentlist">
                                        <p style="color:#000;">Click on a file to preview it here.</p>
                                    </div>
                                </div>



								{{-- Appointments tab removed - Appointment model deleted --}}
                                
								<div class="tab-pane fade {{ $activeTab === 'noteterm' ? 'show active' : '' }}" id="noteterm" role="tabpanel" aria-labelledby="noteterm-tab">
									<div class="card-header-action text-end" style="padding-bottom:15px;">

									</div>
									<div class="note_term_list">
									@php
									$notelist = \App\Models\Note::where('client_id', $fetchedData->id)->whereNull('assigned_to')->whereNull('task_group')->where('type', 'client')->orderby('pin', 'DESC')->orderByRaw('created_at DESC NULLS LAST')->get();
									@endphp
									@include('Admin.partials.notes-list', ['notelist' => $notelist])
									</div>
									<div class="clearfix"></div>
								</div>
								<div class="tab-pane fade {{ $activeTab === 'accounts' ? 'show active' : '' }}" id="accounts" role="tabpanel" aria-labelledby="accounts-tab">
									<div class="row">
										<div class="col-md-12 text-end">

                                            <a class="btn btn-primary createclientreceipt" href="javascript:;" role="button"  style="margin-right:5px !important;">Create Client Receipt</a>


											<div class="cus_invice_btn dropdown d-inline">
												<a href="#" data-bs-toggle="dropdown" class="nav-link nav-link-lg message-toggle btn btn-outline-primary">Create Invoice @icon('angle-down')</a>
												<div class="dropdown-menu">
													<a href="javascript:;" class="dropdown-item opencommissioninvoice">
														Commission Invoice
													</a>
													<a href="javascript:;" class="dropdown-item opengeneralinvoice">
														General Invoice
													</a>
												</div>
											</div>
										</div>
										<div class="clearfix"></div>
									</div>
									<div class="table-responsive">
                                        <table border="1" style="margin-bottom:0rem !important;" class="table text_wrap table-striped table-hover table-md vertical_align">
                                            <caption>Client Receipts</caption>
                                            <thead>
                                                <tr>
                                                    <th>Trans. Date</th>
                                                    <th>Entry Date</th>
                                                    <th>Trans. No</th>
                                                    <th>Payment Method</th>
                                                    <th>Description</th>
                                                    <th>Deposit</th>
                                                </tr>
                                            </thead>
                                            <tbody class="productitemList">
                                                <?php
                                                $receipts_lists = DB::table('account_client_receipts')
                                                    ->where('client_id',$fetchedData->id)
                                                    ->whereIn('receipt_type',[1,2])
                                                    ->where(function($q){ $q->where('void_invoice',0)->orWhereNull('void_invoice'); })
                                                    ->orderBy('created_at','desc')
                                                    ->get();
                                                //dd($receipts_lists);
                                                if(!empty($receipts_lists) && count($receipts_lists)>0 )
                                                {
                                                    $total_deposit_amount = 0.00;
                                                    foreach($receipts_lists as $rec_list=>$rec_val)
                                                    {

                                                ?>
                                                <tr  id="TrRow_<?php echo $rec_val->id;?>">
                                                    <td>
                                                        <?php echo $rec_val->trans_date;?>

                                                        <?php
                                                        if(isset($rec_val->uploaded_doc_id) && $rec_val->uploaded_doc_id >0){
                                                            $client_info = DB::table('admins')->select('id','client_id')->where('id',$rec_val->client_id)->first();

                                                        	$client_doc_list = DB::table('documents')->select('id','myfile','client_id','doc_type','myfile_key')->where('id',$rec_val->uploaded_doc_id)->first();
                                                            if($client_doc_list){
                                                                if( isset($client_doc_list->myfile_key) && $client_doc_list->myfile_key != "") {
                                                                    $awsUrl = $client_doc_list->myfile;
                                                                } else { 
                                                                    $url = 'https://'.env('AWS_BUCKET').'.s3.'. env('AWS_DEFAULT_REGION') . '.amazonaws.com/';
                                                                    $awsUrl = $url.$client_info->client_id.'/'.$client_doc_list->doc_type.'/'.$client_doc_list->myfile; 
                                                                }
                                                                ?>
                                                                <a target="_blank" class="link-primary client-receipt-action" href="<?php echo $awsUrl;?>">@icon('file-pdf')</a>
                                                            <?php
                                                            }
                                                        } ?>
                                                    </td>
                                                    <td><?php echo $rec_val->entry_date;?></td>
                                                    <td><?php echo $rec_val->trans_no;?></td>
                                                    <td><?php echo $rec_val->payment_method;?></td>
                                                    <td>
                                                        <?php echo $rec_val->description;?>
                                                        <?php if($rec_val->receipt_type == 2 && !empty($rec_val->refund_reason)): ?>
                                                            <br><small class="text-muted">Reason: <?php echo e($rec_val->refund_reason); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $isRefund = ($rec_val->receipt_type == 2);
                                                        ?>
                                                        <span class="<?php echo $isRefund ? 'text-danger' : ''; ?>">$<?php echo $rec_val->deposit_amount; ?></span>
                                                        <?php if($isRefund): ?><span class="badge bg-secondary">Refund</span><?php endif; ?>
                                                        <a target="_blank" class="link-primary client-receipt-action" href="{{URL::to('/clients/printpreview')}}/{{$rec_val->id}}" title="Print receipt">{!! \App\Helpers\IconHelper::render('print') !!}</a>
                                                       <?php
                                                        if($rec_val->receipt_type == 1){
                                                            // Validated receipts must not be editable; print/refund stay available
                                                            if((int)($rec_val->validate_receipt ?? 0) !== 1){
                                                        ?>
                                              			<a class="link-primary client-receipt-action updateclientreceipt" href="javascript:;" data-id="<?php echo $rec_val->id;?>" title="Edit receipt">
                                                          @icon('pencil-alt')
                                                        </a>
                                                       <?php
                                                            }
                                                        ?>
                                                        <a class="link-primary client-receipt-action createclientrefund" href="javascript:;" data-id="<?php echo $rec_val->id;?>" data-trans-no="<?php echo e($rec_val->trans_no);?>" data-amount="<?php echo $rec_val->deposit_amount;?>" data-application-id="<?php echo $rec_val->application_id ?? '';?>" title="Create Refund">
                                                          @icon('undo')
                                                        </a>
                                                       <?php
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?php
                                                    $total_deposit_amount += $rec_val->deposit_amount;
                                                } //end foreach
                                                ?>

                                                <tr class="lastRow">
                                                    <td colspan="5" style="text-align:right;">Totals</td>
                                                    <td class="totDepoAmTillNow"><?php echo "$".$total_deposit_amount;?></td>
                                                </tr>
                                            <?php } else { ?>
                                                <!--<tr class="norecord"><td colspan="5">No Record Found</td></tr>-->
                                                <tr class="lastRow">
                                                    <td colspan="5" style="text-align:right;">Totals</td>
                                                    <td class="totDepoAmTillNow"><?php echo "$0";?></td>
                                                </tr>
                                            <?php } ?>
                                            </tbody>
                                        </table>
                                        <br/>

                                        <br/>

										<table class="table invoicetable text_wrap">
											<thead>
												<tr>
													<th>Invoice No.</th>
													<th>Issue Date</th>
													<th class="invoice-service-col">Service</th>
													<th>Invoice Amount</th>
													<th>Discount Given</th>
													<th>Income Shared</th>
													<th>Status</th>
													<th></th>
												</tr>
											</thead>
											<tbody class="tdata invoicedatalist">
												<?php
												$invoicelists = \App\Models\Invoice::where('client_id',$fetchedData->id)->orderby('created_at','DESC')->get();
												foreach($invoicelists as $invoicelist){
													if($invoicelist->type == 3){
														$workflowdaa = \App\Models\Workflow::where('id', $invoicelist->application_id)->first();
													}else{
														$applicationdata = \App\Models\Application::where('id', $invoicelist->application_id)->first();
														$workflowdaa = \App\Models\Workflow::where('id', $invoicelist->application_id)->first();
														$partnerdata = \App\Models\Partner::where('id', @$applicationdata->partner_id)->first();
													}
													$invoiceitemdetails = \App\Models\InvoiceDetail::where('invoice_id', $invoicelist->id)->orderby('id','ASC')->get();
													$netamount = 0;
													$coom_amt = 0;
													$total_fee = 0;
													foreach($invoiceitemdetails as $invoiceitemdetail){
														$netamount += $invoiceitemdetail->netamount;
														$coom_amt += $invoiceitemdetail->comm_amt;
														$total_fee += $invoiceitemdetail->total_fee;
													}

													$paymentdetails = \App\Models\InvoicePayment::where('invoice_id', $invoicelist->id)->orderby('created_at', 'DESC')->get();
													$amount_rec = 0;
													foreach($paymentdetails as $paymentdetail){
														$amount_rec += $paymentdetail->amount_rec;
													}
													// INV-1: same due rules as invoicepaymentstore
													$totaldue = \App\Models\Invoice::computeOutstandingDue(
														$invoicelist->type,
														$total_fee,
														$coom_amt,
														$netamount,
														$amount_rec
													);


												?>
												<tr id="iid_{{$invoicelist->id}}">
													<td>{{$invoicelist->id}}</td>
													<td>{{$invoicelist->invoice_date}}
													<?php if($invoicelist->type == 1){
														$rtype = 'Net Claim';
													}else if($invoicelist->type == 2){
														$rtype = 'Gross Claim';
													}else{
														$rtype = 'General';
													} ?>
													<span title="{{$rtype}}" class="badge bg-warning text-dark zippyLabel">{{$rtype}}</span></td>
													<td class="invoice-service-col">{{@$workflowdaa->name}}<br>{{@$partnerdata->partner_name}}</td>
													<td>AUD {{$invoicelist->net_fee_rec}}</td>
													<td>{{$invoicelist->discount}}</td>
													<td>-</td>
													<td>
													@if($invoicelist->status == 1)
														<span class="ag-label--circular" style="color: #6777ef" >Paid</span></td>
													@else
														<span class="ag-label--circular" style="color: #ed5a5a" >UnPaid</span></td>
													@endif
													<td>
														<div class="dropdown d-inline">
															<button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
															<div class="dropdown-menu">
																<a class="dropdown-item has-icon" href="#">Send Email</a>
																<a target="_blank" class="dropdown-item has-icon" href="{{URL::to('invoice/view/')}}/{{$invoicelist->id}}">View</a>
																<?php if($invoicelist->status == 0){ ?>
																<a target="_blank" class="dropdown-item has-icon" href="{{URL::to('invoice/edit/')}}/{{$invoicelist->id}}">Edit</a>
																<a data-netamount="{{$netamount}}" data-dueamount="{{$totaldue}}" data-invoiceid="{{$invoicelist->id}}" class="dropdown-item has-icon addpaymentmodal" href="javascript:;"> Make Payment</a>
																<?php } ?>
															</div>
														</div>
													</td>
												</tr>
												<?php } ?>
											</tbody>
										</table>
									</div>
								</div>
							<div class="tab-pane fade {{ $activeTab === 'email-v2' ? 'show active' : '' }}" id="email-v2" role="tabpanel" aria-labelledby="email-v2-tab">
									@include('Admin.clients.tabs.emails_v2')
								</div>
							<!--<div class="tab-pane fade" id="other_info" role="tabpanel" aria-labelledby="other_info-tab">
								<span>other_info</span>
							</div>-->
						</div> <!-- end tab-content -->
					</div> <!-- end card-body -->
				</div> <!-- end card -->
			</div> <!-- end right_section -->
		</div> <!-- end bottom-row-container -->
	</div> <!-- end client-detail-container -->
	</div> <!-- end section-body -->
</section>
</div> <!-- end main-content -->

@include('Admin/clients/addclientmodal')
@include('Admin/clients/editclientmodal')

<div id="emailmodal"  data-backdrop="static" data-keyboard="false" class="modal fade custom_modal compose-email-compact" tabindex="-1" role="dialog" aria-labelledby="clientModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header compose-modal-header">
				<h5 class="modal-title" id="clientModalLabel">Compose Email</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body compose-modal-body">
				<form method="post" name="sendmail" action="{{URL::to('/sendmail')}}" autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="client_id" value="{{ $fetchedData->id ?? '' }}">
				<input type="hidden" name="type" value="{{ $fetchedData->type ?? 'client' }}">
				<input type="hidden" name="application_id" id="sendmail_application_id" value="">
				<input type="hidden" name="send_context" id="sendmail_send_context" value="">
				<input type="hidden" name="compose_email_category" id="compose_email_category" value="">
					<div class="row compose-row-tight">
						<div class="col-12 col-md-6">
							<div class="form-group form-group-compact">
								<label for="email_from">From <span class="span_req">*</span></label>
								@include('partials.email-from-ses')
								@if ($errors->has('email_from'))
									<span class="custom-error" role="alert">
										<strong>{{ @$errors->first('email_from') }}</strong>
									</span>
								@endif
							</div>
						</div>
						<div class="col-12 col-md-6">
							<div class="form-group form-group-compact">
								<label for="email_to">To <span class="span_req">*</span></label>
								<select data-valid="required" class="js-data-example-ajax" name="email_to[]"></select>

								@if ($errors->has('email_to'))
									<span class="custom-error" role="alert">
										<strong>{{ @$errors->first('email_to') }}</strong>
									</span>
								@endif
							</div>
						</div>
						<div class="col-12">
							<div class="form-group form-group-compact">
								<label for="template">Templates</label>
                                 <?php
                                $assignee = \App\Support\StaffAssigneeResolver::firstStaffFromAssigneeValue($fetchedData->assignee ?? null);
                                $clientAssigneeName = $assignee ? $assignee->first_name : 'NA';
								$clientDob = (!empty($fetchedData->dob) && $fetchedData->dob != '0000-00-00') ? date('d/m/Y', strtotime($fetchedData->dob)) : '';
								?>
								<select id="template" data-valid="" class="form-control form-control-sm tomselect selecttemplate" name="template" data-clientid="{{@$fetchedData->id}}" data-clientfirstname="{{@$fetchedData->first_name}}" data-clientvisaExpiry="{{@$fetchedData->visaExpiry}}" data-clientreference_number="{{@$fetchedData->client_id}}" data-clientassignee_name="{{@$clientAssigneeName}}" data-clientdob="{{@$clientDob}}">
									<option value="">Select</option>
									@foreach(\App\Models\CrmEmailTemplate::orderBy('id', 'desc')->get() as $list)
										<option value="{{$list->id}}">{{$list->name}}</option>
									@endforeach
								</select>
							</div>
						</div>
						<div class="col-12">
							<div class="form-group form-group-compact">
								<label for="subject">Subject <span class="span_req">*</span></label>
								{!! Form::text('subject', '', array('id'=>'compose_email_subject','class' => 'form-control form-control-sm selectedsubject', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Enter Subject' ))  !!}
								@if ($errors->has('subject'))
									<span class="custom-error" role="alert">
										<strong>{{ @$errors->first('subject') }}</strong>
									</span>
								@endif
							</div>
						</div>
						<div class="col-12">
							<div class="form-group form-group-compact compose-message-wrapper">
								<label for="message">Message <span class="span_req">*</span></label>
								<div class="compose-message-container">
									<textarea class="tinymce-simple selectedmessage" id="compose_email_message" name="message"></textarea>
									<button type="button" class="btn btn-sm btn-outline-info compose-enhance-floating" id="composeMessageEnhanceBtn" title="AI: Fix and improve message content">@icon('magic') Enhance</button>
								</div>
								@if ($errors->has('message'))
									<span class="custom-error" role="alert">
										<strong>{{ @$errors->first('message') }}</strong>
									</span>
								@endif
							</div>
						</div>
						<div class="col-12">
							<div class="form-group form-group-compact compose-labels-section compose-labels-compact">
								<label class="d-inline">Labels</label>
								<div class="compose-labels-display">
									<span class="compose-label-badge compose-label-sent" title="All sent emails are automatically tagged">@icon('paper-plane') Sent</span>
									<div id="composeAdditionalLabelsChips" class="compose-label-chips"></div>
									<div class="compose-add-label-wrapper dropdown">
										<button type="button" class="btn btn-outline-secondary btn-sm compose-add-label-btn" id="composeAddLabelBtn" data-bs-toggle="dropdown" aria-expanded="false">
											@icon('plus') Add
										</button>
										<ul class="dropdown-menu compose-label-dropdown" id="composeLabelDropdown" aria-labelledby="composeAddLabelBtn">
											<!-- Populated by JS when labels are loaded -->
										</ul>
									</div>
								</div>
								<div id="composeLabelIdsContainer"><!-- Hidden inputs for label_ids[] added by JS --></div>
							</div>
						</div>
					<div class="col-12">
					     <div class="form-group form-group-compact compose-attach-row">
					        <label class="d-inline">Attachment</label>
					        {{-- Hidden file input — triggered by button; files managed in JS array --}}
					        <input type="file" id="composeAttachFileInput" class="d-none" multiple>
					        <button type="button" class="btn btn-outline-secondary btn-sm ms-2" id="composeAttachAddBtn">
					            @icon('paperclip') Add Files
					        </button>
					        <button type="button" class="btn btn-link btn-sm p-0 ms-2 compose-attach-toggle" data-bs-toggle="collapse" data-bs-target="#composeChecklistDocuments" aria-expanded="false" aria-controls="composeChecklistDocuments">
					            <span>▼ Checklist / Documents</span>
					        </button>
					     </div>
					     <div id="composeAttachFileList" class="compose-attach-file-list mt-1"></div>
					</div>
                      
                         <div class="col-12 collapse" id="composeChecklistDocuments">
                            <div class="composeemail-tab">
                                <ul class="nav nav-pills round_tabs" id="composeemails-tab" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="pill" id="composechecklist-tab" href="#composechecklist" role="tab" aria-controls="composechecklist" aria-selected="true">Checklist</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-bs-toggle="pill" id="composedocument-tab" href="#composedocument" role="tab" aria-controls="composedocument" aria-selected="false">Document List</a>
                                    </li>
                                </ul>

                                <div class="tab-content" id="composeemailContent">
                                    <div class="tab-pane fade show active" id="composechecklist" role="tabpanel" aria-labelledby="composechecklist-tab">
                                        <div class="table-responsive uploadchecklists">

                                            <table id="mychecklist-datatable" class="table text_wrap table-2 table-sm">
                                                <thead>
                                                    <tr>
                                                        <th></th>
                                                        <th style="white-space: initial;">File Name</th>
                                                        <th style="white-space: initial;">File</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach(\App\Models\UploadChecklist::all() as $uclist)
                                                    <tr>
                                                        <td><input type="checkbox" name="checklistfile[]" value="{{$uclist->id}}" {{ old('checklistfile') && in_array($uclist->id, old('checklistfile', [])) ? 'checked' : '' }}></td>
                                                        <td style="white-space: initial;">{{$uclist->name}}</td>
                                                        <td style="white-space: initial;"><a target="_blank" href="{{ asset('checklists/'.$uclist->file) }}">{{$uclist->name}}</a></td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="composedocument" role="tabpanel" aria-labelledby="composedocument-tab">
                                        <table id="mydocumentlist-datatable" class="table text_wrap table-2 table-sm">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th style="white-space: initial;">File Name</th>
                                                    <th>Document Type</th>
                                                    <th style="white-space: initial;">File</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(\App\Models\Document::with('category')->where('client_id',$fetchedData->id)->where('type','client')->whereIn('doc_type', ['education', 'migration', 'documents'])->whereNull('not_used_doc')->orderby('created_at', 'DESC')->get() as $composedoclist)
                                                <tr>
                                                    <td><input type="checkbox" name="checklistfile_document[]" value="{{$composedoclist->id}}" {{ old('checklistfile_document') && in_array($composedoclist->id, old('checklistfile_document', [])) ? 'checked' : '' }}></td>
                                                    <td style="white-space: initial;">{{$composedoclist->file_name}}</td>
                                                    <td>
                                                        <?php
                                                        $docTypes = [
                                                            'education' => 'Education',
                                                            'migration' => 'Migration',
                                                            'documents' => 'Document'
                                                        ];
                                                        // Show Education/Migration by category for migrated docs (doc_type is now 'documents')
                                                        $displayType = 'N/A';
                                                        if ($composedoclist->category && in_array($composedoclist->category->name, ['Education', 'Migration'])) {
                                                            $displayType = $composedoclist->category->name;
                                                        } elseif (isset($composedoclist->doc_type) && isset($docTypes[$composedoclist->doc_type])) {
                                                            $displayType = $docTypes[$composedoclist->doc_type];
                                                        }
                                                        echo $displayType;
                                                        ?>
                                                    </td>

                                                    <td style="white-space: initial;">
                                                        <?php
                                                        // Dual-read: prefer storage reality (URL/key) over doc_type labels
                                                        $composeMyfile = trim((string) ($composedoclist->myfile ?? ''));
                                                        $composeIsRemote = (! empty($composedoclist->myfile_key))
                                                            || preg_match('#^https?://#i', $composeMyfile);
                                                        if ($composeMyfile === '') {
                                                            // no file
                                                        } elseif ($composeIsRemote) {
                                                            $composeHref = \App\Helpers\Helper::documentFileUrl($composeMyfile);
                                                            ?>
                                                            <a target="_blank" href="<?php echo e($composeHref); ?>">{{ $composedoclist->file_name }}</a>
                                                            <?php
                                                        } else {
                                                            // Bare filename: keep pre-S3 education/migration local paths,
                                                            // and legacy documents basename → reconstructed public S3 URL
                                                            $useLocalPath = ($composedoclist->doc_type == 'education' || $composedoclist->doc_type == 'migration')
                                                                || ($composedoclist->doc_type == 'documents' && $composedoclist->category && in_array($composedoclist->category->name, ['Education', 'Migration']));
                                                            if ($useLocalPath) { ?>
                                                                <a target="_blank" class="dropdown-item" href="{{ asset('img/documents') }}/{{ $composeMyfile }}">{{ $composedoclist->file_name }}</a>
                                                            <?php
                                                            } elseif (($composedoclist->doc_type ?? '') === 'documents') {
                                                                $clientInfo = \App\Models\Admin::where('id', $fetchedData->id)->select('client_id')->first();
                                                                $client_unique_id = $clientInfo && ! empty($clientInfo->client_id) ? $clientInfo->client_id : 'N/A';
                                                                $url = 'https://'.env('AWS_BUCKET').'.s3.'. env('AWS_DEFAULT_REGION') . '.amazonaws.com/';
                                                                $composedoclistUrl = $url.$client_unique_id.'/'.$composedoclist->doc_type.'/'.$composeMyfile;
                                                                ?>
                                                                <a target="_blank" href="<?php echo e($composedoclistUrl); ?>"><?php echo e($composedoclist->file_name); ?></a>
                                                            <?php
                                                            } else { ?>
                                                                <a target="_blank" class="dropdown-item" href="{{ asset('img/documents') }}/{{ $composeMyfile }}">{{ $composedoclist->file_name }}</a>
                                                            <?php
                                                            }
                                                        } ?>
                                                    </td>

                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
						
						<div class="col-12 compose-modal-footer">
							<button onclick="customValidate('sendmail')" type="button" class="btn btn-primary btn-sm">Send</button>
							<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>


@include('Admin.clients.partials.schedule-followup-modal')

@php
	$smsClientId = (int) ($fetchedData->id ?? 0);
	$smsContext = ['application_id' => request()->query('applicationId')];
	$smsTemplateVarMap = $smsClientId > 0
		? \App\Services\Sms\SmsTemplateVariableResolver::placeholderReplacements($smsClientId, $smsContext)
		: [];
@endphp

<!-- Send SMS Modal -->
<div id="sendSmsModal" data-backdrop="static" data-keyboard="false"
	data-page-client-id="{{ $fetchedData->id ?? '' }}"
	data-page-client-name="{{ e($smsTemplateVarMap['{client_name}'] ?? 'there') }}"
	data-page-client-first-name="{{ e($smsTemplateVarMap['{first_name}'] ?? 'there') }}"
	data-page-client-last-name="{{ e($smsTemplateVarMap['{last_name}'] ?? '') }}"
	data-page-student-name="{{ e($smsTemplateVarMap['{Student_Name}'] ?? 'Client') }}"
	class="modal fade custom_modal" tabindex="-1" role="dialog" aria-labelledby="sendSmsModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="sendSmsModalLabel">Send SMS</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form id="sendSmsForm" autocomplete="off">
					@csrf
					<input type="hidden" name="client_id" id="sendSms_client_id" value="">
					<input type="hidden" name="application_id" id="sendSms_application_id" value="">
					<div class="row">
						<div class="col-12 col-md-6">
							<div class="form-group">
								<label for="sendSms_phone">Phone <span class="span_req">*</span></label>
								<select class="form-control" id="sendSms_phone" name="phone" required>
									<option value="">Select phone...</option>
								</select>
							</div>
						</div>
						<div class="col-12 col-md-6">
							<div class="form-group">
								<label for="sendSms_template_id">Template (optional)</label>
								<select class="form-control" id="sendSms_template_id" name="template_id">
									<option value="">Select template...</option>
								</select>
							</div>
						</div>
					</div>
					<div class="form-group">
						<label for="sendSms_message">Message <span class="span_req">*</span></label>
						<textarea class="form-control" id="sendSms_message" name="message" rows="8" required placeholder="Enter your message..."></textarea>
						<small class="form-text text-muted"><span id="sendSms_charCount">0</span>/1600 characters</small>
					</div>
					<div class="form-group">
						<button type="submit" class="btn btn-primary" id="sendSms_submitBtn">@icon('paper-plane') Send SMS</button>
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- Lead phone verification OTP modal -->
<div id="leadVerifyOtpModal" data-backdrop="static" data-keyboard="false" class="modal fade custom_modal" tabindex="-1" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Verify Lead Phone</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<input type="hidden" id="lead_verify_otp_lead_id" value="">
				<div id="leadVerifyOtpSendSection">
					<p class="text-muted">Send a verification code to this phone.</p>
					<button type="button" class="btn btn-primary" id="leadVerifyOtpSendBtn">@icon('paper-plane') Send Code</button>
				</div>
				<div id="leadVerifyOtpCodeSection" style="display: none;">
					<label>Enter 6-digit code</label>
					<div class="input-group mb-2">
						<input type="text" class="form-control" id="lead_verify_otp_code" placeholder="000000" maxlength="6">
						<button type="button" class="btn btn-primary" id="leadVerifyOtpVerifyBtn">Verify</button>
					</div>
					<button type="button" class="btn btn-sm btn-link" id="leadVerifyOtpResendBtn" disabled>Resend code (30s)</button>
				</div>
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

<div id="confirmNotUseDocModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="false" class="modal fade" >
	<div class="modal-dialog">
		<div class="modal-content popUp">
			<div class="modal-body text-center">
				<button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn-close"></button>
				<h4 class="modal-title text-center message col-v-5">Do you want to send this document in Not Use Tab?</h4>
				<button type="submit" style="margin-top: 40px;" class="button btn btn-danger accept">Send</button>
				<button type="button" style="margin-top: 40px;" data-bs-dismiss="modal" class="button btn btn-secondary cancel">Cancel</button>
			</div>
		</div>
	</div>
</div>

<div id="confirmBackToDocModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="false" class="modal fade" >
	<div class="modal-dialog">
		<div class="modal-content popUp">
			<div class="modal-body text-center">
				<button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn-close"></button>
				<h4 class="modal-title text-center message col-v-5">Do you want to send this in document Tab again?</h4>
				<button type="submit" style="margin-top: 40px;" class="button btn btn-danger accept">Send</button>
				<button type="button" style="margin-top: 40px;" data-bs-dismiss="modal" class="button btn btn-secondary cancel">Cancel</button>
			</div>
		</div>
	</div>
</div>

<div id="confirmDocModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="false" class="modal fade" >
	<div class="modal-dialog">
		<div class="modal-content popUp">
			<div class="modal-body text-center">
				<button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn-close"></button>
				<h4 class="modal-title text-center message col-v-5">Do you want to verify this doc?</h4>
				<button type="submit" style="margin-top: 40px;" class="button btn btn-danger accept">Verify</button>
				<button type="button" style="margin-top: 40px;" data-bs-dismiss="modal" class="button btn btn-secondary cancel">Cancel</button>
			</div>
		</div>
	</div>
</div>

<div id="confirmLogModal" tabindex="-1" role="dialog" aria-labelledby="confirmLogModalLabel" aria-hidden="false" class="modal fade" >
	<div class="modal-dialog">
		<div class="modal-content popUp">
			<div class="modal-body text-center">
				<button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn-close"></button>
				<h4 class="modal-title text-center message col-v-5">Do you want to delete this log?</h4>
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


<div id="confirmpublishdocModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="false" class="modal fade" >
	<div class="modal-dialog">
		<div class="modal-content popUp">
			<div class="modal-body text-center">
				<button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn-close"></button>
				<h4 class="modal-title text-center message col-v-5">Publish Document?</h4>
				<h5 class="">Are you sure you want to publish this document?</h5>
				<button type="submit" style="margin-top: 40px;" class="button btn btn-danger acceptpublishdoc">Publish Anyway</button>
				<button type="button" style="margin-top: 40px;" data-bs-dismiss="modal" class="button btn btn-secondary cancel">Cancel</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade custom_modal" id="application_opensaleforcast" tabindex="-1" role="dialog" aria-labelledby="applicationModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="appliationModalLabel">Sales Forecast</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/application/saleforcast')}}" name="saleforcast" id="saleforcast" autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="fapp_id" id="fapp_id" value="">
					<div class="row">
						<div class="col-4 col-md-4 col-lg-4">
							<div class="form-group">
								<label for="sus_agent">Client Revenue</label>
								<input type="number" value="0.00" max="100" min="0" step="0.01" class="form-control " id="client_revenue" name="client_revenue">
								<span class="custom-error workflow_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-4 col-md-4 col-lg-4">
							<div class="form-group">
								<label for="sus_agent">Partner Revenue</label>
								<input type="number" value="0.00" max="100" min="0" step="0.01" class="form-control " id="partner_revenue" name="partner_revenue">
								<span class="custom-error workflow_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-4 col-md-4 col-lg-4">
							<div class="form-group">
								<label for="sus_agent">Discounts</label>
								<input type="number" value="0.00" max="100" min="0" step="0.01" class="form-control " id="discounts" name="discounts">
								<span class="custom-error workflow_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('saleforcast')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<div class="modal fade custom_modal" id="application_ownership" tabindex="-1" role="dialog" aria-labelledby="applicationModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="appliationModalLabel">Application Ownership Ratio</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/application/application_ownership')}}" name="xapplication_ownership" id="xapplication_ownership" autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="mapp_id" id="mapp_id" value="">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="sus_agent"> </label>
								<input type="number" max="100" min="0" step="0.01" class="form-control ration" name="ratio">
								<span class="custom-error workflow_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>

						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('xapplication_ownership')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<div class="modal fade custom_modal" id="superagent_application" tabindex="-1" role="dialog" aria-labelledby="applicationModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="appliationModalLabel">Select Super Agent</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/application/spagent_application')}}" name="spagent_application" id="spagent_application" autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="siapp_id" id="siapp_id" value="">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="super_agent">Super Agent <span class="span_req">*</span></label>
								<select data-valid="required" class="form-control super_agent" id="super_agent" name="super_agent">
									<option value="">Please Select</option>
									<?php $sagents = \App\Models\Agent::whereRaw('? = ANY(string_to_array(agent_type, \',\'))', ['Super Agent'])->get(); ?>
									@foreach($sagents as $sa)
										<option value="{{$sa->id}}">{{$sa->full_name}} {{$sa->email}}</option>
									@endforeach
								</select>
								<span class="custom-error workflow_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>

						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('spagent_application')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<div class="modal fade custom_modal" id="subagent_application" tabindex="-1" role="dialog" aria-labelledby="applicationModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="appliationModalLabel">Select Sub Agent</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/application/sbagent_application')}}" name="sbagent_application" id="sbagent_application" autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="sbapp_id" id="sbapp_id" value="">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="sub_agent">Sub Agent <span class="span_req">*</span></label>
								<select data-valid="required" class="form-control sub_agent" id="sub_agent" name="sub_agent">
									<option value="">Please Select</option>
									<?php $sagents = \App\Models\Agent::whereRaw('? = ANY(string_to_array(agent_type, \',\'))', ['Sub Agent'])->where('is_acrchived',0)->get(); ?>
									@foreach($sagents as $sa)
										<option value="{{$sa->id}}">{{$sa->full_name}} {{$sa->email}}</option>
									@endforeach
								</select>
								<span class="custom-error workflow_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>

						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('sbagent_application')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<div class="modal fade custom_modal" id="tags_clients" tabindex="-1" role="dialog" aria-labelledby="applicationModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="appliationModalLabel">Tags</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/save_tag')}}" name="stags_application" id="stags_application" autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="client_id" id="tags_client_id" value="">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="tagname_input">Tags <span class="span_req">*</span></label>
								<input
									type="text"
									id="tagname_input"
									name="tagname"
									class="form-control"
									data-valid="required"
									placeholder="e.g. VIP, Follow up, IELTS"
									value="{{ trim($fetchedData->tagname ?? '') }}"
								/>
								<small class="form-text text-muted">Separate tags with commas.</small>
                            </div>
						</div>

						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('stags_application')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<div class="modal fade custom_modal" id="new_fee_option" tabindex="-1" role="dialog" aria-labelledby="feeoptionModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="feeoptionModalLabel">Fee Option</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body showproductfee">

			</div>
		</div>
	</div>
</div>

<div class="modal fade custom_modal" id="new_fee_option_latest" tabindex="-1" role="dialog" aria-labelledby="feeoptionModalLabelLatest" aria-hidden="true">
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="feeoptionModalLabelLatest">Other Fee Option</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body showproductfee_latest">

			</div>
		</div>
	</div>
</div>

<div class="modal fade custom_modal" id="application_opensaleforcast" tabindex="-1" role="dialog" aria-labelledby="applicationModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="appliationModalLabel">Sales Forecast</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/application/saleforcast')}}" name="saleforcast" id="saleforcast" autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="fapp_id" id="fapp_id" value="">
					<div class="row">
						<div class="col-4 col-md-4 col-lg-4">
							<div class="form-group">
								<label for="sus_agent">Client Revenue</label>
								<input type="number" value="0.00" max="100" min="0" step="0.01" class="form-control " id="client_revenue" name="client_revenue">
								<span class="custom-error workflow_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-4 col-md-4 col-lg-4">
							<div class="form-group">
								<label for="sus_agent">Partner Revenue</label>
								<input type="number" value="0.00" max="100" min="0" step="0.01" class="form-control " id="partner_revenue" name="partner_revenue">
								<span class="custom-error workflow_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-4 col-md-4 col-lg-4">
							<div class="form-group">
								<label for="sus_agent">Discounts</label>
								<input type="number" value="0.00" max="100" min="0" step="0.01" class="form-control " id="discounts" name="discounts">
								<span class="custom-error workflow_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('saleforcast')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<div class="modal fade custom_modal" id="application_opensaleforcastservice" tabindex="-1" role="dialog" aria-labelledby="applicationModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="appliationModalLabel">Sales Forecast</h5>
				<button type="button" class="close closeservmodal" >
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form method="post" action="{{URL::to('/application/saleforcastservice')}}" name="saleforcastservice" id="saleforcastservice" autocomplete="off" enctype="multipart/form-data">
				@csrf
				<input type="hidden" name="fapp_id" id="fapp_id" value="">
					<div class="row">
						<div class="col-4 col-md-4 col-lg-4">
							<div class="form-group">
								<label for="sus_agent">Client Revenue</label>
								<input type="number" value="0.00" max="100" min="0" step="0.01" class="form-control " id="client_revenue" name="client_revenue">
								<span class="custom-error workflow_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-4 col-md-4 col-lg-4">
							<div class="form-group">
								<label for="sus_agent">Partner Revenue</label>
								<input type="number" value="0.00" max="100" min="0" step="0.01" class="form-control " id="partner_revenue" name="partner_revenue">
								<span class="custom-error workflow_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-4 col-md-4 col-lg-4">
							<div class="form-group">
								<label for="sus_agent">Discounts</label>
								<input type="number" value="0.00" max="100" min="0" step="0.01" class="form-control " id="discounts" name="discounts">
								<span class="custom-error workflow_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('saleforcastservice')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<div class="modal fade custom_modal" id="serviceTaken" tabindex="-1" role="dialog" aria-labelledby="create_interestModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="interestModalLabel">Service Taken</h5>

				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
                <form method="post" action="{{URL::to('/client/createservicetaken')}}" name="createservicetaken" id="createservicetaken" autocomplete="off" enctype="multipart/form-data">
				@csrf
                    <input id="logged_client_id" name="logged_client_id"  type="hidden" value="<?php echo $fetchedData->id;?>">
					<div class="row">
						<div class="col-12 col-md-12 col-lg-12">

							<div class="form-group">
								<label style="display:block;" for="service_type">Select Service Type:</label>
								<div class="form-check form-check-inline">
									<input class="form-check-input" type="radio" id="Migration_inv" value="Migration" name="service_type" checked>
									<label class="form-check-label" for="Migration_inv">Migration</label>
								</div>
								<div class="form-check form-check-inline">
									<input class="form-check-input" type="radio" id="Eductaion_inv" value="Eductaion" name="service_type">
									<label class="form-check-label" for="Eductaion_inv">Eductaion</label>
								</div>
								<span class="custom-error service_type_error" role="alert">
									<strong></strong>
								</span>
							</div>
						</div>

						<div class="col-12 col-md-12 col-lg-12 is_Migration_inv">
                            <div class="form-group">
								<label for="mig_ref_no">Reference No: <span class="span_req">*</span></label>
                                <input type="text" name="mig_ref_no" id="mig_ref_no" value="" class="form-control" data-valid="required">
                            </div>

                            <div class="form-group">
								<label for="mig_service">Service: <span class="span_req">*</span></label>
                                <input type="text" name="mig_service" id="mig_service" value="" class="form-control" data-valid="required">
                            </div>

                            <div class="form-group">
								<label for="mig_notes">Notes: <span class="span_req">*</span></label>
                                <input type="text" name="mig_notes" id="mig_notes" value="" class="form-control" data-valid="required">
                            </div>
                        </div>

                        <div class="col-12 col-md-12 col-lg-12 is_Eductaion_inv" style="display:none;">
                            <div class="form-group">
								<label for="edu_course">Course: <span class="span_req">*</span></label>
                                <input type="text" name="edu_course" id="edu_course" value="" class="form-control">
                            </div>

                            <div class="form-group">
								<label for="edu_college">College: <span class="span_req">*</span></label>
                                <input type="text" name="edu_college" id="edu_college" value="" class="form-control">
                            </div>

                            <div class="form-group">
								<label for="edu_service_start_date">Service Start Date: <span class="span_req">*</span></label>
                                <input type="text" name="edu_service_start_date" id="edu_service_start_date" value="" class="form-control">
                            </div>

                            <div class="form-group">
								<label for="edu_notes">Notes: <span class="span_req">*</span></label>
                                <input type="text" name="edu_notes" id="edu_notes" value="" class="form-control">
                            </div>
                        </div>

                        <div class="col-12 col-md-12 col-lg-12">
							<button onclick="customValidate('createservicetaken')" type="button" class="btn btn-primary">Save</button>
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

{{-- Application Change Assignee Modal (moved here from applicationdetail.blade.php to avoid AJAX loading issues) --}}
<div class="modal fade" id="applicationChangeAssigneeModal" tabindex="-1" aria-labelledby="applicationChangeAssigneeModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="applicationChangeAssigneeModalLabel">Change assignee</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<input type="hidden" id="application_assignee_app_id" value="">
				<div class="mb-3">
					<label for="application_assignee_select" class="form-label">Assignee</label>
					<select class="form-control" id="application_assignee_select">
						<option value="">Select assignee</option>
						@foreach(\App\Models\Staff::where('status', 1)->orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']) as $a)
							<option value="{{ $a->id }}">{{ trim(($a->first_name ?? '') . ' ' . ($a->last_name ?? '')) }}</option>
						@endforeach
					</select>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
				<button type="button" class="btn btn-primary" id="application_assignee_save">Save</button>
			</div>
		</div>
	</div>
</div>

@if($showGoogleReviewReminderModal ?? false)
<div class="modal fade google-review-reminder-modal" id="googleReviewReminderModal" tabindex="-1" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="googleReviewReminderModalLabel" aria-describedby="googleReviewReminderModalDesc googleReviewReminderModalHint" data-bs-backdrop="static" data-bs-keyboard="false">
	<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
		<div class="modal-content grr-modal-content">
			<div class="modal-header grr-modal-header">
				<h5 class="modal-title grr-modal-title" id="googleReviewReminderModalLabel">@icon('google', 'brands', ['class' => 'grr-modal-google-icon me-2']) Google review reminder</h5>
				<button type="button" class="btn-close grr-modal-close js-google-review-reminder" data-action="snooze_one_day" aria-label="Close and remind again tomorrow" title="Close — ask again tomorrow"></button>
			</div>
			<div class="modal-body grr-modal-body">
				<p class="grr-modal-lead" id="googleReviewReminderModalDesc">Has this contact been asked to leave a Google review? Choose an option so we know whether to remind you next time you open their record.</p>
				<p class="mb-0 grr-modal-hint" id="googleReviewReminderModalHint">Using the <strong>close</strong> button above will hide this reminder until tomorrow (one-day snooze).</p>
			</div>
			<div class="modal-footer flex-column align-items-stretch gap-2 grr-modal-footer">
				<button type="button" class="btn w-100 m-0 js-google-review-send-sms grr-btn grr-btn-sms">
					@icon('sms', 'solid', ['class' => 'me-1']) Send SMS with review link
				</button>
				<div class="d-flex flex-wrap gap-2 justify-content-stretch grr-modal-actions-row">
					<button type="button" class="btn flex-grow-1 m-0 js-google-review-reminder grr-btn grr-btn-neutral" data-action="not_interested">Not interested</button>
					<button type="button" class="btn flex-grow-1 m-0 js-google-review-reminder grr-btn grr-btn-neutral" data-action="snooze">Remind me in 1 week</button>
					<button type="button" class="btn flex-grow-1 m-0 js-google-review-reminder grr-btn grr-btn-done" data-action="review_received">Review received</button>
				</div>
			</div>
		</div>
	</div>
</div>
@endif

@endsection
@section('scripts')

@push('scripts')
	{{-- @stack('scripts') renders before @yield in admin layout; AppConfig inline below runs during parse before this defer module executes --}}
	@vite(['resources/js/pages/admin/client-detail-entry.js'])
@endpush

{{-- Configuration Script: Pass Blade variables to JavaScript --}}
<script>
    window.AppConfig = window.AppConfig || {};
    window.PageConfig = window.PageConfig || {};
    
    // Global Configuration
    AppConfig.csrf = '{{ csrf_token() }}';
    AppConfig.siteUrl = '{{ url("/") }}';
    AppConfig.urls = {
        siteUrl: '{{ url("/") }}',
        downloadDocument: '{{ url("/download-document") }}',
        previewDocument: '{{ url("/preview-document") }}',
        documentPreviewView: '{{ url("/document-preview-view") }}',
        deleteAction: '{{ url("/delete_action") }}',
        getActivities: '{{ url("/get-activities") }}',
        getNotes: '{{ url("/get-notes") }}',
        deleteActivityLog: '{{ url("/deleteactivitylog") }}',
        mailEnhance: '{{ route("clients.enhanceMessage") }}',
        clientGetTopReceipt: '{{ url("/clients/getTopReceiptValInDB") }}',
        notPickedCall: '{{ url("/not-picked-call") }}',
        getDateTimeBackend: '{{ url("/getdatetimebackend") }}',
        getDisabledDateTime: '{{ url("/getdisableddatetime") }}',
        clientUpdateEmailVerified: '{{ url("/clients/update-email-verified") }}',
        clientChangeAssignee: '{{ url("/clients/change_assignee") }}',
        clientAction: '{{ url("/clients/action/store") }}',
        scheduleFollowup: '{{ route("clients.schedule-followup.store") }}',
        scheduleFollowupSlots: '{{ route("clients.schedule-followup.slots") }}',
        pinNote: '{{ url("/pinnote") }}',
        pinActivityLog: '{{ url("/pinactivitylog") }}',
        getNoteDetail: '{{ url("/getnotedetail") }}',
        viewNoteDetail: '{{ url("/viewnotedetail") }}',
        viewApplicationNote: '{{ url("/viewapplicationnote") }}',
        getPartnerBranch: '{{ url("/getpartnerbranch") }}',
        getBranchProduct: '{{ url("/getbranchproduct") }}',
        getPartner: '{{ url("/getpartner") }}',
        getProduct: '{{ url("/getproduct") }}',
        clientGetRecipients: '{{ url("/clients/get-recipients") }}',
        getTemplates: '{{ url("/get-templates") }}',
        uploadDocument: '{{ url("/upload-document") }}',
        uploadAllDocument: '{{ url("/upload-alldocument") }}',
        documentsAutoChecklistMatches: '{{ route("clients.documents.getAutoChecklistMatches") }}',
        documentsBulkUpload: '{{ route("clients.documents.bulkUpload") }}',
        getApplicationLists: '{{ url("/get-application-lists") }}',
        renameDoc: '{{ url("/renamedoc") }}',
        renameAllDoc: '{{ url("/renamealldoc") }}',
        renameChecklistDoc: '{{ url("/renamechecklistdoc") }}',
        getBranch: '{{ url("/getbranch") }}',
        clientUpdateSession: '{{ url("/clients/update-session-completed") }}',
        clientFetchContact: '{{ url("/clients/fetchClientContactNo") }}',
        sendMail: '{{ url("/sendmail") }}',
        clientGetReceiptInfo: '{{ url("/clients/getClientReceiptInfoById") }}',
        // NOTE: addScheduleInvoiceDetail removed - Invoice Schedule feature has been removed
        applicationChecklistUpload: '{{ url("/application/checklistupload") }}',
        getApplicationsLogs: '{{ url("/get-applications-logs") }}',
        getApplicationDetail: '{{ url("/getapplicationdetail") }}',
        updateApplicationEnrolmentType: '{{ url("/application/update-enrolment-type") }}',
        updateApplicationCompanyName: '{{ url("/application/update-company-name") }}',
        getApplicationNotes: '{{ url("/getapplicationnotes") }}',
        updateApplicationDates: '{{ url("/application/updatedates") }}',
        showProductFee: '{{ url("/showproductfee") }}',
        showProductFeeLatest: '{{ url("/showproductfeelatest") }}',
        changeApplicationAssignee: '{{ route("application.change-assignee") }}'
    };
    
    // Page-Specific Configuration
    PageConfig.clientId = {{ $fetchedData->id ?? 'null' }};
    PageConfig.clientName = {!! json_encode(trim(($fetchedData->first_name ?? '').' '.($fetchedData->last_name ?? '')) ?: 'Client') !!};
    PageConfig.clientEmail = {!! json_encode($fetchedData->email ?? '') !!};
    PageConfig.clientType = 'client';
    PageConfig.canDeleteActivityLog = {{ Auth::user()->role == 1 ? 'true' : 'false' }};
    PageConfig.canEditApplicationEnrolmentCompanyFields = {{ \App\Models\Application::canUpdateEnrolmentAndCompanyFields() ? 'true' : 'false' }};
</script>

@if($showGoogleReviewReminderModal ?? false)
<script>
$(function () {
    var $modal = $('#googleReviewReminderModal');
    if (!$modal.length) { return; }
    var clientId = parseInt(PageConfig.clientId, 10);
    if (!clientId || clientId < 1) { return; }
    var token = $('meta[name="csrf-token"]').attr('content') || '';
    var postUrl = @json(route('clients.google-review-reminder'));
    var postSmsUrl = @json(route('clients.google-review-reminder.sms'));
    var submitting = false;
    var $smsBtn = $modal.find('.js-google-review-send-sms');
    var smsBtnOriginalHtml = $smsBtn.html();

    function grrAllControls() {
        return $modal.find('.js-google-review-reminder, .js-google-review-send-sms');
    }

    function grrToast(type, msg) {
        if (typeof iziToast !== 'undefined') {
            iziToast[type]({ message: msg, position: 'topRight' });
        }
    }

    /* ---- Auto-open after configurable delay ---- */
    var reminderDelayMs = @json((int) config('crm.google_review_reminder_modal_delay_ms', 60000));
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        reminderDelayMs = Math.min(reminderDelayMs, 400);
    }
    var grrShowTimer = setTimeout(function () { $modal.modal('show'); }, reminderDelayMs);
    $(window).on('pagehide.grr', function () { clearTimeout(grrShowTimer); });

    /* ---- Focus first action button on open ---- */
    $modal.on('shown.bs.modal', function () {
        var $first = $smsBtn.length ? $smsBtn : $modal.find('.js-google-review-reminder').first();
        $first.trigger('focus');
    });

    /* ---- Status actions (snooze / not-interested / review-received / close×) ---- */
    $modal.off('click.grr', '.js-google-review-reminder').on('click.grr', '.js-google-review-reminder', function () {
        if (submitting) { return; }
        var action = $(this).data('action');
        var $btns = grrAllControls();
        submitting = true;
        $btns.prop('disabled', true);
        $.ajax({
            url: postUrl,
            type: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            data: { client_id: clientId, action: action },
            success: function (res) {
                if (res && res.ok) {
                    $modal.modal('hide');
                    var msgs = {
                        snooze_one_day: 'Reminder snoozed until tomorrow',
                        snooze: 'Reminder snoozed for 1 week',
                        not_interested: 'Noted — won\'t be reminded again',
                        review_received: 'Great! Review marked as received'
                    };
                    grrToast('success', msgs[action] || 'Saved');
                } else {
                    grrToast('error', (res && res.message) ? res.message : 'Could not save. Please try again.');
                }
            },
            error: function (xhr) {
                var msg = 'Could not save. Please try again.';
                var j = xhr.responseJSON;
                if (j && j.message) { msg = j.message; }
                else if (j && j.errors) {
                    var keys = Object.keys(j.errors);
                    if (keys.length && j.errors[keys[0]] && j.errors[keys[0]][0]) { msg = j.errors[keys[0]][0]; }
                }
                grrToast('error', msg);
            },
            complete: function () {
                submitting = false;
                $btns.prop('disabled', false);
            }
        });
    });

    /* ---- Send SMS ---- */
    $modal.off('click.grr-sms', '.js-google-review-send-sms').on('click.grr-sms', '.js-google-review-send-sms', function () {
        if (submitting) { return; }
        var $btns = grrAllControls();
        submitting = true;
        $btns.prop('disabled', true);
        $smsBtn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Sending…');
        $.ajax({
            url: postSmsUrl,
            type: 'POST',
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            data: { client_id: clientId },
            success: function (res) {
                if (res && res.ok) {
                    $modal.modal('hide');
                    grrToast('success', res.message || 'Review link sent by SMS');
                    if (typeof getallactivities === 'function') { getallactivities(); }
                } else {
                    grrToast('error', (res && res.message) ? res.message : 'SMS failed. Please try again.');
                }
            },
            error: function (xhr) {
                var msg = 'SMS failed. Please try again.';
                var j = xhr.responseJSON;
                if (j && j.message) { msg = j.message; }
                grrToast('error', msg);
            },
            complete: function () {
                submitting = false;
                $btns.prop('disabled', false);
                $smsBtn.html(smsBtnOriginalHtml);
            }
        });
    });
});
</script>
@endif

@if($showAlert)
<script>
(function () {
    var msg = "Have u updated the following details - email address,current address,current visa,visa expiry,other fields? Pls update these details before forwarding this to anyone?";
    function showAlertToast() {
        if (typeof window.toastMsg === 'function') {
            window.toastMsg(msg, 'warning');
        } else {
            alert(msg);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', showAlertToast);
    } else {
        showAlertToast();
    }
})();
</script>
@endif

{{-- Initialize Document Category Manager --}}
<script>
$(document).ready(function() {
    // Initialize Document Category Manager when Documents tab is active or clicked
    const initCategoryManager = function() {
        if (typeof window.DocumentCategoryManager !== 'undefined') {
            window.DocumentCategoryManager.init({{ $fetchedData->id ?? 'null' }});
        }
    };
    
    // Check if Documents tab is active on page load
    if ($('#alldocuments-tab').hasClass('active')) {
        initCategoryManager();
    }
    
    // Initialize when Documents tab is clicked
    $('#alldocuments-tab').on('shown.bs.tab', function() {
        initCategoryManager();
    });
});
</script>

{{-- Open email/SMS modal from sheet: ?open_checklist_email=1|open_email_reminder=1|open_sms_reminder=1&applicationId=xxx --}}
<script>
(function(){
	var params = new URLSearchParams(window.location.search);
	var openChecklist = params.get('open_checklist_email');
	var openEmailReminder = params.get('open_email_reminder');
	var openSmsReminder = params.get('open_sms_reminder');
	var applicationId = params.get('applicationId');
	var clientId = {{ $fetchedData->id ?? 'null' }};
	var clientEmail = {!! json_encode($fetchedData->email ?? '') !!};
	var clientName = {!! json_encode(trim(($fetchedData->first_name ?? '').' '.($fetchedData->last_name ?? '')) ?: 'Client') !!};

	$(document).ready(function(){
		function runChecklistModal(attempts) {
			attempts = attempts || 0;
			if (typeof RecipientSelect === 'undefined') {
				if (attempts < 40) {
					setTimeout(function() { runChecklistModal(attempts + 1); }, 50);
				}
				return;
			}
			if (openChecklist === '1' && applicationId && $('#emailmodal').length) {
				$('#sendmail_application_id').val(applicationId);
				$('#sendmail_send_context').val('checklist');
				var data = [RecipientSelect.buildEntry(clientId, clientName, clientEmail, 'Client')];
				if (typeof window.scheduleComposeEmailRecipients === 'function') {
					window.scheduleComposeEmailRecipients(data);
				} else {
					$('#emailmodal').data('composeRecipientsPending', data);
				}
				$('#composeChecklistDocuments').collapse('show');
				var checklistTabEl = document.getElementById('composechecklist-tab');
				if (checklistTabEl && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
					bootstrap.Tab.getOrCreateInstance(checklistTabEl).show();
				}
				$('#emailmodal').modal('show');
			} else if (openEmailReminder === '1' && applicationId && $('#emailmodal').length) {
				$('#sendmail_application_id').val(applicationId);
				$('#sendmail_send_context').val('email_reminder');
				var data = [RecipientSelect.buildEntry(clientId, clientName, clientEmail, 'Client')];
				if (typeof window.scheduleComposeEmailRecipients === 'function') {
					window.scheduleComposeEmailRecipients(data);
				} else {
					$('#emailmodal').data('composeRecipientsPending', data);
				}
				$('#emailmodal').modal('show');
			}
		}
		runChecklistModal(0);
	});
})();
</script>

{{-- Send SMS modal: open button, load phones/templates, submit --}}
<script>
window.sendSmsTemplateReplacements = @json($smsTemplateVarMap);
</script>
<script>
$(document).ready(function(){
	var sendSmsSendUrl = '{{ route("adminconsole.features.sms.send") }}';
	var sendSmsTemplatesActiveUrl = '{{ route("adminconsole.features.sms.templates.active") }}';
	var fetchClientContactNoUrl = '{{ route("clients.fetchClientContactNo") }}';

	// Define helper functions first
	function loadSendSmsPhones(clientId) {
		console.log('loadSendSmsPhones called with clientId:', clientId);
		if (!clientId) {
			console.log('No clientId provided');
			return;
		}
		$.ajax({
			url: fetchClientContactNoUrl,
			method: 'POST',
			dataType: 'json',
			data: { _token: $('input[name="_token"]').val(), client_id: clientId },
			success: function(res){
				console.log('Phone response:', res);
				var sel = $('#sendSms_phone');
				sel.find('option:not(:first)').remove();
				if (res.status && res.clientContacts && res.clientContacts.length) {
					console.log('Adding ' + res.clientContacts.length + ' phone options');
					$.each(res.clientContacts, function(i, c){
						var cc = c.client_country_code || '';
						var ph = c.client_phone || '';
						var full = (cc + ph).replace(/\s/g,'');
						var label = (c.contact_type || 'Phone') + ': ' + (cc + ' ' + ph).trim();
						console.log('Adding phone option:', label, full);
						sel.append('<option value="' + full + '">' + label + '</option>');
					});
				} else {
					console.log('No phone contacts found or invalid response');
				}
			},
			error: function(xhr, status, error){
				console.error('Phone load error:', status, error, xhr.responseText);
			}
		});
	}

	function loadSendSmsTemplates() {
		$.get(sendSmsTemplatesActiveUrl, function(res){
			var sel = $('#sendSms_template_id');
			sel.find('option:not(:first)').remove();
			if (res.success && res.data && res.data.length) {
				$.each(res.data, function(i, t){
					sel.append('<option value="' + t.id + '">' + t.title + '</option>');
				});
			}
		});
	}

	function autoResizeSendSmsMessage() {
		var el = document.getElementById('sendSms_message');
		if (!el) return;
		var minH = 120;
		var maxH = 500;
		el.style.overflow = 'hidden';
		el.style.height = '0';
		var h = Math.min(Math.max(el.scrollHeight, minH), maxH);
		el.style.height = h + 'px';
		el.style.overflow = '';
	}

	function getSendSmsTemplateReplacements() {
		var replacements = window.sendSmsTemplateReplacements;
		if (typeof replacements === 'string') {
			try {
				replacements = JSON.parse(replacements);
			} catch (e) {
				replacements = null;
			}
		}
		if (!replacements || typeof replacements !== 'object') {
			replacements = {};
		}

		var $modal = $('#sendSmsModal');
		if (!replacements['{client_name}']) {
			replacements['{client_name}'] = $modal.attr('data-page-client-name') || 'there';
		}
		if (!replacements['{first_name}']) {
			replacements['{first_name}'] = $modal.attr('data-page-client-first-name') || 'there';
		}
		if (!replacements['{last_name}']) {
			replacements['{last_name}'] = $modal.attr('data-page-client-last-name') || '';
		}
		if (!replacements['{Student_Name}']) {
			replacements['{Student_Name}'] = $modal.attr('data-page-student-name') || 'Client';
		}
		if (!replacements['{student_name}']) {
			replacements['{student_name}'] = replacements['{Student_Name}'];
		}

		return replacements;
	}

	function applySendSmsTemplateVariables(message) {
		if (!message) return message;
		var replacements = getSendSmsTemplateReplacements();
		Object.keys(replacements).sort(function(a, b) { return b.length - a.length; }).forEach(function(key) {
			if (!key) return;
			message = message.split(key).join(replacements[key] || '');
		});
		return message;
	}

	// Open Send SMS modal from URL: ?open_sms_reminder=1&applicationId=xxx
	var urlParams = new URLSearchParams(window.location.search);
	var openSmsReminder = urlParams.get('open_sms_reminder');
	var applicationId = urlParams.get('applicationId');
	var clientIdForSms = {{ !empty($fetchedData->id) ? (int)$fetchedData->id : 'null' }};
	console.log('URL check - openSmsReminder:', openSmsReminder, 'applicationId:', applicationId, 'clientIdForSms:', clientIdForSms);
	console.log('Modal exists:', $('#sendSmsModal').length);
	// Shared opener for toolbar .send-sms-btn, legacy .sendmsg, and ?open_sms_reminder=
	window.openClientSendSmsModal = function(clientId, applicationId) {
		clientId = clientId || '';
		applicationId = applicationId || '';
		if (!$('#sendSmsModal').length) {
			return false;
		}
		$('#sendSms_client_id').val(clientId);
		$('#sendSms_application_id').val(applicationId);
		$('#sendSms_phone').empty().append('<option value="">Select phone...</option>');
		$('#sendSms_message').val('');
		$('#sendSms_charCount').text('0');
		$('#sendSms_template_id').val('');
		loadSendSmsPhones(clientId);
		loadSendSmsTemplates();
		$('#sendSmsModal').modal('show');
		return true;
	};

	if (openSmsReminder === '1' && applicationId && $('#sendSmsModal').length) {
		console.log('Opening SMS modal from URL');
		window.openClientSendSmsModal(clientIdForSms || '', applicationId);
	}

	$('.send-sms-btn').on('click', function(){
		window.openClientSendSmsModal($(this).attr('data-client-id') || '', '');
	});

	$('#sendSms_message').on('input', function(){
		$('#sendSms_charCount').text($(this).val().length);
		autoResizeSendSmsMessage();
	});

	$('#sendSmsModal').on('shown.bs.modal', function(){ autoResizeSendSmsMessage(); });

	$('#sendSms_template_id').on('change', function(){
		var id = $(this).val();
		if (!id) return;
		$.get('{{ url("adminconsole/features/sms/templates") }}/' + id, function(r){
			if (r.success && r.data && r.data.message) {
				var message = applySendSmsTemplateVariables(r.data.message);
				$('#sendSms_message').val(message);
				$('#sendSms_charCount').text(message.length);
				setTimeout(function(){ autoResizeSendSmsMessage(); }, 10);
			}
		});
	});

	$('#sendSmsForm').on('submit', function(e){
		e.preventDefault();
		var clientId = $('#sendSms_client_id').val() || $('#sendSmsModal').attr('data-page-client-id') || '';
		var applicationId = $('#sendSms_application_id').val() || '';
		var phone = $('#sendSms_phone').val();
		var message = applySendSmsTemplateVariables($('#sendSms_message').val());
		if (!phone || !message) { toastMsg('Please select a phone and enter a message.', 'warning'); return; }
		$('#sendSms_submitBtn').prop('disabled', true).html(crmIconSpinner(' Sending...'));
		$.ajax({
			url: sendSmsSendUrl,
			method: 'POST',
			data: { _token: $('input[name="_token"]').val(), client_id: clientId, application_id: applicationId, phone: phone, message: message },
			success: function(res){
				if (res.success) {
					toastMsg('SMS sent successfully!', 'success');
					$('#sendSmsModal').modal('hide');
				} else {
					toastMsg(res.message || 'Failed to send SMS', 'error');
				}
			},
			error: function(xhr){
				var res = xhr.responseJSON;
				toastMsg(res && res.message ? res.message : 'Failed to send SMS', 'error');
			},
			complete: function(){
				$('#sendSms_submitBtn').prop('disabled', false).html(crmIcon('paper-plane') + ' Send SMS');
			}
		});
	});

	// Lead phone verification OTP
	var leadVerifyResendTimer;
	$('.btn-lead-verify-phone').on('click', function(){
		var leadId = $(this).data('lead-id');
		$('#lead_verify_otp_lead_id').val(leadId);
		$('#leadVerifyOtpSendSection').show();
		$('#leadVerifyOtpCodeSection').hide();
		$('#lead_verify_otp_code').val('');
		$('#leadVerifyOtpModal').modal('show');
	});
	$('#leadVerifyOtpSendBtn').on('click', function(){
		var id = $('#lead_verify_otp_lead_id').val();
		var btn = $(this);
		btn.prop('disabled', true).html(crmIconSpinner(' Sending...'));
		$.post('{{ route("leads.phone.sendOTP") }}', { _token: $('input[name="_token"]').val(), lead_id: id }, function(r){
			if (r.success) {
				$('#leadVerifyOtpSendSection').hide();
				$('#leadVerifyOtpCodeSection').show();
				$('#leadVerifyOtpResendBtn').prop('disabled', true).text('Resend code (30s)');
				var s = 30;
				leadVerifyResendTimer = setInterval(function(){
					s--; $('#leadVerifyOtpResendBtn').text(s ? 'Resend code ('+s+'s)' : 'Resend code');
					if (s <= 0) { clearInterval(leadVerifyResendTimer); $('#leadVerifyOtpResendBtn').prop('disabled', false); }
				}, 1000);
			} else { toastMsg(r.message || 'Failed to send code', 'error'); }
		}).fail(function(){ toastMsg('Request failed', 'error'); }).always(function(){ btn.prop('disabled', false).html(crmIcon('paper-plane') + ' Send Code'); });
	});
	$('#leadVerifyOtpVerifyBtn').on('click', function(){
		var id = $('#lead_verify_otp_lead_id').val();
		var code = $('#lead_verify_otp_code').val();
		if (!code || code.length !== 6) { toastMsg('Enter 6-digit code', 'warning'); return; }
		var btn = $(this);
		btn.prop('disabled', true);
		$.post('{{ route("leads.phone.verifyOTP") }}', { _token: $('input[name="_token"]').val(), lead_id: id, otp_code: code }, function(r){
			if (r.success) {
				toastMsg('Phone verified successfully!', 'success');
				$('#leadVerifyOtpModal').modal('hide');
				location.reload();
			} else { toastMsg(r.message || 'Invalid code', 'error'); }
		}).fail(function(){ toastMsg('Request failed', 'error'); }).always(function(){ btn.prop('disabled', false); });
	});
	$('#leadVerifyOtpResendBtn').on('click', function(){
		if ($(this).prop('disabled')) return;
		var id = $('#lead_verify_otp_lead_id').val();
		$(this).prop('disabled', true).text('Resend code (30s)');
		var s = 30;
		leadVerifyResendTimer = setInterval(function(){
			s--; $('#leadVerifyOtpResendBtn').text(s ? 'Resend code ('+s+'s)' : 'Resend code');
			if (s <= 0) { clearInterval(leadVerifyResendTimer); $('#leadVerifyOtpResendBtn').prop('disabled', false); }
		}, 1000);
		$.post('{{ route("leads.phone.resendOTP") }}', { _token: $('input[name="_token"]').val(), lead_id: id }, function(r){
			if (!r.success) toastMsg(r.message || 'Resend failed', 'error');
		});
	});
});

// ============================================================================
// MULTI-FILE ATTACHMENT — Compose Email modal (Client Detail)
// Logic lives here; email-handlers.js calls window.clearComposeAttachFiles()
// and window.getComposeAttachedFiles() so it stays page-specific.
// ============================================================================
(function($) {
    var composeAttachedFiles = [];

    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function renderAttachFileList() {
        var list = document.getElementById('composeAttachFileList');
        if (!list) return;
        list.innerHTML = '';
        if (composeAttachedFiles.length === 0) return;
        composeAttachedFiles.forEach(function(file, idx) {
            var item = document.createElement('div');
            item.className = 'compose-attach-file-item';
            item.innerHTML =
                crmIcon('file', 'solid', { class: 'compose-attach-file-icon' }) +
                '<span class="compose-attach-file-name" title="' + file.name + '">' + file.name + '</span>' +
                '<span class="compose-attach-file-size">(' + formatFileSize(file.size) + ')</span>' +
                '<button type="button" class="compose-attach-remove-btn" data-idx="' + idx + '" title="Remove">' +
                    crmIcon('times') +
                '</button>';
            list.appendChild(item);
        });
        list.querySelectorAll('.compose-attach-remove-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var i = parseInt(this.getAttribute('data-idx'));
                composeAttachedFiles.splice(i, 1);
                renderAttachFileList();
            });
        });
    }

    // Exposed globals so email-handlers.js can clear/read without knowing internals
    window.clearComposeAttachFiles = function() {
        composeAttachedFiles = [];
        renderAttachFileList();
        var input = document.getElementById('composeAttachFileInput');
        if (input) input.value = '';
    };

    window.getComposeAttachedFiles = function() {
        return composeAttachedFiles.slice();
    };

    $(document).on('click', '#composeAttachAddBtn', function() {
        var input = document.getElementById('composeAttachFileInput');
        if (input) { input.value = ''; input.click(); }
    });

    $(document).on('change', '#composeAttachFileInput', function() {
        Array.from(this.files || []).forEach(function(newFile) {
            var exists = composeAttachedFiles.some(function(f) {
                return f.name === newFile.name && f.size === newFile.size;
            });
            if (!exists) composeAttachedFiles.push(newFile);
        });
        renderAttachFileList();
    });

    $('form[name="sendmail"]').on('reset', function() {
        window.clearComposeAttachFiles();
    });
}(jQuery));
</script>

@push('tinymce-scripts')
@include('partials.tinymce')
@endpush
@endsection
