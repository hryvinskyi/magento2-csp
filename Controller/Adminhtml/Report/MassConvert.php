<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Report;

use Hryvinskyi\Csp\Model\Report\Command\CspReportConverterInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Report\CollectionFactory;
use Hryvinskyi\Csp\Model\Cache\CacheCleanerInterface;
use Hryvinskyi\Csp\Model\Whitelist\MassConvertManagerInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Mass convert controller for converting multiple reports to whitelist
 */
class MassConvert extends AbstractReport
{
    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param CspReportConverterInterface $cspReportConverter
     * @param CacheCleanerInterface $cacheCleaner
     * @param MassConvertManagerInterface $massConvertManager
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly CspReportConverterInterface $cspReportConverter,
        private readonly CacheCleanerInterface $cacheCleaner,
        private readonly MassConvertManagerInterface $massConvertManager
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $count = $this->massConvertManager->convertReports($collection, $this->cspReportConverter);
        $this->cacheCleaner->cleanCaches();
        $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been converted.', $count));

        return $this->createRedirectResult('*/*/');
    }
}