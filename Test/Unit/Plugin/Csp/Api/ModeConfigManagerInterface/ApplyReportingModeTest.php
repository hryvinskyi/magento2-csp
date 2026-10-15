<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Plugin\Csp\Api\ModeConfigManagerInterface;

use Hryvinskyi\Csp\Api\Config\ReportingConfigInterface;
use Hryvinskyi\Csp\Model\Report\ReportEndpointUrl;
use Hryvinskyi\Csp\Plugin\Csp\Api\ModeConfigManagerInterface\ApplyReportingMode;
use Laminas\Http\Header\HeaderInterface;
use Magento\Csp\Api\Data\ModeConfiguredInterface;
use Magento\Csp\Api\ModeConfigManagerInterface;
use Magento\Csp\Model\Mode\Data\ModeConfigured;
use Magento\Csp\Model\Mode\Data\ModeConfiguredFactory;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Csp\Model\Policy\Renderer\SimplePolicyHeaderRenderer;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\PhpEnvironment\Response;
use PHPUnit\Framework\TestCase;

/**
 * Drives core's header renderer through the plugin and checks the header it writes.
 */
class ApplyReportingModeTest extends TestCase
{
    private const STORE_URL = 'https://shop.example/de/csp_report_watch';
    private const ADMIN_URL = 'https://admin.shop.example/csp_report_watch/admin/index';

    public function testStorefrontReportsGoToTheStoreEndpointAndRestrictModeEnforces(): void
    {
        $response = $this->render(Area::AREA_FRONTEND, reports: true, restrict: true, coreReportOnly: true);

        $this->assertFalse($response->getHeaders()->has('Content-Security-Policy-Report-Only'));
        $this->assertStringContainsString('report-uri ' . self::STORE_URL . ';', $this->headerValue($response, 'Content-Security-Policy'));
    }

    public function testAdminReportsGoToTheAdminEndpoint(): void
    {
        $response = $this->render(Area::AREA_ADMINHTML, reports: true, restrict: false, coreReportOnly: true);

        $this->assertStringContainsString(
            'report-uri ' . self::ADMIN_URL . ';',
            $this->headerValue($response, 'Content-Security-Policy-Report-Only')
        );
    }

    public function testLeavesTheCoreReportUriAloneWhenModuleReportsAreOff(): void
    {
        $response = $this->render(Area::AREA_FRONTEND, reports: false, restrict: false, coreReportOnly: false, coreUri: 'https://collector.example/r');

        $this->assertStringContainsString(
            'report-uri https://collector.example/r;',
            $this->headerValue($response, 'Content-Security-Policy')
        );
    }

    public function testKeepsCoreModeWhenNothingApplies(): void
    {
        $core = new ModeConfigured(true, null);
        $plugin = $this->plugin(Area::AREA_FRONTEND, reports: false, restrict: false);

        $this->assertSame($core, $plugin->afterGetConfigured($this->createStub(ModeConfigManagerInterface::class), $core));
    }

    /**
     * Render `img-src 'self'` with core's renderer, the mode passing through the plugin.
     *
     * @param string $area
     * @param bool $reports
     * @param bool $restrict
     * @param bool $coreReportOnly
     * @param string|null $coreUri
     * @return Response
     */
    private function render(string $area, bool $reports, bool $restrict, bool $coreReportOnly, ?string $coreUri = null): Response
    {
        $plugin = $this->plugin($area, $reports, $restrict);
        $modeConfig = new class ($plugin, new ModeConfigured($coreReportOnly, $coreUri)) implements ModeConfigManagerInterface {
            /**
             * @param ApplyReportingMode $plugin
             * @param ModeConfiguredInterface $core
             */
            public function __construct(
                private readonly ApplyReportingMode $plugin,
                private readonly ModeConfiguredInterface $core
            ) {
            }

            /**
             * @inheritDoc
             */
            public function getConfigured(): ModeConfiguredInterface
            {
                return $this->plugin->afterGetConfigured($this, $this->core);
            }
        };

        $response = new Response();
        (new SimplePolicyHeaderRenderer($modeConfig))->render(
            new FetchPolicy('img-src', false, [], [], true),
            $response
        );

        return $response;
    }

    /**
     * @param string $area
     * @param bool $reports
     * @param bool $restrict
     * @return ApplyReportingMode
     */
    private function plugin(string $area, bool $reports, bool $restrict): ApplyReportingMode
    {
        $config = $this->createStub(ReportingConfigInterface::class);
        $config->method('isReportsEnabled')->willReturn($reports);
        $config->method('isEnabledRestrictModeFrontend')->willReturn($restrict);
        $config->method('isEnabledRestrictModeAdminhtml')->willReturn($restrict);
        $urls = $this->createStub(ReportEndpointUrl::class);
        $urls->method('forStorefront')->willReturn(self::STORE_URL);
        $urls->method('forAdmin')->willReturn(self::ADMIN_URL);
        $state = $this->createStub(State::class);
        $state->method('getAreaCode')->willReturn($area);
        $factory = $this->createStub(ModeConfiguredFactory::class);
        $factory->method('create')->willReturnCallback(
            static function (array $data): ModeConfigured {
                $uri = $data['reportUri'] ?? null;

                return new ModeConfigured((bool)($data['reportOnly'] ?? false), is_string($uri) ? $uri : null);
            }
        );

        return new ApplyReportingMode($config, $urls, $state, $factory);
    }

    /**
     * @param Response $response
     * @param string $name
     * @return string
     */
    private function headerValue(Response $response, string $name): string
    {
        $header = $response->getHeader($name);

        return $header instanceof HeaderInterface ? $header->getFieldValue() : '';
    }
}
