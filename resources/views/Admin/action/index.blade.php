@extends('layouts.admin')
@section('title', 'Action')

@section('content')
<style>
.fc-event-container .fc-h-event{cursor:pointer;}
.sort_col a { color: #212529 !important; font-weight: 700 !important;}
.group_type_section a.active {color:black;}
.countAction {background: #1f1655;padding: 0px 5px;border-radius: 50%;color: #fff;margin-left: 5px;}
.table:not(.table-sm) thead th {background-color:#fff !important;height: 60px;vertical-align: middle;padding: 0 10px !important;color: #212529;font-size: 15px;}
.card .card-body table.table thead tr th {padding: 0px 10px!important;}
.uniqueClassName {text-align: center;}
.filter-checkbox{/*margin-left: 30px;*/}
.filter-checkbox:first-child{margin-left:0}
/*.table-responsive {width:98% !important; overflow-x: hidden !important;}*/
.card .card-body table.table tbody tr td {padding: 8px 5px!important;}
.table-responsive { overflow: hidden;}
.dataTables_wrapper .dataTables_filter{float: left !important;margin-left: 310px !important;}
.popover .popover-body {width: 500px !important; overflow: visible !important;}
.popover .ts-wrapper { z-index: 100001 !important; width: 100% !important; }
.popover .ts-dropdown { z-index: 100001 !important; }
.filter-wrapper div.active {color:blue !important;}
.btn-add-action-trigger { display: inline-block; white-space: nowrap; margin-left: 10px; margin-top: 5px; vertical-align: middle; }
.action-btn-group { display: flex; flex-wrap: nowrap; align-items: center; justify-content: center; gap: 4px; white-space: nowrap; }
.action-btn-group .action-icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; padding: 0; flex-shrink: 0; }
.action-btn-group .action-icon-btn .crm-icon,
.action-btn-group .action-icon-btn svg.lucide { pointer-events: none; flex-shrink: 0; }
.yajra-datatable .action-cell { white-space: nowrap; }
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
                            <h4>Action</h4>
                            <div class="card-header-action">
                            </div>
                            <ul class="nav nav-pills" id="client_tabs" role="tablist">
                                <li class="nav-item is_checked_clientn12">
									<a class="nav-link" id="assigned_by_me"  href="{{URL::to('/action/assigned-by-me')}}">Assigned by me</a>
								</li>

                                <li class="nav-item is_checked_clientn11">
									<a class="nav-link active" id="archived-tab"  href="{{URL::to('/action/completed')}}">Completed</a>
								</li>
                            </ul>
                        </div>

						<div class="card-body">
							<div class="tab-content" id="quotationContent">

                                <div class="row">
                                    <div class="col-md-12">
                                        <?php
                                        if(\Auth::user()->role == 1){
                                            $assigneesCount_All_type = \App\Models\Note::where('type','client')
                                            ->whereNotNull('client_id')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();

                                            $assigneesCount_call_type = \App\Models\Note::where('task_group','like','Call')
                                            ->where('type','client')->whereNotNull('client_id')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();

                                            $assigneesCount_Checklist_type = \App\Models\Note::where('task_group','like','Checklist')
                                            ->where('type','client')->whereNotNull('client_id')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();

                                            $assigneesCount_Review_type = \App\Models\Note::where('task_group','like','Review')
                                            ->where('type','client')->whereNotNull('client_id')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();

                                            $assigneesCount_Query_type = \App\Models\Note::where('task_group','like','Query')
                                            ->where('type','client')->whereNotNull('client_id')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();

                                            $assigneesCount_Urgent_type = \App\Models\Note::where('task_group','like','Urgent')
                                            ->where('type','client')->whereNotNull('client_id')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();

                                            $assigneesCount_Personal_Task_type = \App\Models\Note::where('task_group','like','Personal Task')
                                            ->where('type','client')->whereNotNull('client_id')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();
                                          
                                           $assigneesCount_Stage_type = \App\Models\Note::where('task_group','like','stage')
                                            ->where('type','client')->whereNotNull('client_id')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();

                                          $assigneesCount_partner_type = \App\Models\Note::where('task_group','like','partner')
                                            ->where('type','partner')->whereNotNull('client_id')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();


                                        } else {
                                            $assigneesCount_All_type = \App\Models\Note::where('assigned_to',Auth::user()->id)
                                            ->where('type','client')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();

                                            $assigneesCount_call_type = \App\Models\Note::where('task_group','like','Call')
                                            ->where('assigned_to',Auth::user()->id)->where('type','client')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();

                                            $assigneesCount_Checklist_type = \App\Models\Note::where('task_group','like','Checklist')
                                            ->where('assigned_to',Auth::user()->id)->where('type','client')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();

                                            $assigneesCount_Review_type = \App\Models\Note::where('task_group','like','Review')
                                            ->where('assigned_to',Auth::user()->id)->where('type','client')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();

                                            $assigneesCount_Query_type = \App\Models\Note::where('task_group','like','Query')
                                            ->where('assigned_to',Auth::user()->id)->where('type','client')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();

                                            $assigneesCount_Urgent_type = \App\Models\Note::where('task_group','like','Urgent')
                                            ->where('assigned_to',Auth::user()->id)->where('type','client')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();

                                            $assigneesCount_Personal_Task_type = \App\Models\Note::where('task_group','like','Personal Task')
                                            ->where('assigned_to',Auth::user()->id)->where('type','client')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();
                                        
                                            $assigneesCount_Stage_type = \App\Models\Note::where('task_group','like','stage')
                                            ->where('assigned_to',Auth::user()->id)->where('type','client')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();
                                        
                                          
                                          $assigneesCount_partner_type = \App\Models\Note::where('task_group','like','partner')
                                            ->where('assigned_to',Auth::user()->id)->where('type','partner')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();

                                        } ?>
                                        <div class="filter-wrapper">
                                            <div class="btn btn-light filter-checkbox active" data-val="All"> All <span class="countAction">{{ $assigneesCount_All_type }}</span></div>
                                            <div class="btn btn-light filter-checkbox" data-val="Call">@icon('phone') Call <span class="countAction">{{ $assigneesCount_call_type }}</span></div>
                                            <div class="btn btn-light filter-checkbox" data-val="Checklist"> @icon('bars') Checklist <span class="countAction">{{ $assigneesCount_Checklist_type }}</span></div>
                                            <div class="btn btn-light filter-checkbox" data-val="Review"> @icon('check') Review <span class="countAction">{{ $assigneesCount_Review_type }}</span></div>
                                            <div class="btn btn-light filter-checkbox" data-val="Query">@icon('question') Query <span class="countAction">{{ $assigneesCount_Query_type }}</span></div>
                                            <div class="btn btn-light filter-checkbox" data-val="Urgent"> @icon('flag') Urgent <span class="countAction">{{ $assigneesCount_Urgent_type }}</span></div>
                                            <div class="btn btn-light filter-checkbox" data-val="Personal Task"> @icon('tasks') Personal Task <span class="countAction">{{ $assigneesCount_Personal_Task_type }}</span></div>
                                          
                                          <div class="btn btn-light filter-checkbox" data-val="stage"> @icon('flag') Stage <span class="countAction">{{ $assigneesCount_Stage_type }}</span></div>
                                          
                                          <div class="btn btn-light filter-checkbox" style="margin-top:5px;" data-val="partner"> @icon('flag') Partner <span class="countAction">{{ $assigneesCount_partner_type }}</span></div>

                                            <label class="form-check form-check-inline ms-2 mt-2 align-middle" style="white-space: nowrap;" title="Future-dated Followup actions are hidden by default until their assign date">
                                                <input type="checkbox" class="form-check-input" id="include_scheduled_followups" value="1">
                                                Include scheduled follow-ups
                                            </label>



                                            <button type="button" class="btn btn-primary add_my_task btn-add-action-trigger" data-bs-container="body" data-role="popover" data-bs-placement="bottom" data-html="true" data-content="<div id=&quot;popover-content11&quot;>
                                                <h4 class=&quot;text-center&quot;>Add Action</h4>
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
                                                        <input type=&quot;text&quot; class=&quot;form-control f13 flatpickr-date&quot; placeholder=&quot;yyyy-mm-dd&quot; id=&quot;popoverdatetime&quot; value=&quot;<?php echo date('Y-m-d'); ?>&quot; name=&quot;popoverdate&quot; autocomplete=&quot;off&quot;>
                                                    </div>
                                                    <div class=&quot;clearfix&quot;></div>
                                                </div>
                                            </div>

                                            <input id=&quot;task_group&quot;  name=&quot;task_group&quot;  type=&quot;hidden&quot; value=&quot;Personal Task&quot;>


                                            <form class=&quot;form-inline mr-auto&quot;>
                                                <label for=&quot;inputSub3&quot; class=&quot;col-sm-3 control-label c6 f13&quot; style=&quot;margin-top:8px&quot;>Select Client</label>

                                                <div class=&quot;search-element&quot; style=&quot;margin-left: 5px;width:70%;&quot;>
                                                    <select id=&quot;assign_client_id&quot;  class=&quot;form-control js-data-example-ajaxccsearch__addmytask&quot; type=&quot;search&quot; placeholder=&quot;Search&quot; aria-label=&quot;Search&quot; data-width=&quot;200&quot; style=&quot;width:200px&quot;></select>
                                                    <button class=&quot;btn&quot; type=&quot;submit&quot;><?php echo htmlspecialchars(\App\Helpers\IconHelper::render('search'), ENT_QUOTES, 'UTF-8'); ?></button>
                                                </div>
                                            </form>

                                            <div class=&quot;box-footer&quot; style=&quot;padding:10px 0&quot;>
                                            <div class=&quot;row&quot;>
                                                <input type=&quot;hidden&quot; value=&quot;&quot; id=&quot;popoverrealdate&quot; name=&quot;popoverrealdate&quot; />
                                            </div>
                                            <div class=&quot;row text-center&quot;>
                                                <div class=&quot;col-md-12 text-center&quot;>
                                                <button  class=&quot;btn btn-info&quot; id=&quot;add_my_task&quot;>Add Action</button>
                                                </div>
                                            </div>
                                    </div>" data-original-title="" title="">Add Action</button>


                                        </div>


                                    </div>
                                </div>

                                <div class="tab-pane fade show active" id="active_quotation" role="tabpanel" aria-labelledby="active_quotation-tab">
									<div class="table-responsive common_table">
									    <!-- @if ($message = Session::get('success'))
										<div class="alert alert-success">
											<p>{{ $message }}</p>
										</div>
									    @endif   -->

                                        <table class="table table-bordered yajra-datatable">
                                            <thead>
                                                <tr>
                                                    <th>Sno</th>
                                                    <th>Done</th>
                                                    <th>Assigner Name</th>
                                                    <th>Client/Partner Reference</th>
                                                    <th>Assign Date</th>
                                                    <th>Type</th>
                                                    <th>Note</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>


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
            <div class="modal-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff;">
                <h5 class="modal-title" id="completeActionModalLabel">
                    @icon('check-circle') Complete Action
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="completeActionForm">
                    <input type="hidden" id="complete_action_id" name="action_id">
                    <input type="hidden" id="complete_client_id" name="client_id">
                    
                    <div class="mb-3">
                        <label class="text-muted small">Client/Partner</label>
                        <p id="complete-action-client" style="color: #4b5563; margin: 0; font-weight: 500;">
                            @icon('user') <span></span>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="completion_message" class="form-label">
                            Completion Message <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="completion_message" name="completion_message" rows="4" 
                                  placeholder="Enter completion message..." required></textarea>
                        <small class="text-muted">This message will be recorded in the activities section.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="submitCompleteAction">
                    @icon('check') Complete Action
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')

@push('scripts')
	@vite(['resources/js/pages/admin/popover-entry.js'])
@endpush


<script type="text/javascript">
window.ActionPagePopovers = window.ActionPagePopovers || {
    isManualTrigger: function(el) {
        return $(el).hasClass('update_task') || $(el).hasClass('reassign_task');
    },
    disposeElement: function(el) {
        if (window.bootstrap && window.bootstrap.Popover) {
            var inst = window.bootstrap.Popover.getInstance(el);
            if (inst) {
                try { inst.hide(); } catch (e) {}
                inst.dispose();
            }
        }
        $(el).removeData('bs.popover');
    },
    cleanupOrphanedDom: function() {
        $('body > .popover').filter(function() {
            return $(this).find('#add_my_task').length === 0;
        }).remove();
    },
    initElement: function(el) {
        var $el = $(el);
        this.disposeElement(el);
        var manual = this.isManualTrigger(el);
        $el.popover({
            html: true,
            sanitize: false,
            trigger: manual ? 'manual' : 'click',
            placement: $el.attr('data-bs-placement') || $el.attr('data-placement') || 'auto',
            container: $el.attr('data-bs-container') || $el.attr('data-container') || (manual ? 'body' : false)
        });
    },
    initTablePopovers: function() {
        this.cleanupOrphanedDom();
        $('.yajra-datatable [data-bs-toggle="popover"]').each(function() {
            window.ActionPagePopovers.initElement(this);
        });
        $('.yajra-datatable .update_task, .yajra-datatable .reassign_task').each(function() {
            window.ActionPagePopovers.initElement(this);
        });
    },
    hideRowPopovers: function(exceptEl) {
        $('.yajra-datatable .update_task, .yajra-datatable .reassign_task').each(function() {
            if (exceptEl && this === exceptEl) {
                return;
            }
            if (window.bootstrap && window.bootstrap.Popover) {
                var inst = window.bootstrap.Popover.getInstance(this);
                if (inst) {
                    inst.hide();
                }
            }
        });
    },
    openRowPopover: function(el) {
        this.hideRowPopovers(el);
        if (!window.bootstrap || !window.bootstrap.Popover || !window.bootstrap.Popover.getInstance(el)) {
            this.initElement(el);
        }
        $(el).popover('show');
        return $(el);
    }
};

$(function () {

    var table = $('.yajra-datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('action.list') }}",
            data: function (d) {
                d.include_scheduled_followups = $('#include_scheduled_followups').is(':checked') ? 1 : 0;
            }
        },
        columns: [
            {sWidth: '40px',className: "uniqueClassName", data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {sWidth: '50px',className: "uniqueClassName", data: 'done_task', name: 'done_task',orderable: false,searchable: false},
            {sWidth: '120px',data: 'assigner_name', name: 'assigner_name'},
            {sWidth: '130px',data: 'client_reference', name: 'client_reference'},
            {sWidth: '100px',data: 'assign_date', name: 'assign_date'},
            {sWidth: '80px',data: 'task_group', name: 'task_group'},
            {data: 'note_description', name: 'note_description'},
            {sWidth: '150px',className: "uniqueClassName action-cell", data: 'action',name: 'action',orderable: false,searchable: false},
        ],
       /* "fnDrawCallback": function (oSettings) {
            $('.yajra-datatable tbody tr').each(function () {
                var sTitle;
                var nTds = $('td', this);
                var s0 = $(nTds[6]).text();

                sTitle = s0;

                this.setAttribute('rel', 'tooltip');
                this.setAttribute('title', sTitle);
                //console.log(this);
                //console.log($(this));
                $(this).tooltip({
                    html: true
                });
            });
        },*/

        "fnDrawCallback": function() {
            if (window.ActionPagePopovers) {
                window.ActionPagePopovers.initTablePopovers();
            }
        },
        "bAutoWidth": false
    });

    $('#include_scheduled_followups').on('change', function () {
        table.ajax.reload(null, false);
    });

    //filter record on bais of task group
    /*$('.filter-checkbox').on('change', function(e){
        var searchTerms = []
        $.each($('.filter-checkbox'), function(i,elem){
            if($(elem).prop('checked')){
                searchTerms.push("^" + $(this).val() + "$")
            }
        })
        table.column(5).search(searchTerms.join('|'), true, false, true).draw();
    });*/
    
    //filter record on bais of task group
    $('.filter-checkbox').on('click', function(e){
        var searchTerms = []
        if($(this).attr('data-val') == 'All'){
            searchTerms = ["Call", "Checklist","Review","Query","Urgent","Personal Task","stage","partner"];
        } else {
            searchTerms.push("^" + $(this).attr('data-val') + "$")
        }
        $(".filter-checkbox").removeClass("active");
        $(this).addClass("active");
        //console.log(searchTerms);
        table.column(5).search(searchTerms.join('|'), true, false, true).draw();
    });


    // Delete record
    $('.yajra-datatable').on('click','.deleteNote',function(e){
        e.preventDefault();
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        var url = $(this).data('remote');

        crmConfirm("Are you sure?").then(function (deleteConfirm) {
            if (!deleteConfirm) {
                return;
            }
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    _method: 'DELETE',
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    submit: true
                },
                success: function(response) {
                    var obj = typeof response === 'string' ? $.parseJSON(response) : response;
                    if (obj && obj.success) {
                        showToast(obj.message || 'Activity deleted successfully.', 'success');
                        $('.yajra-datatable').DataTable().draw(false);
                        if (typeof getallactivities === 'function') getallactivities();
                        if (typeof getallnotes === 'function') getallnotes();
                    } else {
                        showToast((obj && obj.message) || 'Failed to delete activity.', 'error');
                    }
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Failed to delete activity.';
                    showToast(msg, 'error');
                }
            });
        });
    });
});

</script>

<script>
jQuery(document).ready(function($){

    //$('[data-bs-toggle="tooltip"]').tooltip();

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
        e.stopImmediatePropagation();

        var $btn = window.ActionPagePopovers
            ? window.ActionPagePopovers.openRowPopover(this)
            : $(this);
        var assignedto = $btn.attr('data-assignedto');
        // Description text for textarea prefill (legacy data-noteid fallback if present)
        var note_description = $btn.attr('data-description');
        if (note_description === undefined) {
            note_description = $btn.attr('data-noteid') || '';
        }
        var task_id = $btn.attr('data-taskid');
        var taskgroup_id = $btn.attr('data-taskgroupid');
        var followupdate_id = $btn.attr('data-followupdate');
        var folowDateArr = followupdate_id.split(" ");
        var finalDate = folowDateArr[0];
        
        // Wait for popover to be shown, then set form values
        var popoverShown = false;
        var setFormValues = function() {
            if (popoverShown) return;
            popoverShown = true;
            
            // Find the visible popover element (Bootstrap 5 creates .popover elements)
            var $popover = $('.popover:visible').last();
            if ($popover.length) {
                // Load assignee list via AJAX and set in popover
                $.ajax({
                    type:'post',
                    url:"{{URL::to('/')}}/action/assignee-list",
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: {assignedto:assignedto},
                    success: function(response){
                        var obj = $.parseJSON(response);
                        if (window.ActionPopoverTomSelect) {
                            ActionPopoverTomSelect.refreshAssigneeSelect($popover.find('#rem_cat')[0], obj.message, $popover[0]);
                        } else {
                            $popover.find('#rem_cat').html(obj.message);
                        }
                    }
                });
                
                // Set form values within the visible popover
                $popover.find('#assignnote').val(note_description);
                $popover.find('#assign_note_id').val(task_id);
                $popover.find('#task_group').val(taskgroup_id);
                $popover.find('#popoverdatetime').val(finalDate);
            } else {
                // Fallback: set values globally (for compatibility)
                $('#assignnote').val(note_description);
                $('#assign_note_id').val(task_id);
                $('#task_group').val(taskgroup_id);
                $('#popoverdatetime').val(finalDate);
                
                // Load assignee list
                $.ajax({
                    type:'post',
                    url:"{{URL::to('/')}}/action/assignee-list",
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: {assignedto:assignedto},
                    success: function(response){
                        var obj = $.parseJSON(response);
                        if (window.ActionPopoverTomSelect) {
                            ActionPopoverTomSelect.refreshAssigneeSelect($('#rem_cat')[0], obj.message, document.body);
                        } else {
                            $('#rem_cat').html(obj.message);
                        }
                    }
                });
            }
        };
        
        // Listen for Bootstrap 5 popover shown event
        $btn.one('shown.bs.popover', setFormValues);
        
        // Fallback timeout in case event doesn't fire
        setTimeout(setFormValues, 200);
    });


    //update task
    $(document).delegate('.update_task', 'click', function(e){
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        var $btn = window.ActionPagePopovers
            ? window.ActionPagePopovers.openRowPopover(this)
            : $(this);
        var assignedto = $btn.attr('data-assignedto');
        // Description text for textarea prefill (legacy data-noteid fallback if present)
        var note_description = $btn.attr('data-description');
        if (note_description === undefined) {
            note_description = $btn.attr('data-noteid') || '';
        }
        var task_id = $btn.attr('data-taskid');
        var taskgroup_id = $btn.attr('data-taskgroupid');
        var followupdate_id = $btn.attr('data-followupdate');
        var folowDateArr = followupdate_id.split(" ");
        var finalDate = folowDateArr[0];
        
        // Wait for popover to be shown, then set form values
        // Use Bootstrap 5 event or jQuery event
        var popoverShown = false;
        var setFormValues = function() {
            if (popoverShown) return;
            popoverShown = true;
            
            // Find the visible popover element (Bootstrap 5 creates .popover elements)
            var $popover = $('.popover:visible').last();
            if ($popover.length) {
                // Set form values within the visible popover
                $popover.find('#rem_cat').each(function() {
                    var $select = $(this);
                    // Load assignee list via AJAX
                    $.ajax({
                        type:'post',
                        url:"{{URL::to('/')}}/action/assignee-list",
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        data: {assignedto:assignedto},
                        success: function(response){
                            var obj = $.parseJSON(response);
                            if (window.ActionPopoverTomSelect) {
                                ActionPopoverTomSelect.refreshAssigneeSelect($select[0], obj.message, $popover[0]);
                            } else {
                                $select.html(obj.message);
                            }
                        }
                    });
                });
                
                $popover.find('#assignnote').val(note_description);
                $popover.find('#assign_note_id').val(task_id);
                $popover.find('#task_group').val(taskgroup_id);
                $popover.find('#popoverdatetime').val(finalDate);
            } else {
                // Fallback: set values globally (for compatibility)
                $('#rem_cat').html(''); // Will be set by AJAX
                $('#assignnote').val(note_description);
                $('#assign_note_id').val(task_id);
                $('#task_group').val(taskgroup_id);
                $('#popoverdatetime').val(finalDate);
                
                // Load assignee list
                $.ajax({
                    type:'post',
                    url:"{{URL::to('/')}}/action/assignee-list",
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: {assignedto:assignedto},
                    success: function(response){
                        var obj = $.parseJSON(response);
                        if (window.ActionPopoverTomSelect) {
                            ActionPopoverTomSelect.refreshAssigneeSelect($('#rem_cat')[0], obj.message, document.body);
                        } else {
                            $('#rem_cat').html(obj.message);
                        }
                    }
                });
            }
        };
        
        // Listen for Bootstrap 5 popover shown event
        $btn.one('shown.bs.popover', setFormValues);
        
        // Fallback timeout in case event doesn't fire
        setTimeout(setFormValues, 200);
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
                    //console.log(response);
                    //var obj = $.parseJSON(response);
                    //location.reload();
                    $('.yajra-datatable').DataTable().draw(false);
                }
			});
        }
	});

    //Function is used for complete the task
	$(document).delegate('.complete_task', 'click', function(e){
		e.preventDefault();
		var row_id = $(this).attr('data-id');
        if(row_id !=""){
            // Get row data from DataTable
            var table = $('.yajra-datatable').DataTable();
            var rowData = table.row($(this).closest('tr')).data();
            
            // Get client ID and name from the row
            var clientId = '';
            var clientName = 'N/A';
            
            // Try to extract client info from the row
            if (rowData && rowData.client_reference) {
                // Extract client name from the HTML (first line before <br>)
                var tempDiv = $('<div>').html(rowData.client_reference);
                clientName = tempDiv.text().split('\n')[0].trim() || 'N/A';
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
                _token: '{{ csrf_token() }}',
                id: actionId,
                client_id: clientId,
                completion_message: message
            },
            success: function(response) {
                // Re-enable button
                $('#submitCompleteAction').prop('disabled', false).html(crmIcon('check') + ' Complete Action');
                
                // Check response status
                if (response && response.status) {
                    // Close modal
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        var modalElement = document.getElementById('completeActionModal');
                        var modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        }
                    } else {
                        $('#completeActionModal').modal('hide');
                    }
                    
                    // Refresh DataTable
                    $('.yajra-datatable').DataTable().draw(false);
                    
                    // Show success message
                    showToast(response.message || 'Action completed successfully!', 'success');
                } else {
                    // Handle error response
                    showToast(response.message || 'Failed to complete action. Please try again.', 'error');
                }
            },
            error: function(xhr) {
                // Re-enable button
                $('#submitCompleteAction').prop('disabled', false).html(crmIcon('check') + ' Complete Action');
                
                var errorMsg = 'An error occurred. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showToast(errorMsg, 'error');
            }
        });
    });

    //re-assign task or update task
    $(document).delegate('#assignUser','click', function(){
		$(".popuploader").show();
		var flag = true;
		var error = "";
		$(".custom-error").remove();
		
		// Find the visible popover and get values from within it
		var $popover = $('.popover:visible').last();
		var $remCat, $assignNote, $taskGroup, $assignNoteId, $assignClientId, $popoverDateTime;
		
		if ($popover.length) {
			// Get form elements from within the popover
			$remCat = $popover.find('#rem_cat');
			$assignNote = $popover.find('#assignnote');
			$taskGroup = $popover.find('#task_group');
			$assignNoteId = $popover.find('#assign_note_id');
			$assignClientId = $popover.find('#assign_client_id');
			$popoverDateTime = $popover.find('#popoverdatetime');
		} else {
			// Fallback to global selectors
			$remCat = $('#rem_cat');
			$assignNote = $('#assignnote');
			$taskGroup = $('#task_group');
			$assignNoteId = $('#assign_note_id');
			$assignClientId = $('#assign_client_id');
			$popoverDateTime = $('#popoverdatetime');
		}
		
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
			// Get values from the correct form elements
			var noteId = $assignNoteId.val();
			var noteDescription = $assignNote.val();
			var clientId = $assignClientId.val();
			var followupDateTime = $popoverDateTime.val();
			var assigneeName = typeof actionPopoverAssigneeLabel === 'function' ? actionPopoverAssigneeLabel($remCat) : $remCat.find(':selected').text();
			var remCat = typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal($remCat) : $remCat.val();
			var taskGroup = typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal($taskGroup) : $taskGroup.val();
			
			if (!noteId) {
				$('.popuploader').hide();
				showToast('Note ID is missing. Please try again.', 'warning');
				return;
			}
			
			$.ajax({
				type:'post',
                url:"{{URL::to('/')}}/clients/reassignaction/store",
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {
					note_id: noteId,
					note_type: 'action',
					description: noteDescription,
					client_id: clientId,
					followup_datetime: followupDateTime,
					assignee_name: assigneeName,
					rem_cat: remCat,
					task_group: taskGroup
				},
                success: function(response){
                    console.log(response);
                    $('.popuploader').hide();
                    var obj = $.parseJSON(response);
                    if(obj.success){
                        // Hide all popovers
                        $("[data-role=popover], [data-bs-toggle=popover]").each(function(){
                            $(this).popover('hide');
                        });
                        if (window.ActionPagePopovers) {
                            window.ActionPagePopovers.cleanupOrphanedDom();
                        }
                        //location.reload();
                        $('.yajra-datatable').DataTable().draw(false);
                        if(typeof getallactivities === 'function') getallactivities();
                        if(typeof getallnotes === 'function') getallnotes();
                    } else{
                        showToast(obj.message || 'An error occurred', 'error');
                        //location.reload();
                        $('.yajra-datatable').DataTable().draw(false);
                    }
                },
				error: function(xhr, status, error) {
					$('.popuploader').hide();
					console.error('AJAX Error:', error);
					showToast('Error: ' + (xhr.responseJSON?.message || error || 'Failed to reassign task'), 'error');
				}
			});
		}else{
			$(".popuploader").hide();
		}
	});

    //update task
    $(document).delegate('#updateTask','click', function(){
		$(".popuploader").show();
		var flag = true;
		var error ="";
		$(".custom-error").remove();

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
                url:"{{URL::to('/')}}/clients/updateaction/store",
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {note_id:$('#assign_note_id').val(),note_type:'action',description:$('#assignnote').val(),client_id:$('#assign_client_id').val(),followup_datetime:$('#popoverdatetime').val(),assignee_name:(typeof actionPopoverAssigneeLabel === 'function' ? actionPopoverAssigneeLabel('#rem_cat') : $('#rem_cat :selected').text()),rem_cat:(typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal('#rem_cat') : $('#rem_cat option:selected').val()),task_group:(typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal('#task_group') : $('#task_group option:selected').val())},
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
                        //location.reload();
                        $('.yajra-datatable').DataTable().draw(false);
                        getallactivities();
                        getallnotes();
                    } else{
                        showToast(obj.message, 'error');
                        $('.yajra-datatable').DataTable().draw(false);
                        //location.reload();
                    }
                }
			});
		}else{
			$("#loader").hide();
		}
	});

    function getAddActionPopoverClientId() {
        var $popover = $('.popover.show, .popover:visible').last();
        var $clientSelect = $popover.length ? $popover.find('#assign_client_id') : $('#assign_client_id');
        if (!$clientSelect.length) {
            return '';
        }
        var el = $clientSelect[0];
        if (window.RecipientSelect && typeof RecipientSelect.getValue === 'function') {
            var vals = RecipientSelect.getValue(el);
            if (Array.isArray(vals) && vals.length) {
                return vals[0];
            }
            return '';
        }
        if (typeof getEnhancedSelectValue === 'function') {
            return getEnhancedSelectValue(el) || '';
        }
        return $clientSelect.val() || '';
    }

    //Add Personal Task
    $(document).delegate('#add_my_task','click', function(){
		$(".popuploader").show();
		var flag = true;
		var error ="";
		$(".custom-error").remove();
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
        if(flag){
			var clientId = getAddActionPopoverClientId();

			$.ajax({
				type:'post',
                url:"{{URL::to('/')}}/clients/personalaction/store",
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {note_type:'action',description:$('#assignnote').val(),client_id:clientId,followup_datetime:$('#popoverdatetime').val(),assignee_name:(typeof actionPopoverAssigneeLabel === 'function' ? actionPopoverAssigneeLabel('#rem_cat') : $('#rem_cat :selected').text()),rem_cat:(typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal('#rem_cat') : $('#rem_cat option:selected').val()),task_group:(typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal('#task_group') : $('#task_group').val())},
                success: function(response){
                    //console.log(response);
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
                        showToast(obj.message || 'Action added successfully.', 'success');
                        //location.reload();
                        $('.yajra-datatable').DataTable().draw(false);
                        getallactivities();
                        getallnotes();
                    } else{
                        showToast(obj.message, 'error');
                        $('.yajra-datatable').DataTable().draw(false);
                        //location.reload();
                    }
                },
                error: function(xhr, status, error) {
                    $('.popuploader').hide();
                    console.error('AJAX Error:', error);
                    console.error('Response:', xhr.responseText);
                    var errorMessage = 'An error occurred. Please try again.';
                    try {
                        var errorObj = $.parseJSON(xhr.responseText);
                        if(errorObj.message) {
                            errorMessage = errorObj.message;
                        }
                    } catch(e) {
                        errorMessage = xhr.responseText || error;
                    }
                    showToast(errorMessage, 'error');
                }
			});
		}else{
			$("#loader").hide();
		}
	});

	// Stub functions to prevent errors - these functions are called but not needed on this page
	function getallactivities(){
		// Function stub - activities are not displayed on the action index page
		// This function is defined in detail pages but called here after task operations
	}

	function getallnotes(){
		// Function stub - notes are not displayed on the action index page
		// This function is defined in detail pages but called here after task operations
	}
});
</script>

@push('tinymce-scripts')
@include('partials.tinymce')
@endpush

@endsection
