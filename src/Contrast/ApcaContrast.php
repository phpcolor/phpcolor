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
 * APCA (Advanced Perceptual Contrast Algorithm) approximate implementation.
 *
 * Notes:
 * - This implementation targets the commonly used constants for sRGB (v0.98x family).
 * - Returns Lc (contrast) where sign indicates polarity:
 *   + Positive: dark text on light background (preferred polarity)
 *   - Negative: light text on dark background (reverse polarity)
 * - Values roughly in the range [-108, +108].
 */
final class ApcaContrast
{
    /**
     * Compute APCA Lc for two colors (foreground over background).
     */
    public static function lc(ColorInterface $fg, ColorInterface $bg): float
    {
        $fgY = self::relLumi($fg->toSrgb());
        $bgY = self::relLumi($bg->toSrgb());

        if (abs($bgY - $fgY) < 1e-9) {
            return 0.0;
        }
        $isNormalPolarity = $bgY > $fgY;

        $normBGExp = 0.56;
        $normFGExp = 0.57;
        $revBGExp = 0.65;
        $revFGExp = 0.62;

        $scaleNorm = 1.14;
        $scaleRev = 1.14;

        $blk = 0.003;

        if ($isNormalPolarity) {
            $c = ($bgY ** $normBGExp) - ($fgY ** $normFGExp);
            $Lc = 100.0 * $scaleNorm * $c;
        } else {
            $fgY = max($fgY, $blk);
            $bgY = max($bgY, $blk);

            $c = ($fgY ** $revFGExp) - ($bgY ** $revBGExp);
            $Lc = -100.0 * $scaleRev * $c;
        }

        return abs($Lc) < 0.1 ? 0.0 : $Lc;
    }

    /**
     * Calculate the relative luminance of an sRGB color.
     */
    private static function relLumi(SrgbColor $c): float
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
