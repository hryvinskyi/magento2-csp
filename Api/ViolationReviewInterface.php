<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

/**
 * Remembers, per admin user, which pending violation report groups that user has already been shown.
 *
 * @api
 */
interface ViolationReviewInterface
{
    /**
     * Mark every report group recorded so far as seen by the admin user.
     *
     * @param int $adminUserId
     * @return void
     */
    public function markReviewed(int $adminUserId): void;

    /**
     * Number of pending report groups recorded after the admin user last opened the violation reports.
     *
     * @param int $adminUserId
     * @return int
     */
    public function countUnreviewed(int $adminUserId): int;
}
