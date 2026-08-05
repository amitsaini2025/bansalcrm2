/**
 * Admin Partner Detail - Bulk Upload Handlers
 *
 * Handles checklist creation, individual uploads, and bulk uploads.
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
    if (typeof window.vendorLibsReady !== 'undefined') {
        console.log('[bulk-upload.js] Waiting for vendorLibsReady promise...');
        await window.vendorLibsReady;
        console.log('[bulk-upload.js] Vendor libraries ready!');
    } else {
        console.log('[bulk-upload.js] vendorLibsReady not found, polling for libraries...');
        await new Promise((resolve) => {
            const check = () => {
                if (typeof $ !== 'undefined') {
                    console.log('[bulk-upload.js] All vendor libraries detected!');
                    resolve();
                } else {
                    setTimeout(check, 50);
                }
            };
            check();
        });
    }

// ============================================================================
// BULK UPLOAD AND CHECKLIST FUNCTIONALITY
// ============================================================================

jQuery(document).ready(function($){
    if (window.partnerBulkUploadInitialized) {
        console.warn('[bulk-upload.js] Already initialized, skipping duplicate registration');
        return;
    }
    window.partnerBulkUploadInitialized = true;

    let bulkUploadFilesPartner = [];
    let currentPartnerId = PageConfig.partnerId;

    // Add Checklist handler
    $(document).on('click', '.add_alldocument_doc', function() {
        var checklistName = prompt('Enter checklist name:');
        if (checklistName && checklistName.trim() !== '') {
            $.ajax({
                url: App.getUrl('partnersAddAllDocChecklist'),
                method: 'POST',
                data: {
                    _token: App.getCsrf(),
                    clientid: currentPartnerId,
                    checklist: checklistName.trim(),
                    type: 'partner',
                    doctype: 'documents'
                },
                success: function(response) {
                    var obj = JSON.parse(response);
                    if (obj.status) {
                        location.reload();
                    } else {
                        alert(obj.message || 'Error adding checklist');
                    }
                },
                error: function() {
                    alert('Error adding checklist. Please try again.');
                }
            });
        }
    });

    // ============================================================================
    // INDIVIDUAL DOCUMENT UPLOAD FOR CHECKLIST ROWS
    // ============================================================================

    $(document).on('click', '.allupload_document .btn-primary', function(e) {
        e.preventDefault();
        $(this).closest('form').find('.alldocupload').click();
    });

    $(document).on('click', '.alldocupload', function() {
        $(this).attr("value", "");
    });

    $(document).on('change', '.alldocupload', function() {
        $('.popuploader').show();
        const $input = $(this);
        if ($input.data('uploading')) {
            return;
        }
        const debugId = Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);
        console.log('[bulk-upload.js] Upload change fired', {
            debugId,
            inputFileId: $input.attr('data-fileid'),
            inputName: $input.attr('name'),
            fileCount: ($input[0] && $input[0].files) ? $input[0].files.length : 0
        });
        const form = $input.closest('form')[0];
        if (!form) {
            console.warn('[bulk-upload.js] Upload form not found for input.');
            $('.popuploader').hide();
            return;
        }
        $input.data('uploading', true);

        const formData = new FormData(form);
        if (!formData.has('fileid')) {
            const fileidL = $input.attr('data-fileid');
            if (fileidL) {
                formData.append('fileid', fileidL);
            }
        }
        formData.append('debug_id', debugId);
        console.log('[bulk-upload.js] Upload payload', {
            debugId,
            formId: $(form).attr('id'),
            formFileId: $(form).find('input[name="fileid"]').val(),
            formChecklist: $(form).find('input[name="checklist"]').val(),
            hasFileIdInFormData: formData.has('fileid')
        });

        $.ajax({
            url: App.getUrl('partnersUploadAllDocument'),
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': App.getCsrf() },
            datatype: 'json',
            data: formData,
            contentType: false,
            processData: false,
            success: function(responses) {
                $('.popuploader').hide();
                $input.data('uploading', false);
                var ress = typeof responses === 'string' ? JSON.parse(responses) : responses;
                console.log('[bulk-upload.js] Upload response', { debugId, status: ress.status, message: ress.message });
                if (ress.status) {
                    $('.custom-error-msg').html('<span class="alert alert-success">' + ress.message + '</span>');
                    location.reload();
                } else {
                    $('.custom-error-msg').html('<span class="alert alert-danger">' + ress.message + '</span>');
                }
            },
            error: function() {
                $('.popuploader').hide();
                $input.data('uploading', false);
                console.warn('[bulk-upload.js] Upload error', { debugId });
                $('.custom-error-msg').html('<span class="alert alert-danger">Error uploading document. Please try again.</span>');
            }
        });
    });

    // ============================================================================
    // BULK UPLOAD UI HANDLERS
    // ============================================================================

    $(document).on('click', '.bulk-upload-toggle-btn', function() {
        const dropzoneContainer = $(this).closest('.card-header-action').next('.bulk-upload-dropzone-container');

        if (dropzoneContainer.length && dropzoneContainer.is(':visible')) {
            dropzoneContainer.slideUp();
            $(this).html(crmIcon('upload') + ' Bulk Upload');
            bulkUploadFilesPartner = [];
            dropzoneContainer.find('.bulk-upload-file-list').hide();
            dropzoneContainer.find('.file-count').text('0');
        } else {
            dropzoneContainer.slideDown();
            bindDropzoneDragHandlers();
            $(this).html(crmIcon('times') + ' Close');
        }
    });

    // Click to browse — only empty dropzone area (not interactive children)
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

    $(document).on('change', '.bulk-upload-file-input', function() {
        const files = this.files;
        if (files.length > 0) {
            handleBulkFilesSelectedPartner(files);
        }
    });

    // Allow file drag events on the page so drop works reliably
    // Bind on window/body to ensure drag events are accepted on Windows browsers
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

    function bindDropzoneDragHandlers() {
        $('.bulk-upload-dropzone').each(function() {
            if (this.dataset.dragBound === '1') {
                return;
            }
            this.dataset.dragBound = '1';

            let dragCount = 0;

            this.addEventListener('dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dragCount += 1;
                if (e.dataTransfer) {
                    e.dataTransfer.dropEffect = 'copy';
                }
                this.classList.add('drag_over');
            });

            this.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (e.dataTransfer) {
                    e.dataTransfer.dropEffect = 'copy';
                }
                this.classList.add('drag_over');
            });

            this.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dragCount = Math.max(dragCount - 1, 0);
                if (dragCount === 0) {
                    this.classList.remove('drag_over');
                }
            });

            this.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dragCount = 0;
                this.classList.remove('drag_over');
                const files = e.dataTransfer && e.dataTransfer.files;
                if (files && files.length > 0) {
                    handleBulkFilesSelectedPartner(files);
                }
            });
        });
    }

    bindDropzoneDragHandlers();

    // Prevent browser from opening file on drop outside the dropzone
    $(document).on('dragover drop', function(e) {
        if ($('.bulk-upload-dropzone:visible').length) {
            e.preventDefault();
        }
    });

    function handleBulkFilesSelectedPartner(files) {
        bulkUploadFilesPartner = [];

        const invalidFiles = [];
        const maxSize = 50 * 1024 * 1024;
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

            bulkUploadFilesPartner.push(file);
        });

        if (invalidFiles.length > 0) {
            alert('The following files were skipped:\n' + invalidFiles.join('\n'));
        }

        if (bulkUploadFilesPartner.length === 0) {
            alert('No valid files selected. Please select PDF, JPG, PNG, DOC, or DOCX files under 50MB.');
            return;
        }

        $('.bulk-upload-file-list').show();
        $('.file-count').text(bulkUploadFilesPartner.length);

        showBulkUploadMappingPartner();
    }

    function showBulkUploadMappingPartner() {
        if (bulkUploadFilesPartner.length === 0) return;

        getExistingChecklistsPartner(function(checklists) {
            displayMappingInterfacePartner(bulkUploadFilesPartner, checklists);
        });
    }

    function getExistingChecklistsPartner(callback) {
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

    function displayMappingInterfacePartner(files, checklists) {
        const modal = $('#bulk-upload-mapping-modal-partner');
        const tableContainer = $('#bulk-upload-mapping-table-partner');

        let html = '<div class="table-responsive" style="overflow-x: auto;">';
        html += '<table class="table table-bordered bulk-upload-table" style="width: 100%; min-width: 600px; margin-bottom: 0;">';
        html += '<thead><tr><th>File Name</th><th>Checklist Assignment</th><th>Status</th><th>Action</th></tr></thead>';
        html += '<tbody>';

        Array.from(files).forEach((file, index) => {
            const fileName = file.name;
            const fileSize = formatFileSizePartner(file.size);

            html += '<tr class="bulk-upload-file-item">';
            html += '<td><div class="file-info">' + crmIcon('file', 'solid', { attrs: { style: 'color: #4a90e2; flex-shrink: 0;' } }) + '<div style="min-width: 0; flex: 1;"><div class="file-name">' + escapeHtmlPartner(fileName) + '</div><div class="file-size">' + fileSize + '</div></div></div></td>';
            html += '<td>';
            html += '<select class="form-control checklist-select" data-file-index="' + index + '">';
            html += '<option value="">-- Select Checklist --</option>';
            html += '<option value="__NEW__">+ Create New Checklist</option>';
            checklists.forEach(checklist => {
                html += '<option value="' + escapeHtmlPartner(checklist.name) + '">' + escapeHtmlPartner(checklist.name) + '</option>';
            });
            html += '</select>';
            html += '<input type="text" class="form-control mt-2 new-checklist-input" data-file-index="' + index + '" placeholder="Enter new checklist name" style="display: none;">';
            html += '</td>';
            html += '<td><span class="match-status manual">Manual selection</span></td>';
            html += '<td><button type="button" class="btn btn-sm btn-outline-danger bulk-upload-remove-file" data-file-index="' + index + '">Remove</button></td>';
            html += '</tr>';
        });

        html += '</tbody></table>';
        html += '</div>';
        tableContainer.html(html);
        modal.show();
    }

    $(document).on('change', '#bulk-upload-mapping-modal-partner .checklist-select', function() {
        const fileIndex = $(this).data('file-index');
        const value = $(this).val();
        const newInput = $('#bulk-upload-mapping-modal-partner .new-checklist-input[data-file-index="' + fileIndex + '"]');

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

    $(document).on('click', '#bulk-upload-mapping-modal-partner .close-mapping-modal, #cancel-bulk-upload-partner', function() {
        $('#bulk-upload-mapping-modal-partner').hide();
        $('#bulk-upload-progress-partner').hide();
        $('#confirm-bulk-upload-partner').prop('disabled', false);
    });

    $(document).on('click', '#bulk-upload-mapping-modal-partner .bulk-upload-remove-file', function() {
        const index = parseInt($(this).data('file-index'), 10);
        if (Number.isNaN(index)) {
            return;
        }
        bulkUploadFilesPartner.splice(index, 1);
        $('.file-count').text(bulkUploadFilesPartner.length);
        if (bulkUploadFilesPartner.length === 0) {
            $('#bulk-upload-mapping-modal-partner').hide();
            $('.bulk-upload-file-list').hide();
            return;
        }
        showBulkUploadMappingPartner();
    });

    $(document).on('click', '#confirm-bulk-upload-partner', function() {
        const mappings = [];

        bulkUploadFilesPartner.forEach((file, index) => {
            const selectElement = $('#bulk-upload-mapping-modal-partner .checklist-select[data-file-index="' + index + '"]');
            const checklist = selectElement.val();

            let mapping = null;

            if (checklist === '__NEW__') {
                const newChecklistName = $('#bulk-upload-mapping-modal-partner .new-checklist-input[data-file-index="' + index + '"]').val();
                if (newChecklistName) {
                    mapping = { type: 'new', name: newChecklistName.trim() };
                }
            } else if (checklist) {
                mapping = { type: 'existing', name: checklist };
            }

            mappings.push(mapping);
        });

        const unmappedFiles = [];
        mappings.forEach((mapping, index) => {
            if (!mapping || !mapping.name) {
                unmappedFiles.push(bulkUploadFilesPartner[index].name);
            }
        });

        if (unmappedFiles.length > 0) {
            alert('Please map all files to checklists:\n' + unmappedFiles.join('\n'));
            return;
        }

        uploadBulkFilesPartner(bulkUploadFilesPartner, mappings);
    });

    function uploadBulkFilesPartner(files, mappings) {
        $('#bulk-upload-progress-partner').show();
        $('#bulk-upload-progress-bar-partner').css('width', '0%').text('0%');
        $('#confirm-bulk-upload-partner').prop('disabled', true);

        let uploadedCount = 0;
        let failedFiles = [];

        function uploadNext(index) {
            if (index >= files.length) {
                $('#bulk-upload-progress-partner').hide();
                $('#confirm-bulk-upload-partner').prop('disabled', false);

                let message = 'Upload completed: ' + uploadedCount + ' file(s) uploaded.';
                if (failedFiles.length > 0) {
                    message += '\n\nFailed files:\n' + failedFiles.join('\n');
                }
                alert(message);
                $('#bulk-upload-mapping-modal-partner').hide();
                $('.bulk-upload-dropzone-container').hide();
                $('.bulk-upload-toggle-btn').html(crmIcon('upload') + ' Bulk Upload');
                bulkUploadFilesPartner = [];

                location.reload();
                return;
            }

            const file = files[index];
            const mapping = mappings[index];
            const formData = new FormData();

            formData.append('_token', App.getCsrf());
            formData.append('clientid', currentPartnerId);
            formData.append('type', 'partner');
            formData.append('doctype', 'documents');
            formData.append('document_upload', file);
            formData.append('checklist', mapping.name);
            formData.append('checklist_type', mapping.type);

            $.ajax({
                url: App.getUrl('partnersUploadAllDocument'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function() {
                    uploadedCount++;
                    const percentComplete = ((uploadedCount / files.length) * 100);
                    $('#bulk-upload-progress-bar-partner').css('width', percentComplete + '%').text(Math.round(percentComplete) + '%');
                    uploadNext(index + 1);
                },
                error: function() {
                    failedFiles.push(file.name);
                    const percentComplete = ((uploadedCount / files.length) * 100);
                    $('#bulk-upload-progress-bar-partner').css('width', percentComplete + '%').text(Math.round(percentComplete) + '%');
                    uploadNext(index + 1);
                }
            });
        }

        uploadNext(0);
    }

    function formatFileSizePartner(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    function escapeHtmlPartner(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});

})(); // End async wrapper
