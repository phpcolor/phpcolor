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
 * XYZ D65 color value object representing colors in the CIE XYZ color space with D65 illuminant.
 *
 * XYZ is a device-independent color space used as an intermediate for conversions between other spaces.
 * D65 refers to standard daylight illuminant at 6500K, representing average midday light conditions.
 */
final readonly class XyzColor extends AbstractColor
{
    public float $alpha;

    /**
     * Create a new XYZ D65 color from X, Y, Z, and alpha components.
     */
    public function __construct(
        public float $x,
        public float $y,
        public float $z,
        float $alpha = 1.0,
    ) {
        $this->alpha = min(max($alpha, 0.0), 1.0);
    }

    /**
     * Create color from channel values.
     *
     * @param array<string, float> $channels
     */
    public static function fromChannels(array $channels, float $alpha = 1.0): static
    {
        return new self(
            $channels['x'] ?? 0.0,
            $channels['y'] ?? 0.0,
            $channels['z'] ?? 0.0,
            $alpha
        );
    }

    public static function fromSrgb(SrgbColor $srgb): static
    {
        $linearR = self::srgbToLinear($srgb->r);
        $linearG = self::srgbToLinear($srgb->g);
        $linearB = self::srgbToLinear($srgb->b);

        $xyz = self::mul3x3(Matrices::SRGB_TO_XYZ_D65, [$linearR, $linearG, $linearB]);

        return new self($xyz[0], $xyz[1], $xyz[2], $srgb->a);
    }

    public static function getSpaceName(): string
    {
        return 'xyz-d65';
    }

    public function getAlpha(): float
    {
        return $this->alpha;
    }

    public function getChannels(): array
    {
        return ['x' => $this->x, 'y' => $this->y, 'z' => $this->z];
    }

    /**
     * Get the 'X' component value.
     */
    public function getX(): float
    {
        return $this->x;
    }

    /**
     * Get the 'Y' component value.
     */
    public function getY(): float
    {
        return $this->y;
    }

    /**
     * Get the 'Z' component value.
     */
    public function getZ(): float
    {
        return $this->z;
    }

    public function toCss(?string $space = null): string
    {
        $target = $space ? self::normalizeSpaceName($space) : 'xyz';

        if (null === $space || 'xyz' === $target || 'xyz-d65' === $target) {
            $x = self::formatCssFloat($this->x);
            $y = self::formatCssFloat($this->y);
            $z = self::formatCssFloat($this->z);
            if (1.0 === $this->alpha) {
                return \sprintf('color(xyz-d65 %s %s %s)', $x, $y, $z);
            }
            $alpha = self::formatCssFloat($this->alpha);

            return \sprintf('color(xyz-d65 %s %s %s / %s)', $x, $y, $z, $alpha);
        }

        if ('srgb' === $target || 'rgb' === $target) {
            return $this->toSrgb()->toCss($space);
        }

        throw new InvalidColorException(\sprintf('CSS output for "%s" is not supported yet.', $space));
    }

    public function toSrgb(): SrgbColor
    {
        $linearRgb = self::mul3x3(Matrices::XYZ_D65_TO_SRGB, [$this->x, $this->y, $this->z]);

        $r = self::linearToSrgb($linearRgb[0]);
        $g = self::linearToSrgb($linearRgb[1]);
        $b = self::linearToSrgb($linearRgb[2]);

        return new SrgbColor($r, $g, $b, $this->alpha);
    }
}
