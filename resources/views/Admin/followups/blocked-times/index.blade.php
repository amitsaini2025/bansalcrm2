@extends(request()->routeIs('adminconsole.followups.*') ? 'layouts.adminconsole' : 'layouts.admin')
@section('title', 'Blocked times management')

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
			<div class="row">
				<div class="col-12">
					<div class="card">
						<div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
							<div>
								<h4 class="mb-0">Blocked times management</h4>
								<p class="text-muted small mb-0 mt-1">Block specific times and manage calendar availability.</p>
							</div>
							<div class="d-flex flex-wrap gap-2 align-items-center">
								<a href="{{ route('adminconsole.producttype.index') }}" class="btn btn-outline-primary btn-sm">@icon('columns', 'solid', ['class' => 'me-1']) Admin Console</a>
								<a href="{{ followups_console_route('blocked-times.create') }}" class="btn btn-danger">
									@icon('plus', 'solid', ['class' => 'me-1']) Block time
								</a>
							</div>
						</div>
						<div class="card-body">
							<div class="mb-3">
								<span class="me-2 text-muted small">Filter by status</span>
								<div class="btn-group btn-group-sm" role="group">
									<a href="{{ followups_console_route('blocked-times.index', ['status' => 'all']) }}" class="btn btn-{{ $status === 'all' ? 'primary' : 'outline-primary' }}">All</a>
									<a href="{{ followups_console_route('blocked-times.index', ['status' => 'active']) }}" class="btn btn-{{ $status === 'active' ? 'primary' : 'outline-primary' }}">Active</a>
									<a href="{{ followups_console_route('blocked-times.index', ['status' => 'inactive']) }}" class="btn btn-{{ $status === 'inactive' ? 'primary' : 'outline-primary' }}">Inactive</a>
								</div>
							</div>
							<div class="table-responsive">
								<table class="table table-striped table-hover table-md mb-0">
									<thead>
										<tr>
											<th>Title</th>
											<th>Date &amp; time</th>
											<th>Type</th>
											<th>Consultants</th>
											<th>Status</th>
											<th class="text-end">Actions</th>
										</tr>
									</thead>
									<tbody>
										@forelse($blocks as $row)
											@php
												$dateStr = $row->block_date->format('M j, Y');
												if ($row->is_all_day) {
													$when = $dateStr.' — All day';
												} else {
													$s = $row->start_time ? \Carbon\Carbon::parse($row->start_time)->format('g:i A') : '—';
													$e = $row->end_time ? \Carbon\Carbon::parse($row->end_time)->format('g:i A') : '—';
													$when = $dateStr.' '.$s.' – '.$e;
												}
												$slugLabels = [];
												foreach ($row->consultant_slugs ?? [] as $slug) {
													$slugLabels[] = FollowupCalendarBlockTiming::consultantSlugOptions()[$slug] ?? $slug;
												}
												$consultantsCol = $slugLabels === [] ? 'All consultants' : implode(', ', $slugLabels);
												$typeBadge = $row->block_type === 'busy' ? 'warning' : 'danger';
											@endphp
											<tr>
												<td class="fw-semibold">{{ $row->title }}</td>
												<td class="small">{{ $when }}</td>
												<td>
													<span class="badge bg-{{ $typeBadge }}">{{ FollowupCalendarBlockTiming::BLOCK_TYPES[$row->block_type] ?? $row->block_type }}</span>
												</td>
												<td class="small text-muted">{{ $consultantsCol }}</td>
												<td>
													@if($row->is_active)
														<span class="badge bg-success">Active</span>
													@else
														<span class="badge bg-secondary">Inactive</span>
													@endif
												</td>
												<td class="text-end text-nowrap">
													<a href="{{ followups_console_route('blocked-times.show', $row) }}" class="btn btn-sm btn-outline-info" title="View">@icon('eye')</a>
													<a href="{{ followups_console_route('blocked-times.edit', $row) }}" class="btn btn-sm btn-outline-warning" title="Edit">@icon('pencil-alt')</a>
													<form action="{{ followups_console_route('blocked-times.destroy', $row) }}" method="post" class="d-inline" data-crm-confirm='Delete this blocked time?'">
														@csrf
														@method('DELETE')
														<button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">@icon('trash-alt')</button>
													</form>
												</td>
											</tr>
										@empty
											<tr>
												<td colspan="6" class="text-center text-muted py-4">No blocked times yet.</td>
											</tr>
										@endforelse
									</tbody>
								</table>
							</div>
							@if($blocks->hasPages())
								<div class="card-footer">{{ $blocks->links() }}</div>
							@endif
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</div>
@endsection
