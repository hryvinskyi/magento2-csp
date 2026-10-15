<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Collector;

use Hryvinskyi\Csp\Api\Config\RulesConfigInterface;
use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Model\Collector\WhitelistDbCollector;
use Hryvinskyi\Csp\Model\Whitelist\ActiveEntries;
use Hryvinskyi\Csp\Test\Unit\Support\RealFetchPolicyFactory;
use Magento\Csp\Model\Policy\FetchPolicy;
use Magento\Framework\App\State;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class WhitelistDbCollectorTest extends TestCase
{
    private const DIGEST = 'n4bQgYhMfWWaL+qgxVrQFaO/TxsrC4Is0V1sFbDwCgg=';

    public function testAddsHostsSchemesAndHashesOfTheStorefrontScope(): void
    {
        $collected = $this->collector('frontend', 3, Area::FRONTEND, [
            ['policy' => 'script-src', 'value_type' => 'host', 'value_algorithm' => '', 'value' => 'cdn.example.com'],
            ['policy' => 'script-src', 'value_type' => 'hash', 'value_algorithm' => 'sha256', 'value' => self::DIGEST],
            ['policy' => 'img-src', 'value_type' => 'host', 'value_algorithm' => '', 'value' => 'data:'],
        ])->collect(['existing' => new FetchPolicy('font-src', false, ['fonts.example.com'])]);

        $this->assertCount(3, $collected);
        [$existing, $script, $image] = array_values($collected);
        $this->assertInstanceOf(FetchPolicy::class, $existing);
        $this->assertInstanceOf(FetchPolicy::class, $script);
        $this->assertInstanceOf(FetchPolicy::class, $image);
        $this->assertSame('font-src', $existing->getId());
        $this->assertSame(['script-src', ['cdn.example.com'], [self::DIGEST => 'sha256']], [$script->getId(), $script->getHostSources(), $script->getHashes()]);
        $this->assertSame(['img-src', [], ['data']], [$image->getId(), $image->getHostSources(), $image->getSchemeSources()]);
    }

    public function testTheAdminReadsEntriesForEveryStore(): void
    {
        $collected = $this->collector('adminhtml', 3, Area::ADMINHTML, [
            ['policy' => 'connect-src', 'value_type' => 'host', 'value_algorithm' => '', 'value' => 'api.example.com'],
        ])->collect();

        $this->assertCount(1, $collected);
    }

    public function testAddsNothingWhenRulesAreDisabled(): void
    {
        $config = $this->createStub(RulesConfigInterface::class);
        $config->method('isRulesEnabled')->willReturn(false);
        $entries = $this->createMock(ActiveEntries::class);
        $entries->expects($this->never())->method('forScope');
        $collector = new WhitelistDbCollector(
            $config,
            $this->createStub(State::class),
            $this->createStub(StoreManagerInterface::class),
            $entries,
            new RealFetchPolicyFactory()
        );

        $this->assertSame([], $collector->collect());
    }

    /**
     * @param string $areaCode
     * @param int $storeId
     * @param Area $expectedArea
     * @param list<array{policy: string, value_type: string, value_algorithm: string, value: string}> $entries
     * @return WhitelistDbCollector
     */
    private function collector(string $areaCode, int $storeId, Area $expectedArea, array $entries): WhitelistDbCollector
    {
        $config = $this->createStub(RulesConfigInterface::class);
        $config->method('isRulesEnabled')->willReturn(true);
        $state = $this->createStub(State::class);
        $state->method('getAreaCode')->willReturn($areaCode);
        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn($storeId);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        $activeEntries = $this->createMock(ActiveEntries::class);
        $activeEntries->expects($this->once())
            ->method('forScope')
            ->with($expectedArea === Area::ADMINHTML ? 0 : $storeId, $expectedArea)
            ->willReturn($entries);

        return new WhitelistDbCollector($config, $state, $storeManager, $activeEntries, new RealFetchPolicyFactory());
    }
}
