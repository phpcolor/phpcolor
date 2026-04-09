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

use PhpColor\Color\Cache\ConversionCache;
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\Exception\ParseException;

/**
 * Represents a color in the Oklch color space.
 *
 * Oklch is a cylindrical representation of the Oklab color space, using
 * lightness (L), chroma (C), and hue (H). It is highly intuitive for
 * color design and manipulation.
 */
final readonly class OklchColor extends AbstractColor
{
    /**
     * Optimized OKLCH pattern: oklch(L C H / alpha?).
     */
    private const string OKLCH_PATTERN = '/^\s*oklch\(\s*([+-]?\d+(?:\.\d+)?%?)\s*(?:,\s*|\s+)\s*([+-]?\d+(?:\.\d+)?)\s*(?:,\s*|\s+)\s*([^\s,\/\)]+)(?:\s*[\/ ,]\s*([^)]+))?\s*\)\s*$/i';

    public float $alpha;
    public float $c;
    public float $h;
    public float $l;

    /**
     * Create a new Oklch color from L, C, H, and alpha components.
     */
    public function __construct(
        float $l,
        float $c,
        float $h,
        float $alpha = 1.0,
    ) {
        $this->l = max(0.0, min(1.0, $l));
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
     * Create an Oklch color from an Oklab color instance.
     */
    public static function fromOklab(OklabColor $oklab): static
    {
        $c = sqrt($oklab->a * $oklab->a + $oklab->b * $oklab->b);
        $h = rad2deg(atan2($oklab->b, $oklab->a));

        if ($h < 0.0) {
            $h += 360.0;
        }

        return new self($oklab->l, $c, $h, $oklab->alpha);
    }

    public static function fromSrgb(SrgbColor $srgb): static
    {
        $cached = ConversionCache::getOklchFromSrgb($srgb);
        if ($cached instanceof self) {
            return $cached;
        }

        $linearR = self::srgbToLinear($srgb->r);
        $linearG = self::srgbToLinear($srgb->g);
        $linearB = self::srgbToLinear($srgb->b);

        $l = 0.4122214708 * $linearR + 0.5363325363 * $linearG + 0.0514459929 * $linearB;
        $m = 0.2119034982 * $linearR + 0.6806995451 * $linearG + 0.1073969566 * $linearB;
        $s = 0.0883024619 * $linearR + 0.2817188376 * $linearG + 0.6299787005 * $linearB;

        $l_ = $l >= 0.0 ? $l ** (1.0 / 3.0) : -((-$l) ** (1.0 / 3.0));
        $m_ = $m >= 0.0 ? $m ** (1.0 / 3.0) : -((-$m) ** (1.0 / 3.0));
        $s_ = $s >= 0.0 ? $s ** (1.0 / 3.0) : -((-$s) ** (1.0 / 3.0));

        $okL = 0.2104542553 * $l_ + 0.7936177850 * $m_ - 0.0040720468 * $s_;
        $okA = 1.9779984951 * $l_ - 2.4285922050 * $m_ + 0.4505937099 * $s_;
        $okB = 0.0259040371 * $l_ + 0.7827717662 * $m_ - 0.8086757660 * $s_;

        $c = sqrt($okA * $okA + $okB * $okB);
        $h = rad2deg(atan2($okB, $okA));
        if ($h < 0.0) {
            $h += 360.0;
        }

        $result = new self($okL, $c, $h, $srgb->a);

        ConversionCache::cacheBidirectional($srgb, $result);

        return $result;
    }

    public static function getSpaceName(): string
    {
        return 'oklch';
    }

    /**
     * Parse an oklch() color string into an Oklch color instance.
     */
    public static function parse(string $color): static
    {
        if (!preg_match(self::OKLCH_PATTERN, $color, $m)) {
            throw new ParseException(\sprintf('Cannot parse color "%s".', $color));
        }

        $l = self::parseUnitOrPercent($m[1]);
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
        $target = $space ? self::normalizeSpaceName($space) : 'oklch';

        if (null === $space || 'oklch' === $target) {
            $l = self::formatCssFloat($this->l);
            $c = self::formatCssFloat($this->c);
            $h = self::formatCssFloat($this->h);
            if (1.0 === $this->alpha) {
                return \sprintf('oklch(%s %s %s)', $l, $c, $h);
            }
            $alpha = self::formatCssFloat($this->alpha);

            return \sprintf('oklch(%s %s %s / %s)', $l, $c, $h, $alpha);
        }

        if ('oklab' === $target) {
            return $this->toOklab()->toCss($space);
        }

        if ('srgb' === $target || 'rgb' === $target) {
            return $this->toSrgb()->toCss($space);
        }

        throw new InvalidColorException(\sprintf('CSS output for "%s" is not supported yet.', $space));
    }

    /**
     * Convert the color to its Oklab representation.
     */
    public function toOklab(): OklabColor
    {
        $hRadians = deg2rad($this->h);
        $a = $this->c * cos($hRadians);
        $b = $this->c * sin($hRadians);

        return new OklabColor($this->l, $a, $b, $this->alpha);
    }

    public function toSrgb(): SrgbColor
    {
        $cached = ConversionCache::getSrgbFromOklch($this);
        if ($cached instanceof SrgbColor) {
            return $cached;
        }

        $hRadians = deg2rad($this->h);
        $okA = $this->c * cos($hRadians);
        $okB = $this->c * sin($hRadians);

        $l_ = $this->l + 0.3963377774 * $okA + 0.2158037573 * $okB;
        $m_ = $this->l - 0.1055613458 * $okA - 0.0638541728 * $okB;
        $s_ = $this->l - 0.0894841775 * $okA - 1.2914855480 * $okB;

        $l = $l_ ** 3.0;
        $m = $m_ ** 3.0;
        $s = $s_ ** 3.0;

        $linearR = +4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s;
        $linearG = -1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s;
        $linearB = -0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s;

        $result = new SrgbColor(
            self::linearToSrgb($linearR),
            self::linearToSrgb($linearG),
            self::linearToSrgb($linearB),
            $this->alpha
        );

        ConversionCache::cacheBidirectional($result, $this);

        return $result;
    }
}
