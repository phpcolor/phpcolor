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

namespace PhpColor\Color\Tests;

use PhpColor\Color\A98RgbColor;
use PhpColor\Color\ColorInterface;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(A98RgbColor::class)]
final class A98RgbColorTest extends ColorTestCase
{
    protected function createColor(): ColorInterface
    {
        return new A98RgbColor(0.6, 0.4, 0.3, 0.5);
    }

    protected function getExpectedColorClass(): string
    {
        return A98RgbColor::class;
    }

    public static function provideColorSamples(): iterable
    {
        yield 'deep red' => [
            new A98RgbColor(0.8, 0.1, 0.1),
            '#ee1313',
            '#ee1313ff',
            'color(a98-rgb 0.8 0.1 0.1)',
        ];
        yield 'bright cyan alpha' => [
            new A98RgbColor(0.2, 0.9, 0.9, 0.3),
            '#00e6e6',
            '#00e6e64d',
            'color(a98-rgb 0.2 0.9 0.9 / 0.3)',
        ];
    }

    public static function provideFromInputs(): iterable
    {
        yield [new SrgbColor(1.0, 0.0, 0.0)];
        yield ['#ff0000'];
        yield ['color(a98-rgb 0.2 0.7 0.4 / 0.8)'];
    }

    public static function provideInvalidCssOutputSpaces(): array
    {
        return [
            ['lab'],
            ['lch'],
            ['oklab'],
            ['oklch'],
            ['display-p3'],
            ['xyz'],
            ['rec2020'],
            ['prophoto-rgb'],
            ['hsl'],
            ['color-srgb'],
        ];
    }

    public function testChannelGetters(): void
    {
        $c = new A98RgbColor(0.11, 0.22, 0.33, 0.44);
        $this->assertSame(0.11, $c->getRed());
        $this->assertSame(0.22, $c->getGreen());
        $this->assertSame(0.33, $c->getBlue());
    }

    public function testCssOutputInA98RgbSpace(): void
    {
        $color = $this->createColor();
        $css = $color->toCss('a98-rgb');
        $this->assertStringStartsWith('color(a98-rgb', $css);
    }

    public function testFromSrgbConversion(): void
    {
        $srgb = new SrgbColor(0.4, 0.5, 0.2);
        $a98 = A98RgbColor::fromSrgb($srgb);

        $this->assertInstanceOf(A98RgbColor::class, $a98);
        $this->assertGreaterThan(0.0, $a98->r);
        $this->assertGreaterThan(0.0, $a98->g);
        $this->assertGreaterThan(0.0, $a98->b);
    }

    public function testRoundTripConversion(): void
    {
        $original = new SrgbColor(0.2, 0.7, 0.5, 0.9);
        $a98 = A98RgbColor::fromSrgb($original);
        $roundTrip = $a98->toSrgb();

        $tolerance = 0.01;
        $this->assertEqualsWithDelta($original->r, $roundTrip->r, $tolerance);
        $this->assertEqualsWithDelta($original->g, $roundTrip->g, $tolerance);
        $this->assertEqualsWithDelta($original->b, $roundTrip->b, $tolerance);
        $this->assertEqualsWithDelta($original->a, $roundTrip->a, $tolerance);
    }
}
