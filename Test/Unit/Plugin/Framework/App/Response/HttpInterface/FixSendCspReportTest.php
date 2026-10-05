<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Plugin\Framework\App\Response\HttpInterface;

use Hryvinskyi\Csp\Api\Config\ReportingConfigInterface;
use Hryvinskyi\Csp\Plugin\Framework\App\Response\HttpInterface\FixSendCspReport;
use Magento\Framework\App\Response\HttpInterface;
use PHPUnit\Framework\TestCase;

class FixSendCspReportTest extends TestCase
{
    /**
     * @dataProvider values
     * @param string $value
     * @param string $expected
     */
    public function testRemovesReportToFromCspHeaders(string $value, string $expected): void
    {
        [, $result] = $this->plugin(true)->beforeSetHeader(
            $this->createStub(HttpInterface::class),
            'Content-Security-Policy-Report-Only',
            $value
        );

        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function values(): array
    {
        return [
            'as core appends it' => [
                "img-src 'self'; report-uri https://shop/r; report-to report-endpoint;",
                "img-src 'self'; report-uri https://shop/r;",
            ],
            'first directive' => ["report-to group1; img-src 'self';", "img-src 'self';"],
            'without trailing semicolon' => ["img-src 'self'; report-to g", "img-src 'self';"],
            'host containing the word' => ["img-src https://report-to.example.com;", "img-src https://report-to.example.com;"],
        ];
    }

    public function testLeavesOtherHeadersAndDisabledReportsAlone(): void
    {
        $value = "img-src 'self'; report-to g;";

        $this->assertSame(
            ['X-Other', $value, false],
            $this->plugin(true)->beforeSetHeader($this->createStub(HttpInterface::class), 'X-Other', $value)
        );
        $this->assertSame(
            ['Content-Security-Policy', $value, true],
            $this->plugin(false)->beforeSetHeader($this->createStub(HttpInterface::class), 'Content-Security-Policy', $value, true)
        );
    }

    /**
     * @param bool $reportsEnabled
     * @return FixSendCspReport
     */
    private function plugin(bool $reportsEnabled): FixSendCspReport
    {
        $config = $this->createStub(ReportingConfigInterface::class);
        $config->method('isReportsEnabled')->willReturn($reportsEnabled);

        return new FixSendCspReport($config);
    }
}
