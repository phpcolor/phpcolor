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

use PhpColor\Color\Color;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\OklchColor;
use PhpColor\Color\SrgbColor;

/**
 * Solver for complex color contrast problems.
 *
 * Provides utilities for alpha compositing, finding required alpha for contrast,
 * and adjusting lightness to meet specific contrast targets.
 */
final class ContrastSolver
{
    /**
     * Composite a foreground color over a background color.
     *
     * Performs alpha blending in sRGB space and returns the resulting color.
     */
    public static function composite(ColorInterface $fg, ColorInterface $bg): SrgbColor
    {
        $a = $fg->toSrgb();
        $b = $bg->toSrgb();

        $outA = $a->a + $b->a * (1.0 - $a->a);
        if ($outA <= 0.0) {
            return new SrgbColor(0.0, 0.0, 0.0, 0.0);
        }

        $r = ($a->r * $a->a + $b->r * $b->a * (1.0 - $a->a)) / $outA;
        $g = ($a->g * $a->a + $b->g * $b->a * (1.0 - $a->a)) / $outA;
        $b2 = ($a->b * $a->a + $b->b * $b->a * (1.0 - $a->a)) / $outA;

        return new SrgbColor($r, $g, $b2, $outA);
    }

    /**
     * Calculate the contrast ratio of a foreground color over a background.
     *
     * Accounts for the alpha channel of the foreground color by compositing it.
     */
    public static function compositedRatio(ColorInterface $fg, ColorInterface $bg): float
    {
        $comp = self::composite($fg, $bg);

        return ColorContrast::calculate($comp, $bg->toSrgb());
    }

    /**
     * Find the minimum alpha required for a foreground to reach a target contrast ratio.
     *
     * Uses binary search to find the minimal alpha value in range [0, 1].
     */
    public static function requiredAlpha(ColorInterface $fg, ColorInterface $bg, float $targetRatio = 4.5): float
    {
        $low = 0.0;
        $high = 1.0;

        if (self::compositedRatio($fg->withAlpha(0.0), $bg) >= $targetRatio) {
            return 0.0;
        }
        if (self::compositedRatio($fg->withAlpha(1.0), $bg) < $targetRatio) {
            return 1.0;
        }

        for ($i = 0; $i < 24; ++$i) {
            $mid = 0.5 * ($low + $high);
            $ratio = self::compositedRatio($fg->withAlpha($mid), $bg);
            if ($ratio >= $targetRatio) {
                $high = $mid;
            } else {
                $low = $mid;
            }
        }

        return max(0.0, min(1.0, $high));
    }

    /**
     * Adjust the lightness of a foreground color to meet a target contrast ratio.
     *
     * Preserves the chroma and hue of the color while searching for the minimal
     * lightness adjustment in Oklch space.
     */
    public static function adjustLightnessToContrast(ColorInterface $fg, ColorInterface $bg, float $targetRatio = 4.5): ColorInterface
    {
        $oklch = $fg->to('oklch');
        $lch = $oklch instanceof OklchColor ? $oklch : OklchColor::fromSrgb($oklch->toSrgb());

        $best = $fg;
        $bestHit = false;
        $dirs = [[0.0, 1.0], [1.0, 0.0]];

        foreach ($dirs as [$start, $end]) {
            $lo = min($start, $end);
            $hi = max($start, $end);
            for ($i = 0; $i < 20; ++$i) {
                $mid = 0.5 * ($lo + $hi);
                $cand = new OklchColor($mid, $lch->c, $lch->h, $lch->alpha);
                $ratio = ColorContrast::calculate($cand->toSrgb(), $bg->toSrgb());
                if ($ratio >= $targetRatio) {
                    $hi = $mid;
                    $best = $cand;
                    $bestHit = true;
                } else {
                    $lo = $mid;
                }
            }
            if ($bestHit) {
                break;
            }
        }

        return $best->to($fg::getSpaceName());
    }

    /**
     * Choose the best contrasting color from a list of candidates.
     *
     * @param list<ColorInterface> $candidates
     */
    public static function bestOn(ColorInterface $bg, array $candidates): ColorInterface
    {
        $best = null;
        $bestR = -\INF;

        foreach ($candidates as $c) {
            $r = ColorContrast::calculate($c, $bg);
            if ($r > $bestR) {
                $best = $c;
                $bestR = $r;
            }
        }

        return $best ?? Color::black();
    }
}
