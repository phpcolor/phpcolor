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

use PhpColor\Color\ColorInterface;

/**
 * Interface for color vision deficiency simulators.
 *
 * Implementations of this interface simulate how colors appear to people with
 * different types of color vision deficiencies (color blindness).
 */
interface VisionSimulatorInterface
{
    /**
     * Simulate a color vision deficiency for a given color.
     */
    public function simulate(ColorInterface $color, ?VisionProfile $profile = null): ColorInterface;
}
