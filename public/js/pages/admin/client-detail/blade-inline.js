/**
 * Admin Client Detail - Blade Inline JavaScript
 * 
 * Contains Blade-specific functionality that uses Laravel Blade variables
 * This file is included after all other modules and contains code that
 * cannot be extracted to standalone JS files due to Blade variable dependencies.
 * 
 * NOTE: This file should remain in sync with detail.blade.php inline scripts
 * 
 * Dependencies:
 *   - jQuery
 *   - Bootstrap 5
 *   - Flatpickr
 *   - All other page modules
 */

'use strict';

function toastMsg(message, type) {
    if (typeof window.toastMsg === 'function') {
        window.toastMsg(message, type);
    } else if (message) {
        alert(message);
    }
}

// ============================================================================
// TAB URL SYNCHRONIZATION
// ============================================================================

// Keep URL in sync with active tab and honor ?tab= on load
(function() {
    var tabList = document.getElementById('client_tabs');
    if (!tabList) {
        return;
    }

    var tabLinks = tabList.querySelectorAll('[data-bs-toggle="tab"][data-tab]');
    if (!tabLinks.length) {
        return;
    }

    var baseUrl = tabList.getAttribute('data-base-url');
    if (!baseUrl) {
        return;
    }
    var activeTabSlug = tabList.getAttribute('data-active-tab');
    var applicationId = tabList.getAttribute('data-application-id');
    var base = new URL(baseUrl, window.location.origin);
    var basePath = base.pathname.replace(/\/+$/, '');
    var applicationPath = applicationId ? basePath + '/application/' + applicationId : null;

    var params = new URLSearchParams(window.location.search);
        var initialTab = params.get('tab');
        if (initialTab) {
            var normalizedInitialTab = initialTab === 'noteterm' ? 'notestrm' : initialTab;
            if (normalizedInitialTab === 'documents' || normalizedInitialTab === 'migrationdocuments') {
                normalizedInitialTab = 'alldocuments';
            }
        var initialTrigger = tabList.querySelector('[data-tab="' + normalizedInitialTab + '"]');
        if (initialTrigger && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(initialTrigger).show();
        }
        var migratedUrl = new URL(window.location.href);
        migratedUrl.searchParams.delete('tab');
        if (normalizedInitialTab === 'application' && applicationPath) {
            migratedUrl.pathname = applicationPath;
        } else {
            migratedUrl.pathname = normalizedInitialTab === 'activities' ? basePath : basePath + '/' + normalizedInitialTab;
        }
        history.replaceState(null, '', migratedUrl.toString());
    } else if (activeTabSlug) {
        var canonicalUrl = new URL(window.location.href);
        canonicalUrl.searchParams.delete('tab');
        if (activeTabSlug === 'application' && applicationPath) {
            canonicalUrl.pathname = applicationPath;
        } else {
            canonicalUrl.pathname = activeTabSlug === 'activities' ? basePath : basePath + '/' + activeTabSlug;
        }
        history.replaceState(null, '', canonicalUrl.toString());
    }

    tabLinks.forEach(function(link) {
        link.addEventListener('shown.bs.tab', function(event) {
            var tabValue = event.target.getAttribute('data-tab');
            if (!tabValue) {
                return;
            }
            var url = new URL(window.location.href);
            var currentApplicationId = tabList.getAttribute('data-application-id');
            var currentApplicationPath = currentApplicationId ? basePath + '/application/' + currentApplicationId : null;
            url.searchParams.delete('tab');
            if (tabValue === 'application' && currentApplicationPath) {
                url.pathname = currentApplicationPath;
            } else {
                url.pathname = tabValue === 'activities' ? basePath : basePath + '/' + tabValue;
            }
            history.replaceState(null, '', url.toString());
        });
    });
})();

// ============================================================================
// BOOTSTRAP DROPDOWN INITIALIZATION
// ============================================================================

// Initialize Bootstrap 5 dropdowns for Action buttons
(function() {
    var dropdownInitAttempts = 0;
    var maxAttempts = 50; // 5 seconds max wait
    
    function initDropdowns() {
        dropdownInitAttempts++;
        
        // Check if Bootstrap is available
        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            // Initialize all dropdown toggles that aren't already initialized
            var dropdownToggles = document.querySelectorAll('[data-bs-toggle="dropdown"]');
            var initializedCount = 0;
            
            dropdownToggles.forEach(function(element) {
                // Check if dropdown is already initialized
                if (!bootstrap.Dropdown.getInstance(element)) {
                    try {
                        new bootstrap.Dropdown(element);
                        initializedCount++;
                    } catch (e) {
                        console.warn('Failed to initialize dropdown:', e, element);
                    }
                }
            });
            
            if (initializedCount > 0) {
                console.log('Initialized ' + initializedCount + ' Bootstrap dropdown(s)');
            }
            
            // Setup mutation observer for dynamically added dropdowns
            if (!window.dropdownObserverSetup) {
                window.dropdownObserverSetup = true;
                
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.addedNodes.length > 0) {
                            mutation.addedNodes.forEach(function(node) {
                                if (node.nodeType === 1) { // Element node
                                    // Check for dropdown toggles in the added node
                                    var dropdowns = node.querySelectorAll ? node.querySelectorAll('[data-bs-toggle="dropdown"]') : [];
                                    dropdowns.forEach(function(element) {
                                        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown && !bootstrap.Dropdown.getInstance(element)) {
                                            try {
                                                new bootstrap.Dropdown(element);
                                            } catch (e) {
                                                console.warn('Failed to initialize dynamic dropdown:', e);
                                            }
                                        }
                                    });
                                    
                                    // Also check if the node itself is a dropdown toggle
                                    if (node.hasAttribute && node.hasAttribute('data-bs-toggle') && node.getAttribute('data-bs-toggle') === 'dropdown') {
                                        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown && !bootstrap.Dropdown.getInstance(node)) {
                                            try {
                                                new bootstrap.Dropdown(node);
                                            } catch (e) {
                                                console.warn('Failed to initialize dynamic dropdown:', e);
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    });
                });
                
                // Observe the document body for changes
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }
        } else if (dropdownInitAttempts < maxAttempts) {
            // Retry if Bootstrap isn't loaded yet
            setTimeout(initDropdowns, 100);
        } else {
            console.error('Bootstrap Dropdown not available after ' + maxAttempts + ' attempts');
        }
    }
    
    // Start initialization when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDropdowns);
    } else {
        // DOM is already ready
        initDropdowns();
    }
    
    // Also try after window load as a fallback
    window.addEventListener('load', function() {
        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            initDropdowns();
        }
    });
})();

// ============================================================================
// ACTIVITIES FILTER FUNCTIONALITY
// ============================================================================

jQuery(document).ready(function($) {
    // Activity Type Button Click Handler (main buttons)
    $('.activity-type-btn:not(.dropdown-toggle)').on('click', function() {
        var type = $(this).data('type');
        
        // Remove active class from all buttons and dropdown items
        $('.activity-type-btn').removeClass('active');
        $('.activity-type-dropdown-item').removeClass('active');
        
        // Add active class to clicked button
        $(this).addClass('active');
        
        // Reset dropdown button text
        $('.activity-type-btn.dropdown-toggle').text('More...').removeClass('active');
        
        // Update hidden input
        $('#activity_type_input').val(type);
    });

    // Activity Type Dropdown Item Click Handler
    $(document).on('click', '.activity-type-dropdown-item', function(e) {
        e.preventDefault();
        var type = $(this).data('type');
        var label = $(this).text().trim();
        
        // Remove active class from all buttons and dropdown items
        $('.activity-type-btn').removeClass('active');
        $('.activity-type-dropdown-item').removeClass('active');
        
        // Add active class to clicked dropdown item
        $(this).addClass('active');
        
        // Update dropdown button - text and active class
        var $dropdownBtn = $('.activity-type-btn.dropdown-toggle');
        $dropdownBtn.text(label).addClass('active');
        
        // Update hidden input
        $('#activity_type_input').val(type);
    });

    // Initialize Date Pickers with Flatpickr
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.date-filter', {
            dateFormat: 'Y-m-d',
            allowInput: true,
            altInput: false
        });
    } else {
        console.warn('Flatpickr is not available. Please ensure vendor-libs.js is loaded.');
    }

    // Auto-submit form on Enter key in search box
    $('#activity_search').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#activitiesFilterForm').submit();
        }
    });
});

// ============================================================================
// BULK UPLOAD FUNCTIONALITY FOR DOCUMENTS TAB
// ============================================================================

let bulkUploadFiles = [];
let currentClientId = window.PageConfig && window.PageConfig.clientId;

// Toggle bulk upload dropzone
$(document).on('click', '.bulk-upload-toggle-btn', function() {
    const dropzoneContainer = $('.bulk-upload-dropzone-container');
    
    if (dropzoneContainer.is(':visible')) {
        dropzoneContainer.slideUp();
        $(this).html(crmIcon('upload') + ' Bulk Upload');
        // Clear files
        bulkUploadFiles = [];
        dropzoneContainer.find('.bulk-upload-file-list').hide();
        dropzoneContainer.find('.file-count').text('0');
    } else {
        dropzoneContainer.slideDown();
        $(this).html(crmIcon('times') + ' Close');
    }
});

// Click to browse files — only empty dropzone area (not interactive children)
$(document).on('click', '.bulk-upload-dropzone', function(e) {
    if ($(e.target).closest(
        'input, button, a, select, textarea, label, ' +
        '.bulk-upload-remove-file, .dropdown, .dropdown-menu, ' +
        '.bulk-upload-file-list'
    ).length) {
        return;
    }
    e.preventDefault();
    $(this).find('.bulk-upload-file-input').first().click();
});

// File input change
$(document).on('change', '.bulk-upload-file-input', function() {
    const files = this.files;
    if (files.length > 0) {
        handleBulkFilesSelected(files);
    }
});

// Drag and drop handlers - Using proven partner implementation approach
jQuery(document).ready(function($) {
    console.log('[DRAG-DROP] Initializing drag and drop for client documents...');
    
    // CRITICAL: Window-level drag event prevention (required for Windows browsers)
    $(window).on('dragenter dragover', function(e) {
        if ($('.bulk-upload-dropzone:visible').length) {
            e.preventDefault();
        }
    });

    $(window).on('drop', function(e) {
        if ($('.bulk-upload-dropzone:visible').length) {
            e.preventDefault();
        }
    });
    
    // Prevent browser from opening file on drop outside the dropzone
    $(document).on('dragover drop', function(e) {
        if ($('.bulk-upload-dropzone:visible').length) {
            e.preventDefault();
        }
    });
    
    // Bind drag and drop handlers to dropzone
    function bindDropzoneDragHandlers() {
        $('.bulk-upload-dropzone').each(function() {
            // Skip if already bound
            if (this.dataset.dragBound === '1') {
                console.log('[DRAG-DROP] Handlers already bound, skipping');
                return;
            }
            this.dataset.dragBound = '1';
            console.log('[DRAG-DROP] Binding drag handlers to dropzone...');

            let dragCount = 0;

            // Drag enter - increment counter and show feedback
            this.addEventListener('dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dragCount += 1;
                if (e.dataTransfer) {
                    e.dataTransfer.dropEffect = 'copy';
                }
                this.classList.add('drag_over');
            });

            // Drag over - maintain feedback
            this.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (e.dataTransfer) {
                    e.dataTransfer.dropEffect = 'copy';
                }
                this.classList.add('drag_over');
            });

            // Drag leave - decrement counter and remove feedback only when count = 0
            this.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dragCount = Math.max(dragCount - 1, 0);
                if (dragCount === 0) {
                    this.classList.remove('drag_over');
                }
            });

            // Drop - process files
            this.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dragCount = 0;
                this.classList.remove('drag_over');
                
                console.log('[DRAG-DROP] Drop event fired');
                const files = e.dataTransfer && e.dataTransfer.files;
                if (files && files.length > 0) {
                    console.log('[DRAG-DROP] Files detected:', files.length, 'file(s)');
                    handleBulkFilesSelected(files);
                } else {
                    console.warn('[DRAG-DROP] No files detected in drop event');
                }
            });
            
            console.log('[DRAG-DROP] ✅ Drag and drop handlers bound successfully');
        });
    }

    // Initialize immediately on page load
    bindDropzoneDragHandlers();
    
    // Re-initialize when bulk upload button is clicked (when dropzone becomes visible)
    $(document).on('click', '.bulk-upload-toggle-btn', function() {
        const dropzoneContainer = $('.bulk-upload-dropzone-container');
        
        if (!dropzoneContainer.is(':visible')) {
            // Dropzone will become visible after slideDown
            console.log('[DRAG-DROP] Dropzone will become visible, binding handlers...');
            setTimeout(bindDropzoneDragHandlers, 150);
        }
    });
});

// Handle files selected
function handleBulkFilesSelected(files) {
    bulkUploadFiles = [];
    
    const invalidFiles = [];
    const maxSize = 50 * 1024 * 1024; // 50MB
    const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    
    Array.from(files).forEach(file => {
        if (file.size > maxSize) {
            invalidFiles.push(file.name + ' (exceeds 50MB)');
            return;
        }
        
        const ext = file.name.split('.').pop().toLowerCase();
        if (!allowedExtensions.includes(ext)) {
            invalidFiles.push(file.name + ' (invalid file type)');
            return;
        }
        
        bulkUploadFiles.push(file);
    });
    
    if (invalidFiles.length > 0) {
        toastMsg('The following files were skipped:\n' + invalidFiles.join('\n'), 'warning');
    }
    
    if (bulkUploadFiles.length === 0) {
        toastMsg('No valid files selected. Please select PDF, JPG, PNG, DOC, or DOCX files under 50MB.', 'warning');
        return;
    }
    
    // Show file list
    $('.bulk-upload-file-list').show();
    $('.file-count').text(bulkUploadFiles.length);
    
    // Show mapping interface
    showBulkUploadMapping();
}

// Show mapping interface
function showBulkUploadMapping() {
    if (bulkUploadFiles.length === 0) return;
    
    // Get existing checklists
    getExistingChecklists(function(checklists) {
        // Call backend to get auto-matches
        getAutoChecklistMatches(bulkUploadFiles, checklists, function(matches) {
            displayMappingInterface(bulkUploadFiles, checklists, matches);
        });
    });
}

// Get existing checklists from database
function getExistingChecklists(callback) {
    var url = App.getUrl('documentsAutoChecklistMatches');
    $.ajax({
        url: url,
        method: 'POST',
        data: {
            _token: App.getCsrf(),
            clientid: currentClientId,
            files: [] // Empty array just to get checklists
        },
        success: function(response) {
            if (response.status && response.checklists) {
                const checklists = response.checklists.map(name => ({ name: name }));
                callback(checklists);
            } else {
                // Fallback: get from table
                const checklists = [];
                const checklistNames = new Set();
                
                $('.alldocumnetlist tr').each(function() {
                    const checklistName = $(this).data('checklist-name');
                    if (checklistName && !checklistNames.has(checklistName)) {
                        checklistNames.add(checklistName);
                        checklists.push({ name: checklistName });
                    }
                });
                
                callback(checklists);
            }
        },
        error: function() {
            // Fallback: get from table
            const checklists = [];
            const checklistNames = new Set();
            
            $('.alldocumnetlist tr').each(function() {
                const checklistName = $(this).data('checklist-name');
                if (checklistName && !checklistNames.has(checklistName)) {
                    checklistNames.add(checklistName);
                    checklists.push({ name: checklistName });
                }
            });
            
            callback(checklists);
        }
    });
}

// Get auto-checklist matches from backend
function getAutoChecklistMatches(files, checklists, callback) {
    const fileData = Array.from(files).map(file => ({
        name: file.name,
        size: file.size,
        type: file.type
    }));
    
    const checklistNames = checklists.map(c => c.name);
    var url = App.getUrl('documentsAutoChecklistMatches');
    
    $.ajax({
        url: url,
        method: 'POST',
        data: {
            _token: App.getCsrf(),
            clientid: currentClientId,
            files: fileData,
            checklists: checklistNames
        },
        success: function(response) {
            if (response.status) {
                callback(response.matches || {});
            } else {
                callback({});
            }
        },
        error: function() {
            callback({});
        }
    });
}

// Display mapping interface
function displayMappingInterface(files, checklists, matches) {
    const modal = $('#bulk-upload-mapping-modal');
    const tableContainer = $('#bulk-upload-mapping-table');
    
    let html = '<div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">';
    html += '<table class="table table-bordered bulk-upload-table" style="width: 100%; min-width: 600px; margin-bottom: 0;">';
    html += '<thead><tr><th>File Name</th><th>Checklist Assignment</th><th>Status</th><th>Action</th></tr></thead>';
    html += '<tbody>';
    
    Array.from(files).forEach((file, index) => {
        const fileName = file.name;
        const fileSize = formatFileSize(file.size);
        const match = matches[fileName] || null;
        
        let selectedChecklist = '';
        let statusClass = 'manual';
        let statusText = 'Manual selection';
        
        if (match && match.checklist) {
            selectedChecklist = match.checklist;
            statusClass = match.confidence === 'high' ? 'auto-matched' : 'manual';
            statusText = match.confidence === 'high' ? 'Auto-matched' : 'Suggested';
        }
        
        html += '<tr class="bulk-upload-file-item">';
        html += '<td><div class="file-info">' + crmIcon('file', 'solid', { attrs: { style: 'color: #4a90e2; flex-shrink: 0;' } }) + '<div style="min-width: 0; flex: 1;"><div class="file-name">' + escapeHtml(fileName) + '</div><div class="file-size">' + fileSize + '</div></div></div></td>';
        html += '<td>';
        html += '<select class="form-control checklist-select" data-file-index="' + index + '">';
        html += '<option value="">-- Select Checklist --</option>';
        html += '<option value="__NEW__">+ Create New Checklist</option>';
        checklists.forEach(checklist => {
            const selected = selectedChecklist === checklist.name ? 'selected' : '';
            html += '<option value="' + escapeHtml(checklist.name) + '" ' + selected + '>' + escapeHtml(checklist.name) + '</option>';
        });
        html += '</select>';
        html += '<input type="text" class="form-control mt-2 new-checklist-input" data-file-index="' + index + '" placeholder="Enter new checklist name" style="display: none;">';
        html += '</td>';
        html += '<td><span class="match-status ' + statusClass + '">' + statusText + '</span></td>';
        html += '<td><button type="button" class="btn btn-sm btn-outline-danger bulk-upload-remove-file" data-file-index="' + index + '">Remove</button></td>';
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    html += '</div>';
    tableContainer.html(html);
    modal.css('display', 'flex').show();
}

// Handle new checklist option
$(document).on('change', '.checklist-select', function() {
    const fileIndex = $(this).data('file-index');
    const value = $(this).val();
    const newInput = $('.new-checklist-input[data-file-index="' + fileIndex + '"]');
    
    if (value === '__NEW__') {
        newInput.show();
        $(this).closest('tr').find('.match-status').removeClass('auto-matched manual').addClass('new-checklist').text('New checklist');
    } else {
        newInput.hide();
        if (value) {
            $(this).closest('tr').find('.match-status').removeClass('new-checklist').addClass('manual').text('Manual selection');
        }
    }
});

// Close modal
$(document).on('click', '.close-mapping-modal, #cancel-bulk-upload', function() {
    $('#bulk-upload-mapping-modal').hide();
    $('#bulk-upload-progress').hide();
    $('#confirm-bulk-upload').prop('disabled', false);
});

// Remove a file from bulk upload
$(document).on('click', '.bulk-upload-remove-file', function() {
    const index = parseInt($(this).data('file-index'), 10);
    if (Number.isNaN(index)) {
        return;
    }
    bulkUploadFiles.splice(index, 1);
    $('.file-count').text(bulkUploadFiles.length);
    if (bulkUploadFiles.length === 0) {
        $('#bulk-upload-mapping-modal').hide();
        $('.bulk-upload-file-list').hide();
        return;
    }
    showBulkUploadMapping();
});

// Confirm bulk upload
$(document).on('click', '#confirm-bulk-upload', function() {
    const mappings = [];
    
    // Collect mappings
    bulkUploadFiles.forEach((file, index) => {
        const selectElement = $('.checklist-select[data-file-index="' + index + '"]');
        const checklist = selectElement.val();
        
        let mapping = null;
        
        if (checklist === '__NEW__') {
            const newChecklistName = $('.new-checklist-input[data-file-index="' + index + '"]').val();
            if (newChecklistName) {
                mapping = { type: 'new', name: newChecklistName.trim() };
            }
        } else if (checklist) {
            mapping = { type: 'existing', name: checklist };
        }
        
        mappings.push(mapping);
    });
    
    // Validate all files have mappings
    const unmappedFiles = [];
    mappings.forEach((mapping, index) => {
        if (!mapping || !mapping.name) {
            unmappedFiles.push(bulkUploadFiles[index].name);
        }
    });
    
    if (unmappedFiles.length > 0) {
        toastMsg('Please map all files to checklists:\n' + unmappedFiles.join('\n'), 'warning');
        return;
    }
    
    // Upload files
    uploadBulkFiles(bulkUploadFiles, mappings);
});

// Upload bulk files
function uploadBulkFiles(files, mappings) {
    const formData = new FormData();
    
    files.forEach((file, index) => {
        formData.append('files[]', file);
        formData.append('mappings[]', JSON.stringify(mappings[index]));
    });
    
    formData.append('_token', App.getCsrf());
    formData.append('clientid', currentClientId);
    formData.append('doctype', 'documents');
    formData.append('type', 'client');
    
    // Add category_id if category system is active and a category is selected
    if (typeof window.DocumentCategoryManager !== 'undefined' && 
        window.DocumentCategoryManager && 
        window.DocumentCategoryManager.currentCategoryId) {
        formData.append('category_id', window.DocumentCategoryManager.currentCategoryId);
    }
    
    $('#bulk-upload-progress').show();
    $('#bulk-upload-progress-bar').css('width', '0%').text('0%');
    $('#confirm-bulk-upload').prop('disabled', true);
    
    $.ajax({
        url: App.getUrl('documentsBulkUpload'),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    $('#bulk-upload-progress-bar').css('width', percentComplete + '%').text(Math.round(percentComplete) + '%');
                }
            });
            return xhr;
        },
        success: function(response) {
            $('#bulk-upload-progress').hide();
            $('#confirm-bulk-upload').prop('disabled', false);
            
            if (response.status) {
                let message = response.message || 'Upload completed.';
                if (response.errors && response.errors.length > 0) {
                    message += '\n\nSome files failed:\n' + response.errors.join('\n');
                }
                toastMsg(message, 'success');
                $('#bulk-upload-mapping-modal').hide();
                $('.bulk-upload-dropzone-container').hide();
                $('.bulk-upload-toggle-btn').html(crmIcon('upload') + ' Bulk Upload');
                bulkUploadFiles = [];
                
                // If category system is active, reload current category documents
                if (typeof window.DocumentCategoryManager !== 'undefined' && 
                    window.DocumentCategoryManager && 
                    window.DocumentCategoryManager.currentCategoryId) {
                    // Reload the current category to show new documents
                    window.DocumentCategoryManager.loadCategoryDocuments(window.DocumentCategoryManager.currentCategoryId);
                    window.DocumentCategoryManager.loadCategories(true);
                } else {
                    // Fallback: Reload the page to show new documents
                    location.reload();
                }
            } else {
                let errorMsg = 'Error: ' + (response.message || 'Upload failed.');
                if (response.errors && response.errors.length > 0) {
                    errorMsg += '\n\nDetails:\n' + response.errors.join('\n');
                }
                toastMsg(errorMsg, 'error');
            }
        },
        error: function(xhr) {
            $('#bulk-upload-progress').hide();
            $('#confirm-bulk-upload').prop('disabled', false);
            let errorMsg = 'Upload failed. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            toastMsg(errorMsg, 'error');
        }
    });
}

// Helper functions
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================================================
// CLIENT RECEIPT MODAL HANDLERS
// ============================================================================

// NOTE: The "Create Client Receipt" button click handler has been moved to
// receipts-and-payments.js to avoid duplicate modal opening.
// This handler was causing the modal to open twice (Issue #3).

// Handle "Edit Client Receipt" button click (from the pencil icon)
$(document).on('click', '.updateclientreceipt', function(e) {
    e.preventDefault();
    var receipt_id = $(this).attr('data-id');
    var url = App.getUrl('clientGetReceiptInfo') || App.getUrl('siteUrl') + '/clients/getClientReceiptInfoById';
    
    // Set function_type to 'edit' for updating receipt
    $('#function_type').val('edit');
    
    // Update modal title
    $('#clientReceiptModalLabel').text('Edit Client Receipt');
    
    // Clear any error messages
    $('.custom-error-msg').html('');
    
    // Show loader
    if ($('.popuploader').length) {
        $('.popuploader').show();
    }
    
    // Fetch receipt data from server
    $.ajax({
        url: url,
        type: 'POST',
        data: {
            _token: App.getCsrf(),
            id: receipt_id
        },
        success: function(response) {
            if ($('.popuploader').length) {
                $('.popuploader').hide();
            }
            
            var obj = typeof response === 'string' ? JSON.parse(response) : response;
            
            if (obj.status) {
                // Populate form with fetched data - Backend returns 'record_get', not 'requestData'
                var receiptData = obj.record_get || obj.requestData || [];
                var firstRecord = receiptData[0] || {};

                // Clear existing rows
                $('.productitem').html('');

                $('#create_client_receipt').data('original-application-id', firstRecord.application_id || '');
                $('#reassignment_reason').val(firstRecord.reassignment_reason || '').removeAttr('data-valid');
                $('#reassignment_reason_wrapper').hide();
                if (typeof window.ensureReceiptApplicationOptions === 'function') {
                    window.ensureReceiptApplicationOptions(function () {
                        $('#receipt_application_id').val(firstRecord.application_id || '');
                    });
                } else {
                    $('#receipt_application_id').val(firstRecord.application_id || '');
                }

                $.each(receiptData, function(index, data) {
                    var clonedRow = `
                        <tr class="clonedrow">
                            <td>
                                <input data-valid="required" class="form-control report_date_fields" name="trans_date[]" type="text" value="${data.trans_date}" />
                            </td>
                            <td>
                                <input data-valid="required" class="form-control report_entry_date_fields" name="entry_date[]" type="text" value="${data.entry_date}" />
                            </td>
                            <td>
                                <input class="form-control unique_trans_no" type="text" value="${data.trans_no}" readonly/>
                                <input class="unique_trans_no_hidden" name="trans_no[]" type="hidden" value="${data.trans_no}" />
                                <input name="id[]" type="hidden" value="${data.id}" />
                            </td>
                            <td>
                                <select data-valid="required" class="form-control" name="payment_method[]">
                                    <option value="">Select</option>
                                    <option value="Cash" ${data.payment_method == 'Cash' ? 'selected' : ''}>Cash</option>
                                    <option value="Bank transfer" ${data.payment_method == 'Bank transfer' ? 'selected' : ''}>Bank transfer</option>
                                    <option value="EFTPOS" ${data.payment_method == 'EFTPOS' ? 'selected' : ''}>EFTPOS</option>
                                    <option value="Paid to College" ${data.payment_method == 'Paid to College' ? 'selected' : ''}>Paid to College</option>
                                </select>
                            </td>
                            <td>
                                <input data-valid="required" class="form-control" name="description[]" type="text" value="${data.description}" />
                            </td>
                            <td>
                                <div class="currencyinput">
                                    <span>$</span>
                                    <input data-valid="required" class="form-control deposit_amount_per_row" name="deposit_amount[]" type="text" value="${data.deposit_amount}" />
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <a class="removeitems text-danger" href="javascript:;" title="Remove row">
                                    ${crmIcon('times')}
                                </a>
                            </td>
                        </tr>
                    `;
                    $('.productitem').append(clonedRow);
                });
                
                // Re-initialize flatpickr for date fields
                if (typeof flatpickr !== 'undefined') {
                    $('.report_date_fields, .report_entry_date_fields').each(function() {
                        if (!this._flatpickr) {
                            flatpickr(this, {
                                dateFormat: 'd/m/Y',
                                allowInput: true
                            });
                        }
                    });
                }
                
                // Calculate and display total
                calculateReceiptTotal();
                
                // Open the modal
                $('#createclientreceiptmodal').modal('show');
            } else {
                toastMsg('Error loading receipt data: ' + obj.message, 'error');
            }
        },
        error: function() {
            toastMsg('Error loading receipt data. Please try again.', 'error');
        },
        complete: function() {
            if ($('.popuploader').length) {
                $('.popuploader').hide();
            }
        }
    });
});

// Calculate total deposit amount
function calculateReceiptTotal() {
    var total = 0;
    $('.deposit_amount_per_row').each(function() {
        var amount = parseFloat($(this).val()) || 0;
        total += amount;
    });
    $('.total_deposit_amount_all_rows').html('$' + total.toFixed(2));
}

// Update total when deposit amount changes
$(document).on('keyup change', '.deposit_amount_per_row', function() {
    calculateReceiptTotal();
});

// Add new line functionality (already in modal)
$(document).on('click', '.openproductrinfo', function() {
    var clonedRow = $('.productitem tr.clonedrow:first').clone();
    clonedRow.find('input, select').val('');
    clonedRow.find('.unique_trans_no').val('');
    $('.productitem').append(clonedRow);
    
    // Re-initialize flatpickr for new row
    if (typeof flatpickr !== 'undefined') {
        clonedRow.find('.report_date_fields, .report_entry_date_fields').each(function() {
            flatpickr(this, {
                dateFormat: 'd/m/Y',
                allowInput: true
            });
        });
    }
    
    calculateReceiptTotal();
});

// Remove row functionality
$(document).on('click', '.removeitems', function() {
    if ($('.productitem tr.clonedrow').length > 1) {
        $(this).closest('tr').remove();
        calculateReceiptTotal();
    } else {
        toastMsg('At least one row is required.', 'warning');
    }
});

// Document upload for receipt - Updated with file feedback
$(document).on('click', '.upload-receipt-doc-btn', function() {
    $('.docclientreceiptupload').trigger('click');
});

// Handle file selection - show selected file name
$(document).on('change', '.docclientreceiptupload', function() {
    var file = this.files[0];
    if (file) {
        var fileName = file.name;
        var fileSize = (file.size / 1024).toFixed(2); // Convert to KB
        
        // Show file info
        $('.file-name-display').text(fileName + ' (' + fileSize + ' KB)');
        $('.selected-file-info').slideDown();
        
        // Change button text to indicate file is attached
        $('.upload-receipt-doc-btn').html(crmIcon('check') + ' Document Attached');
        $('.upload-receipt-doc-btn').removeClass('btn-outline-primary').addClass('btn-success');
    }
});

// Handle file removal
$(document).on('click', '.remove-selected-file', function() {
    // Clear the file input
    $('.docclientreceiptupload').val('');
    
    // Hide file info
    $('.selected-file-info').slideUp();
    
    // Reset button
    $('.upload-receipt-doc-btn').html(crmIcon('plus') + ' Add Document');
    $('.upload-receipt-doc-btn').removeClass('btn-success').addClass('btn-outline-primary');
});

console.log('[blade-inline.js] Blade-specific handlers initialized');
