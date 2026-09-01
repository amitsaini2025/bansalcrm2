<?php

namespace Tests\Unit\Support;

use App\Support\StaffAllocationScope;
use Tests\TestCase;

class StaffAllocationScopeTest extends TestCase
{
    public function test_staff_matches_single_assignee_id(): void
    {
        $this->assertTrue(StaffAllocationScope::staffMatchesAssigneeValue('5', 5));
        $this->assertFalse(StaffAllocationScope::staffMatchesAssigneeValue('5', 6));
    }

    public function test_staff_matches_comma_separated_assignee(): void
    {
        $this->assertTrue(StaffAllocationScope::staffMatchesAssigneeValue('1,5,1215', 5));
        $this->assertTrue(StaffAllocationScope::staffMatchesAssigneeValue('5,10', 5));
        $this->assertTrue(StaffAllocationScope::staffMatchesAssigneeValue('10,5', 5));
        $this->assertFalse(StaffAllocationScope::staffMatchesAssigneeValue('1,10', 5));
    }

    public function test_empty_assignee_does_not_match(): void
    {
        $this->assertFalse(StaffAllocationScope::staffMatchesAssigneeValue('', 5));
        $this->assertFalse(StaffAllocationScope::staffMatchesAssigneeValue(null, 5));
    }
}
