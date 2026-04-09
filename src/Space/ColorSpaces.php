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

namespace PhpColor\Color\Space;

use PhpColor\Color\Parser\ParserUtils;

/**
 * Registry and utilities for canonical color space management.
 *
 * Provides a central location for mapping color space aliases to canonical names
 * and defining expected channel structures for each space.
 */
final class ColorSpaces
{
    /**
     * Map of canonical color space names to their common aliases.
     *
     * @var array<string, list<string>>
     */
    private const array ALIASES = [
        'srgb' => ['rgb'],
        'srgb-linear' => ['linear-srgb'],
        'display-p3' => ['displayp3'],
        'xyz-d65' => ['xyz'],
        'rec2020' => ['rec-2020', 'bt2020', 'bt-2020'],
        'prophoto-rgb' => ['prophoto', 'romm-rgb'],
        'a98-rgb' => ['a98', 'adobe-rgb'],
    ];

    /**
     * Get the expected channel names for a given color space.
     *
     * @return array<int,string>
     */
    public static function expectedChannels(string $space): array
    {
        $s = self::normalize($space);

        return match ($s) {
            'hsl' => ['h', 's', 'l'],
            'oklab', 'lab' => ['l', 'a', 'b'],
            'oklch', 'lch' => ['l', 'c', 'h'],
            'cmyk' => ['c', 'm', 'y', 'k'],
            default => ['r', 'g', 'b'],
        };
    }

    /**
     * Normalize a color space name and resolve its aliases.
     */
    public static function normalize(string $space): string
    {
        $norm = ParserUtils::normalizeSpaceName($space);

        foreach (self::ALIASES as $canonical => $aliases) {
            if ($norm === $canonical || \in_array($norm, $aliases, true)) {
                return $canonical;
            }
        }

        return $norm;
    }
}
