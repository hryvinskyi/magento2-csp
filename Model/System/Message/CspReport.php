<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\System\Message;

use Hryvinskyi\Csp\Api\Config\ReportingConfigInterface;
use Hryvinskyi\Csp\Api\ViolationReviewInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;

/**
 * Tells an admin who may review violation reports that new violations were recorded since they last opened them.
 * Opening the violation reports hides it until the next new violation.
 */
class CspReport implements MessageInterface
{
    private const ACL_RESOURCE = 'Hryvinskyi_Csp::reports';

    private ?int $unreviewed = null;

    /**
     * @param ReportingConfigInterface $config
     * @param ViolationReviewInterface $violationReview
     * @param UserContextInterface $userContext
     * @param AuthorizationInterface $authorization
     * @param UrlInterface $urlBuilder
     * @param Escaper $escaper
     */
    public function __construct(
        private readonly ReportingConfigInterface $config,
        private readonly ViolationReviewInterface $violationReview,
        private readonly UserContextInterface $userContext,
        private readonly AuthorizationInterface $authorization,
        private readonly UrlInterface $urlBuilder,
        private readonly Escaper $escaper
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
     * Shown while pending violations were recorded after the admin last opened the violation reports.
     *
     * @return bool
     */
    public function isDisplayed(): bool
    {
        return $this->unreviewed() > 0;
    }

    /**
     * @inheritDoc
     */
    public function getText(): string
    {
        return (string)__(
            'New Content Security Policy violations: %1. <a href="%2">Review violation reports</a>',
            $this->unreviewed(),
            $this->escaper->escapeUrl($this->urlBuilder->getUrl('hryvinskyi_csp/reportgroup/index'))
        );
    }

    /**
     * @inheritDoc
     */
    public function getSeverity(): int
    {
        return self::SEVERITY_MAJOR;
    }

    /**
     * Pending groups the current admin has not been shown, counted once per request.
     *
     * @return int
     */
    private function unreviewed(): int
    {
        if ($this->unreviewed === null) {
            $this->unreviewed = $this->countForCurrentAdmin();
        }

        return $this->unreviewed;
    }

    /**
     * @return int
     */
    private function countForCurrentAdmin(): int
    {
        $userId = $this->userContext->getUserId();
        if (!$this->config->isNewViolationNoticeEnabled()
            || $this->userContext->getUserType() !== UserContextInterface::USER_TYPE_ADMIN
            || $userId === null
            || !$this->authorization->isAllowed(self::ACL_RESOURCE)
        ) {
            return 0;
        }

        return $this->violationReview->countUnreviewed((int)$userId);
    }
}
