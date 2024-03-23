<?php

declare(strict_types=1);

/*
 * This file is part of the PhpColor package.
 *
 * (c) Simon Andre <smn.andre@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpColor\Tests;

use PhpColor\Color;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Color::class)]
class ColorTest extends TestCase
{
    public function testInstantiate(): void
    {
        $color = new Color();
        $this->assertInstanceOf(Color::class, $color);
    }
}
