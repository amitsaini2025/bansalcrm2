<?php

namespace Tests\Unit\Services;

use App\Services\DashboardService;
use App\Services\StaffWorkloadService;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class StaffWorkloadServiceTest extends TestCase
{
    private function service(): StaffWorkloadService
    {
        return new StaffWorkloadService($this->createMock(DashboardService::class));
    }

    public function test_day_bounds_use_app_timezone_start_and_end(): void
    {
        config(['app.timezone' => 'Australia/Melbourne']);

        $day = Carbon::parse('2026-09-01', 'Australia/Melbourne');
        [$start, $end] = $this->service()->dayBounds($day);

        $this->assertSame('2026-09-01 00:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-01 23:59:59', $end->format('Y-m-d H:i:s'));
        $this->assertSame('Australia/Melbourne', $start->timezone->getName());
    }

    public function test_note_audit_subjects_are_excluded_from_throughput_activity_credit(): void
    {
        $service = $this->service();

        $this->assertTrue($service->isNoteAuditSubject('added a note'));
        $this->assertTrue($service->isNoteAuditSubject('Updated a note'));
        $this->assertFalse($service->isNoteAuditSubject('uploaded document'));
    }

    public function test_inactivity_band_quiet_and_inactive_thresholds(): void
    {
        $service = $this->service();
        $method = new ReflectionMethod(StaffWorkloadService::class, 'inactivityBand');
        $method->setAccessible(true);

        config(['app.timezone' => 'Australia/Melbourne']);
        $today = Carbon::parse('2026-09-01', 'Australia/Melbourne')->startOfDay();

        $this->assertSame('inactive', $method->invoke($service, null, $today));

        $tenDaysAgo = $today->copy()->subDays(10);
        $this->assertSame('quiet', $method->invoke($service, $tenDaysAgo, $today));

        $fifteenDaysAgo = $today->copy()->subDays(15);
        $this->assertSame('inactive', $method->invoke($service, $fifteenDaysAgo, $today));

        $threeDaysAgo = $today->copy()->subDays(3);
        $this->assertNull($method->invoke($service, $threeDaysAgo, $today));
    }

    public function test_encode_record_id_is_reversible(): void
    {
        $encoded = $this->service()->encodeRecordId(42);
        $decoded = convert_uudecode(base64_decode($encoded));

        $this->assertSame('42', $decoded);
    }

    public function test_contact_titles_are_call_and_in_person_only(): void
    {
        $this->assertSame(['Call', 'In-Person'], StaffWorkloadService::CONTACT_TITLES);
    }
}
