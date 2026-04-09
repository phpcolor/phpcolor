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
use PhpColor\Color\Exception\UnsupportedColorSpaceException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UnsupportedColorSpaceException::class)]
final class UnsupportedColorSpaceExceptionTest extends TestCase
{
    public function testImplementsColorExceptionInterface(): void
    {
        $exception = new UnsupportedColorSpaceException('Test message');

        $this->assertInstanceOf(ColorExceptionInterface::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testCanBeCreatedWithMessage(): void
    {
        $message = 'Color space not supported';
        $exception = new UnsupportedColorSpaceException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testCanBeCreatedWithMessageAndCode(): void
    {
        $message = 'Unsupported space';
        $code = 789;
        $exception = new UnsupportedColorSpaceException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testCanBeCreatedWithPreviousException(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new UnsupportedColorSpaceException('Space error', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testCanBeThrown(): void
    {
        $this->expectException(UnsupportedColorSpaceException::class);
        $this->expectExceptionMessage('Test throw');

        throw new UnsupportedColorSpaceException('Test throw');
    }

    public function testCanBeCaughtAsColorException(): void
    {
        try {
            throw new UnsupportedColorSpaceException('Color exception');
        } catch (ColorExceptionInterface $e) {
            $this->assertInstanceOf(UnsupportedColorSpaceException::class, $e);
        }
    }
}
