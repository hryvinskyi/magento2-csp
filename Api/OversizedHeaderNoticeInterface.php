<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

/**
 * Remembers that a CSP header larger than the configured limit was sent unsplit, so the admin can be told.
 *
 * @api
 */
interface OversizedHeaderNoticeInterface
{
    /**
     * Record an oversized unsplit header.
     *
     * @param string $headerName
     * @param int $bytes
     * @param int $maxBytes
     * @return void
     */
    public function record(string $headerName, int $bytes, int $maxBytes): void;

    /**
     * The last oversized header recorded within the past week, or null.
     *
     * @return array{header: string, bytes: int, limit: int, time: int}|null
     */
    public function recent(): ?array;
}
