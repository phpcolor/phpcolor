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

use PhpColor\Color\Colorimetry\Observer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Observer::class)]
final class ObserverTest extends TestCase
{
    public function testAllCases(): void
    {
        $cases = Observer::cases();

        $this->assertCount(2, $cases);
        $this->assertContains(Observer::TwoDegree, $cases);
        $this->assertContains(Observer::TenDegree, $cases);
    }

    public function testCasesOrder(): void
    {
        $cases = Observer::cases();

        // Verify the order is TwoDegree first (default), then TenDegree
        $this->assertSame(Observer::TwoDegree, $cases[0]);
        $this->assertSame(Observer::TenDegree, $cases[1]);
    }

    public function testDefaultObserverIsTwoDegree(): void
    {
        // By convention, the first case is the default
        $cases = Observer::cases();
        $default = $cases[0];

        $this->assertSame(Observer::TwoDegree, $default);
    }

    public function testEnumEquality(): void
    {
        $obs1 = Observer::TwoDegree;
        $obs2 = Observer::TwoDegree;

        $this->assertSame($obs1, $obs2);
    }

    public function testEnumInequality(): void
    {
        $obs1 = Observer::TwoDegree;
        $obs2 = Observer::TenDegree;

        $this->assertNotSame($obs1, $obs2);
    }

    public function testFromInt(): void
    {
        $twoDeg = Observer::from(2);
        $tenDeg = Observer::from(10);

        $this->assertSame(Observer::TwoDegree, $twoDeg);
        $this->assertSame(Observer::TenDegree, $tenDeg);
    }

    public function testIntRepresentation(): void
    {
        // Verify int values match expected angles
        $this->assertSame(2, Observer::TwoDegree->value);
        $this->assertSame(10, Observer::TenDegree->value);
    }

    public function testTenDegreeValue(): void
    {
        $this->assertSame(10, Observer::TenDegree->value);
    }

    public function testTryFromInvalidInt(): void
    {
        $result = Observer::tryFrom(5);

        $this->assertNull($result);
    }

    public function testTryFromValidInt(): void
    {
        $twoDeg = Observer::tryFrom(2);
        $tenDeg = Observer::tryFrom(10);

        $this->assertSame(Observer::TwoDegree, $twoDeg);
        $this->assertSame(Observer::TenDegree, $tenDeg);
    }

    public function testTwoDegreeValue(): void
    {
        $this->assertSame(2, Observer::TwoDegree->value);
    }
}
