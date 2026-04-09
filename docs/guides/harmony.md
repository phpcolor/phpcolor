# Harmony

![Harmony Illustration](../images/harmony.svg)

Detect and generate harmonious color schemes based on standard color theory patterns.

## Harmony Patterns

PHPColor supports standard harmony patterns via the `HarmonyPattern` enum:

- `HarmonyPattern::Analogous` (3 colors)
- `HarmonyPattern::Complementary` (2 colors)
- `HarmonyPattern::SplitComplementary` (3 colors)
- `HarmonyPattern::Triadic` (3 colors)
- `HarmonyPattern::Tetradic` (4 colors)

## Generating Harmonies

The easiest way to generate a harmony is via the `ColorPalette::builder()`:

```php
use PhpColor\Color\Color;
use PhpColor\Color\Palette\ColorPalette;
use PhpColor\Color\Palette\Harmony\HarmonyPattern;

$base = Color::parse("#ef4444");

// Generate a triadic harmony palette
$palette = ColorPalette::builder()
    ->harmony($base, HarmonyPattern::Triadic)
    ->build();
```

Alternatively, you can use the `HarmonyGenerator` directly:

```php
use PhpColor\Color\Palette\Harmony\HarmonyGenerator;
use PhpColor\Color\Palette\Harmony\HarmonyPattern;

$generator = new HarmonyGenerator();
$palette = $generator->generate($base, HarmonyPattern::Complementary);
```

## Harmony Detection

Analyze existing palettes to identify their harmony pattern:

```php
use PhpColor\Color\Palette\Harmony\HarmonyDetector;

$detector = new HarmonyDetector();
$result = $detector->detect($palette);

if ($result["type"] === HarmonyPattern::Triadic) {
    echo "Triadic harmony detected with " . ($result["confidence"] * 100) . "% confidence.";
}
```

## Fixing Harmonies

Adjust an existing palette to perfectly match a theoretical harmony:

```php
use PhpColor\Color\Palette\Fixer\HarmonyFixer;
use PhpColor\Color\Palette\Harmony\HarmonyPattern;

$fixer = new HarmonyFixer();
$perfectPalette = $fixer->fix($originalPalette, [
    "target_harmony" => HarmonyPattern::Triadic,
    "preserve_lightness" => true,
]);
```
