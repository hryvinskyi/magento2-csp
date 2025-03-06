<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Model\Report\Command\CspReportConverterInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\Exception\CouldNotSaveException;

interface MassConvertManagerInterface
{
    /**
     * Convert reports to whitelists
     *
     * @param Collection $collection
     * @param CspReportConverterInterface $cspReportConverter
     * @return int Number of converted reports
     */
    public function convertReports(
        Collection $collection,
        CspReportConverterInterface $cspReportConverter,
    ): int;
}