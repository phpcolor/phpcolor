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

enum HarmonyPattern: string
{
    case Analogous = 'analogous';
    case Complementary = 'complementary';
    case SplitComplementary = 'split_complementary';
    case Triadic = 'triadic';
    case Tetradic = 'tetradic';

    /**
     * @return array<float>
     */
    public function angles(): array
    {
        return match ($this) {
            self::Analogous => [30.0, -30.0],
            self::Complementary => [180.0],
            self::SplitComplementary => [150.0, 210.0],
            self::Triadic => [120.0, 240.0],
            self::Tetradic => [90.0, 180.0, 270.0],
        };
    }

    /**
     * @return array<float>
     */
    public function fullAngles(): array
    {
        return [0.0, ...$this->angles()];
    }
}
