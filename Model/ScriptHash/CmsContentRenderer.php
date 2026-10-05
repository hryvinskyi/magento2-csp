<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\ScriptHash;

use Magento\Email\Model\Template\Filter as StoreAwareFilter;
use Magento\Framework\Filter\Template;

/**
 * Renders CMS content with a CMS template filter for a store view, as the storefront shows it.
 *
 * Must run inside a frontend store emulation. When the filter fails the raw content is returned with a warning.
 */
class CmsContentRenderer
{
    /**
     * Rendered content and a warning when the raw content had to be used.
     *
     * @param Template $filter
     * @param string $content
     * @param int $storeId
     * @return array{html: string, warning: string|null}
     */
    public function render(Template $filter, string $content, int $storeId): array
    {
        try {
            if ($filter instanceof StoreAwareFilter) {
                $filter->setStoreId($storeId);
            }

            return ['html' => (string)$filter->filter($content), 'warning' => null];
        } catch (\Exception $exception) {
            return ['html' => $content, 'warning' => sprintf('rendering failed, raw content used: %s', $exception->getMessage())];
        }
    }
}
