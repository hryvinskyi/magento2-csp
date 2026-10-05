<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Whitelist;

use Hryvinskyi\Csp\Api\Data\WhitelistInterfaceFactory;
use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Hryvinskyi\Csp\Model\Whitelist\EntryDataMapper;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Exception\LocalizedException;

/**
 * Saves a whitelist entry from the form.
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_Csp::whitelist_save';
    private const PERSISTOR_KEY = 'hryvinskyi_csp_whitelist';

    /**
     * @param Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param WhitelistRepositoryInterface $entityRepository
     * @param WhitelistInterfaceFactory $entityFactory
     * @param EntryDataMapper $entryDataMapper
     */
    public function __construct(
        Context $context,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly WhitelistRepositoryInterface $entityRepository,
        private readonly WhitelistInterfaceFactory $entityFactory,
        private readonly EntryDataMapper $entryDataMapper
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $request = $this->getRequest();
        $data = $request instanceof HttpRequest ? $request->getPostValue() : null;
        if (!is_array($data) || $data === []) {
            return $resultRedirect->setPath('*/*/');
        }
        $id = $this->getRequest()->getParam('id', $data['rule_id'] ?? null);

        try {
            $entity = is_numeric($id) ? $this->entityRepository->getById((int)$id) : $this->entityFactory->create();
            $this->entryDataMapper->apply($entity, $data);
            $this->entityRepository->save($entity);
            $this->messageManager->addSuccessMessage((string)__('The whitelist entry has been saved.'));
            $this->dataPersistor->clear(self::PERSISTOR_KEY);

            return $this->getRequest()->getParam('back')
                ? $resultRedirect->setPath('*/*/edit', ['id' => $entity->getRuleId()])
                : $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        $this->dataPersistor->set(self::PERSISTOR_KEY, $data);

        return is_numeric($id)
            ? $resultRedirect->setPath('*/*/edit', ['id' => (int)$id])
            : $resultRedirect->setPath('*/*/new');
    }
}
