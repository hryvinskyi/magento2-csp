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
use Hryvinskyi\Csp\Api\Data\ViolationInterface;
use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Report\Parser\LegacyReportParser;
use Hryvinskyi\Csp\Model\Report\Parser\ParserPool;
use Hryvinskyi\Csp\Model\Report\Parser\ReportingApiParser;
use Hryvinskyi\Csp\Model\Report\Parser\ViolationBuilder;
use Hryvinskyi\Csp\Model\Report\RateLimiter\CacheReportRateLimiter;
use Hryvinskyi\Csp\Model\Report\ReportIngestion;
use Hryvinskyi\Csp\Model\Report\ViolationClassifier;
use Hryvinskyi\Csp\Model\Report\ViolationFilter;
use Hryvinskyi\Csp\Model\Report\ViolationRecorderInterface;
use Hryvinskyi\Csp\Model\Report\ViolationSanitizer;
use Hryvinskyi\Csp\Model\Store\AdminLocation;
use Hryvinskyi\Csp\Model\Store\StoreHostsProviderInterface;
use Hryvinskyi\Csp\Model\Store\UrlHost;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistableDirectives;
use Hryvinskyi\Csp\Test\Unit\Support\InMemoryCache;
use Hryvinskyi\Csp\Test\Unit\Support\RealViolationFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Drives the whole ingestion with real parsers, filter, sanitizer, classifier and rate limiter.
 */
class ReportIngestionTest extends TestCase
{
    /**
     * @var list<array{string, string, int, string, string}>
     */
    private array $recorded = [];

    public function testRecordsStorefrontViolationsOfTheReceivingStore(): void
    {
        $result = $this->ingestion()->ingest($this->batch([
            ['https://shop.example.com/p?x=1', 'https://cdn.example.org/a.js', 'script-src-elem'],
            ['https://other.example.net/', 'https://cdn.example.org/a.js', 'script-src-elem'],
            ['https://shop.example.com/', 'chrome-extension://abc/inject.js', 'script-src-elem'],
            ['https://shop.example.com/', 'https://example.org/', 'navigate-to'],
        ]), 'application/reports+json', '203.0.113.7', Area::FRONTEND, 1);

        $this->assertSame(['accepted' => 1, 'rejected' => 3], $result);
        $this->assertSame([['script-src', 'cdn.example.org', 1, 'frontend', 'https://shop.example.com/p']], $this->recorded);
    }

    public function testAdminReportsComeFromAdminPagesOnly(): void
    {
        $result = $this->ingestion()->ingest($this->batch([
            ['https://shop.example.com/admin/catalog/', 'inline', 'style-src-attr'],
            ['https://shop.example.com/women.html', 'inline', 'style-src-attr'],
        ]), 'application/reports+json', '203.0.113.7', Area::ADMINHTML, 0);

        $this->assertSame(['accepted' => 1, 'rejected' => 1], $result);
        $this->assertSame([['style-src', 'inline', 0, 'adminhtml', 'https://shop.example.com/admin/catalog/']], $this->recorded);
    }

    public function testConsidersTwentyViolationsPerRequestWithinTheRateLimit(): void
    {
        $violations = array_fill(0, 25, ['https://shop.example.com/', 'https://cdn.example.org/a.js', 'img-src']);

        $result = $this->ingestion(rateLimit: 15)->ingest($this->batch($violations), 'application/reports+json', 'c', Area::FRONTEND, 1);

        $this->assertSame(['accepted' => 15, 'rejected' => 10], $result);
    }

    /**
     * @dataProvider rejectedRequests
     * @param string $body
     * @param string $contentType
     * @param bool $enabled
     */
    public function testRejectsWholeRequests(string $body, string $contentType, bool $enabled): void
    {
        $result = $this->ingestion(enabled: $enabled)->ingest($body, $contentType, 'c', Area::FRONTEND, 1);

        $this->assertSame(['accepted' => 0, 'rejected' => 1], $result);
        $this->assertSame([], $this->recorded);
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function rejectedRequests(): array
    {
        $legacy = (string)json_encode(['csp-report' => [
            'document-uri' => 'https://shop.example.com/', 'blocked-uri' => 'eval', 'effective-directive' => 'script-src',
        ]]);

        return [
            'reports disabled' => [$legacy, 'application/csp-report', false],
            'unknown content type' => [$legacy, 'text/plain', true],
            'malformed' => ['{', 'application/csp-report', true],
            'over the size limit' => [str_pad($legacy, 70000), 'application/csp-report', true],
        ];
    }

    /**
     * @param list<array{string, string, string}> $violations Document, blocked, directive
     * @return string
     */
    private function batch(array $violations): string
    {
        return (string)json_encode(array_map(
            static fn (array $violation): array => ['type' => 'csp-violation', 'body' => [
                'documentURL' => $violation[0],
                'blockedURL' => $violation[1],
                'effectiveDirective' => $violation[2],
            ]],
            $violations
        ));
    }

    /**
     * @param int $rateLimit
     * @param bool $enabled
     * @return ReportIngestion
     */
    private function ingestion(int $rateLimit = 100, bool $enabled = true): ReportIngestion
    {
        $config = $this->createStub(ReportingConfigInterface::class);
        $config->method('isReportsEnabled')->willReturn($enabled);
        $config->method('getMaxReportBodyBytes')->willReturn(65536);
        $config->method('getReportRateLimit')->willReturn($rateLimit);
        $dateTime = $this->createStub(DateTime::class);
        $dateTime->method('gmtTimestamp')->willReturn(1_800_000_000);
        $hosts = $this->createStub(StoreHostsProviderInterface::class);
        $hosts->method('storeHosts')->willReturn(['shop.example.com', 'static.example.com']);
        $hosts->method('storefrontHosts')->willReturn(['shop.example.com', 'static.example.com', 'shop.example.fr']);
        $admin = $this->createStub(AdminLocation::class);
        $admin->method('host')->willReturn('shop.example.com');
        $admin->method('pathPrefix')->willReturn('/admin/');
        $recorder = $this->createStub(ViolationRecorderInterface::class);
        $recorder->method('record')->willReturnCallback(
            function (ViolationInterface $violation, string $policy, string $value, int $storeId, Area $area): bool {
                $this->recorded[] = [$policy, $value, $storeId, $area->value, $violation->getDocumentUri()];

                return true;
            }
        );
        $builder = new ViolationBuilder(new RealViolationFactory());

        return new ReportIngestion(
            $config,
            new ParserPool(['legacy' => new LegacyReportParser($builder), 'reporting_api' => new ReportingApiParser($builder)]),
            new CacheReportRateLimiter(new InMemoryCache(), $config, $dateTime, $this->createStub(LoggerInterface::class)),
            new ViolationFilter($hosts, $admin, new UrlHost()),
            new ViolationSanitizer(new RealViolationFactory()),
            new ViolationClassifier(new WhitelistableDirectives(new DirectiveCatalog()), new UrlHost()),
            $recorder
        );
    }
}
