<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Whitelist;

use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Hryvinskyi\Csp\Model\Whitelist\EntryDataMapper;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Saves whitelist entries edited in the grid.
 */
class InlineEdit extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_Csp::whitelist_save';

    /**
     * @param Context $context
     * @param WhitelistRepositoryInterface $entityRepository
     * @param EntryDataMapper $entryDataMapper
     */
    public function __construct(
        Context $context,
        private readonly WhitelistRepositoryInterface $entityRepository,
        private readonly EntryDataMapper $entryDataMapper
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $items = $this->getRequest()->getParam('items', []);
        if (!is_array($items) || $items === [] || !$this->getRequest()->getParam('isAjax')) {
            return $this->respond($resultJson, [(string)__('Please correct the data sent.')], true);
        }

        $messages = [];
        foreach ($items as $id => $data) {
            if (!is_numeric($id) || !is_array($data)) {
                continue;
            }
            try {
                $entity = $this->entityRepository->getById((int)$id);
                $this->entryDataMapper->apply($entity, $data);
                $this->entityRepository->save($entity);
            } catch (LocalizedException $exception) {
                $messages[] = sprintf('[%s]: %s', $id, $exception->getMessage());
            }
        }

        return $this->respond($resultJson, $messages, $messages !== []);
    }

    /**
     * @param ResultInterface $result
     * @param list<string> $messages
     * @param bool $error
     * @return ResultInterface
     */
    private function respond(ResultInterface $result, array $messages, bool $error): ResultInterface
    {
        if ($result instanceof Json) {
            $result->setData(['messages' => $messages, 'error' => $error]);
        }

        return $result;
    }
}
