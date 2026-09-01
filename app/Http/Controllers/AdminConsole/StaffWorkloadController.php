<?php

namespace App\Http\Controllers\AdminConsole;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\StaffWorkloadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StaffWorkloadController extends Controller
{
    public function __construct(
        private StaffWorkloadService $staffWorkloadService,
    ) {
        $this->middleware('auth:admin');
    }

    /**
     * Super Admin only (same gate as Recently Modified Clients).
     */
    private function ensureSuperAdminAccess(): void
    {
        if ((Auth::user()->role ?? null) != 1) {
            abort(403, 'Unauthorized.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdminAccess();

        try {
            $teamOverview = $this->staffWorkloadService->getTeamOverview();
        } catch (\Throwable $e) {
            Log::error('Staff workload team overview failed: '.$e->getMessage());

            return view('AdminConsole.staff_workload.index', [
                'teamOverview' => ['day_label' => now()->timezone(config('app.timezone'))->format('l, j F Y'), 'rows' => []],
            ])->with('error', 'Could not load staff workload data. Please try again.');
        }

        return view('AdminConsole.staff_workload.index', compact('teamOverview'));
    }

    public function show(Request $request, Staff $staff)
    {
        $this->ensureSuperAdminAccess();

        if ((int) ($staff->status ?? 0) !== 1) {
            abort(404);
        }

        try {
            $summary = $this->staffWorkloadService->getDaySummary((int) $staff->id);
        } catch (\Throwable $e) {
            Log::error('Staff workload detail failed: '.$e->getMessage(), ['staff_id' => $staff->id]);

            return redirect()
                ->route('adminconsole.staff-workload.index')
                ->with('error', 'Could not load workload for this staff member.');
        }

        return view('AdminConsole.staff_workload.show', [
            'staff' => $staff,
            'summary' => $summary,
        ]);
    }
}
