var requiredError = 'This field is required.';
var emailError = "Please enter the valid email address.";
var captcha = "Captcha invalid.";
var maxError = "Number should be less than or equal to ";
var min = "This field should be greater than or equal to ";
var max = "This field should be less than or equal to ";
var equal = "This field should be equal to ";

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

// Helper function to sync TinyMCE content to textarea before form submission
function syncEditorContent($form) {
	$form.find('textarea.tinymce-simple, textarea.tinymce-full').each(function() {
		var $field = $(this);
		if(typeof tinymce !== 'undefined') {
			var editorId = $field.attr('id');
			if(editorId) {
				var editor = tinymce.get(editorId);
				if(editor) {
					$field.val(editor.getContent());
					return;
				}
			}
			try {
				var editor = tinymce.get($field[0]);
				if(editor) {
					$field.val(editor.getContent());
				}
			} catch(e) {}
		}
	});
}

/**
 * Refresh the activities list after an AJAX action (notes, appointments, etc.).
 * Uses shared getallactivities() when available; otherwise fetches server-rendered HTML.
 */
function refreshClientActivities(clientId) {
	if (typeof getallactivities === 'function') {
		getallactivities();
		return;
	}

	var id = clientId || $('input[name="client_id"]').val();
	if (!id || typeof site_url === 'undefined') {
		return;
	}

	$.ajax({
		url: site_url + '/get-activities',
		type: 'GET',
		dataType: 'json',
		data: { id: id },
		success: function(responses) {
			if (typeof applyActivitiesResponse === 'function') {
				applyActivitiesResponse(responses);
				return;
			}
			var ress = typeof responses === 'string' ? JSON.parse(responses) : responses;
			if (ress && ress.html) {
				$('.activities').html(ress.html);
			}
		}
	});
}

// Helper function to get value from TinyMCE fields
function getFieldValue($field) {
	var for_class = $field.attr('class') || '';
	
	// Check if it's a TinyMCE field
	if(for_class.indexOf('tinymce-simple') !== -1 || for_class.indexOf('tinymce-full') !== -1) {
		// Try to get content from TinyMCE first
		if(typeof tinymce !== 'undefined') {
			var editorId = $field.attr('id');
			if(editorId) {
				var editor = tinymce.get(editorId);
				if(editor) {
					var content = editor.getContent();
					// Remove HTML tags and trim whitespace for validation
					var textContent = $('<div>').html(content).text();
					return $.trim(textContent);
				}
			}
			// Try to find editor by textarea element
			try {
				var editor = tinymce.get($field[0]);
				if(editor) {
					var content = editor.getContent();
					var textContent = $('<div>').html(content).text();
					return $.trim(textContent);
				}
			} catch(e) {
				// Continue to next method
			}
		}
		// Final fallback: check if textarea has value directly
		var directValue = $.trim($field.val());
		if(directValue) {
			// Remove HTML tags if present
			var textContent = $('<div>').html(directValue).text();
			return $.trim(textContent);
		}
	}
	
	// Tom Select enhanced fields
	if ($field[0] && ($field[0].tomselect || $field.hasClass('tomselect-migrated') || $field.hasClass('tomselect')) &&
		typeof getEnhancedSelectValue === 'function') {
		var enhancedVal = getEnhancedSelectValue($field[0]);
		if (Array.isArray(enhancedVal)) {
			var enhancedFiltered = enhancedVal.filter(function (v) {
				return v !== '' && v != null;
			});
			return enhancedFiltered.length ? enhancedFiltered : '';
		}
		return enhancedVal ? enhancedVal : '';
	}

	// Multi-select (Tom Select / native)
	if ($field.prop('multiple')) {
		var multiVal = $field.val();
		if (!multiVal || !multiVal.length) {
			return '';
		}
		var filtered = multiVal.filter(function (v) {
			return v !== '' && v != null;
		});
		return filtered.length ? filtered : '';
	}

	// Default to regular val() for other fields
	return $.trim($field.val());
}

function appendFieldValidationError($field, errorHtml) {
	if (typeof placeValidationError === 'function' &&
		($field.hasClass('tomselect') || $field.hasClass('tomselect-migrated') || ($field[0] && $field[0].tomselect))) {
		placeValidationError($field[0], errorHtml);
		return;
	}
	$field.after(errorHtml);
}

function customValidate(formName, savetype = '')
	{
		$(".popuploader").show(); //all form submit
		
		// IMPORTANT: Check if category system is handling this form
		// If yes, validate the form but let document-categories.js handle submission
		var isCategorySystemActive = (formName === 'alldocs_upload_form' && typeof window.DocumentCategoryManager !== 'undefined');
		
		if(isCategorySystemActive) {
			console.log('customValidate: Category system IS active for alldocs_upload_form');
		}
		
		var i = 0;	
		$(".custom-error").remove(); //remove all errors when submit the button
		
		$("form[name="+formName+"] :input[data-valid]").each(function(){
			var dataValidation = $(this).attr('data-valid');
			var splitDataValidation = dataValidation.split(' ');
			
			var j = 0; //for serial wise errors shown	
			if($.inArray("required", splitDataValidation) !== -1) //for required
				{
					var for_class = $(this).attr('class') || '';	
					if(for_class.indexOf('multiselect_subject') != -1)
						{
							var value = $.trim($(this).val());	
							if (value.length === 0) 
								{
									i++;
									j++;
									$(this).parent().after(errorDisplay(requiredError)); 
								}	
						} 
					else 
						{
							var fieldValue = getFieldValue($(this));
							if( !fieldValue ) 
								{
									i++;
									j++;
									appendFieldValidationError($(this), errorDisplay(requiredError));  
								}
						}
				}
			if(j <= 0)
				{
					var fieldValue = getFieldValue($(this));
					
					if($.inArray("email", splitDataValidation) !== -1) //for email
						{
							if(!validateEmail(fieldValue)) 
								{
									i++;
									appendFieldValidationError($(this), errorDisplay(emailError));  
								}
						}
						
							
					var forMin = splitDataValidation.find(a =>a.includes("min"));
					if(typeof forMin != 'undefined')
						{
							var breakMin = forMin.split('-');
							var digit = breakMin[1];

							var value = fieldValue.length;
							if(value < digit) 
								{
									i++;
									appendFieldValidationError($(this), errorDisplay(min+' '+digit+' character.'));  
								}	
						}
						
					var forMax = splitDataValidation.find(a =>a.includes("max"));
					if(typeof forMax != 'undefined')
						{
							var breakMax = forMax.split('-');
							var digit = breakMax[1];

							var value = fieldValue.length;
							if(value > digit) 
								{
									i++;
									appendFieldValidationError($(this), errorDisplay(max+' '+digit+' character.'));  
								}	
						}
						
					var forEqual = splitDataValidation.find(a =>a.includes("equal"));
					if(typeof forEqual != 'undefined')
						{
							var breakEqual = forEqual.split('-');
							var digit = breakEqual[1];

							var value = (fieldValue.replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '-')).length;
							if(value != digit) 
								{
									i++;
									appendFieldValidationError($(this), errorDisplay(equal+' '+digit+' character.'));  
								}	
						}
				}			
		});
		
		if(i > 0)
			{
				
				if(formName == 'add-query'){
					$('html, body').animate({scrollTop:$("#row_scroll"). offset(). top}, 'slow');
				}else if(formName != 'upload-answer')	{
					$('html, body').animate({scrollTop:0}, 'slow');
				}
				$(".popuploader").hide();
				return false;
			}	
		else
			{
				// If category system is active for this form, trigger submit and let document-categories.js handle it
				if(isCategorySystemActive) {
					console.log('customValidate: Validation passed, triggering submit for category system');
					$('.popuploader').hide();
					$('#alldocs_upload_form').trigger('submit');
					return false;
				}
				
				// Otherwise proceed with form-specific handlers below
				console.log('customValidate: Validation passed, using old form handler for:', formName);
				if(formName == 'add-query')
					{
						$('#preloader').show();
						$('#preloader div').show();
						var myform = document.getElementById('enquiryco');
						var fd = new FormData(myform);
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('#preloader').hide();
								$('#preloader div').hide();
								var obj = $.parseJSON(response);
								if(obj.success){
									window.location = redirecturl;
								}else{
									$('.customerror').html(obj.message);
									$('html, body').animate({scrollTop:$("#row_scroll"). offset(). top}, 'slow');
								}
							}
						});
					}else if(formName == 'queryform')
					{
						$('#preloader').show();
						$('#preloader div').show();
						var myform = document.getElementById('popenquiryco');
						var fd = new FormData(myform);
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('#preloader').hide();
								$('#preloader div').hide();
								var obj = $.parseJSON(response);
								if(obj.success){
									window.location = redirecturl;
								}else{
									$('.customerror').html(obj.message);
									
								}
							}
						});
					}else if(formName == 'add-note')
					{   
						var myform = document.getElementById('addnoteform');
						syncEditorContent($(myform));
						var fd = new FormData(myform);
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								if(obj.success){
									$('#myAddnotes .modal-title').html('');
									$('#myAddnotes #note_type').html('');
									$('#myAddnotes').modal('hide');
									// myfollowuplist removed - followup routes deleted
								}else{
									$('#myAddnotes .customerror').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});
					}else if(formName == 'edit-note')
					{   
						var myform = document.getElementById('editnoteform');
						syncEditorContent($(myform));
						var fd = new FormData(myform);
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								if(obj.success){
									$('#myeditnotes .modal-title').html('');
									$('#myeditnotes #note_type').html('');
									$('#myeditnotes').modal('hide');
									// myfollowuplist removed - followup routes deleted
								}else{
									$('#myeditnotes .customerror').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});
				}else if(formName == 'appnotetermform')
				{   
			var noteid = $('#appnotetermform input[name="noteid"]').val();
			var stagetype = $('#appnotetermform input[name="type"]').val(); // Capture stage name
					var myform = document.getElementById('appnotetermform');
					syncEditorContent($(myform));
					var fd = new FormData(myform);
					$.ajax({
						type:'post',
						url:$("form[name="+formName+"]").attr('action'),
						processData: false,
						contentType: false,
						data: fd,
						success: function(response){
								$('.popuploader').hide();
							var obj = $.parseJSON(response);
							if(obj.status){
								$('#create_applicationnote').modal('hide');
								
								// Convert stage name to accordion ID format (same logic as backend)
								var accordionId = stagetype.toLowerCase().trim().replace(/[^a-z0-9-]+/g, '-') + '_accor';
								
								$.ajax({
									url: site_url+'/get-applications-logs',
									type:'GET',
									data:{id: noteid},
									success: function(responses){
										 
										$('#accordion').html(responses);
										
										// Re-initialize ALL accordions for click functionality
										reinitializeAccordions();
										
										// Re-open the accordion where note was added (Bootstrap 5 syntax)
										var accordionElement = document.getElementById(accordionId);
										if(accordionElement) {
											var collapseInstance = bootstrap.Collapse.getInstance(accordionElement);
											if(collapseInstance) {
												collapseInstance.show();
											}
										}
										
										// Show success message
										if($('.custom-error-msg').length){
											$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
										}
									}
								});
							}else{
								$('#create_applicationnote .customerror').html('<span class="alert alert-danger">'+obj.message+'</span>');
								
							}
						}
					});
					}else if(formName == 'clientnotetermform')
					{
						
						var client_id = $('input[name="client_id"]').val(); 	
						var myform = document.getElementById('clientnotetermform');
						syncEditorContent($(myform));
						var fd = new FormData(myform);
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								
								if(obj.status){
									$('#create_note').modal('hide');
								$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
								$.ajax({
									url: site_url+'/get-partner-notes',
									type:'GET',
									data:{clientid:client_id,type:'partner'},
									success: function(responses){
										
										$('.note_term_list').html(responses);
									}
								});
									
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});
					}

					else if(formName == 'studentnotetermform')
                    {
                        var partner_id = $('input[name="partner_id"]').val();
                        var myform = document.getElementById('studentnotetermform');
                        syncEditorContent($(myform));
                        var fd = new FormData(myform);
                        $.ajax({
                            type:'post',
                            url:$("form[name="+formName+"]").attr('action'),
                            processData: false,
                            contentType: false,
                            data: fd,
                            success: function(response){
                                $('.popuploader').hide();
                                var obj = $.parseJSON(response);
                                if(obj.status){
                                    $('#create_student_note').modal('hide');
                                    $('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
                                    
									// Refresh activities using the global function if available
									if(typeof getallactivities === 'function') {
										getallactivities();
									}
									// Refresh notes if function is available
									if(typeof getallnotes === 'function') {
										getallnotes();
									}
                                } else {
                                    $('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
                                }
                            }
                        });
                    }
					else if(formName == 'clientbranch')
					{
						
						var client_id = $('input[name="client_id"]').val(); 	
						var myform = document.getElementById('clientbranch');
						var fd = new FormData(myform);
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								
								if(obj.status){
									$('#add_clientbranch').modal('hide');
								$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
								$.ajax({
									url: site_url+'/get-branches',
									type:'GET',
									data:{clientid:client_id,type:'partner'},
									success: function(responses){
										
										$('.branch_term_list').html(responses);
									}
								});
									
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});
					}else if(formName == 'taskform'){
						var client_id = $('#tasktermform input[name="client_id"]').val();
						var myform = document.getElementById('tasktermform');
						var fd = new FormData(myform);	
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								$('#opentaskmodal').modal('hide');
								if(obj.status){
									$('#create_note').modal('hide');
								$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									$.ajax({
										url: site_url+'/get-tasks',
										type:'GET',
										data:{clientid:client_id},
										success: function(responses){
											// $('#my-datatable').DataTable().destroy();
											$('.taskdata').html(responses);
											$('#my-datatable').DataTable({
												"searching": false,
												"lengthChange": false,
											  "columnDefs": [
												{ "sortable": false, "targets": [0, 2, 3] }
											  ],
											  order: [[1, "desc"]] //column indexes is zero based

												
											}).draw();
											
										}
									});
									
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});		
					}

						else if(formName == 'create_client_receipt'){
						var client_id = $('#create_client_receipt input[name="client_id"]').val();
						var myform = document.getElementById('create_client_receipt');
						var fd = new FormData(myform);
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							dataType: 'json',
							timeout: 60000,
							success: function(obj){
								if (!obj || typeof obj !== 'object') {
									$('.popuploader').hide();
									$('#createclientreceiptmodal').modal('hide');
									$('.custom-error-msg').html('<span class="alert alert-danger">Invalid response from server. Please refresh and try again.</span>');
									return;
								}
								$('.popuploader').hide();
								$('#createclientreceiptmodal').modal('hide');
								if(obj.status){
                                    if(obj.function_type == 'add')
                                    {
                                        if(obj.requestData){
                                            var reqData = obj.requestData;
                                            var awsUrl = obj.awsUrl; //console.log('awsUrl='+awsUrl);
                                            var lastInsertedId = obj.lastInsertedId; //console.log('lastInsertedId='+lastInsertedId);
 	                                        var validate_receipt = obj.validate_receipt;
                                            var printUrl = obj.printUrl || (lastInsertedId ? '/clients/printpreview/' + lastInsertedId : '');
                                            var trRows = "";
                                            $.each(reqData, function(index, subArray) {
                                                var rowId = subArray.id || lastInsertedId;
                                                var rowPrintUrl = rowId ? ('/clients/printpreview/' + rowId) : (printUrl || '');
                                                if(awsUrl != ""){
                                                    var awsLink = '<a target="_blank" class="link-primary client-receipt-action" href="'+awsUrl+'">' + crmIcon('file-pdf') + '</a>';
                                                } else {
                                                    var awsLink = '';
                                                }

                                                if(rowPrintUrl != ""){
                                                    var printLink = '<a target="_blank" class="link-primary client-receipt-action" href="'+rowPrintUrl+'" title="Print receipt">' + crmIcon('print') + '</a>';
                                                } else {
                                                    var printLink = '';
                                                }

                                                // New receipts are unvalidated; still gate on flag for safety
                                                var editLink = (parseInt(validate_receipt, 10) === 1)
                                                    ? ''
                                                    : '<a class="link-primary client-receipt-action updateclientreceipt" href="javascript:;" data-id="'+rowId+'" title="Edit receipt">' + crmIcon('pencil-alt') + '</a>';
                                                var refundLink = ' <a class="link-primary client-receipt-action createclientrefund" href="javascript:;" data-id="'+rowId+'" data-trans-no="'+(subArray.trans_no || '')+'" data-amount="'+subArray.deposit_amount+'" data-application-id="" title="Create Refund">' + crmIcon('undo') + '</a>';

                                                trRows += "<tr id=\"TrRow_"+rowId+"\"><td>"+subArray.trans_date+" "+awsLink+"</td><td>"+subArray.entry_date+"</td><td>"+(subArray.trans_no || '')+"</td><td>"+subArray.payment_method+"</td><td>"+subArray.description+"</td><td>$"+subArray.deposit_amount+" "+printLink+" "+editLink+refundLink+"</td></tr>";
                                            });
                                        }
                                        //console.log('trRows='+trRows);
                                        $('.productitemList .lastRow, .lastRow').first().before(trRows);
                                        if (typeof window.refreshCrmIcons === 'function') {
                                            window.refreshCrmIcons(document.querySelector('.productitemList'));
                                        }
                                    }
                                    if(obj.function_type == 'edit')
                                    {
                                        if(obj.requestData){
											var reqData = obj.requestData;
                                            var awsUrl = obj.awsUrl;
                                            var printUrl = obj.printUrl;
                                            var lastInsertedId = obj.lastInsertedId;
                                            var validate_receipt = obj.validate_receipt;

											// Fixed Issue #4: Update existing rows instead of emptying/rebuilding
											$.each(reqData, function(index, subArray) {
												var $existingRow = $('#TrRow_'+subArray.id);
												
												if($existingRow.length === 0) {
													console.error('Row not found for receipt ID:', subArray.id);
													return; // Skip this iteration
												}
												
												// Build links
												var awsLink = '';
												var printLink = '';
												var editLink = '';
												var refundLink = ' <a class="link-primary client-receipt-action createclientrefund" href="javascript:;" data-id="'+subArray.id+'" data-trans-no="'+subArray.trans_no+'" data-amount="'+subArray.deposit_amount+'" data-application-id="'+(subArray.application_id || '')+'" title="Create Refund">' + crmIcon('undo') + '</a>';
												
												if(awsUrl != ""){
                                                    awsLink = '<a target="_blank" class="link-primary client-receipt-action" href="'+awsUrl+'">' + crmIcon('file-pdf') + '</a>';
                                                }

                                                if(printUrl != ""){
                                                    printLink = '<a target="_blank" class="link-primary client-receipt-action" href="'+printUrl+'" title="Print receipt">' + crmIcon('print') + '</a>';
                                                }

												if (parseInt(validate_receipt, 10) !== 1) {
													editLink = '<a class="link-primary client-receipt-action updateclientreceipt" href="javascript:;" data-id="'+subArray.id+'" title="Edit receipt">' + crmIcon('pencil-alt') + '</a>';
												}

												// Update each TD cell instead of emptying the entire row
												$existingRow.find('td:eq(0)').html(subArray.trans_date+" "+awsLink);
												$existingRow.find('td:eq(1)').html(subArray.entry_date);
												$existingRow.find('td:eq(2)').html(subArray.trans_no);
												$existingRow.find('td:eq(3)').html(subArray.payment_method);
												$existingRow.find('td:eq(4)').html(subArray.description);
												$existingRow.find('td:eq(5)').html("$"+subArray.deposit_amount+" "+printLink+" "+editLink+refundLink);
											});
                                            if (typeof window.refreshCrmIcons === 'function') {
                                                window.refreshCrmIcons(document.querySelector('.productitemList'));
                                            }
										}
                                    }
									if(obj.db_total_deposit_amount){
										$('.totDepoAmTillNow').html("$"+obj.db_total_deposit_amount);

										$('#sum_of_client_receipts').val("$"+obj.db_total_deposit_amount);
                                    }
									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
								}
                            },
							error: function(xhr, status, err) {
								$('.popuploader').hide();
								$('#createclientreceiptmodal').modal('hide');
								var msg = (status === 'timeout') ? 'Request timed out. Please try again.' : 'Request failed. Please try again.';
								try {
									var r = typeof xhr.responseText === 'string' ? $.parseJSON(xhr.responseText) : null;
									if (r && r.message) msg = r.message;
								} catch(e) {}
								$('.custom-error-msg').html('<span class="alert alert-danger">'+msg+'</span>');
							},
							complete: function() {
								$('.popuploader').hide();
							}
						});
					}

					else if(formName == 'create_client_refund'){
						var myform = document.getElementById('create_client_refund');
						var fd = new FormData(myform);
						$.ajax({
							type:'post',
							url: myform.action || $("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							dataType: 'json',
							success: function(obj){
								$('.popuploader').hide();
								$('#createclientrefundmodal').modal('hide');
								if(obj.status){
									if(obj.db_total_deposit_amount !== undefined){
										$('.totDepoAmTillNow').html("$"+obj.db_total_deposit_amount);
										$('#sum_of_client_receipts').val("$"+obj.db_total_deposit_amount);
									}
									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									location.reload();
								} else {
									$('.custom-error-msg').html('<span class="alert alert-danger">'+(obj.message || 'Something went wrong.')+'</span>');
								}
							},
							error: function(xhr){
								$('.popuploader').hide();
								var msg = 'Failed to save refund.';
								try {
									var err = JSON.parse(xhr.responseText);
									if(err.message) msg = err.message;
								} catch(e){}
								$('.custom-error-msg').html('<span class="alert alert-danger">'+msg+'</span>');
							}
						});
					}
					
                    
					 else if(formName == 'create_student_invoice')
                    {
						var partner_id = $('#create_student_invoice input[name="partner_id"]').val();
						var myform = document.getElementById('create_student_invoice');
						var fd = new FormData(myform);
                        fd.append('save_type', savetype);
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide();
								var obj = $.parseJSON(response);
								$('#createpartnerstudentinvoicemodal').modal('hide');
								if(obj.status){
                                    if(obj.function_type == 'add')
                                    {
                                        if(obj.requestData)
                                        {
                                            var subArray = obj.requestData;
                                            var awsUrl = obj.awsUrl;
                                            var printUrl = obj.printUrl;
                                            //var lastInsertedId = obj.lastInsertedId;
                                            var db_total_enrolled_student = obj.db_total_enrolled_student;
                                            var db_total_amount = obj.db_total_amount;
                                            var last_invoice_id = obj.last_invoice_id;
                                            var trRows = "";

                                            if(awsUrl != ""){
                                                var awsLink = '<a target="_blank" class="link-primary" href="'+awsUrl+'">' + crmIcon('file-pdf') + '</a>';
                                            } else {
                                                var awsLink = '';
                                            }
                                            if(printUrl != ""){
                                                var printLink = '<a target="_blank" class="link-primary" href="'+printUrl+'">' + crmIcon('print') + '</a>';
                                            } else {
                                                var printLink = '';
                                            }
                                            var draftlink = '<a class="link-primary updatedraftstudentinvoice" href="javascript:;" data-invoiceid="'+last_invoice_id+'">' + crmIcon('pencil-alt') + '</a>';
                                            var dellink = '<a class="link-primary deletestudentinvoice" href="javascript:;" data-invoiceid="'+last_invoice_id+'" data-invoicetype="1" data-partnerid="'+obj.partnerid+'">' + crmIcon('trash') + '</a>';
                                            trRows += "<tr id='TrRow_"+last_invoice_id+"'><td style='padding-top: 5px !important;padding-bottom: 5px !important;'>"+subArray.invoice_date+" "+awsLink+"</td><td style='padding-top: 5px !important;padding-bottom: 5px !important;'>"+subArray.invoice_no+"</td><td style='padding-top: 5px !important;padding-bottom: 5px !important;'>"+db_total_enrolled_student+"</td><td style='padding-top: 5px !important;padding-bottom: 5px !important;'>$"+db_total_amount+" "+printLink+" "+draftlink+" "+dellink+"</td><td style='padding-top: 5px !important;padding-bottom: 5px !important;'><select name='sent_option'  class='sent_option' data-invoiceid="+last_invoice_id+"><option value='No'>No</option><option value='Yes'>Yes</option></select></td></tr>";
                                        }
                                        $('.lastRow').before(trRows);
                                        if(obj.db_total_deposit_amount){
                                            $('.totDepoAmTillNow').html("$"+obj.db_total_deposit_amount);
                                        }
                                    }

                                    if(obj.function_type == 'edit')
                                    {
                                        //for delete
                                        if(obj.requestDeleteDataType == 'delete'){
                                            if(obj.requestDeleteData){
                                                var requestDeleteData = obj.requestDeleteData;
                                                $.each(requestDeleteData, function(index1, subArray1) {
                                                    $('#TrRow_'+subArray1).remove();
                                                });
                                            }
                                        }

                                        //for add new entry
                                        if(obj.requestAddDataType == 'add'){
                                            if(obj.requestAddData){
                                                var awsUrl2 = obj.awsUrl2;
                                                var printUrl2 = obj.printUrl2;
                                                var db_total_enrolled_student2 = obj.db_total_enrolled_student2;
                                                var db_total_amount2 = obj.db_total_amount2;
                                                var last_invoice_id = obj.last_invoice_id;

                                                if(awsUrl2 != ""){
                                                    var awsLink2 = '<a target="_blank" class="link-primary" href="'+awsUrl2+'">' + crmIcon('file-pdf') + '</a>';
                                                } else {
                                                    var awsLink2 = '';
                                                }
                                                if(printUrl2 != ""){
                                                    var printLink2 = '<a target="_blank" class="link-primary" href="'+printUrl2+'">' + crmIcon('print') + '</a>';
                                                } else {
                                                    var printLink2 = '';
                                                }
                                                var unique_invoice_id2 = "TrRow_"+obj.requestAddData.invoice_id;
                                                var draftlink2 = '<a class="link-primary updatedraftstudentinvoice" href="javascript:;" data-invoiceid="'+obj.requestAddData.invoice_id+'">' + crmIcon('pencil-alt') + '</a>';
                                                $('#'+unique_invoice_id2+' td:first').html(obj.requestAddData.invoice_date+" "+awsLink2);
                                                $('#'+unique_invoice_id2+' td:nth-child(3)').html(db_total_enrolled_student2);
                                                $('#'+unique_invoice_id2+' td:nth-child(4)').html("$"+db_total_amount2+" "+printLink2+" "+draftlink2);
                                            }
                                        } //end function type edit and add data

                                        if(obj.requestData){
                                            var awsUrl2 = obj.awsUrl2;
                                            var printUrl2 = obj.printUrl2;
                                            var db_total_enrolled_student2 = obj.db_total_enrolled_student2;
                                            var db_total_amount2 = obj.db_total_amount2;
                                            var last_invoice_id = obj.last_invoice_id;

                                            if(awsUrl2 != ""){
                                                var awsLink2 = '<a target="_blank" class="link-primary" href="'+awsUrl2+'">' + crmIcon('file-pdf') + '</a>';
                                            } else {
                                                var awsLink2 = '';
                                            }

                                            if(printUrl2 != ""){
                                                var printLink2 = '<a target="_blank" class="link-primary" href="'+printUrl2+'">' + crmIcon('print') + '</a>';
                                            } else {
                                                var printLink2 = '';
                                            }
                                            var unique_invoice_id2 = "TrRow_"+obj.requestData.invoice_id;
                                            var draftlink2 = '<a class="link-primary updatedraftstudentinvoice" href="javascript:;" data-invoiceid="'+obj.requestData.invoice_id+'">' + crmIcon('pencil-alt') + '</a>';
                                            var dellink2 = '<a class="link-primary deletestudentinvoice" href="javascript:;" data-invoiceid="'+obj.requestData.invoice_id+'" data-invoicetype="1" data-partnerid="'+obj.partnerid+'">' + crmIcon('trash') + '</a>';
                                            $('#'+unique_invoice_id2+' td:first').html(obj.requestData.invoice_date+" "+awsLink2);
                                            $('#'+unique_invoice_id2+' td:nth-child(3)').html(db_total_enrolled_student2);
                                            $('#'+unique_invoice_id2+' td:nth-child(4)').html("$"+db_total_amount2+" "+printLink2+" "+draftlink2+" "+dellink2);
                                        }
                                        if(obj.db_total_deposit_amount2){
                                            $('.totDepoAmTillNow').html("$"+obj.db_total_deposit_amount2);
                                        }
                                    } //end function type edit an edit data


									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
								} else {
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
								}
                            }
						});
					}

                    else if(formName == 'create_record_invoice')
                    {
                        var partner_id = $('#create_record_invoice input[name="partner_id"]').val();
                        var myform = document.getElementById('create_record_invoice');
                        var fd = new FormData(myform);
                        $.ajax({
                            type:'post',
                            url:$("form[name="+formName+"]").attr('action'),
                            processData: false,
                            contentType: false,
                            data: fd,
                            success: function(response){
                                $('.popuploader').hide();
                                var obj = $.parseJSON(response);
                                $('#createrecordinvoicemodal').modal('hide');
                                if(obj.status){
                                    if(obj.function_type == 'add')
                                    {
                                        if(obj.requestData){
                                            var reqData = obj.requestData;
                                            var awsUrl = obj.awsUrl; //console.log('awsUrl='+awsUrl);
                                            //var printUrl = obj.printUrl; //console.log('printUrl='+printUrl);
                                            var lastInsertedId = obj.lastInsertedId; //console.log('lastInsertedId='+lastInsertedId);
                                            var trRows = "";
                                            $.each(reqData, function(index, subArray) {
                                                if(awsUrl != ""){
                                                    var awsLink = '<a target="_blank" class="link-primary" href="'+awsUrl+'">' + crmIcon('file-pdf') + '</a>';
                                                } else {
                                                    var awsLink = '';
                                                }
                                                var dellink2 = '<a class="link-primary deletestudentrecordinvoice" href="javascript:;" data-uniqueid="'+subArray.id+'" data-invoicetype="2" data-partnerid="'+subArray.partnerid+'">' + crmIcon('trash') + '</a>';
                                                trRows += "<tr id='TrRecordRow_"+subArray.id+"'><td>"+subArray.invoice_date+" "+awsLink+"</td><td>"+subArray.sent_date+"</td><td>"+subArray.invoice_no+"</td><td>$"+subArray.amount_aud+" "+dellink2+"</td></tr>";
                                            });
                                        }
                                        //console.log('trRows='+trRows);
                                        $('.lastRow_invoice').before(trRows);
                                    }
                                    if(obj.db_total_deposit_amount){
                                        $('.totDepoAmTillNow_invoice').html("$"+obj.db_total_deposit_amount);
                                    }
                                    $('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
                                }else{
                                    $('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
                                }
                            }
                        });
                    }

                    else if(formName == 'create_record_payment')
                    {
                        var partner_id = $('#create_record_payment input[name="partner_id"]').val();
                        var myform = document.getElementById('create_record_payment');
                        var fd = new FormData(myform);
                        $.ajax({
                            type:'post',
                            url:$("form[name="+formName+"]").attr('action'),
                            processData: false,
                            contentType: false,
                            data: fd,
                            success: function(response){
                                $('.popuploader').hide();
                                var obj = $.parseJSON(response);
                                $('#createrecordpaymentmodal').modal('hide');
                                if(obj.status){
                                    if(obj.function_type == 'add')
                                    {
                                        if(obj.requestData){
                                            var reqData = obj.requestData;
                                            var awsUrl = obj.awsUrl; //console.log('awsUrl='+awsUrl);
                                            //var printUrl = obj.printUrl; //console.log('printUrl='+printUrl);
                                            var lastInsertedId = obj.lastInsertedId; //console.log('lastInsertedId='+lastInsertedId);
                                            var trRows = "";
                                            $.each(reqData, function(index, subArray) {
                                                if(awsUrl != ""){
                                                    var awsLink = '<a target="_blank" class="link-primary" href="'+awsUrl+'">' + crmIcon('file-pdf') + '</a>';
                                                } else {
                                                    var awsLink = '';
                                                }
                                                var dellink3 = '<a class="link-primary deletestudentpaymentinvoice" href="javascript:;" data-uniqueid="'+subArray.id+'" data-invoicetype="3" data-partnerid="'+subArray.partnerid+'">' + crmIcon('trash') + '</a>';
                                                trRows += "<tr id='TrPaymentRow_"+subArray.id+"'><td>"+subArray.invoice_no+" "+awsLink+"</td><td>"+subArray.method_received+"</td><td>"+subArray.verified_by+"</td><td>"+subArray.verified_date+"</td><td>$"+subArray.amount_aud+" "+dellink3+"</td></tr>";
                                            });
                                        }
                                        //console.log('trRows='+trRows);
                                        $('.lastRow_payment').before(trRows);
                                    }

                                    if(obj.db_total_deposit_amount){
                                        $('.totDepoAmTillNow_payment').html("$"+obj.db_total_deposit_amount);
                                        $('#Total_Payment_Received').val("$"+obj.db_total_deposit_amount);
                                    }
                                    $('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
                                }else{
                                    $('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
                                }
                            }
                        });
                    }

                  
                    
                    
                    else if(formName == 'partner_upload_inbox_mail'){
						var myform = document.getElementById('partner_upload_inbox_mail');
						var fd = new FormData(myform);
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide();
								var obj = $.parseJSON(response);
								$('#partnerUploadAndFetchMail').modal('hide');
                                if(obj.status){
									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
								} else {
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
                                }
                                location.reload();
							}
						});
					}
                    else if(formName == 'partner_upload_sent_mail'){
						var myform = document.getElementById('partner_upload_sent_mail');
						var fd = new FormData(myform);
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide();
								var obj = $.parseJSON(response);
								$('#partnerUploadSentAndFetchMail').modal('hide');
                                if(obj.status){
									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
								} else {
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
                                }
                                location.reload();
							}
						});
					}
                  
                   

                   
					else if(formName == 'feeform'){
						var product_id = $('#feeform input[name="product_id"]').val();
						var myform = document.getElementById('feeform');
						var fd = new FormData(myform);	
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								
								var obj = $.parseJSON(response);
								
								if(obj.status){
									$('#new_fee_option').modal('hide');
								$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									$.ajax({
										url: site_url+'/get-all-fees',
										type:'GET',
										data:{clientid:product_id},
										success: function(responses){
											 $('.popuploader').hide(); 
											$('.feeslist').html(responses);
										}
									});
									
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});		
					}

					else if(formName == 'applicationfeeform'){
						
						var myform = document.getElementById('applicationfeeform');
						var fd = new FormData(myform);	
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
									$('#new_fee_option').modal('hide');
								var obj = $.parseJSON(response);
							
								if(obj.status){
									
								$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									//$('.product_totalfee').html(obj.totalfee);
									//$('.product_discount').html(obj.discount);
									//var t = parseFloat(obj.totalfee) - parseFloat(obj.discount);
									//$('.product_net_fee').html(t);

									$('.total_course_fee_amount').html(obj.total_course_fee_amount);
									$('.scholarship_fee_amount').html(obj.scholarship_fee_amount);
                                    $('.enrolment_fee_amount').html(obj.enrolment_fee_amount);
                                    $('.material_fees').html(obj.material_fees);
                                    $('.tution_fees').html(obj.tution_fees);

		
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});		
					}

					else if(formName == 'applicationfeeformlatest'){
                        var myform = document.getElementById('applicationfeeformlatest');
						var fd = new FormData(myform);
						var appIdForReload = $(myform).find('input[name="id"]').val();
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							dataType: 'json',
							success: function(obj){
								$('.popuploader').hide();
								$('#new_fee_option_latest').modal('hide');
                                if(obj && obj.status){
                                    $('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									// Update Total Fee Paid in Commission Status section
									var totalfeeNum = parseFloat(obj.totalfee);
									var totalfeeDisplay = (!isNaN(totalfeeNum) && isFinite(totalfeeNum))
										? totalfeeNum.toFixed(2) : '0.00';
									var $feeElement = $('.fee_reported_by_college');
									if($feeElement.length){
										$feeElement.html(totalfeeDisplay);
									} else if(appIdForReload && $('.ifapplicationdetailnot').length){
										// Fallback: element not in DOM, reload application detail to show updated Total Fee Paid
										var detailUrl = (typeof App !== 'undefined' && App.getUrl && App.getUrl('getApplicationDetail'))
											? App.getUrl('getApplicationDetail') : ((typeof site_url !== 'undefined' ? site_url : '') + '/getapplicationdetail');
										if(detailUrl){
											$.ajax({
												url: detailUrl,
												type: 'GET',
												data: {id: appIdForReload},
												success: function(html){
													$('.ifapplicationdetailnot').html(html);
													if(typeof reinitializeAccordions === 'function') reinitializeAccordions();
													if(typeof initExpectDatepickers === 'function'){
														initExpectDatepickers('.ifapplicationdetailnot', appIdForReload);
													}
												}
											});
										}
									}
									try {
										if (typeof $.fn.DataTable !== 'undefined') {
											if ($('.table-3').length && $.fn.DataTable.isDataTable($('.table-3')[0])) {
												$('.table-3').DataTable().ajax.reload(null, false);
											}
											if ($('.table-31').length && $.fn.DataTable.isDataTable($('.table-31')[0])) {
												$('.table-31').DataTable().ajax.reload(null, false);
											}
										}
									} catch (eReload) { /* not on partner page or DataTables unavailable */ }
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+(obj && obj.message ? obj.message : 'An error occurred.')+'</span>');
                                }
							},
							error: function(xhr){
								$('.popuploader').hide();
								$('#new_fee_option_latest').modal('hide');
								var errMsg = 'Failed to save. Please try again.';
								try{
									var errResp = xhr.responseJSON || (xhr.responseText ? $.parseJSON(xhr.responseText) : null);
									if(errResp && errResp.message) errMsg = errResp.message;
								}catch(e){}
								$('.custom-error-msg').html('<span class="alert alert-danger">'+errMsg+'</span>');
							}
						});
					}

					else if(formName == 'servicefeeform'){
						
						var myform = document.getElementById('servicefeeform');
						var fd = new FormData(myform);	
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
									$('#new_fee_option_serv').modal('hide');
									
								var obj = $.parseJSON(response);
							
								if(obj.status){
									$(document).on("hidden.bs.modal", "#new_fee_option_serv", function (e) {
										$('body').addClass('modal-open');
									});
								$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									$('.productfeedata .installtype').html(obj.installment_type);
									$('.productfeedata .feedata').html(obj.feedata);
								
									$('.productfeedata .client_dicounts').html(obj.discount);
									var t = parseFloat(obj.totalfee) - parseFloat(obj.discount);
									$('.productfeedata .client_totl').html(t);
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});		
					// NOTE: setuppaymentschedule validation removed - Invoice Schedule feature has been removed
					// NOTE: editinvpaymentschedule validation removed - Invoice Schedule feature has been removed
					}else if(formName == 'checklistform'){
						
						var myform = document.getElementById('checklistform');
						var checklist_type = $('#checklist_type').val();
						var fd = new FormData(myform);	
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('#create_checklist').modal('hide');
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								
									if(obj.status){
									$('#document_type').val();
									$('#checklistdesc').val();
									$('.due_date_col').hide();
									$('.checklistdue_date').val(0);
								$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									$('.'+checklist_type+'_checklists').html(obj.data);
									$('.checklistcount').html(obj.countchecklist);
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});	
											
					// NOTE: addinvpaymentschedule validation removed - Invoice Schedule feature has been removed
					}else if(formName == 'editfeeform'){
						var product_id = $('#editfeeform input[name="product_id"]').val();
						var myform = document.getElementById('editfeeform');
						var fd = new FormData(myform);	
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								
								if(obj.status){
									$('#editfeeoption').modal('hide');
								$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									$.ajax({
										url: site_url+'/get-all-fees',
										type:'GET',
										data:{clientid:product_id},
										success: function(responses){
											 $('.popuploader').hide(); 
											$('.feeslist').html(responses);
										}
									});
									
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});		
					}else if(formName == 'promotionform'){
						var client_id = $('#promotionform input[name="client_id"]').val();
						var myform = document.getElementById('promotionform');
						var fd = new FormData(myform);	
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
							
								if(obj.status){
									$('#create_promotion').modal('hide');
								$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									$.ajax({
										url: site_url+'/get-promotions',
										type:'GET',
										data:{clientid:client_id},
										success: function(responses){
											 
											$('.promotionlists').html(responses);
										}
									});
									
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});		
					}else if(formName == 'editpromotionform'){
						var client_id = $('#editpromotionform input[name="client_id"]').val();
						var myform = document.getElementById('editpromotionform');
						var fd = new FormData(myform);	
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
							
								if(obj.status){
									$('#edit_promotion').modal('hide');
								$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									$.ajax({
										url: site_url+'/get-promotions',
										type:'GET',
										data:{clientid:client_id},
										success: function(responses){
											 
											$('.promotionlists').html(responses);
										}
									});
									
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});		
					}else if(formName == 'testscoreform'){
						var client_id = $('#testscoreform input[name="client_id"]').val();
						var myform = document.getElementById('testscoreform');
						var fd = new FormData(myform);	
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								$('.edit_english_test').modal('hide');
								if(obj.status){
									
								$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
								$('.tofl_lis').html(obj.toefl_Listening);	
								$('.tofl_reading').html(obj.toefl_Reading);	
								$('.tofl_writing').html(obj.toefl_Writing);	
								$('.tofl_speaking').html(obj.toefl_Speaking);
								$('.tofl_score').html(obj.score_1);	
								$('.toefl_date').html(obj.toefl_Date);
								$('.ilets_Listening').html(obj.ilets_Listening);
								$('.ilets_Reading').html(obj.ilets_Reading);
								$('.ilets_Writing').html(obj.ilets_Writing);
								$('.ilets_speaking').html(obj.ilets_Speaking);	
								$('.ilets_score').html(obj.score_2);
								$('.ilets_date').html(obj.ilets_date);
								$('.pte_Listening').html(obj.pte_Listening);
								$('.pte_Reading').html(obj.pte_Reading);	
								$('.pte_Writing').html(obj.pte_Writing);
								$('.pte_Speaking').html(obj.pte_Speaking);	
								$('.pte_score').html(obj.score_3);
								$('.pte_date').html(obj.pte_Date);
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});		
					}else if(formName == 'saveagreement'){
						
						var myform = document.getElementById('saveagreement');
						var fd = new FormData(myform);	
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								if(obj.status){
                                    location.reload();
									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});		
					}else if(formName == 'savesubjectarea'){
						
						var myform = document.getElementById('savesubjectarea');
						var fd = new FormData(myform);	
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								if(obj.status){
									$('#other_info_add').modal('hide');
									$('.otherinfolist').html(obj.data);
									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});		
					}else if(formName == 'editsubjectarea'){
						
						var myform = document.getElementById('editsubjectarea');
						var fd = new FormData(myform);	
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								if(obj.status){
									$('#other_info_edit').modal('hide');
									$('.otherinfolist').html(obj.data);
									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});		
					}else if(formName == 'othertestform'){
						var client_id = $('#othertestform input[name="client_id"]').val();
						var myform = document.getElementById('othertestform');
						var fd = new FormData(myform);	
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								$('.edit_other_test').modal('hide');
								if(obj.status){
									
								$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
								$('.gmat').html(obj.gmat);	
								$('.gre').html(obj.gre);	
								$('.sat_ii').html(obj.sat_ii);	
								$('.sat_i').html(obj.sat_i);
								
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});		
					}else if(formName == 'ajaxinvoicepaymentform'){
							var client_id = $('#ajaxinvoicepaymentform input[name="client_id"]').val();
						var myform = document.getElementById('ajaxinvoicepaymentform');
						var fd = new FormData(myform);	
						
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								$('#addpaymentmodal').modal('hide');
								if(obj.status){
								$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									 $.ajax({
										url: site_url+'/get-invoices',
										type:'GET',
										data:{clientid:client_id},
										success: function(responses){
												 $('.invoicetable').DataTable().destroy();
													$('.invoicedatalist').html(responses);
												$('.invoicetable').DataTable({
													"searching": false,
													"lengthChange": false,
												  "columnDefs": [
													{ "sortable": false, "targets": [0, 2, 3] },
													{ targets: 2, className: 'invoice-service-col' }
												  ],
												  order: [[1, "desc"]] //column indexes is zero based

													
												}).draw();
											
										}
									}); 
									
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});
					
					}
					
                    else if(formName == 'discontinue_application'){
						var client_id = $('#discontinue_application input[name="client_id"]').val();
						var myform = document.getElementById('discontinue_application');
						var fd = new FormData(myform);
                        $.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide();
								var obj = $.parseJSON(response);
								$('#discon_application').modal('hide');
								if(obj.status){
								    $('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									$('.ifdiscont').hide();
									$('.revertapp').show();
									$('.applicationstatus').html('Discontinued');

                                    $('#discontinue_reason').css('display','block');
                                    $('#discontinue_note').css('display','block');

                                    $('#discontinue_reason_text').html(obj.discontinue_reason);
                                    $('#discontinue_note_text').html(obj.discontinue_note);
                                } else {
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
                                }
							}
						});
                    }

                    else if(formName == 'refund_app')
                    {
                        //var client_id = $('#refund_app input[name="client_id"]').val();
                        var myform = document.getElementById('refund_app');
                        var fd = new FormData(myform);
                        $.ajax({
                            type:'post',
                            url:$("form[name="+formName+"]").attr('action'),
                            processData: false,
                            contentType: false,
                            data: fd,
                            success: function(response){
                                $('.popuploader').hide();
                                try {
                                    var obj = $.parseJSON(response);
                                    $('#refund_application').modal('hide');
                                    if(obj.status){
                                        $('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
                                        $('.refund_application').hide();
                                        $('.backstage').hide();
                                        $('.revertapp').hide();
                                        $('.completestage').hide();
                                        $('.nextstage').hide();
                                        $('.discon_application').show();
                                        $('.applicationstatus').html('Refund');
                                        $('#refund_note').css('display','block');
                                        $('#refund_note_text').html(obj.refund_note);
                                        
                                        // Scroll to success message
                                        $('html, body').animate({scrollTop:0}, 'slow');
                                    } else {
                                        $('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
                                        // Scroll to error message
                                        $('html, body').animate({scrollTop:0}, 'slow');
                                    }
                                } catch(e) {
                                    console.error('Error parsing response:', e);
                                    $('.custom-error-msg').html('<span class="alert alert-danger">An error occurred. Please try again.</span>');
                                    $('html, body').animate({scrollTop:0}, 'slow');
                                }
                            },
                            error: function(xhr, status, error){
                                $('.popuploader').hide();
                                $('#refund_application').modal('hide');
                                console.error('AJAX Error:', status, error);
                                $('.custom-error-msg').html('<span class="alert alert-danger">Failed to process refund. Please try again.</span>');
                                $('html, body').animate({scrollTop:0}, 'slow');
                            }
                        });
                    }

					else if(formName == 'revertapplication'){
						var appliid = $('#revertapplication input[name="revapp_id"]').val();	
						var myform = document.getElementById('revertapplication');
						var fd = new FormData(myform);	
						
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
							var obj = $.parseJSON(response);
							$('#revert_application').modal('hide');
							if(obj.status){
								
								$.ajax({
									url: site_url+'/get-applications-logs',
									type:'GET',
									data:{id: appliid},
									success: function(responses){
										 
										$('#accordion').html(responses);
										// Re-initialize Bootstrap Collapse for click functionality
										reinitializeAccordions();
									}
								});
							$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
								
								$('.progress-circle span').html(obj.width+' %');
				var over = '';
				if(obj.width > 50){
					over = '50';
				}
				$("#progresscir").removeClass();
				$("#progresscir").addClass('progress-circle');
				$("#progresscir").addClass('prgs_'+obj.width);
				$("#progresscir").addClass('over_'+over); 
									 $('.ifdiscont').show();
									$('.completestage').show();
									 $('.nextstage').hide();
									 $('.revertapp').hide();
									 $('.applicationstatus').html('In Progress');
									
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});
					
					}else if(formName == 'spagent_application'){
							
						var myform = document.getElementById('spagent_application');
						if(!myform) {
							console.error('Form spagent_application not found');
							$('.popuploader').hide();
							alert('Form not found. Please refresh the page.');
							return false;
						}
						var fd = new FormData(myform);
						var actionUrl = $("form[name="+formName+"]").attr('action');
						console.log('Submitting to:', actionUrl);
						
						$.ajax({
							type:'post',
							url: actionUrl,
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								console.log('Response:', response);
								var obj = typeof response === 'string' ? $.parseJSON(response) : response;
								$('#superagent_application').modal('hide');
								if(obj.status){
									$('#super_agent').val('');
									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									$('.supagent_data').html(obj.data);	
								}else{
									alert(obj.message || 'Error saving super agent');
								}
							},
							error: function(xhr, status, error){
								$('.popuploader').hide();
								$('#superagent_application').modal('hide');
								console.error('Error saving super agent:', error, xhr.responseText);
								alert('Error saving super agent: ' + error);
							}
						});
					
					}else if(formName == 'sbagent_application'){
							
						var myform = document.getElementById('sbagent_application');
						var fd = new FormData(myform);	
						
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = typeof response === 'string' ? $.parseJSON(response) : response;
								$('#subagent_application').modal('hide');
								if(obj.status){
									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									$('.subagent_data').html(obj.data);	
								}else{
									alert(obj.message || 'Error saving sub agent');
								}
							},
							error: function(xhr, status, error){
								$('.popuploader').hide();
								$('#subagent_application').modal('hide');
								console.error('Error saving sub agent:', error);
								alert('Error saving sub agent. Please try again.');
							}
						});
					
					}else if(formName == 'xapplication_ownership'){
							
						var myform = document.getElementById('xapplication_ownership');
						var fd = new FormData(myform);	
						
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								$('#application_ownership').modal('hide');
								if(obj.status){
								$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									 
									$('.application_ownership').attr('data-ration',obj.ratio);	
								}else{
									alert(obj.message);
									
								}
							}
						});
					
					}else if(formName == 'saleforcast'){
							
						var myform = document.getElementById('saleforcast');
						var fd = new FormData(myform);	
						
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								$('#application_opensaleforcast').modal('hide');
								if(obj.status){
								$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									 $('.opensaleforcast').attr('data-client_revenue',obj.client_revenue);	
									 $('.opensaleforcast').attr('data-partner_revenue',obj.partner_revenue);	
									 $('.opensaleforcast').attr('data-discounts',obj.discounts);	
									 
									$('.appsaleforcast .client_revenue').html(obj.client_revenue);	
									$('.appsaleforcast .partner_revenue').html(obj.partner_revenue);	
									$('.appsaleforcast .discounts').html(obj.discounts);	
									var t = parseFloat(obj.client_revenue) + parseFloat(obj.partner_revenue) - parseFloat(obj.discounts);
									$('.appsaleforcast .netrevenue').html(t);	
									$('.app_sale_forcast').html(t+ 'AUD');	
								}else{
									alert(obj.message);
									
								}
							}
						});
					
					}else if(formName == 'appointform'){
						var client_id = $('#appointform input[name="client_id"]').val();
						 var appoint_date = $('#timeslot_col_date').val(); //alert(appoint_date);
                        var appoint_time = $('#timeslot_col_time').val(); //alert(appoint_time);

						if( appoint_date == "" || appoint_time == ""){
                            $('.popuploader').hide();
                            $('.timeslot_col_date_time').show();
                            return false;
                        } else {
							$('.timeslot_col_date_time').hide();	
							var myform = document.getElementById('appointform');
							var fd = new FormData(myform);	
                            $.ajax({
                                type:'post',
                                url:$("form[name="+formName+"]").attr('action'),
                                processData: false,
                                contentType: false,
                                data: fd,
                                success: function(response){
                                    $('.popuploader').hide(); 
                                    var obj = $.parseJSON(response);

                                    if(obj.status){
                                        /*if(obj.reloadpage){
                                            location.reload();
                                        }*/
                                        $('.add_appointment').modal('hide');
                                        $('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
                                        $.ajax({
                                            url: site_url+'/get-appointments',
                                            type:'GET',
                                            data:{clientid:client_id},
                                            success: function(responses){

                                                $('.appointmentlist').html(responses);
                                            }
                                        });
                                        refreshClientActivities(client_id);
									}else{
										$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
									}
								}
							});	
					  	}
					}

					else if(formName == 'partnerappointform'){
						var client_id = $('#partnerappointform input[name="client_id"]').val();
						var myform = document.getElementById('partnerappointform');
						var fd = new FormData(myform);	
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								
								if(obj.status){
									$('#create_appoint').modal('hide');
									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									$.ajax({
										url: site_url+'/partner/get-appointments',
										type:'GET',
										data:{clientid:client_id},
										success: function(responses){
											
											$('.appointmentlist').html(responses);
										}
									});
									
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});		
					}
					else if(formName == 'applipaidserviceform'){
						var client_id = $('#appliappointform input[name="client_id"]').val();
						var noteid = $('#appliappointform input[name="noteid"]').val();
						var myform = document.getElementById('appliappointform');
						var fd = new FormData(myform);	
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								
								if(obj.status){
									$('.add_appointment').modal('hide');
									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									$.ajax({
										url: site_url+'/get-appointments',
										type:'GET',
										data:{clientid:client_id},
										success: function(responses){
											
											$('.appointmentlist').html(responses);
										}
									});
									
								$.ajax({
									url: site_url+'/get-applications-logs',
									type:'GET',
									data:{id: noteid},
									success: function(responses){
										 
										$('#accordion').html(responses);
										// Re-initialize Bootstrap Collapse for click functionality
										reinitializeAccordions();
									}
								});
								
							}else{
								$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
								
							}
						}
					});		
				}

                else if(formName == 'appliassignform'){
						var client_id = $('#appliassignform input[name="client_id"]').val();
						var noteid = $('#appliassignform input[name="noteid"]').val();
						var myform = document.getElementById('appliassignform');
						var fd = new FormData(myform);
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide();
								var obj = $.parseJSON(response);
								if(obj.success){
									$('#create_applicationaction').modal('hide');
									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');

									if(obj.application_id){
                                        $.ajax({
                                            url: site_url+'/get-applications-logs',
                                            type:'GET',
                                            data:{id: obj.application_id},
                                            success: function(responses){
                                                $('#accordion').html(responses);
                                                // Re-initialize Bootstrap Collapse for click functionality
                                                reinitializeAccordions();
                                            }
                                        });
                                    }
									
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
                                }
							}
						});
					}

                    else if(formName == 'partnerassignform'){
						var partner_id = $('#partnerassignform input[name="partner_id"]').val();
						var noteid = $('#partnerassignform input[name="noteid"]').val();
						
						// Sync Summernote/TinyMCE content to textarea before creating FormData
						var $noteField = $('#partnerassignform textarea[name="assignnote"]');
						if($noteField.length){
							// Try Summernote first
							if(typeof TinyMCEHelpers !== 'undefined' && $noteField.attr('id')){
								try {
									var noteContent = TinyMCEHelpers.getContent('#' + $noteField.attr('id'));
									$noteField.val(noteContent);
								} catch(e) {
									console.log('Summernote sync failed, trying TinyMCE');
								}
							}
							
							// Try TinyMCE directly
							if(typeof tinymce !== 'undefined'){
								var editorId = $noteField.attr('id');
								if(editorId){
									var editor = tinymce.get(editorId);
									if(editor){
										var content = editor.getContent();
										$noteField.val(content);
									}
								}
							}
						}
						
						var myform = document.getElementById('partnerassignform');
						var fd = new FormData(myform);
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide();
								var obj = $.parseJSON(response);
                                if(obj.success){
									$('#create_partneraction').modal('hide');
									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
                                    
									// Refresh activities using the global function if available
									if(typeof getallactivities === 'function') {
										getallactivities();
									}
									// Refresh notes if function is available
									if(typeof getallnotes === 'function') {
										getallnotes();
									}
                                }else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
                                }
							}
						});
					}

                    else if(formName == 'appkicationsendmail'){
						var client_id = $('#appkicationsendmail input[name="client_id"]').val();
						var noteid = $('#appkicationsendmail input[name="noteid"]').val();
						var myform = document.getElementById('appkicationsendmail');
						var fd = new FormData(myform);	
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								
								if(obj.status){
									$('#applicationemailmodal').modal('hide');
									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									$.ajax({
										url: site_url+'/get-appointments',
										type:'GET',
										data:{clientid:client_id},
										success: function(responses){
											
											$('.appointmentlist').html(responses);
										}
									});
								
								$.ajax({
									url: site_url+'/get-applications-logs',
									type:'GET',
									data:{id: noteid},
									success: function(responses){
										 
										$('#accordion').html(responses);
										// Re-initialize Bootstrap Collapse for click functionality
										reinitializeAccordions();
									}
								});
								
							}else{
								$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
								
							}
						}
					});		
				}
                 
                    else if(formName == 'sendmsg'){
						// Legacy sendmsg form/backend removed — client SMS uses #sendSmsModal + Admin Console SMS API
						$('.popuploader').hide();
						if (typeof window.openClientSendSmsModal === 'function') {
							var legacyClientId = ($('#sendmsg input[name="client_id"]').val()
								|| $('#sendSms_client_id').val()
								|| (typeof App !== 'undefined' && App.getPageConfig ? App.getPageConfig('clientId') : '')
								|| '');
							window.openClientSendSmsModal(legacyClientId, '');
						} else if (typeof toastMsg === 'function') {
							toastMsg('Please use the Send SMS button on the client page.', 'warning');
						}
					}
					

					else if(formName == 'editappointment'){
						var client_id = $('#editappointment input[name="client_id"]').val();
						var myform = document.getElementById('editappointment');
						var fd = new FormData(myform);	
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								
								if(obj.status){
									$('#edit_appointment').modal('hide');
									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									$.ajax({
										url: site_url+'/get-appointments',
										type:'GET',
										data:{clientid:client_id},
										success: function(responses){
											
											$('.appointmentlist').html(responses);
										}
									});
									refreshClientActivities(client_id);
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});		
					}else if(formName == 'editpartnerappointment'){
						var client_id = $('#editpartnerappointment input[name="client_id"]').val();
						var myform = document.getElementById('editpartnerappointment');
						var fd = new FormData(myform);	
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								
								if(obj.status){
									$('#edit_appointment').modal('hide');
									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									$.ajax({
										url: site_url+'/partner/get-appointments',
										type:'GET',
										data:{clientid:client_id},
										success: function(responses){
											
											$('.appointmentlist').html(responses);
										}
									});
									
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});		
				}else if(formName == 'notetermform')
				{
					
					var client_id = $('input[name="client_id"]').val();
					var note_type = $('input[name="vtype"]').val() || 'client'; // Get type from form, default to 'client'
					var myform = document.getElementById('notetermform');
					syncEditorContent($(myform));
					var fd = new FormData(myform);
					$.ajax({
						type:'post',
						url:$("form[name="+formName+"]").attr('action'),
						processData: false,
						contentType: false,
						data: fd,
						success: function(response){
							$('.popuploader').hide(); 
							var obj = $.parseJSON(response);
							
							if(obj.status){
								$('#create_note').modal('hide');
							$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
							$.ajax({
	url: site_url+'/get-notes',
	type:'GET',
	data:{clientid:client_id,type:note_type},
	success: function(responses){
		
		$('.note_term_list').html(responses);
	}
});
									refreshClientActivities(client_id);
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});
					}
					else if(formName == 'notetermform_n')
					{
						
						var client_id = $('input[name="client_id"]').val(); 	
						var myform = document.getElementById('notetermform_n');
						syncEditorContent($(myform));
						var fd = new FormData(myform);
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								
								if(obj.status){
								    $('#create_note_d select[name="title"]').val('');
					$("#create_note_d .tinymce-simple").val('');
				$('#create_note_d input[name="noteid"]').val('');                    
			if (typeof TinyMCEHelpers !== 'undefined') TinyMCEHelpers.resetBySelector('#create_note_d .tinymce-simple');
									$('#create_note_d').modal('hide');
								$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
								$.ajax({
		url: site_url+'/get-notes',
		type:'GET',
		data:{clientid:client_id,type:'client'},
		success: function(responses){
			
			$('.note_term_list').html(responses);
		}
	});
									refreshClientActivities(client_id);
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});
					}
					else if(formName == 'addtoapplicationform'){
						var myform = document.getElementById('addtoapplicationform');
						var fd = new FormData(myform);
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								
								if(obj.status){
$('#add_application').modal('hide');
									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
								}else{
									$('.custom-error-popupmsg').html('<span  class="alert alert-danger">'+obj.message+'</span>');
								}
							}
						});
					}

					else if(formName == 'alldocs_upload_form'){
						// Old handler - only runs when category system is NOT loaded
						// If category system IS loaded, it's already handled above after validation
						console.log('customValidate: Using old AJAX handler for alldocs_upload_form (no category system)');
						var client_id = $('#alldocs_upload_form input[name="client_id"]').val();
						var myform = document.getElementById('alldocs_upload_form');
						var fd = new FormData(myform);
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							dataType: 'json',
							success: function(response){
								console.log('customValidate old handler: AJAX success, response =', response);
								$('.popuploader').hide();
								// Already parsed as JSON due to dataType
								var obj = response;
								$('#openalldocsmodal').modal('hide');
								if(obj.status){
									$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
									if(obj.data) {
										console.log('customValidate old handler: Updating .alldocumnetlist with HTML from server');
										$('.alldocumnetlist').html(obj.data);
									}
									if(obj.griddata) {
										$('.allgriddata').html(obj.griddata);
									}
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
								}
								//getallactivities();
							},
							error: function(xhr, status, error) {
								$('.popuploader').hide();
								console.error('Form submission error:', error);
								$('.custom-error-msg').html('<span class="alert alert-danger">Error submitting form. Please try again.</span>');
							}
						});
					}

					else if(formName == 'applicationform')
					{   
						var client_id = $('input[name="client_id"]').val(); 
						var myform = document.getElementById('addapplicationformform');
						var fd = new FormData(myform);
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('.popuploader').hide(); 
								var obj = $.parseJSON(response);
								var appModal = document.querySelector('.add_appliation.show') ||
									document.querySelector('.add_appliation');
								if (appModal && typeof ApplicationModalCascade !== 'undefined') {
									ApplicationModalCascade.clearSelectValue(appModal.querySelector('#workflow'), true);
									ApplicationModalCascade.clearSelectValue(appModal.querySelector('#partner'), true);
									ApplicationModalCascade.clearSelectValue(appModal.querySelector('#product'), true);
								} else if (typeof clearEnhancedSelectValue === 'function') {
									clearEnhancedSelectValue('.add_appliation #workflow', true);
									clearEnhancedSelectValue('.add_appliation #partner', true);
									clearEnhancedSelectValue('.add_appliation #product', true);
								} else {
									$(".add_appliation #workflow").val('').trigger('change');
									$(".add_appliation #partner").val('').trigger('change');
									$(".add_appliation #product").val('').trigger('change');
								}
			$(".add_appliation #enrolment_type").val('');
			$(".add_appliation #company_name").val('');
								if(obj.status){
									$('.add_appliation').modal('hide');
								$('.custom-error-msg').html('<span class="alert alert-success">'+obj.message+'</span>');
								$.ajax({
					url: site_url+'/get-application-lists',
					type:'GET',
					datatype:'json',
					data:{id:client_id},
					success: function(responses){
						$('.applicationtdata').html(responses);
					}
				});
									refreshClientActivities(client_id);
									// If "Send Checklist" was checked, open email modal and pre-fill for checklist
									if ($('#send_checklist_after').is(':checked') && obj.application_id && $('#emailmodal').length) {
										$('#sendmail_application_id').val(obj.application_id);
										$('#sendmail_send_context').val('checklist');
										var clientId = obj.client_id || client_id;
										var clientEmail = obj.client_email || '';
										var clientName = obj.client_name || 'Client';
										if (window.RecipientSelect) {
											var checklistEntries = [RecipientSelect.buildEntry(clientId, clientName, clientEmail, 'Client')];
											if (typeof window.scheduleComposeEmailRecipients === 'function') {
												window.scheduleComposeEmailRecipients(checklistEntries);
											} else {
												$('#emailmodal').data('composeRecipientsPending', checklistEntries);
											}
										}
										$('#composechecklist-tab').tab('show');
										$('#emailmodal').modal('show');
										$('#send_checklist_after').prop('checked', false);
									}
								}else{
									$('.custom-error-msg').html('<span class="alert alert-danger">'+obj.message+'</span>');
									
								}
							}
						});
						
						
					}
				else if(formName == 'submit-review')
					{
						$("form[name=submit-review] :input[data-max]").each(function(){
							var data_max  = $(this).attr('data-max');
							var value = $.trim($(this).val());	
							if(parseInt(value) > parseInt(data_max))	
								{
									appendFieldValidationError($(this), errorDisplay(maxError + data_max)); 
									$("#loader").hide();
									return false;	
								}
							else
								{
									$("form[name="+formName+"]").submit();
									return true;
								}	
						});	
					}
				else
					{	
						if(formName == 'invoiceform')
						{
							$('input[name="btn"]').val(savetype);
						}
						$("form[name="+formName+"]").submit();
						return true;	
					} 
			}	
		
	}	
	

function customInvoiceValidate(formName, savetype)
	{
		$("#loader").show(); //all form submit
		
		var i = 0;	
		$(".custom-error").remove(); //remove all errors when submit the button
		$("#save_type").val(savetype);
		$("form[name="+formName+"] :input[data-valid]").each(function(){
			var dataValidation = $(this).attr('data-valid');
			var splitDataValidation = dataValidation.split(' ');
			
			var j = 0; //for serial wise errors shown	
			if($.inArray("required", splitDataValidation) !== -1) //for required
				{
					var for_class = $(this).attr('class');	
					if(for_class.indexOf('multiselect_subject') != -1)
						{
							var value = $.trim($(this).val());	
							if (value.length === 0) 
								{
									i++;
									j++;
									$(this).parent().after(errorDisplay(requiredError)); 
								}	
						} 
					else 
						{
							var fieldValue = getFieldValue($(this));
							if( !fieldValue ) 
								{
									i++;
									j++;
									appendFieldValidationError($(this), errorDisplay(requiredError));  
								}
						}
				}
			if(j <= 0)
				{
					var fieldValue = getFieldValue($(this));
					
					if($.inArray("email", splitDataValidation) !== -1) //for email
						{
							if(!validateEmail(fieldValue)) 
								{
									i++;
									appendFieldValidationError($(this), errorDisplay(emailError));  
								}
						}
						
							
					var forMin = splitDataValidation.find(a =>a.includes("min"));
					if(typeof forMin != 'undefined')
						{
							var breakMin = forMin.split('-');
							var digit = breakMin[1];

							var value = fieldValue.length;
							if(value < digit) 
								{
									i++;
									appendFieldValidationError($(this), errorDisplay(min+' '+digit+' character.'));  
								}	
						}
						
					var forMax = splitDataValidation.find(a =>a.includes("max"));
					if(typeof forMax != 'undefined')
						{
							var breakMax = forMax.split('-');
							var digit = breakMax[1];

							var value = fieldValue.length;
							if(value > digit) 
								{
									i++;
									appendFieldValidationError($(this), errorDisplay(max+' '+digit+' character.'));  
								}	
						}
						
					var forEqual = splitDataValidation.find(a =>a.includes("equal"));
					if(typeof forEqual != 'undefined')
						{
							var breakEqual = forEqual.split('-');
							var digit = breakEqual[1];

							var value = (fieldValue.replace(/[^a-z0-9\s]/gi, '').replace(/[_\s]/g, '-')).length;
							if(value != digit) 
								{
									i++;
									appendFieldValidationError($(this), errorDisplay(equal+' '+digit+' character.'));  
								}	
						}
				}			
		});
		
		if(i > 0)
			{
				if(formName == 'add-query'){
					$('html, body').animate({scrollTop:$("#row_scroll"). offset(). top}, 'slow');
				}else if(formName != 'upload-answer')	{
					$('html, body').animate({scrollTop:0}, 'slow');
				}
				$("#loader").hide();
				return false;
			}	
		else
			{
				if(formName == 'add-query')
					{
						$('#preloader').show();
						$('#preloader div').show();
						var myform = document.getElementById('enquiryco');
						var fd = new FormData(myform);
						$.ajax({
							type:'post',
							url:$("form[name="+formName+"]").attr('action'),
							processData: false,
							contentType: false,
							data: fd,
							success: function(response){
								$('#preloader').hide();
								$('#preloader div').hide();
								var obj = $.parseJSON(response);
								if(obj.success){
									window.location = redirecturl;
								}else{
									$('.customerror').html(obj.message);
									$('html, body').animate({scrollTop:$("#row_scroll"). offset(). top}, 'slow');
								}
							}
						});
					}
				else
					{	
				
						$("form[name="+formName+"]").submit();
						return true;	
					} 
			}	
		
	}
	
function errorDisplay(error) {
	return "<span class='custom-error' role='alert'>"+error+"</span>";
}

function validateEmail(sEmail) {
    var filter = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
    if (filter.test(sEmail)) {
		return true;
	}
    else {
		return false;
    }
}

if (typeof window !== 'undefined') {
    window.customValidate = customValidate;
    window.customInvoiceValidate = customInvoiceValidate;
    window.reinitializeAccordions = reinitializeAccordions;
}