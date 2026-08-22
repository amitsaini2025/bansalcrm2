@php
    $followupCalendarTabs = $followupCalendarTabs ?? [];
    $followupCalendarDefault = $followupCalendarDefault ?? \App\Http\Controllers\Admin\FollowupController::DASHBOARD_DEFAULT_CONSULTANT;
@endphp
@if(!empty($followupCalendarTabs))
<style>
    .dash-followup-cal {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 14px rgba(15, 23, 42, 0.08);
        border: 1px solid #e5e7eb;
        overflow: hidden;
        width: 100%;
    }
    .dash-followup-cal:hover {
        transform: none;
        box-shadow: 0 2px 14px rgba(15, 23, 42, 0.08);
    }
    .dash-followup-cal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem 1rem;
        padding: 1rem 1.25rem 0.75rem;
    }
    .dash-followup-cal-title {
        margin: 0;
        font-size: 1.125rem;
        font-weight: 700;
        color: #1f2937;
    }
    .dash-followup-cal-sub {
        margin: 0.2rem 0 0;
        font-size: 0.8125rem;
        color: #6b7280;
    }
    .dash-followup-cal-metrics {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .dash-followup-cal-metric {
        min-width: 4.75rem;
        text-align: center;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.35rem 0.65rem;
    }
    .dash-followup-cal-metric span {
        display: block;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #6b7280;
    }
    .dash-followup-cal-metric strong {
        display: block;
        font-size: 1.15rem;
        font-weight: 700;
        color: #111827;
        line-height: 1.2;
    }
    .dash-followup-cal-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        padding: 0 1.25rem 0.75rem;
    }
    .dash-followup-cal-tab {
        border: 1px solid #dbe3f0;
        background: #fff;
        color: #334155;
        border-radius: 999px;
        padding: 0.28rem 0.85rem;
        font-size: 0.8125rem;
        font-weight: 600;
        line-height: 1.3;
        cursor: pointer;
    }
    .dash-followup-cal-tab:hover {
        border-color: #93c5fd;
        color: #1d4ed8;
    }
    .dash-followup-cal-tab.is-active {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }
    .dash-followup-cal-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 0.85rem;
        padding: 0 1.25rem 0.85rem;
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 600;
    }
    .dash-followup-cal-legend i {
        display: inline-block;
        width: 0.55rem;
        height: 0.55rem;
        border-radius: 50%;
        margin-right: 0.3rem;
        vertical-align: middle;
    }
    .dash-followup-cal-dot-confirmed { background: #22c55e; }
    .dash-followup-cal-dot-completed { background: #94a3b8; }
    .dash-followup-cal-dot-cancelled { background: #ef4444; }
    .dash-followup-cal-dot-no_show { background: #64748b; }
    .dash-followup-cal-body {
        padding: 0 0.75rem 1rem;
    }
    .dash-followup-cal-grid {
        min-height: 420px;
    }
    .dash-followup-cal-side {
        border-left: 1px solid #f1f5f9;
        padding-left: 0.75rem;
        display: flex;
        flex-direction: column;
        max-height: 560px;
    }
    .dash-followup-cal-side-title {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #1f2937;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .dash-followup-cal-side-sub {
        margin: 0.2rem 0 0.75rem;
        font-size: 0.75rem;
        color: #6b7280;
    }
    .dash-followup-cal-list {
        overflow-y: auto;
        flex: 1;
        min-height: 0;
        padding-right: 0.25rem;
    }
    .dash-followup-cal-group-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #475569;
        margin: 0.5rem 0 0.35rem;
    }
    .dash-followup-cal-item {
        display: block;
        text-decoration: none;
        border: 1px solid #eef2f7;
        border-radius: 8px;
        padding: 0.5rem 0.65rem;
        margin-bottom: 0.4rem;
        background: #fff;
        color: inherit;
    }
    .dash-followup-cal-item:hover {
        border-color: #bfdbfe;
        background: #f8fafc;
    }
    .dash-followup-cal-item-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.15rem;
    }
    .dash-followup-cal-item-time {
        font-size: 0.75rem;
        font-weight: 700;
        color: #334155;
        text-transform: lowercase;
    }
    .dash-followup-cal-badge {
        font-size: 0.65rem;
        font-weight: 700;
        border-radius: 999px;
        padding: 0.12rem 0.45rem;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
    .dash-followup-cal-badge-confirmed { background: #dcfce7; color: #166534; }
    .dash-followup-cal-badge-completed { background: #e2e8f0; color: #334155; }
    .dash-followup-cal-badge-cancelled { background: #fee2e2; color: #991b1b; }
    .dash-followup-cal-badge-no_show { background: #e2e8f0; color: #475569; }
    .dash-followup-cal-item-name {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #0f172a;
    }
    .dash-followup-cal-item-meta {
        font-size: 0.75rem;
        color: #64748b;
    }
    .dash-followup-cal-empty {
        font-size: 0.8125rem;
        color: #9ca3af;
        padding: 1rem 0;
        margin: 0;
    }
    #dashboardFollowupCalendar .fc-followup-pill-wrap.fc-event,
    #dashboardFollowupCalendar .fc-followup-pill-wrap.fc-daygrid-event {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        margin-top: 2px !important;
        margin-bottom: 2px !important;
    }
    #dashboardFollowupCalendar .fc-followup-pill-wrap .fc-event-main {
        padding: 0 !important;
    }
    #dashboardFollowupCalendar .dash-fc-pill {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 6px;
        color: #fff !important;
        font-weight: 600;
        border-radius: 6px;
        padding: 3px 7px;
        font-size: 0.75rem;
        line-height: 1.2;
        width: 100%;
        box-sizing: border-box;
        white-space: nowrap;
        overflow: hidden;
    }
    #dashboardFollowupCalendar .fc-followup-status-confirmed .dash-fc-pill { background: #22c55e; }
    #dashboardFollowupCalendar .fc-followup-status-completed .dash-fc-pill { background: #94a3b8; }
    #dashboardFollowupCalendar .fc-followup-status-cancelled .dash-fc-pill { background: #ef4444; }
    #dashboardFollowupCalendar .fc-followup-status-no_show .dash-fc-pill { background: #64748b; }
    #dashboardFollowupCalendar .fc-toolbar-title {
        font-size: 1.05rem;
        font-weight: 700;
    }
    #dashboardFollowupCalendar .fc-button {
        text-transform: lowercase;
        font-weight: 600;
        font-size: 0.75rem;
        border-radius: 6px !important;
        padding: 0.2rem 0.55rem;
        background: #fff;
        color: #334155;
        border: 1px solid #cbd5e1;
    }
    #dashboardFollowupCalendar .fc-button-primary:not(:disabled).fc-button-active,
    #dashboardFollowupCalendar .fc-button-primary:not(:disabled):active {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
    }
    #dashboardFollowupCalendar .fc-button-primary:hover {
        background: #eff6ff;
        border-color: #93c5fd;
        color: #1d4ed8;
    }
    #dashboardFollowupCalendar .fc-event,
    #dashboardFollowupCalendar .fc-event-main {
        cursor: pointer;
    }
    @media (max-width: 991.98px) {
        .dash-followup-cal-side {
            border-left: 0;
            border-top: 1px solid #f1f5f9;
            padding-left: 0;
            padding-top: 0.85rem;
            margin-top: 0.75rem;
            max-height: 320px;
        }
    }
</style>
<div class="row mb-4" id="dashboardFollowupCalendarPanel">
    <div class="col-12">
        <div class="dash-followup-cal">
            <div class="dash-followup-cal-header">
                <div>
                    <h3 class="dash-followup-cal-title">Followup calendar</h3>
                    <p class="dash-followup-cal-sub">Choose a calendar to view its appointments.</p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="dash-followup-cal-metrics" aria-live="polite">
                        <div class="dash-followup-cal-metric">
                            <span>Today</span>
                            <strong data-metric="today">0</strong>
                        </div>
                        <div class="dash-followup-cal-metric">
                            <span>This week</span>
                            <strong data-metric="this_week">0</strong>
                        </div>
                        <div class="dash-followup-cal-metric">
                            <span>Upcoming</span>
                            <strong data-metric="upcoming">0</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="dash-followup-cal-tabs" role="tablist" aria-label="Followup calendars">
                @foreach($followupCalendarTabs as $tab)
                    <button type="button"
                        class="dash-followup-cal-tab{{ $tab['slug'] === $followupCalendarDefault ? ' is-active' : '' }}"
                        role="tab"
                        aria-selected="{{ $tab['slug'] === $followupCalendarDefault ? 'true' : 'false' }}"
                        data-slug="{{ $tab['slug'] }}"
                        data-events-url="{{ $tab['events_url'] }}">
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>
            <div class="dash-followup-cal-legend">
                <span><i class="dash-followup-cal-dot-confirmed"></i>Confirmed</span>
                <span><i class="dash-followup-cal-dot-completed"></i>Completed</span>
                <span><i class="dash-followup-cal-dot-cancelled"></i>Cancelled</span>
                <span><i class="dash-followup-cal-dot-no_show"></i>No show</span>
            </div>
            <div class="dash-followup-cal-body">
                <div class="row g-2">
                    <div class="col-lg-8">
                        <div class="dash-followup-cal-grid" id="dashboardFollowupCalendar"></div>
                    </div>
                    <div class="col-lg-4">
                        <div class="dash-followup-cal-side">
                            <h4 class="dash-followup-cal-side-title">@icon('list-ul') Appointments</h4>
                            <p class="dash-followup-cal-side-sub">Upcoming appointments for the selected calendar, grouped by date.</p>
                            <div class="dash-followup-cal-list" id="dashboardFollowupAppointments">
                                <p class="dash-followup-cal-empty mb-0">Loading appointments…</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
