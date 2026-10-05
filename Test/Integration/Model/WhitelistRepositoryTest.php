<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Integration\Model;

use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistInterfaceFactory;
use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\CollectionFactory;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\Grid\Collection as GridCollection;
use Hryvinskyi\Csp\Model\Whitelist\EntryDataMapper;
use Hryvinskyi\Csp\Test\Integration\ResolvesServices;
use Magento\Framework\Exception\CouldNotSaveException;
use PHPUnit\Framework\TestCase;

/**
 * @magentoDbIsolation enabled
 * @magentoAppArea adminhtml
 */
class WhitelistRepositoryTest extends TestCase
{
    use ResolvesServices;

    public function testSavesNormalizedEntryWithItsStores(): void
    {
        $saved = $this->save(['value' => 'HTTPS://CDN.Example.com/js/', 'store_id' => [1]]);

        $loaded = $this->repository()->getById((int)$saved->getRuleId());
        $this->assertSame('https://cdn.example.com/js/', $loaded->getValue());
        $this->assertSame([1], $loaded->getStoreIds());

        $loaded->setStoreIds([0, 1]);
        $this->repository()->save($loaded);
        $this->assertSame([0], $this->repository()->getById((int)$saved->getRuleId())->getStoreIds());
    }

    public function testRefusesAnInvalidEntryAndASecondEntryWithTheSameKey(): void
    {
        $this->save(['value' => 'cdn.example.com', 'area' => Area::FRONTEND->value]);
        $this->save(['value' => 'cdn.example.com', 'area' => Area::ADMINHTML->value]);

        $this->expectException(CouldNotSaveException::class);
        $this->save(['value' => 'CDN.example.com', 'area' => Area::FRONTEND->value]);
    }

    public function testRefusesAKeyword(): void
    {
        $this->expectException(CouldNotSaveException::class);
        $this->save(['value' => "'unsafe-inline'"]);
    }

    public function testCollectionsFilterByStoreAndArea(): void
    {
        $everywhere = (int)$this->save(['value' => 'all.example.com'])->getRuleId();
        $storeOne = (int)$this->save(['value' => 'one.example.com', 'store_id' => [1]])->getRuleId();
        $admin = (int)$this->save(['value' => 'admin.example.com', 'area' => Area::ADMINHTML->value])->getRuleId();
        $disabled = (int)$this->save(['value' => 'off.example.com', 'status' => 0])->getRuleId();

        $storefront = $this->service(CollectionFactory::class)->create()
            ->addActiveFilter()
            ->addStoreFilter(1)
            ->addAreaFilter(Area::FRONTEND);
        $ids = array_map('intval', array_keys($storefront->getItems()));
        $this->assertContains($everywhere, $ids);
        $this->assertContains($storeOne, $ids);
        $this->assertNotContains($admin, $ids);
        $this->assertNotContains($disabled, $ids);

        $adminCollection = $this->service(CollectionFactory::class)->create()
            ->addStoreFilter(0)
            ->addAreaFilter(Area::ADMINHTML);
        $adminIds = array_map('intval', array_keys($adminCollection->getItems()));
        $this->assertContains($admin, $adminIds);
        $this->assertNotContains($storeOne, $adminIds);

        $grid = $this->newInstance(GridCollection::class, [
            'mainTable' => 'hryvinskyi_csp_whitelist',
            'resourceModel' => \Hryvinskyi\Csp\Model\ResourceModel\Whitelist::class,
        ]);
        $grid->addFieldToFilter('store_id', ['eq' => '1']);
        $gridRows = [];
        foreach ($grid->getItems() as $id => $row) {
            $gridRows[(int)$id] = $row->getData(WhitelistInterface::STORE_ID);
        }
        $this->assertSame([1], $gridRows[$storeOne] ?? null);
        $this->assertSame([0], $gridRows[$everywhere] ?? null);
    }

    /**
     * Save a storefront host entry for every store, with the given fields on top.
     *
     * @param array<string, mixed> $data
     * @return WhitelistInterface
     */
    private function save(array $data): WhitelistInterface
    {
        $entry = $this->service(WhitelistInterfaceFactory::class)->create();
        $data += ['identifier' => 'Test', 'policy' => 'img-src', 'value_type' => 'host', 'store_id' => [0], 'area' => 'all', 'status' => 1];
        $this->service(EntryDataMapper::class)->apply($entry, $data);

        return $this->repository()->save($entry);
    }

    /**
     * @return WhitelistRepositoryInterface
     */
    private function repository(): WhitelistRepositoryInterface
    {
        return $this->service(WhitelistRepositoryInterface::class);
    }
}
