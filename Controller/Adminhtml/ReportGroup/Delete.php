<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\ReportGroup;

use Hryvinskyi\Csp\Api\ReportGroupRepositoryInterface;
use Hryvinskyi\Csp\Model\Admin\EscapedMessages;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Deletes one report group with its reports.
 */
class Delete extends Action implements HttpPostActionInterface
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
        if (!is_numeric($id)) {
            $this->messages->error(__('Choose a report group to delete.'));

            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }
        try {
            $this->reportGroupRepository->deleteById((int)$id);
            $this->messages->success(__('The report group has been deleted.'));
        } catch (LocalizedException $exception) {
            $this->messages->failure($exception);
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
