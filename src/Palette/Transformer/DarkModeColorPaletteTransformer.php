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
use PhpColor\Color\OklchColor;
use PhpColor\Color\Palette\ColorPalette;

/**
 * Transforms color palettes for dark mode using perceptual color transformations.
 *
 * Operates in Oklch space to preserve perceptual qualities while inverting lightness
 * and optionally dampening chroma for better readability on dark backgrounds.
 */
final readonly class DarkModeColorPaletteTransformer implements ColorPaletteTransformerInterface
{
    /**
     * Create a new dark mode transformer.
     *
     * @param float $bgLevel         Base lightness for transformed dark backgrounds (0-1)
     * @param float $textLevel       Base lightness for transformed light text (0-1)
     * @param float $chromaDampening Factor to reduce chroma for better dark mode aesthetics (0-1)
     */
    public function __construct(
        private float $bgLevel = 0.15,
        private float $textLevel = 0.95,
        private float $chromaDampening = 0.85,
    ) {
    }

    public function transform(ColorPalette $palette): ColorPalette
    {
        $newColors = [];

        foreach ($palette->all() as $name => $color) {
            $newColors[(string) $name] = $this->transformColor($color);
        }

        return ColorPalette::named($newColors);
    }

    /**
     * Transform a single color for dark mode.
     */
    private function transformColor(ColorInterface $color): ColorInterface
    {
        $oklch = OklchColor::from($color);

        $l = $oklch->l;
        $c = $oklch->c;

        $newL = $this->calculateLightness($l);
        $newC = $c > 0.05 ? $c * $this->chromaDampening : $c;

        $newOklch = new OklchColor($newL, $newC, $oklch->h, $oklch->alpha);

        return $newOklch->to($color::getSpaceName());
    }

    /**
     * Calculate the new lightness value for dark mode.
     */
    private function calculateLightness(float $l): float
    {
        if ($l > 0.6) {
            return $this->bgLevel + (1.0 - $l) * 0.3;
        }

        if ($l < 0.4) {
            return $this->textLevel - ($l * 0.5);
        }

        return min(1.0, $l + 0.10);
    }
}
