@extends('layouts.admin')
@section('title', 'Assigned by me')

@section('content')
<style>
.fc-event-container .fc-h-event{cursor:pointer;}
.sort_col a { color: #212529 !important; font-weight: 700 !important;}
.group_type_section a.active {color:black;}
.countAction {background: #1f1655;padding: 0px 5px;border-radius: 50%;color: #fff;margin-left: 5px;}
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
							<h4>Assigned by me</h4>
							<div class="card-header-action">
							</div>

                            <ul class="nav nav-pills" id="client_tabs" role="tablist">
                                <li class="nav-item is_checked_clientn12">
									<a class="nav-link" href="{{URL::to('/action')}}">Incomplete</a>
								</li>

                                <li class="nav-item is_checked_clientn11">
									<a class="nav-link" id="archived-tab"  href="{{URL::to('/action/completed')}}">Completed</a>
								</li>
                            </ul>
						</div>
						<div class="card-body">
							<div class="tab-content" id="quotationContent">
                                <form action="{{ route('action.assigned_by_me') }}" method="get" id="assignedByMeFilters">
                                    <div class="row mb-2">
                                        <div class="col-md-12">
                                            <label class="form-check form-check-inline" title="Future-dated Followup actions are hidden by default until their assign date">
                                                <input type="checkbox" class="form-check-input" name="include_scheduled_followups" value="1"
                                                    {{ !empty($includeScheduledFollowups) ? 'checked' : '' }}
                                                    onchange="document.getElementById('assignedByMeFilters').submit();">
                                                Include scheduled follow-ups
                                            </label>
                                            <small class="text-muted d-block">Default list shows Followups only on/after their assign date.</small>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12 group_type_section"><?php //echo $task_group;?>


                                        </div>
                                    </div>
                                </form>

                                <div class="tab-pane fade show active" id="active_quotation" role="tabpanel" aria-labelledby="active_quotation-tab">
									<div class="table-responsive common_table">
									    <!-- @if ($message = Session::get('success'))
										<div class="alert alert-success">
											<p>{{ $message }}</p>
										</div>
									    @endif   -->

                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="20px" style="text-align: center;">Sno</th>
                                                <th width="25px" style="text-align: center;">Done</th>
                                                <th width="140px">Assignee Name</th>
                                                <th width="140px">Client Reference</th>
                                                <th width="120px" class="sort_col">@sortablelink('action_assign_date','Action Date')</th>
                                                <th width="100px" class="sort_col">@sortablelink('task_group','Type')</th>
                                                <th>Note</th>
                                                <th width="140px">Action</th>
                                            </tr>
                                            <?php
                                            if(count($assignees_notCompleted)>0){
                                            ?>
                                            @foreach ($assignees_notCompleted as $list)
                                            <?php //echo "<pre>list==";print_r($list);
                                                $admin = \App\Models\Staff::find($list->assigned_to);//dd($admin);
                                                if($admin){
                                                    $first_name = $admin->first_name ?? 'N/A';
                                                    $last_name = $admin->last_name ?? 'N/A';
                                                    $full_name = $first_name.' '.$last_name;
                                                } else {
                                                    $full_name = 'N/P';
                                                }
                                            ?>
                                            <tr>
                                                <?php
                                                if($list->noteClient){
                                                    $user_name=$list->noteClient->first_name.' '.$list->noteClient->last_name;
                                                }else{
                                                    $user_name='N/P';
                                                } ?>
                                                <td style="text-align: center;">{{ ++$i }}</td>
                                                <td style="text-align: center;"><input type="radio" class="complete_task" data-bs-toggle="tooltip" title="Mark Complete!" data-id="{{ $list->id }}"></td>
                                                <td>{{ $full_name??'N/P' }}</td>
                                                <td>
                                                    {{ $user_name }}
                                                    <br>
                                                    <?php
                                                    if($list->noteClient)
                                                    { ?>
                                                        <a href="{{URL::to('/clients/detail/'.base64_encode(convert_uuencode(@$list->client_id)))}}" target="_blank" >{{ $list->noteClient->client_id }}</a>
                                                    <?php
                                                    } ?>
                                                </td>

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
                                                <td>
                                                    <?php
                                                    // Escaped plain text only (list/popover XSS-safe). Edit prefills still use data-description.
                                                    $plainDescription = trim(strip_tags((string) ($list->description ?? '')));
                                                    if ($plainDescription !== '') {
                                                        $safeHtml = \App\Support\Utf8Helper::sanitizeForHtml($plainDescription);
                                                        if (mb_strlen($plainDescription) > 190) {
                                                            $preview = \App\Support\Utf8Helper::sanitizeForHtml(mb_substr($plainDescription, 0, 190));
                                                            $safeAttr = \App\Support\Utf8Helper::sanitizeForHtmlAttribute($plainDescription);
                                                            echo $preview . ' <button type="button" class="btn btn-link" data-bs-toggle="popover" data-bs-html="false" data-html="false" title="" data-bs-content="'.$safeAttr.'" data-content="'.$safeAttr.'">Read more</button>';
                                                        } else {
                                                            echo $safeHtml;
                                                        }
                                                    } else {
                                                        echo 'N/P';
                                                    }
                                                    echo "\n";
                                                    ?>
                                                </td>


                                                <td>
                                                    {{-- @if($list->noteClient) --}}
                                                    <form action="{{ route('action.destroy_by_me',$list->id) }}" method="POST">

                                                        {{-- <a class="btn btn-info" href="{{ route('assignees.show',$list->id) }}">Show</a> --}}

                                                        {{--<a class="btn btn-primary" href="{{ url('/clients/edit/'.base64_encode(convert_uuencode(@$list->client_id)).'') }}">Edit</a>--}}

                                                        <?php if($list->task_group != 'Personal Task'){?>
                                                            <button type="button" data-assignedto="{{ $list->assigned_to }}" data-description="{{ $list->description }}" data-taskid="{{ $list->id }}" data-taskgroupid="{{ $list->task_group }}" data-followupdate="{{ $list->action_assign_date }}" class="btn btn-primary btn-block update_task" data-bs-container="body" data-role="popover" data-bs-placement="bottom" data-html="true" data-content="<div id=&quot;popover-content&quot;>
                                                                <h4 class=&quot;text-center&quot;>Update Task</h4>
                                                                <div class=&quot;clearfix&quot;></div>
                                                            <div class=&quot;box-header with-border&quot;>
                                                                <div class=&quot;form-group row&quot; style=&quot;margin-bottom:12px&quot; >
                                                                    <label for=&quot;inputSub3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>Select Assignee</label>
                                                                    <div class=&quot;col-sm-9&quot;>
                                                                        <select class=&quot;assignee-tomselect tomselect form-control selec_reg&quot; id=&quot;rem_cat&quot; name=&quot;rem_cat&quot; onchange=&quot;&quot;>
                                                                            <option value=&quot;&quot; >Select</option>
                                                                            {{--  @foreach(\App\Models\Admin::where('role','!=',7)->orderby('first_name','ASC')->get() as $admin) --}}
                                                                            @foreach(\App\Models\Staff::where('status',1)->orderby('first_name','ASC')->get() as $admin)
                                                                            <?php
                                                                            $branchname = \App\Models\Branch::where('id',$admin->office_id)->first();
                                                                            ?>
                                                                            <option value=&quot;<?php echo $admin->id; ?>&quot; <?php if($admin->id == $list->assigned_to){ echo "selected";} ?>><?php echo $admin->first_name.' '.$admin->last_name.' ('.@$branchname->office_name.')'; ?></option>
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

                                                            <input id=&quot;assign_note_id&quot;  type=&quot;hidden&quot; value=&quot;&quot;>

                                                            <input id=&quot;assign_client_id&quot;  type=&quot;hidden&quot; value=&quot;{{base64_encode(convert_uuencode(@$list->client_id))}}&quot;>
                                                            <div class=&quot;box-footer&quot; style=&quot;padding:10px 0&quot;>
                                                            <div class=&quot;row&quot;>
                                                                <input type=&quot;hidden&quot; value=&quot;&quot; id=&quot;popoverrealdate&quot; name=&quot;popoverrealdate&quot; />
                                                            </div>
                                                            <div class=&quot;row text-center&quot;>
                                                                <div class=&quot;col-md-12 text-center&quot;>
                                                                <button  class=&quot;btn btn-info&quot; id=&quot;updateTask&quot;>Update Task</button>
                                                                </div>
                                                            </div>
                                                    </div>" data-original-title="" title="" style="width: 40px;display: inline;">@icon('edit')</button>
                                                    <?php } ?>

                                                        <?php if($list->task_group != 'Personal Task'){?>
                                                        <button type="button" data-assignedto="{{ $list->assigned_to }}" data-description="{{ $list->description }}" data-taskid="{{ $list->id }}" data-taskgroupid="{{ $list->task_group }}" data-followupdate="{{ $list->action_assign_date }}" class="btn btn-primary btn-block reassign_task" data-bs-container="body" data-role="popover" data-bs-placement="bottom" data-html="true" title="Reassign" data-content="<div id=&quot;popover-content&quot;>
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

                                                        <input id=&quot;assign_note_id&quot;  type=&quot;hidden&quot; value=&quot;&quot;>
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
                                                </div>" data-original-title="" title="" style="width: 40px;display: inline;">@icon('tasks')</button>
                                                        <?php } ?>

                                                        @csrf
                                                        @method('DELETE')

                                                        <!--<button type="submit" class="btn btn-danger" data-crm-confirm='Are you sure want to delete?'">@icon('trash')</button>-->



                                                    </form>
                                                    {{-- @endif --}}
                                                </td>
                                            </tr>
										    @endforeach
                                            <?php
                                            } else {
                                            ?>
                                            <tr>
                                                <td colspan="8"><b>There is no activity assigned by me.</b></td>
                                            </tr>
                                            <?php
                                            }
                                            ?>
									    </table>
										{{-- {!! $assignees->appends(\Request::except('page'))->render() !!} --}}
   										{!! $assignees_notCompleted->appends($_GET)->links() !!}
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

<!-- Complete Action Modal -->
<div class="modal fade" id="completeActionModal" tabindex="-1" role="dialog" aria-labelledby="completeActionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="completeActionModalLabel">Complete Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Client:</label>
                    <p id="complete-action-client"><strong><span></span></strong></p>
                </div>
                <div class="form-group">
                    <label for="completion_message">Completion Message: <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="completion_message" name="completion_message" rows="4" placeholder="Enter completion notes..." required></textarea>
                    <small class="form-text text-muted">Please describe what was done to complete this action.</small>
                </div>
                <input type="hidden" id="complete_action_id" name="complete_action_id" value="">
                <input type="hidden" id="complete_client_id" name="complete_client_id" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="submitCompleteAction">Complete Action</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')

@push('scripts')
	@vite(['resources/js/pages/admin/popover-entry.js'])
@endpush

<script>
	jQuery(document).ready(function($){
    /**
     * Resolve the Bootstrap popover tip DOM for a given trigger.
     * Never fall back to global $('#assignnote') etc. (duplicate ids across rows).
     */
    function getPopoverTipForTrigger(triggerEl) {
        if (!triggerEl) {
            return $();
        }
        try {
            if (window.bootstrap && window.bootstrap.Popover) {
                var inst = window.bootstrap.Popover.getInstance(triggerEl);
                if (inst) {
                    var tip = (typeof inst.getTipElement === 'function')
                        ? inst.getTipElement()
                        : (inst.tip || null);
                    if (tip) {
                        return $(tip);
                    }
                }
            }
        } catch (err) { /* ignore */ }

        var data = $(triggerEl).data('bs.popover');
        if (data && data.tip) {
            return $(data.tip);
        }
        return $();
    }

    /** Form root for submit buttons rendered inside the open popover tip. */
    function getActionPopoverFormFromEvent($btn) {
        var $form = $btn.closest('.popover');
        if ($form.length) {
            return $form;
        }
        // Fallback: tip of last known row trigger (not document-wide #ids)
        if (window._assignedByMeActiveTrigger) {
            $form = getPopoverTipForTrigger(window._assignedByMeActiveTrigger);
            if ($form.length) {
                return $form;
            }
        }
        return $();
    }

    function loadAssigneeIntoPopover($popover, assignedto) {
        if (!$popover || !$popover.length) {
            return;
        }
        $.ajax({
            type: 'post',
            url: "{{URL::to('/')}}/action/assignee-list",
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { assignedto: assignedto },
            success: function(response) {
                var obj = $.parseJSON(response);
                var $select = $popover.find('#rem_cat').first();
                if (!$select.length) {
                    return;
                }
                if (window.ActionPopoverTomSelect) {
                    ActionPopoverTomSelect.refreshAssigneeSelect($select[0], obj.message, $popover[0]);
                } else {
                    $select.html(obj.message);
                }
            }
        });
    }

    /**
     * Wait for this button's popover tip, then prefill only within that tip.
     * Retries briefly; never writes document-global #id fields.
     */
    function fillActionPopoverWhenReady($btn, opts) {
        var filled = false;
        var attempts = 0;
        var maxAttempts = 10;
        var retryMs = 75;

        var tryFill = function() {
            if (filled) {
                return;
            }
            var $popover = getPopoverTipForTrigger($btn[0]);
            if (!$popover.length || !$popover.find('#assign_note_id, #assignnote').length) {
                attempts += 1;
                if (attempts < maxAttempts) {
                    setTimeout(tryFill, retryMs);
                    return;
                }
                if (typeof showToast === 'function') {
                    showToast('Could not open form. Please try again.', 'warning');
                }
                return;
            }

            filled = true;
            $popover.find('#assignnote').val(opts.note_description);
            $popover.find('#assign_note_id').val(opts.task_id);
            $popover.find('#task_group').val(opts.taskgroup_id);
            $popover.find('#popoverdatetime').val(opts.finalDate);
            loadAssigneeIntoPopover($popover, opts.assignedto);
        };

        $btn.one('shown.bs.popover', tryFill);
        setTimeout(tryFill, retryMs);
    }

     $(document).delegate('.openassignee', 'click', function(){
        $('.assignee').show();
    });
	$(document).delegate('.closeassignee', 'click', function(){
        $('.assignee').hide();
    });


    //reassign task
    $(document).delegate('.reassign_task', 'click', function(e){
        e.preventDefault();
        e.stopPropagation();
        
        var $btn = $(this);
        window._assignedByMeActiveTrigger = this;
        var assignedto = $btn.attr('data-assignedto');
        // Description text for textarea prefill (legacy data-noteid fallback if present)
        var note_description = $btn.attr('data-description');
        if (note_description === undefined) {
            note_description = $btn.attr('data-noteid') || '';
        }
        var task_id = $btn.attr('data-taskid');
        var taskgroup_id = $btn.attr('data-taskgroupid');
        var followupdate_id = $btn.attr('data-followupdate');
        var folowDateArr = (followupdate_id || '').split(" ");
        var finalDate = folowDateArr[0] || '';
        
        // Popover is already initialized by popover.js on page load - do NOT re-initialize
        // (Re-initializing causes "Bootstrap doesn't allow more than one instance per element" error)
        $btn.popover('show');

        fillActionPopoverWhenReady($btn, {
            assignedto: assignedto,
            note_description: note_description,
            task_id: task_id,
            taskgroup_id: taskgroup_id,
            finalDate: finalDate
        });
    });

    //update task
    $(document).delegate('.update_task', 'click', function(e){
        e.preventDefault();
        e.stopPropagation();
        
        var $btn = $(this);
        window._assignedByMeActiveTrigger = this;
        var assignedto = $btn.attr('data-assignedto');
        // Description text for textarea prefill (legacy data-noteid fallback if present)
        var note_description = $btn.attr('data-description');
        if (note_description === undefined) {
            note_description = $btn.attr('data-noteid') || '';
        }
        var task_id = $btn.attr('data-taskid');
        var taskgroup_id = $btn.attr('data-taskgroupid');
        var followupdate_id = $btn.attr('data-followupdate');
        var folowDateArr = (followupdate_id || '').split(" ");
        var finalDate = folowDateArr[0] || '';
        
        // Popover is already initialized by popover.js on page load - do NOT re-initialize
        // (Re-initializing causes "Bootstrap doesn't allow more than one instance per element" error)
        $btn.popover('show');

        fillActionPopoverWhenReady($btn, {
            assignedto: assignedto,
            note_description: note_description,
            task_id: task_id,
            taskgroup_id: taskgroup_id,
            finalDate: finalDate
        });
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
	$(document).delegate('.complete_task', 'click', function(e){
		e.preventDefault();
		var row_id = $(this).attr('data-id');
        if(row_id !=""){
            // Get client name from the row
            var $row = $(this).closest('tr');
            var clientName = 'N/A';
            var clientId = '';
            
            // Extract client name from the Client Reference column (4th column)
            var $clientCell = $row.find('td:eq(3)');
            if ($clientCell.length) {
                var cellText = $clientCell.text().trim();
                var lines = cellText.split('\n');
                if (lines.length > 0) {
                    clientName = lines[0].trim() || 'N/A';
                }
            }
            
            // Get client_id from the note data if available
            $.ajax({
                type: 'GET',
                url: "{{URL::to('/')}}/action/get-note-data",
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {id: row_id},
                success: function(noteData){
                    // Handle response structure
                    if(noteData && noteData.status && noteData.client_id){
                        clientId = noteData.client_id;
                        if(noteData.client_name){
                            clientName = noteData.client_name;
                        }
                    } else if(noteData && noteData.client_id){
                        // Fallback for different response structure
                        clientId = noteData.client_id;
                        if(noteData.client_name){
                            clientName = noteData.client_name;
                        }
                    }
                    
                    // Set form values
                    $('#complete_action_id').val(row_id);
                    $('#complete_client_id').val(clientId);
                    $('#complete-action-client span').text(clientName || 'N/A');
                    $('#completion_message').val('');
                    
                    // Show modal
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        var modalElement = document.getElementById('completeActionModal');
                        var modal = new bootstrap.Modal(modalElement);
                        modal.show();
                    } else {
                        $('#completeActionModal').modal('show');
                    }
                },
                error: function(xhr){
                    // Fallback if note data fetch fails - try to get from note directly
                    console.warn('Failed to fetch note data, using fallback');
                    
                    // Set form values with available data
                    $('#complete_action_id').val(row_id);
                    $('#complete_client_id').val(''); // Will be fetched from note on backend
                    $('#complete-action-client span').text(clientName || 'N/A');
                    $('#completion_message').val('');
                    
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        var modalElement = document.getElementById('completeActionModal');
                        var modal = new bootstrap.Modal(modalElement);
                        modal.show();
                    } else {
                        $('#completeActionModal').modal('show');
                    }
                }
            });
        }
	});
    
    // Handle complete action form submission
    $('#submitCompleteAction').on('click', function() {
        var actionId = $('#complete_action_id').val();
        var clientId = $('#complete_client_id').val();
        var message = $('#completion_message').val().trim();
        
        if (!message) {
            showToast('Please enter a completion message.', 'warning');
            return;
        }
        
        // Disable button during submission
        $(this).prop('disabled', true).html(crmIconSpinner('Completing...'));
        
        $.ajax({
            url: "{{URL::to('/')}}/action/task-complete",
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                id: actionId,
                client_id: clientId,
                completion_message: message
            },
            success: function(response) {
                if (response.status) {
                    // Hide modal
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        var modalElement = document.getElementById('completeActionModal');
                        var modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) modal.hide();
                    } else {
                        $('#completeActionModal').modal('hide');
                    }
                    
                    // Show success message
                    showToast(response.message || 'Action completed successfully!', 'success');
                    
                    // Reload page to reflect changes
                    location.reload();
                } else {
                    showToast(response.message || 'Failed to complete action. Please try again.', 'error');
                    $('#submitCompleteAction').prop('disabled', false).html('Complete Action');
                }
            },
            error: function(xhr) {
                var errorMsg = 'An error occurred. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showToast(errorMsg, 'error');
                $('#submitCompleteAction').prop('disabled', false).html('Complete Action');
            }
        });
    });


    //re-assign task or update task
    $(document).delegate('#assignUser','click', function(){
		$(".popuploader").show();
		var flag = true;
		var error ="";
		$(".custom-error").remove();

		// Scope only to the open tip that contains this button — never document-wide #ids
		var $form = getActionPopoverFormFromEvent($(this));
		if (!$form.length) {
			$('.popuploader').hide();
			if (typeof showToast === 'function') {
				showToast('Could not find the open form. Please reopen and try again.', 'warning');
			}
			return;
		}
		
		if(typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal($form.find('#rem_cat')) === '' : $form.find('#rem_cat').val() == ''){
			$('.popuploader').hide();
			error="Assignee field is required.";
			$form.find('#rem_cat').after("<span class='custom-error' role='alert'>"+error+"</span>");
			flag = false;
		}
		if($form.find('#assignnote').val() == ''){
			$('.popuploader').hide();
			error="Note field is required.";
			$form.find('#assignnote').after("<span class='custom-error' role='alert'>"+error+"</span>");
			flag = false;
		}
        if(typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal($form.find('#task_group')) === '' : $form.find('#task_group').val() == ''){
			$('.popuploader').hide();
			error="Group field is required.";
			$form.find('#task_group').after("<span class='custom-error' role='alert'>"+error+"</span>");
			flag = false;
		}
		if(flag){
			$.ajax({
				type:'post',
                url:"{{URL::to('/')}}/clients/reassignaction/store",
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {
					note_id: $form.find('#assign_note_id').val(),
					note_type: 'action',
					description: $form.find('#assignnote').val(),
					client_id: $form.find('#assign_client_id').val(),
					followup_datetime: $form.find('#popoverdatetime').val(),
					assignee_name: typeof actionPopoverAssigneeLabel === 'function' ? actionPopoverAssigneeLabel($form.find('#rem_cat')) : $form.find('#rem_cat :selected').text(),
					rem_cat: typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal($form.find('#rem_cat')) : $form.find('#rem_cat option:selected').val(),
					task_group: typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal($form.find('#task_group')) : $form.find('#task_group option:selected').val()
				},
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
                    } else{
                        showToast(obj.message, 'error');
                        location.reload();
                    }
                }
			});
		}else{
			$("#loader").hide();
			$('.popuploader').hide();
		}
	});


    //update task
    $(document).delegate('#updateTask','click', function(){
		$(".popuploader").show();
		var flag = true;
		var error ="";
		$(".custom-error").remove();

		// Scope only to the open tip that contains this button — never document-wide #ids
		var $form = getActionPopoverFormFromEvent($(this));
		if (!$form.length) {
			$('.popuploader').hide();
			if (typeof showToast === 'function') {
				showToast('Could not find the open form. Please reopen and try again.', 'warning');
			}
			return;
		}

		if(typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal($form.find('#rem_cat')) === '' : $form.find('#rem_cat').val() == ''){
			$('.popuploader').hide();
			error="Assignee field is required.";
			$form.find('#rem_cat').after("<span class='custom-error' role='alert'>"+error+"</span>");
			flag = false;
		}
		if($form.find('#assignnote').val() == ''){
			$('.popuploader').hide();
			error="Note field is required.";
			$form.find('#assignnote').after("<span class='custom-error' role='alert'>"+error+"</span>");
			flag = false;
		}
        if(typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal($form.find('#task_group')) === '' : $form.find('#task_group').val() == ''){
			$('.popuploader').hide();
			error="Group field is required.";
			$form.find('#task_group').after("<span class='custom-error' role='alert'>"+error+"</span>");
			flag = false;
		}
		if(flag){
			$.ajax({
				type:'post',
                url:"{{URL::to('/')}}/clients/updateaction/store",
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {
					note_id: $form.find('#assign_note_id').val(),
					note_type: 'action',
					description: $form.find('#assignnote').val(),
					client_id: $form.find('#assign_client_id').val(),
					followup_datetime: $form.find('#popoverdatetime').val(),
					assignee_name: typeof actionPopoverAssigneeLabel === 'function' ? actionPopoverAssigneeLabel($form.find('#rem_cat')) : $form.find('#rem_cat :selected').text(),
					rem_cat: typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal($form.find('#rem_cat')) : $form.find('#rem_cat option:selected').val(),
					task_group: typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal($form.find('#task_group')) : $form.find('#task_group option:selected').val()
				},
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
                    } else{
                        showToast(obj.message, 'error');
                        location.reload();
                    }
                }
			});
		}else{
			$("#loader").hide();
			$('.popuploader').hide();
		}
	});
});
</script>

@push('tinymce-scripts')
@include('partials.tinymce')
@endpush

@endsection
