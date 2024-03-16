<?php

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
