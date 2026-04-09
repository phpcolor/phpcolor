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

namespace PhpColor\Color\Distance;

use PhpColor\Color\ColorInterface;

/**
 * Interface for color distance calculation algorithms.
 *
 * Defines the contract for algorithms that measure the perceptual difference
 * between two colors.
 */
interface ColorDistanceInterface
{
    /**
     * Calculate the perceptual difference between two colors.
     *
     * Returns a numeric value representing the perceptual difference, where:
     * - 0 means the colors are identical
     * - Larger values indicate greater perceptual difference
     * - The scale and interpretation depend on the specific algorithm
     *
     * @param ColorInterface $color1 The first color to compare
     * @param ColorInterface $color2 The second color to compare
     *
     * @return float The calculated color distance value
     */
    public function calculate(ColorInterface $color1, ColorInterface $color2): float;

    /**
     * Get the name of the algorithm.
     *
     * @return string The algorithm name (e.g., 'CIEDE2000', 'DeltaE94', 'CMC')
     */
    public function getName(): string;
}
