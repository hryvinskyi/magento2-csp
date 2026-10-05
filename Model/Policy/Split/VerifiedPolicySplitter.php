<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Policy\Split;

use Hryvinskyi\Csp\Api\Data\PolicyInterface;
use Hryvinskyi\Csp\Api\PolicySplitterInterface;
use Hryvinskyi\Csp\Model\Policy\PolicyEquivalence;
use Psr\Log\LoggerInterface;

/**
 * Splits with the configured strategy and keeps the parts only when they are safe to send.
 *
 * The parts are kept when there are at least two, each fits the limit, and enforced together they allow exactly what
 * the policy allows ({@see PolicyEquivalence}). Otherwise the policy is returned unsplit and the reason is logged,
 * so a strategy cannot change what the store allows.
 */
class VerifiedPolicySplitter implements PolicySplitterInterface
{
    /**
     * @param PolicySplitterInterface $strategy
     * @param PolicyEquivalence $equivalence
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly PolicySplitterInterface $strategy,
        private readonly PolicyEquivalence $equivalence,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function split(PolicyInterface $policy, int $maxBytes): array
    {
        if ($policy->byteLength() <= $maxBytes) {
            return [$policy];
        }

        $parts = $this->strategy->split($policy, $maxBytes);
        if (count($parts) < 2) {
            return [$policy];
        }

        $oversized = array_filter($parts, static fn (PolicyInterface $part): bool => $part->byteLength() > $maxBytes);
        if ($oversized !== []) {
            $this->logger->warning(sprintf(
                'CSP header not split: %d of %d parts exceed %d bytes.',
                count($oversized),
                count($parts),
                $maxBytes
            ));

            return [$policy];
        }

        $differences = $this->equivalence->splitDifferences($policy, $parts);
        if ($differences !== []) {
            $this->logger->warning(sprintf(
                'CSP header not split: the parts would treat "%s" differently.',
                implode('", "', $differences)
            ));

            return [$policy];
        }

        return $parts;
    }
}
