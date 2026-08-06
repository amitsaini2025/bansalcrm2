/**
 * Admin Client Detail Page - Page-Specific JavaScript
 * 
 * This file contains JavaScript code specific to the Admin Client Detail page.
 * Common/shared functionality should be in /js/common/ files.
 * 
 * Dependencies (loaded before this file):
 *   - config.js
 *   - ajax-helpers.js
 *   - crud-operations.js
 *   - activity-handlers.js
 *   - document-handlers.js
 *   - ui-components.js
 *   - utilities.js
 */

'use strict';

// ============================================================================
// ASYNC WRAPPER - Wait for vendor libraries before initialization
// ============================================================================

(async function() {
    // Wait for vendor libraries to be ready
    if (typeof window.vendorLibsReady !== 'undefined') {
        console.log('[client-detail.js] Waiting for vendorLibsReady promise...');
        await window.vendorLibsReady;
        console.log('[client-detail.js] Vendor libraries ready!');
    } else {
        // Fallback: Poll for vendor libraries
        console.log('[client-detail.js] vendorLibsReady not found, polling for libraries...');
        await new Promise((resolve) => {
            const check = () => {
                if (typeof $ !== 'undefined' &&
                    typeof window.initTomSelect === 'function' &&
                    typeof flatpickr !== 'undefined') {
                    console.log('[client-detail.js] All vendor libraries detected!');
                    resolve();
                } else {
                    setTimeout(check, 50);
                }
            };
            check();
        });
    }

// ============================================================================
// INITIALIZATION
// ============================================================================

// Download + ChatGPT handlers moved to dedicated module file.

// ============================================================================
// MAIN JQUERY READY BLOCK
// ============================================================================

jQuery(document).ready(function($){
  
    // Tab visibility and layout handlers moved to dedicated module file.
  
    // UI initialization moved to ui-initialization module.

    // Google review + not picked call handlers moved to communications module.

    // Receipt flatpickr setup moved to ui-initialization module.

    // NOTE: .openproductrinfo handler has been moved to detail.blade.php inline script
    // to avoid duplication and ensure calculateReceiptTotal() is called properly

    // Receipt helpers moved to receipts-and-payments module.

    // ============================================================================
    // TAG HANDLERS (Client-only tags)
    // ============================================================================

    // UI initialization moved to ui-initialization module.

    // Not picked call handler moved to communications module.

    

    // Email/phone verification handler moved to session-handlers.js module

    // UI layout handlers moved to dedicated module file.

    // Modal handlers moved to modal-handlers module.

    // Assignment handlers moved to assignments module.

    // ============================================================================
    // OVERRIDE COMMON FUNCTIONS WITH PAGE-SPECIFIC IMPLEMENTATIONS
    // ============================================================================
    
    // Note: These functions override the common ones with more detailed implementations
    // If needed, these can be moved to common files later
    
    // Pin and publish handlers moved to pin-and-publish module.

    // Assignee handlers moved to assignee-handlers.js module

    // Document action handlers moved to document-actions module.

    // Delete handlers moved to delete-handlers module.

    // Pin and publish handlers moved to pin-and-publish module.

    // Note handlers moved to notes module.

    // Application workflow/partner/product handlers moved to application-handlers.js module

    // Email and template handlers moved to email-handlers.js module

    // Email template handlers moved to email-handlers.js module

    // Interested product handler moved to application-handlers.js module

    // Document upload handlers moved to document-upload.js module

    // Convert to application handler moved to application-handlers.js module
    
    // Application tab click handler moved to application-handlers.js module

    // Document rename handlers moved to document-rename.js module

    // DataTable initialization moved to datatable-handlers.js module

    // Application detail view handlers moved to application-handlers.js module
    
    // Application modal openers moved to application-handlers.js module

    // Application stage progression handlers moved to application-stage.js module
    
    // Application action button handlers moved to application-stage.js module
    
    // Agent assignment handlers moved to application-stage.js module
    
    // Product fee/commission status handlers moved to application-stage.js module

    // Commission calculation handlers moved to commission-handlers.js module

    // Drag and drop handlers for application checklist uploads moved to drag-drop-handlers.js module

    // Document rename handlers moved to document-rename.js module

    // All document rename handlers moved to document-rename.js module
    
    // Tom Select initialization for application forms moved to application-handlers.js module
    
    // Checklist file selection handler moved to datatable-handlers.js module
    
    // Additional handlers and comments - most functionality moved to dedicated modules
    
    // Email form submission handler moved to email-handlers.js module

    console.log('Admin Client Detail page initialized');
});

})(); // End async wrapper

// Document context menu moved to dedicated module file.

// ============================================================================
// ADDITIONAL PAGE-SPECIFIC FUNCTIONS
// ============================================================================

/**
 * Re-initialize Bootstrap Collapse on accordion headers
 * Must be called after replacing accordion HTML to restore click functionality
 */
function reinitializeAccordions() {
    // Re-initialize Bootstrap collapse on all accordion headers
    var collapseElements = document.querySelectorAll('#accordion [data-bs-toggle="collapse"]');
    collapseElements.forEach(function(element) {
        // Check if already initialized to avoid duplicates
        var instance = bootstrap.Collapse.getInstance(element);
        if (!instance) {
            new bootstrap.Collapse(element, {
                toggle: false // Don't auto-toggle on init
            });
        }
    });
}

// reloadApplicationActivities() and updateProgressBar() moved to application-stage.js module

