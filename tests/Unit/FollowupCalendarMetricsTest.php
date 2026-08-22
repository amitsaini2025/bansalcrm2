<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\FollowupController;
use Carbon\Carbon;
use Tests\TestCase;

class FollowupCalendarMetricsTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_metrics_count_today_this_week_and_upcoming(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-08-22 12:00:00', $tz));

        $metrics = FollowupController::metricsFromSchedule([
            ['startdate' => '2026-08-22', 'start_iso' => '2026-08-22T09:00:00'],
            ['startdate' => '2026-08-22', 'start_iso' => '2026-08-22T15:00:00'],
            ['startdate' => '2026-08-23', 'start_iso' => '2026-08-23T10:00:00'],
            ['startdate' => '2026-09-01', 'start_iso' => '2026-09-01T10:00:00'],
            ['startdate' => '2026-08-01', 'start_iso' => '2026-08-01T10:00:00'],
        ]);

        $this->assertSame(2, $metrics['today']);
        $this->assertSame(3, $metrics['this_week']);
        $this->assertSame(3, $metrics['upcoming']);
    }

    public function test_without_backdated_schedule_keeps_today_and_future_only(): void
    {
        $tz = config('app.timezone');
        Carbon::setTestNow(Carbon::parse('2026-08-22 12:00:00', $tz));

        $kept = FollowupController::withoutBackdatedSchedule([
            1 => ['startdate' => '2026-08-01', 'start_iso' => '2026-08-01T10:00:00'],
            2 => ['startdate' => '2026-08-22', 'start_iso' => '2026-08-22T09:00:00'],
            3 => ['startdate' => '2026-08-31', 'start_iso' => '2026-08-31T10:00:00'],
        ]);

        $this->assertSame([2, 3], array_keys($kept));
    }
}
