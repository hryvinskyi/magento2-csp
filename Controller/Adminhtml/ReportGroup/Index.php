<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\ReportGroup;

use Hryvinskyi\Csp\Api\ViolationReviewInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Report group grid. Opening it marks the violations recorded so far as seen by the admin.
 */
class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_Csp::reports';

    /**
     * @param Context $context
     * @param ViolationReviewInterface $violationReview
     * @param UserContextInterface $userContext
     */
    public function __construct(
        Context $context,
        private readonly ViolationReviewInterface $violationReview,
        private readonly UserContextInterface $userContext
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->markReviewed();
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        if ($page instanceof Page) {
            $page->setActiveMenu('Hryvinskyi_Csp::violation_report');
            $page->addBreadcrumb((string)__('Content Security Policy'), (string)__('Content Security Policy'));
            $page->addBreadcrumb((string)__('Violation Reports'), (string)__('Violation Reports'));
            $page->getConfig()->getTitle()->prepend((string)__('Content Security Policy - Violation Reports'));
        }

        return $page;
    }

    /**
     * Hide the new-violations message for the current admin until the next new violation.
     *
     * @return void
     */
    private function markReviewed(): void
    {
        $userId = $this->userContext->getUserId();
        if ($userId === null || $this->userContext->getUserType() !== UserContextInterface::USER_TYPE_ADMIN) {
            return;
        }
        $this->violationReview->markReviewed((int)$userId);
    }
}
