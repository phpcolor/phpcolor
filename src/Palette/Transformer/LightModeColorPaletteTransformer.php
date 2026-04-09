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
 * Transforms color palettes from dark mode to light mode using perceptual color transformations.
 *
 * Operates in Oklch space to preserve perceptual qualities while inverting lightness
 * and optionally boosting chroma for better vibrancy on light backgrounds.
 */
final readonly class LightModeColorPaletteTransformer implements ColorPaletteTransformerInterface
{
    /**
     * Create a new light mode transformer.
     *
     * @param float $bgLevel        Base lightness for transformed light backgrounds (0-1)
     * @param float $textLevel      Base lightness for transformed dark text (0-1)
     * @param float $chromaBoosting Factor to increase chroma for better light mode aesthetics (0-1)
     */
    public function __construct(
        private float $bgLevel = 0.95,
        private float $textLevel = 0.15,
        private float $chromaBoosting = 1.15,
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
     * Transform a single color for light mode.
     */
    private function transformColor(ColorInterface $color): ColorInterface
    {
        $oklch = OklchColor::from($color);

        $l = $oklch->l;
        $c = $oklch->c;

        $newL = $this->calculateLightness($l);
        $newC = $c > 0.05 ? min(0.4, $c * $this->chromaBoosting) : $c;

        $newOklch = new OklchColor($newL, $newC, $oklch->h, $oklch->alpha);

        return $newOklch->to($color::getSpaceName());
    }

    /**
     * Calculate the new lightness value for light mode.
     */
    private function calculateLightness(float $l): float
    {
        if ($l < 0.4) {
            return $this->bgLevel - ($l * 0.3);
        }

        if ($l > 0.6) {
            return $this->textLevel + ((1.0 - $l) * 0.5);
        }

        return max(0.0, $l - 0.10);
    }
}
