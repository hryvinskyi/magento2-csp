<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ResourceModel\Whitelist;

use Hryvinskyi\Csp\Model\ResourceModel\Whitelist as WhitelistResource;
use Hryvinskyi\Csp\Model\Whitelist as WhitelistModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @method WhitelistResource getResource()
 * @method WhitelistModel[] getItems()
 * @method WhitelistModel[] getItemsByColumnValue($column, $value)
 * @method WhitelistModel getFirstItem()
 * @method WhitelistModel getLastItem()
 * @method WhitelistModel getItemByColumnValue($column, $value)
 * @method WhitelistModel getItemById($idValue)
 * @method WhitelistModel getNewEmptyItem()
 * @method WhitelistModel fetchItem()
 * @property WhitelistModel[] _items
 * @property WhitelistResource _resource
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
     * @inheritdoc
     * @noinspection MagicMethodsValidityInspection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _construct()
    {
        $this->_init(WhitelistModel::class, WhitelistResource::class);
        $this->_setIdFieldName($this->getResource()->getIdFieldName());
    }
}
