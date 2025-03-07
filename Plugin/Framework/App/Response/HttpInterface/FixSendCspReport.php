<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Plugin\Framework\App\Response\HttpInterface;

use Hryvinskyi\Csp\Api\ConfigInterface;
use Magento\Framework\App\Response\HttpInterface;

class FixSendCspReport
{
    public function __construct(private readonly ConfigInterface $config)
    {
    }

    /**
     * Remove report-to report-endpoint from CSP header
     *
     * @param HttpInterface $subject
     * @param $name
     * @param $value
     * @param $replace
     * @return array
     */
    public function beforeSetHeader(
        HttpInterface $subject,
        $name,
        $value,
        $replace = false
    ) {
        if ($this->config->isReportsEnabled() === false) {
            return [$name, $value, $replace];
        }
        
        if ($name === 'Content-Security-Policy' || $name === 'Content-Security-Policy-Report-Only') {
            $value = str_replace(' report-to report-endpoint;', '', $value);
        }

        return [$name, $value, $replace];
    }
}
