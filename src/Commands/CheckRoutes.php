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
    protected $config;
    private $warnings = [];
    private $errors   = [];

    public function run(array $params)
    {
        $this->config = config('RouteChecker');

        $collection            = Services::routes()->loadRoutes();
        $definedRouteCollector = new DefinedRouteCollector($collection);

        CLI::write("Checking defined routes...\n", 'yellow');

        foreach ($definedRouteCollector->collect() as $route) {
            $handler = $route['handler'];

            if (! empty($this->config->ignoredRoutes)) {
                foreach ($this->config->ignoredRoutes as $pattern) {
                    if ($this->filterRoute($pattern, $route['route'])) {
                        continue 2;
                    }
                }
            }

            // TODO handle closures
            if ($handler === '(Closure)') {
                $this->setWarning($route['route'], 'Closure found: ' . $route['route']);

                continue;
            }

            $this->checkStandardRoute($handler, $route);
        }

        $this->displayResults();
    }

    private function filterRoute(string $pattern, string $route): bool
    {
        $pattern = preg_quote($pattern, '/');
        $pattern = str_replace('\*', '.*', $pattern);

        return (bool) preg_match("/^{$pattern}$/", $route);
    }

    private function checkStandardRoute(string $handler, array $route)
    {
        [$controller, $method] = explode('::', $handler);
        $method                = strtok($method, '/') ?: $method;

        $params = [];

        while ($param = strtok('/')) {
            $params[] = $param;
        }

        try {
            $controllerExists = class_exists($controller);
            $methodExists     = $controllerExists && method_exists($controller, $method);

            if (! $controllerExists) {
                $this->setError($route['route'], 'Controller not found: ' . $controller);

                return;
            }

            if (! $methodExists) {
                $this->setError($route['route'], sprintf('Method not found: %s::%s', $controller, $method));

                return;
            }

            $constructor = new ReflectionMethod($controller . '::' . $method);
            $parameters  = $constructor->getParameters();

            if (count($parameters) !== count($params)) {
                if ($this->config->treatParameterMismatchAsError) {
                    $this->setError($route['route'], sprintf('Parameter count mismatch: %s::%s', $controller, $method));

                    return;
                }

                $this->setWarning($route['route'], sprintf('Parameter count mismatch: %s::%s', $controller, $method));
            }
        } catch (Exception $e) {
            $this->setError($route['route'], 'Error checking route: ' . $e->getMessage());
        }
    }

    private function displayResults()
    {
        if (! empty($this->warnings)) {
            CLI::write("Warnings found:\n", 'yellow');

            foreach ($this->warnings as $warning) {
                CLI::write('- Route: ' . $warning['route'], 'white');
                CLI::write('  Warning: ' . $warning['warning'], 'yellow');
            }
        }

        if (empty($this->errors)) {
            CLI::write('All routes are valid!', 'green');

            exit(0);
        }

        CLI::write("Invalid routes found:\n", 'red');

        foreach ($this->errors as $invalidRoute) {
            CLI::write('- Route: ' . $invalidRoute['route'], 'white');
            CLI::write('  Error: ' . $invalidRoute['error'], 'red');
        }

        exit(1);
    }

    private function setWarning(string $route, string $warning)
    {
        $this->warnings[] = [
            'route'   => $route,
            'warning' => $warning,
        ];
    }

    private function setError(string $route, string $error)
    {
        $this->errors[] = [
            'route' => $route,
            'error' => $error,
        ];
    }
}
