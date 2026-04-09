# Palettes

Generate lightness scales, tints, and shades for design systems.

## Lightness Scales

Generate a range of colors by varying the lightness channel of a base color.

```php
use PhpColor\Color\Color;
use PhpColor\Palette\ColorPalette;

$brand = Color::parse("#3b82f6");

// Create 10 tints (base to white)
$tints = ColorPalette::tints($brand, 10);

// Create 10 shades (base to black)
$shades = ColorPalette::shades($brand, 10);
```

### Iterate over Palette

Iterate through the generated colors:

```php
foreach ($tints as $color) {
    echo $color->toHex();
}
```

## Custom Palettes

Create custom palettes from arbitrary color strings or objects.

```php
$palette = ColorPalette::from([
    "#fefce8",
    "#fef9c3",
    "#fef08a",
    "#fde047",
    "#facc15",
    "#eab308",
    "#ca8a04",
    "#a16207",
    "#854d0e",
    "#713f12",
]);
```

### Palette Transformers

Apply transformations to all colors in a palette at once.

```php
// Convert entire palette to Oklch
$oklchPalette = $palette->to("oklch");

// Darken all colors by 10%
$darkPalette = $palette->darken(0.1);

// Get CSS variables
echo $palette->toCssVariables("brand");
```

## Closest Color

Find the closest color in a palette to a target color.

```php
$target = Color::parse("#3e84f8");
$closest = $palette->closest($target);
```

## Next Steps

Explore [Harmony Patterns](harmony.md) for generating harmonious color schemes.
