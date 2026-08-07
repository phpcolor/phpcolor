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

use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\Exception\ParseException;

/**
 * Represents a color in the sRGB color space.
 *
 * This is the standard color space for web and digital displays. Components are
 * stored as red, green, and blue values in the range [0, 1].
 */
final readonly class SrgbColor extends AbstractColor
{
    /**
     * Optimized HSL pattern matching both hsl() and hsla() formats.
     */
    private const string HSL_PATTERN = '/^hsla?\s*\(\s*([^\s,\/)]+)[\s,]+([^\s,\/)]+)[\s,]+([^\s,\/)]+)(?:\s*[\/ ,]\s*([^)]+))?\s*\)$/i';

    /**
     * Optimized RGB pattern matching both rgb() and rgba() formats.
     */
    private const string RGB_PATTERN = '/^rgba?\s*\(\s*([^\s,\/)]+)[\s,]+([^\s,\/)]+)[\s,]+([^\s,\/)]+)(?:\s*[\/ ,]\s*([^)]+))?\s*\)$/i';

    public float $a;

    /**
     * Create a new sRGB color from red, green, blue, and alpha components.
     */
    public function __construct(
        public float $r,
        public float $g,
        public float $b,
        float $a = 1.0,
    ) {
        $this->a = min(max($a, 0.0), 1.0);
    }

    /**
     * Create color from channel values.
     *
     * @param array<string, float> $channels
     */
    public static function fromChannels(array $channels, float $alpha = 1.0): static
    {
        return new self(
            $channels['r'] ?? 0.0,
            $channels['g'] ?? 0.0,
            $channels['b'] ?? 0.0,
            $alpha
        );
    }

    /**
     * Create an sRGB color from HSL components.
     *
     * @param array{h: float, s: float, l: float} $hsl
     */
    public static function fromHsl(array $hsl, float $alpha = 1.0): self
    {
        $h = self::normAngle($hsl['h']) / 360.0;
        $s = self::clamp01($hsl['s']);
        $l = self::clamp01($hsl['l']);

        if (0.0 === $s) {
            return new self($l, $l, $l, self::clamp01($alpha));
        }

        $q = $l < 0.5 ? $l * (1.0 + $s) : ($l + $s - $l * $s);
        $p = 2.0 * $l - $q;

        $r = self::hue2rgb($p, $q, $h + 1.0 / 3.0);
        $g = self::hue2rgb($p, $q, $h);
        $b = self::hue2rgb($p, $q, $h - 1.0 / 3.0);

        return new self($r, $g, $b, self::clamp01($alpha));
    }

    public static function fromSrgb(self $srgb): static
    {
        return $srgb;
    }

    public static function getSpaceName(): string
    {
        return 'srgb';
    }

    /**
     * Parse an RGB or hex color string into an sRGB color instance.
     *
     * Supports: rgb(r g b), rgba(r,g,b,a), rgb(r g b / a), #RGB, #RGBA, #RRGGBB, #RRGGBBAA.
     */
    public static function parse(string $color): static
    {
        $color = trim($color);

        if ('#' === $color[0]) {
            return self::parseHex($color);
        }

        if (!preg_match(self::RGB_PATTERN, $color, $matches)) {
            throw new ParseException(\sprintf('Cannot parse color "%s".', $color));
        }

        $r = self::parseRgbComponent($matches[1]);
        $g = self::parseRgbComponent($matches[2]);
        $b = self::parseRgbComponent($matches[3]);
        $a = isset($matches[4]) ? self::parseAlpha(trim($matches[4])) : 1.0;

        return new self($r, $g, $b, $a);
    }

    /**
     * Parse a hexadecimal color string into an sRGB color instance.
     */
    public static function parseHex(string $hex): static
    {
        if (!ctype_xdigit(ltrim($hex, '#'))) {
            throw new ParseException(\sprintf('Cannot parse color "%s".', $hex));
        }

        $len = \strlen($hex);

        switch ($len) {
            case 4:
                $r = hexdec($hex[1].$hex[1]);
                $g = hexdec($hex[2].$hex[2]);
                $b = hexdec($hex[3].$hex[3]);

                return new self($r / 255.0, $g / 255.0, $b / 255.0, 1.0);

            case 5:
                $r = hexdec($hex[1].$hex[1]);
                $g = hexdec($hex[2].$hex[2]);
                $b = hexdec($hex[3].$hex[3]);
                $a = hexdec($hex[4].$hex[4]);

                return new self($r / 255.0, $g / 255.0, $b / 255.0, $a / 255.0);

            case 7:
                $r = hexdec($hex[1].$hex[2]);
                $g = hexdec($hex[3].$hex[4]);
                $b = hexdec($hex[5].$hex[6]);

                return new self($r / 255.0, $g / 255.0, $b / 255.0, 1.0);

            case 9:
                $r = hexdec($hex[1].$hex[2]);
                $g = hexdec($hex[3].$hex[4]);
                $b = hexdec($hex[5].$hex[6]);
                $a = hexdec($hex[7].$hex[8]);

                return new self($r / 255.0, $g / 255.0, $b / 255.0, $a / 255.0);

            default:
                throw new ParseException(\sprintf('Cannot parse color "%s".', $hex));
        }
    }

    /**
     * Parse an HSL or HSLA color string into an sRGB color instance.
     */
    public static function parseHsl(string $color): static
    {
        if (!preg_match(self::HSL_PATTERN, $color, $m)) {
            throw new ParseException(\sprintf('Cannot parse color "%s".', $color));
        }

        $h = self::parseHue($m[1]);
        $s = self::parsePercentage($m[2]);
        $l = self::parsePercentage($m[3]);
        $a = isset($m[4]) ? self::parseAlpha(trim($m[4])) : 1.0;

        return self::fromHsl(['h' => $h, 's' => $s, 'l' => $l], $a);
    }

    public function getAlpha(): float
    {
        return $this->a;
    }

    /**
     * Get the blue component value (0 to 1).
     */
    public function getBlue(): float
    {
        return $this->b;
    }

    public function getChannels(): array
    {
        return ['r' => $this->r, 'g' => $this->g, 'b' => $this->b];
    }

    /**
     * Get the green component value (0 to 1).
     */
    public function getGreen(): float
    {
        return $this->g;
    }

    /**
     * Get the red component value (0 to 1).
     */
    public function getRed(): float
    {
        return $this->r;
    }

    public function toCss(?string $space = null): string
    {
        $target = $space ? self::normalizeSpaceName($space) : 'srgb';

        if (null === $space || 'srgb' === $target || 'rgb' === $target) {
            $r = (int) round($this->r * 255.0);
            $g = (int) round($this->g * 255.0);
            $b = (int) round($this->b * 255.0);
            if (1.0 === $this->a) {
                return \sprintf('rgb(%d %d %d)', $r, $g, $b);
            }
            $alpha = self::formatCssFloat($this->a);

            return \sprintf('rgb(%d %d %d / %s)', $r, $g, $b, $alpha);
        }

        if ('hsl' === $target) {
            $hsl = $this->getHslChannels();
            $h = self::formatCssFloat($hsl['h']);
            $s = self::formatCssFloat($hsl['s'] * 100.0);
            $l = self::formatCssFloat($hsl['l'] * 100.0);
            if (1.0 === $this->a) {
                return \sprintf('hsl(%s %s%% %s%%)', $h, $s, $l);
            }
            $alpha = self::formatCssFloat($this->a);

            return \sprintf('hsl(%s %s%% %s%% / %s)', $h, $s, $l, $alpha);
        }

        if ('color' === $target || 'color-srgb' === $target) {
            $r = rtrim(rtrim(\sprintf('%.6f', $this->r), '0'), '.');
            $g = rtrim(rtrim(\sprintf('%.6f', $this->g), '0'), '.');
            $b = rtrim(rtrim(\sprintf('%.6f', $this->b), '0'), '.');
            if (1.0 === $this->a) {
                return \sprintf('color(srgb %s %s %s)', $r, $g, $b);
            }
            $alpha = rtrim(rtrim(\sprintf('%.6f', $this->a), '0'), '.');

            return \sprintf('color(srgb %s %s %s / %s)', $r, $g, $b, $alpha);
        }

        throw new InvalidColorException(\sprintf('CSS output for "%s" is not supported yet.', $space));
    }

    #[\Override]
    public function toHex(bool $withAlpha = false): string
    {
        $r = (int) round(self::clamp01($this->r) * 255.0);
        $g = (int) round(self::clamp01($this->g) * 255.0);
        $b = (int) round(self::clamp01($this->b) * 255.0);

        if ($withAlpha) {
            $a = (int) round(self::clamp01($this->a) * 255.0);

            return \sprintf('#%02x%02x%02x%02x', $r, $g, $b, $a);
        }

        return \sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Read the color as HSL channels.
     *
     * HSL is a cylindrical notation of sRGB, not a separate color space, so this
     * returns channel values rather than a color object.
     *
     * @return array{h: float, s: float, l: float}
     */
    public function getHslChannels(): array
    {
        $r = $this->r;
        $g = $this->g;
        $b = $this->b;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2.0;

        if ($max === $min) {
            return ['h' => 0.0, 's' => 0.0, 'l' => $l];
        }

        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2.0 - $max - $min) : $d / ($max + $min);

        if ($max === $r) {
            $h = 60.0 * fmod(($g - $b) / $d, 6.0);
        } elseif ($max === $g) {
            $h = 60.0 * ((($b - $r) / $d) + 2.0);
        } else {
            $h = 60.0 * ((($r - $g) / $d) + 4.0);
        }

        if (0.0 > $h) {
            $h += 360.0;
        }

        return ['h' => $h, 's' => $s, 'l' => $l];
    }

    /**
     * Read the color as HSL channels.
     *
     * @return array{h: float, s: float, l: float}
     *
     * @deprecated since 1.1, use getHslChannels() instead
     */
    public function toHsl(): array
    {
        return $this->getHslChannels();
    }

    public function toSrgb(): self
    {
        return $this;
    }

    /**
     * Helper to convert a hue value to an RGB component.
     */
    private static function hue2rgb(float $p, float $q, float $t): float
    {
        while ($t < 0.0) {
            $t += 1.0;
        }
        while ($t > 1.0) {
            $t -= 1.0;
        }
        if ($t < 1.0 / 6.0) {
            return $p + ($q - $p) * 6.0 * $t;
        }
        if ($t < 1.0 / 2.0) {
            return $q;
        }
        if ($t < 2.0 / 3.0) {
            return $p + ($q - $p) * (2.0 / 3.0 - $t) * 6.0;
        }

        return $p;
    }
}
