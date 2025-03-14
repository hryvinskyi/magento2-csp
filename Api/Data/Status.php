<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api\Data;

enum Status: int
{
    case PENDING = 0;
    case DENIED = 1;
    case SKIP = 2;

    public function getStatusCode(): int
    {
        return $this->value;
    }

    public static function fromCode(int $code): ?self
    {
        return match($code) {
            0 => self::PENDING,
            1 => self::DENIED,
            2 => self::SKIP,
            default => null,
        };
    }
}