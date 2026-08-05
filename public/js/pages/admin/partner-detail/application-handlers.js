/**
 * Admin Partner Detail - Application Handlers
 *
 * Handles application detail views, stages, and related modals.
 *
 * Dependencies:
 *   - jQuery
 *   - Flatpickr
 *   - Tom Select (initTomSelect)
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

jQuery(document).ready(function($){

    $(document).delegate('.openapplicationdetail', 'click', function(){
        var appliid = $(this).attr('data-id');
        $('.if_applicationdetail').hide();
        $('.ifapplicationdetailnot').show();
        $.ajax({
            url: App.getUrl('getApplicationDetail'),
            type:'GET',
            data:{id:appliid},
            success:function(response){
                $('.popuploader').hide();
                $('.ifapplicationdetailnot').html(response);

                if (typeof flatpickr !== 'undefined') {
                    flatpickr('.datepicker', {
                        dateFormat: "Y-m-d",
                        allowInput: true,
                        onChange: function(selectedDates, dateStr) {
                            if (selectedDates.length > 0) {
                                $.ajax({
                                    url: App.getUrl('updateApplicationIntake'),
                                    method: "GET",
                                    dataType: "json",
                                    data: {from: dateStr, appid: appliid}
                                });
                            }
                        }
                    });
                }

                if (typeof initExpectDatepickers === 'function') {
                    initExpectDatepickers('.ifapplicationdetailnot', appliid);
                }
            }
        });
    });

    $(document).delegate('#application-tab', 'click', function(){
        $('.if_applicationdetail').show();
        $('.ifapplicationdetailnot').hide();
        $('.ifapplicationdetailnot').html('<h4>Please wait ...</h4>');
    });

    $(document).delegate('.openappnote', 'click', function(){
        var apptype = $(this).attr('data-app-type');
        var id = $(this).attr('data-id');
        $('#create_applicationnote #noteid').val(id);
        $('#create_applicationnote #type').val(apptype);
        $('#create_applicationnote').modal('show');
    });

    $(document).delegate('.openappappoint', 'click', function(){
        var id = $(this).attr('data-id');
        var apptype = $(this).attr('data-app-type');
        $('#create_applicationappoint #type').val(apptype);
        $('#create_applicationappoint #appointid').val(id);
        $('#create_applicationappoint').modal('show');
    });

    $(document).delegate('.openclientemail', 'click', function(){
        var id = $(this).attr('data-id');
        var apptype = $(this).attr('data-app-type');
        $('#applicationemailmodal #type').val(apptype);
        $('#applicationemailmodal #appointid').val(id);
        $('#applicationemailmodal').modal('show');
    });

    $(document).delegate('.openchecklist', 'click', function(){
        var id = $(this).attr('data-id');
        $('#create_checklist #checklistid').val(id);
        $('#create_checklist').modal('show');
    });

    $(document).delegate('.due_date_sec a.due_date_btn', 'click', function(){
        $('.due_date_sec .due_date_col').show();
        $(this).hide();
    });
    $(document).delegate('.remove_col a.remove_btn', 'click', function(){
        $('.due_date_sec .due_date_col').hide();
        $('.due_date_sec a.due_date_btn').show();
    });

    $(document).delegate('.nextstage', 'click', function(){
        var appliid = $(this).attr('data-id');
        var stage = $(this).attr('data-stage');
        $('.popuploader').show();
        $.ajax({
            url: App.getUrl('updateStage'),
            type:'GET',
            datatype:'json',
            data:{id:appliid},
            success:function(response){
                $('.popuploader').hide();
                var obj = $.parseJSON(response);
                if(obj.status){
                    $('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
                    $('.curerentstage').text(obj.stage);
                    $.ajax({
                        url: App.getUrl('getApplicationsLogs') || (App.getUrl('siteUrl') + '/get-applications-logs'),
                        type:'GET',
                        data:{id: appliid},
                        success: function(responses){
                            $('#accordion').html(responses);
                        }
                    });
                } else {
                    $('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
                }
            }
        });
    });

    $(document).delegate('.backstage', 'click', function(){
        var appliid = $(this).attr('data-id');
        var stage = $(this).attr('data-stage');
        $('.popuploader').show();
        $.ajax({
            url: App.getUrl('updateBackStage'),
            type:'GET',
            datatype:'json',
            data:{id:appliid},
            success:function(response){
                var obj = $.parseJSON(response);
                $('.popuploader').hide();
                if(obj.status){
                    $('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
                    $('.curerentstage').text(obj.stage);
                    $.ajax({
                        url: App.getUrl('getApplicationsLogs') || (App.getUrl('siteUrl') + '/get-applications-logs'),
                        type:'GET',
                        data:{id: appliid},
                        success: function(responses){
                            $('#accordion').html(responses);
                        }
                    });
                } else {
                    $('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
                }
            }
        });
    });

    $(document).delegate('#notes-tab', 'click', function(){
        var appliid = $(this).attr('data-id');
        $('.if_applicationdetail').hide();
        $('.ifapplicationdetailnot').show();
        $.ajax({
            url: App.getUrl('getApplicationNotes'),
            type:'GET',
            data:{id:appliid},
            success:function(response){
                $('.popuploader').hide();
                $('#notes').html(response);
            }
        });
    });

    // Appointment modal timezone selects: initModalTomSelects on #create_appoint shown (if present)
});

})(); // End async wrapper
