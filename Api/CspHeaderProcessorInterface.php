<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

use Magento\Framework\App\Response\HttpInterface as HttpResponse;

/**
 * Rewrites the CSP headers Magento rendered into a response: optimised and, above the size limit, split.
 *
 * @api
 */
interface CspHeaderProcessorInterface
{
    /**
     * Process the CSP headers of the response.
     *
     * @param HttpResponse $response
     * @return void
     */
    public function processHeaders(HttpResponse $response): void;
}
