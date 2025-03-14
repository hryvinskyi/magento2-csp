<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\UiComponent\Listing\Column;

use Hryvinskyi\Csp\Api\Data\Status;
use Magento\Ui\Component\Listing\Columns\Column;

class ReportStatus extends Column
{
    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }
        foreach ($dataSource['data']['items'] as &$item) {
            $item['raw_status'] = $item['status'];
            $item['status'] = $this->getLabel((int)$item['status']);
        }

        return $dataSource;
    }

    /**
     * Get label for status
     *
     * @param int $status
     * @return string
     */
    private function getLabel(int $status): string
    {
        switch (Status::from($status)) {
            case Status::DENIED:
                $class = 'grid-severity-notice';
                $text  = __('Denied');
                break;
            case Status::SKIP:
                $class = 'grid-severity-skip';
                $text  = __('Skip');
                break;
            case Status::PENDING:
                $class = 'grid-severity-critical';
                $text  = __('Pending');
                break;
            default:
                $class = 'grid-severity-critical';
                $text  = __('Unknown');
                break;
        }


        return '<span class="' . $class . '"><span>' . $text . '</span></span>';
    }
}