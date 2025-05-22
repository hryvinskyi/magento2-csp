<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ResourceModel\ReportGroup;

use Hryvinskyi\Csp\Model\ResourceModel\ReportGroup as ReportGroupResource;
use Hryvinskyi\Csp\Model\ReportGroup as ReportGroupModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 *
 * @method ReportGroupResource getResource()
 * @method ReportGroupModel[] getItems()
 * @method ReportGroupModel[] getItemsByColumnValue($column, $value)
 * @method ReportGroupModel getFirstItem()
 * @method ReportGroupModel getLastItem()
 * @method ReportGroupModel getItemByColumnValue($column, $value)
 * @method ReportGroupModel getItemById($idValue)
 * @method ReportGroupModel getNewEmptyItem()
 * @method ReportGroupModel fetchItem()
 * @property ReportGroupModel[] _items
 * @property ReportGroupResource _resource
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected $_eventPrefix = 'hryvinskyi_csp_reportgroup_collection';

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
        $this->_init(ReportGroupModel::class, ReportGroupResource::class);
        $this->_setIdFieldName($this->getResource()->getIdFieldName());
    }
}
