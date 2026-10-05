<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Response;

use Hryvinskyi\Csp\Model\Policy\DocumentSchemeInterface;
use Magento\Framework\App\Request\Http as HttpRequest;

/**
 * Scheme of the page from the current request (honours Magento's offloader header).
 */
class RequestDocumentScheme implements DocumentSchemeInterface
{
    /**
     * @param HttpRequest $request
     */
    public function __construct(private readonly HttpRequest $request)
    {
    }

    /**
     * @inheritDoc
     */
    public function isSecure(): bool
    {
        return $this->request->isSecure();
    }
}
