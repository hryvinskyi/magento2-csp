<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Data;

interface WhitelistInterface
{
    /**#@+
     * Constants for keys of data array.
     */
    public const RULE_ID = 'rule_id';
    public const IDENTIFIER = 'identifier';
    public const POLICY = 'policy';
    public const VALUE_TYPE = 'value_type';
    public const VALUE_ALGORITHM = 'value_algorithm';
    public const VALUE = 'value';
    public const STORE_IDS = 'store_ids';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const STATUS = 'status';
    public const SCRIPT_CONTENT = 'script_content';
    /**#@-*/


    /**
     * Get RuleId value
     *
     * @return int|null
     */
    public function getRuleId(): ?int;

    /**
     * Set RuleId value
     *
     * @param int $ruleId
     *
     * @return $this
     */
    public function setRuleId(int $ruleId): WhitelistInterface;

    /**
     * Get Identifier value
     *
     * @return string|null
     */
    public function getIdentifier(): ?string;

    /**
     * Set Identifier value
     *
     * @param string $identifier
     *
     * @return $this
     */
    public function setIdentifier(string $identifier): WhitelistInterface;

    /**
     * Get Policy value
     *
     * @return string|null
     */
    public function getPolicy(): ?string;

    /**
     * Set Policy value
     *
     * @param string $policy
     *
     * @return $this
     */
    public function setPolicy(string $policy): WhitelistInterface;

    /**
     * Get ValueType value
     *
     * @return string|null
     */
    public function getValueType(): ?string;

    /**
     * Set ValueType value
     *
     * @param string $valueType
     *
     * @return $this
     */
    public function setValueType(string $valueType): WhitelistInterface;

    /**
     * Get ValueAlgorithm value
     *
     * @return string|null
     */
    public function getValueAlgorithm(): ?string;

    /**
     * Set ValueAlgorithm value
     *
     * @param string $valueAlgorithm
     *
     * @return $this
     */
    public function setValueAlgorithm(string $valueAlgorithm): WhitelistInterface;

    /**
     * Get Value value
     *
     * @return string|null
     */
    public function getValue(): ?string;

    /**
     * Set Value value
     *
     * @param string $value
     *
     * @return $this
     */
    public function setValue(string $value): WhitelistInterface;

    /**
     * Get StoreIds value
     *
     * @return string|null
     */
    public function getStoreIds(): ?string;

    /**
     * Set StoreIds value
     *
     * @param string $storeIds
     *
     * @return $this
     */
    public function setStoreIds(string $storeIds): WhitelistInterface;

    /**
     * Get CreatedAt value
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set CreatedAt value
     *
     * @param string $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(string $createdAt): WhitelistInterface;

    /**
     * Get UpdatedAt value
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set UpdatedAt value
     *
     * @param string $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt(string $updatedAt): WhitelistInterface;

    /**
     * Get Status value
     *
     * @return int|null
     */
    public function getStatus(): ?int;

    /**
     * Set Status value
     *
     * @param int $status
     *
     * @return $this
     */
    public function setStatus(int $status): WhitelistInterface;

    /**
     * Get Script Content value
     *
     * @return string|null
     */
    public function getScriptContent(): ?string;

    /**
     * Set Script Content value
     *
     * @param string $content
     *
     * @return $this
     */
    public function setScriptContent(string $content): WhitelistInterface;
}
