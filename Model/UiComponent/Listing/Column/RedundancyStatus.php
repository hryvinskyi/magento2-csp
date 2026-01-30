<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\UiComponent\Listing\Column;

use Hryvinskyi\Csp\Model\Config\Source\RedundancyStatusOptions;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Column renderer for redundancy status in CSP whitelist grid
 */
class RedundancyStatus extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array<string, mixed> $components
     * @param array<string, mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item[$this->getData('name')])) {
                    $status = (int)$item[$this->getData('name')];
                    $item[$this->getData('name')] = match ($status) {
                        RedundancyStatusOptions::DUPLICATE => $this->getDuplicateSvg(),
                        RedundancyStatusOptions::REDUNDANT => $this->getRedundantSvg(),
                        RedundancyStatusOptions::UNIQUE => $this->getUniqueSvg(),
                        default => $this->getNotApplicableSvg(),
                    };
                }
            }
        }

        return $dataSource;
    }

    /**
     * Get SVG for unique entry
     *
     * @return string
     */
    private function getUniqueSvg(): string
    {
        return '<div style="text-align: center"><span title="Unique" style="display:inline-block;line-height:0;">' .
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: #1da750;">' .
            '<path d="M12,2C6.48,2,2,6.48,2,12s4.48,10,10,10s10-4.48,10-10S17.52,2,12,2z M10,17l-5-5l1.41-1.41L10,14.17' .
            'l7.59-7.59L19,8L10,17z"/>' .
            '</svg></span></div>';
    }

    /**
     * Get SVG for duplicate entry (exact match exists)
     *
     * @return string
     */
    private function getDuplicateSvg(): string
    {
        return '<div style="text-align: center"><span title="Duplicate: Another entry with the same policy and value exists" style="display:inline-block;line-height:0;">' .
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: #e22626;">' .
            '<path d="M12,2C6.48,2,2,6.48,2,12s4.48,10,10,10s10-4.48,10-10S17.52,2,12,2z M19,13H5v-2h14V13z"/>' .
            '</svg></span></div>';
    }

    /**
     * Get SVG for redundant entry (covered by wildcard)
     *
     * @return string
     */
    private function getRedundantSvg(): string
    {
        return '<div style="text-align: center"><span title="Redundant: Covered by a wildcard entry" style="display:inline-block;line-height:0;">' .
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: #eb8c00;">' .
            '<path d="M12,2C6.48,2,2,6.48,2,12s4.48,10,10,10s10-4.48,10-10S17.52,2,12,2z M13,17h-2v-2h2V17z M13,13h-2V7h2V13z"/>' .
            '</svg></span></div>';
    }

    /**
     * Get SVG for not applicable (non-host types)
     *
     * @return string
     */
    private function getNotApplicableSvg(): string
    {
        return '<div style="text-align: center"><span title="Not Applicable" style="display:inline-block;line-height:0;">' .
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: #757575;">' .
            '<path d="M12,2C6.48,2,2,6.48,2,12s4.48,10,10,10s10-4.48,10-10S17.52,2,12,2z M12,20c-4.42,0-8-3.58-8-8' .
            'c0-4.42,3.58-8,8-8c4.42,0,8,3.58,8,8C20,16.42,16.42,20,12,20z M7,11h10v2H7V11z"/>' .
            '</svg></span></div>';
    }
}
