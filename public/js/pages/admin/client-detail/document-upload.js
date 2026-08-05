/**
 * Admin Client Detail - Document Upload Module
 * 
 * Handles document upload functionality for various document types
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
        console.log('[document-upload.js] Waiting for vendorLibsReady promise...');
        await window.vendorLibsReady;
        console.log('[document-upload.js] Vendor libraries ready!');
    } else {
        // Fallback: Poll for vendor libraries
        console.log('[document-upload.js] vendorLibsReady not found, polling for libraries...');
        await new Promise((resolve) => {
            const check = () => {
                if (typeof $ !== 'undefined') {
                    console.log('[document-upload.js] All vendor libraries detected!');
                    resolve();
                } else {
                    setTimeout(check, 50);
                }
            };
            check();
        });
    }

// ============================================================================
// DOCUMENT UPLOAD HANDLERS
// ============================================================================

jQuery(document).ready(function($){
    
    // ============================================================================
    // ALL DOCUMENTS UPLOAD HANDLERS
    // ============================================================================
    
    $(document).on('click', '.add_alldocument_doc', function () {
        $('.create_alldocument_docs').modal('show');
    });

    // Destroy Tom Select on #openalldocsmodal close so it re-inits cleanly when reopened.
    $(document).on('hidden.bs.modal', '#openalldocsmodal', function () {
        var checklistEl = document.getElementById('checklist');
        if (checklistEl && typeof destroyTomSelect === 'function') {
            destroyTomSelect(checklistEl);
        }
    });

    $(document).on('hidden.bs.modal', '#create_checklist', function () {
        if (typeof destroyTomSelect === 'function') {
            destroyTomSelect('#document_type');
        }
    });

    // Trigger file input when "Add Document" button is clicked
    $(document).on('click', '.allupload_document .btn-primary', function(e) {
        e.preventDefault();
        $(this).closest('form').find('.alldocupload').click();
    });

    $(document).on('click', '.alldocupload', function() {
        $(this).attr("value", "");
    });

    $(document).on('change', '.alldocupload', function() {
        console.log('document-upload.js: File upload detected for .alldocupload');
        $('.popuploader').show();
        var fileidL = $(this).attr("data-fileid");
        var formData = new FormData($('#upload_form_'+fileidL)[0]);
        var url = App.getUrl('uploadAllDocument') || App.getUrl('siteUrl') + '/upload-alldocument';
        
        // Check if category system is active
        var isCategorySystemActive = (typeof window.DocumentCategoryManager !== 'undefined');
        console.log('document-upload.js: Category system active?', isCategorySystemActive);
        
        $.ajax({
            url: url,
            type:'POST',
            headers: { 'X-CSRF-TOKEN': App.getCsrf()},
            datatype:'json',
            data: formData,
            contentType: false,
            processData: false,
            success: function(responses){
                console.log('document-upload.js: Upload success response:', responses);
                $('.popuploader').hide();
                var ress = typeof responses === 'string' ? JSON.parse(responses) : responses;
                if(ress.status){
                    $('.custom-error-msg').html('<span class="alert alert-success">'+ress.message+'</span>');
                    
                    // If category system is active, reload current category documents
                    if(isCategorySystemActive && window.DocumentCategoryManager.currentCategoryId) {
                        console.log('document-upload.js: Reloading category documents for category:', window.DocumentCategoryManager.currentCategoryId);
                        window.DocumentCategoryManager.loadCategoryDocuments(window.DocumentCategoryManager.currentCategoryId);
                        window.DocumentCategoryManager.loadCategories(true);
                    } else {
                        // Old behavior: update with server HTML (for non-category pages)
                        console.log('document-upload.js: Using old HTML update method');
                        $('.alldocumnetlist').html(ress.data);
                        $('.allgriddata').html(ress.griddata);
                    }
                }else{
                    var failMsg = (ress && ress.message) ? ress.message : 'Upload failed. Please try again.';
                    $('.custom-error-msg').html('<span class="alert alert-danger">'+failMsg+'</span>');
                    if (typeof window.toastMsg === 'function') {
                        window.toastMsg(failMsg, 'error');
                    }
                }
                if(typeof getallactivities === 'function') {
                    getallactivities();
                }
            },
            error: function() {
                $('.popuploader').hide();
                var failMsg = 'Upload failed. Please try again.';
                $('.custom-error-msg').html('<span class="alert alert-danger">'+failMsg+'</span>');
                if (typeof window.toastMsg === 'function') {
                    window.toastMsg(failMsg, 'error');
                }
            }
        });
    });

    // ============================================================================
    // APPLICATION CHECKLIST FILE UPLOAD MODAL HANDLERS
    // ============================================================================
    
    $(document).on('click', '.openfileupload', function(){
        var id = $(this).attr('data-id');
        var type = $(this).attr('data-type');
        var typename = $(this).attr('data-typename');
        var aid = $(this).attr('data-aid');
        $(".checklisttype").val(type);
        $(".checklistid").val(id);
        $(".checklisttypename").val(typename);
        $(".application_id").val(aid);
        if (typeof window.showCrmModal === 'function') {
            window.showCrmModal('openfileuploadmodal');
        } else {
            $('#openfileuploadmodal').modal('show');
        }
    });

    // Handler for opendocnote - Add Document icon in application detail
    $(document).on('click', '.opendocnote', function(event){
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        var apptype = $(this).attr('data-app-type');
        var typename = $(this).attr('data-typename');
        var aid = $(this).attr('data-id');
        var clientid = $(this).attr('data-appdocclientid');
        $(".checklisttype").val(apptype);
        $(".checklistid").val(''); // No specific checklist id for general document upload
        $(".checklisttypename").val(typename);
        $(".application_id").val(aid);
        $(".app_doc_client_id").val(clientid);
        if (typeof window.showCrmModal === 'function') {
            window.showCrmModal('openfileuploadmodal');
        } else {
            $('#openfileuploadmodal').modal('show');
        }
    });

    console.log('[document-upload.js] Document upload handlers initialized');
});

})(); // End async wrapper
