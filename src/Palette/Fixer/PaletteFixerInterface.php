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

namespace PhpColor\Color\Palette\Fixer;

use PhpColor\Color\Palette\ColorPaletteInterface;

/**
 * Interface for palette fixers that correct issues in color palettes.
 *
 * Fixers analyze color palettes and apply corrections to improve specific
 * qualities such as accessibility (contrast) or color theory (harmony).
 */
interface PaletteFixerInterface
{
    /**
     * Analyze and fix issues in the given palette.
     *
     * @param ColorPaletteInterface $palette The palette to analyze and fix
     * @param array<string, mixed>  $options Fixer-specific options
     *
     * @return ColorPaletteInterface A new palette with corrections applied
     */
    public function fix(ColorPaletteInterface $palette, array $options = []): ColorPaletteInterface;

    /**
     * Analyze the palette without making corrections.
     *
     * Returns information about detected issues and their severity.
     *
     * @param ColorPaletteInterface $palette The palette to analyze
     *
     * @return array<string, mixed> Analysis results with issue details
     */
    public function analyze(ColorPaletteInterface $palette): array;
}
