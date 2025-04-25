<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Plugin\Csp\Api\CspRendererInterface;

use Hryvinskyi\Csp\Api\CspHeaderProcessorInterface;
use Magento\Csp\Model\CspRenderer;
use Magento\Framework\App\Response\HttpInterface as HttpResponse;

class HeaderSplitter
{
    public function __construct(
        private readonly CspHeaderProcessorInterface $cspHeaderProcessor
    ) {
    }

    /**
     * Process CSP headers after they've been rendered
     *
     * @param CspRenderer $subject
     * @param null $result
     * @param HttpResponse $response
     * @return null
     */
    public function afterRender(
        CspRenderer $subject,
        $result,
        HttpResponse $response
    ) {
        $this->cspHeaderProcessor->processHeaders($response);
        return $result;
    }
}