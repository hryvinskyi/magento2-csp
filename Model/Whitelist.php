<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Api\Data\Area;
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
        return $this->intData(self::RULE_ID);
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
        return $this->stringData(self::IDENTIFIER);
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
        return $this->stringData(self::POLICY);
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
        return $this->stringData(self::VALUE_TYPE);
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
        return $this->stringData(self::VALUE_ALGORITHM);
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
        return $this->stringData(self::VALUE);
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
    public function getStoreIds(): array
    {
        $storeIds = $this->_getData(self::STORE_ID);
        if (!is_array($storeIds)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $id): int => (int)$id,
            array_filter($storeIds, static fn (mixed $id): bool => is_numeric($id))
        ));
    }

    /**
     * @inheritdoc
     */
    public function setStoreIds(array $storeIds): WhitelistInterface
    {
        $this->setData(self::STORE_ID, array_values(array_map('intval', $storeIds)));

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getArea(): string
    {
        $area = $this->_getData(self::AREA);

        return is_string($area) && $area !== '' ? $area : Area::ALL->value;
    }

    /**
     * @inheritdoc
     */
    public function setArea(string $area): WhitelistInterface
    {
        $this->setData(self::AREA, $area);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->stringData(self::CREATED_AT);
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
        return $this->stringData(self::UPDATED_AT);
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
        return $this->intData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus(int $status): WhitelistInterface
    {
        $this->setData(self::STATUS, $status);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getScriptContent(): ?string
    {
        return $this->stringData(self::SCRIPT_CONTENT);
    }

    /**
     * @inheritDoc
     */
    public function setScriptContent(string $content): WhitelistInterface
    {
        $this->setData(self::SCRIPT_CONTENT, $content);

        return $this;
    }

    /**
     * Data value as a string, null when absent or not scalar.
     *
     * @param string $key
     * @return string|null
     */
    private function stringData(string $key): ?string
    {
        $value = $this->_getData($key);

        return is_scalar($value) ? (string)$value : null;
    }

    /**
     * Data value as an integer, null when absent or not numeric.
     *
     * @param string $key
     * @return int|null
     */
    private function intData(string $key): ?int
    {
        $value = $this->_getData($key);

        return is_numeric($value) ? (int)$value : null;
    }
}
