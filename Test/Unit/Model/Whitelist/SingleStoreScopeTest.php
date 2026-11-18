<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Whitelist;

use Hryvinskyi\Csp\Model\Whitelist\SingleStoreScope;
use Hryvinskyi\Csp\Test\Unit\Support\CreatesWhitelistEntries;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class SingleStoreScopeTest extends TestCase
{
    use CreatesWhitelistEntries;

    public function testANewEntryWithoutStoresAppliesToEveryStoreInSingleStoreMode(): void
    {
        $entry = $this->whitelistEntry(['store_id' => null]);

        $this->scope(true)->applyTo($entry);

        $this->assertSame([0], $entry->getStoreIds());
    }

    public function testANewEntryWithoutStoresStaysWithoutThemWhenSeveralStoresRun(): void
    {
        $entry = $this->whitelistEntry(['store_id' => null]);

        $this->scope(false)->applyTo($entry);

        $this->assertSame([], $entry->getStoreIds());
    }

    public function testTheStoresChosenForANewEntryAreKept(): void
    {
        $entry = $this->whitelistEntry(['store_id' => [1]]);

        $this->scope(true)->applyTo($entry);

        $this->assertSame([1], $entry->getStoreIds());
    }

    public function testASavedEntryThatLostItsStoresIsNotWidened(): void
    {
        $entry = $this->whitelistEntry(['rule_id' => 7, 'store_id' => []]);

        $this->scope(true)->applyTo($entry);

        $this->assertSame([], $entry->getStoreIds());
    }

    /**
     * @param bool $singleStoreMode
     * @return SingleStoreScope
     */
    private function scope(bool $singleStoreMode): SingleStoreScope
    {
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('isSingleStoreMode')->willReturn($singleStoreMode);

        return new SingleStoreScope($storeManager);
    }
}
