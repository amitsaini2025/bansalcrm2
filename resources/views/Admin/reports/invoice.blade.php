@extends('layouts.admin')
@section('title', 'Invoice Report')

@section('content')

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
							<h4>Invoice Report</h4>
							<div class="card-header-action">
								<div class="drop_table_data" style="display: inline-block;margin-right: 10px;"> 
									<button type="button" class="btn btn-primary dropdown-toggle">@icon('columns')</button>
									<div class="dropdown_list invoice_report_list">  
										<label class="dropdown-option all"><input type="checkbox" value="all" checked /> Display All</label>
										<label class="dropdown-option"><input type="checkbox" value="3" checked /> Created By</label>
										<label class="dropdown-option"><input type="checkbox" value="5" checked /> Partner</label>
										<label class="dropdown-option"><input type="checkbox" value="6" checked /> Product</label>
										<label class="dropdown-option"><input type="checkbox" value="7" checked /> Workflow</label>
										<label class="dropdown-option"><input type="checkbox" value="8" checked /> Currency</label>
										<label class="dropdown-option"><input type="checkbox" value="9" checked /> Invoice Type</label>
										<label class="dropdown-option"><input type="checkbox" value="10" checked /> Invoice Due Date</label>
										<label class="dropdown-option"><input type="checkbox" value="11" checked /> Invoice No</label>
										<label class="dropdown-option"><input type="checkbox" value="12" checked /> Task Added By</label>
										<label class="dropdown-option"><input type="checkbox" value="13" checked /> Created At</label>
										<label class="dropdown-option"><input type="checkbox" value="14" checked /> Status</label>
										<label class="dropdown-option"><input type="checkbox" value="15" checked /> Total Fee</label>
										<label class="dropdown-option"><input type="checkbox" value="16" checked /> Commission Amount</label>
										<label class="dropdown-option"><input type="checkbox" value="17" checked /> Income Amount</label>
										<label class="dropdown-option"><input type="checkbox" value="18" checked /> Discount Amount</label>
										<label class="dropdown-option"><input type="checkbox" value="19" checked /> Income Sharing</label>
										<label class="dropdown-option"><input type="checkbox" value="20" checked /> Other Payables</label>
										<label class="dropdown-option"><input type="checkbox" value="21" checked /> Net Income</label>
										<label class="dropdown-option"><input type="checkbox" value="22" checked /> Tax Received</label>
										<label class="dropdown-option"><input type="checkbox" value="23" checked /> Tax Paid</label>
										<label class="dropdown-option"><input type="checkbox" value="24" checked /> Paid Amount</label>
										<label class="dropdown-option"><input type="checkbox" value="25" checked /> Due Amount</label>
										<label class="dropdown-option"><input type="checkbox" value="26" checked /> Last Payment Date</label>
									</div>
								</div>
							</div>
						</div>
						<div class="card-body">
							<div class="table-responsive common_table invoice_report_data"> 
								<table class="table text_wrap">
									<thead> 
										<tr>
											<th>Client ID</th>
											<th>Client</th>
											<th>Created By</th>
											<th>Assignee</th>
											<th>Partner</th>
											<th>Product</th>
											<th>Workflow</th>
											<th>Currency</th>
											<th>Invoice Type</th>
											<th>Invoice Due Date</th>
											<th>Invoice No</th>
											<th>Task Added By</th>
											<th>Created At</th>
											<th>Status</th>
											<th>Total Fee</th>
											<th>Commission Amount</th>
											<th>Income Amount</th>
											<th>Discount Amount</th>
											<th>Income Sharing</th>
											<th>Other Payables</th>
											<th>Net Income</th>
											<th>Tax Received</th>
											<th>Tax Paid</th>
											<th>Paid Amount</th>
											<th>Due Amount</th>
											<th>Last Payment Date</th>
										</tr> 
									</thead>
									@if(count($lists) >0)
									<tbody class="tdata">	
										@if(@$totalData !== 0)
										@foreach (@$lists as $list)
										<?php
											$client = \App\Models\Admin::where('id', '=', $list->client_id)->first();
											$productdetail = \App\Models\Product::where('id', $list->product_id)->first();
											$partnerdetail = \App\Models\Partner::where('id', $list->partner_id)->first();	
											$partnerdetail = \App\Models\Partner::where('id', $list->partner_id)->first();	
											$Appldetail = \App\Models\Application::where('id', $list->branch)->first();
											$invoicedetail = \App\Models\InvoiceDetail::where('id', $list->branch)->first();
										?>
										<tr id="id_{{@$list->id}}">
											<td>{{@$client->id}}</td>
											<td>
												<a href="{{URL::to('/clients/detail/'.base64_encode(convert_uuencode(@$client->id)))}}">{{@$client->first_name}} {{@$client->last_name}}</a>
											</td>
											<td>-</td>
											<td>{{@$client->assignee}}</td>
											<td>{{@$partnerdetail->partner_name}}</td> 
											<td>{{@$productdetail->name}}</td> 
											<td>{{@$Appldetail->workflow}}</td>
											<td>{{ str_limit(@$list->currency ?: 'AUD', '50', '...') }}</td>
											<td>{{ @$list->type == "" ? config('constants.empty') : str_limit(@$list->type, '50', '...') }}</td>
											<td>{{ @$list->due_date == "" ? config('constants.empty') : date('d/m/Y',strtotime(@$list->due_date)) }}</td>
											<td>-</td>
											<td>{{ @$list->created_at == "" ? config('constants.empty') : date('d/m/Y',strtotime(@$list->created_at)) }}</td>
											<td>
												<?php
												if($list->status == 1){
													echo '<span style="color: rgb(113, 204, 83); width: 84px;">Paid</span>';
												}else{
													echo '<span style="color: rgb(255, 173, 0); width: 84px;">Unpaid</span>';
												}
												?>
											</td>
											<td>{{@$invoicedetail->total_fee}}</td>
											<td>{{@$invoicedetail->comm_amt}}</td>
											<td>-</td>
											<td>-</td>
											<td>-</td>
											<td>-</td>
											<td>-</td>
											<td>-</td>
											<td>{{ @$list->net_incone == "" ? config('constants.empty') : str_limit(@$list->net_incone, '50', '...') }}</td>
											<td>-</td>
											<td>-</td>
											<td>-</td>
											<td>-</td>
										</tr>
										@endforeach	
										@endif										
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
					</div>
				</div>
			</div>
		</div>
	</section>
</div>

@endsection