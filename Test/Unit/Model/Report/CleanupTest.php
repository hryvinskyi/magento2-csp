<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Report;

use Hryvinskyi\Csp\Api\ReportCleanupStrategyInterface;
use Hryvinskyi\Csp\Model\Report\Cleanup;
use Hryvinskyi\Csp\Model\Report\Cleanup\ReportTable;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CleanupTest extends TestCase
{
    public function testCleansWithTheModesStrategyAndRemovesEmptyPendingGroups(): void
    {
        $byAge = $this->createMock(ReportCleanupStrategyInterface::class);
        $byAge->expects($this->once())->method('clean')->with(30)->willReturn(12);
        $table = $this->createMock(ReportTable::class);
        $table->expects($this->once())->method('deleteEmptyGroups')->with(0)->willReturn(3);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info');

        $cleanup = new Cleanup($table, $logger, ['date' => $byAge, 'count' => $this->createStub(ReportCleanupStrategyInterface::class)]);

        $this->assertSame(['date', 'count'], $cleanup->modes());
        $this->assertSame(12, $cleanup->clean('date', 30));
    }

    /**
     * @dataProvider invalidRequests
     * @param string $mode
     * @param int $threshold
     */
    public function testRefusesUnknownModesAndNonPositiveThresholds(string $mode, int $threshold): void
    {
        $cleanup = new Cleanup(
            $this->createStub(ReportTable::class),
            $this->createStub(LoggerInterface::class),
            ['date' => $this->createStub(ReportCleanupStrategyInterface::class)]
        );

        $this->expectException(\InvalidArgumentException::class);
        $cleanup->countAffected($mode, $threshold);
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function invalidRequests(): array
    {
        return ['unknown mode' => ['size', 5], 'zero threshold' => ['date', 0]];
    }
}
