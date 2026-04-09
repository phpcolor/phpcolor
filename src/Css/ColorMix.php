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

namespace PhpColor\Color\Css;

use PhpColor\Color\Color;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\Exception\InvalidColorException;

/**
 * Represents a CSS color-mix() expression.
 *
 * This class handles the late-binding resolution of two colors mixed together
 * in a specific color space with optional weights.
 */
final readonly class ColorMix implements CssResolvableInterface
{
    /**
     * Create a new color-mix() expression.
     *
     * @param string                 $space Interpolation color space
     * @param CssResolvableInterface $left  First color operand
     * @param CssResolvableInterface $right Second color operand
     * @param float|null             $w1    Optional weight for the first color (0-1 or 0-100)
     * @param float|null             $w2    Optional weight for the second color (0-1 or 0-100)
     */
    public function __construct(
        private string $space,
        private CssResolvableInterface $left,
        private CssResolvableInterface $right,
        private ?float $w1 = null,
        private ?float $w2 = null,
    ) {
    }

    public function resolve(CssContext $ctx): ColorInterface|CssResolvableInterface
    {
        $l = $this->left->resolve($ctx);
        $r = $this->right->resolve($ctx);

        if (!($l instanceof ColorInterface) || !($r instanceof ColorInterface)) {
            $leftNode = $l instanceof ColorInterface ? new ResolvedColor($l) : $l;
            $rightNode = $r instanceof ColorInterface ? new ResolvedColor($r) : $r;

            return new self($this->space, $leftNode, $rightNode, $this->w1, $this->w2);
        }

        $w1 = $this->w1;
        $w2 = $this->w2;

        if (null === $w1 && null === $w2) {
            $w1 = 0.5;
            $w2 = 0.5;
        } elseif (null === $w1) {
            $w1 = $this->complementWeight($w2);
        } elseif (null === $w2) {
            $w2 = $this->complementWeight($w1);
        }

        $sum = $w1 + $w2;
        if (0.0 >= $sum) {
            throw new InvalidColorException('color-mix() weights must be positive.');
        }
        $w1 /= $sum;
        $w2 /= $sum;

        $t = $w2;

        return Color::mix($l, $r, $t, $this->space);
    }

    public function toCss(): string
    {
        $left = $this->left->toCss();
        $right = $this->right->toCss();
        $w1 = null !== $this->w1 ? ' '.$this->pct($this->w1) : '';
        $w2 = null !== $this->w2 ? ' '.$this->pct($this->w2) : '';

        return \sprintf('color-mix(in %s, %s%s, %s%s)', $this->space, $left, $w1, $right, $w2);
    }

    /**
     * Calculate the complementary weight for a given weight.
     */
    private function complementWeight(float $weight): float
    {
        if ($weight <= 1.0) {
            return 1.0 - $weight;
        }

        return 100.0 - $weight;
    }

    /**
     * Format a weight value as a CSS percentage string.
     */
    private function pct(float $w): string
    {
        $p = 1.0 >= $w ? $w * 100.0 : $w;

        return rtrim(rtrim(\sprintf('%.6f', $p), '0'), '.').'%';
    }
}
