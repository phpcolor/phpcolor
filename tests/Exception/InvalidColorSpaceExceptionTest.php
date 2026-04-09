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
use PhpColor\Color\Exception\InvalidColorSpaceException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvalidColorSpaceException::class)]
final class InvalidColorSpaceExceptionTest extends TestCase
{
    public function testImplementsColorExceptionInterface(): void
    {
        $exception = new InvalidColorSpaceException('Test message');

        $this->assertInstanceOf(ColorExceptionInterface::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testCanBeCreatedWithMessage(): void
    {
        $message = 'Invalid color space';
        $exception = new InvalidColorSpaceException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testCanBeCreatedWithMessageAndCode(): void
    {
        $message = 'Invalid space';
        $code = 101;
        $exception = new InvalidColorSpaceException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testCanBeCreatedWithPreviousException(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new InvalidColorSpaceException('Space error', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testCanBeThrown(): void
    {
        $this->expectException(InvalidColorSpaceException::class);
        $this->expectExceptionMessage('Test throw');

        throw new InvalidColorSpaceException('Test throw');
    }

    public function testCanBeCaughtAsColorException(): void
    {
        try {
            throw new InvalidColorSpaceException('Color exception');
        } catch (ColorExceptionInterface $e) {
            $this->assertInstanceOf(InvalidColorSpaceException::class, $e);
        }
    }
}
