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
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;

/**
 * Whitelist grid rows with the store ids of each entry; a `store_id` filter selects entries that apply to the store.
 */
class Collection extends SearchResult
{
    /**
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param StoreLinks $storeLinks
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
     * Attach the store ids of the loaded rows.
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        $storeIds = $this->storeLinks->storeIdsByRule(array_values(array_map('intval', array_keys($this->_items))));
        foreach ($this->_items as $id => $item) {
            $item->setData(WhitelistInterface::STORE_ID, $storeIds[(int)$id] ?? []);
        }

        return parent::_afterLoad();
    }
}
