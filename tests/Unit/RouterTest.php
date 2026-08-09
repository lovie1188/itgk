<?php
use PHPUnit\Framework\TestCase;
use App\Core\Router;

class RouterTest extends TestCase {
    
    public function testAddRoute() {
        $router = new Router();
        $router->add('GET', '/test', 'TestController', 'index');
        
        $reflection = new ReflectionClass($router);
        $property = $reflection->getProperty('routes');
        $property->setAccessible(true);
        $routes = $property->getValue($router);
        
        $this->assertIsArray($routes);
        $this->assertNotEmpty($routes);
        $this->assertEquals('GET', $routes[0]['method']);
        $this->assertEquals('/test', $routes[0]['path']);
        $this->assertEquals('TestController', $routes[0]['controller']);
    }

    /*
    public function testDispatchMatchesRoute() {
        // Dispatching requires headers/output buffering tricks or refactoring to return response.
        // For now we test logic add.
    }
    */
}
