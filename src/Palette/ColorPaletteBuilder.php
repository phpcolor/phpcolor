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
use PhpColor\Color\Palette\Fixer\PaletteFixerInterface;
use PhpColor\Color\Palette\Harmony\HarmonyGenerator;
use PhpColor\Color\Palette\Harmony\HarmonyPattern;
use PhpColor\Color\Palette\Transformer\CallableColorPaletteTransformer;
use PhpColor\Color\Palette\Transformer\ColorPaletteTransformerInterface;

/**
 * Fluent builder for creating and manipulating color palettes.
 */
final class ColorPaletteBuilder
{
    /** @var array<int|string, ColorInterface> */
    private array $colors = [];

    private bool $isNamed = false;

    /**
     * @internal use ColorPalette::builder() instead
     */
    public function __construct(ColorInterface|string|null $base = null)
    {
        if (null !== $base) {
            $this->add($base);
        }
    }

    /**
     * Add a color to the palette.
     */
    public function add(ColorInterface|string $color, string|int|null $name = null): self
    {
        $colorInstance = $color instanceof ColorInterface ? $color : Color::parse($color);

        if (null !== $name) {
            $this->colors[$name] = $colorInstance;
            $this->isNamed = true;
        } else {
            $this->colors[] = $colorInstance;
        }

        return $this;
    }

    /**
     * Add multiple colors to the palette.
     *
     * @param iterable<ColorInterface|string> $colors
     */
    public function addAll(iterable $colors): self
    {
        foreach ($colors as $key => $color) {
            $this->add($color, \is_string($key) ? $key : null);
        }

        return $this;
    }

    /**
     * Add a harmony palette generated from a base color.
     */
    public function harmony(ColorInterface|string $base, HarmonyPattern|string $pattern): self
    {
        $baseInstance = $base instanceof ColorInterface ? $base : Color::parse($base);
        $patternEnum = $pattern instanceof HarmonyPattern ? $pattern : HarmonyPattern::from($pattern);

        $generator = new HarmonyGenerator();
        $palette = $generator->generate($baseInstance, $patternEnum);

        return $this->addAll($palette->all());
    }

    /**
     * Add a scale of shades generated from a base color.
     */
    public function shades(ColorInterface|string $color, int $steps = 5): self
    {
        $colorInstance = $color instanceof ColorInterface ? $color : Color::parse($color);
        $palette = ColorPalette::shades($colorInstance, $steps);

        return $this->addAll($palette->all());
    }

    /**
     * Add a scale of tints generated from a base color.
     */
    public function tints(ColorInterface|string $color, int $steps = 5): self
    {
        $colorInstance = $color instanceof ColorInterface ? $color : Color::parse($color);
        $palette = ColorPalette::tints($colorInstance, $steps);

        return $this->addAll($palette->all());
    }

    /**
     * Apply a transformer to the current colors in the builder.
     */
    public function transform(ColorPaletteTransformerInterface|callable $transformer): self
    {
        $palette = $this->build();
        if (\is_callable($transformer)) {
            $transformer = new CallableColorPaletteTransformer($transformer);
        }

        $transformedPalette = $transformer->transform($palette);
        $this->colors = $transformedPalette->all();
        $this->isNamed = $transformedPalette->isNamed();

        return $this;
    }

    /**
     * Apply a fixer to the current colors in the builder.
     *
     * @param array<string, mixed> $options
     */
    public function fix(PaletteFixerInterface $fixer, array $options = []): self
    {
        $palette = $this->build();
        $fixedPalette = $fixer->fix($palette, $options);
        $this->colors = $fixedPalette->all();
        $this->isNamed = $fixedPalette->isNamed();

        return $this;
    }

    /**
     * Build the color palette.
     */
    public function build(): ColorPalette
    {
        if ($this->isNamed) {
            /** @var array<string, ColorInterface> $colors */
            $colors = $this->colors;

            return ColorPalette::named($colors);
        }

        return ColorPalette::scale(array_values($this->colors));
    }
}
