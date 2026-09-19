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
use PhpColor\Color\Exception\InvalidArgumentException;
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\OklabColor;
use PhpColor\Color\OklchColor;
use PhpColor\Color\Space\ColorSpaces;
use PhpColor\Color\SrgbColor;

/**
 * Utilities for gamut diagnostics, clipping, and perceptual mapping.
 */
final readonly class Gamut
{
    private const float CHROMA_EPSILON = 0.0001;
    private const float JND = 0.02;
    private const int MAX_ITERATIONS = 32;

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

    /**
     * Clip a color component-by-component into the requested RGB gamut.
     *
     * The color is converted to the target space before each RGB channel is
     * independently limited to the [0, 1] range. Alpha is preserved. Only
     * sRGB is currently supported as a target gamut.
     *
     * @param ColorInterface $color Color to clip
     * @param string         $space Target RGB gamut (`srgb` or its `rgb` alias)
     *
     * @return ColorInterface A color expressed within the requested RGB gamut
     *
     * @throws InvalidArgumentException If a color component is not finite
     * @throws InvalidColorException    If the target gamut is not supported
     */
    public static function clip(ColorInterface $color, string $space): ColorInterface
    {
        self::assertSrgb($space);

        $converted = $color->toSrgb();
        self::assertFinite($converted);

        return new SrgbColor(
            self::clamp($converted->r),
            self::clamp($converted->g),
            self::clamp($converted->b),
            $converted->a,
        );
    }

    /**
     * Perceptually map a color into the requested RGB gamut.
     *
     * Mapping preserves OkLCh lightness and hue while reducing chroma until
     * the clipped candidate reaches the local minimum noticeable difference
     * boundary. Alpha is preserved. Only sRGB is currently supported as a
     * target gamut.
     *
     * @param ColorInterface $color Color to map
     * @param string         $space Target RGB gamut (`srgb` or its `rgb` alias)
     *
     * @return ColorInterface A perceptually mapped color in the requested RGB gamut
     *
     * @throws InvalidArgumentException If a color component is not finite
     * @throws InvalidColorException    If the target gamut is not supported
     */
    public static function map(ColorInterface $color, string $space): ColorInterface
    {
        self::assertSrgb($space);
        self::assertFinite($color);

        $origin = OklchColor::from($color);
        self::assertFinite($origin);

        if (1.0 <= $origin->l) {
            return new SrgbColor(1.0, 1.0, 1.0, $origin->alpha);
        }

        if (0.0 >= $origin->l) {
            return new SrgbColor(0.0, 0.0, 0.0, $origin->alpha);
        }

        if (self::isInGamut($origin, 'srgb', 0.0)) {
            return $origin->toSrgb();
        }

        $current = $origin;
        $clipped = self::clip($current, $space);
        $distance = self::oklabDistance($clipped, $current);

        if (self::JND > $distance) {
            return $clipped;
        }

        $min = 0.0;
        $max = $origin->c;
        $minInGamut = true;

        for ($iteration = 0; $iteration < self::MAX_ITERATIONS && $max - $min > self::CHROMA_EPSILON; ++$iteration) {
            $chroma = ($min + $max) / 2.0;
            $current = new OklchColor($origin->l, $chroma, $origin->h, $origin->alpha);

            if ($minInGamut && self::isInGamut($current, 'srgb', 0.0)) {
                $min = $chroma;

                continue;
            }

            $clipped = self::clip($current, $space);
            $distance = self::oklabDistance($clipped, $current);

            if (self::JND > $distance) {
                if (self::CHROMA_EPSILON > self::JND - $distance) {
                    return $clipped;
                }

                $minInGamut = false;
                $min = $chroma;

                continue;
            }

            $max = $chroma;
        }

        return $clipped;
    }

    private static function assertFinite(ColorInterface $color): void
    {
        foreach ([...array_values($color->getChannels()), $color->getAlpha()] as $value) {
            if (!is_finite($value)) {
                throw new InvalidArgumentException('Gamut operations require finite color channels and alpha.');
            }
        }
    }

    private static function assertSrgb(string $space): void
    {
        if ('srgb' !== ColorSpaces::normalize($space)) {
            throw new InvalidColorException(\sprintf('Unsupported clipping and mapping gamut "%s"; only sRGB is currently supported.', $space));
        }
    }

    private static function clamp(float $value): float
    {
        return min(max($value, 0.0), 1.0);
    }

    private static function oklabDistance(ColorInterface $first, ColorInterface $second): float
    {
        $firstOklab = OklabColor::from($first);
        $secondOklab = OklabColor::from($second);

        return sqrt(
            ($firstOklab->l - $secondOklab->l) ** 2
            + ($firstOklab->a - $secondOklab->a) ** 2
            + ($firstOklab->b - $secondOklab->b) ** 2
        );
    }
}
