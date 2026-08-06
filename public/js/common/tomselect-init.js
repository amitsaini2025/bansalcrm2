/**
 * Tom Select utilities — init helpers and legacy option mapping for migrated pages.
 *
 * Usage:
 *   initTomSelect('#my-select', { placeholder: 'Choose…', allowClear: true });
 *   destroyTomSelect('#my-select');
 *   isTomSelect('#my-select');
 *   getEnhancementWrapper('#my-select');
 *   await waitForTomSelect();
 *
 * Migrated elements get classes `tomselect` + `tomselect-migrated`.
 */
(function (window) {
    'use strict';

    /**
     * Legacy caller option keys — mapped to Tom Select, not passed through verbatim.
     */
    var LEGACY_OPTION_KEYS = {
        ajax: true,
        data: true,
        templateResult: true,
        templateSelection: true,
        escapeMarkup: true,
        width: true,
        containerCssClass: true,
        dropdownCssClass: true,
        allowClear: true,
        minimumInputLength: true,
        tags: true,
        multiple: true,
        closeOnSelect: true,
        minimumResultsForSearch: true,
        dropdownAutoWidth: true,
        language: true,
        matcher: true,
        sorter: true,
        maximumSelectionLength: true,
        tokenSeparators: true
    };

    function isJQuery(obj) {
        return !!(obj && window.jQuery && (obj.jquery !== undefined || obj instanceof window.jQuery));
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
        if (isJQuery(el) && el.length) {
            return el[0];
        }
        if (el.nodeType === 1) {
            return el;
        }
        return null;
    }

    function ensurePlugin(plugins, name) {
        var list = plugins ? plugins.slice() : [];
        if (list.indexOf(name) === -1) {
            list.push(name);
        }
        return list;
    }

    /**
     * Tom Select passes render output through an internal helper that uses
     * document.querySelector() when the string does not contain '<'.
     * Plain display text (e.g. recipient names like "New .") must be wrapped in HTML.
     */
    function wrapPlainTextForTomSelect(text, escape) {
        if (text == null || text === '') {
            return '';
        }
        var str = String(text);
        if (str.indexOf('<') !== -1) {
            return str;
        }
        var safe = typeof escape === 'function' ? escape(str) : str;
        return '<span>' + safe + '</span>';
    }

    /** Handles strings, DOM nodes, and jQuery objects (common in this codebase). */
    function normalizeTemplateOutput(rendered, escape, data) {
        if (rendered == null) {
            return '';
        }
        if (typeof rendered === 'string') {
            return wrapPlainTextForTomSelect(rendered, escape);
        }
        if (isJQuery(rendered)) {
            var node = rendered[0];
            if (!node) {
                return '';
            }
            if (node.outerHTML) {
                return node.outerHTML;
            }
            return wrapPlainTextForTomSelect(rendered.text() || '', escape);
        }
        if (rendered instanceof Element) {
            return rendered.outerHTML;
        }
        if (rendered && typeof rendered.text === 'string') {
            return wrapPlainTextForTomSelect(rendered.text, escape);
        }
        return wrapPlainTextForTomSelect(data && data.text ? data.text : '', escape);
    }

    /**
     * Mark render callbacks that already normalize plain text so we do not
     * wrap/escape them again (avoids double-escaping &amp; etc.).
     */
    function markRenderSafe(fn) {
        if (typeof fn === 'function') {
            fn._tomSelectPlainTextSafe = true;
        }
        return fn;
    }

    /**
     * Wrap a custom render.option/item so plain-text labels (e.g. "New .")
     * become HTML. HTML-containing output is left unchanged.
     */
    function wrapRenderCallback(fn) {
        if (typeof fn !== 'function' || fn._tomSelectPlainTextSafe) {
            return fn;
        }
        return markRenderSafe(function (data, escape) {
            return normalizeTemplateOutput(fn(data, escape), escape, data);
        });
    }

    /**
     * Harden any custom option/item render on the final config.
     * Default Tom Select templates are untouched when render is absent.
     * Shallow-clones `render` when wrapping so caller option objects are not mutated.
     */
    function ensureSafeRenderOutput(config) {
        if (!config || !config.render || typeof config.render !== 'object') {
            return;
        }
        var optionFn = config.render.option;
        var itemFn = config.render.item;
        var wrapOption = typeof optionFn === 'function' && !optionFn._tomSelectPlainTextSafe;
        var wrapItem = typeof itemFn === 'function' && !itemFn._tomSelectPlainTextSafe;
        if (!wrapOption && !wrapItem) {
            return;
        }
        config.render = Object.assign({}, config.render);
        if (wrapOption) {
            config.render.option = wrapRenderCallback(optionFn);
        }
        if (wrapItem) {
            config.render.item = wrapRenderCallback(itemFn);
        }
    }

    function stripLegacyOptionKeys(config) {
        Object.keys(LEGACY_OPTION_KEYS).forEach(function (key) {
            delete config[key];
        });
    }

    function mapLegacyOptions(options) {
        if (!options || typeof options !== 'object') {
            return {};
        }

        var mapped = {};
        var key;

        for (key in options) {
            if (!Object.prototype.hasOwnProperty.call(options, key)) {
                continue;
            }

            switch (key) {
                case 'dropdownParent':
                    if (!options.dropdownParent || options.dropdownParent === 'body' || options.dropdownParent === document.body) {
                        mapped.dropdownParent = 'body';
                    } else {
                        var parentEl = resolveElement(options.dropdownParent);
                        mapped.dropdownParent = parentEl || 'body';
                    }
                    break;
                case 'allowClear':
                    if (options.allowClear) {
                        mapped.plugins = ensurePlugin(mapped.plugins, 'clear_button');
                    }
                    break;
                case 'plugins':
                    if (Array.isArray(options.plugins)) {
                        options.plugins.forEach(function (plugin) {
                            mapped.plugins = ensurePlugin(mapped.plugins, plugin);
                        });
                    }
                    break;
                case 'minimumInputLength':
                    mapped._minimumInputLength = options.minimumInputLength;
                    break;
                case 'ajax':
                    mapped._compatAjax = options.ajax;
                    break;
                case 'data':
                    mapped._compatData = options.data;
                    break;
                case 'templateResult':
                    mapped.render = mapped.render || {};
                    mapped.render.option = markRenderSafe(function (data, escape) {
                        return normalizeTemplateOutput(options.templateResult(data), escape, data);
                    });
                    break;
                case 'templateSelection':
                    mapped.render = mapped.render || {};
                    mapped.render.item = markRenderSafe(function (data, escape) {
                        return normalizeTemplateOutput(options.templateSelection(data), escape, data);
                    });
                    break;
                case 'width':
                    mapped._compatWidth = options.width;
                    break;
                case 'escapeMarkup':
                    if (typeof options.escapeMarkup === 'function' && !options.templateResult) {
                        mapped.render = mapped.render || {};
                        mapped.render.option = markRenderSafe(function (data, escape) {
                            return wrapPlainTextForTomSelect(
                                options.escapeMarkup(data.text || ''),
                                escape
                            );
                        });
                        mapped.render.item = markRenderSafe(function (data, escape) {
                            return wrapPlainTextForTomSelect(
                                options.escapeMarkup(data.text || ''),
                                escape
                            );
                        });
                    }
                    break;
                case 'containerCssClass':
                    mapped.wrapperClass = options.containerCssClass;
                    break;
                case 'dropdownCssClass':
                    mapped.dropdownClass = options.dropdownCssClass;
                    break;
                case 'tags':
                    if (options.tags) {
                        mapped.create = true;
                    }
                    break;
                case 'closeOnSelect':
                    mapped.closeAfterSelect = options.closeOnSelect !== false;
                    break;
                case 'maximumSelectionLength':
                    mapped.maxItems = options.maximumSelectionLength;
                    break;
                case 'minimumResultsForSearch':
                    if (options.minimumResultsForSearch === Infinity) {
                        mapped._disableSearch = true;
                    }
                    break;
                default:
                    if (!LEGACY_OPTION_KEYS[key]) {
                        mapped[key] = options[key];
                    }
            }
        }

        return mapped;
    }

    function applyWidth(instance, width) {
        if (!width || !instance || !instance.wrapper) {
            return;
        }
        if (width === '100%' || width === 'resolve' || width === 'style') {
            instance.wrapper.style.width = '100%';
        } else {
            instance.wrapper.style.width = width;
        }
    }

    function ensurePlaceholderOption(element, placeholder) {
        if (!placeholder || element.querySelector('option[value=""]')) {
            return;
        }
        var option = document.createElement('option');
        option.value = '';
        option.textContent = placeholder;
        element.insertBefore(option, element.firstChild);
    }

    function deliverAjaxResults(ajax, response, query, callback) {
        var processed = ajax.processResults
            ? ajax.processResults(response, { term: query, page: 1 })
            : response;
        var results = processed && processed.results ? processed.results : processed;
        callback(Array.isArray(results) ? results : []);
    }

    function buildAjaxQueryString(data) {
        if (data == null) {
            return '';
        }
        if (typeof data === 'string') {
            return data;
        }
        if (typeof data !== 'object') {
            return '';
        }
        var parts = [];
        Object.keys(data).forEach(function (key) {
            var value = data[key];
            if (value == null) {
                return;
            }
            parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(String(value)));
        });
        return parts.join('&');
    }

    function loadAjaxViaFetch(ajax, requestData, query, callback) {
        var method = String(ajax.type || ajax.method || 'GET').toUpperCase();
        var url = ajax.url || '';
        var headers = Object.assign({}, ajax.headers || {});
        var fetchOpts = {
            method: method,
            credentials: 'same-origin',
            headers: headers
        };

        if (method === 'GET' || method === 'HEAD') {
            var qs = buildAjaxQueryString(requestData);
            if (qs) {
                url += (url.indexOf('?') === -1 ? '?' : '&') + qs;
            }
        } else {
            if (!headers['Content-Type'] && !headers['content-type']) {
                headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
                fetchOpts.headers = headers;
            }
            fetchOpts.body = typeof requestData === 'string'
                ? requestData
                : buildAjaxQueryString(requestData);
        }

        fetch(url, fetchOpts)
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                var dataType = (ajax.dataType || 'json').toLowerCase();
                if (dataType === 'json') {
                    return response.json();
                }
                return response.text();
            })
            .then(function (response) {
                deliverAjaxResults(ajax, response, query, callback);
            })
            .catch(function () {
                callback();
            });
    }

    function buildAjaxLoader(ajax, minimumInputLength) {
        var $ = window.jQuery;

        return function (query, callback) {
            if (query.length < (minimumInputLength || 0)) {
                callback();
                return;
            }

            var requestData = typeof ajax.data === 'function'
                ? ajax.data({ term: query, page: 1 })
                : { q: query };

            if ($ && $.ajax) {
                var ajaxSettings = {
                    url: ajax.url,
                    dataType: ajax.dataType || 'json',
                    data: requestData,
                    success: function (response) {
                        deliverAjaxResults(ajax, response, query, callback);
                    },
                    error: function () {
                        callback();
                    }
                };

                if (ajax.type || ajax.method) {
                    ajaxSettings.type = ajax.type || ajax.method;
                }

                if (ajax.headers) {
                    ajaxSettings.headers = ajax.headers;
                }

                $.ajax(ajaxSettings);
                return;
            }

            if (typeof fetch === 'function') {
                loadAjaxViaFetch(ajax, requestData, query, callback);
                return;
            }

            console.warn('[initTomSelect] jQuery.ajax or fetch required for ajax option mapping');
            callback();
        };
    }

    function markMigration(element) {
        element.classList.add('tomselect', 'tomselect-migrated');
        element.setAttribute('data-enhanced', 'tomselect');
    }

    function unmarkMigration(element) {
        element.classList.remove('tomselect', 'tomselect-migrated');
        element.removeAttribute('data-enhanced');
    }

    function initTomSelect(el, options) {
        if (typeof TomSelect === 'undefined') {
            console.warn('[initTomSelect] Tom Select is not loaded');
            return null;
        }

        var element = resolveElement(el);
        if (!element) {
            console.warn('[initTomSelect] Invalid element');
            return null;
        }

        if (element.tomselect) {
            return element.tomselect;
        }

        options = options || {};

        if (options.multiple && !element.hasAttribute('multiple')) {
            element.setAttribute('multiple', 'multiple');
        }

        ensurePlaceholderOption(element, options.placeholder);
        markMigration(element);

        var config = mapLegacyOptions(options);
        var minimumInputLength = config._minimumInputLength || 0;

        if (config._compatAjax) {
            config.valueField = config.valueField || 'id';
            config.labelField = config.labelField || 'text';
            config.searchField = config.searchField || ['text', 'name', 'email'];
            config.load = buildAjaxLoader(config._compatAjax, minimumInputLength);
            config.loadThrottle = config.loadThrottle || config._compatAjax.delay || 250;
        } else if (config.load) {
            config.valueField = config.valueField || 'id';
            config.labelField = config.labelField || 'text';
        }

        if (options.multiple || element.hasAttribute('multiple')) {
            config.plugins = ensurePlugin(config.plugins, 'remove_button');
        }

        if (config._compatData) {
            config.valueField = config.valueField || 'value';
            config.labelField = config.labelField || 'text';
            config.searchField = config.searchField || ['text', 'name', 'email'];
            config.options = config._compatData.map(function (item) {
                var id = item.id != null ? String(item.id) : (item.value != null ? String(item.value) : '');
                var opt = {
                    value: id,
                    id: id,
                    text: item.text || item.name || '',
                    html: item.html
                };
                if (item.name != null) {
                    opt.name = item.name;
                }
                if (item.email != null) {
                    opt.email = item.email;
                }
                if (item.status != null) {
                    opt.status = item.status;
                }
                if (item.title != null) {
                    opt.title = item.title;
                }
                return opt;
            });
        }

        if (config._disableSearch) {
            config.controlInput = null;
        }

        var width = config._compatWidth;

        delete config._minimumInputLength;
        delete config._compatAjax;
        delete config._compatData;
        delete config._compatWidth;
        delete config._disableSearch;

        stripLegacyOptionKeys(config);
        ensureSafeRenderOutput(config);

        try {
            var instance = new TomSelect(element, config);
            applyWidth(instance, width);
            return instance;
        } catch (err) {
            console.error('[initTomSelect] Failed to initialize Tom Select', err);
            cleanupTomSelectArtifacts(element);
            return null;
        }
    }

    function initTomSelectAll(selector, options) {
        var instances = [];
        document.querySelectorAll(selector).forEach(function (element) {
            var instance = initTomSelect(element, options);
            if (instance) {
                instances.push(instance);
            }
        });
        return instances;
    }

    function cleanupTomSelectArtifacts(element) {
        if (!element) {
            return;
        }
        if (element.tomselect) {
            try {
                element.tomselect.destroy();
            } catch (err) {
                console.warn('[cleanupTomSelectArtifacts] destroy failed', err);
            }
            delete element.tomselect;
        }
        unmarkMigration(element);
        var next = element.nextElementSibling;
        while (next && next.classList && next.classList.contains('ts-wrapper')) {
            var toRemove = next;
            next = next.nextElementSibling;
            toRemove.remove();
        }
    }

    function destroyTomSelect(el) {
        var element = resolveElement(el);
        if (!element) {
            return;
        }
        cleanupTomSelectArtifacts(element);
    }

    function isTomSelect(el) {
        var element = resolveElement(el);
        return !!(element && element.tomselect);
    }

    function getEnhancementWrapper(el) {
        var element = resolveElement(el);
        if (!element) {
            return null;
        }
        if (element.tomselect && element.tomselect.wrapper) {
            return element.tomselect.wrapper;
        }
        var sibling = element.nextElementSibling;
        if (sibling && sibling.classList.contains('ts-wrapper')) {
            return sibling;
        }
        return null;
    }

    function placeValidationError(el, errorHtml) {
        var element = resolveElement(el);
        if (!element) {
            return;
        }
        var wrapper = getEnhancementWrapper(element);
        if (wrapper) {
            wrapper.insertAdjacentHTML('afterend', errorHtml);
            return;
        }
        if (window.jQuery) {
            window.jQuery(element).after(errorHtml);
        }
    }

    function destroyEnhancedSelect(el) {
        var element = resolveElement(el);
        if (!element) {
            return;
        }
        if (element.tomselect) {
            destroyTomSelect(element);
            return;
        }
    }

    function reinitTomSelect(el, options) {
        var element = resolveElement(el);
        if (element) {
            cleanupTomSelectArtifacts(element);
        }
        destroyEnhancedSelect(el);
        return initTomSelect(el, options);
    }

    /** Options for compact dropdowns (no search box). */
    function compactTomSelectOptions(extra) {
        return Object.assign({ width: '100%', minimumResultsForSearch: Infinity }, extra || {});
    }

    /** Destroy enhanced select, replace options HTML, re-init (AJAX cascade chains). */
    function reinitTomSelectAfterHtml(el, html, options) {
        var element = resolveElement(el);
        if (!element) {
            return null;
        }
        destroyEnhancedSelect(element);
        element.innerHTML = html;
        element.classList.add('tomselect');
        return initTomSelect(element, options || {});
    }

    /** Init Tom Select and restore the native value (edit pages with pre-selected options). */
    function initTomSelectPreserveValue(el, options) {
        var element = resolveElement(el);
        if (!element) {
            return null;
        }
        if (isTomSelect(element)) {
            return element.tomselect;
        }
        var currentValue = element.value;
        var instance = initTomSelect(element, options || {});
        if (instance && currentValue) {
            setEnhancedSelectValue(element, currentValue, true);
        }
        return instance;
    }

    /** Batch init matching elements, preserving each element's current value. */
    function initTomSelectAllPreserveValues(selector, options) {
        var instances = [];
        document.querySelectorAll(selector).forEach(function (element) {
            var instance = initTomSelectPreserveValue(element, options || {});
            if (instance) {
                instances.push(instance);
            }
        });
        return instances;
    }

    function resolveModalDropdownParent(modalEl) {
        var modal = resolveElement(modalEl);
        if (!modal) {
            return null;
        }
        return modal.querySelector('.modal-content') || modal;
    }

    /**
     * Init all select.tomselect inside a modal (call on shown.bs.modal).
     */
    function initModalTomSelects(modalEl, options) {
        var modal = resolveElement(modalEl);
        if (!modal || typeof TomSelect === 'undefined') {
            return [];
        }

        var isAddApplicationModal = modal.classList.contains('add_appliation');
        var isAgreementModal = modal.id === 'agreementModal';
        var isPartnerAssignModal = modal.id === 'create_partneraction';
        var isApplicationAssignModal = modal.id === 'create_applicationaction';
        var isApplicationEmailModal = modal.id === 'applicationemailmodal';
        var isCheckinModal = modal.id === 'checkinmodal';
        var isCreateChecklistModal = modal.id === 'create_checklist';
        var isOpenAllDocsModal = modal.id === 'openalldocsmodal';
        var isCommissionInvoiceModal = modal.id === 'opencommissionmodal';
        var isGeneralInvoiceModal = modal.id === 'opengeneralinvoice';
        var omitDropdownParent = isAddApplicationModal || isAgreementModal || isPartnerAssignModal ||
            isApplicationAssignModal || isApplicationEmailModal || isCheckinModal || isCreateChecklistModal ||
            isOpenAllDocsModal || isCommissionInvoiceModal || isGeneralInvoiceModal;
        var base = Object.assign({ width: '100%' }, options || {});
        if (!omitDropdownParent) {
            base.dropdownParent = resolveModalDropdownParent(modal);
        }
        var instances = [];

        modal.querySelectorAll('select.tomselect').forEach(function (element) {
            if (modal.id === 'emailmodal' &&
                (element.classList.contains('js-data-example-ajax') ||
                    element.classList.contains('selecttemplate'))) {
                return;
            }
            if (element.tomselect) {
                if (isAddApplicationModal && typeof destroyEnhancedSelect === 'function') {
                    destroyEnhancedSelect(element);
                } else if ((isCreateChecklistModal || isOpenAllDocsModal ||
                    isCommissionInvoiceModal || isGeneralInvoiceModal ||
                    isApplicationAssignModal || isApplicationEmailModal) &&
                    typeof destroyTomSelect === 'function') {
                    destroyTomSelect(element);
                } else {
                    return;
                }
            }
            var opts = Object.assign({}, base);
            if (modal.id === 'emailmodal') {
                opts.dropdownParent = document.body;
            }
            // Add Application / Agreement / Partner Assign Staff / Check-in / Checklist / Invoice modals:
            // omit dropdownParent (same as Assign Staff popover) so the menu stays on .ts-wrapper
            // and CSS top:100% places it under each control. Reparenting to .modal-content breaks
            // placement (Tom Select only positionDropdown() when dropdownParent is 'body').
            if (isAddApplicationModal || isAgreementModal || isPartnerAssignModal ||
                isApplicationAssignModal || isApplicationEmailModal || isCheckinModal ||
                isCreateChecklistModal || isOpenAllDocsModal ||
                isCommissionInvoiceModal || isGeneralInvoiceModal) {
                opts.maxOptions = null;
            }
            if (omitDropdownParent) {
                delete opts.dropdownParent;
            }
            if (element.multiple) {
                opts.plugins = ensurePlugin(opts.plugins, 'remove_button');
                if (opts.closeAfterSelect === undefined) {
                    opts.closeAfterSelect = false;
                }
                var multiPlaceholder = element.getAttribute('data-placeholder');
                if (multiPlaceholder && !opts.placeholder) {
                    opts.placeholder = multiPlaceholder;
                }
            }
            var instance = initTomSelect(element, opts);
            if (instance) {
                instances.push(instance);
            }
        });

        return instances;
    }

    function setEnhancedSelectValue(el, value, silent) {
        var element = resolveElement(el);
        if (!element) {
            return;
        }
        if (element.tomselect) {
            if (value == null || value === '' || (Array.isArray(value) && !value.length)) {
                element.tomselect.clear(silent !== false);
                return;
            }
            element.tomselect.setValue(value, silent !== false);
            return;
        }
        if (window.jQuery) {
            window.jQuery(element).val(value).trigger('change');
        }
    }

    function clearEnhancedSelectValue(el, silent) {
        setEnhancedSelectValue(el, null, silent);
    }

    function getEnhancedSelectValue(el) {
        var element = resolveElement(el);
        if (!element) {
            return '';
        }
        if (element.tomselect) {
            var value = element.tomselect.getValue();
            if (Array.isArray(value)) {
                return value.length ? value : '';
            }
            return value == null ? '' : value;
        }
        if (window.jQuery) {
            var nativeVal = window.jQuery(element).val();
            return nativeVal == null ? '' : nativeVal;
        }
        return element.value || '';
    }

    /** Run callback when Tom Select helpers are ready (Promise + poll fallback). */
    function whenTomSelectReady(callback, maxAttempts) {
        if (typeof callback !== 'function') {
            return;
        }
        if (typeof waitForTomSelect === 'function') {
            waitForTomSelect(maxAttempts).then(callback);
            return;
        }
        var limit = maxAttempts || 200;
        var attempts = 0;
        var timer = setInterval(function () {
            attempts += 1;
            if (typeof TomSelect !== 'undefined' && typeof window.initTomSelect === 'function') {
                clearInterval(timer);
                callback();
            } else if (attempts >= limit) {
                clearInterval(timer);
                console.warn('[whenTomSelectReady] Tom Select helpers not loaded after timeout');
            }
        }, 50);
    }

    function waitForTomSelect(maxAttempts) {
        var limit = maxAttempts || 200;

        return new Promise(function (resolve) {
            var attempts = 0;

            function check() {
                attempts += 1;
                if (typeof TomSelect !== 'undefined' && typeof window.initTomSelect === 'function') {
                    resolve();
                } else if (attempts >= limit) {
                    console.warn('[waitForTomSelect] Tom Select helpers not loaded after timeout');
                    resolve();
                } else {
                    setTimeout(check, 50);
                }
            }

            check();
        });
    }

    window.initTomSelect = initTomSelect;
    window.initTomSelectAll = initTomSelectAll;
    window.destroyTomSelect = destroyTomSelect;
    window.isTomSelect = isTomSelect;
    window.getEnhancementWrapper = getEnhancementWrapper;
    window.placeValidationError = placeValidationError;
    window.destroyEnhancedSelect = destroyEnhancedSelect;
    window.reinitTomSelect = reinitTomSelect;
    window.compactTomSelectOptions = compactTomSelectOptions;
    window.reinitTomSelectAfterHtml = reinitTomSelectAfterHtml;
    window.initTomSelectPreserveValue = initTomSelectPreserveValue;
    window.initTomSelectAllPreserveValues = initTomSelectAllPreserveValues;
    window.initModalTomSelects = initModalTomSelects;
    window.setEnhancedSelectValue = setEnhancedSelectValue;
    window.getEnhancedSelectValue = getEnhancedSelectValue;
    window.clearEnhancedSelectValue = clearEnhancedSelectValue;
    window.waitForTomSelect = waitForTomSelect;
    window.whenTomSelectReady = whenTomSelectReady;

    if (window.jQuery) {
        window.jQuery(document).on('shown.bs.modal', '.modal', function () {
            initModalTomSelects(this);
        });
    }

    window.BansalTomSelect = {
        init: initTomSelect,
        initAll: initTomSelectAll,
        destroy: destroyTomSelect,
        destroyEnhanced: destroyEnhancedSelect,
        reinit: reinitTomSelect,
        reinitAfterHtml: reinitTomSelectAfterHtml,
        compactOptions: compactTomSelectOptions,
        initPreserveValue: initTomSelectPreserveValue,
        initAllPreserveValues: initTomSelectAllPreserveValues,
        initModal: initModalTomSelects,
        setValue: setEnhancedSelectValue,
        getValue: getEnhancedSelectValue,
        clearValue: clearEnhancedSelectValue,
        whenReady: whenTomSelectReady,
        isTomSelect: isTomSelect,
        getEnhancementWrapper: getEnhancementWrapper,
        placeValidationError: placeValidationError,
        waitForTomSelect: waitForTomSelect
    };
})(window);
