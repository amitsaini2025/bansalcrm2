/**
 * TinyMCE via npm + Vite (Phase 2e).
 * Requires window.TINYMCE_UPLOAD_URL / TINYMCE_CSRF_TOKEN from partials/tinymce.blade.php.
 */
'use strict';

import tinymce from 'tinymce';

import 'tinymce/icons/default/icons.min.js';
import 'tinymce/themes/silver/theme.min.js';
import 'tinymce/models/dom/model.min.js';

import 'tinymce/skins/ui/oxide/skin.js';
import 'tinymce/skins/ui/oxide/content.js';
import 'tinymce/skins/content/default/content.js';

import 'tinymce/plugins/advlist';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/image';
import 'tinymce/plugins/charmap';
import 'tinymce/plugins/preview';
import 'tinymce/plugins/anchor';
import 'tinymce/plugins/searchreplace';
import 'tinymce/plugins/visualblocks';
import 'tinymce/plugins/code';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/insertdatetime';
import 'tinymce/plugins/media';
import 'tinymce/plugins/table';
import 'tinymce/plugins/wordcount';
import 'tinymce/plugins/emoticons';
import 'tinymce/plugins/emoticons/js/emojis';
import 'tinymce/plugins/directionality';
import 'tinymce/plugins/pagebreak';
import 'tinymce/plugins/nonbreaking';
import 'tinymce/plugins/save';

window.tinymce = tinymce;

const SIMPLE_PLUGINS = [
    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
    'insertdatetime', 'media', 'table', 'wordcount',
].join(' ');

const FULL_PLUGINS = [
    SIMPLE_PLUGINS,
    'emoticons', 'directionality', 'pagebreak', 'nonbreaking', 'save',
].join(' ');

// Keep upload handler URLs (S3) intact on nested pages like /clients/detail/.../application/{id}.
const EDITOR_URL_OPTIONS = {
    convert_urls: false,
    relative_urls: false,
    remove_script_host: false,
};

function getImageUploadHandler() {
    return function (blobInfo, progress) {
        return new Promise(function (resolve, reject) {
            var url = typeof window.TINYMCE_UPLOAD_URL !== 'undefined'
                ? window.TINYMCE_UPLOAD_URL
                : '/tinymce/upload-image';
            var token = typeof window.TINYMCE_CSRF_TOKEN !== 'undefined'
                ? window.TINYMCE_CSRF_TOKEN
                : (document.querySelector('meta[name="csrf-token"]') &&
                    document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            var xhr = new XMLHttpRequest();
            var formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            if (token) {
                formData.append('_token', token);
            }
            xhr.open('POST', url);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            if (token) {
                xhr.setRequestHeader('X-CSRF-TOKEN', token);
            }
            xhr.onload = function () {
                if (xhr.status < 200 || xhr.status >= 300) {
                    reject('Upload failed: ' + (xhr.status ? xhr.statusText : 'Network error'));
                    return;
                }
                try {
                    var json = JSON.parse(xhr.responseText);
                    if (json.location) {
                        resolve(json.location);
                    } else {
                        reject(json.message || 'Upload failed');
                    }
                } catch (e) {
                    reject('Invalid server response');
                }
            };
            xhr.onerror = function () {
                reject('Upload failed');
            };
            xhr.upload.onprogress = function (e) {
                if (e.lengthComputable && typeof progress === 'function') {
                    progress((e.loaded / e.total) * 100);
                }
            };
            xhr.send(formData);
        });
    };
}

function ensureTextareaIds(selector) {
    var $ = window.jQuery || window.$;
    if (!$) {
        return;
    }
    $(selector).each(function () {
        if (!$(this).attr('id')) {
            $(this).attr('id', 'tinymce_' + Math.random().toString(36).substr(2, 9));
        }
    });
}

function initTinyMCE() {
    ensureTextareaIds('.tinymce-simple');
    ensureTextareaIds('.tinymce-full');

    tinymce.init({
        selector: '.tinymce-simple',
        license_key: 'gpl',
        skin_url: 'default',
        content_css: 'default',
        height: 150,
        menubar: false,
        plugins: SIMPLE_PLUGINS,
        toolbar: 'bold italic underline strikethrough | bullist numlist | link image | removeformat',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
        branding: false,
        promotion: false,
        browser_spellcheck: true,
        ...EDITOR_URL_OPTIONS,
        images_upload_handler: getImageUploadHandler(),
        setup: function (editor) {
            editor.on('change', function () {
                editor.save();
            });
            editor.on('blur', function () {
                editor.save();
            });
        },
    });

    tinymce.init({
        selector: '.tinymce-full',
        license_key: 'gpl',
        skin_url: 'default',
        content_css: 'default',
        height: 250,
        menubar: true,
        plugins: FULL_PLUGINS,
        toolbar: 'undo redo | formatselect | ' +
            'bold italic underline strikethrough | forecolor backcolor | ' +
            'alignleft aligncenter alignright alignjustify | ' +
            'bullist numlist | outdent indent | ' +
            'removeformat | link image media table | code preview fullscreen',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
        branding: false,
        promotion: false,
        browser_spellcheck: true,
        ...EDITOR_URL_OPTIONS,
        images_upload_handler: getImageUploadHandler(),
        setup: function (editor) {
            editor.on('change', function () {
                editor.save();
            });
            editor.on('blur', function () {
                editor.save();
            });
        },
    });

    tinymce.init({
        selector: '#editor1',
        license_key: 'gpl',
        skin_url: 'default',
        content_css: 'default',
        height: 400,
        menubar: true,
        plugins: FULL_PLUGINS,
        toolbar: 'undo redo | formatselect | ' +
            'bold italic underline strikethrough | forecolor backcolor | ' +
            'alignleft aligncenter alignright alignjustify | ' +
            'bullist numlist | outdent indent | ' +
            'removeformat | link image media table | code preview fullscreen',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
        branding: false,
        promotion: false,
        browser_spellcheck: true,
        ...EDITOR_URL_OPTIONS,
        images_upload_handler: getImageUploadHandler(),
    });
}

window.TinyMCEHelpers = {
    getContent: function (selector) {
        var editor = tinymce.get(selector);
        if (editor) {
            return editor.getContent();
        }
        if (selector.startsWith('#')) {
            editor = tinymce.get(selector.substring(1));
            if (editor) {
                return editor.getContent();
            }
        }
        var $ = window.jQuery || window.$;
        var $el = $ ? $(selector) : null;
        if ($el && $el.length) {
            return $el.val();
        }
        return '';
    },

    setContent: function (selector, content) {
        var editor = tinymce.get(selector);
        if (editor) {
            editor.setContent(content || '');
            return;
        }
        if (selector.startsWith('#')) {
            editor = tinymce.get(selector.substring(1));
            if (editor) {
                editor.setContent(content || '');
                return;
            }
        }
        var $ = window.jQuery || window.$;
        var $el = $ ? $(selector) : null;
        if ($el && $el.length) {
            $el.val(content || '');
        }
    },

    reset: function (selector) {
        this.setContent(selector, '');
    },

    getContentBySelector: function (selector) {
        var $ = window.jQuery || window.$;
        var $el = $ ? $(selector).first() : null;
        if (!$el || !$el.length) {
            return '';
        }
        var id = $el.attr('id');
        if (id) {
            return this.getContent('#' + id);
        }
        return $el.val() || '';
    },

    setContentBySelector: function (selector, content) {
        var $ = window.jQuery || window.$;
        var $el = $ ? $(selector).first() : null;
        if (!$el || !$el.length) {
            return;
        }
        var id = $el.attr('id');
        if (id) {
            this.setContent('#' + id, content);
        }
        $el.val(content || '');
    },

    resetBySelector: function (selector) {
        this.setContentBySelector(selector, '');
    },

    insertHtml: function (selector, html) {
        var editor = tinymce.get(selector);
        if (editor) {
            editor.insertContent(html);
            return;
        }
        if (selector.startsWith('#')) {
            editor = tinymce.get(selector.substring(1));
            if (editor) {
                editor.insertContent(html);
            }
        }
    },
};

function bootTinyMCE() {
    initTinyMCE();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootTinyMCE);
} else {
    bootTinyMCE();
}
