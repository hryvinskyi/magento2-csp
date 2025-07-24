<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Plugin\Csp\Api\CspRendererInterface;

use Hryvinskyi\Csp\Api\CachedCspManagerInterface;
use Magento\Csp\Model\CspRenderer;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;
use Psr\Log\LoggerInterface;

class CachedPolicyLoader
{
    private bool $cachePoliciesLoaded = false;

    public function __construct(
        private readonly CachedCspManagerInterface $cachedCspManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Load cached CSP policies before rendering
     *
     * @param CspRenderer $subject
     * @param HttpResponse $response
     * @return array
     */
    public function beforeRender(CspRenderer $subject, HttpResponse $response): array
    {
        if (!$this->cachePoliciesLoaded) {
            try {
                // Load cached policies and add them to the dynamic collector
                $this->cachedCspManager->applyToCollector();
                $this->cachePoliciesLoaded = true;
                
                $this->logger->debug('Cached CSP policies loaded before CSP rendering');
            } catch (\Throwable $e) {
                $this->logger->error(
                    'Error while loading cached CSP policies before rendering: ' . $e->getMessage(),
                    ['trace' => $e->getTraceAsString()]
                );
            }
        }

        return [$response];
    }
}