<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Whitelist;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Whitelist grid.
 */
class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_Csp::whitelist';

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        if ($resultPage instanceof Page) {
            $resultPage->setActiveMenu('Hryvinskyi_Csp::whitelist');
            $resultPage->addBreadcrumb((string)__('Content Security Policy'), (string)__('Content Security Policy'));
            $resultPage->addBreadcrumb((string)__('Whitelist Rules'), (string)__('Whitelist Rules'));
            $resultPage->getConfig()->getTitle()->prepend((string)__('Content Security Policy - Whitelist Rules'));
        }

        return $resultPage;
    }
}
