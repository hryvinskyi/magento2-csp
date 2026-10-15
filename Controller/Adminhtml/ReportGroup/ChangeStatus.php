<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\ReportGroup;

use Hryvinskyi\Csp\Api\Data\Status;
use Hryvinskyi\Csp\Api\ReportGroupRepositoryInterface;
use Hryvinskyi\Csp\Model\Admin\EscapedMessages;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Sets the status of one report group.
 */
class ChangeStatus extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_Csp::reports_manage';

    /**
     * @param Context $context
     * @param ReportGroupRepositoryInterface $reportGroupRepository
     * @param EscapedMessages $messages
     */
    public function __construct(
        Context $context,
        private readonly ReportGroupRepositoryInterface $reportGroupRepository,
        private readonly EscapedMessages $messages
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $statusCode = $this->getRequest()->getParam('status');
        $status = is_numeric($statusCode) ? Status::tryFrom((int)$statusCode) : null;
        if (!is_numeric($id) || $status === null) {
            $this->messages->error(__('Choose a report group and a valid status.'));

            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }
        try {
            $group = $this->reportGroupRepository->getById((int)$id);
            $group->setStatus($status->value);
            $this->reportGroupRepository->save($group);
            $this->messages->success(__('The report group status has been updated.'));
        } catch (LocalizedException $exception) {
            $this->messages->failure($exception);
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
