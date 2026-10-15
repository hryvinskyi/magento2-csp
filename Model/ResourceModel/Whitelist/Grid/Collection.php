<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ResourceModel\Whitelist\Grid;

use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\StoreLinks;
use Hryvinskyi\Csp\Model\Whitelist\StoreScopeText;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

/**
 * Whitelist grid rows with the stores of each entry; a `store_id` filter selects entries that apply to the store.
 *
 * A row carries its store ids under `store_id_list`, and its stores as text under `store_id`: the grid's export writes
 * `store_id` as it finds it, and the whitelist import reads that text back. The grid itself shows the ids.
 */
class Collection extends SearchResult
{
    public const STORE_ID_LIST = 'store_id_list';

    /**
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param StoreLinks $storeLinks
     * @param StoreScopeText $storeScopeText
     * @param string $mainTable
     * @param string|null $resourceModel
     * @param string|null $identifierName
     * @param string|null $connectionName
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        private readonly StoreLinks $storeLinks,
        private readonly StoreScopeText $storeScopeText,
        $mainTable,
        $resourceModel = null,
        $identifierName = null,
        $connectionName = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel,
            $identifierName,
            $connectionName
        );
    }

    /**
     * Filter on `store_id` selects entries that apply to the given stores; other fields filter as usual.
     *
     * @param string|array<mixed> $field
     * @param array<mixed>|string|null $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === WhitelistInterface::STORE_ID) {
            $this->storeLinks->filterByStores($this->getSelect(), $this->storeLinks->storeIdsFromCondition($condition));

            return $this;
        }

        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * Attach the store ids and the store text of the loaded rows.
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        $storeIds = $this->storeLinks->storeIdsByRule(array_map('intval', array_keys($this->_items)));
        foreach ($this->_items as $id => $item) {
            $ids = $storeIds[(int)$id] ?? [];
            $item->setData(self::STORE_ID_LIST, $ids);
            $item->setData(WhitelistInterface::STORE_ID, $this->storeScopeText->format($ids));
        }

        return parent::_afterLoad();
    }
}
