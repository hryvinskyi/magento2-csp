<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Whitelist;

use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Form for a whitelist entry; redirects to the grid when the entry does not exist.
 */
class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_Csp::whitelist';

    /**
     * @param Context $context
     * @param WhitelistRepositoryInterface $entityRepository
     */
    public function __construct(
        Context $context,
        private readonly WhitelistRepositoryInterface $entityRepository
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if (is_numeric($id) && $this->entityRepository->findById((int)$id) === null) {
            $this->messageManager->addErrorMessage((string)__('Whitelist entry %1 does not exist.', (int)$id));

            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        return $this->resultFactory->create(ResultFactory::TYPE_PAGE);
    }
}
