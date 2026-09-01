<?php

/*
|--------------------------------------------------------------------------
| Admin Console Routes
|--------------------------------------------------------------------------
|
| This file contains routes specifically for the Admin Console section,
| which is separate from the main admin panel. These routes handle
| feature management functionality like Product Types, Profiles, etc.
|
*/

use App\Http\Controllers\Admin\BranchesController;
use App\Http\Controllers\Admin\FollowupCalendarBlockTimingController;
use App\Http\Controllers\Admin\FollowupCalendarSettingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StaffroleController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\UploadChecklistController;
use App\Http\Controllers\AdminConsole\ChecklistController;
use App\Http\Controllers\AdminConsole\CrmEmailTemplateController;
use App\Http\Controllers\AdminConsole\DocumentCategoryController as AdminConsoleDocumentCategoryController;
use App\Http\Controllers\AdminConsole\DocumentChecklistController;
use App\Http\Controllers\AdminConsole\EmailController;
use App\Http\Controllers\AdminConsole\EmailLabelController;
use App\Http\Controllers\AdminConsole\LeadServiceController;
use App\Http\Controllers\AdminConsole\MasterCategoryController;
use App\Http\Controllers\AdminConsole\PartnerTypeController;
use App\Http\Controllers\AdminConsole\ProductTypeController;
use App\Http\Controllers\AdminConsole\ProfileController;
use App\Http\Controllers\AdminConsole\RecentlyModifiedClientsController;
use App\Http\Controllers\AdminConsole\Sms\SmsController as AdminConsoleSmsController;
use App\Http\Controllers\AdminConsole\Sms\SmsSendController;
use App\Http\Controllers\AdminConsole\Sms\SmsTemplateController as AdminConsoleSmsTemplateController;
use App\Http\Controllers\AdminConsole\SourceController;
use App\Http\Controllers\AdminConsole\StaffWorkloadController;
use App\Http\Controllers\AdminConsole\VisaTypeController;
use App\Http\Controllers\AdminConsole\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::prefix('adminconsole')->middleware('auth:admin')->group(function () {

    // Product Type Routes
    Route::get('/product-type', [ProductTypeController::class, 'index'])->name('adminconsole.producttype.index');
    Route::get('/product-type/create', [ProductTypeController::class, 'create'])->name('adminconsole.producttype.create');
    Route::post('/product-type/store', [ProductTypeController::class, 'store'])->name('adminconsole.producttype.store');
    Route::get('/product-type/edit/{id}', [ProductTypeController::class, 'edit'])->name('adminconsole.producttype.edit');
    Route::post('/product-type/edit', [ProductTypeController::class, 'edit'])->name('adminconsole.producttype.update');

    // Profile Routes
    Route::get('/profiles', [ProfileController::class, 'index'])->name('adminconsole.profiles.index');
    Route::get('/profiles/create', [ProfileController::class, 'create'])->name('adminconsole.profiles.create');
    Route::post('/profiles/store', [ProfileController::class, 'store'])->name('adminconsole.profiles.store');
    Route::get('/profiles/edit/{id}', [ProfileController::class, 'edit'])->name('adminconsole.profiles.edit');
    Route::post('/profiles/edit', [ProfileController::class, 'edit'])->name('adminconsole.profiles.update');

    // Partner Type Routes
    Route::get('/partner-type', [PartnerTypeController::class, 'index'])->name('adminconsole.partnertype.index');
    Route::get('/partner-type/create', [PartnerTypeController::class, 'create'])->name('adminconsole.partnertype.create');
    Route::post('/partner-type/store', [PartnerTypeController::class, 'store'])->name('adminconsole.partnertype.store');
    Route::get('/partner-type/edit/{id}', [PartnerTypeController::class, 'edit'])->name('adminconsole.partnertype.edit');
    Route::post('/partner-type/edit', [PartnerTypeController::class, 'edit'])->name('adminconsole.partnertype.update');

    // Visa Type Routes
    Route::get('/visa-type', [VisaTypeController::class, 'index'])->name('adminconsole.visatype.index');
    Route::get('/visa-type/create', [VisaTypeController::class, 'create'])->name('adminconsole.visatype.create');
    Route::post('/visa-type/store', [VisaTypeController::class, 'store'])->name('adminconsole.visatype.store');
    Route::get('/visa-type/edit/{id}', [VisaTypeController::class, 'edit'])->name('adminconsole.visatype.edit');
    Route::post('/visa-type/edit', [VisaTypeController::class, 'edit'])->name('adminconsole.visatype.update');

    // Master Category Routes
    Route::get('/master-category', [MasterCategoryController::class, 'index'])->name('adminconsole.mastercategory.index');
    Route::get('/master-category/create', [MasterCategoryController::class, 'create'])->name('adminconsole.mastercategory.create');
    Route::post('/master-category/store', [MasterCategoryController::class, 'store'])->name('adminconsole.mastercategory.store');
    Route::get('/master-category/edit/{id}', [MasterCategoryController::class, 'edit'])->name('adminconsole.mastercategory.edit');
    Route::post('/master-category/edit', [MasterCategoryController::class, 'edit'])->name('adminconsole.mastercategory.update');

    // Lead Service Routes
    Route::get('/lead-service', [LeadServiceController::class, 'index'])->name('adminconsole.leadservice.index');
    Route::get('/lead-service/create', [LeadServiceController::class, 'create'])->name('adminconsole.leadservice.create');
    Route::post('/lead-service/store', [LeadServiceController::class, 'store'])->name('adminconsole.leadservice.store');
    Route::get('/lead-service/edit/{id}', [LeadServiceController::class, 'edit'])->name('adminconsole.leadservice.edit');
    Route::post('/lead-service/edit', [LeadServiceController::class, 'edit'])->name('adminconsole.leadservice.update');

    // NOTE: Tax routes have been removed
    // TaxController and taxes table have been dropped

    // NOTE: Subject Area routes have been removed
    // SubjectAreaController and subject_areas table have been dropped

    // NOTE: Subject routes have been removed
    // SubjectController and subjects table have been dropped

    // Source Routes
    Route::get('/source', [SourceController::class, 'index'])->name('adminconsole.source.index');
    Route::get('/source/create', [SourceController::class, 'create'])->name('adminconsole.source.create');
    Route::post('/source/store', [SourceController::class, 'store'])->name('adminconsole.source.store');
    Route::get('/source/edit/{id}', [SourceController::class, 'edit'])->name('adminconsole.source.edit');
    Route::post('/source/edit', [SourceController::class, 'edit'])->name('adminconsole.source.update');

    // NOTE: Tags routes have been removed - tags work differently and don't need backend

    // Checklist section in admin console commented out
    // Route::get('/checklist', [ChecklistController::class, 'index'])->name('adminconsole.checklist.index');
    // Route::get('/checklist/create', [ChecklistController::class, 'create'])->name('adminconsole.checklist.create');
    // Route::post('/checklist/store', [ChecklistController::class, 'store'])->name('adminconsole.checklist.store');
    // Route::get('/checklist/edit/{id}', [ChecklistController::class, 'edit'])->name('adminconsole.checklist.edit');
    // Route::post('/checklist/edit', [ChecklistController::class, 'edit'])->name('adminconsole.checklist.update');

    // Workflow Routes
    Route::get('/workflow', [WorkflowController::class, 'index'])->name('adminconsole.workflow.index');
    Route::get('/workflow/create', [WorkflowController::class, 'create'])->name('adminconsole.workflow.create');
    Route::post('/workflow/store', [WorkflowController::class, 'store'])->name('adminconsole.workflow.store');
    Route::get('/workflow/edit/{id}', [WorkflowController::class, 'edit'])->name('adminconsole.workflow.edit');
    Route::get('/workflow/deactivate-workflow/{id}', [WorkflowController::class, 'deactivateWorkflow'])->name('adminconsole.workflow.deactivate');
    Route::get('/workflow/activate-workflow/{id}', [WorkflowController::class, 'activateWorkflow'])->name('adminconsole.workflow.activate');
    Route::post('/workflow/edit', [WorkflowController::class, 'edit'])->name('adminconsole.workflow.update');

    // Email Routes
    Route::get('/emails', [EmailController::class, 'index'])->name('adminconsole.emails.index');
    Route::get('/emails/create', [EmailController::class, 'create'])->name('adminconsole.emails.create');
    Route::post('/emails/store', [EmailController::class, 'store'])->name('adminconsole.emails.store');
    Route::get('/emails/edit/{id}', [EmailController::class, 'edit'])->name('adminconsole.emails.edit');
    Route::post('/emails/edit', [EmailController::class, 'edit'])->name('adminconsole.emails.update');

    // Crm Email Template Routes
    Route::get('/crm_email_template', [CrmEmailTemplateController::class, 'index'])->name('adminconsole.crmemailtemplate.index');
    Route::get('/crm_email_template/create', [CrmEmailTemplateController::class, 'create'])->name('adminconsole.crmemailtemplate.create');
    Route::post('/crm_email_template/store', [CrmEmailTemplateController::class, 'store'])->name('adminconsole.crmemailtemplate.store');
    Route::get('/crm_email_template/edit/{id}', [CrmEmailTemplateController::class, 'edit'])->name('adminconsole.crmemailtemplate.edit');
    Route::post('/crm_email_template/edit', [CrmEmailTemplateController::class, 'edit'])->name('adminconsole.crmemailtemplate.update');

    // Document Checklist Routes
    Route::get('/documentchecklist', [DocumentChecklistController::class, 'index'])->name('adminconsole.documentchecklist.index');
    Route::get('/documentchecklist/create', [DocumentChecklistController::class, 'create'])->name('adminconsole.documentchecklist.create');
    Route::post('/documentchecklist/store', [DocumentChecklistController::class, 'store'])->name('adminconsole.documentchecklist.store');
    Route::get('/documentchecklist/edit/{id}', [DocumentChecklistController::class, 'edit'])->name('adminconsole.documentchecklist.edit');
    Route::post('/documentchecklist/edit', [DocumentChecklistController::class, 'edit'])->name('adminconsole.documentchecklist.update');

    // Document Category Routes (Personal Document Type)
    Route::get('/documentcategory', [AdminConsoleDocumentCategoryController::class, 'index'])->name('adminconsole.documentcategory.index');
    Route::get('/documentcategory/create', [AdminConsoleDocumentCategoryController::class, 'create'])->name('adminconsole.documentcategory.create');
    Route::post('/documentcategory/store', [AdminConsoleDocumentCategoryController::class, 'store'])->name('adminconsole.documentcategory.store');
    Route::get('/documentcategory/edit/{id}', [AdminConsoleDocumentCategoryController::class, 'edit'])->name('adminconsole.documentcategory.edit');
    Route::post('/documentcategory/edit/{id}', [AdminConsoleDocumentCategoryController::class, 'update'])->name('adminconsole.documentcategory.update');
    Route::delete('/documentcategory/{id}', [AdminConsoleDocumentCategoryController::class, 'destroy'])->name('adminconsole.documentcategory.destroy');
    Route::get('/documentcategory/show/{id}', [AdminConsoleDocumentCategoryController::class, 'show'])->name('adminconsole.documentcategory.show');
    Route::post('/documentcategory/toggle-status/{id}', [AdminConsoleDocumentCategoryController::class, 'toggleStatus'])->name('adminconsole.documentcategory.toggleStatus');

    // Branch/Offices Routes
    Route::get('/branch', [BranchesController::class, 'index'])->name('adminconsole.branch.index');
    Route::get('/branch/create', [BranchesController::class, 'create'])->name('adminconsole.branch.create');
    Route::post('/branch/store', [BranchesController::class, 'store'])->name('adminconsole.branch.store');
    Route::get('/branch/edit/{id}', [BranchesController::class, 'edit'])->name('adminconsole.branch.edit');
    Route::get('/branch/view/{id}', [BranchesController::class, 'view'])->name('adminconsole.branch.userview');
    Route::get('/branch/view/client/{id}', [BranchesController::class, 'viewclient'])->name('adminconsole.branch.clientview');
    Route::post('/branch/edit', [BranchesController::class, 'edit'])->name('adminconsole.branch.update');

    // Staff Routes
    Route::get('/staff/active', [StaffController::class, 'active'])->name('adminconsole.staff.active');
    Route::get('/staff/inactive', [StaffController::class, 'inactive'])->name('adminconsole.staff.inactive');

    // Teams Routes
    Route::get('/teams', [TeamController::class, 'index'])->name('adminconsole.teams.index');
    Route::get('/teams/edit/{id}', [TeamController::class, 'edit'])->name('adminconsole.teams.edit');
    Route::post('/teams/edit', [TeamController::class, 'edit'])->name('adminconsole.teams.update');
    Route::post('/teams/store', [TeamController::class, 'store'])->name('adminconsole.teams.store');

    // Staff Role Routes
    Route::get('/staffrole', [StaffroleController::class, 'index'])->name('adminconsole.staffrole.index');
    Route::get('/staffrole/create', [StaffroleController::class, 'create'])->name('adminconsole.staffrole.create');
    Route::post('/staffrole/store', [StaffroleController::class, 'store'])->name('adminconsole.staffrole.store');
    Route::get('/staffrole/edit/{id}', [StaffroleController::class, 'edit'])->name('adminconsole.staffrole.edit');
    Route::post('/staffrole/edit', [StaffroleController::class, 'edit'])->name('adminconsole.staffrole.update');

    // Upload Checklists Routes
    Route::get('/upload-checklists', [UploadChecklistController::class, 'index'])->name('adminconsole.upload_checklists.index');
    Route::post('/upload-checklists/store', [UploadChecklistController::class, 'store'])->name('adminconsole.upload_checklists.store');
    Route::get('/upload-checklists/edit/{id}', [UploadChecklistController::class, 'edit'])->name('adminconsole.upload_checklists.edit');
    Route::post('/upload-checklists/edit/{id}', [UploadChecklistController::class, 'update'])->name('adminconsole.upload_checklists.update');

    // Email Labels Routes
    Route::get('/email-labels', [EmailLabelController::class, 'index'])->name('adminconsole.emaillabels.index');
    Route::get('/email-labels/create', [EmailLabelController::class, 'create'])->name('adminconsole.emaillabels.create');
    Route::post('/email-labels/store', [EmailLabelController::class, 'store'])->name('adminconsole.emaillabels.store');
    Route::get('/email-labels/edit/{id}', [EmailLabelController::class, 'edit'])->name('adminconsole.emaillabels.edit');
    Route::post('/email-labels/edit/{id}', [EmailLabelController::class, 'update'])->name('adminconsole.emaillabels.update');
    Route::delete('/email-labels/{id}', [EmailLabelController::class, 'destroy'])->name('adminconsole.emaillabels.destroy');
    Route::post('/email-labels/toggle-status/{id}', [EmailLabelController::class, 'toggleStatus'])->name('adminconsole.emaillabels.toggleStatus');

    // Recently Modified Clients Routes
    Route::get('/recent-clients', [RecentlyModifiedClientsController::class, 'index'])->name('adminconsole.recentclients.index');
    Route::post('/recent-clients/get-client-details', [RecentlyModifiedClientsController::class, 'getClientDetails'])->name('adminconsole.recentclients.getdetails');
    Route::post('/recent-clients/get-client-documents-by-category', [RecentlyModifiedClientsController::class, 'getClientDocumentsByCategory'])->name('adminconsole.recentclients.documentsbycategory');
    Route::post('/recent-clients/upload-document-to-s3', [RecentlyModifiedClientsController::class, 'uploadDocumentToS3'])->name('adminconsole.recentclients.uploaddocumenttos3');
    Route::post('/recent-clients/upload-all-documents-to-s3', [RecentlyModifiedClientsController::class, 'uploadAllDocumentsToS3'])->name('adminconsole.recentclients.uploadalldocumentstos3');
    Route::post('/recent-clients/bulk-upload-all-documents-to-s3', [RecentlyModifiedClientsController::class, 'bulkUploadAllDocumentsToS3'])->name('adminconsole.recentclients.bulkuploadalldocumentstos3');
    Route::post('/recent-clients/bulk-upload-summary', [RecentlyModifiedClientsController::class, 'bulkUploadSummary'])->name('adminconsole.recentclients.bulkuploadsummary');
    Route::post('/recent-clients/delete-public-doc', [RecentlyModifiedClientsController::class, 'deletePublicDoc'])->name('adminconsole.recentclients.deletepublicdoc');
    Route::post('/recent-clients/delete-all-public-docs-by-category', [RecentlyModifiedClientsController::class, 'deleteAllPublicDocsByCategory'])->name('adminconsole.recentclients.deleteallpublicdocsbycategory');
    Route::post('/recent-clients/delete-document', [RecentlyModifiedClientsController::class, 'deleteDocument'])->name('adminconsole.recentclients.deletedocument');
    Route::post('/recent-clients/toggle-archive', [RecentlyModifiedClientsController::class, 'toggleArchive'])->name('adminconsole.recentclients.togglearchive');
    Route::post('/recent-clients/bulk-archive', [RecentlyModifiedClientsController::class, 'bulkArchive'])->name('adminconsole.recentclients.bulkarchive');

    Route::get('/staff-workload', [StaffWorkloadController::class, 'index'])->name('adminconsole.staff-workload.index');
    Route::get('/staff-workload/{staff}', [StaffWorkloadController::class, 'show'])->name('adminconsole.staff-workload.show');

    // Features - SMS Management (visible to all admins)
    Route::prefix('features')->name('adminconsole.features.')->group(function () {
        Route::prefix('sms')->name('sms.')->group(function () {
            Route::get('/dashboard', [AdminConsoleSmsController::class, 'dashboard'])->name('dashboard');
            Route::get('/history', [AdminConsoleSmsController::class, 'history'])->name('history');
            Route::get('/history/{id}', [AdminConsoleSmsController::class, 'show'])->name('history.show');
            Route::get('/statistics', [AdminConsoleSmsController::class, 'statistics'])->name('statistics');
            Route::get('/status/{smsLogId}', [AdminConsoleSmsController::class, 'checkStatus'])->name('status.check');
            Route::get('/send', [SmsSendController::class, 'create'])->name('send.create');
            Route::post('/send', [SmsSendController::class, 'send'])->name('send');
            Route::post('/send/template', [SmsSendController::class, 'sendFromTemplate'])->name('send.template');
            Route::post('/send/bulk', [SmsSendController::class, 'sendBulk'])->name('send.bulk');
            Route::get('/templates', [AdminConsoleSmsTemplateController::class, 'index'])->name('templates.index');
            Route::get('/templates/create', [AdminConsoleSmsTemplateController::class, 'create'])->name('templates.create');
            Route::post('/templates', [AdminConsoleSmsTemplateController::class, 'store'])->name('templates.store');
            Route::get('/templates/{id}/edit', [AdminConsoleSmsTemplateController::class, 'edit'])->name('templates.edit');
            Route::put('/templates/{id}', [AdminConsoleSmsTemplateController::class, 'update'])->name('templates.update');
            Route::delete('/templates/{id}', [AdminConsoleSmsTemplateController::class, 'destroy'])->name('templates.destroy');
            Route::get('/templates/{id}', [AdminConsoleSmsTemplateController::class, 'show'])->name('templates.show');
            Route::get('/templates-active', [AdminConsoleSmsTemplateController::class, 'active'])->name('templates.active');
        });
    });

    Route::prefix('followups')->name('adminconsole.followups.')->group(function () {
        Route::get('/calendar-settings', [FollowupCalendarSettingController::class, 'index'])->name('calendar-settings.index');
        Route::get('/calendar-settings/{followupCalendarSetting}/edit', [FollowupCalendarSettingController::class, 'edit'])->name('calendar-settings.edit');
        Route::put('/calendar-settings/{followupCalendarSetting}', [FollowupCalendarSettingController::class, 'update'])->name('calendar-settings.update');

        Route::get('/blocked-times', [FollowupCalendarBlockTimingController::class, 'index'])->name('blocked-times.index');
        Route::get('/blocked-times/create', [FollowupCalendarBlockTimingController::class, 'create'])->name('blocked-times.create');
        Route::post('/blocked-times', [FollowupCalendarBlockTimingController::class, 'store'])->name('blocked-times.store');
        Route::get('/blocked-times/{followupCalendarBlockTiming}', [FollowupCalendarBlockTimingController::class, 'show'])->name('blocked-times.show');
        Route::get('/blocked-times/{followupCalendarBlockTiming}/edit', [FollowupCalendarBlockTimingController::class, 'edit'])->name('blocked-times.edit');
        Route::put('/blocked-times/{followupCalendarBlockTiming}', [FollowupCalendarBlockTimingController::class, 'update'])->name('blocked-times.update');
        Route::delete('/blocked-times/{followupCalendarBlockTiming}', [FollowupCalendarBlockTimingController::class, 'destroy'])->name('blocked-times.destroy');
    });

});
