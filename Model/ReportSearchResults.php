<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Magento\Framework\Api\Search\SearchResult;
use Hryvinskyi\Csp\Api\Data\ReportInterface;
use Hryvinskyi\Csp\Api\Data\ReportSearchResultsInterface;

/**
 * Search results holding reports.
 */
class ReportSearchResults extends SearchResult implements ReportSearchResultsInterface
{
    /**
     * The reports held, an empty list until they are set (the parent returns null then, whatever it documents).
     *
     * Documented here because the parent promises search documents while the interface promises reports; any other
     * item set is left out.
     *
     * @return ReportInterface[]
     */
    public function getItems(): array
    {
        $items = $this->_get(self::ITEMS);

        return is_array($items) ? array_filter($items, static fn (mixed $item): bool => $item instanceof ReportInterface) : [];
    }

    /**
     * Set the reports held.
     *
     * @param ReportInterface[]|null $items
     * @return ReportSearchResultsInterface
     */
    public function setItems(?array $items = null): ReportSearchResultsInterface
    {
        $this->setData(self::ITEMS, $items);

        return $this;
    }
}
