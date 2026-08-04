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

namespace PhpColor\Color\Palette;

use PhpColor\Color\ColorInterface;
use PhpColor\Color\Distance\ColorDistanceInterface;
use PhpColor\Color\Exception\InvalidColorException;

/**
 * Public contract for color palette value objects.
 *
 * @extends \IteratorAggregate<int|string, ColorInterface>
 */
interface ColorPaletteInterface extends \Countable, \IteratorAggregate
{
    /**
     * Get all colors in the palette as an array.
     *
     * Returns an associative array for named palettes (name => ColorInterface)
     * or a sequential array for scale palettes.
     *
     * @return array<string|int, ColorInterface>
     */
    public function all(): array;

    /**
     * Get a color at a specific relative position in the palette.
     *
     * Only available for scale palettes. Uses interpolation between existing
     * colors to return a color at the given position (0.0 to 1.0).
     *
     * @throws InvalidColorException If the palette is not a scale palette
     */
    public function at(float $position): ColorInterface;

    /**
     * Find the color in the palette that is closest to a target color.
     *
     * By default, the match is the one with the smallest Euclidean distance in
     * the Oklab color space.
     *
     * @param ColorDistanceInterface|null $metric Distance algorithm to compare colors with,
     *                                            or null for the default Oklab Euclidean distance
     *
     * @throws InvalidColorException If the palette is empty
     */
    public function closest(ColorInterface $color, ?ColorDistanceInterface $metric = null): ColorInterface;

    /**
     * Get the number of colors in the palette.
     */
    public function count(): int;

    /**
     * Decrease the lightness of all colors in the palette.
     *
     * Adjusts lightness in Oklch space by the given amount.
     */
    public function darken(float $amount): static;

    /**
     * Decrease the chroma (saturation) of all colors in the palette.
     *
     * Adjusts chroma in Oklch space by the given amount.
     */
    public function desaturate(float $amount): static;

    /**
     * Filter the colors in the palette using a predicate function.
     *
     * Returns a new palette containing only the colors that match the predicate.
     *
     * @param callable(ColorInterface): bool $callback
     */
    public function filter(callable $callback): static;

    /**
     * Get a specific color by its index or name.
     *
     * @param int|string $key The index (for scales) or name (for named palettes)
     *
     * @throws InvalidColorException If the key does not exist
     */
    public function get(int|string $key): ColorInterface;

    /**
     * Check if the palette is a named palette.
     *
     * Returns true if colors are associated with string names, false if it's a scale.
     */
    public function isNamed(): bool;

    /**
     * Increase the lightness of all colors in the palette.
     *
     * Adjusts lightness in Oklch space by the given amount.
     */
    public function lighten(float $amount): static;

    /**
     * Transform all colors in the palette using a mapping function.
     *
     * Returns a new palette with the results of the transformation.
     *
     * @param callable(ColorInterface): ColorInterface $callback
     */
    public function map(callable $callback): static;

    /**
     * Merge this palette with another palette.
     *
     * Only available for scale palettes. Returns a new palette combining both.
     *
     * @throws InvalidColorException If the palettes cannot be merged
     */
    public function merge(self $palette): static;

    /**
     * Get the names of all colors in the palette.
     *
     * Only available for named palettes. Returns an array of color names.
     *
     * @return array<string>
     */
    public function names(): array;

    /**
     * Reverse the order of colors in the palette.
     *
     * Only available for scale palettes.
     *
     * @throws InvalidColorException If the palette is not a scale palette
     */
    public function reverse(): static;

    /**
     * Rotate the hue of all colors in the palette.
     *
     * Adjusts the hue in Oklch space by the given number of degrees.
     */
    public function rotateHue(float $angle): static;

    /**
     * Increase the chroma (saturation) of all colors in the palette.
     *
     * Adjusts chroma in Oklch space by the given amount.
     */
    public function saturate(float $amount): static;

    /**
     * Get a sub-slice of the palette.
     *
     * Only available for scale palettes. Works like array_slice().
     *
     * @throws InvalidColorException If the palette is not a scale palette
     */
    public function slice(int $offset, ?int $length = null): static;

    /**
     * Convert all colors in the palette to a specific color space.
     *
     * @param string $space Target color space name (e.g., 'oklch', 'srgb')
     *
     * @throws InvalidColorException If the space is unknown or unsupported
     */
    public function to(string $space): static;

    /**
     * Convert all colors in the palette to CSS color strings.
     *
     * @param string|null $format Target format (null for auto-detect)
     *
     * @return array<string|int, string> Array of CSS color strings
     *
     * @throws InvalidColorException If the target format is not supported
     */
    public function toCss(?string $format = null): array;

    /**
     * Generate CSS custom properties (variables) for the palette.
     *
     * Only available for named palettes. Generates a string of CSS variable
     * declarations like '--prefix-name: color;'.
     *
     * @param string $prefix Prefix for CSS variable names (default: 'color')
     *
     * @throws InvalidColorException If the palette is not a named palette
     */
    public function toCssVariables(string $prefix = 'color'): string;

    /**
     * Convert all colors in the palette to hexadecimal color strings.
     *
     * @return array<string|int, string> Array of hex color strings (e.g., '#rrggbb')
     */
    public function toHex(): array;
}
