<?php

namespace Tests\Unit\Support;

use App\Support\ArrayPaginator;
use Illuminate\Http\Request;
use Tests\TestCase;

class ArrayPaginatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance('request', Request::create('http://localhost/dashboard', 'GET'));
    }

    public function test_first_page_shows_ten_items_by_default(): void
    {
        $items = range(1, 25);
        $paginator = ArrayPaginator::make($items, 'quiet_page');

        $this->assertSame(10, $paginator->perPage());
        $this->assertSame(25, $paginator->total());
        $this->assertCount(10, $paginator->items());
        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], $paginator->items());
        $this->assertTrue($paginator->hasPages());
    }

    public function test_second_page_uses_named_query_parameter(): void
    {
        $this->app->instance('request', Request::create('http://localhost/dashboard', 'GET', [
            'quiet_page' => 2,
            'apps_page' => 3,
        ]));

        $paginator = ArrayPaginator::make(range(1, 25), 'quiet_page', fragment: 'quiet-inactive-clients');

        $this->assertSame(2, $paginator->currentPage());
        $this->assertSame([11, 12, 13, 14, 15, 16, 17, 18, 19, 20], $paginator->items());
        $this->assertStringContainsString('quiet_page=1', $paginator->url(1));
        $this->assertStringContainsString('apps_page=3', $paginator->url(1));
        $this->assertStringContainsString('#quiet-inactive-clients', $paginator->url(1));
        $this->assertStringNotContainsString('?page=', $paginator->url(1));
        $this->assertStringNotContainsString('&page=', $paginator->url(1));
    }

    public function test_ten_or_fewer_items_do_not_show_pages(): void
    {
        $paginator = ArrayPaginator::make(range(1, 10), 'apps_page');

        $this->assertFalse($paginator->hasPages());
        $this->assertCount(10, $paginator->items());
    }

    public function test_empty_list_is_empty_and_has_no_pages(): void
    {
        $paginator = ArrayPaginator::make([], 'apps_page');

        $this->assertTrue($paginator->isEmpty());
        $this->assertFalse($paginator->hasPages());
        $this->assertSame(0, $paginator->total());
    }

    public function test_custom_per_page_shows_twenty_items(): void
    {
        $paginator = ArrayPaginator::make(range(1, 45), 'page', 20);

        $this->assertSame(20, $paginator->perPage());
        $this->assertCount(20, $paginator->items());
        $this->assertSame(45, $paginator->total());
        $this->assertSame(3, $paginator->lastPage());
        $this->assertTrue($paginator->hasPages());
    }
}
