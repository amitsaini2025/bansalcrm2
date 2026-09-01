@extends('layouts.adminconsole')
@section('title', 'Staff Workload Today')

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-body">
            <div class="server-error">@include('../Elements/flash-message')</div>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Staff workload — {{ $teamOverview['day_label'] ?? 'Today' }}</h4>
                            <div class="card-header-action">
                                <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-primary">Main dashboard</a>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Staff</th>
                                            <th>Active clients</th>
                                            <th>Leads</th>
                                            <th>Contacted today</th>
                                            <th>Worked on</th>
                                            <th>Apps today</th>
                                            <th>Stage moves</th>
                                            <th>Open apps</th>
                                            <th>Converted</th>
                                            <th>Quiet</th>
                                            <th>Inactive</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($teamOverview['rows'] ?? [] as $row)
                                            <tr>
                                                <td>
                                                    <strong>{{ $row['staff_name'] }}</strong>
                                                    @if(!empty($row['staff_email']))
                                                        <br><small class="text-muted">{{ $row['staff_email'] }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ $row['active_clients_count'] ?? 0 }}</td>
                                                <td>{{ $row['leads_assigned_count'] ?? 0 }}</td>
                                                <td>{{ $row['contacted_students_live_count'] ?? 0 }}</td>
                                                <td>{{ $row['worked_students_count'] ?? 0 }}</td>
                                                <td>{{ $row['worked_applications_count'] ?? 0 }}</td>
                                                <td>{{ $row['stage_moves_count'] ?? 0 }}</td>
                                                <td>{{ $row['owned_applications_open_count'] ?? 0 }}</td>
                                                <td>{{ $row['converted_today'] ?? 0 }}</td>
                                                <td>{{ $row['quiet_students_count'] ?? 0 }}</td>
                                                <td>{{ $row['inactive_students_count'] ?? 0 }}</td>
                                                <td>
                                                    <a href="{{ route('adminconsole.staff-workload.show', $row['staff_id']) }}" class="btn btn-sm btn-primary">View</a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="12" class="text-center text-muted p-4">No active staff found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
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
