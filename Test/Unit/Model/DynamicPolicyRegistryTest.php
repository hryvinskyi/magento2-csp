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
use Magento\Csp\Model\Collector\DynamicCollector;
use Magento\Csp\Model\Collector\FetchPolicyMerger;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Csp\Model\Policy\FetchPolicyFactory;
use PHPUnit\Framework\TestCase;

/**
 * Runs the registry against Magento's real dynamic collector and fetch policies.
 */
class DynamicPolicyRegistryTest extends TestCase
{
    private const HASH = 'ulSR2mFrdWVXSgeR4TBKNBoRfFVHQ+DXt7P2Ek1YyPo=';

    private DynamicCollector $collector;

    private DynamicPolicyRegistry $registry;

    protected function setUp(): void
    {
        $this->collector = new DynamicCollector(new FetchPolicyMerger());
        $factory = $this->createStub(FetchPolicyFactory::class);
        $factory->method('create')->willReturnCallback(
            static function (array $data): FetchPolicy {
                return new FetchPolicy(
                    self::stringOf($data['id'] ?? ''),
                    (bool)($data['noneAllowed'] ?? true),
                    self::listOf($data['hostSources'] ?? []),
                    self::listOf($data['schemeSources'] ?? []),
                    (bool)($data['selfAllowed'] ?? false),
                    false,
                    false,
                    [],
                    self::mapOf($data['hashValues'] ?? [])
                );
            }
        );
        $this->registry = new DynamicPolicyRegistry($this->collector, $factory, new HostSourceParser());
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

    /**
     * @dataProvider invalidAllowances
     * @param string $directive
     * @param list<string> $hosts
     * @param list<string> $hashes
     */
    public function testRefusesWhatCouldBreakTheHeader(string $directive, array $hosts, array $hashes): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->registry->allow($directive, $hosts, $hashes);
    }

    /**
     * @return array<string, array{string, list<string>, list<string>}>
     */
    public static function invalidAllowances(): array
    {
        return [
            'sub-directive that would stop falling back' => ['script-src-elem', ['cdn.example.com'], []],
            'unknown directive' => ['scripts', ['cdn.example.com'], []],
            'keyword as host' => ['script-src', ["'unsafe-inline'"], []],
            'header injection' => ['script-src', ["cdn.example.com; object-src *"], []],
            'line break' => ['img-src', ["cdn.example.com\r\nX-Evil: 1"], []],
            'hash with prefix' => ['script-src', [], ["'sha256-" . self::HASH . "'"]],
            'not a sha256 digest' => ['script-src', [], ['abc']],
        ];
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function stringOf(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private static function listOf(mixed $value): array
    {
        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }

    /**
     * @param mixed $value
     * @return array<string, string>
     */
    private static function mapOf(mixed $value): array
    {
        $map = [];
        foreach (is_array($value) ? $value : [] as $hash => $algorithm) {
            if (is_string($algorithm)) {
                $map[(string)$hash] = $algorithm;
            }
        }

        return $map;
    }
}
