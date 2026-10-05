<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\UiComponent\Listing\Column;

use Hryvinskyi\Csp\Api\Data\Status;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Actions of a report group row: its reports, conversion, status changes and deletion; every change is a POST.
 */
class ReportGroupActions extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array<mixed> $components
     * @param array<mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
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
            if (!is_array($item) || !is_numeric($item['group_id'] ?? null)) {
                continue;
            }
            $id = (int)$item['group_id'];
            $item[$name] = [
                'view' => [
                    'href' => $this->urlBuilder->getUrl('hryvinskyi_csp/report/index', ['group_id' => $id]),
                    'label' => __('View reports'),
                ],
                'convert' => [
                    'href' => $this->urlBuilder->getUrl('hryvinskyi_csp/reportgroup/convertToWhitelist', ['id' => $id]),
                    'label' => __('Convert to Whitelist'),
                    'post' => true,
                ],
                'skip' => [
                    'href' => $this->urlBuilder->getUrl(
                        'hryvinskyi_csp/reportgroup/changeStatus',
                        ['id' => $id, 'status' => Status::SKIP->value]
                    ),
                    'label' => __('Skip'),
                    'post' => true,
                ],
                'deny' => [
                    'href' => $this->urlBuilder->getUrl(
                        'hryvinskyi_csp/reportgroup/changeStatus',
                        ['id' => $id, 'status' => Status::DENIED->value]
                    ),
                    'label' => __('Deny'),
                    'post' => true,
                ],
                'delete' => [
                    'href' => $this->urlBuilder->getUrl('hryvinskyi_csp/reportgroup/delete', ['id' => $id]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete this report group?'),
                        'message' => __('Are you sure you want to delete this report group and all its reports?'),
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
