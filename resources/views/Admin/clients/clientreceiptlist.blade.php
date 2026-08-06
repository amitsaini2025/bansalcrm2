@extends('layouts.admin')
@section('title', 'Clients Receipt List')

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

.ag-list__title{background-color: #fcfcfc;border: 1px solid #f2f2f2;padding: 0.8rem 1.2rem;}
.ag-list__item{font-size: 12px;margin: 0;padding: 0.8rem 2.6rem;}
.filter_panel {background: #f7f7f7;margin-bottom: 10px;border: 1pxsolid #eee;display: none;}
.card .card-body .filter_panel { padding: 20px;}
.strike-through {text-decoration: line-through;}
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
							<h4>All Clients Receipt List</h4>
							<div class="card-header-action">
                                <?php
                                /* 1  //super admin
                                1346 //ajay@bansalimmigration.com.au
                                541 //arun@bansaleducation.com.au
                                1298 //manager@bansalimmigration.com.au*/
                                //if(Auth::user()->id == 1 || Auth::user()->id == 1346  || Auth::user()->id == 541 || Auth::user()->id == 1298)
                                //{
                                ?>
                                <ul class="nav nav-pills" id="client_tabs" role="tablist" style="float: left;">
                                    <li class="nav-item Validate_Receipt" style="display:bock;">
                                        <a class="btn btn-primary" href="javascript:;">Validate Receipt</a>
                                    </li>
                                </ul>
                                <?php //} ?>

                                <a href="javascript:;" class="btn btn-theme btn-theme-sm filter_btn" style="float: right;">@icon('filter') Filter</a>
                            </div>
						</div>
						<div class="card-body">
                            <div class="filter_panel" style="padding: 10px !important;">
                                <h4>Filter Receipts</h4>
                                <form action="{{URL::to('/clients/clientreceiptlist')}}" method="get">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="pnr" class="col-form-label">Client ID</label>
                                                {!! Form::text('client_id', Request::get('client_id'), array('class' => 'form-control', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'Client ID', 'id' => 'client_id' ))  !!}
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="company_name" class="col-form-label">Name</label>
                                                {!! Form::text('name', Request::get('name'), array('class' => 'form-control agent_company_name', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'Name', 'id' => 'name' ))  !!}
                                            </div>
                                        </div>
                                      
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="trans_date" class="col-form-label">Trans Date</label>
                                                {!! Form::text('trans_date', Request::get('trans_date'), array('class' => 'form-control trans_date_fields', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'Trans Date', 'id' => 'trans_date' ))  !!}
                                            </div>
                                        </div>
                                  	</div>
                                  	<div class="row">
                                    	<div class="col-md-4">
                                            <div class="form-group">
                                                <label for="deposit_amount" class="col-form-label">Amount</label>
                                                {!! Form::text('deposit_amount', Request::get('deposit_amount'), array('class' => 'form-control', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'Amount', 'id' => 'amount' ))  !!}
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-start" style="margin-top: 38px;">
                                            <div class="form-group">
                                                {!! Form::submit('Search', ['class'=>'btn btn-primary btn-theme-lg' ])  !!}
                                                <a class="btn btn-info" href="{{URL::to('/clients/clientreceiptlist')}}">Reset</a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                                <div class="tab-pane fade show active" id="clients" role="tabpanel" aria-labelledby="clients-tab">
									<div class="table-responsive common_table client_table_data">
										<table class="table text_wrap">
											<thead>
												<tr>
                                                    <!--<th class="text-center" style="width:30px;">
                                                        <div class="custom-checkbox custom-checkbox-table custom-control">
                                                            <input type="checkbox" data-checkboxes="mygroup" data-checkbox-role="dad" class="custom-control-input" id="checkbox-all">
                                                            <label for="checkbox-all" class="custom-control-label">&nbsp;</label>
                                                        </div>
                                                    </th>-->
                                                    <th></th>
                                                    <th>Receipt No</th>
                                                    <th>Client Id</th>
                                                    <th>Name</th>
                                                    <th>Trans. Date</th>
                                                    <th>Entry Date</th>
                                                    <th>Trans. No</th>
                                                    <th>Payment Method</th>
                                                    <th>Amount</th>
                                                    <th>Created By</th>
													<th>Receipt Validate</th>
                                                    <th>Validate By</th> <!-- New field -->
                                                </tr>
											</thead>

											<tbody class="tdata">
												@if(@$totalData !== 0)
													<?php $i=0; ?>
												@foreach (@$lists as $list)
                                                <?php
                                                $client_info = \App\Models\Admin::select('id','first_name','last_name','client_id')->where('id', $list->client_id)->first();
                                                if(isset($list->voided_or_validated_by) && $list->voided_or_validated_by != ""){
                                                    $validate_by = \App\Models\Staff::select('id','first_name','last_name')->find($list->voided_or_validated_by) ?? \App\Models\Admin::select('id','first_name','last_name')->find($list->voided_or_validated_by);
                                                    $validate_by_full_name = $validate_by->first_name.' '.$validate_by->last_name;
                                                } else {
                                                    $validate_by_full_name = "-";
                                                }
                                                //Created By Name
                                                if(isset($list->user_id) && $list->user_id != ""){
                                                    $created_info = \App\Models\Staff::select('id','first_name','last_name')->find($list->user_id) ?? \App\Models\Admin::select('id','first_name','last_name')->find($list->user_id);
                                                    $created_by_name = $created_info->first_name.' '.$created_info->last_name;
                                                } else {
                                                    $created_by_name = "-";
                                                }
                                                ?>
                                                <tr id="id_{{@$list->id}}">
                                                    <?php
                                                    if( $list->payment_method == 'Cash' )
                                                    {
                                                        /* 1  //super admin
                                                        1346 //ajay@bansalimmigration.com.au
                                                        541 //arun@bansaleducation.com.au
                                                        1298 //manager@bansalimmigration.com.au*/
                                                        if ( Auth::user()->id == 1 || Auth::user()->id == 1346  || Auth::user()->id == 541 || Auth::user()->id == 1298 )
                                                        { ?>
                                                            <td style="white-space: initial;" class="text-center">
                                                                <div class="custom-checkbox custom-control">
                                                                    <input data-id="{{@$list->id}}" data-email="{{@$list->email}}" data-name="{{@$list->first_name}} {{@$list->last_name}}" data-receiptid="{{@$list->receipt_id}}"  type="checkbox" data-checkboxes="mygroup" class="cb-element custom-control-input  your-checkbox" id="checkbox-{{$i}}">
                                                                    <label for="checkbox-{{$i}}" class="custom-control-label">&nbsp;</label>
                                                                </div>
                                                            </td>
                                                        <?php
                                                        }
                                                        else
                                                        { ?>
                                                        <td style="white-space: initial;" class="text-center">

                                                        </td>
                                                    <?php }
                                                    }
                                                    else
                                                    { //if payment method is not Cash ?>
                                                        <td style="white-space: initial;" class="text-center">
                                                            <div class="custom-checkbox custom-control">
                                                                <input data-id="{{@$list->id}}" data-email="{{@$list->email}}" data-name="{{@$list->first_name}} {{@$list->last_name}}" data-receiptid="{{@$list->receipt_id}}"  type="checkbox" data-checkboxes="mygroup" class="cb-element custom-control-input  your-checkbox" id="checkbox-{{$i}}">
                                                                <label for="checkbox-{{$i}}" class="custom-control-label">&nbsp;</label>
                                                            </div>
                                                        </td>
                                                    <?php
                                                    } ?>

                                                    <td><?php echo $list->receipt_id;?></td>
                                                     <td>
                                                        <a href="{{URL::to('/clients/detail/'.base64_encode(convert_uuencode(@$list->client_id)))}}"><?php echo $client_info->client_id;?> </a>
                                                    </td>
                                                    <td><?php echo $client_info->first_name.' '.$client_info->last_name;?></td>
                                                    <td><?php echo $list->trans_date;?></td>
                                                    <td><?php echo $list->entry_date;?></td>
                                                    <td><?php echo $list->trans_no;?></td>
                                                    <td><?php echo $list->payment_method;?></td>
                                                    <td id="deposit_{{@$list->id}}"><?php echo "$".$list->total_deposit_amount;?></td>
                                                    <td><?php echo $created_by_name;?></td>
                                                    <?php
                                                    if($list->validate_receipt == 1) {
                                                        $color = "color:blue;";
                                                    } else {
                                                        $color = "color:red;";
                                                    }
                                                    ?>
                                                    <td id="validate_{{@$list->id}}" style="font-size: 17px; <?php echo $color;?>"><?php if($list->validate_receipt == 1) { echo "Yes";} else { echo "No"; };?></td>
                                                    <td id="validateby_{{@$list->id}}"><?php echo $validate_by_full_name;?></td> <!-- New field data -->
                                                </tr>
												<?php $i++; ?>
												@endforeach
											</tbody>
											@else
											<tbody>
												<tr>
													<td style="text-align:center;" colspan="17">
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
@endsection
@section('scripts')
<script>
jQuery(document).ready(function($){
  
	if (typeof flatpickr !== 'undefined') {
		$('.trans_date_fields').each(function() {
			flatpickr(this, {
				dateFormat: 'd/m/Y',
				allowInput: true
			});
		});
	}
  
    $('.filter_btn').on('click', function(){
		$('.filter_panel').slideToggle();
	});

    $("[data-checkboxes]").each(function () {
        var me = $(this),
        group = me.data('checkboxes'),
        role = me.data('checkbox-role');

        me.change(function () {
            var all = $('[data-checkboxes="' + group + '"]:not([data-checkbox-role="dad"])'),
            checked = $('[data-checkboxes="' + group + '"]:not([data-checkbox-role="dad"]):checked'),
            dad = $('[data-checkboxes="' + group + '"][data-checkbox-role="dad"]'),
            total = all.length,
            checked_length = checked.length;
            if (role == 'dad') {
                if (me.is(':checked')) {
                    all.prop('checked', true);

                } else {
                    all.prop('checked', false);

                }
            } else {
                if (checked_length >= total) {
                    dad.prop('checked', true);
                    $('.is_checked_client').show();
                    $('.is_checked_clientn').hide();
                } else {
                    dad.prop('checked', false);
                    $('.is_checked_client').hide();
                    $('.is_checked_clientn').show();
                }
            }

        });
    });

    var clickedReceiptIds = [];
    $(document).delegate('.your-checkbox', 'click', function(){
        var clicked_receipt_id = $(this).data('receiptid');
        if ($(this).is(':checked')) {
            clickedReceiptIds.push(clicked_receipt_id);
        } else {
            var index2 = clickedReceiptIds.indexOf(clicked_receipt_id);
            if (index2 !== -1) {
                clickedReceiptIds.splice(index2, 1);
            }
        }
    });

    //validate receipt
    $(document).delegate('.Validate_Receipt', 'click', function(){
        if ( clickedReceiptIds.length > 0)
        {
            var mergeStr = "Are you sure want to validate these receipt?";
            crmConfirm(mergeStr).then(function (ok) {
                if (!ok) {
                    return;
                }
                $.ajax({
                    type:'post',
                    url:"{{URL::to('/')}}/validate_receipt",
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data: {clickedReceiptIds:clickedReceiptIds,receipt_type:1},
                    success: function(response){
                        var obj = $.parseJSON(response);
                        //location.reload(true);
                        var record_data = obj.record_data;
                        $.each(record_data, function(index, subArray) {
                            //console.log('index=='+index);
                            //console.log('subArray=='+subArray.id);
                            $('#validate_'+subArray.id).text("Yes");
                            $('#validate_'+subArray.id).css("color",'blue');
                            var firstName = (subArray.first_name == null ? '' : String(subArray.first_name)).trim();
                            var lastName = (subArray.last_name == null ? '' : String(subArray.last_name)).trim();
                            var validateby_full_name = (firstName || lastName) ? (firstName + ' ' + lastName).trim() : '-';
                            $('#validateby_'+subArray.id).text(validateby_full_name);
                        });
                    }
                });
            });
        } else {
            alert('Please select atleast 1 receipt.');
        }
    });


    $('.cb-element').change(function () {
        if ($('.cb-element:checked').length == $('.cb-element').length){
            $('#checkbox-all').prop('checked',true);
        } else {
            $('#checkbox-all').prop('checked',false);
        }
    });
});
</script>
@endsection

