<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\Client\ClientController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class ClientDetailLeadRedirectTest extends TestCase
{
    private function controller(): ClientController
    {
        return new class extends ClientController
        {
            public function __construct()
            {
                // Skip auth middleware for unit tests.
            }

            public function redirect(string $routeName, array $routeParams, Request $request): RedirectResponse
            {
                return $this->redirectToMatchingLeadDetail($routeName, $routeParams, $request);
            }
        };
    }

    public function test_phpunit_uses_in_memory_sqlite_not_application_database(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }

    public function test_lead_detail_redirect_uses_encoded_id(): void
    {
        $encodedId = 'JS0zLFYsM0BgCmAK';
        $request = Request::create('/clients/detail/'.$encodedId, 'GET');

        $response = $this->controller()->redirect('leads.detail', ['id' => $encodedId], $request);

        $this->assertSame(route('leads.detail', ['id' => $encodedId]), $response->getTargetUrl());
    }

    public function test_query_string_is_copied_onto_lead_detail_url(): void
    {
        $encodedId = 'JS0zLFYsM0BgCmAK';
        $request = Request::create('/clients/detail/'.$encodedId, 'GET', ['t' => '99']);

        $response = $this->controller()->redirect('leads.detail', ['id' => $encodedId], $request);

        $this->assertSame(
            route('leads.detail', ['id' => $encodedId, 't' => '99']),
            $response->getTargetUrl()
        );
    }

    public function test_query_tab_stays_on_query_string_not_path(): void
    {
        $encodedId = 'JS0zLFYsM0BgCmAK';
        $request = Request::create('/clients/detail/'.$encodedId, 'GET', ['tab' => 'notes']);

        $response = $this->controller()->redirect('leads.detail', ['id' => $encodedId], $request);

        $this->assertSame(
            route('leads.detail', ['id' => $encodedId]).'?tab=notes',
            $response->getTargetUrl()
        );
    }

    public function test_application_redirect_keeps_application_id(): void
    {
        $encodedId = 'JS0zLFYsM0BgCmAK';
        $request = Request::create('/clients/detail/'.$encodedId.'/application/12', 'GET');

        $response = $this->controller()->redirect(
            'leads.detail.application',
            ['id' => $encodedId, 'applicationId' => 12],
            $request
        );

        $this->assertSame(
            route('leads.detail.application', ['id' => $encodedId, 'applicationId' => 12]),
            $response->getTargetUrl()
        );
    }
}
