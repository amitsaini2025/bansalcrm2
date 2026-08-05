/**
 * Admin Client Detail - Assignment handlers
 */
'use strict';

(function() {
    jQuery(document).ready(function($){
        // ============================================================================
        // ASSIGN USER HANDLER
        // ============================================================================
        $(document).on('click', '#assignUser', function(){
            $(".popuploader").show();
            var flag = true;
            var error = "";
            $(".custom-error").remove();
            
            if(typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal('#rem_cat') === '' : $('#rem_cat').val() == ''){
                $('.popuploader').hide();
                error="Assignee field is required.";
                $('#rem_cat').after("<span class='custom-error' role='alert'>"+error+"</span>");
                flag = false;
            }
            if($('#assignnote').val() == ''){
                $('.popuploader').hide();
                error="Note field is required.";
                $('#assignnote').after("<span class='custom-error' role='alert'>"+error+"</span>");
                flag = false;
            }
            if(typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal('#task_group') === '' : $('#task_group').val() == ''){
                $('.popuploader').hide();
                error="Group field is required.";
                $('#task_group').after("<span class='custom-error' role='alert'>"+error+"</span>");
                flag = false;
            }
            if(flag){
                var url = App.getUrl('clientAction') || App.getUrl('siteUrl') + '/clients/action/store';
                $.ajax({
                    type:'post',
                    url: url,
                    headers: { 'X-CSRF-TOKEN': App.getCsrf()},
                    data: {
                        note_type:'action',
                        description:$('#assignnote').val(),
                        client_id:$('#assign_client_id').val(),
                        followup_datetime:$('#popoverdatetime').val(),
                        assignee_name: typeof actionPopoverAssigneeLabel === 'function' ? actionPopoverAssigneeLabel('#rem_cat') : $('#rem_cat :selected').text(),
                        rem_cat: typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal('#rem_cat') : $('#rem_cat option:selected').val(),
                        task_group: typeof actionPopoverSelectVal === 'function' ? actionPopoverSelectVal('#task_group') : $('#task_group option:selected').val()
                    },
                    success: function(response){
                        $('.popuploader').hide();
                        var obj = typeof response === 'string' ? $.parseJSON(response) : response;
                        if(obj.success){
                            $("[data-role=popover]").each(function(){
                                // Bootstrap 5: plain hide (no BS3 inState API)
                                try {
                                    if (window.bootstrap && window.bootstrap.Popover) {
                                        var inst = window.bootstrap.Popover.getInstance(this);
                                        if (inst) { inst.hide(); return; }
                                    }
                                } catch (e) {}
                                try { $(this).popover('hide'); } catch (e2) {}
                            });
                            if(typeof getallactivities === 'function') {
                                getallactivities();
                            }
                            if(typeof getallnotes === 'function') {
                                getallnotes();
                            }
                            if(typeof showToast === 'function') {
                                showToast('Action submitted successfully.', 'success');
                            }
                        } else if(typeof showToast === 'function') {
                            showToast(obj.message || 'An error occurred. Please try again.', 'error');
                        }
                    },
                    error: function(xhr) {
                        $('.popuploader').hide();
                        var errorMessage = 'An error occurred. Please try again.';
                        try {
                            var errorObj = typeof xhr.responseJSON === 'object' && xhr.responseJSON
                                ? xhr.responseJSON
                                : $.parseJSON(xhr.responseText);
                            if(errorObj && errorObj.message) {
                                errorMessage = errorObj.message;
                            }
                        } catch (e) {
                            if(xhr.responseText) {
                                errorMessage = xhr.responseText;
                            }
                        }
                        if(typeof showToast === 'function') {
                            showToast(errorMessage, 'error');
                        }
                    }
                });
            }else{
                $("#loader").hide();
            }
        });
    });
})();
