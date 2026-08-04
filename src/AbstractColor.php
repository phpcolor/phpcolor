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

use PhpColor\Color\Exception\InvalidArgumentException;
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\Exception\ParseException;
use PhpColor\Color\Space\ColorSpaces;

/**
 * Base class for all color space implementations.
 *
 * Provides shared logic for color manipulations, space conversions, and
 * utility methods for parsing and formatting color components.
 */
abstract readonly class AbstractColor implements \Stringable, ColorInterface
{
    public static function from(ColorInterface|string $input): static
    {
        $color = Color::from($input);
        if ($color instanceof static) {
            return $color;
        }

        return static::fromSrgb($color->toSrgb());
    }

    public static function tryFrom(ColorInterface|string $input): ?static
    {
        $color = Color::tryFrom($input);
        if (!$color instanceof ColorInterface) {
            return null;
        }

        if ($color instanceof static) {
            return $color;
        }

        return static::fromSrgb($color->toSrgb());
    }

    public function analogous(int $count = 2): array
    {
        if ($count < 1) {
            return [];
        }

        $colors = [$this];
        $step = 30.0;

        for ($i = 1; $i <= $count; ++$i) {
            $colors[] = 1 === $i % 2 ? $this->rotateHue($step * (($i + 1) / 2)) : $this->rotateHue(-$step * ($i / 2));
        }

        return $colors;
    }

    public function blend(ColorInterface|string $backdrop, string $mode = 'normal'): static
    {
        $backdropColor = \is_string($backdrop) ? Color::parse($backdrop) : $backdrop;

        $src = $this->toSrgb();
        $dst = $backdropColor->toSrgb();

        $blendR = $this->applyBlendMode($src->r, $dst->r, $mode);
        $blendG = $this->applyBlendMode($src->g, $dst->g, $mode);
        $blendB = $this->applyBlendMode($src->b, $dst->b, $mode);

        $a = $src->a + $dst->a * (1.0 - $src->a);

        if ($a > 0.0) {
            $r = ($blendR * $src->a + $dst->r * $dst->a * (1.0 - $src->a)) / $a;
            $g = ($blendG * $src->a + $dst->g * $dst->a * (1.0 - $src->a)) / $a;
            $b = ($blendB * $src->a + $dst->b * $dst->a * (1.0 - $src->a)) / $a;
        } else {
            $r = $blendR;
            $g = $blendG;
            $b = $blendB;
        }

        $result = new SrgbColor(
            self::clamp01($r),
            self::clamp01($g),
            self::clamp01($b),
            self::clamp01($a)
        );

        return static::fromSrgb($result);
    }

    public function complementary(): static
    {
        return $this->rotateHue(180.0);
    }

    public function cool(float $amount): static
    {
        $amount = self::clamp01($amount);
        $lch = OklchColor::from($this);
        $h = self::normAngle($lch->h);
        $targetHue = 210.0;

        $delta = fmod($targetHue - $h + 540.0, 360.0) - 180.0;
        if ($h < 90.0 && $delta < 0.0) {
            $delta = 360.0 + $delta;
        }
        $t = 0.5 * $amount;
        $newHue = self::normAngle($h + $delta * $t);
        $next = new OklchColor($lch->l, $lch->c, $newHue, $lch->alpha);

        return static::fromSrgb($next->toSrgb());
    }

    public function darken(float $amount): static
    {
        return $this->lighten(-$amount);
    }

    public function desaturate(float $amount): static
    {
        return $this->saturate(-abs($amount));
    }

    public function equals(ColorInterface $other): bool
    {
        if (($this::getSpaceName() !== $other::getSpaceName())
            || ($this->getAlpha() !== $other->getAlpha())) {
            return false;
        }

        return $this->toString() === $other->toString();
    }

    /**
     * Create an instance from a full set of channel values.
     *
     * @param array<string, float|int> $channels
     */
    abstract protected static function fromChannels(array $channels, float $alpha = 1.0): static;

    abstract public static function fromSrgb(SrgbColor $srgb): static;

    abstract public function getAlpha(): float;

    public function getHue(): float
    {
        $lch = OklchColor::from($this);
        if ($lch->c <= 1e-7) {
            return 0.0;
        }

        return self::normAngle($lch->h);
    }

    public function getLuminance(): float
    {
        $srgb = $this->toSrgb();
        $r = self::srgbToLinear($srgb->r);
        $g = self::srgbToLinear($srgb->g);
        $b = self::srgbToLinear($srgb->b);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    public function getOpacity(): float
    {
        return $this->getAlpha();
    }

    public function getSaturation(): float
    {
        $lch = OklchColor::from($this);

        return max(0.0, $lch->c);
    }

    abstract public static function getSpaceName(): string;

    public function grayscale(): static
    {
        $lch = OklchColor::from($this);
        $next = new OklchColor($lch->l, 0.0, $lch->h, $lch->alpha);

        return static::fromSrgb($next->toSrgb());
    }

    public function invert(): static
    {
        $srgb = $this->toSrgb();
        $inv = new SrgbColor(1.0 - $srgb->r, 1.0 - $srgb->g, 1.0 - $srgb->b, $srgb->a);

        return static::fromSrgb($inv);
    }

    public function isCold(): bool
    {
        return $this->temperature() < 0.0;
    }

    public function isDark(): bool
    {
        return !$this->isLight();
    }

    public function isHot(): bool
    {
        return $this->temperature() > 0.0;
    }

    public function isLight(): bool
    {
        return $this->getLuminance() >= 0.179;
    }

    public function isOpaque(): bool
    {
        return 1.0 === $this->getAlpha();
    }

    public function isTransparent(): bool
    {
        return 0.0 === $this->getAlpha();
    }

    public function lighten(float $amount): static
    {
        $lch = OklchColor::from($this);
        $l = self::clamp01($lch->l + $amount);
        $next = new OklchColor($l, $lch->c, $lch->h, $lch->alpha);

        return static::fromSrgb($next->toSrgb());
    }

    public function rotateHue(float $degrees): static
    {
        $lch = OklchColor::from($this);
        $h = self::normAngle($lch->h + $degrees);
        $next = new OklchColor($lch->l, $lch->c, $h, $lch->alpha);

        return static::fromSrgb($next->toSrgb());
    }

    public function saturate(float $amount): static
    {
        $lch = OklchColor::from($this);
        $c = max(0.0, $lch->c + $amount);
        $next = new OklchColor($lch->l, $c, $lch->h, $lch->alpha);

        return static::fromSrgb($next->toSrgb());
    }

    public function shade(float $t): static
    {
        $t = self::clamp01($t);
        $mixed = Color::mix($this, new SrgbColor(0.0, 0.0, 0.0, 1.0), $t, 'oklab');

        return static::fromSrgb($mixed->toSrgb());
    }

    public function splitComplementary(): array
    {
        return [
            $this,
            $this->rotateHue(150.0),
            $this->rotateHue(210.0),
        ];
    }

    public function temperature(): float
    {
        $lch = OklchColor::from($this);
        $h = self::normAngle($lch->h);

        if ($h >= 0.0 && $h < 90.0) {
            return 1.0 - $h / 90.0;
        } elseif ($h >= 90.0 && $h < 180.0) {
            return -($h - 90.0) / 90.0;
        } elseif ($h >= 180.0 && $h < 270.0) {
            return -1.0 + ($h - 180.0) / 90.0;
        }

        return ($h - 270.0) / 90.0;
    }

    public function tetradic(): array
    {
        return [
            $this,
            $this->rotateHue(90.0),
            $this->rotateHue(180.0),
            $this->rotateHue(270.0),
        ];
    }

    public function tint(float $t): static
    {
        $t = self::clamp01($t);
        $mixed = Color::mix($this, new SrgbColor(1.0, 1.0, 1.0, 1.0), $t, 'oklab');

        return static::fromSrgb($mixed->toSrgb());
    }

    public function to(string|ColorInterface $space): ColorInterface
    {
        if ($space instanceof ColorInterface) {
            if ($space instanceof static) {
                return $this;
            }

            $targetSpaceName = $space::getSpaceName();

            if ('srgb' === $targetSpaceName) {
                return $this->toSrgb();
            }

            $srgb = $this->toSrgb();

            return $space::fromSrgb($srgb);
        }

        $target = ColorSpaces::normalize($space);

        if ($target === static::getSpaceName()) {
            return $this;
        }

        if ('srgb' === $target || 'rgb' === $target) {
            return $this->toSrgb();
        }

        $srgb = $this->toSrgb();

        $spaceMap = [
            'oklab' => OklabColor::class,
            'oklch' => OklchColor::class,
            'display-p3' => DisplayP3Color::class,
            'xyz-d65' => XyzColor::class,
            'lab' => LabColor::class,
            'lch' => LchColor::class,
            'hwb' => HwbColor::class,
            'rec2020' => Rec2020Color::class,
            'prophoto-rgb' => ProPhotoColor::class,
            'a98-rgb' => A98RgbColor::class,
            'srgb-linear' => LinearSrgbColor::class,
            'cmyk' => CmykColor::class,
        ];

        if (!isset($spaceMap[$target])) {
            throw new InvalidColorException(\sprintf('Unknown or unsupported color space "%s".', $space));
        }

        $targetClass = $spaceMap[$target];

        return $targetClass::fromSrgb($srgb);
    }

    public function toHex(bool $withAlpha = false): string
    {
        return $this->toSrgb()->toHex($withAlpha);
    }

    public function toString(): string
    {
        return $this->toCss();
    }

    public function triadic(): array
    {
        return [
            $this,
            $this->rotateHue(120.0),
            $this->rotateHue(240.0),
        ];
    }

    public function warm(float $amount): static
    {
        $amount = self::clamp01($amount);
        $lch = OklchColor::from($this);
        $h = self::normAngle($lch->h);
        $targetHue = 30.0;

        $delta = fmod($targetHue - $h + 540.0, 360.0) - 180.0;
        $t = 0.5 * $amount;
        $newHue = self::normAngle($h + $delta * $t);
        $next = new OklchColor($lch->l, $lch->c, $newHue, $lch->alpha);

        return static::fromSrgb($next->toSrgb());
    }

    public function withAlpha(float $alpha = 1.0): static
    {
        return static::fromChannels($this->getChannels(), alpha: $alpha);
    }

    public function withChannel(string $name, float|int $value): static
    {
        return $this->withChannels([$name => (float) $value]);
    }

    public function withChannels(array $partial): static
    {
        $current = $this->getChannels();
        foreach (array_keys($partial) as $k) {
            if (!\array_key_exists($k, $current)) {
                throw new InvalidArgumentException(\sprintf('Unknown channel "%s" for the "%s" color space, which exposes "%s". Convert to a space that carries this channel first, for example to(\'oklch\') to reach "h".', $k, static::getSpaceName(), implode('", "', array_keys($current))));
            }
        }

        foreach ($partial as $k => $v) {
            $current[$k] = (float) $v;
        }

        return static::fromChannels($current, $this->getAlpha());
    }

    /**
     * Extract content between two substrings.
     */
    final protected static function between(string $haystack, string $start, string $end): ?string
    {
        $p1 = strpos($haystack, $start);
        $p2 = strrpos($haystack, $end);
        if (false === $p1 || false === $p2 || $p2 <= $p1) {
            return null;
        }

        return substr($haystack, $p1 + 1, $p2 - $p1 - 1);
    }

    /**
     * Clamp a value between 0 and 1.
     */
    final protected static function clamp01(float $x): float
    {
        if (0.0 > $x) {
            return 0.0;
        }
        if ($x > 1.0) {
            return 1.0;
        }

        return $x;
    }

    /**
     * Format a float value for CSS output, removing unnecessary trailing zeros.
     */
    final protected static function formatCssFloat(float $value): string
    {
        return rtrim(rtrim(\sprintf('%.6f', $value), '0'), '.');
    }

    /**
     * Convert a linear color component to sRGB.
     */
    final protected static function linearToSrgb(float $c): float
    {
        if (0.0031308 >= $c) {
            return 12.92 * $c;
        }

        return 1.055 * ($c ** (1.0 / 2.4)) - 0.055;
    }

    /**
     * Multiply a 3x3 matrix with a 3D vector.
     *
     * @param array{array{float,float,float},array{float,float,float},array{float,float,float}} $m
     * @param array{float,float,float}                                                          $v
     *
     * @return array{float,float,float}
     */
    final protected static function mul3x3(array $m, array $v): array
    {
        return [
            $m[0][0] * $v[0] + $m[0][1] * $v[1] + $m[0][2] * $v[2],
            $m[1][0] * $v[0] + $m[1][1] * $v[1] + $m[1][2] * $v[2],
            $m[2][0] * $v[0] + $m[2][1] * $v[1] + $m[2][2] * $v[2],
        ];
    }

    /**
     * Normalize a color space name by trimming and converting to lowercase.
     */
    final protected static function normalizeSpaceName(string $space): string
    {
        return strtolower(str_replace(['_', ' '], ['-', '-'], trim($space)));
    }

    /**
     * Normalize an angle in degrees to the range [0, 360).
     */
    final protected static function normAngle(float $deg): float
    {
        $r = fmod($deg, 360.0);
        if (0.0 > $r) {
            $r += 360.0;
        }

        return $r;
    }

    /**
     * Parse an alpha value from a CSS string (decimal or percentage).
     */
    final protected static function parseAlpha(string $value): float
    {
        $value = trim($value);
        if (str_ends_with($value, '%')) {
            return self::clamp01(((float) substr($value, 0, -1)) / 100.0);
        }

        return self::clamp01((float) $value);
    }

    /**
     * Parse a hue value from a CSS string, supporting various angle units.
     */
    final protected static function parseHue(string $value): float
    {
        $value = trim($value);

        $degrees = match (true) {
            str_ends_with($value, 'turn') => ((float) substr($value, 0, -4)) * 360.0,
            str_ends_with($value, 'grad') => ((float) substr($value, 0, -4)) * 0.9,
            str_ends_with($value, 'rad') => ((float) substr($value, 0, -3)) * (180.0 / \M_PI),
            str_ends_with($value, 'deg') => (float) substr($value, 0, -3),
            default => (float) $value,
        };

        return self::normAngle($degrees);
    }

    /**
     * Parse a percentage value from a CSS string.
     */
    final protected static function parsePercentage(string $value): float
    {
        $value = trim($value);
        if (!str_ends_with($value, '%')) {
            throw new ParseException(\sprintf('Expected percentage token, got "%s".', $value));
        }

        return self::clamp01(((float) substr($value, 0, -1)) / 100.0);
    }

    /**
     * Parse an RGB component value (0-255 or 0-100%).
     */
    final protected static function parseRgbComponent(string $value): float
    {
        $value = trim($value);
        if (str_ends_with($value, '%')) {
            return self::clamp01(((float) substr($value, 0, -1)) / 100.0);
        }

        return self::clamp01(((float) $value) / 255.0);
    }

    /**
     * Parse a numeric value that can be either a unit or a percentage.
     */
    final protected static function parseUnitOrPercent(string $value): float
    {
        $value = trim($value);
        if (str_ends_with($value, '%')) {
            return self::clamp01(((float) substr($value, 0, -1)) / 100.0);
        }

        return self::clamp01((float) $value);
    }

    /**
     * Convert an sRGB color component to linear.
     */
    final protected static function srgbToLinear(float $c): float
    {
        if (0.04045 >= $c) {
            return $c / 12.92;
        }

        return (($c + 0.055) / 1.055) ** 2.4;
    }

    /**
     * Apply a blend mode calculation for a single color channel.
     */
    private function applyBlendMode(float $src, float $dst, string $mode): float
    {
        return match ($mode) {
            'normal' => $src,
            'multiply' => $src * $dst,
            'screen' => 1.0 - (1.0 - $src) * (1.0 - $dst),
            'overlay' => $dst < 0.5
                ? 2.0 * $src * $dst
                : 1.0 - 2.0 * (1.0 - $src) * (1.0 - $dst),
            'darken' => min($src, $dst),
            'lighten' => max($src, $dst),
            'color-dodge' => $src >= 1.0 ? 1.0 : min(1.0, $dst / (1.0 - $src)),
            'color-burn' => $src <= 0.0 ? 0.0 : max(0.0, 1.0 - (1.0 - $dst) / $src),
            'hard-light' => $src < 0.5
                ? 2.0 * $src * $dst
                : 1.0 - 2.0 * (1.0 - $src) * (1.0 - $dst),
            'soft-light' => $src < 0.5
                ? $dst - (1.0 - 2.0 * $src) * $dst * (1.0 - $dst)
                : $dst + (2.0 * $src - 1.0) * ($this->softLightHelper($dst) - $dst),
            'difference' => abs($src - $dst),
            'exclusion' => $src + $dst - 2.0 * $src * $dst,
            default => $src,
        };
    }

    /**
     * Helper function for the soft-light blend mode calculation.
     */
    private function softLightHelper(float $x): float
    {
        if ($x <= 0.25) {
            return ((16.0 * $x - 12.0) * $x + 4.0) * $x;
        }

        return sqrt($x);
    }

    /**
     * Return the CSS representation of the color.
     *
     * Alias for toString(), invoked implicitly when the color is used in a
     * string context.
     */
    final public function __toString(): string
    {
        return $this->toString();
    }
}
