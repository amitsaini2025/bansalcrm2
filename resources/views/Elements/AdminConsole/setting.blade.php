<?php
		$roles = \App\Models\StaffRole::find(Auth::user()->role);
		$newarray = json_decode($roles->module_access);
		$module_access = (array) $newarray;
		$crmAccessService = app(\App\Services\CrmAccess\CrmAccessService::class);
		$canViewGrantsDashboard = auth('admin')->user() instanceof \App\Models\Staff
			&& $crmAccessService->isApprover(auth('admin')->user());
?>
<div class="custom_nav_setting">
    <ul>
        <?php
			if(Route::currentRouteName() == 'adminconsole.producttype.index' || Route::currentRouteName() == 'adminconsole.producttype.create' || Route::currentRouteName() == 'adminconsole.producttype.edit' || Route::currentRouteName() == 'adminconsole.visatype.index' || Route::currentRouteName() == 'adminconsole.visatype.create' || Route::currentRouteName() == 'adminconsole.visatype.edit' || Route::currentRouteName() == 'adminconsole.mastercategory.index' || Route::currentRouteName() == 'adminconsole.mastercategory.create' || Route::currentRouteName() == 'adminconsole.mastercategory.edit' || Route::currentRouteName() == 'adminconsole.leadservice.index' || Route::currentRouteName() == 'adminconsole.leadservice.create' || Route::currentRouteName() == 'adminconsole.leadservice.edit' || Route::currentRouteName() == 'adminconsole.source.index' || Route::currentRouteName() == 'adminconsole.source.create' || Route::currentRouteName() == 'adminconsole.source.edit' || Route::currentRouteName() == 'adminconsole.workflow.index' || Route::currentRouteName() == 'adminconsole.workflow.create' || Route::currentRouteName() == 'adminconsole.workflow.edit'){
				$addfeatureclasstype = 'active'; 
		}
		?>
		<li class="{{(Route::currentRouteName() == 'adminconsole.profiles.index' || Route::currentRouteName() == 'adminconsole.profiles.create' || Route::currentRouteName() == 'adminconsole.profiles.edit') ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.profiles.index')}}">Profiles</a></li> 	
		<li class="{{(Route::currentRouteName() == 'adminconsole.producttype.index' || Route::currentRouteName() == 'adminconsole.producttype.create' || Route::currentRouteName() == 'adminconsole.producttype.edit') ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.producttype.index')}}">Product Type</a></li>
		<li class="adminconsole-nav-section-title"><span>Followup</span></li>
		<li class="{{ Route::currentRouteName() === 'adminconsole.followups.calendar-settings.index' || Route::currentRouteName() === 'adminconsole.followups.calendar-settings.edit' ? 'active' : '' }}"><a class="nav-link adminconsole-sub-link" href="{{ route('adminconsole.followups.calendar-settings.index') }}">@icon('clock', 'solid', ['class' => 'me-1'])Calendar Setting</a></li>
		<li class="{{ is_string(Route::currentRouteName()) && str_starts_with(Route::currentRouteName(), 'adminconsole.followups.blocked-times') ? 'active' : '' }}"><a class="nav-link adminconsole-sub-link" href="{{ route('adminconsole.followups.blocked-times.index') }}">@icon('ban', 'solid', ['class' => 'me-1'])Calendar Blocked Time</a></li> 
		<li class="{{(Route::currentRouteName() == 'adminconsole.partnertype.index' || Route::currentRouteName() == 'adminconsole.partnertype.create' || Route::currentRouteName() == 'adminconsole.partnertype.edit') ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.partnertype.index')}}">Partner Type</a></li> 
		<li class="{{(Route::currentRouteName() == 'adminconsole.leadservice.index' || Route::currentRouteName() == 'adminconsole.leadservice.create' || Route::currentRouteName() == 'adminconsole.leadservice.edit' ) ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.leadservice.index')}}">Lead Service</a></li>
		<li class="{{(Route::currentRouteName() == 'adminconsole.mastercategory.index' || Route::currentRouteName() == 'adminconsole.mastercategory.create' || Route::currentRouteName() == 'adminconsole.mastercategory.edit' ) ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.mastercategory.index')}}">Master Category</a></li>
		<li class="{{(Route::currentRouteName() == 'adminconsole.visatype.index' || Route::currentRouteName() == 'adminconsole.visatype.create' || Route::currentRouteName() == 'adminconsole.visatype.edit' ) ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.visatype.index')}}">Visa Type</a></li>
		<!-- Subject Area menu removed - subject_areas table has been dropped -->
		<!-- Subject routes removed - NOTE: Subject routes have been removed from adminconsole.php -->
		<!-- Tax routes removed - NOTE: Tax routes have been removed from adminconsole.php -->
		<li class="{{(Route::currentRouteName() == 'adminconsole.source.index' || Route::currentRouteName() == 'adminconsole.source.create' || Route::currentRouteName() == 'adminconsole.source.edit') ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.source.index')}}">Source</a></li>
		<!-- Tags menu removed - tags work differently and don't need backend -->
		{{-- Checklist section in admin console commented out - route adminconsole.checklist.index no longer defined
		<li class="{{(Route::currentRouteName() == 'adminconsole.checklist.index' || Route::currentRouteName() == 'adminconsole.checklist.create' || Route::currentRouteName() == 'adminconsole.checklist.edit') ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.checklist.index')}}">Checklist</a></li>
		--}}
		<li class="{{(Route::currentRouteName() == 'adminconsole.workflow.index' || Route::currentRouteName() == 'adminconsole.workflow.create' || Route::currentRouteName() == 'adminconsole.workflow.edit') ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.workflow.index')}}">Workflow</a></li>
		<li class="{{(Route::currentRouteName() == 'adminconsole.emails.index' || Route::currentRouteName() == 'adminconsole.emails.create' || Route::currentRouteName() == 'adminconsole.emails.edit') ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.emails.index')}}">Email</a></li>
		<li class="{{(Route::currentRouteName() == 'adminconsole.crmemailtemplate.index' || Route::currentRouteName() == 'adminconsole.crmemailtemplate.create' || Route::currentRouteName() == 'adminconsole.crmemailtemplate.edit') ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.crmemailtemplate.index')}}">Crm Email Template</a></li> 
		<li class="{{(Route::currentRouteName() == 'adminconsole.emaillabels.index' || Route::currentRouteName() == 'adminconsole.emaillabels.create' || Route::currentRouteName() == 'adminconsole.emaillabels.edit') ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.emaillabels.index')}}">Email Labels</a></li>

		<li class="{{(str_starts_with(Route::currentRouteName() ?? '', 'adminconsole.features.sms')) ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.features.sms.dashboard')}}">SMS Management</a></li>
		@if($canViewGrantsDashboard)
		<li class="{{(Route::currentRouteName() == 'crm.access.dashboard') ? 'active' : ''}}"><a class="nav-link" href="{{ route('crm.access.dashboard') }}">Grants Dashboard</a></li>
		@endif
		
		<li class="{{(Route::currentRouteName() == 'adminconsole.recentclients.index') ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.recentclients.index')}}">Recently Modified Clients</a></li>
		@if((Auth::user()->role ?? null) == 1)
		<li class="{{ str_starts_with(Route::currentRouteName() ?? '', 'adminconsole.staff-workload') ? 'active' : '' }}"><a class="nav-link" href="{{ route('adminconsole.staff-workload.index') }}">Staff Workload Today</a></li>
		@endif
		
		<?php
			if(Route::currentRouteName() == 'adminconsole.branch.index' || Route::currentRouteName() == 'adminconsole.branch.create' || Route::currentRouteName() == 'adminconsole.branch.edit' || Route::currentRouteName() == 'adminconsole.branch.userview' || Route::currentRouteName() == 'adminconsole.branch.clientview' || Route::currentRouteName() == 'adminconsole.staff.active' || Route::currentRouteName() == 'adminconsole.staff.inactive' || Route::currentRouteName() == 'adminconsole.staffrole.index' || Route::currentRouteName() == 'adminconsole.staffrole.create' || Route::currentRouteName() == 'adminconsole.staffrole.edit' || Route::currentRouteName() == 'adminconsole.teams.index' || Route::currentRouteName() == 'adminconsole.teams.edit' || Route::currentRouteName() == 'adminconsole.upload_checklists.index'){ 
				$teamclasstype = 'active';
			}  
		?> 
			<?php
			if(array_key_exists('1',  $module_access)) {
			?>
			<li class="{{(Route::currentRouteName() == 'adminconsole.branch.index' || Route::currentRouteName() == 'adminconsole.branch.create' || Route::currentRouteName() == 'adminconsole.branch.edit' || Route::currentRouteName() == 'adminconsole.branch.userview' || Route::currentRouteName() == 'adminconsole.branch.clientview') ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.branch.index')}}">Branches</a></li> 
			<?php } ?>
			<?php
			if(array_key_exists('4',  $module_access)) {
			?>
			<li class="{{(Route::currentRouteName() == 'adminconsole.staff.active' || Route::currentRouteName() == 'adminconsole.staff.inactive') ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.staff.active')}}">Staff</a></li>
			<li class="{{(Route::currentRouteName() == 'adminconsole.teams.index' || Route::currentRouteName() == 'adminconsole.teams.edit') ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.teams.index')}}">Teams</a></li>
			<?php } ?>
			<?php
			if(array_key_exists('6',  $module_access)) {
			?>
			<li class="{{(Route::currentRouteName() == 'adminconsole.staffrole.index' || Route::currentRouteName() == 'adminconsole.staffrole.create' || Route::currentRouteName() == 'adminconsole.staffrole.edit') ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.staffrole.index')}}">Roles</a></li>
			<?php } ?>
			<li class="{{(Route::currentRouteName() == 'adminconsole.upload_checklists.index' ) ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.upload_checklists.index')}}">Upload Checklists</a></li>
            
            <!--<li class="{{--(Route::currentRouteName() == 'admin.feature.appointmentdisabledate.index' ) ? 'active' : ''--}}"><a class="nav-link" href="{{--route('admin.feature.appointmentdisabledate.index')--}}">Block Slot</a></li>-->
      
           <li class="{{(Route::currentRouteName() == 'adminconsole.documentchecklist.index' ) ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.documentchecklist.index')}}">Document Checklist</a></li>

           <li class="{{(Route::currentRouteName() == 'adminconsole.documentcategory.index' || Route::currentRouteName() == 'adminconsole.documentcategory.create' || Route::currentRouteName() == 'adminconsole.documentcategory.edit') ? 'active' : ''}}"><a class="nav-link" href="{{route('adminconsole.documentcategory.index')}}">Document Category</a></li>

    </ul>
</div>
	