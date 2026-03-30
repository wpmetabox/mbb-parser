# MBB Parser

## Overview
PHP library for parsing and unparsing Meta Box settings from the builder (provided by MB Builder plugin) into PHP array that Meta Box can use to register the meta boxes and fields.

## Build/Lint/Test Commands

### Lint
```bash
composer phpcs src
```

### Fix Auto-fixable Issues
```bash
composer phpcbf src
```

### No Tests
This project currently has no test suite configured.

## Code Style Guidelines

### General
- Follow WordPress Coding Standards (configured in `phpcs.xml`)
- PHP 7.4+ compatibility
- Use short array syntax `[]` instead of `array()`
- Use short echo tags `<?=` when appropriate
- Allow short ternary operator `?:`
- PSR-4 autoloading with namespace `MBBParser`

### Naming Conventions
- **Classes**: `PascalCase` (e.g., `MetaBox`, `SettingsTrait`)
- **Methods/Functions**: `snake_case` (e.g., `parse_boolean_values`)
- **Properties/Variables**: `snake_case` (e.g., `$id_prefix`)
- **Constants**: `UPPER_SNASE_CASE` (if any)
- **Namespaces**: `PascalCase` matching directory structure (e.g., `MBBParser\Parsers`)

### Formatting
- Indent with tabs (WordPress standard)
- Opening braces on same line for classes/functions
- Spaces inside parentheses: `function( $param )`
- Spaces around operators: `$a + $b`
- Single quotes for strings unless interpolation needed

### Imports
- Always use `use` statements at top of file
- Group related imports together
- Order: WordPress core, vendor packages, local project

```php
<?php
namespace MBBParser\Parsers;

use WP_REST_Server;           // WordPress
use MetaBox\Support\Arr;      // Vendor
use MBBParser\SettingsTrait;   // Local
```

### Type Declarations
- Use return type hints: `public function get_settings(): array`
- Use parameter type hints where appropriate
- PHP 7.4+ typed properties allowed

### Error Handling
- Use strict comparisons: `===` and `!==`
- Check existence with `isset()` before accessing array keys
- Return `$this` for method chaining (fluent interface)

### Comments
- Minimal comments - only when necessary
- Explain WHY, not WHAT
- No required file/class/function docblocks (excluded in phpcs.xml)
- Inline comments acceptable for complex logic

### Class Structure
- Declare namespace first
- Use statements next
- Class declaration
- Traits, properties, then methods

```php
<?php
namespace MBBParser;

use MBBParser\SettingsTrait;

class Example {
    use SettingsTrait;

    protected $settings;

    public function parse(): self {
        // implementation
        return $this;
    }
}
```

### WordPress Standards (with modifications)
- Follow WordPress coding standards
- **EXCEPT**: Use PSR-4 file naming (not WordPress naming)
- **EXCEPT**: Short array syntax allowed
- **EXCEPT**: Minimal docblocks required
- Minimum WordPress version: 5.9
- Minimum PHP version: 7.4
