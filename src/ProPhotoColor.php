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
 * ProPhoto RGB (ROMM RGB) color value object.
 *
 * ProPhoto is an extremely wide-gamut color space developed by Kodak,
 * covering more than 90% of all visible surface colors. It uses a D50 white point.
 */
final readonly class ProPhotoColor extends AbstractColor
{
    private const float LINEAR_THRESHOLD = 1.0 / 512.0;

    public float $a;
    public float $b;
    public float $g;
    public float $r;

    /**
     * Create a new ProPhoto RGB color from red, green, blue, and alpha components.
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

        $xyzD65 = self::mul3x3(Matrices::SRGB_TO_XYZ_D65, $linear);
        $xyzD50 = Colorimetry\Adaptation\Bradford::adaptXYZ(
            $xyzD65,
            Colorimetry\Illuminant::D65->whitePoint(),
            Colorimetry\Illuminant::D50->whitePoint()
        );
        $proLinear = self::mul3x3(Matrices::XYZ_D50_TO_PROPHOTO_RGB, $xyzD50);

        return new self(
            self::linearToProPhoto($proLinear[0]),
            self::linearToProPhoto($proLinear[1]),
            self::linearToProPhoto($proLinear[2]),
            $srgb->a
        );
    }

    public static function getSpaceName(): string
    {
        return 'prophoto-rgb';
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
        $target = $space ? self::normalizeSpaceName($space) : 'prophoto-rgb';

        if (null === $space || 'prophoto-rgb' === $target || 'prophoto' === $target || 'romm-rgb' === $target) {
            $r = self::formatCssFloat($this->r);
            $g = self::formatCssFloat($this->g);
            $b = self::formatCssFloat($this->b);
            if (1.0 === $this->a) {
                return \sprintf('color(prophoto-rgb %s %s %s)', $r, $g, $b);
            }

            $alpha = self::formatCssFloat($this->a);

            return \sprintf('color(prophoto-rgb %s %s %s / %s)', $r, $g, $b, $alpha);
        }

        if ('srgb' === $target || 'rgb' === $target) {
            return $this->toSrgb()->toCss($space);
        }

        throw new InvalidColorException(\sprintf('CSS output for "%s" is not supported yet.', $space));
    }

    public function toSrgb(): SrgbColor
    {
        $linear = [
            $this->proPhotoToLinear($this->r),
            $this->proPhotoToLinear($this->g),
            $this->proPhotoToLinear($this->b),
        ];

        $xyzD50 = self::mul3x3(Matrices::PROPHOTO_RGB_TO_XYZ_D50, $linear);
        $xyzD65 = Colorimetry\Adaptation\Bradford::adaptXYZ(
            $xyzD50,
            Colorimetry\Illuminant::D50->whitePoint(),
            Colorimetry\Illuminant::D65->whitePoint()
        );
        $srgbLinear = self::mul3x3(Matrices::XYZ_D65_TO_SRGB, $xyzD65);

        return new SrgbColor(
            self::linearToSrgb($srgbLinear[0]),
            self::linearToSrgb($srgbLinear[1]),
            self::linearToSrgb($srgbLinear[2]),
            $this->a
        );
    }

    /**
     * Convert a linear color component to ProPhoto RGB.
     */
    private static function linearToProPhoto(float $value): float
    {
        $sign = $value < 0.0 ? -1.0 : 1.0;
        $abs = abs($value);

        if ($abs <= self::LINEAR_THRESHOLD) {
            return $sign * ($abs * 16.0);
        }

        return $sign * ($abs ** (1.0 / 1.8));
    }

    /**
     * Convert a ProPhoto RGB color component to linear.
     */
    private function proPhotoToLinear(float $value): float
    {
        $sign = $value < 0.0 ? -1.0 : 1.0;
        $abs = abs($value);

        if ($abs <= 16.0 * self::LINEAR_THRESHOLD) {
            return $sign * ($abs / 16.0);
        }

        return $sign * ($abs ** 1.8);
    }
}
