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

namespace PhpColor\Color\Tests\Palette\Transformer;

use PhpColor\Color\Color;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\Palette\ColorPalette;
use PhpColor\Color\Palette\Transformer\ColorPaletteTransformerInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class ColorPaletteTransformerInterfaceTest extends TestCase
{
    public function testInterfaceCanBeImplemented(): void
    {
        $transformer = new class implements ColorPaletteTransformerInterface {
            public function transform(ColorPalette $palette): ColorPalette
            {
                /** @var array<string, ColorInterface> $colors */
                $colors = [];
                foreach ($palette->all() as $name => $color) {
                    $colors[(string) $name] = $color;
                }

                return ColorPalette::named($colors);
            }
        };

        $palette = ColorPalette::named([
            'primary' => Color::parse('#ff0000'),
        ]);

        $result = $transformer->transform($palette);

        $this->assertInstanceOf(ColorPalette::class, $result);
        $this->assertSame(1, $result->count());
    }
}
