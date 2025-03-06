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

/**
 * Interface for mass actions on reports
 */
interface MassActionInterface
{
    /**
     * Delete items from collection
     *
     * @param Collection $collection
     * @param ReportRepositoryInterface $repository
     * @return int Number of deleted items
     */
    public function deleteItems(Collection $collection, ReportRepositoryInterface $repository): int;
}