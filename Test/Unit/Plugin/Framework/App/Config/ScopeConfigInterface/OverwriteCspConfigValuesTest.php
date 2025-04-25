<?php
/**
 * Copyright (c) 2025. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Hryvinskyi\Csp\Plugin\Framework\App\Config\ScopeConfigInterface\OverwriteCspConfigValues;
use Hryvinskyi\Csp\Api\ConfigInterface as CspConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;

class OverwriteCspConfigValuesTest extends TestCase
{
    private CspConfig $cspConfig;
    private StoreManagerInterface $storeManager;
    private OverwriteCspConfigValues $plugin;
    private ScopeConfigInterface $subject;

    protected function setUp(): void
    {
        $this->cspConfig = $this->createMock(CspConfig::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->subject = $this->createMock(ScopeConfigInterface::class);

        $this->plugin = new OverwriteCspConfigValues(
            $this->cspConfig,
            $this->storeManager
        );
    }

    public function testNonCspPathReturnsOriginal(): void
    {
        $value = 'foo';
        $path  = 'some/other/path';
        $result = $this->plugin->afterGetValue($this->subject, $value, $path);
        $this->assertSame('foo', $result);
    }

    public function testReportUriDisabledKeepsOriginal(): void
    {
        $this->cspConfig->method('isReportsEnabled')->willReturn(false);
        $value = '/orig';
        $path  = 'csp/whatever/report_uri';
        $result = $this->plugin->afterGetValue($this->subject, $value, $path);
        $this->assertSame('/orig', $result);
    }

    public function testReportUriEnabledReturnsStoreUrl(): void
    {
        $this->cspConfig->method('isReportsEnabled')->willReturn(true);

        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBaseUrl'])
            ->getMock();
        $store->method('getBaseUrl')->willReturn('https://store.test/');

        $this->storeManager
            ->method('getDefaultStoreView')
            ->willReturn($store);

        $value = null;
        $path  = 'csp/foo/report_uri';
        $result = $this->plugin->afterGetValue($this->subject, $value, $path);
        $this->assertSame('https://store.test/csp_report_watch', $result);
    }

    public function testReportOnlyFrontendDisabledKeepsOriginal(): void
    {
        $this->cspConfig->method('isEnabledRestrictModeFrontend')->willReturn(false);
        $value = 1;
        $path  = 'csp/mode/storefront/report_only';
        $result = $this->plugin->afterGetValue($this->subject, $value, $path);
        $this->assertSame(1, $result);
    }

    public function testReportOnlyFrontendEnabledReturnsZero(): void
    {
        $this->cspConfig->method('isEnabledRestrictModeFrontend')->willReturn(true);
        $value = 1;
        $path  = 'csp/mode/storefront/report_only';
        $result = $this->plugin->afterGetValue($this->subject, $value, $path);
        $this->assertSame(0, $result);
    }

    public function testReportOnlyAdminDisabledKeepsOriginal(): void
    {
        $this->cspConfig->method('isEnabledRestrictModeAdminhtml')->willReturn(false);
        $value = 42;
        $path  = 'csp/mode/admin/report_only';
        $result = $this->plugin->afterGetValue($this->subject, $value, $path);
        $this->assertSame(42, $result);
    }

    public function testReportOnlyAdminEnabledReturnsZero(): void
    {
        $this->cspConfig->method('isEnabledRestrictModeAdminhtml')->willReturn(true);
        $value = 99;
        $path  = 'csp/mode/admin/report_only';
        $result = $this->plugin->afterGetValue($this->subject, $value, $path);
        $this->assertSame(0, $result);
    }
}