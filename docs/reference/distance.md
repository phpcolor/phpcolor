# Distance

![Distance Illustration](../images/distance.svg)

Measure the perceptual difference between two colors using industry-standard Delta E formulas.

## Usage

```php
use PhpColor\Color\Color;

// Calculate perceptual difference using CIEDE2000 (standard)
$diff = Color::deltaE('#3b82f6', '#2563eb'); 

// Returns true if colors are effectively identical to human eyes (< 1.0)
$isSame = $diff < 1.0;
```

## Advanced

### Multiple Algorithms
Different industries require different formulas. PHPColor supports the most common ones:

```php
// CIEDE2000 (Default, most accurate)
$d = Color::distance($c1, $c2, 'CIEDE2000');

// DeltaE94 (Graphic arts)
$d = Color::distance($c1, $c2, 'DeltaE94');

// CMC l:c (Textiles)
$d = Color::distance($c1, $c2, 'CMC(2:1)');
```

### Interpretation
| Delta E | Perception |
| :--- | :--- |
| **< 1.0** | Not perceptible by human eyes |
| **1.0 – 2.0** | Perceptible through close observation |
| **2.0 – 10.0** | Perceptible at a glance |
| **11.0 – 49.0** | Colors are more similar than opposite |
| **100.0** | Colors are exact opposites |

## Examples

### Finding the Closest Match
Find the nearest color from a fixed set of brand colors.

```php
$userColor = Color::parse($input);
$closest = $brandPalette->closest($userColor);
```

### De-duplicating Palettes
Remove colors that are perceptually too similar to each other.

```php
$uniqueColors = $palette->filter(function($color) use ($existing) {
    foreach ($existing as $e) {
        if (Color::deltaE($color, $e) < 2.0) return false;
    }
    return true;
});
```

## Navigation
- **Next**: [Vision](vision.md)
- **Previous**: [Contrast](contrast.md)
- **Home**: [Documentation Index](../index.md)
