<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Api\Data\ReportInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Api\ReportGroupRepositoryInterface;
use Hryvinskyi\Csp\Api\ReportRepositoryInterface;
use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class WhitelistManager implements WhitelistManagerInterface
{
    public function __construct(
        private readonly WhitelistRepositoryInterface $whitelistRepository,
        private readonly ReportRepositoryInterface $reportRepository,
        private readonly ReportGroupRepositoryInterface $reportGroupRepository,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function processNewWhitelist(WhitelistInterface $whitelist, ReportInterface $report): int
    {
        $existingWhitelist = $this->whitelistRepository->getWhitelistByParams(
            (string)$whitelist->getPolicy(),
            (string)$whitelist->getValueType(),
            (string)$whitelist->getValue(),
            (string)$whitelist->getValueAlgorithm(),
        );

        if ($existingWhitelist->getTotalCount() > 0) {
            $this->reportRepository->delete($report);
            return self::RESULT_EXISTS;
        }

        try {
            $this->whitelistRepository->save($whitelist);
        } catch (CouldNotSaveException) {
            // Find and delete all reports with the same domain and policy
            $this->reportRepository->deleteByDomainAndPolicy($whitelist->getValue(), $report->getEffectiveDirective());
            $this->reportRepository->deleteByDomainAndPolicy($whitelist->getValue(), $whitelist->getPolicy());
            $this->reportGroupRepository->deleteByValueAndPolicy($whitelist->getValue(), $report->getEffectiveDirective());
            $this->reportGroupRepository->deleteByValueAndPolicy($whitelist->getValue(), $whitelist->getPolicy());
            return self::RESULT_NOT_SAVED;
        }

        // Find and delete all reports with the same domain and policy
        $this->reportRepository->deleteByDomainAndPolicy($whitelist->getValue(), $report->getEffectiveDirective());
        $this->reportRepository->deleteByDomainAndPolicy($whitelist->getValue(), $whitelist->getPolicy());
        $this->reportGroupRepository->deleteByValueAndPolicy($whitelist->getValue(), $report->getEffectiveDirective());
        $this->reportGroupRepository->deleteByValueAndPolicy($whitelist->getValue(), $whitelist->getPolicy());

        return self::RESULT_SUCCESS;
    }
}