@extends(request()->routeIs('adminconsole.followups.*') ? 'layouts.adminconsole' : 'layouts.admin')
@section('title', 'Blocked time')

@section('content')
@php
	use App\Models\FollowupCalendarBlockTiming;
@endphp
<div class="main-content">
	<section class="section">
		<div class="section-body">
			<div class="server-error">
				@include('../Elements/flash-message')
			</div>
			<div class="row justify-content-center">
				<div class="col-12 col-lg-8">
					<div class="d-flex justify-content-between align-items-center mb-3">
						<h4 class="mb-0">{{ $block->title }}</h4>
						<div class="d-flex gap-2">
							<a href="{{ followups_console_route('blocked-times.index') }}" class="btn btn-outline-secondary btn-sm">Back to list</a>
							<a href="{{ followups_console_route('blocked-times.edit', $block) }}" class="btn btn-warning btn-sm">Edit</a>
						</div>
					</div>
					<div class="card">
						<div class="card-body">
							<dl class="row mb-0">
								<dt class="col-sm-4">Date</dt>
								<dd class="col-sm-8">{{ $block->block_date->format('M j, Y') }}</dd>
								<dt class="col-sm-4">Time</dt>
								<dd class="col-sm-8">
									@if($block->is_all_day)
										All day
									@else
										{{ \Carbon\Carbon::parse($block->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($block->end_time)->format('g:i A') }}
									@endif
								</dd>
								<dt class="col-sm-4">Type</dt>
								<dd class="col-sm-8">{{ FollowupCalendarBlockTiming::BLOCK_TYPES[$block->block_type] ?? $block->block_type }}</dd>
								<dt class="col-sm-4">Recurrence</dt>
								<dd class="col-sm-8">{{ FollowupCalendarBlockTiming::RECURRENCE[$block->recurrence] ?? $block->recurrence }}</dd>
								<dt class="col-sm-4">Consultants</dt>
								<dd class="col-sm-8">
									@php
										$showSlugs = $block->consultant_slugs ?? [];
										if (! is_array($showSlugs)) {
											$showSlugs = [];
										}
									@endphp
									@forelse($showSlugs as $slug)
										{{ FollowupCalendarBlockTiming::consultantSlugOptions()[$slug] ?? $slug }}@if(!$loop->last), @endif
									@empty
										<span class="text-muted">All consultants</span>
									@endforelse
								</dd>
								<dt class="col-sm-4">Status</dt>
								<dd class="col-sm-8">{{ $block->is_active ? 'Active' : 'Inactive' }}</dd>
							</dl>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</div>
@endsection
