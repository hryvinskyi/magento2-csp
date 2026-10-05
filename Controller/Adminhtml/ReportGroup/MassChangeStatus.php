<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\ReportGroup;

use Hryvinskyi\Csp\Api\Data\ReportGroupInterface;
use Hryvinskyi\Csp\Api\Data\Status;
use Hryvinskyi\Csp\Api\ReportGroupRepositoryInterface;
use Hryvinskyi\Csp\Model\ResourceModel\ReportGroup\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Sets the status of the report groups selected in the grid.
 */
class MassChangeStatus extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_Csp::reports_manage';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param ReportGroupRepositoryInterface $reportGroupRepository
     */
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
    public function execute()
    {
        $statusCode = $this->getRequest()->getParam('status');
        $status = is_numeric($statusCode) ? Status::tryFrom((int)$statusCode) : null;
        if ($status === null) {
            $this->messageManager->addErrorMessage((string)__('Choose a valid status.'));

            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }
        $updated = 0;
        try {
            foreach ($this->filter->getCollection($this->collectionFactory->create()) as $group) {
                if ($group instanceof ReportGroupInterface) {
                    $group->setStatus($status->value);
                    $this->reportGroupRepository->save($group);
                    $updated++;
                }
            }
            $this->messageManager->addSuccessMessage((string)__('A total of %1 record(s) have been updated.', $updated));
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
