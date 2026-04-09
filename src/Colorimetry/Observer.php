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
 * Standard CIE observer angles.
 *
 * Defines the standard observer angles used in colorimetry for calculating
 * XYZ tristimulus values.
 */
enum Observer: int
{
    /**
     * CIE 1931 2° standard observer.
     */
    case TwoDegree = 2;

    /**
     * CIE 1964 10° supplementary observer.
     */
    case TenDegree = 10;
}
