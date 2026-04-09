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

namespace PhpColor\Color\Gradient;

/**
 * Size keywords supported for radial gradients.
 */
enum RadialSize: string
{
    /**
     * Sized to meet the closest corner.
     */
    case ClosestCorner = 'closest-corner';

    /**
     * Sized to meet the closest side.
     */
    case ClosestSide = 'closest-side';

    /**
     * Sized to meet the farthest corner.
     */
    case FarthestCorner = 'farthest-corner';

    /**
     * Sized to meet the farthest side.
     */
    case FarthestSide = 'farthest-side';
}
