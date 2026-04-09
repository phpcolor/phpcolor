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

namespace PhpColor\Color\Tests\Distance;

use PhpColor\Color\Distance\ColorDistance;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColorDistance::class)]
final class ColorDistanceDeltaETest extends TestCase
{
    public function testDeltaEFacadeReturnsPositiveFloat(): void
    {
        $a = new SrgbColor(1.0, 0.0, 0.0);
        $b = new SrgbColor(0.0, 0.0, 1.0);

        $d = ColorDistance::deltaE($a, $b);

        $this->assertIsFloat($d);
        $this->assertGreaterThan(0.0, $d);
    }
}
