<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Report;

use Hryvinskyi\Csp\Model\Report\ReportEndpointUrl;
use Hryvinskyi\Csp\Model\Store\AdminLocation;
use Hryvinskyi\Csp\Model\Store\UrlHost;
use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class ReportEndpointUrlTest extends TestCase
{
    public function testStorefrontUrlUsesTheCurrentStoresSecureLinkUrl(): void
    {
        $urls = $this->urls($this->storeManager('https://shop.example/de/', 'https://shop.example/'), $this->config(false));

        $this->assertSame('https://shop.example/de/csp_report_watch', $urls->forStorefront());
    }

    public function testAdminUrlUsesTheCustomAdminHostWhenConfigured(): void
    {
        $urls = $this->urls(
            $this->storeManager('https://shop.example/de/', 'https://shop.example/'),
            $this->config(true, 'https://admin.shop.example/')
        );

        $this->assertSame('https://admin.shop.example/csp_report_watch/admin/index', $urls->forAdmin());
    }

    public function testAdminUrlFallsBackToTheDefaultStoreView(): void
    {
        $urls = $this->urls($this->storeManager('https://shop.example/de/', 'https://shop.example/'), $this->config(false));

        $this->assertSame('https://shop.example/csp_report_watch/admin/index', $urls->forAdmin());
    }

    /**
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $config
     * @return ReportEndpointUrl
     */
    private function urls(StoreManagerInterface $storeManager, ScopeConfigInterface $config): ReportEndpointUrl
    {
        return new ReportEndpointUrl(
            $storeManager,
            new AdminLocation($config, $storeManager, $this->createStub(FrontNameResolver::class), new UrlHost())
        );
    }

    /**
     * @param string $currentUrl
     * @param string $defaultUrl
     * @return StoreManagerInterface
     */
    private function storeManager(string $currentUrl, string $defaultUrl): StoreManagerInterface
    {
        $current = $this->store(2, $currentUrl);
        $default = $this->store(1, $defaultUrl);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($current);
        $storeManager->method('getDefaultStoreView')->willReturn($default);

        return $storeManager;
    }

    /**
     * @param int $id
     * @param string $secureLinkUrl
     * @return Store
     */
    private function store(int $id, string $secureLinkUrl): Store
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn($id);
        $store->method('getBaseUrl')->with(UrlInterface::URL_TYPE_LINK, true)->willReturn($secureLinkUrl);

        return $store;
    }

    /**
     * @param bool $useCustomAdminUrl
     * @param string $customAdminUrl
     * @return ScopeConfigInterface
     */
    private function config(bool $useCustomAdminUrl, string $customAdminUrl = ''): ScopeConfigInterface
    {
        $config = $this->createStub(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturn($useCustomAdminUrl);
        $config->method('getValue')->willReturn($customAdminUrl);

        return $config;
    }
}
