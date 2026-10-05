<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Admin;

use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Model\Report\ReportRequestHandler;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Receives violation reports of admin pages (`/csp_report_watch/admin/index`).
 *
 * A storefront route: the browser sends reports without an admin session or URL key.
 */
class Index implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @param RequestInterface $request
     * @param ReportRequestHandler $handler
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly ReportRequestHandler $handler
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(): ResultInterface
    {
        return $this->handler->handle($this->request, Area::ADMINHTML);
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Browsers send reports without a form key.
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
