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

namespace PhpColor\Color;

/**
 * Parse a CSS color string into a ColorInterface instance.
 */
function parse(string $input): ColorInterface
{
    return Color::parse($input);
}

/**
 * Create a new sRGB color from component values.
 */
function rgb(float $r, float $g, float $b, float $a = 1.0): SrgbColor
{
    return Color::rgb($r, $g, $b, $a);
}

/**
 * Create a new sRGB color from a hexadecimal string.
 */
function hex(string $hex): SrgbColor
{
    return Color::hex($hex);
}

/**
 * Create a new Oklab color.
 */
function oklab(float $l, float $a, float $b, float $alpha = 1.0): OklabColor
{
    return Color::oklab($l, $a, $b, $alpha);
}

/**
 * Create a new Oklch color.
 */
function oklch(float $l, float $c, float $h, float $alpha = 1.0): OklchColor
{
    return Color::oklch($l, $c, $h, $alpha);
}

/**
 * Create a new sRGB black color.
 */
function black(): SrgbColor
{
    return Color::black();
}

/**
 * Create a new sRGB white color.
 */
function white(): SrgbColor
{
    return Color::white();
}

/**
 * Create a new sRGB red color.
 */
function red(): SrgbColor
{
    return Color::red();
}

/**
 * Create a new sRGB green color.
 */
function green(): SrgbColor
{
    return Color::green();
}

/**
 * Create a new sRGB blue color.
 */
function blue(): SrgbColor
{
    return Color::blue();
}

/**
 * Mix two colors together in a specified color space.
 */
function mix(ColorInterface|string $a, ColorInterface|string $b, float $t, string|ColorInterface $in = 'oklab'): ColorInterface
{
    return Color::mix($a, $b, $t, $in);
}
