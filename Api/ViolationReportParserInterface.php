<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

use Hryvinskyi\Csp\Api\Data\ViolationInterface;

/**
 * Reads the violations out of a report body of one media type.
 *
 * @api
 */
interface ViolationReportParserInterface
{
    /**
     * Whether the parser reads bodies of the media type (lower-case, without parameters).
     *
     * @param string $mediaType
     * @return bool
     */
    public function supports(string $mediaType): bool;

    /**
     * Violations in the body; entries without a blocked resource or a directive are left out.
     *
     * @param string $body
     * @return list<ViolationInterface>
     * @throws \InvalidArgumentException When the body is not a report of this format
     */
    public function parse(string $body): array;
}
