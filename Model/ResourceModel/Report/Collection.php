<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ResourceModel\Report;

use Hryvinskyi\Csp\Model\ResourceModel\Report as ReportResource;
use Hryvinskyi\Csp\Model\Report as ReportModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @method ReportResource getResource()
 * @method ReportModel[] getItems()
 * @method ReportModel[] getItemsByColumnValue($column, $value)
 * @method ReportModel getFirstItem()
 * @method ReportModel getLastItem()
 * @method ReportModel getItemByColumnValue($column, $value)
 * @method ReportModel getItemById($idValue)
 * @method ReportModel getNewEmptyItem()
 * @method ReportModel fetchItem()
 * @property ReportModel[] _items
 * @property ReportResource _resource
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected $_eventPrefix = 'hryvinskyi_csp_report_collection';

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
        $this->_init(ReportModel::class, ReportResource::class);
        $this->_setIdFieldName($this->getResource()->getIdFieldName());
    }
}
