<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Response;

use Laminas\Http\AbstractMessage;
use Laminas\Http\Header\ContentSecurityPolicy;
use Laminas\Http\Header\ContentSecurityPolicyReportOnly;
use Laminas\Loader\PluginClassLoader;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;

/**
 * Lets a response carry several CSP header lines.
 *
 * Laminas sends a header with `header($line)`, which replaces an earlier line of the same name, unless the header's
 * class is a multiple header. Registering the Laminas CSP header classes makes both CSP headers multiple headers.
 */
class LaminasPluginRegistrar
{
    /**
     * Register the CSP header classes on the response; false when the response is not a Laminas message.
     *
     * @param HttpResponse $response
     * @return bool
     */
    public function registerPlugins(HttpResponse $response): bool
    {
        if (!$response instanceof AbstractMessage) {
            return false;
        }

        $loader = $response->getHeaders()->getPluginClassLoader();
        if (!$loader instanceof PluginClassLoader) {
            return false;
        }

        $loader->registerPlugins([
            'contentsecuritypolicy' => ContentSecurityPolicy::class,
            'contentsecuritypolicyreportonly' => ContentSecurityPolicyReportOnly::class,
        ]);

        return true;
    }
}
