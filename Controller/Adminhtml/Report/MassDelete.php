<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Report;

use Hryvinskyi\Csp\Api\ReportRepositoryInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Report\CollectionFactory;
use Hryvinskyi\Csp\Model\Report\MassActionInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Mass delete controller for deleting multiple reports
 */
class MassDelete extends AbstractReport
{
    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param ReportRepositoryInterface $reportRepository
     * @param MassActionInterface $massAction
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly ReportRepositoryInterface $reportRepository,
        private readonly MassActionInterface $massAction
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $count = $this->massAction->deleteItems($collection, $this->reportRepository);

        $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been deleted.', $count));

        return $this->createRedirectResult('*/*/');
    }
}