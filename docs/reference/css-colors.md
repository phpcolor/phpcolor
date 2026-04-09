# CSS Colors

PHPColor provides a powerful and comprehensive CSS color parser, supporting virtually all modern CSS Color specifications (Level 4 and 5).

## Color Notations

The library can parse all standard CSS color notations into concrete `ColorInterface` objects.

### Named Colors

All 148 standard CSS named colors are supported, including `rebeccapurple`, `transparent`, and `currentcolor`.

```php
$blue = Color::parse('blue');
$purple = Color::parse('rebeccapurple');
```

### Hexadecimal

Supports 3, 4, 6, and 8-digit hex strings.

```php
$c1 = Color::parse('#f00');       // RGB
$c2 = Color::parse('#ff0000');   // RRGGBB
$c3 = Color::parse('#ff000080'); // RRGGBBAA
```

### Functional Notations

Supports both legacy (comma-separated) and modern (space-separated) syntaxes for all common functions.

*   **sRGB**: `rgb()`, `rgba()`, `hsl()`, `hsla()`, `hwb()`
*   **Perceptual**: `oklab()`, `oklch()`, `lab()`, `lch()`
*   **Wide Gamut**: `color(display-p3 ...)`, `color(rec2020 ...)`, `color(a98-rgb ...)`

```php
$c1 = Color::parse('rgb(255 0 0 / 50%)');
$c2 = Color::parse('oklch(60% 0.15 250)');
$c3 = Color::parse('color(display-p3 1 0.5 0)');
```

## Dynamic Expressions

PHPColor goes beyond simple parsing by supporting late-bound CSS expressions that can be resolved at runtime using a `CssContext`.

### CSS Variables

You can parse strings containing `var()` and resolve them later.

```php
use PhpColor\Color\Css\CssColor;
use PhpColor\Color\Css\CssContext;

$expr = CssColor::parse('var(--brand-primary, #007bff)');

// Resolve using a context
$context = new CssContext(['--brand-primary' => '#ff6600']);
$color = $expr->resolve($context); // Returns #ff6600
```

### Color Mix

The `color-mix()` function allows blending two colors in a specified color space.

```php
// Mix 25% white into blue in the Oklab space
$tint = Color::parse('color-mix(in oklab, blue, white 25%)');
```

### Light-Dark Support

Supports the `light-dark()` function, which resolves based on the color scheme of the context.

```php
$expr = Color::parse('light-dark(#ffffff, #000000)');

$light = $expr->resolve(CssContext::light()); // #ffffff
$dark = $expr->resolve(CssContext::dark());   // #000000
```

---

Learn more about manipulating colors in the [Relative Colors](./relative-colors.md) guide.
