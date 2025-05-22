<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Data;

interface ReportGroupInterface
{
    /**
     * Status codes for report
     */
    public const STATUS_CODE_PENDING = 0;
    public const STATUS_CODE_DENIED = 1;
    public const STATUS_CODE_SKIP = 2;

    /**#@+
     * Constants for keys of data array.
     */
    public const GROUP_ID = 'group_id';
    public const POLICY = 'policy';
    public const VALUE = 'value';
    public const STORE_ID = 'store_id';
    public const STATUS = 'status';
    public const COUNT = 'count';
    /**#@-*/


    /**
     * Get GroupId value
     *
     * @return int|null
     */
    public function getGroupId(): ?int;

    /**
     * Set GroupId value
     *
     * @param int $groupId
     *
     * @return $this
     */
    public function setGroupId(int $groupId): ReportGroupInterface;

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
    public function setPolicy(string $policy): ReportGroupInterface;

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
    public function setValue(string $value): ReportGroupInterface;

    /**
     * Get StoreId value
     *
     * @return int|null
     */
    public function getStoreId(): ?int;

    /**
     * Set StoreId value
     *
     * @param int $storeId
     *
     * @return $this
     */
    public function setStoreId(int $storeId): ReportGroupInterface;

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
    public function setStatus(int $status): ReportGroupInterface;

    /**
     * Get Count value
     *
     * @return int|null
     */
    public function getCount(): ?int;

    /**
     * Set Count value
     *
     * @param int $count
     *
     * @return $this
     */
    public function setCount(int $count): ReportGroupInterface;
}
