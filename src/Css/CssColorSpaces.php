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

namespace PhpColor\Color\Css;

use PhpColor\Color\A98RgbColor;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\DisplayP3Color;
use PhpColor\Color\LabColor;
use PhpColor\Color\LchColor;
use PhpColor\Color\LinearSrgbColor;
use PhpColor\Color\OklabColor;
use PhpColor\Color\OklchColor;
use PhpColor\Color\ProPhotoColor;
use PhpColor\Color\Rec2020Color;
use PhpColor\Color\Space\ColorSpaces;
use PhpColor\Color\SrgbColor;
use PhpColor\Color\XyzColor;

/**
 * Builders for CSS color() function output across different color spaces.
 */
final class CssColorSpaces
{
    /**
     * Build an A98 RGB color from channel values.
     *
     * @param array<string,float> $ch
     */
    public static function buildA98(array $ch, float $a): A98RgbColor
    {
        $r = $ch['r'] ?? 0.0;
        $g = $ch['g'] ?? 0.0;
        $b = $ch['b'] ?? 0.0;

        return new A98RgbColor($r, $g, $b, $a);
    }

    /**
     * Build a Display P3 color from channel values.
     *
     * @param array<string,float> $ch
     */
    public static function buildDisplayP3(array $ch, float $a): DisplayP3Color
    {
        $r = $ch['r'] ?? 0.0;
        $g = $ch['g'] ?? 0.0;
        $b = $ch['b'] ?? 0.0;

        return new DisplayP3Color($r, $g, $b, $a);
    }

    /**
     * Get a builder callable for a specific color space.
     *
     * @return (callable(array<string,float>, float): ColorInterface)|null
     */
    public static function builderFor(string $space): ?callable
    {
        $s = ColorSpaces::normalize($space);

        return match ($s) {
            'srgb' => self::buildSrgb(...),
            'srgb-linear' => self::buildLinearSrgb(...),
            'display-p3' => self::buildDisplayP3(...),
            'xyz-d65', 'xyz' => self::buildXyz(...),
            'rec2020' => self::buildRec2020(...),
            'prophoto-rgb' => self::buildProPhoto(...),
            'a98-rgb' => self::buildA98(...),
            'oklab' => self::buildOklab(...),
            'oklch' => self::buildOklch(...),
            'lab' => self::buildLab(...),
            'lch' => self::buildLch(...),
            default => null,
        };
    }

    /**
     * Build a Linear sRGB color from channel values.
     *
     * @param array<string,float> $ch
     */
    public static function buildLinearSrgb(array $ch, float $a): LinearSrgbColor
    {
        $r = $ch['r'] ?? 0.0;
        $g = $ch['g'] ?? 0.0;
        $b = $ch['b'] ?? 0.0;

        return new LinearSrgbColor($r, $g, $b, $a);
    }

    /**
     * Build a Lab color from channel values.
     *
     * @param array<string,float> $ch
     */
    public static function buildLab(array $ch, float $a): LabColor
    {
        return LabColor::fromChannels(['l' => $ch['l'] ?? 0.0, 'a' => $ch['a'] ?? 0.0, 'b' => $ch['b'] ?? 0.0], $a);
    }

    /**
     * Build an LCH color from channel values.
     *
     * @param array<string,float> $ch
     */
    public static function buildLch(array $ch, float $a): LchColor
    {
        return LchColor::fromChannels(['l' => $ch['l'] ?? 0.0, 'c' => $ch['c'] ?? 0.0, 'h' => $ch['h'] ?? 0.0], $a);
    }

    /**
     * Build an Oklab color from channel values.
     *
     * @param array<string,float> $ch
     */
    public static function buildOklab(array $ch, float $a): OklabColor
    {
        return OklabColor::fromChannels(['l' => $ch['l'] ?? 0.0, 'a' => $ch['a'] ?? 0.0, 'b' => $ch['b'] ?? 0.0], $a);
    }

    /**
     * Build an Oklch color from channel values.
     *
     * @param array<string,float> $ch
     */
    public static function buildOklch(array $ch, float $a): OklchColor
    {
        return OklchColor::fromChannels(['l' => $ch['l'] ?? 0.0, 'c' => $ch['c'] ?? 0.0, 'h' => $ch['h'] ?? 0.0], $a);
    }

    /**
     * Build a ProPhoto RGB color from channel values.
     *
     * @param array<string,float> $ch
     */
    public static function buildProPhoto(array $ch, float $a): ProPhotoColor
    {
        $r = $ch['r'] ?? 0.0;
        $g = $ch['g'] ?? 0.0;
        $b = $ch['b'] ?? 0.0;

        return new ProPhotoColor($r, $g, $b, $a);
    }

    /**
     * Build a Rec.2020 color from channel values.
     *
     * @param array<string,float> $ch
     */
    public static function buildRec2020(array $ch, float $a): Rec2020Color
    {
        $r = $ch['r'] ?? 0.0;
        $g = $ch['g'] ?? 0.0;
        $b = $ch['b'] ?? 0.0;

        return new Rec2020Color($r, $g, $b, $a);
    }

    /**
     * Build an sRGB color from channel values.
     *
     * @param array<string,float> $ch
     */
    public static function buildSrgb(array $ch, float $a): SrgbColor
    {
        $r = $ch['r'] ?? 0.0;
        $g = $ch['g'] ?? 0.0;
        $b = $ch['b'] ?? 0.0;

        return new SrgbColor($r, $g, $b, $a);
    }

    /**
     * Build an XYZ color from channel values.
     *
     * @param array<string,float> $ch
     */
    public static function buildXyz(array $ch, float $a): XyzColor
    {
        $x = $ch['x'] ?? ($ch['r'] ?? 0.0);
        $y = $ch['y'] ?? ($ch['g'] ?? 0.0);
        $z = $ch['z'] ?? ($ch['b'] ?? 0.0);

        return new XyzColor($x, $y, $z, $a);
    }
}
