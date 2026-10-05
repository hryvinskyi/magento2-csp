<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\UiComponent\Listing\Column;

use Hryvinskyi\Csp\Api\Data\Status;
use Hryvinskyi\Csp\Model\Config\Source\Status as StatusOptions;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Report group status as a severity badge; pending groups stand out.
 */
class ReportStatus extends Column
{
    use MapsRowValues;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StatusOptions $statusOptions
     * @param Escaper $escaper
     * @param array<mixed> $components
     * @param array<mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly StatusOptions $statusOptions,
        private readonly Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Replace the status code with its badge.
     *
     * @param array<mixed> $dataSource
     * @return array<mixed>
     */
    public function prepareDataSource(array $dataSource)
    {
        return $this->mapRowValues($dataSource, 'status', function (int $code): string {
            $status = Status::tryFrom($code) ?? Status::PENDING;
            $label = $this->escaper->escapeHtml((string)$this->statusOptions->label($status));

            return sprintf(
                '<span class="%s"><span>%s</span></span>',
                $this->severityClass($status),
                is_string($label) ? $label : ''
            );
        });
    }

    /**
     * Admin grid severity class of a status.
     *
     * @param Status $status
     * @return string
     */
    private function severityClass(Status $status): string
    {
        return match ($status) {
            Status::PENDING => 'grid-severity-critical',
            Status::DENIED => 'grid-severity-notice',
            Status::SKIP => 'grid-severity-minor',
        };
    }
}
