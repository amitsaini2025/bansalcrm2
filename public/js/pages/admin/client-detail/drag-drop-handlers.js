/**
 * Admin Client Detail - Drag and Drop Handlers Module
 * 
 * Handles drag and drop file uploads for application checklists
 * 
 * Dependencies:
 *   - jQuery
 *   - config.js (App object)
 */

'use strict';

// ============================================================================
// ASYNC WRAPPER - Wait for vendor libraries before initialization
// ============================================================================

(async function() {
    // Wait for vendor libraries to be ready
    if (typeof window.vendorLibsReady !== 'undefined') {
        console.log('[drag-drop-handlers.js] Waiting for vendorLibsReady promise...');
        await window.vendorLibsReady;
        console.log('[drag-drop-handlers.js] Vendor libraries ready!');
    } else {
        // Fallback: Poll for vendor libraries
        console.log('[drag-drop-handlers.js] vendorLibsReady not found, polling for libraries...');
        await new Promise((resolve) => {
            const check = () => {
                if (typeof $ !== 'undefined') {
                    console.log('[drag-drop-handlers.js] All vendor libraries detected!');
                    resolve();
                } else {
                    setTimeout(check, 50);
                }
            };
            check();
        });
    }

// ============================================================================
// DRAG AND DROP HANDLERS FOR APPLICATION CHECKLIST UPLOADS
// ============================================================================

jQuery(document).ready(function($){
    
    $(document).on("dragover", "#ddArea", function() {
        $(this).addClass("drag_over");
        return false;
    });

    $(document).on("dragleave", "#ddArea", function() {
        $(this).removeClass("drag_over");
        return false;
    });

    // Click to browse — only empty drop area (not interactive children)
    $(document).on("click", "#ddArea", function(e) {
        if ($(e.target).closest('input, button, a, select, textarea, label, .dropdown, .dropdown-menu').length) {
            return;
        }
        applicationFileExplorer();
    });

    $(document).on("drop", "#ddArea", function(e) {
        e.preventDefault();
        $(this).removeClass("drag_over");
        var formData = new FormData();
        var files = e.originalEvent.dataTransfer.files;
        for (var i = 0; i < files.length; i++) {
            formData.append("file[]", files[i]);
        }
        formData.append("type", $('.checklisttype').val());
        formData.append("typename", $('.checklisttypename').val());
        formData.append("id", $('.checklistid').val());
        formData.append("application_id", $('.application_id').val());
        formData.append("client_id", $('.app_doc_client_id').val());
        applicationUploadFormData(formData);
    });

    console.log('[drag-drop-handlers.js] Drag and drop handlers initialized');
});

})(); // End async wrapper

// ============================================================================
// FILE EXPLORER AND UPLOAD FUNCTIONS
// ============================================================================

/**
 * Page-specific file explorer for application checklist uploads
 */
function applicationFileExplorer() {
    const selectfile = document.getElementById("selectfile");
    if (!selectfile) {
        console.warn("selectfile element not found");
        return;
    }
    selectfile.click();
    selectfile.onchange = function() {
        var files = selectfile.files;
        var formData = new FormData();

        for (var i = 0; i < files.length; i++) {
            formData.append("file[]", files[i]);
        }
        formData.append("type", $('.checklisttype').val());
        formData.append("typename", $('.checklisttypename').val());
        formData.append("id", $('.checklistid').val());
        formData.append("application_id", $('.application_id').val());
        formData.append("client_id", $('.app_doc_client_id').val());
        applicationUploadFormData(formData);
    };
}

/**
 * Page-specific upload function for application checklist uploads
 * @param {FormData} form_data - The form data containing files and metadata
 */
function applicationUploadFormData(form_data) {
    function updateUploadSummary(type, message) {
        var summaryEl = document.getElementById('uploadSummary');
        if (!summaryEl) {
            return;
        }
        summaryEl.className = 'alert alert-' + type;
        summaryEl.textContent = message;
        summaryEl.style.display = 'block';
    }

    function showUploadToast(type, title, message) {
        if (typeof iziToast !== 'undefined') {
            iziToast[type]({
                title: title,
                message: message,
                position: 'topRight',
                timeout: 8000
            });
        } else {
            alert(title + ': ' + message);
        }
    }

    $('.popuploader').show();
    var url = App.getUrl('applicationChecklistUpload') || App.getUrl('siteUrl') + '/application/checklistupload';
    $.ajax({
        url: url,
        method: "POST",
        headers: { 'X-CSRF-TOKEN': App.getCsrf()},
        data: form_data,
        datatype: 'json',
        contentType: false,
        cache: false,
        processData: false,
        success: function(response) {
            var obj = typeof response === 'string' ? $.parseJSON(response) : response;
            $('.popuploader').hide();

            if (!obj) {
                showUploadToast('error', 'Upload failed', 'Unable to upload files.');
                updateUploadSummary('danger', 'Upload failed. Unable to upload files.');
                return;
            }
            if (obj.status === false && !obj.doclistdata) {
                showUploadToast('error', 'Upload failed', obj.message || 'Unable to upload files.');
                updateUploadSummary('danger', obj.message || 'Upload failed. Unable to upload files.');
                return;
            }

            $('#openfileuploadmodal').modal('hide');
            $('.mychecklistdocdata').html(obj.doclistdata || '');
            $('.checklistuploadcount').html(obj.applicationuploadcount || '');
            if (obj.type && obj.checklistdata) {
                $('.'+obj.type+'_checklists').html(obj.checklistdata);
            }
            if ($('#selectfile').length) {
                $('#selectfile').val('');
            }

            if (obj.status === false && obj.message) {
                showUploadToast('warning', 'Upload completed with errors', obj.message);
                updateUploadSummary('warning', obj.message);
            }

            if (obj.upload_summary) {
                var summary = obj.upload_summary;
                var failedFiles = summary.failed_files || [];
                if (summary.failed_count > 0) {
                    var detailText = failedFiles.map(function(item) {
                        return item.name + (item.reason ? ' (' + item.reason + ')' : '');
                    }).join(', ');
                    showUploadToast(
                        'warning',
                        'Upload completed with errors',
                        'Uploaded ' + summary.uploaded_count + '/' + summary.total + '. Failed: ' + detailText
                    );
                    updateUploadSummary(
                        'warning',
                        'Uploaded ' + summary.uploaded_count + '/' + summary.total + '. Failed: ' + detailText
                    );
                } else {
                    showUploadToast('success', 'Upload completed', summary.uploaded_count + ' file(s) uploaded.');
                    updateUploadSummary('success', summary.uploaded_count + ' file(s) uploaded.');
                }
            }

            if(obj.application_id){
                var logsUrl = App.getUrl('getApplicationsLogs') || App.getUrl('siteUrl') + '/get-applications-logs';
                $.ajax({
                    url: logsUrl,
                    type:'GET',
                    data:{id: obj.application_id},
                    success: function(responses){
                        $('#accordion').html(responses);
                        // Re-initialize Bootstrap Collapse for click functionality
                        if (typeof reinitializeAccordions === 'function') {
                            reinitializeAccordions();
                        }
                    }
                });
            }
        },
        error: function(xhr) {
            $('.popuploader').hide();
            var message = 'Unable to upload files. Please try again.';
            if (xhr && xhr.responseText) {
                try {
                    var errObj = $.parseJSON(xhr.responseText);
                    if (errObj && errObj.message) {
                        message = errObj.message;
                    }
                } catch (e) {
                    // keep default message
                }
            }
            showUploadToast('error', 'Upload failed', message);
            updateUploadSummary('danger', message);
        }
    });
}

// Make these functions available globally for backward compatibility
if(typeof window !== 'undefined') {
    window.applicationFileExplorer = applicationFileExplorer;
    window.applicationUploadFormData = applicationUploadFormData;
}
