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

use PhpColor\Color\ColorInterface;

/**
 * Linear gradient implementation.
 *
 * Represents a gradient that transitions along a straight line.
 * The direction can be specified as an angle (in degrees) or keywords like 'to right', 'to bottom', etc.
 */
final readonly class LinearGradient extends AbstractGradient
{
    /**
     * Create a new linear gradient instance.
     *
     * @param float              $angle              Direction angle in degrees (0 = up)
     * @param list<GradientStop> $stops              Color stops
     * @param InterpolationSpace $interpolationSpace Color space for interpolation
     */
    public function __construct(
        private float $angle = 180.0,
        array $stops = [],
        InterpolationSpace $interpolationSpace = InterpolationSpace::Oklab,
    ) {
        parent::__construct($stops, $interpolationSpace);
    }

    /**
     * Create a linear gradient with a direction keyword.
     *
     * @param string $direction Direction keyword (e.g., 'to right', 'to bottom')
     */
    public static function fromDirection(string $direction): self
    {
        $angle = match (strtolower(trim($direction))) {
            'to top' => 0.0,
            'to right' => 90.0,
            'to bottom' => 180.0,
            'to left' => 270.0,
            'to top right' => 45.0,
            'to bottom right' => 135.0,
            'to bottom left' => 225.0,
            'to top left' => 315.0,
            default => 180.0,
        };

        return new self($angle);
    }

    public function addStop(ColorInterface $color, float $position): static
    {
        $newStops = $this->stops;
        $newStops[] = new GradientStop($color, $position);

        return new self($this->angle, $newStops, $this->interpolationSpace);
    }

    /**
     * Get the direction angle in degrees.
     */
    public function getAngle(): float
    {
        return $this->angle;
    }

    public function getType(): string
    {
        return 'linear';
    }

    public function toCss(?string $colorSpace = null): string
    {
        $this->assertMinimumStops();

        $params = [
            \sprintf('%sdeg', round($this->angle, 2)),
            $this->formatInterpolationSpace(),
        ];

        return \sprintf('linear-gradient(%s, %s)', implode(' ', $params), $this->formatStops($colorSpace));
    }

    /**
     * Return a new instance with a different angle.
     */
    public function withAngle(float $angle): self
    {
        return new self($angle, $this->stops, $this->interpolationSpace);
    }
}
