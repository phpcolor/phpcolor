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
 * Interface for CSS color expressions that can be resolved at runtime.
 */
interface CssResolvableInterface
{
    /**
     * Resolve the expression into a concrete color using the provided context.
     *
     * Returns a ColorInterface instance if fully resolved, or a new
     * CssResolvableInterface if only partially resolved.
     */
    public function resolve(CssContext $ctx): ColorInterface|self;

    /**
     * Convert the expression back to a CSS string representation.
     */
    public function toCss(): string;
}
