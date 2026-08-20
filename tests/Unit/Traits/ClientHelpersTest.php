<?php

namespace Tests\Unit\Traits;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use Mockery;

/**
 * Test suite for ClientHelpers trait
 */
class ClientHelpersTest extends TestCase
{

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test controller that uses the trait
        $this->controller = new class {
            use \App\Traits\ClientHelpers;
            
            // Mock uploadFile and unlinkFile from base Controller
            public function uploadFile($file, $path)
            {
                return 'uploaded_file.jpg';
            }
            
            public function unlinkFile($file, $path)
            {
                return true;
            }
        };
    }

    /**
     * Test formatDateForDatabase converts DD/MM/YYYY to YYYY-MM-DD
     */
    public function test_formatDateForDatabase_converts_date_format()
    {
        $result = $this->controller->formatDateForDatabase('25/12/2023');
        
        $this->assertEquals('2023-12-25', $result);
    }

    /**
     * Test formatDateForDatabase returns null for empty input
     */
    public function test_formatDateForDatabase_returns_null_for_empty_input()
    {
        $result = $this->controller->formatDateForDatabase(null);
        
        $this->assertNull($result);
    }

    /**
     * Test formatDateForDatabase returns null for invalid format
     */
    public function test_formatDateForDatabase_returns_null_for_invalid_format()
    {
        $result = $this->controller->formatDateForDatabase('2023-12-25');
        
        $this->assertNull($result);
    }

    /**
     * Test formatDateForDisplay converts YYYY-MM-DD to DD/MM/YYYY
     */
    public function test_formatDateForDisplay_converts_date_format()
    {
        $result = $this->controller->formatDateForDisplay('2023-12-25');
        
        $this->assertEquals('25/12/2023', $result);
    }

    /**
     * Test formatDateForDisplay returns null for empty input
     */
    public function test_formatDateForDisplay_returns_null_for_empty_input()
    {
        $result = $this->controller->formatDateForDisplay(null);
        
        $this->assertNull($result);
    }

    /**
     * Test processRelatedFiles processes array correctly
     */
    public function test_processRelatedFiles_processes_array_correctly()
    {
        $request = new Request([
            'related_files' => ['file1.pdf', 'file2.jpg', 'file3.docx']
        ]);
        
        $result = $this->controller->processRelatedFiles($request);
        
        $this->assertEquals('file1.pdf,file2.jpg,file3.docx', $result);
    }

    /**
     * Test processRelatedFiles returns empty string for no files
     */
    public function test_processRelatedFiles_returns_empty_string_for_no_files()
    {
        $request = new Request();
        
        $result = $this->controller->processRelatedFiles($request);
        
        $this->assertEquals('', $result);
    }

    /**
     * Test processFollowers processes array correctly
     */
    public function test_processFollowers_processes_array_correctly()
    {
        $request = new Request([
            'followers' => [1, 2, 3]
        ]);
        
        $result = $this->controller->processFollowers($request);
        
        $this->assertEquals('1,2,3', $result);
    }

    /**
     * Test processTags processes array correctly
     */
    public function test_processTags_processes_array_correctly()
    {
        $request = new Request([
            'tagname' => ['tag1', 'tag2', 'tag3']
        ]);
        
        $result = $this->controller->processTags($request);
        
        $this->assertEquals('tag1,tag2,tag3', $result);
    }

    /**
     * Test generateClientId generates correct format
     */
    public function test_generateClientId_generates_correct_format()
    {
        $result = $this->controller->generateClientId('John', 123);
        
        $this->assertStringStartsWith('JOHN', $result);
        $this->assertStringEndsWith('123', $result);
    }

    /**
     * Test getClientValidationRules returns correct rules for store
     */
    public function test_getClientValidationRules_returns_correct_rules_for_store()
    {
        $request = new Request();
        
        $rules = $this->controller->getClientValidationRules($request);
        
        $this->assertArrayHasKey('first_name', $rules);
        $this->assertArrayHasKey('last_name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('phone', $rules);
    }

    /**
     * Test getClientValidationRules includes client_id for update
     */
    public function test_getClientValidationRules_includes_client_id_for_update()
    {
        $request = new Request();
        
        $rules = $this->controller->getClientValidationRules($request, 1);
        
        $this->assertArrayHasKey('client_id', $rules);
    }

    /**
     * Test getClientViewPath returns agent path for agents
     */
    public function test_getClientViewPath_returns_agent_path_for_agents()
    {
        Auth::shouldReceive('guard')
            ->with('agents')
            ->andReturnSelf();
        Auth::shouldReceive('check')
            ->andReturn(true);
        
        $result = $this->controller->getClientViewPath('clients.index');
        
        $this->assertEquals('Agent.clients.index', $result);
    }

    /**
     * Test getClientViewPath returns admin path for admins
     */
    public function test_getClientViewPath_returns_admin_path_for_admins()
    {
        Auth::shouldReceive('guard')
            ->with('agents')
            ->andReturnSelf();
        Auth::shouldReceive('check')
            ->andReturn(false);
        
        $result = $this->controller->getClientViewPath('clients.index');
        
        $this->assertEquals('Admin.clients.index', $result);
    }

    /**
     * Test encodeString encodes correctly
     */
    public function test_encodeString_encodes_correctly()
    {
        $result = $this->controller->encodeString('123');
        
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    /**
     * Test decodeString decodes correctly
     */
    public function test_decodeString_decodes_correctly()
    {
        $encoded = $this->controller->encodeString('123');
        $decoded = $this->controller->decodeString($encoded);
        
        $this->assertEquals('123', $decoded);
    }

    /**
     * Test decodeString returns false for invalid input
     */
    public function test_decodeString_returns_false_for_invalid_input()
    {
        $result = $this->controller->decodeString('invalid_string');
        
        $this->assertFalse($result);
    }
}

