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
use PhpColor\Color\Exception\InvalidColorException;

/**
 * Fluent builder for creating gradients with great developer experience.
 *
 * Provides a chainable API for constructing gradients with an intuitive syntax.
 */
final class GradientBuilder
{
    private ?float $angle = null;
    private InterpolationSpace $interpolationSpace = InterpolationSpace::Oklab;
    private ?string $position = null;
    private ?string $shape = null;
    private ?string $size = null;
    /** @var list<GradientStop> */
    private array $stops = [];

    private string $type = 'linear';

    /**
     * Create a new gradient builder instance.
     */
    private function __construct()
    {
    }

    /**
     * Start building a new conic gradient.
     */
    public static function conic(float $angle = 0.0): self
    {
        $builder = new self();
        $builder->type = 'conic';
        $builder->angle = $angle;
        $builder->position = 'center';

        return $builder;
    }

    /**
     * Start building a new linear gradient.
     */
    public static function linear(float $angle = 180.0): self
    {
        $builder = new self();
        $builder->type = 'linear';
        $builder->angle = $angle;

        return $builder;
    }

    /**
     * Start building a new radial gradient.
     */
    public static function radial(): self
    {
        $builder = new self();
        $builder->type = 'radial';
        $builder->shape = 'ellipse';
        $builder->size = 'farthest-corner';
        $builder->position = 'center';

        return $builder;
    }

    /**
     * Set the position for radial or conic gradients.
     *
     * @param string $position CSS position keyword ('center', 'top', 'bottom', 'left', 'right') or CSS length/percentage values
     */
    public function at(string $position): self
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Build the gradient.
     */
    public function build(): GradientInterface
    {
        return match ($this->type) {
            'linear' => new LinearGradient(
                $this->angle ?? 180.0,
                $this->stops,
                $this->interpolationSpace
            ),
            'radial' => new RadialGradient(
                $this->shape ?? 'ellipse',
                $this->size ?? 'farthest-corner',
                $this->position ?? 'center',
                $this->stops,
                $this->interpolationSpace
            ),
            'conic' => new ConicGradient(
                $this->angle ?? 0.0,
                $this->position ?? 'center',
                $this->stops,
                $this->interpolationSpace
            ),
            default => throw new InvalidColorException('Unknown gradient type'),
        };
    }

    /**
     * Set as a circular radial gradient.
     */
    public function circle(): self
    {
        return $this->shape(RadialShape::Circle);
    }

    /**
     * Add a color stop at the start (position 0.0).
     *
     * @param ColorInterface|string $color Color object or CSS color string
     */
    public function from(ColorInterface|string $color): self
    {
        return $this->stop($color, 0.0);
    }

    /**
     * Set the interpolation color space.
     *
     * @param InterpolationSpace|string $colorSpace The interpolation space (enum or string for convenience)
     */
    public function in(InterpolationSpace|string $colorSpace): self
    {
        $this->interpolationSpace = $colorSpace instanceof InterpolationSpace
            ? $colorSpace
            : InterpolationSpace::from($colorSpace);

        return $this;
    }

    /**
     * Set the shape for radial gradients.
     *
     * @param RadialShape|string $shape Gradient shape (RadialShape::Circle, RadialShape::Ellipse, or 'circle'/'ellipse')
     */
    public function shape(RadialShape|string $shape): self
    {
        if ('radial' === $this->type) {
            $this->shape = $shape instanceof RadialShape ? $shape->value : $shape;
        }

        return $this;
    }

    /**
     * Set the size for radial gradients.
     *
     * @param RadialSize|string $size Size keyword (RadialSize enum or 'closest-side'/'farthest-side'/'closest-corner'/'farthest-corner')
     */
    public function size(RadialSize|string $size): self
    {
        if ('radial' === $this->type) {
            $this->size = $size instanceof RadialSize ? $size->value : $size;
        }

        return $this;
    }

    /**
     * Add a color stop at a specific position.
     *
     * @param ColorInterface|string $color    Color object or CSS color string
     * @param float                 $position Position (0.0 to 1.0)
     */
    public function stop(ColorInterface|string $color, float $position): self
    {
        $colorObj = \is_string($color) ? Color::parse($color) : $color;
        $this->stops[] = new GradientStop($colorObj, $position);

        return $this;
    }

    /**
     * Add multiple color stops with automatic position distribution.
     *
     * Accepts colors, color strings, or GradientStop objects. If positions are not specified
     * (i.e., colors/strings), they will be auto-distributed evenly across the gradient.
     *
     * @param ColorInterface|GradientStop|string ...$stops Color stops to add
     */
    public function stops(ColorInterface|GradientStop|string ...$stops): self
    {
        $colorOnlyStops = [];

        foreach ($stops as $stop) {
            if ($stop instanceof GradientStop) {
                $this->stops[] = $stop;
            } else {
                $color = \is_string($stop) ? Color::parse($stop) : $stop;
                $colorOnlyStops[] = $color;
            }
        }

        if ([] !== $colorOnlyStops) {
            $count = \count($colorOnlyStops);
            foreach ($colorOnlyStops as $i => $color) {
                $position = $count > 1 ? $i / ($count - 1) : 0.5;
                $this->stops[] = new GradientStop($color, $position);
            }
        }

        return $this;
    }

    /**
     * Add a color stop at the end (position 1.0).
     *
     * @param ColorInterface|string $color Color object or CSS color string
     */
    public function to(ColorInterface|string $color): self
    {
        return $this->stop($color, 1.0);
    }

    /**
     * Add a color stop at the middle (position 0.5).
     *
     * @param ColorInterface|string $color Color object or CSS color string
     */
    public function via(ColorInterface|string $color): self
    {
        return $this->stop($color, 0.5);
    }
}
