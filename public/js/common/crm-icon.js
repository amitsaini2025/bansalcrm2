/**
 * Client-side icon helper — mirrors App\Helpers\IconHelper for dynamic HTML.
 */
(function (global) {
    'use strict';

    var FA_TO_LUCIDE = {
        'angle-down': 'chevron-down',
        'angle-left': 'chevron-left',
        'angle-right': 'chevron-right',
        'archive': 'archive',
        'arrow-down': 'arrow-down',
        'arrow-left': 'arrow-left',
        'arrow-right': 'arrow-right',
        'arrows-alt-h': 'arrow-left-right',
        'at': 'at-sign',
        'bag': 'shopping-bag',
        'ban': 'ban',
        'bars': 'menu',
        'bell': 'bell',
        'bell-slash': 'bell-off',
        'bolt': 'zap',
        'briefcase': 'briefcase',
        'calendar': 'calendar',
        'calendar-alt': 'calendar-days',
        'calendar-check': 'calendar-check',
        'calendar-plus': 'calendar-plus',
        'camera': 'camera',
        'chart-bar': 'chart-column',
        'check': 'check',
        'check-circle': 'circle-check',
        'check-double': 'check-check',
        'chevron-down': 'chevron-down',
        'chevron-left': 'chevron-left',
        'chevron-right': 'chevron-right',
        'circle': 'circle',
        'clipboard-list': 'clipboard-list',
        'clock': 'clock',
        'close': 'x',
        'cloud': 'cloud',
        'cloud-upload-alt': 'cloud-upload',
        'code': 'code',
        'cog': 'cog',
        'cogs': 'settings',
        'columns': 'columns-3',
        'comment-alt': 'message-square',
        'copy': 'copy',
        'desktop': 'monitor',
        'dollar-sign': 'dollar-sign',
        'dollor': 'dollar-sign',
        'download': 'download',
        'edit': 'pencil',
        'ellipsis-v': 'ellipsis-vertical',
        'envelope': 'mail',
        'envelope-open': 'mail-open',
        'eraser': 'eraser',
        'exchange-alt': 'arrow-left-right',
        'exclamation-circle': 'circle-alert',
        'exclamation-triangle': 'triangle-alert',
        'expand': 'expand',
        'external-link-alt': 'external-link',
        'eye': 'eye',
        'file': 'file',
        'file-alt': 'file-text',
        'file-archive': 'file-archive',
        'file-code': 'file-code',
        'file-contract': 'file-text',
        'file-csv': 'file-spreadsheet',
        'file-excel': 'file-spreadsheet',
        'file-image': 'file-image',
        'file-pdf': 'file-text',
        'file-signature': 'signature',
        'file-word': 'file-type',
        'filter': 'funnel',
        'flag': 'flag',
        'folder': 'folder',
        'folder-open': 'folder-open',
        'globe': 'globe',
        'graduation-cap': 'graduation-cap',
        'grip-vertical': 'grip-vertical',
        'history': 'history',
        'id-card': 'id-card',
        'image': 'image',
        'inbox': 'inbox',
        'info-circle': 'info',
        'language': 'languages',
        'link': 'link',
        'list': 'list',
        'list-ul': 'list',
        'lock': 'lock',
        'magic': 'wand-sparkles',
        'map-marker-alt': 'map-pin',
        'minus': 'minus',
        'mobile': 'smartphone',
        'paperclip': 'paperclip',
        'paper-plane': 'send',
        'pen': 'pen',
        'pencil-alt': 'pencil',
        'percentage': 'percent',
        'phone': 'phone',
        'phone-alt': 'phone',
        'plus': 'plus',
        'print': 'printer',
        'question': 'circle-question-mark',
        'redo': 'rotate-cw',
        'refresh': 'refresh-cw',
        'reply': 'reply',
        'save': 'save',
        'search': 'search',
        'server': 'server',
        'share': 'share-2',
        'shield-alt': 'shield',
        'shopping-cart': 'shopping-cart',
        'sign-in-alt': 'log-in',
        'sign-out-alt': 'log-out',
        'sms': 'message-square-text',
        'sort': 'arrow-up-down',
        'sort-down': 'chevron-down',
        'sort-up': 'chevron-up',
        'sort-alpha-down': 'arrow-down-a-z',
        'sort-alpha-up': 'arrow-up-a-z',
        'sort-amount-down': 'arrow-down-wide-narrow',
        'sort-amount-up': 'arrow-up-wide-narrow',
        'sort-numeric-down': 'arrow-down-1-0',
        'sort-numeric-up': 'arrow-up-1-0',
        'spinner': 'loader-circle',
        'star': 'star',
        'sticky-note': 'sticky-note',
        'suitcase': 'briefcase',
        'sync-alt': 'refresh-cw',
        'tag': 'tag',
        'tasks': 'list-checks',
        'thumbtack': 'pin',
        'ticket-alt': 'ticket',
        'times': 'x',
        'times-circle': 'circle-x',
        'toggle-on': 'toggle-right',
        'trash': 'trash',
        'trash-alt': 'trash-2',
        'undo': 'undo-2',
        'university': 'building-2',
        'unlink': 'unlink',
        'upload': 'upload',
        'user': 'user',
        'user-check': 'user-check',
        'user-clock': 'clock',
        'user-edit': 'user-pen',
        'user-plus': 'user-plus',
        'user-shield': 'shield-user',
        'users': 'users',
    };

    var SIZE_CLASSES = {
        xs: 'icon-xs',
        sm: 'icon-sm',
        lg: 'icon-lg',
        '1x': 'icon-1x',
        '2x': 'icon-2x',
        '3x': 'icon-3x',
        'fa-xs': 'icon-xs',
        'fa-sm': 'icon-sm',
        'fa-lg': 'icon-lg',
        'fa-1x': 'icon-1x',
        'fa-2x': 'icon-2x',
        'fa-3x': 'icon-3x',
    };

    var GOOGLE_BRAND_HTML = '<span class="crm-icon crm-icon-brand crm-icon-google" aria-hidden="true">'
        + '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" role="img" aria-hidden="true">'
        + '<path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>'
        + '<path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>'
        + '<path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>'
        + '<path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>'
        + '</svg></span>';

    function looksLikeClassString(value) {
        return /\bfa[srb]?\s+fa-|\bfa-solid\s+fa-|\bfa-regular\s+fa-|\bfa-brands\s+fa-/.test(value);
    }

    function escapeAttr(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;');
    }

    function normalizeFaSlug(name) {
        if (!name) {
            return 'tag';
        }
        if (name.indexOf('fa-') === 0) {
            return name.slice(3);
        }
        return name;
    }

    function lucideName(faSlug) {
        return FA_TO_LUCIDE[faSlug] || faSlug.replace(/_/g, '-');
    }

    function styleFromClassString(classString) {
        if (/\b(far|fa-regular)\b/.test(classString)) {
            return 'regular';
        }
        if (/\b(fab|fa-brands)\b/.test(classString)) {
            return 'brands';
        }
        return 'solid';
    }

    function nameFromClassString(classString) {
        var parts = classString.trim().split(/\s+/);
        var i;
        var part;
        for (i = 0; i < parts.length; i++) {
            part = parts[i];
            if (part.indexOf('fa-') !== 0) {
                continue;
            }
            if (/^fa-(xs|sm|lg|1x|2x|3x|spin|pulse|fw)$/.test(part)) {
                continue;
            }
            return part.slice(3);
        }
        return null;
    }

    function sizeClass(size) {
        if (size == null || size === '') {
            return '';
        }
        var key = String(size);
        if (SIZE_CLASSES[key]) {
            return SIZE_CLASSES[key];
        }
        if (SIZE_CLASSES[size]) {
            return SIZE_CLASSES[size];
        }
        return key.indexOf('icon-') === 0 ? key : 'icon-' + key.replace(/^fa-/, '');
    }

    function buildLucideClasses(faSlug, style, options, legacyClassString) {
        var classes = ['crm-icon'];
        var parts;
        var i;

        if (style === 'regular') {
            classes.push('crm-icon-regular');
        }

        if (options.spin || faSlug === 'spinner') {
            classes.push('icon-spin');
        }

        if (options.size) {
            classes.push(sizeClass(options.size));
        }

        if (legacyClassString) {
            parts = legacyClassString.trim().split(/\s+/);
            for (i = 0; i < parts.length; i++) {
                if (/^fa-(xs|sm|lg|1x|2x|3x|spin|pulse|fw)$/.test(parts[i])) {
                    classes.push(sizeClass(parts[i]));
                }
            }
        }

        if (options.class) {
            options.class.split(/\s+/).forEach(function (part) {
                if (!part) {
                    return;
                }
                if (/^(?:fa-)?(xs|sm|lg|1x|2x|3x)$/.test(part)) {
                    classes.push(sizeClass(part.replace(/^fa-/, '')));
                    return;
                }
                classes.push(part);
            });
        }

        return classes.filter(function (value, index, list) {
            return list.indexOf(value) === index;
        });
    }

    function buildLucideHtml(faSlug, style, options, legacyClassString) {
        if (style === 'brands' && faSlug === 'google') {
            return GOOGLE_BRAND_HTML;
        }

        var classes = buildLucideClasses(faSlug, style, options || {}, legacyClassString);
        var attrs = options.attrs || {};
        if (attrs.class) {
            classes = classes.concat(String(attrs.class).split(/\s+/).filter(Boolean));
            classes = classes.filter(function (value, index, list) {
                return list.indexOf(value) === index;
            });
        }
        var html = '<i class="' + escapeAttr(classes.join(' ')) + '" data-lucide="' + escapeAttr(lucideName(faSlug)) + '" aria-hidden="true"';

        Object.keys(attrs).forEach(function (key) {
            if (key === 'class' || attrs[key] == null || attrs[key] === '') {
                return;
            }
            html += ' ' + key + '="' + escapeAttr(attrs[key]) + '"';
        });

        return html + '></i>';
    }

    function crmIcon(name, style, options) {
        if (typeof style === 'object' && style !== null) {
            options = style;
            style = 'solid';
        }

        style = style || 'solid';
        options = options || {};

        if (looksLikeClassString(name)) {
            return buildLucideHtml(
                nameFromClassString(name) || 'tag',
                styleFromClassString(name),
                options,
                name
            );
        }

        return buildLucideHtml(normalizeFaSlug(name), style, options);
    }

    function crmIconFromClass(classString, options) {
        return crmIcon(classString, 'solid', options);
    }

    function crmIconStored(stored, options) {
        options = options || {};
        stored = (stored || '').trim();

        if (!stored) {
            return crmIcon('tag', 'solid', options);
        }

        return crmIcon(stored, 'solid', options);
    }

    function crmIconSpinner(label) {
        return crmIcon('spinner', 'solid', { spin: true, class: 'icon-spin' }) + (label ? ' ' + label : '');
    }

    function refreshCrmIconsStub(root) {
        if (typeof global.refreshCrmIcons === 'function' && global.refreshCrmIcons !== refreshCrmIconsStub) {
            global.refreshCrmIcons(root);
        }
    }

    global.crmIcon = crmIcon;
    global.crmIconFromClass = crmIconFromClass;
    global.crmIconStored = crmIconStored;
    global.crmIconSpinner = crmIconSpinner;
    if (typeof global.refreshCrmIcons !== 'function') {
        global.refreshCrmIcons = refreshCrmIconsStub;
    }

    global.CRM_FA_LUCIDE_MAP = FA_TO_LUCIDE;
})(window);
