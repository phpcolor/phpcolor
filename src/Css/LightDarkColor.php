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
 * Represents a CSS light-dark() expression.
 *
 * This expression resolves to either its first or second argument depending
 * on the active color scheme in the resolution context.
 */
final readonly class LightDarkColor implements CssResolvableInterface
{
    /**
     * Create a new light-dark() expression.
     */
    public function __construct(
        private CssResolvableInterface $lightExpr,
        private CssResolvableInterface $darkExpr,
    ) {
    }

    public function resolve(CssContext $ctx): ColorInterface|CssResolvableInterface
    {
        $scheme = $ctx->colorScheme();

        if ('light' === $scheme) {
            return $this->lightExpr->resolve($ctx);
        }
        if ('dark' === $scheme) {
            return $this->darkExpr->resolve($ctx);
        }

        $light = $this->lightExpr->resolve($ctx);
        $dark = $this->darkExpr->resolve($ctx);

        $lightNode = $light instanceof ColorInterface ? new ResolvedColor($light) : $light;
        $darkNode = $dark  instanceof ColorInterface ? new ResolvedColor($dark) : $dark;

        return new self($lightNode, $darkNode);
    }

    public function toCss(): string
    {
        return \sprintf('light-dark(%s, %s)', $this->lightExpr->toCss(), $this->darkExpr->toCss());
    }
}
