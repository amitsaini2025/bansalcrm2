<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FollowupCalendarBlockTiming;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowupCalendarBlockTimingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');
        if (! in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        $query = FollowupCalendarBlockTiming::query()->orderByDesc('block_date')->orderByDesc('id');

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $blocks = $query->paginate(25)->withQueryString();

        return view('Admin.followups.blocked-times.index', compact('blocks', 'status'));
    }

    public function create(): View
    {
        return view('Admin.followups.blocked-times.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateBlock($request);
        FollowupCalendarBlockTiming::create($data);

        return redirect()->to(followups_console_route('blocked-times.index'))->with('success', 'Blocked time created.');
    }

    public function show(FollowupCalendarBlockTiming $followupCalendarBlockTiming): View
    {
        return view('Admin.followups.blocked-times.show', ['block' => $followupCalendarBlockTiming]);
    }

    public function edit(FollowupCalendarBlockTiming $followupCalendarBlockTiming): View
    {
        return view('Admin.followups.blocked-times.edit', ['block' => $followupCalendarBlockTiming]);
    }

    public function update(Request $request, FollowupCalendarBlockTiming $followupCalendarBlockTiming): RedirectResponse
    {
        $data = $this->validateBlock($request);
        $followupCalendarBlockTiming->update($data);

        return redirect()->to(followups_console_route('blocked-times.index'))->with('success', 'Blocked time updated.');
    }

    public function destroy(FollowupCalendarBlockTiming $followupCalendarBlockTiming): RedirectResponse
    {
        $followupCalendarBlockTiming->delete();

        return redirect()->to(followups_console_route('blocked-times.index'))->with('success', 'Blocked time deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateBlock(Request $request): array
    {
        $allDay = $request->boolean('is_all_day');

        $rules = [
            'title' => 'required|string|max:255',
            'block_date' => 'required|date',
            'block_type' => 'required|string|in:'.implode(',', array_keys(FollowupCalendarBlockTiming::BLOCK_TYPES)),
            'is_all_day' => 'sometimes|boolean',
            'recurrence' => 'required|string|in:'.implode(',', array_keys(FollowupCalendarBlockTiming::RECURRENCE)),
            'consultants' => 'nullable|array',
            'consultants.*' => 'string|in:'.implode(',', FollowupCalendarBlockTiming::allowedConsultantSlugsForValidation()),
            'is_active' => 'sometimes|boolean',
        ];

        if ($allDay) {
            $rules['start_time'] = 'nullable|date_format:H:i';
            $rules['end_time'] = 'nullable|date_format:H:i';
        } else {
            $rules['start_time'] = 'required|date_format:H:i';
            $rules['end_time'] = 'required|date_format:H:i|after:start_time';
        }

        $validated = $request->validate($rules);

        $consultantSlugs = isset($validated['consultants'])
            ? array_values(array_unique(array_values(array_filter((array) $validated['consultants']))))
            : [];

        return [
            'title' => $validated['title'],
            'block_date' => $validated['block_date'],
            'is_all_day' => $allDay,
            'start_time' => $allDay ? null : ($validated['start_time'].':00'),
            'end_time' => $allDay ? null : ($validated['end_time'].':00'),
            'block_type' => $validated['block_type'],
            'recurrence' => $validated['recurrence'],
            'locations' => [],
            'calendar_types' => [],
            'consultant_slugs' => $consultantSlugs,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
