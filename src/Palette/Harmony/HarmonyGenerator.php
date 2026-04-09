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

namespace PhpColor\Color\Palette\Harmony;

use PhpColor\Color\ColorInterface;
use PhpColor\Color\OklchColor;
use PhpColor\Color\Palette\ColorPalette;
use PhpColor\Color\Palette\ColorPaletteInterface;

/**
 * Generates theoretical color harmonies from a base color.
 */
final class HarmonyGenerator
{
    /**
     * Generate a harmony palette from a base color and a pattern.
     *
     * @param ColorInterface $baseColor The base color to generate harmony from
     * @param HarmonyPattern $pattern   The harmony pattern to apply
     *
     * @return ColorPaletteInterface The generated harmony palette
     */
    public function generate(ColorInterface $baseColor, HarmonyPattern $pattern): ColorPaletteInterface
    {
        $oklch = $baseColor->to('oklch');
        if (!$oklch instanceof OklchColor) {
            $oklch = OklchColor::fromSrgb($oklch->toSrgb());
        }

        $colors = [$baseColor];
        foreach ($pattern->angles() as $angle) {
            $colors[] = new OklchColor(
                $oklch->l,
                $oklch->c,
                $this->normalizeHue($oklch->h + $angle),
                $oklch->alpha
            );
        }

        return ColorPalette::scale($colors);
    }

    /**
     * Normalize a hue angle to the range [0, 360).
     */
    private function normalizeHue(float $hue): float
    {
        $hue = fmod($hue, 360.0);

        return $hue < 0.0 ? $hue + 360.0 : $hue;
    }
}
