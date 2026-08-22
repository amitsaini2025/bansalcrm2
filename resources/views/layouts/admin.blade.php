<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge"> 
	<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
	<meta name="description" content="">
	<meta name="author" content="">
	<meta name="keyword" content="Bansal CRM">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<meta name="current-user-id" content="{{ optional(auth('admin')->user())->id }}"> 
	<meta http-equiv="Content-Security-Policy" content="script-src 'self' 'unsafe-inline' 'unsafe-eval' https: http://localhost:5173 http://127.0.0.1:5173 http://localhost:5174 http://127.0.0.1:5174 ws://localhost:5173 ws://127.0.0.1:5173 ws://localhost:5174 ws://127.0.0.1:5174; script-src-attr 'unsafe-inline' 'unsafe-hashes'; script-src-elem 'self' 'unsafe-inline' 'unsafe-eval' https: http://localhost:5173 http://127.0.0.1:5173 http://localhost:5174 http://127.0.0.1:5174 ws://localhost:5173 ws://127.0.0.1:5173 ws://localhost:5174 ws://127.0.0.1:5174; style-src 'self' 'unsafe-inline' https: http://localhost:5173 http://127.0.0.1:5173 http://localhost:5174 http://127.0.0.1:5174; connect-src 'self' ws://localhost:5173 ws://127.0.0.1:5173 ws://localhost:5174 ws://127.0.0.1:5174 http://localhost:5173 http://127.0.0.1:5173 http://localhost:5174 http://127.0.0.1:5174 https://maps.googleapis.com;">
	<!-- Note: IPv6 literals [::1] are NOT supported by CSP spec. Use 'localhost' which resolves to both IPv4 and IPv6. -->
	<title>Bansal CRM | @yield('title')</title>
	
	<!-- jQuery 3.7.1 — single source (Phase 2a: sync in head; do not also load via Vite) -->
	<script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>
	
	<!-- DataTables: bundled via Vite (vendor-libs.js, Phase 2c) -->

	<link rel="icon" type="image/png" href="{{asset('img/favicon.png')}}">
 	<!-- CSS for libraries now loaded via Vite (vendor-libs.js): iziToast, flatpickr, Tom Select, DataTables -->
	<!-- FullCalendar v6 CSS is now loaded automatically via JavaScript -->
	<!-- TinyMCE - No CSS needed -->
	<!-- Vendor CSS now loaded via Vite (vendor-libs.js) -->
	<link rel="dns-prefetch" href="https://fonts.gstatic.com">
	<link href="https://fonts.googleapis.com/css?family=Nunito:300,400,400i,600,700,800" rel="stylesheet">
	<!-- Bootstrap CSS in head (prevents header FOUC); Bootstrap JS still loaded via app.js -->
	@vite(['resources/sass/app.scss'])
	<!-- Vendor CSS + JS (flatpickr, iziToast, Tom Select, DataTables) — CSS in head prevents FOUC -->
	@vite(['resources/js/vendor-libs.js'])
  
	<link rel="stylesheet" href="{{asset('css/style.css')}}">
  
	<link rel="stylesheet" href="{{asset('css/components.css')}}">
	<!-- Custom style CSS -->
	<link rel="stylesheet" href="{{ asset('css/custom.css') }}?v={{ (config('app.asset_version') ? config('app.asset_version').'-' : '') . filemtime(public_path('css/custom.css')) }}">
	<!-- Lucide icons (via IconHelper / crm-icon.js) — after theme CSS so sidebar/nav overrides win -->
	@include('Elements.font-awesome-styles')
	<link rel="stylesheet" href="{{ asset('css/tomselect-bridge.css') }}?v={{ (config('app.asset_version') ? config('app.asset_version').'-' : '') . filemtime(public_path('css/tomselect-bridge.css')) }}">
	<!-- Modern Search CSS -->
	<link rel="stylesheet" href="{{ asset('css/modern-search.css') }}?v={{ (config('app.asset_version') ? config('app.asset_version').'-' : '') . filemtime(public_path('css/modern-search.css')) }}">
	
    @stack('styles')

<style>
.dropbtn {
  background-color: transparent;
 border:0;
}
.ts-result-row__statistics,
.ts-result-row__stats-wrap .badge {
  font-size: 0.75rem;
}
.dropbtn:hover, .dropbtn:focus {
  background-color: transparent;
   border:0;
}

.mydropdown {
  position: relative;
  display: inline-block;
}

.dropdown-content {
  display: none;
  position: absolute;
  background-color: #fff;
  min-width: 160px;
  overflow: auto;
  box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
  z-index: 1;
}

.dropdown-content a {
  color: black;
  padding: 12px 16px;
  text-decoration: none;
  display: block;
}

.mydropdown a:hover {background-color: #ddd;}

.show {display: block;}

/* Improved Color Contrast for Icons and Text - Better Readability */
/* Navbar Icons - Header Navigation */
.navbar .nav-link,
.navbar .nav-link-lg,
.navbar .nav-link i,
.navbar .nav-link-lg i,
.navbar .nav-link .crm-icon,
.navbar .nav-link svg.crm-icon,
.navbar .nav-link svg.lucide,
.navbar .nav-link-lg .crm-icon,
.navbar .nav-link-lg svg.crm-icon,
.navbar .nav-link-lg svg.lucide {
    color: #2d3748 !important; /* Dark gray for better contrast */
}

.navbar .nav-link:hover,
.navbar .nav-link-lg:hover,
.navbar .nav-link:hover i,
.navbar .nav-link-lg:hover i,
.navbar .nav-link:hover .crm-icon,
.navbar .nav-link:hover svg.crm-icon,
.navbar .nav-link:hover svg.lucide,
.navbar .nav-link-lg:hover .crm-icon,
.navbar .nav-link-lg:hover svg.crm-icon,
.navbar .nav-link-lg:hover svg.lucide {
    color: #1a202c !important; /* Even darker on hover */
}

/* Specific icon improvements */
.navbar .collapse-btn i,
.navbar .collapse-btn .crm-icon,
.navbar .collapse-btn svg.crm-icon,
.navbar .fullscreen-btn i,
.navbar .fullscreen-btn .crm-icon,
.navbar .fullscreen-btn svg.crm-icon,
.navbar .message-toggle i,
.navbar .message-toggle .crm-icon,
.navbar .message-toggle svg.crm-icon,
.navbar .notification-toggle i,
.navbar .notification-toggle .crm-icon,
.navbar .notification-toggle svg.crm-icon,
.navbar .opencheckin i,
.navbar .opencheckin .crm-icon,
.navbar .opencheckin svg.crm-icon {
    color: #2d3748 !important;
    opacity: 1 !important;
}

.navbar .collapse-btn:hover i,
.navbar .collapse-btn:hover .crm-icon,
.navbar .collapse-btn:hover svg.crm-icon,
.navbar .fullscreen-btn:hover i,
.navbar .fullscreen-btn:hover .crm-icon,
.navbar .fullscreen-btn:hover svg.crm-icon,
.navbar .message-toggle:hover i,
.navbar .message-toggle:hover .crm-icon,
.navbar .message-toggle:hover svg.crm-icon,
.navbar .notification-toggle:hover i,
.navbar .notification-toggle:hover .crm-icon,
.navbar .notification-toggle:hover svg.crm-icon,
.navbar .opencheckin:hover i,
.navbar .opencheckin:hover .crm-icon,
.navbar .opencheckin:hover svg.crm-icon {
    color: #1a202c !important;
}

/* Search Element - Improved Contrast */
.search-element .btn,
.search-element .btn i,
.search-element .btn .crm-icon,
.search-element .btn svg.crm-icon {
    color: #2d3748 !important;
}

.search-element .btn:hover,
.search-element .btn:hover i,
.search-element .btn:hover .crm-icon,
.search-element .btn:hover svg.crm-icon {
    color: #1a202c !important;
}

.search-element .form-control,
.search-element .ts-control,
.search-element .ts-wrapper .ts-control input {
    color: #1a202c !important;
}

/* Dropdown Elements */
.dropdown-toggle,
.dropdown-toggle i {
    color: #2d3748 !important;
}

.dropdown-toggle:hover,
.dropdown-toggle:hover i {
    color: #1a202c !important;
}

/* Sidebar Menu Items */
.sidebar-menu .nav-link,
.sidebar-menu .nav-link i,
.sidebar-menu .nav-link .crm-icon,
.sidebar-menu .nav-link svg.crm-icon,
.sidebar-menu .nav-link svg.lucide,
.sidebar-menu .nav-link span {
    color: #2d3748 !important;
}

.sidebar-menu .nav-link:hover,
.sidebar-menu .nav-link:hover i,
.sidebar-menu .nav-link:hover .crm-icon,
.sidebar-menu .nav-link:hover svg.crm-icon,
.sidebar-menu .nav-link:hover svg.lucide,
.sidebar-menu .nav-link:hover span {
    color: #1a202c !important;
}

/* Normal weight for non-active items (theme style.css sets font-weight: 600 on all li a) */
.main-sidebar .sidebar-menu > li:not(.active) > a.nav-link,
.main-sidebar .sidebar-menu > li:not(.active) > a.nav-link span,
.main-sidebar .sidebar-menu li ul.dropdown-menu li:not(.active) > a.nav-link,
.main-sidebar .sidebar-menu li ul.dropdown-menu li:not(.active) > a.nav-link span {
    font-weight: 400 !important;
}

.sidebar-menu .active .nav-link,
.sidebar-menu .active .nav-link i,
.sidebar-menu .active .nav-link .crm-icon,
.sidebar-menu .active .nav-link svg.crm-icon,
.sidebar-menu .active .nav-link svg.lucide,
.sidebar-menu .active .nav-link span {
    color: #1a202c !important;
}

.main-sidebar .sidebar-menu li.active > a.nav-link,
.main-sidebar .sidebar-menu li.active > a.nav-link span {
    font-weight: 600 !important;
}

/* Menu Header Text */
.sidebar-menu .menu-header {
    color: #4a5568 !important;
    font-weight: 700 !important;
}

/* Dropdown Menu Items */
.dropdown-menu a,
.dropdown-menu .dropdown-item {
    color: #2d3748 !important;
}

.dropdown-menu a:hover,
.dropdown-menu .dropdown-item:hover {
    color: #1a202c !important;
    background-color: #f7fafc !important;
}

/* User Profile Dropdown */
.nav-link-user,
.nav-link-user span {
    color: #2d3748 !important;
}

/* Bell Icon with Badge */
.bell,
.bell i {
    color: #2d3748 !important;
}

/* Tom Select dropdown text */
.search-element .ts-wrapper .ts-control {
    color: #1a202c !important;
}

.search-element .ts-wrapper.single .ts-control > input::placeholder {
    color: #718096 !important;
}

/* Form Controls Text */
.form-control,
.form-control::placeholder {
    color: #1a202c !important;
}

.form-control::placeholder {
    color: #718096 !important;
    opacity: 1 !important;
}

/* Button Text */
.btn:not(.btn-primary):not(.btn-success):not(.btn-danger):not(.btn-warning):not(.btn-info) {
    color: #2d3748 !important;
}

/* General Text Improvements */
.text-muted {
    color: #4a5568 !important;
}

/* Ensure all Font Awesome icons have good contrast */
.fas,
.far,
.fa,
[class^="fa-"],
[class*=" fa-"] {
    color: inherit;
}

/* Override any light gray icon colors */
i[style*="color: #"],
i[style*="color:#"],
i[style*="color: rgb"],
i[style*="color:rgba"] {
    color: #2d3748 !important;
}
</style>
</head>
<body >
	<div class="loader"></div>
		<div class="popuploader" style="display: none;"></div>
	<div id="app">
		<div class="main-wrapper main-wrapper-1">
			<div class="navbar-bg"></div>
			<!--Header-->
			@include('../Elements/Admin/header')
			<!--Left Side Bar-->
			@include('../Elements/Admin/left-side-bar')

			@yield('content')
				
			@include('../Elements/AdminConsole/footer')
		</div>
	</div>

   

		<?php if(@Settings::sitedata('date_format') != 'none'){
			   $date_format = @Settings::sitedata('date_format');
			 //  $time_format = @Settings::sitedata('time_format');
			 if($date_format == 'd/m/Y'){
			     $dataformat =  'DD/MM/YYYY';
			 }else if($date_format == 'm/d/Y'){
			     $dataformat =  'MM/DD/YYYY';
			 }else if($date_format == 'Y-m-d'){
		    	 $dataformat = 'YYYY-MM-DD';
			 }else{
			    $dataformat = 'YYYY-MM-DD';
			 }
			}else{
			  $dataformat = 'YYYY-MM-DD';
			}
			?>
				<script>
				    var site_url = '{{URL::to('/')}}';
				     var dataformat = '{{$dataformat}}';
				    </script>
	
	<!-- Load FullCalendar v6 -->
	@vite(['resources/js/fullcalendar-init.js'])

	<!-- Bootstrap / app JS (vendor-libs loaded in <head>) -->
	@vite(['resources/js/app.js'])

	<!-- TinyMCE scripts loaded conditionally via @push('tinymce-scripts') on pages that need it -->
	@stack('tinymce-scripts')
	@if(request()->is('agent', 'agent/*'))
	@vite(['resources/js/agent-layout-scripts.js'])
	@else
	@vite(['resources/js/admin-layout-scripts.js'])
	@endif

	<!-- Legacy initialization now loaded via Vite (legacy-init.js) -->
	@vite(['resources/js/legacy-init.js'])
	
	<div id="checkinmodal"  data-backdrop="static" data-keyboard="false" class="modal fade custom_modal" tabindex="-1" role="dialog" aria-labelledby="clientModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="clientModalLabel">Create In Person Client</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form method="post" name="checkinmodalsave" id="checkinmodalsave" action="{{URL::to('/checkin')}}" autocomplete="off" enctype="multipart/form-data">
				@csrf
			
					<div class="row">
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="email_from">Search Contact <span class="span_req">*</span></label>
								<select data-valid="required" class="js-data-example-ajax-check" name="contact"></select>
								@if ($errors->has('email_from'))
									<span class="custom-error" role="alert">
										<strong>{{ @$errors->first('email_from') }}</strong>
									</span> 
								@endif
							</div> 
						</div>
						<input type="hidden" id="utype" name="utype" value="">
						<div class="col-12 col-md-6 col-lg-6">
							<div class="form-group">
								<label for="email_from">Office <span class="span_req">*</span></label>
								<select data-valid="required" class="form-control" name="office">
									<option value="">Select</option>
									@foreach(\App\Models\Branch::all() as $of)
										<option value="{{$of->id}}">{{$of->office_name}}</option>
									@endforeach
								</select>
								
							</div>
						</div>
						
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="message">Visit Purpose <span class="span_req">*</span></label>
								<textarea class="form-control" name="message"></textarea>
								@if ($errors->has('message'))
									<span class="custom-error" role="alert">
										<strong>{{ @$errors->first('message') }}</strong>
									</span>  
								@endif
							</div>
						</div>
						
						<div class="col-12 col-md-12 col-lg-12">
							<div class="form-group">
								<label for="message">Select In Person Assignee <span class="span_req">*</span></label>
								<?php
								$assignee = \App\Models\Staff::all();
								?>
								<select class="form-control checkin-assignee-tomselect tomselect" name="assignee">
								@foreach($assignee as $assigne)
									<option value="{{$assigne->id}}">{{$assigne->first_name}} ({{$assigne->email}})</option>
								@endforeach
								</select>
								@if ($errors->has('message'))
									<span class="custom-error" role="alert">
										<strong>{{ @$errors->first('message') }}</strong>
									</span>  
								@endif
							</div>
						</div>
						<div class="col-12 col-md-12 col-lg-12">
							<div class="checkin-modal-actions">
								<button onclick="customValidate('checkinmodalsave')" type="button" class="btn btn-primary">Send</button>
								<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

@auth('admin')
@include('Elements.crm-access-request-modal')
@endauth

<div id="checkindetailmodal"  data-backdrop="static" data-keyboard="false" class="modal fade custom_modal" tabindex="-1" role="dialog" aria-labelledby="clientModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="clientModalLabel">In Person Details</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body showchecindetail">
				
			</div>
		</div>
	</div>
</div>
	<!-- Teams-style office visit notification popups -->
	@auth('admin')
	<div class="teams-notification-container" id="teamsNotificationContainer" aria-label="Office visit notifications"></div>
	<style>
		.teams-notification-container { position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column-reverse; gap: 12px; max-width: 360px; }
		.teams-notification-card { background: #fff; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,.15); border-left: 4px solid #1f1655; overflow: hidden; animation: teamsNotificationIn 0.25s ease; }
		@keyframes teamsNotificationIn { from { opacity: 0; transform: translateX(100%); } to { opacity: 1; transform: translateX(0); } }
		.teams-notification-card.minimized { max-height: 48px; }
		.teams-notification-card.minimized .teams-notification-body { display: none; }
		.teams-notification-header { padding: 10px 12px; background: #f8f9fa; display: flex; justify-content: space-between; align-items: center; cursor: pointer; }
		.teams-notification-header h6 { margin: 0; font-size: 14px; font-weight: 600; color: #1f1655; }
		.teams-notification-actions { display: flex; gap: 4px; }
		.teams-notification-actions button { padding: 2px 8px; font-size: 12px; border: none; border-radius: 4px; cursor: pointer; }
		.teams-notification-body { padding: 12px; font-size: 13px; }
		.teams-notification-body p { margin: 0 0 6px; }
		.teams-notification-body .btn-group { margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap; }
		.teams-notification-body .btn-group .btn { font-size: 12px; padding: 6px 12px; }
	</style>
	<script>
	(function() {
		var container = document.getElementById('teamsNotificationContainer');
		if (!container) return;
		var currentUserId = document.querySelector('meta[name="current-user-id"]');
		currentUserId = currentUserId ? (currentUserId.getAttribute('content') || '').trim() : '';
		var baseUrl = document.querySelector('script[data-site-url]') ? document.querySelector('script[data-site-url]').getAttribute('data-site-url') : (typeof site_url !== 'undefined' ? site_url : '');
		var fetchUrl = '{{ url("/fetch-office-visit-notifications") }}';
		var checkStatusUrl = '{{ url("/check-checkin-status") }}';
		var attendSessionUrl = '{{ url("/attend_session") }}';
		var markSeenUrl = '{{ url("/mark-notification-seen") }}';
		var updateStatusUrl = '{{ url("/update-checkin-status") }}';
		var csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
		var pollIntervalMs = 4000;
		var defaultDetailsUrl = (baseUrl || '') + '/office-visits/waiting';

		// N-4: escape untrusted text before innerHTML (XSS-safe display)
		function escapeHtml(value) {
			return String(value == null ? '' : value)
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#39;');
		}

		// Allow only same-app http(s) paths; reject javascript: / data: / open redirects
		function safeNotificationUrl(rawUrl) {
			var fallback = defaultDetailsUrl;
			if (!rawUrl || typeof rawUrl !== 'string') {
				return fallback;
			}
			var url = rawUrl.trim();
			if (!url) {
				return fallback;
			}
			// Absolute path on this host (subdirectory-safe relative paths)
			if (url.charAt(0) === '/' && url.charAt(1) !== '/') {
				return url;
			}
			try {
				var parsed = new URL(url, window.location.origin);
				if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
					return fallback;
				}
				if (parsed.origin !== window.location.origin) {
					return fallback;
				}
				return parsed.pathname + parsed.search + parsed.hash;
			} catch (e) {
				return fallback;
			}
		}

		function showTeamsNotification(notification) {
			if (!notification || !notification.id) return;
			var id = 'ov-notif-' + notification.id;
			if (document.getElementById(id)) return;
			var isReception = !!notification.is_reception_alert;
			var card = document.createElement('div');
			card.className = 'teams-notification-card';
			card.id = id;
			card.setAttribute('data-notification-id', notification.id);
			card.setAttribute('data-checkin-id', notification.checkin_id || '');
			var headerTitle = escapeHtml(notification.popup_title || 'Office Visit Assignment');
			var primaryBtn = '';
			if (isReception) {
				primaryBtn = '<button type="button" class="btn btn-success teams-notification-primary" data-waitingtype="1">Client Sent</button>';
			} else if (notification.show_pls_send_button !== false) {
				primaryBtn = '<button type="button" class="btn btn-success teams-notification-primary" data-waitingtype="0">Pls Send The Client</button>';
			}
			var detailsHref = escapeHtml(safeNotificationUrl(notification.url));
			card.innerHTML =
				'<div class="teams-notification-header">' +
					'<h6>' + headerTitle + '</h6>' +
					'<div class="teams-notification-actions">' +
						'<button type="button" class="btn btn-sm btn-outline-secondary teams-notification-minimize" aria-label="Minimize">−</button>' +
						'<button type="button" class="btn btn-sm btn-outline-secondary teams-notification-close" aria-label="Close">×</button>' +
					'</div>' +
				'</div>' +
				'<div class="teams-notification-body">' +
					'<p><strong>' + escapeHtml(notification.sender_name) + '</strong></p>' +
					'<p>' + escapeHtml(notification.message) + '</p>' +
					'<p>Client: ' + escapeHtml(notification.client_name) + '</p>' +
					'<p>Purpose: ' + escapeHtml(notification.visit_purpose) + '</p>' +
					'<p>Time: ' + escapeHtml(notification.created_at) + '</p>' +
					'<div class="btn-group">' +
						primaryBtn +
						'<a href="' + detailsHref + '" class="btn btn-outline-primary">View Details</a>' +
					'</div>' +
				'</div>';
			container.appendChild(card);

			card.querySelector('.teams-notification-close').addEventListener('click', function() { closeNotification(notification.id); });
			card.querySelector('.teams-notification-minimize').addEventListener('click', function() { card.classList.toggle('minimized'); });
			var primaryEl = card.querySelector('.teams-notification-primary');
			if (primaryEl) {
				primaryEl.addEventListener('click', function() {
					var waitingtype = parseInt(primaryEl.getAttribute('data-waitingtype') || '0', 10);
					handlePrimaryAction(notification.checkin_id, notification.id, card, isReception, waitingtype);
				});
			}

			if (notification.checkin_id && checkStatusUrl) {
				var statusCheckInterval = setInterval(function() {
					fetch(checkStatusUrl + '?checkin_id=' + encodeURIComponent(notification.checkin_id), {
						headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
						credentials: 'same-origin'
					})
						.then(function(r) { return r.json(); })
						.then(function(response) {
							if (response && response.success && response.status !== 0) {
								clearInterval(statusCheckInterval);
								closeNotification(notification.id);
							}
						})
						.catch(function() {});
				}, pollIntervalMs);
			}
		}

		window.showTeamsNotification = showTeamsNotification;

		function handlePrimaryAction(checkinId, notificationId, cardEl, isReception, waitingtype) {
			if (!checkinId) return;
			if (cardEl) cardEl.remove();
			if (!csrfToken) return;

			if (isReception && attendSessionUrl) {
				var body = new URLSearchParams();
				body.append('id', checkinId);
				body.append('waitingtype', String(waitingtype));
				body.append('waitcountdata', '00h:00m:00s');
				body.append('_token', csrfToken);
				fetch(attendSessionUrl, {
					method: 'POST',
					headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
					body: body.toString(),
					credentials: 'same-origin'
				}).then(function() {
					if (notificationId) markNotificationSeen(notificationId);
				}).catch(function() {});
				return;
			}

			if (updateStatusUrl) {
				fetch(updateStatusUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
					body: JSON.stringify({ checkin_id: checkinId, notification_id: notificationId, status: 0, wait_type: 1 })
				}).then(function(r) { return r.json(); }).then(function() {
					if (notificationId) markNotificationSeen(notificationId);
				}).catch(function() {});
			}
		}

		function markNotificationSeen(notificationId) {
			if (!notificationId || !csrfToken || !markSeenUrl) return;
			fetch(markSeenUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: JSON.stringify({ notification_id: notificationId }) });
		}

		function closeNotification(notificationId) {
			var el = document.getElementById('ov-notif-' + notificationId);
			if (el) el.remove();
			markNotificationSeen(notificationId);
		}

		function attendSession(checkinId, notificationId, cardEl) {
			handlePrimaryAction(checkinId, notificationId, cardEl, false, 0);
		}

		function loadOfficeVisitNotifications() {
			if (!fetchUrl) return;
			fetch(fetchUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
				.then(function(r) { return r.json(); })
				.then(function(data) {
					if (data.notifications && data.notifications.length) {
						data.notifications.forEach(function(n) { showTeamsNotification(n); });
					}
				})
				.catch(function() {});
		}

		function pollOfficeVisitNotifications() {
			if (document.visibilityState === 'hidden') return;
			loadOfficeVisitNotifications();
		}

		function setupOfficeVisitRealtimeNotifications() {
			if (typeof window.Echo === 'undefined' || !currentUserId) return;
			window.Echo.private('user.' + currentUserId)
				.listen('.OfficeVisitNotificationCreated', function(e) {
					if (e && e.notification) showTeamsNotification(e.notification);
				});
		}

		loadOfficeVisitNotifications();
		setInterval(pollOfficeVisitNotifications, pollIntervalMs);
		document.addEventListener('visibilitychange', function() {
			if (document.visibilityState === 'visible') pollOfficeVisitNotifications();
		});
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', setupOfficeVisitRealtimeNotifications);
		} else {
			setupOfficeVisitRealtimeNotifications();
		}
	})();
	</script>
	@endauth

	@include('partials.email-from-ses-script')
	<script src="{{ asset('js/components/task-description-mentions.js') }}?v={{ @filemtime(public_path('js/components/task-description-mentions.js')) ?: time() }}"></script>

@stack('scripts')
@yield('scripts')
</body>
</html>