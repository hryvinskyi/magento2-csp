<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Integration\Model\Report;

use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Api\Data\ViolationInterface;
use Hryvinskyi\Csp\Api\Data\ViolationInterfaceFactory;
use Hryvinskyi\Csp\Model\Report\ReportEndpointUrl;
use Hryvinskyi\Csp\Model\Report\ViolationFilter;
use Hryvinskyi\Csp\Model\Store\AdminLocation;
use Hryvinskyi\Csp\Model\Store\StoreHostsProvider;
use Hryvinskyi\Csp\Test\Integration\ResolvesServices;
use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * The admin is located the way the backend builds its own URLs, through the real store and config objects.
 *
 * Each test serves the admin from its own host while the storefront's website overrides its base URL, and adds store
 * codes to URLs. The test framework keeps each scope's resolved values apart, so the values the admin store inherits
 * from the default scope are set on the admin store itself.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 */
class AdminReportLocationTest extends TestCase
{
    use ResolvesServices;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        // Store objects keep the base URLs they computed for earlier tests.
        $this->service(StoreManagerInterface::class)->reinitStores();
    }

    /**
     * @magentoConfigFixture admin_store web/secure/base_url https://admin.example.com/
     * @magentoConfigFixture admin_store web/secure/base_link_url https://admin.example.com/
     * @magentoConfigFixture admin_store web/url/use_store 1
     * @magentoConfigFixture admin_store web/seo/use_rewrites 1
     * @magentoConfigFixture default_store web/secure/base_url https://www.example.com/
     * @magentoConfigFixture default_store web/secure/base_link_url https://www.example.com/
     * @magentoConfigFixture default_store web/url/use_store 1
     * @magentoConfigFixture default_store web/seo/use_rewrites 1
     */
    public function testAdminReportsGoToTheAdminHostWithoutAStoreCode(): void
    {
        $urls = $this->newInstance(ReportEndpointUrl::class, ['adminLocation' => $this->newInstance(AdminLocation::class)]);

        $this->assertSame('https://www.example.com/default/csp_report_watch', $urls->forStorefront());
        $this->assertSame('https://admin.example.com/csp_report_watch/admin/index', $urls->forAdmin());
    }

    /**
     * @magentoConfigFixture admin_store web/secure/base_url https://admin.example.com/
     * @magentoConfigFixture admin_store web/secure/base_link_url https://admin.example.com/
     * @magentoConfigFixture admin_store web/url/use_store 1
     * @magentoConfigFixture admin_store web/seo/use_rewrites 1
     * @magentoConfigFixture default_store web/secure/base_url https://www.example.com/
     * @magentoConfigFixture default_store web/secure/base_link_url https://www.example.com/
     * @magentoConfigFixture default_store web/url/use_store 1
     * @magentoConfigFixture default_store web/seo/use_rewrites 1
     */
    public function testAcceptsReportsFromAdminPagesAndNotFromTheStorefrontHost(): void
    {
        $filter = $this->newInstance(ViolationFilter::class, [
            'storeHostsProvider' => $this->newInstance(StoreHostsProvider::class),
            'adminLocation' => $this->newInstance(AdminLocation::class),
        ]);
        $adminPage = 'https://admin.example.com/' . $this->adminFrontName() . '/catalog/product/';

        $this->assertTrue($filter->accepts($this->violation($adminPage), Area::ADMINHTML, 0));
        $this->assertFalse(
            $filter->accepts($this->violation('https://www.example.com/' . $this->adminFrontName() . '/'), Area::ADMINHTML, 0)
        );
    }

    /**
     * Front name the admin is served under in this installation.
     *
     * @return string
     */
    private function adminFrontName(): string
    {
        $frontName = $this->service(FrontNameResolver::class)->getFrontName();

        return is_string($frontName) ? $frontName : '';
    }

    /**
     * Inline style violation reported by a page.
     *
     * @param string $documentUri
     * @return ViolationInterface
     */
    private function violation(string $documentUri): ViolationInterface
    {
        return $this->service(ViolationInterfaceFactory::class)->create([
            'documentUri' => $documentUri,
            'blockedUri' => 'inline',
            'effectiveDirective' => 'style-src-attr',
        ]);
    }
}
