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

namespace PhpColor\Color\Vision;

/**
 * Common color vision deficiency profiles.
 */
enum VisionProfile: string
{
    /** Red-absent dichromacy. */
    case Protanopia = 'protanopia';
    /** Green-absent dichromacy. */
    case Deuteranopia = 'deuteranopia';
    /** Blue-absent dichromacy. */
    case Tritanopia = 'tritanopia';
    /** Reduced red sensitivity. */
    case Protanomaly = 'protanomaly';
    /** Reduced green sensitivity (most common). */
    case Deuteranomaly = 'deuteranomaly';
    /** Reduced blue sensitivity. */
    case Tritanomaly = 'tritanomaly';
    /** Achromatic vision (no color discrimination). */
    case Monochromacy = 'monochromacy';

    /**
     * Get the most commonly tested vision deficiency profiles.
     *
     * @return array<int, self>
     */
    public static function commonlyTested(): array
    {
        return [
            self::Deuteranomaly,
            self::Deuteranopia,
            self::Protanomaly,
            self::Protanopia,
        ];
    }

    /**
     * Get a human-readable description of the profile.
     */
    public function description(): string
    {
        return match ($this) {
            self::Protanopia => 'Protanopia (red-absent vision)',
            self::Deuteranopia => 'Deuteranopia (green-absent vision)',
            self::Tritanopia => 'Tritanopia (blue-absent vision)',
            self::Protanomaly => 'Protanomaly (reduced red sensitivity)',
            self::Deuteranomaly => 'Deuteranomaly (reduced green sensitivity)',
            self::Tritanomaly => 'Tritanomaly (reduced blue sensitivity)',
            self::Monochromacy => 'Monochromacy (achromatic vision)',
        };
    }

    /**
     * Get the estimated percentage of the population with this profile.
     */
    public function populationShare(): float
    {
        return match ($this) {
            self::Deuteranomaly => 0.049,
            self::Protanomaly => 0.010,
            self::Deuteranopia => 0.012,
            self::Protanopia => 0.002,
            self::Tritanomaly => 0.0001,
            self::Tritanopia => 0.0001,
            self::Monochromacy => 0.00003,
        };
    }

    /**
     * Check if the deficiency is considered severe (full absence or monochromacy).
     */
    public function isSevere(): bool
    {
        return match ($this) {
            self::Protanopia, self::Deuteranopia, self::Tritanopia, self::Monochromacy => true,
            self::Protanomaly, self::Deuteranomaly, self::Tritanomaly => false,
        };
    }
}
