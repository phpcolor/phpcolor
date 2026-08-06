<?php

declare(strict_types=1);

/*
 * This file is part of the PHPColor library.
 *
 * (c) 2024-present Simon André & Raphaêl Geffroy
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace PhpColor\Color;

use PhpColor\Color\Exception\InvalidColorException;

/**
 * Public contract for color value objects.
 *
 * This interface defines the complete API for working with colors in different color spaces.
 * All color objects are immutable value objects that support conversion between spaces,
 * CSS output formatting, and a comprehensive set of manipulation methods.
 *
 * Supported color spaces: sRGB, Display P3, Oklab, Oklch, XYZ D65, Lab, Lch, CMYK
 *
 * @see SrgbColor For standard web colors (sRGB color space)
 * @see OklabColor For perceptually uniform color manipulation
 * @see OklchColor For intuitive lightness/chroma/hue manipulation
 */
interface ColorInterface extends \Stringable
{
    /**
     * Normalize a color input to this color space (throws on invalid strings).
     *
     * Accepts either a ColorInterface instance or a CSS color string. If a string is provided,
     * it is parsed and converted to this exact color space type.
     */
    public static function from(self|string $input): static;

    /**
     * Create a color instance in this space from an sRGB color.
     *
     * This is the primary conversion method used by the library. All color space conversions
     * go through sRGB as an intermediate step when necessary.
     *
     * @param SrgbColor $srgb The source color in sRGB space
     *
     * @return static A new color instance in the target space
     */
    public static function fromSrgb(SrgbColor $srgb): static;

    /**
     * Get the canonical space name for this color space.
     *
     * Returns a lowercase identifier for the color space (e.g., 'srgb', 'oklab', 'oklch').
     * This name can be used with the to() method to convert between spaces.
     *
     * @return string The color space name (e.g., 'srgb', 'oklab', 'oklch', 'lab', 'lch', 'xyz', 'display-p3')
     */
    public static function getSpaceName(): string;

    /**
     * Normalize a color input to this color space without throwing.
     *
     * Returns null if parsing fails. When successful, returns an instance of this exact type.
     */
    public static function tryFrom(self|string $input): ?static;

    /**
     * Generate analogous colors (adjacent colors on the color wheel).
     *
     * Returns an array of colors that are near this color on the color wheel,
     * creating harmonious color schemes. Colors are spaced 30° apart, alternating
     * between positive and negative rotations.
     *
     * The returned array includes this color as the first element, followed by
     * the requested number of analogous colors.
     *
     * Examples:
     * - Color::parse('red')->analogous(2)  // [red, red+30°, red-30°]
     * - Color::parse('blue')->analogous(4) // [blue, blue+30°, blue-30°, blue+60°, blue-60°]
     *
     * @param int $count Number of analogous colors to generate (default: 2)
     *
     * @return array<static> Array containing this color and its analogous colors
     */
    public function analogous(int $count = 2): array;

    /**
     * Blend this color with a backdrop color using a specified blend mode.
     *
     * Implements CSS/Photoshop-style blend modes, compositing this color (source) over
     * the backdrop color using the specified blend mode. All blending calculations happen
     * in sRGB space with proper alpha compositing.
     *
     * Supported blend modes:
     * - 'normal': Source color (default)
     * - 'multiply': Darkens by multiplying colors
     * - 'screen': Lightens by inverting, multiplying, and inverting again
     * - 'overlay': Combines multiply and screen based on backdrop
     * - 'darken': Selects darker of source/backdrop per channel
     * - 'lighten': Selects lighter of source/backdrop per channel
     * - 'color-dodge': Brightens backdrop based on source
     * - 'color-burn': Darkens backdrop based on source
     * - 'hard-light': Combines multiply and screen based on source
     * - 'soft-light': Softer version of overlay
     * - 'difference': Absolute difference between colors
     * - 'exclusion': Similar to difference but lower contrast
     *
     * Examples:
     * - Color::parse('red')->blend(Color::parse('blue'), 'multiply')
     * - Color::parse('#ff0000')->blend('#0000ff', 'screen')
     * - Color::parse('rgba(255 0 0 / 0.5)')->blend('blue', 'normal')
     *
     * @param ColorInterface|string $backdrop The backdrop color to blend with (color object or CSS string)
     * @param string                $mode     Blend mode name (default: 'normal')
     *
     * @return static A new color instance with blending applied
     */
    public function blend(self|string $backdrop, string $mode = 'normal'): static;

    /**
     * Generate the complementary color (opposite on the color wheel).
     *
     * Returns the color that is 180° opposite on the color wheel in perceptually uniform
     * Oklch space. Complementary colors create high contrast and visual interest.
     *
     * Examples:
     * - Color::parse('red')->complementary()    // Returns cyan-like color
     * - Color::parse('blue')->complementary()   // Returns yellow-like color
     * - Color::parse('#2ecc71')->complementary() // Returns magenta-like color
     *
     * @return static A new color instance that is complementary to this color
     */
    public function complementary(): static;

    /**
     * Make the color cooler by shifting hue toward blue/cyan.
     *
     * Adjusts the hue to make the color appear cooler, shifting toward blue and cyan
     * tones in perceptually uniform Oklch space. The amount controls how much the
     * color shifts toward cool tones.
     *
     * Examples:
     * - Color::parse('red')->cool(0.5)     // Shift red toward purple/blue
     * - Color::parse('yellow')->cool(0.8)  // Shift yellow toward green-cyan
     *
     * @param float $amount Amount to cool (0 to 1, where higher values = cooler)
     *
     * @return static A new color instance with cooler hue
     */
    public function cool(float $amount): static;

    /**
     * Decrease the lightness of the color.
     *
     * Adjusts lightness in perceptually uniform Oklch space. Positive values darken,
     * negative values lighten. This is equivalent to lighten() with a negated amount.
     *
     * Examples:
     * - Color::parse('red')->darken(0.2)   // Decrease lightness by 20%
     * - Color::parse('blue')->darken(0.5)  // Decrease lightness by 50%
     * - Color::parse('green')->darken(-0.1) // Actually lightens by 10%
     *
     * @param float $amount Amount to decrease lightness (typically 0-1, negative values lighten)
     *
     * @return static A new color instance with adjusted lightness
     */
    public function darken(float $amount): static;

    /**
     * Decrease the chroma (saturation/colorfulness) of the color.
     *
     * Adjusts chroma in perceptually uniform Oklch space. Lower chroma values make colors
     * less vivid, moving toward gray. The amount is always treated as a reduction.
     *
     * Examples:
     * - Color::parse('red')->desaturate(0.5)  // Make much less saturated
     * - Color::parse('blue')->desaturate(0.2) // Slightly less vivid
     *
     * @param float $amount Amount to decrease chroma (0-1, always desaturates)
     *
     * @return static A new color instance with reduced chroma
     */
    public function desaturate(float $amount): static;

    /**
     * Check if this color is equal to another color.
     *
     * Returns true when both the current color and the $other argument are from the
     * same color space and have the same component values.
     */
    public function equals(self $other): bool;

    /**
     * Get the alpha (opacity) channel value.
     *
     * Returns the alpha/opacity value of the color as a float in the range [0, 1],
     * where 0 is fully transparent and 1 is fully opaque.
     *
     * Examples:
     * - Color::parse('rgb(255 0 0)')->getAlpha()         // 1.0 (opaque)
     * - Color::parse('rgba(255 0 0 / 0.5)')->getAlpha()  // 0.5 (semi-transparent)
     * - Color::parse('rgb(0 255 0 / 0)')->getAlpha()     // 0.0 (transparent)
     *
     * @return float Alpha value from 0.0 (transparent) to 1.0 (opaque)
     */
    public function getAlpha(): float;

    /**
     * Get the color channel values as an associative array.
     *
     * Returns the color's channel values in its native color space. The channel names
     * and value ranges depend on the color space:
     * - sRGB: ['r', 'g', 'b'] (0-1)
     * - Oklab: ['l', 'a', 'b'] (l: 0-1, a/b: unbounded)
     * - Oklch: ['l', 'c', 'h'] (l: 0-1, c: 0+, h: 0-360)
     * - Lab: ['l', 'a', 'b'] (l: 0-100, a/b: unbounded)
     * - Lch: ['l', 'c', 'h'] (l: 0-100, c: 0+, h: 0-360)
     * - XYZ: ['x', 'y', 'z'] (unbounded)
     * - Display P3: ['r', 'g', 'b'] (0-1)
     *
     * The alpha channel is accessed separately via getAlpha().
     *
     * This is particularly useful for CSS Relative Color Syntax or custom color
     * manipulations where you need direct access to channel values.
     *
     * Examples:
     * - Color::parse('red')->getChannels()  // ['r' => 1.0, 'g' => 0.0, 'b' => 0.0]
     * - Color::parse('oklab(0.5 0.1 -0.2)')->getChannels() // ['l' => 0.5, 'a' => 0.1, 'b' => -0.2]
     *
     * @return array<string, float> Associative array mapping channel names to values
     */
    public function getChannels(): array;

    /**
     * Get the hue of the color in Oklch space.
     *
     * Returns the hue angle in degrees [0, 360). Returns 0 for achromatic colors.
     */
    public function getHue(): float;

    /**
     * Get the relative luminance of the color.
     *
     * Calculates the WCAG relative luminance in sRGB space, returning a value between 0 and 1.
     */
    public function getLuminance(): float;

    /**
     * Get the opacity of the color.
     *
     * This is an alias for getAlpha().
     */
    public function getOpacity(): float;

    /**
     * Get the chroma (perceptual saturation) of the color in Oklch space.
     */
    public function getSaturation(): float;

    /**
     * Convert the color to grayscale while preserving perceived luminance.
     *
     * Removes all chroma (colorfulness) by setting chroma to 0 in Oklch space,
     * resulting in a gray color with the same perceived brightness as the original.
     *
     * Examples:
     * - Color::parse('red')->grayscale()    // Gray with same luminance as red
     * - Color::parse('blue')->grayscale()   // Gray with same luminance as blue
     * - Color::parse('#3498db')->grayscale() // Converts to equivalent gray
     *
     * @return static A new grayscale color instance
     */
    public function grayscale(): static;

    /**
     * Invert the color (create a negative).
     *
     * Inverts each RGB channel: new = 1 - old. Performs inversion in sRGB space
     * regardless of the original color space. Alpha channel is preserved unchanged.
     *
     * Examples:
     * - Color::parse('#ffffff')->invert()  // #000000 (white to black)
     * - Color::parse('#ff0000')->invert()  // #00ffff (red to cyan)
     * - Color::parse('rgb(100 200 50)')->invert() // rgb(155 55 205)
     *
     * @return static A new inverted color instance
     */
    public function invert(): static;

    /**
     * Check if the color is considered "cold".
     *
     * Returns true if the color temperature is less than 0.
     */
    public function isCold(): bool;

    /**
     * Check if the color is considered "dark".
     *
     * Inverse of isLight().
     */
    public function isDark(): bool;

    /**
     * Check if the color is considered "hot".
     *
     * Returns true if the color temperature is greater than 0.
     */
    public function isHot(): bool;

    /**
     * Check if the color is considered "light".
     *
     * Returns true if the luminance is greater than or equal to 0.179.
     */
    public function isLight(): bool;

    /**
     * Check if the color is fully opaque (alpha = 1).
     *
     * @return bool True if the color's alpha channel is 1 (fully opaque), false otherwise
     */
    public function isOpaque(): bool;

    /**
     * Check if the color is fully transparent (alpha = 0).
     *
     * @return bool True if the color's alpha channel is 0 (fully transparent), false otherwise
     */
    public function isTransparent(): bool;

    /**
     * Increase the lightness of the color.
     *
     * Adjusts lightness in perceptually uniform Oklch space. Positive values lighten,
     * negative values darken. Values are clamped to keep lightness in valid range [0, 1].
     *
     * Examples:
     * - Color::parse('red')->lighten(0.2)   // Increase lightness by 20%
     * - Color::parse('blue')->lighten(0.5)  // Increase lightness by 50%
     * - Color::parse('green')->lighten(-0.1) // Actually darkens by 10%
     *
     * @param float $amount Amount to increase lightness (typically 0-1, negative values darken)
     *
     * @return static A new color instance with adjusted lightness
     */
    public function lighten(float $amount): static;

    /**
     * Rotate the hue by a specified number of degrees.
     *
     * Performs hue rotation in perceptually uniform Oklch space, preserving lightness
     * and chroma while changing the hue. Positive values rotate clockwise on the color wheel,
     * negative values rotate counter-clockwise.
     *
     * Examples:
     * - Color::parse('red')->rotateHue(120)   // Shift to green region
     * - Color::parse('blue')->rotateHue(-60)  // Shift toward purple
     * - Color::parse('green')->rotateHue(180) // Complementary color
     *
     * @param float $degrees Rotation amount in degrees (positive = clockwise, negative = counter-clockwise)
     *
     * @return static A new color instance with the rotated hue
     */
    public function rotateHue(float $degrees): static;

    /**
     * Increase the chroma (saturation/colorfulness) of the color.
     *
     * Adjusts chroma in perceptually uniform Oklch space. Higher chroma values make colors
     * more vivid and saturated. Positive amounts increase saturation, negative amounts
     * decrease it (desaturate).
     *
     * Examples:
     * - Color::parse('red')->saturate(0.1)   // Make more saturated
     * - Color::parse('blue')->saturate(0.05) // Slightly more vivid
     * - Color::parse('green')->saturate(-0.1) // Actually desaturates
     *
     * @param float $amount Amount to increase chroma (positive saturates, negative desaturates)
     *
     * @return static A new color instance with adjusted chroma
     */
    public function saturate(float $amount): static;

    /**
     * Mix this color toward black (darken perceptually).
     *
     * Creates a shade by mixing with pure black in perceptually uniform Oklab space.
     * The amount parameter controls the mixing ratio: 0 returns the original color,
     * 1 returns black, 0.5 is an equal mix.
     *
     * This is perceptually more accurate than simple darkening operations.
     *
     * Examples:
     * - Color::parse('red')->shade(0.5)  // 50% mix with black (dark red)
     * - Color::parse('yellow')->shade(0.8) // 80% mix with black (very dark yellow)
     *
     * @param float $t Mix amount in [0, 1] where 0 = original color, 1 = black
     *
     * @return static A new color instance with the shade applied
     */
    public function shade(float $t): static;

    /**
     * Generate a split-complementary color harmony.
     *
     * Returns three colors: this color plus the two colors adjacent to its complement.
     * This creates a harmonious scheme with strong visual contrast, similar to
     * complementary but with more nuance (at 150° and 210° from base).
     *
     * Examples:
     * - Color::parse('red')->splitComplementary()  // [red, cyan+30°, cyan-30°]
     * - Color::parse('#9b59b6')->splitComplementary() // Three harmonious colors
     *
     * @return array<static> Array of three colors: [this, this+150°, this+210°]
     */
    public function splitComplementary(): array;

    /**
     * Calculate the perceptual color temperature.
     *
     * Returns a value from -1 (cool) to +1 (warm) based on the hue in perceptually
     * uniform Oklch space. Warm colors (reds, oranges, yellows) have positive values,
     * cool colors (cyans, blues) have negative values, and neutral colors (greens,
     * magentas) are near zero.
     *
     * Temperature mapping:
     * - Red to Orange (0-90°): +1 to 0 (warm)
     * - Yellow to Cyan (90-180°): 0 to -1 (neutral to cool)
     * - Cyan to Blue (180-270°): -1 to 0 (cool)
     * - Blue to Red (270-360°): 0 to +1 (cool to warm)
     *
     * Examples:
     * - Color::parse('red')->temperature()    // ~1.0 (very warm)
     * - Color::parse('blue')->temperature()   // ~-0.5 (cool)
     * - Color::parse('green')->temperature()  // ~0.0 (neutral)
     *
     * @return float Temperature value from -1.0 (cool) to +1.0 (warm)
     */
    public function temperature(): float;

    /**
     * Generate a tetradic/rectangular color harmony.
     *
     * Returns four colors evenly spaced around the color wheel at 90° intervals.
     * Tetradic harmonies offer the most color variety while maintaining balance,
     * suitable for rich, complex designs.
     *
     * Examples:
     * - Color::parse('red')->tetradic()   // [red, orange-ish, cyan-ish, blue-ish]
     * - Color::parse('#e74c3c')->tetradic() // Four balanced colors
     *
     * @return array<static> Array of four colors: [this, this+90°, this+180°, this+270°]
     */
    public function tetradic(): array;

    /**
     * Mix this color toward white (lighten perceptually).
     *
     * Creates a tint by mixing with pure white in perceptually uniform Oklab space.
     * The amount parameter controls the mixing ratio: 0 returns the original color,
     * 1 returns white, 0.5 is an equal mix.
     *
     * This is perceptually more accurate than simple lightening operations.
     *
     * Examples:
     * - Color::parse('red')->tint(0.5)  // 50% mix with white (light red/pink)
     * - Color::parse('blue')->tint(0.8) // 80% mix with white (very pale blue)
     *
     * @param float $t Mix amount in [0, 1] where 0 = original color, 1 = white
     *
     * @return static A new color instance with the tint applied
     */
    public function tint(float $t): static;

    /**
     * Convert this color to another color space.
     *
     * Accepts either a space name as a string (e.g., 'oklab', 'oklch', 'srgb') or a
     * ColorInterface instance to use as a template for the target space.
     *
     * Examples:
     * - $color->to('oklab')           // Convert to Oklab
     * - $color->to('oklch')           // Convert to Oklch
     * - $color->to(new OklabColor(0, 0, 0))  // Convert using template instance
     *
     * Supported space names: 'srgb', 'rgb', 'oklab', 'oklch', 'lab', 'lch',
     * 'xyz', 'xyz-d65', 'display-p3', 'displayp3'
     *
     * @param string|ColorInterface $space Target color space name or template instance
     *
     * @return ColorInterface A new color instance in the target space
     *
     * @throws InvalidColorException If the target space is unknown or unsupported
     */
    public function to(string|self $space): self;

    /**
     * Generate CSS color function output.
     *
     * Returns a CSS-compatible color string. The output format depends on the space parameter:
     * - null (auto): Uses the most appropriate format for this color space
     * - 'srgb' or 'rgb': rgb() function
     * - 'hsl': hsl() function (for sRGB colors)
     * - 'color': color() function with explicit color space
     * - 'oklab', 'oklch', 'lab', 'lch': Respective CSS color functions
     *
     * Examples:
     * - ->toCss()           => 'rgb(255 0 0)' or 'oklab(0.628 0.225 0.126)'
     * - ->toCss('hsl')      => 'hsl(0 100% 50%)'
     * - ->toCss('srgb')     => 'rgb(255 0 0)'
     * - ->toCss('color')    => 'color(srgb 1 0 0)'
     *
     * Alpha channel is included in output when not fully opaque (< 1.0):
     * - 'rgb(255 0 0 / 0.5)' or 'oklab(0.628 0.225 0.126 / 0.8)'
     *
     * @param string|null $space Target output format (null for auto-detect based on color space)
     *
     * @return string CSS color function string
     *
     * @throws InvalidColorException If the requested output format is not supported
     */
    public function toCss(?string $space = null): string;

    /**
     * Generate hexadecimal color notation.
     *
     * Converts the color to sRGB (if necessary) and returns a hex color string.
     * Channel values are clamped to [0, 255] and rounded to integers.
     *
     * Examples:
     * - ->toHex()          => '#ff0000'
     * - ->toHex(true)      => '#ff0000ff' (with alpha)
     * - ->toHex(false)     => '#0099cc'
     *
     * @param bool $withAlpha Include alpha channel in output (default: false)
     *
     * @return string Hexadecimal color string (e.g., '#rrggbb' or '#rrggbbaa')
     */
    public function toHex(bool $withAlpha = false): string;

    /**
     * Convert to the Oklch color space.
     *
     * @return OklchColor The color converted to Oklch space
     */
    public function toOklch(): OklchColor;

    /**
     * Convert to sRGB color space.
     *
     * Convenience method for converting to sRGB, the most common color space for web use.
     * This method is used internally by many operations that need to work in sRGB space.
     *
     * @return SrgbColor The color converted to sRGB space
     */
    public function toSrgb(): SrgbColor;

    /**
     * Returns the public normalized representation of the color.
     *
     * Concrete color implementations MUST call this method when
     * implementing __toString()
     */
    public function toString(): string;

    /**
     * Generate a triadic color harmony.
     *
     * Returns three colors evenly spaced around the color wheel at 120° intervals.
     * Triadic harmonies are vibrant and balanced, offering strong visual contrast
     * while retaining color harmony.
     *
     * Examples:
     * - Color::parse('red')->triadic()   // [red, green-ish, blue-ish]
     * - Color::parse('#3498db')->triadic() // Three balanced colors
     *
     * @return array<static> Array of three colors: [this, this+120°, this+240°]
     */
    public function triadic(): array;

    /**
     * Make the color warmer by shifting hue toward red/orange.
     *
     * Adjusts the hue to make the color appear warmer, shifting toward red and orange
     * tones in perceptually uniform Oklch space. The amount controls how much the
     * color shifts toward warm tones.
     *
     * Examples:
     * - Color::parse('blue')->warm(0.5)    // Shift blue toward purple/magenta
     * - Color::parse('green')->warm(0.8)   // Shift green toward yellow-orange
     *
     * @param float $amount Amount to warm (0 to 1, where higher values = warmer)
     *
     * @return static A new color instance with warmer hue
     */
    public function warm(float $amount): static;

    /**
     * Return a new instance with a different alpha value.
     *
     * Defines the alpha/opacity value of the color as a float in the range [0, 1],
     * where 0 is fully transparent and 1 is fully opaque.
     */
    public function withAlpha(float $alpha = 1.0): static;

    /**
     * Return a new instance with a single channel updated by name.
     */
    public function withChannel(string $name, float|int $value): static;

    /**
     * Return a new instance with multiple channels updated.
     *
     * Generic channel patching helper. Merges provided subset with current channels.
     *
     * @param array<string, float|int> $partial
     */
    public function withChannels(array $partial): static;
}
