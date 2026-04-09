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

namespace PhpColor\Color\Tests\Palette\Harmony;

use PhpColor\Color\Palette\Harmony\HarmonyPattern;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HarmonyPattern::class)]
final class HarmonyPatternTest extends TestCase
{
    public function testAngles(): void
    {
        foreach (HarmonyPattern::cases() as $case) {
            $this->assertIsArray($case->angles());
            $this->assertIsArray($case->fullAngles());
            $this->assertEquals(0.0, $case->fullAngles()[0]);
        }
    }
}
