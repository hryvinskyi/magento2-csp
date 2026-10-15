<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Policy;

/**
 * Scheme of the page the policy is sent with; host sources without a scheme match relative to it.
 */
interface DocumentSchemeInterface
{
    /**
     * Whether the page is served over HTTPS.
     *
     * @return bool
     */
    public function isSecure(): bool;
}
