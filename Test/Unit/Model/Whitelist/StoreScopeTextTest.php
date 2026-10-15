<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Whitelist;

use Hryvinskyi\Csp\Model\Whitelist\StoreScopeText;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class StoreScopeTextTest extends TestCase
{
    /**
     * @dataProvider scopes
     * @param list<int> $storeIds
     * @param string $text
     */
    public function testWritesAScopeAndReadsItBack(array $storeIds, string $text): void
    {
        $scopeText = $this->scopeText();

        $this->assertSame($text, $scopeText->format($storeIds));
        $this->assertSame($storeIds, $scopeText->parse($text));
    }

    /**
     * @return array<string, array{list<int>, string}>
     */
    public static function scopes(): array
    {
        return [
            'every store' => [[0], 'All Store Views'],
            'store views by code' => [[1, 3], 'default,fr'],
            'no store' => [[], ''],
        ];
    }

    /**
     * @dataProvider texts
     * @param string $text
     * @param list<int>|null $storeIds
     */
    public function testReadsCodesIdsNamesAndAllStoreViews(string $text, ?array $storeIds): void
    {
        $this->assertSame($storeIds, $this->scopeText()->parse($text));
    }

    /**
     * @return array<string, array{string, list<int>|null}>
     */
    public static function texts(): array
    {
        return [
            'ids' => ['0, 2', [0, 2]],
            'all store views in any case' => ['all store views', [0]],
            'codes in any case' => [' DEFAULT , fr ', [1, 3]],
            'unique name' => ['Français', [3]],
            'name shared by two stores' => ['Shop', null],
            'unknown store' => ['default,nowhere', null],
            'the admin store is not a store view' => ['admin', null],
            'repeated store' => ['1,default', [1]],
        ];
    }

    public function testWritesTheIdOfAStoreThatNoLongerExists(): void
    {
        $this->assertSame('default,99', $this->scopeText()->format([1, 99]));
    }

    /**
     * @return StoreScopeText
     */
    private function scopeText(): StoreScopeText
    {
        $stores = [];
        $definitions = [0 => ['admin', 'Admin'], 1 => ['default', 'Shop'], 2 => ['de', 'Shop'], 3 => ['fr', 'Français']];
        foreach ($definitions as $id => [$code, $name]) {
            $store = $this->createStub(StoreInterface::class);
            $store->method('getId')->willReturn($id);
            $store->method('getCode')->willReturn($code);
            $store->method('getName')->willReturn($name);
            $stores[$id] = $store;
        }
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn($stores);

        return new StoreScopeText($storeManager);
    }
}
