<div class="main-sidebar sidebar-style-2">
	<aside id="sidebar-wrapper">
		<div class="sidebar-brand">
			<a href="#">
				<img alt="Bansal CRM" src="{{ asset('img/logo.png') }}" class="header-logo" />
				<!--<span class="logo-name"></span>-->
			</a>
		</div>
		<ul class="sidebar-menu">
		<?php
		
		$roles = \App\Models\StaffRole::find(Auth::user()->role);
		$newarray = json_decode($roles->module_access);
	
		$module_access = (array) $newarray;
		?>
			<li class="menu-header">Main</li>
			<?php
			if(Route::currentRouteName() == 'dashboard'){
				$dashclasstype = 'active';
			}
			?> 
			<li class="dropdown {{@$dashclasstype}}">
				<a href="{{route('dashboard')}}" class="nav-link">@icon('desktop')<span>Dashboard</span></a>
			</li>

			<?php
			$followupMenuActive = Route::currentRouteName() === 'followups.index'
				|| Route::currentRouteName() === 'followups.calendar';
			?>
			<li class="dropdown {{ $followupMenuActive ? 'active' : '' }}">
				<a href="#" class="menu-toggle nav-link has-dropdown">@icon('calendar-check')<span>Followup</span></a>
				<ul class="dropdown-menu">
					<li class="{{ Route::currentRouteName() === 'followups.index' ? 'active' : '' }}">
						<a href="{{ route('followups.index') }}" class="nav-link">@icon('list-ul')<span>Listing</span></a>
					</li>
					@php
						$sidebarFollowupConsultants = \Illuminate\Support\Facades\Schema::hasTable('followup_consultants')
							? \App\Models\FollowupConsultant::activeOrdered()
							: collect();
						if ($sidebarFollowupConsultants->isEmpty()) {
							$sidebarFollowupConsultants = collect([
								(object) ['slug' => 'ankit', 'name' => 'Ankit'],
								(object) ['slug' => 'rakshita', 'name' => 'Rakshita'],
								(object) ['slug' => 'jaspreet', 'name' => 'Jaspreet'],
								(object) ['slug' => 'syed', 'name' => 'Syed'],
							]);
						}
					@endphp
					@foreach ($sidebarFollowupConsultants as $sidebarConsultant)
						@php
							$sidebarSlug = (string) ($sidebarConsultant->slug ?? '');
							$sidebarLabel = \App\Models\FollowupConsultant::shortLabelFromName(
								(string) ($sidebarConsultant->name ?? ''),
								$sidebarSlug
							);
						@endphp
						@if ($sidebarSlug !== '')
							<li class="{{ Route::currentRouteName() === 'followups.calendar' && request()->route('consultant') === $sidebarSlug ? 'active' : '' }}">
								<a href="{{ route('followups.calendar', ['consultant' => $sidebarSlug]) }}" class="nav-link">@icon('calendar-alt', 'regular')<span>{{ $sidebarLabel }}</span></a>
							</li>
						@endif
					@endforeach
				</ul>
			</li>
            
				<?php
			if(Route::currentRouteName() == 'leads.index' || Route::currentRouteName() == 'leads.create' || Route::currentRouteName() == 'leads.detail' || Route::currentRouteName() == 'leads.history'){
				$leadstype = 'active'; 
			}
			?>
		<!-- LEAD & PROSPECT MANAGEMENT -->
		<li class="dropdown {{@$leadstype}}">
			<a href="{{route('leads.index')}}" class="nav-link">@icon('user')<span>Lead Manager</span></a>
		</li>

			<?php
			if( Route::currentRouteName() == 'action.index' || Route::currentRouteName() == 'action.completed' ){
				$assigneetype = 'active';
			}
			
			if(\Auth::user()->role == 1){
                //$assigneesCount = \App\Models\Note::where('type','client')->whereNotNull('client_id')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();
               // $assigneesCount = \App\Models\Note::where('type','client')->whereNotNull('client_id')->where('is_action',1)->where('status',0)->count();
              
                  $assigneesCount = \App\Models\Note::whereIn('type', ['client', 'partner'])->whereNotNull('client_id')->where('is_action',1)->where('status','<>','1')->count();
            }else{
                //$assigneesCount = \App\Models\Note::where('assigned_to',Auth::user()->id)->where('type','client')->where('is_action',1)->where('status',0)->orderBy('created_at', 'desc')->count();
                //$assigneesCount = \App\Models\Note::where('assigned_to',Auth::user()->id)->where('type','client')->where('is_action',1)->where('status',0)->count();
              
                 $assigneesCount = \App\Models\Note::where('assigned_to',Auth::user()->id)->whereIn('type', ['client', 'partner'])->where('is_action',1)->where('status','<>','1')->count();
           
            }
			?>
            <li class="dropdown {{@$assigneetype}}">
				<a href="{{route('action.index')}}" class="nav-link">
                  @icon('check')
                  <span>Action
                    <span class="countTotalActivityAction" style="background: #0066cc;padding: 0px 5px;border-radius: 50%;color: #ffffff !important;margin-left: 5px;">{{ $assigneesCount }}</span>
                  </span>
              </a>
			</li>

			<!--<li class="dropdown {{-- @$assigneetype --}}"><!--
				<a href="#" class="menu-toggle nav-link has-dropdown">
                    @icon('check')<span>Activity   <span class="countAction" style="background: #1f1655;
					padding: 0px 5px;
					border-radius: 50%;
					color: #fff;">{{-- $assigneesCount --}}</span></span></a>

                <!--<ul class="dropdown-menu">-->
                    <?php //echo "@@@".Route::currentRouteName();?>
                    <!--<li class="{{-- (Route::currentRouteName()=='assignee.index')?'active':'' --}}">
                        <a class="nav-link" href="{{-- URL::to('/assignee') --}}">Assignee</a>
                    </li>-->
                     <!--<li class="{{-- (Route::currentRouteName()=='assignee.assigned_by_me')?'active':'' --}}">
                        <a class="nav-link" id="assigned_by_me"  href="{{URL::to('/assigned_by_me')}}">Assigned by me</a>
                    </li>
                    <li class="{{-- (Route::currentRouteName()=='assignee.assigned_to_me')?'active':'' --}}">
                        <a class="nav-link" id="assigned_to_me" href="{{URL::to('/assigned_to_me')}}">Assigned to me</a>
                    </li>-->
                 <!--</ul>
			</li>-->

			
          <?php
            
			if(Route::currentRouteName() == 'officevisits.waiting' || Route::currentRouteName() == 'officevisits.attending' || Route::currentRouteName() == 'officevisits.completed'){
				$checlasstype = 'active'; 
			}
			 //if(\Auth::user()->role == 1){
                $InPersonwaitingCount = \App\Models\CheckinLog::where('status',0)->count();
            /*}else{
                $InPersonwaitingCount = \App\Models\CheckinLog::where('user_id',Auth::user()->id)->where('status',0)->orderBy('created_at', 'desc')->count();
            }*/
			?>
			<li class="dropdown {{@$checlasstype}}">
				<a href="{{route('officevisits.waiting')}}" class="nav-link">@icon('check-circle')<span>In Person<span class="countInPersonWaitingAction" style="background: #0066cc;
                    padding: 0px 5px;border-radius: 50%;color: #ffffff !important;margin-left: 5px;">{{ $InPersonwaitingCount }}</span></span></a>
			</li>

			<!-- PEOPLE MANAGEMENT -->
			<?php
			
			if(Route::currentRouteName() == 'clients.index' || Route::currentRouteName() == 'clients.create' || Route::currentRouteName() == 'clients.edit' || Route::currentRouteName() == 'clients.detail'){
				$clientclasstype = 'active'; 
			}
			?> 
			<?php
				if(array_key_exists('21',  $module_access)) {
			?>
		<li class="dropdown {{@$clientclasstype}}">
			<a href="{{route('clients.index')}}" class="nav-link">@icon('user')<span>Clients Manager</span></a>
		</li>
		<?php
		}
	?>

		<?php
		$sheetsRouteNames = ['clients.sheets.ongoing', 'clients.sheets.coe-enrolled', 'clients.sheets.discontinue', 'clients.sheets.refund', 'clients.sheets.checklist', 'clients.sheets.insights'];
		if(in_array(Route::currentRouteName(), $sheetsRouteNames)){
			$sheetsclasstype = 'active';
		}
		?>
		<li class="dropdown {{@$sheetsclasstype}}">
			<a href="#" class="menu-toggle nav-link has-dropdown">@icon('clipboard-list')<span>Sheets</span></a>
			<ul class="dropdown-menu">
				<li class="{{ Route::currentRouteName() == 'clients.sheets.ongoing' ? 'active' : '' }}">
					<a class="nav-link" href="{{ route('clients.sheets.ongoing') }}">
						@icon('list')<span>Ongoing Sheet</span>
					</a>
				</li>
				<li class="{{ Route::currentRouteName() == 'clients.sheets.coe-enrolled' ? 'active' : '' }}">
					<a class="nav-link" href="{{ route('clients.sheets.coe-enrolled') }}">
						@icon('graduation-cap')<span>COE Issued & Enrolled</span>
					</a>
				</li>
				<li class="{{ Route::currentRouteName() == 'clients.sheets.discontinue' ? 'active' : '' }}">
					<a class="nav-link" href="{{ route('clients.sheets.discontinue') }}">
						@icon('ban')<span>Discontinue</span>
					</a>
				</li>
				<li class="{{ Route::currentRouteName() == 'clients.sheets.refund' ? 'active' : '' }}">
					<a class="nav-link" href="{{ route('clients.sheets.refund') }}">
						@icon('undo')<span>Refund</span>
					</a>
				</li>
				<li class="{{ Route::currentRouteName() == 'clients.sheets.checklist' ? 'active' : '' }}">
					<a class="nav-link" href="{{ route('clients.sheets.checklist') }}">
						@icon('tasks')<span>Checklist</span>
					</a>
				</li>
				<li class="{{ Route::currentRouteName() == 'clients.sheets.insights' ? 'active' : '' }}">
					<a class="nav-link" href="{{ route('clients.sheets.insights') }}">
						@icon('chart-bar')<span>Insights</span>
					</a>
				</li>
			</ul>
		</li>

		<?php
		if(Route::currentRouteName() == 'signatures.index' || Route::currentRouteName() == 'signatures.create' || Route::currentRouteName() == 'signatures.show'){
			$signatureclasstype = 'active';
		}
		?>
		<li class="dropdown {{@$signatureclasstype}}">
			<a href="{{route('signatures.index')}}" class="nav-link">@icon('file-signature')<span>Signatures</span></a>
		</li>

		<?php
			//if( Auth::user()->role == 1 ){ //super admin or admin

                    if(Route::currentRouteName() == 'clients.clientreceiptlist'){
                        $clientaccountmanagerclasstype = 'active';
                    }
                    ?>
                <li class="dropdown {{@$clientaccountmanagerclasstype}}">
                    <a href="#" class="menu-toggle nav-link has-dropdown">@icon('file-alt')<span>Client Account Manager</span></a>
                    <ul class="dropdown-menu">
                        <li class="{{(Route::currentRouteName() == 'clients.clientreceiptlist') ? 'active' : ''}}">
                            <a href="{{route('clients.clientreceiptlist')}}" class="nav-link">@icon('file-alt')<span>Clients Receipts</span></a>
                        </li>
                      
                        <!--<li class="{{--(Route::currentRouteName() == 'commissionreport') ? 'active' : ''--}}">
                            <a href="{{--route('commissionreport')--}}" class="nav-link">@icon('file-alt')<span>Commission Report</span></a>
                        </li>-->
                    </ul>
                </li>
                <?php
               // }

			if(Route::currentRouteName() == 'partners.index' || Route::currentRouteName() == 'partners.create' || Route::currentRouteName() == 'partners.edit' || Route::currentRouteName() == 'partners.detail'){
				$partnerclasstype = 'active';
			}
			?> 
			<?php
				if(array_key_exists('7',  $module_access)) {
			?>
			<li class="dropdown {{@$partnerclasstype}}">
				<a href="{{route('partners.index')}}" class="nav-link">@icon('users')<span>Partners Manager</span></a>  
			</li>
			<?php
				}
			if(Route::currentRouteName() == 'agents.active' || Route::currentRouteName() == 'agents.inactive' || Route::currentRouteName() == 'agents.create' || Route::currentRouteName() == 'agents.edit' || Route::currentRouteName() == 'agents.detail'){
				$agentclasstype = 'active';
			}
			?> 
			<?php
				if(array_key_exists('15',  $module_access)) {
			?>
			<li class="dropdown {{@$agentclasstype}}">
				<a href="{{route('agents.active')}}" class="nav-link">@icon('users')<span>Agents Manager</span></a>  
			</li>
			<?php
				}
			?>

			<!-- BUSINESS OPERATIONS -->
			<?php
			if(Route::currentRouteName() == 'applications.index'){
				$applicationclasstype = 'active';
			} 
			?> 
			<?php
				if(array_key_exists('34',  $module_access)) {
			?>
			<li class="dropdown {{@$applicationclasstype}}">
				<a href="{{route('applications.index')}}" class="nav-link">@icon('server')<span>Applications Manager</span></a>  
			</li>
			<?php
			}  
			
				/*	if(Auth::user()->role == 1){
						$countaction = \App\Models\Note::whereDate('action_assign_date', date('Y-m-d'))->count();					
					}else{
						$countaction = \App\Models\Note::whereDate('action_assign_date', date('Y-m-d'))->where('assigned_to', Auth::user()->id)->count();
					}*/
					
	
									?>
			<!--<li class="dropdown {{--@$clientclasstype--}}">
				<a href="{{--URL::to('/action-calendar/')--}}" class="nav-link">@icon('user')<span>Today Actions <span class="countaction" style="background: #1f1655;
    padding: 0px 5px;
    border-radius: 50%;
    color: #fff;">{{--$countaction--}}</span></span></a>
			</li>-->
			<?php
			if(Route::currentRouteName() == 'products.index' || Route::currentRouteName() == 'products.create' || Route::currentRouteName() == 'products.edit' || Route::currentRouteName() == 'products.detail'){
				$productclasstype = 'active';
			}
			?>
			<?php
				if(array_key_exists('12',  $module_access)) {
			?>
			<li class="dropdown {{@$productclasstype}}">
				<a href="{{route('products.index')}}" class="nav-link">@icon('shopping-cart')<span>Products Manager</span></a>
			</li>
			<?php
				}
			?>
			
			<!-- FINANCIAL MANAGEMENT -->
			
			<?php
			if(Route::currentRouteName() == 'invoice.unpaid' || Route::currentRouteName() == 'invoice.paid' || Route::currentRouteName() == 'account.payment' || Route::currentRouteName() == 'invoice.unpaidgroupinvoice' || Route::currentRouteName() == 'invoice.paidgroupinvoice' || Route::currentRouteName() == 'account.payableunpaid' || Route::currentRouteName() == 'account.payablepaid' || Route::currentRouteName() == 'account.receivableunpaid' || Route::currentRouteName() == 'account.receivablepaid'){
				$accountclasstype = 'active';
			}
			?> 	
			<li class="dropdown {{@$accountclasstype}}">
				<a href="#" class="menu-toggle nav-link has-dropdown">@icon('dollar-sign')<span>Accounts</span></a>
				<ul class="dropdown-menu"> 
				<?php
					if(array_key_exists('46',  $module_access)) {
					?>
					<li class="{{(Route::currentRouteName() == 'invoice.unpaid' || Route::currentRouteName() == 'invoice.paid') ? 'active' : ''}}"><a class="nav-link" href="{{route('invoice.unpaid')}}">@icon('file-alt')<span>Invoices</span></a></li>
					<?php } ?>
					<?php
					if(array_key_exists('47',  $module_access)) {
					?>
					<li class="{{(Route::currentRouteName() == 'account.payment') ? 'active' : ''}}"><a class="nav-link" href="{{route('account.payment')}}">@icon('dollar-sign')<span>Payment</span></a></li>
					<?php } ?>
					
					<!-- NOTE: Invoice Schedule menu item removed - Invoice Schedule feature has been removed -->
					<li class="{{(Route::currentRouteName() == 'account.payableunpaid' || Route::currentRouteName() == 'account.payablepaid' || Route::currentRouteName() == 'account.receivableunpaid' || Route::currentRouteName() == 'account.receivablepaid') ? 'active' : ''}}"><a class="nav-link" href="{{route('account.payableunpaid')}}">@icon('exchange-alt')<span>Income Sharing</span></a></li>
				</ul>
			</li> 
			<?php
			/*if(Route::currentRouteName() == 'tasks.index'){
				$taskclasstype = 'active';
			}*/
			?> 
		<!--<liclass="dropdown@$taskclasstype">
				<a href="{{-- route('tasks.index') --}}" class="nav-link">@icon('list')<span>To Do Lists</span></a>
			</li>-->
			
			<!-- REPORTS & ANALYTICS -->
			<?php
            if( Auth::user()->role == 1 || Auth::user()->role == 12 ){ //super admin or admin
            ?>
			<li class="dropdown">
				<a href="#" class="menu-toggle nav-link has-dropdown">@icon('file-alt')<span>Reports</span></a> 
				<ul class="dropdown-menu"> 
				<?php
					if(array_key_exists('62',  $module_access)) {
					?>
					<li class=""><a class="nav-link" href="{{route('reports.client')}}">@icon('user')<span>Client</span></a></li>
					<li class=""><a class="nav-link" href="{{route('reports.application')}}">@icon('server')<span>Applications</span></a></li>
					<?php } ?>
					<?php
					if(array_key_exists('63',  $module_access)) {
					?>
					<li class=""><a class="nav-link" href="{{route('reports.invoice')}}">@icon('file-alt')<span>Invoice</span></a></li>
					<?php } ?>
					<?php
					if(array_key_exists('64',  $module_access)) {
					?>
					<li class=""><a class="nav-link" href="{{route('reports.office-visit')}}">@icon('sign-in-alt')<span>Office Check-In</span></a></li>
					<?php } ?>
					<?php
					if(array_key_exists('65',  $module_access)) {
					?>
					<li class=""><a class="nav-link" href="{{route('reports.saleforecast-application')}}">@icon('chart-bar')<span>Sale Forecast</span></a></li>
					<?php } ?>
					<?php
					if(array_key_exists('68',  $module_access)) {
					?>
					{{-- Task system removed - December 2025 --}}
					<?php } ?>
					<li class=""><a class="nav-link" href="{{URL::to('/reports/visaexpires')}}">@icon('calendar')<span>Visa Expires</span></a></li>
					<li class=""><a class="nav-link" href="{{URL::to('/reports/agreementexpires')}}">@icon('file-contract')<span>Agreement Expires</span></a></li>
					
					@if(Auth::user()->role ===1)
                    <li class=""><a class="nav-link" href="{{route('reports.noofpersonofficevisit')}}">@icon('chart-bar')<span>Office Visit Report Date wise</span></a></li>
                    @endif
                    
				</ul> 
			</li>
			<?php
            }
            
			if(Route::currentRouteName() == 'auditlogs.index'){
				$auditlogsclasstype = 'active';
			}
			?> 

			@if(Auth::user()->role ===1)
			<li class="dropdown {{@$auditlogsclasstype}}">
				<a href="{{route('auditlogs.index')}}" class="nav-link">@icon('sign-in-alt')<span>Staff Login Log</span></a>  
			</li>
			@endif
			<?php
			/* if(Route::currentRouteName() == 'users.index' || Route::currentRouteName() == 'users.create' || Route::currentRouteName() == 'users.edit' || Route::currentRouteName() == 'usertype.index' || Route::currentRouteName() == 'usertype.create' || Route::currentRouteName() == 'usertype.edit' || Route::currentRouteName() == 'userrole.index' || Route::currentRouteName() == 'userrole.create' || Route::currentRouteName() == 'userrole.edit'){
				$userclasstype = 'active';
			}
			?> 			
			<li class="dropdown {{@$userclasstype}}">
				<a href="#" class="menu-toggle nav-link has-dropdown">@icon('user')<span>Staff & Access Management</span></a>
				<ul class="dropdown-menu">
					<li class="{{(Route::currentRouteName() == 'staff.index' || Route::currentRouteName() == 'staff.active' || Route::currentRouteName() == 'staff.create' || Route::currentRouteName() == 'staff.edit' || Route::currentRouteName() == 'staff.view') ? 'active' : ''}}"><a class="nav-link" href="{{route('staff.active')}}">Staff</a></li>
					<li class="{{(Route::currentRouteName() == 'staffrole.index' || Route::currentRouteName() == 'staffrole.create' || Route::currentRouteName() == 'staffrole.edit') ? 'active' : ''}}"><a class="nav-link" href="{{route('staffrole.index')}}">Staff Role</a></li>
				</ul>
			</li>
			<?php 
			if(Route::currentRouteName() == 'services.index' || Route::currentRouteName() == 'services.create' || Route::currentRouteName() == 'services.edit'){
				$servclasstype = 'active';
			}
			?> 
			<li class="dropdown {{@$servclasstype}}">
				<a href="#" class="menu-toggle nav-link has-dropdown">@icon('user')<span>Services</span></a>
				<ul class="dropdown-menu"> 
					<li class="{{(Route::currentRouteName() == 'services.index' || Route::currentRouteName() == 'services.create' || Route::currentRouteName() == 'services.edit') ? 'active' : ''}}"><a class="nav-link" href="{{route('services.index')}}">Services List</a></li>
				</ul>
			</li>
			<?php
			 if(Route::currentRouteName() == 'providers.index' || Route::currentRouteName() == 'providers.create' || Route::currentRouteName() == 'providers.edit'){
				$provclasstype = 'active';
			}
			?> 
			<li class="dropdown {{@$provclasstype}}">
				<a href="#" class="menu-toggle nav-link has-dropdown">@icon('user')<span>Providers</span></a>
				<ul class="dropdown-menu">
					<li class="{{(Route::currentRouteName() == 'providers.index' || Route::currentRouteName() == 'providers.create' || Route::currentRouteName() == 'providers.edit') ? 'active' : ''}}"><a class="nav-link" href="{{route('providers.index')}}">Providers List</a></li>
				</ul>
			</li>
			<?php
			if(Route::currentRouteName() == 'leads.index'){
				$leadclasstype = 'active';
			}
			?> 
			<li class="dropdown {{@$leadclasstype}}">
				<a href="{{route('leads.index')}}" class="nav-link">@icon('briefcase')<span>Leads</span></a>
			</li>
			<?php
			if(Route::currentRouteName() == 'invoice.index'){
				$invclasstype = 'active';
			}
			?> 
			<li class="dropdown {{@$invclasstype}}">
				<a href="#" class="menu-toggle nav-link has-dropdown">@icon('briefcase')<span>Invoices</span></a>
				<ul class="dropdown-menu">
					<li class="{{(Route::currentRouteName() == 'invoice.index') ? 'active' : ''}}"><a class="nav-link" href="{{route('invoice.index')}}">Invoices</a></li>
					<li><a class="nav-link" href="#">Payment Received</a></li>
				</ul>
			</li>
			<?php
		if(Route::currentRouteName() == 'email.index'){
			$emtemclasstype = 'active';
		}
		?>
			<li class="dropdown {{@$emtemclasstype}}">
				<a href="{{route('email.index')}}" class="nav-link">@icon('envelope')<span>Email Templates</span></a>
			</li>
			<?php
			if(Route::currentRouteName() == 'my_profile' || Route::currentRouteName() == 'change_password' || Route::currentRouteName() == 'edit_api'){
				$actsetclasstype = 'active';
			}*/ 
			?> 
			<!--<li class="dropdown {{@$actsetclasstype}}">
				<a href="#" class="menu-toggle nav-link has-dropdown">@icon('cog')<span>My Account & Settings</span></a>
				<ul class="dropdown-menu">
					<li class="{{(Route::currentRouteName() == 'my_profile') ? 'active' : ''}}"><a class="nav-link" href="{{route('my_profile')}}">Manage Profile</a></li>
					<li class="{{(Route::currentRouteName() == 'change_password') ? 'active' : ''}}"><a class="nav-link" href="{{route('change_password')}}">Change Password</a></li>
					<li class="{{(Route::currentRouteName() == 'edit_api') ? 'active' : ''}}"><a class="nav-link" href="{{route('edit_api')}}">Api Key</a></li>
				</ul> 
			</li>-->
			<li class="dropdown">
				<a href="{{route('admin.logout')}}" class="nav-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">@icon('sign-out-alt')<span>Logout</span></a>
			</li>
		</ul>
	</aside>
</div>