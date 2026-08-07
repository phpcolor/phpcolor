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
 * Color spaces supported for gradient interpolation.
 */
enum InterpolationSpace: string
{
    /**
     * Oklab color space.
     */
    case Oklab = 'oklab';

    /**
     * sRGB color space.
     */
    case Srgb = 'srgb';

    /**
     * Linear-light sRGB color space.
     */
    case SrgbLinear = 'srgb-linear';
}
