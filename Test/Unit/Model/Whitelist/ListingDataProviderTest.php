<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Whitelist;

use Hryvinskyi\Csp\Api\HashValidationCalculatorInterface;
use Hryvinskyi\Csp\Api\RedundancyCalculatorInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\Grid\Collection as GridCollection;
use Hryvinskyi\Csp\Model\Whitelist\Grid\ComputedColumns;
use Hryvinskyi\Csp\Model\Whitelist\ListingDataProvider;
use Magento\Framework\Api\AttributeValue;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResult;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use PHPUnit\Framework\TestCase;

class ListingDataProviderTest extends TestCase
{
    public function testTheGridShowsStoresFromTheStoreIdsAndLeavesTheExportTextOut(): void
    {
        $attributeValues = $this->createStub(AttributeValueFactory::class);
        $attributeValues->method('create')->willReturnCallback(static fn (): AttributeValue => new AttributeValue());
        $row = new Document($attributeValues);
        $row->setData([
            'rule_id' => '4',
            'value' => 'cdn.example.com',
            'store_id' => 'default,de',
            GridCollection::STORE_ID_LIST => [1, 2],
        ]);
        $searchResult = new SearchResult();
        $searchResult->setItems([$row]);
        $searchResult->setTotalCount(1);
        $reporting = $this->createStub(ReportingInterface::class);
        $reporting->method('search')->willReturn($searchResult);
        $criteriaBuilder = $this->createStub(SearchCriteriaBuilder::class);
        $criteriaBuilder->method('create')->willReturn(new SearchCriteria());
        $seen = [];
        $redundancy = $this->createStub(RedundancyCalculatorInterface::class);
        $redundancy->method('calculateForItems')->willReturnCallback(
            static function (array $rows) use (&$seen): array {
                $seen = $rows;

                return $rows;
            }
        );
        $hashes = $this->createStub(HashValidationCalculatorInterface::class);
        $hashes->method('calculateForItems')->willReturnArgument(0);

        $provider = new ListingDataProvider(
            'hryvinskyi_csp_whitelist_listing_data_source',
            'rule_id',
            'id',
            $reporting,
            $criteriaBuilder,
            $this->createStub(RequestInterface::class),
            $this->createStub(FilterBuilder::class),
            $redundancy,
            $hashes,
            new ComputedColumns()
        );
        $data = $provider->getData();

        $this->assertSame(1, $data['totalRecords']);
        $this->assertSame([1, 2], $data['items'][0]['store_id'] ?? null);
        $this->assertArrayNotHasKey(GridCollection::STORE_ID_LIST, $data['items'][0]);
        $judged = $seen[0] ?? null;
        $this->assertSame([1, 2], is_array($judged) ? ($judged['store_id'] ?? null) : null, 'Redundancy is judged on the store ids.');
    }
}
