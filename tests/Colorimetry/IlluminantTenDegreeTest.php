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

use PhpColor\Color\Colorimetry\Illuminant;
use PhpColor\Color\Colorimetry\Observer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Illuminant::class)]
final class IlluminantTenDegreeTest extends TestCase
{
    public function testD65TenDegree(): void
    {
        $wp = Illuminant::D65->whitePoint(Observer::TenDegree)->toArray();
        $this->assertEqualsWithDelta(0.94811, $wp[0], 1e-5);
        $this->assertEqualsWithDelta(1.00000, $wp[1], 1e-6);
        $this->assertEqualsWithDelta(1.07304, $wp[2], 1e-5);

        // Ensure it differs from 2°
        $wp2 = Illuminant::D65->whitePoint(Observer::TwoDegree)->toArray();
        $this->assertNotEquals($wp2[0], $wp[0]);
        $this->assertNotEquals($wp2[2], $wp[2]);
    }

    public function testD50TenDegree(): void
    {
        $wp = Illuminant::D50->whitePoint(Observer::TenDegree)->toArray();
        $this->assertEqualsWithDelta(0.96720, $wp[0], 1e-5);
        $this->assertEqualsWithDelta(1.00000, $wp[1], 1e-6);
        $this->assertEqualsWithDelta(0.81427, $wp[2], 1e-5);
    }

    public function testETenDegree(): void
    {
        $wp = Illuminant::E->whitePoint(Observer::TenDegree)->toArray();
        $this->assertSame(1.0, $wp[0]);
        $this->assertSame(1.0, $wp[1]);
        $this->assertSame(1.0, $wp[2]);
    }

    public function testF2TenDegree(): void
    {
        $wp = Illuminant::F2->whitePoint(Observer::TenDegree)->toArray();
        $this->assertEqualsWithDelta(0.99001, $wp[0], 1e-5);
        $this->assertEqualsWithDelta(1.00000, $wp[1], 1e-6);
        $this->assertEqualsWithDelta(0.63197, $wp[2], 1e-5);
    }

    public function testF7TenDegree(): void
    {
        $wp = Illuminant::F7->whitePoint(Observer::TenDegree)->toArray();
        $this->assertEqualsWithDelta(0.95792, $wp[0], 1e-5);
        $this->assertEqualsWithDelta(1.00000, $wp[1], 1e-6);
        $this->assertEqualsWithDelta(1.07655, $wp[2], 1e-5);
    }

    public function testF11TenDegree(): void
    {
        $wp = Illuminant::F11->whitePoint(Observer::TenDegree)->toArray();
        $this->assertEqualsWithDelta(1.00966, $wp[0], 1e-5);
        $this->assertEqualsWithDelta(1.00000, $wp[1], 1e-6);
        $this->assertEqualsWithDelta(0.64370, $wp[2], 1e-5);
    }
}
