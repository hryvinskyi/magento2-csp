<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Policy;

use PHPUnit\Framework\TestCase;

class PolicyParserTest extends TestCase
{
    use ParsesPolicies;

    public function testParsesDirectivesInHeaderOrder(): void
    {
        $policy = $this->parsePolicy("default-src 'self'; img-src 'self' data: cdn.example.com;script-src 'self'");

        $this->assertSame(['default-src', 'img-src', 'script-src'], $policy->names());
        $this->assertSame(["'self'", 'data:', 'cdn.example.com'], $policy->sources('img-src'));
    }

    public function testFirstOccurrenceOfARepeatedDirectiveWins(): void
    {
        $policy = $this->parsePolicy('img-src a.example.com; IMG-SRC b.example.com');

        $this->assertSame(['img-src'], $policy->names());
        $this->assertSame(['a.example.com'], $policy->sources('img-src'));
    }

    public function testKeepsDirectivesWithoutValue(): void
    {
        $policy = $this->parsePolicy("upgrade-insecure-requests; default-src 'self'");

        $this->assertTrue($policy->has('upgrade-insecure-requests'));
        $this->assertSame([], $policy->sources('upgrade-insecure-requests'));
        $this->assertSame("upgrade-insecure-requests; default-src 'self'", $policy->toHeaderValue());
    }

    public function testIgnoresEmptySegmentsAndExtraWhitespace(): void
    {
        $policy = $this->parsePolicy(" ;  script-src\t'self'   example.com ;; ");

        $this->assertSame(['script-src'], $policy->names());
        $this->assertSame(["'self'", 'example.com'], $policy->sources('script-src'));
    }
}
