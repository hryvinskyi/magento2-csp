<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Whitelist;

use Hryvinskyi\Csp\Model\Config\Source\RedundancyStatusOptions;
use Hryvinskyi\Csp\Model\Policy\HostSourceCoverage;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\Whitelist\RedundancyRules;
use PHPUnit\Framework\TestCase;

class RedundancyRulesTest extends TestCase
{
    /**
     * @dataProvider cases
     * @param array{rule_id: int, value: string, stores: list<int>, area: string} $entry
     * @param list<array{rule_id: int, value: string, stores: list<int>, area: string}> $others
     * @param int $expected
     */
    public function testStatus(array $entry, array $others, int $expected): void
    {
        $rules = new RedundancyRules(new HostSourceCoverage(new HostSourceParser()));

        $this->assertSame($expected, $rules->status($entry, [$entry, ...$others]));
    }

    /**
     * @return array<string, array{array{rule_id: int, value: string, stores: list<int>, area: string}, list<array{rule_id: int, value: string, stores: list<int>, area: string}>, int}>
     */
    public static function cases(): array
    {
        $host = self::entry(5, 'cdn.example.com', [1], 'frontend');

        return [
            'alone' => [$host, [], RedundancyStatusOptions::UNIQUE],
            'same value in every store and area' => [
                $host, [self::entry(9, 'CDN.example.com', [0], 'all')], RedundancyStatusOptions::DUPLICATE,
            ],
            'same value and scope, lower id wins' => [
                self::entry(5, 'cdn.example.com', [1], 'frontend'),
                [self::entry(9, 'cdn.example.com', [1], 'adminhtml'), self::entry(3, 'cdn.example.com', [1, 2], 'frontend')],
                RedundancyStatusOptions::DUPLICATE,
            ],
            'same value and scope, higher id is the duplicate' => [
                self::entry(3, 'cdn.example.com', [1], 'frontend'),
                [self::entry(5, 'cdn.example.com', [1], 'frontend')],
                RedundancyStatusOptions::UNIQUE,
            ],
            'same value in another store only' => [
                $host, [self::entry(9, 'cdn.example.com', [2], 'frontend')], RedundancyStatusOptions::UNIQUE,
            ],
            'same value for the admin only' => [
                $host, [self::entry(9, 'cdn.example.com', [0], 'adminhtml')], RedundancyStatusOptions::UNIQUE,
            ],
            'covered by a wildcard in scope' => [
                $host, [self::entry(9, '*.example.com', [0], 'frontend')], RedundancyStatusOptions::REDUNDANT,
            ],
            'wildcard in a narrower store scope' => [
                self::entry(5, 'cdn.example.com', [0], 'all'),
                [self::entry(9, '*.example.com', [1], 'all')],
                RedundancyStatusOptions::UNIQUE,
            ],
            'wildcard does not cover the parent domain' => [
                self::entry(5, 'example.com', [0], 'all'), [self::entry(9, '*.example.com', [0], 'all')],
                RedundancyStatusOptions::UNIQUE,
            ],
            'wildcard without the explicit port' => [
                self::entry(5, 'cdn.example.com:8443', [0], 'all'), [self::entry(9, '*.example.com', [0], 'all')],
                RedundancyStatusOptions::UNIQUE,
            ],
        ];
    }

    /**
     * @param int $id
     * @param string $value
     * @param list<int> $stores
     * @param string $area
     * @return array{rule_id: int, value: string, stores: list<int>, area: string}
     */
    private static function entry(int $id, string $value, array $stores, string $area): array
    {
        return ['rule_id' => $id, 'value' => $value, 'stores' => $stores, 'area' => $area];
    }
}
