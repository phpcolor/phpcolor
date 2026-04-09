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
 * Represents a color in the Oklab color space.
 *
 * Oklab is a perceptually uniform color space that is excellent for color
 * manipulations like lightening, darkening, and mixing while preserving
 * perceived hue and saturation.
 */
final readonly class OklabColor extends AbstractColor
{
    /**
     * Optimized OKLab pattern: oklab(L a b / alpha?).
     */
    private const string OKLAB_PATTERN = '/^\s*oklab\(\s*([+-]?\d+(?:\.\d+)?%?)\s*(?:,\s*|\s+)\s*([+-]?\d+(?:\.\d+)?)\s*(?:,\s*|\s+)\s*([+-]?\d+(?:\.\d+)?)(?:\s*[\/ ,]\s*([^)]+))?\s*\)\s*$/i';
    public float $alpha;
    public float $l;

    /**
     * Create a new Oklab color from L, a, b, and alpha components.
     */
    public function __construct(
        float $l,
        public float $a,
        public float $b,
        float $alpha = 1.0,
    ) {
        $this->l = max(0.0, min(1.0, $l));
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

        return new self($okL, $okA, $okB, $srgb->a);
    }

    public static function getSpaceName(): string
    {
        return 'oklab';
    }

    /**
     * Parse an oklab() color string into an Oklab color instance.
     */
    public static function parse(string $color): static
    {
        if (!preg_match(self::OKLAB_PATTERN, $color, $m)) {
            throw new ParseException(\sprintf('Cannot parse color "%s".', $color));
        }

        $l = self::parseUnitOrPercent($m[1]);
        $a = (float) $m[2];
        $b = (float) $m[3];
        $alpha = isset($m[4]) ? self::parseAlpha(trim($m[4])) : 1.0;

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
        $target = $space ? self::normalizeSpaceName($space) : 'oklab';

        if (null === $space || 'oklab' === $target) {
            $l = self::formatCssFloat($this->l);
            $a = self::formatCssFloat($this->a);
            $b = self::formatCssFloat($this->b);
            if (1.0 === $this->alpha) {
                return \sprintf('oklab(%s %s %s)', $l, $a, $b);
            }
            $alpha = self::formatCssFloat($this->alpha);

            return \sprintf('oklab(%s %s %s / %s)', $l, $a, $b, $alpha);
        }

        if ('srgb' === $target || 'rgb' === $target) {
            return $this->toSrgb()->toCss($space);
        }

        throw new InvalidColorException(\sprintf('CSS output for "%s" is not supported yet.', $space));
    }

    public function toSrgb(): SrgbColor
    {
        $l_ = $this->l + 0.3963377774 * $this->a + 0.2158037573 * $this->b;
        $m_ = $this->l - 0.1055613458 * $this->a - 0.0638541728 * $this->b;
        $s_ = $this->l - 0.0894841775 * $this->a - 1.2914855480 * $this->b;

        $l = $l_ ** 3.0;
        $m = $m_ ** 3.0;
        $s = $s_ ** 3.0;

        $linearR = +4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s;
        $linearG = -1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s;
        $linearB = -0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s;

        $r = self::linearToSrgb($linearR);
        $g = self::linearToSrgb($linearG);
        $b = self::linearToSrgb($linearB);

        return new SrgbColor($r, $g, $b, $this->alpha);
    }
}
