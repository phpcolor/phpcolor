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
 * APCA (Advanced Perceptual Contrast Algorithm) Lc calculation.
 *
 * Implements the canonical APCA 0.0.98G "G-4g" base algorithm (SAPC-8, simple
 * version), ported from the W3-licensed reference implementation at
 * https://github.com/Myndex/apca-w3 (Beta 0.1.9, 2022-07-03 revision).
 *
 * Notes:
 * - Returns Lc (contrast) where sign indicates polarity:
 *   + Positive: dark text on light background (preferred polarity)
 *   - Negative: light text on dark background (reverse polarity)
 * - Values roughly in the range [-108, +108].
 * - Alpha is ignored, matching ColorContrast's WCAG calculation.
 */
final class ApcaContrast
{
    private const float MAIN_TRC = 2.4;

    private const float S_R_CO = 0.2126729;
    private const float S_G_CO = 0.7151522;
    private const float S_B_CO = 0.0721750;

    private const float NORM_BG = 0.56;
    private const float NORM_TXT = 0.57;
    private const float REV_TXT = 0.62;
    private const float REV_BG = 0.65;

    private const float BLACK_THRESHOLD = 0.022;
    private const float BLACK_CLAMP = 1.414;

    private const float SCALE_BOW = 1.14;
    private const float SCALE_WOB = 1.14;
    private const float LOW_CONTRAST_BOW_OFFSET = 0.027;
    private const float LOW_CONTRAST_WOB_OFFSET = 0.027;
    private const float LOW_CONTRAST_CLIP = 0.1;
    private const float DELTA_Y_MIN = 0.0005;

    /**
     * Compute APCA Lc for two colors (foreground text over background).
     */
    public static function lc(ColorInterface $fg, ColorInterface $bg): float
    {
        $txtY = self::relLumi($fg->toSrgb());
        $bgY = self::relLumi($bg->toSrgb());

        if (is_nan($txtY) || is_nan($bgY) || min($txtY, $bgY) < 0.0 || max($txtY, $bgY) > 1.1) {
            return 0.0;
        }

        $txtY = self::softClampBlack($txtY);
        $bgY = self::softClampBlack($bgY);

        if (abs($bgY - $txtY) < self::DELTA_Y_MIN) {
            return 0.0;
        }

        if ($bgY > $txtY) {
            $sapc = ($bgY ** self::NORM_BG - $txtY ** self::NORM_TXT) * self::SCALE_BOW;
            $outputContrast = $sapc < self::LOW_CONTRAST_CLIP ? 0.0 : $sapc - self::LOW_CONTRAST_BOW_OFFSET;
        } else {
            $sapc = ($bgY ** self::REV_BG - $txtY ** self::REV_TXT) * self::SCALE_WOB;
            $outputContrast = $sapc > -self::LOW_CONTRAST_CLIP ? 0.0 : $sapc + self::LOW_CONTRAST_WOB_OFFSET;
        }

        return $outputContrast * 100.0;
    }

    /**
     * Soft-clamp luminance near black so the reverse-polarity formula does
     * not divide the result by an unrealistically dark reference point.
     */
    private static function softClampBlack(float $y): float
    {
        return $y > self::BLACK_THRESHOLD ? $y : $y + (self::BLACK_THRESHOLD - $y) ** self::BLACK_CLAMP;
    }

    /**
     * Calculate the APCA relative luminance of an sRGB color.
     */
    private static function relLumi(SrgbColor $c): float
    {
        return self::S_R_CO * ($c->r ** self::MAIN_TRC)
            + self::S_G_CO * ($c->g ** self::MAIN_TRC)
            + self::S_B_CO * ($c->b ** self::MAIN_TRC);
    }
}
