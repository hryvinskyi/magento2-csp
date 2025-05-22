<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Report;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session;
use Magento\Framework\Controller\ResultInterface;

/**
 * Index controller for listing reports
 */
class Index extends AbstractReport
{
    public function __construct(Context $context, private readonly Session $session)
    {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $groupId = $this->getRequest()->getParam('id');

        if ($groupId !== null) {
            $this->session->setGroupId($groupId);
        }

        return $this->createPageResult();
    }
}
