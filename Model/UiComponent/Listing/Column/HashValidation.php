<?php
/**
 * Copyright (c) 2025. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\UiComponent\Listing\Column;

use Hryvinskyi\Csp\Api\CspHashGeneratorInterface;
use Hryvinskyi\Csp\Model\Config\Source\HashValidationOptions;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class HashValidation extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly CspHashGeneratorInterface $cspHashGenerator,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$this->getData('name')])) {
                    $status = (int)$item[$this->getData('name')];
                    if ($status === HashValidationOptions::INVALID) {
                        $item[$this->getData('name')] = $this->getInvalidSvg();
                    } elseif ($status === HashValidationOptions::VALID) {
                        $item[$this->getData('name')] = $this->getValidSvg();
                    } elseif ($status === HashValidationOptions::NOT_VERIFIED) {
                        $item[$this->getData('name')] = $this->getNotVerifiedSvg();
                    } elseif ($status === HashValidationOptions::NOT_APPLICABLE) {
                        $item[$this->getData('name')] = $this->getNotApplicableSvg();
                    }
                }
            }
        }

        return $dataSource;
    }

    /**
     * Get SVG for valid hash
     *
     * @return string
     */
    private function getValidSvg(): string
    {
        return '<div style="text-align: center"><span title="Valid" style="display:inline-block;line-height:0;">' .
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: #1da750;">' .
            '<path d="M12,2C6.48,2,2,6.48,2,12s4.48,10,10,10s10-4.48,10-10S17.52,2,12,2z M10,17l-5-5l1.41-1.41L10,14.17' .
            'l7.59-7.59L19,8L10,17z"/>' .
            '</svg></span></div>';
    }

    /**
     * Get SVG for invalid hash
     *
     * @return string
     */
    private function getInvalidSvg(): string
    {
        return '<div style="text-align: center"><span title="Invalid" style="display:inline-block;line-height:0;">' .
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: #e22626;">' .
            '<path d="M12,2C6.47,2,2,6.47,2,12s4.47,10,10,10s10-4.47,10-10S17.53,2,12,2z M17,15.59L15.59,17L12,13.41L8.41,17' .
            'L7,15.59L10.59,12L7,8.41L8.41,7L12,10.59L15.59,7L17,8.41L13.41,12L17,15.59z"/>' .
            '</svg></span></div>';
    }

    /**
     * Get SVG for not verified hash
     *
     * @return string
     */
    private function getNotVerifiedSvg(): string
    {
        return '<div style="text-align: center"><span title="Not Verified" style="display:inline-block;line-height:0;">' .
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="fill: #eb8c00;">' .
            '<path d="M12,2C6.48,2,2,6.48,2,12s4.48,10,10,10s10-4.48,10-10S17.52,2,12,2z M13,17h-2v-2h2V17z M13,13h-2V7h2V13z"/>' .
            '</svg></span></div>';
    }

    /**
     * Get SVG for not applicable
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