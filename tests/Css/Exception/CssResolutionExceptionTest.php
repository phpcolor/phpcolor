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

namespace PhpColor\Color\Tests\Css\Exception;

use PhpColor\Color\Css\Exception\CssResolutionException;
use PhpColor\Color\Exception\ColorExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CssResolutionException::class)]
class CssResolutionExceptionTest extends TestCase
{
    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(CssResolutionException::class);
        $this->expectExceptionMessage('resolution error');

        throw new CssResolutionException('resolution error');
    }

    public function testExceptionHasMessage(): void
    {
        $message = 'Failed to resolve CSS color';
        $exception = new CssResolutionException($message);
        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionImplementsColorExceptionInterface(): void
    {
        $exception = new CssResolutionException('test message');
        $this->assertInstanceOf(ColorExceptionInterface::class, $exception);
    }

    public function testExceptionExtendsException(): void
    {
        $exception = new CssResolutionException('test message');
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
