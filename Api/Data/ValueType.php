<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Data;

/**
 * Kind of source a whitelist entry allows.
 *
 * @api
 */
enum ValueType: string
{
    /** A host or scheme source, such as `https://cdn.example.com` or `data:`. */
    case HOST = 'host';

    /** A digest of an inline script or style. */
    case HASH = 'hash';
}
