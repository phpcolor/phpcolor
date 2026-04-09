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
     * Auto-distribute color stops evenly across the gradient.
     *
     * @param list<GradientStop> $stops
     *
     * @return list<GradientStop>
     */
    private static function distributeStops(array $stops): array
    {
        $count = \count($stops);
        if ($count <= 1) {
            return $stops;
        }

        $distributed = [];
        $den = $count - 1;
        foreach ($stops as $i => $stop) {
            $position = $i / $den;
            $distributed[] = new GradientStop($stop->color, $position);
        }

        return $distributed;
    }

    /**
     * Normalize stops to GradientStop objects.
     *
     * Converts colors/strings to GradientStop objects and auto-distributes positions if needed.
     *
     * @return list<GradientStop>
     */
    private static function normalizeStops(ColorInterface|GradientStop|string ...$stops): array
    {
        $gradientStops = [];
        $needsDistribution = false;

        foreach ($stops as $stop) {
            if ($stop instanceof GradientStop) {
                $gradientStops[] = $stop;
            } else {
                $color = \is_string($stop) ? Color::parse($stop) : $stop;
                $gradientStops[] = new GradientStop($color, 0.0);
                $needsDistribution = true;
            }
        }

        if (!$needsDistribution) {
            return $gradientStops;
        }

        return self::distributeStops($gradientStops);
    }
}
