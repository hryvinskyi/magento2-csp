<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\System\Message;

use Hryvinskyi\Csp\Api\Data\ReportGroupInterface;
use Hryvinskyi\Csp\Api\Data\Status;
use Hryvinskyi\Csp\Model\ResourceModel\ReportGroup\CollectionFactory;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;

/**
 * Tells admins who may review violation reports that report groups wait for review.
 */
class CspReport implements MessageInterface
{
    private const ACL_RESOURCE = 'Hryvinskyi_Csp::reports';

    /**
     * @param CollectionFactory $collectionFactory
     * @param AuthorizationInterface $authorization
     * @param UrlInterface $urlBuilder
     * @param Escaper $escaper
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
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
     * Shown while a pending report group exists.
     *
     * @return bool
     */
    public function isDisplayed(): bool
    {
        if (!$this->authorization->isAllowed(self::ACL_RESOURCE)) {
            return false;
        }
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(ReportGroupInterface::STATUS, ['eq' => Status::PENDING->value])
            ->setPageSize(1);

        return $collection->getFirstItem()->getId() !== null;
    }

    /**
     * @inheritDoc
     */
    public function getText(): string
    {
        return (string)__(
            'Content Security Policy violations wait for review. <a href="%1">Review the violation reports</a> and allow or deny what the pages load.',
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
}
