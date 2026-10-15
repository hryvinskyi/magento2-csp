<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Policy\Optimization;

use Hryvinskyi\Csp\Api\Config\OptimizationConfigInterface;
use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\Optimization\RemoveRedundantDirectives;
use Hryvinskyi\Csp\Test\Unit\Model\Policy\ParsesPolicies;
use Hryvinskyi\Csp\Test\Unit\Support\AssertsBrowserDecisions;
use PHPUnit\Framework\TestCase;

class RemoveRedundantDirectivesTest extends TestCase
{
    use ParsesPolicies;
    use AssertsBrowserDecisions;

    private RemoveRedundantDirectives $step;

    protected function setUp(): void
    {
        $config = $this->createStub(OptimizationConfigInterface::class);
        $config->method('isRedundantDirectiveRemovalEnabled')->willReturn(true);
        $this->step = new RemoveRedundantDirectives($config, new DirectiveCatalog(), $this->equivalence());
    }

    public function testRemovesDirectivesThatAllowWhatTheirFallbackAllows(): void
    {
        $original = $this->parsePolicy(
            "default-src 'self' 'unsafe-inline' 'unsafe-eval'; img-src 'self' 'unsafe-inline' data:; "
            . "media-src 'self' 'unsafe-inline'; object-src 'self' 'unsafe-inline'; "
            . "connect-src 'self' 'unsafe-inline' api.example.com; frame-ancestors 'self'"
        );

        $optimized = $this->step->apply($original);

        $this->assertSame(['default-src', 'img-src', 'connect-src', 'frame-ancestors'], $optimized->names());
        $this->assertSameBrowserDecisions([$original], [$optimized]);
        $this->assertSameBrowserDecisions([$original], [$optimized], 'http');
    }

    /**
     * @dataProvider trapPolicies
     * @param string $header
     */
    public function testKeepsWhatThePageAllowsInTheTraps(string $header): void
    {
        $original = $this->parsePolicy($header);

        $optimized = $this->step->apply($original);

        $this->assertSameBrowserDecisions([$original], [$optimized]);
        $this->assertSameBrowserDecisions([$original], [$optimized], 'http');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function trapPolicies(): array
    {
        return [
            'child-src equal to default-src while script-src is wider' => [
                "default-src 'self'; script-src 'self' cdn.example.com; child-src 'self'",
            ],
            'script-src equal to default-src except unsafe-eval' => [
                "default-src 'self' cdn.example.com; script-src 'self' cdn.example.com 'unsafe-eval'",
            ],
            'script-src equal to default-src except unsafe-hashes' => [
                "default-src 'self' 'sha256-AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA='; "
                . "script-src 'self' 'sha256-AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=' 'unsafe-hashes'",
            ],
            'strict-dynamic only in script-src' => [
                "default-src 'self' cdn.example.com; script-src 'self' cdn.example.com 'strict-dynamic'",
            ],
            '1.3.1 default-src consolidation input' => [
                "default-src 'self'; font-src 'self' cdn.example.com; connect-src 'self' cdn.example.com; "
                . "script-src 'self' cdn.example.com js.example.net 'unsafe-eval'",
            ],
            'non-fetch directives' => [
                "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'",
            ],
        ];
    }

    public function testNeverChangesDefaultSrcOrNonFetchDirectives(): void
    {
        $original = $this->parsePolicy(
            "default-src 'self'; font-src 'self' cdn.example.com; base-uri 'self'; form-action 'self'"
        );

        $optimized = $this->step->apply($original);

        $this->assertSame($original->toHeaderValue(), $optimized->toHeaderValue());
    }
}
