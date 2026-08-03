@extends('layouts.admin')
@section('title', 'Commission Report')

@section('content')
<style>
.fc-event-container .fc-h-event{cursor:pointer;}
#openassigneview .modal-body ul.navbar-nav li .dropdown-menu{transform: none!important; top:40px!important;}
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
.dataTables_wrapper .dataTables_filter{float: right !important;margin-right: 3px !important;}
.popover .popover-body {width: 500px !important;}
.filter-wrapper div.active {color:blue !important;}
.dataTables_wrapper .dt-buttons {float: none;margin-left: 25px;margin-top: 29px;}
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
                            <h4>Commission Report</h4>
                            <div class="card-header-action">
                            </div>
                        </div>

						<div class="card-body">
							<div class="tab-content" id="quotationContent">
                                <div class="tab-pane fade show active" id="active_quotation" role="tabpanel" aria-labelledby="active_quotation-tab">
									<div class="table-responsive common_table">
									    <!-- @if ($message = Session::get('success'))
										<div class="alert alert-success">
											<p>{{ $message }}</p>
										</div>
									    @endif-->

                                        <table class="table table-bordered yajra-datatable">
                                            <thead>
                                                <tr>
                                                    <th>Sno</th>
                                                    <th>CRM Ref</th>
                                                    <th>Student Name</th>
                                                    <th>Date of Birth</th>
                                                    <th>Student Id</th>
                                                    <th>College Name</th>
                                                    <th>Course Name</th>
                                                    <th>Start Date</th>
                                                    <th>End Date</th>
                                                    <th>Total Course Fee</th>
                                                    <th>Enrolment Fee</th>
                                                    <th>Material Fee</th>
                                                    <th>Tution Fee</th>

                                                    <!--<th>Total Anticipated Fee</th>-->
                                                    <th>Fee Reported by College</th>
                                                    <th>Total Bonus</th>
                                                    <th>Bonus Pending</th>
                                                    <!--<th>Commission as per anticipated fee</th>-->
                                                    <th>Commission as per Fee reported</th>
                                                    <!--<th>Commission payable as per anticipated fee</th>-->
                                                    <th>Commission paid as per fee Reported</th>
                                                    <th>Commission Pending</th>

                                                    <th>Student Status</th>
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
		</div>
	</section>
</div>
<script type="text/javascript">
$(function () {
    var oldExportAction = function (self, e, dt, button, config) {
        if (button[0].className.indexOf('buttons-excel') >= 0) {
            if ($.fn.dataTable.ext.buttons.excelHtml5.available(dt, config)) {
                $.fn.dataTable.ext.buttons.excelHtml5.action.call(self, e, dt, button, config);
            }
            else {
                $.fn.dataTable.ext.buttons.excelFlash.action.call(self, e, dt, button, config);
            }
        } else if (button[0].className.indexOf('buttons-print') >= 0) {
            $.fn.dataTable.ext.buttons.print.action(e, dt, button, config);
        }
    };

    var newExportAction = function (e, dt, button, config) {
        var self = this;
        var oldStart = dt.settings()[0]._iDisplayStart;

        dt.one('preXhr', function (e, s, data) {
            // Just this once, load all data from the server...
            data.start = 0;
            data.length = 2147483647;

            dt.one('preDraw', function (e, settings) {
                // Call the original action function
                oldExportAction(self, e, dt, button, config);

                dt.one('preXhr', function (e, s, data) {
                    // DataTables thinks the first item displayed is index 0, but we're not drawing that.
                    // Set the property to what it was before exporting.
                    settings._iDisplayStart = oldStart;
                    data.start = oldStart;
                });

                // Reload the grid with the original page. Otherwise, API functions like table.cell(this) don't work properly.
                setTimeout(dt.ajax.reload, 0);

                // Prevent rendering of the full data to the DOM
                return false;
            });
        });

        // Requery the server with the new one-time export settings
        dt.ajax.reload();
    };

	$.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    var table = $('.yajra-datatable').DataTable({
        processing: true,
        serverSide: true,
        
        ajax: {
            url: "{{ route('clients.getcommissionreport') }}",
            type: "POST"
        },
        dom: 'Blfrtip', // Defines the position of the buttons in the DOM
        buttons: [{
          extend: 'excel',
          action: newExportAction
        }],
        scrollX: true,
        columns: [
            {sWidth: '40px',className: "uniqueClassName", data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {sWidth: '130px',data: 'client_reference', name: 'client_reference'},
            {sWidth: '130px',data: 'student_name', name: 'student_name'},
            {sWidth: '130px',data: 'dob', name: 'dob'},

            {sWidth: '130px',data: 'student_id', name: 'student_id'},
            {sWidth: '130px',data: 'college_name', name: 'college_name'},
            {sWidth: '130px',data: 'course_name', name: 'course_name'},
            {sWidth: '130px',data: 'start_date', name: 'start_date'},
            {sWidth: '130px',data: 'end_date', name: 'end_date'},

            {sWidth: '130px',data: 'total_course_fee_amount', name: 'total_course_fee_amount'},
            {sWidth: '130px',data: 'enrolment_fee_amount', name: 'enrolment_fee_amount'},
            {sWidth: '130px',data: 'material_fees', name: 'material_fees'},
            {sWidth: '130px',data: 'tution_fees', name: 'tution_fees'},

            //{sWidth: '130px',data: 'total_anticipated_fee', name: 'total_anticipated_fee'},
            {sWidth: '130px',data: 'fee_reported_by_college', name: 'fee_reported_by_college'},
            {sWidth: '130px',data: 'bonus_amount', name: 'bonus_amount'},
            {sWidth: '130px',data: 'bonus_pending_amount', name: 'bonus_pending_amount'},
            //{sWidth: '130px',data: 'commission_as_per_anticipated_fee', name: 'commission_as_per_anticipated_fee'},
            {sWidth: '130px',data: 'commission_as_per_fee_reported', name: 'commission_as_per_fee_reported'},
            //{sWidth: '130px',data: 'commission_payable_as_per_anticipated_fee', name: 'commission_payable_as_per_anticipated_fee'},
            {sWidth: '130px',data: 'commission_paid_as_per_fee_reported', name: 'commission_paid_as_per_fee_reported'},
            {sWidth: '130px',data: 'commission_pending', name: 'commission_pending'},

            {sWidth: '130px',data: 'student_status', name: 'student_status'}
        ],
        "bAutoWidth": false
    });
});
</script>
@endsection
