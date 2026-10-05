<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Store;

use Hryvinskyi\Csp\Model\Store\AdminLocation;
use Hryvinskyi\Csp\Model\Store\UrlHost;
use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class AdminLocationTest extends TestCase
{
    public function testUsesTheDefaultStoreViewUnlessACustomAdminUrlIsSet(): void
    {
        $location = $this->location(false, '');

        $this->assertSame('https://shop.example.com/shop/', $location->baseUrl());
        $this->assertSame('shop.example.com', $location->host());
        $this->assertSame('/shop/backend/', $location->pathPrefix());
    }

    public function testUsesTheCustomAdminUrl(): void
    {
        $location = $this->location(true, 'https://admin.example.com:8443');

        $this->assertSame('https://admin.example.com:8443/', $location->baseUrl());
        $this->assertSame('admin.example.com:8443', $location->host());
        $this->assertSame('/backend/', $location->pathPrefix());
    }

    public function testUrlHostDropsDefaultPortsOnly(): void
    {
        $urlHost = new UrlHost();

        $this->assertSame('shop.example.com', $urlHost->of('https://Shop.Example.com:443/a'));
        $this->assertSame('shop.example.com:80', $urlHost->of('https://shop.example.com:80/'));
        $this->assertNull($urlHost->of('inline'));
    }

    /**
     * @param bool $useCustom
     * @param string $customUrl
     * @return AdminLocation
     */
    private function location(bool $useCustom, string $customUrl): AdminLocation
    {
        $config = $this->createStub(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturn($useCustom);
        $config->method('getValue')->willReturn($customUrl);
        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.example.com/shop/');
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getDefaultStoreView')->willReturn($store);
        $frontNameResolver = $this->createStub(FrontNameResolver::class);
        $frontNameResolver->method('getFrontName')->willReturn('backend');

        return new AdminLocation($config, $storeManager, $frontNameResolver, new UrlHost());
    }
}
