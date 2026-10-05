<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Report;

use Hryvinskyi\Csp\Api\ReportRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Details of one report.
 */
class View extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_Csp::reports';

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
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if (!is_numeric($id) || $this->reportRepository->findById((int)$id) === null) {
            $this->messageManager->addErrorMessage((string)__('The report does not exist.'));

            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        if ($page instanceof Page) {
            $page->setActiveMenu('Hryvinskyi_Csp::violation_report');
            $page->getConfig()->getTitle()->prepend((string)__('Violation Report'));
        }

        return $page;
    }
}
