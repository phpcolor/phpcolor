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
 * Represents a CSS var() expression.
 *
 * Allows for late resolution of colors defined in CSS custom properties,
 * with optional fallback expressions.
 */
final readonly class ColorVar implements CssResolvableInterface
{
    /**
     * Create a new CSS variable expression.
     */
    public function __construct(
        private string $name,
        private ?CssResolvableInterface $fallback = null,
    ) {
    }

    public function resolve(CssContext $ctx): ColorInterface|CssResolvableInterface
    {
        $raw = $ctx->getVar($this->name);

        if (null !== $raw) {
            $expr = CssColor::parse($raw);

            return $expr->resolve($ctx);
        }

        if ($this->fallback instanceof CssResolvableInterface) {
            return $this->fallback->resolve($ctx);
        }

        if ($ctx->isStrict()) {
            throw new InvalidColorException(\sprintf('Undefined CSS variable %s and no fallback provided.', $this->name));
        }

        return $this;
    }

    public function toCss(): string
    {
        if ($this->fallback instanceof CssResolvableInterface) {
            return \sprintf('var(%s, %s)', $this->name, $this->fallback->toCss());
        }

        return \sprintf('var(%s)', $this->name);
    }
}
