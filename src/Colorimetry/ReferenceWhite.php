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

/**
 * Resolver for reference white points of standard color spaces.
 */
final class ReferenceWhite
{
    /**
     * Get the reference white point for a given color space identifier.
     *
     * Supported aliases:
     * - sRGB family: 'srgb', 'rgb'
     * - Display P3: 'display-p3', 'displayp3'
     * - Rec.2020: 'rec2020', 'rec-2020', 'bt2020', 'bt-2020'
     * - A98 RGB: 'a98-rgb', 'a98', 'adobe-rgb'
     * - ProPhoto RGB: 'prophoto-rgb', 'prophoto', 'romm-rgb'
     */
    public static function forSpace(string $space): WhitePoint
    {
        $s = strtolower(str_replace(['_', ' '], ['-', '-'], trim($space)));

        $d65 = [
            'srgb', 'rgb',
            'display-p3', 'displayp3',
            'rec2020', 'rec-2020', 'bt2020', 'bt-2020',
            'a98-rgb', 'a98', 'adobe-rgb',
        ];

        if (\in_array($s, $d65, true)) {
            return Illuminant::D65->whitePoint();
        }

        $d50 = [
            'prophoto-rgb', 'prophoto', 'romm-rgb',
        ];

        if (\in_array($s, $d50, true)) {
            return Illuminant::D50->whitePoint();
        }

        return Illuminant::D65->whitePoint();
    }
}
