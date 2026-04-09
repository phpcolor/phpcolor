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

namespace PhpColor\Color\Palette\Transformer;

use PhpColor\Color\ColorInterface;
use PhpColor\Color\Palette\ColorPalette;

/**
 * Transforms a color palette by applying a callable to each color.
 *
 * This flexible transformer allows you to create custom transformations
 * by passing any callable that transforms a single color.
 *
 * Example: Create a sepia transformer
 * ```php
 * $sepia = new CallableColorPaletteTransformer(function (ColorInterface $color): ColorInterface {
 *     $oklch = $color->to('oklch');
 *     return new OklchColor($oklch->l, min(0.1, $oklch->c), 70.0, $oklch->alpha);
 * });
 * $sepiaPalette = $sepia->transform($palette);
 * ```
 */
final readonly class CallableColorPaletteTransformer implements ColorPaletteTransformerInterface
{
    /**
     * @param callable(ColorInterface): ColorInterface $callable
     */
    public function __construct(
        private mixed $callable,
    ) {
    }

    public function transform(ColorPalette $palette): ColorPalette
    {
        $newColors = [];

        foreach ($palette->all() as $name => $color) {
            $newColors[(string) $name] = ($this->callable)($color);
        }

        return ColorPalette::named($newColors);
    }
}
