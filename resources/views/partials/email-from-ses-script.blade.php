{{-- Populate Compose Email From dropdowns with AWS SES verified senders --}}
<script>
(function() {
	var sendersUrl = '{{ route("admin.outlook.senders") }}';

	function optionValueInSelect(select, email) {
		if (!email) return false;
		var want = String(email).toLowerCase().trim();
		for (var i = 0; i < select.options.length; i++) {
			if (String(select.options[i].value || '').toLowerCase().trim() === want) {
				return select.options[i].value;
			}
		}
		return null;
	}

	/**
	 * Fill .email-from-ses selects. Prefer previous value if still valid; else API default_from.
	 */
	function populateEmailFromSelects(senders, defaultFrom) {
		var selects = document.querySelectorAll('.email-from-ses');
		if (selects.length === 0) return;

		senders = senders || [];
		defaultFrom = defaultFrom || '';

		selects.forEach(function(select) {
			var prev = select.value;
			select.innerHTML = '<option value="">Select From Email address</option>';

			if (senders.length > 0) {
				senders.forEach(function(s) {
					var opt = document.createElement('option');
					opt.value = s.email || '';
					opt.textContent = (s.name && s.name !== s.email)
						? (s.name + ' <' + s.email + '>')
						: (s.email || '');
					select.appendChild(opt);
				});
				// Keep intentional selection; only auto-pick when empty
				var matchPrev = optionValueInSelect(select, prev);
				if (matchPrev) {
					select.value = matchPrev;
				} else {
					var matchDefault = optionValueInSelect(select, defaultFrom);
					if (matchDefault) {
						select.value = matchDefault;
					}
				}
			} else {
				select.innerHTML = '<option value="">No verified senders — add active addresses in Admin Console → Emails</option>';
			}
		});
	}

	function refreshEmailFromSenders() {
		var selects = document.querySelectorAll('.email-from-ses');
		if (selects.length === 0) return;

		var tokenMeta = document.querySelector('meta[name="csrf-token"]');
		var headers = {
			'Accept': 'application/json',
			'X-Requested-With': 'XMLHttpRequest'
		};
		if (tokenMeta) {
			headers['X-CSRF-TOKEN'] = tokenMeta.getAttribute('content') || '';
		}

		fetch(sendersUrl, {
			headers: headers,
			credentials: 'same-origin'
		})
			.then(function(r) {
				if (!r.ok) throw new Error('HTTP ' + r.status);
				return r.json();
			})
			.then(function(data) {
				populateEmailFromSelects(data.senders || [], data.default_from || '');
			})
			.catch(function() {
				selects.forEach(function(select) {
					select.innerHTML = '<option value="">Could not load senders — check you are logged in and Admin Console → Emails has active addresses</option>';
				});
			});
	}

	window.refreshEmailFromSenders = refreshEmailFromSenders;

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', refreshEmailFromSenders);
	} else {
		refreshEmailFromSenders();
	}

	document.addEventListener('elite:refreshSenders', refreshEmailFromSenders);

	if (typeof jQuery !== 'undefined') {
		jQuery(document).on('shown.bs.modal', '#emailmodal', refreshEmailFromSenders);
	}
})();
</script>
