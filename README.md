# CI4 Route Checker

## Overview
`Ci4RouteChecker` is a CodeIgniter 4 CLI command that checks all defined routes for missing controllers, methods, and potential issues. It helps developers identify incorrect route configurations in their applications.

## Requirements
- CodeIgniter 4.3+
- PHP 7.4+

## Installation
You can install this package via Composer:

```sh
composer require louisstanley/ci4-route-checker
```

After installation, the command will be available for use within your CodeIgniter 4 project.

## Usage
Run the following command in your project root to check all defined routes:

```sh
php spark routes:check
```

## Features
- Checks for missing controllers.
- Checks for missing methods in controllers.
- Detects closure-based routes (currently marked as warnings).
- Identifies mismatches in method parameter counts (can be configured as a warning or error in the future).
- Displays warnings and errors in a structured format.

## Output
- **Warnings:** Highlight potential issues, such as closure routes or parameter mismatches.
- **Errors:** Indicate invalid routes where controllers or methods are missing.
- **Success Message:** Confirms all routes are correctly configured if no issues are found.

## Example Output
```
Checking defined routes...
Warnings found:
- Route: /example/closure
  Warning: Closure found: /example/closure
Invalid routes found:
- Route: /missing-controller
  Error: Controller not found: App\Controllers\MissingController
- Route: /missing-method
  Error: Method not found: App\Controllers\ExampleController::missingMethod
```

## License
This project is licensed under the MIT License.

## Author
Developed by Louis Stanley.

