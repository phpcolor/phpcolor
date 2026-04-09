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
 * Represents a color stop in a gradient.
 *
 * A gradient stop defines a color at a specific position along the gradient line.
 * Position is typically 0.0 (start) to 1.0 (end), but can be outside this range.
 */
final readonly class GradientStop
{
    /**
     * Create a new gradient stop instance.
     */
    public function __construct(
        public ColorInterface $color,
        public float $position,
    ) {
    }

    /**
     * Create a new gradient stop with position clamped to the range [0, 1].
     */
    public static function normalized(ColorInterface $color, float $position): self
    {
        return new self($color, max(0.0, min(1.0, $position)));
    }

    /**
     * Convert the gradient stop to a CSS string representation.
     */
    public function toCss(?string $colorSpace = null): string
    {
        $colorCss = $this->color->toCss($colorSpace);
        $positionPercent = round($this->position * 100, 2);

        return \sprintf('%s %s%%', $colorCss, rtrim(rtrim(\sprintf('%.2f', $positionPercent), '0'), '.'));
    }
}
