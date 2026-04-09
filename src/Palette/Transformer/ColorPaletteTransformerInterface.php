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

use PhpColor\Color\Palette\ColorPalette;

/**
 * Interface for palette transformers that create modified versions of color palettes.
 *
 * Transformers apply systematic changes to all colors in a palette, such as
 * converting a scheme for dark mode or applying artistic effects.
 */
interface ColorPaletteTransformerInterface
{
    /**
     * Transform a color palette to a new palette with modified colors.
     */
    public function transform(ColorPalette $palette): ColorPalette;
}
