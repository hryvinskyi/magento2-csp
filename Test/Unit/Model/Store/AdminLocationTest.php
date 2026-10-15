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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class AdminLocationTest extends TestCase
{
    public function testUsesTheAdminStoreUnlessACustomAdminUrlIsSet(): void
    {
        $location = $this->location(false, '', $this->adminStore('https://admin.example.com/shop/'));

        $this->assertSame('https://admin.example.com/shop/', $location->baseUrl());
        $this->assertSame('admin.example.com', $location->host());
        $this->assertSame('/shop/backend/', $location->pathPrefix());
    }

    public function testIgnoresTheStorefrontsWebsiteBaseUrlOverride(): void
    {
        $storeManager = $this->storeManager($this->adminStore('https://admin.example.com/'));
        $storeManager->method('getDefaultStoreView')->willReturn($this->adminStore('https://www.example.com/'));

        $location = $this->locationWith(false, '', $storeManager);

        $this->assertSame('admin.example.com', $location->host());
    }

    public function testNeverPrefixesTheAdminPathWithAStoreCode(): void
    {
        $location = $this->location(false, '', $this->adminStore('https://admin.example.com/', 'https://admin.example.com/admin/'));

        $this->assertSame('https://admin.example.com/', $location->baseUrl());
        $this->assertSame('/backend/', $location->pathPrefix());
    }

    public function testUsesTheCustomAdminUrl(): void
    {
        $location = $this->location(true, 'https://admin.example.com:8443', $this->adminStore('https://shop.example.com/'));

        $this->assertSame('https://admin.example.com:8443/', $location->baseUrl());
        $this->assertSame('admin.example.com:8443', $location->host());
        $this->assertSame('/backend/', $location->pathPrefix());
    }

    public function testFallsBackToTheRootWhenTheAdminStoreCannotBeRead(): void
    {
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willThrowException(new NoSuchEntityException());

        $location = $this->locationWith(false, '', $storeManager);

        $this->assertSame('/', $location->baseUrl());
        $this->assertNull($location->host());
        $this->assertSame('/backend/', $location->pathPrefix());
    }

    /**
     * @param bool $useCustom
     * @param string $customUrl
     * @param Store $adminStore
     * @return AdminLocation
     */
    private function location(bool $useCustom, string $customUrl, Store $adminStore): AdminLocation
    {
        return $this->locationWith($useCustom, $customUrl, $this->storeManager($adminStore));
    }

    /**
     * @param bool $useCustom
     * @param string $customUrl
     * @param StoreManagerInterface $storeManager
     * @return AdminLocation
     */
    private function locationWith(bool $useCustom, string $customUrl, StoreManagerInterface $storeManager): AdminLocation
    {
        $config = $this->createStub(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturn($useCustom);
        $config->method('getValue')->willReturn($customUrl);
        $frontNameResolver = $this->createStub(FrontNameResolver::class);
        $frontNameResolver->method('getFrontName')->willReturn('backend');

        return new AdminLocation($config, $storeManager, $frontNameResolver, new UrlHost());
    }

    /**
     * Store manager whose admin store is the given one; any other store lookup fails.
     *
     * @param Store $adminStore
     * @return StoreManagerInterface&\PHPUnit\Framework\MockObject\Stub
     */
    private function storeManager(Store $adminStore): StoreManagerInterface
    {
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturnCallback(
            static function (mixed $storeId = null) use ($adminStore): Store {
                if ($storeId !== Store::ADMIN_CODE) {
                    throw new NoSuchEntityException();
                }

                return $adminStore;
            }
        );

        return $storeManager;
    }

    /**
     * Store answering its secure base URLs; the link URL carries a store code when one is given.
     *
     * @param string $directLinkUrl
     * @param string|null $linkUrl
     * @return Store
     */
    private function adminStore(string $directLinkUrl, ?string $linkUrl = null): Store
    {
        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturnCallback(
            static fn (string $type, ?bool $secure = null): string => $type === UrlInterface::URL_TYPE_LINK
                ? ($linkUrl ?? $directLinkUrl)
                : $directLinkUrl
        );

        return $store;
    }
}
