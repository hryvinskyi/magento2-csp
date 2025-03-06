<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Report;

use Magento\Framework\Controller\ResultInterface;

/**
 * Index controller for listing reports
 */
class Index extends AbstractReport
{
    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        return $this->createPageResult();
    }
}
