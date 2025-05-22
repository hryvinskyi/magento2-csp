<?php
/**
 * Copyright (c) 2025. MageCloud.  All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

namespace Hryvinskyi\Csp\Model\UiComponent\Listing\Column;

use Hryvinskyi\Csp\Api\Data\Status;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ReportGroupActions extends Column
{
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
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['group_id'])) {
                continue;
            }
            $item[$this->getData('name')] = [
                'view'   => [
                    'href'  => $this->urlBuilder->getUrl(
                        'hryvinskyi_csp/report/index',
                        [
                            'id' => $item['group_id']
                        ]
                    ),
                    'label' => __('View reports')
                ],
                'convert' => [
                    'href' => $this->urlBuilder->getUrl(
                        'hryvinskyi_csp/reportgroup/convertToWhitelist',
                        ['id' => $item['group_id']]
                    ),
                    'label' => __('Convert to Whitelist')
                ],
                'skip'   => [
                    'href'  => $this->urlBuilder->getUrl(
                        'hryvinskyi_csp/reportgroup/changeStatus',
                        [
                            'id' => $item['group_id'],
                            'status' => Status::SKIP->value
                        ]
                    ),
                    'label' => __('Change status to Skip'),
                ],
                'deny'   => [
                    'href'  => $this->urlBuilder->getUrl(
                        'hryvinskyi_csp/reportgroup/changeStatus',
                        [
                            'id' => $item['group_id'],
                            'status' => Status::DENIED->value
                        ]
                    ),
                    'label' => __('Change status to Deny'),
                ],
                'delete' => [
                    'href'    => $this->urlBuilder->getUrl(
                        'hryvinskyi_csp/reportgroup/delete',
                        [
                            'id' => $item['group_id']
                        ]
                    ),
                    'label'   => __('Delete'),
                    'confirm' => [
                        'title'   => __('Delete this report group?'),
                        'message' => __('Are you sure you wan\'t to delete this record? this will delete all reports in this group.'),
                    ]
                ]
            ];
        }

        return $dataSource;
    }
}
