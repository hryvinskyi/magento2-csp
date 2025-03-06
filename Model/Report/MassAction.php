<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report;

use Hryvinskyi\Csp\Api\ReportRepositoryInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\CouldNotDeleteException;

/**
 * Implementation for mass actions on reports
 */
class MassAction implements MassActionInterface
{
    /**
     * Delete items from collection
     *
     * @param Collection $collection
     * @param ReportRepositoryInterface $repository
     * @return int Number of deleted items
     * @throws CouldNotDeleteException
     */
    public function deleteItems(Collection $collection, ReportRepositoryInterface $repository): int
    {
        $count = $collection->count();

        foreach ($collection as $item) {
            $repository->delete($item);
        }

        return $count;
    }
}