<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\System\Message;

use Hryvinskyi\Csp\Model\Response\OversizedHeaderNotice;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Notification\MessageInterface;

/**
 * Warns admins who manage the whitelist that a policy header was sent unsplit above the size limit in the last days,
 * which proxies may answer with an error page.
 */
class OversizedHeader implements MessageInterface
{
    private const ACL_RESOURCE = 'Hryvinskyi_Csp::whitelist';

    /**
     * @param OversizedHeaderNotice $notice
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        private readonly OversizedHeaderNotice $notice,
        private readonly AuthorizationInterface $authorization
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getIdentity(): string
    {
        return 'hryvinskyi_csp_oversized_header';
    }

    /**
     * Shown while an oversized header was recorded within the last days.
     *
     * @return bool
     */
    public function isDisplayed(): bool
    {
        return $this->authorization->isAllowed(self::ACL_RESOURCE) && $this->notice->recent() !== null;
    }

    /**
     * @inheritDoc
     */
    public function getText(): string
    {
        $recent = $this->notice->recent();
        if ($recent === null) {
            return '';
        }

        return (string)__(
            'The %1 header (%2 bytes) exceeded the %3 byte limit and could not be split without changing the policy. Proxies may reject such responses. Remove unused whitelist entries or raise the limit after checking your proxy.',
            $recent['header'],
            $recent['bytes'],
            $recent['limit']
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
