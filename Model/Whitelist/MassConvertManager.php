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
use Magento\Framework\Exception\LocalizedException;

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
    public function convertReports(Collection $collection, CspReportConverterInterface $cspReportConverter): array
    {
        $count = $collection->count();
        $errorMessages = [];

        foreach ($collection as $entity) {
            try {
                $newWhitelist = $cspReportConverter->convert($entity);
            } catch (LocalizedException $e) {
                $errorMessages[] = $e->getMessage();
                $count--;
                continue;
            }

            $whitelist = $this->whitelistRepository->getWhitelistByParams(
                (string)$newWhitelist->getPolicy(),
                (string)$newWhitelist->getValueType(),
                (string)$newWhitelist->getValue(),
                (string)$newWhitelist->getValueAlgorithm(),
            );

            if ($whitelist->getTotalCount() > 0) {
                $this->reportRepository->delete($entity);
                $count--;
                continue;
            }

            try {
                $this->whitelistRepository->save($newWhitelist);
            } catch (CouldNotSaveException $e) {
                $errorMessages[] = $e->getMessage();
            }

            $this->reportRepository->deleteByDomainAndPolicy(
                $newWhitelist->getValue(),
                $entity->getEffectiveDirective()
            );
            $this->reportRepository->deleteByDomainAndPolicy($newWhitelist->getValue(), $newWhitelist->getPolicy());
        }

        return [
            'count' => $count,
            'messages' => implode(', ', $errorMessages),
        ];
    }
}