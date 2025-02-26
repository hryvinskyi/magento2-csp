<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

interface ConfigInterface
{
    /**
     * Is enabled rules
     *
     * @param $store
     * @return bool
     */
    public function isRulesEnabled($store = null): bool;

    /**
     * Is enabled report
     *
     * @param $store
     * @return bool
     */
    public function isReportsEnabled($store = null): bool;

    /**
     * Is enabled restrict mode for adminhtml
     *
     * @param $store
     * @return bool
     */
    public function isEnabledRestrictModeAdminhtml($store = null): bool;

    /**
     * Is enabled restrict mode for frontend
     *
     * @param $store
     * @return bool
     */
    public function isEnabledRestrictModeFrontend($store = null): bool;

    /**
     * Is add all storefront urls
     *
     * @return bool
     */
    public function isAddAllStorefrontUrls(): bool;
}