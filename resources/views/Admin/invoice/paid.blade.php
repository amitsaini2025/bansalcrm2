@extends('layouts.admin')
@section('title', 'Paid Invoices')

@section('content')
<style>
.ag-space-between {
    justify-content: space-between;
}
.ag-align-center {
    align-items: center;
}
.ag-flex {
    display: flex;
}
.ag-align-start {
    align-items: flex-start;
}
.ag-flex-column {
    flex-direction: column;
}
.col-hr-1 {
    margin-right: 5px!important;
}
.text-semi-bold {
    font-weight: 600!important;
}
.small, small {
    font-size: 85%;
}
.ag-align-end {
    align-items: flex-end;
}
.paid-invoice-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 0.75rem;
    margin-bottom: 1rem;
}
.paid-invoice-toolbar .form-label {
    font-size: 0.8125rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}
.paid-invoice-export-group {
    display: flex;
    gap: 0.5rem;
    margin-left: auto;
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
							<h4>Paid Invoices</h4>
							<div class="card-header-action">
								
								
							</div>
						</div>
						<div class="card-body">
							
							<ul class="nav nav-pills" id="client_tabs" role="tablist">
								
								<li class="nav-item is_checked_clientn">
									<a class="nav-link" id="prospects-tab"  href="{{URL::to('/invoice/unpaid')}}" >Unpaid Invoices</a>
								</li>
								<li class="nav-item is_checked_clientn">
									<a class="nav-link active" id="clients-tab"  href="{{URL::to('/invoice/paid')}}" >Paid Invoices</a>
								</li>
							
							</ul> 
							<div class="tab-content" id="clientContent">								
								<div class="tab-pane fade show active" id="clients" role="tabpanel" aria-labelledby="clients-tab">
									<form method="get" action="{{ route('invoice.paid') }}" id="paid-invoice-filter-form" class="paid-invoice-toolbar">
										<div>
											<label for="issue_date_from" class="form-label">Issue Date From</label>
											<input type="text" id="issue_date_from" name="issue_date_from" class="form-control paid-invoice-datepicker" placeholder="DD/MM/YYYY" value="{{ request('issue_date_from') }}" autocomplete="off">
										</div>
										<div>
											<label for="issue_date_to" class="form-label">Issue Date To</label>
											<input type="text" id="issue_date_to" name="issue_date_to" class="form-control paid-invoice-datepicker" placeholder="DD/MM/YYYY" value="{{ request('issue_date_to') }}" autocomplete="off">
										</div>
										<div style="min-width: 220px;">
											<label for="partner_id" class="form-label">Partner Name</label>
											<select id="partner_id" name="partner_id" class="form-control tomselect paid-invoice-partner-select">
												<option value="">All Partners</option>
												@foreach($partners as $partner)
													<option value="{{ $partner->id }}" {{ (string) request('partner_id') === (string) $partner->id ? 'selected' : '' }}>{{ $partner->partner_name }}</option>
												@endforeach
											</select>
										</div>
										<div class="d-flex gap-2">
											<button type="submit" class="btn btn-primary">Filter</button>
											<a href="{{ route('invoice.paid') }}" class="btn btn-secondary">Clear</a>
										</div>
										<div class="paid-invoice-export-group">
											<button type="button" class="btn btn-success btn-sm" id="paid-invoice-export-excel">@icon('file-excel') Excel</button>
											<button type="button" class="btn btn-info btn-sm" id="paid-invoice-export-csv">@icon('file-csv') CSV</button>
										</div>
									</form>
									<div class="table-responsive common_table"> 
										<table class="table text_wrap">
											<thead>
												<tr> 
													
													<th>No</th>
													<th>Issue Date</th>
													<th>Client Name</th>
													<th>Created By</th>
													<th>Partner Name</th>
													<th>Product</th>
													<th>Amount</th>
													<th>Commission Claimed</th>
													<th>GST</th>
													<th>Net Fee Paid to Partner</th>
													<th>Client Reference</th>
													<th>Assignee</th>
													
													<th>Action</th>
													
												</tr> 
											</thead>
											@if(count($lists) >0)
											<tbody class="tdata">	
												<?php
												foreach($lists as $invoicelist){
																				$clientdata = \App\Models\Admin::where('id', $invoicelist->client_id)->first();
																				$admindata = \App\Models\Staff::find($invoicelist->user_id);
																											if($invoicelist->type == 3){
																		$applicationdata = null;
																		$partnerdata = null;
																		$productdata = null;
																		$assignedTo = null;
																	}else{
																		$applicationdata = \App\Models\Application::where('id', @$invoicelist->application_id)->first();
																		$partnerdata = \App\Models\Partner::where('id', @$applicationdata->partner_id)->first();
																		$productdata = \App\Models\Product::where('id', @$applicationdata->product_id)->first();
																		$assignedTo = isset($applicationdata->user_id) && $applicationdata->user_id ? \App\Models\Staff::find($applicationdata->user_id) : null;
																	}
																	$invoiceitemdetails = \App\Models\InvoiceDetail::where('invoice_id', $invoicelist->id)->orderby('id','ASC')->get();
																	$feeTotals = \App\Models\Invoice::sumLineFeeTotals($invoiceitemdetails);
																	$netamount = $feeTotals['netamount'];
																	$coom_amt = $feeTotals['coom_amt'];
																	$total_fee = $feeTotals['total_fee'];
																	$tax_amt = $feeTotals['tax_amt'];
																	$bonus_amt = $feeTotals['bonus_amt'];
																	$feepaid = $feeTotals['feepaid'];
																	
																	$paymentdetails = \App\Models\InvoicePayment::where('invoice_id', $invoicelist->id)->orderby('created_at', 'DESC')->get();
																	$amount_rec = 0;
																	foreach($paymentdetails as $paymentdetail){
																		$amount_rec += $paymentdetail->amount_rec;
																	} 
																	if($invoicelist->type == 1){
																		$totaldue = $total_fee - $coom_amt;
																	} if($invoicelist->type == 2){
																		$totaldue = $netamount - $amount_rec;
																	}else{
																		$totaldue = $netamount - $amount_rec;
																	}
																	?>
												<tr id="id_<?php echo $invoicelist->id; ?>">
													<td><?php echo $invoicelist->id; ?></td>
													<td style="white-space: initial;"><a href="{{URL::to('invoice/view/')}}/<?php echo $invoicelist->id; ?>">{{date('d/m/Y', strtotime($invoicelist->invoice_date))}} <?php //echo $invoicelist->invoice_date; ?></a></td>
													<td style="white-space: initial;"><a href="{{URL::to('clients/detail/')}}/{{base64_encode(convert_uuencode(@$clientdata->id))}}">{{$clientdata->first_name}} {{$clientdata->last_name}}</a></td>
													<td style="white-space: initial;"><a href="">{{$admindata->first_name}}</a></td>
													<td style="white-space: initial;">{{@$partnerdata->partner_name}}</td>
													<td style="white-space: initial;">{{@$productdata->name}}</td>
													<td>AUD <?php echo $invoicelist->net_fee_rec; ?></td>
													<td style="white-space: initial;">${{number_format($coom_amt, 2)}}</td>
													<td style="white-space: initial;">${{number_format($tax_amt, 2)}}</td>
													<td style="white-space: initial;">${{number_format($feepaid, 2)}}</td>
													<td style="white-space: initial;">{{@$clientdata->client_id ?? 'N/A'}}</td>
													<td style="white-space: initial;">{{@$assignedTo ? trim($assignedTo->first_name.' '.$assignedTo->last_name) : 'N/A'}}</td>
													
													<td>
													<a href="{{URL::to('invoice/view/')}}/<?php echo $invoicelist->id; ?>">@icon('eye')</a>
												<!--	<a href="">@icon('envelope')</a>-->
													
													<!--<a href="">@icon('dollor')</a>-->
													<!--<a href="">@icon('trash')</a>-->
													</td>
												</tr>
												<?php
												}
												?>
											</tbody>
											@else
												<tbody>
													<tr>
														<td style="text-align:center;" colspan="13">
															No Record found
														</td>
													</tr>
												</tbody>
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
@endsection
@section('scripts')
<script>
jQuery(document).ready(function ($) {
	if (typeof flatpickr !== 'undefined') {
		document.querySelectorAll('.paid-invoice-datepicker').forEach(function (el) {
			flatpickr(el, { dateFormat: 'd/m/Y', allowInput: true });
		});
	}

	function buildPaidInvoiceExportUrl(format) {
		var params = new URLSearchParams();
		params.set('format', format === 'xlsx' ? 'xlsx' : 'csv');

		var issueDateFrom = $('#issue_date_from').val();
		var issueDateTo = $('#issue_date_to').val();
		var partnerId = $('#partner_id').val();

		if (issueDateFrom) {
			params.set('issue_date_from', issueDateFrom);
		}
		if (issueDateTo) {
			params.set('issue_date_to', issueDateTo);
		}
		if (partnerId) {
			params.set('partner_id', partnerId);
		}

		return '{{ route('invoice.exportPaid') }}?' + params.toString();
	}

	$('#paid-invoice-export-excel').on('click', function () {
		window.location.href = buildPaidInvoiceExportUrl('xlsx');
	});

	$('#paid-invoice-export-csv').on('click', function () {
		window.location.href = buildPaidInvoiceExportUrl('csv');
	});
});
</script>
@endsection