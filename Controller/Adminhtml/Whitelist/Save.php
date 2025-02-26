<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Whitelist;

use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Api\Data\WhitelistInterfaceFactory;
use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Cache\Type\Collection;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\PageCache\Model\Cache\Type;

/**
 * @method \Magento\Framework\App\Request\Http getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 */
class Save extends Action
{
    public function __construct(
        Context $context,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly WhitelistRepositoryInterface $entityRepository,
        private readonly WhitelistInterfaceFactory $entityFactory,
        private readonly DataObjectHelper $dataObjectHelper,
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
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $id = $this->getRequest()->getParam('id');

        try {
            if ($id === null) {
                $entity = $this->entityFactory->create();
            } else {
                $entity = $this->entityRepository->getById($id);
            }

            if (isset($data[WhitelistInterface::STORE_IDS]) && is_array($data[WhitelistInterface::STORE_IDS])) {
                $data[WhitelistInterface::STORE_IDS] = implode(',', $data[WhitelistInterface::STORE_IDS]);
            }

            $this->dataObjectHelper->populateWithArray($entity, $data, WhitelistInterface::class);
            $this->entityRepository->save($entity);
            $this->cacheTypeCollection->clean();
            $this->cacheType->clean();

            $this->messageManager->addSuccessMessage(__('You saved the entity.'));
            $this->dataPersistor->clear('hryvinskyi_csp_whitelist');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['id' => $entity->getId()]);
            }

            return $resultRedirect->setPath('*/*/');

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the entity.'));
        }

        $this->dataPersistor->set('hryvinskyi_csp_whitelist', $data);

        return $resultRedirect->setPath('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
    }
}
