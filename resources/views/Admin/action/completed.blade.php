@extends('layouts.admin')
@section('title', 'Completed Activities')

@section('content')
<style>
.fc-event-container .fc-h-event{cursor:pointer;}
.sort_col a { color: #212529 !important; font-weight: 700 !important;}
.group_type_section a.active {color:black;}
.countAction {background: #1f1655;padding: 0px 5px;border-radius: 50%;color: #fff;margin-left: 5px;}
.popover .popover-body { overflow: visible !important; }
.popover .ts-wrapper { z-index: 100001 !important; width: 100% !important; }
.popover .ts-dropdown { z-index: 100001 !important; }
.action-btns { display: flex; gap: 4px; flex-wrap: nowrap; align-items: center; }
.action-btns .btn { flex-shrink: 0; }
.table td { vertical-align: middle; }
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
                            <h4>Completed Activities</h4>

                            <div class="card-header-action">
							</div>

                            <ul class="nav nav-pills" id="client_tabs" role="tablist">
                                <li class="nav-item is_checked_clientn11">
									<a class="nav-link active" id="archived-tab"  href="{{URL::to('/action')}}">Incomplete</a>
								</li>
                            </ul>
						</div>
						<div class="card-body">
							<div class="tab-content" id="quotationContent">
                                <form action="{{ route('action.completed') }}" method="get">
                                    <div class="row">
                                        <div class="col-md-12 group_type_section">
                                            @php
                                                $typeCounts = $typeCounts ?? [];
                                                $assignableStaff = $assignableStaff ?? collect();
                                            @endphp
                                            <a href="{{URL::to('/action/completed?group_type=All')}}" id="All" class="group_type <?php if($task_group == 'All') { echo 'active';}?>">All <span class="countAction">{{ (int) ($typeCounts['All'] ?? 0) }}</span></a> | &nbsp;

                                            <button type="button" class="btn btn-light">
                                                <a href="{{URL::to('/action/completed?group_type=Call')}}" id="Call" class="group_type <?php if($task_group == 'Call') { echo 'active';}?>"> @icon('phone') Call <span class="countAction">{{ (int) ($typeCounts['Call'] ?? 0) }}</span></a> &nbsp;
                                            </button>

                                            <button type="button" class="btn btn-light">
                                                <a href="{{URL::to('/action/completed?group_type=Checklist')}}" id="Checklist" class="group_type <?php if($task_group == 'Checklist') { echo 'active';}?>">@icon('bars') Checklist <span class="countAction">{{ (int) ($typeCounts['Checklist'] ?? 0) }}</span></a> &nbsp;
                                            </button>

                                            <button type="button" class="btn btn-light">
                                                <a href="{{URL::to('/action/completed?group_type=Review')}}" id="Review" class="group_type <?php if($task_group == 'Review') { echo 'active';}?>"> @icon('check') Review <span class="countAction">{{ (int) ($typeCounts['Review'] ?? 0) }}</span></a> &nbsp;
                                            </button>

                                            <button type="button" class="btn btn-light">
                                                <a href="{{URL::to('/action/completed?group_type=Query')}}" id="Query" class="group_type <?php if($task_group == 'Query') { echo 'active';}?>">@icon('question') Query <span class="countAction">{{ (int) ($typeCounts['Query'] ?? 0) }}</span></a> &nbsp;
                                            </button>

                                            <button type="button" class="btn btn-light">
                                                <a href="{{URL::to('/action/completed?group_type=Urgent')}}" id="Urgent" class="group_type <?php if($task_group == 'Urgent') { echo 'active';}?>"> @icon('flag') Urgent <span class="countAction">{{ (int) ($typeCounts['Urgent'] ?? 0) }}</span></a> &nbsp;
                                            </button>

                                            <button type="button" class="btn btn-light">
                                                <a href="{{URL::to('/action/completed?group_type=Personal Task')}}" id="Personal Task" class="group_type <?php if($task_group == 'Personal Task') { echo 'active';}?>"> @icon('tasks') Personal Task <span class="countAction">{{ (int) ($typeCounts['Personal Task'] ?? 0) }}</span></a> &nbsp;
                                            </button>
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
                                                <th width="120px">Assigner Name</th>
                                                <th width="140px">Client Reference</th>
                                                <th width="120px" class="sort_col">@sortablelink('action_assign_date','Action Date')</th>
                                                <th width="100px" class="sort_col">@sortablelink('task_group','Type')</th>
                                                <th>Note</th>
                                                <th width="140px">Action</th>
                                            </tr>
                                            <?php
                                            if(count($assignees_completed)>0){
                                            ?>
                                            @foreach ($assignees_completed as $list)
                                            <?php
                                                $admin = $list->noteUser;
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
                                                // Handle both client and partner types
                                                if ($list->isPersonalTaskWithoutClient()) {
                                                    $client_reference_html = '<span class="badge badge-info bg-info">Personal Task</span>';
                                                } elseif($list->type == 'partner'){
                                                    $partnerInfo = $list->notePartner;
                                                    if($partnerInfo){
                                                        $user_name = $partnerInfo->partner_name;
                                                        $reference_link = '<a href="'.route('partners.detail', base64_encode(convert_uuencode(@$list->client_id))).'" target="_blank" >'.$partnerInfo->partner_name.'</a>';
                                                        $client_reference_html = $user_name.'<br>'.$reference_link;
                                                    } else {
                                                        $client_reference_html = 'N/P';
                                                    }
                                                } else {
                                                    // Client/lead type (note.type stays "client"; admins.type may be lead)
                                                    if($list->noteClient){
                                                        $user_name = $list->noteClient->first_name.' '.$list->noteClient->last_name;
                                                        $encodedRefId = base64_encode(convert_uuencode(@$list->client_id));
                                                        $isLeadType = strtolower((string) ($list->noteClient->type ?? '')) === 'lead';
                                                        $detailUrl = $isLeadType
                                                            ? route('leads.detail', $encodedRefId)
                                                            : route('clients.detail', $encodedRefId);
                                                        $reference_link = '<a href="'.$detailUrl.'" target="_blank" >'.$list->noteClient->client_id.'</a>';
                                                        $client_reference_html = $user_name.'<br>'.$reference_link;
                                                    } else {
                                                        $client_reference_html = 'N/P';
                                                    }
                                                }
                                            ?>
                                                <td style="text-align: center;">{{ ++$i }}</td>
                                                <td style="text-align: center;"><input type="radio" class="not_complete_task" data-bs-toggle="tooltip" title="Mark Incomplete!" data-id="{{ $list->id }}"></td>
                                                <td>{{ $full_name??'N/P' }}</td>
                                                <td>{!! $client_reference_html !!}</td>
                                                <td>{{ date('d/m/Y',strtotime($list->action_assign_date)) ?? 'N/P'}} </td>
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
                                                    <form action="{{ route('action.destroy_completed',$list->id) }}" method="POST" class="d-inline">
                                                        <div class="action-btns">
                                                         @if($list->task_group != 'Personal Task')
                                                         {{-- Update Task: use template div to avoid HTML-in-attribute rendering issues --}}
                                                         <div id="popover-update-{{ $list->id }}" class="d-none">
                                                            <h4 class="text-center">Update Task</h4>
                                                            <div class="clearfix"></div>
                                                            <div class="box-header with-border">
                                                                <div class="form-group row" style="margin-bottom:12px">
                                                                    <label class="col-sm-3 control-label c6 f13" style="margin-top:8px">Select Assignee</label>
                                                                    <div class="col-sm-9">
                                                                        <select class="assignee-tomselect tomselect form-control selec_reg rem_cat" name="rem_cat">
                                                                            <option value="">Select</option>
                                                                            @foreach($assignableStaff as $admin)
                                                                            <option value="{{ $admin->id }}" {{ $admin->id == $list->assigned_to ? 'selected' : '' }}>{{ $admin->first_name.' '.$admin->last_name.' ('.($admin->office->office_name ?? '').')' }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="box-header with-border">
                                                                <div class="form-group row" style="margin-bottom:12px">
                                                                    <label class="col-sm-3 control-label c6 f13" style="margin-top:8px">Note</label>
                                                                    <div class="col-sm-9">
                                                                        <textarea class="form-control assignnote tinymce-simple f13" placeholder="Enter an note...." rows="3"></textarea>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="box-header with-border">
                                                                <div class="form-group row" style="margin-bottom:12px">
                                                                    <label class="col-sm-3 control-label c6 f13" style="margin-top:8px">DateTime</label>
                                                                    <div class="col-sm-9">
                                                                        <input type="text" class="form-control f13 flatpickr-date popoverdatetime" placeholder="yyyy-mm-dd" value="{{ date('Y-m-d') }}" name="popoverdate" autocomplete="off">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row" style="margin-bottom:12px">
                                                                <label class="col-sm-3 control-label c6 f13" style="margin-top:8px">Group</label>
                                                                <div class="col-sm-9">
                                                                    <select class="assignee-tomselect tomselect form-control task_group" name="task_group">
                                                                        <option value="">Select</option>
                                                                        <option value="Call" {{ $list->task_group == 'Call' ? 'selected' : '' }}>Call</option>
                                                                        <option value="Checklist" {{ $list->task_group == 'Checklist' ? 'selected' : '' }}>Checklist</option>
                                                                        <option value="Review" {{ $list->task_group == 'Review' ? 'selected' : '' }}>Review</option>
                                                                        <option value="Query" {{ $list->task_group == 'Query' ? 'selected' : '' }}>Query</option>
                                                                        <option value="Urgent" {{ $list->task_group == 'Urgent' ? 'selected' : '' }}>Urgent</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <input type="hidden" class="assign_note_id" value="">
                                                            <input type="hidden" class="assign_client_id" value="{{ base64_encode(convert_uuencode(@$list->client_id)) }}">
                                                            <div class="box-footer" style="padding:10px 0">
                                                                <div class="row text-center">
                                                                    <div class="col-md-12">
                                                                        <button type="button" class="btn btn-info updateTask">Update Task</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                         </div>
                                                         <button type="button" data-popover-target="popover-update-{{ $list->id }}" data-description="{{ $list->description }}" data-taskid="{{ $list->id }}" data-taskgroupid="{{ $list->task_group }}" data-followupdate="{{ $list->action_assign_date }}" data-assignedto="{{ $list->assigned_to }}" class="btn btn-primary btn-sm update_task" data-bs-toggle="tooltip" title="Update Task">@icon('edit')</button>
                                                         @endif

                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm" data-bs-toggle="tooltip" title="Delete" data-crm-confirm='Are you sure want to delete?'">@icon('trash')</button>

                                                        @if($list->task_group != 'Personal Task')
                                                        {{-- Assign Staff: use template div --}}
                                                        <div id="popover-assign-{{ $list->id }}" class="d-none">
                                                            <h4 class="text-center">Re-Assign Staff</h4>
                                                            <div class="clearfix"></div>
                                                            <div class="box-header with-border">
                                                                <div class="form-group row" style="margin-bottom:12px">
                                                                    <label class="col-sm-3 control-label c6 f13" style="margin-top:8px">Select Assignee</label>
                                                                    <div class="col-sm-9">
                                                                        <select class="assignee-tomselect tomselect form-control selec_reg rem_cat" name="rem_cat">
                                                                            <option value="">Select</option>
                                                                            @foreach($assignableStaff as $admin)
                                                                            <option value="{{ $admin->id }}">{{ $admin->first_name.' '.$admin->last_name.' ('.($admin->office->office_name ?? '').')' }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="box-header with-border">
                                                                <div class="form-group row" style="margin-bottom:12px">
                                                                    <label class="col-sm-3 control-label c6 f13" style="margin-top:8px">Note</label>
                                                                    <div class="col-sm-9">
                                                                        <textarea class="form-control assignnote tinymce-simple f13" placeholder="Enter an note...." rows="3"></textarea>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="box-header with-border">
                                                                <div class="form-group row" style="margin-bottom:12px">
                                                                    <label class="col-sm-3 control-label c6 f13" style="margin-top:8px">DateTime</label>
                                                                    <div class="col-sm-9">
                                                                        <input type="text" class="form-control f13 flatpickr-date popoverdatetime" placeholder="yyyy-mm-dd" value="{{ date('Y-m-d') }}" name="popoverdate" autocomplete="off">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group row" style="margin-bottom:12px">
                                                                <label class="col-sm-3 control-label c6 f13" style="margin-top:8px">Group</label>
                                                                <div class="col-sm-9">
                                                                    <select class="assignee-tomselect tomselect form-control task_group" name="task_group">
                                                                        <option value="">Select</option>
                                                                        <option value="Call">Call</option>
                                                                        <option value="Checklist">Checklist</option>
                                                                        <option value="Review">Review</option>
                                                                        <option value="Query">Query</option>
                                                                        <option value="Urgent">Urgent</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <input type="hidden" class="assign_note_id" value="">
                                                            <input type="hidden" class="assign_client_id" value="{{ base64_encode(convert_uuencode(@$list->client_id)) }}">
                                                            <div class="box-footer" style="padding:10px 0">
                                                                <div class="row text-center">
                                                                    <div class="col-md-12">
                                                                        <button type="button" class="btn btn-info assignUser">Assign Staff</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <button type="button" data-popover-target="popover-assign-{{ $list->id }}" data-description="{{ $list->description }}" data-taskid="{{ $list->id }}" data-taskgroupid="{{ $list->task_group }}" data-followupdate="{{ $list->action_assign_date }}" data-assignedto="{{ $list->assigned_to }}" class="btn btn-primary btn-sm reassign_task" data-bs-toggle="tooltip" title="Assign Staff">@icon('tasks')</button>
                                                        @endif
                                                        </div>
                                                    </form>
                                                </td>
                                            </tr>
										    @endforeach
                                            <?php
                                            } else {
                                            ?>
                                            <tr>
                                                <td colspan="8"><b>There is no completed activity exist.</b></td>
                                            </tr>
                                            <?php
                                            }
                                            ?>
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

<!-- Update Task / Assign Staff Modal (populated from template) -->
<div class="modal fade" id="actionPopoverModal" tabindex="-1" aria-labelledby="actionPopoverModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="actionPopoverModalLabel"></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body" id="actionPopoverModalBody"></div>
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
    $('[data-bs-toggle="tooltip"]').tooltip();

    $(document).delegate('.openassignee', 'click', function(){
        $('.assignee').show();
    });

	$(document).delegate('.closeassignee', 'click', function(){
        $('.assignee').hide();
    });

    // Update task - show modal with form from template
    $(document).delegate('.update_task', 'click', function(e){
        e.preventDefault();
        var $btn = $(this);
        var targetId = $btn.data('popover-target');
        var $template = $('#' + targetId);
        if (!$template.length) return;

        var noteDescription = $btn.attr('data-description');
        if (noteDescription === undefined) {
            noteDescription = $btn.attr('data-noteid') || '';
        }
        var taskId = $btn.data('taskid');
        var taskgroupId = $btn.data('taskgroupid');
        var followupdate = ($btn.data('followupdate') || '').toString().split(' ')[0] || '{{ date("Y-m-d") }}';

        var $clone = $template.clone().removeClass('d-none');
        $clone.find('.assign_note_id').val(taskId);
        $clone.find('.assignnote').val(noteDescription);
        if (typeof setEnhancedSelectValue === 'function') {
            setEnhancedSelectValue($clone.find('.task_group')[0], taskgroupId);
        } else {
            $clone.find('.task_group').val(taskgroupId);
        }
        $clone.find('.popoverdatetime').val(followupdate);

        $('#actionPopoverModalLabel').text('Update Task');
        $('#actionPopoverModalBody').html($clone);
        var modalEl = document.getElementById('actionPopoverModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            $(modalEl).modal('show');
        }
        if (typeof initModalFlatpickrDates === 'function') {
            initModalFlatpickrDates('#actionPopoverModal');
        } else if (typeof flatpickr !== 'undefined') {
            document.querySelectorAll('#actionPopoverModal .flatpickr-date').forEach(function (el) {
                if (el._flatpickr) {
                    el._flatpickr.destroy();
                }
                flatpickr(el, { dateFormat: 'Y-m-d', allowInput: true });
            });
        }
    });

    // Reassign task - show modal with form from template
    $(document).delegate('.reassign_task', 'click', function(e){
        e.preventDefault();
        var $btn = $(this);
        var targetId = $btn.data('popover-target');
        var $template = $('#' + targetId);
        if (!$template.length) return;

        var noteDescription = $btn.attr('data-description');
        if (noteDescription === undefined) {
            noteDescription = $btn.attr('data-noteid') || '';
        }
        var taskId = $btn.data('taskid');
        var taskgroupId = $btn.data('taskgroupid');
        var followupdate = ($btn.data('followupdate') || '').toString().split(' ')[0] || '{{ date("Y-m-d") }}';
        var assignedTo = $btn.data('assignedto');

        var $clone = $template.clone().removeClass('d-none');
        $clone.find('.assign_note_id').val(taskId);
        $clone.find('.assignnote').val(noteDescription);
        if (typeof setEnhancedSelectValue === 'function') {
            setEnhancedSelectValue($clone.find('.task_group')[0], taskgroupId);
        } else {
            $clone.find('.task_group').val(taskgroupId);
        }
        $clone.find('.popoverdatetime').val(followupdate);

        $('#actionPopoverModalLabel').text('Re-Assign Staff');
        $('#actionPopoverModalBody').html($clone);

        if (assignedTo) {
            $.ajax({ type:'post', url:"{{URL::to('/')}}/action/assignee-list", headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}, data: {assignedto: assignedTo},
                success: function(r){ var obj = $.parseJSON(r); if(obj.message) { var html = Array.isArray(obj.message) ? obj.message.join('') : obj.message; var $sel = $('#actionPopoverModalBody .rem_cat').first(); if (window.ActionPopoverTomSelect) { ActionPopoverTomSelect.refreshAssigneeSelect($sel[0], html, $('#actionPopoverModalBody')[0]); } else { $sel.html(html); } } }
            });
        }

        var modalEl = document.getElementById('actionPopoverModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        } else {
            $(modalEl).modal('show');
        }
        if (typeof initModalFlatpickrDates === 'function') {
            initModalFlatpickrDates('#actionPopoverModal');
        } else if (typeof flatpickr !== 'undefined') {
            document.querySelectorAll('#actionPopoverModal .flatpickr-date').forEach(function (el) {
                if (el._flatpickr) {
                    el._flatpickr.destroy();
                }
                flatpickr(el, { dateFormat: 'Y-m-d', allowInput: true });
            });
        }
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
		var row_id = $(this).attr('data-id'); //alert(row_id);
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


    $(document).delegate('#actionPopoverModalBody .assignUser','click', function(){
		$(".popuploader").show();
		var $modal = $('#actionPopoverModalBody');
		var flag = true;
		var error = "";
		$modal.find(".custom-error").remove();
		var $remCat = $modal.find('.rem_cat');
		var $assignNote = $modal.find('.assignnote');
		var $taskGroup = $modal.find('.task_group');
		var $assignNoteId = $modal.find('.assign_note_id');
		var $assignClientId = $modal.find('.assign_client_id');
		var $popoverDateTime = $modal.find('.popoverdatetime');
		if(typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal($remCat) === '' : $remCat.val() == ''){
			$('.popuploader').hide();
			error="Assignee field is required.";
			$remCat.after("<span class='custom-error' role='alert'>"+error+"</span>");
			flag = false;
		}
		if($assignNote.val() == ''){
			$('.popuploader').hide();
			error="Note field is required.";
			$assignNote.after("<span class='custom-error' role='alert'>"+error+"</span>");
			flag = false;
		}
		if(typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal($taskGroup) === '' : $taskGroup.val() == ''){
			$('.popuploader').hide();
			error="Group field is required.";
			$taskGroup.after("<span class='custom-error' role='alert'>"+error+"</span>");
			flag = false;
		}
		if(flag){
			$.ajax({
				type:'post',
				url:"{{URL::to('/')}}/clients/reassignaction/store",
				headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
				data: {
					note_id: $assignNoteId.val(),
					note_type:'action',
					description:$assignNote.val(),
					client_id:$assignClientId.val(),
					followup_datetime:$popoverDateTime.val(),
					assignee_name:$remCat.find(':selected').text(),
					rem_cat: typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal($remCat) : $remCat.val(),
					task_group: typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal($taskGroup) : $taskGroup.val()
				},
				success: function(response){
					$('.popuploader').hide();
					var obj = $.parseJSON(response);
					if(obj.success){
						$('#actionPopoverModal').modal('hide');
						if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
							var m = bootstrap.Modal.getInstance(document.getElementById('actionPopoverModal'));
							if (m) m.hide();
						}
						location.reload();
					}else{
						showToast(obj.message, 'error');
					}
				}
			});
		}
	});

	// Update task - called from modal
	$(document).delegate('#actionPopoverModalBody .updateTask','click', function(){
		$(".popuploader").show();
		var $modal = $('#actionPopoverModalBody');
		var flag = true;
		var error = "";
		$modal.find(".custom-error").remove();
		var $remCat = $modal.find('.rem_cat');
		var $assignNote = $modal.find('.assignnote');
		var $taskGroup = $modal.find('.task_group');
		var $assignNoteId = $modal.find('.assign_note_id');
		var $assignClientId = $modal.find('.assign_client_id');
		var $popoverDateTime = $modal.find('.popoverdatetime');
		if(typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal($remCat) === '' : $remCat.val() == ''){
			$('.popuploader').hide();
			error="Assignee field is required.";
			$remCat.after("<span class='custom-error' role='alert'>"+error+"</span>");
			flag = false;
		}
		if($assignNote.val() == ''){
			$('.popuploader').hide();
			error="Note field is required.";
			$assignNote.after("<span class='custom-error' role='alert'>"+error+"</span>");
			flag = false;
		}
		if(typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal($taskGroup) === '' : $taskGroup.val() == ''){
			$('.popuploader').hide();
			error="Group field is required.";
			$taskGroup.after("<span class='custom-error' role='alert'>"+error+"</span>");
			flag = false;
		}
		if(flag){
			$.ajax({
				type:'post',
				url:"{{URL::to('/')}}/clients/updateaction/store",
				headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
				data: {
					note_id: $assignNoteId.val(),
					note_type:'action',
					description:$assignNote.val(),
					client_id:$assignClientId.val(),
					followup_datetime:$popoverDateTime.val(),
					assignee_name:$remCat.find(':selected').text(),
					rem_cat: typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal($remCat) : $remCat.val(),
					task_group: typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal($taskGroup) : $taskGroup.val()
				},
				success: function(response){
					$('.popuploader').hide();
					var obj = $.parseJSON(response);
					if(obj.success){
						var modalEl = document.getElementById('actionPopoverModal');
						if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
							var m = bootstrap.Modal.getInstance(modalEl);
							if (m) m.hide();
						} else {
							$('#actionPopoverModal').modal('hide');
						}
						location.reload();
					}else{
						showToast(obj.message || 'Update failed', 'error');
					}
				}
			});
		}
	});
});
</script>

@push('tinymce-scripts')
@include('partials.tinymce')
@endpush

@endsection
