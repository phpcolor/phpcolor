# Oklch

![Oklch Illustration](../../images/space/oklch.svg)

The most intuitive and perceptually accurate color space for UI design. Oklch defines colors by Lightness, Chroma (intensity), and Hue angle.

## Usage

```php
use PhpColor\Color\Color;
use PhpColor\Color\OklchColor;

// Create from components: L (0-1), C (0-0.4+), H (0-360)
$color = new OklchColor(0.65, 0.15, 210.0);

// Quick factory
$blue = Color::oklch(0.65, 0.15, 210.0);

// Convert any color to Oklch
$oklch = $otherColor->to('oklch');
```

## Advanced

### Why Oklch?
Unlike HSL, Oklch is **perceptually uniform**. This means:
1. Two colors with the same **Lightness (L)** will appear equally bright to human eyes, regardless of their hue.
2. Increasing **Chroma (C)** increases colorfulness without shifting the perceived lightness.
3. Rotating **Hue (H)** preserves the visual weight of the color.

### Conversion Performance
PHPColor uses an optimized conversion pipeline between sRGB and Oklch, including a `ConversionCache` to minimize redundant calculations for repeated transformations.

## Examples

### Intuitive Brightness Adjustments
Because L is perceptual, adding 0.1 to L consistently makes any color appear "10% brighter" to a human observer.

```php
$lighter = $color->withChannel('l', $color->l + 0.1);
```

### Parsing CSS Oklch
Fully supports the modern CSS `oklch()` functional notation.

```php
$color = Color::parse('oklch(65% 0.15 210 / 0.5)');
```

## Navigation
- **Next**: [Lab](lab.md)
- **Previous**: [sRGB](srgb.md)
- **Home**: [Documentation Index](../../index.md)
