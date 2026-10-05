<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Report;

use Hryvinskyi\Csp\Model\ResourceModel\Report\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Data of the read-only report view.
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array<mixed> $meta
     * @param array<mixed> $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Report data keyed by report id.
     *
     * @return array<int|string, array<mixed>>
     */
    public function getData()
    {
        $data = [];
        foreach ($this->collection->getItems() as $id => $item) {
            $data[$id] = (array)$item->getData();
        }

        return $data;
    }
}
