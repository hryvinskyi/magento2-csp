<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Response;

use Hryvinskyi\Csp\Model\Response\OversizedHeaderNotice;
use Hryvinskyi\Csp\Test\Unit\Support\InMemoryCache;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OversizedHeaderNoticeTest extends TestCase
{
    private const NOW = 1_800_000_000;

    /**
     * @var FlagManager&MockObject
     */
    private FlagManager $flagManager;

    /**
     * @var CacheInterface&MockObject
     */
    private CacheInterface $cache;

    private OversizedHeaderNotice $notice;

    protected function setUp(): void
    {
        $this->flagManager = $this->createMock(FlagManager::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $dateTime = $this->createStub(DateTime::class);
        $dateTime->method('gmtTimestamp')->willReturn(self::NOW);
        $this->notice = new OversizedHeaderNotice($this->flagManager, $this->cache, $dateTime);
    }

    public function testRecordsOnceAndThrottlesTheNextWrites(): void
    {
        $dateTime = $this->createStub(DateTime::class);
        $dateTime->method('gmtTimestamp')->willReturn(self::NOW);
        $notice = new OversizedHeaderNotice($this->flagManager, new InMemoryCache(), $dateTime);
        $this->flagManager->expects($this->once())->method('saveFlag')->with(
            'hryvinskyi_csp_oversized_header',
            ['header' => 'Content-Security-Policy', 'bytes' => 9000, 'limit' => 8192, 'time' => self::NOW]
        );

        $notice->record('Content-Security-Policy', 9000, 8192);
        $notice->record('Content-Security-Policy', 9100, 8192);
    }

    public function testRecordsWhenTheCacheMissesWithFalse(): void
    {
        $this->cache->method('load')->willReturn(false);
        $this->flagManager->expects($this->once())->method('saveFlag');
        $this->cache->expects($this->once())->method('save')
            ->with('1', 'HRYVINSKYI_CSP_OVERSIZED_HEADER_RECORDED', [], 3600);

        $this->notice->record('Content-Security-Policy', 9000, 8192);
    }

    public function testDoesNotWriteWhileThrottled(): void
    {
        $this->cache->method('load')->willReturn('1');
        $this->flagManager->expects($this->never())->method('saveFlag');

        $this->notice->record('Content-Security-Policy', 9000, 8192);
    }

    public function testReportsOnlyARecentNotice(): void
    {
        $recent = ['header' => 'Content-Security-Policy', 'bytes' => 9000, 'limit' => 8192, 'time' => self::NOW - 60];
        $old = ['header' => 'Content-Security-Policy', 'bytes' => 9000, 'limit' => 8192, 'time' => self::NOW - 700000];
        $this->flagManager->method('getFlagData')->willReturnOnConsecutiveCalls($recent, $old, 'garbage');

        $this->assertSame($recent, $this->notice->recent());
        $this->assertNull($this->notice->recent());
        $this->assertNull($this->notice->recent());
    }
}
