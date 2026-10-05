<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\ReportGroup;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Report group grid.
 */
class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_Csp::reports';

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        if ($page instanceof Page) {
            $page->setActiveMenu('Hryvinskyi_Csp::violation_report');
            $page->addBreadcrumb((string)__('Content Security Policy'), (string)__('Content Security Policy'));
            $page->addBreadcrumb((string)__('Violation Reports'), (string)__('Violation Reports'));
            $page->getConfig()->getTitle()->prepend((string)__('Content Security Policy - Violation Reports'));
        }

        return $page;
    }
}
