/**
 * Admin Partner Detail - Notes, Contacts, Email Handlers
 *
 * Handles notes, contacts, branches, templates, and email modals.
 *
 * Dependencies:
 *   - jQuery
 *   - Tom Select (initTomSelect)
 *   - Summernote
 *   - config.js (App object)
 */

'use strict';

(async function() {
    if (typeof window.vendorLibsReady !== 'undefined') {
        await window.vendorLibsReady;
    } else {
        await new Promise((resolve) => {
            const check = () => {
                if (typeof $ !== 'undefined') {
                    resolve();
                } else {
                    setTimeout(check, 50);
                }
            };
            check();
        });
    }

    if (typeof window.waitForTomSelect === 'function') {
        await window.waitForTomSelect();
    }

    if (typeof window.waitForRecipientSelect === 'function') {
        await window.waitForRecipientSelect();
    } else if (typeof window.RecipientSelect === 'undefined') {
        await new Promise((resolve) => {
            const check = () => {
                if (typeof window.RecipientSelect !== 'undefined') {
                    resolve();
                } else {
                    setTimeout(check, 50);
                }
            };
            check();
        });
    }

jQuery(document).ready(function($){
    const partnerId = PageConfig.partnerId;
    const siteUrl = App.getUrl('siteUrl') || AppConfig.siteUrl || '';

    // Set contract dates
    if (PageConfig.contractExpiry) {
        $('#contract_expiry').val(PageConfig.contractExpiry);
    } else {
        $('#contract_expiry').val('');
    }

    if (PageConfig.contractStart) {
        $('#contract_start').val(PageConfig.contractStart);
    } else {
        $('#contract_start').val('');
    }

    // View note modal
    $(document).delegate('.viewnote', 'click', function(){
        $('#view_note').modal('show');
        var v = $(this).attr('data-id');
        $('#view_note input[name="noteid"]').val(v);
        $('.popuploader').show();
        $.ajax({
            url: App.getUrl('viewnotedetail'),
            type:'GET',
            datatype:'json',
            data:{note_id:v},
            success:function(response){
                $('.popuploader').hide();
                var res = JSON.parse(response);
                if(res.status){
                    $('#view_note .modal-body .note_content h5').html(res.data.title);
                    $("#view_note .modal-body .note_content p").html(res.data.description);
                    var ad = res.data.admin;
                    $("#view_note .modal-body .note_content .author").html('<a href="#">'+ad+'</a>');
                    var updated_at = res.data.updated_at;
                    $("#view_note .modal-body .note_content .lastdate").html('<a href="#">'+updated_at+'</a>');
                }
            }
        });
    });

    // Add branch
    $(document).delegate('.openbranchnew','click', function(){
        $('#add_clientbranch #appliationModalLabel').html('Add new branch');
        $('#add_clientbranch input[name="branch_id"]').val('');
        $('#add_clientbranch select[name="country_code"]').val(PageConfig.defaultCountryCode || '');
        $('#add_clientbranch #head_office').prop('checked', false);
        $('#add_clientbranch .allinputfields input').val('');
        $('#add_clientbranch .allinputfields select').val('');
        $('#add_clientbranch').modal('show');
    });

    function getallnotes(){
        $.ajax({
            url: App.getUrl('getPartnerNotes') || App.getUrl('getNotes'),
            type:'GET',
            data:{clientid: partnerId, type:'partner'},
            success: function(responses){
                $('.popuploader').hide();
                var html = responses && String(responses).trim() !== '' ? responses : '<h4>No Record Found</h4>';
                $('.note_term_list').html(html);
            }
        });
    }

    function getallactivities(){
        $.ajax({
            url: App.getUrl('getPartnerActivities') || App.getUrl('getActivities'),
            type:'GET',
            dataType:'json',
            data:{ partner_id: partnerId, id: partnerId },
            success: function(responses){
                if (typeof applyActivitiesResponse === 'function') {
                    applyActivitiesResponse(responses);
                    if ($('.activities').length && $('.activities').children().length === 0) {
                        $('.activities').html('<h4>No Record Found</h4>');
                    }
                    return;
                }
                var ress = typeof responses === 'string' ? JSON.parse(responses) : responses;
                if (ress && ress.html) {
                    $('.activities').html(ress.html);
                } else if (!ress || !ress.data || ress.data.length === 0) {
                    $('.activities').html('<h4>No Record Found</h4>');
                }
            }
        });
    }

    // Expose helpers for other modules
    window.getallnotes = getallnotes;
    window.getallactivities = getallactivities;

    var notid = '';
    var delhref = '';
    $(document).delegate('.deletenote', 'click', function(){
        $('#confirmModal').modal('show');
        notid = $(this).attr('data-id');
        delhref = $(this).attr('data-href');
    });
    $(document).delegate('#confirmModal .accept', 'click', function(){
        $('.popuploader').show();
        $.ajax({
            url: siteUrl + '/' + delhref,
            type:'GET',
            datatype:'json',
            data:{note_id:notid,type:'partner'},
            success:function(response){
                $('.popuploader').hide();
                var res = JSON.parse(response);
                $('#confirmModal').modal('hide');
                if(res.status){
                    $('#note_id_'+notid).remove();
                    if(delhref == 'deletedocs'){
                        $('.documnetlist #id_'+notid).remove();
                    }else if(delhref == 'deletebranch'){
                        $.ajax({
                            url: App.getUrl('getBranches'),
                            type:'GET',
                            data:{clientid: partnerId},
                            success: function(responses){
                                $('.branch_term_list').html(responses);
                            }
                        });
                    }else{
                        getallnotes();
                    }
                }
            }
        });
    });

    function setNoteTitleSelect($select, title) {
        if (!$select || !$select.length) {
            return;
        }
        var value = (title == null) ? '' : String(title).trim();
        $select.find('option[data-legacy-note-title="1"]').remove();
        if (value === '') {
            $select.val('');
            return;
        }
        var hasOption = $select.find('option').filter(function() {
            return $(this).val() === value;
        }).length > 0;
        if (!hasOption) {
            $select.append(
                $('<option>', {
                    value: value,
                    text: value
                }).attr('data-legacy-note-title', '1')
            );
        }
        $select.val(value);
    }

    function clearNoteTitleSelect($select) {
        if (!$select || !$select.length) {
            return;
        }
        $select.find('option[data-legacy-note-title="1"]').remove();
        $select.val('');
    }

    $(document).delegate('.create_note', 'click', function(){
        clearNoteTitleSelect($('#create_note select[name="title"]'));
        $('#create_note').modal('show');
        $('#create_note input[name="mailid"]').val(0);
        $('#create_note #appliationModalLabel').html('Create Note');
        $("#create_note .tinymce-simple").val('');
        $('#create_note input[name="noteid"]').val('');
        if (typeof TinyMCEHelpers !== 'undefined') TinyMCEHelpers.resetBySelector("#create_note .tinymce-simple");
        if($(this).attr('datatype') == 'note'){
            $('.is_not_note').hide();
        }else{
            var datasubject = $(this).attr('datasubject');
            var datamailid = $(this).attr('datamailid');
            setNoteTitleSelect($('#create_note select[name="title"]'), datasubject);
            $('#create_note input[name="mailid"]').val(datamailid);
            $('.is_not_note').show();
        }
    });

    $('#noteType').on('change', function() {
        var selectedValue = $(this).val();
        var additionalFields = $("#additionalFields");
        additionalFields.html("");

        if(selectedValue === "Call") {
            additionalFields.append(`
                <div class="form-group" style="margin-top:10px;">
                    <label for="mobileNumber">Mobile Number:</label>
                    <select name="mobileNumber" id="mobileNumber" class="form-control" data-valid="required"></select>
                    <span id="mobileNumberError" class="text-danger"></span>
                </div>
            `);

            var partner_id = $('#partner_id').val();
            $('.popuploader').show();
            $.ajax({
                url: App.getUrl('partnersFetchPartnerContactNo'),
                method: "POST",
                data: {partner_id:partner_id},
                datatype: 'json',
                success: function(response) {
                    $('.popuploader').hide();
                    var obj = $.parseJSON(response);
                    var partnerlist = '<option value="">Select Contact</option>';
                    $.each(obj.partnerContacts, function(index, subArray) {
                        var partner_country_code = subArray.partner_country_code || "";
                        var partner_phone = subArray.partner_phone || "";
                        partnerlist += '<option value="'+partner_country_code+' '+partner_phone+'">'+partner_country_code+' '+partner_phone+'</option>';
                    });
                    $('#mobileNumber').append(partnerlist);
                }
            });
        }
    });

    $(document).delegate('.create_student_note', 'click', function(){
        $('#create_student_note').modal('show');

        $('#student_id').val($(this).attr('data-studentid'));
        $('#student_ref_no').val($(this).attr('data-studentrefno'));
        $('#college_name').val($(this).attr('data-collegename'));

        $('#create_student_note input[name="mailid"]').val(0);
        $('#create_student_note input[name="title"]').val('');
        $('#create_student_note #studentappliationModalLabel').html('Add Note To Student');
        $("#create_student_note .tinymce-simple").val('');
        $('#create_student_note input[name="noteid"]').val('');
        if (typeof TinyMCEHelpers !== 'undefined') TinyMCEHelpers.resetBySelector("#create_student_note .tinymce-simple");
        if($(this).attr('datatype') == 'note'){
            $('.is_not_note').hide();
        } else {
            var datasubject = $(this).attr('datasubject');
            var datamailid = $(this).attr('datamailid');
            $('#create_student_note input[name="title"]').val(datasubject);
            $('#create_student_note input[name="mailid"]').val(datamailid);
            $('.is_not_note').show();
        }
    });

    $('#studentNoteType').on('change', function() {
        var selectedValue = $(this).val();
        var additionalStudentNoteFields = $("#additionalStudentNoteFields");
        additionalStudentNoteFields.html("");

        if(selectedValue === "Call") {
            additionalStudentNoteFields.append(`
                <div class="form-group" style="margin-top:10px;">
                    <label for="mobileNumber">Contact Number:</label>
                    <select name="mobileNumber" id="mobileNumber" class="form-control" data-valid="required"></select>
                    <span id="mobileNumberError" class="text-danger"></span>
                </div>
            `);

            var client_id = $('#student_id').val();
            $('.popuploader').show();
            $.ajax({
                url: App.getUrl('clientsFetchClientContactNo'),
                method: "POST",
                data: {client_id:client_id},
                datatype: 'json',
                success: function(response) {
                    $('.popuploader').hide();
                    var obj = $.parseJSON(response);
                    var contactlist = '<option value="">Select Contact</option>';
                    $.each(obj.clientContacts, function(index, subArray) {
                        var client_country_code = subArray.client_country_code || "";
                        var client_phone = subArray.client_phone || "";
                        contactlist += '<option value="'+client_country_code+' '+client_phone+'">'+client_country_code+' '+client_phone+' ('+subArray.contact_type+')</option>';
                    });
                    $('#mobileNumber').append(contactlist);
                }
            });
        }
    });

    $(document).delegate('.opentaskmodal', 'click', function(){
        $('#opentaskmodal').modal('show');
        $('#opentaskmodal input[name="mailid"]').val(0);
        $('#opentaskmodal input[name="title"]').val('');
        $('#opentaskmodal #appliationModalLabel').html('Create Note');
        $('#opentaskmodal input[name="attachments"]').val('');
        $('#opentaskmodal .showattachment').val('Choose file');

        var datasubject = $(this).attr('datasubject');
        var datamailid = $(this).attr('datamailid');
        $('#opentaskmodal input[name="title"]').val(datasubject);
        $('#opentaskmodal input[name="mailid"]').val(datamailid);
    });

    if (window.RecipientSelect) {
        var rsUrl = App.getUrl('clientsGetRecipients') || RecipientSelect.resolveUrl();
        RecipientSelect.init('#create_note .js-data-example-ajaxcc', {
            url: rsUrl,
            dropdownParent: '#create_note'
        });
        RecipientSelect.init('#opentaskmodal .js-data-example-ajaxcontact', {
            url: rsUrl,
            dropdownParent: '#opentaskmodal'
        });
        RecipientSelect.init('#emailmodal .js-data-example-ajax', {
            url: rsUrl,
            dropdownParent: '#emailmodal'
        });
        RecipientSelect.init('#emailmodal .js-data-example-ajaxcc', {
            url: rsUrl,
            dropdownParent: '#emailmodal'
        });
    }

    $(document).delegate('.opennoteform', 'click', function(){
        var $titleSelect = $('#create_note select[name="title"]');
        clearNoteTitleSelect($titleSelect);
        $('#create_note #appliationModalLabel').html('Edit Note');
        var v = $(this).attr('data-id');
        $('#create_note input[name="noteid"]').val(v);
        $('#create_note').modal('show');
        $('.popuploader').show();
        $.ajax({
            url: App.getUrl('getnotedetail'),
            type:'GET',
            dataType:'json',
            data:{note_id:v},
            success:function(response){
                $('.popuploader').hide();
                var res = typeof response === 'string' ? JSON.parse(response) : response;
                if(res.status){
                    setNoteTitleSelect($titleSelect, res.data.title);
                    $("#create_note .tinymce-simple").val(res.data.description);
                    if (typeof TinyMCEHelpers !== 'undefined') TinyMCEHelpers.setContentBySelector("#create_note .tinymce-simple", res.data.description);
                }
            },
            error: function() {
                $('.popuploader').hide();
            }
        });
    });

    $(document).delegate('.openbranchform', 'click', function(){
        $('#add_clientbranch').modal('show');
        $('#add_clientbranch #appliationModalLabel').html('Edit Branch');
        var v = $(this).attr('data-id');
        $('#add_clientbranch input[name="branch_id"]').val(v);
        $('.popuploader').show();
        $.ajax({
            url: App.getUrl('getbranchdetail'),
            type:'GET',
            datatype:'json',
            data:{note_id:v},
            success:function(response){
                $('.popuploader').hide();
                var res = JSON.parse(response);
                if(res.status){
                    $('#add_clientbranch input[name="name"]').val(res.data.name);
                    $('#add_clientbranch input[name="email"]').val(res.data.email);
                    $('#add_clientbranch input[name="phone"]').val(res.data.phone);
                    $('#add_clientbranch #country').val(res.data.country);
                    $('#add_clientbranch select[name="country_code"]').val(res.data.country_code || '');
                    $('#add_clientbranch input[name="city"]').val(res.data.city);
                    $('#add_clientbranch input[name="state"]').val(res.data.state);
                    $('#add_clientbranch input[name="zip_code"]').val(res.data.zip);
                    $('#add_clientbranch #branch').val(res.data.branch);
                    $('#add_clientbranch #head_office').prop('checked', res.data.primary_contact == 1);
                }
            }
        });
    });

    $(document).delegate('.pinnote', 'click', function(){
        $('.popuploader').show();
        $.ajax({
            url: App.getUrl('pinnote') + '/',
            type:'GET',
            datatype:'json',
            data:{note_id:$(this).attr('data-id')},
            success:function(){
                getallnotes();
            }
        });
    });

    $(document).delegate('.clientemail', 'click', function(){
        var id = $(this).attr('data-id');
        var name = $(this).attr('data-name');
        var email = $(this).attr('data-email');
        var entry = window.RecipientSelect
            ? RecipientSelect.buildEntry(id, name || 'Client', email || '', 'Client')
            : null;
        var $modal = $('#emailmodal');
        if (entry) {
            if (typeof window.scheduleComposeEmailRecipients === 'function') {
                window.scheduleComposeEmailRecipients([entry]);
            } else {
                $modal.data('composeRecipientsPending', [entry]);
            }
        }
        $modal.modal('show');
    });

    $(document).delegate('.selecttemplate', 'change', function(){
        var v = $(this).val();
        $.ajax({
            url: App.getUrl('getTemplates'),
            type:'GET',
            datatype:'json',
            data:{id:v},
            success: function(response){
                var res = JSON.parse(response);
                $('.selectedsubject').val(res.subject);
                if (typeof TinyMCEHelpers !== 'undefined') { TinyMCEHelpers.resetBySelector("#emailmodal .tinymce-simple"); TinyMCEHelpers.setContentBySelector("#emailmodal .tinymce-simple", res.description); }
                $("#emailmodal .tinymce-simple").val(res.description);
            }
        });
    });

    $(document).delegate('.selectapplicationtemplate', 'change', function(){
        var v = $(this).val();
        $.ajax({
            url: App.getUrl('getTemplates'),
            type:'GET',
            datatype:'json',
            data:{id:v},
            success: function(response){
                var res = JSON.parse(response);
                $('.selectedappsubject').val(res.subject);
                if (typeof TinyMCEHelpers !== 'undefined') { TinyMCEHelpers.resetBySelector("#applicationemailmodal .tinymce-simple"); TinyMCEHelpers.setContentBySelector("#applicationemailmodal .tinymce-simple", res.description); }
                $("#applicationemailmodal .tinymce-simple").val(res.description);
            }
        });
    });

    if (PageConfig.activeTab === 'partner-activities') {
        getallactivities();
    }
    if (PageConfig.activeTab === 'noteterm') {
        getallnotes();
    }
});

})(); // End async wrapper
