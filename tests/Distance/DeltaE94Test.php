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

use PhpColor\Color\Color;
use PhpColor\Color\Distance\ColorDistanceInterface;
use PhpColor\Color\Distance\DeltaE94;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeltaE94::class)]
final class DeltaE94Test extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame('DeltaE94', (new DeltaE94())->getName());
    }

    public function testImplementsInterface(): void
    {
        $calc = new DeltaE94();
        $this->assertInstanceOf(ColorDistanceInterface::class, $calc);
    }

    public function testProducesPositiveDifference(): void
    {
        $calc = new DeltaE94();
        $d = $calc->calculate(Color::parse('red'), Color::parse('blue'));
        $this->assertGreaterThan(0.0, $d);
    }
}
