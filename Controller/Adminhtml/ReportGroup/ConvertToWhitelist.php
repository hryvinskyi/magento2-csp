<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\ReportGroup;

use Hryvinskyi\Csp\Api\ReportGroupRepositoryInterface;
use Hryvinskyi\Csp\Controller\Adminhtml\ConvertsToWhitelist;
use Hryvinskyi\Csp\Model\Conversion\ConversionMessages;
use Hryvinskyi\Csp\Model\Conversion\ReportGroupConverter;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Turns one report group into a whitelist allowance.
 */
class ConvertToWhitelist extends Action implements HttpPostActionInterface
{
    use ConvertsToWhitelist;

    /**
     * @param Context $context
     * @param ReportGroupRepositoryInterface $reportGroupRepository
     * @param ReportGroupConverter $converter
     * @param ConversionMessages $conversionMessages
     */
    public function __construct(
        Context $context,
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
        $id = $this->getRequest()->getParam('id');
        if (!is_numeric($id)) {
            $this->messageManager->addErrorMessage((string)__('Choose a report group to convert.'));

            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }
        try {
            $result = $this->converter->convert($this->reportGroupRepository->getById((int)$id));
            $this->conversionMessages->add($this->messageManager, [$result]);
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
