# XYZ (CIE 1931)

![XYZ Illustration](../../images/space/xyz.svg)

The master color space of the CIE system. XYZ represents all human-visible colors and serves as the mathematical foundation for almost all other color spaces.

## Usage

```php
use PhpColor\Color\XyzColor;

// Create from components (X, Y, Z are typically around 0-1 but unbounded)
$color = new XyzColor(0.412, 0.212, 0.019);

// Convert any color to XYZ
$xyz = $otherColor->to('xyz');
```

## Advanced

### The Master Space
Almost all color space conversions in PHPColor (and the industry at large) pass through XYZ. To convert from sRGB to Oklch, the color is first converted to XYZ, then to Oklab, and finally to Oklch.

### D65 Illuminant
PHPColor defaults to the **D65** (Daylight 6500K) illuminant for XYZ, which is the standard for the web and most digital imaging.

### Accuracy
XYZ values represent the actual physical intensity of light at different wavelengths, mapped to three primary "tristimulus" values.

## Examples

### Low-Level Matrix Transforms
If you are implementing a custom color space, convert your source data to XYZ first.

```php
$xyz = $myCustomColor->toXyz();
$srgb = SrgbColor::fromSrgb($xyz->toSrgb());
```

### Scientific Color Analysis
Extract the absolute luminance (Y) of a color from its XYZ representation.

```php
$luminance = $color->to('xyz')->y;
```

## Navigation
- **Next**: [Lch](lch.md)
- **Previous**: [Lab](lab.md)
- **Home**: [Documentation Index](../../index.md)
