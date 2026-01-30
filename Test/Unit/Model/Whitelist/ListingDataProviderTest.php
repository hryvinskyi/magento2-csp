<?php
/**
 * Copyright (c) 2026. Driftworks. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Whitelist;

use Hryvinskyi\Csp\Model\Config\Source\HashValidationOptions;
use Hryvinskyi\Csp\Model\Config\Source\RedundancyStatusOptions;
use Hryvinskyi\Csp\Model\Whitelist\ListingDataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers \Hryvinskyi\Csp\Model\Whitelist\ListingDataProvider
 */
class ListingDataProviderTest extends TestCase
{
    // ==================== Sort Order Extraction Tests ====================

    public function testExtractComputedSortOrdersWithHashValidation(): void
    {
        $sortOrders = $this->extractSortOrders([
            $this->createMockSortOrder('hash_validation', 'ASC'),
        ]);

        $this->assertCount(1, $sortOrders);
        $this->assertEquals('hash_validation', $sortOrders[0]['field']);
        $this->assertEquals('ASC', $sortOrders[0]['direction']);
    }

    public function testExtractComputedSortOrdersWithRedundancyStatus(): void
    {
        $sortOrders = $this->extractSortOrders([
            $this->createMockSortOrder('redundancy_status', 'DESC'),
        ]);

        $this->assertCount(1, $sortOrders);
        $this->assertEquals('redundancy_status', $sortOrders[0]['field']);
        $this->assertEquals('DESC', $sortOrders[0]['direction']);
    }

    public function testExtractComputedSortOrdersExcludesNonComputedColumns(): void
    {
        $sortOrders = $this->extractSortOrders([
            $this->createMockSortOrder('rule_id', 'ASC'),
            $this->createMockSortOrder('identifier', 'DESC'),
        ]);

        $this->assertCount(0, $sortOrders);
    }

    public function testExtractComputedSortOrdersMixedColumns(): void
    {
        $sortOrders = $this->extractSortOrders([
            $this->createMockSortOrder('rule_id', 'ASC'),
            $this->createMockSortOrder('hash_validation', 'DESC'),
            $this->createMockSortOrder('redundancy_status', 'ASC'),
        ]);

        $this->assertCount(2, $sortOrders);
        $this->assertEquals('hash_validation', $sortOrders[0]['field']);
        $this->assertEquals('DESC', $sortOrders[0]['direction']);
        $this->assertEquals('redundancy_status', $sortOrders[1]['field']);
        $this->assertEquals('ASC', $sortOrders[1]['direction']);
    }

    public function testExtractComputedSortOrdersNormalizesDirection(): void
    {
        $sortOrders = $this->extractSortOrders([
            $this->createMockSortOrder('hash_validation', 'desc'),
        ]);

        $this->assertEquals('DESC', $sortOrders[0]['direction']);

        $sortOrders = $this->extractSortOrders([
            $this->createMockSortOrder('hash_validation', 'asc'),
        ]);

        $this->assertEquals('ASC', $sortOrders[0]['direction']);
    }

    // ==================== Sorting Application Tests ====================

    public function testApplyComputedSortOrdersHashValidationAscending(): void
    {
        $items = [
            ['rule_id' => 1, 'hash_validation' => HashValidationOptions::INVALID],
            ['rule_id' => 2, 'hash_validation' => HashValidationOptions::VALID],
            ['rule_id' => 3, 'hash_validation' => HashValidationOptions::NOT_APPLICABLE],
            ['rule_id' => 4, 'hash_validation' => HashValidationOptions::NOT_VERIFIED],
        ];

        $sortOrders = [['field' => 'hash_validation', 'direction' => 'ASC']];
        $result = $this->applySortOrders($items, $sortOrders);

        // Sorted ASC: NOT_APPLICABLE (0), NOT_VERIFIED (1), VALID (2), INVALID (3)
        $this->assertEquals(HashValidationOptions::NOT_APPLICABLE, $result[0]['hash_validation']);
        $this->assertEquals(HashValidationOptions::NOT_VERIFIED, $result[1]['hash_validation']);
        $this->assertEquals(HashValidationOptions::VALID, $result[2]['hash_validation']);
        $this->assertEquals(HashValidationOptions::INVALID, $result[3]['hash_validation']);
    }

    public function testApplyComputedSortOrdersHashValidationDescending(): void
    {
        $items = [
            ['rule_id' => 1, 'hash_validation' => HashValidationOptions::NOT_APPLICABLE],
            ['rule_id' => 2, 'hash_validation' => HashValidationOptions::VALID],
            ['rule_id' => 3, 'hash_validation' => HashValidationOptions::INVALID],
            ['rule_id' => 4, 'hash_validation' => HashValidationOptions::NOT_VERIFIED],
        ];

        $sortOrders = [['field' => 'hash_validation', 'direction' => 'DESC']];
        $result = $this->applySortOrders($items, $sortOrders);

        // Sorted DESC: INVALID (3), VALID (2), NOT_VERIFIED (1), NOT_APPLICABLE (0)
        $this->assertEquals(HashValidationOptions::INVALID, $result[0]['hash_validation']);
        $this->assertEquals(HashValidationOptions::VALID, $result[1]['hash_validation']);
        $this->assertEquals(HashValidationOptions::NOT_VERIFIED, $result[2]['hash_validation']);
        $this->assertEquals(HashValidationOptions::NOT_APPLICABLE, $result[3]['hash_validation']);
    }

    public function testApplyComputedSortOrdersRedundancyStatusAscending(): void
    {
        $items = [
            ['rule_id' => 1, 'redundancy_status' => RedundancyStatusOptions::REDUNDANT],
            ['rule_id' => 2, 'redundancy_status' => RedundancyStatusOptions::UNIQUE],
            ['rule_id' => 3, 'redundancy_status' => RedundancyStatusOptions::DUPLICATE],
        ];

        $sortOrders = [['field' => 'redundancy_status', 'direction' => 'ASC']];
        $result = $this->applySortOrders($items, $sortOrders);

        $this->assertEquals(RedundancyStatusOptions::UNIQUE, $result[0]['redundancy_status']);
        $this->assertEquals(RedundancyStatusOptions::DUPLICATE, $result[1]['redundancy_status']);
        $this->assertEquals(RedundancyStatusOptions::REDUNDANT, $result[2]['redundancy_status']);
    }

    public function testApplyComputedSortOrdersRedundancyStatusDescending(): void
    {
        $items = [
            ['rule_id' => 1, 'redundancy_status' => RedundancyStatusOptions::UNIQUE],
            ['rule_id' => 2, 'redundancy_status' => RedundancyStatusOptions::DUPLICATE],
            ['rule_id' => 3, 'redundancy_status' => RedundancyStatusOptions::REDUNDANT],
        ];

        $sortOrders = [['field' => 'redundancy_status', 'direction' => 'DESC']];
        $result = $this->applySortOrders($items, $sortOrders);

        $this->assertEquals(RedundancyStatusOptions::REDUNDANT, $result[0]['redundancy_status']);
        $this->assertEquals(RedundancyStatusOptions::DUPLICATE, $result[1]['redundancy_status']);
        $this->assertEquals(RedundancyStatusOptions::UNIQUE, $result[2]['redundancy_status']);
    }

    public function testApplyComputedSortOrdersWithMissingValues(): void
    {
        $items = [
            ['rule_id' => 1, 'hash_validation' => HashValidationOptions::INVALID],
            ['rule_id' => 2],
            ['rule_id' => 3, 'hash_validation' => HashValidationOptions::VALID],
        ];

        $sortOrders = [['field' => 'hash_validation', 'direction' => 'ASC']];
        $result = $this->applySortOrders($items, $sortOrders);

        // Missing value defaults to 0, so it comes first in ASC
        $this->assertEquals(2, $result[0]['rule_id']);
        $this->assertEquals(HashValidationOptions::VALID, $result[1]['hash_validation']);
        $this->assertEquals(HashValidationOptions::INVALID, $result[2]['hash_validation']);
    }

    public function testApplyComputedSortOrdersStableSort(): void
    {
        $items = [
            ['rule_id' => 1, 'hash_validation' => HashValidationOptions::VALID],
            ['rule_id' => 2, 'hash_validation' => HashValidationOptions::VALID],
            ['rule_id' => 3, 'hash_validation' => HashValidationOptions::VALID],
        ];

        $sortOrders = [['field' => 'hash_validation', 'direction' => 'ASC']];
        $result = $this->applySortOrders($items, $sortOrders);

        // All have same hash_validation, original order preserved
        $this->assertEquals(1, $result[0]['rule_id']);
        $this->assertEquals(2, $result[1]['rule_id']);
        $this->assertEquals(3, $result[2]['rule_id']);
    }

    public function testApplyComputedSortOrdersEmptyArray(): void
    {
        $items = [];
        $sortOrders = [['field' => 'hash_validation', 'direction' => 'ASC']];
        $result = $this->applySortOrders($items, $sortOrders);

        $this->assertEmpty($result);
    }

    public function testApplyComputedSortOrdersEmptySortOrders(): void
    {
        $items = [
            ['rule_id' => 1, 'hash_validation' => HashValidationOptions::INVALID],
            ['rule_id' => 2, 'hash_validation' => HashValidationOptions::VALID],
        ];

        $result = $this->applySortOrders($items, []);

        // No sorting applied, original order preserved
        $this->assertEquals(1, $result[0]['rule_id']);
        $this->assertEquals(2, $result[1]['rule_id']);
    }

    // ==================== Helper Methods ====================

    /**
     * @param array<int, object> $mockSortOrders
     * @return array<int, array{field: string, direction: string}>
     */
    private function extractSortOrders(array $mockSortOrders): array
    {
        $searchCriteriaMock = $this->createMock(\Magento\Framework\Api\Search\SearchCriteriaInterface::class);
        $searchCriteriaMock->method('getSortOrders')->willReturn($mockSortOrders);
        $searchCriteriaMock->method('setSortOrders')->willReturnSelf();

        $reflection = new ReflectionClass(ListingDataProvider::class);
        $method = $reflection->getMethod('extractComputedSortOrders');
        $method->setAccessible(true);

        $dataProvider = $this->createDataProviderStub();
        return $method->invoke($dataProvider, $searchCriteriaMock);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<int, array{field: string, direction: string}> $sortOrders
     * @return array<int, array<string, mixed>>
     */
    private function applySortOrders(array $items, array $sortOrders): array
    {
        $reflection = new ReflectionClass(ListingDataProvider::class);
        $method = $reflection->getMethod('applyComputedSortOrders');
        $method->setAccessible(true);

        $dataProvider = $this->createDataProviderStub();
        return $method->invoke($dataProvider, $items, $sortOrders);
    }

    /**
     * @return object
     */
    private function createMockSortOrder(string $field, string $direction): object
    {
        $mock = $this->createMock(\Magento\Framework\Api\SortOrder::class);
        $mock->method('getField')->willReturn($field);
        $mock->method('getDirection')->willReturn($direction);
        return $mock;
    }

    private function createDataProviderStub(): ListingDataProvider
    {
        $reportingMock = $this->createMock(\Magento\Framework\Api\Search\ReportingInterface::class);
        $searchCriteriaBuilderMock = $this->createMock(\Magento\Framework\Api\Search\SearchCriteriaBuilder::class);
        $requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $filterBuilderMock = $this->createMock(\Magento\Framework\Api\FilterBuilder::class);
        $redundancyCalculatorMock = $this->createMock(\Hryvinskyi\Csp\Api\RedundancyCalculatorInterface::class);
        $hashValidationCalculatorMock = $this->createMock(\Hryvinskyi\Csp\Api\HashValidationCalculatorInterface::class);

        return new ListingDataProvider(
            'test_data_source',
            'rule_id',
            'id',
            $reportingMock,
            $searchCriteriaBuilderMock,
            $requestMock,
            $filterBuilderMock,
            $redundancyCalculatorMock,
            $hashValidationCalculatorMock
        );
    }
}
