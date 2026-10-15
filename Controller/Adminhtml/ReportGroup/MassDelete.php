<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\ReportGroup;

use Hryvinskyi\Csp\Api\Data\ReportGroupInterface;
use Hryvinskyi\Csp\Api\ReportGroupRepositoryInterface;
use Hryvinskyi\Csp\Model\Admin\EscapedMessages;
use Hryvinskyi\Csp\Model\ResourceModel\ReportGroup\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;

/**
 * Deletes the report groups selected in the grid with their reports.
 */
class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_Csp::reports_manage';

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param ReportGroupRepositoryInterface $reportGroupRepository
     * @param EscapedMessages $messages
     */
    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly ReportGroupRepositoryInterface $reportGroupRepository,
        private readonly EscapedMessages $messages
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $deleted = 0;
        try {
            foreach ($this->filter->getCollection($this->collectionFactory->create()) as $group) {
                if ($group instanceof ReportGroupInterface) {
                    $this->reportGroupRepository->delete($group);
                    $deleted++;
                }
            }
            $this->messages->success(__('A total of %1 record(s) have been deleted.', $deleted));
        } catch (LocalizedException $exception) {
            $this->messages->failure($exception);
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
