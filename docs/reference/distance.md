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

The values below are the usual reference points for CIEDE2000 on small color patches.

| Delta E | Perception |
| :--- | :--- |
| **< 1.0** | Not perceptible by human eyes |
| **1.0 – 2.0** | Perceptible through close observation |
| **2.0 – 10.0** | Perceptible at a glance |
| **> 10.0** | Clearly different colors |

There is no universal upper bound, and no value means "opposite". Black against white
scores exactly 100 in CIEDE2000, but that is the length of the lightness axis, not a maximum:
green against magenta reaches 108.6.

The scale also depends on the algorithm. The same black and white pair scores 100 in DeltaE94
and 97.9 in CMC. The values below compare every pair among the eight corners of the sRGB cube:
black, white, red, green, blue, cyan, magenta, and yellow. They are sample maxima, not universal
upper bounds.

| Algorithm | Black vs white | Largest among sRGB cube corners |
| :--- | ---: | ---: |
| CIEDE2000 | 100.0 | 108.6 |
| DeltaE94 | 100.0 | 148.9 |
| CMC(2:1) | 97.9 | 207.7 |

Calibrate your own thresholds against samples from your project rather than reusing a fixed
table, especially when comparing large areas, text, or colors seen under different lighting.

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
