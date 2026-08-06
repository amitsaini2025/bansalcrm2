/**
 * Admin Partner Detail - Status Handlers Module
 * 
 * Handles student status and application overall status updates
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
        console.log('[status-handlers.js] Waiting for vendorLibsReady promise...');
        await window.vendorLibsReady;
        console.log('[status-handlers.js] Vendor libraries ready!');
    } else {
        // Fallback: Poll for vendor libraries
        console.log('[status-handlers.js] vendorLibsReady not found, polling for libraries...');
        await new Promise((resolve) => {
            const check = () => {
                if (typeof $ !== 'undefined') {
                    console.log('[status-handlers.js] All vendor libraries detected!');
                    resolve();
                } else {
                    setTimeout(check, 50);
                }
            };
            check();
        });
    }

// ============================================================================
// STUDENT STATUS CHANGE HANDLERS
// ============================================================================

    function showChangeStatusMessage(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const html = '<span class="alert ' + alertClass + '">' + message + '</span>';
        $('#changeStatusFormMessage').html(html);
        $('.custom-error-msg').html(html);
    }

    function populateChangeStatusModal(button) {
        if (!button) {
            return;
        }

        const studentId = button.getAttribute('data-id');
        const currentStatus = button.getAttribute('data-current-status');
        const studentIdEl = document.getElementById('studentId');
        const newStatusEl = document.getElementById('newStatus');

        if (studentIdEl) {
            studentIdEl.value = studentId || '';
        }

        if (newStatusEl) {
            const statusVal = (currentStatus !== null && currentStatus !== '') ? String(currentStatus) : '0';
            newStatusEl.value = statusVal;
            if (newStatusEl.value !== statusVal) {
                newStatusEl.value = '0';
            }
        }

        $('#changeStatusFormMessage').empty();
    }

    function updateStatusInTable(tableSelector, studentId, newStatus, newStatusId) {
        if (!$(tableSelector).length || !$.fn.DataTable.isDataTable(tableSelector)) {
            return false;
        }

        const table = $(tableSelector).DataTable();
        const rowIndex = table.rows().eq(0).filter(function (rowIdx) {
            return table.cell(rowIdx, 23).data() == studentId;
        });

        if (rowIndex.length > 0) {
            table.cell(rowIndex[0], 21).data(newStatus).draw(false);
            $(tableSelector).find('.change-status-btn[data-id="' + studentId + '"]').attr('data-current-status', newStatusId);
            return true;
        }

        return false;
    }

    function reloadStudentTableIfPresent(tableSelector) {
        if (!$(tableSelector).length || !$.fn.DataTable.isDataTable(tableSelector)) {
            return;
        }
        $(tableSelector).DataTable().ajax.reload(null, false);
    }

    function refreshStudentStatusInTables(studentId, newStatus, newStatusId) {
        if (updateStatusInTable('.table-3', studentId, newStatus, newStatusId)) {
            return;
        }
        if (updateStatusInTable('.table-31', studentId, newStatus, newStatusId)) {
            return;
        }
        reloadStudentTableIfPresent('.table-3');
        reloadStudentTableIfPresent('.table-31');
    }

    jQuery(function () {
        // --------------------------------------------------------------------
        // CHANGE STUDENT STATUS (applications.status) — Active/Inactive tabs
        // --------------------------------------------------------------------
        const changeStatusForm = document.getElementById('changeStatusForm');
        if (changeStatusForm) {
            // Use delegated handlers because DataTables redraws action buttons dynamically.
            document.addEventListener('click', function (event) {
                const button = event.target.closest('.change-status-btn');
                if (button) {
                    populateChangeStatusModal(button);
                }
            });

            const changeStatusModal = document.getElementById('changeStatusModal');
            if (changeStatusModal) {
                changeStatusModal.addEventListener('show.bs.modal', function (event) {
                    const trigger = event.relatedTarget;
                    if (trigger && trigger.classList.contains('change-status-btn')) {
                        populateChangeStatusModal(trigger);
                    }
                });
            }

            changeStatusForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const studentIdEl = document.getElementById('studentId');
                if (!studentIdEl || !studentIdEl.value) {
                    showChangeStatusMessage('Unable to identify the student record. Please close the modal and try again.', 'error');
                    return;
                }

                const formData = new FormData(this);
                const submitUrl = App.getUrl('partnersUpdateStudentStatus');
                if (!submitUrl) {
                    showChangeStatusMessage('Configuration error. Please refresh the page and try again.', 'error');
                    return;
                }

                fetch(submitUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': App.getCsrf()
                    }
                })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    }).catch(function () {
                        return { ok: false, data: null };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.data) {
                        showChangeStatusMessage('Unable to save student status. Please refresh the page and try again.', 'error');
                        return;
                    }

                    const data = result.data;
                    if (data.status) {
                        $('#changeStatusModal').modal('hide');

                        refreshStudentStatusInTables(data.studentId, data.newStatus, data.newStatus_id);
                        showChangeStatusMessage(data.message, 'success');
                    } else {
                        showChangeStatusMessage(data.message || 'Failed to update student status.', 'error');
                    }
                })
                .catch(function (error) {
                    console.error('Error:', error);
                    showChangeStatusMessage('Unable to save student status. Please try again.', 'error');
                });
            });
        }

        // --------------------------------------------------------------------
        // APPLICATION OVERALL STATUS (applications.overall_status)
        // Active tab ↔ Inactive tab — same pattern as Change Status (click + show.bs.modal)
        // --------------------------------------------------------------------
        function populateApplicationOverallStatusModal(button) {
            if (!button) {
                return;
            }

            const applicationStudentIdEl = document.getElementById('applicationStudentId');
            const applicationOverallStatusEl = document.getElementById('applicationOverallStatus');
            if (!applicationStudentIdEl || !applicationOverallStatusEl) {
                return;
            }

            applicationStudentIdEl.value = button.getAttribute('data-id') || '';
            // Preserve "0"; only fall back to empty when attribute is missing/null.
            const overallAttr = button.getAttribute('data-application-overall-status');
            applicationOverallStatusEl.value = (overallAttr !== null && overallAttr !== undefined) ? overallAttr : '';
        }

        document.addEventListener('click', function (event) {
            const button = event.target.closest('.change-application-overall-status-btn');
            if (button) {
                populateApplicationOverallStatusModal(button);
            }
        });

        const changeApplicationOverallStatusModal = document.getElementById('changeApplicationOverallStatusModal');
        if (changeApplicationOverallStatusModal) {
            changeApplicationOverallStatusModal.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                if (trigger && trigger.classList.contains('change-application-overall-status-btn')) {
                    populateApplicationOverallStatusModal(trigger);
                }
            });
        }

        const changeApplicationOverallStatusForm = document.getElementById('changeApplicationOverallStatusForm');
        if (changeApplicationOverallStatusForm) {
            changeApplicationOverallStatusForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const applicationStudentIdEl = document.getElementById('applicationStudentId');
                if (!applicationStudentIdEl || !applicationStudentIdEl.value) {
                    alert('Unable to identify the student application. Please close the modal and try again.');
                    return;
                }

                const submitUrl = App.getUrl('partnersUpdateStudentApplicationStatus');
                if (!submitUrl) {
                    alert('Configuration error. Please refresh the page and try again.');
                    return;
                }

                const formData = new FormData(this);

                fetch(submitUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': App.getCsrf()
                    }
                })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    }).catch(function () {
                        return { ok: false, data: null };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.data) {
                        alert('Unable to update application status. Please refresh the page and try again.');
                        return;
                    }
                    alert(result.data.message || (result.data.status ? 'Updated successfully.' : 'Update failed.'));
                    // Only reload when overall_status actually changed (Active ↔ Inactive).
                    if (result.data.status) {
                        location.reload();
                    }
                })
                .catch(function (error) {
                    console.error('Error:', error);
                    alert('Unable to update application status. Please try again.');
                });
            });
        }
    });

})(); // End async wrapper
