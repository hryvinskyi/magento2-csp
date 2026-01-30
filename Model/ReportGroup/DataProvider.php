<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ReportGroup;

use Hryvinskyi\Csp\Model\AbstractFormDataProvider;
use Hryvinskyi\Csp\Model\ResourceModel\ReportGroup\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;

class DataProvider extends AbstractFormDataProvider
{
    private const PERSISTOR_KEY = 'hryvinskyi_csp_reportgroup';

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $collectionFactory->create(),
            $dataPersistor,
            self::PERSISTOR_KEY,
            $meta,
            $data
        );
    }
}
