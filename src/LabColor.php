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
 * Represents a color in the CIELAB color space.
 *
 * CIELAB (or Lab) is a device-independent color space that covers the entire
 * range of human color perception. Components are lightness (L), and two
 * color-opponent dimensions (a and b). As specified by CSS Color 4 and ICC,
 * it uses a D50 white point, so the D65 pipeline is adapted with Bradford.
 */
final readonly class LabColor extends AbstractColor
{
    private const string LAB_PATTERN = '/^lab\s*\(\s*([+-]?\d+(?:\.\d+)?%?)\s*(?:,\s*|\s+)\s*([+-]?\d+(?:\.\d+)?%?)\s*(?:,\s*|\s+)\s*([+-]?\d+(?:\.\d+)?%?)(?:\s*[\/ ,]\s*([^)]+))?\s*\)$/i';
    public float $alpha;
    public float $l;

    /**
     * Create a new Lab color from L, a, b, and alpha components.
     */
    public function __construct(
        float $l,
        public float $a,
        public float $b,
        float $alpha = 1.0,
    ) {
        $this->l = max(0.0, min(100.0, $l));
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
            $channels['l'] ?? 0.0,
            $channels['a'] ?? 0.0,
            $channels['b'] ?? 0.0,
            $alpha
        );
    }

    public static function fromSrgb(SrgbColor $srgb): static
    {
        return self::fromXyz(XyzColor::fromSrgb($srgb));
    }

    /**
     * Create a Lab color from an XYZ color instance.
     */
    public static function fromXyz(XyzColor $xyz): static
    {
        $white = Colorimetry\ReferenceWhite::forSpace('lab');
        $adapted = Colorimetry\Adaptation\Bradford::adaptXYZ(
            [$xyz->x, $xyz->y, $xyz->z],
            Colorimetry\Illuminant::D65->whitePoint(),
            $white
        );

        $x = $adapted[0] / $white->X;
        $y = $adapted[1] / $white->Y;
        $z = $adapted[2] / $white->Z;

        $epsilon = 0.008856;
        $kappa = 903.3;

        $fx = ($x > $epsilon) ? $x ** (1.0 / 3.0) : ($kappa * $x + 16.0) / 116.0;
        $fy = ($y > $epsilon) ? $y ** (1.0 / 3.0) : ($kappa * $y + 16.0) / 116.0;
        $fz = ($z > $epsilon) ? $z ** (1.0 / 3.0) : ($kappa * $z + 16.0) / 116.0;

        $l = 116.0 * $fy - 16.0;
        $a = 500.0 * ($fx - $fy);
        $b = 200.0 * ($fy - $fz);

        return new self($l, $a, $b, $xyz->alpha);
    }

    public static function getSpaceName(): string
    {
        return 'lab';
    }

    /**
     * Parse a lab() color string into a Lab color instance.
     */
    public static function parse(string $color): static
    {
        $inner = self::between($color, '(', ')');
        if (null === $inner) {
            throw new ParseException(\sprintf('Cannot parse color "%s".', $color));
        }

        if (!preg_match(self::LAB_PATTERN, $color, $m)) {
            throw new ParseException(\sprintf('Cannot parse color "%s".', $color));
        }

        $lRaw = $m[1];
        $aRaw = $m[2];
        $bRaw = $m[3];

        $l = str_ends_with($lRaw, '%') ? (float) substr($lRaw, 0, -1) : (float) $lRaw;

        $a = (float) $aRaw;
        $b = (float) $bRaw;

        $alpha = 1.0;
        if (isset($m[4]) && '' !== trim($m[4])) {
            $alpha = self::parseAlpha(trim($m[4]));
        }

        return new self($l, $a, $b, $alpha);
    }

    /**
     * Get the 'a' component value.
     */
    public function getA(): float
    {
        return $this->a;
    }

    public function getAlpha(): float
    {
        return $this->alpha;
    }

    /**
     * Get the 'b' component value.
     */
    public function getB(): float
    {
        return $this->b;
    }

    public function getChannels(): array
    {
        return ['l' => $this->l, 'a' => $this->a, 'b' => $this->b];
    }

    /**
     * Get the lightness component value.
     */
    public function getLightness(): float
    {
        return $this->l;
    }

    public function toCss(?string $space = null): string
    {
        $target = $space ? self::normalizeSpaceName($space) : 'lab';

        if (null === $space || 'lab' === $target) {
            $l = self::formatCssFloat($this->l);
            $a = self::formatCssFloat($this->a);
            $b = self::formatCssFloat($this->b);
            if (1.0 === $this->alpha) {
                return \sprintf('lab(%s %s %s)', $l, $a, $b);
            }
            $alpha = self::formatCssFloat($this->alpha);

            return \sprintf('lab(%s %s %s / %s)', $l, $a, $b, $alpha);
        }

        if ('srgb' === $target || 'rgb' === $target) {
            return $this->toSrgb()->toCss($space);
        }

        throw new InvalidColorException(\sprintf('CSS output for "%s" is not supported yet.', $space));
    }

    public function toSrgb(): SrgbColor
    {
        return $this->toXyz()->toSrgb();
    }

    /**
     * Convert the color to its XYZ representation.
     */
    public function toXyz(): XyzColor
    {
        $white = Colorimetry\ReferenceWhite::forSpace('lab');

        $fy = ($this->l + 16.0) / 116.0;
        $fx = $this->a / 500.0 + $fy;
        $fz = $fy - $this->b / 200.0;

        $epsilon = 0.008856;
        $kappa = 903.3;

        $fx3 = $fx ** 3.0;
        $fz3 = $fz ** 3.0;

        $x = ($fx3 > $epsilon) ? $fx3 : (116.0 * $fx - 16.0) / $kappa;
        $y = ($this->l > $kappa * $epsilon) ? (($this->l + 16.0) / 116.0) ** 3.0 : $this->l / $kappa;
        $z = ($fz3 > $epsilon) ? $fz3 : (116.0 * $fz - 16.0) / $kappa;

        $adapted = Colorimetry\Adaptation\Bradford::adaptXYZ(
            [$x * $white->X, $y * $white->Y, $z * $white->Z],
            $white,
            Colorimetry\Illuminant::D65->whitePoint()
        );

        return new XyzColor($adapted[0], $adapted[1], $adapted[2], $this->alpha);
    }
}
