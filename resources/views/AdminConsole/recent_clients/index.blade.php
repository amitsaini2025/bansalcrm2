@extends('layouts.adminconsole')
@section('title', 'Recently Modified Clients')
 
@section('content')

<!-- Main Content -->
<div class="main-content">
	<section class="section">
		<div class="section-body">
			<div class="server-error">@include('../Elements/flash-message')</div>
			<div class="custom-error-msg"></div>
			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header">
							<h4>Recently Modified Clients</h4>
							<div class="card-header-action d-flex align-items-center">
								<button type="button"
									class="btn btn-sm btn-outline-primary recent-clients-filter-toggle"
									id="recentClientsFilterToggle"
									title="Show filters"
									aria-expanded="false"
									aria-controls="recentClientsFilterPanel">
									@icon('filter')
								</button>
							</div>
						</div>
						<div class="card-body">
							<!-- Filter Panel (hidden until the header filter icon is clicked) -->
							<div class="filter-panel mb-4" id="recentClientsFilterPanel" style="display: none; background: #f7f7f7; padding: 20px; border: 1px solid #eee; border-radius: 5px;">
								<form method="GET" action="{{ route('adminconsole.recentclients.index') }}" id="filterForm">
									<!-- Search Box -->
									<div class="row mb-3">
										<div class="col-md-12">
											<div class="form-group">
												<label for="search"><strong>@icon('search') Search Clients</strong></label>
												<input type="text" 
													   name="search" 
													   id="search" 
													   class="form-control" 
													   value="{{ @$search }}" 
													   placeholder="Search by name, email, phone, or client ID (e.g. TEST105453)...">
											</div>
										</div>
									</div>
									
									<!-- Date Filters and Quick Buttons -->
									<div class="row mb-3">
										<div class="col-md-3">
											<div class="form-group">
												<label for="from_date"><strong>@icon('calendar-alt') From Date</strong></label>
												<input type="text" 
													   name="from_date" 
													   id="from_date" 
													   class="form-control filterdatepicker" 
													   style="font-size: 14px; padding: 10px; border: 2px solid #007bff;"
													   value="{{ @$fromDate }}" 
													   placeholder="Select start date">
											</div>
										</div>
										<div class="col-md-3">
											<div class="form-group">
												<label for="to_date"><strong>@icon('calendar-alt') To Date</strong></label>
												<input type="text" 
													   name="to_date" 
													   id="to_date" 
													   class="form-control filterdatepicker" 
													   style="font-size: 14px; padding: 10px; border: 2px solid #007bff;"
													   value="{{ @$toDate }}" 
													   placeholder="Select end date">
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label><strong>@icon('clock') Quick Filters</strong></label>
												<div class="btn-group" role="group">
													<button type="button" class="btn btn-outline-primary quick-filter-btn" data-days="0">Today</button>
													<button type="button" class="btn btn-outline-primary quick-filter-btn" data-days="7">This Week</button>
													<button type="button" class="btn btn-outline-primary quick-filter-btn" data-days="30">This Month</button>
												</div>
											</div>
										</div>
									</div>
									
									<!-- Document count, Document storage, Phone, Email filters -->
									<div class="row mb-3">
										<div class="col-md-3">
											<div class="form-group">
												<label for="document_count"><strong>@icon('file') Documents</strong></label>
												<select name="document_count" id="document_count" class="form-control">
													<option value="">All</option>
													<option value="0" {{ (string)@$documentCount === '0' ? 'selected' : '' }}>0</option>
													<option value="1" {{ (string)@$documentCount === '1' ? 'selected' : '' }}>1</option>
													<option value="2" {{ (string)@$documentCount === '2' ? 'selected' : '' }}>2</option>
													<option value="3" {{ (string)@$documentCount === '3' ? 'selected' : '' }}>3</option>
													<option value="4" {{ (string)@$documentCount === '4' ? 'selected' : '' }}>4</option>
													<option value="5" {{ (string)@$documentCount === '5' ? 'selected' : '' }}>5</option>
													<option value="6" {{ (string)@$documentCount === '6' ? 'selected' : '' }}>6</option>
													<option value="7" {{ (string)@$documentCount === '7' ? 'selected' : '' }}>7</option>
													<option value="8" {{ (string)@$documentCount === '8' ? 'selected' : '' }}>8</option>
													<option value="9" {{ (string)@$documentCount === '9' ? 'selected' : '' }}>9</option>
													<option value="10+" {{ (string)@$documentCount === '10+' ? 'selected' : '' }}>10+</option>
												</select>
											</div>
										</div>
										<div class="col-md-3">
											<div class="form-group">
												<label for="doc_storage"><strong>@icon('cloud') Doc Storage</strong></label>
												<select name="doc_storage" id="doc_storage" class="form-control">
													<option value="">All</option>
													<option value="local" {{ @$docStorage === 'local' ? 'selected' : '' }}>Local only</option>
													<option value="aws" {{ @$docStorage === 'aws' ? 'selected' : '' }}>AWS only</option>
													<option value="both" {{ @$docStorage === 'both' ? 'selected' : '' }}>Both</option>
													<option value="none" {{ @$docStorage === 'none' ? 'selected' : '' }}>No documents</option>
												</select>
											</div>
										</div>
										<div class="col-md-3">
											<div class="form-group">
												<label for="no_phone"><strong>@icon('phone') Phone</strong></label>
												<select name="no_phone" id="no_phone" class="form-control">
													<option value="">All</option>
													<option value="1" {{ @$noPhone === '1' ? 'selected' : '' }}>No phone number</option>
												</select>
											</div>
										</div>
										<div class="col-md-3">
											<div class="form-group">
												<label for="no_email"><strong>@icon('envelope') Email</strong></label>
												<select name="no_email" id="no_email" class="form-control">
													<option value="">All</option>
													<option value="1" {{ @$noEmail === '1' ? 'selected' : '' }}>No email address</option>
												</select>
											</div>
										</div>
									</div>
									
									<!-- Applications, Last Activity, Activity Type and Sort Options -->
									<div class="row mb-3">
										<div class="col-md-2">
											<div class="form-group">
												<label for="has_applications"><strong>@icon('file-alt') Applications</strong></label>
												<select name="has_applications" id="has_applications" class="form-control">
													<option value="">All Clients</option>
													<option value="0" {{ @$hasApplications === '0' ? 'selected' : '' }}>No applications created</option>
												</select>
											</div>
										</div>
										<div class="col-md-2">
											<div class="form-group">
												<label for="last_activity_years"><strong>@icon('clock') Last Activity</strong></label>
												<select name="last_activity_years" id="last_activity_years" class="form-control">
													<option value="">All</option>
													<option value="1" {{ @$lastActivityYears === '1' || @$lastActivityYears === 1 ? 'selected' : '' }}>1+ years ago</option>
													<option value="2" {{ @$lastActivityYears === '2' || @$lastActivityYears === 2 ? 'selected' : '' }}>2+ years ago</option>
													<option value="3" {{ @$lastActivityYears === '3' || @$lastActivityYears === 3 ? 'selected' : '' }}>3+ years ago</option>
													<option value="4" {{ @$lastActivityYears === '4' || @$lastActivityYears === 4 ? 'selected' : '' }}>4+ years ago</option>
													<option value="5" {{ @$lastActivityYears === '5' || @$lastActivityYears === 5 ? 'selected' : '' }}>5+ years ago</option>
												</select>
											</div>
										</div>
										<div class="col-md-2">
											<div class="form-group">
												<label for="activity_type"><strong>@icon('filter') Activity Type</strong></label>
												<select name="activity_type" id="activity_type" class="form-control">
													<option value="">All Activities</option>
													<option value="note" {{ @$activityType == 'note' ? 'selected' : '' }}>Notes</option>
													<option value="call" {{ @$activityType == 'call' ? 'selected' : '' }}>Calls</option>
													<option value="email" {{ @$activityType == 'email' ? 'selected' : '' }}>Emails</option>
													<option value="meeting" {{ @$activityType == 'meeting' ? 'selected' : '' }}>Meetings</option>
													<option value="task" {{ @$activityType == 'task' ? 'selected' : '' }}>Tasks</option>
												</select>
											</div>
										</div>
										<div class="col-md-2">
											<div class="form-group">
												<label for="sort_order"><strong>@icon('sort') Sort Order</strong></label>
												<select name="sort_order" id="sort_order" class="form-control">
													<option value="desc" {{ @$sortOrder == 'desc' ? 'selected' : '' }}>Newest First</option>
													<option value="asc" {{ @$sortOrder == 'asc' ? 'selected' : '' }}>Oldest First</option>
												</select>
											</div>
										</div>
										<div class="col-md-2">
											<div class="form-group">
												<label for="per_page"><strong>@icon('list') Per Page</strong></label>
												<select name="per_page" id="per_page" class="form-control">
													<option value="10" {{ (int) @$perPage === 10 ? 'selected' : '' }}>10</option>
													<option value="20" {{ (int) @$perPage === 20 ? 'selected' : '' }}>20</option>
													<option value="25" {{ (int) @$perPage === 25 ? 'selected' : '' }}>25</option>
													<option value="50" {{ (int) @$perPage === 50 ? 'selected' : '' }}>50</option>
													<option value="100" {{ (int) @$perPage === 100 ? 'selected' : '' }}>100</option>
												</select>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-12 d-flex justify-content-end flex-wrap" style="gap: 8px;">
											<button type="submit" class="btn btn-primary">
												@icon('filter') Apply Filters
											</button>
											<button type="button" class="btn btn-info" id="refreshBtn" title="Refresh Data">
												@icon('sync-alt') Refresh
											</button>
											<a href="{{ route('adminconsole.recentclients.index') }}" class="btn btn-secondary">
												@icon('times') Clear
											</a>
										</div>
									</div>
									
									<!-- Hidden field for sort column -->
									<input type="hidden" name="sort_column" id="sort_column" value="{{ @$sortColumn }}">
								</form>
							</div>
							<div id="recent-clients-results">
							@if($lists->count() > 0)
							<div class="mb-3 d-flex align-items-center flex-wrap">
								<button type="button" class="btn btn-danger mr-2" id="bulkArchiveBtn" disabled title="Select one or more clients to archive">
									@icon('archive') Bulk Archive
								</button>
								@if(in_array(@$docStorage, ['local', 'both'], true))
								<button type="button" class="btn btn-success mr-2" id="bulkUploadAllDocsToS3Btn" disabled title="Select one or more clients to upload all docs to S3">
									@icon('cloud-upload-alt') Upload All Docs To S3
								</button>
								@endif
								<span class="text-muted small" id="selectedCountText">0 selected</span>
							</div>
							@endif
							<div class="table-responsive common_table"> 
								<table class="table text_wrap table-striped recent-clients-table">
								<thead>
									<tr>
										<th style="width: 40px;">
											<label class="mb-0 d-flex align-items-center">
												<input type="checkbox" id="selectAllClients" class="client-select-all" title="Select all on this page">
												<span class="ml-1 small">All</span>
											</label>
										</th>
										<th class="sortable-header" data-sort-column="client_name" style="cursor: pointer;">
											Client Name
											@if(@$sortColumn == 'client_name')
												@if(@$sortOrder == 'desc')
													@icon('sort-down', 'solid', ['class' => 'ml-1'])
												@else
													@icon('sort-up', 'solid', ['class' => 'ml-1'])
												@endif
											@else
												@icon('sort', 'solid', ['class' => 'ml-1 text-muted'])
											@endif
										</th>
										<th class="sortable-header" data-sort-column="client_email" style="cursor: pointer;">
											Email
											@if(@$sortColumn == 'client_email')
												@if(@$sortOrder == 'desc')
													@icon('sort-down', 'solid', ['class' => 'ml-1'])
												@else
													@icon('sort-up', 'solid', ['class' => 'ml-1'])
												@endif
											@else
												@icon('sort', 'solid', ['class' => 'ml-1 text-muted'])
											@endif
										</th>
										<th class="sortable-header" data-sort-column="client_phone" style="cursor: pointer;">
											Phone
											@if(@$sortColumn == 'client_phone')
												@if(@$sortOrder == 'desc')
													@icon('sort-down', 'solid', ['class' => 'ml-1'])
												@else
													@icon('sort-up', 'solid', ['class' => 'ml-1'])
												@endif
											@else
												@icon('sort', 'solid', ['class' => 'ml-1 text-muted'])
											@endif
										</th>
										<th>Last Activity</th>
										<th class="sortable-header" data-sort-column="activity_date" style="cursor: pointer;">
											Activity Date
											@if(@$sortColumn == 'activity_date')
												@if(@$sortOrder == 'desc')
													@icon('sort-down', 'solid', ['class' => 'ml-1'])
												@else
													@icon('sort-up', 'solid', ['class' => 'ml-1'])
												@endif
											@else
												@icon('sort', 'solid', ['class' => 'ml-1 text-muted'])
											@endif
										</th>
										<th class="sortable-header" data-sort-column="modified_by" style="cursor: pointer;">
											Modified By
											@if(@$sortColumn == 'modified_by')
												@if(@$sortOrder == 'desc')
													@icon('sort-down', 'solid', ['class' => 'ml-1'])
												@else
													@icon('sort-up', 'solid', ['class' => 'ml-1'])
												@endif
											@else
												@icon('sort', 'solid', ['class' => 'ml-1 text-muted'])
											@endif
										</th>
										<th>Action</th>
									</tr>
								</thead>
								@if($lists->count() > 0)
								<tbody class="tdata">	
								@foreach (@$lists as $list)
									<tr id="id_{{@$list->activity_id}}" class="client-row">
										<td>
											@if(@$list->client_id)
												<input type="checkbox" class="client-checkbox" name="client_ids[]" value="{{ @$list->client_id }}" data-client-id="{{ @$list->client_id }}">
											@else
												<span class="text-muted">—</span>
											@endif
										</td>
										<td class="cell-wrap">
											@if(@$list->client_id)
												<a href="javascript:void(0);" 
												   class="client-name-toggle" 
												   data-client-id="{{ @$list->client_id }}"
												   data-activity-id="{{ @$list->activity_id }}"
												   style="cursor: pointer; text-decoration: none; color: inherit;">
													@icon('chevron-right', 'solid', ['class' => 'toggle-icon', 'attrs' => ['style' => 'margin-right: 5px; transition: transform 0.3s;']])
													@if(@$list->client_firstname || @$list->client_lastname)
														{{ @$list->client_firstname }} {{ @$list->client_lastname }}
													@else
														<span class="text-muted">{{ config('constants.empty') }}</span>
													@endif
												</a>
												@if(!empty(@$list->client_unique_id))
													<div class="mt-1"><a href="{{ URL::to('/clients/detail/'.base64_encode(convert_uuencode(@$list->client_id))) }}" target="_blank" rel="noopener noreferrer" class="client-unique-id-link" title="Open client in new tab">{{ @$list->client_unique_id }}</a></div>
												@endif
											@else
												@if(@$list->client_firstname || @$list->client_lastname)
													{{ @$list->client_firstname }} {{ @$list->client_lastname }}
												@else
													<span class="text-muted">{{ config('constants.empty') }}</span>
												@endif
												@if(!empty(@$list->client_unique_id))
													<div class="mt-1"><a href="{{ URL::to('/clients/detail/'.base64_encode(convert_uuencode(@$list->client_id))) }}" target="_blank" rel="noopener noreferrer" class="client-unique-id-link" title="Open client in new tab">{{ @$list->client_unique_id }}</a></div>
												@endif
											@endif
										</td>
										<td>{{ empty(@$list->client_email) ? config('constants.empty') : \Illuminate\Support\Str::limit(@$list->client_email, 40, '...') }}</td> 	
										<td>{{ @$list->client_phone == "" ? config('constants.empty') : @$list->client_phone }}</td> 	
										<td class="cell-wrap cell-wrap-activity">
											<div style="max-width: 300px;">
												<strong>{{ @$list->subject }}</strong>
												@if(@$list->description)
													<div class="text-muted small mt-1">
														{!! \Illuminate\Support\Str::limit(strip_tags(@$list->description), 60, '...') !!}
													</div>
												@endif
											</div>
										</td>
										<td>
											@if(@$list->activity_date)
												<div>{{ \Carbon\Carbon::parse(@$list->activity_date)->format('d/m/Y') }}</div>
												<small class="text-muted">{{ \Carbon\Carbon::parse(@$list->activity_date)->format('h:i A') }}</small>
												<div class="text-muted small">{{ \Carbon\Carbon::parse(@$list->activity_date)->diffForHumans() }}</div>
											@else
												{{ config('constants.empty') }}
											@endif
										</td>
										<td>
											@if(@$list->admin_firstname || @$list->admin_lastname)
												{{ @$list->admin_firstname }} {{ @$list->admin_lastname }}
											@else
												<span class="text-muted">{{ config('constants.empty') }}</span>
											@endif
										</td>
										<td>
											@if(@$list->client_id)
												<a href="{{ URL::to('/clients/detail/'.base64_encode(convert_uuencode(@$list->client_id))) }}" class="btn btn-sm btn-primary" title="View Client">
													@icon('eye', 'regular') View
												</a>
											@else
												<span class="text-muted">N/A</span>
											@endif
										</td>
									</tr>
									<!-- Expandable row for client details -->
									@if(@$list->client_id)
									<tr id="client-details-{{ @$list->client_id }}" class="client-details-row" style="display: none;">
										<td colspan="8" style="background-color: #f8f9fa; padding: 20px;">
											<div class="client-details-content" id="client-details-content-{{ @$list->client_id }}">
												<div class="text-center">
													@icon('spinner', 'solid', ['spin' => true]) Loading...
												</div>
											</div>
										</td>
									</tr>
									@endif
								@endforeach	 
								</tbody>
								@else
								<tbody>
									<tr>
										<td style="text-align:center;" colspan="8">
											No records found
										</td>
									</tr>
								</tbody>
								@endif
							</table> 
						</div>
						@if($lists->count() > 0)
							<div class="card-footer">
								{{ $lists->links() }}
							</div>
						@endif
							</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</div>

<!-- Modal: Documents by category (Application / Education / Migration) -->
<div class="modal fade" id="clientDocumentsModal" tabindex="-1" role="dialog" aria-labelledby="clientDocumentsModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="clientDocumentsModalLabel">@icon('file') <span id="clientDocumentsModalTitle">Documents</span></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div id="clientDocumentsModalBody">
					<div class="text-center py-4">@icon('spinner', 'solid', ['spin' => true]) Loading...</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>

@push('styles')
<style>
	/* Allow long text to wrap in Client Name and Last Activity columns (no overlap) */
	table.recent-clients-table td.cell-wrap {
		white-space: normal;
		word-break: break-word;
		overflow-wrap: break-word;
		min-width: 0;
	}
	table.recent-clients-table td.cell-wrap-activity {
		max-width: 320px;
	}
	.client-unique-id-link {
		color: #007bff;
		text-decoration: none;
		font-size: 0.9em;
	}
	.client-unique-id-link:hover {
		color: #0056b3;
		text-decoration: underline;
	}
	.client-name-toggle {
		display: inline-flex;
		align-items: center;
		transition: color 0.2s;
	}
	.client-name-toggle:hover {
		color: #007bff !important;
	}
	.client-name-toggle .toggle-icon {
		font-size: 0.8em;
	}
	.client-details-row td {
		border-top: 2px solid #dee2e6;
	}
	.client-details-content h6 {
		color: #495057;
		margin-bottom: 10px;
		font-weight: 600;
	}
	.client-details-content .card {
		border: 1px solid #e9ecef;
		border-radius: 5px;
	}
	.btn-archive-client {
		/* Small button - no min-width needed */
	}
	.recent-clients-filter-toggle {
		margin-right: 8px;
		padding: 4px 10px;
		line-height: 1;
	}
	.recent-clients-filter-toggle.is-open {
		background: #6777ef;
		color: #fff;
		border-color: #6777ef;
	}
	
	/* Enhanced date picker styling */
	.filterdatepicker {
		font-weight: 500;
		transition: all 0.3s ease;
	}
	.filterdatepicker:focus {
		border-color: #0056b3 !important;
		box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
	}
	
	/* Quick filter buttons */
	.quick-filter-btn {
		margin-right: 5px;
		margin-bottom: 5px;
	}
	.quick-filter-btn:hover {
		transform: translateY(-1px);
		box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	}
	
	/* Sortable header styling */
	.sortable-header {
		user-select: none;
		position: relative;
	}
	.sortable-header:hover {
		background-color: #f8f9fa;
	}
	.sortable-header i {
		opacity: 0.5;
		transition: opacity 0.2s;
	}
	.sortable-header:hover i {
		opacity: 1;
	}
	
	/* Search box styling */
	#search {
		font-size: 15px;
		padding: 12px;
	}
	#search:focus {
		border-color: #007bff;
		box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
	}
	
	/* Refresh button animation */
	#refreshBtn i.icon-spin {
		animation: crm-icon-spin 1s infinite linear;
	}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
	$('#recentClientsFilterToggle').on('click', function() {
		var $btn = $(this);
		var $panel = $('#recentClientsFilterPanel');
		$panel.slideToggle(200, function() {
			var open = $panel.is(':visible');
			$btn.toggleClass('is-open', open)
				.attr('aria-expanded', open ? 'true' : 'false')
				.attr('title', open ? 'Hide filters' : 'Show filters');
		});
	});

	// Wait for flatpickr to be available (it's loaded via Vite)
	function initDatePickers() {
		if (typeof flatpickr !== 'undefined' && $(".filterdatepicker").length) {
			$(".filterdatepicker").each(function() {
				flatpickr(this, {
					dateFormat: "Y-m-d",
					allowInput: true
				});
			});
		} else if ($(".filterdatepicker").length) {
			// Retry after a short delay if flatpickr isn't loaded yet
			setTimeout(initDatePickers, 100);
		}
	}
	
	// Initialize date pickers
	initDatePickers();

	function loadRecentClientsResults(url, pushUrl) {
		var $box = $('#recent-clients-results');
		if (!$box.length || !url) {
			window.location.href = url;
			return;
		}

		$box.css('opacity', 0.55);
		$.ajax({
			url: url,
			type: 'GET',
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
			success: function(html) {
				var doc = new DOMParser().parseFromString(html, 'text/html');
				var fresh = doc.getElementById('recent-clients-results');
				if (!fresh) {
					window.location.href = url;
					return;
				}
				$('#recent-clients-results').replaceWith(fresh);
				if (pushUrl && window.history && window.history.pushState) {
					window.history.pushState({ recentClientsPage: true }, '', url);
				}
			},
			error: function() {
				window.location.href = url;
			}
		});
	}

	$(document).on('click', '#recent-clients-results .pagination a', function(e) {
		var href = $(this).attr('href');
		if (!href || href === '#') {
			e.preventDefault();
			return;
		}
		if ($(this).closest('.disabled, .active').length) {
			e.preventDefault();
			return;
		}
		if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.which === 2) {
			return;
		}
		e.preventDefault();
		loadRecentClientsResults(href, true);
	});

	var skipRecentClientsPopstate = true;
	$(window).on('popstate', function() {
		if (skipRecentClientsPopstate || !$('#recent-clients-results').length) {
			return;
		}
		loadRecentClientsResults(window.location.href, false);
	});
	setTimeout(function() {
		skipRecentClientsPopstate = false;
	}, 0);
	
	// Quick filter buttons (Today, This Week, This Month)
	$('.quick-filter-btn').on('click', function() {
		var days = $(this).data('days');
		var today = new Date();
		var fromDate = new Date();
		
		if (days === 0) {
			// Today
			fromDate.setHours(0, 0, 0, 0);
		} else {
			// This Week or This Month
			fromDate.setDate(today.getDate() - days);
			fromDate.setHours(0, 0, 0, 0);
		}
		
		var toDate = new Date(today);
		toDate.setHours(23, 59, 59, 999);
		
		// Format dates as YYYY-MM-DD
		var formatDate = function(date) {
			var year = date.getFullYear();
			var month = String(date.getMonth() + 1).padStart(2, '0');
			var day = String(date.getDate()).padStart(2, '0');
			return year + '-' + month + '-' + day;
		};
		
		$('#from_date').val(formatDate(fromDate));
		$('#to_date').val(formatDate(toDate));
		
		// Update flatpickr if it's initialized
		if (typeof flatpickr !== 'undefined') {
			var fromPicker = $('#from_date')[0]._flatpickr;
			var toPicker = $('#to_date')[0]._flatpickr;
			if (fromPicker) {
				fromPicker.setDate(fromDate, false);
			}
			if (toPicker) {
				toPicker.setDate(toDate, false);
			}
		}
	});
	
	// Column header sorting
	$(document).on('click', '#recent-clients-results .sortable-header', function() {
		var sortColumn = $(this).data('sort-column');
		var currentSortColumn = $('#sort_column').val();
		var currentSort = $('#sort_order').val() || 'desc';
		
		// If clicking the same column, toggle sort order; otherwise, set to desc
		if (sortColumn === currentSortColumn) {
			var newSort = currentSort === 'desc' ? 'asc' : 'desc';
			$('#sort_order').val(newSort);
		} else {
			$('#sort_column').val(sortColumn);
			$('#sort_order').val('desc');
		}
		
		$('#filterForm').submit();
	});
	
	// Refresh button
	$('#refreshBtn').on('click', function() {
		var $btn = $(this);
		var $icon = $btn.find('svg.crm-icon, i[data-lucide], i.crm-icon').first();
		
		// Add spinning animation
		$icon.addClass('icon-spin');
		$btn.prop('disabled', true);
		
		// Reload the page with current filters
		window.location.reload();
	});
	
	// Auto-submit on per_page change
	$('#per_page').on('change', function() {
		$('#filterForm').submit();
	});
	
	// Handle client name click to expand/collapse details
	$(document).on('click', '#recent-clients-results .client-name-toggle', function(e) {
		e.preventDefault();
		var $toggle = $(this);
		var clientId = $toggle.data('client-id');
		var activityId = $toggle.data('activity-id');
		var $detailsRow = $('#client-details-' + clientId);
		var $icon = $toggle.find('.toggle-icon');
		var $detailsContent = $('#client-details-content-' + clientId);
		
		// Toggle the row visibility
		if ($detailsRow.is(':visible')) {
			// Collapse
			$detailsRow.slideUp(300);
			$icon.css('transform', 'rotate(0deg)');
		} else {
			// Expand
			$detailsRow.slideDown(300);
			$icon.css('transform', 'rotate(90deg)');
			
			// Load details if not already loaded
			if ($detailsContent.find('.client-details-loaded').length === 0) {
				loadClientDetails(clientId, $detailsContent);
			}
		}
	});
	
	// Helper function to strip HTML tags and get plain text
	function stripHtml(html) {
		if (!html) return '';
		var tmp = document.createElement('DIV');
		tmp.innerHTML = html;
		return tmp.textContent || tmp.innerText || '';
	}
	
	// Function to load client details via AJAX
	function loadClientDetails(clientId, $container) {
		$container.html('<div class="text-center">' + crmIconSpinner(' Loading...') + '</div>');
		
		var detailsUrl = '{{ route("adminconsole.recentclients.getdetails") }}';
		
		$.ajax({
			url: detailsUrl,
			type: 'POST',
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			},
			data: {
				client_id: clientId
			},
			success: function(response) {
				if (response.success) {
					var data = response.data;
					var html = '<div class="client-details-loaded">';
					html += '<div class="row">';
					
					// Last Activity Section
					html += '<div class="col-md-6 mb-3">';
					html += '<h6>@icon('history') Last Activity</h6>';
					if (data.last_activity) {
						html += '<div class="card p-3" style="background: white;">';
						html += '<strong>' + (data.last_activity.subject || 'N/A') + '</strong>';
						if (data.last_activity.description) {
							// Show full description without truncation, strip HTML tags for clean display
							var descriptionText = stripHtml(data.last_activity.description);
							html += '<div class="mb-2 text-muted small" style="white-space: pre-wrap; word-wrap: break-word; max-height: 300px; overflow-y: auto;">' + 
								descriptionText + 
								'</div>';
						}
						html += '<div class="text-muted small">';
						html += crmIcon('calendar', 'regular') + ' ' + data.last_activity.date;
						html += ' | ' + crmIcon('user', 'regular') + ' ' + data.last_activity.created_by;
						html += '</div>';
						html += '</div>';
					} else {
						html += '<p class="text-muted">No activity found</p>';
					}
					html += '</div>';
					
					// Document Count & Storage Section
					html += '<div class="col-md-6 mb-3">';
					html += '<h6>' + crmIcon('file') + ' Documents</h6>';
					html += '<div class="card p-3" style="background: white;">';
					html += '<div class="d-flex align-items-center">';
					html += '<span class="badge badge-primary" style="font-size: 1.2em; padding: 8px 12px;">' + data.document_count + '</span>';
					html += '<span class="ml-2">' + (data.document_count === 1 ? 'file' : 'files') + ' found</span>';
					html += '</div>';
					var storageLabel = (data.document_storage === 'both') ? 'Local & AWS' : ((data.document_storage === 'local') ? 'Local only' : ((data.document_storage === 'aws') ? 'AWS only' : '—'));
					if (data.document_count > 0 && data.document_storage) {
						html += '<div class="mt-2"><small class="text-muted">Storage: </small><strong>' + storageLabel + '</strong></div>';
						if (data.document_storage === 'both' && data.count_local != null && data.count_aws != null) {
							html += '<div class="mt-2"><small class="text-muted">Total Local Found: </small><strong>' + data.count_local + '</strong></div>';
							html += '<div class="mt-1"><small class="text-muted">Total AWS Found: </small><strong>' + data.count_aws + '</strong></div>';
						}
					}
					// Category doc counts (local/public folder only) - clickable to show documents in popup
					html += '<div class="mt-3 pt-2 border-top">';
					html += '<small class="text-muted d-block mb-1">In public folder (not S3):</small>';
					html += '<div class="d-flex flex-wrap gap-2 align-items-center">';
					html += '<span class="badge badge-secondary doc-category-badge" data-client-id="' + clientId + '" data-category="application" data-count="' + (data.application_doc_count_local != null ? data.application_doc_count_local : 0) + '" style="cursor: pointer;" title="Click to view documents">Application: ' + (data.application_doc_count_local != null ? data.application_doc_count_local : 0) + '</span>';
					html += '<span class="badge badge-secondary doc-category-badge" data-client-id="' + clientId + '" data-category="education" data-count="' + (data.education_doc_count_local != null ? data.education_doc_count_local : 0) + '" style="cursor: pointer;" title="Click to view documents">Education: ' + (data.education_doc_count_local != null ? data.education_doc_count_local : 0) + '</span>';
					html += '<span class="badge badge-secondary doc-category-badge" data-client-id="' + clientId + '" data-category="migration" data-count="' + (data.migration_doc_count_local != null ? data.migration_doc_count_local : 0) + '" style="cursor: pointer;" title="Click to view documents">Migration: ' + (data.migration_doc_count_local != null ? data.migration_doc_count_local : 0) + '</span>';
					if (data.document_storage === 'local' || data.document_storage === 'both') {
						html += '<button type="button" class="btn btn-sm btn-outline-success btn-upload-all-docs-to-s3" data-client-id="' + clientId + '" title="Upload all Application, Education and Migration documents to S3">' + crmIcon('cloud-upload-alt') + ' Upload All These Docs to S3</button>';
					}
					html += '</div>';
					// Status lines: All Docs uploaded at S3, All Docs Public Path Removed (green = Yes, red = No)
					// Use public_path counts (docs that still have a public copy: local-only OR on S3 with doc_public_path) so status matches popup
					var allDocsAtS3 = (data.document_count === 0) || (data.document_storage === 'aws');
					var appPublic = (data.application_public_path_count != null ? data.application_public_path_count : 0);
					var eduPublic = (data.education_public_path_count != null ? data.education_public_path_count : 0);
					var migPublic = (data.migration_public_path_count != null ? data.migration_public_path_count : 0);
					var allPublicRemoved = (appPublic === 0 && eduPublic === 0 && migPublic === 0);
					html += '<div class="mt-3 pt-2 border-top">';
					html += '<div class="small ' + (allDocsAtS3 ? 'text-success' : 'text-danger') + '"><strong>All Docs are uploaded at S3 - ' + (allDocsAtS3 ? 'Yes' : 'No') + '</strong></div>';
					html += '<div class="small mt-1 ' + (allPublicRemoved ? 'text-success' : 'text-danger') + '"><strong>All Docs Public Path Removed - ' + (allPublicRemoved ? 'Yes' : 'No') + '</strong></div>';
					html += '</div>';
					html += '</div>';
					html += '</div>';
					html += '</div>';
					
					// Archive Button Section
					html += '<div class="col-md-12 mt-3">';
					html += '<div class="card p-3" style="background: white;">';
					html += '<h6>' + crmIcon('archive') + ' Actions</h6>';
					if (data.is_archived) {
						html += '<button type="button" class="btn btn-sm btn-warning btn-archive-client" data-client-id="' + clientId + '" data-action="unarchive">';
						html += crmIcon('undo') + ' Unarchive Client';
						html += '</button>';
						html += '<span class="ml-2 text-muted">' + crmIcon('info-circle') + ' This client is currently archived</span>';
					} else {
						html += '<button type="button" class="btn btn-sm btn-danger btn-archive-client" data-client-id="' + clientId + '" data-action="archive">';
						html += crmIcon('archive') + ' Archive Client';
						html += '</button>';
						html += '<span class="ml-2 text-muted">' + crmIcon('info-circle') + ' Archive this client to move it to archived clients</span>';
					}
					html += '</div>';
					html += '</div>';
					
					html += '</div>'; // End row
					html += '</div>'; // End client-details-loaded
					
					$container.html(html);
				} else {
					$container.html('<div class="alert alert-danger">Error: ' + (response.message || 'Failed to load client details') + '</div>');
				}
			},
			error: function(xhr) {
				var errorMsg = 'Failed to load client details';
				if (xhr.responseJSON && xhr.responseJSON.message) {
					errorMsg = xhr.responseJSON.message;
				}
				$container.html('<div class="alert alert-danger">' + errorMsg + '</div>');
			}
		});
	}
	
	// Close Migration/documents modal when Close button or X is clicked
	$(document).on('click', '#clientDocumentsModal .btn-close, #clientDocumentsModal .modal-footer .btn-secondary', function(e) {
		e.preventDefault();
		$('#clientDocumentsModal').modal('hide');
	});

	// Click on Application / Education / Migration badge: show documents in modal
	$(document).on('click', '.doc-category-badge', function() {
		var clientId = $(this).data('client-id');
		var category = $(this).data('category');
		var count = $(this).data('count');
		if (!clientId || !category) return;
		var categoryLabel = category.charAt(0).toUpperCase() + category.slice(1);
		$('#clientDocumentsModalTitle').text(categoryLabel + ' documents');
		$('#clientDocumentsModalBody').html('<div class="text-center py-4">' + crmIconSpinner(' Loading...') + '</div>');
		$('#clientDocumentsModal').modal('show');
		$.ajax({
			url: '{{ route("adminconsole.recentclients.documentsbycategory") }}',
			type: 'POST',
			headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
			data: { client_id: clientId, category: category },
			success: function(response) {
				if (response.success) {
					var docs = response.documents || [];
					var label = response.category_label || categoryLabel;
					if (docs.length === 0) {
						$('#clientDocumentsModalBody').html('<p class="text-muted mb-0">No documents in public folder for this category.</p>');
					} else {
						var html = '<p class="text-muted small mb-2">' + docs.length + ' document(s)</p>';
						// Show "Delete All [Category] Public Docs" only when all docs are on S3 and have a public (local) copy
						var allOnS3WithPublic = docs.length > 0 && docs.every(function(d) { return d.is_on_s3 && d.has_public_path; });
						if (allOnS3WithPublic) {
							html += '<div class="mb-3"><button type="button" class="btn btn-sm btn-outline-danger btn-delete-all-public-docs" data-client-id="' + clientId + '" data-category="' + category + '" title="Delete all local copies; documents remain on S3">' + crmIcon('trash-alt') + ' Delete All ' + label + ' Public Docs</button></div>';
						}
						html += '<ul class="list-group list-group-flush">';
						for (var i = 0; i < docs.length; i++) {
							var d = docs[i];
							html += '<li class="list-group-item d-flex justify-content-between align-items-start" data-document-id="' + d.id + '">';
							html += '<div class="text-break flex-grow-1 mr-2" style="min-width: 0;">';
							html += '<span style="word-wrap: break-word; overflow-wrap: break-word;"><strong>' + (i + 1) + '.</strong> ' + (d.file_name || 'Document #' + d.id) + '</span>';
							if (d.created_at) html += '<br><small class="text-muted">' + d.created_at + '</small>';
							if (!d.is_on_s3) {
								html += '<br><button type="button" class="btn btn-sm btn-outline-danger btn-delete-document mt-1" data-document-id="' + d.id + '" title="Permanently delete this document">' + crmIcon('trash-alt') + ' Delete Document</button>';
							}
							html += '</div>';
							html += '<span class="d-flex align-items-center flex-wrap flex-shrink-0" style="gap: 10px;">';
							if (d.preview_url) {
								html += '<a href="' + d.preview_url + '" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary doc-view-link">' + crmIcon('external-link-alt') + ' View</a>';
							}
							if (!d.is_on_s3) {
								html += '<button type="button" class="btn btn-sm btn-outline-success btn-upload-doc-to-s3" data-document-id="' + d.id + '" title="Upload this document to S3">' + crmIcon('cloud-upload-alt') + ' Upload to S3</button>';
							}
							if (d.has_public_path) {
								html += '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-public-doc" data-document-id="' + d.id + '" title="Delete the local copy (document remains on S3)">' + crmIcon('trash-alt') + ' Delete public doc</button>';
							}
							html += '</span></li>';
						}
						html += '</ul>';
						$('#clientDocumentsModalBody').html(html);
					}
				} else {
					$('#clientDocumentsModalBody').html('<div class="alert alert-danger">' + (response.message || 'Failed to load documents') + '</div>');
				}
			},
			error: function(xhr) {
				var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to load documents';
				$('#clientDocumentsModalBody').html('<div class="alert alert-danger">' + msg + '</div>');
			}
		});
	});

	// Delete all public documents in this category (modal) - only when all docs are on S3 and have local copy
	$(document).on('click', '.btn-delete-all-public-docs', function() {
		var $btn = $(this);
		var clientId = $btn.data('client-id');
		var category = $btn.data('category');
		if (!clientId || !category) return;
		crmConfirm('Delete all local (public) copies for these documents? They will remain on S3.').then(function (ok) {
			if (!ok) {
				return;
			}
		$btn.prop('disabled', true).html(crmIconSpinner(' Deleting...'));
		$.ajax({
			url: '{{ route("adminconsole.recentclients.deleteallpublicdocsbycategory") }}',
			type: 'POST',
			headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
			data: { client_id: clientId, category: category },
			success: function(response) {
				if (response.message) {
					toastMsg(response.message, response.success ? 'success' : 'warning');
				}
				// Refetch document list so modal updates (button may disappear if no public docs left)
				$.ajax({
					url: '{{ route("adminconsole.recentclients.documentsbycategory") }}',
					type: 'POST',
					headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
					data: { client_id: clientId, category: category },
					success: function(resp) {
						if (resp.success) {
							var docs = resp.documents || [];
							var label = resp.category_label || category.charAt(0).toUpperCase() + category.slice(1);
							if (docs.length === 0) {
								$('#clientDocumentsModalBody').html('<p class="text-muted mb-0">No documents in public folder for this category.</p>');
							} else {
								var html = '<p class="text-muted small mb-2">' + docs.length + ' document(s)</p>';
								var allOnS3WithPublic = docs.length > 0 && docs.every(function(d) { return d.is_on_s3 && d.has_public_path; });
								if (allOnS3WithPublic) {
									html += '<div class="mb-3"><button type="button" class="btn btn-sm btn-outline-danger btn-delete-all-public-docs" data-client-id="' + clientId + '" data-category="' + category + '" title="Delete all local copies; documents remain on S3">' + crmIcon('trash-alt') + ' Delete All ' + label + ' Public Docs</button></div>';
								}
								html += '<ul class="list-group list-group-flush">';
								for (var i = 0; i < docs.length; i++) {
									var d = docs[i];
									html += '<li class="list-group-item d-flex justify-content-between align-items-start" data-document-id="' + d.id + '">';
									html += '<div class="text-break flex-grow-1 mr-2" style="min-width: 0;">';
									html += '<span style="word-wrap: break-word; overflow-wrap: break-word;"><strong>' + (i + 1) + '.</strong> ' + (d.file_name || 'Document #' + d.id) + '</span>';
									if (d.created_at) html += '<br><small class="text-muted">' + d.created_at + '</small>';
									if (!d.is_on_s3) {
										html += '<br><button type="button" class="btn btn-sm btn-outline-danger btn-delete-document mt-1" data-document-id="' + d.id + '" title="Permanently delete this document">' + crmIcon('trash-alt') + ' Delete Document</button>';
									}
									html += '</div>';
									html += '<span class="d-flex align-items-center flex-wrap flex-shrink-0" style="gap: 10px;">';
									if (d.preview_url) {
										html += '<a href="' + d.preview_url + '" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary doc-view-link">' + crmIcon('external-link-alt') + ' View</a>';
									}
									if (!d.is_on_s3) {
										html += '<button type="button" class="btn btn-sm btn-outline-success btn-upload-doc-to-s3" data-document-id="' + d.id + '" title="Upload this document to S3">' + crmIcon('cloud-upload-alt') + ' Upload to S3</button>';
									}
									if (d.has_public_path) {
										html += '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-public-doc" data-document-id="' + d.id + '" title="Delete the local copy (document remains on S3)">' + crmIcon('trash-alt') + ' Delete public doc</button>';
									}
									html += '</span></li>';
								}
								html += '</ul>';
								$('#clientDocumentsModalBody').html(html);
							}
						} else {
							$('#clientDocumentsModalBody').html('<div class="alert alert-danger">' + (resp.message || 'Failed to load documents') + '</div>');
						}
					},
					error: function() {
						$('#clientDocumentsModalBody').html('<div class="alert alert-danger">Failed to refresh document list.</div>');
					}
				});
				var catLabel = (category && category.length) ? (category.charAt(0).toUpperCase() + category.slice(1)) : 'Public';
				$btn.prop('disabled', false).html('' + crmIcon('trash-alt') + ' Delete All ' + catLabel + ' Public Docs');
			},
			error: function(xhr) {
				var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete public documents';
				toastMsg(msg, 'error');
				var catLabel = (category && category.length) ? (category.charAt(0).toUpperCase() + category.slice(1)) : 'Public';
				$btn.prop('disabled', false).html('' + crmIcon('trash-alt') + ' Delete All ' + catLabel + ' Public Docs');
			}
		});
		});
	});

	// Upload all Application, Education, Migration documents to S3 (button in expanded client details)
	$(document).on('click', '.btn-upload-all-docs-to-s3', function() {
		var $btn = $(this);
		var clientId = $btn.data('client-id');
		if (!clientId) return;
		crmConfirm('Upload all Application, Education and Migration documents (in public folder) to S3 for this client?').then(function (ok) {
			if (!ok) {
				return;
			}
		$btn.prop('disabled', true).html(crmIconSpinner(' Uploading...'));
		var $detailsContent = $('#client-details-content-' + clientId);
		$.ajax({
			url: '{{ route("adminconsole.recentclients.uploadalldocumentstos3") }}',
			type: 'POST',
			headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
			data: { client_id: clientId },
			success: function(response) {
				if (response.message) {
					toastMsg(response.message, response.success ? 'success' : 'warning');
				}
				if ($detailsContent.length) loadClientDetails(clientId, $detailsContent);
				$btn.prop('disabled', false).html('' + crmIcon('cloud-upload-alt') + ' Upload All These Docs to S3');
			},
			error: function(xhr) {
				var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Upload to S3 failed';
				toastMsg(msg, 'error');
				$btn.prop('disabled', false).html('' + crmIcon('cloud-upload-alt') + ' Upload All These Docs to S3');
			}
		});
		});
	});

	// Upload single document to S3 (button in documents modal)
	$(document).on('click', '.btn-upload-doc-to-s3', function() {
		var $btn = $(this);
		var documentId = $btn.data('document-id');
		if (!documentId) return;
		$btn.prop('disabled', true).html(crmIconSpinner(' Uploading...'));
		$.ajax({
			url: '{{ route("adminconsole.recentclients.uploaddocumenttos3") }}',
			type: 'POST',
			headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
			data: { document_id: documentId },
			success: function(response) {
				if (response.success && response.s3_url) {
					var $li = $btn.closest('li');
					var $viewLink = $li.find('a.doc-view-link');
					$viewLink.attr('href', response.s3_url).attr('target', '_blank');
					$btn.remove();
					// Show "Delete public doc" after View (local copy can be removed now that S3 has it)
					var deleteBtn = '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-public-doc ml-1" data-document-id="' + (response.document_id || documentId) + '" title="Delete the local copy (document remains on S3)">' + crmIcon('trash-alt') + ' Delete public doc</button>';
					$viewLink.after(deleteBtn);
				} else {
					toastMsg(response.message || 'Upload failed', 'error');
					$btn.prop('disabled', false).html('' + crmIcon('cloud-upload-alt') + ' Upload to S3');
				}
			},
			error: function(xhr) {
				var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Upload to S3 failed';
				toastMsg(msg, 'error');
				$btn.prop('disabled', false).html('' + crmIcon('cloud-upload-alt') + ' Upload to S3');
			}
		});
	});

	// Delete document permanently (documents table + public path). Only for super admin/admin; only when doc is not on S3.
	$(document).on('click', '.btn-delete-document', function() {
		var $btn = $(this);
		var documentId = $btn.data('document-id');
		if (!documentId) return;
		crmConfirm('Are you sure you want to permanently delete this document? It will be removed from the documents list and from the server.').then(function (ok) {
			if (!ok) {
				return;
			}
		$btn.prop('disabled', true).html(crmIconSpinner(' Deleting...'));
		$.ajax({
			url: '{{ route("adminconsole.recentclients.deletedocument") }}',
			type: 'POST',
			headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
			data: { document_id: documentId },
			success: function(response) {
				if (response.success) {
					$btn.closest('li').fadeOut(300, function() { $(this).remove(); });
				} else {
					toastMsg(response.message || 'Delete failed', 'error');
					$btn.prop('disabled', false).html('' + crmIcon('trash-alt') + ' Delete Document');
				}
			},
			error: function(xhr) {
				var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete document';
				toastMsg(msg, 'error');
				$btn.prop('disabled', false).html('' + crmIcon('trash-alt') + ' Delete Document');
			}
		});
		});
	});

	// Delete public doc (after S3 upload): remove local file, clear doc_public_path
	$(document).on('click', '.btn-delete-public-doc', function() {
		var $btn = $(this);
		var documentId = $btn.data('document-id');
		if (!documentId) return;
		crmConfirm('Delete the local (public) copy? The document will remain on S3.').then(function (ok) {
			if (!ok) {
				return;
			}
		$btn.prop('disabled', true).html(crmIconSpinner(''));
		$.ajax({
			url: '{{ route("adminconsole.recentclients.deletepublicdoc") }}',
			type: 'POST',
			headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
			data: { document_id: documentId },
			success: function(response) {
				if (response.success) {
					$btn.remove();
				} else {
					toastMsg(response.message || 'Delete failed', 'error');
					$btn.prop('disabled', false).html('' + crmIcon('trash-alt') + ' Delete public doc');
				}
			},
			error: function(xhr) {
				var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete public doc';
				toastMsg(msg, 'error');
				$btn.prop('disabled', false).html('' + crmIcon('trash-alt') + ' Delete public doc');
			}
		});
		});
	});
	
	// Bulk selection: select all on page
	$(document).on('change', '#selectAllClients', function() {
		var checked = $(this).prop('checked');
		$('.client-checkbox').prop('checked', checked);
		updateBulkArchiveState();
	});
	
	$(document).on('change', '#recent-clients-results .client-checkbox', function() {
		updateBulkArchiveState();
	});
	
	function updateBulkArchiveState() {
		var count = $('.client-checkbox:checked').length;
		$('#selectedCountText').text(count + ' selected');
		$('#bulkArchiveBtn').prop('disabled', count === 0);
		$('#bulkUploadAllDocsToS3Btn').prop('disabled', count === 0);
		$('#selectAllClients').prop('checked', count > 0 && count === $('.client-checkbox').length);
	}
	
	// Bulk archive
	$(document).on('click', '#bulkArchiveBtn', function() {
		var ids = [];
		$('.client-checkbox:checked').each(function() {
			var id = $(this).val();
			if (id) ids.push(id);
		});
		// DEBUG: Log collected IDs
		console.log('[BulkArchive] Checkboxes checked:', $('.client-checkbox:checked').length);
		console.log('[BulkArchive] IDs collected:', ids);
		console.log('[BulkArchive] IDs type:', typeof ids, 'isArray:', Array.isArray(ids));
		
		if (ids.length === 0) {
			toastMsg('Please select at least one client to archive.', 'warning');
			return;
		}
		crmConfirm('Are you sure you want to archive ' + ids.length + ' client(s)? They will be moved to archived clients.').then(function (ok) {
			if (!ok) {
				return;
			}
		var $btn = $('#bulkArchiveBtn');
		$btn.prop('disabled', true);
		var originalHtml = $btn.html();
		$btn.html(crmIconSpinner(' Archiving...'));
		
		var postData = { client_ids: ids };
		console.log('[BulkArchive] Sending payload:', JSON.stringify(postData));
		
		$.ajax({
			url: '{{ route("adminconsole.recentclients.bulkarchive") }}',
			type: 'POST',
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			},
			data: postData,
			success: function(response) {
				console.log('[BulkArchive] Response:', response);
				if (response.debug) {
					console.log('[BulkArchive] Debug info:', response.debug);
				}
				if (response.success) {
					var msg = response.message;
					if (response.debug) {
						msg += '\n\n[DEBUG] ' + JSON.stringify(response.debug, null, 2);
					}
					toastMsg(msg, 'success');
					window.location.reload();
				} else {
					toastMsg(response.message || 'Failed to archive clients', 'error');
					$btn.prop('disabled', false);
					$btn.html(originalHtml);
				}
			},
			error: function(xhr) {
				console.log('[BulkArchive] Error:', xhr.status, xhr.responseText);
				var errorMsg = 'Failed to archive clients';
				if (xhr.responseJSON && xhr.responseJSON.message) {
					errorMsg = xhr.responseJSON.message;
				}
				toastMsg(errorMsg, 'error');
				$btn.prop('disabled', false);
				$btn.html(originalHtml);
			}
		});
		});
	});

	// Bulk upload all docs (Application, Education, Migration) to S3 + remove public paths
	$(document).on('click', '#bulkUploadAllDocsToS3Btn', function() {
		var ids = [];
		$('.client-checkbox:checked').each(function() {
			var id = $(this).val();
			if (id) ids.push(id);
		});

		if (ids.length === 0) {
			toastMsg('First Select Client atlest 1 client.', 'warning');
			return;
		}

		var $btn = $('#bulkUploadAllDocsToS3Btn');
		var $archiveBtn = $('#bulkArchiveBtn');
		var originalHtml = $btn.html();

		$btn.prop('disabled', true).html(crmIconSpinner(' Checking...'));
		$archiveBtn.prop('disabled', true);

		$.ajax({
			url: '{{ route("adminconsole.recentclients.bulkuploadsummary") }}',
			type: 'POST',
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			},
			data: { client_ids: ids },
			success: function(summary) {
				var rows = (summary && summary.rows) ? summary.rows : [];
				var totalClients = (summary && summary.total_clients != null) ? summary.total_clients : rows.length;
				var totalFiles = (summary && summary.total_files != null) ? summary.total_files : 0;
				var lines = [];
				for (var i = 0; i < rows.length; i++) {
					lines.push((rows[i].client_reference_id || ('ID-' + rows[i].client_id)) + ' - ' + (rows[i].total_files || 0) + ' files');
				}

				var confirmText =
					'Selected Clients: ' + totalClients + '\n' +
					'Total Files To Upload: ' + totalFiles + '\n\n' +
					'Client Reference Id - Total Files:\n' +
					(lines.length ? lines.join('\n') : 'No clients found') +
					'\n\nProceed with upload to S3 and remove public path copies?';

				crmConfirm(confirmText).then(function (ok) {
					if (!ok) {
						$btn.prop('disabled', false).html(originalHtml);
						updateBulkArchiveState();
						return;
					}

				$btn.prop('disabled', true).html(crmIconSpinner(' Uploading...'));

				$.ajax({
					url: '{{ route("adminconsole.recentclients.bulkuploadalldocumentstos3") }}',
					type: 'POST',
					headers: {
						'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
					},
					data: { client_ids: ids },
					success: function(response) {
						toastMsg(response.message || 'Bulk upload completed.', response.success ? 'success' : 'warning');
						window.location.reload();
					},
					error: function(xhr) {
						var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Bulk upload failed';
						toastMsg(msg, 'error');
						$btn.prop('disabled', false).html(originalHtml);
						updateBulkArchiveState();
					}
				});
				});
			},
			error: function(xhr) {
				var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to load upload summary';
				toastMsg(msg, 'error');
				$btn.prop('disabled', false).html(originalHtml);
				updateBulkArchiveState();
			}
		});
	});
	
	// Handle archive button click
	$(document).on('click', '.btn-archive-client', function() {
		var $btn = $(this);
		var clientId = $btn.data('client-id');
		var action = $btn.data('action');
		var actionText = action === 'archive' ? 'archive' : 'unarchive';
		var $detailsContent = $('#client-details-content-' + clientId);
		
		crmConfirm('Are you sure you want to ' + actionText + ' this client?').then(function (ok) {
			if (!ok) {
				return;
			}
			// Disable button and show loading
			$btn.prop('disabled', true);
			var originalHtml = $btn.html();
			$btn.html(crmIconSpinner(' Processing...'));
			
			$.ajax({
				url: '{{ route("adminconsole.recentclients.togglearchive") }}',
				type: 'POST',
				headers: {
					'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
				},
				data: {
					client_id: clientId,
					action: action
				},
				success: function(response) {
					if (response.success) {
						// Show success message
						toastMsg(response.message, 'success');
						
						// Reload the client details to reflect the new archive status
						if ($detailsContent.length) {
							loadClientDetails(clientId, $detailsContent);
						}
						
						// Optionally reload the page to refresh the list
						// window.location.reload();
					} else {
						toastMsg(response.message || ('Failed to ' + actionText + ' client'), 'error');
						$btn.prop('disabled', false);
						$btn.html(originalHtml);
					}
				},
				error: function(xhr) {
					var errorMsg = 'Failed to ' + actionText + ' client';
					if (xhr.responseJSON && xhr.responseJSON.message) {
						errorMsg = xhr.responseJSON.message;
					}
					toastMsg(errorMsg, 'error');
					$btn.prop('disabled', false);
					$btn.html(originalHtml);
				}
			});
		});
	});
});
</script>
@endpush
 
@endsection
