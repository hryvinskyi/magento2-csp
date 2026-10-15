<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Whitelist;

use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Hryvinskyi\Csp\Model\Admin\EscapedMessages;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Deletes one whitelist entry.
 */
class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_Csp::whitelist_delete';

    /**
     * @param Context $context
     * @param WhitelistRepositoryInterface $entityRepository
     * @param EscapedMessages $messages
     */
    public function __construct(
        Context $context,
        private readonly WhitelistRepositoryInterface $entityRepository,
        private readonly EscapedMessages $messages
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('id');
        if (!is_numeric($id)) {
            $this->messages->error(__('Choose a whitelist entry to delete.'));

            return $resultRedirect->setPath('*/*/');
        }
        try {
            $this->entityRepository->deleteById((int)$id);
            $this->messages->success(__('The whitelist entry has been deleted.'));
        } catch (LocalizedException $exception) {
            $this->messages->failure($exception);
        }

        return $resultRedirect->setPath('*/*/');
    }
}
