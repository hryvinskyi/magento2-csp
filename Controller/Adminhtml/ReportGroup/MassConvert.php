<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\ReportGroup;

use Hryvinskyi\Csp\Api\ReportRepositoryInterface;
use Hryvinskyi\Csp\Api\ReportGroupRepositoryInterface;
use Hryvinskyi\Csp\Model\Cache\CacheCleanerInterface;
use Hryvinskyi\Csp\Model\Report\Command\CspReportConverterInterface;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistManagerInterface;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\Component\MassAction\Filter;
use Hryvinskyi\Csp\Model\ResourceModel\Report\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Mass Convert Report Groups to Whitelist entries
 */
class MassConvert extends AbstractReportGroup implements HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param ReportRepositoryInterface $reportRepository
     * @param ReportGroupRepositoryInterface $reportGroupRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CspReportConverterInterface $cspReportConverter
     * @param CacheCleanerInterface $cacheCleaner
     * @param WhitelistManagerInterface $whitelistManager
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly ReportRepositoryInterface $reportRepository,
        private readonly ReportGroupRepositoryInterface $reportGroupRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly CspReportConverterInterface $cspReportConverter,
        private readonly CacheCleanerInterface $cacheCleaner,
        private readonly WhitelistManagerInterface $whitelistManager
    ) {
        parent::__construct($context);
    }

    /**
     * Execute mass action
     *
     * @return Redirect
     * @throws LocalizedException
     */
    public function execute(): Redirect
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $groupIds = $collection->getColumnValues('group_id');
        $convertedCount = 0;
        $existingCount = 0;
        $redundantCount = 0;
        $errorCount = 0;

        if (empty($groupIds)) {
            $this->messageManager->addErrorMessage(__('No report groups were selected to convert.'));
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('*/*/');
        }

        foreach ($groupIds as $groupId) {
            try {
                $groupId = (int)$groupId;
                $searchCriteria = $this->searchCriteriaBuilder
                    ->addFilter('group_id', $groupId)
                    ->create();
                $reports = $this->reportRepository->getList($searchCriteria)->getItems();

                if (empty($reports)) {
                    $errorCount++;
                    continue;
                }

                $report = current($reports);
                $newWhitelist = $this->cspReportConverter->convert($report);
                $result = $this->whitelistManager->processNewWhitelist($newWhitelist, $report);

                if ($result === WhitelistManagerInterface::RESULT_EXISTS) {
                    $existingCount++;
                    $this->deleteReportsAndGroup($groupId);
                } elseif ($result === WhitelistManagerInterface::RESULT_REDUNDANT) {
                    $redundantCount++;
                    $this->deleteReportsAndGroup($groupId);
                } elseif ($result === WhitelistManagerInterface::RESULT_NOT_SAVED) {
                    $errorCount++;
                    $this->deleteReportsAndGroup($groupId);
                } else {
                    $convertedCount++;
                    $this->deleteReportsAndGroup($groupId);
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->messageManager->addErrorMessage(
                    __('Error converting report group ID %1: %2', $groupId, $e->getMessage())
                );
            }
        }

        // Clean caches after all conversions
        $this->cacheCleaner->cleanCaches();

        // Add success message
        if ($convertedCount > 0) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 report group(s) have been converted to whitelist entries.', $convertedCount)
            );
        }

        if ($existingCount > 0) {
            $this->messageManager->addWarningMessage(
                __('%1 report group(s) were not converted because equivalent whitelist entries already exist.', $existingCount)
            );
        }

        if ($redundantCount > 0) {
            $this->messageManager->addWarningMessage(
                __('%1 report group(s) were not converted because they are covered by existing wildcard entries.', $redundantCount)
            );
        }

        if ($errorCount > 0) {
            $this->messageManager->addErrorMessage(
                __('Failed to convert %1 report group(s). Please check the error log for details.', $errorCount)
            );
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('hryvinskyi_csp/whitelist/index');
    }

    /**
     * Delete reports and report group after conversion
     *
     * @param int $groupId
     * @return void
     * @throws LocalizedException
     */
    private function deleteReportsAndGroup(int $groupId): void
    {
        // Get all reports for this group
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('group_id', $groupId)
            ->create();
        $reports = $this->reportRepository->getList($searchCriteria)->getItems();

        // Delete each report
        foreach ($reports as $report) {
            try {
                $this->reportRepository->delete($report);
            } catch (CouldNotDeleteException $e) {

            } catch (\Exception $e) {
                // Log the error but don't interrupt the conversion flow
                $this->messageManager->addErrorMessage(
                    __('Error cleaning up after conversion for report ID %1: %2', $report->getReportId(), $e->getMessage())
                );
            }
        }

        try {
            // Delete the report group
            $this->reportGroupRepository->deleteById((int)$groupId);
        } catch (CouldNotDeleteException|NoSuchEntityException $e) {

        } catch (\Exception $e) {
            // Log the error but don't interrupt the conversion flow
            $this->messageManager->addErrorMessage(
                __('Error cleaning up after conversion for group ID %1: %2', $groupId, $e->getMessage())
            );
        }
    }
}
