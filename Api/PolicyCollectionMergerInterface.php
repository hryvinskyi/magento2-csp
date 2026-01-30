<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Api;

use Magento\Csp\Api\Data\PolicyInterface;

/**
 * Merges or adds a policy into a policy collection.
 */
interface PolicyCollectionMergerInterface
{
    /**
     * Merge policy into collection or add if not exists.
     *
     * @param array<string, PolicyInterface> $policies
     * @param string $policyId
     * @param PolicyInterface $policy
     * @return array<string, PolicyInterface>
     * @throws \RuntimeException
     */
    public function mergeOrAdd(array $policies, string $policyId, PolicyInterface $policy): array;
}
