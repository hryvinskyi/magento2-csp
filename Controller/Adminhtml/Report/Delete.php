<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Report;

use Hryvinskyi\Csp\Api\ReportRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;

/**
 * Delete controller for deleting a single report
 */
class Delete extends AbstractReport
{
    /**
     * @param Context $context
     * @param ReportRepositoryInterface $reportRepository
     */
    public function __construct(
        Context $context,
        private readonly ReportRepositoryInterface $reportRepository,
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->createRedirectResult('*/*/');
        $id = $this->getRequest()->getParam('id');

        if ($id === null) {
            $this->messageManager->addErrorMessage(__('We can\'t find a report to delete.'));
            return $resultRedirect;
        }

        try {
            $this->reportRepository->deleteById((int)$id);
            $this->messageManager->addSuccessMessage(__('Report has been deleted.'));
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->createRedirectResult('*/*/edit', ['id' => $id]);
        }
    }
}
