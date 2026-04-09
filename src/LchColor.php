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
 * Represents a color in the CIELCH color space.
 *
 * CIELCH (or Lch) is a cylindrical representation of the CIELAB color space,
 * using lightness (L), chroma (C), and hue (H).
 */
final readonly class LchColor extends AbstractColor
{
    /**
     * Optimized LCH pattern: lch(L C H / alpha?).
     */
    private const string LCH_PATTERN = '/^\s*lch\(\s*([+-]?\d+(?:\.\d+)?%?)\s*(?:,\s*|\s+)\s*([+-]?\d+(?:\.\d+)?)\s*(?:,\s*|\s+)\s*([^\s,\/\)]+)(?:\s*[\/ ,]\s*([^)]+))?\s*\)\s*$/i';

    public float $alpha;
    public float $c;
    public float $h;
    public float $l;

    /**
     * Create a new Lch color from L, C, H, and alpha components.
     */
    public function __construct(
        float $l,
        float $c,
        float $h,
        float $alpha = 1.0,
    ) {
        $this->l = max(0.0, min(100.0, $l));
        $this->c = max(0.0, $c);
        $this->h = self::normAngle($h);
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
            $channels['c'] ?? 0.0,
            $channels['h'] ?? 0.0,
            $alpha
        );
    }

    /**
     * Create an Lch color from a Lab color instance.
     */
    public static function fromLab(LabColor $lab): static
    {
        $c = sqrt($lab->a * $lab->a + $lab->b * $lab->b);
        $h = rad2deg(atan2($lab->b, $lab->a));

        if ($h < 0.0) {
            $h += 360.0;
        }

        return new self($lab->l, $c, $h, $lab->alpha);
    }

    public static function fromSrgb(SrgbColor $srgb): static
    {
        return self::fromLab(LabColor::fromSrgb($srgb));
    }

    public static function getSpaceName(): string
    {
        return 'lch';
    }

    /**
     * Parse an lch() color string into an Lch color instance.
     */
    public static function parse(string $color): static
    {
        if (!preg_match(self::LCH_PATTERN, $color, $m)) {
            throw new ParseException(\sprintf('Cannot parse color "%s".', $color));
        }

        $lRaw = $m[1];
        $l = str_ends_with($lRaw, '%') ? (float) substr($lRaw, 0, -1) : (float) $lRaw;
        $c = max(0.0, (float) $m[2]);
        $h = self::parseHue($m[3]);
        $alpha = isset($m[4]) ? self::parseAlpha(trim($m[4])) : 1.0;

        return new self($l, $c, $h, $alpha);
    }

    public function getAlpha(): float
    {
        return $this->alpha;
    }

    public function getChannels(): array
    {
        return ['l' => $this->l, 'c' => $this->c, 'h' => $this->h];
    }

    /**
     * Get the chroma component value.
     */
    public function getChroma(): float
    {
        return $this->c;
    }

    #[\Override]
    public function getHue(): float
    {
        if ($this->c <= 1e-9) {
            return 0.0;
        }

        return $this->h;
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
        $target = $space ? self::normalizeSpaceName($space) : 'lch';

        if (null === $space || 'lch' === $target) {
            $l = self::formatCssFloat($this->l);
            $c = self::formatCssFloat($this->c);
            $h = self::formatCssFloat($this->h);
            if (1.0 === $this->alpha) {
                return \sprintf('lch(%s %s %s)', $l, $c, $h);
            }
            $alpha = self::formatCssFloat($this->alpha);

            return \sprintf('lch(%s %s %s / %s)', $l, $c, $h, $alpha);
        }

        if ('lab' === $target) {
            return $this->toLab()->toCss($space);
        }

        if ('srgb' === $target || 'rgb' === $target) {
            return $this->toSrgb()->toCss($space);
        }

        throw new InvalidColorException(\sprintf('CSS output for "%s" is not supported yet.', $space));
    }

    /**
     * Convert the color to its Lab representation.
     */
    public function toLab(): LabColor
    {
        $hRadians = deg2rad($this->h);
        $a = $this->c * cos($hRadians);
        $b = $this->c * sin($hRadians);

        return new LabColor($this->l, $a, $b, $this->alpha);
    }

    public function toSrgb(): SrgbColor
    {
        return $this->toLab()->toSrgb();
    }
}
