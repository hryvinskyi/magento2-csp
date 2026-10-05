<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Report;

use Hryvinskyi\Csp\Api\Data\ReportInterface;
use Hryvinskyi\Csp\Api\ReportGroupRepositoryInterface;
use Hryvinskyi\Csp\Controller\Adminhtml\ConvertsToWhitelist;
use Hryvinskyi\Csp\Model\Conversion\ConversionMessages;
use Hryvinskyi\Csp\Model\Conversion\ReportGroupConverter;
use Hryvinskyi\Csp\Model\ResourceModel\Report\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Turns the report groups of the reports selected in the grid into whitelist allowances, each group once.
 */
class MassConvert extends Action implements HttpPostActionInterface
{
    use ConvertsToWhitelist;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param ReportGroupRepositoryInterface $reportGroupRepository
     * @param ReportGroupConverter $converter
     * @param ConversionMessages $conversionMessages
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly ReportGroupRepositoryInterface $reportGroupRepository,
        private readonly ReportGroupConverter $converter,
        private readonly ConversionMessages $conversionMessages
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $results = [];
        try {
            $groupIds = [];
            foreach ($this->filter->getCollection($this->collectionFactory->create()) as $report) {
                if ($report instanceof ReportInterface && $report->getGroupId() !== null) {
                    $groupIds[$report->getGroupId()] = true;
                }
            }
            foreach (array_keys($groupIds) as $groupId) {
                $group = $this->reportGroupRepository->findById($groupId);
                if ($group !== null) {
                    $results[] = $this->converter->convert($group);
                }
            }
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }
        $this->conversionMessages->add($this->messageManager, $results);

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
