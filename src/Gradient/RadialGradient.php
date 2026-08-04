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
 * Radial gradient implementation.
 *
 * Represents a gradient that radiates from a center point outward in a circular or elliptical pattern.
 */
final readonly class RadialGradient extends AbstractGradient
{
    private string $shape;
    private string $size;

    /**
     * Create a new radial gradient instance.
     *
     * @param RadialShape|string $shape              Gradient shape
     * @param RadialSize|string  $size               Gradient size keyword
     * @param string             $position           Center position
     * @param list<GradientStop> $stops              Color stops
     * @param InterpolationSpace $interpolationSpace Color space for interpolation
     */
    public function __construct(
        RadialShape|string $shape = RadialShape::Ellipse,
        RadialSize|string $size = RadialSize::FarthestCorner,
        private string $position = 'center',
        array $stops = [],
        InterpolationSpace $interpolationSpace = InterpolationSpace::Oklab,
    ) {
        $this->shape = $shape instanceof RadialShape ? $shape->value : $shape;
        $this->size = $size instanceof RadialSize ? $size->value : $size;

        parent::__construct($stops, $interpolationSpace);
    }

    /**
     * Create a new circular radial gradient instance.
     */
    public static function circle(): self
    {
        return new self('circle');
    }

    /**
     * Create a new elliptical radial gradient instance.
     */
    public static function ellipse(): self
    {
        return new self('ellipse');
    }

    public function addStop(ColorInterface $color, float $position): static
    {
        $newStops = $this->stops;
        $newStops[] = new GradientStop($color, $position);

        return new self($this->shape, $this->size, $this->position, $newStops, $this->interpolationSpace);
    }

    /**
     * Get the center position.
     */
    public function getPosition(): string
    {
        return $this->position;
    }

    /**
     * Get the gradient shape.
     */
    public function getShape(): string
    {
        return $this->shape;
    }

    /**
     * Get the gradient size keyword.
     */
    public function getSize(): string
    {
        return $this->size;
    }

    public function getType(): string
    {
        return 'radial';
    }

    public function toCss(?string $colorSpace = null): string
    {
        $this->assertMinimumStops();

        $stopsStr = $this->formatStops($colorSpace);

        $params = [];

        if ('circle' === $this->shape) {
            $params[] = 'circle';
        }

        if ('farthest-corner' !== $this->size) {
            $params[] = $this->size;
        }

        if ('center' !== $this->position) {
            $params[] = 'at '.$this->position;
        }

        $params[] = $this->formatInterpolationSpace();

        return \sprintf('radial-gradient(%s, %s)', implode(' ', $params), $stopsStr);
    }

    /**
     * Return a new instance with a different position.
     */
    public function withPosition(string $position): self
    {
        return new self($this->shape, $this->size, $position, $this->stops, $this->interpolationSpace);
    }

    /**
     * Return a new instance with a different shape.
     */
    public function withShape(string $shape): self
    {
        return new self($shape, $this->size, $this->position, $this->stops, $this->interpolationSpace);
    }

    /**
     * Return a new instance with a different size.
     */
    public function withSize(string $size): self
    {
        return new self($this->shape, $size, $this->position, $this->stops, $this->interpolationSpace);
    }
}
