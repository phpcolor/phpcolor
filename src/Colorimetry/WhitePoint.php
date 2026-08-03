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

namespace PhpColor\Color\Colorimetry;

/**
 * XYZ tristimulus white point value object.
 *
 * Represents a reference white point in the CIE XYZ color space,
 * with the Y component typically normalized to 1.0.
 */
final readonly class WhitePoint
{
    /**
     * Create a white point from its X, Y, and Z tristimulus values.
     */
    public function __construct(
        public float $X,
        public float $Y,
        public float $Z,
    ) {
    }

    /**
     * Get the white point components as a numeric array.
     *
     * @return array{float,float,float}
     */
    public function toArray(): array
    {
        return [$this->X, $this->Y, $this->Z];
    }
}
