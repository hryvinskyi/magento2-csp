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
use Hryvinskyi\Csp\Model\Report\RateLimiter\ClientKey;
use Hryvinskyi\Csp\Test\Unit\Support\InMemoryCache;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ClientKeyTest extends TestCase
{
    /**
     * @dataProvider addresses
     * @param string $address
     * @param string $expected
     */
    public function testKeysIpv4ByAddressAndIpv6ByItsSlash64(string $address, string $expected): void
    {
        $this->assertSame($expected, (new ClientKey())->of($address));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function addresses(): array
    {
        return [
            'IPv4' => ['203.0.113.7', '203.0.113.7'],
            'IPv6' => ['2001:db8:1:2:3:4:5:6', '2001:db8:1:2::/64'],
            'IPv6, other interface id' => ['2001:DB8:1:2::ffff', '2001:db8:1:2::/64'],
            'IPv6, neighbouring network' => ['2001:db8:1:3::1', '2001:db8:1:3::/64'],
            'IPv4 mapped into IPv6' => ['::ffff:203.0.113.7', '203.0.113.7'],
            'unresolved' => ['', ''],
            'not an address' => ['unknown', 'unknown'],
        ];
    }

    public function testAddressesOfOneIpv6NetworkShareOneAllowance(): void
    {
        $config = $this->createStub(ReportingConfigInterface::class);
        $config->method('getReportRateLimit')->willReturn(3);
        $dateTime = $this->createStub(DateTime::class);
        $dateTime->method('gmtTimestamp')->willReturn(1_800_000_000);
        $limiter = new CacheReportRateLimiter(new InMemoryCache(), $config, $dateTime, $this->createStub(LoggerInterface::class));
        $key = new ClientKey();

        $allowed = array_map(
            static fn (string $address): int => $limiter->allow($key->of($address), Area::FRONTEND, 1),
            ['2001:db8::1', '2001:db8::2', '2001:db8::3', '2001:db8::4', '2001:db8:0:1::1']
        );

        $this->assertSame([1, 1, 1, 0, 1], $allowed);
    }
}
