<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

namespace Hryvinskyi\Csp\Model\ResourceModel\Report\Grid;

use Magento\Backend\Model\Session;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Select;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
    /**
     * @var Session
     */
    private $session;

    /**
     * Get SQL for get record count.
     * Extra GROUP BY strip added.
     * @return Select
     */
    public function getSelectCountSql(): Select
    {
        $this->applyGroup();

        $countSelect = parent::getSelectCountSql();
        $countSelect->reset(\Zend_Db_Select::GROUP);

        return $countSelect;
    }

    /**
     * Redeclare before load method for adding event
     */
    protected function _beforeLoad(): Collection
    {
        $this->applyGroup();
        return parent::_beforeLoad();
    }

    /**
     * Join slide relation table if there is slide filter
     * @return void
     */
    protected function _renderFiltersBefore(): void
    {
        $this->applyGroup();
        parent::_renderFiltersBefore();
    }

    /**
     * Applying filter
     */
    private function applyGroup(): void
    {
        $id = $this->getSession()->getData('group_id');

        if ($id) {
            $this->addFieldToFilter('group_id', $id);
        }
    }

    /**
     * @return Session
     */
    private function getSession(): Session
    {
        if ($this->session === null) {
            $this->session = ObjectManager::getInstance()->get(Session::class);
        }

        return $this->session;
    }
}
