/**
 * Document Handlers Module
 * 
 * Functions for handling file uploads and document management
 * 
 * Usage:
 *   file_explorer()
 *   uploadFormData(formData)
 *   previewFile(fileType, fileUrl, containerClass)
 */

'use strict';

/**
 * Trigger file explorer dialog
 * Sets up file selection handler
 */
function file_explorer() {
    const selectfile = document.getElementById("selectfile");
    
    if (!selectfile) {
        console.warn("selectfile element not found");
        return;
    }
    
    selectfile.click();
    
    selectfile.onchange = function() {
        var files = selectfile.files;
        if (!files || files.length === 0) {
            return;
        }
        
        var formData = new FormData();
        
        for (var i = 0; i < files.length; i++) {
            formData.append("file[]", files[i]);
        }
        
        // Add form fields from config or DOM
        var type = $('.checklisttype').val();
        var typename = $('.checklisttypename').val();
        var id = $('.checklistid').val();
        var applicationId = $('.application_id').val();
        var clientId = $('.app_doc_client_id').val();
        
        if (type) formData.append("type", type);
        if (typename) formData.append("typename", typename);
        if (id) formData.append("id", id);
        if (applicationId) formData.append("application_id", applicationId);
        if (clientId) formData.append("client_id", clientId);
        
        uploadFormData(formData);
    };
}

/**
 * Upload form data (files + form fields)
 * @param {FormData} formData - FormData object with files and fields
 */
function uploadFormData(formData) {
    $('.popuploader').show();
    
    var url = App.getUrl('checklistUpload') || App.getUrl('applicationChecklistUpload');
    if (!url) {
        console.error('checklistUpload URL not configured');
        $('.popuploader').hide();
        return;
    }
    
    $.ajax({
        url: url,
        method: "POST",
        data: formData,
        datatype: 'json',
        contentType: false,
        cache: false,
        processData: false,
        headers: {
            'X-CSRF-TOKEN': App.getCsrf()
        },
        success: function(response) {
            var obj = typeof response === 'string' ? $.parseJSON(response) : response;
            $('.popuploader').hide();
            $('#openfileuploadmodal').modal('hide');
            $('.mychecklistdocdata').html(obj.doclistdata || '');
            $('.checklistuploadcount').html(obj.applicationuploadcount || '');
            
            if (obj.type && obj.checklistdata) {
                $('.' + obj.type + '_checklists').html(obj.checklistdata);
            }
            
            if ($('#selectfile').length) {
                $('#selectfile').val('');
            }
            
            // Refresh application logs if application_id exists
            if (obj.application_id) {
                var logsUrl = App.getUrl('getApplicationsLogs') || App.getUrl('siteUrl') + '/get-applications-logs';
                if (logsUrl) {
                    $.ajax({
                        url: logsUrl,
                        type: 'GET',
                        data: { id: obj.application_id },
                        success: function(responses) {
                            $('#accordion').html(responses);
                        }
                    });
                }
            }
        },
        error: function() {
            $('.popuploader').hide();
            alert('Error uploading file. Please try again.');
        }
    });
}

/**
 * Preview file in container
 * @param {string} fileType - File type/extension (jpg, pdf, doc, etc.)
 * @param {string} fileUrl - URL to the file
 * @param {string} containerClass - CSS class of container element
 */
function getPreviewDocumentBaseUrl() {
    if (typeof App !== 'undefined' && App.getUrl && App.getUrl('previewDocument')) {
        return App.getUrl('previewDocument');
    }
    if (typeof App !== 'undefined' && App.getUrl && App.getUrl('siteUrl')) {
        return App.getUrl('siteUrl') + '/preview-document';
    }
    return window.location.origin + '/preview-document';
}

function isPrivateRemoteStorageUrl(fileUrl) {
    if (!fileUrl || typeof fileUrl !== 'string') {
        return false;
    }
    if (fileUrl.indexOf('/preview-document') !== -1) {
        return false;
    }
    return /amazonaws\.com/i.test(fileUrl) || /\.s3[\.\-]/i.test(fileUrl);
}

function isLocalPreviewUrl(fileUrl) {
    return fileUrl && (
        fileUrl.startsWith(window.location.origin) ||
        fileUrl.indexOf('127.0.0.1') !== -1 ||
        fileUrl.indexOf('localhost') !== -1 ||
        fileUrl.startsWith('/')
    );
}

function resolvePreviewSourceUrl(fileUrl) {
    if (!fileUrl || fileUrl.indexOf('/preview-document') !== -1) {
        return fileUrl;
    }
    if (isPrivateRemoteStorageUrl(fileUrl)) {
        return getPreviewDocumentBaseUrl() + '?filelink=' + encodeURIComponent(fileUrl);
    }
    return fileUrl;
}

function fetchPresignedPreviewUrl(fileUrl) {
    const url = getPreviewDocumentBaseUrl()
        + '?filelink=' + encodeURIComponent(fileUrl)
        + '&format=json';

    return fetch(url, {
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).then(function(response) {
        if (!response.ok) {
            throw new Error('Preview request failed');
        }
        return response.json();
    }).then(function(data) {
        if (!data || !data.url) {
            throw new Error('Preview URL missing');
        }
        return data.url;
    });
}

function showPreviewLoader(container) {
    hidePreviewLoader(container);
    const loader = document.createElement('div');
    loader.className = 'preview-loader';
    loader.setAttribute('role', 'status');
    loader.setAttribute('aria-live', 'polite');
    loader.innerHTML = '<span class="spinner-border text-primary" role="status"></span><p>Loading preview...</p>';
    container.appendChild(loader);
}

function hidePreviewLoader(container) {
    if (!container) {
        return;
    }
    const loader = container.querySelector('.preview-loader');
    if (loader) {
        loader.remove();
    }
}

function attachPreviewLoadHandler(mediaEl, container, onError) {
    if (!mediaEl) {
        hidePreviewLoader(container);
        return;
    }
    let settled = false;
    const finish = function () {
        if (settled) {
            return;
        }
        settled = true;
        hidePreviewLoader(container);
    };
    const fail = function () {
        if (settled) {
            return;
        }
        settled = true;
        hidePreviewLoader(container);
        if (typeof onError === 'function') {
            onError();
        }
    };
    mediaEl.addEventListener('load', finish, { once: true });
    mediaEl.addEventListener('error', fail, { once: true });
}

function showPreviewError(container, message) {
    hidePreviewLoader(container);
    container.innerHTML = `
        <div class="preview-placeholder">
            ${crmIcon('exclamation-circle', { class: 'mb-3 text-warning' })}
            <p>${message}</p>
        </div>
    `;
}

function parsePreviewFileOnclick(onclickAttr) {
    if (!onclickAttr || onclickAttr.indexOf('previewFile') === -1) {
        return null;
    }
    const match = onclickAttr.match(
        /previewFile\s*\(\s*'((?:\\'|[^'])*)'\s*,\s*'((?:\\'|[^'])*)'\s*,\s*'((?:\\'|[^'])*)'\s*\)/
    );
    if (!match) {
        return null;
    }
    return {
        fileType: match[1],
        fileUrl: match[2].replace(/\\'/g, "'"),
        containerClass: match[3]
    };
}

function normalizeDocumentPreviewUrl(rawUrl) {
    if (!rawUrl) {
        return '';
    }
    var url = String(rawUrl).trim();
    // Unwrap double-wrapped URLs from asset(fullRemoteUrl): https://app/https://bucket.s3.../key
    var doubleWrap = url.match(/^(https?:\/\/[^/]+)\/(https?:\/\/.+)$/i);
    if (doubleWrap) {
        url = doubleWrap[2];
    }
    if (url.startsWith('http://') || url.startsWith('https://')) {
        return url;
    }
    if (url.startsWith('//')) {
        return url;
    }
    if (url.startsWith('/')) {
        return window.location.origin + url;
    }
    return window.location.origin + '/' + url.replace(/^\/+/, '');
}

function findDocumentPreviewContainerClass(row, anchor) {
    if (anchor) {
        const fromData = anchor.getAttribute('data-preview-container');
        if (fromData) {
            return fromData;
        }
        const parsed = parsePreviewFileOnclick(anchor.getAttribute('onclick') || '');
        if (parsed && parsed.containerClass) {
            return parsed.containerClass;
        }
    }
    const scope = row
        ? (row.closest('.tab-pane, .card-body, .card') || document)
        : document;
    const previewEl = scope.querySelector('[class*="preview-container-"]');
    if (previewEl) {
        for (let i = 0; i < previewEl.classList.length; i++) {
            const cls = previewEl.classList[i];
            if (cls.indexOf('preview-container-') === 0) {
                return cls;
            }
        }
    }
    return 'preview-container-alldocumentlist';
}

function resolveDocumentPreviewMeta(anchor) {
    const row = anchor.closest('.drow');
    const docRow = anchor.closest('.doc-row');
    let fileUrl = anchor.getAttribute('data-preview-url') || '';
    let fileType = anchor.getAttribute('data-preview-type') || '';
    let containerClass = anchor.getAttribute('data-preview-container') || '';

    if (typeof jQuery !== 'undefined' && docRow) {
        const $docRow = jQuery(docRow);
        if (!fileUrl) {
            fileUrl = $docRow.data('preview-file-url') || '';
        }
        if (!fileType) {
            fileType = $docRow.data('preview-file-type') || '';
        }
        if (!containerClass) {
            containerClass = $docRow.data('preview-container-class') || '';
        }
    }

    const parsed = parsePreviewFileOnclick(anchor.getAttribute('onclick') || '');
    if (parsed) {
        fileUrl = fileUrl || parsed.fileUrl;
        fileType = fileType || parsed.fileType;
        containerClass = containerClass || parsed.containerClass;
    }

    if (row) {
        fileType = fileType || row.getAttribute('data-file-type') || '';
        if (!fileUrl) {
            const rowMyfile = row.getAttribute('data-myfile') || '';
            fileUrl = normalizeDocumentPreviewUrl(rowMyfile);
        }
    }

    containerClass = containerClass || findDocumentPreviewContainerClass(row, anchor);
    fileUrl = normalizeDocumentPreviewUrl(fileUrl);

    return {
        fileType: fileType,
        fileUrl: fileUrl,
        containerClass: containerClass
    };
}

function previewDocumentLink(anchor) {
    if (!anchor) {
        return;
    }
    const meta = resolveDocumentPreviewMeta(anchor);
    if (!meta.fileUrl) {
        console.error('Document preview URL missing');
        return;
    }
    const openPreview = typeof window.previewFile === 'function' ? window.previewFile : previewFile;
    openPreview(meta.fileType, meta.fileUrl, meta.containerClass);
}

function registerDocumentFilePreviewLinks() {
    if (registerDocumentFilePreviewLinks.initialized) {
        return;
    }
    registerDocumentFilePreviewLinks.initialized = true;
    document.addEventListener('click', function (e) {
        const anchor = e.target.closest('.alldocumnetlist .doc-row a, .notuseddocumnetlist .doc-row a');
        if (!anchor) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        previewDocumentLink(anchor);
    }, true);
}

function previewFile(fileType, fileUrl, containerClass) {
    const container = document.querySelector(`.${containerClass}`);

    if (!container) {
        console.error('Preview container not found');
        return;
    }

    // Normalize before routing (fixes asset() double-wrap; keeps absolute S3 as-is)
    fileUrl = normalizeDocumentPreviewUrl(fileUrl);

    container.innerHTML = '';
    showPreviewLoader(container);

    const normalizedType = (fileType || '').toLowerCase();
    const isS3Url = isPrivateRemoteStorageUrl(fileUrl);
    const previewSourceUrl = resolvePreviewSourceUrl(fileUrl);

    switch (normalizedType) {
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif': {
            const img = document.createElement('img');
            img.className = 'preview-image';
            attachPreviewLoadHandler(img, container, function () {
                showPreviewError(container, 'Unable to load preview. Please try downloading the file.');
            });
            img.src = isS3Url ? previewSourceUrl : fileUrl;
            container.appendChild(img);
            break;
        }

        case 'pdf': {
            const pdfIframe = document.createElement('iframe');
            pdfIframe.className = 'pdf-viewer';
            attachPreviewLoadHandler(pdfIframe, container, function () {
                showPreviewError(container, 'Unable to load preview. Please try downloading the file.');
            });
            if (isLocalPreviewUrl(fileUrl)) {
                pdfIframe.src = fileUrl;
            } else if (isS3Url) {
                pdfIframe.src = previewSourceUrl;
            } else {
                pdfIframe.src = `https://docs.google.com/viewer?url=${encodeURIComponent(fileUrl)}&embedded=true`;
            }
            container.appendChild(pdfIframe);
            break;
        }

        case 'doc':
        case 'docx':
        case 'xls':
        case 'xlsx':
        case 'ppt':
        case 'pptx': {
            const officeViewer = document.createElement('iframe');
            officeViewer.className = 'doc-viewer';
            attachPreviewLoadHandler(officeViewer, container, function () {
                showPreviewError(container, 'Unable to load preview. Please try downloading the file.');
            });
            container.appendChild(officeViewer);

            if (isLocalPreviewUrl(fileUrl)) {
                officeViewer.src = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(fileUrl.startsWith('/') ? (window.location.origin + fileUrl) : fileUrl)}`;
            } else if (isS3Url) {
                fetchPresignedPreviewUrl(fileUrl)
                    .then(function(presignedUrl) {
                        officeViewer.src = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(presignedUrl)}`;
                    })
                    .catch(function() {
                        showPreviewError(container, 'Unable to load preview. Please try downloading the file.');
                    });
            } else {
                officeViewer.src = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(fileUrl)}`;
            }
            break;
        }

        default:
            showPreviewError(container, 'Preview not available for this file type.');
    }
}

// Export functions for use in other modules
if (typeof window !== 'undefined') {
    window.file_explorer = file_explorer;
    window.uploadFormData = uploadFormData;
    window.previewFile = previewFile;
    window.previewDocumentLink = previewDocumentLink;
    window.resolveDocumentPreviewMeta = resolveDocumentPreviewMeta;
    registerDocumentFilePreviewLinks();
}

// ============================================================================
// DOCUMENT UPLOAD BUTTON HANDLERS
// ============================================================================
// Trigger file input when "Add Document" button is clicked in upload_client_receipt_document containers
// This handles: Student Invoice, Record Invoice, Record Payment documents
// NOTE: Client Receipt uses .upload-receipt-doc-btn which has its own handler in blade-inline.js
$(document).on('click', '.upload_client_receipt_document .btn-primary, .upload_client_receipt_document .btn-outline-primary', function(e) {
    // Skip if this is the upload-receipt-doc-btn (has its own handler)
    if ($(this).hasClass('upload-receipt-doc-btn')) {
        return;
    }
    
    e.preventDefault();
    var fileInput = $(this).closest('.upload_client_receipt_document').find('.docclientreceiptupload');
    if (fileInput.length) {
        fileInput.click();
    }
});

