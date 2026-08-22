/**
 * Assign-note @mentions (opt-in: .js-staff-mentions only).
 *
 * Type @ to pick staff. Inserts @Name and sets the assignee select
 * (Tom Select when present). TinyMCE emails/notes without the class
 * are not touched.
 */
(function ($) {
    'use strict';

    if (typeof $ === 'undefined') {
        return;
    }

    if (window.__taskDescMentionsBound) {
        return;
    }
    window.__taskDescMentionsBound = true;

    var MENU_ID = 'task-desc-mention-menu';
    var STYLE_ID = 'task-desc-mention-styles';
    var MENU_Z = 20000;
    var TEXTAREA_SEL = '.js-staff-mentions';
    var ASSIGNEE_SEL = 'select#rem_cat, select.rem_cat, select[name="rem_cat"], select#rem_cat123, select[name="rem_cat123"], select#rem_cat11, select[name="rem_cat11"]';
    var MAX_RESULTS = 80;
    var activeState = null;
    var listenersBound = false;
    var $menuCached = null;
    var applyingMention = false;
    var tinyBoundEditors = {};

    $(function () {
        ensureStyles();
        initTaskDescriptionMentions();
        whenTinymceReady(bindTinyMceMentions);
    });

    function initTaskDescriptionMentions() {
        if (listenersBound) {
            return;
        }
        listenersBound = true;

        $(document).on('input.taskMentions', TEXTAREA_SEL, function () {
            if (applyingMention || getEditorForEl(this) || !isEligibleTextarea(this)) {
                return;
            }
            handleTextareaInput(this);
        });

        $(document).on('keydown.taskMentions', TEXTAREA_SEL, function (e) {
            if (getEditorForEl(this) || !isEligibleTextarea(this)) {
                return;
            }
            handleKeydown(e, { type: 'textarea', textarea: this });
        });

        $(document).on('blur.taskMentions', TEXTAREA_SEL, function () {
            scheduleHideMenu();
        });

        $(document).on('hide.bs.popover hide.bs.modal', function () {
            hideMenu();
        });

        $(document).on('click.taskMentions', function (e) {
            if (isMenuEvent(e) || $(e.target).is(TEXTAREA_SEL)) {
                return;
            }
            hideMenu();
        });
    }

    function isEligibleTextarea(el) {
        return !!(el && $(el).is('textarea' + TEXTAREA_SEL) && !el.disabled && !el.readOnly);
    }

    function getEditorForEl(el) {
        if (!el || !window.tinymce || typeof tinymce.get !== 'function') {
            return null;
        }
        if (el.id) {
            var byId = tinymce.get(el.id);
            if (byId) {
                return byId;
            }
        }
        try {
            return tinymce.get(el) || null;
        } catch (err) {
            return null;
        }
    }

    function whenTinymceReady(cb) {
        if (window.tinymce && typeof tinymce.on === 'function') {
            cb();
            return;
        }
        var tries = 0;
        var timer = setInterval(function () {
            tries += 1;
            if (window.tinymce && typeof tinymce.on === 'function') {
                clearInterval(timer);
                cb();
            } else if (tries > 80) {
                clearInterval(timer);
            }
        }, 100);
    }

    function bindTinyMceMentions() {
        tinymce.on('AddEditor', function (e) {
            if (e && e.editor) {
                e.editor.on('init', function () {
                    attachTinyMceEditor(e.editor);
                });
            }
        });
        var editors = tinymce.get();
        if (editors && editors.length) {
            editors.forEach(attachTinyMceEditor);
        }
    }

    function attachTinyMceEditor(editor) {
        if (!editor || !editor.id || tinyBoundEditors[editor.id]) {
            return;
        }
        var el = editor.getElement && editor.getElement();
        if (!el || !el.classList || !el.classList.contains('js-staff-mentions')) {
            return;
        }
        tinyBoundEditors[editor.id] = true;

        editor.on('keyup input', function () {
            if (applyingMention) {
                return;
            }
            handleEditorInput(editor);
        });

        editor.on('keydown', function (e) {
            handleKeydown(e, { type: 'editor', editor: editor });
        });

        editor.on('blur', function () {
            scheduleHideMenu();
        });

        editor.on('remove', function () {
            delete tinyBoundEditors[editor.id];
            hideMenu();
        });
    }

    function ensureStyles() {
        if (document.getElementById(STYLE_ID)) {
            return;
        }
        var css = [
            '.task-desc-mention-menu{display:none;position:fixed;z-index:20000;max-height:220px;overflow-y:auto;',
            'background:#fff;border:1px solid #c8dcef;border-radius:8px;',
            'box-shadow:0 8px 24px rgba(30,61,96,.14);padding:4px;}',
            '.task-desc-mention-item{display:flex;align-items:center;gap:8px;width:100%;border:0;',
            'background:transparent;text-align:left;padding:8px 10px;border-radius:6px;',
            'color:#1a2c40;font-size:.9rem;cursor:pointer;}',
            '.task-desc-mention-item .mention-at-icon{display:inline-flex;align-items:center;justify-content:center;',
            'flex-shrink:0;width:14px;height:14px;color:#3a6fa8;font-weight:700;font-size:.95rem;line-height:1;}',
            '.task-desc-mention-item svg.mention-at-icon,.task-desc-mention-item .mention-at-icon svg{width:14px;height:14px;}',
            '.task-desc-mention-item:hover,.task-desc-mention-item.is-active{background:#ddeaf8;}'
        ].join('');
        $('<style id="' + STYLE_ID + '">').text(css).appendTo('head');
    }

    function menuHost(anchorEl) {
        if (anchorEl) {
            var $modal = $(anchorEl).closest('.modal');
            if ($modal.length) {
                return $modal.first();
            }
        }
        return $(document.body);
    }

    function ensureMenu(anchorEl) {
        if ($menuCached && $menuCached.length && document.body.contains($menuCached[0])) {
            var $host = menuHost(anchorEl);
            if ($menuCached.parent()[0] !== $host[0]) {
                $host.append($menuCached);
            }
            return $menuCached;
        }

        var $existing = $('#' + MENU_ID);
        if ($existing.length) {
            $menuCached = $existing;
        } else {
            $menuCached = $('<div id="' + MENU_ID + '" class="task-desc-mention-menu" role="listbox" aria-label="Tag staff"></div>');
        }
        menuHost(anchorEl).append($menuCached);

        $menuCached.off('.taskMentions');
        $menuCached.on('mousedown.taskMentions click.taskMentions touchstart.taskMentions', function (e) {
            e.stopPropagation();
        });
        $menuCached.on('mousedown.taskMentions', '.task-desc-mention-item', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var staffId = $(this).attr('data-staff-id');
            var staffName = $(this).attr('data-staff-name') || '';
            if (activeState && staffId) {
                applyActiveMention(staffId, staffName);
            }
        });
        $menuCached.on('click.taskMentions', '.task-desc-mention-item', function (e) {
            e.preventDefault();
            e.stopPropagation();
            hideMenu();
        });

        return $menuCached;
    }

    function isMenuEvent(e) {
        var $menu = $menuCached && $menuCached.length ? $menuCached : $('#' + MENU_ID);
        if (!$menu.length || !e || !e.target) {
            return false;
        }
        return e.target === $menu[0] || $.contains($menu[0], e.target);
    }

    function findRoot($el) {
        var $popup = $el.closest('.popover, .modal, #actionPopoverModal');
        if ($popup.length) {
            return $popup.first();
        }
        var $inner = $el.closest('#popover-content');
        if ($inner.length && $inner.find(ASSIGNEE_SEL).length) {
            return $inner.first();
        }
        return $();
    }

    function scheduleHideMenu() {
        setTimeout(function () {
            var active = document.activeElement;
            if (active && $menuCached && $menuCached[0] && $.contains($menuCached[0], active)) {
                return;
            }
            hideMenu();
        }, 150);
    }

    function handleTextareaInput(textarea) {
        var mention = getMentionAtCaret(textarea.value || '', textarea.selectionStart);
        showOrHideFromMention({ type: 'textarea', textarea: textarea }, mention, $(textarea));
    }

    function handleEditorInput(editor) {
        var mention = getMentionFromEditor(editor);
        var el = editor.getElement && editor.getElement();
        showOrHideFromMention({ type: 'editor', editor: editor, mention: mention }, mention, $(el));
    }

    function showOrHideFromMention(source, mention, $el) {
        if (!mention) {
            hideMenu();
            return;
        }
        var $root = findRoot($el);
        if (!$root.length) {
            hideMenu();
            return;
        }
        var staff = collectStaff($root);
        var filtered = filterStaff(staff, mention.query);
        if (!filtered.length) {
            hideMenu();
            return;
        }
        source.mention = mention;
        activeState = { source: source, mention: mention, items: filtered, index: 0 };
        renderMenu(source, filtered, 0);
    }

    function handleKeydown(e, source) {
        var $menu = $menuCached;
        if (!$menu || !$menu.length || !$menu.is(':visible')) {
            return;
        }
        if (!activeState || !sameSource(activeState.source, source)) {
            return;
        }

        var key = e.key || '';
        var code = e.keyCode;
        if (key === 'ArrowDown' || code === 40) {
            preventEditorDefault(e);
            activeState.index = (activeState.index + 1) % activeState.items.length;
            highlightMenu(activeState.index);
            return;
        }
        if (key === 'ArrowUp' || code === 38) {
            preventEditorDefault(e);
            activeState.index = (activeState.index - 1 + activeState.items.length) % activeState.items.length;
            highlightMenu(activeState.index);
            return;
        }
        if (key === 'Enter' || key === 'Tab' || code === 13 || code === 9) {
            preventEditorDefault(e);
            var item = activeState.items[activeState.index];
            if (item) {
                applyActiveMention(item.id, item.name);
            }
            hideMenu();
            return;
        }
        if (key === 'Escape' || code === 27) {
            preventEditorDefault(e);
            hideMenu();
        }
    }

    function sameSource(a, b) {
        if (!a || !b || a.type !== b.type) {
            return false;
        }
        if (a.type === 'textarea') {
            return a.textarea === b.textarea;
        }
        return a.editor === b.editor;
    }

    function preventEditorDefault(e) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        if (e.stopImmediatePropagation) {
            e.stopImmediatePropagation();
        }
        e.returnValue = false;
    }

    function getMentionAtCaret(value, pos) {
        value = value || '';
        pos = typeof pos === 'number' ? pos : value.length;
        var before = value.slice(0, pos);
        var match = before.match(/(^|[\s(\[{])@([^\s@]*)$/);
        if (!match) {
            return null;
        }
        return {
            start: before.lastIndexOf('@'),
            end: pos,
            query: match[2] || ''
        };
    }

    function getMentionFromEditor(editor) {
        var rng = editor.selection.getRng();
        if (!rng) {
            return null;
        }
        var node = rng.startContainer;
        var offset = rng.startOffset;
        var before = '';
        if (node.nodeType === 3) {
            before = (node.data || '').slice(0, offset);
        } else if (node.nodeType === 1 && offset > 0) {
            var child = node.childNodes[offset - 1];
            if (child && child.nodeType === 3) {
                node = child;
                before = child.data || '';
                offset = before.length;
            } else {
                return null;
            }
        } else {
            return null;
        }
        var match = before.match(/(^|[\s(\[{])@([^\s@]*)$/);
        if (!match) {
            return null;
        }
        return {
            node: node,
            start: before.lastIndexOf('@'),
            end: offset,
            query: match[2] || ''
        };
    }

    function collectStaff($root) {
        var list = [];
        var seen = {};
        $root.find(ASSIGNEE_SEL).each(function () {
            $(this).find('option').each(function () {
                var $opt = $(this);
                var id = String($opt.val() || '');
                if (!id || seen[id]) {
                    return;
                }
                var name = ($opt.text() || '').replace(/\s+/g, ' ').trim().replace(/\s*\([^)]*\)\s*$/, '').trim();
                if (!name || /^select$/i.test(name)) {
                    return;
                }
                seen[id] = true;
                list.push({
                    id: id,
                    name: name,
                    search: (name + id).toLowerCase().replace(/\s+/g, '')
                });
            });
        });
        return list;
    }

    function filterStaff(staff, query) {
        var q = String(query || '').toLowerCase().replace(/\s+/g, '');
        var matched = !q ? staff.slice() : staff.filter(function (s) {
            return s.search.indexOf(q) !== -1 || s.name.toLowerCase().replace(/\s+/g, '').indexOf(q) !== -1;
        });
        return matched.slice(0, MAX_RESULTS);
    }

    function mentionAtIconHtml() {
        var html = '';
        var iconOpts = { class: 'mention-at-icon' };
        if (typeof window.crmIcon === 'function') {
            html = window.crmIcon('at-sign', iconOpts) || '';
        } else if (typeof window.crmIconLegacy === 'function') {
            html = window.crmIconLegacy('fa-at', iconOpts) || '';
        } else if (typeof window.crmI === 'function') {
            html = window.crmI('at', iconOpts) || '';
        }
        if (html) {
            return html;
        }
        return '<span class="mention-at-icon" aria-hidden="true">@</span>';
    }

    function renderMenu(source, items, activeIndex) {
        var anchor = sourceAnchorEl(source);
        var $menu = ensureMenu(anchor);
        var html = items.map(function (item, i) {
            var activeClass = i === activeIndex ? ' is-active' : '';
            return '<button type="button" class="task-desc-mention-item' + activeClass + '" role="option"' +
                ' data-staff-id="' + escapeAttr(item.id) + '"' +
                ' data-staff-name="' + escapeAttr(item.name) + '">' +
                mentionAtIconHtml() +
                '<span>' + escapeHtml(item.name) + '</span>' +
                '</button>';
        }).join('');

        $menu.html(html).show();
        positionMenu(source, $menu);
    }

    function sourceAnchorEl(source) {
        if (source.type === 'editor' && source.editor && source.editor.getContainer) {
            return source.editor.getContainer();
        }
        return source.textarea;
    }

    function highlightMenu(index) {
        var $menu = $menuCached;
        if (!$menu) {
            return;
        }
        $menu.find('.task-desc-mention-item').removeClass('is-active')
            .eq(index).addClass('is-active');
        var el = $menu.find('.task-desc-mention-item').get(index);
        if (el && typeof el.scrollIntoView === 'function') {
            el.scrollIntoView({ block: 'nearest' });
        }
    }

    function positionMenu(source, $menu) {
        var rect = getSourceRect(source);
        var menuHeight = $menu.outerHeight() || 200;
        var spaceBelow = window.innerHeight - rect.bottom;
        var top = spaceBelow < menuHeight && rect.top > menuHeight
            ? (rect.top - menuHeight - 4)
            : (rect.bottom + 4);
        var width = Math.min(Math.max(rect.width || 220, 220), 320);
        var left = Math.min(rect.left, window.innerWidth - width - 8);
        $menu.css({
            position: 'fixed',
            top: Math.max(8, top) + 'px',
            left: Math.max(8, left) + 'px',
            width: width + 'px',
            zIndex: MENU_Z
        });
    }

    function getSourceRect(source) {
        if (source.type === 'editor' && source.editor) {
            var caret = getEditorCaretRect(source.editor);
            if (caret) {
                return caret;
            }
            var container = source.editor.getContainer && source.editor.getContainer();
            if (container) {
                return container.getBoundingClientRect();
            }
        }
        if (source.textarea) {
            return source.textarea.getBoundingClientRect();
        }
        return { top: 8, bottom: 8, left: 8, width: 220 };
    }

    function getEditorCaretRect(editor) {
        try {
            var rng = editor.selection.getRng();
            var iframe = editor.iframeElement || (editor.getContentAreaContainer &&
                editor.getContentAreaContainer().querySelector('iframe'));
            if (!iframe || !rng) {
                return null;
            }
            var frameRect = iframe.getBoundingClientRect();
            var rects = rng.getClientRects && rng.getClientRects();
            var r = rects && rects.length ? rects[0] : rng.getBoundingClientRect();
            if (!r) {
                return null;
            }
            return {
                top: frameRect.top + r.top,
                bottom: frameRect.top + r.bottom,
                left: frameRect.left + r.left,
                width: Math.max(r.width, 220)
            };
        } catch (err) {
            return null;
        }
    }

    function applyActiveMention(staffId, staffName) {
        if (!activeState || !activeState.source) {
            return;
        }
        var source = activeState.source;
        var mention = activeState.mention || source.mention;
        applyingMention = true;
        try {
            if (source.type === 'editor' && source.editor) {
                applyMentionInEditor(source.editor, mention, staffId, staffName);
            } else if (source.textarea && mention) {
                applyMentionInTextarea(source.textarea, mention, staffId, staffName);
            }
        } finally {
            applyingMention = false;
        }
    }

    function applyMentionInTextarea(textarea, mention, staffId, staffName) {
        var value = textarea.value || '';
        var insert = '@' + staffName + ' ';
        textarea.value = value.slice(0, mention.start) + insert + value.slice(mention.end);
        var caret = mention.start + insert.length;
        textarea.focus();
        if (typeof textarea.setSelectionRange === 'function') {
            textarea.setSelectionRange(caret, caret);
        }
        syncAssignee(findRoot($(textarea)), staffId);
    }

    function applyMentionInEditor(editor, mention, staffId, staffName) {
        var insert = '@' + staffName + ' ';
        if (mention && mention.node && mention.node.nodeType === 3 && mention.node.data != null) {
            var text = mention.node.data;
            mention.node.data = text.slice(0, mention.start) + insert + text.slice(mention.end);
            var caret = mention.start + insert.length;
            try {
                var rng = editor.getDoc().createRange();
                rng.setStart(mention.node, Math.min(caret, mention.node.data.length));
                rng.collapse(true);
                editor.selection.setRng(rng);
            } catch (err) {
                editor.insertContent(insert);
            }
        } else {
            editor.insertContent(insert);
        }
        editor.save();
        var el = editor.getElement && editor.getElement();
        syncAssignee(findRoot($(el)), staffId);
        editor.focus();
    }

    function syncAssignee($root, staffId) {
        if (!$root || !$root.length) {
            return;
        }
        var $sel = $root.find(ASSIGNEE_SEL).first();
        if (!$sel.length) {
            return;
        }
        var el = $sel[0];
        if (window.ActionPopoverTomSelect && typeof ActionPopoverTomSelect.setValue === 'function') {
            ActionPopoverTomSelect.setValue(el, String(staffId));
            return;
        }
        if (typeof window.setEnhancedSelectValue === 'function') {
            window.setEnhancedSelectValue(el, String(staffId));
            return;
        }
        $sel.val(String(staffId)).trigger('change');
    }

    function hideMenu() {
        if ($menuCached && $menuCached.length) {
            $menuCached.hide().empty();
        } else {
            $('#' + MENU_ID).hide().empty();
        }
        activeState = null;
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escapeAttr(str) {
        return escapeHtml(str).replace(/'/g, '&#39;');
    }

    window.TaskDescriptionMentions = {
        init: initTaskDescriptionMentions,
        hide: hideMenu
    };
})(window.jQuery);
