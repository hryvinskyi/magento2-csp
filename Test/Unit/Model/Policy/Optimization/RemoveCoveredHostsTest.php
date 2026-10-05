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
use Hryvinskyi\Csp\Model\Policy\HostSourceCoverage;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Policy\Optimization\RemoveCoveredHosts;
use Hryvinskyi\Csp\Test\Unit\Model\Policy\ParsesPolicies;
use Hryvinskyi\Csp\Test\Unit\Support\AssertsBrowserDecisions;
use PHPUnit\Framework\TestCase;

class RemoveCoveredHostsTest extends TestCase
{
    use ParsesPolicies;
    use AssertsBrowserDecisions;

    private RemoveCoveredHosts $step;

    protected function setUp(): void
    {
        $config = $this->createStub(OptimizationConfigInterface::class);
        $config->method('isRedundantWildcardRemovalEnabled')->willReturn(true);
        $parser = new HostSourceParser();
        $this->step = new RemoveCoveredHosts($config, new DirectiveCatalog(), $parser, new HostSourceCoverage($parser));
    }

    public function testRemovesSubdomainsCoveredByAWildcardButNotTheParent(): void
    {
        $policy = $this->step->apply($this->parsePolicy(
            "frame-ancestors 'self' *.example.com www.example.com example.com *.shop.example.com"
        ));

        $this->assertSame(["'self'", '*.example.com', 'example.com'], $policy->sources('frame-ancestors'));
    }

    public function testStarKeepsNoncesHashesKeywordsSchemesAndWebSockets(): void
    {
        $policy = $this->step->apply($this->parsePolicy(
            "script-src * 'unsafe-inline' 'nonce-abc123' 'sha256-AAAA' data: cdn.example.com wss://ws.example.com"
        ));

        $this->assertSame(
            ['*', "'unsafe-inline'", "'nonce-abc123'", "'sha256-AAAA'", 'data:', 'wss://ws.example.com'],
            $policy->sources('script-src')
        );
    }

    public function testKeepsHostsWithAPortOrSchemeTheWildcardDoesNotCover(): void
    {
        $policy = $this->step->apply($this->parsePolicy(
            'connect-src *.example.com a.example.com:8443 wss://ws.example.com http://b.example.com https://c.example.com'
        ));

        $this->assertSame(
            ['*.example.com', 'a.example.com:8443', 'wss://ws.example.com', 'http://b.example.com'],
            $policy->sources('connect-src')
        );
    }

    /**
     * @dataProvider pages
     * @param string $documentScheme
     */
    public function testKeepsWhatThePageAllows(string $documentScheme): void
    {
        $original = $this->parsePolicy(
            "default-src 'self'; script-src * 'unsafe-inline' 'nonce-abc123' cdn.example.com a.example.com; "
            . 'img-src *.example.com a.example.com https://a.b.example.com example.com a.example.com:8443 data:; '
            . 'connect-src *.example.com wss://ws.example.com http://cdn.example.com/lib/; '
            . 'frame-src *.example.com/lib/ cdn.example.com/lib/app.js cdn.example.com/other.js'
        );

        $this->assertSameBrowserDecisions([$original], [$this->step->apply($original)], $documentScheme);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function pages(): array
    {
        return ['https page' => ['https'], 'http page' => ['http']];
    }
}
