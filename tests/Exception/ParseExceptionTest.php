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

use PhpColor\Color\Exception\ParseException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ParseException::class)]
final class ParseExceptionTest extends TestCase
{
    public function testIsException(): void
    {
        $exception = new ParseException('Test message');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Test message', $exception->getMessage());
    }

    public function testWithCode(): void
    {
        $exception = new ParseException('Test message', 123);

        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(123, $exception->getCode());
    }

    public function testWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous exception');
        $exception = new ParseException('Test message', 0, $previous);

        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
