<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Report;

use Hryvinskyi\Csp\Api\ReportRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Deletes one report.
 */
class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_Csp::reports_manage';

    /**
     * @param Context $context
     * @param ReportRepositoryInterface $reportRepository
     */
    public function __construct(
        Context $context,
        private readonly ReportRepositoryInterface $reportRepository
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
            $this->messageManager->addErrorMessage((string)__('Choose a report to delete.'));

            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }
        try {
            $this->reportRepository->deleteById((int)$id);
            $this->messageManager->addSuccessMessage((string)__('The report has been deleted.'));
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
