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
use PhpColor\Color\Distance\ColorDistance;
use PhpColor\Color\Exception\InvalidColorException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColorDistance::class)]
final class ColorDistanceTest extends TestCase
{
    public function testCalculateWithInvalidAlgorithm(): void
    {
        $this->expectException(InvalidColorException::class);
        $this->expectExceptionMessage('Unknown color distance algorithm "invalid".');

        ColorDistance::calculate(Color::red(), Color::blue(), 'invalid');
    }

    #[DataProvider('provideAlgorithmAliases')]
    public function testDifferenceWithAliases(string $algorithm): void
    {
        $this->assertGreaterThan(0.0, Color::distance('red', 'blue', $algorithm));
    }

    public static function provideAlgorithmAliases(): iterable
    {
        yield 'CIEDE2000' => ['CIEDE2000'];
        yield 'DE2000' => ['DE2000'];
        yield 'DeltaE94' => ['DeltaE94'];
        yield 'DE94' => ['DE94'];
        yield 'CMC' => ['CMC'];
        yield 'CMC(2:1)' => ['CMC(2:1)'];
        yield 'CMC(1:1)' => ['CMC(1:1)'];
    }

    public function testDifferenceWithCustomCalculator(): void
    {
        $calc = new Ciede2000(1.0, 1.0, 1.0);
        $this->assertGreaterThan(0.0, Color::distance('red', 'blue', $calc));
    }

    public function testDistanceDefaultAcceptsStrings(): void
    {
        $d = Color::distance('#ff0000', '#0000ff');
        $this->assertIsFloat($d);
        $this->assertGreaterThan(0.0, $d);
    }
}
