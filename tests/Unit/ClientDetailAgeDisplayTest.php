<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\Client\ClientController;
use App\Models\Admin;
use Tests\TestCase;

class ClientDetailAgeDisplayTest extends TestCase
{
    private function controller(): ClientController
    {
        return new class extends ClientController
        {
            public function __construct()
            {
                // Skip auth middleware for unit tests.
            }

            public function applyForDisplay(Admin $record): void
            {
                $this->applyCalculatedAgeForDisplay($record);
            }
        };
    }

    public function test_age_is_computed_on_the_model_but_not_persisted(): void
    {
        $controller = $this->controller();
        $admin = new Admin;
        $admin->dob = '1990-06-15';
        $admin->age = 'stale value';

        $controller->applyForDisplay($admin);

        $this->assertSame($controller->calculateAge('1990-06-15'), $admin->age);
        $this->assertFalse($admin->exists);
        $this->assertFalse($admin->wasRecentlyCreated);
    }

    public function test_blank_dob_leaves_stored_age_unchanged(): void
    {
        $admin = new Admin;
        $admin->dob = '';
        $admin->age = 'keep me';

        $this->controller()->applyForDisplay($admin);

        $this->assertSame('keep me', $admin->age);
    }
}
