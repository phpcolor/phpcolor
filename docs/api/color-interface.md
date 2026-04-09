# ColorInterface

The `PhpColor\Color\ColorInterface` is the foundation of the PHPColor library. All color objects implement this interface, ensuring consistent behavior across different color spaces.

## Properties

- **`getChannels(): array`**: Returns the raw channel values for the current space.
- **`getOpacity(): float`**: Returns the alpha channel (opacity) of the color (0–1).
- **`getAlpha(): float`**: Alias for `getOpacity()`.
- **`getHue(): float`**: Returns the hue in degrees (0–360).
- **`getSaturation(): float`**: Returns the saturation (0–1).
- **`getLuminance(): float`**: Returns the relative luminance (0–1).
- **`temperature(): float`**: Returns the correlated color temperature in Kelvins.

## Conversion & Output

- **`to(string|ColorInterface $space): ColorInterface`**: Converts the color to a new space.
- **`toCss(?string $space = null): string`**: Returns the CSS representation of the color.
- **`toHex(bool $withAlpha = false): string`**: Returns the hex string representation.
- **`toSrgb(): SrgbColor`**: Shortcut for conversion to sRGB.
- **`toString(): string`**: Returns a string representation of the color.

## State Checks

- **`equals(ColorInterface $other): bool`**: Checks for perceptual equality.
- **`isLight(): bool`**: `true` if the color is perceived as light.
- **`isDark(): bool`**: `true` if the color is perceived as dark.
- **`isHot(): bool`**: `true` if the color is perceived as warm/hot.
- **`isCold(): bool`**: `true` if the color is perceived as cool/cold.
- **`isOpaque(): bool`**: `true` if alpha is 1.0.
- **`isTransparent(): bool`**: `true` if alpha is 0.0.

## Manipulation (Immutable)

Methods return a new color object; the original is never modified.

- **`lighten(float $amount)`**: Increases lightness.
- **`darken(float $amount)`**: Decreases lightness.
- **`saturate(float $amount)`**: Increases saturation.
- **`desaturate(float $amount)`**: Decreases saturation.
- **`rotateHue(float $degrees)`**: Rotates the hue.
- **`grayscale()`**: Converts to grayscale.
- **`invert()`**: Inverts the color.
- **`warm(float $amount)`**: Makes the color warmer.
- **`cool(float $amount)`**: Makes the color cooler.
- **`tint(float $t)`**: Mixes with white (0–1).
- **`shade(float $t)`**: Mixes with black (0–1).
- **`blend(ColorInterface|string $backdrop, string $mode = 'normal')`**: Blends with another color.
- **`withAlpha(float $alpha)`**: Sets the alpha channel.
- **`withChannel(string $name, float|int $value)`**: Sets a specific channel value.
- **`withChannels(array $partial)`**: Sets multiple channel values.

## Harmony Generation

- **`complementary()`**: Returns the complementary color.
- **`triadic()`**: Returns an array of 2 triadic colors.
- **`tetradic()`**: Returns an array of 3 tetradic colors.
- **`analogous(int $count = 2)`**: Returns an array of $count analogous colors.
- **`splitComplementary()`**: Returns an array of 2 split-complementary colors.

## Type Hinting

Always type-hint against `ColorInterface` for generic color handling:

```php
use PhpColor\Color\ColorInterface;

function applyBackground(ColorInterface $color): void {
    echo "background: " . $color->toCss();
}
```
