<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report\RateLimiter;

use Hryvinskyi\Csp\Api\Config\ReportingConfigInterface;
use Hryvinskyi\Csp\Api\Data\Area;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

/**
 * Counts violations per client and area in one-minute windows kept in the application cache.
 *
 * Reading and writing the count is not atomic, so concurrent requests can exceed the limit by their number. When the
 * cache fails, reports are let through and the failure is logged once.
 */
class CacheReportRateLimiter implements ReportRateLimiterInterface
{
    public const CACHE_TAG = 'HRYVINSKYI_CSP_RATE';
    private const KEY_PREFIX = 'HRYVINSKYI_CSP_RL_';
    private const WINDOW_SECONDS = 60;
    private const LIFETIME = 120;

    private bool $failureLogged = false;

    /**
     * @param CacheInterface $cache
     * @param ReportingConfigInterface $config
     * @param DateTime $dateTime
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly ReportingConfigInterface $config,
        private readonly DateTime $dateTime,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function allow(string $clientKey, Area $area, int $requested): int
    {
        if ($requested <= 0) {
            return 0;
        }
        $window = intdiv($this->dateTime->gmtTimestamp(), self::WINDOW_SECONDS);
        $key = self::KEY_PREFIX . sha1($area->value . '|' . $clientKey . '|' . $window);
        try {
            $stored = $this->cache->load($key);
            $used = is_numeric($stored) ? (int)$stored : 0;
            $allowed = max(0, min($requested, $this->config->getReportRateLimit() - $used));
            if ($allowed > 0) {
                $this->cache->save((string)($used + $allowed), $key, [self::CACHE_TAG], self::LIFETIME);
            }

            return $allowed;
        } catch (\Exception $exception) {
            if (!$this->failureLogged) {
                $this->failureLogged = true;
                $this->logger->warning('The CSP report rate limit could not be checked.', ['exception' => $exception]);
            }

            return $requested;
        }
    }
}
