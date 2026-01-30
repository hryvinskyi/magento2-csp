<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model;

use Hryvinskyi\Csp\Api\PolicyCollectionMergerInterface;
use Magento\Csp\Api\Data\PolicyInterface;
use Magento\Csp\Model\Collector\MergerInterface;

/**
 * @inheritDoc
 */
class PolicyCollectionMerger implements PolicyCollectionMergerInterface
{
    public function __construct(
        private readonly MergerInterface $merger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function mergeOrAdd(array $policies, string $policyId, PolicyInterface $policy): array
    {
        if (!array_key_exists($policyId, $policies)) {
            $policies[$policyId] = $policy;
            return $policies;
        }

        if (!$this->merger->canMerge($policies[$policyId], $policy)) {
            throw new \RuntimeException('Cannot merge a policy of ' . get_class($policy));
        }

        $policies[$policyId] = $this->merger->merge($policies[$policyId], $policy);

        return $policies;
    }
}
