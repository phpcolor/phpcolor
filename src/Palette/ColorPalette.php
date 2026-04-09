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

namespace PhpColor\Color\Palette;

use PhpColor\Color\Color;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\OklabColor;
use PhpColor\Color\OklchColor;
use PhpColor\Color\Parser\ColorParser;

/**
 * Concrete implementation of a color palette.
 */
final readonly class ColorPalette implements ColorPaletteInterface
{
    /**
     * @param array<int|string, ColorInterface> $colors
     */
    private function __construct(
        private array $colors,
        private bool $isNamed = false,
    ) {
    }

    /**
     * @return \Traversable<int|string, ColorInterface>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->colors);
    }

    /**
     * @param array<int|string, string> $hexColors
     */
    public static function fromHex(array $hexColors): self
    {
        $colors = [];
        foreach ($hexColors as $key => $hex) {
            $colors[$key] = ColorParser::parse((string) $hex);
        }

        return new self($colors, !array_is_list($hexColors));
    }

    public static function interpolate(ColorInterface $start, ColorInterface $end, int $steps = 10, string $space = 'oklab'): self
    {
        if (2 > $steps) {
            throw new InvalidColorException('Interpolation requires at least 2 steps.');
        }
        $colors = [];
        for ($i = 0; $i < $steps; ++$i) {
            $t = $i / ($steps - 1);
            $colors[] = Color::mix($start, $end, $t, $space);
        }

        return new self($colors, false);
    }

    public static function lightnessScale(ColorInterface $color, int $steps = 10, float $min = 0.05, float $max = 0.95): self
    {
        if (1 > $steps) {
            throw new InvalidColorException('Steps must be at least 1.');
        }
        $oklch = $color->to('oklch');
        if (!$oklch instanceof OklchColor) {
            $oklch = OklchColor::fromSrgb($oklch->toSrgb());
        }
        $colors = [];
        for ($i = 0; $i < $steps; ++$i) {
            $t = $steps > 1 ? $i / ($steps - 1) : 0.0;
            $lightness = $min + ($max - $min) * $t;
            $colors[] = new OklchColor($lightness, $oklch->c, $oklch->h, $oklch->alpha);
        }

        return new self($colors, false);
    }

    /**
     * @param array<string, ColorInterface> $colors
     */
    public static function named(array $colors): self
    {
        if ([] === $colors) {
            throw new InvalidColorException('Named palette requires at least one color.');
        }

        return new self($colors, true);
    }

    /**
     * @param array<int|string, string> $cssColors
     */
    public static function parse(array $cssColors): self
    {
        $colors = [];
        foreach ($cssColors as $key => $css) {
            $colors[$key] = Color::parse((string) $css);
        }

        return new self($colors, !array_is_list($cssColors));
    }

    /**
     * @param array<int, ColorInterface> $colors
     */
    public static function scale(array $colors): self
    {
        if ([] === $colors) {
            throw new InvalidColorException('Scale palette requires at least one color.');
        }

        return new self($colors, false);
    }

    public static function shades(ColorInterface $color, int $steps = 5): self
    {
        if (1 > $steps) {
            throw new InvalidColorException('Steps must be at least 1.');
        }
        $colors = [];
        for ($i = 0; $i < $steps; ++$i) {
            $t = $steps > 1 ? $i / ($steps - 1) : 0.0;
            $colors[] = $color->shade($t);
        }

        return new self($colors, false);
    }

    public static function tints(ColorInterface $color, int $steps = 5): self
    {
        if (1 > $steps) {
            throw new InvalidColorException('Steps must be at least 1.');
        }
        $colors = [];
        for ($i = 0; $i < $steps; ++$i) {
            $t = $steps > 1 ? $i / ($steps - 1) : 0.0;
            $colors[] = $color->tint($t);
        }

        return new self($colors, false);
    }

    public static function builder(ColorInterface|string|null $base = null): ColorPaletteBuilder
    {
        return new ColorPaletteBuilder($base);
    }

    public function all(): array
    {
        return $this->colors;
    }

    public function at(float $position): ColorInterface
    {
        if ($this->isNamed) {
            throw new InvalidColorException('at() requires a scale palette.');
        }
        if ([] === $this->colors) {
            throw new InvalidColorException('Cannot get color from empty palette.');
        }
        $position = max(0.0, min(1.0, $position));
        $colors = array_values($this->colors);
        $count = \count($colors);
        if (1 === $count) {
            return $colors[0];
        }
        $scaledPos = $position * ($count - 1);
        $index = (int) floor($scaledPos);
        $fraction = $scaledPos - $index;
        if ($index >= $count - 1) {
            return $colors[$count - 1];
        }

        return Color::mix($colors[$index], $colors[$index + 1], $fraction, 'oklab');
    }

    public function closest(ColorInterface $target): ColorInterface
    {
        if ([] === $this->colors) {
            throw new InvalidColorException('Cannot find closest color in empty palette.');
        }
        $targetOklab = $target->to('oklab');
        if (!$targetOklab instanceof OklabColor) {
            $targetOklab = OklabColor::fromSrgb($targetOklab->toSrgb());
        }
        $minDistance = \PHP_FLOAT_MAX;
        $closest = array_values($this->colors)[0];
        foreach ($this->colors as $color) {
            $oklab = $color->to('oklab');
            if (!$oklab instanceof OklabColor) {
                $oklab = OklabColor::fromSrgb($oklab->toSrgb());
            }
            $distance = sqrt(($targetOklab->l - $oklab->l) ** 2 + ($targetOklab->a - $oklab->a) ** 2 + ($targetOklab->b - $oklab->b) ** 2);
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closest = $color;
            }
        }

        return $closest;
    }

    public function count(): int
    {
        return \count($this->colors);
    }

    public function darken(float $amount): static
    {
        return $this->map(static fn (ColorInterface $c): ColorInterface => $c->darken($amount));
    }

    public function desaturate(float $amount): static
    {
        return $this->map(static fn (ColorInterface $c): ColorInterface => $c->desaturate($amount));
    }

    public function filter(callable $fn): static
    {
        $colors = [];
        foreach ($this->colors as $key => $color) {
            if ($fn($color)) {
                $colors[$key] = $color;
            }
        }

        return new self($colors, $this->isNamed);
    }

    public function get(int|string $key): ColorInterface
    {
        if (!isset($this->colors[$key])) {
            throw new InvalidColorException(\sprintf('Color "%s" not found in palette.', $key));
        }

        return $this->colors[$key];
    }

    public function isNamed(): bool
    {
        return $this->isNamed;
    }

    public function lighten(float $amount): static
    {
        return $this->map(static fn (ColorInterface $c): ColorInterface => $c->lighten($amount));
    }

    public function map(callable $fn): static
    {
        $colors = [];
        foreach ($this->colors as $key => $color) {
            $colors[$key] = $fn($color);
        }

        return new self($colors, $this->isNamed);
    }

    public function merge(ColorPaletteInterface $other): static
    {
        if ($this->isNamed || $other->isNamed()) {
            throw new InvalidColorException('Cannot merge named palettes.');
        }

        return new self([...$this->colors, ...$other->all()], false);
    }

    public function names(): array
    {
        if (!$this->isNamed) {
            return [];
        }

        return array_values(array_filter(array_keys($this->colors), is_string(...)));
    }

    public function reverse(): static
    {
        if ($this->isNamed) {
            throw new InvalidColorException('Cannot reverse a named palette.');
        }

        return new self(array_reverse($this->colors), false);
    }

    public function rotateHue(float $degrees): static
    {
        return $this->map(static fn (ColorInterface $c): ColorInterface => $c->rotateHue($degrees));
    }

    public function saturate(float $amount): static
    {
        return $this->map(static fn (ColorInterface $c): ColorInterface => $c->saturate($amount));
    }

    public function slice(int $offset, ?int $length = null): static
    {
        if ($this->isNamed) {
            throw new InvalidColorException('Cannot slice a named palette.');
        }

        return new self(\array_slice($this->colors, $offset, $length, true), false);
    }

    public function to(string $space): static
    {
        return $this->map(static fn (ColorInterface $c): ColorInterface => $c->to($space));
    }

    public function toCss(?string $space = null): array
    {
        $result = [];
        foreach ($this->colors as $key => $color) {
            $result[$key] = $color->toCss($space);
        }

        return $result;
    }

    public function toCssVariables(string $prefix = 'color'): string
    {
        if (!$this->isNamed) {
            throw new InvalidColorException('CSS variables require a named palette.');
        }
        $css = '';
        foreach ($this->colors as $name => $color) {
            $css .= \sprintf("  --%s-%s: %s;\n", $prefix, $name, $color->toHex());
        }

        return $css;
    }

    public function toHex(): array
    {
        $result = [];
        foreach ($this->colors as $key => $color) {
            $result[$key] = $color->toHex();
        }

        return $result;
    }
}
