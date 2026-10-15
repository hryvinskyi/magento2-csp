<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Report;

use Hryvinskyi\Csp\Api\Config\ReportingConfigInterface;
use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Model\Report\RateLimiter\ClientKey;
use Hryvinskyi\Csp\Model\Report\ReportIngestion;
use Hryvinskyi\Csp\Model\Report\ReportRequestHandler;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class ReportRequestHandlerTest extends TestCase
{
    /**
     * @dataProvider clients
     * @param string|false $remoteAddress
     * @param string $expectedKey
     */
    public function testRateLimitsTheClientByItsNetwork(string|false $remoteAddress, string $expectedKey): void
    {
        $ingestion = $this->createMock(ReportIngestion::class);
        $ingestion->expects($this->once())
            ->method('ingest')
            ->with('{}', 'application/csp-report', $expectedKey, Area::ADMINHTML, 0)
            ->willReturn(['accepted' => 0, 'rejected' => 1]);

        $raw = new Raw();

        $this->assertSame($raw, $this->handler($ingestion, $remoteAddress, $raw)->handle($this->request(), Area::ADMINHTML));
    }

    /**
     * @return array<string, array{string|false, string}>
     */
    public static function clients(): array
    {
        return [
            'IPv4' => ['203.0.113.7', '203.0.113.7'],
            'IPv6' => ['2001:db8:1:2::7', '2001:db8:1:2::/64'],
            'unresolved, though documented as a string' => [false, ''],
        ];
    }

    /**
     * @param ReportIngestion $ingestion
     * @param string|false $remoteAddress
     * @param Raw $raw
     * @return ReportRequestHandler
     */
    private function handler(ReportIngestion $ingestion, string|false $remoteAddress, Raw $raw): ReportRequestHandler
    {
        $config = $this->createStub(ReportingConfigInterface::class);
        $config->method('getMaxReportBodyBytes')->willReturn(65536);
        $address = $this->createStub(RemoteAddress::class);
        $address->method('getRemoteAddress')->willReturn($remoteAddress);
        $rawFactory = $this->createStub(RawFactory::class);
        $rawFactory->method('create')->willReturn($raw);

        return new ReportRequestHandler(
            $ingestion,
            $config,
            $address,
            new ClientKey(),
            $this->createStub(StoreManagerInterface::class),
            $rawFactory
        );
    }

    /**
     * @return HttpRequest
     */
    private function request(): HttpRequest
    {
        $request = $this->createStub(HttpRequest::class);
        $request->method('getHeader')->willReturnCallback(
            static fn (string $name): string|false => match ($name) {
                'Content-Type' => 'application/csp-report',
                'Content-Length' => '2',
                default => false,
            }
        );
        $request->method('getContent')->willReturn('{}');

        return $request;
    }
}
