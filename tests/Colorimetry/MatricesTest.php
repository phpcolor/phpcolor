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

use PhpColor\Color\Colorimetry\Matrices;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrices::class)]
final class MatricesTest extends TestCase
{
    /**
     * Test that all matrix pairs satisfy the identity property: M × M⁻¹ ≈ I.
     */
    #[DataProvider('matrixPairsProvider')]
    public function testMatrixInverseIdentity(string $name, array $forward, array $inverse, float $tolerance): void
    {
        $this->assertTrue(
            Matrices::verifyInverse($forward, $inverse, $tolerance),
            \sprintf('Matrix pair %s does not satisfy M × M⁻¹ ≈ I within tolerance %e', $name, $tolerance)
        );
    }

    /**
     * Test that inverse matrices satisfy the commutative property: M × M⁻¹ = M⁻¹ × M ≈ I.
     */
    #[DataProvider('matrixPairsProvider')]
    public function testMatrixInverseCommutative(string $name, array $forward, array $inverse, float $tolerance): void
    {
        // Test M⁻¹ × M also produces identity
        $this->assertTrue(
            Matrices::verifyInverse($inverse, $forward, $tolerance),
            \sprintf('Matrix pair %s does not satisfy M⁻¹ × M ≈ I (commutativity)', $name)
        );
    }

    /**
     * Provide all matrix pairs for inverse testing.
     *
     * @return iterable<string, array{string, array, array, float}>
     */
    public static function matrixPairsProvider(): iterable
    {
        yield 'sRGB ↔ XYZ D65' => [
            'sRGB ↔ XYZ D65',
            Matrices::SRGB_TO_XYZ_D65,
            Matrices::XYZ_D65_TO_SRGB,
            1e-6,  // High precision for fundamental sRGB matrices
        ];

        yield 'sRGB ↔ Display P3 direct' => [
            'sRGB ↔ Display P3 direct',
            Matrices::SRGB_LINEAR_TO_DISPLAY_P3_LINEAR,
            Matrices::DISPLAY_P3_LINEAR_TO_SRGB_LINEAR,
            2e-2,  // Relaxed for approximate inverses (rounding in original implementation)
        ];

        yield 'Rec.2020 ↔ XYZ D65' => [
            'Rec.2020 ↔ XYZ D65',
            Matrices::REC2020_TO_XYZ_D65,
            Matrices::XYZ_D65_TO_REC2020,
            1e-6,  // High precision for wide-gamut D65 space
        ];

        yield 'Adobe RGB (1998) ↔ XYZ D65' => [
            'Adobe RGB (1998) ↔ XYZ D65',
            Matrices::A98_RGB_TO_XYZ_D65,
            Matrices::XYZ_D65_TO_A98_RGB,
            1e-3,  // Relaxed slightly for numerical precision
        ];

        yield 'ProPhoto RGB ↔ XYZ D50' => [
            'ProPhoto RGB ↔ XYZ D50',
            Matrices::PROPHOTO_RGB_TO_XYZ_D50,
            Matrices::XYZ_D50_TO_PROPHOTO_RGB,
            1e-3,  // Relaxed for D50 space (numerical precision)
        ];
    }

    /**
     * Test individual matrix constants exist and have correct dimensions.
     */
    #[DataProvider('allMatricesProvider')]
    public function testMatrixStructure(string $constantName, array $matrix): void
    {
        $this->assertIsArray($matrix, \sprintf('%s must be an array', $constantName));
        $this->assertCount(3, $matrix, \sprintf('%s must have 3 rows', $constantName));

        foreach ($matrix as $rowIndex => $row) {
            $this->assertIsArray($row, \sprintf('%s row %d must be an array', $constantName, $rowIndex));
            $this->assertCount(3, $row, \sprintf('%s row %d must have 3 columns', $constantName, $rowIndex));

            foreach ($row as $colIndex => $value) {
                $this->assertIsFloat($value, \sprintf(
                    '%s element [%d][%d] must be a float',
                    $constantName,
                    $rowIndex,
                    $colIndex
                ));
                $this->assertTrue(is_finite($value), \sprintf(
                    '%s element [%d][%d] must be finite (not NaN or Inf)',
                    $constantName,
                    $rowIndex,
                    $colIndex
                ));
            }
        }
    }

    /**
     * Provide all individual matrices for structure testing.
     *
     * @return iterable<string, array{string, array}>
     */
    public static function allMatricesProvider(): iterable
    {
        yield 'SRGB_TO_XYZ_D65' => ['SRGB_TO_XYZ_D65', Matrices::SRGB_TO_XYZ_D65];
        yield 'XYZ_D65_TO_SRGB' => ['XYZ_D65_TO_SRGB', Matrices::XYZ_D65_TO_SRGB];
        yield 'SRGB_LINEAR_TO_DISPLAY_P3_LINEAR' => ['SRGB_LINEAR_TO_DISPLAY_P3_LINEAR', Matrices::SRGB_LINEAR_TO_DISPLAY_P3_LINEAR];
        yield 'DISPLAY_P3_LINEAR_TO_SRGB_LINEAR' => ['DISPLAY_P3_LINEAR_TO_SRGB_LINEAR', Matrices::DISPLAY_P3_LINEAR_TO_SRGB_LINEAR];
        yield 'REC2020_TO_XYZ_D65' => ['REC2020_TO_XYZ_D65', Matrices::REC2020_TO_XYZ_D65];
        yield 'XYZ_D65_TO_REC2020' => ['XYZ_D65_TO_REC2020', Matrices::XYZ_D65_TO_REC2020];
        yield 'A98_RGB_TO_XYZ_D65' => ['A98_RGB_TO_XYZ_D65', Matrices::A98_RGB_TO_XYZ_D65];
        yield 'XYZ_D65_TO_A98_RGB' => ['XYZ_D65_TO_A98_RGB', Matrices::XYZ_D65_TO_A98_RGB];
        yield 'PROPHOTO_RGB_TO_XYZ_D50' => ['PROPHOTO_RGB_TO_XYZ_D50', Matrices::PROPHOTO_RGB_TO_XYZ_D50];
        yield 'XYZ_D50_TO_PROPHOTO_RGB' => ['XYZ_D50_TO_PROPHOTO_RGB', Matrices::XYZ_D50_TO_PROPHOTO_RGB];
    }

    /**
     * Test that sRGB matrices are defined with expected precision.
     */
    public function testSrgbMatricesPrecision(): void
    {
        // Test specific known values from sRGB specification
        $srgbToXyz = Matrices::SRGB_TO_XYZ_D65;

        // Verify specific elements (sRGB spec IEC 61966-2-1:1999)
        $this->assertEqualsWithDelta(0.4124564, $srgbToXyz[0][0], 1e-7, 'sRGB→XYZ red X coefficient');
        $this->assertEqualsWithDelta(0.2126729, $srgbToXyz[1][0], 1e-7, 'sRGB→XYZ red Y coefficient (luminance)');
        $this->assertEqualsWithDelta(0.0193339, $srgbToXyz[2][0], 1e-7, 'sRGB→XYZ red Z coefficient');
    }

    /**
     * Test that ProPhoto RGB uses D50 illuminant (different from others).
     */
    public function testProPhotoUsesD50(): void
    {
        // ProPhoto RGB matrices should be different from other spaces
        // because they use D50 instead of D65

        $this->assertNotEquals(
            Matrices::SRGB_TO_XYZ_D65,
            Matrices::PROPHOTO_RGB_TO_XYZ_D50,
            'ProPhoto matrix should be different from sRGB (uses D50 vs D65)'
        );

        // The constant name itself should indicate D50
        $reflection = new \ReflectionClass(Matrices::class);
        $constants = $reflection->getConstants();

        $this->assertArrayHasKey('PROPHOTO_RGB_TO_XYZ_D50', $constants);
        $this->assertArrayHasKey('XYZ_D50_TO_PROPHOTO_RGB', $constants);
    }

    /**
     * Test that Display P3 direct conversion matrices exist.
     */
    public function testDisplayP3DirectConversion(): void
    {
        // Display P3 should have direct sRGB conversion matrices
        $this->assertNotEmpty(Matrices::SRGB_LINEAR_TO_DISPLAY_P3_LINEAR);
        $this->assertNotEmpty(Matrices::DISPLAY_P3_LINEAR_TO_SRGB_LINEAR);

        // Verify they are approximate inverses (tolerance relaxed due to rounding)
        $this->assertTrue(
            Matrices::verifyInverse(
                Matrices::SRGB_LINEAR_TO_DISPLAY_P3_LINEAR,
                Matrices::DISPLAY_P3_LINEAR_TO_SRGB_LINEAR,
                2e-2  // Relaxed tolerance for approximate inverses
            ),
            'sRGB↔P3 direct conversion matrices must be approximate inverses'
        );
    }

    /**
     * Test that Rec.2020 matrices represent a wide gamut.
     */
    public function testRec2020WideGamut(): void
    {
        // Rec.2020 primaries are more extreme than sRGB
        // We can verify by checking that the transformation matrix values differ significantly

        $this->assertNotEquals(
            Matrices::SRGB_TO_XYZ_D65,
            Matrices::REC2020_TO_XYZ_D65,
            'Rec.2020 matrix should differ from sRGB (wider gamut)'
        );
    }

    public function testVerifyInverseDetectsBadMatrix(): void
    {
        $identity = [
            [1.0, 0.0, 0.0],
            [0.0, 1.0, 0.0],
            [0.0, 0.0, 1.0],
        ];
        // Pass identity as forward, but zero matrix as inverse
        $zero = [
            [0.0, 0.0, 0.0],
            [0.0, 0.0, 0.0],
            [0.0, 0.0, 0.0],
        ];

        $this->assertFalse(Matrices::verifyInverse($identity, $zero));
    }

    public function testInstantiation(): void
    {
        $matrices = new Matrices();
        $this->assertInstanceOf(Matrices::class, $matrices);
    }
}
