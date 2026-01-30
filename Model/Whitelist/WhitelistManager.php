<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Api\Data\ReportInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Api\DomainMatcherInterface;
use Hryvinskyi\Csp\Api\ReportGroupRepositoryInterface;
use Hryvinskyi\Csp\Api\ReportRepositoryInterface;
use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class WhitelistManager implements WhitelistManagerInterface
{
    /**
     * @param WhitelistRepositoryInterface $whitelistRepository
     * @param ReportRepositoryInterface $reportRepository
     * @param ReportGroupRepositoryInterface $reportGroupRepository
     * @param DomainMatcherInterface $domainMatcher
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly WhitelistRepositoryInterface $whitelistRepository,
        private readonly ReportRepositoryInterface $reportRepository,
        private readonly ReportGroupRepositoryInterface $reportGroupRepository,
        private readonly DomainMatcherInterface $domainMatcher,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     * @throws CouldNotDeleteException
     * @throws LocalizedException
     */
    public function processNewWhitelist(WhitelistInterface $whitelist, ReportInterface $report): int
    {
        // Check for exact match first
        $existingWhitelist = $this->whitelistRepository->getWhitelistByParams(
            (string)$whitelist->getPolicy(),
            (string)$whitelist->getValueType(),
            (string)$whitelist->getValue(),
            (string)$whitelist->getValueAlgorithm(),
        );

        if ($existingWhitelist->getTotalCount() > 0) {
            $this->deleteRelatedReports($whitelist, $report);
            return self::RESULT_EXISTS;
        }

        // Check if this entry is redundant (covered by existing wildcard)
        if ($this->isValueCoveredByExistingWildcard($whitelist)) {
            $this->deleteRelatedReports($whitelist, $report);
            return self::RESULT_REDUNDANT;
        }

        try {
            $this->whitelistRepository->save($whitelist);
        } catch (CouldNotSaveException) {
            $this->deleteRelatedReports($whitelist, $report);
            return self::RESULT_NOT_SAVED;
        }

        // If we just added a wildcard, remove entries that are now redundant
        if ($this->domainMatcher->isWildcard($whitelist->getValue())) {
            $this->removeRedundantEntriesForWildcard($whitelist);
        }

        $this->deleteRelatedReports($whitelist, $report);

        return self::RESULT_SUCCESS;
    }

    /**
     * Check if the whitelist value is already covered by an existing wildcard.
     *
     * @param WhitelistInterface $whitelist
     * @return bool
     * @throws LocalizedException
     */
    private function isValueCoveredByExistingWildcard(WhitelistInterface $whitelist): bool
    {
        $value = $whitelist->getValue();
        $policy = $whitelist->getPolicy();

        // Don't check wildcards against other wildcards here - that's handled separately
        if ($this->domainMatcher->isWildcard($value)) {
            return $this->isWildcardCoveredByExistingBroaderWildcard($whitelist);
        }

        // Get all wildcard entries for this policy
        $wildcards = $this->getWildcardsForPolicy($policy);

        foreach ($wildcards as $wildcardEntry) {
            $wildcardValue = $wildcardEntry->getValue();
            if ($this->domainMatcher->domainMatchesWildcard($value, $wildcardValue)) {
                $this->logger->debug(sprintf(
                    'CSP WhitelistManager: Skipping redundant entry "%s" - covered by wildcard "%s"',
                    $value,
                    $wildcardValue
                ));
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a wildcard is covered by an existing broader wildcard.
     *
     * @param WhitelistInterface $whitelist
     * @return bool
     * @throws LocalizedException
     */
    private function isWildcardCoveredByExistingBroaderWildcard(WhitelistInterface $whitelist): bool
    {
        $value = $whitelist->getValue();
        $policy = $whitelist->getPolicy();

        $wildcards = $this->getWildcardsForPolicy($policy);

        foreach ($wildcards as $wildcardEntry) {
            $existingWildcard = $wildcardEntry->getValue();
            if ($existingWildcard === $value) {
                continue;
            }
            if ($this->domainMatcher->isWildcardCoveredByBroader($value, $existingWildcard)) {
                $this->logger->debug(sprintf(
                    'CSP WhitelistManager: Skipping redundant wildcard "%s" - covered by broader wildcard "%s"',
                    $value,
                    $existingWildcard
                ));
                return true;
            }
        }

        return false;
    }

    /**
     * Get all wildcard whitelist entries for a specific policy.
     *
     * @param string $policy
     * @return WhitelistInterface[]
     * @throws LocalizedException
     */
    private function getWildcardsForPolicy(string $policy): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('policy', $policy)
            ->addFilter('value', '*.%', 'like')
            ->addFilter('status', 1)
            ->create();

        $result = $this->whitelistRepository->getList($searchCriteria);
        $wildcards = [];

        foreach ($result->getItems() as $item) {
            if ($this->domainMatcher->isWildcard($item->getValue())) {
                $wildcards[] = $item;
            }
        }

        return $wildcards;
    }

    /**
     * Remove existing whitelist entries that are now redundant due to a new wildcard.
     *
     * @param WhitelistInterface $newWildcard
     * @return void
     * @throws LocalizedException
     */
    private function removeRedundantEntriesForWildcard(WhitelistInterface $newWildcard): void
    {
        $wildcardValue = $newWildcard->getValue();
        $policy = $newWildcard->getPolicy();

        // Get all entries for this policy (excluding the new wildcard itself)
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('policy', $policy)
            ->addFilter('status', 1)
            ->addFilter('rule_id', $newWildcard->getRuleId(), 'neq')
            ->create();

        $result = $this->whitelistRepository->getList($searchCriteria);
        $deletedCount = 0;

        foreach ($result->getItems() as $entry) {
            $entryValue = $entry->getValue();

            // Check if this entry is now covered by the new wildcard
            $isCovered = false;

            if ($this->domainMatcher->isWildcard($entryValue)) {
                // Check if existing wildcard is covered by the new broader wildcard
                $isCovered = $this->domainMatcher->isWildcardCoveredByBroader($entryValue, $wildcardValue);
            } else {
                // Check if domain is covered by the new wildcard
                $isCovered = $this->domainMatcher->domainMatchesWildcard($entryValue, $wildcardValue);
            }

            if ($isCovered) {
                try {
                    $this->whitelistRepository->delete($entry);
                    $deletedCount++;
                    $this->logger->debug(sprintf(
                        'CSP WhitelistManager: Removed redundant entry "%s" - now covered by wildcard "%s"',
                        $entryValue,
                        $wildcardValue
                    ));
                } catch (\Exception $e) {
                    $this->logger->error(sprintf(
                        'CSP WhitelistManager: Failed to delete redundant entry "%s": %s',
                        $entryValue,
                        $e->getMessage()
                    ));
                }
            }
        }

        if ($deletedCount > 0) {
            $this->logger->info(sprintf(
                'CSP WhitelistManager: Removed %d redundant entries after adding wildcard "%s"',
                $deletedCount,
                $wildcardValue
            ));
        }
    }

    /**
     * Delete all reports and report groups matching the whitelist value and policy.
     *
     * @param WhitelistInterface $whitelist
     * @param ReportInterface $report
     * @return void
     * @throws CouldNotDeleteException
     */
    private function deleteRelatedReports(WhitelistInterface $whitelist, ReportInterface $report): void
    {
        $this->reportRepository->deleteByDomainAndPolicy($whitelist->getValue(), $report->getEffectiveDirective());
        $this->reportRepository->deleteByDomainAndPolicy($whitelist->getValue(), $whitelist->getPolicy());
        $this->reportGroupRepository->deleteByValueAndPolicy($whitelist->getValue(), $report->getEffectiveDirective());
        $this->reportGroupRepository->deleteByValueAndPolicy($whitelist->getValue(), $whitelist->getPolicy());
    }
}
