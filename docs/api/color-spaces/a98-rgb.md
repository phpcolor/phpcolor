# Adobe RGB (1998)

![Adobe RGB Illustration](../../images/space/a98-rgb.svg)

Also known as **A98-RGB**, this wide-gamut color space was developed by Adobe Systems to encompass many of the colors achievable on professional CMYK printers, particularly in green and cyan hues.

## Usage

```php
use PhpColor\Color\A98RgbColor;

// Create from components (0.0 to 1.0)
$color = new A98RgbColor(0.2, 0.9, 0.5);

// Convert any color to A98 RGB
$a98 = $otherColor->to('a98-rgb');
```

## Advanced

### Optimized for Print
While sRGB was designed for early consumer monitors, Adobe RGB was designed to bridge the gap between digital displays and high-end printing. It covers about 50% of the CIE visible spectrum, making it a standard choice for graphic designers and photographers.

### Accuracy
PHPColor uses the specific 563/256 (~2.2) gamma transfer function defined in the Adobe RGB (1998) specification for high-precision conversions through XYZ D65.

## Examples

### Preparing for High-End Output
Convert colors to Adobe RGB to ensure that vibrant greens and cyans are preserved when moving from a design tool to a professional print pipeline.

```php
$color = Color::parse('oklch(80% 0.2 150)')
    ->to('a98-rgb');
```

### CSS color() output
Generate CSS using the `a98-rgb` identifier.

```php
echo $color->toCss('a98-rgb'); // color(a98-rgb 0.2 0.9 0.5)
```

## Navigation
- **Next**: [Lab](lab.md)
- **Previous**: [ProPhoto RGB](prophoto-rgb.md)
- **Home**: [Documentation Index](../../index.md)
