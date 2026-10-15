<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model;

use Hryvinskyi\Csp\Model\DynamicPolicyRegistry;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Test\Unit\Support\RealFetchPolicyFactory;
use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Collector\FetchPolicyMerger;
use Magento\Csp\Model\Policy\FetchPolicy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Runs the registry against Magento's real dynamic collector and fetch policies.
 */
class DynamicPolicyRegistryTest extends TestCase
{
    private const HASH = 'ulSR2mFrdWVXSgeR4TBKNBoRfFVHQ+DXt7P2Ek1YyPo=';
    private const SHA384 = '11LCxR+6DimqGQVwqdQlPkQHegWNMpf6OlYw1b0BJiL5fCisrtMTtcg7uZDKp9qF';

    private DynamicCollector $collector;

    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    private DynamicPolicyRegistry $registry;

    protected function setUp(): void
    {
        $this->collector = new DynamicCollector(new FetchPolicyMerger());
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->registry = new DynamicPolicyRegistry($this->collector, new RealFetchPolicyFactory(), new HostSourceParser(), $this->logger);
    }

    public function testAddsHostsSchemesHashesAndSelfToTheCollector(): void
    {
        $this->registry->allow('script-src', ['cdn.example.com', 'https://*.example.org', 'data:'], [self::HASH], true);

        $policies = $this->collector->collect();
        $this->assertCount(1, $policies);
        $policy = $policies[0];
        $this->assertInstanceOf(FetchPolicy::class, $policy);
        $this->assertSame('script-src', $policy->getId());
        $this->assertSame(['cdn.example.com', 'https://*.example.org'], $policy->getHostSources());
        $this->assertSame(['data'], $policy->getSchemeSources());
        $this->assertSame([self::HASH => 'sha256'], $policy->getHashes());
        $this->assertTrue($policy->isSelfAllowed());
    }

    public function testMergesRepeatedAllowancesForOneDirective(): void
    {
        $this->registry->allow('img-src', ['a.example.com']);
        $this->registry->allow('img-src', ['b.example.com']);

        $policy = $this->collector->collect()[0];
        $this->assertInstanceOf(FetchPolicy::class, $policy);
        $this->assertSame(['a.example.com', 'b.example.com'], $policy->getHostSources());
    }

    public function testTakesDigestToAlgorithmMapsAs1xCallersPassThem(): void
    {
        $this->logger->expects($this->never())->method('warning');

        $this->registry->allow('script-src', [], [self::HASH => 'sha256', self::SHA384 => 'SHA384']);

        $policy = $this->collector->collect()[0];
        $this->assertInstanceOf(FetchPolicy::class, $policy);
        $this->assertSame([self::HASH => 'sha256', self::SHA384 => 'sha384'], $policy->getHashes());
    }

    /**
     * @dataProvider invalidAllowances
     * @param string $directive
     * @param array<string> $hosts
     * @param array<int|string, string> $hashes
     */
    public function testSkipsAndLogsWhatCouldBreakTheHeader(string $directive, array $hosts, array $hashes): void
    {
        $this->logger->expects($this->once())->method('warning');

        $this->registry->allow($directive, $hosts, $hashes);

        $this->assertSame([], $this->collector->collect());
    }

    /**
     * @return array<string, array{string, array<string>, array<int|string, string>}>
     */
    public static function invalidAllowances(): array
    {
        return [
            'sub-directive that would stop falling back' => ['script-src-elem', ['cdn.example.com'], []],
            'worker-src, which Magento cannot send dynamically' => ['worker-src', ['cdn.example.com'], []],
            'style-src-attr' => ['style-src-attr', [], [self::HASH]],
            'unknown directive' => ['scripts', ['cdn.example.com'], []],
            'keyword as host' => ['script-src', ["'unsafe-inline'"], []],
            'header injection' => ['script-src', ["cdn.example.com; object-src *"], []],
            'line break' => ['img-src', ["cdn.example.com\r\nX-Evil: 1"], []],
            'hash with prefix' => ['script-src', [], ["'sha256-" . self::HASH . "'"]],
            'not a sha256 digest' => ['script-src', [], ['abc']],
            'algorithm used as the digest' => ['script-src', [], ['sha256']],
            'unknown algorithm' => ['script-src', [], [self::HASH => 'md5']],
            'digest of the wrong length for its algorithm' => ['script-src', [], [self::HASH => 'sha384']],
        ];
    }

    public function testKeepsTheValidSourcesOfAnAllowanceAndSkipsTheRest(): void
    {
        $this->logger->expects($this->exactly(2))->method('warning');

        $this->registry->allow('img-src', ['a.example.com', "'unsafe-inline'"], [self::HASH => 'md5', self::HASH], true);

        $policy = $this->collector->collect()[0];
        $this->assertInstanceOf(FetchPolicy::class, $policy);
        $this->assertSame(['a.example.com'], $policy->getHostSources());
        $this->assertSame([self::HASH => 'sha256'], $policy->getHashes());
        $this->assertTrue($policy->isSelfAllowed());
    }

    public function testAddsNothingWhenNothingIsAllowed(): void
    {
        $this->registry->allow('img-src');

        $this->assertSame([], $this->collector->collect());
    }
}
