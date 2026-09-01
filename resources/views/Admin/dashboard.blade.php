@extends('layouts.admin')
@section('title', 'Admin Dashboard')

@section('content')
<style>
    /* ============================================
       MODERN DASHBOARD DESIGN
       ============================================ */
    
    /* Dashboard Section */
    .main-content .section {
        padding: 0.5rem 0;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: calc(100vh - 100px);
    }
    
    /* Modern Card Design */
    .dash_card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        background: #ffffff;
        position: relative;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    /* Ensure cards fill available space */
    .row > [class*="col-"] {
        display: flex;
        flex-direction: column;
    }
    
    .row > [class*="col-"] > .dash_card {
        flex: 1;
    }
    
    .dash_card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #6777ef 0%, #764ba2 100%);
    }
    
    .dash_card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
    }
    
    /* Card Variants with Gradient Backgrounds */
    .dash_card.stat-card-login::before {
        background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
    }
    
    .dash_card.stat-card-tasks::before {
        background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%);
    }
    
    .dash_card.stat-card-checkin::before {
        background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%);
    }
    
    .card-statistic-4 {
        padding: 1rem;
        position: relative;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    /* Card Content Styling */
    .card-content h5 {
        font-size: 0.875rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .card-content h5 i {
        font-size: 1rem;
        color: #6777ef;
    }
    
    .card-content h2 {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0.5rem 0;
        line-height: 1.2;
    }
    
    .card-content p {
        margin: 0;
        font-size: 0.875rem;
        color: #6b7280;
    }
    
    .card-content a {
        color: #6777ef;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }
    
    .card-content a:hover {
        color: #5568d3;
        text-decoration: underline;
    }
    
    /* Card Header */
    .card_header {
        margin-bottom: 0.75rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f3f4f6;
    }
    
    .card_header h5 {
        margin: 0;
        font-weight: 600;
        color: #1f2937;
        font-size: 0.9375rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .card_header h5 i {
        color: #6777ef;
        margin-right: 0.5rem;
    }
    
    /* Card Body */
    .card_body {
        min-height: 60px;
    }
    
    .card_body .text-center {
        padding: 1rem;
    }
    
    .card_body .table-responsive {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .card_body table tbody tr {
        border-bottom: 1px solid #f3f4f6;
    }
    
    .card_body table tbody tr:last-child {
        border-bottom: none;
    }
    
    .card_body .text-muted {
        color: #9ca3af;
        font-size: 0.875rem;
    }
    
    
    /* Empty State Styling */
    .card_body .text-center p {
        margin-bottom: 0.5rem;
        color: #6b7280;
        font-weight: 500;
    }
    
    .card_body .text-center small {
        font-size: 0.8125rem;
        color: #9ca3af;
    }
    
    /* Table Improvements */
    .card_body table {
        width: 100%;
        margin: 0;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .card_body table tbody tr {
        border-bottom: 1px solid #f3f4f6;
        transition: background-color 0.2s;
    }
    
    .card_body table tbody tr:last-child {
        border-bottom: none;
    }
    
    .card_body table tbody tr:hover {
        background-color: #f9fafb;
    }
    
    .card_body table tbody tr td {
        padding: 0.75rem 0.5rem;
        font-size: 0.8125rem;
    }
    
    /* Login Statistics Styling */
    .login-stats {
        font-size: 0.875rem;
    }
    
    .login-stats .stat-item {
        padding: 0.5rem 0;
        border-bottom: 1px solid #f3f4f6;
        transition: padding-left 0.2s;
    }
    
    .login-stats .stat-item:hover {
        padding-left: 0.5rem;
    }
    
    .login-stats .stat-item:last-child {
        border-bottom: none;
    }
    
    .login-stats .stat-item small {
        color: #6b7280;
        font-weight: 500;
        font-size: 0.75rem;
        display: block;
        margin-bottom: 0.25rem;
    }
    
    .login-stats .stat-item .font-weight-bold {
        color: #1f2937;
        font-size: 0.9375rem;
        font-weight: 600;
    }
    
    .login-stats .badge {
        font-size: 0.75rem;
        padding: 0.375rem 0.75rem;
        border-radius: 6px;
        font-weight: 600;
    }
    
    .login-stats .badge-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #ffffff;
    }
    
    .login-stats .badge-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: #ffffff;
    }
    
    /* Notes Table Card */
    .card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    /* Equal height cards for Clients and Recent Activities sections */
    .row > .col-lg-8,
    .row > .col-lg-4 {
        display: flex;
        flex-direction: column;
    }
    
    .row > .col-lg-8 > .card,
    .row > .col-lg-4 > .card {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .row > .col-lg-8 .card-body,
    .row > .col-lg-4 .card-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }
    
    .row > .col-lg-8 .table-responsive {
        flex: 1;
        overflow-y: auto;
        min-height: 0;
    }
    
    .row > .col-lg-4 .recent-activities-list {
        flex: 1;
        overflow-y: auto;
        min-height: 0;
    }
    
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #ffffff;
        padding: 1.25rem 1.5rem;
        border-bottom: none;
    }
    
    .card-header h4 {
        margin: 0;
        font-weight: 600;
        font-size: 1.125rem;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .card-body table {
        border-radius: 8px;
        overflow: hidden;
    }
    
    .card-body table thead {
        background: #f9fafb;
    }
    
    .card-body table thead th {
        font-weight: 600;
        color: #374151;
        font-size: 0.8125rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 1rem;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .card-body table tbody tr {
        border-bottom: 1px solid #f3f4f6;
    }
    
    .card-body table tbody tr:hover {
        background-color: #f9fafb;
    }
    
    .card-body table tbody td {
        padding: 1rem;
        color: #4b5563;
        font-size: 0.875rem;
    }
    
    .card-body table tbody td a {
        color: #6777ef;
        font-weight: 500;
        text-decoration: none;
    }
    
    .card-body table tbody td a:hover {
        text-decoration: underline;
    }
    
    .card-footer {
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        padding: 1rem 1.5rem;
    }
    
    /* Button Improvements */
    .btn-primary {
        background: linear-gradient(135deg, #6777ef 0%, #5568d3 100%);
        border: none;
        border-radius: 8px;
        padding: 0.5rem 1.25rem;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(103, 119, 239, 0.4);
    }
    
    /* Check-in Queue Styling */
    .card_body table tbody tr td a {
        color: #6777ef;
        font-weight: 500;
        text-decoration: none;
        transition: color 0.2s;
    }
    
    .card_body table tbody tr td a:hover {
        color: #5568d3;
        text-decoration: underline;
    }
    
    /* Task Status Colors */
    .task-status-completed {
        color: #10b981 !important;
        font-weight: 600;
    }
    
    .task-status-progress {
        color: #f59e0b !important;
        font-weight: 600;
    }
    
    .task-status-review {
        color: #6b7280 !important;
        font-weight: 600;
    }
    
    .task-status-todo {
        color: #3b82f6 !important;
        font-weight: 600;
    }
    
    /* Responsive adjustments */
    @media (max-width: 1200px) {
        .row > [class*="col-xl-4"] {
            margin-bottom: 1.5rem;
        }
    }
    
    @media (max-width: 768px) {
        .main-content .section {
            padding: 1rem 0;
        }
        
        .dash_card {
            margin-bottom: 1.5rem;
        }
        
        .card-statistic-4 {
            padding: 1.5rem;
        }
        
        .card-content h2 {
            font-size: 2rem;
        }
        
        .login-stats {
            font-size: 0.8125rem;
        }
        
        .card-header {
            padding: 1rem;
        }
        
        .card-body {
            padding: 1rem;
        }
        
        .row > [class*="col-"] {
            display: block;
        }
    }
    
    /* Animation for numbers */
    @keyframes countUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .card-content h2 {
        animation: countUp 0.6s ease-out;
    }
    
    /* Icon Styling */
    .card-content h5 i,
    .card_header h5 i {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: rgba(103, 119, 239, 0.1);
        color: #6777ef;
    }
    
    /* Recent Activities Styling */
    .recent-activities-list {
        max-height: 600px;
        overflow-y: auto;
    }
    
    .recent-activities-list::-webkit-scrollbar {
        width: 6px;
    }
    
    .recent-activities-list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .recent-activities-list::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 10px;
    }
    
    .recent-activities-list::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    
    .activity-item {
        transition: background-color 0.2s;
    }
    
    .activity-item:hover {
        background-color: #f9fafb;
        margin-left: -0.5rem;
        margin-right: -0.5rem;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
        border-radius: 6px;
    }
    
    .activity-item:last-child {
        border-bottom: none !important;
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }
    
    .activity-icon {
        transition: transform 0.2s;
    }
    
    .activity-item:hover .activity-icon {
        transform: scale(1.1);
    }
    
    /* Action Row Styling */
    .action-row {
        transition: background-color 0.2s;
    }
    
    .action-row:hover {
        background-color: #f9fafb;
    }
    
    .action-row td {
        padding: 0.5rem 0.5rem;
        vertical-align: top;
        font-size: 0.8125rem;
    }
    
    .action-row .round {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        border: 2px solid #6777ef;
        display: inline-block;
        margin-top: 0.15rem;
    }
    
    .action-row small {
        display: block;
        line-height: 1.4;
    }
    
    .action-row small i {
        margin-right: 0.25rem;
        color: #6b7280;
        font-size: 0.7rem;
    }

    /* Access approvals (CRM cross-access queue preview) */
    .access-approvals-panel {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 14px rgba(15, 23, 42, 0.08);
        border: 1px solid #e5e7eb;
        border-left: 4px solid #60a5fa;
        overflow: hidden;
    }
    .access-approvals-panel .access-approvals-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #f3f4f6;
    }
    .access-approvals-panel .access-approvals-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
        color: #1f2937;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .access-approvals-panel .access-approvals-title i {
        color: #3b82f6;
        font-size: 1.1rem;
    }
    .access-approvals-panel .access-approvals-queue-link {
        font-size: 0.875rem;
        font-weight: 500;
        color: #2563eb;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .access-approvals-panel .access-approvals-queue-link:hover {
        color: #1d4ed8;
        text-decoration: underline;
    }
    .access-approvals-panel .access-approvals-body {
        padding: 1rem 1.25rem 1.25rem;
    }
    .access-approvals-panel .access-approvals-empty {
        margin: 0;
        font-size: 0.875rem;
        color: #9ca3af;
    }
    .access-approvals-panel table {
        width: 100%;
        font-size: 0.8125rem;
        margin: 0;
    }
    .access-approvals-panel table th {
        color: #6b7280;
        font-weight: 600;
        padding: 0.35rem 0.5rem 0.35rem 0;
        border: none;
    }
    .access-approvals-panel table td {
        padding: 0.45rem 0.5rem 0.45rem 0;
        color: #374151;
        border-top: 1px solid #f3f4f6;
        vertical-align: top;
    }
</style>

<!-- Main Content -->
<div class="main-content">
	<section class="section">
        @include('../Elements/flash-message')
		<!-- Dashboard Header -->
		<div class="row mb-2">
			<div class="col-12">
				<div class="d-flex justify-content-between align-items-center">
					<div>
						<h2 class="mb-0" style="color: #1f2937; font-weight: 700; font-size: 1.875rem;">
							Welcome back, {{ Auth::user()->first_name ?? 'Staff' }}!
						</h2>
						<p class="text-muted mb-0 mt-1" style="font-size: 0.9375rem;">
							Here's what's happening with your CRM today
						</p>
					</div>
					<div>
						<span class="badge badge-primary" style="background: linear-gradient(135deg, #6777ef 0%, #5568d3 100%); padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.875rem;">
							@icon('calendar') {{ date('l, F j, Y') }}
						</span>
					</div>
				</div>
			</div>
		</div>

        @if(!empty($myDaySummary))
            @include('Admin.partials.my-day-panel', [
                'summary' => $myDaySummary,
                'embeddedOnDashboard' => true,
            ])
        @endif

        <div class="row">

            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-xs-12 mb-4">
				<div class="card dash_card stat-card-login">
					<div class="card-statistic-4">
						<div class="align-items-center justify-content-between">
							<div class="row ">
								<div class="col-lg-12 col-md-12">
									<div class="card-content">
										<h5 class="font-14">
											@icon('user-clock') Login Statistics
										</h5>
										<div class="login-stats mt-2">
											<div class="stat-item mb-2">
												<small class="text-muted d-block">Last Login:</small>
												<span class="font-weight-bold">{{$loginStats['last_login_formatted']}}</span>
												@if($loginStats['time_since_last_login_formatted'] != 'Never')
													<small class="text-muted d-block mt-1">
														({{$loginStats['time_since_last_login_formatted']}})
													</small>
												@endif
											</div>
                                            <div class="stat-item">
                                                <small class="text-muted d-block">Current Session:</small>
                                                <span class="font-weight-bold" id="current_session_duration">{{$loginStats['current_session_duration_formatted']}}</span>
                                                <small class="text-muted d-block mt-1">
                                                    Since: <span id="current_login_time">{{$loginStats['current_login_formatted']}}</span>
                                                </small>
                                            </div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>



            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-xs-12 mb-4">
                <div class="card dash_card stat-card-tasks">
                    <div class="card-statistic-4">
                        <div class="card-content cus_card_content">
                            <div class="card_header">
                                <h5 class="font-14">
                                    @icon('tasks') My Actions
                                </h5>
                            </div>
                            <div class="card_body">
                                @if($todayTasks->count() > 0)
                                <div class="taskdata_list">
                                    <div class="table-responsive">
                                        <table id="my-datatable" class="table-2 table text_wrap">
                                            <tbody class="taskdata">
                                            @foreach($todayTasks as $alist)
                                                @php
                                                    // Get action title or description snippet
                                                    $actionTitle = $alist->title ?? 'Action';
                                                    if(empty($actionTitle) && !empty($alist->description)) {
                                                        $actionTitle = \Illuminate\Support\Str::limit(strip_tags($alist->description), 50);
                                                    }
                                                    // Client/partner name is now available directly from the service
                                                    $clientName = $alist->client_name ?? 'N/A';
                                                    // Get message preview
                                                    $messagePreview = '';
                                                    if(!empty($alist->description)) {
                                                        $messagePreview = \Illuminate\Support\Str::limit(strip_tags($alist->description), 60);
                                                    }
                                                @endphp
                                                <tr style="cursor:pointer;" class="action-row" data-action-id="{{$alist->id}}" 
                                                    data-title="{{$actionTitle}}" 
                                                    data-client="{{$clientName}}"
                                                    data-message="{{htmlspecialchars($alist->description ?? '', ENT_QUOTES)}}"
                                                    data-date="{{$alist->formatted_due_date ?? 'N/A'}}"
                                                    data-type="{{$alist->type ?? 'client'}}">
                                                    <td>
                                                        <span class='round'></span>
                                                    </td>
                                                    <td style="font-size: 0.8125rem;">
                                                        <small class="text-muted" style="font-size: 0.75rem;">
                                                            @icon('user') {{$clientName}}
                                                        </small>
                                                        @if($messagePreview)
                                                        <br>
                                                        <small class="text-muted" style="font-size: 0.75rem; line-height: 1.4;">
                                                            {{$messagePreview}}
                                                        </small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-success complete-action-btn" 
                                                                data-action-id="{{$alist->id}}"
                                                                data-client-id="{{$alist->client_id ?? ''}}"
                                                                data-client-name="{{$clientName}}"
                                                                style="font-size: 0.7rem; padding: 0.25rem 0.5rem;">
                                                            @icon('check') Complete
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @else
                                <div class="text-center py-2">
                                    <p class="text-muted mb-0">No actions at the moment.</p>
                                    <small class="text-muted">All caught up!</small>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-xs-12 mb-4">
                <div class="card dash_card stat-card-checkin">
                    <div class="card-statistic-4">
                        <div class="card-content cus_card_content">
                            <div class="card_header">
                                <h5 class="font-14">
                                    @icon('users') Check-In Queue
                                </h5>
                            </div>
                            <div class="card_body">
                            @if($checkInQueue['total'] > 0)
                                <table>
                                    <tbody>
                                    @foreach($checkInQueue['items'] as $checkinslist)
                                        <tr>
                                            <td>
                                                <a id="{{@$checkinslist->id}}" class="opencheckindetail" href="javascript:;">
                                                    {{@$checkinslist->client->first_name ?? 'N/A'}} {{@$checkinslist->client->last_name ?? ''}}
                                                </a>
                                                <br>
                                                <span>Waiting since {{$checkinslist->formatted_waiting_time ?? 'N/A'}}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="text-center py-2">
                                    <p class="text-muted mb-0">No office check-in at the moment.</p>
                                </div>
                            @endif
                            </div>
                        </div>
                    </div>
                </div>
          </div>
      	</div>

        @include('Admin.partials.dashboard-followup-calendar')

        @if(!empty($accessApprovals))
        <div class="row mb-4">
            <div class="col-12">
                <div class="access-approvals-panel">
                    <div class="access-approvals-header">
                        <h3 class="access-approvals-title">
                            @icon('user-shield')
                            Access approvals
                            @if($accessApprovals['count'] > 0)
                                <span class="badge bg-warning text-dark ms-1">{{ $accessApprovals['count'] }}</span>
                            @endif
                        </h3>
                        <a href="{{ route('crm.access.queue') }}" class="access-approvals-queue-link">
                            @icon('folder-open')
                            Full access queue
                        </a>
                    </div>
                    <div class="access-approvals-body">
                        @if($accessApprovals['preview']->isEmpty())
                            <p class="access-approvals-empty mb-0">No pending supervisor requests.</p>
                        @else
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Requested</th>
                                            <th>Requester</th>
                                            <th>Record</th>
                                            <th>Note</th>
                                            <th class="text-nowrap">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($accessApprovals['preview'] as $g)
                                            <tr>
                                                <td>{{ $g->requested_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                                                <td>{{ $g->staff?->first_name }} {{ $g->staff?->last_name }}</td>
                                                <td>
                                                    @php
                                                        $recordName = trim((string) ($g->admin?->first_name ?? '') . ' ' . (string) ($g->admin?->last_name ?? ''));
                                                    @endphp
                                                    {{ $recordName !== '' ? $recordName : ('#' . $g->admin_id) }}
                                                    <span class="text-muted">({{ $g->record_type }})</span>
                                                </td>
                                                <td class="text-muted">{{ \Illuminate\Support\Str::limit($g->requester_note ?? '', 72) }}</td>
                                                <td class="text-nowrap">
                                                    <form action="{{ route('crm.access.approve', $g->id) }}" method="post" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                                    </form>
                                                    <form action="{{ route('crm.access.reject', $g->id) }}" method="post" class="d-inline ms-1">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">Reject</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($accessApprovals['count'] > $accessApprovals['preview']->count())
                                <p class="text-muted small mb-0 mt-2">
                                    Showing {{ $accessApprovals['preview']->count() }} of {{ $accessApprovals['count'] }}.
                                    <a href="{{ route('crm.access.queue') }}">Open queue to approve or reject</a>
                                </p>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
          
          <div class="row">
            <!-- Left Column: Clients with Recent Activities -->
            <div class="col-lg-8 col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Clients with Recent Activities</h4>
                        <div class="card-header-action">
                            <!-- Additional header actions can be added here -->
                        </div>
                    </div>
                    <div class="card-body">
                        @if($clientsWithRecentActivities->count() > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Client Name</th>
                                            <th>Last Activity</th>
                                            <th>Activity Type</th>
                                            <th>Time</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($clientsWithRecentActivities as $clientActivity)
                                        @php
                                            // Admins table holds clients/leads only - always link to client detail
                                            $detailUrl = URL::to('/clients/detail/'.base64_encode(convert_uuencode($clientActivity->client_id)));
                                        @endphp
                                        <tr>
                                            <td>
                                                <a href="{{ $detailUrl }}" style="color: #6777ef; font-weight: 500; text-decoration: none;">
                                                    {{ $clientActivity->client_name ?: 'N/A' }}
                                                </a>
                                                @if($clientActivity->client_email)
                                                    <br><small class="text-muted">{{ $clientActivity->client_email }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span style="font-size: 0.875rem; color: #4b5563;">
                                                    {{ \Illuminate\Support\Str::limit($clientActivity->last_activity_subject, 50) }}
                                                </span>
                                                @if($clientActivity->activity_count > 1)
                                                    <br><small class="text-muted">{{ $clientActivity->activity_count }} activities</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($clientActivity->activity_type == 'email')
                                                    <span class="badge" style="background: #6777ef; color: #fff; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                                                        @icon('envelope') Email
                                                    </span>
                                                @elseif($clientActivity->activity_type == 'file')
                                                    <span class="badge" style="background: #10b981; color: #fff; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                                                        @icon('file') File
                                                    </span>
                                                @elseif($clientActivity->activity_type == 'note')
                                                    <span class="badge" style="background: #f59e0b; color: #fff; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                                                        @icon('sticky-note') Note
                                                    </span>
                                                @else
                                                    <span class="badge" style="background: #6b7280; color: #fff; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;">
                                                        @icon('circle') Activity
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <span style="font-size: 0.875rem; color: #6b7280;">
                                                    {{ $clientActivity->last_activity_time }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ $detailUrl }}" class="btn btn-sm btn-primary">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <p class="text-muted mb-0" style="font-size: 0.875rem;">No recent client activities</p>
                                <small class="text-muted">Client activities will appear here</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Recent Activities -->
            <div class="col-lg-4 col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Recent Activities</h4>
                    </div>
                    <div class="card-body" style="padding: 1rem;">
                        @if($recentActivities->count() > 0)
                            <div class="recent-activities-list">
                                @foreach($recentActivities as $activity)
                                    <div class="activity-item mb-3 pb-3" style="border-bottom: 1px solid #f3f4f6;">
                                        <div class="d-flex align-items-start">
                                            <div class="activity-icon me-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; background: rgba(103, 119, 239, 0.1); flex-shrink: 0;">
                                                @if($activity->activity_type == 'email')
                                                    {!! \App\Helpers\IconHelper::render('envelope', 'solid', ['attrs' => ['style' => 'color: #6777ef; font-size: 14px;']]) !!}
                                                @elseif($activity->activity_type == 'file')
                                                    {!! \App\Helpers\IconHelper::render('file', 'solid', ['attrs' => ['style' => 'color: #6777ef; font-size: 14px;']]) !!}
                                                @elseif($activity->activity_type == 'note')
                                                    {!! \App\Helpers\IconHelper::render('sticky-note', 'solid', ['attrs' => ['style' => 'color: #6777ef; font-size: 14px;']]) !!}
                                                @else
                                                    {!! \App\Helpers\IconHelper::render('circle', 'solid', ['attrs' => ['style' => 'color: #6777ef; font-size: 8px;']]) !!}
                                                @endif
                                            </div>
                                            <div class="activity-content flex-grow-1" style="min-width: 0;">
                                                <div class="activity-text" style="font-size: 0.875rem; color: #4b5563; line-height: 1.5; margin-bottom: 0.25rem;">
                                                    @if($activity->client)
                                                        {{ $activity->subject ?? 'Activity' }} - {{ $activity->client->first_name ?? '' }} {{ $activity->client->last_name ?? '' }}
                                                    @else
                                                        {{ $activity->subject ?? 'Activity' }}
                                                    @endif
                                                </div>
                                                @if($activity->activity_details && $activity->activity_details != $activity->subject)
                                                    <div class="activity-details" style="font-size: 0.8125rem; color: #6b7280; line-height: 1.4;">
                                                        {{ \Illuminate\Support\Str::limit($activity->activity_details, 60) }}
                                                    </div>
                                                @endif
                                                <div class="activity-time mt-1" style="font-size: 0.75rem; color: #9ca3af;">
                                                    {{ $activity->formatted_time }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <p class="text-muted mb-0" style="font-size: 0.875rem;">No recent activities</p>
                                <small class="text-muted">Activities will appear here</small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

	</section>
</div>

<!-- Complete Action Modal -->
<div class="modal fade" id="completeActionModal" tabindex="-1" role="dialog" aria-labelledby="completeActionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff;">
                <h5 class="modal-title" id="completeActionModalLabel">
                    @icon('check-circle') Complete Action
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
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

<!-- Action Detail Modal -->
<div class="modal fade" id="actionDetailModal" tabindex="-1" role="dialog" aria-labelledby="actionDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff;">
                <h5 class="modal-title" id="actionDetailModalLabel">
                    @icon('tasks') Action Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="text-muted small">Title</label>
                    <h6 id="modal-action-title" style="color: #1f2937; font-weight: 600;"></h6>
                </div>
                <div class="mb-3">
                    <label class="text-muted small">Client/Partner</label>
                    <p id="modal-action-client" style="color: #4b5563; margin: 0;">
                        @icon('user') <span></span>
                    </p>
                </div>
                <div class="mb-3">
                    <label class="text-muted small">Due Date</label>
                    <p id="modal-action-date" style="color: #4b5563; margin: 0;">
                        @icon('clock') <span></span>
                    </p>
                </div>
                <div class="mb-3">
                    <label class="text-muted small">Message</label>
                    <div id="modal-action-message" style="color: #4b5563; padding: 1rem; background: #f9fafb; border-radius: 8px; min-height: 100px; white-space: pre-wrap; word-wrap: break-word;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
$(document).ready(function() {
    // Update current session duration in real-time
    @if(isset($loginStats['current_login_time']) && $loginStats['current_login_time'])
        var loginTimestamp = {{ $loginStats['current_login_time']->timestamp * 1000 }};
    @else
        // Fallback: use current time if no login time available
        var loginTimestamp = new Date().getTime();
    @endif
    
    function updateSessionDuration() {
        var now = new Date().getTime();
        var elapsed = Math.floor((now - loginTimestamp) / 1000);
        
        // Ensure elapsed is never negative
        if (elapsed < 0) {
            elapsed = 0;
        }
        
        var hours = Math.floor(elapsed / 3600);
        var minutes = Math.floor((elapsed % 3600) / 60);
        var seconds = elapsed % 60;
        
        var durationText = '';
        if (hours > 0) {
            durationText = hours + ' hour' + (hours != 1 ? 's' : '');
            if (minutes > 0) {
                durationText += ' ' + minutes + ' minute' + (minutes != 1 ? 's' : '');
            }
        } else if (minutes > 0) {
            durationText = minutes + ' minute' + (minutes != 1 ? 's' : '');
            if (seconds > 0) {
                durationText += ' ' + seconds + ' second' + (seconds != 1 ? 's' : '');
            }
        } else {
            durationText = seconds + ' second' + (seconds != 1 ? 's' : '');
        }
        
        $('#current_session_duration').text(durationText);
    }
    
    // Update session duration once when page loads
    updateSessionDuration();
    
    // Handle complete action button click
    $(document).on('click', '.complete-action-btn', function(e) {
        e.stopPropagation(); // Prevent row click event
        
        var actionId = $(this).data('action-id');
        var clientId = $(this).data('client-id') || '';
        var clientName = $(this).data('client-name') || 'N/A';
        
        // Validate action ID
        if (!actionId) {
            window.toastMsg('Action ID is missing. Please refresh the page and try again.', 'warning');
            return;
        }
        
        // Set form values
        $('#complete_action_id').val(actionId);
        $('#complete_client_id').val(clientId);
        $('#complete-action-client span').text(clientName);
        $('#completion_message').val('');
        
        // Show modal
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modalElement = document.getElementById('completeActionModal');
            var modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else {
            $('#completeActionModal').modal('show');
        }
    });
    
    // Handle complete action form submission
    $('#submitCompleteAction').on('click', function() {
        var actionId = $('#complete_action_id').val();
        var clientId = $('#complete_client_id').val();
        var message = $('#completion_message').val().trim();
        
        if (!message) {
            window.toastMsg('Please enter a completion message.', 'warning');
            return;
        }
        
        // Disable button during submission
        $(this).prop('disabled', true).html(crmIconSpinner(' Completing...'));
        
        $.ajax({
            url: '{{ route("admin.complete-action") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                action_id: actionId,
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
                    
                    // Remove the action row
                    $('tr[data-action-id="' + actionId + '"]').fadeOut(300, function() {
                        $(this).remove();
                        // Check if no more actions
                        if ($('.action-row').length === 0) {
                            $('.taskdata_list').html('<div class="text-center py-2"><p class="text-muted mb-0">No actions at the moment.</p><small class="text-muted">All caught up!</small></div>');
                        }
                    });
                    
                    window.toastMsg(response.message || 'Action completed successfully!', 'success');
                } else {
                    window.toastMsg(response.message || 'Failed to complete action. Please try again.', 'error');
                }
            },
            error: function(xhr) {
                var errorMsg = 'An error occurred. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                window.toastMsg(errorMsg, 'error');
            },
            complete: function() {
                // Re-enable button
                $('#submitCompleteAction').prop('disabled', false).html(crmIcon('check') + ' Complete Action');
            }
        });
    });
    
    // Handle action row clicks to show modal
    $(document).on('click', '.action-row', function(e) {
        // Don't trigger if clicking the complete button
        if ($(e.target).closest('.complete-action-btn').length) {
            return;
        }
        var actionId = $(this).data('action-id');
        var title = $(this).data('title');
        var client = $(this).data('client');
        var message = $(this).data('message');
        var date = $(this).data('date');
        
        // Populate modal
        $('#modal-action-title').text(title || 'Action');
        $('#modal-action-client span').text(client || 'N/A');
        $('#modal-action-date span').text(date || 'N/A');
        
        // Format and display message (handle HTML if present)
        var messageHtml = message || 'No message available';
        // Decode HTML entities and handle HTML content
        var tempDiv = $('<div>').html(messageHtml);
        var decodedMessage = tempDiv.text();
        
        // If original message had HTML tags, display as HTML; otherwise as plain text
        if (messageHtml !== decodedMessage || messageHtml.indexOf('<') !== -1) {
            $('#modal-action-message').html(messageHtml);
        } else {
            $('#modal-action-message').text(messageHtml);
        }
        
        // Show modal - try Bootstrap 5 first, fallback to jQuery
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modalElement = document.getElementById('actionDetailModal');
            var modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else {
            $('#actionDetailModal').modal('show');
        }
    });
});
</script>
<script>
(function() {
    function startDashboardFollowupCalendar() {
    try {
        var panel = document.getElementById('dashboardFollowupCalendarPanel');
        if (!panel) {
            return;
        }

        var calendarEl = document.getElementById('dashboardFollowupCalendar');
        var listEl = document.getElementById('dashboardFollowupAppointments');
        var tabs = panel.querySelectorAll('.dash-followup-cal-tab');
        var cache = {};
        var calendar = null;
        var weekdayFmt = new Intl.DateTimeFormat('en-AU', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });

        function escHtml(text) {
            if (text === undefined || text === null) {
                return '';
            }
            var d = document.createElement('div');
            d.textContent = String(text);
            return d.innerHTML;
        }

        function setMetrics(metrics) {
            var map = metrics || {};
            panel.querySelectorAll('[data-metric]').forEach(function(el) {
                var key = el.getAttribute('data-metric');
                el.textContent = String(map[key] != null ? map[key] : 0);
            });
        }

        function statusLabel(status) {
            if (status === 'completed') return 'Completed';
            if (status === 'cancelled') return 'Cancelled';
            if (status === 'no_show') return 'No show';
            return 'Confirmed';
        }

        function buildEvents(rows) {
            var out = [];
            (rows || []).forEach(function(row) {
                var cs = row.calendar_status || 'confirmed';
                if (cs !== 'confirmed' && cs !== 'completed' && cs !== 'cancelled' && cs !== 'no_show') {
                    cs = 'confirmed';
                }
                out.push({
                    id: String(row.id),
                    title: row.client_display_name || '',
                    start: row.start_iso,
                    end: row.end_iso || row.start_iso,
                    allDay: false,
                    classNames: ['fc-followup-pill-wrap', 'fc-followup-status-' + cs],
                    display: 'block',
                    backgroundColor: 'transparent',
                    borderColor: 'transparent',
                    textColor: '#ffffff',
                    url: row.view_url || row.url || '',
                    extendedProps: {
                        timeLabel: row.time_label || '',
                        clientName: row.client_display_name || '',
                        channelShort: row.channel_short || '',
                        calendarStatus: cs
                    }
                });
            });
            return out;
        }

        function renderList(rows) {
            if (!listEl) {
                return;
            }
            var now = new Date();
            var upcoming = (rows || []).filter(function(row) {
                if (!row.start_iso) {
                    return false;
                }
                var d = new Date(row.start_iso);
                return !isNaN(d.getTime()) && d >= now;
            }).sort(function(a, b) {
                return String(a.start_iso).localeCompare(String(b.start_iso));
            });

            if (upcoming.length === 0) {
                listEl.innerHTML = '<p class="dash-followup-cal-empty mb-0">No upcoming follow-ups.</p>';
                return;
            }

            var html = '';
            var lastDate = '';
            upcoming.forEach(function(row) {
                var d = new Date(row.start_iso);
                var dateKey = row.startdate || '';
                if (dateKey !== lastDate) {
                    lastDate = dateKey;
                    html += '<div class="dash-followup-cal-group-label">' + escHtml(weekdayFmt.format(d)) + '</div>';
                }
                var cs = row.calendar_status || 'confirmed';
                var href = row.view_url || row.url || '#';
                var metaParts = [];
                if (row.channel_short) {
                    metaParts.push(row.channel_short);
                }
                if (row.location_display && row.location_display !== '—') {
                    metaParts.push(row.location_display);
                }
                html += '<a class="dash-followup-cal-item" href="' + escHtml(href) + '">' +
                    '<div class="dash-followup-cal-item-top">' +
                    '<span class="dash-followup-cal-item-time">' + escHtml(row.time_label || '') + '</span>' +
                    '<span class="dash-followup-cal-badge dash-followup-cal-badge-' + escHtml(cs) + '">' + escHtml(statusLabel(cs)) + '</span>' +
                    '</div>' +
                    '<div class="dash-followup-cal-item-name">' + escHtml(row.client_display_name || '') +
                    (row.channel_short ? ' (' + escHtml(row.channel_short) + ')' : '') +
                    '</div>' +
                    (metaParts.length ? '<div class="dash-followup-cal-item-meta">' + escHtml(metaParts.join(' · ')) + '</div>' : '') +
                    '</a>';
            });
            listEl.innerHTML = html;
        }

        function withoutBackdates(rows) {
            var now = new Date();
            var todayStr = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0');
            return (rows || []).filter(function(row) {
                var date = String(row.startdate || '').slice(0, 10);
                if (!date && row.start_iso) {
                    date = String(row.start_iso).slice(0, 10);
                }
                return date >= todayStr;
            });
        }

        function showCurrentMonth() {
            if (!calendar) {
                return;
            }
            calendar.changeView('dayGridMonth');
            calendar.today();
        }

        function applyPayload(payload) {
            var rows = withoutBackdates((payload && payload.events) ? payload.events : []);
            setMetrics(payload && payload.metrics ? payload.metrics : { today: 0, this_week: 0, upcoming: 0 });
            renderList(rows);
            if (!calendar) {
                return;
            }
            calendar.removeAllEvents();
            calendar.addEventSource(buildEvents(rows));
            showCurrentMonth();
        }

        function loadConsultant(btn) {
            var slug = btn.getAttribute('data-slug');
            var eventsUrl = btn.getAttribute('data-events-url');
            if (!slug || !eventsUrl) {
                return;
            }

            tabs.forEach(function(tab) {
                var on = tab === btn;
                tab.classList.toggle('is-active', on);
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
            });

            showCurrentMonth();

            if (cache[slug]) {
                applyPayload(cache[slug]);
                return;
            }

            if (listEl) {
                listEl.innerHTML = '<p class="dash-followup-cal-empty mb-0">Loading appointments…</p>';
            }

            fetch(eventsUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
                .then(function(res) {
                    if (!res.ok) {
                        throw new Error('Calendar request failed');
                    }
                    return res.json();
                })
                .then(function(payload) {
                    cache[slug] = payload;
                    applyPayload(payload);
                })
                .catch(function() {
                    setMetrics({ today: 0, this_week: 0, upcoming: 0 });
                    if (listEl) {
                        listEl.innerHTML = '<p class="dash-followup-cal-empty mb-0">Could not load this calendar.</p>';
                    }
                    if (calendar) {
                        calendar.removeAllEvents();
                    }
                });
        }

        if (calendarEl && typeof window.FullCalendar !== 'undefined' && window.FullCalendar.Calendar) {
            calendar = new window.FullCalendar.Calendar(calendarEl, {
                height: 'auto',
                initialView: 'dayGridMonth',
                editable: false,
                selectable: false,
                dayMaxEvents: true,
                moreLinkText: 'more',
                plugins: [
                    window.FullCalendar.dayGridPlugin,
                    window.FullCalendar.timeGridPlugin,
                    window.FullCalendar.interactionPlugin
                ],
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: 'today',
                    month: 'month',
                    week: 'week',
                    day: 'day'
                },
                events: [],
                eventContent: function(arg) {
                    var props = arg.event.extendedProps || {};
                    var time = props.timeLabel || '';
                    var name = props.clientName || arg.event.title || '';
                    return {
                        html: '<div class="dash-fc-pill">' +
                            '<span>' + escHtml(time) + '</span>' +
                            '<span>' + escHtml(name) + '</span>' +
                            '</div>'
                    };
                },
                eventDidMount: function(info) {
                    var props = info.event.extendedProps || {};
                    var time = props.timeLabel || '';
                    var name = props.clientName || info.event.title || '';
                    var full = [time, name].filter(Boolean).join(' ');
                    var slab = statusLabel(props.calendarStatus || 'confirmed');
                    if (slab) {
                        full += full ? (' — ' + slab) : slab;
                    }
                    if (full) {
                        info.el.setAttribute('title', full);
                    }
                }
            });
            calendar.render();
        } else if (listEl) {
            listEl.innerHTML = '<p class="dash-followup-cal-empty mb-0">Calendar view is unavailable.</p>';
        }

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function() {
                loadConsultant(tab);
            });
        });

        var initial = panel.querySelector('.dash-followup-cal-tab.is-active') || tabs[0];
        if (initial) {
            loadConsultant(initial);
        }
    } catch (e) {
        if (typeof console !== 'undefined' && console.warn) {
            console.warn('Dashboard followup calendar failed to start', e);
        }
    }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startDashboardFollowupCalendar);
    } else {
        startDashboardFollowupCalendar();
    }
})();
</script>
@endsection
