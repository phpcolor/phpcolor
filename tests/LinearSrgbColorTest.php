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

use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\LinearSrgbColor;
use PhpColor\Color\SrgbColor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LinearSrgbColor::class)]
final class LinearSrgbColorTest extends TestCase
{
    public function testFromChannelsAndAccessors(): void
    {
        $c = LinearSrgbColor::fromChannels(['r' => 0.2, 'g' => 0.4, 'b' => 0.6], 0.7);
        $this->assertSame(0.7, $c->getAlpha());
        $this->assertEquals(['r' => 0.2, 'g' => 0.4, 'b' => 0.6], $c->getChannels());
    }

    public function testRoundTripThroughSrgb(): void
    {
        $lin = new LinearSrgbColor(0.1, 0.2, 0.3, 0.4);
        $srgb = $lin->toSrgb();
        $back = LinearSrgbColor::fromSrgb($srgb);

        $this->assertEqualsWithDelta($lin->r, $back->r, 1e-6);
        $this->assertEqualsWithDelta($lin->g, $back->g, 1e-6);
        $this->assertEqualsWithDelta($lin->b, $back->b, 1e-6);
        $this->assertEqualsWithDelta($lin->a, $back->a, 1e-12);
    }

    public function testToStringDefaultsToColorFunction(): void
    {
        $lin = new LinearSrgbColor(0.5, 0.25, 0.75, 1.0);
        $css = (string) $lin;
        $this->assertStringStartsWith('color(srgb-linear', $css);
    }

    public function testToCssWithAlphaFormatsLinearFunction(): void
    {
        $lin = new LinearSrgbColor(0.25, 0.5, 0.75, 0.5);
        $css = $lin->toCss();
        $this->assertSame('color(srgb-linear 0.25 0.5 0.75 / 0.5)', $css);
    }

    public function testToCssSrgbTargetDelegatesToSrgb(): void
    {
        $lin = new LinearSrgbColor(0.1, 0.2, 0.3, 0.4);
        $css = $lin->toCss('srgb');
        $this->assertStringStartsWith('rgb(', $css);
        $this->assertStringContainsString('/ 0.4', $css);
    }

    public function testToCssThrowsOnUnsupportedTarget(): void
    {
        $lin = new LinearSrgbColor(0.1, 0.2, 0.3, 0.4);
        $this->expectException(InvalidColorException::class);
        $lin->toCss('xyz-d50');
    }

    public function testAliasResolutionViaAbstractTo(): void
    {
        $srgb = new SrgbColor(0.2, 0.4, 0.6, 0.8);
        $lin = $srgb->to('linear-srgb');
        $this->assertInstanceOf(LinearSrgbColor::class, $lin);

        $back = $lin->to('srgb');
        $this->assertInstanceOf(SrgbColor::class, $back);
    }
}
