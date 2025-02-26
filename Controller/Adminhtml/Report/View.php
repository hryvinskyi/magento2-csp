<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Report;

use Hryvinskyi\Csp\Api\ReportRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * @method \Magento\Framework\App\Request\Http getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 */
class View extends Action
{
    public function __construct(
        Context $context,
        private readonly ReportRepositoryInterface $entityRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        if ($id === null) {
            return $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        }

        try {
            $this->entityRepository->getById((int)$id);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->prepend(__("Content Security Policy - Violation Report"));
        $resultPage->setActiveMenu('Hryvinskyi_Csp::violation_report');
        $resultPage->addBreadcrumb(__('Violation Report'), __('Violation Report'));
        $resultPage->addBreadcrumb(__('Content Security Policy'), __('Content Security Policy'));

        return $resultPage;
    }
}
