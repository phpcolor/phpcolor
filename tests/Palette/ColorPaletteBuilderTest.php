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

namespace PhpColor\Color\Tests\Palette;

use PhpColor\Color\Palette\ColorPalette;
use PhpColor\Color\Palette\ColorPaletteBuilder;
use PhpColor\Color\Palette\Fixer\ContrastFixer;
use PhpColor\Color\Palette\Harmony\HarmonyPattern;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColorPaletteBuilder::class)]
#[CoversClass(ColorPalette::class)]
final class ColorPaletteBuilderTest extends TestCase
{
    public function testAddAndBuild(): void
    {
        $builder = ColorPalette::builder();
        $builder->add('#ff0000')->add('#00ff00');
        $palette = $builder->build();
        $this->assertCount(2, $palette);
    }

    public function testAddAll(): void
    {
        $builder = new ColorPaletteBuilder('#ff0000');
        $builder->addAll(['#00ff00', 'blue' => '#0000ff']);
        $palette = $builder->build();
        $this->assertCount(3, $palette);
        $this->assertTrue($palette->isNamed());
    }

    public function testHarmony(): void
    {
        $builder = new ColorPaletteBuilder();
        $builder->harmony('#ff0000', HarmonyPattern::Complementary);
        $this->assertCount(2, $builder->build());

        $builder = new ColorPaletteBuilder();
        $builder->harmony('#ff0000', 'triadic');
        $this->assertCount(3, $builder->build());
    }

    public function testShadesAndTints(): void
    {
        $builder = new ColorPaletteBuilder();
        $builder->shades('#ff0000', 3)->tints('#ff0000', 3);
        $this->assertCount(6, $builder->build());
    }

    public function testTransform(): void
    {
        $builder = new ColorPaletteBuilder('#ff0000');
        $builder->transform(static fn ($c) => $c->rotateHue(180));
        $this->assertCount(1, $builder->build());
    }

    public function testFix(): void
    {
        $builder = new ColorPaletteBuilder();
        $builder->add('#000000')->add('#111111');
        $builder->fix(new ContrastFixer());
        $this->assertCount(2, $builder->build());
    }
}
