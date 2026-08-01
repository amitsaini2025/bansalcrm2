@extends('layouts.admin')
@section('title', 'Partner Detail')

@section('content')
@php
	// Use activeTab resolved by the controller if already passed, otherwise resolve from request.
	// The controller resolves it early so that Blade query guards (@if $activeTab === 'student' etc.)
	// can skip heavy queries for inactive tabs.
	if (!isset($activeTab)) {
		$allowedTabs = [
			'application',
			'partner-activities',
			'products',
			'branches',
			'agreements',
			'noteterm',
			'documents',
			'notuseddocuments',
			'accounts',
			'promotions',
			'student',
			'invoice',
			'email-v2'
		];
		$tabAliases = [
			'activities' => 'partner-activities',
			'notestrm' => 'noteterm'
		];
		$allowedTabSlugs = array_unique(array_merge($allowedTabs, array_keys($tabAliases)));
		$requestedTab = Request::route('tab') ?? Request::get('tab');
		if (empty($requestedTab) || !in_array($requestedTab, $allowedTabSlugs, true)) {
			$requestedTab = 'application';
		}
		$activeTab = $tabAliases[$requestedTab] ?? $requestedTab;
	}
	$partnerFullWidthTabs = ['email-v2', 'documents', 'notuseddocuments'];
	$hidePartnerLeftSidebar = in_array($activeTab, $partnerFullWidthTabs, true);
@endphp
<link rel="stylesheet" href="{{ asset('css/client-detail.css') }}?v={{ (config('app.asset_version') ? config('app.asset_version').'-' : '') . filemtime(public_path('css/client-detail.css')) }}">
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

.zippyLabel{background-color: #e8e8e8; line-height: 1;display: inline-block;color: rgba(0,0,0,.6);font-weight: 700; border: 0 solid transparent; font-size: 10px;padding: 3px; }
.accordion .accordion-header.app_green{background-color: #54b24b;color: #fff;}
.accordion .accordion-header.app_green .accord_hover a{color: #fff!important;}
.accordion .accordion-header.app_blue{background-color: rgba(3,169,244,.1);color: #03a9f4;}
/* Export buttons styling */
.dt-buttons {
    margin-bottom: 15px;
    display: inline-flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 5px;
}
.dt-buttons .btn {
    margin: 0;
    white-space: nowrap;
    line-height: 1.5;
    align-self: center;
}
.dt-buttons .btn i {
    margin-right: 5px;
}
/* Partner student tab: single-row DataTables toolbar */
.student-dt-toolbar-host {
    overflow: visible;
    position: relative;
    z-index: 20;
    margin-bottom: 12px;
}
.student-dt-toolbar-host .student-dt-toolbar {
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 0;
}
.student-dt-toolbar-host .student-dt-toolbar > [class*="col-"] {
    flex: 0 0 auto;
    width: auto;
    max-width: none;
    padding-left: 0;
    padding-right: 0;
}
.student-dt-toolbar-host .student-dt-toolbar .dataTables_length,
.student-dt-toolbar-host .student-dt-toolbar .dt-buttons {
    margin-bottom: 0;
}
.student-dt-toolbar-host .student-dt-toolbar .dataTables_length label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 0;
    white-space: nowrap;
}
.student-dt-toolbar-host .student-dt-toolbar .dt-buttons {
    display: inline-flex;
    align-items: center;
    flex-wrap: nowrap;
    gap: 6px;
    float: none;
}
.student-dt-toolbar-host .student-dt-toolbar .dataTables_filter {
    float: none !important;
    text-align: left !important;
    margin: 0;
}
.student-dt-toolbar-host .student-dt-filter-controls {
    display: inline-flex;
    align-items: center;
    flex-wrap: nowrap;
    gap: 10px;
}
.student-dt-toolbar-host .student-dt-filter-controls > label:first-child {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 0;
    white-space: nowrap;
}
.student-dt-toolbar-host .student-dt-status-filter {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 0;
    white-space: nowrap;
}
.student-dt-toolbar-host .student-dt-status-filter select {
    width: auto;
    min-width: 120px;
}
.student-dt-toolbar-host .student-dt-columns,
.student_table_panel .student_drop_table_data,
.student_table_panel1 .student_drop_table_data1 {
    position: relative;
}
.student_table_panel .student_drop_table_data .dropdown_list,
.student_table_panel1 .student_drop_table_data1 .dropdown_list {
    z-index: 1050;
    overflow-y: auto;
}
.student-dt-table-scroll {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
/* Full width when left partner profile sidebar is hidden (Emails, Documents, etc.) */
.partner-main-content.email-tab-full-width {
    flex: 0 0 100% !important;
    max-width: 100% !important;
}
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
							<h4>Partner Detail</h4>
							<div class="card-header-action">
                              
								<a href="{{route('partners.index')}}" class="btn btn-primary">Partner List</a>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-3 col-md-3 col-lg-3 partner-left-sidebar <?php echo $hidePartnerLeftSidebar ? 'd-none' : ''; ?>">
					<div class="card author-box left_section_upper">
						<div class="card-body">
							<div class="author-box-center">
							<span class="author-avtar" style="background: rgb(68, 182, 174);"><b>{{substr($fetchedData->partner_name, 0, 1)}}</b></span>
								<div class="clearfix"></div>
								<div class="author-box-name"> 
									<a href="#">{{$fetchedData->partner_name}}</a>
								</div>
								<div class="author-mail_sms">
									<a href="#" title="Compose SMS">@icon('comment-alt')</a>
									<a href="javascript:;" data-id="{{@$fetchedData->id}}" data-email="{{@$fetchedData->email}}" data-name="{{@$fetchedData->partner_name}}" class="clientemail" title="Compose Mail">@icon('envelope')</a> 
									<a href="{{ route('partners.edit', ['id' => base64_encode(convert_uuencode($fetchedData->id))], false) }}" title="Edit" aria-label="Edit partner">@icon('edit')</a>
									
									@if($fetchedData->is_archived == 0)
										<a class="arcivedval" href="javascript:;" onclick="arcivedAction({{$fetchedData->id}}, 'partners')" title="Archive">@icon('archive')</a>
									@else
										<a class="arcivedval" style="background-color:red;" href="javascript:;" onclick="arcivedAction({{$fetchedData->id}}, 'partners')" title="UnArchive">{!! \App\Helpers\IconHelper::render('archive', 'solid', ['attrs' => ['style' => 'color: #fff;']]) !!}</a>
									@endif
								</div>
							</div>
                            
                            <p><button type="button" style="border-radius:30px;" class="btn btn-primary btn-block openpartneraction" title="Actions"> Action</button></p>
                        
                            
						</div>
					</div>
					<div class="card left_section_lower">
						<div class="card-header">
							<h4>General Information</h4>
						</div>
						<div class="card-body">
							<p class="clearfix"> 
								<span class="float-start">Phone No:</span>
								<span class="float-end text-muted">
                                    @if(!empty($partnerSidebarPhones) && count($partnerSidebarPhones) > 0)
                                        @foreach($partnerSidebarPhones as $conVal)
                                            @php
                                                $partner_country_code = $conVal->partner_country_code ?? '';
                                                $phoneType = $conVal->partner_phone_type ?? '';
                                            @endphp
                                            @if($phoneType !== '' && $phoneType !== 'Not In Use')
                                                {!! $partner_country_code . $conVal->partner_phone . '(' . e($phoneType) . ')<br/>' !!}
                                            @else
                                                {!! $partner_country_code . $conVal->partner_phone . '<br/>' !!}
                                            @endif
                                        @endforeach
                                    @else
                                        N/A
                                    @endif
                                </span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Fax:</span>
								<span class="float-end text-muted">{{$fetchedData->fax}}</span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Email:</span>
								<span class="float-end text-muted">
                                    @if(!empty($partnerSidebarEmails) && count($partnerSidebarEmails) > 0)
                                        @foreach($partnerSidebarEmails as $emailVal)
                                            @php $emailType = $emailVal->partner_email_type ?? ''; @endphp
                                            @if($emailType !== '' && $emailType !== 'Not In Use')
                                                {!! e($emailVal->partner_email) . '(' . e($emailType) . ')<br/>' !!}
                                            @else
                                                {!! e($emailVal->partner_email) . '<br/>' !!}
                                            @endif
                                        @endforeach
                                    @else
                                        N/A
                                    @endif
                                </span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Address:</span>
								<span class="float-end text-muted">{{$fetchedData->address}}</span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Website:</span>
								<span class="float-end text-muted">{{$fetchedData->website}}</span>
							</p>
							<?php
						
							$partnerServiceWorkflow = \App\Models\Workflow::where('id', $fetchedData->service_workflow)->first();
							?>
							
							<p class="clearfix"> 
								<span class="float-start">Services:</span>
								<span class="float-end text-muted">{{@$partnerServiceWorkflow->name}}</span>
							</p>
							
							<p class="clearfix"> 
								<span class="float-start">Added On:</span>
								<span class="float-end text-muted">{{date('d/m/Y', strtotime($fetchedData->created_at))}}</span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Business Registration Number:</span>
								<span class="float-end text-muted">{{$fetchedData->business_reg_no}}</span>
							</p>
							<p class="clearfix"> 
								<span class="float-start">Currency code:</span>
								<span class="float-end text-muted">AUD</span>
							</p>
							
						</div>
					</div>
				</div>
				<div class="col-9 col-md-9 col-lg-9 partner-main-content <?php echo $hidePartnerLeftSidebar ? 'email-tab-full-width' : ''; ?>">
					<div class="card">
						<div class="card-body">
							@php
								$partnerId = base64_encode(convert_uuencode($fetchedData->id));
								$partnerRouteId = rawurlencode($partnerId);
								$partnerDetailBase = url('/partners/detail/'.$partnerRouteId);
							@endphp
							<ul class="nav nav-pills" id="partner_tabs" role="tablist">
								<li class="nav-item">
									<a class="nav-link {{ $activeTab === 'application' ? 'active' : '' }}" href="{{ $partnerDetailBase }}" id="application-tab" role="tab" aria-controls="application" aria-selected="{{ $activeTab === 'application' ? 'true' : 'false' }}">Applications</a>
								</li>
                              
                                <li class="nav-item">
									<a class="nav-link {{ $activeTab === 'partner-activities' ? 'active' : '' }}" href="{{ $partnerDetailBase.'/activities' }}" id="partner-activities-tab" role="tab" aria-controls="partner-activities" aria-selected="{{ $activeTab === 'partner-activities' ? 'true' : 'false' }}">Activities</a>
                                </li>
                              
								<li class="nav-item">
									<a class="nav-link {{ $activeTab === 'products' ? 'active' : '' }}" href="{{ $partnerDetailBase.'/products' }}" id="products-tab" role="tab" aria-controls="products" aria-selected="{{ $activeTab === 'products' ? 'true' : 'false' }}">Products</a>
								</li>
								<li class="nav-item">
									<a class="nav-link {{ $activeTab === 'branches' ? 'active' : '' }}" href="{{ $partnerDetailBase.'/branches' }}" id="branches-tab" role="tab" aria-controls="branches" aria-selected="{{ $activeTab === 'branches' ? 'true' : 'false' }}">Branches</a>
								</li>
								<li class="nav-item">
									<a class="nav-link {{ $activeTab === 'agreements' ? 'active' : '' }}" href="{{ $partnerDetailBase.'/agreements' }}" id="agreements-tab" role="tab" aria-controls="agreements" aria-selected="{{ $activeTab === 'agreements' ? 'true' : 'false' }}">Agreements</a>
								</li>
								<li class="nav-item">
									<a class="nav-link {{ $activeTab === 'noteterm' ? 'active' : '' }}" href="{{ $partnerDetailBase.'/notestrm' }}" id="noteterm-tab" role="tab" aria-controls="noteterm" aria-selected="{{ $activeTab === 'noteterm' ? 'true' : 'false' }}">Notes & Terms</a>
								</li>
								<li class="nav-item">
									<a class="nav-link {{ $activeTab === 'documents' ? 'active' : '' }}" href="{{ $partnerDetailBase.'/documents' }}" id="documents-tab" role="tab" aria-controls="documents" aria-selected="{{ $activeTab === 'documents' ? 'true' : 'false' }}">Documents</a>
								</li>
								<li class="nav-item">
									<a class="nav-link {{ $activeTab === 'notuseddocuments' ? 'active' : '' }}" href="{{ $partnerDetailBase.'/notuseddocuments' }}" id="notuseddocuments-tab" role="tab" aria-controls="notuseddocuments" aria-selected="{{ $activeTab === 'notuseddocuments' ? 'true' : 'false' }}">Not Used Documents</a>
								</li>
								<li class="nav-item">
									<a class="nav-link {{ $activeTab === 'accounts' ? 'active' : '' }}" href="{{ $partnerDetailBase.'/accounts' }}" id="accounts-tab" role="tab" aria-controls="accounts" aria-selected="{{ $activeTab === 'accounts' ? 'true' : 'false' }}">Accounts</a>
								</li>
							<li class="nav-item">
								<a class="nav-link {{ $activeTab === 'email-v2' ? 'active' : '' }}" href="{{ $partnerDetailBase.'/email-v2' }}" id="email-v2-tab" role="tab" aria-controls="email-v2" aria-selected="{{ $activeTab === 'email-v2' ? 'true' : 'false' }}">Emails</a>
							</li>
								<li class="nav-item">
									<a class="nav-link {{ $activeTab === 'promotions' ? 'active' : '' }}" href="{{ $partnerDetailBase.'/promotions' }}" id="promotions-tab" role="tab" aria-controls="promotions" aria-selected="{{ $activeTab === 'promotions' ? 'true' : 'false' }}">Promotions</a>
								</li>
                              
                                <li class="nav-item">
									<a class="nav-link {{ $activeTab === 'student' ? 'active' : '' }}" href="{{ $partnerDetailBase.'/student' }}" id="student-tab" role="tab" aria-controls="student" aria-selected="{{ $activeTab === 'student' ? 'true' : 'false' }}">Student</a>
								</li>
                              
                                <li class="nav-item">
									<a class="nav-link {{ $activeTab === 'invoice' ? 'active' : '' }}" href="{{ $partnerDetailBase.'/invoice' }}" id="invoice-tab" role="tab" aria-controls="invoice" aria-selected="{{ $activeTab === 'invoice' ? 'true' : 'false' }}">Invoice</a>
								</li>
							</ul> 
							<div class="tab-content" id="partnerContent" style="padding-top:15px;">
								<div class="tab-pane fade {{ $activeTab === 'application' ? 'show active' : '' }}" id="application" role="tabpanel" aria-labelledby="application-tab">
									@if($activeTab === 'application')
									@include('Admin.partners.tabs.application')
									@endif
								</div>
                              
                              
                                 <div class="tab-pane fade <?php echo ($activeTab === 'partner-activities') ? 'show active' : ''; ?>" id="partner-activities" role="tabpanel" aria-labelledby="partner-activities-tab">
									@if($activeTab === 'partner-activities')
                                    <div class="activities">
                                        <p class="text-muted mb-0 activities-loading">Loading activities...</p>
                                    </div>
									@endif
                                </div>
                              
								<div class="tab-pane fade <?php echo ($activeTab === 'products') ? 'show active' : ''; ?>" id="products" role="tabpanel" aria-labelledby="products-tab">
									@if($activeTab === 'products')
									<div class="card-header-action text-end" style="padding-bottom:15px;">
										<a href="{{route('products.create')}}" class="btn btn-primary">@icon('plus') Add</a>
									</div>
									<div class="table-responsive"> 
										<table class="table text_wrap">
											<thead>
												<tr>
													<th>Product Name</th>
													<th>Sync</th>
													<th>Branches</th>
													<th>In Progress</th>
													<th></th>
												</tr> 
											</thead>
											<tbody class="applicationtdata">
											<?php
											$products = \App\Models\Product::where('partner', $fetchedData->id)->orderby('created_at', 'DESC')->get();
											foreach($products as $product){
											?>
												<tr id="id_{{@$product->id}}">
													<td>{{$product->name}}</td>
													<td></td>
													<?php
													$bname = array();
													if($product->branches != ''){
														$branches = \App\Models\PartnerBranch::whereIn('id', explode(',',$product->branches))->get();
														foreach($branches as $b){
															$bname[] = $b->name;
														}
													}
													?>
													<td>{{implode(', ', $bname)}}</td>
													
													<?php
													$countapplication = \App\Models\Application::where('product_id', $product->id)->where('status', 0)->count();
													?>
													<td>{{$countapplication}}</td>
													<td>
														<div class="dropdown d-inline">
															<button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
															<div class="dropdown-menu"> 
																<a class="dropdown-item has-icon" href="{{URL::to('/products/detail/'.base64_encode(convert_uuencode(@$product->id)))}}">@icon('eye', 'regular') View</a>
																<a class="dropdown-item has-icon" href="{{ route('products.edit', ['id' => base64_encode(convert_uuencode($product->id))], false) }}">@icon('edit', 'regular') Edit</a>
																<a class="dropdown-item has-icon" href="javascript:;" onClick="deleteAction({{@$product->id}}, 'products')">@icon('trash') Delete</a>
															</div>
														</div>
													</td>
												</tr>
											<?php } ?>
											</tbody>
										</table> 
									</div>	
									<div class="clearfix"></div>
									@endif
								</div>
								<div class="tab-pane fade <?php echo ($activeTab === 'branches') ? 'show active' : ''; ?>" id="branches" role="tabpanel" aria-labelledby="branches-tab">
									@if($activeTab === 'branches')
									<div class="card-header-action text-end" style="padding-bottom:15px;">
										<a href="javascript:;" class="btn btn-primary openbranchnew">@icon('plus') Add</a> 
									</div>
									<div class="branch_term_list">
									<?php
										$branchesquery = \App\Models\PartnerBranch::where('partner_id', $fetchedData->id)->orderby('created_at', 'DESC');
										$branchescount = $branchesquery->count();
										$branches = $branchesquery->get();
										if($branchescount !== 0){
										foreach($branches as $branch){
									?>
										<div class="branch_col" id="contact_"> 
											<div class="branch_content">
												<h4><?php echo $branch->name; ?></h4>
												<div class="" style="margin-top: 15px!important;">
													<p>{!! \App\Helpers\IconHelper::render('map-marker-alt', 'solid', ['attrs' => ['style' => 'margin-right: 10px!important;']]) !!} <?php echo $branch->city; ?>, <?php echo $branch->a; ?></p>
												</div>
											</div>
											<div class="extra_content">
												<div class="left">
													<p>{!! \App\Helpers\IconHelper::render('phone', 'solid', ['attrs' => ['style' => 'margin-right: 20px!important;']]) !!} <?php if($branch->phone != ''){ echo $branch->phone; }else{ echo '-'; } ?></p>
													<p>{!! \App\Helpers\IconHelper::render('envelope', 'regular', ['attrs' => ['style' => 'margin-right: 20px!important;']]) !!} <?php if($branch->email != ''){ echo $branch->email; }else{ echo '-'; } ?></p>
												</div>  
												<div class="right">
													<div class="dropdown d-inline dropdown_ellipsis_icon">
														<a class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">@icon('ellipsis-v')</a>
														<div class="dropdown-menu">
															<a class="dropdown-item openbranchform" data-id="{{$branch->id}}" href="javascript:;">Edit</a>
															<a data-id="{{$branch->id}}" data-href="deletebranch" class="dropdown-item deletenote" href="javascript:;" >Delete</a>
														</div>
													</div>
												</div>
											</div>
										</div>
										<?php }
										}else{
											?>
											<h4>No Record Found</h4>
											<?php
										}
										?>
									</div>	
									<div class="clearfix"></div>
									@endif
								</div>	
								<div class="tab-pane fade <?php echo ($activeTab === 'agreements') ? 'show active' : ''; ?>" id="agreements" role="tabpanel" aria-labelledby="agreements-tab">
									@if($activeTab === 'agreements')
									<!-- Add Agreement Button -->
									<div class="card-header-action text-end" style="padding-bottom:15px;">
										<a href="javascript:;" class="btn btn-primary add_agreement_btn">@icon('plus') Add Agreement</a>
									</div>
									
									<!-- Agreements List -->
									<div class="agreements_list_container">
										<div class="table-responsive">
											<table class="table table-striped" id="agreements_table">
												<thead>
													<tr>
														<th>#</th>
														<th>Contract Start</th>
														<th>Contract Expiry</th>
														<th>Commission %</th>
														<th>Bonus</th>
														<th>Representing Regions</th>
														<th>Description</th>
														<th>Status</th>
														<th>Document</th>
														<th>Actions</th>
													</tr>
												</thead>
												<tbody id="agreements_tbody">
													<!-- Agreements will be loaded here via AJAX -->
												</tbody>
											</table>
										</div>
									</div>
									
									<div class="clearfix"></div>
									@endif
								</div>
								
								<div class="tab-pane fade <?php echo ($activeTab === 'noteterm') ? 'show active' : ''; ?>" id="noteterm" role="tabpanel" aria-labelledby="noteterm-tab">
									@if($activeTab === 'noteterm')
									<div class="card-header-action text-end" style="padding-bottom:15px;">
										<a href="javascript:;" datatype="note" class="create_note btn btn-primary">@icon('plus') Add</a>
									</div>
									<div class="note_term_list">
										<p class="text-muted mb-0 notes-loading">Loading notes...</p>
									</div>
									<div class="clearfix"></div>
									@endif
								</div>
								<div class="tab-pane fade <?php echo ($activeTab === 'documents') ? 'show active' : ''; ?>" id="documents" role="tabpanel" aria-labelledby="documents-tab">
									@if($activeTab === 'documents')
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
													@forelse($partnerDocuments ?? [] as $fetch)
													@php
														$admin = $fetch->user;
														$addedByInfo = ($admin ? $admin->first_name : 'Unknown') . ' on ' . date('d/m/Y', strtotime($fetch->created_at));
														$checklist = !empty($fetch->checklist) ? $fetch->checklist : (!empty($fetch->file_name) ? $fetch->file_name : 'N/A');
													@endphp
														<tr class="drow document-row" id="id_{{$fetch->id}}" 
															data-doc-id="<?php echo $fetch->id;?>"
															data-checklist-name="<?php echo htmlspecialchars($checklist, ENT_QUOTES, 'UTF-8'); ?>"
															data-file-name="<?php echo htmlspecialchars($fetch->file_name, ENT_QUOTES, 'UTF-8'); ?>"
															data-file-type="<?php echo htmlspecialchars($fetch->filetype, ENT_QUOTES, 'UTF-8'); ?>"
															data-myfile="<?php echo htmlspecialchars($fetch->myfile, ENT_QUOTES, 'UTF-8'); ?>"
															data-myfile-key="<?php echo isset($fetch->myfile_key) ? htmlspecialchars($fetch->myfile_key, ENT_QUOTES, 'UTF-8') : ''; ?>"
															data-doc-type="<?php echo htmlspecialchars($fetch->doc_type ? $fetch->doc_type : 'documents', ENT_QUOTES, 'UTF-8'); ?>"
															data-user-role="<?php echo Auth::user()->role; ?>"
															title="Added by: <?php echo htmlspecialchars($addedByInfo, ENT_QUOTES, 'UTF-8'); ?>"
															style="cursor: context-menu;">
															<td style="white-space: initial;">
																<div data-id="<?php echo $fetch->id;?>" data-personalchecklistname="<?php echo $checklist; ?>" class="personalchecklist-row">
																	<span><?php echo $checklist; ?></span>
																</div>
															</td>
															<td style="white-space: initial;">
																<?php
																if( isset($fetch->file_name) && $fetch->file_name !=""){ ?>
																	<div data-id="{{$fetch->id}}" data-name="<?php echo $fetch->file_name; ?>" class="doc-row">
																		<?php
																		if (isset($fetch->myfile_key) && $fetch->myfile_key != '') {
																			$inlinePreviewUrl = asset($fetch->myfile);
																		} else {
																			$docType = $fetch->doc_type ? $fetch->doc_type : 'documents';
																			if (filter_var($fetch->myfile, FILTER_VALIDATE_URL)) {
																				$inlinePreviewUrl = asset($fetch->myfile);
																			} else {
																				$url = 'https://'.env('AWS_BUCKET').'.s3.'. env('AWS_DEFAULT_REGION') . '.amazonaws.com/';
																				$inlinePreviewUrl = asset($url.$fetchedData->id.'/'.$docType.'/'.$fetch->myfile);
																			}
																		}
																		?>
																			<a href="javascript:void(0);"
																				data-preview-type="<?php echo htmlspecialchars($fetch->filetype, ENT_QUOTES, 'UTF-8'); ?>"
																				data-preview-url="<?php echo htmlspecialchars($inlinePreviewUrl, ENT_QUOTES, 'UTF-8'); ?>"
																				data-preview-container="preview-container-alldocumentlist-partner">
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
																			<input type="hidden" name="type" value="partner">
																			<input type="hidden" name="doctype" value="documents">
																			<input type="hidden" name="checklist" value="<?php echo htmlspecialchars($fetch->checklist ?? '', ENT_QUOTES, 'UTF-8'); ?>">
																			<a href="javascript:;" class="btn btn-primary">@icon('plus') Add Document</a>
																			<input class="alldocupload" data-fileid="<?php echo $fetch->id;?>" type="file" name="document_upload"/>
																		</form>
																	</div>
																<?php
																}?>
															</td>
														</tr>
													@empty
														<tr><td colspan="2">No documents found.</td></tr>
													@endforelse
												</tbody>
											</table>
										</div>
									</div>
									<div class="grid_data allgriddata">
										@foreach($partnerDocuments ?? [] as $fetch)
											@php $admin = $fetch->user; @endphp
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
																<a class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">@icon('ellipsis-v')</a>
																<div class="dropdown-menu">
																	<?php $docType = $fetch->doc_type ? $fetch->doc_type : 'documents'; ?>
																	<?php if( isset($fetch->myfile_key) && $fetch->myfile_key != ""){ //For new file upload ?>
																		<a target="_blank" class="dropdown-item" href="<?php echo $fetch->myfile; ?>">Preview</a>
																		<a download class="dropdown-item" href="<?php echo $fetch->myfile; ?>">Download</a>
																	<?php } else {  //For old file upload
																		if (filter_var($fetch->myfile, FILTER_VALIDATE_URL)) {
																			$previewUrl = $fetch->myfile;
																		} else {
																			$url = 'https://'.env('AWS_BUCKET').'.s3.'. env('AWS_DEFAULT_REGION') . '.amazonaws.com/';
																			$previewUrl = $url.$fetchedData->id.'/'.$docType.'/'.$fetch->myfile;
																		}
																	?>
																		<a target="_blank" class="dropdown-item" href="<?php echo $previewUrl; ?>">Preview</a>
																		<a download class="dropdown-item" href="<?php echo $previewUrl; ?>">Download</a>
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
										@endforeach
										<div class="clearfix"></div>
									</div>
								   
									<!-- Container for File Preview -->
									<div style="margin-left: 10px;" class="col-5 col-md-5 col-lg-5 file-preview-container preview-container-alldocumentlist-partner">
										<p style="color:#000;">Click on a file to preview it here.</p>
									</div>
									
									<!-- Bulk Upload Mapping Modal -->
									<div id="bulk-upload-mapping-modal-partner" class="bulk-upload-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; overflow-y: auto; padding: 20px 0;">
										<div class="bulk-upload-modal-content">
											<div style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
												<h3 style="margin: 0; color: #333;">Map Files to Checklists</h3>
												<button type="button" class="close-mapping-modal" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
											</div>
											<div style="padding: 20px; overflow-x: auto;">
												<div id="bulk-upload-mapping-table-partner"></div>
											</div>
											<div style="padding: 20px; border-top: 1px solid #e2e8f0;">
												<div class="bulk-upload-progress" id="bulk-upload-progress-partner" style="display: none; margin-bottom: 15px;">
													<div style="background: #e2e8f0; border-radius: 4px; overflow: hidden; height: 30px;">
														<div class="progress-bar" id="bulk-upload-progress-bar-partner" 
															 style="background: #4a90e2; height: 100%; color: white; display: flex; 
																	align-items: center; justify-content: center; font-weight: bold; 
																	transition: width 0.3s; width: 0%;">0%</div>
													</div>
												</div>
												<div style="text-align: right;">
													<button type="button" class="btn btn-secondary" id="cancel-bulk-upload-partner">Cancel</button>
													<button type="button" class="btn btn-primary" id="confirm-bulk-upload-partner" style="margin-left: 10px;">Upload All</button>
												</div>
											</div>
										</div>
									</div>
									@endif
								</div>
								<div class="tab-pane fade <?php echo ($activeTab === 'notuseddocuments') ? 'show active' : ''; ?>" id="notuseddocuments" role="tabpanel" aria-labelledby="notuseddocuments-tab">
									@if($activeTab === 'notuseddocuments')
									<div class="list_data col-6 col-md-6 col-lg-6" style="display:inline-block;vertical-align: top;">
										<div class="">
											<table class="table text_wrap">
												<thead>
													<tr>
														<th>Checklist</th>
														<th>Added By</th>
														<th>File Name</th>
														<th></th>
													</tr>
												</thead>
												<tbody class="tdata notuseddocumnetlist">
													@forelse($partnerNotUsedDocuments ?? [] as $fetch)
													@php $admin = $fetch->user; @endphp
														<tr class="drow" id="id_{{$fetch->id}}">
															<td style="white-space: initial;"><?php echo $fetch->checklist; ?></td>
															<td style="white-space: initial;">
																<?php
																	echo ($admin ? $admin->first_name : 'Unknown') . "<br>";
																	echo date('d/m/Y', strtotime($fetch->created_at));
																?>
															</td>
															<td style="white-space: initial;">
																<?php if( isset($fetch->file_name) && $fetch->file_name !=""){ ?>
																	<div data-id="{{$fetch->id}}" data-name="<?php echo $fetch->file_name; ?>" class="doc-row">
																		<?php
																		if (isset($fetch->myfile_key) && $fetch->myfile_key != '') {
																			$inlinePreviewUrl = asset($fetch->myfile);
																		} else {
																			$url = 'https://'.env('AWS_BUCKET').'.s3.'. env('AWS_DEFAULT_REGION') . '.amazonaws.com/';
																			$inlinePreviewUrl = asset($url.$fetchedData->id.'/'.$fetch->doc_type.'/'.$fetch->myfile);
																		}
																		?>
																			<a href="javascript:void(0);"
																				data-preview-type="<?php echo htmlspecialchars($fetch->filetype, ENT_QUOTES, 'UTF-8'); ?>"
																				data-preview-url="<?php echo htmlspecialchars($inlinePreviewUrl, ENT_QUOTES, 'UTF-8'); ?>"
																				data-preview-container="preview-container-notuseddocumentlist-partner">
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
																			<a target="_blank" class="dropdown-item" href="<?php echo $url.$fetchedData->id.'/'.$fetch->doc_type.'/'.$fetch->myfile; ?>">Preview</a>
																		<?php } ?>

																		<a data-id="{{$fetch->id}}" class="dropdown-item backtodoc" data-doctype="documents" data-href="backtodoc" href="javascript:;">Back To Document</a>
																	</div>
																</div>
															</td>
														</tr>
													@empty
														<tr><td colspan="4">No records found.</td></tr>
													@endforelse
												</tbody>
											</table>
										</div>
									</div>

									<!-- Container for File Preview -->
									<div class="col-5 col-md-5 col-lg-5 file-preview-container preview-container-notuseddocumentlist-partner">
										<p style="color:#000;">Click on a file to preview it here.</p>
									</div>
									@endif
								</div>
								<div class="tab-pane fade <?php echo ($activeTab === 'accounts') ? 'show active' : ''; ?>" id="accounts" role="tabpanel" aria-labelledby="accounts-tab">
									@if($activeTab === 'accounts')
									<div class="student-dt-toolbar-host accounts-dt-toolbar-host"></div>
									<div class="table-responsive"> 
										<table class="table invoicetable text_wrap">
											<thead>
												<tr>
													<th>Invoice No.</th>
													<th>Issue Date</th>
													<th class="invoice-service-col">Service</th>
													<th>Invoice Amount</th>
													<th>Paid Amount</th>
													<th>Status</th>
													<th>Actions</th>
												</tr> 
											</thead>
											<tbody class="tdata invoicedatalist">
												{{-- Rows loaded via AJAX (server-side DataTables) --}}
											</tbody>
										</table>
									</div>
									@endif
								</div>
								<div class="tab-pane fade <?php echo ($activeTab === 'email-v2') ? 'show active' : ''; ?>" id="email-v2" role="tabpanel" aria-labelledby="email-v2-tab">
									@if($activeTab === 'email-v2')
									@php
										$partner = $fetchedData;
									@endphp
									@include('Admin.clients.tabs.emails_v2')
									@endif
								</div>
                      
                      
								<div class="tab-pane fade" id="other_info" role="tabpanel" aria-labelledby="other_info-tab">
									<span>other_info</span>
								</div>
								<div class="tab-pane fade <?php echo ($activeTab === 'promotions') ? 'show active' : ''; ?>" id="promotions" role="tabpanel" aria-labelledby="promotions-tab">
									@if($activeTab === 'promotions')
									<div class="card-header-action text-end" style="padding-bottom:15px;">
										<a href="javascript:;"  class="btn btn-primary add_promotion">@icon('plus') Add</a>
									</div>
									<div class="promotionlists"> 
									<?php
									$promotionslist = \App\Models\Promotion::where('partner_id',$fetchedData->id)->orderby('created_at','DESC')->get();
									foreach($promotionslist as $promotion){
										$countproducts = 0;
										$countbranches = 0;
										if($promotion->apply_to == 'All Products'){
											$countproducts = \App\Models\Product::where('partner', $fetchedData->id)->count();
											$countbranches = \App\Models\PartnerBranch::where('partner_id', $fetchedData->id)->count();
										}else{
											$selectproduct = explode(',',$promotion->selectproduct);
											$countproducts = count($selectproduct);
											$branch = \App\Models\Product::select('branches')->whereIn('id', $selectproduct)->get()->toArray();
											$output =  array_map("unserialize", array_unique(array_map("serialize", $branch)));
											$countbranches = count($output);
										}
									?>
										<div class="promotion_col" id="contact_<?php echo $promotion->id; ?>"> 
											<div class="promotion_content">
											@if($promotion->status == 1)
												<span class="text-success"><b>Active</b></span>
											@else
												<span class="text-danger"><b>Inactive</b></span>
											@endif
												<div class="" style="margin-top: 15px!important;">
													<h4>{{$promotion->promotion_title}}</h4>
													<p>{{ @$promotion->promotion_desc == "" ? config('constants.empty') : str_limit(@$promotion->promotion_desc, '50', '...') }}</p>
												</div>
												<div class="" style="margin-top: 15px!important;">
													<div class="row">
														<div class="col-md-6">
														<span class="text-semi-bold text-underline">For Branches</span>
														</div>
														<div class="col-md-6">
														<span  class="">{{$countbranches}}</span>
														</div>
													</div>
													<div class="row">
														<div class="col-md-6">
														<span class="text-semi-bold text-underline">For Products</span>
														</div>
														<div class="col-md-6">
														<span  class="">{{$countproducts}}</span>
														</div>
													</div>
												</div>
												<div class="" style="margin-top: 15px!important;">
													<div class="row">
														<div class="col-md-6">
														<span ><b>Start Date</b></span>
														<p>{{$promotion->promotion_start_date}}</p>
														</div>
														<div class="col-md-6">
														<span ><b>Expiry Date</b></span>
														<p>{{$promotion->promotion_end_date}}</p>
														</div>
													</div>  
												</div>
											</div>
											<div class="extra_content">
												<div class="left">
													<div class="dropdown d-inline dropdown_ellipsis_icon">
														<a class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">@icon('ellipsis-v')</a>
														<div class="dropdown-menu">
															<a class="dropdown-item openpromotonform" data-id="{{$promotion->id}}" href="javascript:;">Edit</a>
														</div>
													</div>	
												</div>  
												<div class="right">
													<div class="custom-switches">
														<label class="custom-switch">
															<input type="checkbox" data-status="<?php echo $promotion->status; ?>" data-id="{{$promotion->id}}" name="custom-switch-checkbox" class="custom-switch-input changepromotonstatus" @if($promotion->status == 1) checked @endif>
															<span class="custom-switch-indicator"></span>
														</label>
													</div>
												</div>
											</div>
										</div>
									<?php } ?>
									</div>
									@endif
								</div>
                      
                      			
                      
                                <div class="tab-pane fade <?php echo ($activeTab === 'student') ? 'show active' : ''; ?>" id="student" role="tabpanel" aria-labelledby="student-tab">
									@if($activeTab === 'student')
									@include('Admin.partners.tabs.student')
									@endif
                                </div>
                      
                      
                                 <div class="tab-pane fade <?php echo ($activeTab === 'invoice') ? 'show active' : ''; ?>" id="invoice" role="tabpanel" aria-labelledby="invoice-tab">
									@if($activeTab === 'invoice')
                                    <div class="row">
										<div class="col-md-12 mt-3 mb-3">
                                            <?php
                                            $studentdataArr = \App\Models\Application::leftJoin('partners', 'applications.partner_id', '=', 'partners.id')
                                            ->leftJoin('application_fee_options', 'applications.id', '=', 'application_fee_options.app_id')
                                            ->select(
                                                'application_fee_options.tution_fees',
                                                'application_fee_options.commission_as_per_fee_reported',
                                                'application_fee_options.commission_pending'
                                            )
                                            ->where('applications.partner_id', $fetchedData->id)
                                            ->where(function ($query) {
                                                $query->where('applications.stage', 'Coe issued')
                                                        ->orWhere('applications.stage', 'Enrolled')
                                                        ->orWhere('applications.stage', 'Coe Cancelled');
                                            })
                                            ->orderBy('applications.created_at', 'ASC')
                                            ->get(); //dd($studentdataArr);
                                            $Total_Projected_Fee = 0;
                                            $Total_Intended_Commission = 0;
                                            $Total_Pending_Commission = 0;
                                            if( !empty($studentdataArr) && count($studentdataArr) >0 ){
                                                foreach ($studentdataArr as $stdkey => $stdvalue) {
                                                    $Total_Projected_Fee += $stdvalue->tution_fees;
                                                    $Total_Intended_Commission += $stdvalue->commission_as_per_fee_reported;
                                                    $Total_Pending_Commission += $stdvalue->commission_pending;
                                                }
                                            }

                                            $Total_Amount_Invoiced = DB::table('partner_student_invoices')->where('partner_id',$fetchedData->id)->where('invoice_type',1)->sum('amount_aud');
                                            //dd($Total_Amount_Invoiced);

                                            $Total_Payment_Received = DB::table('partner_student_invoices')->where('partner_id',$fetchedData->id)->where('invoice_type',3)->sum('amount_aud');
                                            //dd($Total_Payment_Received);
                                            ?>
                                            <div class="list-group">
                                                <a class="list-group-item list-group-item-action" href="#">Total Projected Fee - <input style="margin-left:84px;border: 1px solid grey;border-radius: 5px;" type="text" id="Total_Projected_Fee" value="<?php echo "$".$Total_Projected_Fee;?>" readonly></a>
                                                <a class="list-group-item list-group-item-action" href="javascript:;">Total Amount Invoiced  - <input style="margin-left:65px;border: 1px solid grey;border-radius: 5px;" type="text" id="Total_Amount_Invoiced" value="<?php echo "$".$Total_Amount_Invoiced;?>"  readonly></a>
                                                <a class="list-group-item list-group-item-action" href="javascript:;">Total Payment Received -<input style="margin-left:60px;border: 1px solid grey;border-radius: 5px;" type="text" id="Total_Payment_Received" value="<?php echo "$".$Total_Payment_Received;?>" readonly></a>
                                                <a class="list-group-item list-group-item-action" href="javascript:;" >Total Intended Commission - <input style="margin-left:37px;border: 1px solid grey;border-radius: 5px;" type="text" id="Total_Intended_Commission" value="<?php echo "$".$Total_Intended_Commission;?>" readonly></a>
                                                <a class="list-group-item list-group-item-action" href="javascript:;" >Total Pending Commission - <input style="margin-left:41px;border: 1px solid grey;border-radius: 5px;" type="text" id="Total_Pending_Commission" value="<?php echo "$".$Total_Pending_Commission;?>" readonly></a>
                                            </div>
                                        </div>
										<div class="clearfix"></div>
									</div>

                                    <div class="invoices_tabs">
                                        <ul class="nav nav-pills round_tabs" id="client_tabs" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" data-bs-toggle="tab" id="create_invoice-tab" href="#create_invoice" role="tab" aria-controls="create_invoice" aria-selected="true">Create Invoice</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="tab" id="record_invoice-tab" href="#record_invoice" role="tab" aria-controls="record_invoice" aria-selected="false">Record Invoice</a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" data-bs-toggle="tab" id="record_payment-tab" href="#record_payment" role="tab" aria-controls="record_payment" aria-selected="false">Record Payment</a>
                                            </li>
                                        </ul>

                                        <div class="tab-content" id="invoicesContent">
                                          
											 <div class="tab-pane fade show active" id="create_invoice" role="tabpanel" aria-labelledby="create_invoice-tab">
                                                <div class="row">
                                                    <div class="col-md-12 text-end">
                                                        <a class="btn btn-primary createpartnerstudentinvoice" href="javascript:;" data-partnerid="{{ $fetchedData->id }}" role="button"  style="margin-right:5px !important;">Create Invoice</a>
                                                    </div>
                                                    <div class="clearfix"></div>
                                                </div>
                                                <div class="table-responsive">
                                                    <caption>Invoice</caption>
                                                    <table border="1" style="margin-bottom:0rem !important;" class="table text_wrap table-striped table-hover table-md vertical_align">
                                                        <thead>
                                                            <tr>
                                                                <th>Invoice Date</th>
                                                                <th>Invoice Number</th>
                                                                <th>No Of Students Enrolled</th>
                                                                <th>Amount(Incl GST)</th>
                                                                <th>Sent</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="productitemList">
                                                            <?php
                                                            // FIXED: Added missing columns to SELECT to avoid undefined property errors
                                                            $receipts_lists = DB::table('partner_student_invoices')
                                                            ->select(
                                                                'invoice_id',
                                                                'invoice_date',
                                                                'invoice_no',
                                                                'invoice_type',
                                                                'partner_id',
                                                                'sent_option',
                                                                'sent_date',
                                                                DB::raw('MAX(uploaded_doc_id) as uploaded_doc_id'),
                                                                DB::raw('COUNT(student_id) as student_count'),
                                                                DB::raw('SUM(amount_aud) as total_amount_aud')
                                                            )
                                                            ->where('partner_id',$fetchedData->id)
                                                            ->where('invoice_type',1)
                                                            ->groupBy('invoice_id', 'invoice_date', 'invoice_no', 'invoice_type', 'partner_id', 'sent_option', 'sent_date')
                                                            ->get();
                                                            //dd($receipts_lists);
                                                            if(!empty($receipts_lists) && count($receipts_lists)>0 )
                                                            {
                                                                $total_deposit_amount = 0.00;
                                                                foreach($receipts_lists as $rec_list=>$rec_val)
                                                                {
                                                            ?>
                                                            <tr  id="TrRow_<?php echo $rec_val->invoice_id;?>">
                                                                <td style="padding-top: 5px !important;padding-bottom: 5px !important;">
                                                                    <?php echo $rec_val->invoice_date;?>
                                                                    <?php
                                                                    if(isset($rec_val->uploaded_doc_id) && $rec_val->uploaded_doc_id >0){
                                                                        $client_doc_list = ($invoiceDocumentMap ?? collect())->get($rec_val->uploaded_doc_id);
                                                                        if($client_doc_list){
                                                                            $url = 'https://'.env('AWS_BUCKET').'.s3.'. env('AWS_DEFAULT_REGION') . '.amazonaws.com/';
                                                                            $awsUrl =  $client_doc_list->myfile;
                                                                        ?>
                                                                            <a target="_blank" class="link-primary" href="<?php echo $awsUrl;?>">@icon('file-pdf')</a>
                                                                        <?php
                                                                        }
                                                                    } ?>
                                                                </td>
                                                                <td style="padding-top: 5px !important;padding-bottom: 5px !important;"><?php echo $rec_val->invoice_no;?></td>
                                                                <td style="padding-top: 5px !important;padding-bottom: 5px !important;"><?php echo $rec_val->student_count;?></td>
                                                                <td style="padding-top: 5px !important;padding-bottom: 5px !important;">
                                                                    <?php echo "$".$rec_val->total_amount_aud;?>
                                                                    <a target="_blank" class="link-primary" href="{{URL::to('/partners/printpreviewcreateinvoice')}}/{{$rec_val->invoice_id}}">{!! \App\Helpers\IconHelper::render('print') !!}</a>
                                                                    <?php if ( isset( $rec_val->sent_option ) && $rec_val->sent_option == 'Yes' ) { ?>
                                                                    <?php } else { ?>
                                                                        <a class="link-primary updatedraftstudentinvoice" href="javascript:;" data-invoiceid="<?php echo $rec_val->invoice_id;?>">@icon('pencil-alt')</a>
                                                                        <a class="link-primary deletestudentinvoice" href="javascript:;" data-invoiceid="<?php echo $rec_val->invoice_id;?>" data-invoicetype="<?php echo $rec_val->invoice_type;?>" data-partnerid="<?php echo $rec_val->partner_id;?>">@icon('trash')</a>
                                                                    <?php } ?>
                                                                </td>
                                                                <td style="padding-top: 5px !important;padding-bottom: 5px !important;">
                                                                    <?php if ( isset( $rec_val->sent_option ) && $rec_val->sent_option == 'Yes') { ?>
                                                                        <span><?php echo $rec_val->sent_option."<br/>".$rec_val->sent_date; ?></span>
                                                                    <?php } else { ?>
                                                                        <select name="sent_option"  class="sent_option" data-invoiceid="<?php echo $rec_val->invoice_id;?>">
                                                                            <option value="No" <?php if ($rec_val->sent_option == 'No') echo 'selected'; ?>>No</option>
                                                                            <option value="Yes" <?php if ($rec_val->sent_option == 'Yes') echo 'selected'; ?>>Yes</option>
                                                                        </select>
                                                                    <?php } ?>
																</td>
                                                            </tr>
                                                            <?php
                                                                $total_deposit_amount += $rec_val->total_amount_aud;
                                                            } //end foreach
                                                            ?>
                                                            <tr class="lastRow">
                                                                <td colspan="3" style="text-align:right;">Totals</td>
                                                                <td colspan="2" class="totDepoAmTillNow"><?php echo "$".$total_deposit_amount;?></td>
                                                            </tr>
                                                        <?php } else { ?>
                                                            <tr class="lastRow">
                                                                <td colspan="3" style="text-align:right;">Totals</td>
                                                                <td colspan="2" class="totDepoAmTillNow"><?php echo "$0";?></td>
                                                            </tr>
                                                        <?php } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <div class="tab-pane fade" id="record_invoice" role="tabpanel" aria-labelledby="record_invoice-tab">
                                                <div class="row">
                                                    <div class="col-md-12 text-end">
                                                        <a class="btn btn-primary createrecordinvoice" href="javascript:;" data-partnerid="{{ $fetchedData->id }}" role="button"  style="margin-right:5px !important;">Create Record Invoice</a>
                                                    </div>
                                                    <div class="clearfix"></div>
                                                </div>
                                                <div class="table-responsive">
                                                    <caption>Record Invoice</caption>
                                                    <table border="1" style="margin-bottom:0rem !important;" class="table text_wrap table-striped table-hover table-md vertical_align">
                                                        <thead>
                                                            <tr>
                                                                <th>Invoice Date</th>
                                                                <th>Sent Date</th>
                                                                <th>Invoice Number</th>
                                                                <th>Amount(Incl GST)</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="productitemList_invoice">
                                                            <?php
                                                            $record_invoices = DB::table('partner_student_invoices')->where('partner_id',$fetchedData->id)->where('invoice_type',2)->get();
                                                            //dd($record_invoices);
                                                            if(!empty($record_invoices) && count($record_invoices)>0 )
                                                            {
                                                                $total_invoice_amount = 0.00;
                                                                foreach($record_invoices as $inv_list=>$inv_val)
                                                                {

                                                            ?>
                                                            <tr  id="TrRecordRow_<?php echo $inv_val->id;?>">
                                                                <td>
                                                                    <?php echo $inv_val->invoice_date;?>
                                                                    <?php
                                                                    if(isset($inv_val->uploaded_doc_id) && $inv_val->uploaded_doc_id >0){
                                                                        $client_inv_doc_list = ($invoiceDocumentMap ?? collect())->get($inv_val->uploaded_doc_id);
                                                                        if($client_inv_doc_list){
                                                                            $awsUrl_inv =  $client_inv_doc_list->myfile;
                                                                        ?>
                                                                            <a target="_blank" class="link-primary" href="<?php echo $awsUrl_inv;?>">@icon('file-pdf')</a>
                                                                        <?php
                                                                        }
                                                                    } ?>
                                                                </td>
                                                                <td><?php echo $inv_val->sent_date;?></td>
                                                                <td><?php echo $inv_val->invoice_no;?></td>
                                                                <td>
                                                                    <?php echo "$".$inv_val->amount_aud;?>
                                                                    <!--<a target="_blank" class="link-primary" href="{{--URL::to('/clients/printpreview')--}}/{{--$rec_val->id--}}">@icon('print')</a>
                                                                    <a class="link-primary updateclientreceipt" href="javascript:;" data-id="<?php //echo $rec_val->id;?>">@icon('pencil-alt')</a>-->
                                                                    <a class="link-primary deletestudentrecordinvoice" href="javascript:;" data-uniqueid="<?php echo $inv_val->id;?>" data-invoicetype="<?php echo $inv_val->invoice_type;?>" data-partnerid="<?php echo $inv_val->partner_id;?>">@icon('trash')</a>
                                                                </td>
                                                            </tr>
                                                            <?php
                                                                $total_invoice_amount += $inv_val->amount_aud;
                                                            } //end foreach
                                                            ?>

                                                            <tr class="lastRow_invoice">
                                                                <td colspan="3" style="text-align:right;">Totals</td>
                                                                <td class="totDepoAmTillNow_invoice"><?php echo "$".$total_invoice_amount;?></td>
                                                            </tr>
                                                            <?php
                                                            } else { ?>
                                                            <tr class="lastRow_invoice">
                                                                <td colspan="3" style="text-align:right;">Totals</td>
                                                                <td class="totDepoAmTillNow_invoice"><?php echo "$0";?></td>
                                                            </tr>
                                                            <?php
                                                            } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <div class="tab-pane fade" id="record_payment" role="tabpanel" aria-labelledby="record_payment-tab">
                                                <div class="row">
                                                    <div class="col-md-12 text-end">
                                                        <a class="btn btn-primary createrecordpayment" href="javascript:;" data-partnerid="{{ $fetchedData->id }}" role="button"  style="margin-right:5px !important;">Create Record Payment</a>
                                                    </div>
                                                    <div class="clearfix"></div>
                                                </div>
                                                <div class="table-responsive">
                                                    <caption>Record Payment</caption>
                                                    <table border="1" style="margin-bottom:0rem !important;" class="table text_wrap table-striped table-hover table-md vertical_align">
                                                        <thead>
                                                            <tr>
                                                                <th>Invoice Number</th>
                                                                <th>Method Received</th>
                                                                <th>Verified By</th>
                                                                <th>Verified Date</th>
                                                                <th>Amount Received</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="productitemList_payment">
                                                            <?php
                                                            $record_payments = DB::table('partner_student_invoices')->where('partner_id',$fetchedData->id)->where('invoice_type',3)->get();
                                                            //dd($record_payments);
                                                            if(!empty($record_payments) && count($record_payments)>0 )
                                                            {
                                                                $total_payment_amount = 0.00;
                                                                foreach($record_payments as $pay_list=>$pay_val)
                                                                {

                                                            ?>
                                                            <tr  id="TrPaymentRow_<?php echo $pay_val->id;?>">
                                                                <td>
                                                                    <?php echo $pay_val->invoice_no;?>
                                                                    <?php
                                                                    if(isset($pay_val->uploaded_doc_id) && $pay_val->uploaded_doc_id >0){
                                                                        $client_pay_doc_list = ($invoiceDocumentMap ?? collect())->get($pay_val->uploaded_doc_id);
                                                                        if($client_pay_doc_list){
                                                                            $awsUrl_pay =  $client_pay_doc_list->myfile;
                                                                        ?>
                                                                            <a target="_blank" class="link-primary" href="<?php echo $awsUrl_pay;?>">@icon('file-pdf')</a>
                                                                        <?php
                                                                        }
                                                                    } ?>
                                                                </td>
                                                                <td><?php echo $pay_val->method_received;?></td>
                                                                <td><?php echo $pay_val->verified_by;?></td>
                                                                <td><?php echo $pay_val->verified_date;?></td>
                                                                <td>
                                                                    <?php echo "$".$pay_val->amount_aud;?>
                                                                    <!--<a target="_blank" class="link-primary" href="{{--URL::to('/clients/printpreview')--}}/{{--$rec_val->id--}}">@icon('print')</a>
                                                                    <a class="link-primary updateclientreceipt" href="javascript:;" data-id="<?php //echo $rec_val->id;?>">@icon('pencil-alt')</a>-->
                                                                    <a class="link-primary deletestudentpaymentinvoice" href="javascript:;" data-uniqueid="<?php echo $pay_val->id;?>" data-invoicetype="<?php echo $pay_val->invoice_type;?>" data-partnerid="<?php echo $pay_val->partner_id;?>">@icon('trash')</a>
                                                                </td>
                                                            </tr>
                                                            <?php
                                                                $total_payment_amount += $pay_val->amount_aud;
                                                            } //end foreach
                                                            ?>

                                                            <tr class="lastRow_payment">
                                                                <td colspan="4" style="text-align:right;">Totals</td>
                                                                <td class="totDepoAmTillNow_payment"><?php echo "$".$total_payment_amount;?></td>
                                                            </tr>
                                                            <?php
                                                            } else { ?>
                                                            <tr class="lastRow_payment">
                                                                <td colspan="4" style="text-align:right;">Totals</td>
                                                                <td class="totDepoAmTillNow_payment"><?php echo "$0";?></td>
                                                            </tr>
                                                            <?php
                                                            } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            

                                            

                                        </div>
                                    </div>
									@endif
                                </div>
                      
                      
							</div> 
						</div> 
					</div>
				</div>
			</div>
		</div>
	</section>
</div> 

@include('Admin/partners/addpartnermodal')  
@include('Admin/partners/editpartnermodal')   

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
				<input type="hidden" name="client_id" value="{{ $fetchedData->id ?? '' }}">
				<input type="hidden" value="partner" name="type">
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
									@foreach($partnerDetailEmailTemplates as $list)
										<option value="{{ $list['id'] }}">{{ $list['name'] }}</option>
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
							<div class="form-group compose-labels-section">
								<label>Labels</label>
								<div class="compose-labels-display">
									<span class="compose-label-badge compose-label-sent" title="All sent emails are automatically tagged">@icon('paper-plane') Sent</span>
									<div id="composeAdditionalLabelsChips" class="compose-label-chips"></div>
									<div class="compose-add-label-wrapper dropdown">
										<button type="button" class="btn btn-outline-secondary btn-sm compose-add-label-btn" id="composeAddLabelBtn" data-bs-toggle="dropdown" aria-expanded="false">
											@icon('plus') Add label
										</button>
										<ul class="dropdown-menu compose-label-dropdown" id="composeLabelDropdown" aria-labelledby="composeAddLabelBtn">
											<!-- Populated by JS when labels are loaded -->
										</ul>
									</div>
								</div>
								<div id="composeLabelIdsContainer"><!-- Hidden inputs for label_ids[] added by JS --></div>
								<small class="form-text text-muted">All sent emails are tagged with "Sent" for records. Add optional labels to filter in the Emails tab.</small>
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

<div class="modal fade addbranch custom_modal" data-keyboard="false" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="clientModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="clientModalLabel">Add New Branch</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form method="post" id="branchform" autocomplete="off" enctype="multipart/form-data">
					<div class="row">
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="branch_name">Name <span class="span_req">*</span></label>
								{!! Form::text('branch_name', '', array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Enter Name' ))  !!}
								<span class="custom-error branch_name_error" role="alert">
									<strong></strong>
								</span> 
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="branch_email">Email <span class="span_req">*</span></label>
								{!! Form::text('branch_email', '', array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Enter Email' ))  !!}
									<span class="custom-error branch_email_error" role="alert">
										<strong></strong>
									</span> 
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group"> 
								<label for="branch_country">Country</label>
							<select class="form-control branch_country tomselect" name="branch_country" >
								<option value="">Select</option>
								@foreach($partnerDetailCountries as $list)
									<option value="{{ $list['name'] }}">{{ $list['name'] }}</option>
								@endforeach
							</select>
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="branch_city">City</label>
								{!! Form::text('branch_city', '', array('class' => 'form-control', 'data-valid'=>'required', 'autocomplete'=>'off','placeholder'=>'Enter City' ))  !!}
								@if ($errors->has('branch_city'))
									<span class="custom-error" role="alert">
										<strong>{{ @$errors->first('branch_city') }}</strong>
									</span> 
								@endif
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group"> 
								<label for="branch_state">State</label>
								{!! Form::text('branch_state', '', array('class' => 'form-control', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'Enter State' ))  !!}
								@if ($errors->has('branch_state'))
									<span class="custom-error" role="alert">
										<strong>{{ @$errors->first('branch_state') }}</strong>
									</span> 
								@endif
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group"> 
								<label for="branch_address">Street</label>
								{!! Form::text('branch_address', '', array('class' => 'form-control', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'Enter Street' ))  !!}
								@if ($errors->has('branch_address'))
									<span class="custom-error" role="alert">
										<strong>{{ @$errors->first('branch_address') }}</strong>
									</span> 
								@endif
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group"> 
								<label for="branch_zip">Zip Code</label>
								{!! Form::text('branch_zip', '', array('class' => 'form-control', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'Enter Zip / Post Code' ))  !!}
								@if ($errors->has('branch_zip'))
									<span class="custom-error" role="alert">
										<strong>{{ @$errors->first('branch_zip') }}</strong>
									</span> 
								@endif
							</div>
						</div>
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group"> 
								<label for="branch_phone">Phone</label>
								<div class="cus_field_input"> 
									<div class="country_code"> 
										@include('partials.country-code-select', [
											'name' => 'brnch_country_code',
											'selected' => old('brnch_country_code', \App\Helpers\PhoneHelper::getDefaultCountryCode())
										])
									</div>	
									{!! Form::text('branch_phone', '', array('class' => 'form-control tel_input', 'data-valid'=>'', 'autocomplete'=>'off','placeholder'=>'Enter Phone' ))  !!}
									@if ($errors->has('branch_phone'))
										<span class="custom-error" role="alert">
											<strong>{{ @$errors->first('branch_phone') }}</strong>
										</span> 
									@endif
								</div> 
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<button type="button" class="btn btn-primary savebranch">Save</button>
							<button type="button" id="update_branch" style="display:none" class="btn btn-primary">Update</button>
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

<div id="confirmEducationModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="false" class="modal fade" >
	<div class="modal-dialog">
		<div class="modal-content popUp">
			<div class="modal-body text-center">
				<button type="button" data-bs-dismiss="modal" aria-label="Close" class="btn-close"></button>
				<h4 class="modal-title text-center message col-v-5">Do you want to delete this note?</h4> 
				<button type="submit" style="margin-top: 40px;" class="button btn btn-danger accepteducation">Delete</button> 
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


<!-- Partner Agreement Modal -->
<div class="modal fade" id="agreementModal" tabindex="-1" aria-labelledby="agreementModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="agreementModalLabel">Add Agreement</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form method="post" id="agreementForm" enctype="multipart/form-data">
				<div class="modal-body">
					@csrf
					<input type="hidden" name="partner_id" id="agreement_partner_id" value="{{$fetchedData->id}}">
					<input type="hidden" name="agreement_id" id="agreement_id" value="">
					
					<div class="row">
						<div class="col-md-6">
							<div class="form-group mb-3">
								<label for="agreement_contract_start">Contract Start Date <span class="text-danger">*</span></label>
								<div class="input-group">
									<span class="input-group-text">
										@icon('calendar-alt')
									</span>
									<input type="text" name="contract_start" id="agreement_contract_start" class="form-control datepicker" placeholder="Select Date" autocomplete="off">
								</div>
							</div>
						</div>
						
						<div class="col-md-6">
							<div class="form-group mb-3">
								<label for="agreement_contract_expiry">Contract Expiry Date <span class="text-danger">*</span></label>
								<div class="input-group">
									<span class="input-group-text">
										@icon('calendar-alt')
									</span>
									<input type="text" name="contract_expiry" id="agreement_contract_expiry" class="form-control datepicker" placeholder="Select Date" autocomplete="off">
								</div>
							</div>
						</div>
					</div>
					
					<div class="row">
						<div class="col-md-6">
							<div class="form-group mb-3">
								<label for="agreement_commission_percentage">Commission Percentage</label>
								<input type="number" name="commission_percentage" id="agreement_commission_percentage" class="form-control" placeholder="Enter Commission Percentage" step="0.01" autocomplete="off">
							</div>
						</div>
						
						<div class="col-md-6">
							<div class="form-group mb-3">
								<label for="agreement_bonus">Bonus</label>
								<input type="number" name="bonus" id="agreement_bonus" class="form-control" placeholder="Enter Bonus Amount" step="0.01" autocomplete="off">
							</div>
						</div>
					</div>
					
					<div class="row">
						<div class="col-md-12">
							<div class="form-group mb-3">
								<label for="agreement_represent_region">Representing Regions</label>
							<select class="form-control tomselect" multiple name="represent_region[]" id="agreement_represent_region" data-placeholder="Select countries...">
								@foreach($partnerDetailCountries as $list)
									<option value="{{ $list['name'] }}">{{ $list['name'] }}</option>
								@endforeach
							</select>
							</div>
						</div>
					</div>
					
					<div class="row">
						<div class="col-md-12">
							<div class="form-group mb-3">
								<label for="agreement_description">Description</label>
								<textarea name="description" id="agreement_description" class="form-control" rows="3" placeholder="Enter agreement description or notes"></textarea>
							</div>
						</div>
					</div>
					
					<div class="row">
						<div class="col-md-4">
							<div class="form-group mb-3">
								<label for="agreement_gst">GST</label>
								<div>
									<input type="checkbox" name="gst" id="agreement_gst" value="1">
								</div>
							</div>
						</div>
						
						<div class="col-md-4">
							<div class="form-group mb-3">
								<label for="agreement_default_super_agent">Default Super Agent</label>
							<select class="form-control tomselect" name="default_super_agent" id="agreement_default_super_agent">
								<option value="">Select</option>
								@foreach($partnerDetailSuperAgents as $sa)
									<option value="{{ $sa['id'] }}">{{ $sa['full_name'] }}{{ !empty($sa['email']) ? ' (' . $sa['email'] . ')' : '' }}</option>
								@endforeach
							</select>
							</div>
						</div>
						
						<div class="col-md-4">
							<div class="form-group mb-3">
								<label for="agreement_status">Status</label>
								<select class="form-control" name="status" id="agreement_status">
									<option value="active">Active</option>
									<option value="inactive">Inactive</option>
								</select>
							</div>
						</div>
					</div>
					
					<div class="row">
						<div class="col-md-12">
							<div class="form-group mb-3">
								<label for="agreement_file_upload">Document Upload</label>
								<input type="file" name="file_upload" id="agreement_file_upload" class="form-control">
								<small id="current_file_display" class="form-text text-muted"></small>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-primary" id="saveAgreementBtn">Save Agreement</button>
				</div>
			</form>
		</div>
	</div>
</div>


<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeStatusModalLabel">Change Student Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
            </div>
            <div class="modal-body">
                <div id="changeStatusFormMessage" class="mb-2"></div>
                <form id="changeStatusForm">
                    <input type="hidden" name="student_id" id="studentId">
                    <div class="mb-3">
                        <label for="newStatus" class="form-label">Select Status</label>
                        <select class="form-select" id="newStatus" name="new_status">
                            <option value="0">In Progress</option>
                            <option value="1">Completed</option>
                            <option value="2">Discontinued</option>
                            <option value="3">Cancelled</option>
                            <option value="4">Withdrawn</option>
                            <option value="5">Deferred</option>
                            <option value="6">Future</option>
                            <option value="7">VOE</option>
                            <option value="8">Refund</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="changeApplicationOverallStatusModal" tabindex="-1" aria-labelledby="changeApplicationOverallStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeApplicationOverallStatusModalLabel">Application Overall Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
            </div>
            <div class="modal-body">
                <h6>Do you want to change application overall status?</h6>
                <form id="changeApplicationOverallStatusForm">
                    <input type="hidden" name="application_student_id" id="applicationStudentId">
                    <input type="hidden" name="application_overall_status" id="applicationOverallStatus" value="">
                    <button type="submit" class="btn btn-primary">Change</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')

@push('scripts')
	{{-- @stack('scripts') renders before @yield in admin layout; AppConfig inline below runs during parse before this defer module executes --}}
	@vite(['resources/js/pages/admin/partner-detail-entry.js'])
@endpush

<style>
    /* Custom styles for date fields (Flatpickr) */
    .datepicker-input {
        max-width: 200px;
    }

    #invoiceSentConfirmModal .flatpickr-calendar {
        z-index: 1060;
    }
    
    /* Fix Tom Select dropdown z-index in modals */
    .ts-wrapper.focus,
    .ts-dropdown {
        z-index: 9999 !important;
    }
    
    /* Ensure modal has proper z-index */
    .modal {
        z-index: 1050;
    }
    
    .modal-backdrop {
        z-index: 1040;
    }
</style>

<script>
    // ============================================================================
    // GLOBAL CONFIGURATION
    // ============================================================================
    
    // Ensure global config objects exist before setting properties
    window.AppConfig = window.AppConfig || {};
    window.PageConfig = window.PageConfig || {};
    
    // Application Configuration
    AppConfig.csrf = '{{ csrf_token() }}';
    AppConfig.siteUrl = '{{ url("/") }}';
    AppConfig.urls = {
        siteUrl: '{{ url("/") }}',
        previewDocument: '{{ url("/preview-document") }}',
        documentPreviewView: '{{ url("/document-preview-view") }}',
        partnersUpdateStudentStatus: '{{ url("/partners/update-student-status") }}',
        partnersUpdateStudentApplicationStatus: '{{ url("/partners/update-student-application-overall-status") }}',
        partnersGetEnrolledStudentList: '{{ URL::to("/partners/getEnrolledStudentList") }}',
        partnersGetTopReceiptValInDB: '{{ URL::to("/partners/getTopReceiptValInDB") }}',
        partnersGetTopInvoiceValInDB: '{{ URL::to("/partners/getTopInvoiceValInDB") }}',
        partnersGetStudentInfo: '{{ URL::to("/partners/getStudentInfo") }}',
        partnersGetStudentCourseInfo: '{{ URL::to("/partners/getStudentCourseInfo") }}',
        partnersUpdateInvoiceSentOptionToYes: '{{ URL::to("/partners/updateInvoiceSentOptionToYes") }}',
        partnersGetInfoByInvoiceId: '{{ URL::to("/partners/getInfoByInvoiceId") }}',
        partnersDeleteStudentRecordByInvoiceId: '{{ URL::to("/partners/deleteStudentRecordByInvoiceId") }}',
        partnersGetEnrolledStudentListInEditMode: '{{ URL::to("/partners/getEnrolledStudentListInEditMode") }}',
        partnersDeleteStudentRecordInvoiceByInvoiceId: '{{ URL::to("/partners/deleteStudentRecordInvoiceByInvoiceId") }}',
        partnersGetRecordedInvoiceList: '{{ URL::to("/partners/getRecordedInvoiceList") }}',
        partnersDeleteStudentPaymentInvoiceByInvoiceId: '{{ URL::to("/partners/deleteStudentPaymentInvoiceByInvoiceId") }}',
        partnersAddAllDocChecklist: '{{ URL::to("/partners/add-alldocchecklist") }}',
        partnersUploadAllDocument: '{{ URL::to("/partners/upload-alldocument") }}',
        partnersUploadPartnerDocument: '{{ url("/upload-partner-document-upload") }}',
        partnersSaveStudentNote: '{{ url("/partners/save-student-note") }}',
        partnersSaveStudentEnrolmentType: '{{ url("/partners/save-student-enrolment-type") }}',
        partnersGetStudentTabData: '{{ url("/partners/getStudentTabData") }}',
        partnersGetStudentTabCount: '{{ url("/partners/getStudentTabCount") }}',
        partnersGetStudentTabTotals: '{{ url("/partners/getStudentTabTotals") }}',
        partnersExportStudentTabData: '{{ url("/partners/exportStudentTabData") }}',
        partnersGetApplicationTabData: '{{ url("/partners/getApplicationTabData") }}',
        partnersGetAccountsTabData: '{{ url("/partners/getAccountsTabData") }}',
        partnersExportAccountsTabData: '{{ url("/partners/exportAccountsTabData") }}',
        getPartnerActivities: '{{ url("/get-partner-activities") }}',
        getPartnerNotes: '{{ url("/get-partner-notes") }}',
        getPartner: '{{ url("/getpartner") }}',
        getProduct: '{{ url("/getproduct") }}',
        getBranch: '{{ url("/getbranch") }}',
        changePromotionStatus: '{{ url("/change-promotion-status") }}',
        getPromotionEditForm: '{{ url("/getpromotioneditform") }}',
        getPromotions: '{{ url("/get-promotions") }}',
        changeClientStatus: '{{ url("/change-client-status") }}',
        getApplicationsLogs: '{{ url("/get-applications-logs") }}',
        getApplicationDetail: '{{ url("/getapplicationdetail") }}',
        updateApplicationEnrolmentType: '{{ url("/application/update-enrolment-type") }}',
        updateApplicationCompanyName: '{{ url("/application/update-company-name") }}',
        updateApplicationIntake: '{{ url("/application/updateintake") }}',
        updateStage: '{{ url("/updatestage") }}',
        updateBackStage: '{{ url("/updatebackstage") }}',
        getApplicationNotes: '{{ url("/getapplicationnotes") }}',
        partnersFetchPartnerContactNo: '{{ URL::to("/partners/fetchPartnerContactNo") }}',
        clientsFetchClientContactNo: '{{ URL::to("/clients/fetchClientContactNo") }}',
        clientsGetRecipients: '{{ URL::to("/clients/get-recipients") }}',
        getNotes: '{{ url("/get-partner-notes") }}',
        getActivities: '{{ url("/get-partner-activities") }}',
        deleteAction: '{{ URL::to("/") }}',
        pinnote: '{{ URL::to("/pinnote") }}',
        viewnotedetail: '{{ URL::to("/viewnotedetail") }}',
        getnotedetail: '{{ URL::to("/getnotedetail") }}',
        getbranchdetail: '{{ URL::to("/getbranchdetail") }}',
        getpartnerbranch: '{{ URL::to("/getpartnerbranch") }}',
        getbranchproduct: '{{ URL::to("/getbranchproduct") }}',
        getTemplates: '{{ URL::to("/get-templates") }}',
        getBranches: '{{ url("/get-branches") }}',
        deletedocs: '{{ url("/deletedocs") }}',
        deletebranch: '{{ url("/deletebranch") }}'
    };
    
    // Page-Specific Configuration
    PageConfig.partnerId = {{ $fetchedData->id ?? 'null' }};
    PageConfig.partnerName = '{{ $fetchedData->partner_name ?? "" }}';
    PageConfig.activeTab = @json($activeTab);
    PageConfig.partnerType = 'partner';
    PageConfig.defaultCountryCode = '{{ \App\Helpers\PhoneHelper::getDefaultCountryCode() }}';
    PageConfig.contractStart = '{{ @$fetchedData->contract_start ?? "" }}';
    PageConfig.contractExpiry = '{{ @$fetchedData->contract_expiry ?? "" }}';
    PageConfig.canEditApplicationEnrolmentCompanyFields = {{ \App\Models\Application::canUpdateEnrolmentAndCompanyFields() ? 'true' : 'false' }};
    
    // ============================================================================
    // PARTNER AGREEMENTS HANDLER
    // ============================================================================
    
    $(document).ready(function() {
        // Load agreements on page load if on agreements tab
        if ($('#agreements').hasClass('active')) {
            loadPartnerAgreements();
        }
        
        // Load agreements when tab is clicked
        $('a[href="#agreements"]').on('shown.bs.tab', function() {
            loadPartnerAgreements();
        });
        
        // Add Agreement Button Click
        $('.add_agreement_btn').on('click', function() {
            resetAgreementForm();
            $('#agreementModalLabel').text('Add Agreement');
            // Use Bootstrap 5 native API or jQuery bridge
            var agreementModal = document.getElementById('agreementModal');
            if (agreementModal) {
                var modal = new bootstrap.Modal(agreementModal);
                modal.show();
            }
        });
        
        // Save Agreement Button Click
        $('#saveAgreementBtn').on('click', function() {
            saveAgreement();
        });
        
        // Edit Agreement
        $(document).on('click', '.edit_agreement', function() {
            var agreementId = $(this).data('id');
            loadAgreementData(agreementId);
        });
        
        // Delete Agreement
        $(document).on('click', '.delete_agreement', function() {
            var agreementId = $(this).data('id');
            deleteAgreement(agreementId);
        });
        
        // Set Active Agreement
        $(document).on('click', '.set_active_agreement', function() {
            var agreementId = $(this).data('id');
            setActiveAgreement(agreementId);
        });
        
        // View Agreement Document
        $(document).on('click', '.view_agreement_doc', function() {
            var docUrl = $(this).data('url');
            window.open(docUrl, '_blank');
        });
        
        // Initialize Flatpickr for agreement modal date fields (replaces jQuery UI datepicker)
        if (typeof flatpickr !== 'undefined') {
            $('#agreement_contract_start, #agreement_contract_expiry').each(function() {
                flatpickr(this, {
                    dateFormat: 'Y-m-d',
                    allowInput: true
                });
            });
        }

        // Agreement modal Tom Select (Phase 6d)
        $(document).on('shown.bs.modal', '#agreementModal', function () {
            if (typeof initModalTomSelects === 'function') {
                initModalTomSelects(this);
            }
        });

        // Destroy Tom Select on close so edit/add re-inits cleanly with correct values.
        $(document).on('hidden.bs.modal', '#agreementModal', function () {
            ['#agreement_represent_region', '#agreement_default_super_agent'].forEach(function (sel) {
                if (typeof destroyTomSelect === 'function') {
                    destroyTomSelect(sel);
                }
            });
        });
    });
    
    function toAgreementDateInputValue(dateString) {
        if (!dateString) return '';
        var value = String(dateString).trim();

        if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
            return value;
        }

        if (value.indexOf('T') !== -1) {
            var date = new Date(value);
            if (!isNaN(date.getTime())) {
                var day = String(date.getDate()).padStart(2, '0');
                var month = String(date.getMonth() + 1).padStart(2, '0');
                var year = date.getFullYear();
                return year + '-' + month + '-' + day;
            }
        }

        var match = value.match(/^(\d{4}-\d{2}-\d{2})/);
        return match ? match[1] : '';
    }

    function formatAgreementDate(dateString) {
        var ymd = toAgreementDateInputValue(dateString);
        if (!ymd) return 'N/A';
        var parts = ymd.split('-');
        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    function setAgreementDateField(selector, ymdValue) {
        var el = document.querySelector(selector);
        if (!el) return;
        if (el._flatpickr) {
            el._flatpickr.setDate(ymdValue, true, 'Y-m-d');
        } else {
            $(selector).val(ymdValue);
        }
    }

    // Load all partner agreements
    function loadPartnerAgreements() {
        $.ajax({
            url: '{{ url("/partner/agreements/list") }}',
            type: 'GET',
            data: {
                partner_id: PageConfig.partnerId
            },
            success: function(response) {
                if (response.status) {
                    displayAgreements(response.agreements);
                } else {
                    toastMsg('Failed to load agreements', 'error');
                }
            },
            error: function() {
                toastMsg('Error loading agreements', 'error');
            }
        });
    }
    
    // Display agreements in table
    function displayAgreements(agreements) {
        var tbody = $('#agreements_tbody');
        tbody.empty();
        
        if (agreements.length === 0) {
            tbody.append('<tr><td colspan="10" class="text-center">No agreements found</td></tr>');
            return;
        }
        
        $.each(agreements, function(index, agreement) {
            var statusBadge = agreement.status === 'active' 
                ? '<span class="badge bg-success">Active</span>' 
                : '<span class="badge bg-secondary">Inactive</span>';
            
            var contractStart = agreement.contract_start ? formatAgreementDate(agreement.contract_start) : 'N/A';
            var contractExpiry = agreement.contract_expiry ? formatAgreementDate(agreement.contract_expiry) : 'N/A';
            var commission = agreement.commission_percentage ? agreement.commission_percentage + '%' : 'N/A';
            var bonus = agreement.bonus ? '$' + parseFloat(agreement.bonus).toFixed(2) : 'N/A';
            
            // Format representing regions
            var representingRegions = 'N/A';
            if (agreement.represent_region) {
                var regions = agreement.represent_region.split(',')
                    .map(function(region) { return region.trim(); })  // Trim whitespace
                    .filter(function(region) { return region !== ''; });  // Remove empty strings
                representingRegions = regions.join(', ');
            }
            
            // Format description
            var description = 'N/A';
            if (agreement.description && agreement.description.trim() !== '') {
                description = escapeHtml(agreement.description);
            }
            
            var documentLink = 'N/A';
            if (agreement.file_upload) {
                var fileName = agreement.file_upload.split('/').pop();
                documentLink = '<a href="javascript:;" class="view_agreement_doc" data-url="' + agreement.file_upload + '">' + crmIcon('file') + ' View</a>';
            }
            
            var actions = '<div class="btn-group" role="group">';
            actions += '<button type="button" class="btn btn-sm btn-primary edit_agreement" data-id="' + agreement.id + '" title="Edit">' + crmIcon('edit') + '</button>';
            
            if (agreement.status === 'inactive') {
                actions += '<button type="button" class="btn btn-sm btn-success set_active_agreement" data-id="' + agreement.id + '" title="Set Active">' + crmIcon('check') + '</button>';
            }
            
            actions += '<button type="button" class="btn btn-sm btn-danger delete_agreement" data-id="' + agreement.id + '" title="Delete">' + crmIcon('trash') + '</button>';
            actions += '</div>';
            
            // Main agreement row
            var row = '<tr>';
            row += '<td>' + (index + 1) + '</td>';
            row += '<td>' + contractStart + '</td>';
            row += '<td>' + contractExpiry + '</td>';
            row += '<td>' + commission + '</td>';
            row += '<td>' + bonus + '</td>';
            row += '<td>' + representingRegions + '</td>';
            row += '<td>' + description + '</td>';
            row += '<td>' + statusBadge + '</td>';
            row += '<td>' + documentLink + '</td>';
            row += '<td>' + actions + '</td>';
            row += '</tr>';
            
            tbody.append(row);
        });
    }
    
    // Helper function to escape HTML to prevent XSS
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Reset agreement form
    function resetAgreementForm() {
        $('#agreementForm')[0].reset();
        $('#agreement_id').val('');
        $('#agreement_gst').prop('checked', false);
        if (typeof clearEnhancedSelectValue === 'function') {
            clearEnhancedSelectValue('#agreement_represent_region');
            clearEnhancedSelectValue('#agreement_default_super_agent');
        } else {
            $('#agreement_represent_region').val(null);
            $('#agreement_default_super_agent').val(null);
        }
        $('#agreement_status').val('active');
        $('#current_file_display').text('');
    }
    
    // Load agreement data for editing
    function loadAgreementData(agreementId) {
        $.ajax({
            url: '{{ url("/partner/agreement/get") }}',
            type: 'GET',
            data: {
                agreement_id: agreementId
            },
            success: function(response) {
                if (response.status) {
                    var agreement = response.agreement;
                    
                    $('#agreement_id').val(agreement.id);
                    setAgreementDateField('#agreement_contract_start', toAgreementDateInputValue(agreement.contract_start));
                    setAgreementDateField('#agreement_contract_expiry', toAgreementDateInputValue(agreement.contract_expiry));
                    $('#agreement_commission_percentage').val(agreement.commission_percentage);
                    $('#agreement_bonus').val(agreement.bonus);
                    $('#agreement_description').val(agreement.description);
                    $('#agreement_gst').prop('checked', agreement.gst == 1);
                    if (typeof setEnhancedSelectValue === 'function') {
                        setEnhancedSelectValue('#agreement_default_super_agent', agreement.default_super_agent);
                    } else {
                        $('#agreement_default_super_agent').val(agreement.default_super_agent);
                    }
                    $('#agreement_status').val(agreement.status);
                    
                    // Set representing regions
                    if (agreement.represent_region) {
                        var regions = agreement.represent_region.split(',');
                        if (typeof setEnhancedSelectValue === 'function') {
                            setEnhancedSelectValue('#agreement_represent_region', regions);
                        } else {
                            $('#agreement_represent_region').val(regions);
                        }
                    } else if (typeof clearEnhancedSelectValue === 'function') {
                        clearEnhancedSelectValue('#agreement_represent_region');
                    } else {
                        $('#agreement_represent_region').val(null);
                    }
                    
                    // Display current file
                    if (agreement.file_upload) {
                        var fileName = agreement.file_upload.split('/').pop();
                        $('#current_file_display').html('Current file: <a href="' + agreement.file_upload + '" target="_blank">' + fileName + '</a>');
                    }
                    
                    $('#agreementModalLabel').text('Edit Agreement');
                    // Use Bootstrap 5 native API or jQuery bridge
                    var agreementModal = document.getElementById('agreementModal');
                    if (agreementModal) {
                        var modal = new bootstrap.Modal(agreementModal);
                        modal.show();
                    }
                } else {
                    toastMsg('Failed to load agreement data', 'error');
                }
            },
            error: function() {
                toastMsg('Error loading agreement data', 'error');
            }
        });
    }
    
    // Save agreement
    function saveAgreement() {
        var formData = new FormData($('#agreementForm')[0]);
        
        $.ajax({
            url: '{{ url("/partner/agreement/store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status) {
                    toastMsg(response.message, 'success');
                    // Use Bootstrap 5 native API to hide modal
                    var agreementModal = document.getElementById('agreementModal');
                    if (agreementModal) {
                        var modal = bootstrap.Modal.getInstance(agreementModal);
                        if (modal) {
                            modal.hide();
                        }
                    }
                    loadPartnerAgreements();
                } else {
                    toastMsg(response.message, 'error');
                }
            },
            error: function(xhr) {
                var errorMsg = 'Error saving agreement';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastMsg(errorMsg, 'error');
            }
        });
    }
    
    // Delete agreement
    function deleteAgreement(agreementId) {
        crmConfirm('Are you sure you want to delete this agreement?').then(function (ok) {
            if (!ok) {
                return;
            }

            $.ajax({
                url: '{{ url("/partner/agreement/delete") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    agreement_id: agreementId
                },
                success: function(response) {
                    if (response.status) {
                        toastMsg(response.message, 'success');
                        loadPartnerAgreements();
                    } else {
                        toastMsg(response.message, 'error');
                    }
                },
                error: function() {
                    toastMsg('Error deleting agreement', 'error');
                }
            });
        });
    }
    
    // Set agreement as active
    function setActiveAgreement(agreementId) {
        $.ajax({
            url: '{{ url("/partner/agreement/set-active") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                agreement_id: agreementId
            },
            success: function(response) {
                if (response.status) {
                    toastMsg(response.message, 'success');
                    loadPartnerAgreements();
                } else {
                    toastMsg(response.message, 'error');
                }
            },
            error: function() {
                toastMsg('Error setting active agreement', 'error');
            }
        });
    }

</script>

@if($activeTab !== 'email-v2')
@push('tinymce-scripts')
@include('partials.tinymce')
@endpush
@endif

@endsection
