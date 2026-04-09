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
use PhpColor\Color\Css\Exception\CssResolutionException;

/**
 * Represents a CSS Relative Color Syntax expression.
 *
 * This allows defining a new color by manipulating the channels of an origin color,
 * which can itself be a resolvable expression (e.g. using CSS variables).
 */
final readonly class RelativeColor implements CssResolvableInterface
{
    /**
     * Create a new relative color expression.
     *
     * @param string                $targetSpace Target color space
     * @param array<string, string> $channels    Channel expressions
     * @param string|float|null     $alpha       Optional alpha expression
     */
    public function __construct(
        private string $targetSpace,
        private CssResolvableInterface $origin,
        private array $channels,
        private string|float|null $alpha = null,
    ) {
    }

    public function resolve(CssContext $ctx): ColorInterface|CssResolvableInterface
    {
        $origin = $this->origin->resolve($ctx);
        if (!$origin instanceof ColorInterface) {
            return new self($this->targetSpace, $origin, $this->channels, $this->alpha);
        }

        $originCss = $origin->toCss();

        $channelList = [];
        foreach ($this->channels as $expr) {
            $channelList[] = (string) $expr;
        }
        $channelsCss = implode(' ', $channelList);

        $alphaCss = $this->formatAlpha();

        $css = \sprintf('%s(from %s %s%s)', $this->targetSpace, $originCss, $channelsCss, $alphaCss);

        try {
            return CssColorParser::parse($css);
        } catch (\Exception $e) {
            throw new CssResolutionException('Relative color resolution failed: '.$e->getMessage(), previous: $e);
        }
    }

    public function toCss(): string
    {
        $channelList = [];
        foreach ($this->channels as $expr) {
            $channelList[] = (string) $expr;
        }
        $channelsCss = implode(' ', $channelList);

        $alphaCss = $this->formatAlpha();

        return \sprintf('%s(from %s %s%s)', $this->targetSpace, $this->origin->toCss(), $channelsCss, $alphaCss);
    }

    /**
     * Format the alpha component for CSS output.
     */
    private function formatAlpha(): string
    {
        if (null === $this->alpha) {
            return '';
        }

        return ' / '.(\is_float($this->alpha) ? rtrim(rtrim(\sprintf('%.6f', $this->alpha), '0'), '.') : $this->alpha);
    }
}
