<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Policy;

use Hryvinskyi\Csp\Model\Policy\Policy;
use PHPUnit\Framework\TestCase;

class PolicyTest extends TestCase
{
    public function testWithSourcesReturnsACopyAndLeavesTheOriginal(): void
    {
        $original = new Policy(['img-src' => ["'self'"]]);

        $changed = $original->withSources('img-src', ["'self'", 'data:'])->withSources('font-src', ['data:']);

        $this->assertSame(["'self'"], $original->sources('img-src'));
        $this->assertFalse($original->has('font-src'));
        $this->assertSame(['img-src', 'font-src'], $changed->names());
        $this->assertSame(["'self'", 'data:'], $changed->sources('img-src'));
    }

    public function testWithoutRemovesOnlyFromTheCopy(): void
    {
        $original = new Policy(['img-src' => ["'self'"], 'font-src' => ['data:']]);

        $changed = $original->without('img-src');

        $this->assertSame(['font-src'], $changed->names());
        $this->assertSame(['img-src', 'font-src'], $original->names());
    }

    public function testRendersHeaderValueAndItsSize(): void
    {
        $policy = new Policy(['default-src' => ["'self'"], 'upgrade-insecure-requests' => []]);

        $this->assertSame("default-src 'self'; upgrade-insecure-requests", $policy->toHeaderValue());
        $this->assertSame(strlen("default-src 'self'; upgrade-insecure-requests"), $policy->byteLength());
    }
}
