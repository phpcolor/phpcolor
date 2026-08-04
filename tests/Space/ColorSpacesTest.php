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

namespace PhpColor\Color\Tests\Space;

use PhpColor\Color\Space\ColorSpaces;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColorSpaces::class)]
final class ColorSpacesTest extends TestCase
{
    #[DataProvider('canonicalProvider')]
    public function testCanonicalNamesPassThrough(string $canonical): void
    {
        $this->assertSame($canonical, ColorSpaces::normalize($canonical));
    }

    public static function canonicalProvider(): iterable
    {
        yield ['srgb'];
        yield ['display-p3'];
        yield ['xyz-d65'];
        yield ['rec2020'];
        yield ['prophoto-rgb'];
        yield ['a98-rgb'];
        yield ['oklab'];
        yield ['oklch'];
        yield ['lab'];
        yield ['lch'];
        yield ['hsl'];
    }

    #[DataProvider('expectedChannelsProvider')]
    public function testExpectedChannelsBySpace(string $space, array $expectedChannels): void
    {
        // Arrange & Act
        $result = ColorSpaces::expectedChannels($space);

        // Assert
        $this->assertSame($expectedChannels, $result);
    }

    public static function expectedChannelsProvider(): iterable
    {
        yield ['hsl', ['h', 's', 'l']];
        yield ['hwb', ['h', 'w', 'b']];
        yield ['oklab', ['l', 'a', 'b']];
        yield ['oklch', ['l', 'c', 'h']];
        yield ['srgb', ['r', 'g', 'b']];
        yield ['rgb', ['r', 'g', 'b']];
        yield ['xyz-d65', ['r', 'g', 'b']];
        yield ['lab', ['l', 'a', 'b']];
        yield ['lch', ['l', 'c', 'h']];
        yield ['rec2020', ['r', 'g', 'b']];
        yield ['cmyk', ['c', 'm', 'y', 'k']];
    }

    public function testExpectedChannelsForUnknownSpace(): void
    {
        // Arrange
        $unknownSpace = 'unknown-space';

        // Act
        $result = ColorSpaces::expectedChannels($unknownSpace);

        // Assert
        $this->assertSame(['r', 'g', 'b'], $result);
    }

    public function testNormalizeHandlesSpacesAndUnderscoresViaParserUtils(): void
    {
        // ParserUtils folds underscores/spaces to dashes first, then alias-maps
        $this->assertSame('display-p3', ColorSpaces::normalize('display_p3'));
        $this->assertSame('rec2020', ColorSpaces::normalize('rec 2020'));
        $this->assertSame('prophoto-rgb', ColorSpaces::normalize('prophoto rgb'));
    }

    public function testNormalizeResolvesAliases(): void
    {
        $this->assertSame('srgb', ColorSpaces::normalize('rgb'));
        $this->assertSame('display-p3', ColorSpaces::normalize('DisplayP3'));
        $this->assertSame('rec2020', ColorSpaces::normalize('rec-2020'));
        $this->assertSame('rec2020', ColorSpaces::normalize('bt-2020'));
        $this->assertSame('prophoto-rgb', ColorSpaces::normalize('romm-rgb'));
        $this->assertSame('a98-rgb', ColorSpaces::normalize('adobe-rgb'));
        // Canonical names pass-through
        $this->assertSame('oklab', ColorSpaces::normalize('oklab'));
        $this->assertSame('display-p3', ColorSpaces::normalize('display-p3'));
        $this->assertSame('a98-rgb', ColorSpaces::normalize('a98-rgb'));
        // xyz maps to xyz-d65 per extras
        $this->assertSame('xyz-d65', ColorSpaces::normalize('xyz'));
        // Unknowns are normalized only (trim/space/underscore handled earlier upstream)
        $this->assertSame('funky', ColorSpaces::normalize('funky'));
    }
}
