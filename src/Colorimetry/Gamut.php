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

namespace PhpColor\Color\Colorimetry;

use PhpColor\Color\ColorInterface;

/**
 * Utilities for gamut diagnostics and analysis.
 */
final readonly class Gamut
{
    /**
     * Check if a color is representable in a target RGB-like space.
     */
    public static function isInGamut(ColorInterface $color, string $space, float $epsilon = 1e-6): bool
    {
        $rgbish = $color->to($space);
        $channels = $rgbish->getChannels();
        foreach (['r', 'g', 'b'] as $k) {
            if (!\array_key_exists($k, $channels)) {
                $rgbish = $color->to('srgb');
                $channels = $rgbish->getChannels();

                break;
            }
        }

        foreach (['r', 'g', 'b'] as $k) {
            $v = $channels[$k] ?? 0.0;
            if (0.0 - $epsilon > $v || $v > 1.0 + $epsilon) {
                return false;
            }
        }

        return true;
    }

    /**
     * Quantify the out-of-gamut overflow for a color.
     *
     * Returns the maximum absolute deviation from the [0, 1] range.
     */
    public static function gamutDelta(ColorInterface $color, string $space): float
    {
        $rgbish = $color->to($space);
        $channels = $rgbish->getChannels();

        $max = 0.0;
        foreach (['r', 'g', 'b'] as $k) {
            $v = (float) ($channels[$k] ?? 0.0);
            $d = 0.0;
            if (0.0 > $v) {
                $d = -$v;
            } elseif ($v > 1.0) {
                $d = $v - 1.0;
            }
            if ($d > $max) {
                $max = $d;
            }
        }

        return $max;
    }
}
