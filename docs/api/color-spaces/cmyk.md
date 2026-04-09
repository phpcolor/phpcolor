# CMYK

![CMYK Illustration](../../images/space/cmyk.svg)

A subtractive color model used primarily in color printing. CMYK represents colors as mixtures of Cyan, Magenta, Yellow, and Key (Black) inks.

## Usage

```php
use PhpColor\Color\CmykColor;

// Create from components (0.0 to 1.0)
$color = new CmykColor(0.0, 0.5, 1.0, 0.0);

// Convert any color to CMYK
$cmyk = $otherColor->to('cmyk');
```

## Advanced

### Device Dependence
Unlike Lab or XYZ, CMYK is **device-dependent**. The same CMYK values will look different when printed on different paper types or using different ink sets. PHPColor uses a standard mathematical conversion suitable for screen preview, but not for high-end professional printing which requires ICC profiles.

### Subtractive Model
Unlike RGB (where adding light leads to white), CMYK is subtractive: adding more ink absorbs more light, leading toward black. The 'K' (Key) channel is used instead of pure CMY mixture to produce deeper blacks and save on colored ink costs.

## Examples

### Generating Print Previews
Quickly estimate what an RGB brand color would look like in a CMYK process.

```php
$brand = Color::parse('#3b82f6');
$print = $brand->to('cmyk');

echo "C: {$print->c}, M: {$print->m}, Y: {$print->y}, K: {$print->k}";
```

### Parsing CSS device-cmyk()
PHPColor supports the `device-cmyk()` functional notation.

```php
$color = Color::parse('device-cmyk(0% 50% 100% 0%)');
```

## Navigation
- **Next**: [Hue](../../reference/hue.md)
- **Previous**: [Lch](lch.md)
- **Home**: [Documentation Index](../../index.md)
