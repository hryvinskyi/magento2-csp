<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ReportGroup;

use Hryvinskyi\Csp\Api\ReportGroupRepositoryInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\CouldNotDeleteException;

/**
 * @inheritDoc
 */
class MassAction implements MassActionInterface
{
    /**
     * @inheritDoc
     * @throws CouldNotDeleteException
     */
    public function deleteItems(Collection $collection, ReportGroupRepositoryInterface $repository): int
    {
        $count = $collection->count();

        foreach ($collection as $item) {
            $repository->delete($item);
        }

        return $count;
    }
}
