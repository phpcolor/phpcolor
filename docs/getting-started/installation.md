# Installation

PHPColor is a modern, zero-dependency PHP library for color manipulation. It requires **PHP 8.3 or higher**.

## Requirements

- **PHP**: ^8.3
- **Extensions**: None required (uses standard math and string functions)

## Installation via Composer

Install PHPColor using [Composer](https://getcomposer.org/):

```bash
composer require phpcolor/phpcolor
```

## Autoloading

The library follows the PSR-4 standard and uses Composer's autoloader. Ensure you include the `vendor/autoload.php` file in your project:

```php
require_once __DIR__ . '/vendor/autoload.php';

use PhpColor\Color\Color;

$color = Color::parse('#3b82f6');
```

## Versioning

PHPColor follows [Semantic Versioning 2.0.0](https://semver.org/).

- **Major** versions for breaking changes.
- **Minor** versions for new features and non-breaking improvements.
- **Patch** versions for bug fixes.

## Next Steps

Check out the [Quickstart](quickstart.md) to start using the library.
