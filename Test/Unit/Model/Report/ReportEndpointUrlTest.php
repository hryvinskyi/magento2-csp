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
        $urls = $this->urls($this->storeManager('https://shop.example/de/', 'https://admin.shop.example/'), $this->config(false));

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

    public function testAdminUrlUsesTheAdminStoreAndNotTheStorefrontsWebsiteBaseUrl(): void
    {
        $urls = $this->urls($this->storeManager('https://www.shop.example/', 'https://admin.shop.example/'), $this->config(false));

        $this->assertSame('https://admin.shop.example/csp_report_watch/admin/index', $urls->forAdmin());
    }

    public function testAdminUrlCarriesNoStoreCode(): void
    {
        $urls = $this->urls(
            $this->storeManager('https://shop.example/default/', 'https://shop.example/', 'https://shop.example/admin/'),
            $this->config(false)
        );

        $this->assertSame('https://shop.example/default/csp_report_watch', $urls->forStorefront());
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
     * Store manager whose current store is a storefront and whose admin store answers the admin URLs.
     *
     * @param string $currentUrl Secure link URL of the current store
     * @param string $adminDirectLinkUrl Secure link URL of the admin store without a store code
     * @param string|null $adminLinkUrl Secure link URL of the admin store with the store code, when one is added
     * @return StoreManagerInterface
     */
    private function storeManager(string $currentUrl, string $adminDirectLinkUrl, ?string $adminLinkUrl = null): StoreManagerInterface
    {
        $current = $this->store(2, $currentUrl, $currentUrl);
        $admin = $this->store(0, $adminLinkUrl ?? $adminDirectLinkUrl, $adminDirectLinkUrl);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturnCallback(
            static fn (mixed $storeId = null): Store => $storeId === Store::ADMIN_CODE ? $admin : $current
        );
        $storeManager->method('getDefaultStoreView')->willReturn($current);

        return $storeManager;
    }

    /**
     * @param int $id
     * @param string $secureLinkUrl
     * @param string $secureDirectLinkUrl
     * @return Store
     */
    private function store(int $id, string $secureLinkUrl, string $secureDirectLinkUrl): Store
    {
        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn($id);
        $store->method('getBaseUrl')->willReturnCallback(
            static fn (string $type, ?bool $secure = null): string => match (true) {
                $secure !== true => 'http://insecure.shop.example/',
                $type === UrlInterface::URL_TYPE_LINK => $secureLinkUrl,
                default => $secureDirectLinkUrl,
            }
        );

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
