/**
 * Admin Client Detail - Application Stage Module
 * 
 * Handles application stage progression, discontinuation, refunds, and agent assignments
 * 
 * Dependencies:
 *   - jQuery
 *   - Bootstrap (for modals)
 *   - config.js (App object)
 */

'use strict';

// ============================================================================
// ASYNC WRAPPER - Wait for vendor libraries before initialization
// ============================================================================

(async function() {
    // Wait for vendor libraries to be ready
    if (typeof window.vendorLibsReady !== 'undefined') {
        console.log('[application-stage.js] Waiting for vendorLibsReady promise...');
        await window.vendorLibsReady;
        console.log('[application-stage.js] Vendor libraries ready!');
    } else {
        // Fallback: Poll for vendor libraries
        console.log('[application-stage.js] vendorLibsReady not found, polling for libraries...');
        await new Promise((resolve) => {
            const check = () => {
                if (typeof $ !== 'undefined') {
                    console.log('[application-stage.js] All vendor libraries detected!');
                    resolve();
                } else {
                    setTimeout(check, 50);
                }
            };
            check();
        });
    }

// ============================================================================
// FEE MODAL HELPERS (Bootstrap 5 native API with jQuery fallback)
// ============================================================================

var feeModalTriggers = {
    new_fee_option: null,
    new_fee_option_latest: null
};

function feeModalLog(step, detail) {
    if (typeof console === 'undefined' || typeof console.log !== 'function') {
        return;
    }
    if (detail !== undefined) {
        console.log('[fee-modal]', step, detail);
    } else {
        console.log('[fee-modal]', step);
    }
}

function feeModalSnapshot(modalEl) {
    if (!modalEl) {
        return { found: false };
    }
    var instance = (typeof bootstrap !== 'undefined' && bootstrap.Modal)
        ? bootstrap.Modal.getInstance(modalEl)
        : null;
    var computedDisplay = '';
    try {
        computedDisplay = window.getComputedStyle(modalEl).display;
    } catch (e) {
        computedDisplay = 'unknown';
    }
    return {
        found: true,
        id: modalEl.id,
        hasShowClass: modalEl.classList.contains('show'),
        ariaHidden: modalEl.getAttribute('aria-hidden'),
        inlineDisplay: modalEl.style.display || '',
        computedDisplay: computedDisplay,
        bodyModalOpen: document.body.classList.contains('modal-open'),
        hasBootstrapInstance: !!instance,
        bootstrapIsShown: instance && typeof instance._isShown !== 'undefined' ? instance._isShown : null
    };
}

function hideClientDetailModal(modalEl) {
    if (!modalEl) {
        feeModalLog('hide skipped — no modal element');
        return;
    }
    feeModalLog('hide requested', feeModalSnapshot(modalEl));
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var instance = bootstrap.Modal.getInstance(modalEl);
        if (instance) {
            instance.hide();
            return;
        }
    }
    if (typeof jQuery !== 'undefined' && jQuery.fn.modal) {
        jQuery(modalEl).modal('hide');
    }
}

function ensureFeeModalCanShow(modalEl) {
    if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        feeModalLog('ensureCanShow skipped', {
            hasModalEl: !!modalEl,
            hasBootstrap: typeof bootstrap !== 'undefined' && !!bootstrap.Modal
        });
        return;
    }
    var instance = bootstrap.Modal.getInstance(modalEl);
    // Recover from stale Bootstrap state (_isShown true while DOM is hidden)
    if (instance && !modalEl.classList.contains('show')) {
        feeModalLog('disposing stale Bootstrap instance', feeModalSnapshot(modalEl));
        instance.dispose();
    }
}

function showClientDetailModal(modalEl, triggerEl) {
    if (!modalEl) {
        feeModalLog('show failed — modal element not in DOM', { expectedIds: ['new_fee_option', 'new_fee_option_latest'] });
        console.error('[fee-modal] Modal element not found');
        return;
    }

    feeModalLog('show requested', {
        before: feeModalSnapshot(modalEl),
        triggerTag: triggerEl ? triggerEl.tagName : null,
        triggerClass: triggerEl ? triggerEl.className : null
    });

    var modalId = modalEl.id;
    if (modalId === 'new_fee_option' || modalId === 'new_fee_option_latest') {
        if (triggerEl) {
            feeModalTriggers[modalId] = triggerEl;
        }
        var otherModalId = modalId === 'new_fee_option' ? 'new_fee_option_latest' : 'new_fee_option';
        var otherModalEl = document.getElementById(otherModalId);
        if (otherModalEl && otherModalEl.classList.contains('show')) {
            feeModalLog('closing other fee modal first', { otherModalId: otherModalId });
            hideClientDetailModal(otherModalEl);
        }
        ensureFeeModalCanShow(modalEl);
    }

    try {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
            feeModalLog('calling bootstrap.Modal.show()', { modalId: modalId });
            modalInstance.show();
        } else if (typeof jQuery !== 'undefined' && jQuery.fn.modal) {
            feeModalLog('calling jQuery.modal(show) fallback', { modalId: modalId });
            jQuery(modalEl).modal('show');
        } else {
            feeModalLog('show failed — no Bootstrap or jQuery modal API available');
        }
    } catch (err) {
        feeModalLog('show threw an error', { message: err && err.message ? err.message : String(err) });
        console.error('[fee-modal] show error', err);
    }

    feeModalLog('show call finished', { after: feeModalSnapshot(modalEl) });
}

function restoreFeeModalFocus(modalId) {
    var modalEl = document.getElementById(modalId);
    if (!modalEl) {
        feeModalLog('focus restore skipped — modal not found', { modalId: modalId });
        return;
    }

    // Sibling fee modal opened — skip focus restore for the one that just closed
    var otherModalId = modalId === 'new_fee_option' ? 'new_fee_option_latest' : 'new_fee_option';
    var otherModalEl = document.getElementById(otherModalId);
    if (otherModalEl && otherModalEl.classList.contains('show')) {
        feeModalLog('focus restore skipped — sibling modal open', { modalId: modalId, otherModalId: otherModalId });
        feeModalTriggers[modalId] = null;
        return;
    }

    var trigger = feeModalTriggers[modalId];
    feeModalTriggers[modalId] = null;

    feeModalLog('focus restore on hidden', { modalId: modalId, hasTrigger: !!trigger });

    if (trigger && document.contains(trigger)) {
        try {
            trigger.focus({ preventScroll: true });
            return;
        } catch (e) {
            feeModalLog('focus restore on trigger failed', { message: e && e.message ? e.message : String(e) });
        }
    }

    var active = document.activeElement;
    if (active && modalEl.contains(active) && typeof active.blur === 'function') {
        active.blur();
    }
}

// ============================================================================
// APPLICATION STAGE HANDLERS
// ============================================================================

jQuery(document).ready(function($){
    
    // ============================================================================
    // PROCEED TO NEXT STAGE HANDLER
    // ============================================================================
    
    $(document).on('click', '.nextstage', function(){
        var appliid = $(this).attr('data-id');
        var stage = $(this).attr('data-stage');
        var clientId = PageConfig.clientId;
        
        if (!appliid) {
            console.error('Application ID is missing');
            return;
        }
        
        if (!clientId) {
            console.error('Client ID is missing');
            return;
        }
        
        $('.popuploader').show();
        
        var url = App.getUrl('siteUrl') + '/updatestage';
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            data: {
                id: appliid,
                client_id: clientId
            },
            success: function(response){
                $('.popuploader').hide();
                
                // Handle both string and object responses
                var obj = typeof response === 'string' ? $.parseJSON(response) : response;
                
                if(obj.status){
                    // Show success message
                    if($('.custom-error-msg').length){
                        $('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
                    } else {
                        // Create message container if it doesn't exist
                        $('.ifapplicationdetailnot').prepend('<div class="custom-error-msg"><span class="alert alert-success">'+obj.message+'</span></div>');
                    }
                    
                    // Update current stage text
                    $('.curerentstage').text(obj.stage);
                    
                    // Update progress bar if it exists
                    if(obj.width !== undefined){
                        var progressWidth = obj.width;
                        var over = progressWidth > 50 ? '50' : '';
                        var $progressCir = $('#progresscir');
                        if($progressCir.length){
                            // Remove old progress classes
                            $progressCir.removeClass(function(index, className) {
                                return (className.match(/(^|\s)prgs_\S+/g) || []).join(' ');
                            });
                            $progressCir.removeClass(function(index, className) {
                                return (className.match(/(^|\s)over_\S+/g) || []).join(' ');
                            });
                            // Add new progress classes
                            if(over){
                                $progressCir.addClass('over_' + over);
                            }
                            $progressCir.addClass('prgs_' + progressWidth);
                            // Update progress text
                            $progressCir.find('span').text(progressWidth + ' %');
                        }
                    }
                    
                    // Reload application activities log
                    var logsUrl = App.getUrl('getApplicationsLogs') || App.getUrl('siteUrl') + '/get-applications-logs';
                    $.ajax({
                        url: logsUrl,
                        type: 'GET',
                        data: {
                            clientid: clientId,
                            id: appliid
                        },
                        success: function(responses){
                            $('#accordion').html(responses);
                            // Re-initialize Bootstrap Collapse for click functionality
                            if (typeof reinitializeAccordions === 'function') {
                                reinitializeAccordions();
                            }
                        },
                        error: function(xhr, status, error){
                            console.error('Error loading application logs:', error);
                        }
                    });
                    
                    // Update button visibility - hide "Proceed to Next Stage" if at last stage
                    if(obj.displaycomplete){
                        $('.nextstage').hide();
                        $('.completestage').show();
                    }
                } else {
                    // Show error message
                    if($('.custom-error-msg').length){
                        $('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
                    } else {
                        $('.ifapplicationdetailnot').prepend('<div class="custom-error-msg"><span class="alert alert-danger">'+obj.message+'</span></div>');
                    }
                }
            },
            error: function(xhr, status, error){
                $('.popuploader').hide();
                console.error('Error updating stage:', error);
                var errorMsg = 'An error occurred while updating the stage. Please try again.';
                if($('.custom-error-msg').length){
                    $('.custom-error-msg').html('<span class="alert alert-danger">'+errorMsg+'</span>');
                } else {
                    $('.ifapplicationdetailnot').prepend('<div class="custom-error-msg"><span class="alert alert-danger">'+errorMsg+'</span></div>');
                }
            }
        });
    });

    // ============================================================================
    // APPLICATION ACTION BUTTON HANDLERS
    // ============================================================================

    // Handler for "Discontinue Application" button
    $(document).on('click', '.discon_application', function(){
        var appliid = $(this).attr('data-id');
        $('#discon_application').modal('show');
        $('input[name="diapp_id"]').val(appliid);
    });

    // Handler for "Refund Application" button  
    $(document).on('click', '.refund_application', function(){
        var appliid = $(this).attr('data-id');
        $('#refund_application').modal('show');
        $('input[name="reapp_id"]').val(appliid);
    });

    // Handler for "Back to Previous Stage" button
    $(document).on('click', '.backstage', function(){
        var appliid = $(this).attr('data-id');
        var stage = $(this).attr('data-stage');
        var clientId = PageConfig.clientId;
        
        if (!appliid || !clientId) {
            console.error('Application ID or Client ID is missing');
            return;
        }
        
        $('.popuploader').show();
        
        var url = App.getUrl('siteUrl') + '/updatebackstage';
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            data: {
                id: appliid,
                client_id: clientId
            },
            success: function(response){
                $('.popuploader').hide();
                var obj = typeof response === 'string' ? $.parseJSON(response) : response;
                
                if(obj.status){
                    $('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
                    $('.curerentstage').text(obj.stage);
                    
                    // Update progress bar
                    if(obj.width !== undefined){
                        updateProgressBar(obj.width);
                    }
                    
                    // Reload activities accordion
                    reloadApplicationActivities(appliid);
                } else {
                    $('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
                }
            },
            error: function(xhr, status, error){
                $('.popuploader').hide();
                console.error('Error going back to previous stage:', error);
                $('.custom-error-msg').html('<span class="alert alert-danger">Error updating stage. Please try again.</span>');
            }
        });
    });

    // Handler for "Complete Application" button
    $(document).on('click', '.completestage', function(){
        var appliid = $(this).attr('data-id');
        $('#confirmcompleteModal').modal('show');
        $('.acceptapplication').attr('data-id', appliid);
    });

    // Handler for confirming application completion
    $(document).on('click', '.acceptapplication', function(){
        var appliid = $(this).attr('data-id');
        var clientId = PageConfig.clientId;
        
        if (!appliid || !clientId) {
            console.error('Application ID or Client ID is missing');
            return;
        }
        
        $('.popuploader').show();
        $('#confirmcompleteModal').modal('hide');
        
        var url = App.getUrl('siteUrl') + '/completestage';
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            data: {
                id: appliid,
                client_id: clientId
            },
            success: function(response){
                $('.popuploader').hide();
                var obj = typeof response === 'string' ? $.parseJSON(response) : response;
                
                if(obj.status){
                    $('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
                    $('.applicationstatus').html('Completed');
                    $('.ifdiscont').hide();
                    $('.revertapp').show();
                    
                    // Update progress to 100%
                    updateProgressBar(100);
                    
                    // Reload activities accordion
                    reloadApplicationActivities(appliid);
                } else {
                    $('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
                }
            },
            error: function(xhr, status, error){
                $('.popuploader').hide();
                console.error('Error completing application:', error);
                $('.custom-error-msg').html('<span class="alert alert-danger">Error completing application. Please try again.</span>');
            }
        });
    });

    // Handler for "Revert Application" button
    $(document).on('click', '.revertapp', function(){
        var appliid = $(this).attr('data-id');
        $('#revert_application').modal('show');
        $('input[name="revapp_id"]').val(appliid);
    });

    // ============================================================================
    // AGENT ASSIGNMENT HANDLERS
    // ============================================================================

    // Handler for "Add Super Agent" button
    $(document).on('click', '.opensuperagent', function(){
        var appliid = $(this).attr('data-id');
        $('#superagent_application').modal('show');
        $('#siapp_id').val(appliid);
    });

    // Handler for "Add Sub Agent" button
    $(document).on('click', '.opensubagent', function(){
        var appliid = $(this).attr('data-id');
        $('#subagent_application').modal('show');
        $('#sbapp_id').val(appliid);
    });

    // ============================================================================
    // PRODUCT FEE/COMMISSION STATUS HANDLERS
    // ============================================================================

    // Restore focus after hide completes (not on hide.bs — that breaks Bootstrap's cycle)
    $(document).on('show.bs.modal', '#new_fee_option, #new_fee_option_latest', function() {
        feeModalLog('Bootstrap show.bs.modal', feeModalSnapshot(this));
    });

    $(document).on('shown.bs.modal', '#new_fee_option, #new_fee_option_latest', function() {
        feeModalLog('Bootstrap shown.bs.modal', feeModalSnapshot(this));
    });

    $(document).on('hide.bs.modal', '#new_fee_option, #new_fee_option_latest', function() {
        feeModalLog('Bootstrap hide.bs.modal', feeModalSnapshot(this));
    });

    $(document).on('hidden.bs.modal', '#new_fee_option, #new_fee_option_latest', function() {
        feeModalLog('Bootstrap hidden.bs.modal', feeModalSnapshot(this));
        restoreFeeModalFocus(this.id);
    });

    // Handler for "Edit Product Fees" button
    $(document).on('click', '.openpaymentfee', function(e){
        e.preventDefault();
        feeModalLog('click .openpaymentfee', {
            applicationId: $(this).attr('data-id'),
            partnerId: $(this).attr('data-partnerid'),
            targetTag: e.target ? e.target.tagName : null,
            modalInDom: !!document.getElementById('new_fee_option'),
            openpaymentfeeCount: $('.openpaymentfee').length
        });

        var triggerEl = this;
        var appliid = $(this).attr('data-id');
        var partnerid = $(this).attr('data-partnerid');
        
        // Load product fee form via AJAX
        var url = App.getUrl('showProductFee') || App.getUrl('siteUrl') + '/showproductfee';
        feeModalLog('AJAX start showproductfee', { url: url, id: appliid, partnerid: partnerid });

        $.ajax({
            url: url,
            type: 'GET',
            data: {
                id: appliid,
                partnerid: partnerid
            },
            beforeSend: function() {
                try {
                    var $body = $('#new_fee_option .showproductfee');
                    feeModalLog('AJAX beforeSend', {
                        bodyTargetCount: $body.length,
                        crmIconSpinnerAvailable: typeof crmIconSpinner === 'function'
                    });
                    $body.html('<div style="text-align:center;padding:20px;">' + crmIconSpinner(' Loading...') + '</div>');
                    showClientDetailModal(document.getElementById('new_fee_option'), triggerEl);
                } catch (beforeSendErr) {
                    feeModalLog('AJAX beforeSend error', { message: beforeSendErr && beforeSendErr.message ? beforeSendErr.message : String(beforeSendErr) });
                    console.error('[fee-modal] beforeSend error', beforeSendErr);
                }
            },
            success: function(response){
                feeModalLog('AJAX success showproductfee', {
                    responseType: typeof response,
                    responseLength: response ? String(response).length : 0,
                    looksLikeLoginPage: response ? String(response).indexOf('<!DOCTYPE') !== -1 : false
                });
                $('#new_fee_option .showproductfee').html(response);
            },
            error: function(xhr, status, error){
                feeModalLog('AJAX error showproductfee', {
                    status: xhr && xhr.status,
                    statusText: status,
                    error: error
                });
                $('#new_fee_option .showproductfee').html('<div style="text-align:center;padding:20px;color:red;">' + crmIcon('exclamation-triangle') + ' Error loading fee details. Please try again.</div>');
                console.error('[fee-modal] Error loading product fee:', error);
            }
        });
    });

    // Handler for "Edit Commission Status" button (Latest)
    $(document).on('click', '.openpaymentfeeLatest', function(e){
        e.preventDefault();
        feeModalLog('click .openpaymentfeeLatest', {
            applicationId: $(this).attr('data-id'),
            modalInDom: !!document.getElementById('new_fee_option_latest')
        });

        var triggerEl = this;
        var appliid = $(this).attr('data-id');
        
        // Load commission status form via AJAX
        var url = App.getUrl('showProductFeeLatest') || App.getUrl('siteUrl') + '/showproductfeelatest';
        feeModalLog('AJAX start showproductfeelatest', { url: url, id: appliid });

        $.ajax({
            url: url,
            type: 'GET',
            data: {
                id: appliid
            },
            beforeSend: function() {
                try {
                    var $body = $('#new_fee_option_latest .showproductfee_latest');
                    feeModalLog('AJAX beforeSend latest', { bodyTargetCount: $body.length });
                    $body.html('<div style="text-align:center;padding:20px;">' + crmIconSpinner(' Loading...') + '</div>');
                    showClientDetailModal(document.getElementById('new_fee_option_latest'), triggerEl);
                } catch (beforeSendErr) {
                    feeModalLog('AJAX beforeSend latest error', { message: beforeSendErr && beforeSendErr.message ? beforeSendErr.message : String(beforeSendErr) });
                    console.error('[fee-modal] beforeSend latest error', beforeSendErr);
                }
            },
            success: function(response){
                feeModalLog('AJAX success showproductfeelatest', {
                    responseLength: response ? String(response).length : 0
                });
                $('#new_fee_option_latest .showproductfee_latest').html(response);
                
                // Initialize flatpickr for date fields in the loaded modal (dd/mm/yyyy)
                if (typeof flatpickr !== 'undefined') {
                    flatpickr('.showproductfee_latest .date_paid', {
                        dateFormat: 'd/m/Y',
                        allowInput: true
                    });
                }
            },
            error: function(xhr, status, error){
                feeModalLog('AJAX error showproductfeelatest', {
                    status: xhr && xhr.status,
                    statusText: status,
                    error: error
                });
                $('#new_fee_option_latest .showproductfee_latest').html('<div style="text-align:center;padding:20px;color:red;">' + crmIcon('exclamation-triangle') + ' Error loading commission status. Please try again.</div>');
                console.error('[fee-modal] Error loading commission status:', error);
            }
        });
    });

    /**
     * Recalculate Other Fee Option table totals + paid/pending/anticipated.
     * Scoped to #new_fee_option_latest so Product Fees / other pages are untouched.
     */
    function recalculateOtherFeeOptionTotals($modal) {
        $modal = $modal && $modal.length ? $modal : $('#new_fee_option_latest');
        var $rows = $modal.find('#productitemviewlatest tbody.tdata tr.add_fee_option');
        var totalFeePaid = 0;
        var totalCommissionEarned = 0;
        var totalAdjustment = 0;
        var totalCommissionClaimed = 0;
        var paidCommission = 0;
        var pendingCommission = 0;
        var anticipatedCommission = 0;

        $rows.each(function(){
            var $row = $(this);
            var feePaid = parseFloat($row.find('.total_fee_am_2nd').val());
            var commissionEarned = parseFloat($row.find('.commission_cal').val());
            var adjustment = parseFloat($row.find('.adjustment_discount_entry').val());
            var commissionClaimed = parseFloat($row.find('.commission_claimed').val());
            var claimedOrNot = $row.find('select[name="claimed_or_not[]"]').val();

            feePaid = isNaN(feePaid) ? 0 : feePaid;
            commissionEarned = isNaN(commissionEarned) ? 0 : commissionEarned;
            adjustment = isNaN(adjustment) ? 0 : adjustment;
            commissionClaimed = isNaN(commissionClaimed) ? 0 : commissionClaimed;

            totalFeePaid += feePaid;
            totalCommissionEarned += commissionEarned;
            totalAdjustment += adjustment;
            totalCommissionClaimed += commissionClaimed;

            if (claimedOrNot === 'Yes') {
                paidCommission += commissionClaimed;
            } else if (claimedOrNot === 'No') {
                pendingCommission += commissionClaimed;
            } else if (claimedOrNot === 'Anticipated') {
                anticipatedCommission += commissionClaimed;
            }
        });

        $modal.find('.total_fees_paid').html(totalFeePaid);
        $modal.find('.total_commission_earned').html(totalCommissionEarned);
        $modal.find('.total_adjustment_discount_entry').html(totalAdjustment);
        $modal.find('.total_commission_claimed').html(totalCommissionClaimed);
        $modal.find('#paid_commission').html(' ' + paidCommission);
        $modal.find('#pending_commission').html(' ' + pendingCommission);
        $modal.find('#anticipated_commission').html(' ' + anticipatedCommission);
    }

    // Handler for "Add Fee" button inside latest fee modal
    $(document).on('click', '#new_fee_option_latest .fee_option_addbtn_latest a', function(e){
        e.preventDefault();

        var $modal = $('#new_fee_option_latest');
        var $tbody = $modal.find('#productitemviewlatest tbody.tdata');
        var $templateRow = $tbody.find('tr.add_fee_option').last();

        if ($templateRow.length === 0) {
            return;
        }

        var $newRow = $templateRow.clone();
        var defaultCommission = $modal.find('#commission_percentage').val() || '';

        $newRow.find('.custom-error').remove();

        $newRow.find('input').each(function(){
            var $input = $(this);
            var type = ($input.attr('type') || '').toLowerCase();

            if (type === 'hidden') {
                if ($input.hasClass('commission_cal_hidden') || $input.hasClass('commission_claimed_hidden')) {
                    $input.val('');
                }
                return;
            }

            if ($input.hasClass('commission_percentage')) {
                $input.val(defaultCommission);
                return;
            }

            $input.val('');
        });

        $newRow.find('select').each(function(){
            var $select = $(this);
            $select.find('option').prop('selected', false);
            $select.val('');
            $select.find('option[value=""]').prop('selected', true);
        });

        $tbody.append($newRow);

        if (typeof flatpickr !== 'undefined') {
            var dateInput = $newRow.find('.date_paid')[0];
            if (dateInput) {
                if (dateInput._flatpickr && typeof dateInput._flatpickr.destroy === 'function') {
                    dateInput._flatpickr.destroy();
                }
                flatpickr(dateInput, {
                    dateFormat: 'd/m/Y',
                    allowInput: true
                });
            }
        }
    });

    /**
     * Confirm delete without nesting Bootstrap modals.
     * crmConfirm (#crmConfirmModal) is unreliable while #new_fee_option_latest is open
     * (confirm sits behind / already-shown). Swal or native confirm stay on top.
     * Scoped to this handler only — does not change global crmConfirm.
     */
    function confirmOtherFeeRowDelete(message) {
        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            return Swal.fire({
                title: 'Please confirm',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545'
            }).then(function(result) {
                return !!(result && result.isConfirmed);
            });
        }
        return Promise.resolve(window.confirm(message));
    }

    // Delete fee row in Other Fee Option modal (confirm → remove → recalculate)
    $(document).on('click', '#new_fee_option_latest .remove_other_fee_row', function(e){
        e.preventDefault();
        e.stopPropagation();

        var $modal = $('#new_fee_option_latest');
        var $row = $(this).closest('tr.add_fee_option');
        var $tbody = $modal.find('#productitemviewlatest tbody.tdata');

        if ($row.length === 0) {
            return;
        }

        if ($tbody.find('tr.add_fee_option').length <= 1) {
            if (typeof window.toastMsg === 'function') {
                window.toastMsg('At least one fee row is required.', 'error');
            } else {
                alert('At least one fee row is required.');
            }
            return;
        }

        var confirmMessage = 'This fee row will be permanently removed when you save. Are you sure you want to delete it?';
        var doRemove = function(){
            var dateInput = $row.find('.date_paid')[0];
            if (dateInput && dateInput._flatpickr && typeof dateInput._flatpickr.destroy === 'function') {
                dateInput._flatpickr.destroy();
            }
            $row.remove();
            recalculateOtherFeeOptionTotals($modal);
        };

        confirmOtherFeeRowDelete(confirmMessage).then(function(ok){
            if (ok) {
                doRemove();
            }
        });
    });

    console.log('[application-stage.js] Application stage handlers initialized');
    feeModalLog('module ready', {
        bootstrapAvailable: typeof bootstrap !== 'undefined' && !!bootstrap.Modal,
        jQueryModalAvailable: typeof jQuery !== 'undefined' && !!jQuery.fn.modal,
        newFeeOptionInDom: !!document.getElementById('new_fee_option'),
        newFeeOptionLatestInDom: !!document.getElementById('new_fee_option_latest')
    });
});

})(); // End async wrapper

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Reload application activities accordion after state change
 * @param {number} appliid - Application ID
 */
function reloadApplicationActivities(appliid) {
    var clientId = PageConfig.clientId;
    var url = App.getUrl('getApplicationsLogs') || App.getUrl('siteUrl') + '/get-applications-logs';
    $.ajax({
        url: url,
        type: 'GET',
        data: { 
            id: appliid,
            clientid: clientId
        },
        success: function(response) {
            // Replace the accordion content
            $('#accordion').html(response);
            // Re-initialize Bootstrap Collapse for click functionality
            if (typeof reinitializeAccordions === 'function') {
                reinitializeAccordions();
            }
            if (typeof window.refreshCrmIcons === 'function') {
                var accordionRoot = document.getElementById('accordion');
                if (accordionRoot) {
                    window.refreshCrmIcons(accordionRoot);
                }
            }
            console.log('Activities reloaded successfully');
        },
        error: function(xhr, status, error) {
            console.error('Error reloading activities:', error);
        }
    });
}

/**
 * Update the circular progress bar
 * @param {number} width - Progress percentage (0-100)
 */
function updateProgressBar(width) {
    $('.progress-circle span').html(width + ' %');
    var over = width > 50 ? '50' : '';
    $('#progresscir').removeClass();
    $('#progresscir').addClass('progress-circle');
    $('#progresscir').addClass('prgs_' + width);
    $('#progresscir').addClass('over_' + over);
}

// Make functions available globally
if(typeof window !== 'undefined') {
    window.reloadApplicationActivities = reloadApplicationActivities;
    window.updateProgressBar = updateProgressBar;
}
