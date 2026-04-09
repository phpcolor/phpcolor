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

use PhpColor\Color\Colorimetry\Adaptation\CAT16;
use PhpColor\Color\Colorimetry\Illuminant;
use PhpColor\Color\Colorimetry\WhitePoint;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CAT16::class)]
final class CAT16AdaptationTest extends TestCase
{
    public function testRoundTripD65ToD50AndBack(): void
    {
        $src = Illuminant::D65->whitePoint();
        $dst = Illuminant::D50->whitePoint();

        $xyz = [0.31, 0.41, 0.19];

        $toD50 = CAT16::adaptXYZ($xyz, $src, $dst);
        $back = CAT16::adaptXYZ($toD50, $dst, $src);

        $this->assertEqualsWithDelta($xyz[0], $back[0], 1e-6);
        $this->assertEqualsWithDelta($xyz[1], $back[1], 1e-6);
        $this->assertEqualsWithDelta($xyz[2], $back[2], 1e-6);
    }

    /**
     * Test that adaptXYZ properly returns the same XYZ values when source and destination white points are the same.
     */
    public function testAdaptXYZSameWhitePoints(): void
    {
        // Arrange
        $xyz = [0.5, 0.4, 0.3];
        $whitePoint = new WhitePoint(0.95047, 1.00000, 1.08883); // D65 standard white point

        // Act
        $adaptedXYZ = CAT16::adaptXYZ($xyz, $whitePoint, $whitePoint);

        // Assert
        $this->assertEquals($xyz, $adaptedXYZ);
    }

    /**
     * Test that adaptXYZ properly adapts XYZ values between two different white points.
     */
    public function testAdaptXYZDifferentWhitePoints(): void
    {
        // Arrange
        $xyz = [0.5, 0.4, 0.3];
        $srcWhitePoint = new WhitePoint(0.95047, 1.00000, 1.08883); // D65
        $dstWhitePoint = new WhitePoint(1.09850, 1.00000, 0.35585); // D50

        // Act
        $adaptedXYZ = CAT16::adaptXYZ($xyz, $srcWhitePoint, $dstWhitePoint);

        // Assert
        $this->assertIsArray($adaptedXYZ);
        $this->assertCount(3, $adaptedXYZ);
        $this->assertNotEquals($xyz, $adaptedXYZ); // Adapted values should differ
    }

    public function invalidXYZInputs(): array
    {
        return [
            'empty array' => [[]],
            'only one value' => [[0.5]],
            'two values' => [[0.5, 0.4]],
            'four values' => [[0.5, 0.4, 0.3, 0.2]],
            'non-numeric values' => [['foo', 'bar', 'baz']],
        ];
    }
}
