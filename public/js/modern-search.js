/**
 * Modern Search Implementation
 * Features: Debouncing, Keyboard Shortcuts, Category Grouping, Highlighting
 */

(function() {
    'use strict';

    var crmAccessModalCtx = null;

    function stripHtml(s) {
        if (!s) {
            return '';
        }
        var t = document.createElement('div');
        t.innerHTML = s;
        return t.textContent || t.innerText || '';
    }

    function getCsrfToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function siteBase() {
        return (typeof site_url !== 'undefined' ? site_url : '');
    }

    function openCrmAccessRequestModal(ctx) {
        crmAccessModalCtx = ctx;
        var modalEl = document.getElementById('crmAccessRequestModal');
        var labelEl = document.getElementById('crm-access-record-label');
        var blockedEl = document.getElementById('crm-access-modal-blocked');
        var formEl = document.getElementById('crm-access-modal-form');
        var msgEl = document.getElementById('crm-access-msg');
        var officeSel = document.getElementById('crm-access-office');
        var reasonSel = document.getElementById('crm-access-reason');
        var noteWrap = document.getElementById('crm-access-supervisor-note-wrap');
        var btnQuick = document.getElementById('crm-access-btn-quick');
        var btnSuper = document.getElementById('crm-access-btn-supervisor');

        if (!modalEl || typeof bootstrap === 'undefined') {
            window.location.href = siteBase() + '/crm/access/request/' + encodeURIComponent(ctx.adminId);
            return;
        }

        if (labelEl) {
            labelEl.textContent = ctx.displayName
                ? ('Record: ' + ctx.displayName)
                : ('Record ID #' + ctx.adminId);
        }
        if (msgEl) {
            msgEl.textContent = '';
            msgEl.className = 'small mt-2';
        }
        if (blockedEl) {
            blockedEl.classList.add('d-none');
            blockedEl.textContent = '';
        }
        if (formEl) {
            formEl.classList.remove('d-none');
        }
        if (officeSel) {
            officeSel.innerHTML = '';
        }
        if (reasonSel) {
            reasonSel.innerHTML = '';
        }
        if (noteWrap) {
            noteWrap.classList.add('d-none');
        }
        var noteTa = document.getElementById('crm-access-note');
        if (noteTa) {
            noteTa.value = '';
        }
        if (btnQuick) {
            btnQuick.classList.remove('d-none');
        }
        if (btnSuper) {
            btnSuper.classList.add('d-none');
        }

        fetch(siteBase() + '/crm/access/meta', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        }).then(function(r) {
            return r.json().then(function(j) {
                return { ok: r.ok, j: j };
            });
        }).then(function(res) {
            if (!res.ok || !res.j) {
                throw new Error('meta');
            }
            var d = res.j;
            if (officeSel && Array.isArray(d.offices)) {
                d.offices.forEach(function(o) {
                    var opt = document.createElement('option');
                    opt.value = o.id;
                    opt.textContent = o.office_name;
                    officeSel.appendChild(opt);
                });
            }
            if (reasonSel && d.reasons && typeof d.reasons === 'object') {
                Object.keys(d.reasons).forEach(function(code) {
                    var opt = document.createElement('option');
                    opt.value = code;
                    opt.textContent = d.reasons[code];
                    reasonSel.appendChild(opt);
                });
            }
            var quickOn = !!d.quick_access_enabled;
            var canSup = !!d.can_supervisor;
            if (btnQuick) {
                if (quickOn) {
                    btnQuick.classList.remove('d-none');
                } else {
                    btnQuick.classList.add('d-none');
                }
            }
            if (btnSuper) {
                if (canSup) {
                    btnSuper.classList.remove('d-none');
                } else {
                    btnSuper.classList.add('d-none');
                }
            }
            if (noteWrap) {
                if (canSup) {
                    noteWrap.classList.remove('d-none');
                }
            }
            if (!quickOn && !canSup && blockedEl && formEl) {
                blockedEl.textContent = 'Temporary access requests are not available for your account. If this seems wrong, contact a Super Admin or Admin.';
                blockedEl.classList.remove('d-none');
                formEl.classList.add('d-none');
                if (btnQuick) {
                    btnQuick.classList.add('d-none');
                }
                if (btnSuper) {
                    btnSuper.classList.add('d-none');
                }
            }
        }).catch(function() {
            if (blockedEl && formEl) {
                blockedEl.textContent = 'Unable to load access options. You can try again or open the full request page.';
                blockedEl.classList.remove('d-none');
                formEl.classList.add('d-none');
            }
            if (btnQuick) {
                btnQuick.classList.add('d-none');
            }
            if (btnSuper) {
                btnSuper.classList.add('d-none');
            }
        });

        try {
            var hdrEl = document.querySelector('.js-data-example-ajaxccsearch');
            if (hdrEl && hdrEl.tomselect) {
                hdrEl.tomselect.close();
            }
        } catch (ignoreClose) {}

        var inst = bootstrap.Modal.getOrCreateInstance(modalEl);
        inst.show();
    }

    function crmAccessPost(url, body, cb) {
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify(body)
        }).then(function(r) {
            return r.text().then(function(text) {
                var j = {};
                if (text) {
                    try {
                        j = JSON.parse(text);
                    } catch (err) {
                        j = { message: text.slice(0, 200) || 'Unexpected server response' };
                    }
                }
                if (!r.ok && j.message && j.errors) {
                    var first = j.errors && typeof j.errors === 'object' ? Object.values(j.errors)[0] : null;
                    if (Array.isArray(first) && first[0]) {
                        j.message = first[0];
                    }
                }
                return { ok: r.ok, j: j, status: r.status };
            });
        }).then(cb).catch(function() {
            cb({ ok: false, j: { message: 'Network error' } });
        });
    }

    /**
     * Ongoing / sheet views: course name links use the same Quick access modal as global search.
     */
    function wireOngoingSheetCourseLinkAccessOnce() {
        if (window.__ongoingSheetCourseGateWired) {
            return;
        }
        window.__ongoingSheetCourseGateWired = true;
        $(document).on('click', 'a.ongoing-course-link--crm-access-gate', function(e) {
            e.preventDefault();
            var el = this;
            var adminId = parseInt(el.getAttribute('data-admin-id'), 10);
            var encId = el.getAttribute('data-encoded-id') || '';
            var isLead = el.getAttribute('data-is-lead') === '1';
            var afterUrl = el.getAttribute('data-after-access-url') || '';
            var disp = el.getAttribute('data-display-name') || '';
            if (!Number.isFinite(adminId) || adminId <= 0 || !encId) {
                return;
            }
            var opener = typeof window.openCrmAccessRequestModal === 'function'
                ? window.openCrmAccessRequestModal
                : openCrmAccessRequestModal;
            if (typeof opener !== 'function') {
                if (afterUrl) {
                    window.location.href = afterUrl;
                }
                return;
            }
            opener({
                adminId: adminId,
                encodedId: encId,
                isLead: isLead,
                displayName: disp,
                afterAccessUrl: afterUrl
            });
        });
    }

    function wireCrmAccessModalOnce() {
        var q = document.getElementById('crm-access-btn-quick');
        var s = document.getElementById('crm-access-btn-supervisor');
        if (q && !q.dataset.wired) {
            q.dataset.wired = '1';
            q.addEventListener('click', function() {
                var ctx = crmAccessModalCtx;
                if (!ctx) {
                    return;
                }
                var office = document.getElementById('crm-access-office');
                var reason = document.getElementById('crm-access-reason');
                var msgEl = document.getElementById('crm-access-msg');
                var oid = parseInt(office && office.value, 10);
                var rcode = reason && reason.value ? String(reason.value).trim() : '';
                if (!Number.isFinite(oid) || oid <= 0 || !rcode) {
                    if (msgEl) {
                        msgEl.textContent = 'Choose an office and reason, then try again.';
                        msgEl.className = 'small mt-2 text-danger';
                    }
                    return;
                }
                crmAccessPost(siteBase() + '/crm/access/quick', {
                    admin_id: ctx.adminId,
                    office_id: oid,
                    reason: rcode
                }, function(res) {
                    if (res.ok && res.j && res.j.ok) {
                        if (msgEl) {
                            if (res.j.mode === 'already_can_view') {
                                msgEl.textContent = 'You already have access. Opening record…';
                            } else {
                                msgEl.textContent = 'Access granted. Opening record…';
                            }
                            msgEl.className = 'small mt-2 text-success';
                        }
                        var path = ctx.isLead ? '/leads/detail/' : '/clients/detail/';
                        setTimeout(function() {
                            var dest = ctx.afterAccessUrl;
                            if (dest && String(dest).trim() !== '') {
                                window.location.href = dest;
                            } else {
                                window.location.href = siteBase() + path + encodeURIComponent(ctx.encodedId);
                            }
                        }, 400);
                    } else if (msgEl) {
                        msgEl.textContent = (res.j && res.j.message) ? res.j.message : 'Request failed';
                        msgEl.className = 'small mt-2 text-danger';
                    }
                });
            });
        }
        if (s && !s.dataset.wired) {
            s.dataset.wired = '1';
            s.addEventListener('click', function() {
                var ctx = crmAccessModalCtx;
                if (!ctx) {
                    return;
                }
                var office = document.getElementById('crm-access-office');
                var reason = document.getElementById('crm-access-reason');
                var note = document.getElementById('crm-access-note');
                var msgEl = document.getElementById('crm-access-msg');
                var oidS = parseInt(office && office.value, 10);
                var rcodeS = reason && reason.value ? String(reason.value).trim() : '';
                if (!Number.isFinite(oidS) || oidS <= 0 || !rcodeS) {
                    if (msgEl) {
                        msgEl.textContent = 'Choose an office and reason, then try again.';
                        msgEl.className = 'small mt-2 text-danger';
                    }
                    return;
                }
                crmAccessPost(siteBase() + '/crm/access/supervisor', {
                    admin_id: ctx.adminId,
                    office_id: oidS,
                    reason: rcodeS,
                    note: note ? note.value : ''
                }, function(res) {
                    if (res.ok && res.j && res.j.ok) {
                        if (msgEl) {
                            msgEl.textContent = 'Supervisor request submitted.';
                            msgEl.className = 'small mt-2 text-success';
                        }
                    } else if (msgEl) {
                        msgEl.textContent = (res.j && res.j.message) ? res.j.message : 'Request failed';
                        msgEl.className = 'small mt-2 text-danger';
                    }
                });
            });
        }
    }

    /**
     * Quick view row: Tom Select selecting often lacks a reliable originalEvent
     * (migrationmanager2 opens the modal from select; we intercept the row via delegation).
     */
    function isTruthyLocked(v) {
        return v === true || v === 1 || v === '1' || v === 'true';
    }

    function searchResultNeedsAccessModal(repo) {
        if (!repo || repo.has_active_temp_access) {
            return false;
        }
        if (isTruthyLocked(repo.locked)) {
            return true;
        }
        return !!repo.search_selection_requires_access_modal;
    }

    function openAccessModalFromSearchRow(el) {
        var adminId = parseInt(el.getAttribute('data-admin-id'), 10);
        var encId = el.getAttribute('data-encoded-id') || '';
        var isLead = el.getAttribute('data-is-lead') === '1';
        if (!Number.isFinite(adminId) || adminId <= 0 || !encId) {
            return false;
        }
        openCrmAccessRequestModal({
            adminId: adminId,
            encodedId: encId,
            isLead: isLead,
            displayName: ''
        });
        return true;
    }

    function wireModernSearchQuickViewRowOnce() {
        if (typeof window !== 'undefined' && window.__modernSearchQuickViewWired) {
            return;
        }
        if (typeof window !== 'undefined') {
            window.__modernSearchQuickViewWired = true;
        }
        $(document).on('mousedown', '.modern-search-result-quickview', function(e) {
            e.preventDefault();
            e.stopPropagation();
        });
        $(document).on('click', '.modern-search-result-quickview', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openAccessModalFromSearchRow(this);
            return false;
        });
        // Main row (title / ID / contact): default selection would navigate; open Request access like Quick view.
        $(document).on('mousedown', '.modern-search-result--access-gate', function(e) {
            if ($(e.target).closest('.modern-search-result-quickview').length) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
        });
        $(document).on('click', '.modern-search-result--access-gate', function(e) {
            if ($(e.target).closest('.modern-search-result-quickview').length) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            openAccessModalFromSearchRow(this);
            return false;
        });
    }

    // Debounce function
    function debounce(func, delay = 300) {
        let timeoutId;
        return function(...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(this, args), delay);
        };
    }

    var modernSearchInstance = null;

    /** Fixed positioning for body-attached dropdown (Tom Select uses document coords; fixed needs viewport coords). */
    function positionModernSearchDropdown(ts) {
        if (!ts || !ts.control || !ts.dropdown) {
            return;
        }

        var rect = ts.control.getBoundingClientRect();
        var dropdown = ts.dropdown;
        var width = Math.max(Math.round(rect.width), 1);
        var left = Math.round(rect.left);
        if (left + width > window.innerWidth - 12) {
            left = Math.max(12, window.innerWidth - width - 12);
        }

        dropdown.style.position = 'fixed';
        dropdown.style.top = Math.round(rect.bottom + 2) + 'px';
        dropdown.style.left = left + 'px';
        dropdown.style.width = width + 'px';
        dropdown.style.minWidth = width + 'px';
        dropdown.style.maxWidth = width + 'px';
        dropdown.style.zIndex = '10100';
        dropdown.style.visibility = 'visible';
        dropdown.style.display = 'block';
        dropdown.style.opacity = '1';
        dropdown.style.background = '#fff';
        dropdown.style.backgroundColor = '#fff';
        dropdown.style.isolation = 'isolate';
        dropdown.style.pointerEvents = 'auto';
        dropdown.classList.add('modern-search-dropdown');
    }

    function buildSearchResultHtml(repo) {
        if (repo.loading) {
            return repo.text || '';
        }

        if (repo.children) {
            return '<strong class="ts-category-header">' + escapeHtml(repo.text) + '</strong>';
        }

        var idStr = repo.id ? String(repo.id) : '';
        var idParts = idStr.split('/');
        var encIdForRow = idParts[0] || '';
        var typeFromId = idParts[1] || '';
        var isCrmPersonRow = idStr.indexOf('/Client') !== -1 || idStr.indexOf('/Lead') !== -1;

        var clientId = repo.client_id
            ? '<span style="color: #007bff; font-weight: 600; margin-right: 8px;">#' + escapeHtml(String(repo.client_id)) + '</span>'
            : '';

        var lockPrefix = repo.locked
            ? '<span class="modern-search-lock" title="No direct access — use Quick view below">&#128274;</span>'
            : '';

        var details = [];
        if (repo.email) {
            details.push(renderHighlightedField(repo.email));
        }
        if (repo.phone) {
            details.push('Phone: ' + renderHighlightedField(repo.phone));
        }
        if (repo.dob) {
            details.push('DOB: ' + escapeHtml(String(repo.dob)));
        }
        var detailsText = details.join(' • ');

        var accessChips = '';
        if (repo.locked && repo.access_ui) {
            var ui = repo.access_ui;
            if (ui.show_quick) {
                accessChips += '<span class="badge rounded-pill bg-light text-dark border">Quick</span>';
            }
            if (ui.show_supervisor) {
                accessChips += '<span class="badge rounded-pill bg-light text-dark border">Supervisor</span>';
            }
        }

        var statusBadge = repo.status
            ? '<span class="badge bg-warning text-dark">' + escapeHtml(repo.status) + '</span>'
            : '';

        var adminIdAttr = repo.admin_id != null && repo.admin_id !== '' ? String(repo.admin_id) : '';
        var isLeadRow = !!(repo.is_lead || typeFromId === 'Lead');

        var needsAccessFlow = !!(isCrmPersonRow && repo.allow_access_modal && !repo.has_active_temp_access && adminIdAttr && encIdForRow
            && searchResultNeedsAccessModal(repo));

        var quickViewLine = needsAccessFlow
            ? '<div class="modern-search-result-quickview" data-admin-id="' + escapeHtml(adminIdAttr) + '" data-encoded-id="' + escapeHtml(encIdForRow) + '" data-is-lead="' + (isLeadRow ? '1' : '0') + '" title="Request access (Quick access in modal), then open record">' + crmIcon('bolt') + '<span>Quick view</span><span class="quickview-hint"> — Request access</span></div>'
            : '';

        var gateDataAttrs = needsAccessFlow
            ? ' data-admin-id="' + escapeHtml(adminIdAttr) + '" data-encoded-id="' + escapeHtml(encIdForRow) + '" data-is-lead="' + (isLeadRow ? '1' : '0') + '"'
            : '';
        var accessGateClass = needsAccessFlow ? ' modern-search-result--access-gate' : '';

        var metaParts = [];
        if (accessChips) {
            metaParts.push('<div class="d-flex flex-wrap justify-content-end gap-1">' + accessChips + '</div>');
        }
        if (statusBadge) {
            metaParts.push('<div class="mt-1">' + statusBadge + '</div>');
        }
        var metaHtml = metaParts.length
            ? '<div class="modern-search-result-meta text-end">' + metaParts.join('') + '</div>'
            : '';

        var nameHtml = renderHighlightedField(repo.name || repo.text || '');

        return (
            '<div class="ts-result-row modern-search-result' +
            (repo.locked ? ' modern-search-result--locked' : '') +
            accessGateClass + '"' + gateDataAttrs + '>' +
            '<div class="modern-search-result-content">' +
            '<div class="modern-search-result-main">' +
            '<div class="modern-search-result-title">' + lockPrefix + clientId + nameHtml + '</div>' +
            '<div class="modern-search-result-subtitle">' + detailsText + '</div>' +
            quickViewLine +
            '</div>' +
            metaHtml +
            '</div></div>'
        );
    }

    function escapeHtml(text) {
        if (text == null) {
            return '';
        }
        return String(text).replace(/[&<>"']/g, function (m) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
        });
    }

    /**
     * Server search API wraps query matches in <mark class="search-highlight">.
     * Escape all other text; preserve mark tags so highlights render (not as visible HTML).
     */
    function renderHighlightedField(text) {
        if (text == null || text === '') {
            return '';
        }
        var s = String(text);
        if (s.indexOf('<mark') === -1) {
            return escapeHtml(s);
        }

        var out = '';
        var lastIndex = 0;
        var re = /<mark\s+class="search-highlight">([\s\S]*?)<\/mark>/gi;
        var match;

        while ((match = re.exec(s)) !== null) {
            out += escapeHtml(s.slice(lastIndex, match.index));
            out += '<mark class="search-highlight">' + escapeHtml(match[1]) + '</mark>';
            lastIndex = re.lastIndex;
        }
        out += escapeHtml(s.slice(lastIndex));
        return out;
    }

    function flattenGroupedForTomSelect(grouped, ts) {
        var options = [];
        if (!Array.isArray(grouped)) {
            return options;
        }

        // Flat API list (no category children)
        if (grouped.length && !grouped[0].children) {
            grouped.forEach(function (item) {
                var id = item.id;
                options.push(Object.assign({}, item, {
                    value: id,
                    text: item.name || item.text || ''
                }));
            });
            return options;
        }

        grouped.forEach(function (group, idx) {
            if (!group.children || !group.children.length) {
                return;
            }
            var ogKey = 'ms_og_' + idx;
            if (ts && typeof ts.addOptionGroup === 'function') {
                ts.addOptionGroup(ogKey, { label: group.text });
            }
            group.children.forEach(function (item) {
                var id = item.id;
                options.push(Object.assign({}, item, {
                    value: id,
                    text: item.name || item.text || '',
                    optgroup: ogKey
                }));
            });
        });
        return options;
    }

    // Initialize search with modern features
    function initModernSearch() {
        const searchSelector = '.js-data-example-ajaxccsearch';
        const searchElement = document.querySelector(searchSelector);

        if (!searchElement) {
            console.log('Search element not found:', searchSelector);
            return;
        }

        if (searchElement.tomselect) {
            modernSearchInstance = searchElement.tomselect;
            return;
        }

        if (typeof initTomSelect !== 'function' || typeof TomSelect === 'undefined') {
            console.error('Tom Select is not available for modern search');
            return;
        }

        var siteUrl = siteBase();
        // Only latest successful API response should be scored/visible (same pattern as recipient-select.js).
        // Prevents Tom Select client-side re-filtering from hiding server matches
        // (e.g. phone found only in client_phones, while option.phone is primary admins.phone).
        var modernSearchLoadState = { seq: 0 };
        var instance = initTomSelect(searchElement, {
            maxItems: 1,
            width: '450px',
            dropdownParent: 'body',
            valueField: 'value',
            labelField: 'text',
            searchField: ['text', 'name', 'email', 'client_id', 'phone', 'dob'],
            placeholder: 'Search clients, leads, partners... (Ctrl+K)',
            plugins: ['clear_button'],
            dropdownClass: 'modern-search-dropdown',
            loadThrottle: 300,
            shouldLoad: function (query) {
                return query.length >= 2;
            },
            // Keep current API hits only — do not re-filter by typed query against displayed fields.
            score: function () {
                return function (item) {
                    return item && item._modernSearchLoadToken === modernSearchLoadState.seq ? 1 : 0;
                };
            },
            load: function (query, callback) {
                if (!query || query.length < 2) {
                    callback();
                    return;
                }
                var ts = this;
                var requestSeq = ++modernSearchLoadState.seq;
                if (!window.jQuery || !window.jQuery.ajax) {
                    callback();
                    return;
                }
                window.jQuery.ajax({
                    url: siteUrl + '/clients/get-allclients',
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    data: {
                        q: query,
                        page: 1
                    },
                    success: function (data) {
                        // Drop out-of-order / superseded responses
                        if (requestSeq !== modernSearchLoadState.seq) {
                            callback();
                            return;
                        }
                        // Remove previous search options so stale hits cannot reappear
                        if (typeof ts.clearOptions === 'function') {
                            ts.clearOptions();
                        }
                        var grouped = groupResultsByCategory(data.items || []);
                        var flat = flattenGroupedForTomSelect(grouped, ts);
                        flat.forEach(function (item) {
                            item._modernSearchLoadToken = requestSeq;
                        });
                        callback(flat);
                        if (flat.length) {
                            window.setTimeout(function () {
                                if (!ts.isOpen) {
                                    ts.open();
                                }
                                positionModernSearchDropdown(ts);
                            }, 0);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Search error:', error, xhr);
                        // Always end Tom Select's loading state for this request
                        callback();
                    }
                });
            },
            render: {
                option: function (data, escape) {
                    if (data.loading) {
                        return '<div>' + escape(data.text) + '</div>';
                    }
                    return buildSearchResultHtml(data);
                },
                item: function (data, escape) {
                    var label = data.name || data.text || 'Search...';
                    if (typeof label === 'string' && label.indexOf('<') !== -1) {
                        label = stripHtml(label);
                    }
                    return '<div>' + escape(label) + '</div>';
                },
                optgroup_header: function (data, escape) {
                    return '<strong class="ts-category-header">' + escape(data.label) + '</strong>';
                }
            },
            onChange: function (value) {
                if (!value) {
                    return;
                }
                var data = this.options[value];
                this.clear(true);
                if (!data) {
                    return;
                }
                navigateToResult(data);
            },
            onDropdownOpen: function () {
                positionModernSearchDropdown(this);
            }
        });

        modernSearchInstance = instance;

        if (instance) {
            // Tom Select positionDropdown() uses document coords — override for fixed viewport positioning.
            instance.positionDropdown = function () {
                positionModernSearchDropdown(this);
            };

            $(window).off('scroll.modernSearch resize.modernSearch').on('scroll.modernSearch resize.modernSearch', function () {
                if (instance.isOpen) {
                    positionModernSearchDropdown(instance);
                }
            });
        }

        // Keyboard shortcut: Ctrl+K or Cmd+K
        $(document).off('keydown.modernSearch').on('keydown.modernSearch', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                if (modernSearchInstance) {
                    modernSearchInstance.open();
                    modernSearchInstance.focus();
                }
            }

            if (e.key === 'Escape' && modernSearchInstance) {
                modernSearchInstance.close();
            }
        });

        // Handle search button click
        $('.search-element .btn').off('click.modernSearch').on('click.modernSearch', function (e) {
            e.preventDefault();
            if (modernSearchInstance) {
                modernSearchInstance.open();
                window.setTimeout(function () {
                    positionModernSearchDropdown(modernSearchInstance);
                }, 0);
            }
        });

        console.log('Modern search initialized successfully (Tom Select)');
    }

    // Group results by category
    function groupResultsByCategory(items) {
        if (!items || items.length === 0) {
            return [];
        }

        const categories = {
            clients: { text: 'CLIENTS', children: [] },
            leads: { text: 'LEADS', children: [] },
            partners: { text: 'PARTNERS', children: [] },
            products: { text: 'PRODUCTS', children: [] },
            applications: { text: 'APPLICATIONS', children: [] }
        };

        items.forEach(item => {
            const category = item.category || 'clients';
            if (categories[category]) {
                categories[category].children.push(item);
            }
        });

        // Only return categories that have results
        const result = [];
        Object.keys(categories).forEach(key => {
            if (categories[key].children.length > 0) {
                result.push(categories[key]);
            }
        });

        return result.length > 0 ? result : items;
    }

    // Format search result item
    function formatSearchResult(repo) {
        if (repo.loading) {
            return repo.text;
        }

        // If it's a category header
        if (repo.children) {
            return $('<strong class="ts-category-header">' + repo.text + '</strong>');
        }

        const idStr = repo.id ? String(repo.id) : '';
        const idParts = idStr.split('/');
        const encIdForRow = idParts[0] || '';
        const typeFromId = idParts[1] || '';
        const isCrmPersonRow = idStr.indexOf('/Client') !== -1 || idStr.indexOf('/Lead') !== -1;

        // Show client ID as blue badge before name
        const clientId = repo.client_id ? `<span style="color: #007bff; font-weight: 600; margin-right: 8px;">#${repo.client_id}</span>` : '';

        const lockPrefix = repo.locked ? '<span class="modern-search-lock" title="No direct access — use Quick view below">&#128274;</span>' : '';

        // Build additional details array for subtitle
        const details = [];
        if (repo.email) {
            details.push(repo.email);
        }
        if (repo.phone) {
            details.push(`Phone: ${repo.phone}`);
        }
        if (repo.dob) {
            details.push(`DOB: ${repo.dob}`);
        }
        const detailsText = details.join(' • ');

        let accessChips = '';
        if (repo.locked && repo.access_ui) {
            const ui = repo.access_ui;
            if (ui.show_quick) {
                accessChips += '<span class="badge rounded-pill bg-light text-dark border">Quick</span>';
            }
            if (ui.show_supervisor) {
                accessChips += '<span class="badge rounded-pill bg-light text-dark border">Supervisor</span>';
            }
        }

        const statusBadge = repo.status
            ? `<span class="badge bg-warning text-dark">${repo.status}</span>`
            : '';

        const adminIdAttr = repo.admin_id != null && repo.admin_id !== '' ? String(repo.admin_id) : '';
        const isLeadRow = !!(repo.is_lead || typeFromId === 'Lead');

        const needsAccessFlow = !!(isCrmPersonRow && repo.allow_access_modal && !repo.has_active_temp_access && adminIdAttr && encIdForRow
            && searchResultNeedsAccessModal(repo));

        // Staff only (server flag); hide while a time-boxed grant is active (go straight to record from main row)
        const quickViewLine = needsAccessFlow
            ? `<div class="modern-search-result-quickview" data-admin-id="${adminIdAttr}" data-encoded-id="${encIdForRow}" data-is-lead="${isLeadRow ? '1' : '0'}" title="Request access (Quick access in modal), then open record">${crmIcon('bolt')}<span>Quick view</span><span class="quickview-hint"> — Request access</span></div>`
            : '';

        const gateDataAttrs = needsAccessFlow
            ? ` data-admin-id="${adminIdAttr}" data-encoded-id="${encIdForRow}" data-is-lead="${isLeadRow ? '1' : '0'}"`
            : '';
        const accessGateClass = needsAccessFlow ? ' modern-search-result--access-gate' : '';

        const metaParts = [];
        if (accessChips) {
            metaParts.push('<div class="d-flex flex-wrap justify-content-end gap-1">' + accessChips + '</div>');
        }
        if (statusBadge) {
            metaParts.push('<div class="mt-1">' + statusBadge + '</div>');
        }
        const metaHtml = metaParts.length
            ? `<div class="modern-search-result-meta text-end">${metaParts.join('')}</div>`
            : '';

        const $container = $(`
            <div class="ts-result-row modern-search-result${repo.locked ? ' modern-search-result--locked' : ''}${accessGateClass}"${gateDataAttrs}>
                <div class="modern-search-result-content">
                    <div class="modern-search-result-main">
                        <div class="modern-search-result-title">${lockPrefix}${clientId}${repo.name || repo.text}</div>
                        <div class="modern-search-result-subtitle">${detailsText}</div>
                        ${quickViewLine}
                    </div>
                    ${metaHtml}
                </div>
            </div>
        `);

        return $container;
    }

    // Format selected item
    function formatSearchSelection(repo) {
        return repo.name || repo.text || 'Search...';
    }

    // Navigate to selected result
    function navigateToResult(data) {
        if (!data.id) {
            return;
        }

        const siteUrl = (typeof site_url !== 'undefined' ? site_url : '');
        const parts = data.id.split('/');
        const type = parts[1];  //alert('type='+type);
        const id = parts[0];  //alert('id='+id);

        if ((type === 'Client' || type === 'Lead') && data.admin_id && searchResultNeedsAccessModal(data)) {
            openCrmAccessRequestModal({
                adminId: parseInt(data.admin_id, 10),
                encodedId: id,
                isLead: !!(data.is_lead || type === 'Lead'),
                displayName: stripHtml(data.name || data.text || '')
            });
            return;
        }

        let url = '';

        switch (type) {
            case 'Client':
                 url = siteUrl + (data.is_lead ? '/leads/detail/' : '/clients/detail/') + id;
                 break;
            case 'Lead':
                url = siteUrl + '/leads/detail/' + id;
                break;
            case 'Partner':
                url = siteUrl + '/partners/detail/' + id;
                break;
            case 'Product':
                url = siteUrl + '/products/detail/' + id;
                break;
            case 'Application':
                // Redirect to applications list (applications detail page removed - use client detail page instead)
                url = siteUrl + '/applications';
                break;
            default:
                console.warn('Unknown result type:', type);
                return;
        }

        if (url) {
            window.location.href = url;
        }
    }

    // Wait for Tom Select helpers to be ready
    (async function() {
        if (typeof window.vendorLibsReady !== 'undefined') {
            try {
                await window.vendorLibsReady;
            } catch (e) {
                console.warn('vendorLibsReady promise rejected, using fallback:', e);
            }
        }

        if (typeof waitForTomSelect === 'function') {
            await waitForTomSelect(200);
        } else {
            await new Promise(function (resolve) {
                var attempts = 0;
                var maxAttempts = 100;
                var check = function () {
                    attempts++;
                    if (typeof TomSelect !== 'undefined' && typeof window.initTomSelect === 'function') {
                        resolve();
                    } else if (attempts < maxAttempts) {
                        setTimeout(check, 50);
                    } else {
                        console.error('Tom Select not available after waiting');
                        resolve();
                    }
                };
                check();
            });
        }

        if (typeof $ !== 'undefined') {
            $(document).ready(function() {
                setTimeout(function() {
                    wireCrmAccessModalOnce();
                    wireOngoingSheetCourseLinkAccessOnce();
                    wireModernSearchQuickViewRowOnce();
                    initModernSearch();
                }, 100);
            });
        } else {
            console.error('jQuery not available for search initialization');
        }
    })();

    // Re-initialize on Turbolinks/AJAX page loads if needed
    $(document).on('turbolinks:load', function() {
        if (typeof TomSelect !== 'undefined' && typeof initTomSelect === 'function') {
            var el = document.querySelector('.js-data-example-ajaxccsearch');
            if (el && el.tomselect) {
                el.tomselect.destroy();
                modernSearchInstance = null;
            }
            wireCrmAccessModalOnce();
            wireOngoingSheetCourseLinkAccessOnce();
            wireModernSearchQuickViewRowOnce();
            initModernSearch();
        }
    });

    window.openCrmAccessRequestModal = openCrmAccessRequestModal;

})();

