<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Test\Unit\Model\Conversion;

use Hryvinskyi\Csp\Model\Conversion\ConversionOutcome;
use PHPUnit\Framework\TestCase;

class ConversionOutcomeTest extends TestCase
{
    public function testTheValueIsAllowedAfterwardsUnlessRefusedOrDisabled(): void
    {
        $allowed = array_values(array_filter(ConversionOutcome::cases(), static fn (ConversionOutcome $outcome): bool => $outcome->isAllowed()));

        $this->assertSame(
            [ConversionOutcome::Created, ConversionOutcome::StoresExtended, ConversionOutcome::AlreadyAllowed, ConversionOutcome::CoveredByWildcard],
            $allowed
        );
    }
}
