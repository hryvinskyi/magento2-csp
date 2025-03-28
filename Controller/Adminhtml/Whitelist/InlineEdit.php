<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Whitelist;

use Hryvinskyi\Csp\Api\Data\WhitelistInterface;
use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Cache\Type\Collection;
use Magento\PageCache\Model\Cache\Type;

/**
 * @method \Magento\Framework\App\Request\Http getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 */
class InlineEdit extends Action
{
    public function __construct(
        Action\Context $context,
        private readonly WhitelistRepositoryInterface $entityRepository,
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
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        $error = false;
        $messages = [];

        $items = $this->getRequest()->getParam('items', []);
        if (!(count($items) && $this->getRequest()->getParam('isAjax'))) {
            return $resultJson->setData([
                'messages' => [__('Please correct the data sent.')],
                'error' => true,
            ]);
        }

        foreach (array_keys($items) as $itemId) {
            try {
                $entity = $this->entityRepository->getById($itemId);
                $this->dataObjectHelper->populateWithArray(
                    $entity,
                    $items[$itemId],
                    WhitelistInterface::class
                );
                $this->entityRepository->save($entity);
                $this->cacheTypeCollection->clean();
                $this->cacheType->clean();
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $messages[] = sprintf('[%s]: %s', $itemId, $e->getMessage());
            } catch (\Exception) {
                $messages[] = sprintf('[%s]: %s', $itemId, __('Something went wrong while saving the entity.'));
                $error = true;
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }
}
