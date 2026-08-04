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

use PhpColor\Color\Colorimetry\Matrices;
use PhpColor\Color\Exception\InvalidColorException;

/**
 * Display P3 color value object representing colors in the wide-gamut Display P3 color space.
 *
 * Display P3 is used by Apple displays and modern screens, offering a wider gamut than sRGB with
 * more vivid colors. It uses the same gamma correction as sRGB but with different color primaries.
 */
final readonly class DisplayP3Color extends AbstractColor
{
    public float $a;
    public float $b;
    public float $g;
    public float $r;

    /**
     * Create a new Display P3 color from red, green, blue, and alpha components.
     *
     * Each channel is clamped to [0, 1]. CSS Color 4 permits out-of-range coordinates in
     * color(), so a color parsed with such coordinates does not round trip unchanged.
     */
    public function __construct(
        float $r,
        float $g,
        float $b,
        float $a = 1.0,
    ) {
        $this->r = min(max($r, 0.0), 1.0);
        $this->g = min(max($g, 0.0), 1.0);
        $this->b = min(max($b, 0.0), 1.0);
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

    public static function fromSrgb(SrgbColor $srgb): static
    {
        $linearR = self::srgbToLinear($srgb->r);
        $linearG = self::srgbToLinear($srgb->g);
        $linearB = self::srgbToLinear($srgb->b);

        $p3Linear = self::mul3x3(Matrices::SRGB_LINEAR_TO_DISPLAY_P3_LINEAR, [$linearR, $linearG, $linearB]);

        $r = self::linearToDisplayP3($p3Linear[0]);
        $g = self::linearToDisplayP3($p3Linear[1]);
        $b = self::linearToDisplayP3($p3Linear[2]);

        return new self($r, $g, $b, $srgb->a);
    }

    public static function getSpaceName(): string
    {
        return 'display-p3';
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
        $target = $space ? self::normalizeSpaceName($space) : 'display-p3';

        if (null === $space || 'display-p3' === $target || 'displayp3' === $target) {
            $r = self::formatCssFloat($this->r);
            $g = self::formatCssFloat($this->g);
            $b = self::formatCssFloat($this->b);
            if (1.0 === $this->a) {
                return \sprintf('color(display-p3 %s %s %s)', $r, $g, $b);
            }
            $alpha = self::formatCssFloat($this->a);

            return \sprintf('color(display-p3 %s %s %s / %s)', $r, $g, $b, $alpha);
        }

        if ('srgb' === $target || 'rgb' === $target) {
            return $this->toSrgb()->toCss($space);
        }

        throw new InvalidColorException(\sprintf('CSS output for "%s" is not supported yet.', $space));
    }

    public function toSrgb(): SrgbColor
    {
        $linearR = $this->displayP3ToLinear($this->r);
        $linearG = $this->displayP3ToLinear($this->g);
        $linearB = $this->displayP3ToLinear($this->b);

        $srgbLinear = self::mul3x3(Matrices::DISPLAY_P3_LINEAR_TO_SRGB_LINEAR, [$linearR, $linearG, $linearB]);

        $r = self::linearToSrgb($srgbLinear[0]);
        $g = self::linearToSrgb($srgbLinear[1]);
        $b = self::linearToSrgb($srgbLinear[2]);

        return new SrgbColor($r, $g, $b, $this->a);
    }

    /**
     * Convert a Display P3 color component to linear.
     */
    private function displayP3ToLinear(float $c): float
    {
        return self::srgbToLinear($c);
    }

    /**
     * Convert a linear color component to Display P3.
     */
    private static function linearToDisplayP3(float $c): float
    {
        return self::linearToSrgb($c);
    }
}
