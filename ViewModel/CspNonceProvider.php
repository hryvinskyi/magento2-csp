<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\ViewModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class CspNonceProvider implements ArgumentInterface
{
    public function __construct(private readonly \Magento\Csp\Helper\CspNonceProvider $cspNonceProvider)
    {
    }

    /**
     * Returns a nonce value for Content Security Policy (CSP) headers.
     *
     * @return string
     */
    public function getNonce(): string
    {
        try {
            return $this->cspNonceProvider->generateNonce();
        } catch (LocalizedException $e) {
            return '';
        }
    }
}
