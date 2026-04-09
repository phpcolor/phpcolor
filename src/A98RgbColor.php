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
 * Adobe RGB (1998) color value object.
 *
 * Adobe RGB is a wide-gamut color space developed by Adobe Systems,
 * offering a broader range of colors than sRGB, especially in cyan-green hues.
 */
final readonly class A98RgbColor extends AbstractColor
{
    private const float GAMMA = 563.0 / 256.0;
    private const float INV_GAMMA = 256.0 / 563.0;

    public float $a;
    public float $b;
    public float $g;
    public float $r;

    /**
     * Create a new A98-RGB color from red, green, blue, and alpha components.
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
        $a98Linear = self::mul3x3(Matrices::XYZ_D65_TO_A98_RGB, $xyz);

        return new self(
            self::linearToA98($a98Linear[0]),
            self::linearToA98($a98Linear[1]),
            self::linearToA98($a98Linear[2]),
            $srgb->a
        );
    }

    public static function getSpaceName(): string
    {
        return 'a98-rgb';
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
        $target = $space ? self::normalizeSpaceName($space) : 'a98-rgb';

        if (null === $space || 'a98-rgb' === $target || 'a98' === $target || 'adobe-rgb' === $target) {
            $r = self::formatCssFloat($this->r);
            $g = self::formatCssFloat($this->g);
            $b = self::formatCssFloat($this->b);
            if (1.0 === $this->a) {
                return \sprintf('color(a98-rgb %s %s %s)', $r, $g, $b);
            }

            $alpha = self::formatCssFloat($this->a);

            return \sprintf('color(a98-rgb %s %s %s / %s)', $r, $g, $b, $alpha);
        }

        if ('srgb' === $target || 'rgb' === $target) {
            return $this->toSrgb()->toCss($space);
        }

        throw new InvalidColorException(\sprintf('CSS output for "%s" is not supported yet.', $space));
    }

    public function toSrgb(): SrgbColor
    {
        $linear = [
            $this->a98ToLinear($this->r),
            $this->a98ToLinear($this->g),
            $this->a98ToLinear($this->b),
        ];

        $xyz = self::mul3x3(Matrices::A98_RGB_TO_XYZ_D65, $linear);
        $srgbLinear = self::mul3x3(Matrices::XYZ_D65_TO_SRGB, $xyz);

        return new SrgbColor(
            self::linearToSrgb($srgbLinear[0]),
            self::linearToSrgb($srgbLinear[1]),
            self::linearToSrgb($srgbLinear[2]),
            $this->a
        );
    }

    /**
     * Convert an A98 color component to linear.
     */
    private function a98ToLinear(float $value): float
    {
        $sign = $value < 0.0 ? -1.0 : 1.0;

        return $sign * (abs($value) ** self::GAMMA);
    }

    /**
     * Convert a linear color component to A98.
     */
    private static function linearToA98(float $value): float
    {
        $sign = $value < 0.0 ? -1.0 : 1.0;

        return $sign * (abs($value) ** self::INV_GAMMA);
    }
}
