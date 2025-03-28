<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Api\ReportRepositoryInterface;
use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Hryvinskyi\Csp\Model\Report\Command\CspReportConverterInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\CouldNotSaveException;

class MassConvertManager implements MassConvertManagerInterface
{
    public function __construct(
        private readonly ReportRepositoryInterface $reportRepository,
        private readonly WhitelistRepositoryInterface $whitelistRepository
    ) {
    }

    /**
     * @inheritDoc
     */
    public function convertReports(Collection $collection, CspReportConverterInterface $cspReportConverter): int
    {
        $count = $collection->count();

        foreach ($collection as $entity) {
            $newWhitelist = $cspReportConverter->convert($entity);

            $whitelist = $this->whitelistRepository->getWhitelistByParams(
                (string)$newWhitelist->getPolicy(),
                (string)$newWhitelist->getValueType(),
                (string)$newWhitelist->getValue(),
                (string)$newWhitelist->getValueAlgorithm(),
            );

            if ($whitelist->getTotalCount() > 0) {
                $this->reportRepository->delete($entity);
                continue;
            }

            try {
                $this->whitelistRepository->save($newWhitelist);
            } catch (CouldNotSaveException) {
                // The whitelist could not be saved
            }

            $this->reportRepository->deleteByDomainAndPolicy(
                $newWhitelist->getValue(),
                $entity->getEffectiveDirective()
            );
            $this->reportRepository->deleteByDomainAndPolicy($newWhitelist->getValue(), $newWhitelist->getPolicy());
        }

        return $count;
    }
}