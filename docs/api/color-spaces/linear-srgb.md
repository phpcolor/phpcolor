# Linear sRGB

**Linear sRGB** is the sRGB color space without the gamma encoding (transfer function). It is used internally by PHPColor for many color calculations, especially blending.

## Class: `LinearSrgbColor`

### Channels
- **`r` (Red)**: 0–1.
- **`g` (Green)**: 0–1.
- **`b` (Blue)**: 0–1.

## Usage

Linear sRGB is rarely used directly for output but is available for custom low-level calculations.

```php
use PhpColor\Color\LinearSrgbColor;

$color = new LinearSrgbColor(0.5, 0.5, 0.5);
```

### Conversion
```php
$srgb = $color->toSrgb();
```
