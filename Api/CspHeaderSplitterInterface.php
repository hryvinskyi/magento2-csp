<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

use Magento\Framework\App\Response\HttpInterface as HttpResponse;

interface CspHeaderSplitterInterface
{
    /**
     * Split a CSP header into multiple headers if needed
     *
     * @param HttpResponse $response
     * @param string $headerName
     * @param string $headerValue
     * @return void
     */
    public function splitHeader(
        HttpResponse $response,
        string $headerName,
        string $headerValue
    ): void;
}