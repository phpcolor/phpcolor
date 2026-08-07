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

namespace PhpColor\Color;

use PhpColor\Color\Contrast\ColorContrast;
use PhpColor\Color\Distance\ColorDistance;
use PhpColor\Color\Distance\ColorDistanceInterface;
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\Parser\ColorParser;

/**
 * Main entry point for the PHPColor library.
 *
 * Provides factory methods for common colors and high-level utilities for parsing,
 * mixing, and analyzing colors.
 */
final class Color
{
    /**
     * Create a new sRGB black color.
     */
    public static function black(): SrgbColor
    {
        return new SrgbColor(0.0, 0.0, 0.0);
    }

    /**
     * Create a new sRGB blue color.
     */
    public static function blue(): SrgbColor
    {
        return new SrgbColor(0.0, 0.0, 1.0);
    }

    /**
     * Calculate the WCAG 2.1 contrast ratio between two colors.
     */
    public static function contrast(ColorInterface|string $a, ColorInterface|string $b): float
    {
        return ColorContrast::calculate(self::from($a), self::from($b));
    }

    /**
     * Create a new sRGB cyan color.
     */
    public static function cyan(): SrgbColor
    {
        return new SrgbColor(0.0, 1.0, 1.0);
    }

    /**
     * Calculate color distance using a specific algorithm.
     *
     * Supported algorithms:
     * - 'CIEDE2000' or 'DE2000': Most accurate modern formula
     * - 'DeltaE94' or 'DE94': Improved 1994 formula
     * - 'CMC' or 'CMC(2:1)': UK textile industry standard (acceptability)
     * - 'CMC(1:1)': CMC perceptibility variant
     *
     * @param ColorInterface|string         $a         First color or CSS color string
     * @param ColorInterface|string         $b         Second color or CSS color string
     * @param string|ColorDistanceInterface $algorithm Algorithm name or instance
     *
     * @return float The calculated color distance value
     *
     * @throws InvalidColorException If the algorithm is not supported
     */
    public static function distance(
        ColorInterface|string $a,
        ColorInterface|string $b,
        string|ColorDistanceInterface $algorithm = 'CIEDE2000',
    ): float {
        return ColorDistance::calculate(self::from($a), self::from($b), $algorithm);
    }

    /**
     * Calculate the perceptual color difference between two colors using CIEDE2000.
     */
    public static function deltaE(ColorInterface|string $a, ColorInterface|string $b): float
    {
        return ColorDistance::deltaE(self::from($a), self::from($b));
    }

    /**
     * Normalize input to a ColorInterface instance.
     *
     * Accepts either a ColorInterface instance (returned as-is) or a CSS color string (parsed).
     * This is the single entry point for input normalization across the API.
     *
     * @param ColorInterface|string $input Color object or CSS string
     */
    public static function from(ColorInterface|string $input): ColorInterface
    {
        return $input instanceof ColorInterface ? $input : self::parse($input);
    }

    /**
     * Create a new sRGB gray color.
     */
    public static function gray(): SrgbColor
    {
        return new SrgbColor(128 / 255, 128 / 255, 128 / 255);
    }

    /**
     * Create a new sRGB green color.
     */
    public static function green(): SrgbColor
    {
        return new SrgbColor(0.0, 1.0, 0.0);
    }

    /**
     * Create a new sRGB color from a hexadecimal string.
     */
    public static function hex(string $hex): SrgbColor
    {
        return SrgbColor::parseHex($hex);
    }

    /**
     * Create a new sRGB magenta color.
     */
    public static function magenta(): SrgbColor
    {
        return new SrgbColor(1.0, 0.0, 1.0);
    }

    /**
     * Mix two colors together in a specified color space.
     *
     * Supports mixing in sRGB, linear sRGB and Oklab spaces. Color channels are interpolated with
     * premultiplied alpha, as specified by CSS Color 4, so a transparent endpoint does not tint
     * the result.
     *
     * @throws InvalidColorException
     */
    public static function mix(ColorInterface|string $a, ColorInterface|string $b, float $t, string|ColorInterface $in = 'oklab'): ColorInterface
    {
        $a = self::from($a);
        $b = self::from($b);

        $space = $in instanceof ColorInterface ? $in : strtolower(str_replace(['_', ' '], ['-', '-'], trim($in)));

        $t = max(0.0, min(1.0, $t));
        if ($space instanceof SrgbColor || 'srgb' === $space) {
            $ra = $a->toSrgb();
            $rb = $b->toSrgb();

            [$r, $g, $b2, $a2] = self::lerpPremultiplied(
                [$ra->r, $ra->g, $ra->b],
                $ra->a,
                [$rb->r, $rb->g, $rb->b],
                $rb->a,
                $t,
            );

            return new SrgbColor($r, $g, $b2, $a2);
        }

        if ($space instanceof LinearSrgbColor || 'srgb-linear' === $space) {
            $ra = $a->toSrgb();
            $rb = $b->toSrgb();

            $la = [
                self::srgbToLinear($ra->r),
                self::srgbToLinear($ra->g),
                self::srgbToLinear($ra->b),
            ];
            $lb = [
                self::srgbToLinear($rb->r),
                self::srgbToLinear($rb->g),
                self::srgbToLinear($rb->b),
            ];

            [$lr0, $lr1, $lr2, $a2] = self::lerpPremultiplied($la, $ra->a, $lb, $rb->a, $t);

            $r = self::linearToSrgb($lr0);
            $g = self::linearToSrgb($lr1);
            $b2 = self::linearToSrgb($lr2);

            return new SrgbColor($r, $g, $b2, $a2);
        }

        if ($space instanceof OklabColor || 'oklab' === $space) {
            $oa = $a->to('oklab');
            $ob = $b->to('oklab');

            $ra = $oa instanceof OklabColor ? $oa : OklabColor::fromSrgb($oa->toSrgb());
            $rb = $ob instanceof OklabColor ? $ob : OklabColor::fromSrgb($ob->toSrgb());

            [$l, $aVal, $bVal, $alpha] = self::lerpPremultiplied(
                [$ra->l, $ra->a, $ra->b],
                $ra->alpha,
                [$rb->l, $rb->a, $rb->b],
                $rb->alpha,
                $t,
            );

            return new OklabColor($l, $aVal, $bVal, $alpha);
        }

        throw new InvalidColorException(\sprintf('Mixing space "%s" is not supported yet.', \is_string($space) ? $space : $space::class));
    }

    /**
     * Create a new Oklab color.
     */
    public static function oklab(float $l, float $a, float $b, float $alpha = 1.0): OklabColor
    {
        return new OklabColor($l, $a, $b, $alpha);
    }

    /**
     * Create a new Oklch color.
     */
    public static function oklch(float $l, float $c, float $h, float $alpha = 1.0): OklchColor
    {
        return new OklchColor($l, $c, $h, $alpha);
    }

    /**
     * Create a new sRGB orange color.
     */
    public static function orange(): SrgbColor
    {
        return new SrgbColor(1.0, 165 / 255, 0.0);
    }

    /**
     * Parse a CSS color string into a ColorInterface instance.
     *
     * Supports hex, rgb(), rgba(), hsl(), hsla(), oklab(), oklch(), lab(), lch(),
     * and named colors.
     */
    public static function parse(string $input): ColorInterface
    {
        return ColorParser::parse($input);
    }

    /**
     * Get the primary sRGB colors (red, green, blue).
     *
     * @return array<string, SrgbColor>
     */
    public static function primaries(float $alpha = 1.0): array
    {
        return [
            'red' => self::red()->withAlpha($alpha),
            'green' => self::green()->withAlpha($alpha),
            'blue' => self::blue()->withAlpha($alpha),
        ];
    }

    /**
     * Create a new sRGB purple color.
     */
    public static function purple(): SrgbColor
    {
        return new SrgbColor(128 / 255, 0.0, 128 / 255);
    }

    /**
     * Create a new sRGB red color.
     */
    public static function red(): SrgbColor
    {
        return new SrgbColor(1.0, 0.0, 0.0);
    }

    /**
     * Create a new sRGB color from component values.
     */
    public static function rgb(float $r, float $g, float $b, float $a = 1.0): SrgbColor
    {
        return new SrgbColor($r, $g, $b, $a);
    }

    /**
     * Get the secondary sRGB colors (cyan, magenta, yellow).
     *
     * @return array<string, SrgbColor>
     */
    public static function secondaries(float $alpha = 1.0): array
    {
        return [
            'cyan' => self::cyan()->withAlpha($alpha),
            'magenta' => self::magenta()->withAlpha($alpha),
            'yellow' => self::yellow()->withAlpha($alpha),
        ];
    }

    /**
     * Create a new sRGB teal color.
     */
    public static function teal(): SrgbColor
    {
        return new SrgbColor(0.0, 128 / 255, 128 / 255);
    }

    /**
     * Attempt to normalize input to ColorInterface without throwing exceptions.
     *
     * Returns null if the input string cannot be parsed.
     */
    public static function tryFrom(ColorInterface|string $input): ?ColorInterface
    {
        if ($input instanceof ColorInterface) {
            return $input;
        }

        try {
            return self::parse($input);
        } catch (Exception\ParseException) {
            return null;
        }
    }

    /**
     * Create a new sRGB white color.
     */
    public static function white(): SrgbColor
    {
        return new SrgbColor(1.0, 1.0, 1.0);
    }

    /**
     * Create a new sRGB yellow color.
     */
    public static function yellow(): SrgbColor
    {
        return new SrgbColor(1.0, 1.0, 0.0);
    }

    private static function lerp(float $a, float $b, float $t): float
    {
        return $a + ($b - $a) * $t;
    }

    /**
     * Interpolate three color channels and alpha using premultiplied alpha.
     *
     * Each channel is weighted by its own alpha before interpolation, then divided by the
     * interpolated alpha. When that alpha is zero the un-premultiplied channels are undefined
     * per CSS Color 4, so the plain channel interpolation is returned instead.
     *
     * @param array{float, float, float} $ca
     * @param array{float, float, float} $cb
     *
     * @return array{float, float, float, float} The interpolated channels followed by alpha
     */
    private static function lerpPremultiplied(array $ca, float $aa, array $cb, float $ab, float $t): array
    {
        $alpha = self::lerp($aa, $ab, $t);

        if (0.0 >= $alpha) {
            return [
                self::lerp($ca[0], $cb[0], $t),
                self::lerp($ca[1], $cb[1], $t),
                self::lerp($ca[2], $cb[2], $t),
                $alpha,
            ];
        }

        return [
            self::lerp($ca[0] * $aa, $cb[0] * $ab, $t) / $alpha,
            self::lerp($ca[1] * $aa, $cb[1] * $ab, $t) / $alpha,
            self::lerp($ca[2] * $aa, $cb[2] * $ab, $t) / $alpha,
            $alpha,
        ];
    }

    private static function linearToSrgb(float $c): float
    {
        if (0.0031308 >= $c) {
            return 12.92 * $c;
        }

        return 1.055 * ($c ** (1.0 / 2.4)) - 0.055;
    }

    private static function srgbToLinear(float $c): float
    {
        if (0.04045 >= $c) {
            return $c / 12.92;
        }

        return (($c + 0.055) / 1.055) ** 2.4;
    }
}
