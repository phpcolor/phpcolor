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

use PhpColor\Color\ColorInterface;
use PhpColor\Color\Exception\ParseException;
use PhpColor\Color\OklabColor;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(OklabColor::class)]
final class OklabColorTest extends ColorTestCase
{
    protected function createColor(): ColorInterface
    {
        return new OklabColor(0.5, 0.1, -0.1, 0.8);
    }

    protected function getExpectedColorClass(): string
    {
        return OklabColor::class;
    }

    public static function provideColorSamples(): iterable
    {
        // color, hex, hexWithAlpha, css
        yield 'red' => [
            new OklabColor(0.628, 0.225, 0.126),
            '#ff0000',
            '#ff0000ff',
            'oklab(0.628 0.225 0.126)',
        ];
        yield 'translucent blue' => [
            new OklabColor(0.452, -0.032, -0.312, 0.5),
            '#0200ff',
            '#0200ff80',
            'oklab(0.452 -0.032 -0.312 / 0.5)',
        ];
    }

    public static function provideFromInputs(): iterable
    {
        yield [new SrgbColor(1.0, 0.0, 0.0)];
        yield ['#ff0000'];
        yield ['oklab(0.7 0.1 0.05)'];
    }

    public static function provideInvalidCssOutputSpaces(): array
    {
        return [
            ['lab'],
            ['lch'],
            ['oklch'],
            ['display-p3'],
            ['xyz'],
            ['a98-rgb'],
            ['rec2020'],
            ['prophoto-rgb'],
            ['hsl'],
            ['color'],
        ];
    }

    public function testAlphaIsClamped(): void
    {
        $oklab1 = new OklabColor(0.5, 0.0, 0.0, -0.1);
        $this->assertSame(0.0, $oklab1->alpha);

        $oklab2 = new OklabColor(0.5, 0.0, 0.0, 1.5);
        $this->assertSame(1.0, $oklab2->alpha);
    }

    public function testChannelGetters(): void
    {
        $c = new OklabColor(0.6, 0.1, -0.2, 0.9);
        $this->assertSame(0.6, $c->getLightness());
        $this->assertSame(0.1, $c->getA());
        $this->assertSame(-0.2, $c->getB());
    }

    public function testConstruction(): void
    {
        $oklab = new OklabColor(0.5, 0.1, -0.1, 0.8);

        $this->assertSame(0.5, $oklab->l);
        $this->assertSame(0.1, $oklab->a);
        $this->assertSame(-0.1, $oklab->b);
        $this->assertSame(0.8, $oklab->alpha);
    }

    public function testConversionToOklab(): void
    {
        $oklab = new OklabColor(0.7, 0.1, 0.05);
        $converted = $oklab->to('oklab');

        $this->assertSame($oklab, $converted);
    }

    public function testCssOutputInOklabSpace(): void
    {
        $color = $this->createColor();
        $css = $color->toCss('oklab');
        $this->assertStringStartsWith('oklab(', $css);
    }

    public function testFromSrgbConversion(): void
    {
        $srgb = new SrgbColor(1.0, 0.0, 0.0); // Red
        $oklab = OklabColor::fromSrgb($srgb);

        $this->assertInstanceOf(OklabColor::class, $oklab);
        $this->assertGreaterThan(0.5, $oklab->l); // Should have reasonable lightness
        $this->assertGreaterThan(0.0, $oklab->a); // Should have positive a (red direction)
    }

    public function testLightnessIsClamped(): void
    {
        $oklab1 = new OklabColor(-0.1, 0.0, 0.0);
        $this->assertSame(0.0, $oklab1->l);

        $oklab2 = new OklabColor(1.5, 0.0, 0.0);
        $this->assertSame(1.0, $oklab2->l);
    }

    #[DataProvider('provideParseCases')]
    public function testParse(string $input, ?OklabColor $expected, ?string $expectedException = null): void
    {
        if (null !== $expectedException) {
            $this->expectException(ParseException::class);
            $this->expectExceptionMessage($expectedException);
        }

        if ($expected instanceof OklabColor) {
            $c = OklabColor::parse($input);
            $this->assertInstanceOf(OklabColor::class, $c);
            $this->assertEqualsWithDelta($expected->l, $c->l, 0.0001);
            $this->assertEqualsWithDelta($expected->a, $c->a, 0.0001);
            $this->assertEqualsWithDelta($expected->b, $c->b, 0.0001);
            $this->assertEqualsWithDelta($expected->alpha, $c->alpha, 0.0001);
        } else {
            OklabColor::parse($input); // should throw
        }
    }

    public static function provideParseCases(): iterable
    {
        yield 'space-separated' => ['oklab(0.7 0.1 0.05)', new OklabColor(0.7, 0.1, 0.05, 1.0)];
        yield 'with alpha' => ['oklab(0.7 0.1 0.05 / 0.8)', new OklabColor(0.7, 0.1, 0.05, 0.8)];
        yield 'comma syntax' => ['oklab(0.7,0.1,0.05,0.5)', new OklabColor(0.7, 0.1, 0.05, 0.5)];
        yield 'percent L' => ['oklab(50% 0.1 0.05)', new OklabColor(0.5, 0.1, 0.05, 1.0)];
        yield 'too few params' => ['oklab(0.7 0.1)', null, 'Cannot parse color "oklab(0.7 0.1)".'];
        yield 'malformed' => ['oklab(0.7 0.1 0.05', null, 'Cannot parse color "oklab(0.7 0.1 0.05".'];
    }

    public function testRoundTripConversion(): void
    {
        $originalSrgb = new SrgbColor(0.5, 0.7, 0.2, 0.8);
        $oklab = OklabColor::fromSrgb($originalSrgb);
        $convertedSrgb = $oklab->toSrgb();

        // Allow small tolerance for floating point precision
        $tolerance = 0.01;
        $this->assertEqualsWithDelta($originalSrgb->r, $convertedSrgb->r, $tolerance);
        $this->assertEqualsWithDelta($originalSrgb->g, $convertedSrgb->g, $tolerance);
        $this->assertEqualsWithDelta($originalSrgb->b, $convertedSrgb->b, $tolerance);
        $this->assertEqualsWithDelta($originalSrgb->a, $convertedSrgb->a, $tolerance);
    }

    public function testDefaultAlpha(): void
    {
        $color = new OklabColor(0.7, 0.1, 0.05);
        $this->assertEqualsWithDelta(1.0, $color->alpha, 0.01);
    }

    public function testGetAlpha(): void
    {
        $color = new OklabColor(0.7, 0.1, 0.05, 0.8);
        $this->assertEqualsWithDelta(0.8, $color->getAlpha(), 0.01);
    }

    #[\Override]
    public function testGetChannels(): void
    {
        $color = new OklabColor(0.7, 0.1, 0.05, 0.8);
        $channels = $color->getChannels();

        $this->assertArrayHasKey('l', $channels);
        $this->assertArrayHasKey('a', $channels);
        $this->assertArrayHasKey('b', $channels);
        $this->assertEqualsWithDelta(0.7, $channels['l'], 0.01);
        $this->assertEqualsWithDelta(0.1, $channels['a'], 0.01);
        $this->assertEqualsWithDelta(0.05, $channels['b'], 0.01);
    }

    #[\Override]
    public function testToHex(): void
    {
        $color = new OklabColor(0.7, 0.1, 0.05, 1.0);
        $hex = $color->toHex();

        $this->assertStringStartsWith('#', $hex);
        $this->assertSame(7, \strlen($hex));
    }

    public function testToHexWithAlpha(): void
    {
        $color = new OklabColor(0.7, 0.1, 0.05, 0.5);
        $hex = $color->toHex(true);

        $this->assertStringStartsWith('#', $hex);
        $this->assertSame(9, \strlen($hex));
    }

    public function testToSrgbAndBack(): void
    {
        $original = new OklabColor(0.7, 0.1, 0.05, 0.9);
        $srgb = $original->toSrgb();
        $back = OklabColor::fromSrgb($srgb);

        $this->assertEqualsWithDelta($original->l, $back->l, 0.01);
        $this->assertEqualsWithDelta($original->a, $back->a, 0.01);
        $this->assertEqualsWithDelta($original->b, $back->b, 0.01);
        $this->assertEqualsWithDelta($original->alpha, $back->alpha, 0.01);
    }
}
