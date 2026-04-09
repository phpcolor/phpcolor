# ProPhoto RGB

![ProPhoto RGB Illustration](../../images/space/prophoto-rgb.svg)

An extremely wide-gamut RGB color space developed by Kodak. It is designed for professional photography and high-end image processing, capable of representing nearly all visible surface colors.

## Usage

```php
use PhpColor\Color\ProPhotoColor;

// Create from components (0.0 to 1.0)
$color = new ProPhotoColor(0.4, 0.8, 0.3);

// Convert any color to ProPhoto RGB
$pro = $otherColor->to('prophoto-rgb');
```

## Advanced

### D50 White Point
Unlike most web color spaces (sRGB, P3, Rec.2020) which use the **D65** (6500K) white point, ProPhoto RGB uses the **D50** (5000K) white point. 

When converting between ProPhoto and other spaces, PHPColor automatically performs **Chromatic Adaptation** using the Bradford transform to ensure color consistency across these different reference whites.

### Professional Use Case
ProPhoto is often used as a "working space" in photography because its gamut is so large that it is almost impossible to "clip" color information when performing heavy adjustments to saturation or exposure.

## Examples

### High-Precision Adjustments
Adjusting colors in ProPhoto space before final output to web formats like sRGB.

```php
$color = Color::parse($rawInput)
    ->to('prophoto-rgb')
    ->lighten(0.1)
    ->saturate(0.2);

// Final output for the web
echo $color->toSrgb()->toCss();
```

### CSS color() output
Generate CSS using the `prophoto-rgb` identifier.

```php
echo $color->toCss('prophoto-rgb'); // color(prophoto-rgb 0.4 0.8 0.3)
```

## Navigation
- **Next**: [Adobe RGB](a98-rgb.md)
- **Previous**: [Rec.2020](rec2020.md)
- **Home**: [Documentation Index](../../index.md)
