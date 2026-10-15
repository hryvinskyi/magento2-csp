<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\UiComponent\Listing;

use Hryvinskyi\Csp\Api\Data\ReportInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Report\Collection;
use Hryvinskyi\Csp\Model\ResourceModel\Report\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * The report group the report grid shows, carried from the grid page into its mass actions.
 *
 * The grid is limited to a group by `group_id` in the page URL, but a mass action rebuilds the selection on its own
 * request, where that filter is gone: "Select All" would reach every report. Each mass action URL therefore names the
 * scope (a group id, or `all` for the unscoped grid), and a request that names none is refused rather than guessed.
 */
class ReportGroupScope
{
    /**
     * Request parameter of a mass action that names the scope.
     */
    public const PARAM = 'report_group';

    /**
     * Request parameter of the grid page that limits it to a group.
     */
    public const PAGE_PARAM = 'group_id';

    /**
     * Scope of the grid that shows the reports of every group.
     */
    public const ALL = 'all';

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * Scope a mass action URL carries for the grid page's group parameter.
     *
     * @param mixed $pageGroupId Value of the page's `group_id` parameter
     * @return string
     */
    public function urlValue(mixed $pageGroupId): string
    {
        $groupId = $this->groupId($pageGroupId);

        return $groupId === null ? self::ALL : (string)$groupId;
    }

    /**
     * Group a mass action request is limited to; null for every group.
     *
     * @param RequestInterface $request
     * @return int|null
     * @throws LocalizedException When the request names no scope, or an invalid one
     */
    public function requestedGroupId(RequestInterface $request): ?int
    {
        $scope = $request->getParam(self::PARAM);
        if ($scope === self::ALL) {
            return null;
        }

        return $this->groupId($scope) ?? throw new LocalizedException(
            __('The report grid did not say which report group it shows. Reload the grid and try again.')
        );
    }

    /**
     * Reports of the group, or of every group when it is null.
     *
     * @param int|null $groupId
     * @return Collection
     */
    public function reports(?int $groupId): Collection
    {
        $collection = $this->collectionFactory->create();
        if ($groupId !== null) {
            $collection->addFieldToFilter(ReportInterface::GROUP_ID, ['eq' => $groupId]);
        }

        return $collection;
    }

    /**
     * Positive group id of a parameter value, null for anything else.
     *
     * @param mixed $value
     * @return int|null
     */
    private function groupId(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }
        $groupId = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return is_int($groupId) ? $groupId : null;
    }
}
