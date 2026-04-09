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

namespace PhpColor\Color\Parser;

/**
 * Internal utility methods for color string parsing.
 *
 * @internal
 */
final class ParserUtils
{
    /**
     * Extract content between two substrings.
     */
    public static function between(string $haystack, string $start, string $end): ?string
    {
        $p1 = strpos($haystack, $start);
        $p2 = strrpos($haystack, $end);
        if (false === $p1 || false === $p2 || $p2 <= $p1) {
            return null;
        }

        return substr($haystack, $p1 + 1, $p2 - $p1 - 1);
    }

    /**
     * Normalize a color space name.
     */
    public static function normalizeSpaceName(string $space): string
    {
        return strtolower(str_replace(['_', ' '], '-', trim($space)));
    }

    /**
     * Parse a numeric value or percentage into a float in range [0, 1].
     */
    public static function parseUnitOrPercent(string $token): float
    {
        return str_ends_with($token, '%') ? max(0.0, min(1.0, (float) substr($token, 0, -1) / 100.0)) : max(0.0, min(1.0, (float) $token));
    }

    /**
     * Split a string by slash only if it is outside of parentheses.
     *
     * @return array{string, string|null}
     */
    public static function splitBySlashOutsideParens(string $str): array
    {
        $depth = 0;
        $len = \strlen($str);

        for ($i = 0; $i < $len; ++$i) {
            $char = $str[$i];

            if ('(' === $char) {
                ++$depth;
            } elseif (')' === $char) {
                --$depth;
            } elseif ('/' === $char && 0 === $depth) {
                $before = substr($str, 0, $i);
                $after = substr($str, $i + 1);

                return [trim($before), trim($after)];
            }
        }

        return [$str, null];
    }

    /**
     * Split channel expressions by whitespace, respecting parentheses.
     *
     * @return array<int, string>
     */
    public static function splitChannelExpressions(string $channelPart): array
    {
        $expressions = [];
        $current = '';
        $depth = 0;
        $len = \strlen($channelPart);

        for ($i = 0; $i < $len; ++$i) {
            $char = $channelPart[$i];

            if ('(' === $char) {
                ++$depth;
                $current .= $char;
            } elseif (')' === $char) {
                --$depth;
                $current .= $char;
            } elseif (' ' === $char && 0 === $depth) {
                if ('' !== trim($current)) {
                    $expressions[] = trim($current);
                    $current = '';
                }
            } else {
                $current .= $char;
            }
        }

        if ('' !== trim($current)) {
            $expressions[] = trim($current);
        }

        return $expressions;
    }
}
