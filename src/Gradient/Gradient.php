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

namespace PhpColor\Color\Gradient;

use PhpColor\Color\Color;
use PhpColor\Color\ColorInterface;

/**
 * Facade for creating gradients with a clean API.
 *
 * Provides static factory methods for convenient gradient creation.
 * Supports both direct construction and builder pattern.
 */
final class Gradient
{
    /**
     * Create a conic gradient.
     *
     * Can be used in two ways:
     * 1. Direct: Gradient::conic(0, '#ff0000', '#0000ff')
     * 2. Builder: Gradient::conic()->from('#ff0000')->to('#0000ff')
     *
     * @param float                                   $angle    Starting angle in degrees (0 = top, 90 = right, etc.)
     * @param ColorInterface|GradientStop|string|null ...$stops Color stops (auto-distributed if no positions specified)
     *
     * @return ConicGradient|GradientBuilder ConicGradient if stops provided, GradientBuilder otherwise
     */
    public static function conic(float $angle = 0.0, ColorInterface|GradientStop|string|null ...$stops): ConicGradient|GradientBuilder
    {
        $stops = array_filter($stops, static fn (\PhpColor\Color\ColorInterface|\PhpColor\Color\Gradient\GradientStop|string|null $stop): bool => null !== $stop);

        if ([] === $stops) {
            return GradientBuilder::conic($angle);
        }

        $gradientStops = self::normalizeStops(...$stops);

        return new ConicGradient($angle, stops: $gradientStops);
    }

    /**
     * Create a linear gradient.
     *
     * Can be used in two ways:
     * 1. Direct: Gradient::linear(180, '#ff0000', '#0000ff')
     * 2. Builder: Gradient::linear()->from('#ff0000')->to('#0000ff')
     *
     * @param float                                   $angle    Direction angle in degrees (0 = up, 90 = right, 180 = down, 270 = left)
     * @param ColorInterface|GradientStop|string|null ...$stops Color stops (auto-distributed if no positions specified)
     *
     * @return LinearGradient|GradientBuilder LinearGradient if stops provided, GradientBuilder otherwise
     */
    public static function linear(float $angle = 180.0, ColorInterface|GradientStop|string|null ...$stops): LinearGradient|GradientBuilder
    {
        $stops = array_filter($stops, static fn (\PhpColor\Color\ColorInterface|\PhpColor\Color\Gradient\GradientStop|string|null $stop): bool => null !== $stop);

        if ([] === $stops) {
            return GradientBuilder::linear($angle);
        }

        $gradientStops = self::normalizeStops(...$stops);

        return new LinearGradient($angle, $gradientStops);
    }

    /**
     * Create a radial gradient.
     *
     * Can be used in two ways:
     * 1. Direct: Gradient::radial('#ff0000', '#0000ff')
     * 2. Builder: Gradient::radial()->circle()->from('#ff0000')->to('#0000ff')
     *
     * @param ColorInterface|GradientStop|string|null ...$stops Color stops (auto-distributed if no positions specified)
     *
     * @return RadialGradient|GradientBuilder RadialGradient if stops provided, GradientBuilder otherwise
     */
    public static function radial(ColorInterface|GradientStop|string|null ...$stops): RadialGradient|GradientBuilder
    {
        $stops = array_filter($stops, static fn (\PhpColor\Color\ColorInterface|\PhpColor\Color\Gradient\GradientStop|string|null $stop): bool => null !== $stop);

        if ([] === $stops) {
            return GradientBuilder::radial();
        }

        $gradientStops = self::normalizeStops(...$stops);

        return new RadialGradient(stops: $gradientStops);
    }

    /**
     * Resolve the positions of stops that were given without one.
     *
     * Explicit positions are kept as-is. Stops without a position are spread evenly between the
     * surrounding explicit positions: from 0 before the first explicit position, up to 1 after the
     * last one, and evenly across the whole gradient when no position is explicit at all.
     *
     * @internal
     *
     * @param list<float|null> $positions Explicit positions, null where the caller gave none
     *
     * @return list<float>
     */
    public static function distributePositions(array $positions): array
    {
        $count = \count($positions);
        $resolved = [];
        $index = 0;

        while ($index < $count) {
            $position = $positions[$index];
            if (null !== $position) {
                $resolved[] = $position;
                ++$index;

                continue;
            }

            $last = $index;
            while ($last + 1 < $count && null === $positions[$last + 1]) {
                ++$last;
            }

            $before = $index > 0 ? $positions[$index - 1] : null;
            $after = $last + 1 < $count ? $positions[$last + 1] : null;
            $length = $last - $index + 1;

            for ($i = 0; $i < $length; ++$i) {
                if (null !== $before && null !== $after) {
                    $resolved[] = $before + ($after - $before) * ($i + 1) / ($length + 1);
                } elseif (null !== $after) {
                    $resolved[] = $after * $i / $length;
                } elseif (null !== $before) {
                    $resolved[] = $before + (1.0 - $before) * ($i + 1) / $length;
                } else {
                    $resolved[] = $length > 1 ? (float) $i / ($length - 1) : 0.0;
                }
            }

            $index = $last + 1;
        }

        return $resolved;
    }

    /**
     * Normalize stops to GradientStop objects.
     *
     * Converts colors/strings to GradientStop objects, keeping the positions carried by the
     * GradientStop arguments and distributing the others around them.
     *
     * @return list<GradientStop>
     */
    private static function normalizeStops(ColorInterface|GradientStop|string ...$stops): array
    {
        $colors = [];
        $positions = [];

        foreach ($stops as $stop) {
            if ($stop instanceof GradientStop) {
                $colors[] = $stop->color;
                $positions[] = $stop->position;
            } else {
                $colors[] = \is_string($stop) ? Color::parse($stop) : $stop;
                $positions[] = null;
            }
        }

        $gradientStops = [];
        foreach (self::distributePositions($positions) as $i => $position) {
            $gradientStops[] = new GradientStop($colors[$i], $position);
        }

        return $gradientStops;
    }
}
