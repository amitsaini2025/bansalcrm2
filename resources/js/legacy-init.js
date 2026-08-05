/**
 * Legacy Initialization Script
 * 
 * This file contains all the inline initialization code that was previously
 * in admin.blade.php. It waits for vendor libraries to be ready before executing.
 */

// Wait for vendor libraries to be ready, then initialize all components
(async function() {
    async function waitForInitTomSelectHelpers(maxAttempts) {
        var limit = maxAttempts || 200;
        for (var attempt = 0; attempt < limit; attempt++) {
            if (typeof window.initTomSelect === 'function') {
                return;
            }
            await new Promise(function (resolve) { setTimeout(resolve, 50); });
        }
        console.warn('[legacy-init] initTomSelect helpers not loaded after timeout');
    }

    // Wait for vendorLibsReady promise (created in vendor-libs.js)
    if (typeof window.vendorLibsReady !== 'undefined') {
        await window.vendorLibsReady;
        console.log('Vendor libraries ready, initializing components...');
    } else {
        // Fallback: wait for event or check periodically
        await new Promise((resolve) => {
            const check = () => {
                if (typeof window.$ !== 'undefined' &&
                    typeof window.TomSelect !== 'undefined' &&
                    typeof window.initTomSelect === 'function') {
                    resolve();
                } else {
                    setTimeout(check, 50);
                }
            };
            check();
        });
    }

    // vendorLibsReady resolves when TomSelect lib loads; helpers come from tomselect-init.js (defer)
    await waitForInitTomSelectHelpers();

    function parseAjaxJson(response) {
        if (response == null || response === '') {
            throw new Error('Empty response');
        }
        if (typeof response === 'object') {
            return response;
        }
        return JSON.parse(response);
    }

    // Now initialize all components
    function initializeComponents() {
        $(document).ready(function () {
           
            $(".tel_input").on("blur", function() {
                this.value = this.value;
            });
            
            // Check-in assignee — Tom Select via action-popover-tomselect.js on #checkinmodal shown
         
            // Modern search is now initialized in modern-search.js
            // This provides better performance with debouncing, keyboard shortcuts, and categorization
            

            $(document).delegate('.opencheckin', 'click', function(e){
                e.preventDefault();
                $('#checkinmodal').modal('show');
            });
            
            $(document).delegate('.visitpurpose', 'blur', function(){
                var visitpurpose = $(this).val();
                var appliid = $(this).attr('data-id');
                $('.popuploader').show();
                $.ajax({
                    url: site_url+'/update_visit_purpose',
                    type:'POST',
                    data:{id: appliid,visit_purpose:visitpurpose},
                    success: function(responses){
                         $.ajax({
                            url: site_url+'/get-checkin-detail',
                            type:'GET',
                            data:{id: appliid},
                            success: function(res){
                                 $('.popuploader').hide();
                                $('.showchecindetail').html(res);
                            },
                            error: function(){
                                $('.popuploader').hide();
                                window.toastMsg('Could not refresh check-in details.', 'error');
                            }
                        });
                    },
                    error: function(){
                        $('.popuploader').hide();
                        window.toastMsg('Could not save visit purpose.', 'error');
                    }
                });
            });
            
            $(document).delegate('.savevisitcomment', 'click', function(){
                var visitcomment = $('.visit_comment').val();
                var appliid = $(this).attr('data-id');
                $('.popuploader').show();
                $.ajax({
                    url: site_url+'/update_visit_comment',
                    type:'POST',
                    data:{id: appliid,visit_comment:visitcomment},
                    success: function(responses){
                        // $('.popuploader').hide();
                        $('.visit_comment').val('');
                        $.ajax({
                            url: site_url+'/get-checkin-detail',
                            type:'GET',
                            data:{id: appliid},
                            success: function(res){
                                 $('.popuploader').hide();
                                $('.showchecindetail').html(res);
                            },
                            error: function(){
                                $('.popuploader').hide();
                                window.toastMsg('Could not refresh check-in details.', 'error');
                            }
                        });
                    },
                    error: function(){
                        $('.popuploader').hide();
                        window.toastMsg('Could not save comment.', 'error');
                    }
                });
            });

            $(document).delegate('.attendsession', 'click', function(){
                var appliid = $(this).attr('data-id');
                var waitingtype = $(this).attr('data-waitingtype');
                $('.popuploader').show();
                $.ajax({
                    url: site_url+'/attend_session',
                    type:'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data:{id: appliid, waitcountdata: $('#waitcountdata').val(), waitingtype: waitingtype},
                    success: function(response){
                        try {
                            var obj = parseAjaxJson(response);
                            if(obj.status){
                                // Update office-visits tab badges (Waiting / Attending / Completed) if counts returned
                                if (obj.attending !== undefined && obj.completed !== undefined && obj.waiting !== undefined) {
                                    $('#waiting-tab .countAction').text(obj.waiting);
                                    $('#attending-tab .countAction').text(obj.attending);
                                    $('#completed-tab .countAction').text(obj.completed);
                                }
                                var successMessage = obj.message || 'Attend session successful!';
                                $.ajax({
                                    url: site_url+'/get-checkin-detail',
                                    type:'GET',
                                    data:{id: appliid},
                                    success: function(res){
                                        $('.popuploader').hide();
                                        $('.showchecindetail').html(res);
                                        $('#checkindetailmodal').modal('hide');
                                        window.toastMsg(successMessage, 'success');
                                    },
                                    error: function(){
                                        $('.popuploader').hide();
                                        $('#checkindetailmodal').modal('hide');
                                        window.toastMsg(successMessage, 'success');
                                    }
                                });
                                $('.checindata #id_'+appliid).remove();
                            }else{
                                $('.popuploader').hide();
                                window.toastMsg(obj.message || 'Could not attend session.', 'error');
                            }
                        } catch(e) {
                            $('.popuploader').hide();
                            console.error('Error parsing response:', e);
                            window.toastMsg('Error processing response', 'error');
                        }
                    },
                    error: function(xhr, status, error){
                        $('.popuploader').hide();
                        console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                        window.toastMsg('Failed to attend session. Error: ' + error + ' — Status: ' + xhr.status, 'error');
                    }
                });
            });
            
            $(document).delegate('.completesession', 'click', function(){
                var appliid = $(this).attr('data-id');
                var attendcountdata = $('#attendcountdata').val();
                console.log('Complete Session clicked - ID:', appliid, 'Attend Count:', attendcountdata);
                $('.popuploader').show();
                $.ajax({
                    url: site_url+'/complete_session',
                    type:'POST',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                    data:{id: appliid, attendcountdata: attendcountdata},
                    success: function(response){
                        console.log('Complete Session Response:', response);
                        try {
                            var obj = parseAjaxJson(response);
                            if(obj.status){
                                $('.popuploader').hide();
                                // Close modal and show message immediately so popup always closes after success
                                $('#checkindetailmodal').modal('hide');
                                window.toastMsg(obj.message || 'Session completed successfully!', 'success');
                                // Update office-visits tab badges (Attending / Completed / Waiting) if counts returned
                                if (obj.attending !== undefined && obj.completed !== undefined && obj.waiting !== undefined) {
                                    $('#attending-tab .countAction').text(obj.attending);
                                    $('#completed-tab .countAction').text(obj.completed);
                                    $('#waiting-tab .countAction').text(obj.waiting);
                                }
                                $('.checindata #id_'+appliid).remove();
                                // Optionally refresh modal content in background for next open (no need to wait)
                                $.ajax({
                                    url: site_url+'/get-checkin-detail',
                                    type:'GET',
                                    data:{id: appliid},
                                    success: function(res){
                                        $('.showchecindetail').html(res);
                                    }
                                });
                            }else{
                                $('.popuploader').hide();
                                window.toastMsg(obj.message || 'Could not complete session.', 'error');
                            }
                        } catch(e) {
                            $('.popuploader').hide();
                            console.error('Error parsing response:', e);
                            window.toastMsg('Error processing response', 'error');
                        }
                    },
                    error: function(xhr, status, error){
                        $('.popuploader').hide();
                        console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                        window.toastMsg('Failed to complete session. Error: ' + error + ' — Status: ' + xhr.status, 'error');
                    }
                });
            });
            
            $(document).delegate('.opencheckindetail', 'click', function(){
                var appliid = $(this).attr('id');
                $('#checkindetailmodal').data('currentCheckinId', appliid);
                $('#checkindetailmodal').modal('show');
                $('.popuploader').show();
                $.ajax({
                        url: site_url+'/get-checkin-detail',
                        type:'GET',
                        data:{id: appliid},
                        success: function(responses){
                             $('.popuploader').hide();
                            $('.showchecindetail').html(responses);
                        },
                        error: function(){
                            $('.popuploader').hide();
                            window.toastMsg('Could not load check-in details.', 'error');
                        }
                    });
            });
            
            // intlTelInput plugin no longer used - replaced with country code select dropdowns
            
            // All the table column show/hide logic
            $('.drop_table_data button').on('click', function(){
                $('.client_dropdown_list').toggleClass('active');
            });
            $('.client_dropdown_list label.dropdown-option input').on('click', function(){
                var val = $(this).val();
                if(val == 'all'){ 
                    if ($(this).is(":checked")) {
                        $('.client_table_data table tr td').show();
                        $('.client_table_data table tr th').show();
                        $('.client_dropdown_list label.dropdown-option input').prop('checked', true);
                    } else{
                        $('.client_dropdown_list label.dropdown-option input').prop('checked', false);
                        $('.client_table_data table tr td').hide(); 
                        $('.client_table_data table tr th').hide();
                        $('.client_table_data table tr td:nth-child(1)').show();
                        $('.client_table_data table tr th:nth-child(1)').show();
                        $('.client_table_data table tr td:nth-child(2)').show();
                        $('.client_table_data table tr th:nth-child(2)').show();
                        $('.client_table_data table tr td:nth-child(17)').show();
                        $('.client_table_data table tr th:nth-child(17)').show();
                    }
                } else{
                     if ($(this).is(":checked")) {
                        $('.client_table_data table tr td:nth-child('+val+')').show();
                        $('.client_table_data table tr th:nth-child('+val+')').show();
                      }
                      else{
                        $('.client_dropdown_list label.dropdown-option.all input').prop('checked', false);
                        $('.client_table_data table tr td:nth-child('+val+')').hide();
                        $('.client_table_data table tr th:nth-child('+val+')').hide();
                      }
                }
            });
            
            // Student column visibility (Active/Inactive) is owned by
            // partner-detail/datatable-handlers.js via DataTables column().visible() (P-10).
            // Do not use jQuery nth-child show/hide here — it resets on serverSide redraw.


            // Client report dropdown
            $('.drop_table_data button').on('click', function(){
                $('.client_report_list').toggleClass('active');
            });
            $('.client_report_list label.dropdown-option input').on('click', function(){
                var val = $(this).val();
                if(val == 'all'){
                    if ($(this).is(":checked")) {
                        $('.client_report_data table tr td').show();
                        $('.client_report_data table tr th').show();
                        $('.client_report_list label.dropdown-option input').prop('checked', true);
                    } else{
                        $('.client_report_list label.dropdown-option input').prop('checked', false);
                        $('.client_report_data table tr td').hide(); 
                        $('.client_report_data table tr th').hide();
                        $('.client_report_data table tr td:nth-child(1)').show();
                        $('.client_report_data table tr th:nth-child(1)').show();
                        $('.client_report_data table tr td:nth-child(2)').show();
                        $('.client_report_data table tr th:nth-child(2)').show();
                        $('.client_report_data table tr td:nth-child(11)').show();
                        $('.client_report_data table tr th:nth-child(11)').show();
                    }
                } else{
                     if ($(this).is(":checked")) {
                        $('.client_report_data table tr td:nth-child('+val+')').show();
                        $('.client_report_data table tr th:nth-child('+val+')').show();
                      }
                      else{
                        $('.client_report_list label.dropdown-option.all input').prop('checked', false);
                        $('.client_report_data table tr td:nth-child('+val+')').hide();
                        $('.client_report_data table tr th:nth-child('+val+')').hide();
                      }
                }
            });
            
            // Application report dropdown
            $('.drop_table_data button').on('click', function(){
                $('.application_report_list').toggleClass('active');
            });
            $('.application_report_list label.dropdown-option input').on('click', function(){
                var val = $(this).val();
                if(val == 'all'){
                    if ($(this).is(":checked")) {
                        $('.application_report_data table tr td').show();
                        $('.application_report_data table tr th').show();
                        $('.application_report_list label.dropdown-option input').prop('checked', true);
                    } else{
                        $('.application_report_list label.dropdown-option input').prop('checked', false);
                        $('.application_report_data table tr td').hide(); 
                        $('.application_report_data table tr th').hide();
                        $('.application_report_data table tr td:nth-child(1)').show();
                        $('.application_report_data table tr th:nth-child(1)').show();
                        $('.application_report_data table tr td:nth-child(2)').show();
                        $('.application_report_data table tr th:nth-child(2)').show();
                        $('.application_report_data table tr td:nth-child(3)').show();
                        $('.application_report_data table tr th:nth-child(3)').show();
                        $('.application_report_data table tr td:nth-child(5)').show();
                        $('.application_report_data table tr th:nth-child(5)').show(); 
                        $('.application_report_data table tr td:nth-child(7)').show();
                        $('.application_report_data table tr th:nth-child(7)').show(); 
                    }
                } else{
                     if ($(this).is(":checked")) {
                        $('.application_report_data table tr td:nth-child('+val+')').show();
                        $('.application_report_data table tr th:nth-child('+val+')').show();
                      }
                      else{
                        $('.application_report_list label.dropdown-option.all input').prop('checked', false);
                        $('.application_report_data table tr td:nth-child('+val+')').hide();
                        $('.application_report_data table tr th:nth-child('+val+')').hide();
                      }
                }
            });
            
            // Office visit report dropdown
            $('.drop_table_data button').on('click', function(){
                $('.officevisit_report_list').toggleClass('active');
            });
            $('.officevisit_report_list label.dropdown-option input').on('click', function(){
                var val = $(this).val();
                if(val == 'all'){
                    if ($(this).is(":checked")) {
                        $('.officevisit_report_data table tr td').show();
                        $('.officevisit_report_data table tr th').show();
                        $('.officevisit_report_list label.dropdown-option input').prop('checked', true);
                    } else{
                        $('.officevisit_report_list label.dropdown-option input').prop('checked', false);
                        $('.officevisit_report_data table tr td').hide(); 
                        $('.officevisit_report_data table tr th').hide();
                        $('.officevisit_report_data table tr td:nth-child(1)').show();
                        $('.officevisit_report_data table tr th:nth-child(1)').show();
                        $('.officevisit_report_data table tr td:nth-child(2)').show();
                        $('.officevisit_report_data table tr th:nth-child(2)').show();
                        $('.officevisit_report_data table tr td:nth-child(4)').show();
                        $('.officevisit_report_data table tr th:nth-child(4)').show(); 
                    }
                } else{
                     if ($(this).is(":checked")) {
                        $('.officevisit_report_data table tr td:nth-child('+val+')').show();
                        $('.officevisit_report_data table tr th:nth-child('+val+')').show();
                      }
                      else{
                        $('.officevisit_report_list label.dropdown-option.all input').prop('checked', false);
                        $('.officevisit_report_data table tr td:nth-child('+val+')').hide();
                        $('.officevisit_report_data table tr th:nth-child('+val+')').hide();
                      }
                }
            });
            
            // Invoice report dropdown
            $('.drop_table_data button').on('click', function(){
                $('.invoice_report_list').toggleClass('active');
            });
            $('.invoice_report_list label.dropdown-option input').on('click', function(){
                var val = $(this).val();
                if(val == 'all'){
                    if ($(this).is(":checked")) { 
                        $('.invoice_report_data table tr td').show();
                        $('.invoice_report_data table tr th').show();
                        $('.invoice_report_list label.dropdown-option input').prop('checked', true);
                    } else{
                        $('.invoice_report_list label.dropdown-option input').prop('checked', false);
                        $('.invoice_report_data table tr td').hide(); 
                        $('.invoice_report_data table tr th').hide();
                        $('.invoice_report_data table tr td:nth-child(1)').show();
                        $('.invoice_report_data table tr th:nth-child(1)').show();
                        $('.invoice_report_data table tr td:nth-child(2)').show();
                        $('.invoice_report_data table tr th:nth-child(2)').show();
                        $('.invoice_report_data table tr td:nth-child(4)').show();
                        $('.invoice_report_data table tr th:nth-child(4)').show(); 
                    } 
                } else{
                     if ($(this).is(":checked")) {
                        $('.invoice_report_data table tr td:nth-child('+val+')').show();
                        $('.invoice_report_data table tr th:nth-child('+val+')').show();
                      }
                      else{
                        $('.invoice_report_list label.dropdown-option.all input').prop('checked', false);
                        $('.invoice_report_data table tr td:nth-child('+val+')').hide();
                        $('.invoice_report_data table tr th:nth-child('+val+')').hide();
                      }
                }
            });
            
            // Sales forecast report dropdown
            $('.drop_table_data button').on('click', function(){
                $('.saleforecast_applic_report_list').toggleClass('active');
            });
            $('.saleforecast_applic_report_list label.dropdown-option input').on('click', function(){
                var val = $(this).val();
                if(val == 'all'){
                    if ($(this).is(":checked")) { 
                        $('.saleforecast_application_report_data table tr td').show();
                        $('.saleforecast_application_report_data table tr th').show();
                        $('.saleforecast_applic_report_list label.dropdown-option input').prop('checked', true);
                    } else{
                        $('.saleforecast_applic_report_list label.dropdown-option input').prop('checked', false);
                        $('.saleforecast_application_report_data table tr td').hide(); 
                        $('.saleforecast_application_report_data table tr th').hide();
                        $('.saleforecast_application_report_data table tr td:nth-child(1)').show();
                        $('.saleforecast_application_report_data table tr th:nth-child(1)').show();
                        $('.saleforecast_application_report_data table tr td:nth-child(2)').show();
                        $('.saleforecast_application_report_data table tr th:nth-child(2)').show();
                        $('.saleforecast_application_report_data table tr td:nth-child(4)').show();
                        $('.saleforecast_application_report_data table tr th:nth-child(4)').show(); 
                    } 
                } else{
                     if ($(this).is(":checked")) {
                        $('.saleforecast_application_report_data table tr td:nth-child('+val+')').show();
                        $('.saleforecast_application_report_data table tr th:nth-child('+val+')').show();
                      }
                      else{
                        $('.saleforecast_applic_report_list label.dropdown-option.all input').prop('checked', false);
                        $('.saleforecast_application_report_data table tr td:nth-child('+val+')').hide();
                        $('.saleforecast_application_report_data table tr th:nth-child('+val+')').hide();
                      }
                }
            });
            
            // Interest service report dropdown
            $('.drop_table_data button').on('click', function(){
                $('.interest_service_report_list').toggleClass('active');
            });
            $('.interest_service_report_list label.dropdown-option input').on('click', function(){
                var val = $(this).val();
                if(val == 'all'){
                    if ($(this).is(":checked")) { 
                        $('.interest_service_report_data table tr td').show();
                        $('.interest_service_report_data table tr th').show();
                        $('.interest_service_report_list label.dropdown-option input').prop('checked', true);
                    } else{
                        $('.interest_service_report_list label.dropdown-option input').prop('checked', false);
                        $('.interest_service_report_data table tr td').hide(); 
                        $('.interest_service_report_data table tr th').hide();
                        $('.interest_service_report_data table tr td:nth-child(1)').show();
                        $('.interest_service_report_data table tr th:nth-child(1)').show();
                        $('.interest_service_report_data table tr td:nth-child(2)').show();
                        $('.interest_service_report_data table tr th:nth-child(2)').show();
                        $('.interest_service_report_data table tr td:nth-child(10)').show();
                        $('.interest_service_report_data table tr th:nth-child(10)').show(); 
                        $('.interest_service_report_data table tr td:nth-child(14)').show();
                        $('.interest_service_report_data table tr th:nth-child(14)').show(); 
                    } 
                } else{
                     if ($(this).is(":checked")) {
                        $('.interest_service_report_data table tr td:nth-child('+val+')').show();
                        $('.interest_service_report_data table tr th:nth-child('+val+')').show();
                      }
                      else{
                        $('.interest_service_report_list label.dropdown-option.all input').prop('checked', false);
                        $('.interest_service_report_data table tr td:nth-child('+val+')').hide();
                        $('.interest_service_report_data table tr th:nth-child('+val+')').hide();
                      }
                }
            });
            
            // Form field visibility logic
            $('#personal_details .is_business').hide();
            $('#office_income_share .is_super_agent').hide();
            $('#office_income_share .is_sub_agent').hide();
            $('.modal-body form#addgroupinvoice .is_superagentinv').hide();
            
            $('#agentstructure input[name="struture"]').on('change', function(){
                var id = $(this).attr('id'); 
                if(id == 'individual'){
                    $('#personal_details .is_business').hide();
                    $('#personal_details .is_individual').show();
                    $('#personal_details .is_business input').attr('data-valid', '');
                    $('#personal_details .is_individual input').attr('data-valid', 'required');
                } 
                else{
                    $('#personal_details .is_individual').hide();
                    $('#personal_details .is_business').show(); 
                    $('#personal_details .is_business input').attr('data-valid', 'required');
                    $('#personal_details .is_individual input').attr('data-valid', '');
                }
            });
            
            $('.modal-body form#addgroupinvoice input[name="partner_type"]').on('change', function(){
                var invid = $(this).attr('id'); 
                if(invid == 'superagent_inv'){
                    $('.modal-body form#addgroupinvoice .is_partnerinv').hide();
                    $('.modal-body form#addgroupinvoice .is_superagentinv').show();
                    $('.modal-body form#addgroupinvoice .is_partnerinv input').attr('data-valid', '');
                    $('.modal-body form#addgroupinvoice .is_superagentinv input').attr('data-valid', 'required');
                } 
                else{
                    $('.modal-body form#addgroupinvoice .is_superagentinv').hide();
                    $('.modal-body form#addgroupinvoice .is_partnerinv').show(); 
                    $('.modal-body form#addgroupinvoice .is_partnerinv input').attr('data-valid', 'required');
                    $('.modal-body form#addgroupinvoice .is_superagentinv input').attr('data-valid', '');
                }
            });
            
            $('.modal .modal-body .is_partner').hide();
            $('.modal .modal-body .is_application').hide();
            $('.modal .modal-body input[name="related_to"]').on('change', function(){
                var relid = $(this).attr('id'); 
                if(relid == 'contact'){
                    $('.modal .modal-body .is_partner').hide();
                    $('.modal .modal-body .is_application').hide();
                    $('.modal .modal-body .is_contact').show();
                    $('.modal .modal-body .is_partner select').attr('data-valid', '');
                    $('.modal .modal-body .is_application select').attr('data-valid', '');
                    $('.modal .modal-body .is_contact select').attr('data-valid', 'required');
                } 
                else if(relid == 'partner'){
                    $('.modal .modal-body .is_contact').hide();
                    $('.modal .modal-body .is_application').hide();
                    $('.modal .modal-body .is_partner').show();
                    $('.modal .modal-body .is_contact select').attr('data-valid', '');
                    $('.modal .modal-body .is_application select').attr('data-valid', '');
                    $('.modal .modal-body .is_partner select').attr('data-valid', 'required');
                }
                else if(relid == 'application'){
                    $('.modal .modal-body .is_contact').hide();
                    $('.modal .modal-body .is_partner').hide();
                    $('.modal .modal-body .is_application').show();
                    $('.modal .modal-body .is_contact select').attr('data-valid', '');
                    $('.modal .modal-body .is_partner select').attr('data-valid', '');
                    $('.modal .modal-body .is_application select').attr('data-valid', 'required');
                }
                else{
                    $('.modal .modal-body .is_contact').hide();
                    $('.modal .modal-body .is_partner').hide(); 
                    $('.modal .modal-body .is_application').hide(); 
                    $('.modal .modal-body .is_contact input').attr('data-valid', '');
                    $('.modal .modal-body .is_partner input').attr('data-valid', '');
                    $('.modal .modal-body .is_application input').attr('data-valid', '');
                }
            });
            
            $('#agenttype input#super_agent').on('click', function(){
                if ($(this).is(":checked")) {
                    $('#office_income_share .is_super_agent').show();
                } 
                else{
                    $('#office_income_share .is_super_agent').hide();
                }
            });	
            
            $('#agenttype input#sub_agent').on('click', function(){
                if ($(this).is(":checked")) {
                    $('#office_income_share .is_sub_agent').show();
                } 
                else{
                    $('#office_income_share .is_sub_agent').hide();
                }
            });	 
             
            $('#internal select[name="source"]').on('change', function(){				
                var sourceval = $(this).val(); 
                if(sourceval == 'Sub Agent'){
                    $('#internal .is_subagent').show();
                    $('#internal .is_subagent select').attr('data-valid', 'required');
                } 
                else{
                    $('#internal .is_subagent').hide();
                    $('#internal .is_subagent select').attr('data-valid', '');
                }
            });
            
            // Document list/grid toggle — skip #alldocuments (client detail; handled by DocumentCategoryManager)
            var $legacyDocLayouts = $('.card .card-body .document_layout_type').filter(function() {
                return $(this).closest('#alldocuments').length === 0;
            });

            $('.card .card-body .grid_data').filter(function() {
                return $(this).closest('#alldocuments').length === 0;
            }).hide();

            // Client detail Documents tab grid is managed by DocumentCategoryManager; hide until init
            $('#alldocuments .allgriddata, #alldocuments .grid_data').hide();

            $legacyDocLayouts.find('a.list').on('click', function(e) {
                e.preventDefault();
                var $layout = $(this).closest('.document_layout_type');
                $layout.find('a').removeClass('active');
                $(this).addClass('active');
                var $scope = $layout.closest('.tab-pane');
                if (!$scope.length) {
                    $scope = $layout.closest('.card-body');
                }
                $scope.find('.grid_data').hide();
                $scope.find('.list_data').css('display', 'inline-block');
            });
            $legacyDocLayouts.find('a.grid').on('click', function(e) {
                e.preventDefault();
                var $layout = $(this).closest('.document_layout_type');
                $layout.find('a').removeClass('active');
                $(this).addClass('active');
                var $scope = $layout.closest('.tab-pane');
                if (!$scope.length) {
                    $scope = $layout.closest('.card-body');
                }
                $scope.find('.list_data').hide();
                $scope.find('.grid_data').show();
            });
            
            // Check-in contact search — Tom Select via RecipientSelect (init on modal shown)
            function bindCheckinContactSelectEvents(instance) {
                if (!instance || typeof instance.on !== 'function') {
                    return;
                }
                instance.on('item_add', function (value) {
                    var opt = instance.options[value];
                    if (opt && opt.status) {
                        $('#utype').val(opt.status);
                    }
                });
            }

            function initCheckinContactSelect(options) {
                options = options || {};
                var selector = '.js-data-example-ajax-check';
                var el = document.querySelector(selector);
                if (!el) {
                    return;
                }
                if (typeof RecipientSelect === 'undefined' || typeof RecipientSelect.init !== 'function') {
                    return;
                }
                if (!options.force && typeof isTomSelect === 'function' && isTomSelect(el)) {
                    return;
                }
                var instance = RecipientSelect.init(selector, {
                    url: site_url + '/clients/get-recipients',
                    dropdownParent: '#checkinmodal',
                    multiple: true,
                    closeOnSelect: false,
                    minimumInputLength: 4,
                    force: !!options.force
                });
                bindCheckinContactSelectEvents(instance);
            }

            $(document).on('shown.bs.modal', '#checkinmodal', function () {
                initCheckinContactSelect({ force: true });
            });

            if (typeof waitForRecipientSelect === 'function') {
                waitForRecipientSelect().then(function () {
                    initCheckinContactSelect();
                });
            } else if (typeof whenTomSelectReady === 'function') {
                whenTomSelectReady(function () {
                    initCheckinContactSelect();
                });
            } else if (typeof waitForTomSelect === 'function') {
                waitForTomSelect().then(function () {
                    initCheckinContactSelect();
                });
            }
        }); // End of $(document).ready
    }
    
    // Initialize components
    initializeComponents();
    
    // Notification polling - separate from component initialization
    $(document).ready(function(){
        if (document.getElementById('countbell_notification')) {
            document.getElementById('countbell_notification').parentNode.addEventListener('click', function(event){
                // N-5: respect app base (subdirectory) via site_url
                var base = (typeof site_url !== 'undefined' && site_url) ? site_url : '';
                window.location = base + '/all-notifications';
            });
        }
        
        function load_unseen_notification(view = '') {
            $.ajax({
                url: site_url + "/fetch-notification",
                method:"GET",
                dataType:"json",
                success:function(data) {
                    // Always sync badge (including 0) so it can clear after read/mark-all
                    var count = parseInt(data && data.unseen_notification, 10);
                    if (isNaN(count) || count < 0) {
                        count = 0;
                    }
                    $('.countbell').html(count > 0 ? count : '');
                }
            });
        }
        
        function mark_toast_message_seen(notificationId) {
            if (!notificationId) {
                return;
            }
            $.ajax({
                url: site_url + "/notifications/mark-toast-seen",
                method: "POST",
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: { id: notificationId }
            });
        }

        function load_unseen_messages(view = '') {
            load_unseen_notification();
            var playing = false;
            $.ajax({
                url: site_url + "/fetch-messages",
                method:"GET",
                dataType:"json",
                success:function(data) {
                    // Support legacy plain-string responses and new {id, message} JSON
                    var message = null;
                    var notificationId = null;
                    if (data && typeof data === 'object') {
                        message = data.message || null;
                        notificationId = data.id || null;
                    } else if (data != null && data !== 0 && data !== '0') {
                        message = data;
                    }

                    if (!message) {
                        return;
                    }

                    // N-2: only mark seen after toast can actually be shown
                    if (typeof iziToast !== 'undefined') {
                        iziToast.show({
                            backgroundColor: 'rgba(0,0,255,0.3)',
                            messageColor: 'rgba(255,255,255)',
                            title: '',
                            message: message,
                            position: 'bottomRight'
                        });
                        mark_toast_message_seen(notificationId);
                    }

                    $(this).toggleClass("down");
                    if (playing == false) {
                        var player = document.getElementById('player');
                        if (player) {
                            player.play();
                            playing = true;
                            $(this).text("stop sound");
                        }
                    } else {
                        var player = document.getElementById('player');
                        if (player) {
                            player.pause();
                            playing = false;
                            $(this).text("restart sound");
                        }
                    }
                }
            });
        }
        
        setInterval(function(){
            // Polling functions commented out
        },120000);
    });
})();

