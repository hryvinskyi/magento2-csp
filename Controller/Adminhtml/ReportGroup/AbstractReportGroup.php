<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\ReportGroup;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;

/**
 * Base controller for Report actions
 */
abstract class AbstractReportGroup extends Action
{
    /**
     * @return ResultInterface
     */
    abstract public function execute();

    /**
     * Get title for the page
     *
     * @return string
     */
    protected function getTitle(): string
    {
        return "Content Security Policy - Violation Report Group";
    }

    /**
     * Create page result
     *
     * @return Page
     */
    protected function createPageResult(): Page
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->prepend(__($this->getTitle()));
        $resultPage->setActiveMenu('Hryvinskyi_Csp::violation_report');
        $resultPage->addBreadcrumb(__('Violation Report Group'), __('Violation Report Group'));
        $resultPage->addBreadcrumb(__('Content Security Policy'), __('Content Security Policy'));

        return $resultPage;
    }

    /**
     * Create redirect result
     *
     * @param string $path
     * @param array $params
     * @return Redirect
     */
    protected function createRedirectResult(string $path, array $params = []): Redirect
    {
        return $this->resultRedirectFactory->create()->setPath($path, $params);
    }
}