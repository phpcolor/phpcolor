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

namespace PhpColor\Color\Distance;

use PhpColor\Color\ColorInterface;

/**
 * CIE Delta E 94 color distance algorithm.
 */
final readonly class DeltaE94 implements ColorDistanceInterface
{
    /**
     * Create a new Delta E 94 distance calculator.
     *
     * @param float $kL Lightness weighting factor (default: 1.0)
     * @param float $kC Chroma weighting factor (default: 1.0)
     * @param float $kH Hue weighting factor (default: 1.0)
     * @param float $K1 First application-specific constant (default: 0.045 for graphic arts)
     * @param float $K2 Second application-specific constant (default: 0.015 for graphic arts)
     */
    public function __construct(
        public float $kL = 1.0,
        public float $kC = 1.0,
        public float $kH = 1.0,
        public float $K1 = 0.045,
        public float $K2 = 0.015,
    ) {
    }

    public function calculate(ColorInterface $color1, ColorInterface $color2): float
    {
        $lab1 = $color1->to('lab');
        $lab2 = $color2->to('lab');

        $channels1 = $lab1->getChannels();
        $channels2 = $lab2->getChannels();

        $L1 = $channels1['l'];
        $a1 = $channels1['a'];
        $b1 = $channels1['b'];

        $L2 = $channels2['l'];
        $a2 = $channels2['a'];
        $b2 = $channels2['b'];

        return $this->calculateFromLab($L1, $a1, $b1, $L2, $a2, $b2);
    }

    /**
     * Calculate Delta E 94 distance from Lab values directly.
     *
     * @internal
     */
    public function calculateFromLab(
        float $L1,
        float $a1,
        float $b1,
        float $L2,
        float $a2,
        float $b2,
    ): float {
        $deltaL = $L1 - $L2;
        $deltaA = $a1 - $a2;
        $deltaB = $b1 - $b2;

        $C1 = sqrt($a1 * $a1 + $b1 * $b1);
        $deltaC = $C1 - sqrt($a2 * $a2 + $b2 * $b2);

        $deltaH_squared = $deltaA * $deltaA + $deltaB * $deltaB - $deltaC * $deltaC;
        $deltaH = $deltaH_squared >= 0.0 ? sqrt($deltaH_squared) : 0.0;

        $SC = 1.0 + $this->K1 * $C1;
        $SH = 1.0 + $this->K2 * $C1;

        return sqrt(
            ($deltaL / $this->kL) ** 2
            + ($deltaC / ($this->kC * $SC)) ** 2
            + ($deltaH / $this->kH * $SH) ** 2
        );
    }

    public function getName(): string
    {
        return 'DeltaE94';
    }
}
