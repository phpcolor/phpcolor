# Parsing Colors

PHPColor provides several entry points for converting strings and other inputs into concrete color objects.

## The `Color::parse()` Method

This is the most common way to create a color. It supports all standard CSS notations, hexadecimal strings, and modern color functions.

```php
use PhpColor\Color\Color;

$c1 = Color::parse('#ff6600');
$c2 = Color::parse('rgb(255 0 0)');
$c3 = Color::parse('oklch(60% 0.15 250)');
```

## Normalization with `Color::from()`

If you have a value that might already be a `ColorInterface` or a string, `Color::from()` is the safest way to ensure you have a color object.

```php
$color = Color::from($input); // $input can be a string or ColorInterface
```

## Safe Parsing with `Color::tryFrom()`

To avoid exceptions, use `tryFrom()`, which returns `null` if the input cannot be parsed.

```php
$color = Color::tryFrom('invalid'); // Returns null
```

## Dynamic Parsing (`CssColor::parse`)

For advanced use cases where you need to parse expressions that are resolved later (like CSS variables or `light-dark()` functions), use the `CssColor` parser.

```php
use PhpColor\Color\Css\CssColor;

$expr = CssColor::parse('var(--brand, blue)');
// Returns a resolvable expression instead of a concrete color
```

## Error Handling

When `Color::parse()` or `Color::from()` fail, they throw a `ParseException`.

```php
use PhpColor\Color\Exception\ParseException;

try {
    $color = Color::parse('not-a-color');
} catch (ParseException $e) {
    // Handle error
}
```

---

Next: [Formatting Colors](./formatting.md)
