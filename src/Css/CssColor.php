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
 * Entrypoint for CSS color parsing and late resolution support.
 *
 * Provides factory methods for creating resolvable CSS color expressions
 * and utilities for resolving them into concrete colors.
 */
final class CssColor
{
    private const string VAR_PATTERN = '/^var\(\s*(--[A-Za-z0-9\-_]+)\s*(?:,\s*(.+))?\)$/';

    /**
     * Create a color-mix() expression.
     */
    public static function colorMix(
        string $space,
        CssResolvableInterface|ColorInterface|string $left,
        CssResolvableInterface|ColorInterface|string $right,
        ?float $w1 = null,
        ?float $w2 = null,
    ): CssResolvableInterface {
        return self::mix($space, $left, $right, $w1, $w2);
    }

    /**
     * Create a currentColor expression.
     */
    public static function currentColor(): CssResolvableInterface
    {
        return new CurrentColor();
    }

    /**
     * Create a relative color expression.
     *
     * @param array<string, string> $channels
     */
    public static function from(
        string $targetSpace,
        CssResolvableInterface|ColorInterface|string $origin,
        array $channels,
        string|float|null $alpha = null,
    ): CssResolvableInterface {
        return self::relative($targetSpace, $origin, $channels, $alpha);
    }

    /**
     * Create a light-dark() expression.
     */
    public static function lightDark(
        CssResolvableInterface|ColorInterface|string $light,
        CssResolvableInterface|ColorInterface|string $dark,
    ): LightDarkColor {
        return new LightDarkColor(self::ensureResolvable($light), self::ensureResolvable($dark));
    }

    /**
     * Create a color-mix() expression.
     */
    public static function mix(
        string $space,
        CssResolvableInterface|ColorInterface|string $left,
        CssResolvableInterface|ColorInterface|string $right,
        ?float $w1 = null,
        ?float $w2 = null,
    ): CssResolvableInterface {
        return new ColorMix($space, self::ensureResolvable($left), self::ensureResolvable($right), $w1, $w2);
    }

    /**
     * Parse a CSS color string into a resolvable expression.
     *
     * @throws InvalidColorException
     */
    public static function parse(string $css): CssResolvableInterface
    {
        $s = trim($css);

        if (0 === strcasecmp($s, 'currentcolor') || 0 === strcasecmp($s, 'currentColor')) {
            return new CurrentColor();
        }

        if (preg_match(self::VAR_PATTERN, $s, $m)) {
            $name = $m[1];
            $fallback = isset($m[2]) ? self::parse($m[2]) : null;

            return new ColorVar($name, $fallback);
        }

        if (0 === stripos($s, 'light-dark(') && str_ends_with($s, ')')) {
            [$a, $b] = self::splitTwoArgs(substr($s, \strlen('light-dark('), -1));

            return new LightDarkColor(self::parse($a), self::parse($b));
        }

        try {
            $color = Color::parse($s);

            return new ResolvedColor($color);
        } catch (\Exception $e) {
            throw new InvalidColorException(\sprintf('Unable to parse CSS color: %s', $css), previous: $e);
        }
    }

    /**
     * Create a relative color expression.
     *
     * @param array<string, string> $channels
     */
    public static function relative(
        string $targetSpace,
        CssResolvableInterface|ColorInterface|string $origin,
        array $channels,
        string|float|null $alpha = null,
    ): CssResolvableInterface {
        return new RelativeColor($targetSpace, self::ensureResolvable($origin), $channels, $alpha);
    }

    /**
     * Resolve a CSS color expression into a concrete color.
     */
    public static function resolve(CssResolvableInterface|ColorInterface $expr, CssContext $ctx): ColorInterface|CssResolvableInterface
    {
        if ($expr instanceof ColorInterface) {
            return $expr;
        }

        return $expr->resolve($ctx);
    }

    /**
     * Create a CSS variable expression.
     */
    public static function var(string $name, CssResolvableInterface|ColorInterface|string|null $fallback = null): CssResolvableInterface
    {
        $fb = null === $fallback ? null : self::ensureResolvable($fallback);

        return new ColorVar($name, $fb);
    }

    /**
     * Ensure a value is a CssResolvableInterface instance.
     */
    private static function ensureResolvable(CssResolvableInterface|ColorInterface|string $value): CssResolvableInterface
    {
        if (\is_string($value)) {
            return self::parse($value);
        }
        if ($value instanceof ColorInterface) {
            return new ResolvedColor($value);
        }

        return $value;
    }

    /**
     * Split two arguments for a CSS function, handling nested parentheses.
     *
     * @return array{0:string,1:string}
     */
    private static function splitTwoArgs(string $inner): array
    {
        $s = trim($inner);
        $len = \strlen($s);
        if (0 === $len) {
            throw new \InvalidArgumentException('light-dark() expects exactly two arguments.');
        }

        if (false === strpbrk($s, '()')) {
            $parts = array_map(trim(...), explode(',', $s));
            if (2 !== \count($parts)) {
                throw new \InvalidArgumentException('light-dark() expects exactly two arguments.');
            }

            return [$parts[0], $parts[1]];
        }

        $level = 0;
        $start = 0;
        $parts = [];
        $i = 0;
        while ($i < $len) {
            $i += strcspn($s, '(),', $i);
            if ($i >= $len) {
                break;
            }
            $ch = $s[$i];
            if ('(' === $ch) {
                ++$level;
                ++$i;

                continue;
            }
            if (')' === $ch) {
                if (0 === $level) {
                    throw new \InvalidArgumentException('Unbalanced parentheses');
                }
                --$level;
                ++$i;

                continue;
            }
            if (0 === $level) {
                $parts[] = trim(substr($s, $start, $i - $start));
                $start = ++$i;

                continue;
            }
            ++$i;
        }
        $parts[] = trim(substr($s, $start));
        if (0 !== $level) {
            throw new \InvalidArgumentException('Unbalanced parentheses');
        }
        if (2 !== \count($parts)) {
            throw new \InvalidArgumentException('light-dark() expects exactly two arguments.');
        }

        return [$parts[0], $parts[1]];
    }
}
