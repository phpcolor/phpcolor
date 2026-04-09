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

namespace PhpColor\Color\Tests\Colorimetry;

use PhpColor\Color\ColorInterface;
use PhpColor\Color\SrgbColor;

/**
 * Lightweight test stub implementing ColorInterface just enough for gamut tests.
 */
final class StubColor implements \Stringable, ColorInterface
{
    /**
     * @param array<string,float> $channels
     */
    public function __construct(private array $channels, private readonly float $alpha = 1.0)
    {
    }

    public static function getSpaceName(): string
    {
        return 'stub';
    }

    // Unused methods for interface compliance ----------------------------------
    public static function from(ColorInterface|string $input): static
    {
        throw new \LogicException('not used');
    }

    public static function tryFrom(ColorInterface|string $input): ?static
    {
        return null;
    }

    public static function fromSrgb(SrgbColor $srgb): static
    {
        return new self(['r' => $srgb->r, 'g' => $srgb->g, 'b' => $srgb->b], $srgb->a);
    }

    // Minimal API used in tests -------------------------------------------------

    public function getChannels(): array
    {
        return $this->channels;
    }

    public function getAlpha(): float
    {
        return $this->alpha;
    }

    public function to(string|ColorInterface $space): ColorInterface
    {
        if (\is_string($space)) {
            $s = strtolower($space);
            if ('srgb' === $s || 'rgb' === $s) {
                $r = (float) ($this->channels['r'] ?? 0.0);
                $g = (float) ($this->channels['g'] ?? 0.0);
                $b = (float) ($this->channels['b'] ?? 0.0);

                return new SrgbColor($r, $g, $b, $this->alpha);
            }

            if ('oklch' === $s) {
                // Return a stub without r/g/b to trigger fallback in Gamut::isInGamut
                return new self(['l' => 0.5], $this->alpha);
            }

            return $this; // generic passthrough
        }

        return $this; // not used in these tests
    }

    public function toSrgb(): SrgbColor
    {
        $r = (float) ($this->channels['r'] ?? 0.0);
        $g = (float) ($this->channels['g'] ?? 0.0);
        $b = (float) ($this->channels['b'] ?? 0.0);

        return new SrgbColor($r, $g, $b, $this->alpha);
    }

    public function analogous(int $count = 2): array
    {
        return [$this];
    }

    public function blend(ColorInterface|string $backdrop, string $mode = 'normal'): static
    {
        return $this;
    }

    public function complementary(): static
    {
        return $this;
    }

    public function cool(float $amount): static
    {
        return $this;
    }

    public function darken(float $amount): static
    {
        return $this;
    }

    public function desaturate(float $amount): static
    {
        return $this;
    }

    public function equals(ColorInterface $other): bool
    {
        return false;
    }

    public function getHue(): float
    {
        return 0.0;
    }

    public function getLuminance(): float
    {
        return 0.0;
    }

    public function getOpacity(): float
    {
        return $this->alpha;
    }

    public function getSaturation(): float
    {
        return 0.0;
    }

    public function grayscale(): static
    {
        return $this;
    }

    public function invert(): static
    {
        return $this;
    }

    public function isCold(): bool
    {
        return false;
    }

    public function isDark(): bool
    {
        return false;
    }

    public function isHot(): bool
    {
        return false;
    }

    public function isLight(): bool
    {
        return true;
    }

    public function isOpaque(): bool
    {
        return 1.0 === $this->alpha;
    }

    public function isTransparent(): bool
    {
        return 0.0 === $this->alpha;
    }

    public function lighten(float $amount): static
    {
        return $this;
    }

    public function rotateHue(float $degrees): static
    {
        return $this;
    }

    public function saturate(float $amount): static
    {
        return $this;
    }

    public function shade(float $t): static
    {
        return $this;
    }

    public function splitComplementary(): array
    {
        return [$this];
    }

    public function temperature(): float
    {
        return 0.0;
    }

    public function tetradic(): array
    {
        return [$this];
    }

    public function tint(float $t): static
    {
        return $this;
    }

    public function toCss(?string $space = null): string
    {
        return '';
    }

    public function toHex(bool $withAlpha = false): string
    {
        return '';
    }

    public function triadic(): array
    {
        return [$this];
    }

    public function warm(float $amount): static
    {
        return $this;
    }

    public function withAlpha(float $alpha = 1.0): static
    {
        return new self($this->channels, $alpha);
    }

    public function withChannel(string $name, float|int $value): static
    {
        $c = $this->channels;
        $c[$name] = (float) $value;

        return new self($c, $this->alpha);
    }

    public function withChannels(array $partial): static
    {
        return new self(array_merge($this->channels, $partial), $this->alpha);
    }

    public function toString(): string
    {
        return '';
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
