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

namespace PhpColor\Color\Tests\Parser;

use PhpColor\Color\Parser\ParserUtils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ParserUtils::class)]
final class ParserUtilsTest extends TestCase
{
    public function testBetweenBasics(): void
    {
        $this->assertSame('255 0 0', ParserUtils::between('rgb(255 0 0)', '(', ')'));
        $this->assertNull(ParserUtils::between('rgb(255 0 0', '(', ')'));
        $this->assertNull(ParserUtils::between('rgb 255 0 0)', '(', ')'));
        $this->assertNull(ParserUtils::between('rgb 255 0 0', '(', ')'));
        $this->assertSame('a)b)c', ParserUtils::between('test(a)b)c)', '(', ')'));
        $this->assertSame('2 + (3 * 4)', ParserUtils::between('calc(2 + (3 * 4))', '(', ')'));
    }

    public function testNormalizeSpaceName(): void
    {
        $this->assertSame('display-p3', ParserUtils::normalizeSpaceName(' Display_P3 '));
        $this->assertSame('prophoto-rgb', ParserUtils::normalizeSpaceName('prophoto rgb'));
        $this->assertSame('xyz-d65', ParserUtils::normalizeSpaceName('XYZ_D65'));
        $this->assertSame('srgb', ParserUtils::normalizeSpaceName('SRGB'));
    }

    #[DataProvider('unitOrPercentProvider')]
    public function testParseUnitOrPercent(string $input, float $expected, float $delta = 1e-9): void
    {
        $this->assertEqualsWithDelta($expected, ParserUtils::parseUnitOrPercent($input), $delta);
    }

    public static function unitOrPercentProvider(): iterable
    {
        yield ['1', 1.0];
        yield ['0', 0.0];
        yield ['0.5', 0.5];
        yield ['50%', 0.5];
        yield ['-1', 0.0];
        yield ['200%', 1.0];
    }

    public function testSplitBySlashOutsideParens(): void
    {
        $this->assertSame(['rgb 255 0 0', null], ParserUtils::splitBySlashOutsideParens('rgb 255 0 0'));
        $this->assertSame(['rgb 255 0 0', '0.5'], ParserUtils::splitBySlashOutsideParens('rgb 255 0 0 / 0.5'));
        $this->assertSame(['calc(10 / 2)', null], ParserUtils::splitBySlashOutsideParens('calc(10 / 2)'));
        $this->assertSame(['calc(10 / 2)', '0.5'], ParserUtils::splitBySlashOutsideParens('calc(10 / 2) / 0.5'));
        $this->assertSame(['a', 'b / c'], ParserUtils::splitBySlashOutsideParens('a / b / c'));
        $this->assertSame(['calc((10 / 2) / 5)', '0.5'], ParserUtils::splitBySlashOutsideParens('calc((10 / 2) / 5) / 0.5'));
        $this->assertSame(['calc(10 / 2) calc(5 / 2)', '0.5'], ParserUtils::splitBySlashOutsideParens('calc(10 / 2) calc(5 / 2) / 0.5'));
    }

    #[DataProvider('splitChannelProvider')]
    public function testSplitChannelExpressions(string $input, array $expected): void
    {
        $this->assertSame($expected, ParserUtils::splitChannelExpressions($input));
    }

    public static function splitChannelProvider(): iterable
    {
        yield ['r g b', ['r', 'g', 'b']];
        yield ['calc(r * 0.5) g b', ['calc(r * 0.5)', 'g', 'b']];
        yield ['calc((r + g) / 2) b alpha', ['calc((r + g) / 2)', 'b', 'alpha']];
        yield ['r   g   b', ['r', 'g', 'b']];
        yield ['calc(r + 50%) g 0.5', ['calc(r + 50%)', 'g', '0.5']];
    }
}
