<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Whitelist;

use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Cache\Type\Collection;
use Magento\PageCache\Model\Cache\Type;

/**
 * @method \Magento\Framework\App\Request\Http getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 */
class Delete extends Action
{
    public function __construct(
        Context $context,
        private readonly WhitelistRepositoryInterface $entityRepository,
        private readonly Collection $cacheTypeCollection,
        private readonly Type $cacheType
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('id');
        if ($id === null) {
            $this->messageManager->addErrorMessage(__('We can\'t find an entity to delete.'));

            return $resultRedirect->setPath('*/*/');
        }
        try {
            $this->entityRepository->deleteById((int)$id);
            $this->messageManager->addSuccessMessage(__('Entity has been deleted.'));

            $this->cacheTypeCollection->clean();
            $this->cacheType->clean();
            return $resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
        }
    }
}
