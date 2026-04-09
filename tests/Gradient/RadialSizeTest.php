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

namespace PhpColor\Color\Tests\Gradient;

use PhpColor\Color\Gradient\RadialSize;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RadialSize::class)]
final class RadialSizeTest extends TestCase
{
    public function testHasClosestCornerCase(): void
    {
        $this->assertSame('closest-corner', RadialSize::ClosestCorner->value);
    }

    public function testHasClosestSideCase(): void
    {
        $this->assertSame('closest-side', RadialSize::ClosestSide->value);
    }

    public function testHasFarthestCornerCase(): void
    {
        $this->assertSame('farthest-corner', RadialSize::FarthestCorner->value);
    }

    public function testHasFarthestSideCase(): void
    {
        $this->assertSame('farthest-side', RadialSize::FarthestSide->value);
    }

    public function testHasExactlyFourCases(): void
    {
        $cases = RadialSize::cases();
        $this->assertCount(4, $cases);
    }

    public function testCasesAreBackedByStrings(): void
    {
        foreach (RadialSize::cases() as $case) {
            $this->assertIsString($case->value);
        }
    }

    public function testCanBeUsedInMatchExpression(): void
    {
        $size = RadialSize::FarthestCorner;

        $result = match ($size) {
            RadialSize::ClosestCorner => 'closest-corner',
            RadialSize::ClosestSide => 'closest-side',
            RadialSize::FarthestCorner => 'farthest-corner',
            RadialSize::FarthestSide => 'farthest-side',
        };

        $this->assertSame('farthest-corner', $result);
    }

    public function testFromMethodWorks(): void
    {
        $closestCorner = RadialSize::from('closest-corner');
        $closestSide = RadialSize::from('closest-side');
        $farthestCorner = RadialSize::from('farthest-corner');
        $farthestSide = RadialSize::from('farthest-side');

        $this->assertSame(RadialSize::ClosestCorner, $closestCorner);
        $this->assertSame(RadialSize::ClosestSide, $closestSide);
        $this->assertSame(RadialSize::FarthestCorner, $farthestCorner);
        $this->assertSame(RadialSize::FarthestSide, $farthestSide);
    }

    public function testFromMethodThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        RadialSize::from('invalid');
    }

    public function testTryFromMethodReturnsNullOnInvalidValue(): void
    {
        $result = RadialSize::tryFrom('invalid');
        $this->assertNull($result);
    }

    public function testTryFromMethodReturnsEnumOnValidValue(): void
    {
        $result = RadialSize::tryFrom('farthest-corner');
        $this->assertSame(RadialSize::FarthestCorner, $result);
    }

    public function testEnumCasesCanBeCompared(): void
    {
        $far1 = RadialSize::FarthestCorner;
        $far2 = RadialSize::FarthestCorner;
        $close = RadialSize::ClosestCorner;

        $this->assertTrue($far1 === $far2);
        $this->assertFalse($far1 === $close);
    }
}
