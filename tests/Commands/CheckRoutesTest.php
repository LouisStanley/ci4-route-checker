<?php

namespace App\Tests;

use CodeIgniter\Router\RouteCollection;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use LouisStanley\Ci4RouteChecker\Commands\CheckRoutes;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class CheckRoutesTest extends CIUnitTestCase
{
    /**
     * @var MockObject|RouteCollection
     */
    protected $mockRouteCollection;

    /**
     * Set up the environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Mock RouteCollection to simulate loaded routes
        $this->mockRouteCollection = $this->createMock(RouteCollection::class);

        // Mock the Services::routes to return our mock
        Services::mock('routes', $this->mockRouteCollection);

        // Mock class_exists and method_exists to avoid errors
        $this->mockClassExists();
        $this->mockMethodExists();
    }

    /**
     * Mock class_exists function for testing purposes.
     */
    protected function mockClassExists()
    {
        $this->getMockBuilder('stdClass')
            ->getMock()
            ->method('class_exists')
            ->willReturnCallback(static function ($class) {
                // Simulate that a controller class does or does not exist
                return ! ($class === 'App\Controllers\NonExistentController');
                // Simulate non-existent controller// Simulate existing controller
            });
    }

    /**
     * Mock method_exists function for testing purposes.
     */
    protected function mockMethodExists()
    {
        $this->getMockBuilder('stdClass')
            ->getMock()
            ->method('method_exists')
            ->willReturnCallback(static function ($object, $method) {
                // Simulate that a method exists or not
                return ! ($method === 'nonExistentMethod');
                // Simulate method does not exist// Simulate method exists
            });
    }

    /**
     * Test command for valid routes.
     */
    public function testValidRoutes()
    {
        $this->mockRouteCollection
            ->method('loadRoutes')
            ->willReturn([
                [
                    'route'   => 'test/route',
                    'handler' => 'App\Controllers\TestController::method',
                ],
            ]);

        // Capture the output of the command
        $output = $this->runCommand('routes:check');

        // Assert the output contains success message
        $this->assertStringContainsString('All routes are valid!', $output);
    }

    /**
     * Test command for invalid routes (missing controller).
     */
    public function testInvalidRouteController()
    {
        $this->mockRouteCollection
            ->method('loadRoutes')
            ->willReturn([
                [
                    'route'   => 'test/route',
                    'handler' => 'App\Controllers\NonExistentController::method',
                ],
            ]);

        // Capture the output of the command
        $output = $this->runCommand('routes:check');

        // Assert the output contains error message for missing controller
        $this->assertStringContainsString('Controller not found: App\Controllers\NonExistentController', $output);
    }

    /**
     * Test command for invalid routes (missing method).
     */
    public function testInvalidRouteMethod()
    {
        $this->mockRouteCollection
            ->method('loadRoutes')
            ->willReturn([
                [
                    'route'   => 'test/route',
                    'handler' => 'App\Controllers\TestController::nonExistentMethod',
                ],
            ]);

        // Capture the output of the command
        $output = $this->runCommand('routes:check');

        // Assert the output contains error message for missing method
        $this->assertStringContainsString('Method not found: App\Controllers\TestController::nonExistentMethod', $output);
    }

    /**
     * Run the command via Console.
     */
    protected function runCommand(string $command, array $params = []): string
    {
        // Create the CheckRoutes command and inject the dependencies
        $commandInstance = new CheckRoutes();

        // Capture output of the command
        ob_start();
        $commandInstance->run($params);

        return ob_get_clean();
    }
}
