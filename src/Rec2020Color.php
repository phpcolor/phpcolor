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
 * Rec.2020 (ITU-R BT.2020) color value object.
 *
 * Rec.2020 is a wide-gamut RGB color space defined for Ultra High Definition
 * Television (UHDTV), covering nearly all colors visible to the human eye.
 */
final readonly class Rec2020Color extends AbstractColor
{
    private const float ALPHA = 1.09929682680944;
    private const float BETA = 0.018053968510807;
    private const float GAMMA_THRESHOLD = 0.08145;

    public float $a;
    public float $b;
    public float $g;
    public float $r;

    /**
     * Create a new Rec.2020 color from red, green, blue, and alpha components.
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
            (float) ($channels['r'] ?? 0.0),
            (float) ($channels['g'] ?? 0.0),
            (float) ($channels['b'] ?? 0.0),
            $alpha
        );
    }

    public static function fromSrgb(SrgbColor $srgb): static
    {
        $linear = [
            self::srgbToLinear($srgb->r),
            self::srgbToLinear($srgb->g),
            self::srgbToLinear($srgb->b),
        ];

        $xyz = self::mul3x3(Matrices::SRGB_TO_XYZ_D65, $linear);
        $recLinear = self::mul3x3(Matrices::XYZ_D65_TO_REC2020, $xyz);

        return new self(
            self::linearToRec2020($recLinear[0]),
            self::linearToRec2020($recLinear[1]),
            self::linearToRec2020($recLinear[2]),
            $srgb->a
        );
    }

    public static function getSpaceName(): string
    {
        return 'rec2020';
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
        $target = $space ? self::normalizeSpaceName($space) : 'rec2020';

        if (null === $space || 'rec2020' === $target || 'rec-2020' === $target || 'bt2020' === $target || 'bt-2020' === $target) {
            $r = self::formatCssFloat($this->r);
            $g = self::formatCssFloat($this->g);
            $b = self::formatCssFloat($this->b);
            if (1.0 === $this->a) {
                return \sprintf('color(rec2020 %s %s %s)', $r, $g, $b);
            }

            $alpha = self::formatCssFloat($this->a);

            return \sprintf('color(rec2020 %s %s %s / %s)', $r, $g, $b, $alpha);
        }

        if ('srgb' === $target || 'rgb' === $target) {
            return $this->toSrgb()->toCss($space);
        }

        throw new InvalidColorException(\sprintf('CSS output for "%s" is not supported yet.', $space));
    }

    public function toSrgb(): SrgbColor
    {
        $linear = [
            self::rec2020ToLinear($this->r),
            self::rec2020ToLinear($this->g),
            self::rec2020ToLinear($this->b),
        ];

        $xyz = self::mul3x3(Matrices::REC2020_TO_XYZ_D65, $linear);
        $srgbLinear = self::mul3x3(Matrices::XYZ_D65_TO_SRGB, $xyz);

        return new SrgbColor(
            self::linearToSrgb($srgbLinear[0]),
            self::linearToSrgb($srgbLinear[1]),
            self::linearToSrgb($srgbLinear[2]),
            $this->a
        );
    }

    /**
     * Convert a linear color component to Rec.2020.
     */
    private static function linearToRec2020(float $value): float
    {
        if ($value < 0.0) {
            return -self::linearToRec2020(-$value);
        }

        if ($value < self::BETA) {
            return 4.5 * $value;
        }

        return self::ALPHA * ($value ** 0.45) - (self::ALPHA - 1.0);
    }

    /**
     * Convert a Rec.2020 color component to linear.
     */
    private static function rec2020ToLinear(float $value): float
    {
        if ($value < 0.0) {
            return -self::rec2020ToLinear(-$value);
        }

        if ($value < self::GAMMA_THRESHOLD) {
            return $value / 4.5;
        }

        return (($value + (self::ALPHA - 1.0)) / self::ALPHA) ** (1.0 / 0.45);
    }
}
