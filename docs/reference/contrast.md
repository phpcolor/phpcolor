# Contrast

Ensuring high color contrast is crucial for accessibility. PHPColor provides tools to calculate contrast ratios and verify compliance with standards.

## WCAG 2.1 Contrast

The WCAG 2.1 standard defines specific contrast ratio targets for text readability.

### Calculate Contrast Ratio

Use `Color::contrast()` to calculate the ratio between two colors (returns a value between 1 and 21).

```php
use PhpColor\Color\Color;

$bg = Color::parse("#ffffff");
$fg = Color::parse("#3b82f6");

$ratio = Color::contrast($fg, $bg); // 4.54
```

### Check WCAG Compliance

Use `ColorContrast::meetsFor()` to check if two colors meet a specific WCAG level.

```php
use PhpColor\Contrast\ColorContrast;
use PhpColor\Contrast\WcagLevel;

$meets = ColorContrast::meetsFor($fg, $bg, WcagLevel::AA); // true
$meetsAAA = ColorContrast::meetsFor($fg, $bg, WcagLevel::AAA); // false
```

## Advanced Contrast Tools

The `ContrastSolver` provides more sophisticated utilities for design systems.

### Pick Best Contrast

Choose the best contrasting color from a list of candidates.

```php
use PhpColor\Contrast\ContrastSolver;

$bg = Color::parse("#3b82f6"); // Blue background
$candidates = [
    Color::parse("#ffffff"), // White
    Color::parse("#000000"), // Black
];

$best = ContrastSolver::bestOn($bg, $candidates); // White
```

### Adjust Lightness for Contrast

Automatically adjust the lightness of a color to meet a target ratio.

```php
$fg = Color::parse("#3b82f6");
$bg = Color::parse("#ffffff");

$accessibleFg = ContrastSolver::adjustLightnessToContrast($fg, $bg, 4.5);
```

### Alpha Compositing Contrast

Calculate contrast even when foreground colors are semi-transparent.

```php
$fg = Color::parse("rgba(59, 130, 246, 0.5)");
$bg = Color::parse("#ffffff");

$ratio = ContrastSolver::compositedRatio($fg, $bg);
```

## Reference

| WCAG Level | Regular Text | Large Text |
| :--- | :--- | :--- |
| **AA** | 4.5:1 | 3:1 |
| **AAA** | 7:1 | 4.5:1 |

> [!TIP]
> **Large text** is defined as at least 18pt (approx. 24px) or 14pt (approx. 18.66px) and bold.
