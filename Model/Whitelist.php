<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist as WhitelistResource;
use Magento\Framework\Model\AbstractModel;

/**
 * @method WhitelistResource getResource()
 * @method \Hryvinskyi\Csp\Model\ResourceModel\Whitelist\Collection getCollection()
 * @method \Hryvinskyi\Csp\Model\ResourceModel\Whitelist\Collection getResourceCollection()
 */
class Whitelist extends AbstractModel implements WhitelistInterface
{
    /**
     * @inheritdoc
     */
    protected $_eventPrefix = 'hryvinskyi_csp_model_whitelist';

    /**
     * @inheritdoc
     * @noinspection MagicMethodsValidityInspection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function _construct()
    {
        $this->_init(WhitelistResource::class);
    }

    /**
     * @inheritdoc
     */
    public function getRuleId(): ?int
    {
        return $this->_getData(self::RULE_ID) === null ? null :
            (int)$this->_getData(self::RULE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRuleId(int $ruleId): WhitelistInterface
    {
        $this->setData(self::RULE_ID, $ruleId);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier(): ?string
    {
        return $this->_getData(self::IDENTIFIER) === null ? null :
            (string)$this->_getData(self::IDENTIFIER);
    }

    /**
     * @inheritdoc
     */
    public function setIdentifier(string $identifier): WhitelistInterface
    {
        $this->setData(self::IDENTIFIER, $identifier);

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
    public function setPolicy(string $policy): WhitelistInterface
    {
        $this->setData(self::POLICY, $policy);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getValueType(): ?string
    {
        return $this->_getData(self::VALUE_TYPE) === null ? null :
            (string)$this->_getData(self::VALUE_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function setValueType(string $valueType): WhitelistInterface
    {
        $this->setData(self::VALUE_TYPE, $valueType);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getValueAlgorithm(): ?string
    {
        return $this->_getData(self::VALUE_ALGORITHM) === null ? null :
            (string)$this->_getData(self::VALUE_ALGORITHM);
    }

    /**
     * @inheritdoc
     */
    public function setValueAlgorithm(string $valueAlgorithm): WhitelistInterface
    {
        $this->setData(self::VALUE_ALGORITHM, $valueAlgorithm);

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
    public function setValue(string $value): WhitelistInterface
    {
        $this->setData(self::VALUE, $value);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStoreIds(): ?string
    {
        return $this->_getData(self::STORE_IDS) === null ? null :
            (string)$this->_getData(self::STORE_IDS);
    }

    /**
     * @inheritdoc
     */
    public function setStoreIds(string $storeIds): WhitelistInterface
    {
        $this->setData(self::STORE_IDS, $storeIds);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->_getData(self::CREATED_AT) === null ? null :
            (string)$this->_getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(string $createdAt): WhitelistInterface
    {
        $this->setData(self::CREATED_AT, $createdAt);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->_getData(self::UPDATED_AT) === null ? null :
            (string)$this->_getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(string $updatedAt): WhitelistInterface
    {
        $this->setData(self::UPDATED_AT, $updatedAt);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): ?int
    {
        return $this->_getData(self::STATUS) === null ? null :
            (int)$this->_getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus(int $status): WhitelistInterface
    {
        $this->setData(self::STATUS, $status);

        return $this;
    }
}
