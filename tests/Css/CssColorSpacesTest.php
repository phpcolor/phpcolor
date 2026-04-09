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

namespace PhpColor\Color\Tests\Css;

use PhpColor\Color\A98RgbColor;
use PhpColor\Color\Css\CssColorSpaces;
use PhpColor\Color\DisplayP3Color;
use PhpColor\Color\LabColor;
use PhpColor\Color\LchColor;
use PhpColor\Color\LinearSrgbColor;
use PhpColor\Color\OklabColor;
use PhpColor\Color\OklchColor;
use PhpColor\Color\ProPhotoColor;
use PhpColor\Color\Rec2020Color;
use PhpColor\Color\SrgbColor;
use PhpColor\Color\XyzColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CssColorSpaces::class)]
final class CssColorSpacesTest extends TestCase
{
    #[DataProvider('spaceProvider')]
    public function testBuildersProduceExpectedClasses(string $space, array $channels, string $expectedClass): void
    {
        $builder = CssColorSpaces::builderFor($space);
        $this->assertIsCallable($builder, "No builder for space: $space");
        $color = $builder($channels, 1.0);
        $this->assertInstanceOf($expectedClass, $color);
    }

    public static function spaceProvider(): iterable
    {
        yield 'srgb canonical' => ['srgb', ['r' => 1.0, 'g' => 0.0, 'b' => 0.0], SrgbColor::class];
        yield 'srgb-linear' => ['srgb-linear', ['r' => 1.0, 'g' => 0.0, 'b' => 0.0], LinearSrgbColor::class];
        yield 'rgb alias' => ['rgb', ['r' => 1.0, 'g' => 0.0, 'b' => 0.0], SrgbColor::class];
        yield 'display-p3' => ['display-p3', ['r' => 1.0, 'g' => 0.0, 'b' => 0.0], DisplayP3Color::class];
        yield 'xyz canonical' => ['xyz', ['r' => 0.1, 'g' => 0.2, 'b' => 0.3], XyzColor::class];
        yield 'xyz-d65' => ['xyz-d65', ['r' => 0.1, 'g' => 0.2, 'b' => 0.3], XyzColor::class];
        yield 'rec2020' => ['rec2020', ['r' => 0.1, 'g' => 0.2, 'b' => 0.3], Rec2020Color::class];
        yield 'prophoto-rgb' => ['prophoto-rgb', ['r' => 0.1, 'g' => 0.2, 'b' => 0.3], ProPhotoColor::class];
        yield 'a98-rgb' => ['a98-rgb', ['r' => 0.1, 'g' => 0.2, 'b' => 0.3], A98RgbColor::class];
        yield 'oklab' => ['oklab', ['l' => 0.5, 'a' => 0.1, 'b' => 0.2], OklabColor::class];
        yield 'oklch' => ['oklch', ['l' => 0.5, 'c' => 0.2, 'h' => 180], OklchColor::class];
        yield 'lab' => ['lab', ['l' => 50, 'a' => 20, 'b' => -20], LabColor::class];
        yield 'lch' => ['lch', ['l' => 50, 'c' => 30, 'h' => 180], LchColor::class];
    }

    public function testUnsupportedSpaceReturnsNull(): void
    {
        $this->assertNull(CssColorSpaces::builderFor('unsupported-space'));
    }
}
