<?php
/**
 * Copyright (c) 2025. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\ReportGroup;

use Hryvinskyi\Csp\Api\ReportGroupRepositoryInterface;
use Hryvinskyi\Csp\Controller\Adminhtml\Report\AbstractReport;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class ChangeStatus extends AbstractReport
{
    public function __construct(
        Context $context,
        private readonly ReportGroupRepositoryInterface $reportGroupRepository,
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $reportGroupId = (int)$this->getRequest()->getParam('id');
        $status = $this->getRequest()->getParam('status');

        if (!$reportGroupId || !$status) {
            $this->messageManager->addErrorMessage(__('Missing required parameters'));
            return $this->createRedirectResult('*/*/');
        }

        try {
            $reportGroup = $this->reportGroupRepository->getById($reportGroupId);
            $reportGroup->setStatus((int)$status);
            $this->reportGroupRepository->save($reportGroup);
            $this->messageManager->addSuccessMessage(__('Report Group status has been updated.'));
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage(__('Report Group with ID %1 does not exist.', $reportGroupId));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not change status: %1', $e->getMessage()));
        }

        return $this->createRedirectResult('*/*/');
    }
}