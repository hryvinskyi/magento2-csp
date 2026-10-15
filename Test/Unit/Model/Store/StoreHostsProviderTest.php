<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Store;

use Hryvinskyi\Csp\Model\Store\StoreHostsProvider;
use Hryvinskyi\Csp\Model\Store\UrlHost;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class StoreHostsProviderTest extends TestCase
{
    public function testCollectsLinkStaticAndMediaHostsOfEveryStore(): void
    {
        $german = $this->store(1, [
            UrlInterface::URL_TYPE_LINK => ['http://shop.example.com:80/de/', 'https://shop.example.com/de/'],
            UrlInterface::URL_TYPE_STATIC => ['http://static.example.com/', 'https://static.example.com/'],
            UrlInterface::URL_TYPE_MEDIA => ['http://Media.Example.com:8080/', 'https://media.example.com/'],
        ]);
        $french = $this->store(2, [
            UrlInterface::URL_TYPE_LINK => ['http://shop.example.fr/', 'https://shop.example.fr/'],
            UrlInterface::URL_TYPE_STATIC => ['/static/', '/static/'],
            UrlInterface::URL_TYPE_MEDIA => ['http://media.example.com/', 'https://media.example.com/'],
        ]);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn([1 => $german, 2 => $french]);
        $storeManager->method('getStore')->willReturn($french);
        $provider = new StoreHostsProvider($storeManager, new UrlHost(), $this->createStub(LoggerInterface::class));

        $this->assertSame(
            ['shop.example.com', 'static.example.com', 'media.example.com:8080', 'media.example.com', 'shop.example.fr'],
            $provider->storefrontHosts()
        );
        $this->assertSame(['shop.example.fr', 'media.example.com'], $provider->storeHosts(2));
    }

    /**
     * @param int $id
     * @param array<string, array{string, string}> $urls Unsecure and secure base URL per type
     * @return Store
     */
    private function store(int $id, array $urls): Store
    {
        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn($id);
        $store->method('getBaseUrl')->willReturnCallback(
            static fn (string $type, ?bool $secure): string => $urls[$type][$secure ? 1 : 0]
        );

        return $store;
    }
}
