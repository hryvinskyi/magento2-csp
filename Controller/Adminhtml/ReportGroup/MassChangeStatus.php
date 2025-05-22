<?php
/**
 * Copyright (c) 2025. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\ReportGroup;

use Hryvinskyi\Csp\Api\ReportGroupRepositoryInterface;
use Hryvinskyi\Csp\Controller\Adminhtml\Report\AbstractReport;
use Hryvinskyi\Csp\Model\ResourceModel\ReportGroup\CollectionFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

class MassChangeStatus extends AbstractReport
{
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly ReportGroupRepositoryInterface $reportGroupRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $status = $this->getRequest()->getParam('status');

        if (!$status) {
            $this->messageManager->addErrorMessage(__('Missing status parameter'));
            return $this->createRedirectResult('*/*/');
        }

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $count = 0;

            foreach ($collection->getItems() as $reportGroup) {
                $reportGroup->setStatus((int)$status);
                $this->reportGroupRepository->save($reportGroup);
                $count++;
            }

            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been updated.', $count)
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Something went wrong while updating the reports.')
            );
        }

        return $this->createRedirectResult('*/*/');
    }
}