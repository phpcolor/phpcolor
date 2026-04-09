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
use PhpColor\Color\Distance\Cmc;
use PhpColor\Color\Distance\ColorDistanceInterface;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Cmc::class)]
final class CmcTest extends TestCase
{
    public function testAcceptabilityPreset(): void
    {
        $calc = Cmc::forAcceptability();
        $this->assertSame('CMC(2.0:1.0)', $calc->getName());
        $this->assertGreaterThan(0.0, $calc->calculate(Color::parse('green'), Color::parse('#00e000')));
    }

    public function testAsymmetry(): void
    {
        $a = Color::parse('red');
        $b = Color::parse('blue');
        $cmc = new Cmc();
        $forward = $cmc->calculate($a, $b);
        $backward = $cmc->calculate($b, $a);
        $this->assertGreaterThan(0.01, abs($forward - $backward));
    }

    public function testCalculateHueHandlesZeroChromas(): void
    {
        $calc = new Cmc();
        // a=b=0 triggers hue=0 branch internally
        $d = $calc->calculateFromLab(50.0, 0.0, 0.0, 50.0, 10.0, 5.0);
        $this->assertIsFloat($d);
        $this->assertGreaterThanOrEqual(0.0, $d);
    }

    public function testIdenticalColors(): void
    {
        $calc = new Cmc();
        $a = new SrgbColor(0.0, 0.0, 1.0, 1.0);
        $b = new SrgbColor(0.0, 0.0, 1.0, 1.0);
        $this->assertEqualsWithDelta(0.0, $calc->calculate($a, $b), 1e-6);
    }

    public function testImplementsInterface(): void
    {
        $calc = new Cmc();
        $this->assertInstanceOf(ColorDistanceInterface::class, $calc);
        $this->assertStringContainsString('CMC', $calc->getName());
    }

    public function testPerceptibilityPreset(): void
    {
        $calc = Cmc::forPerceptibility();
        $this->assertSame('CMC(1.0:1.0)', $calc->getName());
        $this->assertGreaterThan(0.0, $calc->calculate(Color::parse('yellow'), Color::parse('#ffff10')));
    }

    public function testWithCustomWeights(): void
    {
        $calc = new Cmc(1.5, 0.8);
        $this->assertSame('CMC(1.5:0.8)', $calc->getName());
        $this->assertGreaterThan(0.0, $calc->calculate(Color::parse('red'), Color::parse('blue')));
    }
}
