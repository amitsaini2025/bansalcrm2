<?php

namespace Tests\Unit\Http\Controllers\AdminConsole;

use App\Http\Controllers\AdminConsole\StaffWorkloadController;
use App\Services\StaffWorkloadService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use ReflectionMethod;
use Tests\TestCase;

class StaffWorkloadControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance('request', Request::create('http://localhost/adminconsole/staff-workload', 'GET'));
    }

    public function test_team_overview_defaults_to_twenty_rows_per_page(): void
    {
        $rows = [];
        for ($i = 1; $i <= 25; $i++) {
            $rows[] = [
                'staff_id' => $i,
                'staff_name' => 'Staff '.$i,
                'staff_email' => 'staff'.$i.'@example.com',
            ];
        }

        $overview = $this->paginateTeamOverview([
            'day_label' => 'Tuesday, 1 September 2026',
            'rows' => $rows,
        ]);

        $this->assertInstanceOf(LengthAwarePaginator::class, $overview['rows']);
        $this->assertSame(StaffWorkloadController::TEAM_OVERVIEW_PER_PAGE, $overview['rows']->perPage());
        $this->assertSame(20, $overview['rows']->perPage());
        $this->assertSame(25, $overview['rows']->total());
        $this->assertCount(20, $overview['rows']->items());
        $this->assertSame('Staff 1', $overview['rows']->items()[0]['staff_name']);
        $this->assertSame('Staff 20', $overview['rows']->items()[19]['staff_name']);
        $this->assertTrue($overview['rows']->hasPages());
        $this->assertStringContainsString('page=2', $overview['rows']->url(2));
    }

    public function test_second_page_keeps_remaining_staff_and_view_ids(): void
    {
        $this->app->instance('request', Request::create('http://localhost/adminconsole/staff-workload', 'GET', [
            'page' => 2,
        ]));

        $rows = [];
        for ($i = 1; $i <= 25; $i++) {
            $rows[] = [
                'staff_id' => $i,
                'staff_name' => 'Staff '.$i,
            ];
        }

        $overview = $this->paginateTeamOverview(['rows' => $rows]);

        $this->assertSame(2, $overview['rows']->currentPage());
        $this->assertCount(5, $overview['rows']->items());
        $this->assertSame(21, $overview['rows']->items()[0]['staff_id']);
        $this->assertSame(25, $overview['rows']->items()[4]['staff_id']);
    }

    public function test_twenty_or_fewer_staff_do_not_show_pages(): void
    {
        $overview = $this->paginateTeamOverview([
            'rows' => array_fill(0, 20, ['staff_id' => 1, 'staff_name' => 'Staff']),
        ]);

        $this->assertFalse($overview['rows']->hasPages());
        $this->assertCount(20, $overview['rows']->items());
    }

    /**
     * @param  array{day_label?: string, rows?: list<array<string, mixed>>}  $teamOverview
     * @return array{day_label?: string, rows: LengthAwarePaginator}
     */
    private function paginateTeamOverview(array $teamOverview): array
    {
        $controller = new StaffWorkloadController(new StaffWorkloadService);
        $method = new ReflectionMethod(StaffWorkloadController::class, 'paginateTeamOverview');
        $method->setAccessible(true);

        return $method->invoke($controller, $teamOverview);
    }
}
