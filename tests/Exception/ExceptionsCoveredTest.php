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

use PhpColor\Color\Exception\ColorExceptionInterface;
use PhpColor\Color\Exception\InvalidArgumentException;
use PhpColor\Color\Exception\InvalidColorException;
use PhpColor\Color\Exception\InvalidColorSpaceException;
use PhpColor\Color\Exception\ParseException;
use PhpColor\Color\Exception\UnsupportedColorSpaceException;
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

    public function testEveryLibraryExceptionIsCatchableThroughTheInterface(): void
    {
        $exceptions = [
            new InvalidArgumentException('a'),
            new InvalidColorException('b'),
            new InvalidColorSpaceException('c'),
            new ParseException('d'),
            new UnsupportedColorSpaceException('e'),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(ColorExceptionInterface::class, $exception);
            $this->assertInstanceOf(\Throwable::class, $exception);
        }
    }

    public function testInterfaceExtendsThrowable(): void
    {
        $this->assertTrue(is_a(ColorExceptionInterface::class, \Throwable::class, true));
    }

    public function testInterfaceExposesThrowableApi(): void
    {
        try {
            throw new ParseException('boom');
        } catch (ColorExceptionInterface $e) {
            $this->assertSame('boom', $e->getMessage());
            $this->assertSame(0, $e->getCode());
            $this->assertNotSame('', $e->getFile());
        }
    }
}
