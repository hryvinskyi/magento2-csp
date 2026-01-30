<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\ReportGroup;

use Hryvinskyi\Csp\Api\ReportRepositoryInterface;
use Hryvinskyi\Csp\Controller\Adminhtml\Report\AbstractReport;
use Hryvinskyi\Csp\Model\Report\Command\CspReportConverterInterface;
use Hryvinskyi\Csp\Model\Cache\CacheCleanerInterface;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistManagerInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Controller\ResultInterface;

/**
 * Convert to whitelist controller for converting a report to whitelist
 */
class ConvertToWhitelist extends AbstractReport
{
    public function __construct(
        Context $context,
        private readonly ReportRepositoryInterface $reportRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
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
            $this->messageManager->addErrorMessage(__('We can\'t find a report group to convert.'));
            return $resultRedirect;
        }

        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('group_id', $id)
                ->create();
            $reports = $this->reportRepository->getList($searchCriteria)->getItems();

            if (empty($reports)) {
                $this->messageManager->addErrorMessage(__('No reports found for this group.'));
                return $resultRedirect;
            }

            $entity = current($reports);
            $newWhitelist = $this->cspReportConverter->convert($entity);

            $result = $this->whitelistManager->processNewWhitelist($newWhitelist, $entity);

            $this->cacheCleaner->cleanCaches();

            if ($result === WhitelistManagerInterface::RESULT_EXISTS) {
                $this->messageManager->addSuccessMessage(__('Whitelist already exists. Report removed.'));
                return $this->createRedirectResult('*/*/');
            }

            if ($result === WhitelistManagerInterface::RESULT_REDUNDANT) {
                $this->messageManager->addSuccessMessage(
                    __('Entry is covered by an existing wildcard. Report removed.')
                );
                return $this->createRedirectResult('*/*/');
            }

            if ($result === WhitelistManagerInterface::RESULT_NOT_SAVED) {
                $this->messageManager->addWarningMessage(__('Whitelist not converted due to save error.'));
                return $this->createRedirectResult('*/*/');
            }

            $this->messageManager->addSuccessMessage(__('Report group has been converted to Whitelist.'));
            return $this->createRedirectResult('*/*/');

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect;
        }
    }
}