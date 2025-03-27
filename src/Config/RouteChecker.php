<?php

namespace LouisStanley\Ci4RouteChecker\Config;

use CodeIgniter\Config\BaseConfig;

class RouteChecker extends BaseConfig
{
    /**
     * Whether to treat a parameter mismatch as an error.
     *
     * If true, a parameter mismatch will be treated as an error.
     * If false, a parameter mismatch will be treated as a warning.
     */
    public bool $treatParameterMismatchAsError = false;

    /**
     * Routes to ignore.
     *
     * @var array
     *
     * Example:
     *
     * public $ignoredRoutes = [
     *    'admin/*',
     *    'api/*',
     * ];
     */
    public array $ignoredRoutes = [];
}
