<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml;

/**
 * Converting reports writes whitelist entries, so it needs both permissions.
 */
trait ConvertsToWhitelist
{
    /**
     * Whether the admin may convert reports and save whitelist entries.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Hryvinskyi_Csp::reports_convert')
            && $this->_authorization->isAllowed('Hryvinskyi_Csp::whitelist_save');
    }
}
