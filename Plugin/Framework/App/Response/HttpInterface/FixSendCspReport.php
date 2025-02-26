<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Plugin\Framework\App\Response\HttpInterface;

use Magento\Framework\App\Response\HttpInterface;

class FixSendCspReport
{
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
        if ($name === 'Content-Security-Policy' || $name === 'Content-Security-Policy-Report-Only') {
            $value = str_replace(' report-to report-endpoint;', '', $value);
        }

        return [$name, $value, $replace];
    }
}