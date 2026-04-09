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
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Abstract base test case for all color concrete classes.
 *
 * This class provides common test methods that should be implemented
 * by all color space test classes.
 */
abstract class ColorTestCase extends TestCase
{
    abstract protected function createColor(): ColorInterface;

    /**
     * @return class-string<ColorInterface>
     */
    abstract protected function getExpectedColorClass(): string;

    /**
     * @return iterable<string, array{0: ColorInterface, 1: string, 2: string, 3: string}>
     */
    abstract public static function provideColorSamples(): iterable;

    public function testConvertToSelf(): void
    {
        $color = $this->createColor();
        $converted = $color->to($color);
        $this->assertInstanceOf($this->getExpectedColorClass(), $converted);
        $this->assertEquals($color, $converted);
    }

    public function testCssOutputInRgbSpace(): void
    {
        $color = $this->createColor();
        $css = $color->toCss('rgb');
        $this->assertStringStartsWith('rgb(', $css);
    }

    public function testCssOutputInSrgbSpace(): void
    {
        $color = $this->createColor();
        $css = $color->toCss('srgb');
        $this->assertStringStartsWith('rgb(', $css);
    }

    #[DataProvider('provideInvalidCssOutputSpaces')]
    public function testCssOutputWithUnsupportedSpace(string $space): void
    {
        $color = $this->createColor();

        $this->expectException(InvalidColorException::class);
        $this->expectExceptionMessage('CSS output for "'.$space.'" is not supported yet.');

        $color->toCss($space);
    }

    /**
     * Provide unsupported CSS output spaces for the concrete color test.
     *
     * Must return a list of [space] entries, e.g. [['hsl'], ['lab']].
     *
     * @return array<array{0: string}>
     */
    abstract public static function provideInvalidCssOutputSpaces(): array;

    public function testFromChannels(): void
    {
        $color = $this->createColor();
        $recreated = $color::fromChannels($color->getChannels(), $color->getAlpha());
        $this->assertEquals($color, $recreated);
    }

    public function testGetChannels(): void
    {
        $color = $this->createColor();
        $channels = $color->getChannels();
        $this->assertIsArray($channels);
        $this->assertNotEmpty($channels);
        foreach ($channels as $channel) {
            $this->assertIsFloat($channel);
        }
    }

    public function testImplementsColorInterface(): void
    {
        $color = $this->createColor();
        $this->assertInstanceOf(ColorInterface::class, $color);
        $this->assertInstanceOf($this->getExpectedColorClass(), $color);
    }

    #[DataProvider('provideFromInputs')]
    public function testStaticFromNormalizesInput(ColorInterface|string $input): void
    {
        $class = $this->getExpectedColorClass();
        $normalized = $class::from($input);
        $this->assertInstanceOf($class, $normalized);
    }

    #[DataProvider('provideFromInputs')]
    public function testStaticTryFromNormalizesInput(ColorInterface|string $input): void
    {
        $class = $this->getExpectedColorClass();
        $normalized = $class::tryFrom($input);
        $this->assertInstanceOf($class, $normalized);
    }

    /**
     * Provide inputs acceptable by static from()/tryFrom() for the concrete class.
     *
     * Each entry is a single-element array with either a ColorInterface or a string.
     *
     * @return iterable<array{0: ColorInterface|string}>
     */
    abstract public static function provideFromInputs(): iterable;

    public function testStaticTryFromReturnsNullOnInvalidString(): void
    {
        $class = $this->getExpectedColorClass();
        $this->assertNull($class::tryFrom('not-a-valid-color-input'));
    }

    public function testToCss(): void
    {
        foreach (static::provideColorSamples() as [$color, $hex, $hexWithAlpha, $css]) {
            $this->assertSame($css, $color->toCss());
        }
    }

    public function testToHex(): void
    {
        foreach (static::provideColorSamples() as [$color, $hex, $hexWithAlpha]) {
            $this->assertSame($hex, $color->toHex());
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/i', $color->toHex());

            $this->assertSame($hexWithAlpha, $color->toHex(true));
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{8}$/i', $color->toHex(true));
        }
    }

    public function testToSrgb(): void
    {
        $color = $this->createColor();
        $srgb = $color->toSrgb();
        $this->assertInstanceOf(SrgbColor::class, $srgb);
    }

    public function testToSrgbConversion(): void
    {
        $color = $this->createColor();
        $converted = $color->to('srgb');
        $this->assertInstanceOf(SrgbColor::class, $converted);
    }
}
