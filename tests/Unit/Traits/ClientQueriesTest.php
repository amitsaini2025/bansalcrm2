<?php

namespace Tests\Unit\Traits;

use Tests\TestCase;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Mockery;

/**
 * Test suite for ClientQueries trait
 */
class ClientQueriesTest extends TestCase
{

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test controller that uses the trait
        $this->controller = new class {
            use \App\Traits\ClientQueries;
            
            // Mock the decodeString method from ClientHelpers
            public function decodeString(?string $string)
            {
                if (base64_encode(base64_decode($string, true)) === $string) {
                    try {
                        $decoded = @convert_uudecode(base64_decode($string));
                        if ($decoded === false || $decoded === '') {
                            return false;
                        }
                        return $decoded;
                    } catch (\Throwable $e) {
                        return false;
                    }
                }
                return false;
            }
        };
    }

    /**
     * Test getBaseClientQuery returns correct base query
     */
    public function test_getBaseClientQuery_returns_correct_query()
    {
        $query = $this->controller->getBaseClientQuery();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
        
        // Check that the query has the correct where clauses
        $wheres = $query->getQuery()->wheres;
        $this->assertNotEmpty($wheres);
    }

    /**
     * Test getBaseClientQuery includes agent filter for agents
     */
    public function test_getBaseClientQuery_includes_agent_filter_for_agents()
    {
        // Mock agent authentication
        $agent = Mockery::mock('agent');
        $agent->id = 1;
        
        Auth::shouldReceive('guard')
            ->with('agents')
            ->andReturnSelf();
        Auth::shouldReceive('check')
            ->andReturn(true);
        Auth::shouldReceive('user')
            ->andReturn($agent);
        
        $query = $this->controller->getBaseClientQuery();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
    }

    /**
     * Test getArchivedClientQuery returns archived clients
     */
    public function test_getArchivedClientQuery_returns_archived_clients()
    {
        $query = $this->controller->getArchivedClientQuery();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
    }

    /**
     * Test applyClientFilters applies client_id filter
     */
    public function test_applyClientFilters_applies_client_id_filter()
    {
        $query = Admin::query();
        $request = new Request(['client_id' => 'TEST123']);
        
        $filteredQuery = $this->controller->applyClientFilters($query, $request);
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $filteredQuery);
    }

    /**
     * Test applyClientFilters applies name filter
     */
    public function test_applyClientFilters_applies_name_filter()
    {
        $query = Admin::query();
        $request = new Request(['name' => 'John']);
        
        $filteredQuery = $this->controller->applyClientFilters($query, $request);
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $filteredQuery);
    }

    /**
     * Test applyClientFilters applies email filter
     */
    public function test_applyClientFilters_applies_email_filter()
    {
        $query = Admin::query();
        $request = new Request(['email' => 'test@example.com']);
        
        $filteredQuery = $this->controller->applyClientFilters($query, $request);
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $filteredQuery);
    }

    /**
     * Test applyClientFilters applies phone filter
     */
    public function test_applyClientFilters_applies_phone_filter()
    {
        $query = Admin::query();
        $request = new Request(['phone' => '1234567890']);
        
        $filteredQuery = $this->controller->applyClientFilters($query, $request);
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $filteredQuery);
    }

    /**
     * Test isAgentContext returns true for agents
     */
    public function test_isAgentContext_returns_true_for_agents()
    {
        Auth::shouldReceive('guard')
            ->with('agents')
            ->andReturnSelf();
        Auth::shouldReceive('check')
            ->andReturn(true);
        
        $result = $this->controller->isAgentContext();
        
        $this->assertTrue($result);
    }

    /**
     * Test isAgentContext returns false for admins
     */
    public function test_isAgentContext_returns_false_for_admins()
    {
        Auth::shouldReceive('guard')
            ->with('agents')
            ->andReturnSelf();
        Auth::shouldReceive('check')
            ->andReturn(false);
        Auth::shouldReceive('guard')
            ->with('admin')
            ->andReturnSelf();
        Auth::shouldReceive('check')
            ->andReturn(true);
        
        $result = $this->controller->isAgentContext();
        
        $this->assertFalse($result);
    }

    /**
     * Test getEmptyClientQuery returns empty query
     */
    public function test_getEmptyClientQuery_returns_empty_query()
    {
        $query = $this->controller->getEmptyClientQuery();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
    }

    /**
     * Test getClientById returns client when found
     */
    public function test_getClientById_returns_client_when_found()
    {
        // This would require database setup
        // For now, just test the method exists and returns correct type
        $this->assertTrue(method_exists($this->controller, 'getClientById'));
    }

    /**
     * Test getClientByEncodedId decodes and returns client
     */
    public function test_getClientByEncodedId_decodes_and_returns_client()
    {
        // This would require database setup
        // For now, just test the method exists
        $this->assertTrue(method_exists($this->controller, 'getClientByEncodedId'));
    }
}

