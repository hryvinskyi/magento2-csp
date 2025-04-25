<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

use Magento\Framework\App\Response\HttpInterface as HttpResponse;

interface CspHeaderProcessorInterface
{
    /**
     * Process CSP headers in response
     *
     * @param HttpResponse $response
     * @return void
     */
    public function processHeaders(HttpResponse $response): void;
}