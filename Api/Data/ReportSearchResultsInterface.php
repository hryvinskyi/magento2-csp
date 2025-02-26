<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface ReportSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get Report list.
     *
     * @return \Hryvinskyi\Csp\Api\Data\ReportInterface[]
     */
    public function getItems(): array;

    /**
     * Set Report list.
     *
     * @param \Hryvinskyi\Csp\Api\Data\ReportInterface[] $items
     *
     * @return $this
     */
    public function setItems(?array $items = null): ReportSearchResultsInterface;
}
