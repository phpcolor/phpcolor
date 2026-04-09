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

use PhpColor\Color\CmykColor;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\Css\CssColorParser;
use PhpColor\Color\Css\CssColors;
use PhpColor\Color\Exception\ColorExceptionInterface;
use PhpColor\Color\Exception\ParseException;
use PhpColor\Color\HwbColor;
use PhpColor\Color\LabColor;
use PhpColor\Color\LchColor;
use PhpColor\Color\OklabColor;
use PhpColor\Color\OklchColor;
use PhpColor\Color\SrgbColor;

/**
 * Parser for CSS color strings.
 *
 * @internal
 */
final class ColorParser
{
    /**
     * Parse a CSS color string into a ColorInterface instance.
     *
     * @throws ParseException
     */
    public static function parse(string $input): ColorInterface
    {
        $color = trim($input);
        if ('' === $color) {
            throw new ParseException('Empty color string.');
        }

        try {
            if ('#' === $color[0]) {
                return SrgbColor::parseHex($color);
            }

            $lower = strtolower($color);

            if (!str_contains($color, '(')) {
                return CssColors::parse($color);
            }

            if (str_contains($lower, 'from')) {
                return CssColorParser::parse($color);
            }

            return match (true) {
                str_starts_with($lower, 'rgb') => SrgbColor::parse($color),
                str_starts_with($lower, 'oklch') => OklchColor::parse($color),
                str_starts_with($lower, 'hsl') => SrgbColor::parseHsl($color),
                str_starts_with($lower, 'color(') => CssColorParser::parseColorFunction($color),
                str_starts_with($lower, 'oklab') => OklabColor::parse($color),
                str_starts_with($lower, 'lab') => LabColor::parse($color),
                str_starts_with($lower, 'lch') => LchColor::parse($color),
                str_starts_with($lower, 'hwb') => HwbColor::parse($color),
                str_starts_with($lower, 'device-cmyk') => CmykColor::parse($color),
                default => throw new ParseException(\sprintf('Cannot parse color "%s".', $color)),
            };
        } catch (ColorExceptionInterface $e) {
            throw new ParseException($e->getMessage());
        }
    }
}
