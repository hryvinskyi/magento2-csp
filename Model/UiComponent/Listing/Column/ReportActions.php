<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\UiComponent\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Actions of a report row: details, conversion of its group and deletion.
 *
 * Every change is a POST behind a confirmation. The grid reads the post flag of an action with an href only in
 * its default handler, which a confirm attaches; a callback replaces that handler and never posts, and without
 * either the action is a plain link the browser opens with GET.
 */
class ReportActions extends Column
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
            if (!is_array($item) || !is_numeric($item['report_id'] ?? null)) {
                continue;
            }
            $id = (int)$item['report_id'];
            $item[$name] = [
                'view' => [
                    'href' => $this->urlBuilder->getUrl('hryvinskyi_csp/report/view', ['id' => $id]),
                    'label' => __('View'),
                ],
                'convert' => [
                    'href' => $this->urlBuilder->getUrl('hryvinskyi_csp/report/convertToWhitelist', ['id' => $id]),
                    'label' => __('Convert to Whitelist'),
                    'confirm' => [
                        'title' => __('Convert the group of report %1?', $id),
                        'message' => __(
                            'Convert the report group of report %1 to a whitelist entry? Inline code, eval and unsafe sources are refused. If it becomes allowed, the group is removed with all its reports.',
                            $id
                        ),
                        '__disableTmpl' => true,
                    ],
                    'post' => true,
                ],
                'delete' => [
                    'href' => $this->urlBuilder->getUrl('hryvinskyi_csp/report/delete', ['id' => $id]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete report %1?', $id),
                        'message' => __('Are you sure you want to delete report %1?', $id),
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
