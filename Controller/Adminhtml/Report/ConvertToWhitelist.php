<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Report;

use Hryvinskyi\Csp\Api\ReportRepositoryInterface;
use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Hryvinskyi\Csp\Model\Report\Command\CspReportConverterInterface;
use Hryvinskyi\Csp\Model\Cache\CacheCleanerInterface;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistManagerInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;

/**
 * Convert to whitelist controller for converting a report to whitelist
 */
class ConvertToWhitelist extends AbstractReport
{
    /**
     * @param Context $context
     * @param ReportRepositoryInterface $reportRepository
     * @param WhitelistRepositoryInterface $whitelistRepository
     * @param CspReportConverterInterface $cspReportConverter
     * @param CacheCleanerInterface $cacheCleaner
     * @param WhitelistManagerInterface $whitelistManager
     */
    public function __construct(
        Context $context,
        private readonly ReportRepositoryInterface $reportRepository,
        private readonly CspReportConverterInterface $cspReportConverter,
        private readonly CacheCleanerInterface $cacheCleaner,
        private readonly WhitelistManagerInterface $whitelistManager
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->createRedirectResult('*/*/');
        $id = $this->getRequest()->getParam('id');

        if ($id === null) {
            $this->messageManager->addErrorMessage(__('We can\'t find a report to convert.'));
            return $resultRedirect;
        }

        try {
            $entity = $this->reportRepository->getById((int)$id);
            $newWhitelist = $this->cspReportConverter->convert($entity);

            $result = $this->whitelistManager->processNewWhitelist($newWhitelist, $entity);

            $this->cacheCleaner->cleanCaches();

            if ($result === WhitelistManagerInterface::RESULT_EXISTS) {
                $this->messageManager->addSuccessMessage(__('Whitelist already exists.'));
                return $this->createRedirectResult('hryvinskyi_csp/whitelist/index');
            }

            if ($result === WhitelistManagerInterface::RESULT_NOT_SAVED) {
                $this->messageManager->addWarningMessage(__('Whitelist not converted. Already exists.'));
                return $this->createRedirectResult('hryvinskyi_csp/whitelist/index');
            }

            $this->messageManager->addSuccessMessage(__('Report has been converted to Whitelist.'));
            return $this->createRedirectResult('hryvinskyi_csp/whitelist/index');

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect;
        }
    }
}