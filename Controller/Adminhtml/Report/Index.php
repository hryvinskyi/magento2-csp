<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Report;

use Magento\Framework\Controller\ResultFactory;

/**
 * @method \Magento\Framework\App\Request\Http getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 */
class Index extends \Magento\Backend\App\Action
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->prepend(__("Content Security Policy - Violation Report"));
        $resultPage->setActiveMenu('Hryvinskyi_Csp::violation_report');
        $resultPage->addBreadcrumb(__('Violation Report'), __('Violation Report'));
        $resultPage->addBreadcrumb(__('Content Security Policy'), __('Content Security Policy'));
        return $resultPage;
    }
}
