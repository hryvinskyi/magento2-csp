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
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Cache\Type\Collection;
use Magento\PageCache\Model\Cache\Type;

/**
 * @method \Magento\Framework\App\Request\Http getRequest()
 * @method \Magento\Framework\App\Response\Http getResponse()
 */
class ConvertToWhitelist extends Action
{
    public function __construct(
        Context $context,
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
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('id');
        if ($id === null) {
            $this->messageManager->addErrorMessage(__('We can\'t find an report to delete.'));

            return $resultRedirect->setPath('*/*/');
        }
        try {
            $entity = $this->entityRepository->getById((int)$id);
            $whitelist = $this->cspReportConverter->convert($entity);
            $this->whitelistRepository->save($whitelist);
            $this->entityRepository->delete($entity);
            $this->cacheTypeCollection->clean();
            $this->cacheType->clean();

            return $resultRedirect->setPath('hryvinskyi_csp/whitelist/index');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return $resultRedirect->setPath('*/*/');
        }
    }
}
