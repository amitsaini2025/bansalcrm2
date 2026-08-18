<?php

namespace Tests\Unit;

use App\Models\Application;
use App\Support\ClientDetailEagerLoads;
use Tests\TestCase;

class ClientDetailEagerLoadsTest extends TestCase
{
    public function test_numeric_ids_drop_blanks_and_keep_zero(): void
    {
        $ids = ClientDetailEagerLoads::numericIds([null, '', '12', 12, 'abc', 0, '0']);

        $this->assertSame([12, 0], $ids->values()->all());
    }

    public function test_staff_then_admin_by_ids_is_empty_when_no_ids(): void
    {
        $this->assertTrue(ClientDetailEagerLoads::staffThenAdminByIds([null, '', 'x'])->isEmpty());
        $this->assertTrue(ClientDetailEagerLoads::staffByIds([])->isEmpty());
    }

    public function test_open_action_counts_are_empty_when_no_application_ids(): void
    {
        $this->assertTrue(Application::openClientActionCountsByApplicationId(1, [])->isEmpty());
        $this->assertTrue(Application::openClientActionCountsByApplicationId(1, [null, ''])->isEmpty());
    }
}
