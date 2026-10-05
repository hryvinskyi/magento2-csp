<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Plugin\Csp\Api\CspRendererInterface;

use Hryvinskyi\Csp\Api\CspHeaderProcessorInterface;
use Magento\Csp\Api\CspRendererInterface;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;

/**
 * Optimises and splits the CSP headers once Magento has rendered them.
 */
class ProcessCspHeaders
{
    /**
     * @param CspHeaderProcessorInterface $headerProcessor
     */
    public function __construct(private readonly CspHeaderProcessorInterface $headerProcessor)
    {
    }

    /**
     * Process the headers Magento just wrote to the response.
     *
     * @param CspRendererInterface $subject
     * @param mixed $result
     * @param HttpResponse $response
     * @return mixed
     */
    public function afterRender(CspRendererInterface $subject, mixed $result, HttpResponse $response): mixed
    {
        $this->headerProcessor->processHeaders($response);

        return $result;
    }
}
