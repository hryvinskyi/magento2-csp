<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\UiComponent\Listing;

use Hryvinskyi\Csp\Controller\Adminhtml\Whitelist\Import;
use Hryvinskyi\Csp\Model\Whitelist\WhitelistableDirectives;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Container;

/**
 * Whitelist grid import button; hands the browser component the upload URL, the form key and the import rules.
 */
class ImportButton extends Container
{
    /**
     * @param ContextInterface $context
     * @param UrlInterface $urlBuilder
     * @param FormKey $formKey
     * @param WhitelistableDirectives $directives
     * @param AuthorizationInterface $authorization
     * @param array<\Magento\Framework\View\Element\UiComponentInterface> $components
     * @param array<mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        private readonly UrlInterface $urlBuilder,
        private readonly FormKey $formKey,
        private readonly WhitelistableDirectives $directives,
        private readonly AuthorizationInterface $authorization,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
    }

    /**
     * Add the import settings to the component configuration; hide it without the import permission.
     *
     * @return void
     */
    public function prepare()
    {
        $config = $this->getData('config');
        $this->setData('config', [
            ...(is_array($config) ? $config : []),
            'url' => $this->urlBuilder->getUrl('hryvinskyi_csp/whitelist/import'),
            'formKey' => $this->formKey->getFormKey(),
            'directives' => $this->directives->all(),
            'maxFileMegabytes' => Import::MAX_FILE_BYTES / 1048576,
            'visible' => $this->authorization->isAllowed(Import::ADMIN_RESOURCE),
        ]);
        parent::prepare();
    }
}
