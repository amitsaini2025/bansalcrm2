@extends('layouts.adminconsole')
@section('title', 'Staff Workload — '.$staff->full_name)

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-body">
            <div class="server-error">@include('../Elements/flash-message')</div>
            <div class="mb-3">
                <a href="{{ route('adminconsole.staff-workload.index') }}" class="btn btn-sm btn-outline-secondary">@icon('arrow-left') Back to all staff</a>
            </div>
            @include('Admin.partials.my-day-panel', [
                'summary' => $summary,
                'panelTitle' => $staff->full_name.' — My Day',
                'embeddedOnDashboard' => true,
            ])
        </div>
    </section>
</div>
@endsection
