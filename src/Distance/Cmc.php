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
 * CMC l:c (1984) color distance algorithm.
 */
final readonly class Cmc implements ColorDistanceInterface
{
    /**
     * Create a new CMC distance calculator.
     *
     * @param float $l Lightness weighting factor
     * @param float $c Chroma weighting factor
     */
    public function __construct(
        public float $l = 2.0,
        public float $c = 1.0,
    ) {
    }

    /**
     * Create a CMC calculator for acceptability (2:1 ratio).
     */
    public static function forAcceptability(): self
    {
        return new self(2.0, 1.0);
    }

    /**
     * Create a CMC calculator for perceptibility (1:1 ratio).
     */
    public static function forPerceptibility(): self
    {
        return new self(1.0, 1.0);
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
     * Calculate CMC l:c distance from Lab values directly.
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
        $C2 = sqrt($a2 * $a2 + $b2 * $b2);
        $deltaC = $C1 - $C2;

        $H1 = $this->calculateHue($a1, $b1);

        $deltaH_squared = $deltaA * $deltaA + $deltaB * $deltaB - $deltaC * $deltaC;
        $deltaH = $deltaH_squared >= 0.0 ? sqrt($deltaH_squared) : 0.0;

        $SL = $L1 < 16.0 ? 0.511 : (0.040975 * $L1) / (1.0 + 0.01765 * $L1);

        $SC = (0.0638 * $C1) / (1.0 + 0.0131 * $C1) + 0.638;

        $C1_4 = $C1 * $C1 * $C1 * $C1;
        $F = sqrt($C1_4 / ($C1_4 + 1900.0));

        $T = $this->calculateT($H1);

        $SH = $SC * ($F * $T + 1.0 - $F);

        return sqrt(
            ($deltaL / ($this->l * $SL)) ** 2
            + ($deltaC / ($this->c * $SC)) ** 2
            + ($deltaH / $SH) ** 2
        );
    }

    public function getName(): string
    {
        return \sprintf('CMC(%.1f:%.1f)', $this->l, $this->c);
    }

    /**
     * Calculate the hue angle in degrees.
     */
    private function calculateHue(float $a, float $b): float
    {
        if (0.0 === $a && 0.0 === $b) {
            return 0.0;
        }

        $hueRadians = atan2($b, $a);
        $hueDegrees = $hueRadians * 180.0 / \M_PI;

        return $hueDegrees >= 0.0 ? $hueDegrees : $hueDegrees + 360.0;
    }

    /**
     * Calculate the 'T' component based on hue.
     */
    private function calculateT(float $H): float
    {
        if ($H >= 164.0 && $H <= 345.0) {
            return 0.56 + abs(0.2 * cos($this->deg2rad($H + 168.0)));
        }

        return 0.36 + abs(0.4 * cos($this->deg2rad($H + 35.0)));
    }

    /**
     * Convert degrees to radians.
     */
    private function deg2rad(float $degrees): float
    {
        return $degrees * \M_PI / 180.0;
    }
}
