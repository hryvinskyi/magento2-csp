<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\ReportGroup;

use Hryvinskyi\Csp\Api\Data\ReportGroupInterface;
use Hryvinskyi\Csp\Controller\Adminhtml\ConvertsToWhitelist;
use Hryvinskyi\Csp\Model\Conversion\ConversionMessages;
use Hryvinskyi\Csp\Model\Conversion\ReportGroupConverter;
use Hryvinskyi\Csp\Model\ResourceModel\ReportGroup\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Turns the report groups selected in the grid into whitelist allowances.
 */
class MassConvert extends Action implements HttpPostActionInterface
{
    use ConvertsToWhitelist;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param ReportGroupConverter $converter
     * @param ConversionMessages $conversionMessages
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
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
            foreach ($this->filter->getCollection($this->collectionFactory->create()) as $group) {
                if ($group instanceof ReportGroupInterface) {
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
