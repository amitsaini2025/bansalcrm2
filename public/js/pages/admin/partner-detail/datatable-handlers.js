/**
 * Admin Partner Detail - DataTable Handlers
 *
 * Initializes DataTables and handles inline note updates.
 *
 * Dependencies:
 *   - jQuery
 *   - DataTables
 *   - config.js (App object)
 */

'use strict';

// ============================================================================
// ASYNC WRAPPER - Wait for vendor libraries before initialization
// ============================================================================

(async function() {
    if (typeof window.vendorLibsReady !== 'undefined') {
        console.log('[datatable-handlers.js] Waiting for vendorLibsReady promise...');
        await window.vendorLibsReady;
        console.log('[datatable-handlers.js] Vendor libraries ready!');
    } else {
        console.log('[datatable-handlers.js] vendorLibsReady not found, polling for libraries...');
        await new Promise((resolve) => {
            const check = () => {
                if (typeof $ !== 'undefined' && typeof $.fn.DataTable === 'function') {
                    console.log('[datatable-handlers.js] All vendor libraries detected!');
                    resolve();
                } else {
                    setTimeout(check, 50);
                }
            };
            check();
        });
    }

    const activeTabForButtons = (typeof PageConfig !== 'undefined' && PageConfig.activeTab) ? PageConfig.activeTab : 'application';

    if (activeTabForButtons === 'student' || activeTabForButtons === 'accounts') {
        try {
            await import('@/datatables-buttons-init.js');
        } catch (err) {
            console.error('[partner-detail] Failed to load DataTables Buttons:', err);
        }
    }

// ============================================================================
// DATATABLE INITIALIZATION
// ============================================================================

jQuery(document).ready(function($){
    const partnerName = PageConfig.partnerName || 'Partner';
    const activeTab = (typeof PageConfig !== 'undefined' && PageConfig.activeTab) ? PageConfig.activeTab : 'application';
    const partnerNumericId = (typeof PageConfig !== 'undefined' && PageConfig.partnerId)
        ? parseInt(PageConfig.partnerId, 10)
        : 0;

    var enrolmentTypeLabels = {
        transfer_option: 'Transfer',
        course_progression: 'Course progression'
    };

    var companyNameLabels = {
        bansal_education_group: 'Bansal Education Group',
        elite_11: 'Elite 11'
    };

    // Column indices after Enrolment Type + Company Name insert:
    // 22 = Enrolment Type, 23 = Company Name, 24 = Application ID (hidden), 25 = Note, 26 = Action
    var STUDENT_COL_ENROLMENT = 22;
    var STUDENT_COL_COMPANY = 23;
    var STUDENT_COL_APP_ID = 24;
    var STUDENT_COL_NOTE = 25;
    var STUDENT_COL_ACTION = 26;

    function parseSelectFieldValue(data, dataAttr, knownValues) {
        if (data === null || data === undefined) {
            return '';
        }

        if (typeof data === 'string' && data.indexOf('<select') !== -1) {
            var attrRegex = new RegExp('data-' + dataAttr + '="([^"]*)"');
            var attrMatch = data.match(attrRegex);
            if (attrMatch) {
                return attrMatch[1] || '';
            }

            if (knownValues && knownValues.length) {
                var selectedMatch = data.match(new RegExp('<option value="(' + knownValues.join('|') + ')" selected'));
                if (selectedMatch) {
                    return selectedMatch[1];
                }
            }

            return '';
        }

        return String(data);
    }

    function parseEnrolmentTypeValue(data) {
        return parseSelectFieldValue(data, 'enrolment-type', Object.keys(enrolmentTypeLabels));
    }

    function parseCompanyNameValue(data) {
        return parseSelectFieldValue(data, 'company-name', Object.keys(companyNameLabels));
    }

    function isStudentFieldAdminEditor() {
        return !(typeof PageConfig !== 'undefined' && PageConfig.canEditApplicationEnrolmentCompanyFields === false);
    }

    function buildEnrolmentTypeSelect(applicationId, currentValue, cssClass) {
        currentValue = parseEnrolmentTypeValue(currentValue);
        var canEdit = isStudentFieldAdminEditor() || currentValue === '';
        var disabledAttr = canEdit ? '' : ' disabled="disabled"';
        var html = '<select class="' + cssClass + '" data-application-id="' + applicationId + '" data-enrolment-type="' + currentValue + '"' + disabledAttr + '>';
        html += '<option value=""' + (currentValue === '' ? ' selected="selected"' : '') + '>Select</option>';

        Object.keys(enrolmentTypeLabels).forEach(function (value) {
            html += '<option value="' + value + '"' + (currentValue === value ? ' selected="selected"' : '') + '>' + enrolmentTypeLabels[value] + '</option>';
        });

        html += '</select>';
        return html;
    }

    function buildCompanyNameSelect(applicationId, currentValue, cssClass) {
        currentValue = parseCompanyNameValue(currentValue);
        var canEdit = isStudentFieldAdminEditor() || currentValue === '';
        var disabledAttr = canEdit ? '' : ' disabled="disabled"';
        var html = '<select class="' + cssClass + '" data-application-id="' + applicationId + '" data-company-name="' + currentValue + '"' + disabledAttr + '>';
        html += '<option value=""' + (currentValue === '' ? ' selected="selected"' : '') + '>Select</option>';

        Object.keys(companyNameLabels).forEach(function (value) {
            html += '<option value="' + value + '"' + (currentValue === value ? ' selected="selected"' : '') + '>' + companyNameLabels[value] + '</option>';
        });

        html += '</select>';
        return html;
    }

    function enrolmentTypeColumnRender(cssClass) {
        return function (data, type, row) {
            var applicationId = row[STUDENT_COL_APP_ID];
            var currentValue = parseEnrolmentTypeValue(data);

            if (type === 'display') {
                return buildEnrolmentTypeSelect(applicationId, currentValue, cssClass);
            }

            if (type === 'export' || type === 'filter' || type === 'sort') {
                return enrolmentTypeLabels[currentValue] || 'Select';
            }

            return currentValue;
        };
    }

    function companyNameColumnRender(cssClass) {
        return function (data, type, row) {
            var applicationId = row[STUDENT_COL_APP_ID];
            var currentValue = parseCompanyNameValue(data);

            if (type === 'display') {
                return buildCompanyNameSelect(applicationId, currentValue, cssClass);
            }

            if (type === 'export' || type === 'filter' || type === 'sort') {
                return companyNameLabels[currentValue] || 'Select';
            }

            return currentValue;
        };
    }

    function syncEnrolmentTypeSelects(container) {
        $(container).find('.enrolment-type-field, .enrolment-type-field1').each(function () {
            var value = $(this).attr('data-enrolment-type') || '';
            $(this).val(value);
        });
    }

    function syncCompanyNameSelects(container) {
        $(container).find('.company-name-field, .company-name-field1').each(function () {
            var value = $(this).attr('data-company-name') || '';
            $(this).val(value);
        });
    }

    var studentToolbarDom = '<"row student-dt-toolbar align-items-center g-2"<"col-auto"l><"col-auto"B><"col-auto ms-auto"f>>rtip';

    function buildStatusFilterHtml(selectId) {
        return '<label class="student-dt-status-filter mb-0">Filter by Status:' +
            '<select id="' + selectId + '" class="form-control form-control-sm">' +
            '<option value="">All</option>' +
            '<option value="In Progress">In Progress</option>' +
            '<option value="Completed">Completed</option>' +
            '<option value="Discontinued">Discontinued</option>' +
            '<option value="Cancelled">Cancelled</option>' +
            '<option value="Withdrawn">Withdrawn</option>' +
            '<option value="Deferred">Deferred</option>' +
            '<option value="Future">Future</option>' +
            '<option value="VOE">VOE</option>' +
            '<option value="Refund">Refund</option>' +
            '</select></label>';
    }

    /**
     * P-10: apply Student column visibility from checkbox state via DataTables API
     * so it survives serverSide redraw. Checkbox values stay 1-based (nth-child / legacy map):
     * value N → column index N-1. SNo (0) + CRM Ref (1) always stay visible; app id always hidden.
     */
    function applyStudentColumnVisibility(api, $colRoot) {
        if (!api || !$colRoot || !$colRoot.length) {
            return;
        }

        var colCount = api.columns().count();
        var $items = $colRoot.find('label.dropdown-option input').not('[value="all"]');
        var anyToggleableChecked = false;

        $items.each(function () {
            var n = parseInt($(this).val(), 10);
            if (isNaN(n) || n < 1) {
                return;
            }
            var idx = n - 1;
            if (idx < 0 || idx >= colCount) {
                return;
            }
            // Never toggle the hidden application-id column via a mistaken index
            if (idx === STUDENT_COL_APP_ID) {
                return;
            }
            var on = $(this).is(':checked');
            if (on) {
                anyToggleableChecked = true;
            }
            api.column(idx).visible(on, false);
        });

        if (colCount > 0) {
            api.column(0).visible(true, false);
        }
        if (colCount > 1) {
            api.column(1).visible(true, false);
        }
        if (colCount > STUDENT_COL_APP_ID) {
            api.column(STUDENT_COL_APP_ID).visible(false, false);
        }

        if (!anyToggleableChecked) {
            // Match legacy "Display All" off: only SNo, CRM Ref, Student Status (nth-child 1,2,22)
            for (var i = 0; i < colCount; i++) {
                if (i === 0 || i === 1 || i === 21) {
                    api.column(i).visible(true, false);
                } else {
                    api.column(i).visible(false, false);
                }
            }
        } else {
            // Note + Action are not in the toggle list — keep visible during normal use
            if (colCount > STUDENT_COL_NOTE) {
                api.column(STUDENT_COL_NOTE).visible(true, false);
            }
            if (colCount > STUDENT_COL_ACTION) {
                api.column(STUDENT_COL_ACTION).visible(true, false);
            }
        }

        try {
            api.columns.adjust();
        } catch (e) {
            // ignore layout adjust errors
        }
    }

    function setupStudentColumnVisibility(api, $colRoot) {
        if (!api || !$colRoot || !$colRoot.length) {
            return;
        }

        $colRoot.find('button').off('click.partnerColvis').on('click.partnerColvis', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $colRoot.find('.dropdown_list').toggleClass('active');
        });

        $colRoot.find('label.dropdown-option input')
            .off('click.partnerColvis')
            .on('click.partnerColvis', function () {
                var $input = $(this);
                var val = $input.val();

                // Run after the checkbox default toggle so .is(':checked') is correct
                setTimeout(function () {
                    if (val === 'all') {
                        if ($input.is(':checked')) {
                            $colRoot.find('label.dropdown-option input').prop('checked', true);
                        } else {
                            $colRoot.find('label.dropdown-option input').prop('checked', false);
                        }
                    } else if (!$input.is(':checked')) {
                        $colRoot.find('label.dropdown-option.all input').prop('checked', false);
                    } else {
                        var allOn = true;
                        $colRoot.find('label.dropdown-option input').not('[value="all"]').each(function () {
                            if (!$(this).is(':checked')) {
                                allOn = false;
                            }
                        });
                        $colRoot.find('label.dropdown-option.all input').prop('checked', allOn);
                    }
                    applyStudentColumnVisibility(api, $colRoot);
                }, 0);
            });

        // Re-apply after any redraw (counts update, search, paging) as belt-and-suspenders
        api.off('draw.partnerColvis').on('draw.partnerColvis', function () {
            applyStudentColumnVisibility(api, $colRoot);
        });

        applyStudentColumnVisibility(api, $colRoot);
    }

    function setupStudentToolbar(api, options) {
        var $wrapper = $(api.table().container()).closest('.dataTables_wrapper');
        var $toolbar = $wrapper.children('.student-dt-toolbar').first();
        var $toolbarHost = $(options.toolbarHostSelector);

        if (!$toolbar.length || !$toolbarHost.length) {
            return;
        }

        var $colToggle = $(options.columnToggleSelector).detach().removeAttr('style');
        $toolbar.prepend($('<div class="col-auto student-dt-columns">').append($colToggle));
        $toolbar.detach().appendTo($toolbarHost);

        setupStudentColumnVisibility(api, $colToggle);

        var $filter = $toolbar.find('.dataTables_filter');
        $filter.addClass('student-dt-filter-controls');

        if (options.statusFilterId) {
            $filter.append(buildStatusFilterHtml(options.statusFilterId));
            if (!options.serverSideStatusFilter) {
                $('#' + options.statusFilterId).on('change', function () {
                    var statusFilterval = $(this).val();
                    if (statusFilterval === '') {
                        api.column(21).search('').draw();
                    } else {
                        api.column(21).search('^' + statusFilterval + '$', true, false).draw();
                    }
                });
            }
        }
    }

    function updateApplicationStatusCounts(counts) {
        if (!counts) {
            return;
        }
        for (var i = 0; i <= 3; i++) {
            var el = document.getElementById('app-status-count-' + i);
            if (el) {
                el.textContent = counts[i] != null ? counts[i] : 0;
            }
        }
    }

    if (activeTab === 'application' && $('.table-2').length) {
        var applicationDataUrl = (typeof AppConfig !== 'undefined' && AppConfig.urls && AppConfig.urls.partnersGetApplicationTabData)
            ? AppConfig.urls.partnersGetApplicationTabData
            : (typeof App !== 'undefined' && App.getUrl ? App.getUrl('partnersGetApplicationTabData') : null);

        $(".table-2").DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: applicationDataUrl,
                type: 'GET',
                data: function (d) {
                    d.partner_id = partnerNumericId;
                },
                dataSrc: function (json) {
                    if (json.statusCounts) {
                        updateApplicationStatusCounts(json.statusCounts);
                    }
                    return json.data;
                }
            },
            searching: true,
            lengthChange: true,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[7, 'desc']],
            columns: [
                { data: 0 }, // Name
                { data: 1, orderable: false }, // Assignee
                { data: 2 }, // Product Name
                { data: 3 }, // Workflow
                { data: 4 }, // Current Stage
                { data: 5, orderable: false }, // Enrolment Type
                { data: 6 }, // Status
                { data: 7 }, // Added On
                { data: 8 }  // Last Updated
            ],
            columnDefs: [
                { targets: [1, 5], orderable: false },
                { targets: '_all', defaultContent: '' }
            ]
        });
    }

    if (activeTab === 'accounts' && $('.invoicetable').length) {
        var accountsDataUrl = (typeof AppConfig !== 'undefined' && AppConfig.urls && AppConfig.urls.partnersGetAccountsTabData)
            ? AppConfig.urls.partnersGetAccountsTabData
            : (typeof App !== 'undefined' && App.getUrl ? App.getUrl('partnersGetAccountsTabData') : null);

        var accountsExportUrl = (typeof AppConfig !== 'undefined' && AppConfig.urls && AppConfig.urls.partnersExportAccountsTabData)
            ? AppConfig.urls.partnersExportAccountsTabData
            : (typeof App !== 'undefined' && App.getUrl ? App.getUrl('partnersExportAccountsTabData') : null);

        var accountsToolbarDom = '<"row student-dt-toolbar accounts-dt-toolbar align-items-center g-2 flex-nowrap"<"col-auto"l><"col-auto"B>>rtip';

        function buildAccountsExportUrl(format) {
            if (!accountsExportUrl) {
                return '#';
            }
            var params = new URLSearchParams();
            params.set('partner_id', partnerNumericId);
            params.set('format', format === 'xlsx' ? 'xlsx' : 'csv');
            return accountsExportUrl + '?' + params.toString();
        }

        function buildAccountsExportButtons() {
            return [
                {
                    text: crmIcon('file-excel') + ' Excel',
                    className: 'btn btn-success btn-sm',
                    action: function () {
                        window.location.href = buildAccountsExportUrl('xlsx');
                    }
                },
                {
                    text: crmIcon('file-csv') + ' CSV',
                    className: 'btn btn-info btn-sm',
                    action: function () {
                        window.location.href = buildAccountsExportUrl('csv');
                    }
                }
            ];
        }

        function setupAccountsToolbar(api) {
            var $wrapper = $(api.table().container()).closest('.dataTables_wrapper');
            var $toolbar = $wrapper.children('.student-dt-toolbar, .accounts-dt-toolbar').first();
            var $toolbarHost = $('.accounts-dt-toolbar-host');

            if (!$toolbar.length || !$toolbarHost.length) {
                return;
            }

            $toolbar.detach().appendTo($toolbarHost);
        }

        $(".invoicetable").DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: true,
            pageLength: 10,
            dom: accountsToolbarDom,
            buttons: buildAccountsExportButtons(),
            ajax: {
                url: accountsDataUrl,
                type: 'GET',
                data: function (d) {
                    d.partner_id = partnerNumericId;
                }
            },
            columns: [
                { data: 0 },
                { data: 1 },
                { data: 2 },
                { data: 3 },
                { data: 4 },
                { data: 5 },
                { data: 6 }
            ],
            columnDefs: [
                { targets: [0, 2, 3, 5, 6], orderable: false },
                { targets: 2, className: 'invoice-service-col' },
                { targets: '_all', defaultContent: '' }
            ],
            order: [[1, 'desc']],
            initComplete: function () {
                setupAccountsToolbar(this.api());
            },
            rowCallback: function (row, data) {
                if (data[0]) {
                    $(row).attr('id', 'iid_' + data[0]);
                }
            }
        });
    }

    // Student tab — server-side DataTables; inactive table loads on tab click.
    if (activeTab !== 'student') {
        return;
    }

    var studentDataUrl = (typeof AppConfig !== 'undefined' && AppConfig.urls && AppConfig.urls.partnersGetStudentTabData)
        ? AppConfig.urls.partnersGetStudentTabData
        : (typeof App !== 'undefined' && App.getUrl ? App.getUrl('partnersGetStudentTabData') : null);

    var studentTotalsUrl = (typeof AppConfig !== 'undefined' && AppConfig.urls && AppConfig.urls.partnersGetStudentTabTotals)
        ? AppConfig.urls.partnersGetStudentTabTotals
        : (typeof App !== 'undefined' && App.getUrl ? App.getUrl('partnersGetStudentTabTotals') : null);

    var studentCountUrl = (typeof AppConfig !== 'undefined' && AppConfig.urls && AppConfig.urls.partnersGetStudentTabCount)
        ? AppConfig.urls.partnersGetStudentTabCount
        : (typeof App !== 'undefined' && App.getUrl ? App.getUrl('partnersGetStudentTabCount') : null);

    var studentExportUrl = (typeof AppConfig !== 'undefined' && AppConfig.urls && AppConfig.urls.partnersExportStudentTabData)
        ? AppConfig.urls.partnersExportStudentTabData
        : (typeof App !== 'undefined' && App.getUrl ? App.getUrl('partnersExportStudentTabData') : null);

    if (!studentDataUrl) {
        console.error('[partner-detail] partnersGetStudentTabData URL is not configured.');
        return;
    }

    if (!partnerNumericId) {
        console.error('[partner-detail] PageConfig.partnerId is not configured.');
        return;
    }

    var refreshTotalsTimer = null;
    var initialTotalsDelayMs = 2500;
    var countFetchDelayMs = 3000;

    function studentTabCsrfToken() {
        return (typeof App !== 'undefined' && App.getCsrf) ? App.getCsrf() : '';
    }

    function studentTabPost(url, payload, onSuccess) {
        $.ajax({
            url: url,
            type: 'POST',
            data: $.extend({}, payload, { _token: studentTabCsrfToken() }),
            headers: {
                'X-CSRF-TOKEN': studentTabCsrfToken()
            },
            success: onSuccess
        });
    }

    function applyStudentTableCounts(api, recordsTotal, recordsFiltered) {
        if (!api) {
            return;
        }
        var settings = api.settings()[0];
        settings._iRecordsTotal = recordsTotal;
        settings._iRecordsDisplay = recordsFiltered != null ? recordsFiltered : recordsTotal;
        api.draw(false);
    }

    function fetchStudentTabCounts(api, list, options) {
        if (!studentCountUrl || !api) {
            return;
        }
        var payload = {
            partner_id: partnerNumericId,
            list: list,
            start: api.page.info().start,
            length: api.page.len(),
            row_count: api.rows({ page: 'current' }).count(),
            search: resolveStudentSearchValue(api)
        };
        if (list === 'active' && options && options.statusFilterId) {
            payload.status_filter = normaliseStudentStatusFilterValue(
                $('#' + options.statusFilterId).val()
            );
        }
        $.ajax({
            url: studentCountUrl,
            type: 'POST',
            data: $.extend({}, payload, { _token: studentTabCsrfToken() }),
            headers: {
                'X-CSRF-TOKEN': studentTabCsrfToken()
            },
            success: function (resp) {
                if (!resp || !resp.status) {
                    return;
                }
                // P-5: do not install estimated/fake totals as exact pagination N
                if (resp.estimated) {
                    return;
                }
                applyStudentTableCounts(api, resp.recordsTotal, resp.recordsFiltered);
            }
        });
    }

    function normaliseStudentStatusFilterValue(value) {
        if (value === null || value === undefined) {
            return '';
        }
        var trimmed = String(value).trim();
        return (trimmed === '' || trimmed === '-' || trimmed === 'null') ? '' : trimmed;
    }

    /**
     * Resolve the active DataTables search string for server-side Student tab.
     * Prefers api.search(); falls back to last ajax params (P-4).
     */
    function resolveStudentSearchValue(api) {
        if (!api) {
            return '';
        }
        var searchVal = '';
        if (typeof api.search === 'function') {
            searchVal = api.search() || '';
        }
        if (!searchVal && api.ajax && typeof api.ajax.params === 'function') {
            try {
                var ajaxParams = api.ajax.params();
                if (ajaxParams && ajaxParams.search && ajaxParams.search.value) {
                    searchVal = ajaxParams.search.value;
                }
            } catch (e) {
                // ignore: keep empty search
            }
        }
        return String(searchVal || '').trim();
    }

    function scheduleStudentTotalsRefresh(api, list, delayMs) {
        if (!studentTotalsUrl) {
            return;
        }
        clearTimeout(refreshTotalsTimer);
        refreshTotalsTimer = setTimeout(function () {
            refreshStudentTotals(api, list);
        }, typeof delayMs === 'number' ? delayMs : 800);
    }

    var studentColumnDefs = [
        { data: 0 }, { data: 1 }, { data: 2 }, { data: 3 }, { data: 4 },
        { data: 5 }, { data: 6 }, { data: 7 }, { data: 8 }, { data: 9 },
        { data: 10 }, { data: 11 }, { data: 12 }, { data: 13 }, { data: 14 },
        { data: 15 }, { data: 16 }, { data: 17 }, { data: 18 }, { data: 19 },
        { data: 20 }, { data: 21 }, { data: 22 }, { data: 23 }, { data: 24 },
        { data: 25 }, { data: 26 }
    ];

    function buildStudentExportUrl(list, api, format) {
        if (!studentExportUrl) {
            return '#';
        }
        var params = new URLSearchParams();
        params.set('partner_id', partnerNumericId);
        params.set('list', list);
        params.set('format', format === 'xlsx' ? 'xlsx' : 'csv');
        var searchVal = resolveStudentSearchValue(api);
        if (searchVal) {
            params.set('search', searchVal);
        }
        if (list === 'active') {
            var statusVal = normaliseStudentStatusFilterValue($('#statusFilter').val());
            if (statusVal) {
                params.set('status_filter', statusVal);
            }
        }
        return studentExportUrl + '?' + params.toString();
    }

    function refreshStudentTotals(api, list) {
        if (!studentTotalsUrl) {
            return;
        }
        var payload = {
            partner_id: partnerNumericId,
            list: list,
            search: resolveStudentSearchValue(api)
        };
        if (list === 'active') {
            payload.status_filter = normaliseStudentStatusFilterValue($('#statusFilter').val());
        }
        studentTabPost(studentTotalsUrl, payload, function (resp) {
            if (!resp || !resp.status) {
                return;
            }
            if (list === 'active') {
                $('#total_commission_claimed').text(resp.claimed);
                $('#total_commission_anticipated').text(resp.anticipated);
                $('#total_commission_paid').text(resp.paid);
                $('#total_commission_pending').text(resp.pending);
            } else {
                $('#total_commission_as_per_fee_reported1').text(resp.claimed);
                $('#total_commission_anticipated1').text(resp.anticipated);
                $('#total_commission_paid_as_per_fee_reported1').text(resp.paid);
                $('#total_commission_pending1').text(resp.pending);
            }
        });
    }

    function buildStudentExportButtons(list, apiGetter) {
        return [
            {
                text: crmIcon('file-excel') + ' Excel',
                className: 'btn btn-success btn-sm',
                action: function () {
                    var api = typeof apiGetter === 'function' ? apiGetter() : null;
                    window.location.href = buildStudentExportUrl(list, api, 'xlsx');
                }
            },
            {
                text: crmIcon('file-csv') + ' CSV',
                className: 'btn btn-info btn-sm',
                action: function () {
                    var api = typeof apiGetter === 'function' ? apiGetter() : null;
                    window.location.href = buildStudentExportUrl(list, api, 'csv');
                }
            }
        ];
    }

    function initPartnerStudentTable(options) {
        var initialTotalsScheduled = false;
        var deferStudentCounts = true;
        var countsFetchScheduled = false;

        return $(options.tableSelector).DataTable({
            processing: true,
            serverSide: true,
            pageLength: 25,
            ajax: {
                url: studentDataUrl,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': studentTabCsrfToken()
                },
                data: function (d) {
                    d._token = studentTabCsrfToken();
                    d.partner_id = partnerNumericId;
                    d.list = options.list;
                    if (deferStudentCounts && (d.start === 0 || d.start === '0')) {
                        d.defer_counts = 1;
                    }
                    if (options.statusFilterId) {
                        d.status_filter = normaliseStudentStatusFilterValue(
                            $('#' + options.statusFilterId).val()
                        );
                    }
                },
                error: function (xhr, textStatus) {
                    var responsePreview = xhr && xhr.responseText ? xhr.responseText.substring(0, 500) : '';
                    console.error('[partner-detail] Student tab data request failed:', xhr.status, textStatus, responsePreview);
                }
            },
            columns: studentColumnDefs,
            dom: studentToolbarDom,
            buttons: buildStudentExportButtons(options.list, options.apiGetter),
            searching: true,
            lengthChange: true,
            lengthMenu: [[10, 25, 50, 100, 200, 500], [10, 25, 50, 100, 200, 500]],
            columnDefs: [
                {
                    targets: 0,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row, meta) {
                        return meta.row + 1 + meta.settings._iDisplayStart;
                    }
                },
                {
                    targets: STUDENT_COL_ENROLMENT,
                    render: enrolmentTypeColumnRender(options.enrolmentClass)
                },
                {
                    targets: STUDENT_COL_COMPANY,
                    render: companyNameColumnRender(options.companyClass)
                },
                { targets: STUDENT_COL_APP_ID, visible: false },
                { targets: [STUDENT_COL_NOTE, STUDENT_COL_ACTION], orderable: false, searchable: false }
            ],
            order: [],
            initComplete: function () {
                setupStudentToolbar(this.api(), {
                    columnToggleSelector: options.columnToggleSelector,
                    toolbarHostSelector: options.toolbarHostSelector,
                    statusFilterId: options.statusFilterId || null,
                    serverSideStatusFilter: true
                });
                if (options.statusFilterId) {
                    $('#' + options.statusFilterId).off('change.partnerStudent').on('change.partnerStudent', function () {
                        var api = options.apiGetter();
                        api.ajax.reload();
                        scheduleStudentTotalsRefresh(api, options.list);
                    });
                }
                this.api().on('search.dt', function () {
                    scheduleStudentTotalsRefresh(options.apiGetter(), options.list);
                });
            },
            drawCallback: function () {
                var container = this.api().table().container();
                syncEnrolmentTypeSelects(container);
                syncCompanyNameSelects(container);
                if (!initialTotalsScheduled) {
                    initialTotalsScheduled = true;
                    scheduleStudentTotalsRefresh(options.apiGetter(), options.list, initialTotalsDelayMs);
                }
                if (deferStudentCounts && !countsFetchScheduled) {
                    deferStudentCounts = false;
                    countsFetchScheduled = true;
                    setTimeout(function () {
                        fetchStudentTabCounts(options.apiGetter(), options.list, options);
                    }, countFetchDelayMs);
                }
            }
        });
    }

    var table33 = initPartnerStudentTable({
        list: 'active',
        tableSelector: '.table-3',
        enrolmentClass: 'form-control form-control-sm enrolment-type-field',
        companyClass: 'form-control form-control-sm company-name-field',
        columnToggleSelector: '.student_drop_table_data',
        toolbarHostSelector: '.student_table_panel .student-dt-toolbar-host',
        statusFilterId: 'statusFilter',
        apiGetter: function () { return table33; }
    });

    var table331 = null;
    var inactiveStudentTableInitialized = false;

    function initInactiveStudentTable() {
        if (inactiveStudentTableInitialized || !$('.table-31').length) {
            return;
        }
        inactiveStudentTableInitialized = true;
        table331 = initPartnerStudentTable({
            list: 'inactive',
            tableSelector: '.table-31',
            enrolmentClass: 'form-control form-control-sm enrolment-type-field1',
            companyClass: 'form-control form-control-sm company-name-field1',
            columnToggleSelector: '.student_drop_table_data1',
            toolbarHostSelector: '.student_table_panel1 .student-dt-toolbar-host',
            apiGetter: function () { return table331; }
        });
    }

    $('a#stdinactive-tab').on('shown.bs.tab', function () {
        initInactiveStudentTable();
    });

    function findStudentRowByAppId(table, studentId) {
        if (!table) {
            return [];
        }
        return table.rows().eq(0).filter(function (rowIdx) {
            return table.cell(rowIdx, STUDENT_COL_APP_ID).data() == studentId;
        });
    }

    function updateEnrolmentTypeCell(table, studentId, enrolmentType) {
        var rowIndex = findStudentRowByAppId(table, studentId);

        if (rowIndex.length > 0) {
            table.cell(rowIndex[0], STUDENT_COL_ENROLMENT).data(enrolmentType || '').draw(false);
            syncEnrolmentTypeSelects(table.table().container());
        }
    }

    function updateCompanyNameCell(table, studentId, companyName) {
        var rowIndex = findStudentRowByAppId(table, studentId);

        if (rowIndex.length > 0) {
            table.cell(rowIndex[0], STUDENT_COL_COMPANY).data(companyName || '').draw(false);
            syncCompanyNameSelects(table.table().container());
        }
    }

    function getUpdateCompanyNameUrl() {
        if (typeof App !== 'undefined' && App.getUrl && App.getUrl('updateApplicationCompanyName')) {
            return App.getUrl('updateApplicationCompanyName');
        }
        return '/application/update-company-name';
    }

    $(document).on('change', '.enrolment-type-field, .enrolment-type-field1', function () {
        var $select = $(this);
        if ($select.prop('disabled')) {
            return;
        }
        var applicationId = $select.data('application-id');
        var newValue = $select.val();
        var tableType = $select.hasClass('enrolment-type-field1') ? 'inactive' : 'active';
        var previousValue = $select.attr('data-enrolment-type') || '';

        if ($('.popuploader').length) {
            $('.popuploader').show();
        }

        $.ajax({
            url: App.getUrl('partnersSaveStudentEnrolmentType'),
            method: 'POST',
            data: {
                rowId: applicationId,
                enrolment_type: newValue,
                table: tableType,
                _token: App.getCsrf()
            },
            success: function (response) {
                if (response && response.status) {
                    var table = tableType === 'inactive' ? table331 : table33;
                    if (table) {
                        updateEnrolmentTypeCell(table, response.studentId, response.enrolmentType);
                    }
                    $('.custom-error-msg').html('<span class="alert alert-success">' + response.message + '</span>');
                } else {
                    $select.val(previousValue);
                    $('.custom-error-msg').html('<span class="alert alert-danger">' + (response ? response.message : 'Failed to update enrolment type') + '</span>');
                }
            },
            error: function (error) {
                console.error('Error saving enrolment type:', error);
                $select.val(previousValue);
                $('.custom-error-msg').html('<span class="alert alert-danger">Failed to update enrolment type. Please try again.</span>');
            },
            complete: function () {
                if ($('.popuploader').length) {
                    $('.popuploader').hide();
                }
            }
        });
    });

    $(document).on('change', '.company-name-field, .company-name-field1', function () {
        var $select = $(this);
        if ($select.prop('disabled')) {
            return;
        }
        var applicationId = $select.data('application-id');
        var newValue = $select.val();
        var tableType = $select.hasClass('company-name-field1') ? 'inactive' : 'active';
        var previousValue = $select.attr('data-company-name') || '';

        if ($('.popuploader').length) {
            $('.popuploader').show();
        }

        $.ajax({
            url: getUpdateCompanyNameUrl(),
            method: 'POST',
            data: {
                appid: applicationId,
                company_name: newValue,
                _token: App.getCsrf()
            },
            success: function (response) {
                if (response && response.status) {
                    var table = tableType === 'inactive' ? table331 : table33;
                    if (table) {
                        updateCompanyNameCell(table, applicationId, response.companyName);
                    }
                    $('.custom-error-msg').html('<span class="alert alert-success">' + response.message + '</span>');
                } else {
                    $select.val(previousValue);
                    $('.custom-error-msg').html('<span class="alert alert-danger">' + (response ? response.message : 'Failed to update company name') + '</span>');
                }
            },
            error: function (error) {
                console.error('Error saving company name:', error);
                $select.val(previousValue);
                $('.custom-error-msg').html('<span class="alert alert-danger">Failed to update company name. Please try again.</span>');
            },
            complete: function () {
                if ($('.popuploader').length) {
                    $('.popuploader').hide();
                }
            }
        });
    });

    $(document).on('change', '.note-field', function () {
        var studentid = $(this).attr('data-studentid');
        var newValue = $(this).val();
        $.ajax({
            url: App.getUrl('partnersSaveStudentNote'),
            method: 'POST',
            dataType: 'json',
            data: { rowId: studentid, note: newValue, _token: App.getCsrf()},
            success: function (response) {
                if (response && response.status) {
                    const studentId = response.studentId;
                    const studentNote = response.studentNote;
                    const rowIndex = findStudentRowByAppId(table33, studentId);

                    if (rowIndex.length > 0) {
                        table33.cell(rowIndex[0], STUDENT_COL_NOTE).data(
                            '<textarea class="note-field" data-studentid="' + studentId + '">' + $('<div>').text(studentNote || '').html() + '</textarea>'
                        ).draw(false);
                    }
                    $('.custom-error-msg').html('<span class="alert alert-success">' + (response.message || 'Student note saved successfully.') + '</span>');
                } else {
                    $('.custom-error-msg').html('<span class="alert alert-danger">' + ((response && response.message) || 'Failed to save note') + '</span>');
                }
            },
            error: function (error) {
                console.error('Error saving note:', error);
                $('.custom-error-msg').html('<span class="alert alert-danger">Failed to save note. Please try again.</span>');
            }
        });
    });

    $(document).on('change', '.note-field1', function () {
        var studentid = $(this).attr('data-studentid');
        var newValue = $(this).val();
        $.ajax({
            url: App.getUrl('partnersSaveStudentNote'),
            method: 'POST',
            dataType: 'json',
            data: { rowId: studentid, note: newValue, _token: App.getCsrf()},
            success: function (response) {
                if (response && response.status) {
                    const studentId = response.studentId;
                    const studentNote = response.studentNote;
                    if (!table331) {
                        return;
                    }
                    const rowIndex = findStudentRowByAppId(table331, studentId);

                    if (rowIndex.length > 0) {
                        table331.cell(rowIndex[0], STUDENT_COL_NOTE).data(
                            '<textarea class="note-field1" data-studentid="' + studentId + '">' + $('<div>').text(studentNote || '').html() + '</textarea>'
                        ).draw(false);
                    }
                    $('.custom-error-msg').html('<span class="alert alert-success">' + (response.message || 'Student note saved successfully.') + '</span>');
                } else {
                    $('.custom-error-msg').html('<span class="alert alert-danger">' + ((response && response.message) || 'Failed to save note') + '</span>');
                }
            },
            error: function (error) {
                console.error('Error saving note:', error);
                $('.custom-error-msg').html('<span class="alert alert-danger">Failed to save note. Please try again.</span>');
            }
        });
    });
});

})(); // End async wrapper
