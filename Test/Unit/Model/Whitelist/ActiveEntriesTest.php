<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Whitelist;

use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Model\Policy\DirectiveCatalog;
use Hryvinskyi\Csp\Model\Policy\HostSourceParser;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\Collection;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\CollectionFactory;
use Hryvinskyi\Csp\Model\Whitelist\ActiveEntries;
use Hryvinskyi\Csp\Model\Whitelist\SourceValueRules;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistableDirectives;
use Hryvinskyi\Csp\Test\Unit\Support\CreatesWhitelistEntries;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\Cache\Type\Collection as CollectionCache;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ActiveEntriesTest extends TestCase
{
    use CreatesWhitelistEntries;

    /**
     * @var array<string, string>
     */
    private array $cacheRecords = [];

    public function testLeavesOutEntriesThatBreakTheRulesAndCachesTheRest(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('warning')->with($this->anything(), ['rule_ids' => [2, 3]]);
        $entries = $this->activeEntries($this->collectionFactory(1), $logger);

        $expected = [['policy' => 'script-src', 'value_type' => 'host', 'value_algorithm' => '', 'value' => 'cdn.example.com']];
        $first = $entries->forScope(1, Area::FRONTEND);
        $repeated = $entries->forScope(1, Area::FRONTEND);
        $this->assertSame([$expected, $expected], [$first, $repeated]);

        $fromCache = $this->activeEntries($this->collectionFactory(0), $this->createStub(LoggerInterface::class));
        $this->assertSame($expected, $fromCache->forScope(1, Area::FRONTEND));
    }

    /**
     * Factory of a collection holding one valid entry, one with a keyword value and one with a sub-directive.
     *
     * @param int $expectedQueries
     * @return CollectionFactory
     */
    private function collectionFactory(int $expectedQueries): CollectionFactory
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('addActiveFilter')->willReturnSelf();
        $collection->method('addStoreFilter')->willReturnSelf();
        $collection->method('addAreaFilter')->willReturnSelf();
        $collection->method('getItems')->willReturn([
            $this->whitelistEntry(['rule_id' => 1]),
            $this->whitelistEntry(['rule_id' => 2, 'value' => "'unsafe-inline'"]),
            $this->whitelistEntry(['rule_id' => 3, 'policy' => 'script-src-elem']),
        ]);
        $factory = $this->createMock(CollectionFactory::class);
        $factory->expects($this->exactly($expectedQueries))->method('create')->willReturn($collection);

        return $factory;
    }

    /**
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     * @return ActiveEntries
     */
    private function activeEntries(CollectionFactory $collectionFactory, LoggerInterface $logger): ActiveEntries
    {
        $cache = $this->createStub(CollectionCache::class);
        $cache->method('load')->willReturnCallback(fn (string $key): string|false => $this->cacheRecords[$key] ?? false);
        $cache->method('save')->willReturnCallback(function (string $data, string $key): bool {
            $this->cacheRecords[$key] = $data;

            return true;
        });
        $cacheState = $this->createStub(StateInterface::class);
        $cacheState->method('isEnabled')->willReturn(true);

        return new ActiveEntries(
            $collectionFactory,
            $cache,
            $cacheState,
            new Json(),
            new WhitelistableDirectives(new DirectiveCatalog()),
            new SourceValueRules(new HostSourceParser()),
            $logger
        );
    }
}
