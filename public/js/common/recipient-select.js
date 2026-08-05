/**
 * Recipient Select — shared Tom Select AJAX recipient picker (email modals, notes CC, etc.).
 *
 * Phase 3: Tom Select backend via tomselect-init.js (legacy option mapping retained for callers).
 *
 * Usage:
 *   RecipientSelect.init('#emailmodal .js-data-example-ajax', { url: '/clients/get-recipients', dropdownParent: '#emailmodal' });
 *   RecipientSelect.setClientEmailRecipient('#emailmodal .js-data-example-ajax', id, name, email, 'Client', { dropdownParent: '#emailmodal' });
 *   var collected = RecipientSelect.collectFromCheckboxes('.cb-element', 'Client');
 *   RecipientSelect.setData('#emailmodal .js-data-example-ajax', collected.entries, { dropdownParent: '#emailmodal' });
 */
(function (window) {
    'use strict';

    function get$() {
        return window.jQuery;
    }

    function resolveElement(el) {
        if (!el) {
            return null;
        }
        if (el instanceof Element) {
            return el;
        }
        if (typeof el === 'string') {
            return document.querySelector(el);
        }
        var $ = get$();
        if ($ && (el instanceof $ || el.jquery !== undefined)) {
            return el.length ? el[0] : null;
        }
        if (el.nodeType === 1) {
            return el;
        }
        return null;
    }

    function resolveUrl(options) {
        options = options || {};
        if (options.url) {
            return options.url;
        }
        if (typeof App !== 'undefined' && typeof App.getUrl === 'function') {
            return App.getUrl('clientGetRecipients')
                || App.getUrl('clientsGetRecipients')
                || App.getUrl('getRecipients')
                || ((App.getUrl('siteUrl') || '') + '/clients/get-recipients');
        }
        if (window.AppConfig && window.AppConfig.urls) {
            if (window.AppConfig.urls.getRecipients) {
                return window.AppConfig.urls.getRecipients;
            }
            if (window.AppConfig.urls.clientsGetRecipients) {
                return window.AppConfig.urls.clientsGetRecipients;
            }
        }
        return '/clients/get-recipients';
    }

    function statusBadgeClass(status) {
        return status === 'Archived'
            ? 'badge bg-secondary ts-result-row__statistics'
            : 'badge bg-warning text-dark ts-result-row__statistics';
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function buildRecipientHtml(name, email, status) {
        var badgeClass = statusBadgeClass(status || '');
        name = escapeHtml(name || '');
        email = escapeHtml(email || '');
        status = escapeHtml(status || '');
        return (
            "<div class='ts-result-row ag-flex ag-space-between ag-align-center'>" +
            "<div class='ag-flex ag-align-start'>" +
            "<div class='ag-flex ag-flex-column col-hr-1'>" +
            "<div class='ag-flex'><span class='ts-result-row__title text-semi-bold'>" + name + "</span>&nbsp;</div>" +
            "<div class='ag-flex ag-align-center'><small class='ts-result-row__description'>" + email + "</small></div>" +
            "</div></div>" +
            "<div class='ag-flex ag-flex-column ag-align-end'>" +
            "<span class='" + badgeClass + "'>" + status + "</span>" +
            "</div></div>"
        );
    }

    function buildRecipientEntry(id, name, email, status) {
        name = name || '';
        return {
            id: id,
            text: name,
            name: name,
            email: email || '',
            status: status || '',
            html: buildRecipientHtml(name, email, status),
            title: name
        };
    }

    /** Tom Select treats strings without '<' as CSS selectors — wrap display labels. */
    function wrapTomSelectLabel(text) {
        text = text == null ? '' : String(text);
        if (!text) {
            return '';
        }
        return '<span>' + escapeHtml(text) + '</span>';
    }

    function formatRepo(repo) {
        if (!repo) {
            return '';
        }
        if (repo.loading) {
            return wrapTomSelectLabel(repo.text);
        }
        if (repo.html) {
            return repo.html;
        }

        var $ = get$();
        if (!$) {
            return buildRecipientHtml(
                repo.name || repo.text || '',
                repo.email || '',
                repo.status || ''
            );
        }

        var $container = $(
            "<div class='ts-result-row ag-flex ag-space-between ag-align-center'>" +
            "<div class='ag-flex ag-align-start'>" +
            "<div class='ag-flex ag-flex-column col-hr-1'>" +
            "<div class='ag-flex'><span class='ts-result-row__title text-semi-bold'></span>&nbsp;</div>" +
            "<div class='ag-flex ag-align-center'><small class='ts-result-row__description'></small></div>" +
            "</div></div>" +
            "<div class='ag-flex ag-flex-column ag-align-end'>" +
            "<span class='" + statusBadgeClass(repo.status) + "'></span>" +
            "</div></div>"
        );

        $container.find('.ts-result-row__title').text(repo.name || repo.text || '');
        $container.find('.ts-result-row__description').text(repo.email || '');
        $container.find('.ts-result-row__statistics').text(repo.status || '');

        return $container;
    }

    function formatRepoSelection(repo) {
        return wrapTomSelectLabel(repo && (repo.name || repo.text));
    }

    function isEnhanced(element) {
        return !!(element && typeof window.isTomSelect === 'function' && window.isTomSelect(element));
    }

    function destroyRecipientSelect(el) {
        if (typeof window.destroyEnhancedSelect === 'function') {
            window.destroyEnhancedSelect(el);
            return;
        }
        var element = resolveElement(el);
        if (!element) {
            return;
        }
        if (typeof window.destroyTomSelect === 'function') {
            window.destroyTomSelect(element);
        }
    }

    /**
     * Clear selection/options without destroying Tom Select (avoids re-init races on modal reopen).
     */
    function clearRecipientSelect(el) {
        var element = resolveElement(el);
        if (!element) {
            return;
        }
        var instance = element.tomselect;
        if (!instance) {
            // Native multi/select clear
            if (element.multiple) {
                Array.prototype.forEach.call(element.options, function (opt) {
                    opt.selected = false;
                });
            } else {
                element.value = '';
            }
            return;
        }
        try {
            if (typeof instance.clear === 'function') {
                instance.clear(true);
            }
        } catch (e) { /* ignore */ }
        try {
            if (instance.options) {
                Object.keys(instance.options).forEach(function (key) {
                    if (key === '') {
                        return;
                    }
                    instance.removeOption(key, true);
                });
            }
            if (instance.control_input) {
                instance.control_input.value = '';
            }
            if (typeof instance.refreshOptions === 'function') {
                instance.refreshOptions(false);
            }
        } catch (e2) { /* ignore */ }
    }

    function buildAjaxOptions(url, options) {
        var ajax = {
            url: url,
            dataType: 'json',
            processResults: function (data) {
                return { results: (data && data.items) ? data.items : [] };
            },
            cache: true
        };

        if (options.csrf && typeof App !== 'undefined' && typeof App.getCsrf === 'function') {
            ajax.headers = { 'X-CSRF-TOKEN': App.getCsrf() };
        }

        if (options.minimumInputLength) {
            ajax.delay = options.delay || 250;
            ajax.data = function (params) {
                return { q: params.term, page: params.page || 1 };
            };
        }

        return ajax;
    }

    function ensureMultipleAttribute(element, isMultiple) {
        if (!isMultiple || !element) {
            return;
        }
        if (!element.hasAttribute('multiple')) {
            element.setAttribute('multiple', 'multiple');
        }
    }

    function resolveRecipientDropdownParent(options) {
        options = options || {};
        // Check-in modal: omit dropdownParent so the menu stays on .ts-wrapper (below the control).
        // Reparenting to .modal-content breaks placement — same pattern as assignee Tom Select.
        if (options.dropdownParent === '#checkinmodal' || options.omitDropdownParent === true) {
            return null;
        }
        // Bootstrap popovers: keep menu on .ts-wrapper so popover z-index CSS applies.
        if (options.dropdownParent) {
            var parentEl = resolveElement(options.dropdownParent);
            if (parentEl && (
                (parentEl.classList && parentEl.classList.contains('popover')) ||
                (parentEl.closest && parentEl.closest('.popover'))
            )) {
                return null;
            }
        }
        // Compose email modal — body avoids hidden-modal reparenting bugs
        return document.body;
    }

    function clearUnselectedRecipientOptions(instance) {
        if (!instance || !instance.options) {
            return;
        }
        Object.keys(instance.options).forEach(function (key) {
            if (key === '' || (instance.items && instance.items.indexOf(key) !== -1)) {
                return;
            }
            instance.removeOption(key, true);
        });
    }

    function buildRecipientRemoteLoad(url, options, loadState) {
        options = options || {};
        loadState = loadState || { seq: 0, xhr: null };
        var minimumInputLength = options.minimumInputLength || 0;

        return function (query, callback) {
            var self = this;

            if (!query || query.length < minimumInputLength) {
                callback();
                return;
            }

            if (!window.jQuery || !window.jQuery.ajax) {
                callback();
                return;
            }

            if (loadState.xhr && typeof loadState.xhr.abort === 'function') {
                loadState.xhr.abort();
            }

            clearUnselectedRecipientOptions(self);

            var seq = ++loadState.seq;

            var xhr = window.jQuery.ajax({
                url: url,
                dataType: 'json',
                cache: false,
                data: { q: query },
                success: function (data) {
                    // Ignore superseded responses — never call callback() or we wipe newer results.
                    if (seq !== loadState.seq) {
                        return;
                    }

                    var results = (data && data.items) ? data.items : [];
                    var normalized = results.map(function (item) {
                        var id = item && item.id != null ? String(item.id) : '';
                        return Object.assign({}, item, {
                            id: id,
                            text: (item && (item.text || item.name)) || id,
                            _recipientLoadToken: seq
                        });
                    });

                    clearUnselectedRecipientOptions(self);
                    callback(normalized);
                },
                error: function (_jqXHR, _textStatus) {
                    // Only the latest in-flight request may clear the loader / show empty state.
                    // Aborted older requests must not run after a newer success (race fix).
                    if (seq !== loadState.seq) {
                        return;
                    }
                    callback();
                },
                complete: function () {
                    if (loadState.xhr === xhr) {
                        loadState.xhr = null;
                    }
                }
            });

            loadState.xhr = xhr;
        };
    }

    function buildInitOptions(url, options) {
        options = options || {};
        var isMultiple = options.multiple !== false;
        var dropdownParent = resolveRecipientDropdownParent(options);
        var loadState = { seq: 0, xhr: null };

        var initOpts = {
            multiple: isMultiple,
            closeOnSelect: false,
            width: '100%',
            valueField: 'id',
            labelField: 'text',
            load: buildRecipientRemoteLoad(url, options, loadState),
            loadThrottle: options.delay || 250,
            searchField: options.searchField || ['text', 'name', 'email', 'phone', 'client_id'],
            // Only show options from the latest successful API response (avoids stale partial matches).
            score: function () {
                return function (item) {
                    return item && item._recipientLoadToken === loadState.seq ? 1 : 0;
                };
            },
            templateResult: formatRepo,
            templateSelection: formatRepoSelection
        };

        if (dropdownParent) {
            initOpts.dropdownParent = dropdownParent;
        }

        if (options.minimumInputLength) {
            initOpts.minimumInputLength = options.minimumInputLength;
        }

        if (options.placeholder) {
            initOpts.placeholder = options.placeholder;
        }

        return initOpts;
    }

    function buildStaticOptions(options) {
        options = options || {};
        var isMultiple = options.multiple !== false;
        var dropdownParent = resolveRecipientDropdownParent(options);

        var staticOpts = {
            multiple: isMultiple,
            closeOnSelect: false,
            width: '100%',
            escapeMarkup: function (markup) {
                return markup;
            },
            templateResult: function (data) {
                return data.html || wrapTomSelectLabel(data.text || data.name || '');
            },
            templateSelection: function (data) {
                return wrapTomSelectLabel(data.text || data.name || '');
            }
        };

        if (dropdownParent) {
            staticOpts.dropdownParent = dropdownParent;
        }

        return staticOpts;
    }

    function collectRelatedFileEntries() {
        var entries = [];

        if (window.PageConfig && Array.isArray(window.PageConfig.relatedFilesData) && window.PageConfig.relatedFilesData.length) {
            window.PageConfig.relatedFilesData.forEach(function (file) {
                entries.push(buildRecipientEntry(file.id, file.name, file.email, file.status || 'Client'));
            });
            return entries;
        }

        if (typeof App !== 'undefined' && typeof App.getPageConfig === 'function') {
            var pageData = App.getPageConfig('relatedFilesData');
            if (pageData && pageData.length) {
                pageData.forEach(function (file) {
                    entries.push(buildRecipientEntry(file.id, file.name, file.email, file.status || 'Client'));
                });
                return entries;
            }
        }

        var $ = get$();
        if ($) {
            $('.relatedfile').each(function () {
                var $item = $(this);
                var id = $item.data('id');
                if (id) {
                    entries.push(buildRecipientEntry(
                        id,
                        $item.data('name') || '',
                        $item.data('email') || '',
                        'Client'
                    ));
                }
            });
        }

        return entries;
    }

    /**
     * Related Files field on client/leads create & edit — AJAX search with optional preloaded selections.
     */
    function initRelatedFiles(options) {
        options = options || {};
        var selector = options.selector || 'select[name="related_files[]"]';
        var element = resolveElement(selector);

        if (!element) {
            return null;
        }

        if (typeof window.initTomSelect !== 'function') {
            console.warn('[RecipientSelect] initTomSelect not available for related files');
            return null;
        }

        if (options.force || options.reinit) {
            destroyRecipientSelect(element);
        } else if (isEnhanced(element)) {
            return element.tomselect || null;
        }

        var url = resolveUrl(options);
        var isMultiple = options.multiple !== false;
        ensureMultipleAttribute(element, isMultiple);

        var entries = options.entries || collectRelatedFileEntries();
        var initOpts = buildInitOptions(url, Object.assign({ minimumInputLength: 1 }, options));

        if (entries.length) {
            initOpts.data = entries;
        }

        var instance = window.initTomSelect(element, initOpts);

        if (instance && entries.length) {
            var ids = entries.map(function (entry) {
                return String(entry.id);
            });
            if (typeof window.setEnhancedSelectValue === 'function') {
                window.setEnhancedSelectValue(element, ids, true);
            } else {
                instance.setValue(ids, true);
            }
        }

        return instance;
    }

    function ensureRelatedFiles(options, maxAttempts) {
        var limit = maxAttempts || 50;
        var attempts = 0;
        var selector = (options && options.selector) || 'select[name="related_files[]"]';

        function check() {
            attempts += 1;
            var element = resolveElement(selector);
            if (!element) {
                return;
            }
            if (typeof TomSelect === 'undefined' || typeof window.initTomSelect !== 'function') {
                if (attempts < limit) {
                    setTimeout(check, 50);
                }
                return;
            }
            if (!isEnhanced(element)) {
                initRelatedFiles(options);
            }
        }

        check();
    }

    function initRecipientSelect(el, options) {
        options = options || {};

        if (typeof TomSelect === 'undefined' || typeof window.initTomSelect !== 'function') {
            console.warn('[RecipientSelect] Tom Select not available');
            return null;
        }

        var element = resolveElement(el);
        if (!element) {
            return null;
        }

        if (options.force || options.reinit) {
            destroyRecipientSelect(element);
        } else if (isEnhanced(element)) {
            return element.tomselect || null;
        }

        var isMultiple = options.multiple !== false;
        ensureMultipleAttribute(element, isMultiple);

        return window.initTomSelect(element, buildInitOptions(resolveUrl(options), options));
    }

    function initRecipientSelects(selector, options) {
        var instances = [];
        document.querySelectorAll(selector).forEach(function (element) {
            var instance = initRecipientSelect(element, options);
            if (instance) {
                instances.push(instance);
            }
        });
        return instances;
    }

    function reinitRecipientSelect(el, options) {
        if (typeof window.reinitTomSelect === 'function') {
            var element = resolveElement(el);
            if (!element) {
                return null;
            }
            ensureMultipleAttribute(element, (options && options.multiple !== false));
            return window.reinitTomSelect(element, buildInitOptions(resolveUrl(options || {}), options || {}));
        }
        options = options || {};
        options.reinit = true;
        return initRecipientSelect(el, options);
    }

    function setRecipientSelectData(el, entries, options) {
        options = options || {};

        if (typeof TomSelect === 'undefined' || typeof window.initTomSelect !== 'function') {
            return null;
        }

        var element = resolveElement(el);
        if (!element) {
            return null;
        }

        var normalizedEntries = (entries || []).map(function (e) {
            var id = e.id != null ? String(e.id) : '';
            return Object.assign({}, e, {
                id: id,
                text: e.text || e.name || e.email || id,
                name: e.name || e.text || ''
            });
        });
        var ids = normalizedEntries.map(function (e) {
            return e.id;
        }).filter(function (id) {
            return id !== '';
        });
        var isMultiple = options.multiple !== false;
        ensureMultipleAttribute(element, isMultiple);

        var initOpts = buildInitOptions(resolveUrl(options), options);
        if (normalizedEntries.length) {
            initOpts.data = normalizedEntries;
        }

        function applyValues(instance) {
            if (!instance) {
                return null;
            }
            normalizedEntries.forEach(function (entry) {
                if (entry.id && !instance.options[entry.id]) {
                    instance.addOption(entry);
                }
            });
            if (ids.length) {
                if (typeof window.setEnhancedSelectValue === 'function') {
                    window.setEnhancedSelectValue(element, ids, false);
                } else {
                    instance.setValue(ids, false);
                }
            }
            return instance;
        }

        if (options.force || options.reinit) {
            destroyRecipientSelect(element);
        } else if (element.tomselect) {
            return applyValues(element.tomselect);
        }

        var instance = window.initTomSelect(element, initOpts);
        if (!instance) {
            initOpts.dropdownParent = document.body;
            instance = window.initTomSelect(element, initOpts);
        }

        return applyValues(instance);
    }

    function setClientEmailRecipient(el, id, name, email, status, options) {
        setRecipientSelectData(el, [buildRecipientEntry(id, name, email, status || 'Client')], options);
    }

    function getRecipientSelectValue(el) {
        var element = resolveElement(el);
        if (!element) {
            return null;
        }
        if (element.tomselect) {
            var value = element.tomselect.getValue();
            if (Array.isArray(value)) {
                return value;
            }
            return value ? [value] : [];
        }
        var $ = get$();
        if ($) {
            var nativeVal = $(element).val();
            if (Array.isArray(nativeVal)) {
                return nativeVal;
            }
            return nativeVal ? [nativeVal] : [];
        }
        return null;
    }

    function collectFromCheckboxes(checkboxSelector, statusLabel) {
        var entries = [];
        var $ = get$();
        if (!$) {
            return { ids: [], entries: [] };
        }

        $(checkboxSelector + ':checked').each(function () {
            var id = $(this).attr('data-id');
            var email = $(this).attr('data-email');
            var name = $(this).attr('data-name');
            entries.push(buildRecipientEntry(id, name, email, statusLabel || 'Client'));
        });

        return {
            ids: entries.map(function (e) {
                return e.id;
            }),
            entries: entries
        };
    }

    // Only fill globals when no page-level definition is already present.
    // recipient-select.js is deferred, so it runs after inline scripts.
    if (!window.formatRepo) {
        window.formatRepo = formatRepo;
    }
    if (!window.formatRepoSelection) {
        window.formatRepoSelection = formatRepoSelection;
    }

    var api = {
        resolveUrl: resolveUrl,
        buildHtml: buildRecipientHtml,
        buildEntry: buildRecipientEntry,
        formatRepo: formatRepo,
        formatRepoSelection: formatRepoSelection,
        destroy: destroyRecipientSelect,
        clear: clearRecipientSelect,
        init: initRecipientSelect,
        initAll: initRecipientSelects,
        reinit: reinitRecipientSelect,
        setData: setRecipientSelectData,
        setClientEmailRecipient: setClientEmailRecipient,
        getValue: getRecipientSelectValue,
        collectFromCheckboxes: collectFromCheckboxes,
        collectRelatedFileEntries: collectRelatedFileEntries,
        initRelatedFiles: initRelatedFiles,
        ensureRelatedFiles: ensureRelatedFiles
    };

    function waitForRecipientSelect(maxAttempts) {
        var limit = maxAttempts || 200;

        return new Promise(function (resolve) {
            var attempts = 0;

            function check() {
                attempts += 1;
                if (typeof window.RecipientSelect !== 'undefined') {
                    resolve(window.RecipientSelect);
                } else if (attempts >= limit) {
                    console.warn('[waitForRecipientSelect] RecipientSelect not loaded after timeout');
                    resolve(null);
                } else {
                    setTimeout(check, 50);
                }
            }

            check();
        });
    }

    window.waitForRecipientSelect = waitForRecipientSelect;
    window.RecipientSelect = api;
    window.BansalRecipientSelect = api;

    /** Pages without email-handlers.js: apply queued compose recipients on modal shown. */
    function applyPendingComposeRecipientsFallback() {
        if (typeof window.applyComposeRecipientsOnShown === 'function') {
            return;
        }
        setTimeout(function () {
            var $ = get$();
            if (!$) {
                return;
            }
            var $modal = $('#emailmodal');
            if (!$modal.length) {
                return;
            }
            var pending = $modal.data('composeRecipientsPending');
            if (pending && pending.length) {
                $modal.removeData('composeRecipientsPending');
                setRecipientSelectData('#emailmodal .js-data-example-ajax', pending, { dropdownParent: '#emailmodal' });
            }
        }, 50);
    }

    function bindComposeEmailPendingRecipients() {
        var $ = get$();
        if (!$ || window._composeEmailPendingBound) {
            return;
        }
        window._composeEmailPendingBound = true;
        $(document).on('shown.bs.modal', '#emailmodal', function () {
            applyPendingComposeRecipientsFallback();
        });
    }

    bindComposeEmailPendingRecipients();
})(window);
