<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

namespace Hryvinskyi\Csp\Model\Whitelist;

use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class FormDataProvider extends AbstractDataProvider
{
    private array $loadedData = [];

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        private readonly CollectionFactory $collectionFactory,
        private readonly DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $this->collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        if ($this->loadedData !== []) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();

        foreach ($items as $item) {
            $data = $item->getData();
            if (isset($data[WhitelistInterface::STORE_IDS]) && is_string($data[WhitelistInterface::STORE_IDS])) {
                $data[WhitelistInterface::STORE_IDS] = explode(',', $data[WhitelistInterface::STORE_IDS]);
            }
            $this->loadedData[$item->getId()] = $data;
        }

        $data = $this->dataPersistor->get('hryvinskyi_csp_whitelist');
        if (isset($data[WhitelistInterface::STORE_IDS]) && is_string($data[WhitelistInterface::STORE_IDS])) {
            $data[WhitelistInterface::STORE_IDS] = explode(',', $data[WhitelistInterface::STORE_IDS]);
        }
        if (!empty($data)) {
            $item = $this->collection->getNewEmptyItem();
            $item->setData($data);
            $this->loadedData[$item->getId()] = $item->getData();
            $this->dataPersistor->clear('hryvinskyi_csp_whitelist');
        }

        return $this->loadedData;
    }
}
