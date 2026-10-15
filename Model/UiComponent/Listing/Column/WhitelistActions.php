<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\UiComponent\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Edit and delete links of a whitelist grid row; deleting is a POST behind a confirmation.
 */
class WhitelistActions extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param Escaper $escaper
     * @param array<mixed> $components
     * @param array<mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        private readonly Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Add the row actions.
     *
     * @param array<mixed> $dataSource
     * @return array<mixed>
     */
    public function prepareDataSource(array $dataSource)
    {
        $data = $dataSource['data'] ?? null;
        $items = is_array($data) ? ($data['items'] ?? null) : null;
        $name = $this->getData('name');
        if (!is_array($data) || !is_array($items) || !is_string($name)) {
            return $dataSource;
        }

        foreach ($items as $key => $item) {
            if (!is_array($item) || !is_numeric($item['rule_id'] ?? null)) {
                continue;
            }
            $id = (int)$item['rule_id'];
            $identifier = $this->escaper->escapeHtml(is_scalar($item['identifier'] ?? null) ? (string)$item['identifier'] : '');
            $item[$name] = [
                'edit' => [
                    'href' => $this->urlBuilder->getUrl('hryvinskyi_csp/whitelist/edit', ['id' => $id]),
                    'label' => __('Edit'),
                ],
                'delete' => [
                    'href' => $this->urlBuilder->getUrl('hryvinskyi_csp/whitelist/delete', ['id' => $id]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete "%1"', $identifier),
                        'message' => __('Are you sure you want to delete the "%1" entry?', $identifier),
                        '__disableTmpl' => true,
                    ],
                    'post' => true,
                ],
            ];
            $items[$key] = $item;
        }
        $data['items'] = $items;
        $dataSource['data'] = $data;

        return $dataSource;
    }
}
