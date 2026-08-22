<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\FollowupController;
use App\Models\Staff;
use Tests\TestCase;

class DashboardFollowupCalendarTest extends TestCase
{
    private function actingAsStaff(): Staff
    {
        $staff = new Staff([
            'first_name' => 'Test',
            'last_name' => 'Staff',
            'email' => 'staff@example.com',
            'password' => 'secret',
        ]);
        $staff->id = 1;

        $this->actingAs($staff, 'admin');

        return $staff;
    }

    public function test_guest_cannot_fetch_followup_calendar_events(): void
    {
        $response = $this->getJson(route('followups.calendar.events', ['consultant' => 'ankit']));

        $response->assertUnauthorized();
    }

    public function test_unknown_consultant_calendar_events_return_not_found(): void
    {
        $this->actingAsStaff();

        $response = $this->getJson(route('followups.calendar.events', ['consultant' => 'not-a-consultant']));

        $response->assertNotFound();
    }

    public function test_dashboard_tabs_default_to_ankit_then_the_other_three(): void
    {
        $tabs = FollowupController::dashboardCalendarTabs();

        $this->assertSame(['ankit', 'rakshita', 'jaspreet', 'syed'], array_column($tabs, 'slug'));
        $this->assertSame('Ankit', $tabs[0]['label']);
        $this->assertSame(FollowupController::DASHBOARD_DEFAULT_CONSULTANT, $tabs[0]['slug']);
        $this->assertStringContainsString('/followups/calendar/ankit/events', $tabs[0]['events_url']);
    }
}
