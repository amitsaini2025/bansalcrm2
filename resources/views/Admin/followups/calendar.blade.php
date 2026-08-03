@extends('layouts.admin')
@section('title', $consultantLabel)

@section('content')
<style>
/* Follow-up pills (reference: green bar, time left, name + channel right) */
#followupCalendar .fc-followup-pill-wrap.fc-event,
#followupCalendar .fc-followup-pill-wrap.fc-daygrid-event {
	background: transparent !important;
	border: none !important;
	box-shadow: none !important;
	margin-top: 2px !important;
	margin-bottom: 3px !important;
}
#followupCalendar .fc-followup-pill-wrap .fc-event-main {
	padding: 0 !important;
}
#followupCalendar .followup-fc-pill {
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 8px;
	background: #198754;
	color: #fff !important;
	font-weight: 700;
	border-radius: 8px;
	padding: 7px 11px;
	font-size: 0.9375rem;
	line-height: 1.25;
	width: 100%;
	box-sizing: border-box;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
}
/* Follow-up outcome colours */
#followupCalendar .fc-followup-status-confirmed .followup-fc-pill {
	background: #198754;
}
#followupCalendar .fc-followup-status-completed .followup-fc-pill {
	background: #0d6efd;
}
#followupCalendar .fc-followup-status-cancelled .followup-fc-pill {
	background: #dc3545;
}
#followupCalendar .fc-followup-status-no_show .followup-fc-pill {
	background: #6c757d;
}
#followupCalendar .followup-fc-time {
	flex-shrink: 0;
	text-transform: lowercase;
}
#followupCalendar .followup-fc-meta {
	text-align: right;
	flex: 1;
	min-width: 0;
	word-break: break-word;
}
/* Month grid: keep one line — smaller type + tight padding (tooltip still has full text) */
#followupCalendar .fc-daygrid-body .fc-daygrid-event-harness {
	overflow: visible !important;
}
#followupCalendar .fc-daygrid-body .fc-daygrid-day-events {
	overflow: visible !important;
}
#followupCalendar .fc-daygrid-body .fc-followup-pill-wrap.fc-daygrid-event {
	overflow: visible !important;
	white-space: nowrap !important;
}
#followupCalendar .fc-daygrid-body .fc-followup-pill-wrap .fc-event-main,
#followupCalendar .fc-daygrid-body .fc-followup-pill-wrap .fc-event-main-frame {
	overflow: visible !important;
	white-space: nowrap !important;
}
#followupCalendar .fc-daygrid-body .followup-fc-pill {
	flex-direction: row;
	flex-wrap: nowrap;
	align-items: center;
	justify-content: flex-start;
	gap: 5px;
	padding: 4px 8px;
	font-size: 0.875rem;
	line-height: 1.2;
	font-weight: 600;
	white-space: nowrap;
	overflow: visible;
}
#followupCalendar .fc-daygrid-body .followup-fc-time {
	flex-shrink: 0;
	font-weight: 700;
}
#followupCalendar .fc-daygrid-body .followup-fc-meta {
	flex: 0 1 auto;
	min-width: 0;
	text-align: left;
	white-space: nowrap;
	overflow: visible;
	word-break: normal;
}
.followup-detail-modal .modal-content {
	border-radius: 12px;
	overflow: hidden;
}
.followup-detail-modal .modal-dialog {
	max-height: calc(100vh - 1.25rem);
	margin: 0.625rem auto;
}
.followup-detail-modal.modal .modal-dialog-scrollable .modal-content {
	max-height: min(92vh, 920px);
}
.followup-detail-modal .modal-body {
	overflow-y: auto;
	-webkit-overflow-scrolling: touch;
}
.followup-detail-modal .followup-section-label {
	font-size: 0.68rem;
	text-transform: uppercase;
	letter-spacing: 0.04em;
	color: #64748b;
	font-weight: 700;
	margin-bottom: 0.5rem;
}
.followup-detail-modal .followup-dl dt {
	font-weight: 600;
	color: #475569;
	font-size: 0.82rem;
}
.followup-detail-modal .followup-dl dd {
	font-size: 0.9rem;
	color: #0f172a;
	margin-bottom: 0.65rem;
}
/* One row per field: tighter type + ellipsis on long values (native tooltip shows full text on hover where browser supports it) */
.followup-detail-modal .followup-dl-compact .followup-dl-row {
	display: grid;
	grid-template-columns: minmax(7.5rem, 36%) minmax(0, 1fr);
	gap: 0.35rem 0.65rem;
	align-items: center;
	margin-bottom: 0.28rem;
}
.followup-detail-modal .followup-dl-compact .followup-dl-row:last-child {
	margin-bottom: 0;
}
.followup-detail-modal .followup-dl-compact dt {
	font-size: 0.72rem;
	font-weight: 600;
	color: #475569;
	margin: 0;
	align-self: center;
	line-height: 1.25;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.followup-detail-modal .followup-dl-compact dd {
	font-size: 0.78rem;
	color: #0f172a;
	margin: 0;
	min-width: 0;
	line-height: 1.25;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.followup-detail-modal .followup-dl-compact dd .form-select {
	max-width: 100%;
}
.followup-detail-modal .followup-dl-compact dd.followup-dl-dd-control,
.followup-detail-modal .followup-dl-compact dd.followup-dl-dd-badge {
	white-space: normal;
	overflow: visible;
	text-overflow: clip;
}
.followup-detail-modal .followup-dl-compact dd a {
	display: block;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.followup-detail-modal.show .modal-dialog.modal-dialog-centered {
	align-items: flex-start;
	padding-top: 0.35rem;
	padding-bottom: 1rem;
}
.followup-detail-modal .followup-outcome-list .list-group-item {
	border-radius: 8px !important;
	margin-bottom: 4px;
	cursor: pointer;
	font-size: 0.8125rem;
	padding-top: 0.45rem;
	padding-bottom: 0.45rem;
	font-weight: 600;
	border: 1px solid #e2e8f0 !important;
	background-color: #fff !important;
	color: #334155 !important;
	box-shadow: none;
	transition: background-color 0.12s ease, border-color 0.12s ease, color 0.12s ease;
}
.followup-detail-modal .followup-outcome-list .list-group-item i {
	opacity: 1;
	transition: color 0.12s ease;
}
/* Idle: coloured label only */
.followup-detail-modal .followup-outcome-list .followup-outcome-confirmed {
	color: #198754 !important;
}
.followup-detail-modal .followup-outcome-list .followup-outcome-confirmed i {
	color: #198754 !important;
}
.followup-detail-modal .followup-outcome-list .followup-outcome-completed {
	color: #0d6efd !important;
}
.followup-detail-modal .followup-outcome-list .followup-outcome-completed i {
	color: #0d6efd !important;
}
.followup-detail-modal .followup-outcome-list .followup-outcome-cancelled {
	color: #dc3545 !important;
}
.followup-detail-modal .followup-outcome-list .followup-outcome-cancelled i {
	color: #dc3545 !important;
}
/* Hover / keyboard focus: full pill colour */
.followup-detail-modal .followup-outcome-list .followup-outcome-confirmed:hover,
.followup-detail-modal .followup-outcome-list .followup-outcome-confirmed:focus-visible {
	background-color: #198754 !important;
	border-color: #198754 !important;
	color: #fff !important;
}
.followup-detail-modal .followup-outcome-list .followup-outcome-confirmed:hover i,
.followup-detail-modal .followup-outcome-list .followup-outcome-confirmed:focus-visible i {
	color: #fff !important;
}
.followup-detail-modal .followup-outcome-list .followup-outcome-completed:hover,
.followup-detail-modal .followup-outcome-list .followup-outcome-completed:focus-visible {
	background-color: #0d6efd !important;
	border-color: #0d6efd !important;
	color: #fff !important;
}
.followup-detail-modal .followup-outcome-list .followup-outcome-completed:hover i,
.followup-detail-modal .followup-outcome-list .followup-outcome-completed:focus-visible i {
	color: #fff !important;
}
.followup-detail-modal .followup-outcome-list .followup-outcome-cancelled:hover,
.followup-detail-modal .followup-outcome-list .followup-outcome-cancelled:focus-visible {
	background-color: #dc3545 !important;
	border-color: #dc3545 !important;
	color: #fff !important;
}
.followup-detail-modal .followup-outcome-list .followup-outcome-cancelled:hover i,
.followup-detail-modal .followup-outcome-list .followup-outcome-cancelled:focus-visible i {
	color: #fff !important;
}
.followup-detail-modal .followup-outcome-list .list-group-item:last-child {
	margin-bottom: 0;
}
.followup-detail-modal #followup-reschedule-btn {
	border-radius: 8px;
	padding: 0.45rem 1rem;
	font-weight: 600;
}
.followup-detail-modal .modal-footer .btn {
	border-radius: 8px;
	font-weight: 600;
}
@media (min-width: 992px) {
	.followup-detail-modal .border-end-lg {
		border-right: 1px solid #e2e8f0;
	}
}
/* Pointer on follow-up blocks */
#followupCalendar .fc-event,
#followupCalendar .fc-event-main,
#followupCalendar .fc-daygrid-event,
#followupCalendar .fc-timegrid-event,
#followupCalendar .fc-list-event,
#followupCalendar .fc-popover .fc-event,
#followupCalendar .fc-event-container .fc-h-event {
	cursor: pointer;
}
.fc-more-popover {
	overflow-y: scroll;
	max-height: 50%;
	max-width: auto;
}
</style>
<div class="main-content">
	<section class="section">
		<div class="section-body">
			<div class="server-error">
				@include('../Elements/flash-message')
			</div>
			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
							<h4 class="mb-0">{{ $consultantLabel }}</h4>
							<div class="d-flex align-items-center gap-2 flex-wrap">
								<label for="followup-status-filter" class="form-label mb-0 small text-muted">Show</label>
								<select id="followup-status-filter" class="form-select form-select-sm" style="width: auto; min-width: 11rem;" aria-label="Filter follow-ups by status">
									<option value="open" selected>Open (confirmed)</option>
									<option value="completed">Completed</option>
									<option value="cancelled">Cancelled</option>
									<option value="no_show">No show</option>
									<option value="all">All</option>
								</select>
								<a href="{{ route('followups.index') }}" class="btn btn-outline-secondary btn-sm">Back to listing</a>
							</div>
						</div>
						<div class="card-body">
							<div class="fc-overflow">
								<div id="followupCalendar"></div>
							</div>
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
document.addEventListener('DOMContentLoaded', function() {
	if (typeof window.FullCalendar === 'undefined') {
		console.error('FullCalendar v6 not loaded');
		return;
	}

	var calendarPageConsultant = @json($consultant ?? 'ankit');
	var followupsRescheduleUrl = @json($followupsRescheduleUrl ?? '');
	var followupsOutcomeUrl = @json($followupsOutcomeUrl ?? '');

	function clearModalAlert() {
		var a = document.getElementById('followup-modal-alert');
		if (!a) return;
		a.classList.add('d-none');
		a.classList.remove('alert-danger', 'alert-success', 'alert-info');
		a.textContent = '';
	}

	function showModalAlert(kind, msg) {
		var a = document.getElementById('followup-modal-alert');
		if (!a) return;
		a.classList.remove('d-none', 'alert-danger', 'alert-success', 'alert-info');
		a.classList.add(kind === 'danger' ? 'alert-danger' : (kind === 'success' ? 'alert-success' : 'alert-info'));
		a.textContent = msg || '';
	}

	function applyStatusBadges(row) {
		var el = document.getElementById('followup-status-badge');
		if (!el) return;
		el.className = 'badge rounded-pill ';
		var outcome = row.followup_outcome;
		if (!outcome) {
			el.classList.add('bg-success');
			el.textContent = 'CONFIRMED';
		} else if (outcome === 'completed') {
			el.classList.add('bg-primary');
			el.textContent = 'COMPLETED';
		} else if (outcome === 'cancelled') {
			el.classList.add('bg-danger');
			el.textContent = 'CANCELLED';
		} else if (outcome === 'no_show') {
			el.classList.add('bg-secondary');
			el.textContent = 'NO SHOW';
		} else {
			el.classList.add('bg-success');
			el.textContent = 'CONFIRMED';
		}
	}

	/** Matches reassignConsultant / rescheduleFollowup: only open notes (status === 0). */
	function isFollowupOpenForEdit(row) {
		if (!row) {
			return false;
		}
		if (row.note_status === 0 || row.note_status === '0') {
			return true;
		}
		if (row.note_status === 1 || row.note_status === '1') {
			return false;
		}
		var cs = row.calendar_status ? String(row.calendar_status) : 'confirmed';
		return cs === 'confirmed';
	}

	/**
	 * Disable reassign/reschedule for closed follow-ups so UI matches API (no surprise 422).
	 * Change-status stays available (e.g. Mark as confirmed).
	 */
	function applyOpenOnlyEditControls(row) {
		var open = isFollowupOpenForEdit(row);
		var dtInput = document.getElementById('followup-reschedule-datetime');
		var rescheduleBtn = document.getElementById('followup-reschedule-btn');
		var consultantSel = document.getElementById('followup-reassign-consultant');
		var alertEl = document.getElementById('followup-reassign-alert');
		var rescheduleHint = document.getElementById('followup-reschedule-closed-hint');
		var reassignWrap = document.getElementById('followup-reassign-panel');
		var rescheduleWrap = document.getElementById('followup-reschedule-panel');

		if (dtInput) {
			dtInput.disabled = !open;
		}
		if (rescheduleBtn) {
			rescheduleBtn.disabled = !open;
		}
		if (consultantSel) {
			if (open && followupConsultantSlugBaseline) {
				consultantSel.disabled = false;
			} else {
				consultantSel.disabled = true;
			}
		}
		if (alertEl) {
			if (!open) {
				alertEl.textContent = 'Only open (confirmed) follow-ups can change consultant. Mark as confirmed first.';
				alertEl.className = 'small mt-2 text-muted';
			} else {
				alertEl.textContent = '';
				alertEl.className = 'small mt-2 text-muted';
			}
		}
		if (rescheduleHint) {
			if (open) {
				rescheduleHint.classList.add('d-none');
				rescheduleHint.textContent = '';
			} else {
				rescheduleHint.classList.remove('d-none');
				rescheduleHint.textContent = 'Only open (confirmed) follow-ups can be rescheduled. Mark as confirmed first.';
			}
		}
		if (reassignWrap) {
			reassignWrap.classList.toggle('opacity-75', !open);
		}
		if (rescheduleWrap) {
			rescheduleWrap.classList.toggle('opacity-75', !open);
		}
	}

	function populateFollowupModal(row) {
		clearModalAlert();
		var clientLink = document.getElementById('followup-client-link');
		if (clientLink) {
			clientLink.href = row.url || '#';
			var cn = row.client_display_name || (row.name ? atob(row.name) : '');
			clientLink.textContent = cn;
			clientLink.setAttribute('title', cn || '');
		}

		setDetailText('followup-dd-ref', row.stitle || '');

		setDetailText('followup-dd-email', row.email ? atob(row.email) : '—');
		setDetailText('followup-dd-phone', row.phone ? atob(row.phone) : '—');
		setDetailText('followup-dd-datetime', row.date_pretty || row.followup_date || '—');

		setDetailText('followup-dd-meeting', row.channel_short || '—');
		setDetailText('followup-dd-lang', row.preferred_language || '—');

		applyStatusBadges(row);

		var detailsOnlyEl = document.getElementById('appt-details-only');
		if (detailsOnlyEl) {
			var d = row.details_plain;
			detailsOnlyEl.textContent = (d !== undefined && d !== null && String(d).trim() !== '') ? String(d).trim() : '—';
		}

		var dtInput = document.getElementById('followup-reschedule-datetime');
		if (dtInput && row.datetime_local) {
			dtInput.value = row.datetime_local;
		}

		var noteField = document.getElementById('followup-reassign-note-id');
		if (noteField) noteField.value = String(row.id);

		var consultantSel = document.getElementById('followup-reassign-consultant');
		var alertEl = document.getElementById('followup-reassign-alert');
		followupConsultantSlugBaseline = row.consultant_slug ? String(row.consultant_slug) : '';
		if (alertEl) {
			alertEl.textContent = '';
			alertEl.className = 'small mt-2 text-muted';
		}

		if (consultantSel) {
			if (followupConsultantSlugBaseline) {
				consultantSel.value = followupConsultantSlugBaseline;
			}
		}

		applyOpenOnlyEditControls(row);
	}

	function postJsonWithRedirect(url, body, onFail) {
		var csrfMeta = document.querySelector('meta[name="csrf-token"]');
		var token = csrfMeta ? csrfMeta.getAttribute('content') : '';
		fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Accept': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
				'X-CSRF-TOKEN': token
			},
			body: JSON.stringify(body)
		})
			.then(function(r) {
				return r.text().then(function(t) {
					var obj = {};
					try { obj = t ? JSON.parse(t) : {}; } catch (e) { obj = { message: t || 'Bad response' }; }
					return { ok: r.ok, body: obj };
				});
			})
			.then(function(res) {
				if (res.body && res.body.success && res.body.redirect) {
					window.location.href = res.body.redirect;
					return;
				}
				var msg = (res.body && res.body.message) ? res.body.message : 'Request failed.';
				if (typeof onFail === 'function') onFail(msg);
				else showModalAlert('danger', msg);
			})
			.catch(function() {
				var msg = 'Network error.';
				if (typeof onFail === 'function') onFail(msg);
				else showModalAlert('danger', msg);
			});
	}

	function setDetailText(id, val) {
		var el = document.getElementById(id);
		if (!el) return;
		var s = (val !== undefined && val !== null && String(val).trim() !== '') ? String(val).trim() : '—';
		el.textContent = s;
		if (el.closest && el.closest('.followup-dl-compact')) {
			el.setAttribute('title', s === '—' ? '' : s);
		}
	}

	function escHtml(text) {
		if (text === undefined || text === null) {
			return '';
		}
		var d = document.createElement('div');
		d.textContent = String(text);
		return d.innerHTML;
	}

	var scheds = {!! json_encode($sched_res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};

	/**
	 * Build FullCalendar events. Default status filter is open (confirmed) so
	 * completed/cancelled/no_show are not mixed into the main grid; “All” restores prior view.
	 */
	function buildFollowupEvents(statusFilter) {
		var filter = statusFilter || 'open';
		var out = [];
		if (!scheds || typeof scheds !== 'object') {
			return out;
		}
		Object.keys(scheds).forEach(function(k) {
			var row = scheds[k];
			var startIso = row.start_iso || row.startdate;
			var endIso = row.end_iso || startIso;
			var cs = row.calendar_status || 'confirmed';
			if (cs !== 'confirmed' && cs !== 'completed' && cs !== 'cancelled' && cs !== 'no_show') {
				cs = 'confirmed';
			}
			if (filter !== 'all') {
				if (filter === 'open') {
					if (cs !== 'confirmed') {
						return;
					}
				} else if (cs !== filter) {
					return;
				}
			}
			out.push({
				id: String(row.id),
				title: row.client_display_name || row.stitle,
				start: startIso,
				end: endIso,
				allDay: false,
				classNames: ['fc-followup-pill-wrap', 'fc-followup-status-' + cs],
				display: 'block',
				backgroundColor: 'transparent',
				borderColor: 'transparent',
				textColor: '#ffffff',
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

	var events = buildFollowupEvents('open');

	var calendarEl = document.getElementById('followupCalendar');
	if (!calendarEl) return;

	var followupConsultantSlugBaseline = '';

	function performConsultantReassign() {
		clearModalAlert();
		var modal = document.getElementById('appointment-event-details-modal');
		var url = modal ? modal.getAttribute('data-reassign-url') : '';
		var noteIdEl = document.getElementById('followup-reassign-note-id');
		var consultantSel = document.getElementById('followup-reassign-consultant');
		var alertEl = document.getElementById('followup-reassign-alert');
		var csrfMeta = document.querySelector('meta[name="csrf-token"]');
		var token = csrfMeta ? csrfMeta.getAttribute('content') : '';

		if (!url || !noteIdEl || !consultantSel || !consultantSel.value || consultantSel.disabled) {
			return;
		}

		var rowForGuard = scheds && noteIdEl.value ? scheds[String(noteIdEl.value)] : null;
		if (rowForGuard && !isFollowupOpenForEdit(rowForGuard)) {
			if (alertEl) {
				alertEl.textContent = 'Only open (confirmed) follow-ups can change consultant. Mark as confirmed first.';
				alertEl.className = 'small mt-2 text-muted';
			}
			return;
		}

		if (alertEl) {
			alertEl.textContent = 'Updating…';
			alertEl.className = 'small mt-2 text-muted';
		}
		consultantSel.disabled = true;

		fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Accept': 'application/json',
				'X-Requested-With': 'XMLHttpRequest',
				'X-CSRF-TOKEN': token
			},
			body: JSON.stringify({
				note_id: parseInt(noteIdEl.value, 10),
				consultant: consultantSel.value
			})
		})
			.then(function(r) {
				return r.text().then(function(t) {
					var body = {};
					try {
						body = t ? JSON.parse(t) : {};
					} catch (ignore) {
						body = { message: t || 'Unexpected response' };
					}
					return { ok: r.ok, status: r.status, body: body };
				});
			})
			.then(function(res) {
				if (res.body && res.body.success && res.body.redirect) {
					window.location.href = res.body.redirect;
					return;
				}
				var msg = (res.body && res.body.message) ? res.body.message : 'Could not update consultant.';
				if (alertEl) {
					alertEl.textContent = msg;
					alertEl.className = 'small mt-2 text-danger';
				}
				consultantSel.value = followupConsultantSlugBaseline;
				consultantSel.disabled = false;
			})
			.catch(function() {
				if (alertEl) {
					alertEl.textContent = 'Network error. Try again.';
					alertEl.className = 'small mt-2 text-danger';
				}
				consultantSel.value = followupConsultantSlugBaseline;
				consultantSel.disabled = false;
			});
	}

	var consultantSelectEl = document.getElementById('followup-reassign-consultant');
	if (consultantSelectEl) {
		consultantSelectEl.addEventListener('change', function() {
			var sel = consultantSelectEl;
			if (sel.disabled || !followupConsultantSlugBaseline) {
				return;
			}
			var newSlug = sel.value;
			if (newSlug === followupConsultantSlugBaseline) {
				return;
			}
			crmConfirm('Do you want to update the consultant for this followup?').then(function (ok) {
				if (!ok) {
					sel.value = followupConsultantSlugBaseline;
					return;
				}
				clearModalAlert();
				performConsultantReassign();
			});
		});
	}

	var rescheduleBtn = document.getElementById('followup-reschedule-btn');
	if (rescheduleBtn && followupsRescheduleUrl) {
		rescheduleBtn.addEventListener('click', function() {
			var noteIdEl = document.getElementById('followup-reassign-note-id');
			var dtEl = document.getElementById('followup-reschedule-datetime');
			if (!noteIdEl || !dtEl || rescheduleBtn.disabled) {
				return;
			}
			var rowForGuard = scheds && noteIdEl.value ? scheds[String(noteIdEl.value)] : null;
			if (rowForGuard && !isFollowupOpenForEdit(rowForGuard)) {
				showModalAlert('danger', 'Only open (confirmed) follow-ups can be rescheduled. Mark as confirmed first.');
				return;
			}
			if (!dtEl.value) {
				showModalAlert('danger', 'Choose a date and time.');
				return;
			}
			clearModalAlert();
			postJsonWithRedirect(followupsRescheduleUrl, {
				note_id: parseInt(noteIdEl.value, 10),
				followup_datetime: dtEl.value.length === 16 ? (dtEl.value + ':00') : dtEl.value,
				calendar_consultant: calendarPageConsultant
			});
		});
	}

	document.querySelectorAll('.followup-outcome-btn').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var outcome = btn.getAttribute('data-outcome');
			var noteIdEl = document.getElementById('followup-reassign-note-id');
			if (!noteIdEl || !followupsOutcomeUrl || !outcome) return;
			clearModalAlert();
			postJsonWithRedirect(followupsOutcomeUrl, {
				note_id: parseInt(noteIdEl.value, 10),
				outcome: outcome,
				calendar_consultant: calendarPageConsultant
			});
		});
	});

	var calendar = new window.FullCalendar.Calendar(calendarEl, {
		height: "auto",
		initialView: "dayGridMonth",
		editable: false,
		selectable: false,
		dayMaxEvents: true,
		moreLinkText: "More",
		plugins: [
			window.FullCalendar.dayGridPlugin,
			window.FullCalendar.timeGridPlugin,
			window.FullCalendar.listPlugin,
			window.FullCalendar.interactionPlugin
		],
		headerToolbar: {
			left: "prev,next today",
			center: "title",
			right: "dayGridMonth,timeGridWeek,timeGridDay,listMonth",
		},
		events: events,
		eventContent: function(arg) {
			var props = arg.event.extendedProps || {};
			var time = props.timeLabel || '';
			var name = props.clientName || arg.event.title || '';
			var right = escHtml(name);
			return {
				html: '<div class="followup-fc-pill">' +
					'<span class="followup-fc-time">' + escHtml(time) + '</span>' +
					'<span class="followup-fc-meta">' + right + '</span>' +
					'</div>'
			};
		},
		eventDidMount: function(info) {
			var props = info.event.extendedProps || {};
			var time = props.timeLabel || '';
			var name = props.clientName || info.event.title || '';
			var full = [time, name].filter(Boolean).join(' ');
			var statusLabels = {
				confirmed: 'Confirmed',
				completed: 'Completed',
				cancelled: 'Cancelled',
				no_show: 'No show'
			};
			var st = props.calendarStatus || 'confirmed';
			var slab = statusLabels[st];
			if (slab) {
				full += full ? (' — ' + slab) : slab;
			}
			if (full) {
				info.el.setAttribute('title', full);
			}
		},
		eventClick: function(info) {
			var details = document.getElementById('appointment-event-details-modal');
			if (!details) return;
			var id = String(info.event.id);
			if (!scheds[id]) {
				alert('Event is undefined');
				return;
			}
			populateFollowupModal(scheds[id]);
			bootstrap.Modal.getOrCreateInstance(details).show();
		}
	});

	calendar.render();

	var statusFilterEl = document.getElementById('followup-status-filter');
	if (statusFilterEl) {
		statusFilterEl.addEventListener('change', function() {
			var selected = statusFilterEl.value || 'open';
			calendar.removeAllEvents();
			calendar.addEventSource(buildFollowupEvents(selected));
		});
	}
});
</script>

<div class="modal fade followup-detail-modal" tabindex="-1" data-bs-backdrop="true" data-bs-keyboard="true" id="appointment-event-details-modal" data-reassign-url="{{ $followupsReassignUrl ?? '' }}" data-reschedule-url="{{ $followupsRescheduleUrl ?? '' }}" data-outcome-url="{{ $followupsOutcomeUrl ?? '' }}">
	<div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
		<div class="modal-content border-0 shadow">
			<div class="modal-header border-bottom py-2 px-4">
				<h5 class="modal-title fw-bold text-dark mb-0">Followup Details</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body px-3 px-md-4 py-3">
				<div id="followup-modal-alert" class="alert d-none py-2 px-3 small mb-3" role="alert"></div>

				<div class="row g-3">
					<div class="col-lg-6 border-end-lg pe-lg-4">
						<h6 class="followup-section-label">@icon('user', 'regular', ['class' => 'me-2 text-primary'])Client detail</h6>
						<dl class="followup-dl followup-dl-compact mb-0">
							<div class="followup-dl-row">
								<dt>Client</dt>
								<dd><a href="#" id="followup-client-link" target="_blank" rel="noopener noreferrer"></a></dd>
							</div>
							<div class="followup-dl-row">
								<dt>Email</dt>
								<dd id="followup-dd-email"></dd>
							</div>
							<div class="followup-dl-row">
								<dt>Phone</dt>
								<dd id="followup-dd-phone"></dd>
							</div>
							<div class="followup-dl-row">
								<dt>Client reference</dt>
								<dd class="small text-muted" id="followup-dd-ref"></dd>
							</div>
						</dl>
					</div>
					<div class="col-lg-6 ps-lg-4">
						<h6 class="followup-section-label">@icon('calendar-check', 'regular', ['class' => 'me-2 text-primary'])Followup details</h6>
						<dl class="followup-dl followup-dl-compact mb-0">
							<div class="followup-dl-row">
								<dt>Date &amp; time</dt>
								<dd class="fw-semibold" id="followup-dd-datetime"></dd>
							</div>
							<div class="followup-dl-row">
								<dt>Meeting type</dt>
								<dd id="followup-dd-meeting"></dd>
							</div>
							<div class="followup-dl-row">
								<dt>Preferred language</dt>
								<dd id="followup-dd-lang"></dd>
							</div>
							<div class="followup-dl-row">
								<dt>Status</dt>
								<dd class="followup-dl-dd-badge mb-0"><span id="followup-status-badge" class="badge rounded-pill bg-success">CONFIRMED</span></dd>
							</div>
						</dl>
					</div>
				</div>

				<hr class="my-3 text-muted">

				<div id="followup-reschedule-panel">
					<h6 class="followup-section-label">@icon('calendar-alt', 'regular', ['class' => 'me-2 text-primary'])Reschedule date &amp; time</h6>
					<div class="row g-2 g-md-3 align-items-end flex-wrap">
						<div class="col-md-6 col-lg-5">
							<label class="form-label small text-muted mb-1" for="followup-reschedule-datetime">Appointment date &amp; time</label>
							<input type="datetime-local" class="form-control form-control-sm" id="followup-reschedule-datetime" step="60">
						</div>
						<div class="col-md-auto">
							<button type="button" class="btn btn-sm btn-primary" id="followup-reschedule-btn">@icon('save', 'regular', ['class' => 'me-1']) Update date &amp; time</button>
						</div>
					</div>
					<p id="followup-reschedule-closed-hint" class="small text-muted mt-2 mb-0 d-none" role="status"></p>
				</div>

				<hr class="my-3 text-muted">

				<div class="row g-3 align-items-start">
					<div class="col-lg-6">
						<h6 class="followup-section-label">@icon('edit', 'solid', ['class' => 'me-2 text-primary'])Change status</h6>
						<div class="list-group followup-outcome-list border-0 mb-0">
							<button type="button" class="list-group-item list-group-item-action followup-outcome-btn followup-outcome-confirmed" data-outcome="confirmed">@icon('check-circle', 'solid', ['class' => 'me-2'])Mark as confirmed</button>
							<button type="button" class="list-group-item list-group-item-action followup-outcome-btn followup-outcome-completed" data-outcome="completed">@icon('check-double', 'solid', ['class' => 'me-2'])Mark as complete</button>
							<button type="button" class="list-group-item list-group-item-action followup-outcome-btn followup-outcome-cancelled" data-outcome="cancelled">@icon('times-circle', 'solid', ['class' => 'me-2'])Mark as cancelled</button>
						</div>
					</div>
					<div class="col-lg-6" id="followup-reassign-panel">
						<h6 class="followup-section-label">@icon('exchange-alt', 'solid', ['class' => 'me-2 text-primary'])Change consultant</h6>
						<div class="followup-consultant-panel border rounded-3 p-3 bg-light">
							<input type="hidden" id="followup-reassign-note-id" value="">
							@if(($followupConsultants ?? collect())->isNotEmpty())
								<label class="visually-hidden" for="followup-reassign-consultant">Consultant</label>
								<select id="followup-reassign-consultant" class="form-select form-select-sm" autocomplete="off" aria-label="Change consultant">
									@foreach ($followupConsultants as $fuConsultant)
										@php
											$displayName = preg_replace('/\s+Calendar$/u', '', $fuConsultant->name);
										@endphp
										<option value="{{ $fuConsultant->slug }}">{{ $displayName }}</option>
									@endforeach
								</select>
							@else
								<p class="text-muted small mb-0">No consultants configured.</p>
							@endif
							<div id="followup-reassign-alert" class="small mt-2 text-muted" role="status"></div>
						</div>
					</div>
				</div>

				<hr class="my-3 text-muted">

				<h6 class="followup-section-label mb-2">@icon('file-alt', 'regular', ['class' => 'me-2 text-primary'])Followup details</h6>
				<div id="appt-details-only" class="border rounded-3 p-2 p-md-3 bg-light small text-dark" style="white-space:pre-wrap;min-height:56px;"></div>
			</div>
			<div class="modal-footer border-top bg-light py-3 px-4 justify-content-end">
				<button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>
@endsection
