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
use PhpColor\Color\Color;
use PhpColor\Color\Css\CssColorParser;
use PhpColor\Color\DisplayP3Color;
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\Exception\ParseException;
use PhpColor\Color\HwbColor;
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

#[CoversClass(CssColorParser::class)]
final class CssColorParserTest extends TestCase
{
    public function testBetweenExtractsContent(): void
    {
        $result = $this->callPrivateMethod('between', 'rgb(255 0 0)', '(', ')');

        $this->assertSame('255 0 0', $result);
    }

    public function testBetweenReturnsNullWhenMissingEnd(): void
    {
        $result = $this->callPrivateMethod('between', 'rgb(255 0 0', '(', ')');

        $this->assertNull($result);
    }

    public function testBetweenReturnsNullWhenMissingStart(): void
    {
        $result = $this->callPrivateMethod('between', 'rgb 255 0 0)', '(', ')');

        $this->assertNull($result);
    }

    public function testBetweenReturnsNullWhenNotFound(): void
    {
        $result = $this->callPrivateMethod('between', 'rgb 255 0 0', '(', ')');

        $this->assertNull($result);
    }

    public function testBetweenUsesLastOccurrenceOfEnd(): void
    {
        $result = $this->callPrivateMethod('between', 'test(a)b)c)', '(', ')');

        $this->assertSame('a)b)c', $result);
    }

    public function testBetweenWithNestedParentheses(): void
    {
        $result = $this->callPrivateMethod('between', 'calc(2 + (3 * 4))', '(', ')');

        $this->assertSame('2 + (3 * 4)', $result);
    }

    public function testEvaluateChannelExpressionPercentAndInvalid(): void
    {
        $ref = new \ReflectionClass(CssColorParser::class);
        $m = $ref->getMethod('evaluateChannelExpression');

        $val = $m->invoke(null, '50%', ['x' => 0.1], 1.0);
        $this->assertEqualsWithDelta(0.5, $val, 1e-9);

        $this->expectException(ParseException::class);
        $m->invoke(null, 'not-a-var', ['x' => 0.1], 1.0);
    }

    public function testNormalizeSpaceNameComplex(): void
    {
        $result = $this->callPrivateMethod('normalizeSpaceName', '  Display_P3  ');

        $this->assertSame('display-p3', $result);
    }

    public function testNormalizeSpaceNameHandlesAllCommonSpaces(): void
    {
        $spaces = [
            'srgb' => 'srgb',
            'SRGB' => 'srgb',
            'display-p3' => 'display-p3',
            'display_p3' => 'display-p3',
            'DisplayP3' => 'displayp3',
            'xyz' => 'xyz',
            'xyz-d65' => 'xyz-d65',
            'XYZ_D65' => 'xyz-d65',
            'rec2020' => 'rec2020',
            'rec-2020' => 'rec-2020',
            'prophoto-rgb' => 'prophoto-rgb',
            'prophoto_rgb' => 'prophoto-rgb',
            'a98-rgb' => 'a98-rgb',
            'a98_rgb' => 'a98-rgb',
        ];

        foreach ($spaces as $input => $expected) {
            $result = $this->callPrivateMethod('normalizeSpaceName', $input);
            $this->assertSame($expected, $result, "Failed for input: $input");
        }
    }

    public function testNormalizeSpaceNameLowercase(): void
    {
        $result = $this->callPrivateMethod('normalizeSpaceName', 'SRGB');

        $this->assertSame('srgb', $result);
    }

    public function testNormalizeSpaceNamePublicAccess(): void
    {
        $result = $this->callPrivateMethod('normalizeSpaceName', 'Display_P3');

        $this->assertSame('display-p3', $result);
    }

    public function testNormalizeSpaceNameReplaceSpace(): void
    {
        $result = $this->callPrivateMethod('normalizeSpaceName', 'prophoto rgb');

        $this->assertSame('prophoto-rgb', $result);
    }

    public function testNormalizeSpaceNameReplaceUnderscore(): void
    {
        $result = $this->callPrivateMethod('normalizeSpaceName', 'display_p3');

        $this->assertSame('display-p3', $result);
    }

    public function testNormalizeSpaceNameTrim(): void
    {
        $result = $this->callPrivateMethod('normalizeSpaceName', '  xyz-d65  ');

        $this->assertSame('xyz-d65', $result);
    }

    /**
     * @param array<string, float> $expected
     */
    #[DataProvider('normalizeSpaceNameProvider')]
    public function testNormalizeSpaceNameVariants(string $input, string $expected): void
    {
        $result = $this->callPrivateMethod('normalizeSpaceName', $input);

        $this->assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function normalizeSpaceNameProvider(): iterable
    {
        yield 'srgb lowercase' => ['srgb', 'srgb'];
        yield 'SRGB uppercase' => ['SRGB', 'srgb'];
        yield 'SrGb mixed case' => ['SrGb', 'srgb'];
        yield 'display-p3' => ['display-p3', 'display-p3'];
        yield 'display_p3' => ['display_p3', 'display-p3'];
        yield 'Display_P3' => ['Display_P3', 'display-p3'];
        yield 'prophoto rgb' => ['prophoto rgb', 'prophoto-rgb'];
        yield 'prophoto-rgb' => ['prophoto-rgb', 'prophoto-rgb'];
        yield 'a98 rgb' => ['a98 rgb', 'a98-rgb'];
        yield 'rec2020' => ['rec2020', 'rec2020'];
        yield 'rec-2020' => ['rec-2020', 'rec-2020'];
        yield 'rec 2020' => ['rec 2020', 'rec-2020'];
        yield 'xyz' => ['xyz', 'xyz'];
        yield 'xyz-d65' => ['xyz-d65', 'xyz-d65'];
        yield 'XYZ_D65' => ['XYZ_D65', 'xyz-d65'];
    }

    public function testParseChannelSpecificationsWrongCountThrows(): void
    {
        $ref = new \ReflectionClass(CssColorParser::class);
        $m = $ref->getMethod('parseChannelSpecifications');
        $this->expectException(ParseException::class);
        $m->invoke(null, 'l c', ['l' => 0.5, 'c' => 0.2, 'h' => 30], 1.0, 'oklch');
    }

    public function testParseColorFunctionAlphaCalc(): void
    {
        $c = CssColorParser::parseColorFunction('color(srgb 1 0 0 / calc(0.25 + 0.25))');
        $this->assertInstanceOf(SrgbColor::class, $c);
        $this->assertEqualsWithDelta(0.5, $c->a, 1e-9);
    }

    public function testParseColorFunctionAlphaDefaultOne(): void
    {
        $c = CssColorParser::parseColorFunction('color(srgb 1 0 0)');
        $this->assertInstanceOf(SrgbColor::class, $c);
        $this->assertEqualsWithDelta(1.0, $c->a, 1e-9);
    }

    public function testParseColorFunctionAlphaNumber(): void
    {
        $c = CssColorParser::parseColorFunction('color(srgb 1 0 0 / 0.3)');
        $this->assertInstanceOf(SrgbColor::class, $c);
        $this->assertEqualsWithDelta(0.3, $c->a, 1e-9);
    }

    public function testParseColorFunctionAlphaPercent(): void
    {
        $c = CssColorParser::parseColorFunction('color(srgb 1 0 0 / 50%)');
        $this->assertInstanceOf(SrgbColor::class, $c);
        $this->assertEqualsWithDelta(0.5, $c->a, 1e-9);
    }

    public function testParseColorFunctionDisplayP3Alias(): void
    {
        $c = CssColorParser::parseColorFunction('color(displayp3 1 0 0)');
        $this->assertInstanceOf(DisplayP3Color::class, $c);
    }

    public function testParseColorFunctionMissingClosingParen(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Cannot parse color');
        CssColorParser::parseColorFunction('color(srgb 1 0 0');
    }

    public function testParseColorFunctionSpaceVariants(): void
    {
        $xyz = CssColorParser::parseColorFunction('color(xyz 0.1 0.2 0.3)');
        $this->assertInstanceOf(XyzColor::class, $xyz);

        $r2020 = CssColorParser::parseColorFunction('color(rec-2020 0.1 0.2 0.3)');
        $this->assertInstanceOf(Rec2020Color::class, $r2020);

        $pro = CssColorParser::parseColorFunction('color(prophoto 0.1 0.2 0.3)');
        $this->assertInstanceOf(ProPhotoColor::class, $pro);

        $a98 = CssColorParser::parseColorFunction('color(a98 0.1 0.2 0.3)');
        $this->assertInstanceOf(A98RgbColor::class, $a98);
    }

    public function testParseColorFunctionThrowsOnEmptyParameters(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Cannot parse color');

        CssColorParser::parseColorFunction('color()');
    }

    public function testParseColorFunctionThrowsOnInvalidParameterCount(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Cannot parse color');

        CssColorParser::parseColorFunction('color(srgb 1.0 0.5)');
    }

    public function testParseColorFunctionThrowsOnMissingParameters(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Cannot parse color');

        CssColorParser::parseColorFunction('color(srgb)');
    }

    public function testParseColorFunctionThrowsOnUnsupportedSpace(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Unsupported color() space');

        CssColorParser::parseColorFunction('color(unknown 1.0 0.5 0.0)');
    }

    public function testParseDirectFunctionsCoverFastPaths(): void
    {
        $this->assertInstanceOf(SrgbColor::class, CssColorParser::parse('hsl(0 100% 50%)'));
        $this->assertInstanceOf(SrgbColor::class, CssColorParser::parse('hsla(0 100% 50% / 0.5)'));
        $this->assertInstanceOf(OklabColor::class, CssColorParser::parse('oklab(0.5 0.1 0.2)'));
        $this->assertInstanceOf(OklchColor::class, CssColorParser::parse('oklch(0.5 0.1 180)'));
        $this->assertInstanceOf(LabColor::class, CssColorParser::parse('lab(50 25 -25)'));
        $this->assertInstanceOf(LchColor::class, CssColorParser::parse('lch(50 35 180)'));
    }

    public function testParseRelativeDirect(): void
    {
        $color = CssColorParser::parse('oklch(from rgb(255 0 0) l c h)');
        $this->assertInstanceOf(OklchColor::class, $color);
    }

    public function testParseRelativeSrgbLinearIdentity(): void
    {
        $source = Color::parse('#3b82f6');

        $this->assertSame($source->to('srgb-linear')->toCss(), CssColorParser::parse('color(from #3b82f6 srgb-linear r g b)')->toCss());
    }

    public function testParseRelativeSrgbLinearChannelExpression(): void
    {
        $halved = CssColorParser::parse('color(from red srgb-linear calc(r / 2) g b)');

        $this->assertInstanceOf(LinearSrgbColor::class, $halved);
        $this->assertEqualsWithDelta(0.5, $halved->getChannels()['r'], 1e-9);
    }

    public function testParseRelativeXyzIdentity(): void
    {
        $source = Color::parse('#3b82f6');

        $this->assertSame($source->to('xyz-d65')->toCss(), CssColorParser::parse('color(from #3b82f6 xyz x y z)')->toCss());
        $this->assertSame($source->to('xyz-d65')->toCss(), CssColorParser::parse('color(from #3b82f6 xyz-d65 x y z)')->toCss());
    }

    public function testParseRelativeXyzChannelExpression(): void
    {
        $doubled = CssColorParser::parse('color(from red xyz calc(x * 2) y z)');
        $source = Color::parse('red')->to('xyz-d65')->getChannels();

        $this->assertEqualsWithDelta($source['x'] * 2, $doubled->getChannels()['x'], 1e-9);
        $this->assertEqualsWithDelta($source['y'], $doubled->getChannels()['y'], 1e-9);
    }

    public function testParseRelativeMissingParensTriggersInnerNullBranch(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid color() syntax');

        $ref = new \ReflectionClass(CssColorParser::class);
        $m = $ref->getMethod('parseRelative');

        // Directly invoke private parseRelative to cover the inner-null guard at L286
        $m->invoke(null, 'rgb(from');
    }

    public function testParseRelativeWithMissingParen(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid color() syntax');
        CssColorParser::parse('rgb(from');
    }

    public function testParseThrowsExceptionOnEmptyString(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Empty color string');

        CssColorParser::parse('');
    }

    public function testParseThrowsExceptionOnWhitespaceOnly(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Empty color string');

        CssColorParser::parse('   ');
    }

    public function testParseUnitOrPercentClampMax(): void
    {
        $result = $this->callPrivateMethod('parseUnitOrPercent', '1.5');

        $this->assertSame(1.0, $result);
    }

    public function testParseUnitOrPercentClampMin(): void
    {
        $result = $this->callPrivateMethod('parseUnitOrPercent', '-0.5');

        $this->assertSame(0.0, $result);
    }

    public function testParseUnitOrPercentDecimal(): void
    {
        $result = $this->callPrivateMethod('parseUnitOrPercent', '0.5');

        $this->assertSame(0.5, $result);
    }

    public function testParseUnitOrPercentInteger(): void
    {
        $result = $this->callPrivateMethod('parseUnitOrPercent', '1');

        $this->assertSame(1.0, $result);
    }

    public function testParseUnitOrPercentPercentage(): void
    {
        $result = $this->callPrivateMethod('parseUnitOrPercent', '50%');

        $this->assertSame(0.5, $result);
    }

    public function testRelativeAlphaCalcCoversCalcEvaluation(): void
    {
        $c = CssColorParser::parse('rgb(from rgba(255 0 0 / 0.5) r g b / calc(alpha * 2))');
        $this->assertEqualsWithDelta(1.0, $c->getAlpha(), 1e-9);
    }

    public function testRelativeAlphaReferenceCoversAlphaBranch(): void
    {
        $c = CssColorParser::parse('rgb(from rgba(255 0 0 / 0.5) r g b / alpha)');
        $this->assertEqualsWithDelta(0.5, $c->getAlpha(), 1e-9);
    }

    public function testRelativeDisplayP3TargetCoversMapping(): void
    {
        $c = CssColorParser::parse('color(from red display-p3 r g b)');
        $this->assertInstanceOf(DisplayP3Color::class, $c);
    }

    public function testRelativeExplicitTargets(): void
    {
        $xyz = CssColorParser::parse('color(from red xyz 0.1 0.2 0.3)');
        $this->assertInstanceOf(XyzColor::class, $xyz);

        $r2020 = CssColorParser::parse('color(from red rec2020 0.1 0.2 0.3)');
        $this->assertInstanceOf(Rec2020Color::class, $r2020);

        $pro = CssColorParser::parse('color(from red prophoto-rgb 0.1 0.2 0.3)');
        $this->assertInstanceOf(ProPhotoColor::class, $pro);

        $a98 = CssColorParser::parse('color(from red a98-rgb 0.1 0.2 0.3)');
        $this->assertInstanceOf(A98RgbColor::class, $a98);
    }

    public function testRelativeHexNoExplicitTarget(): void
    {
        $rgb = CssColorParser::parse('rgb(from #ff0000 r g b)');
        $this->assertInstanceOf(SrgbColor::class, $rgb);
    }

    public function testRelativeHslBranchAndAlphaSlash(): void
    {
        $hsl = CssColorParser::parse('hsl(from red h s l)');
        $this->assertInstanceOf(SrgbColor::class, $hsl);

        $rgb = CssColorParser::parse('rgb(from red r g b / 50%)');
        $this->assertEqualsWithDelta(0.5, $rgb->getAlpha(), 1e-9);
    }

    public function testRelativeHwbAlphaSlash(): void
    {
        $c = CssColorParser::parse('hwb(from red h w b / 50%)');

        $this->assertInstanceOf(HwbColor::class, $c);
        $this->assertEqualsWithDelta(0.5, $c->getAlpha(), 1e-9);
        $this->assertSame('#ff0000', $c->toSrgb()->toHex());
    }

    public function testRelativeHwbFromNonHwbOrigin(): void
    {
        $c = CssColorParser::parse('hwb(from hsl(120 100% 50%) h w b)');

        $this->assertInstanceOf(HwbColor::class, $c);
        $this->assertEqualsWithDelta(120.0, $c->h, 1e-9);
        $this->assertSame('#00ff00', $c->toSrgb()->toHex());
    }

    public function testRelativeHwbIdentityRoundTrip(): void
    {
        $red = CssColorParser::parse('hwb(from red h w b)');

        $this->assertInstanceOf(HwbColor::class, $red);
        $this->assertSame('#ff0000', $red->toSrgb()->toHex());

        $tinted = CssColorParser::parse('hwb(from #ff8080 h w b)');

        $this->assertInstanceOf(HwbColor::class, $tinted);
        $this->assertSame('#ff8080', $tinted->toSrgb()->toHex());
    }

    public function testRelativeHwbModifiedChannels(): void
    {
        $whiter = CssColorParser::parse('hwb(from red h 50% b)');

        $this->assertInstanceOf(HwbColor::class, $whiter);
        $this->assertEqualsWithDelta(0.5, $whiter->w, 1e-9);
        $this->assertEqualsWithDelta(0.0, $whiter->b, 1e-9);

        // "b" is blackness, not blue: 25% blackness darkens red to #bf0000
        $blacker = CssColorParser::parse('hwb(from red h w calc(b + 0.25))');

        $this->assertInstanceOf(HwbColor::class, $blacker);
        $this->assertEqualsWithDelta(0.25, $blacker->b, 1e-9);
        $this->assertSame('#bf0000', $blacker->toSrgb()->toHex());
    }

    public function testRelativeInvalidInnerSyntaxThrows(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('No relative "from" clause');
        CssColorParser::parse('rgb(from )');
    }

    public function testRelativeOriginAsColorFunctionWithExplicitTarget(): void
    {
        $c = CssColorParser::parse('rgb(from color(srgb 1 0 0) rec2020 r g b)');
        $this->assertInstanceOf(Rec2020Color::class, $c);
    }

    public function testRelativeOriginSpaceWithExplicitTarget(): void
    {
        $out = CssColorParser::parse('rgb(from srgb 1 0 0 rec2020 r g b)');
        $this->assertInstanceOf(Rec2020Color::class, $out);
    }

    public function testRelativeTargetSpaceAliases(): void
    {
        $this->assertInstanceOf(XyzColor::class, CssColorParser::parse('color(from red xyz-d65 0.1 0.2 0.3)'));
        $this->assertInstanceOf(Rec2020Color::class, CssColorParser::parse('color(from red rec-2020 0.1 0.2 0.3)'));
        $this->assertInstanceOf(Rec2020Color::class, CssColorParser::parse('color(from red bt2020 0.1 0.2 0.3)'));
        $this->assertInstanceOf(ProPhotoColor::class, CssColorParser::parse('color(from red prophoto 0.1 0.2 0.3)'));
        $this->assertInstanceOf(ProPhotoColor::class, CssColorParser::parse('color(from red romm-rgb 0.1 0.2 0.3)'));
        $this->assertInstanceOf(A98RgbColor::class, CssColorParser::parse('color(from red a98 0.1 0.2 0.3)'));
        $this->assertInstanceOf(A98RgbColor::class, CssColorParser::parse('color(from red adobe-rgb 0.1 0.2 0.3)'));
    }

    public function testRelativeToSpecificSpaces(): void
    {
        $this->assertInstanceOf(OklabColor::class, CssColorParser::parse('oklab(from red l a b)'));
        $this->assertInstanceOf(OklchColor::class, CssColorParser::parse('oklch(from red l c h)'));
        $this->assertInstanceOf(LabColor::class, CssColorParser::parse('lab(from red l a b)'));
        $this->assertInstanceOf(LchColor::class, CssColorParser::parse('lch(from red l c h)'));
        $this->assertInstanceOf(XyzColor::class, CssColorParser::parse('color(from red xyz-d65 0.1 0.2 0.3)'));
    }

    public function testRelativeUnsupportedTargetSpaceThrows(): void
    {
        $this->expectException(InvalidColorException::class);
        CssColorParser::parse('color(from red unknown r g b)');
    }

    public function testRelativeWithFromKeywordMissingAtStartThrows(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('No relative "from" clause');
        CssColorParser::parse('rgb(1 from red)');
    }

    public function testSplitBySlashOutsideParensIgnoresSlashInParens(): void
    {
        $result = $this->callPrivateMethod('splitBySlashOutsideParens', 'calc(10 / 2) / 0.5');

        $this->assertSame(['calc(10 / 2)', '0.5'], $result);
    }

    public function testSplitBySlashOutsideParensMultipleSlashes(): void
    {
        $result = $this->callPrivateMethod('splitBySlashOutsideParens', 'a / b / c');

        $this->assertSame(['a', 'b / c'], $result);
    }

    public function testSplitBySlashOutsideParensNestedParens(): void
    {
        $result = $this->callPrivateMethod('splitBySlashOutsideParens', 'calc((10 / 2) / 5) / 0.5');

        $this->assertSame(['calc((10 / 2) / 5)', '0.5'], $result);
    }

    public function testSplitBySlashOutsideParensNoSlash(): void
    {
        $result = $this->callPrivateMethod('splitBySlashOutsideParens', 'rgb 255 0 0');

        $this->assertSame(['rgb 255 0 0', null], $result);
    }

    public function testSplitBySlashOutsideParensWithSlash(): void
    {
        $result = $this->callPrivateMethod('splitBySlashOutsideParens', 'rgb 255 0 0 / 0.5');

        $this->assertSame(['rgb 255 0 0', '0.5'], $result);
    }

    #[DataProvider('splitBySlashProvider')]
    public function testSplitBySlashVariants(string $input, string $before, ?string $after): void
    {
        $result = $this->callPrivateMethod('splitBySlashOutsideParens', $input);

        $this->assertSame([$before, $after], $result);
    }

    /**
     * @return iterable<string, array{string, string, string|null}>
     */
    public static function splitBySlashProvider(): iterable
    {
        yield 'no slash' => ['rgb 255 0 0', 'rgb 255 0 0', null];
        yield 'with slash' => ['rgb 255 0 0 / 0.5', 'rgb 255 0 0', '0.5'];
        yield 'slash in parens' => ['calc(10 / 2)', 'calc(10 / 2)', null];
        yield 'slash outside after parens' => ['calc(10 / 2) / 0.5', 'calc(10 / 2)', '0.5'];
        yield 'multiple slashes' => ['a / b / c', 'a', 'b / c'];
        yield 'nested parens with slash' => ['calc((10 / 2) / 5)', 'calc((10 / 2) / 5)', null];
        yield 'multiple parens' => ['calc(10 / 2) calc(5 / 2) / 0.5', 'calc(10 / 2) calc(5 / 2)', '0.5'];
    }

    public function testSplitChannelExpressionsMixed(): void
    {
        $result = $this->callPrivateMethod('splitChannelExpressions', 'calc(r + 50%) g 0.5');

        $this->assertSame(['calc(r + 50%)', 'g', '0.5'], $result);
    }

    public function testSplitChannelExpressionsMultipleSpaces(): void
    {
        $result = $this->callPrivateMethod('splitChannelExpressions', 'r   g   b');

        $this->assertSame(['r', 'g', 'b'], $result);
    }

    public function testSplitChannelExpressionsSimple(): void
    {
        $result = $this->callPrivateMethod('splitChannelExpressions', 'r g b');

        $this->assertSame(['r', 'g', 'b'], $result);
    }

    #[DataProvider('splitChannelExpressionsProvider')]
    public function testSplitChannelExpressionsVariants(string $input, array $expected): void
    {
        $result = $this->callPrivateMethod('splitChannelExpressions', $input);

        $this->assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{string, array<string>}>
     */
    public static function splitChannelExpressionsProvider(): iterable
    {
        yield 'simple' => ['r g b', ['r', 'g', 'b']];
        yield 'with calc' => ['calc(r * 0.5) g b', ['calc(r * 0.5)', 'g', 'b']];
        yield 'all calc' => ['calc(r) calc(g) calc(b)', ['calc(r)', 'calc(g)', 'calc(b)']];
        yield 'nested calc' => ['calc((r + g) / 2) b alpha', ['calc((r + g) / 2)', 'b', 'alpha']];
        yield 'numbers' => ['0.5 0.25 1.0', ['0.5', '0.25', '1.0']];
        yield 'percentages' => ['50% 25% 100%', ['50%', '25%', '100%']];
        yield 'mixed' => ['calc(r + 50%) g 0.5', ['calc(r + 50%)', 'g', '0.5']];
        yield 'multiple spaces' => ['r   g   b', ['r', 'g', 'b']];
        yield 'complex calc' => [
            'calc((r + 0.5) * 2) calc(g / 2) calc(b - 0.1)',
            ['calc((r + 0.5) * 2)', 'calc(g / 2)', 'calc(b - 0.1)'],
        ];
    }

    public function testSplitChannelExpressionsWithCalc(): void
    {
        $result = $this->callPrivateMethod('splitChannelExpressions', 'calc(r * 0.5) g b');

        $this->assertSame(['calc(r * 0.5)', 'g', 'b'], $result);
    }

    public function testSplitChannelExpressionsWithNestedCalc(): void
    {
        $result = $this->callPrivateMethod('splitChannelExpressions', 'calc((r + g) / 2) calc(b * 0.5) alpha');

        $this->assertSame(['calc((r + g) / 2)', 'calc(b * 0.5)', 'alpha'], $result);
    }

    public function testSplitChannelExpressionsWithNumbers(): void
    {
        $result = $this->callPrivateMethod('splitChannelExpressions', '0.5 0.25 1.0');

        $this->assertSame(['0.5', '0.25', '1.0'], $result);
    }

    public function testSplitChannelExpressionsWithPercentages(): void
    {
        $result = $this->callPrivateMethod('splitChannelExpressions', '50% 25% 100%');

        $this->assertSame(['50%', '25%', '100%'], $result);
    }

    public function testSplitColorFunctionRelativeHexOriginWithExplicitTarget(): void
    {
        $c = CssColorParser::parse('rgb(from #ff0000 a98-rgb r g b)');
        $this->assertInstanceOf(A98RgbColor::class, $c);
    }

    public function testSplitColorFunctionRelativeUnrecognizedRest(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Could not parse color() relative color syntax');
        CssColorParser::parse('rgb(from blep bloop blarp)');
    }

    public function testEvaluateChannelExpressionThrowsOnCalcDivisionByZero(): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Invalid calc() expression: "calc(1 / 0)"');

        $this->callPrivateMethod('evaluateChannelExpression', 'calc(1 / 0)', [], 1.0);
    }

    public function testSplitColorFunctionRelativeHexOriginWithNonSpaceTarget(): void
    {
        // This input should trigger the 'tryHex' path, and then the 'else' branch for targetSpace
        // Use 'srgb' as the outer function to ensure a valid default target space.
        $c = CssColorParser::parse('srgb(from red r g b)');
        $this->assertInstanceOf(SrgbColor::class, $c);
        $this->assertEqualsWithDelta(1.0, $c->r, 1e-9);
        $this->assertEqualsWithDelta(0.0, $c->g, 1e-9);
        $this->assertEqualsWithDelta(0.0, $c->b, 1e-9);
    }

    private static function callPrivateMethod(string $method, ...$args): mixed
    {
        $reflection = new \ReflectionClass(CssColorParser::class);
        $method = $reflection->getMethod($method);

        return $method->invoke(null, ...$args);
    }
}
