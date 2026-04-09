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
 * Resolution environment for CSS color expressions.
 *
 * Stores runtime values such as CSS variables, color scheme preference, and
 * the current foreground color, which are needed to resolve late-bound CSS colors.
 */
final readonly class CssContext
{
    /**
     * Create a new CSS resolution context.
     *
     * @param array<string, string> $variables CSS custom properties (--name => value)
     */
    public function __construct(
        private array $variables = [],
        private ?string $colorScheme = null,
        private bool $strict = false,
        private ColorInterface|string|null $currentColor = null,
    ) {
    }

    /**
     * Create a dark-mode context.
     *
     * @param array<string, string> $vars
     */
    public static function dark(array $vars = [], ColorInterface|string|null $currentColor = null): self
    {
        return new self($vars, 'dark', false, $currentColor);
    }

    /**
     * Create a light-mode context.
     *
     * @param array<string, string> $vars
     */
    public static function light(array $vars = [], ColorInterface|string|null $currentColor = null): self
    {
        return new self($vars, 'light', false, $currentColor);
    }

    /**
     * Get the active color scheme ('light', 'dark', or null).
     */
    public function colorScheme(): ?string
    {
        return $this->colorScheme;
    }

    /**
     * Get the current foreground color.
     */
    public function getCurrentColor(): ColorInterface|string|null
    {
        return $this->currentColor;
    }

    /**
     * Get the value of a CSS variable by name.
     */
    public function getVar(string $name): ?string
    {
        return $this->variables[$name] ?? null;
    }

    /**
     * Check if strict resolution is enabled.
     */
    public function isStrict(): bool
    {
        return $this->strict;
    }

    /**
     * Return a new context with a different current color.
     */
    public function withCurrentColor(ColorInterface|string $color): self
    {
        return new self(
            $this->variables,
            $this->colorScheme,
            $this->strict,
            $color
        );
    }

    /**
     * Return a new context with an additional CSS variable.
     */
    public function withVar(string $name, string $value): self
    {
        return new self(
            [...$this->variables, $name => $value],
            $this->colorScheme,
            $this->strict,
            $this->currentColor
        );
    }
}
