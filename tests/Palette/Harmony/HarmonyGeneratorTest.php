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

namespace PhpColor\Color\Tests\Palette\Harmony;

use PhpColor\Color\Color;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\Palette\Harmony\HarmonyGenerator;
use PhpColor\Color\Palette\Harmony\HarmonyPattern;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HarmonyGenerator::class)]
final class HarmonyGeneratorTest extends TestCase
{
    public function testGenerateAll(): void
    {
        $generator = new HarmonyGenerator();
        foreach (HarmonyPattern::cases() as $pattern) {
            $palette = $generator->generate(Color::red(), $pattern);
            $this->assertCount(\count($pattern->fullAngles()), $palette);
        }
    }

    public function testGenerateCastsNonOklchBaseColor(): void
    {
        // L38: when to('oklch') returns a non-OklchColor, the generator must cast via OklchColor::fromSrgb().
        $srgb = new SrgbColor(0.8, 0.2, 0.1);
        $fake = $this->createStub(ColorInterface::class);
        $fake->method('to')->willReturn($srgb);
        $fake->method('toSrgb')->willReturn($srgb);

        $generator = new HarmonyGenerator();
        $palette = $generator->generate($fake, HarmonyPattern::Complementary);
        $this->assertCount(2, $palette);
    }

    public function testNormalizeHue(): void
    {
        $generator = new HarmonyGenerator();
        $refl = new \ReflectionClass($generator);
        $method = $refl->getMethod('normalizeHue');

        $this->assertEquals(0.0, $method->invoke($generator, 360.0));
        $this->assertEquals(350.0, $method->invoke($generator, -10.0));
        $this->assertEquals(10.0, $method->invoke($generator, 10.0));
    }
}
