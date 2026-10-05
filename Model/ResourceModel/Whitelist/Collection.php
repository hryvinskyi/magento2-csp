<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ResourceModel\Whitelist;

use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist as WhitelistResource;
use Hryvinskyi\Csp\Model\Whitelist as WhitelistModel;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Psr\Log\LoggerInterface;

/**
 * Whitelist entries with their store ids.
 *
 * @method WhitelistModel[] getItems()
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected $_eventPrefix = 'hryvinskyi_csp_whitelist_collection';

    /**
     * @inheritdoc
     */
    protected $_eventObject = 'object';

    /**
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param StoreLinks $storeLinks
     * @param AdapterInterface|null $connection
     * @param AbstractDb|null $resource
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        private readonly StoreLinks $storeLinks,
        ?AdapterInterface $connection = null,
        ?AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(WhitelistModel::class, WhitelistResource::class);
    }

    /**
     * Only entries that apply to the store: linked to it or to every store.
     *
     * @param int $storeId
     * @return $this
     */
    public function addStoreFilter(int $storeId): self
    {
        $this->storeLinks->filterByStores($this->getSelect(), [$storeId]);

        return $this;
    }

    /**
     * Only entries that apply to the area: scoped to it or to every area.
     *
     * @param Area $area
     * @return $this
     */
    public function addAreaFilter(Area $area): self
    {
        $this->addFieldToFilter(
            WhitelistInterface::AREA,
            ['in' => array_values(array_unique([Area::ALL->value, $area->value]))]
        );

        return $this;
    }

    /**
     * Only enabled entries.
     *
     * @return $this
     */
    public function addActiveFilter(): self
    {
        $this->addFieldToFilter(WhitelistInterface::STATUS, ['eq' => 1]);

        return $this;
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
     * Attach the store ids of the loaded entries.
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        $ruleIds = array_values(array_map('intval', array_keys($this->_items)));
        $storeIds = $this->storeLinks->storeIdsByRule($ruleIds);
        foreach ($this->_items as $id => $item) {
            if ($item instanceof WhitelistInterface) {
                $item->setStoreIds($storeIds[(int)$id] ?? []);
            }
        }

        return parent::_afterLoad();
    }
}
