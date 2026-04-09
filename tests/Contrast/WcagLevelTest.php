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

namespace PhpColor\Color\Tests\Contrast;

use PhpColor\Color\Contrast\WcagLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WcagLevel::class)]
final class WcagLevelTest extends TestCase
{
    public function testCases(): void
    {
        $this->assertSame('AA', WcagLevel::AA->value);
        $this->assertSame('AAA', WcagLevel::AAA->value);
    }
}
