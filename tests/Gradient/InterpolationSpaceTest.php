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

use PhpColor\Color\Gradient\InterpolationSpace;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InterpolationSpace::class)]
final class InterpolationSpaceTest extends TestCase
{
    public function testHasOklabCase(): void
    {
        $this->assertSame('oklab', InterpolationSpace::Oklab->value);
    }

    public function testHasSrgbCase(): void
    {
        $this->assertSame('srgb', InterpolationSpace::Srgb->value);
    }

    public function testHasSrgbLinearCase(): void
    {
        $this->assertSame('srgb-linear', InterpolationSpace::SrgbLinear->value);
    }

    public function testHasExactlyThreeCases(): void
    {
        $cases = InterpolationSpace::cases();
        $this->assertCount(3, $cases);
    }

    public function testCasesAreBackedByStrings(): void
    {
        foreach (InterpolationSpace::cases() as $case) {
            $this->assertIsString($case->value);
        }
    }

    public function testCanBeUsedInMatchExpression(): void
    {
        $space = InterpolationSpace::Oklab;

        $result = match ($space) {
            InterpolationSpace::Oklab => 'oklab-selected',
            InterpolationSpace::Srgb => 'srgb-selected',
            InterpolationSpace::SrgbLinear => 'srgb-linear-selected',
        };

        $this->assertSame('oklab-selected', $result);
    }

    public function testFromMethodWorks(): void
    {
        $oklab = InterpolationSpace::from('oklab');
        $srgb = InterpolationSpace::from('srgb');
        $srgbLinear = InterpolationSpace::from('srgb-linear');

        $this->assertSame(InterpolationSpace::Oklab, $oklab);
        $this->assertSame(InterpolationSpace::Srgb, $srgb);
        $this->assertSame(InterpolationSpace::SrgbLinear, $srgbLinear);
    }

    public function testFromMethodThrowsOnInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        InterpolationSpace::from('invalid');
    }

    public function testTryFromMethodReturnsNullOnInvalidValue(): void
    {
        $result = InterpolationSpace::tryFrom('invalid');
        $this->assertNull($result);
    }

    public function testTryFromMethodReturnsEnumOnValidValue(): void
    {
        $result = InterpolationSpace::tryFrom('oklab');
        $this->assertSame(InterpolationSpace::Oklab, $result);
    }

    public function testEnumCasesCanBeCompared(): void
    {
        $oklab1 = InterpolationSpace::Oklab;
        $oklab2 = InterpolationSpace::Oklab;
        $srgb = InterpolationSpace::Srgb;

        $this->assertTrue($oklab1 === $oklab2);
        $this->assertFalse($oklab1 === $srgb);
    }
}
