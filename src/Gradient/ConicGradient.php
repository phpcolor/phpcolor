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
 * Conic gradient implementation.
 *
 * Represents a gradient that rotates around a center point, like a color wheel.
 * Also known as angular gradients.
 */
final readonly class ConicGradient extends AbstractGradient
{
    /**
     * Create a new conic gradient instance.
     *
     * @param float              $angle              Starting angle in degrees (0 = top)
     * @param string             $position           Center position
     * @param list<GradientStop> $stops              Color stops
     * @param InterpolationSpace $interpolationSpace Color space for interpolation
     */
    public function __construct(
        private float $angle = 0.0,
        private string $position = 'center',
        array $stops = [],
        InterpolationSpace $interpolationSpace = InterpolationSpace::Oklab,
    ) {
        parent::__construct($stops, $interpolationSpace);
    }

    /**
     * Create a default color wheel conic gradient.
     */
    public static function colorWheel(): self
    {
        return new self();
    }

    public function addStop(ColorInterface $color, float $position): static
    {
        $newStops = $this->stops;
        $newStops[] = new GradientStop($color, $position);

        return new self($this->angle, $this->position, $newStops, $this->interpolationSpace);
    }

    /**
     * Get the starting angle in degrees.
     */
    public function getAngle(): float
    {
        return $this->angle;
    }

    /**
     * Get the center position.
     */
    public function getPosition(): string
    {
        return $this->position;
    }

    public function getType(): string
    {
        return 'conic';
    }

    public function toCss(?string $colorSpace = null): string
    {
        $stopsStr = $this->formatStops($colorSpace);

        $params = [];

        if (abs($this->angle) > 1e-6) {
            $params[] = \sprintf('from %sdeg', round($this->angle, 2));
        }

        if ('center' !== $this->position) {
            $params[] = 'at '.$this->position;
        }

        $paramsStr = implode(' ', $params);

        if ([] === $this->stops) {
            return '' === $paramsStr || '0' === $paramsStr ? 'conic-gradient()' : \sprintf('conic-gradient(%s)', $paramsStr);
        }

        return '' === $paramsStr || '0' === $paramsStr
            ? \sprintf('conic-gradient(%s)', $stopsStr)
            : \sprintf('conic-gradient(%s, %s)', $paramsStr, $stopsStr);
    }

    /**
     * Return a new instance with a different angle.
     */
    public function withAngle(float $angle): self
    {
        return new self($angle, $this->position, $this->stops, $this->interpolationSpace);
    }

    /**
     * Return a new instance with a different position.
     */
    public function withPosition(string $position): self
    {
        return new self($this->angle, $position, $this->stops, $this->interpolationSpace);
    }
}
