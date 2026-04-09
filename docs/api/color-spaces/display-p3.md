# Display P3

![Display P3 Illustration](../../images/space/display-p3.svg)

A wide-gamut RGB color space created by Apple. It offers more vibrant reds and greens than sRGB and is increasingly used in modern displays and mobile devices.

## Usage

```php
use PhpColor\Color\DisplayP3Color;

// Create from components (0.0 to 1.0)
$color = new DisplayP3Color(1.0, 0.0, 0.0);

// Convert any color to Display P3
$p3 = $otherColor->to('display-p3');
```

## Advanced

### Wide Gamut support
Display P3 is approximately 25% larger than sRGB. It uses the same white point (D65) and gamma curve as sRGB, but its color primaries (especially green and red) are significantly more saturated.

### Performance
PHPColor provides direct, matrix-based conversion between sRGB and Display P3 for maximum efficiency. Since they share the same white point, no chromatic adaptation is required.

## Examples

### Generating P3-Only Colors
Create a color that is "too vibrant" for sRGB but perfectly valid in Display P3.

```php
// Pure P3 Red
$p3Red = new DisplayP3Color(1, 0, 0);

// Converting to sRGB will result in values outside [0, 1]
echo $p3Red->toSrgb()->r; // ~1.09
```

### CSS color() output
PHPColor automatically outputs the modern `color(display-p3 ...)` syntax for these colors.

```php
echo $color->toCss(); // color(display-p3 1 0 0)
```

## Navigation
- **Next**: [Rec.2020](rec2020.md)
- **Previous**: [Oklch](oklch.md)
- **Home**: [Documentation Index](../../index.md)
