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

namespace PhpColor\Color\Gradient;

use PhpColor\Color\ColorInterface;

/**
 * Interface for all gradient types.
 *
 * Defines the contract for gradients, which represent smooth transitions
 * between multiple color stops along a path (linear, radial, or conic).
 */
interface GradientInterface
{
    /**
     * Add a color stop to the gradient.
     *
     * @param ColorInterface $color    The color at this stop
     * @param float          $position Position (0.0 to 1.0) where this color appears
     *
     * @return static A new gradient instance with the added stop
     */
    public function addStop(ColorInterface $color, float $position): static;

    /**
     * Get all color stops in this gradient.
     *
     * @return list<GradientStop>
     */
    public function getStops(): array;

    /**
     * Get the gradient type name.
     *
     * @return string Type name (e.g., 'linear', 'radial', 'conic')
     */
    public function getType(): string;

    /**
     * Interpolate the color at a specific position in the gradient.
     *
     * @param float $position Position (0.0 to 1.0) to sample
     *
     * @return ColorInterface The interpolated color at this position
     */
    public function interpolate(float $position): ColorInterface;

    /**
     * Generate CSS gradient notation.
     *
     * @param string|null $colorSpace Optional color space for output colors
     *
     * @return string CSS gradient string (e.g., "linear-gradient(...)")
     */
    public function toCss(?string $colorSpace = null): string;
}
