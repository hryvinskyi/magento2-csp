<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Command;

use Hryvinskyi\Csp\Api\Config\ReportCleanupConfigInterface;
use Hryvinskyi\Csp\Api\ReportCleanupInterface;
use Hryvinskyi\Csp\Command\CleanOldReportsCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CleanOldReportsCommandTest extends TestCase
{
    public function testUsesTheConfiguredModeAndThreshold(): void
    {
        $cleanup = $this->cleanup();
        $cleanup->expects($this->once())->method('clean')->with('date', 30)->willReturn(4);
        $tester = new CommandTester(new CleanOldReportsCommand($this->config(), $cleanup));

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertStringContainsString('4 report(s) deleted (mode date, threshold 30)', $tester->getDisplay());
    }

    public function testDryRunOnlyCounts(): void
    {
        $cleanup = $this->cleanup();
        $cleanup->expects($this->never())->method('clean');
        $cleanup->expects($this->once())->method('countAffected')->with('count', 500)->willReturn(70);
        $tester = new CommandTester(new CleanOldReportsCommand($this->config(), $cleanup));

        $tester->execute(['--mode' => 'count', '--threshold' => '500', '--dry-run' => true]);

        $this->assertStringContainsString('70 report(s) would be deleted', $tester->getDisplay());
    }

    public function testFailsOnAnInvalidMode(): void
    {
        $cleanup = $this->cleanup();
        $cleanup->method('clean')->willThrowException(new \InvalidArgumentException('Unknown cleanup mode "size".'));
        $tester = new CommandTester(new CleanOldReportsCommand($this->config(), $cleanup));

        $this->assertSame(Command::FAILURE, $tester->execute(['--mode' => 'size']));
        $this->assertStringContainsString('Unknown cleanup mode', $tester->getDisplay());
    }

    /**
     * @return ReportCleanupInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private function cleanup(): ReportCleanupInterface
    {
        $cleanup = $this->createMock(ReportCleanupInterface::class);
        $cleanup->method('modes')->willReturn(['date', 'count']);

        return $cleanup;
    }

    /**
     * @return ReportCleanupConfigInterface
     */
    private function config(): ReportCleanupConfigInterface
    {
        $config = $this->createStub(ReportCleanupConfigInterface::class);
        $config->method('getReportCleanupMode')->willReturn('date');
        $config->method('getReportCleanupThreshold')->willReturn(30);

        return $config;
    }
}
