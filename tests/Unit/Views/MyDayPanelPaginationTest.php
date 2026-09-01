<?php

namespace Tests\Unit\Views;

use App\Support\ArrayPaginator;
use Illuminate\Http\Request;
use Tests\TestCase;

class MyDayPanelPaginationTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function summaryWithLists(int $quietCount, int $applicationCount): array
    {
        $quietStudents = [];
        for ($i = 1; $i <= $quietCount; $i++) {
            $quietStudents[] = [
                'name' => 'Quiet Client '.$i,
                'reference' => 'REF'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'last_work_at' => '01/01/2026',
                'url' => '/clients/'.$i,
            ];
        }

        $applications = [];
        for ($i = 1; $i <= $applicationCount; $i++) {
            $applications[] = [
                'client_name' => 'App Client '.$i,
                'client_reference' => 'APP'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'stage' => 'Offer',
                'url' => '/applications/'.$i,
            ];
        }

        return [
            'day_label' => 'Tuesday, 1 September 2026',
            'login_stats' => [],
            'caseload' => [
                'quiet_students' => $quietStudents,
                'inactive_students' => [],
                'active_clients_count' => 0,
                'leads_assigned_count' => 0,
                'owned_applications_open_count' => 0,
                'owned_applications_closed_count' => 0,
                'no_application_students_count' => 0,
                'quiet_students_count' => $quietCount,
                'inactive_students_count' => 0,
            ],
            'contact' => [
                'students' => [],
                'spoke_to_students_count' => 0,
                'met_students_count' => 0,
                'contacted_students_live_count' => 0,
                'spoke_to_colleges_count' => 0,
                'met_colleges_count' => 0,
            ],
            'throughput' => [
                'students' => [],
                'applications' => $applications,
                'worked_students_count' => 0,
                'worked_applications_count' => $applicationCount,
                'worked_colleges_count' => 0,
                'stage_moves_count' => 0,
                'actions_completed_count' => 0,
                'call_not_picked_count' => 0,
            ],
            'leads' => [
                'converted_today' => 0,
            ],
        ];
    }

    public function test_quiet_and_applications_lists_show_ten_rows_and_pagination(): void
    {
        $this->app->instance('request', Request::create('http://localhost/dashboard', 'GET'));

        $html = view('Admin.partials.my-day-panel', [
            'summary' => $this->summaryWithLists(12, 15),
            'embeddedOnDashboard' => true,
        ])->render();

        $this->assertSame(10, substr_count($html, 'Quiet Client '));
        $this->assertStringContainsString('Quiet Client 10', $html);
        $this->assertStringNotContainsString('Quiet Client 11', $html);
        $this->assertStringContainsString('quiet_page=2', $html);
        $this->assertStringContainsString('#quiet-inactive-clients', $html);

        $this->assertSame(10, substr_count($html, 'App Client '));
        $this->assertStringContainsString('App Client 10', $html);
        $this->assertStringNotContainsString('App Client 11', $html);
        $this->assertStringContainsString('apps_page=2', $html);
        $this->assertStringContainsString('#applications-worked-today', $html);

        $this->assertStringContainsString('Clients contacted today', $html);
        $this->assertStringContainsString('Clients worked on today', $html);
        $this->assertStringNotContainsString('?page=', $html);
        $this->assertStringNotContainsString('&page=', $html);
    }

    public function test_ten_or_fewer_rows_do_not_render_pagination_links(): void
    {
        $this->app->instance('request', Request::create('http://localhost/dashboard', 'GET'));

        $html = view('Admin.partials.my-day-panel', [
            'summary' => $this->summaryWithLists(10, 0),
            'embeddedOnDashboard' => true,
        ])->render();

        $this->assertSame(10, substr_count($html, 'Quiet Client '));
        $this->assertStringNotContainsString('quiet_page=', $html);
        $this->assertStringContainsString('No application activity logged today.', $html);
        $this->assertSame(ArrayPaginator::DEFAULT_PER_PAGE, 10);
    }

    public function test_second_quiet_page_keeps_applications_page_query(): void
    {
        $this->app->instance('request', Request::create('http://localhost/dashboard', 'GET', [
            'quiet_page' => 2,
            'apps_page' => 2,
        ]));

        $html = view('Admin.partials.my-day-panel', [
            'summary' => $this->summaryWithLists(12, 15),
            'embeddedOnDashboard' => true,
        ])->render();

        $this->assertStringContainsString('Quiet Client 11', $html);
        $this->assertStringContainsString('Quiet Client 12', $html);
        $this->assertStringNotContainsString('Quiet Client 10', $html);
        $this->assertStringContainsString('App Client 11', $html);
        $this->assertStringContainsString('apps_page=2', $html);
        $this->assertStringContainsString('quiet_page=1', $html);
    }
}
