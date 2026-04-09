# Quickstart

Get up and running with PHPColor in minutes. This guide covers the most common operations: parsing, converting, manipulating, and outputting colors.

## Basic Usage

The `PhpColor\Color\Color` class is the primary entry point for the library.

### 1. Parse a Color

You can parse any valid CSS color string, including modern CSS Color Level 4 and 5 syntax.

```php
use PhpColor\Color\Color;

$color = Color::parse("#3b82f6");
$color = Color::parse("oklch(0.65 0.18 264)");
$color = Color::parse("rebeccapurple");
```

### 2. Convert to Another Space

Every color object can be converted to any of the 15 supported color spaces.

```php
// Convert to Oklch (perceptually uniform)
$oklch = $color->toOklch();

// Convert to Display P3 (wide-gamut)
$p3 = $color->toDisplayP3();

// Generic conversion
$lab = $color->to("lab");
```

### 3. Manipulate

Colors are **immutable**. Every manipulation returns a new instance, allowing for clean method chaining.

```php
$result = Color::parse("#3b82f6")
    ->lighten(0.1)     // Increase lightness
    ->saturate(0.05)   // Increase saturation
    ->rotateHue(30);   // Rotate hue by 30 degrees
```

### 4. Output

Format the color back into a string for use in CSS or other contexts.

```php
echo $result->toCss();    // "rgb(117 169 255)" (or similar depending on space)
echo $result->toHex();    // "#75a9ff"
echo $result->toCss("oklch"); // "oklch(0.72 0.15 294)"
```

## Advanced Example: Theming

Using PHPColor to generate a subtle hover state for a brand color:

```php
use PhpColor\Color\Color;

$brand = Color::parse("oklch(60% 0.15 250)");

$hover = $brand->lighten(0.05)->saturate(0.02);
$active = $brand->darken(0.1);

echo "Base:   " . $brand->toCss() . "
";
echo "Hover:  " . $hover->toCss() . "
";
echo "Active: " . $active->toCss() . "
";
```

## Next Steps

- Learn about [Core Concepts](../core-concepts/index.md) like immutability and the conversion pipeline.
- Explore the [API Reference](../api/index.md) for a full list of available methods.
- Check out the [Guides](../guides/index.md) for complex tasks like palette generation and accessibility.