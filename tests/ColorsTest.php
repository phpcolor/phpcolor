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

use PhpColor\Color\Color;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Color::class)]
final class ColorsTest extends TestCase
{
    public function testBasics(): void
    {
        $this->assertSame('#000000', Color::black()->toHex());
        $this->assertSame('#ffffff', Color::white()->toHex());
        $this->assertSame('#ff0000', Color::red()->toHex());
        $this->assertSame('#00ff00', Color::green()->toHex());
        $this->assertSame('#0000ff', Color::blue()->toHex());
        $this->assertSame('#00ffff', Color::cyan()->toHex());
        $this->assertSame('#ff00ff', Color::magenta()->toHex());
        $this->assertSame('#ffff00', Color::yellow()->toHex());
        $this->assertSame('#ffa500', Color::orange()->toHex());
        $this->assertSame('#800080', Color::purple()->toHex());
        $this->assertSame('#008080', Color::teal()->toHex());
        $this->assertSame('#808080', Color::gray()->toHex());
    }

    public function testCollections(): void
    {
        $p = Color::primaries(0.5);
        $this->assertArrayHasKey('red', $p);
        $this->assertArrayHasKey('green', $p);
        $this->assertArrayHasKey('blue', $p);

        $s = Color::secondaries(0.75);
        $this->assertArrayHasKey('cyan', $s);
        $this->assertArrayHasKey('magenta', $s);
        $this->assertArrayHasKey('yellow', $s);
    }
}
