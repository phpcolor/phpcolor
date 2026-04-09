# sRGB

![sRGB Illustration](../../images/space/srgb.svg)

The standard color space for the web and most digital displays. PHPColor provides high-performance sRGB handling with support for extended gamuts.

## Usage

```php
use PhpColor\Color\Color;
use PhpColor\Color\SrgbColor;

// Create from components (0.0 to 1.0)
$color = new SrgbColor(1.0, 0.5, 0.0);

// Convert any color to sRGB
$srgb = $otherColor->toSrgb();

// Quick factory
$red = Color::red();
```

## Advanced

### Extended sRGB
PHPColor's `SrgbColor` implementation allows channel values **outside the [0, 1] range**. This is intentional and crucial for wide-gamut support. 

When converting from a wider space like Display P3 or Rec.2020, some colors cannot be represented within the standard sRGB gamut. By allowing values like `-0.1` or `1.2`, we preserve the color data during intermediate calculations, preventing "clipping" and information loss.

### CSS Output
The `toCss()` method automatically handles rounding and alpha channel formatting according to modern CSS standards.

```php
$color = new SrgbColor(1, 0, 0, 0.5);
echo $color->toCss(); // rgb(255 0 0 / 0.5)
```

## Examples

### Parsing Legacy Formats
The sRGB parser is highly optimized and supports all common legacy and modern formats.

```php
$c1 = SrgbColor::parse('#f00');
$c2 = SrgbColor::parse('rgb(255, 0, 0)');
$c3 = SrgbColor::parse('rgba(100% 0% 0% / 0.5)');
```

### High-Performance Loops
If you are processing large numbers of sRGB colors (e.g., in an image processor), use the `SrgbColor` constructor directly to bypass the overhead of the universal `Color::parse()` entry point.

## Navigation
- **Next**: [Oklch](oklch.md)
- **Previous**: [Vision](../../reference/vision.md)
- **Home**: [Documentation Index](../../index.md)
