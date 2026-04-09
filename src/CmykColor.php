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

use PhpColor\Color\Exception\ParseException;

/**
 * Represents a color in the CMYK color space.
 *
 * CMYK (Cyan, Magenta, Yellow, and Key/Black) is a subtractive color model
 * used in color printing.
 */
final readonly class CmykColor extends AbstractColor
{
    /**
     * Pattern for device-cmyk() CSS function.
     */
    private const string CMYK_PATTERN = '/^\s*device-cmyk\(\s*([^\s,\/]+)\s+([^\s,\/]+)\s+([^\s,\/]+)\s+([^\s,\/]+)(?:\s*\/\s*([^)]+))?\s*\)\s*$/i';

    public float $alpha;
    public float $c;
    public float $k;
    public float $m;
    public float $y;

    /**
     * Create a new CMYK color from cyan, magenta, yellow, black, and alpha components.
     */
    public function __construct(
        float $c,
        float $m,
        float $y,
        float $k,
        float $alpha = 1.0,
    ) {
        $this->c = self::clamp01($c);
        $this->m = self::clamp01($m);
        $this->y = self::clamp01($y);
        $this->k = self::clamp01($k);
        $this->alpha = self::clamp01($alpha);
    }

    /**
     * Create color from channel values.
     *
     * @param array<string, float> $channels
     */
    public static function fromChannels(array $channels, float $alpha = 1.0): static
    {
        return new self(
            $channels['c'] ?? 0.0,
            $channels['m'] ?? 0.0,
            $channels['y'] ?? 0.0,
            $channels['k'] ?? 0.0,
            $alpha
        );
    }

    public static function fromSrgb(SrgbColor $srgb): static
    {
        $r = $srgb->r;
        $g = $srgb->g;
        $b = $srgb->b;

        $k = 1.0 - max($r, $g, $b);

        if (1.0 === $k) {
            return new self(0.0, 0.0, 0.0, 1.0, $srgb->a);
        }

        $c = (1.0 - $r - $k) / (1.0 - $k);
        $m = (1.0 - $g - $k) / (1.0 - $k);
        $y = (1.0 - $b - $k) / (1.0 - $k);

        return new self($c, $m, $y, $k, $srgb->a);
    }

    public static function getSpaceName(): string
    {
        return 'cmyk';
    }

    /**
     * Parse a device-cmyk() color string into a CMYK color instance.
     */
    public static function parse(string $color): static
    {
        if (!preg_match(self::CMYK_PATTERN, $color, $m)) {
            throw new ParseException(\sprintf('Cannot parse color "%s".', $color));
        }

        $c = self::parseUnitOrPercent($m[1]);
        $m2 = self::parseUnitOrPercent($m[2]);
        $y = self::parseUnitOrPercent($m[3]);
        $k = self::parseUnitOrPercent($m[4]);
        $alpha = isset($m[5]) ? self::parseAlpha(trim($m[5])) : 1.0;

        return new self($c, $m2, $y, $k, $alpha);
    }

    public function getAlpha(): float
    {
        return $this->alpha;
    }

    /**
     * Get the black (key) component value (0 to 1).
     */
    public function getBlack(): float
    {
        return $this->k;
    }

    public function getChannels(): array
    {
        return ['c' => $this->c, 'm' => $this->m, 'y' => $this->y, 'k' => $this->k];
    }

    /**
     * Get the cyan component value (0 to 1).
     */
    public function getCyan(): float
    {
        return $this->c;
    }

    /**
     * Get the magenta component value (0 to 1).
     */
    public function getMagenta(): float
    {
        return $this->m;
    }

    /**
     * Get the yellow component value (0 to 1).
     */
    public function getYellow(): float
    {
        return $this->y;
    }

    public function toCss(?string $space = null): string
    {
        $target = $space ? self::normalizeSpaceName($space) : 'cmyk';

        if (null === $space || 'cmyk' === $target) {
            $c = self::formatCssFloat($this->c * 100.0);
            $m = self::formatCssFloat($this->m * 100.0);
            $y = self::formatCssFloat($this->y * 100.0);
            $k = self::formatCssFloat($this->k * 100.0);

            if (1.0 === $this->alpha) {
                return \sprintf('device-cmyk(%s%% %s%% %s%% %s%%)', $c, $m, $y, $k);
            }

            $alpha = self::formatCssFloat($this->alpha);

            return \sprintf('device-cmyk(%s%% %s%% %s%% %s%% / %s)', $c, $m, $y, $k, $alpha);
        }

        if ('srgb' === $target || 'rgb' === $target) {
            return $this->toSrgb()->toCss($target);
        }

        throw new Exception\InvalidColorException(\sprintf('CSS output for "%s" is not supported yet.', $space));
    }

    public function toSrgb(): SrgbColor
    {
        $r = (1.0 - $this->c) * (1.0 - $this->k);
        $g = (1.0 - $this->m) * (1.0 - $this->k);
        $b = (1.0 - $this->y) * (1.0 - $this->k);

        return new SrgbColor($r, $g, $b, $this->alpha);
    }
}
