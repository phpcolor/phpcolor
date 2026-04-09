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

/**
 * Linear sRGB color value object.
 *
 * This class represents the linear-light version of sRGB, storing linearized
 * RGB channels in the range [0, 1].
 */
final readonly class LinearSrgbColor extends AbstractColor
{
    public float $a;

    /**
     * Create a new linear sRGB color from red, green, blue, and alpha components.
     */
    public function __construct(public float $r, public float $g, public float $b, float $a = 1.0)
    {
        $this->a = min(max($a, 0.0), 1.0);
    }

    public static function fromChannels(array $channels, float $alpha = 1.0): static
    {
        return new self(
            (float) ($channels['r'] ?? 0.0),
            (float) ($channels['g'] ?? 0.0),
            (float) ($channels['b'] ?? 0.0),
            $alpha,
        );
    }

    public static function fromSrgb(SrgbColor $srgb): static
    {
        $r = self::srgbToLinear($srgb->r);
        $g = self::srgbToLinear($srgb->g);
        $b = self::srgbToLinear($srgb->b);

        return new self($r, $g, $b, $srgb->a);
    }

    public static function getSpaceName(): string
    {
        return 'srgb-linear';
    }

    public function getAlpha(): float
    {
        return $this->a;
    }

    public function getChannels(): array
    {
        return ['r' => $this->r, 'g' => $this->g, 'b' => $this->b];
    }

    public function toCss(?string $space = null): string
    {
        $target = $space ? self::normalizeSpaceName($space) : 'color';

        if (null === $space || 'color' === $target) {
            $r = rtrim(rtrim(\sprintf('%.6f', $this->r), '0'), '.');
            $g = rtrim(rtrim(\sprintf('%.6f', $this->g), '0'), '.');
            $b = rtrim(rtrim(\sprintf('%.6f', $this->b), '0'), '.');
            if (1.0 === $this->a) {
                return \sprintf('color(srgb-linear %s %s %s)', $r, $g, $b);
            }
            $alpha = rtrim(rtrim(\sprintf('%.6f', $this->a), '0'), '.');

            return \sprintf('color(srgb-linear %s %s %s / %s)', $r, $g, $b, $alpha);
        }

        if ('srgb' === $target || 'rgb' === $target) {
            return $this->toSrgb()->toCss();
        }

        throw new InvalidColorException(\sprintf('CSS output for "%s" is not supported yet.', $space));
    }

    public function toSrgb(): SrgbColor
    {
        $r = self::linearToSrgb($this->r);
        $g = self::linearToSrgb($this->g);
        $b = self::linearToSrgb($this->b);

        return new SrgbColor($r, $g, $b, $this->a);
    }
}
