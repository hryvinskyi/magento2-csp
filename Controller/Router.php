<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller;

use Hryvinskyi\Csp\Api\ReportGroupRepositoryInterface;
use Hryvinskyi\Csp\Api\ReportRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Route\ConfigInterface;
use Magento\Framework\App\RouterInterface;
use Psr\Log\LoggerInterface;

class Router implements RouterInterface
{
    public function __construct(
        private readonly ConfigInterface $routeConfig,
        private readonly RequestInterface $request,
        private readonly ReportRepositoryInterface $reportRepository,
        private readonly ReportGroupRepositoryInterface $reportGroupRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function match(RequestInterface $request)
    {
        $identifier = trim($request->getPathInfo(), '/');
        if ($identifier !== 'csp_report_watch') {
            return null;
        }

        $modules = $this->routeConfig->getModulesByFrontName('csp_report_watch');
        if (empty($modules)) {
            return null;
        }

        $json = $this->request->getContent();
        try {
            $group = $this->reportGroupRepository->saveFromCspReport($json);
            if ($group->getGroupId() === null) {
                exit;
            }
            $this->reportRepository->saveFromCspReport($group->getGroupId(), $json);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }

        exit;
    }
}