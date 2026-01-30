<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Whitelist;

use Hryvinskyi\Csp\Api\DomainMatcherInterface;
use Hryvinskyi\Csp\Model\Config\Source\RedundancyStatusOptions;
use Hryvinskyi\Csp\Model\DomainMatcher;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\Collection;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\CollectionFactory;
use Hryvinskyi\Csp\Model\Whitelist;
use Hryvinskyi\Csp\Model\Whitelist\RedundancyCalculator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Hryvinskyi\Csp\Model\Whitelist\RedundancyCalculator
 */
class RedundancyCalculatorTest extends TestCase
{
    private RedundancyCalculator $calculator;
    private MockObject|CollectionFactory $collectionFactoryMock;
    private DomainMatcherInterface $domainMatcher;

    protected function setUp(): void
    {
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->domainMatcher = new DomainMatcher();

        $this->calculator = new RedundancyCalculator(
            $this->collectionFactoryMock,
            $this->domainMatcher
        );
    }

    // ==================== Basic Tests ====================

    public function testCalculateForItemsEmptyArray(): void
    {
        $this->setupCollectionMock([]);

        $result = $this->calculator->calculateForItems([]);
        $this->assertSame([], $result);
    }

    public function testCalculateForItemsNonHostType(): void
    {
        $this->setupCollectionMock([]);

        $items = [
            [
                'rule_id' => 1,
                'policy' => 'script-src',
                'value_type' => 'hash',
                'value' => 'sha256-abc123',
            ],
        ];

        $result = $this->calculator->calculateForItems($items);

        $this->assertSame(RedundancyStatusOptions::NOT_APPLICABLE, $result[0]['redundancy_status']);
    }

    public function testCalculateForItemsUniqueEntry(): void
    {
        $this->setupCollectionMock([
            ['rule_id' => 1, 'policy' => 'script-src', 'value' => 'example.com'],
        ]);

        $items = [
            [
                'rule_id' => 1,
                'policy' => 'script-src',
                'value_type' => 'host',
                'value' => 'example.com',
            ],
        ];

        $result = $this->calculator->calculateForItems($items);

        $this->assertSame(RedundancyStatusOptions::UNIQUE, $result[0]['redundancy_status']);
    }

    // ==================== Duplicate Detection Tests ====================

    public function testCalculateForItemsDetectsDuplicate(): void
    {
        $this->setupCollectionMock([
            ['rule_id' => 1, 'policy' => 'script-src', 'value' => 'example.com'],
            ['rule_id' => 2, 'policy' => 'script-src', 'value' => 'example.com'],
        ]);

        $items = [
            [
                'rule_id' => 2,
                'policy' => 'script-src',
                'value_type' => 'host',
                'value' => 'example.com',
            ],
        ];

        $result = $this->calculator->calculateForItems($items);

        $this->assertSame(RedundancyStatusOptions::DUPLICATE, $result[0]['redundancy_status']);
    }

    public function testCalculateForItemsDuplicateCaseInsensitive(): void
    {
        $this->setupCollectionMock([
            ['rule_id' => 1, 'policy' => 'script-src', 'value' => 'example.com'],
            ['rule_id' => 2, 'policy' => 'script-src', 'value' => 'EXAMPLE.COM'],
        ]);

        $items = [
            [
                'rule_id' => 2,
                'policy' => 'script-src',
                'value_type' => 'host',
                'value' => 'EXAMPLE.COM',
            ],
        ];

        $result = $this->calculator->calculateForItems($items);

        $this->assertSame(RedundancyStatusOptions::DUPLICATE, $result[0]['redundancy_status']);
    }

    public function testCalculateForItemsNoDuplicateForDifferentPolicy(): void
    {
        $this->setupCollectionMock([
            ['rule_id' => 1, 'policy' => 'script-src', 'value' => 'example.com'],
            ['rule_id' => 2, 'policy' => 'img-src', 'value' => 'example.com'],
        ]);

        $items = [
            [
                'rule_id' => 2,
                'policy' => 'img-src',
                'value_type' => 'host',
                'value' => 'example.com',
            ],
        ];

        $result = $this->calculator->calculateForItems($items);

        $this->assertSame(RedundancyStatusOptions::UNIQUE, $result[0]['redundancy_status']);
    }

    // ==================== Wildcard Redundancy Tests ====================

    public function testCalculateForItemsDetectsWildcardRedundancy(): void
    {
        $this->setupCollectionMock([
            ['rule_id' => 1, 'policy' => 'script-src', 'value' => '*.example.com'],
            ['rule_id' => 2, 'policy' => 'script-src', 'value' => 'www.example.com'],
        ]);

        $items = [
            [
                'rule_id' => 2,
                'policy' => 'script-src',
                'value_type' => 'host',
                'value' => 'www.example.com',
            ],
        ];

        $result = $this->calculator->calculateForItems($items);

        $this->assertSame(RedundancyStatusOptions::REDUNDANT, $result[0]['redundancy_status']);
    }

    public function testCalculateForItemsDetectsBroaderWildcardRedundancy(): void
    {
        $this->setupCollectionMock([
            ['rule_id' => 1, 'policy' => 'script-src', 'value' => '*.example.com'],
            ['rule_id' => 2, 'policy' => 'script-src', 'value' => '*.sub.example.com'],
        ]);

        $items = [
            [
                'rule_id' => 2,
                'policy' => 'script-src',
                'value_type' => 'host',
                'value' => '*.sub.example.com',
            ],
        ];

        $result = $this->calculator->calculateForItems($items);

        $this->assertSame(RedundancyStatusOptions::REDUNDANT, $result[0]['redundancy_status']);
    }

    public function testCalculateForItemsWildcardNotRedundantWithHigherId(): void
    {
        $this->setupCollectionMock([
            ['rule_id' => 1, 'policy' => 'script-src', 'value' => 'www.example.com'],
            ['rule_id' => 2, 'policy' => 'script-src', 'value' => '*.example.com'],
        ]);

        $items = [
            [
                'rule_id' => 1,
                'policy' => 'script-src',
                'value_type' => 'host',
                'value' => 'www.example.com',
            ],
        ];

        $result = $this->calculator->calculateForItems($items);

        // www.example.com (id=1) is NOT redundant because *.example.com has higher id (2)
        $this->assertSame(RedundancyStatusOptions::UNIQUE, $result[0]['redundancy_status']);
    }

    // ==================== DoubleClick Domain Tests ====================

    public function testCalculateForItemsDoubleClickDomains(): void
    {
        $this->setupCollectionMock([
            ['rule_id' => 1, 'policy' => 'script-src', 'value' => '*.doubleclick.net'],
            ['rule_id' => 2, 'policy' => 'script-src', 'value' => '*.g.doubleclick.net'],
            ['rule_id' => 3, 'policy' => 'script-src', 'value' => 'googleads.g.doubleclick.net'],
            ['rule_id' => 4, 'policy' => 'script-src', 'value' => 'ad.doubleclick.net'],
            ['rule_id' => 5, 'policy' => 'script-src', 'value' => 'bid.g.doubleclick.net'],
        ]);

        $items = [
            ['rule_id' => 1, 'policy' => 'script-src', 'value_type' => 'host', 'value' => '*.doubleclick.net'],
            ['rule_id' => 2, 'policy' => 'script-src', 'value_type' => 'host', 'value' => '*.g.doubleclick.net'],
            ['rule_id' => 3, 'policy' => 'script-src', 'value_type' => 'host', 'value' => 'googleads.g.doubleclick.net'],
            ['rule_id' => 4, 'policy' => 'script-src', 'value_type' => 'host', 'value' => 'ad.doubleclick.net'],
            ['rule_id' => 5, 'policy' => 'script-src', 'value_type' => 'host', 'value' => 'bid.g.doubleclick.net'],
        ];

        $result = $this->calculator->calculateForItems($items);

        // Only *.doubleclick.net (id=1) should be unique
        $this->assertSame(RedundancyStatusOptions::UNIQUE, $result[0]['redundancy_status']);
        // All others are redundant (covered by *.doubleclick.net)
        $this->assertSame(RedundancyStatusOptions::REDUNDANT, $result[1]['redundancy_status']);
        $this->assertSame(RedundancyStatusOptions::REDUNDANT, $result[2]['redundancy_status']);
        $this->assertSame(RedundancyStatusOptions::REDUNDANT, $result[3]['redundancy_status']);
        $this->assertSame(RedundancyStatusOptions::REDUNDANT, $result[4]['redundancy_status']);
    }

    // ==================== Mixed Scenarios Tests ====================

    public function testCalculateForItemsMixedScenarios(): void
    {
        $this->setupCollectionMock([
            ['rule_id' => 1, 'policy' => 'script-src', 'value' => '*.google.com'],
            ['rule_id' => 2, 'policy' => 'script-src', 'value' => 'www.google.com'],
            ['rule_id' => 3, 'policy' => 'script-src', 'value' => 'www.google.com'],
            ['rule_id' => 4, 'policy' => 'img-src', 'value' => 'www.google.com'],
        ]);

        $items = [
            ['rule_id' => 1, 'policy' => 'script-src', 'value_type' => 'host', 'value' => '*.google.com'],
            ['rule_id' => 2, 'policy' => 'script-src', 'value_type' => 'host', 'value' => 'www.google.com'],
            ['rule_id' => 3, 'policy' => 'script-src', 'value_type' => 'host', 'value' => 'www.google.com'],
            ['rule_id' => 4, 'policy' => 'img-src', 'value_type' => 'host', 'value' => 'www.google.com'],
        ];

        $result = $this->calculator->calculateForItems($items);

        // id=1: unique wildcard
        $this->assertSame(RedundancyStatusOptions::UNIQUE, $result[0]['redundancy_status']);
        // id=2: redundant (covered by wildcard id=1)
        $this->assertSame(RedundancyStatusOptions::REDUNDANT, $result[1]['redundancy_status']);
        // id=3: duplicate (same as id=2) AND redundant, but duplicate takes precedence
        $this->assertSame(RedundancyStatusOptions::DUPLICATE, $result[2]['redundancy_status']);
        // id=4: unique (different policy)
        $this->assertSame(RedundancyStatusOptions::UNIQUE, $result[3]['redundancy_status']);
    }

    // ==================== Helper Methods ====================

    /**
     * @param array<int, array{rule_id: int, policy: string, value: string}> $data
     */
    private function setupCollectionMock(array $data): void
    {
        $collectionMock = $this->createMock(Collection::class);

        $items = [];
        foreach ($data as $row) {
            $itemMock = $this->createMock(Whitelist::class);
            $itemMock->method('getRuleId')->willReturn($row['rule_id']);
            $itemMock->method('getPolicy')->willReturn($row['policy']);
            $itemMock->method('getValue')->willReturn($row['value']);
            $items[] = $itemMock;
        }

        $collectionMock->method('addFieldToFilter')->willReturnSelf();
        $collectionMock->method('getIterator')->willReturn(new \ArrayIterator($items));

        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);
    }
}
