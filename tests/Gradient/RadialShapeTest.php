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

use PhpColor\Color\Gradient\RadialShape;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RadialShape::class)]
final class RadialShapeTest extends TestCase
{
    public function testHasCircleCase(): void
    {
        $this->assertSame('circle', RadialShape::Circle->value);
    }

    public function testHasEllipseCase(): void
    {
        $this->assertSame('ellipse', RadialShape::Ellipse->value);
    }

    public function testHasExactlyTwoCases(): void
    {
        $cases = RadialShape::cases();
        $this->assertCount(2, $cases);
    }

    public function testCasesAreBackedByStrings(): void
    {
        foreach (RadialShape::cases() as $case) {
            $this->assertIsString($case->value);
        }
    }

    public function testCanBeUsedInMatchExpression(): void
    {
        $shape = RadialShape::Circle;

        $result = match ($shape) {
            RadialShape::Circle => 'circle-selected',
            RadialShape::Ellipse => 'ellipse-selected',
        };

        $this->assertSame('circle-selected', $result);
    }

    public function testFromMethodWorks(): void
    {
        $circle = RadialShape::from('circle');
        $ellipse = RadialShape::from('ellipse');

        $this->assertSame(RadialShape::Circle, $circle);
        $this->assertSame(RadialShape::Ellipse, $ellipse);
    }

    public function testFromMethodThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        RadialShape::from('invalid');
    }

    public function testTryFromMethodReturnsNullOnInvalidValue(): void
    {
        $result = RadialShape::tryFrom('invalid');
        $this->assertNull($result);
    }

    public function testTryFromMethodReturnsEnumOnValidValue(): void
    {
        $result = RadialShape::tryFrom('circle');
        $this->assertSame(RadialShape::Circle, $result);
    }

    public function testEnumCasesCanBeCompared(): void
    {
        $circle1 = RadialShape::Circle;
        $circle2 = RadialShape::Circle;
        $ellipse = RadialShape::Ellipse;

        $this->assertTrue($circle1 === $circle2);
        $this->assertFalse($circle1 === $ellipse);
    }
}
