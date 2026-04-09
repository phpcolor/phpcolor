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
use PhpColor\Color\Exception\InvalidColorException;

/**
 * Represents the 'currentColor' CSS keyword.
 *
 * Resolution retrieves the computed foreground color from the provided
 * CSS resolution context.
 */
final class CurrentColor implements CssResolvableInterface
{
    public function resolve(CssContext $ctx): ColorInterface|CssResolvableInterface
    {
        $cur = $ctx->getCurrentColor();
        if ($cur instanceof ColorInterface) {
            return $cur;
        }
        if (\is_string($cur)) {
            $expr = CssColor::parse($cur);
            $resolved = $expr->resolve($ctx);
            if ($resolved instanceof ColorInterface) {
                return $resolved;
            }

            return $this;
        }

        if ($ctx->isStrict()) {
            throw new InvalidColorException('currentColor is not available in the provided context.');
        }

        return $this;
    }

    public function toCss(): string
    {
        return 'currentColor';
    }
}
