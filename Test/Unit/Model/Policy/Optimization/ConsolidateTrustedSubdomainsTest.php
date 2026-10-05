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
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Policy\Optimization\ConsolidateTrustedSubdomains;
use Hryvinskyi\Csp\Test\Unit\Model\Policy\ParsesPolicies;
use Hryvinskyi\Csp\Test\Unit\Support\AssertsBrowserDecisions;
use PHPUnit\Framework\TestCase;

class ConsolidateTrustedSubdomainsTest extends TestCase
{
    use ParsesPolicies;
    use AssertsBrowserDecisions;

    public function testIsDisabledWithoutTrustedParents(): void
    {
        $this->assertFalse($this->step([])->isEnabled());
        $this->assertTrue($this->step(['example.com'])->isEnabled());
    }

    public function testNeverConsolidatesRegistrableDomainsUnderARegistrySuffix(): void
    {
        $original = $this->parsePolicy("script-src 'self' royalmail.co.uk parcelforce.co.uk shop.co.uk");

        $optimized = $this->step(['google-analytics.com'])->apply($original);

        $this->assertSame($original->sources('script-src'), $optimized->sources('script-src'));
    }

    public function testNeverConsolidatesSharedHostingUnlessTrusted(): void
    {
        $original = $this->parsePolicy('script-src d1abc.cloudfront.net d2def.cloudfront.net d3ghi.cloudfront.net');

        $optimized = $this->step(['google-analytics.com'])->apply($original);

        $this->assertSame([], $this->decisionChanges([$original], [$optimized]));
    }

    public function testConsolidatesSubdomainsOfATrustedParentAtTheFirstPosition(): void
    {
        $policy = $this->step(['google-analytics.com'])->apply($this->parsePolicy(
            "connect-src 'self' region1.google-analytics.com www.google-analytics.com cdn.example.com "
            . 'stats.google-analytics.com'
        ));

        $this->assertSame(["'self'", '*.google-analytics.com', 'cdn.example.com'], $policy->sources('connect-src'));
    }

    public function testKeepsTheSchemeOfTheConsolidatedHosts(): void
    {
        $policy = $this->step(['example.com'])->apply($this->parsePolicy(
            'connect-src https://a.example.com https://b.example.com https://c.example.com d.example.com'
        ));

        $this->assertSame(['https://*.example.com', 'd.example.com'], $policy->sources('connect-src'));
    }

    public function testOnlyWidensTowardsTheTrustedParent(): void
    {
        $original = $this->parsePolicy("script-src 'self' a.example.com b.example.com c.example.com");

        $changes = $this->decisionChanges([$original], [$this->step(['example.com'])->apply($original)]);

        $this->assertNotSame([], $changes);
        foreach ($changes as $change) {
            $this->assertStringStartsWith('blocked→allowed: ', $change);
            $this->assertMatchesRegularExpression('~://[a-z0-9.\-]+\.example\.com[:/]~', $change);
        }
    }

    public function testRespectsTheThresholdPortsPathsAndOtherSchemes(): void
    {
        $original = $this->parsePolicy(
            'connect-src a.example.com b.example.com:8443 c.example.com/x wss://d.example.com e.example.com'
        );

        $this->assertSame(
            $original->sources('connect-src'),
            $this->step(['example.com'])->apply($original)->sources('connect-src')
        );
    }

    /**
     * @param list<string> $parents
     * @param int $threshold
     * @return ConsolidateTrustedSubdomains
     */
    private function step(array $parents, int $threshold = 3): ConsolidateTrustedSubdomains
    {
        $config = $this->createStub(OptimizationConfigInterface::class);
        $config->method('isSubdomainWildcardConsolidationEnabled')->willReturn(true);
        $config->method('getSubdomainWildcardParents')->willReturn($parents);
        $config->method('getSubdomainWildcardThreshold')->willReturn($threshold);

        return new ConsolidateTrustedSubdomains($config, new DirectiveCatalog(), new HostSourceParser());
    }
}
