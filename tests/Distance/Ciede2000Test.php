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
use PhpColor\Color\Distance\Ciede2000;
use PhpColor\Color\Distance\ColorDistanceInterface;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Ciede2000::class)]
final class Ciede2000Test extends TestCase
{
    public function testIdenticalColors(): void
    {
        $calc = new Ciede2000();
        $a = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $b = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $this->assertEqualsWithDelta(0.0, $calc->calculate($a, $b), 1e-6);
    }

    public function testImplementsInterface(): void
    {
        $calc = new Ciede2000();
        $this->assertInstanceOf(ColorDistanceInterface::class, $calc);
        $this->assertSame('CIEDE2000', $calc->getName());
    }

    public function testIncreaseWithDistance(): void
    {
        $calc = new Ciede2000();
        $base = Color::parse('#808080');
        $near = Color::parse('#888888');
        $far = Color::parse('#ffffff');
        $this->assertLessThan($calc->calculate($base, $far), $calc->calculate($base, $near));
    }

    public function testMeanHuePrimeWrapsWhenSumAtLeast360(): void
    {
        $ref = new \ReflectionClass(Ciede2000::class);
        $method = $ref->getMethod('calculateMeanHuePrime');
        // C1', C2' non-zero; h1'=350, h2'=10 => diff=340>180, sum=360 => branch (h1+h2-360)/2 = 0
        $value = $method->invoke(null, 1.0, 1.0, 350.0, 10.0);
        $this->assertEqualsWithDelta(0.0, $value, 1e-12);
    }

    #[DataProvider('provideReferenceValues')]
    public function testReferenceValues(float $L1, float $a1, float $b1, float $L2, float $a2, float $b2, float $expected): void
    {
        $calc = new Ciede2000();
        $res = $calc->calculateFromLab($L1, $a1, $b1, $L2, $a2, $b2);
        $this->assertEqualsWithDelta($expected, $res, 0.01);
    }

    public static function provideReferenceValues(): iterable
    {
        yield 'test 1' => [50.0, 2.6772, -79.7751, 50.0, 0.0, -82.7485, 2.0425];
        yield 'test 2' => [50.0, 3.1571, -77.2803, 50.0, 0.0, -82.7485, 2.8615];
        yield 'test 3' => [50.0, 2.8361, -74.0200, 50.0, 0.0, -82.7485, 3.4412];
        yield 'test 4' => [50.0, -1.3802, -84.2814, 50.0, 0.0, -82.7485, 1.0000];
        yield 'test 5' => [50.0, -1.1848, -84.8006, 50.0, 0.0, -82.7485, 1.0000];
        yield 'test 6' => [50.0, -0.9009, -85.5211, 50.0, 0.0, -82.7485, 1.0000];
        yield 'black to white' => [0.0, 0.0, 0.0, 100.0, 0.0, 0.0, 100.0];
        yield 'red vs blue' => [53.23, 80.11, 67.22, 32.30, 79.19, -107.86, 52.88];
    }

    public function testSymmetry(): void
    {
        $a = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $b = new SrgbColor(0.0, 0.0, 1.0, 1.0);
        $calc = new Ciede2000();
        $this->assertEqualsWithDelta($calc->calculate($a, $b), $calc->calculate($b, $a), 1e-10);
    }

    public function testWithCustomWeights(): void
    {
        $calc = new Ciede2000(2.0, 1.0, 1.0);
        $d = $calc->calculate(Color::parse('red'), Color::parse('blue'));
        $this->assertGreaterThan(0.0, $d);
    }

    public function testWithSrgbColors(): void
    {
        $a = new SrgbColor(1.0, 0.0, 0.0, 1.0);
        $b = new SrgbColor(0.0, 0.0, 1.0, 1.0);
        $this->assertGreaterThan(0.0, (new Ciede2000())->calculate($a, $b));
    }
}
