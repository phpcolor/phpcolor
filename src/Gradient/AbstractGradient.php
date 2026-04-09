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
 * Abstract base class for gradient implementations.
 *
 * Provides common functionality for color interpolation and stop management.
 */
abstract readonly class AbstractGradient implements GradientInterface
{
    /**
     * @param list<GradientStop> $stops
     * @param InterpolationSpace $interpolationSpace Color space for interpolation (default: oklab)
     */
    public function __construct(
        protected array $stops = [],
        protected InterpolationSpace $interpolationSpace = InterpolationSpace::Oklab,
    ) {
    }

    /**
     * Format the color stops for CSS output.
     */
    protected function formatStops(?string $colorSpace = null): string
    {
        $sortedStops = $this->getSortedStops();
        $cssStops = array_map(
            static fn (GradientStop $stop): string => $stop->toCss($colorSpace),
            $sortedStops
        );

        return implode(', ', $cssStops);
    }

    /**
     * Get all color stops sorted by their position.
     *
     * @return list<GradientStop>
     */
    protected function getSortedStops(): array
    {
        $sorted = $this->stops;
        usort($sorted, static fn (GradientStop $a, GradientStop $b): int => $a->position <=> $b->position);

        return $sorted;
    }

    public function getStops(): array
    {
        return $this->stops;
    }

    public function interpolate(float $position): ColorInterface
    {
        $sortedStops = $this->getSortedStops();

        if (0 === \count($sortedStops)) {
            return Color::parse('#000000');
        }

        if (1 === \count($sortedStops)) {
            return $sortedStops[0]->color;
        }

        $before = null;
        $after = null;

        foreach ($sortedStops as $stop) {
            if ($stop->position <= $position) {
                $before = $stop;
            } else {
                $after = $stop;

                break;
            }
        }

        if (!$before instanceof GradientStop) {
            return $sortedStops[0]->color;
        }
        if (!$after instanceof GradientStop) {
            return $sortedStops[\count($sortedStops) - 1]->color;
        }

        if (abs($after->position - $before->position) < 1e-10) {
            return $before->color;
        }

        $t = ($position - $before->position) / ($after->position - $before->position);

        return Color::mix($before->color, $after->color, $t, $this->interpolationSpace->value);
    }
}
