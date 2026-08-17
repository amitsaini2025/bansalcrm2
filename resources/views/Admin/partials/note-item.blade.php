{{-- Single note item (shared by client/lead detail and AJAX refresh) --}}
<div class="note_col" id="note_id_{{ $list->id }}">
	<div class="note_content">
		<h4><a class="viewnote" data-id="{{ $list->id }}" href="javascript:;">{{ @$list->title == "" ? config('constants.empty') : str_limit(@$list->title, '19', '...') }}</a></h4>
		@if($list->pin == 1)
			<div class="pined_note">@icon('thumbtack')</div>
		@endif
	</div>
	<div class="extra_content">
		@if(!empty($list->description))
			@php
				$description = $list->description;
			@endphp

			<div class="note_description">
				@if(strpos($description, '<xml>') !== false || strpos($description, '<o:OfficeDocumentSettings>') !== false)
					<p>{!! htmlentities($description) !!}</p>
				@else
					<p>{!! \App\Helpers\Helper::normalizeActivityDescriptionHtml($description, true) !!}</p>
				@endif
			</div>
		@endif

		@if(isset($list->mobile_number) && $list->mobile_number != "")
			<p class="note_mobile">{{ @$list->mobile_number }}</p>
		@endif

		<div class="note_footer">
			<div class="left">
				@if($staff)
					<div class="author">
						<a href="{{ route('staff.view', ['id' => $staff->id]) }}">{{ substr($staff->first_name, 0, 1) }}</a>
					</div>
					<div class="note_modify">
						<small>Last Modified <span>{{ date('d/m/Y h:i A', strtotime($list->updated_at)) }}</span></small>
						{{ $staff->first_name }} {{ $staff->last_name }}
					</div>
				@else
					<div class="note_modify">
						<small>Last Modified <span>{{ date('d/m/Y h:i A', strtotime($list->updated_at)) }}</span></small>
						<span class="text-muted">—</span>
					</div>
				@endif
			</div>
			<div class="right">
				<div class="dropdown d-inline dropdown_ellipsis_icon">
					<a class="dropdown-toggle" href="javascript:;" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">@icon('ellipsis-v')</a>
					<div class="dropdown-menu">
						<a class="dropdown-item opennoteform" data-id="{{ $list->id }}" href="javascript:;">Edit</a>
						@if(Auth::user()->role == 1)
							<a data-id="{{ $list->id }}" data-href="deletenote" class="dropdown-item deletenote" href="javascript:;">Delete</a>
						@endif
						@if($list->pin == 1)
							<a data-id="{{ $list->id }}" class="dropdown-item pinnote" href="javascript:;">UnPin</a>
						@else
							<a data-id="{{ $list->id }}" class="dropdown-item pinnote" href="javascript:;">Pin</a>
						@endif
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
