<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Whitelist;

use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Whitelist\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Framework\App\Cache\Type\Collection;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\PageCache\Model\Cache\Type;
use Magento\Ui\Component\MassAction\Filter;

/**
 * @method \Magento\Framework\App\Request\Http getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 */
class MassDelete extends Action
{
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $entityCollectionFactory,
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
        $collection = $this->filter->getCollection($this->entityCollectionFactory->create());

        foreach ($collection as $item) {
            $this->entityRepository->delete($item);
        }

        $this->cacheTypeCollection->clean();
        $this->cacheType->clean();

        $this->messageManager->addSuccessMessage(
            __('A total of %1 record(s) have been deleted.', $collection->count())
        );

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('*/*/');
    }
}
