<?php

namespace LouisStanley\Ci4RouteChecker\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\Publisher\Publisher;
use Throwable;

class Publish extends BaseCommand
{
    protected $group       = 'routes';
    protected $name        = 'routes:publish-config';
    protected $description = 'Publish Route check config into the current application.';

    public function run(array $params)
    {
        // Use the Autoloader to figure out the module path
        $source = service('autoloader')->getNamespace('LouisStanley\\Ci4RouteChecker')[0];

        $publisher = new Publisher($source, APPPATH);

        try {
            // Add only the desired components
            $publisher->addPaths([
                'Config/RouteChecker.php',
            ])->merge(false); // Be careful not to overwrite anything
        } catch (Throwable $e) {
            $this->showError($e);

            return;
        }

        // If publication succeeded then update namespaces
        foreach ($publisher->getPublished() as $file) {
            // Replace the namespace
            $contents = file_get_contents($file);
            $contents = str_replace('namespace LouisStanley\\Ci4RouteChecker\\Config', 'namespace Config', $contents);
            file_put_contents($file, $contents);
        }
    }
}
