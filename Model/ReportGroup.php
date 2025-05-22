<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Api\Data\ReportGroupInterface;
use Hryvinskyi\Csp\Model\ResourceModel\ReportGroup as ReportGroupResource;
use Magento\Framework\Model\AbstractModel;

/**
 * @method ReportGroupResource getResource()
 * @method \Hryvinskyi\Csp\Model\ResourceModel\ReportGroup\Collection getCollection()
 * @method \Hryvinskyi\Csp\Model\ResourceModel\ReportGroup\Collection getResourceCollection()
 */
class ReportGroup extends AbstractModel implements ReportGroupInterface
{
    /**
     * @inheritdoc
     */
    protected $_eventPrefix = 'hryvinskyi_csp_model_report_group';

    /**
     * @inheritdoc
     * @noinspection MagicMethodsValidityInspection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function _construct()
    {
        $this->_init(ReportGroupResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getGroupId(): ?int
    {
        return $this->_getData(self::GROUP_ID) === null ? null :
            (int)$this->_getData(self::GROUP_ID);
    }

    /**
     * @inheritdoc
     */
    public function setGroupId(int $groupId): ReportGroupInterface
    {
        $this->setData(self::GROUP_ID, $groupId);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getPolicy(): ?string
    {
        return $this->_getData(self::POLICY) === null ? null :
            (string)$this->_getData(self::POLICY);
    }

    /**
     * @inheritdoc
     */
    public function setPolicy(string $policy): ReportGroupInterface
    {
        $this->setData(self::POLICY, $policy);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getValue(): ?string
    {
        return $this->_getData(self::VALUE) === null ? null :
            (string)$this->_getData(self::VALUE);
    }

    /**
     * @inheritdoc
     */
    public function setValue(string $value): ReportGroupInterface
    {
        $this->setData(self::VALUE, $value);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStoreId(): ?int
    {
        return $this->_getData(self::STORE_ID) === null ? null :
            (int)$this->_getData(self::STORE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setStoreId(int $storeId): ReportGroupInterface
    {
        $this->setData(self::STORE_ID, $storeId);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): ?int
    {
        return $this->_getData(self::STATUS) === null ? null :
            (int)$this->_getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus(int $status): ReportGroupInterface
    {
        $this->setData(self::STATUS, $status);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCount(): ?int
    {
        return $this->_getData(self::COUNT) === null ? null :
            (int)$this->_getData(self::COUNT);
    }

    /**
     * @inheritdoc
     */
    public function setCount(int $count): ReportGroupInterface
    {
        $this->setData(self::COUNT, $count);

        return $this;
    }

}
