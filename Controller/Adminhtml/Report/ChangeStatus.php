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
use Magento\Framework\Exception\NoSuchEntityException;

class ChangeStatus extends AbstractReport
{
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
    public function execute(): ResultInterface
    {
        $reportId = (int)$this->getRequest()->getParam('id');
        $status = $this->getRequest()->getParam('status');

        if (!$reportId || !$status) {
            $this->messageManager->addErrorMessage(__('Missing required parameters'));
            return $this->createRedirectResult('*/*/');
        }

        try {
            $report = $this->reportRepository->getById($reportId);
            $report->setStatus((int)$status);
            $this->reportRepository->save($report);
            $this->messageManager->addSuccessMessage(__('Report status has been updated.'));
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage(__('Report with ID %1 does not exist.', $reportId));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not change status: %1', $e->getMessage()));
        }

        return $this->createRedirectResult('*/*/');
    }
}