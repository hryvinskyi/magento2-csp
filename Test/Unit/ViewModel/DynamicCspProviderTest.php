<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\ViewModel;

use Hryvinskyi\Csp\Model\DynamicPolicyRegistry;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Test\Unit\Support\RealFetchPolicyFactory;
use Hryvinskyi\Csp\ViewModel\DynamicCspProvider;
use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Collector\FetchPolicyMerger;
use Magento\Csp\Model\Policy\FetchPolicy;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Calls the view model the way 1.x templates do, through the real registry and Magento's dynamic collector.
 */
class DynamicCspProviderTest extends TestCase
{
    private const HASH = 'ulSR2mFrdWVXSgeR4TBKNBoRfFVHQ+DXt7P2Ek1YyPo=';

    private DynamicCollector $collector;

    private DynamicCspProvider $provider;

    protected function setUp(): void
    {
        $this->collector = new DynamicCollector(new FetchPolicyMerger());
        $this->provider = new DynamicCspProvider(new DynamicPolicyRegistry(
            $this->collector,
            new RealFetchPolicyFactory(),
            new HostSourceParser(),
            new NullLogger()
        ));
    }

    public function testEveryHostMethodAllowsItsDirectiveAndIgnoresTheKey(): void
    {
        $this->provider->addScriptSrc(['script.example.com'], false, 'legacy-key');
        $this->provider->addStyleSrc(['style.example.com'], false, 'legacy-key');
        $this->provider->addImgSrc(['img.example.com'], false, 'legacy-key');
        $this->provider->addFontSrc(['font.example.com'], false, 'legacy-key');
        $this->provider->addConnectSrc(['connect.example.com'], false, 'legacy-key');
        $this->provider->addFrameSrc(['frame.example.com'], true, 'legacy-key');
        $this->provider->addCustomPolicy('media-src', ['media.example.com'], false, 'legacy-key');

        $this->assertSame([
            'script-src' => ['script.example.com'],
            'style-src' => ['style.example.com'],
            'img-src' => ['img.example.com'],
            'font-src' => ['font.example.com'],
            'connect-src' => ['connect.example.com'],
            'frame-src' => ['frame.example.com'],
            'media-src' => ['media.example.com'],
        ], $this->hostsByDirective());
        $this->assertTrue($this->policy('frame-src')->isSelfAllowed());
    }

    public function testHashMethodsTakeThe1xDigestToAlgorithmMap(): void
    {
        $this->provider->addScriptSrcHash([self::HASH => 'sha256'], false, 'legacy-key');
        $this->provider->addStyleSrcHash([self::HASH], false, 'legacy-key');
        $this->provider->addCustomPolicyHash('img-src', [self::HASH => 'sha256']);
        $this->provider->addMixedPolicy('connect-src', ['api.example.com'], [self::HASH => 'sha256']);

        foreach (['script-src', 'style-src', 'img-src', 'connect-src'] as $directive) {
            $this->assertSame([self::HASH => 'sha256'], $this->policy($directive)->getHashes(), $directive);
        }
        $this->assertSame(['api.example.com'], $this->policy('connect-src')->getHostSources());
    }

    public function testInvalidInputFromATemplateIsSkippedWithoutThrowing(): void
    {
        $this->provider->addCustomPolicy('worker-src', ['cdn.example.com']);
        $this->provider->addCustomPolicyHash('script-src-elem', [self::HASH]);
        $this->provider->addCustomPolicyHash('style-src-attr', [self::HASH => 'sha256']);
        $this->provider->addScriptSrcHash(['sha256']);
        $this->provider->addImgSrc(["cdn.example.com; object-src *", 'img.example.com']);

        $this->assertSame(['img-src' => ['img.example.com']], $this->hostsByDirective());
    }

    /**
     * Host sources the collector holds, per directive.
     *
     * @return array<string, array<string>>
     */
    private function hostsByDirective(): array
    {
        $hosts = [];
        foreach ($this->collector->collect() as $policy) {
            $this->assertInstanceOf(FetchPolicy::class, $policy);
            $hosts[$policy->getId()] = $policy->getHostSources();
        }

        return $hosts;
    }

    /**
     * The collected policy of one directive.
     *
     * @param string $directive
     * @return FetchPolicy
     */
    private function policy(string $directive): FetchPolicy
    {
        foreach ($this->collector->collect() as $policy) {
            if ($policy instanceof FetchPolicy && $policy->getId() === $directive) {
                return $policy;
            }
        }
        $this->fail('No policy for ' . $directive);
    }
}
