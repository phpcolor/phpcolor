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

namespace PhpColor\Color\Contrast;

use PhpColor\Color\ColorInterface;
use PhpColor\Color\SrgbColor;

/**
 * Utility for calculating color contrast ratios based on WCAG 2.x standards.
 *
 * Provides methods to calculate contrast between colors, determine required
 * ratios for different WCAG levels, and check if a contrast meets those levels.
 */
final class ColorContrast
{
    /**
     * Calculate the contrast ratio between two colors.
     *
     * Returns a float between 1 and 21.
     */
    public static function calculate(ColorInterface $a, ColorInterface $b): float
    {
        $l1 = self::relativeLuminance($a->toSrgb());
        $l2 = self::relativeLuminance($b->toSrgb());

        $L1 = max($l1, $l2);
        $L2 = min($l1, $l2);

        return ($L1 + 0.05) / ($L2 + 0.05);
    }

    /**
     * Get the required contrast ratio for a specific WCAG level.
     *
     * - AA normal: 4.5
     * - AA large: 3.0
     * - AAA normal: 7.0
     * - AAA large: 4.5
     */
    public static function requiredRatio(WcagLevel $level = WcagLevel::AA, bool $largeText = false): float
    {
        if (WcagLevel::AAA === $level) {
            return $largeText ? 4.5 : 7.0;
        }

        return $largeText ? 3.0 : 4.5;
    }

    /**
     * Check if a contrast ratio meets a specific WCAG threshold.
     */
    public static function meets(float $ratio, WcagLevel $level = WcagLevel::AA, bool $largeText = false): bool
    {
        return $ratio >= self::requiredRatio($level, $largeText);
    }

    /**
     * Check if the contrast between two colors meets a specific WCAG threshold.
     */
    public static function meetsFor(ColorInterface $a, ColorInterface $b, WcagLevel $level = WcagLevel::AA, bool $largeText = false): bool
    {
        return self::meets(self::calculate($a, $b), $level, $largeText);
    }

    /**
     * Calculate the relative luminance of an sRGB color.
     */
    private static function relativeLuminance(SrgbColor $c): float
    {
        $r = self::srgbToLinear($c->r);
        $g = self::srgbToLinear($c->g);
        $b = self::srgbToLinear($c->b);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Convert an sRGB color component to linear.
     */
    private static function srgbToLinear(float $c): float
    {
        if (0.04045 >= $c) {
            return $c / 12.92;
        }

        return (($c + 0.055) / 1.055) ** 2.4;
    }
}
