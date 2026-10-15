<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Report;

use Hryvinskyi\Csp\Api\Data\ReportInterface;
use Hryvinskyi\Csp\Api\ReportRepositoryInterface;
use Hryvinskyi\Csp\Model\Admin\EscapedMessages;
use Hryvinskyi\Csp\Model\UiComponent\Listing\ReportGroupScope;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Deletes the reports selected in the grid, within the report group the grid shows.
 */
class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_Csp::reports_manage';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param ReportGroupScope $scope
     * @param ReportRepositoryInterface $reportRepository
     * @param EscapedMessages $messages
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly ReportGroupScope $scope,
        private readonly ReportRepositoryInterface $reportRepository,
        private readonly EscapedMessages $messages
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $groupId = null;
        $deleted = 0;
        try {
            $groupId = $this->scope->requestedGroupId($this->getRequest());
            foreach ($this->filter->getCollection($this->scope->reports($groupId)) as $report) {
                if ($report instanceof ReportInterface) {
                    $this->reportRepository->delete($report);
                    $deleted++;
                }
            }
            $this->messages->success(__('A total of %1 record(s) have been deleted.', $deleted));
        } catch (LocalizedException $exception) {
            $this->messages->failure($exception);
        }

        return $this->resultRedirectFactory->create()->setPath(
            '*/*/',
            $groupId === null ? [] : [ReportGroupScope::PAGE_PARAM => $groupId]
        );
    }
}
