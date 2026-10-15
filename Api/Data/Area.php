<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Data;

/**
 * Application area a whitelist entry applies to, or a report group was recorded in.
 *
 * `All` scopes an entry to both areas; on a report group it marks one recorded before 2.0.0, whose area is unknown.
 *
 * @api
 */
enum Area: string
{
    case ALL = 'all';
    case FRONTEND = 'frontend';
    case ADMINHTML = 'adminhtml';
}
