<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Policy;

use Hryvinskyi\Csp\Api\Config\OptimizationConfigInterface;
use Hryvinskyi\Csp\Api\Data\PolicyInterface;
use Hryvinskyi\Csp\Api\PolicyOptimizationStepInterface;
use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\DocumentSchemeInterface;
use Hryvinskyi\Csp\Model\Policy\HostSourceCoverage;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Policy\Optimization\ConsolidateTrustedSubdomains;
use Hryvinskyi\Csp\Model\Policy\Optimization\RemoveCoveredHosts;
use Hryvinskyi\Csp\Model\Policy\Optimization\RemoveDuplicateSources;
use Hryvinskyi\Csp\Model\Policy\Optimization\RemoveRedundantDirectives;
use Hryvinskyi\Csp\Model\Policy\Optimization\StripHostSchemes;
use Hryvinskyi\Csp\Model\Policy\PolicyOptimizer;
use Hryvinskyi\Csp\Test\Unit\Support\AssertsBrowserDecisions;
use PHPUnit\Framework\TestCase;

class PolicyOptimizerTest extends TestCase
{
    use ParsesPolicies;
    use AssertsBrowserDecisions;

    /**
     * The 1.3.1 regressions: websocket hosts, path scopes, registry suffixes, shared hosting, `*` next to nonces.
     */
    private const REGRESSION_HEADER = "default-src 'self'; "
        . "connect-src 'self' wss://ws.example.com https://region1.google-analytics.com "
        . 'https://www.google-analytics.com https://stats.google-analytics.com; '
        . "script-src 'self' https://www.google.com/recaptcha/ royalmail.co.uk parcelforce.co.uk shop.co.uk "
        . 'd1abc.cloudfront.net d2def.cloudfront.net d3ghi.cloudfront.net; '
        . "style-src * 'unsafe-inline' 'nonce-abc123' 'sha256-AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA='; "
        . "font-src 'self' cdn.example.com; img-src 'self' cdn.example.com";

    public function testDoesNothingWhenOptimisationIsOff(): void
    {
        $config = $this->createStub(OptimizationConfigInterface::class);
        $config->method('isValueOptimizationEnabled')->willReturn(false);
        $step = $this->createMock(PolicyOptimizationStepInterface::class);
        $step->expects($this->never())->method('apply');

        $policy = $this->parsePolicy("img-src 'self' 'self'");

        $this->assertSame($policy, (new PolicyOptimizer($config, ['step' => $step]))->optimize($policy));
    }

    public function testRunsEnabledStepsInOrderAndSkipsDisabledOnes(): void
    {
        $config = $this->createStub(OptimizationConfigInterface::class);
        $config->method('isValueOptimizationEnabled')->willReturn(true);

        $optimized = (new PolicyOptimizer($config, [
            'first' => $this->appendingStep('a.example.com', true),
            'disabled' => $this->appendingStep('b.example.com', false),
            'last' => $this->appendingStep('c.example.com', true),
        ]))->optimize($this->parsePolicy("img-src 'self'"));

        $this->assertSame(["'self'", 'a.example.com', 'c.example.com'], $optimized->sources('img-src'));
    }

    public function testEveryPreservingStepTogetherKeepsWhatThePageAllows(): void
    {
        $original = $this->parsePolicy(self::REGRESSION_HEADER);

        $optimized = $this->fullOptimizer([])->optimize($original);

        $this->assertSameBrowserDecisions([$original], [$optimized]);
    }

    public function testRegressionsStayFixedWithEveryStepOn(): void
    {
        $optimized = $this->fullOptimizer(['google-analytics.com'])->optimize($this->parsePolicy(self::REGRESSION_HEADER));

        $this->assertSame(["'self'", 'wss://ws.example.com', '*.google-analytics.com'], $optimized->sources('connect-src'));
        $this->assertSame(
            [
                "'self'",
                'www.google.com/recaptcha/',
                'royalmail.co.uk',
                'parcelforce.co.uk',
                'shop.co.uk',
                'd1abc.cloudfront.net',
                'd2def.cloudfront.net',
                'd3ghi.cloudfront.net',
            ],
            $optimized->sources('script-src')
        );
        $this->assertSame(
            ['*', "'unsafe-inline'", "'nonce-abc123'", "'sha256-AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA='"],
            $optimized->sources('style-src')
        );
        $this->assertSame(["'self'"], $optimized->sources('default-src'));
    }

    /**
     * @param string $host
     * @param bool $enabled
     * @return PolicyOptimizationStepInterface
     */
    private function appendingStep(string $host, bool $enabled): PolicyOptimizationStepInterface
    {
        return new class ($host, $enabled) implements PolicyOptimizationStepInterface {
            /**
             * @param string $host
             * @param bool $enabled
             */
            public function __construct(private readonly string $host, private readonly bool $enabled)
            {
            }

            /**
             * @inheritDoc
             */
            public function isEnabled(): bool
            {
                return $this->enabled;
            }

            /**
             * @inheritDoc
             */
            public function apply(PolicyInterface $policy): PolicyInterface
            {
                return $policy->withSources('img-src', [...$policy->sources('img-src'), $this->host]);
            }
        };
    }

    /**
     * Optimizer with every step on, as wired in di.xml.
     *
     * @param list<string> $trustedParents
     * @return PolicyOptimizer
     */
    private function fullOptimizer(array $trustedParents): PolicyOptimizer
    {
        $config = $this->createStub(OptimizationConfigInterface::class);
        $config->method('isValueOptimizationEnabled')->willReturn(true);
        $config->method('isSchemeStrippingEnabled')->willReturn(true);
        $config->method('isRedundantWildcardRemovalEnabled')->willReturn(true);
        $config->method('isRedundantDirectiveRemovalEnabled')->willReturn(true);
        $config->method('isSubdomainWildcardConsolidationEnabled')->willReturn(true);
        $config->method('getSubdomainWildcardParents')->willReturn($trustedParents);
        $config->method('getSubdomainWildcardThreshold')->willReturn(3);
        $secure = $this->createStub(DocumentSchemeInterface::class);
        $secure->method('isSecure')->willReturn(true);

        $catalog = new DirectiveCatalog();
        $parser = new HostSourceParser();

        return new PolicyOptimizer($config, [
            'strip_schemes' => new StripHostSchemes($config, $catalog, $parser, $secure),
            'duplicates' => new RemoveDuplicateSources($catalog, $parser),
            'covered_hosts' => new RemoveCoveredHosts($config, $catalog, $parser, new HostSourceCoverage($parser)),
            'trusted_subdomains' => new ConsolidateTrustedSubdomains($config, $catalog, $parser),
            'redundant_directives' => new RemoveRedundantDirectives($config, $catalog, $this->equivalence()),
        ]);
    }
}
