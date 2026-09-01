@php
    $login = $summary['login_stats'] ?? [];
    $caseload = $summary['caseload'] ?? [];
    $contact = $summary['contact'] ?? [];
    $throughput = $summary['throughput'] ?? [];
    $leads = $summary['leads'] ?? [];
    $embeddedOnDashboard = $embeddedOnDashboard ?? false;
    $panelTitle = $panelTitle ?? 'My Day';
    $workloadSectionTitle = $workloadSectionTitle ?? 'My workload';
@endphp

<style>
    .my-day-section { margin-bottom: 1.5rem; }
    .my-day-section h3 {
        font-size: 1rem;
        font-weight: 700;
        color: #374151;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 0.75rem;
    }
    .my-day-tile {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        height: 100%;
    }
    .my-day-tile .card-body { padding: 1rem 1.15rem; }
    .my-day-tile .metric-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .my-day-tile .metric-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #111827;
        line-height: 1.2;
    }
    .my-day-list table { font-size: 0.875rem; }
    .my-day-list th { font-size: 0.75rem; text-transform: uppercase; color: #6b7280; }
    .my-day-hours {
        background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1rem 1.25rem;
    }
    .my-day-note {
        font-size: 0.8125rem;
        color: #6b7280;
    }
    .my-day-panel-wrap {
        margin-bottom: 1.5rem;
        scroll-margin-top: 80px;
    }
    .my-day-list {
        scroll-margin-top: 80px;
    }
    .my-day-list .pagination {
        margin-bottom: 0;
        justify-content: center;
        flex-wrap: wrap;
    }
</style>

<div class="my-day-panel-wrap" id="my-day">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h2 class="mb-0" style="font-weight:700;color:#1f2937;">{{ $panelTitle }}</h2>
                    <p class="text-muted mb-0">Workload and activity for {{ $summary['day_label'] ?? 'today' }}</p>
                </div>
                <span class="badge badge-primary" style="padding:0.5rem 0.85rem;border-radius:8px;">
                    @icon('calendar') Today
                </span>
            </div>
        </div>
    </div>

    @if(! $embeddedOnDashboard)
        <div class="my-day-hours mb-4">
            <div class="row align-items-center">
                <div class="col-md-4 mb-2 mb-md-0">
                    <small class="text-muted d-block">In CRM since</small>
                    <strong>{{ $login['current_login_formatted'] ?? '—' }}</strong>
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                    <small class="text-muted d-block">Session length</small>
                    <strong>{{ $login['current_session_duration_formatted'] ?? '—' }}</strong>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Last seen</small>
                    <strong>{{ $login['current_activity_formatted'] ?? '—' }}</strong>
                </div>
            </div>
            <p class="my-day-note mb-0 mt-2">Hours are context only — contact and throughput below show what you worked on.</p>
        </div>
    @endif

    <div class="my-day-section">
        <h3>Contact today</h3>
        <div class="row">
            @foreach([
                ['Spoke to clients', $contact['spoke_to_students_count'] ?? 0],
                ['Met clients', $contact['met_students_count'] ?? 0],
                ['Contacted live (clients)', $contact['contacted_students_live_count'] ?? 0],
                ['Spoke to colleges', $contact['spoke_to_colleges_count'] ?? 0],
                ['Met colleges', $contact['met_colleges_count'] ?? 0],
            ] as [$label, $value])
                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-3">
                    <div class="card my-day-tile dash_card"><div class="card-body">
                        <div class="metric-label">{{ $label }}</div>
                        <div class="metric-value">{{ $value }}</div>
                    </div></div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="my-day-section">
        <h3>Worked on today</h3>
        <div class="row">
            @foreach([
                ['Clients worked on', $throughput['worked_students_count'] ?? 0],
                ['Applications', $throughput['worked_applications_count'] ?? 0],
                ['Colleges', $throughput['worked_colleges_count'] ?? 0],
                ['Stage moves', $throughput['stage_moves_count'] ?? 0],
                ['Actions completed', $throughput['actions_completed_count'] ?? 0],
                ['Call not picked', $throughput['call_not_picked_count'] ?? 0, true],
            ] as $tile)
                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6 mb-3">
                    <div class="card my-day-tile dash_card"><div class="card-body">
                        <div class="metric-label">{{ $tile[0] }}</div>
                        <div class="metric-value">{{ $tile[1] }}</div>
                        @if(!empty($tile[2]))
                            <small class="text-muted">Chase, not a consult</small>
                        @endif
                    </div></div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="my-day-section">
        <h3>{{ $workloadSectionTitle }}</h3>
        <div class="row">
            @foreach([
                ['Active clients assigned', $caseload['active_clients_count'] ?? 0],
                ['Leads assigned', $caseload['leads_assigned_count'] ?? 0],
                ['Open applications', $caseload['owned_applications_open_count'] ?? 0],
                ['Closed / discontinued apps', $caseload['owned_applications_closed_count'] ?? 0],
                ['No application yet', $caseload['no_application_students_count'] ?? 0],
                ['Converted today (assigned)', $leads['converted_today'] ?? 0],
                ['Quiet clients (7–13d)', $caseload['quiet_students_count'] ?? 0],
                ['Inactive clients (14+d)', $caseload['inactive_students_count'] ?? 0],
            ] as [$label, $value])
                <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                    <div class="card my-day-tile dash_card"><div class="card-body">
                        <div class="metric-label">{{ $label }}</div>
                        <div class="metric-value">{{ $value }}</div>
                    </div></div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card my-day-tile my-day-list dash_card">
                <div class="card-header"><h4 class="mb-0">Clients contacted today</h4></div>
                <div class="card-body p-0">
                    @if(empty($contact['students']))
                        <p class="text-muted p-3 mb-0">No Call or In-Person notes logged today.</p>
                    @else
                        <table class="table table-striped mb-0">
                            <thead><tr><th>Client</th><th>Ref</th><th>Type</th><th>When</th></tr></thead>
                            <tbody>
                            @foreach($contact['students'] as $row)
                                <tr>
                                    <td>@if(!empty($row['url']))<a href="{{ $row['url'] }}">{{ $row['name'] }}</a>@else{{ $row['name'] }}@endif</td>
                                    <td>{{ $row['reference'] ?? '—' }}</td>
                                    <td>{{ $row['note_type'] }}</td>
                                    <td>{{ $row['created_at'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card my-day-tile my-day-list dash_card">
                <div class="card-header"><h4 class="mb-0">Clients worked on today</h4></div>
                <div class="card-body p-0">
                    @if(empty($throughput['students']))
                        <p class="text-muted p-3 mb-0">No client file activity logged today.</p>
                    @else
                        <table class="table table-striped mb-0">
                            <thead><tr><th>Client</th><th>Ref</th></tr></thead>
                            <tbody>
                            @foreach($throughput['students'] as $row)
                                <tr>
                                    <td>@if(!empty($row['url']))<a href="{{ $row['url'] }}">{{ $row['name'] }}</a>@else{{ $row['name'] }}@endif</td>
                                    <td>{{ $row['reference'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card my-day-tile my-day-list dash_card" id="quiet-inactive-clients">
                <div class="card-header"><h4 class="mb-0">Quiet / inactive clients</h4></div>
                <div class="card-body p-0">
                    @php
                        $quietPaginator = \App\Support\ArrayPaginator::make(
                            array_merge($caseload['quiet_students'] ?? [], $caseload['inactive_students'] ?? []),
                            'quiet_page',
                            \App\Support\ArrayPaginator::DEFAULT_PER_PAGE,
                            'quiet-inactive-clients',
                        );
                    @endphp
                    @if($quietPaginator->isEmpty())
                        <p class="text-muted p-3 mb-0">No quiet or inactive allocated clients.</p>
                    @else
                        <table class="table table-striped mb-0">
                            <thead><tr><th>Client</th><th>Ref</th><th>Last work</th></tr></thead>
                            <tbody>
                            @foreach($quietPaginator as $row)
                                <tr>
                                    <td>@if(!empty($row['url']))<a href="{{ $row['url'] }}">{{ $row['name'] }}</a>@else{{ $row['name'] }}@endif</td>
                                    <td>{{ $row['reference'] ?? '—' }}</td>
                                    <td>{{ $row['last_work_at'] ?? 'Never' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
                @if($quietPaginator->hasPages())
                    <div class="card-footer bg-white">{{ $quietPaginator->links() }}</div>
                @endif
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card my-day-tile my-day-list dash_card" id="applications-worked-today">
                <div class="card-header"><h4 class="mb-0">Applications worked on today</h4></div>
                <div class="card-body p-0">
                    @php
                        $appsPaginator = \App\Support\ArrayPaginator::make(
                            $throughput['applications'] ?? [],
                            'apps_page',
                            \App\Support\ArrayPaginator::DEFAULT_PER_PAGE,
                            'applications-worked-today',
                        );
                    @endphp
                    @if($appsPaginator->isEmpty())
                        <p class="text-muted p-3 mb-0">No application activity logged today.</p>
                    @else
                        <table class="table table-striped mb-0">
                            <thead><tr><th>Client</th><th>Ref</th><th>Stage</th></tr></thead>
                            <tbody>
                            @foreach($appsPaginator as $row)
                                <tr>
                                    <td>@if(!empty($row['url']))<a href="{{ $row['url'] }}">{{ $row['client_name'] }}</a>@else{{ $row['client_name'] }}@endif</td>
                                    <td>{{ $row['client_reference'] ?? '—' }}</td>
                                    <td>{{ $row['stage'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
                @if($appsPaginator->hasPages())
                    <div class="card-footer bg-white">{{ $appsPaginator->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
