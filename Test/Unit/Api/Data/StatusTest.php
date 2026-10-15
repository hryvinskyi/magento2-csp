<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Api\Data;

use Hryvinskyi\Csp\Api\Data\Status;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    public function testDeniedIsStricterThanSkippedIsStricterThanPending(): void
    {
        $this->assertSame(Status::DENIED, Status::PENDING->stricter(Status::DENIED));
        $this->assertSame(Status::DENIED, Status::DENIED->stricter(Status::SKIP));
        $this->assertSame(Status::SKIP, Status::PENDING->stricter(Status::SKIP));
        $this->assertSame(Status::PENDING, Status::PENDING->stricter(Status::PENDING));
    }
}
