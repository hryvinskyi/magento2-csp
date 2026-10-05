<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Collector;

use Hryvinskyi\Csp\Api\Config\RulesConfigInterface;
use Hryvinskyi\Csp\Model\Collector\StoreUrlCollector;
use Hryvinskyi\Csp\Model\Store\StoreHostsProviderInterface;
use Hryvinskyi\Csp\Test\Unit\Support\RealFetchPolicyFactory;
use Magento\Csp\Api\Data\PolicyInterface;
use Magento\Csp\Model\Policy\FetchPolicy;
use PHPUnit\Framework\TestCase;

class StoreUrlCollectorTest extends TestCase
{
    public function testAllowsEveryStoreHostInResourceDirectives(): void
    {
        $collected = $this->collector(true, ['shop.example.com', 'static.example.com:8080'])->collect();

        $this->assertSame(
            ['default-src', 'script-src', 'style-src', 'img-src', 'font-src', 'connect-src', 'media-src'],
            array_map(static fn (PolicyInterface $policy): string => $policy->getId(), $collected)
        );
        $this->assertInstanceOf(FetchPolicy::class, $collected[0]);
        $this->assertSame(['shop.example.com', 'static.example.com:8080'], $collected[0]->getHostSources());
    }

    public function testAddsNothingWhenDisabledOrWithoutHosts(): void
    {
        $this->assertSame([], $this->collector(false, ['shop.example.com'])->collect());
        $this->assertSame([], $this->collector(true, [])->collect());
    }

    /**
     * @param bool $enabled
     * @param list<string> $hosts
     * @return StoreUrlCollector
     */
    private function collector(bool $enabled, array $hosts): StoreUrlCollector
    {
        $config = $this->createStub(RulesConfigInterface::class);
        $config->method('isAddAllStorefrontUrls')->willReturn($enabled);
        $provider = $this->createStub(StoreHostsProviderInterface::class);
        $provider->method('storefrontHosts')->willReturn($hosts);

        return new StoreUrlCollector($config, $provider, new RealFetchPolicyFactory());
    }
}
