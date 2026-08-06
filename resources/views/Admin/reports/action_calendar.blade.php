@extends('layouts.admin')
@section('title', 'Action Calendar')

@section('content')
<style>
.fc-event-container .fc-h-event{cursor:pointer;}
.fc-more-popover {
    overflow-y: scroll;
   max-height: 50%;
    max-width: auto;
}
</style>
<!-- Main Content -->
<div class="main-content">
	<section class="section">
		<div class="section-body">
			<div class="server-error">
				@include('../Elements/flash-message')
			</div>
			<div class="custom-error-msg">
			</div>
			<div class="row">
				<div class="col-12 col-md-12 col-lg-12">
					<div class="card">
						<div class="card-header">
							<h4>Action Calendar</h4>
							
						</div>
						<div class="card-body">
							 <div class="fc-overflow">
								<div id="myEvent"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</div>
<?php
 $sched_res = [];
 if(Auth::user()->role == 1){
     $followups = \App\Models\Note::select('client_id','id', 'action_assign_date', 'description')
         ->where('type','client')
         ->where('is_action',1)
         ->where('status',0)
         ->whereNotNull('action_assign_date')
         ->get();
 }else{
     $followups = \App\Models\Note::select('client_id','id', 'action_assign_date', 'description')
         ->where('assigned_to',Auth::user()->id)
         ->where('type','client')
         ->where('is_action',1)
         ->where('status',0)
         ->whereNotNull('action_assign_date')
         ->get();
 }

foreach($followups as $followup){
    $client = \App\Models\Admin::where('id',$followup->client_id)
        ->select('id','client_id','first_name','last_name','email','phone','type')
        ->first();
    
    if($client){
        $encodedClientId = base64_encode(convert_uuencode($client->id));
        $isLeadType = strtolower((string) ($client->type ?? '')) === 'lead';
        $followupData = [
            'id' => $followup->id,
            'clientid' => $client->id,
            'stitle' => htmlspecialchars($client->client_id, ENT_QUOTES, 'UTF-8'),
            'name' => base64_encode($client->first_name.' '.$client->last_name),
            'email' => base64_encode($client->email),
            'phone' => base64_encode($client->phone),
            'startdate' => date("Y-m-d", strtotime($followup->action_assign_date)),
            'end' => date("Y-m-d", strtotime($followup->action_assign_date)),
            'followup_date' => date("F d, Y", strtotime($followup->action_assign_date)),
            'description' => htmlspecialchars($followup->description, ENT_QUOTES, 'UTF-8'),
            'url' => $isLeadType
                ? route('leads.detail', $encodedClientId)
                : route('clients.detail', $encodedClientId),
        ];
        $sched_res[$followup->id] = $followupData;
    }
}
?>
@endsection
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    waitForTomSelect().then(function () {
        initTomSelect('#changeassignee', {
            width: '100%',
            placeholder: 'Select',
            allowClear: true,
            dropdownParent: '#event-details-modal',
            maxOptions: null
        });
    });

    // Wait for FullCalendar to be loaded
    if (typeof window.FullCalendar === 'undefined') {
        console.error('FullCalendar v6 not loaded');
        return;
    }

    var events = [];
    var scheds = {!! json_encode($sched_res, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
    if (!!scheds && typeof scheds === 'object') {
        Object.keys(scheds).map(k => {
            var row = scheds[k]
            events.push({ id: row.id, title: row.stitle, start: row.startdate, end: row.end });
        });
    }

    var calendarEl = document.getElementById('myEvent');
    if (!calendarEl) {
        console.error('Calendar element #myEvent not found');
        return;
    }

    var calendar = new window.FullCalendar.Calendar(calendarEl, {
        height: "auto",
        initialView: "dayGridMonth",
        editable: false,
        selectable: true,
        dayMaxEvents: true,
        moreLinkText: "More",
        plugins: [
            window.FullCalendar.dayGridPlugin,
            window.FullCalendar.timeGridPlugin,
            window.FullCalendar.listPlugin,
            window.FullCalendar.interactionPlugin
        ],
        headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay,listMonth",
        },
        events: events,
        eventClick: function(info) {
            console.log(info);
            var details = document.getElementById('event-details-modal');
            if (!details) return;
            
            var id = info.event.id;

            if (!!scheds[id]) {
                var titleEl = details.querySelector('#title');
                var descEl = details.querySelector('#description');
                var clnameEl = details.querySelector('#clname');
                var phoneEl = details.querySelector('#phone');
                var emailEl = details.querySelector('#email');
                var startEl = details.querySelector('#start');
                var followupClientIdEl = details.querySelector('#action_client_id');
                var leadIdEl = details.querySelector('#lead_id');
                var urlEl = details.querySelector('#url');
                
                if (titleEl) titleEl.textContent = scheds[id].stitle;
                if (descEl) descEl.innerHTML = scheds[id].description;
                if (clnameEl) clnameEl.textContent = atob(scheds[id].name);
                if (phoneEl) phoneEl.textContent = atob(scheds[id].phone);
                if (emailEl) emailEl.textContent = atob(scheds[id].email);
                if (startEl) startEl.textContent = scheds[id].followup_date;
                if (followupClientIdEl) followupClientIdEl.value = scheds[id].clientid;
                if (leadIdEl) leadIdEl.value = scheds[id].id;
                if (scheds[id].url && urlEl) {
                    urlEl.innerHTML = '<a target="_blank" href="'+scheds[id].url+'">View Client</a>';
                }
                
                // Use Bootstrap 5 modal API
                var modal = bootstrap.Modal.getOrCreateInstance(details);
                modal.show();
            } else {
                alert("Event is undefined");
            }
        }
    });

    calendar.render();
});
</script>
<div class="modal fade" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" id="event-details-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-0">
                <div class="modal-header rounded-0">
                    <h5 class="modal-title">Schedule Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">@icon('times')</button>
                </div>
                <div class="modal-body rounded-0">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-6">
                                <dl>
                                    <dt class="text-muted">Client ID</dt>
                                    <dd id="title" class="fw-bold fs-4"></dd>
                                    <dd id="url" class="fw-bold fs-4"></dd>
                                    <dt class="text-muted">Client Name</dt>
                                    <dd id="clname" class="fw-bold fs-4"></dd>
                                   
                                   
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl>
                                    <dt class="text-muted">Email</dt>
                                    <dd id="email" class="fw-bold fs-4"></dd>
                                    
                                    <dt class="text-muted">Phone</dt>
                                    <dd id="phone" class="fw-bold fs-4"></dd>
                                   
                                   
                                </dl>
                            </div>
                            <div class="col-md-12">
                                <dt class="text-muted">Note</dt>
                                    <dd id="description" class="fw-bold fs-4"></dd>
                                </div>
                                 </div>
                        <form method="post" name="retagmodalsave" id="retagmodalsave" action="{{URL::to('/clients/action/retag')}}" autocomplete="off" enctype="multipart/form-data">
		            	@csrf   
		            	<input type="hidden" name="client_id" id="action_client_id">
		            	<input type="hidden" name="lead_id" id="lead_id">
			            	 <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Assigned To</label>
                                    <select data-valid="required" class="form-control tomselect" id="changeassignee" name="changeassignee">
                                         <option value="">Select</option>
						                 <?php 
											foreach(\App\Models\Staff::where('status',1)->orderby('first_name','ASC')->get() as $admin){
												$branchname = \App\Models\Branch::where('id',$admin->office_id)->first();
										?>
												<option value="<?php echo $admin->id; ?>"><?php echo $admin->first_name.' '.$admin->last_name.' ('.@$branchname->office_name.')'; ?></option>
										<?php } ?>
									</select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" name="followup_date" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Time</label>
                                    <input type="time" name="followup_time" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Message</label>
                                    <textarea class="form-control" name="message"></textarea>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <button type="button" class="btn btn-primary" onclick="customValidate('retagmodalsave')">Save</button>
                                </div>
                                </div>
                                </form>
                        
                        
                    </div>
                </div>
               
            </div>
        </div>
    </div>
@endsection