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

namespace PhpColor\Color\Cache;

use PhpColor\Color\OklchColor;
use PhpColor\Color\SrgbColor;

/**
 * Internal cache for color space conversions using WeakMap.
 *
 * @internal
 */
final class ConversionCache
{
    /**
     * @var \WeakMap<SrgbColor, OklchColor>|null
     */
    private static ?\WeakMap $srgbToOklch = null;

    /**
     * @var \WeakMap<OklchColor, SrgbColor>|null
     */
    private static ?\WeakMap $oklchToSrgb = null;

    /**
     * Get the cached OKLCH color for a given sRGB color.
     */
    public static function getOklchFromSrgb(SrgbColor $srgb): ?OklchColor
    {
        if (!self::$srgbToOklch instanceof \WeakMap) {
            /** @var \WeakMap<SrgbColor, OklchColor> $map */
            $map = new \WeakMap();
            self::$srgbToOklch = $map;
        }

        return self::$srgbToOklch[$srgb] ?? null;
    }

    /**
     * Cache an OKLCH color for a given sRGB color.
     */
    public static function setOklchFromSrgb(SrgbColor $srgb, OklchColor $oklch): void
    {
        if (!self::$srgbToOklch instanceof \WeakMap) {
            /** @var \WeakMap<SrgbColor, OklchColor> $map */
            $map = new \WeakMap();
            self::$srgbToOklch = $map;
        }

        self::$srgbToOklch[$srgb] = $oklch;
    }

    /**
     * Get the cached sRGB color for a given OKLCH color.
     */
    public static function getSrgbFromOklch(OklchColor $oklch): ?SrgbColor
    {
        if (!self::$oklchToSrgb instanceof \WeakMap) {
            /** @var \WeakMap<OklchColor, SrgbColor> $map */
            $map = new \WeakMap();
            self::$oklchToSrgb = $map;
        }

        return self::$oklchToSrgb[$oklch] ?? null;
    }

    /**
     * Cache an sRGB color for a given OKLCH color.
     */
    public static function setSrgbFromOklch(OklchColor $oklch, SrgbColor $srgb): void
    {
        if (!self::$oklchToSrgb instanceof \WeakMap) {
            /** @var \WeakMap<OklchColor, SrgbColor> $map */
            $map = new \WeakMap();
            self::$oklchToSrgb = $map;
        }

        self::$oklchToSrgb[$oklch] = $srgb;
    }

    /**
     * Cache both sRGB and OKLCH colors bidirectionally.
     */
    public static function cacheBidirectional(SrgbColor $srgb, OklchColor $oklch): void
    {
        self::setOklchFromSrgb($srgb, $oklch);
        self::setSrgbFromOklch($oklch, $srgb);
    }
}
