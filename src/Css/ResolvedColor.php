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

use PhpColor\Color\ColorInterface;

/**
 * Wrapper for a concrete color that satisfies the resolvable interface.
 */
final readonly class ResolvedColor implements CssResolvableInterface
{
    /**
     * Create a new resolved color wrapper.
     */
    public function __construct(private ColorInterface $color)
    {
    }

    /**
     * Get the wrapped color instance.
     */
    public function color(): ColorInterface
    {
        return $this->color;
    }

    public function resolve(CssContext $ctx): ColorInterface|CssResolvableInterface
    {
        return $this->color;
    }

    public function toCss(): string
    {
        return $this->color->toCss();
    }
}
