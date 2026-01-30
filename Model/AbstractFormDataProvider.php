<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Abstract form data provider for CSP entities.
 */
abstract class AbstractFormDataProvider extends AbstractDataProvider
{
    private array $loadedData = [];

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        AbstractCollection $collection,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly string $persistorKey,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collection;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     */
    public function getData(): array
    {
        if ($this->loadedData !== []) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();

        foreach ($items as $item) {
            $this->loadedData[$item->getId()] = $this->processItemData($item->getData());
        }

        $data = $this->dataPersistor->get($this->persistorKey);
        if (!empty($data)) {
            $item = $this->collection->getNewEmptyItem();
            $item->setData($data);
            $this->loadedData[$item->getId()] = $this->processItemData($item->getData());
            $this->dataPersistor->clear($this->persistorKey);
        }

        return $this->loadedData;
    }

    /**
     * Process item data before returning. Override in child classes for custom transformations.
     *
     * @param array $data
     * @return array
     */
    protected function processItemData(array $data): array
    {
        return $data;
    }
}
