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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvalidArgumentException::class)]
final class InvalidArgumentExceptionTest extends TestCase
{
    public function testImplementsColorExceptionInterface(): void
    {
        $exception = new InvalidArgumentException('Test message');

        $this->assertInstanceOf(ColorExceptionInterface::class, $exception);
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }

    public function testCanBeCreatedWithMessage(): void
    {
        $message = 'Invalid argument provided';
        $exception = new InvalidArgumentException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testCanBeCreatedWithMessageAndCode(): void
    {
        $message = 'Invalid value';
        $code = 456;
        $exception = new InvalidArgumentException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testCanBeCreatedWithPreviousException(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new InvalidArgumentException('Argument error', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testCanBeThrown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Test throw');

        throw new InvalidArgumentException('Test throw');
    }

    public function testCanBeCaught(): void
    {
        try {
            throw new InvalidArgumentException('Caught exception');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Caught exception', $e->getMessage());
        }
    }

    public function testCanBeCaughtAsColorException(): void
    {
        try {
            throw new InvalidArgumentException('Color exception');
        } catch (ColorExceptionInterface $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
        }
    }

    public function testCanBeCaughtAsBaseInvalidArgumentException(): void
    {
        try {
            throw new InvalidArgumentException('Base exception');
        } catch (\InvalidArgumentException $e) {
            $this->assertInstanceOf(InvalidArgumentException::class, $e);
        }
    }
}
