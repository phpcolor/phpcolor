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

namespace PhpColor\Color\Tests\Vision;

use PhpColor\Color\Vision\VisionProfile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(VisionProfile::class)]
final class VisionProfileTest extends TestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame('protanopia', VisionProfile::Protanopia->value);
        $this->assertSame('deuteranopia', VisionProfile::Deuteranopia->value);
        $this->assertSame('tritanopia', VisionProfile::Tritanopia->value);
        $this->assertSame('monochromacy', VisionProfile::Monochromacy->value);
    }

    public function testDescriptionsAvoidNegativeLanguage(): void
    {
        foreach (VisionProfile::cases() as $profile) {
            $description = $profile->description();
            $this->assertNotSame('', $description);
            $this->assertStringNotContainsStringIgnoringCase('blind', $description);
            $this->assertStringNotContainsStringIgnoringCase('deficiency', $description);
        }
    }

    public function testPopulationSharesArePositive(): void
    {
        foreach (VisionProfile::cases() as $profile) {
            $this->assertGreaterThan(0.0, $profile->populationShare());
        }
    }

    public function testSevereProfilesMatchExpectation(): void
    {
        $this->assertTrue(VisionProfile::Protanopia->isSevere());
        $this->assertTrue(VisionProfile::Deuteranopia->isSevere());
        $this->assertTrue(VisionProfile::Tritanopia->isSevere());
        $this->assertTrue(VisionProfile::Monochromacy->isSevere());

        $this->assertFalse(VisionProfile::Protanomaly->isSevere());
        $this->assertFalse(VisionProfile::Deuteranomaly->isSevere());
        $this->assertFalse(VisionProfile::Tritanomaly->isSevere());
    }

    public function testCommonlyTestedProfiles(): void
    {
        $common = VisionProfile::commonlyTested();

        $this->assertCount(4, $common);
        $this->assertContains(VisionProfile::Deuteranopia, $common);
        $this->assertContains(VisionProfile::Deuteranomaly, $common);
        $this->assertContains(VisionProfile::Protanopia, $common);
        $this->assertContains(VisionProfile::Protanomaly, $common);
        $this->assertNotContains(VisionProfile::Monochromacy, $common);
    }
}
