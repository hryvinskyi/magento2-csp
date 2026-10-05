<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Policy\Optimization;

use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Policy\Optimization\RemoveDuplicateSources;
use Hryvinskyi\Csp\Test\Unit\Model\Policy\ParsesPolicies;
use Hryvinskyi\Csp\Test\Unit\Support\AssertsBrowserDecisions;
use PHPUnit\Framework\TestCase;

class RemoveDuplicateSourcesTest extends TestCase
{
    use ParsesPolicies;
    use AssertsBrowserDecisions;

    private RemoveDuplicateSources $step;

    protected function setUp(): void
    {
        $this->step = new RemoveDuplicateSources(new DirectiveCatalog(), new HostSourceParser());
    }

    public function testRemovesCaseInsensitiveRepeatsOfKeywordsSchemesAndHosts(): void
    {
        $policy = $this->step->apply($this->parsePolicy(
            "img-src 'self' data: DATA: 'SELF' cdn.example.com CDN.Example.com"
        ));

        $this->assertSame(["'self'", 'data:', 'cdn.example.com'], $policy->sources('img-src'));
    }

    public function testComparesNoncesHashesAndPathsExactly(): void
    {
        $policy = $this->step->apply($this->parsePolicy(
            "script-src 'nonce-AbC' 'nonce-abc' cdn.example.com/Lib/ cdn.example.com/lib/ 'nonce-AbC'"
        ));

        $this->assertSame(
            ["'nonce-AbC'", "'nonce-abc'", 'cdn.example.com/Lib/', 'cdn.example.com/lib/'],
            $policy->sources('script-src')
        );
    }

    public function testKeepsFlagDirectivesAndWhatThePageAllows(): void
    {
        $original = $this->parsePolicy(
            "upgrade-insecure-requests; default-src 'self' 'self'; script-src 'self' 'nonce-abc123' 'self' "
            . "https://cdn.example.com HTTPS://CDN.EXAMPLE.COM; connect-src wss://ws.example.com wss://ws.example.com"
        );

        $optimized = $this->step->apply($original);

        $this->assertTrue($optimized->has('upgrade-insecure-requests'));
        $this->assertSameBrowserDecisions([$original], [$optimized]);
        $this->assertSameBrowserDecisions([$original], [$optimized], 'http');
    }
}
