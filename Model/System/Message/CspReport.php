<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\System\Message;

use Hryvinskyi\Csp\Api\ReportRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;

class CspReport implements MessageInterface
{
    public function __construct(
        private readonly ReportRepositoryInterface $reportRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getIdentity(): string
    {
        return 'hryvinskyi_csp_report';
    }

    /**
     * @inheritDoc
     */
    public function isDisplayed(): bool
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->setCurrentPage(1)
            ->setPageSize(1);

        $searchCriteria->addFilter('status', 0);

        return (bool)$this->reportRepository->getList($searchCriteria->create())->getTotalCount();
    }

    /**
     * @inheritDoc
     */
    public function getText(): string
    {
        $text = '<style>
    .message-system-collapsible .csp-notice {
        display: inline-block;
        padding: 12px 20px;
        background: #fff6f5;
        border: 1px solid #ffd8d6;
        border-radius: 4px;
        text-decoration: none;
        color: #e22626;
        font-family: \'Admin Fonts\', Arial, sans-serif;
        transition: all 0.2s ease-in-out;
        width: 100%;
    }
    .csp-notice:hover {
        background: #ffeceb;
        border-color: #ffbfbc;
        color: #c91f1f;
        text-decoration: none;
    }
    .csp-notice__title {
        margin: 0 0 4px 0;
        font-size: 14px;
        font-weight: 600;
        line-height: 1.4;
    }
    .csp-notice__text {
        margin: 0;
        font-size: 13px;
        font-weight: 400;
        line-height: 1.4;
    }
</style>
<a href="%1" class="csp-notice">
    <p class="csp-notice__title">Content Security Policy Alert</p>
    <p class="csp-notice__text">Review CSP Reports to update your Content Security Policy settings. Action is required for proper site functionality in Magento 2.4.</p>
</a>';
        $url = $this->urlBuilder->getUrl('hryvinskyi_csp/report/index');
        return __($text, $url)->render();
    }

    /**
     * @inheritDoc
     */
    public function getSeverity(): int
    {
        return self::SEVERITY_CRITICAL;
    }
}