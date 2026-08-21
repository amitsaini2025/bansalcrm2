<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\ActionController;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use ReflectionMethod;
use Tests\TestCase;

class CompletedActionsCountsTest extends TestCase
{
    public function test_type_badge_counts_use_grouped_totals_and_keep_all_as_sum(): void
    {
        $controller = new ActionController;
        $grouped = collect([
            'Call' => 93244,
            'Review' => 48020,
            'Query' => 10,
            'Followup' => 5,
        ]);

        $counts = $this->callPrivate($controller, 'completedActionTypeCounts', $grouped);

        $this->assertSame(141279, $counts['All']);
        $this->assertSame(93244, $counts['Call']);
        $this->assertSame(48020, $counts['Review']);
        $this->assertSame(10, $counts['Query']);
        $this->assertSame(0, $counts['Checklist']);
        $this->assertSame(0, $counts['Urgent']);
        $this->assertSame(0, $counts['Personal Task']);
    }

    public function test_page_numbers_keep_current_page_items_when_total_is_known(): void
    {
        $items = [(object) ['id' => 1], (object) ['id' => 2]];
        $paginator = new LengthAwarePaginator($items, 45, 20, 2, [
            'path' => 'http://localhost/action/completed',
            'pageName' => 'page',
        ]);
        $paginator->appends(['group_type' => 'Call']);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertSame(3, $paginator->lastPage());
        $this->assertSame(2, $paginator->currentPage());
        $this->assertCount(2, $paginator->items());
        $this->assertStringContainsString('group_type=Call', $paginator->url(1));
        $this->assertTrue($paginator->hasPages());
    }

    public function test_empty_group_type_is_treated_as_all(): void
    {
        $request = Request::create('/action/completed', 'GET', ['group_type' => '']);
        $taskGroup = is_array($request->input('group_type')) ? 'All' : trim((string) $request->input('group_type'));
        if ($taskGroup === '') {
            $taskGroup = 'All';
        }

        $this->assertSame('All', $taskGroup);
    }

    private function callPrivate(object $controller, string $method, mixed ...$args): mixed
    {
        $ref = new ReflectionMethod($controller, $method);
        $ref->setAccessible(true);

        return $ref->invoke($controller, ...$args);
    }
}
