<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Whitelist;

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
        $resultPage->getConfig()->getTitle()->prepend(__("Content Security Policy - Whitelist Rules"));
        $resultPage->setActiveMenu('Hryvinskyi_Csp::whitelist');
        $resultPage->addBreadcrumb(__('Whitelist Rules'), __('Whitelist Rules'));
        $resultPage->addBreadcrumb(__('Content Security Policy'), __('Content Security Policy'));
        return $resultPage;
    }
}
