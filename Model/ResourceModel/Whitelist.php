<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ResourceModel;

use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\StoreLinks;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Whitelist entries with their store links.
 */
class Whitelist extends AbstractDb
{
    public const TABLE = 'hryvinskyi_csp_whitelist';

    /**
     * @param Context $context
     * @param StoreLinks $storeLinks
     * @param string|null $connectionName
     */
    public function __construct(
        Context $context,
        private readonly StoreLinks $storeLinks,
        ?string $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::TABLE, WhitelistInterface::RULE_ID);
    }

    /**
     * Id of the entry with the given natural key, if one exists.
     *
     * @param string $policy
     * @param string $valueType
     * @param string $valueAlgorithm
     * @param string $value
     * @param string $area
     * @return int|null
     */
    public function findIdByNaturalKey(
        string $policy,
        string $valueType,
        string $valueAlgorithm,
        string $value,
        string $area
    ): ?int {
        $connection = $this->getConnection();
        if ($connection === false) {
            return null;
        }
        $id = $connection->fetchOne(
            $connection->select()
                ->from($this->getMainTable(), [WhitelistInterface::RULE_ID])
                ->where('policy = ?', $policy)
                ->where('value_type = ?', $valueType)
                ->where('value_algorithm = ?', $valueAlgorithm)
                ->where('value = ?', $value)
                ->where('area = ?', $area)
        );

        return is_numeric($id) ? (int)$id : null;
    }

    /**
     * Attach the entry's store ids.
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterLoad(AbstractModel $object)
    {
        $id = $object->getId();
        if ($object instanceof WhitelistInterface && is_numeric($id)) {
            $object->setStoreIds($this->storeLinks->storeIdsByRule([(int)$id])[(int)$id] ?? []);
        }

        return parent::_afterLoad($object);
    }

    /**
     * Save the entry's store links.
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        $id = $object->getId();
        if ($object instanceof WhitelistInterface && is_numeric($id)) {
            $this->storeLinks->replace((int)$id, $object->getStoreIds());
        }

        return parent::_afterSave($object);
    }
}
