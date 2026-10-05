<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Data of the whitelist entry form, store ids included; data left from a failed save takes precedence.
 */
class FormDataProvider extends AbstractDataProvider
{
    private const PERSISTOR_KEY = 'hryvinskyi_csp_whitelist';

    /**
     * @var array<int|string, array<mixed>>|null
     */
    private ?array $loadedData = null;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array<mixed> $meta
     * @param array<mixed> $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Form data keyed by entry id.
     *
     * @return array<int|string, array<mixed>>
     */
    public function getData()
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }
        $this->loadedData = [];
        foreach ($this->collection->getItems() as $id => $item) {
            $this->loadedData[$id] = (array)$item->getData();
        }

        $persisted = $this->dataPersistor->get(self::PERSISTOR_KEY);
        if (is_array($persisted) && $persisted !== []) {
            $id = $persisted['rule_id'] ?? null;
            $this->loadedData[is_int($id) || is_string($id) ? $id : ''] = $persisted;
            $this->dataPersistor->clear(self::PERSISTOR_KEY);
        }

        return $this->loadedData;
    }
}
