# HWB (Hue-Whiteness-Blackness)

The **HWB** color model was suggested by Alvy Ray Smith (one of the creators of sRGB) as being more intuitive for humans than HSL or HSV.

## Class: `HwbColor`

### Channels
- **`h` (Hue)**: 0–360 (degrees).
- **`w` (Whiteness)**: 0–1.
- **`b` (Blackness)**: 0–1.

## Usage

```php
use PhpColor\Color\Color;

$hwb = Color::parse("hwb(180 20% 10%)");
$hwb = Color::parse("hwb(180, 0.2, 0.1)");
```

### Direct Instantiation
```php
use PhpColor\Color\HwbColor;

$color = new HwbColor(180, 0.2, 0.1);
```

### Accessing Channels
```php
$h = $color->h;
$w = $color->w;
$b = $color->b;
```
