<?php

namespace LouisStanley\Ci4RouteChecker\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Router\DefinedRouteCollector;
use Config\Services;
use Exception;
use ReflectionMethod;

class CheckRoutes extends BaseCommand
{
    protected $group       = 'routes';
    protected $name        = 'routes:check';
    protected $description = 'Check all defined routes for missing controllers, methods, and potential issues.';

    public function run(array $params)
    {
        $collection            = Services::routes()->loadRoutes();
        $definedRouteCollector = new DefinedRouteCollector($collection);

        CLI::write("Checking defined routes...\n", 'yellow');

        $invalidRoutes = [];
        $warnings      = [];

        foreach ($definedRouteCollector->collect() as $route) {
            $handler = $route['handler'];

            // Handle closure routes

            // TODO handle closures
            if ($handler === '(Closure)') {
                $warnings[] = [
                    'route'   => $route,
                    'warning' => 'Closure found: ' . $route['route'],
                ];

                continue;
            }

            // Check standard Controller::method routes
            $this->checkStandardRoute($handler, $route, $invalidRoutes, $warnings);
        }

        $this->displayResults($warnings, $invalidRoutes);
    }

    private function checkStandardRoute(string $handler, array $route, array &$invalidRoutes, array &$warning = [])
    {
        [$controller, $method] = explode('::', $handler);
        $method                = strtok($method, '/') ?: $method;

        $params = [];

        while ($param = strtok('/')) {
            $params[] = $param;  // Add each subsequent word to the params array
        }

        try {
            $controllerExists = class_exists($controller);
            $methodExists     = $controllerExists && method_exists($controller, $method);

            if (! $controllerExists) {
                $invalidRoutes[] = [
                    'route' => $route,
                    'error' => 'Controller not found: ' . $controller,
                ];
            }

            if (! $methodExists) {
                $invalidRoutes[] = [
                    'route' => $route,
                    'error' => sprintf('Method not found: %s::%s', $controller, $method),
                ];
            }

            $constructor = new ReflectionMethod($controller . '::' . $method);
            $parameters  = $constructor->getParameters();

            // TODO make optional as error or warning with config
            if (count($parameters) !== count($params)) {
                $warning[] = [
                    'route'   => $route,
                    'warning' => sprintf('Parameter count mismatch: %s::%s', $controller, $method),
                ];
            }
        } catch (Exception $e) {
            $invalidRoutes[] = [
                'route' => $route,
                'error' => 'Error checking route: ' . $e->getMessage(),
            ];
        }
    }

    private function displayResults(array $warnings, array $invalidRoutes)
    {
        if (! empty($warnings)) {
            CLI::write("Warnings found:\n", 'yellow');

            foreach ($warnings as $warning) {
                CLI::write('- Route: ' . $warning['route']['route'], 'white');
                CLI::write('  Warning: ' . $warning['warning'], 'yellow');
            }
        }

        if (empty($invalidRoutes)) {
            CLI::write('All routes are valid!', 'green');

            exit(0);
        }

        CLI::write("Invalid routes found:\n", 'red');

        foreach ($invalidRoutes as $invalidRoute) {
            CLI::write('- Route: ' . $invalidRoute['route']['route'], 'white');
            CLI::write('  Error: ' . $invalidRoute['error'], 'red');
        }

        exit(1);
    }
}
