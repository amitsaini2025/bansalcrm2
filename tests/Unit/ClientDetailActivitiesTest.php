<?php

namespace Tests\Unit;

use App\Support\ClientDetailActivities;
use Illuminate\Http\Request;
use Tests\TestCase;

class ClientDetailActivitiesTest extends TestCase
{
    public function test_page_size_is_capped_for_load_more(): void
    {
        $this->assertSame(25, ClientDetailActivities::PAGE_SIZE);
    }

    public function test_filters_from_request_use_activity_form_fields(): void
    {
        $request = Request::create('/clients/detail/1', 'GET', [
            'keyword' => 'visa',
            'activity_type' => 'notes',
            'date_from' => '01/08/2026',
            'date_to' => '17/08/2026',
        ]);

        $this->assertSame([
            'keyword' => 'visa',
            'activity_type' => 'notes',
            'date_from' => '01/08/2026',
            'date_to' => '17/08/2026',
        ], ClientDetailActivities::filtersFromRequest($request));
    }

    public function test_keyword_and_notes_type_are_applied_to_the_query(): void
    {
        $query = ClientDetailActivities::queryForClient(9, [
            'keyword' => 'visa',
            'activity_type' => 'notes',
            'date_from' => '',
            'date_to' => '',
        ]);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('client_id', $sql);
        $this->assertContains(9, $bindings);
        $this->assertContains('%visa%', $bindings);
        $this->assertContains('%added a note%', $bindings);
    }

    public function test_all_activity_type_does_not_add_subject_filters(): void
    {
        $sql = ClientDetailActivities::queryForClient(9, [
            'keyword' => '',
            'activity_type' => 'all',
            'date_from' => '',
            'date_to' => '',
        ])->toSql();

        $this->assertStringNotContainsString('like', $sql);
        $this->assertStringContainsString('order by', strtolower($sql));
    }
}
