<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Data;

/**
 * What the admin decided about a report group.
 *
 * @api
 */
enum Status: int
{
    /** Not reviewed yet; new violations are recorded and the admin is alerted. */
    case PENDING = 0;

    /** Rejected; new violations are no longer recorded. */
    case DENIED = 1;

    /** Reviewed and left alone; new violations are recorded without an alert. */
    case SKIP = 2;

    /**
     * Whether new violations of a group with this status are recorded; a denied group records nothing.
     *
     * @return bool
     */
    public function recordsViolations(): bool
    {
        return $this !== self::DENIED;
    }

    /**
     * Whether a group with this status still waits for the admin's review.
     *
     * @return bool
     */
    public function needsAttention(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * The stricter of two statuses: Denied over Skipped over Pending.
     *
     * @param self $other
     * @return self
     */
    public function stricter(self $other): self
    {
        return $this->strictness() >= $other->strictness() ? $this : $other;
    }

    /**
     * Rank of the status, higher meaning stricter.
     *
     * @return int
     */
    private function strictness(): int
    {
        return match ($this) {
            self::PENDING => 0,
            self::SKIP => 1,
            self::DENIED => 2,
        };
    }
}
