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
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\OklchColor;

/**
 * Conic gradient implementation.
 *
 * Represents a gradient that rotates around a center point, like a color wheel.
 * Also known as angular gradients.
 */
final readonly class ConicGradient extends AbstractGradient
{
    /**
     * Chroma of the color wheel hues.
     */
    private const float WHEEL_CHROMA = 0.15;

    /**
     * Lightness of the color wheel hues.
     */
    private const float WHEEL_LIGHTNESS = 0.7;

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
     * Create a color wheel conic gradient of evenly spaced OKLCH hues.
     *
     * The wheel is closed by repeating the first hue at position 1.0.
     *
     * @param int $steps Number of hues around the wheel (at least 2)
     *
     * @throws InvalidColorException if fewer than 2 steps are requested
     */
    public static function colorWheel(int $steps = 12): self
    {
        if ($steps < 2) {
            throw new InvalidColorException(\sprintf('A color wheel requires at least 2 steps, %d given.', $steps));
        }

        $stops = [];

        for ($i = 0; $i < $steps; ++$i) {
            $stops[] = new GradientStop(new OklchColor(self::WHEEL_LIGHTNESS, self::WHEEL_CHROMA, $i * (360.0 / $steps)), $i / $steps);
        }

        $stops[] = new GradientStop(new OklchColor(self::WHEEL_LIGHTNESS, self::WHEEL_CHROMA, 0.0), 1.0);

        return new self(stops: $stops);
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
        $this->assertMinimumStops();

        $stopsStr = $this->formatStops($colorSpace);

        $params = [];

        if (abs($this->angle) > 1e-6) {
            $params[] = \sprintf('from %sdeg', round($this->angle, 2));
        }

        if ('center' !== $this->position) {
            $params[] = 'at '.$this->position;
        }

        $params[] = $this->formatInterpolationSpace();

        return \sprintf('conic-gradient(%s, %s)', implode(' ', $params), $stopsStr);
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
