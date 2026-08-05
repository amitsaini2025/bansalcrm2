<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\ActionController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\ApplicationsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BranchesController;
use App\Http\Controllers\Admin\Client\ClientActivityController;
use App\Http\Controllers\Admin\Client\ClientMessagingController;
use App\Http\Controllers\Admin\Client\ClientNoteController;
use App\Http\Controllers\Admin\Client\ClientServiceController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\FollowupCalendarBlockTimingController;
use App\Http\Controllers\Admin\FollowupCalendarSettingController;
use App\Http\Controllers\Admin\FollowupController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\OfficeVisitController;
use App\Http\Controllers\Admin\OutlookController;
use App\Http\Controllers\Admin\PartnersController;
use App\Http\Controllers\Admin\ProductsController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StaffroleController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\TinyMCEImageUploadController;
use App\Http\Controllers\Admin\UploadChecklistController;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\ExceptionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Email verification routes (public - no auth required)
// These routes MUST be defined first to ensure they're publicly accessible
Route::get('/email-verify-token/{token}', [ClientMessagingController::class, 'emailVerifyToken'])->name('emailVerifyToken');
Route::get('/thankyou', [ClientMessagingController::class, 'thankyou'])->name('emailVerify.thankyou');

/*********************General Function for Both (Front-end & Back-end) ***********************/
/* Route::post('/get_states', 'HomeController@getStates');
Route::post('/get_product_views', 'HomeController@getProductViews');
Route::post('/get_product_other_info', 'HomeController@getProductOtherInformation');
Route::post('/delete_action', 'HomeController@deleteAction')->middleware('auth');
 */

Route::get('/clear-cache', function () {

    Artisan::call('config:clear');
    Artisan::call('view:clear');
    $exitCode = Artisan::call('route:clear');
    $exitCode = Artisan::call('route:cache');
    // return what you want
});

/*********************Exception Handling ***********************/
Route::match(['get', 'post'], '/exception', [ExceptionController::class, 'index'])->name('exception');
// Route::get('/import', 'TestController@import');
/*********************Front Panel Start ***********************/
// Coming Soon
// Route::get('/coming_soon', 'HomeController@coming_soon')->name('coming_soon');

// Route::get('page/{slug}', 'HomeController@Page')->name('page.slug');

// Route::get('/enquiry', 'HomeController@enquiry')->name('enquiry');
// Route::post('/enquiry/store', 'HomeController@store')->name('enquiry.store');

// Route::get('sicaptcha', 'HomeController@sicaptcha')->name('sicaptcha');
// Route::get('invoice/secure/{slug}', 'InvoiceController@invoice')->name('invoice');
// Route::get('/invoice/download/{id}', 'InvoiceController@customer_invoice_download')->name('invoice.customer_invoice_download');
// Route::get('/invoice/print/{id}', 'InvoiceController@customer_invoice_print')->name('invoice.customer_invoice_print');
// Login and Register
// Legacy user authentication routes removed - system uses admin/agent authentication only

// Route::get('/user/verify/{token}', 'Auth\RegisterController@verifyUser');
// //Forgot Password
/* Route::get('/forgot_password', 'HomeController@forgotPassword')->name('forgot_password');
Route::post('/forgot_password', 'HomeController@forgotPassword')->name('forgot_password');

//Reset Link
Route::get('/reset_link/{token}', 'HomeController@resetLink')->name('reset_link');
Route::post('/reset_link', 'HomeController@resetLink')->name('reset_link');	 */

// Review Panel
// Route::post('/add_review', 'DashboardController@addReview')->name('dashboard.add_review');

// Shipping Info
// Route::get('/address', 'DashboardController@address')->name('dashboard.address');
// Route::post('/address', 'DashboardController@address')->name('dashboard.address');

// Thankyou Page
// Route::get('/thankyou', 'PaymentController@thankyou')->name('payment.thankyou');

// //Inner Dashboard
// Route::get('/dashboard', 'DashboardController@index')->name('dashboard.index');
// Route::get('dashboard/view_order_summary/{id}', 'DashboardController@viewOrderSummary')->name('dashboard.view_order_summary');
// Route::get('/view_test_series_order/{id}', 'DashboardController@viewTestSeriesOrder')->name('dashboard.view_test_series_order');
// Route::post('/logout', 'DashboardController@logout')->name('logout');

// //Other Functions
// Route::get('/change_password', 'DashboardController@changePassword')->name('change_password');
// Route::post('/change_password', 'DashboardController@changePassword')->name('change_password');
// Route::get('/edit_profile', 'DashboardController@editProfile')->name('dashboard.edit_profile');
// Route::post('/edit_profile', 'DashboardController@editProfile')->name('dashboard.edit_profile');

// Frontend website routes removed - methods deleted from HomeController
// Removed routes: index, testimonial, ourservices, servicesdetail, search_result, contactus,
// contact, stripe, stripePost, bookappointment, getdatetime, refresh_captcha, myprofile

// Route::get('page/{slug}', 'HomeController@Page')->name('page.slug');
// Route::get('sicaptcha', 'HomeController@sicaptcha')->name('sicaptcha');
// Route::get('invoice/secure/{slug}', 'InvoiceController@invoice')->name('invoice');
// Route::get('/invoice/download/{id}', 'InvoiceController@customer_invoice_download')->name('invoice.customer_invoice_download');
// Route::get('/invoice/print/{id}', 'InvoiceController@customer_invoice_print')->name('invoice.customer_invoice_print');

// Thank you page after email verification - REMOVED (HomeController deleted, will be recreated in future)
// Route::get('thankyou', 'HomeController@thankyou')->name('thankyou');

// Root login routes - same as admin login
Route::get('/', [AdminLoginController::class, 'showLoginForm'])->name('login');
Route::post('/', [AdminLoginController::class, 'login']);

// Admin login routes (keep separate for /admin path access)
Route::get('/admin', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin', [AdminLoginController::class, 'login']);
Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm']);
Route::post('/admin/login', [AdminLoginController::class, 'login']);
Route::post('/admin/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');

/* ---------------Agent Route------------------- */
// Agent routes disabled - agents don't have login access (they exist only as records)
// require __DIR__ . '/agent.php';
/*********************Admin Panel Start ***********************/

// General
Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
Route::redirect('/appointments', '/followups', 301);
Route::get('/followups', [FollowupController::class, 'index'])->name('followups.index');
Route::get('/followups/view/{note}', [FollowupController::class, 'viewNote'])->name('followups.view');
Route::get('/followups/blocked-times', [FollowupCalendarBlockTimingController::class, 'index'])->name('followups.blocked-times.index');
Route::get('/followups/blocked-times/create', [FollowupCalendarBlockTimingController::class, 'create'])->name('followups.blocked-times.create');
Route::post('/followups/blocked-times', [FollowupCalendarBlockTimingController::class, 'store'])->name('followups.blocked-times.store');
Route::get('/followups/blocked-times/{followupCalendarBlockTiming}', [FollowupCalendarBlockTimingController::class, 'show'])->name('followups.blocked-times.show');
Route::get('/followups/blocked-times/{followupCalendarBlockTiming}/edit', [FollowupCalendarBlockTimingController::class, 'edit'])->name('followups.blocked-times.edit');
Route::put('/followups/blocked-times/{followupCalendarBlockTiming}', [FollowupCalendarBlockTimingController::class, 'update'])->name('followups.blocked-times.update');
Route::delete('/followups/blocked-times/{followupCalendarBlockTiming}', [FollowupCalendarBlockTimingController::class, 'destroy'])->name('followups.blocked-times.destroy');
Route::get('/followups/calendar-settings', [FollowupCalendarSettingController::class, 'index'])->name('followups.calendar-settings.index');
Route::get('/followups/calendar-settings/{followupCalendarSetting}/edit', [FollowupCalendarSettingController::class, 'edit'])->name('followups.calendar-settings.edit');
Route::put('/followups/calendar-settings/{followupCalendarSetting}', [FollowupCalendarSettingController::class, 'update'])->name('followups.calendar-settings.update');
Route::get('/followups/calendar/{consultant}', [FollowupController::class, 'calendar'])
    ->where('consultant', '[A-Za-z0-9][A-Za-z0-9\-_]*')
    ->name('followups.calendar');
Route::post('/followups/reassign-consultant', [FollowupController::class, 'reassignConsultant'])
    ->name('followups.reassign-consultant');
Route::post('/followups/reschedule', [FollowupController::class, 'rescheduleFollowup'])->name('followups.reschedule');
Route::post('/followups/set-outcome', [FollowupController::class, 'setFollowupOutcome'])->name('followups.set-outcome');
Route::get('/appointments/calendar/{consultant}', function (string $consultant) {
    return redirect()->route('followups.calendar', ['consultant' => $consultant], 301);
})->where('consultant', '[A-Za-z0-9][A-Za-z0-9\-_]*');
Route::post('/admin/complete-action', [AdminController::class, 'completeAction'])->name('admin.complete-action');
Route::get('/my_profile', [AdminController::class, 'myProfile'])->name('my_profile');
Route::post('/my_profile', [AdminController::class, 'myProfile'])->name('my_profile.update');
Route::get('/change_password', [AdminController::class, 'change_password'])->name('change_password');
Route::post('/change_password', [AdminController::class, 'change_password'])->name('change_password.update');
Route::get('/sessions', [AdminController::class, 'sessions'])->name('sessions');
Route::post('/sessions', [AdminController::class, 'sessions'])->name('sessions.update');
Route::post('/update_action', [AdminController::class, 'updateAction']);
Route::post('/approved_action', [AdminController::class, 'approveAction']);
Route::post('/process_action', [AdminController::class, 'processAction']);
Route::post('/archive_action', [AdminController::class, 'archiveAction']);
Route::post('/declined_action', [AdminController::class, 'declinedAction']);
Route::post('/delete_action', [AdminController::class, 'deleteAction']);
Route::post('/permanent_delete_action', [AdminController::class, 'permanentDeleteAction']);
Route::post('/delete_slot_action', [AdminController::class, 'deleteSlotAction']);
Route::post('/move_action', [AdminController::class, 'moveAction']);

// Removed: /get_chapters route - McqSubject/McqChapter functionality removed (dead code)
Route::post('/get_states', [AdminController::class, 'getStates']);
Route::get('/settings/taxes/returnsetting', [AdminController::class, 'returnsetting'])->name('returnsetting');
Route::post('/settings/taxes/savereturnsetting', [AdminController::class, 'returnsetting'])->name('savereturnsetting');
// NOTE: Tax rate routes have been removed (taxrates, taxrates/create, taxrates/store, taxrates/edit)
// These routes were related to the tax_rates table which has been dropped
Route::get('/getproductbranch', [AdminController::class, 'getproductbranch']);
Route::get('/getpartner', [AdminController::class, 'getpartner']);
Route::get('/getproduct', [AdminController::class, 'getproduct']);
Route::get('/getbranch', [AdminController::class, 'getbranch']);
Route::get('/getpartnerbranch', [AdminController::class, 'getpartnerbranch']);
Route::get('/getbranchproduct', [AdminController::class, 'getbranchproduct']);
Route::get('/getnewPartnerbranch', [AdminController::class, 'getnewPartnerbranch']);
Route::get('/getassigneeajax', [StaffController::class, 'getassigneeajax']);
Route::get('/getpartnerajax', [AdminController::class, 'getpartnerajax']);

// Client validation (AJAX) - Moved to ClientController
Route::get('/checkclientexist', [\App\Http\Controllers\Admin\Client\ClientController::class, 'checkclientexist'])
	->middleware('throttle:60,1');

// Address Autocomplete Routes
Route::post('/address/search', [AddressController::class, 'searchAddress'])->name('address.search');
Route::post('/address/details', [AddressController::class, 'getPlaceDetails'])->name('address.details');

/* CRM route start */

// Staff routes (uses Staff model/table)
Route::get('/staff', [StaffController::class, 'active'])->name('staff.index');
Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
Route::post('/staff/store', [StaffController::class, 'store'])->name('staff.store');
Route::get('/staff/edit/{id}', [StaffController::class, 'edit'])->name('staff.edit');
Route::get('/staff/view/{id}', [StaffController::class, 'view'])->name('staff.view');
Route::post('/staff/edit', [StaffController::class, 'edit'])->name('staff.update');
Route::post('/staff/savezone', [StaffController::class, 'savezone']);

Route::get('/staff/active', [StaffController::class, 'active'])->name('staff.active');
Route::get('/staff/inactive', [StaffController::class, 'inactive'])->name('staff.inactive');
Route::post('/action/assignee-list', [StaffController::class, 'getAssigneeList']);

// Legacy users routes - redirect to staff (backward compatibility)
Route::get('/users', fn () => redirect()->route('staff.active'))->name('users.index');
Route::get('/users/create', fn () => redirect()->route('staff.create'))->name('users.create');
Route::get('/users/active', fn () => redirect()->route('staff.active'))->name('users.active');
Route::get('/users/inactive', fn () => redirect()->route('staff.inactive'))->name('users.inactive');
Route::get('/users/edit/{id}', fn ($id) => redirect()->route('staff.edit', ['id' => $id]))->name('users.edit');
Route::get('/users/view/{id}', fn ($id) => redirect()->route('staff.view', ['id' => $id]))->name('users.view');

// Customer routes removed - legacy travel system feature
// Company client creation routes removed - feature deleted

// Legacy redirect for backward compatibility (named so route('userrole.*') works)
Route::get('/userrole', fn () => redirect()->route('staffrole.index'))->name('userrole.index');
Route::get('/userrole/create', fn () => redirect()->route('staffrole.create'))->name('userrole.create');
Route::get('/userrole/edit/{id}', fn ($id) => redirect()->route('staffrole.edit', ['id' => $id]))->name('userrole.edit');
Route::get('/staffrole', [StaffroleController::class, 'index'])->name('staffrole.index');
Route::get('/staffrole/create', [StaffroleController::class, 'create'])->name('staffrole.create');
Route::post('/staffrole/store', [StaffroleController::class, 'store'])->name('staffrole.store');
Route::get('/staffrole/edit/{id}', [StaffroleController::class, 'edit'])->name('staffrole.edit');
Route::post('/staffrole/edit', [StaffroleController::class, 'edit'])->name('staffrole.update');

// Leads Start - Updated to modern syntax
// Note: leads.detail (+ application) live in routes/clients.php under auth:admin (avoid duplicate registration).
// Route-level auth:admin + LeadController constructor middleware (defense in depth; same paths/names).
Route::middleware(['auth:admin'])->group(function () {
	Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
	Route::get('/leads/export-list', [LeadController::class, 'exportList'])->name('leads.export-list');
	Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create');
	Route::post('/leads/store', [LeadController::class, 'store'])->name('leads.store');
	Route::post('/leads/assign', [LeadController::class, 'assign'])->name('leads.assign');
	Route::post('/leads/import', [LeadController::class, 'import'])->name('leads.import');
	Route::get('/leads/convert/{id?}', [LeadController::class, 'convertoClient'])->name('leads.convert');
});
// Invoices Start

// Removed routes for deleted views: lists, email, invoicebyid, history, reminder
// Route::get('/invoice/lists/{id}', [InvoiceController::class, 'lists'])->name('invoice.lists');
Route::get('/invoice/edit/{id}', [InvoiceController::class, 'edit'])->name('invoice.edit');
Route::post('/invoice/edit', [InvoiceController::class, 'edit'])->name('invoice.update');
Route::get('/invoice/create', [InvoiceController::class, 'create'])->name('invoice.create');
Route::post('/invoice/store', [InvoiceController::class, 'store'])->name('invoice.store');
Route::get('/invoice/detail', [InvoiceController::class, 'detail'])->name('invoice.detail');
// Route::get('/invoice/email/{id}', [InvoiceController::class, 'email'])->name('invoice.email');
// Route::post('/invoice/email', [InvoiceController::class, 'email']);
Route::get('/invoice/editpayment', [InvoiceController::class, 'editpayment'])->name('invoice.editpayment');
// Route::get('/invoice/invoicebyid', [InvoiceController::class, 'invoicebyid'])->name('invoice.invoicebyid');
// Route::get('/invoice/history', [InvoiceController::class, 'history'])->name('invoice.history');
Route::post('/invoice/paymentsave', [InvoiceController::class, 'paymentsave'])->name('invoice.paymentsave');
Route::post('/invoice/editpaymentsave', [InvoiceController::class, 'editpaymentsave'])->name('invoice.editpaymentsave');
Route::post('/invoice/addcomment', [InvoiceController::class, 'addcomment'])->name('invoice.addcomment');
Route::post('/invoice/sharelink', [InvoiceController::class, 'sharelink'])->name('invoice.sharelink');
Route::post('/invoice/disablelink', [InvoiceController::class, 'disablelink'])->name('invoice.disablelink');
Route::get('/invoice/download/{id}', [InvoiceController::class, 'customer_invoice_download'])->name('invoice.customer_invoice_download');
Route::get('/invoice/exportall', [InvoiceController::class, 'exportall'])->name('invoice.exportall');
Route::get('/invoice/printall', [InvoiceController::class, 'customer_invoice_printall'])->name('invoice.customer_invoice_printall');
Route::get('/invoice/print/{id}', [InvoiceController::class, 'customer_invoice_print'])->name('invoice.customer_invoice_print');
// Route::get('/invoice/reminder/{id}', [InvoiceController::class, 'reminder'])->name('invoice.reminder');
// Route::post('/invoice/reminder', [InvoiceController::class, 'reminder']);
Route::post('/invoice/attachfile', [InvoiceController::class, 'attachfile'])->name('invoice.attachfile');
Route::get('/invoice/getattachfile', [InvoiceController::class, 'getattachfile'])->name('invoice.getattachfile');
Route::get('/invoice/removeattachfile', [InvoiceController::class, 'removeattachfile'])->name('invoice.removeattachfile');
Route::get('/invoice/attachfileemail', [InvoiceController::class, 'attachfileemail'])->name('invoice.attachfileemail');
// Manage Api key
// Route::get('/api-key', 'Admin\ApiController@index')->name('apikey.index');
// Manage Api key

// SES verified senders + Outlook compose (Elite inbox + CRM compose modals)
Route::middleware(['auth:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/outlook/senders', [OutlookController::class, 'senders'])->name('outlook.senders');
    Route::post('/outlook/send', [OutlookController::class, 'send'])->name('outlook.send');
    Route::post('/outlook/draft', [OutlookController::class, 'saveDraft'])->name('outlook.saveDraft');
});

// Email Templates Pages
Route::get('/email_templates', [EmailTemplateController::class, 'index'])->name('email.index');
Route::get('/email_templates/create', [EmailTemplateController::class, 'create'])->name('email.create');
Route::post('/email_templates/store', [EmailTemplateController::class, 'store'])->name('email.store');
Route::get('/edit_email_template/{id}', [EmailTemplateController::class, 'editEmailTemplate'])->name('edit_email_template');
Route::post('/edit_email_template', [EmailTemplateController::class, 'editEmailTemplate'])->name('email.update');

Route::get('/api-key', [AdminController::class, 'editapi'])->name('edit_api');
Route::post('/api-key', [AdminController::class, 'editapi'])->name('edit_api.update');

// clients routes - moved to routes/clients.php (unified routes)
// Keep get-templates and sendmail here as they use AdminController (not in clients.php)
Route::get('/get-templates', [AdminController::class, 'gettemplates'])->name('clients.gettemplates');
Route::post('/sendmail', [AdminController::class, 'sendmail'])->name('clients.sendmail');

// products Start
Route::get('/products', [ProductsController::class, 'index'])->name('products.index');
Route::get('/products/create', [ProductsController::class, 'create'])->name('products.create');
Route::post('/products/store', [ProductsController::class, 'store'])->name('products.store');
Route::get('/products/edit/{id}', [ProductsController::class, 'edit'])->name('products.edit');
Route::post('/products/edit', [ProductsController::class, 'edit'])->name('products.update');

        Route::get('/products/detail/{id}/{tab?}', [ProductsController::class, 'detail'])->name('products.detail');
Route::get('/products/get-recipients', [ProductsController::class, 'getrecipients'])->name('products.getrecipients');
Route::get('/products/get-allclients', [ProductsController::class, 'getallclients'])->name('products.getallclients');

// Partner Start
Route::get('/partners', [PartnersController::class, 'index'])->name('partners.index');
Route::get('/partners/create', [PartnersController::class, 'create'])->name('partners.create');
Route::post('/partners/store', [PartnersController::class, 'store'])->name('partners.store');
Route::get('/partners/edit/{id}', [PartnersController::class, 'edit'])->name('partners.edit');
Route::post('/partners/edit', [PartnersController::class, 'edit'])->name('partners.update');
Route::get('/getpaymenttype', [PartnersController::class, 'getpaymenttype'])->name('partners.getpaymenttype');

Route::get('/partners/detail/{id}/{tab?}', [PartnersController::class, 'detail'])->name('partners.detail');
Route::get('/partners/get-recipients', [PartnersController::class, 'getrecipients'])->name('partners.getrecipients');
Route::get('/partners/get-allclients', [PartnersController::class, 'getallclients'])->name('partners.getallclients');

// Branch Start
Route::get('/branch', [BranchesController::class, 'index'])->name('branch.index');
Route::get('/branch/create', [BranchesController::class, 'create'])->name('branch.create');
Route::post('/branch/store', [BranchesController::class, 'store'])->name('branch.store');
Route::get('/branch/edit/{id}', [BranchesController::class, 'edit'])->name('branch.edit');
Route::get('/branch/view/{id}', [BranchesController::class, 'view'])->name('branch.userview');
Route::get('/branch/view/client/{id}', [BranchesController::class, 'viewclient'])->name('branch.clientview');
Route::post('/branch/edit', [BranchesController::class, 'edit'])->name('branch.update');

        // Agent Start
/* 	Route::get('/agents', 'Admin\AgentController@index')->name('agents.index');
    Route::get('/agents/create', 'Admin\AgentController@create')->name('agents.create');
    Route::post('/agents/store', 'Admin\AgentController@store')->name('agents.store');

    Route::post('/agents/edit', 'Admin\AgentController@edit')->name('agents.edit'); */

Route::get('/agents/active', [AgentController::class, 'active'])->name('agents.active');
Route::get('/agents/inactive', [AgentController::class, 'inactive'])->name('agents.inactive');
Route::get('/agents/show/{id}', [AgentController::class, 'show'])->name('agents.show');
Route::get('/agents/create', [AgentController::class, 'create'])->name('agents.create');
Route::get('/agents/import', [AgentController::class, 'import'])->name('agents.import');
Route::post('/agents/store', [AgentController::class, 'store'])->name('agents.store');
Route::get('/agent/detail/{id}', [AgentController::class, 'detail'])->name('agents.detail');
Route::get('/agents/edit/{id}', [AgentController::class, 'edit'])->name('agents.edit');
Route::post('/agents/edit', [AgentController::class, 'edit'])->name('agents.update');
Route::get('/agents/import/business', [AgentController::class, 'businessimport']);
Route::post('/agents/import/business', [AgentController::class, 'businessimport']);
Route::get('/agents/import/individual', [AgentController::class, 'individualimport']);
Route::post('/agents/import/individual', [AgentController::class, 'individualimport']);
// Task System Removed - Database tables preserved (tasks, task_logs, to_do_groups)
// Removed on: December 2025 - System was inactive for 16+ months

// General Invoice Start
Route::get('/invoice/general-invoice', [InvoiceController::class, 'general_invoice'])->name('invoice.general-invoice');

// Interested services removed - create applications directly
// Route::post('/interested-service', [ClientServiceController::class, 'interestedService']);
// Route::post('/edit-interested-service', [ClientServiceController::class, 'editinterestedService']);
// Route::get('/get-services', [ClientServiceController::class, 'getServices']);
Route::post('/upload-mail', [ClientMessagingController::class, 'uploadmail']);
Route::post('/mail/enhance', [ClientMessagingController::class, 'enhanceMessage'])->name('mail.enhance');
Route::post('/tinymce/upload-image', [TinyMCEImageUploadController::class, 'upload'])->name('tinymce.upload-image');

Route::get('/pinnote', [ClientNoteController::class, 'pinnote']);
Route::get('/pinactivitylog', [ClientActivityController::class, 'pinactivitylog']);

// Route::get('/getintrestedservice', [ClientServiceController::class, 'getintrestedservice']);
// Route::post('/application/saleforcastservice', [ClientServiceController::class, 'saleforcastservice']);
// Route::get('/getintrestedserviceedit', [ClientServiceController::class, 'getintrestedserviceedit']);
Route::post('/create-invoice', [InvoiceController::class, 'createInvoice']);
Route::get('/application/invoice/{client_id}/{application}/{invoice_type}', [InvoiceController::class, 'getInvoice']);
Route::get('/invoice/view/{id}', [InvoiceController::class, 'show']);
Route::get('/invoice/preview/{id}', [InvoiceController::class, 'getinvoicespdf']);
Route::get('/invoice/edit/{id}', [InvoiceController::class, 'edit']);
Route::post('/invoice/general-store', [InvoiceController::class, 'generalStore']);
Route::get('/invoice/delete-payment', [InvoiceController::class, 'deletepayment']);
Route::post('/invoice/payment-store', [InvoiceController::class, 'invoicepaymentstore']);
Route::get('/get-invoices', [InvoiceController::class, 'getinvoices']);
Route::get('/get-invoices-pdf', [InvoiceController::class, 'getinvoicespdf']);
Route::get('/delete-invoice', [InvoiceController::class, 'deleteinvoice']);
Route::post('/invoice/general-edit', [InvoiceController::class, 'updategeninvoices']);
Route::post('/invoice/com-store', [InvoiceController::class, 'updatecominvoices']);
Route::get('/invoice/paid', [InvoiceController::class, 'paid'])->name('invoice.paid');
Route::get('/invoice/paid/export', [InvoiceController::class, 'exportPaidInvoices'])->name('invoice.exportPaid');
Route::get('/invoice/unpaid', [InvoiceController::class, 'unpaid'])->name('invoice.unpaid');
// Route::get('/invoice/unpaid', [InvoiceController::class, 'unpaid'])->name('invoice.unpaid');
Route::get('/invoice/', [InvoiceController::class, 'index'])->name('invoice.index');
Route::get('/payment/view/{id}', [AccountController::class, 'preview']);

    Route::get('/getapplicationdetail', [ApplicationsController::class, 'getapplicationdetail']);
Route::get('/updatestage', [ApplicationsController::class, 'updatestage']);
Route::get('/completestage', [ApplicationsController::class, 'completestage']);
Route::get('/updatebackstage', [ApplicationsController::class, 'updatebackstage']);
Route::get('/get-applications-logs', [ApplicationsController::class, 'getapplicationslogs']);
Route::get('/get-applications', [ApplicationsController::class, 'getapplications']);
Route::post('/create-app-note', [ApplicationsController::class, 'addNote']);
Route::get('/getapplicationnotes', [ApplicationsController::class, 'getapplicationnotes']);
Route::post('/application-sendmail', [ApplicationsController::class, 'applicationsendmail']);
Route::get('/application/updateintake', [ApplicationsController::class, 'updateintake']);
Route::post('/application/update-enrolment-type', [ApplicationsController::class, 'updateEnrolmentType'])->name('application.updateEnrolmentType');
Route::post('/application/update-company-name', [ApplicationsController::class, 'updateCompanyName'])->name('application.updateCompanyName');
Route::get('/application/updatedates', [ApplicationsController::class, 'updatedates']);
Route::get('/application/updateexpectwin', [ApplicationsController::class, 'updateexpectwin']);
Route::get('/application/getapplicationbycid', [ApplicationsController::class, 'getapplicationbycid']);
Route::post('/application/spagent_application', [ApplicationsController::class, 'spagent_application']);
Route::post('/application/sbagent_application', [ApplicationsController::class, 'sbagent_application']);
Route::post('/application/application_ownership', [ApplicationsController::class, 'application_ownership']);
Route::post('/application/change-assignee', [ApplicationsController::class, 'changeApplicationAssignee'])->name('application.change-assignee');
Route::post('/application/saleforcast', [ApplicationsController::class, 'saleforcast']);
Route::get('/superagent', [ApplicationsController::class, 'superagent']);
Route::get('/subagent', [ApplicationsController::class, 'subagent']);
Route::get('/showproductfee', [ApplicationsController::class, 'showproductfee']);
Route::post('/applicationsavefee', [ApplicationsController::class, 'applicationsavefee']);
Route::get('/application/export/pdf/{id}', [ApplicationsController::class, 'exportapplicationpdf']);
Route::post('/add-checklists', [ApplicationsController::class, 'addchecklists']);
Route::post('/application/checklistupload', [ApplicationsController::class, 'checklistupload']);
Route::get('/deleteapplicationdocs', [ApplicationsController::class, 'deleteapplicationdocs']);
Route::get('/application/publishdoc', [ApplicationsController::class, 'publishdoc']);

// Account Start
Route::get('/payment', [AccountController::class, 'payment'])->name('account.payment');
Route::get('/income-sharing/payables/unpaid', [AccountController::class, 'payableunpaid'])->name('account.payableunpaid');
Route::get('/income-sharing/payables/paid', [AccountController::class, 'payablepaid'])->name('account.payablepaid');
Route::post('/income-payment-store', [AccountController::class, 'incomepaymentstore']);
Route::get('/revert-payment', [AccountController::class, 'revertpayment']);
Route::get('/income-sharing/receivables/unpaid', [AccountController::class, 'receivableunpaid'])->name('account.receivableunpaid');
Route::get('/income-sharing/receivables/paid', [AccountController::class, 'receivablepaid'])->name('account.receivablepaid');
Route::get('/group-invoice/unpaid', [InvoiceController::class, 'unpaidgroupinvoice'])->name('invoice.unpaidgroupinvoice');
Route::get('/group-invoice/paid', [InvoiceController::class, 'paidgroupinvoice'])->name('invoice.paidgroupinvoice');
Route::get('/group-invoice/create', [InvoiceController::class, 'creategroupinvoice'])->name('invoice.creategroupinvoice');
// NOTE: Invoice Schedule routes removed - Invoice Schedule feature has been removed

// NOTE: Feature configuration routes (Product Type, Partner Type, Visa Type, etc.) have been moved to routes/adminconsole.php
// Those routes now use the AdminConsole namespace and are accessible at /adminconsole/* paths
// The duplicate routes that were here (lines 436-532) have been removed to prevent conflicts and errors

Route::post('/partner/saveagreement', [PartnersController::class, 'saveagreement']);

// New multiple agreements routes
Route::post('/partner/agreement/store', [PartnersController::class, 'storePartnerAgreement']);
Route::get('/partner/agreements/list', [PartnersController::class, 'getPartnerAgreements']);
Route::get('/partner/agreement/get', [PartnersController::class, 'getPartnerAgreement']);
Route::post('/partner/agreement/delete', [PartnersController::class, 'deletePartnerAgreement']);
Route::post('/partner/agreement/set-active', [PartnersController::class, 'setActiveAgreement']);

Route::post('/partner/create-branch', [PartnersController::class, 'createbranch']);
Route::get('/get-branches', [PartnersController::class, 'getbranch']);
Route::get('/getbranchdetail', [PartnersController::class, 'getbranchdetail']);
Route::get('/deletebranch', [PartnersController::class, 'deletebranch']);

Route::post('/saveotherinfo', [ProductsController::class, 'saveotherinfo']);
Route::get('/product/getotherinfo', [ProductsController::class, 'getotherinfo']);
Route::get('/get-all-fees', [ProductsController::class, 'getallfees']);
Route::post('/savefee', [ProductsController::class, 'savefee']);

Route::get('/getfeeoptionedit', [ProductsController::class, 'editfee']);
Route::post('/editfee', [ProductsController::class, 'editfeeform']);
Route::get('/deletefee', [ProductsController::class, 'deletefee']);

        // Task system removed - December 2025
// Route::post('/partner/addtask', [PartnersController::class, 'addtask']);
// Route::get('/partner/get-tasks', [PartnersController::class, 'gettasks']);
// Route::get('/partner/get-task-detail', [PartnersController::class, 'taskdetail']);
// Route::post('/partner/savecomment', [PartnersController::class, 'savecomment']);
// Task system removed - December 2025
// Route::get('/change-task-status', [PartnersController::class, 'changetaskstatus']);
// Route::get('/change-task-priority', [PartnersController::class, 'changetaskpriority']);

Route::post('/promotion/store', [PromotionController::class, 'store']);
Route::post('/promotion/edit', [PromotionController::class, 'edit'])->name('promotion.update');
Route::get('/get-promotions', [PromotionController::class, 'getpromotions']);
Route::get('/getpromotioneditform', [PromotionController::class, 'getpromotioneditform']);
Route::get('/change-promotion-status', [PromotionController::class, 'changepromotionstatus']);

        // Applications Start
Route::get('/applications', [ApplicationsController::class, 'index'])->name('applications.index');
Route::get('/applications/create', [ApplicationsController::class, 'create'])->name('applications.create');
Route::post('/discontinue_application', [ApplicationsController::class, 'discontinue_application']);
Route::post('/revert_application', [ApplicationsController::class, 'revert_application']);
// Route::post('/product-type/store', [ProductTypeController::class, 'store'])->name('feature.producttype.store');
// Route::get('/product-type/edit/{id}', [ProductTypeController::class, 'edit'])->name('feature.producttype.edit');
// Route::post('/product-type/edit', [ProductTypeController::class, 'edit'])->name('feature.producttype.edit');
Route::get('/office-visits', fn () => redirect()->route('officevisits.waiting'))->name('officevisits.index');
Route::get('/office-visits/waiting', [OfficeVisitController::class, 'waiting'])->name('officevisits.waiting');
Route::get('/office-visits/attending', [OfficeVisitController::class, 'attending'])->name('officevisits.attending');
Route::get('/office-visits/completed', [OfficeVisitController::class, 'completed'])->name('officevisits.completed');
Route::get('/office-visits/create', [OfficeVisitController::class, 'create'])->name('officevisits.create');
Route::post('/checkin', [OfficeVisitController::class, 'checkin']);
Route::get('/get-checkin-detail', [OfficeVisitController::class, 'getcheckin']);
Route::post('/update_visit_purpose', [OfficeVisitController::class, 'update_visit_purpose']);
Route::post('/update_visit_comment', [OfficeVisitController::class, 'update_visit_comment']);
Route::post('/attend_session', [OfficeVisitController::class, 'attend_session']);
Route::post('/complete_session', [OfficeVisitController::class, 'complete_session']);
Route::get('/office-visits/change_assignee', [OfficeVisitController::class, 'change_assignee']);
Route::get('/fetch-office-visit-notifications', [OfficeVisitController::class, 'fetchOfficeVisitNotifications'])->name('officevisits.fetch-notifications');
Route::get('/check-checkin-status', [OfficeVisitController::class, 'checkCheckinStatus'])->name('officevisits.check-checkin-status');
Route::post('/mark-notification-seen', [OfficeVisitController::class, 'markNotificationSeen'])->name('officevisits.mark-notification-seen');
Route::post('/update-checkin-status', [OfficeVisitController::class, 'updateCheckinStatus'])->name('officevisits.update-checkin-status');
// Route::post('/agents/store', 'Admin\AgentController@store')->name('agents.store');
// Route::get('/agent/detail/{id}', 'Admin\AgentController@detail')->name('agents.detail');

// Enquiries/Queries routes removed - feature not in use
// Route::get('/enquiries', 'Admin\EnquireController@index')->name('enquiries.index');
// Route::get('/enquiries/archived-enquiries', 'Admin\EnquireController@Archived')->name('enquiries.archived');
// Route::get('/enquiries/covertenquiry/{id}', 'Admin\EnquireController@covertenquiry');
// Route::get('/enquiries/archived/{id}', 'Admin\EnquireController@archivedenquiry');

// Audit Logs Start
Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('auditlogs.index');
Route::get('/audit-logs/export', [AuditLogController::class, 'exportCsv'])->name('auditlogs.export');

// Reports Start
Route::get('/report/client', [ReportController::class, 'client'])->name('reports.client');
Route::get('/report/application', [ReportController::class, 'application'])->name('reports.application');
Route::get('/report/invoice', [ReportController::class, 'invoice'])->name('reports.invoice');
Route::get('/report/office-visit', [ReportController::class, 'office_visit'])->name('reports.office-visit');
Route::get('/report/sale-forecast/application', [ReportController::class, 'saleforecast_application'])->name('reports.saleforecast-application');
// Route::get('/report/sale-forecast/interested-service', [ReportController::class, 'interested_service'])->name('reports.interested-service');
// Task system reports removed - December 2025
// Route::get('/report/task/personal-task-report', [ReportController::class, 'personal_task'])->name('reports.personal-task-report');
// Route::get('/report/task/office-task-report', [ReportController::class, 'office_task'])->name('reports.office-task-report');
Route::get('/reports/visaexpires', [ReportController::class, 'visaexpires']);
Route::get('/action-calendar', [ReportController::class, 'actionCalendar']);
Route::get('/reports/agreementexpires', [ReportController::class, 'agreementexpires']);
Route::get('/report/noofpersonofficevisit', [ReportController::class, 'noofpersonofficevisit'])->name('reports.noofpersonofficevisit');

// NOTE: Email and CRM Email Template routes have been moved to routes/adminconsole.php
// Those routes now use the AdminConsole namespace and are accessible at /adminconsole/* paths
// The duplicate routes that were here (lines 626-637) have been removed to prevent conflicts and errors

Route::get('/fetch-notification', [AdminController::class, 'fetchnotification']);
Route::get('/fetch-messages', [AdminController::class, 'fetchmessages']);
Route::post('/notifications/mark-toast-seen', [AdminController::class, 'markToastMessageSeen'])->name('notifications.mark-toast-seen');
Route::get('/upload-checklists', [UploadChecklistController::class, 'index'])->name('upload_checklists.index');
Route::post('/upload-checklists/store', [UploadChecklistController::class, 'store'])->name('upload_checklistsupload');
Route::get('/upload-checklists/edit/{id}', [UploadChecklistController::class, 'edit'])->name('upload_checklists.edit');
Route::post('/upload-checklists/edit/{id}', [UploadChecklistController::class, 'update'])->name('upload_checklists.update');
Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
Route::get('/teams/edit/{id}', [TeamController::class, 'edit'])->name('teams.edit');
Route::post('/teams/edit', [TeamController::class, 'edit'])->name('teams.update');
Route::post('/teams/store', [TeamController::class, 'store'])->name('teamsupload');
Route::get('/all-notifications', [AdminController::class, 'allnotification'])->name('notifications.index');
Route::post('/notifications/mark-read', [AdminController::class, 'markNotificationAsRead'])->name('notifications.mark-read');
Route::post('/notifications/mark-all-read', [AdminController::class, 'markAllNotificationsAsRead'])->name('notifications.mark-all-read');

// Action module
Route::get('/action', [ActionController::class, 'index'])->name('action.index'); // Main Action page
Route::get('/action/list', [ActionController::class, 'getList'])->name('action.list'); // DataTable data
Route::get('/action/completed', [ActionController::class, 'completed'])->name('action.completed'); // completed actions
Route::get('/action/assigned-by-me', [ActionController::class, 'assignedByMe'])->name('action.assigned_by_me'); // assigned by me
Route::get('/action/assigned-to-me', [ActionController::class, 'assignedToMe'])->name('action.assigned_to_me'); // assigned to me

Route::post('/action/task-complete', [ActionController::class, 'markComplete']); // update task to be completed
Route::post('/action/task-incomplete', [ActionController::class, 'markIncomplete']); // update task to be not completed
Route::get('/action/get-note-data', [ActionController::class, 'getNoteData']); // get note data for completion modal

Route::delete('/action/destroy-by-me/{note_id}', [ActionController::class, 'destroyByMe'])->name('action.destroy_by_me'); // delete assigned by me
Route::delete('/action/destroy-to-me/{note_id}', [ActionController::class, 'destroyToMe'])->name('action.destroy_to_me'); // delete assigned to me
Route::delete('/action/destroy/{note_id}', [ActionController::class, 'destroy'])->name('action.destroy'); // delete action
Route::delete('/action/destroy-completed/{note_id}', [ActionController::class, 'destroyCompleted'])->name('action.destroy_completed'); // delete completed action

// Assignee list moved to StaffController::getAssigneeList

// Total person waiting and total activity counter
Route::get('/fetch-InPersonWaitingCount', [AdminController::class, 'fetchInPersonWaitingCount']);
Route::get('/fetch-TotalActivityCount', [AdminController::class, 'fetchTotalActivityCount']);
// For email and contact number uniqueness
Route::post('/is_email_unique', [LeadController::class, 'is_email_unique']);
Route::post('/is_contactno_unique', [LeadController::class, 'is_contactno_unique']);

// Client routes moved to routes/clients.php (unified routes)

Route::post('/application/updateStudentId', [ApplicationsController::class, 'updateStudentId']);

Route::get('/showproductfeelatest', [ApplicationsController::class, 'showproductfeelatest']);
Route::post('/applicationsavefeelatest', [ApplicationsController::class, 'applicationsavefeelatest']);

// NOTE: Document Checklist routes have been moved to routes/adminconsole.php
// Those routes now use the AdminConsole namespace and are accessible at /adminconsole/* paths
// The duplicate routes that were here (lines 686-690) have been removed to prevent conflicts and errors

// Client document routes moved to routes/clients.php (unified routes)

// inactive partners
Route::get('/partners-inactive', [PartnersController::class, 'inactivePartnerList'])->name('partners.inactive');
Route::post('/partner_change_to_inactive', [AdminController::class, 'partnerChangeToInactive']);
Route::post('/partner_change_to_active', [AdminController::class, 'partnerChangeToActive']);

  // Partner Student Invoice
Route::get('/partners/savepartnerstudentinvoice/{id}', [PartnersController::class, 'savepartnerstudentinvoice'])->name('partners.savepartnerstudentinvoice');
Route::post('/partners/savepartnerstudentinvoice', [PartnersController::class, 'savepartnerstudentinvoice'])->name('partners.savepartnerstudentinvoice.update');
Route::post('/partners/getTopReceiptValInDB', [PartnersController::class, 'getTopReceiptValInDB'])->name('partners.getTopReceiptValInDB');
Route::post('/partners/getEnrolledStudentList', [PartnersController::class, 'getEnrolledStudentList'])->name('partners.getEnrolledStudentList');

// Partner Student Record Invoice
Route::get('/partners/savepartnerrecordinvoice/{id}', [PartnersController::class, 'savepartnerrecordinvoice'])->name('partners.savepartnerrecordinvoice');
Route::post('/partners/savepartnerrecordinvoice', [PartnersController::class, 'savepartnerrecordinvoice'])->name('partners.savepartnerrecordinvoice.update');

// Partner Student Record payment
Route::get('/partners/savepartnerrecordpayment/{id}', [PartnersController::class, 'savepartnerrecordpayment'])->name('partners.savepartnerrecordpayment');
Route::post('/partners/savepartnerrecordpayment', [PartnersController::class, 'savepartnerrecordpayment'])->name('partners.savepartnerrecordpayment.update');
Route::post('/partners/getRecordedInvoiceList', [PartnersController::class, 'getRecordedInvoiceList'])->name('partners.getRecordedInvoiceList');
// update student status
Route::post('/partners/update-student-status', [PartnersController::class, 'updateStudentStatus'])->name('partners.updateStudentStatus');

// get student info
Route::post('/partners/getStudentInfo', [PartnersController::class, 'getStudentInfo'])->name('partners.getStudentInfo');
Route::post('/partners/getStudentCourseInfo', [PartnersController::class, 'getStudentCourseInfo'])->name('partners.getStudentCourseInfo');
// Student tab AJAX data endpoint (partner_id query param preferred; {id} kept for legacy URLs)
Route::match(['get', 'post'], '/partners/getStudentTabData/{id?}', [PartnersController::class, 'getStudentTabData'])->name('partners.getStudentTabData');
Route::match(['get', 'post'], '/partners/getStudentTabCount/{id?}', [PartnersController::class, 'getStudentTabCount'])->name('partners.getStudentTabCount');
Route::match(['get', 'post'], '/partners/getStudentTabTotals/{id?}', [PartnersController::class, 'getStudentTabTotals'])->name('partners.getStudentTabTotals');
Route::get('/partners/exportStudentTabData/{id?}', [PartnersController::class, 'exportStudentTabData'])->name('partners.exportStudentTabData');
Route::get('/partners/getApplicationTabData/{id?}', [PartnersController::class, 'getApplicationTabData'])->name('partners.getApplicationTabData');
Route::get('/partners/getAccountsTabData/{id?}', [PartnersController::class, 'getAccountsTabData'])->name('partners.getAccountsTabData');
Route::get('/partners/exportAccountsTabData/{id?}', [PartnersController::class, 'exportAccountsTabData'])->name('partners.exportAccountsTabData');

Route::post('/partners/getTopInvoiceValInDB', [PartnersController::class, 'getTopInvoiceValInDB'])->name('partners.getTopInvoiceValInDB');
Route::get('/partners/printpreviewcreateinvoice/{id}', [PartnersController::class, 'printpreviewcreateinvoice']); // Create Student Invoice print preview

Route::post('/partners/updateInvoiceSentOptionToYes', [PartnersController::class, 'updateInvoiceSentOptionToYes'])->name('partners.updateInvoiceSentOptionToYes');
Route::post('/partners/getInfoByInvoiceId', [PartnersController::class, 'getInfoByInvoiceId'])->name('partners.getInfoByInvoiceId');

Route::post('/partners/getEnrolledStudentListInEditMode', [PartnersController::class, 'getEnrolledStudentListInEditMode'])->name('partners.getEnrolledStudentListInEditMode');

Route::post('/partners/deleteStudentRecordByInvoiceId', [PartnersController::class, 'deleteStudentRecordByInvoiceId'])->name('partners.deleteStudentRecordByInvoiceId');
Route::post('/partners/deleteStudentRecordInvoiceByInvoiceId', [PartnersController::class, 'deleteStudentRecordInvoiceByInvoiceId'])->name('partners.deleteStudentRecordInvoiceByInvoiceId');
Route::post('/partners/deleteStudentPaymentInvoiceByInvoiceId', [PartnersController::class, 'deleteStudentPaymentInvoiceByInvoiceId'])->name('partners.deleteStudentPaymentInvoiceByInvoiceId');

  // partner inbox and sent email
Route::post('/upload-partner-fetch-mail', [PartnersController::class, 'uploadpartnerfetchmail']);
Route::post('/upload-partner-sent-fetch-mail', [PartnersController::class, 'uploadpartnersentfetchmail']);

// Applications overdue
Route::get('/applications-overdue', [ApplicationsController::class, 'overdueApplicationList'])->name('applications.overdue');

// Applications finalize
Route::get('/applications-finalize', [ApplicationsController::class, 'finalizeApplicationList'])->name('applications.finalize');

// partner assign user
Route::post('/partners/action_partner/store_partner', [PartnersController::class, 'actionstore_partner']);
// Route::get('/get-partner-activities', [PartnersController::class, 'partnerActivities')->name('partners.activities');

// Client routes moved to routes/clients.php (unified routes): fetchClientContactNo

// Fetch all contact list of any partner at create note popup at partner detail page
Route::post('/partners/fetchPartnerContactNo', [PartnersController::class, 'fetchPartnerContactNo']);

// update student application overall status
Route::post('/partners/update-student-application-overall-status', [PartnersController::class, 'updateStudentApplicationOverallStatus'])->name('partners.updateStudentApplicationOverallStatus');

  // Add Note To Student
Route::post('/add-student-note', [PartnersController::class, 'addstudentnote'])->name('partners.addstudentnote');
// Fetch all partner activity logs
Route::get('/get-partner-activities', [PartnersController::class, 'activities'])->name('partners.activities');

  // Note deadline task complete
Route::post('/update-note-deadline-completed', [AdminController::class, 'updatenotedeadlinecompleted']);
// Note deadline extend
Route::post('/extenddeadlinedate', [AdminController::class, 'extenddeadlinedate']);

  // Application Refund
Route::post('/refund_application', [ApplicationsController::class, 'refund_application']);

// save student note
Route::post('/partners/save-student-note', [PartnersController::class, 'saveStudentNote'])->name('partners.saveStudentNote');
Route::post('/partners/save-student-enrolment-type', [PartnersController::class, 'saveStudentEnrolmentType'])->name('partners.saveStudentEnrolmentType');

// Get partner notes
Route::get('/get-partner-notes', [PartnersController::class, 'getPartnerNotes'])->name('partners.getPartnerNotes');

  // SMS and phone verification: now in AdminConsole (features.sms) and PhoneVerificationController (Phase 9)

// Client routes moved to routes/clients.php (unified routes): is_greview_mail_sent, mail/enhance, download-document

// partner document upload
Route::post('/upload-partner-document-upload', [PartnersController::class, 'uploadpartnerdocumentupload']);
Route::post('/partners/add-alldocchecklist', [PartnersController::class, 'addalldocchecklist'])->name('partners.addalldocchecklist');
Route::post('/partners/upload-alldocument', [PartnersController::class, 'uploadalldocument'])->name('partners.uploadalldocument');

// Include unified client routes (accessible by admin only)
// These routes use /clients/* instead of /admin/clients/*
require __DIR__.'/clients.php';

// Include document signature routes
require __DIR__.'/documents.php';

// Client edit form link in send email - REMOVED (HomeController deleted, will be recreated in future)
// Route::get('/verify-dob/{encoded_id}', 'HomeController@showDobForm');
// Route::post('/verify-dob', 'HomeController@verifyDob');
// Route::get('/editclient/{id}', 'HomeController@editclient')->name('editclient');
// Route::get('/editclient/{encoded_id}', 'HomeController@editClient')->middleware('checkDobSession');
// Route::post('/editclient', 'HomeController@editclient')->name('editclient');

// Route::get('/pr-points', 'PRPointsController@index')->name('pr-points.index');
// Route::post('/pr-points/calculate', 'PRPointsController@calculate')->name('pr-points.calculate');

// Frontend Website - Dynamic Pages Route (Commented out)
// Route::get('/{slug}', 'HomeController@Page')->name('page.slug');
// Auth::routes(); // Removed - already defined above

// Home route - REMOVED (HomeController deleted, will be recreated in future)
// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
