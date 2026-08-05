/**
 * Admin Client Detail - Session Handlers Module
 * 
 * Handles email/phone verification and session-related functionality
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
        console.log('[session-handlers.js] Waiting for vendorLibsReady promise...');
        await window.vendorLibsReady;
        console.log('[session-handlers.js] Vendor libraries ready!');
    } else {
        // Fallback: Poll for vendor libraries
        console.log('[session-handlers.js] vendorLibsReady not found, polling for libraries...');
        await new Promise((resolve) => {
            const check = () => {
                if (typeof $ !== 'undefined') {
                    console.log('[session-handlers.js] All vendor libraries detected!');
                    resolve();
                } else {
                    setTimeout(check, 50);
                }
            };
            check();
        });
    }

// ============================================================================
// EMAIL/PHONE VERIFICATION HANDLER
// ============================================================================

jQuery(document).ready(function($){
    
    $('.manual_email_phone_verified').on('change', function(){
        var $checkbox = $(this);
        var previousChecked = !$checkbox.is(':checked');
        if( $checkbox.is(":checked") ) {
            $('.manual_email_phone_verified').val(1);
            var manual_email_phone_verified = 1;
        } else {
            $('.manual_email_phone_verified').val(0);
            var manual_email_phone_verified = 0;
        }

        var client_id = App.getPageConfig('clientId');
        var url = App.getUrl('clientUpdateEmailVerified') || App.getUrl('siteUrl') + '/clients/update-email-verified';

        function revertCheckbox() {
            // prop does not re-fire change; keep UI aligned with server on failure
            $checkbox.prop('checked', previousChecked);
            $('.manual_email_phone_verified').val(previousChecked ? 1 : 0);
        }

        function showFail(msg) {
            if (typeof window.toastMsg === 'function') {
                window.toastMsg(msg || 'Please try again', 'error');
            } else {
                alert(msg || 'Please try again');
            }
        }

        $.ajax({
            url: url,
            headers: { 'X-CSRF-TOKEN': App.getCsrf()},
            type:'POST',
            data:{manual_email_phone_verified:manual_email_phone_verified,client_id:client_id},
            success: function(responses){
                var res = responses;
                if (typeof responses === 'string') {
                    try {
                        res = $.parseJSON(responses);
                    } catch (e) {
                        revertCheckbox();
                        showFail('Invalid server response');
                        return;
                    }
                }
                if (res && res.status) {
                    location.reload();
                } else {
                    revertCheckbox();
                    showFail(res && res.message ? res.message : 'Please try again');
                }
            },
            error: function(){
                revertCheckbox();
                showFail('Could not update verification. Please try again.');
            }
        });
    });

    console.log('[session-handlers.js] Session handlers initialized');
});

})(); // End async wrapper
