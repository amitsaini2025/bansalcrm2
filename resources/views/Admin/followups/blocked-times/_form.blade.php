@php
	use App\Models\FollowupCalendarBlockTiming;
	$b = $block ?? null;
@endphp

<div class="row">
	<div class="col-md-12 mb-3">
		<label class="form-label">Title <span class="text-danger">*</span></label>
		<input type="text" name="title" class="form-control" required maxlength="255"
			value="{{ old('title', $b->title ?? '') }}">
	</div>
	<div class="col-md-6 mb-3">
		<label class="form-label">Date <span class="text-danger">*</span></label>
		<input type="date" name="block_date" class="form-control" required
			value="{{ old('block_date', $b && $b->block_date ? $b->block_date->format('Y-m-d') : '') }}">
	</div>
	<div class="col-md-6 mb-3">
		<label class="form-label">Block type <span class="text-danger">*</span></label>
		<select name="block_type" class="form-control" required>
			@foreach (FollowupCalendarBlockTiming::BLOCK_TYPES as $val => $label)
				<option value="{{ $val }}" @selected(old('block_type', $b->block_type ?? 'unavailable') === $val)>{{ $label }}</option>
			@endforeach
		</select>
	</div>
</div>

<div class="mb-3">
	<input type="hidden" name="is_all_day" value="0">
	<div class="form-check">
		<input type="checkbox" name="is_all_day" value="1" id="blocked_is_all_day" class="form-check-input"
			@checked(old('is_all_day', $b ? ($b->is_all_day ? '1' : '0') : '0') === '1')>
		<label class="form-check-label" for="blocked_is_all_day">All day block</label>
	</div>
</div>

<div class="row blocked-time-range" id="blocked_time_range_row">
	<div class="col-md-6 mb-3">
		<label class="form-label">Start time</label>
		<input type="time" name="start_time" id="blocked_start_time" class="form-control"
			value="{{ old('start_time', ($b && ! $b->is_all_day && $b->start_time) ? substr((string) $b->start_time, 0, 5) : '') }}">
	</div>
	<div class="col-md-6 mb-3">
		<label class="form-label">End time</label>
		<input type="time" name="end_time" id="blocked_end_time" class="form-control"
			value="{{ old('end_time', ($b && ! $b->is_all_day && $b->end_time) ? substr((string) $b->end_time, 0, 5) : '') }}">
	</div>
</div>

<div class="mb-3">
	<label class="form-label">Recurrence <span class="text-danger">*</span></label>
	<select name="recurrence" class="form-control" required>
		@foreach (FollowupCalendarBlockTiming::RECURRENCE as $val => $label)
			<option value="{{ $val }}" @selected(old('recurrence', $b->recurrence ?? 'none') === $val)>{{ $label }}</option>
		@endforeach
	</select>
</div>

<div class="mb-3">
	<span class="form-label d-block">Consultants</span>
	<div class="form-text mb-2">Leave none selected to apply this block to all calendars.</div>
	@php
		$consultOld = old('consultants', $b->consultant_slugs ?? []);
		if (! is_array($consultOld)) {
			$consultOld = [];
		}
		$consultantOptions = FollowupCalendarBlockTiming::consultantSlugOptions();
	@endphp
	<div class="d-flex flex-wrap gap-3">
		@foreach ($consultantOptions as $slug => $label)
			<div class="form-check">
				<input type="checkbox" name="consultants[]" value="{{ $slug }}" id="consultant_{{ $slug }}" class="form-check-input"
					{{ in_array($slug, $consultOld, true) ? 'checked' : '' }}>
				<label class="form-check-label" for="consultant_{{ $slug }}">{{ $label }}</label>
			</div>
		@endforeach
	</div>
</div>

<div class="mb-3">
	<input type="hidden" name="is_active" value="0">
	<div class="form-check">
		<input type="checkbox" name="is_active" value="1" id="blocked_is_active" class="form-check-input"
			@checked(old('is_active', $b ? ($b->is_active ? '1' : '0') : '1') === '1')>
		<label class="form-check-label" for="blocked_is_active">Active</label>
	</div>
</div>

@push('scripts')
<script>
(function () {
	function syncAllDay() {
		var cb = document.getElementById('blocked_is_all_day');
		var row = document.getElementById('blocked_time_range_row');
		var st = document.getElementById('blocked_start_time');
		var et = document.getElementById('blocked_end_time');
		if (!cb || !row || !st || !et) return;
		var all = cb.checked;
		row.style.opacity = all ? '0.5' : '1';
		st.disabled = all;
		et.disabled = all;
		if (all) {
			st.value = '';
			et.value = '';
		}
	}
	var c = document.getElementById('blocked_is_all_day');
	if (c) {
		c.addEventListener('change', syncAllDay);
		syncAllDay();
	}
})();
</script>
@endpush
