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
 * HWB color value object representing colors in the HWB (Hue, Whiteness, Blackness) color space.
 *
 * HWB is a cylindrical representation of the RGB color model, designed to be more
 * intuitive for humans than HSL or HSV.
 */
final readonly class HwbColor extends AbstractColor
{
    private const string HWB_PATTERN = '/^\s*hwb\(\s*(?<h>[\d.e+-]+(?:deg|rad|grad|turn)?)\s+(?<w>[\d.]+%)\s+(?<b>[\d.]+%)(?:\s*\/\s*(?<a>[\d.]+%?))?\s*\)\s*$/i';

    public float $alpha;
    public float $b;
    public float $h;
    public float $w;

    /**
     * Create a new HWB color from hue, whiteness, blackness, and alpha components.
     */
    public function __construct(float $h, float $w, float $b, float $alpha = 1.0)
    {
        $this->h = self::normAngle($h);
        $this->w = self::clamp01($w);
        $this->b = self::clamp01($b);
        $this->alpha = self::clamp01($alpha);
    }

    /**
     * Create color from channel values.
     *
     * @param array<string, float> $channels
     *
     * @throws InvalidColorException If the "h", "w", or "b" channel is missing
     */
    public static function fromChannels(array $channels, float $alpha = 1.0): static
    {
        if (!isset($channels['h']) || !isset($channels['w']) || !isset($channels['b'])) {
            throw new InvalidColorException('Missing HWB channels for construction.');
        }

        return new self($channels['h'], $channels['w'], $channels['b'], $alpha);
    }

    public static function fromSrgb(SrgbColor $srgb): static
    {
        $r = $srgb->r;
        $g = $srgb->g;
        $b = $srgb->b;

        $min = min($r, $g, $b);
        $max = max($r, $g, $b);

        $w = $min;
        $blackness = 1 - $max;

        $h = 0.0;
        if ($max === $min) {
            $h = 0.0;
        } else {
            $delta = $max - $min;
            switch ($max) {
                case $r:
                    $h = ($g - $b) / $delta + ($g < $b ? 6 : 0);

                    break;
                case $g:
                    $h = ($b - $r) / $delta + 2;

                    break;
                case $b:
                    $h = ($r - $g) / $delta + 4;

                    break;
            }
            $h /= 6;
        }

        return new self($h * 360, $w, $blackness, $srgb->a);
    }

    public static function getSpaceName(): string
    {
        return 'hwb';
    }

    /**
     * Parse an hwb() color string into an HWB color instance.
     */
    public static function parse(string $s): self
    {
        if (!preg_match(self::HWB_PATTERN, trim($s), $matches)) {
            throw new ParseException(\sprintf('Cannot parse color "%s".', $s));
        }

        $h = self::parseHue($matches['h']);
        $w = self::parsePercentage($matches['w']);
        $b = self::parsePercentage($matches['b']);
        $a = isset($matches['a']) ? self::parseAlpha($matches['a']) : 1.0;

        return new self($h, $w, $b, $a);
    }

    public function getAlpha(): float
    {
        return $this->alpha;
    }

    /**
     * Get the blackness component value (0 to 1).
     */
    public function getBlackness(): float
    {
        return $this->b;
    }

    public function getChannels(): array
    {
        return ['h' => $this->h, 'w' => $this->w, 'b' => $this->b];
    }

    #[\Override]
    public function getHue(): float
    {
        return $this->h;
    }

    /**
     * Get the whiteness component value (0 to 1).
     */
    public function getWhiteness(): float
    {
        return $this->w;
    }

    public function toCss(?string $space = null): string
    {
        if ($space) {
            self::normalizeSpaceName($space);
        }

        $h = self::formatCssFloat($this->h);
        $w = self::formatCssFloat($this->w * 100.0);
        $b = self::formatCssFloat($this->b * 100.0);
        if (1.0 === $this->alpha) {
            return \sprintf('hwb(%s %s%% %s%%)', $h, $w, $b);
        }
        $alpha = self::formatCssFloat($this->alpha);

        return \sprintf('hwb(%s %s%% %s%% / %s)', $h, $w, $b, $alpha);
    }

    public function toSrgb(): SrgbColor
    {
        $w = $this->w;
        $k = $this->b;
        $a = $this->alpha;

        $sum = $w + $k;
        if ($sum >= 1.0) {
            $g = $sum > 0.0 ? $w / $sum : 0.0;

            return new SrgbColor($g, $g, $g, $a);
        }

        $h = $this->h;
        if ($h > 1.0) {
            $h = fmod($h, 360.0) / 360.0;
        } elseif ($h < 0.0) {
            $h -= floor($h);
        }
        $h6 = $h * 6.0;
        $i = (int) $h6;
        $f = $h6 - $i;
        $p = 1.0 - $f;

        switch ($i) {
            case 0:
                $r0 = 1.0;
                $g0 = $f;
                $b0 = 0.0;

                break;
            case 1:
                $r0 = $p;
                $g0 = 1.0;
                $b0 = 0.0;

                break;
            case 2:
                $r0 = 0.0;
                $g0 = 1.0;
                $b0 = $f;

                break;
            case 3:
                $r0 = 0.0;
                $g0 = $p;
                $b0 = 1.0;

                break;
            case 4:
                $r0 = $f;
                $g0 = 0.0;
                $b0 = 1.0;

                break;
            default:
                $r0 = 1.0;
                $g0 = 0.0;
                $b0 = $p;

                break;
        }

        $scale = 1.0 - $w - $k;
        $r = $r0 * $scale + $w;
        $g = $g0 * $scale + $w;
        $b = $b0 * $scale + $w;

        return new SrgbColor($r, $g, $b, $a);
    }
}
