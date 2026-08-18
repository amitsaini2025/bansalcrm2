<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\Client\ClientController;
use App\Support\ClientDetailTab;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

class ClientDetailTabTest extends TestCase
{
    public function test_blank_tab_defaults_to_activities(): void
    {
        $resolved = ClientDetailTab::resolve(null, null, null);

        $this->assertSame('activities', $resolved['tab']);
        $this->assertSame('activities', $resolved['slug']);
    }

    public function test_aliases_and_email_tab_are_resolved(): void
    {
        $this->assertSame('noteterm', ClientDetailTab::resolve('notestrm')['tab']);
        $this->assertSame('alldocuments', ClientDetailTab::resolve(null, 'documents')['tab']);
        $this->assertSame('email-v2', ClientDetailTab::resolve('email-v2')['tab']);
        $this->assertSame('accounts', ClientDetailTab::resolve(null, null, 'accounts')['tab']);
    }

    public function test_unknown_tab_falls_back_to_activities(): void
    {
        $this->assertSame('activities', ClientDetailTab::resolve('not-a-tab')['tab']);
    }

    public function test_client_applications_are_not_queried_when_accounts_tab_is_active(): void
    {
        $controller = new class extends ClientController
        {
            public function __construct() {}

            public function peek(Request $request, $forcedTab, int $clientId)
            {
                return $this->clientApplicationsForActiveDetailTab($request, $forcedTab, $clientId);
            }
        };

        $request = Request::create('/clients/detail/abc/accounts', 'GET');
        $route = new Route(['GET'], 'clients/detail/{id}/{tab?}', fn () => null);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $this->assertNull($controller->peek($request, 'accounts', 1));
        $this->assertNull($controller->peek($request, 'email-v2', 1));
        $this->assertNull($controller->peek($request, 'activities', 1));
    }

    public function test_detail_blade_keeps_tab_shells_and_skips_hidden_pane_queries(): void
    {
        $blade = file_get_contents(resource_path('views/Admin/clients/detail.blade.php'));

        $this->assertNotFalse($blade);
        $this->assertStringContainsString('id="activities"', $blade);
        $this->assertStringContainsString('class="applicationtdata"', $blade);
        $this->assertStringContainsString('class="tdata alldocumnetlist"', $blade);
        $this->assertStringContainsString('class="note_term_list"', $blade);
        $this->assertStringContainsString('class="tdata invoicedatalist"', $blade);
        $this->assertStringContainsString('id="email-v2"', $blade);
        $this->assertStringContainsString("@if(\$activeTab === 'activities')", $blade);
        $this->assertStringContainsString("@if(\$activeTab === 'application')", $blade);
        $this->assertStringContainsString("@if(\$activeTab === 'alldocuments')", $blade);
        $this->assertStringContainsString("@if(\$activeTab === 'noteterm')", $blade);
        $this->assertStringContainsString("@if(\$activeTab === 'accounts')", $blade);
        $this->assertStringContainsString('activities-load-more', $blade);
    }
}
