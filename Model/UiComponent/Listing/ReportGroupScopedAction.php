<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\UiComponent\Listing;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Action;

/**
 * Mass action of the report grid whose URL names the report group the grid shows.
 *
 * Configure it with `urlPath` (a route path) instead of `url`; the URL is built for each render.
 */
class ReportGroupScopedAction extends Action
{
    /**
     * @param ContextInterface $context
     * @param ReportGroupScope $scope
     * @param array<mixed> $components
     * @param array<mixed> $data
     * @param array<mixed>|\JsonSerializable|null $actions
     */
    public function __construct(
        ContextInterface $context,
        private readonly ReportGroupScope $scope,
        array $components = [],
        array $data = [],
        $actions = null
    ) {
        parent::__construct($context, $components, $data, $actions);
    }

    /**
     * Build the action URL with the scope of the grid page.
     *
     * @return void
     */
    public function prepare()
    {
        $config = $this->getConfiguration();
        $path = $config['urlPath'] ?? null;
        if (is_string($path) && $path !== '') {
            $context = $this->getContext();
            $config['url'] = $context->getUrl($path, [
                ReportGroupScope::PARAM => $this->scope->urlValue(
                    $context->getRequestParam(ReportGroupScope::PAGE_PARAM)
                ),
            ]);
            $this->setData('config', $config);
        }

        parent::prepare();
    }
}
