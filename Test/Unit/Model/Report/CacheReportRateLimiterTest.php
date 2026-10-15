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
use Hryvinskyi\Csp\Model\Report\RateLimiter\CacheReportRateLimiter;
use Hryvinskyi\Csp\Test\Unit\Support\InMemoryCache;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CacheReportRateLimiterTest extends TestCase
{
    public function testCountsViolationsPerClientAreaAndMinute(): void
    {
        $now = 1_800_000_000;
        $limiter = $this->limiter(new InMemoryCache(), 5, $now);

        $this->assertSame(3, $limiter->allow('203.0.113.7', Area::FRONTEND, 3));
        $this->assertSame(2, $limiter->allow('203.0.113.7', Area::FRONTEND, 3));
        $this->assertSame(0, $limiter->allow('203.0.113.7', Area::FRONTEND, 1));
        $this->assertSame(1, $limiter->allow('2001:db8::1', Area::FRONTEND, 1));
        $this->assertSame(1, $limiter->allow('203.0.113.7', Area::ADMINHTML, 1));
    }

    public function testANewMinuteStartsAFreshAllowance(): void
    {
        $cache = new InMemoryCache();
        $this->limiter($cache, 2, 1_800_000_000)->allow('203.0.113.7', Area::FRONTEND, 2);

        $this->assertSame(2, $this->limiter($cache, 2, 1_800_000_060)->allow('203.0.113.7', Area::FRONTEND, 5));
    }

    public function testLetsReportsThroughWhenTheCacheFails(): void
    {
        $cache = $this->createStub(CacheInterface::class);
        $cache->method('load')->willThrowException(new \RuntimeException('Cache is down'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning');
        $limiter = $this->limiter($cache, 1, 1_800_000_000, $logger);

        $first = $limiter->allow('203.0.113.7', Area::FRONTEND, 4);
        $second = $limiter->allow('203.0.113.7', Area::FRONTEND, 4);
        $this->assertSame([4, 4], [$first, $second]);
    }

    /**
     * @param CacheInterface $cache
     * @param int $limit
     * @param int $now
     * @param LoggerInterface|null $logger
     * @return CacheReportRateLimiter
     */
    private function limiter(CacheInterface $cache, int $limit, int $now, ?LoggerInterface $logger = null): CacheReportRateLimiter
    {
        $config = $this->createStub(ReportingConfigInterface::class);
        $config->method('getReportRateLimit')->willReturn($limit);
        $dateTime = $this->createStub(DateTime::class);
        $dateTime->method('gmtTimestamp')->willReturn($now);

        return new CacheReportRateLimiter($cache, $config, $dateTime, $logger ?? $this->createStub(LoggerInterface::class));
    }
}
