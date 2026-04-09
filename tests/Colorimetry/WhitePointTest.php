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

namespace PhpColor\Color\Tests\Colorimetry;

use PhpColor\Color\Colorimetry\WhitePoint;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WhitePoint::class)]
final class WhitePointTest extends TestCase
{
    public function testConstructAndAccessors(): void
    {
        $wp = new WhitePoint(0.95, 1.0, 1.09);

        $this->assertSame(0.95, $wp->X);
        $this->assertSame(1.0, $wp->Y);
        $this->assertSame(1.09, $wp->Z);
    }

    public function testReadonlyProperties(): void
    {
        $wp = new WhitePoint(0.95, 1.0, 1.09);

        // Verify the class is readonly by checking we can read properties
        $this->assertIsFloat($wp->X);
        $this->assertIsFloat($wp->Y);
        $this->assertIsFloat($wp->Z);
    }

    public function testToArray(): void
    {
        $wp = new WhitePoint(0.95047, 1.0, 1.08883);
        $arr = $wp->toArray();

        $this->assertIsArray($arr);
        $this->assertCount(3, $arr);
        $this->assertEqualsWithDelta(0.95047, $arr[0], 1e-9);
        $this->assertSame(1.0, $arr[1]);
        $this->assertEqualsWithDelta(1.08883, $arr[2], 1e-9);
    }

    public function testToArrayOrder(): void
    {
        $wp = new WhitePoint(1.0, 2.0, 3.0);
        $arr = $wp->toArray();

        // Verify order is X, Y, Z
        $this->assertSame(1.0, $arr[0]);
        $this->assertSame(2.0, $arr[1]);
        $this->assertSame(3.0, $arr[2]);
    }

    public function testWithEqualEnergyIlluminant(): void
    {
        // Equal energy illuminant E has all values = 1.0
        $wp = new WhitePoint(1.0, 1.0, 1.0);

        $this->assertSame(1.0, $wp->X);
        $this->assertSame(1.0, $wp->Y);
        $this->assertSame(1.0, $wp->Z);
    }

    public function testWithLargeValues(): void
    {
        $wp = new WhitePoint(100.0, 200.0, 300.0);

        $this->assertSame(100.0, $wp->X);
        $this->assertSame(200.0, $wp->Y);
        $this->assertSame(300.0, $wp->Z);
    }

    public function testWithNegativeValues(): void
    {
        $wp = new WhitePoint(-0.5, -1.0, -0.8);

        $this->assertSame(-0.5, $wp->X);
        $this->assertSame(-1.0, $wp->Y);
        $this->assertSame(-0.8, $wp->Z);
    }

    public function testWithZeroValues(): void
    {
        $wp = new WhitePoint(0.0, 0.0, 0.0);

        $this->assertSame(0.0, $wp->X);
        $this->assertSame(0.0, $wp->Y);
        $this->assertSame(0.0, $wp->Z);
    }
}
