<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

use Magento\Framework\App\Response\HttpInterface as HttpResponse;

interface LaminasPluginRegistrarInterface
{
    /**
     * Register Laminas HTTP plugins for CSP headers
     *
     * @param HttpResponse $response
     * @return bool True if plugins were registered successfully
     */
    public function registerPlugins(HttpResponse $response): bool;
}