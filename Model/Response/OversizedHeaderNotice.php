<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Response;

use Hryvinskyi\Csp\Api\OversizedHeaderNoticeInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Remembers that a CSP header larger than the configured limit was sent unsplit, so the admin can be told.
 *
 * A proxy that limits header size answers such a response with an error (nginx "upstream sent too big header",
 * Varnish 503). Writes are throttled to one per hour.
 */
class OversizedHeaderNotice implements OversizedHeaderNoticeInterface
{
    private const FLAG_CODE = 'hryvinskyi_csp_oversized_header';
    private const THROTTLE_CACHE_ID = 'HRYVINSKYI_CSP_OVERSIZED_HEADER_RECORDED';
    private const THROTTLE_SECONDS = 3600;
    private const RECENT_SECONDS = 604800;

    /**
     * @param FlagManager $flagManager
     * @param CacheInterface $cache
     * @param DateTime $dateTime
     */
    public function __construct(
        private readonly FlagManager $flagManager,
        private readonly CacheInterface $cache,
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * @inheritDoc
     */
    public function record(string $headerName, int $bytes, int $maxBytes): void
    {
        if ($this->cache->load(self::THROTTLE_CACHE_ID) !== false) {
            return;
        }
        $this->flagManager->saveFlag(self::FLAG_CODE, [
            'header' => $headerName,
            'bytes' => $bytes,
            'limit' => $maxBytes,
            'time' => $this->dateTime->gmtTimestamp(),
        ]);
        $this->cache->save('1', self::THROTTLE_CACHE_ID, [], self::THROTTLE_SECONDS);
    }

    /**
     * @inheritDoc
     */
    public function recent(): ?array
    {
        $data = $this->flagManager->getFlagData(self::FLAG_CODE);
        if (!is_array($data)
            || !is_string($data['header'] ?? null)
            || !is_int($data['bytes'] ?? null)
            || !is_int($data['limit'] ?? null)
            || !is_int($data['time'] ?? null)
        ) {
            return null;
        }
        if ($this->dateTime->gmtTimestamp() - $data['time'] > self::RECENT_SECONDS) {
            return null;
        }

        return ['header' => $data['header'], 'bytes' => $data['bytes'], 'limit' => $data['limit'], 'time' => $data['time']];
    }
}
