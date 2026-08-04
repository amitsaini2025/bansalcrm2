@extends('layouts.admin')
@section('title', $sheetTitle ?? 'Ongoing Sheet')

@push('styles')
<style>
    /* Page title header - distinct background and colour */
    .ongoing-sheet-page-header {
        background: linear-gradient(135deg, #5b4d96 0%, #6f5fb8 50%, #5b4d96 100%);
        color: #fff;
        padding: 1rem 1.25rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(91, 77, 150, 0.25);
    }
    .ongoing-sheet-page-header h4 {
        margin: 0;
        font-weight: 600;
        color: #fff;
        font-size: 1.35rem;
    }
    .ongoing-sheet-page-header h4 i {
        margin-right: 0.5rem;
        opacity: 0.95;
    }
    /* Header row - light blue */
    .ongoing-sheet-header {
        background: linear-gradient(135deg, #cfe2ff 0%, #b8daff 100%);
        font-weight: 600;
        text-align: center;
        border: 1px solid #9ec5fe;
    }
    
    tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    /* Current status column - allow text wrapping */
    .status-cell {
        max-width: 300px;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    
    /* Filter section design */
    .ongoing-filter-card {
        border: 1px solid #e3e6f0;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        overflow: visible;
    }
    /* Escape .main-content { overflow: hidden } so Tom Select menus are not clipped */
    .ongoing-sheet-main {
        overflow: visible !important;
    }
    #filterPanel.collapse,
    .ongoing-filter-panel {
        overflow: visible !important;
    }
    .ongoing-filter-card .card-body {
        padding: 0.5rem 0.75rem 0.75rem;
    }
    .ongoing-filter-toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.75rem;
        font-weight: 600;
        font-size: 0.875rem;
        border-radius: 6px;
        background: linear-gradient(135deg, #5b4d96 0%, #6f5fb8 100%);
        color: #fff;
        border: none;
        transition: box-shadow 0.2s, transform 0.1s;
    }
    .ongoing-filter-toggle:hover {
        color: #fff;
        box-shadow: 0 4px 12px rgba(91, 77, 150, 0.35);
    }
    .ongoing-filter-toggle .badge {
        background: rgba(255,255,255,0.25);
        color: #fff;
        font-size: 0.7rem;
    }
    .ongoing-filter-panel {
        background: linear-gradient(180deg, #fafbfc 0%, #f4f6f9 100%);
        border-top: 1px solid #e3e6f0;
        padding: 0.5rem 0.75rem 0.75rem;
        border-radius: 0 0 10px 10px;
    }
    .ongoing-filter-panel .form-label {
        font-weight: 600;
        color: #374151;
        font-size: 0.75rem;
        margin-bottom: 0.2rem;
    }
    .ongoing-filter-panel .form-control {
        border-radius: 5px;
        border: 1px solid #d1d5db;
        padding: 0.3rem 0.5rem;
        font-size: 0.8125rem;
        min-height: 34px;
        height: 34px;
        width: 100%;
    }
    /* First row: Branch, Visa From, Visa To, Current Stage – same size */
    .ongoing-filter-panel .ongoing-filter-field {
        display: flex;
        flex-direction: column;
    }
    .ongoing-filter-panel .ongoing-filter-field .form-control,
    .ongoing-filter-panel .ongoing-filter-field .ts-wrapper {
        width: 100% !important;
        min-width: 0;
        flex: 1 1 auto;
    }
    .ongoing-filter-panel .ongoing-filter-field .form-control {
        min-height: 34px;
        height: 34px;
    }
    .ongoing-filter-panel .ongoing-filter-field .ts-wrapper .ts-control {
        border-radius: 5px;
        border: 1px solid #d1d5db;
        min-height: 34px;
        font-size: 0.8125rem;
        color: #374151;
    }
    .ongoing-filter-panel .form-control:focus {
        border-color: #6f5fb8;
        box-shadow: 0 0 0 2px rgba(111, 95, 184, 0.15);
    }
    .ongoing-filter-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        align-items: center;
        padding-top: 0;
    }
    .btn-ongoing-apply {
        background: linear-gradient(135deg, #5b4d96 0%, #6f5fb8 100%);
        color: #fff;
        border: none;
        padding: 0.35rem 0.75rem;
        font-size: 0.8125rem;
        border-radius: 5px;
        font-weight: 600;
        transition: box-shadow 0.2s;
    }
    .btn-ongoing-apply:hover {
        color: #fff;
        box-shadow: 0 4px 12px rgba(91, 77, 150, 0.35);
    }
    .btn-ongoing-reset {
        background: #fff;
        color: #4b5563;
        border: 1px solid #d1d5db;
        padding: 0.35rem 0.75rem;
        font-size: 0.8125rem;
        border-radius: 5px;
        font-weight: 500;
        transition: background 0.2s, border-color 0.2s;
    }
    .btn-ongoing-reset:hover {
        background: #f9fafb;
        border-color: #9ca3af;
        color: #374151;
    }
    .ongoing-per-page-select {
        min-width: 120px;
        border-radius: 6px;
        border: 1px solid #d1d5db;
        padding: 0.35rem 0.75rem;
        font-size: 0.875rem;
    }
    .ongoing-assignee-select {
        min-width: 160px;
        border-radius: 6px;
        border: 1px solid #d1d5db;
        padding: 0.35rem 0.75rem;
        font-size: 0.875rem;
        height: auto;
    }
    
    /* Ongoing sheet table: wider, horizontal scroll (matches MigrationManager2 pattern) */
    .ongoing-sheet-table-container {
        position: relative;
    }
    .ongoing-sheet-table-wrap {
        position: relative;
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
    }
    .ongoing-sheet-table-wrap .table {
        min-width: 1400px;
        table-layout: auto;
    }
    /* Scroll indicators (left/right gradient overlays when content is scrollable) */
    .ongoing-sheet-scroll-indicator {
        position: absolute;
        top: 0;
        bottom: 20px;
        width: 40px;
        pointer-events: none;
        z-index: 10;
        transition: opacity 0.3s;
    }
    .ongoing-sheet-scroll-indicator-left {
        left: 0;
        background: linear-gradient(to right, rgba(255,255,255,0.95), transparent);
        opacity: 0;
    }
    .ongoing-sheet-scroll-indicator-right {
        right: 0;
        background: linear-gradient(to left, rgba(255,255,255,0.95), transparent);
    }
    .ongoing-sheet-scroll-indicator-left.visible,
    .ongoing-sheet-scroll-indicator-right.visible {
        opacity: 1;
    }
    /* Custom scrollbar styling */
    .ongoing-sheet-table-wrap::-webkit-scrollbar {
        height: 12px;
    }
    .ongoing-sheet-table-wrap::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    .ongoing-sheet-table-wrap::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #5b4d96 0%, #6f5fb8 100%);
        border-radius: 10px;
    }
    .ongoing-sheet-table-wrap::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #6f5fb8 0%, #5b4d96 100%);
    }
    /* Scroll hint */
    .ongoing-sheet-scroll-hint {
        text-align: center;
        padding: 10px;
        background: #f4f1fa;
        border-radius: 5px;
        margin-bottom: 10px;
        font-size: 13px;
        color: #4a4063;
    }
    .ongoing-sheet-scroll-hint i {
        margin-right: 6px;
    }
    .ongoing-sheet-table-wrap .table th.branch-cell,
    .ongoing-sheet-table-wrap .table td.branch-cell {
        min-width: 180px;
        max-width: 280px;
    }
    
    /* Course name column: dark blue / black text */
    .ongoing-course-cell {
        font-weight: 500;
    }
    .ongoing-course-link {
        color: #1e3a5f !important;
        text-decoration: none;
    }
    .ongoing-course-link:hover {
        color: #0f172a !important;
        text-decoration: underline;
    }
    
    .comment-cell {
        max-width: 220px;
        min-width: 120px;
    }
    .checklist-status-cell .checklist-status-select {
        min-width: 140px;
        max-width: 180px;
    }
    .comment-cell .sheet-comment-text {
        display: inline-block;
        max-height: 3.6em;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: pre-wrap;
        word-break: break-word;
    }
</style>
@endpush

@section('content')
<div class="main-content ongoing-sheet-main">
    <section class="section">
        <div class="section-body">
            
            {{-- Page title header --}}
            <div class="ongoing-sheet-page-header">
                <h4>@icon('clipboard-list') {{ $sheetTitle ?? 'Ongoing Sheet' }}</h4>
            </div>
            
            {{-- Filter Bar --}}
            <div class="card ongoing-filter-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <button class="ongoing-filter-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#filterPanel" aria-expanded="{{ $activeFilterCount > 0 ? 'true' : 'false' }}" aria-controls="filterPanel">
                                @icon('filter')
                                <span>Filters</span>
                                @if($activeFilterCount > 0)
                                    <span class="badge">{{ $activeFilterCount }}</span>
                                @endif
                            </button>
                            <label class="mb-0 d-flex align-items-center gap-1">
                                <span class="text-nowrap text-muted small">Assignee</span>
                                <select id="ongoing-assignee-bar" class="form-control ongoing-assignee-select" aria-label="Assignee">
                                    <option value="all" {{ request('assignee') === 'all' || request('assignee') === '' ? 'selected' : '' }}>All</option>
                                    @foreach($assignees as $a)
                                        @php $displayName = trim(($a->first_name ?? '') . ' ' . ($a->last_name ?? '')) ?: ($a->email ?? '—'); @endphp
                                        <option value="{{ $a->id }}" {{ request('assignee') == $a->id ? 'selected' : '' }}>{{ $displayName }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                        
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            @if($activeFilterCount > 0)
                                <a href="{{ route($sheetRoute ?? 'clients.sheets.ongoing', ['clear_filters' => 1]) }}" class="btn-ongoing-reset text-decoration-none">
                                    @icon('times', 'solid', ['class' => 'me-1']) Clear Filters
                                </a>
                            @endif
                            
                            <select name="per_page" class="form-control ongoing-per-page-select" 
                                    onchange="window.location.href='{{ route($sheetRoute ?? 'clients.sheets.ongoing') }}?per_page=' + this.value + '{{ request()->except('page', 'per_page') ? '&' . http_build_query(request()->except('page', 'per_page')) : '' }}';">
                                @foreach([10, 25, 50, 100, 200] as $option)
                                    <option value="{{ $option }}" {{ $perPage == $option ? 'selected' : '' }}>
                                        {{ $option }} per page
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    {{-- Filter Panel (Collapsible) --}}
                    <div class="collapse {{ $activeFilterCount > 0 ? 'show' : '' }}" id="filterPanel">
                        <div class="ongoing-filter-panel">
                            <form method="get" action="{{ route($sheetRoute ?? 'clients.sheets.ongoing') }}">
                                <input type="hidden" name="per_page" value="{{ $perPage }}">
                                <input type="hidden" name="assignee" value="{{ request('assignee') }}">
                                @if(request()->filled('sort'))
                                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                                @endif
                                @if(request()->filled('direction'))
                                    <input type="hidden" name="direction" value="{{ request('direction') }}">
                                @endif
                                <div class="row g-2 align-items-end">
                                    <div class="col-6 col-md-3 ongoing-filter-field">
                                        <label class="form-label">Branch</label>
                                        <select name="branch[]" class="form-control ongoing-filter-branch-select" multiple>
                                            @foreach($branches as $b)
                                                <option value="{{ $b->id }}" 
                                                    {{ in_array($b->id, (array)request('branch', [])) ? 'selected' : '' }}>
                                                    {{ $b->office_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-3 ongoing-filter-field">
                                        <label class="form-label">Visa From</label>
                                        <input type="text" name="visa_expiry_from" class="form-control dobdatepicker" 
                                               placeholder="DD/MM/YYYY" value="{{ request('visa_expiry_from') }}" autocomplete="off">
                                    </div>
                                    <div class="col-6 col-md-3 ongoing-filter-field">
                                        <label class="form-label">Visa To</label>
                                        <input type="text" name="visa_expiry_to" class="form-control dobdatepicker" 
                                               placeholder="DD/MM/YYYY" value="{{ request('visa_expiry_to') }}" autocomplete="off">
                                    </div>
                                    <div class="col-6 col-md-3 ongoing-filter-field">
                                        <label class="form-label">Current Stage</label>
                                        @php
                                            $selectedCurrentStage = request()->filled('current_stage') ? trim((string) request('current_stage')) : '';
                                            $selectedCurrentStageValue = $selectedCurrentStage;
                                            if ($selectedCurrentStage !== '') {
                                                foreach ($currentStages as $value => $label) {
                                                    if (strtolower(trim((string) $value)) === strtolower($selectedCurrentStage)) {
                                                        $selectedCurrentStageValue = trim((string) $value);
                                                        break;
                                                    }
                                                }
                                            }
                                            $selectedStageInOptions = $selectedCurrentStage !== '' && $currentStages->contains(function ($label, $value) use ($selectedCurrentStage) {
                                                return strtolower(trim((string) $value)) === strtolower($selectedCurrentStage);
                                            });
                                        @endphp
                                        <select name="current_stage" id="ongoing-current-stage-filter" class="form-control ongoing-filter-stage-select" data-selected-stage="{{ e($selectedCurrentStageValue) }}">
                                            {{-- Empty option is required: without it the browser/Tom Select submit the first stage (e.g. Offer letter processing) and hide matching search results. --}}
                                            <option value="" {{ $selectedCurrentStage === '' ? 'selected' : '' }}>All stages</option>
                                            @foreach($currentStages as $value => $label)
                                                <option value="{{ $value }}" {{ $selectedCurrentStage !== '' && strtolower(trim((string) $value)) === strtolower($selectedCurrentStage) ? 'selected' : '' }}>
                                                    {{ $label ?: '—' }}
                                                </option>
                                            @endforeach
                                            @if($selectedCurrentStage !== '' && !$selectedStageInOptions)
                                                <option value="{{ e($selectedCurrentStageValue) }}" selected>{{ $selectedCurrentStageValue }}</option>
                                            @endif
                                        </select>
                                    </div>
                                    @if($showStageEntryDateFilters ?? false)
                                    <div class="col-6 col-md-3 ongoing-filter-field">
                                        <label class="form-label">From Date</label>
                                        <input type="text" name="stage_entry_from" class="form-control dobdatepicker" 
                                               placeholder="DD/MM/YYYY" value="{{ request('stage_entry_from') }}" autocomplete="off">
                                    </div>
                                    <div class="col-6 col-md-3 ongoing-filter-field">
                                        <label class="form-label">To Date</label>
                                        <input type="text" name="stage_entry_to" class="form-control dobdatepicker" 
                                               placeholder="DD/MM/YYYY" value="{{ request('stage_entry_to') }}" autocomplete="off">
                                    </div>
                                    @endif
                                    <div class="col-12 col-md-9">
                                        <label class="form-label">Search (Name, CRM Ref, Stage)</label>
                                        <input type="text" name="search" class="form-control" 
                                               placeholder="Search by name, CRM ref or stage..." value="{{ request('search') }}">
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <label class="form-label d-none d-md-block">&nbsp;</label>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <button type="submit" class="btn-ongoing-apply">
                                                @icon('search', 'solid', ['class' => 'me-1']) Apply
                                            </button>
                                            <a href="{{ route($sheetRoute ?? 'clients.sheets.ongoing', ['clear_filters' => 1]) }}" class="btn-ongoing-reset text-decoration-none">
                                                @icon('redo', 'solid', ['class' => 'me-1']) Reset
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Table --}}
            <div class="card">
                <div class="card-body p-0">
                    {{-- Scroll hint --}}
                    <div class="ongoing-sheet-scroll-hint px-3 pt-2">
                        @icon('arrows-alt-h') Scroll horizontally to see all columns.
                    </div>
                    <div class="ongoing-sheet-table-container px-0">
                        <div class="ongoing-sheet-scroll-indicator ongoing-sheet-scroll-indicator-left"></div>
                        <div class="ongoing-sheet-scroll-indicator ongoing-sheet-scroll-indicator-right visible"></div>
                        <div class="table-responsive ongoing-sheet-table-wrap" id="ongoing-sheet-scroll-container">
                            @php
                                $_ongoingSheetRoute = $sheetRoute ?? 'clients.sheets.ongoing';
                                $ongoingSheetSortUrl = function (string $field, string $defaultDirection = 'asc') use ($_ongoingSheetRoute) {
                                    $params = request()->except('page');
                                    $params['sort'] = $field;
                                    $active = (string) request('sort') === $field;
                                    $curr = strtolower((string) request('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
                                    $params['direction'] = $active ? ($curr === 'asc' ? 'desc' : 'asc') : $defaultDirection;
                                    return route($_ongoingSheetRoute, $params);
                                };
                                $ongoingSheetSortTh = function (string $field, string $label, string $defaultDirection = 'asc') use ($ongoingSheetSortUrl) {
                                    $active = (string) request('sort') === $field;
                                    $dir = strtolower((string) request('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
                                    $icon = $dir === 'desc' ? '▼' : '▲';
                                    $ariaSort = $active ? ($dir === 'asc' ? 'ascending' : 'descending') : null;
                                    return [
                                        'url' => $ongoingSheetSortUrl($field, $defaultDirection),
                                        'active' => $active,
                                        'icon' => $icon,
                                        'ariaSort' => $ariaSort,
                                        'label' => $label,
                                    ];
                                };
                            @endphp
                            <table class="table table-striped table-bordered mb-0">
                            <thead>
                                <tr class="ongoing-sheet-header">
                                    @php $th = $ongoingSheetSortTh('course_name', 'Course Name', 'asc'); @endphp
                                    <th @if($th['ariaSort']) aria-sort="{{ $th['ariaSort'] }}" @endif><a href="{{ $th['url'] }}" class="text-dark text-decoration-none">{{ $th['label'] }}@if($th['active'])<span class="text-muted small ms-1" aria-hidden="true">{{ $th['icon'] }}</span>@endif</a></th>
                                    @if(isset($sheetType) && $sheetType === 'checklist')
                                    @php $th = $ongoingSheetSortTh('created_at', 'Application created', 'desc'); @endphp
                                    <th class="text-nowrap" @if($th['ariaSort']) aria-sort="{{ $th['ariaSort'] }}" @endif><a href="{{ $th['url'] }}" class="text-dark text-decoration-none">{{ $th['label'] }}@if($th['active'])<span class="text-muted small ms-1" aria-hidden="true">{{ $th['icon'] }}</span>@endif</a></th>
                                    @endif
                                    @if(!isset($sheetType) || $sheetType !== 'checklist')
                                    @php $th = $ongoingSheetSortTh('crm_ref', 'CRM Reference', 'asc'); @endphp
                                    <th class="text-nowrap" @if($th['ariaSort']) aria-sort="{{ $th['ariaSort'] }}" @endif><a href="{{ $th['url'] }}" class="text-dark text-decoration-none">{{ $th['label'] }}@if($th['active'])<span class="text-muted small ms-1" aria-hidden="true">{{ $th['icon'] }}</span>@endif</a></th>
                                    @php $th = $ongoingSheetSortTh('created_at', 'Application created', 'desc'); @endphp
                                    <th class="text-nowrap" @if($th['ariaSort']) aria-sort="{{ $th['ariaSort'] }}" @endif><a href="{{ $th['url'] }}" class="text-dark text-decoration-none">{{ $th['label'] }}@if($th['active'])<span class="text-muted small ms-1" aria-hidden="true">{{ $th['icon'] }}</span>@endif</a></th>
                                    @endif
                                    @php $th = $ongoingSheetSortTh('name', 'Client Name', 'asc'); @endphp
                                    <th @if($th['ariaSort']) aria-sort="{{ $th['ariaSort'] }}" @endif><a href="{{ $th['url'] }}" class="text-dark text-decoration-none">{{ $th['label'] }}@if($th['active'])<span class="text-muted small ms-1" aria-hidden="true">{{ $th['icon'] }}</span>@endif</a></th>
                                    @php $th = $ongoingSheetSortTh('dob', 'Date of Birth', 'asc'); @endphp
                                    <th class="text-nowrap" @if($th['ariaSort']) aria-sort="{{ $th['ariaSort'] }}" @endif><a href="{{ $th['url'] }}" class="text-dark text-decoration-none">{{ $th['label'] }}@if($th['active'])<span class="text-muted small ms-1" aria-hidden="true">{{ $th['icon'] }}</span>@endif</a></th>
                                    @php $th = $ongoingSheetSortTh('total_payment', 'Payment Received', 'desc'); @endphp
                                    <th class="text-nowrap" @if($th['ariaSort']) aria-sort="{{ $th['ariaSort'] }}" @endif><a href="{{ $th['url'] }}" class="text-dark text-decoration-none">{{ $th['label'] }}@if($th['active'])<span class="text-muted small ms-1" aria-hidden="true">{{ $th['icon'] }}</span>@endif</a></th>
                                    @php $th = $ongoingSheetSortTh('institute', 'Institute', 'asc'); @endphp
                                    <th @if($th['ariaSort']) aria-sort="{{ $th['ariaSort'] }}" @endif><a href="{{ $th['url'] }}" class="text-dark text-decoration-none">{{ $th['label'] }}@if($th['active'])<span class="text-muted small ms-1" aria-hidden="true">{{ $th['icon'] }}</span>@endif</a></th>
                                    @if(!isset($sheetType) || $sheetType !== 'checklist')
                                    @php $th = $ongoingSheetSortTh('branch', 'Branch', 'asc'); @endphp
                                    <th class="branch-cell" @if($th['ariaSort']) aria-sort="{{ $th['ariaSort'] }}" @endif><a href="{{ $th['url'] }}" class="text-dark text-decoration-none">{{ $th['label'] }}@if($th['active'])<span class="text-muted small ms-1" aria-hidden="true">{{ $th['icon'] }}</span>@endif</a></th>
                                    @endif
                                    @php $th = $ongoingSheetSortTh('assignee', 'Assignee', 'asc'); @endphp
                                    <th @if($th['ariaSort']) aria-sort="{{ $th['ariaSort'] }}" @endif><a href="{{ $th['url'] }}" class="text-dark text-decoration-none">{{ $th['label'] }}@if($th['active'])<span class="text-muted small ms-1" aria-hidden="true">{{ $th['icon'] }}</span>@endif</a></th>
                                    @php $th = $ongoingSheetSortTh('visa_expiry', 'Visa Expiry Date', 'asc'); @endphp
                                    <th class="text-nowrap" @if($th['ariaSort']) aria-sort="{{ $th['ariaSort'] }}" @endif><a href="{{ $th['url'] }}" class="text-dark text-decoration-none">{{ $th['label'] }}@if($th['active'])<span class="text-muted small ms-1" aria-hidden="true">{{ $th['icon'] }}</span>@endif</a></th>
                                    @php $th = $ongoingSheetSortTh('visa_category', 'Visa Category', 'asc'); @endphp
                                    <th @if($th['ariaSort']) aria-sort="{{ $th['ariaSort'] }}" @endif><a href="{{ $th['url'] }}" class="text-dark text-decoration-none">{{ $th['label'] }}@if($th['active'])<span class="text-muted small ms-1" aria-hidden="true">{{ $th['icon'] }}</span>@endif</a></th>
                                    @if(!isset($sheetType) || $sheetType !== 'checklist')
                                    @php $th = $ongoingSheetSortTh('stage', 'Current Stage', 'asc'); @endphp
                                    <th class="status-cell" @if($th['ariaSort']) aria-sort="{{ $th['ariaSort'] }}" @endif><a href="{{ $th['url'] }}" class="text-dark text-decoration-none">{{ $th['label'] }}@if($th['active'])<span class="text-muted small ms-1" aria-hidden="true">{{ $th['icon'] }}</span>@endif</a></th>
                                    @endif
                                    <th>Comment</th>
                                    @if(isset($sheetType) && $sheetType === 'checklist')
                                    @php $th = $ongoingSheetSortTh('checklist_status', 'Status', 'asc'); @endphp
                                    <th @if($th['ariaSort']) aria-sort="{{ $th['ariaSort'] }}" @endif><a href="{{ $th['url'] }}" class="text-dark text-decoration-none">{{ $th['label'] }}@if($th['active'])<span class="text-muted small ms-1" aria-hidden="true">{{ $th['icon'] }}</span>@endif</a></th>
                                    @php $th = $ongoingSheetSortTh('checklist_sent_at', 'Checklist sent', 'desc'); @endphp
                                    <th class="text-nowrap" @if($th['ariaSort']) aria-sort="{{ $th['ariaSort'] }}" @endif><a href="{{ $th['url'] }}" class="text-dark text-decoration-none">{{ $th['label'] }}@if($th['active'])<span class="text-muted small ms-1" aria-hidden="true">{{ $th['icon'] }}</span>@endif</a></th>
                                    <th>Email reminder</th>
                                    <th>SMS reminder</th>
                                    <th>Phone reminder</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @if($rows->isEmpty())
                                    <tr>
                                        <td colspan="{{ (isset($sheetType) && $sheetType === 'checklist') ? 15 : 13 }}" class="text-center py-4">
                                            @icon('info-circle', 'solid', ['class' => '2x text-muted mb-2 d-block'])
                                            <p class="mb-0">No records found.</p>
                                        </td>
                                    </tr>
                                @else
                                    @foreach($rows as $row)
                                        @php
                                            $clientEncodedId = base64_encode(convert_uuencode($row->client_id));
                                            $isLeadClient = strtolower((string) ($row->client_type ?? 'client')) === 'lead';
                                            $appDetailUrl = $isLeadClient
                                                ? route('leads.detail.application', ['id' => $clientEncodedId, 'applicationId' => $row->application_id])
                                                : route('clients.detail.application', ['id' => $clientEncodedId, 'applicationId' => $row->application_id]);
                                            $gateCourseNav = Auth::guard('admin')->user() instanceof \App\Models\Staff
                                                && \App\Services\SearchService::staffShouldGateClientNavigation((int) $row->client_id, Auth::guard('admin')->user());
                                            $accessModalDisplayName = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
                                            $applicationCreatedDisplay = '—';
                                            if (!empty($row->application_created_at)) {
                                                try {
                                                    $applicationCreatedDisplay = \Carbon\Carbon::parse($row->application_created_at)
                                                        ->timezone(config('app.timezone'))
                                                        ->format('d/m/Y');
                                                } catch (\Throwable $e) {
                                                    $applicationCreatedDisplay = '—';
                                                }
                                            }
                                        @endphp
                                        <tr>
                                            <td class="ongoing-course-cell">
                                                @if($gateCourseNav)
                                                    <a href="#" class="ongoing-course-link ongoing-course-link--crm-access-gate"
                                                       data-admin-id="{{ (int) $row->client_id }}"
                                                       data-encoded-id="{{ $clientEncodedId }}"
                                                       data-is-lead="{{ $isLeadClient ? '1' : '0' }}"
                                                       data-after-access-url="{{ $appDetailUrl }}"
                                                       data-display-name="{{ e($accessModalDisplayName !== '' ? $accessModalDisplayName : ($row->course_name ?? '')) }}">{{ $row->course_name ?? '—' }}</a>
                                                @else
                                                    <a href="{{ $appDetailUrl }}" class="ongoing-course-link">{{ $row->course_name ?? '—' }}</a>
                                                @endif
                                            </td>
                                            @if(isset($sheetType) && $sheetType === 'checklist')
                                            <td>{{ $applicationCreatedDisplay }}</td>
                                            @endif
                                            @if(!isset($sheetType) || $sheetType !== 'checklist')
                                            <td>{{ $row->crm_ref ?? '—' }}</td>
                                            <td>{{ $applicationCreatedDisplay }}</td>
                                            @endif
                                            <td>{{ trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')) ?: '—' }}</td>
                                            <td>{{ $row->dob ? \Carbon\Carbon::parse($row->dob)->format('d/m/Y') : '—' }}</td>
                                            <td class="@if(($row->total_payment ?? 0) > 0 && empty($row->payment_display_note) && ($row->is_paid_to_college ?? 0)) text-success fw-semibold @endif" @if(($row->total_payment ?? 0) > 0 && empty($row->payment_display_note) && ($row->is_paid_to_college ?? 0)) title="Paid directly to college" @endif>
                                                @if($row->payment_display_note)
                                                    {{ $row->payment_display_note }}
                                                @elseif($row->total_payment > 0)
                                                    ${{ number_format($row->total_payment, 2) }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>{{ $row->institute_override ?? $row->partner_name ?? $row->service_college ?? '—' }}</td>
                                            @if(!isset($sheetType) || $sheetType !== 'checklist')
                                            <td class="branch-cell">{{ $row->branch_name ?? '—' }}</td>
                                            @endif
                                            <td>
                                                <span class="ongoing-assignee-display">{{ trim(($row->assignee_first_name ?? '') . ' ' . ($row->assignee_last_name ?? '')) ?: '—' }}</span>
                                                <a href="javascript:;" class="ongoing-assignee-edit ms-1" data-app-id="{{ $row->application_id }}" data-assignee-id="{{ $row->assignee_id ?? '' }}" title="Change assignee">@icon('edit', 'solid', ['class' => 'text-muted small'])</a>
                                            </td>
                                            <td>
                                                @if($row->visaexpiry && $row->visaexpiry != '0000-00-00')
                                                    {{ \Carbon\Carbon::parse($row->visaexpiry)->format('d/m/Y') }}
                                                    @if($row->visa_opt)
                                                        <span class="text-muted">({{ $row->visa_opt }})</span>
                                                    @endif
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                @if($row->visa_category_override)
                                                    {{ $row->visa_category_override }}
                                                @else
                                                    {{ trim(($row->visa_type ?? '') . ' ' . ($row->visa_opt ?? '')) ?: '—' }}
                                                @endif
                                            </td>
                                            @if(!isset($sheetType) || $sheetType !== 'checklist')
                                            <td class="status-cell">{{ $row->application_stage ?? '—' }}</td>
                                            @endif
                                            <td class="comment-cell">
                                                <span class="sheet-comment-text">{{ $row->sheet_comment_text ?? '—' }}</span>
                                                <a href="javascript:;" class="sheet-comment-edit ms-1" data-app-id="{{ $row->application_id }}" data-comment="{{ e($row->sheet_comment_text ?? '') }}" title="Add/Edit comment">@icon('edit', 'solid', ['class' => 'text-muted small'])</a>
                                            </td>
                                            @if(isset($sheetType) && $sheetType === 'checklist')
                                            <td class="checklist-status-cell">
                                                @php
                                                    $currentStatus = $row->checklist_sheet_status ?? 'active';
                                                    $statusLabels = ['active' => 'Active', 'convert_to_client' => 'Convert to client', 'discontinue' => 'Discontinue', 'hold' => 'Hold'];
                                                @endphp
                                                <select class="form-control form-control-sm checklist-status-select" data-app-id="{{ $row->application_id }}" title="Status">
                                                    @foreach($statusLabels as $val => $label)
                                                    <option value="{{ $val }}" {{ $currentStatus === $val ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="checklist-sent-cell">
                                                @php
                                                    $clientEncodedIdForResend = base64_encode(convert_uuencode($row->client_id));
                                                    $resendChecklistUrl = route('clients.detail', ['id' => $clientEncodedIdForResend]) . '?applicationId=' . $row->application_id . '&open_checklist_email=1';
                                                    $emailReminderUrl = route('clients.detail', ['id' => $clientEncodedIdForResend]) . '?applicationId=' . $row->application_id . '&open_email_reminder=1';
                                                    $smsReminderUrl = route('clients.detail', ['id' => $clientEncodedIdForResend]) . '?applicationId=' . $row->application_id . '&open_sms_reminder=1';
                                                @endphp
                                                @if($row->checklist_sent_at)
                                                    {{ \Carbon\Carbon::parse($row->checklist_sent_at)->format('d/m/Y') }}
                                                    <br><a href="{{ $resendChecklistUrl }}" class="btn btn-sm btn-outline-secondary mt-1" title="Resend checklist email">Resend checklist</a>
                                                @else
                                                    Not sent
                                                    <br><a href="{{ $resendChecklistUrl }}" class="btn btn-sm btn-outline-primary mt-1" title="Send checklist email">Send checklist</a>
                                                @endif
                                            </td>
                                            <td class="reminder-cell">
                                                @if(!empty($row->email_reminder_latest))
                                                    {{ \Carbon\Carbon::parse($row->email_reminder_latest)->format('d/m/Y') }}@if($row->email_reminder_count > 0) ({{ $row->email_reminder_count }})@endif
                                                @else
                                                    —
                                                @endif
                                                <br><a href="{{ $emailReminderUrl }}" class="btn btn-sm btn-outline-secondary mt-1 checklist-reminder-link" data-msg="Open email to send reminder?" title="Email reminder">Email reminder</a>
                                            </td>
                                            <td class="reminder-cell">
                                                @if(!empty($row->sms_reminder_latest))
                                                    {{ \Carbon\Carbon::parse($row->sms_reminder_latest)->format('d/m/Y') }}@if($row->sms_reminder_count > 0) ({{ $row->sms_reminder_count }})@endif
                                                @else
                                                    —
                                                @endif
                                                <br><a href="{{ $smsReminderUrl }}" class="btn btn-sm btn-outline-secondary mt-1 checklist-reminder-link" data-msg="Open SMS to send reminder?" title="SMS reminder">SMS reminder</a>
                                            </td>
                                            <td class="reminder-cell">
                                                @if(!empty($row->phone_reminder_latest))
                                                    {{ \Carbon\Carbon::parse($row->phone_reminder_latest)->format('d/m/Y') }}@if($row->phone_reminder_count > 0) ({{ $row->phone_reminder_count }})@endif
                                                @else
                                                    —
                                                @endif
                                                <br><button type="button" class="btn btn-sm btn-outline-secondary mt-1 checklist-phone-reminder-btn" data-app-id="{{ $row->application_id }}" data-msg="Record phone reminder now?" title="Phone reminder">Phone reminder</button>
                                            </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                        </div>
                    </div>
                    
                    {{-- Pagination --}}
                    @if($rows->hasPages())
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    Showing {{ $rows->firstItem() ?? 0 }} to {{ $rows->lastItem() ?? 0 }} of {{ $rows->total() }} entries
                                </div>
                                <div>
                                    {{ $rows->links() }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            
        </div>
    </section>
</div>

{{-- Sheet comment modal --}}
<div class="modal fade" id="sheetCommentModal" tabindex="-1" aria-labelledby="sheetCommentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sheetCommentModalLabel">Sheet comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="sheet_comment_application_id" value="">
                <p class="small text-muted">This comment replaces the previous one and appears in Notes &amp; Activity with course and college name.</p>
                <div class="mb-3">
                    <label for="sheet_comment_text" class="form-label">Comment</label>
                    <textarea class="form-control" id="sheet_comment_text" rows="4" maxlength="65535" placeholder="Enter comment..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="sheet_comment_save">Save</button>
            </div>
        </div>
    </div>
</div>

{{-- Change assignee modal --}}
<div class="modal fade" id="sheetChangeAssigneeModal" tabindex="-1" aria-labelledby="sheetChangeAssigneeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sheetChangeAssigneeModalLabel">Change assignee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="sheet_assignee_application_id" value="">
                <div class="mb-3">
                    <label for="sheet_assignee_select" class="form-label">Assignee</label>
                    <select class="form-control" id="sheet_assignee_select">
                        <option value="">Select assignee</option>
                        @foreach($assigneesForChangeModal ?? $assignees as $a)
                            <option value="{{ $a->id }}">{{ trim(($a->first_name ?? '') . ' ' . ($a->last_name ?? '')) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="sheet_assignee_save">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Horizontal scroll indicators (matches MigrationManager2 pattern)
    var $scrollContainer = $('#ongoing-sheet-scroll-container');
    var $leftIndicator = $('.ongoing-sheet-scroll-indicator-left');
    var $rightIndicator = $('.ongoing-sheet-scroll-indicator-right');
    function updateScrollIndicators() {
        if (!$scrollContainer.length || !$scrollContainer[0]) return;
        var scrollLeft = $scrollContainer.scrollLeft();
        var scrollWidth = $scrollContainer[0].scrollWidth;
        var clientWidth = $scrollContainer[0].clientWidth;
        var maxScroll = scrollWidth - clientWidth;
        $leftIndicator.toggleClass('visible', scrollLeft > 10);
        $rightIndicator.toggleClass('visible', scrollLeft < maxScroll - 10);
    }
    $scrollContainer.on('scroll', updateScrollIndicators);
    $(window).on('resize', updateScrollIndicators);
    setTimeout(updateScrollIndicators, 100);
    // Vertical mouse wheel scrolls horizontally when content overflows
    $scrollContainer.on('wheel', function(e) {
        if (e.originalEvent.deltaY !== 0 && !e.shiftKey) {
            var el = this;
            if (el.scrollWidth > el.clientWidth) {
                e.preventDefault();
                el.scrollLeft += e.originalEvent.deltaY;
            }
        }
    });

    // Initialize flatpickr for date inputs
    flatpickr('.dobdatepicker', {
        dateFormat: 'd/m/Y',
        allowInput: true,
        clickOpens: true
    });
    
    // Tom Select for filter panel (init when ready; also after Filters panel opens)
    var ongoingStageTomSelectBound = false;

    function resolveOngoingStageOptionValue(stageEl, stageVal) {
        if (!stageEl || !stageVal) {
            return null;
        }
        var norm = String(stageVal).trim().toLowerCase();
        var resolved = null;
        stageEl.querySelectorAll('option').forEach(function (opt) {
            var optVal = opt.getAttribute('value');
            if (optVal && String(optVal).trim().toLowerCase() === norm) {
                resolved = optVal;
            }
        });
        return resolved;
    }

    function getOngoingStagePreservedValue(stageEl) {
        if (!stageEl) {
            return null;
        }
        // data-selected-stage is always set server-side; empty means "All stages" and must not
        // fall back to the first real stage option (browser default).
        if (stageEl.hasAttribute('data-selected-stage')) {
            var fromData = (stageEl.getAttribute('data-selected-stage') || '').trim();
            if (fromData === '') {
                return '';
            }
            return resolveOngoingStageOptionValue(stageEl, fromData) || fromData;
        }
        var selected = stageEl.querySelector('option:checked');
        if (selected) {
            var selectedVal = String(selected.value || '').trim();
            if (selectedVal === '') {
                return '';
            }
            return resolveOngoingStageOptionValue(stageEl, selectedVal) || selectedVal;
        }
        if (stageEl.value && String(stageEl.value).trim() !== '') {
            return resolveOngoingStageOptionValue(stageEl, stageEl.value) || stageEl.value;
        }
        return '';
    }

    function initOngoingFilterTomSelects() {
        if (typeof initTomSelectPreserveValue !== 'function') {
            return;
        }

        document.querySelectorAll('.ongoing-filter-panel select.ongoing-filter-branch-select').forEach(function (el) {
            if (el.tomselect) {
                return;
            }
            initTomSelectPreserveValue(el, {
                width: '100%',
                multiple: true,
                closeOnSelect: false
            });
        });

        var stageEl = document.getElementById('ongoing-current-stage-filter');
        if (!stageEl || stageEl.tomselect) {
            return;
        }

        var preservedVal = getOngoingStagePreservedValue(stageEl);
        initTomSelectPreserveValue(stageEl, {
            width: '100%',
            allowClear: true,
            placeholder: 'All stages'
        });

        if (typeof setEnhancedSelectValue === 'function') {
            setEnhancedSelectValue(stageEl, preservedVal || '', true);
        } else if (preservedVal) {
            stageEl.value = preservedVal;
        }

        if (!ongoingStageTomSelectBound) {
            stageEl.addEventListener('change', function () {
                var val = typeof getEnhancedSelectValue === 'function'
                    ? getEnhancedSelectValue(stageEl)
                    : stageEl.value;
                stageEl.setAttribute('data-selected-stage', val || '');
            });
            ongoingStageTomSelectBound = true;
        }
    }

    function bootOngoingFilterTomSelects() {
        if (typeof whenTomSelectReady === 'function') {
            whenTomSelectReady(initOngoingFilterTomSelects);
        } else if (typeof waitForTomSelect === 'function') {
            waitForTomSelect().then(initOngoingFilterTomSelects);
        } else {
            initOngoingFilterTomSelects();
        }
    }

    bootOngoingFilterTomSelects();

    var filterPanelEl = document.getElementById('filterPanel');
    if (filterPanelEl) {
        filterPanelEl.addEventListener('shown.bs.collapse', function () {
            window.setTimeout(bootOngoingFilterTomSelects, 50);
        });
    }
    // Assignee in top bar: navigate on change, preserve other params
    $('#ongoing-assignee-bar').on('change', function() {
        var assignee = $(this).val();
        var params = new URLSearchParams(window.location.search);
        params.set('assignee', assignee || 'all');
        params.delete('page');
        window.location.href = '{{ route($sheetRoute ?? "clients.sheets.ongoing") }}?' + params.toString();
    });
    
    $('.sheet-comment-edit').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var appId = $(this).data('app-id');
        var comment = $(this).data('comment') || '';
        $('#sheet_comment_application_id').val(appId);
        $('#sheet_comment_text').val(comment);
        var modalEl = document.getElementById('sheetCommentModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        } else {
            $(modalEl).modal('show');
        }
    });

    $('.ongoing-assignee-edit').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var appId = $(this).data('app-id');
        var assigneeId = $(this).data('assignee-id') || '';
        $('#sheet_assignee_application_id').val(appId);
        $('#sheet_assignee_select').val(assigneeId);
        var modalEl = document.getElementById('sheetChangeAssigneeModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        } else {
            $(modalEl).modal('show');
        }
    });

    $('#sheet_assignee_save').on('click', function() {
        var appId = $('#sheet_assignee_application_id').val();
        var assigneeId = $('#sheet_assignee_select').val();
        if (!assigneeId) {
            toastMsg('Please select an assignee.', 'warning');
            return;
        }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '{{ route("application.change-assignee") }}',
            method: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                application_id: parseInt(appId, 10) || appId,
                assignee_id: parseInt(assigneeId, 10) || assigneeId
            },
            success: function(res) {
                if (res && res.success) {
                    var modalEl = document.getElementById('sheetChangeAssigneeModal');
                    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();
                    } else {
                        $(modalEl).modal('hide');
                    }
                    location.reload();
                } else {
                    toastMsg((res && res.message) || 'Failed to update assignee.', 'error');
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to update assignee.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                }
                toastMsg(msg, 'error');
            },
            complete: function() { $btn.prop('disabled', false); }
        });
    });

    $('#sheet_comment_save').on('click', function() {
        var appId = $('#sheet_comment_application_id').val();
        var comment = $('#sheet_comment_text').val().trim();
        if (!comment) {
            toastMsg('Please enter a comment.', 'warning');
            return;
        }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '{{ route("clients.sheets.ongoing.sheet-comment") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                application_id: appId,
                comment: comment
            },
            success: function() {
                var modalEl = document.getElementById('sheetCommentModal');
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                } else {
                    $(modalEl).modal('hide');
                }
                location.reload();
            },
            error: function(xhr) {
                toastMsg(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to save comment.', 'error');
            },
            complete: function() { $btn.prop('disabled', false); }
        });
    });

    // Checklist sheet: Status dropdown — update status; reload if row leaves sheet or Hold (reorder)
    $(document).on('change', '.checklist-status-select', function() {
        var $select = $(this);
        var appId = $select.data('app-id');
        var status = $select.val();
        var $selectParent = $select.closest('td');
        $select.prop('disabled', true);
        $.ajax({
            url: '{{ route("clients.sheets.checklist.update-status") }}',
            method: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                application_id: parseInt(appId, 10),
                status: status
            },
            success: function(res) {
                if (res && res.success) {
                    if (res.data && res.data.leaves_sheet) {
                        location.reload();
                    } else if (status === 'hold') {
                        location.reload();
                    }
                } else {
                    toastMsg((res && res.message) || 'Failed to update status.', 'error');
                    $select.val($select.data('previous') || 'active');
                }
                $select.prop('disabled', false);
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to update status.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    msg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                }
                toastMsg(msg, 'error');
                $select.val($select.data('previous') || 'active');
                $select.prop('disabled', false);
            }
        });
    });
    $(document).on('focus', '.checklist-status-select', function() {
        $(this).data('previous', $(this).val());
    });

    // Checklist sheet: Email/SMS reminder links — confirm then navigate
    $(document).on('click', '.checklist-reminder-link', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var msg = $(this).data('msg') || 'Continue?';
        var href = $(this).attr('href');
        crmConfirm(msg).then(function (ok) {
            if (ok) {
                window.location.href = href;
            }
        });
    });

    // Checklist sheet: Phone reminder — confirm then AJAX
    $(document).on('click', '.checklist-phone-reminder-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $btn = $(this);
        var appId = $btn.data('app-id');
        var msg = $btn.data('msg') || 'Record phone reminder now?';
        crmConfirm(msg).then(function (ok) {
            if (!ok) {
                return;
            }
            $btn.prop('disabled', true);
            $.ajax({
                url: '{{ route("clients.sheets.checklist.phone-reminder") }}',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}', application_id: appId },
                dataType: 'json',
                success: function(res) {
                    if (res && res.success) {
                        location.reload();
                    } else {
                        toastMsg((res && res.message) || 'Failed to record reminder.', 'error');
                    }
                },
                error: function(xhr) {
                    var errMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to record reminder.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errMsg = Object.values(xhr.responseJSON.errors).flat().join(' ');
                    }
                    toastMsg(errMsg, 'error');
                },
                complete: function() { $btn.prop('disabled', false); }
            });
        });
    });
});
</script>
@endpush
