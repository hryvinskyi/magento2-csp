<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Magento\Framework\Api\Search\SearchResult;
use Hryvinskyi\Csp\Api\Data\ReportSearchResultsInterface;

/**
 * Class ReportSearchResults
 */
class ReportSearchResults extends SearchResult implements ReportSearchResultsInterface
{
    /**
     * @inheritdoc
     */
    public function getItems(): array
    {
        return parent::getItems() ?? [];
    }

    /**
     * @inheritdoc
     */
    public function setItems(?array $items = null): ReportSearchResultsInterface
    {
        $this->setData(self::ITEMS, $items);

        return $this;
    }
}
