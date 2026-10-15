<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Magento\Framework\Api\Search\SearchResult;
use Hryvinskyi\Csp\Api\Data\ReportGroupInterface;
use Hryvinskyi\Csp\Api\Data\ReportGroupSearchResultsInterface;

/**
 * Search results holding report groups.
 */
class ReportGroupSearchResults extends SearchResult implements ReportGroupSearchResultsInterface
{
    /**
     * The report groups held, an empty list until they are set (the parent returns null then, whatever it documents).
     *
     * Documented here because the parent promises search documents while the interface promises report groups; any other
     * item set is left out.
     *
     * @return ReportGroupInterface[]
     */
    public function getItems(): array
    {
        $items = $this->_get(self::ITEMS);

        return is_array($items) ? array_filter($items, static fn (mixed $item): bool => $item instanceof ReportGroupInterface) : [];
    }

    /**
     * Set the report groups held.
     *
     * @param ReportGroupInterface[]|null $items
     * @return ReportGroupSearchResultsInterface
     */
    public function setItems(?array $items = null): ReportGroupSearchResultsInterface
    {
        $this->setData(self::ITEMS, $items);

        return $this;
    }
}
