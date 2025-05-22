<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface ReportGroupSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get ReportGroup list.
     *
     * @return \Hryvinskyi\Csp\Api\Data\ReportGroupInterface[]
     */
    public function getItems(): array;

    /**
     * Set ReportGroup list.
     *
     * @param \Hryvinskyi\Csp\Api\Data\ReportGroupInterface[] $items
     *
     * @return $this
     */
    public function setItems(?array $items = null): ReportGroupSearchResultsInterface;
}
