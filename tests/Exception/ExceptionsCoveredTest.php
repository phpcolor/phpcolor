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

namespace PhpColor\Color\Tests\Exception;

use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\Exception\ParseException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvalidColorException::class)]
#[CoversClass(ParseException::class)]
final class ExceptionsCoveredTest extends TestCase
{
    public function testInvalidColorExceptionConstructs(): void
    {
        $e = new InvalidColorException('oops');
        $this->assertSame('oops', $e->getMessage());
    }

    public function testParseExceptionConstructs(): void
    {
        $e = new ParseException('bad');
        $this->assertSame('bad', $e->getMessage());
    }
}
