<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Report;

use Hryvinskyi\Csp\Api\ReportRepositoryInterface;
use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Hryvinskyi\Csp\Model\Report\Command\CspReportConverterInterface;
use Hryvinskyi\Csp\Model\ResourceModel\Report\CollectionFactory;
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
class MassConvert extends Action
{
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $entityCollectionFactory,
        private readonly ReportRepositoryInterface $entityRepository,
        private readonly WhitelistRepositoryInterface $whitelistRepository,
        private readonly CspReportConverterInterface $cspReportConverter,
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

        foreach ($collection as $entity) {
            $newWhitelist = $this->cspReportConverter->convert($entity);

            $whitelist = $this->whitelistRepository->getWhitelistByParams(
                (string)$newWhitelist->getPolicy(),
                (string)$newWhitelist->getValueType(),
                (string)$newWhitelist->getValue(),
                (string)$newWhitelist->getValueAlgorithm(),
            );

            if ($whitelist->getTotalCount() > 0) {
                $this->entityRepository->delete($entity);
                continue;
            }

            $this->whitelistRepository->save($newWhitelist);
            $this->entityRepository->delete($entity);
        }

        $this->cacheTypeCollection->clean();
        $this->cacheType->clean();

        $this->messageManager->addSuccessMessage(
            __('A total of %1 record(s) have been converted.', $collection->count())
        );

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('*/*/');
    }
}
