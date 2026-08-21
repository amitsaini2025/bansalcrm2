<?php

namespace Tests\Unit;

use App\Http\Controllers\AdminConsole\RecentlyModifiedClientsController;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class RecentlyModifiedClientsLatestActivityTest extends TestCase
{
    public function test_from_date_is_windowed_only_without_to_date_or_stale_years_filter(): void
    {
        $controller = new RecentlyModifiedClientsController;

        $this->assertSame('2026-01-01', $this->callPrivate($controller, 'latestActivitySubqueryFromDate', '2026-01-01', '', ''));
        $this->assertNull($this->callPrivate($controller, 'latestActivitySubqueryFromDate', '', '', ''));
        $this->assertNull($this->callPrivate($controller, 'latestActivitySubqueryFromDate', '2026-01-01', '2026-06-01', ''));
        $this->assertNull($this->callPrivate($controller, 'latestActivitySubqueryFromDate', '2026-01-01', '', '2'));
        $this->assertSame('2026-01-01', $this->callPrivate($controller, 'latestActivitySubqueryFromDate', '2026-01-01', '', '0'));
    }

    public function test_latest_activity_subquery_picks_newest_row_then_highest_id(): void
    {
        $controller = new RecentlyModifiedClientsController;
        $sql = strtolower($this->callPrivate($controller, 'latestActivityPerClientSubquery', '2026-01-01')->toSql());

        $this->assertStringContainsString('created_at', $sql);
        $this->assertStringContainsString('id', $sql);

        if (DB::connection()->getDriverName() === 'pgsql') {
            $this->assertStringContainsString('distinct on (client_id)', $sql);
        } else {
            $this->assertStringContainsString('row_number()', $sql);
        }
    }

    public function test_windowed_subquery_includes_from_date_and_unwindowed_does_not(): void
    {
        $controller = new RecentlyModifiedClientsController;

        $windowed = $this->callPrivate($controller, 'latestActivityPerClientSubquery', '2026-01-01');
        $this->assertContains('2026-01-01', $windowed->getBindings());

        $unwindowed = $this->callPrivate($controller, 'latestActivityPerClientSubquery', null);
        $this->assertNotContains('2026-01-01', $unwindowed->getBindings());
    }

    public function test_total_uses_storage_tab_counts_without_extra_query(): void
    {
        $controller = new RecentlyModifiedClientsController;
        $counts = ['local' => 2, 'both' => 6, 'aws' => 4539, 'storage' => 4556];

        $this->assertSame(9103, $this->callPrivate($controller, 'totalFromStorageCounts', $counts, ''));
        $this->assertSame(4539, $this->callPrivate($controller, 'totalFromStorageCounts', $counts, 'aws'));
        $this->assertSame(2, $this->callPrivate($controller, 'totalFromStorageCounts', $counts, 'local'));
        $this->assertSame(4556, $this->callPrivate($controller, 'totalFromStorageCounts', $counts, 'none'));
    }

    public function test_page_numbers_use_known_total_without_changing_current_page_items(): void
    {
        $controller = new RecentlyModifiedClientsController;
        $items = [(object) ['id' => 1], (object) ['id' => 2]];
        $simple = new Paginator($items, 10, 2);
        $request = Request::create('/adminconsole/recent-clients', 'GET', ['search' => 'kaur', 'page' => 2]);

        $lists = $this->callPrivate($controller, 'withPageNumbers', $simple, 25, $request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $lists);
        $this->assertSame(3, $lists->lastPage());
        $this->assertSame(2, $lists->currentPage());
        $this->assertCount(2, $lists->items());
        $this->assertTrue($lists->hasPages());
        $this->assertStringContainsString('search=kaur', $lists->url(1));
    }

    public function test_modified_by_sql_prefers_staff_then_admin(): void
    {
        $controller = new RecentlyModifiedClientsController;
        $first = $this->callPrivate($controller, 'modifiedByFirstNameSql');
        $last = $this->callPrivate($controller, 'modifiedByLastNameSql');
        $full = $this->callPrivate($controller, 'modifiedByFullNameSql');

        $this->assertStringContainsString('modifier_staff.first_name', $first);
        $this->assertStringContainsString('modifier_admins.first_name', $first);
        $this->assertLessThan(strpos($first, 'modifier_admins'), strpos($first, 'modifier_staff'));
        $this->assertStringContainsString('modifier_staff.last_name', $last);
        $this->assertStringContainsString('CONCAT(', $full);
        $this->assertStringContainsString($first, $full);
        $this->assertStringContainsString($last, $full);
    }

    private function callPrivate(object $controller, string $method, mixed ...$args): mixed
    {
        $ref = new ReflectionMethod($controller, $method);
        $ref->setAccessible(true);

        return $ref->invoke($controller, ...$args);
    }
}
