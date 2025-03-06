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

/**
 * View controller to show report details
 */
class View extends AbstractReport
{
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
        $id = $this->getRequest()->getParam('id');

        if ($id === null) {
            return $this->createPageResult();
        }

        try {
            $this->reportRepository->getById((int)$id);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->createRedirectResult('*/*/');
        }

        return $this->createPageResult();
    }
}