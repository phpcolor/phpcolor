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
use PhpColor\Color\Exception\InvalidColorException;

/**
 * Utility for calculating perceptual distance between colors.
 *
 * Supports various algorithms including CIEDE2000, DeltaE94, and CMC.
 */
final class ColorDistance
{
    /**
     * Calculate color distance using a specific algorithm.
     *
     * Supported algorithms:
     * - 'CIEDE2000' or 'DE2000': Most accurate modern formula
     * - 'DeltaE94' or 'DE94': Improved 1994 formula
     * - 'CMC' or 'CMC(2:1)': UK textile industry standard (acceptability)
     * - 'CMC(1:1)': CMC perceptibility variant
     *
     * @param string|ColorDistanceInterface $algorithm Algorithm name or instance
     *
     * @throws InvalidColorException If the algorithm is not supported
     */
    public static function calculate(
        ColorInterface $a,
        ColorInterface $b,
        string|ColorDistanceInterface $algorithm = 'CIEDE2000',
    ): float {
        if ($algorithm instanceof ColorDistanceInterface) {
            return $algorithm->calculate($a, $b);
        }

        $calculator = match (strtoupper($algorithm)) {
            'CIEDE2000', 'DE2000' => new Ciede2000(),
            'DELTAE94', 'DE94' => new DeltaE94(),
            'CMC', 'CMC(2:1)' => Cmc::forAcceptability(),
            'CMC(1:1)' => Cmc::forPerceptibility(),
            default => throw new InvalidColorException(\sprintf('Unknown color distance algorithm "%s".', $algorithm)),
        };

        return $calculator->calculate($a, $b);
    }

    /**
     * Calculate perceptual color distance using the CIEDE2000 algorithm.
     */
    public static function deltaE(ColorInterface $a, ColorInterface $b): float
    {
        return (new Ciede2000())->calculate($a, $b);
    }
}
