/**
 * Admin Client Detail - Receipts and payments
 */
'use strict';

(function() {
    function fillReceiptApplicationSelectFromHtml(html) {
        var $select = jQuery('#receipt_application_id');
        if (!$select.length) {
            return;
        }
        var current = $select.val();
        var $tbody = jQuery('<table><tbody></tbody></table>').find('tbody');
        $tbody.html(html || '');
        $select.find('option').not(':first').remove();
        $tbody.find('tr[id^="id_"]').each(function () {
            var id = String(this.id || '').replace(/^id_/, '');
            if (!id || $select.find('option[value="' + id + '"]').length) {
                return;
            }
            var $link = jQuery(this).find('a.openapplicationdetail');
            var productName = $link.clone().children().remove().end().text().trim();
            var partnerName = jQuery(this).find('small').first().text().split('(')[0].trim();
            var label = productName || ('Application #' + id);
            if (partnerName) {
                label += ' – ' + partnerName;
            }
            $select.append(jQuery('<option></option>').attr('value', id).text(label));
        });
        $select.attr('data-apps-loaded', '1');
        if (current) {
            $select.val(current);
        }
    }

    window.fillReceiptApplicationSelectFromHtml = fillReceiptApplicationSelectFromHtml;

    jQuery(document).ready(function($){
        // Clear stuck full-page loader so Accounts receipt icons remain clickable
        if ($('.popuploader').length && $('.popuploader').is(':visible')) {
            $('.popuploader').hide();
        }

        function ensureReceiptApplicationOptions(onReady) {
            var $select = $('#receipt_application_id');
            var done = function () {
                if (typeof onReady === 'function') {
                    onReady();
                }
            };
            if (!$select.length || $select.attr('data-apps-loaded') === '1') {
                done();
                return;
            }
            if ($select.find('option').length > 1) {
                $select.attr('data-apps-loaded', '1');
                done();
                return;
            }
            var clientId = (typeof App !== 'undefined' && App.getPageConfig)
                ? App.getPageConfig('clientId')
                : (window.PageConfig && PageConfig.clientId);
            if (!clientId) {
                done();
                return;
            }
            var url = (typeof App !== 'undefined' && App.getUrl)
                ? (App.getUrl('getApplicationLists') || App.getUrl('siteUrl') + '/get-application-lists')
                : '/get-application-lists';
            $.ajax({
                url: url,
                type: 'GET',
                data: { id: clientId },
                success: function (html) {
                    fillReceiptApplicationSelectFromHtml(html);
                    done();
                },
                error: function () {
                    done();
                }
            });
        }

        window.ensureReceiptApplicationOptions = ensureReceiptApplicationOptions;

        // ============================================================================
        // RECEIPT HELPERS
        // ============================================================================
        function getTopReceiptValInDB(type) {
            var url = App.getUrl('clientGetTopReceipt') || App.getUrl('siteUrl') + '/clients/getTopReceiptValInDB';
            $.ajax({
                type:'post',
                url: url,
                sync:true,
                headers: { 'X-CSRF-TOKEN': App.getCsrf()},
                data: {type:type},
                success: function(response){
                    var obj = typeof response === 'string' ? $.parseJSON(response) : response;
                    if(obj.receipt_type == 1){ //client receipt
                        if(obj.record_count >0){
                            $('#top_value_db').val(obj.record_count);
                        } else {
                            $('#top_value_db').val(obj.record_count);
                        }
                    }
                }
            });
        }

        $(document).on('blur', '.deposit_amount_per_row', function(){
            if( $(this).val() != ""){
                var randomNumber = $('#top_value_db').val();
                randomNumber = Number(randomNumber);
                randomNumber = randomNumber + 1;
                $('#top_value_db').val(randomNumber);
                randomNumber = "Rec"+randomNumber;
            }
        });

        function grandtotalAccountTab(){
            var total_deposit_amount_all_rows = 0;
            $('.productitem tr').each(function(){
                if($(this).find('.deposit_amount_per_row').val() != ''){
                    var deposit_amount_per_row = $(this).find('.deposit_amount_per_row').val();
                }else{
                    var deposit_amount_per_row = 0;
                }
                total_deposit_amount_all_rows += parseFloat(deposit_amount_per_row);
            });
            $('.total_deposit_amount_all_rows').html("$"+total_deposit_amount_all_rows.toFixed(2));
        }

        // ============================================================================
        // RECEIPT MODAL HANDLERS
        // ============================================================================
        $(document).on('click', '.createclientreceipt', function(){
            // Reset form for new receipt
            if ($('#create_client_receipt').length) {
                $('#function_type').val('add');
                $('#top_value_db').val('');
                // Update modal title
                $('#clientReceiptModalLabel').text('Create Client Receipt');
                // Reset application dropdown and reassignment reason
                $('#receipt_application_id').val('');
                $('#reassignment_reason').val('').removeAttr('data-valid');
                $('#reassignment_reason_wrapper').hide();
                $('#create_client_receipt').removeData('original-application-id');
                // Clear any existing rows from previous edits
                // Remove only dynamically added rows, keep the template clonedrow
                $('.productitem tr.product_field_clone').remove();
                // Remove all but the first clonedrow (keep template row)
                var clonedRows = $('.productitem tr.clonedrow');
                if (clonedRows.length > 1) {
                    clonedRows.not(':first').remove();
                }
                // Clear all input values in the remaining clonedrow
                $('.productitem tr.clonedrow:first').find('input[type="text"], select').val('');
                $('.productitem tr.clonedrow:first').find('input[type="hidden"]').not('[name="trans_no[]"]').val('');
                $('.total_deposit_amount_all_rows').text('');
            }
            ensureReceiptApplicationOptions();
            $('#createclientreceiptmodal').modal('show');
        });

        // Open Create Refund modal
        $(document).on('click', '.createclientrefund', function(e) {
            e.preventDefault();
            var id = $(this).attr('data-id');
            var transNo = $(this).attr('data-trans-no') || 'Rec' + id;
            var amount = parseFloat($(this).attr('data-amount')) || 0;
            var applicationId = $(this).attr('data-application-id') || '';
            $('#refund_parent_receipt_id').val(id);
            $('#refund_application_id').val(applicationId);
            $('#refund_original_display').text(transNo + ' – $' + amount.toFixed(2));
            $('#refund_amount').val(amount.toFixed(2));
            $('#refund_reason').val('');
            $('#createclientrefundmodal').modal('show');
        });

        // Show reassignment reason when application is changed in edit mode
        $(document).on('change', '#receipt_application_id', function() {
            var originalAppId = $('#create_client_receipt').data('original-application-id') || '';
            var currentVal = $(this).val() || '';
            var isEditMode = $('#function_type').val() === 'edit';
            if (isEditMode && (String(originalAppId) !== String(currentVal))) {
                $('#reassignment_reason_wrapper').show();
                $('#reassignment_reason').attr('data-valid', 'required');
            } else {
                $('#reassignment_reason_wrapper').hide();
                $('#reassignment_reason').removeAttr('data-valid').val('');
            }
        });

        // ============================================================================
        // RECEIPT HANDLERS
        // ============================================================================
        function getClientReceiptInfoById(id) {
            var url = App.getUrl('clientGetReceiptInfo') || App.getUrl('siteUrl') + '/clients/getClientReceiptInfoById';
            $.ajax({
                type:'post',
                url: url,
                headers: { 'X-CSRF-TOKEN': App.getCsrf()},
                sync:true,
                data: {id:id},
                success: function(response){
                    var obj = typeof response === 'string' ? $.parseJSON(response) : response;

                    if(obj.status){
                        $('#top_value_db').val(obj.last_record_id);

                        $('#function_type').val("edit");
                        if(obj.record_get){
                            var record_get = obj.record_get;
                            var firstRecord = record_get[0] || {};
                            $('#create_client_receipt').data('original-application-id', firstRecord.application_id || '');
                            $('#reassignment_reason').val(firstRecord.reassignment_reason || '').removeAttr('data-valid');
                            $('#reassignment_reason_wrapper').hide();
                            ensureReceiptApplicationOptions(function () {
                                $('#receipt_application_id').val(firstRecord.application_id || '');
                            });
                        }
                        $('#createclientreceiptmodal').modal('show');
                        if(obj.record_get){
                            var record_get = obj.record_get;
                            var sum = 0;
                            $('.productitem tr.clonedrow').remove();
                            $('.productitem tr.product_field_clone').remove();
                            $.each(record_get, function(index, subArray) {
                                var value_sum = parseFloat(subArray.deposit_amount);
                                if (!isNaN(value_sum)) {
                                    sum += value_sum;
                                }
                                if(index <1 ){
                                    var rowCls = 'clonedrow';
                                } else {
                                    var rowCls = 'product_field_clone';
                                }
                                var trRows_client = '<tr class="'+rowCls+'"><td><input name="id[]" type="hidden" value="'+subArray.id+'" /><input data-valid="required" class="form-control report_date_fields" name="trans_date[]" type="text" value="'+subArray.trans_date+'" /></td><td><input data-valid="required" class="form-control report_entry_date_fields" name="entry_date[]" type="text" value="'+subArray.entry_date+'" /></td><td><input class="form-control unique_trans_no" type="text" value="'+subArray.trans_no+'" readonly/><input class="unique_trans_no_hidden" name="trans_no[]" type="hidden" value="'+subArray.trans_no+'" /></td><td><select class="form-control payment_method_cls" name="payment_method[]"><option value="">Select</option><option value="Cash">Cash</option><option value="Bank transfer">Bank transfer</option><option value="EFTPOS">EFTPOS</option><option value="Paid to College">Paid to College</option></select></td><td><input data-valid="required" class="form-control" name="description[]" type="text" value="'+subArray.description+'" /></td><td><span class="currencyinput" style="display: inline-block;">$</span><input data-valid="required" style="display: inline-block;" class="form-control deposit_amount_per_row" name="deposit_amount[]" type="text" value="'+subArray.deposit_amount+'" /></td><td><a class="removeitems" href="javascript:;">' + crmIcon('times') + '</a></td></tr>';
                                $('.productitem').append(trRows_client);

                                $('.productitem tr:last .payment_method_cls').val(subArray.payment_method);

                                if (typeof flatpickr !== 'undefined') {
                                    flatpickr('.report_date_fields:not(._flatpickr-initialized)', {
                                        dateFormat: 'd/m/Y',
                                        allowInput: true,
                                        onReady: function(selectedDates, dateStr, instance) {
                                            instance.element.classList.add('_flatpickr-initialized');
                                        }
                                    });
                                    flatpickr('.report_entry_date_fields:not(._flatpickr-initialized)', {
                                        dateFormat: 'd/m/Y',
                                        allowInput: true,
                                        onReady: function(selectedDates, dateStr, instance) {
                                            instance.element.classList.add('_flatpickr-initialized');
                                        }
                                    });
                                }

                                if(index <1 ){
                                    $('#receipt_id').val(subArray.receipt_id);
                                }
                            });
                            $('.total_deposit_amount_all_rows').text("$"+sum.toFixed(2));
                        }
                    }
                }
            });
        }

        // On Close Hide all content from popups
        $('#createclientreceiptmodal').on('hidden.bs.modal', function() {
            $('#create_client_receipt')[0].reset();
            $('.total_deposit_amount_all_rows').text("");
            $('#sel_client_agent_id').val("").trigger('change');
            $('#receipt_application_id').val('');
            $('#reassignment_reason').val('').removeAttr('data-valid');
            $('#reassignment_reason_wrapper').hide();
            $('#create_client_receipt').removeData('original-application-id');
            // Reset modal title
            $('#clientReceiptModalLabel').text('Create Client Receipt');
            // Reset function type
            $('#function_type').val('');

            if (typeof flatpickr !== 'undefined') {
                flatpickr('.report_entry_date_fields', {
                    dateFormat: 'd/m/Y',
                    defaultDate: 'today',
                    allowInput: true
                });
            }
        });

        // Make function available globally
        if(typeof window !== 'undefined') {
            window.getClientReceiptInfoById = getClientReceiptInfoById;
        }

        // ============================================================================
        // PAYMENT HANDLERS
        // ============================================================================
        $(document).on('click', '.addpaymentmodal', function(){
            var v = $(this).attr('data-invoiceid');
            var netamount = $(this).attr('data-netamount');
            var dueamount = $(this).attr('data-dueamount');
            $('#invoice_id').val(v);
            $('.invoicenetamount').html(netamount+' AUD');
            $('.totldueamount').html(dueamount);
            $('.totldueamount').attr('data-totaldue', dueamount);
            $('#addpaymentmodal').modal('show');
            $('.payment_field_clone').remove();
            $('.paymentAmount').val('');
        });

        $(document).on('keyup', '.paymentAmount', function(){
            grandtotal();
        });

        function grandtotal(){
            var p = 0;
            $('.paymentAmount').each(function(){
                if($(this).val() != ''){
                    p += parseFloat($(this).val());
                }
            });

            var tamount = $('.totldueamount').attr('data-totaldue');
            var am = parseFloat(tamount) - parseFloat(p);
            $('.totldueamount').html(am.toFixed(2));
        }

        $('.add_payment_field a').on('click', function(){
            var clonedval = $('.payment_field .payment_field_row .payment_first_step').html();
            $('.payment_field .payment_field_row').append('<div class="payment_field_col payment_field_clone">'+clonedval+'</div>');
        });

        $('.add_fee_type a.fee_type_btn').on('click', function(){
            var clonedval = $('.fees_type_sec .fee_type_row .fees_type_col').html();
            $('.fees_type_sec .fee_type_row').append('<div class="custom_type_col fees_type_clone">'+clonedval+'</div>');
        });

        $(document).on('click', '.payment_field_col .field_remove_col a.remove_col', function(){
            var $tr = $(this).closest('.payment_field_clone');
            var trclone = $('.payment_field_clone').length;
            if(trclone > 0){
                $tr.remove();
                grandtotal();
            }
        });

        $(document).on('click', '.fees_type_sec .fee_type_row .fees_type_clone a.remove_btn', function(){
            var $tr = $(this).closest('.fees_type_clone');
            var trclone = $('.fees_type_clone').length;
            if(trclone > 0){
                $tr.remove();
                grandtotal();
            }
        });

        // Make function available globally
        if(typeof window !== 'undefined') {
            window.grandtotal = grandtotal;
        }
    });
})();
