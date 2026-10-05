<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report;

use Hryvinskyi\Csp\Api\Config\ReportingConfigInterface;
use Hryvinskyi\Csp\Api\Data\Area;
use Hryvinskyi\Csp\Model\Whitelist\StoreIdList;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Reads a report request and answers it with an empty 204 whatever was recorded.
 *
 * A body announced larger than the limit is not read. Admin reports belong to store 0.
 */
class ReportRequestHandler
{
    /**
     * @param ReportIngestion $ingestion
     * @param ReportingConfigInterface $config
     * @param RemoteAddress $remoteAddress
     * @param StoreManagerInterface $storeManager
     * @param RawFactory $rawFactory
     */
    public function __construct(
        private readonly ReportIngestion $ingestion,
        private readonly ReportingConfigInterface $config,
        private readonly RemoteAddress $remoteAddress,
        private readonly StoreManagerInterface $storeManager,
        private readonly RawFactory $rawFactory
    ) {
    }

    /**
     * Record the request's violations for the area.
     *
     * @param RequestInterface $request
     * @param Area $area
     * @return Raw
     */
    public function handle(RequestInterface $request, Area $area): Raw
    {
        $result = $this->rawFactory->create();
        $result->setHttpResponseCode(204);
        $result->setContents('');
        if (!$request instanceof HttpRequest) {
            return $result;
        }
        $length = $request->getHeader('Content-Length');
        if (is_numeric($length) && (int)$length > $this->config->getMaxReportBodyBytes()) {
            return $result;
        }
        $contentType = $request->getHeader('Content-Type');
        $clientKey = $this->remoteAddress->getRemoteAddress();
        $this->ingestion->ingest(
            (string)$request->getContent(),
            is_string($contentType) ? $contentType : '',
            is_string($clientKey) ? $clientKey : '',
            $area,
            $area === Area::ADMINHTML ? StoreIdList::ALL_STORES : (int)$this->storeManager->getStore()->getId()
        );

        return $result;
    }
}
